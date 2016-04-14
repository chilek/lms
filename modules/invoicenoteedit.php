<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2015 LMS Developers
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

if (isset($_GET['id']) && $action == 'edit') {
	$cnote = $LMS->GetInvoiceContent($_GET['id']);
	$invoice = array();
	foreach ($cnote['invoice']['content'] as $item)
		$invoice[$item['itemid']] = $item;
	$cnote['invoice']['content'] = $invoice;

	$SESSION->remove('cnotecontents');
	$SESSION->remove('cnote');
	$SESSION->remove('cnoteediterror');

	$cnotecontents = array();
	foreach ($cnote['content'] as $item) {
		$deleted = $item['value'] == 0;
		$nitem['deleted'] = $deleted;
		$nitem['tariffid']	= $item['tariffid'];
		$nitem['name']		= $item['description'];
		$nitem['prodid']	= $item['prodid'];
		if ($deleted) {
			$iitem = $invoice[$item['itemid']];
			$nitem['count'] = $iitem['count'];
			$nitem['discount']	= $iitem['discount'];
			$nitem['pdiscount']	= $iitem['pdiscount'];
			$nitem['vdiscount']	= $iitem['vdiscount'];
			$nitem['content']		= $iitem['content'];
			$nitem['valuenetto']	= $iitem['basevalue'];
			$nitem['valuebrutto']	= $iitem['value'];
			$nitem['s_valuenetto']	= $iitem['totalbase'];
			$nitem['s_valuebrutto']	= $iitem['total'];
		} else {
			$nitem['count']		= str_replace(',' ,'.', $item['count']);
			$nitem['discount']	= str_replace(',' ,'.', $item['pdiscount']);
			$nitem['pdiscount']	= str_replace(',' ,'.', $item['pdiscount']);
			$nitem['vdiscount']	= str_replace(',' ,'.', $item['vdiscount']);
			$nitem['content']		= str_replace(',' ,'.', $item['content']);
			$nitem['valuenetto']	= str_replace(',' ,'.', $item['basevalue']);
			$nitem['valuebrutto']	= str_replace(',' ,'.', $item['value']);
			$nitem['s_valuenetto']	= str_replace(',' ,'.', $item['totalbase']);
			$nitem['s_valuebrutto']	= str_replace(',' ,'.', $item['total']);
		}
		$nitem['tax']		= isset($taxeslist[$item['taxid']]) ? $taxeslist[$item['taxid']]['label'] : '';
		$nitem['taxid']		= $item['taxid'];
		$cnotecontents[$item['itemid']] = $nitem;
	}
	$SESSION->save('cnotecontents', $cnotecontents);

	$cnote['oldcdate'] = $cnote['cdate'];
	$cnote['oldsdate'] = $cnote['sdate'];
	$SESSION->save('cnote', $cnote);
	$SESSION->save('cnoteid', $cnote['id']);
}

$SESSION->restore('cnotecontents', $contents);
$SESSION->restore('cnote', $cnote);
$SESSION->restore('cnoteediterror', $error);
$itemdata = r_trim($_POST);

$ntempl = docnumber($cnote['number'], $cnote['template'], $cnote['cdate']);
$layout['pagetitle'] = trans('Credit Note for Invoice Edit: $a', $ntempl);

switch ($action) {
	case 'deletepos':
		if ($cnote['closed'])
			break;
		$contents[$_GET['itemid']]['deleted'] = true;
		break;

	case 'recoverpos':
		if ($cnote['closed'])
			break;
		$contents[$_GET['itemid']]['deleted'] = false;
		break;

	case 'setheader':
		$oldcnote = $cnote;
		$cnote = null;
		$error = NULL;

		if ($cnote = $_POST['cnote'])
			foreach ($cnote as $key => $val)
				$cnote[$key] = $val;

		$cnote['paytime'] = sprintf('%d', $cnote['paytime']);

		if ($cnote['paytime'] < 0)
			$cnote['paytime'] = 14;

		$currtime = time();

		if ($cnote['sdate']) {
			list ($syear, $smonth, $sday) = explode('/', $cnote['sdate']);
			if (checkdate($smonth, $sday, $syear)) {
				$sdate = mktime(23, 59, 59, $smonth, $sday, $syear);
				$cnote['sdate'] = mktime(date('G', $currtime), date('i', $currtime), date('s', $currtime), $smonth, $sday, $syear);
				if ($sdate < $invoice['sdate'])
					$error['sdate'] = trans('Credit note sale date cannot be earlier than invoice sale date!');
			} else {
				$error['sdate'] = trans('Incorrect date format! Using current date.');
				$cnote['sdate'] = $currtime;
			}
		} else
			$cnote['sdate'] = $currtime;

		if ($cnote['cdate']) {
			list ($year, $month, $day) = explode('/', $cnote['cdate']);
			if (checkdate($month, $day, $year)) {
				$cnote['cdate'] = mktime(date('G', $currtime), date('i', $currtime), date('s', $currtime), $month, $day, $year);
				if($cnote['cdate'] < $invoice['cdate'])
					$error['cdate'] = trans('Credit note date cannot be earlier than invoice date!');
			} else {
				$error['cdate'] = trans('Incorrect date format! Using current date.');
				$cnote['cdate'] = $currtime;
			}
		} else
			$cnote['cdate'] = $currtime;

		$cnote = array_merge($oldcnote, $cnote);
		break;

	case 'save':
		if (empty($contents))
			break;

		$SESSION->restore('cnoteid', $cnote['id']);
		$cnote['type'] = DOC_CNOTE;

		$currtime = time();
		$cdate = $cnote['cdate'] ? $cnote['cdate'] : $currtime;
		$sdate = $cnote['sdate'] ? $cnote['sdate'] : $currtime;
		$iid   = $cnote['id'];

		$newcontents = r_trim($_POST);
		$invoicecontents = $cnote['invoice']['content'];

		foreach ($contents as $idx => $item) {
			$contents[$idx]['taxid'] = isset($newcontents['taxid'][$idx]) ? $newcontents['taxid'][$idx] : $item['taxid'];
			$contents[$idx]['prodid'] = isset($newcontents['prodid'][$idx]) ? $newcontents['prodid'][$idx] : $item['prodid'];
			$contents[$idx]['content'] = isset($newcontents['content'][$idx]) ? $newcontents['content'][$idx] : $item['content'];
			$contents[$idx]['count'] = isset($newcontents['count'][$idx]) ? $newcontents['count'][$idx] : $item['count'];

			$contents[$idx]['discount'] = str_replace(',', '.', isset($newcontents['discount'][$idx]) ? $newcontents['discount'][$idx] : $item['discount']);
			$contents[$idx]['pdiscount'] = 0;
			$contents[$idx]['vdiscount'] = 0;
			$contents[$idx]['discount_type'] = isset($newcontents['discount_type'][$idx]) ? $newcontents['discount_type'][$idx] : $item['discount_type'];
			if (preg_match('/^[0-9]+(\.[0-9]+)*$/', $contents[$idx]['discount'])) {
				$contents[$idx]['pdiscount'] = ($contents[$idx]['discount_type'] == DISCOUNT_PERCENTAGE ? floatval($contents[$idx]['discount']) : 0);
				$contents[$idx]['vdiscount'] = ($contents[$idx]['discount_type'] == DISCOUNT_AMOUNT ? floatval($contents[$idx]['discount']) : 0);
			}
			if ($contents[$idx]['pdiscount'] < 0 || $contents[$idx]['pdiscount'] > 99.9 || $contents[$idx]['vdiscount'] < 0)
				$error['discount'] = trans('Wrong discount value!');

			$contents[$idx]['name'] = isset($newcontents['name'][$idx]) ? $newcontents['name'][$idx] : $item['name'];
			$contents[$idx]['tariffid'] = isset($newcontents['tariffid'][$idx]) ? $newcontents['tariffid'][$idx] : $item['tariffid'];
			$contents[$idx]['valuebrutto'] = $newcontents['valuebrutto'][$idx] != '' ? $newcontents['valuebrutto'][$idx] : $item['valuebrutto'];
			$contents[$idx]['valuenetto'] = $newcontents['valuenetto'][$idx] != '' ? $newcontents['valuenetto'][$idx] : $item['valuenetto'];
			$contents[$idx]['valuebrutto'] = f_round($contents[$idx]['valuebrutto']);
			$contents[$idx]['valuenetto'] = f_round($contents[$idx]['valuenetto']);
			$contents[$idx]['count'] = f_round($contents[$idx]['count']);
			$contents[$idx]['pdiscount'] = f_round($contents[$idx]['pdiscount']);
			$contents[$idx]['vdiscount'] = f_round($contents[$idx]['vdiscount']);
			$taxvalue = $taxeslist[$contents[$idx]['taxid']]['value'];

			if ($contents[$idx]['valuenetto'] != $item['valuenetto'])
				$contents[$idx]['valuebrutto'] = $contents[$idx]['valuenetto'] * ($taxvalue / 100 + 1);

			if (isset($item['deleted']) && $item['deleted']) {
				$contents[$idx]['valuebrutto'] = 0;
				$contents[$idx]['cash'] = round($invoicecontents[$idx]['total'] * $item['count'], 2);
				$contents[$idx]['count'] = 0;
			} elseif ($contents[$idx]['count'] != $invoicecontents[$idx]['count']
				|| $contents[$idx]['valuebrutto'] != $invoicecontents[$idx]['value'])
				$contents[$idx]['cash'] = round($invoicecontents[$idx]['value'] * $invoicecontents[$idx]['count']
					- $contents[$idx]['valuebrutto'] * $contents[$idx]['count'], 2);

			$contents[$idx]['valuebrutto'] = $contents[$idx]['valuebrutto'] - $invoicecontents[$idx]['value'];
			$contents[$idx]['count'] = $contents[$idx]['count'] - $invoicecontents[$idx]['count'];
		}

		$DB->BeginTrans();

		$customer = $LMS->GetCustomer($cnote['customerid']);

		$division = $DB->GetRow('SELECT name, shortname, address, city, zip, countryid, ten, regon,
			account, inv_header, inv_footer, inv_author, inv_cplace 
			FROM divisions WHERE id = ?', array($customer['divisionid']));

		$args = array(
			'cdate' => $cdate,
			'sdate' => $sdate,
			'paytime' => $cnote['paytime'],
			'paytype' => $cnote['paytype'],
			$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST] => $customer['id'],
			'name' => $customer['customername'],
			'address' => $customer['address'],
			'ten' => $customer['ten'],
			'ssn' => $customer['ssn'],
			'zip' => $customer['zip'],
			'city' => $customer['city'],
			'reason' => $cnote['reason'],
			$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_DIV] => $customer['divisionid'],
			'div_name' => ($division['name'] ? $division['name'] : ''),
			'div_shortname' => ($division['shortname'] ? $division['shortname'] : ''),
			'div_address' => ($division['address'] ? $division['address'] : ''), 
			'div_city' => ($division['city'] ? $division['city'] : ''), 
			'div_zip' => ($division['zip'] ? $division['zip'] : ''),
			'div_' . $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_COUNTRY] => ($division['countryid'] ? $division['countryid'] : 0),
			'div_ten'=> ($division['ten'] ? $division['ten'] : ''),
			'div_regon' => ($division['regon'] ? $division['regon'] : ''),
			'div_account' => ($division['account'] ? $division['account'] : ''),
			'div_inv_header' => ($division['inv_header'] ? $division['inv_header'] : ''),
			'div_inv_footer' => ($division['inv_footer'] ? $division['inv_footer'] : ''),
			'div_inv_author' => ($division['inv_author'] ? $division['inv_author'] : ''),
			'div_inv_cplace' => ($division['inv_cplace'] ? $division['inv_cplace'] : ''),
			$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_DOC] => $iid,
		);
		$DB->Execute('UPDATE documents SET cdate = ?, sdate = ?, paytime = ?, paytype = ?, customerid = ?,
				name = ?, address = ?, ten = ?, ssn = ?, zip = ?, city = ?, reason = ?, divisionid = ?,
				div_name = ?, div_shortname = ?, div_address = ?, div_city = ?, div_zip = ?, div_countryid = ?,
				div_ten = ?, div_regon = ?, div_account = ?, div_inv_header = ?, div_inv_footer = ?,
				div_inv_author = ?, div_inv_cplace = ?
				WHERE id = ?', array_values($args));
		if ($SYSLOG)
			$SYSLOG->AddMessage(SYSLOG_RES_DOC, SYSLOG_OPER_UPDATE, $args,
				array($SYSLOG_RESOURCE_KEYS[SYSLOG_RES_DOC], $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST],
					$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_DIV], 'div_' . $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_COUNTRY]));

		if (!$cnote['closed']) {
			if ($SYSLOG) {
				$cashids = $DB->GetCol('SELECT id FROM cash WHERE docid = ?', array($iid));
				foreach ($cashids as $cashid) {
					$args = array(
						$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CASH] => $cashid,
						$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_DOC] => $iid,
						$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST] => $customer['id'],
					);
					$SYSLOG->AddMessage(SYSLOG_RES_CASH, SYSLOG_OPER_DELETE, $args,
						array_keys($args));
				}
				$itemids = $DB->GetCol('SELECT itemid FROM invoicecontents WHERE docid = ?', array($iid));
				foreach ($itemids as $itemid) {
					$args = array(
						$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_DOC] => $iid,
						$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST] => $customer['id'],
						'itemid' => $itemid,
					);
					$SYSLOG->AddMessage(SYSLOG_RES_INVOICECONT, SYSLOG_OPER_DELETE, $args,
						array($SYSLOG_RESOURCE_KEYS[SYSLOG_RES_DOC], $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST]));
				}
			}
			$DB->Execute('DELETE FROM invoicecontents WHERE docid = ?', array($iid));
			$DB->Execute('DELETE FROM cash WHERE docid = ?', array($iid));

			$itemid=0;
			foreach ($contents as $idx => $item) {
				$itemid++;

				$args = array(
					$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_DOC] => $iid,
					'itemid' => $itemid,
					'value' => $item['valuebrutto'],
					$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_TAX] => $item['taxid'],
					'prodid' => $item['prodid'],
					'content' => $item['content'],
					'count' => $item['count'],
					'pdiscount' => $item['pdiscount'],
					'vdiscount' => $item['vdiscount'],
					'name' => $item['name'],
					$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_TARIFF] => $item['tariffid'],
				);
				$DB->Execute('INSERT INTO invoicecontents (docid, itemid, value,
					taxid, prodid, content, count, pdiscount, vdiscount, description, tariffid)
					VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', array_values($args));
				if ($SYSLOG) {
					$args[$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST]] = $customer['id'];
					$SYSLOG->AddMessage(SYSLOG_RES_INVOICECONT, SYSLOG_OPER_ADD, $args,
						array($SYSLOG_RESOURCE_KEYS[SYSLOG_RES_DOC], $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_TAX],
							$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_TARIFF], $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST]));
				}

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
		} else {
			if ($SYSLOG) {
				$cashids = $DB->GetCol('SELECT id FROM cash WHERE docid = ?', array($iid));
				foreach ($cashids as $cashid) {
					$args = array(
						$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CASH] => $cashid,
						$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_DOC] => $iid,
						$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST] => $customer['id'],
					);
					$SYSLOG->AddMessage(SYSLOG_RES_CASH, SYSLOG_OPER_UPDATE, $args,
						array_keys($args));
				}
			}
			$DB->Execute('UPDATE cash SET customerid = ? WHERE docid = ?',
				array($customer['id'], $iid));
		}

		$DB->CommitTrans();

		if (isset($_GET['print']))
			$SESSION->save('invoiceprint', array('invoice' => $id,
				'original' => !empty($_GET['original']) ? 1 : 0,
				'copy' => !empty($_GET['copy']) ? 1 : 0));

		$SESSION->redirect('?m=invoicelist');
		break;
}

$SESSION->save('cnote', $cnote);
$SESSION->save('cnotecontents', $contents);
$SESSION->save('cnoteediterror', $error);

if ($action != '') 
	// redirect needed because we don't want to destroy contents of invoice in order of page refresh
	$SESSION->redirect('?m=invoicenoteedit');

$SMARTY->assign('error', $error);
$SMARTY->assign('contents', $contents);
$SMARTY->assign('cnote', $cnote);
$SMARTY->assign('taxeslist', $taxeslist);
$SMARTY->display('invoice/invoicenoteedit.html');

?>
