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

$netdevinfo = $LMS->GetNetDev($_GET[id]);

$_SESSION[backto] = $_SERVER[QUERY_STRING];

$layout[pagetitle]="Informacje o urz±dzeniu: ".$netdevinfo[name]." ".$netdevinfo[producer]." ".$netdevinfo[model];

$netdevinfo[id] = $_GET[id];
$netdevinfo[takenports] = 'Narazie nie policze ;)';
$SMARTY->assign("netdevinfo",$netdevinfo);
$SMARTY->assign("layout",$layout);
$SMARTY->display("netdevinfo.html");

/*
 * $Log$
 * Revision 1.1  2003/09/12 20:57:05  lexx
 * - netdev
 *
 */
?>
