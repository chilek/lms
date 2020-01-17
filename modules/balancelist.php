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

$default_current_period = ConfigHelper::getConfig('phpui.balancelist_default_current_period', '', true);
if (!preg_match('/^(day|month)$/', $default_current_period)) {
    $default_current_period = '';
}

if (isset($_POST['search'])) {
        $s = $_POST['search'];
} else {
    $SESSION->restore('bls', $s);
}
if (!isset($s) && $default_current_period) {
    list ($year, $month, $day) = explode('/', date('Y/m/d'));
    if ($default_current_period == 'day') {
        $s = date('Y/m/d', mktime(0, 0, 0, $month, $day, $year));
    } else {
        $s = date('Y/m', mktime(0, 0, 0, $month, 1, $year));
    }
}

if (isset($_POST['cat'])) {
        $c = $_POST['cat'];
} else {
    $SESSION->restore('blc', $c);
}
if (!isset($c) && $default_current_period) {
    if ($default_current_period == 'day') {
        $c = 'cdate';
    } else {
        $c = 'month';
    }
}
$SESSION->save('blc', $c);

if (isset($_POST['group'])) {
        $g = $_POST['group'];
    $ge = isset($_POST['groupexclude']) ? 1 : 0;
} else {
        $SESSION->restore('blg', $g);
        $SESSION->restore('blge', $ge);
}
$SESSION->save('blg', $g);
$SESSION->save('blge', $ge);

$SESSION->save('bls', $s);

if (($c == 'cdate' || $c == 'month') && $s) {
    if (preg_match('/^(?<year>[0-9]{4})\/(?<month>[0-9]{2})(?:\/(?<day>[0-9]{2}))?$/', $s, $m)) {
        if (!isset($m['day'])) {
            $m['day'] = 1;
        }
        if (checkdate($m['month'], $m['day'], $m['year'])) {
            $s = $date = mktime(0, 0, 0, $m['month'], $m['day'], $m['year']);
        }
    }
    if (!isset($date)) {
        list ($year, $month, $day) = explode('/', date('Y/m/d'));
        $s = mktime(0, 0, 0, $month, $c == 'cdate' ? $day : 1, $year);
    }
}

if (!empty($_POST['from'])) {
    if (strlen($_POST['from']) > 10) {
        $from = datetime_to_timestamp($_POST['from']);
    } else {
        $from = date_to_timestamp($_POST['from']);
    }
} elseif ($SESSION->is_set('blf')) {
    $SESSION->restore('blf', $from);
} else {
    $from = '';
}

if (!empty($_POST['to'])) {
    if (strlen($_POST['to']) > 10) {
        $to = datetime_to_timestamp($_POST['to']);
    } else {
        $to = date_to_timestamp($_POST['to']);
    }
} elseif ($SESSION->is_set('blt')) {
    $SESSION->restore('blt', $to);
} else {
    $to = '';
}

if (!empty($from) && !empty($to)) {
    if ($from < $to) {
        $SESSION->save('blf', $from);
        $SESSION->save('blt', $to);
    }
} elseif (!empty($from)) {
    $SESSION->save('blf', $from);
} elseif (!empty($to)) {
    $SESSION->save('blt', $to);
}

$pagelimit = ConfigHelper::getConfig('phpui.balancelist_pagelimit');
$page = (empty($_GET['page']) ? 0 : intval($_GET['page']));

if (isset($_GET['sourcefileid'])) {
    $s = $DB->GetOne('SELECT name FROM sourcefiles WHERE id = ?', array($_GET['sourcefileid']));
    $c = 'cashimport';
    $SESSION->save('bls', $s);
    $SESSION->save('blc', $c);
}

$summary = $LMS->GetBalanceList(array('search' => $s, 'cat' => $c, 'group' => $g, 'exclude'=> $ge,
    'from' => $from, 'to' => $to, 'count' => true));
$total = intval($summary['total']);

$limit = intval(ConfigHelper::getConfig('phpui.balancelist_pagelimit', 100));
$page = !isset($_GET['page']) ? ceil($total / $limit) : $_GET['page'];
if (empty($page)) {
    $page = 1;
}
$page = intval($page);
$offset = ($page - 1) * $limit;

$balancelist = $LMS->GetBalanceList(array('search' => $s, 'cat' => $c, 'group' => $g, 'exclude'=> $ge,
    'limit' => $limit, 'offset' => $offset, 'from' => $from, 'to' => $to, 'count' =>  false));

$pagination = LMSPaginationFactory::getPagination($page, $total, $limit, ConfigHelper::checkConfig('phpui.short_pagescroller'));

$listdata['liability'] = $summary['liability'];
$listdata['income'] = $summary['income'];
$listdata['expense'] = $summary['expense'];
$listdata['totalval'] = $summary['income'] - $summary['expense'];
$listdata['total'] = $total;

$SESSION->restore('blc', $listdata['cat']);
$SESSION->restore('bls', $listdata['search']);
$SESSION->restore('blg', $listdata['group']);
$SESSION->restore('blge', $listdata['groupexclude']);
$SESSION->restore('blf', $listdata['from']);
$SESSION->restore('blt', $listdata['to']);

$layout['pagetitle'] = trans('Balance Sheet');

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$SMARTY->assign('balancelist', $balancelist);
$SMARTY->assign('listdata', $listdata);
$SMARTY->assign('pagination', $pagination);
$SMARTY->assign('grouplist', $LMS->CustomergroupGetAll());
$SMARTY->display('balance/balancelist.html');
