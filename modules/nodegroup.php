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
    $nodegroupids = isset($_GET['nodegroupid']) ? array($_GET['nodegroupid']) : ($_POST['markednodegroupid'] ?? array());
    if ($LMS->NodeExists($_GET['id'])) {
        foreach (Utils::filterIntegers((array) $nodegroupids) as $nodegroupid) {
            $LMS->deleteNodeGroupAssignment(array(
                'nodeid' => $_GET['id'],
                'nodegroupid' => $nodegroupid,
            ));
        }
    }
} elseif ($action == 'add') {
    $nodegroupids = Utils::filterIntegers((array) ($_POST['nodegroupid'] ?? array()));
    if ($nodegroupids && $LMS->NodeExists($_GET['id'])) {
        $groups = $DB->GetCol('SELECT id FROM nodegroups WHERE id IN ?', array($nodegroupids));
        foreach ($groups ?: array() as $nodegroupid) {
            $LMS->addNodeGroupAssignment(array(
                'nodeid' => $_GET['id'],
                'nodegroupid' => $nodegroupid,
            ));
        }
    }
} elseif (!empty($_POST['marks']) && $DB->GetOne('SELECT id FROM nodegroups WHERE id = ?', array(intval($_GET['groupid'])))) {
    foreach ($_POST['marks'] as $mark) {
        if (!$LMS->NodeExists($mark)) {
            continue;
        }
        if ($action == 'unsetgroup') {
            $LMS->deleteNodeGroupAssignment(array(
                'nodeid' => $mark,
                'nodegroupid' => intval($_GET['groupid']),
            ));
        } elseif ($action == 'setgroup') {
            $LMS->addNodeGroupAssignment(array(
                'nodeid' => $mark,
                'nodegroupid' => intval($_GET['groupid']),
            ));
        }
    }
} elseif (isset($_POST['nodeassignments']) && $DB->GetOne('SELECT id FROM nodegroups WHERE id = ?', array($_GET['id']))) {
    $oper = $_POST['oper'];
    $nodeassignments = $_POST['nodeassignments'];

    if (isset($nodeassignments['gmnodeid']) && $oper=='0') {
        foreach ($nodeassignments['gmnodeid'] as $nodeid) {
            if ($LMS->NodeExists($nodeid)) {
                $LMS->deleteNodeGroupAssignment(array('nodegroupid' => $_GET['id'], 'nodeid' => $nodeid));
            }
        }
    } elseif (isset($nodeassignments['mnodeid']) && $oper=='1') {
        foreach ($nodeassignments['mnodeid'] as $nodeid) {
            if ($LMS->NodeExists($nodeid)) {
                $LMS->addNodeGroupAssignment(array('nodegroupid' => $_GET['id'], 'nodeid' => $nodeid));
            }
        }
    } elseif (isset($nodeassignments['membersnetid']) && $oper=='2') {
        $SESSION->redirect(
            '?' . preg_replace('/&membersnetid=[0-9]+/', '', $SESSION->remove_history_entry())
            . '&membersnetid=' . $nodeassignments['membersnetid']
        );
    } elseif (isset($nodeassignments['othersnetid']) && $oper=='3') {
        $SESSION->redirect(
            '?' . preg_replace('/&othersnetid=[0-9]+/', '', $SESSION->remove_history_entry())
            . '&othersnetid=' . $nodeassignments['othersnetid']
        );
    }
}

$SESSION->redirect_to_history_entry();
