<?php

namespace LMS\Tests\KSeF;

class FakeKSeFLms
{
    public function GetDivision()
    {
        return [
            'email' => '',
            'phone' => '',
            'rbe' => '',
            'regon' => '',
        ];
    }

    public function GetTaxes()
    {
        return [
            1 => [
                'value' => 23,
                'reversecharge' => 0,
                'taxed' => 1,
            ],
        ];
    }

    public function getCustomerBalance()
    {
        return 0;
    }
}
