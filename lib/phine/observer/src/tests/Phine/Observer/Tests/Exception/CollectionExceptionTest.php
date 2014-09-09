<?php

namespace Phine\Observer\Tests\Exception;

use Phine\Observer\Exception\CollectionException;
use PHPUnit_Framework_TestCase as TestCase;

/**
 * Tests the methods for the {@link CollectionException} class.
 *
 * @author Kevin Herrera <kevin@herrera.io>
 */
class CollectionExceptionTest extends TestCase
{
    /**
     * Make sure we get the message we are expecting.
     */
    public function testIdNotUsed()
    {
        $this->assertEquals(
            'The "test" subject unique identifier is not in use.',
            CollectionException::idNotUsed('test')->getMessage(),
            'Make sure we get the right message.'
        );
    }

    /**
     * Make sure we get the message we are expecting.
     */
    public function testIdUsed()
    {
        $this->assertEquals(
            'The "test" subject unique identifier is already in use.',
            CollectionException::idUsed('test')->getMessage(),
            'Make sure we get the right message.'
        );
    }
}
