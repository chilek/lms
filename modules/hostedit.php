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

$host = $DB->GetRow('SELECT id, name, description FROM hosts WHERE id=?', array($_GET['id']));

$layout['pagetitle'] = trans('Host Edit: $a', $host['name']);

if(isset($_POST['hostedit']))
{
	$hostedit = $_POST['hostedit'];
	$hostedit['name'] = trim($hostedit['name']);
	$hostedit['description'] = trim($hostedit['description']);
	
	if($hostedit['name'] == '')
		$error['name'] = trans('Host name is required!');
	elseif($host['name']!=$hostedit['name'])
		if(GetHostIdByName($hostedit['name']))
			$error['name'] = trans('Host with specified name exists!');
	
	if(!$error)
	{
		$DB->Execute('UPDATE hosts SET name=?, description=? WHERE id=?',
				    array($hostedit['name'], $hostedit['description'], $_GET['id']));
		
		$SESSION->redirect('?m=hostlist');
	}
	
	$host['name'] = $hostedit['name'];
	$host['description'] = $hostedit['description'];
}	

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$SMARTY->assign('hostedit', $host);
$SMARTY->assign('error', $error);
$SMARTY->display('hostedit.html');

?>
