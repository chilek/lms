<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2013 LMS Developers
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

@include(LIB_DIR.'/locale/'.$_language.'/fortunes.php');

$layout['pagetitle'] = 'LAN Management System';

$layout['dbversion'] = $DB->GetDBVersion();
$layout['dbtype'] = $CONFIG['database']['type'];

if (check_conf('privileges.superuser')) {
    $content = $LMS->CheckUpdates();

    if(isset($content['newer_version'])) {
    	list($v, ) = preg_split('/\s+/', $LMS->_version);

	    if(version_compare($content['newer_version'], $v) > 0)
		    $SMARTY->assign('newer_version', $content['newer_version']);
    }

	$SMARTY->assign('regdata', $LMS->GetRegisterData());
}

$SMARTY->assign('_dochref', is_dir('doc/html/'.$LMS->ui_lang) ? 'doc/html/'.$LMS->ui_lang.'/' : 'doc/html/en/');
$SMARTY->assign('rtstats', $LMS->RTStats());

if (!check_conf('privileges.hide_sysinfo')) {
	require_once LIB_DIR.'/Sysinfo.class.php';
	$SI = new Sysinfo;
	$SMARTY->assign('sysinfo', $SI->get_sysinfo());
}

if (!check_conf('privileges.hide_summaries')) {
	$SMARTY->assign('customerstats', $LMS->CustomerStats());
	$SMARTY->assign('nodestats', $LMS->NodeStats());
}

$SMARTY->display('welcome.html');

?>
