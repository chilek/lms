<?php

/*
 *  LMS version 1.11-cvs
 *
 *  (C) Copyright 2001-2009 LMS Developers
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

if (defined('USERPANEL_SETUPMODE'))
{
    function module_setup()
    {
	global $SMARTY, $LMS;
	
	$SMARTY->assign('userlist', $LMS->GetUserNames());
	$SMARTY->assign('queuelist', $LMS->GetQueueNames());
	$SMARTY->assign('default_queue', $LMS->CONFIG['userpanel']['default_queue']);
        $SMARTY->assign('default_userid', $LMS->CONFIG['userpanel']['default_userid']);
	$SMARTY->assign('debug_email', $LMS->CONFIG['userpanel']['debug_email']);
        $SMARTY->assign('lms_url', $LMS->CONFIG['userpanel']['lms_url']);
	$SMARTY->display('module:helpdesk:setup.html');
    }
	    
    function module_submit_setup()
    {
	global $DB;
        $DB->Execute('UPDATE uiconfig SET value = ? WHERE section = \'userpanel\' AND var = \'default_queue\'',array($_POST['default_queue']));
	$DB->Execute('UPDATE uiconfig SET value = ? WHERE section = \'userpanel\' AND var = \'default_userid\'',array($_POST['default_userid']));
        $DB->Execute('UPDATE uiconfig SET value = ? WHERE section = \'userpanel\' AND var = \'debug_email\'',array($_POST['debug_email']));
	$DB->Execute('UPDATE uiconfig SET value = ? WHERE section = \'userpanel\' AND var = \'lms_url\'',array($_POST['lms_url']));
        header('Location: ?m=userpanel&module=helpdesk');
    }
}
			    
function module_main()
{
    global $SMARTY,$LMS,$SESSION,$CONFIG,$DB;

    $error = NULL;

    if(isset($_POST['helpdesk']) && empty($_GET['id']))
    {
        $ticket = $_POST['helpdesk'];

	$ticket['queue'] = $CONFIG['userpanel']['default_queue'];
	$ticket['subject'] = strip_tags($ticket['subject']);
	$ticket['body'] = strip_tags($ticket['body']);

	if(!$ticket['queue'])
	{
		header('Location: ?m=helpdesk');
		die;
	}

	if($ticket['subject']=='' && $ticket['body']=='')
	{
		header('Location: ?m=helpdesk');
		die;
	}

	if($ticket['subject'] == '')
		$error['subject'] = trans('Ticket must have its title!');

	if($ticket['body'] == '')
		$error['body'] = trans('Ticket must have its body!');

	if(!$error)
	{
		$ticket['email'] = $LMS->GetCustomerEmail($SESSION->id);
		$ticket['mailfrom'] = $ticket['email'] ? $ticket['email'] : '';
		$ts = time();
		
		$DB->Execute('INSERT INTO rttickets (queueid, customerid, subject, createtime)
				VALUES (?, ?, ?, ?)',
				array($ticket['queue'],
				        $SESSION->id,
					$ticket['subject'],
					$ts
				));
		
		$id = $DB->GetLastInsertID('rttickets');
		
		$DB->Execute('INSERT INTO rtmessages (ticketid, customerid, createtime, subject, body, mailfrom)
		                VALUES (?, ?, ?, ?, ?, ?)', 
				array($id, 
					$SESSION->id, 
					$ts, 
					$ticket['subject'], 
					$ticket['body'], 
					$ticket['mailfrom']
				));
		
		if(isset($CONFIG['phpui']['newticket_notify']) && chkconfig($CONFIG['phpui']['newticket_notify']))
		{
			$user = $LMS->GetUserInfo($CONFIG['userpanel']['default_userid']);

			if($mailfname = $CONFIG['phpui']['helpdesk_sender_name'])
			{
				if($mailfname == 'queue') $mailfname = $LMS->GetQueueName($ticket['queue']);
				if($mailfname == 'user') $mailfname = $user['name'];
				$mailfname = '"'.$mailfname.'"';
			}

			if ($user['email'])
				$mailfrom = $user['email'];
			elseif ($qemail = $LMS->GetQueueEmail($ticket['queue']))
				$mailfrom = $qemail;
			else
				$mailfrom =  $ticket['mailfrom'];
				
			$headers['Date'] = date('r');
		        $headers['From'] = $mailfname.' <'.$mailfrom.'>';
			$headers['Subject'] = sprintf("[RT#%06d] %s", $id, $ticket['subject']);
			$headers['Reply-To'] = $headers['From'];

			$body = $ticket['body']."\n\n".$CONFIG['userpanel']['lms_url'].'/?m=rtticketview&id='.$id;

			$recipients_query = 'SELECT email FROM users, rtrights WHERE users.id=userid AND email!=\'\' AND (rtrights.rights & 8) = 8 AND queueid='.$ticket['queue'];

			if($recipients = $DB->GetCol($recipients_query))
				foreach($recipients as $email)
				{
					if($CONFIG['userpanel']['debug_email'])
						$recip = $CONFIG['userpanel']['debug_email'];
					else
						$recip = $email;
					$headers['To'] = '<'.$recip.'>';
		        
					$LMS->SendMail($recip, $headers, $body);
				}
		}
		
		header('Location: ?m=helpdesk');
		die;
	}
	else
	{
	        $SMARTY->assign('error', $error);
		$SMARTY->assign('helpdesk', $ticket);
	}
    }
    elseif(isset($_POST['helpdesk']) && !empty($_GET['id']))
    {
        $ticket = $_POST['helpdesk'];

	$ticket['body'] = strip_tags($ticket['body']);
	$ticket['subject'] = strip_tags($ticket['subject']);
	$ticket['inreplyto'] = intval($ticket['inreplyto']);
	$ticket['id'] = intval($_GET['id']);

	if($ticket['subject'] == '')
		$error['subject'] = trans('Ticket must have its title!');

	if($ticket['body'] == '')
		$error['body'] = trans('Ticket must have its body!');

	if(!$DB->GetOne('SELECT 1 FROM rttickets WHERE id = ? AND customerid = ?',
			array($ticket['id'], $SESSION->id))) 
		$error = true;

	if(!$error)
	{
		$ticket['customerid'] = $SESSION->id;

		// add message
		$DB->Execute('INSERT INTO rtmessages (ticketid, createtime, subject, body, customerid, inreplyto)
                        VALUES (?, ?NOW?, ?, ?, ?, ?)',
		        array(
		                $ticket['id'],
		                $ticket['subject'],
		                $ticket['body'],
		                $ticket['customerid'],
		            	$ticket['inreplyto'],
		        ));
	
		// re-open ticket
		$DB->Execute('UPDATE rttickets SET state = CASE state
				WHEN 0 THEN 0
				WHEN 1 THEN 1
				WHEN 2 THEN 1
				WHEN 3 THEN 1 END 
			WHERE id = ?', array($ticket['id']));

		header('Location: ?m=helpdesk&op=view&id='.$ticket['id']);
		die;
	}
	else
	{
	        $SMARTY->assign('error', $error);
		$SMARTY->assign('helpdesk', $ticket);
		$_GET['op'] = 'message';
	}
    }

    if(isset($_GET['op']) && $_GET['op'] == 'view')
    {
	if(ereg('^[0-9]+$',$_GET['id']))
		$ticket = $LMS->GetTicketContents($_GET['id']);

	$ticket['id'] = $_GET['id'];

	$SMARTY->assign('title', trans('Request No. $0', sprintf('%06d',$ticket['ticketid'])));
	
	if($ticket['customerid'] == $SESSION->id)
	{
	        $SMARTY->assign('ticket', $ticket);
	        $SMARTY->display('module:helpdeskview.html');
	        die;
	}								
    }
    elseif(isset($_GET['op']) && $_GET['op'] == 'message')
    {
        if(ereg('^[0-9]+$',$_GET['id']))
		$ticket = $LMS->GetTicketContents($_GET['id']);
	
	$ticket['id'] = $_GET['id'];
	
	if(isset($_GET['msgid']) && ereg('^[0-9]+$', $_GET['msgid']))
	{
	        $helpdesk['subject'] = $DB->GetOne('SELECT subject FROM rtmessages
	                	WHERE ticketid = ? AND id = ?', array($ticket['id'], $_GET['msgid']));
	
		$helpdesk['subject'] = preg_replace('/^Re:\s*/', '', $helpdesk['subject']);
		$helpdesk['subject'] = 'Re: '. $helpdesk['subject'];
	        $SMARTY->assign('helpdesk', $helpdesk);
	}

        $SMARTY->assign('title', trans('Request No. $0', sprintf('%06d',$ticket['ticketid'])));
        if($ticket['customerid'] == $SESSION->id)
        {
        	$SMARTY->assign('ticket', $ticket);
	        $SMARTY->display('module:helpdeskreply.html');
		die;
	}
    }

    if($helpdesklist = $LMS->GetCustomerTickets($SESSION->id))
	foreach($helpdesklist as $idx => $key)
	    $helpdesklist[$idx]['lastmod'] = $LMS->DB->GetOne('SELECT MAX(createtime) FROM rtmessages WHERE ticketid = ?', array($key['id']));

    $SMARTY->assign('helpdesklist', $helpdesklist);
    $SMARTY->display('module:helpdesk.html');
}

?>
