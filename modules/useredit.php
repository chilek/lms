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

$userdata=$_POST[userdata];

if(!$LMS->UserExists($_GET[id]))
	header("Location: ?m=userlist");

if(isset($userdata))
{

	foreach($userdata as $key=>$value)
		$userdata[$key] = trim($value);

	// avoid stupid data in payday field

	$userdata[payday] = sprintf('%d',$userdata[payday]);
	
	if($userdata[payday] < 1)
		$userdata[payday] = 1;
	elseif($userdata[payday] > 28)
		$userdata[payday] = 28;

	if($userdata[lastname]=="")
		$error[username] = "To pole nie mo¿e byæ puste!";
	
	if($userdata[address]=="")
		$error[address] = "Proszê podaæ adres!";

	if($useradd[nip] !="" && !eregi("^[0-9]{3}-[0-9]{3}-[0-9]{2}-[0-9]{2}$",$useradd[nip]) && !eregi("^[0-9]{3}-[0-9]{2}-[0-9]{2}-[0-9]{3}$",$useradd[nip]) && !check_nip($useradd[nip]))
		$error[nip] = "Podany NIP jest b³êdny!";

	if($userdata[zip] !="" && !eregi("^[0-9]{2}-[0-9]{3}$",$userdata[zip]))
		$error[zip] = "Podany kod pocztowy jest b³êdny!";

	if($userdata[gguin] == 0)
		unset($userdata[gguin]);

	if($userdata[gguin] !="" && !eregi("^[0-9]{4,}$",$userdata[gguin]))
		$error[gguin] = "Podany numer GG jest niepoprawny!";

	if(!$LMS->TariffExists($userdata[tariff]))
		$error[tariff] = "Proszê wybraæ taryfê!";
		
	if($userdata[status]!=3&&$LMS->GetUserNodesNo($userdata[id])) 
		$error[status] = "Tylko pod³±czony u¿ytkownik mo¿e posiadaæ komputery!";
		
	if (!isset($error)){
		$LMS->UserUpdate($userdata);
		header("Location: ?m=userinfo&id=".$userdata[id]);
		die;
	}else{
		$olddata=$LMS->GetUser($_GET[id]);
		$userinfo=$userdata;
		$userinfo[createdby]=$olddata[createdby];
		$userinfo[modifiedby]=$olddata[modifiedby];
		$userinfo[creationdateh]=$olddata[creationdateh];
		$userinfo[moddateh]=$olddata[moddateh];
		$userinfo[username]=$olddata[username];
		$userinfo[balance]=$olddata[balance];
		if($olddata[status]==3)
			$userinfo[shownodes] = TRUE;
		$SMARTY->assign("error",$error);
	}
}else{

	$userinfo=$LMS->GetUser($_GET[id]);
	if($userinfo[status]==3)
		$userinfo[shownodes] = TRUE;
}

for($i=1;$i<29;$i++)
	$paydays[] = $i;

$layout[pagetitle]="Edycja danych u¿ytkownika ".$userinfo[username];
$SMARTY->assign("usernodes",$LMS->GetUserNodes($userinfo[id]));
$SMARTY->assign("balancelist",$LMS->GetUserBalanceList($userinfo[id]));
$SMARTY->assign("tariffs",$LMS->GetTariffs());
$SMARTY->assign("userinfo",$userinfo);
$SMARTY->assign("layout",$layout);
$SMARTY->assign("paydays",$paydays);
$SMARTY->display("useredit.html");

$_SESSION[backto] = $_SERVER[QUERY_STRING];

?>
