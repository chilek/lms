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
 * LMSCustomerManager
 *
 * @author Maciej Lew <maciej.lew.1987@gmail.com>
 */
class LMSCustomerManager extends LMSManager
{
    /**
     * Returns customer name
     * 
     * @param int $id Customer id
     * @return string Customer name
     */
    public function getCustomerName($id)
    {
        return $this->db->GetOne(
            'SELECT ' . $this->db->Concat('lastname', "' '", 'name')
            . ' FROM customers WHERE id=?', 
            array($id)
        );
    }

    /**
     * Returns customer email
     * 
     * @param int $id Customer id
     * @return string Customer email
     */
    public function getCustomerEmail($id)
    {
        return $this->db->GetOne('SELECT email FROM customers WHERE id=?', array($id));
    }
    
    /**
     * Checks if customer exists
     * 
     * @param int $id Customer id
     * @return boolean|int True if customer exists, false id not, -1 if exists but is deleted
     */
    public function customerExists($id)
    {
        $customer_deleted = $this->db->GetOne('SELECT deleted FROM customersview WHERE id=?', array($id));
        switch ($customer_deleted) {
            case '0':
                return true;
                break;
            case '1':
                return -1;
                break;
            case '':
            default:
                return false;
                break;
        }
    }
    
    /**
     * Returns number of customer nodes
     * 
     * @param int $id Customer id
     * @return int Number of nodes
     */
    public function getCustomerNodesNo($id)
    {
        return $this->db->GetOne('SELECT COUNT(*) FROM nodes WHERE ownerid=?', array($id));
    }

    /**
     * Returns customer id by node IP
     * 
     * @param string $ipaddr Node IP
     * @return int Customer id
     */
    public function getCustomerIDByIP($ipaddr)
    {
        return $this->db->GetOne(
            'SELECT ownerid FROM nodes WHERE ipaddr=inet_aton(?) OR ipaddr_pub=inet_aton(?)',
            array($ipaddr, $ipaddr)
        );
    }
    
    /**
     * Returns customer status
     * 
     * @param int $id Customer id
     * @return int Status
     */
    public function getCustomerStatus($id)
    {
        return $this->db->GetOne(
            'SELECT status FROM customers WHERE id=?', 
            array($id)
        );
    }
    
    /**
     * Returns list of customers id and customers full name
     * 
     * @return array Customers data
     */
    public function getCustomerNames()
    {
        return $this->db->GetAllByKey(
            'SELECT id, ' . $this->db->Concat('lastname', "' '", 'name')  . ' AS customername 
            FROM customersview 
            WHERE status > 1 AND deleted = 0 
            ORDER BY lastname, name', 
            'id'
        );
    }
    
    /**
     * Returns list of customers id and customers full name
     * 
     * @return array Customers data
     */
    public function getAllCustomerNames()
    {
        return $this->db->GetAllByKey(
            'SELECT id, ' . $this->db->Concat('lastname', "' '", 'name') . ' AS customername 
            FROM customersview 
            WHERE deleted = 0
            ORDER BY lastname, name', 
            'id'
        );
    }
    
    /**
     * Checks if all customer nodes have access
     * 
     * @param int $id Customer id
     * @return boolean|int True if all have access, false if not, 2 if some have access and some not
     */
    public function getCustomerNodesAC($id)
    {
        $acl = $this->db->GetAll('SELECT access FROM nodes WHERE ownerid=?', array($id));
        if ($acl) {
            foreach ($acl as $value) {
                if ($value['access']) {
                    $y++;
                } else {
                    $n++;
                }

                if ($y && !$n) {
                    return true;
                }
                if ($n && !$y) {
                    return false;
                }
            }
        }
        if ($this->db->GetOne('SELECT COUNT(*) FROM nodes WHERE ownerid=?', array($id))) {
            return 2;
        } else {
            return false;
        }
    }
    
    /**
     * Returns customer balance
     * 
     * @param int $id Customer id
     * @param int $totime Timestamp
     * @return int Balance
     */
    public function getCustomerBalance($id, $totime = null)
    {
        return $this->db->GetOne(
            'SELECT SUM(value) 
            FROM cash 
            WHERE customerid = ?' . ($totime ? ' AND time < ' . intval($totime) : ''), 
            array($id)
        );
    }
    
    /**
     * Returns customer balance list
     * 
     * @param int $id Customer id
     * @param int $totime Timestamp
     * @param string $direction Order
     * @return array Balance list
     */
    public function getCustomerBalanceList($id, $totime = null, $direction = 'ASC')
    {
        ($direction == 'ASC' || $direction == 'asc') ? $direction == 'ASC' : $direction == 'DESC';

        $saldolist = array();

        $tslist = $this->db->GetAll(
            'SELECT cash.id AS id, time, cash.type AS type, 
                cash.value AS value, taxes.label AS tax, cash.customerid AS customerid, 
                comment, docid, users.name AS username,
                documents.type AS doctype, documents.closed AS closed
            FROM cash
            LEFT JOIN users ON users.id = cash.userid
            LEFT JOIN documents ON documents.id = docid
            LEFT JOIN taxes ON cash.taxid = taxes.id
            WHERE cash.customerid = ?'
            . ($totime ? ' AND time <= ' . intval($totime) : '')
            . ' ORDER BY time ' . $direction,
            array($id)
        );
        
        if ($tslist) {
            $saldolist['balance'] = 0;
            $saldolist['total'] = 0;
            $i = 0;

            foreach ($tslist as $row) {
                // old format wrapper
                foreach ($row as $column => $value)
                    $saldolist[$column][$i] = $value;

                $saldolist['after'][$i] = round($saldolist['balance'] + $row['value'], 2);
                $saldolist['balance'] += $row['value'];
                $saldolist['date'][$i] = date('Y/m/d H:i', $row['time']);

                $i++;
            }

            $saldolist['total'] = sizeof($tslist);
        }

        $saldolist['customerid'] = $id;
        return $saldolist;
    }
    
    
    /**
     * Returns customer statistics
     * 
     * @return array Statistics
     */
    public function customerStats()
    {
        $result = $this->db->GetRow(
            'SELECT COUNT(id) AS total,
                COUNT(CASE WHEN status = 3 THEN 1 END) AS connected,
                COUNT(CASE WHEN status = 2 THEN 1 END) AS awaiting,
                COUNT(CASE WHEN status = 1 THEN 1 END) AS interested
            FROM customersview 
            WHERE deleted=0'
        );

        $tmp = $this->db->GetRow(
            'SELECT SUM(a.value)*-1 AS debtvalue, COUNT(*) AS debt 
            FROM (
                SELECT SUM(value) AS value 
                FROM cash 
                LEFT JOIN customersview ON (customerid = customersview.id) 
                WHERE deleted = 0 
                GROUP BY customerid 
                HAVING SUM(value) < 0
            ) a'
        );

        if (is_array($tmp)) {
            $result = array_merge($result, $tmp);
        }

        return $result;
    }
    
}
