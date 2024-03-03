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

$customerid = intval($_GET['id']);

if (isset($_GET['action'])) {
    header('Content-type: application/json');

    switch ($_GET['action']) {
        case 'get_sensible_data':
            $sensible_data = $LMS->getCustomerSensibleData($customerid);
            if (empty($sensible_data)) {
                $sensible_data = array();
            }

            if ($SYSLOG) {
                $args = array(
                    SYSLOG::RES_USER => Auth::GetCurrentUser(),
                    SYSLOG::RES_CUST => $customerid,
                );
                foreach ($sensible_data as $key => $value) {
                    $args[$key] = $value;
                }

                $SYSLOG->addMessage(SYSLOG::RES_CUST, SYSLOG::OPER_GET, $args);
            }

            die(json_encode($sensible_data));
    }

    die('[]');
}

$LMS->InitXajax();

if (!isset($_POST['xjxfun'])) {
    $visible_panels = $SESSION->get_persistent_setting('customerinfo-visible-panels');
    $SMARTY->assign('visible_panels', $visible_panels);

    include(MODULES_DIR.'/customer.inc.php');
    require_once(LIB_DIR . DIRECTORY_SEPARATOR . 'customercontacttypes.php');

    //if($customerinfo['cutoffstop'] > mktime(0,0,0))
    //        $customerinfo['cutoffstopnum'] = floor(($customerinfo['cutoffstop'] - mktime(23,59,59))/86400);

    $SESSION->add_history_entry();

    $layout['pagetitle'] = trans('Customer Info: $a', $customerinfo['customername']);
} else {
    $customerinfo = array(
        'id' => $customerid,
    );
}

$hook_data = $LMS->executeHook(
    'customerinfo_before_display',
    array(
        'customerinfo' => $customerinfo,
        'smarty' => $SMARTY,
    )
);
$customerinfo = $hook_data['customerinfo'];

$SMARTY->assign('xajax', $LMS->RunXajax());
$SMARTY->assign('customerinfo_sortable_order', $SESSION->get_persistent_setting('customerinfo-sortable-order'));
$SMARTY->display('customer/customerinfo.html');
