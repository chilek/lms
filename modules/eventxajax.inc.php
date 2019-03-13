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

function getUsersForGroup($groupid) {
	$DB = LMSDB::getInstance();

	$JSResponse = new xajaxResponse();

	if (empty($groupid))
		$users = null;
	elseif (intval($groupid) == -1)
		$users = array(Auth::GetCurrentUser());
	else
		$users = $DB->GetCol('SELECT u.id FROM users u
			JOIN userassignments ua ON ua.userid = u.id
			WHERE u.deleted = 0 AND u.access = 1 AND ua.usergroupid = ?',
			array($groupid));

	$JSResponse->call('update_user_selection', $users);

	return $JSResponse;
}

$LMS->RegisterXajaxFunction(array('getUsersForGroup'));

?>
