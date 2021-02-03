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

$cid = intval($_GET['id']);

if ($cid) {
    $channel = $DB->GetRow('SELECT c.*, c2.id AS cid
        FROM ewx_channels c
        LEFT JOIN ewx_stm_channels c2 ON (c.id = c2.cid)
        WHERE c.id = ?', array($cid));
} else {
    $channel = $DB->GetRow('SELECT 0 AS id, ch.upceil, ch.downceil,
        ch.halfduplex, ch.id AS cid
        FROM ewx_stm_channels ch
        WHERE ch.cid = 0');
}

if (!$channel) {
    $SESSION->redirect('?m=ewxchlist');
}

$layout['pagetitle'] = trans('Info Channel: $a', $channel['name']);

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

if ($channel['id']) {
    $channel['devices'] = $DB->GetAll('SELECT nd.id, nd.name, location,
        (SELECT COUNT(*) FROM vnodes WHERE netdev = nd.id AND ownerid IS NOT NULL) AS nodes
	    FROM netdevices nd
	    LEFT JOIN vaddresses a ON a.id = nd.address_id
    	WHERE channelid = ? ORDER BY nd.name', array($channel['id']));

    $channel['freedevices'] = $DB->GetAll('SELECT nd.id, nd.name, location, producer
	    FROM netdevices nd
	    LEFT JOIN vaddresses a ON a.id = nd.address_id
    	WHERE channelid IS NULL ORDER BY nd.name');
} else {
    // default channel
    $channel['devices'] = $DB->GetAll('SELECT nd.id, nd.name, location,
        (SELECT COUNT(*) FROM vnodes WHERE netdev = nd.id AND ownerid IS NOT NULL) AS nodes
	    FROM netdevices nd
	    LEFT JOIN vaddresses a ON a.id = nd.address_id
	    WHERE nd.id IN (
            SELECT netdev
            FROM vnodes
            WHERE netdev IS NOT NULL AND id IN (
                SELECT nodeid
                FROM ewx_stm_nodes
                WHERE channelid IN (SELECT id FROM ewx_stm_channels
                    WHERE cid = 0)))
	    ORDER BY nd.name', array($channel['id']));
}

$channel['devcnt'] = count($channel['devices']);
$channel['nodecnt'] = $DB->GetOne('SELECT COUNT(*) FROM ewx_stm_nodes n
    WHERE channelid = ?', array($channel['cid']));

$SMARTY->assign('channel', $channel);
$SMARTY->display('ewxch/ewxchinfo.html');
