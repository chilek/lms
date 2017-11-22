<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2017 LMS Developers
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

include_once('class.php');
include_once('authentication.inc');

$layout['pagetitle'] = trans('Customer Logon');

$loginform = $_POST['loginform'];
$login = (trim($loginform['login']) ? trim($loginform['login']) : 0);
$pin = (trim($loginform['pwd']) ? trim($loginform['pwd']) : 0);

// customer authorization ways
//$id = GetCustomerIDByPhoneAndPIN($login, $pin);
//$id = GetCustomerIDByContractAndPIN($login, $pin);
$id = GetCustomerIDByIDAndPIN($login, $pin);

if($id)
{
	session_start();
	$_SESSION['uid'] = $id;

	$LMS->executeHook('customer_otherip_before_display', array('smarty' => $SMARTY, 'customerid' => $id));

	$customerinfo = $LMS->GetCustomer($id);
	$SMARTY->assign('customerinfo', $customerinfo);
	$SMARTY->assign('balancelist',$LMS->GetCustomerBalanceList($id));
	$SMARTY->assign('limit',15);
	$SMARTY->assign('account_no',ConfigHelper::getConfig('finances.account'));
	$SMARTY->assign('bank_name',ConfigHelper::getConfig('finances.bank'));
	$SMARTY->display('balanceview.html');
}
else
	header('Location: index.php?error=1');

?>
