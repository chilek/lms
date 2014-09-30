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
            'name' => lms_ucwords($customeradd['name']),
            'lastname' => $customeradd['lastname'],
            'type' => empty($customeradd['type']) ? 0 : 1,
            'address' => $customeradd['address'],
            'zip' => $customeradd['zip'],
            'city' => $customeradd['city'],
            $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_COUNTRY] => $customeradd['countryid'],
            'email' => $customeradd['email'],
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
				    address, zip, city, countryid, email, ten, ssn, status, creationdate,
				    post_name, post_address, post_zip, post_city, post_countryid,
				    creatorid, info, notes, message, pin, regon, rbe,
				    icn, cutoffstop, consentdate, einvoice, divisionid, paytime, paytype,
				    invoicenotice, mailingnotice)
				    VALUES (?, UPPER(?), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?NOW?,
				    ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', array_values($args))
        ) {
            $zip_code_manager = new LMSZipCodeManager($this->db, $this->auth, $this->cache, $this->syslog);
            $zip_code_manager->UpdateCountryState($customeradd['zip'], $customeradd['stateid']);
            if ($customeradd['post_zip'] != $customeradd['zip']) {
                $zip_code_manager->UpdateCountryState($customeradd['post_zip'], $customeradd['post_stateid']);
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
     * @return array Customer list
     */
    public function getCustomerList($order = 'customername,asc', $state = null, $network = null, $customergroup = null, $search = null, $time = null, $sqlskey = 'AND', $nodegroup = null, $division = null) {
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
            case 4:
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
            case 5: $disabled = 1;
                break;
            case 6: $indebted = 1;
                break;
            case 7: $online = 1;
                break;
            case 8: $groupless = 1;
                break;
            case 9: $tariffless = 1;
                break;
            case 10: $suspended = 1;
                break;
            case 11: $indebted2 = 1;
                break;
            case 12: $indebted3 = 1;
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
								WHERE customerid = c.id AND phone ?LIKE? ' . $this->db->Escape("%$value%") . ')';
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

        if ($customerlist = $this->db->GetAll(
                'SELECT c.id AS id, ' . $this->db->Concat('UPPER(lastname)', "' '", 'c.name') . ' AS customername, 
				status, address, zip, city, countryid, countries.name AS country, email, ten, ssn, c.info AS info, 
				message, c.divisionid, c.paytime AS paytime, COALESCE(b.value, 0) AS balance,
				COALESCE(t.value, 0) AS tariffvalue, s.account, s.warncount, s.online,
				(CASE WHEN s.account = s.acsum THEN 1
					WHEN s.acsum > 0 THEN 2	ELSE 0 END) AS nodeac,
				(CASE WHEN s.warncount = s.warnsum THEN 1
					WHEN s.warnsum > 0 THEN 2 ELSE 0 END) AS nodewarn
				FROM customersview c
				LEFT JOIN countries ON (c.countryid = countries.id) '
                . ($customergroup ? 'LEFT JOIN customerassignments ON (c.id = customerassignments.customerid) ' : '')
                . 'LEFT JOIN (SELECT
					SUM(value) AS value, customerid
					FROM cash'
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
				) s ON (s.ownerid = c.id)
				WHERE c.deleted = ' . intval($deleted)
                . ($state <= 3 && $state > 0 ? ' AND c.status = ' . intval($state) : '')
                . ($division ? ' AND c.divisionid = ' . intval($division) : '')
                . ($online ? ' AND s.online = 1' : '')
                . ($indebted ? ' AND b.value < 0' : '')
                . ($indebted2 ? ' AND b.value < -t.value' : '')
                . ($indebted3 ? ' AND b.value < -t.value * 2' : '')
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
                . ($sqlord != '' ? $sqlord . ' ' . $direction : '')
                )) {
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

}
