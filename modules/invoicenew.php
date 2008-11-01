<?php

/*
 * LMS version 1.11-cvs
 *
 *  (C) Copyright 2001-2008 LMS Developers
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

// Invoiceless liabilities: Zobowiazania/obciazenia na ktore nie zostala wystawiona faktura
function GetCustomerCovenants($customerid)
{
	global $DB;

	if(!$customerid) return NULL;
	
	return $DB->GetAll('SELECT c.time, c.value*-1 AS value, c.comment, c.taxid, 
			taxes.label AS tax, c.id AS cashid,
			ROUND(c.value / (taxes.value+100)*100, 2)*-1 AS net
			FROM cash c
			LEFT JOIN taxes ON (c.taxid = taxes.id)
			WHERE c.customerid = ? AND c.docid = 0 AND c.value < 0
			ORDER BY time', array($customerid));
}

$layout['pagetitle'] = trans('New Invoice');

$taxeslist = $LMS->GetTaxes();

$SESSION->restore('invoicecontents', $contents);
$SESSION->restore('invoicecustomer', $customer);
$SESSION->restore('invoice', $invoice);
$SESSION->restore('invoicenewerror', $error);

$itemdata = r_trim($_POST);

$action = isset($_GET['action']) ? $_GET['action'] : NULL;

switch($action)
{
	case 'init':

    		unset($invoice);
    		unset($contents);
    		unset($customer);
    		unset($error);

		// get default invoice's numberplanid and next number
		$invoice['numberplanid'] = $DB->GetOne('SELECT id FROM numberplans WHERE doctype=? AND isdefault=1', array(DOC_INVOICE));
		$invoice['cdate'] = time();
		if(isset($_GET['customerid']) && $_GET['customerid'] != '' && $LMS->CustomerExists($_GET['customerid']))
		{
			$customer = $LMS->GetCustomer($_GET['customerid'], true);
			if($customer['paytime'] == -1)
				$invoice['paytime'] = 14;
			else
				$invoice['paytime'] = $customer['paytime'];
		}
	break;

	case 'additem':

		foreach(array('count', 'discount', 'valuenetto', 'valuebrutto') as $key)
			$itemdata[$key] = f_round($itemdata[$key]);
		
		if($itemdata['count'] > 0 && $itemdata['name'] != '')
		{
			$taxvalue = isset($itemdata['taxid']) ? $taxeslist[$itemdata['taxid']]['value'] : 0;
			if($itemdata['valuenetto'] != 0)
			{
				$itemdata['valuenetto'] = f_round($itemdata['valuenetto'] - $itemdata['valuenetto'] * f_round($itemdata['discount'])/100);
				$itemdata['valuebrutto'] = round($itemdata['valuenetto'] * ($taxvalue / 100 + 1),2);
			}
			elseif($itemdata['valuebrutto'] != 0)
			{
				$itemdata['valuebrutto'] = f_round($itemdata['valuebrutto'] - $itemdata['valuebrutto'] * f_round($itemdata['discount'])/100);
				$itemdata['valuenetto'] = round($itemdata['valuebrutto'] / ($taxvalue + 100) * 100, 2);
			}
			
			// str_replace->f_round here is needed because of bug in some PHP versions
			$itemdata['s_valuenetto'] = f_round($itemdata['valuenetto'] * $itemdata['count']);
			$itemdata['s_valuebrutto'] = f_round($itemdata['valuebrutto'] * $itemdata['count']);
			$itemdata['valuenetto'] = f_round($itemdata['valuenetto']);
			$itemdata['valuebrutto'] = f_round($itemdata['valuebrutto']);
			$itemdata['count'] = f_round($itemdata['count']);
			$itemdata['discount'] = f_round($itemdata['discount']);
			$itemdata['tax'] = isset($itemdata['taxid']) ? $taxeslist[$itemdata['taxid']]['label'] : '';
			$itemdata['posuid'] = (string) getmicrotime();
			$contents[] = $itemdata;
		}
	break;

	case 'additemlist':
	
		if($marks = $_POST['marks'])
		{
			foreach($marks as $id)
			{
				$cash = $DB->GetRow('SELECT value, comment, taxid 
						    FROM cash WHERE id = ?', array($id));
			
				$itemdata['cashid'] = $id;
				$itemdata['name'] = $cash['comment'];
				$itemdata['taxid'] = $cash['taxid'];
				$itemdata['tax'] = isset($taxeslist[$itemdata['taxid']]) ? $taxeslist[$itemdata['taxid']]['label'] : '';
				$itemdata['discount'] = 0;
				$itemdata['count'] = f_round($_POST['l_count'][$id]);
				$itemdata['valuebrutto'] = f_round((-$cash['value'])/$itemdata['count']);
				$itemdata['s_valuebrutto'] = f_round(-$cash['value']);
				$itemdata['valuenetto'] = round($itemdata['valuebrutto'] / ((isset($taxeslist[$itemdata['taxid']]) ? $taxeslist[$itemdata['taxid']]['value'] : 0) + 100) * 100, 2);
				$itemdata['s_valuenetto'] = round($itemdata['s_valuebrutto'] / ((isset($taxeslist[$itemdata['taxid']]) ? $taxeslist[$itemdata['taxid']]['value'] : 0) + 100) * 100, 2);
				$itemdata['prodid'] = $_POST['l_prodid'][$id];
				$itemdata['jm'] = $_POST['l_jm'][$id];
				$itemdata['posuid'] = (string) (getmicrotime()+$id);
				$itemdata['tariffid'] = 0;
				$contents[] = $itemdata;
			}
		}
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

		$invoice['customerid'] = $_POST['customerid'];
		
		if($invoice['cdate'])
		{
			list($year, $month, $day) = split('/',$invoice['cdate']);
			if(checkdate($month, $day, $year)) 
			{
				$invoice['cdate'] = mktime(date('G',time()),date('i',time()),date('s',time()),$month,$day,$year);
				$currmonth = $month;
			}
			else
			{
				$error['cdate'] = trans('Incorrect date format!');
				$invoice['cdate'] = time();
				break;
			}
		}

		if($invoice['cdate'] && !isset($invoice['cdatewarning']))
		{
			$maxdate = $DB->GetOne('SELECT MAX(cdate) FROM documents WHERE type = ? AND numberplanid = ?', 
					array(DOC_INVOICE, $invoice['numberplanid']));
	
			if($invoice['cdate'] < $maxdate)
			{
				$error['cdate'] = trans('Last date of invoice settlement is $0. If sure, you want to write invoice with date of $1, then click "Submit" again.',date('Y/m/d H:i', $maxdate), date('Y/m/d H:i', $invoice['cdate']));
				$invoice['cdatewarning'] = 1;
			}
		}

		if($invoice['number'])
		{
			if(!eregi('^[0-9]+$', $invoice['number']))
				$error['number'] = trans('Invoice number must be integer!');
			elseif($LMS->DocumentExists($invoice['number'], DOC_INVOICE, $invoice['numberplanid'], $invoice['cdate']))
				$error['number'] = trans('Invoice number $0 already exists!', $invoice['number']);
		}
		
		if(!isset($error))
		{
			$cid = isset($_GET['customerid']) && $_GET['customerid'] != '' ? intval($_GET['customerid']) : intval($_POST['customerid']);
			if($LMS->CustomerExists($cid))
				$customer = $LMS->GetCustomer($cid, true);
		}
	break;

	case 'save':

		if($contents && $customer)
		{
			$DB->BeginTrans();
			$DB->LockTables('documents');
			
			if(!$invoice['number'])
				$invoice['number'] = $LMS->GetNewDocumentNumber(DOC_INVOICE, $invoice['numberplanid'], $invoice['cdate']);
			else
			{
				if(!eregi('^[0-9]+$', $invoice['number']))
					$error['number'] = trans('Invoice number must be integer!');
				elseif($LMS->DocumentExists($invoice['number'], DOC_INVOICE, $invoice['numberplanid'], $invoice['cdate']))
					$error['number'] = trans('Invoice number $0 already exists!', $invoice['number']);
				
				if($error)
					$invoice['number'] = $LMS->GetNewDocumentNumber(DOC_INVOICE, $invoice['numberplanid'], $invoice['cdate']);
			}
				
			$invoice['type'] = DOC_INVOICE;
			$iid = $LMS->AddInvoice(array('customer' => $customer, 'contents' => $contents, 'invoice' => $invoice));
		
			// usuwamy wczesniejsze zobowiazania bez faktury
			foreach($contents as $item)
				if(isset($item['cashid']))
					$DB->Execute('DELETE FROM cash WHERE id = ?', array($item['cashid']));
		
			$DB->UnLockTables();
			$DB->CommitTrans();
			
			$SESSION->remove('invoicecontents');
			$SESSION->remove('invoicecustomer');
			$SESSION->remove('invoice');
			$SESSION->remove('invoicenewerror');
			
			if(isset($_GET['print']))
				$SESSION->redirect('?m=invoicenew&action=init&invoice='.$iid
					.(isset($_GET['original']) ? '&original=1' : '')
					.(isset($_GET['copy']) ? '&copy=1' : '')
					);
			else
				$SESSION->redirect('?m=invoicenew&action=init');
		}
	break;
}

if(!isset($invoice['paytype']) || $invoice['paytype'] == '')
	$invoice['paytype'] = trans('CASH');

$SESSION->save('invoice', $invoice);
$SESSION->save('invoicecontents', isset($contents) ? $contents : NULL);
$SESSION->save('invoicecustomer', isset($customer) ? $customer : NULL);
$SESSION->save('invoicenewerror', isset($error) ? $error : NULL);

if($action)
{
	// redirect, �eby refreshem nie spierdoli� faktury
	if($action == 'init')
	{
		$SESSION->redirect('?m=invoicenew'
			.(isset($_GET['invoice']) ? '&invoice='.intval($_GET['invoice']) : '')
			.(isset($_GET['original']) ? '&original=1' : '')
			.(isset($_GET['copy']) ? '&copy=1' : '')
		);
	}
	else
		$SESSION->redirect('?m=invoicenew');
}

$covenantlist = array();
$list = GetCustomerCovenants($customer['id']);

if(isset($list))
	if($contents)
		foreach($list as $row)
		{
			$i = 0;
			foreach($contents as $item)
				if(isset($item['cashid']) && $row['cashid'] == $item['cashid'])
				{
					$i = 1;
					break;
				}
			if(!$i)
				$covenantlist[] = $row;
		}
	else
		$covenantlist = $list;

if(!isset($CONFIG['phpui']['big_networks']) || !chkconfig($CONFIG['phpui']['big_networks']))
{
        $SMARTY->assign('customers', $LMS->GetCustomerNames());
}

$SMARTY->assign('covenantlist', $covenantlist);
$SMARTY->assign('error', $error);
$SMARTY->assign('contents', $contents);
$SMARTY->assign('customer', $customer);
$SMARTY->assign('invoice', $invoice);
$SMARTY->assign('tariffs', $LMS->GetTariffs());
$SMARTY->assign('numberplanlist', $LMS->GetNumberPlans(DOC_INVOICE, date('Y/m', $invoice['cdate'])));
$SMARTY->assign('taxeslist', $taxeslist);
$SMARTY->assign('newinvoice', isset($_GET['invoice']) ? $_GET['invoice'] : NULL);
$SMARTY->assign('original', isset($_GET['original']) ? TRUE : FALSE);
$SMARTY->assign('copy', isset($_GET['copy']) ? TRUE : FALSE);
$SMARTY->display('invoicenew.html');

?>
