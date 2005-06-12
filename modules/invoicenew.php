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

$layout['pagetitle'] = trans('New Invoice');

$customers = $LMS->GetCustomerNames();
$tariffs = $LMS->GetTariffs();
$taxeslist = $LMS->GetTaxes();
$SESSION->restore('invoicecontents', $contents);
$SESSION->restore('invoicecustomer', $customer);
$SESSION->restore('invoice', $invoice);
$SESSION->restore('invoicenewerror', $error);
$itemdata = r_trim($_POST);

switch($_GET['action'])
{
	case 'init':

    		unset($invoice);
    		unset($contents);
    		unset($customer);
    		unset($error);
		
		$invoice['number'] = $LMS->GetNewInvoiceNumber();
		$invoice['month'] = date("m");
		$invoice['year']  = date("Y");
		$invoice['cdate'] = time();
		$invoice['paytime'] = 14;
		if($_GET['customerid'] != '' && $LMS->CustomerExists($_GET['customerid']))
			$customer = $LMS->GetCustomer($_GET['customerid']);
	break;

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
			
			// str_replace here is needed because of bug in some PHP versions
			$itemdata['s_valuenetto'] = str_replace(',','.',$itemdata['valuenetto'] * $itemdata['count']);
			$itemdata['s_valuebrutto'] = str_replace(',','.',$itemdata['valuebrutto'] * $itemdata['count']);
			$itemdata['taxvalue'] = str_replace(',','.',$itemdata['taxvalue']);
			$itemdata['valuenetto'] = str_replace(',','.',$itemdata['valuenetto']);
			$itemdata['valuebrutto'] = str_replace(',','.',$itemdata['valuebrutto']);
			$itemdata['count'] = str_replace(',','.',$itemdata['count']);
			$itemdata['posuid'] = (string) getmicrotime();
			$contents[] = $itemdata;
		}
	break;

	case 'deletepos':
		if(sizeof($contents))
			foreach($contents as $idx => $row)
				if($row['posuid'] == $_GET['posuid']) 
					unset($contents[$idx]);
	break;

	case 'setcustomer':

		$oldmonth = $invoice['month'];
		$oldyear  = $invoice['year'];		
		unset($invoice); 
		unset($customer);
		unset($error);
		
		if($invoice = $_POST['invoice'])
			foreach($invoice as $key => $val)
				$invoice[$key] = $val;
		
		$invoice['paytime'] = sprintf('%d', $invoice['paytime']);
		
		if($invoice['paytime'] < 0)
			$invoice['paytime'] = 14;

		$invoice['customerid'] = $_POST['customerid'];
		
		if($invoice['cdate'])
		{
			list($year, $month, $day) = split('/',$invoice['cdate']);
			if(checkdate($month, $day, $year)) 
			{
				$invoice['cdate'] = mktime(date('G',time()),date('i',time()),date('s',time()),$month,$day,$year);
				
				if (($oldmonth!=$month) || ($oldyear!=$year))
				{
					$invoice['month'] = $month;
					$invoice['year']  = $year;
				} 
				else 
				{
					$invoice['month'] = $oldmonth;
					$invoice['year']  = $oldyear;
				}
			}
			else
			{
				$error['cdate'] = trans('Incorrect date format!');
				$invoice['cdate'] = time();
				$invoice['month'] = $oldmonth;
				$invoice['year']  = $oldyear;
				break;
			}
		}

		if($invoice['cdate'] && !$invoice['cdatewarning'])
		{
			$maxdate = $DB->GetOne('SELECT MAX(cdate) FROM documents WHERE type = 1');
			if($invoice['cdate'] < $maxdate)
			{
				$error['cdate'] = trans('Last date of invoice settlement is $0. If sure, you want to write invoice with date of $1, then click "Submit" again.',date('Y/m/d H:i', $maxdate), date('Y/m/d H:i', $invoice['cdate']));
				$invoice['cdatewarning'] = 1;
			}
		}

		if(!$invoice['number'])
			$invoice['number'] = $LMS->GetNewInvoiceNumber($invoice['cdate']);
		else
		{
			if(!eregi('^[0-9]+$', $invoice['number']))
				$error['number'] = trans('Invoice number must be integer!');
			elseif($LMS->InvoiceExists($invoice['number'], $invoice['cdate']))
				$error['number'] = trans('Invoice number $0 already exists!', $invoice['number']);
		}
		
		if(!$error)
			if($LMS->CustomerExists(($_GET['customerid'] != '' ? $_GET['customerid'] : $_POST['customer'])))
				$customer = $LMS->GetCustomer(($_GET['customerid'] != '' ? $_GET['customerid'] : $_POST['customer']));

	break;

	case 'save':

		if($contents && $customer)
		{
			$iid = $LMS->AddInvoice(array('customer' => $customer, 'contents' => $contents, 'invoice' => $invoice));
		
			$SESSION->remove('invoicecontents');
			$SESSION->remove('invoicecustomer');
			$SESSION->remove('invoice');
			$SESSION->remove('invoicenewerror');
			$SESSION->redirect('?m=invoice&id='.$iid);
		}
	break;
}

if($invoice['paytype'] == '')
	$invoice['paytype'] = trans('CASH');

$SESSION->save('invoice', $invoice);
$SESSION->save('invoicecontents', $contents);
$SESSION->save('invoicecustomer', $customer);
$SESSION->save('invoicenewerror', $error);

if($_GET['action'] != '')
{
	// redirect, ¿eby refreshem nie spierdoliæ faktury
	$SESSION->redirect('?m=invoicenew');
}

$SMARTY->assign('error', $error);
$SMARTY->assign('contents', $contents);
$SMARTY->assign('customer', $customer);
$SMARTY->assign('invoice', $invoice);
$SMARTY->assign('tariffs', $tariffs);
$SMARTY->assign('customers', $customers);
$SMARTY->assign('taxeslist', $taxeslist);
$SMARTY->display('invoicenew.html');

?>
