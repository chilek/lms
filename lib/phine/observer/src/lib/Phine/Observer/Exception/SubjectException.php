<?php

namespace Phine\Observer\Exception;

use Phine\Exception\Exception;

/**
 * Exception thrown when a problem with the subject is encountered.
 *
 * @author Kevin Herrera <kevin@herrera.io>
 */
class SubjectException extends Exception
{
    /**
     * Creates a new exception for when a subject is already updating.
     *
     * @return SubjectException The new exception.
     */
    public static function alreadyUpdating()
    {
        return new self(
            'There is already an update in progress.'
        );
    }

    /**
     * Creates a new exception for when an interrupt is made without an update.
     *
     * @return SubjectException The new exception.
     */
    public static function notUpdating()
    {
        return new self(
            'There is no update in progress to interrupt.'
        );
    }
}
