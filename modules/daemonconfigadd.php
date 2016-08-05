<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2016 LMS Developers
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

$config = isset($_POST['config']) ? $_POST['config'] : NULL;

if($config) 
{
	foreach($config as $idx => $key)
		$config[$idx] = trim($key);
	
	$config['instanceid'] = $_GET['id'];
	
	if($config['var']=='' && $config['description']=='' && $config['value']=='')
	{
		$SESSION->redirect('?m=daemoninstanceview&id='.$config['instanceid']);
	}
	
	if($config['var'] == '')
		$error['var'] = trans('Option name is required!');
	elseif($DB->GetOne('SELECT id FROM daemonconfig WHERE var=? AND instanceid=?', array($config['var'], $config['instanceid'])))
		$error['var'] = trans('Option with specified name exists in that instance!');

	if (!$error) {
		$config['value'] = str_replace("\r\n","\n",$config['value']);

		$args = array(
			'var' => $config['var'], 
			SYSLOG::RES_DAEMONINST => $config['instanceid'], 
			'description' => $config['description'],
			'value' => $config['value']
		);
		$DB->Execute('INSERT INTO daemonconfig (var, instanceid, description, value) VALUES (?,?,?,?)', array_values($args));

		if ($SYSLOG) {
			$hostid = $DB->GetOne('SELECT hostid FROM daemoninstances WHERE id = ?', array($config['instanceid']));
			$args[SYSLOG::RES_DAEMONCONF] = $DB->GetLastInsertID('daemonconfig');
			$args[SYSLOG::RES_HOST] = $hostid;
			$SYSLOG->AddMessage(SYSLOG::RES_DAEMONCONF, SYSLOG::OPER_ADD, $args);
		}

		if (!isset($config['reuse']))
			$SESSION->redirect('?m=daemoninstanceview&id='.$config['instanceid']);

		unset($config['var']);
		unset($config['value']);
		unset($config['description']);
	}
}

$instance = $DB->GetRow('SELECT daemoninstances.name AS name, hosts.name AS hostname FROM daemoninstances, hosts WHERE hosts.id=hostid AND daemoninstances.id=?', array($_GET['id']));

$layout['pagetitle'] = trans('New Option for Instance: $a/$b', $instance['name'], $instance['hostname']);

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$SMARTY->assign('error', $error);
$SMARTY->assign('instanceid', $_GET['id']);
$SMARTY->assign('config', $config);
$SMARTY->display('daemon/daemonconfigadd.html');

?>
