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

if(isset($_POST['useradd']))
{
	$useradd = $_POST['useradd'];

	if(sizeof($useradd))
		foreach($useradd as $key=>$value)
			$useradd[$key] = trim($value);

	if($useradd['name'] == '' && $useradd['lastname'] == '' && $useradd['phone1'] == '' && $useradd['address'] == '' && $useradd['email'] == '')
	{
		$SESSION->redirect('?m=useradd');
	}

	if($useradd['lastname'] == '')
		$error['username'] = trans('\'Surname/Name\' and \'First Name\' fields cannot be empty!');
	
	if($useradd['address'] == '')
		$error['address'] = trans('Address required!');
	
	if($useradd['nip'] !='' && !check_ten($useradd['nip']))
		$error['nip'] = trans('Incorrect Tax Exempt Number!');

	if($useradd['pesel'] != '' && !check_ssn($useradd['pesel']))
		$error['pesel'] = trans('Incorrect Social Security Number!');
		
	if($useradd['zip'] !='' && !check_zip($useradd['zip']))
		$error['zip'] = trans('Incorrect ZIP code!');

	if($useradd['gguin'] == '')
		$useradd['gguin'] = 0;
	
	if($useradd['pin'] == '')
		$useradd['pin'] = 0;

	if($useradd['gguin'] !=0 && !eregi('^[0-9]{4,}$',$useradd['gguin']))
		$error['gguin'] = trans('Incorrect IM uin!');

        if($useradd['pin']!=0 && !eregi('^[0-9]{4,6}$',$useradd['pin']))
	        $error['pin'] = trans('Incorrect PIN code!');

	if($useradd['email']!='' && !check_email($useradd['email']))
		$error['email'] = trans('Incorrect email!');

	if(!$error)
	{
		$id = $LMS->UserAdd($useradd);
		if(!isset($useradd['reuse']))
		{
			$SESSION->redirect('?m=userinfo&id='.$id);
		}
		$reuse['status'] = $useradd['status'];
		unset($useradd);
		$useradd = $reuse;
		$useradd['reuse'] = '1';
	}
}

if(!isset($useradd['zip']) && isset($LMS->CONFIG['phpui']['default_zip']))
	$useradd['zip'] = $LMS->CONFIG['phpui']['default_zip'];
if(!isset($useradd['city']) && isset($LMS->CONFIG['phpui']['default_city']))
	$useradd['city'] = $LMS->CONFIG['phpui']['default_city'];
if(!isset($useradd['address']) && isset($LMS->CONFIG['phpui']['default_address']))
	$useradd['address'] = $LMS->CONFIG['phpui']['default_address'];

$layout['pagetitle'] = trans('New Customer');

$SMARTY->assign('useradd',$useradd);
$SMARTY->assign('error',$error);
$SMARTY->display('useradd.html');

?>
