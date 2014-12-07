<?php

namespace Phine\Observer;

/**
 * Defines how an observer class must be implemented.
 *
 * Summary
 * -------
 *
 * A class implementing `ObserverInterface` will be able to receive updates
 * from any subject that implements `SubjectInterface`.
 *
 * Starting
 * --------
 *
 * To create a new observer, you will need to create an implementation of
 * `ObserverInterface`.
 *
 *     use Phine\Observer\ObserverInterface;
 *     use Phine\Observer\SubjectInterface;
 *
 *     class MyObserver implements ObserverInterface
 *     {
 *         public function receiveUpdate(SubjectInterface $subject)
 *         {
 *             // do stuff
 *         }
 *     }
 *
 * ### Interrupting the Update
 *
 * If the observer receives an update, it can interrupt the subject and prevent
 * it from updating the remaining observers. This can be accomplished by using
 * the `$subject->interruptUpdate()` method.
 *
 * @see SubjectInterface::interruptUpdate()
 *
 * @author Kevin Herrera <kevin@herrera.io>
 *
 * @api
 */
interface ObserverInterface
{
    /**
     * Receives an update from an observed subject.
     *
     * @param SubjectInterface $subject The subject being observed.
     *
     * @api
     */
    public function receiveUpdate(SubjectInterface $subject);
}
