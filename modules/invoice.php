<?php

/*
 * LMS version 1.1-cvs
 *
 *  (C) Copyright 2001-2003 LMS Developers
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

header('Content-Type: '.$LMS->CONFIG['invoices']['content_type']);
if($LMS->CONFIG['invoices']['attachment_name'] != '')
	header('Content-Disposition: attachment; filename='.$LMS->CONFIG['invoices']['attachment_name']);
				

if($_GET['print'] == 'cached' && sizeof($_SESSION['ilp_marks']))
{
	$layout['pagetitle'] = 'Faktury VAT';
	$SMARTY->assign('layout',$layout);
	$SMARTY->display('clearheader.html');
	foreach($_SESSION['ilp_marks'] as $markid => $junk)
		if($junk)
			$ids[] = $markid;
	sort($ids);
	foreach($ids as $idx => $invoiceid)
	{
		$invoice = $LMS->GetInvoiceContent($invoiceid);
		$SMARTY->assign('type','ORYGINA£');
		$SMARTY->assign('invoice',$invoice);
		$SMARTY->display($LMS->CONFIG['invoices']['template_file'].'.html');
		$SMARTY->assign('type','KOPIA');
		if(! $ids[$idx+1])
		{
			$invoice['last'] = TRUE;
			$SMARTY->assign('invoice',$invoice);
		}
		$SMARTY->display($LMS->CONFIG['invoices']['template_file'].'.html');
	}
	$SMARTY->display('clearfooter.html');
}
elseif($invoice = $LMS->GetInvoiceContent($_GET['id']))
{
	$ntempl = $LMS->CONFIG['invoices']['number_template'];
	$ntempl = str_replace('%N',$invoice['number'],$ntempl);
	$ntempl = str_replace('%M',$invoice['month'],$ntempl);
	$ntempl = str_replace('%Y',$invoice['year'],$ntempl);
	$layout['pagetitle'] = 'Faktura VAT nr '.$ntempl;	
	$SMARTY->assign('layout',$layout);
	$SMARTY->assign('invoice',$invoice);
	$SMARTY->display('clearheader.html');
	$SMARTY->assign('type','ORYGINA£');
	$SMARTY->display($LMS->CONFIG['invoices']['template_file'].'.html');
	$SMARTY->assign('type','KOPIA');
	$invoice['last'] = TRUE;
	$SMARTY->assign('invoice',$invoice);
	$SMARTY->display($LMS->CONFIG['invoices']['template_file'].'.html');
	$SMARTY->display('clearfooter.html');
}
else
{
	header('Location: ?m=invoicelist');
	die;
}
?>
