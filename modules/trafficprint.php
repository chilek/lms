<?php

/*
 * LMS version 1.11-git
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

if (!ConfigHelper::checkConfig('privileges.superuser') && !ConfigHelper::checkConfig('privileges.reports')) {
    access_denied();
}

$type = isset($_GET['type']) ? $_GET['type'] : '';

switch ($type) {
    case 'customertraffic':
        /******************************************/

        $stat_freq = ConfigHelper::getConfig('phpui.stat_freq', 12);
        $speed_unit_type = ConfigHelper::getConfig('phpui.speed_unit_type', 1000);

        $month = isset($_POST['month']) ? $_POST['month'] : date('n');
        $year = isset($_POST['year']) ? $_POST['year'] : date('Y');
        $customer = isset($_POST['customer']) ? intval($_POST['customer']) : intval($_GET['customer']);

        $layout['pagetitle'] = trans('Stats of Customer $a in month $b', $LMS->GetCustomerName($customer), strftime('%B %Y', mktime(0, 0, 0, $month, 1, $year)));

        $SMARTY->assign('showavg', isset($_POST['showavg']) ? 1 : 0);
        $SMARTY->assign('showmax', isset($_POST['showmax']) ? 1 : 0);

        $from = mktime(0, 0, 0, $month, 1, $year);
        $to = mktime(0, 0, 0, $month+1, 1, $year);

        if ($list = $DB->GetAll(
            'SELECT download, upload, dt
				    FROM stats
				    LEFT JOIN nodes ON (nodeid = nodes.id)
				    WHERE ownerid = ? AND dt >= ? AND dt < ?',
            array($customer, $from, $to)
        )) {
            for ($i=1; $i<=date('t', $from); $i++) {
                $stats[$i]['date'] = mktime(0, 0, 0, $month, $i, $year);
                $stats[$i]['download'] = 0;
                $stats[$i]['upload'] = 0;
                $stats[$i]['downmax'] = 0;
                $stats[$i]['upmax'] = 0;
            }

            foreach ($list as $row) {
                $day = date('j', $row['dt']);

                $stats[$day]['download'] += $row['download'];
                $stats[$day]['upload'] += $row['upload'];

                if ($row['download'] > $stats[$day]['downmax']) {
                    $stats[$day]['downmax'] = $row['download'];
                }
                if ($row['upload'] > $stats[$day]['upmax']) {
                    $stats[$day]['upmax'] = $row['upload'];
                }
            }

            $listdata = array(
                'upload' => 0,
                'download' => 0,
                'upavg' => 0,
                'downavg' => 0,
                'upmax' => 0,
                'downmax' => 0,
            );

            for ($i = 1; $i <= date('t', $from); $i++) {
                $stats[$i]['upavg'] = $stats[$i]['upload'] * 8 / $speed_unit_type / 86400; //kbit/s
                $stats[$i]['downavg'] = $stats[$i]['download'] * 8 / $speed_unit_type / 86400; //kbit/s

                $stats[$i]['upmax'] = $stats[$i]['upmax'] * 8 / $speed_unit_type / $stat_freq; //kbit/s
                $stats[$i]['downmax'] = $stats[$i]['downmax'] * 8 / $speed_unit_type / $stat_freq; //kbit/s

                $listdata['upload'] += $stats[$i]['upload'];
                $listdata['download'] += $stats[$i]['download'];
                $listdata['upavg'] += $stats[$i]['upavg'];
                $listdata['downavg'] += $stats[$i]['downavg'];

                list($stats[$i]['upload'], $stats[$i]['uploadunit']) = setunits($stats[$i]['upload']);
                list($stats[$i]['download'], $stats[$i]['downloadunit']) = setunits($stats[$i]['download']);

                if ($stats[$i]['upmax'] > $listdata['upmax']) {
                    $listdata['upmax'] = $stats[$i]['upmax'];
                }
                if ($stats[$i]['downmax'] > $listdata['downmax']) {
                    $listdata['downmax'] = $stats[$i]['downmax'];
                }
            }

            $listdata['upavg'] = $listdata['upavg'] / date('t', $from);
            $listdata['downavg'] = $listdata['downavg'] / date('t', $from);
            list($listdata['upload'], $listdata['uploadunit']) = setunits($listdata['upload']);
            list($listdata['download'], $listdata['downloadunit']) = setunits($listdata['download']);

            $SMARTY->assign('stats', $stats);
            $SMARTY->assign('listdata', $listdata);
        }

        if (strtolower(ConfigHelper::getConfig('phpui.report_type')) == 'pdf') {
            $output = $SMARTY->fetch('print/printcustomertraffic.html');
            html2pdf($output, trans('Reports'), $layout['pagetitle']);
        } else {
            $SMARTY->display('print/printcustomertraffic.html');
        }
        break;

    default:
        $layout['pagetitle'] = trans('Reports');

        $yearstart = date('Y', (int) $DB->GetOne('SELECT MIN(dt) FROM stats'));
        $yearend = date('Y', (int) $DB->GetOne('SELECT MAX(dt) FROM stats'));
        for ($i=$yearstart; $i<$yearend+1; $i++) {
            $statyears[] = $i;
        }
        for ($i=1; $i<13; $i++) {
            $months[$i] = strftime('%B', mktime(0, 0, 0, $i, 1));
        }

        if (!ConfigHelper::checkConfig('phpui.big_networks')) {
            $SMARTY->assign('customers', $LMS->GetCustomerNames());
        }
        $SMARTY->assign('currmonth', date('n'));
        $SMARTY->assign('curryear', date('Y'));
        $SMARTY->assign('statyears', $statyears);
        $SMARTY->assign('months', $months);
        $SMARTY->assign('printmenu', 'traffic');
        $SMARTY->display('print/printindex.html');
        break;
}
