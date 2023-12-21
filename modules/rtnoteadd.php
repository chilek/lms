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
include(MODULES_DIR . DIRECTORY_SEPARATOR . 'rtnotexajax.inc.php');
$SMARTY->assign('xajax', $LMS->RunXajax());

$categories = $LMS->GetUserCategories(Auth::GetCurrentUser());
if (empty($categories)) {
    $categories = array();
}

if (isset($_GET['ticketid'])) {
    $note['ticketid'] = intval($_GET['ticketid']);

    if (!($LMS->CheckTicketAccess($note['ticketid']) & RT_RIGHT_WRITE)) {
        access_denied();
    }

    $LMS->MarkTicketAsRead($note['ticketid']);

    $note = $DB->GetRow('SELECT id AS ticketid, state, cause, queueid, owner FROM rttickets WHERE id = ?', array($note['ticketid']));
    $reply = $LMS->GetFirstMessage($note['ticketid']);
    $note['inreplyto'] = $reply['id'];
    $note['references'] = empty($reply['references']) ? '' : implode(' ', $reply['references']);

    $note['category_change'] = 0;

    if (ConfigHelper::checkConfig('rt.notify', ConfigHelper::checkConfig('phpui.helpdesk_notify'))) {
        $note['notify'] = true;
    }

    $ticket = $LMS->GetTicketContents($note['ticketid']);
    $note['categories'] = $ticket['categories'];
} elseif (isset($_POST['note'])) {
    $note = $_POST['note'];

    if (!($LMS->CheckTicketAccess($note['ticketid']) & RT_RIGHT_WRITE)) {
        access_denied();
    }

    $ticket = $LMS->GetTicketContents($note['ticketid']);

    if (ConfigHelper::checkConfig('rt.block_ticket_close_with_open_events', ConfigHelper::checkConfig('phpui.helpdesk_block_ticket_close_with_open_events'))
        && $note['state'] == RT_RESOLVED && !empty($ticket['openeventcount'])) {
        $error['state'] = trans('Ticket have open assigned events!');
    }

    if ($note['body'] == '') {
        $error['body'] = trans('Note body not specified!');
    }

    if (!isset($note['ticketid']) || !intval($note['ticketid'])) {
        $SESSION->redirect('?m=rtqueuelist');
    }

    if (ConfigHelper::checkConfig('rt.check_owner_verifier_conflict', ConfigHelper::checkConfig('phpui.helpdesk_check_owner_verifier_conflict', true))
        && !empty($note['verifierid']) && $note['verifierid'] == $note['owner']) {
        $error['verifierid'] = trans('Ticket owner could not be the same as verifier!');
        $error['owner'] = trans('Ticket verifier could not be the same as owner!');
    }

    $deadline = datetime_to_timestamp($note['deadline']);
    if ($deadline != $ticket['deadline']) {
        if (!ConfigHelper::checkConfig('rt.allow_all_users_modify_deadline', ConfigHelper::checkConfig('phpui.helpdesk_allow_all_users_modify_deadline'))
            && !empty($note['verifierid']) && $note['verifierid'] != Auth::GetCurrentUser()) {
            $error['deadline'] = trans('If verifier is set then he\'s the only person who can change deadline!');
            $note['deadline'] = $ticket['deadline'];
        }
        if ($deadline && $deadline < time()) {
            $error['deadline'] = trans('Ticket deadline could not be set in past!');
        }
    }

    $LMS->MarkTicketAsRead($note['ticketid']);

    $result = handle_file_uploads('files', $error);
    extract($result);
    $SMARTY->assign('fileupload', $fileupload);

    if (!$error) {
        $messageid = '<msg.' . $ticket['queueid'] . '.' . $note['ticketid'] . '.'  . time() . '@rtsystem.' . gethostname() . '>';

        $note['categories'] = !empty($note['categories']) && is_array($note['categories']) ? array_flip($note['categories']) : array();

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
        }
        $msgid = $LMS->TicketMessageAdd(array(
                'ticketid' => $note['ticketid'],
                'messageid' => $messageid,
                'body' => $note['body'],
                'type' => RTMESSAGE_NOTE,
            ), $files);

        // deletes uploaded files
        if (!empty($files) && !empty($tmppath)) {
            rrmdir($tmppath);
        }

        // setting status and the ticket owner
        $props = array(
            'queueid' => $note['queueid'],
            'owner' => empty($note['owner']) ? null : $note['owner'],
            'cause' => $note['cause'],
            'state' => $note['state'],
            'source' => $note['source'],
            'priority' => isset($note['priority']) ? $note['priority'] : null,
            'verifierid' => empty($note['verifierid']) ? null : $note['verifierid'],
            'deadline' => empty($note['deadline']) ? null : $deadline,
        );

        if ($note['category_change']) {
            $props['category_change'] = $note['category_change'];
            $props['categories'] = isset($note['categories']) ? array_keys($note['categories']) : array();
        }

        $LMS->TicketChange($note['ticketid'], $props);

        if (isset($note['notify'])) {
            $user = $LMS->GetUserInfo(Auth::GetCurrentUser());
            $queue = $LMS->GetQueueByTicketId($note['ticketid']);
            $mailfname = '';

            $helpdesk_sender_name = ConfigHelper::getConfig('rt.sender_name', ConfigHelper::getConfig('phpui.helpdesk_sender_name'));
            if (!empty($helpdesk_sender_name)) {
                $mailfname = $helpdesk_sender_name;

                if ($mailfname == 'queue') {
                    $mailfname = $queue['name'];
                } elseif ($mailfname == 'user') {
                    $mailfname = $user['name'];
                }

                $mailfname = '"'.$mailfname.'"';
            }

            $ticket = $LMS->GetTicketContents($note['ticketid']);

            $mailfrom = $LMS->DetermineSenderEmail($user['email'], $queue['email'], $ticket['requestor_mail']);

            $headers['From'] = $mailfname.' <'.$mailfrom.'>';
            $headers['Reply-To'] = $headers['From'];
            if ($note['references']) {
                $headers['References'] = explode(' ', $note['references']);
                $headers['In-Reply-To'] = array_pop(explode(' ', $note['references']));
            }

            if (ConfigHelper::checkConfig(
                'rt.notification_customerinfo',
                ConfigHelper::checkConfig('phpui.helpdesk_customerinfo')
            )) {
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

                    $params = array(
                        'id' => $note['ticketid'],
                        'customerid' => $ticket['customerid'],
                        'customer' => $info,
                        'emails' => $emails,
                        'phones' => $phones,
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
                } else {
                    $mail_customerinfo = "\n\n-- \n" . trans('Customer:') . ' ' . $ticket['requestor'];
                    $sms_customerinfo = "\n" . trans('Customer:') . ' ' . $ticket['requestor'];
                }
            }

            $params = array(
                'id' => $note['ticketid'],
                'author' => Auth::GetCurrentUserName(),
                'queue' => $queue['name'],
                'messageid' => isset($msgid) ? $msgid : null,
                'customerid' => $ticket['customerid'],
                'status' => $ticket['status'],
                'categories' => $ticket['categorynames'],
                'priority' => isset($ticket['priority']) && is_numeric($ticket['priority']) ? $RT_PRIORITIES[$ticket['priority']] : '',
                'deadline' => $ticket['deadline'],
                'subject' => $ticket['subject'],
                'body' => $note['body'],
                'attachments' => &$attachments,
            );

            if (isset($ticket['priority'])) {
                $headers['X-Priority'] = $RT_MAIL_PRIORITIES[$ticket['priority']];
            }

            if (ConfigHelper::checkConfig('rt.note_send_re_in_subject')) {
                $params['subject'] = 'Re: ' . $LMS->cleanupTicketSubject($ticket['subject']);
            }

            $headers['Subject'] = $LMS->ReplaceNotificationSymbols(ConfigHelper::getConfig('rt.notification_mail_subject', ConfigHelper::getConfig('phpui.helpdesk_notification_mail_subject')), $params);
            $params['customerinfo'] = isset($mail_customerinfo) ? $mail_customerinfo : null;
            $body = $LMS->ReplaceNotificationSymbols(ConfigHelper::getConfig('rt.notification_mail_body', ConfigHelper::getConfig('phpui.helpdesk_notification_mail_body')), $params);
            $params['customerinfo'] = isset($sms_customerinfo) ? $sms_customerinfo : null;
            $sms_body = $LMS->ReplaceNotificationSymbols(ConfigHelper::getConfig('rt.notification_sms_body', ConfigHelper::getConfig('phpui.helpdesk_notification_sms_body')), $params);

            // Don't notify verifier adding note
            $currentuser = Auth::GetCurrentUser();
            $author_notify = ConfigHelper::checkConfig('rt.author_notify');
            if (empty($note['verifierid'])
                || !$author_notify
                || $props['verifierid'] == $currentuser
                || $note['verifierid'] == $currentuser) {
                $note['verifierid'] = null;
            }

            $LMS->NotifyUsers(array(
                'queue' => $queue['id'],
                'verifierid' => $note['verifierid'],
                'mail_headers' => $headers,
                'mail_body' => $body,
                'sms_body' => $sms_body,
                'attachments' => &$attachments,
                'recipients' => ($note['notify'] ? RT_NOTIFICATION_USER : 0)
                    | (empty($note['verifierid']) ? 0 : RT_NOTIFICATION_VERIFIER),
            ));
        }

        $backto = $SESSION->remove_history_entry();
        if (strpos($backto, 'rtqueueview') === false && isset($msgid)) {
            $SESSION->redirect('?m=rtticketview&id=' . $note['ticketid'] . (isset($msgid) ? '#rtmessage-' . $msgid : ''));
        } elseif (strpos($backto, 'rtqueueview') !== false) {
            $SESSION->redirect('?' . $backto
                . ($SESSION->is_set('backid') ? '#' . $SESSION->get('backid') : ''));
        } else {
            $SESSION->redirect('?' . $backto);
        }
    }

    if (!empty($note['categories'])) {
        $note['categories'] = array_flip($note['categories']);
    }
} else {
    $SESSION->redirect('?m=rtqueuelist');
}

$layout['pagetitle'] = trans('New Note');

$SMARTY->assign('ticket', $ticket);
if (!isset($_POST['note'])) {
    $note['source'] = $ticket['source'];
    $note['priority'] = isset($ticket['priority']) ? $ticket['priority'] : null;
    $note['verifierid'] = $ticket['verifierid'];
    $note['deadline'] = $ticket['deadline'];
    $notechangestateafter = ConfigHelper::getConfig('rt.change_ticket_state_to_open_after_note_add_interval', 0);

    if ($note['state'] == RT_NEW && (isset($notechangestateafter) && time()-$ticket['createtime'] > $notechangestateafter)) {
        $note['state'] = RT_OPEN;
    }
}

foreach ($categories as &$category) {
    $category['checked'] = isset($note['categories'][$category['id']]);
}
unset($category);

$SMARTY->assign('categories', $categories);
$SMARTY->assign('note', $note);
$SMARTY->assign('userlist', $LMS->GetUserNames(array('withDeleted' => 1)));
$SMARTY->assign('queuelist', $LMS->LimitQueuesToUserpanelEnabled($LMS->GetQueueList(array('stats' => false)), $note['queueid']));
$SMARTY->assign('notetemplates', $LMS->GetMessageTemplatesByQueueAndType($note['queueid'], RTMESSAGE_NOTE));
$SMARTY->assign('error', $error);
$SMARTY->display('rt/rtnoteadd.html');
