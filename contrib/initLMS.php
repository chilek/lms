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
$_CONFIG['directories']['lib_dir'] = (! $_CONFIG['directories']['lib_dir'] ? $_CONFIG['directories']['sys_dir'].'/lib' : $_CONFIG['directories']['lib_dir']);

foreach(lms_parse_ini_file($_CONFIG['directories']['lib_dir'].'/config_defaults.ini', TRUE) as $section => $values)
	foreach($values as $key => $val)
		if(! isset($_CONFIG[$section][$key]))
			$_CONFIG[$section][$key] = $val;

$_DBTYPE = $_CONFIG['database']['type'];
$_DBHOST = $_CONFIG['database']['host'];
$_DBUSER = $_CONFIG['database']['user'];
$_DBPASS = $_CONFIG['database']['password'];
$_DBNAME = $_CONFIG['database']['database'];

// Init database 

require_once($_CONFIG['directories']['lib_dir'].'/LMSDB.php');

$DB = DBInit($_DBTYPE, $_DBHOST, $_DBUSER, $_DBPASS, $_DBNAME);

// Read configuration of LMS-UI from database

if($cfg = $DB->GetAll('SELECT section, var, value FROM uiconfig WHERE disabled=0'))
	foreach($cfg as $row)
		$_CONFIG[$row['section']][$row['var']] = $row['value'];

// Include required files (including sequence is important)

require_once($_CONFIG['directories']['lib_dir'].'/language.php');
require_once($_CONFIG['directories']['lib_dir'].'/common.php');
require_once($_CONFIG['directories']['lib_dir'].'/LMS.class.php');

$_SESSION = NULL;

$LMS = new LMS($DB, $_SESSION, $_CONFIG);
$LMS->lang = $_language;

?>
