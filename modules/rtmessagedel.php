<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2017 LMS Developers
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

$msg = intval($_GET['id']);
$maction = ($_GET['maction']);
$ticket = $DB->GetOne('SELECT ticketid FROM rtmessages WHERE id = ?', array($msg));
$rights = $LMS->GetUserRightsRT(Auth::GetCurrentUser(), 0, $ticket);

if (($rights & 4) != 4) {
    $SMARTY->display('noaccess.html');
    $SESSION->close();
    die;
}

if ($maction == 'delete') {
    $del = 1;
    $deltime = time();
    $DB->Execute('UPDATE rtmessages SET deleted=?, deltime=?, deluserid=? WHERE id = ?', array($del, $deltime, Auth::GetCurrentUser(), $msg));
}

$SESSION->redirect('?m=rtticketview&id=' . $ticket);
