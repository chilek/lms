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

include(MODULES_DIR . DIRECTORY_SEPARATOR . 'managementurls.inc.php');

function getManagementUrls() {
	$result = new xajaxResponse();

	_getManagementUrls(LMSNetDevManager::NETDEV_URL, $result);

	return $result;
}

function addManagementUrl($params) {
	return _addManagementUrl(LMSNetDevManager::NETDEV_URL, $params);
}

function delManagementUrl($id) {
	return _delManagementUrl(LMSNetDevManager::NETDEV_URL, $id);
}

function updateManagementUrl($id, $params) {
	return _updateManagementUrl(LMSNetDevManager::NETDEV_URL, $id, $params);
}

function validateRadioSector($params) {
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

function _getRadioSectors($result) {
	$lms = LMS::getInstance();
	$radiosectors = $lms->GetRadioSectors($_GET['id']);

	$smarty = LMSSmarty::getInstance();
	$smarty->assign('radiosectors', $radiosectors);
	$radiosectorlist = $smarty->fetch('netdev/radiosectorlist.html');

	$result->assign('radiosectortable', 'innerHTML', $radiosectorlist);
}

function getRadioSectors() {
	$result = new xajaxResponse();

	_getRadioSectors($result);

	return $result;
}

function addRadioSector($params) {
	$result = new xajaxResponse();

	$formdata = array();
	parse_str($params, $formdata);

	$error = validateRadioSector($formdata);

	if (!$error) {
		$lms = LMS::getInstance();
		$lms->AddRadioSector($_GET['id'], $formdata);

		$result->call('hideAddRadioSector');

		_getRadioSectors($result);
	} else
		$result->call('radioSectorErrors', $error);

	return $result;
}

function delRadioSector($id) {
	$result = new xajaxResponse();

	$lms = LMS::getInstance();
	$lms->DeleteRadioSector($id);

	_getRadioSectors($result);

	$result->call('get_radio_sectors_for_self_netdev', null);
	$result->call('change_nodelinktechnology', null);

	return $result;
}

function updateRadioSector($rsid, $params) {
	$result = new xajaxResponse();

	$rsid = intval($rsid);

	$res = validateRadioSector($params);
	$error = array();
	foreach ($res as $key => $val)
		$error[$key . '_edit_' . $rsid] = $val;

	if (!$error) {
		$lms = LMS::getInstance();
		$lms->UpdateRadioSector($rsid, $params);

		_getRadioSectors($result);
	} else
		$result->call('radioSectorErrors', $error);

	$result->call('get_radio_sectors_for_self_netdev', null);
	$result->call('change_nodelinktechnology', null);

	return $result;
}

function getRadioSectorsForNetdev($callback_name, $devid, $technology = 0) {
	$result = new xajaxResponse();

	if (!in_array($callback_name, array('radio_sectors_received_for_srcnetdev', 'radio_sectors_received_for_dstnetdev',
		'radio_sectors_received_for_node')))
		return $result;

	$lms = LMS::getInstance();
	$radiosectors = $lms->GetRadioSectors($devid, $technology);
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
