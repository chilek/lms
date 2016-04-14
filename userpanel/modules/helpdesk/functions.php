<?php

/*
 *  LMS version 1.11-git
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

if (defined('USERPANEL_SETUPMODE'))
{
    function module_setup()
    {
	global $SMARTY, $LMS, $AUTH;

	$default_categories = explode(',', ConfigHelper::getConfig('userpanel.default_categories'));
	$categories = $LMS->GetCategoryListByUser($AUTH->id);
	foreach($categories as $category)
	{
		if (in_array($category['id'], $default_categories))
			$category['checked'] = true;
		$ncategories[] = $category;
	}
	$categories = $ncategories;

	$SMARTY->assign('userlist', $LMS->GetUserNames());
	$SMARTY->assign('queuelist', $LMS->GetQueueNames());
	$SMARTY->assign('queues', explode(';', ConfigHelper::getConfig('userpanel.queues')));
	$SMARTY->assign('tickets_from_selected_queues', ConfigHelper::getConfig('userpanel.tickets_from_selected_queues'));
	$SMARTY->assign('allow_message_add_to_closed_tickets', ConfigHelper::getConfig('userpanel.allow_message_add_to_closed_tickets'));
	$SMARTY->assign('limit_ticket_movements_to_selected_queues', ConfigHelper::getConfig('userpanel.limit_ticket_movements_to_selected_queues'));
        $SMARTY->assign('default_userid', ConfigHelper::getConfig('userpanel.default_userid'));
        $SMARTY->assign('lms_url', ConfigHelper::getConfig('userpanel.lms_url'));
        $SMARTY->assign('categories', $categories);
	$SMARTY->display('module:helpdesk:setup.html');
    }

    function module_submit_setup()
    {
	global $DB;
	if (!empty($_POST['queues']) && array_walk($_POST['queues'], 'intval'))
		$DB->Execute('UPDATE uiconfig SET value = ? WHERE section = \'userpanel\' AND var = \'queues\'', array(implode(';', $_POST['queues'])));
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

function module_main()
{
    global $SMARTY,$LMS,$SESSION,$DB;

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

    if (!$id && isset($_POST['helpdesk']))
    {
        $ticket = $_POST['helpdesk'];

	$ticket['queue'] = intval($ticket['queue']);
	$ticket['categories'] = ConfigHelper::getConfig('userpanel.default_categories');
	$ticket['subject'] = strip_tags($ticket['subject']);
	$ticket['body'] = strip_tags($ticket['body']);

	if(!$ticket['queue'] || !$ticket['categories'])
	{
		header('Location: ?m=helpdesk');
		die;
	}

	if($ticket['subject']=='' && $ticket['body']=='')
	{
		header('Location: ?m=helpdesk');
		die;
	}

	if($ticket['subject'] == '')
		$error['subject'] = trans('Ticket must have its title!');

	if($ticket['body'] == '')
		$error['body'] = trans('Ticket must have its body!');

	if(!$error)
	{
		$ticket['email'] = $LMS->GetCustomerEmail($SESSION->id);
		if (!empty($ticket['email']))
			$ticket['email'] = $ticket['email'][0];
		$ticket['mailfrom'] = $ticket['email'] ? $ticket['email'] : '';
		$ts = time();
		
		$DB->Execute('INSERT INTO rttickets (queueid, customerid, subject, createtime)
				VALUES (?, ?, ?, ?)',
				array($ticket['queue'],
				        $SESSION->id,
					$ticket['subject'],
					$ts
				));

		$id = $DB->GetLastInsertID('rttickets');

		$DB->Execute('INSERT INTO rtmessages (ticketid, customerid, createtime, subject, body, mailfrom)
		                VALUES (?, ?, ?, ?, ?, ?)',
				array($id,
					$SESSION->id,
					$ts,
					$ticket['subject'],
					$ticket['body'],
					$ticket['mailfrom']
				));

		if (!empty($files) && ConfigHelper::getConfig('rt.mail_dir')) {
			$msgid = $DB->GetLastInsertID('rtmessages');
			$dir = ConfigHelper::getConfig('rt.mail_dir') . sprintf('/%06d/%06d', $id, $msgid);
			@mkdir(ConfigHelper::getConfig('rt.mail_dir') . sprintf('/%06d', $id), 0700);
			@mkdir($dir, 0700);
			foreach ($files as $file) {
				$newfile = $dir . '/' . $file['name'];
				if (@rename($file['tmp_name'], $newfile))
					$DB->Execute('INSERT INTO rtattachments (messageid, filename, contenttype)
						VALUES (?,?,?)', array($msgid, $file['name'], $file['type']));
			}
		}

		foreach(explode(',', $ticket['categories']) as $catid)
			$DB->Execute('INSERT INTO rtticketcategories (ticketid, categoryid) VALUES (?, ?)',
				array($id, $catid));

		if(ConfigHelper::checkConfig('phpui.newticket_notify'))
		{
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

			$headers['Date'] = date('r');
			$headers['From'] = $mailfname.' <'.$mailfrom.'>';
			$headers['Subject'] = sprintf("[RT#%06d] %s", $id, $ticket['subject']);
			$headers['Reply-To'] = $headers['From'];

			$sms_body = $headers['Subject']."\n".$ticket['body'];
			$body = $ticket['body']."\n\n".ConfigHelper::getConfig('userpanel.lms_url').'/?m=rtticketview&id='.$id;

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
				$body .= "\n\n-- \n";
				$body .= trans('Customer:').' '.$info['customername']."\n";
				$body .= trans('ID:').' '.sprintf('%04d', $info['customerid'])."\n";
				$body .= trans('Address:').' '.$info['address'].', '.$info['zip'].' '.$info['city']."\n";
				if (!empty($phones))
					$body .= trans('Phone:') . ' ' . implode(', ', $phones) . "\n";
				if (!empty($emails))
					$body .= trans('E-mail:') . ' ' . implode(', ', $emails);

				$sms_body .= "\n";
				$sms_body .= trans('Customer:').' '.$info['customername'];
				$sms_body .= ' '.sprintf('(%04d)', $ticket['customerid']).'. ';
				$sms_body .= $info['address'].', '.$info['zip'].' '.$info['city'].'. ';
				if (!empty($phones))
					$sms_body .= '. ' . trans('Phone:') . ' ' . preg_replace('/([0-9])[\s-]+([0-9])/', '\1\2', implode(',', $phones));
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
			) {
				foreach($recipients as $phone) {
					$LMS->SendSMS($phone, $sms_body);
				}
            }
		}

		header('Location: ?m=helpdesk&op=view&id=' . $id);
		die;
	}
	else
	{
	        $SMARTY->assign('error', $error);
		$SMARTY->assign('helpdesk', $ticket);
	}
    } elseif ($id && isset($_POST['helpdesk'])
	&& ($DB->GetOne('SELECT state FROM rttickets WHERE id = ?', array($id)) != RT_RESOLVED
		|| ConfigHelper::getConfig('userpanel.allow_message_add_to_closed_tickets'))) {
	$ticket = $_POST['helpdesk'];

	$ticket['body'] = strip_tags($ticket['body']);
	$ticket['subject'] = strip_tags($ticket['subject']);
	$ticket['inreplyto'] = intval($ticket['inreplyto']);
	$ticket['id'] = intval($_GET['id']);

	if($ticket['subject'] == '')
		$error['subject'] = trans('Ticket must have its title!');

	if($ticket['body'] == '')
		$error['body'] = trans('Ticket must have its body!');

	if(!$DB->GetOne('SELECT 1 FROM rttickets WHERE id = ? AND customerid = ?',
			array($ticket['id'], $SESSION->id))) 
		$error = true;

	if(!$error)
	{
		$ticket['customerid'] = $SESSION->id;

		// add message
		$DB->Execute('INSERT INTO rtmessages (ticketid, createtime, subject, body, customerid, inreplyto)
                        VALUES (?, ?NOW?, ?, ?, ?, ?)',
		        array(
		                $ticket['id'],
		                $ticket['subject'],
		                $ticket['body'],
		                $ticket['customerid'],
		            	$ticket['inreplyto'],
		        ));

		if (!empty($files) && ConfigHelper::getConfig('rt.mail_dir')) {
			$msgid = $DB->GetLastInsertID('rtmessages');
			$dir = ConfigHelper::getConfig('rt.mail_dir') . sprintf('/%06d/%06d', $id, $msgid);
			@mkdir(ConfigHelper::getConfig('rt.mail_dir') . sprintf('/%06d', $id), 0700);
			@mkdir($dir, 0700);
			foreach ($files as $file) {
				$newfile = $dir . '/' . $file['name'];
				if (@rename($file['tmp_name'], $newfile))
					$DB->Execute('INSERT INTO rtattachments (messageid, filename, contenttype)
						VALUES (?,?,?)', array($msgid, $file['name'], $file['type']));
			}
		}

		// re-open ticket
		$DB->Execute('UPDATE rttickets SET state = CASE state
				WHEN 0 THEN 0
				WHEN 1 THEN 1
				WHEN 2 THEN 1
				WHEN 3 THEN 1 END 
			WHERE id = ?', array($ticket['id']));

		$user = $LMS->GetUserInfo(ConfigHelper::getConfig('userpanel.default_userid'));
		$ticket['queue'] = $LMS->GetQueueByTicketId($ticket['id']);

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

		$headers['Date'] = date('r');
		$headers['From'] = $mailfname . ' <' . $mailfrom . '>';
		$headers['Subject'] = sprintf("[RT#%06d] %s", $ticket['id'], $ticket['subject']);
		$headers['Reply-To'] = $headers['From'];

		$sms_body = $headers['Subject'] . "\n" . $ticket['body'];
		$body = $ticket['body']."\n\n".ConfigHelper::getConfig('userpanel.lms_url') . '/?m=rtticketview&id=' . $ticket['id'];

		if (ConfigHelper::checkConfig('phpui.helpdesk_customerinfo')) {
			$info = $DB->GetRow('SELECT c.id AS customerid, '.$DB->Concat('UPPER(lastname)',"' '",'c.name').' AS customername,
				cc.contact AS email, address, zip, city FROM customeraddressview c WHERE c.id = ?', array($SESSION->id));
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
			$sms_body .= ' '.sprintf('(%04d)', $ticket['customerid']).'. ';
			$sms_body .= $info['address'].', '.$info['zip'].' '.$info['city'].'. ';
			if (!empty($phones))
				$sms_body .= '. ' . trans('Phone:') . ' ' . preg_replace('/([0-9])[\s-]+([0-9])/', '\1\2', implode(',', $phones));
		}

		//print_r($headers);die;
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
	}
	else
	{
	        $SMARTY->assign('error', $error);
		$SMARTY->assign('helpdesk', $ticket);
		$_GET['op'] = 'message';
	}
    }

    if(isset($_GET['op']) && $_GET['op'] == 'view')
    {
	if(preg_match('/^[0-9]+$/',$_GET['id']))
		$ticket = $LMS->GetTicketContents($_GET['id']);

	$ticket['id'] = $_GET['id'];

	$SMARTY->assign('title', trans('Request No. $a / Queue: $b',
		sprintf('%06d',$ticket['ticketid']), $ticket['queuename']));
	
	$queues = explode(';',ConfigHelper::getConfig('userpanel.queues'));
	if($ticket['customerid'] == $SESSION->id && in_array($ticket['queueid'], $queues))
	{
	        $SMARTY->assign('ticket', $ticket);
	        $SMARTY->display('module:helpdeskview.html');
	        die;
	}								
    }
    elseif(isset($_GET['op']) && $_GET['op'] == 'message')
    {
        if(preg_match('/^[0-9]+$/',$_GET['id']))
		$ticket = $LMS->GetTicketContents($_GET['id']);
	
	$ticket['id'] = $_GET['id'];
	
	if(isset($_GET['msgid']) && preg_match('/^[0-9]+$/', $_GET['msgid']))
	{
	        $helpdesk['subject'] = $DB->GetOne('SELECT subject FROM rtmessages
	                	WHERE ticketid = ? AND id = ?', array($ticket['id'], $_GET['msgid']));
	
		$helpdesk['subject'] = preg_replace('/^Re:\s*/', '', $helpdesk['subject']);
		$helpdesk['subject'] = 'Re: '. $helpdesk['subject'];
	        $SMARTY->assign('helpdesk', $helpdesk);
	}

	$SMARTY->assign('title', trans('Request No. $a / Queue: $b',
		sprintf('%06d',$ticket['ticketid']), $ticket['queuename']));
        if($ticket['customerid'] == $SESSION->id)
        {
        	$SMARTY->assign('ticket', $ticket);
	        $SMARTY->display('module:helpdeskreply.html');
		die;
	}
    }

	if ($helpdesklist = $LMS->GetCustomerTickets($SESSION->id))
		foreach($helpdesklist as $idx => $key)
			$helpdesklist[$idx]['lastmod'] = $LMS->DB->GetOne('SELECT MAX(createtime) FROM rtmessages WHERE ticketid = ?',
				array($key['id']));

	$queues = $LMS->DB->GetAll('SELECT id, name FROM rtqueues WHERE id IN (' . str_replace(';', ',', ConfigHelper::getConfig('userpanel.queues')) . ')');
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
