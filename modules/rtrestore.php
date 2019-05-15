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
$maction = ($_GET['maction']);
$taction = ($_GET['taction']);
$qaction = ($_GET['qaction']);

if ($maction == 'restore') {
    $msg = intval($_GET['id']);
    $ticket = $DB->GetOne('SELECT ticketid FROM rtmessages WHERE id = ?', array($msg));
    $del = 0;
    $deltime = 0;
    $deluserid = null;
    $DB->Execute('UPDATE rtmessages SET deleted=?, deltime=?, deluserid=? WHERE id = ?', array($del, $deltime, $deluserid, $msg));

    $SESSION->redirect('?m=rtticketview&id=' . $ticket);
}

if ($taction == 'restore') {
    $ticket = intval($_GET['id']);
    $del = 1;
    $nodel = 0;
    $deltime = 0;
    $deluserid = null;
    // We use incomplete cascaderestore. This means that we restore only ticket, but not restore deleted messages inside ticket which were deleted before restore operation.
    $DB->BeginTrans();
    $DB->Execute('UPDATE rttickets SET deleted=?, deltime=?, deluserid=? WHERE id = ?', array($nodel, $deltime, $deluserid, $ticket));
    $DB->Execute('UPDATE rtmessages SET deleted=?, deluserid=? WHERE deleted=? and deltime = ? and ticketid = ?', array($nodel, $deluserid, $del, $deltime, $ticket));
    $DB->CommitTrans();

    $SESSION->redirect('?m=rtqueueview'
        . ($SESSION->is_set('backid') ? '#' . $SESSION->get('backid') : ''));
}

if ($qaction == 'restore') {
    $queue = intval($_GET['id']);
    $del = 1;
    $nodel = 0;
    $deltime = 0;
    $deluserid = null;
    $DB->BeginTrans();
    $DB->Execute('UPDATE rtqueues SET deleted=?, deltime=?, deluserid=? WHERE id = ?', array($nodel, $deltime, $deluserid, $queue));
    $DB->Execute('UPDATE rttickets SET deleted=?, deluserid=? WHERE deleted=? and deltime = ? and queueid = ?', array($nodel, $deluserid, $del, $deltime, $queue));
    if ($deltickets = $DB->GetCol('SELECT id FROM rttickets WHERE queueid = ?', array($queue))) {
        foreach ($deltickets as $delticket) {
            $DB->Execute('UPDATE rtmessages SET deleted=?, deluserid=? WHERE deleted=? and deltime = ? and ticketid = ?', array($nodel, $deluserid, $del, $deltime, $delticket));
        }
    }
    $DB->CommitTrans();

    $SESSION->redirect('?m=rtqueuelist');
}
