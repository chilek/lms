<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2015 LMS Developers
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

include('class.php');
session_start();

if (!$_SESSION['uid'] || !$_GET['id'])
	die;

if ($_SESSION['uid'] != $DB->GetOne('SELECT customerid FROM documents WHERE id=?', array($_GET['id'])))
	die;

$invoice_type = strtolower(ConfigHelper::getConfig('invoices.type'));
$attachment_name = ConfigHelper::getConfig('invoices.attachment_name');

if ($invoice_type == 'pdf') {
	$pdf_type = ConfigHelper::getConfig('invoices.pdf_type', 'tcpdf');
	$pdf_type = ucwords($pdf_type);
	$classname = 'LMS' . $pdf_type . 'Invoice';
	$document = new $classname('A4', 'portrait', trans('Invoices'));
} else
	$document = new LMSHtmlInvoice($SMARTY);

$invoice = $LMS->GetInvoiceContent($_GET['id']);

$docnumber = docnumber(array(
	'number' => $invoice['number'],
	'template' => $invoice['template'],
	'cdate' => $invoice['cdate'],
));
$layout['pagetitle'] = trans('Invoice No. $a', $docnumber);
$invoice['last'] = true;
$invoice['type'] = trans('ORIGINAL');

$document->Draw($invoice);

if (!is_null($attachment_name)) {
	$attachment_name = str_replace('%number', $docnumber, $attachment_name);
	$attachment_name = preg_replace('/[^[:alnum:]_\.]/i', '_', $attachment_name);
} else
	$attachment_name = 'invoices.pdf';

$document->WriteToBrowser($attachment_name);

?>
