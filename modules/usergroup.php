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

$action = $_GET['action'] ?? '';

if ($action == 'delete') {
    $LMS->UserAssignmentDelete(array('userid' => intval($_GET['id']),'usergroupid' => $_GET['usergroupid']));
} elseif ($action == 'add') {
    $groupid = intval($_POST['usergroupid']);
    $uid = intval($_GET['id']);
    if ($LMS->UserGroupExists($groupid) && !$LMS->UserassignmentExist($groupid, $uid) && $LMS->UserExists($uid)) {
        $LMS->UserAssignmentAdd(array('userid' => $uid, 'usergroupid' => $groupid));
    }
} elseif (!empty($_POST['userassignments']) && $LMS->UserGroupExists($_GET['id'])) {
    $oper = $_POST['oper'];
    $userassignments = $_POST['userassignments'];

    if (isset($userassignments['gmuserid']) && $oper=='0') {
        $assignment['usergroupid'] = $_GET['id'];
        foreach ($userassignments['gmuserid'] as $value) {
            $assignment['userid'] = $value;
            $LMS->UserassignmentDelete($assignment);
        }
    } elseif (isset($userassignments['muserid']) && $oper=='1') {
        $assignment['usergroupid'] = $_GET['id'];
        foreach ($userassignments['muserid'] as $value) {
            $assignment['userid'] = $value;
            if (! $LMS->UserassignmentExist($assignment['usergroupid'], $value)) {
                $LMS->UserassignmentAdd($assignment);
            }
        }
    }
}

$SESSION->redirect_to_history_entry();
