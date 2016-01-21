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

function ConfigOptionExists($id) 
{
	global $DB;
	return ($DB->GetOne('SELECT id FROM uiconfig WHERE id = ?', array($id)) ? TRUE : FALSE);
}

$id = intval($_GET['id']);

if ($id && !ConfigOptionExists($id))
	$SESSION->redirect('?m=configlist');

if (isset($_GET['statuschange'])) {
	if ($SYSLOG) {
		$disabled = $DB->GetOne('SELECT disabled FROM uiconfig WHERE id = ?', array($id));
		$args = array(
			$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_UICONF] => $id,
			'disabled' => $disabled ? 0 : 1
		);
		$SYSLOG->AddMessage(SYSLOG_RES_UICONF, SYSLOG_OPER_UPDATE, $args,
			array($SYSLOG_RESOURCE_KEYS[SYSLOG_RES_UICONF]));
	}
	$DB->Execute('UPDATE uiconfig SET disabled = CASE disabled WHEN 0 THEN 1 ELSE 0 END WHERE id = ?',array($id));
	$SESSION->redirect('?m=configlist');
}

$config = $DB->GetRow('SELECT * FROM uiconfig WHERE id = ?', array($id));
$option = $config['section'] . '.' . $config['var'];
$config['type'] = ($config['type'] == CONFIG_TYPE_AUTO) ? $LMS->GetConfigDefaultType($option) : $config['type'];

if(isset($_POST['config']))
{
	$cfg = $_POST['config'];
	$cfg['id'] = $id;

	foreach($cfg as $key => $val)
		$cfg[$key] = trim($val);

	if(!ConfigHelper::checkPrivilege('superuser'))
		$cfg['type'] = $config['type'];

	if($cfg['var']=='')
		$error['var'] = trans('Option name is required!');
	elseif(strlen($cfg['var'])>64)
		$error['var'] = trans('Option name is too long (max.64 characters)!');
	elseif(!preg_match('/^[a-z0-9_-]+$/', $cfg['var']))
		$error['var'] = trans('Option name contains forbidden characters!');

	if(($cfg['var']!=$config['var'] || $cfg['section']!=$config['section'])
		&& $LMS->GetConfigOptionId($cfg['var'], $cfg['section'])
	)
		$error['var'] = trans('Option exists!');

	if(!preg_match('/^[a-z0-9_-]+$/', $cfg['section']) && $cfg['section']!='')
		$error['section'] = trans('Section name contains forbidden characters!');

	$option = $cfg['section'] . '.' . $cfg['var'];
	if($cfg['type'] == CONFIG_TYPE_AUTO)
		$cfg['type'] = $LMS->GetConfigDefaultType($option);

	if($msg = $LMS->CheckOption($option, $cfg['value'], $cfg['type']))
		$error['value'] = $msg;

	if(!isset($cfg['disabled'])) $cfg['disabled'] = 0;

	if (!$error) {
		if(isset($_POST['richtext']))
			$cfg['type'] = CONFIG_TYPE_RICHTEXT;

		$args = array(
			'section' => $cfg['section'],
			'var' => $cfg['var'],
			'value' => $cfg['value'],
			'description' => $cfg['description'],
			'disabled' => $cfg['disabled'],
			'type' => $cfg['type'],
			$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_UICONF] => $cfg['id']
		);
		$DB->Execute('UPDATE uiconfig SET section = ?, var = ?, value = ?, description = ?, disabled = ?, type = ? WHERE id = ?',
			array_values($args));

		if ($SYSLOG)
			$SYSLOG->AddMessage(SYSLOG_RES_UICONF, SYSLOG_OPER_UPDATE,
				$args, array($SYSLOG_RESOURCE_KEYS[SYSLOG_RES_UICONF]));

		$SESSION->redirect('?m=configlist');
	}
	$config = $cfg;
}

$layout['pagetitle'] = trans('Option Edit: $a',$option);

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$SMARTY->assign('sections', $LMS->GetConfigSections());
$SMARTY->assign('error', $error);
$SMARTY->assign('config', $config);
$SMARTY->display('config/configedit.html');

?>
