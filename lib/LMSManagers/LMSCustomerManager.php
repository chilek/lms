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
            WHERE status <> ? AND deleted = 0 
            ORDER BY lastname, name', 
            'id', array(CSTATUS_INTERESTED)
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
            . ' ORDER BY time ' . $direction . ', cash.id',
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
		global $CSTATUSES;
		$sql = '';
		foreach ($CSTATUSES as $statusidx => $status)
			$sql .= ' COUNT(CASE WHEN status = ' . $statusidx . ' THEN 1 END) AS ' . $status['alias'] . ',';
        $result = $this->db->GetRow(
            'SELECT ' . $sql . ' COUNT(id) AS total
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
    
    /**
     * Adds customer
     * 
     * @global array $SYSLOG_RESOURCE_KEYS
     * @param array $customeradd Customer data
     * @return boolean False on failure, customer id on success
     */
    public function customerAdd($customeradd)
    {
        global $SYSLOG_RESOURCE_KEYS;
        $args = array(
            'name' => $customeradd['name'],
            'lastname' => $customeradd['lastname'],
            'type' => empty($customeradd['type']) ? 0 : 1,
            'address' => $customeradd['address'],
            'zip' => $customeradd['zip'],
            'city' => $customeradd['city'],
            $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_COUNTRY] => $customeradd['countryid'],
            'ten' => $customeradd['ten'],
            'ssn' => $customeradd['ssn'],
            'status' => $customeradd['status'],
            'post_name' => $customeradd['post_name'],
            'post_address' => $customeradd['post_address'],
            'post_zip' => $customeradd['post_zip'],
            'post_city' => $customeradd['post_city'],
            'post_countryid' => $customeradd['post_countryid'],
            $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_USER] => $this->auth->id,
            'info' => $customeradd['info'],
            'notes' => $customeradd['notes'],
            'message' => $customeradd['message'],
            'pin' => $customeradd['pin'],
            'regon' => $customeradd['regon'],
            'rbe' => $customeradd['rbe'],
            'icn' => $customeradd['icn'],
            'cutoffstop' => $customeradd['cutoffstop'],
            'consentdate' => $customeradd['consentdate'],
            'einvoice' => $customeradd['einvoice'],
            $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_DIV] => $customeradd['divisionid'],
            'paytime' => $customeradd['paytime'],
            'paytype' => !empty($customeradd['paytype']) ? $customeradd['paytype'] : null,
            'invoicenotice' => $customeradd['invoicenotice'],
            'mailingnotice' => $customeradd['mailingnotice'],
        );
        if ($this->db->Execute('INSERT INTO customers (name, lastname, type,
				    address, zip, city, countryid, ten, ssn, status, creationdate,
				    post_name, post_address, post_zip, post_city, post_countryid,
				    creatorid, info, notes, message, pin, regon, rbe,
				    icn, cutoffstop, consentdate, einvoice, divisionid, paytime, paytype,
				    invoicenotice, mailingnotice)
				    VALUES (?, UPPER(?), ?, ?, ?, ?, ?, ?, ?, ?, ?NOW?,
				    ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', array_values($args))
        ) {
            $location_manager = new LMSLocationManager($this->db, $this->auth, $this->cache, $this->syslog);
            $location_manager->UpdateCountryState($customeradd['zip'], $customeradd['stateid']);
            if ($customeradd['post_zip'] != $customeradd['zip']) {
                $location_manager->UpdateCountryState($customeradd['post_zip'], $customeradd['post_stateid']);
            }
            $id = $this->db->GetLastInsertID('customers');
            if ($this->syslog) {
                $args[$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST]] = $id;
                unset($args[$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_USER]]);
                $this->syslog->AddMessage(SYSLOG_RES_CUST, SYSLOG_OPER_ADD, $args, array($SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST],
                    $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_DIV],
                    $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_COUNTRY]));
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
    public function getCustomerList($order = 'customername,asc', $state = null, $network = null, $customergroup = null, $search = null, $time = null, $sqlskey = 'AND', $nodegroup = null, $division = null, $limit = null, $offset = null, $count = false)
    {
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
            case 51: $disabled = 1;
                break;
            case 52: $indebted = 1;
                break;
            case 53: $online = 1;
                break;
            case 54: $groupless = 1;
                break;
            case 55: $tariffless = 1;
                break;
            case 56: $suspended = 1;
                break;
            case 57: $indebted2 = 1;
                break;
            case 58: $indebted3 = 1;
                break;
            case 59: case 60: case 61:
                     $contracts = $state - 58;
                     $contracts_days = intval(ConfigHelper::getConfig('contracts.contracts_days'));
                     break;
        }

        if ($network) {
            $network_manager = new LMSNetworkManager($this->db, $this->auth, $this->cache, $this->syslog);
            $net = $network_manager->getNetworkParams($network);
        }

        $over = 0;
        $below = 0;

        if (sizeof($search))
            foreach ($search as $key => $value) {
                if ($value != '') {
                    switch ($key) {
			case 'phone':
				$searchargs[] = 'EXISTS (SELECT 1 FROM customercontacts
					WHERE customerid = c.id AND (customercontacts.type < ' . CONTACT_EMAIL
					. ') AND REPLACE(contact, \'-\', \'\') ?LIKE? ' . $this->db->Escape("%$value%") . ')';
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
                            $searchargs[] = "(UPPER($key) ?LIKE? UPPER(" . $this->db->Escape("%$value%") . ")
								OR UPPER(post_$key) ?LIKE? UPPER(" . $this->db->Escape("%$value%") . '))';
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
                            } else
                                $searchargs[] = 'creationdate >= ' . intval($value);
                            break;
                        case 'createdto':
                            if (!isset($searchargs['createdfrom']))
                                $searchargs[] = 'creationdate <= ' . intval($value);
                            break;
                        case 'deletedfrom':
                            if ($search['deletedto']) {
                                $searchargs['deletedfrom'] = '(moddate >= ' . intval($value)
                                        . ' AND moddate <= ' . intval($search['deletedto']) . ')';
                                unset($search['deletedto']);
                            } else
                                $searchargs[] = 'moddate >= ' . intval($value);
                            $deleted = 1;
                            break;
                        case 'deletedto':
                            if (!isset($searchargs['deletedfrom']))
                                $searchargs[] = 'moddate <= ' . intval($value);
                            $deleted = 1;
                            break;
                        case 'type':
                            $searchargs[] = 'type = ' . intval($value);
                            break;
                        case 'linktype':
                            $searchargs[] = 'EXISTS (SELECT 1 FROM nodes
								WHERE ownerid = c.id AND linktype = ' . intval($value) . ')';
                            break;
                        case 'linktechnology':
                            $searchargs[] = 'EXISTS (SELECT 1 FROM nodes
								WHERE ownerid = c.id AND linktechnology = ' . intval($value) . ')';
                            break;
                        case 'linkspeed':
                            $searchargs[] = 'EXISTS (SELECT 1 FROM nodes
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
							AND (datefrom <= ?NOW? OR datefrom = 0) 
							AND (dateto >= ?NOW? OR dateto = 0)
							AND (tariffid IN (' . $value . ')))';
                            break;
                        case 'tarifftype':
                            $searchargs[] = 'EXISTS (SELECT 1 FROM assignments a 
							JOIN tariffs t ON t.id = a.tariffid
							WHERE a.customerid = c.id
							AND (datefrom <= ?NOW? OR datefrom = 0) 
							AND (dateto >= ?NOW? OR dateto = 0)
							AND (t.type = ' . intval($value) . '))';
                            break;
                        default:
                            $searchargs[] = "$key ?LIKE? " . $this->db->Escape("%$value%");
                    }
                }
            }

        if (isset($searchargs))
            $sqlsarg = implode(' ' . $sqlskey . ' ', $searchargs);

        $suspension_percentage = f_round(ConfigHelper::getConfig('finances.suspension_percentage'));

        $sql = '';
        
        if ($count) {
            $sql .= 'SELECT COUNT(*) ';
        } else {
            $sql .= 'SELECT c.id AS id, ' . $this->db->Concat('UPPER(lastname)', "' '", 'c.name') . ' AS customername, 
                status, address, zip, city, countryid, countries.name AS country, cc.email, ten, ssn, c.info AS info, 
                message, c.divisionid, c.paytime AS paytime, COALESCE(b.value, 0) AS balance,
                COALESCE(t.value, 0) AS tariffvalue, s.account, s.warncount, s.online,
                (CASE WHEN s.account = s.acsum THEN 1
                    WHEN s.acsum > 0 THEN 2 ELSE 0 END) AS nodeac,
                (CASE WHEN s.warncount = s.warnsum THEN 1
                    WHEN s.warnsum > 0 THEN 2 ELSE 0 END) AS nodewarn ';
        }
        
        $sql .= 'FROM customersview c
            LEFT JOIN (SELECT customerid, (' . $this->db->GroupConcat('contact') . ') AS email
            FROM customercontacts WHERE (type & ' . CONTACT_EMAIL .' = '. CONTACT_EMAIL .') GROUP BY customerid) cc ON cc.customerid = c.id
            LEFT JOIN countries ON (c.countryid = countries.id) '
            . ($customergroup ? 'LEFT JOIN customerassignments ON (c.id = customerassignments.customerid) ' : '')
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
                    WHERE (a.datefrom <= ?NOW? OR a.datefrom = 0) AND (a.dateto > ?NOW? OR a.dateto = 0) 
                    GROUP BY a.customerid
                ) t ON (t.customerid = c.id)
                LEFT JOIN (SELECT ownerid,
                    SUM(access) AS acsum, COUNT(access) AS account,
                    SUM(warning) AS warnsum, COUNT(warning) AS warncount, 
                    (CASE WHEN MAX(lastonline) > ?NOW? - ' . intval(ConfigHelper::getConfig('phpui.lastonline_limit')) . '
                        THEN 1 ELSE 0 END) AS online
                    FROM nodes
                    WHERE ownerid > 0
                    GROUP BY ownerid
                ) s ON (s.ownerid = c.id) '
                . ($contracts == 1 ? '
                    LEFT JOIN (
                        SELECT COUNT(*), d.customerid FROM documents d
                        JOIN documentcontents dc ON dc.docid = d.id
                                WHERE d.type IN (' . DOC_CONTRACT . ',' . DOC_ANNEX . ')
                                GROUP BY d.customerid
                        ) d ON d.customerid = c.id' : '')
                    . ($contracts == 2 ? '
                        JOIN (
                            SELECT SUM(CASE WHEN dc.todate < ?NOW? THEN 1 ELSE 0 END),
                                SUM(CASE WHEN dc.todate > ?NOW? THEN 1 ELSE 0 END),
                                d.customerid FROM documents d
                            JOIN documentcontents dc ON dc.docid = d.id
                            WHERE d.type IN (' . DOC_CONTRACT . ',' . DOC_ANNEX . ')
                            GROUP BY d.customerid
                            HAVING SUM(CASE WHEN dc.todate < ?NOW? THEN 1 ELSE 0 END) > 0
                                AND SUM(CASE WHEN dc.todate >= ?NOW? THEN 1 ELSE 0 END) = 0
                        ) d ON d.customerid = c.id' : '')
                . ($contracts == 3 ? '
                    JOIN (
                        SELECT DISTINCT d.customerid FROM documents d
                        JOIN documentcontents dc ON dc.docid = d.id
                        WHERE dc.todate >= ?NOW? AND dc.todate <= ?NOW? + 86400 * ' . $contracts_days . '
                            AND type IN (' . DOC_CONTRACT . ',' . DOC_ANNEX . ')
                    ) d ON d.customerid = c.id' : '')
                . ' WHERE c.deleted = ' . intval($deleted)
                . (($state <= 50 && $state > 0) ? ' AND c.status = ' . intval($state) : '')
                . ($division ? ' AND c.divisionid = ' . intval($division) : '')
                . ($online ? ' AND s.online = 1' : '')
                . ($indebted ? ' AND b.value < 0' : '')
                . ($indebted2 ? ' AND b.value < -t.value' : '')
                . ($indebted3 ? ' AND b.value < -t.value * 2' : '')
                . ($contracts == 1 ? ' AND d.customerid IS NULL' : '')
                . ($disabled ? ' AND s.ownerid IS NOT null AND s.account > s.acsum' : '')
                . ($network ? ' AND EXISTS (SELECT 1 FROM nodes WHERE ownerid = c.id 
                AND (netid = ' . $network . '
                OR (ipaddr_pub > ' . $net['address'] . ' AND ipaddr_pub < ' . $net['broadcast'] . ')))' : '')
                . ($customergroup ? ' AND customergroupid=' . intval($customergroup) : '')
                . ($nodegroup ? ' AND EXISTS (SELECT 1 FROM nodegroupassignments na
                    JOIN nodes n ON (n.id = na.nodeid) 
                    WHERE n.ownerid = c.id AND na.nodegroupid = ' . intval($nodegroup) . ')' : '')
                . ($groupless ? ' AND NOT EXISTS (SELECT 1 FROM customerassignments a 
                    WHERE c.id = a.customerid)' : '')
                . ($tariffless ? ' AND NOT EXISTS (SELECT 1 FROM assignments a 
                    WHERE a.customerid = c.id
                        AND (datefrom <= ?NOW? OR datefrom = 0) 
                        AND (dateto >= ?NOW? OR dateto = 0)
                        AND (tariffid != 0 OR liabilityid != 0))' : '')
                . ($suspended ? ' AND EXISTS (SELECT 1 FROM assignments a
                    WHERE a.customerid = c.id AND (
                        (tariffid = 0 AND liabilityid = 0
                            AND (datefrom <= ?NOW? OR datefrom = 0)
                            AND (dateto >= ?NOW? OR dateto = 0)) 
                        OR ((datefrom <= ?NOW? OR datefrom = 0)
                            AND (dateto >= ?NOW? OR dateto = 0)
                            AND suspended = 1)
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
                    if ($row['balance'] > 0)
                        $over += $row['balance'];
                    elseif ($row['balance'] < 0)
                        $below += $row['balance'];
                }
            }

            $customerlist['total'] = sizeof($customerlist);
            $customerlist['state'] = $state;
            $customerlist['order'] = $order;
            $customerlist['direction'] = $direction;
            $customerlist['below'] = $below;
            $customerlist['over'] = $over;

            return $customerlist;
        } else {
            return $this->db->getOne($sql);
        }
    }

    /**
     * Returns customer nodes
     * 
     * @param int $id Customer id
     * @param int $count Limit
     * @return array Nodes
     */
    public function getCustomerNodes($id, $count = null)
    {
        if ($result = $this->db->GetAll('SELECT n.id, n.name, mac, ipaddr,
				inet_ntoa(ipaddr) AS ip, ipaddr_pub,
				inet_ntoa(ipaddr_pub) AS ip_pub, passwd, access,
				warning, info, ownerid, lastonline, location,
				(SELECT COUNT(*) FROM nodegroupassignments
					WHERE nodeid = n.id) AS gcount,
				n.netid, net.name AS netname
				FROM vnodes n
				JOIN networks net ON net.id = n.netid
				WHERE ownerid = ?
				ORDER BY n.name ASC ' . ($count ? 'LIMIT ' . $count : ''), array($id))) {
            // assign network(s) to node record
            $network_manager = new LMSNetworkManager($this->db, $this->auth, $this->cache);
            $networks = (array) $network_manager->GetNetworks();

            foreach ($result as $idx => $node) {
                $ids[$node['id']] = $idx;
                $result[$idx]['lastonlinedate'] = lastonline_date($node['lastonline']);

                //foreach ($networks as $net)
                //	if (isipin($node['ip'], $net['address'], $net['mask'])) {
                //		$result[$idx]['network'] = $net;
                //		break;
                //	}

                if ($node['ipaddr_pub'])
                    foreach ($networks as $net)
                        if (isipin($node['ip_pub'], $net['address'], $net['mask'])) {
                            $result[$idx]['network_pub'] = $net;
                            break;
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

                if ($channels)
                    foreach ($channels as $channel) {
                        $idx = $ids[$channel['nodeid']];
                        $result[$idx]['channelid'] = $channel['id'] ? $channel['id'] : $channel['channelid'];
                        $result[$idx]['channelname'] = $channel['name'];
                        $result[$idx]['cid'] = $channel['cid'];
                        $result[$idx]['downceil'] = $channel['downceil'];
                        $result[$idx]['upceil'] = $channel['upceil'];
                    }
            }
        }
        return $result;
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
        global $CONTACTTYPES;

        if ($result = $this->db->GetRow('SELECT c.*, '
                . $this->db->Concat('UPPER(c.lastname)', "' '", 'c.name') . ' AS customername,
			d.shortname AS division, d.account
			FROM customers' . (defined('LMS-UI') ? 'view' : '') . ' c 
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
                    if ($result['countryid'] == $result['post_countryid'])
                        $result['post_country'] = $result['country'];
                    else if ($result['post_countryid'])
                        $result['country'] = $this->db->GetOne('SELECT name FROM countries WHERE id = ?', array($result['post_countryid']));
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

            $result['messengers'] = $this->db->GetAllByKey('SELECT uid, type 
					FROM imessengers WHERE customerid = ? ORDER BY type', 'type', array($result['id']));
            $result['contacts'] = $this->db->GetAll('SELECT contact AS phone, name, type
					FROM customercontacts
					WHERE customerid = ? AND type & 7 > 0 ORDER BY id',
					array($result['id']));
            $result['emails'] = $this->db->GetAll('SELECT contact AS email, name, type
					FROM customercontacts
					WHERE customerid = ? AND type & ? = ? ORDER BY id',
					array($result['id'], CONTACT_EMAIL, CONTACT_EMAIL));

            if (is_array($result['contacts']))
                foreach ($result['contacts'] as $idx => $row) {
                    $types = array();
                    foreach ($CONTACTTYPES as $tidx => $tname)
                        if ($row['type'] & $tidx)
                            $types[] = $tname;

                    if ($types)
                        $result['contacts'][$idx]['typestr'] = implode('/', $types);
                }
            if (is_array($result['emails']))
                foreach ($result['emails'] as $idx => $row) {
                    $types = array();
                    foreach ($CONTACTTYPES as $tidx => $tname)
                        if ($row['type'] & $tidx)
                            $types[] = $tname;

                    if ($types)
                        $result['emails'][$idx]['typestr'] = implode('/', $types);
                }

            return $result;
        } else
            return false;
    }
    
    /**
    * Updates customer
    * 
    * @global array $SYSLOG_RESOURCE_KEYS
    * @param array $customerdata Customer data
    * @return int Affected rows
    */
    public function customerUpdate($customerdata)
    {
        global $SYSLOG_RESOURCE_KEYS;
        $args = array(
            'status' => $customerdata['status'],
            'type' => empty($customerdata['type']) ? 0 : 1,
            'address' => $customerdata['address'],
            'zip' => $customerdata['zip'],
            'city' => $customerdata['city'],
            $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_COUNTRY] => $customerdata['countryid'],
            'ten' => $customerdata['ten'],
            'ssn' => $customerdata['ssn'],
            $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_USER] => isset($this->auth->id) ? $this->auth->id : 0,
            'post_name' => $customerdata['post_name'],
            'post_address' => $customerdata['post_address'],
            'post_zip' => $customerdata['post_zip'],
            'post_city' => $customerdata['post_city'],
            'post_countryid' => $customerdata['post_countryid'],
            'info' => $customerdata['info'],
            'notes' => $customerdata['notes'],
            'lastname' => $customerdata['lastname'],
            'name' => $customerdata['name'],
            'message' => $customerdata['message'],
            'pin' => $customerdata['pin'],
            'regon' => $customerdata['regon'],
            'icn' => $customerdata['icn'],
            'rbe' => $customerdata['rbe'],
            'cutoffstop' => $customerdata['cutoffstop'],
            'consentdate' => $customerdata['consentdate'],
            'einvoice' => $customerdata['einvoice'],
            'invoicenotice' => $customerdata['invoicenotice'],
            'mailingnotice' => $customerdata['mailingnotice'],
            $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_DIV] => $customerdata['divisionid'],
            'paytime' => $customerdata['paytime'],
            'paytype' => $customerdata['paytype'] ? $customerdata['paytype'] : null,
            $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST] => $customerdata['id']
        );
        $res = $this->db->Execute('UPDATE customers SET status=?, type=?, address=?,
                               zip=?, city=?, countryid=?, ten=?, ssn=?, moddate=?NOW?, modid=?,
                               post_name=?, post_address=?, post_zip=?, post_city=?, post_countryid=?,
                               info=?, notes=?, lastname=UPPER(?), name=?,
                               deleted=0, message=?, pin=?, regon=?, icn=?, rbe=?,
                               cutoffstop=?, consentdate=?, einvoice=?, invoicenotice=?, mailingnotice=?,
                               divisionid=?, paytime=?, paytype=?
                               WHERE id=?', array_values($args));

        if ($res) {
            if ($this->syslog) {
                unset($args[$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_USER]]);
                $args['deleted'] = 0;
                $this->syslog->AddMessage(SYSLOG_RES_CUST, SYSLOG_OPER_UPDATE, $args, array($SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST],
                    $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_COUNTRY],
                    $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_DIV]));
            }
            $location_manager = new LMSLocationManager($this->db, $this->auth, $this->cache, $this->syslog);
            $location_manager->UpdateCountryState($customerdata['zip'], $customerdata['stateid']);
            if ($customerdata['post_zip'] != $customerdata['zip']) {
                $location_manager->UpdateCountryState($customerdata['post_zip'], $customerdata['post_stateid']);
            }
        }

        return $res;
    }
    
    /**
     * Deletes customer
     * 
     * @global array $SYSLOG_RESOURCE_KEYS
     * @global type $LMS
     * @param int $id Customer id
     */
    public function deleteCustomer($id)
    {
        
        global $SYSLOG_RESOURCE_KEYS, $LMS;
        $this->db->BeginTrans();

        $this->db->Execute('UPDATE customers SET deleted=1, moddate=?NOW?, modid=?
                                    WHERE id=?', array($this->auth->id, $id));

        if ($this->syslog) {
            $this->syslog->AddMessage(SYSLOG_RES_CUST, SYSLOG_OPER_UPDATE, array($SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST] => $id, 'deleted' => 1), array($SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST]));
            $assigns = $this->db->GetAll('SELECT id, customergroupid FROM customerassignments WHERE customerid = ?', array($id));
            if (!empty($assigns))
                foreach ($assigns as $assign) {
                    $args = array(
                        $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUSTASSIGN] => $assign['id'],
                        $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST] => $id,
                        $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUSTGROUP] => $assign['customergroupid']
                    );
                    $this->syslog->AddMessage(SYSLOG_RES_CUSTASSIGN, SYSLOG_OPER_DELETE, $args, array_keys($args));
                }
        }

        $this->db->Execute('DELETE FROM customerassignments WHERE customerid=?', array($id));

        if ($this->syslog) {
            $assigns = $this->db->GetAll('SELECT id, tariffid, liabilityid FROM assignments WHERE customerid = ?', array($id));
            if (!empty($assigns))
                foreach ($assigns as $assign) {
                    if ($assign['liabilityid']) {
                        $args = array(
                            $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_LIAB] => $assign['liabilityid'],
                            $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST] => $id);
                        $this->syslog->AddMessage(SYSLOG_RES_LIAB, SYSLOG_OPER_DELETE, $args, array_keys($args));
                    }
                    $args = array(
                        $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_ASSIGN] => $assign['id'],
                        $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_TARIFF] => $assign['tariffid'],
                        $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_LIAB] => $assign['liabilityid'],
                        $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST] => $id
                    );
                    $this->syslog->AddMessage(SYSLOG_RES_ASSIGN, SYSLOG_OPER_DELETE, $args, array_keys($args));
                    $nodeassigns = $this->db->GetAll('SELECT id, nodeid FROM nodeassignments WHERE assignmentid = ?', array($assign['id']));
                    if (!empty($nodeassigns))
                        foreach ($nodeassigns as $nodeassign) {
                            $args = array(
                                $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NODEASSIGN] => $nodeassign['id'],
                                $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NODE] => $nodeassign['nodeid'],
                                $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_ASSIGN] => $assign['id'],
                                $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST] => $id
                            );
                            $this->syslog->AddMessage(SYSLOG_RES_NODEASSIGN, SYSLOG_OPER_DELETE, $args, array_keys($args));
                        }
                }
        }
        $liabs = $this->db->GetCol('SELECT liabilityid FROM assignments WHERE liabilityid <> 0 AND customerid = ?', array($id));
        if (!empty($liabs))
            $this->db->Execute('DELETE FROM liabilities WHERE id IN (' . implode(',', $liabs) . ')');

        $this->db->Execute('DELETE FROM assignments WHERE customerid=?', array($id));
        // nodes
        $nodes = $this->db->GetCol('SELECT id FROM nodes WHERE ownerid=?', array($id));
        if ($nodes) {
            if ($this->syslog) {
                $macs = $this->db->GetAll('SELECT id, nodeid FROM macs WHERE nodeid IN (' . implode(',', $nodes) . ')');
                foreach ($macs as $mac) {
                    $args = array(
                        $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_MAC] => $mac['id'],
                        $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NODE] => $mac['nodeid']);
                    $this->syslog->AddMessage(SYSLOG_RES_MAC, SYSLOG_OPER_DELETE, $args, array_keys($args));
                }
                foreach ($nodes as $node) {
                    $args = array(
                        $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NODE] => $node,
                        $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST] => $id
                    );
                    $this->syslog->AddMessage(SYSLOG_RES_NODE, SYSLOG_OPER_DELETE, $args, array_keys($args));
                }
            }

            $this->db->Execute('DELETE FROM nodegroupassignments WHERE nodeid IN (' . join(',', $nodes) . ')');
            $plugin_data = array();
            foreach ($nodes as $node)
                $plugin_data[] = array('id' => $node, 'ownerid' => $id);
            $LMS->ExecHook('node_del_before', $plugin_data);
            $this->db->Execute('DELETE FROM nodes WHERE ownerid=?', array($id));
            $LMS->ExecHook('node_del_after', $plugin_data);
        }
        // hosting
        $this->db->Execute('UPDATE passwd SET ownerid=0 WHERE ownerid=?', array($id));
        $this->db->Execute('UPDATE domains SET ownerid=0 WHERE ownerid=?', array($id));
        // Remove Userpanel rights
        $userpanel_dir = ConfigHelper::getConfig('directories.userpanel_dir');
        if (!empty($userpanel_dir))
            $this->db->Execute('DELETE FROM up_rights_assignments WHERE customerid=?', array($id));

        $this->db->CommitTrans();
    }

}
