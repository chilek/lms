<?php

/*
 * LMS version 1.11-cvs
 *
 *  (C) Copyright 2001-2007 LMS Developers
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

if($LMS->CustomerExists($_GET['id']) < 0 && $action != 'recover')
{
	$SESSION->redirect('?m=customerinfo&id='.$_GET['id']);
}
elseif(! $LMS->CustomerExists($_GET['id']))
{
	$SESSION->redirect('?m=customerlist');
}
elseif($action == 'customergroupdelete')
{
	$LMS->CustomerAssignmentDelete(array('customerid' => $_GET['id'], 'customergroupid' => $_GET['customergroupid']));
	$SESSION->redirect('?m=customerinfo&id='.$_GET['id']);
}
elseif($action == 'customergroupadd')
{
	if ($LMS->CustomerGroupExists($_POST['customergroupid']))
		$LMS->CustomerAssignmentAdd(array('customerid' => $_GET['id'], 'customergroupid' => $_POST['customergroupid']));
	$SESSION->redirect('?m=customerinfo&id='.$_GET['id']);
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

	if($customerdata['ten'] !='' && !check_ten($customerdata['ten']))
		$error['ten'] = trans('Incorrect Tax Exempt Number!');

	if($customerdata['ssn'] != '' && !check_ssn($customerdata['ssn']))
		$error['ssn'] = trans('Incorrect Social Security Number!');

	if($customerdata['regon'] != '' && !check_regon($customerdata['regon']))
		$error['regon'] = trans('Incorrect Business Registration Number!');

	if($customerdata['icn'] != '' && !check_icn($customerdata['icn']))
		$error['icn'] = trans('Incorrect Identity Card Number!');

	if($customerdata['zip'] !='' && !check_zip($customerdata['zip']))
		$error['zip'] = trans('Incorrect ZIP code!');

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
		
		$SMARTY->assign('error',$error);
	}
}
else
{
	
	$customerinfo = $LMS->GetCustomer($_GET['id']);

	if($customerinfo['cutoffstop'] > mktime(0,0,0))
		$customerinfo['cutoffstop'] = floor(($customerinfo['cutoffstop'] - mktime(23,59,59))/86400);

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
        $SMARTY->assign('ewx_channelid', $DB->GetOne('SELECT MAX(channelid) FROM ewx_stm_nodes, nodes
                                        WHERE nodeid = nodes.id AND ownerid = ?', array($customerinfo['id'])));
}

$SMARTY->assign('customernodes',$LMS->GetCustomerNodes($customerinfo['id']));
$SMARTY->assign('balancelist',$LMS->GetCustomerBalanceList($customerinfo['id']));
$SMARTY->assign('tariffs',$LMS->GetTariffs());
$SMARTY->assign('assignments',$LMS->GetCustomerAssignments($_GET['id']));
$SMARTY->assign('customergroups',$LMS->CustomergroupGetForCustomer($_GET['id']));
$SMARTY->assign('othercustomergroups',$LMS->GetGroupNamesWithoutCustomer($_GET['id']));
$SMARTY->assign('documents',$LMS->GetDocuments($_GET['id'], 10));
$SMARTY->assign('customerinfo',$customerinfo);
$SMARTY->assign('taxeslist',$LMS->GetTaxes());
$SMARTY->assign('recover',($action == 'recover' ? 1 : 0));
$SMARTY->display('customeredit.html');

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

?>
