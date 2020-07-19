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
    'f:' => 'import-file:',
);

foreach ($parameters as $key => $val) {
    $val = preg_replace('/:/', '', $val);
    $newkey = preg_replace('/:/', '', $key);
    $short_to_longs[$newkey] = $val;
}
$options = getopt(implode('', array_keys($parameters)), $parameters);
foreach ($short_to_longs as $short => $long) {
    if (array_key_exists($short, $options)) {
        $options[$long] = $options[$short];
        unset($options[$short]);
    }
}

if (array_key_exists('version', $options)) {
    print <<<EOF
lms-cashimport.php
(C) 2001-2016 LMS Developers

EOF;
    exit(0);
}

if (array_key_exists('help', $options)) {
    print <<<EOF
lms-cashimport.php
(C) 2001-2016 LMS Developers

-C, --config-file=/etc/lms/lms.ini      alternate config file (default: /etc/lms/lms.ini);
-h, --help                      print this help and exit;
-v, --version                   print version info and exit;
-q, --quiet                     suppress any output, except errors;
-f, --import-file               cash import file name from which import contents is read
                                (stdin if not specifed)

EOF;
    exit(0);
}

$quiet = array_key_exists('quiet', $options);
if (!$quiet) {
    print <<<EOF
lms-cashimport.php
(C) 2001-2016 LMS Developers

EOF;
}

if (array_key_exists('config-file', $options)) {
    $CONFIG_FILE = $options['config-file'];
} else {
    $CONFIG_FILE = '/etc/lms/lms.ini';
}

if (!$quiet) {
    echo "Using file " . $CONFIG_FILE . " as config." . PHP_EOL;
}

if (!is_readable($CONFIG_FILE)) {
    die("Unable to read configuration file [" . $CONFIG_FILE . "]!" . PHP_EOL);
}

define('CONFIG_FILE', $CONFIG_FILE);

$CONFIG = (array) parse_ini_file($CONFIG_FILE, true);

// Check for configuration vars and set default values
$CONFIG['directories']['sys_dir'] = (!isset($CONFIG['directories']['sys_dir']) ? getcwd() : $CONFIG['directories']['sys_dir']);
$CONFIG['directories']['lib_dir'] = (!isset($CONFIG['directories']['lib_dir']) ? $CONFIG['directories']['sys_dir'] . DIRECTORY_SEPARATOR . 'lib' : $CONFIG['directories']['lib_dir']);
$CONFIG['directories']['modules_dir'] = (!isset($CONFIG['directories']['modules_dir']) ? $CONFIG['directories']['sys_dir'] . DIRECTORY_SEPARATOR . 'modules' : $CONFIG['directories']['modules_dir']);
$CONFIG['directories']['plugin_dir'] = (!isset($CONFIG['directories']['plugin_dir']) ? $CONFIG['directories']['sys_dir'] . DIRECTORY_SEPARATOR . 'plugins' : $CONFIG['directories']['plugin_dir']);
$CONFIG['directories']['plugins_dir'] = $CONFIG['directories']['plugin_dir'];

define('SYS_DIR', $CONFIG['directories']['sys_dir']);
define('LIB_DIR', $CONFIG['directories']['lib_dir']);
define('MODULES_DIR', $CONFIG['directories']['modules_dir']);
define('PLUGIN_DIR', $CONFIG['directories']['plugin_dir']);
define('PLUGINS_DIR', $CONFIG['directories']['plugin_dir']);

// Load autoloader
$composer_autoload_path = SYS_DIR . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
if (file_exists($composer_autoload_path)) {
    require_once $composer_autoload_path;
} else {
    die("Composer autoload not found. Run 'composer install' command from LMS directory and try again. More informations at https://getcomposer.org/" . PHP_EOL);
}

// Init database

$DB = null;

try {
    $DB = LMSDB::getInstance();
} catch (Exception $ex) {
    trigger_error($ex->getMessage(), E_USER_WARNING);
    // can't work without database
    die("Fatal error: cannot connect to database!" . PHP_EOL);
}

// Include required files (including sequence is important)

require_once(LIB_DIR . DIRECTORY_SEPARATOR . 'common.php');
require_once(LIB_DIR . DIRECTORY_SEPARATOR . 'language.php');
include_once(LIB_DIR . DIRECTORY_SEPARATOR . 'definitions.php');

$SYSLOG = SYSLOG::getInstance();

// Initialize Session, Auth and LMS classes

$AUTH = null;
$LMS = new LMS($DB, $AUTH, $SYSLOG);

LMS::$currency = Localisation::getCurrentCurrency();
LMS::$default_currency = ConfigHelper::getConfig('phpui.default_currency', '', true);
if (empty(LMS::$default_currency) || !isset($CURRENCIES[LMS::$default_currency])) {
    LMS::$default_currency = LMS::$currency;
}

$plugin_manager = new LMSPluginManager();
$LMS->setPluginManager($plugin_manager);

if (array_key_exists('import-file', $options)) {
    $import_file = $options['import-file'];
    $import_filename = basename($import_file);
    $filemtime = filemtime($import_file);
} else {
    $import_file = 'php://stdin';
    $import_filename = strftime('%Y%m%d%H%M%S.csv');
    $filemtime = time();
}

$import_config = ConfigHelper::getConfig('phpui.import_config', 'cashimportcfg.php');
if (strpos($import_config, DIRECTORY_SEPARATOR) === false) {
    $import_config = MODULES_DIR . DIRECTORY_SEPARATOR . $import_config;
}
@include($import_config);
if (!isset($patterns) || !is_array($patterns)) {
    die(trans("Configuration error. Patterns array not found!") . PHP_EOL);
}

if ($import_file != 'php://stdin' && !is_readable($import_file)) {
    die("Couldn't read contents from $import_file file!" . PHP_EOL);
}

$error = $LMS->CashImportParseFile(
    $import_filename,
    file_get_contents($import_file),
    $patterns,
    $quiet,
    ConfigHelper::checkConfig('cashimport.use_file_date') ? $filemtime : null
);

if (!$quiet && !empty($error)) {
    foreach ($error['lines'] as $ln => $item) {
        if (is_array($item)) {
            $attributes = array();
            foreach ($item as $key => $value) {
                $attributes[] = $key . ': ' . $value;
            }
            echo "Duplicate: line " . $ln . ': ' . implode(', ', $attributes) . PHP_EOL;
        } else {
            echo "Invalid format: line " . $ln . ': ' . $item . PHP_EOL;
        }
    }
}

if (ConfigHelper::checkConfig('cashimport.autocommit')) {
    $LMS->CashImportCommit();
}
