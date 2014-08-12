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

$CONFIG_FILE = (is_readable('lms.ini')) ? 'lms.ini' : '/etc/lms/lms.ini';

// PLEASE DO NOT MODIFY ANYTHING BELOW THIS LINE UNLESS YOU KNOW
// *EXACTLY* WHAT ARE YOU DOING!!!
// *******************************************************************
ini_set('error_reporting', E_ALL&~E_NOTICE);

// Parse configuration file
$CONFIG = (array) parse_ini_file($CONFIG_FILE, true);

// Check for configuration vars and set default values
$CONFIG['directories']['sys_dir'] = (!isset($CONFIG['directories']['sys_dir']) ? getcwd() : $CONFIG['directories']['sys_dir']);
$CONFIG['directories']['backup_dir'] = (!isset($CONFIG['directories']['backup_dir']) ? $CONFIG['directories']['sys_dir'].'/backups' : $CONFIG['directories']['backup_dir']);
$CONFIG['directories']['lib_dir'] = (!isset($CONFIG['directories']['lib_dir']) ? $CONFIG['directories']['sys_dir'].'/lib' : $CONFIG['directories']['lib_dir']);
$CONFIG['directories']['modules_dir'] = (!isset($CONFIG['directories']['modules_dir']) ? $CONFIG['directories']['sys_dir'].'/modules' : $CONFIG['directories']['modules_dir']);
$CONFIG['directories']['smarty_compile_dir'] = (!isset($CONFIG['directories']['smarty_compile_dir']) ? $CONFIG['directories']['sys_dir'].'/templates_c' : $CONFIG['directories']['smarty_compile_dir']);
$CONFIG['directories']['smarty_templates_dir'] = (!isset($CONFIG['directories']['smarty_templates_dir']) ? $CONFIG['directories']['sys_dir'].'/templates' : $CONFIG['directories']['smarty_templates_dir']);

define('SYS_DIR', $CONFIG['directories']['sys_dir']);
define('LIB_DIR', $CONFIG['directories']['lib_dir']);
define('BACKUP_DIR', $CONFIG['directories']['backup_dir']);
define('MODULES_DIR', $CONFIG['directories']['modules_dir']);
define('SMARTY_COMPILE_DIR', $CONFIG['directories']['smarty_compile_dir']);
define('SMARTY_TEMPLATES_DIR', $CONFIG['directories']['smarty_templates_dir']);

// Load autloader
require_once(LIB_DIR.'/autoloader.php');

// Load config defaults

require_once(LIB_DIR.'/config.php');

// Init database

$DB = null;

try {

    $DB = LMSDB::getInstance();

} catch (Exception $ex) {
    
    trigger_error($ex->getMessage(), E_USER_WARNING);
    
    // can't working without database
    die("Fatal error: cannot connect to database!\n");
    
}

// Initialize templates engine
$SMARTY = new Smarty;

// Include required files (including sequence is important)

require_once(LIB_DIR.'/unstrip.php');
require_once(LIB_DIR.'/language.php');
require_once(LIB_DIR.'/definitions.php');
require_once(LIB_DIR.'/common.php');

// Initialize LMS class

$AUTH = NULL;
$LMS = new LMS($DB, $AUTH);
$LMS->ui_lang = $_ui_language;
$LMS->lang = $_language;

// set some template and layout variables

$SMARTY->assignByRef('_LANG', $_LANG);
$SMARTY->assignByRef('LANGDEFS', $LANGDEFS);
$SMARTY->assignByRef('_ui_language', $LMS->ui_lang);
$SMARTY->assignByRef('_language', $LMS->lang);
$SMARTY->template_dir = getcwd();
$SMARTY->compile_dir = SMARTY_COMPILE_DIR;
@include('locale/'.$LMS->ui_lang.'/strings.php');

$layout['lmsv'] = '1.11-git';

$SMARTY->assignByRef('layout', $layout);

header('X-Powered-By: LMS/'.$layout['lmsv']);

$_SERVER['REMOTE_ADDR'] = str_replace('::ffff:','',$_SERVER['REMOTE_ADDR']);

if($customerid = $LMS->GetNodeOwner($LMS->GetNodeIDByIP($_SERVER['REMOTE_ADDR'])))
{
	$balance = $LMS->GetCustomerBalanceList($customerid);
	$customerinfo = $LMS->GetCustomer($customerid);

    $customerinfo['tariffsvalue'] = $LMS->GetCustomerTariffsValue($customerid);
}

$SMARTY->assign('customerinfo', $customerinfo);
$SMARTY->assign('balance', $balance);
$SMARTY->display('customer.html');

?>
