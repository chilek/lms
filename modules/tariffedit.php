<?php

/*
 * LMS version 1.3-cvs
 *
 *  (C) Copyright 2001-2004 LMS Developers
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
	header('Location: ?m=tarifflist');
	die;
}

$tariff = $_POST['tariff'];

if(isset($tariff))
{
	foreach($tariff as $key => $value)
		$tariff[$key] = trim($value);

	$tariff['value'] = str_replace(',','.',$tariff['value']);
	
	if($tariff['uprate'] == '')
		$tariff['uprate'] = 0;
	
	if($tariff['downrate'] == '')
		$tariff['downrate'] = 0;
	
	if($tariff['name'] == '')
		$error['name'] = 'Prosz� poda� nazw� taryfy!';
	elseif($LMS->GetTariffIDByName($tariff['name']) && $tariff['name'] != $LMS->GetTariffName($_GET['id']))
		$error['name'] = 'Istnieje ju� taryfa o takiej nazwie!';	

	if($tariff['value'] == '')
		$error['value'] = 'Prosz� poda� warto��!';
	elseif(!(ereg('^[-]?[0-9.,]+$', $tariff['value'])))
		$error['value'] = 'Podana warto�� jest niepoprawna!';
	
	if($tariff['taxvalue'] != '')
	  	if(!(ereg('^[0-9.,]+$', $tariff['taxvalue'])) || $tariff['taxvalue'] < 0 || $tariff['taxvalue'] > 100)
			$error['taxvalue'] = 'Podana stawka podatku jest niepoprawna!';

	if(!(ereg("^[0-9]+$", $tariff['uprate'])))
		$error['uprate'] = 'To pole musi zawiera� liczb� ca�kowit�';
	
	if(!ereg('^[0-9]+$', $tariff['downrate']))
		$error['downrate'] = 'To pole musi zawiera� liczb� ca�kowit�';
	
	if(($tariff['uprate'] < 8 || $tariff['uprate'] > 4096) && $tariff['uprate'] != 0)
		$error['uprate'] = 'To pole musi zawiera� liczb� z przedzia�u 8 - 4096';
	
	if(($tariff['downrate'] < 8 || $tariff['downrate'] > 4096) && $tariff['downrate'] != 0)
		$error['downrate'] = 'To pole musi zawiera� liczb� z przedzia�u 8 - 4096';

	$tariff['id'] = $_GET['id'];
//	$tariff['count'] = $LMS->GetUsersWithTariff($_GET['id']);	
//	$tariff['totalval'] = $tariff['count'] * str_replace('.',',',$tariff['value']);
	if(!$error)
	{
		$LMS->TariffUpdate($tariff);
		header('Location: ?m=tariffinfo&id='.$tariff['id']);
		die;
	}

}else
	$tariff = $LMS->GetTariff($_GET['id']);
	
$layout['pagetitle'] = 'Edycja taryfy: '.$tariff['name'];	
$SMARTY->assign('tariff',$tariff);
$SMARTY->assign('error',$error);
$SMARTY->display('tariffedit.html');

?>
