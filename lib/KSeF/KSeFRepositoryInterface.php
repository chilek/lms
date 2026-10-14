<?php

namespace Lms\KSeF;

interface KSeFRepositoryInterface
{
    public function getEligibleInvoices(
        int $limit,
        ?int $divisionId = null,
        ?int $customerId = null,
        ?array $docIds = null
    ): array;

    public function reserveInvoices(array $documents, int $environment): array;

    public function getSessionDocuments(int $sessionId): array;

    public function claimSessionRecovery(
        int $sessionId,
        string $expectedReferenceNumber,
        array $documentHashes
    ): bool;

    public function updateSessionReference(int $id, string $referenceNumber): void;

    public function closeSession(int $id): void;

    public function discardSession(int $id): void;

    public function getPendingDocuments(
        int $limit,
        ?int $divisionId = null,
        ?int $customerId = null,
        ?array $docIds = null
    ): array;

    public function updateDocumentStatus(
        int $id,
        int $status,
        ?string $statusDescription,
        ?string $statusDetails,
        ?string $ksefNumber,
        ?string $permanentStorageDate,
        ?string $hash = null
    ): void;
}
