<?php

/*
 * LMS version 1.9-cvs
 *
 *  (C) Copyright 2001-2005 LMS Developers
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

if (strtolower($CONFIG['invoices']['type']) == 'pdf')
{
    include('invoice_pdf.php');
    $SESSION->close();
    die;
}

header('Content-Type: '.$CONFIG['invoices']['content_type']);
if($CONFIG['invoices']['attachment_name'] != '')
	header('Content-Disposition: attachment; filename='.$CONFIG['invoices']['attachment_name']);

$SMARTY->assign('css', file('img/style_print.css')); 

if($_GET['print'] == 'cached')
{
	$SESSION->restore('ilm', $ilm);
	$SESSION->remove('ilm');

	if(sizeof($_POST['marks']))
		foreach($_POST['marks'] as $id => $mark)
			$ilm[$id] = $mark;
	if(sizeof($ilm))
		foreach($ilm as $mark)
			$ids[] = $mark;

	if(!$ids)
	{
		$SESSION->close();
		die;
	}

	$layout['pagetitle'] = trans('Invoices');
	$SMARTY->display('invoiceheader.html');
	
	if($_GET['cash'])
	{
		foreach($ids as $cashid)
		{
			// we need to check if that document is an invoice
			if($invoiceid = $DB->GetOne('SELECT docid FROM cash, documents WHERE docid = documents.id AND documents.type=1 AND cash.id = ?', array($cashid)))
				$idsx[] = $invoiceid;
		}
		$ids = array_unique((array)$idsx);
	}

	sort($ids);
	
	if($_GET['original']) $which[] = trans('ORIGINAL');
	if($_GET['copy']) $which[] = trans('COPY');
	if($_GET['duplicate']) $which[] = trans('DUPLICATE');

	if(!sizeof($which)) $which[] = trans('ORIGINAL');
	
	$count = sizeof($ids) * sizeof($which);
	$i=0;
	foreach($ids as $idx => $invoiceid)
	{
		$invoice = $LMS->GetInvoiceContent($invoiceid);
		$invoice['serviceaddr'] = $LMS->GetCustomerServiceAddress($invoice['customerid']);
		foreach($which as $type)
		{
			$i++;
			if($i == $count) $invoice['last'] = TRUE;
			$SMARTY->assign('type',$type);
			$SMARTY->assign('duplicate',$type==trans('DUPLICATE') ? TRUE : FALSE);
			$SMARTY->assign('invoice',$invoice);
			if($invoice['invoice'])
				$SMARTY->display($CONFIG['invoices']['cnote_template_file']);
			else
				$SMARTY->display($CONFIG['invoices']['template_file']);
		}
	}
	$SMARTY->display('clearfooter.html');
}
elseif($_GET['fetchallinvoices'])
{
	$layout['pagetitle'] = trans('Invoices');

	$ids = $DB->GetCol('SELECT id FROM documents 
				WHERE cdate > ? AND cdate < ? AND type = 1'
				.($_GET['customerid'] ? ' AND customerid = '.$_GET['customerid'] : '')
				.' ORDER BY customerid',
				array($_GET['from'], $_GET['to']));
	if(!$ids)
	{
		$SESSION->close();
		die;
	}

	if($_GET['original']) $which[] = trans('ORIGINAL');
	if($_GET['copy']) $which[] = trans('COPY');
	if($_GET['duplicate']) $which[] = trans('DUPLICATE');
	
	if(!sizeof($which)) $which[] = trans('ORIGINAL');

	$count = sizeof($ids) * sizeof($which);
	$i=0;

	$SMARTY->display('invoiceheader.html');

	foreach($ids as $idx => $invoiceid)
	{
		$invoice = $LMS->GetInvoiceContent($invoiceid);
		$invoice['serviceaddr'] = $LMS->GetCustomerServiceAddress($invoice['customerid']);
		foreach($which as $type)
		{
			$i++;
			if($i == $count) $invoice['last'] = TRUE;
			$SMARTY->assign('type',$type);
			$SMARTY->assign('duplicate',$type==trans('DUPLICATE') ? TRUE : FALSE);
			$SMARTY->assign('invoice',$invoice);
			if($invoice['invoice'])
				$SMARTY->display($CONFIG['invoices']['cnote_template_file']);
			else
				$SMARTY->display($CONFIG['invoices']['template_file']);
		}
	}
	$SMARTY->display('clearfooter.html');
}
elseif($_GET['fetchsingle'])
{
	$invoice = $LMS->GetInvoiceContent($_GET['id']);
	$number = docnumber($invoice['number'], $invoice['template'], $invoice['cdate']);
	if($invoice['doctype']==DOC_INVOICE)
		$layout['pagetitle'] = trans('Invoice No. $0', $number);
	else
		$layout['pagetitle'] = trans('Credit Note No. $0', $number);
	$invoice['last'] = TRUE;
	$invoice['serviceaddr'] = $LMS->GetCustomerServiceAddress($invoice['customerid']);
	$SMARTY->assign('invoice',$invoice);
	$SMARTY->display('invoiceheader.html');
	$SMARTY->assign('type',trans('ORIGINAL'));
	if($invoice['invoice'])
		$SMARTY->display($CONFIG['invoices']['cnote_template_file']);
	else
		$SMARTY->display($CONFIG['invoices']['template_file']);
	$SMARTY->display('clearfooter.html');
}
elseif($invoice = $LMS->GetInvoiceContent($_GET['id']))
{
	$number = docnumber($invoice['number'], $invoice['template'], $invoice['cdate']);
	if($invoice['doctype']==DOC_INVOICE)
		$layout['pagetitle'] = trans('Invoice No. $0', $number);
	else
		$layout['pagetitle'] = trans('Credit Note No. $0', $number);
	$invoice['serviceaddr'] = $LMS->GetCustomerServiceAddress($invoice['customerid']);
	$SMARTY->assign('invoice',$invoice);
	$SMARTY->display('invoiceheader.html');
	$SMARTY->assign('type',trans('ORIGINAL'));
	if($invoice['invoice'])
		$SMARTY->display($CONFIG['invoices']['cnote_template_file']);
	else
		$SMARTY->display($CONFIG['invoices']['template_file']);
	$SMARTY->assign('type',trans('COPY'));
	$invoice['last'] = TRUE;
	$SMARTY->assign('invoice',$invoice);
	if($invoice['invoice'])
		$SMARTY->display($CONFIG['invoices']['cnote_template_file']);
	else
		$SMARTY->display($CONFIG['invoices']['template_file']);
	$SMARTY->display('clearfooter.html');
}
else
{
	$SESSION->redirect('?m=invoicelist');
}

?>
