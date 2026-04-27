<?php

namespace LMS\Tests\KSeF;

if (!defined('STORAGE_DIR')) {
    define('STORAGE_DIR', sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'lms-ksef-test-storage');
}

if (!class_exists('PHPUnit\Framework\TestCase') && class_exists('PHPUnit_Framework_TestCase')) {
    class_alias('PHPUnit_Framework_TestCase', 'PHPUnit\Framework\TestCase');
}

require_once __DIR__ . '/FakeKSeFGateway.php';
require_once __DIR__ . '/FakeKSeFRepository.php';

use Lms\KSeF\KSeF;
use Lms\KSeF\KSeFConfig;
use Lms\KSeF\KSeFSubmissionService;
use PHPUnit\Framework\TestCase;

class KSeFSubmissionServiceTest extends TestCase
{
    public function testSendSubmitsEligibleInvoicesInSingleBatchSessionAndClosesIt()
    {
        $repository = new FakeKSeFRepository([
            $this->invoice(123),
            $this->invoice(124),
        ]);
        $gateway = new FakeKSeFGateway();
        $service = $this->service($repository, $gateway);

        $result = $service->send($this->ksefConfig());

        $this->assertSame(2, $result['submitted']);
        $this->assertSame(0, $result['skipped']);
        $this->assertSame('LOCAL-123', $repository->sessions[0]['reference_number']);
        $this->assertSame(KSeF::ENVIRONMENT_TEST, $repository->sessions[0]['environment']);
        $this->assertSame('SESSION-1', $repository->sessionReferenceUpdates[0]['reference_number']);
        $this->assertSame(1, count($repository->sessions));
        $this->assertSame(2, count($repository->documents));
        $this->assertSame(123, $repository->documents[0]['docid']);
        $this->assertSame(124, $repository->documents[1]['docid']);
        $this->assertSame(1, $repository->documents[0]['ordinalnumber']);
        $this->assertSame(2, $repository->documents[1]['ordinalnumber']);
        $this->assertSame(0, $repository->documents[0]['status']);
        $this->assertSame(
            base64_encode(hash('sha256', '<Faktura>123</Faktura>', true)),
            $repository->documents[0]['hash']
        );
        $this->assertSame([
            '<Faktura>123</Faktura>',
            '<Faktura>124</Faktura>',
        ], $gateway->sentXmlBatches[0]);
        $this->assertSame(['SESSION-1'], $gateway->closedBatchSessions);
        $this->assertSame(1, count($repository->sessionCloseUpdates));
    }

    public function testSendUsesDivisionScopedConfigForEachInvoiceGroup()
    {
        $repository = new FakeKSeFRepository([
            $this->invoice(123, '1234567890', 7),
            $this->invoice(124, '1234567890', 8),
        ]);
        $gateway = new FakeKSeFGateway();
        $service = $this->service(
            $repository,
            $gateway,
            null,
            function (?int $divisionId) {
                return $divisionId === 8
                    ? $this->ksefConfig('production', 'division-8-token')
                    : $this->ksefConfig('test', 'division-7-token');
            }
        );

        $result = $service->send($this->ksefConfig());

        $this->assertSame(2, $result['submitted']);
        $this->assertSame(2, count($repository->sessions));
        $this->assertSame(KSeF::ENVIRONMENT_TEST, $repository->sessions[0]['environment']);
        $this->assertSame(KSeF::ENVIRONMENT_PROD, $repository->sessions[1]['environment']);
        $this->assertSame('division-7-token', $gateway->sentConfigs[0]['token']);
        $this->assertSame('division-8-token', $gateway->sentConfigs[1]['token']);
    }

    public function testSendUsesDivisionScopedConfigWhenDefaultConfigHasNoCredentials()
    {
        $repository = new FakeKSeFRepository([
            $this->invoice(123, '1234567890', 7),
        ]);
        $gateway = new FakeKSeFGateway();
        $service = $this->service(
            $repository,
            $gateway,
            null,
            function (?int $divisionId) {
                return $this->ksefConfig('test', 'division-token');
            }
        );
        $selectionConfig = KSeFConfig::fromArray([
            'environment' => 'test',
            'auth_method' => 'certificate',
        ], false);

        $result = $service->send($selectionConfig);

        $this->assertSame(1, $result['submitted']);
        $this->assertSame('division-token', $gateway->sentConfigs[0]['token']);
    }

    public function testSendCanBeLimitedToSelectedInvoices()
    {
        $repository = new FakeKSeFRepository([
            $this->invoice(123),
            $this->invoice(124),
        ]);
        $gateway = new FakeKSeFGateway();
        $service = $this->service($repository, $gateway);

        $result = $service->send($this->ksefConfig(), null, null, [124]);

        $this->assertSame(1, $result['submitted']);
        $this->assertSame([124], $repository->eligibleDocIds);
        $this->assertSame(124, $repository->documents[0]['docid']);
        $this->assertSame(['<Faktura>124</Faktura>'], $gateway->sentXmlBatches[0]);
    }

    public function testSendSelectedInvoicesIgnoresConfiguredMaxDocuments()
    {
        $repository = new FakeKSeFRepository([
            $this->invoice(123),
            $this->invoice(124),
        ]);
        $gateway = new FakeKSeFGateway();
        $service = $this->service($repository, $gateway);

        $result = $service->send($this->ksefConfig('test', 'secret-token', 1), null, null, [123, 124]);

        $this->assertSame(2, $result['submitted']);
        $this->assertSame([123, 124], $repository->eligibleDocIds);
        $this->assertSame([
            '<Faktura>123</Faktura>',
            '<Faktura>124</Faktura>',
        ], $gateway->sentXmlBatches[0]);
    }

    public function testSendSkipsInvoiceWhenXmlBuilderReturnsError()
    {
        $repository = new FakeKSeFRepository([
            $this->invoice(123),
        ]);
        $gateway = new FakeKSeFGateway();
        $service = $this->service(
            $repository,
            $gateway,
            function () {
                return ['error' => 'Invalid buyer TEN'];
            }
        );

        $result = $service->send($this->ksefConfig());

        $this->assertSame(0, $result['submitted']);
        $this->assertSame(1, $result['skipped']);
        $this->assertSame([], $repository->sessions);
        $this->assertSame([], $gateway->sentXmlBatches);
    }

    public function testSendSkipsOnlyInvoiceWithInvalidXml()
    {
        $repository = new FakeKSeFRepository([
            $this->invoice(123),
            $this->invoice(124),
        ]);
        $gateway = new FakeKSeFGateway();
        $gateway->invalidXmlDocuments = [
            '<Faktura>124</Faktura>' => 'Invalid KSeF XML: NIP pattern mismatch.',
        ];
        $service = $this->service($repository, $gateway);

        $result = $service->send($this->ksefConfig());

        $this->assertSame(1, $result['submitted']);
        $this->assertSame(1, $result['skipped']);
        $this->assertSame(124, $result['errors'][0]['docid']);
        $this->assertSame('Invalid KSeF XML: NIP pattern mismatch.', $result['errors'][0]['error']);
        $this->assertSame([
            '<Faktura>123</Faktura>',
        ], $gateway->sentXmlBatches[0]);
    }

    public function testSendSkipsInvoiceWhenReservationFails()
    {
        $repository = new FakeKSeFRepository([
            $this->invoice(123),
        ]);
        $repository->reservationFails = true;
        $gateway = new FakeKSeFGateway();
        $service = $this->service($repository, $gateway);

        $result = $service->send($this->ksefConfig());

        $this->assertSame(0, $result['submitted']);
        $this->assertSame(1, $result['skipped']);
        $this->assertSame([], $gateway->sentXmlBatches);
    }

    public function testSendReportsReservationSkipReasonWhenNoDocumentsWereReserved()
    {
        $repository = new FakeKSeFRepository([
            $this->invoice(123),
        ]);
        $repository->reservedSkipped = [
            123 => 'Invoice disappeared during reservation.',
        ];
        $gateway = new FakeKSeFGateway();
        $service = $this->service($repository, $gateway);

        $result = $service->send($this->ksefConfig());

        $this->assertSame(0, $result['submitted']);
        $this->assertSame(1, $result['skipped']);
        $this->assertSame('Invoice disappeared during reservation.', $result['errors'][0]['error']);
        $this->assertSame([], $gateway->sentXmlBatches);
    }

    public function testSendRemovesLocalReservationWhenCloseFailsAfterXmlWasSent()
    {
        $repository = new FakeKSeFRepository([
            $this->invoice(123),
        ]);
        $gateway = new FakeKSeFGateway();
        $gateway->failClose = true;
        $service = $this->service($repository, $gateway);

        $result = $service->send($this->ksefConfig());

        $this->assertSame(0, $result['submitted']);
        $this->assertSame(1, $result['skipped']);
        $this->assertSame(1, count($result['errors']));
        $this->assertSame(123, $repository->documents[0]['docid']);
        $this->assertSame([1], $repository->discardedSessions);
        $this->assertSame([], $repository->statusUpdates);
    }

    public function testSendClosesBatchSessionWhenLocalSessionReferenceUpdateFails()
    {
        $repository = new FakeKSeFRepository([
            $this->invoice(123),
        ]);
        $repository->failSessionReferenceUpdate = true;
        $gateway = new FakeKSeFGateway();
        $service = $this->service($repository, $gateway);

        $result = $service->send($this->ksefConfig());

        $this->assertSame(0, $result['submitted']);
        $this->assertSame(1, $result['skipped']);
        $this->assertSame(['SESSION-1'], $gateway->closedBatchSessions);
        $this->assertSame([1], $repository->discardedSessions);
        $this->assertSame([], $repository->sessionCloseUpdates);
    }

    public function testSyncDiscoversInvoiceReferenceByOrdinalNumber()
    {
        $repository = new FakeKSeFRepository([], [
            $this->pendingDocument([
                'ordinalnumber' => 2,
                'session_document_count' => 2,
            ]),
        ]);
        $gateway = new FakeKSeFGateway();
        $gateway->sessionInvoiceReferences['SESSION-1'] = [
            [
                'ordinal_number' => 1,
                'reference_number' => 'INVOICE-1',
            ],
            [
                'ordinal_number' => 2,
                'reference_number' => 'INVOICE-2',
            ],
        ];
        $gateway->invoiceStatuses['SESSION-1:INVOICE-2'] = [
            'status' => 200,
            'status_description' => 'Accepted',
            'status_details' => '',
            'ksef_number' => '1234567890-20260424-ABCDEF',
            'permanent_storage_date' => '2026-04-24T10:00:00+02:00',
            'upo' => '<UPO />',
        ];
        $service = $this->service($repository, $gateway);

        $result = $service->sync($this->ksefConfig());

        $this->assertSame(1, $result['updated']);
        $this->assertSame(10, $repository->statusUpdates[0]['id']);
        $this->assertSame('SESSION-1', $gateway->listedSessions[0]);
        $this->assertSame(200, $repository->statusUpdates[0]['status']);
        $this->assertSame('1234567890-20260424-ABCDEF', $repository->statusUpdates[0]['ksef_number']);
        $this->assertSame('2026-04-24 10:00:00', $repository->statusUpdates[0]['permanent_storage_date']);
        $this->assertSame('<UPO />', $repository->savedUpos[0]['content']);
    }

    public function testSyncUsesSingleInvoiceReferenceOnlyForSingleDocumentSession()
    {
        $repository = new FakeKSeFRepository([], [
            $this->pendingDocument(),
        ]);
        $gateway = new FakeKSeFGateway();
        $gateway->sessionInvoiceReferences['SESSION-1'] = [
            [
                'reference_number' => 'INVOICE-1',
            ],
        ];
        $gateway->invoiceStatuses['SESSION-1:INVOICE-1'] = [
            'status' => 0,
            'status_description' => 'Processing',
            'status_details' => '',
        ];
        $service = $this->service($repository, $gateway);

        $result = $service->sync($this->ksefConfig());

        $this->assertSame(1, $result['updated']);
        $this->assertSame(0, $repository->statusUpdates[0]['status']);
    }

    public function testSyncDoesNotCloseOpenSession()
    {
        $repository = new FakeKSeFRepository([], [
            $this->pendingDocument([
                'session_status' => 0,
            ]),
        ]);
        $gateway = new FakeKSeFGateway();
        $gateway->sessionInvoiceReferences['SESSION-1'] = [
            [
                'reference_number' => 'INVOICE-1',
            ],
        ];
        $gateway->invoiceStatuses['SESSION-1:INVOICE-1'] = [
            'status' => 0,
            'status_description' => 'Processing',
            'status_details' => '',
        ];
        $service = $this->service($repository, $gateway);

        $result = $service->sync($this->ksefConfig());

        $this->assertSame(1, $result['updated']);
        $this->assertSame([], $gateway->closedBatchSessions);
        $this->assertSame([], $repository->sessionCloseUpdates);
    }

    public function testSyncUpdatesInvoiceStatusesIndependently()
    {
        $repository = new FakeKSeFRepository([], [
            $this->pendingDocument([
                'id' => 10,
                'ordinalnumber' => 1,
                'session_document_count' => 2,
            ]),
            $this->pendingDocument([
                'id' => 11,
                'ordinalnumber' => 2,
                'session_document_count' => 2,
            ]),
        ]);
        $gateway = new FakeKSeFGateway();
        $gateway->sessionInvoiceReferences['SESSION-1'] = [
            [
                'ordinal_number' => 1,
                'reference_number' => 'INVOICE-1',
            ],
            [
                'ordinal_number' => 2,
                'reference_number' => 'INVOICE-2',
            ],
        ];
        $gateway->invoiceStatuses['SESSION-1:INVOICE-1'] = [
            'status' => 200,
            'status_description' => 'Accepted',
            'status_details' => '',
            'ksef_number' => '1234567890-20260424-ABCDEF',
            'permanent_storage_date' => '2026-04-24T10:00:00+02:00',
            'upo' => '<UPO />',
        ];
        $gateway->invoiceStatuses['SESSION-1:INVOICE-2'] = [
            'status' => 450,
            'status_description' => 'Rejected',
            'status_details' => 'Invalid invoice.',
        ];
        $service = $this->service($repository, $gateway);

        $result = $service->sync($this->ksefConfig());

        $this->assertSame(2, $result['updated']);
        $this->assertSame(10, $repository->statusUpdates[0]['id']);
        $this->assertSame(200, $repository->statusUpdates[0]['status']);
        $this->assertSame(11, $repository->statusUpdates[1]['id']);
        $this->assertSame(450, $repository->statusUpdates[1]['status']);
        $this->assertSame(null, $repository->statusUpdates[1]['ksef_number']);
        $this->assertSame('<UPO />', $repository->savedUpos[0]['content']);
        $this->assertSame(['SESSION-1'], $gateway->listedSessions);
    }

    public function testSyncUsesDivisionScopedConfigForPendingDocument()
    {
        $repository = new FakeKSeFRepository([], [
            $this->pendingDocument([
                'divisionid' => 8,
            ]),
        ]);
        $gateway = new FakeKSeFGateway();
        $gateway->sessionInvoiceReferences['SESSION-1'] = [
            [
                'reference_number' => 'INVOICE-1',
            ],
        ];
        $gateway->invoiceStatuses['SESSION-1:INVOICE-1'] = [
            'status' => 0,
            'status_description' => 'Processing',
            'status_details' => '',
        ];
        $service = $this->service(
            $repository,
            $gateway,
            null,
            function (?int $divisionId) {
                return $divisionId === 8
                    ? $this->ksefConfig('production', 'division-8-token')
                    : $this->ksefConfig('test', 'default-token');
            }
        );

        $result = $service->sync($this->ksefConfig());

        $this->assertSame(1, $result['updated']);
        $this->assertSame('division-8-token', $gateway->listedConfigs[0]['token']);
        $this->assertSame('division-8-token', $gateway->statusConfigs[0]['token']);
    }

    public function testSyncLimitsPendingDocumentsByDivisionAndCustomer()
    {
        $repository = new FakeKSeFRepository([], []);
        $gateway = new FakeKSeFGateway();
        $service = $this->service($repository, $gateway);

        $result = $service->sync($this->ksefConfig(), 8, 123);

        $this->assertSame(0, $result['updated']);
        $this->assertSame(8, $repository->pendingDivisionId);
        $this->assertSame(123, $repository->pendingCustomerId);
    }

    public function testSyncWaitsForInvoiceReferencesWhenTheyAreNotReadyYet()
    {
        $repository = new FakeKSeFRepository([], [
            $this->pendingDocument(),
        ]);
        $gateway = new FakeKSeFGateway();
        $gateway->emptyInvoiceReferenceResponses = [
            'SESSION-1' => 2,
        ];
        $gateway->sessionInvoiceReferences['SESSION-1'] = [
            [
                'reference_number' => 'INVOICE-1',
            ],
        ];
        $gateway->invoiceStatuses['SESSION-1:INVOICE-1'] = [
            'status' => 0,
            'status_description' => 'Processing',
            'status_details' => '',
        ];
        $sleeps = [];
        $service = $this->service(
            $repository,
            $gateway,
            null,
            null,
            function (int $seconds) use (&$sleeps) {
                $sleeps[] = $seconds;
            }
        );

        $result = $service->sync($this->ksefConfig());

        $this->assertSame(1, $result['updated']);
        $this->assertSame([], $result['errors']);
        $this->assertSame(0, $repository->statusUpdates[0]['status']);
        $this->assertSame(['SESSION-1', 'SESSION-1', 'SESSION-1'], $gateway->listedSessions);
        $this->assertSame([
            1,
            2,
        ], $sleeps);
    }

    public function testSyncWaitsForExpectedOrdinalWhenInvoiceReferencesArePartial()
    {
        $repository = new FakeKSeFRepository([], [
            $this->pendingDocument([
                'ordinalnumber' => 2,
                'session_document_count' => 2,
            ]),
        ]);
        $gateway = new FakeKSeFGateway();
        $gateway->invoiceReferenceResponseSequences['SESSION-1'] = [
            [
                [
                    'ordinal_number' => 1,
                    'reference_number' => 'INVOICE-1',
                ],
            ],
            [
                [
                    'ordinal_number' => 1,
                    'reference_number' => 'INVOICE-1',
                ],
                [
                    'ordinal_number' => 2,
                    'reference_number' => 'INVOICE-2',
                ],
            ],
        ];
        $gateway->invoiceStatuses['SESSION-1:INVOICE-2'] = [
            'status' => 0,
            'status_description' => 'Processing',
            'status_details' => '',
        ];
        $sleeps = [];
        $service = $this->service(
            $repository,
            $gateway,
            null,
            null,
            function (int $seconds) use (&$sleeps) {
                $sleeps[] = $seconds;
            }
        );

        $result = $service->sync($this->ksefConfig());

        $this->assertSame(1, $result['updated']);
        $this->assertSame([], $result['errors']);
        $this->assertSame([
            'SESSION-1',
            'SESSION-1',
        ], $gateway->listedSessions);
        $this->assertSame([
            1,
        ], $sleeps);
        $this->assertSame(0, $repository->statusUpdates[0]['status']);
    }

    public function testSyncWaitsForMissingInvoiceReferencesOnlyOncePerSession()
    {
        $repository = new FakeKSeFRepository([], [
            $this->pendingDocument([
                'id' => 10,
                'docid' => 123,
                'ordinalnumber' => 1,
                'session_document_count' => 2,
            ]),
            $this->pendingDocument([
                'id' => 11,
                'docid' => 124,
                'ordinalnumber' => 2,
                'session_document_count' => 2,
            ]),
        ]);
        $gateway = new FakeKSeFGateway();
        $sleeps = [];
        $service = $this->service(
            $repository,
            $gateway,
            null,
            null,
            function (int $seconds) use (&$sleeps) {
                $sleeps[] = $seconds;
            }
        );

        $result = $service->sync($this->ksefConfig());

        $this->assertSame(0, $result['updated']);
        $this->assertSame(2, count($result['errors']));
        $expectedLookupCount = $this->expectedInvoiceReferenceLookupCount();
        $this->assertSame($expectedLookupCount, count($gateway->listedSessions));
        $this->assertSame($expectedLookupCount - 1, count($sleeps));
    }

    public function testSyncCanBeLimitedToSelectedInvoices()
    {
        $repository = new FakeKSeFRepository([], [
            $this->pendingDocument([
                'docid' => 123,
            ]),
            $this->pendingDocument([
                'id' => 11,
                'docid' => 124,
                'session_reference_number' => 'SESSION-2',
            ]),
        ]);
        $gateway = new FakeKSeFGateway();
        $gateway->sessionInvoiceReferences['SESSION-2'] = [
            [
                'reference_number' => 'INVOICE-2',
            ],
        ];
        $gateway->invoiceStatuses['SESSION-2:INVOICE-2'] = [
            'status' => 0,
            'status_description' => 'Processing',
            'status_details' => '',
        ];
        $service = $this->service($repository, $gateway);

        $result = $service->sync($this->ksefConfig(), null, null, [124]);

        $this->assertSame(1, $result['updated']);
        $this->assertSame([124], $repository->pendingDocIds);
        $this->assertSame(11, $repository->statusUpdates[0]['id']);
    }

    public function testSyncSelectedInvoicesIgnoresConfiguredMaxDocuments()
    {
        $repository = new FakeKSeFRepository([], [
            $this->pendingDocument([
                'id' => 10,
                'docid' => 123,
                'session_reference_number' => 'SESSION-1',
            ]),
            $this->pendingDocument([
                'id' => 11,
                'docid' => 124,
                'session_reference_number' => 'SESSION-2',
            ]),
        ]);
        $gateway = new FakeKSeFGateway();
        $gateway->sessionInvoiceReferences['SESSION-1'] = [
            [
                'reference_number' => 'INVOICE-1',
            ],
        ];
        $gateway->sessionInvoiceReferences['SESSION-2'] = [
            [
                'reference_number' => 'INVOICE-2',
            ],
        ];
        $gateway->invoiceStatuses['SESSION-1:INVOICE-1'] = [
            'status' => 0,
            'status_description' => 'Processing',
            'status_details' => '',
        ];
        $gateway->invoiceStatuses['SESSION-2:INVOICE-2'] = [
            'status' => 0,
            'status_description' => 'Processing',
            'status_details' => '',
        ];
        $service = $this->service($repository, $gateway);

        $result = $service->sync($this->ksefConfig('test', 'secret-token', 1), null, null, [123, 124]);

        $this->assertSame(2, $result['updated']);
        $this->assertSame([123, 124], $repository->pendingDocIds);
        $this->assertSame(10, $repository->statusUpdates[0]['id']);
        $this->assertSame(11, $repository->statusUpdates[1]['id']);
    }

    public function testSyncKeepsDocumentPendingWhenUpoCannotBeSaved()
    {
        $repository = new FakeKSeFRepository([], [
            $this->pendingDocument(),
        ]);
        $repository->failUpoSave = true;
        $gateway = new FakeKSeFGateway();
        $gateway->sessionInvoiceReferences['SESSION-1'] = [
            [
                'reference_number' => 'INVOICE-1',
            ],
        ];
        $gateway->invoiceStatuses['SESSION-1:INVOICE-1'] = [
            'status' => 200,
            'status_description' => 'Accepted',
            'status_details' => '',
            'ksef_number' => '1234567890-20260424-ABCDEF',
            'permanent_storage_date' => '2026-04-24T10:00:00+02:00',
            'upo' => '<UPO />',
        ];
        $service = $this->service($repository, $gateway);

        $result = $service->sync($this->ksefConfig());

        $this->assertSame(0, $result['updated']);
        $this->assertSame('UPO save failed', $result['errors'][0]['error']);
        $this->assertSame([], $repository->statusUpdates);
    }

    public function testSyncTreatsDuplicateInvoiceStatusWithOriginalKsefNumberAsAccepted()
    {
        $repository = new FakeKSeFRepository([], [
            $this->pendingDocument(),
        ]);
        $gateway = new FakeKSeFGateway();
        $gateway->sessionInvoiceReferences['SESSION-1'] = [
            [
                'reference_number' => 'INVOICE-1',
            ],
        ];
        $gateway->invoiceStatuses['SESSION-1:INVOICE-1'] = [
            'status' => 440,
            'status_description' => 'Duplikat faktury',
            'status_details' => 'Duplikat faktury.',
            'original_ksef_number' => '1234567890-20260424-ABCDEF',
            'original_session_reference_number' => '20260424-SO-ORIGINAL',
        ];
        $service = $this->service($repository, $gateway);

        $result = $service->sync($this->ksefConfig());

        $this->assertSame(1, $result['updated']);
        $this->assertSame(200, $repository->statusUpdates[0]['status']);
        $this->assertSame('1234567890-20260424-ABCDEF', $repository->statusUpdates[0]['ksef_number']);
        $this->assertSame('Duplikat faktury', $repository->statusUpdates[0]['status_description']);
        $this->assertSame('Duplikat faktury.', $repository->statusUpdates[0]['status_details']);
        $this->assertSame([], $repository->savedUpos);
    }

    public function testSyncSavesOriginalUpoForDuplicateInvoiceWhenKsefReturnsIt()
    {
        $repository = new FakeKSeFRepository([], [
            $this->pendingDocument(),
        ]);
        $gateway = new FakeKSeFGateway();
        $gateway->sessionInvoiceReferences['SESSION-1'] = [
            [
                'reference_number' => 'INVOICE-1',
            ],
        ];
        $gateway->invoiceStatuses['SESSION-1:INVOICE-1'] = [
            'status' => 440,
            'status_description' => 'Duplikat faktury',
            'status_details' => 'Duplikat faktury.',
            'original_ksef_number' => '1234567890-20260424-ABCDEF',
            'original_session_reference_number' => '20260424-SO-ORIGINAL',
            'upo' => '<OriginalUPO />',
        ];
        $service = $this->service($repository, $gateway);

        $result = $service->sync($this->ksefConfig());

        $this->assertSame(1, $result['updated']);
        $this->assertSame(200, $repository->statusUpdates[0]['status']);
        $this->assertSame('1234567890-20260424-ABCDEF', $repository->statusUpdates[0]['ksef_number']);
        $this->assertSame('1234567890-20260424-ABCDEF', $repository->savedUpos[0]['ksef_number']);
        $this->assertSame('<OriginalUPO />', $repository->savedUpos[0]['content']);
    }

    private function ksefConfig(string $environment = 'test', string $token = 'secret-token', int $maxDocuments = 10000): KSeFConfig
    {
        return KSeFConfig::fromArray([
            'environment' => $environment,
            'auth_method' => 'token',
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
            'batchsessionid' => 20,
            'divisionid' => 7,
            'seller_ten' => '1234567890',
            'session_status' => 200,
            'session_reference_number' => 'SESSION-1',
            'ordinalnumber' => 1,
            'session_document_count' => 1,
        ], $overrides);
    }

    private function service(
        FakeKSeFRepository $repository,
        FakeKSeFGateway $gateway,
        ?callable $xmlBuilder = null,
        ?callable $configProvider = null,
        ?callable $sleeper = null
    ): KSeFSubmissionService {
        return new KSeFSubmissionService(
            $repository,
            $gateway,
            $xmlBuilder ?: function (array $invoice) {
                return '<Faktura>' . $invoice['id'] . '</Faktura>';
            },
            $configProvider,
            $sleeper ?: function () {
            }
        );
    }

    private function expectedInvoiceReferenceLookupCount(): int
    {
        $waitedSeconds = 0;
        $lookupCount = 1;
        for ($attempt = 1; $waitedSeconds < KSeFSubmissionService::INVOICE_REFERENCE_WAIT_SECONDS; $attempt++) {
            $sleepSeconds = KSeFSubmissionService::INVOICE_REFERENCE_RETRY_SECONDS[
                min($attempt - 1, count(KSeFSubmissionService::INVOICE_REFERENCE_RETRY_SECONDS) - 1)
            ];
            $waitedSeconds += min(
                $sleepSeconds,
                KSeFSubmissionService::INVOICE_REFERENCE_WAIT_SECONDS - $waitedSeconds
            );
            $lookupCount++;
        }

        return $lookupCount;
    }
}
