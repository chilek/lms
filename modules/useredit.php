<?php

/*
 * LMS version 1.5-cvs
 *
 *  (C) Copyright 2001-2004 LMS Developers
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

$userdata = $_POST['userdata'];

if($LMS->UserExists($_GET['id']) < 0 && $_GET['action'] != 'recover')
{
	header('Location: ?m=userinfo&id='.$_GET['id']);
	die;
}
elseif(! $LMS->UserExists($_GET['id']))
{
	header('Location: ?m=userlist');
	die;
}
elseif($_GET['action'] == 'usergroupdelete')
{
	$LMS->UserassignmentDelete(array('userid' => $_GET['id'], 'usergroupid' => $_GET['usergroupid']));
	header('Location: ?m=userinfo&id='.$_GET['id']);
	die;
}
elseif($_GET['action'] == 'usergroupadd')
{
	if ($LMS->UsergroupExists($_POST['usergroupid']))
		$LMS->UserassignmentAdd(array('userid' => $_GET['id'], 'usergroupid' => $_POST['usergroupid']));
	header('Location: ?m=userinfo&id='.$_GET['id']);
	die;
}
elseif(isset($userdata))
{
	foreach($userdata as $key=>$value)
		$userdata[$key] = trim($value);

	if($userdata['lastname']=='')
		$error['username'] = 'Pola \'nazwisko/nazwa\' oraz imiê nie mog± byæ puste!';
	
	if($userdata['address']=='')
		$error['address'] = 'Proszê podaæ adres!';

	if($userdata['nip'] !='' && !eregi('^[0-9]{3}-[0-9]{3}-[0-9]{2}-[0-9]{2}$',$userdata['nip']) && !eregi('^[0-9]{3}-[0-9]{2}-[0-9]{2}-[0-9]{3}$',$userdata['nip']) && !check_nip($userdata['nip']))
		$error['nip'] = 'Podany NIP jest b³êdny!';

	if(!check_pesel($userdata['pesel']) && $userdata['pesel'] != '')
		$error['pesel'] = 'Podany PESEL jest b³êdny!';

	if($userdata['zip'] !='' && !eregi('^[0-9]{2}-[0-9]{3}$',$userdata['zip']))
		$error['zip'] = 'Podany kod pocztowy jest b³êdny!';

	if($userdata['email']!='' && !check_email($userdata['email']))
		$error['email'] = 'Podany email nie wydaje siê byæ poprawny!';

	if($userdata['gguin'] == '')
		$userdata['gguin'] = 0;

	if($userdata['pin'] == '')
		$userdata['pin'] = 0;

	if($userdata['gguin']!=0 && !eregi('^[0-9]{4,}$',$userdata['gguin']))
		$error['gguin'] = 'Podany numer GG jest niepoprawny!';

	if($userdata['pin']!=0 && !eregi('^[0-9]{4,6}$',$userdata['pin']))
		$error['pin'] = 'Podany numer PIN jest niepoprawny!';

	if($userdata['status']!=3&&$LMS->GetUserNodesNo($userdata['id'])) 
		$error['status'] = 'Tylko pod³±czony u¿ytkownik mo¿e posiadaæ komputery!';
		
	if (!isset($error)){
		$LMS->UserUpdate($userdata);
		header('Location: ?m=userinfo&id='.$userdata['id']);
		die;
	}else{
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

$layout['pagetitle'] = 'Edycja danych u¿ytkownika: '.$userinfo['username'];

$SMARTY->assign('usernodes',$LMS->GetUserNodes($userinfo['id']));
$SMARTY->assign('balancelist',$LMS->GetUserBalanceList($userinfo['id']));
$SMARTY->assign('tariffs',$LMS->GetTariffs());
$SMARTY->assign('assignments',$LMS->GetUserAssignments($_GET['id']));
$SMARTY->assign('usergroups',$LMS->UsergroupGetForUser($_GET['id']));
$SMARTY->assign('otherusergroups',$LMS->GetGroupNamesWithoutUser($_GET['id']));
$SMARTY->assign('userinfo',$userinfo);
$SMARTY->assign('recover',($_GET['action'] == 'recover' ? 1 : 0));
$SMARTY->display('useredit.html');

$_SESSION['backto'] = $_SERVER['QUERY_STRING'];

?>
