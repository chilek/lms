<?php

namespace LMS\Tests\KSeF;

use Lms\KSeF\KSeFConfig;
use Lms\KSeF\KSeFGatewayInterface;

class FakeKSeFGateway implements KSeFGatewayInterface
{
    public $closedBatchSessions = [];
    public $sentXmlBatches = [];
    public $sentTokens = [];
    public $listedSessions = [];
    public $listedTokens = [];
    public $sessionInvoices = [];
    public $failClose = false;
    public $invalidXmlDocuments = [];
    public $invoiceResponseSequences = [];

    public function validateXml(string $xml): void
    {
        if (isset($this->invalidXmlDocuments[$xml])) {
            throw new \RuntimeException($this->invalidXmlDocuments[$xml]);
        }
    }

    public function sendXmlBatch(KSeFConfig $config, string $sellerTen, array $xmlDocuments): string
    {
        $this->sentXmlBatches[] = $xmlDocuments;
        $this->sentTokens[] = $config->getToken();

        return 'SESSION-' . count($this->sentXmlBatches);
    }

    public function closeBatchSession(KSeFConfig $config, string $sellerTen, string $sessionReferenceNumber): void
    {
        if ($this->failClose) {
            throw new \RuntimeException('Close failed');
        }

        $this->closedBatchSessions[] = $sessionReferenceNumber;
    }

    public function listInvoices(KSeFConfig $config, string $sellerTen, string $sessionReferenceNumber): array
    {
        $this->listedSessions[] = $sessionReferenceNumber;
        $this->listedTokens[] = $config->getToken();
        if (!empty($this->invoiceResponseSequences[$sessionReferenceNumber])) {
            return array_shift($this->invoiceResponseSequences[$sessionReferenceNumber]);
        }

        return $this->sessionInvoices[$sessionReferenceNumber] ?? [];
    }
}
