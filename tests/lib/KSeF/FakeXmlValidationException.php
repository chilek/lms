<?php

namespace LMS\Tests\KSeF;

class FakeXmlValidationException extends \Exception
{
    public $context;

    public function __construct(string $message, array $context)
    {
        parent::__construct($message);
        $this->context = $context;
    }
}
