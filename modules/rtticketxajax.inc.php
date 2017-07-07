<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2017 LMS Developers
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

function GetCategories($queueid) {
	global $LMS;

	$DB = LMSDB::getInstance();
	$result = new xajaxResponse();

	if (empty($queueid))
		return $result;

	$categories = $LMS->GetCategoryListByUser(Auth::GetCurrentUser());
	if (empty($categories))
		return $result;

	$queuecategories = $LMS->GetQueueCategories($queueid);

	foreach ($categories as $category)
		$result->assign('cat' . $category['id'], 'checked', isset($queuecategories[$category['id']]));

	return $result;
}

function select_customer($id) {
	global $LMS;

	$JSResponse = new xajaxResponse();
	if (!empty($id)) {
		$locations = $LMS->getCustomerAddresses($id);
		$JSResponse->call('update_locations', (array) $locations);
	}
	return $JSResponse;
}

$LMS->InitXajax();
$LMS->RegisterXajaxFunction(array('GetCategories', 'select_customer'));
$SMARTY->assign('xajax', $LMS->RunXajax());

?>
