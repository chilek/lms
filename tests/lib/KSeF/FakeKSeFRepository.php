<?php

namespace LMS\Tests\KSeF;

use Lms\KSeF\KSeFRepositoryInterface;

class FakeKSeFRepository implements KSeFRepositoryInterface
{
    public $reservations = [];
    public $sessionReferenceUpdates = [];
    public $sessionCloseUpdates = [];
    public $discardedSessions = [];
    public $statusUpdates = [];
    public $savedUpos = [];
    public $reservedSkipped = [];
    public $failUpoSave = false;
    public $failSessionReferenceUpdate = false;
    public $eligibleDocIds = null;
    public $eligibleLimit = null;
    public $pendingDivisionId = null;
    public $pendingCustomerId = null;
    public $pendingDocIds = null;
    public $pendingLimit = null;

    private $eligibleInvoices;
    private $pendingDocuments;

    public function __construct(array $eligibleInvoices = [], array $pendingDocuments = [])
    {
        $this->eligibleInvoices = $eligibleInvoices;
        $this->pendingDocuments = $pendingDocuments;
    }

    public function getEligibleInvoices(
        int $limit,
        ?int $divisionId = null,
        ?int $customerId = null,
        ?array $docIds = null
    ): array {
        $this->eligibleDocIds = $docIds;
        $this->eligibleLimit = $limit;

        return $this->eligibleInvoices;
    }

    public function reserveInvoices(array $documents, int $environment, int $createdAt): array
    {
        if (!empty($this->reservedSkipped)) {
            return [
                'skipped' => $this->reservedSkipped,
                'documents' => [],
            ];
        }

        $this->reservations[] = [
            'documents' => $documents,
            'environment' => $environment,
        ];
        $sessionId = count($this->reservations);

        return [
            'session_id' => $sessionId,
            'documents' => array_map(
                fn (array $document): array => ['docid' => (int) $document['docid']],
                $documents
            ),
            'skipped' => [],
        ];
    }

    public function updateSessionReference(int $id, string $referenceNumber): void
    {
        if ($this->failSessionReferenceUpdate) {
            throw new \RuntimeException('Session reference update failed');
        }

        $this->sessionReferenceUpdates[] = [
            'id' => $id,
            'reference_number' => $referenceNumber,
        ];
    }

    public function closeSession(int $id): void
    {
        $this->sessionCloseUpdates[] = $id;
    }

    public function discardSession(int $id): void
    {
        $this->discardedSessions[] = $id;
    }

    public function getPendingDocuments(
        int $limit,
        ?int $divisionId = null,
        ?int $customerId = null,
        ?array $docIds = null
    ): array {
        $this->pendingDivisionId = $divisionId;
        $this->pendingCustomerId = $customerId;
        $this->pendingDocIds = $docIds;
        $this->pendingLimit = $limit;

        return $this->pendingDocuments;
    }

    public function updateDocumentStatus(
        int $id,
        int $status,
        ?string $statusDescription,
        ?string $statusDetails,
        ?string $ksefNumber,
        ?string $permanentStorageDate
    ): void {
        $this->statusUpdates[] = [
            'id' => $id,
            'status' => $status,
            'status_description' => $statusDescription,
            'status_details' => $statusDetails,
            'ksef_number' => $ksefNumber,
            'permanent_storage_date' => $permanentStorageDate,
        ];
    }

    public function saveUpo(string $ksefNumber, string $content): void
    {
        if ($this->failUpoSave) {
            throw new \RuntimeException('UPO save failed');
        }

        $this->savedUpos[] = [
            'ksef_number' => $ksefNumber,
            'content' => $content,
        ];
    }
}
