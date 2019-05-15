<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2013 LMS Developers
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

$id = !empty($_GET['id']) ? $_GET['id'] : null;

if (!$id || !$LMS->CustomergroupExists($id)) {
    $SESSION->redirect('?m=customergrouplist');
}

if (isset($_GET['membersnetid']) && $membersnetid = $_GET['membersnetid']) {
    if (!$LMS->NetworkExists($membersnetid)) {
        $SESSION->redirect('?m=customergrouplist');
    }
}

if (isset($_GET['othersnetid']) && $othersnetid = $_GET['othersnetid']) {
    if (!$LMS->NetworkExists($othersnetid)) {
        $SESSION->redirect('?m=customergrouplist');
    }
}

$customergroup = $LMS->CustomergroupGet($id, isset($membersnetid) ? $membersnetid : 0);
$customers = $LMS->GetCustomerWithoutGroupNames($id, isset($othersnetid) ? $othersnetid : 0);
$customerscount = count($customers);

$layout['pagetitle'] = trans('Group Info: $a', $customergroup['name']);

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$SMARTY->assign('customergroup', $customergroup);
$SMARTY->assign('customers', $customers);
$SMARTY->assign('customerscount', $customerscount);
$SMARTY->assign('networks', $LMS->GetNetworks());
$SMARTY->assign('membersnetid', isset($membersnetid) ? $membersnetid : 0);
$SMARTY->assign('othersnetid', isset($othersnetid) ? $othersnetid : 0);
$SMARTY->display('customer/customergroupinfo.html');
