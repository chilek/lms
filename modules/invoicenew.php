<?php

/*
 * LMS version 1.5-cvs
 *
 *  (C) Copyright 2001-2004 LMS Developers
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

$layout['pagetitle'] = 'Nowa faktura';
$users = $LMS->GetUserNames();
$tariffs = $LMS->GetTariffs();
$contents = $_SESSION['invoicecontents'];
$customer = $_SESSION['invoicecustomer'];
$invoice = $_SESSION['invoice'];
$error = $_SESSION['invoicenewerror'];
$itemdata = r_trim($_POST);

if($_GET['userid'] != '' && $LMS->UserExists($_GET['userid']))
	$_GET['action'] = 'setcustomer';

switch($_GET['action'])
{
	case 'additem':
		$itemdata = r_trim($_POST);
		foreach(array('count', 'valuenetto', 'valuebrutto') as $key)
			$itemdata[$key] = sprintf('%01.2f', str_replace(',','.',$itemdata[$key]));
		if ($itemdata['taxvalue'] != '')
			$itemdata['taxvalue'] = sprintf('%01.2f',$itemdata['taxvalue']);
		if($itemdata['count'] > 0 && $itemdata['name'] != '')
		{
			$taxvalue = $itemdata['taxvalue'];
			if ($taxvalue == '')
				$taxvalue = 0;
			if($taxvalue < 0 || $taxvalue > 100)
				$error['taxvalue'] = 'Niepoprawna wysoko¶æ podatku!';
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

		if($invoice['cdate'] && !$invoice['cdatewarning'])
		{
			list($year, $month, $day) = split('/',$invoice['cdate']);
			if(checkdate($month, $day, $year))
				$invoice['cdate'] = mktime(date('G',time()),date('i',time()),date('s',time()),$month,$day,$year);
			else
				$error['cdate'] = 'Nieprawid³owa data!';
		}

		if($invoice['cdate'] && !$invoice['cdatewarning'] && !$error)
		{
			$maxdate = $LMS->DB->GetOne('SELECT MAX(cdate) FROM invoices');
			if($invoice['cdate'] < $maxdate)
			{
				$error['cdate'] = "Ostatnia data wyst. faktury to '".date('Y/m/d H:i', $maxdate)."'. Je¶li jeste¶ pewien, ¿e chcesz zapisaæ fakturê z dat± '".date('Y/m/d H:i', $invoice['cdate'])."' kliknij ponownie 'Wybierz/zmieñ u¿ytkownika'.";
				$invoice['cdatewarning'] = 1;
			}
		}
		
		$invoice['userid'] = $_POST['userid'];
		
		if(!$error)
			if($LMS->UserExists(($_GET['userid'] != '' ? $_GET['userid'] : $_POST['user'])))
				$customer = $LMS->GetUser(($_GET['userid'] != '' ? $_GET['userid'] : $_POST['user']));
	break;

	case 'save':
		if($contents && $customer)
		{
			$iid = $LMS->AddInvoice(array('customer' => $customer, 'contents' => $contents, 'invoice' => $invoice));
		}
		unset($_SESSION['invoicecontents']);
		unset($_SESSION['invoicecustomer']);
		unset($_SESSION['invoice']);
		unset($_SESSION['invoicenewerror']);
		header('Location: ?m=invoice&id='.$iid);
		die;
	break;
}

if($invoice['paytype'] == '')
	$invoice['paytype'] = 'GOTÓWKA';

$_SESSION['invoice'] = $invoice;
$_SESSION['invoicecontents'] = $contents;
$_SESSION['invoicecustomer'] = $customer;
$_SESSION['invoicenewerror'] = $error;

if($_GET['action'] != '')
{
	// redirect, ¿eby refreshem nie spierdoliæ faktury
	header('Location: ?m=invoicenew');
	die;
}

$SMARTY->assign('error', $error);
$SMARTY->assign('contents', $contents);
$SMARTY->assign('customer', $customer);
$SMARTY->assign('invoice', $invoice);
$SMARTY->assign('tariffs', $tariffs);
$SMARTY->assign('users', $users);
$SMARTY->display('invoicenew.html');

?>
