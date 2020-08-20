<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2020 LMS Developers
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

function getDivisionsList($search)
{
    $db = LMSDB::getInstance();
    $lms = LMS::getInstance();

    if (isset($search['offset'])) {
        $offset = $search['offset'];
    } else {
        $offset = null;
    }
    if (isset($search['limit'])) {
        $limit = $search['limit'];
    } else {
        $limit = null;
    }

    $user_divisions = implode(',', array_keys($lms->GetDivisions(array('userid' => Auth::GetCurrentUser()))));

    return $db->GetAll('
        SELECT d.id, d.name, d.shortname, d.status, (SELECT COUNT(*) FROM customers WHERE divisionid = d.id) AS cnt 
        FROM divisions d'
        . (empty($search['superuser']) ? ' WHERE id IN (' . $user_divisions . ')' : '') .
        ' ORDER BY d.shortname'
        . (isset($limit) ? ' LIMIT ' . $limit : '')
        . (isset($offset) ? ' OFFSET ' . $offset : ''));
}

$layout['pagetitle'] = trans('Divisions List');
$search['superuser'] = (ConfigHelper::checkPrivilege('superuser') ? 1 : 0);

if ($SESSION->is_set('cdlp', true) && !isset($_GET['page'])) {
    $SESSION->restore('cdlp', $_GET['page'], true);
} elseif ($SESSION->is_set('cdlp') && !isset($_GET['page'])) {
    $SESSION->restore('cdlp', $_GET['page']);
}

$page = (!isset($_GET['page']) ? 1 : intval($_GET['page']));

$SESSION->save('cdlp', $page);
$SESSION->save('cdlp', $page, true);

$divisionlist = getDivisionsList($search);
$total = empty($divisionlist) ? 0 : count($divisionlist);
$limit = intval(ConfigHelper::getConfig('phpui.divisionlist_pagelimit', $total));
$offset = ($page - 1) * $limit;
$search['offset'] = $offset;
$search['limit'] = $limit;

$divisionlist = getDivisionsList($search);
$pagination = LMSPaginationFactory::getPagination($page, $total, $limit, ConfigHelper::checkConfig('phpui.short_pagescroller'));

$SESSION->save('backto', $_SERVER['QUERY_STRING']);
$SESSION->save('backto', $_SERVER['QUERY_STRING'], true);

$SMARTY->assign('divisionlist', $divisionlist);
$SMARTY->assign('pagination', $pagination);

$SMARTY->display('division/divisionlist.html');
