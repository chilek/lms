<?php

/*
 * LMS version 1.2-cvs
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

	if($tariffadd['name']=="" && $tariffadd['comment']=="" && $tariffadd['value']=="")
	{
		header("Location: ?m=tarifflist");
		die;
	}

	$tariffadd['value'] = str_replace(",",".",$tariffadd['value']);

	if(!(ereg("^[-]?[0-9.,]+$",$tariffadd['value'])))
		$error['value'] = _('Incorrect tariff value!');

	if($tariffadd['taxvalue'] != '')
		if(!(ereg("^[0-9.,]+$",$tariffadd['taxvalue'])) || $tariffadd['taxvalue'] < 0 || $tariffadd['taxvalue'] > 100)
			$error['taxvalue'] = _('Incorrect tariff tax value!');

	if(!(ereg("^[0-9]+$", $tariffadd['uprate'])) && $tariffadd['uprate'] != "")
		$error['uprate'] = _("Uprate value must be integer");
	if($tariffadd['uprate']=="") $tariffadd['uprate']=0;
		
	if(!ereg("^[0-9]+$", $tariffadd['downrate']) && $tariffadd['downrate'] != "")
		$error['downrate'] = _("Downrate value must be integer");
	if($tariffadd['downrate']=="") $tariffadd['downrate']=0;
	
	if(($tariffadd['uprate'] < 8 || $tariffadd['uprate'] > 4096) && $tariffadd['uprate'] != "")
		$error['uprate'] = _("Uprate must be in range of 8 - 4096");

	if(($tariffadd['downrate'] < 8 || $tariffadd['downrate'] > 4096) && $tariffadd['downrate'] != "")
		$error['downrate'] = _("Downrate must be in range of 8 - 4096");
	
	if($tariffadd['name'] == "")
		$error['name'] = _("Tariff name is required!");
	else
		if($LMS->GetTariffIDByName($tariffadd['name']))
			$error['name'] = sprintf(_("Tariff name '%s' exists!"), $tariffadd['name']);

	if(!$error){
		header("Location: ?m=tarifflist&id=".$LMS->TariffAdd($tariffadd));
		die;
	}
	
}

$layout['pagetitle'] = _("New tariff");

$SMARTY->assign('layout',$layout);
$SMARTY->assign('error',$error);
$SMARTY->assign('tariffadd',$tariffadd);
$SMARTY->display('tariffadd.html');

?>
