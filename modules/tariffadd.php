<?

/*
 * LMS version 1.1-cvs
 *
 *  (C) Copyright 2001-2003 LMS Developers
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

$tariffadd = $_POST[tariffadd];

if(isset($tariffadd))
{
	foreach($tariffadd as $key => $value)
		$tariffadd[$key] = trim($value);

	if($tariffadd[name]=="" && $tariffadd[comment]=="" && $tariffadd[value]=="")
	{
		header("Location: ?m=tarifflist");
		exit(0);
	}

	$tariffadd[value] = str_replace(",",".",$tariffadd[value]);

	if($tariffadd[uprate] == "")
		$tariffadd[uprate] = 0;
	
	if($tariffadd[downrate] == "")
		$tariffadd[downrate] = 0;

	if(!(ereg("^[0-9.,]+$",$tariffadd[value])))
		$error[value] = $lang[error_value];

	if(!(ereg("^[0-9]+$", $tariffadd[uprate])))
		$error[uprate] = $lang[error_value2];
		
	if(!ereg("^[0-9]+$", $tariffadd[downrate]))
		$error[downrate] = $lang[error_value2];
	
	if(($tariffadd[uprate] < 8 || $tariffadd[uprate] > 4096) && $tariffadd[uprate] != 0)
		$error[uprate] = $lang[error_uprate];

	if(($tariffadd[downrate] < 8 || $tariffadd[downrate] > 4096) && $tariffadd[downrate] != 0)
		$error[downrate] = $lang[error_downrate];
	
	if($tariffadd[name] == "")
		$error[name] = $lang[error_no_empty_field];
	else
		if($LMS->GetTariffIDByName($tariffadd[name]))
			$error[name] = $lang[error_field_already_exists];

	if(!$error){
		header("Location: ?m=tarifflist&id=".$LMS->TariffAdd($tariffadd));
		exit(0);
	}
	
}

$layout[pagetitle] = $lang[pagetitle_tariffad];

$SMARTY->assign("layout",$layout);
$SMARTY->assign("error",$error);
$SMARTY->assign("tariffadd",$tariffadd);
$SMARTY->display("tariffadd.html");

?>
