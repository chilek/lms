<?php

/*
 * LMS version 1.5-cvs
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

if ((isset($_GET['id'])) && ($_GET['action']=='edit')) {
    $invoice = $LMS->GetInvoiceContent($_GET['id']);

    unset($_SESSION['invoicecontents']);
    unset($_SESSION['invoicecustomer']);

    foreach ($invoice['content'] as $item) {
	$i++;
        $nitem["tariffid"]		= $item["tariffid"];
	$nitem["name"]			= $item["description"];
        $nitem["ttariffid"]		= $item["tariffid"];
	$nitem["pkwiu"]			= $item["pkwiu"];
        $nitem["count"]			= str_replace(",",".",$item["count"]);
	$nitem["jm"]			= str_replace(",",".",$item["content"]);
        $nitem["valuenetto"]		= str_replace(",",".",$item["basevalue"]);
	$nitem["taxvalue"]		= str_replace(",",".",$item["taxvalue"]);
        $nitem["valuebrutto"]		= str_replace(",",".",$item["value"]);
	$nitem["s_valuenetto"]		= str_replace(",",".",$item["totalbase"]);
        $nitem["s_valuebrutto"]		= str_replace(",",".",$item["total"]);
	$nitem["posuid"]		= $i;
        $_SESSION['invoicecontents'][] = $nitem;
    }
    $_SESSION['invoicecustomer'] = $LMS->GetUser($invoice["customerid"]);
    $_SESSION['invoice'] = $invoice;
    $_SESSION['invoiceid'] = $invoice['id'];
}

$layout['pagetitle'] = trans('Edit Invoice');
$users = $LMS->GetUserNames();
$tariffs = $LMS->GetTariffs();
$contents = $_SESSION['invoicecontents'];
$customer = $_SESSION['invoicecustomer'];
$invoice = $_SESSION['invoice'];
$error = $_SESSION['invoiceediterror'];
$itemdata = r_trim($_POST);

if($_GET['userid'] != '' && $LMS->UserExists($_GET['userid']))
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
			$itemdata['s_valuenetto'] = $itemdata['valuenetto'] * $itemdata['count'];
			$itemdata['s_valuebrutto'] = $itemdata['valuebrutto'] * $itemdata['count'];
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
		
		unset($invoice); 
		unset($customer);
		unset($error);
		
		if($invoice = $_POST['invoice'])
			foreach($invoice as $key => $val)
				$invoice[$key] = $val;
		
		$invoice['paytime'] = sprintf('%d', $invoice['paytime']);
		
		if($invoice['paytime'] < 0)
			$invoice['paytime'] = 14;

		if($invoice['cdate']) // && !$invoice['cdatewarning'])
		{
			list($year, $month, $day) = split('/',$invoice['cdate']);
			if(checkdate($month, $day, $year))
				$invoice['cdate'] = mktime(date('G',time()),date('i',time()),date('s',time()),$month,$day,$year);
			else
				$error['cdate'] = trans('Incorrect date format!');
		}
		
		$invoice['userid'] = $_POST['userid'];
		
		if(!$error)
			if($LMS->UserExists(($_GET['userid'] != '' ? $_GET['userid'] : $_POST['user'])))
				$customer = $LMS->GetUser(($_GET['userid'] != '' ? $_GET['userid'] : $_POST['user']));
	break;

	case 'save':

		if($contents && $customer)
		{
			$invoice['id'] = $_SESSION['invoiceid'];
			$LMS->InvoiceUpdate(array('customer' => $customer, 'contents' => $contents, 'invoice' => $invoice));
		
			unset($_SESSION['invoicecontents']);
			unset($_SESSION['invoicecustomer']);
			unset($_SESSION['invoice']);
			unset($_SESSION['invoiceediterror']);
			header('Location: ?m=invoice&id='.$invoice['id']);
			die;
		}
	break;

	case 'invoicedel':
	    $LMS->InvoiceDelete($_GET['id']);
	    header('Location: ?m=invoicelist');
	    die;
}

if($invoice['paytype'] == '')
	$invoice['paytype'] = trans('CASH');

$_SESSION['invoice'] = $invoice;
$_SESSION['invoicecontents'] = $contents;
$_SESSION['invoicecustomer'] = $customer;
$_SESSION['invoiceediterror'] = $error;

if($_GET['action'] != '')
{
	// redirect, ¿eby refreshem nie spierdoliæ faktury
	header('Location: ?m=invoiceedit');
	die;
}

$SMARTY->assign('error', $error);
$SMARTY->assign('contents', $contents);
$SMARTY->assign('customer', $customer);
$SMARTY->assign('invoice', $invoice);
$SMARTY->assign('tariffs', $tariffs);
$SMARTY->assign('users', $users);
$SMARTY->display('invoiceedit.html');
?>
