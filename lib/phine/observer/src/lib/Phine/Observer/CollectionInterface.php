<?php

namespace Phine\Observer;

use Phine\Observer\Exception\CollectionException;

/**
 * Defines how a subject collection class must be implemented.
 *
 * Summary
 * -------
 *
 * A class implementing `CollectionInterface` will associate each subject that
 * is registered with it a unique identifier. This unique identifier can then
 * be used as a way to manage subjects without directly knowing which instance
 * should be used.
 *
 * Starting
 * --------
 *
 * To create a new collection, you will need to create an implementation of
 * `CollectionInterface`. In this example, I will be using the `Collection`,
 * a bundled implementation of the interface.
 *
 *     use Phine\Observers\Collection;
 *
 *     $collection = new Collection();
 *
 * ### Managing Subjects
 *
 * Once you have created your collection, you will want to register one or
 * more subjects. Subjects are registered by association a specific unique
 * identifier for each subject in the collection. You may register the same
 * subject with different identifiers, but you may not register multiple
 * subjects to the same identifier.
 *
 *     use Phine\Observers\Subject;
 *
 *     // create our subjects
 *     $subject1 = new Subject();
 *     $subject2 = new Subject();
 *     $subject3 = new Subject();
 *
 *     // register them with unique identifiers
 *     $collection->registerSubject('one', $subject1);
 *     $collection->registerSubject('two', $subject2);
 *     $collection->registerSubject('three', $subject3);
 *     $collection->registerSubject('four', $subject3);
 *
 *     // this attempt will thrown an exception
 *     $collection->registerSubject('three', $subject1);
 *
 * ### Replacing Subjects
 *
 * At some point, you may have a process that will swap one subject for
 * another. The collection will not allow multiple registrations for a
 * single unique identifier, but it will allow you to replace existing
 * subjects with other ones.
 *
 *     $subject4 = new Subject();
 *
 *     $collection->replaceSubject('four', $subject4);
 *
 * If you attempt to replace a subject for a unique identifier that has not
 * been used, an exception will be thrown. To prevent this from happening,
 * you will want to check the registration of a unique identifier first.
 *
 *     $subject5 = new Subject();
 *
 *     if ($collection->isSubjectRegistered('five')) {
 *         $collection->replaceSubject('five', $subject5);
 *     } else {
 *          $collection->registerSubject('five', $subject5);
 *     }
 *
 * ### Accessing Subjects
 *
 * After your subjects have been registered or replaced, you will want to
 * eventually use them.
 *
 *     $subject = $collection->getSubject('one');
 *     $subject->notifyObservers();
 *
 *     // alternatively
 *
 *     $collection->getSubject('one')->notifyObservers();
 *
 * ### Unregistering Subjects
 *
 * Unregistering a subject is as simple as registering one. However, if you
 * attempt to unregister a subject with a unique identifier that has not been
 * used, an exception is thrown. You will want to check for registration before
 * actually unregistering the subject.
 *
 *     if ($collection->isSubjectRegistered('three')) {
 *         $collection->unregisterSubject('three');
 *     }
 *
 * @author Kevin Herrera <kevin@herrera.io>
 *
 * @api
 */
interface CollectionInterface
{
    /**
     * Copies subjects from another collection.
     *
     * This method will register the subjects of another collection with this
     * collection. If the given collection has one or more subjects with the
     * same unique identifier as those already registered with this collection,
     * the subjects will be replaced with the ones being copied.
     *
     *     $collection->copySubjects($anotherCollection);
     *
     * @param CollectionInterface $collection A collection to copy from.
     *
     * @api
     */
    public function copySubjects(CollectionInterface $collection);

    /**
     * Returns the subject with the unique identifier.
     *
     * This method will return the subject that was registered with the
     * specified unique identifier. If a subject was not registered with
     * the unique identifier, an exception is thrown.
     *
     *     $subject = $collection->getSubject('my_id');
     *
     * @param string $id The unique identifier.
     *
     * @return SubjectInterface The registered subject.
     *
     * @throws CollectionException If the unique identifier is not used.
     *
     * @api
     */
    public function getSubject($id);

    /**
     * Returns a list of subjects registered to this collection.
     *
     * This method will return a list of subjects that have been registered
     * with this collection. The list is return is an array with the key being
     * the unique identifier of the subject (which is the value).
     *
     *     $subjects = $collection->getSubjects();
     *
     * @return SubjectInterface[] The list of subjects.
     *
     * @api
     */
    public function getSubjects();

    /**
     * Checks if a subject is registered with this collection.
     *
     * This method will check if a subject was registered using the specified
     * unique identifier. If an identifier is registered currently registered,
     * `true` is returned.
     *
     *     if ($collection->isSubjectRegistered('my_id')) {
     *         // registered
     *     }
     *
     * @param string $id The unique identifier of the subject.
     *
     * @return boolean Returns `true` if a subject with the given unique
     *                 identifier is registered with this collection. If
     *                 a subject is not found, `false` is returned.
     *
     * @api
     */
    public function isSubjectRegistered($id);

    /**
     * Registers a subject with a unique identifier in this collection.
     *
     * This method will register the given identifier with the specified
     * unique identifier in this collection. If the unique identifier has
     * already been used, an exception is thrown.
     *
     *     $collection->registerSubject('my_id', $subject);
     *
     * @param string           $id      The unique identifier.
     * @param SubjectInterface $subject The subject to register.
     *
     * @throws CollectionException If the unique identifier is already used.
     *
     * @api
     */
    public function registerSubject($id, SubjectInterface $subject);

    /**
     * Replaces a subject registered with a unique identifier.
     *
     * This method will replace a subject that has been registered with the
     * specified unique identifier with the given subject. If a subject has
     * not already been registered with the unique identifier, an exception
     * is thrown.
     *
     *     $subject->replaceSubject('my_id', $anotherSubject);
     *
     * @param string           $id      The unique identifier.
     * @param SubjectInterface $subject The new subject.
     *
     * @throws CollectionException If the unique identifier is not used.
     *
     * @api
     */
    public function replaceSubject($id, SubjectInterface $subject);

    /**
     * Replaces the subjects for this collection using subjects from another.
     *
     * This method will replace the subjects of this collection with the
     * subjects registered with the given collection. Any previous subjects
     * registered with this collection will be forgotten.
     *
     *     $subject->replaceSubjects($anotherCollection);
     *
     * @param CollectionInterface $collection A collection to replace with.
     *
     * @api
     */
    public function replaceSubjects(CollectionInterface $collection);

    /**
     * Unregisters a subject from the collection.
     *
     * This method will unregister a subject that was registered with the
     * specified unique identifier. If a subject has not been registered
     * with the unique identifier, an exception is thrown.
     *
     *     $subject->unregisterSubject('my_id');
     *
     * @param string $id The unique identifier.
     *
     * @throws CollectionException If the unique identifier is not used.
     *
     * @api
     */
    public function unregisterSubject($id);
}
