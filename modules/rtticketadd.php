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

$queue = isset($_GET['id']) ? intval($_GET['id']) : 0;
$ticket['customerid'] = isset($_GET['customerid']) ? intval($_GET['customerid']) : 0;

if(isset($_POST['ticket']))
{
	$ticket = $_POST['ticket'];
	$queue = $ticket['queue'];

	if($ticket['subject']=='' && $ticket['body']=='' && !$ticket['custid'])
	{
		$SESSION->redirect('?m=rtticketadd&id='.$queue);
	}

	if(empty($ticket['categories']))
		$error = true;

	if(($LMS->GetUserRightsRT($AUTH->id, $queue) & 2) != 2)
		$error['queue'] = trans('You have no privileges to this queue!');

	if($ticket['subject'] == '')
		$error['subject'] = trans('Ticket must have its title!');

	if($ticket['body'] == '')
		$error['body'] = trans('Ticket must have its body!');

	if($ticket['email']!='' && !check_email($ticket['email']))
		$error['email'] = trans('Incorrect email!');

	if(isset($ticket['customerid']) && $ticket['customerid'] !=0 && $ticket['custid']!=$ticket['customerid'])
		$error['custid'] = trans('Specified ID is not proper or does not exist!');
	else
		$ticket['customerid'] = $ticket['custid'] ? $ticket['custid'] : 0;

	if($ticket['surname']=='' && $ticket['customerid']==0)
		$error['surname'] = trans('Requester name required!');

	$requestor  = ($ticket['surname'] ? $ticket['surname'].' ' : '');
	$requestor .= ($ticket['name'] ? $ticket['name'].' ' : '');
	$requestor .= ($ticket['email'] ? '<'.$ticket['email'].'>' : '');
	$ticket['requestor'] = trim($requestor);
	
	$ticket['mailfrom'] = $ticket['email'] ? $ticket['email'] : '';

	if (!$error)
	{
		$id = $LMS->TicketAdd($ticket);

		if (isset($CONFIG['phpui']['newticket_notify']) && chkconfig($CONFIG['phpui']['newticket_notify']))
		{
			$user = $LMS->GetUserInfo($AUTH->id);

			if (!empty($CONFIG['phpui']['helpdesk_sender_name']))
			{
				$mailfname = $CONFIG['phpui']['helpdesk_sender_name'];

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

			$headers['From'] = $mailfname.' <'.$mailfrom.'>';
			$headers['Subject'] = sprintf("[RT#%06d] %s", $id, $ticket['subject']);
			$headers['Reply-To'] = $headers['From'];

			$sms_body = $headers['Subject']."\n".$ticket['body'];
			$body = $ticket['body']."\n\nhttp"
				.(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on' ? 's' : '').'://'
				.$_SERVER['HTTP_HOST']
				.substr($_SERVER['REQUEST_URI'], 0, strrpos($_SERVER['REQUEST_URI'], '/') + 1)
				.'?m=rtticketview&id='.$id;

			if (chkconfig($CONFIG['phpui']['helpdesk_customerinfo']))
				if ($ticket['customerid'])
				{
					$info = $DB->GetRow('SELECT '.$DB->Concat('UPPER(lastname)',"' '",'name').' AS customername,
							email, address, zip, city, (SELECT phone FROM customercontacts 
								WHERE customerid = customers.id ORDER BY id LIMIT 1) AS phone
							FROM customers WHERE id = ?', array($ticket['customerid']));

					$body .= "\n\n-- \n";
						$body .= trans('Customer:').' '.$info['customername']."\n";
						$body .= trans('ID:').' '.sprintf('%04d', $ticket['customerid'])."\n";
						$body .= trans('Address:').' '.$info['address'].', '.$info['zip'].' '.$info['city']."\n";
						$body .= trans('Phone:').' '.$info['phone']."\n";
						$body .= trans('E-mail:').' '.$info['email'];

					$sms_body .= "\n";
					$sms_body .= trans('Customer:').' '.$info['customername'];
					$sms_body .= ' '.sprintf('(%04d)', $ticket['customerid']).'. ';
					$sms_body .= $info['address'].', '.$info['zip'].' '.$info['city'];
					if ($info['phone'])
						$sms_body .= '. '.trans('Phone:').' '.$info['phone'];
				}
				elseif (!empty($requestor))
				{
					$body .= "\n\n-- \n";
					$body .= trans('Customer:').' '.$requestor;
					$sms_body .= "\n";
					$sms_body .= trans('Customer:').' '.$requestor;
				}

			// send email
			if ($recipients = $DB->GetCol('SELECT DISTINCT email
				FROM users, rtrights
					WHERE users.id = userid AND queueid = ? AND email != \'\'
						AND (rtrights.rights & 8) = 8 AND deleted = 0
						AND (ntype & ?) = ?',
					array($queue, MSG_MAIL, MSG_MAIL)))
			{
				foreach($recipients as $email) {
					$headers['To'] = '<'.$email.'>';

					$LMS->SendMail($email, $headers, $body);
				}
			}

			// send sms
			if (!empty($CONFIG['sms']['service']) && ($recipients = $DB->GetCol('SELECT DISTINCT phone
				FROM users, rtrights
					WHERE users.id = userid AND queueid = ? AND phone != \'\'
						AND (rtrights.rights & 8) = 8 AND deleted = 0
						AND (ntype & ?) = ?',
					array($queue, MSG_SMS, MSG_SMS))))
			{
				foreach ($recipients as $phone) {
					$LMS->SendSMS($phone, $sms_body);
				}
			}
		}

		$SESSION->redirect('?m=rtticketview&id='.$id);
	}
	$SMARTY->assign('ticket', $ticket);
	$SMARTY->assign('error', $error);
}

$categories = $LMS->GetCategoryListByUser($AUTH->id);

if (!$categories) {
    $SMARTY->display('noaccess.html');
    $SESSION->close();
    die;
}

// handle category id got from welcome module so this category will be selected
if (isset($_GET['catid']) && intval($_GET['catid']))
	$ticket['categories'][intval($_GET['catid'])] = true;

if ($categories) foreach ($categories as $category)
{
	$category['checked'] = isset($ticket['categories'][$category['id']]) || count($categories) == 1;
	$ncategories[] = $category;
}
$categories = $ncategories;

$layout['pagetitle'] = trans('New Ticket');

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

if(!isset($CONFIG['phpui']['big_networks']) || !chkconfig($CONFIG['phpui']['big_networks']))
{
	$SMARTY->assign('customerlist', $LMS->GetAllCustomerNames());
}

if(isset($ticket['customerid']) && $ticket['customerid'])
{
	$SMARTY->assign('customerinfo', $LMS->GetCustomer($ticket['customerid']));
}

$SMARTY->assign('queue', $queue);
$SMARTY->assign('queuelist', $LMS->GetQueueNames());
$SMARTY->assign('categories', $categories);
$SMARTY->assign('customerid', $ticket['customerid']);
$SMARTY->display('rtticketadd.html');

?>
