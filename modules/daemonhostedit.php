<?php

/*
 * LMS version 1.6-cvs
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

function GetHostIdByName($name)
{
	global $LMS;
	return $LMS->DB->GetOne('SELECT id FROM daemonhosts WHERE name = ?', array($name));
}

$host = $LMS->DB->GetRow('SELECT id, name, description FROM daemonhosts WHERE id=?', array($_GET['id']));

$layout['pagetitle'] = trans('Host Edit: $0', $host['name']);

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
		$LMS->DB->Execute('UPDATE daemonhosts SET name=?, description=? WHERE id=?',
				    array($hostedit['name'], $hostedit['description'], $_GET['id']));
		$LMS->SetTS('daemonhosts');
		
		$SESSION->redirect('?m=daemonhostlist');
	}
	
	$host['name'] = $hostedit['name'];
	$host['description'] = $hostedit['description'];
}	

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$SMARTY->assign('hostedit', $host);
$SMARTY->assign('layout', $layout);
$SMARTY->assign('error', $error);
$SMARTY->display('daemonhostedit.html');

?>
