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

function GetOptionList($instanceid)
{
	global $LMS;
	$list = $DB->GetAll('SELECT id, var, value, description, disabled FROM daemonconfig WHERE instanceid=? ORDER BY var', array($instanceid));
	return $list;
}

$instance = $DB->GetRow('SELECT daemoninstances.id AS id, daemonhosts.id AS hostid, daemoninstances.name AS name, daemonhosts.name AS hostname FROM daemoninstances, daemonhosts WHERE daemonhosts.id=hostid AND daemoninstances.id=?', array($_GET['id']));

$layout['pagetitle'] = trans('Configuration of Instance: $0/$1', $instance['name'], '<A href="?m=daemoninstancelist&id='.$instance['hostid'].'">'.$instance['hostname'].'</A>');

$optionlist = GetOptionList($instance['id']);

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$SMARTY->assign('optionlist', $optionlist);
$SMARTY->assign('instance', $instance);
$SMARTY->display('daemoninstanceview.html');

?>
