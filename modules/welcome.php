<?php

/*
 * LMS version 1.5-cvs
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

require_once($_LIB_DIR.'/Sysinfo.class.php');
@include($_LIB_DIR.'/locale/'.$_language.'/fortunes.php');

$SI = new Sysinfo;

$layout['pagetitle'] = 'LAN Management System';

$layout['dbversion'] = $LMS->DB->GetDBVersion();
$layout['dbtype'] = $LMS->CONFIG['database']['type'];

$SMARTY->assign('rtstats', $LMS->RTStats());
$SMARTY->assign('sysinfo',$SI->get_sysinfo());
$SMARTY->assign('userstats',$LMS->UserStats());
$SMARTY->assign('nodestats',$LMS->NodeStats());
$SMARTY->display('welcome.html');

?>
