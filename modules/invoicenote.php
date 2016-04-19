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

$numberplanlist = $LMS->GetNumberPlans(DOC_CNOTE);

$action = isset($_GET['action']) ? $_GET['action'] : NULL;

if (isset($_GET['id']) && $action == 'init')
{
	if ($DB->GetOne('SELECT id FROM documents WHERE reference = ?', array($_GET['id'])))
		$SESSION->redirect('?m=invoicelist');
	$invoice = $LMS->GetInvoiceContent($_GET['id']);

	$taxeslist = $LMS->GetTaxes($invoice['cdate'],$invoice['cdate']);

	foreach ($invoice['content'] as $item)
	{
		$nitem['tariffid']	= $item['tariffid'];
		$nitem['name']		= $item['description'];
		$nitem['prodid']	= $item['prodid'];
		$nitem['count']		= str_replace(',', '.', $item['count']);
		$nitem['discount']	= str_replace(',', '.', $item['pdiscount']);
		$nitem['pdiscount']	= str_replace(',', '.', $item['pdiscount']);
		$nitem['vdiscount']	= str_replace(',', '.', $item['vdiscount']);
		$nitem['jm']		= str_replace(',', '.', $item['content']);
		$nitem['valuenetto']	= str_replace(',', '.', $item['basevalue']);
		$nitem['valuebrutto']	= str_replace(',', '.', $item['value']);
		$nitem['s_valuenetto']	= str_replace(',', '.', $item['totalbase']);
		$nitem['s_valuebrutto']	= str_replace(',', '.', $item['total']);
		$nitem['tax']		= isset($taxeslist[$item['taxid']]) ? $taxeslist[$item['taxid']]['label'] : 0;
		$nitem['taxid']		= $item['taxid'];
		$nitem['itemid']	= $item['itemid'];
		$invoicecontents[$nitem['itemid']] = $nitem;
	}

	$cnote['numberplanid'] = $DB->GetOne('SELECT id FROM numberplans WHERE doctype = ? AND isdefault = 1', array(DOC_CNOTE));
	$currtime = time();
	$cnote['cdate'] = $currtime;
	$cnote['sdate'] = $currtime;
	$cnote['reason'] = '';
	$cnote['paytype'] = $invoice['paytype'];

	$t = $invoice['cdate'] + $invoice['paytime'] * 86400;
	$deadline = mktime(23, 59, 59, date('m',$t), date('d',$t), date('Y',$t));

	if($cnote['cdate'] > $deadline)
		$cnote['paytime'] = 0;
	else
		$cnote['paytime'] = floor(($deadline - $cnote['cdate']) / 86400);

	$cnote['use_current_division'] = true;

	$SESSION->save('cnote', $cnote);
	$SESSION->save('invoice', $invoice);
	$SESSION->save('invoiceid', $invoice['id']);
	$SESSION->save('invoicecontents', $invoicecontents);
}

$SESSION->restore('invoicecontents', $contents);
$SESSION->restore('invoice', $invoice);
$SESSION->restore('cnote', $cnote);
$SESSION->restore('cnoteerror', $error);

$taxeslist = $LMS->GetTaxes($invoice['cdate'],$invoice['cdate']);

$ntempl = docnumber($invoice['number'], $invoice['template'], $invoice['cdate']);
$layout['pagetitle'] = trans('Credit Note for Invoice: $a', $ntempl);

switch($action)
{
	case 'deletepos':
		if ($invoice['closed'])
			break;
		$contents[$_GET['itemid']]['deleted'] = true;
		break;

	case 'recoverpos':
		if ($invoice['closed'])
			break;
		$contents[$_GET['itemid']]['deleted'] = false;
		break;

	case 'setheader':

		$cnote = NULL;
		$error = NULL;

		if($cnote = $_POST['cnote'])
			foreach($cnote as $key => $val)
				$cnote[$key] = $val;

		$cnote['paytime'] = sprintf('%d', $cnote['paytime']);

		if($cnote['paytime'] < 0)
			$cnote['paytime'] = 14;

		$currtime = time();

		if($cnote['sdate'])
		{
			list($syear, $smonth, $sday) = explode('/', $cnote['sdate']);
			if(checkdate($smonth, $sday, $syear))
			{
				$sdate = mktime(23, 59, 59, $smonth, $sday, $syear);
				$cnote['sdate'] = mktime(date('G', $currtime), date('i', $currtime), date('s', $currtime), $smonth, $sday, $syear);
				if($sdate < $invoice['sdate'])
				{
					$error['sdate'] = trans('Credit note sale date cannot be earlier than invoice sale date!');
				}
			}
			else
			{
				$error['sdate'] = trans('Incorrect date format! Using current date.');
				$cnote['sdate'] = $currtime;
			}
		}
		else
			$cnote['sdate'] = $currtime;

		if($cnote['cdate'])
		{
			list($year, $month, $day) = explode('/', $cnote['cdate']);
			if(checkdate($month, $day, $year))
			{
				$cnote['cdate'] = mktime(date('G', $currtime), date('i', $currtime), date('s', $currtime), $month, $day, $year);
				if($cnote['cdate'] < $invoice['cdate'])
				{
					$error['cdate'] = trans('Credit note date cannot be earlier than invoice date!');
				}
			}
			else
			{
				$error['cdate'] = trans('Incorrect date format! Using current date.');
				$cnote['cdate'] = $currtime;
			}
		}
		else
			$cnote['cdate'] = $currtime;

		if($cnote['number'])
		{
			if(!preg_match('/^[0-9]+$/', $cnote['number']))
			        $error['number'] = trans('Credit note number must be integer!');
			elseif($LMS->DocumentExists($cnote['number'], DOC_CNOTE, $cnote['numberplanid'], $cnote['cdate']))
			        $error['number'] = trans('Credit note number $a already exists!', $cnote['number']);
		}

		// finally check if selected customer can use selected numberplan
		$divisionid = !empty($cnote['use_current_division']) ? $invoice['current_divisionid'] : $invoice['divisionid'];

		if($cnote['numberplanid'] && !$DB->GetOne('SELECT 1 FROM numberplanassignments
			WHERE planid = ? AND divisionid = ?', array($cnote['numberplanid'], $divisionid)))
		{
		        $error['number'] = trans('Selected numbering plan doesn\'t match customer\'s division!');
		}

	break;

	case 'save':

		if (empty($contents) || empty($cnote))
			break;

		$SESSION->restore('invoiceid', $invoice['id']);
		$newcontents = r_trim($_POST);

		foreach ($contents as $item) {
			$idx = $item['itemid'];
			$contents[$idx]['taxid'] = isset($newcontents['taxid'][$idx]) ? $newcontents['taxid'][$idx] : $item['taxid'];
			$contents[$idx]['prodid'] = isset($newcontents['prodid'][$idx]) ? $newcontents['prodid'][$idx] : $item['prodid'];
			$contents[$idx]['jm'] = isset($newcontents['jm'][$idx]) ? $newcontents['jm'][$idx] : $item['jm'];
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
				$contents[$idx]['cash'] = round($item['valuebrutto'] * $item['count'],2);
				$contents[$idx]['count'] = 0;
			}
			elseif ($contents[$idx]['count'] != $item['count']
				|| $contents[$idx]['valuebrutto'] != $item['valuebrutto'])
				$contents[$idx]['cash'] = round($item['valuebrutto'] * $item['count'] - $contents[$idx]['valuebrutto'] * $contents[$idx]['count'], 2);

			$contents[$idx]['valuebrutto'] = $contents[$idx]['valuebrutto'] - $item['valuebrutto'];
			$contents[$idx]['count'] = $contents[$idx]['count'] - $item['count'];
		}

		$DB->BeginTrans();
		$DB->LockTables(array('documents', 'numberplans', 'divisions'));

		if(!isset($cnote['number']) || !$cnote['number'])
			$cnote['number'] = $LMS->GetNewDocumentNumber(DOC_CNOTE, $cnote['numberplanid'], $cnote['cdate']);
		else {
			if (!preg_match('/^[0-9]+$/', $cnote['number']))
				$error['number'] = trans('Credit note number must be integer!');
			elseif ($LMS->DocumentExists($cnote['number'], DOC_CNOTE, $cnote['numberplanid'], $cnote['cdate']))
				$error['number'] = trans('Credit note number $a already exists!', $cnote['number']);

			if ($error)
				$cnote['number'] = $LMS->GetNewDocumentNumber(DOC_CNOTE, $cnote['numberplanid'], $cnote['cdate']);
		}

		$division = $DB->GetRow('SELECT name, shortname, address, city, zip, countryid, ten, regon,
						account, inv_header, inv_footer, inv_author, inv_cplace 
						FROM divisions WHERE id = ?',
						array(!empty($cnote['use_current_division']) ? $invoice['current_divisionid'] : $invoice['divisionid']));

		if ($cnote['numberplanid'])
			$fullnumber = docnumber($cnote['number'],
				$DB->GetOne('SELECT template FROM numberplans WHERE id = ?', array($cnote['numberplanid'])),
				$cnote['cdate']);
		else
			$fullnumber = null;

		$args = array(
			'number' => $cnote['number'],
			$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NUMPLAN] => $cnote['numberplanid'] ? $cnote['numberplanid'] : 0,
			'type' => DOC_CNOTE,
			'cdate' => $cnote['cdate'],
			'sdate' => $cnote['sdate'],
			'paytime' => $cnote['paytime'],
			'paytype' => $cnote['paytype'],
			$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_USER] => $AUTH->id,
			$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST] => $invoice['customerid'],
			'name' => $invoice['name'],
			'address' => $invoice['address'],
			'ten' => $invoice['ten'],
			'ssn' => $invoice['ssn'],
			'zip' => $invoice['zip'],
			'city' => $invoice['city'],
			$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_COUNTRY] => $invoice['countryid'],
			'reference' => $invoice['id'],
			'reason' => $cnote['reason'],
			$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_DIV] => !empty($cnote['use_current_division']) ? $invoice['current_divisionid'] : $invoice['divisionid'],
			'div_name' => $division['name'] ? $division['name'] : '',
			'div_shortname' => $division['shortname'] ? $division['shortname'] : '',
			'div_address' => $division['address'] ? $division['address'] : '',
			'div_city' => $division['city'] ? $division['city'] : '',
			'div_zip' => $division['zip'] ? $division['zip'] : '',
			'div_' . $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_COUNTRY] => $division['countryid'] ? $division['countryid'] : 0,
			'div_ten' => $division['ten'] ? $division['ten'] : '',
			'div_regon' => $division['regon'] ? $division['regon'] : '',
			'div_account' => $division['account'] ? $division['account'] : '',
			'div_inv_header' => $division['inv_header'] ? $division['inv_header'] : '',
			'div_inv_footer' => $division['inv_footer'] ? $division['inv_footer'] : '',
			'div_inv_author' => $division['inv_author'] ? $division['inv_author'] : '',
			'div_inv_cplace' => $division['inv_cplace'] ? $division['inv_cplace'] : '',
			'fullnumber' => $fullnumber,
		);
		$DB->Execute('INSERT INTO documents (number, numberplanid, type, cdate, sdate, paytime, paytype,
				userid, customerid, name, address, ten, ssn, zip, city, countryid, reference, reason, divisionid,
				div_name, div_shortname, div_address, div_city, div_zip, div_countryid, div_ten, div_regon,
				div_account, div_inv_header, div_inv_footer, div_inv_author, div_inv_cplace, fullnumber)
				VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
					?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', array_values($args));

		$id = $DB->GetOne('SELECT id FROM documents WHERE number = ? AND cdate = ? AND type = ?',
			array($cnote['number'], $cnote['cdate'], DOC_CNOTE));

		if ($SYSLOG) {
			$args[$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_DOC]] = $id;
			unset($args[$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_USER]]);
			$SYSLOG->AddMessage(SYSLOG_RES_DOC, SYSLOG_OPER_ADD, $args,
				array($SYSLOG_RESOURCE_KEYS[SYSLOG_RES_DOC], $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NUMPLAN],
					$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST], $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_COUNTRY],
					$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_DIV], 'div_' . $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_DIV]));
		}

		$DB->UnLockTables();

		foreach($contents as $idx => $item)
		{
			$item['valuebrutto'] = str_replace(',', '.', $item['valuebrutto']);
			$item['count'] = str_replace(',', '.', $item['count']);
			$item['pdiscount'] = str_replace(',', '.', $item['pdiscount']);
			$item['vdiscount'] = str_replace(',', '.', $item['vdiscount']);

			$args = array(
				$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_DOC] => $id,
				'itemid' => $idx,
				'value' => $item['valuebrutto'],
				$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_TAX] => $item['taxid'],
				'prodid' => $item['prodid'],
				'content' => $item['jm'],
				'count' => $item['count'],
				'pdiscount' => $item['pdiscount'],
				'vdiscount' => $item['vdiscount'],
				'description' => $item['name'],
				$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_TARIFF] => $item['tariffid'],
			);
			$DB->Execute('INSERT INTO invoicecontents (docid, itemid, value, taxid, prodid, content, count, pdiscount, vdiscount, description, tariffid)
					VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', array_values($args));

			if ($SYSLOG) {
				$args[$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST]] = $invoice['customerid'];
				$SYSLOG->AddMessage(SYSLOG_RES_INVOICECONT, SYSLOG_OPER_ADD, $args,
					array($SYSLOG_RESOURCE_KEYS[SYSLOG_RES_DOC], $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_TAX],
						$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_TARIFF], $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST]));
			}

			if (isset($item['cash']) && $item['cash'] != 0) {
				$args = array(
					'time' => $cnote['cdate'],
					$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_USER] => $AUTH->id,
					'value' => str_replace(',','.',$item['cash']),
					$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_TAX] => $item['taxid'],
					$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST] => $invoice['customerid'],
					'comment' => $item['name'],
					$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_DOC] => $id,
					'itemid' => $idx,
				);
				$DB->Execute('INSERT INTO cash (time, userid, value, taxid, customerid, comment, docid, itemid)
					VALUES (?, ?, ?, ?, ?, ?, ?, ?)', array_values($args));
				if ($SYSLOG) {
					unset($args[$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_USER]]);
					$args[$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CASH]] = $DB->GetLastInsertID('cash');
					$SYSLOG->AddMessage(SYSLOG_RES_CASH, SYSLOG_OPER_ADD, $args,
						array($SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CASH], $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_DOC],
							$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_TAX], $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST]));
				}
			}
		}

		$DB->CommitTrans();

		$SESSION->remove('invoice');
		$SESSION->remove('invoiceid');
		$SESSION->remove('cnote');
		$SESSION->remove('invoicecontents');
		$SESSION->remove('cnoteerror');

		if (isset($_GET['print']))
			$SESSION->save('invoiceprint', array('invoice' => $id,
				'original' => !empty($_GET['original']) ? 1 : 0,
				'copy' => !empty($_GET['copy']) ? 1 : 0));

		$SESSION->redirect('?m=invoicelist');
		break;
}

$SESSION->save('invoice', $invoice);
$SESSION->save('cnote', $cnote);
$SESSION->save('invoicecontents', $contents);
$SESSION->save('cnoteerror', $error);

if ($action != '')
{
	// redirect, to not prevent from invoice break with the refresh
	$SESSION->redirect('?m=invoicenote');
}

$SMARTY->assign('error', $error);
$SMARTY->assign('contents', $contents);
$SMARTY->assign('cnote', $cnote);
$SMARTY->assign('invoice', $invoice);
$SMARTY->assign('taxeslist', $taxeslist);
$SMARTY->assign('numberplanlist', $numberplanlist);
$SMARTY->display('invoice/invoicenote.html');

?>
