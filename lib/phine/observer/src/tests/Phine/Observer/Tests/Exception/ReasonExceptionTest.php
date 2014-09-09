<?php

namespace Phine\Observer\Tests\Exception;

use Phine\Observer\Exception\ReasonException;
use PHPUnit_Framework_TestCase as TestCase;

/**
 * Tests the methods for the {@link ReasonException} class.
 *
 * @author Kevin Herrera <kevin@herrera.io>
 */
class ReasonExceptionTest extends TestCase
{
    /**
     * Make sure that we get the message we are expecting.
     */
    public function testNotSpecified()
    {
        $this->assertEquals(
            '(no reason specified)',
            ReasonException::notSpecified()->getMessage(),
            'Make sure we get the right message.'
        );
    }
}
