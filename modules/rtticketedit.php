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
	if(($LMS->GetUserRightsRT(Auth::GetCurrentUser(), 0, $id) & 2) != 2
		|| !$LMS->GetUserRightsToCategory(Auth::GetCurrentUser(), 0, $id))
	{
		$SMARTY->display('noaccess.html');
		$SESSION->close();
		die;
	}

	if (isset($_GET['state']) && $_GET['state']) {
		$state = intval($_GET['state']);
		$LMS->TicketChange($id, array('state' => $state));

		$queue = $LMS->GetQueueByTicketId($id);
		$user = $LMS->GetUserInfo(Auth::GetCurrentUser());
		$ticket = $LMS->GetTicketContents($id);
		if ($ticket['customerid']) {
			$info = $LMS->GetCustomer($ticket['customerid'], true);

			$emails = array_map(function($contact) {
				return $contact['fullname'];
			}, $LMS->GetCustomerContacts($ticket['customerid'], CONTACT_EMAIL));
			$phones = array_map(function($contact) {
				return $contact['fullname'];
			}, $LMS->GetCustomerContacts($ticket['customerid'], CONTACT_LANDLINE | CONTACT_MOBILE));
		}

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
		$from = $mailfname . ' <' . $mailfrom . '>';

		if ($state == RT_RESOLVED) {
			if (!empty($queue['resolveticketsubject']) && !empty($queue['resolveticketbody'])) {
				if (!empty($ticket['customerid'])) {
					if (!empty($emails)) {
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

		$headers['From'] = $from;
		$headers['Reply-To'] = $headers['From'];

		if (ConfigHelper::checkConfig('phpui.helpdesk_customerinfo')) {
			if ($ticket['customerid']) {
				$params = array(
					'id' => $id,
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

		$message = end($ticket['messages']);
		$message['body'] = str_replace('<br>', "\n", $message['body']);

		$params = array(
			'id' => $id,
			'queue' => $queue['name'],
			'customerid' => $ticket['customerid'],
			'status' => $ticket['status'],
			'categories' => $ticket['categorynames'],
			'priority' => $RT_PRIORITIES[$ticket['priority']],
			'subject' => $ticket['subject'],
			'body' => $message['body'],
		);
		$headers['Subject'] = $LMS->ReplaceNotificationSymbols(ConfigHelper::getConfig('phpui.helpdesk_notification_mail_subject'), $params);
		$params['customerinfo'] =  isset($mail_customerinfo) ? $mail_customerinfo : null;
		$body = $LMS->ReplaceNotificationSymbols(ConfigHelper::getConfig('phpui.helpdesk_notification_mail_body'), $params);
		$params['customerinfo'] =  isset($sms_customerinfo) ? $sms_customerinfo : null;
		$sms_body = $LMS->ReplaceNotificationSymbols(ConfigHelper::getConfig('phpui.helpdesk_notification_sms_body'), $params);

		$LMS->NotifyUsers(array(
			'queue' => $ticket['queueid'],
			'mail_headers' => $headers,
			'mail_body' => $body,
			'sms_body' => $sms_body,
		));

		$SESSION->redirect('?m=rtticketview&id='.$id);
	}

	if (isset($_GET['assign'])) {
		$LMS->TicketChange($id, array('owner' => Auth::GetCurrentUser()));
		$SESSION->redirect('?m=rtticketview&id=' . $id);
	}
}

$ticket = $LMS->GetTicketContents($id);
$categories = $LMS->GetCategoryListByUser(Auth::GetCurrentUser());
if (empty($categories))
	$categories = array();

if(isset($_POST['ticket']))
{
	$ticketedit = $_POST['ticket'];
	$ticketedit['ticketid'] = $ticket['ticketid'];

	if(!count($ticketedit['categories']))
		$error['categories'] = trans('You have to select category!');

	if(($LMS->GetUserRightsRT(Auth::GetCurrentUser(), $ticketedit['queueid']) & 2) != 2)
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
			'owner' => empty($ticketedit['owner']) ? null : $ticketedit['owner'],
			'cause' => $ticketedit['cause'],
			'state' => $ticketedit['state'],
			'subject' => $ticketedit['subject'],
			'customerid' => $ticketedit['customerid'],
			'categories' => isset($ticketedit['categories']) ? array_keys($ticketedit['categories']) : array(),
			'source' => $ticketedit['source'],
			'priority' => $ticketedit['priority'],
			'address_id' => $ticketedit['address_id'] == -1 ? null : $ticketedit['address_id'],
			'nodeid' => empty($ticketedit['nodeid']) ? null : $ticketedit['nodeid'],
			'netnodeid' => empty($ticketedit['netnodeid']) ? null : $ticketedit['netnodeid'],
			'netdevid' => empty($ticketedit['netdevid']) ? null : $ticketedit['netdevid'],
		);
		$LMS->TicketChange($ticketedit['ticketid'], $props);

		// przy zmianie kolejki powiadamiamy o "nowym" zgloszeniu
		$newticket_notify = ConfigHelper::getConfig('phpui.newticket_notify', false);
		if (($ticket['state'] != $ticketedit['state']) || ($ticket['queueid'] != $ticketedit['queueid']
			&& !empty($newticket_notify))) {
			$user = $LMS->GetUserInfo(Auth::GetCurrentUser());
			$queue = $LMS->GetQueueByTicketId($ticket['ticketid']);
			$mailfname = '';

			$helpdesk_sender_name = ConfigHelper::getConfig('phpui.helpdesk_sender_name');
			if (!empty($helpdesk_sender_name)) {
				if ($helpdesk_sender_name == 'queue')
					$mailfname = $queue['name'];
				elseif ($helpdesk_sender_name == 'user')
					$mailfname = $user['name'];
				else
					$mailfname = $helpdesk_sender_name;

				$mailfname = '"' . $mailfname . '"';
			}

			$mailfrom = $user['email'] ? $user['email'] : $queue['email'];

			$ticketdata = $LMS->GetTicketContents($ticket['ticketid']);

			$headers['From'] = $mailfname . ' <' . $mailfrom . '>';
			$headers['Reply-To'] = $headers['From'];

			if (ConfigHelper::checkConfig('phpui.helpdesk_customerinfo')) {
				if ($ticketedit['customerid']) {
					$info = $LMS->GetCustomer($ticketedit['customerid'], true);

					$emails = array_map(function($contact) {
							return $contact['fullname'];
						}, $LMS->GetCustomerContacts($ticketedit['customerid'], CONTACT_EMAIL));
					$phones = array_map(function($contact) {
							return $contact['fullname'];
						}, $LMS->GetCustomerContacts($ticketedit['customerid'], CONTACT_LANDLINE | CONTACT_MOBILE));

					$params = array(
						'id' => $ticket['ticketid'],
						'customerid' => $ticketedit['customerid'],
						'customer' => $info,
						'emails' => $emails,
						'phones' => $phones,
					);
					$mail_customerinfo = $LMS->ReplaceNotificationCustomerSymbols(ConfigHelper::getConfig('phpui.helpdesk_customerinfo_mail_body'), $params);
					$sms_customerinfo = $LMS->ReplaceNotificationCustomerSymbols(ConfigHelper::getConfig('phpui.helpdesk_customerinfo_sms_body'), $params);
				} else {
					$mail_customerinfo = "\n\n-- \n" . trans('Customer:') . ' ' . $ticketdata['requestor'];
					$sms_customerinfo = "\n" . trans('Customer:') . ' ' . $ticketdata['requestor'];
				}
			}

			if ($ticket['queueid'] == $ticketedit['queueid']) {
				$ticket = $LMS->GetTicketContents($id);
				$message = end($ticket['messages']);
				$message['body'] = str_replace('<br>', "\n", $message['body']);
			} else
				$message = reset($ticket['messages']);

			$params = array(
				'id' => $ticket['ticketid'],
				'queue' => $queue['name'],
				'customerid' => $ticketedit['customerid'],
				'status' => $ticketdata['status'],
				'categories' => $ticketdata['categorynames'],
				'priority' => $RT_PRIORITIES[$ticketdata['priority']],
				'subject' => $ticket['subject'],
				'body' => $message['body'],
			);
			$headers['Subject'] = $LMS->ReplaceNotificationSymbols(ConfigHelper::getConfig('phpui.helpdesk_notification_mail_subject'), $params);
			$params['customerinfo'] =  isset($mail_customerinfo) ? $mail_customerinfo : null;
			$body = $LMS->ReplaceNotificationSymbols(ConfigHelper::getConfig('phpui.helpdesk_notification_mail_body'), $params);
			$params['customerinfo'] =  isset($sms_customerinfo) ? $sms_customerinfo : null;
			$sms_body = $LMS->ReplaceNotificationSymbols(ConfigHelper::getConfig('phpui.helpdesk_notification_sms_body'), $params);

			$LMS->NotifyUsers(array(
				'queue' => $ticketedit['queueid'],
				'oldqueue' => $ticket['queueid'] == $ticketedit['queueid'] ? null : $ticket['queueid'],
				'mail_headers' => $headers,
				'mail_body' => $body,
				'sms_body' => $sms_body,
			));
		}

		$backto = $SESSION->get('backto');
		if (empty($backto))
			$SESSION->redirect('?m=rtticketview&id='.$id);
		else
			$SESSION->redirect('?' . $backto);
	}

	$ticket['subject'] = $ticketedit['subject'];
	$ticket['queueid'] = $ticketedit['queueid'];
	$ticket['state'] = $ticketedit['state'];
	$ticket['owner'] = $ticketedit['owner'];
	$ticket['address_id'] = $ticketedit['address_id'];
	$ticket['nodeid'] = $ticketedit['nodeid'];
	$ticket['netnodeid'] = $ticketedit['netnodeid'];
	$ticket['netdevid'] = $ticketedit['netdevid'];
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

$netnodelist = $LMS->GetNetNodeList(array(), 'name');
unset($netnodelist['total']);
unset($netnodelist['order']);
unset($netnodelist['direction']);

if (isset($ticket['netnodeid']) && !empty($ticket['netnodeid']))
	$search = array('netnode' => $ticket['netnodeid']);
else
	$search = array();
$netdevlist = $LMS->GetNetDevList('name', $search);
unset($netdevlist['total']);
unset($netdevlist['order']);
unset($netdevlist['direction']);

$SMARTY->assign('ticket', $ticket);
$SMARTY->assign('queuelist', $queuelist);
$SMARTY->assign('categories', $categories);
$SMARTY->assign('netnodelist', $netnodelist);
$SMARTY->assign('netdevlist', $netdevlist);
$SMARTY->assign('userlist', $LMS->GetUserNames());
$SMARTY->assign('error', $error);
$SMARTY->display('rt/rtticketedit.html');

?>
