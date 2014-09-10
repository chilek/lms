<?php

namespace Phine\Observer;

use Exception;
use Phine\Observer\Exception\ReasonException;
use Phine\Observer\Exception\SubjectException;

/**
 * The default implementation of `SubjectInterface`.
 *
 * Summary
 * -------
 *
 * The `Subject` class is an implementation of `SubjectInterface`. You may use
 * the implementation as an authoritative example of how the interface should
 * be implemented. You may optionally extend the class to add (not modify) new
 * functionality that you may need.
 *
 * Starting
 * --------
 *
 * To start, you will need to simply create an instance of `Subject`:
 *
 *     use Phine\Observer\Subject;
 *
 *     $subject = new Subject();
 *
 * @author Kevin Herrera <kevin@herrera.io>
 *
 * @api
 */
class Subject implements SubjectInterface
{
    /**
     * The registered observers.
     *
     * @var array
     */
    private $observers = array();

    /**
     * The reason for the last interrupt.
     *
     * @var ReasonException
     */
    private $reason;

    /**
     * The flag used to determine if an update is in progress.
     *
     * @var boolean
     */
    private $updating = false;

    /**
     * {@inheritDoc}
     */
    public function copyObservers(SubjectInterface $subject)
    {
        foreach ($subject->getObservers() as $priority => $observers) {
            if (isset($this->observers[$priority])) {
                $this->observers[$priority] = array_merge(
                    $this->observers[$priority],
                    $observers
                );
            } else {
                $this->observers[$priority] = $observers;
            }
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getInterruptReason()
    {
        return $this->reason;
    }

    /**
     * {@inheritDoc}
     */
    public function getObservers()
    {
        ksort($this->observers, SORT_NUMERIC);

        return $this->observers;
    }

    /**
     * {@inheritDoc}
     */
    public function hasObserver(ObserverInterface $observer, $priority = null)
    {
        if (null === $priority) {
            foreach ($this->observers as $observers) {
                if (in_array($observer, $observers, true)) {
                    return true;
                }
            }
        } elseif (isset($this->observers[$priority])) {
            return in_array($observer, $this->observers[$priority], true);
        }

        return false;
    }

    /**
     * {@inheritDocs}
     */
    public function hasObservers($priority = null)
    {
        if (null === $priority) {
            foreach ($this->observers as $observers) {
                if (!empty($observers)) {
                    return true;
                }
            }
        } elseif (isset($this->observers[$priority])) {
            return !empty($this->observers[$priority]);
        }

        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function interruptUpdate(ReasonException $reason = null)
    {
        if (false === $this->updating) {
            throw SubjectException::notUpdating();
        }

        if (null === $reason) {
            $reason = ReasonException::notSpecified();
        }

        $this->reason = $reason;
        $this->updating = false;
    }

    /**
     * {@inheritDoc}
     */
    public function isInterrupted()
    {
        return (null !== $this->reason);
    }

    /**
     * {@inheritDoc}
     */
    public function isUpdating()
    {
        return $this->updating;
    }

    /**
     * {@inheritDoc}
     */
    public function notifyObservers()
    {
        if ($this->updating) {
            throw SubjectException::alreadyUpdating();
        }

        $this->resetInterrupt();

        $this->updating = true;

        /** @var ObserverInterface $observer */
        foreach ($this->getObservers() as $observers) {
            foreach ($observers as $observer) {
                try {
                $observer->receiveUpdate($this);
                } catch (Exception $exception) {
                    $this->updating = false;

                    throw $exception;
                }

                if ($this->reason) {
                    $this->updating = false;

                    throw $this->reason;
                }
            }
        }

        $this->updating = false;
    }

    /**
     * {@inheritDoc}
     */
    public function registerObserver(
        ObserverInterface $observer,
        $priority = self::FIRST_PRIORITY
    ) {
        if (!isset($this->observers[$priority])) {
            $this->observers[$priority] = array();
        }

        $this->observers[$priority][] = $observer;
    }

    /**
     * {@inheritDoc}
     */
    public function replaceObservers(SubjectInterface $subject)
    {
        $this->observers = $subject->getObservers();
    }

    /**
     * {@inheritDoc}
     */
    public function unregisterAllObservers(
        ObserverInterface $observer = null,
        $priority = null
    ) {
        if ($observer) {
            if (null === $priority) {
                foreach ($this->observers as $priority => $observers) {
                    foreach (array_keys($observers, $observer, true) as $key) {
                        unset($this->observers[$priority][$key]);
                    }
                }
            } elseif (isset($this->observers[$priority])) {
                foreach (array_keys($this->observers[$priority], $observer, true) as $key) {
                    unset($this->observers[$priority][$key]);
                }
            }
        } elseif (null === $priority) {
            $this->observers = array();
        } else {
            unset($this->observers[$priority]);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function unregisterObserver(
        ObserverInterface $observer,
        $priority = null
    ) {
        if (null === $priority) {
            foreach ($this->getObservers() as $priority => $observers) {
                if (false !== ($key = array_search($observer, $observers, true))) {
                    unset($this->observers[$priority][$key]);

                    break;
                }
            }
        } elseif (isset($this->observers[$priority])) {
            if (false !== ($key = array_search($observer, $this->observers[$priority], true))) {
                unset($this->observers[$priority][$key]);
            }
        }
    }

    /**
     * Resets any interruption made by the last update.
     */
    protected function resetInterrupt()
    {
        $this->reason = null;
    }
}
