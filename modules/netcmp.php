<?php

/*
 * LMS version 1.5-cvs
 *
 *  (C) Copyright 2001-2004 LMS Developers
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
	header('Location: ?m=netlist');
	die;
}

$network['name'] = $LMS->GetNetworkName($_GET['id']);

if($_GET['is_sure'])
{
	$LMS->NetworkCompress($_GET['id']);
	header('Location: ?m='.$_SESSION['lastmodule'].'&id='.$_GET['id']);
	die;
}else{
	$layout['pagetitle'] = sprintf(trans('Readdressing network %s'),strtoupper($network['name']));
	$SMARTY->display('header.html');
	echo '<H1>'.sprintf(trans('Readdressing network %s'),strtoupper($network['name'])).'</H1>';
	echo '<P>'.trans('Are you shure, you want to reorder that network?').'</P>';
	echo '<A href="?m=netcmp&id='.$_GET['id'].'&is_sure=1">'.trans('Yes, I am sure').'</A>';
	$SMARTY->display('footer.html');
}

?>