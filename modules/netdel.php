<?php

/*
 * LMS version 1.4-cvs
 *
 *  (C) Copyright 2001-2005 LMS Developers
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

if(!$LMS->NetworkExists($_GET['id']))
{
	header("Location: ?m=netlist");
	die;
}

$network = $LMS->GetNetworkRecord($_GET['id']);
$networks = $LMS->GetNetworks();

if($network['assigned'])
	$error['delete'] = TRUE;

if(!$error)
{
	if($_GET['is_sure'])
	{
		$LMS->NetworkDelete($network['id']);
		header("Location: ?m=".$_SESSION['lastmodule']."&id=".$_GET['id']);
		die;
	}else{
		$layout['pagetitle'] = "Usuniêcie sieci ".strtoupper($network['name']);
		$SMARTY->display('header.html');
		echo "<H1>Usuniêcie sieci ".strtoupper($network['name'])."</H1>";
		echo "<p>Czy jeste¶ pewien ¿e chcesz usun±æ t± sieæ?</p>";
		echo "<a href=\"?m=netdel&id=".$network['id']."&is_sure=1\">Tak, jestem pewien</A>";
		$SMARTY->display('footer.html');
	}
}else{
	$layout['pagetitle'] = "Informacja o sieci";
	$SMARTY->assign('network',$network);
	$SMARTY->assign('networks',$networks);
	$SMARTY->assign('error',$error);
	$SMARTY->display('netinfo.html');
}

?>