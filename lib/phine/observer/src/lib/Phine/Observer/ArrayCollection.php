<?php

namespace Phine\Observer;

/**
 * The default implementation of `ArrayCollectionInterface`.
 *
 * Summary
 * -------
 *
 * The `ArrayCollection` class is an implementation of `ArrayCollectionInterface`.
 * You may use the implementation as an authoritative example of how the interface
 * should be implemented. You may optionally extend the class to add (not modify)
 * new functionality that you may need.
 *
 * Starting
 * --------
 *
 * To start, you will need to simply create an instance of `ArrayCollection`:
 *
 *     $collection = new ArrayCollection();
 *
 * @author Kevin Herrera <kevin@herrera.io>
 */
class ArrayCollection extends Collection implements ArrayCollectionInterface
{
    /**
     * {@inheritDoc}
     */
    public function offsetExists($id)
    {
        return $this->isSubjectRegistered($id);
    }

    /**
     * {@inheritDoc}
     */
    public function offsetGet($id)
    {
        return $this->getSubject($id);
    }

    /**
     * {@inheritDoc}
     */
    public function offsetSet($id, $subject)
    {
        if ($this->isSubjectRegistered($id)) {
            $this->replaceSubject($id, $subject);
        } else {
            $this->registerSubject($id, $subject);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function offsetUnset($id)
    {
        if ($this->isSubjectRegistered($id)) {
            $this->unregisterSubject($id);
        }
    }
}
