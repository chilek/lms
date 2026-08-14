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
$maction = $_GET['maction'] ?? null;
$taction = $_GET['taction'] ?? null;
$qaction = $_GET['qaction'] ?? null;

$rt_dir = ConfigHelper::getConfig('rt.mail_dir', STORAGE_DIR . DIRECTORY_SEPARATOR . 'rt');

if ($maction == 'delperm') {
    $msg = intval($_GET['id']);
    $ticket = $DB->GetOne('SELECT ticketid FROM rtmessages WHERE id = ?', array($msg));

    if ($DB->GetOne('SELECT MIN(id) FROM rtmessages WHERE ticketid = ?', array($ticket)) != $msg) {
        if (!empty($rt_dir)) {
            rrmdir($rt_dir . DIRECTORY_SEPARATOR . sprintf('%06d' . DIRECTORY_SEPARATOR . '%06d', $ticket, $msg));
        }

        $DB->Execute('DELETE FROM rtmessages WHERE id = ?', array($msg));
    }

    $SESSION->redirect('?m=rtticketview&id=' . $ticket);
}

if ($taction == 'delperm') {
    $ticket = intval($_GET['id']);

    $DB->BeginTrans();

    $LMS->deleteTicket($ticket);

    $DB->CommitTrans();

    $SESSION->redirect('?m=rtqueueview'
        . ($SESSION->is_set('backid') ? '#' . $SESSION->get('backid') : ''));
}

if ($qaction == 'delperm') {
    $queue = intval($_GET['id']);
    $ticket = $DB->GetOne('SELECT id FROM rttickets WHERE queueid = ?', array($queue));

    if (!empty($rt_dir)) {
        // remove attachment files
        if ($tickets = $DB->GetCol('SELECT id FROM rttickets WHERE queueid = ?', array($queue))) {
            foreach ($tickets as $ticket) {
                rrmdir($rt_dir . DIRECTORY_SEPARATOR . sprintf('%06d', $ticket));
            }
        }
    }

    $DB->Execute('DELETE FROM rtqueues WHERE id=?', array($queue));

    $SESSION->redirect('?m=rtqueuelist');
}
