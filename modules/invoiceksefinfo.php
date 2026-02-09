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
        kd.hash,
        kd.ksefnumber
    FROM documents d
    JOIN customerview c ON c.id = d.customerid
    JOIN ksefdocuments kd ON kd.docid = d.id AND kd.status IN ?
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

    $SMARTY->assign(
        'url',
        KSeF::getQrCodeUrl([
            'environment' => $doc['environment'],
            'ten' => $doc['div_ten'],
            'date' => $doc['cdate'],
            'hash' => $doc['hash'],
        ])
    );
    if (empty($doc['ksefnumber']) || empty($doc['ksefstatus'])) {
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

    $SMARTY->assign('invoice', $doc);
    $SMARTY->assign('upo_file_exists', KSeF::upoFileExists($doc['ksefnumber']));
    $SMARTY->display('invoice/invoiceksefinfo.html');
}
