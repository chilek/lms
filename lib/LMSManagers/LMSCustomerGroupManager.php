<?php

/*
 *  LMS version 1.11-git
 *
 *  Copyright (C) 2001-2016 LMS Developers
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
 * LMSCustomerGroupManager
 *
 * @author Maciej Lew <maciej.lew.1987@gmail.com>
 */
class LMSCustomerGroupManager extends LMSManager implements LMSCustomerGroupManagerInterface
{
    /**
     * Returns number of customer assignments to given group
     * 
     * @param int $id Group id
     * @return int Assignments number
     */
    public function CustomergroupWithCustomerGet($id)
    {
        return $this->db->GetOne(
            'SELECT COUNT(*) 
            FROM customerassignments
            WHERE customergroupid = ?', 
            array($id)
        );
    }
    
    /**
     * Adds customer group
     * 
     * @param array $customergroupdata Customer group data
     * @return boolean|int Customer group id on success, false on failure
     */
    public function CustomergroupAdd($customergroupdata)
    {
        if ($this->db->Execute('INSERT INTO customergroups (name, description) VALUES (?, ?)', array($customergroupdata['name'], $customergroupdata['description']))) {
            $id = $this->db->GetLastInsertID('customergroups');
            if ($this->syslog) {
                $args = array(
                    SYSLOG::RES_CUSTGROUP => $id,
                    'name' => $customergroupdata['name'],
                    'description' => $customergroupdata['description']
                );
                $this->syslog->AddMessage(SYSLOG::RES_CUSTGROUP, SYSLOG::OPER_ADD, $args);
            }
            return $id;
        } else {
            return FALSE;
        }
    }
    
    /**
     * Updates customer group
     * 
     * @param array $customergroupdata Customer group data
     * @return type
     */
    public function CustomergroupUpdate($customergroupdata)
    {
        $args = array(
            'name' => $customergroupdata['name'],
            'description' => $customergroupdata['description'],
            SYSLOG::RES_CUSTGROUP => $customergroupdata['id']
        );
        if ($this->syslog)
            $this->syslog->AddMessage(SYSLOG::RES_CUSTGROUP, SYSLOG::OPER_UPDATE, $args);
        return $this->db->Execute('UPDATE customergroups SET name=?, description=? 
				WHERE id=?', array_values($args));
    }

    /**
     * Deletes customer group
     * 
     * @param type $id
     * @return boolean
     */
    public function CustomergroupDelete($id)
    {
        if (!$this->CustomergroupWithCustomerGet($id)) {
            if ($this->syslog) {
                $custassigns = $this->db->GetAll('SELECT id, customerid, customergroupid FROM customerassignments
					WHERE customergroupid = ?', array($id));
                if (!empty($custassigns))
                    foreach ($custassigns as $custassign) {
                        $args = array(
                            SYSLOG::RES_CUSTASSIGN => $custassign['id'],
                            SYSLOG::RES_CUST => $custassign['customerid'],
                            SYSLOG::RES_CUSTGROUP => $custassign['customergroupid']
                        );
                        $this->syslog->AddMessage(SYSLOG::RES_CUSTASSIGN, SYSLOG::OPER_DELETE, $args);
                    }
                $this->syslog->AddMessage(SYSLOG::RES_CUSTGROUP, SYSLOG::OPER_DELETE, array(SYSLOG::RES_CUSTGROUP => $id));
            }
            $this->db->Execute('DELETE FROM customergroups WHERE id=?', array($id));
            return TRUE;
        } else
            return FALSE;
    }

    /**
     * Checks if customer group exists
     * 
     * @param int $id Customer group id
     * @return boolean True on success, false otherwise 
     */
    public function CustomergroupExists($id)
    {
        return ($this->db->GetOne('SELECT id FROM customergroups WHERE id=?', array($id)) ? TRUE : FALSE);
    }

    /**
     * Returns customer group id by it's name
     * 
     * @param string $name Customer group name
     * @return int Customer group id
     */
    public function CustomergroupGetId($name)
    {
        return $this->db->GetOne('SELECT id FROM customergroups WHERE name=?', array($name));
    }
    
    /**
     * Returns customer group name by it's id
     * 
     * @param int $id Customer group id
     * @return string Customer group name
     */
    public function CustomergroupGetName($id)
    {
        return $this->db->GetOne('SELECT name FROM customergroups WHERE id=?', array($id));
    }
    
    /**
     * Returns all customer groups
     * 
     * @return array Customer groups
     */
    public function CustomergroupGetAll()
    {
        return $this->db->GetAll(
            'SELECT g.id, g.name, g.description 
            FROM customergroups g
            WHERE NOT EXISTS (
                SELECT 1 
                FROM excludedgroups 
                WHERE userid = lms_current_user() AND customergroupid = g.id) 
                ORDER BY g.name ASC'
        );
    }
    
    /**
     * Returns customer group
     * 
     * @param int $id Customer group id
     * @param int $network Network id
     * @return array Customer group
     */
    public function CustomergroupGet($id, $network = NULL)
    {
        if ($network) {
            $network_manager = new LMSNetworkManager($this->db, $this->auth, $this->cache, $this->syslog);
            $net = $network_manager->GetNetworkParams($network);
        }

        $result = $this->db->GetRow('SELECT id, name, description 
			FROM customergroups WHERE id=?', array($id));

        $result['customers'] = $this->db->GetAll('SELECT c.id AS id,'
                . $this->db->Concat('c.lastname', "' '", 'c.name') . ' AS customername 
			FROM customerassignments, customers c '
                . ($network ? 'LEFT JOIN nodes ON c.id = nodes.ownerid ' : '')
                . 'WHERE c.id = customerid AND customergroupid = ? '
                . ($network ? 'AND ((ipaddr > ' . $net['address'] . ' AND ipaddr < ' . $net['broadcast'] . ') OR
			(ipaddr_pub > ' . $net['address'] . ' AND ipaddr_pub < ' . $net['broadcast'] . ')) ' : '')
                . ' GROUP BY c.id, c.lastname, c.name ORDER BY c.lastname, c.name', array($id));

        $result['customerscount'] = empty($result['customers']) ? 0 : count($result['customers']);
        $result['count'] = $network ? $this->CustomergroupWithCustomerGet($id) : $result['customerscount'];

        return $result;
    }
    
    /**
     * Returns customer groups
     * 
     * @return array Customer groups
     */
    public function CustomergroupGetList()
    {
        if ($customergrouplist = $this->db->GetAll('SELECT id, name, description,
				(SELECT COUNT(*)
					FROM customerassignments 
					WHERE customergroupid = g.id
				) AS customerscount
				FROM customergroups g
				WHERE NOT EXISTS (
					SELECT 1 FROM excludedgroups
					WHERE userid = lms_current_user() AND customergroupid = g.id
				)
				ORDER BY name ASC')) {
            $totalcount = 0;

            foreach ($customergrouplist as $idx => $row) {
                $totalcount += $row['customerscount'];
            }

            $customergrouplist['total'] = count($customergrouplist);
            $customergrouplist['totalcount'] = $totalcount;
        }

        return $customergrouplist;
    }
    
    /**
     * Returns customer groups assigned to customer
     * 
     * @param int $id Customer id
     * @return array Customer groups
     */
    public function CustomergroupGetForCustomer($id)
    {
        return $this->db->GetAll(
            'SELECT customergroups.id AS id, name, description 
            FROM customergroups, customerassignments 
            WHERE customergroups.id=customergroupid AND customerid=? 
            ORDER BY name ASC', 
            array($id)
        );
    }
    
    /**
     * Returns customer groups without customer
     * 
     * @param int $customerid Customer id
     * @return array Customer groups
     */
    public function GetGroupNamesWithoutCustomer($customerid)
    {
        return $this->db->GetAll(
            'SELECT customergroups.id AS id, name, customerid
            FROM customergroups 
            LEFT JOIN customerassignments ON (customergroups.id=customergroupid AND customerid = ?)
            GROUP BY customergroups.id, name, customerid 
            HAVING customerid IS NULL ORDER BY name', 
            array($customerid)
        );
    }
    
    /**
     * Returns customer groups assignments for customer
     * 
     * @param int $id Customer id
     * @return array Customer groups assignments
     */
    public function CustomerassignmentGetForCustomer($id)
    {
        return $this->db->GetAll(
            'SELECT customerassignments.id AS id, customergroupid, customerid 
            FROM customerassignments, customergroups 
            WHERE customerid=? AND customergroups.id = customergroupid 
            ORDER BY customergroupid ASC', 
            array($id)
        );
    }

    /**
     * Deletes customer assignment
     * 
     * @param array $customerassignmentdata Customer assignment data
     * @return type
     */
	public function CustomerassignmentDelete($customerassignmentdata) {
		if ($this->syslog) {
			if (isset($customerassignmentdata['customergroupid']))
				$assigns = $this->db->GetAll('SELECT id, customerid, customergroupid FROM customerassignments
					WHERE customergroupid = ? AND customerid = ?', array($customerassignmentdata['customergroupid'],
					$customerassignmentdata['customerid']));
			else
				$assigns = $this->db->GetAll('SELECT id, customerid, customergroupid FROM customerassignments
					WHERE customerid = ?', array($customerassignmentdata['customerid']));
			if (!empty($assigns))
				foreach ($assigns as $assign) {
					$args = array(
						SYSLOG::RES_CUSTASSIGN => $assign['id'],
						SYSLOG::RES_CUST => $assign['customerid'],
						SYSLOG::RES_CUSTGROUP => $assign['customergroupid']
					);
					$this->syslog->AddMessage(SYSLOG::RES_CUSTASSIGN, SYSLOG::OPER_DELETE, $args);
				}
		}

		if (isset($customerassignmentdata['customergroupid']))
			return $this->db->Execute('DELETE FROM customerassignments 
				WHERE customergroupid=? AND customerid=?', array($customerassignmentdata['customergroupid'],
					$customerassignmentdata['customerid']));
		else
			return $this->db->Execute('DELETE FROM customerassignments 
				WHERE customerid=?', array($customerassignmentdata['customerid']));
    }
    
    /**
     * Adds customer assignment
     * 
     * @param array  $customerassignmentdata Customer assignment data
     * @return type
     */
    public function CustomerassignmentAdd($customerassignmentdata)
    {
        $res = $this->db->Execute('INSERT INTO customerassignments (customergroupid, customerid) VALUES (?, ?)', array($customerassignmentdata['customergroupid'],
            $customerassignmentdata['customerid']));
        if ($this->syslog && $res) {
            $id = $this->db->GetLastInsertID('customerassignments');
            $args = array(
                SYSLOG::RES_CUSTASSIGN => $id,
                SYSLOG::RES_CUST => $customerassignmentdata['customerid'],
                SYSLOG::RES_CUSTGROUP => $customerassignmentdata['customergroupid']
            );
            $this->syslog->AddMessage(SYSLOG::RES_CUSTASSIGN, SYSLOG::OPER_ADD, $args);
        }
        return $res;
    }
    
    /**
     * Checks if customer assignment exists
     * 
     * @param int $groupid Customer group id
     * @param int $customerid Customer id
     * @return int|null 1 if exists, null otherwise
     */
    public function CustomerassignmentExist($groupid, $customerid)
    {
        return $this->db->GetOne('SELECT 1 FROM customerassignments WHERE customergroupid=? AND customerid=?', array($groupid, $customerid));
    }
    
    /**
     * Returns customers without groups
     * 
     * @param int $groupid Customer group id
     * @param int $network Network id
     * @return array Customers
     */
    public function GetCustomerWithoutGroupNames($groupid, $network = NULL)
    {
        if ($network) {
            $network_manager = new LMSNetworkManager($this->db, $this->auth, $this->cache, $this->syslog);
            $net = $network_manager->GetNetworkParams($network);
        }

        return $this->db->GetAll('SELECT c.id AS id, ' . $this->db->Concat('c.lastname', "' '", 'c.name') . ' AS customername
			FROM customerview c '
                        . ($network ? 'LEFT JOIN nodes ON (c.id = nodes.ownerid) ' : '')
                        . 'WHERE c.deleted = 0 AND c.id NOT IN (
				SELECT customerid FROM customerassignments WHERE customergroupid = ?) '
                        . ($network ? 'AND ((ipaddr > ' . $net['address'] . ' AND ipaddr < ' . $net['broadcast'] . ') OR (ipaddr_pub > '
                                . $net['address'] . ' AND ipaddr_pub < ' . $net['broadcast'] . ')) ' : '')
                        . 'GROUP BY c.id, c.lastname, c.name
			ORDER BY c.lastname, c.name', array($groupid));
    }

}
