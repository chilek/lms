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

$id = intval($_GET['id']);

$current_registry = $DB->GetRow(
    'SELECT
        id,
        name,
        description,
        in_numberplanid,
        out_numberplanid,
        disabled
    FROM cashregs
    WHERE id = ?',
    array($id)
);

$current_registry['rights'] = $DB->GetAllByKey(
    'SELECT
        u.id,
        u.name,
        u.rname,
        u.login,
        u.deleted,
        (CASE WHEN u.access = 1 AND u.accessfrom <= ?NOW? AND (u.accessto >= ?NOW? OR u.accessto = 0) THEN 1 ELSE 0 END) AS access,
        r.rights
    FROM vusers u
    LEFT JOIN cashrights r ON r.userid = u.id
    WHERE r.regid IS NULL OR r.regid = ?
    ORDER BY u.rname',
    'id',
    array($id)
);

if (isset($_POST['registry'])) {
    $registry = $_POST['registry'];
    $registry['id'] = $id;

    if ($registry['name']=='' && $registry['description']=='') {
        $SESSION->redirect('?m=cashreglist');
    }

    if ($registry['name'] == '') {
        $error['name'] = trans('Registry name must be defined!');
    }

    if ($registry['name'] != $DB->GetOne('SELECT name FROM cashregs WHERE id=?', array($id))) {
        if ($DB->GetOne('SELECT id FROM cashregs WHERE name=?', array($registry['name']))) {
            $error['name'] = trans('Registry with specified name already exists!');
        }
    }

    $current_registry['name'] = $registry['name'];
    $current_registry['description'] = $registry['description'];

    if (isset($registry['users'])) {
        foreach ($registry['users'] as $key => $value) {
            $registry['rights'][] = array('id' => $key, 'rights' => array_sum($value));
            $current_registry['rights'][$key]['rights'] = array_sum($value);
        }
    }

    if (!$error) {
        $DB->BeginTrans();
        $args = array(
            'name' => $registry['name'],
            'description' => $registry['description'],
            'in_' . SYSLOG::getResourceKey(SYSLOG::RES_NUMPLAN) => empty($registry['in_numberplanid']) ? null : $registry['in_numberplanid'],
            'out_' . SYSLOG::getResourceKey(SYSLOG::RES_NUMPLAN) => empty($registry['out_numberplanid']) ? null : $registry['out_numberplanid'],
            'disabled' => isset($registry['disabled']) ? 1 : 0,
            SYSLOG::RES_CASHREG => $registry['id'],
        );
        $DB->Execute(
            'UPDATE cashregs
            SET name = ?, description = ?, in_numberplanid = ?, out_numberplanid = ?, disabled = ?
            WHERE id = ?',
            array_values($args)
        );

        if ($SYSLOG) {
            $SYSLOG->AddMessage(
                SYSLOG::RES_CASHREG,
                SYSLOG::OPER_UPDATE,
                $args,
                array('in_' . SYSLOG::getResourceKey(SYSLOG::RES_NUMPLAN),
                    'out_' . SYSLOG::getResourceKey(SYSLOG::RES_NUMPLAN))
            );
            $cashrights = $DB->GetAll('SELECT id, userid FROM cashrights WHERE regid = ?', array($registry['id']));
            if (!empty($cashrights)) {
                foreach ($cashrights as $cashright) {
                    $args = array(
                    SYSLOG::RES_CASHRIGHT => $cashright['id'],
                    SYSLOG::RES_CASHREG => $registry['id'],
                    SYSLOG::RES_USER => $cashright['userid'],
                    );
                    $SYSLOG->AddMessage(SYSLOG::RES_CASHRIGHT, SYSLOG::OPER_DELETE, $args);
                }
            }
        }

        $DB->Execute('DELETE FROM cashrights WHERE regid = ?', array($registry['id']));

        if ($registry['rights']) {
            foreach ($registry['rights'] as $right) {
                if ($right['rights']) {
                    $args = array(
                        SYSLOG::RES_CASHREG => $id,
                        SYSLOG::RES_USER => $right['id'],
                        'rights' => $right['rights'],
                    );
                    $DB->Execute(
                        'INSERT INTO cashrights (regid, userid, rights) VALUES (?, ?, ?)',
                        array_values($args)
                    );
                    if ($SYSLOG) {
                        $args[SYSLOG::RES_CASHRIGHT] = $DB->GetLastInsertID('cashrights');
                        $SYSLOG->AddMessage(SYSLOG::RES_CASHRIGHT, SYSLOG::OPER_ADD, $args);
                    }
                }
            }
        }

        $DB->CommitTrans();
        $SESSION->redirect('?m=cashreginfo&id='.$id);
    }
}

$layout['pagetitle'] = trans('Edit Cash Registry: $a', $registry['name']);

$SESSION->add_history_entry();

$SMARTY->assign('registry', $current_registry);
$SMARTY->assign('numberplanlist', $LMS->GetNumberPlans(array(
    'doctype' => DOC_RECEIPT,
)));
$SMARTY->assign('error', $error);
$SMARTY->display('cash/cashregedit.html');
