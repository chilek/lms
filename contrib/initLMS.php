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

/*
 * To jest przyk³adowy plik do inicjacji klasy LMS'a do wykorzystania we
 * w³asnych projektach PHP korzystaj±cych z LMS'a.
 */

// ¦cie¿ka do pliku konfiguracyjnego

$CONFIG_FILE = (is_readable('lms.ini')) ? 'lms.ini' : '/etc/lms/lms.ini';

// Funkcja parsuj±ca plik konfiguracyjny

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

// Odczytanie pliku konfiguracyjnego

foreach(lms_parse_ini_file($CONFIG_FILE, true) as $key => $val)
	$_CONFIG[$key] = $val;

// Domy¶lne warto¶ci zmiennych których nie da siê gdzie indziej zdefiniowaæ.

$_CONFIG['directories']['sys_dir'] = (! $_CONFIG['directories']['sys_dir'] ? getcwd() : $_CONFIG['directories']['sys_dir']);
$_CONFIG['directories']['backup_dir'] = (! $_CONFIG['directories']['backup_dir'] ? $_CONFIG['directories']['sys_dir'].'/backups' : $_CONFIG['directories']['backup_dir']);
$_CONFIG['directories']['lib_dir'] = (! $_CONFIG['directories']['lib_dir'] ? $_CONFIG['directories']['sys_dir'].'/lib' : $_CONFIG['directories']['lib_dir']);
$_CONFIG['directories']['modules_dir'] = (! $_CONFIG['directories']['modules_dir'] ? $_CONFIG['directories']['sys_dir'].'/modules' : $_CONFIG['directories']['modules_dir']);
$_CONFIG['directories']['config_templates_dir'] = (! $_CONFIG['directories']['config_templates_dir'] ? $_CONFIG['directories']['sys_dir'].'/config_templates' : $_CONFIG['directories']['config_templates_dir']);
$_CONFIG['directories']['smarty_dir'] = (! $_CONFIG['directories']['smarty_dir'] ? (is_readable('/usr/share/php/smarty/libs/Smarty.class.php') ? '/usr/share/php/smarty/libs' : $_CONFIG['directories']['lib_dir'].'/Smarty') : $_CONFIG['directories']['smarty_dir']);
$_CONFIG['directories']['smarty_compile_dir'] = (! $_CONFIG['directories']['smarty_compile_dir'] ? $_CONFIG['directories']['sys_dir'].'/templates_c' : $_CONFIG['directories']['smarty_compile_dir']);
$_CONFIG['directories']['smarty_templates_dir'] = (! $_CONFIG['directories']['smarty_templates_dir'] ? $_CONFIG['directories']['sys_dir'].'/templates' : $_CONFIG['directories']['smarty_templates_dir']);

// Do³adowanie reszty domy¶lnych warto¶ci

foreach(lms_parse_ini_file($_CONFIG['directories']['lib_dir'].'/config_defaults.ini', TRUE) as $section => $values)
	foreach($values as $key => $val)
		if(! isset($_CONFIG[$section][$key]))
			$_CONFIG[$section][$key] = $val;

// Przepisanie warto¶ci zmiennych parametrów bazy danych

$_DBTYPE = $_CONFIG['database']['type'];
$_DBHOST = $_CONFIG['database']['host'];
$_DBUSER = $_CONFIG['database']['user'];
$_DBPASS = $_CONFIG['database']['password'];
$_DBNAME = $_CONFIG['database']['database'];

// Wczytanie niezbêdnych libów.

require_once($_CONFIG['directories']['lib_dir'].'/common.php');
require_once($_CONFIG['directories']['lib_dir'].'/LMSDB.php');
require_once($_CONFIG['directories']['lib_dir'].'/LMS.class.php');
require_once($_CONFIG['directories']['lib_dir'].'/language.php');

// Zainicjowanie bazy danych.

$DB = DBInit($_DBTYPE, $_DBHOST, $_DBUSER, $_DBPASS, $_DBNAME);

// Nie u¿ywamy raczej sesji. ;)

$SESSION = NULL;

// Odczytanie konfiguracji LMS-UI z bazy danych

if($cfg = $DB->GetAll('SELECT section, var, value FROM uiconfig WHERE disabled=0'))
	foreach($cfg as $row)
		$_CONFIG[$row['section']][$row['var']] = $row['value'];

// Inicjacja obiektu LMS'a.

$LMS = new LMS($DB, $SESSION, $_CONFIG);

?>
