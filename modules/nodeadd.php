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

$nodedata = $_POST[nodedata];

$users = $LMS->GetUserNames();

if(isset($nodedata))
{
	$nodedata[ipaddr] = $_POST[nodedataipaddr];
	$nodedata[mac] = $_POST[nodedatamac];
	$nodedata[mac] = str_replace("-",":",$nodedata[mac]);

	foreach($nodedata as $key => $value)
		$nodedata[$key] = trim($value);

	if($nodedata[ipaddr]=="" && $nodedata[mac]=="" && $nodedata[name]=="")
		if($_GET[ownerid])
		{
			header("Location: ?m=userinfo&id=".$_GET[ownerid]);
			exit(0);
		}else{
			header("Location: ?m=nodelist");
			exit(0);
		}
	
	if($nodedata[name]=="")
		$error[name] = $lang[error_no_empty_field];
	elseif(strlen($nodedata[name]) > 16)
		$error[name] = $lang[error_field_too_long];
	elseif($LMS->GetNodeIDByName($nodedata[name]))
		$error[name] = $lang[error_field_already_exists];
	elseif(!eregi("^[_a-z0-9-]+$",$nodedata[name]))
		$error[name] = $lang[error_field_contains_incorrect_characters];
		
	if(!$nodedata[ipaddr])
		$error[ipaddr] = $lang[error_no_empty_field];
	elseif(!check_ip($nodedata[ipaddr]))
		$error[ipaddr] = $lang[error_ip_address_invalid];
	elseif(!$LMS->IsIPValid($nodedata[ipaddr]))
		$error[ipaddr] = $lang[error_ip_address_is_not_in_any_net];
	elseif(!$LMS->IsIPFree($nodedata[ipaddr]))
		$error[ipaddr] = $lang[error_ip_address_is_already_in_use];

	if($LMS->GetNodeIDByMAC($nodedata[mac]) && $_CONFIG[phpui][allow_mac_sharing] == FALSE)
		$error[mac] = $lang[error_mac_already_exists];
	elseif(!check_mac($nodedata[mac]))
		$error[mac] = $lang[error_mac_address_invalid];

	if(!$LMS->UserExists($nodedata[ownerid]))
		$error[user] = $lang[error_choose_user];

	if($LMS->GetUserStatus($nodedata[ownerid]) != 3)
		$error[user] = $lang[error_user_invalid];

	if(!$error)
	{
		$nodeid=$LMS->NodeAdd($nodedata);
		header("Location: ?m=nodeinfo&id=".$nodeid);
		exit(0);
	}
		
}

if($_GET[ownerid]&&$LMS->UserExists($_GET[ownerid]))
{
	$nodedata[ownerid] = $_GET[ownerid];
	$userinfo = $LMS->GetUser($_GET[ownerid]);
}

if(isset($_GET[preip])&&$nodedata[ipaddr]=="")
	$nodedata[ipaddr] = $_GET[preip];

if(isset($_GET[premac])&&$nodedata[mac]=="")
	$nodedata[mac] = $_GET[premac];

if(isset($_GET[prename])&&$nodedata[name]=="")
	$nodedata[name] = $_GET[prename];
		

$layout[pagetitle] = $lang[pagetitle_nodeadd];

$SMARTY->assign("balancelist",$LMS->GetUserBalanceList($nodedata[ownerid]));
$SMARTY->assign("users",$users);
$SMARTY->assign("error",$error);
$SMARTY->assign("userinfo",$userinfo);
$SMARTY->assign("nodedata",$nodedata);
$SMARTY->assign("layout",$layout);

$SMARTY->display("nodeadd.html");

?>
