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
    'test' => 't',
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
-t, --test                      parse and match tickets without database writes, outgoing
                                mail or IMAP flag, delete or move changes
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

$modify_ticket_timeframe = intval(ConfigHelper::getConfig($config_section . '.allow_modify_resolved_tickets_newer_than', 604800));
$parserMaxDateAge = intval(ConfigHelper::getConfig($config_section . '.parser_max_date_age', 3));
$parserAutoreplyLimit = intval(ConfigHelper::getConfig($config_section . '.parser_autoreply_limit', 10, true));
$parserAutoreplyLimitPeriod = intval(ConfigHelper::getConfig($config_section . '.parser_autoreply_limit_period', 86400, true));
$parserAutoreplyMaxCc = intval(ConfigHelper::getConfig($config_section . '.parser_autoreply_max_cc', 5, true));
// Local MTA authserv-id. Empty ignores Authentication-Results and trusts From.
$parserTrustedAuthservId = ConfigHelper::getConfig($config_section . '.parser_trusted_authserv_id', '', true);
// Non-party In-Reply-To/References matches open a new ticket. Set false to append them.
$parserVerifyReferenceSender = ConfigHelper::checkConfig($config_section . '.parser_verify_reference_sender', true);

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
$test = isset($options['test']);

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

function decodeHeader($value)
{
    $value = (string) $value;
    $decoded = @iconv_mime_decode($value, ICONV_MIME_DECODE_CONTINUE_ON_ERROR, 'UTF-8');
    if ($decoded === false) {
        $decoded = $value;
    }
    $decoded = mb_scrub($decoded, 'UTF-8');
    $decoded = preg_replace('/[\x00-\x1F\x7F]/', '', $decoded);

    return trim($decoded);
}

function parseAddressList($header)
{
    $addresses = [];
    if (!strlen($header)) {
        return $addresses;
    }
    foreach (mailparse_rfc822_parse_addresses($header) as $item) {
        $address = trim($item['address']);
        // filter_var() (check_email) rejects valid internal addresses like rt@localhost
        if (!empty($item['is_group']) || !preg_match('/^[^@\s]+@[^@\s]+$/', $address)) {
            continue;
        }
        $display = $item['display'] == $item['address'] ? '' : decodeHeader($item['display']);
        $addresses[] = [
            'display' => $display,
            'address' => $address,
        ];
    }

    return $addresses;
}

function autoreplyDestinations($row)
{
    $destinations = [];
    $replyTo = parseAddressList((string) ($row['replyto'] ?? ''));
    if ($replyTo) {
        $destinations[] = strtolower($replyTo[0]['address']);
    } else {
        $from = parseAddressList((string) ($row['mailfrom'] ?? ''));
        if ($from) {
            $destinations[] = strtolower($from[0]['address']);
        }
    }
    if (!empty($row['customerid']) && preg_match_all('/^cc:[ \t]*(.*)$/im', str_replace("\r", '', (string) ($row['headers'] ?? '')), $matches)) {
        foreach ($matches[1] as $ccHeader) {
            foreach (parseAddressList($ccHeader) as $item) {
                $destinations[] = strtolower($item['address']);
            }
        }
    }

    return array_values(array_unique($destinations));
}

function &autoreplyTicketsBySender()
{
    global $DB, $parserAutoreplyLimit, $parserAutoreplyLimitPeriod;

    static $ticketsBySender = null;
    if ($ticketsBySender !== null) {
        return $ticketsBySender;
    }

    $ticketsBySender = [];
    if ($parserAutoreplyLimit <= 0 || $parserAutoreplyLimitPeriod <= 0) {
        return $ticketsBySender;
    }

    $rows = $DB->GetAll(
        'SELECT m.ticketid, m.customerid, m.mailfrom, m.replyto, m.headers
        FROM rtmessages m
        WHERE m.createtime >= ?
            AND m.type = ?
            AND NOT EXISTS (
                SELECT 1 FROM rtmessages older
                WHERE older.ticketid = m.ticketid
                    AND older.id < m.id
            )',
        [
            time() - $parserAutoreplyLimitPeriod,
            RTMESSAGE_REGULAR,
        ]
    ) ?: [];

    foreach ($rows as $row) {
        foreach (autoreplyDestinations($row) as $address) {
            $ticketsBySender[$address][$row['ticketid']] = true;
        }
    }

    return $ticketsBySender;
}

function recentAutoreplyDestinationCounts()
{
    $counts = [];
    foreach (autoreplyTicketsBySender() as $address => $tickets) {
        $counts[$address] = count($tickets);
    }

    return $counts;
}

function rememberAutoreplyTicket($ticketId, $addresses)
{
    if (!$ticketId) {
        return;
    }
    $ticketsBySender = &autoreplyTicketsBySender();
    foreach ($addresses as $address) {
        $address = strtolower($address);
        if ($address === '') {
            continue;
        }
        $ticketsBySender[$address][$ticketId] = true;
    }
}

function autoreplyDestinationAllowed($address, $counts)
{
    global $parserAutoreplyLimit, $parserAutoreplyLimitPeriod;

    if ($parserAutoreplyLimit <= 0 || $parserAutoreplyLimitPeriod <= 0) {
        return true;
    }

    return ($counts[strtolower($address)] ?? 0) <= $parserAutoreplyLimit;
}

function limitAutoreplyCc($ccemails, $counts)
{
    global $parserAutoreplyMaxCc;

    $limited = [];
    foreach ($ccemails as $address => $display) {
        if ($parserAutoreplyMaxCc > 0 && count($limited) >= $parserAutoreplyMaxCc) {
            break;
        }
        if (!autoreplyDestinationAllowed($address, $counts)) {
            continue;
        }
        $limited[$address] = $display;
    }

    return $limited;
}

function warnTrustedAuthservIdUnset()
{
    global $stderr;

    static $warned = false;
    if ($warned) {
        return;
    }
    $warned = true;
    $message = 'rt.parser_trusted_authserv_id is unset; Authentication-Results is ignored and From is trusted. Set it to the local MTA authserv-id.';
    if (is_resource($stderr)) {
        fprintf($stderr, 'Warning: ' . $message . PHP_EOL);
    }
    if (function_exists('writesyslog')) {
        writesyslog($message, LOG_WARNING);
    }
}

function fromAddressAuthenticated($mailHeaders)
{
    global $parserTrustedAuthservId;

    $authservId = strtolower(trim((string) $parserTrustedAuthservId));
    if ($authservId === '') {
        // Header order is sender-controlled, so an unset authserv-id cannot select a trusted result.
        warnTrustedAuthservIdUnset();
        return true;
    }

    if (!preg_match_all('/^authentication-results:[ \t]*(.*)$/im', str_replace("\r", '', (string) $mailHeaders), $matches)) {
        // A missing Authentication-Results header is trusted (legacy).
        return true;
    }

    $chosen = null;
    foreach ($matches[1] as $value) {
        if (preg_match('/^\s*([^\s;]+)/', $value, $id) && strtolower($id[1]) === $authservId) {
            $chosen = $value;
            break;
        }
    }
    if ($chosen === null) {
        return false;
    }

    return (bool) preg_match('/(?:^|[;\s])(?:dmarc|spf)\s*=\s*pass\b/i', $chosen);
}

function unrelatedSenderOpensNewTicket($subjectMatchedTicket, $referenceMatchedTicket, $senderIsTicketParty)
{
    global $parserVerifyReferenceSender;

    if ($senderIsTicketParty) {
        return false;
    }

    return $subjectMatchedTicket || ($parserVerifyReferenceSender && $referenceMatchedTicket);
}

function senderIsTicketCustomerContact($fromemail, $ticket)
{
    global $LMS;

    if ($fromemail === '' || empty($ticket['customerid'])) {
        return false;
    }
    $fromemail = strtolower($fromemail);
    foreach ($LMS->GetCustomerContacts($ticket['customerid'], CONTACT_EMAIL) as $contact) {
        if (($contact['type'] & CONTACT_DISABLED) != 0) {
            continue;
        }
        if (strtolower(trim($contact['contact'])) === $fromemail) {
            return true;
        }
    }

    return false;
}

function senderIsTicketRequestor($fromemail, $ticket)
{
    if ($fromemail === '') {
        return false;
    }
    $fromemail = strtolower($fromemail);
    $requestor = trim((string) ($ticket['requestor_mail'] ?? ''));
    if ($requestor === '') {
        return false;
    }
    if (strtolower($requestor) === $fromemail) {
        return true;
    }
    $parsed = parseAddressList($requestor);

    return $parsed && strtolower($parsed[0]['address']) === $fromemail;
}

function decodeMailBody($part, $message, $partData, $useHtml, $purifier)
{
    $body = mailparse_msg_extract_part($part, $message, null);
    if (!is_string($body)) {
        $body = '';
    }
    $charset = $partData['content-charset'] ?? 'UTF-8';
    try {
        $body = mb_convert_encoding($body, 'UTF-8', $charset);
    } catch (\ValueError $e) {
        $body = @iconv($charset, 'UTF-8//IGNORE', $body) ?: mb_convert_encoding($body, 'UTF-8', 'UTF-8');
    }
    $body = str_replace("\0", '', $body);

    $contentType = 'text/plain';
    if ($partData['content-type'] == 'text/html') {
        if ($useHtml) {
            $contentType = 'text/html';
            $body = $purifier->purify($body);
        } else {
            $body = (new \Html2Text\Html2Text($body, array()))->getText();
        }
    }

    return array($body, $contentType);
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
        fprintf($stderr, "Fatal error: PHP IMAP extension is required!" . PHP_EOL);
        exit(5);
    }

    if (empty($rtparser_server) || empty($rtparser_username) || empty($rtparser_password)) {
        fprintf($stderr, "Fatal error: mailbox credentials are not set!" . PHP_EOL);
        exit(6);
    }

    imap_timeout(IMAP_OPENTIMEOUT, 30);
    imap_timeout(IMAP_READTIMEOUT, 30);

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
    $posts = imap_search($ih, empty($search_filter) ? 'ALL' : implode(' ', $search_filter), SE_UID | SE_FREE);
    if (empty($posts)) {
        imap_close($ih);
        die;
    }

    $postid = reset($posts);
} else {
    if (isset($options['message-file'])) {
        if (!is_readable($options['message-file'])) {
            fprintf($stderr, "Cannot read message file '%s'!" . PHP_EOL, $options['message-file']);
            exit(8);
        }
        $buffer = file_get_contents($options['message-file']);
    } else {
        $buffer = file_get_contents('php://stdin');
    }
}

$defaultQueue = $queue;

function handleRtparserMessage()
{
    global $DB, $LMS, $_ccemails, $address, $address_id, $attachment, $attachments, $autoSubmitted;
    global $autoSubmittedValue, $autoSubmittedValues, $auto_open, $autoreply, $autoreplyHeaders;
    global $autoreply_body_template, $autoreply_format, $autoreply_from, $autoreply_name, $autoreply_subject_template;
    global $body, $body_customer_phone_number_regexp_match, $body_date_regexp_match, $body_template, $buffer;
    global $categories, $category, $catid, $cats, $ccaddress, $ccdisplay, $ccemails, $check_mail, $config_section;
    global $contact, $contenttype, $current_autoreply_body, $current_autoreply_subject, $custmail_body;
    global $custmail_headers, $custmail_subject, $customCreateTime, $customerinfo, $date, $decoded_mail_header;
    global $decoded_mail_headers, $defaultQueue, $detect_customer_location_address, $display, $e, $email;
    global $emailContacts, $emails, $file, $file_content, $file_name, $files, $fromAddresses, $fromemail, $fromname;
    global $headerName, $headers, $height, $helpdesk_sender_name, $hm_purifier, $hostname, $html, $ignore_sender_email;
    global $ih, $imageSize, $image_max_size, $imagick, $inReplyIds, $info, $inreplytoid, $isAttachment, $lms_url, $m;
    global $mail, $mail_body, $mail_customerinfo, $mail_header, $mail_headers, $mail_mh_subject, $mailfname, $mailfrom;
    global $mailto, $mailto_qp_encoded, $matches, $message, $messageAutoreplyFrom, $messageAutoreplyName, $message_id;
    global $mh_from, $mh_msgid, $mh_references, $mh_replyto, $mh_subject, $mode, $modify_ticket_timeframe, $msgid;
    global $new_ticket, $notify, $now, $params, $parsedDate, $parserMaxDateAge, $part, $partdata, $partid, $parts;
    global $parserVerifyReferenceSender, $phone, $phones, $postid, $posts, $precedenceValues, $prefer_html, $prev_tid, $prev_tid_contents, $queue;
    global $queue_autoreply, $queuedata, $queueid, $ref, $refcandidates, $referenceIds, $replyToAddresses;
    global $replytoemail, $replytoname, $reqcustid, $requserid, $resolvedParentId, $return_paths, $set_flags;
    global $sms_body, $sms_customerinfo, $smtp_options, $stderr, $subject_template, $subject_ticket_regexp_match;
    global $subpart, $subpartdata, $subpartid, $test, $ticket, $ticket_id, $ticketbody_variable, $ticketsubject_variable;
    global $timestamp, $toemails, $transfer_encoding, $use_html, $value, $var, $width;

    if ($postid !== false && $postid !== null) {
        $buffer = imap_fetchbody($ih, $postid, '', FT_PEEK | FT_UID);
    }

    unset($msgid, $mail_customerinfo, $sms_customerinfo, $info, $emails, $queuedata, $prev_tid_contents);
    $queue = $defaultQueue;
    $contenttype = 'text/plain';

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

        foreach (['from', 'to', 'cc', 'message-id', 'reply-to', 'subject', 'references', 'in-reply-to', 'date'] as $headerName) {
            if (!isset($headers[$headerName]) || !is_array($headers[$headerName])) {
                continue;
            }
            if ($headerName == 'date' || $headerName == 'message-id') {
                $headers[$headerName] = reset($headers[$headerName]);
            } elseif ($headerName == 'from' || $headerName == 'to' || $headerName == 'cc' || $headerName == 'reply-to') {
                $headers[$headerName] = implode(', ', $headers[$headerName]);
            } else {
                $headers[$headerName] = implode(' ', $headers[$headerName]);
            }
        }

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

        $autoSubmittedValues = array_map(function ($value) {
            return strtolower(trim($value));
        }, (array) ($headers['auto-submitted'] ?? 'no'));
        $precedenceValues = array_map(function ($value) {
            return strtolower(trim($value));
        }, (array) ($headers['precedence'] ?? []));
        $autoSubmitted = false;
        foreach ($autoSubmittedValues as $autoSubmittedValue) {
            if (strtok($autoSubmittedValue, ';') !== 'no') {
                $autoSubmitted = true;
                break;
            }
        }
        if (!empty($return_paths) || $autoSubmitted || array_intersect($precedenceValues, ['bulk', 'junk', 'list', 'auto_reply'])) {
            mailparse_msg_free($mail);
            writesyslog(
                'Skipping automatic message (Message-ID: ' . decodeHeader($headers['message-id'] ?? '-') . ').',
                LOG_INFO
            );

            if ($postid !== false && $postid !== null) {
                if (!$test) {
                    if (!empty($set_flags)) {
                        imap_setflag_full($ih, $postid, implode(' ', $set_flags), ST_UID);
                    } else {
                        imap_clearflag_full($ih, $postid, "\\Seen \\Flagged", ST_UID);
                    }
                }

                $postid = next($posts);
            }

            $buffer = null;

            return;
        }

        $customCreateTime = null;
        if (isset($headers['date'])) {
            $parsedDate = strtotime($headers['date']);
            $now = time();
            if ($parsedDate === false
                || $parsedDate > strtotime('+5 minutes', $now)
                || $parsedDate < strtotime('-' . $parserMaxDateAge . ' days', $now)
            ) {
                $customCreateTime = $now;
            } else {
                $customCreateTime = $parsedDate;
            }
        }
        $mh_from = isset($headers['from']) ? decodeHeader($headers['from']) : '';
        $mh_msgid = isset($headers['message-id']) ? decodeHeader($headers['message-id']) : '';
        $mh_replyto = isset($headers['reply-to']) ? decodeHeader($headers['reply-to']) : '';
        $mh_subject = isset($headers['subject']) ? decodeHeader($headers['subject']) : '';
        if (!strlen($mh_subject)) {
            $mh_subject = trans('(no subject)');
        }
        if (isset($headers['references'])) {
            $mh_references = decodeHeader($headers['references']);
        } else {
            $mh_references = '';
        }
        $files = array();
        $attachments = array();

        $mail_headers = substr($buffer, $partdata['starting-pos'], $partdata['starting-pos-body'] - $partdata['starting-pos'] - 1);
        // unfold first, otherwise encoded-words split across lines are not decoded
        $mail_headers = preg_replace('/\r?\n[ \t]+/', ' ', $mail_headers);
        $decoded_mail_headers = array();
        foreach (explode("\n", $mail_headers) as $mail_header) {
            $decoded_mail_header = @iconv_mime_decode($mail_header);
            if ($decoded_mail_header === false) {
                $decoded_mail_headers[] = $mail_header;
            } else {
                $decoded_mail_headers[] = $decoded_mail_header;
            }
        }
        $mail_headers = str_replace("\0", '', mb_scrub(implode("\n", $decoded_mail_headers), 'UTF-8'));
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
                    && !isset($partdata['disposition-filename']) && !isset($partdata['content-name'])
                    && preg_match('#^text/(plain|html)$#i', $partdata['content-type'])
                    && ($mail_body == '' || ($html && $prefer_html) || (!$html && !$use_html))) {
                    list($mail_body, $contenttype) = decodeMailBody($part, $buffer, $partdata, $use_html, $hm_purifier ?? null);
                } elseif (preg_match('#multipart/alternative#', $partdata['content-type']) && $mail_body == '') {
                    while (!empty($parts) && strpos($parts[0], $partid . '.') === 0) {
                        $subpartid = array_shift($parts);
                        $subpart = mailparse_msg_get_part($mail, $subpartid);
                        $subpartdata = mailparse_msg_get_part_data($subpart);
                        $html = strpos($subpartdata['content-type'], 'html') !== false;
                        if (preg_match('#^text/(plain|html)$#i', $subpartdata['content-type'])
                            && (trim($mail_body) == '' || ($html && $prefer_html) || (!$html && !$use_html))) {
                            list($mail_body, $contenttype) = decodeMailBody($subpart, $buffer, $subpartdata, $use_html, $hm_purifier ?? null);
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
                    $file_name = basename(str_replace('\\', '/', decodeHeader($file_name)));
                    if ($file_name === '' || $file_name === '.' || $file_name === '..') {
                        unset($file_content);
                        continue;
                    }

                    if (!isset($partdata['content-id']) && $image_max_size && class_exists('Imagick') && strpos($partdata['content-type'], 'image/') === 0
                        && ($imageSize = @getimagesizefromstring($file_content))
                        && $imageSize[0] > 0 && $imageSize[1] > 0 && $imageSize[0] <= 8192 && $imageSize[1] <= 8192
                    ) {
                        try {
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
                            $imagick->clear();
                        } catch (\Exception $e) {
                            if (isset($imagick)) {
                                $imagick->clear();
                            }
                            // keep original content
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
            list($mail_body, $contenttype) = decodeMailBody($part, $buffer, $partdata, $use_html, $hm_purifier ?? null);
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
        $resolvedParentId = 0;
        // check In-Reply-To and References, newest first, at most 10 lookups
        $referenceIds = [];
        $inReplyIds = [];
        preg_match_all('/<[^<>\s]+>/', $mh_references, $referenceIds);
        if (!empty($headers['in-reply-to'])) {
            preg_match_all('/<[^<>\s]+>/', $headers['in-reply-to'], $inReplyIds);
        }
        $refcandidates = array_merge($inReplyIds[0] ?? [], array_reverse($referenceIds[0] ?? []));
        foreach (array_slice(array_unique($refcandidates), 0, 10) as $ref) {
            $message = $DB->GetRow(
                "SELECT m.id, m.ticketid, t.state, t.resolvetime
                FROM rtmessages m
                JOIN rttickets t ON t.id = m.ticketid
                WHERE m.messageid = ?
                ORDER BY m.id DESC LIMIT 1",
                [$ref]
            );
            if (empty($message)) {
                continue;
            }
            if ($message['state'] != RT_RESOLVED || $message['resolvetime'] + $modify_ticket_timeframe > time()) {
                $prev_tid = $message['ticketid'];
                $inreplytoid = $message['id'];
                break;
            }
            if (!$resolvedParentId) {
                $resolvedParentId = $message['ticketid'];
            }
        }

        // check email subject
        $subjectMatchedTicket = false;
        if (!$prev_tid && preg_match('/' . $subject_ticket_regexp_match . '/', $mh_subject, $matches) && isset($matches['ticketid'])) {
            $prev_tid = intval($matches['ticketid']);
            $subjectMatchedTicket = true;
        }

        if ($prev_tid) {
            $prev_tid_contents = $LMS->GetTicketContents($prev_tid);
            if (empty($prev_tid_contents)) {
                $prev_tid = 0;
                $inreplytoid = null;
            } else {
                $queue = $prev_tid_contents['queueid'];
            }
        }

        $mail_mh_subject = $mh_subject;
        $reqcustid = 0;
        $requserid = null;

        $replyToAddresses = parseAddressList($headers['reply-to'] ?? '');
        $replytoname = $replyToAddresses ? $replyToAddresses[0]['display'] : '';
        $replytoemail = $replyToAddresses ? $replyToAddresses[0]['address'] : '';

        $fromAddresses = parseAddressList($headers['from'] ?? '');
        $fromname = $fromAddresses ? $fromAddresses[0]['display'] : '';
        $fromemail = $fromAddresses ? $fromAddresses[0]['address'] : '';

        $toemails = [];
        foreach (parseAddressList($headers['to'] ?? '') as $address) {
            $toemails[strtolower($address['address'])] = strtolower($address['address']);
        }

        $ccemails = [];
        foreach (parseAddressList($headers['cc'] ?? '') as $address) {
            $ccemails[strtolower($address['address'])] = $address['display'];
        }

        // find queue ID if not specified
        if ((!$queue || $check_mail) && (!empty($toemails) || !empty($ccemails))) {
            $queueid = $queue;
            $queue = $DB->GetRow(
                "SELECT id, LOWER(email) AS email FROM rtqueues WHERE LOWER(email) IN ?"
                    . ($check_mail && $queueid ? ' AND id = ' . intval($queueid) : '')
                    . " ORDER BY (CASE WHEN LOWER(email) IN ? THEN 0 ELSE 1 END), id LIMIT 1",
                array(
                    array_merge(array_keys($toemails), array_keys($ccemails)),
                    $toemails ? array_keys($toemails) : array(''),
                )
            );
            if (!empty($queue) && (!$check_mail || !$queueid || $queue['id'] == $queueid)) {
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
                writesyslog(
                    'Queue not found, skipping message (Message-ID: ' . decodeHeader($mh_msgid)
                        . ', To: ' . decodeHeader(implode(', ', array_merge($toemails, array_keys($ccemails)))) . ').',
                    LOG_WARNING
                );
                if (!$test) {
                    if (!empty($set_flags)) {
                        imap_setflag_full($ih, $postid, implode(' ', $set_flags), ST_UID);
                    } else {
                        imap_clearflag_full($ih, $postid, "\\Seen \\Flagged", ST_UID);
                    }
                }
                $postid = next($posts);
                $buffer = null;
                return;
            }
            fprintf($stderr, "Fatal error: Queue ID not found, exiting." . PHP_EOL);
            exit(9);
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
                    "SELECT DISTINCT c.id
                    FROM customers c
                    JOIN customercontacts cc ON cc.customerid = c.id AND (cc.type & ?) > 0
                    WHERE c.deleted = 0
                        AND REPLACE(REPLACE(contact, ' ', ''), '-', '') ?LIKE? ?",
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

        $fromMayIdentify = fromAddressAuthenticated($mail_headers);
        if (!empty($phone) && !empty($reqcustid) && count($reqcustid) == 1) {
            $reqcustid = reset($reqcustid);
        } elseif ($fromMayIdentify && $fromemail !== '') {
            // find customerid
            $reqcustid = $DB->GetCol(
                "SELECT DISTINCT c.id
                FROM customers c
                JOIN customercontacts cc ON cc.customerid = c.id AND (cc.type & ?) > 0
                WHERE c.deleted = 0
                    AND LOWER(cc.contact) = LOWER(?)",
                [
                    CONTACT_EMAIL,
                    $fromemail,
                ]
            );
            if (empty($reqcustid) || count($reqcustid) > 1) {
                $reqcustid = 0;
            } else {
                $reqcustid = reset($reqcustid);
            }
        } else {
            $reqcustid = 0;
        }

        if ($fromMayIdentify && $fromemail !== '') {
            $requserid = $DB->GetOne(
                "SELECT id FROM vusers WHERE LOWER(email) = LOWER(?)",
                [$fromemail]
            );
            if (empty($requserid)) {
                $requserid = null;
            }
        }

        $messageAutoreplyFrom = $autoreply_from;
        $messageAutoreplyName = $autoreply_name;

        // get sender e-mail if not specified
        if (!$messageAutoreplyFrom) {
            $queue_autoreply = $DB->GetRow(
                "SELECT email, name FROM rtqueues WHERE id = ?",
                array($queue)
            );
            if (!empty($queue_autoreply)) {
                $messageAutoreplyFrom = $queue_autoreply['email'];
                $messageAutoreplyName = $messageAutoreplyName ?: $queue_autoreply['name'];
            }
        }

        $senderIsTicketParty = false;
        if ($fromMayIdentify && $prev_tid && !empty($prev_tid_contents)) {
            $senderIsTicketParty = senderIsTicketCustomerContact($fromemail, $prev_tid_contents)
                || senderIsTicketRequestor($fromemail, $prev_tid_contents)
                || !empty($requserid);
        }

        // add new message or create new ticket
        $ticketAcceptsReply = $prev_tid && ($prev_tid_contents['state'] != RT_RESOLVED || $prev_tid_contents['resolvetime'] + $modify_ticket_timeframe > time());
        $referenceMatchedTicket = $prev_tid && !$subjectMatchedTicket;
        if ($ticketAcceptsReply && unrelatedSenderOpensNewTicket($subjectMatchedTicket, $referenceMatchedTicket, $senderIsTicketParty)) {
            writesyslog(
                ($subjectMatchedTicket ? 'Subject' : 'Reference') . ' ticket ' . intval($prev_tid)
                    . ' not appended, sender <' . decodeHeader($fromemail)
                    . '> is not a party to the ticket (Message-ID: ' . decodeHeader($mh_msgid ?? '-') . ').',
                LOG_INFO
            );
            $ticketAcceptsReply = false;
        }
        $messageCustomerId = $ticketAcceptsReply && $fromMayIdentify && senderIsTicketCustomerContact($fromemail, $prev_tid_contents)
            ? $prev_tid_contents['customerid']
            : null;
        if ($test) {
            $reportedCustomerId = $ticketAcceptsReply ? $messageCustomerId : $reqcustid;
            printf(
                '%s queue=%d customer=%s attachments=%d' . PHP_EOL,
                $ticketAcceptsReply ? 'append to #' . intval($prev_tid) : 'new ticket',
                intval($queue),
                $reportedCustomerId ? '#' . intval($reportedCustomerId) : '-',
                count($files)
            );
            if ($postid !== false && $postid !== null) {
                $postid = next($posts);
            }
            $buffer = null;
            return;
        }
        if ($ticketAcceptsReply) {
            $msgid = $LMS->TicketMessageAdd(array(
                'ticketid' => $prev_tid,
                'mailfrom' => $mh_from,
                'customerid' => $messageCustomerId,
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
            if ($prev_tid && !$resolvedParentId) {
                $resolvedParentId = $prev_tid;
            }
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
                'customcreatetime' => $customCreateTime,
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
                'categories' => $cats,
                'parentid' => $resolvedParentId ?: null,
            ), $files);

            $message_id = $LMS->GetLastMessageID();

            if ($autoreply && (empty($reqcustid) || $DB->GetOne(
                'SELECT cc.id
                FROM customercontacts cc
                WHERE cc.customerid = ?
                    AND (cc.type & ?) = ?
                    AND LOWER(cc.contact) = LOWER(?)',
                array(
                    $reqcustid,
                    CONTACT_EMAIL | CONTACT_HELPDESK_NOTIFICATIONS | CONTACT_DISABLED,
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

                $autoreplyCounts = recentAutoreplyDestinationCounts();
                if (!autoreplyDestinationAllowed($mailto, $autoreplyCounts)) {
                    writesyslog(
                        'Autoreply suppressed, limit reached for <' . decodeHeader($mailto)
                            . '> (Message-ID: ' . decodeHeader($mh_msgid ?? '-') . ').',
                        LOG_INFO
                    );
                } else {
                    $autoreplyHeaders = [
                        'From' => (empty($messageAutoreplyName) ? '' : qp_encode($messageAutoreplyName) . ' ') . '<' . $messageAutoreplyFrom . '>',
                        'To' => $mailto_qp_encoded,
                        'Subject' => $current_autoreply_subject,
                        'References' => $mh_references . ' ' . $mh_msgid,
                        'In-Reply-To' => $mh_msgid,
                        'Message-ID' => "<confirm.$ticket_id.$queue.$timestamp@rtsystem.$hostname>",
                        'Auto-Submitted' => 'auto-replied',
                    ];

                    if ($autoreply_format == 'html') {
                        $autoreplyHeaders['X-LMS-Format'] = 'html';
                    }

                    if (!empty($reqcustid) && !empty($ccemails)) {
                        $ccLimited = limitAutoreplyCc($ccemails, $autoreplyCounts);
                        if ($ccLimited) {
                            $autoreplyHeaders['Cc'] = implode(
                                ', ',
                                array_map(function ($address, $display) {
                                    return (empty($display) ? '' : qp_encode($display) . ' ') . '<' . $address . '>';
                                }, array_keys($ccLimited), array_values($ccLimited))
                            );
                        }
                    }

                    $LMS->SendMail($mailto, $autoreplyHeaders, $current_autoreply_body, null, null, $smtp_options);
                    rememberAutoreplyTicket($ticket_id, autoreplyDestinations([
                        'customerid' => $reqcustid,
                        'mailfrom' => $mh_from,
                        'replyto' => $mh_replyto,
                        'headers' => $mail_headers,
                    ]));
                }
            }

            $new_ticket = true;
        }

        $ticket = $LMS->GetTicketContents($ticket_id);

        $headers = [];

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

            $mailfrom = $LMS->DetermineSenderEmail(
                empty($requserid) ? null : $fromemail,
                $LMS->GetQueueEmail($queue),
                $fromemail,
                ConfigHelper::getConfig($config_section . '.sender_email', '', true)
            ) ?: $messageAutoreplyFrom;

            $headers['From'] = $mailfname . ' <' . $mailfrom . '>';
            $headers['Reply-To'] = $headers['From'];

            $queuedata = $LMS->GetQueue($queue);

            if ($ticket['customerid'] && $reqcustid) {
                $info = $LMS->GetCustomer($ticket['customerid'], true);

                $emailContacts = array_filter(
                    $LMS->GetCustomerContacts($ticket['customerid'], CONTACT_EMAIL),
                    function ($contact) {
                        return ($contact['type'] & (CONTACT_HELPDESK_NOTIFICATIONS | CONTACT_DISABLED)) == CONTACT_HELPDESK_NOTIFICATIONS;
                    }
                );
                $emails = array_map(
                    function ($contact) {
                        return $contact['fullname'];
                    },
                    $emailContacts
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

            if ($notify) {
                $LMS->NotifyUsers([
                    'queue' => $queue,
                    'ticketid' => $ticket_id,
                    'mail_headers' => $headers,
                    'mail_body' => $body,
                    'sms_body' => $sms_body,
                    'contenttype' => $contenttype,
                    'attachments' => &$attachments,
                    'smtp_options' => $smtp_options,
                ]);
            }
        }

        if ($ticket['customerid'] && $reqcustid && !empty($emails)) {
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
                $custmail_body = str_replace('%pin', $info['pin'] ?? '', $custmail_body);
                $custmail_body = str_replace('%customername', $info['customername'] ?? '', $custmail_body);
                $custmail_body = str_replace('%title', $mh_subject, $custmail_body);
                $custmail_body = str_replace('%body', $mail_body, $custmail_body);
                $custmail_headers = array(
                    'From' => $headers['From'],
                    'Reply-To' => $headers['From'],
                    'Subject' => $custmail_subject,
                );
                if ($contenttype == 'text/html') {
                    $custmail_headers['X-LMS-Format'] = 'html';
                }
                // fullname is "address (name)"; send to the bare address
                foreach (array_column($emailContacts, 'contact') as $email) {
                    $custmail_headers['To'] = '<' . $email . '>';
                    $LMS->SendMail($email, $custmail_headers, $custmail_body, null, null, $smtp_options);
                }
            }
        }
    }

    if ($postid !== false && $postid !== null) {
        if (!$test) {
            if (!empty($set_flags)) {
                imap_setflag_full($ih, $postid, implode(' ', $set_flags), ST_UID);
            } else {
                imap_clearflag_full($ih, $postid, "\\Seen \\Flagged", ST_UID);
            }
        }

        $postid = next($posts);
    }

    $buffer = null;
}

while (isset($buffer) || ($postid !== false && $postid !== null)) {
    try {
        handleRtparserMessage();
    } catch (\Throwable $e) {
        writesyslog('Message processing failed: ' . decodeHeader($e->getMessage()), LOG_ERR);
        if ($mode != MODE_IMAP) {
            exit(75);
        }
        if ($postid !== false && $postid !== null) {
            if (!$test) {
                $failFlags = ['\\Flagged'];
                if ($rtparser_use_seen_flag) {
                    $failFlags[] = '\\Seen';
                }
                imap_setflag_full($ih, $postid, implode(' ', $failFlags), ST_UID);
            }
            $postid = next($posts);
        }
        $buffer = null;
        continue;
    }
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
