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

	if($LMS->UserExists($_GET[ownerid]))
	{
		$LMS->NodeSetU($_GET[ownerid],$_GET[access]);
		$backid = $_GET[ownerid];
	}
	if($LMS->NodeExists($_GET[id]))
	{
		$LMS->NodeSet($_GET[id]);
		$backid = $_GET[id];
	}
	if(strstr($_SESSION[backto],"nodelist"))
	    header("Location: ?".$_SESSION[backto]);
	else header("Location: ?".$_SESSION[backto]."#".$backid);

/*
 * $Log$
 * Revision 1.11  2003/09/22 17:31:16  alec
 * naprawiona zmiana statusu komputera przy pomocy ikony zarowki na liscie kompow
 *
 * Revision 1.10  2003/08/27 20:18:42  lukasz
 * - changed nodes.access from ENUM to BOOL;
 *
 * Revision 1.9  2003/08/24 13:12:54  lukasz
 * - massive attack: s/<?/<?php/g - that was causing problems on some fucked
 *   redhat's :>
 *
 * Revision 1.8  2003/08/18 16:52:19  lukasz
 * - added CVS Log tags
 *
 */
?>
