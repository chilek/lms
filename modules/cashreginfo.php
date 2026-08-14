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

$registry = $DB->GetRow(
    'SELECT
        reg.id AS id,
        reg.name AS name,
        reg.description AS description,
        i.template AS in_template,
        o.template AS out_template,
        disabled
    FROM cashregs reg
    LEFT JOIN numberplans i ON in_numberplanid = i.id
    LEFT JOIN numberplans o ON out_numberplanid = o.id
    WHERE reg.id = ?',
    array($id)
);

if (!$registry) {
    $SESSION->redirect('?m=cashreglist');
}

$registry['rights'] = $DB->GetAllByKey(
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

$layout['pagetitle'] = trans('Cash Registry Info: $a', $registry['name']);

$SESSION->add_history_entry();

$SMARTY->assign('registry', $registry);
$SMARTY->display('cash/cashreginfo.html');
