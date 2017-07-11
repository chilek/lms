<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2016 LMS Developers
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

if(!($ticketid = $DB->GetOne('SELECT ticketid FROM rtmessages WHERE id = ? AND type = ?',
	array($_GET['id'], RTMESSAGE_NOTE))))
	$SESSION->redirect('?'.$SESSION->get('backto'));

$rights = $LMS->GetUserRightsRT($AUTH->id, 0, $ticketid);

if (($rights & 4) != 4) {
	$SMARTY->display('noaccess.html');
	$SESSION->close();
	die;
}

$msg = intval($_GET['id']);
$naction = ($_GET['naction']);

if ($DB->GetOne('SELECT MIN(id) FROM rtmessages WHERE ticketid = ?', array($ticketid)) != $msg && $naction== 'delperm') {
	       $mail_dir = ConfigHelper::getConfig('rt.mail_dir');
	       if (!empty($mail_dir))
		               rrmdir($mail_dir . DIRECTORY_SEPARATOR . sprintf('%06d' . DIRECTORY_SEPARATOR . '%06d', $ticketid, $msg));
		
		       $DB->Execute('DELETE FROM rtmessages WHERE id = ? AND type = ?',
				               array($msg, RTMESSAGE_NOTE));
		}

if ($naction == 'delete')
{
$del = 1;
$deltime = time();
$DB->Execute('UPDATE rtmessages SET deleted=?, deltime=?, deluserid=? WHERE id = ? AND type = ?', array($del, $deltime, $AUTH->id, $msg, RTMESSAGE_NOTE));

$SESSION->redirect('?m=rtticketview&id=' . $ticketid);
}

if ($naction == 'restore')
{
	$del = 0;
	$deltime = 0;
	$deluserid = 0;
	$DB->Execute('UPDATE rtmessages SET deleted=?, deltime=?, deluserid=? WHERE id = ? AND type = ?', array($del, $deltime, $deluserid, $msg, RTMESSAGE_NOTE));
	
	$SESSION->redirect('?m=rtticketview&id=' . $ticketid);
}

$SESSION->redirect('?m=rtticketview&id=' . $ticketid);


?>
