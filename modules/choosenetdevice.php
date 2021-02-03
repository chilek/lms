<?php

/*
 *  LMS version 1.11-git
 *
 *  Copyright (C) 2001-2016 LMS Developers
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

if (!$p || $p == 'main') {
    $SMARTY->assign('js', 'var targetfield = window.parent.targetfield;');
}

if (isset($_GET['netdevid'])) {
    $netdevid = $_GET['netdevid'];
}

if (isset($_POST['searchnetdev']) && $_POST['searchnetdev']) {
    $search = $_POST['searchnetdev'];

    $netdevices = $DB->GetAll('
		SELECT n.id, n.name, va.location, n.producer, n.ports, n.ownerid, n.address_id
		FROM netdevices n
			LEFT JOIN vaddresses va ON n.address_id = va.id
		WHERE (n.name ?LIKE? '.$DB->Escape('%'.$search.'%').' OR va.location ?LIKE? '.$DB->Escape('%'.$search.'%').' OR n.producer ?LIKE? '.$DB->Escape('%'.$search.'%').')
			' . (isset($netdevid) ? ' AND n.id <> ' . intval($netdevid)
            . ' AND NOT EXISTS (SELECT n.id FROM netlinks WHERE (n.id = dst AND src = ' . intval($netdevid) . ')
					OR (n.id = src AND dst = ' . intval($netdevid) . '))'
            : '') . '
		ORDER BY n.name');

    if ($netdevices) {
        foreach ($netdevices as $k => $nd) {
            $netdevices[$k]['ports'] = $nd['ports'] - $LMS->CountNetDevLinks($nd['id']);

            if (!empty($nd['ownerid']) && empty($nd['address_id'])) {
                $netdevices[$k]['location'] = $LMS->getAddressForCustomerStuff($nd['ownerid']);
            }
        }
    }

    $SMARTY->assign('searchnetdev', $search);
    $SMARTY->assign('netdevices', $netdevices);
}

if (isset($netdevid)) {
    $SMARTY->assign('netdevid', $netdevid);
}
$SMARTY->assign('part', $p);
$SMARTY->display('choose/choosenetdevice.html');
