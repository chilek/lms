<?php

/*
 * LMS version 1.11-cvs
 *
 *  (C) Copyright 2001-2008 LMS Developers
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

if(isset($_POST['tariff']))
{
	$tariff = $_POST['tariff'];
	$limit = isset($_POST['limit']) ? $_POST['limit'] : array();
	
	foreach($tariff as $key => $value)
		$tariff[$key] = trim($value);

	if($tariff['name']=='' && $tariff['description']=='' && $tariff['value']=='')
	{
		$SESSION->redirect('Location: ?m=tarifflist');
	}

	$tariff['value'] = str_replace(',','.',$tariff['value']);

	if(!(ereg('^[-]?[0-9.,]+$',$tariff['value'])))
		$error['value'] = trans('Incorrect subscription value!');

	$items = array('uprate', 'downrate', 'upceil', 'downceil', 'climit', 'plimit', 'dlimit');

	foreach($items as $item)
	{
		if($tariff[$item]=='')
			$tariff[$item] = 0;
		elseif(!ereg('^[0-9]+$', $tariff[$item]))
			$error[$item] = trans('Integer value expected!');
	}
	
	if(($tariff['uprate'] < 8 || $tariff['uprate'] > 10000) && $tariff['uprate'] != 0)
		$error['uprate'] = trans('This field must be within range 8 - 10000');
	if(($tariff['downrate'] < 8 || $tariff['downrate'] > 10000) && $tariff['downrate'] != 0)
		$error['downrate'] = trans('This field must be within range 8 - 10000');
	if(($tariff['upceil'] < 8 || $tariff['upceil'] < $tariff['uprate']) && $tariff['upceil'] != 0)
		$error['upceil'] = trans('This field must contain number greater than 8 and greater than upload rate');
	if(($tariff['downceil'] < 8 || $tariff['downceil'] < $tariff['downrate']) && $tariff['downceil'] != 0)
		$error['downceil'] = trans('This field must contain number greater than 8 and greater than download rate');

	if($tariff['name'] == '')
		$error['name'] = trans('Subscription name required!');
	else
		if($LMS->GetTariffIDByName($tariff['name']))
			$error['name'] = trans('Subscription $0 already exists!',$tariff['name']);

	if(!isset($tariff['taxid']))
		$tariff['taxid'] = 0;

	$items = array('domain_limit', 'alias_limit', 
			'sh_limit', 'mail_limit', 'www_limit', 'ftp_limit', 'sql_limit', 
			'quota_sh_limit', 'quota_mail_limit', 'quota_www_limit', 
			'quota_ftp_limit', 'quota_sql_limit', 
	);
	
	foreach($items as $item)
	{
		if(isset($limit[$item])) 
			$tariff[$item] = NULL;
		elseif(!ereg('^[0-9]+$', $tariff[$item]))
			$error[$item] = trans('Integer value expected!');
	}

	if(!$error)
	{
		$SESSION->redirect('?m=tarifflist&id='.$LMS->TariffAdd($tariff));
	}

	$SMARTY->assign('error',$error);
}
else
{
	$tariff['domain_limit'] = 0;	
	$tariff['alias_limit'] = 0;	
	$tariff['sh_limit'] = 0;	
	$tariff['www_limit'] = 0;	
	$tariff['mail_limit'] = 0;	
	$tariff['ftp_limit'] = 0;	
	$tariff['sql_limit'] = 0;	
	$tariff['quota_sh_limit'] = 0;	
	$tariff['quota_www_limit'] = 0;	
	$tariff['quota_mail_limit'] = 0;	
	$tariff['quota_ftp_limit'] = 0;	
	$tariff['quota_sql_limit'] = 0;	
}

$layout['pagetitle'] = trans('New Subscription');

$SMARTY->assign('taxeslist',$LMS->GetTaxes());
$SMARTY->assign('tariff', $tariff);
$SMARTY->display('tariffadd.html');

?>
