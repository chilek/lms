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

if(!$LMS->AdminExists($_GET[id]))
{
	header("Location: ?m=adminlist");
	exit(0);
}

$admininfo=$_POST[admininfo];

if(isset($admininfo))
{
	$acl = $_POST[acl];
	$admininfo[id] = $_GET[id];
	
	foreach($admininfo as $key => $value)
		$admininfo[$key] = trim($value);

	if($LMS->GetAdminIDByLogin($admininfo[login]) && $LMS->GetAdminIDByLogin($admininfo[login]) != $_GET[id])
		$error[login] = "Podany login jest ju� zaj�ty!";

	if($admininfo[login] == "")
		$error[login] = "To pole nie mo�e by� puste!";

	if($admininfo[name] == "")
		$error[name] = "To pole nie mo�e by� puste!";

	if($admininfo[email]!="" && !check_email($admininfo[email]))
		$error[email] = "Podany email nie wydaje si� by� poprawny!";
				

	// zr�bmy mask� ACL...

	for($i=0;$i<256;$i++)
		$mask .= "0";
	
	foreach($access[table] as $idx => $row)
		if($acl[$idx]=="1")
			$mask[255-$idx] = "1";
	for($i=0;$i<256;$i += 4)
		$outmask = $outmask . dechex(bindec(substr($mask,$i,4)));

	$admininfo[rights] = ereg_replace('^[0]*(.*)$','\1',$outmask);

	if(!$error)
	{
		$LMS->AdminUpdate($admininfo);
		header("Location: ?m=admininfo&id=".$admininfo[id]);
		exit(0);
	}

}

foreach($LMS->GetAdminInfo($_GET[id]) as $key => $value)
	if(!isset($admininfo[$key]))
		$admininfo[$key] = $value;

$layout[pagetitle]="Edycja danych administratora ".$LMS->GetAdminName($_GET[id]);

$rights = $LMS->GetAdminRights($_GET[id]);

foreach($access[table] as $idx => $row)
{
	$row[id] = $idx;
	foreach($rights as $right)
		if($right == $idx)
			$row[enabled]=TRUE;
	$accesslist[] = $row;
}
$SMARTY->assign("layout",$layout);
$SMARTY->assign("accesslist",$accesslist);
$SMARTY->assign("admininfo",$admininfo);
$SMARTY->assign("unlockedit",TRUE);
$SMARTY->assign("error",$error);
$SMARTY->display("admininfo.html");
?>
