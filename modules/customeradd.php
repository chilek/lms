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

if(isset($_GET['ajax'])) 
{
	header('Content-type: text/plain');
	$search = urldecode(trim($_GET['what']));

	switch($_GET['mode'])
	{
	        case 'address':
			$mode='address';
			if ($CONFIG['database']['type'] == 'mysql' || $CONFIG['database']['type'] == 'mysqli') 
				$mode = 'substring(address from 1 for length(address)-locate(\' \',reverse(address))+1)';
			elseif($CONFIG['database']['type'] == 'postgres') 
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
	$candidates = $DB->GetAll('SELECT '.$mode.' as item, count(id) as entries FROM customers WHERE '.$mode.' != \'\' AND lower('.$mode.') ?LIKE? lower(\'%'.$search.'%\') GROUP BY item ORDER BY entries desc, item asc');
	$eglible=array(); $descriptions=array();
	if ($candidates)
	foreach($candidates as $idx => $row) {
		$eglible[$row['item']] = escape_js($row['item']);
		$descriptions[$row['item']] = escape_js($row['entries'].' '.trans('entries'));
	}
	header('Content-type: text/plain');
	if ($eglible) {
		print preg_replace('/$/',"\");\n","this.eligible = new Array(\"".implode('","',$eglible));
		print preg_replace('/$/',"\");\n","this.descriptions = new Array(\"".implode('","',$descriptions));
	} else {
		print "false;\n";
	}
	exit;
}

$customeradd = array();

if(isset($_POST['customeradd']) && isset($_GET['newcontact']))
{
	$customeradd = $_POST['customeradd'];
	$customeradd['contacts'][] = array();
}
elseif(isset($_POST['customeradd']))
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
		$error['customername'] = trans('\'Last/Company Name\' and \'First Name\' fields cannot be empty!');
	
	if($customeradd['address'] == '')
		$error['address'] = trans('Address required!');
	
	if($customeradd['ten'] !='' && !check_ten($customeradd['ten']))
		$error['ten'] = trans('Incorrect Tax Exempt Number!');

	if($customeradd['ssn'] != '' && !check_ssn($customeradd['ssn']))
		$error['ssn'] = trans('Incorrect Social Security Number!');

	if($customeradd['icn'] != '' && !check_icn($customeradd['icn']))
		$error['icn'] = trans('Incorrect Identity Card Number!');

	if($customeradd['regon'] != '' && !check_regon($customeradd['regon']))
		$error['regon'] = trans('Incorrect Business Registration Number!');
		
	if($customeradd['zip'] !='' && !check_zip($customeradd['zip']))
		$error['zip'] = trans('Incorrect ZIP code!');

	if($customeradd['pin'] == '')
		$error['pin'] = trans('PIN code is required!');
        elseif(!eregi('^[0-9]{4,6}$',$customeradd['pin']))
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
		
		if($name && !$phone)
			$error['contact'.$idx] = trans('Phone number is required!');
		elseif($phone) 
			$contacts[] = array('name' => $name, 'phone' => $phone);
	}

	if(!$error)
	{
		if($customeradd['cutoffstop'])
			$customeradd['cutoffstop'] = mktime(23,59,59,date('m'), date('d') + $customeradd['cutoffstop']);

		$id = $LMS->CustomerAdd($customeradd);

		if(isset($im) && $id)
			foreach($im as $idx => $val)
				$DB->Execute('INSERT INTO imessengers (customerid, uid, type)
					VALUES(?, ?, ?)', array($id, $val, $idx));

		if(isset($contacts) && $id)
			foreach($contacts as $contact)
				$DB->Execute('INSERT INTO customercontacts (customerid, phone, name)
					VALUES(?, ?, ?)', array($id, $contact['phone'], $contact['name']));

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

if(!isset($customeradd['zip']) && isset($CONFIG['phpui']['default_zip']))
	$customeradd['zip'] = $CONFIG['phpui']['default_zip'];
if(!isset($customeradd['city']) && isset($CONFIG['phpui']['default_city']))
	$customeradd['city'] = $CONFIG['phpui']['default_city'];
if(!isset($customeradd['address']) && isset($CONFIG['phpui']['default_address']))
	$customeradd['address'] = $CONFIG['phpui']['default_address'];

$layout['pagetitle'] = trans('New Customer');

$SMARTY->assign('customeradd',$customeradd);
$SMARTY->assign('error',$error);
$SMARTY->display('customeradd.html');

?>
