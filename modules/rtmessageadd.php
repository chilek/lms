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

if($_GET['id'])
	$reply = $LMS->GetMessage($_GET['id']); 

if(isset($message))
{
	$message['ticketid'] = $_GET['ticketid'];

	if($message['subject'] == '')
		$error['subject'] = "Wiadomo¶æ musi mieæ tytu³!";

	if($message['body'] == '')
		$error['body'] = "Nie poda³e¶ tre¶ci wiadomo¶ci!";

	if($message['mailfrom']!='' && !check_email($message['mailfrom']))
		$error['mailfrom'] = 'Podany email nie wydaje siê byæ poprawny!';

	if($message['destination']!='' && !check_email($message['destination']))
		$error['destination'] = 'Podany email nie wydaje siê byæ poprawny!';

	if($message['destination']=='' && isset($_GET['mail']))
		$error['destination'] = 'Nie mo¿na wys³aæ wiadomo¶ci bez adresu odbiorcy!';


	if(!$error)
	{
		$message['inreplyto'] = ($reply['id'] ? $reply['id'] : 0);
		$message['sender'] = $SESSION->id;

		$LMS->MessageAdd($message);

		// here will be message sending
		// if(isset($_GET['mail']))
		//	$LMS->MessageSend($message);
		
		header("Location: ?m=rtticketview&id=".$message['ticketid']);
		die;
	}
}
else
{
	if($_GET['ticketid'])
		$queue = $LMS->GetQueueByTicketId($_GET['ticketid']);
	$admin = $LMS->GetAdminInfo($SESSION->id);

	$message['mailfrom'] = ($queue['email'] ? $queue['email'] : $admin['email']);
	$message['destination'] = ($reply['replyto'] ? $reply['replyto'] : $reply['mailfrom']);
	$message['ticketid'] = $_GET['ticketid'];
}

$layout['pagetitle'] = 'Nowa wiadomo¶æ';

$_SESSION['backto'] = $_SERVER['QUERY_STRING'];

$SMARTY->assign('message', $message);
$SMARTY->assign('error', $error);
$SMARTY->display('rtmessageadd.html');

?>
