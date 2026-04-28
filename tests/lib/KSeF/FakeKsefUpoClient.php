<?php

namespace LMS\Tests\KSeF;

use N1ebieski\KSEFClient\Requests\Sessions\Invoices\KsefUpo\KsefUpoRequest;

class FakeKsefUpoClient
{
    public $request;

    private $body;
    private $fail;

    public function __construct(?string $body, bool $fail = false)
    {
        $this->body = $body;
        $this->fail = $fail;
    }

    public function sessions()
    {
        return $this;
    }

    public function invoices()
    {
        return $this;
    }

    public function ksefUpo(KsefUpoRequest $request)
    {
        if ($this->fail) {
            throw new \RuntimeException('UPO API failed');
        }

        $this->request = $request;

        return $this;
    }

    public function body()
    {
        return $this->body;
    }
}
