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

function ConfigOptionExistsById($id) 
{
	global $LMS;
	return ($LMS->DB->GetOne('SELECT id FROM uiconfig WHERE id = ?', array($id)) ? TRUE : FALSE);
}

$id = $_GET['id'];

if($id && !ConfigOptionExistsById($id))
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
		&& ConfigOptionExists($cfg['var'], $cfg['section'])
	)
	    $error['var'] = 'Opcja ju¿ jest w bazie!';

	if(!eregi("^[a-z0-9_-]+$", $cfg['section']) && $cfg['section']!='')
    	    $error['section'] = 'Nazwa sekcji zawiera niepoprawne znaki!';
	    
	if($cfg['value']=='')
	    $error['value'] = 'Opcja musi mieæ okre¶lon± warto¶æ!';
	
	if($cfg['disabled']!='1') $cfg['disabled'] = 0;

	// sprawdzenie warto¶ci niektórych opcji (z config_defaults.ini)
	switch($cfg['var'])
	{
	    case 'accountlist_pagelimit':
	    case 'ticketlist_pagelimit':
	    case 'balancelist_pagelimit':
	    case 'invoicelist_pagelimit':
	    case 'timeout':
		    if($cfg['value']<=0)
			    $error['value'] = 'Warto¶æ opcji \''.$cfg['var'].'\' musi byæ liczb± wiêksz± od zera!';
		break;
	    case 'reload_type':
		    if($cfg['value']!='sql' && $cfg['value']!='exec')
			    $error['value'] = 'Z³y typ reloadu. Obs³ugiwane typy: sql, exec!';
		break;
	}

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
