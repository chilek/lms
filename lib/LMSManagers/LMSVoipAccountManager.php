<?php

/*
 *  LMS version 1.11-git
 *
 *  Copyright (C) 2001-2017 LMS Developers
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

        [$order, $direction] = sscanf($order, '%[^,],%s');
        $direction = ($direction == 'desc') ? 'desc' : 'asc';

        switch ($order) {
            case 'login':
                $sqlord = ' ORDER BY v.login';
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

        if (!empty($search) && count($search)) {
            foreach ($search as $idx => $value) {
                if ($value != '') {
                    switch ($idx) {
                        case 'login':
                            $searchargs[] = 'v.login ?LIKE? ' . $this->db->Escape("%$value%");
                            break;

                        case 'password':
                            $searchargs[] = 'v.passwd ?LIKE? ' . $this->db->Escape("%$value%");
                            break;

                        case 'ownerid':
                            $searchargs[] = 'v.ownerid = ' . intval($value);
                            break;

                        case 'phone':
                            $searchargs[] = 'n.phone ?LIKE? ' . $this->db->Escape("%$value%");
                            break;

                        default:
                            $searchargs[] = $idx . ' ?LIKE? ' . $this->db->Escape("%$value%");
                    }
                }
            }
        }

        if (isset($searchargs)) {
            $searchargs = ' WHERE ' . implode(' ' . $sqlskey . ' ', $searchargs);
        }

        $voipaccountlist = $this->db->GetAll(
            'SELECT v.id, v.login, v.passwd, v.ownerid, '
                . $this->db->Concat('c.lastname', "' '", 'c.name')
                . ' AS owner, v.access, v.description,
				lb.name AS borough_name, ld.name AS district_name, lst.name AS state_name,
				lc.name AS city_name,
				(CASE WHEN ls.name2 IS NOT NULL THEN ' . $this->db->Concat('ls.name2', "' '", 'ls.name') . ' ELSE ls.name END) AS street_name,
				lt.name AS street_type,
				addr.name as location_name,
				addr.city as location_city_name, addr.street as location_street_name,
				addr.city_id as location_city, addr.street_id as location_street,
				addr.house as location_house, addr.flat as location_flat, addr.location
			FROM voipaccounts v '
                . (isset($search['phone']) ? 'JOIN voip_numbers n ON n.voip_account_id = v.id' : '')
                . ' JOIN customerview c ON (v.ownerid = c.id)
				LEFT JOIN vaddresses addr          ON addr.id = v.address_id
				LEFT JOIN location_cities lc       ON lc.id   = addr.city_id
				LEFT JOIN location_streets ls      ON ls.id   = addr.street_id
				LEFT JOIN location_street_types lt ON lt.id   = ls.typeid
				LEFT JOIN location_boroughs lb     ON lb.id   = lc.boroughid
				LEFT JOIN location_districts ld    ON ld.id   = lb.districtid
				LEFT JOIN location_states lst      ON lst.id  = ld.stateid '
                . ($searchargs ?? '')
                . ($sqlord != '' ? $sqlord . ' ' . $direction : '')
        );

        if ($voipaccountlist) {
            $customer_manager = new LMSCustomerManager($this->db, $this->auth, $this->cache, $this->syslog);

            $addresses = array();

            foreach ($voipaccountlist as $k => $v) {
                if (!$v['location'] && $v['ownerid']) {
                    if (!isset($addresses[$v['ownerid']])) {
                        $addresses[$v['ownerid']] = $customer_manager->getAddressForCustomerStuff($v['ownerid']);
                    }
                    $voipaccountlist[$k]['location'] = $addresses[$v['ownerid']];
                }
            }
        }

        $tmp_phone_list = $this->db->GetAll(
            'SELECT n.voip_account_id, n.phone
            FROM voip_numbers n'
            . (empty($search['ownerid']) ? '' : ' JOIN voipaccounts va ON va.id = n.voip_account_id
                WHERE va.ownerid = ' . intval($search['ownerid']))
        );
        $phone_list = array();
        if (!empty($tmp_phone_list)) {
            foreach ($tmp_phone_list as $k => $v) {
                if (isset($phone_list[$v['voip_account_id']])) {
                    $phone_list[$v['voip_account_id']][] = $v['phone'];
                } else {
                    $phone_list[$v['voip_account_id']] = array($v['phone']);
                }
            }
            unset($tmp_phone_list);
        }

        if (!empty($voipaccountlist)) {
            foreach ($voipaccountlist as &$voipaccount) {
                if (isset($phone_list[$voipaccount['id']])) {
                    $voipaccount['phone'] = $phone_list[$voipaccount['id']];
                }
            }
            unset($voipaccount);
        }

        $voipaccountlist['total'] = empty($voipaccountlist) ? 0 : count($voipaccountlist);
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
    public function voipAccountSet($id, $access = -1)
    {
        if ($this->syslog) {
            $ownerid = $this->db->GetOne('SELECT ownerid FROM voipaccounts WHERE id = ?', array($id));
        }

        if ($access != -1) {
            if ($access) {
                $voip_account_updated = $this->db->Execute(
                    'UPDATE voipaccounts SET access = 1
                    WHERE id = ? AND EXISTS (
                        SELECT 1
                        FROM customers
                        WHERE id = ownerid AND status = ?)',
                    array(
                        $id,
                        CSTATUS_CONNECTED,
                    )
                );

                if ($voip_account_updated && $this->syslog) {
                    $args = array(
                        SYSLOG::RES_VOIP_ACCOUNT => $id,
                        SYSLOG::RES_CUST => $ownerid,
                        'access' => 1,
                    );
                    $this->syslog->AddMessage(SYSLOG::RES_VOIP_ACCOUNT, SYSLOG::OPER_UPDATE, $args);
                }
            } else {
                $voip_account_updated = $this->db->Execute(
                    'UPDATE voipaccounts SET access = 0 WHERE id = ?',
                    array($id)
                );

                if ($voip_account_updated && $this->syslog) {
                    $args = array(
                        SYSLOG::RES_VOIP_ACCOUNT => $id,
                        SYSLOG::RES_CUST => $ownerid,
                        'access' => 0,
                    );
                    $this->syslog->AddMessage(SYSLOG::RES_VOIP_ACCOUNT, SYSLOG::OPER_UPDATE, $args);
                }
            }
            return $voip_account_updated;
        } else {
            $access = $this->db->GetOne(
                'SELECT access FROM voipaccounts WHERE id = ?',
                array($id)
            );

            if ($access == 1) {
                $voip_account_updated = $this->db->Execute(
                    'UPDATE voipaccounts SET access = 0 WHERE id = ?',
                    array($id)
                );

                if ($voip_account_updated && $this->syslog) {
                    $args = array(
                        SYSLOG::RES_VOIP_ACCOUNT => $id,
                        SYSLOG::RES_CUST => $ownerid,
                        'access' => 0,
                    );
                    $this->syslog->AddMessage(SYSLOG::RES_VOIP_ACCOUNT, SYSLOG::OPER_UPDATE, $args);
                }
            } else {
                $voip_account_updated = $this->db->Execute(
                    'UPDATE voipaccounts SET access = 1
                    WHERE id = ? AND EXISTS (
                        SELECT 1
                        FROM customers
                        WHERE id = ownerid AND status = ?
                    )',
                    array(
                        $id,
                        CSTATUS_CONNECTED,
                    )
                );

                if ($voip_account_updated && $this->syslog) {
                    $args = array(
                        SYSLOG::RES_VOIP_ACCOUNT => $id,
                        SYSLOG::RES_CUST => $ownerid,
                        'access' => 1,
                    );
                    $this->syslog->AddMessage(SYSLOG::RES_VOIP_ACCOUNT, SYSLOG::OPER_UPDATE, $args);
                }
            }
            return $voip_account_updated;
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
        if ($this->syslog) {
            $ownerid = $this->db->GetOne('SELECT ownerid FROM voipaccounts WHERE id = ?', array($id));
        }

        if ($access) {
            $status = $this->db->GetOne(
                'SELECT status FROM customers WHERE id = ?',
                array($id)
            );

            if ($status == CSTATUS_CONNECTED) {
                $voip_account_updated = $this->db->Execute(
                    'UPDATE voipaccounts SET access = 1 WHERE ownerid = ?',
                    array($id)
                );

                if ($voip_account_updated && $this->syslog) {
                    $args = array(
                        SYSLOG::RES_VOIP_ACCOUNT => $id,
                        SYSLOG::RES_CUST => $ownerid,
                        'access' => 1,
                    );
                    $this->syslog->AddMessage(SYSLOG::RES_VOIP_ACCOUNT, SYSLOG::OPER_UPDATE, $args);
                }

                return $voip_account_updated;
            }
        } else {
            $voip_account_updated = $this->db->Execute(
                'UPDATE voipaccounts SET access = 0 WHERE ownerid = ?',
                array($id)
            );

            if ($voip_account_updated && $this->syslog) {
                $args = array(
                    SYSLOG::RES_VOIP_ACCOUNT => $id,
                    SYSLOG::RES_CUST => $ownerid,
                    'access' => 0,
                );
                $this->syslog->AddMessage(SYSLOG::RES_VOIP_ACCOUNT, SYSLOG::OPER_UPDATE, $args);
            }

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
        $DB = $this->db;

        $DB->BeginTrans();

        // -1 is equal to no selected, then set null
        if (isset($voipaccountdata['address_id']) && $voipaccountdata['address_id'] < 0) {
            $voipaccountdata['address_id'] = null;
        }

        $args = array(
            SYSLOG::RES_CUST => $voipaccountdata['ownerid'],
            'login' => $voipaccountdata['login'],
            'passwd' => $voipaccountdata['passwd'],
            SYSLOG::RES_USER => Auth::GetCurrentUser(),
            'access' => $voipaccountdata['access'],
            'balance' => $voipaccountdata['balance'] ?? ConfigHelper::getConfig('voip.default_cost_limit', 200),
            'flags' => $voipaccountdata['flags'] ?? ConfigHelper::getConfig('voip.default_account_flags', 0),
            'cost_limit' => $voipaccountdata['cost_limit'] ?? null,
            SYSLOG::RES_ADDRESS => empty($voipaccountdata['address_id']) ? null : $voipaccountdata['address_id'],
            'description' => isset($voipaccountdata['description']) ? Utils::removeInsecureHtml($voipaccountdata['description']) : '',
            'extid' => isset($voipaccountdata['extid']) ? strval($voipaccountdata['extid']) : null,
            'serviceproviderid' => isset($voipaccountdata['serviceproviderid']) ? intval($voipaccountdata['serviceproviderid']) : null,
        );

        $voip_account_inserted = $DB->Execute(
            'INSERT INTO voipaccounts (ownerid, login, passwd, creatorid, creationdate, access,
            balance, flags, cost_limit, address_id, description, extid, serviceproviderid)
            VALUES (?, ?, ?, ?, ?NOW?, ?, ?, ?, ?, ?, ?, ?, ?)',
            array_values($args)
        );

        if ($voip_account_inserted) {
            $id = $DB->GetLastInsertID('voipaccounts');

            if ($this->syslog) {
                unset($args[SYSLOG::RES_USER]);
                $args[SYSLOG::RES_VOIP_ACCOUNT] = $id;
                $this->syslog->AddMessage(SYSLOG::RES_VOIP_ACCOUNT, SYSLOG::OPER_ADD, $args);
            }

            $phone_index = 0;
            $phones = array();

            if (!isset($voipaccountdata['numbers'])) {
                $voipaccountdata['numbers'] = array();
            }

            foreach ($voipaccountdata['numbers'] as $number) {
                $phones[] = '(' . $id . ', ' . $this->db->Escape($number['phone']) . ', ' . (++$phone_index) . ', ' . $this->db->Escape($number['info']) . ')';

                if ($this->syslog) {
                    $args = array(
                        SYSLOG::RES_VOIP_ACCOUNT => $id,
                        SYSLOG::RES_CUST => $voipaccountdata['ownerid'],
                        'phone' => $number['phone'],
                        'number_index' => $phone_index,
                        'info' => $number['info'],
                    );
                    $this->syslog->AddMessage(SYSLOG::RES_VOIP_ACCOUNT_NUMBER, SYSLOG::OPER_ADD, $args);
                }
            }

            if ($phones) {
                $DB->Execute('INSERT INTO voip_numbers (voip_account_id, phone, number_index, info) VALUES ' . implode(',', $phones));

                $DB->CommitTrans();

                return $id;
            } else {
                $DB->RollbackTrans();

                return false;
            }
        }

        $DB->RollbackTrans();

        return false;
    }

    /**
     * Checks if VoIP account exists
     *
     * @param int $id VoIP account id
     * @return boolean True if exists, false otherwise
     */
    public function voipAccountExists($id)
    {
        $voip_account = $this->db->GetOne(
            '
            SELECT v.id
            FROM voipaccounts v
            WHERE v.id = ? AND NOT EXISTS (
                SELECT 1
                FROM vcustomerassignments a
                JOIN excludedgroups e ON (a.customergroupid = e.customergroupid)
                WHERE e.userid = lms_current_user() AND a.customerid = v.ownerid
            )',
            array($id)
        );
        return (bool)$voip_account;
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
        $result = $this->db->GetRow(
            '
            SELECT v.id, ownerid, login, passwd, creationdate, moddate, creatorid,
                modid, access, balance, description, lb.name AS borough_name,
                ld.name AS district_name, lst.name AS state_name, lc.name AS city_name,
                (CASE WHEN ls.name2 IS NOT NULL THEN ' . $this->db->Concat('ls.name2', "' '", 'ls.name') . ' ELSE ls.name END) AS street_name,
                lt.name AS street_type, v.address_id, v.flags, v.balance,
                v.cost_limit, v.address_id, addr.name as location_name,
                addr.state AS location_state_name,
                addr.city as location_city_name,
                addr.street as location_street_name,
                addr.state_id AS location_state,
                addr.city_id as location_city,
                addr.street_id as location_street,
                addr.house as location_house, addr.flat as location_flat, addr.location
            FROM voipaccounts v
                LEFT JOIN vaddresses addr          ON addr.id = v.address_id
                LEFT JOIN location_cities lc       ON lc.id   = addr.city_id
                LEFT JOIN location_streets ls      ON ls.id   = addr.street_id
                LEFT JOIN location_street_types lt ON lt.id   = ls.typeid
                LEFT JOIN location_boroughs lb     ON lb.id   = lc.boroughid
                LEFT JOIN location_districts ld    ON ld.id   = lb.districtid
                LEFT JOIN location_states lst      ON lst.id  = ld.stateid
            WHERE v.id = ?',
            array($id)
        );

        if ($result) {
            $customer_manager        = new LMSCustomerManager($this->db, $this->auth, $this->cache, $this->syslog);
            $user_manager            = new LMSUserManager($this->db, $this->auth, $this->cache, $this->syslog);
            $result['createdby']     = $user_manager->getUserName($result['creatorid']);
            $result['modifiedby']    = $user_manager->getUserName($result['modid']);
            $result['creationdateh'] = date('Y/m/d, H:i', $result['creationdate']);
            $result['moddateh']      = date('Y/m/d, H:i', $result['moddate']);
            $result['phones'] = $result['numbers'] = $this->db->GetAll('SELECT * FROM voip_numbers WHERE voip_account_id = ?', array($id));
            $result['owner']         = $customer_manager->getCustomerName($result['ownerid']);
            return $result;
        }

        return false;
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
     * @param  string $phone Phone number
     * @return int    VoIP account id
     */
    public function getVoipAccountIDByPhone($phone)
    {
        return $this->db->GetOne('SELECT voip_account_id FROM voip_numbers WHERE phone = ?', array($phone));
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

        if ($this->syslog) {
            $account = $this->db->GetRow('SELECT * FROM voipaccounts WHERE id = ?', array($id));
            if ($account) {
                $args = array(
                    SYSLOG::RES_VOIP_ACCOUNT => $id,
                    SYSLOG::RES_CUST => $account['ownerid'],
                );
                $this->syslog->AddMessage(SYSLOG::RES_VOIP_ACCOUNT, SYSLOG::OPER_DELETE, $args);

                $numbers = $this->db->GetCol('SELECT id FROM voip_numbers WHERE voip_account_id = ?', array($id));
                if ($numbers) {
                    foreach ($numbers as $numberid) {
                        $args = array(
                            SYSLOG::RES_VOIP_ACCOUNT => $id,
                            SYSLOG::RES_VOIP_ACCOUNT_NUMBER => $numberid,
                            SYSLOG::RES_CUST => $account['ownerid'],
                        );
                        $this->syslog->AddMessage(SYSLOG::RES_VOIP_ACCOUNT_NUMBER, SYSLOG::OPER_DELETE, $args);
                    }
                }
            }
        }

        $this->db->Execute('DELETE FROM voip_numbers WHERE voip_account_id = ?', array($id));
        $this->db->Execute('DELETE FROM voipaccounts WHERE id = ?', array($id));


        $this->db->CommitTrans();
    }

    /**
     * Updates VoIP account data
     *
     * @param type $voipaccountdata New VoIP account data
     * @return boolean
     */
    public function voipAccountUpdate($data)
    {
        $this->db->BeginTrans();

        // -1 is equal to no selected, then set null
        if ($data['address_id'] < 0) {
            $data['address_id'] = null;
        }

        $args = array(
            'login' => $data['login'],
            'passwd' => $data['passwd'],
            'access' => $data['access'],
            SYSLOG::RES_USER => Auth::GetCurrentUser(),
            SYSLOG::RES_CUST => $data['ownerid'],
            'flags' => $data['flags']      ?: ConfigHelper::getConfig('voip.default_account_flags', 0),
            'balance' => $data['balance']    ?: 0,
            'cost_limit' => $data['cost_limit'] ?: null,
            SYSLOG::RES_ADDRESS => $data['address_id'] ?: null,
            'description' => isset($data['description']) ? Utils::removeInsecureHtml($data['description']) : '',
            SYSLOG::RES_VOIP_ACCOUNT => $data['id'],
        );

        $result = $this->db->Execute(
            'UPDATE voipaccounts
             SET login=?, passwd=?, moddate=?NOW?, access=?, modid=?,
                 ownerid=?, flags=?, balance=?, cost_limit=?, address_id=?,
                 description = ?
             WHERE id = ?',
            array_values($args)
        );

        if ($result) {
            if ($this->syslog) {
                unset($args[SYSLOG::RES_USER]);
                $this->syslog->AddMessage(SYSLOG::RES_VOIP_ACCOUNT, SYSLOG::OPER_UPDATE, $args);
            }

            $this->db->Execute('UPDATE voip_numbers SET number_index = null WHERE voip_account_id = ?', array($data['id']));

            $current_phones = $this->db->GetAllByKey('SELECT phone FROM voip_numbers WHERE voip_account_id = ?', 'phone', array($data['id']));
            $phone_index = 0;

            $numbers = $data['numbers'];

            foreach ($numbers as $v) {
                if (!isset($current_phones[$v['phone']])) {
                    $args = array(
                        SYSLOG::RES_VOIP_ACCOUNT => $data['id'],
                        'phone' => $v['phone'],
                        'number_index' => ++$phone_index,
                        'info' => $v['info'],
                    );
                    $result = $this->db->Execute(
                        'INSERT INTO voip_numbers (voip_account_id, phone, number_index, info) VALUES (?, ?, ?, ?)',
                        array_values($args)
                    );
                    if ($result && $this->syslog) {
                        $args[SYSLOG::RES_CUST] = $data['ownerid'];
                        $args[SYSLOG::RES_VOIP_ACCOUNT_NUMBER] = $this->db->GetLastInsertID('voip_numbers');
                        $this->syslog->AddMessage(SYSLOG::RES_VOIP_ACCOUNT_NUMBER, SYSLOG::OPER_ADD, $args);
                    }
                } else {
                    $args = array(
                        'number_index' => ++$phone_index,
                        'info' => $v['info'],
                        'phone' => $v['phone'],
                        SYSLOG::RES_VOIP_ACCOUNT => $data['id'],
                    );
                    $result = $this->db->Execute(
                        'UPDATE voip_numbers SET number_index = ?, info = ? WHERE phone = ? AND voip_account_id = ?',
                        array_values($args)
                    );
                    if ($result && $this->syslog) {
                        unset($args['info']);
                        $voip_number_id = $this->db->GetOne(
                            'SELECT id FROM voip_numbers WHERE number_index = ? AND phone = ? AND voip_account_id = ?',
                            array_values($args)
                        );

                        if ($voip_number_id) {
                            $args[SYSLOG::RES_CUST] = $data['ownerid'];
                            $args[SYSLOG::RES_VOIP_ACCOUNT_NUMBER] = $voip_number_id;
                            $this->syslog->AddMessage(SYSLOG::RES_VOIP_ACCOUNT_NUMBER, SYSLOG::OPER_UPDATE, $args);
                        }
                    }
                }
            }

            $numbers = Utils::array_column($data['numbers'], 'phone', 'phone');

            foreach ($current_phones as $v) {
                if (!isset($numbers[$v['phone']])) {
                    $voip_number_id = $this->db->GetOne(
                        'SELECT id FROM voip_numbers
                        WHERE voip_account_id = ? AND phone = ?',
                        array(
                            $data['id'],
                            $v['phone'],
                        )
                    );
                    if ($voip_number_id) {
                        if ($this->syslog) {
                            $args = array(
                                SYSLOG::RES_VOIP_ACCOUNT_NUMBER => $voip_number_id,
                                SYSLOG::RES_VOIP_ACCOUNT => $data['id'],
                                SYSLOG::RES_CUST => $data['ownerid'],
                            );
                            $this->syslog->AddMessage(SYSLOG::RES_VOIP_ACCOUNT_NUMBER, SYSLOG::OPER_DELETE, $args);
                        }

                        $this->db->Execute('DELETE FROM voip_numbers WHERE id = ?', array($voip_number_id));
                    }
                }
            }

            $this->db->CommitTrans();

            return true;
        }

        $this->db->RollbackTrans();

        return false;
    }

    /**
     * Returns all VoIP accounts for given customer id
     *
     * @param int $id Customer id
     * @param int $extid Customer extid
     * @param int $serviceproviderid Service provider id
     * @return array VoIP accounts data
     */
    public function getCustomerVoipAccounts($id, $extid = null, $serviceproviderid = null)
    {
        $extId = !empty($extid) ? strval($extid) : null;
        $result = $this->db->GetAll(
            'SELECT v.id, login, passwd, ownerid, access, flags, balance, cost_limit, extid, serviceproviderid,
                lb.name AS borough_name, ld.name AS district_name,
                lst.name AS state_name, lc.name AS city_name,
                (CASE WHEN ls.name2 IS NOT NULL THEN ' . $this->db->Concat('ls.name2', "' '", 'ls.name') . ' ELSE ls.name END) AS street_name,
                lt.name AS street_type, addr.name as location_name,
                addr.city as location_city_name, addr.street as location_street_name,
                addr.city_id as location_city, addr.street_id as location_street,
                addr.house as location_house, addr.flat as location_flat, addr.location
            FROM voipaccounts v
                LEFT JOIN vaddresses addr          ON addr.id = v.address_id
                LEFT JOIN location_cities lc       ON lc.id   = addr.city_id
                LEFT JOIN location_streets ls      ON ls.id   = addr.street_id
                LEFT JOIN location_street_types lt ON lt.id   = ls.typeid
                LEFT JOIN location_boroughs lb     ON lb.id   = lc.boroughid
                LEFT JOIN location_districts ld    ON ld.id   = lb.districtid
                LEFT JOIN location_states lst      ON lst.id  = ld.stateid
            WHERE ownerid = ?'
            . (empty($extid) ? '' : ' AND extid ?LIKE? ' . $this->db->Escape("%$extid%"))
            . (empty($serviceproviderid) ? '' : ' AND serviceproviderid = ' . intval($serviceproviderid))
            . ' ORDER BY login ASC',
            array($id)
        );

        if (!empty($result)) {
            foreach ($result as &$account) {
                $account['phones'] = $this->db->GetAll(
                    'SELECT * FROM voip_numbers WHERE voip_account_id = ? ORDER BY number_index',
                    array($account['id'])
                );
            }
            unset($account);
        }

        return $result;
    }

    /**
     * Returns VoIP billing list.
     *
     * @param  array $params Array with parameters
     * @return array $result Array with billings
     */
    public function getVoipBillings(array $params)
    {
        $count = $params['count'] ?? false;
        $stats = $params['stats'] ?? false;
        $offset = $params['offset'] ?? null;
        $limit = $params['limit'] ?? null;

        $order_string = '';
        if (isset($params['o'])) {
            $order = explode(',', $params['o']);
            if (empty($order[1]) || $order[1] != 'desc') {
                 $order[1] = 'asc';
            }

            switch ($order[0]) {
                case 'caller_name':
                case 'callee_name':
                case 'caller':
                case 'callee':
                case 'begintime':
                case 'totaltime':
                case 'billedtime':
                case 'status':
                case 'direction':
                case 'type':
                case 'price':
                    $order_string = ' ORDER BY ' . $order[0] . ' ' . $order[1];
                    break;
            }
        }

        // FILTERS
        $where = array();

        // VOIP ACCOUNT ID
        if (!empty($params['id'])) {
            if (is_array($params['id'])) {
                $tmp = '(' . implode(',', $params['id']) . ')';
                $where[] = '(cdr.callervoipaccountid in ' . $tmp . ' OR cdr.calleevoipaccountid in' . $tmp . ')';
                unset($tmp);
            } else {
                $where[] = '(cdr.callervoipaccountid = ' . $params['id'] . ' OR cdr.calleevoipaccountid = ' . $params['id'] . ')';
            }
        }

        // PHONE
        if (!empty($params['phone'])) {
            $where[] = "(cdr.caller like '" . $params['phone'] . "' OR cdr.callee like '" . $params['phone'] . "')";
        }

        // OWNERID
        if (!empty($params['fvownerid'])) {
            $where[] = "vacc.ownerid = " . $params['fvownerid'];
        }

        // CALL BILLING RANGE
        if (!empty($params['frangefrom'])) {
            [$year, $month, $day] = explode('/', $params['frangefrom']);
            $where[] = 'call_start_time >= ' . mktime(0, 0, 0, $month, $day, $year);
        }

        if (!empty($params['frangeto'])) {
            [$year, $month, $day] = explode('/', $params['frangeto']);
            $where[] = 'call_start_time <= ' . mktime(23, 59, 59, $month, $day, $year);
        }

        // billing record statuses
        if (!empty($params['fstatus'])) {
            switch ($params['fstatus']) {
                case BILLING_RECORD_STATUS_ANSWERED:
                case BILLING_RECORD_STATUS_NO_ANSWER:
                case BILLING_RECORD_STATUS_BUSY:
                case BILLING_RECORD_STATUS_SERVER_FAILED:
                case BILLING_RECORD_STATUS_UNKNOWN:
                    $where[] = 'cdr.status = ' . $params['fstatus'];
                    break;
            }
        }

        // billing record directions
        if (!empty($params['fdirection'])) {
            switch ($params['fdirection']) {
                case BILLING_RECORD_DIRECTION_OUTGOING:
                case BILLING_RECORD_DIRECTION_INCOMING:
                    $where[] = 'cdr.direction = ' . $params['fdirection'];
                    break;
            }
        }

        // billing record directions
        if (isset($params['ftype']) && is_numeric($params['ftype'])) {
            $where[] = 'cdr.type = ' . $params['ftype'];
        }

        // custom SQL conditions
        if (isset($params['custom_sql_conditions']) && is_array($params['custom_sql_conditions'])) {
            $where = array_merge($where, $params['custom_sql_conditions']);
        }

        $where_string = empty($where) ? '' : ' WHERE ' . implode(' AND ', $where);

        $DB = $this->db;

        if ($count) {
            return $DB->GetOne('SELECT COUNT(cdr.id)
                                  FROM
                                     voip_cdr cdr
                                     LEFT JOIN voipaccounts      vacc ON cdr.callervoipaccountid = vacc.id
                                     LEFT JOIN voipaccounts     vacc2 ON cdr.calleevoipaccountid = vacc2.id
                                     LEFT JOIN customers           c1 ON c1.id = vacc.ownerid
                                     LEFT JOIN customers           c2 ON c2.id = vacc2.ownerid
                                     LEFT JOIN customer_addresses ca1 ON ca1.customer_id = c1.id AND ca1.type = ' . BILLING_ADDRESS . '
                                     LEFT JOIN        addresses addr1 ON ca1.address_id = addr1.id
                                     LEFT JOIN customer_addresses ca2 ON ca2.customer_id = c2.id AND ca2.type = ' . BILLING_ADDRESS . '
                                     LEFT JOIN        addresses addr2 ON ca2.address_id = addr2.id
                                     ' . $where_string);
        }

        if ($stats) {
            return $DB->GetRow(
                'SELECT
                SUM(price) AS price,
                SUM(totaltime) AS totaltime,
                SUM(billedtime) AS billedtime,
                COUNT(*) AS cnt
                FROM
                voip_cdr cdr
                LEFT JOIN voipaccounts      vacc ON cdr.callervoipaccountid = vacc.id
                LEFT JOIN voipaccounts     vacc2 ON cdr.calleevoipaccountid = vacc2.id
                LEFT JOIN customers           c1 ON c1.id = vacc.ownerid
                LEFT JOIN customers           c2 ON c2.id = vacc2.ownerid
                LEFT JOIN customer_addresses ca1 ON ca1.customer_id = c1.id AND ca1.type = ' . BILLING_ADDRESS . '
                LEFT JOIN        addresses addr1 ON ca1.address_id = addr1.id
                LEFT JOIN customer_addresses ca2 ON ca2.customer_id = c2.id AND ca2.type = ' . BILLING_ADDRESS . '
                LEFT JOIN        addresses addr2 ON ca2.address_id = addr2.id'
                . $where_string
            );
        }

        $bill_list = $DB->GetAll('SELECT
                                     cdr.id, caller, callee, price, call_start_time as begintime, cdr.uniqueid,
                                     totaltime, billedtime,
                                     cdr.direction as direction, cdr.type AS type, callervoipaccountid, calleevoipaccountid,
                                     cdr.status as status, vacc.ownerid as callerownerid, vacc2.ownerid as calleeownerid,

                                     c1.name as caller_name, c1.lastname as caller_lastname, addr1.city as caller_city,
                                     addr1.street as caller_street, addr1.house as caller_building,
                                     c2.name as callee_name, c2.lastname as callee_lastname, addr2.city as callee_city,
                                     addr2.street as callee_street, addr2.house as callee_building, caller_flags, callee_flags
                                  FROM
                                     voip_cdr cdr
                                     LEFT JOIN voipaccounts      vacc ON cdr.callervoipaccountid = vacc.id
                                     LEFT JOIN voipaccounts     vacc2 ON cdr.calleevoipaccountid = vacc2.id
                                     LEFT JOIN customers           c1 ON c1.id = vacc.ownerid
                                     LEFT JOIN customers           c2 ON c2.id = vacc2.ownerid
                                     LEFT JOIN customer_addresses ca1 ON ca1.customer_id = c1.id AND ca1.type = ' . BILLING_ADDRESS . '
                                     LEFT JOIN        addresses addr1 ON ca1.address_id = addr1.id
                                     LEFT JOIN customer_addresses ca2 ON ca2.customer_id = c2.id AND ca2.type = ' . BILLING_ADDRESS . '
                                     LEFT JOIN        addresses addr2 ON ca2.address_id = addr2.id
                                     ' . $where_string . $order_string
            . (isset($limit) ? ' LIMIT ' . $limit : '')
            . (isset($offset) ? ' OFFSET ' . $offset : ''));

        return $bill_list;
    }

    /**
     * Returns voip tariffs.
     *
     * @return array Array with tariffs
     */
    public function getVoipTariffs()
    {
        return $this->db->GetAll('SELECT id, name, description FROM voip_tariffs');
    }

    /**
     * Returns voip tariff rule groups.
     *
     * @return array Array with tariffs
     */
    public function getVoipTariffRuleGroups()
    {
        return $this->db->GetAll('SELECT id, name, description FROM voip_rule_groups');
    }
}
