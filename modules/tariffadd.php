<? // $Id$

/*
 * LMS version 1.0
 *
 *  (C) Copyright 2002 Rulez.PL Development Team
 *  (C) Copyright 2001-2002 NetX ACN
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
	$ec = 0;
	foreach($tariffadd as $key => $value)
	{
		$tariffadd[$key] = trim($value);
		if(trim($value)=="")
			$ec++;
	}

	if($ec == 3)
	{
		header("Location: ?m=tarifflist");
		exit(0);
	}

	$tariffadd[value] = str_replace(",",".",$tariffadd[value]);
	
	if($tariffadd[name] == "")
		$error[name] = "To pole nie mo¿e byæ puste!";
	else
		if($LMS->GetTariffIDByName($tariffadd[name]))
			$error[name] = "Istnieje ju¿ taryfa o takiej nazwie!";

	if(!$error){
		header("Location: ?m=tariffinfo&id=".$LMS->TariffAdd($tariffadd));
		exit(0);
	}
	
}

$layout[pagetitle]="Lista taryf";

$tarifflist = $LMS->GetTariffList();

$SMARTY->assign("tarifflist",$tarifflist);

$SMARTY->assign("layout",$layout);
$SMARTY->assign("error",$error);
$SMARTY->assign("tariffadd",$tariffadd);
$SMARTY->display("header.html");
$SMARTY->display("tarifflist.html");
$SMARTY->display("footer.html");

?>
