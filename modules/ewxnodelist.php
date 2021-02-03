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

$nodes = $DB->GetAll('SELECT n.id, n.name, n.mac,
        inet_ntoa(n.ipaddr) AS ip, inet_ntoa(n.ipaddr_pub) AS ip_pub,
        n.access, n.warning, n.info, n.lastonline
    FROM vnodes n
    JOIN ewx_stm_nodes s ON (s.nodeid = n.id)
    JOIN ewx_stm_channels c ON (s.channelid = c.id)
    WHERE c.cid = ?', array($_GET['id']));

if ($nodes) {
    foreach ($nodes as $idx => $row) {
        $nodes[$idx]['lastonlinedate'] = lastonline_date($row['lastonline']);
    }
}

$SMARTY->assign('customernodes', $nodes);
$SMARTY->display('node/nodelistshort.html');
