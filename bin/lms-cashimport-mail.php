#!/usr/bin/env php
<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2020 LMS Developers
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
    'c' => 'stdout',
    's:' => 'section:',
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
lms-cashimport-mail.php
(C) 2001-2020 LMS Developers

EOF;
    exit(0);
}

if (array_key_exists('help', $options)) {
    print <<<EOF
lms-cashimport-mail.php
(C) 2001-2020 LMS Developers

-C, --config-file=/etc/lms/lms.ini      alternate config file (default: /etc/lms/lms.ini);
-h, --help                      print this help and exit;
-v, --version                   print version info and exit;
-q, --quiet                     suppress any output, except errors;
-c, --stdout                    write cash import file contents to stdout
-s, --section=<section-name>    section name from lms configuration where settings
                                are stored

EOF;
    exit(0);
}

$quiet = array_key_exists('quiet', $options);
if (!$quiet) {
    print <<<EOF
lms-cashimport-mail.php
(C) 2001-2020 LMS Developers

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

define('SYS_DIR', $CONFIG['directories']['sys_dir']);
define('LIB_DIR', $CONFIG['directories']['lib_dir']);

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

// Initialize Session, Auth and LMS classes

$stdout = isset($options['stdout']);
$config_section = isset($options['section']) && preg_match('/^[a-z0-9-_]+$/i', $options['section']) ? $options['section'] : 'cashimport';

$cashimport_server = ConfigHelper::getConfig($config_section . '.server');
$cashimport_username = ConfigHelper::getConfig($config_section . '.username');
$cashimport_password = ConfigHelper::getConfig($config_section . '.password');
$cashimport_filename_pattern = ConfigHelper::getConfig($config_section . '.filename_pattern', '', true);

if (empty($cashimport_server) || empty($cashimport_username) || empty($cashimport_password)) {
    die("Fatal error: mailbox credentials are not set!" . PHP_EOL);
}

$cashimport_use_seen_flag = ConfigHelper::checkValue(ConfigHelper::getConfig($config_section . '.use_seen_flag', true));
$cashimport_folder = ConfigHelper::getConfig($config_section . '.folder', 'INBOX');

$ih = @imap_open("{" . $cashimport_server . "}" . $cashimport_folder, $cashimport_username, $cashimport_password);
if (!$ih) {
    die("Cannot connect to mail server!" . PHP_EOL);
}

$posts = imap_search($ih, $cashimport_use_seen_flag ? 'UNSEEN' : 'ALL');
if (empty($posts)) {
    imap_close($ih);
    die;
}

foreach ($posts as $postid) {
    $files = array();
    $post = imap_fetchstructure($ih, $postid);
    //print_r($post);
    if ($post->type == 1) {
        $parts = $post->parts;
        //print_r($parts);

        foreach ($parts as $partid => $part) {
            if ($part->ifdisposition) {
                if (in_array(strtolower($part->disposition), array('attachment', 'inline'))
                    && $part->ifdparameters) {
                    foreach ($part->dparameters as $dparameter) {
                        if (strtolower($dparameter->attribute) == 'filename') {
                            if (preg_match('/^=\?/', $dparameter->value)) {
                                $elems = imap_mime_header_decode($dparameter->value);
                                if ($elems[0]->charset != 'default') {
                                    $fname = iconv($elems[0]->charset, 'utf-8', $elems[0]->text);
                                } else {
                                    $fname = $elems[0]->text;
                                }
                            } else {
                                $fname = $dparameter->value;
                            }
                            $body = imap_fetchbody($ih, $postid, $partid + 1);
                            if ($part->encoding == 3) {
                                $body = imap_base64($body);
                            }
                            $files[] = array(
                            'name' => $fname,
                            'contents' => $body,
                            );
                        }
                    }
                }
            } elseif ($part->ifsubtype) {
                if (strtolower($part->subtype) == 'octet-stream' && $part->ifparameters) {
                    foreach ($part->parameters as $parameter) {
                        if (strtolower($parameter->attribute) == 'name') {
                            $elems = imap_mime_header_decode($parameter->value);
                            if ($elems[0]->charset != 'default') {
                                $fname = iconv($elems[0]->charset, 'utf-8', $elems[0]->text);
                            } else {
                                $fname = $elems[0]->text;
                            }
                            $body = imap_fetchbody($ih, $postid, $partid + 1);
                            if ($part->encoding == 3) {
                                $body = imap_base64($body);
                            }
                            $files[] = array(
                            'name' => $fname,
                            'contents' => $body,
                            );
                        }
                    }
                } elseif (strtolower($part->subtype) == 'mixed' && isset($part->parts)) {
                    foreach ($part->parts as $subpartid => $subpart) {
                        if ($subpart->type == 3 && $subpart->ifdisposition
                            && in_array(strtolower($subpart->disposition), array('attachment', 'inline'))
                            && $subpart->ifdparameters) {
                            foreach ($subpart->dparameters as $dparameter) {
                                if (strtolower($dparameter->attribute) == 'filename') {
                                    if (preg_match('/^=\?/', $dparameter->value)) {
                                        $elems = imap_mime_header_decode($dparameter->value);
                                        if ($elems[0]->charset != 'default') {
                                            $fname = iconv($elems[0]->charset, 'utf-8', $elems[0]->text);
                                        } else {
                                            $fname = $elems[0]->text;
                                        }
                                    } else {
                                        $fname = $dparameter->value;
                                    }
                                    $body = imap_fetchbody($ih, $postid, ($partid + 1) . '.' . ($subpartid + 1));
                                    if ($subpart->encoding == 3) {
                                        $body = imap_base64($body);
                                    }
                                    $files[] = array(
                                        'name' => $fname,
                                        'contents' => $body,
                                    );
                                }
                            }
                        }
                    }
                }
            }
        }
    } elseif ($post->type == 3 && $post->ifdisposition
        && in_array(strtolower($post->disposition), array('attachment', 'inline'))
        && $post->ifdparameters) {
        foreach ($post->dparameters as $dparameter) {
            if (strtolower($dparameter->attribute) == 'filename') {
                if (preg_match('/^=\?/', $dparameter->value)) {
                    $elems = imap_mime_header_decode($dparameter->value);
                    if ($elems[0]->charset != 'default') {
                        $fname = iconv($elems[0]->charset, 'utf-8', $elems[0]->text);
                    } else {
                        $fname = $elems[0]->text;
                    }
                } else {
                    $fname = $dparameter->value;
                }
                $body = imap_fetchbody($ih, $postid, '1');
                if ($post->encoding == 3) {
                    $body = imap_base64($body);
                }
                $files[] = array(
                'name' => $fname,
                'contents' => $body,
                );
            }
        }
    }

    if ($cashimport_use_seen_flag) {
        imap_setflag_full($ih, $postid, "\\Seen");
    } else {
        imap_clearflag_full($ih, $postid, "\\Seen");
    }

    if (empty($files)) {
        continue;
    }

    foreach ($files as $file) {
        if (!empty($cashimport_filename_pattern) && !preg_match('/' . $cashimport_filename_pattern . '/', $file['name'])) {
            continue;
        }

        if ($stdout) {
            $import_file = 'php://stdout';
        } else {
            $import_file = $file['name'];
            if (file_exists($import_file)) {
                if (preg_match('/^(?<name>.+)\.(?<extension>[^\.]+)$/', $import_file, $m)) {
                    $name = $m['name'];
                    $extension = $m['extension'];
                } else {
                    $name = $import_file;
                    $extension = '';
                }
                $nr = 1;
                while (file_exists($name . '_' . $nr . (empty($extension) ? '' : '.' . $extension))) {
                    $nr++;
                }
                $import_file = $name . '_' . $nr . (empty($extension) ? '' : '.' . $extension);
            }
        }
        $fh = fopen($import_file, "w");
        if (empty($fh)) {
            echo "Couldn't write contents to $import_file file!" . PHP_EOL;
            continue;
        }
        fwrite($fh, $file['contents']);
        fclose($fh);
    }
}

imap_close($ih);

?>
