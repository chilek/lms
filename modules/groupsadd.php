<?

/*
 * LMS version 1.0-cvs
 *
 *  (C) Copyright 2002-2003 Rulez Development Team
 *  (C) Copyright 2001-2003 ASK NetX
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
$layout[pagetitle]="Dodawanie GRUPY";

$group = $_POST[group];

if(isset($group))
{
	foreach($group as $key => $value)
		$group[$key] = trim($value);
	$group[id]=$_GET[id];
		

	if($group[g_name] == "")
		$error[name] = "Proszê podaæ nazwê grupy!";
#	elseif($LMS->GetTariffIDByName($group[name]) && $group[name] != $LMS->GetTariffName($_GET[id]))
#		$error[g_name] = "Istnieje ju¿ grupa o takiej nazwie!";	

	if(!$error)
	{
		$LMS->FilterADD($group);
		header("Location: ?m=groupinfo&id=".$group[id]);
		exit(0);
	}

}else
	{
	$gg		= $LMS->GetFilters();
	$group = $gg[$_GET[id]];
	}
	
$SMARTY->assign("layout",$layout);
$SMARTY->assign("group",$group);
$SMARTY->assign("error",$error);
$SMARTY->display("groupsedit.html");
?>
