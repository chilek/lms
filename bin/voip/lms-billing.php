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
	'a:' => 'action:',
	'd'  => 'debug',
	'e:' => 'callee:',
	'f:' => 'file:',
	'l:' => 'calltime:',
	'o:' => 'totaltime:',
	'r:' => 'caller:',
	's:' => 'startcall:',
	't:' => 'type:',
	'u:' => 'status:',
	'U:' => 'uniqueid:',
	'c:' => 'cache-dir:',
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
lms-billing.php
(C) 2001-2016 LMS Developers

EOF;
	exit(0);
}

if (array_key_exists('help', $options)) {
	print <<<EOF
lms-billing.php
(C) 2001-2016 LMS Developers

-C, --config-file=/etc/lms/lms.ini      alternate config file (default: /etc/lms/lms.ini);
-h, --help                      print this help and exit;
-v, --version                   print version info and exit;
-q, --quiet                     suppress any output, except errors
-c, --cache-dir=<cache-directory>       explicitly sets cache directory

EOF;
	exit(0);
}

$quiet = array_key_exists('quiet', $options);
if (!$quiet) {
	print <<<EOF
lms-billing.php
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
if (file_exists($composer_autoload_path))
	require_once $composer_autoload_path;
else
	die("Composer autoload not found. Run 'composer install' command from LMS directory and try again. More informations at https://getcomposer.org/" . PHP_EOL);

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

require_once(LIB_DIR . DIRECTORY_SEPARATOR . 'language.php');
include_once(LIB_DIR . DIRECTORY_SEPARATOR . 'definitions.php');
require_once(LIB_DIR . DIRECTORY_SEPARATOR . 'common.php');

setlocale(LC_NUMERIC, 'en_US');
include 'functions.inc.php';

$options['action'] = (isset($options['action'])) ? $options['action'] : '';

define('VOIP_CACHE_DIR', isset($options['cache-dir']) ? $options['cache-dir']
    : SYS_DIR . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'voip' . DIRECTORY_SEPARATOR . 'cache');

$estimate  = new Estimate(SqlProvider::getInstance());
$db_buffor = new VoipDbBuffor(SqlProvider::getInstance());

switch (strtolower($options['action'])) {

    case 'estimate':
        if ($options['caller'])
            die("Caller phone number isn't set.");

        if ($options['callee'])
            die("Callee phone number isn't set.");

        try {
            $call_time = $estimate->getMaxCallTime($options['caller'], $options['callee']);

            // if debug mode is set print value else change to miliseconds before print
            echo (array_key_exists('debug', $options)) ? $call_time.PHP_EOL : $call_time*1000;
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    break;

    case 'account':
        if (isset($options['file'])) {
            $fh    = (isset($options['file'])) ? fopen($options['file'], 'r') : fopen('php://stdin', 'r');
            $error = array();
            $i     = 0;

            while($f_line = fgets($fh)) {
                ++$i;

                if (($tmp = $db_buffor->appendCdr($f_line)) != 1) {
                    $error[] = array('line'=>$i, 'desc'=>$tmp);
                }
            }

            if ($error) {
                echo 'Failed loaded CDR records: ' . count($error['errors']) . PHP_EOL;
                // do somethink with errors here
            }

            fclose($fh);
        } else {
            try {
                $cdr['caller']             = $options['caller'];
                $cdr['callee']             = $options['callee'];
                $cdr['call_start']         = $options['startcall'];
                $cdr['time_start_to_end']  = $options['totaltime'];
                $cdr['time_answer_to_end'] = $options['calltime'];
                $cdr['call_status']        = $options['status'];
                $cdr['call_type']          = $options['type'];
                $cdr['uniqueid']           = $options['uniqueid'];

                $db_buffor->appendCdr($cdr);
            }
            catch (Exception $e) {
                echo $e->getMessage();
            }
        }

        $db_buffor->insert();
    break;

    default:
        echo 'Unknow operation.' . PHP_EOL;
}

?>
