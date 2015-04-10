<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2015 LMS Developers
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

function validateManagementUrl($params, $update = false) {
	global $DB;

	$error = NULL;

	if (!strlen($params['url']))
		$error['url'] = trans('Management URL cannot be empty!');
	elseif (strlen($params['url']) < 10)
		$error['url'] = trans('Management URL is too short!');

	return $error;
}

function getManagementUrls($formdata = NULL) {
	global $SMARTY, $DB;

	$result = new xajaxResponse();

	$netdevid = intval($_GET['id']);

	$mgmurls = NULL;
	$mgmurls = $DB->GetAll('SELECT id, url, comment FROM managementurls WHERE netdevid = ? ORDER BY id', array($netdevid));
	$SMARTY->assign('mgmurls', $mgmurls);
	if (isset($formdata['error']))
		$SMARTY->assign('error', $formdata['error']);
	$SMARTY->assign('formdata', $formdata);
	$mgmurllist = $SMARTY->fetch('managementurl/managementurllist.html');

	$result->assign('managementurltable', 'innerHTML', $mgmurllist);

	return $result;
}

function addManagementUrl($params) {
	global $DB, $SYSLOG, $SYSLOG_RESOURCE_KEYS;

	$result = new xajaxResponse();

	$error = validateManagementUrl($params);

	$params['error'] = $error;

	$netdevid = intval($_GET['id']);

	if (!$error) {
		if (!preg_match('/^[[:alnum:]]+:\/\/.+/i', $params['url']))
			$params['url'] = 'http://' . $params['url'];

		$args = array(
			$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NETDEV] => $netdevid,
			'url' => $params['url'],
			'comment' => $params['comment'],
		);
		$DB->Execute('INSERT INTO managementurls (netdevid, url, comment) VALUES (?, ?, ?)', array_values($args));
		if ($SYSLOG) {
			$args[$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_MGMTURL]] = $DB->GetLastInsertID('managementurls');
			$SYSLOG->AddMessage(SYSLOG_RES_MGMTURL, SYSLOG_OPER_ADD, $args,
				array($SYSLOG_RESOURCE_KEYS[SYSLOG_RES_MGMTURL], $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NETDEV]));
		}
		$params = NULL;
	}

	$result->call('xajax_getManagementUrls', $params);
	$result->assign('managementurladdlink', 'disabled', false);

	return $result;
}

function delManagementUrl($id) {
	global $DB, $SYSLOG, $SYSLOG_RESOURCE_KEYS;

	$result = new xajaxResponse();

	$netdevid = intval($_GET['id']);
	$id = intval($id);

	$res = $DB->Execute('DELETE FROM managementurls WHERE id = ?', array($id));
	if ($res && $SYSLOG) {
		$args = array(
			$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_MGMTURL] => $id,
			$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NETDEV] => $netdevid,
		);
		$SYSLOG->AddMessage(SYSLOG_RES_MGMTURL, SYSLOG_OPER_DELETE, $args, array_keys($args));
	}
	$result->call('xajax_getManagementUrls', $netdevid);
	$result->assign('managementurltable', 'disabled', false);

	return $result;
}

function updateManagementUrl($urlid, $params) {
	global $DB, $SYSLOG, $SYSLOG_RESOURCE_KEYS;

	$result = new xajaxResponse();

	$urlid = intval($urlid);
	$netdevid = intval($_GET['id']);

	$res = validateManagementUrl($params, true);

	$error = array();
	foreach ($res as $key => $val)
		$error[$key . '_edit_' . $urlid] = $val;
	$params['error'] = $error;

	if (!$error) {
		if (!preg_match('/^[[:alnum:]]+:\/\/.+/i', $params['url']))
			$params['url'] = 'http://' . $params['url'];

		$args = array(
			'url' => $params['url'],
			'comment' => $params['comment'],
			$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_MGMTURL] => $urlid,
		);
		$DB->Execute('UPDATE managementurls SET url = ?, comment = ? WHERE id = ?', array_values($args));
		if ($SYSLOG) {
			$args[$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NETDEV]] = $netdevid;
			$SYSLOG->AddMessage(SYSLOG_RES_MGMTURL, SYSLOG_OPER_UPDATE, $args,
				array($SYSLOG_RESOURCE_KEYS[SYSLOG_RES_MGMTURL], $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NETDEV]));
		}
		$params = NULL;
	}

	$result->call('xajax_getManagementUrls', $params);
	$result->assign('managementurltable', 'disabled', false);

	return $result;
}

function validateRadioSector($params, $update = false) {
	global $DB;

	$error = NULL;

	if (!strlen($params['name']))
		$error['name'] = trans('Radio sector name cannot be empty!');
	elseif (strlen($params['name']) > 63)
		$error['name'] = trans('Radio sector name is too long!');
	elseif (!preg_match('/^[a-z0-9_\-]+$/i', $params['name']))
		$error['name'] = trans('Radio sector name contains invalid characters!');
	elseif (!$update && $DB->GetOne('SELECT 1 FROM netradiosectors WHERE UPPER(name) = UPPER(?)', array($params['name'])))
		$error['name'] = trans('Radio sector with entered name already exists for this network device!');

	if (!strlen($params['azimuth']))
		$error['azimuth'] = trans('Radio sector azimuth cannot be empty!');
	elseif (!preg_match('/^[0-9]+(\.[0-9]+)?$/', $params['azimuth']))
		$error['azimuth'] = trans('Radio sector azimuth has invalid format!');

	if (!strlen($params['radius']))
		$error['radius'] = trans('Radio sector radius cannot be empty!');
	elseif (!preg_match('/^[0-9]+(\.[0-9]+)?$/', $params['radius']))
		$error['radius'] = trans('Radio sector radius has invalid format!');

	if (!strlen($params['altitude']))
		$error['altitude'] = trans('Radio sector altitude cannot be empty!');
	elseif (!preg_match('/^[0-9]+$/', $params['altitude']))
		$error['altitude'] = trans('Radio sector altitude has invalid format!');

	if (!strlen($params['rsrange']))
		$error['rsrange'] = trans('Radio sector range cannot be empty!');
	elseif (!preg_match('/^[0-9]+$/', $params['rsrange']))
		$error['rsrange'] = trans('Radio sector range has invalid format!');

	return $error;
}

function getRadioSectors($formdata = NULL) {
	global $SMARTY, $DB;

	$result = new xajaxResponse();

	$netdevid = intval($_GET['id']);

	$radiosectors = $DB->GetAll('SELECT * FROM netradiosectors WHERE netdev = ? ORDER BY name', array($netdevid));
	$SMARTY->assign('radiosectors', $radiosectors);
	if (isset($formdata['error']))
		$SMARTY->assign('error', $formdata['error']);
	$SMARTY->assign('formdata', $formdata);
	$radiosectorlist = $SMARTY->fetch('netdev/radiosectorlist.html');

	$result->assign('radiosectortable', 'innerHTML', $radiosectorlist);

	return $result;
}

function addRadioSector($params) {
	global $DB, $SYSLOG, $SYSLOG_RESOURCE_KEYS;

	$result = new xajaxResponse();

	$netdevid = intval($_GET['id']);

	$error = validateRadioSector($params);

	$params['error'] = $error;

	if (!$error) {
		$args = array(
			'name' => $params['name'],
			'azimuth' => $params['azimuth'],
			'radius' => $params['radius'],
			'altitude' => $params['altitude'],
			'rsrange' => $params['rsrange'],
			$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NETDEV] => $netdevid,
		);
		$DB->Execute('INSERT INTO netradiosectors (name, azimuth, radius, altitude, rsrange, netdev) VALUES (?, ?, ?, ?, ?, ?)',
			array_values($args));
		if ($SYSLOG) {
			$args[$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_RADIOSECTOR]] = $DB->GetLastInsertID('netradiosectors');
			$SYSLOG->AddMessage(SYSLOG_RES_RADIOSECTOR, SYSLOG_OPER_ADD, $args,
				array($SYSLOG_RESOURCE_KEYS[SYSLOG_RES_RADIOSECTOR], $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NETDEV]));
		}
		$params = NULL;
	}
	$result->call('xajax_getRadioSectors', $params);
	$result->assign('radiosectoraddlink', 'disabled', false);

	return $result;
}

function delRadioSector($id) {
	global $DB, $SYSLOG, $SYSLOG_RESOURCE_KEYS;

	$result = new xajaxResponse();

	$netdevid = intval($_GET['id']);
	$id = intval($id);

	$res = $DB->Execute('DELETE FROM netradiosectors WHERE id = ?', array($id));
	if ($res && $SYSLOG) {
		$args = array(
			$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_RADIOSECTOR] => $id,
			$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NETDEV] => $netdevid,
		);
		$SYSLOG->AddMessage(SYSLOG_RES_RADIOSECTOR, SYSLOG_OPER_DELETE, $args, array_keys($args));
	}
	$result->call('xajax_getRadioSectors', NULL);
	$result->assign('radiosectortable', 'disabled', false);

	return $result;
}

function updateRadioSector($rsid, $params) {
	global $DB;

	$result = new xajaxResponse();

	$rsid = intval($rsid);
	$netdevid = intval($_GET['id']);

	$res = validateRadioSector($params, true);
	$error = array();
	foreach ($res as $key => $val)
		$error[$key . '_edit_' . $rsid] = $val;
	$params['error'] = $error;

	if (!$error) {
		$args = array(
			'name' => $params['name'],
			'azimuth' => $params['azimuth'],
			'radius' => $params['radius'],
			'altitude' => $params['altitude'],
			'rsrange' => $params['rsrange'],
			$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_RADIOSECTOR] => $rsid,
		);
		$DB->Execute('UPDATE netradiosectors SET name = ?, azimuth = ?, radius = ?, altitude = ?,
			rsrange = ? WHERE id = ?', array_values($args));
		if ($SYSLOG) {
			$args[$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NETDEV]] = $netdevid;
			$SYSLOG->AddMessage(SYSLOG_RES_RADIOSECTOR, SYSLOG_OPER_UPDATE, $args,
				array($SYSLOG_RESOURCE_KEYS[SYSLOG_RES_RADIOSECTOR], $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NETDEV]));
		}
		$params = NULL;
	}

	$result->call('xajax_getRadioSectors', $params);

	return $result;
}

function getRadioSectorsForNetdev($devid) {
	global $DB;

	$result = new xajaxResponse();

	$radiosectors = $DB->GetAll('SELECT id, name FROM netradiosectors WHERE netdev = ? ORDER BY name', array(intval($devid)));
	$result->call('radio_sectors_received', $radiosectors);

	return $result;
}

$LMS->InitXajax();
$LMS->RegisterXajaxFunction(array(
	'getManagementUrls','addManagementUrl', 'delManagementUrl', 'updateManagementUrl',
	'getRadioSectors', 'addRadioSector', 'delRadioSector', 'updateRadioSector',
	'getRadioSectorsForNetdev',
));
$SMARTY->assign('xajax', $LMS->RunXajax());

?>
