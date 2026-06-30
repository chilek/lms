<?php

namespace LMS\Tests\KSeF;

use Lms\KSeF\KSeFRepositoryInterface;

class FakeKSeFRepository implements KSeFRepositoryInterface
{
    public $sessions = [];
    public $documents = [];
    public $sessionReferenceUpdates = [];
    public $sessionCloseUpdates = [];
    public $discardedSessions = [];
    public $statusUpdates = [];
    public $savedUpos = [];
    public $reservationFails = false;
    public $reservedSkipped = [];
    public $failUpoSave = false;
    public $failSessionReferenceUpdate = false;
    public $eligibleDocIds = null;
    public $pendingDivisionId = null;
    public $pendingCustomerId = null;
    public $pendingDocIds = null;

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
        $eligibleInvoices = $this->eligibleInvoices;
        if ($docIds !== null) {
            $eligibleInvoices = array_filter(
                $eligibleInvoices,
                function (array $invoice) use ($docIds): bool {
                    return in_array((int) $invoice['id'], $docIds, true);
                }
            );
        }

        return array_slice(array_values($eligibleInvoices), 0, $limit);
    }

    public function reserveInvoices(array $documents, int $environment, int $createdAt): array
    {
        if ($this->reservationFails) {
            return [
                'skipped' => [],
                'documents' => [],
            ];
        }
        if (!empty($this->reservedSkipped)) {
            return [
                'skipped' => $this->reservedSkipped,
                'documents' => [],
            ];
        }

        $sessionReferenceNumber = 'LOCAL-' . $documents[0]['docid'];
        $this->sessions[] = [
            'reference_number' => $sessionReferenceNumber,
            'environment' => $environment,
            'created_at' => $createdAt,
        ];
        $sessionId = count($this->sessions);
        $reservedDocuments = [];
        foreach ($documents as $index => $document) {
            $this->documents[] = [
                'sessionid' => $sessionId,
                'docid' => (int) $document['docid'],
                'ordinalnumber' => $index + 1,
                'hash' => $document['hash'],
                'status' => 0,
                'statusdescription' => 'Reserved for KSeF submission.',
                'statusdetails' => null,
            ];
            $reservedDocuments[] = [
                'docid' => (int) $document['docid'],
                'document_id' => count($this->documents),
                'ordinalnumber' => $index + 1,
            ];
        }

        return [
            'session_id' => $sessionId,
            'session_reference_number' => $sessionReferenceNumber,
            'documents' => $reservedDocuments,
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
        $this->sessionCloseUpdates[] = [
            'id' => $id,
        ];
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
        $pendingDocuments = $this->pendingDocuments;
        if ($docIds !== null) {
            $pendingDocuments = array_filter(
                $pendingDocuments,
                function (array $document) use ($docIds): bool {
                    return in_array((int) ($document['docid'] ?? 0), $docIds, true);
                }
            );
        }

        return array_slice(array_values($pendingDocuments), 0, $limit);
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
