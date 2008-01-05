<?php

/*
 * LMS version 1.11-cvs
 *
 *  (C) Copyright 2001-2008 LMS Developers
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

if(isset($_POST['nodegroupadd']))
{
	$nodegroupadd = $_POST['nodegroupadd'];
	
	foreach($nodegroupadd as $key => $value)
		$nodegroupadd[$key] = trim($value);

	if($nodegroupadd['name']=='' && $nodegroupadd['description']=='')
	{
		$SESSION->redirect('?m=nodegrouplist');
	}
	
	if($nodegroupadd['name'] == '')
		$error['name'] = trans('Group name required!');
	elseif(strlen($nodegroupadd['name']) > 32)
		$error['name'] = trans('Group name is too long!');
	elseif(!eregi("^[._a-z0-9-]+$",$nodegroupadd['name']))
		$error['name'] = trans('Invalid chars in group name!');
	elseif($DB->GetOne('SELECT 1 FROM nodegroups WHERE name = ?', array($nodegroupadd['name'])))
		$error['name'] = trans('Group with name $0 already exists!',$nodegroupadd['name']);

	if(!$error)
	{
		$DB->Execute('INSERT INTO nodegroups (name, description)
				VALUES (?, ?)', 
				array($nodegroupadd['name'],
					$nodegroupadd['description']
				));
	
		$id = $DB->GetLastInsertID('nodegroups');
		
		$SESSION->redirect('?m=nodegrouplist&id='.$id);
	}
	
	$SMARTY->assign('error',$error);
	$SMARTY->assign('nodegroupadd',$nodegroupadd);
}

$layout['pagetitle'] = trans('New Group');

$SMARTY->display('nodegroupadd.html');

?>
