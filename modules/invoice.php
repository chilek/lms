<?php

/*
 * LMS version 1.7-cvs
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

header('Content-Type: '.$LMS->CONFIG['invoices']['content_type']);
if($LMS->CONFIG['invoices']['attachment_name'] != '')
	header('Content-Disposition: attachment; filename='.$LMS->CONFIG['invoices']['attachment_name']);

$SMARTY->assign('css', file('img/style_print.css')); 

if($_GET['print'] == 'cached' && sizeof($_POST['marks']))
{
	$layout['pagetitle'] = trans('Invoices');
	$SMARTY->display('invoiceheader.html');
	foreach($_POST['marks'] as $markid => $junk)
		if($junk)
			$ids[] = $markid;

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
	$which = ($_GET['which'] != '' ? $_GET['which'] : trans('ORIGINAL+COPY'));
	
	$count = (strstr($which, '+') ? sizeof($ids)*2 : sizeof($ids));
	$i=0;
	foreach($ids as $idx => $invoiceid)
	{
		$invoice = $LMS->GetInvoiceContent($invoiceid);
		$invoice['serviceaddr'] = $LMS->GetCustomerServiceAddress($invoice['customerid']);
		foreach(split('\+', $which) as $type)
		{
			$i++;
			if($i == $count) $invoice['last'] = TRUE;
			$SMARTY->assign('type',$type);
			$SMARTY->assign('invoice',$invoice);
			$SMARTY->display($LMS->CONFIG['invoices']['template_file']);
		}
	}
	$SMARTY->display('clearfooter.html');
}
elseif($_GET['fetchallinvoices'])
{
	$layout['pagetitle'] = trans('Invoices');
	$SMARTY->display('invoiceheader.html');
	$which = ($_GET['which'] != '' ? $_GET['which'] : trans('ORIGINAL+COPY'));
	
	$ids = $DB->GetCol('SELECT id FROM documents 
				WHERE cdate > ? AND cdate < ? AND type = 1'
				.($_GET['customerid'] ? ' AND customerid = '.$_GET['customerid'] : '')
				.' ORDER BY cdate',
				array($_GET['from'], $_GET['to']));
	if(!$ids)
	{
		$SESSION->close();
		die;
	}

	$count = (strstr($which, '+') ? sizeof($ids)*2 : sizeof($ids));
	$i=0;

	foreach($ids as $idx => $invoiceid)
	{
		$invoice = $LMS->GetInvoiceContent($invoiceid);
		$invoice['serviceaddr'] = $LMS->GetCustomerServiceAddress($invoice['customerid']);
		foreach(split('\+', $which) as $type)
		{
			$i++;
			if($i == $count) $invoice['last'] = TRUE;
			$SMARTY->assign('type',$type);
			$SMARTY->assign('invoice',$invoice);
			$SMARTY->display($LMS->CONFIG['invoices']['template_file']);
		}
	}
	$SMARTY->display('clearfooter.html');
}
elseif($_GET['fetchsingle'])
{
	$invoice = $LMS->GetInvoiceContent($_GET['id']);
	$number = docnumber($invoice['number'], $invoice['template'], $invoice['cdate']);
	$layout['pagetitle'] = trans('Invoice No. $0', $number);
	$invoice['last'] = TRUE;
	$invoice['serviceaddr'] = $LMS->GetCustomerServiceAddress($invoice['customerid']);
	$SMARTY->assign('invoice',$invoice);
	$SMARTY->display('invoiceheader.html');
	$SMARTY->assign('type',trans('ORIGINAL'));
	$SMARTY->display($LMS->CONFIG['invoices']['template_file']);
	$SMARTY->display('clearfooter.html');
}
elseif($invoice = $LMS->GetInvoiceContent($_GET['id']))
{
	$number = docnumber($invoice['number'], $invoice['template'], $invoice['cdate']);
	$layout['pagetitle'] = trans('Invoice No. $0', $number);
	$invoice['serviceaddr'] = $LMS->GetCustomerServiceAddress($invoice['customerid']);
	$SMARTY->assign('invoice',$invoice);
	$SMARTY->display('invoiceheader.html');
	$SMARTY->assign('type',trans('ORIGINAL'));
	$SMARTY->display($LMS->CONFIG['invoices']['template_file']);
	$SMARTY->assign('type',trans('COPY'));
	$invoice['last'] = TRUE;
	$SMARTY->assign('invoice',$invoice);
	$SMARTY->display($LMS->CONFIG['invoices']['template_file']);
	$SMARTY->display('clearfooter.html');
}
else
{
	$SESSION->redirect('?m=invoicelist');
}

?>
