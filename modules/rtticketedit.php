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

$ticket = $_POST['ticket'];

if(isset($ticket))
{
//	if(! $LMS->GetAdminRightsRT($SESSION->id, $queue))
//		$error['queue'] = "Nie masz uprawnieñ do tej kolejki!";
	
	if($ticket['name'] == '')
		$error['name'] = "Zg³oszenie musi posiadaæ tytu³!";

	if($ticket['email']!='' && !check_email($ticket['email']))
		$error['email'] = 'Podany email nie wydaje siê byæ poprawny!';

	$user = $ticket['user'];

	if(!$error)
	{
		$id = $LMS->TicketUpdate($ticket);
		header("Location: ?m=rtticketview&id=".$id);
		die;
	}
}
else
{
	$ticket = $LMS->GetTicketContents($_GET['id']);
}
	
$layout['pagetitle'] = 'Edycja zg³oszenia Nr '.$ticket['ticketid'];

$_SESSION['backto'] = $_SERVER['QUERY_STRING'];

$SMARTY->assign('ticket', $ticket);
$SMARTY->assign('queue', $queue);
$SMARTY->assign('queuelist', $LMS->GetQueueNames());
$SMARTY->assign('user', $queue);
$SMARTY->assign('userlist', $LMS->GetUserNames());
$SMARTY->assign('error', $error);
$SMARTY->display('rtticketedit.html');

?>
