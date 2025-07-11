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

$LMS->InitXajax();
include(MODULES_DIR . DIRECTORY_SEPARATOR . 'rtticketxajax.inc.php');
$SMARTY->assign('xajax', $LMS->RunXajax());

if (isset($_GET['id'])) {
    if (is_array($_GET['id'])) {
        $id = Utils::filterIntegers($_GET['id']);
    } else {
        $id = intval($_GET['id']);
    }
    if (empty($id)) {
        die;
    }
}

$userid = Auth::GetCurrentUser();

if (!empty($_GET['action'])) {
    $action = $_GET['action'];
}

if (is_array($id)) {
    foreach ($id as $ticketid) {
        if (!($LMS->CheckTicketAccess($ticketid) & RT_RIGHT_WRITE)) {
            access_denied();
        }
    }
} elseif (!($LMS->CheckTicketAccess($id) & RT_RIGHT_WRITE)) {
    access_denied();
}

if ($id && !isset($_POST['ticket'])) {
    if (isset($action)) {
        switch ($action) {
            case 'queuechange':
                $dstqueue = intval($_GET['queueid']);
                $LMS->TicketChange($id, array('queueid' => $dstqueue));
                $SESSION->redirect('?m=rtticketview&id=' . $id);
                break;
            case 'verify':
                $ticket = $LMS->GetTicketContents($id);

                if ($ticket['state'] == RT_VERIFIED) {
                    $SESSION->redirect('?m=rtticketview&id=' . $id);
                }

                $smtp_options = $LMS->GetRTSmtpOptions();

                $notification_options_by_division_ids = array(
                    0 => array(
                        'notification_sender_name' => ConfigHelper::getConfig('rt.sender_name', ConfigHelper::getConfig('phpui.helpdesk_sender_name')),
                        'notification_sms_body' => ConfigHelper::getConfig('rt.notification_sms_body', ConfigHelper::getConfig('phpui.helpdesk_notification_sms_body')),
                    ),
                );
                $notification_options_by_division_ids[$divisionid] = $notification_options_by_division_ids[0];
                extract($notification_options_by_division_ids[0]);

                $ticket_divisionid = $LMS->getDivisionIdByTicketId($id);

                if (empty($ticket_divisionid)) {
                    extract($notification_options_by_division_ids[0]);
                } elseif ($ticket_divisionid != $divisionid) {
                    ConfigHelper::setFilter($ticket_divisionid, Auth::GetCurrentUser());

                    $smtp_options = $LMS->GetRTSmtpOptions();

                    $notification_options_by_division_ids = array(
                        $ticket_divisionid => array(
                            'notification_sender_name' => ConfigHelper::getConfig('rt.sender_name', ConfigHelper::getConfig('phpui.helpdesk_sender_name')),
                            'notification_sms_body' => ConfigHelper::getConfig('rt.notification_sms_body', ConfigHelper::getConfig('phpui.helpdesk_notification_sms_body')),
                        ),
                    );
                    extract($notification_options_by_division_ids[$ticket_divisionid]);
                }

                $LMS->TicketChange($id, array('state' => RT_VERIFIED, 'verifier_rtime' => time()));

                $queue = $LMS->GetQueueByTicketId($id);
                $user = $LMS->GetUserInfo($userid);

                if ($ticket['customerid']) {
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
                }

                $smtp_options = $LMS->GetRTSmtpOptions();
                $smtp_options_by_division_ids = array(
                    0 => $smtp_options,
                    $divisionid => $smtp_options,
                );

                $mailfname = '';

                if (!empty($notification_sender_name)) {
                    if ($notification_sender_name == 'queue') {
                        $mailfname = $queue['name'];
                    } elseif ($notification_sender_name == 'user') {
                        $mailfname = $user['name'];
                    }

                    $mailfname = '"' . $mailfname . '"';
                }

                $mailfrom = $LMS->DetermineSenderEmail($user['email'], $queue['email'], $ticket['requestor_mail']);

                $from = $mailfname . ' <' . $mailfrom . '>';

                $headers['From'] = $from;
                $headers['Reply-To'] = $headers['From'];

                $params = array(
                    'id' => $id,
                    'queue' => $queue['name'],
                    'author' => Auth::GetCurrentUserName(),
                    'verifierid' => $ticket['verifierid'],
                    'customerid' => $ticket['customerid'],
                    'status' => $ticket['status'],
                    'categories' => $ticket['categorynames'],
                    'priority' => isset($ticket['priority']) && is_numeric($ticket['priority']) ? $RT_PRIORITIES[$ticket['priority']] : trans('undefined'),
                    'deadline' => $ticket['deadline'],
                    'service' => $ticket['service'],
                    'type' => $ticket['type'],
                );
                $headers['Subject'] = $LMS->ReplaceNotificationSymbols($queue['verifierticketsubject'], $params);
                $body = $LMS->ReplaceNotificationSymbols($queue['verifierticketbody'], $params);
                $sms_body = $LMS->ReplaceNotificationSymbols($notification_sms_body, $params);

                $LMS->NotifyUsers(array(
                    // don't notify regular users when ticket has been sent to verification
                    //'queue' => $ticket['queue'],
                    'queue' => null,
                    'ticketid' => $id,
                    'verifierid' => $ticket['verifierid'],
                    'mail_headers' => $headers,
                    'mail_body' => $body,
                    'sms_body' => $sms_body,
                ));

                $backto = $SESSION->remove_history_entry();
                if (!empty($backto)) {
                    $SESSION->redirect(
                        '?' . $backto . ($SESSION->is_set('backid') ? '#' . $SESSION->get('backid') : '')
                    );
                } else {
                    $SESSION->redirect('?m=rtticketview&id=' . $id);
                }

                break;
            case 'assign':
                if (isset($_GET['check-conflict'])) {
                    header('Content-Type: application/json');
                    die(json_encode($LMS->TicketIsAssigned($id)));
                }
                $LMS->TicketChange($id, array('owner' => $userid));
                $SESSION->redirect('?m=rtticketview&id=' . $id);
                break;
            case 'assign2':
                $LMS->TicketChange($id, array('verifierid' => $userid));
                $SESSION->redirect('?m=rtticketview&id=' . $id);
                break;
            case 'read':
                if (is_array($id)) {
                    foreach ($id as $ticketid) {
                        $LMS->MarkTicketAsRead($ticketid);
                    }
                } else {
                    $LMS->MarkTicketAsRead($id);
                }
                $SESSION->redirect('?m=rtqueueview'
                    . ($SESSION->is_set('backid') ? '#' . $SESSION->get('backid') : ''));
                break;
            case 'unread':
                $LMS->MarkTicketAsUnread($id);
                $SESSION->redirect('?m=rtqueueview'
                    . ($SESSION->is_set('backid') ? '#' . $SESSION->get('backid') : ''));
                break;
            case 'resetpriority':
                $ticket = $LMS->GetTicketContents($id);
                if (isset($ticket['priority']) && $ticket['priority'] != RT_PRIORITY_NORMAL) {
                    $LMS->TicketChange($id, array('priority' => RT_PRIORITY_NORMAL));
                }
                $SESSION->redirect('?m=rtqueueview'
                    . ($SESSION->is_set('backid') ? '#' . $SESSION->get('backid') : ''));
                break;
            case 'resolve':
                $notification_options_by_division_ids = array(
                    0 => array(
                        'block_ticket_close_with_open_events' => ConfigHelper::checkConfig('rt.block_ticket_close_with_open_events', ConfigHelper::checkConfig('phpui.helpdesk_block_ticket_close_with_open_events')),
                        'notification_sender_name' => ConfigHelper::getConfig('rt.sender_name', ConfigHelper::getConfig('phpui.helpdesk_sender_name')),
                        'ticket_property_change_notify' => ConfigHelper::checkConfig(
                            'rt.ticket_property_change_notify',
                            ConfigHelper::checkConfig('phpui.ticket_property_change_notify')
                        ),
                        'notification_customerinfo' => ConfigHelper::checkConfig(
                            'rt.notification_customerinfo',
                            ConfigHelper::checkConfig('phpui.helpdesk_customerinfo')
                        ),
                        'notification_mail_body_customerinfo_format' => ConfigHelper::getConfig(
                            'rt.notification_mail_body_customerinfo_format',
                            ConfigHelper::getConfig('phpui.helpdesk_customerinfo_mail_body')
                        ),
                        'notification_sms_body_customerinfo_format' => ConfigHelper::getConfig(
                            'rt.notification_sms_body_customerinfo_format',
                            ConfigHelper::getConfig('phpui.helpdesk_customerinfo_sms_body')
                        ),
                        'notification_mail_subject' => ConfigHelper::getConfig('rt.notification_mail_subject', ConfigHelper::getConfig('phpui.helpdesk_notification_mail_subject')),
                        'notification_mail_body' => ConfigHelper::getConfig('rt.notification_mail_body', ConfigHelper::getConfig('phpui.helpdesk_notification_mail_body')),
                        'notification_sms_body' => ConfigHelper::getConfig('rt.notification_sms_body', ConfigHelper::getConfig('phpui.helpdesk_notification_sms_body')),
                    ),
                );

                $user = $LMS->GetUserInfo($userid);

                if (is_array($id)) {
                    $ticketids = $id;
                } else {
                    $ticketids = array($id);
                }

                $smtp_options = $LMS->GetRTSmtpOptions();
                $smtp_options_by_division_ids = array(
                    0 => $smtp_options,
                    $divisionid => $smtp_options,
                );

                $notification_options_by_division_ids[$divisionid] = $notification_options_by_division_ids[0];
                extract($notification_options_by_division_ids[0]);

                $ticket_divisionid = $divisionid;

                foreach ($ticketids as $ticketid) {
                    $new_ticket_divisionid = $LMS->getDivisionIdByTicketId($ticketid);

                    if (empty($new_ticket_divisionid)) {
                        $smtp_options = $smtp_options_by_division_ids[0];

                        extract($notification_options_by_division_ids[0]);
                    } elseif ($new_ticket_divisionid != $ticket_divisionid) {
                        $ticket_divisionid = $new_ticket_divisionid;

                        if (!isset(
                            $smtp_options_by_division_ids[$ticket_divisionid],
                            $notification_options_by_division_ids[$ticket_divisionid]
                        )) {
                            ConfigHelper::setFilter($ticket_divisionid, Auth::GetCurrentUser());
                        }

                        if (!isset($smtp_options_by_division_ids[$ticket_divisionid])) {
                            $smtp_options_by_division_ids[$ticket_divisionid] = $LMS->GetRTSmtpOptions();
                        }

                        if (!isset($notification_options_by_division_ids[$ticket_divisionid])) {
                            $notification_options_by_division_ids[$ticket_divisionid] = array(
                                'block_ticket_close_with_open_events' => ConfigHelper::checkConfig('rt.block_ticket_close_with_open_events', ConfigHelper::checkConfig('phpui.helpdesk_block_ticket_close_with_open_events')),
                                'notification_sender_name' => ConfigHelper::getConfig('rt.sender_name', ConfigHelper::getConfig('phpui.helpdesk_sender_name')),
                                'ticket_property_change_notify' => ConfigHelper::checkConfig(
                                    'rt.ticket_property_change_notify',
                                    ConfigHelper::checkConfig('phpui.ticket_property_change_notify')
                                ),
                                'notification_customerinfo' => ConfigHelper::checkConfig(
                                    'rt.notification_customerinfo',
                                    ConfigHelper::checkConfig('phpui.helpdesk_customerinfo')
                                ),
                                'notification_mail_body_customerinfo_format' => ConfigHelper::getConfig(
                                    'rt.notification_mail_body_customerinfo_format',
                                    ConfigHelper::getConfig('phpui.helpdesk_customerinfo_mail_body')
                                ),
                                'notification_sms_body_customerinfo_format' => ConfigHelper::getConfig(
                                    'rt.notification_sms_body_customerinfo_format',
                                    ConfigHelper::getConfig('phpui.helpdesk_customerinfo_sms_body')
                                ),
                                'notification_mail_subject' => ConfigHelper::getConfig('rt.notification_mail_subject', ConfigHelper::getConfig('phpui.helpdesk_notification_mail_subject')),
                                'notification_mail_body' => ConfigHelper::getConfig('rt.notification_mail_body', ConfigHelper::getConfig('phpui.helpdesk_notification_mail_body')),
                                'notification_sms_body' => ConfigHelper::getConfig('rt.notification_sms_body', ConfigHelper::getConfig('phpui.helpdesk_notification_sms_body')),
                            );
                        }

                        $smtp_options = $smtp_options_by_division_ids[$ticket_divisionid];

                        extract($notification_options_by_division_ids[$ticket_divisionid]);
                    }

                    $ticket = $LMS->GetTicketContents($ticketid);

                    if ($block_ticket_close_with_open_events && !empty($ticket['openeventcount'])) {
                        die(trans("Ticket have open assigned events!"));
                    } else {
                        if ($ticket['state'] != RT_RESOLVED) {
                            $LMS->TicketChange($ticketid, array('state' => RT_RESOLVED));
                        } else {
                            $SESSION->redirect('?m=rtqueueview'
                                . ($SESSION->is_set('backid') ? '#' . $SESSION->get('backid') : ''));
                        }
                    }

/*
                    // dont trigger any notifications when we resolve many tickets with dedicated button
                    if (is_array($id)) {
                        continue;
                    }
*/

                    $queue = $LMS->GetQueueByTicketId($ticketid);
                    if ($ticket['customerid']) {
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

                        $all_phones = array_filter(
                            $LMS->GetCustomerContacts($ticket['customerid'], CONTACT_LANDLINE | CONTACT_MOBILE),
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
                    }

                    $mailfname = '';

                    if (!empty($sender_name)) {
                        if ($sender_name == 'queue') {
                            $mailfname = $queue['name'];
                        } elseif ($sender_name == 'user') {
                            $mailfname = $user['name'];
                        }

                        $mailfname = '"' . $mailfname . '"';
                    }

                    $mailfrom = $LMS->DetermineSenderEmail($user['email'], $queue['email'], $ticket['requestor_mail']);

                    $from = $mailfname . ' <' . $mailfrom . '>';

                    if (!empty($ticket['customerid'])) {
                        if (!empty($queue['resolveticketsubject']) && !empty($queue['resolveticketbody']) && !empty($emails)) {
                            $custmail_subject = $queue['resolveticketsubject'];
                            $custmail_subject = preg_replace_callback(
                                '/%(\\d*)tid/',
                                function ($m) use ($ticketid) {
                                    return sprintf('%0' . $m[1] . 'd', $ticketid);
                                },
                                $custmail_subject
                            );
                            $custmail_subject = str_replace('%title', $ticket['subject'], $custmail_subject);
                            $custmail_body = $queue['resolveticketbody'];
                            $custmail_body = preg_replace_callback(
                                '/%(\\d*)tid/',
                                function ($m) use ($ticketid) {
                                    return sprintf('%0' . $m[1] . 'd', $ticketid);
                                },
                                $custmail_body
                            );
                            $custmail_body = str_replace('%cid', $info['id'], $custmail_body);
                            $custmail_body = str_replace('%pin', $info['pin'], $custmail_body);
                            $custmail_body = str_replace('%customername', $info['customername'], $custmail_body);
                            $custmail_body = str_replace('%title', $ticket['subject'], $custmail_body);
                            $message = end($ticket['messages']);
                            $body = str_replace('<br>', "\n", $message['body']);
                            $custmail_body = str_replace('%body', $body, $custmail_body);
                            $custmail_headers = array(
                                'From' => $from,
                                'Reply-To' => $from,
                                'Subject' => $custmail_subject,
                            );
                            $LMS->prepareMessageTemplates('rt');
                            foreach ($emails as $email) {
                                $custmail_headers['To'] = '<' . $email . '>';
                                $LMS->SendMail(
                                    $email,
                                    $custmail_headers,
                                    $LMS->applyMessageTemplates($custmail_body),
                                    null,
                                    null,
                                    $smtp_options
                                );
                            }
                        }

                        if (!empty($queue['resolveticketsmsbody']) && !empty($mobile_phones)) {
                            $custsms_body = $queue['resolveticketsmsbody'];
                            $custsms_body = preg_replace_callback(
                                '/%(\\d*)tid/',
                                function ($m) use ($ticketid) {
                                    return sprintf('%0' . $m[1] . 'd', $ticketid);
                                },
                                $custsms_body
                            );
                            $custsms_body = str_replace('%cid', $info['id'], $custsms_body);
                            $custsms_body = str_replace('%pin', $info['pin'], $custsms_body);
                            $custsms_body = str_replace('%customername', $info['customername'], $custsms_body);
                            $custsms_body = str_replace('%title', $ticket['subject'], $custsms_body);
                            $message = end($ticket['messages']);
                            $body = str_replace('<br>', "\n", $message['body']);
                            $custsms_body = str_replace('%body', $body, $custsms_body);
                            $custsms_body = str_replace('%service', $ticket['service'], $custsms_body);

                            foreach ($mobile_phones as $phone) {
                                $LMS->SendSMS($phone['contact'], $custsms_body);
                            }
                        }
                    }

                    if ($ticket_property_change_notify) {
                        $headers['From'] = $from;
                        $headers['Reply-To'] = $headers['From'];

                        if ($notification_customerinfo) {
                            if ($ticket['customerid']) {
                                $params = array(
                                    'id' => $ticketid,
                                    'customerid' => $ticket['customerid'],
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
                            } else {
                                $mail_customerinfo = "\n\n-- \n" . trans('Customer:') . ' ' . $ticket['requestor'];
                                $sms_customerinfo = "\n" . trans('Customer:') . ' ' . $ticket['requestor'];
                            }
                        }

                        $message = end($ticket['messages']);
                        $message['body'] = str_replace('<br>', "\n", $message['body']);

                        $params = array(
                            'id' => $ticketid,
                            'queue' => $queue['name'],
                            'customerid' => $ticket['customerid'],
                            'status' => $ticket['status'],
                            'categories' => $ticket['categorynames'],
                            'priority' => isset($ticket['priority']) && is_numeric($ticket['priority']) ? $RT_PRIORITIES[$ticket['priority']] : trans('undefined'),
                            'deadline' => $ticket['deadline'],
                            'service' => $ticket['service'],
                            'type' => $ticket['type'],
                            'subject' => $ticket['subject'],
                            'body' => $message['body'],
                        );
                        $headers['Subject'] = $LMS->ReplaceNotificationSymbols($notification_mail_subject, $params);
                        $params['customerinfo'] = $mail_customerinfo ?? null;
                        $body = $LMS->ReplaceNotificationSymbols($notification_mail_body, $params);
                        $params['customerinfo'] = $sms_customerinfo ?? null;
                        $sms_body = $LMS->ReplaceNotificationSymbols($notification_sms_body, $params);

                        $LMS->NotifyUsers(array(
                            'queue' => $queue['id'],
                            'ticketid' => $ticketid,
                            'mail_headers' => $headers,
                            'mail_body' => $body,
                            'sms_body' => $sms_body,
                        ));
                    }
                }

                if (is_array($id)) {
                    $SESSION->redirect('?m=rtqueueview'
                        . ($SESSION->is_set('backid') ? '#' . $SESSION->get('backid') : ''));
                } else {
                    $SESSION->redirect('?m=rtticketview&id=' . $id);
                }

                break;
        }
    }
}

$allow_empty_categories = ConfigHelper::checkConfig('rt.allow_empty_categories', ConfigHelper::checkConfig('phpui.helpdesk_allow_empty_categories'));
$empty_category_warning = ConfigHelper::checkConfig('rt.empty_category_warning', ConfigHelper::checkConfig('phpui.helpdesk_empty_category_warning', true));

$ticket = $LMS->GetTicketContents($id);
$LMS->MarkTicketAsRead($id);
$LMS->getTicketImageGalleries($ticket);
$ticket['oldverifierid'] = $ticket['verifierid'];
$categories = $LMS->GetUserCategories($userid);
if (empty($categories)) {
    $categories = array();
}

$aet = ConfigHelper::getConfig('rt.allow_modify_resolved_tickets_newer_than', 86400);
if ($ticket['state'] == RT_RESOLVED && !ConfigHelper::checkPrivilege('superuser') && $aet && (time() - $ticket['resolvetime'] > $aet)) {
    die("Cannot edit ticket - ticket was resolved more than " . $aet . " seconds.");
}

if (isset($_POST['ticket'])) {
    $notification_options_by_division_ids = array(
        0 => array(
            'block_ticket_close_with_open_events' => ConfigHelper::checkConfig('rt.block_ticket_close_with_open_events', ConfigHelper::checkConfig('phpui.helpdesk_block_ticket_close_with_open_events')),
            'notification_sender_name' => ConfigHelper::getConfig('rt.sender_name', ConfigHelper::getConfig('phpui.helpdesk_sender_name')),
            'ticket_property_change_notify' => ConfigHelper::checkConfig(
                'rt.ticket_property_change_notify',
                ConfigHelper::checkConfig('phpui.ticket_property_change_notify')
            ),
            'notification_customerinfo' => ConfigHelper::checkConfig(
                'rt.notification_customerinfo',
                ConfigHelper::checkConfig('phpui.helpdesk_customerinfo')
            ),
            'notification_mail_body_customerinfo_format' => ConfigHelper::getConfig(
                'rt.notification_mail_body_customerinfo_format',
                ConfigHelper::getConfig('phpui.helpdesk_customerinfo_mail_body')
            ),
            'notification_sms_body_customerinfo_format' => ConfigHelper::getConfig(
                'rt.notification_sms_body_customerinfo_format',
                ConfigHelper::getConfig('phpui.helpdesk_customerinfo_sms_body')
            ),
            'notification_mail_subject' => ConfigHelper::getConfig('rt.notification_mail_subject', ConfigHelper::getConfig('phpui.helpdesk_notification_mail_subject')),
            'notification_mail_body' => ConfigHelper::getConfig('rt.notification_mail_body', ConfigHelper::getConfig('phpui.helpdesk_notification_mail_body')),
            'notification_sms_body' => ConfigHelper::getConfig('rt.notification_sms_body', ConfigHelper::getConfig('phpui.helpdesk_notification_sms_body')),
        ),
    );

    $ticketedit = $_POST['ticket'];
    $ticketedit['ticketid'] = $ticket['ticketid'];

    if (!empty($ticketedit['parentid'])) {
        if (!$LMS->TicketExists($ticketedit['parentid'])) {
            $error['parentid'] = trans("Ticket does not exist");
        }
    }

    if (!empty($ticketedit['parentid'])) {
        if ($LMS->IsTicketLoop($ticket['ticketid'], $ticketedit['parentid'])) {
            $error['parentid'] = trans("Cannot link ticket because of related ticket loop!");
        }
    }

    if (ConfigHelper::checkConfig('rt.check_owner_verifier_conflict', ConfigHelper::checkConfig('phpui.helpdesk_check_owner_verifier_conflict', true))
        && !empty($ticketedit['verifierid']) && $ticketedit['verifierid'] == $ticketedit['owner']) {
        $error['verifierid'] = trans('Ticket owner could not be the same as verifier!');
        $error['owner'] = trans('Ticket verifier could not be the same as owner!');
    }

    $deadline = datetime_to_timestamp($ticketedit['deadline']);
    if ($deadline != $ticket['deadline']) {
        if (!ConfigHelper::checkConfig('rt.allow_all_users_modify_deadline', ConfigHelper::checkConfig('phpui.helpdesk_allow_all_users_modify_deadline'))
            && !empty($ticket['verifierid']) && $ticket['verifierid'] != $userid) {
            $error['deadline'] = trans('If verifier is set then he\'s the only person who can change deadline!');
            $ticketedit['deadline'] = $ticket['deadline'];
        }
        if ($deadline && $deadline < time()) {
            $error['deadline'] = trans('Ticket deadline could not be set in past!');
        }
    }

    if (empty($ticketedit['categories']) && (!$allow_empty_categories || (empty($ticketedit['categorywarn']) && $empty_category_warning))) {
        if ($allow_empty_categories) {
            $ticketedit['categorywarn'] = 1;
            $error['categories'] = trans('Category selection is recommended but not required!');
        } else {
            $error['categories'] = trans('You have to select category!');
        }
    }

    if (!($LMS->GetUserRightsRT($userid, $ticketedit['queue']) & RT_RIGHT_WRITE)) {
        $error['queue'] = trans('You have no privileges to this queue!');
    }

    if ($ticketedit['subject'] == '') {
        $error['subject'] = trans('Ticket must have its title!');
    } elseif ($ticketedit['subject'] != $ticket['subject'] && mb_strlen($ticketedit['subject']) > ConfigHelper::getConfig('rt.subject_max_length', 50)) {
        $error['subject'] = trans('Ticket subject can contain maximum $a characters!', ConfigHelper::getConfig('rt.subject_max_length', 50));
    }

    if ($block_ticket_close_with_open_events) {
        if ($ticketedit['state'] == RT_RESOLVED && !empty($ticket['openeventcount'])) {
            $error['state'] = trans('Ticket have open assigned events!');
        }
    }

    if ($ticketedit['state'] != RT_NEW && !$ticketedit['owner']) {
        $error['owner'] = trans('Only \'new\' ticket can be owned by no one!');
    }

    if (!ConfigHelper::checkConfig('rt.allow_change_ticket_state_from_open_to_new', ConfigHelper::checkConfig('phpui.helpdesk_allow_change_ticket_state_from_open_to_new'))) {
        if ($ticketedit['state'] == RT_NEW && $ticketedit['owner']) {
            $ticketedit['state'] = RT_OPEN;
        }
    }

    if (!ConfigHelper::checkPrivilege('superuser') && $ticket['state'] == RT_VERIFIED) {
        if ($ticketedit['state'] != RT_VERIFIED) {
            if (!empty($ticket['verifierid']) && $ticket['verifierid'] != $userid) {
                $error['state'] = trans('Ticket is already transferred to verifier!');
            }
        } else {
            if ($ticket['verifierid'] != $ticketedit['verifierid'] && $ticketedit['verifierid'] != $userid) {
                $error['verifierid'] = trans('Ticket is already transferred to verifier!');
            }
        }
    }

    $ticketedit['customerid'] = ($ticketedit['custid'] ?: 0);

    $ticket_divisionid = $LMS->getDivisionIdByTicketId($ticketedit['customerid']);

    if (empty($ticket_divisionid)) {
        $smtp_options = $smtp_options_by_division_ids[0];

        extract($notification_options_by_division_ids[0]);
    } elseif ($ticket_divisionid != $divisionid) {
        ConfigHelper::setFilter($ticket_divisionid, Auth::GetCurrentUser());

        $smtp_options_by_division_ids[$ticket_divisionid] = $LMS->GetRTSmtpOptions();

        $notification_options_by_division_ids[$ticket_divisionid] = array(
            'block_ticket_close_with_open_events' => ConfigHelper::checkConfig('rt.block_ticket_close_with_open_events', ConfigHelper::checkConfig('phpui.helpdesk_block_ticket_close_with_open_events')),
            'notification_sender_name' => ConfigHelper::getConfig('rt.sender_name', ConfigHelper::getConfig('phpui.helpdesk_sender_name')),
            'ticket_property_change_notify' => ConfigHelper::checkConfig(
                'rt.ticket_property_change_notify',
                ConfigHelper::checkConfig('phpui.ticket_property_change_notify')
            ),
            'notification_customerinfo' => ConfigHelper::checkConfig(
                'rt.notification_customerinfo',
                ConfigHelper::checkConfig('phpui.helpdesk_customerinfo')
            ),
            'notification_mail_body_customerinfo_format' => ConfigHelper::getConfig(
                'rt.notification_mail_body_customerinfo_format',
                ConfigHelper::getConfig('phpui.helpdesk_customerinfo_mail_body')
            ),
            'notification_sms_body_customerinfo_format' => ConfigHelper::getConfig(
                'rt.notification_sms_body_customerinfo_format',
                ConfigHelper::getConfig('phpui.helpdesk_customerinfo_sms_body')
            ),
            'notification_mail_subject' => ConfigHelper::getConfig('rt.notification_mail_subject', ConfigHelper::getConfig('phpui.helpdesk_notification_mail_subject')),
            'notification_mail_body' => ConfigHelper::getConfig('rt.notification_mail_body', ConfigHelper::getConfig('phpui.helpdesk_notification_mail_body')),
            'notification_sms_body' => ConfigHelper::getConfig('rt.notification_sms_body', ConfigHelper::getConfig('phpui.helpdesk_notification_sms_body')),
        );

        $smtp_options = $smtp_options_by_division_ids[$ticket_divisionid];

        extract($notification_options_by_division_ids[$ticket_divisionid]);
    }

    if ($ticketedit['requestor_userid'] == '0') {
        if (empty($ticketedit['requestor_name'])
            && empty($ticketedit['requestor_mail'])
            && empty($ticketedit['requestor_customer_mail'])
            && empty($ticketedit['requestor_phone'])
            && empty($ticketedit['requestor_customer_phone'])) {
            $error['requestor_name'] = $error['requestor_mail'] = $error['requestor_phone'] =
                trans('At least requestor name, mail or phone should be filled!');
        }
    }

    if (!isset($ticketedit['parentid'])) {
        $ticketedit['parentid'] = null;
    }

    if (!empty($ticketedit['customcreatetime'])) {
        $customcreatetime = datetime_to_timestamp($ticketedit['customcreatetime']);
        if (!isset($customcreatetime)) {
            $error['customcreatetime'] = trans('Invalid date format: $a.\\nFormat accepted is \'YYYY/MM/DD hh:mm\'.');
        }
    } else {
        $customcreatetime = null;
    }

    if (!empty($ticketedit['customresolvetime'])) {
        $customresolvetime = datetime_to_timestamp($ticketedit['customresolvetime']);
        if (!isset($customresolvetime)) {
            $error['customresolvetime'] = trans('Invalid date format: $a.\\nFormat accepted is \'YYYY/MM/DD hh:mm\'.');
        }
    } else {
        $customresolvetime = null;
    }

    if (isset($customcreatetime, $customresolvetime) && $customcreatetime > $customresolvetime) {
        $error['customcreatetime'] = $error['customresolvetime'] = trans('Custom resolve time should be later than custom create time!');
    }

    $hook_data = $LMS->executeHook(
        'ticketedit_validation_before_submit',
        array(
            'ticketedit' => $ticketedit,
            'error' => $error
        )
    );

    $ticketedit = $hook_data['ticketedit'];
    $error = $hook_data['error'];

    if (!empty($ticketedit['categories'])) {
        $ticketedit['categories'] = array_flip($ticketedit['categories']);
    }

    if (!$error) {
        // setting status and the ticket owner
        $props = array(
            'queueid' => $ticketedit['queue'],
            'owner' => empty($ticketedit['owner']) ? null : $ticketedit['owner'],
            'cause' => $ticketedit['cause'],
            'state' => $ticketedit['state'],
            'subject' => $ticketedit['subject'],
            'customerid' => $ticketedit['customerid'],
            'categories' => isset($ticketedit['categories']) ? array_keys($ticketedit['categories']) : array(),
            'source' => $ticketedit['source'],
            'priority' => $ticketedit['priority'] ?? null,
            'address_id' => $ticketedit['address_id'] == -1 ? null : $ticketedit['address_id'],
            'nodeid' => empty($ticketedit['nodeid']) ? null : $ticketedit['nodeid'],
            'netnodeid' => empty($ticketedit['netnodeid']) ? null : $ticketedit['netnodeid'],
            'netdevid' => empty($ticketedit['netdevid']) ? null : $ticketedit['netdevid'],
            'verifierid' => empty($ticketedit['verifierid']) ? null : $ticketedit['verifierid'],
            'verifier_rtime' => empty($ticketedit['verifier_rtime']) ? null : $ticketedit['verifier_rtime'],
            'deadline' => empty($ticketedit['deadline']) ? null : $deadline,
            'service' => empty($ticketedit['service']) ? null : $ticketedit['service'],
            'type' => empty($ticketedit['type']) ? null : $ticketedit['type'],
            'invprojectid' => empty($ticketedit['invprojectid']) ? null : $ticketedit['invprojectid'],
            'requestor_userid' => empty($ticketedit['requestor_userid']) ? null : $ticketedit['requestor_userid'],
            'requestor' => !empty($ticketedit['requestor_userid']) || $ticketedit['requestor_userid'] == ''
                || empty($ticketedit['requestor_name']) ? '' : $ticketedit['requestor_name'],
            'requestor_mail' => !empty($ticketedit['requestor_userid'])
                || $ticketedit['requestor_userid'] == ''
                || empty($ticketedit['requestor_mail'])
                && empty($ticketedit['requestor_customer_mail'])
                    ? null
                    : (empty($ticketedit['requestor_mail']) ? $ticketedit['requestor_customer_mail'] : $ticketedit['requestor_mail']),
            'requestor_phone' => !empty($ticketedit['requestor_userid'])
                || $ticketedit['requestor_userid'] == ''
                || empty($ticketedit['requestor_phone'])
                && empty($ticketedit['requestor_customer_phone'])
                    ? null
                    : (empty($ticketedit['requestor_phone']) ? $ticketedit['requestor_customer_phone'] : $ticketedit['requestor_phone']),
            'parentid' => empty($ticketedit['parentid']) ? null : $ticketedit['parentid'],
            'relatedtickets' => $ticketedit['relatedtickets'] ?? array(),
            'customcreatetime' => isset($customcreatetime) ? $customcreatetime : null,
            'customresolvetime' => isset($customresolvetime) ? $customresolvetime : null,
        );
        $LMS->TicketChange($ticketedit['ticketid'], $props);

        $hook_data = $LMS->executeHook(
            'ticketedit_after_submit',
            array(
                'ticketedit' => $ticketedit,
            )
        );
        $ticketedit = $hook_data['ticketedit'];

        // we notify about new ticket after queue change
        $newticket_notify = ConfigHelper::checkConfig(
            'rt.new_ticket_notify',
            ConfigHelper::checkConfig('phpui.newticket_notify', true)
        );
        if (isset($ticketedit['notify'])
            && (($ticket_property_change_notify && ($ticket['state'] != $ticketedit['state']
                || $ticket['owner'] != $ticketedit['owner']
                || $ticket['deadline'] != $ticketedit['deadline']
                || $ticket['priority'] != $ticketedit['priority']
                || $ticket['parentid'] != $ticketedit['parentid']))
            || ($ticket['queueid'] != $ticketedit['queue'] && !empty($newticket_notify))
            || ($ticket['verifierid'] != $ticketedit['verifierid'] && !empty($ticketedit['verifierid'])))) {
            $user = $LMS->GetUserInfo($userid);
            $queue = $LMS->GetQueueByTicketId($ticket['ticketid']);
            $verifierid = $ticket['verifierid'];
            $mailfname = '';

            if (!empty($notification_sender_name)) {
                if ($notification_sender_name == 'queue') {
                    $mailfname = $queue['name'];
                } elseif ($notification_sender_name == 'user') {
                    $mailfname = $user['name'];
                } else {
                    $mailfname = $notification_sender_name;
                }

                $mailfname = '"' . $mailfname . '"';
            }

            $ticketdata = $LMS->GetTicketContents($ticket['ticketid']);

            $mailfrom = $LMS->DetermineSenderEmail($user['email'], $queue['email'], $ticketdata['requestor_mail']);

            $headers['From'] = $mailfname . ' <' . $mailfrom . '>';
            $headers['Reply-To'] = $headers['From'];

            if ($notification_customerinfo) {
                if ($ticketedit['customerid']) {
                    $info = $LMS->GetCustomer($ticketedit['customerid'], true);

                    $emails = array_map(
                        function ($contact) {
                            return $contact['fullname'];
                        },
                        array_filter(
                            $LMS->GetCustomerContacts($ticketedit['customerid'], CONTACT_EMAIL),
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
                            $LMS->GetCustomerContacts($ticketedit['customerid'], CONTACT_LANDLINE | CONTACT_MOBILE),
                            function ($contact) {
                                return $contact['type'] & CONTACT_HELPDESK_NOTIFICATIONS;
                            }
                        )
                    );

                    $params = array(
                        'id' => $ticket['ticketid'],
                        'customerid' => $ticketedit['customerid'],
                        'customer' => $info,
                        'emails' => $emails,
                        'phones' => $phones,
                    );
                    $mail_customerinfo = $LMS->ReplaceNotificationCustomerSymbols($notification_mail_body_customerinfo_format, $params);
                    $sms_customerinfo = $LMS->ReplaceNotificationCustomerSymbols($notification_sms_body_customerinfo_format, $params);
                } else {
                    $mail_customerinfo = "\n\n-- \n" . trans('Customer:') . ' ' . $ticketdata['requestor'];
                    $sms_customerinfo = "\n" . trans('Customer:') . ' ' . $ticketdata['requestor'];
                }
            }

            if ($ticket['queueid'] == $ticketedit['queue']) {
                $ticket = $LMS->GetTicketContents($id);
                $message = end($ticket['messages']);
                $message['body'] = str_replace('<br>', "\n", $message['body']);
            } else {
                $message = reset($ticket['messages']);
            }

            $params = array(
                'id' => $ticket['ticketid'],
                'queue' => $queue['name'],
                'customerid' => $ticketedit['customerid'],
                'status' => $ticketdata['status'],
                'categories' => $ticketdata['categorynames'],
                'subject' => $ticket['subject'],
                'body' => $message['body'],
                'priority' => isset($ticketdata['priority']) && is_numeric($ticketdata['priority']) ? $RT_PRIORITIES[$ticketdata['priority']] : trans('undefined'),
                'deadline' => $ticketdata['deadline'],
                'service' => $ticketdata['service'],
                'type' => $ticketdata['type'],
                'invproject' => $ticketdata['invproject_name'],
                'invprojectid' => $ticketdata['invprojectid'],
                'requestor' => $ticketdata['requestor'],
                'requestor_mail' => $ticketdata['requestor_mail'],
                'requestor_phone' => $ticketdata['requestor_phone'],
                'requestor_userid' => $ticketdata['requestor_userid'],
                'parentid' => $ticketdata['parentid'],
                'node' => $ticketdata['node_name'],
                'nodeid' => $ticketdata['nodeid'],
                'netnode' => $ticketdata['netnode_name'],
                'netnodeid' => $ticketdata['netnodeid'],
                'netdev' => $ticketdata['netdev_name'],
                'netdevid' => $ticketdata['netdevid'],
                'owner' => $ticketdata['ownername'],
                'ownerid' => $ticketdata['owner'],
                'verifier' => $ticketdata['verifier_username'],
                'verifierid' => $ticketdata['verifierid'],
                'attachments' => &$attachments,
            );
            $headers['Subject'] = $LMS->ReplaceNotificationSymbols($notification_mail_subject, $params);
            $params['customerinfo'] = $mail_customerinfo ?? null;
            $body = $LMS->ReplaceNotificationSymbols($notification_mail_body, $params);
            $params['customerinfo'] = $sms_customerinfo ?? null;
            $sms_body = $LMS->ReplaceNotificationSymbols($notification_sms_body, $params);

            $LMS->NotifyUsers(array(
                'queue' => $ticketedit['queue'],
                'oldqueue' => $ticket['queueid'] == $ticketedit['queue'] ? null : $ticket['queueid'],
                'ticketid' => $ticket['ticketid'],
                'verifierid' => $verifierid == $ticketedit['verifierid'] ? null : $ticketedit['verifierid'],
                'mail_headers' => $headers,
                'mail_body' => $body,
                'sms_body' => $sms_body,
            ));
        }

        $backto = $SESSION->remove_history_entry();
        if (empty($backto)) {
            $SESSION->redirect('?m=rtticketview&id='.$id);
        } elseif (strpos($backto, 'rtqueueview') !== false) {
            $SESSION->redirect('?' . $backto
                . ($SESSION->is_set('backid') ? '#' . $SESSION->get('backid') : ''));
        } else {
            $SESSION->redirect('?' . $backto);
        }
    }

    $ticket['subject'] = $ticketedit['subject'];
    $ticket['queueid'] = $ticketedit['queue'];
    $ticket['customerid'] = $ticketedit['customerid'];
    $ticket['service'] = $ticketedit['service'];
    $ticket['type'] = $ticketedit['type'];
    $ticket['state'] = $ticketedit['state'];
    $ticket['owner'] = $ticketedit['owner'];
    $ticket['verifierid'] = $ticketedit['verifierid'];
    $ticket['cause'] = $ticketedit['cause'];
    $ticket['source'] = $ticketedit['source'];
    $ticket['deadline'] = $ticketedit['deadline'];
    $ticket['address_id'] = $ticketedit['address_id'];
    $ticket['nodeid'] = $ticketedit['nodeid'];
    $ticket['netnodeid'] = $ticketedit['netnodeid'] ?? null;
    $ticket['netdevid'] = $ticketedit['netdevid'];
    $ticket['invprojectid'] = empty($ticketedit['invprojectid']) ? null : $ticketedit['invprojectid'];
    $ticket['priority'] = $ticketedit['priority'];
    $ticket['requestor_userid'] = $ticketedit['requestor_userid'];
    $ticket['requestor_name'] = $ticketedit['requestor_name'] ?? null;
    $ticket['requestor_mail'] = $ticketedit['requestor_mail'] ?? null;
    $ticket['requestor_phone'] = $ticketedit['requestor_phone'] ?? null;
    $ticket['parentid'] = $ticketedit['parentid'] ?? null;
    $ticket['categorywarn'] = $ticketedit['categorywarn'] ?? 0;
    $ticket['customcreatetime'] = $ticketedit['customcreatetime'];
    $ticket['customresolvetime'] = $ticketedit['customresolvetime'];

    if (!empty($ticketedit['relatedtickets'])) {
        $ticket['relatedtickets'] = $LMS->getTickets($ticketedit['relatedtickets']);
    }
    if (!empty($ticketedit['parentid'])) {
        $ticket['parent'] = $LMS->getTickets($ticketedit['parentid']);
    }
} else {
    $ticketedit['categories'] = $ticket['categories'];

    if (ConfigHelper::checkConfig('rt.notify', ConfigHelper::checkConfig('phpui.helpdesk_notify'))) {
        $ticket['notify'] = true;
    }

    $ticket['categorywarn'] = 0;
}

foreach ($categories as &$category) {
    $category['checked'] = isset($ticketedit['categories'][$category['id']]);
}
unset($category);

$layout['pagetitle'] = trans('Ticket Edit: $a', sprintf("%06d", $ticket['ticketid']));

if (!ConfigHelper::checkConfig('phpui.big_networks')) {
    $SMARTY->assign('customerlist', $LMS->GetAllCustomerNames());
}

$queuelist = $LMS->LimitQueuesToUserpanelEnabled($LMS->GetQueueNames(), $ticket['queueid']);

if (!empty($ticket['customerid'])) {
    $SMARTY->assign('nodes', $LMS->GetNodeLocations(
        $ticket['customerid'],
        isset($ticket['address_id']) && intval($ticket['address_id']) > 0 ? $ticket['address_id'] : null
    ));
}

$netnodelist = $LMS->GetNetNodeList(array('short' => true), 'name');
unset($netnodelist['total'], $netnodelist['order'], $netnodelist['direction']);

$invprojectlist = $LMS->GetProjects();
unset($invprojectlist['total'], $invprojectlist['order'], $invprojectlist['direction']);

if (!empty($ticket['netnodeid'])) {
    $search = array('netnode' => $ticket['netnodeid']);
} else {
    $search = array();
}
$search['short'] = true;
$netdevlist = $LMS->GetNetDevList('name', $search);
unset($netdevlist['total'], $netdevlist['order'], $netdevlist['direction']);

$hook_data = $LMS->executeHook(
    'ticketedit_before_display',
    array(
        'ticket' => $ticket,
        'smarty' => $SMARTY
    )
);
$ticket = $hook_data['ticket'];

if (!empty($ticket['customerid'])) {
    $addresses = $LMS->getCustomerAddresses($ticket['customerid']);
    $LMS->determineDefaultCustomerAddress($addresses);
    $SMARTY->assign('addresses', $addresses);

    $emails = array_filter(
        $LMS->getCustomerContacts($ticket['customerid'], CONTACT_EMAIL),
        function ($contact) {
            return !($contact['type'] & CONTACT_DISABLED);
        }
    );
    usort(
        $emails,
        function ($a, $b) {
            return ($a['contact'] < $b['contact']) ? -1 : 1;
        }
    );

    $phones = array_filter(
        $LMS->getCustomerContacts($ticket['customerid'], CONTACT_LANDLINE | CONTACT_MOBILE),
        function ($contact) {
            return !($contact['type'] & CONTACT_DISABLED);
        }
    );
    usort(
        $phones,
        function ($a, $b) {
            return ($a['contact'] < $b['contact']) ? -1 : 1;
        }
    );

    $SMARTY->assign('customercontacts', compact('emails', 'phones'));
}

$SMARTY->assign(
    array(
        'ticket' => $ticket,
        'customerid' => $ticket['customerid'],
        'queuelist' => $queuelist,
        'queue' => $ticket['queueid'],
        'categories' => $categories,
        'netnodelist' => $netnodelist,
        'netdevlist' => $netdevlist,
        'invprojectlist' => $invprojectlist,
        'userlist' => $LMS->GetUserNames(array('withDeleted' => 1)),
        'error' => ($error ?? null),
    )
);

$SMARTY->display('rt/rtticketedit.html');
