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

$usergroupinfo = $_POST['usergroupinfo'];

if (sizeof($usergroupinfo['gmuserid']) && $usergroupinfo['oper']==0)
{
	$userassignment['usergroupid'] = $usergroupinfo['backid'];
	foreach($usergroupinfo['gmuserid'] as $value)
	{
		$userassignment['userid'] = $value;
		$LMS->UserassignmentDelete($userassignment);
	}
	header("Location: ?".$_SESSION['backto']);
	die;
}

if (sizeof($usergroupinfo['muserid']) && $usergroupinfo['oper']==1)
{
	$userassignment['usergroupid'] = $usergroupinfo['backid'];
	foreach($usergroupinfo['muserid'] as $value)
	{
		$userassignment['userid'] = $value;
		$LMS->UserassignmentDelete($userassignment);
	}
	header("Location: ?".$_SESSION['backto']);
	die;
}

if (!isset($_GET['id']))
{
	header("Location: ?".$_SESSION['backto']);
	die;
}

if ($id = $_GET['id'])
{
	if (!$LMS->UsergroupExists($id))
	{
		header("Location: ?m=usergrouplist");
		die;
	}
}

$_SESSION['backto'] = $_SERVER['QUERY_STRING'];

$usergroup = $LMS->UsergroupGet($_GET['id']);
$layout['pagetitle'] = "Informacja o grupie: ".$usergroup['name'];

$SMARTY->assign('usergroup',$usergroup);
$SMARTY->assign('users',$LMS->GetUserNames());
$SMARTY->display('usergroupinfo.html');

?>
