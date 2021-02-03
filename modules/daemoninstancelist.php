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

function GetInstanceList($hostid)
{
    global $DB;

    $where = $hostid ? 'WHERE hostid = '.intval($hostid) : '';
        
    return $DB->GetAll('SELECT daemoninstances.id AS id, daemoninstances.name AS name, daemoninstances.description AS description, 
			module, crontab, priority, disabled, hosts.name AS hostname
			FROM daemoninstances LEFT JOIN hosts ON hosts.id = hostid '
            .$where.
            ' ORDER BY hostname, priority, name');
}

$layout['pagetitle'] = trans('Instances List');

if (!isset($_GET['id'])) {
        $SESSION->restore('dilh', $hostid);
} else {
    $hostid = $_GET['id'];
}
$SESSION->save('dilh', $hostid);
        
$instancelist = GetInstanceList($hostid);

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$SMARTY->assign('instancelist', $instancelist);
$SMARTY->assign('hostid', $hostid);
$SMARTY->assign('hosts', $DB->GetAll('SELECT id, name FROM hosts ORDER BY name'));
$SMARTY->display('daemon/daemoninstancelist.html');
