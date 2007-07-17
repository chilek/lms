<?php

/*
 * LMS version 1.9-cvs
 *
 *  (C) Copyright 2001-2007 LMS Developers
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

$CONFIG_FILE = '/etc/lms/lms.ini';

// PLEASE DO NOT MODIFY ANYTHING BELOW THIS LINE UNLESS YOU KNOW
// *EXACTLY* WHAT ARE YOU DOING!!!
// *******************************************************************

define('START_TIME', microtime());

// find alternative config files:

if(is_readable('lms.ini'))
	$CONFIG_FILE = 'lms.ini';
elseif(is_readable('/etc/lms/lms-'.$_SERVER['HTTP_HOST'].'.ini'))
	$CONFIG_FILE = '/etc/lms/lms-'.$_SERVER['HTTP_HOST'].'.ini';

// Parse configuration file
function lms_parse_ini_file($filename, $process_sections = false) 
{
	$ini_array = array();
	$section = '';
	$lines = file($filename);
	foreach($lines as $line) 
	{
		$line = trim($line);
		
		if($line == '' || $line[0] == ';' || $line[0] == '#') 
			continue;
		
		list($sec_name) = sscanf($line, "[%[^]]");
		
		if( $sec_name )
			$section = trim($sec_name);
		else 
		{
			list($property, $value) = sscanf($line, "%[^=] = '%[^']'");
			if ( !$property || !$value ) 
			{
				list($property, $value) = sscanf($line, "%[^=] = \"%[^\"]\"");
				if ( !$property || !$value ) 
				{
					list($property, $value) = sscanf($line, "%[^=] = %[^;#]");
					if( !$property || !$value ) 
						continue;
					else
						$value = trim($value, "\"'");
				}
			}
		
			$property = trim($property);
			$value = trim($value);
			
			if($process_sections) 
				$ini_array[$section][$property] = $value;
			else 
				$ini_array[$property] = $value;
		}
	}
	
	return $ini_array;
}

$CONFIG = array();

foreach(lms_parse_ini_file($CONFIG_FILE, true) as $key => $val)
	$CONFIG[$key] = $val;

// Check for configuration vars and set default values
$CONFIG['directories']['sys_dir'] = (!isset($CONFIG['directories']['sys_dir']) ? getcwd() : $CONFIG['directories']['sys_dir']);
$CONFIG['directories']['lib_dir'] = (!isset($CONFIG['directories']['lib_dir']) ? $CONFIG['directories']['sys_dir'].'/lib' : $CONFIG['directories']['lib_dir']);
$CONFIG['directories']['doc_dir'] = (!isset($CONFIG['directories']['doc_dir']) ? $CONFIG['directories']['sys_dir'].'/documents' : $CONFIG['directories']['doc_dir']);
$CONFIG['directories']['modules_dir'] = (!isset($CONFIG['directories']['modules_dir']) ? $CONFIG['directories']['sys_dir'].'/modules' : $CONFIG['directories']['modules_dir']);
$CONFIG['directories']['backup_dir'] = (!isset($CONFIG['directories']['backup_dir']) ? $CONFIG['directories']['sys_dir'].'/backups' : $CONFIG['directories']['backup_dir']);
$CONFIG['directories']['config_templates_dir'] = (!isset($CONFIG['directories']['config_templates_dir']) ? $CONFIG['directories']['sys_dir'].'/config_templates' : $CONFIG['directories']['config_templates_dir']);
$CONFIG['directories']['smarty_compile_dir'] = (!isset($CONFIG['directories']['smarty_compile_dir']) ? $CONFIG['directories']['sys_dir'].'/templates_c' : $CONFIG['directories']['smarty_compile_dir']);
$CONFIG['directories']['smarty_templates_dir'] = (!isset($CONFIG['directories']['smarty_templates_dir']) ? $CONFIG['directories']['sys_dir'].'/templates' : $CONFIG['directories']['smarty_templates_dir']);

foreach(lms_parse_ini_file($CONFIG['directories']['lib_dir'].'/config_defaults.ini', TRUE) as $section => $values)
	foreach($values as $key => $val)
		if(! isset($CONFIG[$section][$key]))
			$CONFIG[$section][$key] = $val;

define('SYS_DIR', $CONFIG['directories']['sys_dir']);
define('LIB_DIR', $CONFIG['directories']['lib_dir']);
define('DOC_DIR', $CONFIG['directories']['doc_dir']);
define('BACKUP_DIR', $CONFIG['directories']['backup_dir']);
define('MODULES_DIR', $CONFIG['directories']['modules_dir']);
define('SMARTY_COMPILE_DIR', $CONFIG['directories']['smarty_compile_dir']);
define('SMARTY_TEMPLATES_DIR', $CONFIG['directories']['smarty_templates_dir']);

$_DBTYPE = $CONFIG['database']['type'];
$_DBHOST = $CONFIG['database']['host'];
$_DBUSER = $CONFIG['database']['user'];
$_DBPASS = $CONFIG['database']['password'];
$_DBNAME = $CONFIG['database']['database'];

require_once(LIB_DIR.'/checkdirs.php');
require_once(LIB_DIR.'/checkconfig.php');

// Init database 

require_once(LIB_DIR.'/LMSDB.php');

$DB = DBInit($_DBTYPE, $_DBHOST, $_DBUSER, $_DBPASS, $_DBNAME);

// Enable/disable data encoding conversion
// Call any of upgrade process before anything else

require_once(LIB_DIR.'/dbencoding.php');
require_once(LIB_DIR.'/upgradedb.php');

// Initialize templates engine (must be before locale settings)

require_once(LIB_DIR.'/Smarty/Smarty.class.php');

$SMARTY = new Smarty;

// test for proper version of Smarty

if(version_compare('2.6.0', $SMARTY->_version) > 0)
	die('<B>Old version of Smarty engine! You must get newest from <A HREF="http://smarty.php.net/distributions/Smarty-2.6.8.tar.gz">http://smarty.php.net/distributions/Smarty-2.6.8.tar.gz</A></B>');

// Read configuration of LMS-UI from database

if($cfg = $DB->GetAll('SELECT section, var, value FROM uiconfig WHERE disabled=0'))
	foreach($cfg as $row)
		$CONFIG[$row['section']][$row['var']] = $row['value'];

// Redirect to SSL

$_FORCE_SSL = (isset($CONFIG['phpui']['force_ssl']) ? chkconfig($CONFIG['phpui']['force_ssl']) : FALSE);

if($_FORCE_SSL && (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] != 'on'))
{
	header('Location: https://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);
	exit(0);
}

// Include required files (including sequence is important)

require_once(LIB_DIR.'/language.php');
require_once(LIB_DIR.'/unstrip.php');
require_once(LIB_DIR.'/definitions.php');
require_once(LIB_DIR.'/common.php');
require_once(LIB_DIR.'/checkip.php');
require_once(LIB_DIR.'/LMS.class.php');
require_once(LIB_DIR.'/Auth.class.php');
require_once(LIB_DIR.'/accesstable.php');
require_once(LIB_DIR.'/Session.class.php');

// Initialize Session, Auth and LMS classes

$SESSION = new Session($DB, $CONFIG['phpui']['timeout']);
$AUTH = new Auth($DB, $SESSION);
$LMS = new LMS($DB, $AUTH, $CONFIG);
$LMS->lang = $_language;

// set some template and layout variables

$SMARTY->assign_by_ref('_LANG', $_LANG);
$SMARTY->assign_by_ref('LANGDEFS', $LANGDEFS);
$SMARTY->assign_by_ref('_language', $LMS->lang);
$SMARTY->assign('_dochref', is_dir('doc/html/'.$LMS->lang) ? 'doc/html/'.$LMS->lang.'/' : 'doc/html/en/');
$SMARTY->assign('_config',$CONFIG);
$SMARTY->template_dir = SMARTY_TEMPLATES_DIR;
$SMARTY->compile_dir = SMARTY_COMPILE_DIR;
$SMARTY->debugging = (isset($CONFIG['phpui']['smarty_debug']) ? chkconfig($CONFIG['phpui']['smarty_debug']) : FALSE);
require_once(LIB_DIR.'/menu.php');

$layout['logname'] = $AUTH->logname;
$layout['logid'] = $AUTH->id;
$layout['lmsdbv'] = $DB->_version;
$layout['smarty_version'] = $SMARTY->_version;
$layout['hostname'] = hostname();
$layout['lmsv'] = '1.9-cvs';
$layout['lmsvr'] = $LMS->_revision.'/'.$AUTH->_revision;
$layout['dberrors'] =& $DB->errors;

$SMARTY->assign_by_ref('newmenu', $menu);
$SMARTY->assign_by_ref('layout', $layout);

header('X-Powered-By: LMS/'.$layout['lmsv']);

$error = NULL; // initialize error variable needed for (almost) all modules
$layout['popup'] = isset($_GET['popup']) ? true : false;

if($AUTH->islogged)
{
	$module = (isset($_GET['m']) ? $_GET['m'] : '');
	$deny = FALSE;

	if($module == '')
	{
		$module = $CONFIG['phpui']['default_module'];
		$SMARTY->assign('warning', isset($CONFIG['phpui']['disable_devel_warning']) ? !chkconfig($CONFIG['phpui']['disable_devel_warning']) : true);
	}
	
	if(file_exists(MODULES_DIR.'/'.$module.'.php'))
	{
		if(eregi($access['allow'], $module))
			$allow = TRUE;
		else{
			$rights = $LMS->GetUserRights($AUTH->id);
			if($rights)
				foreach($rights as $level)
					if(isset($access['table'][$level]['deny_reg']) && eregi($access['table'][$level]['deny_reg'], $module))
						$deny = TRUE;
					elseif(isset($access['table'][$level]['allow_reg']) && eregi($access['table'][$level]['allow_reg'], $module))
						$allow = TRUE;
		}

		if($allow && ! $deny)
		{
			$layout['module'] = $module;
			include(LIB_DIR.'/views.php');
			include(MODULES_DIR.'/'.$module.'.php');
		}
		else
			$SMARTY->display('noaccess.html');
	}
	else
	{
		$layout['module'] = 'notfound';
		$layout['pagetitle'] = trans('Error!');
		$SMARTY->assign('layout', $layout);
		$SMARTY->assign('server', $_SERVER);
		$SMARTY->display('notfound.html');
	}
	
	if($SESSION->get('lastmodule') != $module)
		$SESSION->save('lastmodule', $module);
}
else
{
	$SMARTY->assign('error', $AUTH->error);
	$SMARTY->assign('target','?'.$_SERVER['QUERY_STRING']);
	$SMARTY->display('login.html');
}

$SESSION->close();
$DB->Destroy();

?>
