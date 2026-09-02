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
