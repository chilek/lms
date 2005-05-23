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

if ((isset($_GET['id'])) && ($_GET['action']=='edit'))
{
    $invoice = $LMS->GetInvoiceContent($_GET['id']);

    $SESSION->remove('invoicecontents');
    $SESSION->remove('invoicecustomer');

    foreach ($invoice['content'] as $item) {
	$i++;
        $nitem['tariffid']	= $item['tariffid'];
	$nitem['name']		= $item['description'];
        $nitem['ttariffid']	= $item['tariffid'];
	$nitem['pkwiu']		= $item['pkwiu'];
        $nitem['count']		= str_replace(',','.',$item['count']);
	$nitem['jm']		= str_replace(',','.',$item['content']);
        $nitem['valuenetto']	= str_replace(',','.',$item['basevalue']);
	$nitem['taxvalue']	= str_replace(',','.',$item['taxvalue']);
        $nitem['valuebrutto']	= str_replace(',','.',$item['value']);
	$nitem['s_valuenetto']	= str_replace(',','.',$item['totalbase']);
        $nitem['s_valuebrutto']	= str_replace(',','.',$item['total']);
	$nitem['posuid']	= $i;
	$SESSION->restore('invoicecontents', $invoicecontents);
	$invoicecontents[] = $nitem;
	$SESSION->save('invoicecontents', $invoicecontents);
    }
    $SESSION->save('invoicecustomer', $LMS->GetUser($invoice['customerid']));
    $invoice['oldcdate'] = $invoice['cdate'];
    $SESSION->save('invoice', $invoice);
    $SESSION->save('invoiceid', $invoice['id']);
}

$users = $LMS->GetUserNames();
$tariffs = $LMS->GetTariffs();
$SESSION->restore('invoicecontents', $contents);
$SESSION->restore('invoicecustomer', $customer);
$SESSION->restore('invoice', $invoice);
$SESSION->restore('invoiceediterror', $error);
$itemdata = r_trim($_POST);

$ntempl = $LMS->CONFIG['invoices']['number_template'];
$ntempl = str_replace('%N', $invoice['number'], $ntempl);
$ntempl = str_replace('%M', date('m',$invoice['oldcdate']), $ntempl);
$ntempl = str_replace('%Y', date('Y',$invoice['oldcdate']), $ntempl);

$layout['pagetitle'] = trans('Invoice Edit: $0', $ntempl);

if($_GET['customerid'] != '' && $LMS->UserExists($_GET['customerid']))
	$_GET['action'] = 'setcustomer';

switch($_GET['action'])
{
	case 'additem':
		$itemdata = r_trim($_POST);
		foreach(array('count', 'valuenetto', 'valuebrutto') as $key)
			$itemdata[$key] = round((float) str_replace(',','.',$itemdata[$key]),2);
		if ($itemdata['taxvalue'] != '')
			$itemdata['taxvalue'] = round((float) str_replace(',','.',$itemdata['taxvalue']),2);
		if($itemdata['count'] > 0 && $itemdata['name'] != '')
		{
			$taxvalue = $itemdata['taxvalue'];
			if ($taxvalue == '')
				$taxvalue = 0;
			if($taxvalue < 0 || $taxvalue > 100)
				$error['taxvalue'] = trans('Incorrect tax value!');
			if($itemdata['valuenetto'] != 0)
				$itemdata['valuebrutto'] = round($itemdata['valuenetto'] * ($taxvalue / 100 + 1),2);
			elseif($itemdata['valuebrutto'] != 0)
				$itemdata['valuenetto'] = round($itemdata['valuebrutto'] / ($taxvalue + 100) * 100, 2);
			
			// str_replace here is needed because of bug in some PHP versions (4.3.10)
			$itemdata['s_valuenetto'] = str_replace(',','.',$itemdata['valuenetto'] * $itemdata['count']);
			$itemdata['s_valuebrutto'] = str_replace(',','.',$itemdata['valuebrutto'] * $itemdata['count']);
			$itemdata['valuenetto'] = str_replace(',','.',$itemdata['valuenetto']);
			$itemdata['valuebrutto'] = str_replace(',','.',$itemdata['valuebrutto']);
			$itemdata['taxvalue'] = str_replace(',','.',$itemdata['taxvalue']);
			$itemdata['count'] = str_replace(',','.',$itemdata['count']);
			$itemdata['posuid'] = (string) getmicrotime();
			$contents[] = $itemdata;
		}
	break;

	case 'clear':
		unset($contents);
		unset($customer);
		unset($invoice);
	break;

	case 'deletepos':
		if(sizeof($contents))
			foreach($contents as $idx => $row)
				if($row['posuid'] == $_GET['posuid']) 
					unset($contents[$idx]);
	break;

	case 'setcustomer':
		
		$olddate = $invoice['oldcdate'];
		unset($invoice); 
		unset($customer);
		unset($error);
		
		if($invoice = $_POST['invoice'])
			foreach($invoice as $key => $val)
				$invoice[$key] = $val;
		
		$invoice['paytime'] = sprintf('%d', $invoice['paytime']);
		
		$invoice['oldcdate'] = $olddate;
		
		if($invoice['paytime'] < 0)
			$invoice['paytime'] = 14;

		if($invoice['cdate']) // && !$invoice['cdatewarning'])
		{
			list($year, $month, $day) = split('/',$invoice['cdate']);
			if(checkdate($month, $day, $year))
			{
				$invoice['cdate'] = mktime(date('G',time()),date('i',time()),date('s',time()),$month,$day,$year);
				$invoice['month'] = date('m', $invoice['cdate']);
				$invoice['year'] = date('Y', $invoice['cdate']);
				
				$oldmonth = date('m', $olddate);
				$oldyear = date('Y', $olddate);
		    		
				if((($invoice['month'] != $oldmonth || $invoice['year'] != $oldyear) && $LMS->CONFIG['invoices']['monthly_numbering']) ||
				    ($invoice['year'] != $oldyear && !$LMS->CONFIG['invoices']['monthly_numbering']))
				{
					$error['cdate'] = trans('Specified date conflicts with invoice number!');
				}		
			}
			else
				$error['cdate'] = trans('Incorrect date format!');
		}
		
		$invoice['customerid'] = $_POST['customerid'];
		
		if(!$error)
			if($LMS->UserExists(($_GET['customerid'] != '' ? $_GET['customerid'] : $_POST['user'])))
				$customer = $LMS->GetUser(($_GET['customerid'] != '' ? $_GET['customerid'] : $_POST['user']));
	break;

	case 'save':

		if($contents && $customer)
		{
			$SESSION->restore('invoiceid', $invoice['id']);
			$LMS->InvoiceUpdate(array('customer' => $customer, 'contents' => $contents, 'invoice' => $invoice));
			$SESSION->redirect('?m=invoice&id='.$invoice['id']);
		}
	break;

	case 'invoicedel':
	    $LMS->InvoiceDelete($_GET['id']);
	    $SESSION->redirect('?m=invoicelist');
}

if($invoice['paytype'] == '')
	$invoice['paytype'] = trans('CASH');

$SESSION->save('invoice', $invoice);
$SESSION->save('invoicecontents', $contents);
$SESSION->save('invoicecustomer', $customer);
$SESSION->save('invoiceediterror', $error);

if($_GET['action'] != '')
{
	// redirect, ¿eby refreshem nie spierdoliæ faktury
	$SESSION->redirect('?m=invoiceedit');
}

$SMARTY->assign('error', $error);
$SMARTY->assign('contents', $contents);
$SMARTY->assign('customer', $customer);
$SMARTY->assign('invoice', $invoice);
$SMARTY->assign('tariffs', $tariffs);
$SMARTY->assign('users', $users);
$SMARTY->display('invoiceedit.html');
?>
