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

$ticketedit = $_POST['ticket'];

if(($id = $_GET['id']) && !isset($ticketedit))
{
	if($LMS->GetUserRightsRT($AUTH->id, 0, $id) < 2)
	{
		$SMARTY->display('noaccess.html');
		$SESSION->close();
		die;
	}

	if($_GET['state'])
	{
		$LMS->SetTicketState($id, $_GET['state']);
		$SESSION->redirect('?m=rtticketview&id='.$id);
	}
}

$ticket = $LMS->GetTicketContents($id);

if(isset($ticketedit))
{
	$ticketedit['ticketid'] = $ticket['ticketid'];
	if($LMS->GetUserRightsRT($AUTH->id, $ticketedit['queueid']) < 2)
		$error['queue'] = trans('You have no privileges to this queue!');
	
	if($ticketedit['subject'] == '')
		$error['subject'] = trans('Ticket must have its title!');

	if($ticketedit['state']>0 && !$ticketedit['owner'])
		$error['owner'] = trans('Only \'new\' ticket can be owned by no one!');

	if($ticketedit['state']==0 && $ticketedit['owner'])
		$ticketedit['state'] = 1;

	if(!$error)
	{
		$LMS->TicketUpdate($ticketedit);
		$SESSION->redirect('?m=rtticketview&id='.$id);
	}
	
	$ticket['subject'] = $ticketedit['subject'];
	$ticket['queueid'] = $ticketedit['queueid'];
	$ticket['state'] = $ticketedit['state'];
	$ticket['owner'] = $ticketedit['owner'];
}

$layout['pagetitle'] = trans('Ticket Edit: No. $0',sprintf("%06d",$ticket['ticketid']));

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$SMARTY->assign('ticket', $ticket);
$SMARTY->assign('queuelist', $LMS->GetQueueNames());
$SMARTY->assign('userlist', $LMS->GetUserNames());
$SMARTY->assign('customerlist', $LMS->GetAllCustomerNames());
$SMARTY->assign('error', $error);
$SMARTY->display('rtticketedit.html');

?>
