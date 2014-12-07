<?php

namespace Phine\Observer;

use Phine\Observer\Exception\CollectionException;

/**
 * The default implementation of `CollectionInterface`.
 *
 * Summary
 * -------
 *
 * The `Collection` class is an implementation of `CollectionInterface`. You
 * may use the implementation as an authoritative example of how the interface
 * should be implemented. You may optionally extend the class to add (not
 * modify) new functionality that you may need.
 *
 * Starting
 * --------
 *
 * To start, you will need to simply create an instance of `Collection`:
 *
 *     $collection = new Collection();
 *
 * @author Kevin Herrera <kevin@herrera.io>
 *
 * @api
 */
class Collection implements CollectionInterface
{
    /**
     * The registered subjects.
     *
     * @var SubjectInterface[]
     */
    private $subjects = array();

    /**
     * {@inheritDoc}
     */
    public function copySubjects(CollectionInterface $collection)
    {
        $this->subjects = array_merge(
            $this->subjects,
            $collection->getSubjects()
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getSubject($id)
    {
        if (!isset($this->subjects[$id])) {
            throw CollectionException::idNotUsed($id);
        }

        return $this->subjects[$id];
    }

    /**
     * {@inheritDoc}
     */
    public function getSubjects()
    {
        return $this->subjects;
    }

    /**
     * {@inheritDoc}
     */
    public function isSubjectRegistered($id)
    {
        return isset($this->subjects[$id]);
    }

    /**
     * {@inheritDoc}
     */
    public function registerSubject($id, SubjectInterface $subject)
    {
        if (isset($this->subjects[$id])) {
            throw CollectionException::idUsed($id);
        }

        $this->subjects[$id] = $subject;
    }

    /**
     * {@inheritDoc}
     */
    public function replaceSubject($id, SubjectInterface $subject)
    {
        if (!isset($this->subjects[$id])) {
            throw CollectionException::idNotUsed($id);
        }

        $this->subjects[$id] = $subject;
    }

    /**
     * {@inheritDoc}
     */
    public function replaceSubjects(CollectionInterface $collection)
    {
        $this->subjects = $collection->getSubjects();
    }

    /**
     * {@inheritDoc}
     */
    public function unregisterSubject($id)
    {
        if (!isset($this->subjects[$id])) {
            throw CollectionException::idNotUsed($id);
        }

        unset($this->subjects[$id]);
    }
}
