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

$channelid = intval($_GET['id']);

if ($channelid)
    $where = 'WHERE d.channelid = '.$channelid;
else // default channel
    $where = 'WHERE d.id IN (SELECT netdev
        FROM nodes
        WHERE netdev IS NOT NULL AND id IN (
            SELECT nodeid
            FROM ewx_stm_nodes
            WHERE channelid IN (SELECT id FROM ewx_stm_channels
                WHERE cid = 0)))';

$devices = $DB->GetAll('SELECT d.id, d.name, d.producer,
        d.model, d.location
    FROM netdevices d '.$where);

$SMARTY->assign('devices', $devices);
$SMARTY->display('netdev/netdevlistshort.html');

?>
