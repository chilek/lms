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
	'a:' => 'action:',
	'd' => 'debug',
	'e:' => 'callee:',
	'f:' => 'file:',
	'l:' => 'calltime:',
	'o:' => 'totaltime:',
	'r:' => 'caller:',
	's:' => 'startcall:',
	't:' => 'type:',
	'u:' => 'status:'
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
define('VOIP_CACHE_DIR', SYS_DIR . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'billing' . DIRECTORY_SEPARATOR . 'voip-cache');

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

include 'functions.php';
$tariffs = array();

$options['action'] = (isset($options['action'])) ? $options['action'] : '';

// valid parameters
$param_err = validParamters($options);
if ($param_err !== TRUE)
	die($param_err);

switch (strtolower($options['action'])) {
	case 'estimate':
		if (empty($options['caller']))
			die('Caller phone number is not set. Please use --caller [phone_number].' . PHP_EOL);

		if (empty($options['callee']))
			die('Callee phone number is not set. Please use --callee [phone_number].' . PHP_EOL);

		// get maximum call time in seconds
		$call_time = getMaxCallTime($options['caller'], $options['callee']);

		// if debug mode is set print value else change to miliseconds before print
		echo (array_key_exists('debug', $options)) ? $call_time.PHP_EOL : $call_time*1000;
	break;

	case 'account':
		if (isset($options['caller'])) {
			if (empty($options['caller']))
				die('Caller phone number is not set. Please use --caller [phone_number].' . PHP_EOL);

			if (empty($options['callee']))
				die('Callee phone number is not set. Please use --callee [phone_number].' . PHP_EOL);

			if (empty($options['startcall']))
				die('Call start is not set. Please use --startcall [unix_timestamp].' . PHP_EOL);

			if (empty($options['totaltime']))
				die('Time start to end of call is not set. Please use --totaltime [number_of_seconds].' . PHP_EOL);

			if (empty($options['calltime']))
				die('Time answer to end of call is not set. Please use --calltime [number_of_seconds].' . PHP_EOL);

			if (empty($options['type']))
				die('Call type is not set. Please use --type (incoming|outgoing).' . PHP_EOL);

			if (empty($options['status']))
				die('Call status is not set. Please use --status (busy|answered|no answer).' . PHP_EOL);

			// get customer and include tariff
			$customer = getCustomerByPhone($options['caller']);
			include_tariff($customer['tariffid']);

			// get first letter of type
			$call_type = strtolower($options['type'][0]);

			// no payments for incoming call else calculate cost for call
			$call_cost = getCost($options['caller'], $options['callee'], $customer['tariffid']);
			$price = ($call_type == 'o') ? round(ceil($options['calltime']/$call_cost['unitSize']) * $call_cost['costPerUnit'], 5) : 0;

			// insert cdr record to database
			$query = sprintf("INSERT INTO
										 voip_cdr (caller, callee, call_start_time, time_start_to_end, time_answer_to_end, price, status, type, voipaccountid)
									 VALUES
										 ('%s', '%s', %s, %d, %d, %f, '%s', '%s', %d);", $options['caller'], $options['callee'], $options['startcall'], $options['totaltime'], $options['calltime'], $price, strtolower($options['status']), $call_type, $customer['voipaccountid']);

			$DB->Execute($query);
		} else {
			$fh = (isset($options['file'])) ? fopen($options['file'], 'r') : fopen('php://stdin', 'r');
			$customer_list = getCustomerList();
			$error = array();
			$i=0;

			while($f_line = fgets($fh)) {
				// increment file line counter
				++$i;

				// change line to associative array
				$cdr = parseRow($f_line);

				// check values of cdr array
				$cdr_error = validCDR($cdr);

				if ($cdr_error === TRUE) {
					$tariff_id = $customer_list[$cdr['caller']]['tariffid'];

					//include customer tariff
					if (!isset($tariffs[$tariff_id])) {
						include_tariff($tariff_id);

						if (!isset($tariffs[$tariff_id])) {
							$error['errors'][] = array('line'=>$i, 'line_content'=>$f_line, 'error'=>'Cant find tariff ' . $tariff_id . ' in tariff files.');
							continue;
						}
					}

					// get first letter of type
					$call_type = strtolower($cdr['call_type'][0]);

					// generate unix timestamp
					$call_start = mktime($cdr['call_start_hour'], $cdr['call_start_min'], $cdr['call_start_sec'], $cdr['call_start_month'], $cdr['call_start_day'], $cdr['call_start_year']);

					// no payments for incoming call else calculate cost for call
					$call_cost = getCost($cdr['caller'], $cdr['callee'], $tariff_id);
					$price = ($call_type == 'o') ? round(ceil($cdr['time_answer_to_end']/$call_cost['unitSize']) * $call_cost['costPerUnit'], 5) : 0;

					// insert cdr record to database
					$query = sprintf("INSERT INTO
												voip_cdr (caller, callee, call_start_time, time_start_to_end, time_answer_to_end, price, status, type, voipaccountid)
											VALUES
												('%s', '%s', %d, %d, %d, %f, '%s', '%s', %d);", $cdr['caller'], $cdr['callee'], $call_start, $cdr['time_start_to_end'], $cdr['time_answer_to_end'], $price, strtolower($cdr['call_status']), $call_type, $customer_list[$cdr['caller']]['voipaccountid']);

					$DB->Execute($query);
				} else {
					$error['errors'][] = array('line'=>$i, 'line_content'=>$f_line, 'error'=>$cdr_error);
					continue;
				}
			}

			fclose($fh);
		}
	break;
}

?>
