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

$ticket = $_POST['ticket'];

$queue = $_GET['id'];

if(isset($ticket))
{
	$queue = $ticket['queue'];

	if($ticket['subject']=='' && $ticket['message']['body']=='')
	{
		$SESSION->redirect('?m=rtticketadd&id='.$queue);
	}

	if($LMS->GetAdminRightsRT($AUTH->id, $queue) < 2)
		$error['queue'] = trans('You have no privilleges to this queue!');

	if($ticket['subject'] == '')
		$error['subject'] = trans('Ticket must have its title!');

	if($ticket['body'] == '')
		$error['body'] = trans('Ticket must have its body!');

	if($ticket['email']!='' && !check_email($ticket['email']))
		$error['email'] = trans('Incorrect email!');

	if($ticket['surname']=='' && $ticket['userid']==0)
		$error['surname'] = trans('Requestor name required!');

	$requestor  = ($ticket['surname'] ? $ticket['surname'].' ' : '');
	$requestor .= ($ticket['name'] ? $ticket['name'].' ' : '');	    
	$requestor .= ($ticket['email'] ? '<'.$ticket['email'].'>' : '');
	$ticket['requestor'] = trim($requestor);
	
	$ticket['mailfrom'] = $ticket['email'] ? $ticket['email'] : '';

	if(!$error)
	{
		$id = $LMS->TicketAdd($ticket);

		$ticket['admin'] = $LMS->DB->GetOne('SELECT email FROM rtqueues WHERE id='.$queue);
		$message['destination'] = $ticket['admin'];
		if($LMS->CONFIG['phpui']['debug_email'])
			$message['destination'] = $LMS->CONFIG['phpui']['debug_email'];
		$recipients = $message['destination'];
		$message['mailfrom'] = $ticket['mailfrom'] ? $ticket['mailfrom'] : $ticket['admin'];
		$headers['Date'] = date('D, d F Y H:i:s T');
	        $headers['From'] = '<'.$message['mailfrom'].'>';
		$headers['To'] = '<'.$message['destination'].'>';
		$headers['Subject'] = $ticket['subject'];
		$headers['Reply-To'] = $headers['From'];
		$headers['X-Mailer'] = 'LMS-'.$LMS->_version.'/PHP-'.phpversion();
		$headers['X-Remote-IP'] = $_SERVER['REMOTE_ADDR'];
		$headers['X-HTTP-User-Agent'] = $_SERVER['HTTP_USER_AGENT'];

		$msg[1]['content_type'] = 'text/plain; charset=UTF-8';
		$msg[1]['filename'] = '';
		$msg[1]['no_base64'] = TRUE;
		$msg[1]['data'] = $ticket['body']."\n\nhttps://".$_SERVER['HTTP_HOST'].substr($_SERVER['REQUEST_URI'],
			0, strrpos($_SERVER['REQUEST_URI'], '/') + 1).'?m=rtticketview&id='.$id;
		$out = mp_new_message($msg);
		$body = $out[0];

		$body = $ticket['body']."\n\nhttp".($_SERVER['HTTPS'] == 'on' ? 's' : '').'://'
			.$_SERVER['HTTP_HOST'].substr($_SERVER['REQUEST_URI'], 0, strrpos($_SERVER['REQUEST_URI'], '/') + 1)
			.'?m=rtticketview&id='.$id;
		$LMS->SendMail($recipients, $headers, $body);

		$SESSION->redirect('?m=rtticketview&id='.$id);
	}
}
	
$layout['pagetitle'] = trans('New Ticket');

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$SMARTY->assign('ticket', $ticket);
$SMARTY->assign('queue', $queue);
$SMARTY->assign('queuelist', $LMS->GetQueueNames());
$SMARTY->assign('user', $user);
$SMARTY->assign('userlist', $LMS->GetUserNames());
$SMARTY->assign('error', $error);
$SMARTY->display('rtticketadd.html');

?>
