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

$layout['pagetitle'] = trans('New Config Option');

$config = isset($_POST['config']) ? $_POST['config'] : array();

if(sizeof($config))
{
	foreach($config as $key => $val)
	    $config[$key] = trim($val);
	
	if(!($config['name'] || $config['value'] || $config['description']))
	{
		$SESSION->redirect('?m=configlist');
	}
	
	if($config['name']=='')
		$error['name'] = trans('Option name is required!');
	elseif(strlen($config['name'])>64)
		$error['name'] = trans('Option name is too long (max.64 characters)!');
	elseif(!preg_match('/^[a-z0-9_-]+$/', $config['name']))
    		$error['name'] = trans('Option name contains forbidden characters!');
	elseif($LMS->GetConfigOptionId($config['name'], $config['section']))
		$error['name'] = trans('Option exists!'); 

	$section = empty($config['section']) ? $config['newsection'] : $config['section'];
	if (empty($section))
		$error['newsection'] = trans('Section name can\'t be empty!');
	elseif (!preg_match('/^[a-z0-9_-]+$/', $section))
		$error[empty($config['section']) ? 'newsection' : 'section'] = trans('Section name contains forbidden characters!');

	if($config['value']=='')
		$error['value'] = trans('Option with empty value not allowed!');
	elseif($msg = $LMS->CheckOption($config['name'], $config['value']))
	        $error['value'] = $msg;
	
	if(!isset($config['disabled'])) $config['disabled'] = 0;

	if(!$error)
	{
		$DB->Execute('INSERT INTO uiconfig (section, var, value, description, disabled) VALUES (?, ?, ?, ?, ?)', 
				array($section, 
					$config['name'], 
					$config['value'],
					$config['description'],
					$config['disabled']
					));
		
		if(!isset($config['reuse']))
		{
			$SESSION->redirect('?m=configlist');
		}
		unset($config['name']);
		unset($config['value']);
		unset($config['description']);
		unset($config['disabled']);
	}
}

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

if(isset($_GET['section']))
	$config['section'] = $_GET['section'];

$SMARTY->assign('sections', $LMS->GetConfigSections());
$SMARTY->assign('error', $error);
$SMARTY->assign('config', $config);
$SMARTY->display('configadd.html');

?>
