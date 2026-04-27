<?php

namespace Lms\KSeF;

class KSeFSubmissionService
{
    const STATUS_PENDING = 0;
    const STATUS_ACCEPTED = 200;
    const INVOICE_REFERENCE_RETRY_SECONDS = [1, 2, 3, 5, 10];
    const INVOICE_REFERENCE_WAIT_SECONDS = 600;

    private $repository;
    private $gateway;
    private $xmlBuilder;
    private $configProvider;
    private $sleeper;

    public function __construct(
        KSeFRepositoryInterface $repository,
        KSeFGatewayInterface $gateway,
        callable $xmlBuilder,
        ?callable $configProvider = null,
        ?callable $sleeper = null
    ) {
        $this->repository = $repository;
        $this->gateway = $gateway;
        $this->xmlBuilder = $xmlBuilder;
        $this->configProvider = $configProvider;
        $this->sleeper = $sleeper ?: 'sleep';
    }

    public function send(
        KSeFConfig $config,
        ?int $divisionId = null,
        ?int $customerId = null,
        ?array $docIds = null
    ): array
    {
        $result = [
            'submitted' => 0,
            'skipped' => 0,
            'errors' => [],
        ];

        $invoices = $this->repository->getEligibleInvoices(
            $this->getDocumentLimit($config, $docIds),
            $divisionId,
            $customerId,
            $docIds
        );
        $invoiceGroups = [];
        foreach ($invoices as $invoice) {
            $xml = call_user_func($this->xmlBuilder, $invoice);
            if (is_array($xml) && isset($xml['error'])) {
                $result['skipped']++;
                $result['errors'][] = [
                    'docid' => (int) $invoice['id'],
                    'error' => $xml['error'],
                ];
                continue;
            }
            if (!is_string($xml) || trim($xml) === '') {
                $result['skipped']++;
                $result['errors'][] = [
                    'docid' => (int) $invoice['id'],
                    'error' => 'Empty KSeF XML document.',
                ];
                continue;
            }
            try {
                $this->gateway->validateXml($xml);
            } catch (\Throwable $e) {
                $result['skipped']++;
                $result['errors'][] = [
                    'docid' => (int) $invoice['id'],
                    'error' => $e->getMessage(),
                ];
                continue;
            }

            $sellerTen = preg_replace('/[^0-9]/', '', $invoice['division_ten'] ?? $invoice['div_ten'] ?? '');
            if ($sellerTen === '') {
                $result['skipped']++;
                $result['errors'][] = [
                    'docid' => (int) $invoice['id'],
                    'error' => 'Missing seller TEN.',
                ];
                continue;
            }

            $groupDivisionId = isset($invoice['divisionid']) ? (int) $invoice['divisionid'] : null;
            $groupKey = ($groupDivisionId === null ? 'global' : $groupDivisionId) . ':' . $sellerTen;
            if (!isset($invoiceGroups[$groupKey])) {
                $invoiceGroups[$groupKey] = [
                    'division_id' => $groupDivisionId,
                    'seller_ten' => $sellerTen,
                    'invoices' => [],
                ];
            }

            $invoiceGroups[$groupKey]['invoices'][] = [
                'invoice' => $invoice,
                'xml' => $xml,
                'hash' => $this->invoiceHash($xml),
            ];
        }

        foreach ($invoiceGroups as $invoiceGroup) {
            $sellerTen = $invoiceGroup['seller_ten'];
            $preparedInvoices = $invoiceGroup['invoices'];
            $groupConfig = $this->configForDivision($invoiceGroup['division_id'], $config);
            $reserved = null;
            $documents = [];
            foreach ($preparedInvoices as $preparedInvoice) {
                $documents[] = [
                    'docid' => (int) $preparedInvoice['invoice']['id'],
                    'hash' => $preparedInvoice['hash'],
                ];
            }

            try {
                $reserved = $this->repository->reserveInvoices(
                    $documents,
                    $groupConfig->getEnvironment(),
                    time()
                );

                if (empty($reserved['documents'])) {
                    $this->addReservationSkippedErrors($result, $reserved, $preparedInvoices);
                    continue;
                }

                foreach ($reserved['skipped'] as $docId => $error) {
                    $result['skipped']++;
                    $result['errors'][] = [
                        'docid' => (int) $docId,
                        'error' => $error,
                    ];
                }

                $reservedDocIds = [];
                foreach ($reserved['documents'] as $document) {
                    $reservedDocIds[(int) $document['docid']] = true;
                }

                $xmlDocuments = [];
                foreach ($preparedInvoices as $preparedInvoice) {
                    if (isset($reservedDocIds[(int) $preparedInvoice['invoice']['id']])) {
                        $xmlDocuments[] = $preparedInvoice['xml'];
                    }
                }

                $sessionReferenceNumber = null;
                $closeError = null;
                try {
                    $sessionReferenceNumber = $this->gateway->sendXmlBatch($groupConfig, $sellerTen, $xmlDocuments);
                    $this->repository->updateSessionReference($reserved['session_id'], $sessionReferenceNumber);
                } finally {
                    if ($sessionReferenceNumber !== null) {
                        try {
                            $this->gateway->closeBatchSession($groupConfig, $sellerTen, $sessionReferenceNumber);
                        } catch (\Throwable $e) {
                            $closeError = $e;
                        }
                    }
                }

                if ($closeError !== null) {
                    foreach ($reserved['documents'] as $document) {
                        $result['skipped']++;
                        $result['errors'][] = [
                            'docid' => (int) $document['docid'],
                            'error' => 'KSeF session close failed: ' . $closeError->getMessage(),
                        ];
                    }
                    $this->repository->discardSession((int) $reserved['session_id']);
                    continue;
                }

                $this->repository->closeSession($reserved['session_id']);
                $result['submitted'] += count($reserved['documents']);
            } catch (\Throwable $e) {
                if (!empty($reserved['session_id'])) {
                    $this->repository->discardSession((int) $reserved['session_id']);
                }

                $failedInvoices = !empty($reserved['documents']) ? $reserved['documents'] : array_map(
                    function (array $preparedInvoice): array {
                        return [
                            'docid' => (int) $preparedInvoice['invoice']['id'],
                        ];
                    },
                    $preparedInvoices
                );
                foreach ($failedInvoices as $failedInvoice) {
                    $result['skipped']++;
                    $result['errors'][] = [
                        'docid' => (int) $failedInvoice['docid'],
                        'error' => $e->getMessage(),
                    ];
                }
            }
        }

        return $result;
    }

    public function sync(
        KSeFConfig $config,
        ?int $divisionId = null,
        ?int $customerId = null,
        ?array $docIds = null
    ): array
    {
        $result = [
            'updated' => 0,
            'errors' => [],
        ];

        $documents = $this->repository->getPendingDocuments(
            $this->getDocumentLimit($config, $docIds),
            $divisionId,
            $customerId,
            $docIds
        );
        $invoiceReferenceCache = [];
        foreach ($documents as $document) {
            try {
                $sellerTen = preg_replace('/[^0-9]/', '', $document['seller_ten'] ?? '');
                $documentConfig = $this->configForDivision(
                    isset($document['divisionid']) ? (int) $document['divisionid'] : null,
                    $config
                );
                $invoiceReferenceNumber = $this->findInvoiceReference(
                    $documentConfig,
                    $sellerTen,
                    $document,
                    $invoiceReferenceCache
                );

                $status = $this->gateway->getInvoiceStatus(
                    $documentConfig,
                    $sellerTen,
                    $document['session_reference_number'],
                    $invoiceReferenceNumber
                );

                $statusCode = (int) ($status['status'] ?? self::STATUS_PENDING);
                $statusDescription = $status['status_description'] ?? null;
                $statusDetails = $status['status_details'] ?? null;
                $ksefNumber = $status['ksef_number'] ?? null;
                $permanentStorageDate = $this->normalizeStorageDate($status['permanent_storage_date'] ?? null);
                if ($statusCode === 440 && !empty($status['original_ksef_number'])) {
                    $statusCode = self::STATUS_ACCEPTED;
                    $ksefNumber = $status['original_ksef_number'];
                }

                if ($statusCode === self::STATUS_ACCEPTED
                    && !empty($ksefNumber)
                    && isset($status['upo'])
                    && is_string($status['upo'])
                    && $status['upo'] !== ''
                ) {
                    $this->repository->saveUpo($ksefNumber, $status['upo']);
                }

                $this->repository->updateDocumentStatus(
                    (int) $document['id'],
                    $statusCode,
                    $statusDescription,
                    $statusDetails,
                    $ksefNumber,
                    $permanentStorageDate
                );

                $result['updated']++;
            } catch (\Throwable $e) {
                $result['errors'][] = [
                    'id' => (int) $document['id'],
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $result;
    }

    private function configForDivision(?int $divisionId, KSeFConfig $defaultConfig): KSeFConfig
    {
        if ($this->configProvider === null || $divisionId === null) {
            return $defaultConfig;
        }

        $config = call_user_func($this->configProvider, $divisionId);
        if (!$config instanceof KSeFConfig) {
            throw new \RuntimeException('KSeF config provider must return KSeFConfig.');
        }

        return $config;
    }

    private function getDocumentLimit(KSeFConfig $config, ?array $docIds): int
    {
        if ($docIds === null) {
            return $config->getMaxDocuments();
        }

        return max(1, count(array_unique(array_map('intval', $docIds))));
    }

    private function addReservationSkippedErrors(array &$result, array $reserved, array $preparedInvoices): void
    {
        if (!empty($reserved['skipped'])) {
            foreach ($reserved['skipped'] as $docId => $error) {
                $result['skipped']++;
                $result['errors'][] = [
                    'docid' => (int) $docId,
                    'error' => $error,
                ];
            }

            return;
        }

        foreach ($preparedInvoices as $preparedInvoice) {
            $result['skipped']++;
            $result['errors'][] = [
                'docid' => (int) $preparedInvoice['invoice']['id'],
                'error' => 'Invoice is already reserved for KSeF submission.',
            ];
        }
    }

    private function invoiceHash(string $xml): string
    {
        return base64_encode(hash('sha256', $xml, true));
    }

    private function findInvoiceReference(
        KSeFConfig $config,
        string $sellerTen,
        array $document,
        array &$invoiceReferenceCache
    ): string
    {
        $cacheKey = $sellerTen . ':' . $document['session_reference_number'];
        if (!array_key_exists($cacheKey, $invoiceReferenceCache)) {
            $invoiceReferenceCache[$cacheKey] = $this->waitForInvoiceReferences(
                $config,
                $sellerTen,
                $document['session_reference_number'],
                $document
            );
        }
        $invoiceReferences = $invoiceReferenceCache[$cacheKey];
        if (!empty($invoiceReferences) && $this->findInvoiceReferenceNumber($invoiceReferences, $document) === null) {
            $invoiceReferences = $this->waitForInvoiceReferences(
                $config,
                $sellerTen,
                $document['session_reference_number'],
                $document
            );
            $invoiceReferenceCache[$cacheKey] = $invoiceReferences;
        }

        $invoiceReferenceNumber = $this->findInvoiceReferenceNumber($invoiceReferences, $document);
        if ($invoiceReferenceNumber !== null) {
            return $invoiceReferenceNumber;
        }

        throw new \RuntimeException(
            'Couldn\'t find KSeF invoice reference for session ' . $document['session_reference_number']
                . ' and ordinal number ' . $document['ordinalnumber'] . '.'
        );
    }

    private function waitForInvoiceReferences(
        KSeFConfig $config,
        string $sellerTen,
        string $sessionReferenceNumber,
        array $document
    ): array {
        $waitedSeconds = 0;
        $lastInvoiceReferences = [];
        for ($attempt = 0; $attempt === 0 || $waitedSeconds < self::INVOICE_REFERENCE_WAIT_SECONDS; $attempt++) {
            if ($attempt > 0) {
                $sleepSeconds = self::INVOICE_REFERENCE_RETRY_SECONDS[
                    min($attempt - 1, count(self::INVOICE_REFERENCE_RETRY_SECONDS) - 1)
                ];
                $sleepSeconds = min($sleepSeconds, self::INVOICE_REFERENCE_WAIT_SECONDS - $waitedSeconds);
                call_user_func($this->sleeper, $sleepSeconds);
                $waitedSeconds += $sleepSeconds;
            }

            $invoiceReferences = $this->gateway->listInvoiceReferences(
                $config,
                $sellerTen,
                $sessionReferenceNumber
            );
            $lastInvoiceReferences = $invoiceReferences;
            if ($this->findInvoiceReferenceNumber($invoiceReferences, $document) !== null) {
                return $invoiceReferences;
            }
        }

        return $lastInvoiceReferences;
    }

    private function findInvoiceReferenceNumber(array $invoiceReferences, array $document): ?string
    {
        foreach ($invoiceReferences as $invoiceReference) {
            if (isset($invoiceReference['ordinal_number'])
                && (int) $invoiceReference['ordinal_number'] === (int) $document['ordinalnumber']
                && !empty($invoiceReference['reference_number'])
            ) {
                return $invoiceReference['reference_number'];
            }
        }

        if ((int) ($document['session_document_count'] ?? 0) === 1
            && count($invoiceReferences) === 1
            && !empty($invoiceReferences[0]['reference_number'])
        ) {
            return $invoiceReferences[0]['reference_number'];
        }

        return null;
    }

    private function normalizeStorageDate(?string $date): ?string
    {
        if ($date === null || trim($date) === '') {
            return null;
        }

        try {
            return (new \DateTimeImmutable($date))->format('Y-m-d H:i:s');
        } catch (\Exception $e) {
            return null;
        }
    }
}
