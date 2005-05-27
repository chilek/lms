<?php

/*
 * LMS version 1.6-cvs
 *
 *  (C) Copyright 2001-2005 LMS Developers
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

$ticket = $_POST['ticket'];
$queue = $_GET['id'];

if(isset($ticket))
{
	$queue = $ticket['queue'];

	if($ticket['subject']=='' && $ticket['body']=='')
	{
		$SESSION->redirect('?m=rtticketadd&id='.$queue);
	}

	if($LMS->GetAdminRightsRT($AUTH->id, $queue) < 2)
		$error['queue'] = trans('You have no privileges to this queue!');

	if($ticket['subject'] == '')
		$error['subject'] = trans('Ticket must have its title!');

	if($ticket['body'] == '')
		$error['body'] = trans('Ticket must have its body!');

	if($ticket['email']!='' && !check_email($ticket['email']))
		$error['email'] = trans('Incorrect email!');

	if($ticket['userid'] !=0 && $ticket['custid']!=$ticket['userid'])
		$error['custid'] = trans('Specified ID is not proper or does not exist!');

	if($ticket['surname']=='' && $ticket['userid']==0)
		$error['surname'] = trans('Requester name required!');

	$requestor  = ($ticket['surname'] ? $ticket['surname'].' ' : '');
	$requestor .= ($ticket['name'] ? $ticket['name'].' ' : '');	    
	$requestor .= ($ticket['email'] ? '<'.$ticket['email'].'>' : '');
	$ticket['requestor'] = trim($requestor);
	
	$ticket['mailfrom'] = $ticket['email'] ? $ticket['email'] : '';

	if(!$error)
	{
		$id = $LMS->TicketAdd($ticket);
		
		if($LMS->CONFIG['phpui']['newticket_notify'])
		{
			$admin = $LMS->GetAdminInfo($AUTH->id);

			if($mailfname = $LMS->CONFIG['phpui']['helpdesk_sender_name'])
			{
				if($mailfname == 'queue') $mailfname = $LMS->GetQueueName($queue);
				if($mailfname == 'user') $mailfname = $admin['name'];
				$mailfname = '"'.$mailfname.'"';
			}

			if ($admin['email'])
				$mailfrom = $admin['email'];
			elseif ($qemail = $LMS->GetQueueEmail($queue))
				$mailfrom = $qemail;
			else
				$mailfrom =  $ticket['mailfrom'];
				
			$headers['Date'] = date('D, d F Y H:i:s T');
		        $headers['From'] = $mailfname.' <'.$mailfrom.'>';
			$headers['Subject'] = sprintf("[RT#%06d] %s", $id, $ticket['subject']);
			$headers['Reply-To'] = $headers['From'];

			$body = $ticket['body']."\n\nhttp".($_SERVER['HTTPS'] == 'on' ? 's' : '').'://'
				.$_SERVER['HTTP_HOST'].substr($_SERVER['REQUEST_URI'], 0, strrpos($_SERVER['REQUEST_URI'], '/') + 1)
				.'?m=rtticketview&id='.$id;

			if($recipients = $LMS->DB->GetCol('SELECT email FROM admins, rtrights WHERE admins.id=adminid AND queueid=? AND email!=\'\' AND rtrights.rights>2',array($queue)))
				foreach($recipients as $email)
				{
					if($LMS->CONFIG['phpui']['debug_email'])
						$recip = $LMS->CONFIG['phpui']['debug_email'];
					else
						$recip = $email;
					$headers['To'] = '<'.$recip.'>';
		        
					$LMS->SendMail($recip, $headers, $body);
				}
		}
		
		$SESSION->redirect('?m=rtticketview&id='.$id);
	}
}
	
$layout['pagetitle'] = trans('New Ticket');

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$SMARTY->assign('ticket', $ticket);
$SMARTY->assign('queue', $queue);
$SMARTY->assign('queuelist', $LMS->GetQueueNames());
$SMARTY->assign('userid', isset($ticket['userid']) ? $ticket['userid'] : $_GET['userid']);
$SMARTY->assign('userlist', $LMS->GetUserNames());
$SMARTY->assign('error', $error);
$SMARTY->display('rtticketadd.html');

?>
