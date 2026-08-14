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
        $error['login'] = trans('VoIP account login is required!');
    } elseif (strlen($voipaccountdata['login']) > 32) {
        $error['login'] = trans('VoIP account login is too long (max.32 characters)!');
    } elseif ($LMS->GetVoipAccountIDByLogin($voipaccountdata['login'])) {
        $error['login'] = trans('Specified login is in use!');
    } elseif (!preg_match('/' . ConfigHelper::getConfig('voip.account_login_regexp', '^[_a-z0-9-]+$') . '/i', $voipaccountdata['login'])) {
        $error['login'] = trans('Specified login contains forbidden characters!');
    }

    $password_max_length = intval(ConfigHelper::getConfig('voip.account_password_max_length', 32));
    if ($voipaccountdata['passwd']=='') {
        $error['passwd'] = trans('VoIP account password is required!');
    } elseif (strlen($voipaccountdata['passwd']) > $password_max_length) {
        $error['passwd'] = trans('VoIP account password is too long (max. $a characters)!', $password_max_length);
    } elseif (!preg_match('/' . ConfigHelper::getConfig('voip.account_password_regexp', '^[_a-z0-9-@%]+$') . '/i', $voipaccountdata['passwd'])) {
        $error['passwd'] = trans('Specified password contains forbidden characters!');
    }

    foreach ($voipaccountdata['numbers'] as $k => $number) {
        if (!strlen($number['phone'])) {
            $error['phone-number-' . $k] = trans('VoIP account phone number is required!');
        } elseif (strlen($number['phone']) > 32) {
            $error['phone-number-' . $k] = trans('VoIP account phone number is too long (max.32 characters)!');
        } elseif ($LMS->GetVoipAccountIDByPhone($number['phone'])) {
            $error['phone-number-' . $k] = trans('Specified phone is in use!');
        } elseif (!preg_match('/^C?[0-9]+$/', $number['phone'])) {
            $error['phone-number-' . $k] = trans('Specified phone number contains forbidden characters!');
        }
    }

    if (!isset($voipaccountdata['balance']) || !is_numeric($voipaccountdata['balance'])) {
        $voipaccountdata['balance'] = 0;
    } elseif ($voipaccountdata['balance'] < 0) {
        $error['balance'] = trans('Account balance must be positive!');
    }

    if (!isset($voipaccountdata['cost_limit']) || !is_numeric($voipaccountdata['cost_limit'])) {
        $voipaccountdata['cost_limit'] = null;
    } elseif ($voipaccountdata['cost_limit'] < 0) {
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
            $error['voipaccountdata[customerid]'] = trans('VoIP account owner is not connected!');
            $error['voipaccountdata[ownerid]'] = trans('Selected customer is not connected!');
        }
    }

    if (!isset($error['voipaccountdata[ownerid]'])) {
        // check if selected address belongs to customer
        if ($voipaccountdata['address_id'] != -1 && !$LMS->checkCustomerAddress($voipaccountdata['address_id'], $voipaccountdata['ownerid'])) {
            $error['address_id'] = trans('Selected address was not assigned to customer.');
            $voipaccountdata['address_id'] = null;
        }
        // check if selected address is teryt address
        if (!isset($error['address_id']) && !ConfigHelper::checkPrivilege('full_access')
            && ($voipaccountdata['address_id'] <= 0 || !$LMS->isTerritAddress($voipaccountdata['address_id']))) {
            $terytRequired = ConfigHelper::getConfig('phpui.teryt_required', 'false');
            if ($terytRequired === 'error') {
                $terytRequired = true;
            } elseif ($terytRequired !== 'warning') {
                $terytRequired = ConfigHelper::checkValue($terytRequired);
            }
            if (is_bool($terytRequired) && $terytRequired) {
                $error['voipaccountdata[address_id]'] = trans('TERYT address is required!');
            } elseif ($terytRequired === 'warning' && !isset($warnings['voipaccountdata-address_id-'])) {
                $warning['voipaccountdata[address_id]'] = trans('TERYT address recommended!');
            }
        }
    }

    $hook_data = $plugin_manager->executeHook(
        'voipaccountadd_before_submit',
        array('voipaccountdata'=>$voipaccountdata, 'error'=>$error)
    );

    $voipaccountdata = $hook_data['voipaccountdata'];
    $error = $hook_data['error'];

    if (!$error && !$warning) {
        $voipaccountdata['flags'] = 0;

        if (isset($voipaccountdata[VOIP_ACCOUNT_FLAG_ADMIN_RECORDING])) {
            $voipaccountdata['flags'] |= VOIP_ACCOUNT_FLAG_ADMIN_RECORDING;
        }

        if (isset($voipaccountdata[VOIP_ACCOUNT_FLAG_CUSTOMER_RECORDING])) {
            $voipaccountdata['flags'] |= VOIP_ACCOUNT_FLAG_CUSTOMER_RECORDING;
        }

        if (isset($voipaccountdata[VOIP_ACCOUNT_FLAG_TRUNK])) {
            $voipaccountdata['flags'] |= VOIP_ACCOUNT_FLAG_TRUNK;
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

$layout['pagetitle'] = trans('New VoIP Account');

$SMARTY->assign('customers', ConfigHelper::checkConfig('phpui.big_networks') ? array() : $LMS->GetCustomerNames());

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
$SMARTY->assign('error', $error);
$SMARTY->assign('voipaccountdata', $voipaccountdata);
$SMARTY->display('voipaccount/voipaccountadd.html');
