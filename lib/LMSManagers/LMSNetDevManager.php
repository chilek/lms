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
 * LMSNetDevManager
 *
 * @author Maciej Lew <maciej.lew.1987@gmail.com>
 */
class LMSNetDevManager extends LMSManager implements LMSNetDevManagerInterface
{

    public function GetNetDevLinkedNodes($id)
    {
        return $this->db->GetAll('SELECT n.id AS id, n.name AS name, linktype, rs.name AS radiosector,
        		linktechnology, linkspeed,
			ipaddr, inet_ntoa(ipaddr) AS ip, ipaddr_pub, inet_ntoa(ipaddr_pub) AS ip_pub, 
			n.netdev, port, ownerid,
			' . $this->db->Concat('c.lastname', "' '", 'c.name') . ' AS owner,
			net.name AS netname
			FROM vnodes n
			JOIN customersview c ON c.id = ownerid
			JOIN networks net ON net.id = n.netid
			LEFT JOIN netradiosectors rs ON rs.id = n.linkradiosector
			WHERE n.netdev = ? AND ownerid > 0 
			ORDER BY n.name ASC', array($id));
    }

    public function NetDevLinkNode($id, $devid, $link = NULL)
    {
        global $SYSLOG_RESOURCE_KEYS;

	if (empty($link)) {
		$type = 0;
		$technology = 0;
		$radiosector = NULL;
		$speed = 100000;
		$port = 0;
	} else {
		$type = isset($link['type']) ? intval($link['type']) : 0;
		$radiosector = isset($link['radiosector']) ? intval($link['radiosector']) : NULL;
		$technology = isset($link['technology']) ? intval($link['technology']) : 0;
		$speed = isset($link['speed']) ? intval($link['speed']) : 100000;
		$port = isset($link['port']) ? intval($link['port']) : 0;
	}

        $args = array(
            $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NETDEV] => $devid,
            'linktype' => $type,
            'linkradiosector' => $radiosector,
            'linktechnology' => $technology,
            'linkspeed' => $speed,
            'port' => intval($port),
            $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NODE] => $id,
        );
        $res = $this->db->Execute('UPDATE nodes SET netdev=?, linktype=?, linkradiosector=?,
			linktechnology=?, linkspeed=?, port=?
			WHERE id=?', array_values($args));
        if ($this->syslog && $res) {
            $args[$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST]] = $this->db->GetOne('SELECT ownerid FROM vnodes WHERE id=?', array($id));
            $this->syslog->AddMessage(SYSLOG_RES_NODE, SYSLOG_OPER_UPDATE, $args, array($SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NODE],
                $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NETDEV],
                $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST]));
        }
        return $res;
    }

    public function SetNetDevLinkType($dev1, $dev2, $link = NULL)
    {
	global $SYSLOG_RESOURCE_KEYS;

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
		'src_' . $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_RADIOSECTOR] => $srcradiosector,
		'dst_' . $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_RADIOSECTOR] => $dstradiosector,
		'technology' => $technology,
		'speed' => $speed,
		'src_' . $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NETDEV] => $dev2,
		'dst_' . $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NETDEV] => $dev1,
	);
	$res = $this->db->Execute('UPDATE netlinks SET type=?, srcradiosector=?, dstradiosector=?, technology=?, speed=?
		WHERE src=? AND dst=?', array_values($args));
	if (!$res) {
		$args = array(
			'type' => $type,
			'src_' . $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_RADIOSECTOR] => $srcradiosector,
			'dst_' . $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_RADIOSECTOR] => $dstradiosector,
			'technology' => $technology,
			'speed' => $speed,
			'src_' . $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NETDEV] => $dev1,
			'dst_' . $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NETDEV] => $dev2,
		);
		$res = $this->db->Execute('UPDATE netlinks SET type=?, dstradiosector=?, srcradiosector=?, technology=?, speed=?
			WHERE src=? AND dst=?', array_values($args));
	}
	if ($this->syslog && $res) {
		$args[$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NETLINK]] =
			$this->db->GetOne('SELECT id FROM netlinks WHERE (src=? AND dst=?) OR (dst=? AND src=?)', array($dev1, $dev2, $dev1, $dev2));
		$this->syslog->AddMessage(SYSLOG_RES_NETLINK, SYSLOG_OPER_UPDATE, $args, array($SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NETLINK],
			'src_' . $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NETDEV],
			'dst_' . $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NETDEV],
			'src_' . $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_RADIOSECTOR],
			'dst_' . $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_RADIOSECTOR],
		));
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
        global $SYSLOG_RESOURCE_KEYS;

	$type = $link['type'];
	$srcradiosector = ($type == 1 ?
		(isset($link['srcradiosector']) && intval($link['srcradiosector']) ? intval($link['srcradiosector']) : null) : null);
	$dstradiosector = ($type == 1 ?
		(isset($link['dstradiosector']) && intval($link['dstradiosector']) ? intval($link['dstradiosector']) : null) : null);
	$technology = $link['technology'];
	$speed = $link['speed'];
	$sport = $link['srcport'];
	$dport = $link['dstport'];

        if ($dev1 != $dev2)
            if (!$this->IsNetDevLink($dev1, $dev2)) {
                $args = array(
                    'src_' . $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NETDEV] => $dev1,
                    'dst_' . $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NETDEV] => $dev2,
                    'type' => $type,
                    'src_' . $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_RADIOSECTOR] => $srcradiosector,
                    'dst_' . $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_RADIOSECTOR] => $dstradiosector,
                    'technology' => $technology,
                    'speed' => $speed,
                    'srcport' => intval($sport),
                    'dstport' => intval($dport),
                );
                $res = $this->db->Execute('INSERT INTO netlinks 
					(src, dst, type, srcradiosector, dstradiosector, technology, speed, srcport, dstport) 
					VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)', array_values($args));
                if ($this->syslog && $res) {
                    $args[$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NETLINK]] = $this->db->GetLastInsertID('netlinks');
                    $this->syslog->AddMessage(SYSLOG_RES_NETLINK, SYSLOG_OPER_ADD, $args, array($SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NETLINK],
                        'src_' . $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NETDEV],
                        'dst_' . $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NETDEV],
                        'src_' . $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_RADIOSECTOR],
                        'dst_' . $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_RADIOSECTOR]));
                }
                return $res;
            }

        return FALSE;
    }

    public function NetDevUnLink($dev1, $dev2)
    {
        global $SYSLOG_RESOURCE_KEYS;

        if ($this->syslog) {
            $netlinks = $this->db->GetAll('SELECT id, src, dst FROM netlinks WHERE (src=? AND dst=?) OR (dst=? AND src=?)', array($dev1, $dev2, $dev1, $dev2));
            if (!empty($netlinks))
                foreach ($netlinks as $netlink) {
                    $args = array(
                        $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NETLINK] => $netlink['id'],
                        'src_' . $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NETDEV] => $netlink['src'],
                        'dst_' . $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NETDEV] => $netlink['dst'],
                    );
                    $this->syslog->AddMessage(SYSLOG_RES_NETLINK, SYSLOG_OPER_DELETE, $args, array_keys($args));
                }
        }
        $this->db->Execute('DELETE FROM netlinks WHERE (src=? AND dst=?) OR (dst=? AND src=?)', array($dev1, $dev2, $dev1, $dev2));
    }

    public function NetDevUpdate($data)
    {
        global $SYSLOG_RESOURCE_KEYS;

        $args = array(
            'name' => $data['name'],
            'description' => $data['description'],
            'producer' => $data['producer'],
            'location' => trim($data['location']),
            'location_city' => $data['location_city'] ? trim($data['location_city']) : null,
            'location_street' => $data['location_street'] ? trim($data['location_street']) : null,
            'location_house' => $data['location_house'] ? trim($data['location_house']) : null,
            'location_flat' => $data['location_flat'] ? trim($data['location_flat']) : null,
            'model' => $data['model'],
            'serialnumber' => $data['serialnumber'],
            'ports' => $data['ports'],
            'purchasetime' => $data['purchasetime'],
            'guaranteeperiod' => $data['guaranteeperiod'],
            'shortname' => $data['shortname'],
            'nastype' => $data['nastype'],
            'clients' => $data['clients'],
            'secret' => $data['secret'],
            'community' => $data['community'],
            'channelid' => !empty($data['channelid']) ? $data['channelid'] : NULL,
            'longitude' => !empty($data['longitude']) ? str_replace(',', '.', $data['longitude']) : null,
            'latitude' => !empty($data['latitude']) ? str_replace(',', '.', $data['latitude']) : null,
            'invprojectid' => $data['invprojectid'],
            'netnodeid' => $data['netnodeid'],
            'status' => $data['status'],
            'netdevicemodelid' => !empty($data['netdevicemodelid']) ? $data['netdevicemodelid'] : null,
            $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NETDEV] => $data['id'],
        );
        $res = $this->db->Execute('UPDATE netdevices SET name=?, description=?, producer=?, location=?,
				location_city=?, location_street=?, location_house=?, location_flat=?,
				model=?, serialnumber=?, ports=?, purchasetime=?, guaranteeperiod=?, shortname=?,
				nastype=?, clients=?, secret=?, community=?, channelid=?, longitude=?, latitude=?,
				invprojectid=?, netnodeid=?, status=?, netdevicemodelid=?
				WHERE id=?', array_values($args));
        if ($this->syslog && $res)
            $this->syslog->AddMessage(SYSLOG_RES_NETDEV, SYSLOG_OPER_UPDATE, $args, array($SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NETDEV]));
    }

    public function NetDevAdd($data)
    {
        global $SYSLOG_RESOURCE_KEYS;

        $args = array(
            'name' => $data['name'],
            'location' => trim($data['location']),
            'location_city' => $data['location_city'] ? trim($data['location_city']) : null,
            'location_street' => $data['location_street'] ? trim($data['location_street']) : null,
            'location_house' => $data['location_house'] ? trim($data['location_house']) : null,
            'location_flat' => $data['location_flat'] ? trim($data['location_flat']) : null,
            'description' => $data['description'],
            'producer' => $data['producer'],
            'model' => $data['model'],
            'serialnumber' => $data['serialnumber'],
            'ports' => $data['ports'],
            'purchasetime' => $data['purchasetime'],
            'guaranteeperiod' => $data['guaranteeperiod'],
            'shortname' => $data['shortname'],
            'nastype' => $data['nastype'],
            'clients' => $data['clients'],
            'secret' => $data['secret'],
            'community' => $data['community'],
            'channelid' => !empty($data['channelid']) ? $data['channelid'] : NULL,
            'longitude' => !empty($data['longitude']) ? str_replace(',', '.', $data['longitude']) : NULL,
            'latitude' => !empty($data['latitude']) ? str_replace(',', '.', $data['latitude']) : NULL,
            'invprojectid' => $data['invprojectid'],
            'netnodeid' => $data['netnodeid'],
            'status' => $data['status'],
            'netdevicemodelid' => !empty($data['netdevicemodelid']) ? $data['netdevicemodelid'] : null,
        );
        if ($this->db->Execute('INSERT INTO netdevices (name, location,
				location_city, location_street, location_house, location_flat,
				description, producer, model, serialnumber,
				ports, purchasetime, guaranteeperiod, shortname,
				nastype, clients, secret, community, channelid,
				longitude, latitude, invprojectid, netnodeid, status, netdevicemodelid)
				VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', array_values($args))) {
            $id = $this->db->GetLastInsertID('netdevices');

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
                $args[$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NETDEV]] = $id;
                $this->syslog->AddMessage(SYSLOG_RES_NETDEV, SYSLOG_OPER_ADD, $args, array($SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NETDEV]));
            }
            return $id;
        } else
            return FALSE;
    }

    public function NetDevExists($id)
    {
        return ($this->db->GetOne('SELECT * FROM netdevices WHERE id=?', array($id)) ? TRUE : FALSE);
    }

    public function GetNetDevIDByNode($id)
    {
        return $this->db->GetOne('SELECT netdev FROM vnodes WHERE id=?', array($id));
    }

    public function CountNetDevLinks($id)
    {
        return $this->db->GetOne('SELECT COUNT(*) FROM netlinks WHERE src = ? OR dst = ?', array($id, $id)) + $this->db->GetOne('SELECT COUNT(*) FROM vnodes WHERE netdev = ? AND ownerid > 0', array($id));
    }

    public function GetNetDevLinkType($dev1, $dev2)
    {
        return $this->db->GetRow('SELECT type, technology, speed FROM netlinks
			WHERE (src=? AND dst=?) OR (dst=? AND src=?)',
			array($dev1, $dev2, $dev1, $dev2));
    }

    public function GetNetDevConnectedNames($id)
    {
        return $this->db->GetAll('SELECT d.id, d.name, d.description,
			d.location, d.producer, d.ports, l.type AS linktype,
			l.technology AS linktechnology, l.speed AS linkspeed, l.srcport, l.dstport,
			srcrs.name AS srcradiosector, dstrs.name AS dstradiosector,
			(SELECT COUNT(*) FROM netlinks WHERE src = d.id OR dst = d.id) 
			+ (SELECT COUNT(*) FROM vnodes WHERE netdev = d.id AND ownerid > 0)
			AS takenports,
			lc.name AS city_name, lb.name AS borough_name, lb.type AS borough_type,
			ld.name AS district_name, ls.name AS state_name
			FROM netdevices d
			JOIN (SELECT DISTINCT type, technology, speed, 
				(CASE src WHEN ? THEN dst ELSE src END) AS dev, 
				(CASE src WHEN ? THEN dstport ELSE srcport END) AS srcport, 
				(CASE src WHEN ? THEN srcport ELSE dstport END) AS dstport, 
				(CASE src WHEN ? THEN dstradiosector ELSE srcradiosector END) AS srcradiosector,
				(CASE src WHEN ? THEN srcradiosector ELSE dstradiosector END) AS dstradiosector
				FROM netlinks WHERE src = ? OR dst = ?
			) l ON (d.id = l.dev)
			LEFT JOIN location_cities lc ON lc.id = d.location_city
			LEFT JOIN location_boroughs lb ON lb.id = lc.boroughid
			LEFT JOIN location_districts ld ON ld.id = lb.districtid
			LEFT JOIN location_states ls ON ls.id = ld.stateid
			LEFT JOIN netradiosectors srcrs ON srcrs.id = l.srcradiosector
			LEFT JOIN netradiosectors dstrs ON dstrs.id = l.dstradiosector
			ORDER BY name', array($id, $id, $id, $id, $id, $id, $id));
    }

    public function GetNetDevList($order = 'name,asc', $search = array())
    {
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
	foreach ($search as $key => $value)
		switch ($key) {
			case 'status':
				if ($value != -1)
					$where[] = 'd.status = ' . intval($value);
				break;
			case 'project':
				if ($value > 0)
					$where[] = '(d.invprojectid = ' . intval($value)
						. ' OR (d.invprojectid = ' . INV_PROJECT_SYSTEM . ' AND n.invprojectid = ' . intval($value) . '))';
				elseif ($value == -2)
					$where[] = '(d.invprojectid IS NULL OR (d.invprojectid = ' . INV_PROJECT_SYSTEM . ' AND n.invprojectid IS NULL))';
				break;
			case 'netnode':
				if ($value > 0)
					$where[] = 'd.netnodeid = ' . intval($value);
				elseif ($value == -2)
					$where[] = 'd.netnodeid IS NULL';
				break;
			case 'producer':
			case 'model':
				if (!preg_match('/^-[0-9]+$/', $value))
					$where[] = "UPPER(TRIM(d.$key)) = UPPER(" . $this->db->Escape($value) . ")";
				elseif ($value == -2)
					$where[] = "d.$key = ''";
				break;
		}

	$netdevlist = $this->db->GetAll('SELECT d.id, d.name, d.location,
			d.description, d.producer, d.model, d.serialnumber, d.ports,
			(SELECT COUNT(*) FROM nodes WHERE ipaddr <> 0 AND netdev=d.id AND ownerid > 0)
			+ (SELECT COUNT(*) FROM netlinks WHERE src = d.id OR dst = d.id)
			AS takenports, d.netnodeid, n.name AS netnode,
			lb.name AS borough_name, lb.type AS borough_type,
			ld.name AS district_name, ls.name AS state_name
			FROM netdevices d
			LEFT JOIN invprojects p ON p.id = d.invprojectid
			LEFT JOIN netnodes n ON n.id = d.netnodeid
			LEFT JOIN location_cities lc ON lc.id = d.location_city
			LEFT JOIN location_boroughs lb ON lb.id = lc.boroughid
			LEFT JOIN location_districts ld ON ld.id = lb.districtid
			LEFT JOIN location_states ls ON ls.id = ld.stateid '
			. (!empty($where) ? ' WHERE ' . implode(' AND ', $where) : '')
                . ($sqlord != '' ? $sqlord . ' ' . $direction : ''));

        $netdevlist['total'] = sizeof($netdevlist);
        $netdevlist['order'] = $order;
        $netdevlist['direction'] = $direction;

        return $netdevlist;
    }

    public function GetNetDevNames()
    {
        return $this->db->GetAll('SELECT id, name, location, producer 
			FROM netdevices ORDER BY name');
    }

    public function GetNotConnectedDevices($id)
    {
        return $this->db->GetAll('SELECT d.id, d.name, d.description,
			d.location, d.producer, d.ports
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
        $result = $this->db->GetRow('SELECT d.*, t.name AS nastypename, c.name AS channel,
				(CASE WHEN lst.name2 IS NOT NULL THEN ' . $this->db->Concat('lst.name2', "' '", 'lst.name') . ' ELSE lst.name END) AS street_name,
				lt.name AS street_type,
				lc.name AS city_name,
				lb.name AS borough_name, lb.type AS borough_type,
				ld.name AS district_name, ls.name AS state_name
			FROM netdevices d
			LEFT JOIN nastypes t ON (t.id = d.nastype)
			LEFT JOIN ewx_channels c ON (d.channelid = c.id)
			LEFT JOIN location_cities lc ON (lc.id = d.location_city)
			LEFT JOIN location_streets lst ON (lst.id = d.location_street)
			LEFT JOIN location_street_types lt ON (lt.id = lst.typeid)
			LEFT JOIN location_boroughs lb ON (lb.id = lc.boroughid)
			LEFT JOIN location_districts ld ON (ld.id = lb.districtid)
			LEFT JOIN location_states ls ON (ls.id = ld.stateid)
			WHERE d.id = ?', array($id));

        $result['takenports'] = $this->CountNetDevLinks($id);
	$result['radiosectors'] = $this->db->GetAll('SELECT * FROM netradiosectors WHERE netdev = ? ORDER BY name', array($id));

        if ($result['guaranteeperiod'] != NULL && $result['guaranteeperiod'] != 0)
            $result['guaranteetime'] = strtotime('+' . $result['guaranteeperiod'] . ' month', $result['purchasetime']); // transform to UNIX timestamp
        elseif ($result['guaranteeperiod'] == NULL)
            $result['guaranteeperiod'] = -1;

        return $result;
    }

    public function NetDevDelLinks($id)
    {
        global $SYSLOG_RESOURCE_KEYS;

        if ($this->syslog) {
            $netlinks = $this->db->GetAll('SELECT id, src, dst FROM netlinks WHERE src=? OR dst=?', array($id, $id));
            if (!empty($netlinks))
                foreach ($netlinks as $netlink) {
                    $args = array(
                        $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NETLINK] => $netlink['id'],
                        'src_' . $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NETDEV] => $netlink['src'],
                        'dst_' . $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NETDEV] => $netlink['dst'],
                    );
                    $this->syslog->AddMessage(SYSLOG_RES_NETLINK, SYSLOG_OPER_DELETE, $args, array_keys($args));
                }
            $nodes = $this->db->GetAll('SELECT id, netdev, ownerid FROM vnodes WHERE netdev=? AND ownerid>0', array($id));
            if (!empty($nodes))
                foreach ($nodes as $node) {
                    $args = array(
                        $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NODE] => $node['id'],
                        $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST] => $node['ownerid'],
                        $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NETDEV] => 0,
                        'port' => 0,
                    );
                    $this->syslog->AddMessage(SYSLOG_RES_NODE, SYSLOG_OPER_UPDATE, $args, array($SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NODE],
                        $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST],
                        $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NETDEV]));
                }
        }
        $this->db->Execute('DELETE FROM netlinks WHERE src=? OR dst=?', array($id, $id));
        $this->db->Execute('UPDATE nodes SET netdev=0, port=0 
				WHERE netdev=? AND ownerid>0', array($id));
    }

    public function DeleteNetDev($id)
    {
        global $SYSLOG_RESOURCE_KEYS;

        $this->db->BeginTrans();
        if ($this->syslog) {
            $netlinks = $this->db->GetAll('SELECT id, src, dst FROM netlinks WHERE src = ? OR dst = ?', array($id, $id));
            if (!empty($netlinks))
                foreach ($netlinks as $netlink) {
                    $args = array(
                        $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NETLINK] => $netlink['id'],
                        'src_' . $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NETDEV] => $netlink['src'],
                        'dst_' . $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NETDEV] => $netlink['dst'],
                    );
                    $this->syslog->AddMessage(SYSLOG_RES_NETLINK, SYSLOG_OPER_DELETE, $args, array_keys($args));
                }
            $nodes = $this->db->GetCol('SELECT id FROM vnodes WHERE ownerid = 0 AND netdev = ?', array($id));
            if (!empty($nodes))
                foreach ($nodes as $node) {
                    $macs = $this->db->GetCol('SELECT id FROM macs WHERE nodeid = ?', array($node));
                    if (!empty($macs))
                        foreach ($macs as $mac) {
                            $args = array(
                                $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_MAC] => $mac,
                                $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NODE] => $node,
                            );
                            $this->syslog->AddMessage(SYSLOG_RES_MAC, SYSLOG_OPER_DELETE, $args, array_keys($args));
                        }
                    $args = array(
                        $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NODE] => $node,
                        $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NETDEV] => $id,
                    );
                    $this->syslog->AddMessage(SYSLOG_RES_NODE, SYSLOG_OPER_DELETE, $args, array_keys($args));
                }
            $nodes = $this->db->GetAll('SELECT id, ownerid FROM vnodes WHERE ownerid <> 0 AND netdev = ?', array($id));
            if (!empty($nodes))
                foreach ($nodes as $node) {
                    $args = array(
                        $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NODE] => $node['id'],
                        $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST] => $node['ownerid'],
                        $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NETDEV] => 0,
                    );
                    $this->syslog->AddMessage(SYSLOG_RES_NODE, SYSLOG_OPER_UPDATE, $args, array_keys($args));
                }
            $args = array($SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NETDEV] => $id);
            $this->syslog->AddMessage(SYSLOG_RES_NETDEV, SYSLOG_OPER_DELETE, $args, array_keys($args));
        }
        $this->db->Execute('DELETE FROM netlinks WHERE src=? OR dst=?', array($id, $id));
        $this->db->Execute('DELETE FROM nodes WHERE ownerid=0 AND netdev=?', array($id));
        $this->db->Execute('UPDATE nodes SET netdev=0 WHERE netdev=?', array($id));
        $this->db->Execute('DELETE FROM netdevices WHERE id=?', array($id));
        $this->db->CommitTrans();
    }

}
