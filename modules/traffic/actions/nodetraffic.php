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

function NodeStats($id, $dt)
{
    global $DB;
    if ($stats = $DB->GetRow(
        'SELECT SUM(download) AS download, SUM(upload) AS upload 
			    FROM stats WHERE nodeid=? AND dt>?',
        array($id, time()-$dt)
    )) {
        list($result['download']['data'], $result['download']['units']) = setunits($stats['download']);
        list($result['upload']['data'], $result['upload']['units']) = setunits($stats['upload']);
        $result['downavg'] = $stats['download']*8/1000/$dt;
        $result['upavg'] = $stats['upload']*8/1000/$dt;
    }
    return $result;
}

$nodeid = $_GET['id'];

$nodestats['hour'] = NodeStats($nodeid, 60*60);
$nodestats['day'] = NodeStats($nodeid, 60*60*24);
$nodestats['month'] = NodeStats($nodeid, 60*60*24*30);

$SMARTY->assign('nodestats', $nodestats);

register_plugin('nodes-infobox-end', '../modules/traffic/templates/nodetraffic.html');
