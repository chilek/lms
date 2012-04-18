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

// REPLACE THIS WITH PATH TO YOUR CONFIG FILE
// find alternative config files:
if (is_readable('lms.ini'))
	$CONFIG_FILE = 'lms.ini';
elseif (is_readable('/etc/lms/lms-' . $_SERVER['HTTP_HOST'] . '.ini'))
	$CONFIG_FILE = '/etc/lms/lms-' . $_SERVER['HTTP_HOST'] . '.ini';
elseif (!is_readable($CONFIG_FILE))
	die('Unable to read configuration file [' . $CONFIG_FILE . ']!');

$CONFIG = (array) parse_ini_file($CONFIG_FILE, true);

// Check for configuration vars and set default values
$CONFIG['directories']['sys_dir'] = (!isset($CONFIG['directories']['sys_dir']) ? getcwd() : $CONFIG['directories']['sys_dir']);
$CONFIG['directories']['backup_dir'] = (!isset($CONFIG['directories']['backup_dir']) ? $CONFIG['directories']['sys_dir'] . '/backups' : $CONFIG['directories']['backup_dir']);
$CONFIG['directories']['doc_dir'] = (!isset($CONFIG['directories']['doc_dir']) ? $CONFIG['directories']['sys_dir'] . '/documents' : $CONFIG['directories']['doc_dir']);
$CONFIG['directories']['lib_dir'] = (!isset($CONFIG['directories']['lib_dir']) ? $CONFIG['directories']['sys_dir'] . '/lib' : $CONFIG['directories']['lib_dir']);
$CONFIG['directories']['modules_dir'] = (!isset($CONFIG['directories']['modules_dir']) ? $CONFIG['directories']['sys_dir'] . '/modules' : $CONFIG['directories']['modules_dir']);
$CONFIG['directories']['config_templates_dir'] = (!isset($CONFIG['directories']['config_templates_dir']) ? $CONFIG['directories']['sys_dir'] . '/config_templates' : $CONFIG['directories']['config_templates_dir']);
$CONFIG['directories']['smarty_compile_dir'] = (!isset($CONFIG['directories']['smarty_compile_dir']) ? $CONFIG['directories']['sys_dir'] . '/templates_c' : $CONFIG['directories']['smarty_compile_dir']);
$CONFIG['directories']['smarty_templates_dir'] = (!isset($CONFIG['directories']['smarty_templates_dir']) ? $CONFIG['directories']['sys_dir'] . '/templates' : $CONFIG['directories']['smarty_templates_dir']);

define('SYS_DIR', $CONFIG['directories']['sys_dir']);
define('LIB_DIR', $CONFIG['directories']['lib_dir']);
define('DOC_DIR', $CONFIG['directories']['doc_dir']);
define('BACKUP_DIR', $CONFIG['directories']['backup_dir']);
define('MODULES_DIR', $CONFIG['directories']['modules_dir']);
define('SMARTY_COMPILE_DIR', $CONFIG['directories']['smarty_compile_dir']);
define('SMARTY_TEMPLATES_DIR', $CONFIG['directories']['smarty_templates_dir']);

// Do some checks and load config defaults

require_once(LIB_DIR . '/checkdirs.php');
require_once(LIB_DIR . '/config.php');

// Init database 

$_DBTYPE = $CONFIG['database']['type'];
$_DBHOST = $CONFIG['database']['host'];
$_DBUSER = $CONFIG['database']['user'];
$_DBPASS = $CONFIG['database']['password'];
$_DBNAME = $CONFIG['database']['database'];

require_once(LIB_DIR . '/LMSDB.php');

$DB = DBInit($_DBTYPE, $_DBHOST, $_DBUSER, $_DBPASS, $_DBNAME);

// Call any of upgrade process before anything else

require_once(LIB_DIR . '/upgradedb.php');

// test for proper version of Smarty

if (defined('Smarty::SMARTY_VERSION'))
	$ver_chunks = preg_split('/[- ]/', Smarty::SMARTY_VERSION);
else
	$ver_chunks = NULL;
if (count($ver_chunks) != 2 || version_compare('3.0', $ver_chunks[1]) > 0)
	die('<B>Wrong version of Smarty engine! We support only Smarty-3.x greater than 3.0.</B>');

define('SMARTY_VERSION', $ver_chunks[1]);

// system localization

require_once(LIB_DIR . '/language.php');

// Read configuration of LMS-UI from database

if ($cfg = $DB->GetAll('SELECT section, var, value FROM uiconfig WHERE disabled=0'))
	foreach ($cfg as $row)
		$CONFIG[$row['section']][$row['var']] = $row['value'];

// Redirect to SSL

$_FORCE_SSL = (isset($CONFIG['phpui']['force_ssl']) ? chkconfig($CONFIG['phpui']['force_ssl']) : FALSE);

if ($_FORCE_SSL && $_SERVER['HTTPS'] != 'on') {
	header('Location: https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
	exit(0);
}

// EXPERIMENTAL CODE! USE WITH CAUTION ;-)

$_LMSDIR = dirname(__FILE__);

require_once(LIB_DIR . '/ExecStack.class.php');

$ExecStack = new ExecStack($_LMSDIR . '/modules/', (isset($_GET['m']) ? $_GET['m'] : NULL), (isset($_GET['a']) ? $_GET['a'] : NULL));

// don't use foreach() below, because privileges checking action
// will probably need to remove some actions from stack
// Note: that action should be executed first

while (list(, $execute) = each($ExecStack->_EXECSTACK['actions'])) {
	// do include once, because testing that language for executed module has been already loaded
	// will take some time, so let's PHP decide if we already loaded it or what...

	@include_once($_LMSDIR . '/modules/' . $execute['module'] . '/lang/' . $_language . '.php');
	@include_once($_LMSDIR . '/modules/' . $execute['module'] . '/modinit.php');

	// execute action

	if ($ExecStack->needExec($execute['module'], $execute['action']))
		include($_LMSDIR . '/modules/' . $execute['module'] . '/actions/' . $execute['action'] . '.php');
}

foreach ($ExecStack->_EXECSTACK['templates'] as $step => $execute)
	$SMARTY->display($_LMSDIR . '/modules/' . $execute['module'] . '/templates/' . $execute['template'] . '.html');

$DB->Destroy();


echo '<PRE>';
print_r($ExecStack);
?>
