<?php

namespace Phine\Observer;

use ArrayAccess;
use Phine\Observer\Exception\CollectionException;

/**
 * Defines how an array accessible subject collection class must be implemented.
 *
 * Summary
 * -------
 *
 * The `ArrayCollectionInterface` is an extension of `CollectionInterface`
 * that allows for array access. Using array access provides the ability
 * to reduce the amount of boilerplate code that is used to manage subjects
 * and their observers, adding convenience.
 *
 * Starting
 * --------
 *
 * To create a new array accessible collection, you will need to create an
 * implementation of `ArrayCollectionInterface`. In this example, I will be
 * using the bundled `ArrayCollection` class.
 *
 *     use Phine\Observer\ArrayCollection;
 *
 *     $collection = new ArrayCollection();
 *
 * ### Managing Subjects
 *
 * Managing subject registrations with an array accessible collection is as
 * simple as... accessing an array! You can perform all collection actions
 * via array access. It is important to note that exceptions will still be
 * thrown for some conditions, such as accessing unregistered unique
 * identifiers.
 *
 *     // register new subjects
 *     $collection['one'] = new Subject();
 *     $collection['two'] = new Subject();
 *     $collection['three'] = new Subject();
 *
 *     // replace an existing one
 *     $collection['two'] = new Subject();
 *
 *     // unregister an existing one
 *     unset($collection['one']);
 *
 *     // use an existing one
 *     $collection['three']->notifyObservers();
 *
 * @author Kevin Herrera <kevin@herrera.io>
 *
 * @api
 */
interface ArrayCollectionInterface extends ArrayAccess, CollectionInterface
{
    /**
     * Checks if a subject is registered with this collection.
     *
     * This method will check if a subject was registered using the specified
     * unique identifier. If an identifier is registered currently registered,
     * `true` is returned.
     *
     *     if (isset($collection['my_id'])) {
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
    public function offsetExists($id);

    /**
     * Returns the subject with the unique identifier.
     *
     * This method will return the subject that was registered with the
     * specified unique identifier. If a subject was not registered with
     * the unique identifier, an exception is thrown.
     *
     *     $subject = $collection['my_id'];
     *
     * @param string $id The unique identifier.
     *
     * @return SubjectInterface The registered subject.
     *
     * @throws CollectionException If the unique identifier is not used.
     *
     * @api
     */
    public function offsetGet($id);

    /**
     * Registers or replaces a subject with the unique identifier.
     *
     * This method will register the given identifier with the specified
     * unique identifier in this collection. If the unique identifier has
     * already been used, the registered subject will be replaced with the
     * new one provided.
     *
     *     // new
     *     $collection['my_id'] = new Subject();
     *
     *     // replace
     *     $collection['my_id'] = new Subject();
     *
     * @param string           $id      The unique identifier.
     * @param SubjectInterface $subject The subject to register.
     *
     * @api
     */
    public function offsetSet($id, $subject);

    /**
     * Unregisters a subject from the collection.
     *
     * This method will unregister a subject that was registered with the
     * specified unique identifier.
     *
     *     unset($collection['my_id']);
     *
     * @param string $id The unique identifier.
     *
     * @api
     */
    public function offsetUnset($id);
}
