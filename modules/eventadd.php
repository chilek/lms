<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2018 LMS Developers
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

$LMS->InitXajax();
include(MODULES_DIR . DIRECTORY_SEPARATOR . 'eventxajax.inc.php');
include(MODULES_DIR . DIRECTORY_SEPARATOR . 'rtticketxajax.inc.php');
$SMARTY->assign('xajax', $LMS->RunXajax());

if (isset($_GET['ticketid']) && !empty($_GET['ticketid']))
	$eventticketid = intval($_GET['ticketid']);

if (isset($_POST['helpdesk']) && isset($_POST['ticket']))
	$ticket = $_POST['ticket'];

$userlist = $LMS->GetUserList();
unset($userlist['total']);

if(isset($_POST['event']))
{
	$event = $_POST['event'];

	if (!isset($event['usergroup']))
		$event['usergroup'] = 0;
	$SESSION->save('eventgid', $event['usergroup']);

	if ($event['title'] == '')
		$error['title'] = trans('Event title is required!');
	elseif(strlen($event['title']) > 255)
		$error['title'] = trans('Event title is too long!');

	$date = 0;
	if ($event['begin'] == '')
		$error['begin'] = trans('You have to specify event day!');
	else {
		if (isset($event['wholedays'])) {
			$date = date_to_timestamp($event['begin']);
			if (empty($date))
				$error['begin'] = trans('Incorrect date format! Enter date in YYYY/MM/DD format!');
			else
				$begintime = 0;
		} else {
			$date = datetime_to_timestamp($event['begin'], $midnight = true);
			if (empty($date))
				$error['begin'] = trans('Incorrect date format! Enter date in YYYY/MM/DD HH:MM format!');
			else
				$begintime = datetime_to_timestamp($event['begin']) - $date;
		}
	}

	$enddate = 0;
	if ($event['end'] != '') {
		if (isset($event['wholedays'])) {
			$enddate = date_to_timestamp($event['end']);
			if (empty($enddate))
				$error['end'] = trans('Incorrect date format! Enter date in YYYY/MM/DD format!');
			else
				$endtime = 86400;
		} else {
			$enddate = datetime_to_timestamp($event['end'], $midnight = true);
			if (empty($enddate))
				$error['end'] = trans('Incorrect date format! Enter date in YYYY/MM/DD HH:MM format!');
			else
				$endtime = datetime_to_timestamp($event['end']) - $enddate;
		}
	} elseif ($date) {
		$enddate = $date;
		if (isset($event['wholedays']))
			$endtime = 86400;
		else
			$endtime = $begintime;
	}

	if ($enddate && $date > $enddate)
		$error['end'] = trans('End time must not precede start time!');

	if (ConfigHelper::checkConfig('phpui.event_overlap_warning')
		&& !$error && empty($event['overlapwarned']) && ($users = $LMS->EventOverlaps(array(
			'date' => $date,
			'begintime' => $begintime,
			'enddate' => $enddate,
			'endtime' => $endtime,
			'users' => $event['userlist'],
		)))) {
		$users = array_map(function($userid) use ($userlist) {
				return $userlist[$userid]['rname'];
			}, $users);
		$error['begin'] = $error['end'] =
			trans('Event is assigned to users which already have assigned an event in the same time: $a!',
				implode(', ', $users));
		$event['overlapwarned'] = 1;
	}

	if (!isset($event['customerid']))
		$event['customerid'] = $event['custid'];

	$event['private'] = isset($event['private']) ? 1 : 0;

    if (!empty($event['helpdesk']) && !count($ticket['categories']))
            $error['categories'] = trans('You have to select category!');

	if ($ticket['requestor_userid'] == '0') {
		if (empty($ticket['requestor_name']) && empty($ticket['requestor_mail']) && empty($ticket['requestor_phone']))
			$error['requestor_name'] = $error['requestor_mail'] = $error['requestor_phone'] =
				trans('At least requestor name, mail or phone should be filled!');
	}

	if (!$error) {
		$event['address_id'] = !isset($event['address_id']) || $event['address_id'] == -1 ? null : $event['address_id'];
		$event['nodeid'] = !isset($event['nodeid']) || empty($event['nodeid']) ? null : $event['nodeid'];

		if (isset($event['helpdesk'])) {

			$ticket['customerid'] = $event['customerid'];
			$ticket['body'] = $event['description'];
			$ticket['subject'] = $event['title'];
			$ticket['address_id'] = $event['address_id'];
			$ticket['nodeid'] = $event['nodeid'];

			if (!empty($ticket['requestor_userid'])) {
				$ticket['requestor'] = '';
				$ticket['requestor_mail'] = null;
				$ticket['requestor_phone'] = null;
			} else {
				$ticket['requestor_userid'] = null;
				$ticket['requestor'] = empty($ticket['requestor_name']) ? '' : $ticket['requestor_name'];
				$ticket['requestor_mail'] = empty($ticket['requestor_mail']) ? null : $ticket['requestor_mail'];
				$ticket['requestor_phone'] = empty($ticket['requestor_phone']) ? null : $ticket['requestor_phone'];
			}

			$event['ticketid'] = $LMS->TicketAdd($ticket);

			if (ConfigHelper::checkConfig('phpui.newticket_notify')) {
				$user = $LMS->GetUserInfo(Auth::GetCurrentUser());

				$helpdesk_sender_name = ConfigHelper::getConfig('phpui.helpdesk_sender_name');
				if (!empty($helpdesk_sender_name))
				{
					$mailfname = $helpdesk_sender_name;

					if($mailfname == 'queue') $mailfname = $LMS->GetQueueName($ticket['queue']);
					elseif($mailfname == 'user') $mailfname = $user['name'];
					$mailfname = '"'.$mailfname.'"';
				}
				else
					$mailfname = '';

				$mailfrom = $LMS->DetermineSenderEmail($user['email'], $LMS->GetQueueEmail($ticket['queue']), $ticket['mailfrom']);

				$ticketdata = $LMS->GetTicketContents($event['ticketid']);

				$headers['From'] = $mailfname.' <'.$mailfrom.'>';
				$headers['Reply-To'] = $headers['From'];
				$headers['Message-ID'] = $LMS->GetLastMessageID();

				$queuedata = $LMS->GetQueue($ticket['queue']);

				if ($ticket['customerid']) {
					$info = $LMS->GetCustomer($ticket['customerid'], true);

					$emails = array_map(function($contact) {
						return $contact['fullname'];
					}, $LMS->GetCustomerContacts($ticket['customerid'], CONTACT_EMAIL));
					$phones = array_map(function($contact) {
						return $contact['fullname'];
					}, $LMS->GetCustomerContacts($ticket['customerid'], CONTACT_LANDLINE | CONTACT_MOBILE));

					if (ConfigHelper::checkConfig('phpui.helpdesk_customerinfo')) {
						$params = array(
							'id' => $id,
							'customerid' => $ticket['customerid'],
							'customer' => $info,
							'emails' => $emails,
							'phones' => $phones,
						);
						$mail_customerinfo = $LMS->ReplaceNotificationCustomerSymbols(
							ConfigHelper::getConfig('phpui.helpdesk_customerinfo_mail_body'), $params);
						$sms_customerinfo = $LMS->ReplaceNotificationCustomerSymbols(
							ConfigHelper::getConfig('phpui.helpdesk_customerinfo_sms_body'), $params);
					}

					if (isset($event['customernotify']) && !empty($queuedata['newticketsubject']) && !empty($queuedata['newticketbody'])
						&& !empty($emails)) {
						$custmail_subject = $queuedata['newticketsubject'];
						$custmail_subject = str_replace('%tid', $id, $custmail_subject);
						$custmail_subject = str_replace('%title', $ticket['subject'], $custmail_subject);
						$custmail_body = $queuedata['newticketbody'];
						$custmail_body = str_replace('%tid', $id, $custmail_body);
						$custmail_body = str_replace('%cid', $ticket['customerid'], $custmail_body);
						$custmail_body = str_replace('%pin', $info['pin'], $custmail_body);
						$custmail_body = str_replace('%customername', $info['customername'], $custmail_body);
						$custmail_body = str_replace('%title', $ticket['subject'], $custmail_body);
						$custmail_headers = array(
							'From' => $headers['From'],
							'Reply-To' => $headers['From'],
							'Subject' => $custmail_subject,
						);
						foreach ($emails as $email) {
							$custmail_headers['To'] = '<' . $info['email'] . '>';
							$LMS->SendMail($email, $custmail_headers, $custmail_body);
						}
					}
				} elseif (!empty($requestor) && ConfigHelper::checkConfig('phpui.helpdesk_customerinfo')) {
					$mail_customerinfo = "\n\n-- \n" . trans('Customer:') . ' ' . $requestor;
					$sms_customerinfo = "\n" . trans('Customer:') . ' ' . $requestor;
				}

				$params = array(
					'id' => $event['ticketid'],
					'queue' => $queuedata['name'],
					'customerid' => $ticket['customerid'],
					'status' => $ticketdata['status'],
					'categories' => $ticketdata['categorynames'],
					'priority' => $RT_PRIORITIES[$ticketdata['priority']],
					'subject' => $ticket['subject'],
					'body' => $ticket['body'],
				);
				$headers['Subject'] = $LMS->ReplaceNotificationSymbols(ConfigHelper::getConfig('phpui.helpdesk_notification_mail_subject'), $params);
				$params['customerinfo'] = isset($mail_customerinfo) ? $mail_customerinfo : null;
				$body = $LMS->ReplaceNotificationSymbols(ConfigHelper::getConfig('phpui.helpdesk_notification_mail_body'), $params);
				$params['customerinfo'] = isset($sms_customerinfo) ? $sms_customerinfo : null;
				$sms_body = $LMS->ReplaceNotificationSymbols(ConfigHelper::getConfig('phpui.helpdesk_notification_sms_body'), $params);

				$LMS->NotifyUsers(array(
					'queue' => $queuedata['name'],
					'mail_headers' => $headers,
					'mail_body' => $body,
					'sms_body' => $sms_body,
				));
			}
		}

		$event['date'] = $date;
		$event['begintime'] = $begintime;
		$event['enddate'] = $enddate;
		$event['endtime'] = $endtime;

		$LMS->EventAdd($event);

		if (!isset($event['reuse'])) {
			$backto = $SESSION->get('backto');
			if (isset($backto) && preg_match('/m=rtticketview/', $backto))
				$SESSION->redirect('?' . $backto);
			else
				$SESSION->redirect('?m=eventlist');
		}

		unset($event['title']);
		unset($event['description']);
		unset($event['categories']);
	}
} else {
	if (isset($_GET['id']) && intval($_GET['id'])) {
		$event = $LMS->GetEvent($_GET['id']);

		if (empty($event['enddate']))
			$event['enddate'] = $event['date'];
		$event['begin'] = date('Y/m/d H:i', $event['date'] + $event['begintime']);
		$event['end'] = date('Y/m/d H:i', $event['enddate'] + ($event['endtime'] == 86400 ? 0 : $event['endtime']));

		$event['ticketid'] = 0;
	} else {
		$event['overlapwarned'] = 0;
		$event['wholedays'] = false;
		$event['date'] = isset($event['date']) ? $event['date'] : $SESSION->get('edate');
	}
	if (!isset($eventticketid))
		$event['helpdesk'] = ConfigHelper::checkConfig('phpui.default_event_ticket_assignment');
}

if (isset($event['helpdesk'])) {
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

    $invprojectlist = $LMS->GetProjects('name', array());

	$categories = $LMS->GetUserCategories(Auth::GetCurrentUser());
	$queuelist = $LMS->GetQueueList(array('stats' => false));

	if (isset($_POST['event'])) {

		foreach ($categories as &$category)
			$category['checked'] = isset($event['categories'][$category['id']]) || count($categories) == 1;
		unset($category);

		if (isset($event['customernotify']))
			$ticket['customernotify'] = 1;
	} else {
		if (isset($eventticketid)) {
			$ticket = $LMS->GetTicketContents($eventticketid);
			$event['address_id'] = $ticket['address_id'];
			$event['nodeid'] = $ticket['nodeid'];
		} else
			$ticket = array();

		if (!empty($queuelist)) {
			$firstqueue = reset($queuelist);
			$queue = $firstqueue['id'];
			if ($firstqueue['newticketsubject'] && $firstqueue['newticketbody'])
				$ticket['customernotify'] = 1;

			$queuecategories = $LMS->GetQueueCategories($queue);
			foreach ($categories as &$category)
				if (isset($queuecategories[$category['id']]) || count($categories) == 1)
					$category['checked'] = 1;
			unset($category);
		}
	}

	$SMARTY->assign('queuelist', $queuelist);
	$SMARTY->assign('categories', $categories);
	$SMARTY->assign('ticket', $ticket);
} elseif (isset($eventticketid)) {
	$event['ticketid'] = $eventticketid;
}

if (isset($_GET['customerid']))
	$event['customerid'] = intval($_GET['customerid']);
if (isset($event['customerid']) && !empty($event['customerid'])) {
	$event['customername'] = $LMS->GetCustomerName($event['customerid']);
	$SMARTY->assign('nodes', $LMS->GetNodeLocations($event['customerid'],
		isset($event['address_id']) && intval($event['address_id']) > 0 ? $event['address_id'] : null));
}

if (isset($_GET['day']) && isset($_GET['month']) && isset($_GET['year']))
	$event['begin'] = date('Y/m/d H:i', mktime(0, 0, 0, $_GET['month'], $_GET['day'], $_GET['year']));

$layout['pagetitle'] = trans('New Event');

if (!isset($_GET['ticketid']))
	$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$usergroups = $DB->GetAll('SELECT id, name FROM usergroups');

if (!isset($event['usergroup']))
	$SESSION->restore('eventgid', $event['usergroup']);

if (!ConfigHelper::checkConfig('phpui.big_networks'))
	$SMARTY->assign('customerlist', $LMS->GetAllCustomerNames());

$SMARTY->assign('max_userlist_size', ConfigHelper::getConfig('phpui.event_max_userlist_size'));
$SMARTY->assign('userlist', $userlist);
$SMARTY->assign('usergroups', $usergroups);
$SMARTY->assign('error', $error);
$SMARTY->assign('event', $event);
$SMARTY->assign('netnodelist', $netnodelist);
$SMARTY->assign('netdevlist', $netdevlist);
$SMARTY->assign('invprojectlist', $invprojectlist);
$SMARTY->display('event/eventmodify.html');

?>
