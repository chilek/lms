<?php

/*
 * LMS version 1.8-cvs
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

if(!$LMS->TariffExists($_GET['id']))
{
	$SESSION->redirect('?m=tarifflist');
}

$tariff = $_POST['tariff'];

if(isset($tariff))
{
	foreach($tariff as $key => $value)
		$tariff[$key] = trim($value);

	$tariff['value'] = str_replace(',','.',$tariff['value']);
	
	if($tariff['uprate'] == '') $tariff['uprate'] = 0;
	if($tariff['upceil'] == '') $tariff['upceil'] = 0;
	if($tariff['downrate'] == '') $tariff['downrate'] = 0;
	if($tariff['downceil'] == '') $tariff['downceil'] = 0;
	if($tariff['climit'] == '') $tariff['climit'] = 0;
	if($tariff['plimit'] == '') $tariff['plimit'] = 0;

	if($tariff['name'] == '')
		$error['name'] = trans('Subscription name required!');
	elseif($LMS->GetTariffIDByName($tariff['name']) && $tariff['name'] != $LMS->GetTariffName($_GET['id']))
		$error['name'] = trans('Subscription with specified name already exists!');

	if($tariff['value'] == '')
		$error['value'] = trans('Value required!');
	elseif(!(ereg('^[-]?[0-9.,]+$', $tariff['value'])))
		$error['value'] = trans('Incorrect value!');
	
	if(!(ereg("^[0-9]+$", $tariff['uprate'])))
		$error['uprate'] = trans('Integer value expected!');
	if(!ereg('^[0-9]+$', $tariff['downrate']))
		$error['downrate'] = trans('Integer value expected!');
	if(!(ereg("^[0-9]+$", $tariff['upceil'])))
		$error['upceil'] = trans('Integer value expected!');
	if(!ereg('^[0-9]+$', $tariff['downceil']))
		$error['downceil'] = trans('Integer value expected!');
	if(!(ereg("^[0-9]+$", $tariff['climit'])))
		$error['climit'] = trans('Integer value expected!');
	if(!ereg('^[0-9]+$', $tariff['plimit']))
		$error['plimit'] = trans('Integer value expected!');
	
	if(($tariff['uprate'] < 8 || $tariff['uprate'] > 4096) && $tariff['uprate'] != 0)
		$error['uprate'] = trans('This field must be within range 8 - 4096');
	if(($tariff['downrate'] < 8 || $tariff['downrate'] > 4096) && $tariff['downrate'] != 0)
		$error['downrate'] = trans('This field must be within range 8 - 4096');
	if(($tariff['upceil'] < 8 || $tariff['upceil'] < $tariff['uprate']) && $tariff['upceil'] != 0)
		$error['upceil'] = trans('This field must be greater than 8 and greater than upload rate');
	if(($tariff['downceil'] < 8 || $tariff['downceil'] < $tariff['downrate']) && $tariff['downceil'] != 0)
		$error['downceil'] = trans('This field must be greater than 8 and greater than download rate');

	if(!$tariff['taxid'])
		$tariff['taxid'] = 0;

	$tariff['id'] = $_GET['id'];

	if(!$error)
	{
		$LMS->TariffUpdate($tariff);
		$SESSION->redirect('?m=tariffinfo&id='.$tariff['id']);
	}

}else
	$tariff = $LMS->GetTariff($_GET['id']);
	
$layout['pagetitle'] = trans('Subscription Edit: $0',$tariff['name']);

$SMARTY->assign('tariff',$tariff);
$SMARTY->assign('taxeslist',$LMS->GetTaxes());
$SMARTY->assign('error',$error);
$SMARTY->display('tariffedit.html');

?>
