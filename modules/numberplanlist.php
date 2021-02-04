<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2021 LMS Developers
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

if ($SESSION->is_set('nplp') && !isset($_GET['page'])) {
    $SESSION->restore('nplp', $_GET['page']);
}

if (!isset($_POST['divisionid'])) {
    $SESSION->restore('npldivisionid', $divisionid);
} else {
    $divisionid = $_POST['divisionid'];
}
$SESSION->save('npldivisionid', $divisionid);

if (!isset($_POST['userid'])) {
    $SESSION->restore('npluserid', $userid);
} else {
    $userid = $_POST['userid'];
}
$SESSION->save('npluserid', $userid);

if (!isset($_POST['type'])) {
    $SESSION->restore('npltype', $type);
} else {
    $type = $_POST['type'];
}
$SESSION->save('npltype', $type);

$layout['pagetitle'] = trans('Numbering Plans List');

$params = array(
    'count' => true,
    'divisionid' => $divisionid,
    'userid' => $userid,
    'type' => $type,
);

$count = $LMS->getNumberPlanList($params);

$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = intval(ConfigHelper::getConfig('phpui.numberplanlist_pagelimit', $count));
$offset = ($page - 1) * $limit;
if ($offset > $count) {
    $offset = 0;
    $page = 1;
}

$SESSION->save('nplp', $page);

$pagination = LMSPaginationFactory::getPagination($page, $count, $limit, ConfigHelper::checkConfig('phpui.short_pagescroller'));

$params['count'] = false;
$params['offset'] = $offset;
$params['limit'] = $limit;

$numberplanlist = $LMS->getNumberPlanList($params);

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$divisions = $LMS->GetDivisions();
$users = $LMS->GetUsers(array(
    'divisions' => implode(',', array_keys($divisions)),
    'order' => 'rname,asc',
));

$SMARTY->assign('pagination', $pagination);
$SMARTY->assign('divisions', $divisions);
$SMARTY->assign('divisionid', $divisionid);
$SMARTY->assign('userid', $userid);
$SMARTY->assign('users', $users);
$SMARTY->assign('type', $type);

$SMARTY->assign('numberplanlist', $numberplanlist);

$SMARTY->display('numberplan/numberplanlist.html');
