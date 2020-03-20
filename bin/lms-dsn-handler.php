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
lms-dsnhandler.php
(C) 2001-2016 LMS Developers

EOF;
    exit(0);
}

if (array_key_exists('help', $options)) {
    print <<<EOF
lms-dsnhandler.php
(C) 2001-2016 LMS Developers

-C, --config-file=/etc/lms/lms.ini      alternate config file (default: /etc/lms/lms.ini);
-h, --help                      print this help and exit;
-v, --version                   print version info and exit;
-q, --quiet                     suppress any output, except errors;

EOF;
    exit(0);
}

$quiet = array_key_exists('quiet', $options);
if (!$quiet) {
    print <<<EOF
lms-dsnhandler.php
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
    // can't working without database
    die("Fatal error: cannot connect to database!" . PHP_EOL);
}

// Include required files (including sequence is important)

require_once(LIB_DIR . DIRECTORY_SEPARATOR . 'common.php');
require_once(LIB_DIR . DIRECTORY_SEPARATOR . 'language.php');
include_once(LIB_DIR . DIRECTORY_SEPARATOR . 'definitions.php');
/*
$SYSLOG = SYSLOG::getInstance();
*/

$ih = @imap_open(
    '{' . ConfigHelper::getConfig('dsn-handler.server') . '}' . ConfigHelper::getConfig('dsn-handler.folder', 'INBOX'),
    ConfigHelper::getConfig('dsn-handler.username'),
    ConfigHelper::getConfig('dsn-handler.password')
);
if (!$ih) {
    die("Cannot connect to mail server!" . PHP_EOL);
}

$handled_posts = array();
$posts = imap_search($ih, 'ALL');
if (!empty($posts)) {
    foreach ($posts as $postid) {
        $post = imap_fetchstructure($ih, $postid);
        if ($post->subtype != 'REPORT') {
            continue;
        }
        if (count($post->parts) < 2 || count($post->parts) > 3) {
            continue;
        }
        $parts = $post->parts;

        $msgitemid = 0;
        $status = 0;
        $diag_code = '';
        $disposition = '';
        $lastdate = '';
        $readdate = '';
        foreach ($parts as $partid => $part) {
            switch ($part->subtype) {
                case 'PLAIN':
                    $headers = imap_fetchheader($ih, $postid);
                    if (preg_match('/Date:\s+(?<date>.+)\r\n?/', $headers, $m)) {
                        $lastdate = strtotime($m['date']);
                    }
                    break;
                case 'DELIVERY-STATUS':
                    $body = imap_fetchbody($ih, $postid, $partid + 1);
                    if (preg_match('/Status:\s+(?<status>[0-9]+\.[0-9]+\.[0-9]+)/', $body, $m)) {
                        $code = explode('.', $m['status']);
                        $status = intval($code[0]);
                    }
                    if (preg_match('/Diagnostic-Code:\s+(?<code>.+\r\n?(?:\s+[^\s]+.+\r\n?)*)/m', $body, $m)) {
                        $diag_code = $m['code'];
                    }
                    break;
                case 'DISPOSITION-NOTIFICATION':
                    $body = imap_fetchbody($ih, $postid, $partid + 1);
                    if (preg_match('/Disposition:\s+(?<disposition>.+)\r\n?/', $body, $m)) {
                        $disposition = $m['disposition'];
                    }
                    if (preg_match('/.*Message-ID:\s+<messageitem-(?<msgitemid>[0-9]+)@.+>/', $body, $m)) {
                        $msgitemid = intval($m['msgitemid']);
                    }
                    $headers = imap_fetchheader($ih, $postid);
                    if (preg_match('/Date:\s+(?<date>.+)\r\n?/', $headers, $m)) {
                        $readdate = strtotime($m['date']);
                    }
                    break;
                case 'RFC822-HEADERS':
                case 'RFC822':
                    $body = imap_fetchbody($ih, $postid, $partid + 1);
                    if (preg_match('/X-LMS-Message-Item-Id:\s+(?<msgitemid>[0-9]+)/', $body, $m)) {
                        $msgitemid = intval($m['msgitemid']);
                    }
                    if (preg_match('/.*Message-ID:\s+<messageitem-(?<msgitemid>[0-9]+)@.+>/', $body, $m)) {
                        $msgitemid = intval($m['msgitemid']);
                    }
                    break;
            }
        }
        if (empty($msgitemid)) {
            continue;
        }
        if (!empty($status)) {
            if ($status == 4) {
                $handled_posts[] = $postid;
                continue;
            }
            switch ($status) {
                case 2:
                    $status = MSG_DELIVERED;
                    break;
                case 5:
                    $status = MSG_ERROR;
                    break;
            }
            if (empty($lastdate)) {
                $lastdate = $DB->GetOne('SELECT lastdate FROM messageitems WHERE id = ?', array($msgitemid));
            }
            $DB->Execute(
                'UPDATE messageitems SET status = ?, error = ?, lastdate = ? WHERE id = ?',
                array($status, $status == MSG_ERROR && !empty($diag_code) ? $diag_code : null,
                    $lastdate,
                $msgitemid)
            );
        } elseif (!empty($disposition) && !empty($readdate)) {
            $DB->Execute(
                'UPDATE messageitems SET status = ?, lastreaddate = ? WHERE id = ?',
                array(MSG_DELIVERED, $readdate, $msgitemid)
            );
        } else {
            continue;
        }

        $handled_posts[] = $postid;
    }
    foreach ($handled_posts as $postid) {
        imap_delete($ih, $postid);
    }
}

imap_close($ih, CL_EXPUNGE);

?>
