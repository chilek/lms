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

 
function check_nip($nip)

{

	$steps = array(6, 5, 7, 2, 3, 4, 5, 6, 7);

	$nip = str_replace('-', '', $nip);
	$nip = str_replace(' ', '', $nip);

	if (strlen($nip) != 10) return 0;

	for ($x = 0; $x < 9; $x++) $sum_nb += $steps[$x] * $nip[$x];

	if ($sum_nb % 11 == $nip[9]) return 1;

    return 0;

 }

$userdata=$_POST[userdata];

if(!$LMS->UserExists($_GET[id]))
	header("Location: ?m=userlist");

if(isset($userdata))
{

	foreach($userdata as $key=>$value)
		$userdata[$key] = trim($value);

	if($userdata[lastname]=="")
		$error[username] = "To pole nie mo¿e byæ puste!";
	
	if($userdata[address]=="")
		$error[address] = "Proszê podaæ adres!";

	if($useradd[nip] !="" &&  !check_nip($useradd[nip])) 
		$error[nip] = "Podany NIP jest b³êdny!";

	if($userdata[zip] !="" && !eregi("^[0-9]{2}-[0-9]{3}$",$userdata[zip]))
		$error[zip] = "Podany kod pocztowy jest b³êdny!";
	
	if($userdata[gguin] !="" && !eregi("^[0-9]{4,}$",$userdata[gguin]))
		$error[gguin] = "Podany numer GG jest niepoprawny!";
	elseif($userdata[gguin] =="") $userdata[gguin] = NULL;

	if(!$LMS->TariffExists($userdata[tariff]))
		$error[tariff] = "Proszê wybraæ taryfê!";
		
	if($userdata[status]!=3&&$LMS->GetUserNodesNo($userdata[id])) 
		$error[status] = "Tylko pod³±czony u¿ytkownik mo¿e posiadaæ komputery!";
		
	if (!isset($error)){
		$LMS->UserUpdate($userdata);
		header("Location: ?m=userinfo&id=".$userdata[id]);
		exit(0);
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

$layout[pagetitle]="Edycja danych u¿ytkownika ".$userinfo[username];
$SMARTY->assign("usernodes",$LMS->GetUserNodes($userinfo[id]));
$SMARTY->assign("balancelist",$LMS->GetUserBalanceList($userinfo[id]));
$SMARTY->assign("tariffs",$LMS->GetTariffs());
$SMARTY->assign("userinfo",$userinfo);
$SMARTY->assign("layout",$layout);
$SMARTY->display("useredit.html");

$_SESSION[backto] = $_SERVER[QUERY_STRING];

?>
