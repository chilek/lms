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

function hoursRange($lower, $upper, $step)
{
    $times = array();

    foreach (range($lower, $upper, $step) as $increment) {
        $nextincrement = gmdate('H:i', ($increment + $step));
        $times[strval($increment)] = gmdate('H:i', $increment).'-'.$nextincrement;
    }

    return $times;
}

function parseWorkingHours($hours_period)
{
    $working_hours_ts = array();

    list($begin,$end) = explode('-', $hours_period);
    $parsed_begin = date_parse($begin);
    $parsed_end = date_parse($end);
    $working_hours_ts['begin'] = $parsed_begin['hour'] * 3600 + $parsed_begin['minute'] * 60;
    $working_hours_ts['end'] = $parsed_end['hour'] * 3600 + $parsed_end['minute'] * 60 - 1;

    return $working_hours_ts;
}

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

$SESSION->saveFilter($filter);

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


$userid = $filter['userid'];
$userlist = $LMS->GetUserNames();
$usereventlist = array();
if (!isset($userid) || empty($userid)) {
    unset($filter['userid']);
    foreach ($userlist as $user) {
        $filter['userid'] = $user['id'];
        $usereventlist[$user['id']]['events'] = $LMS->GetEventList($filter);
        $usereventlist[$user['id']]['username'] = $user['name'];
    }
    unset($filter['userid']);
    $filter['userid'] = '-1';
    $usereventlist[-1]['events'] = $LMS->GetEventList($filter);
    $usereventlist[-1]['username'] = trans("unassigned");

    $filter['userid'] = $userid;
} else if (is_array($userid)) {
    if (in_array('-1', $userid)) {
        $usereventlist[$user['id']]['events'] = $LMS->GetEventList($filter);
        $usereventlist[$user['id']]['username'] = trans("unassigned");
    } else {
        unset($filter['userid']);
        foreach ($userlist as $user) {
            if (in_array($user['id'], $userid)) {
                $filter['userid'] = $user['id'];
                $usereventlist[$user['id']]['events'] = $LMS->GetEventList($filter);
                $usereventlist[$user['id']]['username'] = $user['name'];
            }
        }
        $filter['userid'] = $userid;
    }
}

$usereventlistcount = count($usereventlist);

//<editor-fold desc="group events by days">
$usereventlistdates = array();
foreach ($usereventlist as $userid => $userevents) {
    $usereventlistdates[$userid]['username'] = $userevents['username'];
    if ($userevents['events']) {
        foreach ($userevents['events'] as $event) {
            $usereventlistdates[$userid]['events'][$event['date']][] = $event;
        }
    } else {
        $usereventlistdates[$userid]['events'] = array();
    }
}
//</editor-fold>

$working_hours_interval = ConfigHelper::getConfig('phpui.timetable_working_hours_interval', 30);
$working_hours_interval_ts = $working_hours_interval * 60;
$working_hours = ConfigHelper::getConfig('phpui.timetable_working_hours', '08:00-19:00');
$working_hours_ts = parseWorkingHours($working_hours);
$times = hoursRange($working_hours_ts['begin'], $working_hours_ts['end'], $working_hours_interval_ts);
$SMARTY->assign('times', $times);

//<editor-fold desc="set events in columns">
$usereventlistgrid = $usereventlistdates;
$allevents = 0;
foreach ($usereventlistgrid as $guserid => $guserevents) {
    foreach ($guserevents['events'] as $gdekey => $gdateevent) {
            $allevents += count($gdateevent);
    }
}

$column = 0;
do {
    foreach ($usereventlistgrid as $guserid => $guserevents) {
        if (count($guserevents['events']) != 0) {
            foreach ($guserevents['events'] as $gdekey => $gdateevent) {
                $last_gevent_endtime = 0;
                $last_gevent_begintime = 0;
                foreach ($gdateevent as $gekey => $gevent) {
                    if ($gekey == 0) {
                        $usereventlistgrid[$guserid]['addedevents'][$gevent['date']]['columns'][$column][$gevent['id']] = $gevent;
                        unset($usereventlistgrid[$guserid]['events'][$gevent['date']][$gekey]);
                        $allevents --;
                    } else {
                        $addedcolumn = $usereventlistgrid[$guserid]['addedevents'][$gevent['date']]['columns'][$column];
                        foreach ($addedcolumn as $addedevent) {
                            if ($gevent['begintime'] >= $addedevent['endtime'] || $gevent['endtime'] <= $addedevent['begintime']) {
                                $noconflict = true;
                            } else {
                                $noconflict = false;
                                break;
                            }
                        }
                        if ($noconflict) {
                            $usereventlistgrid[$guserid]['addedevents'][$gevent['date']]['columns'][$column][$gevent['id']] = $gevent;
                            unset($usereventlistgrid[$guserid]['events'][$gevent['date']][$gekey]);
                            $allevents --;
                        }
                    }
                }

                if (count($usereventlistgrid[$guserid]['events'][$gdekey]) > 0) {
                    $usereventlistgrid[$guserid]['events'][$gdekey] = array_values($usereventlistgrid[$guserid]['events'][$gdekey]);
                } else {
                    unset($usereventlistgrid[$guserid]['events'][$gdekey]);
                }
            }
        } else {
            continue;
        }
    }
    $column += 1;
} while ($allevents != 0);
//</editor-fold>

//<editor-fold desc="Set grid (row for every time period as events cells)">
foreach ($usereventlistgrid as $guserid => $guserevents) {
    foreach ($guserevents['addedevents'] as $gdekey => $gdateevent) {
        foreach ($gdateevent['columns'] as $colkey => $column) {
            foreach ($column as $ekey => $event) {
                foreach ($times as $ktime => $time) {
                    if (($ktime > ($event['begintime'] - $working_hours_interval_ts) && $ktime < $event['endtime'] && $event['endtime'] != $event['begintime'])
                        || ($ktime > ($event['begintime'] -$working_hours_interval_ts) && $ktime <= $event['endtime'] && $event['endtime'] == $event['begintime'])) {
                        $usereventlistgrid[$guserid]['eventsgrid'][$gdekey]['grid'][$ktime][$colkey] = $event;

                        if (!array_key_exists($event['id'], $usereventlistgrid[$guserid]['gridhelper'][$gdekey])) {
                            $usereventlistgrid[$guserid]['gridhelper'][$gdekey][$event['id']]['id'] = $event['id'];
                            $usereventlistgrid[$guserid]['eventsgrid'][$gdekey]['grid'][$ktime][$colkey]['position'] = 0;
                        } else {
                            $usereventlistgrid[$guserid]['eventsgrid'][$gdekey]['grid'][$ktime][$colkey]['position'] = 1;
                        }
                    }
                }
            }
        }
    }
}
//set empty value for cell to place cell in proper column
foreach ($usereventlistgrid as $guserid => $guserevents) {
    foreach ($guserevents['eventsgrid'] as $gdekey => $gdateevent) {
        foreach ($gdateevent['grid'] as $gekey => $gevents) {
            $maxgdkey = max(array_keys($gevents));
            if ($maxgdkey > 0) {
                for ($i = $maxgdkey; $i >= 0; $i--) {
                    if (!array_key_exists($i, $gevents)) {
                        $usereventlistgrid[$guserid]['eventsgrid'][$gdekey]['grid'][$gekey][$i] = array();
                    }
                }
            }
        }
    }
}
//</editor-fold>

if (ConfigHelper::checkConfig('phpui.timetable_overdue_events')) {
    $filter['forward'] = -1;
    $filter['closed'] = 0;
    $overdue_events = $LMS->GetEventList($filter);
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
$SMARTY->assign('usereventlist', $usereventlist);
$SMARTY->assign('usereventlistcount', $usereventlistcount);
$SMARTY->assign('usereventlistdates', $usereventlistdates);
$SMARTY->assign('usereventlistgrid', $usereventlistgrid);
if (ConfigHelper::checkConfig('phpui.timetable_overdue_events')) {
    $SMARTY->assign('overdue_events', $overdue_events);
}

$SMARTY->assign('days', $days);
$SMARTY->assign('daylist', $daylist);
$SMARTY->assign('date', $date);
$SMARTY->assign('error', $error);
//$SMARTY->assign('userlist', $LMS->GetUserNames());
$SMARTY->assign('userlist', $userlist);
if (!ConfigHelper::checkConfig('phpui.big_networks')) {
    $SMARTY->assign('customerlist', $LMS->GetCustomerNames());
}
$SMARTY->assign('getHolidays', getHolidays($year));
$SMARTY->display('event/schedule.html');
