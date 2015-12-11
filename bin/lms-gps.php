#!/usr/bin/php
<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2015 LMS Developers
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

ini_set('error_reporting', E_ALL&~E_NOTICE);

$parameters = array(
	'C:' => 'config-file:',
	'q' => 'quiet',
	'h' => 'help',
	'v' => 'version',
	'u' => 'update',
	'U' => 'update_netdevices',
);

foreach ($parameters as $key => $val) {
	$val = preg_replace('/:/', '', $val);
	$newkey = preg_replace('/:/', '', $key);
	$short_to_longs[$newkey] = $val;
}
$options = getopt(implode('', array_keys($parameters)), $parameters);
foreach ($short_to_longs as $short => $long)
	if (array_key_exists($short, $options)) {
		$options[$long] = $options[$short];
		unset($options[$short]);
	}

if (array_key_exists('version', $options)) {
	print <<<EOF
lms-gps.php
(C) 2001-2015 LMS Developers

EOF;
	exit(0);
}

if (array_key_exists('help', $options)) {
	print <<<EOF
lms-gps.php
(C) 2001-2015 LMS Developers

-C, --config-file=/etc/lms/lms.ini      alternate config file (default: /etc/lms/lms.ini);
-u, --update                    update nodes GPS coordinates using Google Maps API ;
-U, --update_netdevices         update netdevices GPS coordinates using Google Maps API;
-h, --help                      print this help and exit;
-v, --version                   print version info and exit;
-q, --quiet                     suppress any output, except errors;

EOF;
	exit(0);
}

$quiet = array_key_exists('quiet', $options);
if (!$quiet) {
	print <<<EOF
lms-gps.php
(C) 2001-2015 LMS Developers

EOF;
}

if (array_key_exists('config-file', $options))
	$CONFIG_FILE = $options['config-file'];
else
	$CONFIG_FILE = DIRECTORY_SEPARATOR . 'etc' . DIRECTORY_SEPARATOR . 'lms' . DIRECTORY_SEPARATOR . 'lms.ini';

if (!$quiet)
	echo "Using file ".$CONFIG_FILE." as config." . PHP_EOL;

$update_netdevices = array_key_exists('update_netdevices', $options);
$update = array_key_exists('update', $options);

if (!is_readable($CONFIG_FILE))
	die("Unable to read configuration file [".$CONFIG_FILE."]!" . PHP_EOL);

define('CONFIG_FILE', $CONFIG_FILE);

$CONFIG = (array) parse_ini_file($CONFIG_FILE, true);

// Check for configuration vars and set default values
$CONFIG['directories']['sys_dir'] = (!isset($CONFIG['directories']['sys_dir']) ? getcwd() : $CONFIG['directories']['sys_dir']);
$CONFIG['directories']['lib_dir'] = (!isset($CONFIG['directories']['lib_dir']) ? $CONFIG['directories']['sys_dir'] . DIRECTORY_SEPARATOR . 'lib' : $CONFIG['directories']['lib_dir']);

define('SYS_DIR', $CONFIG['directories']['sys_dir']);
define('LIB_DIR', $CONFIG['directories']['lib_dir']);

// Load autoloader
require_once(LIB_DIR . DIRECTORY_SEPARATOR . 'autoloader.php');

// Do some checks and load config defaults
require_once(LIB_DIR . DIRECTORY_SEPARATOR . 'config.php');

// Init database

$DB = null;

try {
	$DB = LMSDB::getInstance();
} catch (Exception $ex) {
	trigger_error($ex->getMessage(), E_USER_WARNING);
	// can't working without database
	die("Fatal error: cannot connect to database!" . PHP_EOL);
}

// Include required files (including sequence is important)

require_once(LIB_DIR . DIRECTORY_SEPARATOR . 'language.php');
include_once(LIB_DIR . DIRECTORY_SEPARATOR . 'definitions.php');
require_once(LIB_DIR . DIRECTORY_SEPARATOR . 'unstrip.php');
require_once(LIB_DIR . DIRECTORY_SEPARATOR . 'common.php');
require_once(LIB_DIR . DIRECTORY_SEPARATOR . 'SYSLOG.class.php');

if (ConfigHelper::checkConfig('phpui.logging') && class_exists('SYSLOG'))
	$SYSLOG = new SYSLOG($DB);
else
	$SYSLOG = null;

// Initialize Session, Auth and LMS classes

$AUTH = NULL;
$LMS = new LMS($DB, $AUTH, $SYSLOG);
$LMS->ui_lang = $_ui_language;
$LMS->lang = $_language;

/*
$_APIKEY = ConfigHelper::getConfig('google.apikey');
if (!$_APIKEY)
	die("Unable to read apikey from configuration file." . PHP_EOL);
*/

if ($update) {
        $loc = $DB->GetAll("SELECT id, location FROM vnodes WHERE longitude IS NULL AND latitude IS NULL AND location IS NOT NULL AND location_house IS NOT NULL AND location !='' AND location_house !=''");
        if ($loc) {
                foreach($loc as $row) {
                        $address = urlencode($row['location']." Poland");
                        $link = "http://maps.googleapis.com/maps/api/geocode/json?address=".$address."&sensor=false";
                        $page = json_decode(file_get_contents($link), true);
                        $latitude = str_replace(',', '.', $page["results"][0]["geometry"]["location"]["lat"]);
                        $longitude = str_replace(',', '.', $page["results"][0]["geometry"]["location"]["lng"]);
                        $status = $page["status"];
                        $accuracy = $page["results"][0]["geometry"]["location_type"];
                        if (($status == "OK") && ($accuracy == "ROOFTOP")) {
                                $DB->Execute("UPDATE nodes SET latitude = ?, longitude = ? WHERE id = ?", array($latitude, $longitude, $row['id']));
                                echo $row['id']." - OK - Accuracy: ".$accuracy." (lat.: ".$latitude." long.: ".$longitude.")" . PHP_EOL;
                        } else {
                                echo $row['id']." - ERROR - Accuracy: ".$accuracy." (lat.: ".$latitude." long.: ".$longitude.")" . PHP_EOL;
                        }
                        sleep(2);
                }
        }
}

if ($update_netdevices) {
        $loc = $DB->GetAll("SELECT id,location FROM netdevices WHERE longitude IS NULL AND latitude IS NULL AND location IS NOT NULL AND location_house IS NOT NULL AND location !='' AND location_house !=''");
        if ($loc) {
                foreach($loc as $row) {
                        $address = urlencode($row['location']." Poland");
                        $link = "http://maps.googleapis.com/maps/api/geocode/json?address=".$address."&sensor=false";
                        $page = json_decode(file_get_contents($link), true);
                        $latitude = str_replace(',', '.', $page["results"][0]["geometry"]["location"]["lat"]);
                        $longitude = str_replace(',', '.', $page["results"][0]["geometry"]["location"]["lng"]);
                        $status = $page["status"];
                        $accuracy = $page["results"][0]["geometry"]["location_type"];
                        if (($status == "OK") && ($accuracy == "ROOFTOP")) {
                                $DB->Execute("UPDATE netdevices SET latitude = ?, longitude = ? WHERE id = ?", array($latitude, $longitude, $row['id']));
                                echo $row['id']." - OK - Accuracy: ".$accuracy." (lat.: ".$latitude." long.: ".$longitude.")" . PHP_EOL;
                        } else {
                                echo $row['id']." - ERROR - Accuracy: ".$accuracy." (lat.: ".$latitude." long.: ".$longitude.")" . PHP_EOL;
                        }
                        sleep(2);
                }
        }
}

?>
