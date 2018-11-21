<?php

/**
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
	elseif (strlen($params['url']) < 7)
		$error['url'] = trans('Management URL is too short!');

	return $error;
}

function _getManagementUrls($type, $result) {
	$lms = LMS::getInstance();
	$mgmurls = $lms->GetManagementUrls($type, $_GET['id']);

	$smarty = LMSSmarty::getInstance();
	$smarty->assign('mgmurls', $mgmurls);
	$mgmurllist = $smarty->fetch('managementurl/managementurllist.html');

	$result->assign('managementurltable', 'innerHTML', $mgmurllist);
}

function _addManagementUrl($type, $params) {
	$result = new xajaxResponse();

	$formdata = array();
	parse_str($params, $formdata);

	$error = validateManagementUrl($formdata);

	if (!$error) {
		if (!preg_match('/^[[:alnum:]]+:\/\/.+/i', $formdata['url']))
			$formdata['url'] = 'http://' . $formdata['url'];

		$lms = LMS::getInstance();
		$lms->AddManagementUrl($type, $_GET['id'], $formdata);

		$result->call('hideAddManagementUrl');

		_getManagementUrls($type, $result);
	} else
		$result->call('managementUrlErrors', $error);

	return $result;
}

function _delManagementUrl($type, $id) {
	$result = new xajaxResponse();

	$lms = LMS::getInstance();
	$lms->DeleteManagementUrl($type, $id);

	_getManagementUrls($type, $result);

	return $result;
}

function _updateManagementUrl($type, $id, $params) {
	$result = new xajaxResponse();

	$res = validateManagementUrl($params);

	$error = array();
	foreach ($res as $key => $val)
		$error[$key . '_edit_' . $id] = $val;

	if (!$error) {
		if (!preg_match('/^[[:alnum:]]+:\/\/.+/i', $params['url']))
			$params['url'] = 'http://' . $params['url'];

		$lms = LMS::getInstance();
		$lms->UpdateManagementUrl($type, $id, $params);

		_getManagementUrls($type, $result);
	} else
		$result->call('managementUrlErrors', $error);

	return $result;
}
