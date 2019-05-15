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

if (empty($_GET['action'])) {
    $_GET['action'] = 'none';
}

switch ($_GET['action']) {
    case 'getpoolnumbers':
        $poolid = intval($_POST['poolid']);
        $pool = $DB->GetRow("SELECT poolstart, poolend FROM voip_pool_numbers WHERE id = ?", array($poolid));

        $range   = array();
        $range[$pool['poolstart']] = array($pool['poolstart'],'');
        $tmp     = $pool['poolstart'];

        while (gmp_cmp($tmp, $pool['poolend'])) {
            $tmp = gmp_strval(gmp_add($tmp, 1));
            $range[$tmp] = array($tmp,'');
        }

        $numbers = $DB->GetAll("SELECT phone FROM voip_numbers;");
        foreach ($numbers as $n) {
            if (isset($range[$n['phone']])) {
                $range[$n['phone']][1] = trans("used");
            }
        }

        $range = array_values($range);

        die(json_encode($range));
    break;
}

$voipaccountdata['access'] = 1;
$voipaccountdata['ownerid'] = null;

if (isset($_GET['ownerid'])) {
    if ($LMS->CustomerExists($_GET['ownerid']) == true) {
        $voipaccountdata['ownerid'] = $_GET['ownerid'];
        $customerinfo = $LMS->GetCustomer($_GET['ownerid']);
        $SMARTY->assign('customerinfo', $customerinfo);
    } else {
        $SESSION->redirect('?m=voipaccountinfo&id='.$_GET['ownerid']);
    }
}

if (isset($_GET['prelogin'])) {
    $voipaccountdata['login'] = $_GET['prelogin'];
}

if (isset($_GET['prepasswd'])) {
    $voipaccountdata['passwd'] = $_GET['prepasswd'];
}

if (isset($_GET['prephone'])) {
    $voipaccountdata['phone'] = $_GET['prephone'];
}

if (isset($_POST['voipaccountdata'])) {
    $voipaccountdata = $_POST['voipaccountdata'];
    $error = array();

    foreach ($voipaccountdata as $key => $value) {
        if (!is_array($value)) {
            $voipaccountdata[$key] = trim($value);
        }
    }

    if ($voipaccountdata['login']=='') {
        $error['login'] = trans('Voip account login is required!');
    } elseif (strlen($voipaccountdata['login']) > 32) {
        $error['login'] = trans('Voip account login is too long (max.32 characters)!');
    } elseif ($LMS->GetVoipAccountIDByLogin($voipaccountdata['login'])) {
        $error['login'] = trans('Specified login is in use!');
    } elseif (!preg_match('/^[_a-z0-9-]+$/i', $voipaccountdata['login'])) {
        $error['login'] = trans('Specified login contains forbidden characters!');
    }

    if ($voipaccountdata['passwd']=='') {
        $error['passwd'] = trans('Voip account password is required!');
    } elseif (strlen($voipaccountdata['passwd']) > 32) {
        $error['passwd'] = trans('Voip account password is too long (max.32 characters)!');
    } elseif (!preg_match('/^[_a-z0-9-@%]+$/i', $voipaccountdata['passwd'])) {
        $error['passwd'] = trans('Specified password contains forbidden characters!');
    }

    foreach ($voipaccountdata['phone'] as $k => $phone) {
        if (!strlen($phone)) {
            $error['phone'.$k] = trans('Voip account phone number is required!');
        } elseif (strlen($phone) > 32) {
            $error['phone'.$k] = trans('Voip account phone number is too long (max.32 characters)!');
        } elseif ($LMS->GetVoipAccountIDByPhone($phone)) {
            $error['phone'.$k] = trans('Specified phone is in use!');
        } elseif (!preg_match('/^C?[0-9]+$/', $phone)) {
            $error['phone'.$k] = trans('Specified phone number contains forbidden characters!');
        }
    }

    if (!isset($voipaccountdata['balance'])) {
        $voipaccountdata['balance'] = 0;
    } elseif ($voipaccountdata['balance'] < 0) {
        $error['balance'] = trans('Account balance must be positive!');
    }

    if ($voipaccountdata['cost_limit'] < 0) {
        $error['cost_limit'] = trans('Cost limit must be positive!');
    }

    if (!$voipaccountdata['ownerid']) {
        $error['voipaccountdata[customerid]'] = trans('Customer not selected!');
        $error['voipaccountdata[ownerid]'] = trans('Customer not selected!');
    } elseif (!$LMS->CustomerExists($voipaccountdata['ownerid'])) {
        $error['voipaccountdata[customerid]'] = trans('Inexistent owner selected!');
        $error['voipaccountdata[ownerid]'] = trans('Inexistent owner selected!');
    } else {
        $status = $LMS->GetCustomerStatus($voipaccountdata['ownerid']);
        if ($status == CSTATUS_INTERESTED) { // unknown (interested)
            $error['voipaccountdata[customerid]'] = trans('Selected customer is not connected!');
            $error['voipaccountdata[ownerid]'] = trans('Selected customer is not connected!');
        } elseif ($status == CSTATUS_WAITING) { // awaiting
            $error['voipaccountdata[customerid]'] = trans('Voip account owner is not connected!');
            $error['voipaccountdata[ownerid]'] = trans('Selected customer is not connected!');
        }
    }

    if (!isset($error['voipaccountdata[ownerid]'])) {
        // check if selected address belongs to customer
        if ($voipaccountdata['address_id'] != -1 && !$LMS->checkCustomerAddress($voipaccountdata['address_id'], $voipaccountdata['ownerid'])) {
            $error['address_id'] = trans('Selected address was not assigned to customer.');
            $voipaccountdata['address_id'] = null;
        }
    }

    $hook_data = $plugin_manager->executeHook(
        'voipaccountadd_before_submit',
        array('voipaccountdata'=>$voipaccountdata, 'error'=>$error)
    );

    $voipaccountdata = $hook_data['voipaccountdata'];
    $error = $hook_data['error'];

    if (!$error) {
        $voipaccountdata['flags'] = 0;
        if (isset($voipaccountdata['admin_record_flag'])) {
            $voipaccountdata['flags'] |= CALL_FLAG_ADMIN_RECORDING;
        }

        if (isset($voipaccountdata['customer_record_flag'])) {
            $voipaccountdata['flags'] |= CALL_FLAG_CUSTOMER_RECORDING;
        }

        $voipaccountid = $LMS->VoipAccountAdd($voipaccountdata);
        if ($voipaccountid) {
            $hook_data = $plugin_manager->executeHook(
                'voipaccountadd_after_submit',
                array('voipaccountdata'=>$voipaccountdata, 'error'=>$error, 'voipaccountid'=>$voipaccountid)
            );
        }

        if (!isset($voipaccountdata['reuse'])) {
            $SESSION->redirect('?m=voipaccountinfo&id='.$voipaccountid);
        }
        
        $ownerid = $voipaccountdata['ownerid'];
        unset($voipaccountdata);
        
        $voipaccountdata['ownerid'] = $ownerid;
        $voipaccountdata['reuse'] = '1';
    }
}

$layout['pagetitle'] = trans('New Voip Account');

if (!ConfigHelper::checkConfig('phpui.big_networks')) {
    $SMARTY->assign('customers', $LMS->GetCustomerNames());
}

if (!empty($voipaccountdata['ownerid']) && $LMS->CustomerExists($voipaccountdata['ownerid']) && ($customerid = $voipaccountdata['ownerid'])) {
    include(MODULES_DIR.'/customer.inc.php');
}

$hook_data = $plugin_manager->executeHook(
    'voipaccountadd_before_display',
    array(
        'voipaccountdata' => $voipaccountdata,
        'smarty' => $SMARTY,
    )
);

$voipaccountdata = $hook_data['voipaccountdata'];

$SMARTY->assign('pool_list', $DB->GetAll("SELECT id,name FROM voip_pool_numbers"));
$SMARTY->assign('customers', $customers);
$SMARTY->assign('error', $error);
$SMARTY->assign('voipaccountdata', $voipaccountdata);
$SMARTY->display('voipaccount/voipaccountadd.html');
