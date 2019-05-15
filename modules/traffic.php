<?php

/* LMS version 1.11-git
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

function Traffic($from = 0, $to = 0, $net = 0, $customerid = 0, $order = '', $limit = 0)
{
    global $DB, $LMS;
    
    // period
    $fromdate = intval($from);
    $todate = intval($to);
    $delta = ($todate-$fromdate) ? ($todate-$fromdate) : 1;

    $dt = "( dt >= $fromdate AND dt < $todate ) ";

    // nets
    if ($net) {
        $params = $LMS->GetNetworkParams($net);
        $params['address']++;
        $params['broadcast']--;
        $net = ' AND (( ipaddr > '.$params['address'].' AND ipaddr < '.$params['broadcast'].')
			OR ( ipaddr_pub > '.$params['address'].' AND ipaddr_pub < '.$params['broadcast'].')) ';
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
        $limit = ' LIMIT '.intval($limit);
    } else {
        $limit = '';
    }

    // join query from parts
    $query = 'SELECT nodeid, name, inet_ntoa(ipaddr) AS ip, 
			    sum(upload) as upload, sum(download) as download 
		    FROM stats 
		    LEFT JOIN nodes ON stats.nodeid = nodes.id 
		    WHERE '
            .$dt
            .$net
            .($customerid ? ' AND ownerid = '.intval($customerid) : '')
            .' GROUP BY nodeid, name, ipaddr'
            .$order.$limit;

    // get results
    if ($traffic = $DB->GetAll($query)) {
        $downloadsum = 0;
        $uploadsum = 0;

        foreach ($traffic as $idx => $row) {
            $traffic['upload']['data'][] = $row['upload'];
            $traffic['download']['data'][] = $row['download'];
            $traffic['upload']['avg'][] = $row['upload']*8/($delta*1000);
            $traffic['download']['avg'][] = $row['download']*8/($delta*1000);
            $traffic['upload']['name'][] = ($row['name'] ? $row['name'] : trans('unknown').' (ID: '.$row['nodeid'].')');
            $traffic['download']['name'][] = ($row['name'] ? $row['name'] : trans('unknown').' (ID: '.$row['nodeid'].')');
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

$layout['pagetitle'] = trans('Network Statistics');

$bars = 1;

if (isset($_GET['bar'])) {
    if (isset($_POST['order'])) {
        $SESSION->save('trafficorder', $_POST['order']);
    }
    if (isset($_POST['net'])) {
        $SESSION->save('trafficnet', $_POST['net']);
    }
    if (isset($_POST['customerid'])) {
        $SESSION->save('trafficcustid', $_POST['customerid']);
    }
}

$bar = isset($_GET['bar']) ? $_GET['bar'] : '';

switch ($bar) {
    case 'hour':
        $traffic = Traffic(
            time()-(60*60),
            time(),
            $SESSION->is_set('trafficnet') ? $SESSION->get('trafficnet') : 0,
            $SESSION->is_set('trafficcustid') ? $SESSION->get('trafficcustid') : 0,
            $SESSION->is_set('trafficorder') ? $SESSION->get('trafficorder') : 'download'
        );
        break;

    case 'day':
        $traffic = Traffic(
            time()-(60*60*24),
            time(),
            $SESSION->is_set('trafficnet') ? $SESSION->get('trafficnet') : 0,
            $SESSION->is_set('trafficcustid') ? $SESSION->get('trafficcustid') : 0,
            $SESSION->is_set('trafficorder') ? $SESSION->get('trafficorder') : 'download'
        );
        break;

    case 'month':
        $traffic = Traffic(
            time()-(60*60*24*30),
            time(),
            $SESSION->is_set('trafficnet') ? $SESSION->get('trafficnet') : 0,
            $SESSION->is_set('trafficcustid') ? $SESSION->get('trafficcustid') : 0,
            $SESSION->is_set('trafficorder') ? $SESSION->get('trafficorder') : 'download'
        );
        break;

    case 'year':
        $traffic = Traffic(
            time()-(60*60*24*365),
            time(),
            $SESSION->is_set('trafficnet') ? $SESSION->get('trafficnet') : 0,
            $SESSION->is_set('trafficcustid') ? $SESSION->get('trafficcustid') : 0,
            $SESSION->is_set('trafficorder') ? $SESSION->get('trafficorder') : 'download'
        );
        break;

    case 'user':
        $from = !empty($_POST['from']) ? $_POST['from'] : time()-(60*60*24);
        $to = !empty($_POST['to']) ? $_POST['to'] : time();
        $net = !empty($_POST['net']) ? $_POST['net'] : 0;
        $customer = !empty($_POST['customerid']) ? $_POST['customerid'] : 0;

        if (is_array($from)) {
                    $from = mktime($from['Hour'], $from['Minute'], 0, $from['Month'], $from['Day'], $from['Year']);
        }
        if (is_array($to)) {
                $to = mktime($to['Hour'], $to['Minute'], 0, $to['Month'], $to['Day'], $to['Year']);
        }

        $SMARTY->assign('datefrom', $from);
        $SMARTY->assign('dateto', $to);
        $SMARTY->assign('net', $net);
        $SMARTY->assign('customer', $customer);

        $traffic = Traffic(
            $from,
            $to,
            $net,
            $customer,
            isset($_POST['order']) ? $_POST['order'] : '',
            isset($_POST['limit']) ? $_POST['limit'] : 0
        );
        break;

    default: // set filter window
        $SMARTY->assign('netlist', $LMS->GetNetworks());
        $SMARTY->assign('nodelist', $LMS->GetNodeList());
        if (!ConfigHelper::checkConfig('phpui.big_networks')) {
            $SMARTY->assign('customers', $LMS->GetCustomerNames());
        }
        $bars = 0;
        break;
}

if (isset($traffic)) {
    $SMARTY->assign('download', $traffic['download']);
    $SMARTY->assign('upload', $traffic['upload']);
}

// fuck this anyway... Maybe i write function in LMS:: for this, but not now

$starttime = $DB->GetOne('SELECT MIN(dt) FROM stats');
$endtime = $DB->GetOne('SELECT MAX(dt) FROM stats');

// if 'stats' table is empty use fixed values for time ranges
if (empty($starttime)) {
    $starttime = time()-(3600*24);
    $endtime = time();
    $startyear = 2001;
    $endyear = date('Y', $endtime);
} else {
    $startyear = date('Y', $starttime);
    $endyear = date('Y', $endtime);
}

$SMARTY->assign('starttime', $starttime);
$SMARTY->assign('startyear', $startyear);
$SMARTY->assign('endtime', $endtime);
$SMARTY->assign('endyear', $endyear);
$SMARTY->assign('showips', isset($_POST['showips']) ? true : false);
$SMARTY->assign('bars', $bars);
$SMARTY->assign('bar', $bar);
$SMARTY->assign('trafficorder', $SESSION->is_set('trafficorder') ? $SESSION->get('trafficorder') : 'download');
$SMARTY->assign('trafficnet', $SESSION->is_set('trafficnet') ? $SESSION->get('trafficnet') : 0);
$SMARTY->display('traffic/traffic.html');
