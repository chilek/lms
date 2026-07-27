<?php

namespace Lms\KSeF;

class KSeFSubmissionService
{
    const INVOICE_LIST_RETRY_SECONDS = [1, 2, 3, 5, 10];
    const INVOICE_LIST_WAIT_SECONDS = 600;

    private $repository;
    private $gateway;
    private $xmlBuilder;
    private $configProvider;
    private $sleeper;
    private $divisionConfigs = [];

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
    ): array {
        $result = [
            'submitted' => 0,
            'skipped' => 0,
            'errors' => [],
        ];

        $docIds = $this->normalizeDocumentIds($docIds);
        if ($docIds === []) {
            return $result;
        }

        $invoices = $this->repository->getEligibleInvoices(
            $docIds === null ? $config->getMaxDocuments() : count($docIds),
            $divisionId,
            $customerId,
            $docIds
        );
        $invoiceGroups = [];
        foreach ($invoices as $invoice) {
            $xml = call_user_func($this->xmlBuilder, $invoice);
            if (is_array($xml) && isset($xml['error'])) {
                $this->skipInvoice($result, (int) $invoice['id'], $xml['error']);
                continue;
            }
            if (!is_string($xml) || trim($xml) === '') {
                $this->skipInvoice($result, (int) $invoice['id'], 'Empty KSeF XML document.');
                continue;
            }
            try {
                $this->gateway->validateXml($xml);
            } catch (\Throwable $e) {
                $this->skipInvoice($result, (int) $invoice['id'], $e->getMessage());
                continue;
            }

            $sellerTen = preg_replace('/[^0-9]/', '', $invoice['division_ten'] ?? $invoice['div_ten'] ?? '');
            if ($sellerTen === '') {
                $this->skipInvoice($result, (int) $invoice['id'], 'Missing seller TEN.');
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
                'docid' => (int) $invoice['id'],
                'xml' => $xml,
                'hash' => $this->invoiceHash($xml),
            ];
        }

        foreach ($invoiceGroups as $invoiceGroup) {
            $sellerTen = $invoiceGroup['seller_ten'];
            $preparedInvoices = $invoiceGroup['invoices'];
            $groupConfig = $this->configForDivision($invoiceGroup['division_id'], $config);
            $reserved = null;
            $sessionReferenceStored = false;
            $documents = [];
            foreach ($preparedInvoices as $preparedInvoice) {
                $documents[] = [
                    'docid' => $preparedInvoice['docid'],
                    'hash' => $preparedInvoice['hash'],
                ];
            }

            try {
                $reserved = $this->repository->reserveInvoices(
                    $documents,
                    $groupConfig->getEnvironment()
                );

                foreach ($reserved['skipped'] as $docId => $error) {
                    $this->skipInvoice($result, (int) $docId, $error);
                }

                if (empty($reserved['documents'])) {
                    continue;
                }

                $reservedDocIds = [];
                foreach ($reserved['documents'] as $document) {
                    $reservedDocIds[(int) $document['docid']] = true;
                }

                $xmlDocuments = [];
                foreach ($preparedInvoices as $preparedInvoice) {
                    if (isset($reservedDocIds[$preparedInvoice['docid']])) {
                        $xmlDocuments[] = $preparedInvoice['xml'];
                    }
                }

                $sessionReferenceNumber = null;
                try {
                    $sessionReferenceNumber = $this->gateway->sendXmlBatch($groupConfig, $sellerTen, $xmlDocuments);
                    $this->repository->updateSessionReference($reserved['session_id'], $sessionReferenceNumber);
                    $sessionReferenceStored = true;
                } finally {
                    if ($sessionReferenceNumber !== null) {
                        $this->gateway->closeBatchSession($groupConfig, $sellerTen, $sessionReferenceNumber);
                    }
                }

                $this->repository->closeSession($reserved['session_id']);
                $result['submitted'] += count($reserved['documents']);
            } catch (\Throwable $e) {
                if (!empty($reserved['session_id']) && !$sessionReferenceStored) {
                    $this->repository->discardSession((int) $reserved['session_id']);
                }

                $failedInvoices = !empty($reserved['documents']) ? $reserved['documents'] : $documents;
                foreach ($failedInvoices as $failedInvoice) {
                    $this->skipInvoice($result, (int) $failedInvoice['docid'], $e->getMessage());
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
    ): array {
        $result = [
            'updated' => 0,
            'errors' => [],
        ];

        $docIds = $this->normalizeDocumentIds($docIds);
        if ($docIds === []) {
            return $result;
        }

        $documents = $this->repository->getPendingDocuments(
            $docIds === null ? $config->getMaxDocuments() : count($docIds),
            $divisionId,
            $customerId,
            $docIds
        );
        $sessionGroups = [];
        foreach ($documents as $document) {
            try {
                $sellerTen = preg_replace('/[^0-9]/', '', $document['seller_ten'] ?? '');
                if ($sellerTen === '') {
                    throw new \RuntimeException('Missing seller TEN.');
                }
                $documentConfig = $this->configForDivision(
                    isset($document['divisionid']) ? (int) $document['divisionid'] : null,
                    $config
                );
                $groupKey = $sellerTen . ':' . $document['session_reference_number'];
                if (!isset($sessionGroups[$groupKey])) {
                    $sessionGroups[$groupKey] = [
                        'config' => $documentConfig,
                        'seller_ten' => $sellerTen,
                        'reference_number' => $document['session_reference_number'],
                        'documents' => [],
                    ];
                }
                $sessionGroups[$groupKey]['documents'][] = $document;
            } catch (\Throwable $e) {
                $this->addSyncError($result, (int) $document['id'], $e->getMessage());
            }
        }

        foreach ($sessionGroups as $sessionGroup) {
            try {
                $invoicesByOrdinalNumber = $this->waitForInvoices(
                    $sessionGroup['config'],
                    $sessionGroup['seller_ten'],
                    $sessionGroup['reference_number'],
                    $sessionGroup['documents']
                );
            } catch (\Throwable $e) {
                foreach ($sessionGroup['documents'] as $document) {
                    $this->addSyncError($result, (int) $document['id'], $e->getMessage());
                }
                continue;
            }

            foreach ($sessionGroup['documents'] as $document) {
                try {
                    $ordinalNumber = (int) $document['ordinalnumber'];
                    if (!isset($invoicesByOrdinalNumber[$ordinalNumber])) {
                        throw new \RuntimeException(
                            'Couldn\'t find KSeF invoice for session ' . $document['session_reference_number']
                                . ' and ordinal number ' . $document['ordinalnumber'] . '.'
                        );
                    }

                    $this->updateDocument($document, $invoicesByOrdinalNumber[$ordinalNumber]);
                    $result['updated']++;
                } catch (\Throwable $e) {
                    $this->addSyncError($result, (int) $document['id'], $e->getMessage());
                }
            }
        }

        return $result;
    }

    private function skipInvoice(array &$result, int $docId, $error): void
    {
        $result['skipped']++;
        $result['errors'][] = [
            'docid' => $docId,
            'error' => $error,
        ];
    }

    private function addSyncError(array &$result, int $documentId, $error): void
    {
        $result['errors'][] = [
            'id' => $documentId,
            'error' => $error,
        ];
    }

    private function configForDivision(?int $divisionId, KSeFConfig $defaultConfig): KSeFConfig
    {
        if ($this->configProvider === null || $divisionId === null) {
            return $defaultConfig;
        }

        if (isset($this->divisionConfigs[$divisionId])) {
            return $this->divisionConfigs[$divisionId];
        }

        $config = call_user_func($this->configProvider, $divisionId);
        if (!$config instanceof KSeFConfig) {
            throw new \RuntimeException('KSeF config provider must return KSeFConfig.');
        }

        $this->divisionConfigs[$divisionId] = $config;

        return $config;
    }

    private function invoiceHash(string $xml): string
    {
        return base64_encode(hash('sha256', $xml, true));
    }

    private function waitForInvoices(
        KSeFConfig $config,
        string $sellerTen,
        string $sessionReferenceNumber,
        array $documents
    ): array {
        $waitedSeconds = 0;
        for ($attempt = 0; $attempt === 0 || $waitedSeconds < self::INVOICE_LIST_WAIT_SECONDS; $attempt++) {
            if ($attempt > 0) {
                $sleepSeconds = self::INVOICE_LIST_RETRY_SECONDS[
                    min($attempt - 1, count(self::INVOICE_LIST_RETRY_SECONDS) - 1)
                ];
                $sleepSeconds = min($sleepSeconds, self::INVOICE_LIST_WAIT_SECONDS - $waitedSeconds);
                call_user_func($this->sleeper, $sleepSeconds);
                $waitedSeconds += $sleepSeconds;
            }

            $invoicesByOrdinalNumber = [];
            foreach ($this->gateway->listInvoices($config, $sellerTen, $sessionReferenceNumber) as $invoice) {
                if (isset($invoice['ordinal_number'])) {
                    $invoicesByOrdinalNumber[(int) $invoice['ordinal_number']] = $invoice;
                }
            }
            if ($this->containsAllDocuments($invoicesByOrdinalNumber, $documents)) {
                return $invoicesByOrdinalNumber;
            }
        }

        return $invoicesByOrdinalNumber;
    }

    private function containsAllDocuments(array $invoicesByOrdinalNumber, array $documents): bool
    {
        foreach ($documents as $document) {
            if (!isset($invoicesByOrdinalNumber[(int) $document['ordinalnumber']])) {
                return false;
            }
        }

        return true;
    }

    private function updateDocument(array $document, array $status): void
    {
        $statusCode = (int) ($status['status'] ?? KSeF::STATUS_PENDING);
        $ksefNumber = $status['ksef_number'] ?? null;
        if ($statusCode === KSeF::STATUS_DUPLICATE && !empty($status['original_ksef_number'])) {
            $statusCode = KSeF::STATUS_ACCEPTED;
            $ksefNumber = $status['original_ksef_number'];
        }

        if ($statusCode === KSeF::STATUS_ACCEPTED
            && !empty($ksefNumber)
            && !empty($status['upo'])
            && is_string($status['upo'])
        ) {
            $this->repository->saveUpo($ksefNumber, $status['upo']);
        }

        $this->repository->updateDocumentStatus(
            (int) $document['id'],
            $statusCode,
            $status['status_description'] ?? null,
            $status['status_details'] ?? null,
            $ksefNumber,
            $this->normalizeStorageDate($status['permanent_storage_date'] ?? null)
        );
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

    private function normalizeDocumentIds(?array $docIds): ?array
    {
        return $docIds === null
            ? null
            : array_values(array_unique(array_filter(array_map('intval', \Utils::filterIntegers($docIds)))));
    }
}
