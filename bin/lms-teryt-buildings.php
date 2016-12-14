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
    'q'  => 'quiet',
    'h'  => 'help',
    'v'  => 'version',
    'f:' => 'file:'
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
lms-stub.php
(C) 2001-2016 LMS Developers

EOF;
    exit(0);
}

if (array_key_exists('help', $options)) {
    print <<<EOF
lms-stub.php
(C) 2001-2016 LMS Developers

-C, --config-file=/etc/lms/lms.ini alternate config file (default: /etc/lms/lms.ini);
-h, --help                         print this help and exit;
-v, --version                      print version info and exit;
-q, --quiet                        suppress any output, except errors
-f, --file                         csv file

EOF;
    exit(0);
}

$quiet = array_key_exists('quiet', $options);
if (!$quiet) {
    print <<<EOF
lms-stub.php
(C) 2001-2016 LMS Developers

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

/*!
 * \brief Change text to asociative array.
 *
 * \param string $row single row to parse
 * \return array associative array with paremeters
 */
function parseRow($row) {
    $pattern = '(?<symnad>.*);(?<simc>.*);(?<ulic>.*);(?<building_num>.*);(?<flats>.*);(?<latitude>.*);(?<longitude>.*)';

    $row = str_replace("\r", '', $row);
    preg_match('/^'.$pattern.'$/', $row, $matches);

    foreach ($matches as $k=>$v) {
        if (is_numeric($k))
            unset($matches[$k]);
    }

    return $matches;
}

if (empty($options['file'])) {
    echo 'File isn\'t set. Please use -f --file to set file to read.', PHP_EOL;
    exit;
}

$fh = fopen($options['file'], "r");

if (!$fh) {
    echo 'File \'', $options['file'], '\' not exists or name isn\'t correct.', PHP_EOL;
    exit;
}

ini_set('memory_limit', '512M');
$stderr = fopen('php://stderr', 'w');
$stdout = fopen('php://stdout', 'w');

$steps = ceil( filesize($options['file']) / 4096 );
$i = 1;

$to_update = array();
$to_insert = array();

if ($fh) {
    $previous_line = '';

    // ---------
    // CREATE LOCATION CACHE MANAGER

    $location_cache = new LocationCache('full');

    // ---------

    echo 'Parsing file', PHP_EOL;

    while (!feof($fh)) {
        $lines = explode("\n", fread($fh, 4096));
        $lines = str_replace("'", '', $lines);

        // ---------
        // TRY TO JOIN PREVIOUS LINE

        if (substr_count($lines[0], ';') == 6 && substr_count($previous_line, ';') == 6) {
            array_unshift($lines, $previous_line);
        } else {
            $lines[0] = $previous_line . $lines[0];
        }

        end($lines);
        $k = key($lines);
        $previous_line = $lines[ $k ];
        unset($lines[ $k ]);

        // ---------


        // ---------
        // INSERT LOADED DATA TO DATABASE

        foreach ($lines as $k=>$l) {
            $v = parseRow( $l );

            if ( !$v ) {
                fwrite($stderr, 'error: can\'t parse row ' . $l . PHP_EOL);
                continue;
            }

            if ( !preg_match('/^[0-9a-zA-Z \/łŁ]*$/', $v['building_num']) ) {
                fwrite($stderr, 'warning: house number contains incorrect characters in row ' . $l . PHP_EOL);
                continue;
            }

            if ( strpos($v['symnad'], ' ') !== false ) {
                fwrite($stderr, 'error: symnad contains whitespace characters in row ' . $l . PHP_EOL);
                continue;
            }

            $symnad = ltrim($v['symnad'], '0');
            $simc   = ltrim($v['simc']  , '0');
            $city   = $location_cache->getCityByIdent( $simc );

            if ( !$city ) {
                fwrite($stderr, 'warning: teryt city id ' . $v['simc'] . ' was not found in database'. PHP_EOL);
                continue;
            }

            $street = $location_cache->getStreetByIdent( $city['id'], ltrim($v['ulic'],'0') );

            if ( $v['symnad'] == $v['simc'] ) {

                // if $building is not empty then try update else insert as new building
                $building = $location_cache->buildingExists( $city['id'], $street['id'], $v['building_num'] );

                if ( $building ) {
                    $fields_to_update = array();

                    if ( $building['flats'] != $v['flats'] ) {
                        $fields_to_update[] = 'flats = ' . ($v['flats'] ? $v['flats'] : 'null');
                    }

                    if ( $building['latitude'] != $v['latitude'] ) {
                        $fields_to_update[] = 'latitude = ' . ($v['latitude'] ? $v['latitude'] : 'null');
                    }

                    if ( $building['longitude'] != $v['longitude'] ) {
                        $fields_to_update[] = 'longitude = ' . ($v['longitude'] ? $v['longitude'] : 'null');
                    }

                    if ($fields_to_update) {
                        $DB->Execute('UPDATE location_buildings SET updated = 1, ' . implode(',', $fields_to_update) . ' WHERE id = ' . $building['id'] );
                    } else {
                        $to_update[] = $building['id'];
                    }
                } else {
                    $data = array();

                    $data[] = $city['id'];
                    $data[] = $street['id']      ? $street['id']              : 'null';
                    $data[] = $v['building_num'] ? "'".$v['building_num']."'" : 'null';
                    $data[] = ( strlen($v['flats']) != 0 ) ? $v['flats']      : 'null';
                    $data[] = $v['latitude']     ? $v['latitude']             : 'null';
                    $data[] = $v['longitude']    ? $v['longitude']            : 'null';
                    $data[] = 1;

                    $to_insert[] = '(' . implode(',', $data) . ')';
                }
            } else {
                if ( $location_cache->getCityById( $city['cityid'] ) ) {
                    $symnad_ident = $location_cache->getCityById( $city['cityid'] )['ident'];
                } else {
                    continue;
                }

                if ( $symnad == $symnad_ident ) {
                    $data = array();
                    $data[] = $city['id'];
                    $data[] = $street['id']      ? $street['id']              : 'null';
                    $data[] = $v['building_num'] ? "'".$v['building_num']."'" : 'null';
                    $data[] = $v['flats']        ? $v['flats']                : 'null';
                    $data[] = $v['latitude']     ? $v['latitude']             : 'null';
                    $data[] = $v['longitude']    ? $v['longitude']            : 'null';
                    $data[] = 1;

                    $to_insert[] = '(' . implode(',', $data) . ')';
                } else {
                    fwrite($stderr, 'warning: wrong symnad or simc in line ' . $l . PHP_EOL);
                }
            }
        }

        if ($to_insert) {
            $DB->Execute( 'INSERT INTO location_buildings (city_id, street_id, building_num, flats, latitude, longitude, updated) VALUES ' . implode(',', $to_insert) . ';' );
            $to_insert = array();
        }

        if ($to_update) {
            $DB->Execute( 'UPDATE location_buildings SET updated = 1 WHERE id in (' . implode(',', $to_update) . ')' );
            $to_update = array();
        }

        // ---------


        // ---------
        // PROGRES MESSAGES

        echo $i, ' / ', $steps, PHP_EOL;
        ++$i;

        // ---------
    }

    if ($to_insert) {
        $DB->Execute( 'INSERT INTO location_buildings (city_id, street_id, building_num, flats, latitude, longitude, updated) VALUES ' . implode(',', $to_insert) . ';' );
    }

    if ($to_update) {
         $DB->Execute( 'UPDATE location_buildings SET updated = 1 WHERE id in (' . implode(',', $to_update) . ')' );
    }

    echo 'done', PHP_EOL;
    echo 'Remove old buildings', PHP_EOL;

    $DB->Execute('DELETE FROM location_buildings WHERE updated = 0;');
    $DB->Execute('UPDATE location_buildings SET updated = 0;');

    echo 'done', PHP_EOL;

    fclose($fh);
    fclose($stderr);
    fclose($stdout);
}

?>
