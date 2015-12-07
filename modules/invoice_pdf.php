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

function invoice_body() {
	global $invoice, $pdf;

	if (isset($invoice['invoice']))
		$template = ConfigHelper::getConfig('invoices.cnote_template_file');
	else
		$template = ConfigHelper::getConfig('invoices.template_file');

	switch ($template) {
		case "standard":
			invoice_body_standard();
			break;
		case "FT-0100":
			invoice_body_ft0100();
			break;
		default:
			require($template);
	}

	if (!isset($invoice['last']))
		new_page();
}

$pdf_type = ConfigHelper::getConfig('invoices.pdf_type', 'tcpdf');
if (!in_array($pdf_type, array('ezpdf', 'tcpdf')))
	$pdf_type = 'tcpdf';
require_once(LIB_DIR . '/' . $pdf_type . '.php');
require_once(MODULES_DIR . '/invoice_' . $pdf_type . '.inc.php');

$pdf = init_pdf('A4', 'portrait', trans('Invoices'));

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
			invoice_body();
		}
	}
} elseif (isset($_GET['fetchallinvoices'])) {
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
			invoice_body();
		}
	}
} elseif ($invoice = $LMS->GetInvoiceContent($_GET['id'])) {
	$docnumber = docnumber($invoice['number'], $invoice['template'], $invoice['cdate']);

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
		invoice_body();
	}
} else
	$SESSION->redirect('?m=invoicelist');

if (isset($docnumber)) {
	$filename = ConfigHelper::getConfig('invoices.file_name', 'file.pdf');
	$filename = str_replace('%number', $docnumber, $filename);
	$filename = preg_replace('/[^[:alnum:]_]/i', '_', $filename);
} else
	$filename = null;

close_pdf($pdf, $filename);

?>
