<?php

/*
 * LMS version 1.11-git
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

if(isset($_POST['message']))
{
	$message = $_POST['message'];

	if($message['subject'] == '')
		$error['subject'] = trans('Message subject not specified!');
	else if (strlen($message['subject']) > 255)
		$error['subject'] = trans('Subject must contains less than 255 characters!');

	if($message['body'] == '')
		$error['body'] = trans('Message body not specified!');

	if($message['destination']!='' && !check_email($message['destination']))
		$error['destination'] = trans('Incorrect email!');

	if($message['destination']!='' && $message['sender']=='customer')
		$error['destination'] = trans('Customer cannot send message!');

    if (ConfigHelper::checkConfig('phpui.helpdesk_block_ticket_close_with_open_events')) {
    	$ticketcontent = $LMS->GetTicketContents($message['ticketid']);
	$oec = $ticketcontent['openeventcount'];
        if ($message['state'] == RT_RESOLVED && !empty($oec))
            $error['state'] = trans('Ticket have open assigned events!');
    }

	$result = handle_file_uploads('files', $error);
	extract($result);
	$SMARTY->assign('fileupload', $fileupload);

	if(!$error)
	{
		$queue = $LMS->GetQueueByTicketId($message['ticketid']);
		$user = $LMS->GetUserInfo(Auth::GetCurrentUser());

		$message['queue'] = $queue;

		$message['messageid'] = '<msg.' . $queue['id'] . '.' . $message['ticketid'] . '.' . time()
			. '@rtsystem.' . gethostname() . '>';

		if ($message['sender'] == 'user') {
			$message['userid'] = Auth::GetCurrentUser();
			$message['customerid'] = null;
		} else {
			$message['userid'] = null;
			if (!$message['customerid']) {
				$req = $DB->GetOne('SELECT requestor FROM rttickets WHERE id = ?', array($message['ticketid']));
				$message['mailfrom'] = preg_replace('/^.* <(.+@.+)>/','\1', $req);
				if(!check_email($message['mailfrom']))
					$message['mailfrom'] = '';
			}
		}

		$mailfname = '';

		$helpdesk_sender_name = ConfigHelper::getConfig('phpui.helpdesk_sender_name');
		if (!empty($helpdesk_sender_name) && ($mailfname = $helpdesk_sender_name))
		{
			if($mailfname == 'queue') $mailfname = $queue['name'];
			if($mailfname == 'user') $mailfname = $user['name'];
			$mailfname = '"'.$mailfname.'"';
		}

		if (!ConfigHelper::checkConfig('phpui.helpdesk_backend_mode') || $message['destination'] == '') {
			$headers = array();

			if ($message['references']) {
				$headers['References'] = $message['references'];
				$headers['In-Reply-To'] = array_pop(explode(' ', $message['references']));
			}
			$headers['Message-ID'] = $message['messageid'];

			if($message['destination'] && $message['userid']
				&& ($user['email'] || $queue['email'])
				&& $message['destination'] != $queue['email'])
			{
				$recipients = $message['destination'];
				$message['mailfrom'] = $user['email'] ? $user['email'] : $queue['email'];

				$headers['Date'] = date('r');
				$headers['From'] = $mailfname.' <'.$message['mailfrom'].'>';
				$headers['To'] = '<'.$message['destination'].'>';
				$headers['Subject'] = $message['subject'];
				$headers['Reply-To'] = $headers['From'];

				$body = $message['body'];

				$attachments = NULL;
				if (!empty($files))
					foreach ($files as $file)
						$attachments[] = array(
							'content_type' => $file['type'],
							'filename' => $file['name'],
							'data' => file_get_contents($tmppath . DIRECTORY_SEPARATOR . $file['name']),
						);

				$LMS->SendMail($recipients, $headers, $body, $attachments);
			}
			else
			{
				if($message['customerid'] || $message['userid'])
					$message['mailfrom'] = '';
				$message['headers'] = '';
				$message['replyto'] = '';
			}

			if (!empty($files)) {
				foreach ($files as &$file)
					$file['name'] = $tmppath . DIRECTORY_SEPARATOR . $file['name'];
				unset($file);
			}
			$message['headers'] = $headers;
			$msgid = $LMS->TicketMessageAdd($message, $files);
		}
		else //sending to backend
		{
			($message['destination']!='' ? $addmsg = 1 : $addmsg = 0);

			if($message['destination']=='')
				$message['destination'] = $queue['email'];
			$recipients = $message['destination'];

			if($message['userid'] && $addmsg)
				$message['mailfrom'] = $queue['email'] ? $queue['email'] : $user['email'];
			if($message['userid'] && !$addmsg)
				$message['mailfrom'] = $user['email'] ? $user['email'] : $queue['email'];

			if($message['customerid']) {
				$message['mailfrom'] = $LMS->GetCustomerEmail($message['customerid']);
				if (!empty($message['mailfrom']))
					$message['mailfrom'] = $message['mailfrom'][0];
			}

			$headers['Date'] = date('r');
			$headers['From'] = $mailfname.' <'.$message['mailfrom'].'>';
			$headers['To'] = '<'.$message['destination'].'>';
			$headers['Subject'] = $message['subject'];
			if ($message['references']) {
				$headers['References'] = $message['references'];
				$headers['In-Reply-To'] = array_pop(explode(' ', $message['references']));
			}
			$headers['Message-ID'] = $message['messageid'];
			$headers['Reply-To'] = $headers['From'];

			// message to customer is written to database
			if ($message['userid'] && $addmsg) {
				foreach ($files as &$file)
					$file['name'] = $tmppath . DIRECTORY_SEPARATOR . $file['name'];
				unset($file);
				$message['headers'] = $headers;
				$msgid = $LMS->TicketMessageAdd($message, $files);
			}

			$body = $message['body'];
			if ($message['destination'] == $queue['email'] || $message['destination'] == $user['email'])
				$body .= "\n\nhttp".($_SERVER['HTTPS'] == 'on' ? 's' : '').'://'
					.$_SERVER['HTTP_HOST'].substr($_SERVER['REQUEST_URI'], 0, strrpos($_SERVER['REQUEST_URI'], '/') + 1)
					. '?m=rtticketview&id=' . $message['ticketid'] . (isset($msgid) ? '#rtmessage-' . $msgid : '');
			$attachments = NULL;
			if (!empty($files))
				foreach ($files as $file)
					$attachments[] = array(
						'content_type' => $file['type'],
						'filename' => $file['name'],
						'data' => file_get_contents($tmppath . DIRECTORY_SEPARATOR . $file['name']),
					);
			$LMS->SendMail($recipients, $headers, $body, $attachments);
		}

		// deletes uploaded files
		if (!empty($files))
			rrmdir($tmppath);

		// setting status and the ticket owner
		if (isset($message['resolve']))
			$message['state'] = RT_RESOLVED;

		if (!$DB->GetOne('SELECT owner FROM rttickets WHERE id = ?', array($message['ticketid'])))
			$message['owner'] = Auth::GetCurrentUser();

		$props = array(
			'queueid' => $message['queueid'],
			'owner' => empty($message['owner']) ? null : $message['owner'],
			'cause' => $message['cause'],
			'state' => $message['state'],
			'source' => $message['source'],
			'priority' => $message['priority'],
			'verifierid' => empty($message['verifierid']) ? null : $message['verifierid'],
			'deadline' => $message['deadline'],
		);
		$LMS->TicketChange($message['ticketid'], $props);

		$service = ConfigHelper::getConfig('sms.service');

		// customer notification via sms when we reply to ticket message created from customer sms
		if (isset($message['smsnotify']) && !empty($message['phonefrom']) && !empty($service)) {
			$sms_body = preg_replace('/\r?\n/', ' ', $message['body']);
			$LMS->SendSMS($message['phonefrom'], $sms_body);
		}

		// Users notification
		if (isset($message['notify']) && ($user['email'] || $queue['email']))
		{
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

			$mailfrom = $user['email'] ? $user['email'] : $queue['email'];

			$ticketdata = $LMS->GetTicketContents($message['ticketid']);

			$headers['From'] = $mailfname.' <'.$mailfrom.'>';
			$headers['Reply-To'] = $headers['From'];

			if ($ticketdata['customerid']) {
				$info = $LMS->GetCustomer($ticketdata['customerid'], true);

				$emails = array_map(function($contact) {
						return $contact['fullname'];
					}, $LMS->GetCustomerContacts($ticketdata['customerid'], CONTACT_EMAIL));
				$phones = array_map(function($contact) {
						return $contact['fullname'];
					}, $LMS->GetCustomerContacts($ticketdata['customerid'], CONTACT_LANDLINE | CONTACT_MOBILE));

				if (ConfigHelper::checkConfig('phpui.helpdesk_customerinfo')) {
					$params = array(
						'id' => $message['ticketid'],
						'customerid' => $ticketdata['customerid'],
						'customer' => $info,
						'emails' => $emails,
						'phones' => $phones,
					);
					$mail_customerinfo = $LMS->ReplaceNotificationCustomerSymbols(ConfigHelper::getConfig('phpui.helpdesk_customerinfo_mail_body'), $params);
					$sms_customerinfo = $LMS->ReplaceNotificationCustomerSymbols(ConfigHelper::getConfig('phpui.helpdesk_customerinfo_sms_body'), $params);
				}

				$queuedata = $LMS->GetQueueByTicketId($message['ticketid']);
				if (isset($message['customernotify']) && !empty($queuedata['newmessagesubject']) && !empty($queuedata['newmessagebody'])
					&& !empty($emails)) {
					$title = $DB->GetOne('SELECT subject FROM rtmessages WHERE ticketid = ?
						ORDER BY id LIMIT 1', array($message['ticketid']));
					$custmail_subject = $queuedata['newmessagesubject'];
					$custmail_subject = str_replace('%tid', $id, $custmail_subject);
					$custmail_subject = str_replace('%title', $title, $custmail_subject);
					$custmail_body = $queuedata['newmessagebody'];
					$custmail_body = str_replace('%tid', $id, $custmail_body);
					$custmail_body = str_replace('%cid', $ticketdata['customerid'], $custmail_body);
					$custmail_body = str_replace('%pin', $info['pin'], $custmail_body);
					$custmail_body = str_replace('%customername', $info['customername'], $custmail_body);
					$custmail_body = str_replace('%title', $title, $custmail_body);
					$custmail_headers = array(
						'From' => $headers['From'],
						'Reply-To' => $headers['From'],
						'Subject' => $custmail_subject,
					);
					foreach ($emails as $email) {
						$custmail_headers['To'] = '<' . $email . '>';
						$LMS->SendMail($email, $custmail_headers, $custmail_body);
					}
				}
			} elseif (ConfigHelper::checkConfig('phpui.helpdesk_customerinfo')) {
				$mail_customerinfo = "\n\n-- \n" . trans('Customer:') . ' ' . $ticketdata['requestor'];
				$sms_customerinfo = "\n" . trans('Customer:') . ' ' . $ticketdata['requestor'];
			}

			$params = array(
				'id' => $message['ticketid'],
				'queue' => $queue['name'],
				'messageid' => isset($msgid) ? $msgid : null,
				'customerid' => empty($message['customerid']) ? $ticketdata['customerid'] : $message['customerid'],
				'status' => $ticketdata['status'],
				'categories' => $ticketdata['categorynames'],
				'priority' => $RT_PRIORITIES[$ticketdata['priority']],
				'deadline' => $ticketdata['deadline'],
				'subject' => $message['subject'],
				'body' => $message['body'],
			);
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
			));
		}

		$backto = $SESSION->get('backto');
		if (strpos($backto, 'rtqueueview') === false && isset($msgid))
			$SESSION->redirect('?m=rtticketview&id=' . $message['ticketid'] . (isset($msgid) ? '#rtmessage-' . $msgid : ''));
		else
			$SESSION->redirect('?' . $backto);
	}
}
else
{
	if ($_GET['ticketid']) {
		$queue = $LMS->GetQueueByTicketId($_GET['ticketid']);
		$message = $LMS->GetTicketContents($_GET['ticketid']);
		if ($queue['newmessagesubject'] && $queue['newmessagebody'])
			$message['customernotify'] = 1;
		if (ConfigHelper::checkConfig('phpui.helpdesk_notify'))
			$message['notify'] = TRUE;
	}

	$user = $LMS->GetUserInfo(Auth::GetCurrentUser());

	$message['ticketid'] = $_GET['ticketid'];
	$message['customerid'] = $DB->GetOne('SELECT customerid FROM rttickets WHERE id = ?', array($message['ticketid']));
	
	if(isset($_GET['id']))
	{
		$reply = $LMS->GetMessage($_GET['id']);

		if($reply['replyto'])
			$message['destination'] = preg_replace('/^.* <(.+@.+)>/','\1',$reply['replyto']);
		else 
			$message['destination'] = preg_replace('/^.* <(.+@.+)>/','\1',$reply['mailfrom']);

		if ($reply['phonefrom']) {
			$message['phonefrom'] = $reply['phonefrom'];
			if (ConfigHelper::checkConfig('phpui.helpdesk_customer_notify'))
				$message['smsnotify'] = true;
		}

		if (!$message['destination'] && !$reply['userid']) {
			$message['destination'] = $LMS->GetCustomerEmail($message['customerid']);
			if (!empty($message['destination']))
				$message['destination'] = implode(',', $message['destination']);
		}

		$message['subject'] = 'Re: '.$reply['subject'];
		$message['inreplyto'] = $reply['id'];
		$message['references'] = implode(' ', $reply['references']);

		if (ConfigHelper::checkConfig('phpui.helpdesk_reply_body')) {
			$body = explode("\n",textwrap(strip_tags($reply['body']),74));
			foreach($body as $line)
				$message['body'] .= '> '.$line."\n";
		}

	} else {
		$reply = $LMS->GetFirstMessage($_GET['ticketid']);
		$message['inreplyto'] = $reply['id'];
		$message['references'] = implode(' ', $reply['references']);
	}
}

$layout['pagetitle'] = trans('New Message');

$SMARTY->assign('error', $error);

$ticket = $LMS->GetTicketContents($message['ticketid']);
$SMARTY->assign('ticket', $ticket);
if (!isset($_POST['message'])) {
	$message['source'] = $ticket['source'];
	$message['priority'] = $ticket['priority'];
	$message['verifierid'] = $ticket['verifierid'];
	$message['deadline'] = $ticket['deadline'];
	if ($message['state'] == RT_NEW)
		$message['state'] = RT_OPEN;
}

$SMARTY->assign('message', $message);
$SMARTY->assign('userlist', $LMS->GetUserNames());
$SMARTY->assign('queuelist', $LMS->GetQueueListByUser(Auth::GetCurrentUser(), false));
$SMARTY->display('rt/rtmessageadd.html');

?>
