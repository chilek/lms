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

$layout['pagetitle'] = trans('Network Statistics');

$bars = 1;

if (isset($_GET['bar']) && isset($_POST['order'])) {
    $SESSION->save('trafficorder', $_POST['order']);
}

$bar = isset($_GET['bar']) ? $_GET['bar'] : '';

switch ($bar) {
    case 'user':
        $traffic = Traffic($_POST['from'], $_POST['to'], $_POST['net'], $_POST['order'], $_POST['limit']);
        break;

    default: // set filter window
        $SMARTY->assign('netlist', $LMS->GetNetworks());
        $SMARTY->assign('nodelist', $LMS->GetNodeList());
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
$starttime = $starttime ? $starttime : time();
$endtime = $endtime ? $endtime : time();
$startyear = date('Y', $starttime);
$endyear = date('Y', $endtime);

$SMARTY->assign('starttime', $starttime);
$SMARTY->assign('startyear', $startyear);
$SMARTY->assign('endtime', $endtime);
$SMARTY->assign('endyear', $endyear);
$SMARTY->assign('showips', isset($_POST['showips']) ? true : false);
$SMARTY->assign('bars', $bars);
$SMARTY->assign('trafficorder', $SESSION->is_set('trafficorder') ? $SESSION->get('trafficorder') : 'download');
