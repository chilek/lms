<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2017 LMS Developers
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

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

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
    if (!empty($event)) {
            $_POST['event'] = $event;
    }
}

if (isset($_POST['event'])) {
    $event = $_POST['event'];

    if ($event['ticketid']) {
        $event['ticketid'] = intval($event['ticketid']);
    }

    if ($event['datefrom']) {
        list($year, $month, $day) = explode('/', $event['datefrom']);
        $event['datefrom'] = mktime(0, 0, 0, $month, $day, $year);
    }

    if ($event['dateto']) {
        list($year, $month, $day) = explode('/', $event['dateto']);
        $event['dateto'] = mktime(0, 0, 0, $month, $day, $year);
    }

    if ($event['custid']) {
        $event['customerid'] = $event['custid'];
    }
        
    $eventlist = $LMS->EventSearch($event);
    $daylist = array();

    if (count($eventlist)) {
        foreach ($eventlist as $event) {
            if (!in_array($event['date'], $daylist)) {
                $daylist[] = $event['date'];
            }
        }
    }
        
    $SMARTY->assign('eventlist', $eventlist);
    $SMARTY->assign('daylist', $daylist);
    $SMARTY->assign('getHolidays', getHolidays($year));
    $SMARTY->display('event/eventsearchresults.html');
    $SESSION->close();
    die;
}

$SMARTY->assign('userlist', $LMS->GetUserNames());
if (!ConfigHelper::checkConfig('phpui.big_networks')) {
    $SMARTY->assign('customerlist', $LMS->GetCustomerNames());
}
$SMARTY->display('event/eventsearch.html');
