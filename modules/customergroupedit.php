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

if(!$LMS->CustomergroupExists($_GET['id']))
{
	$SESSION->redirect('?m=customergrouplist');
}

if(isset($_POST['customerassignments']))
{
	$oper = $_POST['oper'];
	$customerassignments = $_POST['customerassignments'];
	
	if(isset($customerassignments['gmcustomerid']) && $oper=='0')
	{
		$assignment['customergroupid'] = $_GET['id'];
		foreach($customerassignments['gmcustomerid'] as $value)
		{
			$assignment['customerid'] = $value;
			$LMS->CustomerassignmentDelete($assignment);
		}
		$SESSION->redirect('?'.$SESSION->get('backto'));
	}

	if (isset($customerassignments['mcustomerid']) && $oper=='1')
	{
		$assignment['customergroupid'] = $_GET['id'];
		foreach($customerassignments['mcustomerid'] as $value)
		{
			$assignment['customerid'] = $value;
			if(! $LMS->CustomerassignmentExist($assignment['customergroupid'],$value))
				$LMS->CustomerassignmentAdd($assignment);
		}
		$SESSION->redirect('?'.$SESSION->get('backto'));
	}
}

$customergroup = $LMS->CustomergroupGet($_GET['id']);
$customers = $LMS->GetCustomerWithoutGroupNames($_GET['id']);

$layout['pagetitle'] = trans('Group Edit: $0', $customergroup['name']);

if(isset($_POST['customergroup']))
{
	$customergroupedit = $_POST['customergroup'];

	foreach($customergroupedit as $key => $value)
		$customergroupedit[$key] = trim($value);

	$customergroupedit['id'] = $_GET['id'];
	
	if($customergroupedit['name'] == '')
		$error['name'] = trans('Group name required!');
	elseif(strlen($customergroupedit['name']) > 16)
		$error['name'] = trans('Group name is too long!');
	elseif( ($id = $LMS->CustomergroupGetId($customergroupedit['name'])) && $id != $customergroupedit['id'])
		$error['name'] = trans('Group with name $0 already exists!',$customergroupedit['name']);
	elseif(!eregi("^[._a-z0-9-]+$",$customergroupedit['name']))
		$error['name'] = trans('Invalid chars in group name!');

	if(!$error)
	{
		$LMS->CustomergroupUpdate($customergroupedit);
		$SESSION->redirect('?m=customergroupinfo&id='.$customergroup['id']);
	}

	$customergroup['description'] = $customergroupedit['description'];
	$customergroup['name'] = $customergroupedit['name'];
}

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$SMARTY->assign('customergroup',$customergroup);
$SMARTY->assign('error', $error);
$SMARTY->assign('customers', $customers);
$SMARTY->assign('customerscount', sizeof($customers));
$SMARTY->display('customergroupedit.html');

?>
