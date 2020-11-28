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

$layout['pagetitle'] = trans('Divisions List');
$params['superuser'] = (ConfigHelper::checkPrivilege('superuser') ? 1 : 0);

if ($SESSION->is_set('cdlp', true) && !isset($_GET['page'])) {
    $SESSION->restore('cdlp', $_GET['page'], true);
} elseif ($SESSION->is_set('cdlp') && !isset($_GET['page'])) {
    $SESSION->restore('cdlp', $_GET['page']);
}

$page = (!isset($_GET['page']) ? 1 : intval($_GET['page']));

$SESSION->save('cdlp', $page);
$SESSION->save('cdlp', $page, true);

$divisionlist = $LMS->getDivisionList($params);
$total = empty($divisionlist) ? 0 : count($divisionlist);
$limit = intval(ConfigHelper::getConfig('phpui.divisionlist_pagelimit', $total));
$offset = ($page - 1) * $limit;
$params['offset'] = $offset;
$params['limit'] = $limit;

$divisionlist = $LMS->getDivisionList($params);
$pagination = LMSPaginationFactory::getPagination($page, $total, $limit, ConfigHelper::checkConfig('phpui.short_pagescroller'));

if ($divisionlist) {
    foreach ($divisionlist as $idx => $division) {
        $divisionUsers = $LMS->GetUserList(array('divisions' => $division['id']));
        if ($divisionUsers['total'] == 0) {
            $divisionlist[$idx]['unblock_delete'] = 1;
        }
    }
}

$SESSION->save('backto', $_SERVER['QUERY_STRING']);
$SESSION->save('backto', $_SERVER['QUERY_STRING'], true);

$SMARTY->assign('divisionlist', $divisionlist);
$SMARTY->assign('pagination', $pagination);

$SMARTY->display('division/divisionlist.html');
