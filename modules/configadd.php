<?php

/*
 * LMS version 1.5-cvs
 *
 *  (C) Copyright 2001-2004 LMS Developers
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

function ConfigOptionExists($var, $section) 
{
	global $LMS;
	return ($LMS->DB->GetOne('SELECT id FROM uiconfig WHERE section = ? AND var = ?', array($section, $var)) ? TRUE : FALSE);
}

$layout['pagetitle'] = 'Dodanie opcji konfiguracyjnej';

if($config = $_POST['config'])
{
	if(!($config['name'] || $config['value'] || $config['description']))
	{
		header('Location: ?m=configlist');
		die;
	}
	
	if(!eregi("^[a-z0-9_-]+$", $config['name']))
    	    $error['name'] = 'Nazwa opcji zawiera niepoprawne znaki!';

	if($config['name']=='')
	    $error['name'] = 'Musisz poda� nazw� opcji!';
	    
	if(ConfigOptionExists($config['name'], $config['section']))
	    $error['name'] = 'Opcja ju� jest w bazie!'; 

	if(!eregi("^[a-z0-9_-]+$", $config['section']) && $config['section']!='')
    	    $error['section'] = 'Nazwa sekcji zawiera niepoprawne znaki!';
	    
	if($config['value']=='')
	    $error['value'] = 'Opcja musi mie� okre�lon� warto��!';
	
	if($config['disabled']!='1') $config['disabled'] = 0;

	if(!$error)
	{
		$LMS->DB->Execute('INSERT INTO uiconfig (section, var, value, description, disabled) VALUES (?, ?, ?, ?, ?)', 
				array(	$config['section'], 
					$config['name'], 
					$config['value'],
					$config['description'],
					$config['disabled']
					));
		if(!$config['reuse'])
		{
			header('Location: ?m=configlist');
			die;
		}
		unset($config);
	}
}

$_SESSION['backto'] = $_SERVER['QUERY_STRING'];

$SMARTY->assign('error', $error);
$SMARTY->assign('config', $config);
$SMARTY->assign('layout', $layout);
$SMARTY->display('configadd.html');

?>
