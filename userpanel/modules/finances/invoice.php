<?php

/*
 *  LMS version 1.11-git
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

global $LMS,$SESSION,$SMARTY,$invoice, $layout, $type;

$type = ConfigHelper::checkConfig('userpanel.invoice_duplicate') ? trans('DUPLICATE') : trans('ORIGINAL');

if(strtolower(ConfigHelper::getConfig('invoices.type')) == 'pdf')
{
    include('invoice_pdf.php');
    die;
}

header('Content-Type: '.ConfigHelper::getConfig('invoices.content_type'));
if (ConfigHelper::getConfig('invoices.attachment_name') != '')
	header('Content-Disposition: attachment; filename='.ConfigHelper::getConfig('invoices.attachment_name'));

$SMARTY->assign('css', file(ConfigHelper::getConfig('directories.sys_dir').'/img/style_print.css')); 

// use LMS templates directory
define('SMARTY_TEMPLATES_DIR', ConfigHelper::getConfig('directories.smarty_templates_dir', ConfigHelper::getConfig('directories.sys_dir').'/templates'));
$SMARTY->setTemplateDir(null);
$custom_templates_dir = ConfigHelper::getConfig('phpui.custom_templates_dir');
if (!empty($custom_templates_dir) && file_exists(SMARTY_TEMPLATES_DIR . '/' . $custom_templates_dir)
	&& !is_file(SMARTY_TEMPLATES_DIR . '/' . $custom_templates_dir))
	$SMARTY->AddTemplateDir(SMARTY_TEMPLATES_DIR . '/' . $custom_templates_dir);
$SMARTY->AddTemplateDir(
	array(
		SMARTY_TEMPLATES_DIR . '/default',
		SMARTY_TEMPLATES_DIR,
	)
);

// handle multi-invoices print
if(!empty($_POST['inv']))
{
	$layout['pagetitle'] = trans('Invoices');
        $SMARTY->display('invoiceheader.html');

	$count = count($_POST['inv']);
	$i = 0;
	foreach (array_keys($_POST['inv']) as $key)
	{
		$invoice = $LMS->GetInvoiceContent(intval($key));
		$i++;
		if($invoice['customerid'] != $SESSION->id)
		{
			continue;
		}

		if($i == $count)
			$invoice['last'] = TRUE;
		$invoice['type'] = $type;

		$SMARTY->assign('invoice', $invoice);
		$SMARTY->assign('type', $type);

		if(isset($invoice['invoice']))
			$SMARTY->display(ConfigHelper::getConfig('invoices.cnote_template_file'));
		else
			$SMARTY->display(ConfigHelper::getConfig('invoices.template_file'));
	}

	$SMARTY->display('clearfooter.html');
	die;
}

$invoice = $LMS->GetInvoiceContent($_GET['id']);

if($invoice['customerid'] != $SESSION->id)
{
	die;
}

$invoice['last'] = TRUE;
$invoice['type'] = $type;

$number = docnumber($invoice['number'], $invoice['template'], $invoice['cdate']);

if(!isset($invoice['invoice']))
	$layout['pagetitle'] = trans('Invoice No. $a', $number);
else
	$layout['pagetitle'] = trans('Credit Note No. $a', $number);

$SMARTY->display('invoiceheader.html');

$SMARTY->assign('invoice', $invoice);
$SMARTY->assign('type', $type);

if(isset($invoice['invoice']))
	$SMARTY->display(ConfigHelper::getConfig('invoices.cnote_template_file'));
else
	$SMARTY->display(ConfigHelper::getConfig('invoices.template_file'));

$SMARTY->display('clearfooter.html');

?>
