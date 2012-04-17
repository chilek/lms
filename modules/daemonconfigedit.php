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

$config = $DB->GetRow('SELECT id, var, value, description, disabled, instanceid FROM daemonconfig WHERE id=?', array($_GET['id']));

if(isset($_POST['config'])) 
{
	$configedit = $_POST['config'];

	foreach($configedit as $idx => $key)
		$configedit[$idx] = trim($key);
	
	if($configedit['var'] == '')
		$error['var'] = trans('Option name is required!');
	elseif($config['var']!=$configedit['var'])
		if($DB->GetOne('SELECT id FROM daemonconfig WHERE var=? AND instanceid=?', array($configedit['var'], $config['instanceid'])))
			$error['var'] = trans('Option with specified name exists in that instance!');
	
	if(!isset($configedit['disabled']))
		$configedit['disabled'] = 0;
		
	if(!$error)
	{
		$configedit['value'] = str_replace("\r\n","\n", $configedit['value']);
		
		$DB->Execute('UPDATE daemonconfig SET var=?, description=?, value=?, disabled=? WHERE id=?',
				    array($configedit['var'], 
					    $configedit['description'],
					    $configedit['value'],
					    $configedit['disabled'],
					    $_GET['id']));
		
		$SESSION->redirect('?m=daemoninstanceview&id='.$config['instanceid']);
	}
}
elseif(isset($_GET['statuschange']))
{
	if($config['disabled'])
		$DB->Execute('UPDATE daemonconfig SET disabled=0 WHERE id=?', array($config['id']));
	else
		$DB->Execute('UPDATE daemonconfig SET disabled=1 WHERE id=?', array($config['id']));
	
	$SESSION->redirect('?m=daemoninstanceview&id='.$config['instanceid']);
}	

$instance = $DB->GetRow('SELECT daemoninstances.name AS name, hosts.name AS hostname FROM daemoninstances, hosts WHERE hosts.id=hostid AND daemoninstances.id=?', array($config['instanceid']));

$layout['pagetitle'] = trans('Option Edit: $a/$b/$c', $config['var'], $instance['name'], $instance['hostname']);

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$SMARTY->assign('error', $error);
$SMARTY->assign('config', $config);
$SMARTY->display('daemonconfigedit.html');

?>
