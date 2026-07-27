<?php

namespace LMS\Tests\KSeF {
    if (!defined('STORAGE_DIR')) {
        define('STORAGE_DIR', sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'lms-ksef-test-storage');
    }

    if (!class_exists('PHPUnit\Framework\TestCase') && class_exists('PHPUnit_Framework_TestCase')) {
        class_alias('PHPUnit_Framework_TestCase', 'PHPUnit\Framework\TestCase');
    }

    require_once __DIR__ . '/FakeKsefUpoClient.php';

    use Lms\KSeF\N1ebieskiKSeFGateway;
    use N1ebieski\KSEFClient\Exceptions\XmlValidationException;
    use N1ebieski\KSEFClient\Requests\Sessions\Invoices\KsefUpo\KsefUpoRequest;
    use N1ebieski\KSEFClient\Requests\Sessions\Invoices\List\ListRequest;
    use N1ebieski\KSEFClient\ValueObjects\Requests\ContinuationToken;
    use PHPUnit\Framework\TestCase;

    class N1ebieskiKSeFGatewayTest extends TestCase
    {
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

            $sessionReferenceNumber = '20260424-SO-ABCDEFGHIJ-1234567890-AB';
            $request = $method->invoke($gateway, $sessionReferenceNumber, 'NEXT-PAGE');

            $this->assertInstanceOf(ListRequest::class, $request);
            $this->assertSame($sessionReferenceNumber, $request->referenceNumber->value);
            $this->assertSame(1000, $request->pageSize->value);
            $this->assertInstanceOf(ContinuationToken::class, $request->continuationToken);
            $this->assertSame('NEXT-PAGE', $request->continuationToken->value);
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
                new XmlValidationException(
                    'The value is not valid with xsd.',
                    0,
                    null,
                    ['errors' => [$error]]
                )
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

}
