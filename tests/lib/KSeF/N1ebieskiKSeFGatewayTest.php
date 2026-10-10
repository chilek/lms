<?php

namespace LMS\Tests\KSeF;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Lms\KSeF\KSeFConfig;
use Lms\KSeF\N1ebieskiKSeFGateway;
use N1ebieski\KSEFClient\ClientBuilder;
use N1ebieski\KSEFClient\Testing\Fixtures\Requests\Sessions\Invoices\List\ListRequestFixture;
use N1ebieski\KSEFClient\Testing\Fixtures\Requests\Sessions\Invoices\List\ListResponseFixture;
use N1ebieski\KSEFClient\ValueObjects\Mode;
use PHPUnit\Framework\TestCase;

class N1ebieskiKSeFGatewayTest extends TestCase
{
    public function testMapsCompleteInvoiceListWithOneHttpRequest()
    {
        $listResponse = new ListResponseFixture();
        unset($listResponse->data['continuationToken']);
        $history = [];
        $config = $this->ksefConfig();
        $gateway = $this->gatewayWithResponses($config, [
            new Response($listResponse->statusCode, [], $listResponse->toContents()),
        ], $history);
        $sessionReference = (new ListRequestFixture())->data['referenceNumber'];

        $invoices = $gateway->listInvoices($config, '5265877635', $sessionReference);

        $this->assertCount(2, $invoices);
        $this->assertSame(1, $invoices[0]['ordinal_number']);
        $this->assertSame(200, $invoices[0]['status']);
        $this->assertSame('5265877635-20250626-010080DD2B5E-26', $invoices[0]['ksef_number']);
        $this->assertSame('2025-09-18T12:24:01.0154302+00:00', $invoices[0]['permanent_storage_date']);
        $this->assertSame(2, $invoices[1]['ordinal_number']);
        $this->assertSame(440, $invoices[1]['status']);
        $this->assertSame('5265877635-20250626-010080DD2B5E-26', $invoices[1]['original_ksef_number']);
        $this->assertNull($invoices[1]['permanent_storage_date']);

        $this->assertCount(1, $history);
        $this->assertStringEndsWith(
            '/sessions/' . $sessionReference . '/invoices',
            $history[0]['request']->getUri()->getPath()
        );
        parse_str($history[0]['request']->getUri()->getQuery(), $query);
        $this->assertSame('1000', $query['pageSize']);
    }

    public function testFormatsRealXmlValidationErrorsWithLineAndColumn()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('line 1, column 28');

        (new N1ebieskiKSeFGateway())->validateXml('<Faktura><broken></Faktura>');
    }

    private function ksefConfig(): KSeFConfig
    {
        return KSeFConfig::fromArray([
            'environment' => 'test',
            'token' => 'secret-token',
        ]);
    }

    private function gatewayWithResponses(
        KSeFConfig $ksefConfig,
        array $responses,
        array &$history
    ): N1ebieskiKSeFGateway {
        $handler = HandlerStack::create(new MockHandler($responses));
        $handler->push(Middleware::history($history));
        $client = (new ClientBuilder())
            ->withMode(Mode::Test)
            ->withHttpClient(new GuzzleClient(['handler' => $handler]))
            ->withValidateXml(false)
            ->build();
        $gateway = new N1ebieskiKSeFGateway();
        $clients = new \ReflectionProperty($gateway, 'clients');
        $clients->setAccessible(true);
        $clients->setValue($gateway, [
            spl_object_hash($ksefConfig) . ':5265877635' => $client,
        ]);

        return $gateway;
    }
}
