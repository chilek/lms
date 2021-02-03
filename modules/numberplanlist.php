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

$layout['pagetitle'] = trans('Numbering Plans List');

$count = $LMS->getNumberPlanList(array('count' => true));

$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = intval(ConfigHelper::getConfig('phpui.numberplanlist_pagelimit', $count));
$offset = ($page - 1) * $limit;

$SESSION->save('nplp', $page);

$pagination = LMSPaginationFactory::getPagination($page, $count, $limit, ConfigHelper::checkConfig('phpui.short_pagescroller'));

$numberplanlist = $LMS->getNumberPlanList(array('count' => false, 'offset' => $offset, 'limit' => $limit));

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$SMARTY->assign('pagination', $pagination);
$SMARTY->assign('numberplanlist', $numberplanlist);

$SMARTY->display('numberplan/numberplanlist.html');
