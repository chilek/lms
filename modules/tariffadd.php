<?php

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
		die;
	}

	$tariffadd[value] = str_replace(",",".",$tariffadd[value]);

	if(!(ereg("^[-]?[0-9.,]+$",$tariffadd[value])))
		$error[value] = "Podana warto¶æ taryfy jest niepoprawna!";

	if(!(ereg("^[0-9]+$", $tariffadd[uprate])) && $tariffadd[uprate] != "")
		$error[uprate] = "Pole uprate musi zawieraæ liczbê ca³kowit±";
	if($tariffadd[uprate]=="") $tariffadd[uprate]=0;
		
	if(!ereg("^[0-9]+$", $tariffadd[downrate]) && $tariffadd[downrate] != "")
		$error[downrate] = "Pole downrate zawieraæ liczbê ca³kowit±";
	if($tariffadd[downrate]=="") $tariffadd[downrate]=0;
	
	if(($tariffadd[uprate] < 8 || $tariffadd[uprate] > 4096) && $tariffadd[uprate] != "")
		$error[uprate] = "Pole uprate musi zawieraæ liczbê z przedzia³u 8 - 4096";

	if(($tariffadd[downrate] < 8 || $tariffadd[downrate] > 4096) && $tariffadd[downrate] != "")
		$error[downrate] = "Pole downrate musi zawieraæ liczbê z przedzia³u 8 - 4096";
	
	if($tariffadd[name] == "")
		$error[name] = "Musisz podaæ nazwê taryfy!";
	else
		if($LMS->GetTariffIDByName($tariffadd[name]))
			$error[name] = "Istnieje ju¿ taryfa o nazwie '".$tariffadd[name]."'!";

	if(!$error){
		header("Location: ?m=tarifflist&id=".$LMS->TariffAdd($tariffadd));
		die;
	}
	
}

$layout[pagetitle]="Nowa taryfa";

$SMARTY->assign("layout",$layout);
$SMARTY->assign("error",$error);
$SMARTY->assign("tariffadd",$tariffadd);
$SMARTY->display("tariffadd.html");

/*
 * $Log$
 * Revision 1.27  2003/10/05 20:47:02  alec
 * stream -> rate in errors
 *
 * Revision 1.26  2003/10/05 20:37:15  alec
 * ujednolicenie interfejsu
 *
 * Revision 1.25  2003/09/09 01:22:28  lukasz
 * - nowe finanse
 * - kosmetyka
 * - bugfixy
 * - i inne rzeczy o których aktualnie nie pamiêtam
 *
 * Revision 1.24  2003/09/06 07:41:17  alec
 * dodana mo¿liwo¶æ tworzenia taryfy bez uprate i downrate, kosmetyka
 *
 * Revision 1.23  2003/09/05 13:11:24  lukasz
 * - nowy sposób wy¶wietlania informacji o b³êdach
 *
 * Revision 1.22  2003/08/24 13:12:54  lukasz
 * - massive attack: s/<?/<?php/g - that was causing problems on some fucked
 *   redhat's :>
 *
 * Revision 1.21  2003/08/18 16:52:19  lukasz
 * - added CVS Log tags
 *
 */
?>
