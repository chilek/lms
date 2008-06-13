<?php

/*
 *  LMS Userpanel version 1.11-cvs
 *
 *  (C) Copyright 2001-2008 LMS Developers
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

$CONFIG = array();

foreach(lms_parse_ini_file($CONFIG_FILE, true) as $key => $val)
     $CONFIG[$key] = $val;

// Check for configuration vars and set default values
if(empty($CONFIG['directories']['sys_dir']) || !file_exists($CONFIG['directories']['sys_dir']))
	die('System directory is not set or not exists!');
else
	$CONFIG['directories']['sys_dir'] = $CONFIG['directories']['sys_dir'];
$CONFIG['directories']['lib_dir'] = (!isset($CONFIG['directories']['lib_dir']) ? $CONFIG['directories']['sys_dir'].'/lib' : $CONFIG['directories']['lib_dir']);
$CONFIG['directories']['modules_dir'] = (!isset($CONFIG['directories']['modules_dir']) ? $CONFIG['directories']['sys_dir'].'/modules' : $CONFIG['directories']['modules_dir']);
$CONFIG['directories']['userpanel_dir'] = (!isset($CONFIG['directories']['userpanel_dir']) ? getcwd() : $CONFIG['directories']['userpanel_dir']);
$CONFIG['directories']['smarty_compile_dir'] = $CONFIG['directories']['userpanel_dir'].'/templates_c';

foreach(lms_parse_ini_file($CONFIG['directories']['lib_dir'].'/config_defaults.ini', TRUE) as $section => $values)
	foreach($values as $key => $val)
    		if(! isset($CONFIG[$section][$key]))
			$CONFIG[$section][$key] = $val;

define('USERPANEL_DIR', $CONFIG['directories']['userpanel_dir']);
define('USERPANEL_LIB_DIR', USERPANEL_DIR.'/lib/');
define('USERPANEL_MODULES_DIR', USERPANEL_DIR.'/modules/');

define('SYS_DIR', $CONFIG['directories']['sys_dir']);
define('LIB_DIR', $CONFIG['directories']['lib_dir']);
define('MODULES_DIR', $CONFIG['directories']['modules_dir']);
define('SMARTY_COMPILE_DIR', $CONFIG['directories']['smarty_compile_dir']);

$_DBTYPE = $CONFIG['database']['type'];
$_DBHOST = $CONFIG['database']['host'];
$_DBUSER = $CONFIG['database']['user'];
$_DBPASS = $CONFIG['database']['password'];
$_DBNAME = $CONFIG['database']['database'];

// include required files

require_once(LIB_DIR.'/checkconfig.php');
require_once(LIB_DIR.'/LMSDB.php');

// Initialize database

$DB = DBInit($_DBTYPE, $_DBHOST, $_DBUSER, $_DBPASS, $_DBNAME);

require_once(LIB_DIR.'/dbencoding.php');

// Initialize templates engine (must be before locale settings)

require_once(LIB_DIR.'/Smarty/Smarty.class.php');

$SMARTY = new Smarty;

// test for proper version of Smarty

if(version_compare('2.6.0', $SMARTY->_version) > 0)
     die('<B>Old version of Smarty engine! You shuld get newest from <A HREF="http://smarty.php.net/distributions/Smarty-2.6.9.tar.gz">http://smarty.php.net/distributions/Smarty-2.6.9.tar.gz</A>!</B>');

// Read configuration of LMS-UI from database

if($cfg = $DB->GetAll('SELECT section, var, value FROM uiconfig WHERE disabled=0'))
        foreach($cfg as $row)
                $CONFIG[$row['section']][$row['var']] = $row['value'];

// Redirect to SSL

$_FORCE_SSL = chkconfig($CONFIG['phpui']['force_ssl']);

if($_FORCE_SSL && $_SERVER['HTTPS'] != 'on')
{
     header('Location: https://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);
     exit(0);
}

$_TIMEOUT = $CONFIG['phpui']['timeout'];

// Include required files (including sequence is important)

require_once(LIB_DIR.'/language.php');
include_once(LIB_DIR.'/definitions.php');
require_once(LIB_DIR.'/unstrip.php');
require_once(LIB_DIR.'/common.php');
require_once(LIB_DIR.'/LMS.class.php');

$AUTH = NULL;
$LMS = new LMS($DB, $AUTH, $CONFIG);

require_once(USERPANEL_LIB_DIR.'/checkdirs.php');
require_once(USERPANEL_LIB_DIR.'/Session.class.php');
require_once(USERPANEL_LIB_DIR.'/Userpanel.class.php');
require_once(USERPANEL_LIB_DIR.'/ULMS.class.php');
@include(USERPANEL_DIR.'/lib/locale/'.$_language.'/strings.php');

unset($LMS); // reset LMS class to enable wrappers for LMS older versions

$LMS = new ULMS($DB, $AUTH, $CONFIG);
$SESSION = new Session($DB, $_TIMEOUT);
$USERPANEL = new USERPANEL($DB, $SESSION, $CONFIG);
$LMS->lang = $_language;

// Initialize modules

$dh  = opendir(USERPANEL_MODULES_DIR);
while (false !== ($filename = readdir($dh))) {
    if ((ereg("^[a-zA-Z0-9]",$filename)) && (is_dir(USERPANEL_MODULES_DIR.$filename)) && file_exists(USERPANEL_MODULES_DIR.$filename.'/configuration.php'))
    {
	@include(USERPANEL_MODULES_DIR.$filename.'/locale/'.$_language.'/strings.php');
	include(USERPANEL_MODULES_DIR.$filename.'/configuration.php');
    }
};																						    

$SMARTY->assign('_config',$CONFIG);
$SMARTY->assign_by_ref('_LANG', $_LANG);
$SMARTY->assign_by_ref('LANGDEFS', $LANGDEFS);
$SMARTY->assign_by_ref('_language', $LMS->lang);
$SMARTY->template_dir = USERPANEL_DIR.'/templates/';
$SMARTY->compile_dir = SMARTY_COMPILE_DIR;
$SMARTY->debugging = chkconfig($CONFIG['phpui']['smarty_debug']);
require_once(USERPANEL_LIB_DIR.'/smarty_addons.php');

$layout['upv'] = $USERPANEL->_version.' ('.$USERPANEL->_revision.'/'.$SESSION->_revision.')';
$layout['lmsdbv'] = $DB->_version;
$layout['lmsv'] = $LMS->_version;
$layout['smarty_version'] = $SMARTY->_version;
$layout['hostname'] = hostname();
$layout['dberrors'] =& $DB->errors;

$SMARTY->assign_by_ref('modules', $USERPANEL->MODULES);
$SMARTY->assign_by_ref('layout', $layout);

header('X-Powered-By: LMS/'.$layout['lmsv']);

if($SESSION->islogged)
{
	$module = isset($_GET['m']) ? $_GET['m'] : '';

	if (isset($USERPANEL->MODULES[$module])) $USERPANEL->MODULES[$module]['selected'] = true;

	// Userpanel rights module
	$rights = $USERPANEL->GetCustomerRights($SESSION->id);
	$SMARTY->assign('rights', $rights);

	if(isset($LMS->CONFIG['userpanel']['hide_nodes_modules']) && chkconfig($LMS->CONFIG['userpanel']['hide_nodes_modules']))
	{
		if(!$DB->GetOne('SELECT COUNT(*) FROM nodes WHERE ownerid = ? LIMIT 1', array($SESSION->id)))
		{
			unset($USERPANEL->MODULES['messages']);
			unset($USERPANEL->MODULES['stats']);
		}
	}

	if( file_exists(USERPANEL_MODULES_DIR.$module.'/functions.php')
	    && isset($USERPANEL->MODULES[$module]) )
        {
    		include(USERPANEL_MODULES_DIR.$module.'/functions.php');
		
		$function = isset($_GET['f']) && $_GET['f']!='' ? $_GET['f'] : 'main';
		if (function_exists('module_'.$function)) 
		{
		    $to_execute = 'module_'.$function;
		    $to_execute();
		} else {
    		    $layout['error'] = trans('Function <b>$0</b> in module <b>$1</b> not found!', $function, $module);
    		    $SMARTY->display('error.html');
		}
        }
        // if no module selected, redirect on module with lowest prio
	elseif ($module=='')
        {
		$redirectmodule = 'nomodulesfound';
		$redirectprio = 999;
		foreach ($USERPANEL->MODULES as $menupos)
		    if ($redirectprio > $menupos['prio']) 
		    {
			$redirectmodule = $menupos['module'];
			$redirectprio = $menupos['prio'];
		    }
		if ($redirectmodule == 'nomodulesfound') 
		{
    		    $layout['error'] = trans('No modules found!');
    		    $SMARTY->display('error.html');
		} 
		else
		{
		    header('Location: ?m='.$redirectmodule);
		}
        }
        else
        {
    		$layout['error'] = trans('Module <b>$0</b> not found!', $module);
    		$SMARTY->display('error.html');
    	}

        if(!isset($_SESSION['lastmodule']) || $_SESSION['lastmodule'] != $module)
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
