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

if (!ConfigHelper::checkConfig('privileges.superuser') && !ConfigHelper::checkConfig('privileges.reports')) {
    access_denied();
}

$type = $_GET['type'] ?? '';

switch ($type) {
    case 'nodelist':
        /***********************************************/
        $type = isset($_POST['type']) ? $_POST['type'] : 'print';

        if (!empty($_POST['options'])) {
            $options = $_POST['options'];
            if (!empty($options['assigned-bandwidth'])) {
                $nodeBandwidths = $DB->GetAllByKey(
                    'SELECT
                        n.id,
                        SUM(t.downrate) AS downrate,
                        SUM(t.downceil) AS downceil,
                        SUM(t.uprate) AS uprate,
                        SUM(t.upceil) AS upceil
                    FROM nodes n
                    JOIN nodeassignments na ON na.nodeid = n.id
                    JOIN assignments a ON a.id = na.assignmentid
                    JOIN tariffs t ON t.id = a.tariffid
                    WHERE a.commited = 1
                        AND a.suspended = 0
                        AND a.datefrom <= ?NOW?
                        AND (a.dateto = 0 OR a.dateto >= ?NOW?)
                        AND NOT EXISTS (
                            SELECT 1
                            FROM assignments a2
                            JOIN nodeassignments na2 ON na2.assignmentid = a2.id
                            WHERE na2.nodeid = n.id
                                AND a2.tariffid IS NULL
                                AND a2.liabilityid IS NULL
                                AND a2.datefrom <= ?NOW?
                                AND (a2.dateto = 0 OR a2.dateto >= ?NOW?)
                        )
                    GROUP BY n.id',
                    'id'
                );
            }
        } else {
            $options = array();
        }

        switch ($_POST['filter']) {
            case 0:
                $layout['pagetitle'] = trans('Nodes List');
                $nodelist = $LMS->GetNodeList(array('order' => $_POST['order'].','.$_POST['direction'],
                    'network' => $_POST['network'], 'customergroup' => $_POST['customergroup']));
                break;
            case 1:
                $layout['pagetitle'] = trans('List of Connected Nodes');
                $nodelist = $LMS->GetNodeList(array('order' => $_POST['order'].','.$_POST['direction'],
                    'network' => $_POST['network'], 'status' => 1, 'customergroup' => $_POST['customergroup']));
                break;
            case 2:
                $layout['pagetitle'] = trans('List of Disconnected Nodes');
                $nodelist = $LMS->GetNodeList(array('order' => $_POST['order'].','.$_POST['direction'],
                    'network' => $_POST['network'], 'status' => 2, 'customergroup' => $_POST['customergroup']));
                break;
            case 3:
                $layout['pagetitle'] = trans('Nodes List for Customers In Debt');

                $order=$_POST['order'].','.$_POST['direction'];
                if ($order=='') {
                    $order='name,asc';
                }

                [$order, $direction] = explode(',', $order);

                ($direction=='desc') ? $direction = 'desc' : $direction = 'asc';

                switch ($order) {
                    case 'name':
                        $sqlord = ' ORDER BY n.name';
                        break;
                    case 'id':
                        $sqlord = ' ORDER BY n.id';
                        break;
                    case 'mac':
                        $sqlord = ' ORDER BY n.mac';
                        break;
                    case 'ip':
                        $sqlord = ' ORDER BY n.ipaddr';
                        break;
                    case 'ownerid':
                        $sqlord = ' ORDER BY n.ownerid';
                        break;
                    case 'owner':
                        $sqlord = ' ORDER BY owner';
                        break;
                }

                $net = intval($_POST['network']);
                if ($net) {
                    $net = $LMS->GetNetworkParams($_POST['network']);
                }

                $group = intval($_POST['customergroup']);

                $nodelist = $DB->GetAll(
                    'SELECT
                        n.id AS id,
                        INET_NTOA(n.ipaddr) AS ip,
                        mac,
                        n.name AS name,
                        n.info AS info,
                        COALESCE(SUM(value), 0.00)/(CASE COUNT(DISTINCT n.id) WHEN 0 THEN 1 ELSE COUNT(DISTINCT n.id) END) AS balance, '
                        . $DB->Concat('UPPER(c.lastname)', "' '", 'c.name') . ' AS owner,
                        c.type AS ctype,
                        lc.name AS city_name,
                        lc.ident AS city_ident,
                        lb.name AS borough_name,
                        lb.ident AS borough_ident,
                        lb.type AS borough_type,
                        ld.name AS district_name,
                        ld.ident AS district_ident,
                        ls.name AS state_name,
                        ls.ident AS state_ident,
                        lst.name AS street_name,
                        (CASE WHEN lst.ident IS NULL
                            THEN (CASE WHEN lst.name = \'\' THEN \'99999\' ELSE \'99998\' END)
                            ELSE lst.ident END) AS street_ident,
                        n.location_house,
                        n.location_flat,
                        n.longitude,
                        n.latitude,
                        a.zip
                    FROM vnodes n
                    LEFT JOIN addresses a ON a.id = n.address_id
                    LEFT JOIN customers c ON c.id = n.ownerid
                    LEFT JOIN cash ON cash.customerid = c.id
                    LEFT JOIN location_streets lst ON lst.id = n.location_street
                    LEFT JOIN location_cities lc ON lc.id = n.location_city
                    LEFT JOIN location_boroughs lb ON lb.id = lc.boroughid
                    LEFT JOIN location_districts ld ON ld.id = lb.districtid
                    LEFT JOIN location_states ls ON ls.id = ld.stateid
                    WHERE 1 = 1 '
                        . ($net ? ' AND ((n.ipaddr > ' . $net['address'] . ' AND n.ipaddr < ' . $net['broadcast'] . ') OR (n.ipaddr_pub > ' . $net['address'] . ' AND n.ipaddr_pub < ' . $net['broadcast'] . '))' : '')
                        . ($group ? ' AND EXISTS (SELECT 1 FROM vcustomerassignments WHERE customerid = ownerid)' : '')
                    . ' GROUP BY n.id, n.ipaddr, n.mac, n.name, n.info, c.lastname, c.name, c.type, lc.name,
                        lc.ident, lb.name, lb.ident, lb.type, ld.name, ld.ident, ls.name, ls.ident, lst.ident, lst.name, n.location_house, n.location_flat, n.longitude, n.latitude, a.zip
                    HAVING SUM(value) < 0'
                    . ($sqlord != '' ? $sqlord . ' ' . $direction : '')
                );

                if (!empty($nodeBandwidths)) {
                    foreach ($nodelist as &$node) {
                        if (!empty($nodeBandwidths[$node['id']])) {
                            $node = array_merge($node, $nodeBandwidths[$node['id']]);
                        }
                    }
                    unset($node);
                }

                $SMARTY->assign('options', $options);
                $SMARTY->assign('nodelist', $nodelist);

                if (strtolower(ConfigHelper::getConfig('phpui.report_type')) == 'pdf' && $type == 'print') {
                    $output = $SMARTY->fetch('print/printindebtnodelist.html');
                    Utils::html2pdf(array(
                        'content' => $output,
                        'subject' => trans('Reports'),
                        'title' => $layout['pagetitle'],
                    ));
                } elseif ($type == 'print') {
                    $SMARTY->display('print/printindebtnodelist.html');
                } else {
                    $filename = 'nodes-indebt-' . date('YmdHis') . '.csv';
                    header('Content-Type: text/plain; charset=utf-8');
                    header('Content-Disposition: attachment; filename=' . $filename);
                    header('Pragma: public');

                    $SMARTY->display('print/printindebtnodelist-csv.html');
                }

                $SESSION->close();

                die;

                break;
        }

        unset($nodelist['total']);
        unset($nodelist['order']);
        unset($nodelist['direction']);
        unset($nodelist['totalon']);
        unset($nodelist['totaloff']);

        if (!empty($nodeBandwidths)) {
            foreach ($nodelist as &$node) {
                if (!empty($nodeBandwidths[$node['id']])) {
                    $node = array_merge($node, $nodeBandwidths[$node['id']]);
                }
            }
            unset($node);
        }

        $SMARTY->assign('options', $options);
        $SMARTY->assign('nodelist', $nodelist);

        if (strtolower(ConfigHelper::getConfig('phpui.report_type')) == 'pdf' && $type == 'print') {
            $output = $SMARTY->fetch('print/printnodelist.html');
            Utils::html2pdf(array(
                'content' => $output,
                'subject' => trans('Reports'),
                'title' => $layout['pagetitle'],
            ));
        } elseif ($type == 'print') {
            $SMARTY->display('print/printnodelist.html');
        } else {
            $filename = 'nodes-' . date('YmdHis') . '.csv';
            header('Content-Type: text/plain; charset=utf-8');
            header('Content-Disposition: attachment; filename=' . $filename);
            header('Pragma: public');

            $SMARTY->display('print/printnodelist-csv.html');
        }
        break;

    default:
        $layout['pagetitle'] = trans('Reports');

        $SMARTY->assign('customergroups', $LMS->CustomergroupGetAll());
        $SMARTY->assign('networks', $LMS->GetNetworks());
        $SMARTY->assign('printmenu', 'node');
        $SMARTY->display('print/printindex.html');
        break;
}
