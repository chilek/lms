<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2026 LMS Developers
 *
 *  Please, see the doc/AUTHORS for more information about authors!
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License Version 2 as
 *  published by the Free Software Foundation.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program; if not, write to the Free Software
 *  Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307,
 *  USA.
 *
 *  $Id$
 */

use \Lms\KSeF\KSeF;
use \Lms\KSeF\KSeFConfig;
use \Lms\KSeF\KSeFRepository;
use \Lms\KSeF\KSeFSubmissionService;
use \Lms\KSeF\N1ebieskiKSeFGateway;

function invoiceKSeFResultKey()
{
    try {
        return bin2hex(random_bytes(8));
    } catch (\Throwable $e) {
        return sha1(uniqid('', true));
    }
}

function invoiceKSeFRenderSendResult(array $result)
{
    $layout['pagetitle'] = trans('KSeF invoice handling');
    $backUrl = $result['backurl'] ?? '?m=invoicelist';

    echo '<H1>' . $layout['pagetitle'] . '</H1>';
    echo '<style>'
        . '.ksef-submit-result-document,.ksef-submit-result-ksef-number,.ksef-submit-result-upo{white-space:nowrap;}'
        . '.ksef-submit-result-status{overflow-wrap:anywhere;word-break:break-word;}'
        . '</style>';

    if (!empty($result['error'])) {
        echo '<p class="red">'
            . htmlspecialchars($result['error'], ENT_QUOTES, 'UTF-8')
            . '</p>';
        echo '<p>'
            . '<a href="' . htmlspecialchars($backUrl, ENT_QUOTES, 'UTF-8') . '">'
            . trans('Return to invoice list')
            . '</a>'
            . '</p>';
        return;
    }

    $sendResult = $result['send_result'];
    $syncResult = $result['sync_result'];
    $skippedDocIds = $result['skipped_doc_ids'];
    $resultDocuments = $result['result_documents'];

    $skipped = intval($sendResult['skipped']) + count($skippedDocIds);
    echo '<p>'
        . trans('KSeF submitted:') . ' ' . intval($sendResult['submitted'])
        . ', ' . trans('KSeF synchronized:') . ' ' . intval($syncResult['updated'])
        . ', ' . trans('skipped:') . ' ' . $skipped
        . '</p>';

    $sendErrors = [];
    foreach ($sendResult['errors'] as $error) {
        $sendErrors[(int) $error['docid']][] = $error['error'];
    }
    $syncErrors = [];
    foreach ($syncResult['errors'] as $error) {
        $syncErrors[(int) $error['id']][] = $error['error'];
    }
    $skippedMap = array_fill_keys($skippedDocIds, true);

    if (!empty($resultDocuments)) {
        echo '<table class="lmsbox ksef-submit-result-table" cellpadding="3">';
        echo '<thead><tr>'
            . '<th class="ksef-submit-result-document">' . trans('Document') . '</th>'
            . '<th class="ksef-submit-result-status">' . trans('Status') . '</th>'
            . '<th class="ksef-submit-result-ksef-number">' . trans('KSeF number') . '</th>'
            . '<th class="ksef-submit-result-upo">' . trans('UPO') . '</th>'
            . '</tr></thead><tbody>';
        foreach ($resultDocuments as $document) {
            $docId = (int) $document['id'];
            $ksefDocumentId = (int) $document['ksefdocumentid'];
            $statusMessages = [];
            $statusClass = '';

            if (isset($skippedMap[$docId])) {
                $statusMessages[] = trans('Document is not eligible for KSeF submission or has been submitted already.');
                $statusClass = 'red';
            }
            if (!empty($sendErrors[$docId])) {
                $statusMessages = array_merge($statusMessages, $sendErrors[$docId]);
                $statusClass = 'red';
            }
            if ($ksefDocumentId && !empty($syncErrors[$ksefDocumentId])) {
                $statusMessages = array_merge($statusMessages, $syncErrors[$ksefDocumentId]);
                $statusClass = 'red';
            }
            if (empty($statusMessages)) {
                if ((int) $document['status'] === KSeFSubmissionService::STATUS_ACCEPTED) {
                    $statusMessages[] = $document['statusdescription'] ?: trans('KSeF accepted');
                } elseif (isset($document['status'])) {
                    $statusMessages[] = ($document['statusdescription'] ?: trans('waiting for KSeF handling'))
                        . ' (' . intval($document['status']) . ')';
                    $statusDetails = KSeF::formatStatusDetails($document['statusdetails']);
                    if (!empty($statusDetails)) {
                        $statusMessages[] = $statusDetails;
                    }
                } else {
                    $statusMessages[] = trans('not submitted to KSeF');
                }
            }

            $upo = '-';
            if (!empty($document['ksefnumber']) && KSeF::upoFileExists($document['ksefnumber'])) {
                $upo = '<a href="?m=invoiceksefinfo&id=' . $docId . '&action=upo-download">'
                    . trans('Download UPO')
                    . '</a>'
                    . ' | '
                    . '<a href="?m=invoiceksefinfo&id=' . $docId . '&action=upo-view" target="_blank">'
                    . trans('View UPO')
                    . '</a>';
            } elseif ((int) $document['status'] === KSeFSubmissionService::STATUS_ACCEPTED) {
                $upo = trans('UPO not available');
            }

            echo '<tr>'
                . '<td class="ksef-submit-result-document">'
                . htmlspecialchars($document['fullnumber'], ENT_QUOTES, 'UTF-8')
                . '</td>'
                . '<td class="ksef-submit-result-status' . ($statusClass ? ' ' . $statusClass : '') . '">'
                . htmlspecialchars(implode(' ', $statusMessages), ENT_QUOTES, 'UTF-8')
                . '</td>'
                . '<td class="ksef-submit-result-ksef-number">'
                . htmlspecialchars($document['ksefnumber'] ?: '-', ENT_QUOTES, 'UTF-8')
                . '</td>'
                . '<td class="ksef-submit-result-upo">' . $upo . '</td>'
                . '</tr>';
        }
        echo '</tbody></table>';
    }

    echo '<p>'
        . '<a href="' . htmlspecialchars($backUrl, ENT_QUOTES, 'UTF-8') . '">'
        . trans('Return to invoice list')
        . '</a>'
        . '</p>';
}

if (!empty($_GET['action']) && $_GET['action'] == 'send-result') {
    if (!ConfigHelper::checkPrivileges('finances_management', 'financial_operations')) {
        die('Access denied.');
    }

    $resultKey = preg_replace('/[^a-f0-9]/', '', $_GET['key'] ?? '');
    $result = null;
    if ($resultKey !== '') {
        $resultSessionKey = 'invoiceksefresult.' . $resultKey;
        $SESSION->restore($resultSessionKey, $result);
        $SESSION->remove($resultSessionKey);
    }

    $layout['pagetitle'] = trans('KSeF invoice handling');
    $SMARTY->display('header.html');
    if (empty($result) || !is_array($result)) {
        invoiceKSeFRenderSendResult([
            'backurl' => '?m=invoicelist',
            'error' => trans('KSeF submission result is not available.'),
        ]);
    } else {
        invoiceKSeFRenderSendResult($result);
    }
    $SMARTY->display('footer.html');
    die;
}

if (!empty($_GET['action']) && $_GET['action'] == 'send') {
    if (!ConfigHelper::checkPrivileges('finances_management', 'financial_operations')) {
        die('Access denied.');
    }
    if ($_SERVER['REQUEST_METHOD'] != 'POST') {
        die('Invalid request method.');
    }

    set_time_limit(0);

    if (!empty($_GET['id'])) {
        $docIds = [
            intval($_GET['id']),
        ];
    } elseif (isset($_POST['marks']) && is_array($_POST['marks'])) {
        $docIds = Utils::filterIntegers($_POST['marks']);
    } else {
        $docIds = [];
    }
    $docIds = array_values(array_unique(array_filter(array_map('intval', $docIds))));
    if (empty($docIds)) {
        die('No invoices selected.');
    }
    $backUrl = '?m=invoicelist';
    if (!empty($_POST['backurl']) && is_string($_POST['backurl'])
        && preg_match('/^\?m=invoicelist(?:[&#]|$)/', $_POST['backurl'])) {
        $backUrl = $_POST['backurl'];
    }

    $result = [
        'backurl' => $backUrl,
    ];
    try {
        $section = 'ksef';
        $repository = new KSeFRepository($DB);
        $configProvider = function (?int $divisionId = null) use ($section) {
            if ($divisionId !== null) {
                ConfigHelper::setFilter($divisionId);
            }

            return KSeFConfig::fromConfigHelper($section, true);
        };
        $config = KSeFConfig::fromConfigHelper($section, false);
        $ksef = new KSeF($DB, $LMS);
        $service = new KSeFSubmissionService(
            $repository,
            new N1ebieskiKSeFGateway(),
            function (array $invoice) use ($LMS, $ksef) {
                $invoiceContent = $LMS->GetInvoiceContent((int) $invoice['id']);
                if (empty($invoiceContent)) {
                    return ['error' => 'Invoice not found.'];
                }

                return $ksef->getInvoiceXml($invoiceContent);
            },
            $configProvider
        );

        $selectedDocumentLimit = count($docIds);
        $eligibleInvoices = $repository->getEligibleInvoices($selectedDocumentLimit, null, null, $docIds);
        $pendingDocuments = $repository->getPendingDocuments($selectedDocumentLimit, null, null, $docIds);
        $actionableDocIds = [];
        foreach ($eligibleInvoices as $invoice) {
            $actionableDocIds[(int) $invoice['id']] = true;
        }
        foreach ($pendingDocuments as $document) {
            $actionableDocIds[(int) $document['docid']] = true;
        }

        $sendResult = $service->send($config, null, null, $docIds);
        $syncResult = [
            'updated' => 0,
            'errors' => [],
        ];
        if ($sendResult['submitted'] > 0 || !empty($pendingDocuments)) {
            $syncResult = $service->sync($config, null, null, $docIds);
        }
        $skippedDocIds = array_values(array_diff($docIds, array_keys($actionableDocIds)));
        $result['send_result'] = $sendResult;
        $result['sync_result'] = $syncResult;
        $result['skipped_doc_ids'] = $skippedDocIds;
        $result['result_documents'] = $DB->GetAll(
            'SELECT
                d.id,
                d.fullnumber,
                kd.id AS ksefdocumentid,
                kd.status,
                kd.statusdescription,
                kd.statusdetails,
                kd.ksefnumber
            FROM documents d
            LEFT JOIN (
                SELECT docid, MAX(id) AS maxid
                FROM ksefdocuments
                GROUP BY docid
            ) latestkd ON latestkd.docid = d.id
            LEFT JOIN ksefdocuments kd ON kd.id = latestkd.maxid
            WHERE d.id IN (' . implode(',', $docIds) . ')
            ORDER BY d.id'
        ) ?: [];
    } catch (\Throwable $e) {
        $result['error'] = $e->getMessage();
    }

    $resultKey = invoiceKSeFResultKey();
    $SESSION->save('invoiceksefresult.' . $resultKey, $result);
    $SESSION->redirect('?m=invoiceksefinfo&action=send-result&key=' . $resultKey);
}

if (!empty($_GET['purchase'])) {
    $doc = $DB->GetRow(
        'SELECT
            d.id,
            d.invoice_number AS fullnumber,
            d.issue_date AS cdate,
            d.seller_ten AS div_ten,
            d.buyer_identifier_value AS div_buyer,
            d.division_id AS divisionid,
            ? AS environment,
            ? AS ksefbatchsessionnumber,
            ? AS ksefbatchsessionlastupdate,
            ? AS status,
            d.invoice_hash AS hash,
            d.ksef_number AS ksefnumber
        FROM ksefinvoices d
        JOIN vdivisions vd ON vd.id = d.division_id
        WHERE d.id = ?',
        [
            KSeF::ENVIRONMENT_PROD,
            0,
            0,
            200,
            $_GET['id'],
        ]
    );

    if (!empty($_GET['action'])) {
        $action = $_GET['action'];
        switch ($action) {
            case 'invoice-download':
            case 'invoice-view':
                $invoiceFileContent = KSeF::getInvoiceFile($doc['div_buyer'], $doc['ksefnumber']);
                if ($invoiceFileContent === false) {
                    die;
                }
                $invoiceFileName = $doc['ksefnumber'] . '.xml';
                break;
        }

        if ($action == 'invoice-download') {
            header('Content-Type: text/xml; charset=utf-8');
            header('Content-Disposition: attachment; filename=' . $invoiceFileName);
            header('Pragma: public');

            echo $invoiceFileContent;
        } elseif ($action == 'invoice-view') {
            header('Content-Type: text/html; charset=utf-8');
            header('Content-Disposition: inline; filename=' . $invoiceFileName);
            header('Pragma: public');

            $xml = new \DOMDocument();
            $xml->loadXML($invoiceFileContent);

            $xsl = new \DOMDocument();
            $xsl->load(LIB_DIR . DIRECTORY_SEPARATOR . 'KSeF' . DIRECTORY_SEPARATOR . 'schemat_FA(3)_v1-0E.xsl');

            $proc = new \XSLTProcessor();
            $proc->importStylesheet($xsl);

            $html = $proc->transformToXML($xml);

            echo $html;
        }

        die;
    }

    $SMARTY->assign(
        'url',
        KSeF::getQrCodeUrl([
            'environment' => $doc['environment'],
            'ten' => $doc['div_ten'],
            'date' => $doc['cdate'],
            'hash' => $doc['hash'],
        ])
    );

    $SMARTY->assign('invoice', $doc);
    $SMARTY->assign('invoice_file_exists', KSeF::invoiceFileExists($doc['div_buyer'], $doc['ksefnumber']));
    $SMARTY->display('invoice/invoiceksefinfo.html');

    $SESSION->close();
    die;
}

if ($doc = $DB->GetRow(
    'SELECT
        d.id,
        d.fullnumber,
        d.cdate,
        d.div_ten,
        d.divisionid,
        kbs.environment,
        kbs.ksefnumber AS ksefbatchsessionnumber,
        kbs.lastupdate AS ksefbatchsessionlastupdate,
        kd.status,
        kd.statusdescription AS ksefstatusdescription,
        kd.statusdetails AS ksefstatusdetails,
        kd.hash,
        kd.ksefnumber
    FROM documents d
    JOIN customerview c ON c.id = d.customerid
    LEFT JOIN (
        SELECT
            kd.docid,
            MAX(kd.id) AS maxid
        FROM ksefdocuments kd
        GROUP BY kd.docid
    ) kd2 ON kd2.docid = d.id
    JOIN ksefdocuments kd ON kd.docid = d.id AND (kd.status IN ? OR kd.id = kd2.maxid)
    JOIN ksefbatchsessions kbs ON kbs.id = kd.batchsessionid
    WHERE d.id = ?',
    [
        [
            0,
            200,
        ],
        $_GET['id'],
    ]
)) {
    $doc['ksefstatusdetails'] = KSeF::formatStatusDetails($doc['ksefstatusdetails']);

    if (!empty($_GET['action'])) {
        $action = $_GET['action'];
        switch ($action) {
            case 'upo-download':
            case 'upo-view':
                $upoFileContent = KSeF::getUpoFile($doc['ksefnumber']);
                if ($upoFileContent === false) {
                    die;
                }
                $upoFileName = $doc['ksefnumber'] . '.xml';
                break;
        }

        if ($action == 'upo-download') {
            header('Content-Type: text/xml; charset=utf-8');
            header('Content-Disposition: attachment; filename=' . $upoFileName);
            header('Pragma: public');

            echo $upoFileContent;
        } elseif ($action == 'upo-view') {
            header('Content-Type: text/html; charset=utf-8');
            header('Content-Disposition: inline; filename=' . $upoFileName);
            header('Pragma: public');

            $xml = new \DOMDocument();
            $xml->loadXML($upoFileContent);

            $xsl = new \DOMDocument();
            $xsl->load(LIB_DIR . DIRECTORY_SEPARATOR . 'KSeF' . DIRECTORY_SEPARATOR . 'upo-ksef-v4-3-to-html.xsl');

            $proc = new \XSLTProcessor();
            $proc->importStylesheet($xsl);

            $html = $proc->transformToXML($xml);

            echo $html;
        }

        die;
    }

    if (empty($doc['status']) || $doc['status'] == 200) {
        $SMARTY->assign(
            'url',
            KSeF::getQrCodeUrl([
                'environment' => $doc['environment'],
                'ten' => $doc['div_ten'],
                'date' => $doc['cdate'],
                'hash' => $doc['hash'],
            ])
        );
        if (empty($doc['ksefnumber']) || empty($doc['status'])) {
            $SMARTY->assign(
                'certificateurl',
                KSeF::getCertificateQrCodeUrl([
                    'environment' => $doc['environment'],
                    'ten' => $doc['div_ten'],
                    'divisionid' => $doc['divisionid'],
                    'hash' => $doc['hash'],
                ])
            );
        }
    }

    $SMARTY->assign('invoice', $doc);
    $SMARTY->assign('upo_file_exists', KSeF::upoFileExists($doc['ksefnumber']));
    $SMARTY->display('invoice/invoiceksefinfo.html');
}
