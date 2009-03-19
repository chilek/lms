<?php

/*
 * LMS version 1.11-cvs
 *
 *  (C) Copyright 2001-2009 LMS Developers
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

$action = isset($_GET['action']) ? $_GET['action'] : '';
$exists = $LMS->CustomerExists($_GET['id']);

if($exists < 0 && $action != 'recover')
{
	$SESSION->redirect('?m=customerinfo&id='.$_GET['id']);
}
elseif(!$exists)
{
	$SESSION->redirect('?m=customerlist');
}
elseif(isset($_POST['customerdata']) && !isset($_GET['newcontact']))
{
	$customerdata = $_POST['customerdata'];
	foreach($customerdata as $key=>$value)
		if($key != 'uid' && $key != 'contacts')
			$customerdata[$key] = trim($value);

	if($customerdata['lastname']=='')
		$error['customername'] = trans('\'Last/Company Name\' and \'First Name\' fields cannot be empty!');
	
	if($customerdata['address']=='')
		$error['address'] = trans('Address required!');

	if($customerdata['ten'] !='' && !check_ten($customerdata['ten']) && !isset($customerdata['tenwarning']))
	{
		$error['ten'] = trans('Incorrect Tax Exempt Number! If you are sure you want to accept it, then click "Submit" again.');
		$tenwarning = 1;
	}

	if($customerdata['ssn'] != '' && !check_ssn($customerdata['ssn']) && !isset($customerdata['ssnwarning']))
	{
		$error['ssn'] = trans('Incorrect Social Security Number! If you are sure you want to accept it, then click "Submit" again.');
		$ssnwarning = 1;
	}

	if($customerdata['regon'] != '' && !check_regon($customerdata['regon']))
		$error['regon'] = trans('Incorrect Business Registration Number!');

	if($customerdata['icn'] != '' && !check_icn($customerdata['icn']))
		$error['icn'] = trans('Incorrect Identity Card Number!');

	if($customerdata['zip'] !='' && !check_zip($customerdata['zip']) && !isset($customerdata['zipwarning']))
	{
		$error['zip'] = trans('Incorrect ZIP code! If you are sure you want to accept it, then click "Submit" again.');
		$zipwarning = 1;
	}

	if($customerdata['email']!='' && !check_email($customerdata['email']))
		$error['email'] = trans('Incorrect email!');

	if($customerdata['pin'] == '')
		$error['pin'] = trans('PIN code is required!');
	elseif(!eregi('^[0-9]{4,6}$',$customerdata['pin']))
		$error['pin'] = trans('Incorrect PIN code!');

	if($customerdata['status'] == 1 && $LMS->GetCustomerNodesNo($customerdata['id'])) 
		$error['status'] = trans('Interested customers can\'t have computers!');

	foreach($customerdata['uid'] as $idx => $val)
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

	foreach($customerdata['contacts'] as $idx => $val)
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
		if($customerdata['cutoffstop'])
			$customerdata['cutoffstop'] = mktime(23,59,59,date('m'), date('d') + $customerdata['cutoffstop']);
		
		$consent = $DB->GetOne('SELECT consentdate FROM customers WHERE id = ?', array($customerdata['id']));
		
		if(!isset($customerdata['consentdate']))
			$customerdata['consentdate'] = 0;
		elseif($consent)
			$customerdata['consentdate'] = $consent;

		if(!isset($customerdata['divisionid']))
			$customerdata['divisionid'] = 0;
		
		$LMS->CustomerUpdate($customerdata);
		
		$DB->Execute('DELETE FROM imessengers WHERE customerid = ?', array($customerdata['id']));
		if(isset($im))
			foreach($im as $idx => $val)
				$DB->Execute('INSERT INTO imessengers (customerid, uid, type)
					VALUES(?, ?, ?)', array($customerdata['id'], $val, $idx));

		$DB->Execute('DELETE FROM customercontacts WHERE customerid = ?', array($customerdata['id']));
		if(isset($contacts))
			foreach($contacts as $contact)
				$DB->Execute('INSERT INTO customercontacts (customerid, phone, name)
					VALUES(?, ?, ?)', array($customerdata['id'], $contact['phone'], $contact['name']));
		
		if($customerdata['zip'] && $customerdata['stateid'])
		{
			$cstate = $DB->GetOne('SELECT stateid FROM zipcodes WHERE zip = ?', array($customerdata['zip']));
			
			if($cstate === NULL)
				$DB->Execute('INSERT INTO zipcodes (stateid, zip) VALUES (?, ?)',
					array($customerdata['stateid'], $customerdata['zip']));
			elseif($cstate != $customerdata['stateid'])
				$DB->Execute('UPDATE zipcodes SET stateid = ? WHERE zip = ?',
					array($customerdata['stateid'], $customerdata['zip']));
		}

		$SESSION->redirect('?m=customerinfo&id='.$customerdata['id']);
	}
	else
	{
		$olddata = $LMS->GetCustomer($_GET['id']);

		$customerinfo = $customerdata;
		$customerinfo['createdby'] = $olddata['createdby'];
		$customerinfo['modifiedby'] = $olddata['modifiedby'];
		$customerinfo['creationdateh'] = $olddata['creationdateh'];
		$customerinfo['moddateh'] = $olddata['moddateh'];
		$customerinfo['customername'] = $olddata['customername'];
		$customerinfo['balance'] = $olddata['balance'];
		$customerinfo['stateid'] = isset($olddata['stateid']) ? $olddata['stateid'] : 0;
		$customerinfo['zipwarning'] = empty($zipwarning) ? 0 : 1;
		$customerinfo['tenwarning'] = empty($tenwarning) ? 0 : 1;
		$customerinfo['ssnwarning'] = empty($ssnwarning) ? 0 : 1;

		$SMARTY->assign('error',$error);
	}
}
else
{
	$customerinfo = $LMS->GetCustomer($_GET['id']);

	if($customerinfo['cutoffstop'] > mktime(0,0,0))
		$customerinfo['cutoffstop'] = floor(($customerinfo['cutoffstop'] - mktime(23,59,59))/86400);
	else
		$customerinfo['cutoffstop'] = 0;

	if($customerinfo['messengers'])
		foreach($customerinfo['messengers'] as $idx => $val)
			$customerinfo['uid'][$idx] = $val['uid'];

	if(!$customerinfo['contacts'])
	{
		$customerinfo['contacts'][] = array();
	}
	elseif(isset($_POST['customerdata']) && isset($_GET['newcontact']))
	{
    		$customerdata = $_POST['customerdata'];
		$customerdata['contacts'][] = array();
		$customerinfo = array_merge($customerinfo, $customerdata);
	}
}

$layout['pagetitle'] = trans('Customer Edit: $0',$customerinfo['customername']);

if(isset($CONFIG['phpui']['ewx_support']) && chkconfig($CONFIG['phpui']['ewx_support']))
{
        $SMARTY->assign('ewx_channelid',
		$DB->GetOne('SELECT MAX(channelid) FROM ewx_stm_nodes, nodes
                        WHERE nodeid = nodes.id AND ownerid = ?',
			array($customerinfo['id'])));
}

$SMARTY->assign('customernodes',$LMS->GetCustomerNodes($customerinfo['id']));
$SMARTY->assign('balancelist',$LMS->GetCustomerBalanceList($customerinfo['id']));
$SMARTY->assign('tariffs',$LMS->GetTariffs());
$SMARTY->assign('assignments',$LMS->GetCustomerAssignments($_GET['id']));
$SMARTY->assign('customergroups',$LMS->CustomergroupGetForCustomer($_GET['id']));
$SMARTY->assign('othercustomergroups',$LMS->GetGroupNamesWithoutCustomer($_GET['id']));
$SMARTY->assign('allnodegroups', $LMS->GetNodeGroupNames());
$SMARTY->assign('documents',$LMS->GetDocuments($_GET['id'], 10));
$SMARTY->assign('customerinfo',$customerinfo);
$SMARTY->assign('taxeslist',$LMS->GetTaxes());
$SMARTY->assign('cstateslist',$LMS->GetCountryStates());
$SMARTY->assign('countrieslist',$LMS->GetCountries());
$SMARTY->assign('messagelist', $messagelist = $LMS->GetMessages($_GET['id'], 10));
$SMARTY->assign('divisions', $DB->GetAll('SELECT id, shortname, status FROM divisions ORDER BY shortname'));
$SMARTY->assign('eventlist', $LMS->EventSearch(array('customerid'=>$_GET['id']), 'date,desc', true));
$SMARTY->assign('recover',($action == 'recover' ? 1 : 0));
$SMARTY->display('customeredit.html');

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

?>
