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
	'a:' => 'action:',
	'd' => 'debug',
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

// valid parameters
$param_err = validParamters($options);
if ($param_err !== TRUE)
	die($param_err);

define('VOIP_CACHE_DIR', isset($options['cache-dir']) ? $options['cache-dir']
	: SYS_DIR . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'voip' . DIRECTORY_SEPARATOR . 'cache');

switch (strtolower($options['action'])) {
	case 'estimate':
		if (empty($options['caller']))
			die('Caller phone number is not set. Please use --caller [phone_number].' . PHP_EOL);

		if (empty($options['callee']))
			die('Callee phone number is not set. Please use --callee [phone_number].' . PHP_EOL);

		try {
			// get maximum call time in seconds
			$call_time = getMaxCallTime($options['caller'], $options['callee']);

			// if debug mode is set print value else change to miliseconds before print
			echo (array_key_exists('debug', $options)) ? $call_time.PHP_EOL : $call_time*1000;
		} catch (Exception $e) {
			echo $e->getMessage();
		}
	break;

	case 'account':
		// get customer list
		$customer_list = getCustomerList();

		// get prefix to group name array
		$prefix_list = getPrefixList();

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
				die('Call type is not set. Please use --type.' . PHP_EOL);

			if (empty($options['status']))
				die('Call status is not set. Please use --status.' . PHP_EOL);

			if (empty($options['uniqueid']))
				die('Call unique id is not set. Please use --uniqueid' . PHP_EOL);

			$caller = $customer_list[$options['caller']];
			$callee = $customer_list[$options['callee']];

			try {
				$caller['prefix_group'] = $prefix_list[findLongestPrefix($caller['phone'], $caller['tariffid'])]['name'];
				$callee['prefix_group'] = $prefix_list[findLongestPrefix($callee['phone'], $callee['tariffid'])]['name'];

				// set call status
				$call_status = parseCallStatus($options['status']);

				// set call type
				$call_type = parseCallType($options['type']);

				switch ($call_type) {
					case CALL_INCOMING: // no payments for incoming call
						$price = 0;
					break;

					case CALL_OUTGOING:
						$call_cost = getCost($options['caller'], $options['callee'], $caller['tariffid']);
						$price = round(ceil($options['calltime']/$call_cost['unitSize']) * $call_cost['costPerUnit'], 5);
					break;
				}

				$DB->BeginTrans();

				if ($price)
					updateCustomerBalance($price, $caller['voipaccountid']);

				// insert cdr record to database
				$DB->Execute("INSERT INTO voip_cdr
								(caller, callee, call_start_time, time_start_to_end, time_answer_to_end,
								price, status, type, callervoipaccountid, calleevoipaccountid, caller_flags,
								callee_flags, caller_prefix_group, callee_prefix_group, uniqueid)
							VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
							array(
								$options['caller'], $options['callee'], $options['startcall'], $options['totaltime'],
								$options['calltime'], $price, $call_status, $call_type, $caller['voipaccountid'],
								$callee['voipaccountid'], (int) $caller['flags'], (int) $callee['flags'],
								$caller['prefix_group'], $callee['prefix_group'], $options['uniqueid']));

				$DB->CommitTrans();
			}
			catch (Exception $e) {
				echo $e->getMessage();
			}
		} else {
			$fh = (isset($options['file'])) ? fopen($options['file'], 'r') : fopen('php://stdin', 'r');
			$error = array();
			$i=0;

			while($f_line = fgets($fh)) {
				// file line counter
				++$i;

				try {
					// change line to associative array
					$cdr = parseRow($f_line);

					// check values of cdr array
					validCDR($cdr);

					$caller = $customer_list[$cdr['caller']];
					$callee = $customer_list[$cdr['callee']];

					$caller['prefix_group'] = $prefix_list[findLongestPrefix($caller['phone'], $caller['tariffid'])]['name'];
					$callee['prefix_group'] = $prefix_list[findLongestPrefix($callee['phone'], $callee['tariffid'])]['name'];

					// generate unix timestamp
					$call_start = mktime($cdr['call_start_hour'],
										 $cdr['call_start_min'],
										 $cdr['call_start_sec'],
										 $cdr['call_start_month'],
										 $cdr['call_start_day'],
										 $cdr['call_start_year']);

					// set call status
					$call_status = parseCallStatus($cdr['call_status']);

					// set call type
					$call_type = parseCallType($cdr['call_type']);

					switch ($call_type) {
						case CALL_INCOMING: // no payments for incoming call
							$price = 0;
						break;

						case CALL_OUTGOING:
							$call_cost = getCost($cdr['caller'], $cdr['callee'], $caller['tariffid']);
							$price = round(ceil($cdr['time_answer_to_end']/$call_cost['unitSize']) * $call_cost['costPerUnit'], 5);
						break;
					}

					$DB->BeginTrans();

					if ($price)
						updateCustomerBalance($caller['voipaccountid'], $price);

					// insert cdr record to database
					$DB->Execute("INSERT INTO voip_cdr
									(caller, callee, call_start_time, time_start_to_end, time_answer_to_end,
									price, status, type, callervoipaccountid, calleevoipaccountid, caller_flags,
									callee_flags, caller_prefix_group, callee_prefix_group, uniqueid)
								  VALUES
									(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?);",
								  array(
									$cdr['caller'], $cdr['callee'], $call_start, $cdr['time_start_to_end'],
									$cdr['time_answer_to_end'], $price, $call_status, $call_type, $caller['voipaccountid'],
									$callee['voipaccountid'], (int) $caller['flags'], (int) $callee['flags'],
									$caller['prefix_group'], $callee['prefix_group'], $cdr['uniqueid']));

					$DB->CommitTrans();
				} catch(Exception $e) {
					echo "line $i: ", $e->getMessage(), PHP_EOL;
				}
			}

			if ($error['errors']) {
				echo 'Failed loaded CDR records: ' . count($error['errors']) . PHP_EOL;

				// do somethink with errors here
			}

			fclose($fh);
		}
	break;

	case 'gencache':
		// create cache directory tree
		if (!file_exists(VOIP_CACHE_DIR) && !mkdir(VOIP_CACHE_DIR, 0755, true))
			die('Failed to create cache folder.');

		$cache_array = $DB->GetAll("SELECT p.prefix, t.price, t.unitsize, t.tariffid
									FROM voip_prefixes p
									LEFT JOIN voip_prefix_groups g ON p.groupid = g.id
									LEFT JOIN voip_tariffs t ON g.id = t.groupid");

		$prefix_array = array();
		foreach ($cache_array as $single_prefix)
			$prefix_array[$single_prefix['tariffid']][] = $single_prefix;
		unset($cache_array);

		// build cache files
		foreach ($prefix_array as $tariffid => $single_tariff) {
			$root_path = VOIP_CACHE_DIR . DIRECTORY_SEPARATOR . 'tariffs'
				 . DIRECTORY_SEPARATOR . $tariffid;

			foreach ($single_tariff as $prefix_data) {
				$path = $root_path;
				foreach (str_split($prefix_data['prefix']) as $digit)
					$path .= DIRECTORY_SEPARATOR . $digit;

				if (!is_dir($path))
					mkdir($path, 0755, true);

				file_put_contents($path . DIRECTORY_SEPARATOR . 'unit_size', $prefix_data['unitsize']);
				file_put_contents($path . DIRECTORY_SEPARATOR . 'sale_price', $prefix_data['price']);
			}
		}

		break;
}

?>
