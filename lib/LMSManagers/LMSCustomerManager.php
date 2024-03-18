<?php

/*
 *  LMS version 1.11-git
 *
 *  Copyright (C) 2001-2022 LMS Developers
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
    public const CUSTOMER_LAST_BALANCE_TABLE_STYLE = '<style>
        .customer-last-balance-table th {
            border: 1px solid black;
            white-space: nowrap;
            padding: 0.3em;
            vertical-align: middle;
        }

        .customer-last-balance-table tbody td {
            border: 1px solid black;
            white-space: nowrap;
            padding: 0.3em;
            text-align: center;
            vertical-align: middle;
        }
        </style>';

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
    public function getCustomerEmail($id, $requiredFlags = 0, $forbiddenFlags = 0)
    {
        return $this->db->GetCol(
            'SELECT contact FROM customercontacts
            WHERE customerid = ? AND (type & ?) = ?',
            array(
                $id,
                CONTACT_EMAIL | $requiredFlags | $forbiddenFlags,
                (CONTACT_EMAIL | $requiredFlags) & ~$forbiddenFlags,
            )
        );
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
     * @param boolean|int $expired take only expired liabilities into account
     *   if int then treat it as grace period
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
                'SELECT SUM(value * cash.currencyvalue)
                FROM cash
                LEFT JOIN documents doc ON doc.id = cash.docid
                LEFT JOIN customers cust ON cust.id = cash.customerid
                LEFT JOIN divisions ON divisions.id = cust.divisionid
                LEFT JOIN (
                    SELECT SUM(value * cash.currencyvalue) AS totalvalue, docid
                    FROM cash
                    JOIN documents ON documents.id = cash.docid
                    WHERE cash.customerid = ?
                        AND documents.type = ?
                    GROUP BY docid
                ) tv ON tv.docid = cash.docid
                WHERE cust.id = ? AND ((cash.docid IS NULL AND ((cash.type <> 0 AND cash.time < ' . $totime . ')
                    OR (cash.type = 0 AND cash.value > 0 AND cash.time < ' . $totime . ')
                    OR (cash.type = 0 AND cash.time +
                        ((CASE cust.paytime WHEN -1
                            THEN
                                (CASE WHEN divisions.inv_paytime IS NULL
                                    THEN ' . $deadline . '
                                    ELSE divisions.inv_paytime
                                END)
                            ELSE cust.paytime
                        END) + ' . (is_int($expired) ? $expired : '0') . ') * 86400 < ' . $totime . ')))
                        OR (cash.docid IS NOT NULL AND ((doc.type = ? AND cash.time < ' . $totime . ')
                            OR (doc.type = ? AND cash.time < ' . $totime . ' AND tv.totalvalue >= 0)
                            OR (((doc.type = ? AND tv.totalvalue < 0)
                                OR doc.type IN (?, ?, ?)) AND doc.cdate + (doc.paytime + ' . (is_int($expired) ? $expired : '0') . ') * 86400 < ' . $totime . '))))',
                array(
                    $id,
                    DOC_CNOTE,
                    $id,
                    DOC_RECEIPT,
                    DOC_CNOTE,
                    DOC_CNOTE,
                    DOC_INVOICE,
                    DOC_INVOICE_PRO,
                    DOC_DNOTE,
                )
            );
        } else {
            return $this->db->GetOne(
                'SELECT SUM(value * currencyvalue)
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
                cash.value AS value, cash.currency, cash.currencyvalue,
                taxes.label AS tax, cash.customerid AS customerid,
                documents.comment AS documentcomment, documents.reference,
                cash.comment, docid, vusers.name AS username,
                documents.type AS doctype, documents.closed AS closed,
                documents.published, documents.senddate, documents.archived, cash.importid,
                (CASE WHEN EXISTS (SELECT 1 FROM documents d2 WHERE d2.reference = documents.id AND d2.type > 0) THEN 1 ELSE 0 END) AS referenced,
                (CASE WHEN EXISTS (SELECT 1 FROM documents d2 WHERE d2.reference = documents.id AND d2.type < 0) THEN 1 ELSE 0 END) AS documentreferenced,
                documents.cdate, documents.number, numberplans.template
            FROM cash
            LEFT JOIN vusers ON vusers.id = cash.userid
            LEFT JOIN documents ON documents.id = docid
            LEFT JOIN numberplans ON numberplans.id = documents.numberplanid
            LEFT JOIN taxes ON cash.taxid = taxes.id
            WHERE cash.customerid = ?'
            . ($totime ? ' AND time <= ' . intval($totime) : '') . ')
            UNION
            (SELECT ic.itemid AS id, d.cdate AS time, 0 AS type,
                    -ic.grossvalue AS value,
                    d.currency, d.currencyvalue, NULL AS tax, d.customerid,
                    d.comment AS documentcomment, d.reference,
                    ic.description AS comment, d.id AS docid, vusers.name AS username,
                    d.type AS doctype, d.closed AS closed,
                    d.published, d.senddate, 0 AS archived, NULL AS importid,
                    (CASE WHEN d3.reference IS NULL THEN 0 ELSE 1 END) AS referenced,
                    0 AS documentreferenced,
                    d.cdate, d.number, numberplans.template
                FROM documents d
                JOIN ' . (ConfigHelper::getConfig('database.type') == 'postgres' ? 'get_invoice_contents(' . intval($id) . ')' : 'vinvoicecontents') . ' ic ON ic.docid = d.id
                LEFT JOIN (
                    SELECT DISTINCT reference FROM documents
                ) d3 ON d3.reference = d.id
                LEFT JOIN numberplans ON numberplans.id = d.numberplanid
                LEFT JOIN vusers ON vusers.id = d.userid
                WHERE ' . (ConfigHelper::checkConfig('phpui.proforma_invoice_generates_commitment') ? '1=0 AND' : '')
                . ' d.customerid = ? AND d.type = ?'
                . ($totime ? ' AND d.cdate <= ' . intval($totime) : '') . ')
            ORDER BY time ' . $direction . ', docid, id',
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
                    $row['after'] = round($result['balance'] + ($row['value'] * $row['currencyvalue']), 2);
                    $result['balance'] += $row['value'] * $row['currencyvalue'];
                }
                $row['date'] = date('Y/m/d H:i', $row['time']);

                if (!empty($row['doctype']) && !empty($row['documentreferenced'])) {
                    if (!isset($document_manager)) {
                        $document_manager = new LMSDocumentManager($this->db, $this->auth, $this->cache, $this->syslog);
                    }
                    $row['refdocs'] = $document_manager->getDocumentReferences($row['docid']);
                }
            }

            $result['total'] = count($result['list']);
        }

        $result['sendinvoices'] = ($this->db->GetOne('SELECT 1 FROM customercontacts cc
			JOIN customeraddressview c ON c.id = cc.customerid
			WHERE c.id = ? AND invoicenotice = 1 AND cc.type & ? = ?
			LIMIT 1', array($id, CONTACT_INVOICES | CONTACT_DISABLED, CONTACT_INVOICES)) > 0);

        return $result;
    }

    public function GetCustomerShortBalanceList($customerid, $limit = 10, $order = 'DESC', $aggregate_documents = false)
    {
        $result = $this->db->GetAll('SELECT cash.comment, cash.value, cash.currency, cash.currencyvalue,
                    cash.time, cash.docid, d.type AS doctype, d.number, np.template, d.cdate
                FROM cash
                LEFT JOIN documents d ON d.id = cash.docid
                LEFT JOIN numberplans np ON np.id = d.numberplanid
                               WHERE cash.customerid = ?
                               ORDER BY cash.time ' . $order
                . (empty($limit) ? '' : ' LIMIT ' . intval($limit)), array($customerid));

        if (empty($result)) {
            return null;
        }

        if ($aggregate_documents) {
            $result['list'] = $result;
            $result['customerid'] = $customerid;
            $finance_manager = new LMSFinanceManager($this->db, $this->auth, $this->cache, $this->syslog);
            $result = $finance_manager->AggregateDocuments($result);
            $result = $result['list'];
        }

        $balance = $this->getCustomerBalance($customerid);

        if ($order == 'ASC') {
            $result = array_reverse($result);
        }

        foreach ($result as &$record) {
            $record['after'] = $balance;
            $balance -= $record['value'] * $record['currencyvalue'];
        }
        unset($record);

        if ($order == 'ASC') {
            $result = array_reverse($result);
        }

        return $result;
    }

    public function getLastNInTable($body, $customerid, $format, $aggregate_documents = false)
    {
        static $cols = null;

        if (preg_match('/%last_(?<number>[0-9]+)_in_a_table/', $body, $m)) {
            if (empty($cols)) {
                $cols = array(
                    'date' => array(
                        'length' => 0,
                        'label' => trans('Date'),
                        'align' => 'left',
                    ),
                    'liability' => array(
                        'length' => 0,
                        'label' => trans('Liability'),
                        'align' => 'right',
                    ),
                    'payment' => array(
                        'length' => 0,
                        'label' => trans('Payment'),
                        'align' => 'right',
                    ),
                    'balance' => array(
                        'length' => 0,
                        'label' => trans('Balance'),
                        'align' => 'right',
                    ),
                    'description' => array(
                        'length' => 53,
                        'label' => trans('Description'),
                        'align' => 'left',
                    ),
                );
            }

            if ($aggregate_documents) {
                $lastN = $this->GetCustomerShortBalanceList($customerid, 0, 'DESC', $aggregate_documents);
                $lastN = array_slice($lastN, 0, $m['number']);
            } else {
                $lastN = $this->GetCustomerShortBalanceList($customerid, $m['number'], 'DESC', $aggregate_documents);
            }
            if (empty($lastN)) {
                $lN = '';
            } else {
                // ok, now we are going to rise up system's load
                if ($format == 'html') {
                    $lN = '<table class="customer-last-balance-table"><thead><tr>' . PHP_EOL;
                    foreach ($cols as $col_name => $col) {
                        $lN .= '<th style="text-align: ' . $col['align'] . ';">' . $col['label'] . '</th>' . PHP_EOL;
                    }
                    $lN .= '</thead><tbody>' . PHP_EOL;
                } else {
                    $chunks = array();
                    $titles = array();
                    foreach ($cols as $col_name => $col) {
                        $cols[$col_name]['length'] = $col_length = max(mb_strlen($col['label']) + 2, 13, $col['length']);
                        $chunks[] = str_repeat('-', $col_length);
                        $titles[] = ' ' . str_pad($col['label'], $col_length - 2 + strlen($col['label']) - mb_strlen($col['label']), ' ', $col['align'] == 'right' ? STR_PAD_LEFT : STR_PAD_RIGHT) . ' ';
                    }
                    $horizontal_line = implode('+', $chunks);
                    $lN = $horizontal_line . PHP_EOL;
                    $lN .= implode('|', $titles) . PHP_EOL;
                    $lN .= $horizontal_line . PHP_EOL;
                }
                foreach ($lastN as $row_s) {
                    $cols['date']['value'] = date('Y/m/d', $row_s['time']);
                    if ($row_s['value'] < 0) {
                        $cols['liability']['value'] = moneyf($row_s['value'] * -1, $row_s['currency']);
                        $cols['payment']['value'] = '';
                    } else {
                        $cols['liability']['value'] = '';
                        $cols['payment']['value'] = moneyf($row_s['value'], $row_s['currency']);
                    }
                    $cols['balance']['value'] = moneyf($row_s['after'], Localisation::getCurrentCurrency());
                    $cols['description']['value'] = $row_s['comment'];
                    if ($format == 'html') {
                        $lN .= '<tr>' . PHP_EOL
                            . '<td>'
                                . $cols['date']['value'] . '</td>' . PHP_EOL . '
                            <td>'
                                . $cols['liability']['value'] . '</td>' . PHP_EOL . '
                            <td style="'
                                . ($cols['payment']['value'] > 0 ? ' color: green;' : ($cols['payment'] < 0 ? 'color: red;' : '')) . '">'
                                . ($cols['payment']['value'] > 0 ? '+' : '') . $cols['payment']['value'] . '</td>' . PHP_EOL . '
                            <td style="'
                                . ($cols['balance']['value'] < 0 ? 'color: red;' : '') . '">' . $cols['balance']['value'] . '</td>' . PHP_EOL . '
                            <td>'
                                . $cols['description']['value'] . '</td>' . PHP_EOL
                        . '</tr>' . PHP_EOL;
                    } else {
                        $chunks = array();
                        foreach ($cols as $col_name => $col) {
                            $chunks[] = ' ' . str_pad($col['value'], $col['length'] - 2, ' ', $col['align'] == 'right' ? STR_PAD_LEFT : STR_PAD_RIGHT) . ' ';
                        }
                        $lN .= implode('|', $chunks) . PHP_EOL;
                    }
                }
                if ($format == 'html') {
                    $lN .= '</tbody></table>' . PHP_EOL;
                } else {
                    $lN .= $horizontal_line . PHP_EOL;
                }
            }
            $body = preg_replace(
                '/%last_[0-9]+_in_a_table/',
                $lN,
                ($format == 'html' ? self::CUSTOMER_LAST_BALANCE_TABLE_STYLE : '') . $body
            );
        }

        return $body;
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
            'SELECT
                SUM(a.value) * -1 AS debtvalue,
                COUNT(*) AS debt,
                SUM(CASE WHEN a.status = ? THEN a.value ELSE 0 END) * -1 AS debtcollectionvalue
            FROM (
                SELECT c.status, b.balance AS value
                FROM customerbalances b
                LEFT JOIN customerview c ON (customerid = c.id)
                WHERE c.deleted = 0 AND b.balance < 0
            ) a',
            array(
                CSTATUS_DEBT_COLLECTION,
            )
        );

        if (is_array($tmp)) {
            $result = array_merge($result, $tmp);
        }

        return $result;
    }

    private function extractCustomerConsents($consents)
    {
        $final_consents = array();
        array_walk(
            $consents,
            function ($value, $type) use (&$final_consents) {
                global $CCONSENTS;
                if (isset($CCONSENTS[$type]) && !is_array($CCONSENTS[$type])) {
                    $final_consents[$CCONSENTS[$type]] = $type;
                } else {
                    $final_consents[$type] = $value;
                }
            }
        );
        return $final_consents;
    }

    private function compactCustomerConsents($consents)
    {
        $final_consents = array();
        array_walk(
            $consents,
            function ($value, $type) use (&$final_consents) {
                global $CCONSENTS;
                if (isset($CCONSENTS[$type]) && $CCONSENTS[$type]['type'] == 'selection') {
                    $final_consents[$value] = time();
                } else {
                    $final_consents[$type] = $value;
                }
            }
        );
        return $final_consents;
    }

    public function updateCustomerConsents($customerid, $current_consents, $new_consents)
    {
        $consents_to_remove = array_diff($current_consents, $new_consents);
        $consents_to_add = array_diff($new_consents, $current_consents);

        $userid = Auth::GetCurrentUser();

        if (!empty($consents_to_remove)) {
            $this->db->Execute(
                'DELETE FROM customerconsents WHERE customerid = ? AND type IN ?',
                array($customerid, $consents_to_remove)
            );
            if ($this->syslog) {
                foreach ($consents_to_remove as $type) {
                    $args = array(
                        SYSLOG::RES_USER => $userid,
                        SYSLOG::RES_CUST => $customerid,
                        'type' => $type,
                    );
                    $this->syslog->AddMessage(SYSLOG::RES_CUSTCONSENT, SYSLOG::OPER_DELETE, $args);
                }
            }
        }
        if (!empty($consents_to_add)) {
            $records = array();
            $now = time();
            foreach ($consents_to_add as $consent) {
                $records[] = '(' . $customerid . ',' . $consent . ',' . $now . ')';
                if ($this->syslog) {
                    $args = array(
                        SYSLOG::RES_USER => $userid,
                        SYSLOG::RES_CUST => $customerid,
                        'type' => $consent,
                        'cdate' => $now,
                    );
                    $this->syslog->AddMessage(SYSLOG::RES_CUSTCONSENT, SYSLOG::OPER_ADD, $args);
                }
            }
            if (!empty($records)) {
                $this->db->Execute('INSERT INTO customerconsents (customerid, type, cdate) VALUES ' . implode(',', $records));
            }
        }
    }

    /**
     * Adds customer
     *
     * @param array $customeradd Customer data
     * @return boolean False on failure, customer id on success
     */
    public function customerAdd($customeradd)
    {
        global $CUSTOMERFLAGS;

        $location_manager = new LMSLocationManager($this->db, $this->auth, $this->cache, $this->syslog);

        $capitalize_customer_names = ConfigHelper::checkConfig('phpui.capitalize_customer_names', true);

        $customeradd['name'] = str_replace(array('”', '„'), '"', $customeradd['name']);
        $customeradd['lastname'] = str_replace(array('”', '„'), '"', $customeradd['lastname']);

        $flags = 0;
        if (isset($customeradd['flags'])) {
            foreach ($customeradd['flags'] as $flag) {
                if (isset($CUSTOMERFLAGS[$flag])) {
                    $flags |= $flag;
                }
            }
        }

        $args = array(
            'name'           => $customeradd['name'],
            'lastname'       => $customeradd['lastname'],
            'altname'        => empty($customeradd['altname']) ? null : $customeradd['altname'],
            'type'           => empty($customeradd['type']) ? 0 : 1,
            'ten'            => $customeradd['ten'],
            'ssn'            => $customeradd['ssn'] ?? '',
            'status'         => $customeradd['status'],
            SYSLOG::RES_USER => Auth::GetCurrentUser(),
            'info'           => Utils::removeInsecureHtml($customeradd['info']),
            'notes'          => Utils::removeInsecureHtml($customeradd['notes']),
            'message'        => Utils::removeInsecureHtml($customeradd['message']),
            'documentmemo'   => empty($customeradd['documentmemo']) ? null : Utils::removeInsecureHtml($customeradd['documentmemo']),
            'pin'            => $customeradd['pin'],
            'regon'          => $customeradd['regon'],
            'rbename'        => $customeradd['rbename'],
            'rbe'            => $customeradd['rbe'],
            'ict'            => $customeradd['ict'] ?? 0,
            'icn'            => $customeradd['icn'] ?? '',
            'icexpires'      => isset($customeradd['icexpires']) ? (intval($customeradd['icexpires']) > 0
                ? strtotime('tomorrow', intval($customeradd['icexpires'])) - 1
                : ($customeradd['icexpires'] === '-1' ? 0 : null)) : null,
            'cutoffstop'     => $customeradd['cutoffstop'],
            SYSLOG::RES_DIV  => empty($customeradd['divisionid']) ? null : $customeradd['divisionid'],
            'paytime'        => $customeradd['paytime'],
            'paytype'        => !empty($customeradd['paytype']) ? $customeradd['paytype'] : null,
            'flags'          => $flags,
        );

        $reuse_customer_id = ConfigHelper::checkConfig('phpui.reuse_customer_id');

        if ($reuse_customer_id) {
            $this->db->BeginTrans();
            $this->db->LockTables('customers');

            $cids = $this->db->GetCol('SELECT id FROM customers ORDER BY id');
            $id = 0;
            if (!empty($cids)) {
                foreach ($cids as $cid) {
                    if ($cid - $id > 1) {
                        break;
                    }
                    $id = $cid;
                }
            }
            $id++;

            $args[SYSLOG::RES_CUST] = $id;
        }

        $result = $this->db->Execute(
            'INSERT INTO customers (name, lastname, altname, type,
            ten, ssn, status, creationdate,
            creatorid, info, notes, message, documentmemo, pin, pinlastchange, regon, rbename, rbe,
            ict, icn, icexpires, cutoffstop, divisionid, paytime, paytype, flags' . ($reuse_customer_id ? ', id' : ''). ')
            VALUES (?, ' . ($capitalize_customer_names ? 'UPPER(?)' : '?') . ', ?, ?, ?, ?, ?, ?NOW?,
                    ?, ?, ?, ?, ?, ?, ?NOW?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?' . ($reuse_customer_id ? ', ?' : '') . ')',
            array_values($args)
        );

        if ($reuse_customer_id) {
            $this->db->UnLockTables();
            $this->db->CommitTrans();
        }

        if ($result) {
            if ($reuse_customer_id) {
                switch (ConfigHelper::getConfig('database.type')) {
                    case 'postgres':
                        $this->db->Execute('SELECT setval(\'customers_id_seq\', (SELECT MAX(id) FROM customers))');
                        break;
                }
            } else {
                $id = $this->db->GetLastInsertID('customers');
            }

            // INSERT ADDRESSES
            foreach ($customeradd['addresses'] as $v) {
                $location_manager->InsertCustomerAddress($id, $v);

                // update country states
                if ($v['location_zip'] && $v['location_state'] && !isset($v['teryt'])) {
                    $location_manager->UpdateCountryState($v['location_zip'], $v['location_state']);
                }
            }

            if ($this->syslog) {
                $args[SYSLOG::RES_CUST] = $id;
                unset($args[SYSLOG::RES_USER]);
                $this->syslog->AddMessage(SYSLOG::RES_CUST, SYSLOG::OPER_ADD, $args);
            }

            // update customer consents
            $this->updateCustomerConsents(
                $id,
                array(),
                array_keys($this->compactCustomerConsents($customeradd['consents']))
            );

            if (empty($customeradd['extids'])) {
                $customeradd['extids'] = array();
            } else {
                $customeradd['extids'] = array_filter($customeradd['extids'], function ($customerextid) {
                    return strlen($customerextid['extid']) > 0;
                });
            }
            $this->updateCustomerExternalIDs(
                $id,
                $customeradd['extids']
            );

            if (ConfigHelper::checkConfig('phpui.add_customer_group_required')) {
                $args = array(
                    'customerid' => $id,
                );
                if (!isset($customeradd['group'])) {
                    $customeradd['group'] = array();
                }
                if (!is_array($customeradd['group'])) {
                    $customeradd['group'] = array($customeradd['group']);
                }
                $customeradd['group'] = Utils::filterIntegers($customeradd['group']);
                if (!empty($customeradd['group'])) {
                    foreach ($customeradd['group'] as $groupid) {
                        $args[SYSLOG::RES_CUSTGROUP] = $groupid;

                        $res = $this->db->Execute('INSERT INTO customerassignments (customerid, customergroupid) VALUES (?, ?)', array_values($args));

                        if ($this->syslog && $res) {
                            $this->syslog->AddMessage(SYSLOG::RES_CUSTASSIGN, SYSLOG::OPER_ADD, $args);
                        }
                    }
                }
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
     * @param string $statesqlskey Logical conjunction used for state field
     * @param boolean $network With or without network params
     * @param int $customergroup Customer group
     * @param boolean $customergroupnegation negate customer group assignments
     * @param string $customergroupsqlskey Logical conjunction used for customergroup field
     * @param array $search Search parameters
     * @param int $time Timestamp
     * @param string $sqlskey Logical conjunction
     * @param int $nodegroup Node group
     * @param boolean $nodegroupnegation negate node group assignments
     * @param int $division Division id
     * @param array $document Document parameters
     * @param int $days Days after expiration
     * @param int $limit Limit
     * @param int $offset Offset
     * @param boolean $count Count flag
     * @return array Customer list
     */
    public function getCustomerList($params = array())
    {
        extract($params);

        if (empty($order)) {
            $order = 'customername,asc';
        }

        if (empty($sqlskey)) {
            $sqlskey = 'AND';
        }

        if (!isset($count)) {
            $count = false;
        }

        if (!isset($time)) {
            $time = null;
        }

        if (!isset($days)) {
            $days = 0;
        }

        [$order, $direction] = sscanf($order, '%[^,],%s');

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
            case 'karma':
                $sqlord = ' ORDER BY karma';
                break;
            default:
                $sqlord = ' ORDER BY customername';
                break;
        }

        if (!isset($statesqlskey)) {
            $statesqlskey = 'AND';
        }

        if (!isset($customergroupsqlskey)) {
            $customergroupsqlskey = 'AND';
        }

        if (!isset($customergroupnegation)) {
            $customergroupnegation = false;
        }

        if (!isset($nodegroupnegation)) {
            $nodegroupnegation = false;
        }

        if (!is_array($state) && !empty($state)) {
            $state = array($state);
        }

        if (!isset($flagsqlskey)) {
            $flagsqlskey = 'AND';
        }

        if (!empty($flags) && !is_array($flags)) {
            $flags = array($flags);
        }

        $customer_statuses = array();
        $state_conditions = array();

        $consent_condition = '';
        if (!empty($consents)) {
            $consent_conditions = array();
            foreach ($consents as $consentid => $consent) {
                if ($consent >= 0) {
                    $consent_conditions[] = ($consent == 1 ? '' : 'NOT ')
                        . 'EXISTS (SELECT customerid FROM customerconsents cc
                        WHERE type = ' . intval($consentid) . ' AND customerid = c.id)';
                }
            }
            if (!empty($consent_conditions)) {
                $consent_condition = '(' . implode(' AND ', $consent_conditions) . ')';
            }
        }

        if (!isset($state) || !is_array($state)) {
            $state = array();
        }

        $contracts = 0;
        $overduereceivables = 0;
        $archived_document_condition = '';

        $ignore_deleted_customers = ConfigHelper::checkConfig('phpui.ignore_deleted_customers');

        foreach ($state as $state_item) {
            switch ($state_item) {
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
                    $state_conditions[] = 'c.deleted = 1';
                    $ignore_deleted_customers = false;
                    break;
                case 51:
                    $state_conditions[] = '(s.ownerid IS NOT null AND s.account > s.acsum)';
                    break;
                case 52:
                    $state_conditions[] = 'b.balance < 0';
                    break;
                case 53:
                    $state_conditions[] = 's.online = 1';
                    break;
                case 54:
                    $state_conditions[] = 'NOT EXISTS (SELECT 1 FROM vcustomerassignments a
                    WHERE c.id = a.customerid)';
                    break;
                case 55:
                    $state_conditions[] = 'NOT EXISTS (SELECT 1 FROM assignments a
                    WHERE a.customerid = c.id
                    	AND a.commited = 1
                        AND datefrom <= ?NOW?
                        AND (dateto >= ?NOW? OR dateto = 0)
                        AND (tariffid IS NOT NULL OR liabilityid IS NOT NULL))';
                    break;
                case 56:
                    $state_conditions[] = 'EXISTS (SELECT 1 FROM assignments a
                    WHERE a.customerid = c.id AND (
                        (tariffid IS NULL AND liabilityid IS NULL
                            AND datefrom <= ?NOW?
                            AND (dateto >= ?NOW? OR dateto = 0))
                        OR (datefrom <= ?NOW?
                            AND (dateto >= ?NOW? OR dateto = 0)
                            AND suspended = 1 AND commited = 1)
                        ))';
                    break;
                case 57:
                    $state_conditions[] = 'b.balance < -t.value';
                    break;
                case 58:
                    $state_conditions[] = 'b.balance < -t.value * 2';
                    break;
                case 59:
                case 60:
                case 61:
                case 76:
                case 77:
                case 78:
                    $contracts_expiration_type = ConfigHelper::getConfig('contracts.expiration_type', 'documents');
                    if ($state_item >= 76) {
                        $contracts = $state_item - 75;
                        if ($contracts_expiration_type == 'documents') {
                            $archived_document_condition = ' AND d.archived = 0';
                        }
                    } else {
                        $contracts = $state_item - 58;
                    }
                    $contracts_days = intval(ConfigHelper::getConfig('contracts.contracts_days'));
                    if ($contracts == 1) {
                        if ($contracts_expiration_type == 'documents') {
                            $state_conditions[] = 'd.customerid IS NULL';
                        } else {
                            $state_conditions[] = 'ass.customerid IS NULL';
                        }
                    }
                    break;
                case 85:
                    $contracts = 4;
                    if ($state_item == 85) {
                        $archived_document_condition = ' AND d.archived = 0';
                    }
                    break;
                case 62:
                    $state_conditions[] = 'c.einvoice = 1';
                    break;
                case 63:
                    $state_conditions[] = 'EXISTS (SELECT 1 FROM nodes WHERE ownerid = c.id AND access = 1)';
                    break;
                case 64:
                    $state_conditions[] = 'EXISTS (SELECT 1 FROM nodes WHERE ownerid = c.id)';
                    break;
                case 65:
                    $state_conditions[] = 'NOT EXISTS (SELECT 1 FROM nodes WHERE ownerid = c.id)';
                    break;
                case 66:
                    $state_conditions[] = 'EXISTS (SELECT 1 FROM assignments WHERE invoice = 0 AND commited = 1 AND customerid = c.id)';
                    break;
                case 67:
                    $state_conditions[] = 'c.building IS NULL';
                    break;
                case 68:
                    $state_conditions[] = 'c.zip IS NULL';
                    break;
                case 69:
                    $state_conditions[] = 'c.city IS NULL';
                    break;
                case 70:
                    $state_conditions[] = 'c.id IN (SELECT DISTINCT ca.customer_id
                        FROM customer_addresses ca
                        JOIN addresses a ON a.id = ca.address_id
                        WHERE a.city_id IS NULL)';
                    break;
                case 71:
                    $overduereceivables = 1;
                    $state_conditions[] = 'b2.balance < 0';
                    break;
                case 72:
                    $state_conditions[] = 'c.deleted = 0';
                    break;
                case 73:
                    $state_conditions[] = 'EXISTS (SELECT 1 FROM documents WHERE customerid = c.id AND type < 0 AND archived = 0)';
                    break;
                case 74:
                    $state_conditions[] = 'EXISTS (SELECT 1
                        FROM customer_addresses ca
                        JOIN addresses a ON a.id = ca.address_id
                        WHERE a.zip IS NULL AND ca.type <> ' . BILLING_ADDRESS . ' AND ca.customer_id = c.id)';
                    break;
                case 75:
                    $state_conditions[] = 'EXISTS (SELECT 1 FROM assignments WHERE commited = 1 AND (vdiscount > 0 OR pdiscount > 0) AND customerid = c.id)';
                    break;
                case 79:
                    $state_conditions[] = 'EXISTS (SELECT 1 FROM assignments WHERE commited = 1 AND tariffid IS NULL AND datefrom < ?NOW? AND (dateto = 0 OR dateto > ?NOW?) AND customerid = c.id)';
                    break;
                case 80:
                    $state_conditions[] = 'EXISTS (SELECT 1 FROM assignments WHERE commited = 1 AND tariffid IS NULL AND customerid = c.id)';
                    break;
                case 81:
                    $state_conditions[] = 'NOT EXISTS (SELECT 1 FROM customer_addresses WHERE customer_addresses.customer_id = c.id
                        AND customer_addresses.type IN (' . LOCATION_ADDRESS . ',' . DEFAULT_LOCATION_ADDRESS . '))';
                    break;
                case 82:
                    $state_conditions[] = 'b.balance <= -t.value';
                    break;
                case 83:
                    $state_conditions[] = 'b.balance <= -t.value * 2';
                    break;
                case 84:
                    $state_conditions[] = 'EXISTS (SELECT 1 FROM nodes WHERE ownerid = c.id)
                        AND NOT EXISTS (SELECT 1 FROM nodes
                        JOIN netdevices ON nodes.netdev = netdevices.id
                        WHERE nodes.ownerid = c.id)';
                    break;
                default:
                    if ($state_item > 0 && $state_item < 50 && intval($state_item)) {
                        $customer_statuses[] = intval($state_item);
                    }
                    break;
            }
        }
        if (!empty($customer_statuses)) {
            $state_conditions[] = '((c.status = ' . implode(' ' . $statesqlskey . ' c.status = ', $customer_statuses) . ')'
                . ($ignore_deleted_customers ? ' AND c.deleted = 0' : '') . ')';
            $ignore_deleted_customers = false;
        }

        $flagmask = 0;
        if (!empty($flags)) {
            foreach ($flags as $flag) {
                $flagmask |= intval($flag);
            }
        }
        if ($flagmask) {
            $flag_condition = '(c.flags & ' . $flagmask . ($flagsqlskey == 'AND' ? ' = ' . $flagmask : ' > 0') . ')';
        } else {
            $flag_condition = '';
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
            case -1:
                if (!empty($search['tarifftype'])) {
                    $assignment = 'SELECT DISTINCT(a.customerid)
                        FROM assignments a
                        LEFT JOIN tariffs t ON t.id = a.tariffid
                        LEFT JOIN liabilities l ON (l.id = a.liabilityid AND a.period <> 0)
                        WHERE a.suspended = 0
                        AND a.commited = 1
                        AND a.dateto = 0
                        AND (t.type = ' . intval($search['tarifftype']) . ' OR l.type = ' . intval($search['tarifftype']) . ')';
                } else {
                    $assignment = 'SELECT DISTINCT(a.customerid) FROM assignments a WHERE a.suspended = 0 AND a.commited = 1 AND a.dateto = 0';
                }
                break;
            case -2:
                $assignment = 'SELECT DISTINCT(a.customerid) FROM assignments a WHERE a.suspended = 0 AND a.commited = 1 '
                    . 'AND (a.dateto = 0 OR a.dateto > ?NOW?) AND ((a.at + 86400) > ?NOW? OR a.period <> 0)';
                break;
            case -3:
                $assignment = 'SELECT DISTINCT(a.customerid) FROM assignments a WHERE a.invoice = ' . DOC_INVOICE
                    . ' AND a.suspended = 0 AND a.commited = 1
                    AND (a.dateto = 0 OR a.dateto > ?NOW?) AND (a.period <> ' . DISPOSABLE . ' OR (a.at + 86400) > ?NOW?)';
                break;
            case -4:
                $assignment = 'SELECT DISTINCT(a.customerid) FROM assignments a WHERE a.suspended <> 0';
                break;
            case -5:
                $assignment = 'SELECT DISTINCT(a.customerid) FROM assignments a WHERE a.invoice = ' . DOC_INVOICE_PRO
                    . ' AND a.suspended = 0 AND a.commited = 1
                    AND (a.dateto = 0 OR a.dateto > ?NOW?) AND ((a.at + 86400) > ?NOW? or a.period != 0)';
                break;
            case -6:
                $assignment = 'SELECT DISTINCT(a.customerid) FROM assignments a
                    LEFT JOIN documents d ON d.id = a.docid
                    WHERE a.suspended = 0 AND a.commited = 1
                        AND (a.dateto = 0 OR a.dateto > ?NOW?) AND a.period <> 0
                    GROUP BY a.customerid, d.id
                    HAVING MIN(a.datefrom) > ?NOW?';
                break;
            case -7:
                $assignment = 'SELECT DISTINCT(a.customerid)
                    FROM assignments a
                    WHERE a.suspended = 0 AND a.commited = 1
                        AND (a.dateto = 0 OR a.dateto > ?NOW?) AND ((a.at + 86400) > ?NOW? OR a.period <> 0)
                        AND NOT EXISTS (SELECT 1 FROM nodeassignments WHERE assignmentid = a.id)';
                break;
            case -8:
                $assignment = 'SELECT DISTINCT(a.customerid)
                    FROM assignments a
                    LEFT JOIN documents d ON d.id = a.docid
                    WHERE a.suspended = 0 AND a.commited = 1
                        AND (a.dateto = 0 OR a.dateto > ?NOW?) AND a.period <> 0
                        AND NOT EXISTS (SELECT 1 FROM nodeassignments WHERE assignmentid = a.id)
                    GROUP BY a.customerid, d.id
                    HAVING MIN(a.datefrom) > ?NOW?';
                break;
            case -9:
                $assignment = 'SELECT DISTINCT(a.customerid)
                    FROM assignments a
                    WHERE a.commited = 1 AND tariffid IS NULL AND liabilityid IS NULL
                        AND datefrom <= ?NOW?
                        AND (dateto >= ?NOW? OR dateto = 0)';
                break;
            case -10:
                $assignment = 'SELECT DISTINCT(a.customerid) FROM assignments a WHERE a.suspended = 0 AND a.commited = 1 AND a.datefrom = 0';
                break;
            case -11:
                $assignment = 'SELECT DISTINCT(a.customerid) FROM assignments a WHERE a.commited = 1';
                break;
            case -12:
                $assignment = 'SELECT DISTINCT(a.customerid) FROM assignments a WHERE a.commited = 0';
                break;
            case -13:
                $assignment = 'SELECT DISTINCT(a.customerid) FROM assignments a WHERE a.invoice = 0
                    AND a.suspended = 0 AND a.commited = 1
                    AND (a.dateto = 0 OR a.dateto > ?NOW?) AND (a.period <> ' . DISPOSABLE . ' OR (a.at + 86400) > ?NOW?)';
                break;
            default:
                if ($as > 0) {
                    $assignment = 'SELECT DISTINCT(a.customerid) FROM assignments a WHERE
                        a.suspended = 0 AND a.commited = 1 AND a.dateto > ' . time() . ' AND a.dateto <= ' . (time() + ($as * 86400))
                        . ' AND NOT EXISTS (SELECT 1 FROM assignments aa WHERE aa.customerid = a.customerid AND aa.datefrom > a.dateto LIMIT 1)';
                } else {
                    $assignment = null;
                }
                break;
        }

        if (isset($search['assignment'])) {
            if (is_array($search['assignment'])) {
                $assignment_properties = $search['assignment'];
            }
            unset($search['assignment']);
        }

        if (isset($assignment) && $assignment_properties) {
            $where_assignments = array();
            foreach ($assignment_properties as $key => $value) {
                switch ($key) {
                    case 'at':
                        if (strlen($value)) {
                            $where_assignments[] = 'a.at = ' . intval($value);
                        }
                        break;
                    case 'period':
                        if (is_numeric($value)) {
                            $where_assignments[] = 'a.period = ' . intval($value);
                        }
                        break;
                    case 'backwardperiod':
                        $where_assignments[] = 'a.backwardperiod = ' . intval($value);
                        break;
                }
            }
            if (!empty($where_assignments)) {
                $assignment .= ' AND ' . (implode(' AND ', $where_assignments));
            }
        }

        if (isset($network) && $network) {
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

        if (!empty($search) && is_array($search)) {
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
                        case 'full_address':
                            $searchargs[] = "UPPER(c.full_address) ?LIKE? UPPER(" . $this->db->Escape("%$value%") . ")";
                            break;
                        case 'post_name':
                            $searchargs[] = "UPPER(c.post_name) ?LIKE? UPPER(" . $this->db->Escape("%$value%") . ")";
                            break;
                        case 'post_full_address':
                            $searchargs[] = "UPPER(c.post_full_address) ?LIKE? UPPER(" . $this->db->Escape("%$value%") . ")";
                            break;
                        case 'location_name':
                            $searchargs[] = "EXISTS (SELECT 1 FROM customer_addresses ca2
                                JOIN vaddresses va ON va.id = ca2.address_id
                                WHERE ca2.customer_id = c.id AND ca2.type IN (" . DEFAULT_LOCATION_ADDRESS . ',' . LOCATION_ADDRESS . ")
                                    AND UPPER(va.name) ?LIKE? UPPER(" . $this->db->Escape("%$value%") . "))";
                            break;
                        case 'location_full_address':
                            $searchargs[] = "EXISTS (SELECT 1 FROM customer_addresses ca2
                                JOIN vaddresses va ON va.id = ca2.address_id
                                WHERE ca2.customer_id = c.id AND ca2.type IN (" . DEFAULT_LOCATION_ADDRESS . ',' . LOCATION_ADDRESS . ")
                                    AND UPPER(va.location) ?LIKE? UPPER(" . $this->db->Escape("%$value%") . "))";
                            break;
                        case 'customername':
                            if (!isset($search['customernamestartingwith'])) {
                                // UPPER here is a workaround for postgresql ILIKE bug
                                $searchargs[] = $this->db->Concat('UPPER(c.lastname)', "' '", 'UPPER(c.name)') . ' ?LIKE? UPPER(' . $this->db->Escape("%$value%") . ')';
                            }
                            break;
                        case 'customernamestartingwith':
                            if ($search['customername'] != '') {
                                $searchargs[] = $this->db->Concat('UPPER(c.lastname)', "' '", 'UPPER(c.name)') . ' ?LIKE? UPPER(' . $this->db->Escape($search['customername'] . '%') . ')';
                            }
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
                            $state_conditions[] = 'c.deleted = 1';
                            break;
                        case 'deletedto':
                            if (!isset($searchargs['deletedfrom'])) {
                                $searchargs[] = 'moddate <= ' . intval($value);
                            }
                            $state_conditions[] = 'c.deleted = 1';
                            break;
                        case 'cutoffstopfrom':
                            if ($search['cutoffstopto']) {
                                $searchargs['cutoffstopfrom'] = '(cutoffstop >= ' . intval($value)
                                    . ' AND cutoffstop <= ' . intval($search['cutoffstopto']) . ')';
                                unset($search['cutoffstopto']);
                            } else {
                                $searchargs[] = 'cutoffstop >= ' . intval($value);
                            }
                            break;
                        case 'cutoffstopto':
                            if (!isset($searchargs['cutoffstopfrom'])) {
                                $searchargs[] = 'cutoffstop <= ' . intval($value);
                            }
                            break;
                        case 'type':
                            $searchargs[] = 'type = ' . intval($value);
                            break;
                        case 'ict':
                            if (!empty($value)) {
                                $searchargs[] = 'ict = ' . intval($value);
                            }
                            break;
                        case 'linktype':
                            $searchargs[] = '(EXISTS (
                                    SELECT 1 FROM vnodes
                                    JOIN netdevices ON netdevices.id = vnodes.netdev
                                    WHERE vnodes.ownerid = c.id
                                        AND vnodes.linktype = ' . intval($value) . '
                                        AND netdevices.ownerid IS NULL
                                ) OR EXISTS (
                                    SELECT 1 FROM netdevices nd1
                                    JOIN netlinks ON netlinks.src = nd1.id OR netlinks.dst = nd1.id
                                    JOIN netdevices nd2 ON nd2.id = netlinks.src OR nd2.id = netlinks.dst
                                    WHERE nd1.ownerid = c.id
                                        AND nd2.ownerid IS NULL
                                        AND ((netlinks.src = nd1.id AND netlinks.dst = nd2.id) OR (netlinks.dst = nd1.id AND netlinks.src = nd2.id))
                                        AND netlinks.type = ' . intval($value) . '
                                ))';
                            break;
                        case 'linktechnology':
                            $searchargs[] = '(EXISTS (
                                    SELECT 1 FROM vnodes
                                    JOIN netdevices ON netdevices.id = vnodes.netdev
                                    WHERE vnodes.ownerid = c.id
                                        AND vnodes.linktechnology ' . (empty($value) ? 'IS NULL' : '= ' . intval($value)) . '
                                        AND netdevices.ownerid IS NULL
                                ) OR EXISTS (
                                    SELECT 1 FROM netdevices nd1
                                    JOIN netlinks ON netlinks.src = nd1.id OR netlinks.dst = nd1.id
                                    JOIN netdevices nd2 ON nd2.id = netlinks.src OR nd2.id = netlinks.dst
                                    WHERE nd1.ownerid = c.id
                                        AND nd2.ownerid IS NULL
                                        AND ((netlinks.src = nd1.id AND netlinks.dst = nd2.id) OR (netlinks.dst = nd1.id AND netlinks.src = nd2.id))
                                        AND netlinks.technology ' . (empty($value) ? 'IS NULL' : '= ' . intval($value)) . '
                                ))';
                            break;
                        case 'linkspeed':
                            $searchargs[] = '(EXISTS (
                                    SELECT 1 FROM vnodes
                                    JOIN netdevices ON netdevices.id = vnodes.netdev
                                    WHERE vnodes.ownerid = c.id
                                        AND vnodes.linkspeed = ' . intval($value) . '
                                        AND netdevices.ownerid IS NULL
                                ) OR EXISTS (
                                    SELECT 1 FROM netdevices nd1
                                    JOIN netlinks ON netlinks.src = nd1.id OR netlinks.dst = nd1.id
                                    JOIN netdevices nd2 ON nd2.id = netlinks.src OR nd2.id = netlinks.dst
                                    WHERE nd1.ownerid = c.id
                                        AND nd2.ownerid IS NULL
                                        AND ((netlinks.src = nd1.id AND netlinks.dst = nd2.id) OR (netlinks.dst = nd1.id AND netlinks.src = nd2.id))
                                        AND netlinks.speed = ' . intval($value) . '
                                ))';
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
                        case 'balance_date':
                        case 'balance_days':
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
                                $searchargs[] = 'b.balance' . ($balance_relation == -1 ? '<=' : '>=') . ' ' . str_replace(',', '.', floatval($value));
                            }
                            break;
                        case 'ten':
                            if ($value == '*') {
                                $searchargs[] = "ten <> ''";
                            } else {
                                $searchargs[] = "REPLACE(REPLACE(ten, '-', ''), ' ', '') ?LIKE? " . $this->db->Escape('%' . preg_replace('/[\- ]/', '', $value) . '%');
                            }
                            break;
                        case 'karma':
                            if (intval($value)) {
                                $searchargs[] = 'c.karma ' . ($value > 0 ? '>' : '<') . '= ' . $value;
                            }
                            break;
                        default:
                            $searchargs[] = "$key ?LIKE? " . $this->db->Escape("%$value%");
                    }
                }
            }
        }

        if (!empty($document)) {
            if (!empty($document['type'])) {
                $doctype = Utils::filterIntegers($document['type']);
            }
            if (!empty($doctype)) {
                $datefrom = false;
                if (!empty($document['datefrom'])) {
                    $datefrom = strtotime($document['datefrom']);
                }
                $dateto = false;
                if (!empty($document['dateto'])) {
                    $dateto = strtotime($document['dateto']);
                    if ($dateto !== false) {
                        $dateto = strtotime('tomorrow', $dateto);
                    }
                }
                switch ($document['dateselection']) {
                    case 'creationdate':
                        $documentfield = 'documents.cdate';
                        break;
                    case 'confirmationdate':
                        $documentfield = 'documents.sdate';
                        break;
                    case 'archivizationdate':
                        $documentfield = 'documents.adate';
                        break;
                    case 'fromdate':
                        $documentfield = 'documentcontents.fromdate';
                        break;
                    case 'todate':
                    default:
                        $documentfield = 'documentcontents.todate';
                        break;
                }
                $searchargs[] = 'EXISTS (SELECT 1 FROM documents JOIN documentcontents ON documentcontents.docid = documents.id WHERE documents.customerid = c.id'
                    . (empty($doctype) ? '' : ' AND documents.type IN (' . implode(', ', $doctype) . ')')
                    . ($datefrom === false ? '' : ' AND ' . $documentfield . ' >= ' . $datefrom)
                    . ($dateto === false ? '' : ' AND ' . $documentfield . ' < ' . $dateto)
                    . ')';
            }
        }

        if (isset($searchargs)) {
            $sqlsarg = implode(' ' . $sqlskey . ' ', $searchargs);
        }

        $suspension_percentage = f_round(ConfigHelper::getConfig('payments.suspension_percentage', ConfigHelper::getConfig('finances.suspension_percentage', 0)));

        $sql = '';

        if ($count) {
            $sql .= 'SELECT COUNT(c.id) AS total,
            	SUM(CASE WHEN b.balance > 0 THEN b.balance ELSE 0 END) AS balanceover,
            	SUM(CASE WHEN b.balance < 0 THEN b.balance ELSE 0 END) AS balancebelow ';
        } else {
            $capitalize_customer_names = ConfigHelper::checkConfig('phpui.capitalize_customer_names', true);
            $sql .= 'SELECT c.id AS id, c.lastname, c.name, ' . $this->db->Concat($capitalize_customer_names ? 'UPPER(lastname)' : 'lastname', "' '", 'c.name') . ' AS customername,
                c.karma, c.type, c.deleted,
                status, full_address, post_full_address, c.address, c.zip, c.city, countryid, countries.name AS country, cc.email, ccp.phone, ten, ssn, c.info AS info,
                extid, message, c.divisionid, c.paytime AS paytime, COALESCE(b.balance, 0) AS balance,
                COALESCE(t.value, 0) AS tariffvalue, s.account, s.warncount, s.online,
                (CASE WHEN s.account = s.acsum THEN 1
                    WHEN s.acsum > 0 THEN 2 ELSE 0 END) AS nodeac,
                (CASE WHEN s.warncount = s.warnsum THEN 1
                    WHEN s.warnsum > 0 THEN 2 ELSE 0 END) AS nodewarn ';
        }

        $sql .= 'FROM customerview c
            ' . ($overduereceivables ? '
            LEFT JOIN (
                SELECT cash.customerid, SUM(value * cash.currencyvalue) AS balance FROM cash
                LEFT JOIN customers ON customers.id = cash.customerid
                LEFT JOIN divisions ON divisions.id = customers.divisionid
                LEFT JOIN documents d ON d.id = cash.docid
                LEFT JOIN (
                    SELECT SUM(value * cash.currencyvalue) AS totalvalue, docid FROM cash
                    JOIN documents ON documents.id = cash.docid
                    WHERE documents.type = ' . DOC_CNOTE . '
                    GROUP BY docid
                ) tv ON tv.docid = cash.docid
                WHERE (cash.docid IS NULL AND ((cash.type <> 0 AND cash.time < ' . ($time ?: time()) . ')
                    OR (cash.type = 0 AND cash.value > 0 AND cash.time < ' . ($time ?: time()) . ')
                    OR (cash.type = 0 AND cash.time + ((CASE customers.paytime WHEN -1 THEN
                        (CASE WHEN divisions.inv_paytime IS NULL THEN '
                            . ConfigHelper::getConfig('payments.deadline', ConfigHelper::getConfig('invoices.paytime', 0))
                        . ' ELSE divisions.inv_paytime END) ELSE customers.paytime END)' . ($days > 0 ? ' + ' . $days : '') . ') * 86400 < ' . ($time ?: time()) . ')))
                    OR (cash.docid IS NOT NULL AND ((d.type = ' . DOC_RECEIPT . ' AND cash.time < ' . ($time ?: time()) . ')
                        OR (d.type = ' . DOC_CNOTE . ' AND cash.time < ' . ($time ?: time()) . ' AND tv.totalvalue >= 0)
                        OR (((d.type = ' . DOC_CNOTE . ' AND tv.totalvalue < 0)
                            OR d.type IN (' . DOC_INVOICE . ',' . DOC_DNOTE . ')) AND d.cdate + (d.paytime' . ($days > 0 ? ' + ' . $days : '') . ')  * 86400 < ' . ($time ?: time()) . ')))
                GROUP BY cash.customerid
            ) b2 ON b2.customerid = c.id ' : '')
            . (!empty($customergroup) ? 'LEFT JOIN (SELECT vcustomerassignments.customerid, COUNT(*) AS gcount
            	FROM vcustomerassignments '
                    . (is_array($customergroup) || $customergroup > 0 ? ' WHERE customergroupid IN ('
                        . (is_array($customergroup) ? implode(',', Utils::filterIntegers($customergroup)) : intval($customergroup)) . ')' : '') . '
            		GROUP BY vcustomerassignments.customerid) ca ON ca.customerid = c.id ' : '')
            . (!empty($nodegroup) ? 'LEFT JOIN (SELECT nodes.ownerid AS customerid, COUNT(*) AS gcount
                FROM nodegroupassignments
                JOIN nodes ON nodes.id = nodeid'
                . (is_array($nodegroup) || $nodegroup > 0 ? ' WHERE nodegroupid IN ('
                    . (is_array($nodegroup) ? implode(',', Utils::filterIntegers($nodegroup)) : intval($nodegroup)) . ')' : '') . '
                GROUP BY ownerid) na ON na.customerid = c.id ' : '')
            . ($count ? '' : '
                LEFT JOIN (SELECT customerid, (' . $this->db->GroupConcat('contact') . ') AS email
                FROM customercontacts WHERE (type & ' . CONTACT_EMAIL .' > 0) GROUP BY customerid) cc ON cc.customerid = c.id
                LEFT JOIN (SELECT customerid, (' . $this->db->GroupConcat('contact') . ') AS phone
                FROM customercontacts WHERE (type & ' . (CONTACT_MOBILE | CONTACT_LANDLINE) .' > 0) GROUP BY customerid) ccp ON ccp.customerid = c.id
                LEFT JOIN countries ON (c.countryid = countries.id) ')
            . (isset($time) && $time ?
                'LEFT JOIN (SELECT SUM(value * currencyvalue) AS balance, customerid FROM cash
                WHERE time < ' . $time . ' GROUP BY customerid) b ON b.customerid = c.id'
                : 'LEFT JOIN customerbalances b ON b.customerid = c.id')
            . '
            LEFT JOIN (
                SELECT a.customerid,
                    SUM(
                        (
                            CASE a.suspended
                                WHEN 0 THEN (((100 - a.pdiscount) * (CASE WHEN t.value IS null THEN l.value ELSE t.value END) / 100) - a.vdiscount)
                                ELSE ((((100 - a.pdiscount) * (CASE WHEN t.value IS null THEN l.value ELSE t.value END) / 100) - a.vdiscount) * ' . $suspension_percentage . ' / 100)
                            END
                        ) * (
                            CASE WHEN a.period = ' . DISPOSABLE . ' THEN 0
                            ELSE (
                                CASE WHEN a.period <> ' . DISPOSABLE . ' AND t.period > 0 AND t.period <> a.period THEN (
                                        CASE t.period
                                            WHEN ' . YEARLY . ' THEN 1/12.0
                                            WHEN ' . HALFYEARLY . ' THEN 1/6.0
                                            WHEN ' . QUARTERLY . ' THEN 1/3.0
                                            ELSE 1
                                        END
                                    ) ELSE (
                                        CASE a.period
                                            WHEN ' . YEARLY . ' THEN 1/12.0
                                            WHEN ' . HALFYEARLY . ' THEN 1/6.0
                                            WHEN ' . QUARTERLY . ' THEN 1/3.0
                                            WHEN ' . WEEKLY . ' THEN 4.0
                                            WHEN ' . DAILY . ' THEN 30.0
                                            ELSE 1
                                        END
                                    )
                                END
                            )
                            END
                        ) * a.count
                    ) AS value
                    FROM assignments a
                    LEFT JOIN tariffs t ON (t.id = a.tariffid)
                    LEFT JOIN liabilities l ON (l.id = a.liabilityid AND a.period <> ' . DISPOSABLE . ')
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
							WHERE d.type IN (' . DOC_CONTRACT . ',' . DOC_ANNEX . ')'
                            . $archived_document_condition
                            . ' GROUP BY d.customerid
						) d ON d.customerid = c.id' :
                        'LEFT JOIN (
							SELECT customerid
							FROM assignments
							WHERE dateto > 0
							GROUP BY customerid
							HAVING MAX(dateto) < ?NOW?
						) ass ON ass.customerid = c.id') :
                ($contracts == 2 ?
                    ($contracts_expiration_type == 'documents' ?
                        'JOIN (
							SELECT SUM(CASE WHEN dc.todate < ?NOW? THEN 1 ELSE 0 END),
								SUM(CASE WHEN dc.todate > ?NOW? THEN 1 ELSE 0 END),
								d.customerid FROM documents d
							JOIN documentcontents dc ON dc.docid = d.id
							WHERE d.type IN (' . DOC_CONTRACT . ',' . DOC_ANNEX . ')'
                            . $archived_document_condition
                            . ' GROUP BY d.customerid
							HAVING SUM(CASE WHEN dc.todate > 0 AND dc.todate < ?NOW? THEN 1 ELSE 0 END) > 0
								AND SUM(CASE WHEN dc.todate >= ?NOW? THEN 1 ELSE 0 END) = 0
						) d ON d.customerid = c.id' :
                        'JOIN (
							SELECT customerid
							FROM assignments
							WHERE dateto > 0
							GROUP BY customerid
							HAVING MAX(dateto) < ?NOW?
						) ass ON ass.customerid = c.id') :
                ($contracts == 3 ?
                    ($contracts_expiration_type == 'documents' ?
                        'JOIN (
							SELECT DISTINCT d.customerid FROM documents d
							JOIN documentcontents dc ON dc.docid = d.id
							WHERE dc.todate >= ?NOW? AND dc.todate <= ?NOW? + 86400 * ' . $contracts_days . '
								AND type IN (' . DOC_CONTRACT . ',' . DOC_ANNEX . ')'
                                . $archived_document_condition
                            . '
						) d ON d.customerid = c.id' :
                        'JOIN (
							SELECT customerid
							FROM assignments
							WHERE dateto > 0
							GROUP BY customerid
							HAVING MAX(dateto) >= ?NOW? AND MAX(dateto) <= ?NOW? + 86400 * ' . $contracts_days . '
						) ass ON ass.customerid = c.id') :
                ($contracts == 4 ? 'JOIN (
                            SELECT DISTINCT d.customerid FROM documents d
                            JOIN documentcontents dc ON dc.docid = d.id
                            WHERE dc.todate <> 0
                                AND type IN (' . DOC_CONTRACT . ',' . DOC_ANNEX . ')'
                                . $archived_document_condition
                            . '
                        ) d ON d.customerid = c.id' : ''))))
                . ' WHERE '
                . (empty($state_conditions) ? '1 = 1' : implode(' ' . $statesqlskey . ' ', $state_conditions))
                . ($ignore_deleted_customers ? ' AND c.deleted = 0' : '')
                . ($flag_condition ? ' AND ' . $flag_condition : '')
                . (isset($division) && $division ? ' AND c.divisionid = ' . intval($division) : '')
                . ($assignment ? ' AND c.id IN ('.$assignment.')' : '')
                . (isset($network) && $network ? ' AND (EXISTS (SELECT 1 FROM vnodes WHERE ownerid = c.id
                		AND (netid' . (is_array($network) ? ' IN (' . implode(',', $network) . ')' : ' = ' . $network) . '
                		OR (ipaddr_pub > ' . $net['address'] . ' AND ipaddr_pub < ' . $net['broadcast'] . ')))
                	OR EXISTS (SELECT 1 FROM netdevices
                		JOIN vnodes ON vnodes.netdev = netdevices.id AND vnodes.ownerid IS NULL
                		WHERE netdevices.ownerid = c.id AND (netid'
                            . (is_array($network) ? ' IN (' . implode(',', $network) . ')' : ' = ' . $network) . '
                		OR (ipaddr_pub > ' . $net['address'] . ' AND ipaddr_pub < ' . $net['broadcast'] . '))))' : '')
                . (!empty($customergroup) && $customergroup != -1 ? ' AND ca.gcount '
                    . ($customergroupnegation
                        ? ($customergroupsqlskey == 'AND' ? 'IS NULL' : ' < ' . (is_array($customergroup) ? count($customergroup) : 1))
                        : ($customergroupsqlskey == 'AND' ? '= ' . (is_array($customergroup) ? count($customergroup) : 1) : '> 0')
                    ) : '')
                . (isset($customergroup) && $customergroup == -1 ? ' AND ca.gcount IS NULL ' : '')
                . (!empty($nodegroup) ? ($nodegroupnegation ? ' AND na.gcount IS NULL' : ' AND na.gcount = ' . (is_array($nodegroup) ? count($nodegroup) : 1)) : '')
                . (!empty($consent_condition) ? ' AND ' . $consent_condition : '')
                . (isset($sqlsarg) ? ' AND (' . $sqlsarg . ')' : '')
                . ($sqlord != ''  && !$count ? $sqlord . ' ' . $direction . ', c.id ASC' : '')
                . (isset($limit) && !$count ? ' LIMIT ' . $limit : '')
                . (isset($offset) && !$count ? ' OFFSET ' . $offset : '');

        if (!$count) {
            $customerlist = $this->db->GetAll($sql);

            if (!empty($customerlist)) {
                $customer_idx_by_cids = array();
                foreach ($customerlist as $idx => $row) {
                    // summary
                    if ($row['balance'] > 0) {
                        $over += $row['balance'];
                    } elseif ($row['balance'] < 0) {
                        $below += $row['balance'];
                    }

                    $customer_idx_by_cids[$row['id']] = $idx;
                }

                if (!empty($customernodes)) {
                    $nodes = $this->db->GetAll(
                        'SELECT n.id, n.name, n.mac, n.ownerid, INET_NTOA(n.ipaddr) AS ip FROM vnodes n
                            WHERE n.ownerid IN (' . implode(',', array_keys($customer_idx_by_cids)) . ')'
                    );
                    if (empty($nodes)) {
                        $nodes = array();
                    }
                    foreach ($nodes as $node) {
                        $idx = $customer_idx_by_cids[$node['ownerid']];
                        if (!isset($customerlist[$idx]['nodes'])) {
                            $customerlist[$idx]['nodes'] = array();
                        }
                        $customerlist[$idx]['nodes'][] = $node;
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

        $daysecond = time() - strtotime('today');
        $weekday = 1 << (date('N') - 1);

        $result = $this->db->GetAll("SELECT
                                        n.id, n.name, mac, ipaddr, inet_ntoa(ipaddr) AS ip, n.netdev, nd.name as netdev_name,
                                        n.linktype, n.linktechnology, n.linkspeed,
                                        ipaddr_pub, n.authtype, inet_ntoa(ipaddr_pub) AS ip_pub,
                                        passwd, access, warning, info, n.ownerid, lastonline, n.location, n.address_id,
                                        (CASE WHEN addr.city_id IS NOT NULL THEN 1 ELSE 0 END) AS teryt,
                                        (SELECT COUNT(*)
                                        FROM nodegroupassignments
                                        WHERE nodeid = n.id) AS gcount,
                                        n.netid,
                                        net.name AS netname,
                                        net.notes AS netnotes,
                                        vlans.vlanid,
                                        vlans.description AS vlandescription,
                                        (CASE WHEN EXISTS (
                                            SELECT 1 FROM nodelocks
                                            WHERE disabled = 0 AND (days & " . $weekday . ") > 0 AND " . $daysecond . " >= fromsec
                                                AND " . $daysecond . " <= tosec AND nodeid = n.id
                                        ) THEN 1 ELSE 0 END) AS locked
                                     FROM
                                        vnodes n
                                     LEFT JOIN addresses addr ON addr.id = n.address_id
                                     JOIN networks net ON net.id = n.netid
                                     LEFT JOIN vlans ON vlans.id = net.vlanid
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
            if (ConfigHelper::checkConfig('phpui.ewx_support')) {
                $channels = $this->db->GetAllByKey('SELECT nodeid, channelid, c.name, c.id, cid,
				        nc.upceil, nc.downceil
			 		FROM ewx_stm_nodes
					JOIN ewx_stm_channels nc ON (channelid = nc.id)
					LEFT JOIN ewx_channels c ON (c.id = nc.cid)
					WHERE nodeid IN (' . implode(',', array_keys($ids)) . ')', 'nodeid');

                if ($channels) {
                    foreach ($channels as $channel) {
                        $idx = $ids[$channel['nodeid']];
                        $result[$idx]['channelid']   = $channel['id'] ?: $channel['channelid'];
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
                                              nd.description,
                                              nd.producer,
                                              nd.model,
                                              t.id AS devicetypeid,
                                              t.name AS devicetypename,
                                              nd.serialnumber, nd.ports, nd.purchasetime, nd.guaranteeperiod, nd.shortname, nd.nastype,
                                              nd.clients, nd.community, nd.channelid, nd.longitude, nd.latitude, nd.netnodeid, nd.invprojectid,
                                              nd.status, nd.netdevicemodelid, nd.ownerid, no.authtype, va.id as address_id
                                           FROM
                                              netdevices nd
                                              LEFT JOIN netdevicemodels m ON m.id = nd.netdevicemodelid
                                              LEFT JOIN netdevicetypes t ON t.id = m.type
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
            SELECT n.*,
            nd.id AS routernetdevid, nd.name AS routernetdevname,
            rn.nodeid AS routernodeid, nodes.name AS routernodename, INET_NTOA(nodes.ipaddr) AS routerip
            FROM vnetworks n
            LEFT JOIN routednetworks rn ON rn.netid = n.id
            LEFT JOIN nodes ON nodes.id = rn.nodeid
            LEFT JOIN netdevices nd ON nd.id = nodes.netdev AND nodes.ownerid IS NULL AND nodes.netdev IS NOT NULL
            WHERE n.ownerid = ?
            ORDER BY n.name ASC
            ' . ($count ? ' LIMIT ' . $count : ''), array($id));
    }

    public function getCustomerConsents($id)
    {
        $result = $this->db->GetAllByKey('SELECT cdate, type FROM customerconsents WHERE customerid = ?', 'type', array($id));
        if (empty($result)) {
            return array();
        }

        return $this->extractCustomerConsents($result);
    }

    public function getCustomerSensibleData($id)
    {
        return $this->db->GetRow(
            'SELECT c.ssn, c.icn
            FROM customer' . (defined('LMS-UI') ? 'view' : 's') . ' c
            WHERE c.id = ?',
            array($id)
        );
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
        global $CONTACTTYPES, $CUSTOMERCONTACTTYPES, $CUSTOMERFLAGS;

        require_once(LIB_DIR . DIRECTORY_SEPARATOR . 'customercontacttypes.php');

        $capitalize_customer_names = ConfigHelper::checkConfig('phpui.capitalize_customer_names', true);
        if ($result = $this->db->GetRow('SELECT c.*, '
                . $this->db->Concat($capitalize_customer_names ? 'UPPER(c.lastname)' : 'c.lastname', "' '", 'c.name') . ' AS customername,
			d.shortname AS division, d.label AS division_label, d.account, c.altname
			FROM customer' . (defined('LMS-UI') ? '' : 'address') . 'view c
			LEFT JOIN divisions d ON (d.id = c.divisionid)
			WHERE c.id = ?', array($id))) {
            if (!$short) {
                $user_manager = new LMSUserManager($this->db, $this->auth, $this->cache, $this->syslog);
                $result['createdby'] = $user_manager->getUserName($result['creatorid']);
                $result['modifiedby'] = $user_manager->getUserName($result['modid']);
                $result['creationdateh'] = date('Y/m/d, H:i', $result['creationdate']);
                $result['moddateh'] = date('Y/m/d, H:i', $result['moddate']);
                $result['consentdate'] = !empty($result['consentdate']) ? date('Y/m/d', $result['consentdate']) : '';
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
                    $result['post_stateid'] = $result['stateid'] ?? null;
                    $result['post_cstate'] = $result['cstate'] ?? null;
                } else if ($result['post_zip'] && ($cstate = $this->db->GetRow('SELECT s.id, s.name
					FROM states s, zipcodes
					WHERE zip = ? AND stateid = s.id', array($result['post_zip'])))) {
                    $result['post_stateid'] = $cstate['id'];
                    $result['post_cstate'] = $cstate['name'];
                }
                $result['consents'] = $this->getCustomerConsents($id);
                $result['extids'] = $this->getCustomerExternalIDs($id);
            }
            $result['balance'] = $this->getCustomerBalance($result['id']);
            if (ConfigHelper::checkConfig('phpui.show_customer_due_balance', ConfigHelper::checkConfig('phpui.show_customer_expired_balance'))) {
                $result['expiredbalance'] = $this->getCustomerBalance($result['id'], null, true);
            }
            $result['bankaccount'] = bankaccount($result['id'], $result['account']);

            $result['secure_pin'] = preg_match('/^\$[0-9a-z]+\$/', $result['pin']);

            $flags = $result['flags'];
            $result['flags'] = array();
            foreach ($CUSTOMERFLAGS as $cflag => $flag) {
                if ($flags & $cflag) {
                    $result['flags'][$cflag] = $cflag;
                }
            }

            foreach ($CUSTOMERCONTACTTYPES as $contacttype => $properties) {
                $result[$contacttype . 's'] = $this->db->GetAll(
                    'SELECT id, contact AS ' . $contacttype . ',
						contact, name, type
					FROM customercontacts
					WHERE customerid = ? AND type & ? > 0 ORDER BY id',
                    array($result['id'], $properties['flagmask'])
                );

                if ($contacttype == 'email' && $result[$contacttype . 's']) {
                    foreach ($result[$contacttype . 's'] as $key => $item) {
                        $result[$contacttype . 's'][$key]['properties'] = $this->db->GetAll(
                            'SELECT name, value
                            FROM customercontactproperties
                            WHERE contactid = ? ORDER BY name',
                            array($item['id'])
                        );
                    }
                }
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

                        $result[$ctype][$idx]['customerid'] = $id;
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

    public function GetCustomerAltName($id)
    {
        return empty($id) ? null : $this->db->GetOne(
            'SELECT altname FROM customers WHERE id = ?',
            array($id)
        );
    }

    /**
    * Updates customer
    *
    * @param array $customerdata Customer data
    * @return int Affected rows
    */
    public function customerUpdate($customerdata)
    {
        global $CUSTOMERFLAGS;

        $location_manager = new LMSLocationManager($this->db, $this->auth, $this->cache, $this->syslog);

        $customerdata['name'] = str_replace(array('”', '„'), '"', $customerdata['name']);
        $customerdata['lastname'] = str_replace(array('”', '„'), '"', $customerdata['lastname']);

        $flags = 0;
        if (isset($customerdata['flags'])) {
            foreach ($customerdata['flags'] as $flag) {
                if (isset($CUSTOMERFLAGS[$flag])) {
                    $flags |= $flag;
                }
            }
        }

        $customer = $this->db->GetRow('SELECT ssn, ict, icn, icexpires, pin, pinlastchange FROM customers WHERE id = ?', array($customerdata['id']));

        $unsecure_pin_validity = intval(ConfigHelper::getConfig('phpui.unsecure_pin_validity', 0, true));
        if (empty($unsecure_pin_validity)) {
            $pinlastchange = $customer['pin'] == $customerdata['pin'] ? $customer['pinlastchange'] : time();
            $pin = $customerdata['pin'];
        } else {
            if (strlen($customerdata['pin'])) {
                $pinlastchange = time();
                $pin = $customerdata['pin'];
            } else {
                $pinlastchange = $customer['pinlastchange'];
                $pin = $customer['pin'];
            }
        }

        $args = array(
            'status'         => $customerdata['status'],
            'type'           => empty($customerdata['type']) ? 0 : 1,
            'ten'            => $customerdata['ten'],
            'ssn'            => $customerdata['ssn'] ?? $customer['ssn'],
            SYSLOG::RES_USER => Auth::GetCurrentUser(),
            'info'           => Utils::removeInsecureHtml($customerdata['info']),
            'notes'          => Utils::removeInsecureHtml($customerdata['notes']),
            'lastname'       => $customerdata['lastname'],
            'name'           => $customerdata['name'],
            'altname'        => empty($customerdata['altname']) ? null : $customerdata['altname'],
            'message'        => Utils::removeInsecureHtml($customerdata['message']),
            'documentmemo'   => empty($customerdata['documentmemo']) ? null : Utils::removeInsecureHtml($customerdata['documentmemo']),
            'pin'            => $pin,
            'pinlastchange'  => $pinlastchange,
            'regon'          => $customerdata['regon'],
            'ict'            => $customerdata['ict'] ?? $customer['ict'],
            'icn'            => $customerdata['icn'] ?? $customer['icn'],
            'icexpires'      => isset($customerdata['icexpires']) ? (intval($customerdata['icexpires']) > 0
                ? strtotime('tomorrow', intval($customerdata['icexpires'])) - 1
                : ($customerdata['icexpires'] === '-1' ? 0 : null)) : $customer['icexpires'],
            'rbename'        => $customerdata['rbename'],
            'rbe'            => $customerdata['rbe'],
            'cutoffstop'     => $customerdata['cutoffstop'],
            SYSLOG::RES_DIV  => empty($customerdata['divisionid']) ? null : $customerdata['divisionid'],
            'paytime'        => $customerdata['paytime'],
            'paytype'        => $customerdata['paytype'] ?: null,
            'flags'          => $flags,
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

        $capitalize_customer_names = ConfigHelper::checkConfig('phpui.capitalize_customer_names', true);

        // UPDATE CUSTOMER FIELDS
        $res = $this->db->Execute(
            'UPDATE customers SET status = ?, type = ?, ten = ?, ssn = ?, moddate = ?NOW?, modid = ?,
            info = ?, notes = ?, lastname=' . ($capitalize_customer_names ? 'UPPER(?)' : '?') . ', name = ?, altname = ?,
            deleted = 0, message = ?, documentmemo = ?, pin = ?, pinlastchange = ?, regon = ?, ict = ?, icn = ?, icexpires = ?,
            rbename = ?, rbe = ?, cutoffstop = ?, divisionid = ?, paytime = ?, paytype = ?, flags = ?
            WHERE id = ?',
            array_values($args)
        );

        if ($res) {
            if ($this->syslog) {
                unset($args[SYSLOG::RES_USER]);
                $args['deleted'] = 0;
                $this->syslog->AddMessage(SYSLOG::RES_CUST, SYSLOG::OPER_UPDATE, $args);
            }

            // update customer consents
            $this->updateCustomerConsents(
                $customerdata['id'],
                array_keys($this->compactCustomerConsents($this->getCustomerConsents($customerdata['id']))),
                array_keys($this->compactCustomerConsents($customerdata['consents']))
            );

            if (empty($customerdata['extids'])) {
                $customerdata['extids'] = array();
            } else {
                $customerdata['extids'] = array_filter($customerdata['extids'], function ($customerextid) {
                    return strlen($customerextid['extid']) > 0;
                });
            }
            $this->updateCustomerExternalIDs(
                $customerdata['id'],
                $customerdata['extids'],
                true
            );
        }

        return $res;
    }

    private function deleteCustomerHelper($id)
    {
        global $LMS;

        $disable_customer_contacts = ConfigHelper::checkConfig('phpui.disable_contacts_during_customer_delete');
        $delete_related_resources = preg_split(
            '/[\s\.,;]+/',
            ConfigHelper::getConfig(
                'phpui.delete_related_customer_resources',
                'assignments,customergroups,nodegroups,nodes,userpanel'
            ),
            -1,
            PREG_SPLIT_NO_EMPTY
        );
        if (empty($delete_related_resources)) {
            $delete_related_resources = array();
        }

        $this->db->Execute('UPDATE customers SET deleted=1, moddate=?NOW?, modid=?
                WHERE id=?', array(Auth::GetCurrentUser(), $id));

        if ($this->syslog) {
            $this->syslog->AddMessage(
                SYSLOG::RES_CUST,
                SYSLOG::OPER_UPDATE,
                array(SYSLOG::RES_CUST => $id, 'deleted' => 1)
            );
            if (in_array('customergroups', $delete_related_resources)) {
                $assigns = $this->db->GetAll('SELECT id, customergroupid FROM vcustomerassignments WHERE customerid = ?', array($id));
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
        }

        if (in_array('customergroups', $delete_related_resources)) {
            $this->db->Execute('UPDATE customerassignments SET enddate = ?NOW? WHERE customerid = ? AND enddate = 0', array($id));
        }

        if ($this->syslog) {
            if (in_array('assignments', $delete_related_resources)) {
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

        if (in_array('assignments', $delete_related_resources)) {
            $liabs = $this->db->GetCol('SELECT liabilityid FROM assignments WHERE liabilityid IS NOT NULL AND customerid = ?', array($id));
            if (!empty($liabs)) {
                $this->db->Execute('DELETE FROM liabilities WHERE id IN (' . implode(',', $liabs) . ')');
            }

            $this->db->Execute('DELETE FROM assignments WHERE customerid=?', array($id));
        }

        // nodes
        $nodes = $this->db->GetCol('SELECT id FROM vnodes WHERE ownerid=?', array($id));
        if ($nodes) {
            if ($this->syslog && in_array('nodes', $delete_related_resources)) {
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

            if (in_array('nodegroups', $delete_related_resources)) {
                $this->db->Execute('DELETE FROM nodegroupassignments WHERE nodeid IN (' . join(',', $nodes) . ')');
            }

            if (in_array('nodes', $delete_related_resources)) {
                $plugin_data = array();
                foreach ($nodes as $node) {
                    $plugin_data[] = array('id' => $node, 'ownerid' => $id);
                }
                $LMS->ExecHook('node_del_before', $plugin_data);
                $this->db->Execute('DELETE FROM nodes WHERE ownerid=?', array($id));
                $LMS->ExecHook('node_del_after', $plugin_data);
            }
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

        if (in_array('userpanel', $delete_related_resources)) {
            // Remove Userpanel rights
            $userpanel_dir = ConfigHelper::getConfig('directories.userpanel_dir');
            if (!empty($userpanel_dir)) {
                $this->db->Execute('DELETE FROM up_rights_assignments WHERE customerid=?', array($id));
            }
        }
    }

    /**
     * Deletes customer
     *
     * @global type $LMS
     * @param int $id Customer id
     */
    public function deleteCustomer($id)
    {
        $this->db->BeginTrans();
        $this->deleteCustomerHelper($id);
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
        $this->db->Execute(
            'DELETE FROM addresses WHERE id IN ?',
            array($addr_ids)
        );

        $this->deleteCustomerHelper($id);

        $this->db->Execute('DELETE FROM customers WHERE id = ?', array($id));

        if ($this->syslog) {
            $this->syslog->AddMessage(SYSLOG::RES_CUST, SYSLOG::OPER_DELETE, array(SYSLOG::RES_CUST => $id));
        }

        $this->db->CommitTrans();
    }

    public function restoreCustomer($id)
    {
        $this->db->BeginTrans();

        $this->db->Execute(
            'UPDATE customers
            SET deleted = 0, moddate = ?NOW?, modid = ?
            WHERE id = ?',
            array(
                Auth::GetCurrentUser(),
                $id,
            )
        );

        if ($this->syslog) {
            $this->syslog->AddMessage(
                SYSLOG::RES_CUST,
                SYSLOG::OPER_UPDATE,
                array(SYSLOG::RES_CUST => $id, 'deleted' => 0)
            );
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

    public function determineDefaultCustomerAddress(array &$caddr)
    {
        if (empty($caddr)) {
            return null;
        }

        foreach ($caddr as $k => &$v) {
            if (empty($v['location'])) {
                unset($caddr[$k]);
                continue;
            } elseif ($v['teryt']) {
                $v['location'] = trans('$a (TERYT)', $v['location']);
            }

            switch ($v['location_address_type']) {
                case BILLING_ADDRESS:
                    $billing_address = $k;
                    break;
                case LOCATION_ADDRESS:
                    if (isset($location_address)) {
                        $location_address = 0;
                        break;
                    }
                    $location_address = $k;
                    break;
                case DEFAULT_LOCATION_ADDRESS:
                    $default_location_address = $k;
                    break;
            }

            $v['location'] = (empty($v['location_name']) ? '' : $v['location_name'] . ', ') . $v['location'];
        }
        unset($v);

        if (isset($default_location_address)) {
            $caddr[$default_location_address]['default_address'] = true;
            return $default_location_address;
        } elseif (!empty($location_address)) {
            $caddr[$location_address]['default_address'] = true;
            return $location_address;
        } else {
            $caddr[$billing_address]['default_address'] = true;
            return $billing_address;
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
                (CASE WHEN lst.name2 IS NOT NULL THEN ' . $this->db->Concat('lst.name', "' '", 'lst.name2') . ' ELSE lst.name END) AS location_full_street_name,
                (CASE WHEN lst.name2 IS NOT NULL THEN ' . $this->db->Concat('lst.name2', "' '", 'lst.name') . ' ELSE lst.name END) AS location_full_reversed_street_name,
                COALESCE(lst.name, addr.street) AS location_short_street_name,
                addr.house as location_house, addr.zip as location_zip, addr.postoffice AS location_postoffice,
                addr.country_id as location_country_id, addr.flat as location_flat,
                ca.type as location_address_type, addr.location,
                0 AS use_counter, 0 AS node_use_counter, 0 AS netdev_use_counter, 0 AS netnode_use_counter,
                (CASE WHEN addr.city_id is not null THEN 1 ELSE 0 END) as teryt,
                ' . $this->db->Concat('simc.woj', 'simc.pow', 'simc.gmi', 'simc.rodz_gmi') . ' AS terc,
                simc.sym AS simc,
                ulic.sym_ul AS ulic
            FROM customers cv
            LEFT JOIN customer_addresses ca ON ca.customer_id = cv.id
            LEFT JOIN vaddresses addr       ON addr.id = ca.address_id
            LEFT JOIN location_streets lst ON lst.id = addr.street_id
            LEFT JOIN teryt_simc simc ON simc.cityid = addr.city_id
            LEFT JOIN teryt_ulic ulic ON ulic.id = addr.street_id
            WHERE
                cv.id = ?' .
                (($hide_deleted) ? ' AND cv.deleted != 1' : '')
            . ' ORDER BY LOWER(addr.city), LOWER(addr.street), LOWER(addr.house)',
            'address_id',
            array( $id )
        );

        if (empty($data)) {
            return array();
        }

        $node_addresses = $this->db->GetAllByKey('SELECT address_id, 1 AS resourcetype, COUNT(*) AS used FROM nodes
			WHERE ownerid = ? AND address_id IS NOT NULL
			GROUP BY address_id, resourcetype', 'address_id', array($id));
        if (empty($node_addresses)) {
            $node_addresses = array();
        }

        $netdev_addresses = $this->db->GetAllByKey('SELECT address_id, 2 AS resourcetype, COUNT(*) AS used FROM netdevices
			WHERE ownerid = ? AND address_id IS NOT NULL
			GROUP BY address_id, resourcetype', 'address_id', array($id));
        if (empty($netdev_addresses)) {
            $netdev_addresses = array();
        }

        $netnode_addresses = $this->db->GetAllByKey('SELECT address_id, 3 AS resourcetype, COUNT(*) AS used FROM netnodes
			WHERE ownerid = ? AND address_id IS NOT NULL
			GROUP BY address_id, resourcetype', 'address_id', array($id));
        if (empty($netnode_addresses)) {
            $netnode_addresses = array();
        }

        static $resource_type_map = array(
            1 => 'node_use_counter',
            2 => 'netdev_use_counter',
            3 => 'netnode_use_counter',
        );

        foreach (array($node_addresses, $netdev_addresses, $netnode_addresses) as $addresses) {
            foreach ($addresses as $address_id => $address) {
                if (isset($data[$address_id]) && !empty($address['used'])) {
                    $data[$address_id]['use_counter'] += $address['used'];
                    $data[$address_id][$resource_type_map[$address['resourcetype']]] += $address['used'];
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
                                                ca.type, addr.location,
                                                (CASE WHEN addr.city_id IS NOT NULL THEN 1 ELSE 0 END) AS teryt
                                             FROM customer_addresses ca
                                                LEFT JOIN vaddresses addr ON ca.address_id = addr.id
                                             WHERE
                                                ca.customer_id = ?', 'type', array($customer_id));

        $address = null;

        if (isset($addresses[DEFAULT_LOCATION_ADDRESS])) {
            $address = $addresses[DEFAULT_LOCATION_ADDRESS];
        } elseif (isset($addresses[BILLING_ADDRESS])) {
            $address = $addresses[BILLING_ADDRESS];
        }

        if (isset($address)) {
            $address = (empty($address['teryt']) ? $address['location'] : trans('$a (TERYT)', $address['location']));
        }

        return $address;
    }

    public function getFullAddressForCustomerStuff($customer_id)
    {
        $addresses = $this->db->GetAllByKey('SELECT
                                                ca.address_id, ca.type, addr.location
                                             FROM customer_addresses ca
                                                LEFT JOIN vaddresses addr ON ca.address_id = addr.id
                                             WHERE
                                                ca.customer_id = ?', 'type', array($customer_id));

        return $addresses[DEFAULT_LOCATION_ADDRESS] ?? $addresses[BILLING_ADDRESS] ?? null;
    }

    public function detectCustomerLocationAddress($customer_id)
    {
        $addresses = $this->db->GetAll(
            'SELECT ca.address_id, ca.type
            FROM customer_addresses ca
            WHERE ca.customer_id = ?
            ORDER BY ca.type DESC',
            array($customer_id)
        );

        if (!empty($addresses)) {
            $locations = 0;
            $location_address_id = null;
            foreach ($addresses as $address) {
                switch ($address['type']) {
                    case DEFAULT_LOCATION_ADDRESS:
                        return $address['address_id'];
                    case BILLING_ADDRESS:
                        if ($locations == 1) {
                            return $location_address_id;
                        } else {
                            return $address['address_id'];
                        }
                        break;
                    case LOCATION_ADDRESS:
                        $location_address_id = $address['address_id'];
                        $locations++;
                        break;
                }
            }
        }

        return null;
    }

    public function isTerritAddress($address_id)
    {
        return $this->db->GetOne('SELECT id FROM addresses WHERE city_id IS NOT NULL AND house IS NOT NULL AND id = ?', array($address_id)) > 0;
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

    public function isSplitPaymentSuggested($customerid, $cdate, $value)
    {
        if (empty($customerid) || empty($value)) {
            return false;
        }

        $customerid = intval($customerid);
        $value = floatval($value);

        if (empty($cdate)) {
            $cdate = time();
        } else {
            [$year, $month, $day] = explode('/', $cdate);
            $cdate = mktime(0, 0, 0, $month, $day, $year);
        }

        $default_value = $cdate >= mktime(0, 0, 0, 11, 1, 2019) ? 15000 : -1;
        $split_payment_threshold_value = floatval(ConfigHelper::getConfig('invoices.split_payment_threshold_value', $default_value));
        if ($split_payment_threshold_value == -1) {
            return false;
        }

        return $this->db->GetOne(
            'SELECT c.id FROM customers c
            WHERE c.id = ? AND c.type = ?',
            array($customerid, CTYPES_COMPANY)
        ) > 0 && $value >= $split_payment_threshold_value;
    }

    public function isTelecomServiceSuggested($customerid)
    {
        if (empty($customerid)) {
            return false;
        }

        $customerid = intval($customerid);

        $check_customer_vat_payer_flag_for_telecom_service =
            ConfigHelper::checkConfig('invoices.check_customer_vat_payer_flag_for_telecom_service');

        if (time() < mktime(0, 0, 0, 7, 1, 2021)) {
            return $this->db->GetOne(
                'SELECT c.id FROM customers c
                WHERE c.id = ? AND (c.type = ? OR '
                . ($check_customer_vat_payer_flag_for_telecom_service ? 'c.flags & ' . CUSTOMER_FLAG_VAT_PAYER . ' = 0' : ' 1 = 0') . ')',
                array($customerid, CTYPES_PRIVATE)
            ) > 0;
        } else {
            return $this->db->GetOne(
                'SELECT c.id FROM customeraddressview c
                JOIN vdivisions d ON d.id = c.divisionid
                WHERE c.id = ?
                    AND c.type = ?
                    AND c.countryid IS NOT NULL
                    AND d.countryid IS NOT NULL
                    AND c.countryid <> d.countryid',
                array($customerid, CTYPES_PRIVATE)
            ) > 0;
        }
    }

    public function getCustomerSMSOptions()
    {
        global $LMS;

        $options = array();

        $variable_mapping = array(
            'service' => 'sms-customers.service',
            'username' => 'sms-customers.username',
            'password' => 'sms-customers.password',
            'debug_phone' => 'sms-customers.debug_phone',
            'prefix' => 'sms-customers.prefix',
            'transliterate_message' => 'sms-customers.transliterate_message',
            'max_length' => 'sms-customers.max_length',
            'delivery_reports' => 'sms-customers.delivery_reports',
            'smscenter_type' => 'sms-customers.smscenter_type',
            'smstools_outdir' => 'sms-customers.smstools_outdir',
            'queue' => 'sms-customers.queue',
            'fast' => 'sms-customers.fast',
            'from' => 'sms-customers.from',
            'phone_number_validation_pattern' => 'sms-customers.phone_number_validation_pattern',
            'message_template' => 'sms-customers.message_template',
        );

        $variable_mapping = $LMS->executeHook(
            'get_customer_sms_options',
            $variable_mapping
        );

        foreach ($variable_mapping as $option_name => $variable_name) {
            if (is_array($variable_name)) {
                $exists = false;
                foreach ($variable_name as $vname) {
                    if (ConfigHelper::variableExists($vname)) {
                        $exists = true;
                        break;
                    }
                }
                if (!$exists) {
                    continue;
                }
                $variable_name = $vname;
            } else if (!ConfigHelper::variableExists($variable_name)) {
                continue;
            }

            $variable = ConfigHelper::getConfig($variable_name);
            if (empty($variable)) {
                continue;
            }

            $options[$option_name] = $variable;
        }

        return $options;
    }

    private function getCustomerAddressessWithOrWithoutEndPoints($customerid, $with = true)
    {
        $customerid = intval($customerid);

        return $this->db->GetAllByKey(
            'SELECT
                a.*,
                ca.type AS location_type,
                (CASE WHEN a.city_id IS NOT NULL THEN 1 ELSE 0 END) AS teryt
            FROM vaddresses a
            JOIN customer_addresses ca ON ca.address_id = a.id
            WHERE ca.customer_id = ? AND a.id ' . ($with ? '' : 'NOT') . ' IN (
                (
                    SELECT DISTINCT (CASE WHEN nd.address_id IS NULL
                            THEN (CASE WHEN ca.address_id IS NULL THEN ca2.address_id ELSE ca.address_id END)
                            ELSE nd.address_id END
                        ) AS address_id FROM netdevices nd
                    LEFT JOIN customer_addresses ca ON ca.customer_id = nd.ownerid AND ca.type = ?
                    LEFT JOIN customer_addresses ca2 ON ca2.customer_id = nd.ownerid AND ca.type = ?
                    WHERE nd.ownerid = ?
                ) UNION (
                    SELECT DISTINCT (CASE WHEN n.address_id IS NULL
                            THEN (CASE WHEN ca.address_id IS NULL THEN ca2.address_id ELSE ca.address_id END)
                            ELSE n.address_id END
                        ) AS address_id FROM nodes n
                    LEFT JOIN customer_addresses ca ON ca.customer_id = n.ownerid AND ca.type = ?
                    LEFT JOIN customer_addresses ca2 ON ca2.customer_id = n.ownerid AND ca.type = ?
                    WHERE n.ownerid = ?
                )
            )',
            'id',
            array($customerid, DEFAULT_LOCATION_ADDRESS, BILLING_ADDRESS, $customerid, DEFAULT_LOCATION_ADDRESS, BILLING_ADDRESS, $customerid)
        );
    }

    public function GetCustomerAddressesWithEndPoints($customerid)
    {
        return $this->getCustomerAddressessWithOrWithoutEndPoints($customerid);
    }

    public function GetCustomerAddressesWithoutEndPoints($customerid)
    {
        return $this->getCustomerAddressessWithOrWithoutEndPoints($customerid, false);
    }

    public function checkCustomerTenExistence($customerid, $ten, $divisionid = null)
    {
        $ten = preg_replace('/- /', '', $ten);
        if (empty($divisionid)) {
            if (empty($customerid)) {
                return $this->db->GetOne(
                    "SELECT id FROM customers WHERE REPLACE(REPLACE(ten, '-', ''), ' ', '') = ?",
                    array(preg_replace('/[ \-]/', '', $ten))
                ) > 0;
            } else {
                return $this->db->GetOne(
                    "SELECT id FROM customers WHERE id <> ? AND REPLACE(REPLACE(ten, '-', ''), ' ', '') = ?",
                    array($customerid, preg_replace('/[ \-]/', '', $ten))
                ) > 0;
            }
        } else {
            if (empty($customerid)) {
                return $this->db->GetOne(
                    "SELECT id FROM customers WHERE divisionid = ? AND REPLACE(REPLACE(ten, '-', ''), ' ', '') = ?",
                    array($divisionid, preg_replace('/[ \-]/', '', $ten))
                ) > 0;
            } else {
                return $this->db->GetOne(
                    "SELECT id FROM customers WHERE id <> ? AND divisionid = ? AND REPLACE(REPLACE(ten, '-', ''), ' ', '') = ?",
                    array($customerid, $divisionid, preg_replace('/[ \-]/', '', $ten))
                ) > 0;
            }
        }
    }

    public function checkCustomerSsnExistence($customerid, $ssn, $divisionid = null)
    {
        $ssn = preg_replace('/- /', '', $ssn);
        if (empty($divisionid)) {
            if (empty($customerid)) {
                return $this->db->GetOne(
                    "SELECT id FROM customers WHERE REPLACE(REPLACE(ssn, '-', ''), ' ', '') = ?",
                    array(preg_replace('/[ \-]/', '', $ssn))
                ) > 0;
            } else {
                return $this->db->GetOne(
                    "SELECT id FROM customers WHERE id <> ? AND REPLACE(REPLACE(ssn, '-', ''), ' ', '') = ?",
                    array($customerid, preg_replace('/[ \-]/', '', $ssn))
                ) > 0;
            }
        } else {
            if (empty($customerid)) {
                return $this->db->GetOne(
                    "SELECT id FROM customers WHERE divisionid = ? AND REPLACE(REPLACE(ssn, '-', ''), ' ', '') = ?",
                    array($divisionid, preg_replace('/[ \-]/', '', $ssn))
                ) > 0;
            } else {
                return $this->db->GetOne(
                    "SELECT id FROM customers WHERE id <> ? AND divisionid = ? AND REPLACE(REPLACE(ssn, '-', ''), ' ', '') = ?",
                    array($customerid, $divisionid, preg_replace('/[ \-]/', '', $ssn))
                ) > 0;
            }
        }
    }

    public function checkCustomerConsent($customerid, $consent)
    {
        return $this->db->GetOne(
            'SELECT type FROM customerconsents
                WHERE customerid = ? AND type = ?',
            array($customerid, $consent)
        ) == $consent;
    }

    public function customerNotificationReplaceSymbols($string, $data)
    {
        $customerinfo = $data['customerinfo'];
        $string = str_replace('%cid%', $customerinfo['id'], $string);
        $string = str_replace('%customername%', $customerinfo['customername'], $string);
        $document = $data['document'];
        $string = str_replace('%document%', $document['fullnumber'], $string);
        $string = str_replace('%docid%', $document['id'], $string);
        return $string;
    }

    public function addCustomerConsents($customerid, $consents)
    {
        if (!is_array($consents)) {
            $consents = array($consents);
        }
        $consents = Utils::filterIntegers($consents);
        $added = 0;
        if (!empty($consents)) {
            $now = time();
            foreach ($consents as $consent) {
                if (!$this->db->GetOne(
                    'SELECT customerid FROM customerconsents WHERE customerid = ? AND type = ?',
                    array($customerid, $consent)
                )) {
                    $args = array(
                        SYSLOG::RES_CUST => $customerid,
                        'cdate' => $now,
                        'type' => $consent,
                    );
                    if ($this->db->Execute(
                        'INSERT INTO customerconsents (customerid, cdate, type) VALUES (?, ?, ?)',
                        array_values($args)
                    )) {
                        $added++;
                        if ($this->syslog) {
                            $this->syslog->AddMessage(SYSLOG::RES_CUSTCONSENT, SYSLOG::OPER_ADD, $args);
                        }
                    }
                }
            }
        }
        return $added;
    }

    public function removeCustomerConsents($customerid, $consents)
    {
        if (!is_array($consents)) {
            $consents = array($consents);
        }
        $consents = Utils::filterIntegers($consents);
        $removed = 0;
        if (!empty($consents)) {
            foreach ($consents as $consent) {
                if ($this->db->GetOne(
                    'SELECT customerid FROM customerconsents WHERE customerid = ? AND type = ?',
                    array($customerid, $consent)
                )) {
                    $args = array(
                        SYSLOG::RES_CUST => $customerid,
                        'type' => $consent,
                    );
                    if ($this->db->Execute(
                        'DELETE FROM customerconsents WHERE customerid = ? AND type = ?',
                        array_values($args)
                    )) {
                        $removed++;
                        if ($this->syslog) {
                            $this->syslog->AddMessage(SYSLOG::RES_CUSTCONSENT, SYSLOG::OPER_DELETE, $args);
                        }
                    }
                }
            }
        }
        return $removed;
    }

    private function changeCustomerContactFlags($operation, $customerid, $type, $flags)
    {
        if (!is_array($flags)) {
            $flags = array($flags);
        }
        $flags = Utils::filterIntegers($flags);
        $contacttypes = $GLOBALS['CUSTOMERCONTACTTYPES'];
        if (!empty($flags) && isset($contacttypes[$type])) {
            $contacttype = $contacttypes[$type];
            $flags = array_intersect(array_keys($contacttype['ui']['flags']), $flags);
            if (empty($flags)) {
                return;
            }
            $newtype = 0;
            foreach ($flags as $flag) {
                $newtype |= $flag;
            }
            if ($newtype) {
                if ($operation == 'add') {
                    $this->db->Execute(
                        'UPDATE customercontacts SET type = type | ? WHERE customerid = ? AND type & ? > 0',
                        array($newtype, $customerid, $contacttype['flagmask'])
                    );
                } else {
                    $this->db->Execute(
                        'UPDATE customercontacts SET type = type & ? WHERE customerid = ? AND type & ? > 0',
                        array(~$newtype, $customerid, $contacttype['flagmask'])
                    );
                }

                if ($this->syslog) {
                    $contacts = $this->db->GetAll(
                        'SELECT id, type FROM customercontacts WHERE customerid = ? AND type & ? > 0',
                        array($customerid, $contacttype['flagmask'])
                    );
                    if (!empty($contacts)) {
                        foreach ($contacts as $contact) {
                            $args = array(
                                SYSLOG::RES_CUSTCONTACT => $contact['id'],
                                SYSLOG::RES_CUST => $customerid,
                                'type' => $contact['type'],
                            );
                            $this->syslog->AddMessage(SYSLOG::RES_CUSTCONTACT, SYSLOG::OPER_UPDATE, $args);
                        }
                    }
                }
            }
        }
    }

    public function addCustomerContactFlags($customerid, $type, $flags)
    {
        return $this->changeCustomerContactFlags('add', $customerid, $type, $flags);
    }

    public function removeCustomerContactFlags($customerid, $type, $flags)
    {
        return $this->changeCustomerContactFlags('remove', $customerid, $type, $flags);
    }

    public function getCustomerNotes($cid)
    {
        return $this->db->GetAll(
            'SELECT
                n.id,
                u.login AS user,
                u.name AS username,
                u.rname AS rusername,
                dt,
                u2.login AS moduser,
                u2.name AS modusername,
                u2.rname AS modrusername,
                moddate,
                message AS note
            FROM customernotes n
            LEFT JOIN vusers u ON u.id = n.userid
            LEFT JOIN vusers u2 ON u2.id = n.moduserid
            WHERE customerid = ? ORDER BY dt DESC',
            array($cid)
        );
    }

    public function getCustomerNote($id)
    {
        $result = $this->db->GetRow(
            'SELECT
                n.id,
                u.login AS user,
                u.name AS username,
                u.rname AS rusername,
                dt,
                u2.login AS moduser,
                u2.name AS modusername,
                u2.rname AS modrusername,
                moddate,
                message AS note
            FROM customernotes n
            LEFT JOIN vusers u ON u.id = n.userid
            LEFT JOIN vusers u2 ON u2.id = n.moduserid
            WHERE n.id = ?',
            array($id)
        );
        $result['date'] = date('Y/m/d H:i', $result['dt']);
        $result['moddate'] = empty($result['moddate']) ? '' : date('Y/m/d H:i', $result['moddate']);
        $result['text'] = htmlspecialchars($result['note']);
        return $result;
    }

    public function addCustomerNote($params)
    {
        $res = $this->db->Execute(
            'INSERT INTO customernotes (userid, customerid, dt, message) VALUES (?, ?, ?NOW?, ?)',
            array(Auth::GetCurrentUser(), $params['customerid'], $params['customernote'])
        );

        if ($res) {
            $id = $this->db->GetLastInsertID('customernotes');
            if ($this->syslog) {
                $args = array(
                    SYSLOG::RES_CUSTNOTE => $id,
                    SYSLOG::RES_CUST => $params['customerid'],
                    'message' => $params['customernote'],
                );
                $this->syslog->AddMessage(SYSLOG::RES_CUSTNOTE, SYSLOG::OPER_ADD, $args);
            }
        } else {
            $id = null;
        }

        return $id;
    }

    public function updateCustomerNote($params)
    {
        if (!isset($params['noteid'], $params['customernote'])) {
            return null;
        }

        $res = $this->db->Execute(
            'UPDATE customernotes SET message = ?, moddate = ?NOW?, moduserid = ? WHERE id = ?',
            array(
                $params['customernote'],
                Auth::GetCurrentUser(),
                $params['noteid'],
            )
        );

        if ($res) {
            $id = $params['noteid'];
            if ($this->syslog) {
                $args = array(
                    SYSLOG::RES_CUSTNOTE => $params['noteid'],
                    SYSLOG::RES_CUST => $params['customerid'],
                    'message' => $params['customernote'],
                );
                $this->syslog->AddMessage(SYSLOG::RES_CUSTNOTE, SYSLOG::OPER_UPDATE, $args);
            }
        } else {
            $id = null;
        }

        return $id;
    }

    public function delCustomerNote($id)
    {
        if ($this->syslog) {
            $note = $this->db->GetAll(
                'SELECT id, customerid FROM customernotes WHERE id = ?',
                array($id)
            );
            $args = array(
                SYSLOG::RES_CUSTNOTE => $id,
                SYSLOG::RES_CUST => $note['customerid'],
            );
            $this->syslog->AddMessage(SYSLOG::RES_CUSTNOTE, SYSLOG::OPER_DELETE, $args);
        }

        $res = $this->db->Execute('DELETE FROM customernotes WHERE id = ?', array($id));

        return $res;
    }

    private function changeCustomerKarma($id, $diff)
    {
        $karma = $this->db->GetOne(
            'SELECT karma FROM customerview WHERE id = ?',
            array($id)
        );
        if (!isset($karma)) {
            return array(
                'karma' => 0,
                'error' => trans('Access denied!'),
            );
        }

        $customerKarmaChangeInterval = intval(ConfigHelper::getConfig('phpui.customer_karma_change_interval', '86400'));
        if (!$customerKarmaChangeInterval) {
            $customerKarmaChangeInterval = 86400;
        }

        $userid = Auth::GetCurrentUser();

        $timestamp = $this->db->GetOne(
            'SELECT timestamp
            FROM customerkarmalastchanges
            WHERE customerid = ? AND userid = ?',
            array($id, $userid)
        );
        if (isset($timestamp) && time() - $timestamp <= $customerKarmaChangeInterval) {
            return array(
                'karma' => $karma,
                'error' => trans('Karma is changed too often!'),
            );
        }

        $karma += $diff;
        $this->db->Execute(
            'UPDATE customers SET karma = ? WHERE id = ?',
            array($karma, $id)
        );
        if ($this->syslog) {
            $args = array(
                SYSLOG::RES_CUST => $id,
                SYSLOG::RES_USER => $userid,
                'karma' => $karma,
            );
            $this->syslog->AddMessage(SYSLOG::RES_CUST, SYSLOG::OPER_UPDATE, $args);
        }

        if (isset($timestamp)) {
            $this->db->Execute(
                'UPDATE customerkarmalastchanges SET timestamp = ?NOW? WHERE customerid = ? AND userid = ?',
                array($id, $userid)
            );
        } else {
            $this->db->Execute(
                'INSERT INTO customerkarmalastchanges (timestamp, customerid, userid) VALUES (?NOW?, ?, ?)',
                array($id, $userid)
            );
        }

        return array(
            'karma' => $karma,
        );
    }

    public function raiseCustomerKarma($id)
    {
        return $this->changeCustomerKarma($id, 1);
    }

    public function lowerCustomerKarma($id)
    {
        return $this->changeCustomerKarma($id, -1);
    }

    public function getCustomerPin($id)
    {
        return $this->db->GetOne('SELECT pin FROM customers WHERE id = ?', array($id));
    }

    public function getCustomerPinRequirements()
    {
        $pin_min_size = intval(ConfigHelper::getConfig('phpui.pin_min_size', 4));
        if (!$pin_min_size) {
            $pin_min_size = 4;
        }
        $pin_max_size = intval(ConfigHelper::getConfig('phpui.pin_max_size', 6));
        if (!$pin_max_size) {
            $pin_max_size = 6;
        }
        if ($pin_min_size > $pin_max_size) {
            $pin_max_size = $pin_min_size;
        }
        $pin_allowed_characters = ConfigHelper::getConfig('phpui.pin_allowed_characters', '0123456789');

        return compact('pin_min_size', 'pin_max_size', 'pin_allowed_characters');
    }

    public function checkCustomerPin($id, $pin)
    {
        if (empty($id)) {
            $oldpin = '';
            $hashed_oldpin = false;
        } else {
            $validate_changed_pin = ConfigHelper::checkConfig('phpui.validate_changed_pin');
            $oldpin = $this->getCustomerPin($id);
            $hashed_oldpin = preg_match('/^\$[0-9a-z]+\$/', $oldpin);
        }

        extract($this->getCustomerPinRequirements());

        if ($hashed_oldpin) {
            if ($pin == ''
                || $validate_changed_pin && password_verify($pin, $oldpin)
                || validate_random_string($pin, $pin_min_size, $pin_max_size, $pin_allowed_characters)) {
                return true;
            } else {
                return trans('Incorrect PIN code!');
            }
        } else {
            if ($pin == '') {
                return trans('PIN code is required!');
            }
            if (empty($id)) {
                return (validate_random_string($pin, $pin_min_size, $pin_max_size, $pin_allowed_characters)
                    ? true
                    : trans('Incorrect PIN code!'));
            } else {
                if ($validate_changed_pin) {
                    if ($pin == $oldpin) {
                        return true;
                    } else {
                        return (validate_random_string($pin, $pin_min_size, $pin_max_size, $pin_allowed_characters)
                            ? true
                            : trans('Incorrect PIN code!'));
                    }
                } else {
                    return (validate_random_string($pin, $pin_min_size, $pin_max_size, $pin_allowed_characters)
                        ? true
                        : trans('Incorrect PIN code!'));
                }
            }
        }
    }

    public function getCustomerTen($id)
    {
        return $this->db->GetOne('SELECT ten FROM customers WHERE id = ?', array($id));
    }

    public function getCustomerSsn($id)
    {
        return $this->db->GetOne('SELECT ssn FROM customers WHERE id = ?', array($id));
    }

    public function changeCustomerType($id, $type)
    {
        $this->db->Execute(
            'UPDATE customers SET type = ? WHERE id = ?',
            array($type, $id)
        );
        if ($this->syslog) {
            $userid = Auth::GetCurrentUser();
            $args = array(
                SYSLOG::RES_USER => $userid,
                SYSLOG::RES_CUST => $id,
                'type' => $type,
            );
            $this->syslog->AddMessage(SYSLOG::RES_CUST, SYSLOG::OPER_UPDATE, $args);
        }
    }

    public function changeCustomerStatus($id, $status)
    {
        $this->db->Execute(
            'UPDATE customers SET status = ? WHERE id = ?',
            array($status, $id)
        );
        if ($this->syslog) {
            $userid = Auth::GetCurrentUser();
            $args = array(
                SYSLOG::RES_USER => $userid,
                SYSLOG::RES_CUST => $id,
                'status' => $status,
            );
            $this->syslog->AddMessage(SYSLOG::RES_CUST, SYSLOG::OPER_UPDATE, $args);
        }
    }

    public function getCustomerCalls(array $params)
    {
        $count = !empty($params['count']);

        if (isset($params['offset'])) {
            $offset = ' OFFSET ' . intval($params['offset']);
        } else {
            $offset = '';
        }

        //$id = null, $limit = -1
        if (!$count && isset($params['limit'])) {
            if ($params['limit'] == -1) {
                $limit = ' LIMIT ' . intval(ConfigHelper::getConfig('phpui.customer_call_limit', 5));
            } else {
                $limit = ' LIMIT ' . intval($params['limit']);
            }
        } else {
            $limit = '';
        }

        if (isset($params['order'])) {
            [$field, $sort] = explode(',', $params['order']);
            switch ($field) {
                case 'id':
                    $order = 'c.id';
                    break;
                default:
                    $order = 'c.dt';
            }
            $order .= ' ' . (strtoupper($sort) == 'DESC' ? 'DESC' : 'ASC');
        } else {
            $order = 'c.dt DESC';
        }

        $join = array();
        $where = array();

        $join[] = 'LEFT JOIN vusers u ON u.id = c.userid';

        if (isset($params['userid'])) {
            $where[] = 'c.userid = ' . intval($params['userid']);
        }

        if (isset($params['assigned']) && $params['assigned'] === 1) {
            $where[] = 'EXISTS (SELECT 1 FROM customercallassignments a WHERE a.customercallid = c.id'
                . (isset($params['customerid']) ? ' AND a.customerid = ' . intval($params['customerid']) : '') . ')';
        } elseif (isset($params['assigned']) && $params['assigned'] === 0) {
            $where[] = 'NOT EXISTS (SELECT 1 FROM customercallassignments a WHERE a.customercallid = c.id'
                . (isset($params['customerid']) ? ' AND a.customerid = ' . intval($params['customerid']) : '') . ')';
        } elseif (isset($params['customerid'])) {
            $where[] = 'EXISTS (SELECT 1 FROM customercallassignments a WHERE a.customercallid = c.id AND a.customerid = '
                . intval($params['customerid']) . ')';
        }

        if (isset($params['phone']) && strlen($params['phone'])) {
            $where[] = 'c.phone LIKE \'%' . $this->db->Escape($params['phone']) . '%\'';
        }

        if (!empty($params['datefrom'])) {
            $where[] = 'c.dt >= ' . intval($params['datefrom']);
        }

        if (!empty($params['dateto'])) {
            $where[] = 'c.dt <= ' . intval($params['dateto']);
        }

        if ($count) {
            return $this->db->GetOne(
                'SELECT COUNT(c.*) FROM customercalls c '
                . implode(' ', $join)
                . (empty($where) ? '' : ' WHERE ' . implode(' AND ', $where))
            );
        } else {
            $calls = $this->db->GetAll(
                'SELECT c.*, u.name AS username, a2.customerid, a2.customerlastname, a2.customername
                FROM customercalls c
                LEFT JOIN (
                    SELECT cca.customercallid,
                        ' . $this->db->GroupConcat('cca.customerid') . ' AS customerid,
                        ' . $this->db->GroupConcat('cv.lastname') . ' AS customerlastname,
                        ' . $this->db->GroupConcat('cv.name') . ' AS customername
                    FROM customercallassignments cca
                    JOIN customerview cv ON cv.id = cca.customerid
                    GROUP BY cca.customercallid
                ) a2 ON a2.customercallid = c.id '
                . implode(' ', $join)
                . (empty($where) ? '' : ' WHERE ' . implode(' AND ', $where))
                . ' ORDER BY ' . $order
                . $limit
                . $offset
            );
        }

        if (!empty($calls)) {
            foreach ($calls as &$call) {
                $call['customers'] = array();
                if (empty($call['customerid'])) {
                    continue;
                }
                $customerid = explode(',', $call['customerid']);
                $customerlastname = explode(',', $call['customerlastname']);
                $customername = explode(',', $call['customername']);
                foreach ($customerid as $idx => $cid) {
                    $call['customers'][] = array(
                        'id' => $cid,
                        'lastname' => $customerlastname[$idx],
                        'name' =>  $customername[$idx],
                    );
                }
            }
            unset($call);
        }
        return $calls;
    }

    public function deleteCustomerCall($customerid, $callid)
    {
        if ($this->db->GetOne(
            'SELECT COUNT(*) FROM customercallassignments WHERE customercallid = ?',
            array($callid)
        ) > 1) {
            return $this->db->Execute(
                'DELETE FROM customerassignmentcalls WHERE customercallid = ? AND customerid = ?',
                array($callid, $customerid)
            );
        } else {
            $customer_call_dir = STORAGE_DIR . DIRECTORY_SEPARATOR . 'customercalls';

            $call = $this->db->GetRow('SELECT * FROM customercalls WHERE id = ?', array($callid));

            if (empty($call)) {
                return false;
            }

            @unlink(
                $customer_call_dir . DIRECTORY_SEPARATOR . date('Y-m-d', $call['dt'])
                    . DIRECTORY_SEPARATOR . $call['filename']
            );
            return $this->db->Execute('DELETE FROM customercalls WHERE id = ?', array($callid));
        }
    }

    public function getCustomerCallContent($callid)
    {
        $call = $this->db->GetRow('SELECT * FROM customercalls WHERE id = ?', array($callid));

        if (empty($call)) {
            die;
        }

        $customer_call_dir = STORAGE_DIR . DIRECTORY_SEPARATOR . 'customercalls'
            . DIRECTORY_SEPARATOR . date('Y-m-d', $call['dt']);

        $file_path = $customer_call_dir . DIRECTORY_SEPARATOR . $call['filename'];
        if (!is_file($file_path) || !is_readable($file_path)) {
            die;
        }

        header('Content-Type: ' . mime_content_type($file_path));

        echo file_get_contents($file_path);
        die;
    }

    public function isCustomerCallExists(array $params)
    {
        if (isset($params['filename'])) {
            return $this->db->GetOne(
                'SELECT id FROM customercalls WHERE filename = ?',
                array($params['filename'])
            ) > 0;
        } else {
            return false;
        }
    }

    public function addCustomerCall(array $params)
    {
        $res = $this->db->Execute(
            'INSERT INTO customercalls (dt, userid, filename, outgoing, phone, duration)
            VALUES (?, ?, ?, ?, ?, ?)',
            array(
                $params['dt'],
                !empty($params['userid']) ? intval($params['userid']) : null,
                $params['filename'],
                empty($params['outgoing']) ? 0 : 1,
                $params['phone'],
                $params['duration'],
            )
        );
        return $res ? $this->db->GetLastInsertID('customercalls') : null;
    }

    public function updateCustomerCall($callid, array $params)
    {
        $res = $this->db->Execute(
            'UPDATE customercalls SET notes = ? WHERE id = ?',
            array(
                empty($params['notes']) ? null : $params['notes'],
                $callid,
            )
        );

        if ($res) {
            if (!empty($params['added-customers'])) {
                foreach ($params['added-customers'] as $customerid) {
                    $this->db->Execute(
                        'INSERT INTO customercallassignments (customercallid, customerid) VALUES (?, ?)',
                        array($callid, $customerid)
                    );
                }
            }
            if (!empty($params['removed-customers'])) {
                $this->db->Execute(
                    'DELETE FROM customercallassignments
                    WHERE customercallid = ? AND customerid IN ?',
                    array($callid, $params['removed-customers'])
                );
            }
        }

        return $res;
    }

    public function addCustomerCallAssignment($customerid, $callid)
    {
        if (!$this->db->GetOne(
            'SELECT id FROM customercallassignments WHERE customercallid = ? AND customerid = ?',
            array($callid, $customerid)
        )) {
            $res = $this->db->Execute(
                'INSERT INTO customercallassignments (customercallid, customerid) VALUES (?, ?)',
                array($callid, $customerid)
            );
            return $res ? $this->db->GetLastInsertID('customercallassignments') : null;
        } else {
            return null;
        }
    }

    public function getCustomerModificationInfo($customerid)
    {
        return $this->db->GetRow(
            'SELECT c.moddate AS date, u.name AS username
            FROM customerview c
            LEFT JOIN vusers u ON u.id = c.modid
            WHERE c.id = ?',
            array($customerid)
        );
    }

    public function getCustomerExternalIDs($customerid, $serviceproviderid = null, $serviceprovidersonly = false)
    {
        $result = $this->db->GetAllByKey(
            'SELECT ce.extid,
                COALESCE(ce.serviceproviderid, 0) AS serviceproviderid,
                sp.name AS serviceprovidername
            FROM customerextids ce
            LEFT JOIN serviceproviders sp ON sp.id = ce.serviceproviderid
            WHERE ce.customerid = ?'
            . (empty($serviceproviderid) ? '' : ' AND ce.serviceproviderid = ' . intval($serviceproviderid))
            . (empty($serviceprovidersonly) ? '' : ' AND ce.serviceproviderid IS NOT NULL'),
            'serviceproviderid',
            array($customerid)
        );
        return empty($result) ? array() : $result;
    }

    public function addCustomerExternalID($customerid, $extid, $serviceproviderid)
    {
        if (!empty($customerid) && !empty($extid) && !empty($serviceproviderid)) {
            return $this->db->Execute(
                'INSERT INTO customerextids (customerid, extid, serviceproviderid)
                       VALUES (?, ?, ?)',
                array(
                    $customerid,
                    Utils::removeInsecureHtml($extid),
                    $serviceproviderid,
                )
            );
        }
    }

    public function updateCustomerExternalID($customerid, $extid, $oldextid, $serviceproviderid, $oldserviceproviderid)
    {
        if (!empty($customerid) && !empty($extid) && !empty($serviceproviderid)) {
            return $this->db->Execute(
                'UPDATE customerextids SET extid = ?, serviceproviderid = ?
                WHERE customerid = ?
                AND extid = ?
                AND serviceproviderid = ?',
                array(
                    Utils::removeInsecureHtml($extid),
                    $serviceproviderid,
                    $customerid,
                    $oldextid,
                    $oldserviceproviderid
                )
            );
        }
    }

    public function updateCustomerExternalIDs($customerid, array $customerextids, $only_passed_service_providers = false)
    {
        if ($only_passed_service_providers) {
            $service_providers = array();
            foreach ($customerextids as $customerextid) {
                $serviceproviderid = $customerextid['serviceproviderid'];
                $service_providers[$serviceproviderid] = $serviceproviderid;
            }
        }

        $current_customerextids = $this->getCustomerExternalIDs($customerid);

        if (!empty($service_providers)) {
            $current_customerextids = array_filter($current_customerextids, function ($customerextid) use ($service_providers) {
                $serviceprovider = $customerextid['serviceproviderid'];
                return isset($service_providers[$serviceprovider]);
            });
        }

        $modifications = 0;

        foreach ($customerextids as $customerextid) {
            $serviceproviderid = $customerextid['serviceproviderid'];
            if (isset($current_customerextids[$serviceproviderid])) {
                if ($customerextid['extid'] == $current_customerextids[$serviceproviderid]['extid']) {
                    $modifications++;
                } else {
                    if (empty($serviceproviderid)) {
                        $result = $this->db->Execute(
                            'UPDATE customerextids SET extid = ? WHERE customerid = ? AND serviceproviderid IS NULL',
                            array(
                                $customerextid['extid'],
                                $customerid,
                            )
                        );
                    } else {
                        $result = $this->db->Execute(
                            'UPDATE customerextids SET extid = ? WHERE customerid = ? AND serviceproviderid = ?',
                            array(
                                $customerextid['extid'],
                                $customerid,
                                $serviceproviderid,
                            )
                        );
                    }
                    if (empty($result)) {
                        return null;
                    } else {
                        $modifications += $result;
                    }
                }
            } else {
                $result = $this->db->Execute(
                    'INSERT INTO customerextids (customerid, extid, serviceproviderid)
                       VALUES (?, ?, ?)',
                    array(
                        $customerid,
                        $customerextid['extid'],
                        empty($serviceproviderid) ? null : $serviceproviderid,
                    )
                );
                if (empty($result)) {
                    return null;
                } else {
                    $modifications += $result;
                }
            }
        }

        foreach ($current_customerextids as $customerextid) {
            $serviceproviderid = $customerextid['serviceproviderid'];
            if (!isset($customerextids[$serviceproviderid])) {
                if (empty($serviceproviderid)) {
                    $result = $this->db->Execute(
                        'DELETE FROM customerextids WHERE customerid = ? AND serviceproviderid IS NULL',
                        array(
                            $customerid,
                        )
                    );
                } else {
                    $result = $this->db->Execute(
                        'DELETE FROM customerextids WHERE customerid = ? AND serviceproviderid = ?',
                        array(
                            $customerid,
                            $serviceproviderid,
                        )
                    );
                }
                if (empty($result)) {
                    return null;
                } else {
                    $modifications += $result;
                }
            }
        }

        return $modifications;
    }

    public function deleteCustomerExternalID($customerid, $extid, $serviceproviderid)
    {
        if (!empty($customerid) && !empty($extid) && !empty($serviceproviderid)) {
            $this->db->Execute(
                'DELETE FROM customerextids WHERE customerid = ? AND extid = ? AND serviceproviderid = ?',
                array(
                    $customerid,
                    strval($extid),
                    $serviceproviderid,
                )
            );
        }
    }

    public function getServiceProviders()
    {
        $result = $this->db->GetAllByKey('SELECT * FROM serviceproviders ORDER BY name', 'id');
        return empty($result) ? array() : $result;
    }
}
