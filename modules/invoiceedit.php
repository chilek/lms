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

$taxeslist = $LMS->GetTaxes();
$action = isset($_GET['action']) ? $_GET['action'] : '';

if(isset($_GET['id']) && $action == 'edit')
{
	$invoice = $LMS->GetInvoiceContent($_GET['id']);

	$SESSION->remove('invoicecontents');
	$SESSION->remove('invoicecustomer');

	$i = 0;
	foreach ($invoice['content'] as $item) {
		$i++;
		$nitem['tariffid']	= $item['tariffid'];
		$nitem['name']		= $item['description'];
		$nitem['prodid']	= $item['prodid'];
		$nitem['count']		= str_replace(',' ,'.', $item['count']);
		$nitem['discount']	= str_replace(',' ,'.', $item['pdiscount']);
		$nitem['pdiscount']	= str_replace(',' ,'.', $item['pdiscount']);
		$nitem['vdiscount']	= str_replace(',' ,'.', $item['vdiscount']);
		$nitem['jm']		= str_replace(',' ,'.', $item['content']);
		$nitem['valuenetto']	= str_replace(',' ,'.', $item['basevalue']);
		$nitem['valuebrutto']	= str_replace(',' ,'.', $item['value']);
		$nitem['s_valuenetto']	= str_replace(',' ,'.', $item['totalbase']);
		$nitem['s_valuebrutto']	= str_replace(',' ,'.', $item['total']);
		$nitem['tax']		= isset($taxeslist[$item['taxid']]) ? $taxeslist[$item['taxid']]['label'] : '';
		$nitem['taxid']		= $item['taxid'];
		$nitem['posuid']	= $i;
		$SESSION->restore('invoicecontents', $invoicecontents);
		$invoicecontents[] = $nitem;
		$SESSION->save('invoicecontents', $invoicecontents);
	}
	$SESSION->save('invoicecustomer', $LMS->GetCustomer($invoice['customerid'], true));
	$invoice['oldcdate'] = $invoice['cdate'];
	$invoice['oldsdate'] = $invoice['sdate'];
	$SESSION->save('invoice', $invoice);
	$SESSION->save('invoiceid', $invoice['id']);
}

$SESSION->restore('invoicecontents', $contents);
$SESSION->restore('invoicecustomer', $customer);
$SESSION->restore('invoice', $invoice);
$SESSION->restore('invoiceediterror', $error);
$itemdata = r_trim($_POST);

$ntempl = docnumber($invoice['number'], $invoice['template'], $invoice['cdate']);
$layout['pagetitle'] = trans('Invoice Edit: $a', $ntempl);

if(isset($_GET['customerid']) && $_GET['customerid'] != '' && $LMS->CustomerExists($_GET['customerid']))
	$action = 'setcustomer';

switch($action)
{
	case 'additem':
		if ($invoice['closed'])
			break;

		$itemdata = r_trim($_POST);

		unset($error);

		$itemdata['discount'] = str_replace(',', '.', $itemdata['discount']);
		$itemdata['pdiscount'] = 0;
		$itemdata['vdiscount'] = 0;
		if (preg_match('/^[0-9]+(\.[0-9]+)*$/', $itemdata['discount'])) {
			$itemdata['pdiscount'] = ($itemdata['discount_type'] == DISCOUNT_PERCENTAGE ? floatval($itemdata['discount']) : 0);
			$itemdata['vdiscount'] = ($itemdata['discount_type'] == DISCOUNT_AMOUNT ? floatval($itemdata['discount']) : 0);
		}
		if ($itemdata['pdiscount'] < 0 || $itemdata['pdiscount'] > 99.9 || $itemdata['vdiscount'] < 0)
			$error['discount'] = trans('Wrong discount value!');

		if ($error)
			break;

		foreach(array('count', 'discount', 'pdiscount', 'vdiscount', 'valuenetto', 'valuebrutto') as $key)
			$itemdata[$key] = round((float) str_replace(',', '.', $itemdata[$key]), 2);

		if ($itemdata['count'] > 0 && $itemdata['name'] != '')
		{
			$taxvalue = $taxeslist[$itemdata['taxid']]['value'];
			if ($itemdata['valuenetto'] != 0)
			{
				$itemdata['valuenetto'] = f_round(($itemdata['valuenetto'] - $itemdata['valuenetto'] * f_round($itemdata['pdiscount']) / 100) - $itemdata['vdiscount']);
				$itemdata['valuebrutto'] = round($itemdata['valuenetto'] * ($taxvalue / 100 + 1), 2);
			}
			elseif ($itemdata['valuebrutto'] != 0)
			{
				$itemdata['valuebrutto'] = f_round(($itemdata['valuebrutto'] - $itemdata['valuebrutto'] * $itemdata['pdiscount'] / 100) - $itemdata['vdiscount']);
				$itemdata['valuenetto'] = round($itemdata['valuebrutto'] / ($taxvalue / 100 + 1), 2);
			}

			// str_replace here is needed because of bug in some PHP versions (4.3.10)
			$itemdata['s_valuebrutto'] = str_replace(',', '.', $itemdata['valuebrutto'] * $itemdata['count']);
			$itemdata['s_valuenetto'] = str_replace(',', '.', $itemdata['s_valuebrutto'] / ($taxvalue / 100 + 1));
			$itemdata['valuenetto'] = str_replace(',', '.', $itemdata['valuenetto']);
			$itemdata['valuebrutto'] = str_replace(',', '.', $itemdata['valuebrutto']);
			$itemdata['count'] = str_replace(',', '.', $itemdata['count']);
			$itemdata['discount'] = str_replace(',', '.', $itemdata['discount']);
			$itemdata['pdiscount'] = str_replace(',', '.', $itemdata['pdiscount']);
			$itemdata['vdiscount'] = str_replace(',', '.', $itemdata['vdiscount']);
			$itemdata['tax'] = $taxeslist[$itemdata['taxid']]['label'];
			$itemdata['posuid'] = (string) getmicrotime();
			$contents[] = $itemdata;
		}
	break;

	case 'deletepos':
		if ($invoice['closed'])
			break;

		if (sizeof($contents))
			foreach($contents as $idx => $row)
				if ($row['posuid'] == $_GET['posuid']) 
					unset($contents[$idx]);
	break;

	case 'setcustomer':

		$oldcdate = $invoice['oldcdate'];
		$oldsdate = $invoice['oldsdate'];
		$closed   = $invoice['closed'];

		unset($invoice);
		unset($customer);
		unset($error);
		$error = NULL;

		if($invoice = $_POST['invoice'])
			foreach($invoice as $key => $val)
				$invoice[$key] = $val;

		$invoice['paytime'] = sprintf('%d', $invoice['paytime']);
		$invoice['oldcdate'] = $oldcdate;
		$invoice['oldsdate'] = $oldsdate;

		if($invoice['paytime'] < 0)
			$invoice['paytime'] = 14;

		if($invoice['cdate']) // && !$invoice['cdatewarning'])
		{
			list($year, $month, $day) = explode('/', $invoice['cdate']);
			if(checkdate($month, $day, $year))
			{
				$oldday = date('d', $invoice['oldcdate']);
				$oldmonth = date('m', $invoice['oldcdate']);
				$oldyear = date('Y', $invoice['oldcdate']);

				if($oldday != $day || $oldmonth != $month || $oldyear != $year)
				{
					$invoice['cdate'] = mktime(date('G', time()), date('i', time()), date('s', time()), $month, $day, $year);
				}
				else // save hour/min/sec value if date is the same
					$invoice['cdate'] = $invoice['oldcdate'];
			}
			else
				$error['cdate'] = trans('Incorrect date format!');
		}

		if($invoice['sdate'])
		{
			list($syear, $smonth, $sday) = explode('/', $invoice['sdate']);
			if(checkdate($smonth, $sday, $syear))
			{
				$oldsday = date('d', $invoice['oldsdate']);
				$oldsmonth = date('m', $invoice['oldsdate']);
				$oldsyear = date('Y', $invoice['oldsdate']);

				if($oldsday != $sday || $oldsmonth != $smonth || $oldsyear != $syear)
				{
					$invoice['sdate'] = mktime(date('G', time()), date('i', time()), date('s', time()), $smonth, $sday, $syear);
				}
				else // save hour/min/sec value if date is the same
					$invoice['sdate'] = $invoice['oldsdate'];
			}
			else
				$error['sdate'] = trans('Incorrect date format!');
		}

		$invoice['customerid'] = $_POST['customerid'];
		$invoice['closed']     = $closed;

		if(!$error)
			if($LMS->CustomerExists($invoice['customerid']))
				$customer = $LMS->GetCustomer($invoice['customerid'], true);
	break;

	case 'save':
		if (empty($contents) || empty($customer))
			break;

		$SESSION->restore('invoiceid', $invoice['id']);
		$invoice['type'] = DOC_INVOICE;

		$currtime = time();
		$cdate = $invoice['cdate'] ? $invoice['cdate'] : $currtime;
		$sdate = $invoice['sdate'] ? $invoice['sdate'] : $currtime;
		$iid   = $invoice['id'];

		$DB->BeginTrans();

		$DB->Execute('UPDATE documents SET cdate = ?, sdate = ?, paytime = ?, paytype = ?, customerid = ?,
				name = ?, address = ?, ten = ?, ssn = ?, zip = ?, city = ?, divisionid = ?
				WHERE id = ?',
				array($cdate,
					$sdate,
					$invoice['paytime'],
					$invoice['paytype'],
					$customer['id'],
					$customer['customername'],
					$customer['address'],
					$customer['ten'],
					$customer['ssn'],
					$customer['zip'],
					$customer['city'],
					$customer['divisionid'],
					$iid
				));

		if (!$invoice['closed']) {
			$DB->Execute('DELETE FROM invoicecontents WHERE docid = ?', array($iid));
			$DB->Execute('DELETE FROM cash WHERE docid = ?', array($iid));

			$itemid=0;
			foreach ($contents as $idx => $item) {
				$itemid++;

				$DB->Execute('INSERT INTO invoicecontents (docid, itemid, value,
					taxid, prodid, content, count, pdiscount, vdiscount, description, tariffid)
					VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
					array(
						$iid,
						$itemid,
						$item['valuebrutto'],
						$item['taxid'],
						$item['prodid'],
						$item['jm'],
						$item['count'],
						$item['pdiscount'],
						$item['vdiscount'],
						$item['name'],
						$item['tariffid']
					));

				$LMS->AddBalance(array(
					'time' => $cdate,
					'value' => $item['valuebrutto']*$item['count']*-1,
					'taxid' => $item['taxid'],
					'customerid' => $customer['id'],
					'comment' => $item['name'],
					'docid' => $iid,
					'itemid' => $itemid
					));
			}
		}

		$DB->CommitTrans();

		if (isset($_GET['print']))
			$SESSION->save('invoiceprint', array('invoice' => $invoice['id'],
				'original' => !empty($_GET['original']) ? 1 : 0,
			'copy' => !empty($_GET['copy']) ? 1 : 0,
				'duplicate' => !empty($_GET['duplicate']) ? 1 : 0));

		$SESSION->redirect('?m=invoicelist');
	break;
}

$SESSION->save('invoice', $invoice);
$SESSION->save('invoicecontents', $contents);
$SESSION->save('invoicecustomer', $customer);
$SESSION->save('invoiceediterror', $error);

if($action != '')
{
	// redirect needed because we don't want to destroy contents of invoice in order of page refresh
	$SESSION->redirect('?m=invoiceedit');
}

if(!isset($CONFIG['phpui']['big_networks']) || !chkconfig($CONFIG['phpui']['big_networks']))
{
        $SMARTY->assign('customers', $LMS->GetCustomerNames());
}

$SMARTY->assign('error', $error);
$SMARTY->assign('contents', $contents);
$SMARTY->assign('customer', $customer);
$SMARTY->assign('invoice', $invoice);
$SMARTY->assign('tariffs', $LMS->GetTariffs());
$SMARTY->assign('taxeslist', $taxeslist);
$SMARTY->display('invoiceedit.html');

?>
