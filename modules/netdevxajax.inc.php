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

function getManagementUrls($netdevid) {
	global $SMARTY, $DB;

	$result = new xajaxResponse();

	$netdevid = intval($netdevid);

	$mgmurls = NULL;
	$mgmurls = $DB->GetAll('SELECT id, url, comment FROM managementurls WHERE netdevid = ? ORDER BY id', array($netdevid));
	$SMARTY->assign('mgmurls', $mgmurls);
	$mgmurllist = $SMARTY->fetch('managementurl/managementurllist.html');

	$result->assign('managementurltable', 'innerHTML', $mgmurllist);

	return $result;
}

function addManagementUrl($netdevid, $params) {
	global $DB, $SYSLOG, $SYSLOG_RESOURCE_KEYS;

	$result = new xajaxResponse();

	if (empty($params['url']))
		return $result;

	$netdevid = intval($netdevid);

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
	$result->call('xajax_getManagementUrls', $netdevid);
	$result->assign('managementurladdlink', 'disabled', false);

	return $result;
}

function delManagementUrl($netdevid, $id) {
	global $DB, $SYSLOG, $SYSLOG_RESOURCE_KEYS;

	$result = new xajaxResponse();

	$netdevid = intval($netdevid);
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

function getRadioSectors($netdevid, $formdata = NULL) {
	global $SMARTY, $DB;

	$result = new xajaxResponse();

	$netdevid = intval($netdevid);

	$radiosectors = $DB->GetAll('SELECT * FROM netradiosectors WHERE netdev = ? ORDER BY name', array($netdevid));
	$SMARTY->assign('radiosectors', $radiosectors);
	if (isset($formdata['error']))
		$SMARTY->assign('error', $formdata['error']);
	$SMARTY->assign('formdata', $formdata);
	$radiosectorlist = $SMARTY->fetch('netdev/radiosectorlist.html');

	$result->assign('radiosectortable', 'innerHTML', $radiosectorlist);

	return $result;
}

function addRadioSector($netdevid, $params) {
	global $DB, $SYSLOG, $SYSLOG_RESOURCE_KEYS;

	$result = new xajaxResponse();

	$netdevid = intval($netdevid);

	if (!strlen($params['name']))
		$error['name'] = trans('Radio sector name cannot be empty!');
	elseif (strlen($params['name']) > 63)
		$error['name'] = trans('Radio sector name is too long!');
	elseif (!preg_match('/^[a-z0-9_\-]+$/i', $params['name']))
		$error['name'] = trans('Radio sector name contains invalid characters!');
	elseif ($DB->GetOne('SELECT 1 FROM netradiosectors WHERE UPPER(name) = UPPER(?)', array($params['name'])))
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

	if (!strlen($params['range']))
		$error['range'] = trans('Radio sector range cannot be empty!');
	elseif (!preg_match('/^[0-9]+$/', $params['range']))
		$error['range'] = trans('Radio sector range has invalid format!');

	$params['error'] = $error;

	if (!$error) {
		$args = array(
			'name' => $params['name'],
			'azimuth' => $params['azimuth'],
			'radius' => $params['radius'],
			'altitude' => $params['altitude'],
			'range' => $params['range'],
			$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NETDEV] => $netdevid,
		);
		$DB->Execute('INSERT INTO netradiosectors (name, azimuth, radius, altitude, range, netdev) VALUES (?, ?, ?, ?, ?, ?)',
			array_values($args));
		if ($SYSLOG) {
			$args[$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_RADIOSECTOR]] = $DB->GetLastInsertID('netradiosectors');
			$SYSLOG->AddMessage(SYSLOG_RES_RADIOSECTOR, SYSLOG_OPER_ADD, $args,
				array($SYSLOG_RESOURCE_KEYS[SYSLOG_RES_RADIOSECTOR], $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NETDEV]));
		}
		$params = NULL;
	}
	$result->call('xajax_getRadioSectors', $netdevid, $params);
	$result->assign('radiosectoraddlink', 'disabled', false);

	return $result;
}

function delRadioSector($netdevid, $id) {
	global $DB, $SYSLOG, $SYSLOG_RESOURCE_KEYS;

	$result = new xajaxResponse();

	$netdevid = intval($netdevid);
	$id = intval($id);

	$res = $DB->Execute('DELETE FROM netradiosectors WHERE id = ?', array($id));
	if ($res && $SYSLOG) {
		$args = array(
			$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_RADIOSECTOR] => $id,
			$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NETDEV] => $netdevid,
		);
		$SYSLOG->AddMessage(SYSLOG_RES_RADIOSECTOR, SYSLOG_OPER_DELETE, $args, array_keys($args));
	}
	$result->call('xajax_getRadioSectors', $netdevid, NULL);
	$result->assign('radiosectortable', 'disabled', false);

	return $result;
}


$LMS->InitXajax();
$LMS->RegisterXajaxFunction(array(
	'getManagementUrls','addManagementUrl', 'delManagementUrl',
	'getRadioSectors', 'addRadioSector', 'delRadioSector',
));
$SMARTY->assign('xajax', $LMS->RunXajax());

?>
