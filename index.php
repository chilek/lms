<?php

/*
 * LMS version 1.7-cvs
 *
 *  (C) Copyright 2001-2005 LMS Developers
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

ini_set('session.name','LMSSESSIONID');

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

foreach(lms_parse_ini_file($CONFIG_FILE, true) as $key => $val)
	$CONFIG[$key] = $val;

// Check for configuration vars and set default values
$CONFIG['directories']['sys_dir'] = (!isset($CONFIG['directories']['sys_dir']) ? getcwd() : $CONFIG['directories']['sys_dir']);
$CONFIG['directories']['backup_dir'] = (!isset($CONFIG['directories']['backup_dir']) ? $CONFIG['directories']['sys_dir'].'/backups' : $CONFIG['directories']['backup_dir']);
$CONFIG['directories']['lib_dir'] = (!isset($CONFIG['directories']['lib_dir']) ? $CONFIG['directories']['sys_dir'].'/lib' : $CONFIG['directories']['lib_dir']);
$CONFIG['directories']['modules_dir'] = (!isset($CONFIG['directories']['modules_dir']) ? $CONFIG['directories']['sys_dir'].'/modules' : $CONFIG['directories']['modules_dir']);
$CONFIG['directories']['config_templates_dir'] = (!isset($CONFIG['directories']['config_templates_dir']) ? $CONFIG['directories']['sys_dir'].'/config_templates' : $CONFIG['directories']['config_templates_dir']);
$CONFIG['directories']['smarty_dir'] = (!isset($CONFIG['directories']['smarty_dir']) ? (is_readable('/usr/share/php/smarty/libs/Smarty.class.php') ? '/usr/share/php/smarty/libs' : $CONFIG['directories']['lib_dir'].'/Smarty') : $CONFIG['directories']['smarty_dir']);
$CONFIG['directories']['smarty_compile_dir'] = (!isset($CONFIG['directories']['smarty_compile_dir']) ? $CONFIG['directories']['sys_dir'].'/templates_c' : $CONFIG['directories']['smarty_compile_dir']);
$CONFIG['directories']['smarty_templates_dir'] = (!isset($CONFIG['directories']['smarty_templates_dir']) ? $CONFIG['directories']['sys_dir'].'/templates' : $CONFIG['directories']['smarty_templates_dir']);

foreach(lms_parse_ini_file($CONFIG['directories']['lib_dir'].'/config_defaults.ini', TRUE) as $section => $values)
	foreach($values as $key => $val)
		if(! isset($CONFIG[$section][$key]))
			$CONFIG[$section][$key] = $val;

$_SYSTEM_DIR = $CONFIG['directories']['sys_dir'];
$_BACKUP_DIR = $CONFIG['directories']['backup_dir'];
$_LIB_DIR = $CONFIG['directories']['lib_dir'];
$_MODULES_DIR = $CONFIG['directories']['modules_dir'];
$_SMARTY_DIR = $CONFIG['directories']['smarty_dir'];
$_SMARTY_COMPILE_DIR = $CONFIG['directories']['smarty_compile_dir'];
$_SMARTY_TEMPLATES_DIR = $CONFIG['directories']['smarty_templates_dir'];
$_DBTYPE = $CONFIG['database']['type'];
$_DBHOST = $CONFIG['database']['host'];
$_DBUSER = $CONFIG['database']['user'];
$_DBPASS = $CONFIG['database']['password'];
$_DBNAME = $CONFIG['database']['database'];

require_once($_LIB_DIR.'/checkdirs.php');
require_once($_LIB_DIR.'/checkconfig.php');

// Init database 

require_once($_LIB_DIR.'/LMSDB.php');

$DB = DBInit($_DBTYPE, $_DBHOST, $_DBUSER, $_DBPASS, $_DBNAME);

// Enable/disable data encoding conversion
// Call any of upgrade process before anything else

require_once($_LIB_DIR.'/dbencoding.php');
require_once($_LIB_DIR.'/upgradedb.php');

// Initialize templates engine (must be before locale settings)

require_once($_SMARTY_DIR.'/Smarty.class.php');

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

if($_FORCE_SSL && $_SERVER['HTTPS'] != 'on')
{
	header('Location: https://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);
	exit(0);
}

// Include required files (including sequence is important)

require_once($_LIB_DIR.'/language.php');
require_once($_LIB_DIR.'/unstrip.php');
require_once($_LIB_DIR.'/common.php');
require_once($_LIB_DIR.'/checkip.php');
require_once($_LIB_DIR.'/LMS.class.php');
require_once($_LIB_DIR.'/Auth.class.php');
require_once($_LIB_DIR.'/accesstable.php');
require_once($_LIB_DIR.'/Session.class.php');

// Initialize Session, Auth and LMS classes

$SESSION = new Session($DB, $CONFIG);
$AUTH = new Auth($DB, $SESSION);
$LMS = new LMS($DB, $AUTH, $CONFIG);
$LMS->lang = $_language;

// set some template and layout variables

$SMARTY->assign_by_ref('_LANG', $_LANG);
$SMARTY->assign_by_ref('LANGDEFS', $LANGDEFS);
$SMARTY->assign_by_ref('_language', $LMS->lang);
$SMARTY->assign('_dochref', is_dir('doc/html/'.$LMS->lang) ? 'doc/html/'.$LMS->lang.'/' : 'doc/html/en/');
$SMARTY->assign('_config',$CONFIG);
$SMARTY->template_dir = $_SMARTY_TEMPLATES_DIR;
$SMARTY->compile_dir = $_SMARTY_COMPILE_DIR;
$SMARTY->debugging = (isset($CONFIG['phpui']['smarty_debug']) ? chkconfig($CONFIG['phpui']['smarty_debug']) : FALSE);
$SMARTY->_tpl_vars['missing_strings'] = array();
require_once($_LIB_DIR.'/smarty_addons.php');

$layout['logname'] = $AUTH->logname;
$layout['logid'] = $AUTH->id;
$layout['lmsdbv'] = $DB->_version;
$layout['smarty_version'] = $SMARTY->_version;
$layout['uptime'] = uptime();
$layout['hostname'] = hostname();
$layout['lmsv'] = '1.7-cvs';
$layout['lmsvr'] = $LMS->_revision.'/'.$AUTH->_revision;
$layout['dberrors'] =& $DB->errors;

$SMARTY->assign_by_ref('menu', $LMS->MENU);

require_once($_LIB_DIR.'/menu.php');

$SMARTY->assign_by_ref('newmenu', $menu);
$SMARTY->assign_by_ref('layout', $layout);

header('X-Powered-By: LMS/'.$layout['lmsv']);

$error = NULL; // initialize error variable needed for (almost) all modules
$layout['popup'] = $_GET['popup'];

if($AUTH->islogged)
{
	if($AUTH->passwd == '')
		$SMARTY->assign('emptypasswd',TRUE);

	$module = (isset($_GET['m']) ? $_GET['m'] : '');
	$deny = FALSE;
	
	if (file_exists($_MODULES_DIR.'/'.$module.'.php'))
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
			include($_MODULES_DIR.'/'.$module.'.php');
		}
		else
			$SMARTY->display('noaccess.html');
	}
	elseif($module == '')
	{
		$layout['module'] = 'welcome';
		$SMARTY->assign('warning',!chkconfig($CONFIG['phpui']['disable_devel_warning']));
		include($_MODULES_DIR.'/welcome.php');
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
