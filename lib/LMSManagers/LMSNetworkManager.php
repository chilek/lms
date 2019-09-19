<?php

/*
 *  LMS version 1.11-git
 *
 *  Copyright (C) 2001-2018 LMS Developers
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

/**
 * LMSNetworkManager
 *
 */
class LMSNetworkManager extends LMSManager implements LMSNetworkManagerInterface
{

    public function NetworkExists($id)
    {
        return ($this->db->GetOne('SELECT * FROM networks WHERE id=?', array($id)) ? true : false);
    }

    public function NetworkSet($id, $disabled = -1)
    {
        if ($this->syslog) {
            $args = array(
                SYSLOG::RES_NETWORK => $id,
                SYSLOG::RES_HOST =>
                $this->db->GetOne('SELECT hostid FROM networks WHERE id = ?', array($id)),
            );
        }
        $res = null;
        if ($disabled != -1) {
            if ($this->syslog) {
                $args['disabled'] = intval($disabled == 1);
            }
            if ($disabled == 1) {
                $res = $this->db->Execute('UPDATE networks SET disabled = 1 WHERE id = ?', array($id));
            } else {
                $res = $this->db->Execute('UPDATE networks SET disabled = 0 WHERE id = ?', array($id));
            }
        } elseif ($this->db->GetOne('SELECT disabled FROM networks WHERE id = ?', array($id)) == 1) {
            if ($this->syslog) {
                $args['disabled'] = 0;
            }
            $res = $this->db->Execute('UPDATE networks SET disabled = 0 WHERE id = ?', array($id));
        } else {
            if ($this->syslog) {
                $args['disabled'] = 1;
            }
            $res = $this->db->Execute('UPDATE networks SET disabled = 1 WHERE id = ?', array($id));
        }
        if ($this->syslog && $res) {
            $this->syslog->AddMessage(SYSLOG::RES_NETWORK, SYSLOG::OPER_UPDATE, $args);
        }
        return $res;
    }

    public function IsIPFree($ip, $netid = 0)
    {
        if ($netid) {
            return !($this->db->GetOne('SELECT id FROM vnodes WHERE (ipaddr=inet_aton(?) AND netid=?) OR ipaddr_pub=inet_aton(?)', array($ip, $netid, $ip)) ? true : false);
        } else {
            return !($this->db->GetOne('SELECT id FROM vnodes WHERE ipaddr=inet_aton(?) OR ipaddr_pub=inet_aton(?)', array($ip, $ip)) ? true : false);
        }
    }

    public function IsIPInNetwork($ip, $netid)
    {
        return $this->db->GetOne('SELECT id FROM networks WHERE INET_ATON(?) & INET_ATON(mask) = address AND id = ? LIMIT 1', array($ip, $netid));
    }

    public function IsIPGateway($ip)
    {
        return ($this->db->GetOne('SELECT gateway FROM networks WHERE gateway = ?', array($ip)) ? true : false);
    }

    public function GetPrefixList()
    {
        for ($i = 30; $i > 15; $i--) {
            $prefixlist['id'][] = $i;
            $prefixlist['value'][] = trans('$a ($b addresses)', $i, pow(2, 32 - $i));
        }

        return $prefixlist;
    }

    public function NetworkAdd($netadd)
    {
        if ($netadd['prefix'] != '') {
            $netadd['mask'] = prefix2mask($netadd['prefix']);
        }

        $netadd['name'] = strtoupper($netadd['name']);

        $args = array(
            'name' => $netadd['name'],
            'address' => $netadd['address'],
            'mask' => $netadd['mask'],
            'interface' => strtolower($netadd['interface']),
            'gateway' => $netadd['gateway'],
            'dns' => $netadd['dns'],
            'dns2' => $netadd['dns2'],
            'domain' => $netadd['domain'],
            'wins' => $netadd['wins'],
            'dhcpstart' => $netadd['dhcpstart'],
            'dhcpend' => $netadd['dhcpend'],
            'notes' => $netadd['notes'],
            'vlanid' => intval($netadd['vlanid']),
            SYSLOG::RES_HOST => $netadd['hostid'],
            'authtype' => $netadd['authtype'],
            'snat' => !empty($netadd['snat']) ? $netadd['snat'] : null,
            'pubnetid' => empty($netadd['pubnetid']) ? null : $netadd['pubnetid'],
        );
        if ($this->db->Execute('INSERT INTO networks (name, address, mask, interface, gateway,
				dns, dns2, domain, wins, dhcpstart, dhcpend, notes, vlanid, hostid, authtype, snat, pubnetid)
				VALUES (?, inet_aton(?), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, inet_aton(?), ?)', array_values($args))) {
            $netid = $this->db->GetLastInsertID('networks');
            if (!$netid) {
                return false;
            }
            if ($this->syslog) {
                $this->syslog->AddMessage(SYSLOG::RES_NETWORK, SYSLOG::OPER_ADD, $args);
            }
            if ($netadd['ownerid']) {
                $args = array(
                    'name' => $netadd['name'],
                    SYSLOG::RES_CUST => $netadd['ownerid'],
                    SYSLOG::RES_NETWORK => $netid,
                );
                $this->db->Execute('INSERT INTO nodes (name, ownerid, netid) VALUES(?, ?, ?)', array_values($args));
                if ($this->syslog) {
                    $this->syslog->AddMessage(SYSLOG::RES_NODE, SYSLOG::OPER_ADD, $args);
                }
            }
            return $netid;
        } else {
            return false;
        }
    }

    public function NetworkDelete($id)
    {
        if ($this->syslog) {
            $hostid = $this->db->GetOne('SELECT hostid FROM networks WHERE id=?', array($id));
        }
        $res = $this->db->Execute('DELETE FROM networks WHERE id=?', array($id));
        if ($this->syslog && $res) {
            $args = array(
                SYSLOG::RES_NETWORK => $id,
                SYSLOG::RES_HOST => $hostid,
            );
            $this->syslog->AddMessage(SYSLOG::RES_NETWORK, SYSLOG::OPER_DELETE, $args);
        }
        return $res;
    }

    public function GetNetworkName($id)
    {
        return $this->db->GetOne('SELECT name FROM networks WHERE id=?', array($id));
    }

    /**
     * Returns network parameters
     *
     * @param int $id Network id
     * @return array Network parameters
     */
    public function getNetworkParams($id)
    {
        return $this->db->GetRow(
            'SELECT *, inet_ntoa(address) AS netip, broadcast(address, inet_aton(mask)) AS broadcast
            FROM networks WHERE id ' . (is_array($id) ? 'IN' : '=') . ' ?',
            array($id)
        );
    }

    /**
     * Returns networks
     *
     * @param boolean $with_disabled With disabled (default true)
     * @return array Networks
     */
    public function GetNetworks($with_disabled = true)
    {
        if ($with_disabled == false) {
            return $this->db->GetAll('SELECT id, name, inet_ntoa(address) AS address, 
				address AS addresslong, mask, mask2prefix(inet_aton(mask)) AS prefix, disabled 
				FROM networks WHERE disabled=0 ORDER BY name');
        } else {
            return $this->db->GetAll('SELECT id, name, inet_ntoa(address) AS address, 
				address AS addresslong, mask, mask2prefix(inet_aton(mask)) AS prefix, disabled 
				FROM networks ORDER BY name');
        }
    }

    public function GetNetIDByIP($ipaddr)
    {
        return $this->db->GetOne('SELECT id FROM networks 
				WHERE address = (inet_aton(?) & inet_aton(mask))', array($ipaddr));
    }

    public function GetUnlinkedNodes()
    {
        return $this->db->GetAll('SELECT n.id, n.name, inet_ntoa(n.ipaddr) AS ip, net.name AS netname
			FROM vnodes n
			JOIN networks net ON net.id = n.netid
			WHERE netdev IS NULL ORDER BY name ASC');
    }

    public function GetNetDevIPs($id)
    {
        $result = $this->db->GetAll('SELECT n.id, n.name, mac, ipaddr, inet_ntoa(ipaddr) AS ip, 
			ipaddr_pub, inet_ntoa(ipaddr_pub) AS ip_pub, access, info, port, n.netid, net.name AS netname, n.authtype,
			n.lastonline
			FROM vnodes n
			JOIN networks net ON net.id = n.netid
			WHERE ownerid IS NULL AND netdev = ?', array($id));

        if ($result) {
            foreach ($result as &$node) {
                $node['lastonlinedate'] = lastonline_date($node['lastonline']);
            }
            unset($node);
        }

        return $result;
    }

    public function GetNetworkList(array $search)
    {
        if (!is_array($search)) {
            $search = array(
                'order' => 'id,asc',
            );
        }

        $order = isset($search['order']) && !empty($search['order']) ? $search['order'] : 'id,asc';

        list($order, $direction) = sscanf($order, '%[^,],%s');

        ($direction == 'desc') ? $direction = 'desc' : $direction = 'asc';

        switch ($order) {
            case 'name':
                $sqlord = ' ORDER BY n.name';
                break;
            case 'id':
                $sqlord = ' ORDER BY n.id';
                break;
            case 'address':
                $sqlord = ' ORDER BY n.address';
                break;
            case 'mask':
                $sqlord = ' ORDER BY n.mask';
                break;
            case 'interface':
                $sqlord = ' ORDER BY n.interface';
                break;
            case 'host':
                $sqlord = ' ORDER BY hostname';
                break;
            case 'size':
                $sqlord = ' ORDER BY size';
                break;
            case 'assigned':
                $sqlord = ' ORDER BY assigned';
                break;
            case 'online':
                $sqlord = ' ORDER BY online';
                break;
            case 'vlanid':
                $sqlord = ' ORDER BY vlanid';
                break;
        }

        $search = array_filter($search);
        $sqlwhere = ' WHERE';

        if (isset($search['compareType'])) {
            $p = '%';
            $search['compareType'] = 'LIKE';
        } else {
            $p = '';
            $search['compareType'] = '=';
        }

        foreach ($search as $k => $v) {
            if ($v != '') {
                switch ($k) {
                    case 'network_name':
                        $sqlwhere .= " lower(n.name) ?LIKE? lower('" . $p.$v.$p . "') " . $search['operatorType'];
                        break;

                    case 'network_address':
                        $sqlwhere .= " inet_ntoa(address) " . $search['compareType'] . " lower('" . $p.$v.$p . "') " . $search['operatorType'];
                        break;

                    case 'dhcp':
                        $sqlwhere .= " '$v' BETWEEN n.dhcpstart AND n.dhcpend " . $search['operatorType'];
                        break;

                    case 'size':
                        $sqlwhere .= " $v ". $search['size_compare_char'] . " pow(2, 32 - mask2prefix(inet_aton(n.mask))) " . $search['operatorType'];
                        break;

                    case 'interface':
                        $sqlwhere .= " lower(n.interface) ?LIKE? lower('" . $p.$v.$p . "') " . $search['operatorType'];
                        break;

                    case 'vlanid':
                        $sqlwhere .= " vlanid = " . $v . " " . $search['operatorType'];
                        break;

                    case 'gateway':
                        $sqlwhere .= " n.gateway " . $search['compareType'] . " '" . $p.$v.$p . "' " . $search['operatorType'];
                        break;

                    case 'dns':
                        $sqlwhere .= " (n.dns " . $search['compareType'] . " '" . $p.$v.$p . "' OR n.dns2 " . $search['compareType'] . " '" . $p.$v.$p . "') " . $search['operatorType'];
                        break;

                    case 'wins':
                        $sqlwhere .= " n.wins " . $search['compareType'] . " '" . $p.$v.$p . "' " . $search['operatorType'];
                        break;

                    case 'domain':
                        $sqlwhere .= " lower(n.domain) " . $search['compareType'] . " lower('" . $p.$v.$p . "') " . $search['operatorType'];
                        break;

                    case 'host':
                        $sqlwhere .= " 1 IN (SELECT 1 FROM hosts WHERE name ?LIKE? '" . $p.$v.$p . "') " . $search['operatorType'];
                        break;

                    case 'description':
                        $sqlwhere .= " lower(n.notes) ?LIKE? lower('" . $p.$v.$p . "') " . $search['operatorType'];
                        break;
                }
            }
        }
        $sqlwhere = rtrim($sqlwhere, $search['operatorType']);

        $count = isset($search['count']) && !empty($search['count']);

        if ($count) {
            return $this->db->GetOne('SELECT COUNT(n.id)
				FROM networks n
				LEFT JOIN hosts h ON h.id = n.hostid
				' . ($sqlwhere != ' WHERE' ? $sqlwhere : ''));
        }

        $networks = $this->db->GetAll(
            'SELECT 
				n.id, h.name AS hostname, n.name, inet_ntoa(address) AS address, 
				address AS addresslong, mask, interface, gateway, dns, dns2, 
				domain, wins, dhcpstart, dhcpend,
				mask2prefix(inet_aton(mask)) AS prefix,
				broadcast(address, inet_aton(mask)) AS broadcastlong, vlanid,
				inet_ntoa(broadcast(address, inet_aton(mask))) AS broadcast,
				pow(2,(32 - mask2prefix(inet_aton(mask)))) AS size, disabled,
				(SELECT COUNT(*) 
					FROM nodes 
					WHERE netid = n.id AND ipaddr <> 0 AND (ipaddr >= address AND ipaddr <= broadcast(address, inet_aton(mask))) 
						OR (ipaddr_pub >= address AND ipaddr_pub <= broadcast(address, inet_aton(mask)))
				) AS assigned,
				(SELECT COUNT(*) 
					FROM nodes 
					WHERE netid = n.id AND ipaddr <> 0 AND ((ipaddr >= address AND ipaddr <= broadcast(address, inet_aton(mask))) 
						OR (ipaddr_pub >= address AND ipaddr_pub <= broadcast(address, inet_aton(mask))))
						AND (?NOW? - lastonline < ?)
				) AS online
			FROM networks n
			LEFT JOIN hosts h ON h.id = n.hostid'
            . ($sqlwhere != ' WHERE' ? $sqlwhere : '')
            . ($sqlord != '' ? $sqlord . ' ' . $direction : '')
            . (isset($search['limit']) ? ' LIMIT ' . $search['limit'] : '')
            . (isset($search['offset']) ? ' OFFSET ' . $search['offset'] : ''),
            array(intval(ConfigHelper::getConfig('phpui.lastonline_limit')))
        );

        if ($networks) {
            $size = 0;
            $assigned = 0;
            $online = 0;

            foreach ($networks as $idx => $row) {
                $size += $row['size'];
                $assigned += $row['assigned'];
                $online += $row['online'];
            }

            $networks['size'] = $size;
            $networks['assigned'] = $assigned;
            $networks['online'] = $online;
            $networks['order'] = $order;
            $networks['direction'] = $direction;
        }

        return $networks;
    }

    public function IsIPValid($ip, $checkbroadcast = false, $ignoreid = 0)
    {
        $ip = ip_long($ip);
        return $this->db->GetOne('SELECT 1 FROM networks
			WHERE id != ? AND address < ?
			AND broadcast(address, inet_aton(mask)) >' . ($checkbroadcast ? '=' : '') . ' ?', array(intval($ignoreid), $ip, $ip));
    }

    public function NetworkOverlaps($network, $mask, $hostid, $ignorenet = 0)
    {
        $cnetaddr = ip_long($network);
        $cbroadcast = ip_long(getbraddr($network, $mask));

        return $this->db->GetOne('SELECT 1 FROM networks
			WHERE id != ? AND hostid = ? AND (
				address = ? OR broadcast(address, inet_aton(mask)) = ?
				OR (address > ? AND broadcast(address, inet_aton(mask)) < ?) 
				OR (address < ? AND broadcast(address, inet_aton(mask)) > ?) 
			)', array(
                    intval($ignorenet),
                    intval($hostid),
                    $cnetaddr, $cbroadcast,
                    $cnetaddr, $cbroadcast,
                    $cnetaddr, $cbroadcast
        ));
    }

    public function NetworkShift($netid, $network = '0.0.0.0', $mask = '0.0.0.0', $shift = 0)
    {
        if ($this->syslog) {
            $nodes = array_merge(
                (array) $this->db->GetAll('SELECT id, ownerid, ipaddr FROM vnodes
					WHERE netid = ? AND ipaddr >= inet_aton(?) AND ipaddr <= inet_aton(?)', array($netid, $network, getbraddr($network, $mask))),
                (array) $this->db->GetAll('SELECT id, ownerid, ipaddr_pub FROM vnodes
					WHERE netid = ? AND ipaddr_pub >= inet_aton(?) AND ipaddr_pub <= inet_aton(?)', array($netid, $network, getbraddr($network, $mask)))
            );
            if (!empty($nodes)) {
                foreach ($nodes as $node) {
                    $args = array(
                    SYSLOG::RES_NODE => $node['id'],
                    SYSLOG::RES_CUST => $node['ownerid'],
                    SYSLOG::RES_NETWORK => $netid,
                    );
                    unset($node['id']);
                    unset($node['ownerid']);
                    foreach ($node as $key => $value) {
                        $args[$key] = $value + $shift;
                    }
                    $this->syslog->AddMessage(SYSLOG::RES_NODE, SYSLOG::OPER_UPDATE, $args);
                }
            }
        }
        return ($this->db->Execute('UPDATE nodes SET ipaddr = ipaddr + ? 
				WHERE netid = ? AND ipaddr >= inet_aton(?) AND ipaddr <= inet_aton(?)', array($shift, $netid, $network, getbraddr($network, $mask)))
            + $this->db->Execute('UPDATE nodes SET ipaddr_pub = ipaddr_pub + ?
				WHERE netid = ? AND ipaddr_pub >= inet_aton(?) AND ipaddr_pub <= inet_aton(?)', array($shift, $netid, $network, getbraddr($network, $mask))));
    }

    public function NetworkUpdate($networkdata)
    {
        $args = array(
            'name' => strtoupper($networkdata['name']),
            'address' => $networkdata['address'],
            'mask' => $networkdata['mask'],
            'interface' => strtolower($networkdata['interface']),
            'vlanid' => intval($networkdata['vlanid']),
            'gateway' => $networkdata['gateway'],
            'dns' => $networkdata['dns'],
            'dns2' => $networkdata['dns2'],
            'domain' => $networkdata['domain'],
            'wins' => $networkdata['wins'],
            'dhcpstart' => $networkdata['dhcpstart'],
            'dhcpend' => $networkdata['dhcpend'],
            'notes' => $networkdata['notes'],
            SYSLOG::RES_HOST => $networkdata['hostid'],
            'authtype' => $networkdata['authtype'],
            'snat' => $networkdata['snatlong'],
            'pubnetid' => empty($networkdata['pubnetid']) ? null : $networkdata['pubnetid'],
            SYSLOG::RES_NETWORK => $networkdata['id']
        );

        $res = $this->db->Execute('UPDATE networks SET name=?, address=inet_aton(?), 
            mask=?, interface=?, vlanid=?, gateway=?, dns=?, dns2=?, domain=?, wins=?,
            dhcpstart=?, dhcpend=?, notes=?, hostid=?, authtype=?, snat=?, pubnetid=? WHERE id=?', array_values($args));

        if ($res && $this->syslog) {
            $this->syslog->AddMessage(SYSLOG::RES_NETWORK, SYSLOG::OPER_UPDATE, $args);
        }
    }

    public function NetworkCompress($id, $shift = 0)
    {
        $nodes = array();
        $network = $this->GetNetworkRecord($id);
        $address = $network['addresslong'] + $shift;
        $broadcast = $network['addresslong'] + $network['size'];
        $dhcpstart = ip2long($network['dhcpstart']);
        $dhcpend = ip2long($network['dhcpend']);

        $specials = array(ip2long($network['gateway']),
//              ip2long($network['wins']),
//              ip2long($network['dns']),
//              ip2long($network['dns2'])
        );

        foreach ($network['nodes']['id'] as $idx => $value) {
            if ($value) {
                $nodes[] = $network['nodes']['addresslong'][$idx];
            }
        }

        for ($i = $address + 1; $i < $broadcast; $i++) {
            if (!count($nodes)) {
                break;
            }

            // skip special and dhcp range addresses
            if (in_array($i, $specials) || ($i >= $dhcpstart && $i <= $dhcpend)) {
                continue;
            }

            $ip = array_shift($nodes);

            if ($i == $ip) {
                continue;
            }

            // don't change assigned special addresses
            if (in_array($ip, $specials)) {
                array_unshift($nodes, $ip);
                $size = count($nodes);

                foreach ($nodes as $idx => $ip) {
                    if (!in_array($ip, $specials)) {
                        unset($nodes[$idx]);
                        break;
                    }
                }

                if ($size == count($nodes)) {
                    break;
                }
            }

            if ($this->db->Execute('UPDATE nodes SET ipaddr=? WHERE netid=? AND ipaddr=?', array($i, $id, $ip))) {
                if ($this->syslog) {
                    $node = $this->db->GetRow('SELECT id, ownerid FROM vnodes WHERE netid = ? AND ipaddr = ?', array($id, $ip));
                    $args = array(
                        SYSLOG::RES_NODE => $node['id'],
                        SYSLOG::RES_CUST => $node['ownerid'],
                        SYSLOG::RES_NETWORK => $id,
                        'ipaddr' => $i,
                    );
                    $this->syslog->AddMessage(SYSLOG::RES_NODE, SYSLOG::OPER_UPDATE, $args);
                }
            } elseif ($this->db->Execute('UPDATE nodes SET ipaddr_pub=? WHERE netid=? AND ipaddr_pub=?', array($i, $id, $ip))) {
                if ($this->syslog) {
                    $node = $this->db->GetRow('SELECT id, ownerid FROM vnodes WHERE netid = ? AND ipaddr_pub = ?', array($id, $ip));
                    $args = array(
                        SYSLOG::RES_NODE => $node['id'],
                        SYSLOG::RES_CUST => $node['ownerid'],
                        SYSLOG::RES_NETWORK => $id,
                        'ipaddr_pub' => $i,
                    );
                    $this->syslog->AddMessage(SYSLOG::RES_NODE, SYSLOG::OPER_UPDATE, $args);
                }
            }
        }
    }

    public function NetworkRemap($src, $dst)
    {
        $network['source'] = $this->GetNetworkRecord($src);
        $network['dest'] = $this->GetNetworkRecord($dst);
        $address = $network['dest']['addresslong'] + 1;
        $broadcast = $network['dest']['addresslong'] + $network['dest']['size'];
        foreach ($network['source']['nodes']['id'] as $idx => $value) {
            if ($value) {
                $nodes[] = $network['source']['nodes']['addresslong'][$idx];
            }
        }
        foreach ($network['dest']['nodes']['id'] as $idx => $value) {
            if ($value) {
                $destnodes[] = $network['dest']['nodes']['addresslong'][$idx];
            }
        }

        $counter = 0;

        for ($i = $address; $i < $broadcast; $i++) {
            if (!count($nodes)) {
                break;
            }
            $ip = array_pop($nodes);

            while (in_array($i, (array) $destnodes)) {
                $i++;
            }

            if ($this->db->Execute('UPDATE nodes SET ipaddr=?, netid=? WHERE netid=? AND ipaddr=?', array($i, $dst, $src, $ip))) {
                if ($this->syslog) {
                    $node = $this->db->GetRow('SELECT id, ownerid FROM vnodes WHERE netid = ? AND ipaddr = ?', array($dst, $ip));
                    $args = array(
                        SYSLOG::RES_NODE => $node['id'],
                        SYSLOG::RES_CUST => $node['ownerid'],
                        SYSLOG::RES_NETWORK => $dst,
                        'ipaddr' => $i,
                    );
                    $this->syslog->AddMessage(SYSLOG::RES_NODE, SYSLOG::OPER_UPDATE, $args);
                }
            } elseif ($this->db->Execute('UPDATE nodes SET ipaddr_pub=? WHERE ipaddr_pub=?', array($i, $ip))) {
                if ($this->syslog) {
                    $node = $this->db->GetRow('SELECT id, ownerid FROM vnodes WHERE netid = ? AND ipaddr_pub = ?', array($dst, $ip));
                    $args = array(
                        SYSLOG::RES_NODE => $node['id'],
                        SYSLOG::RES_CUST => $node['ownerid'],
                        'ipaddr' => $i,
                    );
                    $this->syslog->AddMessage(SYSLOG::RES_NODE, SYSLOG::OPER_UPDATE, $args);
                }
            }

            $counter++;
        }

        return $counter;
    }

    public function GetNetworkRecord($id, $page = 0, $plimit = 4294967296, $firstfree = false)
    {
        $network = $this->db->GetRow('SELECT no.ownerid, ne.id, ne.name, ne.vlanid, inet_ntoa(ne.address) AS address,
                ne.address AS addresslong, ne.mask, ne.interface, ne.gateway, ne.dns, ne.dns2,
                ne.domain, ne.wins, ne.dhcpstart, ne.dhcpend, ne.hostid, ne.authtype, inet_ntoa(ne.snat) AS snat,
                mask2prefix(inet_aton(ne.mask)) AS prefix, ne.notes, ne.pubnetid,
                inet_ntoa(broadcast(ne.address, inet_aton(ne.mask))) AS broadcast
            FROM networks ne
            LEFT JOIN nodes no ON (no.netid = ne.id AND no.ipaddr = 0 AND no.ipaddr_pub = 0)
            WHERE ne.id = ?', array($id));

        if ($network['ownerid']) {
            $customer_manager = new LMSCustomerManager($this->db, $this->auth, $this->cache, $this->syslog);
            $network['customername'] = $customer_manager->GetCustomerName($network['ownerid']);
        }

        if ($network['pubnetid']) {
            $network['pubnet'] = $this->db->GetRow('SELECT name, inet_ntoa(address) AS address, mask
				FROM networks WHERE id = ?', array($network['pubnetid']));
            $network['pubnet']['prefix'] = mask2prefix($network['pubnet']['mask']);
        }

        $nodes = $this->db->GetAllByKey('
				SELECT id, name, ipaddr, ownerid, netdev 
				FROM vnodes WHERE netid = ? AND ipaddr > ? AND ipaddr < ?
				UNION ALL
				SELECT id, name, ipaddr_pub AS ipaddr, ownerid, netdev
				FROM vnodes WHERE ipaddr_pub > ? AND ipaddr_pub < ?', 'ipaddr', array($id, $network['addresslong'], ip_long($network['broadcast']),
            $network['addresslong'], ip_long($network['broadcast'])));

        if ($network['hostid']) {
            $network['hostname'] = $this->db->GetOne('SELECT name FROM hosts WHERE id=?', array($network['hostid']));
        }
        $network['size'] = pow(2, 32 - $network['prefix']);
        $network['assigned'] = empty($nodes) ? 0 : count($nodes);
        $network['free'] = $network['size'] - $network['assigned'] - 2;
        if ($network['dhcpstart']) {
            $network['free'] = $network['free'] - (ip_long($network['dhcpend']) - ip_long($network['dhcpstart']) + 1);
        }

        if (!$plimit) {
            $plimit = 256;
        }
        $network['pages'] = ceil($network['size'] / $plimit);

        if ($page > $network['pages']) {
            $page = $network['pages'];
        }
        if ($page < 1) {
            $page = 1;
        }
        $page--;

        while (1) {
            $start = $page * $plimit;
            $end = ($network['size'] > $plimit ? $start + $plimit : $network['size']);
            $network['pageassigned'] = 0;
            unset($network['nodes']);

            for ($i = 0; $i < ($end - $start); $i++) {
                $longip = (string) ($network['addresslong'] + $i + $start);

                $network['nodes']['addresslong'][$i] = $longip;
                $network['nodes']['address'][$i] = long_ip($longip);

                if (isset($nodes[$longip])) {
                    $network['nodes']['id'][$i] = $nodes[$longip]['id'];
                    $network['nodes']['netdev'][$i] = $nodes[$longip]['netdev'];
                    $network['nodes']['ownerid'][$i] = $nodes[$longip]['ownerid'];
                    $network['nodes']['name'][$i] = $nodes[$longip]['name'];
                    $network['pageassigned'] ++;
                } else {
                    $network['nodes']['id'][$i] = 0;

                    if ($longip == $network['addresslong']) {
                        $network['nodes']['name'][$i] = '<b>NETWORK</b>';
                    } elseif ($network['nodes']['address'][$i] == $network['broadcast']) {
                        $network['nodes']['name'][$i] = '<b>BROADCAST</b>';
                    } elseif ($network['nodes']['address'][$i] == $network['gateway']) {
                        $network['nodes']['name'][$i] = '<b>GATEWAY</b>';
                    } elseif ($longip >= ip_long($network['dhcpstart']) && $longip <= ip_long($network['dhcpend'])) {
                        $network['nodes']['name'][$i] = '<b>DHCP</b>';
                    } else {
                        $freenode = true;
                    }
                }
            }

            if ($firstfree && !isset($freenode)) {
                if ($page + 1 >= $network['pages']) {
                    break;
                }
                $page++;
            } else {
                break;
            }
        }

        $network['rows'] = ceil(count($network['nodes']['address']) / 4);
        $network['page'] = $page + 1;

        return $network;
    }

    public function ScanNodes()
    {
        $result = array();
        $networks = $this->GetNetworks();
        if ($networks) {
            $node_manager = new LMSNodeManager($this->db, $this->auth, $this->cache, $this->syslog);
            foreach ($networks as $idx => $network) {
                if ($res = execute_program('nbtscan', '-q -s: ' . $network['address'] . '/' . $network['prefix'])) {
                    $out = explode("\n", $res);
                    foreach ($out as $line) {
                        list($ipaddr, $name, $null, $login, $mac) = explode(':', $line, 5);
                        $row['ipaddr'] = trim($ipaddr);
                        if ($row['ipaddr']) {
                            $row['name'] = trim($name);
                            $row['mac'] = strtoupper(str_replace('-', ':', trim($mac)));
                            if ($row['mac'] != "00:00:00:00:00:00" && !$node_manager->GetNodeIDByIP($row['ipaddr'])) {
                                $result[] = $row;
                            }
                        }
                    }
                }
            }
        }
        return $result;
    }

    public function GetNetworkPageForIp($netid, $ip)
    {
        $netmask = $this->db->GetOne('SELECT mask FROM networks WHERE id = ?', array($netid));
        $net = ip_long(getnetaddr($ip, $netmask));
        $ip = ip_long($ip);
        $page = floor(($ip - $net) / ConfigHelper::getConfig('phpui.networkhosts_pagelimit')) + 1;
        return $page;
    }

    public function GetPublicNetworkID($netid)
    {
        return $this->db->GetOne('SELECT pubnetid FROM networks WHERE id = ?', array($netid));
    }

    public function getFirstFreeAddress($netid)
    {
        $reservedaddresses = intval(ConfigHelper::getConfig('phpui.first_reserved_addresses', 0, true));
        $net = $this->GetNetworkRecord($netid);
        $ip = false;

        foreach ($net['nodes']['id'] as $idx => $nodeid) {
            if ($idx < $reservedaddresses) {
                continue;
            }
            if ($nodeid) {
                $firstnodeid = $idx;
                $ip = false;
            }
            if (!$nodeid && !isset($net['nodes']['name'][$idx]) && empty($ip)) {
                $ip = $net['nodes']['address'][$idx];
                if (isset($firstnodeid)) {
                    break;
                }
            }
        }
        return $ip;
    }
}
