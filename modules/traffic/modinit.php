<?php

/* LMS version 1.11-git
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

function Traffic($from = 0, $to = 0, $net = 0, $order = '', $limit = 0)
{
    global $DB, $LMS;
    
    // period
    if (is_array($from)) {
        $fromdate = mktime($from['Hour'], $from['Minute'], 0, $from['Month'], $from['Day'], $from['Year']);
    } else {
        $fromdate = $from;
    }
    if (is_array($to)) {
        $todate = mktime($to['Hour'], $to['Minute'], 0, $to['Month'], $to['Day'], $to['Year']);
    } else {
        $todate = $to;
    }

    $delta = ($todate-$fromdate) ? ($todate-$fromdate) : 1;

    $dt = "( dt >= $fromdate AND dt < $todate )";

    // nets
    if ($net != 'allnets') {
        $params = $LMS->GetNetworkParams($net);
        $params['address']++;
        $params['broadcast']--;
        $net = ' AND (( ipaddr > '.$params['address'].' AND ipaddr < '.$params['broadcast'].') OR ( ipaddr_pub > '.$params['address'].' AND ipaddr_pub < '.$params['broadcast'].'))';
    } else {
        $net = '';
    }

    // order
    switch ($order) {
        case 'nodeid':
            $order = ' ORDER BY nodeid';
            break;
        case 'download':
            $order = ' ORDER BY download DESC';
            break;
        case 'upload':
            $order = ' ORDER BY upload DESC';
            break;
        case 'name':
            $order = ' ORDER BY name';
            break;
        case 'ip':
            $order = ' ORDER BY ipaddr';
            break;
    }

    // limits
    if ($limit > 0) {
        $limit = ' LIMIT '.$limit;
    } else {
        $limit = '';
    }
        
    // join query from parts
    $query = 'SELECT nodeid, name, inet_ntoa(ipaddr) AS ip, sum(upload) as upload, sum(download) as download 
		    FROM stats 
		    LEFT JOIN nodes ON stats.nodeid=nodes.id 
		    WHERE '.$dt.' '.$net.
            'GROUP BY nodeid, name, ipaddr '.$order.' '.$limit;

    // get results
    if ($traffic = $DB->GetAll($query)) {
        $downloadsum = 0;
        $uploadsum = 0;
        
        foreach ($traffic as $idx => $row) {
            $traffic['upload']['data'][] = $row['upload'];
            $traffic['download']['data'][] = $row['download'];
            $traffic['upload']['avg'][] = $row['upload']*8/($delta*1000);
            $traffic['download']['avg'][] = $row['download']*8/($delta*1000);
            $traffic['upload']['name'][] = ($row['name'] ? $row['name'] : 'nieznany (ID: '.$row['nodeid'].')');
            $traffic['download']['name'][] = ($row['name'] ? $row['name'] : 'nieznany (ID: '.$row['nodeid'].')');
            $traffic['upload']['ipaddr'][] = $row['ip'];
            $traffic['download']['nodeid'][] = $row['nodeid'];
            $traffic['upload']['nodeid'][] = $row['nodeid'];
            $traffic['download']['ipaddr'][] = $row['ip'];
            $downloadsum += $row['download'];
            $uploadsum += $row['upload'];
        }

        $traffic['upload']['sum']['data'] = $uploadsum;
        $traffic['download']['sum']['data'] = $downloadsum;
        $traffic['upload']['avgsum'] = $uploadsum*8/($delta*1000);
        $traffic['download']['avgsum'] = $downloadsum*8/($delta*1000);

        // get maximum data from array

        $maximum = max($traffic['download']['data']);
        if ($maximum < max($traffic['upload']['data'])) {
            $maximum = max($traffic['upload']['data']);
        }

        if ($maximum == 0) {       // do not need divide by zero
            $maximum = 1;
        }

        // make data for bars drawing
        $x = 0;

        foreach ($traffic['download']['data'] as $data) {
            $traffic['download']['bar'][] = round($data * 150 / $maximum);
            list($traffic['download']['data'][$x], $traffic['download']['unit'][$x]) = setunits($data);
            $x++;
        }
        $x = 0;

        foreach ($traffic['upload']['data'] as $data) {
            $traffic['upload']['bar'][] = round($data * 150 / $maximum);
            list($traffic['upload']['data'][$x], $traffic['upload']['unit'][$x]) = setunits($data);
            $x++;
        }

        //set units for data
        list($traffic['download']['sum']['data'], $traffic['download']['sum']['unit']) = setunits($traffic['download']['sum']['data']);
        list($traffic['upload']['sum']['data'], $traffic['upload']['sum']['unit']) = setunits($traffic['upload']['sum']['data']);
    }

    return $traffic;
}
