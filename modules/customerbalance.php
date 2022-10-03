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

$SESSION->add_history_entry();

if (!$LMS->CustomerExists($_GET['id'])) {
    header('Location: ?m=customerlist');
}

$customername = $LMS->GetCustomerName($_GET['id']);
$id = $_GET['id'];

$layout['pagetitle'] = trans('Customer Balance: $a', '<A HREF="?m=customerinfo&id='.$_GET['id'].'">'.$customername.'</A>');

if (isset($_GET['aggregate_documents'])) {
    $aggregate_documents = !empty($_GET['aggregate_documents']);
} else {
    $aggregate_documents = ConfigHelper::checkConfig('phpui.aggregate_documents');
}

$SMARTY->assign('aggregate_documents', $aggregate_documents);
$SMARTY->assign('balancelist', $LMS->GetCustomerBalanceList($_GET['id'], null, false, $aggregate_documents));
$SMARTY->assign('taxeslist', $LMS->GetTaxes());
$SMARTY->assign('customername', $customername);
$SMARTY->assign('objectid', $id);
$SMARTY->display('customer/customerbalance.html');
