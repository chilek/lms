<?php

/*
 * LMS version 1.3-cvs
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
if($_GET['action'] == 'assignmentdelete')
{
	$LMS->DeleteAssignment($_GET['aid'],$_GET['balance']);
	header('Location: ?m=userinfo&id='.$_GET['id']);
	die;
}
elseif($_GET['action'] == 'addassignment')
{
	$period = sprintf('%d',$_POST['period']);

	if($period < 0 || $period > 3)
		$period = 1;

	switch($period)
	{
		case 0:
			$at = sprintf('%d',$_POST['at']);
			
			if($at < 1)
				$at = 1;
			elseif($at > 7)
				$at = 7;
		break;

		case 1:
			$at = sprintf('%d',$_POST['at']);
			if($at == 0)
			{
				$at = 1 + date('d',time());
				if($at > 28)
					$at = 1;
			}
				
			if($at < 1)
				$at = 1;
			elseif($at > 28)
				$at = 28;
		break;

		case 2:
			if(!eregi('^[0-9]{2}/[0-9]{2}$',trim($_POST['at'])))
				$error[] = 'Niepoprawny format daty';
			else {
				list($d,$m) = split('/',trim($_POST['at']));
				if($d>30 || $d<1)
					$error[] = 'Niepoprawna liczba dni w miesi±cu';
				if($m>3 || $m<1)
					$error[] = 'Niepoprawny numer miesi±ca (max.3)';
				
				$at = ($m-1) * 100 + $d;
			}
		break;

		case 3:
			if(!eregi('^[0-9]{2}/[0-9]{2}$',trim($_POST['at'])))
				$error[] = 'Niepoprawny format daty';
			else
				list($d,$m) = split('/',trim($_POST['at']));
			$ttime = mktime(12, 0, 0, $m, $d, 1990);
			$at = date('z',$ttime) + 1;
		break;
	}

	if(trim($_POST['datefrom'] == ''))
		$from = 0;
	elseif(eregi('^[0-9]{4}/[0-9]{2}/[0-9]{2}$',trim($_POST['datefrom'])))
	{
		list($y, $m, $d) = split('/', trim($_POST['datefrom']));
		if(checkdate($m, $d, $y))
			$from = mktime(0, 0, 0, $m, $d, $y);
		else
			$error[] = 'Koniec okresu naliczania jest niepoprawny!';
	}
	else
		$error[] = 'Pocz±tek okresu naliczania jest niepoprawny!';

	if(trim($_POST['dateto'] == ''))
		$to = 0;
	elseif(eregi('^[0-9]{4}/[0-9]{2}/[0-9]{2}$',trim($_POST['dateto'])))
	{
		list($y, $m, $d) = split('/', trim($_POST['dateto']));
		if(checkdate($m, $d, $y))
			$to = mktime(23, 59, 59, $m, $d, $y);
		else
			$error[] = 'Koniec okresu naliczania jest niepoprawny!';
	}
	else
		$error[] = 'Koniec okresu naliczania jest niepoprawny!';

	if($to < $from && $to != 0 && $from != 0)
		$error[] = 'Zakres dat jest niepoprawny!';

	if($LMS->TariffExists($_POST['tariffid']) && !$error)
		$LMS->AddAssignment(array('tariffid' => $_POST['tariffid'], 'userid' => $_GET['id'], 'period' => $period, 'at' => $at, 'invoice' => sprintf('%d',$_POST['invoice']), 'datefrom' => $from, 'dateto' => $to ));
	header('Location: ?m=userinfo&id='.$_GET['id']);
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

	if($userdata['gguin'] == '')
		$userdata['gguin'] = 0;

	if($userdata['gguin']!=0 && !eregi('^[0-9]{4,}$',$userdata['gguin']))
		$error['gguin'] = 'Podany numer GG jest niepoprawny!';

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
