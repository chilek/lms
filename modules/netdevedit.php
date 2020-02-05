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

if ($api) {
    if (!$LMS->NetDevExists($id)) {
        die;
    }

    if (!isset($_POST['in'])) {
        die;
    }
    $netdev = json_decode(base64_decode($_POST['in']), true);
} else {
    if (!$LMS->NetDevExists($id)) {
        $SESSION->redirect('?m=netdevlist');
    }

    if (isset($_POST['netdev'])) {
        $netdev = $_POST['netdev'];
    }
}

$action = !empty($_GET['action']) ? $_GET['action'] : '';
$edit = '';
$subtitle = '';

switch ($action) {
    case 'updatenodefield':
        $LMS->updateNodeField($_POST['nodeid'], $_POST['field'], $_POST['val']);
        die();
    break;

    case 'replace':
        $dev1 = $LMS->GetNetDev($_GET['id']);
        $dev2 = $LMS->GetNetDev($_GET['netdev']);

        if ($dev1['ports'] < $dev2['takenports']) {
            $error['replace'] = trans('It scans for ports in source device!');
        } elseif ($dev2['ports'] < $dev1['takenports']) {
            $error['replace'] = trans('It scans for ports in destination device!');
        }

        if (!$error) {
            $links1 = $DB->GetAll('(SELECT type, 
				(CASE src WHEN ? THEN dst ELSE src END) AS id,
				speed, technology,
				(CASE src WHEN ? THEN srcport ELSE dstport END) AS srcport,
				(CASE src WHEN ? THEN dstport ELSE srcport END) AS dstport,
				(CASE src WHEN ? THEN srcradiosector ELSE dstradiosector END) AS srcradiosector,
				(CASE src WHEN ? THEN dstradiosector ELSE srcradiosector END) AS dstradiosector
				FROM netlinks WHERE src = ? OR dst = ?)
			UNION
				(SELECT linktype AS type,
					id,
					linkspeed AS speed, linktechnology AS technology,
					port AS srcport,
					NULL AS dstport,
					linkradiosector AS srcradiosector,
					NULL AS dstradiosector
				FROM nodes WHERE netdev = ? AND ownerid IS NOT NULL)
			ORDER BY srcport', array($dev1['id'], $dev1['id'], $dev1['id'], $dev1['id'], $dev1['id'],
                    $dev1['id'], $dev1['id'], $dev1['id']));

            $links2 = $DB->GetAll('(SELECT type,
				(CASE src WHEN ? THEN dst ELSE src END) AS id,
				speed, technology,
				(CASE src WHEN ? THEN srcport ELSE dstport END) AS srcport,
				(CASE src WHEN ? THEN dstport ELSE srcport END) AS dstport,
				(CASE src WHEN ? THEN srcradiosector ELSE dstradiosector END) AS srcradiosector,
				(CASE src WHEN ? THEN dstradiosector ELSE srcradiosector END) AS dstradiosector
				FROM netlinks WHERE src = ? OR dst = ?)
			UNION
				(SELECT linktype AS type,
					id,
					linkspeed AS speed, linktechnology AS technology,
					port AS srcport,
					NULL AS dstport,
					linkradiosector AS srcradiosector,
					NULL AS dstradiosector
				FROM nodes WHERE netdev = ? AND ownerid IS NOT NULL)
			ORDER BY srcport', array($dev2['id'], $dev2['id'], $dev2['id'], $dev2['id'], $dev2['id'],
                    $dev2['id'], $dev2['id'], $dev2['id']));

            $DB->BeginTrans();

            $DB->Execute('UPDATE netdevices SET netnodeid = ?, ownerid = ?, address_id = ?, latitude = ?, longitude = ?
				WHERE id = ?', array($dev1['netnodeid'], $dev1['ownerid'], $dev1['address_id'], $dev1['latitude'], $dev1['longitude'], $dev2['id']));
            $DB->Execute('UPDATE netdevices SET netnodeid = ?, ownerid = ?, address_id = ?, latitude = ?, longitude = ?
				WHERE id = ?', array($dev2['netnodeid'], $dev2['ownerid'], $dev2['address_id'], $dev2['latitude'], $dev2['longitude'], $dev1['id']));

            if ($SYSLOG) {
                $args = array(
                    SYSLOG::RES_NETDEV => $dev2['id'],
                    'location' => $dev1['location'],
                    'latitude' => $dev1['latitude'],
                    'longitude' => $dev1['longitude'],
                );
                $SYSLOG->AddMessage(SYSLOG::RES_NETDEV, SYSLOG::OPER_UPDATE, $args);
                $args = array(
                    SYSLOG::RES_NETDEV => $dev1['id'],
                    'location' => $dev2['location'],
                    'latitude' => $dev2['latitude'],
                    'longitude' => $dev2['longitude'],
                );
                $SYSLOG->AddMessage(SYSLOG::RES_NETDEV, SYSLOG::OPER_UPDATE, $args);
            }

            $LMS->NetDevDelLinks($dev1['id']);
            $LMS->NetDevDelLinks($dev2['id']);

            $ports = array();
            // przypisujemy urzadzenia/komputer, probujac zachowac numeracje portow
            if ($links1) {
                foreach ($links1 as $row) {
                    $sport = $row['srcport'];
                    if ($sport) {
                        if ($sport > $dev2['ports']) {
                            for ($i = 1; $i <= $dev2['ports']; $i++) {
                                if (!isset($ports[$sport])) {
                                    $sport = $i;
                                    break;
                                }
                            }
                        }

                        $ports[$sport] = $sport;
                    }

                    if (isset($row['dstport'])) { // device
                        $LMS->NetDevLink($dev2['id'], $row['id'], array(
                        'type' => $row['type'],
                        'srcradiosector' => $row['srcradiosector'],
                        'dstradiosector' => $row['dstradiosector'],
                        'technology' => $row['technology'],
                        'speed' => $row['speed'],
                        'srcport' => $sport,
                        'dstport' => $row['dstport'],
                        ));
                    } else { // node
                        $LMS->NetDevLinkNode($row['id'], $dev2['id'], array(
                        'type' => $row['type'],
                        'radiosector' => $row['srcradiosector'],
                        'technology' => $row['technology'],
                        'speed' => $row['speed'],
                        'port' => $sport,
                        ));
                    }
                }
            }

            $ports = array();
            if ($links2) {
                foreach ($links2 as $row) {
                    $sport = $row['srcport'];
                    if ($sport) {
                        if ($sport > $dev1['ports']) {
                            for ($i = 1; $i <= $dev1['ports']; $i++) {
                                if (!isset($ports[$sport])) {
                                    $sport = $i;
                                    break;
                                }
                            }
                        }

                        $ports[$sport] = $sport;
                    }

                    if (isset($row['dstport'])) { // device
                        $LMS->NetDevLink($dev1['id'], $row['id'], array(
                        'type' => $row['type'],
                        'srcradiosector' => $row['srcradiosector'],
                        'dstradiosector' => $row['dstradiosector'],
                        'technology' => $row['technology'],
                        'speed' => $row['speed'],
                        'srcport' => $sport,
                        'dstport' => $row['dstport']
                        ));
                    } else { // node
                        $LMS->NetDevLinkNode($row['id'], $dev1['id'], array(
                        'type' => $row['type'],
                        'radiosector' => $row['srcradiosector'],
                        'technology' => $row['technology'],
                        'speed' => $row['speed'],
                        'port' => $sport,
                        ));
                    }
                }
            }

            $DB->CommitTrans();

            $SESSION->redirect('?m=netdevinfo&id=' . $_GET['id']);
        }

        break;

    case 'disconnect':
        $LMS->NetDevUnLink($_GET['id'], $_GET['devid']);
        $SESSION->redirect('?m=netdevinfo&id=' . $_GET['id']);
        break;
    case 'disconnectnode':
        $LMS->NetDevLinkNode($_GET['nodeid'], 0);
        $SESSION->redirect('?m=netdevinfo&id=' . $_GET['id']);
        break;
    case 'connect':
        $linktype = !empty($_GET['linktype']) ? intval($_GET['linktype']) : '0';
        $srcradiosector = ($linktype == LINKTYPE_WIRELESS ? intval($_GET['srcradiosector']) : null);
        $dstradiosector = ($linktype == LINKTYPE_WIRELESS ? intval($_GET['dstradiosector']) : null);
        $linktechnology = !empty($_GET['linktechnology']) ? intval($_GET['linktechnology']) : '0';
        $linkspeed = !empty($_GET['linkspeed']) ? intval($_GET['linkspeed']) : '100000';
        $dev['srcport'] = !empty($_GET['srcport']) ? intval($_GET['srcport']) : '0';
        $dev['dstport'] = !empty($_GET['dstport']) ? intval($_GET['dstport']) : '0';
        $dev['id'] = !empty($_GET['netdev']) ? intval($_GET['netdev']) : '0';

        $ports1 = $DB->GetOne('SELECT ports FROM netdevices WHERE id = ?', array($_GET['id']));
        $takenports1 = $LMS->CountNetDevLinks($_GET['id']);

        $ports2 = $DB->GetOne('SELECT ports FROM netdevices WHERE id = ?', array($dev['id']));
        $takenports2 = $LMS->CountNetDevLinks($dev['id']);

        if ($ports1 <= $takenports1 || $ports2 <= $takenports2) {
            $error['srcport'] = trans('No free ports on device!');
        } else {
            if ($dev['srcport']) {
                if (!preg_match('/^[0-9]+$/', $dev['srcport']) || $dev['srcport'] > $ports2) {
                    $error['srcport'] = trans('Incorrect port number!');
                } elseif ($DB->GetOne('SELECT id FROM vnodes WHERE netdev=? AND port=? AND ownerid IS NOT NULL', array($dev['id'], $dev['srcport']))
                        || $DB->GetOne('SELECT 1 FROM netlinks WHERE (src = ? OR dst = ?)
					AND (CASE src WHEN ? THEN srcport ELSE dstport END) = ?', array($dev['id'], $dev['id'], $dev['id'], $dev['srcport']))) {
                    $error['srcport'] = trans('Selected port number is taken by other device or node!');
                }
            }

            if ($dev['dstport']) {
                if (!preg_match('/^[0-9]+$/', $dev['dstport']) || $dev['dstport'] > $ports1) {
                    $error['dstport'] = trans('Incorrect port number!');
                } elseif ($DB->GetOne('SELECT id FROM vnodes WHERE netdev=? AND port=? AND ownerid IS NOT NULL', array($_GET['id'], $dev['dstport']))
                        || $DB->GetOne('SELECT 1 FROM netlinks WHERE (src = ? OR dst = ?)
					AND (CASE src WHEN ? THEN srcport ELSE dstport END) = ?', array($_GET['id'], $_GET['id'], $_GET['id'], $dev['dstport']))) {
                    $error['dstport'] = trans('Selected port number is taken by other device or node!');
                }
            }
        }

        $SESSION->save('devlinktype', $linktype);
        $SESSION->save('devlinksrcradiosector', $srcradiosector);
        $SESSION->save('devlinkdstradiosector', $dstradiosector);
        $SESSION->save('devlinktechnology', $linktechnology);
        $SESSION->save('devlinkspeed', $linkspeed);

        if (!$error) {
            $LMS->NetDevLink($dev['id'], $_GET['id'], array(
                'type' => $linktype,
                'srcradiosector' => $srcradiosector,
                'dstradiosector' => $dstradiosector,
                'technology' => $linktechnology,
                'speed' => $linkspeed,
                'srcport' => $dev['srcport'],
                'dstport' => $dev['dstport'],
            ));
            $SESSION->redirect('?m=netdevinfo&id=' . $_GET['id']);
        }

        $SMARTY->assign('connect', $dev);

        break;

    case 'connectnode':
        $linktype = !empty($_GET['linktype']) ? intval($_GET['linktype']) : '0';
        $linkradiosector = ($linktype == 1 ? intval($_GET['radiosector']) : null);
        $linktechnology = !empty($_GET['linktechnology']) ? intval($_GET['linktechnology']) : '0';
        $linkspeed = !empty($_GET['linkspeed']) ? intval($_GET['linkspeed']) : '0';
        $node['port'] = !empty($_GET['port']) ? intval($_GET['port']) : '0';
        $node['id'] = !empty($_GET['nodeid']) ? intval($_GET['nodeid']) : '0';

        $ports = $DB->GetOne('SELECT ports FROM netdevices WHERE id = ?', array($_GET['id']));
        $takenports = $LMS->CountNetDevLinks($_GET['id']);

        if ($ports <= $takenports) {
            $error['linknode'] = trans('No free ports on device!');
        } elseif ($node['port']) {
            if (!preg_match('/^[0-9]+$/', $node['port']) || $node['port'] > $ports) {
                $error['port'] = trans('Incorrect port number!');
            } elseif ($DB->GetOne('SELECT id FROM vnodes WHERE netdev=? AND port=? AND ownerid IS NOT NULL', array($_GET['id'], $node['port']))
                    || $DB->GetOne('SELECT 1 FROM netlinks WHERE (src = ? OR dst = ?)
				AND (CASE src WHEN ? THEN srcport ELSE dstport END) = ?', array($_GET['id'], $_GET['id'], $_GET['id'], $node['port']))) {
                $error['port'] = trans('Selected port number is taken by other device or node!');
            }
        }

        $SESSION->save('nodelinktype', $linktype);
        $SESSION->save('nodelinkradiosector', $linkradiosector);
        $SESSION->save('nodelinktechnology', $linktechnology);
        $SESSION->save('nodelinkspeed', $linkspeed);

        if (!$error) {
            $LMS->NetDevLinkNode($node['id'], $_GET['id'], array(
                'type' => $linktype,
                'radiosector' => $linkradiosector,
                'technology' => $linktechnology,
                'speed' => $linkspeed,
                'port' => $node['port']
            ));
            $SESSION->redirect('?m=netdevinfo&id=' . $_GET['id']);
        }

        $SMARTY->assign('connectnode', $node);

        break;

    case 'addip':
        $subtitle = trans('New IP address');
        $nodeipdata['access'] = 1;
        $nodeipdata['macs'] = array(0 => '');
        $SMARTY->assign('nodeipdata', $nodeipdata);
        $edit = 'addip';
        break;

    case 'editip':
        $nodeipdata = $LMS->GetNode($_GET['ip']);
        $nodeipdata['ipaddr'] = $nodeipdata['ip'];
        $nodeipdata['ipaddr_pub'] = $nodeipdata['ip_pub'];
        $subtitle = trans('IP address edit');
        $macs = array();
        foreach ($nodeipdata['macs'] as $key => $value) {
            $macs[] = $nodeipdata['macs'][$key]['mac'];
        }
        $nodeipdata['macs'] = $macs;
        $SMARTY->assign('nodeipdata', $nodeipdata);
        $edit = 'ip';
        break;

    case 'ipdel':
        if (!empty($_GET['ip'])) {
            if ($SYSLOG) {
                $args = array(
                    SYSLOG::RES_NODE => $_GET['ip'],
                    SYSLOG::RES_NETDEV => $_GET['id'],
                );
                $SYSLOG->AddMessage(SYSLOG::RES_NODE, SYSLOG::OPER_UPDATE, $args);
            }
            $DB->Execute('DELETE FROM nodes WHERE id = ? AND ownerid IS NULL', array($_GET['ip']));
        }

        $SESSION->redirect('?m=netdevinfo&id=' . $_GET['id']);
        break;

    case 'ipset':
        if (!empty($_GET['ip'])) {
            if ($SYSLOG) {
                $access = $DB->GetOne(
                    'SELECT access FROM vnodes WHERE id = ? AND ownerid IS NULL',
                    array($_GET['ip'])
                );
                $args = array(
                    SYSLOG::RES_NODE => $_GET['ip'],
                    SYSLOG::RES_NETDEV => $_GET['id'],
                    'access' => intval(!$access),
                );
                $SYSLOG->AddMessage(SYSLOG::RES_NODE, SYSLOG::OPER_UPDATE, $args);
            }
            $DB->Execute('UPDATE nodes SET access = (CASE access WHEN 1 THEN 0 ELSE 1 END)
				WHERE id = ? AND ownerid IS NULL', array($_GET['ip']));
        } else {
            $LMS->IPSetU($_GET['id'], $_GET['access']);
        }

        header('Location: ?' . $SESSION->get('backto'));
        break;

    case 'formaddip':
        $subtitle = trans('New IP address');
        $nodeipdata = $_POST['ipadd'];
        $nodeipdata['ownerid'] = null;
        foreach ($nodeipdata['macs'] as $key => $value) {
            $nodeipdata['macs'][$key] = str_replace('-', ':', $value);
        }

        $nodeipdata = trim_rec($nodeipdata);

        if ($nodeipdata['ipaddr'] == '' && $nodeipdata['name'] == '' && $nodeipdata['passwd'] == '') {
            $SESSION->redirect('?m=netdevedit&action=addip&id=' . $_GET['id']);
        }

        if ($nodeipdata['name'] == '') {
            $error['ipname'] = trans('Address field is required!');
        } elseif (strlen($nodeipdata['name']) > 32) {
            $error['ipname'] = trans('Specified name is too long (max. $a characters)!', '32');
        } elseif ($LMS->GetNodeIDByName($nodeipdata['name']) || $LMS->GetNodeIDByNetName($nodeipdata['name'])) {
            $error['ipname'] = trans('Specified name is in use!');
        } elseif (!preg_match('/^[_a-z0-9-]+$/i', $nodeipdata['name'])) {
            $error['ipname'] = trans('Name contains forbidden characters!');
        }

        if ($nodeipdata['ipaddr'] == '') {
            $error['ipaddr'] = trans('IP address is required!');
        } elseif (!check_ip($nodeipdata['ipaddr'])) {
            $error['ipaddr'] = trans('Incorrect IP address!');
        } elseif (!$LMS->IsIPValid($nodeipdata['ipaddr'])) {
            $error['ipaddr'] = trans('Specified address does not belong to any network!');
        } else {
            if (empty($nodeipdata['netid'])) {
                $nodeipdata['netid'] = $DB->GetOne(
                    'SELECT id FROM networks WHERE INET_ATON(?) & INET_ATON(mask) = address ORDER BY id LIMIT 1',
                    array($nodeipdata['ipaddr'])
                );
            }
            if (!$LMS->IsIPInNetwork($nodeipdata['ipaddr'], $nodeipdata['netid'])) {
                $error['ipaddr'] = trans('Specified IP address doesn\'t belong to selected network!');
            } else if (!$LMS->IsIPFree($nodeipdata['ipaddr'], $nodeipdata['netid'])) {
                $error['ipaddr'] = trans('Specified IP address is in use!');
            } else if ($LMS->IsIPGateway($nodedata['ipaddr'])) {
                $error['ipaddr'] = trans('Specified IP address is network gateway!');
            }
        }

        if ($nodeipdata['ipaddr_pub'] != '0.0.0.0' && $nodeipdata['ipaddr_pub'] != '') {
            if (!check_ip($nodeipdata['ipaddr_pub'])) {
                $error['ipaddr_pub'] = trans('Incorrect IP address!');
            } elseif (!$LMS->IsIPValid($nodeipdata['ipaddr_pub'])) {
                $error['ipaddr_pub'] = trans('Specified address does not belongs to any network!');
            } elseif (!$LMS->IsIPFree($nodeipdata['ipaddr_pub'])) {
                $error['ipaddr_pub'] = trans('Specified IP address is in use!');
            }
        } else {
            $nodeipdata['ipaddr_pub'] = '0.0.0.0';
        }

        $macs = array();
        foreach ($nodeipdata['macs'] as $key => $value) {
            if (check_mac($value)) {
                if ($value != '00:00:00:00:00:00' && !ConfigHelper::checkConfig('phpui.allow_mac_sharing')) {
                    if ($LMS->GetNodeIDByMAC($value)) {
                        $error['mac' . $key] = trans('MAC address is in use!');
                    }
                }
                $macs[] = $value;
            } elseif ($value != '') {
                $error['mac' . $key] = trans('Incorrect MAC address!');
            }
        }
        $nodeipdata['macs'] = $macs;

        if (strlen($nodeipdata['passwd']) > 32) {
            $error['passwd'] = trans('Password is too long (max.32 characters)!');
        }

        if (!isset($nodeipdata['chkmac'])) {
            $nodeipdata['chkmac'] = 0;
        }
        if (!isset($nodeipdata['halfduplex'])) {
            $nodeipdata['halfduplex'] = 0;
        }
        if (!isset($nodeipdata['nas'])) {
            $nodeipdata['nas'] = 0;
        }

        $authtype = 0;
        if (isset($nodeipdata['authtype'])) {
            foreach ($nodeipdata['authtype'] as $value) {
                $authtype |= intval($value);
            }
        }
        $nodeipdata['authtype'] = $authtype;

        if (!$error) {
            $nodeipdata['warning'] = 0;
            $nodeipdata['location'] = '';
            $nodeipdata['netdev'] = $_GET['id'];

            $LMS->NodeAdd($nodeipdata);
            $SESSION->redirect('?m=netdevinfo&id=' . $_GET['id']);
        }

        $SMARTY->assign('nodeipdata', $nodeipdata);
        $edit = 'addip';
        break;

    case 'formeditip':
        $subtitle = trans('IP address edit');
        $nodeipdata = $_POST['ipadd'];
        $nodeipdata['ownerid'] = null;
        foreach ($nodeipdata['macs'] as $key => $value) {
            $nodeipdata['macs'][$key] = str_replace('-', ':', $value);
        }

        foreach ($nodeipdata as $key => $value) {
            if (!is_array($value)) {
                $nodeipdata[$key] = trim($value);
            }
        }

        if ($nodeipdata['ipaddr'] == '' && $nodeipdata['name'] == '' && $nodeipdata['passwd'] == '') {
            $SESSION->redirect('?m=netdevedit&action=editip&id=' . $_GET['id'] . '&ip=' . $_GET['ip']);
        }

        if ($nodeipdata['name'] == '') {
            $error['ipname'] = trans('Address field is required!');
        } elseif (strlen($nodeipdata['name']) > 32) {
            $error['ipname'] = trans('Specified name is too long (max. $a characters)!', '32');
        } elseif (($LMS->GetNodeIDByName($nodeipdata['name']) || $LMS->GetNodeIDByNetName($nodeipdata['name']))
                && strtoupper($LMS->GetNodeName($_GET['ip'])) != strtoupper($nodeipdata['name'])) {
            $error['ipname'] = trans('Specified name is in use!');
        } elseif (!preg_match('/^[_a-z0-9-]+$/i', $nodeipdata['name'])) {
            $error['ipname'] = trans('Name contains forbidden characters!');
        }

        if ($nodeipdata['ipaddr'] == '') {
            $error['ipaddr'] = trans('IP address is required!');
        } elseif (!check_ip($nodeipdata['ipaddr'])) {
            $error['ipaddr'] = trans('Incorrect IP address!');
        } elseif (!$LMS->IsIPValid($nodeipdata['ipaddr'])) {
            $error['ipaddr'] = trans('Specified address does not belong to any network!');
        } else {
            if (empty($nodeipdata['netid'])) {
                $nodeipdata['netid'] = $DB->GetOne(
                    'SELECT id FROM networks WHERE INET_ATON(?) & INET_ATON(mask) = address ORDER BY id LIMIT 1',
                    array($nodeipdata['ipaddr'])
                );
            }
            if (!$LMS->IsIPInNetwork($nodeipdata['ipaddr'], $nodeipdata['netid'])) {
                $error['ipaddr'] = trans('Specified IP address doesn\'t belong to selected network!');
            } else if (!$LMS->IsIPFree($nodeipdata['ipaddr'], $nodeipdata['netid']) &&
                $LMS->GetNodeIPByID($_GET['ip']) != $nodeipdata['ipaddr']) {
                $error['ipaddr'] = trans('IP address is in use!');
            } else if ($LMS->IsIPGateway($nodedata['ipaddr'])) {
                $error['ipaddr'] = trans('Specified IP address is network gateway!');
            }
        }

        if ($nodeipdata['ipaddr_pub'] != '0.0.0.0' && $nodeipdata['ipaddr_pub'] != '') {
            if (check_ip($nodeipdata['ipaddr_pub'])) {
                if ($LMS->IsIPValid($nodeipdata['ipaddr_pub'])) {
                    $ip = $LMS->GetNodePubIPByID($nodeipdata['id']);
                    if ($ip != $nodeipdata['ipaddr_pub'] && !$LMS->IsIPFree($nodeipdata['ipaddr_pub'])) {
                        $error['ipaddr_pub'] = trans('Specified IP address is in use!');
                    }
                } else {
                    $error['ipaddr_pub'] = trans('Specified IP address doesn\'t overlap with any network!');
                }
            } else {
                $error['ipaddr_pub'] = trans('Incorrect IP address!');
            }
        } else {
            $nodeipdata['ipaddr_pub'] = '0.0.0.0';
        }

        $macs = array();
        foreach ($nodeipdata['macs'] as $key => $value) {
            if (check_mac($value)) {
                if ($value != '00:00:00:00:00:00' && !ConfigHelper::checkValue(ConfigHelper::getConfig('phpui.allow_mac_sharing', true))) {
                    if (($nodeid = $LMS->GetNodeIDByMAC($value)) != null && $nodeid != $_GET['ip']) {
                        $error['mac' . $key] = trans('MAC address is in use!');
                    }
                }
                $macs[] = $value;
            } elseif ($value != '') {
                $error['mac' . $key] = trans('Incorrect MAC address!');
            }
        }
        $nodeipdata['macs'] = $macs;

        if (strlen($nodeipdata['passwd']) > 32) {
            $error['passwd'] = trans('Password is too long (max.32 characters)!');
        }

        if (!isset($nodeipdata['chkmac'])) {
            $nodeipdata['chkmac'] = 0;
        }
        if (!isset($nodeipdata['halfduplex'])) {
            $nodeipdata['halfduplex'] = 0;
        }
        if (!isset($nodeipdata['nas'])) {
            $nodeipdata['nas'] = 0;
        }

        $authtype = 0;
        if (isset($nodeipdata['authtype'])) {
            foreach ($nodeipdata['authtype'] as $value) {
                $authtype |= intval($value);
            }
        }
        $nodeipdata['authtype'] = $authtype;

        if (!$error) {
            $nodeipdata['warning'] = 0;
            $nodeipdata['location'] = '';
            $nodeipdata['netdev'] = $_GET['id'];

            $LMS->NodeUpdate($nodeipdata);
            $SESSION->redirect('?m=netdevinfo&id=' . $_GET['id']);
        }

        $SMARTY->assign('nodeipdata', $nodeipdata);
        $edit = 'ip';
        break;
    case 'authtype':
        $DB->Execute('UPDATE nodes SET authtype=? WHERE id=?', array($_GET['authtype'], $_GET['ip']));
        if ($SYSLOG) {
            $args = array(
                SYSLOG::RES_NODE => $_GET['ip'],
                SYSLOG::RES_CUST => $customerid,
                'authtype' => intval($_GET['authtype']),
            );
            $SYSLOG->AddMessage(SYSLOG::RES_NODE, SYSLOG::OPER_UPDATE, $args);
        }
        $SESSION->redirect('?m=netdevinfo&id=' . $_GET['id'].'&ip='.$_GET['ip']);
        break;
    default:
        $edit = 'data';
        break;
}

if (isset($netdev)) {
    $netdev['id'] = $id;

    if ($netdev['name'] == '') {
        $error['name'] = trans('Device name is required!');
    } elseif (strlen($netdev['name']) > 60) {
        $error['name'] = trans('Specified name is too long (max. $a characters)!', '60');
    }

    $netdev['ports'] = intval($netdev['ports']);

    if ($netdev['ports'] < $LMS->CountNetDevLinks($id)) {
        $error['ports'] = trans('Connected devices number exceeds number of ports!');
    }

    if (!empty($netdev['ownerid']) && !$LMS->CustomerExists($netdev['ownerid'])) {
        $error['ownerid'] = trans('Customer doesn\'t exist!');
    }

    if (!$api) {
        $netdev['clients'] = (empty($netdev['clients'])) ? 0 : intval($netdev['clients']);

        $netdev['purchasetime'] = intval($netdev['purchasetime']);
        if ($netdev['purchasetime'] && time() < $netdev['purchasetime']) {
            $error['purchasetime'] = trans('Date from the future not allowed!');
        }

        if ($netdev['guaranteeperiod'] != 0 && !$netdev['purchasetime']) {
            $error['purchasetime'] = trans('Purchase date cannot be empty when guarantee period is set!');
        }
    }

    if (!strlen($netdev['projectid']) && !empty($netdev['project'])) {
        $project = $LMS->GetProjectByName($netdev['project']);
        if (empty($project)) {
            $netdev['projectid'] = -1;
        } else {
            $netdev['projectid'] = $project['id'];
        }
    }

    $hook_data = $LMS->executeHook(
        'netdevedit_validation_before_submit',
        array(
            'netdevdata' => $netdev,
            'error' => $error
        )
    );
    $netdev = $hook_data['netdevdata'];
    $error = $hook_data['error'];

    if (!$error) {
        if (!$api) {
            if ($netdev['guaranteeperiod'] == -1) {
                $netdev['guaranteeperiod'] = null;
            }

            if (!isset($netdev['shortname'])) {
                $netdev['shortname'] = '';
            }
            if (!isset($netdev['login'])) {
                $netdev['login'] = '';
            }
            if (!isset($netdev['secret'])) {
                $netdev['secret'] = '';
            }
            if (!isset($netdev['community'])) {
                $netdev['community'] = '';
            }
            if (!isset($netdev['nastype'])) {
                $netdev['nastype'] = 0;
            }
        }

        if ($netdev['projectid'] == -1) {
            $netdev['projectid'] = $LMS->AddProject($netdev);
        } elseif (empty($netdev['projectid'])) {
            $netdev['projectid'] = null;
        }

        // no net node selected
        if ($netdev['netnodeid'] == '-1') {
            $netdev['netnodeid'] = null;
        }

        $result = $LMS->NetDevUpdate($netdev);
        $LMS->CleanupProjects();

        if ($api) {
            if ($result) {
                header('Content-Type: application-json');
                echo json_encode(array('id' => $id));
            }
            die;
        }

        $hook_data = $LMS->executeHook(
            'netdevedit_after_update',
            array(
                'smarty' => $SMARTY,
                'netdevdata' => $netdev,
            )
        );
        $SESSION->redirect('?m=netdevinfo&id=' . $id);
    } elseif ($api) {
        header('Content-Type: application-json');
        echo json_encode($error);
        die;
    }
} else {
    $attachmenttype = 'netdevid';
    $attachmentresourceid = $id;

    $netdev = $LMS->GetNetDev($id);

    if (preg_match('/^[0-9]+$/', $netdev['producerid'])
        && preg_match('/^[0-9]+$/', $netdev['modelid'])) {
        $netdev['producer'] = $netdev['producerid'];
        $netdev['model'] = $netdev['modelid'];
    }

    if ($netdev['model']) {
        $attachmenttype_model = 'netdevmodelid';
        $attachmentresourceid_model = $netdev['model'];
    }

    include(MODULES_DIR . DIRECTORY_SEPARATOR . 'attachments.php');

    if ($netdev['purchasetime']) {
        $netdev['purchasedate'] = date('Y/m/d', $netdev['purchasetime']);
    }

    if (($netdev['location_city'] || $netdev['location_street']) && !$netdev['ownerid']) {
        $netdev['teryt'] = true;
    }
}

$netdev['id'] = $id;

$netdevips       = $LMS->GetNetDevIPs($id);
if ($netdev['ports'] > $netdev['takenports']) {
    $nodelist        = $LMS->GetUnlinkedNodes();
}
$netdevconnected = $LMS->GetNetDevConnectedNames($id);
$netcomplist     = $LMS->GetNetDevLinkedNodes($id);
$netdevlist      = $LMS->GetNotConnectedDevices($id);

unset($netdevlist['total']);
unset($netdevlist['order']);
unset($netdevlist['direction']);

if ($netdev['producer']) {
    $layout['pagetitle'] = trans('Device Edit: $a ($b)', $netdev['name'], $netdev['producer']);
} else {
    $layout['pagetitle'] = trans('Device Edit: $a', $netdev['name']);
}

$hook_data = $LMS->executeHook(
    'netdevedit_before_display',
    array(
        'netdevdata' => $netdev,
        'smarty' => $SMARTY,
    )
);
$netdev = $hook_data['netdevdata'];

if ($subtitle) {
    $layout['pagetitle'] .= ' - ' . $subtitle;
}

$SMARTY->assign('NNprojects', $LMS->GetProjects());
$SMARTY->assign('NNnodes', $LMS->GetNetNodes());
$SMARTY->assign('producers', $LMS->GetProducers());
$SMARTY->assign('models', $LMS->GetModels());

$SMARTY->assign('error', $error);
$SMARTY->assign('netdev', $netdev);
$SMARTY->assign('objectid', $netdev['id']);
$SMARTY->assign('netdevlist', $netdevconnected);
$SMARTY->assign('netcomplist', $netcomplist);
$SMARTY->assign('nodelist', $nodelist);
$SMARTY->assign('mgmurls', $LMS->GetManagementUrls(LMSNetDevManager::NETDEV_URL, $netdev['id']));
$SMARTY->assign('radiosectors', $LMS->GetRadioSectors($netdev['id']));
$SMARTY->assign('netdevcontype', $netdevcontype);
$SMARTY->assign('netdevauthtype', $netdevauthtype);
$SMARTY->assign('netdevips', $netdevips);
$SMARTY->assign('restnetdevlist', $netdevlist);
$SMARTY->assign('devlinktype', $SESSION->get('devlinktype'));
$SMARTY->assign('devlinksrcradiosector', $SESSION->get('devlinksrcradiosector'));
$SMARTY->assign('devlinkdstradiosector', $SESSION->get('devlinkdstradiosector'));
$SMARTY->assign('devlinktechnology', $SESSION->get('devlinktechnology'));
$SMARTY->assign('devlinkspeed', $SESSION->get('devlinkspeed'));
$SMARTY->assign('nodelinktype', $SESSION->get('nodelinktype'));
$SMARTY->assign('nodelinkradiosector', $SESSION->get('nodelinkradiosector'));
$SMARTY->assign('nodelinktechnology', $SESSION->get('nodelinktechnology'));
$SMARTY->assign('nodelinkspeed', $SESSION->get('nodelinkspeed'));
$SMARTY->assign('nastypes', $LMS->GetNAStypes());

if (!ConfigHelper::checkConfig('phpui.big_networks')) {
    $SMARTY->assign('customers', $LMS->GetCustomerNames());
}

$LMS->InitXajax();
include(MODULES_DIR . DIRECTORY_SEPARATOR . 'netdevxajax.inc.php');
include(MODULES_DIR . DIRECTORY_SEPARATOR . 'geocodexajax.inc.php');
$SMARTY->assign('xajax', $LMS->RunXajax());

switch ($edit) {
    case 'data':
        if (ConfigHelper::checkConfig('phpui.ewx_support')) {
            $SMARTY->assign('channels', $DB->GetAll('SELECT id, name FROM ewx_channels ORDER BY name'));
        }

        $SMARTY->assign('netdevedit_sortable_order', $SESSION->get_persistent_setting('netdevedit-sortable-order'));
        $SMARTY->display('netdev/netdevedit.html');
        break;
    case 'ip':
        $SMARTY->assign('networks', $LMS->GetNetworks(true));
        $SMARTY->assign('nodesessions', $LMS->GetNodeSessions($_GET['ip']));
        $SMARTY->assign('netdevvipedit_sortable_order', $SESSION->get_persistent_setting('netdevipedit-sortable-order'));
        $SMARTY->display('netdev/netdevipedit.html');
        break;
    case 'addip':
        $SMARTY->assign('networks', $LMS->GetNetworks(true));
        $SMARTY->assign('netdevvipadd_sortable_order', $SESSION->get_persistent_setting('netdevipadd-sortable-order'));
        $SMARTY->display('netdev/netdevipadd.html');
        break;
    default:
        $SMARTY->display('netdev/netdevinfo.html');
        break;
}
