<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2017 LMS Developers
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
	if ($LMS->isDocumentPublished($_GET['id']) && !ConfigHelper::checkConfig('privileges.superuser'))
		return;

	$cnote = $LMS->GetInvoiceContent($_GET['id']);

	if (!empty($cnote['cancelled']))
		return;

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
	$cnote['olddeadline'] = $cnote['deadline'] = $cnote['cdate'] + $cnote['paytime'] * 86400;
	$cnote['oldnumber'] = $cnote['number'];
	$cnote['oldnumberplanid'] = $cnote['numberplanid'];

	$SESSION->save('cnote', $cnote);
	$SESSION->save('cnoteid', $cnote['id']);
}

$SESSION->restore('cnotecontents', $contents);
$SESSION->restore('cnote', $cnote);
$SESSION->restore('cnoteediterror', $error);
$itemdata = r_trim($_POST);

$ntempl = docnumber(array(
	'number' => $cnote['number'],
	'template' => $cnote['template'],
	'cdate' => $cnote['cdate'],
	'customerid' => $cnote['customerid'],
));
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

		$oldcdate = $cnote['oldcdate'];
		$oldnumber = $cnote['oldnumber'];
		$oldnumberplanid = $cnote['oldnumberplanid'];

		$oldcnote = $cnote;
		$cnote = null;
		$error = NULL;

		if ($cnote = $_POST['cnote'])
			foreach ($cnote as $key => $val)
				$cnote[$key] = $val;

		$cnote['oldcdate'] = $oldcdate;
		$cnote['oldnumber'] = $oldnumber;
		$cnote['oldnumberplanid'] = $oldnumberplanid;

		$SESSION->restore('cnoteid', $cnote['id']);

		$currtime = time();

		if ($cnote['sdate']) {
			list ($syear, $smonth, $sday) = explode('/', $cnote['sdate']);
			if (checkdate($smonth, $sday, $syear)) {
				$sdate = mktime(23, 59, 59, $smonth, $sday, $syear);
				$cnote['sdate'] = mktime(date('G', $currtime), date('i', $currtime), date('s', $currtime), $smonth, $sday, $syear);
				if ($sdate < $cnote['oldsdate'])
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
				if($cnote['cdate'] < $cnote['oldcdate'])
					$error['cdate'] = trans('Credit note date cannot be earlier than invoice date!');
			} else {
				$error['cdate'] = trans('Incorrect date format! Using current date.');
				$cnote['cdate'] = $currtime;
			}
		} else
			$cnote['cdate'] = $currtime;

		if ($cnote['deadline']) {
			list ($dyear, $dmonth, $dday) = explode('/', $cnote['deadline']);
			if (checkdate($dmonth, $dday, $dyear))
				$cnote['deadline'] = mktime(date('G', $currtime), date('i', $currtime), date('s', $currtime), $dmonth, $dday, $dyear);
			else {
				$error['deadline'] = trans('Incorrect date format!');
				$cnote['deadline'] = $currtime;
				break;
			}
		} else
			$cnote['deadline'] = $currtime;

		if ($cnote['deadline'] < $cnote['cdate'])
			$error['deadline'] = trans('Deadline date should be later than consent date!');

		if ($cnote['number']) {
			if (!preg_match('/^[0-9]+$/', $cnote['number']))
				$error['number'] = trans('Credit note number must be integer!');
			elseif (($cnote['oldcdate'] != $cnote['cdate'] || $cnote['oldnumber'] != $cnote['number']
					|| $cnote['oldnumberplanid'] != $cnote['numberplanid']) && ($docid = $LMS->DocumentExists(array(
					'number' => $cnote['number'],
					'doctype' => DOC_CNOTE,
					'planid' => $cnote['numberplanid'],
					'cdate' => $cnote['cdate'],
					'customerid' => $cnote['customerid'],
				))) > 0 && $docid != $cnote['id'])
				$error['number'] = trans('Credit note number $a already exists!', $cnote['number']);
		}

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
		$deadline = $cnote['deadline'] ? $cnote['deadline'] : $currtime;
		$paytime = $cnote['paytime'] = round(($cnote['deadline'] - $cnote['cdate']) / 86400);
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
			} else {
				if ($contents[$idx]['count'] != $invoicecontents[$idx]['count']
					|| $contents[$idx]['valuebrutto'] != $invoicecontents[$idx]['value'])
					$contents[$idx]['cash'] = round($invoicecontents[$idx]['value'] * $invoicecontents[$idx]['count']
						- $contents[$idx]['valuebrutto'] * $contents[$idx]['count'], 2);
				$contents[$idx]['valuebrutto'] = $invoicecontents[$idx]['value'] - $contents[$idx]['valuebrutto'];
				$contents[$idx]['count'] = $invoicecontents[$idx]['count'] - $contents[$idx]['count'];
			}
		}

		$DB->BeginTrans();

		$use_current_customer_data = isset($cnote['use_current_customer_data']);
		if ($use_current_customer_data)
			$customer = $LMS->GetCustomer($cnote['customerid'], true);

		$division = $DB->GetRow('SELECT name, shortname, address, city, zip, countryid, ten, regon,
			account, inv_header, inv_footer, inv_author, inv_cplace 
			FROM vdivisions WHERE id = ?', array($use_current_customer_data ? $customer['divisionid'] : $cnote['divisionid']));

		if (!$cnote['number'])
			$cnote['number'] = $LMS->GetNewDocumentNumber(array(
				'doctype' => DOC_CNOTE,
				'planid' => $cnote['numberplanid'],
				'cdate' => $cnote['cdate'],
				'customerid' => $cnote['customerid'],
			));
		else {
			if (!preg_match('/^[0-9]+$/', $cnote['number']))
				$error['number'] = trans('Credit note number must be integer!');
			elseif (($cnote['cdate'] != $cnote['oldcdate'] || $cnote['number'] != $cnote['oldnumber']
				|| $cnote['numberplanid'] != $cnote['oldnumberplanid']) && ($docid = $LMS->DocumentExists(array(
					'number' => $cnote['number'],
					'doctype' => DOC_CNOTE,
					'planid' => $cnote['numberplanid'],
					'cdate' => $cnote['cdate'],
					'customerid' => $cnote['customerid'],
				))) > 0 && $docid != $iid)
				$error['number'] = trans('Credit note number $a already exists!', $cnote['number']);

			if ($error) {
				$cnote['number'] = $LMS->GetNewDocumentNumber(array(
					'doctype' => DOC_CNOTE,
					'planid' => $cnote['numberplanid'],
					'cdate' => $cnote['cdate'],
					'customerid' => $cnote['customerid'],
				));
				$error = null;
			}
		}

		$args = array(
			'cdate' => $cdate,
			'sdate' => $sdate,
			'paytime' => $paytime,
			'paytype' => $cnote['paytype'],
			SYSLOG::RES_CUST => $cnote['customerid'],
			'name' => $use_current_customer_data ? $customer['customername'] : $cnote['name'],
			'address' => $use_current_customer_data ? (($customer['postoffice'] && $customer['postoffice'] != $customer['city'] && $customer['street']
				? $customer['postoffice'] . ', ' : '') . $customer['address']) : $cnote['address'],
			'ten' => $use_current_customer_data ? $customer['ten'] : $cnote['ten'],
			'ssn' => $use_current_customer_data ? $customer['ssn'] : $cnote['ssn'],
			'zip' => $use_current_customer_data ? $customer['zip'] : $cnote['zip'],
			'city' => $use_current_customer_data ? ($customer['postoffice'] ? $customer['postoffice'] : $customer['city'])
				: $cnote['city'],
			SYSLOG::RES_COUNTRY => $use_current_customer_data ? (empty($customer['countryid']) ? null : $customer['countryid'])
				: (empty($cnote['countryid']) ? null : $cnote['countryid']),
			'reason' => $cnote['reason'],
			SYSLOG::RES_DIV => $use_current_customer_data ? $customer['divisionid'] : $cnote['divisionid'],
			'div_name' => ($division['name'] ? $division['name'] : ''),
			'div_shortname' => ($division['shortname'] ? $division['shortname'] : ''),
			'div_address' => ($division['address'] ? $division['address'] : ''), 
			'div_city' => ($division['city'] ? $division['city'] : ''), 
			'div_zip' => ($division['zip'] ? $division['zip'] : ''),
			'div_' . SYSLOG::getResourceKey(SYSLOG::RES_COUNTRY) => ($division['countryid'] ? $division['countryid'] : 0),
			'div_ten'=> ($division['ten'] ? $division['ten'] : ''),
			'div_regon' => ($division['regon'] ? $division['regon'] : ''),
			'div_account' => ($division['account'] ? $division['account'] : ''),
			'div_inv_header' => ($division['inv_header'] ? $division['inv_header'] : ''),
			'div_inv_footer' => ($division['inv_footer'] ? $division['inv_footer'] : ''),
			'div_inv_author' => ($division['inv_author'] ? $division['inv_author'] : ''),
			'div_inv_cplace' => ($division['inv_cplace'] ? $division['inv_cplace'] : ''),
		);
		$args['number'] = $cnote['number'];
		if ($cnote['numberplanid'])
			$args['fullnumber'] = docnumber(array(
				'number' => $cnote['number'],
				'template' => $DB->GetOne('SELECT template FROM numberplans WHERE id = ?', array($cnote['numberplanid'])),
				'cdate' => $cnote['cdate'],
				'customerid' => $cnote['customerid'],
			));
		else
			$args['fullnumber'] = null;
		$args[SYSLOG::RES_NUMPLAN] = $cnote['numberplanid'];
		$args[SYSLOG::RES_DOC] = $iid;

		$DB->Execute('UPDATE documents SET cdate = ?, sdate = ?, paytime = ?, paytype = ?, customerid = ?,
				name = ?, address = ?, ten = ?, ssn = ?, zip = ?, city = ?, countryid = ?, reason = ?, divisionid = ?,
				div_name = ?, div_shortname = ?, div_address = ?, div_city = ?, div_zip = ?, div_countryid = ?,
				div_ten = ?, div_regon = ?, div_account = ?, div_inv_header = ?, div_inv_footer = ?,
				div_inv_author = ?, div_inv_cplace = ?, number = ?, fullnumber = ?, numberplanid = ?
				WHERE id = ?', array_values($args));
		if ($SYSLOG)
			$SYSLOG->AddMessage(SYSLOG::RES_DOC, SYSLOG::OPER_UPDATE, $args,
				array('div_' . SYSLOG::getResourceKey(SYSLOG::RES_COUNTRY)));

		if (!$cnote['closed']) {
			if ($SYSLOG) {
				$cashids = $DB->GetCol('SELECT id FROM cash WHERE docid = ?', array($iid));
				foreach ($cashids as $cashid) {
					$args = array(
						SYSLOG::RES_CASH => $cashid,
						SYSLOG::RES_DOC => $iid,
						SYSLOG::RES_CUST => $cnote['customerid'],
					);
					$SYSLOG->AddMessage(SYSLOG::RES_CASH, SYSLOG::OPER_DELETE, $args);
				}
				$itemids = $DB->GetCol('SELECT itemid FROM invoicecontents WHERE docid = ?', array($iid));
				foreach ($itemids as $itemid) {
					$args = array(
						SYSLOG::RES_DOC => $iid,
						SYSLOG::RES_CUST => $cnote['customerid'],
						'itemid' => $itemid,
					);
					$SYSLOG->AddMessage(SYSLOG::RES_INVOICECONT, SYSLOG::OPER_DELETE, $args);
				}
			}
			$DB->Execute('DELETE FROM invoicecontents WHERE docid = ?', array($iid));
			$DB->Execute('DELETE FROM cash WHERE docid = ?', array($iid));

			$itemid=0;
			foreach ($contents as $idx => $item) {
				$itemid++;

				$args = array(
					SYSLOG::RES_DOC => $iid,
					'itemid' => $itemid,
					'value' => str_replace(',', '.', -$item['valuebrutto']),
					SYSLOG::RES_TAX => $item['taxid'],
					'prodid' => $item['prodid'],
					'content' => $item['content'],
					'count' => $item['count'],
					'pdiscount' => str_replace(',', '.', $item['pdiscount']),
					'vdiscount' => str_replace(',', '.', $item['vdiscount']),
					'name' => $item['name'],
					SYSLOG::RES_TARIFF => empty($item['tariffid']) ? null : $item['tariffid'],
				);
				$DB->Execute('INSERT INTO invoicecontents (docid, itemid, value,
					taxid, prodid, content, count, pdiscount, vdiscount, description, tariffid)
					VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', array_values($args));
				if ($SYSLOG) {
					$args[SYSLOG::RES_CUST] = $cnote['customerid'];
					$SYSLOG->AddMessage(SYSLOG::RES_INVOICECONT, SYSLOG::OPER_ADD, $args);
				}

				$LMS->AddBalance(array(
					'time' => $cdate,
					'value' => $item['cash'],
					'taxid' => $item['taxid'],
					'customerid' => $cnote['customerid'],
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
						SYSLOG::RES_CASH => $cashid,
						SYSLOG::RES_DOC => $iid,
						SYSLOG::RES_CUST => $cnote['customerid'],
					);
					$SYSLOG->AddMessage(SYSLOG::RES_CASH, SYSLOG::OPER_UPDATE, $args);
				}
			}
			$DB->Execute('UPDATE cash SET customerid = ? WHERE docid = ?',
				array($cnote['customerid'], $iid));
		}

		$DB->CommitTrans();

		if (isset($_GET['print']))
			$SESSION->save('invoiceprint', array('invoice' => $iid,
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

$args = array(
	'doctype' => DOC_CNOTE,
	'cdate' => date('Y/m', $cnote['cdate']),
	'customerid' => $cnote['customerid'],
	'division' => $DB->GetOne('SELECT divisionid FROM customers WHERE id = ?', array($cnote['customerid'])),
);
$SMARTY->assign('numberplanlist', $LMS->GetNumberPlans($args));

$SMARTY->display('invoice/invoicenoteedit.html');

?>
