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

if(!$LMS->AdminExists($_GET[id]))
{
	header("Location: ?m=adminlist");
	die;
}

$admininfo=$_POST[admininfo];

if(isset($admininfo))
{
	$acl = $_POST[acl];
	$admininfo[id] = $_GET[id];
	
	foreach($admininfo as $key => $value)
		$admininfo[$key] = trim($value);

	if($LMS->GetAdminIDByLogin($admininfo[login]) && $LMS->GetAdminIDByLogin($admininfo[login]) != $_GET[id])
		$error[login] = "Podany login jest ju¿ zajêty!";

	if($admininfo[login] == "")
		$error[login] = "Pole login nie mo¿e byæ puste!";
	elseif(!eregi("^[a-z0-9.-_]+$",$admininfo[login]))
		$error[login] = "Login zawiera niepoprawne znaki!";

	if($admininfo[name] == "")
		$error[name] = "Pole 'imiê i nazwisko' nie mo¿e byæ puste!";

	if($admininfo[email]!="" && !check_email($admininfo[email]))
		$error[email] = "Podany email nie wydaje siê byæ poprawny!";
				

	// zróbmy maskê ACL...

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
		die;
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
$SMARTY->assign("layout",$layout);
$SMARTY->display("admininfo.html");
/*
 * $Log$
 * Revision 1.21  2003/09/15 10:48:45  lukasz
 * - http://bts.rulez.pl/bug_view_page.php?bug_id=0000072
 *
 * Revision 1.20  2003/09/05 13:11:23  lukasz
 * - nowy sposób wy¶wietlania informacji o b³êdach
 *
 * Revision 1.19  2003/08/24 13:12:54  lukasz
 * - massive attack: s/<?/<?php/g - that was causing problems on some fucked
 *   redhat's :>
 *
 * Revision 1.18  2003/08/18 16:52:19  lukasz
 * - added CVS Log tags
 *
 */
?>
