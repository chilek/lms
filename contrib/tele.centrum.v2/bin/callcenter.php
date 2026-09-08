#!/usr/bin/env php
<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2022 LMS Developers
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

// REPLACE THIS WITH PATH TO YOUR CONFIG FILE

// PLEASE DO NOT MODIFY ANYTHING BELOW THIS LINE UNLESS YOU KNOW
// *EXACTLY* WHAT ARE YOU DOING!!!
// *******************************************************************

ini_set('error_reporting', E_ALL & ~E_NOTICE & ~E_DEPRECATED);

$parameters = array(
    'config-file:' => 'C:',
    'quiet' => 'q',
    'help' => 'h',
    'version' => 'v',
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

if (array_key_exists('version', $options)) {
    print <<<EOF
callcenter.php
(C) 2001-2022 LMS Developers

EOF;
    exit(0);
}

if (array_key_exists('help', $options)) {
    print <<<EOF
callcenter.php
(C) 2001-2022 LMS Developers

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
callcenter.php
(C) 2001-2022 LMS Developers

EOF;
}

if (array_key_exists('config-file', $options)) {
    $CONFIG_FILE = $options['config-file'];
} else {
    $CONFIG_FILE = DIRECTORY_SEPARATOR . 'etc' . DIRECTORY_SEPARATOR . 'lms' . DIRECTORY_SEPARATOR . 'lms.ini';
}

if (!$quiet) {
    echo "Using file " . $CONFIG_FILE . " as config." . PHP_EOL;
}

if (!is_readable($CONFIG_FILE)) {
    die('Unable to read configuration file [' . $CONFIG_FILE . ']!');
}

define('CONFIG_FILE', $CONFIG_FILE);

$CONFIG = (array) parse_ini_file($CONFIG_FILE, true);

// Check for configuration vars and set default values
$CONFIG['directories']['sys_dir'] = (!isset($CONFIG['directories']['sys_dir']) ? getcwd() : $CONFIG['directories']['sys_dir']);
$CONFIG['directories']['lib_dir'] = (!isset($CONFIG['directories']['lib_dir']) ? $CONFIG['directories']['sys_dir'] . DIRECTORY_SEPARATOR . 'lib'
    : $CONFIG['directories']['lib_dir']);
$CONFIG['directories']['storage_dir'] = (!isset($CONFIG['directories']['storage_dir']) ? $CONFIG['directories']['sys_dir'] . DIRECTORY_SEPARATOR . 'storage' : $CONFIG['directories']['storage_dir']);

define('SYS_DIR', $CONFIG['directories']['sys_dir']);
define('LIB_DIR', $CONFIG['directories']['lib_dir']);
define('STORAGE_DIR', $CONFIG['directories']['storage_dir']);

$composer_autoload_path = SYS_DIR . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
if (file_exists($composer_autoload_path)) {
    require_once $composer_autoload_path;
} else {
    die("Composer autoload not found. Run 'composer install' command from LMS directory and try again. More information at https://getcomposer.org/");
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

/* ****************************************
  Good place for config value analysis
 ****************************************/

// Include required files (including sequence is important)

require_once(LIB_DIR . DIRECTORY_SEPARATOR . 'common.php');
require_once(LIB_DIR . DIRECTORY_SEPARATOR . 'language.php');
include_once(LIB_DIR . DIRECTORY_SEPARATOR . 'definitions.php');

$SYSLOG = SYSLOG::getInstance();

// Initialize Session, Auth and LMS classes

$AUTH = null;
$LMS = new LMS($DB, $AUTH, $SYSLOG);

/* CODE */

$user = ConfigHelper::getConfig('callcenter.default_user');
if (!empty($user)) {
    $userid = $LMS->getUserIDByLogin($user);
    if (empty($userid)) {
        if (!preg_match('/^[0-9]+$/', $user) || !$LMS->userExists($user)) {
            $user = null;
        }
    } else {
        $user = $userid;
    }
}

$queue = ConfigHelper::getConfig('callcenter.default_queue');
if (empty($queue)) {
    echo 'Warning: missed \'default_queue\' configuration variable!' . PHP_EOL;
}
$queueid = $LMS->GetQueueIdByName($queue);
if (empty($queueid)) {
    if (!preg_match('/^[0-9]+$/', $queue) || !$LMS->QueueExists($queue)) {
        echo 'Warning: couldn\'t find default queue!' . PHP_EOL;
        $queue = null;
    }
} else {
    $queue = $queueid;
}

$category = ConfigHelper::getConfig('callcenter.default_category');
$categoryid = $LMS->GetCategoryIdByName($category);
if (empty($categoryid)) {
    if (!preg_match('/^[0-9]+$/', $category) || !$LMS->CategoryExists($category)) {
        echo 'Warning: couldn\'t find default category!' . PHP_EOL;
        $category = null;
    }
} else {
    $category = $categoryid;
}

$folder = ConfigHelper::getConfig('callcenter.folder', 'INBOX');
$hostname = "{" . ConfigHelper::getConfig('callcenter.server', ConfigHelper::getConfig('callcenter.hostname')) . "}" . $folder;
$username = ConfigHelper::getConfig('callcenter.username', ConfigHelper::getConfig('callcenter.user'));
$password = ConfigHelper::getConfig('callcenter.password', ConfigHelper::getConfig('callcenter.pass'));

$rt_dir = STORAGE_DIR . DIRECTORY_SEPARATOR . 'rt';
$storage_dir_permission = intval(ConfigHelper::getConfig('storage.dir_permission', ConfigHelper::getConfig('rt.mail_dir_permission', '0700')), 8);
$storage_dir_owneruid = ConfigHelper::getConfig('storage.dir_owneruid', 'root');
$storage_dir_ownergid = ConfigHelper::getConfig('storage.dir_ownergid', 'root');

$rtmessages_extid_exists = $DB->ResourceExists('rtmessages.extid', LMSDB::RESOURCE_TYPE_COLUMN);

$inbox = @imap_open($hostname, $username, $password);
if ($inbox === false) {
    die('Cannot connect to mail server: ' . imap_last_error() . '!' . PHP_EOL);
}

$emails = imap_search($inbox, 'ALL FROM "' . ConfigHelper::getConfig('callcenter.sender_email', ConfigHelper::getConfig('callcenter.mailfrom')) . '"');

if (!empty($emails)) {
    $finfo = new finfo(FILEINFO_MIME_TYPE);

    foreach ($emails as $email_number) {
        $structure     = imap_fetchstructure($inbox, $email_number);
        $attachments = array();
        $uid = null;

        if (isset($structure->parts) && count($structure->parts)) {
            for ($i = 0; $i < count($structure->parts); $i++) {
                $attachment = array(
                    'is_attachment' => false,
                    'filename' => '',
                    'name' => '',
                    'contenttype' => 'audio/wav',
                    'attachment' => ''
                );

                if ($structure->parts[$i]->ifdparameters) {
                    foreach ($structure->parts[$i]->dparameters as $object) {
                        if (strtolower($object->attribute) == 'filename') {
                            $attachment['is_attachment'] = true;
                            $attachment['filename'] = $object->value;
                        }
                    }
                }
                if (!$structure->parts[$i]->type) {
                    foreach ($structure->parts[$i]->parameters as $object) {
                        $content = imap_fetchbody($inbox, $email_number, 1);
                        foreach (preg_split('/[\n\r]/', $content, -1, PREG_SPLIT_NO_EMPTY) as $line) {
                            $line = trim($line);
                            if (stripos($line, '<UniqueID>') !== false
                                && preg_match('/^<UniqueID>(?<uniqueid>[0-9]+(?:\.[0-9]+)?)<\/UniqueID>/', $line, $m)) {
                                $uid = $m['uniqueid'];
                            }
                        }
                    }
                }

                if ($attachment['is_attachment']) {
                    $attachment['attachment'] = imap_fetchbody($inbox, $email_number, $i+1);
                    if ($structure->parts[$i]->encoding == 3) {
                        $attachment['attachment'] = base64_decode($attachment['attachment']);
                    } elseif ($structure->parts[$i]->encoding == 4) {
                        $attachment['attachment'] = quoted_printable_decode($attachment['attachment']);
                    }

                    $mime = $finfo->buffer($attachment['attachment']);
                    $attachment['contenttype'] = $mime === false ? 'application/octet-stream' : $mime;

                    $attachments[] = $attachment;
                }
            }
        }

        if (count($attachments) && isset($uid)) {
            foreach ($attachments as $at) {
                if ($at['is_attachment'] && !empty($rt_dir)) {
                    $subject = 'Zgłoszenie telefoniczne z E-Południe Call Center nr [' . $uid . ']';
                    if ($rtmessages_extid_exists) {
                        $message = $DB->GetRow('SELECT id, ticketid FROM rtmessages WHERE extid = ?', array($uid));
                    } else {
                        $message = null;
                    }
                    if (empty($message)) {
                        $message = $DB->GetRow('SELECT id, ticketid FROM rtmessages WHERE subject = ?', array($subject));
                    }
                    if (empty($message)) {
                        if (empty($queue)) {
                            die('Fatal error: missed \'default_queue\' configuration variable!' . PHP_EOL);
                        }

                        $DB->Execute(
                            'INSERT INTO rttickets (queueid, customerid, requestor, subject,
                            state, owner, createtime, cause, source, creatorid)
                            VALUES (?, ?, ?, ?, ?, ?, ?NOW?, ?, ?, ?)',
                            array(
                                $queue,
                                null,
                                '',
                                $subject,
                                RT_NEW,
                                null,
                                RT_CAUSE_OTHER,
                                RT_SOURCE_CALLCENTER,
                                $user,
                            )
                        );
                        $id = $DB->GetLastInsertID('rttickets');

                        $body = 'Nie znaleziono zgłoszenia w bazie danych.' . PHP_EOL . 'Dołaczono nagranie.';
                        $DB->Execute(
                            'INSERT INTO rtmessages (ticketid, customerid, createtime,
                            subject, body, mailfrom)
                            VALUES (?, ?, ?NOW?, ?, ?, ?)',
                            array(
                                $id,
                                null,
                                $subject,
                                $body,
                                '',
                            )
                        );
                        $message['id'] = $DB->GetLastInsertID('rtmessages');

                        if (!empty($category)) {
                            $DB->Execute(
                                'INSERT INTO rtticketcategories (ticketid, categoryid) VALUES (?, ?)',
                                array(
                                    $id,
                                    $category,
                                )
                            );
                        }

                        $message['ticketid'] = $id;
                    }
                    file_put_contents($at['filename'], $at['attachment']);

                    $ticket_dir = $rt_dir . DIRECTORY_SEPARATOR . sprintf('%06d', $message['ticketid']);
                    $message_dir = $ticket_dir . DIRECTORY_SEPARATOR . sprintf('%06d', $message['id']);

                    @umask(0007);
                    @mkdir($ticket_dir, $storage_dir_permission);
                    @chown($ticket_dir, $storage_dir_owneruid);
                    @chgrp($ticket_dir, $storage_dir_ownergid);
                    @mkdir($message_dir, $storage_dir_permission);
                    @chown($message_dir, $storage_dir_owneruid);
                    @chgrp($message_dir, $storage_dir_ownergid);

                    $newfile = $message_dir . DIRECTORY_SEPARATOR . $at['filename'];

                    if (@rename($at['filename'], $newfile)) {
                        @chown($newfile, $storage_dir_owneruid);
                        @chgrp($newfile, $storage_dir_ownergid);

                        $DB->Execute(
                            'INSERT INTO rtattachments (messageid, filename, contenttype)
                            VALUES (?, ?, ?)',
                            array(
                                $message['id'],
                                $at['filename'],
                                $at['contenttype'],
                            )
                        );

                        imap_delete($inbox, $email_number);
                    }
                }
            }
        }
    }
}

imap_close($inbox, CL_EXPUNGE);
