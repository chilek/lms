<?php

/*
 * LMS version 1.5-cvs
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

$tariffadd = $_POST['tariffadd'];

if(isset($tariffadd))
{
	foreach($tariffadd as $key => $value)
		$tariffadd[$key] = trim($value);

	if($tariffadd['name']=='' && $tariffadd['comment']=='' && $tariffadd['value']=='')
	{
		header("Location: ?m=tarifflist");
		die;
	}

	$tariffadd['value'] = str_replace(',','.',$tariffadd['value']);

	if(!(ereg('^[-]?[0-9.,]+$',$tariffadd['value'])))
		$error['value'] = 'Podana warto¶æ taryfy jest niepoprawna!';

	if($tariffadd['taxvalue']!='')
		if(!(ereg('^[0-9.,]+$',$tariffadd['taxvalue'])) || $tariffadd['taxvalue'] < 0 || $tariffadd['taxvalue'] > 100)
			$error['taxvalue'] = 'Podana stawka podatku jest niepoprawna!';

	if($tariffadd['uprate']=='') $tariffadd['uprate'] = 0;
	if($tariffadd['downrate']=='') $tariffadd['downrate'] = 0;
	if($tariffadd['upceil']=='') $tariffadd['upceil'] = 0;
	if($tariffadd['downceil']=='') $tariffadd['downceil'] = 0;
	if($tariffadd['climit']=='') $tariffadd['climit'] = 0;
	if($tariffadd['plimit']=='') $tariffadd['plimit'] = 0;

	if(!ereg('^[0-9]+$', $tariffadd['uprate']))
		$error['uprate'] = 'To pole musi zawieraæ liczbê ca³kowit±';
	if(!ereg('^[0-9]+$', $tariffadd['downrate']))
		$error['downrate'] = 'To pole musi zawieraæ liczbê ca³kowit±';
	if(!ereg('^[0-9]+$', $tariffadd['upceil']))
		$error['upceil'] = 'To pole musi zawieraæ liczbê ca³kowit±';
	if(!ereg('^[0-9]+$', $tariffadd['downceil']))
		$error['downceil'] = 'To pole musi zawieraæ liczbê ca³kowit±';
	if(!ereg('^[0-9]+$', $tariffadd['climit']))
		$error['climit'] = 'To pole musi zawieraæ liczbê ca³kowit±';
	if(!ereg('^[0-9]+$', $tariffadd['plimit']))
		$error['plimit'] = 'To pole musi zawieraæ liczbê ca³kowit±';

	if(($tariffadd['uprate'] < 8 || $tariffadd['uprate'] > 4096) && $tariffadd['uprate'] != 0)
		$error['uprate'] = 'To pole musi zawieraæ liczbê z przedzia³u 8 - 4096';
	if(($tariffadd['downrate'] < 8 || $tariffadd['downrate'] > 4096) && $tariffadd['downrate'] != 0)
		$error['downrate'] = 'To pole musi zawieraæ liczbê z przedzia³u 8 - 4096';
	if(($tariffadd['upceil'] < 8 || $tariffadd['upceil'] < $tariffadd['uprate']) && $tariffadd['upceil'] != 0)
		$error['upceil'] = 'To pole musi zawieraæ liczbê wiêksz± od 8 i wiêksz± od upload rate';
	if(($tariffadd['downceil'] < 8 || $tariffadd['downceil'] < $tariffadd['downrate']) && $tariffadd['downceil'] != 0)
		$error['downceil'] = 'To pole musi zawieraæ liczbê wiêksz± od 8 i wiêksz± od download rate';

	if($tariffadd['name'] == '')
		$error['name'] = 'Musisz podaæ nazwê taryfy!';
	else
		if($LMS->GetTariffIDByName($tariffadd['name']))
			$error['name'] = "Istnieje ju¿ taryfa o nazwie '".$tariffadd['name']."'!";

	if(!$error)
	{
		header('Location: ?m=tarifflist&id='.$LMS->TariffAdd($tariffadd));
		die;
	}
	
}

$layout['pagetitle'] = 'Nowa taryfa';

$SMARTY->assign('error',$error);
$SMARTY->assign('tariffadd',$tariffadd);
$SMARTY->display('tariffadd.html');

?>
