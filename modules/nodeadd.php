<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2017 LMS Developers
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

$nodedata['access']   = 1;
$nodedata['ownerid']  = null;
$nodedata['authtype'] = 0;

if (isset($_GET['ownerid'])) {
    if ($LMS->CustomerExists($_GET['ownerid']) == true) {
        $nodedata['ownerid'] = $_GET['ownerid'];
        $customerinfo = $LMS->GetCustomer($_GET['ownerid']);
        $SMARTY->assign('customerinfo', $customerinfo);
    } else {
        $SESSION->redirect('?m=customerinfo&id='.$_GET['ownerid']);
    }
}

if (isset($_GET['preip'])) {
    $nodedata['ipaddr'] = $_GET['preip'];
}

if (isset($_GET['prenetwork'])) {
    $nodedata['netid'] = $_GET['prenetwork'];
}

if (isset($_GET['premac'])) {
    if (is_array($_GET['premac'])) {
        $nodedata['macs'] = $_GET['premac'];
    } else {
        $nodedata['macs'][] = $_GET['premac'];
    }
}

if (isset($_GET['prename'])) {
    $nodedata['name'] = $_GET['prename'];
}

if (isset($_GET['pre_address_id'])) {
    $nodedata['address_id'] = $_GET['pre_address_id'];
}

if (isset($_POST['nodedata'])) {
    $nodedata = $_POST['nodedata'];

    foreach ($nodedata['macs'] as $key => $value) {
        $nodedata['macs'][$key] = str_replace('-', ':', $value);
    }

    foreach ($nodedata as $key => $value) {
        if ($key != 'macs' && $key != 'authtype' && $key != 'wysiwyg' && $key != 'nodegroup') {
            $nodedata[$key] = trim($value);
        }
    }

    if ($nodedata['ipaddr']=='' && $nodedata['ipaddr_pub'] && $nodedata['mac']=='' && $nodedata['name']=='' && !isset($nodedata['wholenetwork'])) {
        if ($_GET['ownerid']) {
            $SESSION->redirect('?m=customerinfo&id='.$_GET['ownerid']);
        } else {
            $SESSION->redirect('?m=nodelist');
        }
    }

    if ($nodedata['wholenetwork'] && empty($nodedata['netid'])) {
        $error['netid'] = trans('Please choose network');
    }

    if ($nodedata['name']=='') {
        $error['name'] = trans('Node name is required!');
    } else if (strlen($nodedata['name']) > 32) {
        $error['name'] = trans('Node name is too long (max.32 characters)!');
    } else if (!preg_match('/' . ConfigHelper::getConfig('phpui.node_name_regexp', '^[_a-z0-9-.]+$') . '/i', $nodedata['name'])) {
        $error['name'] = trans('Specified name contains forbidden characters!');
    } else if ($LMS->GetNodeIDByName($nodedata['name']) || $LMS->GetNodeIDByNetName($nodedata['name'])) {
        $error['name'] = trans('Specified name is in use!');
    }

    if (isset($nodedata['wholenetwork'])) {
        $nodedata['ipaddr']     = '0.0.0.0';
        $nodedata['ipaddr_pub'] = '0.0.0.0';
        $net = $LMS->GetNetworkRecord($nodedata['netid'], 0, 1);
        if (!empty($net['ownerid']) && !empty($nodedata['ownerid']) && $net['ownerid'] != $nodedata['ownerid']) {
            $error['netid'] = trans('Selected network is already assigned to customer $a ($b)!', $net['customername'], $net['ownerid']);
        }
    } else if (!$nodedata['ipaddr']) {
        $error['ipaddr'] = trans('Node IP address is required!');
    } else if (!check_ip($nodedata['ipaddr'])) {
        $error['ipaddr'] = trans('Incorrect node IP address!');
    } else if (!$LMS->IsIPValid($nodedata['ipaddr'])) {
        $error['ipaddr'] = trans('Specified IP address doesn\'t overlap with any network!');
    } else {
        if (empty($nodedata['netid'])) {
            $nodedata['netid'] = $DB->GetOne(
                'SELECT id FROM networks WHERE INET_ATON(?) & INET_ATON(mask) = address ORDER BY id LIMIT 1',
                array($nodedata['ipaddr'])
            );
        }
        if (!$LMS->IsIPInNetwork($nodedata['ipaddr'], $nodedata['netid'])) {
            $error['ipaddr'] = trans('Specified IP address doesn\'t belong to selected network!');
        } else if (!$LMS->IsIPFree($nodedata['ipaddr'], $nodedata['netid'])) {
            $error['ipaddr'] = trans('Specified IP address is in use!');
        } else if ($LMS->IsIPGateway($nodedata['ipaddr'])) {
            $error['ipaddr'] = trans('Specified IP address is network gateway!');
        }
    }

    if ($nodedata['ipaddr_pub']!='0.0.0.0' && $nodedata['ipaddr_pub']!='') {
        if (!check_ip($nodedata['ipaddr_pub'])) {
            $error['ipaddr_pub'] = trans('Incorrect node IP address!');
        } else if (!$LMS->IsIPValid($nodedata['ipaddr_pub'])) {
            $error['ipaddr_pub'] = trans('Specified IP address doesn\'t overlap with any network!');
        } else if (!$LMS->IsIPFree($nodedata['ipaddr_pub'])) {
            $error['ipaddr_pub'] = trans('Specified IP address is in use!');
        } else if ($LMS->IsIPGateway($nodedata['ipaddr_pub'])) {
            $error['ipaddr_pub'] = trans('Specified IP address is network gateway!');
        }
    } else {
        $nodedata['ipaddr_pub'] = '0.0.0.0';
    }

    $macs = array();
    $key = 0;
    foreach ($nodedata['macs'] as $value) {
        if (!$value) {
            continue;
        }

        if (check_mac($value)) {
            if ($value != '00:00:00:00:00:00' && !ConfigHelper::checkConfig('phpui.allow_mac_sharing')) {
                if ($LMS->GetNodeIDByMAC($value)) {
                    $error['mac' . $key] = trans('Specified MAC address is in use!');
                }
            }
        } else {
            $error['mac' . $key] = trans('Incorrect MAC address!');
        }

        $macs[$key] = $value;
        ++$key;
    }

    if (empty($macs)) {
        $error['mac0'] = trans('MAC address is required!');
    }
    $nodedata['macs'] = $macs;

    if (strlen($nodedata['passwd']) > 32) {
        $error['passwd'] = trans('Password is too long (max.32 characters)!');
    }

    if (!$nodedata['ownerid']) {
        $error['nodedata[customerid]'] = trans('Customer not selected!');
        $error['nodedata[ownerid]']    = trans('Customer not selected!');
    } else if (! $LMS->CustomerExists($nodedata['ownerid'])) {
        $error['nodedata[customerid]'] = trans('Inexistent owner selected!');
        $error['nodedata[ownerid]'] = trans('Inexistent owner selected!');
    } else {
        $status = $LMS->GetCustomerStatus($nodedata['ownerid']);
        if ($status == CSTATUS_INTERESTED) { // unknown (interested)
            $error['ownerid'] = trans('Selected customer is not connected!');
        } else if ($status == CSTATUS_WAITING && $nodedata['access']) { // awaiting
            $error['access'] = trans('Node owner is not connected!');
        }
    }

    // check if customer address is selected or if default location address exists
    // if both are not fullfilled we generate user interface warning
    $customer_addresses_warning = $_POST['customer_addresses_warning'];
    if (!$customer_addresses_warning && isset($nodedata['address_id'])
        && $nodedata['address_id'] == -1 && !empty($nodedata['ownerid'])) {
        $addresses = $LMS->getCustomerAddresses($nodedata['ownerid'], true);
        if (count($addresses) > 1) {
            $i = 0;
            foreach ($addresses as $address) {
                if ($address['location_address_type'] == DEFAULT_LOCATION_ADDRESS) {
                    break;
                }
                $i++;
            }
            if ($i == count($addresses)) {
                $customer_addresses_warning = 1;
                $warn['address_id'] = trans('No address has been selected!');
            }
        }
    }
    $SMARTY->assign('customer_addresses_warning', $customer_addresses_warning);

    if ($nodedata['netdev']) {
        $ports = $DB->GetOne('SELECT ports FROM netdevices WHERE id = ?', array($nodedata['netdev']));
        $takenports = $LMS->CountNetDevLinks($nodedata['netdev']);

        if ($ports <= $takenports) {
            $error['netdev'] = trans('No free ports on device!');
        } else if ($nodedata['port']) {
            if (!preg_match('/^[0-9]+$/', $nodedata['port']) || $nodedata['port'] > $ports) {
                $error['port'] = trans('Incorrect port number!');
            } else if ($DB->GetOne(
                'SELECT id FROM vnodes WHERE netdev=? AND port=? AND ownerid IS NOT NULL',
                array($nodedata['netdev'], $nodedata['port'])
            )
                    || $DB->GetOne(
                        'SELECT 1 FROM netlinks WHERE (src = ? OR dst = ?)
			        AND (CASE src WHEN ? THEN srcport ELSE dstport END) = ?',
                        array($nodedata['netdev'], $nodedata['netdev'], $nodedata['netdev'], $nodedata['port'])
                    )) {
                    $error['port'] = trans('Selected port number is taken by other device or node!');
            }
        }
    } else {
        $nodedata['netdev'] = 0;
    }

    if (!isset($nodedata['chkmac'])) {
        $nodedata['chkmac'] = 0;
    }

    if (!isset($nodedata['halfduplex'])) {
        $nodedata['halfduplex'] = 0;
    }

    if (!ConfigHelper::checkPrivilege('full_access') && ConfigHelper::checkConfig('phpui.teryt_required')
        && !empty($nodedata['address_id']) && !$LMS->isTerritAddress($nodedata['address_id'])) {
        $error['address_id'] = trans('TERRIT address is required!');
    }

    if ($nodedata['invprojectid'] == '-1') { // nowy projekt
        if (!strlen(trim($nodedata['projectname']))) {
            $error['projectname'] = trans('Project name is required');
        }
        if ($LMS->ProjectByNameExists($nodedata['projectname'])) {
            $error['projectname'] = trans('Project with that name already exists');
        }
    }

    $authtype = 0;
    if (isset($nodedata['authtype'])) {
        foreach ($nodedata['authtype'] as $val) {
            $authtype |= intval($val);
        }
    }
    $nodedata['authtype'] = $authtype;

    $hook_data = $LMS->executeHook(
        'nodeadd_validation_before_submit',
        array(
            'nodeadd' => $nodedata,
            'error'   => $error,
            'warning'   => $warning,
        )
    );
    $nodedata = $hook_data['nodeadd'];
    $error = $hook_data['error'];
    $warning = $hook_data['warning'];

    if (!$error && !$warning) {
        $nodedata = $LMS->ExecHook('node_add_before', $nodedata);

        $ipi = $nodedata['invprojectid'];
        if ($ipi == '-1') {
            $nodedata['project'] = $nodedata['projectname'];
            $ipi = $LMS->AddProject($nodedata);
        }

        if ($nodedata['invprojectid'] == '-1' || intval($ipi)>0) {
            $nodedata['invprojectid'] = intval($ipi);
        } else {
            $nodedata['invprojectid'] = null;
        }

        $nodeid = $LMS->NodeAdd($nodedata);

        if (is_array($nodedata['nodegroup']) && count($nodedata['nodegroup'])) {
            foreach ($nodedata['nodegroup'] as $nodegroupid) {
                $DB->Execute('INSERT INTO nodegroupassignments (nodeid, nodegroupid)
					VALUES (?, ?)', array($nodeid, intval($nodegroupid)));
            }
        }

        $nodedata['id'] = $nodeid;
        $nodedata = $LMS->ExecHook('node_add_after', $nodedata);

        $hook_data = $LMS->executeHook(
            'nodeadd_after_submit',
            array(
                'nodeadd' => $nodedata,
            )
        );
        $nodedata = $hook_data['nodeadd'];

        if (!isset($nodedata['reuse'])) {
            if (isset($nodedata['wholenetwork'])) {
                $SESSION->redirect('?m=netinfo&id=' . $nodedata['netid']);
            } else {
                $SESSION->redirect('?m=nodeinfo&id=' . $nodeid);
            }
        }

        $ownerid = $nodedata['ownerid'];
        unset($nodedata);

        $nodedata['ownerid'] = $ownerid;
        $nodedata['reuse'] = '1';
    } else {
        if ($nodedata['ipaddr_pub']=='0.0.0.0') {
            $nodedata['ipaddr_pub'] = '';
        }
    }
} else {
    $nodedata['linktype'] = intval(ConfigHelper::getConfig('phpui.default_linktype', LINKTYPE_WIRE));
    $nodedata['linktechnology'] = intval(ConfigHelper::getConfig('phpui.default_linktechnology', 0));
    $nodedata['linkspeed'] = intval(ConfigHelper::getConfig('phpui.default_linkspeed', 100000));

    // check if customer address is selected or if default location address exists
    // if both are not fullfilled we generate user interface warning
/*
    if (isset($_GET['ownerid'])) {
        $addresses = $LMS->getCustomerAddresses($_GET['ownerid'], true);
        if (count($addresses) > 1) {
            $i = 0;
            foreach ($addresses as $address) {
                if ($address['location_address_type'] == DEFAULT_LOCATION_ADDRESS) {
                    break;
                }
                $i++;
            }
            if ($i == count($addresses)) {
                $error['address_id'] = trans('No address has been selected!');
            }
        }
    }
*/
}

if (empty($nodedata['macs'])) {
    $nodedata['macs'][] = '';
}

$layout['pagetitle'] = trans('New Node');

if (!empty($nodedata['ownerid']) && $LMS->CustomerExists($nodedata['ownerid']) && ($customerid = $nodedata['ownerid'])) {
    include(MODULES_DIR.'/customer.inc.php');
} else {
    $SMARTY->assign('allnodegroups', $LMS->GetNodeGroupNames());
}

if (!ConfigHelper::checkConfig('phpui.big_networks')) {
    $SMARTY->assign('customers', $LMS->GetCustomerNames());
}

$nprojects = $LMS->GetProjects();
$SMARTY->assign('NNprojects', $nprojects);

$LMS->InitXajax();
include(MODULES_DIR . DIRECTORY_SEPARATOR . 'nodexajax.inc.php');
include(MODULES_DIR . DIRECTORY_SEPARATOR . 'geocodexajax.inc.php');

$nodedata = $LMS->ExecHook('node_add_init', $nodedata);

$hook_data = $LMS->executeHook(
    'nodeadd_before_display',
    array(
        'nodeadd' => $nodedata,
        'smarty' => $SMARTY,
    )
);
$nodedata = $hook_data['nodeadd'];

$SMARTY->assign('xajax', $LMS->RunXajax());

if (!empty($nodedata['ownerid'])) {
    $addresses = $LMS->getCustomerAddresses($nodedata['ownerid']);
    $LMS->determineDefaultCustomerAddress($addresses);
    $SMARTY->assign('addresses', $addresses);
}

$SMARTY->assign('networks', $LMS->GetNetworks(true));
$SMARTY->assign('netdevices', $LMS->GetNetDevNames());
$SMARTY->assign('error', $error);
$SMARTY->assign('nodedata', $nodedata);
$SMARTY->display('node/nodeadd.html');
