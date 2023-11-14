<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2022 LMS Developers
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

class MetroportMVNO
{
    private $syslog;
    private $db;
//    private $lms;
    private $db_errors;
//    private $dbtype;
    public $serviceProviderId;

    const SERVICE_PROVIDER_NAME = 'Metroport MVNO';

    public function __construct()
    {
        $this->db = LMSDB::getInstance();
        $this->syslog = SYSLOG::getInstance();
//        $this->lms = LMS::getInstance();
        $this->db_errors = &$this->db->GetErrors();
//        $this->dbtype = ConfigHelper::getConfig('database.type');
        $this->serviceProviderId = $this->getServiceProviderId(self::SERVICE_PROVIDER_NAME);
    }

    /**
     * Get service provider id
     *
     * @param string $serviceProviderName
     * @return int LMS seervice provider id
     */
    public function getServiceProviderId(string $serviceProviderName)
    {
        return $this->db->GetOne(
            'SELECT id
            FROM serviceproviders 
            WHERE name = ?',
            array(
                $serviceProviderName
            )
        );
    }

    /**
     * Get LMS customers for full synchronization
     *
     * @param int|null $customerid
     * @return array LMS customers for full synchronization
     */
    public function getCustomersForBind(int $customerid = null)
    {
        return $this->db->GetAllByKey(
            "SELECT c.id as id, c.ten, c.ssn, c.lastname, c.name, c.type, c.icn, ce.extid
            FROM customers c
            LEFT JOIN customerextids ce ON ce.customerid = c.id 
            WHERE ce.serviceproviderid IS NULL
            AND NOT EXISTS (SELECT 1 FROM customerextids ce1 WHERE ce1.customerid = ce.customerid AND ce1.serviceproviderid = ?)
            AND ((c.ssn IS NOT NULL AND c.ssn <> '') OR (c.ten IS NOT NULL AND c.ten <> ''))
            AND c.deleted = 0"
            . (!empty($customerid) ? " AND c.id = " . $customerid : "")
            . " ORDER BY id",
            "id",
            array(
                $this->serviceProviderId
            )
        );
    }

    /**
     * Get LMS customers bound with MMSC
     *
     * @param int|null $customerid
     * @return array LMS customers bound with MMSC
     * @throws
     */
    public function getBoundCustomers(int $customerid = null)
    {
        return $this->db->GetAllByKey(
            'SELECT c.id as id, c.ten, c.ssn, c.lastname, c.name, c.type, c.icn, ce.extid
            FROM customers c
            LEFT JOIN customerextids ce ON ce.customerid = c.id 
            WHERE ce.serviceproviderid = ?'
            . (!empty($customerid) ? ' AND c.id = ' . $customerid : '')
            . ' ORDER BY id',
            'id',
            array($this->serviceProviderId)
        );
    }

    /**
     * Get LMS customer extid for mmsc provider
     *
     * @param int $customerId
     * @return int LMS customer ext id for mmsc provider
     */
    public function getLMSCustomerExtid(int $customerId)
    {
        return $this->db->GetOne(
            'SELECT extid
            FROM customerextids
            WHERE customerid = ?
            AND serviceproviderid = ?',
            array(
                $customerId,
                $this->serviceProviderId
            )
        );
    }

    /**
     * set LMS customer Extid
     *
     * @param int $lmsCustomerId LMS customer ID
     * @param int $mmcsUserId MMSC user ID
     * @return int
     * @throws
     */
    public function setCustomerExtid(int $lmsCustomerId, int $mmcsUserId)
    {
        $extId = $this->getLMSCustomerExtid($lmsCustomerId);
        if (empty($extId)) {
            return $this->db->Execute(
                'INSERT INTO customerextids(customerid, extid, serviceproviderid)
                VALUES (?, ?, ?)',
                array(
                    $lmsCustomerId,
                    $mmcsUserId,
                    $this->serviceProviderId
                )
            );
        }
    }

    /**
     * Print customer synchronization message
     *
     * @param array $params
     * @return string
     * @throws
     */
    public function setCustomerExtidMessage(array $params)
    {
        $msg = '';
        if ($params['matching_result']) {
            $msg = 'LMS customer ' . $params['lms_customer_lsatname'] . $params['lms_customer_name'] . '(#' . $params['lms_customer_id'] . ') ' . trans('has been bound with MMSC user') . ' #' . $params['mmsc_user_id'];
        } /*else {
            $msg = 'LMS customer ' . $params['lms_customer_lsatname'] . $params['lms_customer_name'] . '(#' . $params['lms_customer_id'] . ') was not mached with MMSC user.';
        }*/

        return $msg;
    }

    /**
     * set LMS customer account extid
     *
     * @param int $accountId  account ID
     * @param string $extId  account ID
     * @return bool
     * @throws
     */
    public function setAccountExtid(int $accountId, string $extId)
    {
        return $this->db->Execute(
            'UPDATE voipaccounts SET extid = ?, serviceproviderid = ?
            WHERE id = ?',
            array(
                $extId,
                $this->serviceProviderId,
                $accountId,
            )
        );
    }

    /**
     * Add customer account
     *
     * @param array $accountData account data
     * @return int|false Id on success, flase on failure
     */
    public function accountAdd(array $accountData)
    {
        // -1 is equal to no selected, then set null
        if (isset($accountData['address_id']) && $accountData['address_id'] < 0) {
            $accountData['address_id'] = null;
        }

        $args = array(
            $this->syslog::RES_CUST => $accountData['ownerid'],
            'login' => $accountData['login'],
            'passwd' => $accountData['passwd'],
            $this->syslog::RES_USER => Auth::GetCurrentUser(),
            'access' => $accountData['access'],
            'balance' => $accountData['balance'] ?? ConfigHelper::getConfig('voip.default_cost_limit', 200),
            'flags' => $accountData['flags'] ?? ConfigHelper::getConfig('voip.default_account_flags', 0),
            'cost_limit' => $accountData['cost_limit'] ?? null,
            $this->syslog::RES_ADDRESS => empty($accountData['address_id']) ? null : $accountData['address_id'],
            'description' => isset($accountData['description']) ? Utils::removeInsecureHtml($accountData['description']) : '',
            'extid' => isset($accountData['extid']) ? strval($accountData['extid']) : null,
            'serviceproviderid' => isset($accountData['serviceproviderid']) ? intval($accountData['serviceproviderid']) : null,
        );

        $voip_account_inserted = $this->db->Execute(
            'INSERT INTO voipaccounts (ownerid, login, passwd, creatorid, creationdate, access,
            balance, flags, cost_limit, address_id, description, extid, serviceproviderid)
            VALUES (?, ?, ?, ?, ?NOW?, ?, ?, ?, ?, ?, ?, ?, ?)',
            array_values($args)
        );

        if ($voip_account_inserted) {
            $id = $this->db->GetLastInsertID('voipaccounts');

            if ($this->syslog) {
                unset($args[$this->syslog::RES_USER]);
                $args[$this->syslog::RES_VOIP_ACCOUNT] = $id;
                $this->syslog->AddMessage($this->syslog::RES_VOIP_ACCOUNT, $this->syslog::OPER_ADD, $args);
            }

            $phone_index = 0;
            $phones = array();

            if (!isset($accountData['numbers'])) {
                $accountData['numbers'] = array();
            }

            foreach ($accountData['numbers'] as $number) {
                $phones[] = '(' . $id . ', ' . $this->db->Escape($number['phone']) . ', ' . (++$phone_index) . ', ' . $this->db->Escape($number['info']) . ')';

                if ($this->syslog) {
                    $args = array(
                        $this->syslog::RES_VOIP_ACCOUNT => $id,
                        $this->syslog::RES_CUST => $accountData['ownerid'],
                        'phone' => $number['phone'],
                        'number_index' => $phone_index,
                        'info' => $number['info'],
                    );
                    $this->syslog->AddMessage($this->syslog::RES_VOIP_ACCOUNT_NUMBER, $this->syslog::OPER_ADD, $args);
                }
            }

            if ($phones) {
                $this->db->Execute('INSERT INTO voip_numbers (voip_account_id, phone, number_index, info) VALUES ' . implode(',', $phones));
                return $id;
            } else {
                return false;
            }
        }

        return false;
    }

    /**
     * Updates customer account data
     *
     * @param array $accountData account data
     * @return boolean
     */
    public function accountUpdate(array $accountData)
    {
        // -1 is equal to no selected, then set null
        if ($accountData['address_id'] < 0) {
            $accountData['address_id'] = null;
        }

        $args = array(
            'login' => $accountData['login'],
            'passwd' => $accountData['passwd'],
            'access' => $accountData['access'],
            $this->syslog::RES_USER => Auth::GetCurrentUser(),
            $this->syslog::RES_CUST => $accountData['ownerid'],
            'flags' => !empty($accountData['flags']) ? $accountData['flags'] : ConfigHelper::getConfig('voip.default_account_flags', 0),
            'balance' => !empty($accountData['balance']) ? $accountData['balance'] : 0,
            'cost_limit' => !empty($accountData['cost_limit']) ? $accountData['cost_limit'] : null,
            $this->syslog::RES_ADDRESS => !empty($accountData['address_id']) ? $accountData['address_id'] : null,
            'description' => isset($accountData['description']) ? Utils::removeInsecureHtml($accountData['description']) : '',
            $this->syslog::RES_VOIP_ACCOUNT => $accountData['id'],
        );

        $result = $this->db->Execute(
            'UPDATE voipaccounts SET login=?, passwd=?, moddate=?NOW?, access=?, modid=?,
                 ownerid=?, flags=?, balance=?, cost_limit=?, address_id=?, description = ?
             WHERE id = ?',
            array_values($args)
        );

        if ($result) {
            if ($this->syslog) {
                unset($args[$this->syslog::RES_USER]);
                $this->syslog->AddMessage($this->syslog::RES_VOIP_ACCOUNT, $this->syslog::OPER_UPDATE, $args);
            }

            $result = $this->db->Execute(
                'UPDATE voip_numbers SET phone = ? WHERE voip_account_id = ?',
                array(
                    $accountData['numbers'][0]['phone'],
                    $accountData['id']
                )
            );

            if ($result && $this->syslog) {
                $args[$this->syslog::RES_CUST] = $accountData['ownerid'];
                $args[$this->syslog::RES_VOIP_ACCOUNT_NUMBER] = $accountData['numbers'][0]['phone'];
                $this->syslog->AddMessage($this->syslog::RES_VOIP_ACCOUNT_NUMBER, $this->syslog::OPER_UPDATE, $args);
            }
        }

        return $result;
    }

    /**
     * Deletes account with given id
     *
     * @param int $id account id
     */
    public function accountDelete(int $id)
    {
        if ($this->syslog) {
            $account = $this->db->GetRow('SELECT * FROM voipaccounts WHERE id = ?', array($id));
            if ($account) {
                $args = array(
                    $this->syslog::RES_VOIP_ACCOUNT => $id,
                    $this->syslog::RES_CUST => $account['ownerid'],
                );
                $this->syslog->AddMessage($this->syslog::RES_VOIP_ACCOUNT, $this->syslog::OPER_DELETE, $args);

                $numbers = $this->db->GetCol('SELECT id FROM voip_numbers WHERE voip_account_id = ?', array($id));
                if ($numbers) {
                    foreach ($numbers as $numberid) {
                        $args = array(
                            $this->syslog::RES_VOIP_ACCOUNT => $id,
                            $this->syslog::RES_VOIP_ACCOUNT_NUMBER => $numberid,
                            $this->syslog::RES_CUST => $account['ownerid'],
                        );
                        $this->syslog->AddMessage($this->syslog::RES_VOIP_ACCOUNT_NUMBER, $this->syslog::OPER_DELETE, $args);
                    }
                }
            }
        }

        $this->db->Execute('DELETE FROM voipaccounts WHERE id = ?', array($id));
    }
}
