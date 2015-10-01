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
	't' => 'test',
	'd' => 'debug',
	'h' => 'help',
	'v' => 'version',
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
lms-signals.php
(C) 2001-2015 LMS Developers

EOF;
	exit(0);
}

if (array_key_exists('help', $options)) {
	print <<<EOF
lms-signals.php
(C) 2001-2015 LMS Developers

-C, --config-file=/etc/lms/lms.ini      alternate config file (default: /etc/lms/lms.ini);
-h, --help                      print this help and exit;
-t, --test			test only - don't update database
-d, --debug			print debugging information
-v, --version                   print version info and exit;
-q, --quiet                     suppress any output, except errors

EOF;
	exit(0);
}

$test = array_key_exists('test', $options);
$debug = array_key_exists('debug', $options);

$quiet = array_key_exists('quiet', $options);
if (!$quiet) {
	print <<<EOF
lms-signals.php
(C) 2001-2015 LMS Developers

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

$rs=$DB->GetAll("SELECT * FROM netradiosectors");
foreach ($rs AS $id => $radiosector) {
	#if ($debug) echo $radiosector['name']."\n";
	$netdev=$LMS->GetNetDev($radiosector['netdev']);
	if (preg_match('/mikrotik/i',$netdev['producer'])) {
		$netdevips = $LMS->GetNetDevIPs($radiosector['netdev']);
		$ip=$netdevips['0']['ip'];
		if (preg_match('/.*-(.*)$/',$radiosector['name'],$x))
			$interface=$x[1];
		else
			$interface='all';
			
		if ($debug) echo "Urządzenie ".$netdev['name']."($ip): $interface\n";
		$mt=new Mikrotik($ip);
		$channel=$mt->GetChannel($interface);
		$connected=$mt->GetRadiosectorConnected($interface);
		foreach ($connected AS $id => $dev) {
			$nodeid=$LMS->GetNodeIDByMAC($dev['mac-address']);
			$dev['rx-rate']=preg_replace('/Mbps.*/','',$dev['rx-rate']);
			$dev['tx-rate']=preg_replace('/Mbps.*/','',$dev['tx-rate']);
			$bytes=preg_split('/,/',$dev['bytes']);
			$args=array(strftime('%Y-%m-%d %H:%M:%S'),$nodeid,$netdev['id'],$channel,$dev['routeros-version'],$dev['signal-strength'],$dev['tx-signal-strength'],$dev['rx-rate'],$dev['tx-rate'],$dev['tx-ccq'],$dev['rx-ccq'],$bytes[0],$bytes[1]);

			#date nodeid netdev channel software rxsignal txsignal rxrate txrate rxccq txccq rxbytes txbytes
			$DB->Execute("INSERT INTO signals VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)",$args);
		}
	} elseif ($debug) {
		echo "Producentem ".$netdev['name']." jest ".$netdev['producer']." a nie Mikrotik!\n";
	}
}



#$mt = new RouterosAPI();
$mt->debug=false;






?>
