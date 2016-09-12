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

        if (count($search)) {
            foreach ($search as $idx => $value) {
                if ($value != '') {
                    switch ($idx) {
                        case 'login' :
                            $searchargs[] = 'v.login ?LIKE? '
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
            'SELECT v.id, v.login, v.passwd, v.ownerid, '
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

        $tmp_phone_list = $this->db->GetAll('SELECT voip_account_id, phone FROM voip_numbers;');
        $phone_list = array();
		if (!empty($tmp_phone_list)) {
			foreach ($tmp_phone_list as $k=>$v)
				if (isset($phone_list[$v['voip_account_id']]))
					$phone_list[$v['voip_account_id']][] = $v['phone'];
				else
					$phone_list[$v['voip_account_id']] = array($v['phone']);
			unset($tmp_phone_list);
		}

		if (!empty($voipaccountlist)) {
			foreach ($voipaccountlist as &$voipaccount)
				if (isset($phone_list[$v['id']]))
					$voipaccount['phone'] = $phone_list[$v['id']];
			unset($voipaccount);
		}

        $voipaccountlist['total'] = count($voipaccountlist);
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
    public function voipAccountAdd($voipaccountdata) {
        $DB = $this->db;
        $DB->BeginTrans();

        $voip_account_inserted = $DB->Execute(
            'INSERT INTO voipaccounts (ownerid, login, passwd, creatorid, creationdate, access,
            location, location_city, location_street, location_house, location_flat, balance, flags, cost_limit)
            VALUES (?, ?, ?, ?, ?NOW?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            array(
                $voipaccountdata['ownerid'],
                $voipaccountdata['login'],
                $voipaccountdata['passwd'],
                $this->auth->id,
                $voipaccountdata['access'],
                $voipaccountdata['location'],
                $voipaccountdata['location_city'] ? $voipaccountdata['location_city'] : null,
                $voipaccountdata['location_street'] ? $voipaccountdata['location_street'] : null,
                $voipaccountdata['location_house'] ? $voipaccountdata['location_house'] : null,
                $voipaccountdata['location_flat'] ? $voipaccountdata['location_flat'] : null,
                $voipaccountdata['balance'] ? $voipaccountdata['balance'] : ConfigHelper::getConfig('voip.default_cost_limit', 200),
                $voipaccountdata['flags'] ? $voipaccountdata['flags'] : ConfigHelper::getConfig('voip.default_account_flags', 0),
                $voipaccountdata['cost_limit'] ? $voipaccountdata['cost_limit'] : null
            )
        );

        if ($voip_account_inserted) {
            $id = $DB->GetLastInsertID('voipaccounts');
            $phones = array();

            foreach ($voipaccountdata['phone'] as $phone) {
                $phones[] = "($id, '$phone')";
            }

            if ($phones) {
                $DB->Execute('INSERT INTO voip_numbers (voip_account_id, phone) VALUES ' . implode(',', $phones));
                $DB->CommitTrans();
                return $id;
            } else {
                $DB->RollbackTrans();
                return FALSE;
            }
        }

        $DB->RollbackTrans();
        return FALSE;
    }

    /**
     * Checks if VoIP account exists
     *
     * @param int $id VoIP account id
     * @return boolean True if exists, false otherwise
     */
    public function voipAccountExists($id) {
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
    public function getVoipAccountOwner($id) {
        return $this->db->GetOne('SELECT ownerid FROM voipaccounts WHERE id=?', array($id));
    }

    /**
     * Returns VoIP account data
     *
     * @param int $id VoIP account id
     * @return array|false VoIP account data on success, false on failure
     */
    public function getVoipAccount($id) {
        $result = $this->db->GetRow('
            SELECT v.id, ownerid, login, passwd, creationdate, moddate, creatorid, modid, access, balance,
                location, location_city, location_street, location_house, location_flat,
                lb.name AS borough_name, ld.name AS district_name, ls.name AS state_name, v.flags, v.balance, v.cost_limit
            FROM voipaccounts v
                LEFT JOIN location_cities lc ON lc.id = v.location_city
                LEFT JOIN location_boroughs lb ON lb.id = lc.boroughid
                LEFT JOIN location_districts ld ON ld.id = lb.districtid
                LEFT JOIN location_states ls ON ls.id = ld.stateid
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
            $result['phones']        = $this->db->GetAll('SELECT phone FROM voip_numbers WHERE voip_account_id = ?;', array($id));
            $result['owner']         = $customer_manager->getCustomerName($result['ownerid']);
            return $result;
        }

        return FALSE;
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
    public function getVoipAccountIDByPhone($phone) {
        return $this->db->GetOne('SELECT voip_account_id FROM voip_numbers WHERE phone ?LIKE? ?', array((string)$phone));
    }

    /**
     * Returns VoIP account login for given id
     *
     * @param int $id VoIP account id
     * @return string VoIP account login
     */
    public function getVoipAccountLogin($id) {
        return $this->db->GetOne('SELECT login FROM voipaccounts WHERE id=?', array($id));
    }

    /**
     * Deletes VoIP account with given id
     *
     * @param int $id VoIP account id
     */
    public function deleteVoipAccount($id) {
        $this->db->BeginTrans();
        $this->db->Execute('DELETE FROM voipaccounts WHERE id = ?', array($id));
        $this->db->Execute('DELETE FROM voip_numbers WHERE voip_account_id = ?', array($id));
        $this->db->CommitTrans();
    }

    /**
     * Updates VoIP account data
     *
     * @param type $voipaccountdata New VoIP account data
     */
    public function voipAccountUpdate($data) {
        $this->db->BeginTrans();

        $result = $this->db->Execute(
            'UPDATE voipaccounts SET login=?, passwd=?, moddate=?NOW?, access=?, modid=?, ownerid=?, location=?,
                location_city=?, location_street=?, location_house=?, location_flat=?, flags=?, balance=?, cost_limit=?
              WHERE id=?',
             array(
                $data['login'],
                $data['passwd'],
                $data['access'],
                $this->auth->id,
                $data['ownerid'],
                $data['location'],
                $data['location_city']   ? $data['location_city']   : null,
                $data['location_street'] ? $data['location_street'] : null,
                $data['location_house']  ? $data['location_house']  : null,
                $data['location_flat']   ? $data['location_flat']   : null,
                $data['flags']           ? $data['flags']           : ConfigHelper::getConfig('voip.default_account_flags', 0),
                $data['balance']         ? $data['balance']         : 0,
                $data['cost_limit']      ? $data['cost_limit']      : null,
                $data['id']
             )
        );

        if ($result) {
            $current_phones = $this->db->GetAllByKey('SELECT phone FROM voip_numbers WHERE voip_account_id = ?;', 'phone', array($data['id']));

            $phone_to_delete = array();
            $phone_to_insert = array();

            foreach ($data['phone'] as $v) {
                if (!isset($current_phones[$v]))
                    $phone_to_insert[] = '('.$data['id'].",'$v')";
            }

            $data['phone'] = array_flip($data['phone']);
            foreach ($current_phones as $v) {
                if (!isset($data['phone'][$v['phone']]))
                    $phone_to_delete[] = " phone = '".$v['phone'] . "' ";
            }

            if ($phone_to_delete)
                $this->db->Execute('DELETE FROM voip_numbers WHERE ' . implode('OR', $phone_to_delete));

            if ($phone_to_insert)
                $this->db->Execute('INSERT INTO voip_numbers (voip_account_id, phone) VALUES ' . implode(',', $phone_to_insert));

            $this->db->CommitTrans();
            return TRUE;
        }

        $this->RollbackTrans();
        return FALSE;
    }

    /**
     * Returns all VoIP accounts for given customer id
     *
     * @param int $id Customer id
     * @return array VoIP accounts data
     */
    public function getCustomerVoipAccounts($id) {
        $result['accounts'] = $this->db->GetAll(
            'SELECT v.id, login, passwd, ownerid, access,
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
            $result['total'] = count($result['accounts']);
        }
        return $result;
    }

    /**
     * Returns VoIP billings.
     *
     * @param  array $p      Array with parameters
     * @return array $result Array with billings
     */
    public function getVoipBillings(array $params) {

        $order = explode(',', $params['o']);
        if (empty($order[1]) || $order[1] != 'desc')
             $order[1] = 'asc';

        switch ($order[0]) {
            case 'caller_name':
            case 'callee_name':
            case 'caller':
            case 'callee':
            case 'begintime':
            case 'callbegintime':
            case 'callanswertime':
            case 'status':
            case 'type':
            case 'price':
                $order_string = ' ORDER BY ' . $order[0] . ' ' . $order[1];
            break;

            default:
                $order_string = '';
        }

        // FILTERS
        $where = array();

        // VOIP ACCOUNT ID
        if (!empty($params['id'])) {
            if (is_array($params['id'])) {
                $tmp = '(' . implode(',', $params['id']) . ')';
                $where[] = '(cdr.callervoipaccountid in ' . $tmp . ' OR cdr.calleevoipaccountid in' . $tmp . ')';
                unset($tmp);
            } else
                $where[] = '(cdr.callervoipaccountid = ' . $params['id'] . ' OR cdr.calleevoipaccountid = ' . $params['id'] . ')';
        }

        // PHONE
        if (!empty($params['phone'])) {
            $where[] = "(cdr.caller like '" . $params['phone'] . "' OR cdr.callee like '" . $params['phone'] . "')";
        }

        // CALL BILLING RANGE
        if (!empty($params['frangefrom'])) {
            list($year,$month,$day) = explode('/', $params['frangefrom']);
            $where[] = 'call_start_time >= ' . mktime(0,0,0, $month, $day, $year);
        }

        if (!empty($params['frangeto'])) {
            list($year,$month,$day) = explode('/', $params['frangeto']);
            $where[] = 'call_start_time <= ' . mktime(23,59,59, $month, $day, $year);
        }

        // CALL STATUS
        if (!empty($params['fstatus']))
            switch ($params['fstatus']) {
                case CALL_ANSWERED:
                case CALL_NO_ANSWER:
                case CALL_BUSY:
                case CALL_SERVER_FAILED:
                    $where[] = "cdr.status = " . $params['fstatus'];
                break;
            }

        // CALL TYPE
        if (!empty($params['ftype']))
            switch ($params['ftype']) {
                case CALL_OUTGOING:
                case CALL_INCOMING:
                    $where[] = "cdr.type = " . $params['ftype'];
                break;
            }

        $where_string = ($where) ? ' WHERE ' . implode(' AND ', $where) : '';

        $DB = $this->db;
        $bill_list = $DB->GetAll('SELECT
                                     cdr.id, caller, callee, price, call_start_time as begintime, cdr.uniqueid,
                                     time_start_to_end as callbegintime, time_answer_to_end as callanswertime,
                                     cdr.type as type, callervoipaccountid, calleevoipaccountid,
                                     cdr.status as status, vacc.ownerid as callerownerid, vacc2.ownerid as calleeownerid,
                                     c1.name as caller_name, c1.lastname as caller_lastname, c1.city as caller_city,
                                     c1.street as caller_street, c1.building as caller_building,
                                     c2.name as callee_name, c2.lastname as callee_lastname, c2.city as callee_city,
                                     c2.street as callee_street, c2.building as callee_building, caller_flags, callee_flags
                                  FROM
                                     voip_cdr cdr
                                     LEFT JOIN voipaccounts  vacc ON cdr.callervoipaccountid = vacc.id
                                     LEFT JOIN voipaccounts vacc2 ON cdr.calleevoipaccountid = vacc2.id
                                     LEFT JOIN customers       c1 ON c1.id = vacc.ownerid
                                     LEFT JOIN customers       c2 ON c2.id = vacc2.ownerid' .
                                  $where_string . $order_string);

        return $bill_list;
    }

}
