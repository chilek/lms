<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2020 LMS Developers
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

if (!$LMS->NetDevExists($id)) {
    $SESSION->redirect('?m=netdevlist');
}

$LMS->InitXajax();
include(MODULES_DIR . DIRECTORY_SEPARATOR . 'netdevxajax.inc.php');
$SMARTY->assign('xajax', $LMS->RunXajax());

if (!isset($_POST['xjxfun'])) {                  // xajax was called and handled by netdevxajax.inc.php
    $netdev = $LMS->GetNetDev($id);
    if (!empty($netdev['ownerid'])) {
        $assignments = $LMS->GetCustomerAssignments($netdev['ownerid'], true, false);
        $assignments = $LMS->GetNetDevCustomerAssignments($id, $assignments);
        $SMARTY->assign(array(
            'assignments' => $assignments,
            'customerinfo' => array(
                'id' => $netdev['ownerid'],
            )
        ));
    }
    $attachmenttype = 'netdevid';
    $attachmentresourceid = $id;
    $SMARTY->assign('attachmenttype', $attachmenttype);
    $SMARTY->assign('attachmentresourceid', $attachmentresourceid);

    $filecontainers = array(
        'netdevid' => array(
            'id' => $id,
            'prefix' => trans('Device attachments'),
            'containers' => $LMS->GetFileContainers('netdevid', $id),
        ),
        'netdevmodelid' => array(
            'id' => intval($netdev['modelid']),
            'prefix' => trans('Model attachments'),
            'containers' => $LMS->GetFileContainers('netdevmodelid', intval($netdev['modelid'])),
        ),
    );
    $SMARTY->assign('filecontainers', $filecontainers);

    include(MODULES_DIR . DIRECTORY_SEPARATOR . 'attachments.php');

    $netdevconnected = $LMS->GetNetDevConnectedNames($id);
    $netcomplist = $LMS->GetNetdevLinkedNodes($id);
    $netdevlist = $LMS->GetNotConnectedDevices($id);

    if ($netdev['ports'] > $netdev['takenports']) {
        $nodelist = $LMS->GetUnlinkedNodes();
    }
    $netdevips = $LMS->GetNetDevIPs($id);

    $SESSION->save('backto', $_SERVER['QUERY_STRING']);

    $layout['pagetitle'] = trans('Device Info: $a $b $c', $netdev['name'], $netdev['producer'], $netdev['model']);

    $netdev['id'] = $id;

    if ($netdev['netnodeid']) {
        $netdev['netnode'] = $LMS->GetNetNode($netdev['netnodeid']);
    }

    $netdev['projectname'] = trans('none');
    if ($netdev['invprojectid']) {
        if ($LMS->GetProjectType($netdev['invprojectid']) == INV_PROJECT_SYSTEM) {
            /* inherited */
            if ($netdev['netnodeid']) {
                $netdev['projectname'] = trans('$a (from network node $b)', $netdev['netnode']['name'], $netdev['netnode']['name']);
            }
        } else {
            $prj = $LMS->GetProject($netdev['invprojectid']);
            if ($prj) {
                $netdev['projectname'] = $prj['name'];
            }
        }
    }

    $queue = $LMS->GetQueueContents(array('netdevids' => $id, 'short'=> 1));

    $start = 0;
    $pagelimit = ConfigHelper::getConfig('phpui.ticketlist_pagelimit', $queue['total']);

    $SMARTY->assign('netdev', $netdev);
    $SMARTY->assign('start', $start);
    $SMARTY->assign('pagelimit', $pagelimit);
    $SMARTY->assign('queue', $queue);
    $SMARTY->assign('queue_count', $queue_count);
    $SMARTY->assign('queue_netdevid', $id);
    $SMARTY->assign('objectid', $netdev['id']);
    $SMARTY->assign('restnetdevlist', $netdevlist);
    $SMARTY->assign('netdevips', $netdevips);
    $SMARTY->assign('nodelist', $nodelist);
    $SMARTY->assign('mgmurls', $LMS->GetManagementUrls(LMSNetDevManager::NETDEV_URL, $id));
    $SMARTY->assign('radiosectors', $LMS->GetRadioSectors($id));
    $SMARTY->assign('devlinktype', $SESSION->get('devlinktype'));
    $SMARTY->assign('devlinktechnology', $SESSION->get('devlinktechnology'));
    $SMARTY->assign('devlinkspeed', $SESSION->get('devlinkspeed'));
    $SMARTY->assign('nodelinktype', $SESSION->get('nodelinktype'));
    $SMARTY->assign('nodelinktechnology', $SESSION->get('nodelinktechnology'));
    $SMARTY->assign('nodelinkspeed', $SESSION->get('nodelinkspeed'));

    $SMARTY->assign(
        'targetnetdevs',
        $DB->GetAll(
            'SELECT n.name, n.id, n.producer, n.model, va.location, n.ports
            FROM netdevices n
            LEFT JOIN vaddresses va ON va.id = n.address_id
            WHERE n.id <> ' . intval($id)
            . ' ORDER BY name'
        )
    );

    $hook_data = $LMS->executeHook(
        'netdevinfo_before_display',
        array(
            'netdevconnected' => $netdevconnected,
            'netcomplist' => $netcomplist,
            'smarty' => $SMARTY,
        )
    );
    $netdevconnected = $hook_data['netdevconnected'];
    $netcomplist = $hook_data['netcomplist'];
    $SMARTY->assign('netdevlist', $netdevconnected);
    $SMARTY->assign('netcomplist', $netcomplist);

    if (isset($_GET['ip'])) {
        $nodeipdata = $LMS->GetNodeConnType($_GET['ip']);
        $netdevauthtype = array();
        $authtype = $nodeipdata;
        if ($authtype != 0) {
            $netdevauthtype['dhcp'] = ($authtype & 2);
            $netdevauthtype['eap'] = ($authtype & 4);
        }
        $SMARTY->assign('nodeipdata', $LMS->GetNode($_GET['ip']));
        $SMARTY->assign('nodesessions', $LMS->GetNodeSessions($_GET['ip']));
        $SMARTY->assign('netdevauthtype', $netdevauthtype);

        $SMARTY->assign('routednetworks', $LMS->getNodeRoutedNetworks($_GET['ip']));
        $SMARTY->assign('notroutednetworks', $LMS->getNodeNotRoutedNetworks($_GET['ip']));
        $SMARTY->assign('nodeid', $_GET['ip']);

        $SMARTY->display('netdev/netdevipinfo.html');
    } else {
        $SMARTY->assign('netdevinfo_sortable_order', $SESSION->get_persistent_setting('netdevinfo-sortable-order'));
        $SMARTY->display('netdev/netdevinfo.html');
    }
}
