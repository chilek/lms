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
	$admininfo[id] = $_GET[id];
	
	foreach($admininfo as $key => $value)
		$admininfo[$key] = trim($value);

	if($LMS->GetAdminIDByLogin($admininfo[login]) && $LMS->GetAdminIDByLogin($admininfo[login]) != $_GET[id])
		$error[login] = "Podany login jest ju¿ zajêty!";

	if($admininfo[login] == "")
		$error[login] = "To pole nie mo¿e byæ puste!";

	if($admininfo[name] == "")
		$error[name] = "To pole nie mo¿e byæ puste!";

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

$SMARTY->assign("layout",$layout);
$SMARTY->assign("admininfo",$admininfo);
$SMARTY->assign("unlockedit",TRUE);
$SMARTY->assign("error",$error);
$SMARTY->display("admininfo.html");
?>
