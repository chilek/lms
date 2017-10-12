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

$queue = intval($_GET['id']);
$qaction = ($_GET['qaction']);
$ticket = $DB->GetOne('SELECT id FROM rttickets WHERE queueid = ?', array($queue));

if ($qaction == 'delete')
{
	$del = 1;
	$nodel = 0;
	$deltime = time();
	$DB->BeginTrans();
	$DB->Execute('UPDATE rtqueues SET deleted=?, deltime=?, deluserid=? WHERE id = ?', array($del, $deltime, Auth::GetCurrentUser(), $queue));
	$DB->Execute('UPDATE rttickets SET deleted=?, deluserid=? WHERE deleted=? and queueid = ?', array($del, Auth::GetCurrentUser(), $nodel, $queue));
	if ($deltickets = $DB->GetCol('SELECT id FROM rttickets WHERE queueid = ?', array($queue)))
	{
		foreach ($deltickets as $delticket) {
			$DB->Execute('UPDATE rtmessages SET deleted=?, deluserid=? WHERE deleted=? and ticketid = ?', array($del, Auth::GetCurrentUser(), $nodel, $delticket));
		}
	}
	$DB->CommitTrans();
}

$SESSION->redirect('?m=rtqueuelist');

?>
