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

function ConfigOptionExists($id) 
{
	global $LMS;
	return ($LMS->DB->GetOne('SELECT id FROM uiconfig WHERE id = ?', array($id)) ? TRUE : FALSE);
}

$id = $_GET['id'];

if($id && !ConfigOptionExists($id))
{
	header('Location: ?m=configlist');
	die;
}

$config = $LMS->DB->GetRow('SELECT * FROM uiconfig WHERE id = ?', array($id));
$option = $config['var'];

if($cfg = $_POST['config'])
{
	$cfg['id'] = $id;
	
	foreach($cfg as $key => $val) 
		$cfg[$key] = trim($val);
	
	if(!eregi("^[a-z0-9_-]+$", $cfg['var']))
    		$error['var'] = 'Nazwa opcji zawiera niepoprawne znaki!';

	if($cfg['var']=='')
		$error['var'] = 'Musisz podaæ nazwê opcji!';
	    
	if(($cfg['var']!=$config['var'] || $cfg['section']!=$config['section'])
		&& $LMS->GetConfigOptionId($cfg['var'], $cfg['section'])
	)
		$error['var'] = 'Opcja ju¿ jest w bazie!';

	if(!eregi("^[a-z0-9_-]+$", $cfg['section']) && $cfg['section']!='')
    		$error['section'] = 'Nazwa sekcji zawiera niepoprawne znaki!';
	    
	if($cfg['value']=='')
		$error['value'] = 'Opcja musi mieæ okre¶lon± warto¶æ!';
	elseif($msg = $LMS->CheckOption($cfg['var'], $cfg['value']))
		$error['value'] = $msg;
	
	if($cfg['disabled']!='1') $cfg['disabled'] = 0;

	if(!$error)
	{
		$LMS->DB->Execute('UPDATE uiconfig SET section = ?, var = ?, value = ?, description = ?, disabled = ? WHERE id = ?', 
				array(	$cfg['section'], 
					$cfg['var'], 
					$cfg['value'],
					$cfg['description'],
					$cfg['disabled'],
					$cfg['id']
					));
		$LMS->SetTS('uiconfig');
		header('Location: ?m=configlist');
		die;
	}
	$config = $cfg;
}

$layout['pagetitle'] = 'Edycja opcji: '.$option;

$_SESSION['backto'] = $_SERVER['QUERY_STRING'];

$SMARTY->assign('error', $error);
$SMARTY->assign('config', $config);
$SMARTY->assign('layout', $layout);
$SMARTY->display('configedit.html');

?>
