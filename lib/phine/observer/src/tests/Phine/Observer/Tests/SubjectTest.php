<?php

namespace Phine\Observer\Tests;

use Phine\Observer\Exception\ReasonException;
use Phine\Observer\Exception\SubjectException;
use Phine\Observer\Subject;
use Phine\Observer\Test\Observer;
use Phine\Test\Property;
use PHPUnit_Framework_TestCase as TestCase;

/**
 * Tests the methods for the {@link Subject} class.
 *
 * @author Kevin Herrera <kevin@herrera.io>
 */
class SubjectTest extends TestCase
{
    /**
     * The subject to test.
     *
     * @var Subject
     */
    private $subject;

    /**
     * Make sure that we can copy the observers of another subject.
     */
    public function testCopyObservers()
    {
        $a = new Observer();
        $b = new Observer();
        $c = new Observer();
        $d = new Observer();

        Property::set(
            $this->subject,
            'observers',
            array(
                123 => array($a),
            )
        );

        $another = new Subject();

        Property::set(
            $another,
            'observers',
            array(
                0 => array($b),
                123 => array($c),
                456 => array($d),
            )
        );

        $this->subject->copyObservers($another);

        $this->assertSame(
            array(
                123 => array($a, $c),
                0 => array($b),
                456 => array($d),
            ),
            Property::get($this->subject, 'observers'),
            'The observers should have been copied correctly.'
        );
    }

    /**
     * Make sure that we can get the reason for the interrupted update.
     */
    public function testGetInterruptReason()
    {
        $reason = ReasonException::notSpecified();

        Property::set($this->subject, 'reason', $reason);

        $this->assertSame(
            $reason,
            $this->subject->getInterruptReason(),
            'Make sure we can retrieve the reason for the interrupted update.'
        );
    }

    /**
     * Make sure that we can retrieve the registered observers.
     */
    public function testGetObservers()
    {
        $observers = array(
            0 => array(new Observer()),
            123 => array(new Observer(), new Observer()),
            456 => array(new Observer()),
        );

        Property::set($this->subject, 'observers', $observers);

        $this->assertSame(
            $observers,
            $this->subject->getObservers(),
            'The same observers should be returned.'
        );
    }

    /**
     * Make sure that we can check if an observer is registered.
     */
    public function testHasObserver()
    {
        $observer = new Observer();

        $this->assertFalse(
            $this->subject->hasObserver($observer),
            'Make sure we do not find the observer in a new subject.'
        );

        Property::set($this->subject, 'observers', array(array($observer)));

        $this->assertTrue(
            $this->subject->hasObserver($observer),
            'Make sure we can confirm that an observer is registered.'
        );

        $this->assertFalse(
            $this->subject->hasObserver($observer, 123),
            'Make sure that using a different priority returns false.'
        );

        $this->assertTrue(
            $this->subject->hasObserver($observer, 0),
            'Make sure that using the same priority returns true.'
        );
    }

    /**
     * Make sure that we can check if any observer is registered.
     */
    public function testHasObservers()
    {
        Property::set($this->subject, 'observers', array(123 => array()));

        $this->assertFalse(
            $this->subject->hasObservers(),
            'Make sure that no observers are registered with a new subject.'
        );

        Property::set(
            $this->subject,
            'observers',
            array(123 => array(new Observer()))
        );

        $this->assertTrue(
            $this->subject->hasObservers(),
            'Make sure that checking any priority returns true if registered.'
        );

        $this->assertFalse(
            $this->subject->hasObservers(0),
            'Make sure that unused priorities return false.'
        );

        $this->assertTrue(
            $this->subject->hasObservers(123),
            'Make sure that used priorities return true.'
        );
    }

    /**
     * Make sure that we can interrupt an update.
     */
    public function testInterruptUpdate()
    {
        Property::set($this->subject, 'updating', true);

        $this->subject->interruptUpdate();

        $this->assertEquals(
            '(no reason specified)',
            Property::get($this->subject, 'reason')->getMessage(),
            'Make sure that a default reason is used if none is given.'
        );

        $reason = new ReasonException('Just testing.');

        Property::set($this->subject, 'updating', true);

        $this->subject->interruptUpdate($reason);

        $this->assertSame(
            $reason,
            Property::get($this->subject, 'reason'),
            'Make sure that we can use our own reason for interrupting.'
        );

        $this->assertFalse(
            Property::get($this->subject, 'updating'),
            'The subject should no longer be in the updating state.'
        );

        $this->setExpectedException(
            'Phine\\Observer\\Exception\\SubjectException',
            'There is no update in progress to interrupt.'
        );

        $this->subject->interruptUpdate();
    }

    /**
     * Make sure that we can check if an update was interrupted.
     */
    public function testIsInterrupted()
    {
        $this->assertFalse(
            $this->subject->isInterrupted(),
            'By default, the subject should not be interrupted.'
        );

        Property::set($this->subject, 'reason', new ReasonException());

        $this->assertTrue(
            $this->subject->isInterrupted(),
            'The subject should now be interrupted.'
        );
    }

    /**
     * Make sure that sure can check if an update is in progress.
     */
    public function testIsUpdating()
    {
        $this->assertFalse(
            $this->subject->isUpdating(),
            'By default, the subject should not be in the updating state.'
        );

        Property::set($this->subject, 'updating', true);

        $this->assertTrue(
            $this->subject->isUpdating(),
            'The subject should now be in the updating state.'
        );
    }

    /**
     * Make sure that we can notify all registered observers.
     */
    public function testNotifyObservers()
    {
        // perform a regular update
        Observer::$counter = 0;

        $observers = array(
            array(new Observer(false, $this), 2),
            array(new Observer(false, $this), 0),
            array(new Observer(false, $this), 1),
        );

        Property::set(
            $this->subject,
            'observers',
            array(
                0 => array($observers[1][0]),
                123 => array($observers[2][0], $observers[0][0]),
            )
        );

        $this->subject->notifyObservers();

        $this->assertFalse(
            Property::get($this->subject, 'updating'),
            'The subject should no longer be updating.'
        );

        foreach ($observers as $i => $observer) {
            $this->assertSame(
                $this->subject,
                $observer[0]->subject,
                "Make sure that observer #$i has the same subject."
            );

            $this->assertEquals(
                $observer[1],
                $observer[0]->order,
                "Make sure that observer #$i is called {$observer[1]}st/rd/th."
            );
        }

        // perform an interrupted update
        $observers = array(
            array(new Observer(), null),
            array(new Observer(), 3),
            array(new Observer(true), 4),
        );

        Property::set(
            $this->subject,
            'observers',
            array(
                0 => array($observers[1][0]),
                123 => array($observers[2][0], $observers[0][0]),
            )
        );
        try {
            $this->subject->notifyObservers();
        } catch (ReasonException $reason) {
        }

        foreach ($observers as $i => $observer) {
            $this->assertEquals(
                $observer[1],
                $observer[0]->order,
                "Make sure that observer #$i is called {$observer[1]}st/rd/th or not at all."
            );
        }

        $this->assertTrue(
            isset($reason),
            'Make sure that the update was interrupted.'
        );

        $this->assertNotNull(
            Property::get($this->subject, 'reason'),
            'The interrupt reason should be set.'
        );

        $this->assertFalse(
            Property::get($this->subject, 'updating'),
            'The subject should no longer be updating.'
        );

        // perform a double update
        Property::set(
            $this->subject,
            'observers',
            array(
                0 => array(new Observer(false, null, true)),
            )
        );
        try {
            $this->subject->notifyObservers();
        } catch (SubjectException $exception) {
        }

        $this->assertTrue(
            isset($exception),
            'Make sure that the double update was done.'
        );

        $this->assertFalse(
            Property::get($this->subject, 'updating'),
            'The subject should no longer be updating.'
        );
    }

    /**
     * Make sure that we can register an observer.
     */
    public function testRegisterObserver()
    {
        $observer = new Observer();

        $this->subject->registerObserver($observer);

        $this->assertEquals(
            array(
                0 => array($observer)
            ),
            Property::get($this->subject, 'observers'),
            'Make sure the observer is registered as zero priority.'
        );

        $this->subject->registerObserver($observer, 123);

        $this->assertEquals(
            array(
                0 => array($observer),
                123 => array($observer)
            ),
            Property::get($this->subject, 'observers'),
            'Make sure that the observer is registered with priority 123.'
        );
    }

    /**
     * Make sure that we can replace all registered observers using another subject.
     */
    public function testReplaceObservers()
    {
        $a = new Observer();
        $b = new Observer();
        $c = new Observer();

        Property::set(
            $this->subject,
            'observers',
            array(
                123 => array(new Observer()),
            )
        );

        $another = new Subject();

        Property::set(
            $another,
            'observers',
            array(
                0 => array($a),
                123 => array($b),
                456 => array($c),
            )
        );

        $this->subject->replaceObservers($another);

        $this->assertSame(
            Property::get($another, 'observers'),
            Property::get($this->subject, 'observers'),
            'The observers should have been copied correctly.'
        );
    }

    /**
     * Make sure that we can unregister all observers.
     */
    public function testUnregisterAllObservers()
    {
        $observers = array(
            new Observer(),
            new Observer(),
            new Observer(),
        );

        Property::set(
            $this->subject,
            'observers',
            array(
                0 => array($observers[0], $observers[1]),
                123 => array($observers[0], $observers[1], $observers[2], $observers[1]),
                456 => array($observers[2], $observers[2]),
            )
        );

        $this->subject->unregisterAllObservers($observers[1], 123);

        $this->assertSame(
            array(
                0 => array($observers[0], $observers[1]),
                123 => array($observers[0], 2 => $observers[2]),
                456 => array($observers[2], $observers[2]),
            ),
            Property::get($this->subject, 'observers'),
            'Make sure that all of a single observer is unregistered in a specific priority.'
        );

        $this->subject->unregisterAllObservers($observers[2]);

        $this->assertSame(
            array(
                0 => array($observers[0], $observers[1]),
                123 => array($observers[0]),
                456 => array(),
            ),
            Property::get($this->subject, 'observers'),
            'Make sure that all of a single observer is unregistered for any priority.'
        );

        $this->subject->unregisterAllObservers(null, 0);

        $this->assertSame(
            array(
                123 => array($observers[0]),
                456 => array(),
            ),
            Property::get($this->subject, 'observers'),
            'Make sure that all observers of a single priority are unregistered.'
        );

        $this->subject->unregisterAllObservers();

        $this->assertSame(
            array(),
            Property::get($this->subject, 'observers'),
            'Make sure that all observers are unregistered.'
        );
    }

    /**
     * Make sure that we can unregister a single occurrences of an observer.
     */
    public function testUnregisterObserver()
    {
        $observers = array(
            new Observer(),
            new Observer(),
            new Observer(),
        );

        Property::set(
            $this->subject,
            'observers',
            array(
                456 => array($observers[2], $observers[2]),
                0 => array($observers[0], $observers[1]),
                123 => array($observers[0], $observers[1], $observers[2], $observers[1]),
            )
        );

        $this->subject->unregisterObserver($observers[0]);

        $this->assertSame(
            array(
                0 => array(1 => $observers[1]),
                123 => array($observers[0], $observers[1], $observers[2], $observers[1]),
                456 => array($observers[2], $observers[2]),
            ),
            Property::get($this->subject, 'observers'),
            'Unregister the first occurrence sorted by priority.'
        );

        $this->subject->unregisterObserver($observers[2], 456);

        $this->assertSame(
            array(
                0 => array(1 => $observers[1]),
                123 => array($observers[0], $observers[1], $observers[2], $observers[1]),
                456 => array(1 => $observers[2]),
            ),
            Property::get($this->subject, 'observers'),
            'Unregister the first occurrence for a specific priority.'
        );
    }

    /**
     * Creates a new {@link Subject} for testing.
     */
    protected function setUp()
    {
        $this->subject = new Subject();
    }
}
