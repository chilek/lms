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

$CONFIG_FILE = '/etc/lms/lms.ini';

// PLEASE DO NOT MODIFY ANYTHING BELOW THIS LINE UNLESS YOU KNOW
// *EXACTLY* WHAT ARE YOU DOING!!!
// *******************************************************************

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

// Init database 

require_once($_LIB_DIR.'/LMSDB.php');

$DB = DBInit($_DBTYPE, $_DBHOST, $_DBUSER, $_DBPASS, $_DBNAME);

// Enable data encoding conversion if needed

require_once($_LIB_DIR.'/dbencoding.php');

// Initialize templates engine

require_once($_SMARTY_DIR.'/Smarty.class.php');

$SMARTY = new Smarty;
$SESSION = NULL;

// Include required files (including sequence is important)

require_once($_LIB_DIR.'/language.php');
require_once($_LIB_DIR.'/common.php');
require_once($_LIB_DIR.'/LMS.class.php');

// Initialize LMS class

$LMS = new LMS($DB, $SESSION, $_CONFIG);
$LMS->CONFIG = $_CONFIG;
$LMS->lang = $_language;

// set some template and layout variables

$SMARTY->assign_by_ref('_LANG', $_LANG);
$SMARTY->assign_by_ref('LANGDEFS', $LANGDEFS);
$SMARTY->assign_by_ref('_language', $LMS->lang);
$SMARTY->template_dir = getcwd();
$SMARTY->compile_dir = $_SMARTY_COMPILE_DIR;

require_once($_LIB_DIR.'/smarty_addons.php');
include('lang.php');

$SMARTY->assign_by_ref('layout', $layout);

if (isset($_SERVER[HTTP_X_FORWARDED_FOR])) {
    $forwarded_ip = explode(",",$_SERVER[HTTP_X_FORWARDED_FOR]);
    $nodeid = $LMS->GetNodeIDByIP($forwarded_ip['0']);    
} else {
    $nodeid = $LMS->GetNodeIDByIP(str_replace("::ffff:","",$_SERVER[REMOTE_ADDR]));    
}
$userid = $LMS->GetNodeOwner($nodeid);    
$nodeinfo = $LMS->GetNode($nodeid);    

if (isset($_GET['readed'])) {
    $LMS->DB->Execute('UPDATE nodes SET warning = 0 WHERE id = ?',array($nodeid));
    header('Location: '.$_GET['oldurl']);
} else {
    $userinfo = $LMS->GetUser($userid);
    $layout['oldurl']=$_GET['oldurl'];
    $SMARTY->assign("userinfo",$userinfo);
    $SMARTY->assign("nodeinfo",$nodeinfo);
    $SMARTY->assign("layout",$layout);
    $SMARTY->display("message.html");
}

?>
