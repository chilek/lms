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

	if($userdata[lastname]=="")
		$error[username] = $lang[error_no_empty_filed];
	
	if($userdata[address]=="")
		$error[address] = $lang[error_no_empty_filed];

	if($useradd[nip] !="" && !eregi("^[0-9]{3}-[0-9]{3}-[0-9]{2}-[0-9]{2}$",$useradd[nip]) && !eregi("^[0-9]{3}-[0-9]{2}-[0-9]{2}-[0-9]{3}$",$useradd[nip]) && !check_nip($useradd[nip]))
		$error[nip] = $lang[error_taxid_invalid];

	if($userdata[zip] !="" && !eregi("^[0-9]{2}-[0-9]{3}$",$userdata[zip]))
		$error[zip] = $lang[error_zip_invalid];

	if($userdata[gguin] == 0)
		unset($userdata[gguin]);

	if($userdata[gguin] !="" && !eregi("^[0-9]{4,}$",$userdata[gguin]))
		$error[gguin] = $lang[error_gguin_invalid];

	if(!$LMS->TariffExists($userdata[tariff]))
		$error[tariff] = $lang[error_choose_tariff];
		
	if($userdata[status]!=3&&$LMS->GetUserNodesNo($userdata[id])) 
		$error[status] = $lang[error_only_connected_users_can_have_nodes];
		
	if (!isset($error)){
		$LMS->UserUpdate($userdata);
		header("Location: ?m=userinfo&id=".$userdata[id]);
		exit(0);
	}else{
		$olddata=$LMS->GetUser($_GET[id]);
		$userinfo=$userdata;
		$userinfo[createdby] = $olddata[createdby];
		$userinfo[modifiedby] = $olddata[modifiedby];
		$userinfo[creationdateh] = $olddata[creationdateh];
		$userinfo[moddateh] = $olddata[moddateh];
		$userinfo[username] = $olddata[username];
		$userinfo[balance] = $olddata[balance];
		if($olddata[status]==3)
			$userinfo[shownodes] = TRUE;
		$SMARTY->assign("error",$error);
	}
}else{

	$userinfo=$LMS->GetUser($_GET[id]);
	if($userinfo[status]==3)
		$userinfo[shownodes] = TRUE;
}

$layout[pagetitle] = sprintf($lang[pagetitle_useredit],$userinfo[username]);
$SMARTY->assign("usernodes",$LMS->GetUserNodes($userinfo[id]));
$SMARTY->assign("balancelist",$LMS->GetUserBalanceList($userinfo[id]));
$SMARTY->assign("tariffs",$LMS->GetTariffs());
$SMARTY->assign("userinfo",$userinfo);
$SMARTY->assign("layout",$layout);
$SMARTY->display("useredit.html");

$_SESSION[backto] = $_SERVER[QUERY_STRING];

?>
