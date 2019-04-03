<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2019 LMS Developers
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

if (isset($_GET['ajax'])) {
	$search = urldecode(trim($_GET['what']));

	switch ($_GET['mode']) {
		case 'netdev':
			$candidates = $DB->GetAll('SELECT
					nd.name AS item,
					n.ipaddr,
					COUNT(nd.id) AS entries
				FROM netdevices nd
				LEFT JOIN nodes n ON n.netdev = nd.id AND n.ownerid IS NULL
				WHERE LOWER(nd.name) ?LIKE? LOWER(' . $DB->Escape('%' . $search . '%') . ')
					OR INET_NTOA(n.ipaddr) ?LIKE? (' . $DB->Escape('%' . $search . '%') . ')
				GROUP BY item, n.ipaddr
				ORDER BY entries DESC, item ASC
				LIMIT 15');
			break;
	}

	$result = array();

	if ($candidates)
		foreach ($candidates as $idx => $row) {
			$name = $row['ipaddr']
				&& preg_match('/' . str_replace('.','\\.', $search) . '/', long_ip($row['ipaddr']))
				? long_ip($row['ipaddr']) : $row['item'];
			$name_class = '';
			$description = $row['entries'] . ' ' . trans('entries');
			$description_class = '';
			$action = '';

			$result[$row['item']] = compact('name', 'name_long', 'name_class', 'description', 'description_class', 'action');
		}
	header('Content-Type: application/json');
	if (!empty($result))
		echo json_encode(array_values($result));
	exit;
}

function get_loc_boroughs($districtid) {
	global $DB, $BOROUGHTYPES;

	$list = $DB->GetAll('SELECT b.id, b.name AS borough, b.type AS btype
		FROM location_boroughs b
		WHERE b.districtid = ?
		ORDER BY b.name, b.type', array($districtid));

	if ($list)
		foreach ($list as $idx => $row) {
			$name = sprintf('%s (%s)', $row['borough'], $BOROUGHTYPES[$row['btype']]);
			$list[$idx] = array('id' => $row['id'], 'name' => $name);
		}

	return $list;
}

function select_location($what, $id) {
	global $DB;

	$JSResponse = new xajaxResponse();

	if ($what == 'search[state]')
		$stateid = $id;
	else if ($what == 'search[district]')
		$districtid = $id;
	else if ($what == 'search[borough]')
		$boroughid = $id;

	if ($stateid) {
		$list = $DB->GetAll('SELECT id, name
			FROM location_districts WHERE stateid = ?
			ORDER BY name', array($stateid));

		$JSResponse->call('update_selection', 'district', $list ? $list : array(), !$what ? $districtid : 0);
	}

	if ($districtid) {
		$list = get_loc_boroughs($districtid);
		$JSResponse->call('update_selection', 'borough', $list ? $list : array(), !$what ? $boroughid : 0);
	}

	return $JSResponse;
}

function connect_nodes($nodeids, $deviceid, $linktype, $linktechnology, $linkspeed) {
	global $DB;

	$JSResponse = new xajaxResponse();

	$DB->BeginTrans();
	foreach ($nodeids as $nodeid)
		$DB->Execute("UPDATE nodes SET netdev = ?, port = 0, linktype = ?, linktechnology = ?, linkspeed = ? WHERE id = ?",
			array($deviceid, $linktype, $linktechnology, $linkspeed, $nodeid));
	$DB->CommitTrans();

	$JSResponse->call('operation_finished');

	return $JSResponse;
}

function assign_nodes($nodeids, $nodegroupid) {
	$JSResponse = new xajaxResponse();

	$DB = LMSDB::getInstance();

	foreach ($nodeids as $nodeid)
		$DB->Execute("INSERT INTO nodegroupassignments (nodeid, nodegroupid) VALUES (?, ?)",
			array($nodeid, $nodegroupid));

	$JSResponse->call('operation_finished');

	return $JSResponse;
}

function unassign_nodes($nodeids, $nodegroupid) {
	$JSResponse = new xajaxResponse();

	$DB = LMSDB::getInstance();

	foreach ($nodeids as $nodeid)
		$DB->Execute("DELETE FROM nodegroupassignments WHERE nodeid = ? AND nodegroupid = ?",
			array($nodeid, $nodegroupid));

	$JSResponse->call('operation_finished');

	return $JSResponse;
}

function macformat($mac) {
	$res = str_replace('-', ':', $mac);
	// allow eg. format "::ab:3::12", only whole addresses
	if (preg_match('/^([0-9a-f]{0,2}):([0-9a-f]{0,2}):([0-9a-f]{0,2}):([0-9a-f]{0,2}):([0-9a-f]{0,2}):([0-9a-f]{0,2})$/i', $mac, $arr)) {
		$res = '';
		for ($i = 1; $i <= 6; $i++) {
			if ($i > 1)
				$res .= ':';
			if (strlen($arr[$i]) == 1)
				$res .= '0';
			if (strlen($arr[$i]) == 0)
				$res .= '00';

			$res .= $arr[$i];
		}
	}
	else { // other formats eg. cisco xxxx.xxxx.xxxx or parts of addresses
		$tmp = preg_replace('/[^0-9a-f]/i', '', $mac);

		if (strlen($tmp) == 12) // we've the whole address
			if (check_mac($tmp))
				$res = $tmp;
	}
	return $res;
}

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

if (isset($_POST['search']))
	$nodesearch = $_POST['search'];

if (!isset($nodesearch))
	$SESSION->restore('nodesearch', $nodesearch);
else
	$SESSION->save('nodesearch', $nodesearch);
if (!isset($_GET['o']))
	$SESSION->restore('nslo', $o);
else
	$o = $_GET['o'];
$SESSION->save('nslo', $o);

if (!isset($_POST['k']))
	$SESSION->restore('nslk', $k);
else
	$k = $_POST['k'];
$SESSION->save('nslk', $k);

// MAC address reformatting
$nodesearch['mac'] = macformat($nodesearch['mac']);

$LMS->InitXajax();

if (isset($_GET['search'])) {
	$LMS->RegisterXajaxFunction(array('connect_nodes', 'assign_nodes', 'unassign_nodes'));
	$SMARTY->assign('xajax', $LMS->RunXajax());

	$error = null;

	if (!empty($nodesearch['lastonlinebefore'])) {
		$lastonlinebefore = datetime_to_timestamp($nodesearch['lastonlinebefore']);
		if (empty($lastonlinebefore))
			$error['lastonlinebefore'] = trans('Enter date in YYYY/MM/DD hh:mm format (empty field means ignore) or click to choose it from calendar');
	}

	if (!empty($nodesearch['lastonlineafter'])) {
		$lastonlineafter = datetime_to_timestamp($nodesearch['lastonlineafter']);
		if (empty($lastonlineafter))
			$error['lastonlineafter'] = trans('Enter date in YYYY/MM/DD hh:mm format (empty field means ignore) or click to choose it from calendar');
	}

	if (!$error) {
		if (isset($lastonlinebefore))
			$nodesearch['lastonlinebefore'] = $lastonlinebefore;
		if (isset($lastonlineafter))
			$nodesearch['lastonlineafter'] = $lastonlineafter;

		$nodelist = $LMS->GetNodeList(array('order' => $o, 'search' => $nodesearch, 'sqlskey' => $k));

		$layout['pagetitle'] = trans('Nodes Search Results');

		$listdata['total'] = $nodelist['total'];
		$listdata['order'] = $nodelist['order'];
		$listdata['direction'] = $nodelist['direction'];
		$listdata['totalon'] = $nodelist['totalon'];
		$listdata['totaloff'] = $nodelist['totaloff'];

		unset($nodelist['total']);
		unset($nodelist['order']);
		unset($nodelist['direction']);
		unset($nodelist['totalon']);
		unset($nodelist['totaloff']);

		if ($SESSION->is_set('nslp') && !isset($_GET['page']))
			$SESSION->restore('nslp', $_GET['page']);

		$page = (!isset($_GET['page']) ? 1 : $_GET['page']);

		$pagelimit = ConfigHelper::getConfig('phpui.nodelist_pagelimit', $listdata['total']);
		$start = ($page - 1) * $pagelimit;
		$SESSION->save('nslp', $page);

		$SMARTY->assign('page', $page);
		$SMARTY->assign('pagelimit', $pagelimit);
		$SMARTY->assign('start', $start);
		$SMARTY->assign('nodelist', $nodelist);
		$SMARTY->assign('listdata', $listdata);

		$netdevlist = $LMS->GetNetDevList();
		unset($netdevlist['total']);
		unset($netdevlist['order']);
		unset($netdevlist['direction']);
		$SMARTY->assign('netdevlist', $netdevlist);

		if (isset($_GET['print']))
			$SMARTY->display('print/printnodelist.html');
		elseif ($listdata['total'] == 1)
			$SESSION->redirect('?m=nodeinfo&id=' . $nodelist[0]['id']);
		else {
			$SMARTY->assign('nodegroups', $LMS->GetNodeGroupNames());
			$SMARTY->display('node/nodesearchresults.html');
		}
		$SESSION->close();
		die;
	} else {
		$SMARTY->assign('error', $error);
		$SMARTY->assign('nodesearch', $nodesearch);
	}
}

$LMS->RegisterXajaxFunction('select_location');
$SMARTY->assign('xajax', $LMS->RunXajax());

$layout['pagetitle'] = trans('Nodes Search');

$SESSION->remove('nslp');

$SMARTY->assign('states', $DB->GetAll('SELECT id, name, ident FROM location_states ORDER BY name'));
$SMARTY->assign('k', $k);

$SMARTY->display('node/nodesearch.html');

?>
