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
$DB_STRUCTURE_FILE = 'doc/lms.sqlite';

// PLEASE DO NOT MODIFY ANYTHING BELOW THIS LINE UNLESS YOU KNOW
// *EXACTLY* WHAT ARE YOU DOING!!!
// *******************************************************************

if(!file_exists($CONFIG_FILE))
	die("Set path to 'lms.ini' file!\n");

if(!file_exists($DB_STRUCTURE_FILE))
	die("Set path to 'lms.sqlite' file!\n");

if(!function_exists('sqlite_open'))
	die("Your PHP does not supports SQLite!\n");

if(!function_exists('file_get_contents'))
	die("Required at least PHP 4.3.0!\n");

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

$_DBTYPE = $CONFIG['database']['type'];
$_DBNAME = $CONFIG['database']['database'];

$dblink = sqlite_open($_DBNAME);

if(!$dblink)
	die("Unable to open/create database!\n");

$file = file_get_contents($DB_STRUCTURE_FILE);

$sql = explode(';',$file);

if($sql)
	foreach($sql as $query)
	{
		$query = trim($query);
		if(!$query) continue;
		$res = sqlite_exec($dblink, $query);
	}

sqlite_close($dblink);

echo "Now, change owner of '$_DBNAME' to apache user/group.\n";

?>
