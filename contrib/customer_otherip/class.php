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
$CONFIG['directories']['sys_dir'] = (! $CONFIG['directories']['sys_dir'] ? getcwd() : $CONFIG['directories']['sys_dir']);
$CONFIG['directories']['backup_dir'] = (! $CONFIG['directories']['backup_dir'] ? $CONFIG['directories']['sys_dir'].'/backups' : $CONFIG['directories']['backup_dir']);
$CONFIG['directories']['lib_dir'] = (! $CONFIG['directories']['lib_dir'] ? $CONFIG['directories']['sys_dir'].'/lib' : $CONFIG['directories']['lib_dir']);
$CONFIG['directories']['modules_dir'] = (! $CONFIG['directories']['modules_dir'] ? $CONFIG['directories']['sys_dir'].'/modules' : $CONFIG['directories']['modules_dir']);
$CONFIG['directories']['config_templates_dir'] = (! $CONFIG['directories']['config_templates_dir'] ? $CONFIG['directories']['sys_dir'].'/config_templates' : $CONFIG['directories']['config_templates_dir']);
$CONFIG['directories']['smarty_dir'] = (! $CONFIG['directories']['smarty_dir'] ? (is_readable('/usr/share/php/smarty/libs/Smarty.class.php') ? '/usr/share/php/smarty/libs' : $CONFIG['directories']['lib_dir'].'/Smarty') : $CONFIG['directories']['smarty_dir']);
$CONFIG['directories']['smarty_compile_dir'] = (! $CONFIG['directories']['smarty_compile_dir'] ? $CONFIG['directories']['sys_dir'].'/templates_c' : $CONFIG['directories']['smarty_compile_dir']);
$CONFIG['directories']['smarty_templates_dir'] = (! $CONFIG['directories']['smarty_templates_dir'] ? $CONFIG['directories']['sys_dir'].'/templates' : $CONFIG['directories']['smarty_templates_dir']);

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

// Init database 

require_once($_LIB_DIR.'/LMSDB.php');

$DB = DBInit($_DBTYPE, $_DBHOST, $_DBUSER, $_DBPASS, $_DBNAME);

// Enable data encoding conversion if needed 

require_once($_LIB_DIR.'/dbencoding.php');

// Read configuration of LMS-UI from database

if($cfg = $DB->GetAll('SELECT section, var, value FROM uiconfig WHERE disabled=0'))
	foreach($cfg as $row)
		$CONFIG[$row['section']][$row['var']] = $row['value'];

// Initialize templates engine

require_once($_SMARTY_DIR.'/Smarty.class.php');

$SMARTY = new Smarty;

// Include required files (including sequence is important)

require_once($_LIB_DIR.'/language.php');
require_once($_LIB_DIR.'/common.php');
require_once($_LIB_DIR.'/LMS.class.php');

// Initialize LMS class

$SESSION = NULL;

$LMS = new LMS($DB, $SESSION, $CONFIG);
$LMS->lang = $_language;

// set some template and layout variables

$SMARTY->assign_by_ref('_LANG', $_LANG);
$SMARTY->assign_by_ref('LANGDEFS', $LANGDEFS);
$SMARTY->assign_by_ref('_language', $LMS->lang);
$SMARTY->template_dir = getcwd();
$SMARTY->compile_dir = $_SMARTY_COMPILE_DIR;

require_once($_LIB_DIR.'/smarty_addons.php');

@include('locale/'.$LMS->lang.'/strings.php');

$layout['lmsv'] = '1.7-cvs';

$SMARTY->assign_by_ref('layout', $layout);
$SMARTY->assign_by_ref('_config', $CONFIG);

?>
