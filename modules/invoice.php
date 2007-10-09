<?php

/*
 * LMS version 1.11-cvs
 *
 *  (C) Copyright 2001-2007 LMS Developers
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

if(strtolower($CONFIG['invoices']['type']) == 'pdf')
{
    include('invoice_pdf.php');
    $SESSION->close();
    die;
}

header('Content-Type: '.$CONFIG['invoices']['content_type']);
if(!empty($CONFIG['invoices']['attachment_name']))
	header('Content-Disposition: attachment; filename='.$CONFIG['invoices']['attachment_name']);

$SMARTY->assign('css', file('img/style_print.css')); 

if(isset($_GET['print']) && $_GET['print'] == 'cached')
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
			// we need to check if that document is an invoice or credit note
			if($invoiceid = $DB->GetOne('SELECT docid FROM cash, documents WHERE docid = documents.id AND (documents.type=? OR documents.type=?) AND cash.id = ?', array(DOC_INVOICE, DOC_CNOTE, $cashid)))
				$idsx[] = $invoiceid;
		}
		$ids = array_unique((array)$idsx);
	}

	sort($ids);
	
	if(isset($_GET['original'])) $which[] = trans('ORIGINAL');
	if(isset($_GET['copy'])) $which[] = trans('COPY');
	if(isset($_GET['duplicate'])) $which[] = trans('DUPLICATE');

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
			if(isset($invoice['invoice']))
				$SMARTY->display($CONFIG['invoices']['cnote_template_file']);
			else
				$SMARTY->display($CONFIG['invoices']['template_file']);
		}
	}
	$SMARTY->display('clearfooter.html');
}
elseif(isset($_GET['fetchallinvoices']))
{
	$layout['pagetitle'] = trans('Invoices');

	$ids = $DB->GetCol('SELECT d.id FROM documents d
				WHERE d.cdate >= ? AND d.cdate <= ? AND (d.type = ? OR d.type = ?)'
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
				.' ORDER BY CEIL(d.cdate/86400), d.id',
				array($_GET['from'], $_GET['to'], DOC_INVOICE, DOC_CNOTE));
	if(!$ids)
	{
		$SESSION->close();
		die;
	}

	if(isset($_GET['original'])) $which[] = trans('ORIGINAL');
	if(isset($_GET['copy'])) $which[] = trans('COPY');
	if(isset($_GET['duplicate'])) $which[] = trans('DUPLICATE');
	
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
			if(isset($invoice['invoice']))
				$SMARTY->display($CONFIG['invoices']['cnote_template_file']);
			else
				$SMARTY->display($CONFIG['invoices']['template_file']);
		}
	}
	$SMARTY->display('clearfooter.html');
}
elseif(isset($_GET['fetchsingle']))
{
	$invoice = $LMS->GetInvoiceContent($_GET['id']);
	$number = docnumber($invoice['number'], $invoice['template'], $invoice['cdate']);
	if(!isset($invoice['invoice']))
		$layout['pagetitle'] = trans('Invoice No. $0', $number);
	else
		$layout['pagetitle'] = trans('Credit Note No. $0', $number);
	$invoice['last'] = TRUE;
	$invoice['serviceaddr'] = $LMS->GetCustomerServiceAddress($invoice['customerid']);
	$SMARTY->assign('invoice',$invoice);
	$SMARTY->display('invoiceheader.html');
	$SMARTY->assign('type',trans('ORIGINAL'));
	if(isset($invoice['invoice']))
		$SMARTY->display($CONFIG['invoices']['cnote_template_file']);
	else
		$SMARTY->display($CONFIG['invoices']['template_file']);
	$SMARTY->display('clearfooter.html');
}
elseif($invoice = $LMS->GetInvoiceContent($_GET['id']))
{
	$number = docnumber($invoice['number'], $invoice['template'], $invoice['cdate']);
	if(!isset($invoice['invoice']))
		$layout['pagetitle'] = trans('Invoice No. $0', $number);
	else
		$layout['pagetitle'] = trans('Credit Note No. $0', $number);
	$invoice['serviceaddr'] = $LMS->GetCustomerServiceAddress($invoice['customerid']);
	$SMARTY->assign('invoice',$invoice);
	$SMARTY->display('invoiceheader.html');
	$SMARTY->assign('type',trans('ORIGINAL'));
	if(isset($invoice['invoice']))
		$SMARTY->display($CONFIG['invoices']['cnote_template_file']);
	else
		$SMARTY->display($CONFIG['invoices']['template_file']);
	$SMARTY->assign('type',trans('COPY'));
	$invoice['last'] = TRUE;
	$SMARTY->assign('invoice',$invoice);
	if(isset($invoice['invoice']))
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
