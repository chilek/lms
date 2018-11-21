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

if ($api) {
	$filter['order'] = 'name,asc';
	$filter['search'] = array(
		'project' => null,
	);
	$filter['status'] = null;
	$filter['network'] = null;
	$filter['status'] = null;
	$filter['customergroup'] = null;
	$filter['nodegroup'] = null;
	$filter['offset'] = null;
	$filter['limit'] = null;
} else {
	$layout['pagetitle'] = trans('Nodes List');

	$SESSION->save('backto', $_SERVER['QUERY_STRING']);

	if (isset($_GET['o']))
		$filter['order'] = $_GET['o'];

	if (isset($_GET['s']))
		$filter['status'] = $_GET['s'];

	if (isset($_GET['n']))
		$filter['network'] = $_GET['n'];

	if (isset($_GET['g']))
		$filter['customergroup'] = $_GET['g'];

	if (isset($_GET['ng']))
		$filter['nodegroup'] = $_GET['ng'];

	if (isset($_GET['p'])) {
		if (!isset($filter['search']))
			$filter['search'] = array();
		$filter['search']['project'] = $_GET['p'];
	}

	if (isset($_GET['page']))
		$filter['page'] = intval($_GET['page']);
	elseif (!isset($filter['page']) || empty($filter['page']))
		$filter['page'] = 1;

	$SESSION->saveFilter($filter);

	$filter['count'] = true;
	$filter['sqlskey'] = null;
	$filter['limit'] = null;
	$filter['offset'] = null;
	$filter['total'] = intval($LMS->GetNodeList($filter));

	$filter['count'] = false;
	$filter['limit'] = intval(ConfigHelper::getConfig('phpui.nodelist_pagelimit', $filter['total']));
	$filter['offset'] = ($filter['page'] - 1) * $filter['limit'];
}

$nodelist = $LMS->GetNodeList($filter);

if (!$api) {
	$pagination = LMSPaginationFactory::getPagination($filter['page'], $filter['total'], $filter['limit'],
		ConfigHelper::checkConfig('phpui.short_pagescroller'));

	$filter['order'] = $nodelist['order'];
	$filter['direction'] = $nodelist['direction'];
	$filter['totalon'] = $nodelist['totalon'];
	$filter['totaloff'] = $nodelist['totaloff'];
}

unset($nodelist['total']);
unset($nodelist['order']);
unset($nodelist['direction']);
unset($nodelist['totalon']);
unset($nodelist['totaloff']);

if ($api) {
	header('Content-Type: application/json');
	echo json_encode(array_values($nodelist));
	die;
}

$SMARTY->assign('nodelist',$nodelist);
$SMARTY->assign('pagination',$pagination);
$SMARTY->assign('networks',$LMS->GetNetworks());
$SMARTY->assign('nodegroups', $LMS->GetNodeGroupNames());
$SMARTY->assign('customergroups', $LMS->CustomergroupGetAll());
$SMARTY->assign('NNprojects', $LMS->GetProjects());

$SMARTY->display('node/nodelist.html');

?>
