<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2019 LMS Developers
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

if(! $LMS->TicketExists($_GET['id']))
{
	$SESSION->redirect('?m=rtqueuelist');
}

if (!$LMS->CheckTicketAccess($_GET['id']))
	access_denied();

$LMS->MarkTicketAsRead($_GET['id']);

$ticket = $LMS->GetTicketContents($_GET['id']);

if ($ticket['customerid'] && ConfigHelper::checkConfig('phpui.helpdesk_stats')) {
	$yearago = mktime(0, 0, 0, date('n'), date('j'), date('Y')-1);
	$stats = $DB->GetAllByKey('SELECT COUNT(*) AS num, cause FROM rttickets
		WHERE customerid = ? AND createtime >= ?
		GROUP BY cause', 'cause', array($ticket['customerid'], $yearago));

	$SMARTY->assign('stats', $stats);
}

if($ticket['customerid'])
{
	$SMARTY->assign('customerinfo', $LMS->GetCustomer($ticket['customerid']));
	$SMARTY->assign('customernodes', $LMS->GetCustomerNodes($ticket['customerid']));
}

$layout['pagetitle'] = trans('Ticket No. $a',sprintf("%06d",$ticket['ticketid']));

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$SMARTY->assign('ticket', $ticket);
$SMARTY->assign('messages', isset($_GET['messages']) ? 1 : 0);
$SMARTY->assign('notes', isset($_GET['notes']) ? 1 : 0);
$SMARTY->assign('history', isset($_GET['history']) ? 1 : 0);
$SMARTY->display('rt/' . ConfigHelper::getConfig('phpui.ticket_template_file'));

?>
