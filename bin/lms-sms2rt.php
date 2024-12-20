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
    'message-file:' => 'm:',
    'force-http-mode' => 'f',
    'fetch-only' => 'o',
    'output-directory:' => null,
);

$script_help = <<<EOF
-s, --section=<section-name>    section name from lms configuration where settings
                                are stored
-m, --message-file=<message-file>       name of message file;
-f, --force-http-mode           force callback url mode even if script is not launched under
                                http server control;
-o, --fetch-only                only fetch incoming SMS messages and write them to files;
    --output-directory=<directory>
                                output directory is directory where fetched messages
                                are stored;
EOF;

require_once('script-options.php');

$SYSLOG = SYSLOG::getInstance();

// Initialize Session, Auth and LMS classes

$AUTH = null;
$LMS = new LMS($DB, $AUTH, $SYSLOG);

$config_section = isset($options['section']) && preg_match('/^[a-z0-9-_]+$/i', $options['section']) ? $options['section'] : 'sms';

$incoming_queue = ConfigHelper::getConfig($config_section . '.incoming_queue', 'SMS');
$default_mail_from = ConfigHelper::getConfig($config_section . '.default_mail_from', 'root@localhost');
$categories = ConfigHelper::getConfig($config_section . '.categories', 'default');
$categories = preg_split('/\s*,\s*/', trim($categories));
$lms_url = ConfigHelper::getConfig($config_section . '.lms_url', '', true);
$service = ConfigHelper::getConfig($config_section . '.service', '', true);
if (!empty($service)) {
    LMSConfig::getConfig()->getSection('sms')->addVariable(new ConfigVariable('service', $service));
}
$prefix = ConfigHelper::getConfig($config_section . '.prefix', '', true);
$newticket_notify = ConfigHelper::checkConfig(
    'rt.new_ticket_notify',
    ConfigHelper::checkConfig('phpui.newticket_notify', true)
);
$helpdesk_customerinfo = ConfigHelper::checkConfig(
    'rt.notification_customerinfo',
    ConfigHelper::checkConfig('phpui.helpdesk_customerinfo')
);
$helpdesk_sendername = ConfigHelper::getConfig('rt.sender_name', ConfigHelper::getConfig('phpui.helpdesk_sender_name'));
$customer_auto_reply_body = ConfigHelper::getConfig('sms.customer_auto_reply_body', '', true);

$detect_customer_location_address = ConfigHelper::checkConfig($config_section . '.detect_customer_location_address');

$mms_detect_regexp = ConfigHelper::getConfig($config_section . '.mms_detect_regexp', null, true);
$customer_mms_auto_reply_body = ConfigHelper::getConfig($config_section . '.customer_mms_auto_reply_body', '', true);

$voicecall_detect_regexp = ConfigHelper::getConfig($config_section . '.voicecall_detect_regexp', null, true);
$customer_voicecall_auto_reply_body = ConfigHelper::getConfig($config_section . '.customer_voicecall_auto_reply_body', '', true);

// Load plugin files and register hook callbacks
$plugin_manager = LMSPluginManager::getInstance();
$LMS->setPluginManager($plugin_manager);

$message_files = array();

if ($http_mode) {
    if (isset($options['output-directory'])) {
        $output_directory = $options['output-directory'];
        if (!is_dir($output_directory)) {
            die('Output directory \'' . $output_directory . '\' does not exist!' . PHP_EOL);
        }
    } else {
        $output_directory = sys_get_temp_dir();
    }

    // call external incoming SMS handler(s)
    $errors = array();
    $content = null;

    foreach (explode(',', $service) as $single_service) {
        $data = $LMS->executeHook(
            'parse_incoming_sms',
            array(
                'service' => $single_service
            )
        );
        if (isset($data['error'])) {
            $errors[$single_service] = $data['error'];
            continue;
        }
        if (isset($data['content'])) {
            $content = $data['content'];
            break;
        }
    }

    if (!isset($content)) {
        foreach ($errors as $single_service => $error) {
            echo $single_service . ': ' . $error . '<br>';
        }
        die;
    }

    if (is_array($content)) {
        foreach ($content as $sms) {
            $message_file = $output_directory . DIRECTORY_SEPARATOR . 'LMS_INCOMING_MESSAGE-' . uniqid('', true);
            file_put_contents($message_file, $sms);
            $message_files[] = $message_file;
        }
    } else {
        $message_file = $output_directory . DIRECTORY_SEPARATOR . 'LMS_INCOMING_MESSAGE-' . uniqid('', true);
        file_put_contents($message_file, $content);
        $message_files[] = $message_file;
    }

    if (isset($options['fetch-only'])) {
        die;
    }
} else {
    if (isset($options['message-file'])) {
        $message_files[] = $options['message-file'];
    } else {
        die('Required message file parameter!' . PHP_EOL);
    }
}

if (($queueid = $DB->GetOne(
    "SELECT id FROM rtqueues WHERE UPPER(name) = UPPER(?)",
    array($incoming_queue)
)) == null) {
    die('Undefined queue!' . PHP_EOL);
}

$plugins = $plugin_manager->getAllPluginInfo(LMSPluginManager::OLD_STYLE);
if (!empty($plugins)) {
    foreach ($plugins as $plugin_name => $plugin) {
        if ($plugin['enabled']) {
            require(LIB_DIR . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . $plugin_name . '.php');
        }
    }
}

foreach ($message_files as $message_file) {
    if (($fh = fopen($message_file, 'r')) != null) {
        $sms = fread($fh, 4000);
        fclose($fh);

        $lines = explode("\n", $sms);

        $body = false;
        $message = "";
        $phone = null;
        $date = null;
        $ucs = false;
        $binary = false;
        reset($lines);
        while (($line = current($lines)) !== false) {
            if (preg_match('/^From: ([0-9]{3,15})$/', $line, $matches) && $phone == null) {
                $phone = $matches[1];
            }
            if (preg_match('/^Received: (.*)$/', $line, $matches) && !isset($date)) {
                $date = strtotime($matches[1]);
                if ($date === false) {
                    $date = null;
                }
            }
            if (preg_match('/^Alphabet:.*UCS2?$/', $line)) {
                $ucs = true;
            } elseif (preg_match('/^Alphabet:[\s]*binary$/', $line)) {
                $binary = true;
            }
            if (empty($line) && !$body) {
                $body = true;
            } else if ($body) {
                if ($ucs) {
                    $line = preg_replace('/\x0$/', "\x0\n", $line);
                }
                $message .= $line;
            }
            next($lines);
        }
        if ($ucs) {
            $message = iconv('UNICODEBIG', 'UTF-8', $message);
        }

        $mms_detected = false;
        $voicecall_detected = false;

        if (isset($mms_detect_regexp)) {
            if ($binary) {
                $message = preg_replace('/[\x00-\x1F\x7F-\xFF]/', '', $message);
            }
            if (preg_match('#' . $mms_detect_regexp . '#i', $message, $m)
                && isset($m['phone'])) {
                $phone = $m['phone'];
                $mms_detected = true;
            }
        }

        if (!$mms_detected && isset($voicecall_detect_regexp)) {
            if ($binary) {
                $message = preg_replace('/[\x00-\x1F\x7F-\xFF]/', '', $message);
            }
            if (preg_match('#' . $voicecall_detect_regexp . '#i', $message, $m)
                && isset($m['phone'])) {
                $phone = $m['phone'];
                $voicecall_detected = true;
            }
        }

        if (!empty($phone)) {
            $phone = preg_replace('/^(\+)?' . $prefix . '/', '', $phone);

            $customer = $DB->GetRow(
                "SELECT customerid AS cid, " . $DB->Concat('lastname', "' '", 'c.name') . " AS name
                FROM customercontacts cc
                LEFT JOIN customers c ON c.id = cc.customerid
                WHERE c.deleted = 0
                    AND (cc.type & ?) > 0
                    AND REPLACE(REPLACE(contact, ' ', ''), '-', '') ?LIKE? ?",
                array(
                    CONTACT_MOBILE | CONTACT_LANDLINE,
                    '%' . $phone,
                )
            );

            $formatted_phone = preg_replace('/^([0-9]{3})([0-9]{3})([0-9]{3})$/', '$1 $2 $3', $phone);

            if ($mms_detected) {
                if (!empty($customer_mms_auto_reply_body)) {
                    $LMS->SendSMS($phone, $customer_mms_auto_reply_body, null, $LMS->getCustomerSMSOptions());
                    sleep(1);
                }
            } elseif ($voicecall_detected) {
                if (!empty($customer_voicecall_auto_reply_body)) {
                    $LMS->SendSMS($phone, $customer_voicecall_auto_reply_body, null, $LMS->getCustomerSMSOptions());
                    sleep(1);
                }
            } else {
                if (!empty($customer_auto_reply_body)) {
                    $LMS->SendSMS($phone, $customer_auto_reply_body, null, $LMS->getCustomerSMSOptions());
                    sleep(1);
                }
            }
        } else {
            $customer = null;
        }

//      if ($phone[0] != '+') {
//          $phone = '+' . $phone;
//      }

        $cats = array();
        foreach ($categories as $category) {
            if (($catid = $LMS->GetCategoryIdByName($category)) != null) {
                $cats[$catid] = $category;
            }
        }
        $requestor = !empty($customer['name']) ? $customer['name'] : (empty($phone) ? '' : $formatted_phone);

        if (empty($customer['cid']) || !$detect_customer_location_address) {
            $address_id = null;
        } else {
            $address_id = $LMS->detectCustomerLocationAddress($customer['cid']);
        }

        $tid = $LMS->TicketAdd(array(
            'queue' => $queueid,
            'createtime' => $date ?? null,
            'requestor' => $requestor,
            'requestor_phone' => empty($phone) ? null : $phone,
            'subject' => trans('SMS from $a', (empty($phone) ? trans('unknown') : $formatted_phone)),
            'customerid' => !empty($customer['cid']) ? $customer['cid'] : 0,
            'address_id' => $address_id,
            'body' => $message,
            'phonefrom' => empty($phone) ? '' : $phone,
            'categories' => $cats,
            'source' => RT_SOURCE_SMS,
        ));

        if ($newticket_notify) {
            if (!empty($helpdesk_sender_name)) {
                $mailfname = $LMS->GetQueueName($queueid);
                $mailfname = '"'.$mailfname.'"';
            } else {
                $mailfname = '';
            }

            if ($qemail = $LMS->GetQueueEmail($queueid)) {
                $mailfrom = $qemail;
            } else {
                $mailfrom = $default_mail_from;
            }

            $headers['From'] = $mailfname.' <'.$mailfrom.'>';
            $headers['Reply-To'] = $headers['From'];

            $queuedata = $LMS->GetQueue($queueid);

            if (!empty($customer['cid'])) {
                $info = $LMS->GetCustomer($customer['cid'], true);

                $emails = array_map(
                    function ($contact) {
                        return $contact['fullname'];
                    },
                    array_filter(
                        $LMS->GetCustomerContacts($customer['cid'], CONTACT_EMAIL),
                        function ($contact) {
                            return $contact['type'] & CONTACT_HELPDESK_NOTIFICATIONS;
                        }
                    )
                );

                $all_phones = array_filter(
                    $LMS->GetCustomerContacts($customer['cid'], CONTACT_LANDLINE | CONTACT_MOBILE),
                    function ($contact) {
                        return $contact['type'] & CONTACT_HELPDESK_NOTIFICATIONS;
                    }
                );

                $phones = array_map(function ($contact) {
                    return $contact['fullname'];
                }, $all_phones);

                $mobile_phones = array_filter($all_phones, function ($contact) {
                    return ($contact['type'] & (CONTACT_MOBILE | CONTACT_DISABLED)) == CONTACT_MOBILE;
                });

                if ($helpdesk_customerinfo) {
                    $params = array(
                        'id' => $tid,
                        'customerid' => $customer['cid'],
                        'customer' => $info,
                        'emails' => $emails,
                        'phones' => $phones,
                        'categories' => $cats,
                    );
                    $mail_customerinfo = $LMS->ReplaceNotificationCustomerSymbols(
                        ConfigHelper::getConfig(
                            'rt.notification_mail_body_customerinfo_format',
                            ConfigHelper::getConfig('phpui.helpdesk_customerinfo_mail_body')
                        ),
                        $params
                    );
                    $sms_customerinfo = $LMS->ReplaceNotificationCustomerSymbols(
                        ConfigHelper::getConfig(
                            'rt.notification_sms_body_customerinfo_format',
                            ConfigHelper::getConfig('phpui.helpdesk_customerinfo_sms_body')
                        ),
                        $params
                    );
                }

                if (!$mms_detected && !$voicecall_detected) {
                    if (!empty($queuedata['newticketsubject']) && !empty($queuedata['newticketbody']) && !empty($emails)) {
                        $custmail_subject = $queuedata['newticketsubject'];
                        $custmail_subject = preg_replace_callback(
                            '/%(\\d*)tid/',
                            function ($m) use ($tid) {
                                return sprintf('%0' . $m[1] . 'd', $tid);
                            },
                            $custmail_subject
                        );
                        $custmail_subject = str_replace(
                            '%title',
                            trans('SMS from $a', (empty($phone) ? trans("unknown") : $formatted_phone)),
                            $custmail_subject
                        );
                        $custmail_body = $queuedata['newticketbody'];
                        $custmail_body = preg_replace_callback(
                            '/%(\\d*)tid/',
                            function ($m) use ($tid) {
                                return sprintf('%0' . $m[1] . 'd', $tid);
                            },
                            $custmail_body
                        );
                        $custmail_body = str_replace('%cid', $customer['cid'], $custmail_body);
                        $custmail_body = str_replace('%pin', $info['pin'], $custmail_body);
                        $custmail_body = str_replace('%customername', $info['customername'], $custmail_body);
                        $custmail_body = str_replace(
                            '%title',
                            trans('SMS from $a', (empty($phone) ? trans("unknown") : $formatted_phone)),
                            $custmail_body
                        );
                        $custmail_body = str_replace('%body', $message, $custmail_body);
                        $custmail_headers = array(
                            'From' => $headers['From'],
                            'Reply-To' => $headers['From'],
                            'Subject' => $custmail_subject,
                        );
                        foreach ($emails as $email) {
                            $custmail_headers['To'] = '<' . $email . '>';
                            $LMS->SendMail($email, $custmail_headers, $custmail_body, null, null, $LMS->GetRTSmtpOptions());
                        }
                    }

                    if (!empty($queuedata['newticketsmsbody']) && !empty($mobile_phones)) {
                        $custsms_body = $queuedata['newticketsmsbody'];
                        $custsms_body = preg_replace_callback(
                            '/%(\\d*)tid/',
                            function ($m) use ($tid) {
                                return sprintf('%0' . $m[1] . 'd', $tid);
                            },
                            $custsms_body
                        );
                        $custsms_body = str_replace('%cid', $customer['cid'], $custsms_body);
                        $custsms_body = str_replace('%pin', $info['pin'], $custsms_body);
                        $custsms_body = str_replace('%customername', $info['customername'], $custsms_body);
                        $custsms_body = str_replace(
                            '%title',
                            trans('SMS from $a', (empty($phone) ? trans("unknown") : $formatted_phone)),
                            $custsms_body
                        );
                        $custsms_body = str_replace('%body', $message, $custsms_body);

                        foreach ($mobile_phones as $phone) {
                            $LMS->SendSMS($phone['contact'], $custsms_body);
                        }
                    }
                }
            } elseif ($helpdesk_customerinfo) {
                $mail_customerinfo = "\n\n-- \n" . trans('Customer:') . ' ' . $requestor;
                $sms_customerinfo = "\n" . trans('Customer:') . ' ' . $requestor;
            }

            $params = array(
                'id' => $tid,
                'queue' => $queuedata['name'],
                'messageid' => $msgid ?? null,
                'customerid' => empty($customer) ? null : $customer['cid'],
                'status' => $RT_STATES[RT_NEW],
                'categories' => $cats,
                'subject' => trans('SMS from $a', (empty($phone) ? trans("unknown") : $formatted_phone)),
                'body' => $message,
                'url' => $lms_url . '?m=rtticketview&id=',
            );
            $headers['Subject'] = $LMS->ReplaceNotificationSymbols(ConfigHelper::getConfig('rt.notification_mail_subject', ConfigHelper::getConfig('phpui.helpdesk_notification_mail_subject')), $params);
            $params['customerinfo'] = $mail_customerinfo ?? null;
            $message = $LMS->ReplaceNotificationSymbols(ConfigHelper::getConfig('rt.notification_mail_body', ConfigHelper::getConfig('phpui.helpdesk_notification_mail_body')), $params);
            $params['customerinfo'] = $sms_customerinfo ?? null;
            $sms_body = $LMS->ReplaceNotificationSymbols(ConfigHelper::getConfig('rt.notification_sms_body', ConfigHelper::getConfig('phpui.helpdesk_notification_sms_body')), $params);

            $LMS->NotifyUsers(array(
                'queue' => $queueid,
                'ticketid' => $tid,
                'mail_headers' => $headers,
                'mail_body' => $message,
                'sms_body' => $sms_body,
            ));
        }
    } else {
        die('Message file \'' . $message_file . '\' doesn\'t exist!' . PHP_EOL);
    }
}

if ($http_mode && !empty($message_files)) {
    foreach ($message_files as $message_file) {
        @unlink($message_file);
    }
}
