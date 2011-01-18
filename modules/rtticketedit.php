<?php

/*
 * LMS version 1.11-cvs
 *
 *  (C) Copyright 2001-2011 LMS Developers
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

if(($id = $_GET['id']) && !isset($_POST['ticket']))
{
	if(($LMS->GetUserRightsRT($AUTH->id, 0, $id) & 2) != 2)
	{
		$SMARTY->display('noaccess.html');
		$SESSION->close();
		die;
	}

	if(isset($_GET['state']) && $_GET['state'])
	{
		$LMS->SetTicketState($id, $_GET['state']);
		$SESSION->redirect('?m=rtticketview&id='.$id);
	}
}

$ticket = $LMS->GetTicketContents($id);

if(isset($_POST['ticket']))
{
	$ticketedit = $_POST['ticket'];
	$ticketedit['ticketid'] = $ticket['ticketid'];

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
		if($ticketedit['state'] == 2)
		{
			$DB->Execute('UPDATE rttickets SET queueid=?, subject=?, state=?, owner=?, customerid=?, cause=?, resolvetime=?NOW? 
					WHERE id=?', array($ticketedit['queueid'], 
						$ticketedit['subject'], 
						$ticketedit['state'], 
						$ticketedit['owner'], 
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
				$DB->Execute('UPDATE rttickets SET queueid=?, subject=?, state=?, owner=?, customerid=?, cause=?, resolvetime=0 
					WHERE id=?', array($ticketedit['queueid'], 
						$ticketedit['subject'], 
						$ticketedit['state'], 
						$ticketedit['owner'], 
						$ticketedit['customerid'], 
						$ticketedit['cause'], 
						$ticketedit['ticketid']
						));
			}
			else
			{
				$DB->Execute('UPDATE rttickets SET queueid=?, subject=?, state=?, owner=?, customerid=?, cause=? 
					WHERE id=?', array($ticketedit['queueid'], 
						$ticketedit['subject'], 
						$ticketedit['state'], 
						$ticketedit['owner'], 
						$ticketedit['customerid'], 
						$ticketedit['cause'], 
						$ticketedit['ticketid']
						));
			}
		}

		// przy zmianie kolejki powiadamiamy o "nowym" zgloszeniu
		if(	$ticket['queueid'] != $ticketedit['queueid']
			&& isset($CONFIG['phpui']['newticket_notify']) 
			&& chkconfig($CONFIG['phpui']['newticket_notify']))
		{
			$user = $LMS->GetUserInfo($AUTH->id);
			$queue = $LMS->GetQueueByTicketId($ticket['ticketid']);
			$mailfname = '';
			
			if(isset($CONFIG['phpui']['helpdesk_sender_name']))
			{
				if($CONFIG['phpui']['helpdesk_sender_name'] == 'queue')
				{
					$mailfname = $$queue['name'];
				}
				elseif($CONFIG['phpui']['helpdesk_sender_name'] == 'user')
				{
					$mailfname = $user['name'];
				}
				
				$mailfname = '"'.$mailfname.'"';
			}

			$mailfrom = $user['email'] ? $user['email'] : $queue['email'];

	        $headers['From'] = $mailfname.' <'.$mailfrom.'>';
			$headers['Subject'] = sprintf("[RT#%06d] %s", $ticket['ticketid'], $ticket['subject']);
			$headers['Reply-To'] = $headers['From'];

			$body = $ticket['messages'][0]['body'];

			$body .= "\n\nhttp".(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on' ? 's' : '').'://'
				.$_SERVER['HTTP_HOST'].substr($_SERVER['REQUEST_URI'], 0, strrpos($_SERVER['REQUEST_URI'], '/') + 1)
				.'?m=rtticketview&id='.$ticket['ticketid'];

			if(chkconfig($CONFIG['phpui']['helpdesk_customerinfo']) && $ticketedit['customerid'])
			{
				$info = $DB->GetRow('SELECT id, '.$DB->Concat('UPPER(lastname)',"' '",'name').' AS customername,
						email, address, zip, city, (SELECT phone FROM customercontacts 
							WHERE customerid = customers.id ORDER BY id LIMIT 1) AS phone
						FROM customers WHERE id = ?', array($ticketedit['customerid']));

				$body .= "\n\n-- \n";
				$body .= trans('Customer:').' '.$info['customername']."\n";
				$body .= trans('Address:').' '.$info['address'].', '.$info['zip'].' '.$info['city']."\n";
				$body .= trans('Phone:').' '.$info['phone']."\n";
				$body .= trans('E-mail:').' '.$info['email'];
			}

			if($recipients = $DB->GetCol('SELECT email FROM users, rtrights 
						WHERE users.id=userid AND queueid = ? AND email != \'\' 
							AND (rtrights.rights & 8) = 8 AND users.id != ?', 
							array($ticketedit['queueid'], $AUTH->id)))
			{
				$oldrecipients = $DB->GetCol('SELECT email FROM users, rtrights 
						WHERE users.id=userid AND queueid = ? AND email != \'\' 
							AND (rtrights.rights & 8) = 8', 
							array($ticket['queueid']));

				foreach($recipients as $email)
				{
					if(in_array($email, (array)$oldrecipients)) continue;

					if(!empty($CONFIG['mail']['debug_email']))
						$recip = $CONFIG['mail']['debug_email'];
					else
						$recip = $email;
					$headers['To'] = '<'.$recip.'>';
					$LMS->SendMail($recip, $headers, $body);
				}
			}
		}

		$SESSION->redirect('?m=rtticketview&id='.$id);
	}
	
	$ticket['subject'] = $ticketedit['subject'];
	$ticket['queueid'] = $ticketedit['queueid'];
	$ticket['state'] = $ticketedit['state'];
	$ticket['owner'] = $ticketedit['owner'];
}

$layout['pagetitle'] = trans('Ticket Edit: $0',sprintf("%06d",$ticket['ticketid']));

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

if(!isset($CONFIG['phpui']['big_networks']) || !chkconfig($CONFIG['phpui']['big_networks']))
{
        $SMARTY->assign('customerlist', $LMS->GetAllCustomerNames());
}

$SMARTY->assign('ticket', $ticket);
$SMARTY->assign('queuelist', $LMS->GetQueueNames());
$SMARTY->assign('userlist', $LMS->GetUserNames());
$SMARTY->assign('error', $error);
$SMARTY->display('rtticketedit.html');

?>
