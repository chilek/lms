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
 * LMSNetDevManager
 *
 */
class LMSNetDevManager extends LMSManager implements LMSNetDevManagerInterface
{
    const NETDEV_URL = 1;
    const NODE_URL = 2;

    public function GetNetDevLinkedNodes($id)
    {
        $result = $this->db->GetAll('SELECT n.id AS id, n.name AS name, linktype, rs.name AS radiosector,
        		linktechnology, linkspeed,
			ipaddr, inet_ntoa(ipaddr) AS ip, ipaddr_pub, inet_ntoa(ipaddr_pub) AS ip_pub,
			n.netdev, port, ownerid,
			' . $this->db->Concat('c.lastname', "' '", 'c.name') . ' AS owner,
			net.name AS netname, n.location, lastonline,
			lc.name AS city_name, lb.name AS borough_name, lb.type AS borough_type,
			ld.name AS district_name, ls.name AS state_name
			FROM vnodes n
			JOIN customerview c ON c.id = ownerid
			JOIN networks net ON net.id = n.netid
			LEFT JOIN netradiosectors rs ON rs.id = n.linkradiosector
			LEFT JOIN location_cities lc ON lc.id = n.location_city
			LEFT JOIN location_boroughs lb ON lb.id = lc.boroughid
			LEFT JOIN location_districts ld ON ld.id = lb.districtid
			LEFT JOIN location_states ls ON ls.id = ld.stateid
			WHERE n.netdev = ? AND ownerid IS NOT NULL
			ORDER BY n.name ASC', array($id));

        if ($result) {
            foreach ($result as &$node) {
                $node['lastonlinedate'] = lastonline_date($node['lastonline']);
            }
            unset($node);
        }

        return $result;
    }

    public function NetDevLinkNode($id, $devid, $link = null)
    {
        if (empty($link)) {
            $type        = 0;
            $technology  = 0;
            $radiosector = null;
            $speed       = 100000;
            $port        = 0;
        } else {
            $type        = isset($link['type']) ? intval($link['type']) : 0;
            $radiosector = isset($link['radiosector']) && !empty($link['radiosector']) ? intval($link['radiosector']) : null;
            $technology  = isset($link['technology']) ? intval($link['technology']) : 0;
            $speed       = isset($link['speed']) ? intval($link['speed']) : 100000;
            $port        = isset($link['port'])  ? intval($link['port'])  : 0;
        }

        $args = array(
            SYSLOG::RES_NETDEV => empty($devid) ? null : $devid,
            'linktype'         => $type,
            'linkradiosector'  => $radiosector,
            'linktechnology'   => $technology,
            'linkspeed'        => $speed,
            'port'             => intval($port),
            SYSLOG::RES_NODE   => $id,
        );
        $res = $this->db->Execute('UPDATE nodes SET netdev=?, linktype=?, linkradiosector=?,
			linktechnology=?, linkspeed=?, port=?
			WHERE id=?', array_values($args));
        if ($this->syslog && $res) {
            $args[SYSLOG::RES_CUST] = $this->db->GetOne('SELECT ownerid FROM vnodes WHERE id=?', array($id));
            $this->syslog->AddMessage(SYSLOG::RES_NODE, SYSLOG::OPER_UPDATE, $args);
        }
        return $res;
    }

    public function SetNetDevLinkType($dev1, $dev2, $link = null)
    {
        if (empty($link)) {
            $type = 0;
            $srcradiosector = null;
            $dstradiosector = null;
            $technology = 0;
            $speed = 100000;
        } else {
            $type = isset($link['type']) ? $link['type'] : 0;
            $srcradiosector = isset($link['srcradiosector']) ? (intval($link['srcradiosector']) ? intval($link['srcradiosector']) : null) : null;
            $dstradiosector = isset($link['dstradiosector']) ? (intval($link['dstradiosector']) ? intval($link['dstradiosector']) : null) : null;
            $technology = isset($link['technology']) ? $link['technology'] : 0;
            $speed = isset($link['speed']) ? $link['speed'] : 100000;
        }

        $args = array(
            'type' => $type,
            'src_' . SYSLOG::getResourceKey(SYSLOG::RES_RADIOSECTOR) => $srcradiosector,
            'dst_' . SYSLOG::getResourceKey(SYSLOG::RES_RADIOSECTOR) => $dstradiosector,
            'technology' => $technology,
            'speed' => $speed,
            'src_' . SYSLOG::getResourceKey(SYSLOG::RES_NETDEV) => $dev2,
            'dst_' . SYSLOG::getResourceKey(SYSLOG::RES_NETDEV) => $dev1,
        );
        $res = $this->db->Execute('UPDATE netlinks SET type=?, srcradiosector=?, dstradiosector=?, technology=?, speed=?
			WHERE src=? AND dst=?', array_values($args));
        if (!$res) {
            $args = array(
                'type' => $type,
                'src_' . SYSLOG::getResourceKey(SYSLOG::RES_RADIOSECTOR) => $srcradiosector,
                'dst_' . SYSLOG::getResourceKey(SYSLOG::RES_RADIOSECTOR) => $dstradiosector,
                'technology' => $technology,
                'speed' => $speed,
                'src_' . SYSLOG::getResourceKey(SYSLOG::RES_NETDEV) => $dev1,
                'dst_' . SYSLOG::getResourceKey(SYSLOG::RES_NETDEV) => $dev2,
            );
            $res = $this->db->Execute('UPDATE netlinks SET type=?, dstradiosector=?, srcradiosector=?, technology=?, speed=?
				WHERE src=? AND dst=?', array_values($args));
        }
        if ($this->syslog && $res) {
            $args[SYSLOG::RES_NETLINK] =
                $this->db->GetOne('SELECT id FROM netlinks WHERE (src=? AND dst=?) OR (dst=? AND src=?)', array($dev1, $dev2, $dev1, $dev2));
            $this->syslog->AddMessage(
                SYSLOG::RES_NETLINK,
                SYSLOG::OPER_UPDATE,
                $args,
                array('src_' . SYSLOG::getResourceKey(SYSLOG::RES_NETDEV),
                'dst_' . SYSLOG::getResourceKey(SYSLOG::RES_NETDEV),
                'src_' . SYSLOG::getResourceKey(SYSLOG::RES_RADIOSECTOR),
                'dst_' . SYSLOG::getResourceKey(SYSLOG::RES_RADIOSECTOR),
                )
            );
        }

        return $res;
    }

    public function IsNetDevLink($dev1, $dev2)
    {
        return $this->db->GetOne('SELECT COUNT(id) FROM netlinks
			WHERE (src=? AND dst=?) OR (dst=? AND src=?)', array($dev1, $dev2, $dev1, $dev2));
    }

    public function NetDevLink($dev1, $dev2, $link)
    {
        $type = $link['type'];
        $srcradiosector = ($type == 1 ?
        (isset($link['srcradiosector']) && intval($link['srcradiosector']) ? intval($link['srcradiosector']) : null) : null);
        $dstradiosector = ($type == 1 ?
        (isset($link['dstradiosector']) && intval($link['dstradiosector']) ? intval($link['dstradiosector']) : null) : null);
        $technology = $link['technology'];
        $speed = $link['speed'];
        $sport = $link['srcport'];
        $dport = $link['dstport'];

        if ($dev1 != $dev2) {
            if (!$this->IsNetDevLink($dev1, $dev2)) {
                $args = array(
                'src_' . SYSLOG::getResourceKey(SYSLOG::RES_NETDEV) => $dev1,
                'dst_' . SYSLOG::getResourceKey(SYSLOG::RES_NETDEV) => $dev2,
                'type' => $type,
                'src_' . SYSLOG::getResourceKey(SYSLOG::RES_RADIOSECTOR) => $srcradiosector,
                'dst_' . SYSLOG::getResourceKey(SYSLOG::RES_RADIOSECTOR) => $dstradiosector,
                'technology' => $technology,
                'speed' => $speed,
                'srcport' => intval($sport),
                'dstport' => intval($dport),
                );
                $res = $this->db->Execute('INSERT INTO netlinks
					(src, dst, type, srcradiosector, dstradiosector, technology, speed, srcport, dstport)
					VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)', array_values($args));
                if ($this->syslog && $res) {
                    $args[SYSLOG::RES_NETLINK] = $this->db->GetLastInsertID('netlinks');
                    $this->syslog->AddMessage(
                        SYSLOG::RES_NETLINK,
                        SYSLOG::OPER_ADD,
                        $args,
                        array('src_' . SYSLOG::getResourceKey(SYSLOG::RES_NETDEV),
                        'dst_' . SYSLOG::getResourceKey(SYSLOG::RES_NETDEV),
                        'src_' . SYSLOG::getResourceKey(SYSLOG::RES_RADIOSECTOR),
                        'dst_' . SYSLOG::getResourceKey(SYSLOG::RES_RADIOSECTOR))
                    );
                }
                return $res;
            }
        }

        return false;
    }

    public function NetDevUnLink($dev1, $dev2)
    {
        if ($this->syslog) {
            $netlinks = $this->db->GetAll('SELECT id, src, dst FROM netlinks WHERE (src=? AND dst=?) OR (dst=? AND src=?)', array($dev1, $dev2, $dev1, $dev2));
            if (!empty($netlinks)) {
                foreach ($netlinks as $netlink) {
                    $args = array(
                    SYSLOG::RES_NETLINK => $netlink['id'],
                    'src_' . SYSLOG::getResourceKey(SYSLOG::RES_NETDEV) => $netlink['src'],
                    'dst_' . SYSLOG::getResourceKey(SYSLOG::RES_NETDEV) => $netlink['dst'],
                    );
                    $this->syslog->AddMessage(
                        SYSLOG::RES_NETLINK,
                        SYSLOG::OPER_DELETE,
                        $args,
                        array('src_' . SYSLOG::getResourceKey(SYSLOG::RES_NETDEV),
                        'dst_' . SYSLOG::getResourceKey(SYSLOG::RES_NETDEV),
                        )
                    );
                }
            }
        }
        $this->db->Execute('DELETE FROM netlinks WHERE (src=? AND dst=?) OR (dst=? AND src=?)', array($dev1, $dev2, $dev1, $dev2));
    }

    public function NetDevUpdate($data)
    {
        $old_ownerid = $this->db->GetOne('SELECT ownerid FROM netdevices WHERE id = ?', array($data['id']));
        $ownerid = empty($data['ownerid']) ? null: intval($data['ownerid']);

        $args = array();

        if (array_key_exists('name', $data)) {
            $args['name'] = $data['name'];
        }
        if (array_key_exists('description', $data)) {
            $args['description'] = empty($data['description']) ? '' : $data['description'];
        }

        if (array_key_exists('producer', $data)) {
            $args['producer'] = empty($data['producer']) ? '' : $data['producer'];
        }
        if (array_key_exists('model', $data)) {
            $args['model'] = empty($data['model']) ? '' : $data['model'];
        }

        if (preg_match('/^[0-9]+$/', $data['producerid'])) {
            if (preg_match('/^[0-9]+$/', $data['modelid'])) {
                $args['netdevicemodelid'] = $data['modelid'];
            } else {
                $args['netdevicemodelid'] = null;
                $args['producer'] = $this->db->GetOne('SELECT name FROM netdeviceproducers WHERE id = ?', array($data['producerid']));
            }
        } else {
            $args['netdevicemodelid'] = null;
        }

            $args['model'] = empty($data['model']) ? '' : $data['model'];
        if (array_key_exists('serialnumber', $data)) {
            $args['serialnumber'] = empty($data['serialnumber']) ? '' : $data['serialnumber'];
        }
        if (array_key_exists('ports', $data)) {
            $args['ports'] = empty($data['ports']) ? 0 : $data['ports'];
        }
        if (array_key_exists('purchasetime', $data)) {
            $args['purchasetime'] = empty($data['purchasetime']) ? 0 : $data['purchasetime'];
        }
        if (array_key_exists('guaranteeperiod', $data)) {
            $args['guaranteeperiod'] = $data['guaranteeperiod'];
        }
        if (array_key_exists('shortname', $data)) {
            $args['shortname'] = empty($data['shortname']) ? '' : $data['shortname'];
        }
        if (array_key_exists('nastype', $data)) {
            $args['nastype'] = empty($data['nastype']) ? 0 : $data['nastype'];
        }
        if (array_key_exists('clients', $data)) {
            $args['clients'] = empty($data['clients']) ? 0 : $data['clients'];
        }
        if (array_key_exists('secret', $data)) {
            $args['secret'] = empty($data['secret']) ? '' : $data['secret'];
        }
        if (array_key_exists('community', $data)) {
            $args['community'] = empty($data['community']) ? '' : $data['community'];
        }
        if (array_key_exists('channelid', $data)) {
            $args['channelid'] = !empty($data['channelid']) ? $data['channelid'] : null;
        }
        if (array_key_exists('longitude', $data)) {
            $args['longitude'] = !empty($data['longitude']) ? str_replace(',', '.', $data['longitude']) : null;
        }
        if (array_key_exists('latitude', $data)) {
            $args['latitude'] = !empty($data['latitude']) ? str_replace(',', '.', $data['latitude']) : null;
        }
        if (array_key_exists('projectid', $data)) {
            $args['invprojectid'] = $data['projectid'];
        }
        if (array_key_exists('netnodeid', $data)) {
            $args['netnodeid'] = $data['netnodeid'];
        }
        if (array_key_exists('status', $data)) {
            $args['status'] = empty($data['status']) ? 0 : $data['status'];
        }
        if (array_key_exists('netdevicemodelid', $data)) {
            $args['netdevicemodelid'] = !empty($data['netdevicemodelid']) ? $data['netdevicemodelid'] : null;
        }
        if (array_key_exists('ownerid', $data)) {
            $args['ownerid'] = empty($data['ownerid']) ? null : $data['ownerid'];
        }

        if (empty($args)) {
            return null;
        }

        if (!empty($args['netdevicemodelid'])) {
            $args = array_merge($args, $this->db->GetRow('SELECT p.name AS producer, m.name AS model
				FROM netdevicemodels m
				JOIN netdeviceproducers p on m.netdeviceproducerid = p.id
				WHERE m.id = ?', array($args['netdevicemodelid'])));
        }

        $res = $this->db->Execute('UPDATE netdevices SET ' . implode(' = ?, ', array_keys($args)) . ' = ?
        	WHERE id = ?', array_merge(array_values($args), array($data['id'])));

        $args[SYSLOG::RES_NETDEV] = $data['id'];

        if ($data['address_id'] && $data['address_id'] < 0) {
            $data['address_id'] = null;
        }

        $location_manager = new LMSLocationManager($this->db, $this->auth, $this->cache, $this->syslog);

        if ($data['ownerid']) {
            if ($data['address_id'] && !$this->db->GetOne('SELECT 1 FROM customer_addresses WHERE address_id = ?', array($data['address_id']))) {
                $location_manager->DeleteAddress($data['address_id']);
            }

            $this->db->Execute(
                'UPDATE netdevices SET address_id = ? WHERE id = ?',
                array(
                                    ($data['customer_address_id'] >= 0 ? $data['customer_address_id'] : null),
                                    $data['id']
                                    )
            );
        } else {
            if (!$data['address_id'] || $data['address_id'] && $this->db->GetOne('SELECT 1 FROM customer_addresses WHERE address_id = ?', array($data['address_id']))) {
                $address_id = $location_manager->InsertAddress($data);

                $this->db->Execute(
                    'UPDATE netdevices SET address_id = ? WHERE id = ?',
                    array(
                        ($address_id >= 0 ? $address_id : null),
                        $data['id']
                        )
                );
            } else {
                $location_manager->UpdateAddress($data);
            }
        }

        if ($this->syslog && $res) {
            $this->syslog->AddMessage(SYSLOG::RES_NETDEV, SYSLOG::OPER_UPDATE, $args);

            if ($old_ownerid != $ownerid) {
                $nodeassigns = $this->db->GetAll('SELECT id, nodeid, assignmentid FROM nodeassignments
					WHERE nodeid IN (SELECT id FROM nodes WHERE netdev = ? AND ownerid IS NULL)', array($data['id']));
                if (!empty($nodeassigns)) {
                    foreach ($nodeassigns as $nodeassign) {
                        $args = array(
                        SYSLOG::RES_NODEASSIGN => $nodeassign['id'],
                        SYSLOG::RES_NETDEV => $data['id'],
                        SYSLOG::RES_NODE => $nodedata['id'],
                        SYSLOG::RES_ASSIGN => $nodedata['assignmentid'],
                        SYSLOG::RES_CUST => $nodedata['ownerid']
                        );
                        $this->syslog->AddMessage(SYSLOG::RES_NODEASSIGN, SYSLOG::OPER_DELETE, $args);
                    }
                }
            }
        }

        if ($old_ownerid != $ownerid) {
            $this->db->Execute('DELETE FROM nodeassignments
				WHERE nodeid IN (SELECT id FROM nodes WHERE netdev = ? AND ownerid IS NULL)', array($data['id']));
        }

        return $res;
    }

    public function NetDevAdd($data)
    {

        $args = array(
            'name'             => $data['name'],
            'description'      => empty($data['description']) ? '' : $data['description'],
            'producer'         => empty($data['producer']) ? '' : $data['producer'],
            'model'            => empty($data['model']) ? '' : $data['model'],
            'serialnumber'     => empty($data['serialnumber']) ? '' : $data['serialnumber'],
            'ports'            => empty($data['ports']) ? 0 : $data['ports'],
            'purchasetime'     => empty($data['purchasetime']) ? 0 : $data['purchasetime'],
            'guaranteeperiod'  => $data['guaranteeperiod'],
            'shortname'        => empty($data['shortname']) ? '' : $data['shortname'],
            'nastype'          => empty($data['nastype']) ? 0 : $data['nastype'],
            'clients'          => empty($data['clients']) ? 0 : $data['clients'],
            'secret'           => empty($data['secret']) ? '' : $data['secret'],
            'community'        => empty($data['community']) ? '' : $data['community'],
            'channelid'        => !empty($data['channelid']) ? $data['channelid'] : null,
            'longitude'        => !empty($data['longitude']) ? str_replace(',', '.', $data['longitude']) : null,
            'latitude'         => !empty($data['latitude'])  ? str_replace(',', '.', $data['latitude'])  : null,
            'invprojectid'     => $data['projectid'],
            'netnodeid'        => $data['netnodeid'],
            'status'           => empty($data['status']) ? 0 : $data['status'],
            'netdevicemodelid' => null,
            'address_id'       => ($data['address_id'] >= 0 ? $data['address_id'] : null),
            'ownerid'          => !empty($data['ownerid'])  ? $data['ownerid']    : null
        );

        if (preg_match('/^[0-9]+$/', $data['producerid'])) {
            if (preg_match('/^[0-9]+$/', $data['modelid'])) {
                $args['netdevicemodelid'] = $data['modelid'];
            } else {
                $args['netdevicemodelid'] = null;
                $args['producer'] = $this->db->GetOne('SELECT name FROM netdeviceproducers WHERE id = ?', array($data['producerid']));
            }
        } else {
            $args['netdevicemodelid'] = null;
        }

        if (!empty($args['netdevicemodelid'])) {
            $args = array_merge($args, $this->db->GetRow('SELECT p.name AS producer, m.name AS model
				FROM netdevicemodels m
				JOIN netdeviceproducers p ON m.netdeviceproducerid = p.id
				WHERE m.id = ?', array($args['netdevicemodelid'])));
        }

        if ($this->db->Execute('INSERT INTO netdevices (name,
				description, producer, model, serialnumber,
				ports, purchasetime, guaranteeperiod, shortname,
				nastype, clients, secret, community, channelid,
				longitude, latitude, invprojectid, netnodeid, status, netdevicemodelid, address_id, ownerid)
				VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', array_values($args))) {
            $id = $this->db->GetLastInsertID('netdevices');

            if (empty($data['ownerid'])) {
                global $LMS;

                $address_id = $LMS->InsertAddress($data);

                if ($address_id >= 0) {
                    $this->db->Execute('UPDATE netdevices SET address_id = ? WHERE id = ?', array($address_id, $id));
                }
            } else if ($data['address_id'] && $data['address_id'] >= 0) {
                $this->db->Execute('UPDATE netdevices SET address_id = ? WHERE id = ?', array($data['address_id'], $id));
            }

            // EtherWerX support (devices have some limits)
            // We must to replace big ID with smaller (first free)
            if ($id > 99999 && ConfigHelper::checkValue(ConfigHelper::getConfig('phpui.ewx_support', false))) {
                $this->db->BeginTrans();
                $this->db->LockTables('ewx_channels');

                if ($newid = $this->db->GetOne('SELECT n.id + 1 FROM ewx_channels n
						LEFT OUTER JOIN ewx_channels n2 ON n.id + 1 = n2.id
						WHERE n2.id IS NULL AND n.id <= 99999
						ORDER BY n.id ASC LIMIT 1')) {
                    $this->db->Execute('UPDATE ewx_channels SET id = ? WHERE id = ?', array($newid, $id));
                    $id = $newid;
                }

                $this->db->UnLockTables();
                $this->db->CommitTrans();
            }

            if ($this->syslog) {
                $args[SYSLOG::RES_NETDEV] = $id;
                $this->syslog->AddMessage(SYSLOG::RES_NETDEV, SYSLOG::OPER_ADD, $args);
            }
            return $id;
        } else {
            return false;
        }
    }

    public function NetDevExists($id)
    {
        return ($this->db->GetOne('SELECT * FROM netdevices WHERE id=?', array($id)) ? true : false);
    }

    public function GetNetDevIDByNode($id)
    {
        return $this->db->GetOne('SELECT netdev FROM vnodes WHERE id=?', array($id));
    }

    public function CountNetDevLinks($id)
    {
        return $this->db->GetOne('SELECT COUNT(*) FROM netlinks WHERE src = ? OR dst = ?', array($id, $id)) + $this->db->GetOne('SELECT COUNT(*) FROM vnodes WHERE netdev = ? AND ownerid IS NOT NULL', array($id));
    }

    public function GetNetDevLinkType($dev1, $dev2)
    {
        return $this->db->GetRow(
            'SELECT type, technology, speed FROM netlinks
			WHERE (src=? AND dst=?) OR (dst=? AND src=?)',
            array($dev1, $dev2, $dev1, $dev2)
        );
    }

    public function GetNetDevConnectedNames($id)
    {
        return $this->db->GetAll('SELECT d.id, d.name, d.description,
			d.producer, d.ports, l.type AS linktype,
			l.technology AS linktechnology, l.speed AS linkspeed, l.srcport, l.dstport,
			srcrs.name AS srcradiosector, dstrs.name AS dstradiosector,
			(SELECT COUNT(*) FROM netlinks WHERE src = d.id OR dst = d.id)
			+ (SELECT COUNT(*) FROM vnodes WHERE netdev = d.id AND ownerid IS NOT NULL)
			AS takenports,
			lc.name AS city_name, lb.name AS borough_name, lb.type AS borough_type,
			ld.name AS district_name, ls.name AS state_name, addr.location
			FROM netdevices d
			LEFT JOIN vaddresses addr ON d.address_id = addr.id
			JOIN (SELECT DISTINCT type, technology, speed,
				(CASE src WHEN ? THEN dst ELSE src END) AS dev,
				(CASE src WHEN ? THEN dstport ELSE srcport END) AS srcport,
				(CASE src WHEN ? THEN srcport ELSE dstport END) AS dstport,
				(CASE src WHEN ? THEN dstradiosector ELSE srcradiosector END) AS srcradiosector,
				(CASE src WHEN ? THEN srcradiosector ELSE dstradiosector END) AS dstradiosector
				FROM netlinks WHERE src = ? OR dst = ?
			) l ON (d.id = l.dev)
			LEFT JOIN location_cities lc ON lc.id = addr.city_id
			LEFT JOIN location_boroughs lb ON lb.id = lc.boroughid
			LEFT JOIN location_districts ld ON ld.id = lb.districtid
			LEFT JOIN location_states ls ON ls.id = ld.stateid
			LEFT JOIN netradiosectors srcrs ON srcrs.id = l.srcradiosector
			LEFT JOIN netradiosectors dstrs ON dstrs.id = l.dstradiosector
			ORDER BY name', array($id, $id, $id, $id, $id, $id, $id));
    }

    public function GetNetDevList($order = 'name,asc', $search = array())
    {
        if (isset($search['count'])) {
            $count = $search['count'];
        } else {
            $count = false;
        }
        if (isset($search['offset'])) {
            $offset = $search['offset'];
        } else {
            $offset = null;
        }
        if (isset($search['limit'])) {
            $limit = $search['limit'];
        } else {
            $limit = null;
        }
        $short = isset($search['short']) && !empty($search['short']);

        list($order, $direction) = sscanf($order, '%[^,],%s');

        ($direction == 'desc') ? $direction = 'desc' : $direction = 'asc';

        switch ($order) {
            case 'id':
                $sqlord = ' ORDER BY id';
                break;
            case 'producer':
                $sqlord = ' ORDER BY producer';
                break;
            case 'model':
                $sqlord = ' ORDER BY model';
                break;
            case 'ports':
                $sqlord = ' ORDER BY ports';
                break;
            case 'takenports':
                $sqlord = ' ORDER BY takenports';
                break;
            case 'serialnumber':
                $sqlord = ' ORDER BY serialnumber';
                break;
            case 'location':
                $sqlord = ' ORDER BY location';
                break;
            case 'netnode':
                $sqlord = ' ORDER BY netnode';
                break;
            default:
                $sqlord = ' ORDER BY name';
                break;
        }

        $where = array();
        foreach ($search as $key => $value) {
            switch ($key) {
                case 'status':
                    switch ($value) {
                        case -1:
                            break;
                        case 100:
                            $where[] = 'd.id IN (
								SELECT netdevices.id FROM netdevices
								LEFT JOIN vaddresses ON vaddresses.id = netdevices.address_id
								LEFT JOIN netlinks ON netlinks.src = netdevices.id OR netlinks.dst = netdevices.id
								WHERE address_id IS NULL OR vaddresses.city_id IS NULL
								GROUP BY netdevices.id
								HAVING COUNT(netlinks.id) >= 0
							)';
                            break;
                        default:
                            $where[] = 'd.status = ' . intval($value);
                    }
                    break;
                case 'project':
                    if ($value > 0) {
                        $where[] = '(d.invprojectid = ' . intval($value)
                        . ' OR (d.invprojectid = ' . INV_PROJECT_SYSTEM . ' AND n.invprojectid = ' . intval($value) . '))';
                    } elseif ($value == -2) {
                        $where[] = '(d.invprojectid IS NULL OR (d.invprojectid = ' . INV_PROJECT_SYSTEM . ' AND n.invprojectid IS NULL))';
                    }
                    break;
                case 'netnode':
                    if ($value > 0) {
                        $where[] = 'd.netnodeid = ' . intval($value);
                    } elseif ($value == -2) {
                        $where[] = 'd.netnodeid IS NULL';
                    }
                    break;
                case 'producer':
                case 'model':
                    if (!preg_match('/^-[0-9]+$/', $value)) {
                        $where[] = "UPPER(TRIM(d.$key)) = UPPER(" . $this->db->Escape($value) . ")";
                    } elseif ($value == -2) {
                        $where[] = "d.$key = ''";
                    }
                    break;
                case 'ownerid':
                    $where[] = 'd.ownerid = ' . $value;
                    break;
            }
        }

        if ($count) {
            return $this->db->GetOne('SELECT COUNT(d.id)
				FROM netdevices d
				LEFT JOIN vaddresses addr       ON d.address_id = addr.id
				LEFT JOIN invprojects p         ON p.id = d.invprojectid
				LEFT JOIN netnodes n            ON n.id = d.netnodeid
				LEFT JOIN location_streets lst  ON lst.id = addr.street_id
				LEFT JOIN location_cities lc    ON lc.id = addr.city_id
				LEFT JOIN location_boroughs lb  ON lb.id = lc.boroughid
				LEFT JOIN location_districts ld ON ld.id = lb.districtid
				LEFT JOIN location_states ls    ON ls.id = ld.stateid '
                . (!empty($where) ? ' WHERE ' . implode(' AND ', $where) : ''));
        }

        $netdevlist = $this->db->GetAll('SELECT d.id, d.name' . ($short ? '' : ',
				d.description, d.producer, d.model, d.serialnumber, d.ports, d.ownerid,
				d.invprojectid, p.name AS project, d.status,
				(SELECT COUNT(*) FROM nodes WHERE ipaddr <> 0 AND netdev=d.id AND ownerid IS NOT NULL)
				+ (SELECT COUNT(*) FROM netlinks WHERE src = d.id OR dst = d.id)
				AS takenports, d.netnodeid, n.name AS netnode,
				lb.name AS borough_name, lb.type AS borough_type, lb.ident AS borough_ident,
				ld.name AS district_name, ld.ident AS district_ident,
				ls.name AS state_name, ls.ident AS state_ident,
				addr.state as location_state_name, addr.state_id as location_state,
				addr.zip as location_zip, addr.country_id as location_country,
				addr.city as location_city_name, addr.city_id as location_city,
				lc.ident AS city_ident,
				addr.street AS location_street_name, addr.street_id as location_street,
				(CASE WHEN lst.ident IS NULL
					THEN (CASE WHEN addr.street = \'\' THEN \'99999\' ELSE \'99998\' END)
					ELSE lst.ident END) AS street_ident,
				addr.house as location_house, addr.flat as location_flat, addr.location, no.lastonline') . '
			FROM netdevices d ' . ($short ? '' : '
			    LEFT JOIN (
			        SELECT netdev AS netdevid, MAX(lastonline) AS lastonline
			        FROM nodes
			        WHERE nodes.netdev IS NOT NULL AND nodes.ownerid IS NULL
			            AND lastonline > 0 
			        GROUP BY netdev
			    ) no ON no.netdevid = d.id ') . '
				LEFT JOIN vaddresses addr       ON d.address_id = addr.id
				LEFT JOIN invprojects p         ON p.id = d.invprojectid
				LEFT JOIN netnodes n            ON n.id = d.netnodeid
				LEFT JOIN location_streets lst  ON lst.id = addr.street_id
				LEFT JOIN location_cities lc    ON lc.id = addr.city_id
				LEFT JOIN location_boroughs lb  ON lb.id = lc.boroughid
				LEFT JOIN location_districts ld ON ld.id = lb.districtid
				LEFT JOIN location_states ls    ON ls.id = ld.stateid '
                . (!empty($where) ? ' WHERE ' . implode(' AND ', $where) : '')
                . ($sqlord != '' ? $sqlord . ' ' . $direction : '')
                . (isset($limit) ? ' LIMIT ' . $limit : '')
                . (isset($offset) ? ' OFFSET ' . $offset : ''));

        if (!$short && $netdevlist) {
            $customer_manager = new LMSCustomerManager($this->db, $this->auth, $this->cache, $this->syslog);

            $filecontainers = $this->db->GetAllByKey('SELECT fc.netdevid, '
                . $this->db->GroupConcat("CASE WHEN fc.description = '' THEN '---' ELSE fc.description END") . ' AS descriptions
			FROM filecontainers fc
			WHERE fc.netdevid IS NOT NULL
			GROUP BY fc.netdevid', 'netdevid');

            foreach ($netdevlist as &$netdev) {
                $netdev['customlinks'] = array();
                if (!$netdev['location'] && $netdev['ownerid']) {
                    $netdev['location'] = $customer_manager->getAddressForCustomerStuff($netdev['ownerid']);
                }
                $netdev['terc'] = empty($netdev['state_ident']) ? null
                    : $netdev['state_ident'] . $netdev['district_ident']
                        . $netdev['borough_ident'] . $netdev['borough_type'];
                $netdev['simc'] = empty($netdev['city_ident']) ? null : $netdev['city_ident'];
                $netdev['ulic'] = empty($netdev['street_ident']) ? null : $netdev['street_ident'];
                $netdev['filecontainers'] = isset($filecontainers[$netdev['id']])
                    ? explode(',', $filecontainers[$netdev['id']]['descriptions'])
                    : array();

                $netdev['lastonlinedate'] = lastonline_date($netdev['lastonline']);
            }
            unset($netdev);
        }

        $netdevlist['total'] = empty($netdevlist) ? 0 : count($netdevlist);
        $netdevlist['order'] = $order;
        $netdevlist['direction'] = $direction;

        return $netdevlist;
    }

    public function GetNetDevNames()
    {
        global $LMS;

        $netdevs = $this->db->GetAll('SELECT nd.id, nd.name, nd.producer, nd.ownerid,
                                         addr.city as city_name, addr.flat as location_flat,
                                         addr.house as location_house, addr.street as street_name,
                                         addr.location
                                     FROM netdevices nd
                                     LEFT JOIN vaddresses addr ON nd.address_id = addr.id
                                     ORDER BY name');

        if ($netdevs) {
            foreach ($netdevs as $k => $v) {
                if (!$v['location'] && $v['ownerid']) {
                    $netdevs[$k]['location'] = $LMS->getAddressForCustomerStuff($v['ownerid']);
                }
            }
        }

        return $netdevs;
    }

    public function GetNetDevName($id)
    {
        return $this->db->GetOne('SELECT name FROM netdevices WHERE id = ?', array($id));
    }

    public function GetNotConnectedDevices($id)
    {
        return $this->db->GetAll('SELECT d.id, d.name, d.description,
			d.producer, d.ports
			FROM netdevices d
			LEFT JOIN (SELECT DISTINCT
				(CASE src WHEN ? THEN dst ELSE src END) AS dev
				FROM netlinks WHERE src = ? OR dst = ?
			) l ON (d.id = l.dev)
			WHERE l.dev IS NULL AND d.id != ?
			ORDER BY name', array($id, $id, $id, $id));
    }

    public function GetNetDev($id)
    {
        $result = $this->db->GetRow('SELECT d.*, d.invprojectid AS projectid, t.name AS nastypename, c.name AS channel, d.ownerid,
				producer, ndm.netdeviceproducerid AS producerid, model, d.netdevicemodelid AS modelid,
				(CASE WHEN lst.name2 IS NOT NULL THEN ' . $this->db->Concat('lst.name2', "' '", 'lst.name') . ' ELSE lst.name END) AS street_name,
				lt.name AS street_type, lc.name AS city_name,
				lb.name AS borough_name, lb.type AS borough_type,
				ld.name AS district_name, ls.name AS state_name, addr.id as address_id,
				addr.name as location_name,
				addr.state as location_state_name, addr.state_id as location_state,
				addr.zip as location_zip, addr.country_id as location_country,
				addr.city as location_city_name, addr.street as location_street_name,
				addr.city_id as location_city, addr.street_id as location_street,
				addr.postoffice AS location_postoffice,
				addr.house as location_house, addr.flat as location_flat, addr.location
			FROM netdevices d
				LEFT JOIN netdevicemodels ndm      ON ndm.id = d.netdevicemodelid
				LEFT JOIN vaddresses addr          ON addr.id = d.address_id
				LEFT JOIN nastypes t               ON (t.id = d.nastype)
				LEFT JOIN ewx_channels c           ON (d.channelid = c.id)
				LEFT JOIN location_cities lc       ON (lc.id = addr.city_id)
				LEFT JOIN location_streets lst     ON (lst.id = addr.street_id)
				LEFT JOIN location_street_types lt ON (lt.id = lst.typeid)
				LEFT JOIN location_boroughs lb     ON (lb.id = lc.boroughid)
				LEFT JOIN location_districts ld    ON (ld.id = lb.districtid)
				LEFT JOIN location_states ls       ON (ls.id = ld.stateid)
			WHERE d.id = ?', array($id));

        // if location is empty and owner is set then heirdom address from owner
        if (!$result['location'] && $result['ownerid']) {
            global $LMS;

            $result['location'] = $LMS->getAddressForCustomerStuff($result['ownerid']);
        }

        $result['takenports']   = $this->CountNetDevLinks($id);
        $result['radiosectors'] = $this->db->GetAll('SELECT * FROM netradiosectors WHERE netdev = ? ORDER BY name', array($id));

        if ($result['guaranteeperiod'] != null && $result['guaranteeperiod'] != 0) {
            $result['guaranteetime'] = strtotime('+' . $result['guaranteeperiod'] . ' month', $result['purchasetime']); // transform to UNIX timestamp
        } elseif ($result['guaranteeperiod'] == null) {
            $result['guaranteeperiod'] = -1;
        }

        if ($result['ownerid']) {
            $customer_manager = new LMSCustomerManager($this->db, $this->auth, $this->cache, $this->syslog);
            $result['owner'] = $customer_manager->getCustomerName($result['ownerid']);
        }

        return $result;
    }

    public function NetDevDelLinks($id)
    {
        if ($this->syslog) {
            $netlinks = $this->db->GetAll('SELECT id, src, dst FROM netlinks WHERE src=? OR dst=?', array($id, $id));
            if (!empty($netlinks)) {
                foreach ($netlinks as $netlink) {
                    $args = array(
                    SYSLOG::RES_NETLINK => $netlink['id'],
                    'src_' . SYSLOG::getResourceKey(SYSLOG::RES_NETDEV) => $netlink['src'],
                    'dst_' . SYSLOG::getResourceKey(SYSLOG::RES_NETDEV) => $netlink['dst'],
                    );
                    $this->syslog->AddMessage(
                        SYSLOG::RES_NETLINK,
                        SYSLOG::OPER_DELETE,
                        $args,
                        array('src_' . SYSLOG::getResourceKey(SYSLOG::RES_NETDEV),
                        'dst_' . SYSLOG::getResourceKey(SYSLOG::RES_NETDEV))
                    );
                }
            }
            $nodes = $this->db->GetAll('SELECT id, netdev, ownerid FROM vnodes WHERE netdev=? AND ownerid IS NOT NULL', array($id));
            if (!empty($nodes)) {
                foreach ($nodes as $node) {
                    $args = array(
                    SYSLOG::RES_NODE => $node['id'],
                    SYSLOG::RES_CUST => $node['ownerid'],
                    SYSLOG::RES_NETDEV => 0,
                    'port' => 0,
                    );
                    $this->syslog->AddMessage(SYSLOG::RES_NODE, SYSLOG::OPER_UPDATE, $args);
                }
            }
        }
        $this->db->Execute('DELETE FROM netlinks WHERE src=? OR dst=?', array($id, $id));
        $this->db->Execute('UPDATE nodes SET netdev = NULL, port=0
				WHERE netdev=? AND ownerid IS NOT NULL', array($id));
    }

    public function DeleteNetDev($id)
    {
        $file_manager = new LMSFileManager($this->db, $this->auth, $this->cache, $this->syslog);
        $file_manager->DeleteFileContainers('netdevid', $id);

        $this->db->BeginTrans();
        if ($this->syslog) {
            $netlinks = $this->db->GetAll('SELECT id, src, dst FROM netlinks WHERE src = ? OR dst = ?', array($id, $id));
            if (!empty($netlinks)) {
                foreach ($netlinks as $netlink) {
                    $args = array(
                    SYSLOG::RES_NETLINK => $netlink['id'],
                    'src_' . SYSLOG::getResourceKey(SYSLOG::RES_NETDEV) => $netlink['src'],
                    'dst_' . SYSLOG::getResourceKey(SYSLOG::RES_NETDEV) => $netlink['dst'],
                    );
                    $this->syslog->AddMessage(
                        SYSLOG::RES_NETLINK,
                        SYSLOG::OPER_DELETE,
                        $args,
                        array('src_' . SYSLOG::getResourceKey(SYSLOG::RES_NETDEV),
                        'dst_' . SYSLOG::getResourceKey(SYSLOG::RES_NETDEV))
                    );
                }
            }
            $nodes = $this->db->GetCol('SELECT id FROM vnodes WHERE ownerid IS NULL AND netdev = ?', array($id));
            if (!empty($nodes)) {
                foreach ($nodes as $node) {
                    $macs = $this->db->GetCol('SELECT id FROM macs WHERE nodeid = ?', array($node));
                    if (!empty($macs)) {
                        foreach ($macs as $mac) {
                            $args = array(
                            SYSLOG::RES_MAC => $mac,
                            SYSLOG::RES_NODE => $node,
                            );
                            $this->syslog->AddMessage(SYSLOG::RES_MAC, SYSLOG::OPER_DELETE, $args);
                        }
                    }
                    $args = array(
                    SYSLOG::RES_NODE => $node,
                    SYSLOG::RES_NETDEV => $id,
                    );
                    $this->syslog->AddMessage(SYSLOG::RES_NODE, SYSLOG::OPER_DELETE, $args);
                }
            }
            $nodes = $this->db->GetAll('SELECT id, ownerid FROM vnodes WHERE ownerid IS NOT NULL AND netdev = ?', array($id));
            if (!empty($nodes)) {
                foreach ($nodes as $node) {
                    $args = array(
                    SYSLOG::RES_NODE => $node['id'],
                    SYSLOG::RES_CUST => $node['ownerid'],
                    SYSLOG::RES_NETDEV => 0,
                    );
                    $this->syslog->AddMessage(SYSLOG::RES_NODE, SYSLOG::OPER_UPDATE, $args);
                }
            }
            $args = array(SYSLOG::RES_NETDEV => $id);
            $this->syslog->AddMessage(SYSLOG::RES_NETDEV, SYSLOG::OPER_DELETE, $args);
        }

        $netdev = $this->db->GetRow('SELECT ownerid, address_id FROM netdevices WHERE id = ?', array($id));
        if (!$netdev['ownerid'] && $netdev['address_id']) {
            global $LMS;
            $LMS->DeleteAddress($netdev['address_id']);
        }

        $this->db->Execute('DELETE FROM netlinks WHERE src=? OR dst=?', array($id, $id));
        $this->db->Execute('DELETE FROM nodes WHERE ownerid IS NULL AND netdev=?', array($id));
        $this->db->Execute('UPDATE nodes SET netdev = NULL WHERE netdev=?', array($id));
        $result = $this->db->Execute('DELETE FROM netdevices WHERE id=?', array($id));

        $this->db->CommitTrans();

        return $result;
    }

    public function GetProducers()
    {
        return $this->db->GetAllByKey('SELECT id, name FROM netdeviceproducers ORDER BY name ASC', 'id');
    }

    public function GetModels($producerid = null)
    {
        if (!empty($producerid)) {
            return $this->db->GetAll(
                'SELECT id, name FROM netdevicemodels ORDER BY name ASC',
                array($producerid)
            );
        }

        $models = $this->db->GetAll('SELECT m.id, p.id AS producerid, m.name
			FROM netdevicemodels m
			JOIN netdeviceproducers p ON p.id = m.netdeviceproducerid
			ORDER BY p.id, m.name');
        if (empty($models)) {
            return array();
        }

        $result = array();
        foreach ($models as $model) {
            if (!isset($result[$model['producerid']])) {
                $result[$model['producerid']] = array();
            }
            $result[$model['producerid']][$model['id']] = $model;
        }

        return $result;
    }

    public function GetRadioSectors($netdevid, $technology = 0)
    {
        $radiosectors = $this->db->GetAll('SELECT s.*, (CASE WHEN n.computers IS NULL THEN 0 ELSE n.computers END) AS computers,
				((CASE WHEN l1.devices IS NULL THEN 0 ELSE l1.devices END)
				+ (CASE WHEN l2.devices IS NULL THEN 0 ELSE l2.devices END)) AS devices
			FROM netradiosectors s
			LEFT JOIN (
				SELECT linkradiosector AS rs, COUNT(*) AS computers
				FROM nodes n WHERE n.ownerid IS NOT NULL AND linkradiosector IS NOT NULL
				GROUP BY rs
			) n ON n.rs = s.id
			LEFT JOIN (
				SELECT srcradiosector, COUNT(*) AS devices FROM netlinks GROUP BY srcradiosector
			) l1 ON l1.srcradiosector = s.id
			LEFT JOIN (
				SELECT dstradiosector, COUNT(*) AS devices FROM netlinks GROUP BY dstradiosector
			) l2 ON l2.dstradiosector = s.id
			WHERE s.netdev = ?' . ($technology ? ' AND (technology = ' . intval($technology) . ' OR technology = 0)' : '') . '
			ORDER BY s.name', array($netdevid));

        if (!empty($radiosectors)) {
            foreach ($radiosectors as &$radiosector) {
                if (!empty($radiosector['bandwidth'])) {
                    $radiosector['bandwidth'] *= 1000;
                }
            }
            unset($radiosector);
        }

        return $radiosectors;
    }

    public function AddRadioSector($netdevid, array $radiosector)
    {
        $args = array(
            'name' => $radiosector['name'],
            'azimuth' => $radiosector['azimuth'],
            'width' => $radiosector['width'],
            'altitude' => $radiosector['altitude'],
            'rsrange' => $radiosector['rsrange'],
            'license' => (strlen($radiosector['license']) ? $radiosector['license'] : null),
            'technology' => intval($radiosector['technology']),
            'type' => intval($radiosector['type']),
            'frequency' => (strlen($radiosector['frequency']) ? $radiosector['frequency'] : null),
            'frequency2' => (strlen($radiosector['frequency2']) ? $radiosector['frequency2'] : null),
            'bandwidth' => (strlen($radiosector['bandwidth']) ? str_replace(',', '.', $radiosector['bandwidth'] / 1000) : null),
            SYSLOG::RES_NETDEV => $netdevid,
            'secret' => intval($radiosector['secret']),
        );

        $this->db->Execute(
            'INSERT INTO netradiosectors (name, azimuth, width, altitude, rsrange, license, technology, type,
			frequency, frequency2, bandwidth, netdev, secret)
			VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            array_values($args)
        );

        $rsid = $this->db->GetLastInsertID('netradiosectors');

        if ($rsid && $this->syslog) {
            $args[SYSLOG::RES_RADIOSECTOR] = $rsid;
            $this->syslog->AddMessage(SYSLOG::RES_RADIOSECTOR, SYSLOG::OPER_ADD, $args);
        }

        return $rsid;
    }

    public function DeleteRadioSector($id)
    {
        if ($this->syslog) {
            $netdevid = $this->db->GetOne(
                'SELECT netdev FROM netradiosectors WHERE id = ?',
                array($id)
            );
        }

        $res = $this->db->Execute('DELETE FROM netradiosectors WHERE id = ?', array($id));

        if ($res && $this->syslog) {
            $args = array(
                SYSLOG::RES_RADIOSECTOR => $id,
                SYSLOG::RES_NETDEV => $netdevid,
            );
            $this->syslog->AddMessage(SYSLOG::RES_RADIOSECTOR, SYSLOG::OPER_DELETE, $args);
        }
    }

    public function UpdateRadioSector($id, array $radiosector)
    {
        $args = array(
            'name' => $radiosector['name'],
            'azimuth' => $radiosector['azimuth'],
            'width' => $radiosector['width'],
            'altitude' => $radiosector['altitude'],
            'rsrange' => $radiosector['rsrange'],
            'license' => (strlen($radiosector['license']) ? $radiosector['license'] : null),
            'technology' => intval($radiosector['technology']),
            'type' => intval($radiosector['type']),
            'secret' => $radiosector['secret'],
            'frequency' => (strlen($radiosector['frequency']) ? $radiosector['frequency'] : null),
            'frequency2' => (strlen($radiosector['frequency2']) ? $radiosector['frequency2'] : null),
            'bandwidth' => (strlen($radiosector['bandwidth']) ? str_replace(',', '.', $radiosector['bandwidth'] / 1000) : null),
            SYSLOG::RES_RADIOSECTOR => $id,
        );

        $res = $this->db->Execute('UPDATE netradiosectors SET name = ?, azimuth = ?, width = ?, altitude = ?,
			rsrange = ?, license = ?, technology = ?, type = ?, secret = ?,
			frequency = ?, frequency2 = ?, bandwidth = ? WHERE id = ?', array_values($args));

        if ($res && $this->syslog) {
            $args[SYSLOG::RES_NETDEV] = $this->db->GetOne(
                'SELECT netdev FROM netradiosectors WHERE id = ?',
                array($id)
            );
            $this->syslog->AddMessage(SYSLOG::RES_RADIOSECTOR, SYSLOG::OPER_UPDATE, $args);
        }

        return $res;
    }

    public function GetManagementUrls($type, $id)
    {
        return $this->db->GetAll(
            'SELECT id, url, comment FROM managementurls WHERE '
            . ($type == self::NETDEV_URL ? 'netdevid' : 'nodeid') . ' = ? ORDER BY id',
            array($id)
        );
    }

    public function AddManagementUrl($type, $id, array $url)
    {
        if ($type == self::NETDEV_URL) {
            $args = array(
                SYSLOG::RES_NETDEV => $id,
                'url' => $url['url'],
                'comment' => $url['comment'],
            );
            $this->db->Execute(
                'INSERT INTO managementurls (netdevid, url, comment) VALUES (?, ?, ?)',
                array_values($args)
            );
        } else {
            $args = array(
                SYSLOG::RES_NODE => $id,
                'url' => $url['url'],
                'comment' => $url['comment'],
            );
            $this->db->Execute(
                'INSERT INTO managementurls (nodeid, url, comment) VALUES (?, ?, ?)',
                array_values($args)
            );
        }

        $urlid = $this->db->GetLastInsertID('managementurls');

        if ($urlid && $this->syslog) {
            $args[SYSLOG::RES_MGMTURL] = $urlid;
            $this->syslog->AddMessage(SYSLOG::RES_MGMTURL, SYSLOG::OPER_ADD, $args);
        }

        return $urlid;
    }

    public function DeleteManagementUrl($type, $id)
    {
        $res = $this->db->Execute('DELETE FROM managementurls WHERE id = ?', array($id));

        if ($res && $this->syslog) {
            $args = array(
                SYSLOG::RES_MGMTURL => $id,
                ($type == self::NETDEV_URL ? SYSLOG::RES_NETDEV : SYSLOG::RES_NODE) => $id,
            );
            $this->syslog->AddMessage(SYSLOG::RES_MGMTURL, SYSLOG::OPER_DELETE, $args);
        }

        return $res;
    }

    public function updateManagementUrl($type, $id, array $url)
    {
        $args = array(
            'url' => $url['url'],
            'comment' => $url['comment'],
            SYSLOG::RES_MGMTURL => $id,
        );

        $res = $this->db->Execute(
            'UPDATE managementurls SET url = ?, comment = ? WHERE id = ?',
            array_values($args)
        );

        if ($res && $this->syslog) {
            if ($type == self::NETDEV_URL) {
                $args[SYSLOG::RES_NETDEV] = $this->db->GetOne(
                    'SELECT netdevid FROM managementurls WHERE id = ?',
                    array($id)
                );
            } else {
                $args[SYSLOG::RES_NODE] = $this->db->GetOne(
                    'SELECT nodeid FROM managementurls WHERE id = ?',
                    array($id)
                );
            }

            $this->syslog->AddMessage(SYSLOG::RES_MGMTURL, SYSLOG::OPER_UPDATE, $args);
        }

        return $res;
    }
}
