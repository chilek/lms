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
include(MODULES_DIR . DIRECTORY_SEPARATOR . 'rtticketxajax.inc.php');
$SMARTY->assign('xajax', $LMS->RunXajax());
$userid = Auth::GetCurrentUser();

$queue = isset($_GET['id']) ? intval($_GET['id']) : 0;
$ticket['netdevid'] = isset($_GET['netdevid']) ? intval($_GET['netdevid']) : 0;
if (!empty($ticket['netdevid'])) {
    $ticket['customerid'] = $LMS->getNetDevOwner($ticket['netdevid']);
}
if (!isset($ticket['customerid'])) {
    $ticket['customerid'] = isset($_GET['customerid']) && intval($_GET['customerid']) ? intval($_GET['customerid']) : '';
}
$ticket['netnodeid'] = isset($_GET['netnodeid']) ? intval($_GET['netnodeid']) : 0;
$ticket['invprojectid'] = isset($_GET['invprojectid']) ? intval($_GET['invprojectid']) : 0;
$ticket['parentid'] = isset($_GET['parentid']) ? intval($_GET['parentid']) : null;

$categories = $LMS->GetUserCategories($userid);
if (!$categories) {
    access_denied();
}

$allow_empty_categories = ConfigHelper::checkConfig('rt.allow_empty_categories', ConfigHelper::checkConfig('phpui.helpdesk_allow_empty_categories'));
$empty_category_warning = ConfigHelper::checkConfig('rt.empty_category_warning', ConfigHelper::checkConfig('phpui.helpdesk_empty_category_warning', true));

if (isset($_POST['ticket'])) {
    $ticket = $_POST['ticket'];
    $queue = $ticket['queue'];

    $result = handle_file_uploads('files', $error);
    extract($result);
    $SMARTY->assign('fileupload', $fileupload);

    if (ConfigHelper::checkConfig('rt.check_owner_verifier_conflict', ConfigHelper::checkConfig('phpui.helpdesk_check_owner_verifier_conflict', true))
        && !empty($ticket['verifierid']) && $ticket['verifierid'] == $ticket['owner']) {
        $error['verifierid'] = trans('Ticket owner could not be the same as verifier!');
        $error['owner'] = trans('Ticket verifier could not be the same as owner!');
    }

    $deadline = datetime_to_timestamp($ticket['deadline']);
    if ($deadline && $deadline < time()) {
        $error['deadline'] = trans('Ticket deadline could not be set in past!');
    }

    if (empty($ticket['categories']) && (!$allow_empty_categories || (empty($ticket['categorywarn']) && $empty_category_warning))) {
        if ($allow_empty_categories) {
            $ticket['categorywarn'] = 1;
            $error['categories'] = trans('Category selection is recommended but not required!');
        } else {
            $error['categories'] = trans('You have to select category!');
        }
    }

    if (($LMS->GetUserRightsRT($userid, $queue) & 2) != 2) {
        $error['queue'] = trans('You have no privileges to this queue!');
    }

    if ($ticket['subject'] == '') {
        $error['subject'] = trans('Ticket must have its title!');
    } elseif (mb_strlen($ticket['subject']) > ConfigHelper::getConfig('rt.subject_max_length', 50)) {
        $error['subject'] = trans('Ticket subject can contain maximum $a characters!', ConfigHelper::getConfig('rt.subject_max_length', 50));
    }

    if ($ticket['body'] == '') {
        $error['body'] = trans('Ticket must have its body!');
    }

    if (isset($ticket['mail']) && $ticket['mail']!='' && !check_email($ticket['mail'])) {
        $error['mail'] = trans('Incorrect email!');
    }

    if (!empty($ticket['requestor_phone'])) {
        if (strlen($ticket['requestor_phone']) > 32) {
            $error['phone'] = trans('Specified phone number is not correct!');
        }
    }

    if ((isset($ticket['customerid']) && $ticket['customerid'] !=0 && $ticket['custid'] != $ticket['customerid'])
        || (intval($ticket['custid']) && !$LMS->CustomerExists($ticket['custid']))) {
        $error['custid'] = trans('Specified ID is not proper or does not exist!');
    } else {
        $ticket['customerid'] = $ticket['custid'] ? $ticket['custid'] : 0;
        if ($ticket['customerid'] && $ticket['address_id'] <= 0) {
            $addresses = $LMS->getCustomerAddresses($ticket['customerid']);
            if (count($addresses) > 1 && !$_POST['address_id_warning']) {
                $error['address_id'] = trans('No address has been selected!');
                $SMARTY->assign('address_id_warning', 1);
            }
        }
    }

    if ($ticket['requestor_userid'] == '0') {
        if (empty($ticket['requestor_name']) && empty($ticket['requestor_mail']) && empty($ticket['requestor_phone'])) {
            $error['requestor_name'] = $error['requestor_mail'] = $error['requestor_phone'] =
                trans('At least requestor name, mail or phone should be filled!');
        }
    }

    $hook_data = $LMS->executeHook(
        'ticketadd_validation_before_submit',
        array(
            'ticket' => $ticket,
            'error' => $error,
        )
    );
    $ticket = $hook_data['ticket'];
    $error = $hook_data['error'];

    if (!empty($ticket['categories'])) {
        $ticket['categories'] = array_flip($ticket['categories']);
    }

    if (!$error) {
        $ticket['contenttype'] = isset($ticket['wysiwyg']) && isset($ticket['wysiwyg']['body']) && ConfigHelper::checkValue($ticket['wysiwyg']['body'])
            ? 'text/html' : 'text/plain';

        if (!$ticket['customerid']) {
            if ((!isset($ticket['requestor_name']) || $ticket['requestor_name'] == '')
                && (!isset($ticket['requestor_phone']) || $ticket['requestor_phone'] == '')
                && (!isset($ticket['requestor_mail']) || $ticket['requestor_mail'] == '')) {
                $userinfo = $LMS->GetUserInfo($userid);
                $ticket['requestor_userid'] = $userinfo['id'];
            }
        }

        if ($ticket['address_id'] == -1) {
            $ticket['address_id'] = null;
        }

        if (empty($ticket['nodeid'])) {
            $ticket['nodeid'] = null;
        }

        if (empty($ticket['netnodeid'])) {
            $ticket['netnodeid'] = null;
        }

        if (empty($ticket['netdevid'])) {
            $ticket['netdevid'] = null;
        }

        if (empty($ticket['invprojectid'])) {
            $ticket['invprojectid'] = null;
        }

        if (!empty($ticket['requestor_userid'])) {
            $ticket['requestor'] = '';
            $ticket['requestor_mail'] = null;
            $ticket['requestor_phone'] = null;
        } else {
            $ticket['requestor_userid'] = null;
            $ticket['requestor'] = empty($ticket['requestor_name']) ? '' : $ticket['requestor_name'];
            $ticket['requestor_mail'] = empty($ticket['requestor_mail']) ? null : $ticket['requestor_mail'];
            $ticket['requestor_phone'] = empty($ticket['requestor_phone']) ? null : $ticket['requestor_phone'];
        }

        $ticket['verifierid'] = empty($ticket['verifierid']) ? null : $ticket['verifierid'];
        $ticket['deadline'] = empty($ticket['deadline']) ? null : $deadline;

        if (empty($ticket['type'])) {
            $ticket['type'] = null;
        }

        if (empty($ticket['service'])) {
            $ticket['service'] = null;
        }

        if ($ticket['priority'] == '') {
            unset($ticket['priority']);
        }

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
        $id = $LMS->TicketAdd($ticket, $files);

        $hook_data = $LMS->executeHook(
            'ticketadd_after_submit',
            array(
                'id' => $id,
                'ticket' => $ticket,
            )
        );
        $ticket = $hook_data['ticket'];
        // deletes uploaded files
        if (!empty($files) && !empty($tmppath)) {
            rrmdir($tmppath);
        }

        if ((isset($ticket['notify']) || isset($ticket['customernotify']))
            && ConfigHelper::checkConfig(
                'rt.new_ticket_notify',
                ConfigHelper::checkConfig('phpui.newticket_notify', true)
            )
        ) {
            $user = $LMS->GetUserInfo($userid);

            $helpdesk_sender_name = ConfigHelper::getConfig('rt.sender_name', ConfigHelper::getConfig('phpui.helpdesk_sender_name'));
            if (!empty($helpdesk_sender_name)) {
                $mailfname = $helpdesk_sender_name;

                if ($mailfname == 'queue') {
                    $mailfname = $LMS->GetQueueName($queue);
                } elseif ($mailfname == 'user') {
                    $mailfname = $user['name'];
                }
                $mailfname = '"'.$mailfname.'"';
            } else {
                $mailfname = '';
            }

            $mailfrom = $LMS->DetermineSenderEmail($user['email'], $LMS->GetQueueEmail($ticket['queue']), $ticket['requestor_mail']);

            $ticketdata = $LMS->GetTicketContents($id);

            $headers['From'] = $mailfname.' <'.$mailfrom.'>';
            $headers['Reply-To'] = $headers['From'];
            $headers['Message-ID'] = $LMS->GetLastMessageID();

            $queuedata = $LMS->GetQueue($queue);

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

                if (isset($ticket['notify'])
                    && ConfigHelper::checkConfig(
                        'rt.notification_customerinfo',
                        ConfigHelper::checkConfig('phpui.helpdesk_customerinfo')
                    )
                ) {
                    $params = array(
                        'id' => $id,
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
                }
            } elseif (!empty($requestor) && isset($ticket['notify'])
                && ConfigHelper::checkConfig(
                    'rt.notification_customerinfo',
                    ConfigHelper::checkConfig('phpui.helpdesk_customerinfo')
                )
            ) {
                $mail_customerinfo = "\n\n-- \n" . trans('Customer:') . ' ' . $requestor;
                $sms_customerinfo = "\n" . trans('Customer:') . ' ' . $requestor;
            }

            if (isset($ticket['notify'])) {
                $params = array(
                    'id' => $id,
                    'queue' => $queuedata['name'],
                    'customerid' => $ticket['customerid'],
                    'status' => $ticketdata['status'],
                    'categories' => $ticketdata['categorynames'],
                    'subject' => $ticket['subject'],
                    'body' => $ticket['body'],
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
                if ($ticketdata['priority']) {
                    $headers['X-Priority'] = $RT_MAIL_PRIORITIES[$ticketdata['priority']];
                }
                $headers['Subject'] = $LMS->ReplaceNotificationSymbols(ConfigHelper::getConfig('rt.notification_mail_subject', ConfigHelper::getConfig('phpui.helpdesk_notification_mail_subject')), $params);

                $params['customerinfo'] = isset($mail_customerinfo)
                    ? ($ticket['contenttype'] == 'text/html' ? str_replace("\n", '<br>', $mail_customerinfo) : $mail_customerinfo)
                    : null;
                $params['contenttype'] = $ticket['contenttype'];
                $body = $LMS->ReplaceNotificationSymbols(ConfigHelper::getConfig('rt.notification_mail_body', ConfigHelper::getConfig('phpui.helpdesk_notification_mail_body')), $params);

                if ($ticket['contenttype'] == 'text/html') {
                    $params['body'] = trans('(HTML content has been omitted)');
                    $headers['X-LMS-Format'] = 'html';
                }

                $params['customerinfo'] = isset($sms_customerinfo) ? $sms_customerinfo : null;
                $params['contenttype'] = 'text/plain';
                $sms_body = $LMS->ReplaceNotificationSymbols(ConfigHelper::getConfig('rt.notification_sms_body', ConfigHelper::getConfig('phpui.helpdesk_notification_sms_body')), $params);

                $LMS->NotifyUsers(array(
                    'queue' => $queue,
                    'verifierid' => $ticket['verifierid'],
                    'mail_headers' => $headers,
                    'mail_body' => $body,
                    'sms_body' => $sms_body,
                    'contenttype' => $ticket['contenttype'],
                    'attachments' => &$attachments,
                ));
            }
        }

        if (isset($ticket['customernotify']) && $ticket['customerid']
            && ConfigHelper::checkConfig(
                'rt.new_ticket_notify',
                ConfigHelper::checkConfig('phpui.newticket_notify', true)
            )
            && (!empty($mails) || !empty($mobile_phones))) {
            if (!empty($queuedata['newticketsubject']) && !empty($queuedata['newticketbody']) && !empty($emails)) {
                $custmail_subject = $queuedata['newticketsubject'];
                $custmail_subject = preg_replace_callback(
                    '/%(\\d*)tid/',
                    function ($m) use ($id) {
                        return sprintf('%0' . $m[1] . 'd', $id);
                    },
                    $custmail_subject
                );
                $custmail_subject = str_replace('%title', $ticket['subject'], $custmail_subject);
                $custmail_body = $queuedata['newticketbody'];
                $custmail_body = preg_replace_callback(
                    '/%(\\d*)tid/',
                    function ($m) use ($id) {
                        return sprintf('%0' . $m[1] . 'd', $id);
                    },
                    $custmail_body
                );
                $custmail_body = str_replace('%cid', $ticket['customerid'], $custmail_body);
                $custmail_body = str_replace('%pin', $info['pin'], $custmail_body);
                $custmail_body = str_replace('%customername', $info['customername'], $custmail_body);
                $custmail_body = str_replace('%title', $ticket['subject'], $custmail_body);
                $custmail_body = str_replace('%body', $ticket['body'], $custmail_body);
                $custmail_body = str_replace('%service', $ticket['service'], $custmail_body);
                $custmail_headers = array(
                    'From' => $headers['From'],
                    'Reply-To' => $headers['From'],
                    'Subject' => $custmail_subject,
                );
                $smtp_options = $LMS->GetRTSmtpOptions();
                $LMS->prepareMessageTemplates('rt');
                foreach ($emails as $email) {
                    $custmail_headers['To'] = '<' . (isset($info['email']) ? $info['email'] : $email) . '>';
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
            if (!empty($queuedata['newticketsmsbody']) && !empty($mobile_phones)) {
                $custsms_body = $queuedata['newticketsmsbody'];
                $custsms_body = preg_replace_callback(
                    '/%(\\d*)tid/',
                    function ($m) use ($id) {
                        return sprintf('%0' . $m[1] . 'd', $id);
                    },
                    $custsms_body
                );
                $custsms_body = str_replace('%cid', $ticket['customerid'], $custsms_body);
                $custsms_body = str_replace('%pin', $info['pin'], $custsms_body);
                $custsms_body = str_replace('%customername', $info['customername'], $custsms_body);
                $custsms_body = str_replace('%title', $ticket['subject'], $custsms_body);
                $custsms_body = str_replace('%body', $ticket['body'], $custsms_body);
                $custsms_body = str_replace('%service', $ticket['service'], $custsms_body);

                foreach ($mobile_phones as $phone) {
                    $LMS->SendSMS($phone['contact'], $custsms_body);
                }
            }
        }

        $SESSION->redirect('?m=rtticketview&id='.$id);
    }
    $SMARTY->assign('error', $error);

    $queuelist = $LMS->GetQueueList(array('stats' => false));

    foreach ($categories as &$category) {
        $category['checked'] = isset($ticket['categories'][$category['id']]) || count($categories) == 1;
    }
    unset($category);

    if (!empty($ticket['relatedtickets'])) {
        $ticket['relatedtickets'] = $LMS->getTickets($ticket['relatedtickets']);
    }
} else {
    $queuelist = $LMS->GetQueueList(array('stats' => false));
    if (!$queue && !empty($queuelist)) {
        $queue = ConfigHelper::getConfig('rt.default_queue');
        if (!empty($queue) && preg_match('/^[0-9]+$/', $queue)) {
            if (!$LMS->QueueExists($queue)) {
                $queue = 0;
            }
        } else {
            $queue = $LMS->GetQueueIdByName($queue);
        }
        if ($queue) {
            foreach ($queuelist as $firstqueue) {
                if ($firstqueue['id'] == $queue) {
                    break;
                }
                $firstqueue = null;
            }
            if (!isset($firstqueue)) {
                $queue = 0;
            }
        }
        if (!$queue) {
            $firstqueue = reset($queuelist);
            $queue = $firstqueue['id'];
        }
        $ticket['verifierid'] = $LMS->GetQueueVerifier($queue);
        if (($firstqueue['newticketsubject'] && $firstqueue['newticketbody']) || $firstqueue['newticketsmsbody']) {
            $ticket['customernotify'] = 1;
        }
    } elseif ($queue) {
        $queuedata = $LMS->GetQueue($queue);
        $ticket['verifierid'] = empty($queuedata['verifier']) ? 0 : $queuedata['verifier']['id'];
        if (($queuedata['newticketsubject'] && $queuedata['newticketbody']) || $queuedata['newticketsmsbody']) {
            $ticket['customernotify'] = 1;
        }
    }

    if (!isset($_GET['ticketid'])) {
        $queuecategories = $LMS->GetQueueCategories($queue);
        foreach ($categories as &$category) {
            if (isset($queuecategories[$category['id']]) || count($categories) == 1
                // handle category id got from welcome module so this category will be selected
                || (isset($_GET['catid']) && $category['id'] == intval($_GET['catid']))) {
                $category['checked'] = 1;
            }
        }
        unset($category);
    }

    if (ConfigHelper::checkConfig('rt.notify', ConfigHelper::checkConfig('phpui.helpdesk_notify'))) {
        $ticket['notify'] = true;
    }

    $ticket['categorywarn'] = 0;

    if (isset($_GET['ticketid'])) {
        $oldticket = $LMS->GetTicketContents($_GET['ticketid']);
        $ticket['queue'] = $oldticket['queueid'];
        $ticket['customerid'] = $oldticket['customerid'];
        if (!empty($oldticket['requestor_userid'])) {
            $ticket['requestor_userid'] = $oldticket['requestor_userid'];
        } elseif (!empty($oldticket['requestor_phone']) || !empty($oldticket['requestor_mail'])) {
            $ticket['requestor_userid'] = 0;
            $ticket['requestor_name'] = $oldticket['requestor'];
            $ticket['requestor_phone'] = $oldticket['requestor_phone'];
            $ticket['requestor_mail'] = $oldticket['requestor_mail'];
        }
        $ticket['subject'] = $oldticket['subject'];
        $ticket['service'] = $oldticket['service'];
        $ticket['type'] = $oldticket['type'];
        $oldmessage = reset($oldticket['messages']);
        if ($oldmessage['type'] == RTMESSAGE_REGULAR) {
            $ticket['body'] = $oldmessage['body'];
        } elseif ($oldmessage['type'] == RTMESSAGE_NOTE) {
            $ticket['note'] = $oldmessage['note'];
        }
        if (!empty($oldticket['categories'])) {
            foreach ($categories as &$category) {
                if (isset($oldticket['categories'][$category['id']])) {
                    $category['checked'] = 1;
                }
            }
        }
        $ticket['owner'] = $oldticket['owner'];
        $ticket['verifierid'] = $oldticket['verifierid'];
        $ticket['deadline'] = $oldticket['deadline'];
        $ticket['state'] = $oldticket['state'];
        $ticket['cause'] = $oldticket['cause'];
        $ticket['source'] = $oldticket['source'];
        $ticket['priority'] = $oldticket['priority'];
        $ticket['address_id'] = $oldticket['address_id'];
        $ticket['nodeid'] = $oldticket['nodeid'];
        $ticket['netnodeid'] = $oldticket['netnodeid'];
        $ticket['invprojectid'] = $oldticket['invprojectid'];
        $ticket['parentid'] = $oldticket['parentid'];
        $ticket['netdevid'] = $oldticket['netdevid'];
    }
}
if (!empty($ticket['parentid'])) {
    $ticket['parent'] = $LMS->getTickets($ticket['parentid']);
}

$layout['pagetitle'] = trans('New Ticket');

$SESSION->add_history_entry();

if (!ConfigHelper::checkConfig('phpui.big_networks')) {
    $SMARTY->assign('customerlist', $LMS->GetAllCustomerNames());
}

if (isset($ticket['customerid']) && intval($ticket['customerid'])) {
    $SMARTY->assign('nodes', $LMS->GetNodeLocations(
        $ticket['customerid'],
        isset($ticket['address_id']) && intval($ticket['address_id']) > 0 ? $ticket['address_id'] : null
    ));
    $SMARTY->assign('customerinfo', $LMS->GetCustomer($ticket['customerid']));
}

$netnodelist = $LMS->GetNetNodeList(array('short' => true), 'name');
unset($netnodelist['total'], $netnodelist['order'], $netnodelist['direction']);

if (isset($ticket['netnodeid']) && !empty($ticket['netnodeid'])) {
    $search = array('netnode' => $ticket['netnodeid']);
} else {
    $search = array();
}
$search['short'] = true;
$netdevlist = $LMS->GetNetDevList('name', $search);
unset($netdevlist['total'], $netdevlist['order'], $netdevlist['direction']);

$invprojectlist = $LMS->GetProjects('name', array());
unset($invprojectlist['total'], $invprojectlist['order'], $invprojectlist['direction']);

$hook_data = $LMS->executeHook(
    'ticketadd_before_display',
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

$SMARTY->assign(
    array(
        'ticket' => $ticket,
        'queue' => $queue,
        'queuelist' => $queuelist,
        'categories' => $categories,
        'netnodelist' => $netnodelist,
        'netdevlist' => $netdevlist,
        'invprojectlist' => $invprojectlist,
        'customerid' => $ticket['customerid'],
        'userlist' => $LMS->GetUserNames(array('withDeleted' => 1)),
        'messagetemplates' => $LMS->GetMessageTemplatesByQueueAndType($queue, RTMESSAGE_REGULAR),
        'notetemplates' => $LMS->GetMessageTemplatesByQueueAndType($queue, RTMESSAGE_NOTE),
    )
);
$SMARTY->display('rt/rtticketadd.html');
