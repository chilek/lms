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

	if($ticket['email']!='' && !check_email($ticket['email']))
		$error['email'] = trans('Incorrect email!');

	if ((isset($ticket['customerid']) && $ticket['customerid'] !=0 && $ticket['custid'] != $ticket['customerid'])
		|| (intval($ticket['custid']) && !$LMS->CustomerExists($ticket['custid'])))
		$error['custid'] = trans('Specified ID is not proper or does not exist!');
	else
		$ticket['customerid'] = $ticket['custid'] ? $ticket['custid'] : 0;

	$result = handle_file_uploads('files', $error);
	extract($result);
	$SMARTY->assign('fileupload', $fileupload);

	if (!$error) {
		if (!$ticket['customerid'] && $ticket['surname'] == '') {
			$userinfo = $LMS->GetUserInfo(Auth::GetCurrentUser());
			$ticket['surname'] = $userinfo['lastname'];
			$ticket['name'] = $userinfo['firstname'];
		}

		$ticket['mailfrom'] = $ticket['email'] ? $ticket['email'] : '';

		$requestor  = ($ticket['surname'] ? $ticket['surname'].' ' : '');
		$requestor .= ($ticket['name'] ? $ticket['name'].' ' : '');
		$requestor .= ($ticket['email'] ? '<'.$ticket['email'].'>' : '');
		$ticket['requestor'] = trim($requestor);

		if ($ticket['address_id'] == -1)
			$ticket['address_id'] = null;

		if (empty($ticket['nodeid']))
			$ticket['nodeid'] = null;

		foreach ($files as &$file)
			$file['name'] = $tmppath . DIRECTORY_SEPARATOR . $file['name'];
		unset($file);
		$id = $LMS->TicketAdd($ticket, $files);

		if (ConfigHelper::checkConfig('phpui.newticket_notify')) {
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
			foreach ($ticketdata[categories] as $tcat)
					$tcatname = $tcatname . $tcat['name'] .' ; ';

			$helpdesk_notification_mail_subject = ConfigHelper::getConfig('phpui.helpdesk_notification_mail_subject');
			$helpdesk_notification_mail_subject = str_replace('%tid', sprintf("%06d",$id), $helpdesk_notification_mail_subject);
			$helpdesk_notification_mail_subject = str_replace('%cid', sprintf("%04d",$ticket['customerid']), $helpdesk_notification_mail_subject);
			$helpdesk_notification_mail_subject = str_replace('%status', $ticketdata['status'], $helpdesk_notification_mail_subject);
			$helpdesk_notification_mail_subject = str_replace('%cat', $tcatname, $helpdesk_notification_mail_subject);

			$headers['From'] = $mailfname.' <'.$mailfrom.'>';
			$headers['Subject'] = $helpdesk_notification_mail_subject .' # '.$ticket['subject'];
			$headers['Reply-To'] = $headers['From'];
			$headers['Message-ID'] = $LMS->GetLastMessageID();

			$helpdesk_notification_mail_body = ConfigHelper::getConfig('phpui.helpdesk_notification_mail_body');
			$helpdesk_notification_mail_body = str_replace('%tid', sprintf("%06d",$id), $helpdesk_notification_mail_body);
			$helpdesk_notification_mail_body = str_replace('%cid', sprintf("%04d",$ticket['customerid']), $helpdesk_notification_mail_body);
			$helpdesk_notification_mail_body = str_replace('%status', $ticketdata['status'], $helpdesk_notification_mail_body);
			$helpdesk_notification_mail_body = str_replace('%cat', $tcatname, $helpdesk_notification_mail_body);
			$url = 'http'
					.(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on' ? 's' : '').'://'
							.$_SERVER['HTTP_HOST']
							.substr($_SERVER['REQUEST_URI'], 0, strrpos($_SERVER['REQUEST_URI'], '/') + 1)
							.'?m=rtticketview&id='.$id;
			$helpdesk_notification_mail_body = str_replace('%url', $url, $helpdesk_notification_mail_body);

			$body = $helpdesk_notification_mail_body ."\n\n".$ticket['body'];

			$helpdesk_notification_sms_body = ConfigHelper::getConfig('phpui.helpdesk_notification_sms_body');
			$helpdesk_notification_sms_body = str_replace('%tid', sprintf("%06d",$id), $helpdesk_notification_sms_body);
			$helpdesk_notification_sms_body = str_replace('%cid', sprintf("%04d",$ticket['customerid']), $helpdesk_notification_sms_body);
			$helpdesk_notification_sms_body = str_replace('%status', $ticketdata['status'], $helpdesk_notification_sms_body);
			$helpdesk_notification_sms_body = str_replace('%cat', $tcatname, $helpdesk_notification_sms_body);

			$sms_body = $helpdesk_notification_sms_body;

			if ($ticket['customerid']) {
				$info = $DB->GetRow('SELECT id, pin, '.$DB->Concat('UPPER(lastname)',"' '",'name').' AS customername,
						address, zip, city FROM customeraddressview
						WHERE id = ?', array($ticket['customerid']));

				$info['contacts'] = $DB->GetAll('SELECT contact, name, type FROM customercontacts
					WHERE customerid = ?', array($ticket['customerid']));

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
					$locations = $LMS->getCustomerAddresses($ticket['customerid']);
					$body .= "\n\n-- \n";
					$body .= trans('Customer:').' '.$info['customername']."\n";
					$body .= trans('ID:').' '.sprintf('%04d', $ticket['customerid'])."\n";
					$body .= trans('Address:') . ' ' . (empty($ticket['address_id']) ? $info['address'] . ', ' . $info['zip'] . ' ' . $info['city']
						: $locations[$ticket['address_id']]['location']) . "\n";

					if (!empty($phones))
						$body .= trans('Phone:').' ' . implode(', ', $phones) . "\n";
					if (!empty($emails))
						$body .= trans('E-mail:') . ' ' . implode(', ', $emails);
				}

				$queuedata = $LMS->GetQueue($queue);
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

				if (ConfigHelper::checkConfig('phpui.helpdesk_customerinfo')) {
					$sms_body .= "\n";
					$sms_body .= trans('Customer:').' '.$info['customername'];
					$sms_body .= ' '.sprintf('(%04d)', $ticket['customerid']).'. ';
					$sms_body .= (empty($ticket['address_id']) ? $info['address'] . ', ' . $info['zip'] . ' ' . $info['city']
						: $locations[$ticket['address_id']]['location']);
					if (!empty($phones))
						$sms_body .= '. ' . trans('Phone:') . ' ' . preg_replace('/([0-9])[\s-]+([0-9])/', '\1\2', implode(',', $phones));
				}
			} elseif (!empty($requestor) && ConfigHelper::checkConfig('phpui.helpdesk_customerinfo')) {
				$body .= "\n\n-- \n";
				$body .= trans('Customer:').' '.$requestor;
				$sms_body .= "\n";
				$sms_body .= trans('Customer:').' '.$requestor;
			}

			$notify_author = ConfigHelper::checkConfig('phpui.helpdesk_author_notify');
			$args = array(
				'queue' => $queue,
				'user' => Auth::GetCurrentUser(),
			);
			if ($notify_author)
				unset($args['user']);

			// send email
			$args['type'] = MSG_MAIL;
			if ($recipients = $DB->GetCol('SELECT DISTINCT email
				FROM users, rtrights
					WHERE users.id = userid AND queueid = ? AND email != \'\'
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
			$service = ConfigHelper::getConfig('sms.service');
			$args['type'] = MSG_SMS;
			if (!empty($service) && ($recipients = $DB->GetCol('SELECT DISTINCT phone
				FROM users, rtrights
					WHERE users.id = userid AND queueid = ? AND phone != \'\'
						AND (rtrights.rights & 8) = 8 AND deleted = 0'
						. ($notify_author ? '' : ' AND users.id <> ?')
						. ' AND (ntype & ?) > 0',
					array_values($args))))
			{
				foreach ($recipients as $phone) {
					$LMS->SendSMS($phone, $sms_body);
				}
			}
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

$SMARTY->assign('ticket', $ticket);
$SMARTY->assign('queue', $queue);
$SMARTY->assign('queuelist', $queuelist);
$SMARTY->assign('categories', $categories);
$SMARTY->assign('customerid', $ticket['customerid']);
$SMARTY->assign('userlist', $LMS->GetUserNames());
$SMARTY->display('rt/rtticketadd.html');

?>
