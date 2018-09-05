<?php

/*
 *  LMS version 1.11-git
 *
 *  Copyright (C) 2001-2017 LMS Developers
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

$layout['pagetitle'] = trans('Select netdevice');

$p = isset($_GET['p']) ? $_GET['p'] : '';

if (!$p || $p == 'main')
    $SMARTY->assign('js', 'var targetfield = window.parent.targetfield;');

if (isset($_POST['searchnodedev']) && $_POST['searchnodedev']) {
    $search = $_POST['searchnodedev'];
    $netdevices = $DB->GetAll('SELECT
											n.id, n.name, n.lastonline, inet_ntoa(n.ipaddr) as ipaddr, inet_ntoa(n.ipaddr_pub) as ipaddr_pub,
											c.name as customername, c.lastname, c.street, c.building, c. apartment
										FROM
											nodes n
										LEFT JOIN customerview c ON n.ownerid = c.id
										WHERE
											(n.name ?LIKE? ' . $DB->Escape('%'.$search.'%') . ' OR
											inet_ntoa(n.ipaddr) ?LIKE? ' . $DB->Escape('%'.$search.'%') . ' OR
											inet_ntoa(n.ipaddr_pub) ?LIKE? ' . $DB->Escape('%'.$search.'%') . ')
											AND n.netdev IS NULL
										ORDER BY
											n.name');

    $SMARTY->assign('searchnodedev', $search);
    $SMARTY->assign('netdevices', $netdevices);
}

$SMARTY->assign('part', $p);
$SMARTY->display('choose/choosenodedevice.html');

?>