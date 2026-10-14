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
    public $failSend = false;
    public $invalidXmlDocuments = [];

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
        if ($this->failSend) {
            throw new \RuntimeException('Submission response lost');
        }

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
        return $this->sessionInvoices[$sessionReferenceNumber] ?? [];
    }
}
