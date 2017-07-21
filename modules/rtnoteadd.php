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

if(isset($_GET['ticketid']))
{
	$note['ticketid'] = intval($_GET['ticketid']);

	if(($LMS->GetUserRightsRT($AUTH->id, 0, $note['ticketid']) & 2) != 2)
        {
	        $SMARTY->display('noaccess.html');
	        $SESSION->close();
	        die;
	}

	$note = $DB->GetRow('SELECT id AS ticketid, state, cause, queueid, owner FROM rttickets WHERE id = ?', array($note['ticketid']));
	$reply = $LMS->GetFirstMessage($note['ticketid']);
	$note['inreplyto'] = $reply['id'];
	$note['references'] = implode(' ', $reply['references']);

        if(ConfigHelper::checkConfig('phpui.helpdesk_notify')){
            $note['notify'] = TRUE;
        }
}
elseif(isset($_POST['note']))
{
	$note = $_POST['note'];
	$ticket = $DB->GetRow('SELECT id AS ticketid, state, cause, queueid, owner, address_id FROM rttickets WHERE id = ?', array($note['ticketid']));

	if($note['body'] == '')
		$error['body'] = trans('Note body not specified!');

	if(!isset($note['ticketid']) || !intval($note['ticketid']))
	{
		$SESSION->redirect('?m=rtqueuelist');
	}

	$result = handle_file_uploads('files', $error);
	extract($result);
	$SMARTY->assign('fileupload', $fileupload);

	if(!$error)
	{
		$messageid = '<msg.' . $ticket['queueid'] . '.' . $note['ticketid'] . '.'  . time() . '@rtsystem.' . gethostname() . '>';

		foreach ($files as &$file)
			$file['name'] = $tmppath . DIRECTORY_SEPARATOR . $file['name'];
		unset($file);
		$msgid = $LMS->TicketMessageAdd(array(
				'ticketid' => $note['ticketid'],
				'messageid' => $messageid,
				'body' => $note['body'],
				'type' => RTMESSAGE_NOTE,
			), $files);

		// setting status and the ticket owner
		$props = array(
			'queueid' => $note['queueid'],
			'owner' => $note['owner'],
			'cause' => $note['cause'],
			'state' => $note['state']
		);
		$LMS->TicketChange($note['ticketid'], $props);

		if(isset($note['notify']))
		{
			$user = $LMS->GetUserInfo($AUTH->id);
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

			$mailfrom = $user['email'] ? $user['email'] : $queue['email'];

			$ticketdata = $LMS->GetTicketContents($note['ticketid']);
			$ticketcat = $LMS->GetTicketCategories($note['ticketid']);
			foreach ($ticketcat as $tcat)
				$tcatname = $tcatname . $tcat['name'] .' ; ';

			$helpdesk_msgsubject = ConfigHelper::getConfig('phpui.helpdesk_msgsubject');
			$helpdesk_msgsubject = str_replace('%tid', str_pad($note['ticketid'],6,"0",STR_PAD_LEFT), $helpdesk_msgsubject);
			$helpdesk_msgsubject = str_replace('%cid', str_pad($ticketdata['customerid'],4,"0",STR_PAD_LEFT), $helpdesk_msgsubject);
			$helpdesk_msgsubject = str_replace('%status', $ticketdata['status'], $helpdesk_msgsubject);
			$helpdesk_msgsubject = str_replace('%cat', $tcatname, $helpdesk_msgsubject);

			$headers['From'] = $mailfname.' <'.$mailfrom.'>';
			$headers['Subject'] = $helpdesk_msgsubject .' # ';
// 			$headers['Subject'] = sprintf("[RT#%06d] %s", $note['ticketid'], $DB->GetOne('SELECT subject FROM rttickets WHERE id = ?', array($note['ticketid'])));
			$headers['Reply-To'] = $headers['From'];
			if ($note['references']) {
				$headers['References'] = explode(' ', $note['references']);
				$headers['In-Reply-To'] = array_pop(explode(' ', $note['references']));
			}

			$helpdesk_msgbody = ConfigHelper::getConfig('phpui.helpdesk_msgbody');
			$helpdesk_msgbody = str_replace('%tid', str_pad($note['ticketid'],6,"0",STR_PAD_LEFT), $helpdesk_msgbody);
			$helpdesk_msgbody = str_replace('%cid', str_pad($ticket['customerid'],4,"0",STR_PAD_LEFT), $helpdesk_msgbody);
			$helpdesk_msgbody = str_replace('%status', $ticketdata['status'], $helpdesk_msgbody);
			$helpdesk_msgbody = str_replace('%cat', $tcatname, $helpdesk_msgbody);
			$url = 'http'
					.(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on' ? 's' : '').'://'
							.$_SERVER['HTTP_HOST']
							.substr($_SERVER['REQUEST_URI'], 0, strrpos($_SERVER['REQUEST_URI'], '/') + 1)
							.'?m=rtticketview&id=' . $note['ticketid'] . (isset($msgid) ? '#rtmessage-' . $msgid : '');
			$helpdesk_msgbody = str_replace('%url', $url, $helpdesk_msgbody);

			$body = $helpdesk_msgbody ."\n\n". $note['body'];

			$sms_body = $headers['Subject']."\n".$note['body'];

			if (ConfigHelper::checkConfig('phpui.helpdesk_customerinfo')
				&& ($cid = $DB->GetOne('SELECT customerid FROM rttickets WHERE id = ?', array($note['ticketid'])))) {
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

				$locations = $LMS->getCustomerAddresses($cid);

				$body .= "\n\n-- \n";
				$body .= trans('Customer:').' '.$info['customername']."\n";
				$body .= trans('Address:') . ' ' . (empty($ticket['address_id']) ? $info['address'] . ', ' . $info['zip'] . ' ' . $info['city']
					: $locations[$ticket['address_id']]['location']) . "\n";
				if (!empty($phones))
					$body .= trans('Phone:').' ' . implode(', ', $phones) . "\n";
				if (!empty($emails))
					$body .= trans('E-mail:') . ' ' . implode(', ', $emails);

				$sms_body .= "\n";
				$sms_body .= trans('Customer:').' '.$info['customername'];
				$sms_body .= ' '.sprintf('(%04d)', $cid).'. ';
				$sms_body .= (empty($ticket['address_id']) ? $info['address'] . ', ' . $info['zip'] . ' ' . $info['city']
					: $locations[$ticket['address_id']]['location']);
				if (!empty($phones))
					$sms_body .= '. ' . trans('Phone:') . ' ' . preg_replace('/([0-9])[\s-]+([0-9])/', '\1\2', implode(',', $phones));
			}

			$notify_author = ConfigHelper::checkConfig('phpui.helpdesk_author_notify');
			$args = array(
				'queue' => $queue['id'],
				'user' => $AUTH->id,
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
					array_values($args))
			)
				foreach ($recipients as $email) {
					$headers['To'] = '<'.$email.'>';

					$LMS->SendMail($email, $headers, $body);
				}

			// send sms
			$service = ConfigHelper::getConfig('sms.service');
			$args['type'] = MSG_SMS;
			if (!empty($service) && ($recipients = $DB->GetCol('SELECT DISTINCT phone
				FROM users, rtrights
					WHERE users.id=userid AND queueid = ? AND phone != \'\'
						AND (rtrights.rights & 8) = 8 AND deleted = 0'
						. ($notify_author ? '' : ' AND users.id <> ?')
						. ' AND (ntype & ?) > 0',
					array_values($args)))
			)
				foreach ($recipients as $phone)
					$LMS->SendSMS($phone, $sms_body);
		}

		$SESSION->redirect('?m=rtticketview&id=' . $note['ticketid'] . (isset($msgid) ? '#rtmessage-' . $msgid : ''));
	}
}
else
{
	header('Locaton: ?m=rtqueuelist');
	die;
}

$layout['pagetitle'] = trans('New Note');

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$SMARTY->assign('note', $note);
$SMARTY->assign('ticket', $LMS->GetTicketContents($note['ticketid']));
$SMARTY->assign('userlist', $LMS->GetUserNames());
$SMARTY->assign('queuelist', $LMS->GetQueueNames());
$SMARTY->assign('error', $error);
$SMARTY->display('rt/rtnoteadd.html');

?>
