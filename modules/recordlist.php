<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2009 Webvisor Sp. z o.o.
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
 */

if (!isset($_GET['d'])) {
    $SESSION->restore('ald', $d);
} else {
    $d = $_GET['d'];
}
$SESSION->save('ald', $d);

// this may happen after logout
if (!$d) {
    $d = $DB->GetOne('SELECT id FROM domains ORDER BY name LIMIT 1');
}

$recordslist = $DB->GetAll(
    'SELECT *,
	(CASE WHEN type=\'TXT\' THEN 1
		WHEN type=\'MX\' THEN 2
		WHEN type=\'NS\' THEN 3
		WHEN type=\'SOA\' THEN 4
		ELSE 0 END) AS ord
	FROM records WHERE domain_id = ? ORDER BY ord desc, prio, name',
    array($d)
);

$listdata['total'] = count($recordslist);
$listdata['domain'] = $d;
$listdata['domainName'] = $domain['name'];

if ($domain['type'] == 'SLAVE') {
    $showAddEdit = false;
} else {
    $showAddEdit = true;
}

$page = (!isset($_GET['page']) ? 1 : $_GET['page']);
$pagelimit = ConfigHelper::getConfig('phpui.recordlist_pagelimit', $listdata['total']);
$start = ($page - 1) * $pagelimit;

$SESSION->save('alp', $page);

$layout['pagetitle'] = trans('DNS Records List');

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$SMARTY->assign('pagelimit', $pagelimit);
$SMARTY->assign('page', $page);
$SMARTY->assign('start', $start);
$SMARTY->assign('recordslist', $recordslist);
$SMARTY->assign('listdata', $listdata);
$SMARTY->assign('showaddedit', $showAddEdit);
$SMARTY->assign('domainlist', $DB->GetAll('SELECT id, name FROM domains ORDER BY name'));
$SMARTY->display('record/recordlist.html');
