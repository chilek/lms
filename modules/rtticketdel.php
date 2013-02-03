<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2013 LMS Developers
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

$ticket = intval($_GET['id']);
$queue = $DB->GetOne('SELECT queueid FROM rttickets WHERE id = ?', array($ticket));
$right = $LMS->GetUserRightsRT($AUTH->id, $queue);

if(($right & 4) != 4)
{
	$SMARTY->display('noaccess.html');
        $SESSION->close();
	die;
}

$DB->Execute('DELETE FROM rttickets WHERE id = ?', array($ticket));
//HINT: We delete messages connected with deleted ticket in database (ON DELETE CASCADE mechanism)

if (isset($CONFIG['rt']['mail_dir']))
	rrmdir($CONFIG['rt']['mail_dir'].sprintf('/%06d', $ticket));

header('Location: ?m=rtqueueview&id='.$queue);

?>
