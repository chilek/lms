<?php

/*
 *  LMS version 1.11-git
 *
 *  Copyright (C) 2001-2019 LMS Developers
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
 */
class LMSCustomerManager extends LMSManager implements LMSCustomerManagerInterface
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
     * @return array Customer email
     */
    public function getCustomerEmail($id)
    {
        return $this->db->GetCol('SELECT contact FROM customercontacts
               WHERE customerid = ? AND (type & ? = ?)', array($id, CONTACT_EMAIL, CONTACT_EMAIL));
    }

    /**
     * Checks if customer exists
     *
     * @param int $id Customer id
     * @return boolean|int True if customer exists, false id not, -1 if exists but is deleted
     */
    public function customerExists($id)
    {
        $customer_deleted = $this->db->GetOne('SELECT deleted FROM customers WHERE id=?', array($id));
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
        return $this->db->GetOne('SELECT COUNT(*) FROM vnodes WHERE ownerid=?', array($id));
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
            'SELECT ownerid FROM vnodes WHERE ipaddr=inet_aton(?) OR ipaddr_pub=inet_aton(?)',
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
            FROM customerview
            WHERE status <> ? AND deleted = 0
            ORDER BY lastname, name',
            'id',
            array(CSTATUS_INTERESTED)
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
            FROM customerview
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
        $y = 0;
        $n = 0;

        $acl = $this->db->GetAll('SELECT access FROM vnodes WHERE ownerid=?', array($id));
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
        if ($this->db->GetOne('SELECT COUNT(*) FROM vnodes WHERE ownerid=?', array($id))) {
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
     * @param boolean $expired take only expired liabilities into account
     * @return int Balance
     */
    public function getCustomerBalance($id, $totime = null, $expired = false)
    {
        if ($expired) {
            $deadline = ConfigHelper::getConfig('payments.deadline', ConfigHelper::getConfig('invoices.paytime', 0));
            if (empty($totime)) {
                $totime = time();
            }
            return $this->db->GetOne(
                'SELECT SUM(value) FROM cash
				LEFT JOIN documents d ON d.id = cash.docid
				LEFT JOIN customers c ON c.id = cash.customerid
				LEFT JOIN divisions ON divisions.id = c.divisionid
				WHERE c.id = ? AND ((cash.docid IS NULL AND ((cash.type <> 0 AND cash.time < ' . $totime . ')
					OR (cash.type = 0 AND cash.time +
						(CASE c.paytime WHEN -1
							THEN
								(CASE WHEN divisions.inv_paytime IS NULL
									THEN ' . $deadline . '
									ELSE divisions.inv_paytime
								END)
							ELSE c.paytime
						END) * 86400 < ' . $totime . ')))
						OR (cash.docid IS NOT NULL AND ((d.type IN (?, ?) AND cash.time < ' . $totime . '
							OR (d.type IN (?, ?) AND d.cdate + (d.paytime + 0) * 86400 < ' . $totime . ')))))',
                array($id, DOC_RECEIPT, DOC_CNOTE, DOC_INVOICE, DOC_DNOTE)
            );
        } else {
            return $this->db->GetOne(
                'SELECT SUM(value)
				FROM cash
				WHERE customerid = ?' . ($totime ? ' AND time < ' . intval($totime) : ''),
                array($id)
            );
        }
    }

    /**
     * Returns customer balance list
     *
     * @param int $id Customer id
     * @param int $totime Timestamp
     * @param string $direction Order
     * @return array Balance list
     */
    public function getCustomerBalanceList($id, $totime = null, $direction = 'ASC', $aggregate_documents = false)
    {
        ($direction == 'ASC' || $direction == 'asc') ? $direction == 'ASC' : $direction == 'DESC';

        $result = array();

        $result['list'] = $this->db->GetAll(
            '(SELECT cash.id AS id, time, cash.type AS type,
                cash.value AS value, taxes.label AS tax, cash.customerid AS customerid,
                cash.comment, docid, vusers.name AS username,
                documents.type AS doctype, documents.closed AS closed,
                documents.published, documents.archived, cash.importid,
                (CASE WHEN d2.id IS NULL THEN 0 ELSE 1 END) AS referenced,
                documents.cdate, documents.number, numberplans.template
            FROM cash
            LEFT JOIN vusers ON vusers.id = cash.userid
            LEFT JOIN documents ON documents.id = docid
            LEFT JOIN numberplans ON numberplans.id = documents.numberplanid
            LEFT JOIN documents d2 ON d2.reference = documents.id
            LEFT JOIN taxes ON cash.taxid = taxes.id
            WHERE cash.customerid = ?'
            . ($totime ? ' AND time <= ' . intval($totime) : '') . ')
            UNION
            (SELECT ic.itemid AS id, d.cdate AS time, 0 AS type,
            		(-ic.value * ic.count) AS value, NULL AS tax, d.customerid,
            		ic.description AS comment, d.id AS docid, vusers.name AS username,
            		d.type AS doctype, d.closed AS closed,
            		d.published, 0 AS archived, NULL AS importid,
            		0 AS referenced,
            		d.cdate, d.number, numberplans.template
            	FROM documents d
            	JOIN invoicecontents ic ON ic.docid = d.id
            	JOIN numberplans ON numberplans.id = d.numberplanid
            	LEFT JOIN vusers ON vusers.id = d.userid
            	WHERE ' . (ConfigHelper::checkConfig('phpui.proforma_invoice_generates_commitment') ? '1=0 AND' : '')
                . ' d.customerid = ? AND d.type = ?'
                . ($totime ? ' AND d.cdate <= ' . intval($totime) : '') . ')
            ORDER BY time ' . $direction . ', id',
            array($id, $id, DOC_INVOICE_PRO)
        );

        $result['customerid'] = $id;

        if (!empty($result['list'])) {
            $result['balance'] = 0;
            $result['total'] = 0;

            if ($aggregate_documents) {
                $finance_manager = new LMSFinanceManager($this->db, $this->auth, $this->cache, $this->syslog);
                $result = $finance_manager->AggregateDocuments($result);
            }

            foreach ($result['list'] as $idx => &$row) {
                $row['customlinks'] = array();
                if ($row['doctype'] == DOC_INVOICE_PRO && !ConfigHelper::checkConfig('phpui.proforma_invoice_generates_commitment')) {
                    $row['after'] = $result['balance'];
                } else {
                    $row['after'] = round($result['balance'] + $row['value'], 2);
                    $result['balance'] += $row['value'];
                }
                $row['date'] = date('Y/m/d H:i', $row['time']);
            }

            $result['total'] = count($result['list']);
        }

        $result['sendinvoices'] = ($this->db->GetOne('SELECT 1 FROM customercontacts cc
			JOIN customers c ON c.id = cc.customerid 
			WHERE c.id = ? AND invoicenotice = 1 AND cc.type & ? = ?
			LIMIT 1', array($id, CONTACT_INVOICES | CONTACT_DISABLED, CONTACT_INVOICES)) > 0);

        return $result;
    }

    public function GetCustomerShortBalanceList($customerid, $limit = 10, $order = 'DESC')
    {
        $result = $this->db->GetAll('SELECT comment, value, time FROM cash
				WHERE customerid = ?
				ORDER BY time ' . $order . '
				LIMIT ?', array($customerid, $limit));

        if (empty($result)) {
            return null;
        }

        $balance = $this->getCustomerBalance($customerid);

        if ($order == 'ASC') {
            $result = array_reverse($result);
        }

        foreach ($result as &$record) {
            $record['after'] = $balance;
            $balance -= $record['value'];
        }
        unset($record);

        if ($order == 'ASC') {
            $result = array_reverse($result);
        }

        return $result;
    }

    /**
     * Returns customer statistics
     *
     * @return array Statistics
     */
    public function customerStats()
    {
        global $CSTATUSES;
        $sql = '';
        foreach ($CSTATUSES as $statusidx => $status) {
            $sql .= ' COUNT(CASE WHEN status = ' . $statusidx . ' THEN 1 END) AS ' . $status['alias'] . ',';
        }
        $result = $this->db->GetRow(
            'SELECT ' . $sql . ' COUNT(id) AS total
            FROM customerview
            WHERE deleted=0'
        );

        $tmp = $this->db->GetRow(
            'SELECT SUM(a.value)*-1 AS debtvalue, COUNT(*) AS debt
            FROM (
                SELECT SUM(value) AS value
                FROM cash
                LEFT JOIN customerview ON (customerid = customerview.id)
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

    /**
     * Adds customer
     *
     * @param array $customeradd Customer data
     * @return boolean False on failure, customer id on success
     */
    public function customerAdd($customeradd)
    {
        $location_manager = new LMSLocationManager($this->db, $this->auth, $this->cache, $this->syslog);

        $capitalize_customer_names = ConfigHelper::checkValue(ConfigHelper::getConfig('phpui.capitalize_customer_names', true));

        $args = array(
            'extid'          => $customeradd['extid'],
            'name'           => $customeradd['name'],
            'lastname'       => $customeradd['lastname'],
            'type'           => empty($customeradd['type']) ? 0 : 1,
            'ten'            => $customeradd['ten'],
            'ssn'            => $customeradd['ssn'],
            'status'         => $customeradd['status'],
            SYSLOG::RES_USER => Auth::GetCurrentUser(),
            'info'           => $customeradd['info'],
            'notes'          => $customeradd['notes'],
            'message'        => $customeradd['message'],
            'pin'            => $customeradd['pin'],
            'regon'          => $customeradd['regon'],
            'rbename'        => $customeradd['rbename'],
            'rbe'            => $customeradd['rbe'],
            'icn'            => $customeradd['icn'],
            'cutoffstop'     => $customeradd['cutoffstop'],
            'consentdate'    => $customeradd['consentdate'],
            'einvoice'       => $customeradd['einvoice'],
            SYSLOG::RES_DIV  => empty($customeradd['divisionid']) ? null : $customeradd['divisionid'],
            'paytime'        => $customeradd['paytime'],
            'paytype'        => !empty($customeradd['paytype']) ? $customeradd['paytype'] : null,
            'invoicenotice'  => $customeradd['invoicenotice'],
            'mailingnotice'  => $customeradd['mailingnotice'],
        );

        if ($this->db->Execute('INSERT INTO customers (extid, name, lastname, type,
                        ten, ssn, status, creationdate,
                        creatorid, info, notes, message, pin, regon, rbename, rbe,
                        icn, cutoffstop, consentdate, einvoice, divisionid, paytime, paytype,
                        invoicenotice, mailingnotice)
                    VALUES (?, ?, ' . ($capitalize_customer_names ? 'UPPER(?)' : '?') . ', ?, ?, ?, ?, ?NOW?,
                        ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', array_values($args))
        ) {
            $id = $this->db->GetLastInsertID('customers');

            // INSERT ADDRESSES
            foreach ($customeradd['addresses'] as $v) {
                $location_manager->InsertCustomerAddress($id, $v);

                // update country states
                if ($v['location_zip'] && $v['location_state']) {
                    $location_manager->UpdateCountryState($v['location_zip'], $v['location_state']);
                }
            }

            if ($this->syslog) {
                $args[SYSLOG::RES_CUST] = $id;
                unset($args[SYSLOG::RES_USER]);
                $this->syslog->AddMessage(SYSLOG::RES_CUST, SYSLOG::OPER_ADD, $args);
            }

            if (ConfigHelper::checkValue(ConfigHelper::getConfig('phpui.add_customer_group_required', false))) {
                $gargs = array(
                        'customerid' => $id,
                        'customergroupid' => $customeradd['group']
                );
                $res = $this->db->Execute('INSERT INTO customerassignments (customerid, customergroupid) VALUES (?,?)', array_values($gargs));
                if ($this->syslog && $res) {
                }
                    $args = array(
                        SYSLOG::RES_CUST => $id,
                        SYSLOG::RES_CUSTGROUP => $customeradd['group']
                    );
                    $this->syslog->AddMessage(SYSLOG::RES_CUSTASSIGN, SYSLOG::OPER_ADD, $args);
            }
            return $id;
        } else {
            return false;
        }
    }

    /**
     * Returns customer list
     *
     * @param string $order Order
     * @param int $state State
     * @param boolean $network With or without network params
     * @param int $customergroup Customer group
     * @param array $search Search parameters
     * @param int $time Timestamp
     * @param string $sqlskey Logical conjunction
     * @param int $nodegroup Node group
     * @param int $division Division id
     * @param int $limit Limit
     * @param int $offset Offset
     * @param boolean $count Count flag
     * @return array Customer list
     */
    public function getCustomerList($params = array())
    {
        extract($params);

        if (isset($order) && is_null($order)) {
            $order = 'customername,asc';
        }
        if (isset($sqlskey) && is_null($sqlskey)) {
            $sqlskey = 'AND';
        }
        if (isset($count) && is_null($count)) {
            $count = false;
        }

        list($order, $direction) = sscanf($order, '%[^,],%s');

        ($direction != 'desc') ? $direction = 'asc' : $direction = 'desc';

        switch ($order) {
            case 'id':
                $sqlord = ' ORDER BY c.id';
                break;
            case 'address':
                $sqlord = ' ORDER BY address';
                break;
            case 'balance':
                $sqlord = ' ORDER BY balance';
                break;
            case 'tariff':
                $sqlord = ' ORDER BY tariffvalue';
                break;
            case 'extid':
                $sqlord = ' ORDER BY extid';
                break;
            default:
                $sqlord = ' ORDER BY customername';
                break;
        }

        switch ($state) {
            case 50:
                // When customer is deleted we have no assigned groups or nodes, see DeleteCustomer().
                // Return empty list in this case
                if (!empty($network) || !empty($customergroup) || !empty($nodegroup)) {
                    $customerlist['total'] = 0;
                    $customerlist['state'] = 0;
                    $customerlist['order'] = $order;
                    $customerlist['direction'] = $direction;
                    return $customerlist;
                }
                $deleted = 1;
                break;
            case 51:
                $disabled = 1;
                break;
            case 52:
                $indebted = 1;
                break;
            case 53:
                $online = 1;
                break;
            case 54:
                $groupless = 1;
                break;
            case 55:
                $tariffless = 1;
                break;
            case 56:
                $suspended = 1;
                break;
            case 57:
                $indebted2 = 1;
                break;
            case 58:
                $indebted3 = 1;
                break;
            case 59:
            case 60:
            case 61:
                    $contracts = $state - 58;
                    $contracts_days = intval(ConfigHelper::getConfig('contracts.contracts_days'));
                    $contracts_expiration_type = ConfigHelper::getConfig('contracts.expiration_type', 'documents');
                break;
            case 62:
                    $einvoice =1;
                break;
            case 63:
                    $withactivenodes = 1;
                break;
            case 64:
                    $withnodes = 1;
                break;
            case 65:
                    $withoutnodes = 1;
                break;
            case 66:
                    $withoutinvoiceflag =1;
                break;
            case 67:
                    $withoutbuildingnumber =1;
                break;
            case 68:
                    $withoutzip =1;
                break;
            case 69:
                    $withoutcity = 1;
                break;
            case 70:
                $withoutteryt = 1;
                break;
        }

        if (isset($assignments)) {
            $as = $assignments;
        } elseif (isset($search['assignments'])) {
            $as = $search['assignments'];
            unset($search['assignments']);
        } else {
            $as = null;
        }

        switch ($as) {
            case 7:
            case 14:
            case 30:
                    $assignment = 'SELECT DISTINCT(a.customerid) FROM assignments a WHERE '
                    .'a.suspended = 0 AND a.commited = 1 AND a.dateto > '.time(). ' AND a.dateto <= '. (time() + ($as*86400))
                    .' AND NOT EXISTS (SELECT 1 FROM assignments aa WHERE aa.customerid = a.customerid AND aa.datefrom > a.dateto LIMIT 1)';
                break;
            case -1:
                $assignment = 'SELECT DISTINCT(a.customerid) FROM assignments a WHERE a.suspended = 0 AND a.commited = 1 AND a.dateto = 0';
                break;
            case -2:
                $assignment = 'SELECT DISTINCT(a.customerid) FROM assignments a WHERE a.suspended = 0 AND a.commited = 1 '
                .'AND (a.dateto = 0 OR a.dateto > ?NOW?) AND ((a.at + 86400) > ?NOW? or a.period != 0)';
                break;
            case -3:
                $assignment = 'SELECT DISTINCT(a.customerid) FROM assignments a WHERE a.invoice = 1 AND a.suspended = 0 AND a.commited = 1 '
                .'AND (a.dateto = 0 OR a.dateto > ?NOW?) AND ((a.at + 86400) > ?NOW? or a.period != 0)';
                break;
            case -4:
                $assignment = 'SELECT DISTINCT(a.customerid) FROM assignments a WHERE a.suspended != 0';
                break;
            default:
                $assignment = null;
                break;
        }

        if ($network) {
            $network_manager = new LMSNetworkManager($this->db, $this->auth, $this->cache, $this->syslog);
            $net = $network_manager->getNetworkParams($network);
        }

        $over = 0;
        $below = 0;

        if (isset($search['withenddate'])) {
            $withenddate = intval($search['withenddate']);
            unset($search['withenddate']);
        } else {
            $withenddate = -1;
        }

        if (count($search)) {
            foreach ($search as $key => $value) {
                if ($value != '') {
                    switch ($key) {
                        case 'phone':
                            $searchargs[] = 'EXISTS (SELECT 1 FROM customercontacts
					WHERE customerid = c.id AND (customercontacts.type & ' . (CONTACT_MOBILE | CONTACT_LANDLINE)
                            . ') > 0 AND REPLACE(contact, \'-\', \'\') ?LIKE? ' . $this->db->Escape("%$value%") . ')';
                            break;
                        case 'email':
                            $searchargs[] = 'EXISTS (SELECT 1 FROM customercontacts
					WHERE customerid = c.id AND customercontacts.type & ' . CONTACT_EMAIL .' = '. CONTACT_EMAIL
                            . ' AND contact ?LIKE? ' . $this->db->Escape("%$value%") . ')';
                            break;
                        case 'zip':
                        case 'city':
                        case 'address':
                            // UPPER here is a workaround for postgresql ILIKE bug
                            if (!isset($search['type']) || !strlen($search['addresstype'])) {
                                $searchargs[] = "(UPPER(c.$key) ?LIKE? UPPER(" . $this->db->Escape("%$value%") . ")
									OR UPPER(post_$key) ?LIKE? UPPER(" . $this->db->Escape("%$value%") . ")
									OR EXISTS (SELECT 1 FROM customer_addresses ca2
										JOIN vaddresses va ON va.id = ca2.address_id
										WHERE ca2.customer_id = c.id
											AND UPPER(va.$key) ?LIKE? UPPER(" . $this->db->Escape("%$value%") . ")))";
                            } elseif ($search['addresstype'] == BILLING_ADDRESS) {
                                $searchargs[] = "UPPER(c.$key) ?LIKE? UPPER(" . $this->db->Escape("%$value%") . ")";
                            } elseif ($search['addresstype'] == LOCATION_ADDRESS) {
                                $searchargs[] = "EXISTS (SELECT 1 FROM customer_addresses ca2
									JOIN vaddresses va ON va.id = ca2.address_id
									WHERE ca2.customer_id = c.id AND ca2.type IN (" . DEFAULT_LOCATION_ADDRESS . ',' . LOCATION_ADDRESS . ")
										AND UPPER(va.$key) ?LIKE? UPPER(" . $this->db->Escape("%$value%") . "))";
                            } else {
                                $searchargs[] = "UPPER(post_$key) ?LIKE? UPPER(" . $this->db->Escape("%$value%") . ")";
                            }
                            break;
                        case 'customername':
                            // UPPER here is a workaround for postgresql ILIKE bug
                            $searchargs[] = $this->db->Concat('UPPER(c.lastname)', "' '", 'UPPER(c.name)') . ' ?LIKE? UPPER(' . $this->db->Escape("%$value%") . ')';
                            break;
                        case 'createdfrom':
                            if ($search['createdto']) {
                                $searchargs['createdfrom'] = '(creationdate >= ' . intval($value)
                                    . ' AND creationdate <= ' . intval($search['createdto']) . ')';
                                unset($search['createdto']);
                            } else {
                                $searchargs[] = 'creationdate >= ' . intval($value);
                            }
                            break;
                        case 'createdto':
                            if (!isset($searchargs['createdfrom'])) {
                                $searchargs[] = 'creationdate <= ' . intval($value);
                            }
                            break;
                        case 'deletedfrom':
                            if ($search['deletedto']) {
                                $searchargs['deletedfrom'] = '(moddate >= ' . intval($value)
                                    . ' AND moddate <= ' . intval($search['deletedto']) . ')';
                                unset($search['deletedto']);
                            } else {
                                $searchargs[] = 'moddate >= ' . intval($value);
                            }
                            $deleted = 1;
                            break;
                        case 'deletedto':
                            if (!isset($searchargs['deletedfrom'])) {
                                $searchargs[] = 'moddate <= ' . intval($value);
                            }
                            $deleted = 1;
                            break;
                        case 'type':
                            $searchargs[] = 'type = ' . intval($value);
                            break;
                        case 'linktype':
                            $searchargs[] = 'EXISTS (SELECT 1 FROM vnodes
								WHERE ownerid = c.id AND linktype = ' . intval($value) . ')';
                            break;
                        case 'linktechnology':
                            $searchargs[] = 'EXISTS (SELECT 1 FROM vnodes
								WHERE ownerid = c.id AND linktechnology = ' . intval($value) . ')';
                            break;
                        case 'linkspeed':
                            $searchargs[] = 'EXISTS (SELECT 1 FROM vnodes
								WHERE ownerid = c.id AND linkspeed = ' . intval($value) . ')';
                            break;
                        case 'doctype':
                            $val = explode(':', $value); // <doctype>:<fromdate>:<todate>
                            $searchargs[] = 'EXISTS (SELECT 1 FROM documents
								WHERE customerid = c.id'
                                . (!empty($val[0]) ? ' AND type = ' . intval($val[0]) : '')
                                . (!empty($val[1]) ? ' AND cdate >= ' . intval($val[1]) : '')
                                . (!empty($val[2]) ? ' AND cdate <= ' . intval($val[2]) : '')
                                . ')';
                            break;
                        case 'stateid':
                            $searchargs[] = 'EXISTS (SELECT 1 FROM zipcodes z
								WHERE z.zip = c.zip AND z.stateid = ' . intval($value) . ')';
                            break;
                        case 'tariffs':
                            $searchargs[] = 'EXISTS (SELECT 1 FROM assignments a
							WHERE a.customerid = c.id
							AND a.datefrom <= ?NOW?
							' . ($withenddate == 1 ? 'AND a.dateto > ?NOW?' :
                            ($withenddate == 0 ? 'AND a.dateto = 0' :
                                'AND (a.dateto > ?NOW? OR a.dateto = 0)')) . '
							AND (tariffid IN (' . $value . ')))';
                            break;
                        case 'addresstype':
                            break;
                        case 'tarifftype':
                            $searchargs[] = 'EXISTS (SELECT 1 FROM assignments a
							JOIN tariffs t ON t.id = a.tariffid
							WHERE a.customerid = c.id
							AND a.datefrom <= ?NOW?
							AND (a.dateto >= ?NOW? OR a.dateto = 0)
							AND (t.type = ' . intval($value) . '))';
                            break;
                        case 'balance':
                        case 'balance_relation':
                            if ($key == 'balance' && isset($search['balance_relation'])) {
                                $balance_relation = intval($search['balance_relation']);
                                $searchargs[] = 'b.value' . ($balance_relation == -1 ? '<=' : '>=') . ' ' . floatval($value);
                            }
                            break;
                        default:
                            $searchargs[] = "$key ?LIKE? " . $this->db->Escape("%$value%");
                    }
                }
            }
        }

        if (isset($searchargs)) {
            $sqlsarg = implode(' ' . $sqlskey . ' ', $searchargs);
        }

        $suspension_percentage = f_round(ConfigHelper::getConfig('finances.suspension_percentage'));

        $sql = '';

        if ($count) {
            $sql .= 'SELECT COUNT(DISTINCT c.id) AS total,
            	SUM(CASE WHEN b.value > 0 THEN b.value ELSE 0 END) AS balanceover,
            	SUM(CASE WHEN b.value < 0 THEN b.value ELSE 0 END) AS balancebelow ';
        } else {
            $sql .= 'SELECT DISTINCT c.id AS id, c.lastname, c.name, ' . $this->db->Concat('UPPER(lastname)', "' '", 'c.name') . ' AS customername,
            	c.type,
                status, full_address, post_full_address, c.address, c.zip, c.city, countryid, countries.name AS country, cc.email, ccp.phone, ten, ssn, c.info AS info,
                extid, message, c.divisionid, c.paytime AS paytime, COALESCE(b.value, 0) AS balance,
                COALESCE(t.value, 0) AS tariffvalue, s.account, s.warncount, s.online,
                (CASE WHEN s.account = s.acsum THEN 1
                    WHEN s.acsum > 0 THEN 2 ELSE 0 END) AS nodeac,
                (CASE WHEN s.warncount = s.warnsum THEN 1
                    WHEN s.warnsum > 0 THEN 2 ELSE 0 END) AS nodewarn ';
        }

        $sql .= 'FROM customerview c
            LEFT JOIN (SELECT customerid, (' . $this->db->GroupConcat('contact') . ') AS email
            FROM customercontacts WHERE (type & ' . CONTACT_EMAIL .' > 0) GROUP BY customerid) cc ON cc.customerid = c.id
            LEFT JOIN (SELECT customerid, (' . $this->db->GroupConcat('contact') . ') AS phone
            FROM customercontacts WHERE (type & ' . (CONTACT_MOBILE | CONTACT_LANDLINE) .' > 0) GROUP BY customerid) ccp ON ccp.customerid = c.id
            LEFT JOIN countries ON (c.countryid = countries.id) '
            . (!empty($customergroup) ? 'LEFT JOIN (SELECT customerassignments.customerid, COUNT(*) AS gcount
            	FROM customerassignments '
                    . (is_array($customergroup) || $customergroup > 0 ? ' WHERE customergroupid IN ('
                        . (is_array($customergroup) ? implode(',', Utils::filterIntegers($customergroup)) : intval($customergroup)) . ')' : '') . '
            		GROUP BY customerassignments.customerid) ca ON ca.customerid = c.id ' : '')
            . 'LEFT JOIN (SELECT SUM(value) AS value, customerid FROM cash'
            . ($time ? ' WHERE time < ' . $time : '') . '
                GROUP BY customerid
            ) b ON (b.customerid = c.id)
            LEFT JOIN (SELECT a.customerid,
                SUM((CASE a.suspended
                WHEN 0 THEN (((100 - a.pdiscount) * (CASE WHEN t.value IS null THEN l.value ELSE t.value END) / 100) - a.vdiscount)
                ELSE ((((100 - a.pdiscount) * (CASE WHEN t.value IS null THEN l.value ELSE t.value END) / 100) - a.vdiscount) * ' . $suspension_percentage . ' / 100) END)
                * (CASE t.period
                WHEN ' . MONTHLY . ' THEN 1
                WHEN ' . YEARLY . ' THEN 1/12.0
                WHEN ' . HALFYEARLY . ' THEN 1/6.0
                WHEN ' . QUARTERLY . ' THEN 1/3.0
                ELSE (CASE a.period
                    WHEN ' . MONTHLY . ' THEN 1
                    WHEN ' . YEARLY . ' THEN 1/12.0
                    WHEN ' . HALFYEARLY . ' THEN 1/6.0
                    WHEN ' . QUARTERLY . ' THEN 1/3.0
                    ELSE 0 END)
                END)
                ) AS value
                    FROM assignments a
                    LEFT JOIN tariffs t ON (t.id = a.tariffid)
                    LEFT JOIN liabilities l ON (l.id = a.liabilityid AND a.period != ' . DISPOSABLE . ')
                    WHERE a.commited = 1 AND a.datefrom <= ?NOW? AND (a.dateto > ?NOW? OR a.dateto = 0)
                    GROUP BY a.customerid
                ) t ON (t.customerid = c.id)
                LEFT JOIN (SELECT ownerid,
                    SUM(access) AS acsum, COUNT(access) AS account,
                    SUM(warning) AS warnsum, COUNT(warning) AS warncount,
                    (CASE WHEN MAX(lastonline) > ?NOW? - ' . intval(ConfigHelper::getConfig('phpui.lastonline_limit')) . '
                        THEN 1 ELSE 0 END) AS online
                    FROM nodes
                    WHERE ownerid > 0 AND ipaddr <> 0
                    GROUP BY ownerid
                ) s ON (s.ownerid = c.id) '
                . ($contracts == 1 ?
                    ($contracts_expiration_type == 'documents' ?
                        'LEFT JOIN (
							SELECT COUNT(*), d.customerid FROM documents d
							JOIN documentcontents dc ON dc.docid = d.id
							WHERE d.type IN (' . DOC_CONTRACT . ',' . DOC_ANNEX . ')
							GROUP BY d.customerid
						) d ON d.customerid = c.id' :
                        'LEFT JOIN (
							SELECT customerid
							FROM assignments
							WHERE dateto > 0
							GROUP BY customerid
							HAVING MAX(dateto) < ?NOW?
						) ass ON ass.customerid = c.id') : '')
                . ($contracts == 2 ?
                    ($contracts_expiration_type == 'documents' ?
                        'JOIN (
							SELECT SUM(CASE WHEN dc.todate < ?NOW? THEN 1 ELSE 0 END),
								SUM(CASE WHEN dc.todate > ?NOW? THEN 1 ELSE 0 END),
								d.customerid FROM documents d
							JOIN documentcontents dc ON dc.docid = d.id
							WHERE d.type IN (' . DOC_CONTRACT . ',' . DOC_ANNEX . ')
							GROUP BY d.customerid
							HAVING SUM(CASE WHEN dc.todate < ?NOW? THEN 1 ELSE 0 END) > 0
								AND SUM(CASE WHEN dc.todate >= ?NOW? THEN 1 ELSE 0 END) = 0
						) d ON d.customerid = c.id' :
                        'JOIN (
							SELECT customerid
							FROM assignments
							WHERE dateto > 0
							GROUP BY customerid
							HAVING MAX(dateto) < ?NOW?
						) ass ON ass.customerid = c.id') : '')
                . ($contracts == 3 ?
                    ($contracts_expiration_type == 'documents' ?
                        'JOIN (
							SELECT DISTINCT d.customerid FROM documents d
							JOIN documentcontents dc ON dc.docid = d.id
							WHERE dc.todate >= ?NOW? AND dc.todate <= ?NOW? + 86400 * ' . $contracts_days . '
								AND type IN (' . DOC_CONTRACT . ',' . DOC_ANNEX . ')
						) d ON d.customerid = c.id' :
                        'JOIN (
							SELECT customerid
							FROM assignments
							WHERE dateto > 0
							GROUP BY customerid
							HAVING MAX(dateto) >= ?NOW? AND MAX(dateto) <= ?NOW? + 86400 * ' . $contracts_days . '
						) ass ON ass.customerid = c.id') : '')
                . ' WHERE c.deleted = ' . intval($deleted)
                . (($state < 50 && $state > 0) ? ' AND c.status = ' . intval($state) : '')
                . ($division ? ' AND c.divisionid = ' . intval($division) : '')
                . ($online ? ' AND s.online = 1' : '')
                . ($indebted ? ' AND b.value < 0' : '')
                . ($indebted2 ? ' AND b.value < -t.value' : '')
                . ($indebted3 ? ' AND b.value < -t.value * 2' : '')
                . ($einvoice ? ' AND c.einvoice = 1' : '')
                . ($withactivenodes ? ' AND EXISTS (SELECT 1 FROM nodes WHERE ownerid = c.id AND access = 1)' : '')
                . ($withnodes ? ' AND EXISTS (SELECT 1 FROM nodes WHERE ownerid = c.id)' : '')
                . ($withoutnodes ? ' AND NOT EXISTS (SELECT 1 FROM nodes WHERE ownerid = c.id)' : '')
                . ($withoutinvoiceflag ? ' AND c.id IN (SELECT DISTINCT customerid FROM assignments WHERE invoice = 0 AND commited = 1)' : '')
                . ($withoutbuildingnumber ? ' AND c.building IS NULL' : '')
                . ($withoutzip ? ' AND c.zip IS NULL' : '')
                . ($withoutcity ? ' AND c.city IS NULL' : '')
                . ($withoutteryt ? ' AND c.id IN (SELECT DISTINCT ca.customer_id
					FROM customer_addresses ca
					JOIN addresses a ON a.id = ca.address_id
					WHERE a.city_id IS NULL)' : '')
                . ($contracts == 1 ? ($contracts_expiration_type == 'documents' ?
                        ' AND d.customerid IS NULL' :
                        ' AND ass.customerid IS NULL') : '')
                . ($assignment ? ' AND c.id IN ('.$assignment.')' : '')
                . ($disabled ? ' AND s.ownerid IS NOT null AND s.account > s.acsum' : '')
                . ($network ? ' AND (EXISTS (SELECT 1 FROM vnodes WHERE ownerid = c.id
                		AND (netid' . (is_array($network) ? ' IN (' . implode(',', $network) . ')' : ' = ' . $network) . '
                		OR (ipaddr_pub > ' . $net['address'] . ' AND ipaddr_pub < ' . $net['broadcast'] . ')))
                	OR EXISTS (SELECT 1 FROM netdevices
                		JOIN vnodes ON vnodes.netdev = netdevices.id AND vnodes.ownerid IS NULL
                		WHERE netdevices.ownerid = c.id AND (netid'
                            . (is_array($network) ? ' IN (' . implode(',', $network) . ')' : ' = ' . $network) . '
                		OR (ipaddr_pub > ' . $net['address'] . ' AND ipaddr_pub < ' . $net['broadcast'] . '))))' : '')
                . (!empty($customergroup) && $customergroup != -1 ? ' AND ca.gcount = ' . (is_array($customergroup) ? count($customergroup) : 1) : '')
                . ($customergroup == -1 ? ' AND ca.gcount IS NULL ' : '')
                . ($nodegroup ? ' AND EXISTS (SELECT 1 FROM nodegroupassignments na
                    JOIN vnodes n ON (n.id = na.nodeid)
                    WHERE n.ownerid = c.id AND na.nodegroupid = ' . intval($nodegroup) . ')' : '')
                . ($groupless ? ' AND NOT EXISTS (SELECT 1 FROM customerassignments a
                    WHERE c.id = a.customerid)' : '')
                . ($tariffless ? ' AND NOT EXISTS (SELECT 1 FROM assignments a
                    WHERE a.customerid = c.id
                    	AND a.commited = 1
                        AND datefrom <= ?NOW?
                        AND (dateto >= ?NOW? OR dateto = 0)
                        AND (tariffid IS NOT NULL OR liabilityid IS NOT NULL))' : '')
                . ($suspended ? ' AND EXISTS (SELECT 1 FROM assignments a
                    WHERE a.customerid = c.id AND (
                        (tariffid IS NULL AND liabilityid IS NULL
                            AND datefrom <= ?NOW?
                            AND (dateto >= ?NOW? OR dateto = 0))
                        OR (datefrom <= ?NOW?
                            AND (dateto >= ?NOW? OR dateto = 0)
                            AND suspended = 1 AND commited = 1)
                        ))' : '')
                . (isset($sqlsarg) ? ' AND (' . $sqlsarg . ')' : '')
                . ($sqlord != ''  && !$count ? $sqlord . ' ' . $direction : '')
                . ($limit !== null && !$count ? ' LIMIT ' . $limit : '')
                . ($offset !== null && !$count ? ' OFFSET ' . $offset : '');

        if (!$count) {
            $customerlist = $this->db->GetAll($sql);

            if (!empty($customerlist)) {
                foreach ($customerlist as $idx => $row) {
                    // summary
                    if ($row['balance'] > 0) {
                        $over += $row['balance'];
                    } elseif ($row['balance'] < 0) {
                        $below += $row['balance'];
                    }
                }
            }

            $customerlist['total'] = empty($customerlist) ? 0 : count($customerlist);
            $customerlist['state'] = $state;
            $customerlist['order'] = $order;
            $customerlist['direction'] = $direction;
            $customerlist['below'] = $below;
            $customerlist['over'] = $over;

            return $customerlist;
        } else {
            $result = $this->db->getRow($sql);
            if (!empty($result)) {
                $result['over'] = $result['balanceover'];
                unset($result['balanceover']);
                $result['below'] = $result['balancebelow'];
                unset($result['balancebelow']);
            }
            return $result;
        }
    }

    /**
     * Returns customer nodes
     *
     * @param  int   $id Customer id
     * @param  int   $count Rows limit for SQL query
     * @return array Nodes
     */
    public function getCustomerNodes($id, $count = null)
    {
        return $this->customerNodesProvider($id, 'default', $count);
    }

    /**
     * Returns customer network device nodes
     *
     * @param  int   $id Customer id
     * @param  int   $count Rows limit for SQL query
     * @return array Nodes
     */
    public function getCustomerNetDevNodes($id, $count = null)
    {
        $tmp = $this->customerNodesProvider($id, 'netdev', $count);

        if (!$tmp) {
            return null;
        }

        $netdevs = array();
        foreach ($tmp as $v) {
            $netdevs[ $v['id'] ] = $v;
        }

        return $netdevs;
    }

    protected function customerNodesProvider($customer_id, $type = '', $count = null)
    {
        $type = strtolower($type);

        $result = $this->db->GetAll("SELECT
                                        n.id, n.name, mac, ipaddr, inet_ntoa(ipaddr) AS ip, nd.name as netdev_name,
                                        ipaddr_pub, n.authtype, inet_ntoa(ipaddr_pub) AS ip_pub,
                                        passwd, access, warning, info, n.ownerid, lastonline, n.location, n.address_id,
                                        (SELECT COUNT(*)
                                        FROM nodegroupassignments
                                        WHERE nodeid = n.id) AS gcount,
                                        n.netid, net.name AS netname
                                     FROM
                                        vnodes n
                                        JOIN networks net ON net.id = n.netid
                                        " . ($type == 'netdev' ? '' : 'LEFT ') . "JOIN netdevices nd ON n.netdev = nd.id
                                     WHERE
                                        " . ($type == 'netdev' ? 'nd.ownerid = ? AND n.ownerid IS NULL' : 'n.ownerid = ?') . "
                                     ORDER BY
                                        n.name ASC " . ($count ? 'LIMIT ' . $count : ''), array($customer_id));

        if ($result) {
            // assign network(s) to node record
            $network_manager = new LMSNetworkManager($this->db, $this->auth, $this->cache);
            $networks = (array) $network_manager->GetNetworks();

            foreach ($result as $idx => $node) {
                $ids[$node['id']] = $idx;
                $result[$idx]['lastonlinedate'] = lastonline_date($node['lastonline']);

                if (!$result[$idx]['address_id']) {
                    $result[$idx]['location'] = $this->getAddressForCustomerStuff($customer_id);
                }

                if ($node['ipaddr_pub']) {
                    foreach ($networks as $net) {
                        if (isipin($node['ip_pub'], $net['address'], $net['mask'])) {
                            $result[$idx]['network_pub'] = $net;
                            break;
                        }
                    }
                }
            }

            // get EtherWerX channels
            if (ConfigHelper::checkValue(ConfigHelper::getConfig('phpui.ewx_support', false))) {
                $channels = $this->db->GetAllByKey('SELECT nodeid, channelid, c.name, c.id, cid,
				        nc.upceil, nc.downceil
			 		FROM ewx_stm_nodes
					JOIN ewx_stm_channels nc ON (channelid = nc.id)
					LEFT JOIN ewx_channels c ON (c.id = nc.cid)
					WHERE nodeid IN (' . implode(',', array_keys($ids)) . ')', 'nodeid');

                if ($channels) {
                    foreach ($channels as $channel) {
                        $idx = $ids[$channel['nodeid']];
                        $result[$idx]['channelid']   = $channel['id'] ? $channel['id'] : $channel['channelid'];
                        $result[$idx]['channelname'] = $channel['name'];
                        $result[$idx]['cid']         = $channel['cid'];
                        $result[$idx]['downceil']    = $channel['downceil'];
                        $result[$idx]['upceil']      = $channel['upceil'];
                    }
                }
            }
        }

        return $result;
    }

    /**
     * Returns customer network devices.
     *
     * @param  int   $customer_id Customer id
     * @return array network devices
     */
    public function GetCustomerNetDevs($customer_id)
    {

        $netdevs = $this->db->GetAllByKey('SELECT
                                              nd.id, nd.name, va.city AS location_city, va.city_id AS location_city_id, va.street AS location_street,
                                              va.street_id AS location_street_id, va.zip AS location_zip, va.location_house, va.location_flat,
                                              nd.description, nd.producer,
                                              nd.model, nd.serialnumber, nd.ports, nd.purchasetime, nd.guaranteeperiod, nd.shortname, nd.nastype,
                                              nd.clients, nd.community, nd.channelid, nd.longitude, nd.latitude, nd.netnodeid, nd.invprojectid,
                                              nd.status, nd.netdevicemodelid, nd.ownerid, no.authtype, va.id as address_id
                                           FROM
                                              netdevices nd
                                              LEFT JOIN vaddresses va ON nd.address_id = va.id
                                              LEFT JOIN nodes no ON nd.id = no.netdev
                                           WHERE
                                              nd.ownerid = ?', 'id', array(intval($customer_id)));

        return $netdevs;
    }

    /**
     * Returns customer networks
     *
     * @param int $id Customer id
     * @param int $count Limit
     * @return array Networks
     */
    public function GetCustomerNetworks($id, $count = null)
    {
        return $this->db->GetAll('
            SELECT *
            FROM vnetworks
            WHERE ownerid = ?
            ORDER BY name ASC
            ' . ($count ? ' LIMIT ' . $count : ''), array($id));
    }

    /**
     * Returns customer data
     *
     * @global array $CONTACTTYPES
     * @param int $id Customer id
     * @param boolean $short Basic or expanded data
     * @return array|boolean Customer data or false on failure
     */
    public function GetCustomer($id, $short = false)
    {
        global $CONTACTTYPES, $CUSTOMERCONTACTTYPES;

        require_once(LIB_DIR . DIRECTORY_SEPARATOR . 'customercontacttypes.php');

        $capitalize_customer_names = ConfigHelper::checkValue(ConfigHelper::getConfig('phpui.capitalize_customer_names', true));
        if ($result = $this->db->GetRow('SELECT c.*, '
                . $this->db->Concat($capitalize_customer_names ? 'UPPER(c.lastname)' : 'c.lastname', "' '", 'c.name') . ' AS customername,
			d.shortname AS division, d.account
			FROM customer' . (defined('LMS-UI') ? '' : 'address') . 'view c
			LEFT JOIN divisions d ON (d.id = c.divisionid)
			WHERE c.id = ?', array($id))) {
            if (!$short) {
                $user_manager = new LMSUserManager($this->db, $this->auth, $this->cache, $this->syslog);
                $result['createdby'] = $user_manager->getUserName($result['creatorid']);
                $result['modifiedby'] = $user_manager->getUserName($result['modid']);
                $result['creationdateh'] = date('Y/m/d, H:i', $result['creationdate']);
                $result['moddateh'] = date('Y/m/d, H:i', $result['moddate']);
                $result['consentdate'] = $result['consentdate'] ? date('Y/m/d', $result['consentdate']) : '';
                $result['up_logins'] = $this->db->GetRow('SELECT lastlogindate, lastloginip,
					failedlogindate, failedloginip
					FROM up_customers WHERE customerid = ?', array($result['id']));

                // Get country name
                if ($result['countryid']) {
                    $result['country'] = $this->db->GetOne('SELECT name FROM countries WHERE id = ?', array($result['countryid']));
                    if ($result['countryid'] == $result['post_countryid']) {
                        $result['post_country'] = $result['country'];
                    } else if ($result['post_countryid']) {
                        $result['country'] = $this->db->GetOne('SELECT name FROM countries WHERE id = ?', array($result['post_countryid']));
                    }
                }

                // Get state name
                if ($cstate = $this->db->GetRow('SELECT s.id, s.name
					FROM states s, zipcodes
					WHERE zip = ? AND stateid = s.id', array($result['zip']))) {
                    $result['stateid'] = $cstate['id'];
                    $result['cstate'] = $cstate['name'];
                }
                if ($result['zip'] == $result['post_zip']) {
                    $result['post_stateid'] = $result['stateid'];
                    $result['post_cstate'] = $result['cstate'];
                } else if ($result['post_zip'] && ($cstate = $this->db->GetRow('SELECT s.id, s.name
					FROM states s, zipcodes
					WHERE zip = ? AND stateid = s.id', array($result['post_zip'])))) {
                    $result['post_stateid'] = $cstate['id'];
                    $result['post_cstate'] = $cstate['name'];
                }
            }
            $result['balance'] = $this->getCustomerBalance($result['id']);
            $result['bankaccount'] = bankaccount($result['id'], $result['account']);

            foreach ($CUSTOMERCONTACTTYPES as $contacttype => $properties) {
                $result[$contacttype . 's'] = $this->db->GetAll(
                    'SELECT contact AS ' . $contacttype . ',
						contact, name, type
					FROM customercontacts
					WHERE customerid = ? AND type & ? > 0 ORDER BY id',
                    array($result['id'], $properties['flagmask'])
                );
            }

            $result['sendinvoices'] = false;
            $result['senddocuments'] = false;

            foreach (array_keys($CUSTOMERCONTACTTYPES) as $ctype) {
                $customercontacttype = $CUSTOMERCONTACTTYPES[$ctype];
                $ctype .= 's';
                if (is_array($result[$ctype])) {
                    foreach ($result[$ctype] as $idx => $row) {
                        $types = array();
                        foreach ($CONTACTTYPES as $tidx => $tname) {
                            if ($row['type'] & $tidx && isset($customercontacttype['ui']['flags'][$row['type'] & $tidx])) {
                                $types[] = $tname;
                            }
                        }

                        if (isset($customercontacttype['ui']['typeselectors'])) {
                            $result[$ctype][$idx]['typeselector'] = $tidx;
                        }

                        if ($ctype == 'emails' && (($row['type'] & (CONTACT_INVOICES | CONTACT_DISABLED)) == CONTACT_INVOICES)) {
                            $result['sendinvoices'] = true;
                        }

                        if ($ctype == 'emails' && (($row['type'] & (CONTACT_DOCUMENTS | CONTACT_DISABLED)) == CONTACT_DOCUMENTS)) {
                            $result['senddocuments'] = true;
                        }

                        if ($types) {
                            $result[$ctype][$idx]['typestr'] = implode('/', $types);
                        }
                    }
                }
            }
            $result['contacts'] = $result['phones'];

            if (empty($result['invoicenotice'])) {
                $result['sendinvoices'] = false;
            }

            $result['addresses'] = $this->getCustomerAddresses($result['id']);

            return $result;
        } else {
            return false;
        }
    }

    /**
    * Updates customer
    *
    * @param array $customerdata Customer data
    * @return int Affected rows
    */
    public function customerUpdate($customerdata)
    {
        $location_manager = new LMSLocationManager($this->db, $this->auth, $this->cache, $this->syslog);

        $args = array(
            'extid'          => $customerdata['extid'],
            'status'         => $customerdata['status'],
            'type'           => empty($customerdata['type']) ? 0 : 1,
            'ten'            => $customerdata['ten'],
            'ssn'            => $customerdata['ssn'],
            SYSLOG::RES_USER => Auth::GetCurrentUser(),
            'info'           => $customerdata['info'],
            'notes'          => $customerdata['notes'],
            'lastname'       => $customerdata['lastname'],
            'name'           => $customerdata['name'],
            'message'        => $customerdata['message'],
            'pin'            => $customerdata['pin'],
            'regon'          => $customerdata['regon'],
            'icn'            => $customerdata['icn'],
            'rbename'        => $customerdata['rbename'],
            'rbe'            => $customerdata['rbe'],
            'cutoffstop'     => $customerdata['cutoffstop'],
            'consentdate'    => $customerdata['consentdate'],
            'einvoice'       => $customerdata['einvoice'],
            'invoicenotice'  => $customerdata['invoicenotice'],
            'mailingnotice'  => $customerdata['mailingnotice'],
            SYSLOG::RES_DIV  => empty($customerdata['divisionid']) ? null : $customerdata['divisionid'],
            'paytime'        => $customerdata['paytime'],
            'paytype'        => $customerdata['paytype'] ? $customerdata['paytype'] : null,
            SYSLOG::RES_CUST => $customerdata['id']
        );

        $current_addresses = $this->getCustomerAddresses($customerdata['id']);

        // INSERT OR UPDATE ADDRESS
        foreach ($customerdata['addresses'] as $v) {
            if (empty($v['address_id'])) {
                $id = $location_manager->InsertCustomerAddress($customerdata['id'], $v);
            } else {
                $location_manager->UpdateCustomerAddress($customerdata['id'], $v);
            }

            // update country states
            if ($v['location_zip'] && $v['location_state'] && !isset($v['teryt'])) {
                $location_manager->UpdateCountryState($v['location_zip'], $v['location_state']);
            }
        }

        // DELETE OLD ADDRESSES
        foreach ($current_addresses as $k => $v) {
            $found = 0;

            foreach ($customerdata['addresses'] as $v2) {
                if (!empty($v2['address_id']) && $v2['address_id'] == $v['address_id']) {
                    $found = 1;
                }
            }

            if (!$found) {
                $location_manager->DeleteAddress($k);
            }
        }

        $capitalize_customer_names = ConfigHelper::checkValue(ConfigHelper::getConfig('phpui.capitalize_customer_names', true));

        // UPDATE CUSTOMER FIELDS
        $res = $this->db->Execute('UPDATE customers SET extid=?, status=?, type=?,
                               ten=?, ssn=?, moddate=?NOW?, modid=?,
                               info=?, notes=?, lastname=' . ($capitalize_customer_names ? 'UPPER(?)' : '?') . ', name=?,
                               deleted=0, message=?, pin=?, regon=?, icn=?, rbename=?, rbe=?,
                               cutoffstop=?, consentdate=?, einvoice=?, invoicenotice=?, mailingnotice=?,
                               divisionid=?, paytime=?, paytype=?
                               WHERE id=?', array_values($args));

        if ($res) {
            if ($this->syslog) {
                unset($args[SYSLOG::RES_USER]);
                $args['deleted'] = 0;
                $this->syslog->AddMessage(SYSLOG::RES_CUST, SYSLOG::OPER_UPDATE, $args);
            }
        }

        return $res;
    }

    /**
     * Deletes customer
     *
     * @global type $LMS
     * @param int $id Customer id
     */
    public function deleteCustomer($id)
    {
        global $LMS;

        $disable_customer_contacts = ConfigHelper::checkConfig('phpui.disable_contacts_during_customer_delete');

        $this->db->BeginTrans();

        $this->db->Execute('UPDATE customers SET deleted=1, moddate=?NOW?, modid=?
                WHERE id=?', array(Auth::GetCurrentUser(), $id));

        if ($this->syslog) {
            $this->syslog->AddMessage(
                SYSLOG::RES_CUST,
                SYSLOG::OPER_UPDATE,
                array(SYSLOG::RES_CUST => $id, 'deleted' => 1)
            );
            $assigns = $this->db->GetAll('SELECT id, customergroupid FROM customerassignments WHERE customerid = ?', array($id));
            if (!empty($assigns)) {
                foreach ($assigns as $assign) {
                    $args = array(
                    SYSLOG::RES_CUSTASSIGN => $assign['id'],
                    SYSLOG::RES_CUST => $id,
                    SYSLOG::RES_CUSTGROUP => $assign['customergroupid']
                    );
                    $this->syslog->AddMessage(SYSLOG::RES_CUSTASSIGN, SYSLOG::OPER_DELETE, $args);
                }
            }
        }

        $this->db->Execute('DELETE FROM customerassignments WHERE customerid=?', array($id));

        if ($this->syslog) {
            $assigns = $this->db->GetAll('SELECT id, tariffid, liabilityid FROM assignments WHERE customerid = ?', array($id));
            if (!empty($assigns)) {
                foreach ($assigns as $assign) {
                    if ($assign['liabilityid']) {
                        $args = array(
                        SYSLOG::RES_LIAB => $assign['liabilityid'],
                        SYSLOG::RES_CUST => $id);
                        $this->syslog->AddMessage(SYSLOG::RES_LIAB, SYSLOG::OPER_DELETE, $args);
                    }
                    $args = array(
                    SYSLOG::RES_ASSIGN => $assign['id'],
                    SYSLOG::RES_TARIFF => $assign['tariffid'],
                    SYSLOG::RES_LIAB => $assign['liabilityid'],
                    SYSLOG::RES_CUST => $id
                    );
                    $this->syslog->AddMessage(SYSLOG::RES_ASSIGN, SYSLOG::OPER_DELETE, $args);
                    $nodeassigns = $this->db->GetAll('SELECT id, nodeid FROM nodeassignments WHERE assignmentid = ?', array($assign['id']));
                    if (!empty($nodeassigns)) {
                        foreach ($nodeassigns as $nodeassign) {
                            $args = array(
                            SYSLOG::RES_NODEASSIGN => $nodeassign['id'],
                            SYSLOG::RES_NODE => $nodeassign['nodeid'],
                            SYSLOG::RES_ASSIGN => $assign['id'],
                            SYSLOG::RES_CUST => $id
                            );
                            $this->syslog->AddMessage(SYSLOG::RES_NODEASSIGN, SYSLOG::OPER_DELETE, $args);
                        }
                    }
                }
            }

            if ($disable_customer_contacts) {
                $contacts = $this->db->GetCol('SELECT id FROM customercontacts WHERE customerid = ?', array($id));
                if (!empty($contacts)) {
                    foreach ($contacts as $contact) {
                        $args = array(
                        SYSLOG::RES_CUSTCONTACT => $contact,
                        SYSLOG::RES_CUST => $id,
                        );
                        $this->syslog->AddMessage(SYSLOG::RES_CUSTCONTACT, SYSLOG::OPER_UPDATE, $args);
                    }
                }
            }
        }

        $liabs = $this->db->GetCol('SELECT liabilityid FROM assignments WHERE liabilityid IS NOT NULL AND customerid = ?', array($id));
        if (!empty($liabs)) {
            $this->db->Execute('DELETE FROM liabilities WHERE id IN (' . implode(',', $liabs) . ')');
        }

        $this->db->Execute('DELETE FROM assignments WHERE customerid=?', array($id));
        // nodes
        $nodes = $this->db->GetCol('SELECT id FROM vnodes WHERE ownerid=?', array($id));
        if ($nodes) {
            if ($this->syslog) {
                $macs = $this->db->GetAll('SELECT id, nodeid FROM macs WHERE nodeid IN (' . implode(',', $nodes) . ')');
                foreach ($macs as $mac) {
                    $args = array(
                        SYSLOG::RES_MAC => $mac['id'],
                        SYSLOG::RES_NODE => $mac['nodeid']);
                    $this->syslog->AddMessage(SYSLOG::RES_MAC, SYSLOG::OPER_DELETE, $args);
                }
                foreach ($nodes as $node) {
                    $args = array(
                        SYSLOG::RES_NODE => $node,
                        SYSLOG::RES_CUST => $id
                    );
                    $this->syslog->AddMessage(SYSLOG::RES_NODE, SYSLOG::OPER_DELETE, $args);
                }
            }

            $this->db->Execute('DELETE FROM nodegroupassignments WHERE nodeid IN (' . join(',', $nodes) . ')');
            $plugin_data = array();
            foreach ($nodes as $node) {
                $plugin_data[] = array('id' => $node, 'ownerid' => $id);
            }
            $LMS->ExecHook('node_del_before', $plugin_data);
            $this->db->Execute('DELETE FROM nodes WHERE ownerid=?', array($id));
            $LMS->ExecHook('node_del_after', $plugin_data);
        }

        // hosting
        $this->db->Execute('UPDATE passwd SET ownerid=NULL WHERE ownerid=?', array($id));
        $this->db->Execute('UPDATE domains SET ownerid=NULL WHERE ownerid=?', array($id));

        if ($disable_customer_contacts) {
            $this->db->Execute(
                'UPDATE customercontacts SET type = type | ? WHERE customerid = ?',
                array(CONTACT_DISABLED, $id)
            );
        }

        // Remove Userpanel rights
        $userpanel_dir = ConfigHelper::getConfig('directories.userpanel_dir');
        if (!empty($userpanel_dir)) {
            $this->db->Execute('DELETE FROM up_rights_assignments WHERE customerid=?', array($id));
        }

        $this->db->CommitTrans();
    }

    /**
     * Deletes customer permanently
     *
     * @param int $id Customer id
     */
    public function deleteCustomerPermanent($id)
    {
        $this->db->BeginTrans();

        // Remove customer addresses
        $addr_ids = $this->db->GetCol('SELECT address_id FROM customer_addresses WHERE customer_id = ?', array($id));
        $this->db->Execute('DELETE FROM addresses WHERE id in (' . implode($addr_ids, ',') . ')');

        $this->deleteCustomer($id);

        $this->db->Execute('DELETE FROM customers WHERE id = ?', array($id));

        if ($this->syslog) {
            $this->syslog->AddMessage(SYSLOG::RES_CUST, SYSLOG::OPER_DELETE, array(SYSLOG::RES_CUST => $id));
        }

        $this->db->CommitTrans();
    }

    /**
     * Check if address is belong to customer.
     *
     * \param  int $a_id address id
     * \param  int $c id customer id
     * \return boolean   true/false
     */
    public function checkCustomerAddress($a_id, $c_id)
    {
        $addr_id = $this->db->GetOne('SELECT address_id
                                      FROM customer_addresses
                                      WHERE
                                         customer_id = ? AND
                                         address_id  = ?', array( $c_id, $a_id ));

        if ($a_id != $addr_id) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Returns all customer addresses.
     *
     * \param  int   $id           customer id
     * \param  bool  $hide_deleted show only active customers
     * \return array
     */
    public function getCustomerAddresses($id, $hide_deleted = false)
    {

        $data = $this->db->GetAllByKey(
            'SELECT
                                          addr.id AS address_id, ca.id AS customer_address_id,
                                          addr.name as location_name,
                                          addr.state as location_state_name, addr.state_id as location_state,
                                          addr.city as location_city_name, addr.city_id as location_city,
                                          addr.street as location_street_name, addr.street_id as location_street,
                                          addr.house as location_house, addr.zip as location_zip, addr.postoffice AS location_postoffice,
                                          addr.country_id as location_country_id, addr.flat as location_flat,
                                          ca.type as location_address_type, addr.location, 0 AS use_counter,
                                          (CASE WHEN addr.city_id is not null THEN 1 ELSE 0 END) as teryt
                                       FROM
                                          customers cv
                                          LEFT JOIN customer_addresses ca ON ca.customer_id = cv.id
                                          LEFT JOIN vaddresses addr       ON addr.id = ca.address_id
                                       WHERE
                                          cv.id = ?' .
                                          (($hide_deleted) ? ' AND cv.deleted != 1' : ''),
            'address_id',
            array( $id )
        );

        if (!$data) {
            return array();
        }

        $node_addresses = $this->db->GetAllByKey('SELECT address_id, COUNT(*) AS used FROM nodes
			WHERE ownerid = ? AND address_id IS NOT NULL
			GROUP BY address_id', 'address_id', array($id));
        if (empty($node_addresses)) {
            $node_addresses = array();
        }

        $netdev_addresses = $this->db->GetAllByKey('SELECT address_id, COUNT(*) AS used FROM netdevices
			WHERE ownerid = ? AND address_id IS NOT NULL
			GROUP BY address_id', 'address_id', array($id));
        if (empty($netdev_addresses)) {
            $netdev_addresses = array();
        }

        foreach (array($node_addresses, $netdev_addresses) as $addresses) {
            foreach ($addresses as $address_id => $address) {
                if (isset($data[$address_id])) {
                    $data[$address_id]['use_counter'] += $address['used'];
                }
            }
        }

        return $data;
    }

    /*!
     * \brief Method return best matching address for customer stuff.
     * As first method try get DEFAULT_LOCATION_ADDRESS, if not found
     * then returns BILLING_ADDRESS
     *
     * \param  int    $customer_id customer_id
     * \return string location string
     * \return null   any address not found
     */
    public function getAddressForCustomerStuff($customer_id)
    {
        $addresses = $this->db->GetAllByKey('SELECT
                                                ca.type, addr.location
                                             FROM customer_addresses ca
                                                LEFT JOIN vaddresses addr ON ca.address_id = addr.id
                                             WHERE
                                                ca.customer_id = ?', 'type', array($customer_id));

        if (isset($addresses[DEFAULT_LOCATION_ADDRESS])) {
            return $addresses[DEFAULT_LOCATION_ADDRESS]['location'];
        }

        if (isset($addresses[BILLING_ADDRESS])) {
            return $addresses[BILLING_ADDRESS]['location'];
        }

        return null;
    }

    public function getFullAddressForCustomerStuff($customer_id)
    {
        $addresses = $this->db->GetAllByKey('SELECT
                                                ca.address_id, ca.type, addr.location
                                             FROM customer_addresses ca
                                                LEFT JOIN vaddresses addr ON ca.address_id = addr.id
                                             WHERE
                                                ca.customer_id = ?', 'type', array($customer_id));

        if (isset($addresses[DEFAULT_LOCATION_ADDRESS])) {
            return $addresses[DEFAULT_LOCATION_ADDRESS];
        }

        if (isset($addresses[BILLING_ADDRESS])) {
            return $addresses[BILLING_ADDRESS];
        }

        return null;
    }

    public function GetCustomerContacts($id, $mask = null)
    {
        $contacts = $this->db->GetAll(
            'SELECT contact, name, type FROM customercontacts
			WHERE customerid = ?' . (isset($mask) ? ' AND type & ' . intval($mask) . ' > 0' : ''),
            array($id)
        );
        if (empty($contacts)) {
            return array();
        }
        foreach ($contacts as &$contact) {
            $contact['fullname'] = $contact['contact'] . (strlen($contact['name']) ? ' (' . $contact['name'] . ')' : '');
        }
        return $contacts;
    }

    public function GetCustomerDivision($id)
    {
        return $this->db->GetOne('SELECT divisionid FROM customers WHERE id = ?', array($id));
    }
}
