#!/usr/bin/env php
<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2024 LMS Developers
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

$script_parameters = array(
    'config-file:' => 'C:',
    'quiet' => 'q',
    'help' => 'h',
    'version' => 'v',
    'section:' => 's:',
    'import-file:' => 'f:',
);

$script_help = <<<EOF
-C, --config-file=/etc/lms/lms.ini      alternate config file (default: /etc/lms/lms.ini);
-h, --help                      print this help and exit;
-v, --version                   print version info and exit;
-q, --quiet                     suppress any output, except errors;
-s, --section=<section-name>    section name from lms configuration where settings
                                are stored
-f, --import-file               cash import file name from which import contents is read
                                (stdin if not specifed)
EOF;

require_once('script-options.php');

$SYSLOG = SYSLOG::getInstance();

// Initialize Session, Auth and LMS classes

$AUTH = null;
$LMS = new LMS($DB, $AUTH, $SYSLOG);

$config_section = isset($options['section']) && preg_match('/^[a-z0-9-_]+$/i', $options['section'])
    ? $options['section'] : 'cashimport';

$plugin_manager = new LMSPluginManager();
$LMS->setPluginManager($plugin_manager);

if (array_key_exists('import-file', $options)) {
    $import_file = $options['import-file'];
    $import_filename = basename($import_file);
    $filemtime = filemtime($import_file);
} else {
    $import_file = 'php://stdin';
    $import_filename = date('YmdHis') . '.csv';
    $filemtime = time();
}

if (isset($options['section'])) {
    $import_config = ConfigHelper::getConfig($config_section . '.import_config', '', true);
}
if (!isset($import_config) || !strlen($import_config)) {
    $import_config = ConfigHelper::getConfig('phpui.import_config', 'cashimportcfg.php');
}
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
    ConfigHelper::checkConfig($config_section . '.use_file_date') ? $filemtime : null,
    $config_section
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

if (ConfigHelper::checkConfig($config_section . '.autocommit')) {
    $LMS->CashImportCommit();
}
