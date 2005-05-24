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

if (strtolower($_CONFIG['invoices']['type']) == 'pdf')
{
    include('invoice_pdf.php');
    $SESSION->close();
    die;
}

header('Content-Type: '.$LMS->CONFIG['invoices']['content_type']);
if($LMS->CONFIG['invoices']['attachment_name'] != '')
	header('Content-Disposition: attachment; filename='.$LMS->CONFIG['invoices']['attachment_name']);

if($_GET['print'] == 'cached' && sizeof($_POST['marks']))
{
	$layout['pagetitle'] = trans('Invoices');
	$SMARTY->display('clearheader.html');
	foreach($_POST['marks'] as $markid => $junk)
		if($junk)
			$ids[] = $markid;

	if($_GET['cash'])
	{
		foreach($ids as $cashid)
		{
			if($invoiceid = $LMS->DB->GetOne('SELECT invoiceid FROM cash WHERE id = ?', array($cashid)))
				$idsx[] = $invoiceid;
		}
		$ids = array_unique($idsx);
	}
	
	sort($ids);
	$which = ($_GET['which'] != '' ? $_GET['which'] : trans('ORIGINAL+COPY'));
	
	$count = (strstr($which, '+') ? sizeof($ids)*2 : sizeof($ids));
	$i=0;
	
	foreach($ids as $idx => $invoiceid)
	{
		echo '<PRE>';
		
		$invoice = $LMS->GetInvoiceContent($invoiceid);
		$invoice['serviceaddr'] = $LMS->GetUserServiceAddress($invoice['customerid']);
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
	$SMARTY->display('clearheader.html');
	$which = ($_GET['which'] != '' ? $_GET['which'] : trans('ORIGINAL+COPY'));
	
	$ids = $LMS->DB->GetCol('SELECT id FROM invoices 
				WHERE cdate > ? AND cdate < ?'
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
//		echo '<PRE>';
		$invoice = $LMS->GetInvoiceContent($invoiceid);
		$invoice['serviceaddr'] = $LMS->GetUserServiceAddress($invoice['customerid']);
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
	$ntempl = $LMS->CONFIG['invoices']['number_template'];
	$ntempl = str_replace('%N',$invoice['number'],$ntempl);
	$ntempl = str_replace('%M',$invoice['month'],$ntempl);
	$ntempl = str_replace('%Y',$invoice['year'],$ntempl);
	$layout['pagetitle'] = trans('Invoice No. $0', $ntempl);
	$invoice['last'] = TRUE;
	$invoice['serviceaddr'] = $LMS->GetUserServiceAddress($invoice['customerid']);
	$SMARTY->assign('invoice',$invoice);
	$SMARTY->display('clearheader.html');
	$SMARTY->assign('type',trans('ORIGINAL'));
	$SMARTY->display($LMS->CONFIG['invoices']['template_file']);
	$SMARTY->display('clearfooter.html');
}
elseif($invoice = $LMS->GetInvoiceContent($_GET['id']))
{
	$ntempl = $LMS->CONFIG['invoices']['number_template'];
	$ntempl = str_replace('%N',$invoice['number'],$ntempl);
	$ntempl = str_replace('%M',$invoice['month'],$ntempl);
	$ntempl = str_replace('%Y',$invoice['year'],$ntempl);
	$layout['pagetitle'] = trans('Invoice No. $0', $ntempl);
	$invoice['serviceaddr'] = $LMS->GetUserServiceAddress($invoice['customerid']);
	$SMARTY->assign('invoice',$invoice);
	$SMARTY->display('clearheader.html');
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
