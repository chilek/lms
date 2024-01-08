#!/usr/bin/env php
<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2021 LMS Developers
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

$parameters = array(
    'config-file:' => 'C:',
    'quiet' => 'q',
    'help' => 'h',
    'version' => 'v',
    'section:' => 's:',
);

$long_to_shorts = array();
foreach ($parameters as $long => $short) {
    $long = str_replace(':', '', $long);
    if (isset($short)) {
        $short = str_replace(':', '', $short);
    }
    $long_to_shorts[$long] = $short;
}

$options = getopt(
    implode(
        '',
        array_filter(
            array_values($parameters),
            function ($value) {
                return isset($value);
            }
        )
    ),
    array_keys($parameters)
);

foreach (array_flip(array_filter($long_to_shorts, function ($value) {
    return isset($value);
})) as $short => $long) {
    if (array_key_exists($short, $options)) {
        $options[$long] = $options[$short];
        unset($options[$short]);
    }
}

if (isset($options['version'])) {
    print <<<EOF
lms-dsn-handler.php
(C) 2001-2021 LMS Developers

EOF;
    exit(0);
}

if (isset($options['help'])) {
    print <<<EOF
lms-dsn-handler.php
(C) 2001-2021 LMS Developers

-C, --config-file=/etc/lms/lms.ini      alternate config file (default: /etc/lms/lms.ini);
-h, --help                      print this help and exit;
-v, --version                   print version info and exit;
-q, --quiet                     suppress any output, except errors;
-s, --section=<section-name>    configuration section name where settings
                                are stored

EOF;
    exit(0);
}

$quiet = isset($options['quiet']);
if (!$quiet) {
    print <<<EOF
lms-dsn-handler.php
(C) 2001-2021 LMS Developers

EOF;
}

if (isset($options['config-file'])) {
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
include_once(LIB_DIR . DIRECTORY_SEPARATOR . 'definitions.php');
/*
$SYSLOG = SYSLOG::getInstance();
*/

$config_section = isset($options['section']) && preg_match('/^[a-z0-9-_]+$/i', $options['section'])
    ? $options['section']
    : 'dsn-handler';

$ih = @imap_open(
    '{' . ConfigHelper::getConfig($config_section . '.server') . '}' . ConfigHelper::getConfig($config_section . '.folder', 'INBOX'),
    ConfigHelper::getConfig($config_section . '.username'),
    ConfigHelper::getConfig($config_section . '.password')
);
if (!$ih) {
    die("Cannot connect to mail server!" . PHP_EOL);
}

$month_mappings = array(
    'sty' => 1,
    'lut' => 2,
    'mar' => 3,
    'kwi' => 4,
    'maj' => 5,
    'cze' => 6,
    'lip' => 7,
    'sie' => 8,
    'wrz' => 9,
    'paź' => 10,
    'lis' => 11,
    'gru' => 12,
);

$handled_posts = array();
$posts = imap_search($ih, 'ALL');
if (!empty($posts)) {
    foreach ($posts as $postid) {
        $post = imap_fetchstructure($ih, $postid);
        $headers = imap_fetchheader($ih, $postid);
        if ($post->subtype == 'MIXED' || $post->subtype == 'HTML') {
            $subject = $readdate = $sender = $orig_date = null;

            if (empty($post->parts)) {
                $body = imap_fetchbody($ih, $postid, 1);

                $charset = 'UTF-8';
                if (!empty($post->parameters)) {
                    foreach ($post->parameters as $parameter) {
                        if ($parameter->attribute == 'charset') {
                            $charset = $parameter->value;
                        }
                    }
                }

                switch ($post->encoding) {
                    case 3:
                        $body = base64_decode($body);
                        break;
                    case 4:
                        $body = quoted_printable_decode($body);
                        break;
                }

                if ($charset != 'UTF-8') {
                    $body = iconv($charset, 'UTF-8', $body);
                }

                if ($post->subtype == 'HTML') {
                    // X-Mailer: OnetMailer
                    // From: "Poczta w Onet.pl" <komunikaty@onet.pl>
                    if (preg_match('/Subject:[[:blank:]]+(?<subject>.+\r?\n(?:[[:blank:]].+\r?\n)*)/s', $headers, $m)) {
                        $subject = iconv_mime_decode($m['subject']);
                        if (!preg_match('/^Potwierdzenie (odczytania|otrzymania) wiadomości/', $subject)) {
                            continue;
                        }
                    }
                    if (preg_match('/Date:[[:blank:]]+(?<date>.+)\r\n?/', $headers, $m)) {
                        $readdate = strtotime($m['date']);
                    } else {
                        continue;
                    }
                    foreach (preg_split('/\r?\n/', $body) as $line) {
                        if (preg_match('#^Potwierdzenie odczytania wiadomości <br[[:blank:]]*> wysłanej do <b>(?<sender>[a-z0-9_\.-]+@[\da-z\.-]+\.[a-z\.]{2,6})</b> o godzinie <b>(?<date>[0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2})</b>#', $line, $m)) {
                            $sender = $m['sender'];
                            $orig_date = strtotime($m['date']);
                        }
                    }
                }
            } else {
                $parts = $post->parts;
                foreach ($parts as $partid => $part) {
                    if (!empty($part->ifsubtype) && $part->subtype == 'ALTERNATIVE') {
                        foreach ($part->parts as $partid2 => $part2) {
                            if (!empty($part2->ifsubtype) && $part2->subtype == 'PLAIN') {
                                // User-Agent: Poczta o2
                                //   lub
                                // User-Agent: Poczta Wirtualnej Polski
                                if (preg_match('/Subject:[[:blank:]]+(?<subject>.+\r?\n(?:[[:blank:]].+\r?\n)*)/s', $headers, $m)) {
                                    $subject = iconv_mime_decode($m['subject']);
                                    if (!preg_match('/^Potwierdzenie odbioru:[[:blank:]]*/', $subject)) {
                                        break 2;
                                    }
                                }
                                if (preg_match('/Date:[[:blank:]]+(?<date>.+)\r?\n/', $headers, $m)) {
                                    $readdate = strtotime($m['date']);
                                } else {
                                    break 2;
                                }
                                if (preg_match('/From:[[:blank:]]+(?<from>.+\r?\n(?:[[:blank:]].+\r?\n)*)/s', $headers, $m)) {
                                    $sender = iconv_mime_decode($m['from']);
                                    if (!preg_match('/^(?:(?<name>.*) )?<?(?<mail>[a-z0-9_\.-]+@[\da-z\.-]+\.[a-z\.]{2,6})>?$/iA', $sender, $m)) {
                                        break 2;
                                    }
                                    $sender = $m['mail'];
                                } else {
                                    break 2;
                                }
                                $body = imap_fetchbody($ih, $postid, ($partid + 1) . '.' . ($partid2 + 1));

                                $charset = 'UTF-8';
                                if (!empty($part2->parameters)) {
                                    foreach ($part2->parameters as $parameter) {
                                        if ($parameter->attribute == 'charset') {
                                            $charset = $parameter->value;
                                        }
                                    }
                                }

                                switch ($part2->encoding) {
                                    case 3:
                                        $body = base64_decode($body);
                                        break;
                                    case 4:
                                        $body = quoted_printable_decode($body);
                                        break;
                                }

                                if ($charset != 'UTF-8') {
                                    $body = iconv($charset, 'UTF-8', $body);
                                }

                                foreach (preg_split('/\r?\n/', $body) as $line) {
                                    if (preg_match('/To jest potwierdzenie dla wiadomości wysłanej przez Ciebie do.+[[:blank:]]+(?<mday>[0-9]+)[[:blank:]]+(?<mname>[[:alpha:]]+)[[:blank:]]+(?<year>[0-9]{4})[[:blank:]]+o[[:blank:]]+godz\.[[:blank:]]+(?<hour>[0-9]{2}):(?<minute>[0-9]{2})\./u', $line, $m)) {
                                        $month_mapping_index = mb_substr($m['mname'], 0, 3);
                                        if (!isset($month_mappings[$month_mapping_index])) {
                                            break 3;
                                        }
                                        $orig_date = mktime($m['hour'], $m['minute'], 0, $month_mappings[$month_mapping_index], $m['mday'], $m['year']);
                                    }
                                }
                            }
                        }
                    }
                }
            }

            if (!isset($orig_date)) {
                continue;
            }

            $msgitemid = $DB->GetOne(
                'SELECT mi.id
                FROM messageitems mi
                JOIN messages m ON m.id = mi.messageid
                WHERE m.type = ?
                    AND m.cdate >= ? - 60
                    AND m.cdate <= ? + 60
                    AND mi.destination = ?
                LIMIT 1',
                array(
                    MSG_MAIL,
                    $orig_date,
                    $orig_date,
                    $sender,
                )
            );

            if (!empty($msgitemid)) {
                $DB->Execute(
                    'UPDATE messageitems SET status = ?, lastreaddate = ? WHERE id = ?',
                    array(MSG_DELIVERED, $readdate, $msgitemid)
                );

                $handled_posts[] = $postid;
            }

            continue;
        } elseif ($post->subtype != 'REPORT') {
            continue;
        }
        if ((count($post->parts) < 2 || count($post->parts) > 3) && preg_match('/In-Reply-To:[[:blank:]]+<messageitem-(?<msgitemid>[0-9]+)@.+>/', $headers) === 0) {
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
                    if (preg_match('/Date:[[:blank:]]+(?<date>.+)\r\n?/', $headers, $m)) {
                        $lastdate = strtotime($m['date']);
                    }
                    break;
                case 'DELIVERY-STATUS':
                    $body = imap_fetchbody($ih, $postid, $partid + 1);
                    if (preg_match('/Status:[[:blank:]]+(?<status>[0-9]+\.[0-9]+\.[0-9]+)/', $body, $m)) {
                        $code = explode('.', $m['status']);
                        $status = intval($code[0]);
                    }
                    if (preg_match('/Diagnostic-Code:[[:blank:]]+(?<code>.+\r\n?(?:\s+[^\s]+.+\r\n?)*)/m', $body, $m)) {
                        $diag_code = $m['code'];
                    }
                    break;
                case 'DISPOSITION-NOTIFICATION':
                    $body = imap_fetchbody($ih, $postid, $partid + 1);
                    if (preg_match('/Disposition:[[:blank:]]+(?<disposition>.+)\r\n?/', $body, $m)) {
                        $disposition = $m['disposition'];
                    }
                    if (preg_match('/.*Message-ID:[[:blank:]]+<messageitem-(?<msgitemid>[0-9]+)@.+>/', $body, $m)) {
                        $msgitemid = intval($m['msgitemid']);
                    }
                    $headers = imap_fetchheader($ih, $postid);
                    if (preg_match('/Date:[[:blank:]]+(?<date>.+)\r\n?/', $headers, $m)) {
                        $readdate = strtotime($m['date']);
                    }
                    break;
                case 'RFC822-HEADERS':
                case 'RFC822':
                    $body = imap_fetchbody($ih, $postid, $partid + 1);
                    if (preg_match('/X-LMS-Message-Item-Id:[[:blank:]]+(?<msgitemid>[0-9]+)/', $body, $m)) {
                        $msgitemid = intval($m['msgitemid']);
                    }
                    if (preg_match('/.*Message-ID:[[:blank:]]+<messageitem-(?<msgitemid>[0-9]+)@.+>/', $body, $m)) {
                        $msgitemid = intval($m['msgitemid']);
                    }
                    break;
                case 'HTML':
                    $headers = imap_fetchheader($ih, $postid);
                    if (preg_match('/Content-Type: .*report.+/', $headers) === 0) {
                        break;
                    }
                    if (preg_match('/Date:[[:blank:]]+(?<date>.+)\r\n?/', $headers, $m)) {
                        $readdate = strtotime($m['date']);
                    }
                    if (preg_match('/In-Reply-To:[[:blank:]]+<messageitem-(?<msgitemid>[0-9]+)@.+>/', $headers, $m)) {
                        $msgitemid = intval($m['msgitemid']);
                    }
                    if (preg_match('/.*report-type=(?<disposition>disposition-notification).+/', $headers, $m)) {
                        $disposition = $m['disposition'];
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
