<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2019 LMS Developers
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

if (isset($_GET['ticketid'])) {
	$note['ticketid'] = intval($_GET['ticketid']);

	if (!($LMS->CheckTicketAccess($note['ticketid']) & RT_RIGHT_WRITE))
		access_denied();

	$LMS->MarkTicketAsRead($note['ticketid']);

	$note = $DB->GetRow('SELECT id AS ticketid, state, cause, queueid, owner FROM rttickets WHERE id = ?', array($note['ticketid']));
	$reply = $LMS->GetFirstMessage($note['ticketid']);
	$note['inreplyto'] = $reply['id'];
	$note['references'] = implode(' ', $reply['references']);

	if (ConfigHelper::checkConfig('phpui.helpdesk_notify'))
		$note['notify'] = TRUE;

	$ticket = $LMS->GetTicketContents($note['ticketid']);
} elseif (isset($_POST['note'])) {
	$note = $_POST['note'];

	if (!($LMS->CheckTicketAccess($note['ticketid']) & RT_RIGHT_WRITE))
		access_denied();

	$ticket = $LMS->GetTicketContents($note['ticketid']);

	if (ConfigHelper::checkConfig('phpui.helpdesk_block_ticket_close_with_open_events')
		&& $note['state'] == RT_RESOLVED && !empty($ticket['openeventcount']))
		$error['state'] = trans('Ticket have open assigned events!');

	if ($note['body'] == '')
		$error['body'] = trans('Note body not specified!');

	if (!isset($note['ticketid']) || !intval($note['ticketid']))
		$SESSION->redirect('?m=rtqueuelist');

	if (ConfigHelper::checkValue(ConfigHelper::getConfig('phpui.helpdesk_check_owner_verifier_conflict', true))
		&& !empty($note['verifierid']) && $note['verifierid'] == $note['owner']) {
		$error['verifierid'] = trans('Ticket owner could not be the same as verifier!');
		$error['owner'] = trans('Ticket verifier could not be the same as owner!');
	}

	$deadline = datetime_to_timestamp($note['deadline']);
	if ($deadline != $ticket['deadline']) {
		if (!ConfigHelper::checkConfig('phpui.helpdesk_allow_all_users_modify_deadline')
			&& !empty($note['verifierid']) && $note['verifierid'] != Auth::GetCurrentUser()) {
			$error['deadline'] = trans('If verifier is set then he\'s the only person who can change deadline!');
			$note['deadline'] = $ticket['deadline'];
		}
		if ($deadline && $deadline < time())
			$error['deadline'] = trans('Ticket deadline could not be set in past!');
	}

	$LMS->MarkTicketAsRead($note['ticketid']);

	$result = handle_file_uploads('files', $error);
	extract($result);
	$SMARTY->assign('fileupload', $fileupload);

	if(!$error)
	{
		$messageid = '<msg.' . $ticket['queueid'] . '.' . $note['ticketid'] . '.'  . time() . '@rtsystem.' . gethostname() . '>';

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
		if (!empty($files) && !empty($tmppath))
			rrmdir($tmppath);

		// setting status and the ticket owner
		$props = array(
			'queueid' => $note['queueid'],
			'owner' => empty($note['owner']) ? null : $note['owner'],
			'cause' => $note['cause'],
			'state' => $note['state'],
			'source' => $note['source'],
			'priority' => $note['priority'],
			'verifierid' => empty($note['verifierid']) ? null : $note['verifierid'],
			'deadline' => empty($note['deadline']) ? null : $deadline,
		);
		$LMS->TicketChange($note['ticketid'], $props);

		if(isset($note['notify']))
		{
			$user = $LMS->GetUserInfo(Auth::GetCurrentUser());
			$queue = $LMS->GetQueueByTicketId($note['ticketid']);
			$mailfname = '';

			$helpdesk_sender_name = ConfigHelper::getConfig('phpui.helpdesk_sender_name');
			if(!empty($helpdesk_sender_name))
			{
				$mailfname = $helpdesk_sender_name;

				if($mailfname == 'queue')
					$mailfname = $queue['name'];
				elseif($mailfname == 'user')
					$mailfname = $user['name'];

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

			if (ConfigHelper::checkConfig('phpui.helpdesk_customerinfo')) {
				if ($ticket['customerid']) {
					$info = $LMS->GetCustomer($ticket['customerid'], true);

					$emails = array_map(function($contact) {
							return $contact['fullname'];
						}, $LMS->GetCustomerContacts($ticket['customerid'], CONTACT_EMAIL));
					$phones = array_map(function($contact) {
							return $contact['fullname'];
						}, $LMS->GetCustomerContacts($ticket['customerid'], CONTACT_LANDLINE | CONTACT_MOBILE));

					$params = array(
						'id' => $note['ticketid'],
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

			$params = array(
				'id' => $note['ticketid'],
				'queue' => $queue['name'],
				'messageid' => isset($msgid) ? $msgid : null,
				'customerid' => $ticket['customerid'],
				'status' => $ticket['status'],
				'categories' => $ticket['categorynames'],
				'priority' => $RT_PRIORITIES[$ticket['priority']],
				'deadline' => $ticket['deadline'],
				'subject' => $ticket['subject'],
				'body' => $note['body'],
				'attachments' => &$attachments,
			);

			$headers['X-Priority'] = $RT_MAIL_PRIORITIES[$ticket['priority']];

			if (ConfigHelper::checkConfig('rt.note_send_re_in_subject'))
				$params['subject'] = 'Re: '.$ticket['subject'];

			$headers['Subject'] = $LMS->ReplaceNotificationSymbols(ConfigHelper::getConfig('phpui.helpdesk_notification_mail_subject'), $params);
			$params['customerinfo'] = isset($mail_customerinfo) ? $mail_customerinfo : null;
			$body = $LMS->ReplaceNotificationSymbols(ConfigHelper::getConfig('phpui.helpdesk_notification_mail_body'), $params);
			$params['customerinfo'] = isset($sms_customerinfo) ? $sms_customerinfo : null;
			$sms_body = $LMS->ReplaceNotificationSymbols(ConfigHelper::getConfig('phpui.helpdesk_notification_sms_body'), $params);

			$LMS->NotifyUsers(array(
				'queue' => $queue['id'],
				'mail_headers' => $headers,
				'mail_body' => $body,
				'sms_body' => $sms_body,
				'attachments' => &$attachments,
			));
		}

		$backto = $SESSION->get('backto');
		if (strpos($backto, 'rtqueueview') === false && isset($msgid)) {
			$SESSION->redirect('?m=rtticketview&id=' . $note['ticketid'] . (isset($msgid) ? '#rtmessage-' . $msgid : ''));
		} else
			$SESSION->redirect('?' . $backto);
	}
} else
	$SESSION->redirect('?m=rtqueuelist');

$layout['pagetitle'] = trans('New Note');

$SMARTY->assign('ticket', $ticket);
if (!isset($_POST['note'])) {
	$note['source'] = $ticket['source'];
	$note['priority'] = $ticket['priority'];
	$note['verifierid'] = $ticket['verifierid'];
	$note['deadline'] = $ticket['deadline'];
	if ($note['state'] == RT_NEW)
		$note['state'] = RT_OPEN;
}

$SMARTY->assign('note', $note);
$SMARTY->assign('userlist', $LMS->GetUserNames());
$SMARTY->assign('queuelist', $LMS->GetQueueList(array('stats' => false)));
$SMARTY->assign('error', $error);
$SMARTY->display('rt/rtnoteadd.html');

?>
