<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2013 LMS Developers
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

	if(!empty($_POST['marks']))
		foreach($_POST['marks'] as $id => $mark)
			$ilm[$id] = $mark;
	if(sizeof($ilm))
		foreach($ilm as $mark)
			$ids[] = intval($mark);

	if(empty($ids))
	{
		$SESSION->close();
		die;
	}

	$layout['pagetitle'] = trans('Invoices');
	$SMARTY->display('invoiceheader.html');
	
	if(isset($_GET['cash']))
	{
		$ids = $DB->GetCol('SELECT DISTINCT docid
                        FROM cash, documents
		        WHERE docid = documents.id AND (documents.type = ? OR documents.type = ?)
                                AND cash.id IN ('.implode(',', $ids).')
                        ORDER BY docid',
                        array(DOC_INVOICE, DOC_CNOTE));
	}
	
	if(!empty($_GET['original'])) $which[] = trans('ORIGINAL');
	if(!empty($_GET['copy'])) $which[] = trans('COPY');
	if(!empty($_GET['duplicate'])) $which[] = trans('DUPLICATE');

	if(!sizeof($which)) $which[] = trans('ORIGINAL');

	$count = sizeof($ids) * sizeof($which);
	$i=0;
	foreach($ids as $idx => $invoiceid)
	{
		$invoice = $LMS->GetInvoiceContent($invoiceid);

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

	$offset = intval(date('Z'));
	$ids = $DB->GetCol('SELECT d.id FROM documents d
		WHERE d.cdate >= ? AND d.cdate <= ? AND (d.type = ? OR d.type = ?)'
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
		.' ORDER BY CEIL(d.cdate/86400), d.id',
		array(intval($_GET['from']) - $offset, intval($_GET['to']) - $offset, DOC_INVOICE, DOC_CNOTE));

	if(!$ids)
	{
		$SESSION->close();
		die;
	}

	if(!empty($_GET['original'])) $which[] = trans('ORIGINAL');
	if(!empty($_GET['copy'])) $which[] = trans('COPY');
	if(!empty($_GET['duplicate'])) $which[] = trans('DUPLICATE');

    if(!sizeof($which)) $which[] = trans('ORIGINAL');

	$count = sizeof($ids) * sizeof($which);
	$i=0;

	$SMARTY->display('invoiceheader.html');

	foreach($ids as $idx => $invoiceid)
	{
		$invoice = $LMS->GetInvoiceContent($invoiceid);

		foreach($which as $type)
		{
			$SMARTY->assign('type',$type);
			$SMARTY->assign('invoice',$invoice);
			if(isset($invoice['invoice']))
				$SMARTY->display($CONFIG['invoices']['cnote_template_file']);
			else
				$SMARTY->display($CONFIG['invoices']['template_file']);
		}
	}
	$SMARTY->display('clearfooter.html');
}
elseif($invoice = $LMS->GetInvoiceContent($_GET['id']))
{
	$number = docnumber($invoice['number'], $invoice['template'], $invoice['cdate']);
	if(!isset($invoice['invoice']))
		$layout['pagetitle'] = trans('Invoice No. $a', $number);
	else
		$layout['pagetitle'] = trans('Credit Note No. $a', $number);

	$which = array();

	if(!empty($_GET['original'])) $which[] = trans('ORIGINAL');
	if(!empty($_GET['copy'])) $which[] = trans('COPY');
	if(!empty($_GET['duplicate'])) $which[] = trans('DUPLICATE');

	if(!sizeof($which))
        {
	        $tmp = explode(',', $CONFIG['invoices']['default_printpage']);
	        foreach($tmp as $t)
			if(trim($t) == 'original') $which[] = trans('ORIGINAL');
			elseif(trim($t) == 'copy') $which[] = trans('COPY');
			elseif(trim($t) == 'duplicate') $which[] = trans('DUPLICATE');
		
		if(!sizeof($which)) $which[] = trans('ORIGINAL');
	}
	
	$count = sizeof($which);
	$i = 0;
	
	$SMARTY->display('invoiceheader.html');
	foreach($which as $type)
	{
		$i++;
		if($i == $count) $invoice['last'] = TRUE;
		$SMARTY->assign('invoice',$invoice);
		$SMARTY->assign('duplicate',$type==trans('DUPLICATE') ? TRUE : FALSE);
		$SMARTY->assign('type',$type);

		if(isset($invoice['invoice']))
			$SMARTY->display($CONFIG['invoices']['cnote_template_file']);
		else
			$SMARTY->display($CONFIG['invoices']['template_file']);
	}
	$SMARTY->display('clearfooter.html');
}
else
{
	$SESSION->redirect('?m=invoicelist');
}

?>
