<?php

namespace {
    if (!defined('STORAGE_DIR')) {
        define('STORAGE_DIR', sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'lms-ksef-test-storage');
    }
    if (!defined('CONFIG_FILE')) {
        define('CONFIG_FILE', '/etc/lms/lms.ini');
    }
    if (!defined('DOC_INVOICE')) {
        define('DOC_INVOICE', 1);
    }
    if (!defined('DOC_CNOTE')) {
        define('DOC_CNOTE', 3);
    }
    if (!defined('DOC_FLAG_SPLIT_PAYMENT')) {
        define('DOC_FLAG_SPLIT_PAYMENT', 1);
    }
    if (!defined('DOC_FLAG_RECEIPT')) {
        define('DOC_FLAG_RECEIPT', 2);
    }
    if (!defined('DOC_FLAG_RELATED_ENTITY')) {
        define('DOC_FLAG_RELATED_ENTITY', 4);
    }
    if (!defined('PAYTYPE_CASH')) {
        define('PAYTYPE_CASH', 1);
    }
    if (!defined('PAYTYPE_CARD')) {
        define('PAYTYPE_CARD', 2);
    }
    if (!defined('PAYTYPE_BANK_LOAN')) {
        define('PAYTYPE_BANK_LOAN', 3);
    }
    if (!defined('PAYTYPE_TRANSFER')) {
        define('PAYTYPE_TRANSFER', 4);
    }
    if (!defined('PAYTYPE_BARTER')) {
        define('PAYTYPE_BARTER', 5);
    }
    if (!defined('PAYTYPE_CASH_ON_DELIVERY')) {
        define('PAYTYPE_CASH_ON_DELIVERY', 6);
    }
    if (!defined('PAYTYPE_COMPENSATION')) {
        define('PAYTYPE_COMPENSATION', 7);
    }
    if (!defined('PAYTYPE_CONTRACT')) {
        define('PAYTYPE_CONTRACT', 8);
    }
    if (!defined('PAYTYPE_INSTALMENTS')) {
        define('PAYTYPE_INSTALMENTS', 9);
    }
    if (!defined('PAYTYPE_PAID')) {
        define('PAYTYPE_PAID', 10);
    }
    if (!defined('PAYTYPE_TRANSFER_CASH')) {
        define('PAYTYPE_TRANSFER_CASH', 11);
    }

    if (!class_exists('PHPUnit\Framework\TestCase') && class_exists('PHPUnit_Framework_TestCase')) {
        class_alias('PHPUnit_Framework_TestCase', 'PHPUnit\Framework\TestCase');
    }
    if (!class_exists('Localisation')) {
        class Localisation
        {
            public static function getCurrentCurrency()
            {
                return 'PLN';
            }
        }
    }
    if (!class_exists('ConfigHelper')) {
        class ConfigHelper
        {
            public static function checkConfig()
            {
                return false;
            }
        }
    }
    if (!class_exists('Utils')) {
        class Utils
        {
            public static function removeHtml($value)
            {
                return strip_tags($value);
            }

            public static function wordWrapToArray($value)
            {
                return [$value];
            }
        }
    }
    if (!class_exists('LMS')) {
        class LMS
        {
            const SOFTWARE_NAME = 'LMS';
            const SOFTWARE_VERSION = 'test';
        }
    }
    if (!function_exists('bankaccount')) {
        function bankaccount($customerId, $account)
        {
            return $account;
        }
    }
}

namespace LMS\Tests\KSeF {
    use Lms\KSeF\KSeF;
    use PHPUnit\Framework\TestCase;

    class KSeFTest extends TestCase
    {
        public function testGetInvoiceXmlAcceptsLmsDoctypeKeyWithoutWarning()
        {
            $ksef = $this->ksefXmlGenerator();

            set_error_handler(function ($severity, $message) {
                throw new \ErrorException($message, 0, $severity);
            });
            try {
                $xml = $ksef->getInvoiceXml($this->invoiceFixture());
            } finally {
                restore_error_handler();
            }

            $this->assertStringContainsString('<RodzajFaktury>VAT</RodzajFaktury>', $xml);
            $this->assertStringContainsString('<Nazwa>KSeF Test Company</Nazwa>', $xml);
        }

        public function testSaveUpoContentCreatesMissingStorageDirectory()
        {
            $storageDir = STORAGE_DIR . DIRECTORY_SEPARATOR . 'ksef';
            $this->removeDirectory($storageDir);
            $this->resetKSeFUpoStorageCache();

            try {
                $ksefNumber = '1234567890-20260425-ABCDEF';
                $this->assertFalse(KSeF::upoFileExists($ksefNumber));

                $result = KSeF::saveUpoContent($ksefNumber, '<UPO>test</UPO>');

                $this->assertTrue($result);
                $this->assertFileExists(
                    STORAGE_DIR . DIRECTORY_SEPARATOR . 'ksef'
                        . DIRECTORY_SEPARATOR . 'upo'
                        . DIRECTORY_SEPARATOR . '1234567890'
                        . DIRECTORY_SEPARATOR . '20260425'
                        . DIRECTORY_SEPARATOR . $ksefNumber . '.xml'
                );
            } finally {
                $this->removeDirectory($storageDir);
                $this->resetKSeFUpoStorageCache();
            }
        }

        public function testFormatStatusDetailsDecodesJsonUnicodeEscapes()
        {
            $this->assertSame(
                "Nip nabywcy: '6021767728' jest nieprawidłowy.",
                KSeF::formatStatusDetails('["Nip nabywcy: \'6021767728\' jest nieprawid\\u0142owy."]')
            );
        }

        public function testFormatStatusDetailsFormatsNestedJsonWithoutArrayWarning()
        {
            $this->assertSame(
                '{"field":"NIP","message":"Nieprawidłowy NIP"}',
                KSeF::formatStatusDetails('[{"field":"NIP","message":"Nieprawid\\u0142owy NIP"}]')
            );
        }

        private function resetKSeFUpoStorageCache()
        {
            $property = new \ReflectionProperty(KSeF::class, 'upoStorage');
            $property->setAccessible(true);
            $property->setValue(null, null);
        }

        private function removeDirectory($directory)
        {
            if (!is_dir($directory)) {
                return;
            }

            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST
            );
            foreach ($iterator as $item) {
                if ($item->isDir()) {
                    rmdir($item->getPathname());
                } else {
                    unlink($item->getPathname());
                }
            }
            rmdir($directory);
        }

        private function invoiceFixture()
        {
            return [
                'id' => 1,
                'doctype' => DOC_INVOICE,
                'divisionid' => 1,
                'division_ten' => '1234567890',
                'division_name' => 'Seller',
                'division_address' => 'Seller Street 1',
                'division_zip' => '00-001',
                'division_city' => 'Warszawa',
                'division_countryid' => 1,
                'division_footer' => '',
                'div_bank' => '',
                'customerid' => 10,
                'name' => 'KSeF Test Company',
                'address' => 'ul. KSeF Testowa 1',
                'zip' => '00-010',
                'city' => 'Warszawa',
                'countryid' => 1,
                'ten' => '1111111111',
                'cdate' => strtotime('2026-03-25'),
                'sdate' => strtotime('2026-03-25'),
                'pdate' => strtotime('2026-04-08'),
                'fullnumber' => '001/03/2026/fa',
                'currency' => 'PLN',
                'currencyvalue' => 1,
                'taxest' => [
                    '23.00' => [
                        'base' => 100.0,
                        'tax' => 23.0,
                    ],
                ],
                'taxes' => [],
                'total' => 123.0,
                'netflag' => 0,
                'flags' => [],
                'comment' => '',
                'memo' => '',
                'invoice' => null,
                'content' => [
                    [
                        'itemid' => 1,
                        'description' => 'Service',
                        'content' => '',
                        'count' => 1,
                        'grossprice' => 123.0,
                        'netprice' => 100.0,
                        'total' => 123.0,
                        'totalbase' => 100.0,
                        'totaltax' => 23.0,
                        'taxid' => 1,
                        'taxcategory' => '',
                        'prodid' => '',
                    ],
                ],
                'ksefshowbalancesummary' => 0,
                'ksefxmladdallvalues' => 0,
                'paytype' => PAYTYPE_TRANSFER,
                'account' => '11111111111111111111111111',
                'export' => false,
                'division_bank' => '',
                'bankaccounts' => [],
                'extid' => '',
            ];
        }

        private function ksefXmlGenerator()
        {
            $reflection = new \ReflectionClass(KSeF::class);
            $ksef = $reflection->newInstanceWithoutConstructor();
            $this->setKSeFProperty($ksef, 'lms', new FakeKSeFLms());
            $this->setKSeFProperty($ksef, 'divisions', [
                1 => [
                    'email' => '',
                    'phone' => '',
                    'rbe' => '',
                    'regon' => '',
                ],
            ]);
            $this->setKSeFProperty($ksef, 'countries', [
                1 => [
                    'ccode' => 'pl_PL',
                ],
            ]);
            $this->setKSeFProperty($ksef, 'defaultCurrency', 'PLN');
            $this->setKSeFProperty($ksef, 'taxes', [
                1 => [
                    'value' => 23,
                    'reversecharge' => 0,
                    'taxed' => 1,
                ],
            ]);
            $this->setKSeFProperty($ksef, 'payTypes', [
                PAYTYPE_TRANSFER => 6,
            ]);
            $this->setKSeFProperty($ksef, 'showOnlyAlternativeAccounts', false);
            $this->setKSeFProperty($ksef, 'showAllAccounts', false);

            return $ksef;
        }

        private function setKSeFProperty($ksef, $name, $value)
        {
            $property = new \ReflectionProperty(KSeF::class, $name);
            $property->setAccessible(true);
            $property->setValue($ksef, $value);
        }
    }

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
}
