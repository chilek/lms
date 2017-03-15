#!/usr/bin/env php
<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2016 LMS Developers
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
	'd' => 'dist'
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
lms-ukerange.php
(C) 2001-2017 LMS Developers

EOF;
	exit(0);
}

if (array_key_exists('help', $options)) {
	print <<<EOF
lms-ukerange.php
(C) 2001-2017 LMS Developers

-C, --config-file=/etc/lms/lms.ini      alternate config file (default: /etc/lms/lms.ini);
-h, --help                      print this help and exit;
-v, --version                   print version info and exit;
-q, --quiet                     suppress any output, except errors

EOF;
	exit(0);
}

$quiet = array_key_exists('quiet', $options);
if (!$quiet) {
	print <<<EOF
lms-ukerange.php
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

/* ****************************************
   Good place for config value analysis
   ****************************************/


// Include required files (including sequence is important)

require_once(LIB_DIR . DIRECTORY_SEPARATOR . 'common.php');
require_once(LIB_DIR . DIRECTORY_SEPARATOR . 'language.php');
include_once(LIB_DIR . DIRECTORY_SEPARATOR . 'definitions.php');
require_once(LIB_DIR . DIRECTORY_SEPARATOR . 'unstrip.php');

$SYSLOG = SYSLOG::getInstance();

// Initialize Session, Auth and LMS classes

$AUTH = NULL;
$LMS = new LMS($DB, $AUTH, $SYSLOG);
$LMS->ui_lang = $_ui_language;
$LMS->lang = $_language;

/* ********************************************************************
   We should have all hard work here which is being done by our script!
   ********************************************************************/

class Point {
    public $lat = 0;
    public $lon = 0;
}

/*!
 * \brief Change meters to GPS(dd) distance.
 *
 * \param  int   $m      distance
 * \return float $degree GPS decimal degrees
 */
function getLatitudeDistDiff( $m ) {
    // distance in meters => degree
    $dist_tab = array(
        1854.277 => 1/60,
        100      => 1/1111.9662,
        30.87    => 1/3600,
        1        => 1/111132
    );

    $degree = 0;

    foreach ( $dist_tab as $dist=>$deg ) {
        $degree += intval($m / $dist) * $deg;
        $m -= intval($m / $dist) * $dist;
    }

    return $degree;
}

/*!
 * \brief Change meters to GPS(dd) distance.
 *
 * \param  int   $m      distance
 * \return float $degree GPS decimal degrees
 */
function getLongitudeDistDiff( $m, $latitude ) {
    // get latitude length in KM
    $parallel_len = 2 * M_PI * 6378 * cos(deg2rad($latitude));

    // distance in meters => degree
    $dist_tab = array(
        (string) ($parallel_len / 21.6)  => 1/60,
        (string) ($parallel_len / 1296)  => 1/3600,
        (string) ($parallel_len / 77760) => 1/216000
    );

    $degree = 0;

    foreach ( $dist_tab as $dist=>$deg ) {
        $degree += intval($m / $dist) * $deg;
        $m -= intval($m / $dist) * $dist;
    }

    return $degree;
}

function getGPSdistance( Point $p1, Point $p2 ) {
    // get distance between two points in kilometers
    $distance = sqrt( pow($p2->lat - $p1->lat, 2) + pow(cos($p1->lat * M_PI / 180) * ($p2->lon - $p1->lon), 2) ) * 40075.704 / 360;

    // change kilometers to meters
    $distance *= 1000;

    return $distance;
}

$top    = -1;
$right  = -1;
$bottom = PHP_INT_MAX;
$left   = PHP_INT_MAX;

$distance = 100;

$points = $DB->GetAll('SELECT latitude, longitude FROM netnodes ORDER BY longitude');

foreach ($points as $v) {
    if ( $v['latitude'] > $top ) {
        $top = $v['latitude'];
    } else if ( $v['latitude'] < $bottom ) {
        $bottom = $v['latitude'];
    }

    if ( $v['longitude'] < $left ) {
        $left = $v['longitude'];
    } else if ( $v['longitude'] > $right ) {
        $right = $v['longitude'];
    }
}

$p1 = new Point();
$p1->lat = $left;
$p1->lon = $top;

$p2 = new Point();
$p2->lat = $right;
$p2->lon = $bottom;

$distance = 100;




//var_dump($top, $right, $bottom, $left);


print_r(   );

echo PHP_EOL;

?>
