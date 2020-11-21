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
    'config-file:' => 'C:',
    'silent' => 's',
    'help' => 'h',
    'version' => 'v',
    'queue:' => 'q:',
    'message-file:' => 'm:',
    'use-html' => null,
    'imap' => null,
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
lms-rtparser.php
(C) 2001-2020 LMS Developers

EOF;
    exit(0);
}

if (array_key_exists('help', $options)) {
    print <<<EOF
lms-rtparser.php
(C) 2001-2020 LMS Developers

-C, --config-file=/etc/lms/lms.ini      alternate config file (default: /etc/lms/lms.ini);
-h, --help                      print this help and exit;
-v, --version                   print version info and exit;
-s, --silent                    suppress any output, except errors;
-q, --queue=<queueid>           queue ID (it means, QUEUE ID, numeric! NOT NAME! also
                                its required to run!);
-m, --message-file=<messag-file>
                                use message file instead of standard input;
    --use-html                  use html content type and load it to database if it's present
    --imap
                                fetch posts using imap protocol

EOF;
    exit(0);
}

$quiet = array_key_exists('silent', $options);
if (!$quiet) {
    print <<<EOF
lms-rtparser.php
(C) 2001-2020 LMS Developers

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

$SYSLOG = SYSLOG::getInstance();

// Initialize Session, Auth and LMS classes

$AUTH = null;
$LMS = new LMS($DB, $AUTH, $SYSLOG);

$hostname = gethostname();
if (empty($hostname)) {
    $hostname = 'example.com';
}

$smtp_options = $LMS->GetRTSmtpOptions();

$queue = 0;
if (isset($options['queue'])) {
    $queue = $options['queue'];
}
$queue = ConfigHelper::getConfig('rt.default_queue', $queue);
if (preg_match('/^[0-9]+$/', $queue)) {
    $queue = intval($queue);
    if ($queue && !$LMS->QueueExists($queue)) {
        $queue = 0;
    }
} else {
    $queue = $LMS->GetQueueIdByName($queue);
}
$categories = ConfigHelper::getConfig('rt.default_categories', 'default');
$categories = preg_split('/\s*,\s*/', trim($categories));
$auto_open = ConfigHelper::checkValue(ConfigHelper::getConfig('rt.auto_open', '0'));
//$tmp_dir = ConfigHelper::getConfig('rt.tmp_dir', '', true);
$notify = ConfigHelper::checkValue(ConfigHelper::getConfig('rt.newticket_notify', '0'));
$customerinfo = ConfigHelper::checkValue(ConfigHelper::getConfig('rt.include_customerinfo', '1'));
$lms_url = ConfigHelper::getConfig('rt.lms_url', 'http://localhost/lms/');
$autoreply_from = ConfigHelper::getConfig('rt.mail_from', '', true);
$autoreply_name = ConfigHelper::getConfig('rt.mail_from_name', '', true);
$autoreply_subject = ConfigHelper::getConfig('rt.autoreply_subject', "[RT#%tid] Receipt of request '%subject'");
$autoreply_body = ConfigHelper::getConfig('rt.autoreply_body', '', true);
$autoreply = ConfigHelper::checkValue(ConfigHelper::getConfig('rt.autoreply', '1'));
$subject_ticket_regexp_match = ConfigHelper::getConfig('rt.subject_ticket_regexp_match', 'RT#(?<ticketid>[0-9]{6,})');

$image_max_size = ConfigHelper::getConfig('phpui.uploaded_image_max_size');

$rtparser_server = ConfigHelper::getConfig('rt.imap_server');
$rtparser_username = ConfigHelper::getConfig('rt.imap_username');
$rtparser_password = ConfigHelper::getConfig('rt.imap_password');
$rtparser_use_seen_flag = ConfigHelper::checkValue(ConfigHelper::getConfig('rt.imap_use_seen_flag', true));
$rtparser_folder = ConfigHelper::getConfig('rt.imap_folder', 'INBOX');

$url_props = parse_url($lms_url);

$stderr = fopen('php://stderr', 'w');

$use_html = isset($options['use-html']);

define('MODE_FILE', 1);
define('MODE_IMAP', 2);

$mode = isset($options['imap']) ? MODE_IMAP : MODE_FILE;

if ($smtp_options['auth'] && !preg_match('/^(LOGIN|PLAIN|CRAM-MD5|NTLM)$/i', $smtp_options['auth'])) {
    fprintf($stderr, "Fatal error: smtp_auth setting not supported! Can't continue, exiting." . PHP_EOL);
    exit(1);
}

if (!$autoreply_body) {
    $autoreply_body = "Your request was registered in our system.\n"
        . "To this request was assigned ticket identifier: RT#%tid\n\n"
        . "Please, place string [RT#%tid] in subject field of any\n"
        . "mail relating to this request.\n";
}
$autoreply_body = str_replace("\\n", "\n", $autoreply_body);

if (!function_exists('mailparse_msg_create')) {
    fprintf($stderr, "Fatal error: PECL mailparse module is required!" . PHP_EOL);
    exit(2);
}

class HTMLPurifier_URIScheme_cid extends \HTMLPurifier_URIScheme
{
    public $browsable = true;
    public $may_omit_host = true;

    public function doValidate(&$uri, $config, $context)
    {
        return true;
    }
}

if ($use_html) {
    $hm_config = HTMLPurifier_Config::createDefault();
    $hm_config->set('URI.AllowedSchemes', array(
        'http' => true,
        'https' => true,
        'mailto' => true,
        'ftp' => true,
        'nntp' => true,
        'news' => true,
        'tel' => true,
        'cid' => true,
    ));
    $hm_config->set('CSS.MaxImgLength', null);
    $hm_config->set('HTML.MaxImgLength', null);
    HTMLPurifier_URISchemeRegistry::instance()->register('cid', new HTMLPurifier_URIScheme_cid());
    $hm_purifier = new HTMLPurifier($hm_config);
}

$postid = null;

if ($mode == MODE_IMAP) {
    if (!function_exists('imap_open')) {
        fprintf($stderr, "Fatal error: PHP IMAP extension is required!" . PGP_EOL);
        exit(5);
    }

    if (empty($rtparser_server) || empty($rtparser_username) || empty($rtparser_password)) {
        fprintf($stderr, "Fatal error: mailbox credentials are not set!" . PHP_EOL);
        exit(6);
    }

    $ih = @imap_open("{" . $rtparser_server . "}" . $rtparser_folder, $rtparser_username, $rtparser_password);
    if (!$ih) {
        fprintf($stderr, "Cannot connect to mail server!" . PHP_EOL);
        exit(7);
    }

    $posts = imap_search($ih, $rtparser_use_seen_flag ? 'UNSEEN' : 'ALL');
    if (empty($posts)) {
        imap_close($ih);
        die;
    }

    $postid = reset($posts);
} else {
    if (isset($options['message-file'])) {
        if (!is_readable($options['message-file'])) {
            die('Cannot read message file \'' . $options['message-file'] . '\'!' . PHP_EOL);
        }
        $buffer = file_get_contents($options['message-file']);
    } else {
        $buffer = file_get_contents('php://stdin');
    }
}

while (isset($buffer) || $postid !== false) {
    if ($postid !== false) {
        $buffer = imap_fetchbody($ih, $postid, '');
    }

    if (!empty($buffer)) {
        if (!preg_match('/\r?\n$/', $buffer)) {
            $buffer .= "\n";
        }

        $mail = mailparse_msg_create();
        if ($mail === false) {
            fprintf($stderr, "Fatal error: mailparse_msg_create() error!" . PHP_EOL);
            exit(3);
        }

        if (mailparse_msg_parse($mail, $buffer) === false) {
            fprintf($stderr, "Fatal error: mailparse_msg_parse() error!" . PHP_EOL);
            exit(4);
        }

        $parts = mailparse_msg_get_structure($mail);
        $partid = array_shift($parts);
        $part = mailparse_msg_get_part($mail, $partid);
        $partdata = mailparse_msg_get_part_data($part);
        $headers = $partdata['headers'];

        $mh_from = iconv_mime_decode($headers['from']);
        $mh_to = iconv_mime_decode($headers['to']);
        $mh_cc = isset($headers['cc']) ? iconv_mime_decode($headers['cc']) : '';
        $mh_msgid = iconv_mime_decode($headers['message-id']);
        $mh_replyto = isset($headers['reply-to']) ? iconv_mime_decode($headers['reply-to']) : '';
        $mh_subject = isset($headers['subject']) ? iconv_mime_decode($headers['subject']) : '';
        if (!strlen($mh_subject)) {
            $mh_subject = trans('(no subject)');
        }
        $mh_references = iconv_mime_decode($headers['references']);
        $files = array();
        $attachments = array();

        $mail_headers = substr($buffer, $partdata['starting-pos'], $partdata['starting-pos-body'] - $partdata['starting-pos'] - 1);
        $decoded_mail_headers = array();
        foreach (explode("\n", $mail_headers) as $mail_header) {
            $decoded_mail_header = @iconv_mime_decode($mail_header);
            if ($decoded_mail_header === false) {
                $decoded_mail_headers[] = $mail_header;
            } else {
                $decoded_mail_headers[] = $decoded_mail_header;
            }
        }
        $mail_headers = implode("\n", $decoded_mail_headers);
        unset($decoded_mail_headers);

        if (preg_match('#multipart/#', $partdata['content-type']) && !empty($parts)) {
            $mail_body = '';
            while (!empty($parts)) {
                $partid = array_shift($parts);
                $part = mailparse_msg_get_part($mail, $partid);
                $partdata = mailparse_msg_get_part_data($part);
                if (preg_match('/text/', $partdata['content-type']) && ($use_html || $mail_body == '')) {
                    $mail_body = substr($buffer, $partdata['starting-pos-body'], $partdata['ending-pos-body'] - $partdata['starting-pos-body']);
                    $charset = $partdata['content-charset'];
                    $transfer_encoding = isset($partdata['transfer-encoding']) ? $partdata['transfer-encoding'] : '';
                    switch ($transfer_encoding) {
                        case 'base64':
                            $mail_body = base64_decode($mail_body);
                            break;
                        case 'quoted-printable':
                            $mail_body = quoted_printable_decode($mail_body);
                            break;
                    }
                    $mail_body = iconv($charset, 'UTF-8', $mail_body);

                    $contenttype = 'text/plain';

                    if ($partdata['content-type'] == 'text/html') {
                        if ($use_html) {
                            $contenttype = 'text/html';
                            $mail_body = $hm_purifier->purify($mail_body);
                        } else {
                            $html2text = new \Html2Text\Html2Text($mail_body, array());
                            $mail_body = $html2text->getText();
                        }
                    }
                } elseif (preg_match('#multipart/alternative#', $partdata['content-type']) && $mail_body == '') {
                    while (!empty($parts) && strpos($parts[0], $partid . '.') === 0) {
                        $subpartid = array_shift($parts);
                        $subpart = mailparse_msg_get_part($mail, $subpartid);
                        $subpartdata = mailparse_msg_get_part_data($subpart);
                        if (preg_match('/text/', $subpartdata['content-type']) && ($use_html || trim($mail_body) == '')) {
                            $mail_body = substr($buffer, $subpartdata['starting-pos-body'], $subpartdata['ending-pos-body'] - $subpartdata['starting-pos-body']);
                            $charset = $subpartdata['content-charset'];
                            $transfer_encoding = isset($subpartdata['transfer-encoding']) ? $subpartdata['transfer-encoding'] : '';
                            switch ($transfer_encoding) {
                                case 'base64':
                                    $mail_body = base64_decode($mail_body);
                                    break;
                                case 'quoted-printable':
                                    $mail_body = quoted_printable_decode($mail_body);
                                    break;
                            }
                            $mail_body = iconv($charset, 'UTF-8', $mail_body);

                            $contenttype = 'text/plain';

                            if ($subpartdata['content-type'] == 'text/html') {
                                if ($use_html) {
                                    $contenttype = 'text/html';
                                    $mail_body = $hm_purifier->purify($mail_body);
                                } else {
                                    $html2text = new \Html2Text\Html2Text($mail_body, array());
                                    $mail_body = $html2text->getText();
                                }
                            }
                        }
                    }
                } elseif ((isset($partdata['content-disposition']) && ($partdata['content-disposition'] == 'attachment'
                            || $partdata['content-disposition'] == 'inline')) || isset($partdata['content-id'])) {
                    $file_content = substr($buffer, $partdata['starting-pos-body'], $partdata['ending-pos-body'] - $partdata['starting-pos-body']);
                    $transfer_encoding = isset($partdata['transfer-encoding']) ? $partdata['transfer-encoding'] : '';
                    switch ($transfer_encoding) {
                        case 'base64':
                            $file_content = base64_decode($file_content);
                            break;
                        case 'quoted-printable':
                            $file_content = quoted_printable_decode($file_content);
                            break;
                    }
                    $file_name = isset($partdata['content-name']) ? $partdata['content-name'] :
                        (isset($partdata['disposition-filename']) ? $partdata['disposition-filename'] : '');
                    if (!$file_name) {
                        unset($file_content);
                        continue;
                    }
                    $file_name = iconv_mime_decode($file_name);

                    if (!isset($partdata['content-id']) && $image_max_size && class_exists('Imagick') && strpos($partdata['content-type'], 'image/') === 0) {
                        $imagick = new \Imagick();
                        $imagick->readImageBlob($file_content);
                        $width = $imagick->getImageWidth();
                        $height = $imagick->getImageHeight();
                        if ($height > $width) {
                            if ($height > $image_max_size) {
                                $imagick->scaleImage(0, $image_max_size);
                                $file_content = $imagick->getImageBlob();
                            }
                        } else {
                            if ($width > $image_max_size) {
                                $imagick->scaleImage($image_max_size, 0);
                                $file_content = $imagick->getImageBlob();
                            }
                        }
                    }

                    $files[] = array(
                        'name' => $file_name,
                        'type' => $partdata['content-type'],
                        'content' => &$file_content,
                        'content-id' => isset($partdata['content-id']) ? $partdata['content-id'] : null,
                    );
                    $attachments[] = array(
                        'content_type' => $partdata['content-type'],
                        'filename' => $file_name,
                        'data' => &$file_content,
                        'content-id' => isset($partdata['content-id']) ? $partdata['content-id'] : null,
                    );
                    unset($file_content);
                }
            }
        } else {
            $charset = $partdata['content-charset'];
            $mail_body = substr($buffer, $partdata['starting-pos-body'], $partdata['ending-pos-body'] - $partdata['starting-pos-body']);

            $transfer_encoding = isset($partdata['transfer-encoding']) ? $partdata['transfer-encoding'] : '';
            switch ($transfer_encoding) {
                case 'base64':
                    $mail_body = base64_decode($mail_body);
                    break;
                case 'quoted-printable':
                    $mail_body = quoted_printable_decode($mail_body);
                    break;
            }

            $mail_body = iconv($charset, 'UTF-8', $mail_body);

            $contenttype = 'text/plain';

            if ($partdata['content-type'] == 'text/html') {
                if ($use_html) {
                    $contenttype = 'text/html';
                    $mail_body = $hm_purifier->purify($mail_body);
                } else {
                    $html2text = new \Html2Text\Html2Text($mail_body, array());
                    $mail_body = $html2text->getText();
                }
            }
        }

        mailparse_msg_free($mail);

        $timestamp = time();

        /*
            before we create new ticket try to find references...
            no because: somebody would like to make new request while replying
            with new subject (without ticketdid)
        */

        $prev_tid = 0;
        $inreplytoid = null;
        $reftab = explode(' ', $mh_references);
        $lastref = array_pop($reftab);

        // check 'References'
        if ($lastref) {
            $message = $DB->GetRow(
                "SELECT id, ticketid FROM rtmessages WHERE messageid = ?",
                array($lastref)
            );
            if (!empty($message)) {
                $prev_tid = $message['ticketid'];
                $inreplytoid = $message['id'];
            }
        }

        // check email subject
        if (!$prev_tid && preg_match('/' . $subject_ticket_regexp_match . '/', $mh_subject, $matches)) {
            $prev_tid = sprintf('%d', $matches['ticketid']);
            if (!$DB->GetOne("SELECT id FROM rttickets WHERE id = ?", array($prev_tid))) {
                $prev_tid = 0;
            }
        }

        $mail_mh_subject = $mh_subject;
        $reqcustid = 0;
        $requserid = null;

        if (preg_match('/^(?<display>.*)<(?<address>.+@.+)>$/', $mh_replyto, $m)) {
            $replytoname = $m['display'];
            $replytoemail = $m['address'];
        } else {
            $replytoname = $replytoemail = '';
        }

        if (preg_match('/^(?<display>.*)<(?<address>.+@.+)>$/', $mh_from, $m)) {
            $fromname = $m['display'];
            $fromemail = $m['address'];
        } else {
            $fromname = $fromemail = '';
        }

        $toemails = array();

        if (preg_match('/^.*<(?<address>.+@.+)>$/', $mh_to, $m)) {
            $toemails[] = $m['address'];
        } elseif (!empty($mh_to)) {
            $toemails[] = $mh_to;
        }

        if (preg_match('/^.*<(?<address>.+@.+)>$/', $mh_cc, $m)) {
            $toemails[] = $m['address'];
        } elseif (!empty($mh_cc)) {
            $toemails[] = $mh_cc;
        }

        // find queue ID if not specified
        if (!$queue && !empty($toemails)) {
            $queue = $DB->GetOne(
                "SELECT id FROM rtqueues WHERE email IN (" . implode(',', array_fill(1, count($toemails), '?')) . ")",
                $toemails
            );
        }

        if (!$queue) {
            if ($mode == MODE_IMAP) {
                continue;
            }
            fprintf($stderr, "Fatal error: Queue ID not found, exiting." . PHP_EOL);
            exit(5);
        }

        // find customerid
        $reqcustid = $DB->GetOne("SELECT c.id FROM customers c
            JOIN customercontacts cc ON cc.customerid = c.id AND (cc.type & ? > 0)
            WHERE cc.contact = ?", array(CONTACT_EMAIL | CONTACT_INVOICES | CONTACT_NOTIFICATIONS, $fromemail));
        if (empty($reqcustid)) {
            $reqcustid = 0;
        }

        // get sender e-mail if not specified
        if (!$autoreply_from) {
            $queue_autoreply = $DB->GetRow(
                "SELECT email, name FROM rtqueues WHERE id = ?",
                array($queue)
            );
            if (!empty($queue_autoreply)) {
                $autoreply_from = $queue_autoreply['email'];
                $autoreply_name = $autoreply_name ? $autoreply_name : $queue_autoreply['name'];
            }
        }

        if (!$prev_tid) { // generate new ticket if previous not found
            $cats = array();
            foreach ($categories as $category) {
                if (($catid = $LMS->GetCategoryIdByName($category)) != null) {
                    $cats[$catid] = $category;
                }
            }

            $ticket_id = $LMS->TicketAdd(array(
                'queue' => $queue,
                'requestor' => $mh_from,
                'customerid' => $reqcustid,
                'subject' => $mh_subject,
                'createtime' => $timestamp,
                'source' => RT_SOURCE_EMAIL,
                'mailfrom' => $mh_from,
                'replyto' => $mh_replyto,
                'messageid' => $mh_msgid,
                'headers' => $mail_headers,
                'contenttype' => $contenttype,
                'body' => $mail_body,
                'categories' => $cats), $files);

            $message_id = $LMS->GetLastMessageID();

            if ($autoreply) {
                $ticketid = sprintf("%06d", $ticket_id);
                $autoreply_subject = str_replace('%tid', $ticketid, $autoreply_subject);
                $autoreply_subject = str_replace('%subject', $mail_mh_subject, $autoreply_subject);
                $autoreply_body = str_replace('%tid', $ticketid, $autoreply_body);
                $autoreply_body = str_replace('%subject', $mail_mh_subject, $autoreply_body);

                if ($replytoemail) {
                    $mailto = $replytoemail;
                    $mailto_qp_encoded = (empty($replytoname) ? '' : qp_encode($replytoname) . ' ') . '<' . $replytoemail . '>';
                } else {
                    $mailto = $fromemail;
                    $mailto_qp_encoded = (empty($fromname) ? '' : qp_encode($fromname) . ' ') . '<' . $fromemail . '>';
                }

                $headers = array(
                    'From' => (empty($autoreply_name) ? '' : qp_encode($autoreply_name) . ' ') . '<' . $autoreply_from . '>',
                    'To' => $mailto_qp_encoded,
                    'Subject' => $autoreply_subject,
                    'References' => $mh_references . ' ' . $mh_msgid,
                    'In-Reply-To' => $mh_msgid,
                    'Message-ID' => "<confirm.$ticket_id.$queue.$timestamp@rtsystem.$hostname>",
                );
                $LMS->SendMail($mailto, $headers, $autoreply_body, null, null, $smtp_options);
            }

            $new_ticket = true;
        } else {
            // find userid
            $requserid = $DB->GetOne(
                "SELECT id FROM vusers WHERE email = ? AND email <> ''",
                array($fromemail)
            );
            if (empty($requserid)) {
                $requserid = null;
            }

            $msgid = $LMS->TicketMessageAdd(array(
                'ticketid' => $prev_tid,
                'mailfrom' => $mh_from,
                'customerid' => $reqcustid,
                'userid' => $requserid,
                'subject' => $mh_subject,
                'messageid' => $mh_msgid,
                'replyto' => $mh_replyto,
                'headers' => $mail_headers,
                'contenttype' => $contenttype,
                'body' => $mail_body,
                'inreplyto' => $inreplytoid,
            ), $files);

            if ($auto_open) {
                $DB->Execute(
                    "UPDATE rttickets SET state = ? WHERE id = ? AND state > ?",
                    array(RT_OPEN, $prev_tid, RT_OPEN)
                );
            }

            $ticket_id = $prev_tid;
            $new_ticket = false;
        }

        if ($notify) {
            $helpdesk_sender_name = ConfigHelper::getConfig('phpui.helpdesk_sender_name');
            if (!empty($helpdesk_sender_name)) {
                $mailfname = '"' . $LMS->GetQueueName($queue) . '"';
            } else {
                $mailfname = '';
            }

            if ($qemail = $LMS->GetQueueEmail($queue)) {
                $mailfrom = $qemail;
            } elseif ($fromemail) {
                $mailfrom = $fromemail;
            } else {
                $mailfrom = $autoreply_from;
            }

            $ticket = $LMS->GetTicketContents($ticket_id);

            $headers['From'] = $mailfname . ' <' . $mailfrom . '>';
            $headers['Reply-To'] = $headers['From'];

            $queuedata = $LMS->GetQueue($queue);

            if ($ticket['customerid'] && $reqcustid) {
                $info = $LMS->GetCustomer($ticket['customerid'], true);

                $emails = array_map(function ($contact) {
                    return $contact['fullname'];
                }, $LMS->GetCustomerContacts($ticket['customerid'], CONTACT_EMAIL));
                $phones = array_map(function ($contact) {
                    return $contact['fullname'];
                }, $LMS->GetCustomerContacts($ticket['customerid'], CONTACT_LANDLINE | CONTACT_MOBILE));

                if ($customerinfo) {
                    $params = array(
                        'id' => $ticket_id,
                        'customerid' => $ticket['customerid'],
                        'customer' => $info,
                        'emails' => $emails,
                        'phones' => $phones,
                    );
                    $mail_customerinfo = $LMS->ReplaceNotificationCustomerSymbols(ConfigHelper::getConfig('phpui.helpdesk_customerinfo_mail_body'), $params);
                    $sms_customerinfo = $LMS->ReplaceNotificationCustomerSymbols(ConfigHelper::getConfig('phpui.helpdesk_customerinfo_sms_body'), $params);
                }

                if ($new_ticket) {
                    $ticketsubject_variable = 'newticketsubject';
                    $ticketbody_variable = 'newticketbody';
                } else {
                    $ticketsubject_variable = 'newmessagesubject';
                    $ticketbody_variable = 'newmessagebody';
                }
                if (!empty($queuedata[$ticketsubject_variable]) && !empty($queuedata[$ticketbody_variable]) && !empty($emails)) {
                    $ticketid = sprintf("%06d", $ticket_id);
                    $custmail_subject = $queuedata[$ticketsubject_variable];
                    $custmail_subject = str_replace('%tid', $ticketid, $custmail_subject);
                    $custmail_subject = str_replace('%title', $mh_subject, $custmail_subject);
                    $custmail_body = $queuedata[$ticketbody_variable];
                    $custmail_body = str_replace('%tid', $ticketid, $custmail_body);
                    $custmail_body = str_replace('%cid', $ticket['customerid'], $custmail_body);
                    $custmail_body = str_replace('%pin', $info['pin'], $custmail_body);
                    $custmail_body = str_replace('%customername', $info['customername'], $custmail_body);
                    $custmail_body = str_replace('%title', $mh_subject, $custmail_body);
                    $custmail_headers = array(
                        'From' => $headers['From'],
                        'Reply-To' => $headers['From'],
                        'Subject' => $custmail_subject,
                    );
                    foreach ($emails as $email) {
                        $custmail_headers['To'] = '<' . $email . '>';
                        $LMS->SendMail($email, $custmail_headers, $custmail_body, null, null, $smtp_options);
                    }
                }
            } elseif ($customerinfo && !empty($fromname)) {
                $mail_customerinfo = "\n\n-- \n" . trans('Customer:') . ' ' . $fromname;
                $sms_customerinfo = "\n" . trans('Customer:') . ' ' . $fromname;
            }

            $params = array(
                'id' => $ticket_id,
                'queue' => $queuedata['name'],
                'messageid' => isset($msgid) ? $msgid : null,
                'customerid' => $ticket['customerid'] && $reqcustid ? $ticket['customerid'] : null,
                'status' => $ticket['status'],
                'categories' => $ticket['categorynames'],
                'subject' => $mh_subject,
                'body' => $mail_body,
                'attachments' => &$attachments,
                'url' => $lms_url,
            );

            $headers['Subject'] = $LMS->ReplaceNotificationSymbols(ConfigHelper::getConfig('phpui.helpdesk_notification_mail_subject'), $params);

            $params['customerinfo'] = isset($mail_customerinfo) ? $mail_customerinfo : null;
            $params['contenttype'] = $contenttype;
            $body = $LMS->ReplaceNotificationSymbols(ConfigHelper::getConfig('phpui.helpdesk_notification_mail_body'), $params);

            $params['customerinfo'] = isset($sms_customerinfo) ? $sms_customerinfo : null;
            $params['contenttype'] = 'text/plain';
            $sms_body = $LMS->ReplaceNotificationSymbols(ConfigHelper::getConfig('phpui.helpdesk_notification_sms_body'), $params);

            if ($contenttype == 'text/html') {
                $headers['X-LMS-Format'] = 'html';
            }

            $LMS->NotifyUsers(array(
                'queue' => $queue,
                'mail_headers' => $headers,
                'mail_body' => $body,
                'sms_body' => $sms_body,
                'contenttype' => $contenttype,
                'attachments' => &$attachments,
            ));
        }
    }

    if ($postid !== false) {
        if ($rtparser_use_seen_flag) {
            imap_setflag_full($ih, $postid, "\\Seen");
        } else {
            imap_clearflag_full($ih, $postid, "\\Seen");
        }

        $postid = next($posts);
    }

    unset($buffer);
}

if (!empty($ih)) {
    imap_close($ih);
}

/*
echo $mail_body . PHP_EOL;
foreach ($attachments as $attachment) {
    $attachment['content'] = substr($attachment['content'], 0, 100);
    print_r($attachment);
}
*/
