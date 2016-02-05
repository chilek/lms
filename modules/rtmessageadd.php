<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2013 LMS Developers
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

function MessageAdd($msg, $headers, $files = NULL) {
	global $DB, $LMS;
	$time = time();

	$head = '';
	if($headers)
		foreach ($headers as $idx => $header)
			$head .= $idx . ": " . $header . "\n";

	$DB->Execute('INSERT INTO rtmessages (ticketid, createtime, subject, body, userid, customerid, mailfrom, inreplyto, messageid, replyto, headers)
			VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
			array(
				$msg['ticketid'],
				$time,
				$msg['subject'],
				preg_replace("/\r/", "", $msg['body']),
				$msg['userid'],
				$msg['customerid'],
				$msg['mailfrom'],
				$msg['inreplyto'],
				$msg['messageid'],
				(isset($msg['replyto']) ? $msg['replyto'] : $headers['Reply-To']),
				$head));

	$mail_dir = ConfigHelper::getConfig('rt.mail_dir');
	if (!empty($files) && !empty($mail_dir)) {
		$id = $DB->GetLastInsertId('rtmessages');
		$dir = $mail_dir . sprintf('/%06d/%06d', $msg['ticketid'], $id);
		@mkdir($mail_dir . sprintf('/%06d', $msg['ticketid']), 0700);
		@mkdir($dir, 0700);
		foreach ($files as $file) {
			$newfile = $dir . '/' . $file['name'];
			if(@rename($file['tmp_name'], $newfile))
				$DB->Execute('INSERT INTO rtattachments (messageid, filename, contenttype) 
						VALUES (?,?,?)', array($id, $file['name'], $file['type']));
		}
	}
}

if(isset($_POST['message']))
{
	$message = $_POST['message'];

	if($message['subject'] == '')
		$error['subject'] = trans('Message subject not specified!');

	if($message['body'] == '')
		$error['body'] = trans('Message body not specified!');

	if($message['destination']!='' && !check_email($message['destination']))
		$error['destination'] = trans('Incorrect email!');

	if($message['destination']!='' && $message['sender']=='customer')
		$error['destination'] = trans('Customer cannot send message!');

	$files = array();
	foreach ($_FILES['files']['name'] as $fileidx => $filename)
		if (!empty($filename)) {
			if (is_uploaded_file($_FILES['files']['tmp_name'][$fileidx]) && $_FILES['files']['size'][$fileidx]) {
				$filecontents = '';
				$fd = fopen($_FILES['files']['tmp_name'][$fileidx], 'r');
				if ($fd) {
					while (!feof($fd))
						$filecontents .= fread($fd,256);
					fclose($fd);
				}
				$files[] = array(
					'name' => $filename,
					'tmp_name' => $_FILES['files']['tmp_name'][$fileidx],
					'type' => $_FILES['files']['type'][$fileidx],
					'contents' => $filecontents,
				);
			} else { // upload errors
				if (isset($error['files']))
					$error['files'] .= "\n";
				else
					$error['files'] = '';
				switch ($_FILES['files']['error'][$fileidx]) {
					case 1:
					case 2: $error['files'] .= trans('File is too large: $a', $filename); break;
					case 3: $error['files'] .= trans('File upload has finished prematurely: $a', $filename); break;
					case 4: $error['files'] .= trans('Path to file was not specified: $a', $filename); break;
					default: $error['files'] .= trans('Problem during file upload: $a', $filename); break;
				}
			}
		}

	if(!$error)
	{
		$queue = $LMS->GetQueueByTicketId($message['ticketid']);
		$user = $LMS->GetUserInfo($AUTH->id);

		$message['messageid'] = '<msg.'.$message['ticketid'].'.'.$queue['id'].'.'.time().'@rtsystem.'.gethostbyaddr(gethostbyname($_SERVER['SERVER_NAME'])).'>';

		if($message['sender']=='user')
		{
			$message['userid'] = $AUTH->id;
			$message['customerid'] = 0;
		}
		else
		{
			$message['userid'] = 0;
			if(!$message['customerid']) 
			{
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

		if(!ConfigHelper::checkValue(ConfigHelper::getConfig('phpui.helpdesk_backend_mode', false)) || $message['destination'] == '') {
			$headers = array();

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
				$headers['Message-Id'] = $message['messageid'];
				$headers['Reply-To'] = $headers['From'];

				if ($message['references'])
					$headers['References'] = $message['references'];

				$body = $message['body'];

				$attachments = NULL;
				if (!empty($files))
					foreach ($files as $file)
						$attachments[] = array(
							'content_type' => $file['type'],
							'filename' => $file['name'],
							'data' => $file['contents'],
						);

				$LMS->SendMail($recipients, $headers, $body, $attachments);
			}
			else
			{
				$message['messageid'] = '';
				if($message['customerid'] || $message['userid'])
					$message['mailfrom'] = '';
				$message['headers'] = '';
				$message['replyto'] = '';
			}

			MessageAdd($message, $headers, $files);
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
			if ($message['references'])
				$headers['References'] = $message['references'];
			$headers['Message-Id'] = $message['messageid'];
			$headers['Reply-To'] = $headers['From'];

			$body = $message['body'];
			if ($message['destination'] == $queue['email'] || $message['destination'] == $user['email'])
				$body .= "\n\nhttp".($_SERVER['HTTPS'] == 'on' ? 's' : '').'://'
					.$_SERVER['HTTP_HOST'].substr($_SERVER['REQUEST_URI'], 0, strrpos($_SERVER['REQUEST_URI'], '/') + 1)
					.'?m=rtticketview&id='.$message['ticketid'];
			$attachments = NULL;
			if (!empty($files))
				foreach ($files as $file)
					$attachments[] = array(
						'content_type' => $file['type'],
						'filename' => $file['name'],
						'data' => $file['contents'],
					);
			$LMS->SendMail($recipients, $headers, $body, $attachments);

			// message to customer is written to database
			if($message['userid'] && $addmsg) 
				MessageAdd($message, $headers, $files);
		}

		// setting status and the ticket owner
		if (isset($message['state']))
			$message['state'] = RT_RESOLVED;
		else if (!$DB->GetOne('SELECT state FROM rttickets WHERE id = ?', array($message['ticketid'])))
			$message['state'] = RT_OPEN;
		
		if (!$DB->GetOne('SELECT owner FROM rttickets WHERE id = ?', array($message['ticketid'])))
			$message['owner'] = $AUTH->id;

		$props = array(
			'queueid' => $message['queueid'], 
			'owner' => $message['owner'], 
			'cause' => $message['cause'],
			'state' => $message['state']
		);
		$LMS->TicketChange($message['ticketid'], $props);
		
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

			$headers['From'] = $mailfname.' <'.$mailfrom.'>';
			$headers['Subject'] = sprintf("[RT#%06d] %s", $message['ticketid'], $DB->GetOne('SELECT subject FROM rttickets WHERE id = ?', array($message['ticketid'])));
			$headers['Reply-To'] = $headers['From'];

			$sms_body = $headers['Subject']."\n".$message['body'];
			$body = $message['body']."\n\nhttp"
				.(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on' ? 's' : '').'://'
				.$_SERVER['HTTP_HOST']
				.substr($_SERVER['REQUEST_URI'], 0, strrpos($_SERVER['REQUEST_URI'], '/') + 1)
				.'?m=rtticketview&id='.$message['ticketid'];

			if (ConfigHelper::checkValue(ConfigHelper::getConfig('phpui.helpdesk_customerinfo', false)))
				if ($cid = $DB->GetOne('SELECT customerid FROM rttickets WHERE id = ?', array($message['ticketid'])))
				{
					$info = $DB->GetRow('SELECT id, pin, '.$DB->Concat('UPPER(lastname)',"' '",'name').' AS customername,
							address, zip, city FROM customers WHERE id = ?', array($cid));
					$info['contacts'] = $DB->GetAll('SELECT contact, name, type FROM customercontacts
						WHERE customerid = ?', array($cid));

					$emails = array();
					$phones = array();
					if (!empty($info['contacts']))
						foreach ($info['contacts'] as $contact) {
							$target = $contact['contact'] . (strlen($contact['name']) ? ' (' . $contact['name'] . ')' : '');
							if ($contact['type'] & CONTACT_EMAIL )
								$emails[] = $target;
							else
								$phones[] = $target;
						}

					$body .= "\n\n-- \n";
					$body .= trans('Customer:').' '.$info['customername']."\n";
					$body .= trans('ID:').' '.sprintf('%04d', $cid)."\n";
					$body .= trans('Address:').' '.$info['address'].', '.$info['zip'].' '.$info['city']."\n";
					if (!empty($phones))
						$body .= trans('Phone:').' ' . implode(', ', $phones) . "\n";
					if (!empty($emails))
						$body .= trans('E-mail:') . ' ' . implode(', ', $emails);

					$queuedata = $LMS->GetQueueByTicketId($message['ticketid']);
					if (!empty($queuedata['newmessagesubject']) && !empty($queuedata['newmessagebody'])
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
							'To' => '<' . $info['email'] . '>',
							'Reply-To' => $headers['From'],
							'Subject' => $custmail_subject,
						);
						$LMS->SendMail(implode(',', $emails), $custmail_headers, $custmail_body);
					}

					$sms_body .= "\n";
					$sms_body .= trans('Customer:').' '.$info['customername'];
					$sms_body .= ' '.sprintf('(%04d)', $cid).'. ';
					$sms_body .= $info['address'].', '.$info['zip'].' '.$info['city'];
					if (!empty($phones))
						$sms_body .= '. ' . trans('Phone:') . ' ' . preg_replace('/([0-9])[\s-]+([0-9])/', '\1\2', implode(',', $phones));
				}
				elseif ($requestor = $DB->GetOne('SELECT requestor FROM rttickets WHERE id = ?', array($message['ticketid'])))
				{
					$body .= "\n\n-- \n";
					$body .= trans('Customer:').' '.$requestor;

					$sms_body .= "\n";
					$sms_body .= trans('Customer:').' '.$requestor;
				}

			// send email
			if ($recipients = $DB->GetCol('SELECT DISTINCT email
					FROM users, rtrights 
					WHERE users.id=userid AND queueid = ? AND email != \'\' 
						AND (rtrights.rights & 8) = 8 AND users.id != ?
						AND deleted = 0 AND (ntype & ?) = ?',
					array($queue['id'], $AUTH->id, MSG_MAIL, MSG_MAIL)))
			{
				foreach($recipients as $email) {
					$headers['To'] = '<'.$email.'>';

					$LMS->SendMail($email, $headers, $body);
				}
			}

			// send sms
			$service = ConfigHelper::getConfig('sms.service');
			if (!empty($service) && ($recipients = $DB->GetCol('SELECT DISTINCT phone
			        FROM users, rtrights
					WHERE users.id=userid AND queueid = ? AND phone != \'\'
						AND (rtrights.rights & 8) = 8 AND users.id != ?
						AND deleted = 0 AND (ntype & ?) = ?',
					array($queue['id'], $AUTH->id, MSG_SMS, MSG_SMS))))
			{
				foreach($recipients as $phone) {
					$LMS->SendSMS($phone, $sms_body);
				}
			}
		}

		$SESSION->redirect('?m=rtticketview&id='.$message['ticketid']);
	}
}
else
{
	if($_GET['ticketid'])
	{
		$queue = $LMS->GetQueueByTicketId($_GET['ticketid']);
		$message = $DB->GetRow('SELECT id AS ticketid, state, cause, queueid, owner FROM rttickets WHERE id = ?', array($_GET['ticketid']));
	}

	$user = $LMS->GetUserInfo($AUTH->id);
	
	$message['ticketid'] = $_GET['ticketid'];
	$message['customerid'] = $DB->GetOne('SELECT customerid FROM rttickets WHERE id = ?', array($message['ticketid']));
	
	if(isset($_GET['id']))
	{
		$reply = $LMS->GetMessage($_GET['id']); 

		if($reply['replyto'])
			$message['destination'] = preg_replace('/^.* <(.+@.+)>/','\1',$reply['replyto']);
		else 
			$message['destination'] = preg_replace('/^.* <(.+@.+)>/','\1',$reply['mailfrom']);

		if (!$message['destination'] && !$reply['userid']) {
			$message['destination'] = $LMS->GetCustomerEmail($message['customerid']);
			if (!empty($message['destination']))
				$message['destination'] = implode(',', $message['destination']);
		}

		$message['subject'] = 'Re: '.$reply['subject'];
		$message['inreplyto'] = $reply['id'];
		$message['references'] = $reply['messageid'];
		
		if (ConfigHelper::checkValue(ConfigHelper::getConfig('phpui.helpdesk_reply_body', false)))
		{
			$body = explode("\n",textwrap(strip_tags($reply['body']),74));
			foreach($body as $line)
				$message['body'] .= '> '.$line."\n";
		}

		if(!preg_match('/\[RT#[0-9]{6}\]/i', $message['subject'])) 
			$message['subject'] .= sprintf(' [RT#%06d]', $message['ticketid']); 
	}
}

$layout['pagetitle'] = trans('New Message');

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$SMARTY->assign('message', $message);
$SMARTY->assign('error', $error);
$SMARTY->assign('userlist', $LMS->GetUserNames());
$SMARTY->assign('queuelist', $LMS->GetQueueNames());
$SMARTY->display('rt/rtmessageadd.html');

?>
