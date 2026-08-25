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

$id = intval($_GET['id']);

$SESSION->add_history_entry();

$result = $LMS->GetNetNode($id);

$layout['pagetitle'] = trans('Net Device Node Info: $a', $result['name']);

if (!$result) {
    $SESSION->redirect('?m=netnodelist');
}

$SMARTY->assign('nodeinfo', $result);
$SMARTY->assign('objectid', $result['id']);

$attachmenttype = 'netnodeid';
$attachmentresourceid = $id;
$SMARTY->assign('attachmenttype', $attachmenttype);
$SMARTY->assign('attachmentresourceid', $attachmentresourceid);

$filecontainers = array(
    'netnodeid' => array(
        'id' => $id,
        'prefix' => trans('Node attachments'),
        'containers' => $LMS->GetFileContainers('netnodeid', $id),
    ),
);
$SMARTY->assign('filecontainers', $filecontainers);

include(MODULES_DIR . DIRECTORY_SEPARATOR . 'attachments.php');

$netdevlist = $DB->GetAll(
    'SELECT d.*, addr.location,
        lb.name AS borough_name, lb.type AS borough_type, lb.ident AS borough_ident,
        ld.name AS district_name, ld.ident AS district_ident,
        ls.name AS state_name, ls.ident AS state_ident
    FROM netdevices d
    LEFT JOIN vaddresses addr       ON d.address_id = addr.id
    LEFT JOIN location_streets lst  ON lst.id = addr.street_id
    LEFT JOIN location_cities lc    ON lc.id = addr.city_id
    LEFT JOIN location_boroughs lb  ON lb.id = lc.boroughid
    LEFT JOIN location_districts ld ON ld.id = lb.districtid
    LEFT JOIN location_states ls    ON ls.id = ld.stateid
    WHERE d.netnodeid = ?
    ORDER BY name',
    array(
        $id
    )
);
if (!empty($netdevlist)) {
    foreach ($netdevlist as &$netdev) {
        if (!$netdev['location'] && $netdev['ownerid']) {
            $netdev['location'] = $LMS->getAddressForCustomerStuff($netdev['ownerid']);
        }
    }
    unset($netdev);
}
$SMARTY->assign('netdevlist', $netdevlist);
$SMARTY->assign('netnodeevents', $LMS->GetEventList(array('netnodeid' => $id)));

$queue = $LMS->GetQueueContents(array('removed' => 0, 'netnodeids' => $id, 'short' => 1));

$SMARTY->assign('queue', $queue);

$start = 0;
$pagelimit = ConfigHelper::getConfig(
    'rt.ticketlist_pagelimit',
    ConfigHelper::getConfig('phpui.ticketlist_pagelimit', empty($queue) ? -1 : count($queue))
);
$SMARTY->assign('start', $start);
$SMARTY->assign('pagelimit', $pagelimit);

$foreign_entities = Utils::getForeignEntities();
if (!empty($result['coowner']) && !empty($foreign_entities[$result['coowner']])) {
    $SMARTY->assign('foreign_entity', $foreign_entities[$result['coowner']]);
}

$SMARTY->assign('netnodeinfo_sortable_order', $SESSION->get_persistent_setting('netnodeinfo-sortable-order'));
$SMARTY->display('netnode/netnodeinfo.html');
