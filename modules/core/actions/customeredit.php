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
elseif(isset($_POST['customerdata']))
{
	$customerdata = $_POST['customerdata'];
	foreach($customerdata as $key=>$value)
		$customerdata[$key] = trim($value);

	if($customerdata['lastname']=='')
		$error['customername'] = trans('\'Last/Company Name\' and \'First Name\' fields cannot be empty!');
	
	if($customerdata['address']=='')
		$error['address'] = trans('Address required!');

	if($customerdata['ten'] !='' && !check_ten($customerdata['ten']))
		$error['ten'] = trans('Incorrect Tax Exempt Number!');

	if(!check_ssn($customerdata['ssn']) && $customerdata['ssn'] != '')
		$error['ssn'] = trans('Incorrect Social Security Number!');

	if($customerdata['zip'] !='' && !check_zip($customerdata['zip']))
		$error['zip'] = trans('Incorrect ZIP code!');

	if($customerdata['email']!='' && !check_email($customerdata['email']))
		$error['email'] = trans('Incorrect email!');

	if($customerdata['im']!='' && !check_im($customerdata['im']))
		$error['im'] = trans('Incorrect IM uin!');

	if($customerdata['im'] == '')
		$customerdata['im'] = 0;

	if($customerdata['pin'] == '')
		$customerdata['pin'] = 0;

	if($customerdata['pin']!=0 && !eregi('^[0-9]{4,6}$',$customerdata['pin']))
		$error['pin'] = trans('Incorrect PIN code!');

	if($customerdata['status']!=3&&$LMS->GetCustomerNodesNo($customerdata['id'])) 
		$error['status'] = trans('Only customer with \'connected\' status can own computers!');
		
	if (!$error)
	{
		$LMS->CustomerUpdate($customerdata);
		$SESSION->redirect('?m=customerinfo&id='.$customerdata['id']);
	}
	else
	{
		$olddata=$LMS->GetCustomer($_GET['id']);
		$customerinfo=$customerdata;
		$customerinfo['createdby']=$olddata['createdby'];
		$customerinfo['modifiedby']=$olddata['modifiedby'];
		$customerinfo['creationdateh']=$olddata['creationdateh'];
		$customerinfo['moddateh']=$olddata['moddateh'];
		$customerinfo['customername']=$olddata['customername'];
		$customerinfo['balance']=$olddata['balance'];
		if($olddata['status']==3)
			$customerinfo['shownodes'] = TRUE;
		$SMARTY->assign('error',$error);
	}
}else{

	$customerinfo=$LMS->GetCustomer($_GET['id']);
	if($customerinfo['status'] == 3)
		$customerinfo['shownodes'] = TRUE;
}

$layout['pagetitle'] = trans('Customer Edit: $0',$customerinfo['customername']);

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
