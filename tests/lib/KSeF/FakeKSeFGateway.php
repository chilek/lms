<?php

namespace LMS\Tests\KSeF;

use Lms\KSeF\KSeFConfig;
use Lms\KSeF\KSeFGatewayInterface;

class FakeKSeFGateway implements KSeFGatewayInterface
{
    public $closedBatchSessions = [];
    public $sentXmlBatches = [];
    public $sentConfigs = [];
    public $listedSessions = [];
    public $listedConfigs = [];
    public $statusConfigs = [];
    public $sessionInvoiceReferences = [];
    public $invoiceStatuses = [];
    public $failClose = false;
    public $invalidXmlDocuments = [];
    public $emptyInvoiceReferenceResponses = [];
    public $invoiceReferenceResponseSequences = [];

    public function validateXml(string $xml): void
    {
        if (isset($this->invalidXmlDocuments[$xml])) {
            throw new \RuntimeException($this->invalidXmlDocuments[$xml]);
        }
    }

    public function sendXmlBatch(KSeFConfig $config, string $sellerTen, array $xmlDocuments): string
    {
        $this->sentXmlBatches[] = $xmlDocuments;
        $this->sentConfigs[] = [
            'environment' => $config->getEnvironment(),
            'token' => $config->getToken(),
        ];

        return 'SESSION-' . count($this->sentXmlBatches);
    }

    public function closeBatchSession(KSeFConfig $config, string $sellerTen, string $sessionReferenceNumber): void
    {
        if ($this->failClose) {
            throw new \RuntimeException('Close failed');
        }

        $this->closedBatchSessions[] = $sessionReferenceNumber;
    }

    public function listInvoiceReferences(KSeFConfig $config, string $sellerTen, string $sessionReferenceNumber): array
    {
        $this->listedSessions[] = $sessionReferenceNumber;
        $this->listedConfigs[] = [
            'environment' => $config->getEnvironment(),
            'token' => $config->getToken(),
        ];
        if (!empty($this->emptyInvoiceReferenceResponses[$sessionReferenceNumber])) {
            $this->emptyInvoiceReferenceResponses[$sessionReferenceNumber]--;

            return [];
        }
        if (!empty($this->invoiceReferenceResponseSequences[$sessionReferenceNumber])) {
            return array_shift($this->invoiceReferenceResponseSequences[$sessionReferenceNumber]);
        }

        return $this->sessionInvoiceReferences[$sessionReferenceNumber] ?? [];
    }

    public function getInvoiceStatus(
        KSeFConfig $config,
        string $sellerTen,
        string $sessionReferenceNumber,
        string $invoiceReferenceNumber
    ): array {
        $this->statusConfigs[] = [
            'environment' => $config->getEnvironment(),
            'token' => $config->getToken(),
        ];

        return $this->invoiceStatuses[$sessionReferenceNumber . ':' . $invoiceReferenceNumber];
    }
}
