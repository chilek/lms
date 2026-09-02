<?php

namespace Lms\KSeF;

use N1ebieski\KSEFClient\ClientBuilder;
use N1ebieski\KSEFClient\Contracts\Resources\ClientResourceInterface;
use N1ebieski\KSEFClient\Factories\EncryptionKeyFactory;
use N1ebieski\KSEFClient\Requests\Sessions\Batch\Close\CloseRequest;
use N1ebieski\KSEFClient\Requests\Sessions\Batch\OpenAndSend\OpenAndSendXmlRequest;
use N1ebieski\KSEFClient\Requests\Sessions\Invoices\List\ListRequest;
use N1ebieski\KSEFClient\Support\Optional;
use N1ebieski\KSEFClient\Validator\Rules\Xml\SchemaRule;
use N1ebieski\KSEFClient\Validator\Validator;
use N1ebieski\KSEFClient\ValueObjects\Mode;
use N1ebieski\KSEFClient\ValueObjects\Requests\ContinuationToken;
use N1ebieski\KSEFClient\ValueObjects\Requests\ReferenceNumber;
use N1ebieski\KSEFClient\ValueObjects\Requests\Sessions\FormCode;
use N1ebieski\KSEFClient\ValueObjects\Requests\Sessions\PageSize;
use N1ebieski\KSEFClient\ValueObjects\SchemaPath;

class N1ebieskiKSeFGateway implements KSeFGatewayInterface
{
    private const INVOICE_LIST_PAGE_SIZE = 1000;

    private $clients = [];

    public function validateXml(string $xml): void
    {
        try {
            Validator::validate($xml, [
                new SchemaRule(SchemaPath::from(FormCode::Fa3->getSchemaPath())),
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
            ->openAndSend(new OpenAndSendXmlRequest(FormCode::Fa3, $xmlDocuments))
            ->object();

        $referenceNumber = $response->referenceNumber ?? null;
        if (!is_string($referenceNumber) || $referenceNumber === '') {
            throw new \RuntimeException('KSeF response does not contain referenceNumber.');
        }

        return $referenceNumber;
    }

    public function closeBatchSession(KSeFConfig $config, string $sellerTen, string $sessionReferenceNumber): void
    {
        $this->buildClient($config, $sellerTen)
            ->sessions()
            ->batch()
            ->close(new CloseRequest(ReferenceNumber::from($sessionReferenceNumber)))
            ->status();
    }

    public function listInvoices(KSeFConfig $config, string $sellerTen, string $sessionReferenceNumber): array
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
                    $continuationToken
                ))
                ->object();

            if (!empty($response->invoices) && is_array($response->invoices)) {
                foreach ($response->invoices as $invoice) {
                    if (empty($invoice->referenceNumber) || !is_string($invoice->referenceNumber)) {
                        continue;
                    }

                    $status = $invoice->status ?? null;
                    $statusCode = (int) ($status->code ?? 0);
                    $statusDetails = $this->extractStatusDetails($invoice);
                    $ksefNumber = $invoice->ksefNumber ?? null;
                    [$originalKsefNumber] = $this->extractDuplicateReferences($statusDetails);
                    $originalKsefNumber = $status?->extensions?->originalKsefNumber ?? $originalKsefNumber;

                    $invoices[] = [
                        'ordinal_number' => isset($invoice->ordinalNumber) ? (int) $invoice->ordinalNumber : null,
                        'status' => $statusCode,
                        'status_description' => $status->description ?? null,
                        'status_details' => $statusDetails,
                        'ksef_number' => $ksefNumber,
                        'permanent_storage_date' => $invoice->permanentStorageDate ?? null,
                        'original_ksef_number' => $originalKsefNumber,
                        'upo' => null,
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

    private function buildClient(KSeFConfig $config, string $sellerTen): ClientResourceInterface
    {
        $clientKey = spl_object_hash($config) . ':' . $sellerTen;
        if (isset($this->clients[$clientKey])) {
            return $this->clients[$clientKey];
        }

        $builder = (new ClientBuilder())
            ->withMode($this->mode($config))
            ->withEncryptionKey(EncryptionKeyFactory::makeRandom())
            ->withValidateXml(false);

        $builder->withIdentifier($sellerTen);

        if ($config->usesApiToken()) {
            $builder->withKsefToken($config->getToken());
        } else {
            $builder->withCertificatePath(
                $config->getCertificatePath(),
                $config->getCertificatePassword()
            );
        }

        $this->clients[$clientKey] = $builder->build();

        return $this->clients[$clientKey];
    }

    private function mode(KSeFConfig $config): Mode
    {
        return match ($config->getEnvironment()) {
            KSeF::ENVIRONMENT_PROD => Mode::Production,
            KSeF::ENVIRONMENT_DEMO => Mode::Demo,
            default => Mode::Test,
        };
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
        ?string $continuationToken = null
    ): ListRequest {
        return new ListRequest(
            ReferenceNumber::from($sessionReferenceNumber),
            PageSize::from(self::INVOICE_LIST_PAGE_SIZE),
            $continuationToken === null
                ? new Optional()
                : ContinuationToken::from($continuationToken)
        );
    }

    private function extractStatusDetails(object $invoice): ?string
    {
        if (!empty($invoice->status->details)) {
            return is_string($invoice->status->details)
                ? $invoice->status->details
                : json_encode($invoice->status->details);
        }

        return null;
    }

    private function extractDuplicateReferences(?string $statusDetails): array
    {
        $ksefNumber = null;
        $sessionReferenceNumber = null;
        if ($statusDetails !== null) {
            if (preg_match('/\b[0-9]{10}-[0-9]{8}-[A-Z0-9]{12}-[A-Z0-9]{2}\b/i', $statusDetails, $matches)) {
                $ksefNumber = strtoupper($matches[0]);
            }
            if (preg_match('/\b[0-9]{8}-[A-Z]{2}-[A-Z0-9]{10}-[A-Z0-9]{10}-[A-Z0-9]{2}\b/i', $statusDetails, $matches)) {
                $sessionReferenceNumber = strtoupper($matches[0]);
            }
        }

        return [$ksefNumber, $sessionReferenceNumber];
    }
}
