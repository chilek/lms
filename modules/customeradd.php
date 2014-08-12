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

if(isset($_GET['ajax'])) 
{
	header('Content-type: text/plain');
	$search = urldecode(trim($_GET['what']));

	switch($_GET['mode'])
	{
	        case 'address':
			$mode='address';
                        $database_type = ConfigHelper::getConfig('database.type');
			if ($database_type == 'mysql' || $database_type == 'mysqli') 
				$mode = 'substring(address from 1 for length(address)-locate(\' \',reverse(address))+1)';
			elseif($database_type == 'postgres') 
				$mode = 'substring(address from \'^.* \')';
		break;
	        case 'zip':
			$mode='zip';
		break;
	        case 'city':
			$mode='city';
		break;
	}

	if (!isset($mode)) { print 'false;'; exit; }

	$candidates = $DB->GetAll('SELECT '.$mode.' as item, count(id) as entries
	    FROM customers
	    WHERE '.$mode.' != \'\' AND lower('.$mode.') ?LIKE? lower(' . $DB->Escape('%'.$search.'%') . ')
	    GROUP BY item
	    ORDER BY entries desc, item asc
	    LIMIT 15');

	$eglible=array(); $descriptions=array();
	if ($candidates)
	foreach($candidates as $idx => $row) {
		$eglible[$row['item']] = escape_js($row['item']);
		$descriptions[$row['item']] = escape_js($row['entries'].' '.trans('entries'));
	}
	if ($eglible) {
		print "this.eligible = [\"".implode('","',$eglible)."\"];\n";
		print "this.descriptions = [\"".implode('","',$descriptions)."\"];\n";
	} else {
		print "false;\n";
	}
	exit;
}

$customeradd = array();

if (isset($_POST['customeradd']))
{
	$customeradd = $_POST['customeradd'];

	if(sizeof($customeradd))
		foreach($customeradd as $key => $value)
			if($key != 'uid' && $key != 'contacts')
				$customeradd[$key] = trim($value);

	if($customeradd['name'] == '' && $customeradd['lastname'] == '' && $customeradd['address'] == '' && $customeradd['email'] == '')
	{
		$SESSION->redirect('?m=customeradd');
	}

	if($customeradd['lastname'] == '')
		$error['lastname'] = trans('Last/Company name cannot be empty!');

	if($customeradd['name'] == '' && !$customeradd['type'])
		$error['name'] = trans('First name cannot be empty!');
	
	if($customeradd['address'] == '')
		$error['address'] = trans('Address required!');
	
	if($customeradd['ten'] !='' && !check_ten($customeradd['ten']) && !isset($customeradd['tenwarning']))
	{
		$error['ten'] = trans('Incorrect Tax Exempt Number! If you are sure you want to accept it, then click "Submit" again.');
		$customeradd['tenwarning'] = 1;
	}

	if($customeradd['ssn'] != '' && !check_ssn($customeradd['ssn']) && !isset($customeradd['ssnwarning']))
	{
		$error['ssn'] = trans('Incorrect Social Security Number! If you are sure you want to accept it, then click "Submit" again.');
		$customeradd['ssnwarning'] = 1;
	}

	if($customeradd['icn'] != '' && !check_icn($customeradd['icn']))
		$error['icn'] = trans('Incorrect Identity Card Number!');

	if($customeradd['regon'] != '' && !check_regon($customeradd['regon']))
		$error['regon'] = trans('Incorrect Business Registration Number!');

	if($customeradd['zip'] !='' && !check_zip($customeradd['zip']) && !isset($customeradd['zipwarning']))
	{
		$error['zip'] = trans('Incorrect ZIP code! If you are sure you want to accept it, then click "Submit" again.');
		$customeradd['zipwarning'] = 1;
	}
	if($customeradd['post_zip'] !='' && !check_zip($customeradd['post_zip']) && !isset($customeradd['post_zipwarning']))
	{
		$error['post_zip'] = trans('Incorrect ZIP code! If you are sure you want to accept it, then click "Submit" again.');
		$customeradd['post_zipwarning'] = 1;
	}

	if($customeradd['pin'] == '')
		$error['pin'] = trans('PIN code is required!');
        elseif(!preg_match('/^[0-9]{4,6}$/', $customeradd['pin']))
	        $error['pin'] = trans('Incorrect PIN code!');

	if($customeradd['email']!='' && !check_email($customeradd['email']))
		$error['email'] = trans('Incorrect email!');

	foreach($customeradd['uid'] as $idx => $val)
	{
		$val = trim($val);
		switch($idx)
		{
			case IM_GG:
				if($val!='' && !check_gg($val))
					$error['gg'] = trans('Incorrect IM uin!');
			break;
			case IM_YAHOO:
				if($val!='' && !check_yahoo($val))
					$error['yahoo'] = trans('Incorrect IM uin!');
			break;
			case IM_SKYPE:
				if($val!='' && !check_skype($val))
					$error['skype'] = trans('Incorrect IM uin!');
			break;
		}

		if($val) $im[$idx] = $val;
	}

	foreach($customeradd['contacts'] as $idx => $val)
	{
		$phone = trim($val['phone']);
		$name = trim($val['name']);
		$type = !empty($val['type']) ? array_sum($val['type']) : NULL;

		$customeradd['contacts'][$idx]['type'] = $type;

		if($name && !$phone)
			$error['contact'.$idx] = trans('Phone number is required!');
		elseif ($phone)
			$contacts[] = array('name' => $name, 'phone' => $phone, 'type' => $type);
	}

	if ($customeradd['cutoffstop'] == '')
		$cutoffstop = 0;
	elseif (check_date($customeradd['cutoffstop'])) {
			list ($y, $m, $d) = explode('/', $customeradd['cutoffstop']);
			if (checkdate($m, $d, $y))
				$cutoffstop = mktime(23, 59, 59, $m, $d, $y);
			else
				$error['cutoffstop'] = trans('Incorrect date of cutoff suspending!');
	} else
		$error['cutoffstop'] = trans('Incorrect date of cutoff suspending!');

	if (!$error) {
		$customeradd['cutoffstop'] = $cutoffstop;

		if(!isset($customeradd['consentdate']))
			$customeradd['consentdate'] = 0;
		if(!isset($customeradd['divisionid']))
			$customeradd['divisionid'] = 0;

		$id = $LMS->CustomerAdd($customeradd);

		if(isset($im) && $id)
			foreach($im as $idx => $val) {
				$DB->Execute('INSERT INTO imessengers (customerid, uid, type)
					VALUES(?, ?, ?)', array($id, $val, $idx));
				if ($SYSLOG) {
					$contactid = $DB->GetLastInsertID('imessengers');
					$args = array(
						$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_IMCONTACT] => $contactid,
						$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST] => $id,
						'uid' => $val,
						'type' => $idx
					);
					$SYSLOG->AddMessage(SYSLOG_RES_IMCONTACT, SYSLOG_OPER_ADD, $args,
						array($SYSLOG_RESOURCE_KEYS[SYSLOG_RES_IMCONTACT],
							$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST]));
				}
			}

		if(isset($contacts) && $id)
			foreach($contacts as $contact) {
				$DB->Execute('INSERT INTO customercontacts (customerid, phone, name, type)
					VALUES(?, ?, ?, ?)', array($id, $contact['phone'], $contact['name'], $contact['type']));
				if ($SYSLOG) {
					$contactid = $DB->GetLastInsertID('customercontacts');
					$args = array(
						$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUSTCONTACT] => $contactid,
						$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST] => $id,
						'phone' => $contact['phone'],
						'name' => $contact['name'],
						'type' => $contact['type']
					);
					$SYSLOG->AddMessage(SYSLOG_RES_CUSTCONTACT, SYSLOG_OPER_ADD, $args,
						array($SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUSTCONTACT],
							$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST]));
				}
			}

		if(!isset($customeradd['reuse']))
		{
			$SESSION->redirect('?m=customerinfo&id='.$id);
		}

		$reuse['status'] = $customeradd['status'];
		$reuse['contacts'][] = array();
		unset($customeradd);
		$customeradd = $reuse;
		$customeradd['reuse'] = '1';
	}
}
else
{
	$customeradd['contacts'][] = array();
}

$default_zip = ConfigHelper::getConfig('phpui.default_zip');
$default_city = ConfigHelper::getConfig('phpui.default_city');
$default_address = ConfigHelper::getConfig('phpui.default_address');

if (!isset($customeradd['zip']) && $default_zip) {
	$customeradd['zip'] = $default_zip;
} if (!isset($customeradd['city']) && $default_city) {
	$customeradd['city'] = $default_city;
} if (!isset($customeradd['address']) && $default_address) {
	$customeradd['address'] = $default_address;
}

$layout['pagetitle'] = trans('New Customer');

$SMARTY->assign('cstateslist', $LMS->GetCountryStates());
$SMARTY->assign('countrieslist', $LMS->GetCountries());
$SMARTY->assign('divisions', $DB->GetAll('SELECT id, shortname, status FROM divisions ORDER BY shortname'));
$SMARTY->assign('customeradd', $customeradd);
$SMARTY->assign('error', $error);
$SMARTY->display('customeradd.html');

?>
