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

function hourRange($lower, $upper, $step)
{
    $times = array();

    foreach (range($lower, $upper, $step) as $increment) {
        $nextincrement = gmdate('H:i', ($increment + $step));
        $times[strval($increment)] = gmdate('H:i', $increment).'-'.$nextincrement;
    }

    return $times;
}

function parseWorkTimeHours($period)
{
    [$begin, $end] = explode('-', $period);
    $parsed_begin = date_parse($begin . (strpos($begin, ':') === false ? ':00' : ''));
    $parsed_end = date_parse($end . (strpos($end, ':') === false ? ':00' : ''));

    return array(
        'begin' => $parsed_begin['hour'] * 3600 + $parsed_begin['minute'] * 60,
        'end' => $parsed_end['hour'] * 3600 + $parsed_end['minute'] * 60 - 1,
    );
}

$big_networks = ConfigHelper::checkConfig('phpui.big_networks');
$default_forward_day_limit = ConfigHelper::getConfig('timetable.default_forward_day_limit', ConfigHelper::getConfig('phpui.timetable_days_forward'));
$hide_disabled_users = ConfigHelper::checkConfig('timetable.hide_disabled_users', ConfigHelper::checkConfig('phpui.timetable_hide_disabled_users'));

// ajax request handling
if (isset($_GET['action']) && $_GET['action'] == 'eventmove') {
    if (!isset($_GET['id']) || !isset($_GET['delta'])) {
        die;
    }
    $LMS->MoveEvent($_GET['id'], $_GET['delta']);
    header('Content-Type: application/json');
    die('[]');
}

if (!empty($filter['edate'])) {
    [$filter['year'], $filter['month'], $filter['day']] = explode('/', $filter['edate']);
}

if (!isset($_POST['loginform']) && !empty($_POST)) {
    [$filter['year'], $filter['month'], $filter['day']] = explode('/', !empty($_POST['date']) ? $_POST['date'] : date('Y/m/d'));

    if (!empty($filter['edate'])) {
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
    $filter['userid'] = $_POST['userid'] ?? array();
    $filter['customerid'] = $_POST['customerid'] ?? null;
    $filter['type'] = $_POST['type'] ?? null;
    $filter['privacy'] = isset($_POST['privacy']) ? intval($_POST['privacy']) : null;
    $filter['closed'] = $_POST['closed'] ?? null;

    if (isset($_POST['switchToTimetable'])) {
        $SESSION->save('schedulerFiler', $filter, true);
        $SESSION->redirect('?m=eventlist&switchToTimetable=1');
        die();
    }
} elseif (isset($_GET['switchToSchedule'])) {
    $SESSION->restore('timetableFiler', $filter, true);
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

    if (isset($_GET['userid'])) {
        $filter['userid'] = $_GET['userid'];
    }

    if (isset($_GET['customerid'])) {
        $filter['customerid'] = $_GET['customerid'] == 'all' ? null : $_GET['customerid'];
    }

    if (isset($_GET['type'])) {
        $filter['type'] = $_GET['type'] == 'all' ? null : $_GET['type'];
    }

    if (isset($_GET['privacy'])) {
        $filter['privacy'] = $_GET['privacy'] == 'all' ? null : $_GET['privacy'];
    }

    if (isset($_GET['closed'])) {
        $filter['closed'] = $_GET['closed'] = 'all' ? '' : $_GET['closed'];
    } elseif (!isset($filter['closed'])) {
        $allevents = ConfigHelper::checkConfig(
            'timetable.default_show_closed_events',
            ConfigHelper::checkConfig('phpui.default_show_closed_events')
        );

        $filter['closed'] = $allevents ? '' : 0;
    }
}

if (isset($filter['year']) && isset($filter['month']) && isset($filter['day'])) {
    $filter['edate'] = sprintf('%04d/%02d/%02d', $filter['year'], $filter['month'], $filter['day']);
}

$SESSION->save('eld', array(
    'year' => $filter['year'] ?? null,
    'month' => $filter['month'] ?? null,
    'day' => $filter['day'] ?? null,
    'edate' => $filter['edate'] ?? null,
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

$layout['pagetitle'] = trans('Schedule');

$filter['forward'] = $default_forward_day_limit;
$eventlist = $LMS->GetEventList($filter);
$eventlistIds = Utils::array_column($eventlist, 'id', 'id');

$userid = $filter['userid'] ?? null;
$userlistcount = empty($userid) ? 0 : count($userid);

$params['short'] = 1;
if ($hide_disabled_users) {
    $params['userAccess'] = 1;
}
$params['withDeleted'] = 1;
$userlist = $LMS->GetUserList($params);

$SMARTY->assign('userlist', $userlist);
if (is_array($userid) && in_array('-1', $userid)) {
    $userlist[-1]['id'] = -1;
    $userlist[-1]['name'] = trans("unassigned");
}

$usereventlist = array();
if (empty($userid)) {
    unset($filter['userid']);
    foreach ($userlist as $user) {
        $filter['userid'] = $user['id'];
        $usereventlist[$user['id']]['events'] = $LMS->GetEventList($filter);
        $usereventlist[$user['id']]['username'] = $user['name'];
        if (!$LMS->checkUserAccess($user['id'])) {
            $usereventlist[$user['id']]['noaccess'] = 1;
        }
    }
    unset($filter['userid']);
    $filter['userid'] = '-1';
    $usereventlist[-1]['events'] = $LMS->GetEventList($filter);
    $usereventlist[-1]['username'] = trans("unassigned");

    $filter['userid'] = $userid;
} else if (is_array($userid)) {
    unset($filter['userid']);
    foreach ($userlist as $user) {
        if (in_array($user['id'], $userid)) {
            $filter['userid'] = $user['id'];
            $usereventlist[$user['id']]['events'] = $LMS->GetEventList($filter);
            $usereventlist[$user['id']]['username'] = $user['name'];
            if (!$LMS->checkUserAccess($user['id'])) {
                $usereventlist[$user['id']]['noaccess'] = 1;
            }
            if ($filter['userand']) {
                foreach ($usereventlist[$user['id']]['events'] as $ekey => $event) {
                    if (!isset($eventlistIds[$event['id']])) {
                        unset($usereventlist[$user['id']]['events'][$ekey]);
                    }
                }
            }
        }
    }
    $filter['userid'] = $userid;
}

$usereventlistcount = empty($usereventlist) ? 0 : count($usereventlist);

//<editor-fold desc="group events by days">
$usereventlistdates = array();
foreach ($usereventlist as $userid => $userevents) {
    $usereventlistdates[$userid]['username'] = $userevents['username'];
    if (isset($userevents['noaccess'])) {
        $usereventlistdates[$userid]['noaccess'] = $userevents['noaccess'];
    }
    if ($userevents['events']) {
        foreach ($userevents['events'] as $event) {
            $usereventlistdates[$userid]['events'][$event['date']][] = $event;
        }
    } else {
        $usereventlistdates[$userid]['events'] = array();
    }
}
//</editor-fold>

$work_time_step = ConfigHelper::getConfig('timetable.work_time_step', ConfigHelper::getConfig('phpui.timetable_working_hours_interval', 30));
$work_time_step_ts = $work_time_step * 60;
$work_time_hours = ConfigHelper::getConfig('timetable.work_time_hours', ConfigHelper::getConfig('phpui.timetable_working_hours', '08:00-19:00'));
$work_time_hours_ts = parseWorkTimeHours($work_time_hours);
$times = hourRange($work_time_hours_ts['begin'], $work_time_hours_ts['end'], $work_time_step_ts);
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
                            if (($gevent['begintime'] >= $addedevent['endtime'] && $gevent['begintime'] != $addedevent['begintime']) || ($gevent['endtime'] <= $addedevent['begintime'] && $gevent['begintime'] != $addedevent['begintime'])) {
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
        }
    }
    $column += 1;
} while ($allevents != 0);
//</editor-fold>

//<editor-fold desc="Set grid (row for every time period as events cells)">
foreach ($usereventlistgrid as $guserid => $guserevents) {
    if (isset($guserevents['addedevents'])) {
        foreach ($guserevents['addedevents'] as $gdekey => $gdateevent) {
            $usereventlistgrid[$guserid]['gridhelper'][$gdekey] = array();
            foreach ($gdateevent['columns'] as $colkey => $column) {
                foreach ($column as $ekey => $event) {
                    foreach ($times as $ktime => $time) {
                        if (($ktime > ($event['begintime'] - $work_time_step_ts) && $ktime < $event['endtime'] && $event['endtime'] != $event['begintime'])
                            || ($ktime > ($event['begintime'] - $work_time_step_ts) && $ktime <= $event['endtime'] && $event['endtime'] == $event['begintime'])) {
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
}
//set empty value for cell to place cell in proper column
foreach ($usereventlistgrid as $guserid => $guserevents) {
    if (isset($guserevents['eventsgrid'])) {
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
}
//</editor-fold>

// create calendars
for ($i = 0; $i < $default_forward_day_limit; $i++) {
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

$SESSION->add_history_entry();
$SESSION->remove('backid');

$today = mktime(0, 0, 0, date('n'), date('j'), date('Y'));
$SMARTY->assign(array(
    'today' => $today,
    'period' => $LMS->GetTimetableRange(),
    'eventlist' => $eventlist,
    'usereventlist' => $usereventlist,
    'usereventlistcount' => $usereventlistcount,
    'userlistcount' => $userlistcount,
    'usereventlistdates' => $usereventlistdates,
    'usereventlistgrid' => $usereventlistgrid,
    'days' => $days,
    'daylist' => $daylist,
    'date' => $date,
    'error' => $error,
    'customerlist' => ($big_networks ? null : $LMS->GetCustomerNames()),
    'getHolidays' => getHolidays($year ?? null)
));
$SMARTY->display('event/eventschedule.html');
