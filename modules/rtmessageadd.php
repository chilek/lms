<?php

/*
 * LMS version 1.3-cvs
 *
 *  (C) Copyright 2001-2004 LMS Developers
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

$message = $_POST['message'];

if(isset($message))
{
	if($message['subject'] == '')
		$error['subject'] = "Wiadomo¶æ musi mieæ tytu³!";

	if($message['body'] == '')
		$error['body'] = "Nie poda³e¶ tre¶ci wiadomo¶ci!";

	if($message['destination']!='' && !check_email($message['destination']))
		$error['destination'] = 'Podany email nie wydaje siê byæ poprawny!';

	if($message['destination']!='' && $message['sender']=='user')
		$error['destination'] = 'U¿ytkownik nie mo¿e wysy³aæ wiadomo¶ci!';


	if(!$error)
	{
		$queue = $LMS->GetQueueByTicketId($message['ticketid']);
		$admin = $LMS->GetAdminInfo($SESSION->id);
		
		$message['messageid'] = '<msg.'.$message['ticketid'].'.'.$queue['id'].'.'.time().'@rtsystem.'.gethostbyaddr(gethostbyname($_SERVER['SERVER_NAME'])).'>';

		if($LMS->CONFIG['phpui']['debug_email'])
			$message['destination'] = $LMS->CONFIG['phpui']['debug_email'];
		
		if($message['sender']=='admin')
		{
			$message['adminid'] = $SESSION->id;
			$message['userid'] = 0;		
		}
		else
		{
			$message['adminid'] = 0;
			if($message['userid'])
				$message['mailfrom'] = $LMS->GetUserEmail($message['userid']);
			else
				$message['mailfrom'] = $message['destination'];
		}
		
		if(!$LMS->CONFIG['phpui']['helpdesk_backend_mode'])
		{
			if($message['destination'])
			{
				$message['mailfrom'] = $queue['email'] ? $queue['email'] : $admin['email'];
				$message['mailfrom'] = $LMS->CONFIG['rt']['mail_from'] ? $LMS->CONFIG['rt']['mail_from'] : '<'.$message['mailfrom'].'>';
				$message['replyto'] = $message['mailfrom']; 
				$message['headers'] = 'From: '.$message['mailfrom']."\n"
				    .($message['references'] ? 'References: '.$message['references']."\n" : '')
				    .'Message-Id: '.$message['messageid']."\n"
				    .'Reply-To: '.$message['mailfrom']."\n"
				    ."Content-Type: text/plain; charset=ISO-8859-2;\n"
				    .'X-Mailer: LMS-'.$LMS->_version.'/PHP-'.phpversion()."\n"
				    .'X-Remote-IP: '.$_SERVER['REMOTE_ADDR']."\n"
				    .'X-HTTP-User-Agent: '.$_SERVER['HTTP_USER_AGENT']."\n";
			    	
				
				mail('<'.$message['destination'].'>',
					$message['subject'],
					$message['body'],
					$message['headers']);
				flush();
			}
			else 
			{
				$message['messageid'] = '';
				$message['mailfrom'] = '';
				$message['headers'] = '';
			    	$message['replyto'] = ''; 
			}
				
			$LMS->MessageAdd($message);
		}
		else //wysy³amy do backendu
		{
			
	/*
			mail (	'<'.$message['destination'].'>',
			$message['subject'],
			$message['body'],
			'From: '.($LMS->CONFIG['rt']['mail_from'] ? $LMS->CONFIG['rt']['mail_from'] : '<'.$message['from'].'>')."\n"
			.($message['references'] ? 'References: '.$message['references']."\n" : '')
			.'Message-Id: '.$message['messageid']."\n"
			.'Reply-To: '.$message['from']."\n"
			."Content-Type: text/plain; charset=ISO-8859-2;\n"
			.'X-Mailer: LMS-'.$LMS->_version.'/PHP-'.phpversion()."\n"
			.'X-Remote-IP: '.$_SERVER['REMOTE_ADDR']."\n"
			.'X-HTTP-User-Agent: '.$_SERVER['HTTP_USER_AGENT']."\n"
			);
			flush();
	*/	}
		
		// ustawiamy status i w³a¶ciciela ticketu
		if(!$LMS->GetTicketOwner($message['ticketid']))
			$LMS->SetTicketOwner($message['ticketid']);
		if(!$LMS->GetTicketState($message['ticketid']))
			$LMS->SetTicketState($message['ticketid'], 1);

		header("Location: ?m=rtticketview&id=".$message['ticketid']);
		die;
	}
}
else
{
	if($_GET['ticketid'])
		$queue = $LMS->GetQueueByTicketId($_GET['ticketid']);
	$admin = $LMS->GetAdminInfo($SESSION->id);
	
	$message['ticketid'] = $_GET['ticketid'];
	$message['userid'] = $LMS->DB->GetOne('SELECT userid FROM rttickets WHERE id = ?', array($message['ticketid']));
	
	if($_GET['id'])
	{
		$reply = $LMS->GetMessage($_GET['id']); 

		if($reply['replyto'])
			$message['destination'] = ereg_replace('^.* <(.+@.+)>','\1',$reply['replyto']);
		else 
			$message['destination'] = ereg_replace('^.* <(.+@.+)>','\1',$reply['mailfrom']);

		if(!$message['destination'] && !$reply['adminid'])
			$message['destination'] = $LMS->GetUserEmail($message['userid']);
	
		$message['subject'] = 'Re: '.$reply['subject'];
			
		$message['inreplyto'] = $reply['id'];
		$message['references'] = $reply['messageid'];
	}
	
	if(!eregi("[RT#[0-9]{6}]",$message['subject'])) 
		$message['subject'] .= sprintf(" [RT#%06d]",$message['ticketid']); 
}

$layout['pagetitle'] = 'Nowa wiadomo¶æ';

$_SESSION['backto'] = $_SERVER['QUERY_STRING'];

$SMARTY->assign('message', $message);
$SMARTY->assign('error', $error);
$SMARTY->display('rtmessageadd.html');

?>
