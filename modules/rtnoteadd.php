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

if(isset($_GET['ticketid']))
{
	$note['ticketid'] = intval($_GET['ticketid']);

	if(($LMS->GetUserRightsRT($AUTH->id, 0, $note['ticketid']) & 2) != 2)
        {
	        $SMARTY->display('noaccess.html');
	        $SESSION->close();
	        die;
	}

	$note = $DB->GetRow('SELECT id AS ticketid, state, cause FROM rttickets WHERE id = ?', array($note['ticketid']));
}
elseif(isset($_POST['note']))
{
	$note = $_POST['note'];

	if($note['body'] == '')
		$error['body'] = trans('Note body not specified!');

	if(!isset($note['ticketid']) || !intval($note['ticketid']))
	{
		$SESSION->redirect('?m=rtqueuelist');
	}

	if(!$error)
	{
		$DB->Execute('INSERT INTO rtnotes (userid, ticketid, body, createtime)
			    VALUES(?, ?, ?, ?NOW?)',
			    array($AUTH->id, $note['ticketid'], $note['body']));

		$LMS->SetTicketState($note['ticketid'], $note['state']);

		$DB->Execute('UPDATE rttickets SET cause = ? WHERE id = ?', array($note['cause'], $note['ticketid']));

		if(isset($note['notify']))
		{
			$user = $LMS->GetUserInfo($AUTH->id);
			$queue = $LMS->GetQueueByTicketId($note['ticketid']);
			$mailfname = '';

			if(!empty($CONFIG['phpui']['helpdesk_sender_name']))
			{
				$mailfname = $CONFIG['phpui']['helpdesk_sender_name'];

				if($mailfname == 'queue')
					$mailfname = $queue['name'];
				elseif($mailfname == 'user')
					$mailfname = $user['name'];

				$mailfname = '"'.$mailfname.'"';
			}

			$mailfrom = $user['email'] ? $user['email'] : $queue['email'];

	        $headers['From'] = $mailfname.' <'.$mailfrom.'>';
			$headers['Subject'] = sprintf("[RT#%06d] %s", $note['ticketid'], $DB->GetOne('SELECT subject FROM rttickets WHERE id = ?', array($note['ticketid'])));
			$headers['Reply-To'] = $headers['From'];

            $sms_body = $headers['Subject']."\n".$note['body'];
			$body = $note['body']."\n\nhttp"
				.(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on' ? 's' : '').'://'
				.$_SERVER['HTTP_HOST']
				.substr($_SERVER['REQUEST_URI'], 0, strrpos($_SERVER['REQUEST_URI'], '/') + 1)
				.'?m=rtticketview&id='.$note['ticketid'];

			if(chkconfig($CONFIG['phpui']['helpdesk_customerinfo']) 
				&& ($cid = $DB->GetOne('SELECT customerid FROM rttickets WHERE id = ?', array($note['ticketid']))))
			{
				$info = $DB->GetRow('SELECT id, '.$DB->Concat('UPPER(lastname)',"' '",'name').' AS customername,
						email, address, zip, city, (SELECT phone FROM customercontacts 
							WHERE customerid = customers.id ORDER BY id LIMIT 1) AS phone
						FROM customers WHERE id = ?', array($cid));

				$body .= "\n\n-- \n";
				$body .= trans('Customer:').' '.$info['customername']."\n";
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

            // send email
			if($recipients = $DB->GetCol('SELECT DISTINCT email
			        FROM users, rtrights
					WHERE users.id=userid AND queueid = ? AND email != \'\'
						AND (rtrights.rights & 8) = 8 AND users.id != ?
						AND deleted = 0 AND (ntype & ?) = ?',
					array($queue['id'], $AUTH->id, MSG_MAIL, MSG_MAIL))
			) {
				foreach ($recipients as $email) {
					$headers['To'] = '<'.$email.'>';

					$LMS->SendMail($email, $headers, $body);
				}
			}

            // send sms
			if (!empty($CONFIG['sms']['service']) && ($recipients = $DB->GetCol('SELECT DISTINCT phone
			        FROM users, rtrights
					WHERE users.id=userid AND queueid = ? AND phone != \'\'
						AND (rtrights.rights & 8) = 8 AND users.id != ?
						AND deleted = 0 AND (ntype & ?) = ?',
					array($queue['id'], $AUTH->id, MSG_SMS, MSG_SMS)))
			) {
				foreach ($recipients as $phone) {
					$LMS->SendSMS($phone, $sms_body);
				}
			}
		}

		$SESSION->redirect('?m=rtticketview&id='.$note['ticketid']);
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
$SMARTY->assign('error', $error);
$SMARTY->display('rtnoteadd.html');

?>
