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

include(MODULES_DIR . DIRECTORY_SEPARATOR . 'rtticketxajax.inc.php');

$id = intval($_GET['id']);
if ($id && !isset($_POST['ticket'])) {
	if(($LMS->GetUserRightsRT($AUTH->id, 0, $id) & 2) != 2 || !$LMS->GetUserRightsToCategory($AUTH->id, 0, $id))
	{
		$SMARTY->display('noaccess.html');
		$SESSION->close();
		die;
	}

	if (isset($_GET['state']) && $_GET['state']) {
		$state = intval($_GET['state']);
		$LMS->TicketChange($id, array('state' => $state));

		if ($state == RT_RESOLVED) {
			$queue = $LMS->GetQueueByTicketId($id);
			if (!empty($queue['resolveticketsubject']) && !empty($queue['resolveticketbody'])) {
				$ticket = $DB->GetRow('SELECT * FROM rttickets WHERE id = ?', array($id));
				if (!empty($ticket['customerid'])) {
					$user = $LMS->GetUserInfo($AUTH->id);
					$mailfname = '';

					$helpdesk_sender_name = ConfigHelper::getConfig('phpui.helpdesk_sender_name');
					if (!empty($helpdesk_sender_name)) {
						if ($helpdesk_sender_name == 'queue')
							$mailfname = $$queue['name'];
						elseif ($helpdesk_sender_name == 'user')
							$mailfname = $user['name'];

						$mailfname = '"' . $mailfname . '"';
					}

					$mailfrom = $user['email'] ? $user['email'] : $queue['email'];
					$from = $mailfname . ' <' . $mailfrom . '>';

					$info = $DB->GetRow('SELECT id, pin, '.$DB->Concat('UPPER(lastname)',"' '",'name').' AS customername,
							address, zip, city,
								(SELECT ' . $DB->GroupConcat('contact', ',', true) . ' FROM customercontacts 
								WHERE customerid = c.id AND (type & ?) > 0) AS emails,
								(SELECT ' . $DB->GroupConcat('contact', ',', true) . ' FROM customercontacts 
								WHERE customerid = c.id AND (type & ?) > 0) AS phones
							FROM customeraddressview c
							WHERE id = ?', array(CONTACT_EMAIL, (CONTACT_MOBILE|CONTACT_FAX|CONTACT_LANDLINE), $ticket['customerid']));
					if (!empty($info['emails'])) {
						$custmail_subject = $queue['resolveticketsubject'];
						$custmail_subject = str_replace('%tid', $id, $custmail_subject);
						$custmail_subject = str_replace('%title', $ticket['subject'], $custmail_subject);
						$custmail_body = $queue['resolveticketbody'];
						$custmail_body = str_replace('%tid', $id, $custmail_body);
						$custmail_body = str_replace('%cid', $info['id'], $custmail_body);
						$custmail_body = str_replace('%pin', $info['pin'], $custmail_body);
						$custmail_body = str_replace('%customername', $info['customername'], $custmail_body);
						$custmail_body = str_replace('%title', $ticket['subject'], $custmail_body);
						$custmail_headers = array(
							'From' => $from,
							'Reply-To' => $from,
							'Subject' => $custmail_subject,
						);
						foreach (explode(',', $info['emails']) as $email) {
							$custmail_headers['To'] = '<' . $email . '>';
							$LMS->SendMail($email, $custmail_headers, $custmail_body);
						}
					}
				}
			}
		}

		$SESSION->redirect('?m=rtticketview&id='.$id);
	}

	if (isset($_GET['assign'])) {
		$LMS->TicketChange($id, array('owner' => $AUTH->id));
		$SESSION->redirect('?m=rtticketview&id=' . $id);
	}
}

$ticket = $LMS->GetTicketContents($id);
$categories = $LMS->GetCategoryListByUser($AUTH->id);
if (empty($categories))
	$categories = array();

if(isset($_POST['ticket']))
{
	$ticketedit = $_POST['ticket'];
	$ticketedit['ticketid'] = $ticket['ticketid'];

	if(!count($ticketedit['categories']))
		$error['categories'] = trans('You have to select category!');

	if(($LMS->GetUserRightsRT($AUTH->id, $ticketedit['queueid']) & 2) != 2)
		$error['queue'] = trans('You have no privileges to this queue!');
	
	if($ticketedit['subject'] == '')
		$error['subject'] = trans('Ticket must have its title!');

	if($ticketedit['state']>0 && !$ticketedit['owner'])
		$error['owner'] = trans('Only \'new\' ticket can be owned by no one!');

	if($ticketedit['state']==0 && $ticketedit['owner'])
		$ticketedit['state'] = 1;

	$ticketedit['customerid'] = ($ticketedit['custid'] ? $ticketedit['custid'] : 0);

	if(!$error)
	{
/*		if($ticketedit['state'] == RT_RESOLVED)
		{
			$DB->Execute('UPDATE rttickets SET subject=?, state=?, customerid=?, cause=?, resolvetime=?NOW? 
					WHERE id=?', array(
						$ticketedit['subject'], 
						$ticketedit['state'], 
						$ticketedit['customerid'], 
						$ticketedit['cause'], 
						$ticketedit['ticketid']
						));
		}
		else
		{
			// if ticket was resolved, set resolvetime=0
			if($DB->GetOne('SELECT state FROM rttickets WHERE id = ?', array($ticket['ticketid'])) == 2)
			{
				$DB->Execute('UPDATE rttickets SET subject=?, state=?, customerid=?, cause=?, resolvetime=0 
					WHERE id=?', array(
						$ticketedit['subject'], 
						$ticketedit['state'], 
						$ticketedit['customerid'], 
						$ticketedit['cause'], 
						$ticketedit['ticketid']
						));
			}
			else
			{
				$DB->Execute('UPDATE rttickets SET subject=?, state=?, customerid=?, cause=? 
					WHERE id=?', array(
						$ticketedit['subject'], 
						$ticketedit['state'], 
						$ticketedit['customerid'], 
						$ticketedit['cause'], 
						$ticketedit['ticketid']
						));
			}
		}
*/
		// setting status and the ticket owner
		$props = array(
			'queueid' => $ticketedit['queueid'],
			'owner' => $ticketedit['owner'],
			'cause' => $ticketedit['cause'],
			'state' => $ticketedit['state'],
			'subject' => $ticketedit['subject'],
			'customerid' => $ticketedit['customerid'],
			'categories' => isset($ticketedit['categories']) ? array_keys($ticketedit['categories']) : array(),
			'source' => $ticketedit['source'],
			'address_id' => $ticketedit['address_id'] == -1 ? null : $ticketedit['address_id'],
			'nodeid' => empty($ticketedit['nodeid']) ? null : $ticketedit['nodeid'],
		);
		$LMS->TicketChange($ticketedit['ticketid'], $props);

		// przy zmianie kolejki powiadamiamy o "nowym" zgloszeniu
		$newticket_notify = ConfigHelper::getConfig('phpui.newticket_notify', false);
		if ($ticket['queueid'] != $ticketedit['queueid']
			&& !empty($newticket_notify)) {
			$user = $LMS->GetUserInfo($AUTH->id);
			$queue = $LMS->GetQueueByTicketId($ticket['ticketid']);
			$mailfname = '';

			$helpdesk_sender_name = ConfigHelper::getConfig('phpui.helpdesk_sender_name');
			if (!empty($helpdesk_sender_name)) {
				if ($helpdesk_sender_name == 'queue')
					$mailfname = $queue['name'];
				elseif ($helpdesk_sender_name == 'user')
					$mailfname = $user['name'];

				$mailfname = '"' . $mailfname . '"';
			}

			$mailfrom = $user['email'] ? $user['email'] : $queue['email'];

			$ticketdata = $LMS->GetTicketContents($ticket['ticketid']);

			$params = array(
				'id' => $ticket['ticketid'],
				'customerid' => $ticketedit['customerid'],
				'status' => $ticketdata['status'],
				'categories' => $ticketdata['categorynames'],
				'subject' => $ticket['subject'],
				'body' => $ticket['messages'][0]['body'],
			);

			$headers['From'] = $mailfname . ' <' . $mailfrom . '>';
			$headers['Subject'] = $LMS->ReplaceNotificationSymbols(ConfigHelper::getConfig('phpui.helpdesk_notification_mail_subject'), $params);
			$headers['Reply-To'] = $headers['From'];

			$body = $LMS->ReplaceNotificationSymbols(ConfigHelper::getConfig('phpui.helpdesk_notification_mail_body'), $params);
			$sms_body = $LMS->ReplaceNotificationSymbols(ConfigHelper::getConfig('phpui.helpdesk_notification_sms_body'), $params);

			if (ConfigHelper::checkConfig('phpui.helpdesk_customerinfo') && $ticketedit['customerid']) {
				$info = $DB->GetRow('SELECT id, pin, '.$DB->Concat('UPPER(lastname)',"' '",'name').' AS customername,
							address, zip, city FROM customeraddressview WHERE id = ?', array($ticketedit['customerid']));
				$info['contacts'] = $DB->GetAll('SELECT contact, name, type FROM customercontacts
					WHERE customerid = ?', array($ticketedit['customerid']));

				$emails = array();
				$phones = array();
				if (!empty($info['contacts']))
					foreach ($info['contacts'] as $contact) {
						$target = $contact['contact'] . (strlen($contact['name']) ? ' (' . $contact['name'] . ')' : '');
						if ($contact['type'] & CONTACT_EMAIL)
							$emails[] = $target;
						else
							$phones[] = $target;
					}

				$params = array(
					'id' => $ticket['ticketid'],
					'customerid' => $ticketedit['customerid'],
					'customer' => $info,
					'emails' => $emails,
					'phones' => $phones,
				);

				$body .= "\n\n-- \n" . $LMS->ReplaceNotificationCustomerSymbols(ConfigHelper::getConfig('phpui.helpdesk_customerinfo_mail_body'), $params);

				$sms_body .= "\n" . $LMS->ReplaceNotificationCustomerSymbols(ConfigHelper::getConfig('phpui.helpdesk_customerinfo_sms_body'), $params);
			}

			// send email
			if($recipients = $DB->GetCol('SELECT DISTINCT email
			        FROM users, rtrights
					WHERE users.id=userid AND queueid = ? AND email != \'\'
						AND (rtrights.rights & 8) = 8 AND users.id != ?
						AND deleted = 0 AND (ntype & ?) = ?',
					array($ticketedit['queueid'], $AUTH->id, MSG_MAIL, MSG_MAIL))
			) {
				$oldrecipients = $DB->GetCol('SELECT DISTINCT email
				    FROM users, rtrights
					WHERE users.id=userid AND queueid = ? AND email != \'\'
						AND (rtrights.rights & 8) = 8 AND deleted = 0
						AND (ntype & ?) = ?',
					array($ticket['queueid'], MSG_MAIL, MSG_MAIL));

				foreach($recipients as $email) {
					if(in_array($email, (array)$oldrecipients)) continue;

					$headers['To'] = '<'.$email.'>';
					$LMS->SendMail($email, $headers, $body);
					echo '<pre>'; print_r($headers); echo '</pre>';
				}
			}

			// send sms
			$service = ConfigHelper::getConfig('sms.service');
			if (!empty($service) && ($recipients = $DB->GetCol('SELECT DISTINCT phone
			        FROM users, rtrights
					WHERE users.id = userid AND queueid = ? AND phone != \'\'
						AND (rtrights.rights & 8) = 8 AND users.id != ?
						AND deleted = 0 AND (ntype & ?) = ?',
					array($ticketedit['queueid'], $AUTH->id, MSG_SMS, MSG_SMS)))
			) {
				$oldrecipients = $DB->GetCol('SELECT DISTINCT phone
				    FROM users, rtrights
					WHERE users.id=userid AND queueid = ? AND phone != \'\'
						AND (rtrights.rights & 8) = 8 AND deleted = 0
						AND (ntype & ?) = ?',
					array($ticket['queueid'], MSG_SMS, MSG_SMS));

				foreach ($recipients as $phone) {
					if (in_array($phone, (array)$oldrecipients)) continue;

					$LMS->SendSMS($phone, $sms_body);
				}
			}
		}

		$SESSION->redirect('?m=rtticketview&id='.$id);
	}

	$ticket['subject'] = $ticketedit['subject'];
	$ticket['queueid'] = $ticketedit['queueid'];
	$ticket['state'] = $ticketedit['state'];
	$ticket['owner'] = $ticketedit['owner'];
	$ticket['address_id'] = $ticketedit['address_id'];
	$ticket['nodeid'] = $ticketedit['nodeid'];
}
else
	$ticketedit['categories'] = $ticket['categories'];

foreach ($categories as $category)
{
	$category['checked'] = isset($ticketedit['categories'][$category['id']]);
	$ncategories[] = $category;
}
$categories = $ncategories;

$layout['pagetitle'] = trans('Ticket Edit: $a',sprintf("%06d",$ticket['ticketid']));

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

if (!ConfigHelper::checkConfig('phpui.big_networks'))
	$SMARTY->assign('customerlist', $LMS->GetAllCustomerNames());

$queuelist = $LMS->GetQueueNames();
$userpanel_enabled_modules = ConfigHelper::getConfig('userpanel.enabled_modules');
if ((empty($userpanel_enabled_modules) || strpos('helpdesk', $userpanel_enabled_modules) !== false)
	&& ConfigHelper::getConfig('userpanel.limit_ticket_movements_to_selected_queues')) {
	$selectedqueues = explode(';', ConfigHelper::getConfig('userpanel.queues'));
	if (in_array($ticket['queueid'], $selectedqueues))
		foreach ($queuelist as $idx => $queue)
			if (!in_array($queue['id'], $selectedqueues))
				unset($queuelist[$idx]);
}

if (!empty($ticket['customerid']))
	$SMARTY->assign('nodes', $LMS->GetNodeLocations($ticket['customerid'],
		isset($ticket['address_id']) && intval($ticket['address_id']) > 0 ? $ticket['address_id'] : null));

$SMARTY->assign('ticket', $ticket);
$SMARTY->assign('queuelist', $queuelist);
$SMARTY->assign('categories', $categories);
$SMARTY->assign('userlist', $LMS->GetUserNames());
$SMARTY->assign('error', $error);
$SMARTY->display('rt/rtticketedit.html');

?>
