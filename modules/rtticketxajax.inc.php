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

function GetCategories($queueid) {
	global $LMS;

	$result = new xajaxResponse();

	if (empty($queueid))
		return $result;

	$categories = $LMS->GetUserCategories(Auth::GetCurrentUser());
	if (empty($categories))
		return $result;

	$queuecategories = $LMS->GetQueueCategories($queueid);

	foreach ($categories as $category)
		$result->assign('cat' . $category['id'], 'checked', isset($queuecategories[$category['id']]));

	return $result;
}

function select_location($customerid, $address_id) {
	global $LMS;

	$JSResponse = new xajaxResponse();
	$nodes = $LMS->GetNodeLocations($customerid, !empty($address_id) && intval($address_id) > 0 ? $address_id : null);
	if (empty($nodes))
		$nodes = array();
	$JSResponse->call('update_nodes', array_values($nodes));
	return $JSResponse;
}

function netnode_changed($netnodeid, $netdevid) {
	global $LMS, $SMARTY;

	$JSResponse = new xajaxResponse();

	$search = array();
	if (!empty($netnodeid))
		$search['netnode'] = $netnodeid;
	$netdevlist = $LMS->GetNetDevList('name', $search);
	unset($netdevlist['total']);
	unset($netdevlist['order']);
	unset($netdevlist['direction']);

	$SMARTY->assign('netdevlist', $netdevlist);
	$SMARTY->assign('ticket', array('netdevid' => $netdevid));
	$SMARTY->assign('form', 'ticket');
	$content = $SMARTY->fetch('rt' . DIRECTORY_SEPARATOR . 'rtnetdevs.html');
	$JSResponse->assign('rtnetdevs', 'innerHTML', $content);

	return $JSResponse;
}

function queue_changed($queue) {
    global $LMS, $SMARTY;

    $JSResponse = new xajaxResponse();
    if(empty($queue))
        return $JSResponse;

	$SMARTY->assign('messagetemplates', $LMS->GetMessageTemplatesForQueue($queue));
	$JSResponse->assign('message-templates', 'innerHTML', $SMARTY->fetch('rt/rtmessagetemplates.html'));

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

$LMS->RegisterXajaxFunction(array('GetCategories', 'select_location', 'netnode_changed', 'queue_changed'));

?>
