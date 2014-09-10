<?php

namespace Phine\Observer\Test;

use Phine\Observer\Exception\ReasonException;
use Phine\Observer\ObserverInterface;
use Phine\Observer\SubjectInterface;
use Phine\Test\Property;
use PHPUnit_Framework_TestCase as TestCase;

/**
 * A simple test observer.
 *
 * @author Kevin Herrera <kevin@herrera.io>
 */
class Observer implements ObserverInterface
{
    /**
     * The order counter (must reset before update).
     *
     * @var integer
     */
    public static $counter = 0;

    /**
     * Perform a double update?
     *
     * @var boolean
     */
    private $double = false;

    /**
     * The order in which it was called.
     *
     * @var integer
     */
    public $order;

    /**
     * The reason for the interrupt.
     *
     * @var ReasonException
     */
    public $reason;

    /**
     * The subject that updated this observer.
     *
     * @var SubjectInterface
     */
    public $subject;

    /**
     * The flag used to determine if the update should be interrupted.
     *
     * @var boolean
     */
    private $interrupt;

    /**
     * The test case.
     *
     * @var TestCase
     */
    private $test;

    /**
     * Sets the interrupt flag.
     *
     * @param boolean  $interrupt Interrupt the update?
     * @param TestCase $test      The test case.
     * @param boolean  $double    Perform a double update?
     */
    public function __construct(
        $interrupt = false,
        TestCase $test = null,
        $double = false
    ) {
        $this->double = $double;
        $this->interrupt = $interrupt;
        $this->test = $test;
    }

    /**
     * {@inheritDoc}
     */
    public function receiveUpdate(SubjectInterface $subject)
    {
        $this->order = self::$counter++;

        $this->subject = $subject;

        if ($this->double) {
            $subject->notifyObservers();
        }

        if ($this->test) {
            $this->test->assertTrue(
                Property::get($subject, 'updating'),
                'The subject should be updating.'
            );
        }

        if ($this->interrupt) {
            $this->reason = new ReasonException('Testing interruption.');

            $subject->interruptUpdate($this->reason);
        }
    }
}
