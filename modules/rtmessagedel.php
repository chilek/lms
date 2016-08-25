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

$msg = intval($_GET['id']);
$ticket = $DB->GetOne('SELECT ticketid FROM rtmessages WHERE id = ?', array($msg));
$rights = $LMS->GetUserRightsRT($AUTH->id, 0, $ticket);

if(($rights & 4) != 4)
{
	$SMARTY->display('noaccess.html');
	$SESSION->close();
	die;
}

if($DB->GetOne('SELECT MIN(id) FROM rtmessages WHERE ticketid = ?', array($ticket)) != $msg)
{
	$mail_dir = ConfigHelper::getConfig('rt.mail_dir');
	if(!empty($mail_dir)) {
		rrmdir($mail_dir.sprintf('/%06d/%06d', $ticket, $msg));
	}

	$DB->Execute('DELETE FROM rtmessages WHERE id = ?', array($msg));
}

header('Location: ?m=rtticketview&id='.$ticket);

?>
