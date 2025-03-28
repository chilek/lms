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

class LMSNetNodeManager extends LMSManager implements LMSNetNodeManagerInterface
{

    public function GetNetNodeList($search = array(), $order = 'name,asc')
    {
        global $NETWORK_NODE_FLAGS, $NETWORK_NODE_SERVICES;

        $search = $search ?? array();
        $order = $order ?? 'name,asc';

        [$order, $dir] = sscanf($order, '%[^,],%s');
        ($dir == 'desc') ? $dir = 'desc' : $dir = 'asc';
        $short = !empty($search['short']);

        $count = $search['count'] ?? false;
        $offset = $search['offset'] ?? null;
        $limit = $search['limit'] ?? null;

        switch ($order) {
            case 'id':
                $ostr = 'ORDER BY n.id';
                break;
            case 'type':
                $ostr = 'ORDER BY n.type';
                break;
            case 'status':
                $ostr = 'ORDER BY n.status';
                break;
            case 'lastinspectiontime':
                $ostr = 'ORDER BY n.lastinspectiontime';
                break;
            case 'netdevcount':
                $ostr = 'ORDER BY netdevcount.netdevcount';
                break;
            case 'name':
            default:
                $ostr = 'ORDER BY n.name';
                break;
        }

        $where = array();
        foreach ($search as $key => $value) {
            if (is_array($value)) {
                $val = Utils::filterIntegers($value);
            } else {
                $val = intval($value);
            }
            switch ($key) {
                case 'type':
                    if (is_array($val)) {
                        if (!in_array(-1, $val)) {
                            $where[] = 'n.type IN (' . implode(',', $val) . ')';
                        }
                    } elseif ($val != -1) {
                        $where[] = 'n.type = ' . $val;
                    }
                    break;
                case 'status':
                    if ($val != -1) {
                        $where[] = 'n.status = ' . $val;
                    }
                    break;
                case 'invprojectid':
                    if (is_array($val)) {
                        if (in_array(-2, $val)) {
                            $where[] = 'n.invprojectid IS NULL';
                        } else {
                            $where[] = 'n.invprojectid IN (' . implode(',', $val) . ')';
                        }
                    } elseif ($val == -2) {
                        $where[] = 'n.invprojectid IS NULL';
                    } elseif ($val != -1) {
                        $where[] = 'n.invprojectid = ' . $val;
                    }
                    break;
                case 'ownership':
                    if ($val != -1) {
                        $where[] = 'n.ownership = ' . $val;
                    }
                    break;
                case 'divisionid':
                    if ($val != -1 && !empty($val)) {
                        $where[] = 'n.divisionid = ' . $val;
                    }
                    break;
                case 'flags':
                    if (!empty($val)) {
                        $where[] = '(n.flags & ' . $val . ') > 0';
                    }
                    break;
                case 'services':
                    if (!empty($val)) {
                        $where[] = '(n.services ?LIKE? \'%,' . $val . ',%\' OR n.services ?LIKE? \'' . $val . ',%\' OR n.services ?LIKE? \'%,' . $val . '\' OR n.services = \'' . $val . '\')';
                    }
                    break;
            }
        }

        if ($count) {
            return $this->db->GetOne(
                'SELECT COUNT(n.id)
                FROM netnodes n
                    LEFT JOIN divisions d ON d.id = n.divisionid
                    LEFT JOIN vaddresses addr        ON addr.id = n.address_id
                    LEFT JOIN invprojects p         ON (n.invprojectid = p.id)
                    LEFT JOIN location_streets lst  ON lst.id = addr.street_id
                    LEFT JOIN location_cities lc    ON lc.id = addr.city_id
                    LEFT JOIN location_boroughs lb  ON lb.id = lc.boroughid
                    LEFT JOIN location_districts ld ON ld.id = lb.districtid
                    LEFT JOIN location_states ls    ON ls.id = ld.stateid '
                . (empty($where) ? '' : ' WHERE ' . implode(' AND ', $where))
            );
        } else {
            $nlist = $this->db->GetAllByKey(
                'SELECT n.id, n.name' . ($short ? ''
                    : ', n.type, n.status, n.invprojectid, n.info, n.lastinspectiontime, p.name AS project,
                    n.divisionid, d.shortname AS division, longitude, latitude, ownership, coowner, uip, miar,
                    n.flags,
                    n.services,
                    netdevcount.netdevcount,
                    lc.ident AS location_city_ident,
                    (CASE WHEN lst.ident IS NULL
                        THEN (CASE WHEN addr.street = \'\' THEN \'99999\' ELSE \'99998\' END)
                        ELSE lst.ident END) AS location_street_ident,
                    lb.id AS location_borough, lb.name AS location_borough_name, lb.ident AS location_borough_ident,
                    lb.type AS location_borough_type,
                    ld.id AS location_district, ld.name AS location_district_name, ld.ident AS location_district_ident,
                    ls.id AS location_state, ls.name AS location_state_name, ls.ident AS location_state_ident,
                    addr.location, addr.name as location_name,
                    addr.city as location_city_name, addr.street as location_street_name,
                    addr.city_id as location_city, addr.street_id as location_street,
                    addr.house as location_house, addr.flat as location_flat') . '
                FROM netnodes n
                ' . ($short ? ''
                : ' LEFT JOIN (
                    SELECT
                        nn.id AS netnodeid,
                        COUNT(*) AS netdevcount
                    FROM netnodes nn
                    LEFT JOIN netdevices nd ON nd.netnodeid = nn.id
                    GROUP BY nn.id
                ) netdevcount ON netdevcount.netnodeid = n.id') . '
                LEFT JOIN divisions d ON d.id = n.divisionid
                LEFT JOIN vaddresses addr        ON addr.id = n.address_id
                LEFT JOIN invprojects p         ON (n.invprojectid = p.id)
                LEFT JOIN location_streets lst  ON lst.id = addr.street_id
                LEFT JOIN location_cities lc    ON lc.id = addr.city_id
                LEFT JOIN location_boroughs lb  ON lb.id = lc.boroughid
                LEFT JOIN location_districts ld ON ld.id = lb.districtid
                LEFT JOIN location_states ls    ON ls.id = ld.stateid '
                . (empty($where) ? '' : ' WHERE ' . implode(' AND ', $where))
                . ' ' . $ostr . ' ' . $dir
                . (isset($limit) ? ' LIMIT ' . $limit : '')
                . (isset($offset) ? ' OFFSET ' . $offset : ''),
                'id'
            );
        }

        if (!empty($nlist) && !$short) {
            foreach ($nlist as &$netnode) {
                $flags = $netnode['flags'];
                $netnode['flags'] = array();
                foreach ($NETWORK_NODE_FLAGS as $flag => $label) {
                    if ($flags & $flag) {
                        $netnode['flags'][$flag] = $flag;
                    }
                }

                $netnode['services'] = array();
                if (!empty($node['services'])) {
                    $services = explode(',', $netnode['services']);
                    foreach ($services as $service) {
                        $netnodes['services'][$service] = $service;
                    }
                }
            }
            unset($netnode);
        }

        if (!$short && $nlist) {
            $filecontainers = $this->db->GetAllByKey(
                'SELECT fc.netnodeid
                FROM filecontainers fc
                WHERE fc.netnodeid IS NOT NULL
                GROUP BY fc.netnodeid',
                'netnodeid'
            );

            if (!empty($filecontainers)) {
                if (!isset($file_manager)) {
                    $file_manager = new LMSFileManager($this->db, $this->auth, $this->cache, $this->syslog);
                }
                foreach ($filecontainers as &$filecontainer) {
                    $filecontainer = $file_manager->GetFileContainers('netnodeid', $filecontainer['netnodeid']);
                }
            }

            foreach ($nlist as &$netnode) {
                $netnode['terc'] = empty($netnode['location_state_ident']) ? null
                    : $netnode['location_state_ident'] . $netnode['location_district_ident']
                    . $netnode['location_borough_ident'] . $netnode['location_borough_type'];
                $netnode['simc'] = empty($netnode['location_city_ident']) ? null : $netnode['location_city_ident'];
                $netnode['ulic'] = empty($netnode['location_street_ident']) ? null : $netnode['location_street_ident'];
                $netnode['filecontainers'] = $filecontainers[$netnode['id']] ?? array();
            }
            unset($netnode);
        }

        $nlist['total']        = empty($nlist) ? 0 : count($nlist);
        $nlist['order']        = $order;
        $nlist['direction']    = $dir;

        return $nlist;
    }

    public function NetNodeAdd($netnodedata)
    {
        if (!empty($netnodedata['ownerid']) && $netnodedata['customer_address_id'] > 0) {
            $address_id = $netnodedata['customer_address_id'];
        } elseif (!empty($netnodedata['location_city_name'])) {
            $location_manager = new LMSLocationManager($this->db, $this->auth, $this->cache, $this->syslog);
            $address_id = $location_manager->InsertAddress($netnodedata);
        } else {
            $address_id = null;
        }

        $args = array(
            'name'            => $netnodedata['name'],
            'type'            => $netnodedata['type'],
            'status'          => $netnodedata['status'],
            'longitude'       => !empty($netnodedata['longitude']) ? str_replace(',', '.', $netnodedata['longitude']) : null,
            'latitude'        => !empty($netnodedata['latitude'])  ? str_replace(',', '.', $netnodedata['latitude'])  : null,
            'ownership'       => $netnodedata['ownership'],
            'coowner'         => $netnodedata['coowner'],
            'uip'             => $netnodedata['uip'],
            'miar'            => $netnodedata['miar'],
            'createtime'      => time(),
            'divisionid'      => !empty($netnodedata['divisionid']) ? $netnodedata['divisionid'] : null,
            'invprojectid'    => intval($netnodedata['projectid']) ? $netnodedata['projectid'] : null,
            'info'        => $netnodedata['info'],
            'admcontact' => empty($netnodedata['admcontact']) ? null : $netnodedata['admcontact'],
            'lastinspectiontime' => empty($netnodedata['lastinspectiontime']) ? null : $netnodedata['lastinspectiontime'],
            'address_id'       => $address_id,
            'ownerid'          => !empty($netnodedata['ownerid']) && !empty($netnodedata['ownership']) ? $netnodedata['ownerid'] : null
        );

        $args['flags'] = 0;
        if (array_key_exists('flags', $netnodedata)) {
            foreach ($netnodedata['flags'] as $flag) {
                $args['flags'] += $flag;
            }
        }

        if (array_key_exists('services', $netnodedata)) {
            $args['services'] = implode(',', $netnodedata['services']);
        } else {
            $args['services'] = '';
        }

        $this->db->Execute("INSERT INTO netnodes (" . implode(', ', array_keys($args))
            . ") VALUES (" . implode(', ', array_fill(0, count($args), '?')) . ")", array_values($args));

        $id = $this->db->GetLastInsertID('netnodes');

        return $id;
    }

    public function NetNodeExists($id)
    {
        return $this->db->GetOne('SELECT id FROM netnodes WHERE id = ?', array($id)) > 0;
    }

    public function NetNodeDelete($id)
    {
        $addr_id = $this->db->GetOne('SELECT address_id FROM netnodes WHERE id = ?', array($id));

        if (!empty($addr_id) && !$this->db->GetOne('SELECT 1 FROM customer_addresses WHERE address_id = ?', array($addr_id))) {
            $this->db->Execute('DELETE FROM addresses WHERE id = ?', array($addr_id));
        }

        $file_manager = new LMSFileManager($this->db, $this->auth, $this->cache, $this->syslog);
        $file_manager->DeleteFileContainers('netnodeid', $id);

        return $this->db->Execute("DELETE FROM netnodes WHERE id=?", array($id));
    }

    public function NetNodeUpdate($netnodedata)
    {
        $args = array();
        if (array_key_exists('name', $netnodedata)) {
            $args['name'] = $netnodedata['name'];
        }
        if (array_key_exists('type', $netnodedata)) {
            $args['type'] = $netnodedata['type'];
        }
        if (array_key_exists('status', $netnodedata)) {
            $args['status'] = $netnodedata['status'];
        }
        if (array_key_exists('longitude', $netnodedata)) {
            $args['longitude'] = empty($netnodedata['longitude']) ? null : str_replace(',', '.', $netnodedata['longitude']);
        }
        if (array_key_exists('latitude', $netnodedata)) {
            $args['latitude'] = empty($netnodedata['latitude']) ? null : str_replace(',', '.', $netnodedata['latitude']);
        }
        if (array_key_exists('ownership', $netnodedata)) {
            $args['ownership'] = $netnodedata['ownership'];
        }
        if (array_key_exists('coowner', $netnodedata)) {
            $args['coowner'] = $netnodedata['coowner'];
        }
        if (array_key_exists('uip', $netnodedata)) {
            $args['uip'] = $netnodedata['uip'];
        }
        if (array_key_exists('miar', $netnodedata)) {
            $args['miar'] = $netnodedata['miar'];
        }
        if (array_key_exists('divisionid', $netnodedata)) {
            $args['divisionid'] = $netnodedata['divisionid'];
        }
        if (array_key_exists('projectid', $netnodedata)) {
            $args['invprojectid'] = intval($netnodedata['projectid']) ? $netnodedata['projectid'] : null;
        }
        if (array_key_exists('info', $netnodedata)) {
            $args['info'] = $netnodedata['info'];
        }
        if (array_key_exists('admcontact', $netnodedata)) {
            $args['admcontact'] = empty($netnodedata['admcontact']) ? null : $netnodedata['admcontact'];
        }
        if (array_key_exists('lastinspectiontime', $netnodedata)) {
            $args['lastinspectiontime'] = empty($netnodedata['lastinspectiontime']) ? null : $netnodedata['lastinspectiontime'];
        }
        if (array_key_exists('ownerid', $netnodedata)) {
            $args['ownerid'] = empty($netnodedata['ownerid']) || empty($netnodedata['ownership']) ? null : $netnodedata['ownerid'];
        }

        $args['flags'] = 0;
        if (array_key_exists('flags', $netnodedata)) {
            foreach ($netnodedata['flags'] as $flag) {
                $args['flags'] += $flag;
            }
        }

        if (array_key_exists('services', $netnodedata)) {
            $args['services'] = implode(',', $netnodedata['services']);
        } else {
            $args['services'] = '';
        }

        if (empty($args)) {
            return null;
        }

        $res = $this->db->Execute(
            'UPDATE netnodes SET ' . implode(' = ?, ', array_keys($args)) . ' = ? WHERE id = ?',
            array_merge(array_values($args), array($netnodedata['id']))
        );

        if ($netnodedata['address_id'] && $netnodedata['address_id'] < 0) {
            $netnodedata['address_id'] = null;
        }

        $location_manager = new LMSLocationManager($this->db, $this->auth, $this->cache, $this->syslog);

        if ($netnodedata['ownerid']) {
            if ($netnodedata['address_id'] && !$this->db->GetOne('SELECT 1 FROM customer_addresses WHERE address_id = ?', array($netnodedata['address_id']))) {
                $location_manager->DeleteAddress($netnodedata['address_id']);
            }

            $this->db->Execute(
                'UPDATE netnodes SET address_id = ? WHERE id = ?',
                array(
                    ($netnodedata['customer_address_id'] >= 0 ? $netnodedata['customer_address_id'] : null),
                    $netnodedata['id']
                )
            );
        } else {
            if (!$netnodedata['address_id'] || $netnodedata['address_id']
                && $this->db->GetOne('SELECT 1 FROM customer_addresses WHERE address_id = ?', array($netnodedata['address_id']))) {
                $address_id = $location_manager->InsertAddress($netnodedata);

                $this->db->Execute(
                    'UPDATE netnodes SET address_id = ? WHERE id = ?',
                    array(
                        ($address_id >= 0 ? $address_id : null),
                        $netnodedata['id']
                    )
                );
            } else {
                $location_manager->UpdateAddress($netnodedata);
            }
        }

        return $res;
    }

    public function GetNetNode($id)
    {
        global $NETWORK_NODE_FLAGS;

        $result = $this->db->GetRow("SELECT n.*, p.name AS projectname,
				addr.location, addr.name as location_name, addr.id as address_id,
				addr.state as location_state_name, addr.state_id as location_state,
				lb.name AS location_borough_name, lb.id AS location_borough, lb.type AS location_borough_type,
				ld.name AS location_district_name, ld.id AS location_district,
				addr.zip as location_zip, addr.country_id as location_country,
				addr.city as location_city_name, addr.street as location_street_name,
				addr.postoffice AS location_postoffice,
				addr.city_id as location_city, addr.street_id as location_street,
				addr.house as location_house, addr.flat as location_flat,
				d.shortname AS division
			FROM netnodes n
				LEFT JOIN vaddresses addr ON n.address_id = addr.id
				LEFT JOIN invprojects p ON n.invprojectid = p.id
				LEFT JOIN divisions d ON d.id = n.divisionid
				LEFT JOIN location_cities lc ON lc.id = addr.city_id
				LEFT JOIN location_boroughs lb ON lb.id = lc.boroughid
				LEFT JOIN location_districts ld ON ld.id = lb.districtid
				LEFT JOIN location_states ls ON ls.id = ld.stateid
			WHERE n.id=?", array($id));

        $flags = $result['flags'];
        $result['flags'] = array();
        foreach ($NETWORK_NODE_FLAGS as $flag => $label) {
            if ($flags & $flag) {
                $result['flags'][$flag] = $flag;
            }
        }

        $services = explode(',', $result['services']);
        $result['services'] = array();
        foreach ($services as $service) {
            $result['services'][$service] = $service;
        }

        if (!empty($result['location_city'])) {
            $result['teryt'] = 1;
        }

        // if location is empty and owner is set then heirdom address from owner
        if (!$result['location'] && $result['ownerid']) {
            global $LMS;

            $result['location'] = $LMS->getAddressForCustomerStuff($result['ownerid']);
        }

        if ($result['ownerid']) {
            $customer_manager = new LMSCustomerManager($this->db, $this->auth, $this->cache, $this->syslog);
            $result['owner'] = $customer_manager->getCustomerName($result['ownerid']);
        }

        return $result;
    }

    public function GetNetNodeName($id)
    {
        return $this->db->GetOne('SELECT name FROM netnodes WHERE id = ?', array($id));
    }

    public function GetNetNodes()
    {
        return $this->db->GetAllByKey('SELECT * FROM netnodes ORDER BY name', 'id');
    }

    /**
     * Returns customer network nodes.
     *
     * @param  int   $customer_id Customer id
     * @return array network nodes
     */
    public function GetCustomerNetNodes($id)
    {
        return $this->db->GetAllByKey('SELECT
				nd.id, nd.name, nd.type, lc.name as location_city, lc.id as location_city_id, ls.name as location_street,
				ls.id as location_street_id, va.location_house, va.location_flat,
				nd.longitude, nd.latitude, nd.invprojectid,
				nd.status, nd.ownerid, va.id as address_id, va.location
			FROM netnodes nd
			LEFT JOIN vaddresses va ON nd.address_id = va.id
			LEFT JOIN location_cities lc ON va.city_id = lc.id
			LEFT JOIN location_streets ls ON va.street_id = ls.id
			WHERE nd.ownerid = ?
			ORDER BY nd.name asc', 'id', array($id));
    }
}
