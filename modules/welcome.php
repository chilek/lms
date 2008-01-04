<?php

/*
 * LMS version 1.10-cvs
 *
 *  (C) Copyright 2001-2008 LMS Developers
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

require_once(LIB_DIR.'/Sysinfo.class.php');
@include(LIB_DIR.'/locale/'.$_language.'/fortunes.php');

$SI = new Sysinfo;

$layout['pagetitle'] = 'LAN Management System';

$layout['dbversion'] = $DB->GetDBVersion();
$layout['dbtype'] = $CONFIG['database']['type'];

$content = $LMS->CheckUpdates();

if(isset($content['newer_version']))
{
	list($v, ) = split(' ', $LMS->_version);

	if(version_compare($content['newer_version'], $v)>0)
		$SMARTY->assign('newer_version', $content['newer_version']);
}

$SMARTY->assign('regdata', $LMS->GetRegisterData());
$SMARTY->assign('rtstats', $LMS->RTStats());
$SMARTY->assign('sysinfo',$SI->get_sysinfo());
$SMARTY->assign('customerstats',$LMS->CustomerStats());
$SMARTY->assign('nodestats',$LMS->NodeStats());
$SMARTY->display('welcome.html');

?>
