<?php

/*
 * LMS version 1.5-cvs
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

include($_LIB_DIR.'/multipart_mime_email.php');

function MessageAdd($msg, $file=NULL)
{
	global $LMS;
	$time = time();
	$LMS->DB->Execute('INSERT INTO rtmessages (ticketid, createtime, subject, body, adminid, userid, mailfrom, inreplyto, messageid, replyto, headers)
			    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', array($msg['ticketid'], $time, $msg['subject'], $msg['body'], $msg['adminid'], $msg['userid'], $msg['mailfrom'], $msg['inreplyto'], $msg['messageid'], $msg['replyto'], $msg['headers']));
	$LMS->SetTS('rtmessages');
	if($file['name'])
	{
		$id = $LMS->DB->GetOne('SELECT id FROM rtmessages WHERE ticketid=? AND adminid=? AND userid=? AND createtime=?', array($msg['ticketid'], $msg['adminid'], $msg['userid'], $time));
		$dir = $LMS->CONFIG['rt']['mail_dir'].sprintf('/%06d/%06d',$msg['ticketid'],$id);
		@mkdir($LMS->CONFIG['rt']['mail_dir'].sprintf('/%06d',$msg['ticketid']), 0700);
		mkdir($dir, 0700);
		$newfile = $dir.'/'.$file['name'];
		if(rename($file['tmp_name'], $newfile))
			if($LMS->DB->Execute('INSERT INTO rtattachments (messageid, filename, contenttype) 
						VALUES (?,?,?)', array($id, $file['name'], $file['type'])))
				$LMS->SetTS('rtattachments');
	}		    
}

$message = $_POST['message'];

if(isset($message))
{
	if($message['subject'] == '')
		$error['subject'] = trans('Message title not specified!');
	
	if($message['body'] == '')
		$error['body'] = trans('Message body not specified!');

	if($message['destination']!='' && !check_email($message['destination']))
		$error['destination'] = trans('Incorrect email!');

	if($message['destination']!='' && $message['sender']=='user')
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
				case 3: $error['file'] = trans('File has been uploaded partly.'); break;
				case 4: $error['file'] = trans('Path to file was not specified.'); break;
				default: $error['file'] = trans('Problem during file upload.'); break;
			}
	}	

	if(!$error)
	{
		$queue = $LMS->GetQueueByTicketId($message['ticketid']);
		$admin = $LMS->GetAdminInfo($AUTH->id);
		
		$message['messageid'] = '<msg.'.$message['ticketid'].'.'.$queue['id'].'.'.time().'@rtsystem.'.gethostbyaddr(gethostbyname($_SERVER['SERVER_NAME'])).'>';

		if($message['sender']=='admin')
		{
			$message['adminid'] = $AUTH->id;
			$message['userid'] = 0;
		}
		else
		{
			$message['adminid'] = 0;
			if(!$message['userid']) 
			{
				$req = $LMS->DB->GetOne('SELECT requestor FROM rttickets WHERE id = ?', array($message['ticketid']));
				$message['mailfrom'] = ereg_replace('^.* <(.+@.+)>','\1', $req);
				if(!check_email($message['mailfrom']))
					$message['mailfrom'] = '';
			}
		}

		if($mailfname = $LMS->CONFIG['phpui']['helpdesk_sender_name'])
		{
			if($mailfname == 'queue') $mailfname = $queue['name'];
			if($mailfname == 'user') $mailfname = $admin['name'];
			$mailfname = '"'.$mailfname.'"';
		}
	
		if(!$LMS->CONFIG['phpui']['helpdesk_backend_mode'])
		{
			if($message['destination'] && $message['adminid'])
			{
				if($LMS->CONFIG['phpui']['debug_email'])
					$message['destination'] = $LMS->CONFIG['phpui']['debug_email'];
				$message['mailfrom'] = $queue['email'] ? $queue['email'] : $admin['email'];
				$message['mailfrom'] = '<'.$message['mailfrom'].'>';
				$message['replyto'] = $message['mailfrom'];
				
				$message['headers'] = 'From: '.$mailfname.' '.$message['mailfrom']."\n"
				    .($message['references'] ? 'References: '.$message['references']."\n" : '')
				    .'Message-Id: '.$message['messageid']."\n"
				    .'Reply-To: '.$message['replyto']."\n"
				    .(!$file ? "Content-Type: text/plain; charset=UTF-8;\n" : '')
				    .'X-Mailer: LMS-'.$LMS->_version.'/PHP-'.phpversion()."\n"
				    .'X-Remote-IP: '.$_SERVER['REMOTE_ADDR']."\n"
				    .'X-HTTP-User-Agent: '.$_SERVER['HTTP_USER_AGENT'];
			    	
				if($file)
				{
					$msg[1]['content_type'] = 'text/plain; charset=UTF-8';
					$msg[1]['filename'] = '';
					$msg[1]['no_base64'] = TRUE;
					$msg[1]['data'] = $message['body'];
			
					$msg[2]['content_type'] = $_FILES['file']['type'];
					$msg[2]['filename'] = $filename;
					$msg[2]['data'] = $file;
					$msg[2]['headers'] = '';
					
					$out = mp_new_message($msg);
				}
				
				mail('<'.$message['destination'].'>', 
					$message['subject'], 
					($out[0] ? $out[0] : $message['body']), 
					$message['headers']."\n".($out[1] ? "\n".$out[1] : ''));
				flush();
			}
			else 
			{
				$message['messageid'] = '';
				if($message['userid'] || $message['adminid'])
					$message['mailfrom'] = '';
				$message['headers'] = '';
			    	$message['replyto'] = '';
			}
				
			MessageAdd($message, $_FILES['file']);
		}
		else //sending to backend
		{
			($message['destination']!='' ? $addmsg = 1 : $addmsg = 0);
			
			if($LMS->CONFIG['phpui']['debug_email'])
					$message['destination'] = $LMS->CONFIG['phpui']['debug_email'];
			if($message['destination']=='') 
					$message['destination'] = $queue['email'];
				
			if($message['adminid'] && $addmsg)
				$message['mailfrom'] = $queue['email'] ? $queue['email'] : $admin['email'];
			if($message['adminid'] && !$addmsg)
				$message['mailfrom'] = $admin['email'] ? $admin['email'] : $queue['email'];
			
			if($message['userid'])
				$message['mailfrom'] = $LMS->GetUserEmail($message['userid']);

			$message['mailfrom'] = '<'.$message['mailfrom'].'>';
			$message['replyto'] = $message['mailfrom']; 
			$message['headers'] = 'From: '.$mailfname.' '.$message['mailfrom']."\n"
			    .($message['references'] ? 'References: '.$message['references']."\n" : '')
			    .'Message-Id: '.$message['messageid']."\n"
			    .'Reply-To: '.$message['replyto']."\n"
			    .(!$file ? "Content-Type: text/plain; charset=UTF-8;\n" : '')
			    .'X-Mailer: LMS-'.$LMS->_version.'/PHP-'.phpversion()."\n"
			    .'X-Remote-IP: '.$_SERVER['REMOTE_ADDR']."\n"
			    .'X-HTTP-User-Agent: '.$_SERVER['HTTP_USER_AGENT'];

			if($file)
			{
				$msg[1]['content_type'] = 'text/plain; charset=UTF-8';
				$msg[1]['filename'] = '';
				$msg[1]['no_base64'] = TRUE;
				$msg[1]['data'] = $message['body'];

				$msg[2]['content_type'] = $_FILES['file']['type'];
				$msg[2]['filename'] = $filename;
				$msg[2]['data'] = $file;
				$msg[2]['headers'] = '';
				
				$out = mp_new_message($msg);
			}

			mail('<'.$message['destination'].'>', 
				$message['subject'], 
				($out[0] ? $out[0] : $message['body']), 
				$message['headers'].($out[1] ? "\n".$out[1] : ''));
			flush();
			
			// message to user is written to database
			if($message['adminid'] && $addmsg) 
				MessageAdd($message, $_FILES['file']);
		}
		
		// setting status and ticket owner
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
	$admin = $LMS->GetAdminInfo($AUTH->id);
	
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

$layout['pagetitle'] = trans('New Message');

$_SESSION['backto'] = $_SERVER['QUERY_STRING'];

$SMARTY->assign('message', $message);
$SMARTY->assign('error', $error);
$SMARTY->display('rtmessageadd.html');

?>
