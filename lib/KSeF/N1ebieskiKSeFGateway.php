<?php

namespace Lms\KSeF;

use N1ebieski\KSEFClient\Requests\Sessions\Batch\Close\CloseRequest;
use N1ebieski\KSEFClient\Requests\Sessions\Batch\OpenAndSend\OpenAndSendXmlRequest;
use N1ebieski\KSEFClient\Requests\Sessions\Invoices\KsefUpo\KsefUpoRequest;
use N1ebieski\KSEFClient\ValueObjects\Requests\KsefNumber;
use N1ebieski\KSEFClient\ValueObjects\Requests\ReferenceNumber;
use N1ebieski\KSEFClient\ValueObjects\Requests\Sessions\FormCode;

class N1ebieskiKSeFGateway implements KSeFGatewayInterface
{
    public function validateXml(string $xml): void
    {
        try {
            \N1ebieski\KSEFClient\Validator\Validator::validate($xml, [
                new \N1ebieski\KSEFClient\Validator\Rules\Xml\SchemaRule(
                    \N1ebieski\KSEFClient\ValueObjects\SchemaPath::from(FormCode::Fa3->getSchemaPath())
                ),
            ]);
        } catch (\Throwable $e) {
            throw new \RuntimeException($this->formatXmlValidationException($e), 0, $e);
        }
    }

    public function sendXmlBatch(KSeFConfig $config, string $sellerTen, array $xmlDocuments): string
    {
        $response = $this->buildClient($config, $sellerTen)
            ->sessions()
            ->batch()
            ->openAndSend($this->createOpenAndSendXmlRequest($xmlDocuments))
            ->object();

        return $this->readStringProperty($response, 'referenceNumber');
    }

    public function closeBatchSession(KSeFConfig $config, string $sellerTen, string $sessionReferenceNumber): void
    {
        $this->buildClient($config, $sellerTen)
            ->sessions()
            ->batch()
            ->close($this->createCloseRequest($sessionReferenceNumber))
            ->status();
    }

    public function listInvoiceReferences(KSeFConfig $config, string $sellerTen, string $sessionReferenceNumber): array
    {
        $client = $this->buildClient($config, $sellerTen);
        $invoices = [];
        $continuationToken = null;
        $seenContinuationTokens = [];

        do {
            $response = $client
                ->sessions()
                ->invoices()
                ->list($this->createInvoiceListRequest(
                    $sessionReferenceNumber,
                    $config->getInvoiceReferencePageSize(),
                    $continuationToken
                ))
                ->object();

            if (!empty($response->invoices) && is_array($response->invoices)) {
                foreach ($response->invoices as $invoice) {
                    if (empty($invoice->referenceNumber) || !is_string($invoice->referenceNumber)) {
                        continue;
                    }

                    $invoices[] = [
                        'reference_number' => $invoice->referenceNumber,
                        'ordinal_number' => isset($invoice->ordinalNumber) ? (int) $invoice->ordinalNumber : null,
                    ];
                }
            }

            $continuationToken = !empty($response->continuationToken) && is_string($response->continuationToken)
                ? $response->continuationToken
                : null;
            if ($continuationToken !== null && isset($seenContinuationTokens[$continuationToken])) {
                throw new \RuntimeException('KSeF repeated invoice list continuation token.');
            }
            if ($continuationToken !== null) {
                $seenContinuationTokens[$continuationToken] = true;
            }
        } while ($continuationToken !== null);

        return $invoices;
    }

    public function getInvoiceStatus(
        KSeFConfig $config,
        string $sellerTen,
        string $sessionReferenceNumber,
        string $invoiceReferenceNumber
    ): array {
        $client = $this->buildClient($config, $sellerTen);
        $response = $client
            ->sessions()
            ->invoices()
            ->status([
                'referenceNumber' => $sessionReferenceNumber,
                'invoiceReferenceNumber' => $invoiceReferenceNumber,
            ])
            ->object();

        $status = $response->status ?? null;
        $statusCode = (int) ($status->code ?? 0);
        $ksefNumber = $response->ksefNumber ?? null;
        $statusDetails = $this->extractStatusDetails($response);
        $originalKsefNumber = $response->status->extensions->originalKsefNumber
            ?? $this->extractOriginalKsefNumberFromDetails($statusDetails);
        $originalSessionReferenceNumber = $response->status->extensions->originalSessionReferenceNumber
            ?? $this->extractOriginalSessionReferenceFromDetails($statusDetails);
        $upo = null;

        if ($statusCode === KSeFSubmissionService::STATUS_ACCEPTED && !empty($ksefNumber)) {
            $upo = $client
                ->sessions()
                ->invoices()
                ->upo([
                    'referenceNumber' => $sessionReferenceNumber,
                    'invoiceReferenceNumber' => $invoiceReferenceNumber,
                ])
                ->body();
        }
        if ($statusCode === 440 && !empty($originalKsefNumber) && !empty($originalSessionReferenceNumber)) {
            $upo = $this->fetchOriginalUpo($client, $originalSessionReferenceNumber, $originalKsefNumber);
        }

        return [
            'status' => $statusCode,
            'status_description' => $status->description ?? null,
            'status_details' => $statusDetails,
            'ksef_number' => $ksefNumber,
            'permanent_storage_date' => $this->extractPermanentStorageDate($response),
            'original_ksef_number' => $originalKsefNumber,
            'original_session_reference_number' => $originalSessionReferenceNumber,
            'upo' => $upo,
        ];
    }

    private function buildClient(KSeFConfig $config, ?string $sellerTen = null)
    {
        if (!class_exists('\N1ebieski\KSEFClient\ClientBuilder')) {
            throw new \RuntimeException('Missing n1ebieski/ksef-php-client dependency. Run composer install.');
        }

        $builder = (new \N1ebieski\KSEFClient\ClientBuilder())
            ->withMode($this->mode($config))
            ->withEncryptionKey(\N1ebieski\KSEFClient\Factories\EncryptionKeyFactory::makeRandom())
            ->withValidateXml(true);

        if ($sellerTen !== null && $sellerTen !== '') {
            $builder = $builder->withIdentifier($sellerTen);
        }

        if ($config->getAuthMethod() === KSeFConfig::AUTH_METHOD_TOKEN) {
            $builder = $builder->withKsefToken($config->getToken());
        } else {
            $builder = $builder->withCertificatePath(
                $config->getCertificatePath(),
                $config->getCertificatePassword()
            );
        }

        return $builder->build();
    }

    private function mode(KSeFConfig $config)
    {
        switch ($config->getEnvironment()) {
            case KSeF::ENVIRONMENT_PROD:
                return \N1ebieski\KSEFClient\ValueObjects\Mode::Production;
            case KSeF::ENVIRONMENT_DEMO:
                return \N1ebieski\KSEFClient\ValueObjects\Mode::Demo;
            default:
                return \N1ebieski\KSEFClient\ValueObjects\Mode::Test;
        }
    }

    private function createOpenAndSendXmlRequest(array $xmlDocuments): OpenAndSendXmlRequest
    {
        return new OpenAndSendXmlRequest(FormCode::Fa3, $xmlDocuments);
    }

    private function createCloseRequest(string $sessionReferenceNumber): CloseRequest
    {
        return new CloseRequest(ReferenceNumber::from($sessionReferenceNumber));
    }

    private function createKsefUpoRequest(string $sessionReferenceNumber, string $ksefNumber): KsefUpoRequest
    {
        return new KsefUpoRequest(
            ReferenceNumber::from($sessionReferenceNumber),
            KsefNumber::from($ksefNumber)
        );
    }

    private function fetchOriginalUpo($client, string $sessionReferenceNumber, string $ksefNumber): ?string
    {
        try {
            return $client
                ->sessions()
                ->invoices()
                ->ksefUpo($this->createKsefUpoRequest($sessionReferenceNumber, $ksefNumber))
                ->body();
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function formatXmlValidationException(\Throwable $exception): string
    {
        $message = $exception->getMessage();
        $context = property_exists($exception, 'context') ? $exception->context : null;
        $errors = is_array($context) && isset($context['errors']) && is_array($context['errors'])
            ? $context['errors']
            : [];

        if (empty($errors)) {
            return $message;
        }

        $details = [];
        foreach ($errors as $error) {
            if (!$error instanceof \LibXMLError) {
                continue;
            }

            $details[] = trim($error->message)
                . ' (line ' . $error->line . ', column ' . $error->column . ')';
        }

        return empty($details)
            ? $message
            : $message . ' ' . implode(' ', $details);
    }

    private function createInvoiceListRequest(
        string $sessionReferenceNumber,
        int $pageSize,
        ?string $continuationToken = null
    ): array {
        $request = [
            'referenceNumber' => $sessionReferenceNumber,
            'pageSize' => $pageSize,
        ];

        if ($continuationToken !== null) {
            $request['continuationToken'] = $continuationToken;
        }

        return $request;
    }

    private function readStringProperty($object, string $property): string
    {
        if (!isset($object->{$property}) || !is_string($object->{$property}) || $object->{$property} === '') {
            throw new \RuntimeException('KSeF response does not contain ' . $property . '.');
        }

        return $object->{$property};
    }

    private function extractStatusDetails($response): ?string
    {
        if (!empty($response->status->details)) {
            return is_string($response->status->details)
                ? $response->status->details
                : json_encode($response->status->details);
        }

        return null;
    }

    private function extractOriginalKsefNumberFromDetails(?string $statusDetails): ?string
    {
        if ($statusDetails === null) {
            return null;
        }

        if (preg_match('/\b[0-9]{10}-[0-9]{8}-[A-Z0-9]{12}-[A-Z0-9]{2}\b/i', $statusDetails, $matches)) {
            return strtoupper($matches[0]);
        }

        return null;
    }

    private function extractOriginalSessionReferenceFromDetails(?string $statusDetails): ?string
    {
        if ($statusDetails === null) {
            return null;
        }

        if (preg_match('/\b[0-9]{8}-[A-Z]{2}-[A-Z0-9]{10}-[A-Z0-9]{10}-[A-Z0-9]{2}\b/i', $statusDetails, $matches)) {
            return strtoupper($matches[0]);
        }

        return null;
    }

    private function extractPermanentStorageDate($response): ?string
    {
        foreach (['permanentStorageDate', 'invoicingDate', 'acquisitionTimestamp'] as $field) {
            if (!empty($response->{$field})) {
                return (string) $response->{$field};
            }
        }

        return null;
    }
}
