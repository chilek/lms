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

$source = $DB->GetRow('SELECT * FROM cashsources WHERE id = ?', array($_GET['id']));

if (!$source) {
    $SESSION->redirect('?m=cashsourcelist');
}

$layout['pagetitle'] = trans('Cash Import Source Edit: $a', $source['name']);

if (isset($_POST['sourceedit'])) {
    $sourceedit = $_POST['sourceedit'];
    $sourceedit['name'] = trim($sourceedit['name']);
    $sourceedit['description'] = trim($sourceedit['description']);

    if ($sourceedit['name'] == '') {
        $error['name'] = trans('Source name is required!');
    } elseif (mb_strlen($sourceedit['name'])>32) {
        $error['name'] = trans('Source name is too long!');
    } elseif ($source['name'] != $sourceedit['name']) {
        if ($DB->GetOne('SELECT 1 FROM cashsources WHERE name = ?', array($sourceedit['name']))) {
            $error['name'] = trans('Source with specified name exists!');
        }
    }

    if ($sourceedit['account'] != '' && (strlen($sourceedit['account'])>48 || !preg_match('/^([A-Z][A-Z])?[0-9]+$/', $sourceedit['account']))) {
        $error['account'] = trans('Wrong account number!');
    }

    if (isset($sourceedit['isdefault']) && $DB->GetOne('SELECT id FROM cashsources WHERE isdefault = ? AND id <> ?', array(1, $_GET['id'])) > 0) {
        $error['isdefault'] = trans('Only one cash import source can be set as default!');
    }

    if (!$error) {
        $args = array(
            'name' => $sourceedit['name'],
            'description' => $sourceedit['description'],
            'account' => $sourceedit['account'],
            'isdefault' => isset($sourceedit['isdefault']) ? 1 : 0,
            SYSLOG::RES_CASHSOURCE => $_GET['id']
        );
        $DB->Execute('UPDATE cashsources SET name = ?, description = ?, account = ?, isdefault = ? WHERE id=?', array_values($args));

        if ($SYSLOG) {
            $SYSLOG->AddMessage(SYSLOG::RES_CASHSOURCE, SYSLOG::OPER_UPDATE, $args);
        }

        $SESSION->redirect('?m=cashsourcelist');
    }

    $source['name'] = $sourceedit['name'];
    $source['description'] = $sourceedit['description'];
    $source['account'] = $sourceedit['account'];
    $source['isdefault'] = isset($sourceedit['isdefault']);
}

$SESSION->add_history_entry();

$SMARTY->assign('sourceedit', $source);
$SMARTY->assign('error', $error);
$SMARTY->display('cash/cashsourceedit.html');
