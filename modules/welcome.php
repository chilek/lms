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
require_once($_LIB_DIR."/Sysinfo.class.php");
require_once($_LIB_DIR."/fortunes.php");

$SI = new Sysinfo;

$layout[pagetitle]="Witamy w LMS :-) !";
$SMARTY->assign("sysinfo",$SI->get_sysinfo());
$SMARTY->assign("userstats",$LMS->UserStats());
$SMARTY->assign("nodestats",$LMS->NodeStats());
$SMARTY->assign("layout",$layout);
$SMARTY->display("welcome.html");
/*
 * $Log$
 * Revision 1.27  2003/09/24 22:33:54  lukasz
 * - s/TipOfTheDay/fortunes/g
 *
 * Revision 1.26  2003/08/28 11:36:47  lukasz
 * - http://bts.rulez.pl/bug_view_page.php?bug_id=0000057
 *
 * Revision 1.25  2003/08/24 13:12:54  lukasz
 * - massive attack: s/<?/<?php/g - that was causing problems on some fucked
 *   redhat's :>
 *
 * Revision 1.24  2003/08/18 16:57:00  lukasz
 * - more cvs tags :>
 *
 * Revision 1.23  2003/08/18 16:52:19  lukasz
 * - added CVS Log tags
 *
 */
?>
