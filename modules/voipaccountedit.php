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

if(!$LMS->VoipAccountExists($_GET['id']))
	if(isset($_GET['ownerid']))
		header('Location: ?m=customerinfo&id='.$_GET['ownerid']);
	else
		header('Location: ?m=voipaccountlist');

$voipaccountid = intval($_GET['id']);
$voipaccountinfo = $LMS->GetVoipAccount($voipaccountid);
$customerid = $voipaccountinfo['ownerid'];

if(!isset($_GET['ownerid']))
	$SESSION->save('backto', $SESSION->get('backto') . '&ownerid='.$customerid);
else
	$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$layout['pagetitle'] = trans('Voip Account Edit: $a', $voipaccountinfo['login']);

if(isset($_POST['voipaccountedit']))
{
	$voipaccountedit = $_POST['voipaccountedit'];
	
	foreach($voipaccountedit as $key => $value) {
		if (!is_array($value)) {
			$voipaccountedit[$key] = trim($value);
		}
	}
	
	if($voipaccountedit['login']=='')
		$error['login'] = trans('Voip account login is required!');
	else
	{
		$loginids = $LMS->GetVoipAccountIDByLogin($voipaccountedit['login']);

		$foundid = 0;
		if(isset($loginids))
			foreach($loginids as $loginid)
			{
				$foundid = ($loginid['id'] == $voipaccountedit['id']);
				if($foundid)
					break;
				else
					$error['login'] = trans('Specified login is in use!');
			}
/*		if($foundid)
		{
			$phoneid = $LMS->GetVoipAccountIDByPhone($voipaccountedit['phone']);
			if(isset($phoneid) && $phoneid != $foundid)
				$error['login'] = trans('Specified login is in use!');
		}
*/

	}
	if(!$error['login'])
		if(!preg_match('/^[_a-z0-9-]+$/i', $voipaccountedit['login']))
			$error['login'] = trans('Specified login contains forbidden characters!');
		elseif(strlen($voipaccountedit['login'])>32)
			$error['login'] = trans('Voip account login is too long (max.32 characters)!');

	if($voipaccountedit['passwd']=='')
		$error['passwd'] = trans('Voip account password is required!');
	elseif(strlen($voipaccountedit['passwd']) > 32)
		$error['passwd'] = trans('Voip account password is too long (max.32 characters)!');
	elseif(!preg_match('/^[_a-z0-9-@]+$/i', $voipaccountedit['passwd']))
		$error['passwd'] = trans('Specified password contains forbidden characters!');		

	if($voipaccountedit['phone']=='')
		$error['phone'] = trans('Voip account phone number is required!');
	elseif(strlen($voipaccountedit['phone']) > 32)
		$error['phone'] = trans('Voip account phone number is too long (max.32 characters)!');
	elseif($LMS->GetVoipAccountIDByPhone($voipaccountedit['phone']) && $LMS->GetVoipAccountIDByPhone($voipaccountedit['phone']) != $voipaccountedit['id'])
		$error['phone'] = trans('Specified phone is in use!');
	elseif(!preg_match('/^C?[0-9]+$/', $voipaccountedit['phone']))
		$error['phone'] = trans('Specified phone number contains forbidden characters or is too short!');

	if(!$LMS->CustomerExists($voipaccountedit['ownerid']))
		$error['customer'] = trans('You have to select owner!');
	else
	{
		$status = $LMS->GetCustomerStatus($voipaccountedit['ownerid']);
		if($status == 1) // unknown (interested)
			$error['customer'] = trans('Selected customer is not connected!');
		elseif($status == 2) // awaiting
	                $error['customer'] = trans('Voip account owner is not connected!');
	}

	$voipaccountinfo['login'] = $voipaccountedit['login'];
	$voipaccountinfo['passwd'] = $voipaccountedit['passwd'];
	$voipaccountinfo['phone'] = $voipaccountedit['phone'];
	$voipaccountinfo['ownerid'] = $voipaccountedit['ownerid'];
	$voipaccountinfo['location'] = $voipaccountedit['location'];
	$voipaccountinfo['location_city'] = $voipaccountedit['location_city'];
	$voipaccountinfo['location_street'] = $voipaccountedit['location_street'];
	$voipaccountinfo['location_house'] = $voipaccountedit['location_house'];
	$voipaccountinfo['location_flat'] = $voipaccountedit['location_flat'];

        $hook_data = $plugin_manager->executeHook(
            'voipaccountedit_before_submit',
            array(
                'voipaccountedit' => $voipaccountedit,
                'error' => $error
            )
        );
        $voipaccountedit = $hook_data['voipaccountedit'];
        $error = $hook_data['error'];
        
	if(!$error)
	{
		if (empty($voipaccountedit['teryt'])) {
			$voipaccountedit['location_city'] = null;
			$voipaccountedit['location_street'] = null;
			$voipaccountedit['location_house'] = null;
			$voipaccountedit['location_flat'] = null;
		}

		$LMS->VoipAccountUpdate($voipaccountedit);
		$SESSION->redirect('?m=voipaccountinfo&id='.$voipaccountedit['id']);
		die;
	}
} else
	if ($voipaccountinfo['location_city'] && $voipaccountinfo['location_street'])
		$voipaccountinfo['teryt'] = 1;

$customers = $LMS->GetCustomerNames();

include(MODULES_DIR.'/customer.inc.php');

$hook_data = $plugin_manager->executeHook(
    'voipaccountedit_before_display', 
    array(
        'voipaccountinfo' => $voipaccountinfo,
        'smarty' => $SMARTY,
    )
);

$voipaccountinfo = $hook_data['voipaccountinfo'];

$SMARTY->assign('customervoipaccounts',$customervoipaccounts);
$SMARTY->assign('error',$error);
$SMARTY->assign('voipaccountinfo',$voipaccountinfo);
$SMARTY->assign('customers',$customers);
$SMARTY->display('voipaccount/voipaccountedit.html');

?>
