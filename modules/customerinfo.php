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

if(!eregi("^[0-9]+$",$_GET['id']))
{
	$SESSION->redirect('?m=customerlist');
}

if($LMS->CustomerExists($_GET['id']) == 0)
{
	$SESSION->redirect('?m=customerlist');
}

$customerinfo = $LMS->GetCustomer($_GET['id']);
$assigments = $LMS->GetCustomerAssignments($_GET['id']);
$customergroups = $LMS->CustomergroupGetForCustomer($_GET['id']);
$othercustomergroups = $LMS->GetGroupNamesWithoutCustomer($_GET['id']);
$balancelist = $LMS->GetCustomerBalanceList($_GET['id']);
$customernodes = $LMS->GetCustomerNodes($_GET['id']);
$tariffs = $LMS->GetTariffs();
$contractlist = $LMS->GetContractList();

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$layout['pagetitle'] = trans('Customer Info: $0',$customerinfo['customername']);

$customernodes['ownerid'] = $_GET['id'];
$SMARTY->assign(
		array(
			'customernodes' => $customernodes,
			'balancelist' => $balancelist,
			'assignments' => $assigments,
			'customergroups' => $customergroups,
			'othercustomergroups' => $othercustomergroups,
			'customerinfo' => $customerinfo,
			'tariffs' => $tariffs,
			'contractlist' => $contractlist,
			'contractcount' => sizeof($contractlist)
		     )
		);
$SMARTY->display('customerinfo.html');

?>
