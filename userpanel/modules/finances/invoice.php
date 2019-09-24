<?php

/*
 *  LMS version 1.11-git
 *
 *  (C) Copyright 2001-2019 LMS Developers
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

use setasign\Fpdi\Tcpdf\Fpdi;
use setasign\Fpdi\PdfParser\StreamReader;

global $LMS, $SESSION, $SMARTY, $layout;
global $invoice_type, $type, $document, $classname;

function try_generate_archive_invoices($ids)
{
    global $LMS, $SESSION, $invoice_type, $type, $document, $classname;

    $SMARTY = LMSSmarty::getInstance();

    $archive_stats = $LMS->GetTradeDocumentArchiveStats($ids);

    if (($invoice_type == 'pdf' && ($archive_stats['html'] > 0 || $archive_stats['rtype'] == 'html'))
        || ($invoice_type == 'html' && ($archive_stats['pdf'] > 0 || $archive_stats['rtype'] == 'pdf'))) {
        die('Currently you can only print many documents of type text/html or application/pdf!');
    }

    if (!empty($archive_stats) && $archive_stats['archive'] > 0 && $type != trans('DUPLICATE')) {
        if ($archive_stats['rtype'] && $archive_stats['rtype'] != $invoice_type) {
            $invoice_type = $archive_stats['rtype'];
        }

        $attachment_name = 'invoices.' . ($invoice_type == 'pdf' ? 'pdf' : 'html');
        header('Content-Type: ' . ($invoice_type == 'pdf' ? 'application/pdf' : 'text/html'));
        header('Content-Disposition: attachment; filename="' . $attachment_name . '"');
        header('Pragma: public');

        if ($invoice_type == 'pdf') {
            $pdf = new Fpdi();
            $pdf->setPrintHeader(false);
            $pdf->setPrintFooter(false);
        }

        $i = 0;

        foreach ($ids as $idx => $invoiceid) {
            if ($LMS->isArchiveDocument($invoiceid)) {
                $file = $LMS->GetArchiveDocument($invoiceid);

                if ($file['document']['customerid'] != $SESSION->id) {
                    continue;
                }

                if (!$file['document']['published']) {
                    $LMS->PublishDocuments($invoiceid);
                }
            } else {
                if (!$document) {
                    if ($invoice_type == 'pdf') {
                        $document = new $classname(trans('Invoices'));
                    } else {
                        $document = new LMSHtmlInvoice($SMARTY);
                    }
                }

                $invoice = $LMS->GetInvoiceContent($invoiceid);

                if ($invoice['customerid'] != $SESSION->id) {
                    continue;
                }

                $invoice['type'] = $type;

                refresh_ui_language($invoice['lang']);
                $document->Draw($invoice);

                if (!$invoice['published']) {
                    $LMS->PublishDocuments($invoice['id']);
                }

                $file['data'] = $document->WriteToString();

                unset($document);
                $document = null;
            }

            $LMS->PublishDocuments($invoiceid);

            if ($invoice_type == 'pdf') {
                $pageCount = $pdf->setSourceFile(StreamReader::createByString($file['data']));
                for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
                    // import a page
                    $templateId = $pdf->importPage($pageNo);
                    // get the size of the imported page
                    $size = $pdf->getTemplateSize($templateId);

                    // create a page (landscape or portrait depending on the imported page size)
                    $pdf->AddPage($size['orientation'], $size);

                    // use the imported page
                    $pdf->useTemplate($templateId);
                }
            } else {
                echo $file['data'];
                if ($idx < count($ids) - 1) {
                    echo '<div style="page-break-after: always;">&nbsp;</div>';
                }
            }
        }

        if ($invoice_type == 'pdf') {
            $pdf->Output();
        }

        die;
    }
}

$type = ConfigHelper::checkConfig('userpanel.invoice_duplicate') ? trans('DUPLICATE') : trans('ORIGINAL');

$attachment_name = ConfigHelper::getConfig('invoices.attachment_name');
$invoice_type = strtolower(ConfigHelper::getConfig('invoices.type'));

if ($invoice_type == 'pdf') {
    $pdf_type = ConfigHelper::getConfig('invoices.pdf_type', 'tcpdf');
    $pdf_type = ucwords($pdf_type);
    $classname = 'LMS' . $pdf_type . 'Invoice';
    $document = new $classname(trans('Invoices'));
} else {
    // use LMS templates directory
    define('SMARTY_TEMPLATES_DIR', ConfigHelper::getConfig('directories.smarty_templates_dir', ConfigHelper::getConfig('directories.sys_dir').'/templates'));
    $SMARTY->setTemplateDir(null);
    $custom_templates_dir = ConfigHelper::getConfig('phpui.custom_templates_dir');
    if (!empty($custom_templates_dir) && file_exists(SMARTY_TEMPLATES_DIR . '/' . $custom_templates_dir)
        && !is_file(SMARTY_TEMPLATES_DIR . '/' . $custom_templates_dir)) {
        $SMARTY->AddTemplateDir(SMARTY_TEMPLATES_DIR . '/' . $custom_templates_dir);
    }
    $SMARTY->AddTemplateDir(
        array(
            SMARTY_TEMPLATES_DIR . '/default',
            SMARTY_TEMPLATES_DIR,
        )
    );
    $document = new LMSHtmlInvoice($SMARTY);
}

// handle multi-invoices print
if (!empty($_POST['inv'])) {
    $layout['pagetitle'] = trans('Invoices');

    try_generate_archive_invoices(array_keys($_POST['inv']));

    $count = count($_POST['inv']);
    $i = 0;
    foreach (array_keys($_POST['inv']) as $key) {
        $invoice = $LMS->GetInvoiceContent(intval($key));
        $i++;
        if ($invoice['customerid'] != $SESSION->id) {
            continue;
        }

        if ($count == 1) {
            $docnumber = docnumber(array(
                'number' => $invoice['number'],
                'template' => $invoice['template'],
                'cdate' => $invoice['cdate'],
                'customerid' =>  $invoice['customerid'],
            ));
        }

        if ($i == $count) {
            $invoice['last'] = true;
        }
        $invoice['type'] = $type;

        refresh_ui_language($invoice['lang']);

        $document->Draw($invoice);
        if (!isset($invoice['last'])) {
            $document->NewPage();
        }

        if (!$invoice['published']) {
            $LMS->PublishDocuments($invoice['id']);
        }
    }
    reset_ui_language();
} else {
    $invoice = $LMS->GetInvoiceContent($_GET['id']);

    if ($invoice['customerid'] != $SESSION->id) {
        die;
    }

    if ($invoice['archived'] && $type != trans('DUPLICATE')) {
        $invoice = $LMS->GetArchiveDocument($_GET['id']);
        if ($invoice) {
            header('Content-Type: ' . $invoice['content-type']);
            header('Content-Disposition: inline; filename=' . $invoice['filename']);
            echo $invoice['data'];
        }
        $SESSION->close();
        die;
    }

    $invoice['last'] = true;
    $invoice['type'] = $type;

    $docnumber = docnumber(array(
        'number' => $invoice['number'],
        'template' => $invoice['template'],
        'cdate' => $invoice['cdate'],
        'customerid' => $invoice['customerid'],
    ));

    if (!isset($invoice['invoice'])) {
        $layout['pagetitle'] = trans('Invoice No. $a', $docnumber);
    } else {
        $layout['pagetitle'] = trans('Credit Note No. $a', $docnumber);
    }

    refresh_ui_language($invoice['lang']);
    $document->Draw($invoice);
    reset_ui_language();

    if (!$invoice['published']) {
        $LMS->PublishDocuments($invoice['id']);
    }
}

if (!is_null($attachment_name) && isset($docnumber)) {
    $attachment_name = str_replace('%number', $docnumber, $attachment_name);
    $attachment_name = preg_replace('/[^[:alnum:]_\.]/i', '_', $attachment_name);
} else {
    $attachment_name = 'invoices.pdf';
}

$document->WriteToBrowser($attachment_name);
