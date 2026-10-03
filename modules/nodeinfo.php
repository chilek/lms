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

if (isset($_GET['nodegroups'])) {
    $nodegroups = $LMS->GetNodeGroupNamesByNode(intval($_GET['id']));

    $SMARTY->assign('nodegroups', $nodegroups);
    $SMARTY->assign('total', count($nodegroups));
    $SMARTY->display('node/nodegrouplistshort.html');
    die;
}

if (!preg_match('/^[0-9]+$/', $_GET['id'])) {
    $SESSION->redirect('?m=nodelist');
} else {
    $nodeid = $_GET['id'];
}

if (!$LMS->NodeExists($nodeid)) {
    if (isset($_GET['ownerid'])) {
        $SESSION->redirect('?m=customerinfo&id=' . $_GET['ownerid']);
    } else if ($DB->GetOne('SELECT 1 FROM vnodes WHERE id = ? AND ownerid IS NULL', array($nodeid))) {
        $SESSION->redirect('?m=netdevinfo&ip=' . $nodeid . '&id=' . $LMS->GetNetDevIDByNode($nodeid));
    } else {
        $SESSION->redirect('?m=nodelist');
    }
}

if (isset($_GET['devid'])) {
    $error['netdev'] = trans('It scans for free ports in selected device!');
    $SMARTY->assign('error', $error);
    $SMARTY->assign('netdevice', $_GET['devid']);
}

$nodeinfo = $LMS->GetNode($nodeid);

$params = [
    'nodeids' => $nodeid,
    'short' => true,
];

$alltickets = !empty($_GET['alltickets']) ?: ConfigHelper::checkConfig(
    'rt.default_show_closed_tickets',
    ConfigHelper::checkConfig('phpui.default_show_closed_tickets')
);

if (!$alltickets) {
    $params['state'] = -1;
}

$nodeticketlist = $LMS->GetQueueContents($params);

$node_empty_mac = ConfigHelper::getConfig('nodes.empty_mac', ConfigHelper::getConfig('phpui.node_empty_mac', '', true));
if (strlen($node_empty_mac)) {
    $node_empty_mac = Utils::normalizeMac($node_empty_mac);
    $nodeinfo['macs'] = array_filter($nodeinfo['macs'], function ($mac) use ($node_empty_mac) {
        return $mac['mac'] != $node_empty_mac;
    });
}

if (!isset($resource_tabs['nodegroups']) || $resource_tabs['nodegroups']) {
    $nodegroups = $LMS->GetNodeGroupNamesByNode($nodeid);
    $othernodegroups = $LMS->GetNodeGroupNamesWithoutNode($nodeid);
}
$customerid = $nodeinfo['ownerid'];

include(MODULES_DIR . DIRECTORY_SEPARATOR . 'customer.inc.php');
require_once(LIB_DIR . DIRECTORY_SEPARATOR . 'customerconsents.php');
require_once(LIB_DIR . DIRECTORY_SEPARATOR . 'customercontacttypes.php');

if (!isset($resource_tabs['nodeassignments']) || $resource_tabs['nodeassignments']) {
    $nodeassignments = array();
    if (!empty($customernodes) && !empty($assignments)) {
        foreach ($customernodes as $node) {
            $assigns = $LMS->GetNodeCustomerAssignments($node['id'], $assignments);
            if (!empty($assigns)) {
                $nodeassignments[$node['id']] = $assigns[$node['id']];
            }
        }
    }
    $SMARTY->assign('nodeassignments', $nodeassignments);
}

$SESSION->add_history_entry();

if (!isset($_GET['ownerid'])) {
    $SESSION->add_history_entry($SESSION->remove_history_entry() . '&ownerid=' . $customerid);
}

if ($nodeinfo['netdev'] == 0) {
    $netdevices = $LMS->GetNetDevNames();
} else {
    $netdevices = $LMS->GetNetDev($nodeinfo['netdev']);
}

$layout['pagetitle'] = trans('Node Info: $a', $nodeinfo['name']);

$nodeinfo['projectname'] = trans('none');
if ($nodeinfo['invprojectid']) {
    $prj = $LMS->GetProject($nodeinfo['invprojectid']);
    if ($prj) {
        if ($prj['type'] == INV_PROJECT_SYSTEM && intval($prj['id']==1)) {
            /* inherited */
            if ($nodeinfo['netdev']) {
                $prj = $LMS->GetProject($netdevices['invprojectid']);
                if ($prj) {
                    if ($prj['type'] == INV_PROJECT_SYSTEM && intval($prj['id'])==1) {
                        /* inherited */
                        if ($netdevices['netnodeid']) {
                            $prj = $DB->GetRow(
                                "SELECT p.*, n.name AS nodename FROM invprojects p
								JOIN netnodes n ON n.invprojectid = p.id
								WHERE n.id=?",
                                array($netdevices['netnodeid'])
                            );
                            if ($prj) {
                                $nodeinfo['projectname'] = trans('$a (from network node $b)', $prj['name'], $prj['nodename']);
                            }
                        }
                    } else {
                        $nodeinfo['projectname'] = trans('$a (from network device $b)', $prj['name'], $netdevices['name']);
                    }
                }
            }
        } else {
            $nodeinfo['projectname'] = $prj['name'];
        }
    }
}
$nodeauthtype = array();
$authtype = $nodeinfo['authtype'];
if ($authtype != 0) {
    $nodeauthtype['pppoe'] = ($authtype & 1);
    $nodeauthtype['dhcp'] = ($authtype & 2);
    $nodeauthtype['eap'] = ($authtype & 4);
}

if (!isset($resource_tabs['nodesessions']) || $resource_tabs['nodesessions']) {
    $nodesessions = $LMS->GetNodeSessions($nodeid);
    $SMARTY->assign('nodesessions', $nodesessions);
}

$LMS->InitXajax();
include(MODULES_DIR . DIRECTORY_SEPARATOR . 'nodexajax.inc.php');

$nodeinfo = $LMS->ExecHook('node_info_init', $nodeinfo);

$hook_data = $LMS->executeHook(
    'nodeinfo_before_display',
    array(
        'nodeinfo' => $nodeinfo,
        'smarty' => $SMARTY,
    )
);
$nodeinfo = $hook_data['nodeinfo'];

$SMARTY->assign('xajax', $LMS->RunXajax());

$SMARTY->assign(array(
    'linktype' => intval(ConfigHelper::getConfig('phpui.default_linktype', LINKTYPE_WIRE)),
    'linktechnology' => intval(ConfigHelper::getConfig('phpui.default_linktechnology', 0)),
    'linkspeed' => intval(ConfigHelper::getConfig('phpui.default_linkspeed', 100000)),
));

$SMARTY->assign('netdevices', $netdevices);
$SMARTY->assign('nodeauthtype', $nodeauthtype);
if (!isset($resource_tabs['nodegroups']) || $resource_tabs['nodegroups']) {
    $SMARTY->assign('nodegroups', $nodegroups);
    $SMARTY->assign('othernodegroups', $othernodegroups);
}
if (!isset($resource_tabs['managementurls']) || $resource_tabs['managementurls']) {
    $SMARTY->assign('mgmurls', $LMS->GetManagementUrls(LMSNetDevManager::NODE_URL, $nodeinfo['id']));
}

if (!isset($resource_tabs['routednetworks']) || $resource_tabs['routednetworks']) {
    $SMARTY->assign('routednetworks', $LMS->getNodeRoutedNetworks($nodeinfo['id']));
    $SMARTY->assign('notroutednetworks', $LMS->getNodeNotRoutedNetworks($nodeinfo['id']));
    $SMARTY->assign('nodeid', $nodeinfo['id']);
}

if (!isset($resource_tabs['nodetickets']) || $resource_tabs['nodetickets']) {
    $SMARTY->assign(['nodeticketlist' => $nodeticketlist, 'alltickets' => $alltickets]);
}

$SMARTY->assign('nodeinfo', $nodeinfo);
$SMARTY->assign('objectid', $nodeinfo['id']);
$SMARTY->assign('nodeinfo_sortable_order', $SESSION->get_persistent_setting('nodeinfo-sortable-order'));
$SMARTY->display('node/nodeinfo.html');
