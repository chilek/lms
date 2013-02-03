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

include('class.php');
session_start();

if(!$_SESSION['uid'] || !$_GET['id'])
{
	die;
}

if($_SESSION['uid'] != $DB->GetOne('SELECT customerid FROM documents WHERE id=?', array($_GET['id'])))
{
	die;
}

if (strtolower($CONFIG['invoices']['type']) == 'pdf')
{
    include('invoice_pdf.php');
    die;
}

header('Content-Type: '.$LMS->CONFIG['invoices']['content_type']);
if($LMS->CONFIG['invoices']['attachment_name'] != '')
	header('Content-Disposition: attachment; filename='.$LMS->CONFIG['invoices']['attachment_name']);

$invoice = $LMS->GetInvoiceContent($_GET['id']);

$ntempl = docnumber($invoice['number'], $invoice['template'], $invoice['cdate']);
$layout['pagetitle'] = trans('Invoice No. $a', $ntempl);
$invoice['last'] = TRUE;
$SMARTY->assign('invoice',$invoice);
$SMARTY->display(SMARTY_TEMPLATES_DIR.'/clearheader.html');
$SMARTY->assign('type',trans('ORIGINAL'));
$SMARTY->display(SMARTY_TEMPLATES_DIR.'/'.$LMS->CONFIG['invoices']['template_file']);
$SMARTY->display(SMARTY_TEMPLATES_DIR.'/clearfooter.html');

?>
