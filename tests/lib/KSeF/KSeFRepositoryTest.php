<?php

namespace LMS\Tests\KSeF;

use Lms\KSeF\KSeFRepository;
use PHPUnit\Framework\TestCase;

class KSeFRepositoryTest extends TestCase
{
    public function testFailedDatabaseWriteIsReported(): void
    {
        $db = new class {
            public function Execute($query, $params)
            {
                return false;
            }
        };

        $repository = new KSeFRepository($db);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('KSeF database operation failed.');
        $repository->updateSessionReference(1, 'SESSION-1');
    }

    public function testStorageDateUsesUnambiguousUtcForPostgres(): void
    {
        $db = $this->recordingDatabase('postgres');
        $repository = new KSeFRepository($db);

        $repository->updateDocumentStatus(1, 200, null, null, null, '2026-04-24T08:00:00+00:00');

        $this->assertSame('2026-04-24T08:00:00+00:00', $db->params[4]);
    }

    public function testStorageDateUsesUtcWithoutOffsetForMysql(): void
    {
        $db = $this->recordingDatabase('mysqli');
        $repository = new KSeFRepository($db);

        $repository->updateDocumentStatus(1, 200, null, null, null, '2026-04-24T08:00:00+00:00');

        $this->assertSame('2026-04-24 08:00:00', $db->params[4]);
    }

    public function testFailedTransactionStartPreventsWrites(): void
    {
        $db = new class {
            public $executeCalled = false;

            public function BeginTrans()
            {
                return false;
            }

            public function Execute($query, $params)
            {
                $this->executeCalled = true;
                return 1;
            }
        };
        $repository = new KSeFRepository($db);

        try {
            $repository->discardSession(1);
            $this->fail('Failed transaction start should throw.');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('start KSeF database transaction', $e->getMessage());
            $this->assertFalse($db->executeCalled);
        }
    }

    public function testFailedTransactionCommitIsReported(): void
    {
        $db = new class {
            public $rollbackCalled = false;

            public function BeginTrans()
            {
                return true;
            }

            public function Execute($query, $params)
            {
                return 1;
            }

            public function CommitTrans()
            {
                return false;
            }

            public function RollbackTrans()
            {
                $this->rollbackCalled = true;
                return true;
            }
        };
        $repository = new KSeFRepository($db);

        try {
            $repository->discardSession(1);
            $this->fail('Failed transaction commit should throw.');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('commit KSeF database transaction', $e->getMessage());
            $this->assertTrue($db->rollbackCalled);
        }
    }

    private function recordingDatabase(string $type)
    {
        return new class ($type) {
            public $params;
            private $type;

            public function __construct(string $type)
            {
                $this->type = $type;
            }

            public function GetDbType(): string
            {
                return $this->type;
            }

            public function Execute($query, $params)
            {
                $this->params = $params;
                return 1;
            }
        };
    }
}
