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

// REPLACE THIS WITH PATH TO YOU CONFIG FILE

$CONFIG_FILE = '/etc/lms/lms.ini';

// PLEASE DO NOT MODIFY ANYTHING BELOW THIS LINE UNLESS YOU KNOW
// *EXACTLY* WHAT ARE YOU DOING!!!
// *******************************************************************

// Parse configuration file
$CONFIG = (array) parse_ini_file($CONFIG_FILE, true);

// Check for configuration vars and set default values
$CONFIG['directories']['sys_dir'] = (! $CONFIG['directories']['sys_dir'] ? getcwd() : $CONFIG['directories']['sys_dir']);
$CONFIG['directories']['backup_dir'] = (! $CONFIG['directories']['backup_dir'] ? $CONFIG['directories']['sys_dir'].'/backups' : $CONFIG['directories']['backup_dir']);
$CONFIG['directories']['lib_dir'] = (! $CONFIG['directories']['lib_dir'] ? $CONFIG['directories']['sys_dir'].'/lib' : $CONFIG['directories']['lib_dir']);
$CONFIG['directories']['modules_dir'] = (! $CONFIG['directories']['modules_dir'] ? $CONFIG['directories']['sys_dir'].'/modules' : $CONFIG['directories']['modules_dir']);
$CONFIG['directories']['smarty_compile_dir'] = (! $CONFIG['directories']['smarty_compile_dir'] ? $CONFIG['directories']['sys_dir'].'/templates_c' : $CONFIG['directories']['smarty_compile_dir']);
$CONFIG['directories']['smarty_templates_dir'] = (! $CONFIG['directories']['smarty_templates_dir'] ? $CONFIG['directories']['sys_dir'].'/templates' : $CONFIG['directories']['smarty_templates_dir']);

define('SYS_DIR', $CONFIG['directories']['sys_dir']);
define('LIB_DIR', $CONFIG['directories']['lib_dir']);
define('BACKUP_DIR', $CONFIG['directories']['backup_dir']);
define('MODULES_DIR', $CONFIG['directories']['modules_dir']);
define('SMARTY_COMPILE_DIR', $CONFIG['directories']['smarty_compile_dir']);
define('SMARTY_TEMPLATES_DIR', $CONFIG['directories']['smarty_templates_dir']);

// Load config defaults

require_once(LIB_DIR.'/config.php');

// Init database 
$_DBTYPE = $CONFIG['database']['type'];
$_DBHOST = $CONFIG['database']['host'];
$_DBUSER = $CONFIG['database']['user'];
$_DBPASS = $CONFIG['database']['password'];
$_DBNAME = $CONFIG['database']['database'];

require_once(LIB_DIR.'/LMSDB.php');

$DB = DBInit($_DBTYPE, $_DBHOST, $_DBUSER, $_DBPASS, $_DBNAME);

// Initialize templates engine

require_once(LIB_DIR.'/Smarty/Smarty.class.php');

$SMARTY = new Smarty;
$SESSION = NULL;

// Include required files (including sequence is important)

require_once(LIB_DIR.'/language.php');
require_once(LIB_DIR.'/common.php');
require_once(LIB_DIR.'/LMS.class.php');

// Initialize LMS class

$LMS = new LMS($DB, $SESSION, $CONFIG);
$LMS->ui_lang = $_ui_language;
$LMS->lang = $_language;

// set some template and layout variables

$SMARTY->assignByRef('_LANG', $_LANG);
$SMARTY->assignByRef('LANGDEFS', $LANGDEFS);
$SMARTY->assignByRef('_ui_language', $LMS->ui_lang);
$SMARTY->assignByRef('_language', $LMS->lang);
$SMARTY->template_dir = getcwd();
$SMARTY->compile_dir = SMARTY_COMPILE_DIR;
include('lang.php');

$SMARTY->assignByRef('layout', $layout);

if (isset($_SERVER['HTTP_X_FORWARDED_FOR']))
{
	$forwarded_ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
	$nodeid = $LMS->GetNodeIDByIP($forwarded_ip['0']);    
} 
else 
{
	$nodeid = $LMS->GetNodeIDByIP(str_replace('::ffff:', '', $_SERVER['REMOTE_ADDR']));
}

$customerid = $LMS->GetNodeOwner($nodeid);    
$nodeinfo = $LMS->GetNode($nodeid);    

if (isset($_GET['readed']))
{
	$DB->Execute('UPDATE nodes SET warning = 0 WHERE id = ?', array($nodeid));
	header('Location: '.$_GET['oldurl']);
} 
else 
{
	$customerinfo = $LMS->GetCustomer($customerid);
	$layout['oldurl'] = $_GET['oldurl'];
	$SMARTY->assign('customerinfo', $customerinfo);
        $SMARTY->assign('nodeinfo', $nodeinfo);
	$SMARTY->assign('layout', $layout);
	$SMARTY->display('message.html');
}

?>
