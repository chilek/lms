#!/usr/bin/env php
<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2019 LMS Developers
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
ini_set('max_execution_time', 0);
ini_set('memory_limit', -1);

$parameters = array(
	'C:' => 'config-file:',
	'q' => 'quiet',
	'h' => 'help',
	'v' => 'version',
	'o:' => 'update-order:',
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
upgradedb.php
(C) 2001-2019 LMS Developers

EOF;
	exit(0);
}

if (array_key_exists('help', $options)) {
	print <<<EOF
upgradedb.php
(C) 2001-2019 LMS Developers

-C, --config-file=/etc/lms/lms.ini      alternate config file (default: /etc/lms/lms.ini);
-h, --help                      print this help and exit;
-t, --test                      print only invoices to send;
-v, --version                   print version info and exit;
-q, --quiet                     suppress any output, except errors;
-o, --update-order=<core,plugins>
                                comma separated list of system facilities determining
                                db schema update order

EOF;
	exit(0);
}

$quiet = array_key_exists('quiet', $options);
if (!$quiet) {
	print <<<EOF
upgrade.php
(C) 2001-2019 LMS Developers

EOF;
}

if (array_key_exists('config-file', $options))
	$CONFIG_FILE = $options['config-file'];
else
	$CONFIG_FILE = DIRECTORY_SEPARATOR . 'etc' . DIRECTORY_SEPARATOR . 'lms' . DIRECTORY_SEPARATOR . 'lms.ini';

if (!$quiet)
	echo "Using file " . $CONFIG_FILE . " as config." . PHP_EOL;

// find alternative config files:
if (is_readable('lms.ini'))
	$CONFIG_FILE = 'lms.ini';
elseif (!is_readable($CONFIG_FILE))
	die('Unable to read configuration file ['.$CONFIG_FILE.']!' . PHP_EOL);

define('CONFIG_FILE', $CONFIG_FILE);

$CONFIG = (array) parse_ini_file(CONFIG_FILE, true);

// Check for configuration vars and set default values
$CONFIG['directories']['sys_dir'] = (!isset($CONFIG['directories']['sys_dir']) ? getcwd() : $CONFIG['directories']['sys_dir']);
$CONFIG['directories']['lib_dir'] = (!isset($CONFIG['directories']['lib_dir']) ? $CONFIG['directories']['sys_dir'] . DIRECTORY_SEPARATOR . 'lib' : $CONFIG['directories']['lib_dir']);
$CONFIG['directories']['plugin_dir'] = (!isset($CONFIG['directories']['plugin_dir']) ? $CONFIG['directories']['sys_dir'] . DIRECTORY_SEPARATOR . 'plugins' : $CONFIG['directories']['plugin_dir']);
$CONFIG['directories']['plugins_dir'] = $CONFIG['directories']['plugin_dir'];
$CONFIG['directories']['vendor_dir'] = (!isset($CONFIG['directories']['vendor_dir']) ? $CONFIG['directories']['sys_dir'] . DIRECTORY_SEPARATOR . 'vendor' : $CONFIG['directories']['vendor_dir']);

define('SYS_DIR', $CONFIG['directories']['sys_dir']);
define('LIB_DIR', $CONFIG['directories']['lib_dir']);
define('PLUGIN_DIR', $CONFIG['directories']['plugin_dir']);
define('PLUGINS_DIR', $CONFIG['directories']['plugin_dir']);
define('VENDOR_DIR', $CONFIG['directories']['vendor_dir']);

// Load autoloader
$composer_autoload_path = VENDOR_DIR . DIRECTORY_SEPARATOR . 'autoload.php';
if (file_exists($composer_autoload_path)) {
	require_once $composer_autoload_path;
} else {
	die("Composer autoload not found. Run 'composer install' command from LMS directory and try again. More informations at https://getcomposer.org/" . PHP_EOL);
}

// Do some checks and load config defaults
require_once(LIB_DIR . DIRECTORY_SEPARATOR . 'common.php');

// Init database

$DB = null;

try {
	$DB = LMSDB::getInstance();
} catch (Exception $ex) {
	trigger_error($ex->getMessage(), E_USER_WARNING);
	// can't work without database
	die("Fatal error: cannot connect to database!" . PHP_EOL);
}

if (isset($options['update-order'])) {
	$facilities = explode(',', $options['update-order']);
	foreach ($facilities as $facility)
		if (!in_array($facility, array('core', 'plugins')))
			die('Unsupported system facility: ' . $facility . PHP_EOL);
} else
	$facilities = array('core', 'plugins');

foreach ($facilities as $facility)
	switch ($facility) {
		case 'core':
			$schema_version = $DB->UpgradeDb();
			echo 'DB schema version bumped to ' . $schema_version . PHP_EOL;
			break;
		case 'plugins':
			$plugin_manager = new LMSPluginManager();
			$plugin_info = $plugin_manager->getAllPluginInfo();
			if (!empty($plugin_info))
				foreach ($plugin_info as $plugin)
					if ($plugin['dbcurrschversion'])
						echo 'Plugin ' . $plugin['name'] . ' DB schema version bumped to ' . $plugin['dbcurrschversion'] . PHP_EOL;
			break;
	}

$errors = $DB->GetErrors();
if (!empty($errors)) {
	echo 'DB schema upgrade errors:' . PHP_EOL;
	foreach ($errors as $error) {
		echo $error['query'] . PHP_EOL;
		echo $error['error'] . PHP_EOL;
	}
}

?>
