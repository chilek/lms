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

$useradd = $_POST[useradd];

if(sizeof($useradd))
	foreach($useradd as $key=>$value)
		$useradd[$key] = trim($value);

if($useradd[name]=="" && $useradd[lastname]=="" && $useradd[phone1]=="" && $useradd[address]=="" && $useradd[email]=="" && isset($useradd))
{
	header("Location: ?m=useradd");
	die;
}
elseif(isset($useradd))
{

	$useradd[payday] = sprintf('%d',$useradd[payday]);
	
	if($useradd[payday] < 1)
		$useradd[payday] = 1;
	elseif($useradd[payday] > 28)
		$useradd[payday] = 28;
							
	if($useradd[lastname]=="")
		$error[username]="Pola 'nazwisko/nazwa' oraz 'imiê' nie mog± byæ puste!";
	
	if($useradd[address]=="")
		$error[address]="Proszê podaæ adres!";
	
	if(!$LMS->TariffExists($useradd[tariff]))
		$error[tariff]="Proszê wybraæ taryfê!";
	
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
			die;
		}
		$reuse[status] = $useradd[status];
		$reuse[tariff] = $useradd[tariff];
		unset($useradd);
		$useradd = $reuse;
		$useradd[reuse] = "1";
	}
}else{
	$useradd[zip] = $_CONFIG[phpui][default_zip];
	$useradd[city] = $_CONFIG[phpui][default_city];
	$useradd[address] = $_CONFIG[phpui][default_address];
}

$layout[pagetitle]="Nowy u¿ytkownik";
$tariffs = $LMS->GetTariffs();
if(!isset($useradd[tariff]))
	$useradd[tariff] = $tariffs[common];
if(!isset($useradd[payday]))
	if(chkconfig($_CONFIG[phpui][use_current_payday]))
		$useradd[payday] = (date('j',time()) > 28 ? 28 : date('j',time()));
	else
		$useradd[payday] = $tariffs[commonpayday];
for($i=1;$i<29;$i++)
        $paydays[] = $i;
$SMARTY->assign("paydays",$paydays);
$SMARTY->assign("layout",$layout);
$SMARTY->assign("useradd",$useradd);
$SMARTY->assign("error",$error);
$SMARTY->assign("tariffs",$tariffs);
$SMARTY->display("useradd.html");

/*
 * $Log$
 * Revision 1.36  2003/09/05 13:11:24  lukasz
 * - nowy sposób wy¶wietlania informacji o b³êdach
 *
 * Revision 1.35  2003/08/24 13:12:54  lukasz
 * - massive attack: s/<?/<?php/g - that was causing problems on some fucked
 *   redhat's :>
 *
 * Revision 1.34  2003/08/18 16:52:19  lukasz
 * - added CVS Log tags
 *
 */
?>
