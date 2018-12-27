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

if (!$LMS->VoipAccountExists($_GET['id']))
	if(isset($_GET['ownerid']))
		header('Location: ?m=customerinfo&id='.$_GET['ownerid']);
	else
		header('Location: ?m=voipaccountlist');

$voipaccountid = intval($_GET['id']);
$voipaccountinfo = $LMS->GetVoipAccount($voipaccountid);
$customerid = $voipaccountinfo['ownerid'];

if (!isset($_GET['ownerid']))
	$SESSION->save('backto', $SESSION->get('backto') . '&ownerid='.$customerid);
else
	$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$layout['pagetitle'] = trans('Voip Account Edit: $a', $voipaccountinfo['login']);

if (isset($_POST['voipaccountedit'])) {
	$voipaccountedit = $_POST['voipaccountedit'];
	//$voipaccountedit['address_id'] = $voipaccountinfo['address_id'];

	foreach ($voipaccountedit as $key => $value) {
		if (!is_array($value)) {
			$voipaccountedit[$key] = trim($value);
		}
	}

	if ($voipaccountedit['login']=='')
		$error['login'] = trans('Voip account login is required!');
	else {
		$loginids = $LMS->GetVoipAccountIDByLogin($voipaccountedit['login']);

		$foundid = 0;
		if (isset($loginids))
			foreach ($loginids as $loginid)
			{
				$foundid = ($loginid['id'] == $voipaccountedit['id']);
				if($foundid)
					break;
				else
					$error['login'] = trans('Specified login is in use!');
			}
	}

	if (!$error['login'])
		if (!preg_match('/^[_a-z0-9-]+$/i', $voipaccountedit['login']))
			$error['login'] = trans('Specified login contains forbidden characters!');
		elseif (strlen($voipaccountedit['login'])>32)
			$error['login'] = trans('Voip account login is too long (max.32 characters)!');

	if ($voipaccountedit['passwd']=='')
		$error['passwd'] = trans('Voip account password is required!');
	elseif (strlen($voipaccountedit['passwd']) > 32)
		$error['passwd'] = trans('Voip account password is too long (max.32 characters)!');
	elseif (!preg_match('/^[_a-z0-9-@%]+$/i', $voipaccountedit['passwd']))
		$error['passwd'] = trans('Specified password contains forbidden characters!');

	foreach ($voipaccountedit['phone'] as $k => $phone) {
		if (!strlen($phone))
			$error['phone'.$k] = trans('Voip account phone number is required!');
		elseif (strlen($phone) > 32)
			$error['phone'.$k] = trans('Voip account phone number is too long (max.32 characters)!');
		elseif (($accountid = $LMS->GetVoipAccountIDByPhone($phone)) > 0 && $accountid != $voipaccountedit['id'])
			$error['phone'.$k] = trans('Specified phone is in use!');
		elseif (!preg_match('/^C?[0-9]+$/', $phone))
			$error['phone'.$k] = trans('Specified phone number contains forbidden characters!');
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
			$error['voipaccountedit[customerid]'] = trans('Voip account owner is not connected!');
			$error['voipaccountedit[ownerid]'] = trans('Selected customer is not connected!');
		}
	}

	$flags = 0;
	if (!empty($voipaccountedit['admin_record_flag']))
		$flags |= CALL_FLAG_ADMIN_RECORDING;

    if (!empty($voipaccountedit['customer_record_flag']))
		$flags |= CALL_FLAG_CUSTOMER_RECORDING;

	if (ConfigHelper::checkPrivilege('superuser')) {
		$voipaccountinfo['balance'] = $voipaccountedit['balance'];
		$voipaccountinfo['cost_limit'] = (strlen($voipaccountedit['cost_limit'])) ? $voipaccountedit['cost_limit'] : NULL;
	} else {
		$voipaccountedit['balance'] = $voipaccountinfo['balance'];
		$voipaccountedit['cost_limit'] = $voipaccountinfo['cost_limit'];
	}

    $voipaccountinfo['flags']   = $voipaccountedit['flags'] = $flags;
    $voipaccountinfo['login']   = $voipaccountedit['login'];
    $voipaccountinfo['passwd']  = $voipaccountedit['passwd'];
    $voipaccountinfo['ownerid'] = $voipaccountedit['ownerid'];

    foreach ($voipaccountedit['phone'] as $k=>$v)
        $voipaccountinfo['phones'][$k] = array('phone'=>$v);

	if (!isset($error['voipaccountedit[ownerid]']))
		// check if selected address belongs to customer
		if ( $voipaccountedit['address_id'] != -1 && !$LMS->checkCustomerAddress($voipaccountedit['address_id'], $voipaccountedit['ownerid']) ) {
			$error['address_id'] = trans('Selected address was not assigned to customer.');
			$voipaccountedit['address_id'] = null;
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

if (!ConfigHelper::checkConfig('phpui.big_networks'))
	$SMARTY->assign('customers', $LMS->GetCustomerNames());

if (!empty($voipaccountedit['ownerid']) && $LMS->CustomerExists($voipaccountedit['ownerid']) && ($customerid = $voipaccountedit['ownerid'])) {
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

$SMARTY->assign('pool_list'           , $DB->GetAll("SELECT id,name FROM voip_pool_numbers;"));
$SMARTY->assign('customervoipaccounts', $customervoipaccounts);
$SMARTY->assign('error'               , $error);
$SMARTY->assign('voipaccountinfo'     , $voipaccountinfo);
$SMARTY->assign('customers'           , $customers);
$SMARTY->assign('customer_addresses'  , empty($voipaccountinfo['ownerid']) ? array() : $LMS->getCustomerAddresses($voipaccountinfo['ownerid']) );

$SMARTY->display('voipaccount/voipaccountedit.html');

?>
