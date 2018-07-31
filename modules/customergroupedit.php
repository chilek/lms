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

if(!$LMS->CustomergroupExists($_GET['id']))
{
	$SESSION->redirect('?m=customergrouplist');
}

if(isset($_POST['customerassignments']))
{
	$oper = $_POST['oper'];
	$customerassignments = $_POST['customerassignments'];
	if (isset($customerassignments['membersnetid']) && $oper=='2')
	{
		$SESSION->redirect('?'.preg_replace('/&membersnetid=[0-9]+/', '', $SESSION->get('backto')).'&membersnetid='.$customerassignments['membersnetid']);
	}
	if (isset($customerassignments['othersnetid']) && $oper=='3')
	{
		$SESSION->redirect('?'.preg_replace('/&othersnetid=[0-9]+/', '', $SESSION->get('backto')).'&othersnetid='.$customerassignments['othersnetid']);
	}
}

$membersnetid = isset($_GET['membersnetid']) ? $_GET['membersnetid'] : 0;
$othersnetid =  isset($_GET['othersnetid']) ? $_GET['othersnetid'] : 0;

$customergroup = $LMS->CustomergroupGet($_GET['id'], $membersnetid);
$customers = $LMS->GetCustomerWithoutGroupNames($_GET['id'], $othersnetid);

$layout['pagetitle'] = trans('Group Edit: $a', $customergroup['name']);

if(isset($_POST['customergroup']))
{
	$customergroupedit = $_POST['customergroup'];

	foreach($customergroupedit as $key => $value)
		$customergroupedit[$key] = trim($value);

	$customergroupedit['id'] = $_GET['id'];

	if($customergroupedit['name'] == '')
		$error['name'] = trans('Group name required!');
	elseif(strlen($customergroupedit['name']) > 255)
		$error['name'] = trans('Group name is too long!');
	elseif(!preg_match('/^[._a-z0-9-]+$/i', $customergroupedit['name']))
		$error['name'] = trans('Invalid chars in group name!');
	elseif(($id = $LMS->CustomergroupGetId($customergroupedit['name'])) && $id != $customergroupedit['id'])
		$error['name'] = trans('Group with name $a already exists!',$customergroupedit['name']);

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
$SMARTY->assign('customerscount', count($customers));
$SMARTY->assign('networks', $LMS->GetNetworks());
$SMARTY->assign('customergroups',$LMS->CustomergroupGetAll());
$SMARTY->assign('membersnetid', isset($membersnetid) ? $membersnetid : 0);
$SMARTY->assign('othersnetid', isset($othersnetid) ? $othersnetid : 0);
$SMARTY->display('customer/customergroupedit.html');

?>
