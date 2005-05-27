<?php

/*
 * LMS version 1.7-cvs
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

function GetInstanceList($hostid)
{
	global $DB;
	$list = $DB->GetAll('SELECT id, name, description, module, crontab, priority, disabled FROM daemoninstances WHERE hostid=? ORDER BY priority, name', array($hostid));
	return $list;
}

$layout['pagetitle'] = trans('Instances List');

$hostid = $_GET['id'];

$instancelist = GetInstanceList($hostid);

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$SMARTY->assign('instancelist', $instancelist);
$SMARTY->assign('hostid', $hostid);
$SMARTY->assign('hosts', $DB->GetAll('SELECT id, name FROM daemonhosts ORDER BY name'));
$SMARTY->display('daemoninstancelist.html');

?>
