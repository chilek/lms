<?php

namespace Phine\Observer\Tests\Exception;

use Phine\Observer\Exception\SubjectException;
use PHPUnit_Framework_TestCase as TestCase;

/**
 * Tests the methods for the `SubjectException` class.
 *
 * @author Kevin Herrera <kevin@herrera.io>
 */
class SubjectExceptionTest extends TestCase
{
    /**
     * Make sure that we get the message we are expecting.
     */
    public function testAlreadyUpdating()
    {
        $this->assertEquals(
            'There is already an update in progress.',
            SubjectException::alreadyUpdating()->getMessage(),
            'Make sure we get the right message.'
        );
    }

    /**
     * Make sure that we get the message we are expecting.
     */
    public function testNotUpdating()
    {
        $this->assertEquals(
            'There is no update in progress to interrupt.',
            SubjectException::notUpdating()->getMessage(),
            'Make sure we get the right message.'
        );
    }
}
