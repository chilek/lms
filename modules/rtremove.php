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
$id = intval($_GET['id']);

$rt_dir = ConfigHelper::getConfig('rt.mail_dir', STORAGE_DIR . DIRECTORY_SEPARATOR . 'rt');

if ($maction == 'delperm') {
    $ticket = $DB->GetOne('SELECT ticketid FROM rtmessages WHERE id = ?', array($id));

    if ($DB->GetOne('SELECT MIN(id) FROM rtmessages WHERE ticketid = ?', array($ticket)) != $id) {
        if (!empty($rt_dir)) {
            rrmdir($rt_dir . DIRECTORY_SEPARATOR . sprintf('%06d' . DIRECTORY_SEPARATOR . '%06d', $ticket, $id));
        }

        $DB->Execute('DELETE FROM rtmessages WHERE id = ?', array($id));
    }

    $SESSION->redirect('?m=rtticketview&id=' . $ticket);
}

if ($taction == 'delperm') {
    $DB->BeginTrans();

    $LMS->deleteTicket($id);

    $DB->CommitTrans();

    $SESSION->redirect('?m=rtqueueview'
        . ($SESSION->is_set('backid') ? '#' . $SESSION->get('backid') : ''));
}

if ($qaction == 'delperm') {
    $ticket = $DB->GetOne('SELECT id FROM rttickets WHERE queueid = ?', array($id));

    if (!empty($rt_dir)) {
        // remove attachment files
        if ($tickets = $DB->GetCol('SELECT id FROM rttickets WHERE queueid = ?', array($id))) {
            foreach ($tickets as $ticket) {
                rrmdir($rt_dir . DIRECTORY_SEPARATOR . sprintf('%06d', $ticket));
            }
        }
    }

    $DB->Execute('DELETE FROM rtqueues WHERE id = ?', array($id));

    $SESSION->redirect('?m=rtqueuelist');
}
