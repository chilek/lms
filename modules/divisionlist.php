<?php

/*
 * LMS version 1.11-git
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

$divisionlist = $DB->GetAll('SELECT d.id, d.name, d.shortname, d.status,
	(SELECT COUNT(*) FROM customers WHERE divisionid = d.id) AS cnt 
	FROM divisions d ORDER BY d.shortname');

$listdata['total'] = empty($divisionlist) ? 0 : count($divisionlist);

if ($SESSION->is_set('cdlp') && !isset($_GET['page'])) {
    $SESSION->restore('cdlp', $_GET['page']);
}

$page = (!isset($_GET['page']) ? 1 : $_GET['page']);
$pagelimit = ConfigHelper::getConfig('phpui.divisionlist_pagelimit', $listdata['total']);
$start = ($page - 1) * $pagelimit;

$SESSION->save('cdlp', $page);

$layout['pagetitle'] = trans('Divisions List');

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$SMARTY->assign('pagelimit', $pagelimit);
$SMARTY->assign('page', $page);
$SMARTY->assign('start', $start);
$SMARTY->assign('divisionlist', $divisionlist);
$SMARTY->assign('listdata', $listdata);
$SMARTY->display('division/divisionlist.html');
