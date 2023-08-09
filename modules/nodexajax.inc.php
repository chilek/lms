<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2022 LMS Developers
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
    if ($stats = $DB->GetRow('SELECT SUM(download) AS download, SUM(upload) AS upload 
		FROM stats WHERE nodeid=? AND dt>?', array($id, time() - $dt))) {
            list($result['download']['data'], $result['download']['units']) = setunits($stats['download']);
            list($result['upload']['data'], $result['upload']['units']) = setunits($stats['upload']);
            $result['downavg'] = $stats['download'] * 8 / 1000 / $dt;
            $result['upavg'] = $stats['upload'] * 8 / 1000 / $dt;
    }
    return $result;
}

function getNodeLocks()
{
    global $SMARTY;

    $result = new xajaxResponse();

    $nodelocks = null;
    $nodeid = intval($_GET['id']);
    $DB = LMSDB::getInstance();
    $locks = $DB->GetAll(
        'SELECT id, days, fromsec, tosec, disabled FROM nodelocks WHERE nodeid = ? ORDER BY id',
        array($nodeid)
    );
    if ($locks) {
        foreach ($locks as $lock) {
            $fromsec = intval($lock['fromsec']);
            $tosec = intval($lock['tosec']);
            $days = intval($lock['days']);
            $lockdays = array();
            for ($i = 0; $i < 7; $i++) {
                if ($days & (1 << $i)) {
                    $lockdays[$i] = 1;
                }
            }
            $disabled = intval($lock['disabled']);
            $nodelocks[] = array('id' => $lock['id'], 'days' => $lockdays, 'fhour' => intval($fromsec / 3600), 'fminute' => intval(($fromsec % 3600) / 60),
                'thour' => intval($tosec / 3600), 'tminute' => intval(($tosec % 3600) / 60), 'disabled' => $disabled);
        }
    }
    $SMARTY->assign('nodelocks', $nodelocks);
    $nodelocklist = $SMARTY->fetch('node/nodelocklist.html');

    $result->assign('nodelocktable', 'innerHTML', $nodelocklist);
    $result->assign('nodelockaddlink', 'disabled', false);

    return $result;
}

function addNodeLock($params)
{
    $result = new xajaxResponse();

    if (empty($params)) {
        $result->assign('nodelockaddlink', 'disabled', false);
        return $result;
    }

    $formdata = array();
    parse_str($params, $formdata);

    $days = 0;
    foreach ($formdata['days'] as $key => $value) {
        $days += (1 << $key);
    }
    $fromsec = $formdata['fhour'] * 3600 + $formdata['fminute'] * 60;
    $tosec = $formdata['thour'] * 3600 + $formdata['tminute'] * 60;
    if ($fromsec >= $tosec || !$days) {
        $result->assign('nodelockaddlink', 'disabled', false);
        return $result;
    }

    $nodeid = intval($_GET['id']);

    $DB = LMSDB::getInstance();
    $DB->Execute(
        'INSERT INTO nodelocks (nodeid, days, fromsec, tosec) VALUES (?, ?, ?, ?)',
        array($nodeid, $days, $fromsec, $tosec)
    );

    $result->call('getNodeLocks');

    return $result;
}

function delNodeLock($id)
{
    $result = new xajaxResponse();

    $nodeid = intval($_GET['id']);

    $DB = LMSDB::getInstance();
    $DB->Execute('DELETE FROM nodelocks WHERE id = ?', array($id));

    $result->call('getNodeLocks');

    return $result;
}

function toggleNodeLock($id)
{
    $result = new xajaxResponse();

    $nodeid = intval($_GET['id']);

    $DB = LMSDB::getInstance();
    $DB->Execute('UPDATE nodelocks SET disabled = (CASE WHEN disabled = 0 THEN 1 ELSE 0 END) WHERE id = ?', array($id));

    $result->call('getNodeLocks');

    return $result;
}

function getThroughput($ip)
{
    $cmd = ConfigHelper::getConfig('phpui.live_traffic_helper');
    if (empty($cmd)) {
        return '';
    }

    $cmd = str_replace('%i', $ip, $cmd);
    exec($cmd, $output);
    if (!is_array($output) || count($output) != 1) {
        return '';
    }

    $stats = explode(' ', $output[0]);
    if (count($stats) != 4) {
        return '';
    }

    $speed_unit_type = ConfigHelper::getConfig('phpui.speed_unit_type', 1000);
    $speed_unit_aggregation_threshold = ConfigHelper::getConfig('phpui.speed_unit_aggregation_threshold', 5);

    array_walk($stats, 'intval');
    foreach (array(0, 2) as $idx) {
        $stats[$idx] = convert_to_units($stats[$idx], $speed_unit_aggregation_threshold, $speed_unit_type) . '/s';
    }

    return $stats[0] . ' / ' . $stats[2] . ' (' . $stats[1] . ' pps / ' . $stats[3] . ' pps)';
}

function getNodeStats($nodeid)
{
    global $SMARTY, $DB;

    $nodeid = intval($nodeid);

    $nodestats['hour'] = NodeStats($nodeid, 60 * 60);
    $nodestats['day'] = NodeStats($nodeid, 60 * 60 * 24);
    $nodestats['month'] = NodeStats($nodeid, 60 * 60 * 24 * 30);

    $SMARTY->assign('nodeid', $nodeid);
    $nodeip = $DB->GetOne('SELECT INET_NTOA(ipaddr) FROM vnodes WHERE id = ?', array($nodeid));
    $SMARTY->assign('nodeip', $nodeip);
    $SMARTY->assign('nodestats', $nodestats);

    $contents = $SMARTY->fetch('node/nodestats.html');

    return $contents;
}

include(MODULES_DIR . DIRECTORY_SEPARATOR . 'managementurls.inc.php');

function getManagementUrls()
{
    $result = new xajaxResponse();

    _getManagementUrls(LMSNetDevManager::NODE_URL, $result);

    return $result;
}

function addManagementUrl($params)
{
    return _addManagementUrl(LMSNetDevManager::NODE_URL, $params);
}

function delManagementUrl($id)
{
    return _delManagementUrl(LMSNetDevManager::NODE_URL, $id);
}

function updateManagementUrl($id, $params)
{
    return _updateManagementUrl(LMSNetDevManager::NODE_URL, $id, $params);
}

function getRadioSectors($netdev, $technology = 0)
{
    $result = new xajaxResponse();

    $lms = LMS::getInstance();
    $radiosectors = $lms->GetRadioSectors($netdev, $technology);

    $result->call('radio_sectors_received', $radiosectors);

    return $result;
}

function getFirstFreeAddress($netid, $elemid)
{
    global $LMS;

    $result = new xajaxResponse();

    $ip = $LMS->GetFirstFreeAddress($netid);
    if ($ip != false) {
        $result->assign($elemid, 'value', $ip);
        $result->script('
            $("#ipaddr").removeClass("lms-ui-warning").removeAttr("data-tooltip").attr("title", null);
        ');
    } else {
        $result->script('
            $("#ipaddr").addClass("lms-ui-warning").removeAttr("data-tooltip").attr("title",
                $t("No free addresses in selected network!"));
        ');
    }

    return $result;
}

if (isset($_GET['action'])) {
    header('Content-type: text/html');
    switch ($_GET['action']) {
        case 'get_node_stats':
            if (!isset($_GET['id'])) {
                die;
            }
            die(getNodeStats($_GET['id']));
            break;
        case 'get_throughput':
            if (!isset($_GET['ip']) || !isset($_GET['id'])) {
                die;
            }
            die(getThroughput($_GET['ip']));
            break;
    }
}

$LMS->RegisterXajaxFunction(array('getNodeLocks', 'addNodeLock', 'delNodeLock', 'toggleNodeLock',
    'getManagementUrls', 'addManagementUrl', 'delManagementUrl', 'updateManagementUrl', 'getRadioSectors',
    'getFirstFreeAddress'));
