<?php

namespace N1ebieski\KSEFClient\ValueObjects\Requests {
    if (!class_exists(ReferenceNumber::class)) {
        final class ReferenceNumber
        {
            public $value;

            public function __construct(string $value)
            {
                $this->value = $value;
            }

            public static function from(string $value): self
            {
                return new self($value);
            }
        }
    }
}

namespace N1ebieski\KSEFClient\ValueObjects\Requests\Sessions {
    if (!enum_exists(FormCode::class)) {
        enum FormCode: string
        {
            case Fa3 = 'FA (3)';
        }
    }
}

namespace N1ebieski\KSEFClient\Requests\Sessions\Batch\OpenAndSend {
    use N1ebieski\KSEFClient\ValueObjects\Requests\Sessions\FormCode;

    if (!class_exists(OpenAndSendXmlRequest::class)) {
        final class OpenAndSendXmlRequest
        {
            public $formCode;
            public $faktury;

            public function __construct(FormCode $formCode, array $faktury)
            {
                $this->formCode = $formCode;
                $this->faktury = $faktury;
            }
        }
    }
}

namespace N1ebieski\KSEFClient\Requests\Sessions\Batch\Close {
    use N1ebieski\KSEFClient\ValueObjects\Requests\ReferenceNumber;

    if (!class_exists(CloseRequest::class)) {
        final class CloseRequest
        {
            public $referenceNumber;

            public function __construct(ReferenceNumber $referenceNumber)
            {
                $this->referenceNumber = $referenceNumber;
            }
        }
    }
}

namespace N1ebieski\KSEFClient\ValueObjects\Requests {
    if (!class_exists(KsefNumber::class)) {
        final class KsefNumber
        {
            public $value;

            public function __construct(string $value)
            {
                $this->value = $value;
            }

            public static function from(string $value): self
            {
                return new self($value);
            }
        }
    }
}

namespace N1ebieski\KSEFClient\Requests\Sessions\Invoices\KsefUpo {
    use N1ebieski\KSEFClient\ValueObjects\Requests\KsefNumber;
    use N1ebieski\KSEFClient\ValueObjects\Requests\ReferenceNumber;

    if (!class_exists(KsefUpoRequest::class)) {
        final class KsefUpoRequest
        {
            public $referenceNumber;
            public $ksefNumber;

            public function __construct(ReferenceNumber $referenceNumber, KsefNumber $ksefNumber)
            {
                $this->referenceNumber = $referenceNumber;
                $this->ksefNumber = $ksefNumber;
            }
        }
    }
}

namespace LMS\Tests\KSeF {
    if (!defined('STORAGE_DIR')) {
        define('STORAGE_DIR', sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'lms-ksef-test-storage');
    }

    if (!class_exists('PHPUnit\Framework\TestCase') && class_exists('PHPUnit_Framework_TestCase')) {
        class_alias('PHPUnit_Framework_TestCase', 'PHPUnit\Framework\TestCase');
    }

    use Lms\KSeF\N1ebieskiKSeFGateway;
    use N1ebieski\KSEFClient\Requests\Sessions\Batch\Close\CloseRequest;
    use N1ebieski\KSEFClient\Requests\Sessions\Batch\OpenAndSend\OpenAndSendXmlRequest;
    use N1ebieski\KSEFClient\Requests\Sessions\Invoices\KsefUpo\KsefUpoRequest;
    use N1ebieski\KSEFClient\ValueObjects\Requests\Sessions\FormCode;
    use PHPUnit\Framework\TestCase;

    class N1ebieskiKSeFGatewayTest extends TestCase
    {
        public function testCreatesBatchXmlRequestForFa3Documents()
        {
            $gateway = new N1ebieskiKSeFGateway();
            $method = new \ReflectionMethod($gateway, 'createOpenAndSendXmlRequest');
            $method->setAccessible(true);

            $request = $method->invoke($gateway, [
                '<Faktura>1</Faktura>',
                '<Faktura>2</Faktura>',
            ]);

            $this->assertInstanceOf(OpenAndSendXmlRequest::class, $request);
            $this->assertSame(FormCode::Fa3, $request->formCode);
            $this->assertSame([
                '<Faktura>1</Faktura>',
                '<Faktura>2</Faktura>',
            ], $request->faktury);
        }

        public function testCreatesBatchCloseRequest()
        {
            $gateway = new N1ebieskiKSeFGateway();
            $method = new \ReflectionMethod($gateway, 'createCloseRequest');
            $method->setAccessible(true);

            $request = $method->invoke($gateway, '20260424-SO-ABCDEFGHIJ-1234567890-AB');

            $this->assertInstanceOf(CloseRequest::class, $request);
            $this->assertSame('20260424-SO-ABCDEFGHIJ-1234567890-AB', $request->referenceNumber->value);
        }

        public function testCreatesKsefUpoRequest()
        {
            $gateway = new N1ebieskiKSeFGateway();
            $method = new \ReflectionMethod($gateway, 'createKsefUpoRequest');
            $method->setAccessible(true);

            $request = $method->invoke(
                $gateway,
                '20260424-SO-ABCDEFGHIJ-1234567890-AB',
                '5130271243-20260424-ABCDEF-123456-AB'
            );

            $this->assertInstanceOf(KsefUpoRequest::class, $request);
            $this->assertSame('20260424-SO-ABCDEFGHIJ-1234567890-AB', $request->referenceNumber->value);
            $this->assertSame('5130271243-20260424-ABCDEF-123456-AB', $request->ksefNumber->value);
        }

        public function testFetchesOriginalUpoForDuplicateInvoice()
        {
            $gateway = new N1ebieskiKSeFGateway();
            $method = new \ReflectionMethod($gateway, 'fetchOriginalUpo');
            $method->setAccessible(true);
            $client = new FakeKsefUpoClient('<OriginalUPO />');

            $result = $method->invoke(
                $gateway,
                $client,
                '20260424-SO-ABCDEFGHIJ-1234567890-AB',
                '5130271243-20260424-ABCDEF-123456-AB'
            );

            $this->assertSame('<OriginalUPO />', $result);
            $this->assertInstanceOf(KsefUpoRequest::class, $client->request);
        }

        public function testOriginalUpoFetchFailureDoesNotBlockDuplicateRecovery()
        {
            $gateway = new N1ebieskiKSeFGateway();
            $method = new \ReflectionMethod($gateway, 'fetchOriginalUpo');
            $method->setAccessible(true);
            $client = new FakeKsefUpoClient(null, true);

            $result = $method->invoke(
                $gateway,
                $client,
                '20260424-SO-ABCDEFGHIJ-1234567890-AB',
                '5130271243-20260424-ABCDEF-123456-AB'
            );

            $this->assertSame(null, $result);
        }

        public function testCreatesPaginatedInvoiceListRequest()
        {
            $gateway = new N1ebieskiKSeFGateway();
            $method = new \ReflectionMethod($gateway, 'createInvoiceListRequest');
            $method->setAccessible(true);

            $request = $method->invoke($gateway, 'SESSION-1', 500, 'NEXT-PAGE');

            $this->assertSame([
                'referenceNumber' => 'SESSION-1',
                'pageSize' => 500,
                'continuationToken' => 'NEXT-PAGE',
            ], $request);
        }

        public function testFormatsXmlValidationErrorsWithLineAndColumn()
        {
            $gateway = new N1ebieskiKSeFGateway();
            $method = new \ReflectionMethod($gateway, 'formatXmlValidationException');
            $method->setAccessible(true);
            $error = new \LibXMLError();
            $error->message = 'Element NIP is not accepted by the pattern.';
            $error->line = 26;
            $error->column = 0;

            $result = $method->invoke(
                $gateway,
                new FakeXmlValidationException('The value is not valid with xsd.', [
                    'errors' => [$error],
                ])
            );

            $this->assertSame(
                'The value is not valid with xsd. Element NIP is not accepted by the pattern. (line 26, column 0)',
                $result
            );
        }

        public function testExtractsOriginalKsefNumberFromDuplicateStatusDetails()
        {
            $gateway = new N1ebieskiKSeFGateway();
            $method = new \ReflectionMethod($gateway, 'extractOriginalKsefNumberFromDetails');
            $method->setAccessible(true);

            $result = $method->invoke(
                $gateway,
                'Duplikat faktury. Faktura o numerze KSeF: 5265877635-20250626-010080DD2B5E-26 została już prawidłowo przesłana do systemu w sesji: 20250626-SO-2F14610000-242991F8C9-B4'
            );

            $this->assertSame('5265877635-20250626-010080DD2B5E-26', $result);
        }

        public function testExtractsOriginalSessionReferenceFromDuplicateStatusDetails()
        {
            $gateway = new N1ebieskiKSeFGateway();
            $method = new \ReflectionMethod($gateway, 'extractOriginalSessionReferenceFromDetails');
            $method->setAccessible(true);

            $result = $method->invoke(
                $gateway,
                'Duplikat faktury. Faktura o numerze KSeF: 5265877635-20250626-010080DD2B5E-26 została już prawidłowo przesłana do systemu w sesji: 20250626-SO-2F14610000-242991F8C9-B4'
            );

            $this->assertSame('20250626-SO-2F14610000-242991F8C9-B4', $result);
        }
    }

    class FakeXmlValidationException extends \Exception
    {
        public $context;

        public function __construct(string $message, array $context)
        {
            parent::__construct($message);
            $this->context = $context;
        }
    }

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
}
