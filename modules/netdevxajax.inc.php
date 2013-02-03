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
 *  $Id: netdevxajax.inc.php,v 1.1 2012/04/11 23:12:01 chilek Exp $
 */

function getManagementUrls($netdevid) {
	global $SMARTY, $DB;

	$result = new xajaxResponse();
	$mgmurls = NULL;
	$mgmurls = $DB->GetAll('SELECT id, url, comment FROM managementurls WHERE netdevid = ? ORDER BY id', array($netdevid));
	$SMARTY->assign('mgmurls', $mgmurls);
	$mgmurllist = $SMARTY->fetch('managementurllist.html');

	$result->assign('managementurltable', 'innerHTML', $mgmurllist);

	return $result;
}

function addManagementUrl($netdevid, $params) {
	global $DB;

	$result = new xajaxResponse();

	if (empty($params['url']))
		return $result;

	if (!preg_match('/^[[:alnum:]]+:\/\/.+/i', $params['url']))
		$params['url'] = 'http://' . $params['url'];

	$DB->Execute('INSERT INTO managementurls (netdevid, url, comment) VALUES (?, ?, ?)', array($netdevid, $params['url'], $params['comment']));
	$result->call('xajax_getManagementUrls', $netdevid);
	$result->assign('managementurladdlink', 'disabled', false);

	return $result;
}

function delManagementUrl($netdevid, $id) {
	global $DB;

	$result = new xajaxResponse();
	$DB->Execute('DELETE FROM managementurls WHERE id = ?', array($id));
	$result->call('xajax_getManagementUrls', $netdevid);
	$result->assign('managementurltable', 'disabled', false);

	return $result;
}

$LMS->InitXajax();
$LMS->RegisterXajaxFunction(array('getManagementUrls', 'addManagementUrl', 'delManagementUrl'));
$SMARTY->assign('xajax', $LMS->RunXajax());
?>
