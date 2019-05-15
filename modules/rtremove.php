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

if ($maction == 'delperm') {
    $msg = intval($_GET['id']);
    $ticket = $DB->GetOne('SELECT ticketid FROM rtmessages WHERE id = ?', array($msg));

    if ($DB->GetOne('SELECT MIN(id) FROM rtmessages WHERE ticketid = ?', array($ticket)) != $msg) {
        $mail_dir = ConfigHelper::getConfig('rt.mail_dir');
        if (!empty($mail_dir)) {
            rrmdir($mail_dir . DIRECTORY_SEPARATOR . sprintf('%06d' . DIRECTORY_SEPARATOR . '%06d', $ticket, $msg));
        }

        $DB->Execute('DELETE FROM rtmessages WHERE id = ?', array($msg));
    }

    $SESSION->redirect('?m=rtticketview&id=' . $ticket);
}

if ($taction == 'delperm') {
    $ticket = intval($_GET['id']);

    $queue = $LMS->GetQueueByTicketId($ticket);
    $DB->Execute('DELETE FROM rttickets WHERE id = ?', array($ticket));
    //HINT: We delete messages connected with deleted ticket in database (ON DELETE CASCADE mechanism)
    
    $mail_dir = ConfigHelper::getConfig('rt.mail_dir');
    if (!empty($mail_dir)) {
        rrmdir($mail_dir . DIRECTORY_SEPARATOR . sprintf('%06d', $ticket));
    }

    $SESSION->redirect('?m=rtqueueview'
        . ($SESSION->is_set('backid') ? '#' . $SESSION->get('backid') : ''));
}

if ($qaction == 'delperm') {
    $queue = intval($_GET['id']);
    $ticket = $DB->GetOne('SELECT id FROM rttickets WHERE queueid = ?', array($queue));

    $mail_dir = ConfigHelper::getConfig('rt.mail_dir');
    if (!empty($mail_dir)) {
        // remove attachment files
        if ($tickets = $DB->GetCol('SELECT id FROM rttickets WHERE queueid = ?', array($queue))) {
            foreach ($tickets as $ticket) {
                rrmdir($mail_dir . DIRECTORY_SEPARATOR . sprintf('%06d', $ticket));
            }
        }
    }

    $DB->Execute('DELETE FROM rtqueues WHERE id=?', array($queue));

    $SESSION->redirect('?m=rtqueuelist');
}
