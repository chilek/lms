<?php

/*
 * LMS version 1.5-cvs
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
	$_CONFIG[$key] = $val;

// Check for configuration vars and set default values
$_CONFIG['directories']['sys_dir'] = (! $_CONFIG['directories']['sys_dir'] ? getcwd() : $_CONFIG['directories']['sys_dir']);
$_CONFIG['directories']['backup_dir'] = (! $_CONFIG['directories']['backup_dir'] ? $_CONFIG['directories']['sys_dir'].'/backups' : $_CONFIG['directories']['backup_dir']);
$_CONFIG['directories']['lib_dir'] = (! $_CONFIG['directories']['lib_dir'] ? $_CONFIG['directories']['sys_dir'].'/lib' : $_CONFIG['directories']['lib_dir']);
$_CONFIG['directories']['modules_dir'] = (! $_CONFIG['directories']['modules_dir'] ? $_CONFIG['directories']['sys_dir'].'/modules' : $_CONFIG['directories']['modules_dir']);
$_CONFIG['directories']['config_templates_dir'] = (! $_CONFIG['directories']['config_templates_dir'] ? $_CONFIG['directories']['sys_dir'].'/config_templates' : $_CONFIG['directories']['config_templates_dir']);
$_CONFIG['directories']['smarty_dir'] = (! $_CONFIG['directories']['smarty_dir'] ? (is_readable('/usr/share/php/smarty/libs/Smarty.class.php') ? '/usr/share/php/smarty/libs' : $_CONFIG['directories']['lib_dir'].'/Smarty') : $_CONFIG['directories']['smarty_dir']);
$_CONFIG['directories']['smarty_compile_dir'] = (! $_CONFIG['directories']['smarty_compile_dir'] ? $_CONFIG['directories']['sys_dir'].'/templates_c' : $_CONFIG['directories']['smarty_compile_dir']);
$_CONFIG['directories']['smarty_templates_dir'] = (! $_CONFIG['directories']['smarty_templates_dir'] ? $_CONFIG['directories']['sys_dir'].'/templates' : $_CONFIG['directories']['smarty_templates_dir']);

foreach(lms_parse_ini_file($_CONFIG['directories']['lib_dir'].'/config_defaults.ini', TRUE) as $section => $values)
	foreach($values as $key => $val)
		if(! isset($_CONFIG[$section][$key]))
			$_CONFIG[$section][$key] = $val;

$_SYSTEM_DIR = $_CONFIG['directories']['sys_dir'];
$_BACKUP_DIR = $_CONFIG['directories']['backup_dir'];
$_LIB_DIR = $_CONFIG['directories']['lib_dir'];
$_MODULES_DIR = $_CONFIG['directories']['modules_dir'];
$_SMARTY_DIR = $_CONFIG['directories']['smarty_dir'];
$_SMARTY_COMPILE_DIR = $_CONFIG['directories']['smarty_compile_dir'];
$_SMARTY_TEMPLATES_DIR = $_CONFIG['directories']['smarty_templates_dir'];
$_DBTYPE = $_CONFIG['database']['type'];
$_DBHOST = $_CONFIG['database']['host'];
$_DBUSER = $_CONFIG['database']['user'];
$_DBPASS = $_CONFIG['database']['password'];
$_DBNAME = $_CONFIG['database']['database'];

require_once($_LIB_DIR.'/checkdirs.php');
require_once($_LIB_DIR.'/checkconfig.php');

// Init database 

require_once($_LIB_DIR.'/LMSDB.php');

$DB = DBInit($_DBTYPE, $_DBHOST, $_DBUSER, $_DBPASS, $_DBNAME);

// Call any of upgrade process before anything else

require_once($_LIB_DIR.'/upgradedb.php');

// Read configuration of LMS-UI from database

if($cfg = $DB->GetAll('SELECT section, var, value FROM uiconfig WHERE disabled=0'))
	foreach($cfg as $row)
		$_CONFIG[$row['section']][$row['var']] = $row['value'];

// Redirect to SSL

$_FORCE_SSL = chkconfig($_CONFIG['phpui']['force_ssl']);

if($_FORCE_SSL && $_SERVER['HTTPS'] != 'on')
{
	header('Location: https://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);
	exit(0);
}

$_TIMEOUT = $_CONFIG['phpui']['timeout'];

// Initialize templates engine

require_once($_SMARTY_DIR.'/Smarty.class.php');

$SMARTY = new Smarty;

// test for proper version of Smarty

if(version_compare('2.5.0', $SMARTY->_version) > 0)
	die('<B>'.trans('Old version of Smarty engine! You must get newest from $0.','<A HREF="http://smarty.php.net/distributions/Smarty-2.5.0.tar.gz">http://smarty.php.net/distributions/Smarty-2.5.0.tar.gz</A>').'</B>');

// Include required files (including sequence is important)

require_once($_LIB_DIR.'/unstrip.php');
require_once($_LIB_DIR.'/language.php');
require_once($_LIB_DIR.'/common.php');
require_once($_LIB_DIR.'/checkip.php');
require_once($_LIB_DIR.'/LMS.class.php');
require_once($_LIB_DIR.'/Session.class.php');
require_once($_LIB_DIR.'/accesstable.php');

// Initialize Session and LMS classes

$SESSION = new Session($DB, $_TIMEOUT);

$LMS = new LMS($DB, $SESSION, $_CONFIG);
$LMS->CONFIG = $_CONFIG;
$LMS->lang = $_language;

// set some template and layout variables

$SMARTY->assign_by_ref('_LANG', $_LANG);
$SMARTY->assign_by_ref('LANGDEFS', $LANGDEFS);
$SMARTY->assign_by_ref('_language', $LMS->lang);
$SMARTY->assign('_dochref', is_dir('doc/html/'.$LMS->lang) ? 'doc/html/'.$LMS->lang.'/' : 'doc/html/en/');
$SMARTY->assign('_config',$_CONFIG);
$SMARTY->template_dir = $_SMARTY_TEMPLATES_DIR;
$SMARTY->compile_dir = $_SMARTY_COMPILE_DIR;
$SMARTY->debugging = chkconfig($_CONFIG['phpui']['smarty_debug']);
require_once($_LIB_DIR.'/smarty_addons.php');

$layout['logname'] = $SESSION->logname;
$layout['logid'] = $SESSION->id;
$layout['lmsv'] = '1.5-cvs ('.$LMS->_revision.'/'.$SESSION->_revision.')';
$layout['lmsdbv'] = $DB->_version;
$layout['smarty_version'] = $SMARTY->_version;
$layout['uptime'] = uptime();
$layout['hostname'] = hostname();
$layout['lmsvs'] = '1.5-cvs';
$layout['dberrors'] =& $DB->errors;

$SMARTY->assign_by_ref('menu', $LMS->MENU);
$SMARTY->assign_by_ref('layout', $layout);

header('X-Powered-By: LMS/'.$layout['lmsv']);
if($SESSION->islogged)
{

	if($SESSION->passwd == '')
		$SMARTY->assign('emptypasswd',TRUE);

	$module = $_GET['m'];
	
	if (file_exists($_MODULES_DIR.'/'.$module.'.php'))
	{
		if(eregi($access['allow'], $module))
			$allow = TRUE;
		else{
			$rights = $LMS->GetAdminRights($SESSION->id);
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
		$SMARTY->assign('warning',!chkconfig($_CONFIG['phpui']['disable_devel_warning']));
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
	
	if($_SESSION['lastmodule'] != $module)
		$_SESSION['lastmodule'] = $module;
}
else
{
	$SMARTY->assign('error', $SESSION->error);
	$SMARTY->assign('target','?'.$_SERVER['QUERY_STRING']);
	$SMARTY->display('login.html');
	
}

$DB->Destroy();

?>
