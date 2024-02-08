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

ini_set('error_reporting', E_ALL & ~E_NOTICE & ~E_DEPRECATED);

const SCRIPT_COPYRIGHT_INFO = '(c) 2001-2024 LMS Developers';

$http_mode = isset($_SERVER['HTTP_HOST']);

if ($http_mode) {
    ob_clean();
    $options = array();
} else {
    $long_to_shorts = array();
    $short_to_longs = array();
    $params_required = array();
    $params_optional = array();

    $script_parameters = array_merge(
        array(
            'config-file:' => 'C:',
            'quiet' => 'q',
            'help' => 'h',
            'version' => 'v',
        ),
        $script_parameters ?? array()
    );

    foreach ($script_parameters as $long => $short) {
        $param_required = strpos($long, ':') === strlen($long) - 1;
        $param_optional = strpos($long, ':') === strlen($long) - 2;
        $long = str_replace(':', '', $long);
        if (isset($short)) {
            $short = str_replace(':', '', $short);
            $short_to_longs[$short] = $long;
        }
        $long_to_shorts[$long] = $short;
        $params_required[$long] = $param_required;
        $params_optional[$long] = $param_optional;
    }

    $options = getopt(
        implode(
            '',
            array_filter(
                array_values($script_parameters),
                function ($value) {
                    return isset($value);
                }
            )
        ),
        array_keys($script_parameters)
    );

    foreach (array_flip(array_filter($long_to_shorts, function ($value) {
        return isset($value);
    })) as $short => $long) {
        if (array_key_exists($short, $options)) {
            $options[$long] = $options[$short];
            unset($options[$short]);
        }
    }

    $option = null;
    $args = array();
    foreach (array_slice($argv, 1) as $arg) {
        $args = array_merge($args, explode('=', $arg));
    }
    foreach ($args as $arg_idx => $arg) {
        if (strpos($arg, '-') !== 0) {
            if (!isset($option)) {
                die('Fatal error: unexpected option parameter \'' . $arg . '\'!' . PHP_EOL);
            }
            if (isset($options[$option])) {
                if (empty($params_required[$option]) && empty($params_optional[$option])) {
                    die('Fatal error: option \'' . $option_presentation . '\' doesn\'t expect parameter!' . PHP_EOL);
                }
            } else {
                if (!empty($params_required[$option])) {
                    die('Fatal error: option \'' . $option_presentation . '\' requires parameter!' . PHP_EOL);
                }
            }
            $option = null;
            continue;
        } elseif (isset($option) && !empty($params_required[$option])) {
            die('Fatal error: option \'' . $option_presentation . '\' requires parameter!' . PHP_EOL);
        }

        $option = mb_substr($arg, 1);
        $option_presentation = '-';
        if (strpos($option, '-') === 0) {
            $option = mb_substr($option, 1);
            $long_option = true;
            $option_presentation .= '-';
        } else {
            $long_option = false;
        }
        $equal_pos = strpos($option, '=');
        if ($equal_pos !== false) {
            $option = mb_substr($option, 0, $equal_pos);
        }
        $option_presentation .= $option;

        if ($long_option && !array_key_exists($option, $long_to_shorts)
            || !$long_option && !isset($short_to_longs[$option])) {
            die('Fatal error: unsupported option \'' . $option_presentation . '\'!' . PHP_EOL);
        }

        $option = $long_option ? $option : $short_to_longs[$option];

        if (!empty($params_required[$option]) && $arg_idx == count($args) - 1) {
            die('Fatal error: option \'' . $option_presentation . '\' requires parameter!' . PHP_EOL);
        }
    }
}

if (isset($options['force-http-mode'])) {
    $http_mode = true;
}

if (!isset($script_name)) {
    $script_name = basename($argv[0]);
}

if (isset($options['version'])) {
    echo $script_name . PHP_EOL
        . SCRIPT_COPYRIGHT_INFO . PHP_EOL
        . PHP_EOL;
    exit(0);
}

if (isset($options['help'])) {
    echo $script_name . PHP_EOL
        . SCRIPT_COPYRIGHT_INFO . PHP_EOL
        . PHP_EOL
        . '-C, --config-file=/etc/lms/lms.ini      alternate config file (default: /etc/lms/lms.ini);' . PHP_EOL
        . '-h, --help                      print this help and exit;' . PHP_EOL
        . '-v, --version                   print version info and exit;' . PHP_EOL
        . '-q, --quiet                     suppress any output, except errors;' . PHP_EOL
        . $script_help . PHP_EOL;
    exit(0);
}

if (isset($options['config-file'])) {
    $CONFIG_FILE = $options['config-file'];
} elseif ($http_mode && is_readable('lms.ini')) {
    $CONFIG_FILE = 'lms.ini';
} elseif ($http_mode && is_readable(DIRECTORY_SEPARATOR . 'etc' . DIRECTORY_SEPARATOR . 'lms' . DIRECTORY_SEPARATOR . 'lms-' . $_SERVER['HTTP_HOST'] . '.ini')) {
    $CONFIG_FILE = DIRECTORY_SEPARATOR . 'etc' . DIRECTORY_SEPARATOR . 'lms' . DIRECTORY_SEPARATOR . 'lms-' . $_SERVER['HTTP_HOST'] . '.ini';
} else {
    $CONFIG_FILE = DIRECTORY_SEPARATOR . 'etc' . DIRECTORY_SEPARATOR . 'lms' . DIRECTORY_SEPARATOR . 'lms.ini';
}

$quiet = isset($options['quiet']);

if (!$quiet && (!$http_mode || isset($options['force-http-mode']))) {
    echo $script_name . PHP_EOL
        . SCRIPT_COPYRIGHT_INFO . PHP_EOL
        . 'Using file ' . $CONFIG_FILE . ' as config.' . PHP_EOL;
}

if (!is_readable($CONFIG_FILE)) {
    die('Unable to read configuration file \'' . $CONFIG_FILE . '\'!' . PHP_EOL);
}

define('CONFIG_FILE', $CONFIG_FILE);

$CONFIG = (array) parse_ini_file($CONFIG_FILE, true);

// Check for configuration vars and set default values
define('SYS_DIR', $CONFIG['directories']['sys_dir'] ?? getcwd());
define('LIB_DIR', $CONFIG['directories']['lib_dir'] ?? $CONFIG['directories']['sys_dir'] . DIRECTORY_SEPARATOR . 'lib');
define('DOC_DIR', $CONFIG['directories']['doc_dir'] ?? $CONFIG['directories']['sys_dir'] . DIRECTORY_SEPARATOR . 'documents');
define('STORAGE_DIR', $CONFIG['directories']['storage_dir'] ?? $CONFIG['directories']['sys_dir'] . DIRECTORY_SEPARATOR . 'storage');
define('SMARTY_COMPILE_DIR', $CONFIG['directories']['smarty_compile_dir'] ?? $CONFIG['directories']['sys_dir'] . DIRECTORY_SEPARATOR . 'templates_c');
define('SMARTY_TEMPLATES_DIR', $CONFIG['directories']['smarty_templates_dir'] ?? $CONFIG['directories']['sys_dir'] . DIRECTORY_SEPARATOR . 'templates');
define('PLUGIN_DIR', $CONFIG['directories']['plugin_dir'] ?? $CONFIG['directories']['sys_dir'] . DIRECTORY_SEPARATOR . 'plugins');
const PLUGINS_DIR = PLUGIN_DIR;

// Load autoloader
$composer_autoload_path = SYS_DIR . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
if (file_exists($composer_autoload_path)) {
    require_once $composer_autoload_path;
} else {
    die("Composer autoload not found. Run 'composer install' command from LMS directory and try again. More information at https://getcomposer.org/" . PHP_EOL);
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
require_once(LIB_DIR . DIRECTORY_SEPARATOR . 'definitions.php');
