<?php

namespace LMS\Tests\KSeF;

require_once __DIR__ . '/../../../lib/KSeF/KSeFSynchronizationLock.php';

use Lms\KSeF\KSeFSynchronizationLock;
use PHPUnit\Framework\TestCase;

class KSeFSynchronizationLockTest extends TestCase
{
    public function testOnlyOneSynchronizationCanHoldLock(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'lms-ksef-lock-');
        $first = new KSeFSynchronizationLock();
        $second = new KSeFSynchronizationLock();

        try {
            $this->assertTrue($first->acquire($path));
            $this->assertFalse($second->acquire($path));
            $first->release();
            $this->assertTrue($second->acquire($path));
        } finally {
            $first->release();
            $second->release();
            unlink($path);
        }
    }
}
