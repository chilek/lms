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
 * LMSVoipAccountManager
 *
 * @author Maciej Lew <maciej.lew.1987@gmail.com>
 */
class LMSVoipAccountManager extends LMSManager implements LMSVoipAccountManagerInterface
{
    /**
     * Returns VoIP account list
     * 
     * @param string $order Order
     * @param array $search Search parameters
     * @param string $sqlskey Logical conjunction
     * @return array VoIP account list
     */
    public function getVoipAccountList($order = 'login,asc', $search = null, $sqlskey = 'AND')
    {
        if ($order == '') {
            $order = 'login,asc';
        }

        list($order, $direction) = sscanf($order, '%[^,],%s');

        ($direction == 'desc') ? $direction = 'desc' : $direction = 'asc';

        switch ($order) {
            case 'login':
                $sqlord = ' ORDER BY v.login';
                break;
            case 'passwd':
                $sqlord = ' ORDER BY v.passwd';
                break;
            case 'phone':
                $sqlord = ' ORDER BY v.phone';
                break;
            case 'id':
                $sqlord = ' ORDER BY v.id';
                break;
            case 'ownerid':
                $sqlord = ' ORDER BY v.ownerid';
                break;
            case 'owner':
                $sqlord = ' ORDER BY owner';
                break;
        }

        if (sizeof($search)) {
            foreach ($search as $idx => $value) {
                if ($value != '') {
                    switch ($idx) {
                        case 'login' :
                            $searchargs[] = 'v.login ?LIKE? ' 
                                . $this->db->Escape("%$value%");
                            break;
                        case 'phone' :
                            $searchargs[] = 'v.phone ?LIKE? ' 
                                . $this->db->Escape("%$value%");
                            break;
                        case 'password' :
                            $searchargs[] = 'v.passwd ?LIKE? ' 
                                . $this->db->Escape("%$value%");
                            break;
                        default :
                            $searchargs[] = $idx . ' ?LIKE? ' 
                                . $this->db->Escape("%$value%");
                    }
                }
            }
        }

        if (isset($searchargs)) {
            $searchargs = ' WHERE ' . implode(' ' . $sqlskey . ' ', $searchargs);
        }

        $voipaccountlist = $this->db->GetAll(
            'SELECT v.id, v.login, v.passwd, v.phone, v.ownerid, '
            . $this->db->Concat('c.lastname', "' '", 'c.name') 
            . ' AS owner, v.access,
		location, lb.name AS borough_name, ld.name AS district_name, ls.name AS state_name
		FROM voipaccounts v
		JOIN customerview c ON (v.ownerid = c.id)
		LEFT JOIN location_cities lc ON lc.id = v.location_city
		LEFT JOIN location_boroughs lb ON lb.id = lc.boroughid
		LEFT JOIN location_districts ld ON ld.id = lb.districtid
		LEFT JOIN location_states ls ON ls.id = ld.stateid '
            . (isset($searchargs) ? $searchargs : '')
            . ($sqlord != '' ? $sqlord . ' ' . $direction : '')
        );

        $voipaccountlist['total'] = sizeof($voipaccountlist);
        $voipaccountlist['order'] = $order;
        $voipaccountlist['direction'] = $direction;

        return $voipaccountlist;
    }

    /**
     * Activates/deactivates VoIP account
     * 
     * @param int $id VoIP account id
     * @param int $access Access
     * @return int|false Integer on success, false on failure
     */
    public function voipAccountSet($id, $access = -1) {
        if ($access != -1) {
            if ($access) {
                $voip_account_updated = $this->db->Execute(
                    'UPDATE voipaccounts SET access = 1 
                    WHERE id = ? AND EXISTS (
                        SELECT 1 
                        FROM customers 
                        WHERE id = ownerid AND status = 3)',
                    array($id)
                );
                return $voip_account_updated;
                
            } else {
                $voip_account_updated = $this->db->Execute(
                    'UPDATE voipaccounts SET access = 0 
                    WHERE id = ?', 
                    array($id)
                );
                return $voip_account_updated;
            }
        } else {
            $access = $this->db->GetOne(
                'SELECT access 
                FROM voipaccounts 
                WHERE id = ?', 
                array($id)
            );
            if ($access == 1) {
                $voip_account_updated = $this->db->Execute(
                    'UPDATE voipaccounts SET access=0 WHERE id = ?', 
                    array($id)
                );
                return $voip_account_updated;
            } else {
                $voip_account_updated = $this->db->Execute(
                    'UPDATE voipaccounts SET access = 1 
                    WHERE id = ? AND EXISTS (
                        SELECT 1 
                        FROM customers 
                        WHERE id = ownerid AND status = 3
                    )', 
                    array($id)
                );
                return $voip_account_updated;
            }
        }
    }

    /**
     * Activates/deactivates VoIP account
     * 
     * @param int $id VoIP account id
     * @param int $access Access
     * @return int|false Integer on success, false on failure
     */
    public function voipAccountSetU($id, $access = false)
    {
        if ($access) {
            $status = $this->db->GetOne(
                'SELECT status 
                FROM customers 
                WHERE id = ?',
                array($id)
            );
            if ($status == 3) {
                $voip_account_updated = $this->db->Execute(
                    'UPDATE voipaccounts SET access=1 WHERE ownerid=?', 
                    array($id)
                );
                return $voip_account_updated;
            }
        } else {
            $voip_account_updated = $this->db->Execute(
                'UPDATE voipaccounts SET access=0 WHERE ownerid=?', 
                array($id)
            );
            return $voip_account_updated;
        }
    }

    /**
     * Adds VoIP account
     * 
     * @param array $voipaccountdata VoIP account data
     * @return int|false Id on success, flase on failure
     */
    public function voipAccountAdd($voipaccountdata)
    {
        $voip_account_inserted = $this->db->Execute(
            'INSERT INTO voipaccounts (ownerid, login, passwd, phone, creatorid, creationdate, access,
		location, location_city, location_street, location_house, location_flat)
            VALUES (?, ?, ?, ?, ?, ?NOW?, ?, ?, ?, ?, ?, ?)',
            array(
                $voipaccountdata['ownerid'],
                $voipaccountdata['login'],
                $voipaccountdata['passwd'],
                $voipaccountdata['phone'],
                $this->auth->id,
                $voipaccountdata['access'],
		$voipaccountdata['location'],
		$voipaccountdata['location_city'] ? $voipaccountdata['location_city'] : null,
		$voipaccountdata['location_street'] ? $voipaccountdata['location_street'] : null,
		$voipaccountdata['location_house'] ? $voipaccountdata['location_house'] : null,
		$voipaccountdata['location_flat'] ? $voipaccountdata['location_flat'] : null,
            )
        );
        if ($voip_account_inserted) {
            $id = $this->db->GetLastInsertID('voipaccounts');
            return $id;
        } else {
            return false;
        }
    }
    
    /**
     * Checks if VoIP account exists
     * 
     * @param int $id VoIP account id
     * @return boolean True if exists, false otherwise
     */
    public function voipAccountExists($id)
    {
        $voip_account = $this->db->GetOne('
            SELECT v.id 
            FROM voipaccounts v
            WHERE v.id = ? AND NOT EXISTS (
                SELECT 1 
                FROM customerassignments a
                JOIN excludedgroups e ON (a.customergroupid = e.customergroupid)
                WHERE e.userid = lms_current_user() AND a.customerid = v.ownerid
            )',
            array($id)
        );
        return (($voip_account) ? true : false);
    }

    /**
     * Returns VoIP account owner cusomer id
     * 
     * @param int $id VoIP account id
     * @return int Owner id
     */
    public function getVoipAccountOwner($id)
    {
        return $this->db->GetOne('SELECT ownerid FROM voipaccounts WHERE id=?', array($id));
    }

    /**
     * Returns VoIP account data
     * 
     * @param int $id VoIP account id
     * @return array|false VoIP account data on success, false on failure
     */
    public function getVoipAccount($id)
    {
        $result = $this->db->GetRow('
            SELECT v.id, ownerid, login, passwd, phone, creationdate, moddate, creatorid, modid, access,
		location, location_city, location_street, location_house, location_flat,
		lb.name AS borough_name, ld.name AS district_name, ls.name AS state_name
		FROM voipaccounts v
		LEFT JOIN location_cities lc ON lc.id = v.location_city
		LEFT JOIN location_boroughs lb ON lb.id = lc.boroughid
		LEFT JOIN location_districts ld ON ld.id = lb.districtid
		LEFT JOIN location_states ls ON ls.id = ld.stateid
            WHERE v.id = ?',
            array($id)
        );
        if ($result) {
            $customer_manager = new LMSCustomerManager($this->db, $this->auth, $this->cache, $this->syslog);
            $user_manager = new LMSUserManager($this->db, $this->auth, $this->cache, $this->syslog);
            $result['createdby'] = $user_manager->getUserName($result['creatorid']);
            $result['modifiedby'] = $user_manager->getUserName($result['modid']);
            $result['creationdateh'] = date('Y/m/d, H:i', $result['creationdate']);
            $result['moddateh'] = date('Y/m/d, H:i', $result['moddate']);
            $result['owner'] = $customer_manager->getCustomerName($result['ownerid']);
            return $result;
        } else {
            return FALSE;
        }
    }

    /**
     * Returns VoIP account id for given login
     * 
     * @param string $login Login
     * @return int VoIP account id
     */
    public function getVoipAccountIDByLogin($login)
    {
        return $this->db->GetAll('SELECT id FROM voipaccounts WHERE login=?', array($login));
    }

    /**
     * Returns VoIP account id for given phone number
     * 
     * @param string $phone Phone number
     * @return int VoIP account id
     */
    public function getVoipAccountIDByPhone($phone)
    {
        return $this->db->GetOne('SELECT id FROM voipaccounts WHERE phone=?', array($phone));
    }

    /**
     * Returns VoIP account login for given id
     * 
     * @param int $id VoIP account id
     * @return string VoIP account login
     */
    public function getVoipAccountLogin($id)
    {
        return $this->db->GetOne('SELECT login FROM voipaccounts WHERE id=?', array($id));
    }

    /**
     * Deletes VoIP account with given id
     * 
     * @param int $id VoIP account id
     */
    public function deleteVoipAccount($id)
    {
        $this->db->BeginTrans();
        $this->db->Execute('DELETE FROM voipaccounts WHERE id = ?', array($id));
        $this->db->CommitTrans();
    }

    /**
     * Updates VoIP account data
     * 
     * @param type $voipaccountdata New VoIP account data
     */
    public function voipAccountUpdate($voipaccountdata)
    {
        $this->db->Execute(
            'UPDATE voipaccounts SET login=?, passwd=?, phone=?, moddate=?NOW?, access=?, modid=?, ownerid=?,
		location=?, location_city=?, location_street=?, location_house=?, location_flat=? WHERE id=?', 
            array(
                $voipaccountdata['login'],
                $voipaccountdata['passwd'],
                $voipaccountdata['phone'],
                $voipaccountdata['access'],
                $this->auth->id,
                $voipaccountdata['ownerid'],
		$voipaccountdata['location'],
		$voipaccountdata['location_city'] ? $voipaccountdata['location_city'] : null,
		$voipaccountdata['location_street'] ? $voipaccountdata['location_street'] : null,
		$voipaccountdata['location_house'] ? $voipaccountdata['location_house'] : null,
		$voipaccountdata['location_flat'] ? $voipaccountdata['location_flat'] : null,
                $voipaccountdata['id']
            )
        );
    }

    /**
     * Returns all VoIP accounts for given customer id
     * 
     * @param int $id Customer id
     * @return array VoIP accounts data
     */
    public function getCustomerVoipAccounts($id)
    {
        $result['accounts'] = $this->db->GetAll(
            'SELECT v.id, login, passwd, phone, ownerid, access,
		location, location_city, location_street, location_house, location_flat,
		lb.name AS borough_name, ld.name AS district_name, ls.name AS state_name
		FROM voipaccounts v
		LEFT JOIN location_cities lc ON lc.id = v.location_city
		LEFT JOIN location_boroughs lb ON lb.id = lc.boroughid
		LEFT JOIN location_districts ld ON ld.id = lb.districtid
		LEFT JOIN location_states ls ON ls.id = ld.stateid
            WHERE ownerid=? 
            ORDER BY login ASC', array($id)
        );
        if ($result['accounts']) {
            $result['total'] = sizeof($result['accounts']);
        }
        return $result;
    }

}
