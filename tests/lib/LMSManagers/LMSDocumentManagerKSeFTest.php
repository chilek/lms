<?php

namespace LMS\Tests\LMSManagers;

if (!defined('DBVERSION')) {
    define('DBVERSION', 'test');
}
if (!defined('CTYPES_COMPANY')) {
    define('CTYPES_COMPANY', 1);
}
if (!defined('CCONSENT_KSEF_INVOICE')) {
    define('CCONSENT_KSEF_INVOICE', 1);
}

use PHPUnit\Framework\TestCase;

class LMSDocumentManagerKSeFTest extends TestCase
{
    public function testKsefAgeLockAlsoCoversDocumentWithoutSubmission(): void
    {
        $db = $this->createMock(\LMSDBInterface::class);
        $db->expects($this->once())
            ->method('GetOne')
            ->with($this->callback(function (string $query): bool {
                return str_contains($query, '(kd.id IS NULL OR kd.id <> kd2.maxid)');
            }), $this->anything())
            ->willReturn(1);

        $manager = new \LMSDocumentManager($db);

        $this->assertSame(1, $manager->isKsefDocument(123));
    }

    public function testKsefAgeLockAlsoCoversCashRecordWithoutSubmission(): void
    {
        $db = $this->createMock(\LMSDBInterface::class);
        $db->expects($this->once())
            ->method('GetOne')
            ->with($this->callback(function (string $query): bool {
                return str_contains($query, '(kd.id IS NULL OR kd.id <> kd2.maxid)');
            }), $this->anything())
            ->willReturn(1);

        $manager = new \LMSDocumentManager($db);

        $this->assertSame(1, $manager->isKsefDocumentByCashId(456));
    }
}
