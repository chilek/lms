<?php

namespace Phine\Observer;

use Phine\Observer\Exception\ReasonException;
use Phine\Observer\Exception\SubjectException;

/**
 * Defines how a subject class must be implemented.
 *
 * Summary
 * -------
 *
 * A class implementing `SubjectInterface` will be the subject in the observer
 * pattern. In addition to simply being capable of having observers registered,
 * a priority can be set for each observer.
 *
 * Starting
 * --------
 *
 * To create a new subject, you will need to create an implementation of
 * `SubjectInterface`. In this example, I will be using the bundled `Subject`
 * implementation.
 *
 *     use Phine\Observer\Subject;
 *
 *     $subject = new Subject();
 *
 * Observing
 * ---------
 *
 * To observer the subject for changes, you will first need an implementation
 * of `ObserverInterface`. The implementation will be responsible for receiving
 * updates from any subject it is registered to.
 *
 *     use Phine\Observer\ObserverInterface;
 *     use Phine\Observer\SubjectInterface;
 *
 *     class MyObserver implements ObserverInterface
 *     {
 *         private $name;
 *
 *         public function __construct($name)
 *         {
 *             $this->name = $name;
 *         }
 *
 *         public function receiveUpdate(SubjectInterface $subject)
 *         {
 *             echo 'Updated: ', $this->name, "\n";
 *         }
 *     }
 *
 * Once you have your implementation, you will need to register it.
 *
 *     $observer = new MyObserver('example');
 *
 *     $subject->registerObserver($observer);
 *
 * What is not shown in the example is that the observer has been registered
 * with the priority of `SubjectInterface::FIRST_PRIORITY` (`0`, zero). This
 * means that the observer will receive an update before any observer with a
 * priority number higher than `0` (zero). You may specify the registration
 * priority number as a second argument.
 *
 *     $subject->registerObserver($observer, 123);
 *
 * The highest priority number is `SubjectInterface::LAST_PRIORITY`, which is
 * an alias for `PHP_INT_MAX`. If multiple observers have been registered with
 * the same priority number, the observers will be updated in the order they
 * have been registered.
 *
 * > It might be useful to know that a single observer instance may be
 * > registered multiple times, in the same or different priority.
 *
 * Updating
 * --------
 *
 * When a change is made to the subject, the observers should be updated.
 *
 *     $subject->notifyObservers();
 *
 * The observers will be updated in priority and registration order. Assume
 * we registered the following observers using the example implementation that
 * was shown earlier:
 *
 *     $subject->registerObserver(new MyObserver('A'), 10);
 *     $subject->registerObserver(new MyObserver('B'));
 *     $subject->registerObserver(new MyObserver('C'), Subject::LAST_PRIORITY);
 *     $subject->registerObserver(new MyObserver('D'));
 *     $subject->registerObserver(new MyObserver('E'), 20);
 *
 * If we notify the observers of an update:
 *
 *     $subject->notifyObservers();
 *
 * The output from the observers will be:
 *
 *     Updated: B
 *     Updated: D
 *     Updated: A
 *     Updated: E
 *     Updated: C
 *
 * ### Interrupting an Update
 *
 * There may be a few cases where you would need to gracefully interrupt
 * the update process in order to prevent the remaining observers from being
 * notified. The following example will demonstrate how an observer is able
 * to interrupt a subject in the middle of an update.
 *
 *     use Phine\Observer\Exception\ReasonException;
 *
 *     class InterruptingObserver implements ObserverInterface
 *     {
 *         private $badConditionMet = false;
 *
 *         public function badConditionMet()
 *         {
 *             $this->badConditionMet = true;
 *         }
 *
 *         public function receiveUpdate(SubjectInterface $subject)
 *         {
 *             if ($this->badConditionMet) {
 *                 $subject->interruptUpdate(
 *                     new ReasonException('It is an example.')
 *                 );
 *             }
 *
 *             // otherwise do work
 *         }
 *     }
 *
 * Using the new `InterruptingObserver` class with the `MyObserver` class,
 * you can see how the subject will halt an update that is in progress.
 *
 *     $observer = new InterruptingObserver();
 *     $observer->badConditionMet();
 *
 *     $subject->registerObserver(new MyObserver('A'));
 *     $subject->registerObserver($observer);
 *     $subject->registerObserver(new MyObserver('B'));
 *
 *     $subject->notifyObservers();
 *
 * When the observers are notified, you will only see the following output:
 *
 *     Updated: A
 *     PHP Fatal error:  Uncaught exception 'Phine\Observer\Exception\ReasonException' with message 'It is an example.'
 *     ...
 *
 * While passing an instance of `ReasonException` to `interruptUpdate()` is
 * not required, I strongly advise that you do provide one. Doing so will make
 * it clear to a developer why an update was interrupted, ideally eliminating
 * the need to dig through the stack trace to find more information about why
 * an interrupt was done.
 *
 * @author Kevin Herrera <kevin@herrera.io>
 *
 * @api
 */
interface SubjectInterface
{
    /**
     * The first priority for an observer.
     *
     * @api
     */
    const FIRST_PRIORITY = 0;

    /**
     * The last priority for an observer.
     *
     * @api
     */
    const LAST_PRIORITY = PHP_INT_MAX;

    /**
     * Copies observers from another subject.
     *
     * This method will register the observers of another subject with this
     * subject, maintain their priority and the order they were registered in.
     * Note, however, that they will be called after any observers that have
     * already been registered with this subject.
     *
     *     // $subject has a copy of the observers from $anotherSubject
     *     $subject->copyObservers($anotherSubject);
     *
     * @param SubjectInterface $subject A subject to copy from.
     *
     * @api
     */
    public function copyObservers(SubjectInterface $subject);

    /**
     * Returns the reason the last update was interrupted.
     *
     * This method will return the reason the last update this subject made
     * was interrupted. Note that if there has yet to be an update, or if the
     * last update was not interrupted, nothing is returned.
     *
     *     $reason = $subject->getInterruptReason();
     *
     * @return ReasonException If the previous update was interrupted, the
     *                         exception representing the reason is returned.
     *                         If no interrupt had occurred, nothing (`null`)
     *                         is returned.
     *
     * @api
     */
    public function getInterruptReason();

    /**
     * Returns the observers registered to this subject.
     *
     * This method will return a list of observers in the order they would
     * be updated by the subject. The list is an array of arrays, where the
     * key of the outer array is the priority number, and the inner array
     * for each priority contains the list of observers in the order they
     * have been registered.
     *
     *     $observers = $subject->getObservers();
     *
     * The returned list would look something like the following:
     *
     *     $observers = array(
     *         0 => array(
     *             $observer1,
     *             $observer2,
     *         ),
     *         123 => array(
     *             $observer3
     *         ),
     *         456 => array(
     *             $observer4
     *         ),
     *     );
     *
     * @return array The list of observers and their priorities.
     *
     * @api
     */
    public function getObservers();

    /**
     * Checks if a specific observer is registered with this subject.
     *
     * This method will check if the given observer is registered with this
     * subject. If a `$priority` is specified, this method will only check
     * if the given observer was registered with the specified priority.
     *
     *     $subject->registerObserver($observer, 123);
     *
     *     if ($subject->hasObserver($observer)) {
     *         // registered
     *     }
     *
     *     if ($subject->hasObserver($observer, 123)) {
     *         // registered with priority 123
     *     }
     *
     *     if (!$subject->hasObserver($observer, 456)) {
     *         // not registered with priority 456
     *     }
     *
     * @param ObserverInterface $observer The observer to check for.
     * @param integer           $priority The priority to limit the check to.
     *
     * @return boolean If the observer is registered, `true` is returned. If
     *                 the observer is not registered, `false` is returned.
     *
     * @api
     */
    public function hasObserver(ObserverInterface $observer, $priority = null);

    /**
     * Checks if this subject has any observers registered.
     *
     * This method will check to see if this subject has any observers
     * registered, regardless of priority. If a `$priority` is specified, this
     * method will only check to see if any observer has been registered with
     * the specified priority.
     *
     *     $subject->registerObserver($observer, 123);
     *
     *     if ($subject->hasObservers()) {
     *         // registered
     *     }
     *
     *     if (!$subject->hasObserver(456)) {
     *        // none registered with priority 456
     *     }
     *
     * @param integer $priority The priority to limit the check to.
     *
     * @return boolean If there are one or more observer registered, `true`
     *                 is returned. If there are no observers registered,
     *                 `false` is returned.
     *
     * @api
     */
    public function hasObservers($priority = null);

    /**
     * Interrupts the observer update process.
     *
     * This method will interrupt an update that is currently in progress.
     * If a `$reason` is not provided, a default one will be created. If an
     * update is currently not in progress, an exception will be thrown.
     *
     *     class MyObserver implements ObserverInterface
     *     {
     *         public function receiveUpdate(SubjectInterface $subject)
     *         {
     *             $subject->interruptUpdate(new ReasonException('My reason.'));
     *         }
     *     }
     *
     * @param ReasonException $reason A reason for the interruption.
     *
     * @throws SubjectException If an update is not in progress.
     *
     * @api
     */
    public function interruptUpdate(ReasonException $reason = null);

    /**
     * Checks if the last update was interrupted.
     *
     * This method will check to see if the last update was interrupted.
     *
     *     if ($subject->isInterrupted()) {
     *         // it was interrupted
     *     }
     *
     * @return boolean If the last update was interrupted, `true` is returned.
     *                 If the last update was not interrupted, `false` is
     *                 returned.
     *
     * @api
     */
    public function isInterrupted();

    /**
     * Checks if the subject is in the process of updating its observers.
     *
     * This method will check if the subject is in the process of updating
     * its registered observers.
     *
     *     if ($subject->isUpdating()) {
     *         // currently updating
     *     }
     *
     * @return boolean If the subject is currently updating its observers,
     *                 `true` is returned. If the subject is not currently
     *                 updating its observers, `false` is returned.
     *
     * @api
     */
    public function isUpdating();

    /**
     * Notifies all observers of an update from this subject.
     *
     * This method will update all observers that have been registered with
     * this subject. The observers will first be updated in the order they
     * have been prioritized, from `SubjectInterface::FIRST_PRIORITY` (zero)
     * to `SubjectInterface::LAST_PRIORITY` (`PHP_INT_MAX`). If the update
     * is interrupted by an observer, an exception will be thrown.
     *
     * If an update is already in progress, an exception will be thrown.
     *
     *     try {
     *         $subject->notifyObservers();
     *     } catch (ReasonException $exception) {
     *         // the update was interrupted
     *     } catch (SubjectException $exception) {
     *         // already updating observers
     *     }
     *
     *     if (!$subject->isInterrupted()) {
     *         // only do something if not interrupted
     *     }
     *
     * @return mixed While not required, a subject may return a value once
     *               all of the observers have been notified. If no value is
     *               available, `null` is always returned.
     *
     * @throws ReasonException  If the update is interrupted.
     * @throws SubjectException If the subject is already updating.
     *
     * @api
     */
    public function notifyObservers();

    /**
     * Registers an observer with this subject.
     *
     * This method will register a single occurrence of the given `$observer`
     * with this subject. Multiple registrations of the same observer may be
     * done. By default, the `SubjectInterface::FIRST_PRIORITY` priority is
     * used for the registration.
     *
     *     $subject->registerObserver($observer, 123);
     *     $subject->registerObserver($observer, 456);
     *     $subject->registerObserver($observer, 789);
     *
     * The priority may be anywhere from `0` (zero) to `PHP_INT_MAX`. Observers
     * are updated beginning with `0` and ending with `PHP_INT_MAX`. Multiple
     * observers, even the same observer, may be registered using the same
     * priority.
     *
     * For convenience, the following constants are made available:
     *
     * - `SubjectInterface::FIRST_PRIORITY` &mdash; Priority `0` (zero).
     * - `SubjectInterface::LAST_PRIORITY` &mdash; Priority `PHP_INT_MAX`.
     *
     * @param ObserverInterface $observer The observer to register.
     * @param integer           $priority The priority of the observer.
     *
     * @api
     */
    public function registerObserver(
        ObserverInterface $observer,
        $priority = self::FIRST_PRIORITY
    );

    /**
     * Replaces the observers for this subject using observers from another.
     *
     * This method will replace the observers of this subject with the
     * observers registered with the given subject, preserving priority and
     * registration order. Any previous observers registered with this subject
     * will be forgotten.
     *
     *     // $subject will have the observers of $anotherSubject
     *     $subject->replaceObservers($anotherSubject);
     *
     * @param SubjectInterface $subject A subject to replace with.
     *
     * @api
     */
    public function replaceObservers(SubjectInterface $subject);

    /**
     * Unregisters some or all observers from this subject.
     *
     * This method will unregister some or all observers and each occurrence
     * of their registration, depending on the parameters that have been
     * provided.
     *
     * - By default, unregisters all occurrences of all observers of all
     *   priorities.
     * - If an `$observer` is provided, unregisters all occurrences of only
     *   the given observer of all priorities.
     * - If a `$priority` is specified, unregisters all occurrences of all
     *   observers only with the specified priority.
     * - If both `$observer` and `$priority` is specified, unregisters all
     *   occurrences of the given observer with the specified priority.
     *
     *     // all gone
     *     $subject->unregisterAllObservers();
     *
     *     // all $observer gone
     *     $subject->unregisterAllObservers($observer);
     *
     *     // all with 123 priority gone
     *     $subject->unregisterAllObservers(null, 123);
     *
     *     // all $observer with priority 123 gone
     *     $subject->unregisterAllObservers($observer, 123);
     *
     * @param ObserverInterface $observer The observer to unregister.
     * @param integer           $priority The priority to limit to.
     *
     * @api
     */
    public function unregisterAllObservers(
        ObserverInterface $observer = null,
        $priority = null
    );

    /**
     * Unregisters an observer from this subject.
     *
     * This method will unregister all occurrences of this observer from this
     * subject. If a `$priority` is specified all occurrences will be removed
     * only with the specified priority.
     *
     *     // all gone
     *     $subject->unregisterObserver($observer);
     *
     *     // all with 123 priority gone
     *     $subject->unregisterObserver($observer, 123);
     *
     * @param ObserverInterface $observer The observer to unregister.
     * @param integer           $priority The priority to limit to.
     *
     * @api
     */
    public function unregisterObserver(
        ObserverInterface $observer,
        $priority = null
    );
}
