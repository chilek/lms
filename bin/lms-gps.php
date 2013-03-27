#!/usr/bin/php
<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2013 LMS Developers
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
 *  $Id: lms-gps.php $
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
foreach($short_to_longs as $short => $long)
        if (array_key_exists($short, $options))
        {
                $options[$long] = $options[$short];
                unset($options[$short]);
        }

if (array_key_exists('version', $options))
{
        print <<<EOF
lms-gps.php
(C) 2001-2013 LMS Developers

EOF;
        exit(0);
}

if (array_key_exists('help', $options))
{
        print <<<EOF
lms-gps.php
(C) 2001-2013 LMS Developers

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
if (!$quiet)
{
        print <<<EOF
lms-gps.php
(C) 2001-2013 LMS Developers

EOF;
}

if (array_key_exists('config-file', $options))
        $CONFIG_FILE = $options['config-file'];
else
        $CONFIG_FILE = '/etc/lms/lms.ini';

if (!$quiet) {
        echo "Using file ".$CONFIG_FILE." as config.\n";
}
$update_netdevices = array_key_exists('update_netdevices', $options);
$update = array_key_exists('update', $options);

if (!is_readable($CONFIG_FILE))
        die("Unable to read configuration file [".$CONFIG_FILE."]!\n");

$CONFIG = (array) parse_ini_file($CONFIG_FILE, true);

// Check for configuration vars and set default values
$CONFIG['directories']['sys_dir'] = (!isset($CONFIG['directories']['sys_dir']) ? getcwd() : $CONFIG['directories']['sys_dir']);
$CONFIG['directories']['lib_dir'] = (!isset($CONFIG['directories']['lib_dir']) ? $CONFIG['directories']['sys_dir'].'/lib' : $CONFIG['directories']['lib_dir']);

define('SYS_DIR', $CONFIG['directories']['sys_dir']);
define('LIB_DIR', $CONFIG['directories']['lib_dir']);
// Do some checks and load config defaults

require_once(LIB_DIR.'/config.php');

// Init database

$_DBTYPE = $CONFIG['database']['type'];
$_DBHOST = $CONFIG['database']['host'];
$_DBUSER = $CONFIG['database']['user'];
$_DBPASS = $CONFIG['database']['password'];
$_DBNAME = $CONFIG['database']['database'];

require(LIB_DIR.'/LMSDB.php');

$DB = DBInit($_DBTYPE, $_DBHOST, $_DBUSER, $_DBPASS, $_DBNAME);

if(!$DB)
{
        // can't working without database
        die("Fatal error: cannot connect to database!\n");
}

// Read configuration from database

if($cfg = $DB->GetAll('SELECT section, var, value FROM uiconfig WHERE disabled=0'))
        foreach($cfg as $row)
                $CONFIG[$row['section']][$row['var']] = $row['value'];

// Include required files (including sequence is important)

require_once(LIB_DIR.'/language.php');
include_once(LIB_DIR.'/definitions.php');
require_once(LIB_DIR.'/unstrip.php');
require_once(LIB_DIR.'/common.php');
require_once(LIB_DIR.'/LMS.class.php');

// Initialize Session, Auth and LMS classes

$AUTH = NULL;
$LMS = new LMS($DB, $AUTH, $CONFIG);
$LMS->ui_lang = $_ui_language;
$LMS->lang = $_language;

$_APIKEY = $CONFIG['google']['apikey'];
if (!$_APIKEY) {
        echo "Unable to read apikey from configuration file.\n";
}

if ($update) {
        $loc = $DB->GetAll("SELECT id, location FROM nodes WHERE longitude IS NULL AND latitude IS NULL AND location IS NOT NULL AND location_house IS NOT NULL AND location !='' AND location_house !=''");
        if ($loc) {
                foreach($loc as $row) {
                        $address = urlencode($row['location']." Poland");
                        $link = "http://maps.googleapis.com/maps/api/geocode/json?address=".$address."&sensor=false";
                        $page = json_decode(file_get_contents($link), true);
                        $latitude = $page["results"][0]["geometry"]["location"]["lat"];
                        $longitude = $page["results"][0]["geometry"]["location"]["lng"];
                        $status = $page["status"];
                        $accuracy = $page["results"][0]["geometry"]["location_type"];
                        if (($status == "OK") && ($accuracy == "ROOFTOP")) {
                                $DB->Execute("UPDATE nodes SET latitude = ?, longitude = ? WHERE id = ?", array($latitude, $longitude, $row['id']));
                                echo $row['id']." - OK\n";
                        } else {
                                echo $row['id']." - ERROR\n";
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
                        $latitude = $page["results"][0]["geometry"]["location"]["lat"];
                        $longitude = $page["results"][0]["geometry"]["location"]["lng"];
                        $status = $page["status"];
                        $accuracy = $page["results"][0]["geometry"]["location_type"];
                        if (($status == "OK") && ($accuracy == "ROOFTOP")) {
                                $DB->Execute("UPDATE netdevices SET latitude = ?, longitude = ? WHERE id = ?", array($latitude, $longitude, $row['id']));
                                echo $row['id']." - OK\n";
                        } else {
                                echo $row['id']." - ERROR\n";
                        }
                        sleep(2);
                }
        }
}

?> 
