#!/usr/bin/env php
<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2017 LMS Developers
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
	'U' => 'update-netdevices',
	'N' => 'update-netnodes',
	's:' => 'sources:',
	'd' => 'debug',
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
(C) 2001-2017 LMS Developers

EOF;
	exit(0);
}

if (array_key_exists('help', $options)) {
	print <<<EOF
lms-gps.php
(C) 2001-2017 LMS Developers

-C, --config-file=/etc/lms/lms.ini      alternate config file (default: /etc/lms/lms.ini);
-u, --update                    update nodes GPS coordinates
-U, --update-netdevices         update netdevices GPS coordinates
-N, --update-netnodes           update netnodes GPS coordinates
-s, --sources=<google,siis>     use Google Maps API and/or SIIS building location database
                                to determine GPS coordinates (in specified order)
-d, --debug                     only try to determine GPS coordinates without updating database
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
(C) 2001-2017 LMS Developers

EOF;
}

if (array_key_exists('config-file', $options))
	$CONFIG_FILE = $options['config-file'];
else
	$CONFIG_FILE = DIRECTORY_SEPARATOR . 'etc' . DIRECTORY_SEPARATOR . 'lms' . DIRECTORY_SEPARATOR . 'lms.ini';

if (!$quiet)
	echo "Using file ".$CONFIG_FILE." as config." . PHP_EOL;

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
$composer_autoload_path = SYS_DIR . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
if (file_exists($composer_autoload_path)) {
    require_once $composer_autoload_path;
} else {
    die("Composer autoload not found. Run 'composer install' command from LMS directory and try again. More informations at https://getcomposer.org/");
}

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

require_once(LIB_DIR . DIRECTORY_SEPARATOR . 'common.php');
require_once(LIB_DIR . DIRECTORY_SEPARATOR . 'language.php');
include_once(LIB_DIR . DIRECTORY_SEPARATOR . 'definitions.php');

$SYSLOG = SYSLOG::getInstance();

/*
$_APIKEY = ConfigHelper::getConfig('google.apikey');
if (!$_APIKEY)
	die("Unable to read apikey from configuration file." . PHP_EOL);
*/

$debug = isset($options['debug']);

$types = array();
if (isset($options['update-netdevices']))
	$types['Network devices:'] = 'netdevices';
if (isset($options['update-netnodes']))
	$types['Network nodes:'] = 'netnodes';
if (isset($options['update']))
	$types['Nodes:'] = 'nodes';

$sources = array();
if (isset($options['sources'])) {
	$srcs = explode(',', $options['sources']);
	foreach ($srcs as $source)
		if (in_array($source, array('google', 'siis')))
			$sources[$source] = true;
}
if (empty($sources))
	$sources['google'] = true;

$google_api_key = ConfigHelper::getConfig('phpui.googlemaps_api_key', '', true);

$lc = new LocationCache('full');

foreach ($types as $label => $type) {
	if (!$quiet)
		echo $label . PHP_EOL;
	$locations = $DB->GetAll("SELECT t.id, va.location, va.city_id, va.street_id, va.house, ls.name AS state_name,
			ld.name AS distict_name, lb.name AS borough_name FROM " . $type . " t
		LEFT JOIN vaddresses va ON va.id = t.address_id
		LEFT JOIN location_cities lc ON lc.id = va.city_id
		LEFT JOIN location_boroughs lb ON lb.id = lc.boroughid
		LEFT JOIN location_districts ld ON ld.id = lb.districtid
		LEFT JOIN location_states ls ON ls.id = ld.stateid
		WHERE longitude IS NULL AND latitude IS NULL AND location IS NOT NULL
			AND location_house IS NOT NULL AND location <> '' AND location_house <> ''");
	if (!empty($locations))
		foreach ($locations as $row)
			foreach ($sources as $source => $true)
				if ($source == 'google') {
					$res = geocode((empty($row['state_name']) ? '' : $row['state_name'] . ', ' . $row['district_name'] . ', ' . $row['borough_name'])
						. $row['location'] . " Poland");
					if (($res['status'] == "OK") && ($res['accuracy'] == "ROOFTOP")) {
						if (!$debug)
							$DB->Execute("UPDATE " . $type . " SET latitude = ?, longitude = ? WHERE id = ?", array($res['latitude'], $res['longitude'], $row['id']));
						if (!$quiet)
							echo $row['id']." - OK - Accuracy: ".$res['accuracy']." (lat.: ".$res['latitude']." long.: ".$res['longitude'].")" . PHP_EOL;
						sleep(2);
						break;
					} else {
						if (!$quiet)
							echo $row['id']." - ERROR - Accuracy: ".$res['accuracy']." (lat.: ".$res['latitude']." long.: ".$res['longitude'].")" . PHP_EOL;
					}
					if (empty($google_api_key))
						sleep(2);
					else
						usleep(50000);
				} elseif ($source == 'siis' && !empty($row['state_name'])) {
					if (($building = $lc->buildingExists($row['city_id'], empty($row['street_id']) ? 'null' : $row['street_id'], $row['house']))
						&& !empty($building['longitude']) && !empty($building['latitude'])) {
						if (!$debug)
							$DB->Execute("UPDATE " . $type . " SET latitude = ?, longitude = ? WHERE id = ?",
								array($building['latitude'], $building['longitude'], $row['id']));
						if (!$quiet)
							echo $row['id']." - OK - Building: " . $row['location'] . " (lat.: " . $building['latitude']
								. " long.: " . $building['longitude'] . ")" . PHP_EOL;
						break;
					} else {
						if (!$quiet)
							echo $row['id']." - ERROR - Building: " . $row['location'] . PHP_EOL;
					}
				}
}

?>
