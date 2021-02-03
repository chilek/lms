<?php

/*
 *  LMS version 1.11-git
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

if (defined('USERPANEL_SETUPMODE')) {
    function module_setup()
    {
        global $SMARTY,$LMS;
        $SMARTY->assign('owner_stats', ConfigHelper::getConfig('userpanel.owner_stats'));

        $SMARTY->display('module:stats:setup.html');
    }

    function module_submit_setup()
    {
        global $SMARTY,$DB;
        if ($_POST['owner_stats']) {
            $DB->Execute('UPDATE uiconfig SET value = \'1\' WHERE section = \'userpanel\' AND var = \'owner_stats\'');
        } else {
            $DB->Execute('UPDATE uiconfig SET value = \'0\' WHERE section = \'userpanel\' AND var = \'owner_stats\'');
        }
        header('Location: ?m=userpanel&module=stats');
    }
}
                                    
function Traffic($from = 0, $to = 0, $owner = 0, $order = '')
{
    global $LMS, $DB;

    if ($owner) {
        $owner = ' AND ownerid = '.$owner;
    }

    // period
    if (is_array($from)) {
        $fromdate = mktime($from['Hour'], $from['Minute'], 0, $from['Month'], $from['Day'], $from['Year']);
    } else {
        $fromdate = $from;
    }
    if (is_array($to)) {
        $todate = mktime($to['Hour'], $to['Minute'], 59, $to['Month'], $to['Day'], $to['Year']);
    } else {
        $todate = $to;
    }

    $delta = ($todate-$fromdate) ? ($todate-$fromdate) : 1;

    if ($from || $to) {
        $dt = " AND ( dt >= $fromdate AND dt <= $todate )";
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

    // join query from parts
    $query = 'SELECT nodeid, name, inet_ntoa(ipaddr) AS ip, sum(upload) as upload, sum(download) as download 
		    FROM stats 
		    LEFT JOIN nodes ON stats.nodeid=nodes.id 
		    WHERE 1=1 '
            .($dt ? $dt : '')
            .($owner ? $owner : '')
            .' GROUP BY nodeid, name, ipaddr '.$order;

    // get results
    if ($traffic = $LMS->DB->GetAll($query)) {
        $downloadsum = 0;
        $uploadsum = 0;
        
        foreach ($traffic as $idx => $row) {
            $traffic['upload']['data'][] = $row['upload'];
            $traffic['download']['data'][] = $row['download'];
            $traffic['upload']['name'][] = ($row['name'] ? $row['name'] : 'nieznany (ID: '.$row['nodeid'].')');
            $traffic['download']['name'][] = ($row['name'] ? $row['name'] : 'nieznany (ID: '.$row['nodeid'].')');
            $traffic['upload']['ipaddr'][] = $row['ip'];
            $traffic['download']['nodeid'][] = $row['nodeid'];
            $traffic['upload']['nodeid'][] = $row['nodeid'];
            $traffic['download']['ipaddr'][] = $row['ip'];
            $downloadsum += $row['download'];
            $uploadsum += $row['upload'];
            $traffic['upload']['avg'][] = ($row['upload']*8)/($delta*1000);
            $traffic['download']['avg'][] = ($row['download']*8)/($delta*1000);
        }

        $traffic['upload']['sum']['data'] = $uploadsum;
        $traffic['download']['sum']['data'] = $downloadsum;
        $traffic['upload']['avgsum'] = ($uploadsum*8)/($delta*1000);
        $traffic['download']['avgsum'] = ($downloadsum*8)/($delta*1000);
        
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
            $down = round($data * 150 / $maximum);
            $traffic['download']['bar'][] = $down ? $down : 1;
            list($traffic['download']['data'][$x], $traffic['download']['unit'][$x]) = setunits($data);
            $x++;
        }
        $x = 0;

        foreach ($traffic['upload']['data'] as $data) {
            $up = round($data * 150 / $maximum);
            $traffic['upload']['bar'][] = $up ? $up : 1;
            list($traffic['upload']['data'][$x], $traffic['upload']['unit'][$x]) = setunits($data);
            $x++;
        }

        //set units for data
        list($traffic['download']['sum']['data'], $traffic['download']['sum']['unit']) = setunits($traffic['download']['sum']['data']);
        list($traffic['upload']['sum']['data'], $traffic['upload']['sum']['unit']) = setunits($traffic['upload']['sum']['data']);
    }

    return $traffic;
}

function module_main()
{
    global $SMARTY, $SESSION;
    $bars = 1;

    if (isset($_GET['bar']) && isset($_POST['order'])) {
        $SESSION->save('trafficorder', $_POST['order']);
    }

    $bar = isset($_GET['bar']) ? $_GET['bar'] : '';
    $owner = ConfigHelper::checkConfig('userpanel.owner_stats') ? $SESSION->id : null;

    switch ($bar) {
        case 'hour':
            $traffic = Traffic(time()-(60*60), time(), $owner, 'download');
            break;

        case 'day':
            $traffic = Traffic(time()-(60*60*24), time(), $owner, 'download');
            break;

        case 'year':
            $traffic = Traffic(time()-(60*60*24*365), time(), $owner, 'download');
            break;

        case 'all':
            $traffic = Traffic(0, time(), $owner, 'download');
            break;

        case 'month':
        default:
            $traffic = Traffic(time()-(60*60*24*30), time(), $owner, 'download');
            break;
    }

    if (isset($traffic)) {
        $SMARTY->assign('download', $traffic['download']);
        $SMARTY->assign('upload', $traffic['upload']);
    }

    $layout['pagetitle'] = trans('Network Statistics');

    $SMARTY->assign('bar', $bar ? $bar : 'month');
    $SMARTY->display('module:stats.html');
}
