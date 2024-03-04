<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2022 LMS Developers
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

$divisionContext = $SESSION->get('division_context', true);
if (!isset($divisionContext)) {
    $divisionContext = $SESSION->get_persistent_setting('division_context');
    $SESSION->save('division_context', $divisionContext, true);
}
$SMARTY->assign('division_context', $divisionContext);
$layout['division'] = $divisionContext;

$superuser = (ConfigHelper::checkPrivilege('superuser') ? 1 : 0);

if ($SESSION->is_set('uldiv', true) && !isset($_GET['division'])) {
    $SESSION->restore('uldiv', $_GET['division'], true);
} elseif ($SESSION->is_set('uldiv') && !isset($_GET['division'])) {
    $SESSION->restore('uldiv', $_GET['division']);
}

$selectedDivision = $_GET['division'] ?? $divisionContext;

$userslist = (empty($superuser) ? $LMS->GetUserList(array('divisions' => $selectedDivision)) : $LMS->GetUserList(array('divisions' => $selectedDivision, 'superuser' => 1)));
unset($userslist['total']);

$SESSION->save('uldiv', $selectedDivision);
$SESSION->save('uldiv', $selectedDivision, true);

$SESSION->add_history_entry();

$SMARTY->assign('userslist', $userslist);
$SMARTY->assign('selectedDivision', $selectedDivision);
$SMARTY->assign('superuser', $superuser);
$SMARTY->display('user/userlist.html');
