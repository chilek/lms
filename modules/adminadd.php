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

$adminadd = $_POST[adminadd];
$acl = $_POST[acl];

if(isset($adminadd))
{
	foreach($adminadd as $key => $value)
		$adminadd[$key] = trim($value);
	
	if($adminadd[login]==""&&$adminadd[name]==""&&$adminadd[password]==""&&$adminadd[confirm]=="")
	{
		header("Location: ?m=adminadd");
		exit(0);
	}
	
	if($LMS->GetAdminIDByLogin($adminadd[login]))
		$error[login] = $lang[error_login_already_exists];
	elseif(!eregi("^[a-z0-9.-_]+$",$adminadd[login]))
		$error[login] = $lang[error_login_contains_incorrect_characters];

	if($adminadd[email]!="" && !check_email($adminadd[email]))
		$error[email] = $lang[error_email_incorrect];

	if($adminadd[password]=="")
		$error[password] = $lang[error_passwd_mismatch];
	elseif($adminadd[password]!=$adminadd[confirm])
		$error[password] = $lang[error_passwd_mismatch];

	// zróbmy maskê ACL...

	for($i=0;$i<256;$i++)
		$mask .= "0";

	foreach($access[table] as $idx => $row)
		if($acl[$idx]=="1")
			$mask[255-$idx] = "1";

	for($i=0;$i<256;$i += 4)
		$outmask = $outmask . dechex(bindec(substr($mask,$i,4)));

	$adminadd[rights] = ereg_replace('^[0]*(.*)$','\1',$outmask);

	if(!$error)
	{
		header("Location: ?m=admininfo&id=".$LMS->AdminAdd($adminadd));
		exit(0);
	}
}
foreach($access[table] as $idx => $row)
{
	$row[id] = $idx;
	if($acl[$idx] == "1")
		$row[enabled] = TRUE;
	$accesslist[] = $row;
}

$layout[pagetitle]=$lang[pagetitle_adminadd];
$SMARTY->assign("layout",$layout);
$SMARTY->assign("adminadd",$adminadd);
$SMARTY->assign("error",$error);
$SMARTY->assign("accesslist",$accesslist);
$SMARTY->display("adminadd.html");

?>
