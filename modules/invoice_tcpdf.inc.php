<?php

function invoice_date() {
	global $pdf, $invoice;

	$pdf->SetFont('arial', '', 10);
	$pdf->writeHTMLCell(0, 0, '', 25, trans('Settlement date:').' <b>'.date("d.m.Y",$invoice['cdate']).' r.</b>', 0, 1, 0, true, 'R');
}

function invoice_title() {
	global $pdf, $invoice, $type;

	$pdf->SetY(35);
	$pdf->SetFont('arial', 'B', 16);
	$docnumber = docnumber($invoice['number'], $invoice['template'], $invoice['cdate']);
	if (isset($invoice['invoice']))
		$title = trans('Credit Note No. $a', $docnumber);
	else
		$title = trans('Invoice No. $a', $docnumber);
	$pdf->Write($h=0, $title, $link='', $fill=0, $align='C', $ln=true, $stretch=0, $firstline=false, $firstblock=false, $maxh=0);

	if (isset($invoice['invoice'])) {
		$pdf->SetFont('arial', 'B', 12);
		$docnumber = docnumber($invoice['invoice']['number'], $invoice['invoice']['template'], $invoice['invoice']['cdate']);
		$title = trans('for Invoice No. $a', $docnumber);
		$pdf->Write($h=0, $title, $link='', $fill=0, $align='C', $ln=true, $stretch=0, $firstline=false, $firstblock=false, $maxh=0);
	}

	$pdf->SetFont('arial', '', 16);
	$pdf->Write($h=0, $type, $link='', $fill=0, $align='C', $ln=true, $stretch=0, $firstline=false, $firstblock=false, $maxh=0);

	if ($type == trans('DUPLICATE')) {
		$pdf->SetFont('arial', '', 10);
		$title = trans('Duplicate draw-up date:').' '.date('d.m.Y').' r.';
		$pdf->Write($h=0, $title, $link='', $fill=0, $align='C', $ln=true, $stretch=0, $firstline=false, $firstblock=false, $maxh=0);
	}
}

function invoice_seller() {
	global $pdf, $invoice;

	$pdf->SetFont('arial', '', 10);
	$seller = '<b>'.trans('Seller:').'</b><br>';
	$tmp = $invoice['division_header'];
	$tmp = preg_split('/\r?\n/', $tmp);
	foreach ($tmp as $line)
		$seller .= $line.'<br>';
	$pdf->writeHTMLCell(80, 35, '', 60, $seller, 0, 1, 0, true, 'L');
}

function invoice_buyer() {
	global $pdf, $invoice;

	$buyer = '<b>'.trans('Purchaser:').'</b><br>';

	if ($invoice['post_name'] || $invoice['post_address']) {
		if ($invoice['post_name'])
			$buyer .= $invoice['post_name'].'<br>';
		else
			$buyer .= $invoice['name'].'<br>';
		$buyer .= $invoice['post_address'].'<br>';
		$buyer .= $invoice['post_zip'].' '.$invoice['post_city'].'<br>';
	} else {
		$buyer .= $invoice['name'].'<br>';
		$buyer .= $invoice['address'].'<br>';
		$buyer .= $invoice['zip'].' '.$invoice['city'].'<br>';
	}

	if ($invoice['division_countryid'] && $invoice['countryid'] && $invoice['division_countryid'] != $invoice['countryid'])
		$buyer .= trans($invoice['country']).'<br>';
	if ($invoice['ten'])
		$buyer .= trans('TEN').': '.$invoice['ten'].'<br>';

	$pin = '<b>'.trans('Customer ID: $a', sprintf('%04d',$invoice['customerid'])).'</b><br>';
	$pin .= '<b>PIN: '.sprintf('%04d', $invoice['customerpin']).'</b><br>';

	$pdf->SetFont('arial', '', 10);
	$pdf->writeHTMLCell(80, '', 105, 60, $buyer, 0, 1, 0, true, 'L');
	$pdf->SetFont('arial', 'B', 8);
	$pdf->writeHTMLCell('', '', 105, '', $pin, 0, 1, 0, true, 'L');
}

function invoice_data() {
	global $pdf, $invoice;

	/* print table */
	$pdf->writeHTMLCell('', '', '', 105, '', 0, 1, 0, true, 'L');
	$pdf->Table($header, $invoice);
}

function invoice_to_pay() {
	global $pdf, $invoice;

	$pdf->SetFont('arial', 'B', 14);
	if (isset($invoice['rebate']))
		$pdf->writeHTMLCell(0, 0, '', '', trans('To repay:').' '.moneyf($invoice['value']), 0, 1, 0, true, 'L');
	else
		$pdf->writeHTMLCell(0, 0, '', '', trans('To pay:').' '.moneyf($invoice['value']), 0, 1, 0, true, 'L');

	$pdf->SetFont('arial', '', 10);
	$pdf->writeHTMLCell(0, 6, '', '', trans('In words:').' '.trans('$a dollars $b cents', to_words(floor($invoice['value'])), to_words(round(($invoice['value']-floor($invoice['value']))*100))), 0, 1, 0, true, 'L');
}

function invoice_balance() {
	global $pdf, $invoice, $LMS;

	$pdf->SetFont('arial', '', 7);
	$pdf->writeHTMLCell(0, 0, '', '', trans('Your balance on date of invoice amounts:').' '.moneyf($LMS->GetCustomerBalance($invoice['customerid'], $invoice['cdate'])), 0, 1, 0, true, 'L');
}

function invoice_dates() {
	global $pdf, $invoice;

	$paytype = $invoice['paytype'];
	$pdf->SetFont('arial', '', 8);
	$pdf->Ln();
	if ($paytype != 8) {
		$deadline = trans('Deadline:').' <b>'.date("d.m.Y", $invoice['pdate']).' r.</b>';
		$pdf->writeHTMLCell(0, 0, '', '', $deadline, 0, 1, 0, true, 'L');
	}
	$payment = trans('Payment type:').' <b>'.$invoice['paytypename'].'</b>';
	$pdf->writeHTMLCell(0, 0, '', '', $payment, 0, 1, 0, true, 'L');
}

function invoice_expositor() {
	global $pdf, $invoice;

	$expositor = isset($invoice['user']) ? $invoice['user'] : $invoice['division_author'];
	$pdf->SetFont('arial', '', 8);
	$pdf->writeHTMLCell(0, 0, '', '', trans('Expositor:').' <b>'.$expositor.'</b>', 0, 1, 0, true, 'R');
}

function invoice_footnote() {
	global $pdf, $invoice;

	if (!empty($invoice['division_footer'])) {
		$pdf->Ln(25);
		$pdf->SetFont('arial', 'B', 10);
		$pdf->Write($h=0, trans('Infos:'), $link='', $fill=0, $align='L', $ln=true, $stretch=0, $firstline=false, $firstblock=false, $maxh=0);
		$tmp = $invoice['division_footer'];
		$tmp = preg_split('/\r?\n/', $tmp);
		$pdf->SetFont('arial', '', 6);
		foreach ($tmp as $line)
			$footnote .= $line.'<br>';
		$pdf->writeHTMLCell(0, 0, '', '', $footnote, 0, 1, 0, true, 'L');
	}
}

function invoice_body_standard()
{
	global $pdf, $invoice;

	invoice_date();
	invoice_title();
	invoice_seller();
	invoice_buyer();
	invoice_data();
	invoice_to_pay();
	invoice_balance();
	invoice_dates();
	invoice_expositor();
	invoice_footnote();
	$docnumber = docnumber($invoice['number'], $invoice['template'], $invoice['cdate']);
	$pdf->SetTitle(trans('Invoice No. $a', $docnumber));
	$pdf->SetAuthor($invoice['division_name']);
	$pdf->setBarcode($docnumber);

	/* setup your cert & key file */
	$cert = 'file://'.LIB_DIR.'/tcpdf/config/lms.cert';
	$key = 'file://'.LIB_DIR.'/tcpdf/config/lms.key';

	/* setup signature additional information */
	$info = array(
		'Name' => $invoice['division_name'],
		'Location' => trans('Invoices'),
		'Reason' => trans('Invoice No. $a', $docnumber),
		'ContactInfo' => $invoice['division_author']
	);

	/* set document digital signature & protection */
	if (file_exists($cert) && file_exists($key)) {
		$pdf->setSignature($cert, $key, 'lms-invoices', '', 1, $info);
		$pdf->setSignatureAppearance(13, 10, 50, 20);
	}
	$pdf->SetProtection(array('modify', 'copy', 'annot-forms', 'fill-forms', 'extract', 'assemble'), '', 'PASSWORD_CHANGEME', '1');
}

?>
