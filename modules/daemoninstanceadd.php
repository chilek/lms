<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2013 LMS Developers
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

$instance = isset($_POST['instance']) ? $_POST['instance'] : NULL;

if($instance) 
{
	foreach($instance as $idx => $key)
		$instance[$idx] = trim($key);
	
	if($instance['name']=='' && $instance['description']=='' && $instance['module']=='' & $instance['crontab']=='')
	{
		$SESSION->redirect('?m=hostlist');
	}
	
	if($instance['name'] == '')
		$error['name'] = trans('Instance name is required!');
	elseif($DB->GetOne('SELECT id FROM daemoninstances WHERE name=? AND hostid=?', array($instance['name'], $instance['hostid'])))
		$error['name'] = trans('Instance with specified name exists on that host!');
	
	if($instance['module'] == '')
		$error['module'] = trans('Instance module is required!');
		
	if(!$instance['hostid'])
		$error['hostid'] = trans('Instance host is required!');
	
	if($instance['crontab'] != '' && !preg_match('/^[0-9\/\*,-]+[ \t][0-9\/\*,-]+[ \t][0-9\/*,-]+[ \t][0-9\/\*,-]+[ \t][0-9\/\*,-]+$/', $instance['crontab']))
		$error['crontab'] = trans('Incorrect crontab format!');

	if($instance['priority'] == '')
		$instance['priority'] = 0;
	elseif(!is_numeric($instance['priority']))
		$error['priority'] = trans('Priority must be integer!');
	
	if(!$error)
	{
		$DB->Execute('INSERT INTO daemoninstances (name, hostid, description, module, crontab, priority) VALUES (?,?,?,?,?,?)',
				    array($instance['name'], 
					    $instance['hostid'], 
					    $instance['description'],
					    $instance['module'],
					    $instance['crontab'],
					    $instance['priority']));
		
		if($instance['id'])
		{
			$id = $DB->GetLastInsertId('daemoninstances');
			$DB->Execute('INSERT INTO daemonconfig (var, description, value, instanceid)
					SELECT var, description, value, ? FROM daemonconfig
					WHERE instanceid = ?', array($id, $instance['id']));
		}
		
		if(!isset($instance['reuse']))
		{
			$SESSION->redirect('?m=daemoninstancelist&id='.$instance['hostid']);
		}
		
		unset($instance['id']);
		unset($instance['name']);
		unset($instance['module']);
		unset($instance['crontab']);
		unset($instance['priority']);
		unset($instance['description']);
	}
}	
elseif(isset($_GET['id']))
{
	$instance = $DB->GetRow('SELECT * FROM daemoninstances
			WHERE id = ?', array(intval($_GET['id'])));
}

$instance['hostid'] = isset($instance['hostid']) ? $instance['hostid'] : $_GET['hostid'];

$layout['pagetitle'] = trans('New Instance');

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$SMARTY->assign('error', $error);
$SMARTY->assign('instance', $instance);
$SMARTY->assign('hosts', $DB->GetAll('SELECT id, name FROM hosts ORDER BY name'));

$SMARTY->display('daemoninstanceadd.html');

?>
