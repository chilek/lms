<?php

namespace Lms\KSeF;

class KSeFRepository implements KSeFRepositoryInterface
{
    private const RECOVERY_DELAY = 18 * 60 * 60;

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

    public function reserveInvoices(array $documents, int $environment): array
    {
        if (empty($documents)) {
            throw new \InvalidArgumentException('KSeF invoice reservation requires at least one document.');
        }

        $sessionReferenceNumber = 'LOCAL-S-' . (int) $documents[0]['docid'] . '-' . \Utils::randomBytes(12);

        $this->beginTransactionOrFail();
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
                        KSeF::STATUS_PENDING,
                        KSeF::STATUS_ACCEPTED,
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

            $this->executeOrFail(
                'INSERT INTO ksefbatchsessions (ksefnumber, cdate, lastupdate, status, statusdescription, environment)
                VALUES (?, ?NOW?, ?NOW?, ?, ?, ?)',
                [
                    $sessionReferenceNumber,
                    KSeF::STATUS_PENDING,
                    'Reserved for KSeF submission.',
                    $environment,
                ]
            );
            $sessionId = (int) $this->db->GetLastInsertID('ksefbatchsessions');

            $reservedDocuments = [];
            foreach ($reservableDocuments as $index => $document) {
                $ordinalNumber = $index + 1;
                $this->executeOrFail(
                    'INSERT INTO ksefdocuments
                        (batchsessionid, docid, ordinalnumber, hash, status, statusdescription, statusdetails)
                    VALUES (?, ?, ?, ?, ?, ?, ?)',
                    [
                        $sessionId,
                        $document['docid'],
                        $ordinalNumber,
                        $document['hash'],
                        KSeF::STATUS_PENDING,
                        'Reserved for KSeF submission.',
                        null,
                    ]
                );
                $reservedDocuments[] = [
                    'docid' => $document['docid'],
                ];
            }
            $this->commitTransactionOrFail();

            return [
                'session_id' => $sessionId,
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
        $this->executeOrFail(
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

    public function getSessionDocuments(int $sessionId): array
    {
        return $this->db->GetAll(
            'SELECT kd.id, kd.docid, kd.ordinalnumber, kd.hash, kd.statusdetails,
                kbs.ksefnumber AS session_reference_number, kbs.id AS session_id,
                kbs.status AS session_status, d.divisionid, d.div_ten AS seller_ten
            FROM ksefdocuments kd
            JOIN ksefbatchsessions kbs ON kbs.id = kd.batchsessionid
            JOIN documents d ON d.id = kd.docid
            WHERE kd.batchsessionid = ? AND kd.status = ?
            ORDER BY kd.ordinalnumber',
            [$sessionId, KSeF::STATUS_PENDING]
        ) ?: [];
    }

    public function claimSessionRecovery(
        int $sessionId,
        string $expectedReferenceNumber,
        array $documentHashes
    ): bool {
        $this->beginTransactionOrFail();
        try {
            $affectedRows = $this->executeOrFail(
                'UPDATE ksefbatchsessions
                SET ksefnumber = ?, lastupdate = ?NOW?, statusdescription = ?
                WHERE id = ? AND ksefnumber = ? AND ?NOW? - lastupdate >= ?',
                [
                    'RECOVERY-S-' . $sessionId,
                    'KSeF recovery submission attempted.',
                    $sessionId,
                    $expectedReferenceNumber,
                    self::RECOVERY_DELAY,
                ]
            );
            if ($affectedRows !== 1) {
                $this->db->RollbackTrans();
                return false;
            }

            foreach ($documentHashes as $documentId => $hashes) {
                $affectedRows = $this->executeOrFail(
                    'UPDATE ksefdocuments SET statusdetails = ?
                    WHERE id = ? AND batchsessionid = ? AND status = ?',
                    [
                        json_encode($hashes),
                        $documentId,
                        $sessionId,
                        KSeF::STATUS_PENDING,
                    ]
                );
                if ($affectedRows !== 1) {
                    throw new \RuntimeException('KSeF recovery document claim failed.');
                }
            }
            $this->commitTransactionOrFail();
            return true;
        } catch (\Throwable $e) {
            $this->db->RollbackTrans();
            throw $e;
        }
    }

    public function closeSession(int $id): void
    {
        $this->executeOrFail(
            'UPDATE ksefbatchsessions
            SET status = ?,
                lastupdate = ?NOW?,
                statusdescription = ?
            WHERE id = ?',
            [
                KSeF::STATUS_ACCEPTED,
                'KSeF session closed.',
                $id,
            ]
        );
    }

    public function discardSession(int $id): void
    {
        $this->beginTransactionOrFail();
        try {
            $this->executeOrFail(
                'DELETE FROM ksefdocuments
                WHERE batchsessionid = ?',
                [
                    $id,
                ]
            );
            $this->executeOrFail(
                'DELETE FROM ksefbatchsessions
                WHERE id = ?',
                [
                    $id,
                ]
            );
            $this->commitTransactionOrFail();
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
            '(kbs.ksefnumber NOT LIKE ? OR ?NOW? - kbs.lastupdate >= ?)',
        ];
        $params = [
            KSeF::STATUS_PENDING,
            'RECOVERY-S-%',
            'LOCAL-S-%',
            self::RECOVERY_DELAY,
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
                kd.ordinalnumber,
                kd.hash,
                kd.statusdetails,
                kbs.ksefnumber AS session_reference_number,
                kbs.id AS session_id,
                kbs.status AS session_status,
                d.divisionid,
                d.div_ten AS seller_ten
            FROM ksefdocuments kd
            JOIN ksefbatchsessions kbs ON kbs.id = kd.batchsessionid
            JOIN documents d ON d.id = kd.docid
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
        ?string $permanentStorageDate,
        ?string $hash = null
    ): void {
        $this->executeOrFail(
            'UPDATE ksefdocuments
            SET status = ?,
                statusdescription = ?,
                statusdetails = ?,
                ksefnumber = ?,
                permanent_storage_date = ?,
                hash = COALESCE(?, hash)
            WHERE id = ?',
            [
                $status,
                $statusDescription,
                $statusDetails,
                $ksefNumber,
                $this->storageDateForDatabase($permanentStorageDate),
                $hash,
                $id,
            ]
        );
    }

    private function normalizeIds(?array $ids): array
    {
        return array_values(array_unique(array_filter(array_map('intval', \Utils::filterIntegers($ids)))));
    }

    private function executeOrFail(string $query, array $params): int
    {
        $result = $this->db->Execute($query, $params);
        if ($result === false) {
            throw new \RuntimeException('KSeF database operation failed.');
        }

        return $result;
    }

    private function beginTransactionOrFail(): void
    {
        if ($this->db->BeginTrans() === false) {
            throw new \RuntimeException('Unable to start KSeF database transaction.');
        }
    }

    private function commitTransactionOrFail(): void
    {
        if ($this->db->CommitTrans() === false) {
            throw new \RuntimeException('Unable to commit KSeF database transaction.');
        }
    }

    private function storageDateForDatabase(?string $date): ?string
    {
        if ($date === null || !method_exists($this->db, 'GetDbType')) {
            return $date;
        }

        if (in_array($this->db->GetDbType(), ['mysql', 'mysqli'], true)) {
            return (new \DateTimeImmutable($date))
                ->setTimezone(new \DateTimeZone('UTC'))
                ->format('Y-m-d H:i:s');
        }

        return $date;
    }
}
