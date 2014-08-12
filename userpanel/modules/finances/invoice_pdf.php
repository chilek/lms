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
	global $invoice,$pdf;

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
			if (file_exists($template))
				require($template);
			else //go to LMS modules directory
				require(MODULES_DIR . '/' . $template);
	 }

	if (!isset($invoice['last']))
		new_page();
}

global $pdf;

$pdf_type = ConfigHelper::getConfig('invoices.pdf_type', 'tcpdf');
if (!in_array($pdf_type, array('ezpdf', 'tcpdf')))
	$pdf_type = 'tcpdf';
require_once(LIB_DIR . '/' . $pdf_type . '.php');
require_once(MODULES_DIR . '/invoice_' . $pdf_type . '.inc.php');

// handle multi-invoice print
if(!empty($_POST['inv']))
{
	$pdf = init_pdf('A4', 'portrait', trans('Invoices'));

	$count = count($_POST['inv']);
        $i = 0;
	
	foreach (array_keys($_POST['inv']) as $key)
	{
		$invoice = $LMS->GetInvoiceContent(intval($key));
		$invoice['type'] = $type;
		$i++;

		if($invoice['customerid'] != $SESSION->id)
		{
    			continue;
		}
		
		if($i == $count)
			$invoice['last'] = TRUE;
		invoice_body();
	}
	
	close_pdf($pdf);
	die;
}

$invoice = $LMS->GetInvoiceContent($_GET['id']);

if($invoice['customerid'] != $SESSION->id)
{
        die;
}

$number = docnumber($invoice['number'], $invoice['template'], $invoice['cdate']);

if(!isset($invoice['invoice']))
        $title = trans('Invoice No. $a', $number);
else
        $title = trans('Credit Note No. $a', $number);

$pdf = init_pdf('A4', 'portrait', $title);

$invoice['last'] = TRUE;
$invoice['type'] = $type;

invoice_body();

close_pdf($pdf);

?>
