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

if(isset($_POST['usergroupadd']))
{
	$usergroupadd = $_POST['usergroupadd'];
	
	foreach($usergroupadd as $key => $value)
		$usergroupadd[$key] = trim($value);

	if($usergroupadd['name']=='' && $usergroupadd['description']=='')
	{
		$SESSION->redirect('?m=usergrouplist');
	}
	
	if($usergroupadd['name'] == '')
		$error['name'] = trans('Group name required!');
	elseif(strlen($usergroupadd['name']) > 16)
		$error['name'] = trans('Group name is too long!');
	elseif($LMS->UsergroupGetId($usergroupadd['name']))
		$error['name'] = trans('Group with name $0 already exists!',$usergroupadd['name']);
	elseif(!eregi("^[._a-z0-9-]+$",$usergroupadd['name']))
		$error['name'] = trans('Invalid chars in group name!');

	if(!$error)
	{
		$SESSION->redirect('?m=usergrouplist&id='.$LMS->UsergroupAdd($usergroupadd));
	}
	
	$SMARTY->assign('error',$error);
	$SMARTY->assign('usergroupadd',$usergroupadd);
}

$layout['pagetitle'] = trans('New Group');

$SMARTY->display('usergroupadd.html');

?>
