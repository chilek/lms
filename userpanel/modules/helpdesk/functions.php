<?php

/*
 *  LMS version 1.11-git
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

if (defined('USERPANEL_SETUPMODE'))
{
    function module_setup()
    {
	global $SMARTY, $LMS, $AUTH;

	$default_categories = explode(',', $LMS->CONFIG['userpanel']['default_categories']);
	$categories = $LMS->GetCategoryListByUser($AUTH->id);
	foreach($categories as $category)
	{
		if (in_array($category['id'], $default_categories))
			$category['checked'] = true;
		$ncategories[] = $category;
	}
	$categories = $ncategories;

	$SMARTY->assign('userlist', $LMS->GetUserNames());
	$SMARTY->assign('queuelist', $LMS->GetQueueNames());
	$SMARTY->assign('default_queue', $LMS->CONFIG['userpanel']['default_queue']);
        $SMARTY->assign('default_userid', $LMS->CONFIG['userpanel']['default_userid']);
        $SMARTY->assign('lms_url', $LMS->CONFIG['userpanel']['lms_url']);
        $SMARTY->assign('categories', $categories);
	$SMARTY->display('module:helpdesk:setup.html');
    }

    function module_submit_setup()
    {
	global $DB;
        $DB->Execute('UPDATE uiconfig SET value = ? WHERE section = \'userpanel\' AND var = \'default_queue\'',array($_POST['default_queue']));
	$DB->Execute('UPDATE uiconfig SET value = ? WHERE section = \'userpanel\' AND var = \'default_userid\'',array($_POST['default_userid']));
	$DB->Execute('UPDATE uiconfig SET value = ? WHERE section = \'userpanel\' AND var = \'lms_url\'',array($_POST['lms_url']));
	$categories = array_keys((isset($_POST['lms_categories']) ? $_POST['lms_categories'] : array()));
	$DB->Execute('UPDATE uiconfig SET value = ? WHERE section = \'userpanel\' AND var = \'default_categories\'', array(implode(',', $categories)));
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
	$ticket['categories'] = $CONFIG['userpanel']['default_categories'];
	$ticket['subject'] = strip_tags($ticket['subject']);
	$ticket['body'] = strip_tags($ticket['body']);

	if(!$ticket['queue'] || !$ticket['categories'])
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

		foreach(explode(',', $ticket['categories']) as $catid)
			$DB->Execute('INSERT INTO rtticketcategories (ticketid, categoryid) VALUES (?, ?)',
				array($id, $catid));

		if(check_conf('phpui.newticket_notify'))
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

            $sms_body = $headers['Subject']."\n".$ticket['body'];
			$body = $ticket['body']."\n\n".$CONFIG['userpanel']['lms_url'].'/?m=rtticketview&id='.$id;

            if (check_conf('phpui.helpdesk_customerinfo')) {
                $info = $DB->GetRow('SELECT id AS customerid, '.$DB->Concat('UPPER(lastname)',"' '",'name').' AS customername,
                        email, address, zip, city, (SELECT phone FROM customercontacts
                            WHERE customerid = customers.id ORDER BY id LIMIT 1) AS phone
                        FROM customers WHERE id = ?', array($SESSION->id));

                $body .= "\n\n-- \n";
                $body .= trans('Customer:').' '.$info['customername']."\n";
                $body .= trans('ID:').' '.sprintf('%04d', $info['customerid'])."\n";
                $body .= trans('Address:').' '.$info['address'].', '.$info['zip'].' '.$info['city']."\n";
                $body .= trans('Phone:').' '.$info['phone']."\n";
                $body .= trans('E-mail:').' '.$info['email'];

                $sms_body .= "\n";
                $sms_body .= trans('Customer:').' '.$info['customername'];
                $sms_body .= ' '.sprintf('(%04d)', $ticket['customerid']).'. ';
                $sms_body .= $info['address'].', '.$info['zip'].' '.$info['city'].'. ';
                $sms_body .= $info['phone'];
            }

            // send email
			if ($recipients = $DB->GetCol('SELECT DISTINCT email
			    FROM users, rtrights
			    WHERE users.id = userid AND email != \'\' AND (rtrights.rights & 8) = 8
			        AND (ntype & ?) = ? AND queueid = ?',
			    array(MSG_MAIL, MSG_MAIL, intval($ticket['queue'])))
			) {
				foreach($recipients as $email) {
					$headers['To'] = '<'.$email.'>';

					$LMS->SendMail($email, $headers, $body);
				}
            }

            // send sms
			if (!empty($CONFIG['sms']['service']) && ($recipients = $DB->GetCol('SELECT DISTINCT phone
			    FROM users, rtrights
			    WHERE users.id = userid AND phone != \'\' AND (rtrights.rights & 8) = 8
			        AND (ntype & ?) = ? AND queueid = ?',
			    array(MSG_SMS, MSG_SMS, intval($ticket['queue']))))
			) {
				foreach($recipients as $phone) {
					$LMS->SendSMS($phone, $sms_body);
				}
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
	if(preg_match('/^[0-9]+$/',$_GET['id']))
		$ticket = $LMS->GetTicketContents($_GET['id']);

	$ticket['id'] = $_GET['id'];

	$SMARTY->assign('title', trans('Request No. $a', sprintf('%06d',$ticket['ticketid'])));
	
	if($ticket['customerid'] == $SESSION->id)
	{
	        $SMARTY->assign('ticket', $ticket);
	        $SMARTY->display('module:helpdeskview.html');
	        die;
	}								
    }
    elseif(isset($_GET['op']) && $_GET['op'] == 'message')
    {
        if(preg_match('/^[0-9]+$/',$_GET['id']))
		$ticket = $LMS->GetTicketContents($_GET['id']);
	
	$ticket['id'] = $_GET['id'];
	
	if(isset($_GET['msgid']) && preg_match('/^[0-9]+$/', $_GET['msgid']))
	{
	        $helpdesk['subject'] = $DB->GetOne('SELECT subject FROM rtmessages
	                	WHERE ticketid = ? AND id = ?', array($ticket['id'], $_GET['msgid']));
	
		$helpdesk['subject'] = preg_replace('/^Re:\s*/', '', $helpdesk['subject']);
		$helpdesk['subject'] = 'Re: '. $helpdesk['subject'];
	        $SMARTY->assign('helpdesk', $helpdesk);
	}

        $SMARTY->assign('title', trans('Request No. $a', sprintf('%06d',$ticket['ticketid'])));
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
