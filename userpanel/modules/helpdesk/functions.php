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
	function module_setup() {
		global $SMARTY, $LMS, $AUTH;

		$default_categories = explode(',', ConfigHelper::getConfig('userpanel.default_categories'));
		$categories = $LMS->GetCategoryListByUser($AUTH->id);
		foreach ($categories as $category) {
			if (in_array($category['id'], $default_categories))
				$category['checked'] = true;
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
		$SMARTY->display('module:helpdesk:setup.html');
	}

	function module_submit_setup() {
		$DB = LMSDB::getInstance();
		if (!empty($_POST['queues']) && array_walk($_POST['queues'], 'intval'))
			$DB->Execute('UPDATE uiconfig SET value = ? WHERE section = \'userpanel\' AND var = \'queues\'', array(implode(';', $_POST['queues'])));
		if (!empty($_POST['sources']) && array_walk($_POST['sources'], 'intval'))
			$DB->Execute('UPDATE uiconfig SET value = ? WHERE section = \'userpanel\' AND var = \'visible_ticket_sources\'',
				array(implode(';', $_POST['sources'])));
		$DB->Execute('UPDATE uiconfig SET value = ? WHERE section = \'userpanel\' AND var = \'tickets_from_selected_queues\'',
			array(intval($_POST['tickets_from_selected_queues'])));
		$DB->Execute('UPDATE uiconfig SET value = ? WHERE section = \'userpanel\' AND var = \'allow_message_add_to_closed_tickets\'',
			array(intval($_POST['allow_message_add_to_closed_tickets'])));
		$DB->Execute('UPDATE uiconfig SET value = ? WHERE section = \'userpanel\' AND var = \'limit_ticket_movements_to_selected_queues\'',
			array(intval($_POST['limit_ticket_movements_to_selected_queues'])));
		$DB->Execute('UPDATE uiconfig SET value = ? WHERE section = \'userpanel\' AND var = \'default_userid\'',array($_POST['default_userid']));
		$DB->Execute('UPDATE uiconfig SET value = ? WHERE section = \'userpanel\' AND var = \'lms_url\'',array($_POST['lms_url']));
		$categories = array_keys((isset($_POST['lms_categories']) ? $_POST['lms_categories'] : array()));
		$DB->Execute('UPDATE uiconfig SET value = ? WHERE section = \'userpanel\' AND var = \'default_categories\'', array(implode(',', $categories)));
		header('Location: ?m=userpanel&module=helpdesk');
	}
}

function module_main() {
	global $SMARTY, $LMS, $SESSION , $RT_STATES;

	$DB = LMSDB::getInstance();

	$error = NULL;

	$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

	if (isset($_FILES['files'])) {
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
	}

	if (!$id && isset($_POST['helpdesk'])) {
		$ticket = $_POST['helpdesk'];

		$ticket['queue'] = intval($ticket['queue']);
		$ticket['categories'] = ConfigHelper::getConfig('userpanel.default_categories');
		$ticket['subject'] = strip_tags($ticket['subject']);
		$ticket['body'] = strip_tags($ticket['body']);

		if (!$ticket['queue'] || !$ticket['categories']) {
			header('Location: ?m=helpdesk');
			die;
		}

		if ($ticket['subject'] == '' && $ticket['body'] == '') {
			header('Location: ?m=helpdesk');
			die;
		}

		if ($ticket['subject'] == '')
			$error['subject'] = trans('Ticket must have its title!');

		if ($ticket['body'] == '')
			$error['body'] = trans('Ticket must have its body!');

		$queues = explode(';', ConfigHelper::getConfig('userpanel.queues'));
		if (!in_array($ticket['queue'], $queues))
			$error = true;

		if (!$error) {
			$ticket['email'] = $LMS->GetCustomerEmail($SESSION->id);
			if (!empty($ticket['email']))
				$ticket['email'] = $ticket['email'][0];
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

				if($mailfname = ConfigHelper::getConfig('phpui.helpdesk_sender_name'))
				{
					if($mailfname == 'queue') $mailfname = $LMS->GetQueueName($ticket['queue']);
					if($mailfname == 'user') $mailfname = $user['name'];
					$mailfname = '"'.$mailfname.'"';
				}

				if ($user['email'])
					$mailfrom = $user['email'];
				elseif ($qemail = $LMS->GetQueueEmail($ticket['queue']))
					$mailfrom = $qemail;
				else
					$mailfrom =  $ticket['mailfrom'];

				$ticketdata = $LMS->GetTicketContents($id);
				foreach ($ticketdata[categories] as $tcat)
					$tcatname = $tcatname . $tcat['name'] .' ; ';

				$helpdesk_notification_mail_subject = ConfigHelper::getConfig('phpui.helpdesk_notification_mail_subject');
				$helpdesk_notification_mail_subject = str_replace('%tid', sprintf("%06d",$id), $helpdesk_notification_mail_subject);
				$helpdesk_notification_mail_subject = str_replace('%cid', sprintf("%04d",$id['customerid']), $helpdesk_notification_mail_subject);
				$helpdesk_notification_mail_subject = str_replace('%status', $RT_STATES[$ticketdata['state']], $helpdesk_notification_mail_subject);
				$helpdesk_notification_mail_subject = str_replace('%cat', $tcatname, $helpdesk_notification_mail_subject);

				$headers['Date'] = date('r');
				$headers['From'] = $mailfname.' <'.$mailfrom.'>';
				$headers['Subject'] = $helpdesk_notification_mail_subject .' '.$ticket['subject'];
				$headers['Reply-To'] = $headers['From'];
				$headers['Message-ID'] = $LMS->GetLastMessageID();

				$helpdesk_notification_mail_body = ConfigHelper::getConfig('phpui.helpdesk_notification_mail_body');
				$helpdesk_notification_mail_body = str_replace('%tid', sprintf("%06d",$id), $helpdesk_notification_mail_body);
				$helpdesk_notification_mail_body = str_replace('%cid', sprintf("%04d",$id['customerid']), $helpdesk_notification_mail_body);
				$helpdesk_notification_mail_body = str_replace('%status', $RT_STATES[$ticketdata['state']], $helpdesk_notification_mail_body);
				$helpdesk_notification_mail_body = str_replace('%cat', $tcatname, $helpdesk_notification_mail_body);
				$url = 'http'
						.(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on' ? 's' : '').'://'
						.$_SERVER['HTTP_HOST']
						.substr($_SERVER['REQUEST_URI'], 0, strrpos($_SERVER['REQUEST_URI'], '/') + 1)
						.'?m=rtticketview&id='.$id;
				$helpdesk_notification_mail_body = str_replace('%url', $url, $helpdesk_notification_mail_body);

				$body = $ticket['body']."\n\n".$helpdesk_notification_mail_body;

				$helpdesk_notification_sms_body = ConfigHelper::getConfig('phpui.helpdesk_notification_sms_body');
				$helpdesk_notification_sms_body = str_replace('%tid', sprintf("%06d",$id), $helpdesk_notification_sms_body);
				$helpdesk_notification_sms_body = str_replace('%cid', sprintf("%04d",$id['customerid']), $helpdesk_notification_sms_body);
				$helpdesk_notification_sms_body = str_replace('%status', $RT_STATES[$ticketdata['state']], $helpdesk_notification_sms_body);
				$helpdesk_notification_sms_body = str_replace('%cat', $tcatname, $helpdesk_notification_sms_body);

				$sms_body = $helpdesk_notification_sms_body."\n".$ticket['body'];

				$info = $DB->GetRow('SELECT id AS customerid, pin, '.$DB->Concat('UPPER(lastname)',"' '",'name').' AS customername,
					address, zip, city FROM customeraddressview WHERE id = ?', array($SESSION->id));
				$info['contacts'] = $DB->GetAll('SELECT contact, name FROM customercontacts
						WHERE customerid = ?', array($SESSION->id));

				$emails = array();
				$phones = array();
				if (!empty($info['contacts']))
					foreach ($info['contacts'] as $contact)
						if ($contact['type'] & CONTACT_NOTIFICATIONS) {
							$contact = $contact['contact'] . (strlen($contact['name']) ? ' (' . $contact['name'] . ')' : '');
							if ($contact['type'] & CONTACT_EMAIL)
								$emails[] = $contact;
							else
								$phones[] = $contact;
						}

				if (ConfigHelper::checkConfig('phpui.helpdesk_customerinfo')) {

					$helpdesk_customerinfo_mail_body = ConfigHelper::getConfig('phpui.helpdesk_customerinfo_mail_body');
					$helpdesk_customerinfo_mail_body = str_replace('%custname', $info['customername'], $helpdesk_customerinfo_mail_body);
					$helpdesk_customerinfo_mail_body = str_replace('%cid', sprintf("%04d",$info['customerid']), $helpdesk_customerinfo_mail_body);
					$helpdesk_customerinfo_mail_body = str_replace('%address', $info['address'] . ', ' . $info['zip'] . ' ' . $info['city'], $helpdesk_customerinfo_mail_body);
					if (!empty($phones))
						$helpdesk_customerinfo_mail_body = str_replace('%phone', implode(', ', $phones), $helpdesk_customerinfo_mail_body);
					if (!empty($emails))
						$helpdesk_customerinfo_mail_body = str_replace('%email', implode(', ', $emails), $helpdesk_customerinfo_mail_body);

					$body .= "\n\n-- \n";
					$body .= $helpdesk_customerinfo_mail_body;

					$helpdesk_customerinfo_sms_body = ConfigHelper::getConfig('phpui.helpdesk_customerinfo_sms_body');
					$helpdesk_customerinfo_sms_body = str_replace('%custname', $info['customername'], $helpdesk_customerinfo_sms_body);
					$helpdesk_customerinfo_sms_body = str_replace('%cid', sprintf("%04d",$info['customerid']), $helpdesk_customerinfo_sms_body);
					$helpdesk_customerinfo_sms_body = str_replace('%address', $info['address'] . ', ' . $info['zip'] . ' ' . $info['city'], $helpdesk_customerinfo_sms_body);
					if (!empty($phones))
						$helpdesk_customerinfo_sms_body = str_replace('%phone', preg_replace('/([0-9])[\s-]+([0-9])/', '\1\2', implode(',', $phones)), $helpdesk_customerinfo_sms_body);

					$sms_body .= "\n";
					$sms_body .= $helpdesk_customerinfo_sms_body;
				}

				$queuedata = $LMS->GetQueue($ticket['queue']);
				if (!empty($queuedata['newticketsubject']) && !empty($queuedata['newticketbody'])
					&& !empty($emails)) {
					$custmail_subject = $queuedata['newticketsubject'];
					$custmail_subject = str_replace('%tid', $id, $custmail_subject);
					$custmail_subject = str_replace('%title', $ticket['subject'], $custmail_subject);
					$custmail_body = $queuedata['newticketbody'];
					$custmail_body = str_replace('%tid', $id, $custmail_body);
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
					$LMS->SendMail(implode(',', $emails), $custmail_headers, $custmail_body);
				}

				// send email
				if ($recipients = $DB->GetCol('SELECT DISTINCT email
				    FROM users, rtrights
				    WHERE users.id = userid AND email != \'\' AND (rtrights.rights & 8) = 8
				        AND (ntype & ?) = ? AND queueid = ?',
				    array(MSG_MAIL, MSG_MAIL, intval($ticket['queue'])))
				) {
					foreach($recipients as $email) {
						$headers['To'] = '<'.$email.'>';

						$LMS->SendMail($email, $headers, $body);
					}
				}

				// send sms
				$service = ConfigHelper::getConfig('sms.service');
				if (!empty($service) && ($recipients = $DB->GetCol('SELECT DISTINCT phone
				    FROM users, rtrights
				    WHERE users.id = userid AND phone != \'\' AND (rtrights.rights & 8) = 8
				        AND (ntype & ?) = ? AND queueid = ?',
				    array(MSG_SMS, MSG_SMS, intval($ticket['queue']))))
				)
					foreach($recipients as $phone)
						$LMS->SendSMS($phone, $sms_body);
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

		$ticket['body'] = strip_tags($ticket['body']);
		$ticket['subject'] = strip_tags($ticket['subject']);
		$ticket['inreplyto'] = intval($ticket['inreplyto']);
		$ticket['id'] = intval($_GET['id']);

		if ($ticket['subject'] == '')
			$error['subject'] = trans('Ticket must have its title!');

		if ($ticket['body'] == '')
			$error['body'] = trans('Ticket must have its body!');

		$queues = str_replace(';', ',', ConfigHelper::getConfig('userpanel.queues'));
		$sources = str_replace(';', ',', ConfigHelper::getConfig('userpanel.visible_ticket_sources'));
		if (!$DB->GetOne('SELECT 1 FROM rttickets WHERE source IN (' . $sources . ')
			AND queueid IN ('. $queues . ') AND id = ? AND customerid = ?',
			array($ticket['id'], $SESSION->id)))
			$error = true;

		if (!$error) {
			$ticket['customerid'] = $SESSION->id;
			$ticket['queue'] = $LMS->GetQueueByTicketId($ticket['id']);

			// add message
			$ticket['messageid'] = '<msg.' . $ticket['queue']['id'] . '.' . $ticket['id'] . '.' . time()
				. '@rtsystem.' . gethostname() . '>';

			$LMS->TicketMessageAdd(array(
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
			$DB->Execute('UPDATE rttickets SET state = ? WHERE id = ?',
				array($ticket_change_state_map[$ticket['state']], $ticket['id']));

			$user = $LMS->GetUserInfo(ConfigHelper::getConfig('userpanel.default_userid'));

			if ($mailfname = ConfigHelper::getConfig('phpui.helpdesk_sender_name')) {
				if ($mailfname == 'queue')
					$mailfname = $ticket['queue']['name'];
				if ($mailfname == 'user')
					$mailfname = $user['name'];
				$mailfname = '"' . $mailfname . '"';
			}

			$ticket['email'] = $LMS->GetCustomerEmail($SESSION->id);
			$ticket['mailfrom'] = $ticket['email'] ? $ticket['email'] : '';

			if ($user['email'])
				$mailfrom = $user['email'];
			elseif (!empty($ticket['queue']['email']))
				$mailfrom = $ticket['queue']['email'];
			else
				$mailfrom = $ticket['mailfrom'];

			$ticketdata = $LMS->GetTicketContents($ticket['id']);
			foreach ($ticketdata[categories] as $tcat)
				$tcatname = $tcatname . $tcat['name'] .' ; ';

			$helpdesk_notification_mail_subject = ConfigHelper::getConfig('phpui.helpdesk_notification_mail_subject');
			$helpdesk_notification_mail_subject = str_replace('%tid', sprintf("%06d",$ticket['id']), $helpdesk_notification_mail_subject);
			$helpdesk_notification_mail_subject = str_replace('%cid', sprintf("%04d",$ticket['customerid']), $helpdesk_notification_mail_subject);
			$helpdesk_notification_mail_subject = str_replace('%status', $RT_STATES[$ticketdata['state']], $helpdesk_notification_mail_subject);
			$helpdesk_notification_mail_subject = str_replace('%cat', $tcatname, $helpdesk_notification_mail_subject);

			$headers['Date'] = date('r');
			$headers['From'] = $mailfname . ' <' . $mailfrom . '>';
			$headers['Subject'] = $helpdesk_notification_mail_subject .' '.$ticket['subject'];
			$headers['Reply-To'] = $headers['From'];
			if ($ticket['references']) {
				$headers['References'] = $ticket['references'];
				$headers['In-Reply-To'] = array_pop(explode(' ', $ticket['references']));
			}
			$headers['Message-ID'] = $ticket['messageid'];

			$helpdesk_notification_mail_body = ConfigHelper::getConfig('phpui.helpdesk_notification_mail_body');
			$helpdesk_notification_mail_body = str_replace('%tid', sprintf("%06d",$ticket['id']), $helpdesk_notification_mail_body);
			$helpdesk_notification_mail_body = str_replace('%cid', sprintf("%04d",$ticket['customerid']), $helpdesk_notification_mail_body);
			$helpdesk_notification_mail_body = str_replace('%status', $ticketdata['status'], $helpdesk_notification_mail_body);
			$helpdesk_notification_mail_body = str_replace('%cat', $tcatname, $helpdesk_notification_mail_body);
			$url = 'http'
					.(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on' ? 's' : '').'://'
					.$_SERVER['HTTP_HOST']
					.substr($_SERVER['REQUEST_URI'], 0, strrpos($_SERVER['REQUEST_URI'], '/') + 1)
					.'?m=rtticketview&id=' . $ticket['id'];
			$helpdesk_notification_mail_body = str_replace('%url', $url, $helpdesk_notification_mail_body);

			$body = $ticket['body']."\n\n".$helpdesk_notification_mail_body;

			$helpdesk_notification_sms_body = ConfigHelper::getConfig('phpui.helpdesk_notification_sms_body');
			$helpdesk_notification_sms_body = str_replace('%tid', sprintf("%06d",$ticket['id']), $helpdesk_notification_sms_body);
			$helpdesk_notification_sms_body = str_replace('%cid', sprintf("%04d",$ticket['customerid']), $helpdesk_notification_sms_body);
			$helpdesk_notification_sms_body = str_replace('%status', $ticketdata['status'], $helpdesk_notification_sms_body);
			$helpdesk_notification_sms_body = str_replace('%cat', $tcatname, $helpdesk_notification_sms_body);

			$sms_body = $helpdesk_notification_sms_body."\n".$ticket['body'];

			if (ConfigHelper::checkConfig('phpui.helpdesk_customerinfo')) {
				$info = $DB->GetRow('SELECT c.id AS customerid, '.$DB->Concat('UPPER(lastname)',"' '",'c.name').' AS customername,
					address, zip, city FROM customeraddressview c WHERE c.id = ?', array($SESSION->id));
				$info['contacts'] = $DB->GetAll('SELECT contact, name, type FROM customercontacts
						WHERE customerid = ?', array($SESSION->id));

				$emails = array();
				$phones = array();
				if (!empty($info['contacts']))
					foreach ($info['contacts'] as $contact)
						if ($contact['type'] & CONTACT_NOTIFICATIONS) {
							$target = $contact['contact'] . (strlen($contact['name']) ? ' (' . $contact['name'] . ')' : '');
							if ($contact['type'] & CONTACT_EMAIL)
								$emails[] = $target;
							else
								$phones[] = $target;
						}

				$body .= "\n\n-- \n";
				$body .= trans('Customer:').' '.$info['customername']."\n";
				$body .= trans('ID:').' '.sprintf('%04d', $info['customerid'])."\n";
				$body .= trans('Address:').' '.$info['address'].', '.$info['zip'].' '.$info['city']."\n";
				if (!empty($phones))
					$body .= trans('Phone:').' ' . implode(', ', $phones) . "\n";
				if (!empty($emails))
					$body .= trans('E-mail:') . ' ' . implode(', ', $emails);

				$sms_body .= "\n";
				$sms_body .= trans('Customer:').' '.$info['customername'];
				$sms_body .= ' '.sprintf('(%04d)', $info['customerid']).'. ';
				$sms_body .= $info['address'].', '.$info['zip'].' '.$info['city'].'. ';
				if (!empty($phones))
					$sms_body .= '. ' . trans('Phone:') . ' ' . preg_replace('/([0-9])[\s-]+([0-9])/', '\1\2', implode(',', $phones));
			}

			// send email
			if ($recipients = $DB->GetCol('SELECT DISTINCT email
				FROM users, rtrights
				WHERE users.id = userid AND email != \'\' AND (rtrights.rights & 8) = 8
					AND (ntype & ?) = ? AND queueid = ?',
				array(MSG_MAIL, MSG_MAIL, intval($ticket['queue']['id'])))) {
				foreach ($recipients as $email) {
					$headers['To'] = '<' . $email . '>';
					$LMS->SendMail($email, $headers, $body);
				}
			}

			// send sms
			$service = ConfigHelper::getConfig('sms.service');
			if (!empty($service) && ($recipients = $DB->GetCol('SELECT DISTINCT phone
				FROM users, rtrights
				WHERE users.id = userid AND phone != \'\' AND (rtrights.rights & 8) = 8
					AND (ntype & ?) = ? AND queueid = ?',
				array(MSG_SMS, MSG_SMS, intval($ticket['queue']['id']))))) {
				foreach ($recipients as $phone)
					$LMS->SendSMS($phone, $sms_body);
			}

			header('Location: ?m=helpdesk&op=view&id='.$ticket['id']);
			die;
		} else {
			$SMARTY->assign('error', $error);
			$SMARTY->assign('helpdesk', $ticket);
			$_GET['op'] = 'message';
		}
	}

	if (isset($_GET['op']) && $_GET['op'] == 'view') {
		if (intval($_GET['id']))
			$ticket = $LMS->GetTicketContents($_GET['id']);

		$ticket['id'] = $_GET['id'];

		$queues = explode(';', ConfigHelper::getConfig('userpanel.queues'));
		$sources = explode(';', ConfigHelper::getConfig('userpanel.visible_ticket_sources'));
		if ($ticket['customerid'] == $SESSION->id && in_array($ticket['queueid'], $queues)
			&& in_array($ticket['source'], $sources)) {
			$SMARTY->assign('title', trans('Request No. $a / Queue: $b',
				sprintf('%06d',$ticket['ticketid']), $ticket['queuename']));

			$SMARTY->assign('ticket', $ticket);
			$SMARTY->display('module:helpdeskview.html');
			die;
		}
	} elseif(isset($_GET['op']) && $_GET['op'] == 'message') {
		if (intval($_GET['id']))
			$ticket = $LMS->GetTicketContents($_GET['id']);

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

			$SMARTY->assign('title', trans('Request No. $a / Queue: $b',
				sprintf('%06d', $ticket['ticketid']), $ticket['queuename']));
			if ($ticket['customerid'] == $SESSION->id) {
				$SMARTY->assign('ticket', $ticket);
				$SMARTY->display('module:helpdeskreply.html');
				die;
			}
		}
	}

	if ($helpdesklist = $LMS->GetCustomerTickets($SESSION->id))
		foreach ($helpdesklist as $idx => $key)
			$helpdesklist[$idx]['lastmod'] = $LMS->DB->GetOne('SELECT MAX(createtime) FROM rtmessages WHERE ticketid = ?',
				array($key['id']));

	$queues = $LMS->DB->GetAll('SELECT id, name FROM rtqueues WHERE id IN ('
		. str_replace(';', ',', ConfigHelper::getConfig('userpanel.queues')) . ')');
	$SMARTY->assign('queues', $queues);
	$SMARTY->assign('helpdesklist', $helpdesklist);
	$SMARTY->display('module:helpdesk.html');
}

function module_attachment() {
	global $DB, $SESSION;
	$attach = $DB->GetRow('SELECT ticketid, filename, contenttype FROM rtattachments a
		JOIN rtmessages m ON m.id = a.messageid
		JOIN rttickets t ON t.id = m.ticketid
		WHERE t.customerid = ? AND a.messageid = ? AND filename = ?',
		array($SESSION->id, $_GET['msgid'], $_GET['file']));
	if (empty($attach))
		die;
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

?>
