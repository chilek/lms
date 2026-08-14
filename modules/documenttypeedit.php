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

//$type = $DB->GetRow('SELECT * FROM types WHERE id=?', array($_GET['id']));

$id = intval($_GET['id']);

if (!$id || empty($DOCTYPES[$id])) {
    $SESSION->redirect('?m=documenttypes');
}

if (isset($_POST['rights'])) {
    $rights = $_POST['rights'];

    if (!$error) {
        $DB->BeginTrans();

        $DB->Execute('DELETE FROM docrights WHERE doctype = ?', array($id));

        foreach ($rights as $idx => $user) {
            $DB->Execute(
                'INSERT INTO docrights (userid, doctype, rights)
				VALUES (?, ?, ?)',
                array(
                    $idx,
                    $id,
                    array_sum($user)
                )
            );
        }

        $DB->CommitTrans();

        $SESSION->redirect('?m=documenttypes');
    } else {
        $users = $DB->GetAllByKey(
            'SELECT
                id,
                name,
                rname,
                login,
                (CASE WHEN access = 1 AND accessfrom <= ?NOW? AND (accessto >= ?NOW? OR accessto = 0) THEN 1 ELSE 0 END) AS access
            FROM vusers
            WHERE deleted = 0
            ORDER BY rname',
            'id'
        );

        foreach ($users as $idx => $user) {
            if (!empty($rights[$idx])) {
                $rights[$idx]['rights'] = array_sum($rights[$idx]);
            }
            $rights[$idx]['name'] = $user['name'];
            $rights[$idx]['access'] = $user['access'];
        }
    }
} else {
    $rights = $DB->GetAllByKey(
        'SELECT
            u.id,
            u.name,
            u.rname,
            u.login,
            d.rights,
            (CASE WHEN u.access = 1 AND u.accessfrom <= ?NOW? AND (u.accessto >= ?NOW? OR u.accessto = 0) THEN 1 ELSE 0 END) AS access
        FROM vusers u
        LEFT JOIN docrights d ON u.id = d.userid AND d.doctype = ?
        WHERE u.deleted = 0
        ORDER BY u.rname',
        'id',
        array($id)
    );
}

$type = array(
    'name' => $DOCTYPES[$id],
    'rights' => $rights,
    'id' => $id
);

$layout['pagetitle'] = trans('Document Type Edit: $a', $type['name']);

$SESSION->add_history_entry();

$SMARTY->assign('documenttype', $type);
$SMARTY->assign('error', $error);
$SMARTY->display('document/documenttypeedit.html');
