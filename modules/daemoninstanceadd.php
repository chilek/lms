<?php

/*
 * LMS version 1.7-cvs
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

if($instance = $_POST['instance']) 
{
	foreach($instance as $idx => $key)
		$instance[$idx] = trim($key);
	
	if($instance['name']=='' && $instance['description']=='' && $instance['module']=='' & $instance['crontab']=='')
	{
		$SESSION->redirect('?m=daemonhostlist');
	}
	
	if($instance['name'] == '')
		$error['name'] = trans('Instance name is required!');
	elseif($LMS->DB->GetOne('SELECT id FROM daemoninstances WHERE name=? AND hostid=?', array($instance['name'], $instance['hostid'])))
		$error['name'] = trans('Instance with specified name exists on that host!');
	
	if($instance['module'] == '')
		$error['module'] = trans('Instance module is required!');
		
	if(!$instance['hostid'])
		$error['hostid'] = trans('Instance host is required!');
	
	if($instance['crontab'] != '' && !eregi('^[0-9/*,-]+[ \t][0-9/*,-]+[ \t][0-9/*,-]+[ \t][0-9/*,-]+[ \t][0-9/*,-]+$', $instance['crontab']))
		$error['crontab'] = trans('Incorrect crontab format!');

	if($instance['priority'] == '')
		$instance['priority'] = 0;
	elseif(!is_numeric($instance['priority']))
		$error['priority'] = trans('Priority must be integer!');
	
	if(!$error)
	{
		$LMS->DB->Execute('INSERT INTO daemoninstances (name, hostid, description, module, crontab, priority) VALUES (?,?,?,?,?,?)',
				    array($instance['name'], 
					    $instance['hostid'], 
					    $instance['description'],
					    $instance['module'],
					    $instance['crontab'],
					    $instance['priority']));
		$LMS->SetTS('daemoninstances');
		
		if(!$instance['reuse'])
		{
			$SESSION->redirect('?m=daemoninstancelist&id='.$instance['hostid']);
		}
		
		unset($instance['name']);
		unset($instance['module']);
		unset($instance['crontab']);
		unset($instance['priority']);
		unset($instance['description']);
	}
}	

$layout['pagetitle'] = trans('New Instance');

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$instance['hostid'] = $instance['hostid'] ? $instance['hostid'] : $_GET['hostid'];

$SMARTY->assign('error', $error);
$SMARTY->assign('instance', $instance);
$SMARTY->assign('hosts', $LMS->DB->GetAll('SELECT id, name FROM daemonhosts ORDER BY name'));
$SMARTY->assign('layout', $layout);
$SMARTY->display('daemoninstanceadd.html');

?>
