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

$month = $_POST['month'] ? $_POST['month'] : date('n');
$year = $_POST['year'] ? $_POST['year'] : date('Y');
$customer = $_POST['customer'] ? $_POST['customer'] : $_GET['customer'];

$layout['pagetitle'] = trans('Stats of Customer $a in month $b', $LMS->GetCustomerName($customer), strftime('%B %Y', mktime(0, 0, 0, $month, 1, $year)));
    
$from = mktime(0, 0, 0, $month, 1, $year);
$to = mktime(0, 0, 0, $month+1, 1, $year);

if ($list = $DB->GetAll(
    'SELECT download, upload, dt
            	    FROM stats
		    LEFT JOIN nodes ON (nodeid = nodes.id)
		    WHERE ownerid = ? AND dt >= ? AND dt < ?',
    array($customer, $from, $to)
)) {
    for ($i=1; $i<=date('t', $from); $i++) {
        $stats[$i]['date'] = mktime(0, 0, 0, $month, $i, $year);
    }
        
    foreach ($list as $row) {
        $day = date('j', $row['dt']);
        
        $stats[$day]['download'] += $row['download'];
        $stats[$day]['upload'] += $row['upload'];
    }
            
    for ($i=1; $i<=date('t', $from); $i++) {
        $stats[$i]['upavg'] = $stats[$i]['upload']*8/1000/86400; //kbit/s
        $stats[$i]['downavg'] = $stats[$i]['download']*8/1000/86400; //kbit/s
        
        $listdata['upload'] += $stats[$i]['upload'];
        $listdata['download'] += $stats[$i]['download'];
        $listdata['upavg'] += $stats[$i]['upavg'];
        $listdata['downavg'] += $stats[$i]['downavg'];
                
        list($stats[$i]['upload'], $stats[$i]['uploadunit']) = setunits($stats[$i]['upload']);
        list($stats[$i]['download'], $stats[$i]['downloadunit']) = setunits($stats[$i]['download']);
    }

    $listdata['upavg'] = $listdata['upavg']/date('t', $from);
    $listdata['downavg'] = $listdata['downavg']/date('t', $from);
    list($listdata['upload'], $listdata['uploadunit']) = setunits($listdata['upload']);
    list($listdata['download'], $listdata['downloadunit']) = setunits($listdata['download']);
}

$SMARTY->assign('stats', $stats);
$SMARTY->assign('listdata', $listdata);

clearheader();
