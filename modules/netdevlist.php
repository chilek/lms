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

$layout[pagetitle]="Osprzêt sieciowy";

$_SESSION[backto]=$_SERVER[QUERY_STRING];

$SMARTY->assign("layout",$layout);

if(!isset($_GET[o]))
	$o = $_SESSION[ndlo];
else
	$o = $_GET[o];
$_SESSION[ndlo] = $o;

$netdevlist = $LMS->GetNetDevList($o);
//$nodelist = $LMS->GetNodeList($o);
$listdata[total] = $netdevlist[total];
$listdata[order] = $netdevlist[order];
$listdata[direction] = $netdevlist[direction];

unset($netdevlist[total]);
unset($netdevlist[order]);
unset($netdevlist[direction]);

if (isset($_SESSION[nlp]) && !isset($_GET[page]))
        $_GET[page] = $_SESSION[nlp];
	
$page = (! $_GET[page] ? 1 : $_GET[page]);
$pagelimit = (! $LMS->CONFIG[phpui][nodelist_pagelimit] ? $listdata[total] : $LMS->CONFIG[phpui][nodelist_pagelimit]);
$start = ($page - 1) * $pagelimit;

$_SESSION[nlp] = $page;

$SMARTY->assign("page",$page);
$SMARTY->assign("pagelimit",$pagelimit);
$SMARTY->assign("start",$start);
$SMARTY->assign("netdevlist",$netdevlist);
$SMARTY->assign("listdata",$listdata);
$SMARTY->display("netdevlist.html");

/*
 * $Log$
 * Revision 1.5  2003/12/04 04:39:14  lukasz
 * - porz±dki
 * - trochê pod³ubane przy parsowaniu pliku konfiguracyjnego
 *
 * Revision 1.4  2003/10/06 05:33:04  lukasz
 * - temporary save / lot of fixes
 *
 * Revision 1.3  2003/09/12 20:57:05  lexx
 * - netdev
 *
 */
?>
