<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2012 LMS Developers
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

function GetHostIdByName($name)
{
	global $DB;
	return $DB->GetOne('SELECT id FROM hosts WHERE name = ?', array($name));
}

$hostadd = isset($_POST['hostadd']) ? $_POST['hostadd'] : NULL;

if($hostadd) 
{
	$hostadd['name'] = trim($hostadd['name']);
	$hostadd['description'] = trim($hostadd['description']);
	
	if($hostadd['name']=='' && $hostadd['description']=='')
	{
		$SESSION->redirect('?m=hostlist');
	}
	
	if($hostadd['name'] == '')
		$error['name'] = trans('Host name is required!');
	elseif(GetHostIdByName($hostadd['name']))
		$error['name'] = trans('Host with specified name exists!');
	
	if(!$error)
	{
		$DB->Execute('INSERT INTO hosts (name, description) VALUES (?,?)',
				    array($hostadd['name'], $hostadd['description']));
		
		if(!isset($hostadd['reuse']))
		{
			$SESSION->redirect('?m=hostlist');
		}
		
		unset($hostadd['name']);
		unset($hostadd['description']);
	}

}	

$layout['pagetitle'] = trans('New Host');

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$SMARTY->assign('error', $error);
$SMARTY->assign('hostadd', $hostadd);
$SMARTY->display('hostadd.html');

?>
