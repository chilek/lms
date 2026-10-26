<?php

namespace Lms\KSeF;

interface KSeFGatewayInterface
{
    public function validateXml(string $xml): void;

    public function sendXmlBatch(KSeFConfig $config, string $sellerTen, array $xmlDocuments): string;

    public function closeBatchSession(KSeFConfig $config, string $sellerTen, string $sessionReferenceNumber): void;

    public function listInvoices(KSeFConfig $config, string $sellerTen, string $sessionReferenceNumber): array;
}
