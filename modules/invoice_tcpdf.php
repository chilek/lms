<?php

function invoice_body() {
	global $invoice, $pdf, $CONFIG;

	if (isset($invoice['invoice']))
		$template = $CONFIG['invoices']['cnote_template_file'];
	else
		$template = $CONFIG['invoices']['template_file'];

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

	if (!isset($invoice['last'])) $pdf->AddPage();
}

require_once(LIB_DIR.'/tcpdf.php');
require_once(MODULES_DIR.'/invoice_tcpdf.inc.php');

$pdf =& init_pdf('A4', 'portrait', trans('Invoices'));

if (isset($_GET['print']) && $_GET['print'] == 'cached') {
	$SESSION->restore('ilm', $ilm);
	$SESSION->remove('ilm');

	if (isset($_POST['marks']))
		foreach($_POST['marks'] as $idx => $mark)
			$ilm[$idx] = $mark;

	if (sizeof($ilm))
		foreach($ilm as $mark)
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
	$i=0;

	foreach ($ids as $idx => $invoiceid) {
		$invoice = $LMS->GetInvoiceContent($invoiceid);

		foreach ($which as $type) {
			$i++;
			if ($i == $count) $invoice['last'] = TRUE;
			invoice_body(false);
		}
	}
} elseif (isset($_GET['fetchallinvoices'])) {
	$ids = $DB->GetCol('SELECT id FROM documents d
				WHERE cdate >= ? AND cdate <= ? AND (type = ? OR type = ?)'
				.(!empty($_GET['customerid']) ? ' AND d.customerid = '.intval($_GET['customerid']) : '')
				.(!empty($_GET['numberplanid']) ? ' AND d.numberplanid = '.intval($_GET['numberplanid']) : '')
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
				array($_GET['from'], $_GET['to'], DOC_INVOICE, DOC_CNOTE));
	if (!$ids) {
		$SESSION->close();
		die;
	}

	if (!empty($_GET['original'])) $which[] = trans('ORIGINAL');
	if (!empty($_GET['copy'])) $which[] = trans('COPY');
	if (!empty($_GET['duplicate'])) $which[] = trans('DUPLICATE');

	if (!sizeof($which)) $which[] = trans('ORIGINAL');
	
	$count = sizeof($ids) * sizeof($which);
	$i=0;

	foreach ($ids as $idx => $invoiceid) {
		$invoice = $LMS->GetInvoiceContent($invoiceid);

		foreach ($which as $type) {
			$i++;
			if ($i == $count) $invoice['last'] = TRUE;
			invoice_body();
		}
	}
} elseif ($invoice = $LMS->GetInvoiceContent($_GET['id'])) {
	$which = array();

	if (!empty($_GET['original'])) $which[] = trans('ORIGINAL');
	if (!empty($_GET['copy'])) $which[] = trans('COPY');
	if (!empty($_GET['duplicate'])) $which[] = trans('DUPLICATE');

	if (!sizeof($which)) {
		$tmp = explode(',', $CONFIG['invoices']['default_printpage']);
		foreach ($tmp as $t)
			if (trim($t) == 'original') $which[] = trans('ORIGINAL');
			elseif (trim($t) == 'copy') $which[] = trans('COPY');
			elseif (trim($t) == 'duplicate') $which[] = trans('DUPLICATE');

		if (!sizeof($which)) $which[] = trans('ORIGINAL');
	}

	$count = sizeof($which);
	$i=0;

	foreach($which as $type) {
		$i++;
		if ($i == $count) $invoice['last'] = TRUE;
		invoice_body();
	}
} else {
	$SESSION->redirect('?m=invoicelist');
}

close_pdf($pdf);

?>
