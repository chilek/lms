<?php

/*
 * LMS version 1.4-cvs
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

	if($ticket['subject']=='' && $ticket['message']['body']=='')
	{
		header('Location: ?m=rtticketadd&id'.$queue);
		die;
	}

	if($LMS->GetAdminRightsRT($SESSION->id, $queue) < 2)
		$error['queue'] = "Nie masz uprawnieñ do tej kolejki!";

	if($ticket['subject'] == '')
		$error['subject'] = "Zg³oszenie musi posiadaæ tytu³!";

	if($ticket['body'] == '')
		$error['body'] = "Zg³oszenie musi mieæ tre¶æ!";

	if($ticket['email']!='' && !check_email($ticket['email']))
		$error['email'] = 'Podany email nie wydaje siê byæ poprawny!';

	if($ticket['surname']=='' && $ticket['userid']==0)
		$error['surname'] = 'Musisz podaæ nazwê/nazwisko zg³aszaj±cego!';

	$requestor  = ($ticket['surname'] ? $ticket['surname'].' ' : '');
	$requestor .= ($ticket['name'] ? $ticket['name'].' ' : '');	    
	$requestor .= ($ticket['email'] ? '<'.$ticket['email'].'>' : '');
	$ticket['requestor'] = trim($requestor);
	
	$ticket['mailfrom'] = $ticket['email'] ? $ticket['email'] : '';

	if(!$error)
	{
		$id = $LMS->TicketAdd($ticket);
		header("Location: ?m=rtticketview&id=".$id);
		die;
	}
}
	
$layout['pagetitle'] = 'Nowe zg³oszenie';

$_SESSION['backto'] = $_SERVER['QUERY_STRING'];

$SMARTY->assign('ticket', $ticket);
$SMARTY->assign('queue', $queue);
$SMARTY->assign('queuelist', $LMS->GetQueueNames());
$SMARTY->assign('user', $user);
$SMARTY->assign('userlist', $LMS->GetUserNames());
$SMARTY->assign('error', $error);
$SMARTY->display('rtticketadd.html');

?>
