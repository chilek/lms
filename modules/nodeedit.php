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

$action = $_GET['action'] ?? '';

if (!$LMS->NodeExists($_GET['id'])) {
    if (isset($_GET['ownerid'])) {
        header('Location: ?m=customerinfo&id=' . $_GET['ownerid']);
    } else {
        header('Location: ?m=nodelist');
    }
}

$nodeid = intval($_GET['id']);
$customerid = $LMS->GetNodeOwner($nodeid);

switch ($action) {
    case 'updatenodefield':
        $LMS->updateNodeField($_POST['nodeid'], $_POST['field'], $_POST['val']);
        die();
    break;

    case 'link':
        if (empty($_GET['devid']) || !($netdev = $LMS->GetNetDev($_GET['devid']))) {
            $SESSION->redirect('?m=nodeinfo&id=' . $nodeid);
        } else if ($netdev['ports'] > $netdev['takenports']) {
            $LMS->NetDevLinkNode($nodeid, $_GET['devid'], array(
                'type' => isset($_GET['linktype']) ? intval($_GET['linktype']) : 0,
                'technology' => isset($_GET['linktechnology']) ? intval($_GET['linktechnology']) : 0,
                'speed' => isset($_GET['linkspeed']) ? intval($_GET['linkspeed']) : 100000,
                'port' => intval($_GET['port']),
            ));
            $SESSION->redirect('?m=nodeinfo&id=' . $nodeid);
        } else {
            $SESSION->redirect('?m=nodeinfo&id=' . $nodeid . '&devid=' . $_GET['devid']);
        }
        break;
}

$nodeinfo = $LMS->GetNode($nodeid);

$nodeinfo['macs'] = Utils::array_column($nodeinfo['macs'], 'mac');
$node_empty_mac = ConfigHelper::getConfig('nodes.empty_mac', ConfigHelper::getConfig('phpui.node_empty_mac', '', true));
if (strlen($node_empty_mac)) {
    if (check_mac($node_empty_mac)) {
        $node_empty_mac = Utils::normalizeMac($node_empty_mac);
        $nodeinfo['macs'] = array_filter($nodeinfo['macs'], function ($mac) use ($node_empty_mac) {
            return $mac != $node_empty_mac;
        });
    } else {
        $node_empty_mac = '';
    }
}

$netdevices = $LMS->GetNetDevNames();

$layout['pagetitle'] = trans('Node Edit: $a', $nodeinfo['name']);

if (isset($_POST['nodeedit'])) {
    $nodeedit = $_POST['nodeedit'];

    if (empty($nodeedit['macs'])) {
        $nodeedit['macs'] = array();
    }

    $nodeedit['macs'] = array_map(
        function ($mac) {
            return Utils::normalizeMac($mac);
        },
        $nodeedit['macs']
    );

    if (strlen($node_empty_mac)) {
        $nodeedit['macs'] = array_filter(
            $nodeedit['macs'],
            function ($mac) use ($node_empty_mac) {
                return $mac != $node_empty_mac;
            }
        );
    }

    foreach ($nodeedit as $key => $value) {
        if ($key != 'macs' && $key != 'authtype' && $key != 'wysiwyg') {
            $nodeedit[$key] = trim($value);
        }
    }

    if ($nodeedit['ipaddr'] == '' && $nodeedit['ipaddr_pub'] == '' && empty($nodeedit['macs']) && $nodeedit['name'] == '' && $nodeedit['info'] == '' && $nodeedit['passwd'] == '' && !isset($nodeedit['wholenetwork'])) {
        $SESSION->redirect_to_history_entry();
    }

    if (isset($nodeedit['wholenetwork'])) {
        $nodeedit['ipaddr'] = '0.0.0.0';
        $nodeedit['ipaddr_pub'] = '0.0.0.0';
        $net = $LMS->GetNetworkRecord($nodeedit['netid'], 0, 1);
        if (!empty($net['ownerid']) && !empty($nodeedit['ownerid']) && $net['ownerid'] != $nodeedit['ownerid']) {
            $error['netid'] = trans('Selected network is already assigned to customer $a ($b)!', $net['customername'], $net['ownerid']);
        }
    } elseif (check_ip($nodeedit['ipaddr'])) {
        if ($LMS->IsIPValid($nodeedit['ipaddr'])) {
            if (empty($nodeedit['netid'])) {
                $nodeedit['netid'] = $DB->GetOne(
                    'SELECT id FROM networks WHERE INET_ATON(?) & INET_ATON(mask) = address ORDER BY id LIMIT 1',
                    array($nodeedit['ipaddr'])
                );
            }
            if (!$LMS->IsIPInNetwork($nodeedit['ipaddr'], $nodeedit['netid'])) {
                $error['ipaddr'] = trans('Specified IP address doesn\'t belong to selected network!');
            } else {
                $ip = $LMS->GetNodeIPByID($nodeedit['id']);
                if ($ip != $nodeedit['ipaddr']) {
                    if (!$LMS->IsIPFree($nodeedit['ipaddr'], $nodeedit['netid'])) {
                        $error['ipaddr'] = trans('Specified IP address is in use!');
                    } elseif ($LMS->IsIPGateway($nodeedit['ipaddr'])) {
                        $error['ipaddr'] = trans('Specified IP address is network gateway!');
                    }
                }
            }
        } else {
            $error['ipaddr'] = trans('Specified IP address doesn\'t overlap with any network!');
        }
    } else {
        $error['ipaddr'] = trans('Incorrect IP address!');
    }

    if ($nodeedit['ipaddr_pub'] != '0.0.0.0' && $nodeedit['ipaddr_pub'] != '') {
        if (check_ip($nodeedit['ipaddr_pub'])) {
            if ($LMS->IsIPValid($nodeedit['ipaddr_pub'])) {
                $ip = $LMS->GetNodePubIPByID($nodeedit['id']);
                if ($ip != $nodeedit['ipaddr_pub'] && !$LMS->IsIPFree($nodeedit['ipaddr_pub'])) {
                    $error['ipaddr_pub'] = trans('Specified IP address is in use!');
                } elseif ($ip != $nodeedit['ipaddr_pub'] && $LMS->IsIPGateway($nodeedit['ipaddr_pub'])) {
                    $error['ipaddr_pub'] = trans('Specified IP address is network gateway!');
                }
            } else {
                $error['ipaddr_pub'] = trans('Specified IP address doesn\'t overlap with any network!');
            }
        } else {
            $error['ipaddr_pub'] = trans('Incorrect IP address!');
        }
    } else {
        $nodeedit['ipaddr_pub'] = '0.0.0.0';
    }

    $macs = array();
    $key = 0;
    foreach ($nodeedit['macs'] as $value) {
        if (!$value) {
            continue;
        }

        if (check_mac($value)) {
            if (in_array($value, $macs)) {
                $error['mac-input-' . $key] = trans('Specified MAC address is in use!');
            } elseif ($value != '00:00:00:00:00:00' && !ConfigHelper::checkConfig('phpui.allow_mac_sharing')) {
                if (($nodeid = $LMS->GetNodeIDByMAC($value)) != null && $nodeid != $nodeinfo['id']) {
                    $error['mac-input-' . $key] = trans('Specified MAC address is in use!');
                }
            }
        } else {
            $error['mac-input-' . $key] = trans('Incorrect MAC address!');
        }

        $macs[$key] = $value;
        ++$key;
    }

    if (!strlen($node_empty_mac) && empty($macs)) {
        $error['mac0'] = trans('MAC address is required!');
    }
    $nodeedit['macs'] = $macs;

    if ($nodeedit['name'] == '') {
        $error['name'] = trans('Node name is required!');
    } elseif (!preg_match('/' . ConfigHelper::getConfig('nodes.name_regexp', ConfigHelper::getConfig('phpui.node_name_regexp', '^[_a-z0-9\-\.]+$')) . '/i', $nodeedit['name'])) {
        $error['name'] = trans('Specified name contains forbidden characters!');
    } elseif (strlen($nodeedit['name']) > 32) {
        $error['name'] = trans('Node name is too long (max. 32 characters)!');
    } elseif (($tmp_nodeid = $LMS->GetNodeIDByName($nodeedit['name'])) && $tmp_nodeid != $nodeedit['id']) {
        $error['name'] = trans('Specified name is in use!');
    }

    $login_required = ConfigHelper::getConfig('nodes.login_required', ConfigHelper::getConfig('phpui.node_login_required', 'none'));

    if ($login_length = strlen($nodeedit['login'])) {
        if ($login_length > 32) {
            $error['login'] = trans('Login is too long (max. 32 characters)!');
        } elseif (!preg_match('/' . ConfigHelper::getConfig('nodes.login_regexp', ConfigHelper::getConfig('phpui.node_login_regexp', '^[_a-z0-9\-\.]+$')) . '/i', $nodeedit['login'])) {
            $error['login'] = trans('Specified login contains forbidden characters!');
        } elseif (($tmp_nodeid = $LMS->GetNodeIDByLogin($nodeedit['login'])) && $tmp_nodeid != $nodeedit['id']) {
            $error['login'] = trans('Specified login is in use!');
        }
    } elseif ($login_required != 'none') {
        if ($login_required == 'error' || $login_required == 'true') {
            $error['login'] = trans('Login is required!');
        } elseif ($login_required == 'warning' && !isset($warnings['nodeedit-login-'])) {
            $warning['nodeedit[login]'] = trans('Login is empty!');
        }
    }

    $password_required = ConfigHelper::getConfig('nodes.password_required', ConfigHelper::getConfig('phpui.node_password_required', ConfigHelper::getConfig('phpui.nodepassword_required', 'none')));

    $password_max_length = intval(ConfigHelper::getConfig('nodes.password_max_length', 32));

    if (strlen($nodeedit['passwd']) > $password_max_length) {
        $error['passwd'] = trans('Password is too long (max. $a characters)!', $password_max_length);
    } elseif (!strlen($nodeedit['passwd']) && $password_required != 'none') {
        $auth_types = ConfigHelper::getConfig('nodes.password_required_for_auth_types', ConfigHelper::getConfig('phpui.node_password_required_for_auth_types', 'all'));
        if ($auth_types == 'all') {
            $auth_types = null;
        } else {
            $auth_types = preg_split("/([\s]+|[\s]*,[\s]*)/", $auth_types, -1, PREG_SPLIT_NO_EMPTY);
            if (empty($auth_types)) {
                $auth_types = null;
            } else {
                $all_auth_types = Utils::array_column($SESSIONTYPES, 'alias');
                $auth_types = array_intersect($all_auth_types, $auth_types);
                if (empty($auth_types)) {
                    $auth_types = null;
                }
            }
        }
        if (empty($auth_types)) {
            $requiring_auth_type = true;
        } else {
            $requiring_auth_type = false;
            foreach ($nodeedit['authtype'] as $val) {
                if (isset($auth_types[$val])) {
                    $requiring_auth_type = true;
                    break;
                }
            }
        }
        if ($requiring_auth_type) {
            if ($password_required == 'error' || $password_required == 'true') {
                $error['passwd'] = trans('Password is required!');
            } elseif ($password_required == 'warning' && !isset($warnings['nodeedit-passwd-'])) {
                $warning['nodeedit[passwd]'] = trans('Password is empty!');
            }
        }
    }

    $gps_coordinates_required = ConfigHelper::getConfig('nodes.gps_coordinates_required', ConfigHelper::getConfig('phpui.node_gps_coordinates_required', 'none'));

    $longitude = filter_var($nodeedit['longitude'], FILTER_VALIDATE_FLOAT);
    $latitude = filter_var($nodeedit['latitude'], FILTER_VALIDATE_FLOAT);

    if (strlen($nodeedit['longitude']) && $longitude === false) {
        $error['longitude'] = trans('Invalid longitude format!');
    }
    if (strlen($nodeedit['latitude']) && $latitude === false) {
        $error['latitude'] = trans('Invalid latitude format!');
    }

    if (!strlen($nodeedit['longitude']) != !strlen($nodeedit['latitude'])) {
        if (!isset($error['longitude'])) {
            $error['longitude'] = trans('Longitude and latitude cannot be empty!');
        }
        if (!isset($error['latitude'])) {
            $error['latitude'] = trans('Longitude and latitude cannot be empty!');
        }
    }

    if ($gps_coordinates_required != 'none'
        && ($gps_coordinates_required == 'warning'
            || $gps_coordinates_required == 'error'
            || ConfigHelper::checkValue($gps_coordinates_required))) {
        if ($gps_coordinates_required != 'warning' && $gps_coordinates_required != 'error') {
            $gps_coordinates_required = 'error';
        }
        if (!isset($error['longitude']) && !strlen($nodeedit['longitude'])) {
            if ($gps_coordinates_required == 'error') {
                $error['longitude'] = trans('Longitude is required!');
            } elseif ($gps_coordinates_required == 'warning' && !isset($warnings['nodeedit-longitude-'])) {
                $warning['nodeedit[longitude]'] = trans('Longitude should not be empty!');
            }
        }
        if (!isset($error['latitude']) && !strlen($nodeedit['latitude'])) {
            if ($gps_coordinates_required == 'error') {
                $error['latitude'] = trans('Latitude is required!');
            } elseif ($gps_coordinates_required == 'warning' && !isset($warnings['nodeedit-latitude-'])) {
                $warning['nodeedit[latitude]'] = trans('Latitude should not be empty!');
            }
        }
    }

    if (!isset($nodeedit['access'])) {
        $nodeedit['access'] = 0;
    }
    if (!isset($nodeedit['warning'])) {
        $nodeedit['warning'] = 0;
    }
    if (!isset($nodeedit['chkmac'])) {
        $nodeedit['chkmac'] = 0;
    }
    if (!isset($nodeedit['halfduplex'])) {
        $nodeedit['halfduplex'] = 0;
    }
    if (!isset($nodeedit['netdev'])) {
        $nodeedit['netdev'] = 0;
    }

    if ($nodeinfo['netdev'] != $nodeedit['netdev'] && $nodeedit['netdev'] != 0) {
        $ports = $DB->GetOne('SELECT ports FROM netdevices WHERE id = ?', array($nodeedit['netdev']));
        $takenports = $LMS->CountNetDevLinks($nodeedit['netdev']);

        if ($ports <= $takenports) {
            $error['netdev'] = trans('It scans for free ports in selected device!');
        }
        $nodeinfo['netdev'] = $nodeedit['netdev'];
    }

    if ($nodeedit['netdev'] && ($nodeedit['port'] != $nodeinfo['port'] || $nodeinfo['netdev'] != $nodeedit['netdev'])) {
        if ($nodeedit['port']) {
            if (!isset($ports)) {
                $ports = $DB->GetOne('SELECT ports FROM netdevices WHERE id = ?', array($nodeedit['netdev']));
            }

            if (!preg_match('/^[0-9]+$/', $nodeedit['port']) || $nodeedit['port'] > $ports) {
                $error['port'] = trans('Incorrect port number!');
            } elseif ($DB->GetOne('SELECT id FROM vnodes WHERE netdev=? AND port=? AND ownerid IS NOT NULL', array($nodeedit['netdev'], $nodeedit['port']))
                    || $DB->GetOne('SELECT 1 FROM netlinks WHERE (src = ? OR dst = ?)
			                AND (CASE src WHEN ? THEN srcport ELSE dstport END) = ?', array($nodeedit['netdev'], $nodeedit['netdev'], $nodeedit['netdev'], $nodeedit['port']))) {
                $error['port'] = trans('Selected port number is taken by other device or node!');
            }
        }
    }

    if (!ConfigHelper::checkPrivilege('full_access') && ConfigHelper::checkConfig('nodes.network_device_connection_required', ConfigHelper::checkConfig('phpui.node_to_network_device_connection_required'))
        && empty($nodeedit['netdev'])) {
        $error['netdev'] = trans('Network device selection is required!');
    }

    if (!$nodeedit['ownerid']) {
        $error['nodeedit[customerid]'] = trans('Customer not selected!');
        $error['nodeedit[ownerid]']    = trans('Customer not selected!');
    } else if (! $LMS->CustomerExists($nodeedit['ownerid'])) {
        $error['nodeedit[customerid]'] = trans('Inexistent owner selected!');
        $error['nodeedit[ownerid]'] = trans('Inexistent owner selected!');
    } elseif ($nodeedit['access']) {
        $allowed_statuses = array_flip(
            Utils::determineAllowedCustomerStatus(
                ConfigHelper::getConfig(
                    'customers.node_access_change_allowed_on_statuses',
                    ConfigHelper::getConfig('phpui.node_access_change_allowed_customer_statuses')
                ),
                array(
                    CSTATUS_CONNECTED,
                )
            )
        );
        if (!isset($allowed_statuses[$LMS->GetCustomerStatus($nodeedit['ownerid'])])) {
            $error['access'] = trans('Node owner is not connected!');
        }
    }

    if (!ConfigHelper::checkPrivilege('full_access') && ConfigHelper::checkConfig('phpui.teryt_required')
        && !empty($nodeedit['address_id']) && !$LMS->isTerritAddress($nodeedit['address_id'])) {
        $error['address_id'] = trans('TERYT address is required!');
    }

    if ($nodeedit['invprojectid'] == '-1') { // nowy projekt
        if (!strlen(trim($nodeedit['projectname']))) {
            $error['projectname'] = trans('Project name is required');
        }
        if ($LMS->ProjectByNameExists($nodeedit['projectname'])) {
            $error['projectname'] = trans('Project with that name already exists');
        }
    }
    $authtype = 0;
    if (isset($nodeedit['authtype'])) {
        foreach ($nodeedit['authtype'] as $idx) {
            $authtype |= intval($idx);
        }
    }
    $nodeedit['authtype'] = $authtype;

    if (!empty($netdevices)) {
        $technology_required = ConfigHelper::getConfig('nodes.link_technology_required', ConfigHelper::getConfig('phpui.node_link_technology_required', 'error'));
        $technology = intval($nodeedit['linktechnology']);

        if ($technology_required != 'none' && empty($technology)) {
            if ($technology_required == 'error' || $technology_required == 'true') {
                $error['linktechnology'] = trans('Link technology is required!');
            } elseif ($technology_required == 'warning' && !isset($warnings['nodeedit-linktechnology-'])) {
                $warning['nodeedit[linktechnology]'] = trans('Link technology is not selected!');
            }
        }
    }

    $hook_data = $LMS->executeHook(
        'nodeedit_validation_before_submit',
        array(
            'nodeedit' => $nodeedit,
            'error' => $error,
        )
    );
    $nodeedit = $hook_data['nodeedit'];
    $error = $hook_data['error'];

    if (!$error && !$warning) {
        $nodeedit = $LMS->ExecHook('node_edit_before', $nodeedit);

        $ipi = $nodeedit['invprojectid'];
        if ($ipi == '-1') {
            $nodeedit['project'] = $nodeedit['projectname'];
            $ipi = $LMS->AddProject($nodeedit);
        }
        if ($nodeedit['invprojectid'] == '-1' || intval($ipi)>0) {
            $nodeedit['invprojectid'] = intval($ipi);
        } else {
            $nodeedit['invprojectid'] = null;
        }
        $LMS->NodeUpdate($nodeedit, ($customerid != $nodeedit['ownerid']));
        $LMS->CleanupProjects();

        $nodeedit = $LMS->ExecHook('node_edit_after', $nodeedit);

        $hook_data = $LMS->executeHook(
            'nodeedit_after_submit',
            array(
                'nodeedit' => $nodeedit,
            )
        );
        $nodeedit = $hook_data['nodeedit'];

        $SESSION->redirect_to_history_entry();
    }

    $nodeinfo['name'] = $nodeedit['name'];
    $nodeinfo['macs'] = $nodeedit['macs'];
    $nodeinfo['ipaddr'] = $nodeedit['ipaddr'];
    $nodeinfo['netid'] = $nodeedit['netid'];
    $nodeinfo['wholenetwork'] = $nodeedit['wholenetwork'] ?? null;
    $nodeinfo['ipaddr_pub'] = $nodeedit['ipaddr_pub'];
    $nodeinfo['pubnetid'] = $nodeedit['pubnetid'];
    $nodeinfo['passwd'] = $nodeedit['passwd'];
    $nodeinfo['access'] = $nodeedit['access'];
    $nodeinfo['ownerid'] = $nodeedit['ownerid'];
    $nodeinfo['chkmac'] = $nodeedit['chkmac'];
    $nodeinfo['halfduplex'] = $nodeedit['halfduplex'];
    $nodeinfo['port'] = $nodeedit['port'];
    $nodeinfo['stateid'] = $nodeedit['stateid'] ?? null;
    $nodeinfo['latitude'] = $nodeedit['latitude'];
    $nodeinfo['longitude'] = $nodeedit['longitude'];
    $nodeinfo['invprojectid'] = $nodeedit['invprojectid'];
    $nodeinfo['authtype'] = $nodeedit['authtype'];
    $nodeinfo['info'] = $nodeedit['info'];
    $nodeinfo['wysiwyg'] = $nodeedit['wysiwyg'];
    $nodeinfo['linktechnology'] = $nodeedit['linktechnology'];

    if ($nodeedit['ipaddr_pub'] == '0.0.0.0') {
        $nodeinfo['ipaddr_pub'] = '';
    }
} else {
    $nodeinfo['ipaddr'] = $nodeinfo['ip'];
    $nodeinfo['ipaddr_pub'] = $nodeinfo['ip_pub'];

    if (empty($nodeinfo['netdev'])) {
        if (!ctype_digit($nodeinfo['linktype'])) {
            $nodeinfo['linktype'] = intval(ConfigHelper::getConfig('phpui.default_linktype', LINKTYPE_WIRE));
        }
        if (!ctype_digit($nodeinfo['linktechnology'])) {
            $nodeinfo['linktechnology'] = intval(ConfigHelper::getConfig('phpui.default_linktechnology', 0));
        }
        if (!ctype_digit($nodeinfo['linkspeed'])) {
            $nodeinfo['linkspeed'] = intval(ConfigHelper::getConfig('phpui.default_linkspeed', 100000));
        }
    }
}

if (!strlen($node_empty_mac) && empty($nodeinfo['macs'])) {
    $nodeinfo['macs'][] = '';
}

include(MODULES_DIR . DIRECTORY_SEPARATOR . 'customer.inc.php');

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

if (!ConfigHelper::checkConfig('phpui.big_networks')) {
    $SMARTY->assign('customers', $LMS->GetCustomerNames());
}

$LMS->InitXajax();
include(MODULES_DIR . DIRECTORY_SEPARATOR . 'nodexajax.inc.php');
include(MODULES_DIR . DIRECTORY_SEPARATOR . 'geocodexajax.inc.php');

$nodeinfo = $LMS->ExecHook('node_edit_init', $nodeinfo);

$hook_data = $LMS->executeHook(
    'nodeedit_before_display',
    array(
        'nodeedit' => $nodeinfo,
        'smarty' => $SMARTY,
    )
);
$nodeinfo = $hook_data['nodeedit'];

$SMARTY->assign('xajax', $LMS->RunXajax());

$history_entry = $SESSION->get_history_entry();
$backurl = $history_entry ? '?' . $history_entry : '?m=nodelist';
$SMARTY->assign('backurl', $backurl);

if (!empty($nodeinfo['ownerid'])) {
    $addresses = $LMS->getCustomerAddresses($nodeinfo['ownerid']);
    $LMS->determineDefaultCustomerAddress($addresses);
    $SMARTY->assign('addresses', $addresses);
}

$nprojects = $LMS->GetProjects();
$SMARTY->assign('NNprojects', $nprojects);

if (!isset($resource_tabs['nodesessions']) || $resource_tabs['nodesessions']) {
    $SMARTY->assign('nodesessions', $LMS->GetNodeSessions($nodeid));
}
$SMARTY->assign('networks', $LMS->GetNetworks());
$SMARTY->assign('netdevices', $netdevices);
if (!isset($resource_tabs['nodegroups']) || $resource_tabs['nodegroups']) {
    $SMARTY->assign('nodegroups', $LMS->GetNodeGroupNamesByNode($nodeid));
    $SMARTY->assign('othernodegroups', $LMS->GetNodeGroupNamesWithoutNode($nodeid));
}
if (!isset($resource_tabs['managementurls']) || $resource_tabs['managementurls']) {
    $SMARTY->assign('mgmurls', $LMS->GetManagementUrls(LMSNetDevManager::NODE_URL, $nodeid));
}

if (!isset($resource_tabs['routednetworks']) || $resource_tabs['routednetworks']) {
    $SMARTY->assign('routednetworks', $LMS->getNodeRoutedNetworks($nodeid));
    $SMARTY->assign('notroutednetworks', $LMS->getNodeNotRoutedNetworks($nodeid));
    $SMARTY->assign('nodeid', $nodeid);
}

$SMARTY->assign('error', $error);
$SMARTY->assign('node_empty_mac', $node_empty_mac);
$SMARTY->assign('nodeinfo', $nodeinfo);
$SMARTY->assign('objectid', $nodeinfo['id']);
$SMARTY->assign('nodeedit_sortable_order', $SESSION->get_persistent_setting('nodeedit-sortable-order'));
$SMARTY->display('node/nodeedit.html');
