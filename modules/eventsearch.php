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

$layout['pagetitle'] = trans('Event Search');

$SESSION->add_history_entry();

if (!isset($_POST['event'])) {
       $event = array();
    if (isset($_GET['datefrom'])) {
            $event['datefrom'] = $_GET['datefrom'];
    }
    if (isset($_GET['dateto'])) {
            $event['dateto'] = $_GET['dateto'];
    }
    if (isset($_GET['ticketid'])) {
            $event['ticketid'] = $_GET['ticketid'];
    }
    if (isset($_GET['closed'])) {
            $event['closed'] = $_GET['closed'];
    }

    if (!empty($event)) {
            $_POST['event'] = $event;
    }
}

if (isset($_POST['event'])) {
    $event = $_POST['event'];

    if (!empty($event['ticketid'])) {
        $event['ticketid'] = intval($event['ticketid']);
    }

    if ($event['datefrom']) {
        [$year, $month, $day] = explode('/', $event['datefrom']);
        $event['datefrom'] = mktime(0, 0, 0, $month, $day, $year);
    }

    if ($event['dateto']) {
        [$year, $month, $day] = explode('/', $event['dateto']);
        $event['dateto'] = mktime(0, 0, 0, $month, $day, $year);
    }

    if (!empty($event['custid'])) {
        $event['customerid'] = intval($event['custid']);
    }

    $eventlist = $LMS->EventSearch($event);
    $daylist = array();

    if (!empty($eventlist)) {
        foreach ($eventlist as $event) {
            if (!in_array($event['date'], $daylist)) {
                $daylist[] = $event['date'];
            }
        }
    }

    $SMARTY->assign('eventlist', $eventlist);
    $SMARTY->assign('daylist', $daylist);
    $SMARTY->assign('getHolidays', getHolidays($year ?? null));

    $total_time = 0;
    $calendar_intervals = array();
    foreach ($eventlist as $event) {
        if (empty($event['hide'])) {
            $total_time += $event['total_time'];
        }
        $start_datetime = $event['date'] + $event['begintime'];
        $end_datetime = $event['enddate'] + $event['endtime'];
        $interval = $event['endtime'] - $event['begintime'];
        if (!isset($calendar_intervals[$start_datetime]) || $interval > $calendar_intervals[$start_datetime]) {
            $calendar_intervals[$start_datetime] = $interval;
        }
    }
    uksort(
        $calendar_intervals,
        function ($a, $b) {
            return $a - $b;
        }
    );

    $calendar_final_intervals = array();
    foreach ($calendar_intervals as $start_datetime => $interval) {
        if (isset($calendar_final_intervals[$start_datetime])) {
            if ($interval > $calendar_final_intervals[$start_datetime]) {
                $calendar_final_intervals[$start_datetime] = $interval;
            }
        } else {
            $found = false;
            foreach ($calendar_final_intervals as $start_final_datetime => &$final_interval) {
                if ($start_datetime > $start_final_datetime
                    && $start_datetime + $interval > $start_final_datetime + $final_interval
                    && $start_datetime <= $start_final_datetime + $final_interval) {
                    $final_interval += $start_datetime + $interval - ($start_final_datetime + $final_interval);
                    $found = true;
                    break;
                } elseif ($start_datetime > $start_final_datetime && $start_datetime + $interval <= $start_final_datetime + $final_interval) {
                    $found = true;
                    break;
                }
            }
            unset($final_interval);
            if ($found) {
                continue;
            }
            $calendar_final_intervals[$start_datetime] = $interval;
        }
    }

    $calendar_total_time = 0;
    foreach ($calendar_final_intervals as $final_interval) {
        $calendar_total_time += $final_interval;
    }

    $SMARTY->assign('total_time', $total_time);
    $SMARTY->assign('calendar_total_time', $calendar_total_time);

    $SMARTY->display('event/eventsearchresults.html');

    $SESSION->close();
    die;
}

$SMARTY->assign('userlist', $LMS->GetUserNames());
if (!ConfigHelper::checkConfig('phpui.big_networks')) {
    $SMARTY->assign('customerlist', $LMS->GetCustomerNames());
}
$SMARTY->display('event/eventsearch.html');
