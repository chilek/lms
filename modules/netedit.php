<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2018 LMS Developers
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

if (!$LMS->NetworkExists($_GET['id'])) {
    $SESSION->redirect('?m=netlist');
}

if (isset($_GET['id']) && isset($_GET['networkset'])) {
    $LMS->NetworkSet($_GET['id']);
    $SESSION->redirect('?' . $SESSION->get('backto'));
}

if ($SESSION->is_set('ntlp.'.$_GET['id']) && ! isset($_GET['page'])) {
    $SESSION->restore('ntlp.'.$_GET['id'], $page);
} else {
    $page = empty($_GET['page']) ? 0 : $_GET['page'];
}

$SESSION->save('ntlp.'.$_GET['id'], $page);
    
$network = $LMS->GetNetworkRecord($_GET['id'], $page, ConfigHelper::getConfig('phpui.networkhosts_pagelimit'));

if (isset($_POST['networkdata'])) {
    $networkdata = $_POST['networkdata'];

    foreach ($networkdata as $key => $value) {
        if ($key != 'authtype') {
            $networkdata[$key] = trim($value);
        }
    }

    $networkdata['id'] = $_GET['id'];
    $networkdata['size'] = pow(2, 32-$networkdata['prefix']);
    $networkdata['addresslong'] = ip_long($networkdata['address']);
    $networkdata['mask'] = prefix2mask($networkdata['prefix']);
    $networkdata['snatlong'] = ip_long($networkdata['snat']);

    if (empty($networkdata['hostid'])) {
        $error['hostid'] = trans('Host should be selected!');
    }

    if (!empty($networkdata['snat'])) {
        if (!check_ip($networkdata['snat'])) {
            $error['snat'] = trans('Incorrect snat IP address!');
        }
    }

    if (!check_ip($networkdata['address'])) {
        $error['address'] = trans('Incorrect network IP address!');
    } else {
        if (getnetaddr($networkdata['address'], prefix2mask($networkdata['prefix']))!=$networkdata['address']) {
            $error['address'] = trans('Specified address is not a network address, setting $a', getnetaddr($networkdata['address'], prefix2mask($networkdata['prefix'])));
            $networkdata['address'] = getnetaddr($networkdata['address'], prefix2mask($networkdata['prefix']));
        } else {
            if ($LMS->NetworkOverlaps($networkdata['address'], prefix2mask($networkdata['prefix']), $networkdata['hostid'], $networkdata['id'])) {
                $error['address'] = trans('Specified IP address overlaps with other network!');
            } else {
                if ($network['assigned'] > ($networkdata['size']-2)) {
                    $error['address'] = trans('New network is too small!');
                } else {
                    $node = $DB->GetRow(
                        'SELECT MAX(ipaddr) AS last, MIN(ipaddr) AS first
							    FROM nodes WHERE (ipaddr>? AND ipaddr<?)',
                        array($network['addresslong'],ip_long($network['broadcast']))
                    );
                                
                    $node_pub = $DB->GetRow(
                        'SELECT MAX(ipaddr_pub) AS last, MIN(ipaddr_pub) AS first
							    FROM nodes WHERE (ipaddr_pub>? AND ipaddr_pub<?)',
                        array($network['addresslong'],ip_long($network['broadcast']))
                    );
                
                    if ($node_pub['first']) {
                        $node['first'] = min($node['first'], $node_pub['first']);
                    }
                    if ($node_pub['last']) {
                        $node['last'] = min($node['last'], $node_pub['last']);
                    }
                    
                    if (($node['first'] && $node['first'] < $networkdata['addresslong']) ||
                        ($node['last'] && $node['last'] >= ip_long(getbraddr($networkdata['address'], prefix2mask($networkdata['prefix'])))) ) {
                        $shift = $networkdata['addresslong'] - $network['addresslong'];
                        if ($node['first'] + $shift < $networkdata['addresslong'] ||
                            $node['last'] + $shift >= ip_long(getbraddr($networkdata['address'], prefix2mask($networkdata['prefix'])))
                        ) {
                            $error['address'] = trans('New network is too small. Put in order IP addresses first!');
                        } else {
                            $networkdata['needshft'] = true;
                        }
                    }
                }
            }
        }
    }

    if ($networkdata['interface'] != '' && !preg_match('/^[a-z0-9:.]+$/', $networkdata['interface'])) {
        $error['interface'] = trans('Incorrect interface name!');
    }

    if ($networkdata['vlanid'] != '') {
        if (!is_numeric($networkdata['vlanid'])) {
            $error['vlanid'] = trans('Vlan ID must be integer!');
        } elseif ($networkdata['vlanid'] < 1 || $networkdata['vlanid'] > 4094) {
            $error['vlanid'] = trans('Vlan ID must be between 1 and 4094!');
        }
    }

    if ($networkdata['name']=='') {
        $error['name'] = trans('Network name is required!');
    } elseif (!preg_match('/^[._a-z0-9-]+$/i', $networkdata['name'])) {
        $error['name'] = trans('Network name contains forbidden characters!');
    }

    if ($networkdata['domain']!='' && !preg_match('/^[.a-z0-9-]+$/i', $networkdata['domain'])) {
        $error['domain'] = trans('Specified domain contains forbidden characters!');
    }

    if ($networkdata['dns']!='' && !check_ip($networkdata['dns'])) {
        $error['dns'] = trans('Incorrect DNS server IP address!');
    }

    if ($networkdata['dns2']!='' && !check_ip($networkdata['dns2'])) {
        $error['dns2'] = trans('Incorrect DNS server IP address!');
    }

    if ($networkdata['wins']!='' && !check_ip($networkdata['wins'])) {
        $error['wins'] =  trans('Incorrect WINS server IP address!');
    }

    if ($networkdata['gateway']!='') {
        if (!check_ip($networkdata['gateway'])) {
            $error['gateway'] = trans('Incorrect gateway IP address!');
        } else if (!isipin($networkdata['gateway'], getnetaddr($networkdata['address'], prefix2mask($networkdata['prefix'])), prefix2mask($networkdata['prefix']))) {
            $error['gateway'] =  trans('Specified gateway address does not match with network address!');
        }
    }

    if ($networkdata['dhcpstart']!='') {
        if (!check_ip($networkdata['dhcpstart'])) {
            $error['dhcpstart'] = trans('Incorrect IP address for DHCP range start!');
        } else if (!isipin($networkdata['dhcpstart'], getnetaddr($networkdata['address'], prefix2mask($networkdata['prefix'])), prefix2mask($networkdata['prefix'])) && $networkdata['address']!='') {
            $error['dhcpstart'] = trans('IP address for DHCP range start does not match with network address!');
        }
    }

    if ($networkdata['dhcpend']!='') {
        if (!check_ip($networkdata['dhcpend'])) {
            $error['dhcpend'] =  trans('Incorrect IP address for DHCP range end!');
        } else if (!isipin($networkdata['dhcpend'], getnetaddr($networkdata['address'], prefix2mask($networkdata['prefix'])), prefix2mask($networkdata['prefix'])) && $networkdata['address']!='') {
            $error['dhcpend'] = trans('IP address for DHCP range end does not match with network address!');
        }
    }

    if (!isset($error['dhcpstart']) && !isset($error['dhcpend'])) {
        if (($networkdata['dhcpstart']!='' && $networkdata['dhcpend']=='')||($networkdata['dhcpstart']=='' && $networkdata['dhcpend']!='')) {
            $error['dhcpend'] = trans('Both IP addresses for DHCP range are required!');
        }
        if ($networkdata['dhcpstart']!='' && $networkdata['dhcpend']!='' && !(ip_long($networkdata['dhcpend']) >= ip_long($networkdata['dhcpstart']))) {
            $error['dhcpend'] = trans('End of DHCP range has to be equal or greater than start!');
        }
    }

    if (!empty($networkdata['ownerid']) && !$LMS->CustomerExists($networkdata['ownerid'])) {
        $error['ownerid'] = trans('Customer with the specified ID does not exist');
    }

    $authtype = 0;
    if (isset($networkdata['authtype'])) {
        foreach ($networkdata['authtype'] as $idx) {
            $authtype |= intval($idx);
        }
    }
    $networkdata['authtype'] = $authtype;

    if (!$error) {
        if (isset($networkdata['needshft']) && $networkdata['needshft']) {
            $LMS->NetworkShift($networkdata['id'], $network['address'], $network['mask'], $networkdata['addresslong'] - $network['addresslong']);
        }

        if ($networkdata['ownerid'] != $network['ownerid']) {
            $vnetwork = $DB->GetRow('SELECT nodeid, ownerid FROM vnetworks WHERE id = ?', array($networkdata['id']));
            if ($networkdata['ownerid'] == '' && $vnetwork) {
                $DB->Execute('DELETE FROM nodes WHERE id = ?', array($vnetwork['nodeid']));
            } elseif ($vnetwork) {
                $DB->Execute(
                    'UPDATE nodes SET ownerid = ? WHERE id = ?',
                    array(
                        empty($networkdata['ownerid']) ? null : $networkdata['ownerid'],
                        $vnetwork['nodeid'],
                    )
                );
            } else {
                $DB->Execute(
                    'INSERT INTO nodes (name, ownerid, netid) VALUES(?, ?, ?)',
                    array(
                        $networkdata['name'],
                        empty($networkdata['ownerid']) ? null : $networkdata['ownerid'],
                        $networkdata['id'],
                    )
                );
            }
        }

        $LMS->NetworkUpdate($networkdata);
        $SESSION->redirect('?m=netinfo&id=' . $networkdata['id']);
    }

    $network['name'] = $networkdata['name'];
    $network['interface'] = $networkdata['interface'];
    $network['vlanid'] = $networkdata['vlanid'];
    $network['prefix'] = $networkdata['prefix'];
    $network['address'] = $networkdata['address'];
    $network['size'] = $networkdata['size'];
    $network['dhcpstart'] = $networkdata['dhcpstart'];
    $network['dhcpend'] = $networkdata['dhcpend'];
    $network['domain'] = $networkdata['domain'];
    $network['gateway'] = $networkdata['gateway'];
    $network['wins'] = $networkdata['wins'];
    $network['dns'] = $networkdata['dns'];
    $network['dns2'] = $networkdata['dns2'];
    $network['notes'] = $networkdata['notes'];
    $network['hostid'] = $networkdata['hostid'];
    $network['ownerid'] = $networkdata['ownerid'];
    $network['authtype'] = $networkdata['authtype'];
    $network['snat'] = $networkdata['snat'];
    $network['snatlong'] = $networkdata['snatlong'];
    $network['pubnetid'] = $networkdata['pubnetid'];
}

$networks = $LMS->GetNetworks();

if (!ConfigHelper::checkConfig('phpui.big_networks')) {
    $SMARTY->assign('customers', $LMS->GetCustomerNames());
}

$layout['pagetitle'] = trans('Network Edit: $a', $network['name']);

$SMARTY->assign('unlockedit', true);
$SMARTY->assign('network', $network);
$SMARTY->assign('networks', $networks);
$SMARTY->assign('netlistsize', count($networks));
$SMARTY->assign('prefixlist', $LMS->GetPrefixList());
$SMARTY->assign('hostlist', $LMS->DB->GetAll('SELECT id, name FROM hosts ORDER BY name'));
$SMARTY->assign('error', $error);
$SMARTY->display('net/netinfo.html');
