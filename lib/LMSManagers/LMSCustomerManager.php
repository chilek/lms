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
    
}
