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

$voipaccountdata['access'] = 1;
$voipaccountdata['ownerid'] = 0;

if(isset($_GET['ownerid']))
{
	if($LMS->CustomerExists($_GET['ownerid']) == true)
	{
		$voipaccountdata['ownerid'] = $_GET['ownerid'];
		$customerinfo = $LMS->GetCustomer($_GET['ownerid']);
		$SMARTY->assign('customerinfo', $customerinfo);
	}
	else
		$SESSION->redirect('?m=voipaccountinfo&id='.$_GET['ownerid']);
}

if(isset($_GET['prelogin']))
	$voipaccountdata['login'] = $_GET['prelogin'];

if(isset($_GET['prepasswd']))
	$voipaccountdata['passwd'] = $_GET['prepasswd'];

if(isset($_GET['prephone']))
	$voipaccountdata['phone'] = $_GET['prephone'];

if(isset($_POST['voipaccountdata']))
{
	$voipaccountdata = $_POST['voipaccountdata'];

        $error = array();
        
	foreach($voipaccountdata as $key => $value) {
		if (!is_array($value)) {
			$voipaccountdata[$key] = trim($value);
		}
	}

	if($voipaccountdata['login']=='')
		$error['login'] = trans('Voip account login is required!');
	elseif(strlen($voipaccountdata['login']) > 32)
		$error['login'] = trans('Voip account login is too long (max.32 characters)!');
	//elseif($LMS->GetVoipAccountIDByLogin($voipaccountdata['login']) && $LMS->GetVoipAccountIDByPhone($voipaccountdata['phone']))
	elseif($LMS->GetVoipAccountIDByLogin($voipaccountdata['login']))
		$error['login'] = trans('Specified login is in use!');
	elseif(!preg_match('/^[_a-z0-9-]+$/i', $voipaccountdata['login']))
		$error['login'] = trans('Specified login contains forbidden characters!');		

	if($voipaccountdata['passwd']=='')
		$error['passwd'] = trans('Voip account password is required!');
	elseif(strlen($voipaccountdata['passwd']) > 32)
		$error['passwd'] = trans('Voip account password is too long (max.32 characters)!');
	elseif(!preg_match('/^[_a-z0-9-@]+$/i', $voipaccountdata['passwd']))
		$error['passwd'] = trans('Specified password contains forbidden characters!');		

	if($voipaccountdata['phone']=='')
		$error['phone'] = trans('Voip account phone number is required!');
	elseif(strlen($voipaccountdata['phone']) > 32)
		$error['phone'] = trans('Voip account phone number is too long (max.32 characters)!');
	elseif($LMS->GetVoipAccountIDByPhone($voipaccountdata['phone']))
		$error['phone'] = trans('Specified phone is in use!');
	elseif(!preg_match('/^C?[0-9]+$/', $voipaccountdata['phone']))
		$error['phone'] = trans('Specified phone number contains forbidden characters or is too short!');

	if(!$LMS->CustomerExists($voipaccountdata['ownerid']))
		$error['customer'] = trans('You have to select owner!');
	else
	{
		$status = $LMS->GetCustomerStatus($voipaccountdata['ownerid']);
		if($status == 1) // unknown (interested)
			$error['customer'] = trans('Selected customer is not connected!');
		elseif($status == 2) // awaiting
	                $error['customer'] = trans('Voip account owner is not connected!');
	}

        $hook_data = $plugin_manager->executeHook(
                'voipaccountadd_before_submit',
                array(
                    'voipaccountdata' => $voipaccountdata,
                    'error' => $error
                )
        );
        $voipaccountdata = $hook_data['voipaccountdata'];
        $error = $hook_data['error'];
        
	if(!$error)
	{
		if (empty($voipaccountdata['teryt'])) {
			$voipaccountdata['location_city'] = null;
			$voipaccountdata['location_street'] = null;
			$voipaccountdata['location_house'] = null;
			$voipaccountdata['location_flat'] = null;
		}

		$voipaccountid = $LMS->VoipAccountAdd($voipaccountdata);

		if(!isset($voipaccountdata['reuse']))
		{
			$SESSION->redirect('?m=voipaccountinfo&id='.$voipaccountid);
		}
		
		$ownerid = $voipaccountdata['ownerid'];
		unset($voipaccountdata);
		
		$voipaccountdata['ownerid'] = $ownerid;
		$voipaccountdata['reuse'] = '1';
	}
}

$layout['pagetitle'] = trans('New Voip Account');

$customers = $LMS->GetCustomerNames();

if($customerid = $voipaccountdata['ownerid'])
{
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

$SMARTY->assign('customers', $customers);
$SMARTY->assign('error', $error);
$SMARTY->assign('voipaccountdata', $voipaccountdata);
$SMARTY->display('voipaccount/voipaccountadd.html');

?>
