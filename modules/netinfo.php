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

if(!$LMS->NetworkExists($_GET[id]))
{
	header("Location: ?m=netlist");
	die;
}

if (isset($_SESSION[ntlp][$_GET[id]]) && !isset($_GET[page]))
	$_GET[page] = $_SESSION[ntlp][$_GET[id]];

$_SESSION[ntlp][$_GET[id]] = $_GET[page];

$network = $LMS->GetNetworkRecord($_GET[id],$_GET[page],1024);

$layout[pagetitle]="Informacja o sieci: ".$network[name];

$SMARTY->assign("layout",$layout);
$SMARTY->assign("network",$network);
$SMARTY->display("netinfo.html");
/*
 * $Log$
 * Revision 1.22  2003/10/03 15:59:46  alec
 * ujednolicenie interfejsu
 *
 * Revision 1.21  2003/09/09 01:22:28  lukasz
 * - nowe finanse
 * - kosmetyka
 * - bugfixy
 * - i inne rzeczy o których aktualnie nie pamiêtam
 *
 * Revision 1.20  2003/08/27 19:25:47  lukasz
 * - changed format of ipaddr storage in database
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
