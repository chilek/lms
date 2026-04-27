<?php

namespace Lms\KSeF;

interface KSeFGatewayInterface
{
    public function validateXml(string $xml): void;

    public function sendXmlBatch(KSeFConfig $config, string $sellerTen, array $xmlDocuments): string;

    public function closeBatchSession(KSeFConfig $config, string $sellerTen, string $sessionReferenceNumber): void;

    public function listInvoiceReferences(KSeFConfig $config, string $sellerTen, string $sessionReferenceNumber): array;

    public function getInvoiceStatus(
        KSeFConfig $config,
        string $sellerTen,
        string $sessionReferenceNumber,
        string $invoiceReferenceNumber
    ): array;
}
