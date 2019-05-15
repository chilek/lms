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

$layout['pagetitle'] = trans('Node Sessions');

if (isset($_POST['type'])) {
    $type = intval($_POST['type']);
} else {
    $SESSION->restore('nsltype', $type);
    if (empty($type)) {
        $type = 0;
    }
}

$SESSION->save('nsltype', $type);

if (isset($_POST['datefrom']) && !empty($_POST['datefrom'])) {
    if (preg_match('/^(?<year>[0-9]{4})\/(?<month>[0-9]{2})\/(?<day>[0-9]{2}) (?<hour>[0-9]{2}):(?<minute>[0-9]{2})$/', $_POST['datefrom'], $m)) {
        $datefrom = mktime($m['hour'], $m['minute'], 0, $m['month'], $m['day'], $m['year']);
    } else {
        $datefrom = time() - 24 * 60 * 60;
    }
} else {
    $SESSION->restore('nsldatefrom', $datefrom);
    if (empty($datefrom)) {
        $datefrom = time() - 24 * 60 * 60;
    }
}

if (isset($_POST['dateto']) && !empty($_POST['dateto'])) {
    if (preg_match('/^(?<year>[0-9]{4})\/(?<month>[0-9]{2})\/(?<day>[0-9]{2}) (?<hour>[0-9]{2}):(?<minute>[0-9]{2})$/', $_POST['dateto'], $m)) {
        $dateto = mktime($m['hour'], $m['minute'], 0, $m['month'], $m['day'], $m['year']);
    } else {
        $dateto = time();
    }
} else {
    $SESSION->restore('nsldateto', $dateto);
    if (empty($dateto)) {
        $dateto = time();
    }
}

$SESSION->save('nsldatefrom', $datefrom);
$SESSION->save('nsldateto', $dateto);

if (isset($_POST['filtertype'])) {
    if (in_array($_POST['filtertype'], array('ip', 'mac', 'customer', 'nodeid'))) {
        $filtertype = $_POST['filtertype'];
    } else {
        $filtertype = '';
    }
} else {
    $SESSION->restore('nslfiltertype', $filtertype);
}

if (isset($_POST['filtervalue'])) {
    if (!empty($_POST['filtervalue'])) {
        $filtervalue = $_POST['filtervalue'];
    } else {
        $filtervalue = '';
    }
} else {
        $SESSION->restore('nslfiltervalue', $filtervalue);
}

if (empty($filtervalue)) {
    $filtertype = '';
}

if (isset($_GET['nodeid'])) {
    $filtertype = 'nodeid';
    $filtervalue = intval($_GET['nodeid']);
    $type = 0;
}

$SESSION->save('nslfiltertype', $filtertype);
$SESSION->save('nslfiltervalue', $filtervalue);

$where = array();
if (!empty($type)) {
    $where[] = '(s.type & ' . intval($type) . ') > 0';
}
$where[] = 's.start > ' . $datefrom . ' AND s.stop < ' . $dateto;

if (!empty($filtertype)) {
    switch ($filtertype) {
        case 'ip':
            if (check_ip($filtervalue)) {
                $where[] = 's.ipaddr = ' . ip_long($filtervalue);
            } else {
                $filtervalue = '';
            }
            break;
        case 'mac':
            if (check_mac($filtervalue)) {
                $where[] = 's.mac = \'' . $filtervalue . '\'';
            } else {
                $filtervalue = '';
            }
            break;
        case 'customer':
            $where[] = '(c.name ?LIKE? ' . $DB->Escape("%$filtervalue%")
            . ' OR c.lastname ?LIKE? ' . $DB->Escape("%$filtervalue%") . ')';
            break;
        case 'nodeid':
            if (intval($filtervalue)) {
                $where[] = 's.nodeid = ' . intval($filtervalue);
            } else {
                $filtervalue = '';
            }
            break;
    }
}

$nodesessions = $DB->GetAll('SELECT s.*, c.name, c.lastname FROM nodesessions s
	LEFT JOIN nodes n ON n.id = s.nodeid
	LEFT JOIN customers c ON c.id = s.customerid
	WHERE ' . implode(' AND ', $where) . '
	ORDER BY s.start DESC LIMIT 5000');

if (!empty($nodesessions)) {
    foreach ($nodesessions as &$session) {
        list ($number, $unit) = setunits($session['download']);
        $session['download'] = round($number, 2) . ' ' . $unit;
        list ($number, $unit) = setunits($session['upload']);
        $session['upload'] = round($number, 2) . ' ' . $unit;
        $session['duration'] = $session['stop']
        ? ($session['stop'] - $session['start'] < 60 ? trans('shorter than minute') : uptimef($session['stop'] - $session['start']))
        : '-';
    }
}

$pagelimit = ConfigHelper::getConfig('phpui.nodesession_pagelimit', 100);
$page = !isset($_GET['page']) ? 1 : intval($_GET['page']);

$listdata['total'] = empty($nodesessions) ? 0 : count($nodesessions);

if (($page - 1) * $pagelimit > $listdata['total']) {
    $page = 1;
}

$SMARTY->assign('listdata', $listdata);
$SMARTY->assign('pagelimit', $pagelimit);
$SMARTY->assign('start', ($page - 1) * $pagelimit);
$SMARTY->assign('page', $page);

$SMARTY->assign('filtertype', $filtertype);
$SMARTY->assign('filtervalue', $filtervalue);
$SMARTY->assign('datefrom', $datefrom ? strftime('%Y/%m/%d %H:%M', $datefrom) : '');
$SMARTY->assign('dateto', $dateto ? strftime('%Y/%m/%d %H:%M', $dateto) : '');
$SMARTY->assign('type', $type);

$SMARTY->assign('nodesessions', $nodesessions);
$SMARTY->display('node/nodesessionlist.html');
