<?php

/*
 * LMS version 1.9-cvs
 *
 *  (C) Copyright 2001-2007 LMS Developers
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

$rights = $LMS->GetUserRightsRT($AUTH->id, 0, $_GET['id']);

if(!$rights)
{
	$SMARTY->display('noaccess.html');
	$SESSION->close();
	die;
}

if(isset($_GET['delmsgid']))
{
	if(($rights & 4) != 4)
	{
		$SMARTY->display('noaccess.html');
		$SESSION->close();
		die;
	}

	if($DB->GetOne('SELECT MIN(id) FROM rtmessages WHERE ticketid = ?', array($_GET['id'])) != $_GET['delmsgid'])
	{
		$DB->Execute('DELETE FROM rtmessages WHERE id = ?', array(intval($_GET['delmsgid'])));
	}
}

$ticket = $LMS->GetTicketContents($_GET['id']);

if($ticket['customerid'] && isset($CONFIG['phpui']['helpdesk_stats']) && chkconfig($CONFIG['phpui']['helpdesk_stats']))
{
	$yearago = mktime(0, 0, 0, date('n'), date('j'), date('Y')-1);
	$stats = $DB->GetAllByKey('SELECT COUNT(*) AS num, cause FROM rttickets 
			    WHERE customerid = ? AND createtime >= ? 
			    GROUP BY cause', 'cause', array($ticket['customerid'], $yearago));

	$SMARTY->assign('stats', $stats);
}

if($ticket['customerid'] && chkconfig($CONFIG['phpui']['helpdesk_customerinfo']))
{
	$customer = $LMS->GetCustomer($ticket['customerid']);
        $customer['groups'] = $LMS->CustomergroupGetForCustomer($ticket['customerid']);

	if($customer['contacts']) $customer['phone'] = $customer['contacts'][0]['phone'];
	$SMARTY->assign('customer', $customer);		
}

$layout['pagetitle'] = trans('Ticket Review: $0',sprintf("%06d",$ticket['ticketid']));

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$SMARTY->assign('ticket', $ticket);
$SMARTY->display('rtticketview.html');
?>
