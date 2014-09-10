<?php

namespace Phine\Observer\Exception;

use Phine\Exception\Exception;

/**
 * Exception thrown when a problem with the collection is encountered.
 *
 * @author Kevin Herrera <kevin@herrera.io>
 */
class CollectionException extends Exception
{
    /**
     * Creates a new exception for a unique identifier that is not used.
     *
     * @param string $id The unique identifier.
     *
     * @return CollectionException The new exception.
     */
    public static function idNotUsed($id)
    {
        return self::createUsingFormat(
            'The "%s" subject unique identifier is not in use.',
            $id
        );
    }

    /**
     * Creates a new exception for a unique identifier that is already used.
     *
     * @param string $id The unique identifier.
     *
     * @return CollectionException The new exception.
     */
    public static function idUsed($id)
    {
        return self::createUsingFormat(
            'The "%s" subject unique identifier is already in use.',
            $id
        );
    }
}
