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

check_file_uploads();

function getMessageTemplate($tmplid, $subjectelem, $messageelem)
{
    global $DB;

    $result = new xajaxResponse();
    $row = $DB->GetRow('SELECT subject, message FROM templates WHERE id = ?', array($tmplid));
    $result->call('messageTemplateReceived', $subjectelem, $row['subject'], $messageelem, $row['message']);

    return $result;
}

function getMessageTemplates($tmpltype)
{
    global $LMS;

    $result = new xajaxResponse();
    $templates = $LMS->GetMessageTemplates($tmpltype);
    $result->call('messageTemplatesReceived', $templates);

    return $result;
}

$LMS->InitXajax();
$LMS->RegisterXajaxFunction(array('getMessageTemplate', 'getMessageTemplates'));
$SMARTY->assign('xajax', $LMS->RunXajax());

function GetRecipients($filter, $type = MSG_MAIL)
{
    global $LMS;

    $state = intval($filter['state']);

    if (!empty($filter['division'])) {
        $division = intval($filter['division']);
    }

    if (empty($filter['network'])) {
        $networks = array();
    } elseif (is_array($filter['network'])) {
        $networks = Utils::filterIntegers($filter['network']);
    } else {
        $networks = Utils::filterIntegers(array($filter['network']));
    }

    if (empty($filter['customergroup'])) {
        $customergroup = null;
    } elseif (is_array($filter['customergroup'])) {
        $customergroup = implode(',', Utils::filterIntegers($filter['customergroup']));
    } else {
        $customergroup = intval($filter['customergroup']);
    }
    if (empty($filter['nodegroup'])) {
        $nodegroup = null;
    } elseif (is_array($filter['nodegroup'])) {
        $nodegroup = implode(',', Utils::filterIntegers($filter['nodegroup']));
    } else {
        $nodegroup = intval($filter['nodegroup']);
    }
    $linktype = $filter['linktype'] == '' ? '' : intval($filter['linktype']);
    $tarifftype = intval($filter['tarifftype']);
    $consent = isset($filter['consent']);
    $email_options = isset($filter['email-options']) ? intval($filter['email-options']) : 0;
    $phone_options = isset($filter['phone-options']) ? intval($filter['phone-options']) : 0;
    $netdevices = $filter['netdevices'] ?? null;

    if ($state == 50) {
        $deleted = 1;
        $networks = array();
        $customergroup = null;
    } else {
        $deleted = 0;
    }

    $disabled = 0;
    $indebted = 0;
    $not_indebted = 0;
    $indebted2 = 0;
    $indebted3 = 0;
    $unapproved_documents = 0;
    $expired_indebted = 0;
    $expired_not_indebted = 0;
    $expired_indebted2 = 0;
    $expired_indebted3 = 0;
    $contracts = 0;

    $expired_days = 0;

    $archived_document_condition = '';
    $document_condition = '';

    switch ($state) {
        case 51:
            $disabled = 1;
            break;
        case 52:
            $indebted = 1;
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
        case 76:
        case 77:
        case 78:
            $contracts_expiration_type = ConfigHelper::getConfig('contracts.expiration_type', 'documents');
            if ($state >= 76) {
                $contracts = $state - 75;
                if ($contracts_expiration_type == 'documents') {
                    $archived_document_condition = ' AND d.archived = 0';
                }
            } else {
                $contracts = $state - 58;
            }
            $contracts_days = intval(ConfigHelper::getConfig('contracts.contracts_days'));
            if ($contracts == 1) {
                if ($contracts_expiration_type == 'documents') {
                    $document_condition = ' AND d.customerid IS NULL';
                } else {
                    $document_condition = ' AND ass.customerid IS NULL';
                }
            }
            break;
        case 153:
            $not_indebted = 1;
            break;
        case 159:
            $unapproved_documents = 1;
            break;
        case 160:
            $expired_not_indebted = 1;
            break;
        case 161:
            $expired_indebted = 1;
            break;
        case 162:
            $expired_indebted2 = 1;
            break;
        case 163:
            $expired_indebted3 = 1;
            break;
        case 164:
            $expired_indebted = 1;
            $expired_days = 30;
            break;
        case 165:
            $expired_indebted = 1;
            $expired_days = 60;
            break;
    }

    if ($state >= 50) {
        $state = 0;
    }

    if (!empty($networks)) {
        $network_where = array();
        foreach ($networks as $network) {
            $net = $LMS->GetNetworkParams($network);
            $network_where[] = '(netid = ' . $net['id'] . ' AND ipaddr > ' . $net['address'] . ' AND ipaddr < ' . $net['broadcast'] . ')';
            $network_where[] = '(ipaddr_pub > ' . $net['address'] . ' AND ipaddr_pub < ' . $net['broadcast'] . ')';
        }
        $network_condition = ' AND c.id IN (SELECT ownerid FROM vnodes WHERE ' . implode(' OR ', $network_where) . ')';
    } else {
        $network_condition = '';
    }

    if ($type == MSG_SMS) {
        $smstable = 'JOIN (SELECT ' . $LMS->DB->GroupConcat('contact') . ' AS phone, customerid
				FROM customercontacts
				WHERE ((type & ' . (CONTACT_MOBILE | CONTACT_DISABLED | $phone_options) . ') = ' . (CONTACT_MOBILE | $phone_options) . ' )
				GROUP BY customerid
			) x ON (x.customerid = c.id) ';
    } elseif ($type == MSG_MAIL) {
        $mailtable = 'JOIN (SELECT ' . $LMS->DB->GroupConcat('contact') . ' AS email, customerid
				FROM customercontacts
				WHERE ((type & ' . (CONTACT_EMAIL | CONTACT_DISABLED | $email_options) . ') = ' . (CONTACT_EMAIL | $email_options) . ')
				GROUP BY customerid
			) cc ON (cc.customerid = c.id) ';
    }

    if ($tarifftype) {
        $tarifftable = 'JOIN (
			SELECT DISTINCT a.customerid FROM assignments a
			JOIN tariffs t ON t.id = a.tariffid
			WHERE a.suspended = 0
				AND (a.datefrom = 0 OR a.datefrom < ?NOW?)
				AND (a.dateto = 0 OR a.dateto > ?NOW?)
				AND t.type = ' . $tarifftype . '
		) a ON a.customerid = c.id ';
    }

    $deadline = ConfigHelper::getConfig('payments.deadline', ConfigHelper::getConfig('invoices.paytime', 0));

    if (!empty($netdevices)) {
        $netdevtable = ' JOIN (
				SELECT DISTINCT n.ownerid FROM nodes n
				WHERE n.ownerid IS NOT NULL AND netdev IN (' . implode(',', $netdevices) . ')
			) nd ON nd.ownerid = c.id ';
    }

    $suspension_percentage = f_round(ConfigHelper::getConfig('payments.suspension_percentage', ConfigHelper::getConfig('finances.suspension_percentage', 0)));

    $recipients = $LMS->DB->GetAll(
        'SELECT c.id, pin, c.divisionid, '
            . ($type == MSG_MAIL ? 'cc.email, ' : '')
            . ($type == MSG_SMS ? 'x.phone, ' : '')
            . $LMS->DB->Concat('c.lastname', "' '", 'c.name') . ' AS customername,
            divisions.account,
            acc.alternative_accounts,
            COALESCE(b.balance, 0) AS totalbalance,
            b2.balance AS balance
        FROM customerview c
        LEFT JOIN divisions ON divisions.id = c.divisionid
        LEFT JOIN (
            SELECT ' . $LMS->DB->GroupConcat('contact') . ' AS alternative_accounts, customerid
            FROM customercontacts
            WHERE (type & ?) = ?
            GROUP BY customerid
        ) acc ON acc.customerid = c.id
        LEFT JOIN customerbalances b ON b.customerid = c.id
        LEFT JOIN (
            SELECT cash.customerid, SUM(value * cash.currencyvalue) AS balance FROM cash
            LEFT JOIN customers ON customers.id = cash.customerid
            LEFT JOIN divisions ON divisions.id = customers.divisionid
            LEFT JOIN documents d ON d.id = cash.docid
            LEFT JOIN (
                SELECT SUM(value * cash.currencyvalue) AS totalvalue, docid FROM cash
                JOIN documents ON documents.id = cash.docid
                WHERE documents.type = ?
                GROUP BY docid
            ) tv ON tv.docid = cash.docid
            WHERE (cash.docid IS NULL AND ((cash.type <> 0 AND cash.time < ?NOW?)
                OR (cash.type = 0 AND cash.value > 0 AND cash.time < ?NOW?)
                OR (cash.type = 0 AND cash.time + ((CASE customers.paytime WHEN -1 THEN
                    (CASE WHEN divisions.inv_paytime IS NULL THEN ' . $deadline . ' ELSE divisions.inv_paytime END) ELSE customers.paytime END) + ' . $expired_days . ') * 86400 < ?NOW?)))
                OR (cash.docid IS NOT NULL AND ((d.type = ? AND cash.time < ?NOW?)
                    OR (d.type = ? AND cash.time < ?NOW? AND tv.totalvalue >= 0)
                    OR (((d.type = ? AND tv.totalvalue < 0)
                        OR d.type IN (?, ?, ?)) AND d.cdate + (d.paytime + ' . $expired_days . ') * 86400 < ?NOW?)))
            GROUP BY cash.customerid
        ) b2 ON b2.customerid = c.id
		LEFT JOIN (SELECT a.customerid,
			SUM(
				(CASE a.suspended
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
				* a.count
			) AS value 
			FROM assignments a
			LEFT JOIN tariffs t ON (t.id = a.tariffid)
			LEFT JOIN liabilities l ON (l.id = a.liabilityid AND a.period != ' . DISPOSABLE . ')
			WHERE a.datefrom <= ?NOW? AND (a.dateto > ?NOW? OR a.dateto = 0) 
			GROUP BY a.customerid
        ) t ON (t.customerid = c.id) '
        . ($contracts == 1 ?
            ($contracts_expiration_type == 'documents' ?
                'LEFT JOIN (
                    SELECT COUNT(*), d.customerid FROM documents d
                    JOIN documentcontents dc ON dc.docid = d.id
                    WHERE d.type IN (' . DOC_CONTRACT . ',' . DOC_ANNEX . ')'
                    . $archived_document_condition
                    . ' GROUP BY d.customerid
                ) d ON d.customerid = c.id ' :
                'LEFT JOIN (
                    SELECT customerid
                    FROM assignments
                    WHERE dateto > 0
                    GROUP BY customerid
                    HAVING MAX(dateto) < ?NOW?
                ) ass ON ass.customerid = c.id ') :
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
                ) d ON d.customerid = c.id ' :
                'JOIN (
                    SELECT customerid
                    FROM assignments
                    WHERE dateto > 0
                    GROUP BY customerid
                    HAVING MAX(dateto) < ?NOW?
                ) ass ON ass.customerid = c.id ') :
        ($contracts == 3 ?
            ($contracts_expiration_type == 'documents' ?
                'JOIN (
                    SELECT DISTINCT d.customerid FROM documents d
                    JOIN documentcontents dc ON dc.docid = d.id
                    WHERE dc.todate >= ?NOW? AND dc.todate <= ?NOW? + 86400 * ' . $contracts_days . '
                        AND type IN (' . DOC_CONTRACT . ',' . DOC_ANNEX . ')'
                    . $archived_document_condition
                    . '
                ) d ON d.customerid = c.id ' :
                'JOIN (
                    SELECT customerid
                    FROM assignments
                    WHERE dateto > 0
                    GROUP BY customerid
                    HAVING MAX(dateto) >= ?NOW? AND MAX(dateto) <= ?NOW? + 86400 * ' . $contracts_days . '
                ) ass ON ass.customerid = c.id ') : '')))
        . ($netdevtable ?? '')
        . ($mailtable ?? '')
        . ($smstable ?? '')
        . ($tarifftype ? $tarifftable : '')
        .'WHERE deleted = ' . $deleted
        . ($consent ? ' AND ' . ($type == MSG_SMS || $type == MSG_ANYSMS ? 'c.smsnotice' : 'c.mailingnotice') . ' = 1' : '')
        . ($type == MSG_WWW ? ' AND c.id IN (SELECT DISTINCT ownerid FROM nodes)' : '')
        . ($state != 0 ? ' AND c.status = ' . $state : '')
        . (empty($division) ? '' : ' AND c.divisionid = ' . $division)
        . $document_condition
        . $network_condition
        .($customergroup ? ' AND c.id IN (SELECT customerid FROM vcustomerassignments
			WHERE customergroupid IN (' . $customergroup . '))' : '')
        .($nodegroup ? ' AND c.id IN (SELECT ownerid FROM vnodes
			JOIN nodegroupassignments ON nodeid = vnodes.id
			WHERE nodegroupid IN (' . $nodegroup . '))' : '')
        .($linktype != '' ? ' AND c.id IN (SELECT ownerid FROM vnodes
			WHERE linktype = ' . $linktype . ')' : '')
        .($disabled ? ' AND EXISTS (SELECT 1 FROM vnodes WHERE ownerid = c.id
			GROUP BY ownerid HAVING (SUM(access) != COUNT(access)))' : '')
        . ($indebted ? ' AND COALESCE(b.balance, 0) < 0' : '')
        . ($indebted2 ? ' AND t.value > 0 AND COALESCE(b.balance, 0) < -t.value' : '')
        . ($indebted3 ? ' AND t.value > 0 AND COALESCE(b.balance, 0) < -t.value * 2' : '')
        . ($not_indebted ? ' AND COALESCE(b.balance, 0) >= 0' : '')
        . ($expired_indebted ? ' AND COALESCE(b2.balance, 0) < 0' : '')
        . ($expired_indebted2 ? ' AND t.value > 0 AND COALESCE(b2.balance, 0) < -t.value' : '')
        . ($expired_indebted3 ? ' AND t.value > 0 AND COALESCE(b2.balance, 0) < -t.value * 2' : '')
        . ($expired_not_indebted ? ' AND COALESCE(b2.balance, 0) >= 0' : '')
        . ($unapproved_documents ? ' AND c.id IN (SELECT DISTINCT customerid FROM documents
			WHERE documents.closed = 0
				AND documents.type < 0)' : '')
        . ($tarifftype ? ' AND NOT EXISTS (SELECT id FROM assignments
			WHERE customerid = c.id AND tariffid IS NULL AND liabilityid IS NULL
				AND (datefrom = 0 OR datefrom < ?NOW?)
				AND (dateto = 0 OR dateto > ?NOW?))' : '')
        .' ORDER BY c.divisionid, customername',
        array(
            CONTACT_BANKACCOUNT | CONTACT_INVOICES | CONTACT_DISABLED,
            CONTACT_BANKACCOUNT | CONTACT_INVOICES,
            DOC_CNOTE,
            DOC_RECEIPT,
            DOC_CNOTE,
            DOC_CNOTE,
            DOC_INVOICE,
            DOC_INVOICE_PRO,
            DOC_DNOTE,
        )
    );

    return $recipients;
}

function GetCustomers($customers)
{
    $DB = LMSDB::getInstance();

    $deadline = intval(ConfigHelper::getConfig('payments.deadline', ConfigHelper::getConfig('invoices.paytime', 0)));

    return $DB->GetAllByKey(
        'SELECT c.id, pin, c.divisionid, '
            . $DB->Concat('c.lastname', "' '", 'c.name') . ' AS customername,
            divisions.account,
            acc.alternative_accounts,
            COALESCE(b.balance, 0) AS totalbalance,
            b2.balance AS balance
        FROM customerview c
        LEFT JOIN divisions ON divisions.id = c.divisionid
        LEFT JOIN (
            SELECT ' . $DB->GroupConcat('contact') . ' AS alternative_accounts, customerid
            FROM customercontacts
            WHERE (type & ?) = ?
            GROUP BY customerid
        ) acc ON acc.customerid = c.id
        LEFT JOIN customerbalances b ON b.customerid = c.id
        LEFT JOIN (
            SELECT cash.customerid, SUM(value * cash.currencyvalue) AS balance FROM cash
            LEFT JOIN customers ON customers.id = cash.customerid
            LEFT JOIN divisions ON divisions.id = customers.divisionid
            LEFT JOIN documents d ON d.id = cash.docid
            LEFT JOIN (
                SELECT SUM(value * cash.currencyvalue) AS totalvalue, docid FROM cash
                JOIN documents ON documents.id = cash.docid
                WHERE documents.type = ?
                GROUP BY docid
            ) tv ON tv.docid = cash.docid
            WHERE (cash.docid IS NULL AND ((cash.type <> 0 AND cash.time < ?NOW?)
                OR (cash.type = 0 AND cash.value > 0 AND cash.time < ?NOW?)
                OR (cash.type = 0 AND cash.time + (CASE customers.paytime WHEN -1 THEN
                    (CASE WHEN divisions.inv_paytime IS NULL THEN ' . $deadline . ' ELSE divisions.inv_paytime END) ELSE customers.paytime END) * 86400 < ?NOW?)))
                OR (cash.docid IS NOT NULL AND ((d.type = ? AND cash.time < ?NOW?)
                    OR (d.type = ? AND cash.time < ?NOW? AND tv.totalvalue >= 0)
                    OR (((d.type = ? AND tv.totalvalue < 0)
                        OR d.type IN (?, ?, ?)) AND d.cdate + d.paytime * 86400 < ?NOW?)))
            GROUP BY cash.customerid
        ) b2 ON b2.customerid = c.id
        WHERE c.id IN ?
        ORDER BY c.divisionid, customername',
        'id',
        array(
            CONTACT_BANKACCOUNT | CONTACT_INVOICES | CONTACT_DISABLED,
            CONTACT_BANKACCOUNT | CONTACT_INVOICES,
            DOC_CNOTE,
            DOC_RECEIPT,
            DOC_CNOTE,
            DOC_CNOTE,
            DOC_INVOICE,
            DOC_INVOICE_PRO,
            DOC_DNOTE,
            $customers
        )
    );
}

function replaceSymbols(&$content, $data, $format)
{
    static $use_only_alternative_accounts = null,
        $use_all_accounts = null;

    global $LMS;

    $data['services'] = isset($data['id']) ? $LMS->GetCustomerServiceSummary($data['id']) : array();

    $hook_data = $LMS->ExecuteHook('messageadd_data_parser', array(
        'data' => $data
    ));
    $data = $hook_data['data'];

    if (!isset($data['balance'])) {
        $data['balance'] = 0;
    }
    $amount = -$data['balance'];
    if (!isset($data['totalbalance'])) {
        $data['totalbalance'] = 0;
    }
    $totalamount = -$data['totalbalance'];

    if (strpos($content, '%bankaccount') !== false) {
        if (!isset($use_only_alternative_accounts)) {
            $use_only_alternative_accounts = ConfigHelper::checkConfig('messages.use_only_alternative_accounts');
            $use_all_accounts = ConfigHelper::checkConfig('messages.use_all_accounts');
        }

        $alternative_accounts = isset($data['alternative_accounts']) && strlen($data['alternative_accounts'])
            ? explode(',', $data['alternative_accounts'])
            : array();

        if (!$use_only_alternative_accounts || empty($alternative_accounts)) {
            $accounts = array(bankaccount($data['id'], $data['account']));
        } else {
            $accounts = array();
        }

        if ($use_all_accounts || $use_only_alternative_accounts) {
            $accounts = array_merge($accounts, $alternative_accounts);
        }
        foreach ($accounts as &$account) {
            $account = format_bankaccount($account);
        }
        unset($account);

        $all_accounts = implode($format == 'text' ? "\n" : '<br>', $accounts);

        $content = str_replace('%bankaccount', $all_accounts, $content);
    }

    [$now_year, $now_month, $now_day] = explode('/', date('Y/m/d'));

    $currency = Localisation::getCurrentCurrency();

    if ($data['totalbalance'] < 0) {
        $commented_balance = trans('Billing status: $a (to pay)', moneyf(-$data['totalbalance'], $currency));
    } elseif ($data['totalbalance'] > 0) {
        $commented_balance = trans('Billing status: $a (excess payment or to repay)', moneyf($data['totalbalance'], $currency));
    } else {
        $commented_balance = trans('Billing status: $a', moneyf($data['totalbalance'], $currency));
    }

    $content = str_replace(
        array(
            '%date-y',
            '%date-m',
            '%date-d',
            '%balance',
            '%commented_balance',
            '%b',
            '%totalb',
            '%totalB',
            '%totalsaldo',
            '%B',
            '%saldo',
            '%customer',
            '%cid',
            '%pin',
        ),
        array(
            $now_year,
            $now_month,
            $now_day,
            moneyf($data['totalbalance'], $currency),
            $commented_balance,
            sprintf('%01.2f', $amount),
            sprintf('%01.2f', $totalamount),
            sprintf('%01.2f', $data['totalbalance']),
            moneyf($data['totalbalance'], $currency),
            sprintf('%01.2f', $data['balance']),
            moneyf($data['balance'], $currency),
            $data['customername'] ?? '',
            $data['id'] ?? '',
            $data['pin'] ?? '',
        ),
        $content
    );

    if (isset($data['node'])) {
        $macs = array();
        if (!empty($data['node']['macs'])) {
            foreach ($data['node']['macs'] as $mac) {
                $macs[] = $mac['mac'];
            }
        }
        $content = str_replace(
            array(
                '%node_name',
                '%node_login',
                '%node_password',
                '%node_ip_pub',
                '%node_ip',
                '%node_mac',
            ),
            array(
                $data['node']['name'],
                empty($data['node']['login']) ? $data['node']['name'] : $data['node']['login'],
                $data['node']['passwd'] ?: '-',
                $data['node']['ipaddr_pub'] ? $data['node']['ip_pub'] : '-',
                $data['node']['ipaddr'] ? $data['node']['ip'] : '-',
                empty($macs) ? '-' : implode(', ', $macs),
            ),
            $content
        );
    } else {
        $content = str_replace(
            array('%node_name', '%node_password', '%node_ip_pub', '%node_ip', '%node_mac'),
            array('-', '-', '-', '-', '-'),
            $content
        );
    }

    if (isset($data['id'])) {
        $content = $LMS->getLastNInTable($content, $data['id'], $format, ConfigHelper::checkConfig('phpui.aggregate_documents'));
    }

    if (strpos($content, '%services') !== false) {
        $services = $data['services'];
        $lN = '';
        if (!empty($services)) {
            $lN .= strtoupper(trans("Total:"))  . " " . moneyf($services['total_value'], $currency)
                . ($format == 'html' ? '<br>' : PHP_EOL);
            unset($services['total_value']);
            foreach ($services as $row) {
                $lN .= strtoupper($row['tarifftypename']) .": ";
                $lN .= moneyf($row['sumvalue'], $currency) . ($format == 'html' ? '<br>' : PHP_EOL);
            }
        }
        $content = str_replace('%services', $lN, $content);
    }
}

function FindNetDeviceUplink($netdevid)
{
    static $uplink_netdev = null;
    static $visited = array();
    static $root_netdevid = null;
    static $netdev_links = null;
    static $DB = null;

    if (is_null($DB)) {
        $DB = LMSDB::getInstance();
    }

    if (empty($root_netdevid)) {
        $root_netdevid = ConfigHelper::getConfig('phpui.root_netdevice_id');
    }

    if (is_null($netdev_links)) {
        $netlinks = $DB->GetAll('SELECT id, src, dst FROM netlinks');
        if (!empty($netlinks)) {
            foreach ($netlinks as $netlink) {
                if (!isset($netdev_links[$netlink['src']])) {
                    $netdev_links[$netlink['src']] = array();
                }
                $netdev_links[$netlink['src']][] = $netlink['dst'];
                if (!isset($netdev_links[$netlink['dst']])) {
                    $netdev_links[$netlink['dst']] = array();
                }
                $netdev_links[$netlink['dst']][] = $netlink['src'];
            }
        } else {
            $netdev_links = array();
        }
    }

    $visited[$netdevid] = true;

    if ($root_netdevid == $netdevid) {
        return $uplink_netdev;
    }

    if (!isset($netdev_links[$netdevid])) {
        return null;
    }

    foreach ($netdev_links[$netdevid] as $netdev) {
        if (isset($visited[$netdev])) {
            continue;
        }

        if ($netdev == $root_netdevid) {
            return $netdev;
        } else {
            $uplink_netdev = FindNetDeviceUplink($netdev);
        }
        if (!empty($uplink_netdev)) {
            return $netdev;
        }
    }

    return $uplink_netdev;
}

function GetNetDevicesInSubtree($netdevid)
{
    static $uplink_netdev = null;
    static $netdevices = array();
    static $visited = array();
    static $netdev_links = null;
    static $DB = null;

    if (is_null($uplink_netdev)) {
        $uplink_netdev = FindNetDeviceUplink($netdevid);
        if (empty($uplink_netdev)) {
            $uplink_netdev = 0;
        }
    }

    if (is_null($DB)) {
        $DB = LMSDB::getInstance();
    }

    if (is_null($netdev_links)) {
        $netlinks = $DB->GetAll('SELECT id, src, dst FROM netlinks');
        if (!empty($netlinks)) {
            foreach ($netlinks as $netlink) {
                if (!isset($netdev_links[$netlink['src']])) {
                    $netdev_links[$netlink['src']] = array();
                }
                $netdev_links[$netlink['src']][] = $netlink['dst'];
                if (!isset($netdev_links[$netlink['dst']])) {
                    $netdev_links[$netlink['dst']] = array();
                }
                $netdev_links[$netlink['dst']][] = $netlink['src'];
            }
        } else {
            $netdev_links = array();
        }
    }

    $netdevices[] = $netdevid;
    $visited[$netdevid] = true;

    if (!isset($netdev_links[$netdevid])) {
        return array();
    }

    foreach ($netdev_links[$netdevid] as $netdev) {
        if ($netdev == $uplink_netdev || isset($visited[$netdev])) {
            continue;
        }
        GetNetDevicesInSubtree($netdev);
    }

    return $netdevices;
}

$layout['pagetitle'] = trans('Message Add');

$divisions = $LMS->getDivisionList();
$division_count = 0;
foreach ($divisions as $division) {
    if (!empty($division['cnt'])) {
        $division_count++;
    }
}

$userinfo = $LMS->GetUserInfo(Auth::GetCurrentUser());
$sender_name = ConfigHelper::getConfig('messages.sender_name', $userinfo['name']);
$default_send_attempts = intval(ConfigHelper::getConfig('messages.default_send_attempts', 3));

$SMARTY->assign(
    array(
        'division_count' => $division_count,
        'sender_name' => $sender_name,
        'default_send_attempts' => $default_send_attempts,
    )
);

if (isset($_POST['message']) && !isset($_GET['sent'])) {
    $message = $_POST['message'];

    $message['netdevices'] = array();
    if (!empty($message['netdev'])) {
        if (isset($message['wholesubtree'])) {
            $message['netdevices'] = GetNetDevicesInSubtree($message['netdev']);
        } else {
            $message['netdevices'][] = $message['netdev'];
        }
    }

    if (!in_array($message['type'], array(MSG_MAIL, MSG_SMS, MSG_ANYSMS, MSG_WWW, MSG_USERPANEL))) {
        $message['type'] = MSG_USERPANEL_URGENT;
    }

    if (empty($message['customerid']) && isset($message['state']) && ($message['state'] < 0 || $message['state'] > 165
        || ($message['state'] > CSTATUS_LAST && $message['state'] < 50))) {
        $error['state'] = trans('Incorrect recipient group!');
    }

    $html_format = isset($message['wysiwyg']) && isset($message['wysiwyg']['mailbody']) && ConfigHelper::checkValue($message['wysiwyg']['mailbody']);

    $startdate = $attempts = null;

    if ($message['type'] == MSG_MAIL) {
        $message['body'] = $html_format ? Utils::removeInsecureHtml($message['mailbody']) : $message['mailbody'];
        if ($division_count <= 1) {
            if ($message['sender'] == '') {
                $error['sender'] = trans('Sender e-mail is required!');
            } elseif (!check_email($message['sender'])) {
                $error['sender'] = trans('Specified e-mail is not correct!');
            }
        }
        $message['sender'] = empty($message['sender'])
            ? ConfigHelper::getConfig(
                'messages.sender_email',
                ConfigHelper::getConfig('phpui.message_sender_email', $userinfo['email'])
            )
            : $message['sender'];
        if ($message['from'] == '') {
            $error['from'] = trans('Sender name is required!');
        }
    } elseif ($message['type'] == MSG_WWW || $message['type'] == MSG_USERPANEL || $message['type'] == MSG_USERPANEL_URGENT) {
        $message['body'] = $html_format ? Utils::removeInsecureHtml($message['mailbody']) : $message['mailbody'];
    } else {
        $message['body'] = $message['smsbody'];
        $message['sender'] = '';
        $message['from'] = '';
        $phonenumbers = array();
        if ($message['type'] == MSG_ANYSMS) {
            $message['phonenumber'] = preg_replace('/[ \t]/', '', $message['phonenumber']);
            if (preg_match('/^[\+]?[0-9]+(,[\+]?[0-9]+)*$/', $message['phonenumber'])) {
                $phonenumbers = preg_split('/,/', $message['phonenumber']);
            }
            if (!empty($message['users']) && count($message['users'])) {
                $user_phones =
                    $DB->GetAllByKey('SELECT id, phone FROM users', 'id');
                foreach ($message['users'] as $userid) {
                    if (isset($user_phones[$userid])) {
                        $phonenumbers[] = $user_phones[$userid]['phone'];
                    }
                }
                $phonenumbers = array_unique($phonenumbers);
            }
            if (empty($phonenumbers)) {
                $error['phonenumber'] = trans('Specified phone number is not correct!');
            }
        }
    }

    if (($message['type'] == MSG_MAIL || $message['type'] == MSG_SMS || $message['type'] == MSG_ANYSMS)
        && strlen($message['startdate'])) {
        $startdate = datetime_to_timestamp($message['startdate']);
        if (empty($startdate)) {
            $error['startdate'] = trans('Incorrect date format!');
        } else {
            $message['startdate'] = $startdate;
        }

        $attempts = filter_var(
            $message['attempts'],
            FILTER_VALIDATE_INT,
            array(
                'options' => array(
                    'min_range' => 1,
                    'max_range' => 10,
                ),
            )
        );
        if ($attempts === false) {
            $error['attempts'] = trans('Incorrect value!');
        }
    }

    $msgtmplid = intval($message['tmplid']);
    $msgtmploper = intval($message['tmploper']);
    $msgtmplname = $message['tmplname'];
    if (!isset($_GET['count_recipients']) && $msgtmploper > 1) {
        switch ($message['type']) {
            case MSG_MAIL:
                $msgtmpltype = TMPL_MAIL;
                break;
            case MSG_SMS:
            case MSG_ANYSMS:
                $msgtmpltype = TMPL_SMS;
                break;
            case MSG_WWW:
                $msgtmpltype = TMPL_WWW;
                break;
            case MSG_USERPANEL:
                $msgtmpltype = TMPL_USERPANEL;
                break;
            case MSG_USERPANEL_URGENT:
                $msgtmpltype = TMPL_USERPANEL_URGENT;
                break;
        }
        switch ($msgtmploper) {
            case 2:
                if (empty($msgtmplid)) {
                    break;
                }
                $LMS->UpdateMessageTemplate($msgtmplid, $msgtmpltype, null, $message['subject'], null, null, $message['body']);
                break;
            case 3:
                if (!strlen($msgtmplname)) {
                    break;
                }
                if (!$LMS->MessageTemplateExists($msgtmpltype, $msgtmplname)) {
                    $LMS->AddMessageTemplate(
                        $msgtmpltype,
                        $msgtmplname,
                        $message['subject'],
                        null,
                        null,
                        $message['body']
                    );
                }
                break;
        }
    }

    if ($message['subject']=='') {
        $error['subject'] = trans('Message subject is required!');
    }

    if ($message['body'] == '') {
        if (in_array($message['type'], array(MSG_SMS, MSG_ANYSMS))) {
            $error['smsbody'] = trans('Message body is required!');
        } else {
            $error['mailbody'] = trans('Message body is required!');
        }
    }

    if (!$error) {
        $recipients = array();
        if (!isset($message['customermode'])) {
            if ($message['type'] != MSG_ANYSMS) {
                $recipients = GetRecipients($message, $message['type']);
            } else {
                foreach ($phonenumbers as $phone) {
                    $recipients[]['phone'] = $phone;
                }
            }
        } else {
            $customers = array();
            if (!empty($message['customers'])) {
                foreach ($message['customers'] as $customerid => &$customer) {
                    if ($message['type'] == MSG_SMS || $message['type'] == MSG_MAIL) {
                        $msg_idx = $message['type'] == MSG_SMS ? 'phones' : 'emails';
                        if (!empty($customer[$msg_idx])) {
                            foreach ($customer[$msg_idx] as $contactid => $contact) {
                                if (!empty($contact)) {
                                    $customers[] = $customerid;
                                } else {
                                    unset($customer[$message['type'] == MSG_SMS ? 'phones' : 'emails'][$contactid]);
                                }
                            }
                        }
                    } else {
                        $customers[] = $customerid;
                    }
                }
                unset($customer);
            }
            $customers = array_unique($customers);

            if (empty($customers)) {
                $recipients = array();
            } else {
                $recipients = GetCustomers($customers);
            }

            if (isset($recipients) && count($recipients) == 1 && !empty($message['nodeid'])) {
                $recipient = array_shift($recipients);
                $recipient['node'] = $LMS->GetNode($message['nodeid']);
                $recipients[$recipient['id']] = $recipient;
            }

            if ($message['type'] == MSG_ANYSMS) {
                $customer = array_shift($recipients);
                foreach ($phonenumbers as $phone) {
                    $recipients[]['phone'] = $phone;
                }
            } else {
                if (!empty($recipients)) {
                    foreach ($recipients as $customerid => &$recipient) {
                        switch ($message['type']) {
                            case MSG_MAIL:
                                if (empty($message['customers'][$customerid]['emails'])) {
                                    break;
                                }
                                $recipient['email'] = implode(',', $message['customers'][$customerid]['emails']);
                                break;
                            case MSG_SMS:
                                if (empty($message['customers'][$customerid]['phones'])) {
                                    break;
                                }
                                $recipient['phone'] = implode(',', $message['customers'][$customerid]['phones']);
                                break;
                        }
                    }
                    unset($recipient);
                }
            }
        }

        if (!$recipients) {
            $error['subject'] = trans('Unable to send message. No recipients selected!');
        }
    }

    if (isset($_GET['count_recipients'])) {
        header('Content-Type: application/json');
        die(json_encode(array(
            'recipients' => empty($error) ? count($recipients) : -1,
        )));
    }

    if ($message['type'] == MSG_MAIL || $message['type'] == MSG_USERPANEL || $message['type'] == MSG_USERPANEL_URGENT) {
        $result = handle_file_uploads('files', $error);
        extract($result);
        $SMARTY->assign('fileupload', $fileupload);
    }

    if (!$error) {
        set_time_limit(0);

        $message['body'] = str_replace("\r", '', $message['body']);

        $format = $html_format ? 'html' : 'text';

        if ($message['type'] == MSG_MAIL && !$html_format) {
            $message['body'] = wordwrap($message['body'], 128);
        }

        $message['contenttype'] = $html_format ? 'text/html' : 'text/plain';

        $SMARTY->assign('recipcount', count($recipients));

        $DB->BeginTrans();

        $result = $LMS->addMessage(array(
            'type' => $message['type'],
            'subject' => $message['subject'],
            'body' => $message['body'],
            'sender' => array(
                'name' => $message['from'],
                'mail' => $message['sender'],
            ),
            'contenttype' => $message['contenttype'],
            'startdate' => $startdate,
            'attempts' => $attempts,
            'recipients' => $recipients,
        ));
        $msgid = $result['id'];
        $msgitems = $result['items'];

        foreach ($recipients as &$row) {
            if ($message['type'] == MSG_MAIL) {
                $row['destination'] = explode(',', $row['email']);
            } elseif ($message['type'] == MSG_WWW) {
                $row['destination'] = array(trans('www'));
            } elseif ($message['type'] == MSG_USERPANEL) {
                $row['destination'] = array(trans('userpanel'));
            } elseif ($message['type'] == MSG_USERPANEL_URGENT) {
                $row['destination'] = array(trans('userpanel urgent'));
            } else {
                $row['destination'] = explode(',', $row['phone']);
            }
        }
        unset($row);

        if ($message['type'] == MSG_MAIL || $message['type'] == MSG_USERPANEL || $message['type'] == MSG_USERPANEL_URGENT) {
            if (!empty($files)) {
                foreach ($files as &$file) {
                    $file['name'] = $tmppath . DIRECTORY_SEPARATOR . $file['name'];
                    $file['data'] = file_get_contents($file['name']);
                }
                unset($file);
                $LMS->AddFileContainer(array(
                    'description' => 'message-' . $msgid,
                    'files' => $files,
                    'type' => 'messageid',
                    'resourceid' => $msgid,
                ));
            }
        }

        $DB->CommitTrans();

        $message['id'] = $msgid;
        $SMARTY->assign('message', $message);
        $SMARTY->assign('backto', '?' . $SESSION->get_history_entry());
        $SMARTY->display('message/messagesend.html');

        if ($message['type'] == MSG_MAIL) {
            $attachments = null;
            if (!empty($files)) {
                foreach ($files as $file) {
                    $attachments[] = array(
                        'content_type' => $file['type'],
                        'filename' => basename($file['name']),
                        'data' => $file['data'],
                    );
                }

                // deletes uploaded files
                if (!empty($tmppath)) {
                    rrmdir($tmppath);
                }
            }

            $debug_email = ConfigHelper::getConfig('mail.debug_email');
            if (!empty($debug_email)) {
                echo '<B>'.trans('Warning! Debug mode (using address $a).', ConfigHelper::getConfig('mail.debug_email')).'</B><BR>';
            }

            $headers['Subject'] = $message['subject'];

            if ($html_format) {
                $headers['X-LMS-Format'] = 'html';
            }

            $interval = intval(ConfigHelper::getConfig(
                'messages.send_interval',
                ConfigHelper::getConfig('phpui.message_send_interval', '0')
            ));
        } elseif ($message['type'] != MSG_WWW && $message['type'] != MSG_USERPANEL && $message['type'] != MSG_USERPANEL_URGENT) {
            $debug_phone = ConfigHelper::getConfig('sms.debug_phone');
            if (!empty($debug_phone)) {
                echo '<B>'.trans('Warning! Debug mode (using phone $a).', $debug_phone).'</B><BR>';
            }
        }

        if ($message['type'] == MSG_SMS) {
            $sms_options = $LMS->getCustomerSMSOptions();
        }

        $permanent_attributes = array(
            'copytosender' => isset($message['copytosender']),
            'reply' => $message['reply-email'],
            'cc' => $message['cc-email'],
            'bcc' => $message['bcc-email'],
        );

        if (!empty($permanent_attributes['cc'])) {
            $headers['Cc'] = $permanent_attributes['cc'];
        }

        if (!empty($permanent_attributes['bcc'])) {
            $headers['Bcc'] = $permanent_attributes['bcc'];
        }

        $divisionid = 0;
        $key = 1;
        foreach ($recipients as $row) {
            if (isset($row['divisionid']) && $row['divisionid'] != $divisionid) {
                $divisionid = $row['divisionid'];
                ConfigHelper::setFilter($divisionid);

                if ($message['type'] == MSG_MAIL) {
                    $sender_email = ConfigHelper::getConfig(
                        'messages.sender_email',
                        ConfigHelper::getConfig('phpui.message_sender_email', $message['sender'])
                    );

                    $permanent_attributes['sender_name'] = $message['from'];
                    $permanent_attributes['sender_email'] = $sender_email;

                    $headers['From'] = '"' . qp_encode($message['from']) . '"' . ' <' . $sender_email . '>';
                    if (isset($message['copytosender'])) {
                        $headers['Cc'] = $headers['From'];
                    }

                    $reply_email = ConfigHelper::getConfig('mail.reply_email');

                    if (empty($permanent_attributes['reply'])) {
                        $headers['Reply-To'] = empty($reply_email) ? $sender_email : $reply_email;
                    } else {
                        $headers['Reply-To'] = $permanent_attributes['reply'];
                    }

                    $dsn_email = ConfigHelper::getConfig('mail.dsn_email', '', true);
                    $mdn_email = ConfigHelper::getConfig('mail.mdn_email', '', true);

                    if (!empty($dsn_email)) {
                        $permanent_attributes['sender_email'] = $dsn_email;
                        $headers['From'] = (empty($message['from']) ? '' : qp_encode($message['from']) . ' ') . '<' . $dsn_email . '>';
                        $headers['Delivery-Status-Notification-To'] = true;
                    }
                    if (!empty($mdn_email)) {
                        $headers['Return-Receipt-To'] = $mdn_email;
                        $headers['Disposition-Notification-To'] = $mdn_email;
                    }

                    $LMS->prepareMessageTemplates();
                }
            }

            $body = $message['body'];

            $customerid = $row['id'] ?? 0;

            if (!empty($customerid) || $message['type'] == MSG_ANYSMS) {
                $subject = $message['subject'];
                $plain_body = $body;

                if ($message['type'] == MSG_ANYSMS && isset($customer)) {
                    replaceSymbols($body, $customer, $format);
                    replaceSymbols($subject, $customer, 'text');
                    $data = $customer;
                } else {
                    $row['contenttype'] = $message['contenttype'];
                    replaceSymbols($body, $row, $format);
                    replaceSymbols($subject, $row, 'text');
                    $data = $row;
                }

                $hook_data = $LMS->ExecuteHook('messageadd_variable_parser', array(
                    'subject' => $subject,
                    'body' => $body,
                    'data' => $data,
                ));
                $subject = $hook_data['subject'];
                $body = $hook_data['body'];

                $LMS->updateMessageItems(array(
                    'messageid' => $msgid,
                    'original_body' => $plain_body,
                    'real_body' => $body,
                    'original_subject' => $message['subject'],
                    'real_subject' => $subject,
                    'customerid' => $customerid ?: null,
                ));

                if ($message['type'] == MSG_MAIL) {
                    $headers['Subject'] = $subject;
                }
            }

            foreach ($row['destination'] as $destination) {
                $orig_destination = $destination;
                if ($message['type'] == MSG_MAIL) {
                    $headers['To'] = '<' . $destination . '>';
                    echo '<img src="img/mail.gif" border="0" align="absmiddle" alt=""> ';
                } elseif ($message['type'] == MSG_WWW) {
                    echo '<img src="img/network.gif" border="0" align="absmiddle" alt=""> ';
                } elseif ($message['type'] == MSG_USERPANEL || $message['type'] == MSG_USERPANEL_URGENT) {
                    echo '<img src="img/cms.gif" border="0" align="absmiddle" alt=""> ';
                } else {
                    $destination = preg_replace('/[^0-9]/', '', $destination);
                    echo '<img src="img/sms.gif" border="0" align="absmiddle" alt=""> ';
                }

                echo trans(
                    '$a of $b ($c) $d:',
                    $key,
                    count($recipients),
                    sprintf('%02.1f%%', round((100 / count($recipients)) * $key, 1)),
                    ($row['customername'] ?? '-') . ' &lt;' . $destination . '&gt;'
                );
                flush();

                $attributes = null;

                switch ($message['type']) {
                    case MSG_MAIL:
                        if (isset($message['copytosender'])) {
                            $destination .= ',' . $sender_email;
                        }
                        if (!empty($dsn_email) || !empty($mdn_email)) {
                            $headers['X-LMS-Message-Item-Id'] = $msgitems[$customerid][$orig_destination];
                            $headers['Message-ID'] = '<messageitem-' . $msgitems[$customerid][$orig_destination] . '@rtsystem.' . gethostname() . '>';
                        }
                        if (empty($startdate) || $startdate <= time()) {
                            $result = $LMS->SendMail(
                                $destination,
                                $headers,
                                $LMS->applyMessageTemplates(
                                    $body,
                                    $message['contenttype']
                                ),
                                $attachments
                            );

                            if (!empty($interval)) {
                                if ($interval == -1) {
                                    $delay = mt_rand(500, 5000);
                                } else {
                                    $delay = $interval;
                                }
                                usleep($delay * 1000);
                            }
                        } else {
                            $attributes = array(
                                'destination' => $destination,
                                'headers' => $headers,
                                'body' => $LMS->applyMessageTemplates(
                                    $body,
                                    $message['contenttype']
                                ),
                            );
                            $result = array(
                                'status' => MSG_NEW,
                            );
                        }

                        break;
                    case MSG_SMS:
                    case MSG_ANYSMS:
                        if (empty($startdate) || $startdate <= time()) {
                            $result = $LMS->SendSMS(
                                $destination,
                                $body,
                                $msgitems[$customerid][$orig_destination],
                                $sms_options ?? null
                            );
                        } else {
                            $attributes = array(
                                'destination' => $destination,
                                'body' => $LMS->applyMessageTemplates(
                                    $body,
                                    $message['contenttype']
                                ),
                            );
                            $result = array(
                                'status' => MSG_NEW,
                            );
                        }

                        break;
                    case MSG_USERPANEL:
                    case MSG_USERPANEL_URGENT:
                        $result = MSG_SENT;
                        break;
                    default:
                        $result = MSG_NEW;
                }

                if (is_int($result)) {
                    $status = $result;
                    $errors = array();
                } elseif (is_string($result)) {
                    $status = MSG_ERROR;
                    $errors = array($result);
                } else {
                    $status = $result['status'];
                    $errors = $result['errors'] ?? array();
                }
                switch ($status) {
                    case MSG_ERROR:
                        echo ' <span class="red">' . implode(', ', $errors) . '</span>';
                        break;
                    case MSG_SENT:
                        echo ' [' . trans('sent') . ']';
                        break;
                    default:
                        echo ' [' . trans('added') . ']';
                        break;
                }

                echo "<BR>\n";

                if ($status == MSG_SENT || isset($result['id']) || !empty($errors)) {
                    $DB->Execute(
                        'UPDATE messageitems SET status = ?, lastdate = ?NOW?,
                            error = ?, externalmsgid = ?, attributes = ? WHERE messageid = ? AND '
                            . (empty($customerid) ? 'customerid IS NULL' : 'customerid = ' . intval($customerid)) . '
                            AND destination = ?',
                        array(
                            $status,
                            empty($errors) ? null : implode(', ', $errors),
                            !is_array($result) || empty($result['id']) ? null : $result['id'],
                            empty($attributes) ? null : serialize(array_merge($permanent_attributes, $attributes)),
                            $msgid,
                            $orig_destination,
                        )
                    );
                } elseif (!empty($attributes)) {
                    $DB->Execute(
                        'UPDATE messageitems SET attributes = ? WHERE messageid = ? AND '
                        . (empty($customerid) ? 'customerid IS NULL' : 'customerid = ' . intval($customerid)) . '
                            AND destination = ?',
                        array(
                            serialize(array_merge($permanent_attributes, $attributes)),
                            $msgid,
                            $orig_destination,
                        )
                    );
                }
            }

            $key++;
        }

        echo '<script type="text/javascript">';
        echo "history.replaceState({}, '', location.href.replace(/&sent=1/gi, '') + '&sent=1');";
        echo '</script>';

        $SMARTY->display('footer.html');
        $SESSION->close();
        die;
    } else if (!empty($message['customermode'])) {
        $selected_contacts = $message['customers'];

        $customers = array_unique(array_keys($message['customers']));

        $message['customers'] = $DB->GetAllByKey(
            'SELECT id AS customerid, '
            . $DB->Concat('UPPER(lastname)', "' '", 'name') . ' AS name
            FROM customerview
            WHERE id IN ?
            ORDER BY name',
            'customerid',
            array($customers)
        );

        $phones = $DB->GetAll(
            'SELECT id, customerid, contact, name, type FROM customercontacts
		    WHERE customerid IN ? AND (type & ?) = 0 AND (type & ?) > 0',
            array($customers, CONTACT_DISABLED, CONTACT_MOBILE | CONTACT_FAX | CONTACT_LANDLINE)
        );

        $message['phonecount'] = 0;

        if (!empty($phones)) {
            $message['checkedphones'] = 0;
            foreach ($phones as $phone) {
                $customerid = $phone['customerid'];
                if (isset($message['customers'][$customerid])) {
                    if (!isset($message['customers'][$customerid]['phones'])) {
                        $message['customers'][$customerid]['phones'] = array();
                    }
                    if (!empty($selected_contacts[$customerid]['phones'][$phone['id']])) {
                        $phone['checked'] = 1;
                        $message['checkedphones']++;
                    }
                    $message['customers'][$customerid]['phones'][$phone['id']] = $phone;
                    $message['phonecount']++;
                }
            }
        }

        $emails = $DB->GetAll(
            'SELECT id, customerid, contact, name FROM customercontacts
		    WHERE customerid IN ? AND (type & ?) = ?',
            array($customers, CONTACT_EMAIL | CONTACT_DISABLED, CONTACT_EMAIL)
        );

        $message['emailcount'] = 0;

        if (!empty($emails)) {
            foreach ($emails as $email) {
                $customerid = $email['customerid'];
                if (isset($message['customers'][$customerid])) {
                    if (!isset($message['customers'][$customerid]['emails'])) {
                        $message['customers'][$customerid]['emails'] = array();
                    }
                    if (!empty($selected_contacts[$customerid]['emails'][$email['id']])) {
                        $email['checked'] = 1;
                    }
                    $message['customers'][$customerid]['emails'][$email['id']] = $email;
                    $message['emailcount']++;
                }
            }
        }
    } else {
        require_once(LIB_DIR . DIRECTORY_SEPARATOR . 'customercontacttypes.php');
    }

    $SMARTY->assign('error', $error);
} else if (!empty($_GET['customerid']) || isset($_POST['customers'])) {
    if (!empty($_GET['customerid'])) {
        $customers = array($_GET['customerid']);
    } else {
        $customers = $_POST['customers'];
    }

    $message['customers'] = $DB->GetAllByKey(
        'SELECT id AS customerid, '
        . $DB->Concat('UPPER(lastname)', "' '", 'name') . ' AS name
        FROM customerview
        WHERE id IN ?
        ORDER BY name',
        'customerid',
        array($customers)
    );

    $contactid = isset($_GET['contactid']) ? intval($_GET['contactid']) : 0;

    $phones = $DB->GetAll(
        'SELECT id, customerid, contact, name, type FROM customercontacts
		WHERE customerid IN ? AND (type & ?) = 0 AND (type & ?) > 0',
        array($customers, CONTACT_DISABLED, CONTACT_MOBILE | CONTACT_FAX | CONTACT_LANDLINE)
    );

    $message['phonecount'] = 0;

    if (!empty($phones)) {
        $message['checkedphones'] = 0;
        foreach ($phones as $phone) {
            $customerid = $phone['customerid'];
            if (isset($message['customers'][$customerid])) {
                if (!isset($message['customers'][$customerid]['phones'])) {
                    $message['customers'][$customerid]['phones'] = array();
                }
                if ($phone['type'] & CONTACT_MOBILE && (!$contactid || $contactid == $phone['id'])) {
                    $phone['checked'] = 1;
                    $message['checkedphones']++;
                }
                $message['customers'][$customerid]['phones'][$phone['id']] = $phone;
                $message['phonecount']++;
            }
        }
    }

    $emails = $DB->GetAll(
        'SELECT id, customerid, contact, name FROM customercontacts
		WHERE customerid IN ? AND (type & ?) = ?',
        array($customers, CONTACT_EMAIL | CONTACT_DISABLED, CONTACT_EMAIL)
    );

    $message['emailcount'] = 0;

    if (!empty($emails)) {
        foreach ($emails as $email) {
            $customerid = $email['customerid'];
            if (isset($message['customers'][$customerid])) {
                if (!isset($message['customers'][$customerid]['emails'])) {
                    $message['customers'][$customerid]['emails'] = array();
                }
                if (!$contactid || $contactid == $email['id']) {
                    $email['checked'] = 1;
                }
                $message['customers'][$customerid]['emails'][$email['id']] = $email;
                $message['emailcount']++;
            }
        }
    }

    if (isset($_GET['messageid'])) {
        $msg = $LMS->getSingleMessage($_GET['messageid']);
        $message['type'] = $msg['type'];
        $message['subject'] = !empty($msg['subject']) ? $msg['subject'] : '';
        $message['body'] = !empty($msg['body']) ? $msg['body'] : '';
        if ($msg['contenttype'] == 'text/html') {
            $message['wysiwyg']['mailbody'] = 'true';
        }
    }

    $message['type'] = isset($_GET['type']) ? intval($_GET['type'])
        : (empty($message['emailcount']) ? (empty($message['phonecount']) ? MSG_WWW : MSG_SMS) : MSG_MAIL);
    $message['usergroup'] = isset($_GET['usergroupid']) ? intval($_GET['usergroupid']) : 0;
    $message['tmplid'] = isset($_GET['templateid']) ? intval($_GET['templateid']) : 0;
    $SMARTY->assign('autoload_template', true);
    $message['nodeid'] = isset($_GET['nodeid']) ? intval($_GET['nodeid']) : 0;
} else {
    if (isset($_GET['messageid'])) {
        $msg = $LMS->getSingleMessage($_GET['messageid']);
        $message['type'] = $msg['type'];
        $message['subject'] = !empty($msg['subject']) ? $msg['subject'] : '';
        $message['body'] = !empty($msg['body']) ? $msg['body'] : '';
        if ($msg['contenttype'] == 'text/html') {
            $message['wysiwyg']['mailbody'] = 'true';
        }
    }
    $message['usergroup'] = isset($_GET['usergroupid']) ? intval($_GET['usergroupid']) : 0;
    $message['tmplid'] = isset($_GET['templateid']) ? intval($_GET['templateid']) : 0;
    $SMARTY->assign('autoload_template', true);

    require_once(LIB_DIR . DIRECTORY_SEPARATOR . 'customercontacttypes.php');
}

if (isset($message['type'])) {
    switch ($message['type']) {
        case MSG_MAIL:
            $msgtmpltype = TMPL_MAIL;
            break;
        case MSG_SMS:
        case MSG_ANYSMS:
            $msgtmpltype = TMPL_SMS;
            break;
        case MSG_WWW:
            $msgtmpltype = TMPL_WWW;
            break;
        case MSG_USERPANEL:
            $msgtmpltype = TMPL_USERPANEL;
            break;
        case MSG_USERPANEL_URGENT:
            $msgtmpltype = TMPL_USERPANEL_URGENT;
            break;
    }
} else {
    $msgtmpltype = TMPL_MAIL;
}

$SMARTY->assign('divisions', $LMS->GetDivisions());
$SMARTY->assign('messagetemplates', $LMS->GetMessageTemplates($msgtmpltype));
$SMARTY->assign('networks', $LMS->GetNetworks());
$SMARTY->assign('customergroups', $LMS->CustomergroupGetAll());
$SMARTY->assign('nodegroups', $LMS->GetNodeGroupNames());

if (empty($message['sender'])) {
    $message['sender'] = ConfigHelper::getConfig(
        'messages.sender_email',
        ConfigHelper::getConfig('phpui.message_sender_email', $userinfo['email'])
    );
}

$SMARTY->assign('message', $message);
$SMARTY->assign('userinfo', $userinfo);

$SMARTY->assign('users', $DB->GetAllByKey('SELECT id, rname AS name, phone FROM vusers WHERE phone <> ? ORDER BY rname', 'id', array('')));

$usergroups = $LMS->UsergroupGetList();
unset($usergroups['total'], $usergroups['totalcount']);
$SMARTY->assign('usergroups', $usergroups);

$netdevices = $LMS->GetNetDevList();
unset($netdevices['total'], $netdevices['order'], $netdevices['direction']);
$SMARTY->assign('netdevices', $netdevices);

if (!empty($message['customers']) && count($message['customers']) == 1) {
    $customer = reset($message['customers']);
    $SMARTY->assign('nodes', $LMS->GetCustomerNodes($customer['customerid']));
}

$SMARTY->display('message/messageadd.html');
