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

$userassignments = $_POST['userassignments'];
$oper = $_POST['oper'];

if(isset($userassignments))
{
	if (sizeof($userassignments['gmuserid']) && $oper=='0')
	{
		$assignment['usergroupid'] = $userassignments['backid'];
		foreach($userassignments['gmuserid'] as $value)
		{
			$assignment['userid'] = $value;
			$LMS->UserassignmentDelete($assignment);
		}
		header('Location: ?'.$_SESSION['backto']);
		die;
	}

	if (sizeof($userassignments['muserid']) && $oper=='1')
	{
		$assignment['usergroupid'] = $userassignments['backid'];
		foreach($userassignments['muserid'] as $value)
		{
			$assignment['userid'] = $value;
			if(! $LMS->UserassignmentExist($assignment['usergroupid'],$value))
				$LMS->UserassignmentAdd($assignment);
		}
		header('Location: ?'.$_SESSION['backto']);
		die;
	}
}

if(!$LMS->UsergroupExists($_GET['id']))
{
	header('Location: ?m=usergrouplist');
	die;
}

$usergroup = $_POST['usergroup'];

if(isset($usergroup))
{
	foreach($usergroup as $key => $value)
		$usergroup[$key] = trim($value);

	if($usergroup['name'] == '')
		$error['name'] = 'Proszê podaæ nazwê grupy!';
	elseif($LMS->UsergroupGetId($usergroup['name']) && $usergroup['name'] != $LMS->UsergroupGetName($_GET['id']))
		$error['name'] = 'Istnieje ju¿ grupa o takiej nazwie!';	

	$usergroup['id'] = $_GET['id'];

	if(!$error)
	{
		$LMS->UsergroupUpdate($usergroup);
		header('Location: ?m=usergroupinfo&id='.$usergroup['id']);
		die;
	}

}

$usergroup = $LMS->UsergroupGet($_GET['id']);
$_SESSION['backto'] = $_SERVER['QUERY_STRING'];

$layout['pagetitle'] = 'Edycja grupy: '.$usergroup['name'];	

$SMARTY->assign('usergroup',$usergroup);
$SMARTY->assign('error',$error);
$SMARTY->assign('users', $LMS->GetUserWithoutGroupNames($_GET['id']));
$SMARTY->display('usergroupedit.html');

?>



