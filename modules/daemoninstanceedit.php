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

$instance = $LMS->DB->GetRow('SELECT id, name, hostid, description, module, crontab, priority, disabled FROM daemoninstances WHERE id=?', array($_GET['id']));

$layout['pagetitle'] = trans('Instance Edit: $0', $instance['name']);

if($instedit = $_POST['instance']) 
{
	foreach($instedit as $idx => $key)
		$instedit[$idx] = trim($key);

	$instedit['id'] = $instance['id'];
	
	if($instedit['name'] == '')
		$error['name'] = trans('Instance name is required!');
	elseif($instedit['name']!=$instance['name'])
		if($LMS->DB->GetOne('SELECT id FROM daemoninstances WHERE name=? AND hostid=?', array($instedit['name'], $instedit['hostid'])))
			$error['name'] = trans('Instance with specified name exists on that host!');
	
	if($instedit['module'] == '')
		$error['module'] = trans('Instance module is required!');
		
	if(!$instedit['hostid'])
		$error['hostid'] = trans('Instance host is required!');
	
	if($instedit['crontab'] != '' && !eregi('^[0-9/*,-]+[ \t][0-9/*,-]+[ \t][0-9/*,-]+[ \t][0-9/*,-]+[ \t][0-9/*,-]+$', $instedit['crontab']))
		$error['crontab'] = trans('Incorrect crontab format!');
	
	if($instedit['disabled']!='1') $instedit['disabled'] = 0;
	
	if(!$error)
	{
		$LMS->DB->Execute('UPDATE daemoninstances SET name=?, hostid=?, description=?, module=?, crontab=?, priority=? WHERE id=?',
				    array($instedit['name'], 
					    $instedit['hostid'], 
					    $instedit['description'],
					    $instedit['module'],
					    $instedit['crontab'],
					    $instedit['priority'],
					    $instedit['id']));
		$LMS->SetTS('daemoninstances');
		
		$SESSION->redirect('?m=daemoninstancelist&id='.$instedit['hostid']);
	}
}	
elseif($_GET['statuschange'])
{
	if($instance['disabled'])
		$LMS->DB->Execute('UPDATE daemoninstances SET disabled=0 WHERE id=?', array($_GET['id']));
	else
		$LMS->DB->Execute('UPDATE daemoninstances SET disabled=1 WHERE id=?', array($_GET['id']));
	$SESSION->redirect('?m=daemoninstancelist&id='.$instance['hostid']);
}

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$SMARTY->assign('error', $error);
$SMARTY->assign('instance', $instedit ? $instedit : $instance);
$SMARTY->assign('hosts', $LMS->DB->GetAll('SELECT id, name FROM daemonhosts ORDER BY name'));
$SMARTY->assign('layout', $layout);
$SMARTY->display('daemoninstanceedit.html');

?>
