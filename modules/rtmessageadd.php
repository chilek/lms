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
			$message['customerid'] = 0;
		} else {
			$message['userid'] = 0;
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

			foreach ($files as &$file)
				$file['name'] = $tmppath . DIRECTORY_SEPARATOR . $file['name'];
			unset($file);
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

		// setting status and the ticket owner
		if (isset($message['state']))
			$message['state'] = RT_RESOLVED;
		else if (!$DB->GetOne('SELECT state FROM rttickets WHERE id = ?', array($message['ticketid'])))
			$message['state'] = RT_OPEN;
		
		if (!$DB->GetOne('SELECT owner FROM rttickets WHERE id = ?', array($message['ticketid'])))
			$message['owner'] = Auth::GetCurrentUser();

		$props = array(
			'queueid' => $message['queueid'],
			'owner' => $message['owner'],
			'cause' => $message['cause'],
			'state' => $message['state']
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
			foreach ($ticketdata['categories'] as $tcat)
				$tcatname = $tcatname . $tcat['name'] .' ; ';

			$params = array(
				'id' => $message['ticketid'],
				'customerid' => $message['customerid'],
				'status' => $ticketdata['status'],
				'categories' => $tcatname,
				'subject' => $message['subject'],
				'body' => $message['body'],
			);

			$headers['From'] = $mailfname.' <'.$mailfrom.'>';
			$headers['Subject'] = $LMS->ReplaceNotificationSymbols(ConfigHelper::getConfig('phpui.helpdesk_notification_mail_subject'), $params);
			$headers['Reply-To'] = $headers['From'];

			$body = $LMS->ReplaceNotificationSymbols(ConfigHelper::getConfig('phpui.helpdesk_notification_mail_body'), $params);
			$sms_body = $LMS->ReplaceNotificationSymbols(ConfigHelper::getConfig('phpui.helpdesk_notification_sms_body'), $params);

			if ($cid = $DB->GetOne('SELECT customerid FROM rttickets WHERE id = ?', array($message['ticketid']))) {
				$info = $DB->GetRow('SELECT id, pin, '.$DB->Concat('UPPER(lastname)',"' '",'name').' AS customername,
						address, zip, city FROM customeraddressview WHERE id = ?', array($cid));
				$info['contacts'] = $DB->GetAll('SELECT contact, name, type FROM customercontacts
					WHERE customerid = ?', array($cid));

				$emails = array();
				$phones = array();
				if (!empty($info['contacts']))
					foreach ($info['contacts'] as $contact) {
						$target = $contact['contact'] . (strlen($contact['name']) ? ' (' . $contact['name'] . ')' : '');
						if ($contact['type'] & CONTACT_EMAIL)
							$emails[] = $target;
						elseif ($contact['type'] & (CONTACT_LANDLINE | CONTACT_MOBILE))
							$phones[] = $target;
					}

				if (ConfigHelper::checkConfig('phpui.helpdesk_customerinfo')) {
					$locations = $LMS->getCustomerAddresses($cid);
					$address_id = $DB->GetOne('SELECT address_id FROM rttickets WHERE id = ?', array($message['ticketid']));

					$helpdesk_customerinfo_mail_body = ConfigHelper::getConfig('phpui.helpdesk_customerinfo_mail_body');
					$helpdesk_customerinfo_mail_body = str_replace('%custname', $info['customername'], $helpdesk_customerinfo_mail_body);
					$helpdesk_customerinfo_mail_body = str_replace('%cid', sprintf("%04d",$cid), $helpdesk_customerinfo_mail_body);
					$helpdesk_customerinfo_mail_body = str_replace('%address', (empty($address_id) ? $info['address'] . ', ' . $info['zip'] . ' ' . $info['city']
							: $locations[$address_id]['location']), $helpdesk_customerinfo_mail_body);
					if (!empty($phones))
					$helpdesk_customerinfo_mail_body = str_replace('%phone', implode(', ', $phones), $helpdesk_customerinfo_mail_body);
					if (!empty($emails))
						$helpdesk_customerinfo_mail_body = str_replace('%email', implode(', ', $emails), $helpdesk_customerinfo_mail_body);

					$body .= "\n\n-- \n";
					$body .= $helpdesk_customerinfo_mail_body;

					$helpdesk_customerinfo_sms_body = ConfigHelper::getConfig('phpui.helpdesk_customerinfo_sms_body');
					$helpdesk_customerinfo_sms_body = str_replace('%custname', $info['customername'], $helpdesk_customerinfo_sms_body);
					$helpdesk_customerinfo_sms_body = str_replace('%cid', sprintf("%04d",$cid), $helpdesk_customerinfo_sms_body);
					$helpdesk_customerinfo_sms_body = str_replace('%address', (empty($address_id) ? $info['address'] . ', ' . $info['zip'] . ' ' . $info['city']
							: $locations[$address_id]['location']), $helpdesk_customerinfo_sms_body);
					if (!empty($phones))
						$helpdesk_customerinfo_sms_body = str_replace('%phone', preg_replace('/([0-9])[\s-]+([0-9])/', '\1\2', implode(',', $phones)), $helpdesk_customerinfo_sms_body);

					$sms_body .= "\n";
					$sms_body .= $helpdesk_customerinfo_sms_body;
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
					$custmail_body = str_replace('%cid', $cid, $custmail_body);
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
				} elseif (ConfigHelper::checkConfig('phpui.helpdesk_customerinfo')
					&& $requestor = $DB->GetOne('SELECT requestor FROM rttickets WHERE id = ?', array($message['ticketid']))) {
					$body .= "\n\n-- \n";
					$body .= trans('Customer:') . ' ' . $requestor;

					$sms_body .= "\n";
					$sms_body .= trans('Customer:') . ' ' . $requestor;
				}
			}

			$notify_author = ConfigHelper::checkConfig('phpui.helpdesk_author_notify');
			$args = array(
				'queue' => $queue['id'],
				'user' => Auth::GetCurrentUser(),
			);
			if ($notify_author)
				unset($args['user']);

			// send email
			$args['type'] = MSG_MAIL;
			if ($recipients = $DB->GetCol('SELECT DISTINCT email
					FROM users, rtrights
					WHERE users.id=userid AND queueid = ? AND email != \'\'
						AND (rtrights.rights & 8) = 8 AND deleted = 0'
						. ($notify_author ? '' : ' AND users.id <> ?')
						. ' AND (ntype & ?) > 0',
					array_values($args)))
			{
				foreach($recipients as $email) {
					$headers['To'] = '<'.$email.'>';

					$LMS->SendMail($email, $headers, $body);
				}
			}

			// send sms
			$args['type'] = MSG_SMS;
			if (!empty($service) && ($recipients = $DB->GetCol('SELECT DISTINCT phone
				FROM users, rtrights
					WHERE users.id=userid AND queueid = ? AND phone != \'\'
						AND (rtrights.rights & 8) = 8 AND deleted = 0'
						. ($notify_author ? '' : ' AND users.id <> ?')
						. ' AND (ntype & ?) > 0',
					array_values($args))))
			{
				foreach($recipients as $phone) {
					$LMS->SendSMS($phone, $sms_body);
				}
			}
		}

		$SESSION->redirect('?m=rtticketview&id=' . $message['ticketid'] . (isset($msgid) ? '#rtmessage-' . $msgid : ''));
	}
}
else
{
	if ($_GET['ticketid']) {
		$queue = $LMS->GetQueueByTicketId($_GET['ticketid']);
		$message = $DB->GetRow('SELECT id AS ticketid, state, cause, queueid, owner FROM rttickets WHERE id = ?', array($_GET['ticketid']));
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

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$SMARTY->assign('message', $message);
$SMARTY->assign('error', $error);
$SMARTY->assign('ticket', $LMS->GetTicketContents($message['ticketid']));
$SMARTY->assign('userlist', $LMS->GetUserNames());
$SMARTY->assign('queuelist', $LMS->GetQueueList(false));
$SMARTY->display('rt/rtmessageadd.html');

?>
