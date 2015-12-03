<?php

/*
 *  LMS version 1.11-git
 *
 *  Copyright (C) 2001-2013 LMS Developers
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
 * LMSNodeManager
 *
 * @author Maciej Lew <maciej.lew.1987@gmail.com>
 */
class LMSNodeManager extends LMSManager implements LMSNodeManagerInterface
{

    public function GetNodeOwner($id)
    {
        return $this->db->GetOne('SELECT ownerid FROM nodes WHERE id=?', array($id));
    }

    public function NodeUpdate($nodedata, $deleteassignments = FALSE)
    {
        global $SYSLOG_RESOURCE_KEYS;
        $args = array(
            'name' => $nodedata['name'],
            'ipaddr_pub' => $nodedata['ipaddr_pub'],
            'ipaddr' => $nodedata['ipaddr'],
            'passwd' => $nodedata['passwd'],
            $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NETDEV] => $nodedata['netdev'],
            $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_USER] => $this->auth->id,
            'access' => $nodedata['access'],
            'warning' => $nodedata['warning'],
            $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST] => $nodedata['ownerid'],
            'info' => $nodedata['info'],
            'location' => $nodedata['location'],
            'location_city' => $nodedata['location_city'] ? $nodedata['location_city'] : null,
            'location_street' => $nodedata['location_street'] ? $nodedata['location_street'] : null,
            'location_house' => $nodedata['location_house'] ? $nodedata['location_house'] : null,
            'location_flat' => $nodedata['location_flat'] ? $nodedata['location_flat'] : null,
            'chkmac' => $nodedata['chkmac'],
            'halfduplex' => $nodedata['halfduplex'],
            'linktype' => isset($nodedata['linktype']) ? intval($nodedata['linktype']) : 0,
	    'linkradiosector' => (isset($nodedata['linktype']) && intval($nodedata['linktype']) == 1 ?
		(isset($nodedata['radiosector']) && intval($nodedata['radiosector']) ? intval($nodedata['radiosector']) : null) : null),
            'linktechnology' => isset($nodedata['linktechnology']) ? intval($nodedata['linktechnology']) : 0,
            'linkspeed' => isset($nodedata['linkspeed']) ? intval($nodedata['linkspeed']) : 100000,
            'port' => isset($nodedata['port']) && $nodedata['netdev'] ? intval($nodedata['port']) : 0,
            'nas' => isset($nodedata['nas']) ? $nodedata['nas'] : 0,
            'longitude' => !empty($nodedata['longitude']) ? str_replace(',', '.', $nodedata['longitude']) : null,
            'latitude' => !empty($nodedata['latitude']) ? str_replace(',', '.', $nodedata['latitude']) : null,
            $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NETWORK] => $nodedata['netid'],
            'invprojectid' => $nodedata['invprojectid'],
	    'authtype' => $nodedata['authtype'] ? $nodedata['authtype'] : 0,
            $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NODE] => $nodedata['id']
        );
        $this->db->Execute('UPDATE nodes SET name=UPPER(?), ipaddr_pub=inet_aton(?),
				ipaddr=inet_aton(?), passwd=?, netdev=?, moddate=?NOW?,
				modid=?, access=?, warning=?, ownerid=?, info=?, location=?,
				location_city=?, location_street=?, location_house=?, location_flat=?,
				chkmac=?, halfduplex=?, linktype=?, linkradiosector=?, linktechnology=?, linkspeed=?,
				port=?, nas=?, longitude=?, latitude=?, netid=?, invprojectid=?, authtype=?
				WHERE id=?', array_values($args));

        if ($this->syslog) {
            unset($args[$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_USER]]);
            $this->syslog->AddMessage(SYSLOG_RES_NODE, SYSLOG_OPER_UPDATE, $args, array($SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NODE], $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NETWORK],
                $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST], $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NETDEV]));

            $macs = $this->db->GetAll('SELECT id, nodeid FROM macs WHERE nodeid = ?', array($nodedata['id']));
            if (!empty($macs))
                foreach ($macs as $mac) {
                    $args = array(
                        $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_MAC] => $mac['id'],
                        $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NODE] => $mac['nodeid'],
                        $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST] => $nodedata['ownerid']
                    );
                    $this->syslog->AddMessage(SYSLOG_RES_MAC, SYSLOG_OPER_DELETE, $args, array_keys($args));
                }
        }
        $this->db->Execute('DELETE FROM macs WHERE nodeid=?', array($nodedata['id']));
        foreach ($nodedata['macs'] as $mac) {
            $this->db->Execute('INSERT INTO macs (mac, nodeid) VALUES(?, ?)', array(strtoupper($mac), $nodedata['id']));
            if ($this->syslog) {
                $macid = $this->db->GetLastInsertID('macs');
                $args = array(
                    $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_MAC] => $macid,
                    $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NODE] => $nodedata['id'],
                    $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST] => $nodedata['ownerid'],
                    'mac' => strtoupper($mac)
                );
                $this->syslog->AddMessage(SYSLOG_RES_MAC, SYSLOG_OPER_ADD, $args, array($SYSLOG_RESOURCE_KEYS[SYSLOG_RES_MAC],
                    $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NODE],
                    $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST]));
            }
        }

        if ($deleteassignments) {
            if ($this->syslog) {
                $nodeassigns = $this->db->GetAll('SELECT id, nodeid, assignmentid FROM nodeassignments
					WHERE nodeid = ?', array($nodedata['id']));
                if (!empty($nodeassigns))
                    foreach ($nodeassigns as $nodeassign) {
                        $args = array(
                            $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NODEASSIGN] => $nodeassign['id'],
                            $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NODE] => $nodedata['id'],
                            $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_ASSIGN] => $nodedata['assignmentid'],
                            $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST] => $nodedata['ownerid']
                        );
                        $this->syslog->AddMessage(SYSLOG_RES_NODEASSIGN, SYSLOG_OPER_DELETE, $args, array_keys($args));
                    }
            }
            $this->db->Execute('DELETE FROM nodeassignments WHERE nodeid = ?', array($nodedata['id']));
        }
    }

    public function DeleteNode($id)
    {
        global $SYSLOG_RESOURCE_KEYS;
        $this->db->BeginTrans();

        if ($this->syslog) {
            $customerid = $this->db->GetOne('SELECT ownerid FROM nodes WHERE id = ?', array($id));
            $macs = $this->db->GetCol('SELECT macid FROM vmacs WHERE id = ?', array($id));
            if (!empty($macs))
                foreach ($macs as $mac) {
                    $args = array(
                        $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_MAC] => $mac,
                        $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NODE] => $id,
                        $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST] => $customerid
                    );
                    $this->syslog->AddMessage(SYSLOG_RES_MAC, SYSLOG_OPER_DELETE, $args, array_keys($args));
                }
            $args = array(
                $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NODE] => $id,
                $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST] => $customerid
            );
            $this->syslog->AddMessage(SYSLOG_RES_NODE, SYSLOG_OPER_DELETE, $args, array_keys($args));
        }

        $this->db->Execute('DELETE FROM nodes WHERE id = ?', array($id));
        $this->db->Execute('DELETE FROM nodegroupassignments WHERE nodeid = ?', array($id));
        $this->db->CommitTrans();
    }

    public function GetNodeNameByMAC($mac)
    {
        return $this->db->GetOne('SELECT name FROM vnodes WHERE mac=UPPER(?)', array($mac));
    }

    public function GetNodeIDByIP($ipaddr)
    {
        return $this->db->GetOne('SELECT id FROM nodes WHERE ipaddr=inet_aton(?) OR ipaddr_pub=inet_aton(?)', array($ipaddr, $ipaddr));
    }

    public function GetNodeIDByMAC($mac)
    {
        return $this->db->GetOne('SELECT nodeid FROM macs WHERE mac=UPPER(?)', array($mac));
    }

    public function GetNodeIDByName($name)
    {
        return $this->db->GetOne('SELECT id FROM nodes WHERE name=UPPER(?)', array($name));
    }

    public function GetNodeIPByID($id)
    {
        return $this->db->GetOne('SELECT inet_ntoa(ipaddr) FROM nodes WHERE id=?', array($id));
    }

    public function GetNodePubIPByID($id)
    {
        return $this->db->GetOne('SELECT inet_ntoa(ipaddr_pub) FROM nodes WHERE id=?', array($id));
    }

    public function GetNodeMACByID($id)
    {
        return $this->db->GetOne('SELECT mac FROM vnodes WHERE id=?', array($id));
    }

    public function GetNodeName($id)
    {
        return $this->db->GetOne('SELECT name FROM nodes WHERE id=?', array($id));
    }

    public function GetNodeNameByIP($ipaddr)
    {
        return $this->db->GetOne('SELECT name FROM nodes WHERE ipaddr=inet_aton(?) OR ipaddr_pub=inet_aton(?)', array($ipaddr, $ipaddr));
    }
    public function GetNodeConnType($id)
    {
        return $this->db->GetOne('SELECT authtype FROM nodes WHERE id=?', array($id));
    }

    public function GetNode($id)
    {
        if ($result = $this->db->GetRow('SELECT n.*, rs.name AS linkradiosectorname,
		    inet_ntoa(n.ipaddr) AS ip, inet_ntoa(n.ipaddr_pub) AS ip_pub,
		    lc.name AS city_name,
				(CASE WHEN ls.name2 IS NOT NULL THEN ' . $this->db->Concat('ls.name2', "' '", 'ls.name') . ' ELSE ls.name END) AS street_name,
				lt.name AS street_type,
			lb.name AS borough_name, lb.type AS borough_type,
			ld.name AS district_name, lst.name AS state_name
			FROM vnodes n
			LEFT JOIN netradiosectors rs ON rs.id = n.linkradiosector
			LEFT JOIN location_cities lc ON (lc.id = n.location_city)
			LEFT JOIN location_streets ls ON (ls.id = n.location_street)
			LEFT JOIN location_street_types lt ON (lt.id = ls.typeid)
			LEFT JOIN location_boroughs lb ON (lb.id = lc.boroughid)
			LEFT JOIN location_districts ld ON (ld.id = lb.districtid)
			LEFT JOIN location_states lst ON (lst.id = ld.stateid)
			WHERE n.id = ?', array($id))
        ) {
            $customer_manager = new LMSCustomerManager($this->db, $this->auth, $this->cache, $this->syslog);
            $user_manager = new LMSUserManager($this->db, $this->auth, $this->cache, $this->syslog);
            $result['radiosectors'] = $this->db->GetAll('SELECT * FROM netradiosectors WHERE netdev = ?', array($result['netdev']));
            $result['owner'] = $customer_manager->GetCustomerName($result['ownerid']);
            $result['createdby'] = $user_manager->GetUserName($result['creatorid']);
            $result['modifiedby'] = $user_manager->GetUserName($result['modid']);
            $result['creationdateh'] = date('Y/m/d, H:i', $result['creationdate']);
            $result['moddateh'] = date('Y/m/d, H:i', $result['moddate']);
            $result['lastonlinedate'] = lastonline_date($result['lastonline']);

            $result['mac'] = preg_split('/,/', $result['mac']);
            foreach ($result['mac'] as $mac)
                $result['macs'][] = array('mac' => $mac, 'producer' => get_producer($mac));
            unset($result['mac']);

            if ($netname = $this->db->GetOne('SELECT name FROM networks
                    WHERE id = ?', array($result['netid']))) {
                $result['netname'] = $netname;
            }

            if ($result['ip_pub'] != '0.0.0.0') {
                $network_manager = new LMSNetworkManager($this->db, $this->auth, $this->cache, $this->syslog);
                $result['netpubid'] = $network_manager->GetNetIDByIP($result['ip_pub']);
                $result['netpubname'] = $this->db->GetOne('SELECT name FROM networks
					WHERE id = ?', array($result['netpubid']));
            }

            return $result;
        } else
            return FALSE;
    }

    public function GetNodeList($order = 'name,asc', $search = NULL, $sqlskey = 'AND', $network = NULL, $status = NULL, $customergroup = NULL, $nodegroup = NULL, $limit = null, $offset = null, $count = false)
    {
        if ($order == '')
            $order = 'name,asc';

        list($order, $direction) = sscanf($order, '%[^,],%s');

        ($direction == 'desc') ? $direction = 'desc' : $direction = 'asc';

        switch ($order) {
            case 'name':
                $sqlord = ' ORDER BY n.name';
                break;
            case 'id':
                $sqlord = ' ORDER BY n.id';
                break;
            case 'mac':
                $sqlord = ' ORDER BY n.mac';
                break;
            case 'ip':
                $sqlord = ' ORDER BY n.ipaddr';
                break;
            case 'ip_pub':
                $sqlord = ' ORDER BY n.ipaddr_pub';
                break;
            case 'ownerid':
                $sqlord = ' ORDER BY n.ownerid';
                break;
            case 'owner':
                $sqlord = ' ORDER BY owner';
                break;
            case 'location':
                $sqlord = ' ORDER BY location';
                break;
        }

        if (sizeof($search))
            foreach ($search as $idx => $value) {
                if ($value != '') {
                    switch ($idx) {
                        case 'ipaddr':
                            $searchargs[] = '(inet_ntoa(n.ipaddr) ?LIKE? ' . $this->db->Escape('%' . trim($value) . '%')
                                    . ' OR inet_ntoa(n.ipaddr_pub) ?LIKE? ' . $this->db->Escape('%' . trim($value) . '%') . ')';
                            break;
                        case 'state':
                            if ($value != '0')
                                $searchargs[] = 'n.location_city IN (SELECT lc.id FROM location_cities lc 
								JOIN location_boroughs lb ON lb.id = lc.boroughid 
								JOIN location_districts ld ON ld.id = lb.districtid 
								JOIN location_states ls ON ls.id = ld.stateid WHERE ls.id = ' . $this->db->Escape($value) . ')';
                            break;
                        case 'district':
                            if ($value != '0')
                                $searchargs[] = 'n.location_city IN (SELECT lc.id FROM location_cities lc 
								JOIN location_boroughs lb ON lb.id = lc.boroughid 
								JOIN location_districts ld ON ld.id = lb.districtid WHERE ld.id = ' . $this->db->Escape($value) . ')';
                            break;
                        case 'borough':
                            if ($value != '0')
                                $searchargs[] = 'n.location_city IN (SELECT lc.id FROM location_cities lc WHERE lc.boroughid = '
                                        . $this->db->Escape($value) . ')';
                            break;
                        default:
                            $searchargs[] = 'n.' . $idx . ' ?LIKE? ' . $this->db->Escape("%$value%");
                    }
                }
            }

        if (isset($searchargs))
            $searchargs = ' AND (' . implode(' ' . $sqlskey . ' ', $searchargs) . ')';

        $totalon = 0;
        $totaloff = 0;

        if ($network) {
            $network_manager = new LMSNetworkManager($this->db, $this->auth, $this->cache, $this->syslog);
            $net = $network_manager->GetNetworkParams($network);
        }

        $sql = '';

		if ($count) {
			$sql .= 'SELECT COUNT(n.id) ';
		} else {
			$sql .= 'SELECT n.id AS id, n.ipaddr, inet_ntoa(n.ipaddr) AS ip, ipaddr_pub,
				inet_ntoa(n.ipaddr_pub) AS ip_pub, n.mac, n.name, n.ownerid, n.access, n.warning,
				n.netdev, n.lastonline, n.info, '
				. $this->db->Concat('c.lastname', "' '", 'c.name') . ' AS owner, net.name AS netname, n.location,
				lb.name AS borough_name, lb.type AS borough_type,
				ld.name AS district_name, ls.name AS state_name ';
		}
		$sql .= 'FROM vnodes n 
				JOIN customersview c ON (n.ownerid = c.id)
				JOIN networks net ON net.id = n.netid 
				LEFT JOIN location_cities lc ON lc.id = n.location_city
				LEFT JOIN location_boroughs lb ON lb.id = lc.boroughid
				LEFT JOIN location_districts ld ON ld.id = lb.districtid
				LEFT JOIN location_states ls ON ls.id = ld.stateid '
				. ($customergroup ? 'JOIN customerassignments ON (customerid = c.id) ' : '')
				. ($nodegroup ? 'JOIN nodegroupassignments ON (nodeid = n.id) ' : '')
				. ' WHERE 1=1 '
				. ($network ? ' AND (n.netid = ' . $network . ' OR (n.ipaddr_pub > ' . $net['address'] . ' AND n.ipaddr_pub < ' . $net['broadcast'] . '))' : '')
				. ($status == 1 ? ' AND n.access = 1' : '') //connected
				. ($status == 2 ? ' AND n.access = 0' : '') //disconnected
				. ($status == 3 ? ' AND n.lastonline > ?NOW? - ' . intval(ConfigHelper::getConfig('phpui.lastonline_limit')) : '') //online
				. ($customergroup ? ' AND customergroupid = ' . intval($customergroup) : '')
				. ($nodegroup ? ' AND nodegroupid = ' . intval($nodegroup) : '')
				. (isset($searchargs) ? $searchargs : '')
				. ($sqlord != '' && !$count ? $sqlord . ' ' . $direction : '')
				. ($limit !== null && !$count ? ' LIMIT ' . $limit : '')
				. ($offset !== null && !$count ? ' OFFSET ' . $offset : '');

		if (!$count) {
			$nodelist = $this->db->GetAll($sql);
			if (!empty($nodelist)) {
				foreach ($nodelist as $idx => $row) {
					($row['access']) ? $totalon++ : $totaloff++;
				}

				$nodelist['total'] = sizeof($nodelist);
				$nodelist['order'] = $order;
				$nodelist['direction'] = $direction;
				$nodelist['totalon'] = $totalon;
				$nodelist['totaloff'] = $totaloff;

				return $nodelist;
			}
		} else {
			return $this->db->getOne($sql);
		}
    }

    public function NodeSet($id, $access = -1)
    {
        global $SYSLOG_RESOURCE_KEYS;
        $keys = array($SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NODE], $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST]);
        $customerid = $this->db->GetOne('SELECT ownerid FROM nodes WHERE id = ?', array($id));
        $args = array(
            $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NODE] => $id,
            $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST] => $customerid
        );

        if ($access != -1) {
            $args['access'] = $access;
            if ($access) {
                if ($this->db->GetOne('SELECT 1 FROM nodes WHERE id = ? AND EXISTS
					(SELECT 1 FROM customers WHERE id = ownerid AND status = 3)', array($id))) {
                    if ($this->syslog)
                        $this->syslog->AddMessage(SYSLOG_RES_NODE, SYSLOG_OPER_UPDATE, $args, $keys);
                    return $this->db->Execute('UPDATE nodes SET access = 1 WHERE id = ?
						AND EXISTS (SELECT 1 FROM customers WHERE id = ownerid 
							AND status = 3)', array($id));
                }
                return 0;
            } else {
                if ($this->syslog)
                    $this->syslog->AddMessage(SYSLOG_RES_NODE, SYSLOG_OPER_UPDATE, $args, $keys);
                return $this->db->Execute('UPDATE nodes SET access = 0 WHERE id = ?', array($id));
            }
        }
        elseif ($this->db->GetOne('SELECT access FROM nodes WHERE id = ?', array($id)) == 1) {
            if ($this->syslog) {
                $args['access'] = 0;
                $this->syslog->AddMessage(SYSLOG_RES_NODE, SYSLOG_OPER_UPDATE, $args, $keys);
            }
            return $this->db->Execute('UPDATE nodes SET access=0 WHERE id = ?', array($id));
        } else {
            if ($this->db->GetOne('SELECT 1 FROM nodes WHERE id = ? AND EXISTS
				(SELECT 1 FROM customers WHERE id = ownerid AND status = 3)', array($id))) {
                if ($this->syslog) {
                    $args['access'] = 1;
                    $this->syslog->AddMessage(SYSLOG_RES_NODE, SYSLOG_OPER_UPDATE, $args, $keys);
                }
                return $this->db->Execute('UPDATE nodes SET access = 1 WHERE id = ?
						AND EXISTS (SELECT 1 FROM customers WHERE id = ownerid 
							AND status = 3)', array($id));
            }
            return 0;
        }
    }

    public function NodeSetU($id, $access = FALSE)
    {
        global $SYSLOG_RESOURCE_KEYS;
        $keys = array($SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NODE], $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST]);

        if ($access) {
            if ($this->db->GetOne('SELECT status FROM customers WHERE id = ?', array($id)) == 3) {
                if ($this->syslog) {
                    $nodes = $this->db->GetCol('SELECT id FROM nodes WHERE ownerid = ?', array($id));
                    $args = array(
                        $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST] => $id,
                        'access' => $access
                    );
                    if (!empty($nodes))
                        foreach ($nodes as $nodeid) {
                            $args[$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NODE]] = $nodeid;
                            $this->syslog->AddMessage(SYSLOG_RES_NODE, SYSLOG_OPER_UPDATE, $args, $keys);
                        }
                }
                return $this->db->Execute('UPDATE nodes SET access=1 WHERE ownerid=?', array($id));
            }
        } else {
            if ($this->syslog) {
                $nodes = $this->db->GetCol('SELECT id FROM nodes WHERE ownerid = ?', array($id));
                $args = array(
                    $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST] => $id,
                    'access' => $access
                );
                if (!empty($nodes))
                    foreach ($nodes as $nodeid) {
                        $args[$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NODE]] = $nodeid;
                        $this->syslog->AddMessage(SYSLOG_RES_NODE, SYSLOG_OPER_UPDATE, $args, $keys);
                    }
            }
            return $this->db->Execute('UPDATE nodes SET access=0 WHERE ownerid=?', array($id));
        }
    }

    public function NodeSetWarn($id, $warning = FALSE)
    {
        global $SYSLOG_RESOURCE_KEYS;
        if ($this->syslog) {
            $cids = $this->db->GetAll('SELECT id, ownerid FROM nodes WHERE id IN ('
                    . (is_array($id) ? implode(',', $id) : $id) . ')');
            if (!empty($cids))
                foreach ($cids as $cid) {
                    $args = array(
                        $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NODE] => $cid['id'],
                        $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST] => $cid['ownerid'],
                        'warning' => $warning
                    );
                    $this->syslog->AddMessage(SYSLOG_RES_NODE, SYSLOG_OPER_UPDATE, $args, array($SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NODE], $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST]));
                }
        }
        return $this->db->Execute('UPDATE nodes SET warning = ? WHERE id IN ('
                        . (is_array($id) ? implode(',', $id) : $id) . ')', array($warning ? 1 : 0));
    }

    public function NodeSwitchWarn($id)
    {
        global $SYSLOG_RESOURCE_KEYS;
        if ($this->syslog) {
            $node = $this->db->GetRow('SELECT ownerid, warning FROM nodes WHERE id = ?', array($id));
            $args = array(
                $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NODE] => $id,
                $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST] => $node['ownerid'],
                'warning' => ($node['warning'] ? 0 : 1)
            );
            $this->syslog->AddMessage(SYSLOG_RES_NODE, SYSLOG_OPER_UPDATE, $args, array($SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NODE], $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST]));
        }
        return $this->db->Execute('UPDATE nodes 
			SET warning = (CASE warning WHEN 0 THEN 1 ELSE 0 END)
			WHERE id = ?', array($id));
    }

    public function NodeSetWarnU($id, $warning = FALSE)
    {
        global $SYSLOG_RESOURCE_KEYS;
        if ($this->syslog) {
            $nodes = $this->db->GetAll('SELECT id, ownerid FROM nodes WHERE ownerid IN ('
                    . (is_array($id) ? implode(',', $id) : $id) . ')');
            if (!empty($nodes))
                foreach ($nodes as $node) {
                    $args = array(
                        $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NODE] => $node['id'],
                        $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST] => $node['ownerid'],
                        'warning' => $warning
                    );
                    $this->syslog->AddMessage(SYSLOG_RES_NODE, SYSLOG_OPER_UPDATE, $args, array($SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NODE], $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST]));
                }
        }
        return $this->db->Execute('UPDATE nodes SET warning = ? WHERE ownerid IN ('
                        . (is_array($id) ? implode(',', $id) : $id) . ')', array($warning ? 1 : 0));
    }

    public function IPSetU($netdev, $access = FALSE)
    {
        global $SYSLOG_RESOURCE_KEYS;
        if ($access)
            $res = $this->db->Execute('UPDATE nodes SET access=1 WHERE netdev=? AND ownerid=0', array($netdev));
        else
            $res = $this->db->Execute('UPDATE nodes SET access=0 WHERE netdev=? AND ownerid=0', array($netdev));
        if ($this->syslog && $res) {
            $nodes = $this->db->GetCol('SELECT id FROM nodes WHERE netdev=? AND ownerid=0', array($netdev));
            foreach ($nodes as $node) {
                $args = array(
                    $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NODE] => $node,
                    $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NETDEV] => $netdev,
                    'access' => intval($access),
                );
                $this->syslog->AddMessage(SYSLOG_RES_NODE, SYSLOG_OPER_UPDATE, $args, array($SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NODE],
                    $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NETDEV]));
            }
        }
        return $res;
    }

    public function NodeAdd($nodedata)
    {
        global $SYSLOG_RESOURCE_KEYS;
        $args = array(
            'name' => $nodedata['name'],
            'ipaddr' => $nodedata['ipaddr'],
            'ipaddr_pub' => $nodedata['ipaddr_pub'],
            $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST] => $nodedata['ownerid'],
            'passwd' => $nodedata['passwd'],
            $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_USER] => $this->auth->id,
            'access' => $nodedata['access'],
            'warning' => $nodedata['warning'],
            'info' => $nodedata['info'],
            $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NETDEV] => $nodedata['netdev'],
            'location' => $nodedata['location'],
            'location_city' => $nodedata['location_city'] ? $nodedata['location_city'] : null,
            'location_street' => $nodedata['location_street'] ? $nodedata['location_street'] : null,
            'location_house' => $nodedata['location_house'] ? $nodedata['location_house'] : null,
            'location_flat' => $nodedata['location_flat'] ? $nodedata['location_flat'] : null,
            'linktype' => isset($nodedata['linktype']) ? intval($nodedata['linktype']) : 0,
	    'linkradiosector' => (isset($nodedata['linktype']) && intval($nodedata['linktype']) == 1 ?
		(isset($nodedata['radiosector']) && intval($nodedata['radiosector']) ? intval($nodedata['radiosector']) : null) : null),
            'linktechnology' => isset($nodedata['linktechnology']) ? intval($nodedata['linktechnology']) : 0,
            'linkspeed' => isset($nodedata['linkspeed']) ? intval($nodedata['linkspeed']) : 100000,
            'port' => isset($nodedata['port']) && $nodedata['netdev'] ? intval($nodedata['port']) : 0,
            'chkmac' => $nodedata['chkmac'],
            'halfduplex' => $nodedata['halfduplex'],
            'nas' => isset($nodedata['nas']) ? $nodedata['nas'] : 0,
            'longitude' => !empty($nodedata['longitude']) ? str_replace(',', '.', $nodedata['longitude']) : null,
            'latitude' => !empty($nodedata['latitude']) ? str_replace(',', '.', $nodedata['latitude']) : null,
            $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NETWORK] => $nodedata['netid'],
            'invprojectid' => $nodedata['invprojectid'],
	    'authtype' => $nodedata['authtype'],
        );

        if ($this->db->Execute('INSERT INTO nodes (name, ipaddr, ipaddr_pub, ownerid,
			passwd, creatorid, creationdate, access, warning, info, netdev,
			location, location_city, location_street, location_house, location_flat,
			linktype, linkradiosector, linktechnology, linkspeed, port, chkmac, halfduplex, nas,
			longitude, latitude, netid, invprojectid, authtype)
			VALUES (?, inet_aton(?), inet_aton(?), ?, ?, ?,
			?NOW?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', array_values($args))) {
            $id = $this->db->GetLastInsertID('nodes');

            // EtherWerX support (devices have some limits)
            // We must to replace big ID with smaller (first free)
            if ($id > 99999 && ConfigHelper::checkValue(ConfigHelper::getConfig('phpui.ewx_support', false))) {
                $this->db->BeginTrans();
                $this->db->LockTables('nodes');

                if ($newid = $this->db->GetOne('SELECT n.id + 1 FROM nodes n 
						LEFT OUTER JOIN nodes n2 ON n.id + 1 = n2.id
						WHERE n2.id IS NULL AND n.id <= 99999
						ORDER BY n.id ASC LIMIT 1')) {
                    $this->db->Execute('UPDATE nodes SET id = ? WHERE id = ?', array($newid, $id));
                    $id = $newid;
                }

                $this->db->UnLockTables();
                $this->db->CommitTrans();
            }

            if ($this->syslog) {
                unset($args[$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_USER]]);
                $args[$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NODE]] = $id;
                $this->syslog->AddMessage(SYSLOG_RES_NODE, SYSLOG_OPER_ADD, $args, array($SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NODE], $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NETWORK],
                    $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST], $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NETDEV]));
            }

            foreach ($nodedata['macs'] as $mac)
                $this->db->Execute('INSERT INTO macs (mac, nodeid) VALUES(?, ?)', array(strtoupper($mac), $id));
            if ($this->syslog) {
                $macs = $this->db->GetAll('SELECT id, mac FROM macs WHERE nodeid = ?', array($id));
                foreach ($macs as $mac) {
                    $args = array(
                        $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_MAC] => $mac['id'],
                        $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NODE] => $id,
                        $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST] => $nodedata['ownerid'],
                        'mac' => $mac['mac']
                    );
                    $this->syslog->AddMessage(SYSLOG_RES_MAC, SYSLOG_OPER_ADD, $args, array($SYSLOG_RESOURCE_KEYS[SYSLOG_RES_MAC],
                        $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NODE],
                        $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST]));
                }
            }

            return $id;
        }

        return FALSE;
    }

    public function NodeExists($id)
    {
        return ($this->db->GetOne('SELECT n.id FROM nodes n
			WHERE n.id = ? AND n.ownerid > 0 AND NOT EXISTS (
		        	SELECT 1 FROM customerassignments a
			        JOIN excludedgroups e ON (a.customergroupid = e.customergroupid)
				WHERE e.userid = lms_current_user() AND a.customerid = n.ownerid)'
                        , array($id)) ? TRUE : FALSE);
    }

    public function NodeStats()
    {
        $result = $this->db->GetRow('SELECT COUNT(CASE WHEN access=1 THEN 1 END) AS connected, 
				COUNT(CASE WHEN access=0 THEN 1 END) AS disconnected,
				COUNT(CASE WHEN ?NOW?-lastonline < ? THEN 1 END) AS online
				FROM nodes WHERE ownerid > 0', array(ConfigHelper::getConfig('phpui.lastonline_limit')));

        $result['total'] = $result['connected'] + $result['disconnected'];
        return $result;
    }
    
    public function SetNodeLinkType($node, $link = NULL)
    {
        global $SYSLOG_RESOURCE_KEYS;

	if (empty($link)) {
		$type = 0;
		$technology = 0;
		$radiosector = NULL;
		$speed = 100000;
	} else {
		$type = isset($link['type']) ? intval($link['type']) : 0;
		$radiosector = isset($link['radiosector']) ? intval($link['radiosector']) : NULL;
		if ($type != 1 || $radiosector == 0)
			$radiosector = NULL;
		$technology = isset($link['technology']) ? intval($link['technology']) : 0;
		$speed = isset($link['speed']) ? intval($link['speed']) : 100000;
	}

        $res = $this->db->Execute('UPDATE nodes SET linktype=?, linkradiosector = ?, linktechnology=?, linkspeed=? WHERE id=?',
        	array($type, $radiosector, $technology, $speed, $node));
        if ($this->syslog && $res) {
            $nodedata = $this->db->GetRow('SELECT ownerid, netdev FROM nodes WHERE id=?', array($node));
            $args = array(
                $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NODE] => $node,
                $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST] => $nodedata['ownerid'],
                $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NETDEV] => $nodedata['netdev'],
                'linktype' => $type,
                'linkradiosector' => $radiosector,
                'linktechnology' => $technology,
                'linkspeed' => $speed,
            );
            $this->syslog->AddMessage(SYSLOG_RES_NODE, SYSLOG_OPER_UPDATE, $args, array($SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NODE],
                $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST],
                $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NETDEV]));
        }
        return $res;
    }

}
