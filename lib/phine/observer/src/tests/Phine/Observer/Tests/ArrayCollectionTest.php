<?php

namespace Phine\Observer\Tests;

use Phine\Observer\ArrayCollection;
use Phine\Observer\Subject;
use Phine\Test\Property;
use PHPUnit_Framework_TestCase as TestCase;

/**
 * Tests the methods in the {@link ArrayCollection} class.
 *
 * @author Kevin Herrera <kevin@herrera.io>
 */
class ArrayCollectionTest extends TestCase
{
    /**
     * The array collection to test.
     *
     * @var ArrayCollection
     */
    private $collection;

    /**
     * Make sure we can check if a subject is registered.
     */
    public function testOffsetExists()
    {
        $this->assertFalse(
            isset($this->collection['test']),
            'Make sure the "test" subject is not registered.'
        );

        Property::set($this->collection, 'subjects', array('test' => new Subject()));

        $this->assertTrue(
            isset($this->collection['test']),
            'Make sure the "test" subject is registered.'
        );
    }

    /**
     * Make sure we can get get a registered subject.
     */
    public function testOffsetGet()
    {
        $subject = new Subject();

        Property::set($this->collection, 'subjects', array('test' => $subject));

        $this->assertSame(
            $subject,
            $this->collection['test'],
            'Make sure we get back the same "test" subject.'
        );
    }

    /**
     * Make sure that we can register and replace a subject.
     */
    public function testOffsetSet()
    {
        $subject = new Subject();

        $this->collection['test'] = $subject;

        $this->assertSame(
            array('test' => $subject),
            Property::get($this->collection, 'subjects'),
            'Make sure that the "test" subject is set.'
        );

        $new = new Subject();

        $this->collection['test'] = $new;

        $this->assertSame(
            array('test' => $new),
            Property::get($this->collection, 'subjects'),
            'Make sure that the "test" subject is replaced.'
        );
    }

    /**
     * Make sure that we can unregister a subject.
     */
    public function testOffsetUnset()
    {
        $subject = new Subject();

        Property::set($this->collection, 'subjects', array('test' => $subject));

        unset($this->collection['test']);
        unset($this->collection['test']); // should not thrown an exception

        $this->assertSame(
            array(),
            Property::get($this->collection, 'subjects'),
            'Make sure the "test" subject is unregistered.'
        );
    }

    /**
     * Creates a new instance of {@link ArrayCollection} for testing.
     */
    protected function setUp()
    {
        $this->collection = new ArrayCollection();
    }
}
