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
    'section:' => 's:',
    'queue:' => null,
    'message-file:' => 'm:',
    'use-html' => null,
    'prefer-html' => null,
    'imap' => null,
    'check-mail' => null,
);

$script_help = <<<EOF
-s, --section=<section-name>    section name from lms configuration where settings
                                are stored
    --queue=<queueid>           queue ID (it means, QUEUE ID, numeric! NOT NAME! also
                                its required to run!);
-m, --message-file=<message-file>
                                use message file instead of standard input;
    --use-html                  use html content type and load it to database if it's present
    --prefer-html               force html content usage; without this html content is used
                                only if text/plain content is not present in handled post
    --imap
                                fetch posts using imap protocol
    --check-mail
                                check if mail from 'To' header matches selected queue mail address
EOF;

require_once('script-options.php');

$SYSLOG = SYSLOG::getInstance();

// Initialize Session, Auth and LMS classes

$AUTH = null;
$LMS = new LMS($DB, $AUTH, $SYSLOG);

// Initialize plugin manager (required for hooks like send_sms_before)
$plugin_manager = LMSPluginManager::getInstance();
$LMS->setPluginManager($plugin_manager);

$hostname = gethostname();
if (empty($hostname)) {
    $hostname = 'example.com';
}

$config_section = isset($options['section']) && preg_match('/^[a-z0-9-_]+$/i', $options['section'])
    ? $options['section']
    : 'rt';

$smtp_options = $LMS->GetRTSmtpOptions($config_section);

$queue = 0;
if (isset($options['queue'])) {
    $queue = $options['queue'];
}
if (empty($queue)) {
    $queue = ConfigHelper::getConfig($config_section . '.parser_default_queue');
}

if (preg_match('/^[0-9]+$/', $queue)) {
    $queue = intval($queue);
    if ($queue && !$LMS->QueueExists($queue)) {
        $queue = 0;
    }
} else {
    $queue = $LMS->GetQueueIdByName($queue);
}
$categories = ConfigHelper::getConfig($config_section . '.default_categories', 'default');
$categories = preg_split('/\s*,\s*/', trim($categories));
$auto_open = ConfigHelper::checkConfig($config_section . '.auto_open', true);
//$tmp_dir = ConfigHelper::getConfig($config_section . '.tmp_dir', '', true);
$notify = ConfigHelper::checkConfig(
    $config_section . '.new_ticket_notify',
    ConfigHelper::checkConfig($config_section . '.newticket_notify', true)
);
$customerinfo = ConfigHelper::checkConfig($config_section . '.include_customerinfo', true);
$lms_url = ConfigHelper::getConfig($config_section . '.lms_url', 'http://localhost/lms/');
$autoreply_from = ConfigHelper::getConfig($config_section . '.mail_from', '', true);
$autoreply_name = ConfigHelper::getConfig($config_section . '.mail_from_name', '', true);
$autoreply_format = ConfigHelper::getConfig($config_section . '.autoreply_format', 'text');
$autoreply_subject = ConfigHelper::getConfig($config_section . '.autoreply_subject', "[RT#%tid] Receipt of request '%subject'");
$autoreply_body = ConfigHelper::getConfig($config_section . '.autoreply_body', '', true);
$autoreply = ConfigHelper::checkConfig($config_section . '.autoreply', true);
$subject_ticket_regexp_match = ConfigHelper::getConfig($config_section . '.subject_ticket_regexp_match', '\[RT#(?<ticketid>[0-9]{6,})\]');
$body_customer_phone_number_regexp_match = ConfigHelper::getConfig($config_section . '.body_customer_phone_number_regexp_match', '', true);
$body_date_regexp_match = ConfigHelper::getConfig($config_section . '.body_date_regexp_match', '', true);
$subject_template = ConfigHelper::getConfig($config_section . '.subject_template', '', true);
$body_template = ConfigHelper::getConfig($config_section . '.body_template', '', true);
$ignore_sender_email = ConfigHelper::checkConfig($config_section . '.ignore_sender_email');

$modify_ticket_timeframe = ConfigHelper::getConfig($config_section . '.allow_modify_resolved_tickets_newer_than', 604800);

$detect_customer_location_address = ConfigHelper::checkConfig($config_section . '.detect_customer_location_address');

$image_max_size = ConfigHelper::getConfig('phpui.uploaded_image_max_size');

$rtparser_server = ConfigHelper::getConfig(
    $config_section . '.imap_server',
    $smtp_options['host'] ?? ConfigHelper::GetConfig('mail.smtp_host')
);
$rtparser_username = ConfigHelper::getConfig(
    $config_section . '.imap_username',
    $smtp_options['user'] ?? ConfigHelper::GetConfig('mail.smtp_username')
);
$rtparser_password = ConfigHelper::getConfig(
    $config_section . '.imap_password',
    $smtp_options['pass'] ?? ConfigHelper::GetConfig('mail.smtp_password')
);
$rtparser_use_seen_flag = ConfigHelper::checkConfig($config_section . '.imap_use_seen_flag', true);
$rtparser_use_flagged_flag = ConfigHelper::checkConfig($config_section . '.imap_use_flagged_flag');
$rtparser_folder = ConfigHelper::getConfig($config_section . '.imap_folder', 'INBOX');

$url_props = parse_url($lms_url);

$stderr = fopen('php://stderr', 'w');

$prefer_html = isset($options['prefer-html']);
$use_html = isset($options['use-html']) || $prefer_html;
$check_mail = isset($options['check-mail']);

define('MODE_FILE', 1);
define('MODE_IMAP', 2);

$mode = isset($options['imap']) ? MODE_IMAP : MODE_FILE;

if (isset($smtp_options['auth']) && $smtp_options['auth'] && !preg_match('/^(LOGIN|PLAIN|CRAM-MD5|NTLM)$/i', $smtp_options['auth'])) {
    fprintf($stderr, "Fatal error: smtp_auth setting not supported! Can't continue, exiting." . PHP_EOL);
    exit(1);
}

if (!$autoreply_body) {
    $autoreply_body = "Your request was registered in our system.\n"
        . "Ticket identifier RT#%tid has been assigned to this request.\n\n"
        . "Please, include string [RT#%tid] in subject field of any\n"
        . "subsequent posts related to this request.\n";
}
$autoreply_body = str_replace("\\n", "\n", $autoreply_body);

$autoreply_subject_template = $autoreply_subject;
$autoreply_body_template = $autoreply_body;

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
        fprintf($stderr, 'Cannot connect to mail server: ' . imap_last_error() . '!' . PHP_EOL);
        exit(7);
    }

    $search_filter = array();
    $set_flags = array();
    if ($rtparser_use_seen_flag) {
        $search_filter[] = 'UNSEEN';
        $set_flags[] = "\\Seen";
    }
    if ($rtparser_use_flagged_flag) {
        $search_filter[] = 'UNFLAGGED';
        $set_flags[] = "\\Flagged";
    }
    $posts = imap_search($ih, empty($search_filter) ? 'ALL' : implode(' ', $search_filter));
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

while (isset($buffer) || ($postid !== false && $postid !== null)) {
    if ($postid !== false && $postid !== null) {
        $buffer = imap_fetchbody($ih, $postid, '');

        if (!empty($set_flags)) {
            imap_setflag_full($ih, $postid, implode(' ', $set_flags));
        } else {
            imap_clearflag_full($ih, $postid, "\\Seen \\Flagged");
        }
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

        $return_paths = null;
        if (isset($headers['return-path'])) {
            if (is_array($headers['return-path'])) {
                $return_paths = $headers['return-path'];
            } else {
                $return_paths = array($headers['return-path']);
            }
            $return_paths = array_filter($return_paths, function ($var) {
                return $var == '<>' || strtolower($var) == '<mailer-daemon>';
            });
        }

        if (!empty($return_paths) || isset($headers['auto-submitted']) && $headers['auto-submitted'] == 'auto-replied') {
            mailparse_msg_free($mail);

            if ($postid !== false && $postid !== null) {
                if (!empty($set_flags)) {
                    imap_setflag_full($ih, $postid, implode(' ', $set_flags));
                } else {
                    imap_clearflag_full($ih, $postid, "\\Seen \\Flagged");
                }

                $postid = next($posts);
            }

            unset($buffer);

            continue;
        }

        $mh_from = iconv_mime_decode($headers['from']);
        $mh_to = iconv_mime_decode($headers['to']);
        $mh_cc = isset($headers['cc']) ? iconv_mime_decode($headers['cc']) : '';
        $mh_msgid = iconv_mime_decode($headers['message-id']);
        $mh_replyto = isset($headers['reply-to']) ? iconv_mime_decode($headers['reply-to']) : '';
        $mh_subject = isset($headers['subject']) ? iconv_mime_decode($headers['subject']) : '';
        if (!strlen($mh_subject)) {
            $mh_subject = trans('(no subject)');
        }
        if (isset($headers['references'])) {
            $mh_references = iconv_mime_decode($headers['references']);
        } else {
            $mh_references = '';
        }
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
                $html = strpos($partdata['content-type'], 'html') !== false;
                $isAttachment = isset($partdata['content-disposition']) && $partdata['content-disposition'] == 'attachment';
                if (!$isAttachment
                    && preg_match('/text/', $partdata['content-type'])
                    && ($mail_body == '' || ($html && $prefer_html) || (!$html && !$use_html))) {
                    $mail_body = substr($buffer, $partdata['starting-pos-body'], $partdata['ending-pos-body'] - $partdata['starting-pos-body']);
                    $charset = $partdata['content-charset'];
                    $transfer_encoding = $partdata['transfer-encoding'] ?? '';
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
                        $html = strpos($subpartdata['content-type'], 'html') !== false;
                        if (preg_match('/text/', $subpartdata['content-type'])
                            && (trim($mail_body) == '' || ($html && $prefer_html) || (!$html && !$use_html))) {
                            $mail_body = substr($buffer, $subpartdata['starting-pos-body'], $subpartdata['ending-pos-body'] - $subpartdata['starting-pos-body']);
                            $charset = $subpartdata['content-charset'];
                            $transfer_encoding = $subpartdata['transfer-encoding'] ?? '';
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
                } elseif ((isset($partdata['content-disposition']) && ($isAttachment
                            || $partdata['content-disposition'] == 'inline')) || isset($partdata['content-id'])) {
                    $file_content = substr($buffer, $partdata['starting-pos-body'], $partdata['ending-pos-body'] - $partdata['starting-pos-body']);
                    $transfer_encoding = $partdata['transfer-encoding'] ?? '';
                    switch ($transfer_encoding) {
                        case 'base64':
                            $file_content = base64_decode($file_content);
                            break;
                        case 'quoted-printable':
                            $file_content = quoted_printable_decode($file_content);
                            break;
                    }
                    $file_name = $partdata['content-name'] ?? ($partdata['disposition-filename'] ?? '');
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
                        'content-id' => !$isAttachment && isset($partdata['content-id']) ? $partdata['content-id'] : null,
                    );
                    $attachments[] = array(
                        'content_type' => $partdata['content-type'],
                        'filename' => $file_name,
                        'data' => &$file_content,
                        'content-id' => !$isAttachment && isset($partdata['content-id']) ? $partdata['content-id'] : null,
                    );
                    unset($file_content);
                }
            }
        } else {
            $charset = $partdata['content-charset'];
            $mail_body = substr($buffer, $partdata['starting-pos-body'], $partdata['ending-pos-body'] - $partdata['starting-pos-body']);

            $transfer_encoding = $partdata['transfer-encoding'] ?? '';
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

        if ($contenttype != 'text/html') {
            if (!empty($files)) {
                foreach ($files as &$file) {
                    unset($file['content-id']);
                }
                unset($file);
                foreach ($attachments as &$attachment) {
                    unset($attachment['content-id']);
                }
                unset($attachment);
            }
        }

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
                "SELECT m.id, m.ticketid, t.state, t.resolvetime
                FROM rtmessages m
                JOIN rttickets t ON t.id = m.ticketid
                WHERE m.messageid = ?",
                array($lastref)
            );
            if (!empty($message)
                && ($message['state'] != RT_RESOLVED || $message['resolvetime'] + $modify_ticket_timeframe > time())) {
                $prev_tid = $message['ticketid'];
                $inreplytoid = $message['id'];
            }
        }

        // check email subject
        if (!$prev_tid && preg_match('/' . $subject_ticket_regexp_match . '/', $mh_subject, $matches)) {
            $prev_tid = sprintf('%d', $matches['ticketid']);
        }

        if ($prev_tid) {
            $prev_tid_contents = $LMS->GetTicketContents($prev_tid);
            $queue = $prev_tid_contents['queueid'];
        }

        $mail_mh_subject = $mh_subject;
        $reqcustid = 0;
        $requserid = null;

        if (preg_match('/^(?:(?<display>.*) )?<?(?<address>[a-z0-9_\.-]+@[\da-z\.-]+\.[a-z\.]{2,6})>?$/iA', $mh_replyto, $m)) {
            $replytoname = $m['display'] ?? '';
            $replytoemail = $m['address'];
        } else {
            $replytoname = $replytoemail = '';
        }

        if (preg_match('/^(?:(?<display>.*) )?<?(?<address>[a-z0-9_\.-]+@[\da-z\.-]+\.[a-z\.]{2,6})>?$/iA', $mh_from, $m)) {
            $fromname = $m['display'] ?? '';
            $fromemail = $m['address'];
        } else {
            $fromname = $fromemail = '';
        }

        $toemails = array();

        if (preg_match('/^(?:(?<display>.*) )?<?(?<address>[a-z0-9_\.-]+@[\da-z\.-]+\.[a-z\.]{2,6})>?$/iA', $mh_to, $m)) {
            $toemails[strtolower($m['address'])] = strtolower($m['address']);
        } elseif (!empty($mh_to)) {
            $toemails[strtolower($mh_to)] = strtolower($mh_to);
        }

        $ccemails = array();
        $_ccemails = preg_split('/\s*,\s*/', $mh_cc, null, PREG_SPLIT_NO_EMPTY);
        if (!empty($_ccemails)) {
            foreach ($_ccemails as $ccemail) {
                if (preg_match('/^(?:(?<display>.*) )?<?(?<address>[a-z0-9_\.-]+@[\da-z\.-]+\.[a-z\.]{2,6})>?$/iA', $ccemail, $m)) {
                    $ccemails[strtolower($m['address'])] = $m['display'] ?? '';
                } else {
                    $ccemails[strtolower($ccemail)] = '';
                }
            }
        }

        // find queue ID if not specified
        if ((!$queue || $check_mail) && (!empty($toemails) || !empty($ccemails))) {
            $queueid = $queue;
            $queue = $DB->GetRow(
                "SELECT id, LOWER(email) AS email FROM rtqueues WHERE LOWER(email) IN ? LIMIT 1",
                array(array_merge(array_keys($toemails), array_keys($ccemails)))
            );
            if (!empty($queue) && (!$check_mail || $queue['id'] == $queueid)) {
                $_ccemails = array();
                foreach ($ccemails as $ccaddress => $ccdisplay) {
                    if ($ccaddress != $queue['email']) {
                        $_ccemails[$ccaddress] = $ccdisplay;
                    }
                }
                $ccemails = $_ccemails;
                $queue = $queue['id'];
            } else {
                $queue = 0;
            }
        }

        if (!$queue) {
            if ($mode == MODE_IMAP) {
                $postid = next($posts);
                $buffer = null;
                continue;
            }
            fprintf($stderr, "Fatal error: Queue ID not found, exiting." . PHP_EOL);
            exit(5);
        }

        // try to find customerid by phone number match
        $phone = null;
        if (!empty($body_customer_phone_number_regexp_match)) {
            if (preg_match('/' . $body_customer_phone_number_regexp_match . '/im', $mail_body, $m)) {
                if (!empty($m['phone'])) {
                    $phone = $m['phone'];
                } elseif (!empty($m['number'])) {
                    $phone = $m['number'];
                }
            }
            if (!empty($phone)) {
                $phone = preg_replace('/[^0-9]/', '', $phone);

                $reqcustid = $DB->GetCol(
                    "SELECT c.id
                    FROM customers c
                    JOIN customercontacts cc ON cc.customerid = c.id AND (cc.type & ?) > 0
                    WHERE REPLACE(REPLACE(contact, ' ', ''), '-', '') ?LIKE? ?",
                    array(
                        CONTACT_MOBILE | CONTACT_LANDLINE,
                        '%' . $phone,
                    )
                );
            }
        }

        $date = null;
        if (!empty($body_date_regexp_match)) {
            if (preg_match('/' . $body_date_regexp_match . '/im', $mail_body, $m)) {
                if (!empty($m['date'])) {
                    $date = trim($m['date']);
                }
            }
        }

        if (!empty($phone) && !empty($reqcustid) && count($reqcustid) == 1) {
            $reqcustid = reset($reqcustid);
        } else {
            // find customerid
            $reqcustid = $DB->GetCol(
                "SELECT c.id FROM customers c
                JOIN customercontacts cc ON cc.customerid = c.id AND (cc.type & ?) > 0
                WHERE cc.contact = ?",
                array(
                    CONTACT_EMAIL,
                    $fromemail,
                )
            );
            if (empty($reqcustid) || count($reqcustid) > 1) {
                $reqcustid = 0;
            } else {
                $reqcustid = reset($reqcustid);
            }
        }

        // get sender e-mail if not specified
        if (!$autoreply_from) {
            $queue_autoreply = $DB->GetRow(
                "SELECT email, name FROM rtqueues WHERE id = ?",
                array($queue)
            );
            if (!empty($queue_autoreply)) {
                $autoreply_from = $queue_autoreply['email'];
                $autoreply_name = $autoreply_name ?: $queue_autoreply['name'];
            }
        }

        // add new message or create new ticket
        if ($prev_tid && ($prev_tid_contents['state'] != RT_RESOLVED || $prev_tid_contents['resolvetime'] + $modify_ticket_timeframe > time())) {
            // find userid
            $requserid = $DB->GetOne(
                "SELECT id FROM vusers WHERE LOWER(email) = LOWER(?)",
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
        } else {
            $cats = array();
            foreach ($categories as $category) {
                if (preg_match('/^[0-9]+$/', $category) && $LMS->CategoryExists($category)) {
                    $cats[$category] = $LMS->GetCategoryName($category);
                } elseif (($catid = $LMS->GetCategoryIdByName($category)) != null) {
                    $cats[$catid] = $category;
                }
            }

            if (empty($reqcustid) || !$detect_customer_location_address) {
                $address_id = null;
            } else {
                $address_id = $LMS->detectCustomerLocationAddress($reqcustid);
            }

            $ticket_id = $LMS->TicketAdd(array(
                'queue' => $queue,
                'requestor' => empty($fromname) ? $mh_from : $fromname,
                'requestor_mail' => $ignore_sender_email || empty($fromemail) ? null : $fromemail,
                'requestor_phone' => empty($phone) ? null : $phone,
                'customerid' => $reqcustid,
                'address_id' => $address_id,
                'subject' => empty($subject_template)
                    ? $mh_subject
                    : str_replace(
                        array(
                            '%phone',
                        ),
                        array(
                            empty($phone) ? '-' : $phone,
                        ),
                        $subject_template
                    ),
                'createtime' => $timestamp,
                'source' => RT_SOURCE_EMAIL,
                'mailfrom' => $mh_from,
                'replyto' => $mh_replyto,
                'messageid' => $mh_msgid,
                'headers' => $mail_headers,
                'contenttype' => $contenttype,
                'body' => empty($body_template)
                    ? $mail_body
                    : str_replace(
                        array(
                            '%phone',
                            '%date',
                        ),
                        array(
                            empty($phone) ? '-' : $phone,
                            empty($date) ? '-' : $date,
                        ),
                        $body_template
                    ),
                'phonefrom' => empty($phone) ? '' : $phone,
                'categories' => $cats), $files);

            $message_id = $LMS->GetLastMessageID();

            if ($autoreply && (empty($reqcustid) || $DB->GetOne(
                'SELECT cc.id
                FROM customercontacts cc
                WHERE cc.customerid = ?
                    AND (cc.type & ?) = ?
                    AND cc.contact = ?',
                array(
                    $reqcustid,
                    CONTACT_EMAIL | CONTACT_HELPDESK_NOTIFICATIONS,
                    CONTACT_EMAIL | CONTACT_HELPDESK_NOTIFICATIONS,
                    $fromemail,
                )
            ))) {
                $current_autoreply_subject = preg_replace_callback(
                    '/%(\\d*)tid/',
                    function ($m) use ($ticket_id) {
                        return sprintf('%0' . $m[1] . 'd', $ticket_id);
                    },
                    $autoreply_subject_template
                );
                $current_autoreply_subject = str_replace('%subject', $mail_mh_subject, $current_autoreply_subject);
                $current_autoreply_body = preg_replace_callback(
                    '/%(\\d*)tid/',
                    function ($m) use ($ticket_id) {
                        return sprintf('%0' . $m[1] . 'd', $ticket_id);
                    },
                    $autoreply_body_template
                );
                $current_autoreply_body = str_replace('%subject', $mail_mh_subject, $current_autoreply_body);

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
                    'Subject' => $current_autoreply_subject,
                    'References' => $mh_references . ' ' . $mh_msgid,
                    'In-Reply-To' => $mh_msgid,
                    'Message-ID' => "<confirm.$ticket_id.$queue.$timestamp@rtsystem.$hostname>",
                );

                if ($autoreply_format == 'html') {
                    $headers['X-LMS-Format'] = 'html';
                }

                if (!empty($reqcustid) && !empty($ccemails)) {
                    $headers['Cc'] = implode(
                        ', ',
                        array_map(function ($address, $display) {
                            return (empty($display) ? '' : $display . ' ') . '<' . $address . '>';
                        }, array_keys($ccemails), array_values($ccemails))
                    );
                }

                $LMS->SendMail($mailto, $headers, $current_autoreply_body, null, null, $smtp_options);
            }

            $new_ticket = true;
        }

        $ticket = $LMS->GetTicketContents($ticket_id);

        if ($notify || $ticket['customerid'] && $reqcustid) {
            $helpdesk_sender_name = ConfigHelper::getConfig($config_section . '.sender_name', ConfigHelper::getConfig('phpui.helpdesk_sender_name'));

            $mailfname = '';

            if (!empty($helpdesk_sender_name)) {
                $mailfname = $helpdesk_sender_name;

                if ($mailfname == 'queue' || $mailfname == 'user') {
                    $mailfname = $LMS->GetQueueName($queue);
                }

                $mailfname = '"' . $mailfname . '"';
            }

            if ($qemail = $LMS->GetQueueEmail($queue)) {
                $mailfrom = $qemail;
            } elseif ($fromemail) {
                $mailfrom = $fromemail;
            } else {
                $mailfrom = $autoreply_from;
            }

            $headers['From'] = $mailfname . ' <' . $mailfrom . '>';
            $headers['Reply-To'] = $headers['From'];

            $queuedata = $LMS->GetQueue($queue);

            if ($ticket['customerid'] && $reqcustid) {
                $info = $LMS->GetCustomer($ticket['customerid'], true);

                $emails = array_map(
                    function ($contact) {
                        return $contact['fullname'];
                    },
                    array_filter(
                        $LMS->GetCustomerContacts($ticket['customerid'], CONTACT_EMAIL),
                        function ($contact) {
                            return $contact['type'] & CONTACT_HELPDESK_NOTIFICATIONS;
                        }
                    )
                );
                $phones = array_map(
                    function ($contact) {
                        return $contact['fullname'];
                    },
                    array_filter(
                        $LMS->GetCustomerContacts($ticket['customerid'], CONTACT_LANDLINE | CONTACT_MOBILE),
                        function ($contact) {
                            return $contact['type'] & CONTACT_HELPDESK_NOTIFICATIONS;
                        }
                    )
                );

                if ($notify && $customerinfo) {
                    $params = array(
                        'id' => $ticket_id,
                        'customerid' => $ticket['customerid'],
                        'customer' => $info,
                        'emails' => $emails,
                        'phones' => $phones,
                    );
                    $mail_customerinfo = $LMS->ReplaceNotificationCustomerSymbols(
                        ConfigHelper::getConfig(
                            $config_section . '.notification_mail_body_customerinfo_format',
                            ConfigHelper::getConfig('phpui.helpdesk_customerinfo_mail_body')
                        ),
                        $params
                    );
                    $sms_customerinfo = $LMS->ReplaceNotificationCustomerSymbols(
                        ConfigHelper::getConfig(
                            $config_section . '.notification_sms_body_customerinfo_format',
                            ConfigHelper::getConfig('phpui.helpdesk_customerinfo_sms_body')
                        ),
                        $params
                    );
                }
            } elseif ($customerinfo && !empty($fromname)) {
                $mail_customerinfo = "\n\n-- \n" . trans('Customer:') . ' ' . $fromname;
                $sms_customerinfo = "\n" . trans('Customer:') . ' ' . $fromname;
            }

            $params = array(
                'id' => $ticket_id,
                'queue' => $queuedata['name'],
                'messageid' => $msgid ?? null,
                'customerid' => $ticket['customerid'] && $reqcustid ? $ticket['customerid'] : null,
                'status' => $ticket['status'],
                'categories' => $ticket['categorynames'],
                'subject' => $mh_subject,
                'body' => $mail_body,
                'attachments' => &$attachments,
                'url' => $lms_url,
            );

            $headers['Subject'] = $LMS->ReplaceNotificationSymbols(ConfigHelper::getConfig($config_section . '.notification_mail_subject', ConfigHelper::getConfig('phpui.helpdesk_notification_mail_subject')), $params);

            $params['customerinfo'] = $mail_customerinfo ?? null;
            $params['contenttype'] = $contenttype;
            $body = $LMS->ReplaceNotificationSymbols(ConfigHelper::getConfig($config_section . '.notification_mail_body', ConfigHelper::getConfig('phpui.helpdesk_notification_mail_body')), $params);

            $params['customerinfo'] = $sms_customerinfo ?? null;
            $params['contenttype'] = 'text/plain';
            $sms_body = $LMS->ReplaceNotificationSymbols(ConfigHelper::getConfig($config_section . '.notification_sms_body', ConfigHelper::getConfig('phpui.helpdesk_notification_sms_body')), $params);

            if ($contenttype == 'text/html') {
                $headers['X-LMS-Format'] = 'html';
            }

            $LMS->NotifyUsers(array(
                'queue' => $queue,
                'ticketid' => $ticket_id,
                'mail_headers' => $headers,
                'mail_body' => $body,
                'sms_body' => $sms_body,
                'contenttype' => $contenttype,
                'attachments' => &$attachments,
            ));
        }

        if ($ticket['customerid'] && $reqcustid && !empty($mails)) {
            if ($new_ticket) {
                $ticketsubject_variable = 'newticketsubject';
                $ticketbody_variable = 'newticketbody';
            } else {
                $ticketsubject_variable = 'newmessagesubject';
                $ticketbody_variable = 'newmessagebody';
            }

            if (!empty($queuedata[$ticketsubject_variable]) && !empty($queuedata[$ticketbody_variable]) && !empty($emails)) {
                $custmail_subject = $queuedata[$ticketsubject_variable];
                $custmail_subject = preg_replace_callback(
                    '/%(\\d*)tid/',
                    function ($m) use ($ticket_id) {
                        return sprintf('%0' . $m[1] . 'd', $ticket_id);
                    },
                    $custmail_subject
                );
                $custmail_subject = str_replace('%title', $mh_subject, $custmail_subject);
                $custmail_body = $queuedata[$ticketbody_variable];
                $custmail_body = preg_replace_callback(
                    '/%(\\d*)tid/',
                    function ($m) use ($ticket_id) {
                        return sprintf('%0' . $m[1] . 'd', $ticket_id);
                    },
                    $custmail_body
                );
                $custmail_body = str_replace('%cid', $ticket['customerid'], $custmail_body);
                $custmail_body = str_replace('%pin', $info['pin'], $custmail_body);
                $custmail_body = str_replace('%customername', $info['customername'], $custmail_body);
                $custmail_body = str_replace('%title', $mh_subject, $custmail_body);
                $custmail_body = str_replace('%body', $mail_body, $custmail_body);
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
        }
    }

    if ($postid !== false && $postid !== null) {
        if (!empty($set_flags)) {
            imap_setflag_full($ih, $postid, implode(' ', $set_flags));
        } else {
            imap_clearflag_full($ih, $postid, "\\Seen \\Flagged");
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
