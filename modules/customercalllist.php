<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2021 LMS Developers
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

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

if (isset($_GET['c'])) {
    $cid = intval($_GET['c']);
    if (!$LMS->CustomerExists($cid)) {
        $SESSION->redirect('?m=customerlist');
    }

    $customername = $LMS->GetCustomerName($cid);
    $layout['pagetitle'] = trans('Customer Call List: $a', '<a href="?m=customerinfo&id=' . $cid . '">' . $customername . '</a>');

    $customercalls = $LMS->getCustomerCalls(array(
        'customerid' => $cid,
    ));
} elseif (isset($_GET['u'])) {
    $uid = intval($_GET['u']);
    if (!$LMS->userExists($uid)) {
        $SESSION->redirect('?m=userlist');
    }

    $username = $LMS->getUserName($uid);
    $layout['pagetitle'] = trans('User Call List: $a', '<a href="?m=userinfo&id=' . $uid . '">' . $username . '</a>');

    $customercalls = $LMS->getCustomerCalls(array(
        'userid' => $uid,
    ));
}

$SMARTY->assign('customercalls', $customercalls);
$SMARTY->assign('customername', $customername);
$SMARTY->assign('id', $id);
$SMARTY->display('customer/customercalllist.html');
