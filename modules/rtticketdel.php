<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2020 LMS Developers
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

if (is_array($_GET['id'])) {
    $tickets = $_GET['id'];
} else {
    $tickets = array($_GET['id']);
}
$tickets = Utils::filterIntegers($tickets);
if (empty($tickets)) {
    die;
}

foreach ($tickets as $ticket) {
    if (!($LMS->CheckTicketAccess($ticket) & RT_RIGHT_DELETE)) {
        access_denied();
    }
}

switch ($_GET['taction']) {
    case 'delete':
        // We use incomplete cascade delete. This means that we delete only messages tah weren't deleted before ticket delete operation.
        $DB->BeginTrans();
        $DB->Execute(
            'UPDATE rttickets SET deleted = ?, deltime = ?NOW?, deluserid = ? WHERE deleted = ? AND id IN ?',
            array(1, Auth::GetCurrentUser(), 0, $tickets)
        );
        $DB->Execute(
            'UPDATE rtmessages SET deleted = ?, deluserid = ? WHERE deleted = ? and ticketid IN ?',
            array(1, Auth::GetCurrentUser(), 0, $tickets)
        );
        $DB->CommitTrans();
        break;
    case 'delperm':
        $DB->Execute(
            'DELETE FROM rttickets WHERE id IN ?',
            array($tickets)
        );
        break;
}

$SESSION->redirect(
    '?m=rtqueueview'
        . ($SESSION->is_set('backid') ? '#' . $SESSION->get('backid') : '')
);
