<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2022 LMS Developers
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
    global $DB;
    $list = $DB->GetAll('SELECT id, var, value, description, disabled
		FROM daemonconfig
		WHERE instanceid = ?
		ORDER BY var', array($instanceid));
    return $list;
}

$instance = $DB->GetRow(
    'SELECT i.id, hosts.id AS hostid, i.name, hosts.name AS hostname
	FROM daemoninstances i, hosts
	WHERE hosts.id = i.hostid AND i.id = ?',
    array($_GET['id'])
);

$layout['pagetitle'] = trans(
    'Configuration of Instance: $a/$b',
    $instance['name'],
    '<A href="?m=daemoninstancelist&id='.$instance['hostid'].'">'.$instance['hostname'].'</A>'
);

$optionlist = GetOptionList($instance['id']);

$SESSION->add_history_entry();

$SMARTY->assign('optionlist', $optionlist);
$SMARTY->assign('instance', $instance);
$SMARTY->display('daemon/daemoninstanceview.html');
