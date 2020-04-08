<?php

/*
 *  LMS version 1.11-git
 *
 *  (C) Copyright 2001-2017 LMS Developers
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

if (defined('USERPANEL_SETUPMODE')) {
    function module_setup()
    {
        global $SMARTY, $LMS, $AUTH;

        $default_categories = explode(',', ConfigHelper::getConfig('userpanel.default_categories'));
        $categories = $LMS->GetUserCategories(Auth::GetCurrentUser());
        foreach ($categories as $category) {
            if (in_array($category['id'], $default_categories)) {
                $category['checked'] = true;
            }
            $ncategories[] = $category;
        }
        $categories = $ncategories;

        $SMARTY->assign('userlist', $LMS->GetUserNames());
        $SMARTY->assign('queuelist', $LMS->GetQueueNames());
        $SMARTY->assign('queues', explode(';', ConfigHelper::getConfig('userpanel.queues')));
        $SMARTY->assign('sources', explode(';', ConfigHelper::getConfig('userpanel.visible_ticket_sources')));
        $SMARTY->assign('tickets_from_selected_queues', ConfigHelper::getConfig('userpanel.tickets_from_selected_queues'));
        $SMARTY->assign('allow_message_add_to_closed_tickets', ConfigHelper::getConfig('userpanel.allow_message_add_to_closed_tickets'));
        $SMARTY->assign('limit_ticket_movements_to_selected_queues', ConfigHelper::getConfig('userpanel.limit_ticket_movements_to_selected_queues'));
        $SMARTY->assign('default_userid', ConfigHelper::getConfig('userpanel.default_userid'));
        $SMARTY->assign('lms_url', ConfigHelper::getConfig('userpanel.lms_url'));
        $SMARTY->assign('categories', $categories);

        $allow_reopen_tickets_newer_than = ConfigHelper::getConfig('userpanel.allow_reopen_tickets_newer_than');
        if (empty($allow_reopen_tickets_newer_than)) {
            $allow_reopen_tickets_newer_than = '';
        }
        $SMARTY->assign('allow_reopen_tickets_newer_than', $allow_reopen_tickets_newer_than);

        $SMARTY->display('module:helpdesk:setup.html');
    }

    function module_submit_setup()
    {
        $DB = LMSDB::getInstance();
        if (!empty($_POST['queues']) && ($queues = Utils::filterIntegers($_POST['queues']))) {
            $DB->Execute('UPDATE uiconfig SET value = ? WHERE section = \'userpanel\' AND var = \'queues\'', array(implode(';', $queues)));
        }
        if (!empty($_POST['sources']) && ($sources = Utils::filterIntegers($_POST['sources']))) {
            $DB->Execute(
                'UPDATE uiconfig SET value = ? WHERE section = \'userpanel\' AND var = \'visible_ticket_sources\'',
                array(implode(';', $sources))
            );
        }
        $DB->Execute(
            'UPDATE uiconfig SET value = ? WHERE section = \'userpanel\' AND var = \'tickets_from_selected_queues\'',
            array(intval($_POST['tickets_from_selected_queues']))
        );
        $DB->Execute(
            'UPDATE uiconfig SET value = ? WHERE section = \'userpanel\' AND var = \'allow_message_add_to_closed_tickets\'',
            array(intval($_POST['allow_message_add_to_closed_tickets']))
        );
        $DB->Execute(
            'UPDATE uiconfig SET value = ? WHERE section = \'userpanel\' AND var = \'limit_ticket_movements_to_selected_queues\'',
            array(intval($_POST['limit_ticket_movements_to_selected_queues']))
        );
        $DB->Execute('UPDATE uiconfig SET value = ? WHERE section = \'userpanel\' AND var = \'default_userid\'', array($_POST['default_userid']));
        $DB->Execute('UPDATE uiconfig SET value = ? WHERE section = \'userpanel\' AND var = \'lms_url\'', array($_POST['lms_url']));
        $categories = array_keys((isset($_POST['lms_categories']) ? $_POST['lms_categories'] : array()));
        $DB->Execute('UPDATE uiconfig SET value = ? WHERE section = \'userpanel\' AND var = \'default_categories\'', array(implode(',', $categories)));
        $DB->Execute(
            'UPDATE uiconfig SET value = ? WHERE section = ? AND var = ?',
            array(intval($_POST['allow_reopen_tickets_newer_than']), 'userpanel' , 'allow_reopen_tickets_newer_than')
        );
        header('Location: ?m=userpanel&module=helpdesk');
    }
}

function module_main()
{
    global $SMARTY, $LMS, $SESSION, $RT_PRIORITIES;

    $DB = LMSDB::getInstance();

    $error = null;

    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;

    $files = array();
    $attachments = array();

    if (isset($_FILES['files'])) {
        foreach ($_FILES['files']['name'] as $fileidx => $filename) {
            if (!empty($filename)) {
                if (is_uploaded_file($_FILES['files']['tmp_name'][$fileidx]) && $_FILES['files']['size'][$fileidx]) {
                    $filecontents = '';
                    $fd = fopen($_FILES['files']['tmp_name'][$fileidx], 'r');
                    if ($fd) {
                        while (!feof($fd)) {
                            $filecontents .= fread($fd, 256);
                        }
                        fclose($fd);
                    }
                    $files[] = array(
                    'name' => $filename,
                    'tmp_name' => $_FILES['files']['tmp_name'][$fileidx],
                    'type' => $_FILES['files']['type'][$fileidx],
                    'contents' => &$filecontents,
                    );
                    $attachments[] = array(
                    'content_type' => $_FILES['files']['type'][$fileidx],
                    'filename' => $filename,
                    'data' => &$filecontents,
                    );
                } else { // upload errors
                    if (isset($error['files'])) {
                        $error['files'] .= "\n";
                    } else {
                        $error['files'] = '';
                    }
                    switch ($_FILES['files']['error'][$fileidx]) {
                        case 1:
                        case 2:
                            $error['files'] .= trans('File is too large: $a', $filename);
                            break;
                        case 3:
                            $error['files'] .= trans('File upload has finished prematurely: $a', $filename);
                            break;
                        case 4:
                            $error['files'] .= trans('Path to file was not specified: $a', $filename);
                            break;
                        default:
                            $error['files'] .= trans('Problem during file upload: $a', $filename);
                            break;
                    }
                }
            }
        }
    }

    if (!$id && isset($_POST['helpdesk'])) {
        $ticket = $_POST['helpdesk'];

        $ticket['queue'] = intval($ticket['queue']);
        $ticket['categories'] = ConfigHelper::getConfig('userpanel.default_categories');
        $ticket['subject'] = strip_tags($ticket['subject']);
        $ticket['body'] = strip_tags($ticket['body']);

        if (!$ticket['queue']) {
            header('Location: ?m=helpdesk');
            die;
        }

        if ($ticket['subject'] == '' && $ticket['body'] == '') {
            header('Location: ?m=helpdesk');
            die;
        }

        if ($ticket['subject'] == '') {
            $ticket['subject'] = trans("No subject");
        } elseif (mb_strlen($ticket['subject']) > ConfigHelper::getConfig('rt.subject_max_length')) {
            $error['subject'] = trans('Ticket subject can contain maximum $a characters!', ConfigHelper::getConfig('rt.subject_max_length'));
        }

        if ($ticket['body'] == '') {
            $error['body'] = trans('Ticket must have its body!');
        }

        $queues = explode(';', ConfigHelper::getConfig('userpanel.queues'));
        if (!in_array($ticket['queue'], $queues)) {
            $error = true;
        }

        if (!$error) {
            $ticket['email'] = $LMS->GetCustomerEmail($SESSION->id);
            if (!empty($ticket['email'])) {
                $ticket['email'] = $ticket['email'][0];
            }
            $ticket['mailfrom'] = $ticket['email'] ? $ticket['email'] : '';

            $id = $LMS->TicketAdd(array(
                'queue' => $ticket['queue'],
                'subject' => $ticket['subject'],
                'customerid' => $SESSION->id,
                'requestor' => $LMS->GetCustomerName($SESSION->id),
                'createtime' => time(),
                'body' => $ticket['body'],
                'categories' => array_flip(explode(',', $ticket['categories'])),
                'mailfrom' => $ticket['mailfrom'],
                'source' => RT_SOURCE_USERPANEL), $files);

            if (ConfigHelper::checkConfig('phpui.newticket_notify')) {
                $user = $LMS->GetUserInfo(ConfigHelper::getConfig('userpanel.default_userid'));

                if ($mailfname = ConfigHelper::getConfig('phpui.helpdesk_sender_name')) {
                    if ($mailfname == 'queue') {
                        $mailfname = $LMS->GetQueueName($ticket['queue']);
                    }
                    if ($mailfname == 'user') {
                        $mailfname = $user['name'];
                    }
                    $mailfname = '"'.$mailfname.'"';
                }

                $mailfrom = $LMS->DetermineSenderEmail($user['email'], $LMS->GetQueueEmail($ticket['queue']), $ticket['mailfrom']);

                $ticketdata = $LMS->GetTicketContents($id);

                $headers['Date'] = date('r');
                $headers['From'] = $mailfname.' <'.$mailfrom.'>';
                $headers['Reply-To'] = $headers['From'];
                $headers['Message-ID'] = $LMS->GetLastMessageID();

                $info = $LMS->GetCustomer($SESSION->id, true);

                $emails = array_map(function ($contact) {
                        return $contact['fullname'];
                }, $LMS->GetCustomerContacts($SESSION->id, CONTACT_EMAIL));
                $phones = array_map(function ($contact) {
                        return $contact['fullname'];
                }, $LMS->GetCustomerContacts($SESSION->id, CONTACT_LANDLINE | CONTACT_MOBILE));

                if (ConfigHelper::checkConfig('phpui.helpdesk_customerinfo')) {
                    $params = array(
                        'id' => $id,
                        'customerid' => $SESSION->id,
                        'customer' => $info,
                        'emails' => $emails,
                        'phones' => $phones,
                    );
                    $mail_customerinfo = $LMS->ReplaceNotificationCustomerSymbols(ConfigHelper::getConfig('phpui.helpdesk_customerinfo_mail_body'), $params);
                    $sms_customerinfo = $LMS->ReplaceNotificationCustomerSymbols(ConfigHelper::getConfig('phpui.helpdesk_customerinfo_sms_body'), $params);
                }

                $queuedata = $LMS->GetQueue($ticket['queue']);
                if (!empty($queuedata['newticketsubject']) && !empty($queuedata['newticketbody'])
                    && !empty($emails)) {
                    $ticketid = sprintf("%06d", $id);
                    $custmail_subject = $queuedata['newticketsubject'];
                    $custmail_subject = str_replace('%tid', $ticketid, $custmail_subject);
                    $custmail_subject = str_replace('%title', $ticket['subject'], $custmail_subject);
                    $custmail_body = $queuedata['newticketbody'];
                    $custmail_body = str_replace('%tid', $ticketid, $custmail_body);
                    $custmail_body = str_replace('%cid', $SESSION->id, $custmail_body);
                    $custmail_body = str_replace('%pin', $info['pin'], $custmail_body);
                    $custmail_body = str_replace('%customername', $info['customername'], $custmail_body);
                    $custmail_body = str_replace('%title', $ticket['subject'], $custmail_body);
                    $custmail_headers = array(
                        'From' => $headers['From'],
                        'To' => '<' . $info['email'] . '>',
                        'Reply-To' => $headers['From'],
                        'Subject' => $custmail_subject,
                    );
                    $LMS->SendMail(implode(',', $emails), $custmail_headers, $custmail_body, null, null, $LMS->GetRTSmtpOptions());
                }

                $params = array(
                    'id' => $id,
                    'customerid' => $SESSION->id,
                    'status' => $ticketdata['status'],
                    'categories' => $ticketdata['categorynames'],
                    'priority' => $RT_PRIORITIES[$ticketdata['priority']],
                    'subject' => $ticket['subject'],
                    'body' => $ticket['body'],
                );

                // try to use LMS url from userpanel configuration
                $lms_url = ConfigHelper::getConfig('userpanel.lms_url', '');
                if (!empty($lms_url)) {
                    $params['url'] = $lms_url;
                }

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
                    'attachments' => $attachments,
                ));
            }

            header('Location: ?m=helpdesk&op=view&id=' . $id);
            die;
        } else {
            $SMARTY->assign('error', $error);
            $SMARTY->assign('helpdesk', $ticket);
        }
    } elseif ($id && isset($_POST['helpdesk'])
        && ($DB->GetOne('SELECT state FROM rttickets WHERE id = ?', array($id)) != RT_RESOLVED
        || ConfigHelper::getConfig('userpanel.allow_message_add_to_closed_tickets'))
        && $DB->GetOne('SELECT customerid FROM rttickets WHERE id = ?', array($id)) == $SESSION->id) {
        $ticket = $_POST['helpdesk'];

        $ticket['lastmod'] = $DB->GetOne(
            'SELECT MAX(createtime) FROM rtmessages WHERE ticketid = ?',
            array($id)
        );
        $allow_reopen_tickets_newer_than = intval(ConfigHelper::getConfig('userpanel.allow_reopen_tickets_newer_than'));
        if ($allow_reopen_tickets_newer_than && time() - $allow_reopen_tickets_newer_than > $ticket['lastmod']) {
            header('Location: ?m=helpdesk&op=view&id=' . $id);
            die;
        }

        $ticket['body'] = strip_tags($ticket['body']);
        $ticket['subject'] = strip_tags($ticket['subject']);
        $ticket['inreplyto'] = intval($ticket['inreplyto']);
        $ticket['id'] = intval($_GET['id']);

        if ($ticket['subject'] == '') {
            $error['subject'] = trans('Ticket must have its title!');
        }

        if ($ticket['body'] == '') {
            $error['body'] = trans('Ticket must have its body!');
        }

        $queues = str_replace(';', ',', ConfigHelper::getConfig('userpanel.queues'));
        $sources = str_replace(';', ',', ConfigHelper::getConfig('userpanel.visible_ticket_sources'));
        if (!$DB->GetOne(
            'SELECT 1 FROM rttickets WHERE source IN (' . $sources . ')
			AND queueid IN ('. $queues . ') AND id = ? AND customerid = ?',
            array($ticket['id'], $SESSION->id)
        )) {
            $error = true;
        }

        if (!$error) {
            $ticket['customerid'] = $SESSION->id;
            $ticket['queue'] = $LMS->GetQueueByTicketId($ticket['id']);

            // add message
            $ticket['messageid'] = '<msg.' . $ticket['queue']['id'] . '.' . $ticket['id'] . '.' . time()
                . '@rtsystem.' . gethostname() . '>';

            $msgid = $LMS->TicketMessageAdd(array(
                    'ticketid' => $ticket['id'],
                    'subject' => $ticket['subject'],
                    'body' => $ticket['body'],
                    'customerid' => $ticket['customerid'],
                    'inreplyto' => $ticket['inreplyto'],
                    'messageid' => $ticket['messageid'],
                ), $files);

            // re-open ticket
            static $ticket_change_state_map = array(
                RT_NEW => RT_NEW,
                RT_OPEN => RT_OPEN,
                RT_RESOLVED => RT_OPEN,
                RT_DEAD => RT_OPEN,
            );
            $ticket['state'] = $DB->GetOne('SELECT state FROM rttickets WHERE id = ?', array($ticket['id']));
            $DB->Execute(
                'UPDATE rttickets SET state = ? WHERE id = ?',
                array($ticket_change_state_map[$ticket['state']], $ticket['id'])
            );

            $user = $LMS->GetUserInfo(ConfigHelper::getConfig('userpanel.default_userid'));

            if ($mailfname = ConfigHelper::getConfig('phpui.helpdesk_sender_name')) {
                if ($mailfname == 'queue') {
                    $mailfname = $ticket['queue']['name'];
                }
                if ($mailfname == 'user') {
                    $mailfname = $user['name'];
                }
                $mailfname = '"' . $mailfname . '"';
            }

            $ticket['email'] = $LMS->GetCustomerEmail($SESSION->id);
            $ticket['mailfrom'] = $ticket['email'] ? $ticket['email'] : '';

            $mailfrom = $LMS->DetermineSenderEmail($user['email'], $ticket['queue']['email'], $ticket['mailfrom']);

            $ticketdata = $LMS->GetTicketContents($ticket['id']);

            $headers['Date'] = date('r');
            $headers['From'] = $mailfname . ' <' . $mailfrom . '>';
            $headers['Reply-To'] = $headers['From'];
            if ($ticket['references']) {
                $headers['References'] = $ticket['references'];
                $headers['In-Reply-To'] = array_pop(explode(' ', $ticket['references']));
            }
            $headers['Message-ID'] = $ticket['messageid'];

            if (ConfigHelper::checkConfig('phpui.helpdesk_customerinfo')) {
                $info = $LMS->GetCustomer($SESSION->id, true);

                $emails = array_map(function ($contact) {
                        return $contact['fullname'];
                }, $LMS->GetCustomerContacts($SESSION->id, CONTACT_EMAIL));
                $phones = array_map(function ($contact) {
                        return $contact['fullname'];
                }, $LMS->GetCustomerContacts($SESSION->id, CONTACT_LANDLINE | CONTACT_MOBILE));

                $params = array(
                    'id' => $ticket['id'],
                    'customerid' => $SESSION->id,
                    'customer' => $info,
                    'emails' => $emails,
                    'phones' => $phones,
                );
                $mail_customerinfo = $LMS->ReplaceNotificationCustomerSymbols(ConfigHelper::getConfig('phpui.helpdesk_customerinfo_mail_body'), $params);
                $sms_customerinfo = $LMS->ReplaceNotificationCustomerSymbols(ConfigHelper::getConfig('phpui.helpdesk_customerinfo_sms_body'), $params);
            }

            $params = array(
                'id' => $ticket['id'],
                'messageid' => isset($msgid) ? $msgid : null,
                'customerid' => $SESSION->id,
                'status' => $ticketdata['status'],
                'categories' => $ticketdata['categorynames'],
                'priority' => $RT_PRIORITIES[$ticketdata['priority']],
                'subject' => $ticket['subject'],
                'body' => $ticket['body'],
                'attachments' => &$attachments,
            );

            // try to use LMS url from userpanel configuration
            $lms_url = ConfigHelper::getConfig('userpanel.lms_url', '');
            if (!empty($lms_url)) {
                $params['url'] = $lms_url;
            }

            $headers['Subject'] = $LMS->ReplaceNotificationSymbols(ConfigHelper::getConfig('phpui.helpdesk_notification_mail_subject'), $params);
            $params['customerinfo'] = isset($mail_customerinfo) ? $mail_customerinfo : null;
            $body = $LMS->ReplaceNotificationSymbols(ConfigHelper::getConfig('phpui.helpdesk_notification_mail_body'), $params);
            $params['customerinfo'] = isset($sms_customerinfo) ? $sms_customerinfo : null;
            $sms_body = $LMS->ReplaceNotificationSymbols(ConfigHelper::getConfig('phpui.helpdesk_notification_sms_body'), $params);

            $LMS->NotifyUsers(array(
                'queue' => $ticket['queue']['id'],
                'mail_headers' => $headers,
                'mail_body' => $body,
                'sms_body' => $sms_body,
                'attachments' => &$attachments,
            ));

            header('Location: ?m=helpdesk&op=view&id='.$ticket['id']);
            die;
        } else {
            $SMARTY->assign('error', $error);
            $helpdesk = $ticket;
            $SMARTY->assign('helpdesk', $helpdesk);
            $_GET['op'] = 'message';
        }
    } else {
        $SMARTY->assign('helpdesk', array());
    }

    $unit_multipliers = array(
        'K' => 1024,
        'M' => 1024 * 1024,
        'G' => 1024 * 1024 * 1024,
        'T' => 1024 * 1024 * 1024 * 1024,
    );
    foreach (array('post_max_size', 'upload_max_filesize') as $var) {
        preg_match('/^(?<number>[0-9]+)(?<unit>[kKmMgGtT]?)$/', ini_get($var), $m);
        $unit_multiplier = isset($m['unit']) ? $unit_multipliers[strtoupper($m['unit'])] : 1;
        if ($var == 'post_max_size') {
            $unit_multiplier *= 1/1.33;
        }
        if (empty($m['number'])) {
            $val['bytes'] = 0;
            $val['text'] = trans('(unlimited)');
        } else {
            $val['bytes'] = round($m['number'] * $unit_multiplier);
            $res = setunits($val['bytes']);
            $val['text'] = round($res[0]) . ' ' . $res[1];
        }
        $SMARTY->assign($var, $val);
    }

    if (isset($_GET['op']) && $_GET['op'] == 'view') {
        if ($LMS->TicketExists($_GET['id'])) {
            $ticket = $LMS->GetTicketContents($_GET['id']);

            $ticket['id'] = $_GET['id'];

            $queues = explode(';', ConfigHelper::getConfig('userpanel.queues'));
            $sources = explode(';', ConfigHelper::getConfig('userpanel.visible_ticket_sources'));
            if ($ticket['customerid'] == $SESSION->id && in_array($ticket['queueid'], $queues)
                && in_array($ticket['source'], $sources)) {
                if (count($queues)==1) {
                    $SMARTY->assign('title', trans(
                        'Request No. $a',
                        sprintf('%06d', $ticket['ticketid'])
                    ));
                } else {
                    $SMARTY->assign('title', trans(
                        'Request No. $a / Queue: $b',
                        sprintf('%06d', $ticket['ticketid']),
                        $ticket['queuename']
                    ));
                }

                $SMARTY->assign('ticket', $ticket);
                $SMARTY->display('module:helpdeskview.html');
                die;
            }
        }
    } elseif (isset($_GET['op']) && $_GET['op'] == 'message') {
        if (intval($_GET['id'])) {
            $ticket = $LMS->GetTicketContents($_GET['id']);
        }

        $ticket['id'] = $_GET['id'];

        $queues = explode(';', ConfigHelper::getConfig('userpanel.queues'));
        $sources = explode(';', ConfigHelper::getConfig('userpanel.visible_ticket_sources'));
        if ($ticket['customerid'] == $SESSION->id && in_array($ticket['queueid'], $queues)
            && in_array($ticket['source'], $sources)) {
            if (isset($_GET['msgid']) && intval($_GET['msgid'])) {
                $reply = $LMS->GetMessage($_GET['msgid']);

                $helpdesk['subject'] = $reply['subject'];
                $helpdesk['subject'] = preg_replace('/^Re:\s*/', '', $helpdesk['subject']);
                $helpdesk['subject'] = 'Re: '. $helpdesk['subject'];

                $helpdesk['inreplyto'] = $reply['id'];
                $helpdesk['references'] = implode(' ', $reply['references']);
            } else {
                $reply = $LMS->GetFirstMessage($_GET['id']);
                $helpdesk['inreplyto'] = $reply['id'];
                $helpdesk['references'] = implode(' ', $reply['references']);
            }
            $SMARTY->assign('helpdesk', $helpdesk);

            if (count($queues)==1) {
                            $SMARTY->assign('title', trans(
                                'Request No. $a',
                                sprintf('%06d', $ticket['ticketid'])
                            ));
            } else {
                    $SMARTY->assign('title', trans(
                        'Request No. $a / Queue: $b',
                        sprintf('%06d', $ticket['ticketid']),
                        $ticket['queuename']
                    ));
            }

            if ($ticket['customerid'] == $SESSION->id) {
                $SMARTY->assign('ticket', $ticket);
                $SMARTY->display('module:helpdeskreply.html');
                die;
            }
        }
    }

    $helpdesklist = $LMS->GetCustomerTickets($SESSION->id);

    $queues = ConfigHelper::getConfig('userpanel.queues');
    if (!empty($queues)) {
        $queues = $LMS->DB->GetAll('SELECT id, name FROM rtqueues WHERE id IN ('
            . str_replace(';', ',', $queues) . ')');
    } else {
        $queues = array();
    }
    $SMARTY->assign('queues', $queues);
    $SMARTY->assign('helpdesklist', $helpdesklist);
    $SMARTY->display('module:helpdesk.html');
}

function module_attachment()
{
    global $DB, $SESSION;
    $attach = $DB->GetRow(
        'SELECT ticketid, filename, contenttype FROM rtattachments a
		JOIN rtmessages m ON m.id = a.messageid
		JOIN rttickets t ON t.id = m.ticketid
		WHERE t.customerid = ? AND a.messageid = ? AND filename = ?',
        array($SESSION->id, $_GET['msgid'], $_GET['file'])
    );
    if (empty($attach)) {
        die;
    }
    $file = ConfigHelper::getConfig('rt.mail_dir') . sprintf("/%06d/%06d/%s", $attach['ticketid'], $_GET['msgid'], $_GET['file']);
    if (file_exists($file)) {
        $size = @filesize($file);
        header('Content-Length: ' . $size . ' bytes');
        header('Content-Type: '. $attach['contenttype']);
        header('Cache-Control: private');
        header('Content-Disposition: attachment; filename=' . $attach['filename']);
        @readfile($file);
    }
    die;
}
