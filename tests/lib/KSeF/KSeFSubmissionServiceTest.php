<?php

namespace LMS\Tests\KSeF;

if (!defined('STORAGE_DIR')) {
    define('STORAGE_DIR', sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'lms-ksef-test-storage');
}

require_once __DIR__ . '/FakeKSeFGateway.php';
require_once __DIR__ . '/FakeKSeFRepository.php';

use Lms\KSeF\KSeF;
use Lms\KSeF\KSeFConfig;
use Lms\KSeF\KSeFSubmissionService;
use PHPUnit\Framework\TestCase;

class KSeFSubmissionServiceTest extends TestCase
{
    public function testSendReservesAndSubmitsOneBatchPerSellerAndDivision()
    {
        $repository = new FakeKSeFRepository([
            $this->invoice(123, '1234567890', 7),
            $this->invoice(124, '1234567890', 7),
            $this->invoice(125, '1234567890', 8),
        ]);
        $gateway = new FakeKSeFGateway();
        $service = $this->service($repository, $gateway, null, function (?int $divisionId) {
            return $this->config(
                $divisionId === 8 ? 'production' : 'test',
                'division-' . $divisionId . '-token'
            );
        });

        $result = $service->send(KSeFConfig::fromArray(['environment' => 'test'], false));

        $this->assertSame(['submitted' => 3, 'skipped' => 0, 'errors' => []], $result);
        $this->assertCount(2, $repository->reservations);
        $this->assertSame(
            [KSeF::ENVIRONMENT_TEST, KSeF::ENVIRONMENT_PROD],
            array_column($repository->reservations, 'environment')
        );
        $this->assertSame(['division-7-token', 'division-8-token'], $gateway->sentTokens);
        $this->assertSame([
            '<Faktura>123</Faktura>',
            '<Faktura>124</Faktura>',
        ], $gateway->sentXmlBatches[0]);
        $this->assertSame(['<Faktura>125</Faktura>'], $gateway->sentXmlBatches[1]);
        $this->assertSame(
            'Cq4ssQtAVcbVa8bW5amkaOK0hNNzB6Pfthlb+vOQYOQ=',
            $repository->reservations[0]['documents'][0]['hash']
        );
        $this->assertSame(['SESSION-1', 'SESSION-2'], $gateway->closedBatchSessions);
        $this->assertCount(2, $repository->sessionCloseUpdates);
    }

    public function testSendHonoursExplicitSelectionInsteadOfConfiguredLimit()
    {
        $repository = new FakeKSeFRepository([
            $this->invoice(123),
            $this->invoice(124),
        ]);
        $gateway = new FakeKSeFGateway();

        $result = $this->service($repository, $gateway)->send(
            $this->config('test', 'token', 1),
            null,
            null,
            [123, 124, 124]
        );

        $this->assertSame(2, $result['submitted']);
        $this->assertSame([123, 124], $repository->eligibleDocIds);
        $this->assertSame(2, $repository->eligibleLimit);
        $this->assertCount(2, $gateway->sentXmlBatches[0]);
    }

    public function testSendDoesNotQueryWhenSelectionIsEmpty()
    {
        $repository = new FakeKSeFRepository([$this->invoice(123)]);
        $gateway = new FakeKSeFGateway();

        $result = $this->service($repository, $gateway)->send($this->config(), null, null, []);

        $this->assertSame(['submitted' => 0, 'skipped' => 0, 'errors' => []], $result);
        $this->assertNull($repository->eligibleLimit);
        $this->assertSame([], $gateway->sentXmlBatches);
    }

    public function testSendSkipsOnlyInvoicesThatCannotProduceValidXml()
    {
        $repository = new FakeKSeFRepository([
            $this->invoice(123),
            $this->invoice(124),
            $this->invoice(125),
        ]);
        $gateway = new FakeKSeFGateway();
        $gateway->invalidXmlDocuments['<Faktura>124</Faktura>'] = 'Schema mismatch.';
        $service = $this->service($repository, $gateway, function (array $invoice) {
            return $invoice['id'] === 125
                ? ['error' => 'Invalid buyer TEN.']
                : '<Faktura>' . $invoice['id'] . '</Faktura>';
        });

        $result = $service->send($this->config());

        $this->assertSame(1, $result['submitted']);
        $this->assertSame(2, $result['skipped']);
        $this->assertSame([124, 125], array_column($result['errors'], 'docid'));
        $this->assertSame(['Schema mismatch.', 'Invalid buyer TEN.'], array_column($result['errors'], 'error'));
        $this->assertSame(['<Faktura>123</Faktura>'], $gateway->sentXmlBatches[0]);
    }

    public function testSendReturnsRepositoryReservationReason()
    {
        $repository = new FakeKSeFRepository([$this->invoice(123)]);
        $repository->reservedSkipped = [123 => 'Invoice disappeared during reservation.'];
        $gateway = new FakeKSeFGateway();

        $result = $this->service($repository, $gateway)->send($this->config());

        $this->assertSame(0, $result['submitted']);
        $this->assertSame(1, $result['skipped']);
        $this->assertSame('Invoice disappeared during reservation.', $result['errors'][0]['error']);
        $this->assertSame([], $gateway->sentXmlBatches);
    }

    public function testSendKeepsRemoteReferenceWhenClosingSessionFails()
    {
        $repository = new FakeKSeFRepository([$this->invoice(123)]);
        $gateway = new FakeKSeFGateway();
        $gateway->failClose = true;

        $result = $this->service($repository, $gateway)->send($this->config());

        $this->assertSame(1, $result['skipped']);
        $this->assertSame('SESSION-1', $repository->sessionReferenceUpdates[0]['reference_number']);
        $this->assertSame([], $repository->discardedSessions);
        $this->assertSame([], $repository->sessionCloseUpdates);
    }

    public function testSendKeepsReservationWhenSavingRemoteReferenceFails()
    {
        $repository = new FakeKSeFRepository([$this->invoice(123)]);
        $repository->failSessionReferenceUpdate = true;
        $gateway = new FakeKSeFGateway();

        $result = $this->service($repository, $gateway)->send($this->config());

        $this->assertSame(1, $result['skipped']);
        $this->assertSame(['SESSION-1'], $gateway->closedBatchSessions);
        $this->assertSame([], $repository->discardedSessions);
        $this->assertSame([], $repository->sessionCloseUpdates);
        $this->assertStringContainsString('Remote KSeF session reference: SESSION-1.', $result['errors'][0]['error']);
    }

    public function testSyncMatchesSessionInvoicesByOrdinalAndUpdatesThemIndependently()
    {
        $repository = new FakeKSeFRepository([], [
            $this->pendingDocument(['id' => 10, 'ordinalnumber' => 1]),
            $this->pendingDocument(['id' => 11, 'ordinalnumber' => 2]),
        ]);
        $gateway = new FakeKSeFGateway();
        $gateway->sessionInvoices['SESSION-1'] = [
            $this->remoteInvoice(1, [
                'status' => 200,
                'status_description' => 'Accepted',
                'ksef_number' => '1234567890-20260424-ABCDEF',
                'permanent_storage_date' => '2026-04-24T10:00:00+02:00',
            ]),
            $this->remoteInvoice(2, [
                'status' => 450,
                'status_description' => 'Rejected',
                'status_details' => 'Invalid invoice.',
            ]),
        ];

        $result = $this->service($repository, $gateway)->sync($this->config());

        $this->assertSame(['updated' => 2, 'errors' => []], $result);
        $this->assertSame([10, 11], array_column($repository->statusUpdates, 'id'));
        $this->assertSame([200, 450], array_column($repository->statusUpdates, 'status'));
        $this->assertSame('2026-04-24 08:00:00', $repository->statusUpdates[0]['permanent_storage_date']);
        $this->assertNull($repository->statusUpdates[1]['ksef_number']);
        $this->assertSame(['SESSION-1'], $gateway->listedSessions);
    }

    public function testSyncUsesDivisionConfigAndSelectionFilters()
    {
        $repository = new FakeKSeFRepository([], [
            $this->pendingDocument(['divisionid' => 8]),
        ]);
        $gateway = new FakeKSeFGateway();
        $gateway->sessionInvoices['SESSION-1'] = [$this->remoteInvoice(1)];
        $configCalls = 0;
        $service = $this->service($repository, $gateway, null, function (?int $divisionId) use (&$configCalls) {
            $configCalls++;
            return $this->config('production', 'division-' . $divisionId . '-token');
        });

        $result = $service->sync($this->config(), 8, 123);

        $this->assertSame(1, $result['updated']);
        $this->assertSame(8, $repository->pendingDivisionId);
        $this->assertSame(123, $repository->pendingCustomerId);
        $this->assertSame(1, $configCalls);
        $this->assertSame('division-8-token', $gateway->listedTokens[0]);
    }

    public function testSyncUsesOneRequestAndLeavesMissingDocumentsPending()
    {
        $repository = new FakeKSeFRepository([], [
            $this->pendingDocument(['id' => 10, 'ordinalnumber' => 1]),
            $this->pendingDocument(['id' => 11, 'ordinalnumber' => 2]),
        ]);
        $gateway = new FakeKSeFGateway();
        $gateway->sessionInvoices['SESSION-1'] = [$this->remoteInvoice(1)];

        $result = $this->service($repository, $gateway)->sync($this->config());

        $this->assertSame(1, $result['updated']);
        $this->assertCount(1, $result['errors']);
        $this->assertSame(11, $result['errors'][0]['id']);
        $this->assertSame(['SESSION-1'], $gateway->listedSessions);
    }

    public function testSyncRetriesClosingUnconfirmedSessionBeforeListingInvoices()
    {
        $repository = new FakeKSeFRepository([], [
            $this->pendingDocument(['session_status' => KSeF::STATUS_PENDING]),
        ]);
        $gateway = new FakeKSeFGateway();
        $gateway->sessionInvoices['SESSION-1'] = [$this->remoteInvoice(1)];

        $result = $this->service($repository, $gateway)->sync($this->config());

        $this->assertSame(1, $result['updated']);
        $this->assertSame(['SESSION-1'], $gateway->closedBatchSessions);
        $this->assertSame([1], $repository->sessionCloseUpdates);
        $this->assertSame(['SESSION-1'], $gateway->listedSessions);
    }

    public function testSyncCanRecoverStatusesWhenRetryingSessionCloseFails()
    {
        $repository = new FakeKSeFRepository([], [
            $this->pendingDocument(['session_status' => KSeF::STATUS_PENDING]),
        ]);
        $gateway = new FakeKSeFGateway();
        $gateway->failClose = true;
        $gateway->sessionInvoices['SESSION-1'] = [$this->remoteInvoice(1)];

        $result = $this->service($repository, $gateway)->sync($this->config());

        $this->assertSame(1, $result['updated']);
        $this->assertSame([], $result['errors']);
        $this->assertSame([], $repository->sessionCloseUpdates);
        $this->assertSame(['SESSION-1'], $gateway->listedSessions);
    }

    public function testSyncHonoursExplicitSelectionInsteadOfConfiguredLimit()
    {
        $repository = new FakeKSeFRepository([], [
            $this->pendingDocument(['id' => 10, 'docid' => 123, 'session_reference_number' => 'SESSION-1']),
            $this->pendingDocument(['id' => 11, 'docid' => 124, 'session_reference_number' => 'SESSION-2']),
        ]);
        $gateway = new FakeKSeFGateway();
        $gateway->sessionInvoices = [
            'SESSION-1' => [$this->remoteInvoice(1)],
            'SESSION-2' => [$this->remoteInvoice(1)],
        ];

        $result = $this->service($repository, $gateway)->sync(
            $this->config('test', 'token', 1),
            null,
            null,
            [123, 124, 124]
        );

        $this->assertSame(2, $result['updated']);
        $this->assertSame([123, 124], $repository->pendingDocIds);
        $this->assertSame(2, $repository->pendingLimit);
        $this->assertSame([10, 11], array_column($repository->statusUpdates, 'id'));
    }

    public function testSyncDoesNotQueryWhenSelectionIsEmpty()
    {
        $repository = new FakeKSeFRepository([], [$this->pendingDocument()]);
        $gateway = new FakeKSeFGateway();

        $result = $this->service($repository, $gateway)->sync($this->config(), null, null, []);

        $this->assertSame(['updated' => 0, 'errors' => []], $result);
        $this->assertNull($repository->pendingLimit);
        $this->assertSame([], $gateway->listedSessions);
    }

    public function testSyncRejectsDocumentWithoutSellerTenBeforeCallingKSeF()
    {
        $repository = new FakeKSeFRepository([], [$this->pendingDocument(['seller_ten' => ''])]);
        $gateway = new FakeKSeFGateway();

        $result = $this->service($repository, $gateway)->sync($this->config());

        $this->assertSame(0, $result['updated']);
        $this->assertSame('Missing seller TEN.', $result['errors'][0]['error']);
        $this->assertSame([], $gateway->listedSessions);
    }

    public function testSyncRecoversOriginalNumberForDuplicate()
    {
        $repository = new FakeKSeFRepository([], [$this->pendingDocument()]);
        $gateway = new FakeKSeFGateway();
        $gateway->sessionInvoices['SESSION-1'] = [$this->remoteInvoice(1, [
            'status' => KSeF::STATUS_DUPLICATE,
            'status_description' => 'Duplikat faktury',
            'status_details' => 'Duplikat faktury.',
            'original_ksef_number' => '1234567890-20260424-ABCDEF',
        ])];

        $result = $this->service($repository, $gateway)->sync($this->config());

        $this->assertSame(1, $result['updated']);
        $this->assertSame(200, $repository->statusUpdates[0]['status']);
        $this->assertSame('1234567890-20260424-ABCDEF', $repository->statusUpdates[0]['ksef_number']);
        $this->assertSame('Duplikat faktury', $repository->statusUpdates[0]['status_description']);
    }

    private function config(
        string $environment = 'test',
        string $token = 'secret-token',
        int $maxDocuments = 10000
    ): KSeFConfig {
        return KSeFConfig::fromArray([
            'environment' => $environment,
            'token' => $token,
            'max_documents' => $maxDocuments,
        ]);
    }

    private function invoice(int $id, string $sellerTen = '1234567890', int $divisionId = 7): array
    {
        return [
            'id' => $id,
            'divisionid' => $divisionId,
            'division_ten' => $sellerTen,
        ];
    }

    private function pendingDocument(array $overrides = []): array
    {
        return array_merge([
            'id' => 10,
            'docid' => 123,
            'divisionid' => 7,
            'seller_ten' => '1234567890',
            'session_reference_number' => 'SESSION-1',
            'session_id' => 1,
            'session_status' => KSeF::STATUS_ACCEPTED,
            'ordinalnumber' => 1,
        ], $overrides);
    }

    private function remoteInvoice(int $ordinalNumber, array $overrides = []): array
    {
        return array_merge([
            'ordinal_number' => $ordinalNumber,
            'status' => 0,
        ], $overrides);
    }

    private function service(
        FakeKSeFRepository $repository,
        FakeKSeFGateway $gateway,
        ?callable $xmlBuilder = null,
        ?callable $configProvider = null
    ): KSeFSubmissionService {
        return new KSeFSubmissionService(
            $repository,
            $gateway,
            $xmlBuilder ?: function (array $invoice) {
                return '<Faktura>' . $invoice['id'] . '</Faktura>';
            },
            $configProvider
        );
    }
}
