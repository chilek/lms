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

function validateManagementUrl($params) {
	$error = NULL;

	if (!strlen($params['url']))
		$error['url'] = trans('Management URL cannot be empty!');
	elseif (strlen($params['url']) < 10)
		$error['url'] = trans('Management URL is too short!');

	return $error;
}

function getManagementUrls() {
	global $SMARTY, $DB;

	$result = new xajaxResponse();

	$netdevid = intval($_GET['id']);

	$mgmurls = NULL;
	$mgmurls = $DB->GetAll('SELECT id, url, comment FROM managementurls WHERE netdevid = ? ORDER BY id', array($netdevid));
	$SMARTY->assign('mgmurls', $mgmurls);
	$mgmurllist = $SMARTY->fetch('managementurl/managementurllist.html');

	$result->assign('managementurltable', 'innerHTML', $mgmurllist);

	return $result;
}

function addManagementUrl($params) {
	global $DB, $SYSLOG;

	$DB = LMSDB::getInstance();

	$result = new xajaxResponse();

	$formdata = array();
	parse_str($params, $formdata);

	$error = validateManagementUrl($formdata);

	$netdevid = intval($_GET['id']);

	if (!$error) {
		if (!preg_match('/^[[:alnum:]]+:\/\/.+/i', $formdata['url']))
			$formdata['url'] = 'http://' . $formdata['url'];

		$args = array(
			SYSLOG::RES_NETDEV => $netdevid,
			'url' => $formdata['url'],
			'comment' => $formdata['comment'],
		);
		$DB->Execute('INSERT INTO managementurls (netdevid, url, comment) VALUES (?, ?, ?)', array_values($args));
		if ($SYSLOG) {
			$args[SYSLOG::RES_MGMTURL] = $DB->GetLastInsertID('managementurls');
			$SYSLOG->AddMessage(SYSLOG::RES_MGMTURL, SYSLOG::OPER_ADD, $args);
		}

		$result->call('hideAddManagementUrl');
		$result->call('getManagementUrls');
	} else
		$result->call('managementUrlResponse', $error);

	$result->assign('managementurladdlink', 'disabled', false);

	return $result;
}

function delManagementUrl($id) {
	global $SYSLOG;

	$result = new xajaxResponse();

	$netdevid = intval($_GET['id']);
	$id = intval($id);

	$DB = LMSDB::getInstance();
	$res = $DB->Execute('DELETE FROM managementurls WHERE id = ?', array($id));
	if ($res && $SYSLOG) {
		$args = array(
			SYSLOG::RES_MGMTURL => $id,
			SYSLOG::RES_NETDEV => $netdevid,
		);
		$SYSLOG->AddMessage(SYSLOG::RES_MGMTURL, SYSLOG::OPER_DELETE, $args);
	}
	$result->call('getManagementUrls');
	$result->assign('managementurltable', 'disabled', false);

	return $result;
}

function updateManagementUrl($urlid, $params) {
	global $SYSLOG;

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
			SYSLOG::RES_MGMTURL => $urlid,
		);
		$DB = LMSDB::getInstance();
		$DB->Execute('UPDATE managementurls SET url = ?, comment = ? WHERE id = ?', array_values($args));
		if ($SYSLOG) {
			$args[SYSLOG::RES_NETDEV] = $netdevid;
			$SYSLOG->AddMessage(SYSLOG::RES_MGMTURL, SYSLOG::OPER_UPDATE, $args);
		}
		$params = NULL;
	}

	$result->call('getManagementUrls', $params);
	$result->call('managementUrlResponse', $error);
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

	if (!strlen($params['azimuth']))
		$error['azimuth'] = trans('Radio sector azimuth cannot be empty!');
	elseif (!preg_match('/^[0-9]+(\.[0-9]+)?$/', $params['azimuth']))
		$error['azimuth'] = trans('Radio sector azimuth has invalid format!');
	elseif ($params['azimuth'] >= 360)
		$error['azimuth'] = trans('Radio sector azimuth should be less than 360 degrees!');

	if (!strlen($params['width']))
		$error['width'] = trans('Radio sector angular width cannot be empty!');
	elseif (!preg_match('/^[0-9]+(\.[0-9]+)?$/', $params['width']))
		$error['width'] = trans('Radio sector angular width has invalid format!');
	elseif ($params['width'] > 360)
		$error['width'] = trans('Radio sector angular width should be less than 360 degrees!');

	if (!strlen($params['altitude']))
		$error['altitude'] = trans('Radio sector altitude cannot be empty!');
	elseif (!preg_match('/^[0-9]+$/', $params['altitude']))
		$error['altitude'] = trans('Radio sector altitude has invalid format!');

	if (!strlen($params['rsrange']))
		$error['rsrange'] = trans('Radio sector range cannot be empty!');
	elseif (!preg_match('/^[0-9]+$/', $params['rsrange']))
		$error['rsrange'] = trans('Radio sector range has invalid format!');

	if (strlen($params['license']) > 63)
		$error['license'] = trans('Radio sector license number is too long!');

    if (strlen($params['secret']) > 63)
        $error['secret'] = trans('Encryption key is too long!');

	if (strlen($params['frequency']) && !preg_match('/^[0-9]{1,3}(\.[0-9]{1,5})?$/', $params['frequency']))
		$error['frequency'] = trans('Radio sector frequency has invalid format!');

	if (strlen($params['frequency2'])) {
		if (!strlen($params['frequency']))
			$error['frequency2'] = trans('Radio sector second frequency should be also empty if first frequency is empty!');
		elseif (!preg_match('/^[0-9]{1,3}(\.[0-9]{1,5})?$/', $params['frequency2']))
			$error['frequency2'] = trans('Radio sector frequency has invalid format!');
	}

	if (strlen($params['bandwidth']) && !preg_match('/^[0-9]{1,4}?$/', $params['bandwidth']))
		$error['bandwidth'] = trans('Radio sector bandwidth has invalid format!');

	return $error;
}

function getRadioSectors($formdata = NULL, $result = NULL) {
	global $SMARTY, $DB;

	if (! $result)
		$result = new xajaxResponse();

	$netdevid = intval($_GET['id']);

	$radiosectors = $DB->GetAll('SELECT s.*, (CASE WHEN n.computers IS NULL THEN 0 ELSE n.computers END) AS computers,
		((CASE WHEN l1.devices IS NULL THEN 0 ELSE l1.devices END)
		+ (CASE WHEN l2.devices IS NULL THEN 0 ELSE l2.devices END)) AS devices
		FROM netradiosectors s
		LEFT JOIN (
			SELECT linkradiosector AS rs, COUNT(*) AS computers
			FROM nodes n WHERE n.ownerid IS NOT NULL AND linkradiosector IS NOT NULL
			GROUP BY rs
		) n ON n.rs = s.id
		LEFT JOIN (
			SELECT srcradiosector, COUNT(*) AS devices FROM netlinks GROUP BY srcradiosector
		) l1 ON l1.srcradiosector = s.id
		LEFT JOIN (
			SELECT dstradiosector, COUNT(*) AS devices FROM netlinks GROUP BY dstradiosector
		) l2 ON l2.dstradiosector = s.id
		WHERE s.netdev = ?
		ORDER BY s.name', array($netdevid));
	foreach ($radiosectors as $rsidx => $radiosector)
		if (!empty($radiosector['bandwidth']))
			$radiosectors[$rsidx]['bandwidth'] *= 1000;
	$SMARTY->assign('radiosectors', $radiosectors);
	if (isset($formdata['error']))
		$SMARTY->assign('error', $formdata['error']);
	$SMARTY->assign('formdata', $formdata);
	$radiosectorlist = $SMARTY->fetch('netdev/radiosectorlist.html');

	$result->assign('radiosectortable', 'innerHTML', $radiosectorlist);

	return $result;
}

function addRadioSector($params) {
	global $DB, $SYSLOG;

	$result = new xajaxResponse();

	$netdevid = intval($_GET['id']);

	$error = validateRadioSector($params);

	$params['error'] = $error;

	if (!$error) {
		$args = array(
			'name' => $params['name'],
			'azimuth' => $params['azimuth'],
			'width' => $params['width'],
			'altitude' => $params['altitude'],
			'rsrange' => $params['rsrange'],
			'license' => (strlen($params['license']) ? $params['license'] : null),
			'technology' => intval($params['technology']),
			'type' => intval($params['type']),
			'frequency' => (strlen($params['frequency']) ? $params['frequency'] : null),
			'frequency2' => (strlen($params['frequency2']) ? $params['frequency2'] : null),
			'bandwidth' => (strlen($params['bandwidth']) ? str_replace(',', '.', $params['bandwidth'] / 1000) : null),
			SYSLOG::RES_NETDEV => $netdevid,
			'secret' => intval($params['secret']),
		);
		$DB->Execute('INSERT INTO netradiosectors (name, azimuth, width, altitude, rsrange, license, technology, type,
			frequency, frequency2, bandwidth, netdev, secret)
			VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
			array_values($args));
		if ($SYSLOG) {
			$args[SYSLOG::RES_RADIOSECTOR] = $DB->GetLastInsertID('netradiosectors');
			$SYSLOG->AddMessage(SYSLOG::RES_RADIOSECTOR, SYSLOG::OPER_ADD, $args);
		}
		$params = NULL;
	}
	$result = getRadioSectors($params, $result);
	//$result->call('xajax_getRadioSectors', $params);
	$result->assign('add_new_radiosector_button', 'disabled', false);
	$result->assign('cancel_new_radiosector_button', 'disabled', false);
	$result->call('get_radio_sectors_for_self_netdev', null);
	$result->call('change_nodelinktechnology', null);

	return $result;
}

function delRadioSector($id) {
	global $DB, $SYSLOG;

	$result = new xajaxResponse();

	$netdevid = intval($_GET['id']);
	$id = intval($id);

	$res = $DB->Execute('DELETE FROM netradiosectors WHERE id = ?', array($id));
	if ($res && $SYSLOG) {
		$args = array(
			SYSLOG::RES_RADIOSECTOR => $id,
			SYSLOG::RES_NETDEV => $netdevid,
		);
		$SYSLOG->AddMessage(SYSLOG::RES_RADIOSECTOR, SYSLOG::OPER_DELETE, $args);
	}
	$result->call('xajax_getRadioSectors', NULL);
	$result->assign('radiosectortable', 'disabled', false);
	$result->call('get_radio_sectors_for_self_netdev', null);
	$result->call('change_nodelinktechnology', null);

	return $result;
}

function updateRadioSector($rsid, $params) {
	global $DB, $SYSLOG;

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
			'width' => $params['width'],
			'altitude' => $params['altitude'],
			'rsrange' => $params['rsrange'],
			'license' => (strlen($params['license']) ? $params['license'] : null),
			'technology' => intval($params['technology']),
			'type' => intval($params['type']),
			'frequency' => (strlen($params['frequency']) ? $params['frequency'] : null),
			'frequency2' => (strlen($params['frequency2']) ? $params['frequency2'] : null),
			'bandwidth' => (strlen($params['bandwidth']) ? str_replace(',', '.', $params['bandwidth'] / 1000) : null),
			SYSLOG::RES_RADIOSECTOR => $rsid,
			'secret' => $params['secret'],
		);
		$DB->Execute('UPDATE netradiosectors SET name = ?, azimuth = ?, width = ?, altitude = ?,
			rsrange = ?, license = ?, technology = ?, type = ?, secret = ?,
			frequency = ?, frequency2 = ?, bandwidth = ? WHERE id = ?', array_values($args));
		if ($SYSLOG) {
			$args[SYSLOG::RES_NETDEV] = $netdevid;
			$SYSLOG->AddMessage(SYSLOG::RES_RADIOSECTOR, SYSLOG::OPER_UPDATE, $args);
		}
		$params = NULL;
	}

	$result->call('xajax_getRadioSectors', null);
	$result->call('get_radio_sectors_for_self_netdev', null);
	$result->call('change_nodelinktechnology', null);

	return $result;
}

function getRadioSectorsForNetdev($callback_name, $devid, $technology = 0) {
	global $DB;

	$result = new xajaxResponse();

	if (!in_array($callback_name, array('radio_sectors_received_for_srcnetdev', 'radio_sectors_received_for_dstnetdev',
		'radio_sectors_received_for_node')))
		return $result;

	$technology = intval($technology);
	$radiosectors = $DB->GetAll('SELECT id, name FROM netradiosectors WHERE netdev = ?'
		. ($technology ? ' AND (technology = ' . $technology . ' OR technology = 0)' : '')
		. ' ORDER BY name', array(intval($devid)));
	$result->call($callback_name, $radiosectors);

	return $result;
}

function getFirstFreeAddress($netid, $elemid) {
	global $LMS;

	$DB = LMSDB::getInstance();

	$result = new xajaxResponse();

	$reservedaddresses = intval(ConfigHelper::getConfig('phpui.first_reserved_addresses', 0, true));
	$net = $LMS->GetNetworkRecord($netid);
	$ip = '';

	foreach ($net['nodes']['id'] as $idx => $nodeid) {
		if ($idx < $reservedaddresses)
			continue;
		if ($nodeid) {
			$firstnodeid = $idx;
			$ip = '';
		}
		if (!$nodeid && !isset($net['nodes']['name'][$idx]) && empty($ip)) {
			$ip = $net['nodes']['address'][$idx];
			if (isset($firstnodeid))
				break;
		}
	}
	if (!empty($ip))
		$result->assign($elemid, 'value', $ip);

	return $result;
}

$LMS->RegisterXajaxFunction(array(
	'getManagementUrls','addManagementUrl', 'delManagementUrl', 'updateManagementUrl',
	'getRadioSectors', 'addRadioSector', 'delRadioSector', 'updateRadioSector',
	'getRadioSectorsForNetdev', 'getFirstFreeAddress'
));

?>
