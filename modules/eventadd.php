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

include(MODULES_DIR . DIRECTORY_SEPARATOR . 'eventxajax.inc.php');

$event['helpdesk'] = ConfigHelper::checkConfig('phpui.default_event_ticket_assignment');

if (!empty($_GET['ticketid'])) {
	$eventticketid = intval($_GET['ticketid']);
	$tqname = $LMS->GetQueueNameByTicketId($eventticketid);
}

$userlist = $DB->GetAllByKey('SELECT id, rname FROM vusers
	WHERE deleted = 0 AND access = 1 ORDER BY lastname ASC', 'id');

if(isset($_POST['event']))
{
	$event = $_POST['event'];

	if (!empty($event['helpdesk']) && !count($event['categories']))
		$error['categories'] = trans('You have to select category!');

	if (!isset($event['usergroup']))
		$event['usergroup'] = 0;
	$SESSION->save('eventgid', $event['usergroup']);

	if (!($event['title'] || $event['description'] || $event['date']))
		$SESSION->redirect('?m=eventlist');

	if ($event['title'] == '')
		$error['title'] = trans('Event title is required!');
	elseif(strlen($event['title']) > 255)
		$error['title'] = trans('Event title is too long!');

	if ($event['date'] == '')
		$error['date'] = trans('You have to specify event day!');
	else {
		list ($year, $month, $day) = explode('/',$event['date']);
		if (checkdate($month, $day, $year))
			$date = mktime(0, 0, 0, $month, $day, $year);
		else
			$error['date'] = trans('Incorrect date format! Enter date in YYYY/MM/DD format!');
	}

	$enddate = 0;
	if ($event['enddate'] != '') {
		list ($year, $month, $day) = explode('/', $event['enddate']);
		if (checkdate($month, $day, $year))
			$enddate = mktime(0, 0, 0, $month, $day, $year);
		else
			$error['enddate'] = trans('Incorrect date format! Enter date in YYYY/MM/DD format!');
	}

	if ($enddate && $date > $enddate)
		$error['enddate'] = trans('End time must not precede start time!');

	if (ConfigHelper::checkConfig('phpui.event_overlap_warning')
		&& !$error && empty($event['overlapwarned']) && ($users = $LMS->EventOverlaps(array(
			'begindate' => $date,
			'begintime' => $event['begintime'],
			'enddate' => $enddate,
			'endtime' => $event['endtime'],
			'users' => $event['userlist'],
		)))) {
		$users = array_map(function($userid) use ($userlist) {
				return $userlist[$userid]['rname'];
			}, $users);
		$error['date'] = $error['enddate'] = $error['begintime'] = $error['endtime'] =
			trans('Event is assigned to users which already have assigned an event in the same time: $a!',
				implode(', ', $users));
		$event['overlapwarned'] = 1;
	}

	if (!isset($event['customerid']))
		$event['customerid'] = $event['custid'];

	$event['status'] = isset($event['status']) ? 1 : 0;

	if (!$error) {
		$event['address_id'] = !isset($event['address_id']) || $event['address_id'] == -1 ? null : $event['address_id'];
		$event['nodeid'] = !isset($event['nodeid']) || empty($event['nodeid']) ? null : $event['nodeid'];

		if (isset($event['helpdesk'])) {
			$ticket['queue'] = $event['rtqueue'];
			$ticket['customerid'] = $event['customerid'];
			$ticket['body'] = $event['description'];
			$ticket['requestor'] = $event['name']." ".$event['surname'];
			$ticket['subject'] = $event['title'];
			$ticket['mailfrom'] = $event['email'];
			$ticket['categories'] = $event['categories'];
			$ticket['owner'] = '0';
			$ticket['address_id'] = $event['address_id'];
			$ticket['nodeid'] = $event['nodeid'];

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

				if ($user['email'])
					$mailfrom = $user['email'];
				elseif ($qemail = $LMS->GetQueueEmail($ticket['queue']))
					$mailfrom = $qemail;
				else
					$mailfrom =  $ticket['mailfrom'];

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
		$event['enddate'] = $enddate;

		$LMS->EventAdd($event);

		if (!isset($event['reuse'])) {
			$backto = $SESSION->get('backto');
			if (isset($backto) && preg_match('/m=rtticketview/', $backto))
				$SESSION->redirect('?' . $backto);
			$SESSION->redirect('?m=eventlist');
		}

		unset($event['title']);
		unset($event['description']);
		unset($event['categories']);
	}
} else {
	$event['helpdesk'] = ConfigHelper::checkConfig('phpui.default_event_ticket_assignment');
	$event['overlapwarned'] = 0;
}

if (isset($event['helpdesk'])) {
	$categories = $LMS->GetCategoryListByUser(Auth::GetCurrentUser());
	$queuelist = $LMS->GetQueueListByUser(Auth::GetCurrentUser(),false);

	if (isset($_POST['event'])) {
		$ticket['queue'] = $event['rtqueue'];
		$ticket['surname'] = $event['surname'];
		$ticket['name'] = $event['name'];
		$ticket['email'] = $event['email'];

		foreach ($categories as &$category)
			$category['checked'] = isset($event['categories'][$category['id']]) || count($categories) == 1;
		unset($category);

		if (isset($event['customernotify']))
			$ticket['customernotify'] = 1;
	} else {
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
}

$event['date'] = isset($event['date']) ? $event['date'] : $SESSION->get('edate');

if (isset($_GET['customerid']))
	$event['customerid'] = intval($_GET['customerid']);
if (isset($event['customerid']) && !empty($event['customerid'])) {
	$event['customername'] = $LMS->GetCustomerName($event['customerid']);
	$SMARTY->assign('nodes', $LMS->GetNodeLocations($event['customerid'],
		isset($event['address_id']) && intval($event['address_id']) > 0 ? $event['address_id'] : null));
}

if(isset($_GET['day']) && isset($_GET['month']) && isset($_GET['year']))
{
	$event['date'] = sprintf('%04d/%02d/%02d', $_GET['year'], $_GET['month'], $_GET['day']);
}


$layout['pagetitle'] = trans('New Event');

if (!isset($_GET['ticketid']))
	$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$usergroups = $DB->GetAll('SELECT id, name FROM usergroups');

if (!isset($event['usergroup']))
	$SESSION->restore('eventgid', $event['usergroup']);

if (!ConfigHelper::checkConfig('phpui.big_networks'))
	$SMARTY->assign('customerlist', $LMS->GetAllCustomerNames());

if (isset($eventticketid))
	$event['ticketid'] = $eventticketid;

$SMARTY->assign('max_userlist_size', ConfigHelper::getConfig('phpui.event_max_userlist_size'));
$SMARTY->assign('userlist', $userlist);
$SMARTY->assign('tqname',$tqname);
$SMARTY->assign('usergroups', $usergroups);
$SMARTY->assign('error', $error);
$SMARTY->assign('event', $event);
$SMARTY->assign('hours',
		array(0,30,100,130,200,230,300,330,400,430,500,530,
		600,630,700,730,800,830,900,930,1000,1030,1100,1130,
		1200,1230,1300,1330,1400,1430,1500,1530,1600,1630,1700,1730,
		1800,1830,1900,1930,2000,2030,2100,2130,2200,2230,2300,2330
		));
$SMARTY->display('event/eventadd.html');

?>
