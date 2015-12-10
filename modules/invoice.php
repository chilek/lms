<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2014 LMS Developers
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

function invoice_body($document, $invoice) {
	$document->Draw($invoice);
	if (!isset($invoice['last']))
		$document->NewPage();
}

$attachment_name = ConfigHelper::getConfig('invoices.attachment_name');
$invoice_type = strtolower(ConfigHelper::getConfig('invoices.type'));

if ($invoice_type == 'pdf') {
	$pdf_type = ConfigHelper::getConfig('invoices.pdf_type', 'tcpdf');
	$pdf_type = ucwords($pdf_type);
	$classname = 'LMS' . $pdf_type . 'Invoice';
	$document = new $classname(trans('Invoices'));
} else
	$document = new LMSHtmlInvoice($SMARTY);

if (isset($_GET['print']) && $_GET['print'] == 'cached') {
	$SESSION->restore('ilm', $ilm);
	$SESSION->remove('ilm');

	if (isset($_POST['marks']))
		foreach ($_POST['marks'] as $idx => $mark)
			$ilm[$idx] = intval($mark);

	if (sizeof($ilm))
		foreach ($ilm as $mark)
			$ids[] = $mark;

	if (!isset($ids)) {
		$SESSION->close();
		die;
	}

	$layout['pagetitle'] = trans('Invoices');

	if (isset($_GET['cash'])) {
		$ids = $DB->GetCol('SELECT DISTINCT docid
			FROM cash, documents
			WHERE docid = documents.id AND (documents.type = ? OR documents.type = ?)
				AND cash.id IN ('.implode(',', $ids).')
			ORDER BY docid',
			array(DOC_INVOICE, DOC_CNOTE));
	}

	if (!empty($_GET['original'])) $which[] = trans('ORIGINAL');
	if (!empty($_GET['copy'])) $which[] = trans('COPY');
	if (!empty($_GET['duplicate'])) $which[] = trans('DUPLICATE');

	if (!sizeof($which)) $which[] = trans('ORIGINAL');

	$count = sizeof($ids) * sizeof($which);
	$i = 0;

	foreach ($ids as $idx => $invoiceid) {
		$invoice = $LMS->GetInvoiceContent($invoiceid);
		if (count($ids) == 1)
			$docnumber = docnumber($invoice['number'], $invoice['template'], $invoice['cdate']);

		foreach ($which as $type) {
			$i++;
			if ($i == $count) $invoice['last'] = TRUE;
			$invoice['type'] = $type;
			invoice_body($document, $invoice);
		}
	}
} elseif (isset($_GET['fetchallinvoices'])) {
	$layout['pagetitle'] = trans('Invoices');

	$offset = intval(date('Z'));
	$ids = $DB->GetCol('SELECT id FROM documents d
				WHERE cdate >= ? AND cdate <= ? AND (type = ? OR type = ?)'
				.(!empty($_GET['customerid']) ? ' AND d.customerid = '.intval($_GET['customerid']) : '')
				.(!empty($_GET['numberplanid']) ? ' AND d.numberplanid = '.intval($_GET['numberplanid']) : '')
				.(!empty($_GET['autoissued']) ? ' AND d.userid = 0' : '')
				.(!empty($_GET['groupid']) ?
				' AND '.(!empty($_GET['groupexclude']) ? 'NOT' : '').'
					EXISTS (SELECT 1 FROM customerassignments a
					WHERE a.customergroupid = '.intval($_GET['groupid']).'
						AND a.customerid = d.customerid)' : '')
				.' AND NOT EXISTS (
					SELECT 1 FROM customerassignments a
					JOIN excludedgroups e ON (a.customergroupid = e.customergroupid)
					WHERE e.userid = lms_current_user() AND a.customerid = d.customerid)'
				.' ORDER BY CEIL(cdate/86400), id',
				array(intval($_GET['from']) - $offset, intval($_GET['to']) - $offset, DOC_INVOICE, DOC_CNOTE));
	if (!$ids) {
		$SESSION->close();
		die;
	}

	if (!empty($_GET['original'])) $which[] = trans('ORIGINAL');
	if (!empty($_GET['copy'])) $which[] = trans('COPY');
	if (!empty($_GET['duplicate'])) $which[] = trans('DUPLICATE');

	if (!sizeof($which)) $which[] = trans('ORIGINAL');

	$count = sizeof($ids) * sizeof($which);
	$i = 0;

	foreach ($ids as $idx => $invoiceid) {
		$invoice = $LMS->GetInvoiceContent($invoiceid);
		if (count($ids) == 1)
			$docnumber = docnumber($invoice['number'], $invoice['template'], $invoice['cdate']);

		foreach ($which as $type) {
			$i++;
			if ($i == $count) $invoice['last'] = TRUE;
			$invoice['type'] = $type;
			invoice_body($document, $invoice);
		}
	}
} elseif ($invoice = $LMS->GetInvoiceContent($_GET['id'])) {
	$docnumber = docnumber($invoice['number'], $invoice['template'], $invoice['cdate']);
	if(!isset($invoice['invoice']))
		$layout['pagetitle'] = trans('Invoice No. $a', $docnumber);
	else
		$layout['pagetitle'] = trans('Credit Note No. $a', $docnumber);

	$which = array();

	if (!empty($_GET['original'])) $which[] = trans('ORIGINAL');
	if (!empty($_GET['copy'])) $which[] = trans('COPY');
	if (!empty($_GET['duplicate'])) $which[] = trans('DUPLICATE');

	if (!sizeof($which)) {
		$tmp = explode(',', ConfigHelper::getConfig('invoices.default_printpage'));
		foreach ($tmp as $t)
			if (trim($t) == 'original') $which[] = trans('ORIGINAL');
			elseif (trim($t) == 'copy') $which[] = trans('COPY');
			elseif (trim($t) == 'duplicate') $which[] = trans('DUPLICATE');

		if (!sizeof($which)) $which[] = trans('ORIGINAL');
	}

	$count = sizeof($which);
	$i = 0;

	foreach ($which as $type) {
		$i++;
		if ($i == $count) $invoice['last'] = TRUE;
		$invoice['type'] = $type;
		invoice_body($document, $invoice);
	}
} else
	$SESSION->redirect('?m=invoicelist');

if (!is_null($attachment_name) && isset($docnumber)) {
	$attachment_name = str_replace('%number', $docnumber, $attachment_name);
	$attachment_name = preg_replace('/[^[:alnum:]_\.]/i', '_', $attachment_name);
} else
	$attachment_name = 'invoices.' . ($invoice_type == 'pdf' ? 'pdf' : 'html');

$document->WriteToBrowser($attachment_name);

?>
