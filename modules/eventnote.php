<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2022 LMS Developers
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

$event = $LMS->GetEvent($_GET['id']);

$event['date'] = sprintf('%04d/%02d/%02d', date('Y', $event['date']), date('n', $event['date']), date('j', $event['date']));

$backto = $SESSION->get_history_entry('m=eventlist');
$backid = $SESSION->get('backid');
$backurl = '?' . $backto . (empty($backid) ? '' : '#' . $backid);

if (isset($_POST['event'])) {
    $event = $_POST['event'];
    $event['id'] = $_GET['id'];
    $DB->Execute('UPDATE events SET note=? WHERE id=?', array($event['note'], $event['id']));
    $SESSION->remove_history_entry();
    $SESSION->redirect($backurl);
}

$SMARTY->assign('backurl', $backurl);

$layout['pagetitle'] = trans('Add Note');

$SESSION->add_history_entry();
$SMARTY->assign('event', $event);
$SMARTY->display('event/eventnote.html');
