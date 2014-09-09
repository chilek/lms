<?php

namespace Phine\Observer\Exception;

use Phine\Exception\Exception;

/**
 * A throwable reason for interrupting an observer update.
 *
 * @author Kevin Herrera <kevin@herrera.io>
 */
class ReasonException extends Exception
{
    /**
     * Creates an exception for when a reason is not provided.
     *
     * @return ReasonException The new exception.
     */
    public static function notSpecified()
    {
        return new self('(no reason specified)');
    }
}
