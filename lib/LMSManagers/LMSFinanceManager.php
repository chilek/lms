<?php

/*
 *  LMS version 1.11-git
 *
 *  Copyright (C) 2001-2016 LMS Developers
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
 * LMSFinanceManager
 *
 * @author Maciej Lew <maciej.lew.1987@gmail.com>
 * @author Tomasz Chiliński <tomasz.chilinski@chilan.com>
 */
class LMSFinanceManager extends LMSManager implements LMSFinanceManagerInterface
{

    public function GetCustomerTariffsValue($id)
    {
        return $this->db->GetOne('SELECT SUM(tariffs.value)
		    FROM assignments, tariffs
			WHERE tariffid = tariffs.id AND customerid = ? AND suspended = 0
			    AND datefrom <= ?NOW? AND (dateto > ?NOW? OR dateto = 0)', array($id));
    }

    public function GetCustomerAssignments($id, $show_expired = false)
    {
        $now = mktime(0, 0, 0, date('n'), date('d'), date('Y'));

        if ($assignments = $this->db->GetAll('SELECT a.id AS id, a.tariffid,
			a.customerid, a.period, a.at, a.suspended, a.invoice, a.settlement,
			a.datefrom, a.dateto, a.pdiscount, a.vdiscount, a.attribute, a.liabilityid,
			t.uprate, t.upceil, t.downceil, t.downrate,
			(CASE WHEN t.value IS NULL THEN l.value ELSE t.value END) AS value,
			(CASE WHEN t.name IS NULL THEN l.name ELSE t.name END) AS name
			FROM assignments a
			LEFT JOIN tariffs t ON (a.tariffid = t.id)
			LEFT JOIN liabilities l ON (a.liabilityid = l.id)
			WHERE a.customerid=? '
                . (!$show_expired ? 'AND (a.dateto > ' . $now . ' OR a.dateto = 0)
			    AND (a.at >= ' . $now . ' OR a.at < 531)' : '')
                . ' ORDER BY a.datefrom, t.name, value', array($id))) {
            foreach ($assignments as $idx => $row) {
                switch ($row['period']) {
                    case DISPOSABLE:
                        $row['payday'] = date('Y/m/d', $row['at']);
                        $row['period'] = trans('disposable');
                        break;
                    case DAILY:
                        $row['period'] = trans('daily');
                        $row['payday'] = trans('daily');
                        break;
                    case WEEKLY:
                        $row['at'] = strftime("%a", mktime(0, 0, 0, 0, $row['at'] + 5, 0));
                        $row['payday'] = trans('weekly ($a)', $row['at']);
                        $row['period'] = trans('weekly');
                        break;
                    case MONTHLY:
                        $row['payday'] = trans('monthly ($a)', $row['at']);
                        $row['period'] = trans('monthly');
                        break;
                    case QUARTERLY:
                        $row['at'] = sprintf('%02d/%02d', $row['at'] % 100, $row['at'] / 100 + 1);
                        $row['payday'] = trans('quarterly ($a)', $row['at']);
                        $row['period'] = trans('quarterly');
                        break;
                    case HALFYEARLY:
                        $row['at'] = sprintf('%02d/%02d', $row['at'] % 100, $row['at'] / 100 + 1);
                        $row['payday'] = trans('half-yearly ($a)', $row['at']);
                        $row['period'] = trans('half-yearly');
                        break;
                    case YEARLY:
                        $row['at'] = date('d/m', ($row['at'] - 1) * 86400);
                        $row['payday'] = trans('yearly ($a)', $row['at']);
                        $row['period'] = trans('yearly');
                        break;
                }

                $assignments[$idx] = $row;

                // assigned nodes
                $assignments[$idx]['nodes'] = $this->db->GetAll('SELECT vnodes.name, vnodes.id FROM nodeassignments, vnodes
						    WHERE nodeid = vnodes.id AND assignmentid = ?', array($row['id']));

                $assignments[$idx]['discounted_value'] = (((100 - $row['pdiscount']) * $row['value']) / 100) - $row['vdiscount'];

                if ($row['suspended'] == 1)
                    $assignments[$idx]['discounted_value'] = $assignments[$idx]['discounted_value'] * ConfigHelper::getConfig('finances.suspension_percentage') / 100;

                $assignments[$idx]['discounted_value'] = round($assignments[$idx]['discounted_value'], 2);

                $now = time();

                if ($row['suspended'] == 0 &&
                        (($row['datefrom'] == 0 || $row['datefrom'] < $now) &&
                        ($row['dateto'] == 0 || $row['dateto'] > $now))) {
                    // for proper summary
                    $assignments[$idx]['real_value'] = $row['value'];
                    $assignments[$idx]['real_disc_value'] = $assignments[$idx]['discounted_value'];
                    $assignments[$idx]['real_downrate'] = $row['downrate'];
                    $assignments[$idx]['real_downceil'] = $row['downceil'];
                    $assignments[$idx]['real_uprate'] = $row['uprate'];
                    $assignments[$idx]['real_upceil'] = $row['upceil'];
                }
            }
        }

        return $assignments;
    }

    public function DeleteAssignment($id)
    {
        global $SYSLOG_RESOURCE_KEYS;
        $this->db->BeginTrans();

        if ($this->syslog) {
            $custid = $this->db->GetOne('SELECT customerid FROM assignments WHERE id=?', array($id));

            $nodeassigns = $this->db->GetAll('SELECT id, nodeid FROM nodeassignments WHERE assignmentid = ?', array($id));
            if (!empty($nodeassigns))
                foreach ($nodeassigns as $nodeassign) {
                    $args = array(
                        $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NODEASSIGN] => $nodeassign['id'],
                        $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST] => $custid,
                        $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NODE] => $nodeassign['nodeid'],
                        $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_ASSIGN] => $id
                    );
                    $this->syslog->AddMessage(SYSLOG_RES_NODEASSIGN, SYSLOG_OPER_DELETE, $args, array_keys($args));
                }

            $assign = $this->db->GetRow('SELECT tariffid, liabilityid FROM assignments WHERE id=?', array($id));
            $lid = $assign['liabilityid'];
            $tid = $assign['tariffid'];
            if ($lid) {
                $args = array(
                    $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_LIAB] => $lid,
                    $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST] => $custid
                );
                $this->syslog->AddMessage(SYSLOG_RES_LIAB, SYSLOG_OPER_DELETE, $args, array_keys($args));
            }
        }
        $this->db->Execute('DELETE FROM liabilities WHERE id=(SELECT liabilityid FROM assignments WHERE id=?)', array($id));
        $this->db->Execute('DELETE FROM assignments WHERE id=?', array($id));
        if ($this->syslog) {
            $args = array(
                $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_TARIFF] => $tid,
                $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_LIAB] => $lid,
                $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_ASSIGN] => $id,
                $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST] => $custid
            );
            $this->syslog->AddMessage(SYSLOG_RES_ASSIGN, SYSLOG_OPER_DELETE, $args, array_keys($args));
        }

        $this->db->CommitTrans();
    }

    public function AddAssignment($data)
    {
        global $SYSLOG_RESOURCE_KEYS;
        $result = array();

        // Create assignments according to promotion schema
        if (!empty($data['promotiontariffid']) && !empty($data['schemaid'])) {
            $data['tariffid'] = $data['promotiontariffid'];
            $tariff = $this->db->GetRow('SELECT a.data, s.data AS sdata,
                    t.name, t.value, t.period, t.id, t.prodid, t.taxid,
                    s.continuation, s.ctariffid
                    FROM promotionassignments a
                    JOIN promotionschemas s ON (s.id = a.promotionschemaid)
                    JOIN tariffs t ON (t.id = a.tariffid)
                    WHERE a.promotionschemaid = ? AND a.tariffid = ?', array($data['schemaid'], $data['promotiontariffid']));
            $data_schema = explode(';', $tariff['sdata']);
            $data_tariff = explode(';', $tariff['data']);
            $datefrom = $data['datefrom'];
            $cday = date('d', $datefrom);

            foreach ($data_tariff as $idx => $dt) {
                list($value, $period) = explode(':', $dt);
                // Activation
                if (!$idx) {
                    // if activation value specified, create disposable liability
                    if (f_round($value)) {
                        $start_day = date('d', $data['datefrom']);
                        $start_month = date('n', $data['datefrom']);
                        $start_year = date('Y', $data['datefrom']);
                        // payday is before the start of the period
                        // set activation payday to next month's payday
                        if ($start_day > $data['at']) {
                            $_datefrom = $data['datefrom'];
                            $datefrom = mktime(0, 0, 0, $start_month + 1, $data['at'], $start_year);
                        }

                        $args = array(
                            'name' => trans('Activation payment'),
                            'value' => str_replace(',', '.', $value),
                            $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_TAX] => intval($tariff['taxid']),
                            'prodid' => $tariff['prodid']
                        );
                        $this->db->Execute('INSERT INTO liabilities (name, value, taxid, prodid)
							VALUES (?, ?, ?, ?)', array_values($args));
                        $lid = $this->db->GetLastInsertID('liabilities');

                        if ($this->syslog) {
                            $args[$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_LIAB]] = $lid;
                            $args[$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST]] = $data['customerid'];
                            $this->syslog->AddMessage(SYSLOG_RES_LIAB, SYSLOG_OPER_ADD, $args, array($SYSLOG_RESOURCE_KEYS[SYSLOG_RES_LIAB],
                                $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST],
                                $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_TAX]));
                        }

                        $tariffid = 0;
                        $period = DISPOSABLE;
                        $at = $datefrom;
                    } else {
                        continue;
                    }
                }
                // promotion period
                else {
                    $lid = 0;
                    if (!$period)
                        $period = $data['period'];
                    $datefrom = $_datefrom ? $_datefrom : $datefrom;
                    $_datefrom = 0;
                    $at = $this->CalcAt($period, $datefrom);
                    $length = $data_schema[$idx - 1];
                    $month = date('n', $datefrom);
                    $year = date('Y', $datefrom);
                    // assume $data['at'] == 1, set last day of the specified month
                    $dateto = mktime(23, 59, 59, $month + $length + ($cday && $cday != 1 ? 1 : 0), 0, $year);
                    $cday = 0;

                    // Find tariff with specified name+value+period...
                    $tariffid = null;
                    if ($tariff['period'] !== null) {
                        $tariffid = $this->db->GetOne('
                            SELECT id FROM tariffs
                            WHERE name = ? AND value = ? AND period = ?
                            LIMIT 1',
                            array(
                                $tariff['name'],
                                str_replace(',', '.', $value),
                                $tariff['period'],
                            )
                        );
                    } else {
                        $tariffid = $this->db->GetOne('
                            SELECT id FROM tariffs
                            WHERE name = ? AND value = ? AND period IS NULL
                            LIMIT 1', 
                            array(
                                $tariff['name'],
                                str_replace(',', '.', $value),
                            )
                        );
                    }

                    // ... if not found clone tariff
                    if (!$tariffid) {
                        $args = $this->db->GetRow('SELECT name, value, period,
							taxid AS ' . $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_TAX] . ', type, upceil, downceil, uprate, downrate,
							prodid, plimit, climit, dlimit, upceil_n, downceil_n, uprate_n, downrate_n,
							domain_limit, alias_limit, sh_limit, www_limit, ftp_limit, mail_limit, sql_limit,
							quota_sh_limit, quota_www_limit, quota_ftp_limit, quota_mail_limit, quota_sql_limit
							FROM tariffs WHERE id = ?', array($tariff['id']));
                        $args = array_merge($args, array(
                            $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_TAX] => $args[$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_TAX]],
                            'name' => $tariff['name'],
                            'value' => str_replace(',', '.', $value),
                            'period' => $tariff['period']
                        ));
                        unset($args['taxid']);
                        $this->db->Execute('INSERT INTO tariffs (name, value, period,
							taxid, type, upceil, downceil, uprate, downrate,
							prodid, plimit, climit, dlimit, upceil_n, downceil_n, uprate_n, downrate_n,
							domain_limit, alias_limit, sh_limit, www_limit, ftp_limit, mail_limit, sql_limit,
							quota_sh_limit, quota_www_limit, quota_ftp_limit, quota_mail_limit, quota_sql_limit)
							VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', array_values($args));
                        $tariffid = $this->db->GetLastInsertId('tariffs');
                        if ($this->syslog) {
                            $args[$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_TARIFF]] = $tariffid;
                            $this->syslog->AddMessage(SYSLOG_RES_TARIFF, SYSLOG_OPER_ADD, $args, array($SYSLOG_RESOURCE_KEYS[SYSLOG_RES_TARIFF],
                                $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_TAX]));
                        }
                    }
                }

                // Create assignment
                $args = array(
                    $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_TARIFF] => $tariffid,
                    $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST] => $data['customerid'],
                    'period' => $period,
                    'at' => $at,
                    'invoice' => !empty($data['invoice']) ? 1 : 0,
                    'settlement' => !empty($data['settlement']) ? 1 : 0,
                    $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NUMPLAN] => !empty($data['numberplanid']) ? $data['numberplanid'] : NULL,
                    'paytype' => !empty($data['paytype']) ? $data['paytype'] : NULL,
                    'datefrom' => $idx ? $datefrom : 0,
                    'dateto' => $idx ? $dateto : 0,
                    'pdiscount' => 0,
                    'vdiscount' => 0,
                    'attribute' => !empty($data['attribute']) ? $data['attribute'] : NULL,
                    $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_LIAB] => $lid,
                );

                $this->db->Execute('INSERT INTO assignments (tariffid, customerid, period, at, invoice,
					    settlement, numberplanid, paytype, datefrom, dateto, pdiscount, vdiscount, attribute, liabilityid)
					    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', array_values($args));

                $id = $this->db->GetLastInsertID('assignments');

                if ($this->syslog) {
                    $args[$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_ASSIGN]] = $id;
                    $this->syslog->AddMessage(SYSLOG_RES_ASSIGN, SYSLOG_OPER_ADD, $args, array($SYSLOG_RESOURCE_KEYS[SYSLOG_RES_ASSIGN],
                        $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST],
                        $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_TARIFF],
                        $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_LIAB],
                        $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NUMPLAN]));
                }

                $result[] = $id;
                if ($idx) {
                    $datefrom = $dateto + 1;
                }
            }

            // add "after promotion" tariff(s)
            if ($tariff['continuation'] || !$data_schema[0]) {
                $tariffs[] = $tariff['id'];
                if ($tariff['ctariffid'] && $data_schema[0] != 0) {
                    $tariffs[] = $tariff['ctariffid'];
                }

                // Create assignments
                foreach ($tariffs as $t) {
                    $args = array(
                        $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_TARIFF] => $t,
                        $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST] => $data['customerid'],
                        'period' => $data['period'],
                        'at' => $this->CalcAt($data['period'], $datefrom),
                        'invoice' => !empty($data['invoice']) ? 1 : 0,
                        'settlement' => !empty($data['settlement']) ? 1 : 0,
                        $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NUMPLAN] => !empty($data['numberplanid']) ? $data['numberplanid'] : NULL,
                        'paytype' => !empty($data['paytype']) ? $data['paytype'] : NULL,
                        'datefrom' => $datefrom,
                        'dateto' => 0,
                        'pdiscount' => 0,
                        'vdiscount' => 0,
                         'attribute' => !empty($data['attribute']) ? $data['attribute'] : NULL,
                        $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_LIAB] => 0,
                    );

                    $this->db->Execute('INSERT INTO assignments (tariffid, customerid, period, at, invoice,
					    settlement, numberplanid, paytype, datefrom, dateto, pdiscount, vdiscount, attribute, liabilityid)
					    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', array_values($args));

                    $id = $this->db->GetLastInsertID('assignments');

                    if ($this->syslog) {
                        $args[$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_ASSIGN]] = $id;
                        $this->syslog->AddMessage(SYSLOG_RES_ASSIGN, SYSLOG_OPER_ADD, $args, array($SYSLOG_RESOURCE_KEYS[SYSLOG_RES_ASSIGN],
                            $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST],
                            $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_TARIFF],
                            $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_LIAB],
                            $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NUMPLAN]));
                    }

                    $result[] = $id;
                }
            }
        }
        // Create one assignment record
        else {
            if (!empty($data['value'])) {
                $args = array(
                    'name' => $data['name'],
                    'value' => str_replace(',', '.', $data['value']),
                    $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_TAX] => intval($data['taxid']),
                    'prodid' => $data['prodid']
                );
                $this->db->Execute('INSERT INTO liabilities (name, value, taxid, prodid)
					    VALUES (?, ?, ?, ?)', array_values($args));
                $lid = $this->db->GetLastInsertID('liabilities');
                if ($this->syslog) {
                    $args[$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_LIAB]] = $lid;
                    $args[$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST]] = $data['customerid'];
                    $this->syslog->AddMessage(SYSLOG_RES_LIAB, SYSLOG_OPER_ADD, $args, array($SYSLOG_RESOURCE_KEYS[SYSLOG_RES_LIAB],
                        $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST],
                        $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_TAX]));
                }
            }

            $args = array(
                $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_TARIFF] => intval($data['tariffid']),
                $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST] => $data['customerid'],
                'period' => $data['period'],
                'at' => $data['at'],
                'invoice' => !empty($data['invoice']) ? 1 : 0,
                'settlement' => !empty($data['settlement']) ? 1 : 0,
                $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NUMPLAN] => !empty($data['numberplanid']) ? $data['numberplanid'] : NULL,
                'paytype' => !empty($data['paytype']) ? $data['paytype'] : NULL,
                'datefrom' => $data['datefrom'],
                'dateto' => $data['dateto'],
                'pdiscount' => str_replace(',', '.', $data['pdiscount']),
                'vdiscount' => str_replace(',', '.', $data['vdiscount']),
                 'attribute' => !empty($data['attribute']) ? $data['attribute'] : NULL,
                $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_LIAB] => isset($lid) ? $lid : 0,
            );
            $this->db->Execute('INSERT INTO assignments (tariffid, customerid, period, at, invoice,
					    settlement, numberplanid, paytype, datefrom, dateto, pdiscount, vdiscount, attribute, liabilityid)
					    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', array_values($args));

            $id = $this->db->GetLastInsertID('assignments');

            if ($this->syslog) {
                $args[$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_ASSIGN]] = $id;
                $this->syslog->AddMessage(SYSLOG_RES_ASSIGN, SYSLOG_OPER_ADD, $args, array($SYSLOG_RESOURCE_KEYS[SYSLOG_RES_ASSIGN],
                    $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST],
                    $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_TARIFF],
                    $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_LIAB],
                    $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NUMPLAN]));
            }

            $result[] = $id;
        }

        if (!empty($result) && count($result = array_filter($result))) {
            if (!empty($data['nodes'])) {
                // Use multi-value INSERT query
                $values = array();
                foreach ((array) $data['nodes'] as $nodeid)
                    foreach ($result as $aid)
                        $values[] = sprintf('(%d, %d)', $nodeid, $aid);

                $this->db->Execute('INSERT INTO nodeassignments (nodeid, assignmentid)
					VALUES ' . implode(', ', $values));
                if ($this->syslog) {
                    $nodeassigns = $this->db->GetAll('SELECT id, nodeid FROM nodeassignments WHERE assignmentid = ?', array($aid));
                    foreach ($nodeassigns as $nodeassign) {
                        $args = array(
                            $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NODEASSIGN] => $nodeassign['id'],
                            $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST] => $data['customerid'],
                            $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NODE] => $nodeassign['nodeid'],
                            $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_ASSIGN] => $aid
                        );
                        $this->syslog->AddMessage(SYSLOG_RES_NODEASSIGN, SYSLOG_OPER_ADD, $args, array_keys($args));
                    }
                }
            }
        }

        return $result;
    }

    public function SuspendAssignment($id, $suspend = TRUE)
    {
        global $SYSLOG_RESOURCE_KEYS;
        if ($this->syslog) {
            $assign = $this->db->GetRow('SELECT id, tariffid, liabilityid, customerid FROM assignments WHERE id = ?', array($id));
            $args = array(
                $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_ASSIGN] => $assign['id'],
                $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_TARIFF] => $assign['tariffid'],
                $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_LIAB] => $assign['liabilityid'],
                $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST] => $assign['customerid'],
                'suspend' => ($suspend ? 1 : 0)
            );
            $this->syslog->AddMessage(SYSLOG_RES_ASSIGN, SYSLOG_OPER_UPDATE, $args, array($SYSLOG_RESOURCE_KEYS[SYSLOG_RES_ASSIGN],
                $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_TARIFF],
                $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_LIAB],
                $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST]));
        }
        return $this->db->Execute('UPDATE assignments SET suspended=? WHERE id=?', array($suspend ? 1 : 0, $id));
    }

    public function AddInvoice($invoice)
    {
        global $SYSLOG_RESOURCE_KEYS;

        $currtime = time();
        $cdate = $invoice['invoice']['cdate'] ? $invoice['invoice']['cdate'] : $currtime;
        $sdate = $invoice['invoice']['sdate'] ? $invoice['invoice']['sdate'] : $currtime;
        $number = $invoice['invoice']['number'];
        $type = $invoice['invoice']['type'];
        if ($invoice['invoice']['numberplanid'])
            $fullnumber = docnumber($number, $this->db->GetOne('SELECT template FROM numberplans WHERE id = ?', array($invoice['invoice']['numberplanid'])), $cdate);
        else
            $fullnumber = null;

        $division = $this->db->GetRow('SELECT name, shortname, address, city, zip, countryid, ten, regon,
				account, inv_header, inv_footer, inv_author, inv_cplace 
				FROM divisions WHERE id = ? ;', array($invoice['customer']['divisionid']));

        $args = array(
            'number' => $number,
            $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NUMPLAN] => $invoice['invoice']['numberplanid'] ? $invoice['invoice']['numberplanid'] : 0,
            'type' => $type,
            'cdate' => $cdate,
            'sdate' => $sdate,
            'paytime' => $invoice['invoice']['paytime'],
            'paytype' => $invoice['invoice']['paytype'],
            $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_USER] => $this->auth->id,
            $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST] => $invoice['customer']['id'],
            'customername' => $invoice['customer']['customername'],
            'address' => $invoice['customer']['address'],
            'ten' => $invoice['customer']['ten'],
            'ssn' => $invoice['customer']['ssn'],
            'zip' => $invoice['customer']['zip'],
            'city' => $invoice['customer']['city'],
            $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_COUNTRY] => $invoice['customer']['countryid'],
            $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_DIV] => $invoice['customer']['divisionid'],
            'div_name' => ($division['name'] ? $division['name'] : ''),
            'div_shortname' => ($division['shortname'] ? $division['shortname'] : ''),
            'div_address' => ($division['address'] ? $division['address'] : ''),
            'div_city' => ($division['city'] ? $division['city'] : ''),
            'div_zip' => ($division['zip'] ? $division['zip'] : ''),
            'div_' . $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_COUNTRY] => ($division['countryid'] ? $division['countryid'] : 0),
            'div_ten' => ($division['ten'] ? $division['ten'] : ''),
            'div_regon' => ($division['regon'] ? $division['regon'] : ''),
            'div_account' => ($division['account'] ? $division['account'] : ''),
            'div_inv_header' => ($division['inv_header'] ? $division['inv_header'] : ''),
            'div_inv_footer' => ($division['inv_footer'] ? $division['inv_footer'] : ''),
            'div_inv_author' => ($division['inv_author'] ? $division['inv_author'] : ''),
            'div_inv_cplace' => ($division['inv_cplace'] ? $division['inv_cplace'] : ''),
            'fullnumber' => $fullnumber,
        );

        $this->db->Execute('INSERT INTO documents (number, numberplanid, type,
			cdate, sdate, paytime, paytype, userid, customerid, name, address, 
			ten, ssn, zip, city, countryid, divisionid,
			div_name, div_shortname, div_address, div_city, div_zip, div_countryid, div_ten, div_regon,
			div_account, div_inv_header, div_inv_footer, div_inv_author, div_inv_cplace, fullnumber)
			VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', array_values($args));
        $iid = $this->db->GetLastInsertID('documents');
        if ($this->syslog) {
            unset($args[$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_USER]]);
            $args[$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_DOC]] = $iid;
            $this->syslog->AddMessage(SYSLOG_RES_DOC, SYSLOG_OPER_ADD, $args, array($SYSLOG_RESOURCE_KEYS[SYSLOG_RES_DOC], $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NUMPLAN],
                $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST], $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_COUNTRY],
                $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_DIV], 'div_' . $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_COUNTRY]));
        }

        $itemid = 0;
        foreach ($invoice['contents'] as $idx => $item) {
            $itemid++;
            $item['valuebrutto'] = str_replace(',', '.', $item['valuebrutto']);
            $item['count'] = str_replace(',', '.', $item['count']);
            $item['discount'] = str_replace(',', '.', $item['discount']);
            $item['pdiscount'] = str_replace(',', '.', $item['pdiscount']);
            $item['vdiscount'] = str_replace(',', '.', $item['vdiscount']);
            $item['taxid'] = isset($item['taxid']) ? $item['taxid'] : 0;

            $args = array(
                $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_DOC] => $iid,
                'itemid' => $itemid,
                'value' => $item['valuebrutto'],
                $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_TAX] => $item['taxid'],
                'prodid' => $item['prodid'],
                'content' => $item['jm'],
                'count' => $item['count'],
                'pdiscount' => $item['pdiscount'],
                'vdiscount' => $item['vdiscount'],
                'description' => $item['name'],
                $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_TARIFF] => $item['tariffid'],
            );
            $this->db->Execute('INSERT INTO invoicecontents (docid, itemid,
				value, taxid, prodid, content, count, pdiscount, vdiscount, description, tariffid) 
				VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', array_values($args));
            if ($this->syslog) {
                $args[$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST]] = $invoice['customer']['id'];
                $this->syslog->AddMessage(SYSLOG_RES_INVOICECONT, SYSLOG_OPER_ADD, $args, array($SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST], $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_DOC],
                    $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_TAX], $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_TARIFF]));
            }

            $this->AddBalance(array(
                'time' => $cdate,
                'value' => $item['valuebrutto'] * $item['count'] * -1,
                'taxid' => $item['taxid'],
                'customerid' => $invoice['customer']['id'],
                'comment' => $item['name'],
                'docid' => $iid,
                'itemid' => $itemid
            ));
        }

        return $iid;
    }

    public function InvoiceDelete($invoiceid)
    {
        global $SYSLOG_RESOURCE_KEYS;
        $this->db->BeginTrans();
        if ($this->syslog) {
            $customerid = $this->db->GetOne('SELECT customerid FROM documents WHERE id = ?', array($invoiceid));
            $args = array(
                $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_DOC] => $invoiceid,
                $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST] => $customerid,
            );
            $this->syslog->AddMessage(SYSLOG_RES_DOC, SYSLOG_OPER_DELETE, $args, array_keys($args));
            $cashids = $this->db->GetCol('SELECT id FROM cash WHERE docid = ?', array($invoiceid));
            foreach ($cashids as $cashid) {
                $args = array(
                    $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CASH] => $cashid,
                    $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_DOC] => $invoiceid,
                    $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST] => $customerid,
                );
                $this->syslog->AddMessage(SYSLOG_RES_CASH, SYSLOG_OPER_DELETE, $args, array_keys($args));
            }
            $itemids = $this->db->GetCol('SELECT itemid FROM invoicecontents WHERE docid = ?', array($invoiceid));
            foreach ($itemids as $itemid) {
                $args = array(
                    $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_DOC] => $invoiceid,
                    $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST] => $customerid,
                    'itemid' => $itemid,
                );
                $this->syslog->AddMessage(SYSLOG_RES_INVOICECONT, SYSLOG_OPER_DELETE, $args, array($SYSLOG_RESOURCE_KEYS[SYSLOG_RES_DOC], $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST]));
            }
        }
        $this->db->Execute('DELETE FROM documents WHERE id = ?', array($invoiceid));
        $this->db->Execute('DELETE FROM invoicecontents WHERE docid = ?', array($invoiceid));
        $this->db->Execute('DELETE FROM cash WHERE docid = ?', array($invoiceid));
        $this->db->CommitTrans();
    }

    public function InvoiceContentDelete($invoiceid, $itemid = 0)
    {
        global $SYSLOG_RESOURCE_KEYS;
        if ($itemid) {
            $this->db->BeginTrans();
            if ($this->syslog) {
                $customerid = $this->db->GetOne('SELECT customerid FROM documents
					JOIN invoicecontents ON docid = id WHERE id = ?', array($invoiceid));
                $args = array(
                    $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_DOC] => $invoiceid,
                    $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST] => $customerid,
                    'itemid' => $itemid,
                );
                $this->syslog->AddMessage(SYSLOG_RES_INVOICECONT, SYSLOG_OPER_DELETE, $args, array($SYSLOG_RESOURCE_KEYS[SYSLOG_RES_DOC], $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST]));
            }
            $this->db->Execute('DELETE FROM invoicecontents WHERE docid=? AND itemid=?', array($invoiceid, $itemid));

            if (!$this->db->GetOne('SELECT COUNT(*) FROM invoicecontents WHERE docid=?', array($invoiceid))) {
                // if that was the last item of invoice contents
                $this->db->Execute('DELETE FROM documents WHERE id = ?', array($invoiceid));
                if ($this->syslog) {
                    $args = array(
                        $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_DOC] => $invoiceid,
                        $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST] => $customerid,
                    );
                    $this->syslog->AddMessage(SYSLOG_RES_DOC, SYSLOG_OPER_DELETE, $args, array_keys($args));
                }
            }

            if ($this->syslog) {
                $cashid = $this->db->GetOne('SELECT id FROM cash WHERE docid = ? AND itemid = ?', array($invoiceid, $itemid));
                $args = array(
                    $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CASH] => $cashid,
                    $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_DOC] => $invoiceid,
                    $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST] => $customerid,
                );
                $this->syslog->AddMessage(SYSLOG_RES_CASH, SYSLOG_OPER_DELETE, $args, array_keys($args));
            }
            $this->db->Execute('DELETE FROM cash WHERE docid = ? AND itemid = ?', array($invoiceid, $itemid));
            $this->db->CommitTrans();
        } else
            $this->InvoiceDelete($invoiceid);
    }

    public function GetInvoiceContent($invoiceid)
    {
        global $PAYTYPES, $LMS;

        if ($result = $this->db->GetRow('SELECT d.id, d.number, d.name, d.customerid,
				d.userid, d.address, d.zip, d.city, d.countryid, cn.name AS country,
				d.ten, d.ssn, d.cdate, d.sdate, d.paytime, d.paytype, d.numberplanid,
				d.closed, d.reference, d.reason, d.divisionid,
				(SELECT name FROM users WHERE id = d.userid) AS user, n.template,
				d.div_name AS division_name, d.div_shortname AS division_shortname,
				d.div_address AS division_address, d.div_zip AS division_zip,
				d.div_city AS division_city, d.div_countryid AS division_countryid, 
				d.div_ten AS division_ten, d.div_regon AS division_regon, d.div_account AS account,
				d.div_inv_header AS division_header, d.div_inv_footer AS division_footer,
				d.div_inv_author AS division_author, d.div_inv_cplace AS division_cplace,
				c.pin AS customerpin, c.divisionid AS current_divisionid,
				c.street, c.building, c.apartment,
				c.post_street, c.post_building, c.post_apartment,
				c.post_name, c.post_address, c.post_zip, c.post_city, c.post_countryid
				FROM documents d
				JOIN customeraddressview c ON (c.id = d.customerid)
				LEFT JOIN countries cn ON (cn.id = d.countryid)
				LEFT JOIN numberplans n ON (d.numberplanid = n.id)
				WHERE d.id = ? AND (d.type = ? OR d.type = ?)', array($invoiceid, DOC_INVOICE, DOC_CNOTE))) {

			$result['bankaccounts'] = $this->db->GetCol('SELECT contact FROM customercontacts
				WHERE customerid = ? AND (type & ?) = ?',
				array($result['customerid'], CONTACT_BANKACCOUNT | CONTACT_INVOICES | CONTACT_DISABLED,
					CONTACT_BANKACCOUNT | CONTACT_INVOICES));
			if (empty($result['bankaccounts']))
				$result['bankaccounts'] = array();

            $result['pdiscount'] = 0;
            $result['vdiscount'] = 0;
            $result['totalbase'] = 0;
            $result['totaltax'] = 0;
            $result['total'] = 0;

            if ($result['reference'])
                $result['invoice'] = $this->GetInvoiceContent($result['reference']);

            if (!$result['division_header']) {
                $location_manager = new LMSLocationManager($this->db, $this->auth, $this->cache, $this->syslog);
                $result['division_header'] = $result['division_name'] . "\n"
                        . $result['division_address'] . "\n" . $result['division_zip'] . ' ' . $result['division_city']
                        . ($result['division_countryid'] && $result['countryid'] && $result['division_countryid'] != $result['countryid'] ? "\n" . trans($location_manager->GetCountryName($result['division_countryid'])) : '')
                        . ($result['division_ten'] != '' ? "\n" . trans('TEN') . ' ' . $result['division_ten'] : '');
            }

            if ($result['content'] = $this->db->GetAll('SELECT invoicecontents.value AS value, 
						itemid, taxid, taxes.value AS taxvalue, taxes.label AS taxlabel, 
						prodid, content, count, invoicecontents.description AS description, 
						tariffid, itemid, pdiscount, vdiscount 
						FROM invoicecontents 
						LEFT JOIN taxes ON taxid = taxes.id 
						WHERE docid=? 
						ORDER BY itemid', array($invoiceid))
            )
                foreach ($result['content'] as $idx => $row) {
                    if (isset($result['invoice'])) {
                        $row['value'] += $result['invoice']['content'][$idx]['value'];
                        $row['count'] += $result['invoice']['content'][$idx]['count'];
                    }

                    $result['content'][$idx]['basevalue'] = round(($row['value'] / (100 + $row['taxvalue']) * 100), 2);
                    $result['content'][$idx]['total'] = round($row['value'] * $row['count'], 2);
                    $result['content'][$idx]['totalbase'] = round($result['content'][$idx]['total'] / (100 + $row['taxvalue']) * 100, 2);
                    $result['content'][$idx]['totaltax'] = round($result['content'][$idx]['total'] - $result['content'][$idx]['totalbase'], 2);
                    $result['content'][$idx]['value'] = $row['value'];
                    $result['content'][$idx]['count'] = $row['count'];

                    if (isset($result['taxest'][$row['taxvalue']])) {
                        $result['taxest'][$row['taxvalue']]['base'] += $result['content'][$idx]['totalbase'];
                        $result['taxest'][$row['taxvalue']]['total'] += $result['content'][$idx]['total'];
                        $result['taxest'][$row['taxvalue']]['tax'] += $result['content'][$idx]['totaltax'];
                    } else {
                        $result['taxest'][$row['taxvalue']]['base'] = $result['content'][$idx]['totalbase'];
                        $result['taxest'][$row['taxvalue']]['total'] = $result['content'][$idx]['total'];
                        $result['taxest'][$row['taxvalue']]['tax'] = $result['content'][$idx]['totaltax'];
                        $result['taxest'][$row['taxvalue']]['taxlabel'] = $row['taxlabel'];
                    }

                    $result['totalbase'] += $result['content'][$idx]['totalbase'];
                    $result['totaltax'] += $result['content'][$idx]['totaltax'];
                    $result['total'] += $result['content'][$idx]['total'];

                    // for backward compatybility
                    $result['taxest'][$row['taxvalue']]['taxvalue'] = $row['taxvalue'];
                    $result['content'][$idx]['pkwiu'] = $row['prodid'];

                    $result['pdiscount'] += $row['pdiscount'];
                    $result['vdiscount'] += $row['vdiscount'];
                }

            $result['pdate'] = $result['cdate'] + ($result['paytime'] * 86400);
            $result['value'] = $result['total'] - (isset($result['invoice']) ? $result['invoice']['value'] : 0);

            if ($result['value'] < 0) {
                $result['value'] = abs($result['value']);
                $result['rebate'] = true;
            }
            $result['valuep'] = round(($result['value'] - floor($result['value'])) * 100);

            $customer_manager = new LMSCustomerManager($this->db, $this->auth, $this->cache, $this->syslog);
            $result['customerbalance'] = $customer_manager->GetCustomerBalance($result['customerid'], $result['cdate'] + 1);
            // NOTE: don't waste CPU/mem when printing history is not set:
            if (ConfigHelper::checkValue(ConfigHelper::getConfig('invoices.print_balance_history', false))) {
                if (ConfigHelper::checkValue(ConfigHelper::getConfig('invoices.print_balance_history_save', false))) {
                    $result['customerbalancelist'] = $customer_manager->GetCustomerBalanceList($result['customerid'], $result['cdate']);
                } else {
                    $result['customerbalancelist'] = $customer_manager->GetCustomerBalanceList($result['customerid']);
                }
                $result['customerbalancelistlimit'] = ConfigHelper::getConfig('invoices.print_balance_history_limit');
            }

            $result['paytypename'] = $PAYTYPES[$result['paytype']];

            // for backward compat.
            $result['totalg'] = round(($result['value'] - floor($result['value'])) * 100);
            $result['year'] = date('Y', $result['cdate']);
            $result['month'] = date('m', $result['cdate']);
            $result['pesel'] = $result['ssn'];
            $result['nip'] = $result['ten'];
            if ($result['post_name'] || $result['post_address']) {
                $result['serviceaddr'] = $result['post_name'];
                if ($result['post_address'])
                    $result['serviceaddr'] .= "\n" . $result['post_address'];
                if ($result['post_zip'] && $result['post_city'])
                    $result['serviceaddr'] .= "\n" . $result['post_zip'] . ' ' . $result['post_city'];
            }

            $result['disable_protection'] = ConfigHelper::checkConfig('invoices.disable_protection');

            return $result;
        } else
            return FALSE;
    }

    public function GetNoteContent($id)
    {
        if ($result = $this->db->GetRow('SELECT d.id, d.number, d.name, d.customerid,
				d.userid, d.address, d.zip, d.city, d.countryid, cn.name AS country,
				d.ten, d.ssn, d.cdate, d.numberplanid, d.closed, d.divisionid, d.paytime, 
				(SELECT name FROM users WHERE id = d.userid) AS user, n.template,
				d.div_name AS division_name, d.div_shortname AS division_shortname,
				d.div_address AS division_address, d.div_zip AS division_zip,
				d.div_city AS division_city, d.div_countryid AS division_countryid, 
				d.div_ten AS division_ten, d.div_regon AS division_regon, d.div_account AS account,
				d.div_inv_header AS division_header, d.div_inv_footer AS division_footer,
				d.div_inv_author AS division_author, d.div_inv_cplace AS division_cplace,
				c.pin AS customerpin, c.divisionid AS current_divisionid,
				c.street, c.building, c.apartment,
				c.post_street, c.post_building, c.post_apartment,
				c.post_name, c.post_address, c.post_zip, c.post_city, c.post_countryid
				FROM documents d
				JOIN customeraddressview c ON (c.id = d.customerid)
				LEFT JOIN countries cn ON (cn.id = d.countryid)
				LEFT JOIN numberplans n ON (d.numberplanid = n.id)
				WHERE d.id = ? AND d.type = ?', array($id, DOC_DNOTE))) {

			$result['bankaccounts'] = $this->db->GetCol('SELECT contact FROM customercontacts
				WHERE customerid = ? AND (type & ?) = ?',
				array($result['customerid'], CONTACT_BANKACCOUNT | CONTACT_INVOICES | CONTACT_DISABLED,
					CONTACT_BANKACCOUNT | CONTACT_INVOICES));
			if (empty($result['bankaccounts']))
				$result['bankaccounts'] = array();

            $result['value'] = 0;

            if (!$result['division_header']) {
                $location_manager = new LMSLocationManager($this->db, $this->auth, $this->cache, $this->syslog);
                $result['division_header'] = $result['division_name'] . "\n"
                        . $result['division_address'] . "\n" . $result['division_zip'] . ' ' . $result['division_city']
                        . ($result['division_countryid'] && $result['countryid'] && $result['division_countryid'] != $result['countryid'] ? "\n" . trans($location_manager->GetCountryName($result['division_countryid'])) : '')
                        . ($result['division_ten'] != '' ? "\n" . trans('TEN') . ' ' . $result['division_ten'] : '');
            }

            if ($result['content'] = $this->db->GetAll('SELECT
				value, itemid, description 
				FROM debitnotecontents 
				WHERE docid=? 
				ORDER BY itemid', array($id))
            )
                foreach ($result['content'] as $idx => $row) {
                    $result['content'][$idx]['value'] = $row['value'];
                    $result['value'] += $row['value'];
                }

            $result['valuep'] = round(($result['value'] - floor($result['value'])) * 100);
            $result['pdate'] = $result['cdate'] + ($result['paytime'] * 86400);

            // NOTE: don't waste CPU/mem when printing history is not set:
            if (ConfigHelper::checkValue(ConfigHelper::getConfig('notes.print_balance', false))) {
                if (ConfigHelper::checkValue(ConfigHelper::getConfig('notes.print_balance_history', false))) {
                    $result['customerbalancelist'] = $LMS->GetCustomerBalanceList($result['customerid'], $result['cdate']);
                } else {
                    $result['customerbalancelist'] = $LMS->GetCustomerBalanceList($result['customerid']);
                }
                $result['customerbalancelistlimit'] = ConfigHelper::getConfig('notes.print_balance_history_limit');
            }

            // for backward compatibility
            if ($result['post_name'] || $result['post_address']) {
                $result['serviceaddr'] = $result['post_name'];
                if ($result['post_address'])
                    $result['serviceaddr'] .= "\n" . $result['post_address'];
                if ($result['post_zip'] && $result['post_city'])
                    $result['serviceaddr'] .= "\n" . $result['post_zip'] . ' ' . $result['post_city'];
            }

            return $result;
        } else
            return FALSE;
    }

    public function TariffAdd($tariff)
    {
        global $SYSLOG_RESOURCE_KEYS;
        $args = array(
            'name' => $tariff['name'],
            'description' => $tariff['description'],
            'value' => $tariff['value'],
            'period' => $tariff['period'] ? $tariff['period'] : null,
            $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_TAX] => $tariff['taxid'],
            $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NUMPLAN] => $tariff['numberplanid'] ? $tariff['numberplanid'] : null,
            'prodid' => $tariff['prodid'],
            'uprate' => $tariff['uprate'],
            'downrate' => $tariff['downrate'],
            'upceil' => $tariff['upceil'],
            'downceil' => $tariff['downceil'],
            'climit' => $tariff['climit'],
            'plimit' => $tariff['plimit'],
            'uprate_n' => $tariff['uprate_n'],
            'downrate_n' => $tariff['downrate_n'],
            'upceil_n' => $tariff['upceil_n'],
            'downceil_n' => $tariff['downceil_n'],
            'climit_n' => $tariff['climit_n'],
            'plimit_n' => $tariff['plimit_n'],
            'dlimit' => $tariff['dlimit'],
            'type' => $tariff['type'],
            'sh_limit' => $tariff['sh_limit'],
            'www_limit' => $tariff['www_limit'],
            'mail_limit' => $tariff['mail_limit'],
            'sql_limit' => $tariff['sql_limit'],
            'ftp_limit' => $tariff['ftp_limit'],
            'quota_sh_limit' => $tariff['quota_sh_limit'],
            'quota_www_limit' => $tariff['quota_www_limit'],
            'quota_mail_limit' => $tariff['quota_mail_limit'],
            'quota_sql_limit' => $tariff['quota_sql_limit'],
            'quota_ftp_limit' => $tariff['quota_ftp_limit'],
            'domain_limit' => $tariff['domain_limit'],
            'alias_limit' => $tariff['alias_limit'],
        );
        $result = $this->db->Execute('INSERT INTO tariffs (name, description, value,
				period, taxid, numberplanid, prodid, uprate, downrate, upceil, downceil, climit,
				plimit, uprate_n, downrate_n, upceil_n, downceil_n, climit_n,
				plimit_n, dlimit, type, sh_limit, www_limit, mail_limit, sql_limit,
				ftp_limit, quota_sh_limit, quota_www_limit, quota_mail_limit,
				quota_sql_limit, quota_ftp_limit, domain_limit, alias_limit)
				VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)', array_values($args));
        if ($result) {
            $id = $this->db->GetLastInsertID('tariffs');
            if ($this->syslog) {
                $args[$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_TARIFF]] = $id;
                $this->syslog->AddMessage(SYSLOG_RES_TARIFF, SYSLOG_OPER_ADD, $args,
                	array($SYSLOG_RESOURCE_KEYS[SYSLOG_RES_TARIFF], $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_TAX], $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NUMPLAN]));
            }
            return $id;
        } else
            return FALSE;
    }

    public function TariffUpdate($tariff)
    {
        global $SYSLOG_RESOURCE_KEYS;
        $args = array(
            'name' => $tariff['name'],
            'description' => $tariff['description'],
            'value' => $tariff['value'],
            'period' => $tariff['period'] ? $tariff['period'] : null,
            $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_TAX] => $tariff['taxid'],
            $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NUMPLAN] => $tariff['numberplanid'] ? $tariff['numberplanid'] : null,
            'prodid' => $tariff['prodid'],
            'uprate' => $tariff['uprate'],
            'downrate' => $tariff['downrate'],
            'upceil' => $tariff['upceil'],
            'downceil' => $tariff['downceil'],
            'climit' => $tariff['climit'],
            'plimit' => $tariff['plimit'],
            'uprate_n' => $tariff['uprate_n'],
            'downrate_n' => $tariff['downrate_n'],
            'upceil_n' => $tariff['upceil_n'],
            'downceil_n' => $tariff['downceil_n'],
            'climit_n' => $tariff['climit_n'],
            'plimit_n' => $tariff['plimit_n'],
            'dlimit' => $tariff['dlimit'],
            'sh_limit' => $tariff['sh_limit'],
            'www_limit' => $tariff['www_limit'],
            'mail_limit' => $tariff['mail_limit'],
            'sql_limit' => $tariff['sql_limit'],
            'ftp_limit' => $tariff['ftp_limit'],
            'quota_sh_limit' => $tariff['quota_sh_limit'],
            'quota_www_limit' => $tariff['quota_www_limit'],
            'quota_mail_limit' => $tariff['quota_mail_limit'],
            'quota_sql_limit' => $tariff['quota_sql_limit'],
            'quota_ftp_limit' => $tariff['quota_ftp_limit'],
            'domain_limit' => $tariff['domain_limit'],
            'alias_limit' => $tariff['alias_limit'],
            'type' => $tariff['type'],
            $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_TARIFF] => $tariff['id']
        );
        $res = $this->db->Execute('UPDATE tariffs SET name=?, description=?, value=?,
				period=?, taxid=?, numberplanid=?, prodid=?, uprate=?, downrate=?, upceil=?, downceil=?,
				climit=?, plimit=?, uprate_n=?, downrate_n=?, upceil_n=?, downceil_n=?,
				climit_n=?, plimit_n=?, dlimit=?, sh_limit=?, www_limit=?, mail_limit=?,
				sql_limit=?, ftp_limit=?, quota_sh_limit=?, quota_www_limit=?,
				quota_mail_limit=?, quota_sql_limit=?, quota_ftp_limit=?,
				domain_limit=?, alias_limit=?, type=? WHERE id=?', array_values($args));
        if ($res && $this->syslog)
            $this->syslog->AddMessage(SYSLOG_RES_TARIFF, SYSLOG_OPER_UPDATE, $args,
            	array($SYSLOG_RESOURCE_KEYS[SYSLOG_RES_TARIFF], $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_TAX], $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NUMPLAN]));
        return $res;
    }

    public function TariffDelete($id)
    {
        global $SYSLOG_RESOURCE_KEYS;
        if ($this->syslog)
            $assigns = $this->db->GetAll('SELECT promotionid, a.id, promotionschemaid FROM promotionassignments a
				JOIN promotionschemas s ON s.id = a.promotionschemaid
				WHERE a.tariffid = ?', array($id));
        $res = $this->db->Execute('DELETE FROM tariffs WHERE id=?', array($id));
        if ($res && $this->syslog) {
            $this->syslog->AddMessage(SYSLOG_RES_TARIFF, SYSLOG_OPER_DELETE, array($SYSLOG_RESOURCE_KEYS[SYSLOG_RES_TARIFF] => $id), array($SYSLOG_RESOURCE_KEYS[SYSLOG_RES_TARIFF]));
            if (!empty($assigns))
                foreach ($assigns as $assign) {
                    $args = array(
                        $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_PROMOASSIGN] => $assign['id'],
                        $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_PROMOSCHEMA] => $assign['promotionschemaid'],
                        $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_PROMO] => $assign['promotionid'],
                        $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_TARIFF] => $id
                    );
                    $this->syslog->AddMessage(SYSLOG_RES_PROMOASSIGN, SYSLOG_OPER_DELETE, $args, array_keys($args));
                }
        }
        return $res;
    }

    public function GetTariff($id, $network = NULL)
    {
        if ($network) {
            $network_manager = new LMSNetworkManager($this->db, $this->auth, $this->cache, $this->syslog);
            $net = $network_manager->GetNetworkParams($network);
        }

        $result = $this->db->GetRow('SELECT t.*, taxes.label AS tax, taxes.value AS taxvalue
			FROM tariffs t
			LEFT JOIN taxes ON (t.taxid = taxes.id)
			WHERE t.id=?', array($id));

        $result['customers'] = $this->db->GetAll('SELECT c.id AS id, COUNT(c.id) AS cnt, '
                . $this->db->Concat('c.lastname', "' '", 'c.name') . ' AS customername '
                . ($network ? ', COUNT(vnodes.id) AS nodescount ' : '')
                . 'FROM assignments, customerview c '
                . ($network ? 'LEFT JOIN vnodes ON (c.id = vnodes.ownerid) ' : '')
                . 'WHERE c.id = customerid AND deleted = 0 AND tariffid = ? '
                . ($network ? 'AND ((ipaddr > ' . $net['address'] . ' AND ipaddr < ' . $net['broadcast'] . ') OR (ipaddr_pub > '
                        . $net['address'] . ' AND ipaddr_pub < ' . $net['broadcast'] . ')) ' : '')
                . 'GROUP BY c.id, c.lastname, c.name ORDER BY c.lastname, c.name', array($id));

        $unactive = $this->db->GetRow('SELECT COUNT(*) AS count,
            SUM(CASE t.period
				WHEN ' . MONTHLY . ' THEN t.value
				WHEN ' . QUARTERLY . ' THEN t.value/3
				WHEN ' . HALFYEARLY . ' THEN t.value/6
				WHEN ' . YEARLY . ' THEN t.value/12
				ELSE (CASE a.period
				    WHEN ' . MONTHLY . ' THEN t.value
				    WHEN ' . QUARTERLY . ' THEN t.value/3
				    WHEN ' . HALFYEARLY . ' THEN t.value/6
				    WHEN ' . YEARLY . ' THEN t.value/12
				    ELSE 0
				    END)
				END) AS value
			FROM assignments a
			JOIN tariffs t ON (t.id = a.tariffid)
			WHERE t.id = ? AND (
			            a.suspended = 1
			            OR a.datefrom > ?NOW?
			            OR (a.dateto <= ?NOW? AND a.dateto != 0)
			            OR EXISTS (
			                    SELECT 1 FROM assignments b
					    WHERE b.customerid = a.customerid
						    AND liabilityid = 0 AND tariffid = 0
						    AND b.datefrom <= ?NOW? AND (b.dateto > ?NOW? OR b.dateto = 0)
				    )
			)', array($id));

        $all = $this->db->GetRow('SELECT COUNT(*) AS count,
			SUM(CASE t.period
				WHEN ' . MONTHLY . ' THEN t.value
				WHEN ' . QUARTERLY . ' THEN t.value/3
				WHEN ' . HALFYEARLY . ' THEN t.value/6
				WHEN ' . YEARLY . ' THEN t.value/12
				ELSE (CASE a.period
				    WHEN ' . MONTHLY . ' THEN t.value
				    WHEN ' . QUARTERLY . ' THEN t.value/3
				    WHEN ' . HALFYEARLY . ' THEN t.value/6
				    WHEN ' . YEARLY . ' THEN t.value/12
				    ELSE 0
				    END)
				 END) AS value
			FROM assignments a
			JOIN tariffs t ON (t.id = a.tariffid)
			WHERE tariffid = ?', array($id));

        // count of all customers with that tariff
        $result['customerscount'] = sizeof($result['customers']);
        // count of all assignments
        $result['count'] = $all['count'];
        // count of 'active' assignments
        $result['activecount'] = $all['count'] - $unactive['count'];
        // avg monthly income (without unactive assignments)
        $result['totalval'] = $all['value'] - $unactive['value'];

        $result['rows'] = ceil($result['customerscount'] / 2);
        return $result;
    }

    public function GetTariffs()
    {
        return $this->db->GetAll('SELECT t.id, t.name, t.value, uprate, taxid, prodid,
				downrate, upceil, downceil, climit, plimit, taxes.value AS taxvalue,
				taxes.label AS tax, t.period
				FROM tariffs t
				LEFT JOIN taxes ON t.taxid = taxes.id
				WHERE t.disabled = 0
				ORDER BY t.name, t.value DESC');
    }

    public function TariffSet($id)
    {
        if ($this->db->GetOne('SELECT disabled FROM tariffs WHERE id = ?', array($id)) == 1)
            return $this->db->Execute('UPDATE tariffs SET disabled = 0 WHERE id = ?', array($id));
        else
            return $this->db->Execute('UPDATE tariffs SET disabled = 1 WHERE id = ?', array($id));
    }

    public function TariffExists($id)
    {
        return ($this->db->GetOne('SELECT id FROM tariffs WHERE id=?', array($id)) ? TRUE : FALSE);
    }

    public function ReceiptContentDelete($docid, $itemid = 0)
    {
        global $SYSLOG_RESOURCE_KEYS;
        if ($itemid) {
            if ($this->syslog) {
                $customerid = $this->db->GetOne('SELECT customerid FROM documents WHERE id=?', array($docid));
                $args = array(
                    $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_DOC] => $docid,
                    $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST] => $customerid,
                    'itemid' => $itemid,
                );
                $this->syslog->AddMessage(SYSLOG_RES_RECEIPTCONT, SYSLOG_OPER_DELETE, $args, array($SYSLOG_RESOURCE_KEYS[SYSLOG_RES_DOC], $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST]));
            }
            $this->db->Execute('DELETE FROM receiptcontents WHERE docid=? AND itemid=?', array($docid, $itemid));

            if (!$this->db->GetOne('SELECT COUNT(*) FROM receiptcontents WHERE docid=?', array($docid))) {
                // if that was the last item of invoice contents
                if ($this->syslog) {
                    $args = array(
                        $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_DOC] => $docid,
                        $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST] => $customerid,
                    );
                    $this->syslog->AddMessage(SYSLOG_RES_DOC, SYSLOG_OPER_DELETE, $args, array_keys($args));
                }
                $this->db->Execute('DELETE FROM documents WHERE id = ?', array($docid));
            }
            if ($this->syslog) {
                $cashid = $this->db->GetOne('SELECT id FROM cash WHERE docid = ? AND itemid = ?', array($docid, $itemid));
                $args = array(
                    $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CASH] => $cashid,
                    $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_DOC] => $docid,
                    $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST] => $customerid,
                );
                $this->syslog->AddMessage(SYSLOG_RES_CASH, SYSLOG_OPER_DELETE, $args, array_keys($args));
            }
            $this->db->Execute('DELETE FROM cash WHERE docid = ? AND itemid = ?', array($docid, $itemid));
        } else {
            if ($this->syslog) {
                $customerid = $this->db->GetOne('SELECT customerid FROM documents WHERE id=?', array($docid));
                $itemids = $this->db->GetCol('SELECT itemid FROM receiptcontents WHERE docid=?', array($docid));
                foreach ($itemids as $itemid) {
                    $args = array(
                        $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_DOC] => $docid,
                        $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST] => $customerid,
                        'itemid' => $itemid,
                    );
                    $this->syslog->AddMessage(SYSLOG_RES_RECEIPTCONT, SYSLOG_OPER_DELETE, $args, array_keys($args));
                }
                $args = array(
                    $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_DOC] => $docid,
                    $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST] => $customerid,
                );
                $this->syslog->AddMessage(SYSLOG_RES_DOC, SYSLOG_OPER_DELETE, $args, array_keys($args));
                $cashids = $this->db->GetCol('SELECT id FROM cash WHERE docid=?', array($docid));
                foreach ($cashids as $itemid) {
                    $args = array(
                        $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CASH] => $itemid,
                        $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_DOC] => $docid,
                        $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST] => $customerid,
                    );
                    $this->syslog->AddMessage(SYSLOG_RES_CASH, SYSLOG_OPER_DELETE, $args, array_keys($args));
                }
            }
            $this->db->Execute('DELETE FROM receiptcontents WHERE docid=?', array($docid));
            $this->db->Execute('DELETE FROM documents WHERE id = ?', array($docid));
            $this->db->Execute('DELETE FROM cash WHERE docid = ?', array($docid));
        }
    }

    public function DebitNoteContentDelete($docid, $itemid = 0)
    {
        global $SYSLOG_RESOURCE_KEYS;
        if ($itemid) {
            if ($this->syslog) {
                list ($dnotecontid, $customerid) = array_values($this->db->GetRow('SELECT dn.id, customerid FROM debitnotecontents dn
					JOIN documents d ON d.id = dn.docid WHERE docid=? AND itemid=?', array($docid, $itemid)));
                $args = array(
                    $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_DNOTECONT] => $dnotecontid,
                    $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_DOC] => $docid,
                    $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST] => $customerid,
                );
                $this->syslog->AddMessage(SYSLOG_RES_DNOTECONT, SYSLOG_OPER_DELETE, $args, array_keys($args));
            }
            $this->db->Execute('DELETE FROM debitnotecontents WHERE docid=? AND itemid=?', array($docid, $itemid));

            if (!$this->db->GetOne('SELECT COUNT(*) FROM debitnotecontents WHERE docid=?', array($docid))) {
                // if that was the last item of debit note contents
                if ($this->syslog) {
                    $args = array(
                        $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_DOC] => $docid,
                        $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST] => $customerid,
                    );
                    $this->syslog->AddMessage(SYSLOG_RES_DOC, SYSLOG_OPER_DELETE, $args, array_keys($args));
                }
                $this->db->Execute('DELETE FROM documents WHERE id = ?', array($docid));
            }
            if ($this->syslog) {
                $cashid = $this->db->GetOne('SELECT id FROM cash WHERE docid = ? AND itemid = ?', array($docid, $itemid));
                $args = array(
                    $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CASH] => $cashid,
                    $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_DOC] => $docid,
                    $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST] => $customerid,
                );
                $this->syslog->AddMessage(SYSLOG_RES_CASH, SYSLOG_OPER_DELETE, $args, array_keys($args));
            }
            $this->db->Execute('DELETE FROM cash WHERE docid = ? AND itemid = ?', array($docid, $itemid));
        } else {
            if ($this->syslog) {
                $customerid = $this->db->GetOne('SELECT customerid FROM documents WHERE id=?', array($docid));
                $dnotecontids = $this->db->GetCol('SELECT id FROM debitnotecontents WHERE docid=?', array($docid));
                foreach ($dnotecontids as $itemid) {
                    $args = array(
                        $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_DNOTECONT] => $itemid,
                        $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_DOC] => $docid,
                        $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST] => $customerid,
                    );
                    $this->syslog->AddMessage(SYSLOG_RES_DNOTECONT, SYSLOG_OPER_DELETE, $args, array_keys($args));
                }
                $args = array(
                    $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_DOC] => $docid,
                    $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST] => $customerid,
                );
                $this->syslog->AddMessage(SYSLOG_RES_DOC, SYSLOG_OPER_DELETE, $args, array_keys($args));
                $cashids = $this->db->GetCol('SELECT id FROM cash WHERE docid=?', array($docid));
                foreach ($cashids as $itemid) {
                    $args = array(
                        $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CASH] => $itemid,
                        $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_DOC] => $docid,
                        $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST] => $customerid,
                    );
                    $this->syslog->AddMessage(SYSLOG_RES_CASH, SYSLOG_OPER_DELETE, $args, array_keys($args));
                }
            }
            $this->db->Execute('DELETE FROM debitnotecontents WHERE docid=?', array($docid));
            $this->db->Execute('DELETE FROM documents WHERE id = ?', array($docid));
            $this->db->Execute('DELETE FROM cash WHERE docid = ?', array($docid));
        }
    }

    public function AddBalance($addbalance)
    {
        global $SYSLOG_RESOURCE_KEYS;
        $args = array(
            'time' => isset($addbalance['time']) ? $addbalance['time'] : time(),
            $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_USER] => isset($addbalance['userid']) ? $addbalance['userid'] : $this->auth->id,
            'value' => str_replace(',', '.', round($addbalance['value'], 2)),
            'type' => isset($addbalance['type']) ? $addbalance['type'] : 0,
            $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_TAX] => isset($addbalance['taxid']) ? $addbalance['taxid'] : 0,
            $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST] => $addbalance['customerid'],
            'comment' => $addbalance['comment'],
            $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_DOC] => isset($addbalance['docid']) ? $addbalance['docid'] : 0,
            'itemid' => isset($addbalance['itemid']) ? $addbalance['itemid'] : 0,
            $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CASHIMPORT] => !empty($addbalance['importid']) ? $addbalance['importid'] : NULL,
            $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CASHSOURCE] => !empty($addbalance['sourceid']) ? $addbalance['sourceid'] : NULL,
        );
        $res = $this->db->Execute('INSERT INTO cash (time, userid, value, type, taxid,
			customerid, comment, docid, itemid, importid, sourceid)
			VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', array_values($args));
        if ($res && $this->syslog) {
            unset($args[$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_USER]]);
            $args[$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CASH]] = $this->db->GetLastInsertID('cash');
            $this->syslog->AddMessage(SYSLOG_RES_CASH, SYSLOG_OPER_ADD, $args, array($SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CASH], $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_USER],
                $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_TAX], $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST],
                $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_DOC], $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CASHIMPORT],
                $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CASHSOURCE]));
        }
        return $res;
    }

    public function DelBalance($id)
    {
        global $SYSLOG_RESOURCE_KEYS;

        $row = $this->db->GetRow('SELECT cash.customerid, docid, itemid, documents.type AS doctype, importid
					FROM cash
					LEFT JOIN documents ON (docid = documents.id)
					WHERE cash.id = ?', array($id));

        if ($row['doctype'] == DOC_INVOICE || $row['doctype'] == DOC_CNOTE)
            $this->InvoiceContentDelete($row['docid'], $row['itemid']);
        elseif ($row['doctype'] == DOC_RECEIPT)
            $this->ReceiptContentDelete($row['docid'], $row['itemid']);
        elseif ($row['doctype'] == DOC_DNOTE)
            $this->DebitNoteContentDelete($row['docid'], $row['itemid']);
        else {
            $this->db->Execute('DELETE FROM cash WHERE id = ?', array($id));
            if ($this->syslog) {
                $args = array(
                    $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CASH] => $id,
                    $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST] => $row['customerid'],
                );
                $this->syslog->AddMessage(SYSLOG_RES_CASH, SYSLOG_OPER_DELETE, $args, array_keys($args));
            }
            if ($row['importid']) {
                if ($this->syslog) {
                    $cashimport = $this->db->GetRow('SELECT customerid, sourceid, sourcefileid FROM cashimport WHERE id = ?', array($row['importid']));
                    $args = array(
                        $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CASHIMPORT] => $row['importid'],
                        $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST] => $cashimport['customerid'],
                        $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CASHSOURCE] => $cashimport['sourceid'],
                        $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_SOURCEFILE] => $cashimport['sourcefileid'],
                        'closed' => 0,
                    );
                    $this->syslog->AddMessage(SYSLOG_RES_CASHIMPORT, SYSLOG_OPER_UPDATE, $args, array($SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CASHIMPORT],
                        $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST],
                        $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CASHSOURCE],
                        $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_SOURCEFILE]));
                }
                $this->db->Execute('UPDATE cashimport SET closed = 0 WHERE id = ?', array($row['importid']));
            }
        }
    }

    public function GetPaymentList()
    {
        if ($paymentlist = $this->db->GetAll('SELECT id, name, creditor, value, period, at, description FROM payments ORDER BY name ASC'))
            foreach ($paymentlist as $idx => $row) {
                switch ($row['period']) {
                    case DAILY:
                        $row['payday'] = trans('daily');
                        break;
                    case WEEKLY:
                        $row['payday'] = trans('weekly ($a)', strftime("%a", mktime(0, 0, 0, 0, $row['at'] + 5, 0)));
                        break;
                    case MONTHLY:
                        $row['payday'] = trans('monthly ($a)', $row['at']);
                        break;
                    case QUARTERLY:
                        $row['payday'] = trans('quarterly ($a)', sprintf('%02d/%02d', $row['at'] % 100, $row['at'] / 100 + 1));
                        break;
                    case HALFYEARLY:
                        $row['payday'] = trans('half-yearly ($a)', sprintf('%02d/%02d', $row['at'] % 100, $row['at'] / 100 + 1));
                        break;
                    case YEARLY:
                        $row['payday'] = trans('yearly ($a)', date('d/m', ($row['at'] - 1) * 86400));
                        break;
                }

                $paymentlist[$idx] = $row;
            }

        $paymentlist['total'] = sizeof($paymentlist);

        return $paymentlist;
    }

    public function GetPayment($id)
    {
        $payment = $this->db->GetRow('SELECT id, name, creditor, value, period, at, description FROM payments WHERE id=?', array($id));

        switch ($payment['period']) {
            case DAILY:
                $payment['payday'] = trans('daily');
                break;
            case WEEKLY:
                $payment['payday'] = trans('weekly ($a)', strftime("%a", mktime(0, 0, 0, 0, $payment['at'] + 5, 0)));
                break;
            case MONTHLY:
                $payment['payday'] = trans('monthly ($a)', $payment['at']);
                break;
            case QUARTERLY:
                $payment['payday'] = trans('quarterly ($a)', sprintf('%02d/%02d', $payment['at'] % 100, $payment['at'] / 100 + 1));
                break;
            case HALFYEARLY:
                $payment['payday'] = trans('half-yearly ($a)', sprintf('%02d/%02d', $payment['at'] % 100, $payment['at'] / 100 + 1));
                break;
            case YEARLY:
                $payment['payday'] = trans('yearly ($a)', date('d/m', ($payment['at'] - 1) * 86400));
                break;
        }
        return $payment;
    }

    public function GetPaymentName($id)
    {
        return $this->db->GetOne('SELECT name FROM payments WHERE id=?', array($id));
    }

    public function GetPaymentIDByName($name)
    {
        return $this->db->GetOne('SELECT id FROM payments WHERE name=?', array($name));
    }

    public function PaymentExists($id)
    {
        return ($this->db->GetOne('SELECT id FROM payments WHERE id=?', array($id)) ? TRUE : FALSE);
    }

    public function PaymentAdd($paymentdata)
    {
        global $SYSLOG_RESOURCE_KEYS;
        $args = array(
            'name' => $paymentdata['name'],
            'creditor' => $paymentdata['creditor'],
            'description' => $paymentdata['description'],
            'value' => $paymentdata['value'],
            'period' => $paymentdata['period'],
            'at' => $paymentdata['at'],
        );
        if ($this->db->Execute('INSERT INTO payments (name, creditor, description, value, period, at)
			VALUES (?, ?, ?, ?, ?, ?)', array_values($args))) {
            $id = $this->db->GetLastInsertID('payments');
            if ($this->syslog) {
                $args[$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_PAYMENT]] = $id;
                $this->syslog->AddMessage(SYSLOG_RES_PAYMENT, SYSLOG_OPER_ADD, $args, array($SYSLOG_RESOURCE_KEYS[SYSLOG_RES_PAYMENT]));
            }
            return $id;
        } else
            return FALSE;
    }

    public function PaymentDelete($id)
    {
        global $SYSLOG_RESOURCE_KEYS;
        if ($this->syslog) {
            $args = array($SYSLOG_RESOURCE_KEYS[SYSLOG_RES_PAYMENT] => $id);
            $this->syslog->AddMessage(SYSLOG_RES_PAYMENT, SYSLOG_OPER_DELETE, $args, array_keys($args));
        }
        return $this->db->Execute('DELETE FROM payments WHERE id=?', array($id));
    }

    public function PaymentUpdate($paymentdata)
    {
        global $SYSLOG_RESOURCE_KEYS;
        $args = array(
            'name' => $paymentdata['name'],
            'creditor' => $paymentdata['creditor'],
            'description' => $paymentdata['description'],
            'value' => $paymentdata['value'],
            'period' => $paymentdata['period'],
            'at' => $paymentdata['at'],
            $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_PAYMENT] => $paymentdata['id'],
        );
        $res = $this->db->Execute('UPDATE payments SET name=?, creditor=?, description=?, value=?, period=?, at=? WHERE id=?', array_values($args));
        if ($res && $this->syslog)
            $this->syslog->AddMessage(SYSLOG_RES_PAYMENT, SYSLOG_OPER_UPDATE, $args, array($SYSLOG_RESOURCE_KEYS[SYSLOG_RES_PAYMENT]));
        return $res;
    }

    public function GetHostingLimits($customerid)
    {
        $result = array('alias_limit' => 0,
            'domain_limit' => 0,
            'sh_limit' => 0,
            'www_limit' => 0,
            'ftp_limit' => 0,
            'mail_limit' => 0,
            'sql_limit' => 0,
            'quota_sh_limit' => 0,
            'quota_www_limit' => 0,
            'quota_ftp_limit' => 0,
            'quota_mail_limit' => 0,
            'quota_sql_limit' => 0,
        );

        if ($limits = $this->db->GetAll('SELECT alias_limit, domain_limit, sh_limit,
			www_limit, mail_limit, sql_limit, ftp_limit, quota_sh_limit,
			quota_www_limit, quota_mail_limit, quota_sql_limit, quota_ftp_limit
	                FROM tariffs WHERE id IN (SELECT tariffid FROM assignments
				WHERE customerid = ? AND tariffid != 0
				AND (dateto > ?NOW? OR dateto = 0)
				AND (datefrom < ?NOW? OR datefrom = 0))', array($customerid))) {
            foreach ($limits as $row)
                foreach ($row as $idx => $val)
                    if ($val === NULL || $result[$idx] === NULL) {
                        $result[$idx] = NULL; // no limit
                    } else {
                        $result[$idx] += $val;
                    }
        }

        return $result;
    }

    public function GetTaxes($from = NULL, $to = NULL)
    {
        $from = $from ? $from : mktime(0, 0, 0);
        $to = $to ? $to : mktime(23, 59, 59);

        return $this->db->GetAllByKey('SELECT id, value, label, taxed FROM taxes
			WHERE (validfrom = 0 OR validfrom <= ?)
			    AND (validto = 0 OR validto >= ?)
			ORDER BY value', 'id', array($from, $to));
    }
    
    public function CalcAt($period, $date)
    {
        $m = date('n', $date);

        if ($period == YEARLY) {
            if ($m) {
                $ttime = mktime(12, 0, 0, $m, 1, 1990);
                return date('z', $ttime) + 1;
            } else {
                return 1;
            }
        } else if ($period == HALFYEARLY) {
            if ($m > 6)
                $m -= 6;
            return ($m - 1) * 100 + 1;
        } else if ($period == QUARTERLY) {
            if ($m > 9)
                $m -= 9;
            else if ($m > 6)
                $m -= 6;
            else if ($m > 3)
                $m -= 3;
            return ($m - 1) * 100 + 1;
        } else {
            return 1;
        }
    }

}
