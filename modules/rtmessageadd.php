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

check_file_uploads();

$LMS->InitXajax();
include(MODULES_DIR . DIRECTORY_SEPARATOR . 'rtmessagexajax.inc.php');
$SMARTY->assign('xajax', $LMS->RunXajax());

$notification_options_by_division_ids = array(
    0 => array(
        'notification_sender_name' => ConfigHelper::getConfig('rt.sender_name', ConfigHelper::getConfig('phpui.helpdesk_sender_name')),
        'customer_notification_mail_subject' => ConfigHelper::getConfig('rt.customer_notification_mail_subject', ConfigHelper::getConfig('phpui.helpdesk_customer_notification_mail_subject', '[RT#%tid] %subject')),
        'new_message_preserve_no_owner' => ConfigHelper::checkConfig('rt.new_message_preserve_no_owner'),
        'notification_customerinfo' => ConfigHelper::checkConfig('rt.notification_customerinfo', ConfigHelper::checkConfig('phpui.helpdesk_customerinfo')),
        'notification_mail_body_customerinfo_format' => ConfigHelper::getConfig('rt.notification_mail_body_customerinfo_format', ConfigHelper::getConfig('phpui.helpdesk_customerinfo_mail_body')),
        'notification_sms_body_customerinfo_format' => ConfigHelper::getConfig('rt.notification_sms_body_customerinfo_format', ConfigHelper::getConfig('phpui.helpdesk_customerinfo_sms_body')),
        'notification_mail_subject' => ConfigHelper::getConfig('rt.notification_mail_subject', ConfigHelper::getConfig('phpui.helpdesk_notification_mail_subject')),
        'notification_mail_body' => ConfigHelper::getConfig('rt.notification_mail_body', ConfigHelper::getConfig('phpui.helpdesk_notification_mail_body')),
        'notification_sms_body' => ConfigHelper::getConfig('rt.notification_sms_body', ConfigHelper::getConfig('phpui.helpdesk_notification_sms_body')),
    ),
);

$userid = Auth::GetCurrentUser();
$block_ticket_close_with_open_events = ConfigHelper::checkConfig('rt.block_ticket_close_with_open_events', ConfigHelper::checkConfig('phpui.helpdesk_block_ticket_close_with_open_events'));
$check_owner_verifier_conflict = ConfigHelper::checkConfig('rt.check_owner_verifier_conflict', ConfigHelper::checkConfig('phpui.helpdesk_check_owner_verifier_conflict', true));
$allow_all_users_modify_deadline = ConfigHelper::checkConfig('rt.allow_all_users_modify_deadline', ConfigHelper::checkConfig('phpui.helpdesk_allow_all_users_modify_deadline'));
$service = ConfigHelper::getConfig('sms.service');
$aet = ConfigHelper::getConfig('rt.allow_modify_resolved_tickets_newer_than', 86400);
$author_notify = ConfigHelper::checkConfig('rt.author_notify');
$superuser = ConfigHelper::checkPrivilege('superuser');
$notify = ConfigHelper::checkConfig('rt.notify', ConfigHelper::checkConfig('phpui.helpdesk_notify'));
$customer_notify = ConfigHelper::checkConfig('rt.customer_notify', ConfigHelper::checkConfig('phpui.helpdesk_customer_notify'));
$content_type = ConfigHelper::getConfig('rt.default_message_reply_content_type', 'text');
$quote_body = ConfigHelper::checkConfig('rt.quote_body', ConfigHelper::checkConfig('phpui.helpdesk_reply_body'));

$categories = $LMS->GetUserCategories($userid);
if (empty($categories)) {
    $categories = array();
}

$contacts = array(
    'mails' => array(),
    'phones' => array(),
);

if (isset($_POST['message'])) {
    $message = $_POST['message'];

    $group_reply = is_array($message['ticketid']);
    if ($group_reply) {
        $tickets = Utils::filterIntegers($message['ticketid']);
        if (empty($tickets)) {
            die;
        }

        $message['destination'] = '';
        $message['inreplyto'] = null;
        $message['sender'] = 'user';
    } else {
        if (!intval($message['ticketid']) || !($LMS->CheckTicketAccess($message['ticketid']) & RT_RIGHT_WRITE)) {
            access_denied();
        }

        $tickets = array($message['ticketid']);

        if (isset($message['destination'])) {
            if ($message['destination'] != '' && !check_email($message['destination'])) {
                $error['destination'] = trans('Incorrect email!');
            }

            if ($message['destination'] != '' && $message['sender'] == 'customer') {
                $error['destination'] = trans('Customer cannot send message!');
            }
        }

        $ticket = $LMS->GetTicketContents($message['ticketid']);
        if ($block_ticket_close_with_open_events) {
            $oec = $ticket['openeventcount'];
            if ($message['state'] == RT_RESOLVED && !empty($oec)) {
                $error['state'] = trans('Ticket have open assigned events!');
            }
        }
    }

    foreach ($tickets as $ticketid) {
        $LMS->MarkTicketAsRead($ticketid);
        if ($message['watching'] == 1) {
            $LMS->changeTicketWatching($ticketid, 1);
        } elseif ($message['watching'] == 0) {
            $LMS->changeTicketWatching($ticketid, 0);
        }
    }

    if ($message['subject'] == '') {
        $error['subject'] = trans('Message subject not specified!');
    } else if (strlen($message['subject']) > 255) {
        $error['subject'] = trans('Subject must contain less than 255 characters!');
    }

    if ($message['body'] == '') {
        $error['body'] = trans('Message body not specified!');
    }

    if ($check_owner_verifier_conflict && !empty($message['verifierid']) && $message['verifierid'] == $message['owner']) {
        $error['verifierid'] = trans('Ticket owner could not be the same as verifier!');
        $error['owner'] = trans('Ticket verifier could not be the same as owner!');
    }

    // TODO: verifierid/deadline validation for group reply
    $deadline = datetime_to_timestamp($message['deadline']);
    if (!$group_reply && $deadline != $ticket['deadline']) {
        if (!$allow_all_users_modify_deadline && !empty($message['verifierid']) && $message['verifierid'] != $userid) {
            $error['deadline'] = trans('If verifier is set then he\'s the only person who can change deadline!');
            $message['deadline'] = $ticket['deadline'];
        }
        if ($deadline && $deadline < time()) {
            $error['deadline'] = trans('Ticket deadline could not be set in past!');
        }
    }

    $result = handle_file_uploads('files', $error);
    extract($result);
    $SMARTY->assign('fileupload', $fileupload);

    $hook_data = $LMS->executeHook(
        'rtmessageadd_validation_before_submit',
        array(
            'message' => $message,
            'error' => $error,
        )
    );
    $message = $hook_data['message'];
    $error = $hook_data['error'];

    if (!$error) {
        $message['contenttype'] = isset($message['wysiwyg']) && isset($message['wysiwyg']['body']) && ConfigHelper::checkValue($message['wysiwyg']['body'])
            ? 'text/html' : 'text/plain';

        $message['categories'] = isset($message['categories']) && is_array($message['categories']) ? array_flip($message['categories']) : array();
        $user = $LMS->GetUserInfo($userid);

        $attachments = null;

        if (!empty($files)) {
            foreach ($files as &$file) {
                $attachments[] = array(
                    'content_type' => $file['type'],
                    'filename' => $file['name'],
                    'data' => file_get_contents($tmppath . DIRECTORY_SEPARATOR . $file['name']),
                );
                $file['name'] = $tmppath . DIRECTORY_SEPARATOR . $file['name'];
            }
            unset($file);
        } else {
            $files = array();
        }

        $message['userid'] = $userid;

        $smtp_options = $LMS->GetRTSmtpOptions();
        $smtp_options_by_division_ids = array(
            0 => $smtp_options,
            $divisionid => $smtp_options,
        );

        $notification_options_by_division_ids[$divisionid] = $notification_options_by_division_ids[0];
        extract($notification_options_by_division_ids[0]);

        $ticket_divisionid = $divisionid;

        foreach ($tickets as $ticketid) {
            $new_ticket_divisionid = $LMS->getDivisionIdByTicketId($ticketid);

            if (empty($new_ticket_divisionid)) {
                $smtp_options = $smtp_options_by_division_ids[0];

                extract($notification_options_by_division_ids[0]);
            } elseif ($new_ticket_divisionid != $ticket_divisionid) {
                $ticket_divisionid = $new_ticket_divisionid;

                if (!isset($smtp_options_by_division_ids[$ticket_divisionid])) {
                    ConfigHelper::setFilter($ticket_divisionid, Auth::GetCurrentUser());

                    $smtp_options_by_division_ids[$ticket_divisionid] = $LMS->GetRTSmtpOptions();

                    $notification_options_by_division_ids[$ticket_divisionid] = array(
                        'notification_sender_name' => ConfigHelper::getConfig('rt.sender_name', ConfigHelper::getConfig('phpui.helpdesk_sender_name')),
                        'customer_notification_mail_subject' => ConfigHelper::getConfig('rt.customer_notification_mail_subject', ConfigHelper::getConfig('phpui.helpdesk_customer_notification_mail_subject', '[RT#%tid] %subject')),
                        'new_message_preserve_no_owner' => ConfigHelper::checkConfig('rt.new_message_preserve_no_owner'),
                        'notification_customerinfo' => ConfigHelper::checkConfig('rt.notification_customerinfo', ConfigHelper::checkConfig('phpui.helpdesk_customerinfo')),
                        'notification_mail_body_customerinfo_format' => ConfigHelper::getConfig('rt.notification_mail_body_customerinfo_format', ConfigHelper::getConfig('phpui.helpdesk_customerinfo_mail_body')),
                        'notification_sms_body_customerinfo_format' => ConfigHelper::getConfig('rt.notification_sms_body_customerinfo_format', ConfigHelper::getConfig('phpui.helpdesk_customerinfo_sms_body')),
                        'notification_mail_subject' => ConfigHelper::getConfig('rt.notification_mail_subject', ConfigHelper::getConfig('phpui.helpdesk_notification_mail_subject')),
                        'notification_mail_body' => ConfigHelper::getConfig('rt.notification_mail_body', ConfigHelper::getConfig('phpui.helpdesk_notification_mail_body')),
                        'notification_sms_body' => ConfigHelper::getConfig('rt.notification_sms_body', ConfigHelper::getConfig('phpui.helpdesk_notification_sms_body')),
                    );
                }

                $smtp_options = $smtp_options_by_division_ids[$ticket_divisionid];

                extract($notification_options_by_division_ids[$ticket_divisionid]);
            }

            $queue = $LMS->GetQueueByTicketId($ticketid);
            if ($message['queueid'] != -100 && $message['queueid'] != $queue['id']) {
                $queue = $LMS->GetQueue($message['queueid'], true);
            }

            $requestor_mail = $LMS->GetTicketRequestorMail($ticketid);

            $message['queue'] = $queue['id'];

            $message['messageid'] = '<msg.' . $queue['id'] . '.' . $ticketid . '.' . time()
                . '@rtsystem.' . gethostname() . '>';

            $message['customerid'] = null;

            $mailfname = '';

            if (!empty($notification_sender_name)) {
                $mailfname = $notification_sender_name;

                if ($mailfname == 'queue') {
                    $mailfname = $queue['name'];
                } elseif ($mailfname == 'user') {
                    $mailfname = $user['name'];
                }

                $mailfname = '"' . $mailfname . '"';
            }

            $headers = array();

            if ($message['references']) {
                $headers['References'] = $message['references'];
                $references = explode(' ', $message['references']);
                $headers['In-Reply-To'] = array_pop($references);
            }
            $headers['Message-ID'] = $message['messageid'];

            if ($message['userid'] && ($user['email'] || $queue['email'] || $requestor_mail)) {
                $mailfrom = $LMS->DetermineSenderEmail($user['email'], $queue['email'], $requestor_mail);

                $message['mailfrom'] = $mailfrom;
                $headers['Date'] = date('r');
                $headers['From'] = $mailfname . ' <' . $message['mailfrom'] . '>';
                $headers['Subject'] = preg_replace_callback(
                    '/%(\\d*)tid/',
                    function ($m) use ($ticketid) {
                        return sprintf('%0' . $m[1] . 'd', $ticketid);
                    },
                    str_replace('%subject', $message['subject'], $customer_notification_mail_subject)
                );
                $headers['Reply-To'] = $headers['From'];

                if ($message['contenttype'] == 'text/html') {
                    $headers['X-LMS-Format'] = 'html';
                }

                if (!empty($message['contacts']['mails'])) {
                    $toemails = array();
                    $ccemails = array();
                    foreach ($message['contacts']['mails'] as $address => $contact) {
                        $display = empty($message['contacts']['maildisplays'][$address]) ? '' : qp_encode($message['contacts']['maildisplays'][$address]) . ' ';
                        $message_source = $message['contacts']['mailsources'][$address];
                        if ($message_source == 'requestor_mail' || $message_source == 'mailfrom' || $message_source == 'customer' || $message_source = 'reply-to') {
                            $toemails[] = array(
                                'name' => $display,
                                'email' => $contact,
                            );
                        } else {
                            $ccemails[] = array(
                                'name' => $display,
                                'email' => $contact,
                            );
                        }
                    }
                    if (!empty($toemails)) {
                        $headers['To'] = implode(
                            ',',
                            array_map(function ($toemail) {
                                return $toemail['name'] . ' <' . $toemail['email']  . '>';
                            }, $toemails)
                        );
                    }
                    if (!empty($ccemails)) {
                        $headers['Cc'] = implode(
                            ',',
                            array_map(function ($ccemail) {
                                return $ccemail['name'] . ' <' . $ccemail['email']  . '>';
                            }, $ccemails)
                        );
                    }
                }
            } else {
                if ($message['customerid'] || $message['userid']) {
                    $message['mailfrom'] = '';
                }
                $message['headers'] = '';
                $message['replyto'] = '';
            }

            $message['headers'] = $headers;
            $message['ticketid'] = $ticketid;
            $msgid = $LMS->TicketMessageAdd($message, $files);

            $hook_data = $LMS->executeHook(
                'rtmessageadd_after_submit',
                array(
                    'msgid' => $msgid,
                    'message' => $message,
                )
            );
            $message = $hook_data['message'];

            // setting status and the ticket owner
            if (isset($message['resolve'])) {
                $message['state'] = RT_RESOLVED;
            }

            $owner = $DB->GetOne('SELECT owner FROM rttickets WHERE id = ?', array($ticketid));

            if ($group_reply) {
                $props = array();
                if ($message['owner'] == -100) {
                    if (!$owner) {
                        if (!$new_message_preserve_no_owner) {
                            $message['owner'] = $userid;
                        }
                        $props['owner'] = empty($message['owner']) ? null : $message['owner'];
                    }
                } else {
                    $props['owner'] = empty($message['owner']) ? null : $message['owner'];
                }
                if ($message['cause'] != -1) {
                    $props['cause'] = $message['cause'];
                }
                if ($message['state'] != -1) {
                    $props['state'] = $message['state'];
                }
                if ($message['priority'] != -100) {
                    $props['priority'] = $message['priority'];
                }
                if ($message['queueid'] != -100) {
                    $props['queueid'] = $message['queueid'];
                }
                if ($message['verifierid'] != -1) {
                    $props['verifierid'] = empty($message['verifierid']) ? null : $message['verifierid'];
                }
                if ($message['deadline']) {
                    $props['deadline'] = empty($message['deadline']) ? null : $deadline;
                }
            } else {
                if (!$new_message_preserve_no_owner && !$owner && empty($message['owner'])) {
                    $message['owner'] = $userid;
                }
                $props = array(
                    'queueid' => $message['queueid'],
                    'owner' => empty($message['owner']) ? null : $message['owner'],
                    'cause' => $message['cause'],
                    'state' => $message['state'],
                    'source' => $message['source'],
                    'priority' => $message['priority'] ?? null,
                    'verifierid' => empty($message['verifierid']) ? null : $message['verifierid'],
                    'deadline' => empty($message['deadline']) ? null : $deadline,
                );
            }

            if ($message['category_change']) {
                $props['category_change'] = $message['category_change'];
                $props['categories'] = isset($message['categories']) ? array_keys($message['categories']) : array();
            }

            $LMS->TicketChange($ticketid, $props);

            $LMS->prepareMessageTemplates('rt');

            // customer notification via e-mail when we reply to ticket message created from customer post
            if (isset($message['mailnotify'])) {
                if ($group_reply) {
                    if (!empty($requestor_mail)) {
                        $headers['To'] = $requestor_mail;
                        $recipients = $requestor_mail;
                    } else {
                        $recipients = '';
                    }
                } else {
                    $recipients = empty($toemails) ? '' : implode(',', Utils::array_column($toemails, 'email'));
                }
                if ($recipients) {
                    $LMS->SendMail(
                        $recipients,
                        $headers,
                        $LMS->applyMessageTemplates($message['body'], $message['contenttype']),
                        $attachments,
                        null,
                        $smtp_options
                    );
                }
            }
            unset($headers['Cc']);

            // customer notification via sms when we reply to ticket message created from customer sms
            if (isset($message['smsnotify']) && !empty($service)) {
                $phones = array();
                if ($group_reply) {
                    $phone = $LMS->GetTicketRequestorPhone($ticketid);
                    if (!empty($phone)) {
                        $phones[] = $phone;
                    }
                } else {
                    if (isset($message['contacts']['phones'])) {
                        foreach ($message['contacts']['phones'] as $phone) {
                            $phones[] = $phone;
                        }
                    }
                }
                if (!empty($phones)) {
                    $sms_body = str_replace(
                        array(
                            '%body',
                            '%username',
                        ),
                        array(
                            $message['body'],
                            $layout['logname'],
                        ),
                        ConfigHelper::getConfig('sms.customer_message_template', '%body')
                    );
                    $sms_body = preg_replace('/\r?\n/', ' ', $sms_body);
                    foreach ($phones as $phone) {
                        $LMS->SendSMS($phone, $sms_body);
                    }
                }
            }

            // User notifications
            if (isset($message['notify']) || isset($message['customernotify']) || !empty($message['verifierid'])) {
                $mailfname = '';
                if (!empty($notification_sender_name)) {
                    $mailfname = $notification_sender_name;

                    if ($mailfname == 'queue') {
                        $mailfname = $queue['name'];
                    } elseif ($mailfname == 'user') {
                        $mailfname = $user['name'];
                    }

                    $mailfname = '"' . $mailfname . '"';
                }

                $mailfrom = $LMS->DetermineSenderEmail($user['email'], $queue['email'], $requestor_mail);

                $ticketdata = $LMS->GetTicketContents($ticketid);

                $headers['From'] = $mailfname . ' <' . $mailfrom . '>';
                $headers['Reply-To'] = $headers['From'];

                if ($ticketdata['customerid']) {
                    $info = $LMS->GetCustomer($ticketdata['customerid'], true);

                    $emails = array_map(
                        function ($contact) {
                            return $contact['fullname'];
                        },
                        array_filter(
                            $LMS->GetCustomerContacts($ticketdata['customerid'], CONTACT_EMAIL),
                            function ($contact) {
                                return $contact['type'] & CONTACT_HELPDESK_NOTIFICATIONS;
                            }
                        )
                    );

                    $all_phones = array_filter(
                        $LMS->GetCustomerContacts($ticketdata['customerid'], CONTACT_LANDLINE | CONTACT_MOBILE),
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

                    if ((isset($message['notify']) || !empty($message['verifierid'])) && $notification_customerinfo) {
                        $params = array(
                            'id' => $ticketid,
                            'customerid' => $ticketdata['customerid'],
                            'customer' => $info,
                            'emails' => $emails,
                            'phones' => $phones,
                        );
                        $mail_customerinfo = $LMS->ReplaceNotificationCustomerSymbols(
                            $notification_mail_body_customerinfo_format,
                            $params
                        );
                        $sms_customerinfo = $LMS->ReplaceNotificationCustomerSymbols(
                            $notification_sms_body_customerinfo_format,
                            $params
                        );
                    }
                } elseif ((isset($message['notify']) || !empty($message['verifierid'])) && $notification_customerinfo) {
                    $mail_customerinfo = "\n\n-- \n" . trans('Customer:') . ' ' . $ticketdata['requestor'];
                    $sms_customerinfo = "\n" . trans('Customer:') . ' ' . $ticketdata['requestor'];
                }

                if (isset($message['notify'])) {
                    $params = array(
                        'id' => $ticketid,
                        'author' => Auth::GetCurrentUserName(),
                        'queue' => $queue['name'],
                        'messageid' => $msgid ?? null,
                        'customerid' => empty($message['customerid']) ? $ticketdata['customerid'] : $message['customerid'],
                        'status' => $ticketdata['status'],
                        'categories' => $ticketdata['categorynames'],
                        'priority' => isset($ticketdata['priority']) && is_numeric($ticketdata['priority']) ? $RT_PRIORITIES[$ticketdata['priority']] : trans('undefined'),
                        'deadline' => $ticketdata['deadline'],
                        'subject' => $LMS->cleanupTicketSubject($message['subject']),
                        'body' => $message['body'],
                        'attachments' => &$attachments,
                    );
                    if ($ticketdata['priority']) {
                        $headers['X-Priority'] = $RT_MAIL_PRIORITIES[$ticketdata['priority']];
                    }
                    $headers['Subject'] = $LMS->ReplaceNotificationSymbols($notification_mail_subject, $params);

                    $params['customerinfo'] = isset($mail_customerinfo)
                        ? ($message['contenttype'] == 'text/html' ? str_replace("\n", '<br>', $mail_customerinfo) : $mail_customerinfo)
                        : null;
                    $params['contenttype'] = $message['contenttype'];
                    $body = $LMS->ReplaceNotificationSymbols($notification_mail_body, $params);

                    if ($message['contenttype'] == 'text/html') {
                        $params['body'] = trans('(HTML content has been omitted)');
                        $headers['X-LMS-Format'] = 'html';
                    }

                    $params['customerinfo'] = $sms_customerinfo ?? null;
                    $params['contenttype'] = 'text/plain';
                    $sms_body = $LMS->ReplaceNotificationSymbols($notification_sms_body, $params);

                    // Don't notify verifier adding message
                    $currentuser = $userid;
                    if (empty($message['verifierid'])
                        || !$author_notify
                        || $props['verifierid'] == $currentuser
                        || $message['verifierid'] == $currentuser) {
                        $message['verifierid'] = null;
                    }

                    $LMS->NotifyUsers(array(
                        'queue' => $queue['id'],
                        'ticketid' => $ticketid,
                        'verifierid' => $message['verifierid'],
                        'mail_headers' => $headers,
                        'mail_body' => $body,
                        'sms_body' => $sms_body,
                        'contenttype' => $message['contenttype'],
                        'attachments' => &$attachments,
                        'recipients' => ($message['notify'] ? RT_NOTIFICATION_USER : 0)
                            | (empty($message['verifierid']) ? 0 : RT_NOTIFICATION_VERIFIER),
                        'smtp_options' => $smtp_options,
                    ));
                }
            }

            if (isset($message['customernotify']) && !empty($ticketdata['customerid']) && (!empty($emails) || !empty($mobile_phones))) {
                $queuedata = $LMS->GetQueueByTicketId($ticketid);

                $title = $DB->GetOne('SELECT subject FROM rtmessages WHERE ticketid = ?
							ORDER BY id LIMIT 1', array($ticketid));
                if (!empty($queuedata['newmessagesubject']) && !empty($queuedata['newmessagebody']) && !empty($emails)) {
                    $custmail_subject = $queuedata['newmessagesubject'];
                    $custmail_subject = preg_replace_callback(
                        '/%(\\d*)tid/',
                        function ($m) use ($ticketid) {
                            return sprintf('%0' . $m[1] . 'd', $ticketid);
                        },
                        $custmail_subject
                    );
                    $custmail_subject = str_replace('%title', $title, $custmail_subject);
                    $custmail_body = $queuedata['newmessagebody'];
                    $custmail_body = preg_replace_callback(
                        '/%(\\d*)tid/',
                        function ($m) use ($ticketid) {
                            return sprintf('%0' . $m[1] . 'd', $ticketid);
                        },
                        $custmail_body
                    );
                    $custmail_body = str_replace('%cid', $ticketdata['customerid'], $custmail_body);
                    $custmail_body = str_replace('%pin', $info['pin'], $custmail_body);
                    $custmail_body = str_replace('%customername', $info['customername'], $custmail_body);
                    $custmail_body = str_replace('%title', $title, $custmail_body);
                    $custmail_body = str_replace('%body', $message['body'], $custmail_body);
                    $custmail_headers = array(
                        'From' => $headers['From'],
                        'Reply-To' => $headers['From'],
                        'Subject' => $custmail_subject,
                    );

                    $custmail_body = $LMS->applyMessageTemplates($custmail_body);

                    foreach ($emails as $email) {
                        $custmail_headers['To'] = '<' . $email . '>';
                        $LMS->SendMail($email, $custmail_headers, $custmail_body, null, null, $smtp_options);
                    }
                }
                if (!empty($queuedata['newmessagesmsbody']) && !empty($mobile_phones)) {
                    $custsms_body = $queuedata['newmessagesmsbody'];
                    $custsms_body = preg_replace_callback(
                        '/%(\\d*)tid/',
                        function ($m) use ($ticketid) {
                            return sprintf('%0' . $m[1] . 'd', $ticketid);
                        },
                        $custsms_body
                    );
                    $custsms_body = str_replace('%cid', $ticketdata['customerid'], $custsms_body);
                    $custsms_body = str_replace('%pin', $info['pin'], $custsms_body);
                    $custsms_body = str_replace('%customername', $info['customername'], $custsms_body);
                    $custsms_body = str_replace('%title', $title, $custsms_body);
                    $custsms_body = str_replace('%body', $message['body'], $custsms_body);
                    $custsms_body = str_replace('%service', $ticket['service'], $custsms_body);

                    foreach ($mobile_phones as $phone) {
                        $LMS->SendSMS($phone['contact'], $custsms_body);
                    }
                }
            }
        }

        // deletes uploaded files
        if (!empty($files) && !empty($tmppath)) {
            rrmdir($tmppath);
        }

        $backto = $SESSION->remove_history_entry();
        if (strpos($backto, 'rtqueueview') === false && isset($msgid)) {
            $SESSION->redirect('?m=rtticketview&id=' . $message['ticketid'] . '#rtmessage-' . $msgid);
        } elseif (strpos($backto, 'rtqueueview') !== false) {
            $SESSION->redirect('?' . $backto
                . ($SESSION->is_set('backid') ? '#' . $SESSION->get('backid') : ''));
        } else {
            $SESSION->redirect('?' . $backto);
        }
    }

    if (!empty($message['categories'])) {
        $message['categories'] = array_flip($message['categories']);
    }
} else {
    if ($_GET['ticketid']) {
        if (is_array($_GET['ticketid'])) {
            $ticketid = Utils::filterIntegers($_GET['ticketid']);
            if (empty($ticketid)) {
                die;
            }
            $message['customernotify'] = 1;
            $message['state'] = -1;
            $message['cause'] = -1;
            $message['queueid'] = -100;
            $message['owner'] = -100;
            $message['priority'] = -100;
            $message['deadline'] = 0;
            $message['verifierid'] = -1;
        } else {
            if (!($LMS->CheckTicketAccess($_GET['ticketid']) & RT_RIGHT_WRITE)) {
                access_denied();
            }
            $ticketid = intval($_GET['ticketid']);
            if (empty($ticketid)) {
                die;
            }
            $queue = $LMS->GetQueueByTicketId($ticketid);
            $message = $LMS->GetTicketContents($ticketid);
            if ($message['state'] == RT_NEW) {
                $message['state'] = RT_OPEN;
            }
            if (($queue['newmessagesubject'] && $queue['newmessagebody']) || $queue['newmessagesmsbody']) {
                $message['customernotify'] = 1;
            }
            if ($message['state'] == RT_RESOLVED && !$superuser && $aet && (time() - $message['resolvetime'] > $aet)) {
                die("Cannot send message - ticket was resolved more than " . $aet . " seconds.");
            }
        }
        $message['category_change'] = 0;
        if ($notify) {
            $message['notify'] = true;
        }
    }

    $message['ticketid'] = $ticketid;

    if (is_array($ticketid)) {
        foreach ($ticketid as $id) {
            $LMS->MarkTicketAsRead($id);
        }
        if ($customer_notify) {
            $message['smsnotify'] = true;
        }

        $layout['pagetitle'] = trans('New Message (group action for $a tickets)', count($ticketid));
    } else {
        $LMS->MarkTicketAsRead($ticketid);
        $message['customerid'] = $DB->GetOne('SELECT customerid FROM rttickets WHERE id = ?', array($ticketid));

        if (isset($_GET['id'])) {
            $reply = $LMS->GetMessage($_GET['id']);
            switch ($content_type) {
                case 'html':
                    $message['contenttype'] = 'text/html';
                    break;
                case 'predecessor':
                    $message['contenttype'] = $reply['contenttype'];
                    break;
                case 'text':
                default:
                    $message['contenttype'] = 'text/plain';
                    break;
            }
            $message['wysiwyg']['body'] = $message['contenttype'] == 'text/html' ? 'true' : 'false';

            $message['mailfrom'] = array();

            if (!empty($reply['mailfrom']) && !empty($reply['customerid'])
                && preg_match('/^(?:(?<name>.*) )?<?(?<mail>[a-z0-9_\.-]+@[\da-z\.-]+\.[a-z\.]{2,6})>?$/iA', $reply['mailfrom'], $m)) {
                $message['mailfrom'] = array(
                    $m['mail'] => array(
                        'contact' => $m['mail'],
                        'display' => $m['name'],
                        'source' => 'mailfrom',
                    )
                );
            }

            if (!empty($reply['cc'])) {
                foreach ($reply['cc'] as &$cc) {
                    if (preg_match('/^(?:(?<name>.*) )?<?(?<mail>[a-z0-9_\.-]+@[\da-z\.-]+\.[a-z\.]{2,6})>?$/iA', $cc['address'], $m)) {
                        $cc['contact'] = $m['mail'];
                        $cc['display'] = $m['name'] ?? '';
                        $cc['source'] = 'carbon-copy';
                    }
                }
                unset($cc);

                $message['mailfrom'] = array_merge($message['mailfrom'], $reply['cc']);
            }

            if (!empty($reply['replyto']) && preg_match('/^(?:(?<name>.*) )?<?(?<mail>[a-z0-9_\.-]+@[\da-z\.-]+\.[a-z\.]{2,6})>?$/iA', $reply['replyto'], $m)) {
                $message['mailfrom'][$m['mail']] = array(
                    'contact' => $m['mail'],
                    'display' => $m['name'] ?? '',
                    'source' => 'reply-to',
                );
            }

            if (!empty($message['mailfrom']) && $customer_notify) {
                $message['mailnotify'] = true;
            }

            if ($reply['phonefrom']) {
                $message['phonefrom'] = $reply['phonefrom'];
                if ($customer_notify) {
                    $message['smsnotify'] = true;
                }
            }

            if ((!isset($message['destination']) || !$message['destination']) && !$reply['userid']) {
                $message['destination'] = $LMS->GetCustomerEmail($message['customerid'], CONTACT_HELPDESK_NOTIFICATIONS, CONTACT_DISABLED);
                if (!empty($message['destination'])) {
                    $message['destination'] = implode(',', $message['destination']);
                }
            }

            $message['subject'] = 'Re: ' . $LMS->cleanupTicketSubject($reply['subject']);
            $message['inreplyto'] = $reply['id'];
            $message['references'] = implode(' ', $reply['references']);
            $message['cc'] = $reply['cc'];

            if ($quote_body || isset($_GET['citing'])) {
                $message['body'] = '';
                if ($message['contenttype'] == 'text/html') {
                    $message['body'] = '<br><blockquote>' . $reply['body'] . '</blockquote>';
                } else {
                    $body = explode("\n", textwrap(strip_tags($reply['body']), 74));
                    foreach ($body as $line) {
                        $message['body'] .= '> ' . $line . "\n";
                    }
                    $message['body'] .= "\n";
                }
            }
        } else {
            $reply = $LMS->GetFirstMessage($ticketid);
            $message['inreplyto'] = $reply['id'];
            $message['references'] = implode(' ', $reply['references']);
        }

        $layout['pagetitle'] = trans('New Message');
    }

    $message['ticketid'] = $ticketid;
}

$SMARTY->assign('error', $error);

foreach ($categories as &$category) {
    $category['checked'] = isset($message['categories'][$category['id']]);
}
unset($category);

$hook_data = $LMS->executeHook(
    'rtmessageadd_before_display',
    array(
        'message' => $message,
        'smarty' => $SMARTY
    )
);
$message = $hook_data['message'];

if (!is_array($message['ticketid'])) {
    $ticket = $LMS->GetTicketContents($message['ticketid']);
    $LMS->getTicketImageGalleries($ticket);
    $SMARTY->assign('ticket', $ticket);
    if (!isset($_POST['message'])) {
        $message['source'] = $ticket['source'];
        $message['priority'] = $ticket['priority'];
        $message['verifierid'] = $ticket['verifierid'];
        $message['deadline'] = $ticket['deadline'];
        if ($message['state'] == RT_NEW) {
            $message['state'] = RT_OPEN;
        }
    }

    // collect carbon copy email addresses from ticket, message to which you reply and customer email contacts
    if (!empty($message['customerid'])) {
        $customercontacts = $LMS->GetCustomerContacts($message['customerid'], CONTACT_EMAIL);
        if (empty($customercontacts)) {
            $customercontacts = array();
        }
        foreach ($customercontacts as &$customercontact) {
            if (!($customercontact['type'] & CONTACT_DISABLED)) {
                $customercontact['checked'] = 0;
                $customercontact['display'] = $customercontact['name'];
                $customercontact['source'] = ($customercontact['type'] & CONTACT_HELPDESK_NOTIFICATIONS) ? 'customer' : 'customer-unflagged';
                $contacts['mails'][$customercontact['contact']] = $customercontact;
            }
        }
        unset($customercontact);
    }

    if (!empty($ticket['requestor_mail'])) {
        $contacts['mails'][$ticket['requestor_mail']] = array(
            'contact' => $ticket['requestor_mail'],
            'name' => trans('from ticket'),
            'display' => $ticket['requestor'],
            'source' => 'requestor_mail',
            'checked' => 0,
        );
    }

    $from_mail_addresses = false;
    if (!empty($message['mailfrom'])) {
        foreach ($message['mailfrom'] as $address) {
            switch ($address['source']) {
                case 'carbon-copy':
                    $contact_name = trans('from message "Copy" header');
                    break;
                case 'reply-to':
                    $contact_name = trans('from message "Reply" header');
                    break;
                default:
                    $contact_name = trans('from message "From" header');
                    break;
            }
            $contacts['mails'][$address['contact']] = array(
                'contact' => $address['contact'],
                'name' => $contact_name,
                'display' => $address['display'],
                'source' => $address['source'],
                'checked' => 0,
            );
            if ($address['source'] == 'mailfrom') {
                $from_mail_addresses = true;
            }
        }
    }

    $replyto_cc_mail_addresses = false;
    if (!empty($message['inreplyto'])) {
        $reply = $LMS->GetMessage($message['inreplyto']);

        if (!empty($reply['cc'])) {
            foreach ($reply['cc'] as $cc) {
                if (preg_match('/^(?:(?<name>.*) )?<?(?<mail>[a-z0-9_\.-]+@[\da-z\.-]+\.[a-z\.]{2,6})>?$/iA', $cc['address'], $m)) {
                    $contacts['mails'][$m['mail']] = array(
                        'contact' => $m['mail'],
                        'name' => trans('from message "Copy" header'),
                        'display' => $m['name'] ?? '',
                        'source' => 'carbon-copy',
                        'checked' => empty($reply['customerid']) ? 0 : 1,
                    );
                    $replyto_cc_mail_addresses = true;
                }
            }
        }

        if (!empty($reply['replyto'])
            && preg_match('/^(?:(?<name>.*) )?<?(?<mail>[a-z0-9_\.-]+@[\da-z\.-]+\.[a-z\.]{2,6})>?$/iA', $reply['replyto'], $m)) {
            $contacts['mails'][$m['mail']] = array(
                'contact' => $m['mail'],
                'name' => trans('from message "Reply" header'),
                'display' => $m['name'] ?? '',
                'source' => 'reply-to',
                'checked' => 1,
            );
            $replyto_cc_mail_addresses = true;
        }
    }

    if ($replyto_cc_mail_addresses && $from_mail_addresses) {
        $contacts['mails'] = array_map(
            function ($contact) {
                if ($contact['source'] == 'mailfrom') {
                    $contact['checked'] = 0;
                }
                return $contact;
            },
            $contacts['mails']
        );
    }

    // collect phone numbers from ticket, message to which you reply and customer mobile phone contacts
    if (!empty($ticket['requestor_phone'])) {
        $contacts['phones'][$ticket['requestor_phone']] = array(
            'contact' => $ticket['requestor_phone'],
            'name' => trans('from ticket'),
            'source' => 'ticket',
            'checked' => 1,
        );
    }
    if (!empty($message['phonefrom'])) {
        $contacts['phones'][$message['phonefrom']] = array(
            'contact' => $message['phonefrom'],
            'name' => trans('from message'),
            'source' => 'message',
            'checked' => 1,
        );
    }

    if (!empty($message['customerid'])) {
        $customercontacts = $LMS->GetCustomerContacts($message['customerid'], CONTACT_MOBILE);
        if (empty($customercontacts)) {
            $customercontacts = array();
        }
        foreach ($customercontacts as &$customercontact) {
            if (($customercontact['type'] & (CONTACT_HELPDESK_NOTIFICATIONS | CONTACT_DISABLED)) == CONTACT_HELPDESK_NOTIFICATIONS) {
                $customercontact['checked'] = 0;
                $contacts['phones'][$customercontact['contact']] = $customercontact;
            }
        }
        unset($customercontact);
    }

    if (!empty($contacts['mails'])) {
        $default_notified_email_sources = ConfigHelper::getConfig('rt.default_notified_email_sources', '', true);
        $default_notified_email_sources = preg_split('/([\s]+|[\s]*,[\s]*)/', mb_strtolower($default_notified_email_sources), -1, PREG_SPLIT_NO_EMPTY);
        $default_notified_email_sources = array_flip($default_notified_email_sources);

        foreach ($contacts['mails'] as $email => &$contact) {
            switch ($contact['source']) {
                case 'requestor_mail':
                    if (isset($default_notified_email_sources['requestor'])) {
                        $contact['checked'] = 1;
                    }
                    break;
                case 'mailfrom':
                    if (isset($default_notified_email_sources['from-header'])) {
                        $contact['checked'] = 1;
                    }
                    break;
                case 'carbon-copy':
                    if (isset($default_notified_email_sources['cc-header'])) {
                        $contact['checked'] = 1;
                    }
                    break;
                case 'reply-to':
                    if (isset($default_notified_email_sources['reply-to-header'])) {
                        $contact['checked'] = 1;
                    }
                    break;
                case 'customer':
                    if (isset($default_notified_email_sources['contact'])) {
                        $contact['checked'] = 1;
                    }
                    break;
                case 'customer-unflagged':
                    if (isset($default_notified_email_sources['unflagged-contact'])) {
                        $contact['checked'] = 1;
                    }
                    break;
            }
        }
        unset($contact);

        $contacts['mails'] = array_filter(
            $contacts['mails'],
            function ($contact) use ($default_notified_email_sources) {
                return $contact['source'] != 'customer-unflagged' || isset($default_notified_email_sources['unflagged-contact']);
            }
        );
    }

    if (isset($_POST['message'])) {
        if (!isset($message['contacts'])) {
            $message['contacts'] = array(
                'mails' => array(),
                'phones' => array(),
            );
        }
        foreach (array('mails', 'phones') as $contact_type) {
            foreach ($contacts[$contact_type] as $contactidx => &$contact) {
                $contact['name'] = $message['contacts']['mailnames'][$contactidx] ?? '';
                $contact['checked'] = isset($message['contacts'][$contact_type][$contactidx]) ? 1 : 0;
            }
            unset($contact);
        }
    } elseif ($customer_notify) {
        $message['mailnotify'] = !empty($contacts['mails']);
        $message['smsnotify'] = !empty($contacts['phones']);
    }

    $SMARTY->assign('queuelist', $LMS->LimitQueuesToUserpanelEnabled($LMS->GetQueueList(array('stats' => false)), $message['queueid']));
    $SMARTY->assign('messagetemplates', $LMS->GetMessageTemplatesByQueueAndType($message['queueid'], RTMESSAGE_REGULAR));
} else {
    $SMARTY->assign('queuelist', $LMS->GetQueueList(array('stats' => false)));
    $SMARTY->assign('messagetemplates', $LMS->GetMessageTemplatesByQueueAndType($LMS->GetMyQueues(), RTMESSAGE_REGULAR));
}
$SMARTY->assign('citing', isset($_GET['citing']) || $quote_body);
$SMARTY->assign('userlist', $LMS->GetUserNames(array('withDeleted' => 1)));
$SMARTY->assign('categories', $categories);
$SMARTY->assign('contacts', $contacts);
$SMARTY->assign('message', $message);

$SMARTY->display('rt/rtmessageadd.html');
