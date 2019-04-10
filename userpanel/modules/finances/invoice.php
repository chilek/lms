<?php

/*
 *  LMS version 1.11-git
 *
 *  (C) Copyright 2001-2016 LMS Developers
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

global $LMS, $SESSION, $SMARTY, $layout;

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
		&& !is_file(SMARTY_TEMPLATES_DIR . '/' . $custom_templates_dir))
		$SMARTY->AddTemplateDir(SMARTY_TEMPLATES_DIR . '/' . $custom_templates_dir);
	$SMARTY->AddTemplateDir(
		array(
			SMARTY_TEMPLATES_DIR . '/default',
			SMARTY_TEMPLATES_DIR,
		)
	);
	$document = new LMSHtmlInvoice($SMARTY);
}

// handle multi-invoices print
if(!empty($_POST['inv']))
{
	$layout['pagetitle'] = trans('Invoices');

	$count = count($_POST['inv']);
	$i = 0;
	foreach (array_keys($_POST['inv']) as $key) {
		$invoice = $LMS->GetInvoiceContent(intval($key));
		$i++;
		if ($invoice['customerid'] != $SESSION->id)
			continue;

		if ($count == 1)
			$docnumber = docnumber(array(
				'number' => $invoice['number'],
				'template' => $invoice['template'],
				'cdate' => $invoice['cdate'],
			));

		if($i == $count)
			$invoice['last'] = TRUE;
		$invoice['type'] = $type;

		$document->Draw($invoice);
		if (!isset($invoice['last']))
			$document->NewPage();

		if (!$invoice['published'])
			$LMS->PublishDocuments($invoice['id']);
	}
} else {
	$invoice = $LMS->GetInvoiceContent($_GET['id']);

	if ($invoice['customerid'] != $SESSION->id)
		die;

	if ($invoice['archived']) {
		$invoice = $LMS->GetArchiveDocument($_GET['id']);
		if ($invoice) {
			header('Content-Type: ' . $invoice['content-type']);
			header('Content-Disposition: inline; filename=' . $invoice['filename']);
			echo $invoice['data'];
		}
		$SESSION->close();
		die;
	}

	$invoice['last'] = TRUE;
	$invoice['type'] = $type;

	$docnumber = docnumber(array(
		'number' => $invoice['number'],
		'template' => $invoice['template'],
		'cdate' => $invoice['cdate'],
	));

	if(!isset($invoice['invoice']))
		$layout['pagetitle'] = trans('Invoice No. $a', $docnumber);
	else
		$layout['pagetitle'] = trans('Credit Note No. $a', $docnumber);

	$document->Draw($invoice);

	if (!$invoice['published'])
		$LMS->PublishDocuments($invoice['id']);
}

if (!is_null($attachment_name) && isset($docnumber)) {
	$attachment_name = str_replace('%number', $docnumber, $attachment_name);
	$attachment_name = preg_replace('/[^[:alnum:]_\.]/i', '_', $attachment_name);
} else
	$attachment_name = 'invoices.pdf';

$document->WriteToBrowser($attachment_name);

?>
