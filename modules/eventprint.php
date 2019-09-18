<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2019 LMS Developers
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

$date = $_GET['day'];

if (empty($date)) {
    $date = time();
}

list ($year, $month, $day) = explode('/', date('Y/m/d', $date));

$eventlist = $LMS->GetEventList(
    array(
        'year' => $year,
        'month' => $month,
        'day' => $day,
        'forward' => 1,
        'userid' => $_GET['a'],
        'type' => $_GET['t'],
        'customerid' => $_GET['u'],
        'privacy' => $_GET['privacy'],
        'closed' => $_GET['closed'],
        'singleday' => true,
        'count' => false,
    )
);

//$eventlist = GetEvents($date, $_GET['a'], $_GET['t'], $_GET['u'], intval($_GET['privacy']), $_GET['closed']);

$layout['pagetitle'] = trans('Timetable');

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$SMARTY->assign('eventlist', $eventlist);
$SMARTY->assign('date', $date);
$SMARTY->display('event/eventprint.html');
