<?php

/*
 * LMS version 1.7-cvs
 *
 *  (C) Copyright 2001-2005 LMS Developers
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

if(isset($_POST['customeradd']))
{
	$customeradd = $_POST['customeradd'];

	if(sizeof($customeradd))
		foreach($customeradd as $key=>$value)
			$customeradd[$key] = trim($value);

	if($customeradd['name'] == '' && $customeradd['lastname'] == '' && $customeradd['phone1'] == '' && $customeradd['address'] == '' && $customeradd['email'] == '')
	{
		$SESSION->redirect('?m=customeradd');
	}

	if($customeradd['lastname'] == '')
		$error['customername'] = trans('\'Last/Company Name\' and \'First Name\' fields cannot be empty!');
	
	if($customeradd['address'] == '')
		$error['address'] = trans('Address required!');
	
	if($customeradd['nip'] !='' && !check_ten($customeradd['nip']))
		$error['nip'] = trans('Incorrect Tax Exempt Number!');

	if($customeradd['pesel'] != '' && !check_ssn($customeradd['pesel']))
		$error['pesel'] = trans('Incorrect Social Security Number!');
		
	if($customeradd['zip'] !='' && !check_zip($customeradd['zip']))
		$error['zip'] = trans('Incorrect ZIP code!');

	if($customeradd['gguin'] == '')
		$customeradd['gguin'] = 0;
	
	if($customeradd['pin'] == '')
		$customeradd['pin'] = 0;

	if($customeradd['gguin'] !=0 && !eregi('^[0-9]{4,}$',$customeradd['gguin']))
		$error['gguin'] = trans('Incorrect IM uin!');

        if($customeradd['pin']!=0 && !eregi('^[0-9]{4,6}$',$customeradd['pin']))
	        $error['pin'] = trans('Incorrect PIN code!');

	if($customeradd['email']!='' && !check_email($customeradd['email']))
		$error['email'] = trans('Incorrect email!');

	if(!$error)
	{
		$id = $LMS->CustomerAdd($customeradd);
		if(!isset($customeradd['reuse']))
		{
			$SESSION->redirect('?m=customerinfo&id='.$id);
		}
		$reuse['status'] = $customeradd['status'];
		unset($customeradd);
		$customeradd = $reuse;
		$customeradd['reuse'] = '1';
	}
}

if(!isset($customeradd['zip']) && isset($LMS->CONFIG['phpui']['default_zip']))
	$customeradd['zip'] = $LMS->CONFIG['phpui']['default_zip'];
if(!isset($customeradd['city']) && isset($LMS->CONFIG['phpui']['default_city']))
	$customeradd['city'] = $LMS->CONFIG['phpui']['default_city'];
if(!isset($customeradd['address']) && isset($LMS->CONFIG['phpui']['default_address']))
	$customeradd['address'] = $LMS->CONFIG['phpui']['default_address'];

$layout['pagetitle'] = trans('New Customer');

$SMARTY->assign('customeradd',$customeradd);
$SMARTY->assign('error',$error);
$SMARTY->display('customeradd.html');

?>
