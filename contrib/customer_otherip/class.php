<?php

/*
 * LMS version 1.4-cvs
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

// REPLACE THIS WITH PATH TO YOURS CONFIG FILE

$CONFIG_FILE = (is_readable('lms.ini')) ? 'lms.ini' : '/etc/lms/lms.ini';

// PLEASE DO NOT MODIFY ANYTHING BELOW THIS LINE UNLESS YOU KNOW
// *EXACTLY* WHAT ARE YOU DOING!!!

// Parse configuration file

function lms_parse_ini_file($filename, $process_sections = false)
{
	$ini_array = array();
	$sec_name = "";
	$lines = file($filename);
	foreach($lines as $line)
	{
		$line = trim($line);

		if($line == "" || $line[0] == ";" || $line[0] == "#")
			continue;

		if( sscanf($line, "[%[^]]", &$sec_name)==1 )
			$sec_name = trim($sec_name);
		else
		{
			if ( sscanf($line, "%[^=] = '%[^']'", &$property, &$value) != 2 )
				if ( sscanf($line, "%[^=] = \"%[^\"]\"", &$property, &$value) != 2 )
					if( sscanf($line, "%[^=] = %[^;#]", &$property, &$value) != 2 )
						continue;
					else
						$value = trim($value, "\"'");

			$property = trim($property);
			$value = trim($value);

			if($process_sections)
				$ini_array[$sec_name][$property] = $value;
			else
				$ini_array[$property] = $value;
		}
	}
	return $ini_array;
}

foreach(lms_parse_ini_file($CONFIG_FILE, true) as $key=>$val) $_CONFIG[$key] = $val;

// Define directories and configuration vars

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

function chkconfig($value, $default = FALSE)
{
	if(eregi('^(1|y|on|yes|true|tak|t)$', $value))
		return TRUE;
	elseif(eregi('^(0|n|no|off|false|nie)$', $value))
		return FALSE;
	elseif(!isset($value) || $value == '')
		return $default;
	else
		trigger_error('B³êdna warto¶æ opcji "'.$value.'"');
}

$_SYSTEM_DIR = $_CONFIG['directories']['sys_dir'];
$_BACKUP_DIR = $_CONFIG['directories']['backup_dir'];
$_LIB_DIR = $_CONFIG['directories']['lib_dir'];
$_MODULES_DIR = $_CONFIG['directories']['modules_dir'];
$_SMARTY_DIR = $_CONFIG['directories']['smarty_dir'];
$_SMARTY_COMPILE_DIR = $_CONFIG['directories']['smarty_compile_dir'];
$_SMARTY_TEMPLATES_DIR = $_CONFIG['directories']['smarty_templates_dir'];
$_TIMEOUT = $_CONFIG['phpui']['timeout'];
$_FORCE_SSL = chkconfig($_CONFIG['phpui']['force_ssl']);
$_DBTYPE = $_CONFIG['database']['type'];
$_DBHOST = $_CONFIG['database']['host'];
$_DBUSER = $_CONFIG['database']['user'];
$_DBPASS = $_CONFIG['database']['password'];
$_DBNAME = $_CONFIG['database']['database'];

// Redirect to SSL

if($_FORCE_SSL && $_SERVER['HTTPS'] != 'on')
{
	header('Location: https://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);
	exit(0);
}

// include required files

require_once($_SMARTY_DIR.'/Smarty.class.php');
require_once($_LIB_DIR.'/LMSDB.php');
require_once($_LIB_DIR.'/common.php');
require_once($_LIB_DIR.'/LMS.class.php');

// Initialize LMSDB object

$DB = DBInit($_DBTYPE, $_DBHOST, $_DBUSER, $_DBPASS, $_DBNAME);

// Initialize database and template classes

$SESSION = NULL;

$LMS = new LMS($DB,$SESSION,$_CONFIG);

$SMARTY = new Smarty;

// test for proper version of Smarty

$SMARTY->template_dir = getcwd();
$SMARTY->compile_dir = $_SMARTY_COMPILE_DIR;

$layout['lmsv']='1.4-cvs';

$SMARTY->assign('menu',$menu);
$SMARTY->assign('layout',$layout);

?>
