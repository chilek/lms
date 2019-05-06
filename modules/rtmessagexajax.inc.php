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

function queue_changed($queue) {
	global $LMS, $SMARTY;

	$JSResponse = new xajaxResponse();
	if(empty($queue))
		return $JSResponse;

	$templates = $LMS->GetMessageTemplatesByQueueAndType($queue, RTMESSAGE_REGULAR);
	if ($templates) {
		$SMARTY->assign('templates', $templates);
		$JSResponse->assign('message-templates', 'innerHTML', $SMARTY->fetch('rt/rtmessagetemplates.html'));
		$JSResponse->assign('message-template-row', 'style', '');
	} else {
		$JSResponse->assign('message-template-row', 'style', 'display: none;');
	}

	$vid = $LMS->GetQueueVerifier($queue);

	if(empty($vid))
		return $JSResponse;

	$userlist = $LMS->GetUserNames();

	$SMARTY->assign('userlist', $userlist);
	$SMARTY->assign('ticket', array('verifierid'=>$vid));
	$content = $SMARTY->fetch('rt/rtverifiers.html');

	$JSResponse->assign('rtverifiers','innerHTML', $content);

	return $JSResponse;
}

$LMS->RegisterXajaxFunction(array('queue_changed'));
