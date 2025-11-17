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

if (!$LMS->VoipAccountExists($_GET['id'])) {
    if (isset($_GET['ownerid'])) {
        header('Location: ?m=customerinfo&id='.$_GET['ownerid']);
    } else {
        header('Location: ?m=voipaccountlist');
    }
}

$voipaccountid = intval($_GET['id']);
$voipaccountinfo = $LMS->GetVoipAccount($voipaccountid);
$customerid = $voipaccountinfo['ownerid'];

if (!isset($_GET['ownerid'])) {
    $SESSION->add_history_entry($SESSION->remove_history_entry() . '&ownerid=' . $customerid);
} else {
    $SESSION->add_history_entry();
}

$layout['pagetitle'] = trans('VoIP Account Edit: $a', $voipaccountinfo['login']);

if (isset($_POST['voipaccountedit'])) {
    $voipaccountedit = $_POST['voipaccountedit'];
    //$voipaccountedit['address_id'] = $voipaccountinfo['address_id'];

    foreach ($voipaccountedit as $key => $value) {
        if (!is_array($value)) {
            $voipaccountedit[$key] = trim($value);
        }
    }

    if (!isset($voipaccountedit['login']) || $voipaccountedit['login'] == '') {
        $error['login'] = trans('VoIP account login is required!');
    } else {
        $loginids = $LMS->GetVoipAccountIDByLogin($voipaccountedit['login']);

        $foundid = 0;
        if (isset($loginids)) {
            foreach ($loginids as $loginid) {
                $foundid = ($loginid['id'] == $voipaccountedit['id']);
                if ($foundid) {
                    break;
                } else {
                    $error['login'] = trans('Specified login is in use!');
                }
            }
        }
    }

    if (!$error['login']) {
        if (!preg_match('/' . ConfigHelper::getConfig('voip.account_login_regexp', '^[_a-z0-9-]+$') . '/i', $voipaccountedit['login'])) {
            $error['login'] = trans('Specified login contains forbidden characters!');
        } elseif (strlen($voipaccountedit['login']) > 32) {
            $error['login'] = trans('VoIP account login is too long (max.32 characters)!');
        }
    }

    $password_max_length = intval(ConfigHelper::getConfig('voip.account_password_max_length', 32));
    if ($voipaccountedit['passwd']=='') {
        $error['passwd'] = trans('VoIP account password is required!');
    } elseif (strlen($voipaccountedit['passwd']) > $password_max_length) {
        $error['passwd'] = trans('VoIP account password is too long (max. $a characters)!', $password_max_length);
    } elseif (!preg_match('/' . ConfigHelper::getConfig('voip.account_password_regexp', '^[_a-z0-9-@%]+$') . '/i', $voipaccountedit['passwd'])) {
        $error['passwd'] = trans('Specified password contains forbidden characters!');
    }

    foreach ($voipaccountedit['numbers'] as $k => $number) {
        if (!strlen($number['phone'])) {
            $error['phone-number-' . $k] = trans('VoIP account phone number is required!');
        } elseif (strlen($number['phone']) > 32) {
            $error['phone-number-' . $k] = trans('VoIP account phone number is too long (max.32 characters)!');
        } elseif (($accountid = $LMS->GetVoipAccountIDByPhone($number['phone'])) > 0 && $accountid != $voipaccountedit['id']) {
            $error['phone-number-' . $k] = trans('Specified phone is in use!');
        } elseif (!preg_match('/^C?[0-9]+$/', $number['phone'])) {
            $error['phone-number-' . $k] = trans('Specified phone number contains forbidden characters!');
        }
    }

    if (!$voipaccountedit['ownerid']) {
        $error['voipaccountedit[customerid]'] = trans('Customer not selected!');
        $error['voipaccountedit[ownerid]'] = trans('Customer not selected!');
    } elseif (!$LMS->CustomerExists($voipaccountedit['ownerid'])) {
        $error['voipaccountedit[customerid]'] = trans('Inexistent owner selected!');
        $error['voipaccountedit[ownerid]'] = trans('Inexistent owner selected!');
    } else {
        $status = $LMS->GetCustomerStatus($voipaccountedit['ownerid']);
        if ($status == CSTATUS_INTERESTED) { // unknown (interested)
            $error['voipaccountedit[customerid]'] = trans('Selected customer is not connected!');
            $error['voipaccountedit[ownerid]'] = trans('Selected customer is not connected!');
        } elseif ($status == CSTATUS_WAITING) { // awaiting
            $error['voipaccountedit[customerid]'] = trans('VoIP account owner is not connected!');
            $error['voipaccountedit[ownerid]'] = trans('Selected customer is not connected!');
        }
    }

    $flags = 0;
    if (isset($voipaccountedit[VOIP_ACCOUNT_FLAG_ADMIN_RECORDING])) {
        $flags |= VOIP_ACCOUNT_FLAG_ADMIN_RECORDING;
    }

    if (isset($voipaccountedit[VOIP_ACCOUNT_FLAG_CUSTOMER_RECORDING])) {
        $flags |= VOIP_ACCOUNT_FLAG_CUSTOMER_RECORDING;
    }

    if (isset($voipaccountedit[VOIP_ACCOUNT_FLAG_TRUNK])) {
        $flags |= VOIP_ACCOUNT_FLAG_TRUNK;
    }

    if (ConfigHelper::checkPrivilege('superuser')) {
        $voipaccountinfo['balance'] = isset($voipaccountedit['balance'])
            && is_numeric($voipaccountedit['cost_limit']) ? $voipaccountedit['balance'] : 0;
        $voipaccountinfo['cost_limit'] = isset($voipaccountedit['cost_limit'])
            && is_numeric($voipaccountedit['cost_limit']) ? $voipaccountedit['cost_limit'] : null;
    } else {
        $voipaccountedit['balance'] = $voipaccountinfo['balance'];
        $voipaccountedit['cost_limit'] = $voipaccountinfo['cost_limit'];
    }

    $voipaccountinfo['numbers'] = $voipaccountedit['numbers'];
    $voipaccountinfo['flags']   = $voipaccountedit['flags'] = $flags;
    $voipaccountinfo['login']   = $voipaccountedit['login'] ?? null;
    $voipaccountinfo['passwd']  = $voipaccountedit['passwd'];
    $voipaccountinfo['ownerid'] = $voipaccountedit['ownerid'];

    if (!isset($error['voipaccountedit[ownerid]'])) {
        // check if selected address belongs to customer
        if ($voipaccountedit['address_id'] != -1 && !$LMS->checkCustomerAddress($voipaccountedit['address_id'], $voipaccountedit['ownerid'])) {
            $error['address_id'] = trans('Selected address was not assigned to customer.');
            $voipaccountedit['address_id'] = null;
        }
        // check if selected address is teryt address
        if (!isset($error['address_id']) && !ConfigHelper::checkPrivilege('full_access')
            && ConfigHelper::checkConfig('phpui.teryt_required') && ($voipaccountedit['address_id'] == -1
                || !$LMS->isTerritAddress($voipaccountedit['address_id']))) {
            $error['address_id'] = trans('TERYT address is required!');
        }
    }

    $hook_data = $plugin_manager->executeHook(
        'voipaccountedit_before_submit',
        array(
            'voipaccountedit' => $voipaccountedit,
            'error' => $error
        )
    );

    $voipaccountedit = $hook_data['voipaccountedit'];
    $error = $hook_data['error'];

    if (!$error) {
        $LMS->VoipAccountUpdate($voipaccountedit);
        $SESSION->redirect('?m=voipaccountinfo&id='.$voipaccountedit['id']);
        die;
    }
}

$SMARTY->assign('customers', ConfigHelper::checkConfig('phpui.big_networks') ? array() : $LMS->GetCustomerNames());

if (!empty($voipaccountinfo['ownerid']) && $LMS->CustomerExists($voipaccountinfo['ownerid']) && ($customerid = $voipaccountinfo['ownerid'])) {
    include(MODULES_DIR . '/customer.inc.php');
}

$hook_data = $plugin_manager->executeHook(
    'voipaccountedit_before_display',
    array(
        'voipaccountinfo' => $voipaccountinfo,
        'smarty' => $SMARTY,
    )
);

$voipaccountinfo = $hook_data['voipaccountinfo'];

$SMARTY->assign('pool_list', $DB->GetAll("SELECT id,name FROM voip_pool_numbers;"));
$SMARTY->assign('customervoipaccounts', $customervoipaccounts);
$SMARTY->assign('error', $error);
$SMARTY->assign('voipaccountinfo', $voipaccountinfo);
$SMARTY->assign('customer_addresses', empty($voipaccountinfo['ownerid']) ? array() : $LMS->getCustomerAddresses($voipaccountinfo['ownerid']));

$SMARTY->display('voipaccount/voipaccountedit.html');
