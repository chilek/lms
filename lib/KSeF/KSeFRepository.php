<?php

namespace Lms\KSeF;

class KSeFRepository implements KSeFRepositoryInterface
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function getEligibleInvoices(
        int $limit,
        ?int $divisionId = null,
        ?int $customerId = null,
        ?array $docIds = null
    ): array {
        $conditions = [
            'd.cancelled = 0',
            'd.type IN (' . implode(',', [DOC_INVOICE, DOC_CNOTE]) . ')',
            'd.cdate >= kc.boundarydate',
            'kc.delay > -1',
            '?NOW? - d.cdate >= kc.delay',
            '(c.type = ' . CTYPES_COMPANY
                . ' OR kc.allconsumers = 1'
                . ' OR EXISTS (SELECT 1 FROM customerconsents cc WHERE cc.customerid = d.customerid AND cc.type = '
                . CCONSENT_KSEF_INVOICE . '))',
            'NOT EXISTS (
                SELECT 1 FROM ksefdocuments kd
                WHERE kd.docid = d.id
                    AND (kd.status = 0 OR kd.status = 200)
            )',
        ];

        if ($divisionId !== null) {
            $conditions[] = 'd.divisionid = ' . intval($divisionId);
        }
        if ($customerId !== null) {
            $conditions[] = 'd.customerid = ' . intval($customerId);
        }
        $docIds = $this->normalizeIds($docIds);
        if (!empty($docIds)) {
            $conditions[] = 'd.id IN (' . implode(',', $docIds) . ')';
        }

        $query = 'SELECT
                d.id,
                d.divisionid,
                d.div_ten AS division_ten
            FROM documents d
            JOIN customers c ON c.id = d.customerid
            JOIN ksefconfig kc ON kc.divisionid = d.divisionid
            WHERE ' . implode(' AND ', $conditions) . '
            ORDER BY d.cdate, d.id
            LIMIT ' . intval($limit);

        return $this->db->GetAll($query) ?: [];
    }

    public function reserveInvoices(array $documents, int $environment, int $createdAt): array
    {
        if (empty($documents)) {
            throw new \InvalidArgumentException('KSeF invoice reservation requires at least one document.');
        }

        $sessionReferenceNumber = $this->localReference('LOCAL-S', (int) $documents[0]['docid']);

        $this->db->BeginTrans();
        try {
            $reservableDocuments = [];
            $skippedDocuments = [];

            foreach ($documents as $document) {
                $docId = (int) $document['docid'];
                $lockedDocId = $this->db->GetOne(
                    'SELECT id FROM documents WHERE id = ? FOR UPDATE',
                    [
                        $docId,
                    ]
                );
                if (empty($lockedDocId)) {
                    $skippedDocuments[$docId] = 'Invoice not found.';
                    continue;
                }

                $alreadyPendingOrAccepted = $this->db->GetOne(
                    'SELECT 1 FROM ksefdocuments
                    WHERE docid = ?
                        AND (status = ? OR status = ?)',
                    [
                        $docId,
                        KSeFSubmissionService::STATUS_PENDING,
                        KSeFSubmissionService::STATUS_ACCEPTED,
                    ]
                );
                if (!empty($alreadyPendingOrAccepted)) {
                    $skippedDocuments[$docId] = 'Invoice is already reserved for KSeF submission.';
                    continue;
                }

                $reservableDocuments[] = [
                    'docid' => $docId,
                    'hash' => $document['hash'],
                ];
            }

            if (empty($reservableDocuments)) {
                $this->db->RollbackTrans();
                return [
                    'skipped' => $skippedDocuments,
                    'documents' => [],
                ];
            }

            $this->db->Execute(
                'INSERT INTO ksefbatchsessions (ksefnumber, cdate, lastupdate, status, statusdescription, environment)
                VALUES (?, ?, ?, ?, ?, ?)',
                [
                    $sessionReferenceNumber,
                    $createdAt,
                    $createdAt,
                    KSeFSubmissionService::STATUS_PENDING,
                    'Reserved for KSeF submission.',
                    $environment,
                ]
            );
            $sessionId = (int) $this->db->GetLastInsertID('ksefbatchsessions');

            $reservedDocuments = [];
            foreach ($reservableDocuments as $index => $document) {
                $ordinalNumber = $index + 1;
                $this->db->Execute(
                    'INSERT INTO ksefdocuments
                        (batchsessionid, docid, ordinalnumber, hash, status, statusdescription, statusdetails)
                    VALUES (?, ?, ?, ?, ?, ?, ?)',
                    [
                        $sessionId,
                        $document['docid'],
                        $ordinalNumber,
                        $document['hash'],
                        KSeFSubmissionService::STATUS_PENDING,
                        'Reserved for KSeF submission.',
                        null,
                    ]
                );
                $reservedDocuments[] = [
                    'docid' => $document['docid'],
                    'document_id' => (int) $this->db->GetLastInsertID('ksefdocuments'),
                    'ordinalnumber' => $ordinalNumber,
                ];
            }
            $this->db->CommitTrans();

            return [
                'session_id' => $sessionId,
                'session_reference_number' => $sessionReferenceNumber,
                'documents' => $reservedDocuments,
                'skipped' => $skippedDocuments,
            ];
        } catch (\Throwable $e) {
            $this->db->RollbackTrans();
            throw $e;
        }
    }

    public function updateSessionReference(int $id, string $referenceNumber): void
    {
        $this->db->Execute(
            'UPDATE ksefbatchsessions
            SET ksefnumber = ?,
                lastupdate = ?NOW?,
                statusdescription = ?
            WHERE id = ?',
            [
                $referenceNumber,
                'KSeF session opened.',
                $id,
            ]
        );
    }

    public function closeSession(int $id): void
    {
        $this->db->Execute(
            'UPDATE ksefbatchsessions
            SET status = ?,
                lastupdate = ?NOW?,
                statusdescription = ?
            WHERE id = ?',
            [
                KSeFSubmissionService::STATUS_ACCEPTED,
                'KSeF session closed.',
                $id,
            ]
        );
    }

    public function discardSession(int $id): void
    {
        $this->db->BeginTrans();
        try {
            $this->db->Execute(
                'DELETE FROM ksefdocuments
                WHERE batchsessionid = ?',
                [
                    $id,
                ]
            );
            $this->db->Execute(
                'DELETE FROM ksefbatchsessions
                WHERE id = ?',
                [
                    $id,
                ]
            );
            $this->db->CommitTrans();
        } catch (\Throwable $e) {
            $this->db->RollbackTrans();
            throw $e;
        }
    }

    public function getPendingDocuments(
        int $limit,
        ?int $divisionId = null,
        ?int $customerId = null,
        ?array $docIds = null
    ): array {
        $conditions = [
            'kd.status = ?',
            'kbs.ksefnumber NOT LIKE ?',
        ];
        $params = [
            KSeFSubmissionService::STATUS_PENDING,
            'LOCAL-S-%',
        ];

        if ($divisionId !== null) {
            $conditions[] = 'd.divisionid = ?';
            $params[] = $divisionId;
        }
        if ($customerId !== null) {
            $conditions[] = 'd.customerid = ?';
            $params[] = $customerId;
        }
        $docIds = $this->normalizeIds($docIds);
        if (!empty($docIds)) {
            $conditions[] = 'd.id IN (' . implode(',', $docIds) . ')';
        }

        return $this->db->GetAll(
            'SELECT
                kd.id,
                d.id AS docid,
                kd.batchsessionid,
                kd.ordinalnumber,
                session_documents.document_count AS session_document_count,
                kbs.ksefnumber AS session_reference_number,
                kbs.status AS session_status,
                d.divisionid,
                d.div_ten AS seller_ten
            FROM ksefdocuments kd
            JOIN ksefbatchsessions kbs ON kbs.id = kd.batchsessionid
            JOIN documents d ON d.id = kd.docid
            JOIN (
                SELECT batchsessionid, COUNT(*) AS document_count
                FROM ksefdocuments
                GROUP BY batchsessionid
            ) session_documents ON session_documents.batchsessionid = kd.batchsessionid
            WHERE ' . implode(' AND ', $conditions) . '
            ORDER BY kbs.lastupdate, kd.id
            LIMIT ' . intval($limit),
            $params
        ) ?: [];
    }

    public function updateDocumentStatus(
        int $id,
        int $status,
        ?string $statusDescription,
        ?string $statusDetails,
        ?string $ksefNumber,
        ?string $permanentStorageDate
    ): void {
        $this->db->Execute(
            'UPDATE ksefdocuments
            SET status = ?,
                statusdescription = ?,
                statusdetails = ?,
                ksefnumber = ?,
                permanent_storage_date = ?
            WHERE id = ?',
            [
                $status,
                $statusDescription,
                $statusDetails,
                $ksefNumber,
                $permanentStorageDate,
                $id,
            ]
        );
    }

    public function saveUpo(string $ksefNumber, string $content): void
    {
        $result = KSeF::saveUpoContent($ksefNumber, $content);
        if ($result !== true) {
            throw new \RuntimeException(is_string($result) ? $result : 'Couldn\'t save KSeF UPO file.');
        }
    }

    private function localReference(string $prefix, int $docId): string
    {
        return $prefix . '-' . $docId . '-' . substr(hash('sha1', uniqid('', true)), 0, 12);
    }

    private function normalizeIds(?array $ids): array
    {
        if (empty($ids)) {
            return [];
        }

        return array_values(array_unique(array_filter(array_map('intval', $ids))));
    }
}
