<?php

namespace Phine\Observer\Tests;

use Phine\Observer\Collection;
use Phine\Observer\Subject;
use Phine\Observer\Test\Observer;
use Phine\Test\Property;
use PHPUnit_Framework_TestCase as TestCase;

/**
 * Tests the methods in the {@link Collection} class.
 *
 * @author Kevin Herrera <kevin@herrera.io>
 */
class CollectionTest extends TestCase
{
    /**
     * The collection to test.
     *
     * @var Collection
     */
    private $collection;

    /**
     * Make sure that we can copy the subjects of another collection.
     */
    public function testCopySubjects()
    {
        $a = new Subject();
        $b = new Subject();
        $c = new Subject();
        $d = new Subject();

        Property::set(
            $this->collection,
            'subjects',
            array(
                'b' => $b
            )
        );

        $collection = new Collection();

        Property::set(
            $collection,
            'subjects',
            array(
                'a' => $a,
                'b' => $c,
                'c' => $d
            )
        );

        $this->collection->copySubjects($collection);

        $this->assertSame(
            array(
                'b' => $c,
                'a' => $a,
                'c' => $d,
            ),
            Property::get($this->collection, 'subjects'),
            'The subjects should have been copied correctly.'
        );
    }

    /**
     * Make sure that we can retrieve a registered subject.
     */
    public function testGetSubject()
    {
        $subject = new Subject();

        Property::set($this->collection, 'subjects', array('test' => $subject));

        $this->assertSame(
            $subject,
            $this->collection->getSubject('test'),
            'Make sure we get back the same subject.'
        );
    }

    /**
     * Make sure that an exception is thrown if the subject is not registered.
     */
    public function testGetSubjectNotRegistered()
    {
        $this->setExpectedException(
            'Phine\\Observer\\Exception\\CollectionException',
            'The "test" subject unique identifier is not in use.'
        );

        $this->collection->getSubject('test');
    }

    /**
     * Make sure that we can retrieve the registered subjects.
     */
    public function testGetSubjects()
    {
        $subjects = array(
            'a' => new Subject(),
            'b' => new Subject(),
            'c' => new Subject(),
        );

        Property::set($this->collection, 'subjects', $subjects);

        $this->assertSame(
            $subjects,
            $this->collection->getSubjects(),
            'The list of subjects should be returned.'
        );
    }

    /**
     * Make sure we can check if a subject is registered.
     */
    public function testIsSubjectRegistered()
    {
        $this->assertFalse(
            $this->collection->isSubjectRegistered('test'),
            'Make sure the "test" subject is not registered.'
        );

        Property::set(
            $this->collection,
            'subjects',
            array('test' => new Subject())
        );

        $this->assertTrue(
            $this->collection->isSubjectRegistered('test'),
            'Make sure the "test" subject is registered.'
        );
    }

    /**
     * Make sure we can register a subject.
     */
    public function testRegisterSubject()
    {
        $subject = new Subject();


        $this->collection->registerSubject('test', $subject);

        $this->assertSame(
            array('test' => $subject),
            Property::get($this->collection, 'subjects'),
            'Make sure that the "test" subject is registered.'
        );
    }

    /**
     * Make sure an exception is thrown if the subject is already registered.
     */
    public function testRegisterSubjectDuplicate()
    {
        $subject = new Subject();

        $this->collection->registerSubject('test', $subject);

        $this->setExpectedException(
            'Phine\\Observer\\Exception\\CollectionException',
            'The "test" subject unique identifier is already in use.'
        );

        $this->collection->registerSubject('test', $subject);
    }

    /**
     * Make sure that we can replace a registered subject.
     */
    public function testReplaceSubject()
    {
        $subject = new Subject();

        Property::set($this->collection, 'subjects', array('test' => $subject));

        $new = new Subject();

        $this->collection->replaceSubject('test', $new);

        $this->assertSame(
            array('test' => $new),
            Property::get($this->collection, 'subjects'),
            'Make sure the "test" subject is replaced.'
        );
    }

    /**
     * Make sure an exception is thrown if we replace a non-existent subject.
     */
    public function testReplaceSubjectNotRegistered()
    {
        $subject = new Subject();

        $this->setExpectedException(
            'Phine\\Observer\\Exception\\CollectionException',
            'The "test" subject unique identifier is not in use.'
        );

        $this->collection->replaceSubject('test', $subject);
    }

    /**
     * Make sure that we can replace the subjects using another collection.
     */
    public function testReplaceSubjects()
    {
        $a = new Subject();
        $b = new Subject();
        $c = new Subject();
        $d = new Subject();

        Property::set(
            $this->collection,
            'subjects',
            array(
                'a' => $a
            )
        );

        $collection = new Collection();

        Property::set(
            $collection,
            'subjects',
            array(
                'b' => $b,
                'c' => $c,
                'd' => $d
            )
        );

        $this->collection->replaceSubjects($collection);

        $this->assertSame(
            array(
                'b' => $b,
                'c' => $c,
                'd' => $d,
            ),
            Property::get($this->collection, 'subjects'),
            'The subjects should have been replaced.'
        );
    }

    /**
     * Make sure that we can unregister a subject.
     */
    public function testUnregisterSubject()
    {
        Property::set($this->collection, 'subjects', array('test' =>  new Subject()));

        $this->collection->unregisterSubject('test');

        $this->assertSame(
            array(),
            Property::get($this->collection, 'subjects'),
            'Make sure the "test" subject is unregistered.'
        );
    }

    /**
     * Make sure an exception is thrown if we unregister an unregistered subject.
     */
    public function testUnregisterSubjectNotRegistered()
    {
        $this->setExpectedException(
            'Phine\\Observer\\Exception\\CollectionException',
            'The "test" subject unique identifier is not in use.'
        );

        $this->collection->unregisterSubject('test');
    }

    /**
     * Creates a new collection to test.
     */
    protected function setUp()
    {
        $this->collection = new Collection();
    }
}
