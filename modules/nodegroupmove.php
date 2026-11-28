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

$from = !empty($_GET['from']) ? intval($_GET['from']) : 0;
$to = !empty($_GET['to']) ? intval($_GET['to']) : 0;

if ($from == $to || ($_GET['is_sure'] ?? 0) != 1 || !$from || !$to) {
    $SESSION->redirect_to_history_entry();
}

$DB->BeginTrans();
$groups = $DB->GetCol('SELECT id FROM nodegroups WHERE id IN ? ORDER BY id FOR UPDATE', array(array($from, $to)));
if (count($groups ?: array()) != 2) {
    $DB->RollbackTrans();
    $SESSION->redirect_to_history_entry();
}

$assignments = $DB->GetAll(
    'SELECT DISTINCT a.id, a.nodeid, dest.id AS destinationid
    FROM nodegroupassignments a
    JOIN nodes n ON n.id = a.nodeid
    LEFT JOIN customerview c ON c.id = n.ownerid
    LEFT JOIN nodegroupassignments dest ON dest.nodegroupid = ? AND dest.nodeid = a.nodeid
    WHERE a.nodegroupid = ? AND (n.ownerid IS NULL OR c.id IS NOT NULL)',
    array($to, $from)
);
if ($assignments) {
    $newMembers = array();
    foreach ($assignments as $assignment) {
        if (!$assignment['destinationid']) {
            $newMembers[] = $assignment['nodeid'];
        }
    }

    if ($newMembers && $DB->Execute(
        'INSERT INTO nodegroupassignments (nodegroupid, nodeid)
        SELECT ?, nodeid FROM nodegroupassignments WHERE nodegroupid = ? AND nodeid IN ?',
        array($to, $from, $newMembers)
    ) != count($newMembers)) {
        $DB->RollbackTrans();
        $SESSION->redirect_to_history_entry();
    }

    if ($DB->Execute(
        'DELETE FROM nodegroupassignments WHERE id IN ?',
        array(array_column($assignments, 'id'))
    ) != count($assignments)) {
        $DB->RollbackTrans();
        $SESSION->redirect_to_history_entry();
    }

    if ($SYSLOG) {
        if ($newMembers) {
            $added = $DB->GetAll(
                'SELECT id, nodeid FROM nodegroupassignments WHERE nodegroupid = ? AND nodeid IN ?',
                array($to, $newMembers)
            );
            foreach ($added as $assignment) {
                $SYSLOG->AddMessage(SYSLOG::RES_NODEGROUPASSIGN, SYSLOG::OPER_ADD, array(
                    SYSLOG::RES_NODEGROUPASSIGN => $assignment['id'],
                    SYSLOG::RES_NODE => $assignment['nodeid'],
                    SYSLOG::RES_NODEGROUP => $to,
                ));
            }
        }
        foreach ($assignments as $assignment) {
            $SYSLOG->AddMessage(SYSLOG::RES_NODEGROUPASSIGN, SYSLOG::OPER_DELETE, array(
                SYSLOG::RES_NODEGROUPASSIGN => $assignment['id'],
                SYSLOG::RES_NODE => $assignment['nodeid'],
                SYSLOG::RES_NODEGROUP => $from,
            ));
        }
    }
}
$DB->CommitTrans();
$SESSION->redirect('?m=nodegroupinfo&id=' . $to);
