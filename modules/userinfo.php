<?php

/*
 * LMS version 1.5-cvs
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
	$SESSION->redirect('?m=userlist');
}

if($LMS->UserExists($_GET['id']) == 0)
{
	$SESSION->redirect('?m=userlist');
}

$userinfo = $LMS->GetUser($_GET['id']);
$assigments = $LMS->GetUserAssignments($_GET['id']);
$usergroups = $LMS->UsergroupGetForUser($_GET['id']);
$otherusergroups = $LMS->GetGroupNamesWithoutUser($_GET['id']);
$balancelist = $LMS->GetUserBalanceList($_GET['id']);
$usernodes = $LMS->GetUserNodes($_GET['id']);
$tariffs = $LMS->GetTariffs();
$contractlist = $LMS->GetContractList();

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$layout['pagetitle'] = trans('Customer Info: $0',$userinfo['username']);

$usernodes['ownerid'] = $_GET['id'];
$SMARTY->assign(
		array(
			'usernodes' => $usernodes,
			'balancelist' => $balancelist,
			'assignments' => $assigments,
			'usergroups' => $usergroups,
			'otherusergroups' => $otherusergroups,
			'error' => $error,
			'userinfo' => $userinfo,
			'tariffs' => $tariffs,
			'contractlist' => $contractlist,
			'contractcount' => sizeof($contractlist)
		     )
		);
$SMARTY->display('userinfo.html');

?>
