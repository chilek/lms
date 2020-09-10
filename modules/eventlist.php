<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2018 LMS Developers
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

// ajax request handling
if (isset($_GET['action']) && $_GET['action'] == 'eventmove') {
    if (!isset($_GET['id']) || !isset($_GET['delta'])) {
        die;
    }
    $LMS->MoveEvent($_GET['id'], $_GET['delta']);
    header('Content-Type: application/json');
    die('[]');
}

if (isset($filter['edate']) && !empty($filter['edate'])) {
    list ($filter['year'], $filter['month'], $filter['day']) = explode('/', $filter['edate']);
}

if (!isset($_POST['loginform']) && !empty($_POST)) {
    list ($filter['year'], $filter['month'], $filter['day']) = explode('/', isset($_POST['date']) && !empty($_POST['date']) ? $_POST['date'] : date('Y/m/d'));

    if ($filter['edate']) {
        if (empty($filter['month'])) {
            if ($filter['month'] != $_POST['month']) {
                $filter['day'] = 1;
            }
        }
        if (empty($filter['year'])) {
            if ($filter['year'] != $_POST['year']) {
                $filter['day'] = 1;
            }
        }
    } else {
        $day = date('j', time());
    }

    $filter['userand'] = isset($_POST['userand']) ? intval($_POST['userand']) : 0;
    $filter['userid'] = isset($_POST['a']) ? $_POST['a'] : null;
    $filter['customerid'] = isset($_POST['u']) ? $_POST['u'] : null;
    $filter['type'] = isset($_POST['type']) ? $_POST['type'] : null;
    $filter['privacy'] = isset($_POST['privacy']) ? intval($_POST['privacy']) : null;
    $filter['closed'] = isset($_POST['closed']) ? $_POST['closed'] : null;
} else {
    if ($SESSION->is_set('eld')) {
        $filter = array_merge($filter, $SESSION->get('eld'));
    }

    if (isset($_GET['day']) && isset($_GET['month']) && isset($_GET['year'])) {
        if (isset($_GET['day'])) {
            $filter['day'] = $_GET['day'];
        } elseif ($filter['edate']) {
            if ($filter['month'] != $_GET['month'] || $filter['year'] != $_GET['year']) {
                $filter['day'] = 1;
            }
        } else {
            $filter['day'] = 1;
        }

        if (isset($_GET['month'])) {
            $filter['month'] = $_GET['month'];
        }

        if (isset($_GET['year'])) {
            $filter['year'] = $_GET['year'];
        }
    }

    $filter['userand'] = isset($_GET['userand']) ? intval($_GET['userand']) : 0;

    if (isset($_GET['a'])) {
        $filter['userid'] = $_GET['a'];
    }

    if (isset($_GET['u'])) {
        $filter['customerid'] = $_GET['u'] == 'all' ? null : $_GET['u'];
    }

    if (isset($_GET['type'])) {
        $filter['type'] = $_GET['type'] == 'all' ? null : $_GET['type'];
    }

    if (isset($_GET['privacy'])) {
        $filter['privacy'] = $_GET['privacy'] == 'all' ? null : $_GET['privacy'];
    }

    if (isset($_GET['closed'])) {
        $filter['closed'] = $_GET['closed'] = 'all' ? '' : $_GET['closed'];
    }
}

if (isset($filter['year']) && isset($filter['month']) && isset($filter['day'])) {
    $filter['edate'] = sprintf('%04d/%02d/%02d', $filter['year'], $filter['month'], $filter['day']);
}

$SESSION->save('eld', array(
    'year' => $filter['year'],
    'month' => $filter['month'],
    'day' => $filter['day'],
    'edate' => $filter['edate'],
));

$SESSION->saveFilter($filter, null, array('year', 'month', 'day', 'edate'), true);

if (!isset($filter['day'])) {
    $filter['day'] = date('j');
}

if (!isset($filter['month'])) {
    $filter['month'] = date('m');
}

if (!isset($filter['year'])) {
    $filter['year'] = date('Y');
}

$layout['pagetitle'] = trans('Timetable');

$filter['forward'] = ConfigHelper::getConfig('phpui.timetable_days_forward');
$eventlist = $LMS->GetEventList($filter);

$overdue_events_only = isset($_GET['overdue_events_only']) ? 1 : 0;
$overdue_events = array();

if (ConfigHelper::checkConfig('phpui.timetable_overdue_events') && empty($overdue_events_only)) {
    $params['userid'] = Auth::GetCurrentUser();
    $params['forward'] = -1;
    $params['closed'] = 0;
    $params['type'] = 0;
    $params['count'] = true;
    $count = $LMS->GetEventList($params);
    $params['count'] = false;
    if ($count > 100) {
        $params['limit'] = 100;
        $SMARTY->assign('overdue_limited', 1);
    } else {
        $params['limit'] = $count;
    }
    $overdue_events = $LMS->GetEventList($params);
} elseif (!empty($overdue_events_only)) {
    unset($params['userid']);
    $params['forward'] = -1;
    $params['closed'] = 0;
    $params['type'] = 0;
    $params['count'] = true;
    $count = $LMS->GetEventList($params);
    $params['count'] = false;
    if ($count > 100) {
        $params['limit'] = 100;
        $SMARTY->assign('overdue_limited', 1);
    } else {
        $params['limit'] = $count;
    }
    $overdue_events = $LMS->GetEventList($params);
}

// create calendars
for ($i = 0; $i < ConfigHelper::getConfig('phpui.timetable_days_forward'); $i++) {
    $dt = mktime(0, 0, 0, $filter['month'], $filter['day'] + $i, $filter['year']);
    $daylist[$i] = $dt;
}

$date = mktime(0, 0, 0, $filter['month'], $filter['day'], $filter['year']);
$daysnum = date('t', $date);
for ($i = 1; $i < $daysnum + 1; $i++) {
    $date = mktime(0, 0, 0, $filter['month'], $i, $filter['year']);
    $days['day'][] = date('j', $date);
    $days['dow'][] = date('w', $date);
    $days['sel'][] = ($i == $filter['day']);
}

$SESSION->save('backto', $_SERVER['QUERY_STRING']);
$SESSION->remove('backid');

$today = mktime(0, 0, 0, date('n'), date('j'), date('Y'));
$SMARTY->assign('today', $today);

$SMARTY->assign('period', $DB->GetRow('SELECT MIN(date) AS fromdate, MAX(date) AS todate FROM events'));
$SMARTY->assign('eventlist', $eventlist);
$SMARTY->assign('overdue_events', $overdue_events);

$SMARTY->assign('days', $days);
$SMARTY->assign('daylist', $daylist);
$SMARTY->assign('date', $date);
$SMARTY->assign('error', $error);
$SMARTY->assign('userlist', $LMS->GetUserNames());
$SMARTY->assign('overdue_events_only', $overdue_events_only);
if (!ConfigHelper::checkConfig('phpui.big_networks')) {
    $SMARTY->assign('customerlist', $LMS->GetCustomerNames());
}
$SMARTY->assign('getHolidays', getHolidays($year));
$SMARTY->display('event/eventlist.html');
