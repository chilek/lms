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

$queue = isset($_GET['id']) ? intval($_GET['id']) : 0;
$ticket['customerid'] = isset($_GET['customerid']) ? intval($_GET['customerid']) : 0;
$ticket['netdevid'] = isset($_GET['netdevid']) ? intval($_GET['netdevid']) : 0;
$ticket['netnodeid'] = isset($_GET['netnodeid']) ? intval($_GET['netnodeid']) : 0;

$categories = $LMS->GetCategoryListByUser(Auth::GetCurrentUser());
if (!$categories) {
	$SMARTY->display('noaccess.html');
	$SESSION->close();
	die;
}

if(isset($_POST['ticket']))
{
	$ticket = $_POST['ticket'];
	$queue = $ticket['queue'];

	if($ticket['subject']=='' && $ticket['body']=='' && !$ticket['custid'])
	{
		$SESSION->redirect('?m=rtticketadd&id='.$queue);
	}

	if(empty($ticket['categories']))
		$error['categories'] = trans('You have to select category!');

	if(($LMS->GetUserRightsRT(Auth::GetCurrentUser(), $queue) & 2) != 2)
		$error['queue'] = trans('You have no privileges to this queue!');

	if($ticket['subject'] == '')
		$error['subject'] = trans('Ticket must have its title!');

	if($ticket['body'] == '')
		$error['body'] = trans('Ticket must have its body!');

	if($ticket['mail']!='' && !check_email($ticket['mail']))
		$error['mail'] = trans('Incorrect email!');

	if(!empty($ticket['requestor_phone']))
		if(strlen($ticket['requestor_phone']) > 32 )
			$error['phone'] = trans('Specified phone number is not correct!');

	if ((isset($ticket['customerid']) && $ticket['customerid'] !=0 && $ticket['custid'] != $ticket['customerid'])
		|| (intval($ticket['custid']) && !$LMS->CustomerExists($ticket['custid'])))
		$error['custid'] = trans('Specified ID is not proper or does not exist!');
	else {
		$ticket['customerid'] = $ticket['custid'] ? $ticket['custid'] : 0;
		if ($ticket['customerid'] && $ticket['address_id'] <= 0) {
			$addresses = $LMS->getCustomerAddresses($ticket['customerid']);
			if (count($addresses) > 2 && !$_POST['address_id_warning']) {
				$error['address_id'] = trans('No address has been selected!');
				$SMARTY->assign('address_id_warning', 1);
			}
		}
	}

	$result = handle_file_uploads('files', $error);
	extract($result);
	$SMARTY->assign('fileupload', $fileupload);

	if (!$error) {
		if (!$ticket['customerid']) {
			if ($ticket['surname'] == '' && $ticket['phone'] == '' && $ticket['mail'] == '')
				$userinfo = $LMS->GetUserInfo(Auth::GetCurrentUser());
				$ticket['requestor_userid'] = $userinfo['id'];
		}

		$ticket['mailfrom'] = $ticket['mail'] ? $ticket['mail'] : '';

		$requestor  = ($ticket['surname'] ? $ticket['surname'].' ' : '');
		$requestor .= ($ticket['name'] ? $ticket['name'].' ' : '');
		$ticket['requestor'] = trim($requestor);

		if ($ticket['address_id'] == -1)
			$ticket['address_id'] = null;

		if (empty($ticket['nodeid']))
			$ticket['nodeid'] = null;

		if (empty($ticket['netnodeid']))
			$ticket['netnodeid'] = null;

		if (empty($ticket['netdevid']))
			$ticket['netdevid'] = null;

		if (empty($ticket['requestor_mail']))
			$ticket['requestor_mail'] = null;

		if (empty($ticket['requestor_phone']))
			$ticket['requestor_phone'] = null;

		if (empty($ticket['requestor_userid']))
			$ticket['requestor_userid'] = null;

		if (empty($ticket['verifierid']))
			$ticket['verifierid'] = null;

		if (empty($ticket['deadlinetime']))
			$ticket['deadlinetime'] = null;
		else
			$ticket['deadlinetime'] = datetime_to_timestamp($ticket['deadlinetime']);

		if (!empty($files)) {
			foreach ($files as &$file)
				$file['name'] = $tmppath . DIRECTORY_SEPARATOR . $file['name'];
			unset($file);
		}
		$id = $LMS->TicketAdd($ticket, $files);

		// deletes uploaded files
		if (!empty($files))
			rrmdir($tmppath);

		if (isset($ticket['notify']) && ConfigHelper::checkConfig('phpui.newticket_notify')) {
			$user = $LMS->GetUserInfo(Auth::GetCurrentUser());

			$helpdesk_sender_name = ConfigHelper::getConfig('phpui.helpdesk_sender_name');
			if (!empty($helpdesk_sender_name))
			{
				$mailfname = $helpdesk_sender_name;

				if($mailfname == 'queue') $mailfname = $LMS->GetQueueName($queue);
				elseif($mailfname == 'user') $mailfname = $user['name'];
				$mailfname = '"'.$mailfname.'"';
			}
			else
				$mailfname = '';

			if ($user['email'])
				$mailfrom = $user['email'];
			elseif ($qemail = $LMS->GetQueueEmail($queue))
				$mailfrom = $qemail;
			else
				$mailfrom =  $ticket['mailfrom'];

			$ticketdata = $LMS->GetTicketContents($id);

			$headers['From'] = $mailfname.' <'.$mailfrom.'>';
			$headers['Reply-To'] = $headers['From'];
			$headers['Message-ID'] = $LMS->GetLastMessageID();

			$queuedata = $LMS->GetQueue($queue);

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

				if (isset($ticket['customernotify']) && !empty($queuedata['newticketsubject']) && !empty($queuedata['newticketbody'])
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
				'id' => $id,
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
				'queue' => $queue,
				'mail_headers' => $headers,
				'mail_body' => $body,
				'sms_body' => $sms_body,
			));
		}

		$SESSION->redirect('?m=rtticketview&id='.$id);
	}
	$SMARTY->assign('error', $error);

	$queuelist = $LMS->GetQueueList(false);

	foreach ($categories as &$category)
		$category['checked'] = isset($ticket['categories'][$category['id']]) || count($categories) == 1;
	unset($category);
} else {
	$queuelist = $LMS->GetQueueList(false);
	if (!$queue && !empty($queuelist)) {
		$firstqueue = reset($queuelist);
		$queue = $firstqueue['id'];
		if ($firstqueue['newticketsubject'] && $firstqueue['newticketbody'])
			$ticket['customernotify'] = 1;
	} elseif ($queue) {
		$queuedata = $LMS->GetQueue($queue);
		if ($queuedata['newticketsubject'] && $queuedata['newticketbody'])
			$ticket['customernotify'] = 1;
	}

	$queuecategories = $LMS->GetQueueCategories($queue);
	foreach ($categories as &$category)
		if (isset($queuecategories[$category['id']]) || count($categories) == 1
			// handle category id got from welcome module so this category will be selected
			|| (isset($_GET['catid']) && $category['id'] == intval($_GET['catid'])))
			$category['checked'] = 1;
	unset($category);

	if (ConfigHelper::checkConfig('phpui.helpdesk_notify'))
		$ticket['notify'] = TRUE;
}

$layout['pagetitle'] = trans('New Ticket');

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

if (!ConfigHelper::checkConfig('phpui.big_networks'))
	$SMARTY->assign('customerlist', $LMS->GetAllCustomerNames());

if (isset($ticket['customerid']) && intval($ticket['customerid'])) {
	$SMARTY->assign('nodes', $LMS->GetNodeLocations($ticket['customerid'],
		isset($ticket['address_id']) && intval($ticket['address_id']) > 0 ? $ticket['address_id'] : null));
	$SMARTY->assign('customerinfo', $LMS->GetCustomer($ticket['customerid']));
}

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
$SMARTY->assign('queue', $queue);
$SMARTY->assign('queuelist', $queuelist);
$SMARTY->assign('categories', $categories);
$SMARTY->assign('netnodelist', $netnodelist);
$SMARTY->assign('netdevlist', $netdevlist);
$SMARTY->assign('customerid', $ticket['customerid']);
$SMARTY->assign('userlist', $LMS->GetUserNames());
$SMARTY->display('rt/rtticketadd.html');

?>
