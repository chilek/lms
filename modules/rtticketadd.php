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
