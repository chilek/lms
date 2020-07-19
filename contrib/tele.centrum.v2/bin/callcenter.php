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

$creatorid = ConfigHelper::getConfig('callcenter.queueuser');

ini_set('error_reporting', E_ALL&~E_NOTICE);

$CONFIG_FILE = DIRECTORY_SEPARATOR . 'etc' . DIRECTORY_SEPARATOR . 'lms' . DIRECTORY_SEPARATOR . 'lms.ini';

if (!is_readable($CONFIG_FILE)) {
    die("Unable to read configuration file [".$CONFIG_FILE."]!" . PHP_EOL);
}

define('CONFIG_FILE', $CONFIG_FILE);

$CONFIG = (array) parse_ini_file($CONFIG_FILE, true);

// Check for configuration vars and set default values
$CONFIG['directories']['sys_dir'] = (!isset($CONFIG['directories']['sys_dir']) ? getcwd() : $CONFIG['directories']['sys_dir']);
$CONFIG['directories']['lib_dir'] = (!isset($CONFIG['directories']['lib_dir']) ? $CONFIG['directories']['sys_dir'] . DIRECTORY_SEPARATOR . 'lib'
    : $CONFIG['directories']['lib_dir']);

define('SYS_DIR', $CONFIG['directories']['sys_dir']);
define('LIB_DIR', $CONFIG['directories']['lib_dir']);

$composer_autoload_path = SYS_DIR . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
if (file_exists($composer_autoload_path)) {
    require_once $composer_autoload_path;
} else {
    die("Composer autoload not found. Run 'composer install' command from LMS directory and try again. More informations at https://getcomposer.org/");
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

$hostname = "{".ConfigHelper::getConfig('callcenter.hostname')."}INBOX";
$username = ConfigHelper::getConfig('callcenter.user');
$password = ConfigHelper::getConfig('callcenter.pass');

$inbox  = imap_open($hostname, $username, $password) or die('Cannot connect to mail: ' . imap_last_error());
$emails = imap_search($inbox, 'ALL FROM "'.ConfigHelper::getConfig('callcenter.mailfrom').'"');

if ($emails) {
    foreach ($emails as $email_number) {
        $structure     = imap_fetchstructure($inbox, $email_number);
        $attachments = array();

        if (isset($structure->parts) && count($structure->parts)) {
            for ($i = 0; $i < count($structure->parts); $i++) {
                $attachments[$i] = array(
                    'is_attachment' => false,
                    'filename'         => '',
                    'name'             => '',
                    'contenttype'     => 'audio/wav',
                    'attachment'     => ''
                );

                if ($structure->parts[$i]->ifdparameters) {
                    foreach ($structure->parts[$i]->dparameters as $object) {
                        if (strtolower($object->attribute) == 'filename') {
                            $attachments[$i]['is_attachment']   = true;
                            $attachments[$i]['filename']        = $object->value;
                        }
                    }
                }
                if ($structure->parts[$i]->type==0) {
                    foreach ($structure->parts[$i]->parameters as $object) {
                        $uid = imap_fetchbody($inbox, $email_number, 1);
                        $uid = explode('<UniqueID>', $uid);
                        $uid = explode('</UniqueID>', $uid[1]);
                        $uid = $uid[0];
                    }
                }

                if ($attachments[$i]['is_attachment']) {
                    $attachments[$i]['attachment'] = imap_fetchbody($inbox, $email_number, $i+1);
                    if ($structure->parts[$i]->encoding == 3) {
                        $attachments[$i]['attachment'] = base64_decode($attachments[$i]['attachment']);
                    } elseif ($structure->parts[$i]->encoding == 4) {
                        $attachments[$i]['attachment'] = quoted_printable_decode($attachments[$i]['attachment']);
                    }
                }
            }
        }
        if (count($attachments)!=0  and !empty($uid)) {
            foreach ($attachments as $at) {
                if ($at[is_attachment] == 1  && ConfigHelper::getConfig('rt.mail_dir')) {
                    $subject = 'Zgłoszenie telefoniczne z E-Południe Call Center nr ['.$uid.']';
                    $message = $DB->GetRow('SELECT id, ticketid FROM rtmessages WHERE subject = ?', array($subject));
                    if (empty($message)) {
                        $DB->Execute(
                           'INSERT INTO rttickets (queueid, customerid, requestor, subject,
                            state, owner, createtime, cause, creatorid)
                            VALUES (?, ?, ?, ?, 0, ?, ?NOW?, ?, ?)',
                            array(
                                1,
                                0,
                                '',
                                $subject,
                                '',
                                '',
                                $creatorid
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
                                0,
                                $subject,
                                $body,
                                ''
                            )
                        );
                        $message['id'] = $DB->GetLastInsertID('rtmessages');

                        $DB->Execute('INSERT INTO rtticketcategories (ticketid, categoryid) VALUES (?, ?)', array($id, 1));

                        $message['ticketid'] = $id;
                    }
                    file_put_contents($at[filename], $at[attachment]);
                    $dir = ConfigHelper::getConfig('rt.mail_dir') . sprintf('/%06d/%06d', $message['ticketid'], $message['id']);
                    @mkdir(ConfigHelper::getConfig('rt.mail_dir') . sprintf('/%06d', $message['ticketid']), 0700);
                    @mkdir($dir, 0700);
                    $newfile = $dir . DIRECTORY_SEPARATOR . $at[filename];

                    if (@rename($at[filename], $newfile)) {
                        $DB->Execute(
                            'INSERT INTO rtattachments (messageid, filename, contenttype)
                            VALUES (?,?,?)',
                            array($message['id'], $at[filename], $at[contenttype])
                        );

                        exec("chown -R www-data:www-data ".ConfigHelper::getConfig('rt.mail_dir') . sprintf('/%06d', $message['ticketid'])."");
                        imap_delete($inbox, $email_number);
                    }
                }
            }
        }
    }
}

imap_close($inbox, CL_EXPUNGE);
