<?

/*
 * LMS version 1.0-cvs
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

$useradd = $_POST[useradd];

if(sizeof($useradd))
	foreach($useradd as $key=>$value)
		$useradd[$key] = trim($value);

if($useradd[name]=="" && $useradd[lastname]=="" && $useradd[phone1]=="" && $useradd[address]=="" && $useradd[email]=="" && isset($useradd))
{
	header("Location: ?m=useradd");
	exit(0);
}
elseif(isset($useradd))
{
	if($useradd[lastname]=="")
		$error[username]=TRUE;
	
	if($useradd[address]=="")
		$error[address]="Proszê podaæ adres!";
	
	if(!$LMS->TariffExists($useradd[tariff]))
		$error[tariff]=TRUE;
	
	if($useradd[nip] !="" && !eregi("^[0-9]{3}-[0-9]{3}-[0-9]{2}-[0-9]{2}$",$useradd[nip]) && !eregi("^[0-9]{3}-[0-9]{2}-[0-9]{2}-[0-9]{3}$",$useradd[nip]))
		$error[nip] = "Podany NIP jest b³êdny!";
		
	if($useradd[zip] !="" && !eregi("^[0-9]{2}-[0-9]{3}$",$useradd[zip]))
		$error[zip] = "Podany kod pocztowy jest b³êdny!";

	if($useradd[gguin] == 0)
		unset($useradd[gguin]);

	if($useradd[gguin] !="" && !eregi("^[0-9]{4,}$",$useradd[gguin]))
		$error[gguin] = "Podany numer GG jest niepoprawny!";
	
	if(!$error)
	{
		$id = $LMS->UserAdd($useradd);
		if($useradd[reuse] =="")
		{
			header("Location: ?m=userinfo&id=".$id);
			exit(0);
		}
		$reuse[status] = $useradd[status];
		$reuse[tariff] = $useradd[tariff];
		unset($useradd);
		$useradd = $reuse;
		$useradd[reuse] = "1";
	}
}

$layout[pagetitle]="Nowy u¿ytkownik";
$tariffs = $LMS->GetTariffs();
if(!isset($useradd[tariff]))
	$useradd[tariff] = $tariffs[common];	
$SMARTY->assign("layout",$layout);
$SMARTY->assign("useradd",$useradd);
$SMARTY->assign("error",$error);
$SMARTY->assign("tariffs",$tariffs);
$SMARTY->display("useradd.html");

?>
