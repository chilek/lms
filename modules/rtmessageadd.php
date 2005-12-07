<?php

/*
 * LMS version 1.9-cvs
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

function MessageAdd($msg, $headers, $file=NULL)
{
	global $DB, $LMS;
	$time = time();
	
	$head = '';
	if($headers)
		foreach($headers as $idx => $header)
			$head .= $idx.": ".$header."\n";
	
	$DB->Execute('INSERT INTO rtmessages (ticketid, createtime, subject, body, userid, customerid, mailfrom, inreplyto, messageid, replyto, headers)
			    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', 
			    array(
				$msg['ticketid'],
				$time,
				$msg['subject'],
				$msg['body'],
				$msg['userid'],
				$msg['customerid'],
				$msg['mailfrom'],
				$msg['inreplyto'],
				$msg['messageid'],
				(isset($msg['replyto']) ? $msg['replyto'] : $headers['Reply-To']),
				$head));
	$LMS->SetTS('rtmessages');

	if(isset($file['name']))
	{
		$id = $DB->GetOne('SELECT id FROM rtmessages WHERE ticketid=? AND userid=? AND customerid=? AND createtime=?', array($msg['ticketid'], $msg['userid'], $msg['customerid'], $time));
		$dir = $LMS->CONFIG['rt']['mail_dir'].sprintf('/%06d/%06d',$msg['ticketid'],$id);
		@mkdir($LMS->CONFIG['rt']['mail_dir'].sprintf('/%06d',$msg['ticketid']), 0700);
		@mkdir($dir, 0700);
		$newfile = $dir.'/'.$file['name'];
		if(@rename($file['tmp_name'], $newfile))
			if($DB->Execute('INSERT INTO rtattachments (messageid, filename, contenttype) 
						VALUES (?,?,?)', array($id, $file['name'], $file['type'])))
				$LMS->SetTS('rtattachments');
	}		    
}

if(isset($_POST['message']))
{
	$message = $_POST['message'];
	
	if($message['subject'] == '')
		$error['subject'] = trans('Message subject not specified!');
	
	if($message['body'] == '')
		$error['body'] = trans('Message body not specified!');

	if($message['destination']!='' && !check_email($message['destination']))
		$error['destination'] = trans('Incorrect email!');

	if($message['destination']!='' && $message['sender']=='customer')
		$error['destination'] = trans('Customer cannot send message!');

	if($filename = $_FILES['file']['name'])
	{
		if(is_uploaded_file($_FILES['file']['tmp_name']) && $_FILES['file']['size'])
		{
			$file = '';
			$fd = fopen($_FILES['file']['tmp_name'], 'r');
			if($fd)
			{
				while(!feof($fd))
					$file .= fread($fd,256);
				fclose($fd);
			}
		} 
		else // upload errors
			switch($_FILES['file']['error'])
			{
				case 1: 			
				case 2: $error['file'] = trans('File is too large.'); break;
				case 3: $error['file'] = trans('File upload has finished prematurely.'); break;
				case 4: $error['file'] = trans('Path to file was not specified.'); break;
				default: $error['file'] = trans('Problem during file upload.'); break;
			}
	}	

	if(!$error)
	{
		$queue = $LMS->GetQueueByTicketId($message['ticketid']);
		$user = $LMS->GetUserInfo($AUTH->id);
		
		$message['messageid'] = '<msg.'.$message['ticketid'].'.'.$queue['id'].'.'.time().'@rtsystem.'.gethostbyaddr(gethostbyname($_SERVER['SERVER_NAME'])).'>';

		if($message['sender']=='user')
		{
			$message['userid'] = $AUTH->id;
			$message['customerid'] = 0;
		}
		else
		{
			$message['userid'] = 0;
			if(!$message['customerid']) 
			{
				$req = $DB->GetOne('SELECT requestor FROM rttickets WHERE id = ?', array($message['ticketid']));
				$message['mailfrom'] = ereg_replace('^.* <(.+@.+)>','\1', $req);
				if(!check_email($message['mailfrom']))
					$message['mailfrom'] = '';
			}
		}

		if($mailfname = $LMS->CONFIG['phpui']['helpdesk_sender_name'])
		{
			if($mailfname == 'queue') $mailfname = $queue['name'];
			if($mailfname == 'customer') $mailfname = $user['name'];
			$mailfname = '"'.$mailfname.'"';
		}
	
		if(!$LMS->CONFIG['phpui']['helpdesk_backend_mode'])
		{
			if($message['destination'] == '')
				$message['destination'] = $queue['email'];
			if($message['destination'] && $message['userid'])
			{
				if($LMS->CONFIG['phpui']['debug_email'])
					$message['destination'] = $LMS->CONFIG['phpui']['debug_email'];
				$recipients = $message['destination'];
				$message['mailfrom'] = $user['email'] ? $user['email'] : $queue['email'];

				$headers['Date'] = date('D, d F Y H:i:s T');
				$headers['From'] = $mailfname.' <'.$message['mailfrom'].'>';
				$headers['To'] = '<'.$message['destination'].'>';
				$headers['Subject'] = $message['subject'];
				if ($message['references'])
					$headers['References'] = $message['references'];
				$headers['Message-Id'] = $message['messageid'];
				$headers['Reply-To'] = $headers['From'];

				$body = $message['body'];
				if ($message['destination'] == $queue['email'] || $message['destination'] == $user['email'])
					$body .= "\n\nhttp".($_SERVER['HTTPS'] == 'on' ? 's' : '').'://'
						.$_SERVER['HTTP_HOST'].substr($_SERVER['REQUEST_URI'], 0, strrpos($_SERVER['REQUEST_URI'], '/') + 1)
						.'?m=rtticketview&id='.$message['ticketid'];
				$files = NULL;
				if (isset($file))
				{
					$files[0]['content_type'] = $_FILES['file']['type'];
					$files[0]['filename'] = $filename;
					$files[0]['data'] = $file;
				}
				$LMS->SendMail($recipients, $headers, $body, $files);
			}
			else 
			{
				$message['messageid'] = '';
				if($message['customerid'] || $message['userid'])
					$message['mailfrom'] = '';
				$message['headers'] = '';
			    	$message['replyto'] = '';
			}
				
			MessageAdd($message, $headers, $_FILES['file']);
		}
		else //sending to backend
		{
			($message['destination']!='' ? $addmsg = 1 : $addmsg = 0);
			
			if($LMS->CONFIG['phpui']['debug_email'])
				$message['destination'] = $LMS->CONFIG['phpui']['debug_email'];
			if($message['destination']=='') 
				$message['destination'] = $queue['email'];
			$recipients = $message['destination'];

			if($message['userid'] && $addmsg)
				$message['mailfrom'] = $queue['email'] ? $queue['email'] : $user['email'];
			if($message['userid'] && !$addmsg)
				$message['mailfrom'] = $user['email'] ? $user['email'] : $queue['email'];
			
			if($message['customerid'])
				$message['mailfrom'] = $LMS->GetCustomerEmail($message['customerid']);

			$headers['Date'] = date('D, d F Y H:i:s T');
			$headers['From'] = $mailfname.' <'.$message['mailfrom'].'>';
			$headers['To'] = '<'.$message['destination'].'>';
			$headers['Subject'] = $message['subject'];
			if ($message['references'])
				$headers['References'] = $message['references'];
			$headers['Message-Id'] = $message['messageid'];
			$headers['Reply-To'] = $headers['From'];
			
			$body = $message['body'];
			if ($message['destination'] == $queue['email'] || $message['destination'] == $user['email'])
				$body .= "\n\nhttp".($_SERVER['HTTPS'] == 'on' ? 's' : '').'://'
					.$_SERVER['HTTP_HOST'].substr($_SERVER['REQUEST_URI'], 0, strrpos($_SERVER['REQUEST_URI'], '/') + 1)
					.'?m=rtticketview&id='.$message['ticketid'];
			$files = NULL;
			if ($file)
			{
				$files[0]['content_type'] = $_FILES['file']['type'];
				$files[0]['filename'] = $filename;
				$files[0]['data'] = $file;
			}
			$LMS->SendMail($recipients, $headers, $body, $files);

			// message to customer is written to database
			if($message['userid'] && $addmsg) 
				MessageAdd($message, $headers, $_FILES['file']);
		}
		
		// setting status and ticket owner
		if(!$LMS->GetTicketOwner($message['ticketid']))
			$LMS->SetTicketOwner($message['ticketid']);
		if(!$LMS->GetTicketState($message['ticketid']))
			$LMS->SetTicketState($message['ticketid'], 1);

		$SESSION->redirect('?m=rtticketview&id='.$message['ticketid']);
	}
}
else
{
	if($_GET['ticketid'])
		$queue = $LMS->GetQueueByTicketId($_GET['ticketid']);
	$user = $LMS->GetUserInfo($AUTH->id);
	
	$message['ticketid'] = $_GET['ticketid'];
	$message['customerid'] = $DB->GetOne('SELECT customerid FROM rttickets WHERE id = ?', array($message['ticketid']));
	
	if($_GET['id'])
	{
		$reply = $LMS->GetMessage($_GET['id']); 

		if($reply['replyto'])
			$message['destination'] = ereg_replace('^.* <(.+@.+)>','\1',$reply['replyto']);
		else 
			$message['destination'] = ereg_replace('^.* <(.+@.+)>','\1',$reply['mailfrom']);

		if(!$message['destination'] && !$reply['userid'])
			$message['destination'] = $LMS->GetCustomerEmail($message['customerid']);
	
		$message['subject'] = 'Re: '.$reply['subject'];
			
		$message['inreplyto'] = $reply['id'];
		$message['references'] = $reply['messageid'];
	}
	
	if(!eregi("[RT#[0-9]{6}]",$message['subject'])) 
		$message['subject'] .= sprintf(" [RT#%06d]",$message['ticketid']); 
}

$layout['pagetitle'] = trans('New Message');

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$SMARTY->assign('message', $message);
$SMARTY->assign('error', $error);
$SMARTY->display('rtmessageadd.html');

?>
