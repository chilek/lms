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

$id = $_GET['id'];

if (! $LMS->TicketExists($id)) {
    $SESSION->redirect('?m=rtqueuelist');
}

if (!$LMS->CheckTicketAccess($id)) {
    access_denied();
}

//$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$ticket = $LMS->GetTicketContents($id);


$ticket['relatedtickets'] = $LMS->GetRelatedTickets($id);
$ticket['childtickets'] = $LMS->GetChildTickets($id);

$ticket['message'] = $DB->GetOne(
    'SELECT body FROM rtmessages
		    WHERE ticketid = ?
		    ORDER BY createtime DESC LIMIT 1',
    array($id)
);

$ticket['uptime'] = uptimef($ticket['resolvetime'] ? $ticket['resolvetime'] - $ticket['createtime'] : time() - $ticket['createtime']);
$aet = ConfigHelper::getConfig('rt.allow_modify_resolved_tickets_newer_than', 86400);

$SMARTY->assign('ticket', $ticket);
$SMARTY->assign('aet', $aet);
$SMARTY->display('rt/rtticketinfoshort.html');
