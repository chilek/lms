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

if($LMS->UserExists($_GET['id']) < 0 && $action != 'recover')
{
	$SESSION->redirect('?m=userinfo&id='.$_GET['id']);
}
elseif(! $LMS->UserExists($_GET['id']))
{
	$SESSION->redirect('?m=userlist');
}
elseif($action == 'usergroupdelete')
{
	$LMS->UserassignmentDelete(array('userid' => $_GET['id'], 'usergroupid' => $_GET['usergroupid']));
	$SESSION->redirect('?m=userinfo&id='.$_GET['id']);
}
elseif($action == 'usergroupadd')
{
	if ($LMS->UsergroupExists($_POST['usergroupid']))
		$LMS->UserassignmentAdd(array('userid' => $_GET['id'], 'usergroupid' => $_POST['usergroupid']));
	$SESSION->redirect('?m=userinfo&id='.$_GET['id']);
}
elseif(isset($_POST['userdata']))
{
	$userdata = $_POST['userdata'];
	foreach($userdata as $key=>$value)
		$userdata[$key] = trim($value);

	if($userdata['lastname']=='')
		$error['username'] = trans('\'Surname/Name\' and \'First Name\' fields cannot be empty!');
	
	if($userdata['address']=='')
		$error['address'] = trans('Address required!');

	if($userdata['nip'] !='' && !check_ten($userdata['nip']))
		$error['nip'] = trans('Incorrect Tax Exempt Number!');

	if(!check_ssn($userdata['pesel']) && $userdata['pesel'] != '')
		$error['pesel'] = trans('Incorrect Social Security Number!');

	if($userdata['zip'] !='' && !check_zip($userdata['zip']))
		$error['zip'] = trans('Incorrect ZIP code!');

	if($userdata['email']!='' && !check_email($userdata['email']))
		$error['email'] = trans('Incorrect email!');

	if($userdata['gguin'] == '')
		$userdata['gguin'] = 0;

	if($userdata['pin'] == '')
		$userdata['pin'] = 0;

	if($userdata['gguin']!=0 && !eregi('^[0-9]{4,}$',$userdata['gguin']))
		$error['gguin'] = trans('Incorrect IM uin!');

	if($userdata['pin']!=0 && !eregi('^[0-9]{4,6}$',$userdata['pin']))
		$error['pin'] = trans('Incorrect PIN code!');

	if($userdata['status']!=3&&$LMS->GetUserNodesNo($userdata['id'])) 
		$error['status'] = trans('Only customer with status \'connected\' can own computers!');
		
	if (!$error)
	{
		$LMS->UserUpdate($userdata);
		$SESSION->redirect('?m=userinfo&id='.$userdata['id']);
	}
	else
	{
		$olddata=$LMS->GetUser($_GET['id']);
		$userinfo=$userdata;
		$userinfo['createdby']=$olddata['createdby'];
		$userinfo['modifiedby']=$olddata['modifiedby'];
		$userinfo['creationdateh']=$olddata['creationdateh'];
		$userinfo['moddateh']=$olddata['moddateh'];
		$userinfo['username']=$olddata['username'];
		$userinfo['balance']=$olddata['balance'];
		if($olddata['status']==3)
			$userinfo['shownodes'] = TRUE;
		$SMARTY->assign('error',$error);
	}
}else{

	$userinfo=$LMS->GetUser($_GET['id']);
	if($userinfo['status'] == 3)
		$userinfo['shownodes'] = TRUE;
}

$layout['pagetitle'] = trans('Customer Edit: $0',$userinfo['username']);

$SMARTY->assign('usernodes',$LMS->GetUserNodes($userinfo['id']));
$SMARTY->assign('balancelist',$LMS->GetUserBalanceList($userinfo['id']));
$SMARTY->assign('tariffs',$LMS->GetTariffs());
$SMARTY->assign('assignments',$LMS->GetUserAssignments($_GET['id']));
$SMARTY->assign('usergroups',$LMS->UsergroupGetForUser($_GET['id']));
$SMARTY->assign('otherusergroups',$LMS->GetGroupNamesWithoutUser($_GET['id']));
$SMARTY->assign('userinfo',$userinfo);
$SMARTY->assign('recover',($action == 'recover' ? 1 : 0));
$SMARTY->display('useredit.html');

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

?>
