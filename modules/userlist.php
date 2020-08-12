<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2020 LMS Developers
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

$layout['pagetitle'] = trans('Users List');

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$divisionContext = $SESSION->get('division_context', true);
if (!isset($divisionContext)) {
    $divisionContext = $SESSION->get_persistent_setting('division_context');
    $SESSION->save('division_context', $divisionContext, true);
}
$SMARTY->assign('division_context', $divisionContext);
$layout['division'] = $divisionContext;

if (isset($_GET['division'])) {
    $filter['division'] = $_GET['division'];
} else {
    $filter['division'] = $divisionContext;
}

if (empty($filter['division'])) {
    $user_divisions = implode(",", array_keys($LMS->GetDivisions(array('userid' => Auth::GetCurrentUser()))));
} else {
    $user_divisions = $filter['division'];
}

$SMARTY->assign('userslist', $LMS->GetUserList(array('divisions' => $user_divisions)));
$SMARTY->display('user/userlist.html');
