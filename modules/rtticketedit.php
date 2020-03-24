<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2018 LMS Developers
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

$id = intval($_GET['id']);
if (!empty($_GET['action'])) {
    $action = $_GET['action'];
}

if (!($LMS->CheckTicketAccess($id) & RT_RIGHT_WRITE)) {
    access_denied();
}

if ($id && !isset($_POST['ticket'])) {
    if (isset($action)) {
        switch ($action) {
            case 'verify':
                $LMS->TicketChange($id, array('state' => RT_VERIFIED, 'verifier_rtime' => time()));

                $queue = $LMS->GetQueueByTicketId($id);
                $ticket = $LMS->GetTicketContents($id);
                $user = $LMS->GetUserInfo(Auth::GetCurrentUser());

                if ($ticket['customerid']) {
                    $info = $LMS->GetCustomer($ticket['customerid'], true);

                    $emails = array_map(function ($contact) {
                        return $contact['fullname'];
                    }, $LMS->GetCustomerContacts($ticket['customerid'], CONTACT_EMAIL));
                    $phones = array_map(function ($contact) {
                        return $contact['fullname'];
                    }, $LMS->GetCustomerContacts($ticket['customerid'], CONTACT_LANDLINE | CONTACT_MOBILE));
                }

                $mailfname = '';

                $helpdesk_sender_name = ConfigHelper::getConfig('phpui.helpdesk_sender_name');
                if (!empty($helpdesk_sender_name)) {
                    if ($helpdesk_sender_name == 'queue') {
                        $mailfname = $queue['name'];
                    } elseif ($helpdesk_sender_name == 'user') {
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
                        'verifierid' => $ticket['verifierid'],
                        'customerid' => $ticket['customerid'],
                        'status' => $ticket['status'],
                        'categories' => $ticket['categorynames'],
                        'priority' => $RT_PRIORITIES[$ticket['priority']],
                        'deadline' => $ticket['deadline'],
                        'service' => $ticket['service'],
                        'type' => $ticket['type'],
                    );
                    $headers['Subject'] = $LMS->ReplaceNotificationSymbols($queue['verifierticketsubject'], $params);
                    $body = $LMS->ReplaceNotificationSymbols($queue['verifierticketbody'], $params);
                    $sms_body = $LMS->ReplaceNotificationSymbols(ConfigHelper::getConfig('phpui.helpdesk_notification_sms_body'), $params);

                    $LMS->NotifyUsers(array(
                        'queue' => $ticket['queue'],
                        'verifierid' => $params['verifierid'],
                        'mail_headers' => $headers,
                        'mail_body' => $body,
                        'sms_body' => $sms_body,
                    ));
                $SESSION->redirect('?m=rtticketview&id=' . $id);
                break;
            case 'assign':
                if (isset($_GET['check-conflict'])) {
                    header('Content-Type: application/json');
                    die(json_encode($LMS->TicketIsAssigned($id)));
                }
                $LMS->TicketChange($id, array('owner' => Auth::GetCurrentUser()));
                $SESSION->redirect('?m=rtticketview&id=' . $id);
                break;
            case 'read':
                $LMS->MarkTicketAsRead($id);
                $SESSION->redirect('?m=rtqueueview'
                    . ($SESSION->is_set('backid') ? '#' . $SESSION->get('backid') : ''));
                break;
            case 'unread':
                $LMS->MarkTicketAsUnread($id);
                $SESSION->redirect('?m=rtqueueview'
                    . ($SESSION->is_set('backid') ? '#' . $SESSION->get('backid') : ''));
                break;
            case 'resolve':
                $LMS->TicketChange($id, array('state' => RT_RESOLVED));

                $queue = $LMS->GetQueueByTicketId($id);
                $user = $LMS->GetUserInfo(Auth::GetCurrentUser());
                $ticket = $LMS->GetTicketContents($id);
                if ($ticket['customerid']) {
                    $info = $LMS->GetCustomer($ticket['customerid'], true);

                    $emails = array_map(function ($contact) {
                        return $contact['fullname'];
                    }, $LMS->GetCustomerContacts($ticket['customerid'], CONTACT_EMAIL));
                    $phones = array_map(function ($contact) {
                        return $contact['fullname'];
                    }, $LMS->GetCustomerContacts($ticket['customerid'], CONTACT_LANDLINE | CONTACT_MOBILE));
                }

                $mailfname = '';

                $helpdesk_sender_name = ConfigHelper::getConfig('phpui.helpdesk_sender_name');
                if (!empty($helpdesk_sender_name)) {
                    if ($helpdesk_sender_name == 'queue') {
                        $mailfname = $queue['name'];
                    } elseif ($helpdesk_sender_name == 'user') {
                        $mailfname = $user['name'];
                    }

                    $mailfname = '"' . $mailfname . '"';
                }

                $mailfrom = $LMS->DetermineSenderEmail($user['email'], $queue['email'], $ticket['requestor_mail']);

                $from = $mailfname . ' <' . $mailfrom . '>';

                if ($state == RT_RESOLVED) {
                    if (!empty($queue['resolveticketsubject']) && !empty($queue['resolveticketbody'])) {
                        if (!empty($ticket['customerid'])) {
                            if (!empty($emails)) {
                                $ticketid = sprintf("%06d", $id);
                                $custmail_subject = $queue['resolveticketsubject'];
                                $custmail_subject = str_replace('%tid', $ticketid, $custmail_subject);
                                $custmail_subject = str_replace('%title', $ticket['subject'], $custmail_subject);
                                $custmail_body = $queue['resolveticketbody'];
                                $custmail_body = str_replace('%tid', $ticketid, $custmail_body);
                                $custmail_body = str_replace('%cid', $info['id'], $custmail_body);
                                $custmail_body = str_replace('%pin', $info['pin'], $custmail_body);
                                $custmail_body = str_replace('%customername', $info['customername'], $custmail_body);
                                $custmail_body = str_replace('%title', $ticket['subject'], $custmail_body);
                                $custmail_headers = array(
                                    'From' => $from,
                                    'Reply-To' => $from,
                                    'Subject' => $custmail_subject,
                                );
                                $smtp_options = $LMS->GetRTSmtpOptions();
                                foreach (explode(',', $info['emails']) as $email) {
                                    $custmail_headers['To'] = '<' . $email . '>';
                                    $LMS->SendMail($email, $custmail_headers, $custmail_body, null, null, $smtp_options);
                                }
                            }
                        }
                    }
                }

                $ticket_property_change_notify = ConfigHelper::checkConfig('phpui.ticket_property_change_notify');
                if ($ticket_property_change_notify) {
                    $headers['From'] = $from;
                    $headers['Reply-To'] = $headers['From'];

                    if (ConfigHelper::checkConfig('phpui.helpdesk_customerinfo')) {
                        if ($ticket['customerid']) {
                            $params = array(
                                'id' => $id,
                                'customerid' => $ticket['customerid'],
                                'customer' => $info,
                                'emails' => $emails,
                                'phones' => $phones,
                            );
                            $mail_customerinfo = $LMS->ReplaceNotificationCustomerSymbols(ConfigHelper::getConfig('phpui.helpdesk_customerinfo_mail_body'), $params);
                            $sms_customerinfo = $LMS->ReplaceNotificationCustomerSymbols(ConfigHelper::getConfig('phpui.helpdesk_customerinfo_sms_body'), $params);
                        } else {
                            $mail_customerinfo = "\n\n-- \n" . trans('Customer:') . ' ' . $ticket['requestor'];
                            $sms_customerinfo = "\n" . trans('Customer:') . ' ' . $ticket['requestor'];
                        }
                    }

                    $message = end($ticket['messages']);
                    $message['body'] = str_replace('<br>', "\n", $message['body']);

                    $params = array(
                        'id' => $id,
                        'queue' => $queue['name'],
                        'customerid' => $ticket['customerid'],
                        'status' => $ticket['status'],
                        'categories' => $ticket['categorynames'],
                        'priority' => $RT_PRIORITIES[$ticket['priority']],
                        'deadline' => $ticket['deadline'],
                        'service' => $ticket['service'],
                        'type' => $ticket['type'],
                        'subject' => $ticket['subject'],
                        'body' => $message['body'],
                    );
                    $headers['Subject'] = $LMS->ReplaceNotificationSymbols(ConfigHelper::getConfig('phpui.helpdesk_notification_mail_subject'), $params);
                    $params['customerinfo'] = isset($mail_customerinfo) ? $mail_customerinfo : null;
                    $body = $LMS->ReplaceNotificationSymbols(ConfigHelper::getConfig('phpui.helpdesk_notification_mail_body'), $params);
                    $params['customerinfo'] = isset($sms_customerinfo) ? $sms_customerinfo : null;
                    $sms_body = $LMS->ReplaceNotificationSymbols(ConfigHelper::getConfig('phpui.helpdesk_notification_sms_body'), $params);

                    $LMS->NotifyUsers(array(
                        'queue' => $ticket['queue'],
                        'mail_headers' => $headers,
                        'mail_body' => $body,
                        'sms_body' => $sms_body,
                    ));
                }

                $SESSION->redirect('?m=rtticketview&id=' . $id);
        }
    }
}

$allow_empty_categories = ConfigHelper::checkConfig('phpui.helpdesk_allow_empty_categories');
$empty_category_warning = ConfigHelper::checkValue(ConfigHelper::getConfig('phpui.helpdesk_empty_category_warning', true));

$ticket = $LMS->GetTicketContents($id);
$ticket['oldverifierid'] = $ticket['verifierid'];
$categories = $LMS->GetUserCategories(Auth::GetCurrentUser());
if (empty($categories)) {
    $categories = array();
}

if (isset($_POST['ticket'])) {
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

    if (ConfigHelper::checkValue(ConfigHelper::getConfig('phpui.helpdesk_check_owner_verifier_conflict', true))
        && !empty($ticketedit['verifierid']) && $ticketedit['verifierid'] == $ticketedit['owner']) {
        $error['verifierid'] = trans('Ticket owner could not be the same as verifier!');
        $error['owner'] = trans('Ticket verifier could not be the same as owner!');
    }

    $deadline = datetime_to_timestamp($ticketedit['deadline']);
    if ($deadline != $ticket['deadline']) {
        if (!ConfigHelper::checkConfig('phpui.helpdesk_allow_all_users_modify_deadline')
            && !empty($ticket['verifierid']) && $ticket['verifierid'] != Auth::GetCurrentUser()) {
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

    if (($LMS->GetUserRightsRT(Auth::GetCurrentUser(), $ticketedit['queue']) & 2) != 2) {
        $error['queue'] = trans('You have no privileges to this queue!');
    }

    if ($ticketedit['subject'] == '') {
        $error['subject'] = trans('Ticket must have its title!');
    }

    if (ConfigHelper::checkConfig('phpui.helpdesk_block_ticket_close_with_open_events')) {
        if ($ticketedit['state'] == RT_RESOLVED && !empty($ticket['openeventcount'])) {
            $error['state'] = trans('Ticket have open assigned events!');
        }
    }

    if ($ticketedit['state'] != RT_NEW && !$ticketedit['owner']) {
        $error['owner'] = trans('Only \'new\' ticket can be owned by no one!');
    }

    if (!ConfigHelper::checkConfig('phpui.helpdesk_allow_change_ticket_state_from_open_to_new')) {
        if ($ticketedit['state'] == RT_NEW && $ticketedit['owner']) {
            $ticketedit['state'] = RT_OPEN;
        }
    }

    $ticketedit['customerid'] = ($ticketedit['custid'] ? $ticketedit['custid'] : 0);

    if ($ticketedit['requestor_userid'] == '0') {
        if (empty($ticketedit['requestor_name']) && empty($ticketedit['requestor_mail']) && empty($ticketedit['requestor_phone'])) {
            $error['requestor_name'] = $error['requestor_mail'] = $error['requestor_phone'] =
                trans('At least requestor name, mail or phone should be filled!');
        }
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
            'priority' => $ticketedit['priority'],
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
            'requestor_mail' => !empty($ticketedit['requestor_userid']) || $ticketedit['requestor_userid'] == ''
                || empty($ticketedit['requestor_mail']) ? null : $ticketedit['requestor_mail'],
            'requestor_phone' => !empty($ticketedit['requestor_userid']) || $ticketedit['requestor_userid'] == ''
                || empty($ticketedit['requestor_phone']) ? null : $ticketedit['requestor_phone'],
            'parentid' => empty($ticketedit['parentid']) ? null : $ticketedit['parentid'],
            'relatedtickets' => $ticketedit['relatedtickets'],
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
        $newticket_notify = ConfigHelper::checkConfig('phpui.newticket_notify');
        $ticket_property_change_notify = ConfigHelper::checkConfig('phpui.ticket_property_change_notify');
        if (($ticket_property_change_notify && ($ticket['state'] != $ticketedit['state']
                || $ticket['owner'] != $ticketedit['owner']
                || $ticket['deadline'] != $ticketedit['deadline']
                || $ticket['priority'] != $ticketedit['priority']
                || $ticket['parentid'] != $ticketedit['parentid']))
            || ($ticket['queueid'] != $ticketedit['queue'] && !empty($newticket_notify))) {
            $user = $LMS->GetUserInfo(Auth::GetCurrentUser());
            $queue = $LMS->GetQueueByTicketId($ticket['ticketid']);
            $mailfname = '';

            $helpdesk_sender_name = ConfigHelper::getConfig('phpui.helpdesk_sender_name');
            if (!empty($helpdesk_sender_name)) {
                if ($helpdesk_sender_name == 'queue') {
                    $mailfname = $queue['name'];
                } elseif ($helpdesk_sender_name == 'user') {
                    $mailfname = $user['name'];
                } else {
                    $mailfname = $helpdesk_sender_name;
                }

                $mailfname = '"' . $mailfname . '"';
            }

            $ticketdata = $LMS->GetTicketContents($ticket['ticketid']);

            $mailfrom = $LMS->DetermineSenderEmail($user['email'], $queue['email'], $ticketdata['requestor_mail']);

            $headers['From'] = $mailfname . ' <' . $mailfrom . '>';
            $headers['Reply-To'] = $headers['From'];

            if (ConfigHelper::checkConfig('phpui.helpdesk_customerinfo')) {
                if ($ticketedit['customerid']) {
                    $info = $LMS->GetCustomer($ticketedit['customerid'], true);

                    $emails = array_map(function ($contact) {
                            return $contact['fullname'];
                    }, $LMS->GetCustomerContacts($ticketedit['customerid'], CONTACT_EMAIL));
                    $phones = array_map(function ($contact) {
                            return $contact['fullname'];
                    }, $LMS->GetCustomerContacts($ticketedit['customerid'], CONTACT_LANDLINE | CONTACT_MOBILE));

                    $params = array(
                        'id' => $ticket['ticketid'],
                        'customerid' => $ticketedit['customerid'],
                        'customer' => $info,
                        'emails' => $emails,
                        'phones' => $phones,
                    );
                    $mail_customerinfo = $LMS->ReplaceNotificationCustomerSymbols(ConfigHelper::getConfig('phpui.helpdesk_customerinfo_mail_body'), $params);
                    $sms_customerinfo = $LMS->ReplaceNotificationCustomerSymbols(ConfigHelper::getConfig('phpui.helpdesk_customerinfo_sms_body'), $params);
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
                'priority' => $RT_PRIORITIES[$ticketdata['priority']],
                'deadline' => $ticketdata['deadline'],
                'subject' => $ticket['subject'],
                'body' => $message['body'],
            );
            $headers['Subject'] = $LMS->ReplaceNotificationSymbols(ConfigHelper::getConfig('phpui.helpdesk_notification_mail_subject'), $params);
            $params['customerinfo'] =  isset($mail_customerinfo) ? $mail_customerinfo : null;
            $body = $LMS->ReplaceNotificationSymbols(ConfigHelper::getConfig('phpui.helpdesk_notification_mail_body'), $params);
            $params['customerinfo'] =  isset($sms_customerinfo) ? $sms_customerinfo : null;
            $sms_body = $LMS->ReplaceNotificationSymbols(ConfigHelper::getConfig('phpui.helpdesk_notification_sms_body'), $params);

            $LMS->NotifyUsers(array(
                'queue' => $ticketedit['queue'],
                'oldqueue' => $ticket['queueid'] == $ticketedit['queue'] ? null : $ticket['queueid'],
                'mail_headers' => $headers,
                'mail_body' => $body,
                'sms_body' => $sms_body,
            ));
        }

        $backto = $SESSION->get('backto');
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
    $ticket['netnodeid'] = $ticketedit['netnodeid'];
    $ticket['netdevid'] = $ticketedit['netdevid'];
    $ticket['priority'] = $ticketedit['priority'];
    $ticket['requestor_userid'] = $ticketedit['requestor_userid'];
    $ticket['requestor_name'] = $ticketedit['requestor_name'];
    $ticket['requestor_mail'] = $ticketedit['requestor_mail'];
    $ticket['requestor_phone'] = $ticketedit['requestor_phone'];
    $ticket['parentid'] = $ticketedit['parentid'];
    $ticket['categorywarn'] = $ticketedit['categorywarn'];

    if (!empty($ticketedit['relatedtickets'])) {
        $ticket['relatedtickets'] = $LMS->getSelectedRelatedTickets($ticketedit['relatedtickets']);
    }
} else {
    $ticketedit['categories'] = $ticket['categories'];

    $ticketedit['categorywarn'] = 0;
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
unset($netnodelist['total']);
unset($netnodelist['order']);
unset($netnodelist['direction']);

$invprojectlist = $LMS->GetProjects('name', array());
unset($invprojectlist['total']);
unset($invprojectlist['order']);
unset($invprojectlist['direction']);

if (isset($ticket['netnodeid']) && !empty($ticket['netnodeid'])) {
    $search = array('netnode' => $ticket['netnodeid']);
} else {
    $search = array();
}
$search['short'] = true;
$netdevlist = $LMS->GetNetDevList('name', $search);
unset($netdevlist['total']);
unset($netdevlist['order']);
unset($netdevlist['direction']);

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
}

$SMARTY->assign('ticket', $ticket);
$SMARTY->assign('customerid', $ticket['customerid']);
$SMARTY->assign('queuelist', $queuelist);
$SMARTY->assign('queue', $ticket['queueid']);
$SMARTY->assign('categories', $categories);
$SMARTY->assign('netnodelist', $netnodelist);
$SMARTY->assign('netdevlist', $netdevlist);
$SMARTY->assign('invprojectlist', $invprojectlist);
$SMARTY->assign('userlist', $LMS->GetUserNames());
$SMARTY->assign('error', $error);
$SMARTY->display('rt/rtticketedit.html');
