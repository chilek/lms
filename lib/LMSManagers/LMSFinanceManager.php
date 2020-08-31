<?php

/*
 *  LMS version 1.11-git
 *
 *  Copyright (C) 2001-2020 LMS Developers
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
 */
class LMSFinanceManager extends LMSManager implements LMSFinanceManagerInterface
{
    const INVOICE_CONTENT_DETAIL_GENERAL = 1;
    const INVOICE_CONTENT_DETAIL_MORE = 2;
    const INVOICE_CONTENT_DETAIL_ALL = 3;

    private $currency_values = array();

    public function GetPromotionNameBySchemaID($id)
    {
        return $this->db->GetOne('SELECT p.name FROM promotionschemas AS s
		LEFT JOIN promotions AS p ON s.promotionid = p.id WHERE s.id = ?', array($id));
    }
    public function GetPromotionNameByID($id)
    {
        return $this->db->GetOne('SELECT name FROM promotions WHERE id=?', array($id));
    }

    public function GetCustomerTariffsValue($id)
    {
        return $this->db->GetAllByKey('SELECT SUM(tariffs.value * a.count) AS value, tariffs.currency
		    FROM assignments a, tariffs
			WHERE tariffid = tariffs.id AND customerid = ? AND suspended = 0 AND commited = 1
			    AND a.datefrom <= ?NOW? AND (a.dateto > ?NOW? OR a.dateto = 0)
			GROUP BY tariffs.currency', 'currency', array($id));
    }

    public function GetCustomerAssignmentValue($id)
    {
        $suspension_percentage = f_round(ConfigHelper::getConfig('finances.suspension_percentage'));

        return $this->db->GetAllByKey('SELECT SUM(sum), currency FROM
            (SELECT SUM((CASE a.suspended
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
                END) * a.count) AS sum,
                (CASE WHEN t.currency IS NULL THEN l.currency ELSE t.currency END) AS currency
                FROM assignments a
                LEFT JOIN tariffs t ON t.id = a.tariffid
                LEFT JOIN liabilities l ON l.id = a.liabilityid
                WHERE customerid = ? AND suspended = 0 AND commited = 1 AND a.period <> ' . DISPOSABLE . '
                    AND a.datefrom <= ?NOW? AND (a.dateto > ?NOW? OR a.dateto = 0)
                GROUP BY t.currency, l.currency
            ) as ca
            GROUP BY ca.currency', 'currency', array($id));
    }

    public function GetCustomerAssignments($id, $show_expired = false, $show_approved = true)
    {
        $now = mktime(0, 0, 0, date('n'), date('d'), date('Y'));

        $assignments = $this->db->GetAll('SELECT
                                            a.id AS id, a.tariffid, a.customerid, a.period, a.backwardperiod,
                                            a.at, a.suspended, a.invoice, a.settlement,
                                            a.datefrom, a.dateto, a.pdiscount,
                                            a.vdiscount AS unitary_vdiscount,
                                            (a.vdiscount * a.count) AS vdiscount,                                                                                        
                                            a.attribute, a.liabilityid, a.separatedocument,
                                            (CASE WHEN t.splitpayment IS NULL THEN l.splitpayment ELSE t.splitpayment END) AS splitpayment,
                                            (CASE WHEN t.taxcategory IS NULL THEN l.taxcategory ELSE t.taxcategory END) AS taxcategory,
                                            ROUND(t.uprate * a.count) AS uprate,
                                            uprate AS unitary_uprate,
                                            ROUND(t.upceil * a.count) AS upceil,
                                            upceil AS unitary_upceil,
                                            ROUND(t.downceil * a.count) AS downceil,
                                            downceil AS unitary_downceil,
                                            ROUND(t.downrate * a.count) AS downrate,
                                            downrate AS unitary_downrate,
                                            t.type AS tarifftype,
                                            (CASE WHEN t.value IS NULL THEN l.value ELSE t.value END) AS unitary_value,
                                            a.count,
                                            (CASE WHEN t.value IS NULL THEN l.value ELSE t.value END) * a.count AS value,
                                            (CASE WHEN t.currency IS NULL THEN l.currency ELSE t.currency END) AS currency,
                                            (CASE WHEN t.name IS NULL THEN l.name ELSE t.name END) AS name,
                                            d.number AS docnumber, d.type AS doctype, d.cdate, np.template,
                                            d.fullnumber,
                                            (CASE WHEN (a.dateto > ' . $now . ' OR a.dateto = 0) AND (a.at >= ' . $now . ' OR a.at < 531) THEN 0 ELSE 1 END) AS expired,
                                            commited
                                          FROM
                                            assignments a
                                            LEFT JOIN tariffs t     ON (a.tariffid = t.id)
                                            LEFT JOIN liabilities l ON (a.liabilityid = l.id)
                                            LEFT JOIN documents d ON d.id = a.docid
                                            LEFT JOIN numberplans np ON np.id = d.numberplanid
                                          WHERE a.customerid=? ' . ($show_approved ? 'AND a.commited = 1 ' : '')
                                            . (!$show_expired ? 'AND (a.dateto > ' . $now . ' OR a.dateto = 0) AND (a.at >= ' . $now . ' OR a.at < 531)' : '') . '
                                          ORDER BY
                                            a.datefrom, t.name, value', array($id));

        if ($assignments) {
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
                        $row['payday'] = trans('monthly ($a)', $row['at'] ?: trans('last day'));
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

                $row['docnumber'] = docnumber(array(
                    'number' => $row['docnumber'],
                    'template' => $row['numtemplate'],
                    'cdate' => $row['cdate'],
                    'customerid' => $id,
                ));

                $assignments[$idx] = $row;

                // assigned nodes
                $assignments[$idx]['nodes'] = $this->db->GetAll('SELECT vn.name, vn.id, vn.location, vn.ownerid,
                                                                    nd.id AS netdev_id, nd.name AS netdev_name,
                                                                    nd.ownerid AS netdev_ownerid
                                                                 FROM
                                                                   nodeassignments, vnodes vn
                                                                   LEFT JOIN netdevices nd ON vn.netdev = nd.id
                                                                 WHERE
                                                                   nodeid = vn.id AND
                                                                   assignmentid = ?', array($row['id']));

                $assignments[$idx]['phones'] = $this->db->GetAllByKey('SELECT vn.phone
                                                                       FROM
                                                                         voip_number_assignments vna
                                                                         LEFT JOIN voip_numbers vn ON vna.number_id = vn.id
                                                                       WHERE
                                                                         assignment_id = ?', 'phone', array($row['id']));

                $assignments[$idx]['discounted_value'] = (((100 - $row['pdiscount']) * $row['value']) / 100) - $row['vdiscount'];

                if ($row['suspended'] == 1) {
                    $assignments[$idx]['discounted_value'] = $assignments[$idx]['discounted_value'] * ConfigHelper::getConfig('finances.suspension_percentage') / 100;
                }

                $assignments[$idx]['discounted_value'] = round($assignments[$idx]['discounted_value'], 2);

                $now = time();

                if ($row['suspended'] == 0 &&
                        (($row['datefrom'] == 0 || $row['datefrom'] < $now) &&
                        ($row['dateto'] == 0 || $row['dateto'] > $now))) {
                    // for proper summary
                    $assignments[$idx]['real_unitary_value'] = $row['unitary_value'];
                    $assignments[$idx]['real_count'] = $row['count'];
                    $assignments[$idx]['real_value'] = $row['value'];
                    $assignments[$idx]['real_disc_value'] = $assignments[$idx]['discounted_value'];
                    $assignments[$idx]['real_unitary_downrate'] = $row['unitary_downrate'];
                    $assignments[$idx]['real_downrate'] = $row['downrate'];
                    $assignments[$idx]['real_unitary_downceil'] = $row['unitary_downceil'];
                    $assignments[$idx]['real_downceil'] = $row['downceil'];
                    $assignments[$idx]['real_unitary_uprate'] = $row['unitary_uprate'];
                    $assignments[$idx]['real_uprate'] = $row['uprate'];
                    $assignments[$idx]['real_unitary_upceil'] = $row['unitary_upceil'];
                    $assignments[$idx]['real_upceil'] = $row['upceil'];
                }
            }
        }

        return $assignments;
    }

    public function GetCustomerServiceSummary($id)
    {
        global $SERVICETYPES;

        $now = mktime(0, 0, 0, date('n'), date('d'), date('Y'));
        $suspension_percentage = f_round(ConfigHelper::getConfig('finances.suspension_percentage'));

        $servicesassignments = $this->db->GetAll('SELECT
            t.type AS tarifftype,
            ROUND(SUM((CASE a.suspended
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
                END) * a.count), 2) AS sumvalue
            FROM
            assignments a
            LEFT JOIN tariffs t ON a.tariffid = t.id
            LEFT JOIN liabilities l ON a.liabilityid = l.id
            WHERE a.customerid= ?
            AND a.commited = 1
            AND a.period <> ' . DISPOSABLE . '
            AND a.datefrom <= ' . $now . ' AND (a.dateto > ' . $now . ' OR a.dateto = 0)
            GROUP BY tarifftype', array($id));

        if ($servicesassignments) {
            $total_value = 0;
            foreach ($servicesassignments as $idx => $row) {
                $servicesassignments[$idx]['tarifftypename'] = $SERVICETYPES[$row['tarifftype']];
                $total_value += $row['sumvalue'];
            }
            $servicesassignments['total_value'] = $total_value;
        }

        return $servicesassignments;
    }

    public function DeleteAssignment($id)
    {
        if ($this->syslog) {
            $custid = $this->db->GetOne('SELECT customerid FROM assignments WHERE id=?', array($id));

            $nodeassigns = $this->db->GetAll('SELECT id, nodeid FROM nodeassignments WHERE assignmentid = ?', array($id));
            if (!empty($nodeassigns)) {
                foreach ($nodeassigns as $nodeassign) {
                    $args = array(
                    SYSLOG::RES_NODEASSIGN => $nodeassign['id'],
                    SYSLOG::RES_CUST => $custid,
                    SYSLOG::RES_NODE => $nodeassign['nodeid'],
                    SYSLOG::RES_ASSIGN => $id
                    );
                    $this->syslog->AddMessage(SYSLOG::RES_NODEASSIGN, SYSLOG::OPER_DELETE, $args);
                }
            }

            $assign = $this->db->GetRow('SELECT tariffid, liabilityid FROM assignments WHERE id=?', array($id));
            $lid = $assign['liabilityid'];
            $tid = $assign['tariffid'];
            if ($lid) {
                $args = array(
                    SYSLOG::RES_LIAB => $lid,
                    SYSLOG::RES_CUST => $custid
                );
                $this->syslog->AddMessage(SYSLOG::RES_LIAB, SYSLOG::OPER_DELETE, $args);
            }
        }
        $this->db->Execute('DELETE FROM liabilities WHERE id=(SELECT liabilityid FROM assignments WHERE id=?)', array($id));
        $this->db->Execute('DELETE FROM assignments WHERE id=?', array($id));
        if ($this->syslog) {
            $args = array(
                SYSLOG::RES_TARIFF => $tid,
                SYSLOG::RES_LIAB => $lid,
                SYSLOG::RES_ASSIGN => $id,
                SYSLOG::RES_CUST => $custid
            );
            $this->syslog->AddMessage(SYSLOG::RES_ASSIGN, SYSLOG::OPER_DELETE, $args);
        }
    }

    public function AddAssignment($data)
    {
        $result = array();

        $commited = (!isset($data['commited']) || $data['commited'] ? 1 : 0);

        // Create assignments according to promotion schema
        if (!empty($data['promotionassignmentid']) && !empty($data['schemaid'])) {
            $tariff = $this->db->GetRow('SELECT a.data, s.data AS sdata, t.name, t.value, t.currency, t.period,
                                            t.id, t.prodid, t.taxid, t.splitpayment, t.taxcategory
					                     FROM
					                     	promotionassignments a
						                    JOIN promotionschemas s ON (s.id = a.promotionschemaid)
						                    JOIN tariffs t ON (t.id = a.tariffid)
					                     WHERE a.id = ?', array($data['promotionassignmentid']));
            $data['tariffid'] = $tariff['id'];

            $data_schema = explode(';', $tariff['sdata']);
            $data_tariff = explode(';', $tariff['data']);
            $datefrom    = $data['datefrom'];
            $cday        = date('d', $datefrom);

            $use_discounts = ConfigHelper::checkConfig('phpui.promotion_use_discounts');

            foreach ($data_tariff as $idx => $dt) {
                list($value, $period) = explode(':', $dt);

                if (isset($data['modifiedvalues'][$idx])) {
                    $value = $data['modifiedvalues'][$idx];
                }

                // Activation
                if (!$idx) {
                    // if activation value specified, create disposable liability
                    if (f_round($value)) {
                        $start_day   = date('d', $data['datefrom']);
                        $start_month = date('n', $data['datefrom']);
                        $start_year  = date('Y', $data['datefrom']);

                        // payday is before the start of the period
                        // set activation payday to next month's payday
                        $activation_at_next_day = ConfigHelper::getConfig('phpui.promotion_activation_at_next_day', '', true);
                        if (ConfigHelper::checkValue($activation_at_next_day) || preg_match('/^(absolute|business)$/', $activation_at_next_day)) {
                            $_datefrom = $data['datefrom'];
                            $datefrom = time() + 86400;
                            if ($activation_at_next_day == 'business') {
                                $datefrom = Utils::findNextBusinessDay($datefrom);
                            }
                        } elseif (($data['at'] === 0 && $start_day >= date('j', mktime(12, 0, 0, $start_month + 1, 0, $start_year)))
                            || ($data['at'] > 0 && $start_day >= $data['at'])) {
                            $_datefrom = $data['datefrom'];
                            $datefrom = mktime(0, 0, 0, $start_month + ($data['at'] === 0 ? 2 : 1), $data['at'], $start_year);
                        } elseif ($data['at'] === 0) {
                            $_datefrom = $data['datefrom'];
                            $datefrom = mktime(0, 0, 0, $start_month + 1, 0, $start_year);
                        }

                        // check if current promotion schema tariff has only activation value defined
                        $only_activation = true;
                        for ($periodical_idx = 1; $periodical_idx < count($data_tariff); $periodical_idx++) {
                            if (strpos($data_tariff[$periodical_idx], 'NULL') !== 0) {
                                $only_activation = false;
                                break;
                            }
                        }

                        if ($only_activation) {
                            if ($use_discounts) {
                                $tariffid = $tariff['id'];
                            } else {
                                $tariffid = $this->db->GetOne(
                                    'SELECT id FROM tariffs
                                                               WHERE
                                                                    name   = ? AND
                                                                    value  = ? AND
                                                                    currency = ?
                                                               LIMIT 1',
                                    array($tariff['name'],
                                        empty($value) || $value == 'NULL' ? 0 : str_replace(',', '.', $value),
                                        $tariff['currency']
                                    )
                                );

                                // ... if not found clone tariff
                                if (!$tariffid) {
                                    $args = $this->db->GetRow('SELECT
                                                              name, value, splitpayment, taxcategory, currency, period, taxid, type,
                                                              upceil, downceil, uprate, downrate,
                                                              up_burst_time, up_burst_threshold, up_burst_limit,
                                                              down_burst_time, down_burst_threshold, down_burst_limit,
                                                              prodid, plimit, climit, dlimit,
                                                              upceil_n, downceil_n, uprate_n, downrate_n,
                                                              up_burst_time_n, up_burst_threshold_n, up_burst_limit_n,
                                                              down_burst_time_n, down_burst_threshold_n, down_burst_limit_n,
                                                              domain_limit, alias_limit, sh_limit,
                                                              www_limit, ftp_limit, mail_limit, sql_limit, quota_sh_limit, quota_www_limit,
                                                              quota_ftp_limit, quota_mail_limit, quota_sql_limit, authtype
                                                           FROM
                                                              tariffs WHERE id = ?', array($tariff['id']));

                                    $args = array_merge($args, array(
                                        'name' => $tariff['name'],
                                        'value' => str_replace(',', '.', $value),
                                        'period' => $tariff['period']));

                                    $args[SYSLOG::RES_TAX] = $args['taxid'];
                                    unset($args['taxid']);

                                    $this->db->Execute('INSERT INTO tariffs
                                                       (name, value, splitpayment, taxcategory, currency, period, type,
                                                       upceil, downceil, uprate, downrate,
                                                       up_burst_time, up_burst_threshold, up_burst_limit,
                                                       down_burst_time, down_burst_threshold, down_burst_limit,
                                                       prodid, plimit, climit, dlimit,
                                                       upceil_n, downceil_n, uprate_n, downrate_n,
                                                       up_burst_time_n, up_burst_threshold_n, up_burst_limit_n,
                                                       down_burst_time_n, down_burst_threshold_n, down_burst_limit_n,
                                                       domain_limit, alias_limit, sh_limit, www_limit, ftp_limit, mail_limit, sql_limit,
                                                       quota_sh_limit, quota_www_limit, quota_ftp_limit, quota_mail_limit, quota_sql_limit,
                                                       authtype, taxid)
                                                    VALUES
                                                       (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
                                                       ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', array_values($args));

                                    $tariffid = $this->db->GetLastInsertId('tariffs');

                                    if ($this->syslog) {
                                        $args[SYSLOG::RES_TARIFF] = $tariffid;
                                        $this->syslog->AddMessage(SYSLOG::RES_TARIFF, SYSLOG::OPER_ADD, $args);
                                    }
                                }
                            }
                        } else {
                            $args = array(
                                'name' => trans('Activation payment'),
                                'value' => str_replace(',', '.', $value),
                                'splitpayment' => $tariff['splitpayment'],
                                'taxcategory' => $tariff['taxcategory'],
                                'currency' => $tariff['currency'],
                                SYSLOG::RES_TAX => intval($tariff['taxid']),
                                'prodid' => $tariff['prodid']
                            );
                            $this->db->Execute('INSERT INTO liabilities (name, value, splitpayment, taxcategory, currency, taxid, prodid)
                                VALUES (?, ?, ?, ?, ?, ?, ?)', array_values($args));

                            $lid = $this->db->GetLastInsertID('liabilities');

                            if ($this->syslog) {
                                $args[SYSLOG::RES_LIAB] = $lid;
                                $args[SYSLOG::RES_CUST] = $data['customerid'];
                                $this->syslog->AddMessage(SYSLOG::RES_LIAB, SYSLOG::OPER_ADD, $args);
                            }

                            $tariffid = 0;
                        }

                        $period   = DISPOSABLE;
                        $at = $datefrom;
                    } else {
                        continue;
                    }
                } else {
                // promotion period
                    $lid = 0;

                    if (!$period) {
                        $period = $data['period'];
                    }

                    $datefrom  = !empty($_datefrom) ? $_datefrom : $datefrom;
                    $_datefrom = 0;
                    $at        = (ConfigHelper::checkConfig('phpui.promotion_preserve_at_day') && $data['at'] !== '')
                                               ? $data['at'] : $this->CalcAt($period, $datefrom);

                    $length    = $data_schema[$idx - 1];
                    $month     = date('n', $datefrom);
                    $year      = date('Y', $datefrom);

                    // Find tariff with specified name+value+period...
                    $tariffid = null;

                    if ($value != 'NULL') {
                        if ($use_discounts) {
                            $tariffid = $tariff['id'];
                        } else {
                            if ($tariff['period'] !== null) {
                                $tariffid = $this->db->GetOne(
                                    'SELECT id FROM tariffs
                                                               WHERE
                                                                    name   = ? AND
                                                                    value  = ? AND
                                                                    currency = ? AND
                                                                    period = ?
                                                               LIMIT 1',
                                    array($tariff['name'],
                                        empty($value) || $value == 'NULL' ? 0 : str_replace(',', '.', $value),
                                        $tariff['currency'],
                                        $tariff['period'])
                                );
                            } else {
                                $tariffid = $this->db->GetOne(
                                    '
                                    SELECT id FROM tariffs
                                    WHERE name = ? AND value = ? AND currency = ? AND period IS NULL
                                    LIMIT 1',
                                    array(
                                        $tariff['name'],
                                        empty($value) || $value == 'NULL' ? 0 : str_replace(',', '.', $value),
                                        $tariff['currency'],
                                    )
                                );
                            }
                        }

                        // ... if not found clone tariff
                        if (!$tariffid) {
                            $args = $this->db->GetRow('SELECT
														  name, value, splitpayment, taxcategory, currency, period, taxid, type,
														  upceil, downceil, uprate, downrate,
														  up_burst_time, up_burst_threshold, up_burst_limit, 
														  down_burst_time, down_burst_threshold, down_burst_limit, 
														  prodid, plimit, climit, dlimit,
														  upceil_n, downceil_n, uprate_n, downrate_n,
														  up_burst_time_n, up_burst_threshold_n, up_burst_limit_n, 
														  down_burst_time_n, down_burst_threshold_n, down_burst_limit_n, 
														  domain_limit, alias_limit, sh_limit,
														  www_limit, ftp_limit, mail_limit, sql_limit, quota_sh_limit, quota_www_limit,
														  quota_ftp_limit, quota_mail_limit, quota_sql_limit, authtype
													   FROM
														  tariffs WHERE id = ?', array($tariff['id']));

                            $args = array_merge($args, array(
                                'name' => $tariff['name'],
                                'value' => str_replace(',', '.', $value),
                                'period' => $tariff['period']));

                            $args[SYSLOG::RES_TAX] = $args['taxid'];
                            unset($args['taxid']);

                            $this->db->Execute('INSERT INTO tariffs
												   (name, value, splitpayment, taxcategory, currency, period, type,
												   upceil, downceil, uprate, downrate,
												   up_burst_time, up_burst_threshold, up_burst_limit, 
												   down_burst_time, down_burst_threshold, down_burst_limit, 
												   prodid, plimit, climit, dlimit,
												   upceil_n, downceil_n, uprate_n, downrate_n,
												   up_burst_time_n, up_burst_threshold_n, up_burst_limit_n, 
												   down_burst_time_n, down_burst_threshold_n, down_burst_limit_n, 
												   domain_limit, alias_limit, sh_limit, www_limit, ftp_limit, mail_limit, sql_limit,
												   quota_sh_limit, quota_www_limit, quota_ftp_limit, quota_mail_limit, quota_sql_limit,
												   authtype, taxid)
												VALUES
												   (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
												   ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', array_values($args));

                            $tariffid = $this->db->GetLastInsertId('tariffs');

                            if ($this->syslog) {
                                $args[SYSLOG::RES_TARIFF] = $tariffid;
                                $this->syslog->AddMessage(SYSLOG::RES_TARIFF, SYSLOG::OPER_ADD, $args);
                            }
                        }
                    }

                    // creates assignment record for starting partial period
                    if (isset($data['settlement']) && $data['settlement'] == 2 && $idx == 1 && $period == MONTHLY) {
                        $val = $value;
                        if ($tariff['period'] && $period != DISPOSABLE
                            && $tariff['period'] != $period) {
                            if ($tariff['period'] == YEARLY) {
                                $val = $val / 12.0;
                            } elseif ($tariff['period'] == HALFYEARLY) {
                                $val = $val / 6.0;
                            } elseif ($tariff['period'] == QUARTERLY) {
                                $val = $val / 3.0;
                            }

                            if ($period == YEARLY) {
                                $val = $val * 12.0;
                            } elseif ($period == HALFYEARLY) {
                                $val = $val * 6.0;
                            } elseif ($period == QUARTERLY) {
                                $val = $val * 3.0;
                            } elseif ($period == WEEKLY) {
                                $val = $val / 4.0;
                            } elseif ($period == DAILY) {
                                $val = $val / 30.0;
                            }
                        }
                        $discounted_val = $val;

                        list ($year, $month, $dom) = explode('/', date('Y/m/d', $data['datefrom']));
                        $nextperiod = mktime(0, 0, 0, $month + 1, 1, $year);
                        $partial_dateto = !empty($data['dateto']) && $nextperiod > $data['dateto'] ? $data['dateto'] + 1 : $nextperiod;
                        $diffdays = ($partial_dateto - $data['datefrom']) / 86400;
                        if ($diffdays > 0) {
                            list ($y, $m) = explode('/', date('Y/m', $partial_dateto - 1));
                            $month_days = strftime("%d", mktime(0, 0, 0, $m + 1, 0, $y));

                            $partial_dateto--;
                            if (($data['at'] > 0 && $data['at'] >= $dom + 1) || ($data['at'] === 0 && $month_days >= $dom + 1)) {
                                $partial_at = $data['at'];
                            } else {
                                $partial_at = $dom + 1;
                            }

                            if ($value != 'NULL') {
                                $v = $diffdays * $discounted_val / $month_days;
                                $partial_vdiscount = str_replace(',', '.', round(abs($v - $val), 2));

                                $args = array(
                                    SYSLOG::RES_TARIFF => $tariffid,
                                    SYSLOG::RES_CUST => $data['customerid'],
                                    'period' => $period,
                                    'backwardperiod' => $data['backwardperiod'],
                                    'at' => $partial_at,
                                    'count' => $data['count'],
                                    'invoice' => isset($data['invoice']) ? $data['invoice'] : 0,
                                    'separatedocument' => isset($data['separatedocument']) ? 1 : 0,
                                    'settlement' => 0,
                                    SYSLOG::RES_NUMPLAN => !empty($data['numberplanid']) ? $data['numberplanid'] : null,
                                    'paytype' => !empty($data['paytype']) ? $data['paytype'] : null,
                                    'datefrom' => $datefrom,
                                    'dateto' => $partial_dateto,
                                    'pdiscount' => 0,
                                    'vdiscount' => str_replace(',', '.', ($use_discounts ? $tariff['value'] - $val : 0) + $partial_vdiscount),
                                    'attribute' => !empty($data['attribute']) ? $data['attribute'] : null,
                                    SYSLOG::RES_LIAB => null,
                                    'recipient_address_id' => $data['recipient_address_id'] > 0 ? $data['recipient_address_id'] : null,
                                    'docid' => empty($data['docid']) ? null : $data['docid'],
                                    'commited' => $commited,
                                );

                                $result[] = $data['assignmentid'] = $this->insertAssignment($args);

                                $this->insertNodeAssignments($data);
                                $this->insertPhoneAssignments($data);
                            }

                            $datefrom = $partial_dateto + 1;
                        }
                    }

                    // assume $data['at'] == 1, set last day of the specified month
                    $dateto = mktime(23, 59, 59, $month + (empty($length) ? 0 : $length) + ($cday && $cday != 1 ? 1 : 0), 0, $year);
                    $cday = 0;
                }

                if (!empty($lid) || $value != 'NULL') {
                    $ending_period_date = 0;

                    // creates assignment record for ending partial period
                    if ($idx && $idx == count($data_tariff) - 1 && $period == MONTHLY && isset($data['last-settlement'])
                        && $data['dateto'] && $data['dateto'] > $dateto) {
                        $val = $value;
                        if ($tariff['period'] && $period != DISPOSABLE
                            && $tariff['period'] != $period) {
                            if ($tariff['period'] == YEARLY) {
                                $val = $val / 12.0;
                            } elseif ($tariff['period'] == HALFYEARLY) {
                                $val = $val / 6.0;
                            } elseif ($tariff['period'] == QUARTERLY) {
                                $val = $val / 3.0;
                            }

                            if ($period == YEARLY) {
                                $val = $val * 12.0;
                            } elseif ($period == HALFYEARLY) {
                                $val = $val * 6.0;
                            } elseif ($period == QUARTERLY) {
                                $val = $val * 3.0;
                            } elseif ($period == WEEKLY) {
                                $val = $val / 4.0;
                            } elseif ($period == DAILY) {
                                $val = $val / 30.0;
                            }
                        }
                        $discounted_val = $val;

                        list ($year, $month, $dom) = explode('/', date('Y/m/d', $data['dateto']));
                        $prevperiod = mktime(0, 0, 0, $month, 1, $year);
                        $diffdays = sprintf("%d", ($data['dateto'] + 1 - $prevperiod) / 86400);
                        if ($diffdays > 0) {
                            $month_days = strftime("%d", mktime(0, 0, 0, $month + 1, 0, $year));
                            $v = $diffdays * $discounted_val / $month_days;
                            $partial_vdiscount = str_replace(',', '.', round(abs($v - $val), 2));
                            $partial_datefrom = $prevperiod;
                            if ($data['at'] > 0 && $data['at'] < $dom) {
                                $partial_at = $data['at'];
                            } else {
                                $partial_at = $dom - 1;
                            }

                            $args = array(
                                SYSLOG::RES_TARIFF  => empty($data['tariffid']) ? null : $tariffid,
                                SYSLOG::RES_CUST    => $data['customerid'],
                                'period'            => $period,
                                'backwardperiod'    => $data['backwardperiod'],
                                'at'                => $partial_at,
                                'count'             => $data['count'],
                                'invoice'           => isset($data['invoice']) ? $data['invoice'] : 0,
                                'separatedocument'  => isset($data['separatedocument']) ? 1 : 0,
                                'settlement'        => 0,
                                SYSLOG::RES_NUMPLAN => !empty($data['numberplanid']) ? $data['numberplanid'] : null,
                                'paytype'           => !empty($data['paytype']) ? $data['paytype'] : null,
                                'datefrom'          => $partial_datefrom,
                                'dateto'            => $data['dateto'],
                                'pdiscount'         => 0,
                                'vdiscount'         => str_replace(',', '.', ($use_discounts ? $tariff['value'] - $val : 0) + $partial_vdiscount),
                                'attribute'         => !empty($data['attribute']) ? $data['attribute'] : null,
                                SYSLOG::RES_LIAB    => null,
                                'recipient_address_id' => $data['recipient_address_id'] > 0 ? $data['recipient_address_id'] : null,
                                'docid'             => empty($data['docid']) ? null : $data['docid'],
                                'commited'          => $commited,
                            );

                            $result[] = $data['assignmentid'] = $this->insertAssignment($args);

                            $this->insertNodeAssignments($data);
                            $this->insertPhoneAssignments($data);
                        }

                        $ending_period_date = mktime(23, 59, 59, $month, 0, $year);
                    }

                    $__datefrom = $idx ? $datefrom : 0;
                    $__dateto = $idx && ($idx < count($data_tariff) - 1) ? $dateto : $ending_period_date;

                    if ($__datefrom < $__dateto || !$__dateto) {
                        // creates assignment record for schema period
                        $args = array(
                            SYSLOG::RES_TARIFF => empty($tariffid) ? null : $tariffid,
                            SYSLOG::RES_CUST => $data['customerid'],
                            'period' => $period,
                            'backwardperiod' => $data['backwardperiod'],
                            'at' => $at,
                            'count' => $data['count'],
                            'invoice' => isset($data['invoice']) ? $data['invoice'] : 0,
                            'separatedocument' => isset($data['separatedocument']) ? 1 : 0,
                            'settlement' => isset($data['settlement']) && $data['settlement'] == 1 && $idx == 1 ? 1 : 0,
                            SYSLOG::RES_NUMPLAN => !empty($data['numberplanid']) ? $data['numberplanid'] : null,
                            'paytype' => !empty($data['paytype']) ? $data['paytype'] : null,
                            'datefrom' => $__datefrom,
                            'dateto' => $__dateto,
                            'pdiscount' => 0,
                            'vdiscount' => str_replace(',', '.', ($use_discounts ? $tariff['value'] - $value : 0)),
                            'attribute' => !empty($data['attribute']) ? $data['attribute'] : null,
                            SYSLOG::RES_LIAB => empty($lid) ? null : $lid,
                            'recipient_address_id' => $data['recipient_address_id'] > 0 ? $data['recipient_address_id'] : null,
                            'docid' => empty($data['docid']) ? null : $data['docid'],
                            'commited' => $commited,
                        );

                        $result[] = $data['assignmentid'] = $this->insertAssignment($args);

                        $this->insertNodeAssignments($data);
                        $this->insertPhoneAssignments($data);
                    }
                }

                if ($idx) {
                    $datefrom = $dateto + 1;
                }
            }
        } else {
            if ($data['period'] == MONTHLY && ((isset($data['settlement']) && $data['settlement'] == 2) || isset($data['last-settlement']))) {
                if (empty($data['tariffid'])) {
                    $val = $data['value'];
                } else {
                    $tariff = $this->db->GetRow('SELECT value, period FROM tariffs WHERE id = ?', array($data['tariffid']));
                    $val = $tariff['value'];
                    if ($tariff['period'] && $data['period'] != DISPOSABLE
                        && $tariff['period'] != $data['period']) {
                        if ($tariff['period'] == YEARLY) {
                            $val = $val / 12.0;
                        } elseif ($tariff['period'] == HALFYEARLY) {
                            $val = $val / 6.0;
                        } elseif ($tariff['period'] == QUARTERLY) {
                            $val = $val / 3.0;
                        }

                        if ($data['period'] == YEARLY) {
                            $val = $val * 12.0;
                        } elseif ($data['period'] == HALFYEARLY) {
                            $val = $val * 6.0;
                        } elseif ($data['period'] == QUARTERLY) {
                            $val = $val * 3.0;
                        } elseif ($data['period'] == WEEKLY) {
                            $val = $val / 4.0;
                        } elseif ($data['period'] == DAILY) {
                            $val = $val / 30.0;
                        }
                    }
                }
                $discounted_val = $val;

                if (!empty($data['pdiscount'])) {
                    $discounted_val = ((100 - $data['pdiscount']) * $val) / 100;
                } elseif (!empty($data['vdiscount'])) {
                    $discounted_val -= $data['vdiscount'];
                }
            }

            // creates assignment record for starting partial period
            if ($data['datefrom'] && isset($data['settlement']) && $data['settlement'] == 2 && $data['period'] == MONTHLY) {
                list ($year, $month, $dom) = explode('/', date('Y/m/d', $data['datefrom']));
                $nextperiod = mktime(0, 0, 0, $month + 1, 1, $year);
                $partial_dateto = !empty($data['dateto']) && $nextperiod > $data['dateto'] ? $data['dateto'] + 1: $nextperiod;
                $diffdays = ($partial_dateto - $data['datefrom']) / 86400;
                if ($diffdays > 0) {
                    list ($y, $m) = explode('/', date('Y/m', $partial_dateto - 1));
                    $month_days = strftime("%d", mktime(0, 0, 0, $m + 1, 0, $y));
                    $value = $diffdays * $discounted_val / $month_days;
                    $partial_vdiscount = str_replace(',', '.', round(abs($value - $val), 2));
                    $partial_dateto--;
                    if (($data['at'] > 0 && $data['at'] >= $dom + 1) || ($data['at'] === 0 && $month_days >= $dom + 1)) {
                        $partial_at = $data['at'];
                    } else {
                        $partial_at = $dom + 1;
                    }

                    if (!empty($data['value'])) {
                        $args = array(
                            'name' => $data['name'],
                            'value' => str_replace(',', '.', $data['value']),
                            'splitpayment' => isset($data['splitpayment']) ? $data['splitpayment'] : 0,
                            'taxcategory' => $data['taxcategory'],
                            'currency' => $data['currency'],
                            SYSLOG::RES_TAX => intval($data['taxid']),
                            'prodid' => $data['prodid']
                        );
                        $this->db->Execute('INSERT INTO liabilities (name, value, splitpayment, taxcategory, currency, taxid, prodid)
					    VALUES (?, ?, ?, ?, ?, ?, ?)', array_values($args));
                        $lid = $this->db->GetLastInsertID('liabilities');
                        if ($this->syslog) {
                            $args[SYSLOG::RES_LIAB] = $lid;
                            $args[SYSLOG::RES_CUST] = $data['customerid'];
                            $this->syslog->AddMessage(SYSLOG::RES_LIAB, SYSLOG::OPER_ADD, $args);
                        }
                    }

                    $args = array(
                        SYSLOG::RES_TARIFF  => empty($data['tariffid']) ? null : intval($data['tariffid']),
                        SYSLOG::RES_CUST    => $data['customerid'],
                        'period'            => $data['period'],
                        'backwardperiod'    => 0,
                        'at'                => $partial_at,
                        'count'             => isset($data['count']) ? $data['count'] : 1,
                        'invoice'           => isset($data['invoice']) ? $data['invoice'] : 0,
                        'separatedocument'  => isset($data['separatedocument']) ? 1 : 0,
                        'settlement'        => 0,
                        SYSLOG::RES_NUMPLAN => !empty($data['numberplanid']) ? $data['numberplanid'] : null,
                        'paytype'           => !empty($data['paytype']) ? $data['paytype'] : null,
                        'datefrom'          => $data['datefrom'],
                        'dateto'            => $partial_dateto,
                        'pdiscount'         => 0,
                        'vdiscount'         => $partial_vdiscount,
                        'attribute'         => !empty($data['attribute']) ? $data['attribute'] : null,
                        SYSLOG::RES_LIAB    => !isset($lid) || empty($lid) ? null : $lid,
                        'recipient_address_id' => $data['recipient_address_id'] > 0 ? $data['recipient_address_id'] : null,
                        'docid'             => empty($data['docid']) ? null : $data['docid'],
                        'commited'          => $commited,
                    );

                    $result[] = $data['assignmentid'] = $this->insertAssignment($args);

                    $this->insertNodeAssignments($data);
                    $this->insertPhoneAssignments($data);

                    $data['datefrom'] = $partial_dateto + 1;
                }
            }

            // creates assignment record for ending partial period
            if ($data['dateto'] && $data['datefrom'] < $data['dateto'] && isset($data['last-settlement']) && $data['period'] == MONTHLY) {
                list ($year, $month, $dom) = explode('/', date('Y/m/d', $data['dateto']));
                $prevperiod = mktime(0, 0, 0, $month, 1, $year);
                $diffdays = sprintf("%d", ($data['dateto'] + 1 - $prevperiod) / 86400);
                if ($diffdays > 0) {
                    $month_days = strftime("%d", mktime(0, 0, 0, $month + 1, 0, $year));
                    $value = $diffdays * $discounted_val / $month_days;
                    $partial_vdiscount = str_replace(',', '.', round(abs($value - $val), 2));
                    $partial_datefrom = $prevperiod;
                    if ($data['at'] > 0 && $data['at'] < $dom) {
                        $partial_at = $data['at'];
                    } else {
                        $partial_at = $dom - 1;
                    }

                    if (!empty($data['value'])) {
                        $args = array(
                            'name' => $data['name'],
                            'value' => str_replace(',', '.', $data['value']),
                            'splitpayment' => isset($data['splitpayment']) ? $data['splitpayment'] : 0,
                            'currency' => $data['currency'],
                            SYSLOG::RES_TAX => intval($data['taxid']),
                            'prodid' => $data['prodid']
                        );
                        $this->db->Execute('INSERT INTO liabilities (name, value, splitpayment, currency, taxid, prodid)
					    VALUES (?, ?, ?, ?, ?, ?)', array_values($args));
                        $lid = $this->db->GetLastInsertID('liabilities');
                        if ($this->syslog) {
                            $args[SYSLOG::RES_LIAB] = $lid;
                            $args[SYSLOG::RES_CUST] = $data['customerid'];
                            $this->syslog->AddMessage(SYSLOG::RES_LIAB, SYSLOG::OPER_ADD, $args);
                        }
                    }

                    $args = array(
                        SYSLOG::RES_TARIFF  => empty($data['tariffid']) ? null : intval($data['tariffid']),
                        SYSLOG::RES_CUST    => $data['customerid'],
                        'period'            => $data['period'],
                        'backwardperiod'    => 0,
                        'at'                => $partial_at,
                        'count'             => isset($data['count']) ? $data['count'] : 1,
                        'invoice'           => isset($data['invoice']) ? $data['invoice'] : 0,
                        'separatedocument'  => isset($data['separatedocument']) ? 1 : 0,
                        'settlement'        => 0,
                        SYSLOG::RES_NUMPLAN => !empty($data['numberplanid']) ? $data['numberplanid'] : null,
                        'paytype'           => !empty($data['paytype']) ? $data['paytype'] : null,
                        'datefrom'          => $partial_datefrom,
                        'dateto'            => $data['dateto'],
                        'pdiscount'         => 0,
                        'vdiscount'         => $partial_vdiscount,
                        'attribute'         => !empty($data['attribute']) ? $data['attribute'] : null,
                        SYSLOG::RES_LIAB    => !isset($lid) || empty($lid) ? null : $lid,
                        'recipient_address_id' => $data['recipient_address_id'] > 0 ? $data['recipient_address_id'] : null,
                        'docid'             => empty($data['docid']) ? null : $data['docid'],
                        'commited'          => $commited,
                    );

                    $result[] = $data['assignmentid'] = $this->insertAssignment($args);

                    $this->insertNodeAssignments($data);
                    $this->insertPhoneAssignments($data);

                    $data['dateto'] = $partial_datefrom - 1;
                }
            }

            if ($data['datefrom'] < $data['dateto'] || !$data['datefrom'] || !$data['dateto']) {
                // creates one assignment record
                if (!empty($data['value'])) {
                    $args = array(
                        'name' => $data['name'],
                        'value' => str_replace(',', '.', $data['value']),
                        'splitpayment' => isset($data['splitpayment']) ? $data['splitpayment'] : 0,
                        'taxcategory' => $data['taxcategory'],
                        'currency' => $data['currency'],
                        SYSLOG::RES_TAX => intval($data['taxid']),
                        'prodid' => $data['prodid']
                    );
                    $this->db->Execute('INSERT INTO liabilities (name, value, splitpayment, taxcategory, currency, taxid, prodid)
							VALUES (?, ?, ?, ?, ?, ?, ?)', array_values($args));
                    $lid = $this->db->GetLastInsertID('liabilities');
                    if ($this->syslog) {
                        $args[SYSLOG::RES_LIAB] = $lid;
                        $args[SYSLOG::RES_CUST] = $data['customerid'];
                        $this->syslog->AddMessage(SYSLOG::RES_LIAB, SYSLOG::OPER_ADD, $args);
                    }
                }

                $args = array(
                    SYSLOG::RES_TARIFF => empty($data['tariffid']) ? null : intval($data['tariffid']),
                    SYSLOG::RES_CUST => $data['customerid'],
                    'period' => $data['period'],
                    'backwardperiod'    => isset($data['backwardperiod']) ? 1 : 0,
                    'at' => $data['at'],
                    'count'             => isset($data['count']) ? $data['count'] : 1,
                    'invoice' => isset($data['invoice']) ? $data['invoice'] : 0,
                    'separatedocument' => isset($data['separatedocument']) ? 1 : 0,
                    'settlement' => !isset($data['settlement']) || $data['settlement'] != 1 ? 0 : 1,
                    SYSLOG::RES_NUMPLAN => !empty($data['numberplanid']) ? $data['numberplanid'] : null,
                    'paytype' => !empty($data['paytype']) ? $data['paytype'] : null,
                    'datefrom' => $data['datefrom'],
                    'dateto' => $data['dateto'],
                    'pdiscount' => str_replace(',', '.', $data['pdiscount']),
                    'vdiscount' => str_replace(',', '.', $data['vdiscount']),
                    'attribute' => !empty($data['attribute']) ? $data['attribute'] : null,
                    SYSLOG::RES_LIAB => !isset($lid) || empty($lid) ? null : $lid,
                    'recipient_address_id' => $data['recipient_address_id'] > 0 ? $data['recipient_address_id'] : null,
                    'docid' => empty($data['docid']) ? null : $data['docid'],
                    'commited' => $commited,
                );

                $result[] = $data['assignmentid'] = $this->insertAssignment($args);

                $this->insertNodeAssignments($data);
                $this->insertPhoneAssignments($data);
            }
        }

        return $result;
    }

    /*
     * Helper method who insert assignment.
     *
     * \param  array $args array with parameters for SQL query
     * \return int   last inserted id
     */
    private function insertAssignment($args)
    {
        $this->db->Execute(
            'INSERT INTO assignments
                (tariffid, customerid, period, backwardperiod, at, count, invoice, separatedocument,
                settlement, numberplanid,
                paytype, datefrom, dateto, pdiscount, vdiscount, attribute, liabilityid, recipient_address_id,
                docid, commited)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            array_values($args)
        );

        $id = $this->db->GetLastInsertID('assignments');

        if ($this->syslog) {
            $args[SYSLOG::RES_ASSIGN] = $id;
            $this->syslog->AddMessage(SYSLOG::RES_ASSIGN, SYSLOG::OPER_ADD, $args);
        }

        return $id;
    }

    private function insertNodeAssignments($args)
    {
        if (!empty($args['nodes'])) {
            // Use multi-value INSERT query
            $values = array();
            foreach ($args['nodes'] as $nodeid) {
                $values[] = sprintf('(%d, %d)', $nodeid, $args['assignmentid']);
            }

            $this->db->Execute('INSERT INTO nodeassignments (nodeid, assignmentid)
				VALUES ' . implode(', ', $values));
            if ($this->syslog) {
                $nodeassigns = $this->db->GetAll('SELECT id, nodeid FROM nodeassignments WHERE assignmentid = ?', array($args['assignmentid']));
                foreach ($nodeassigns as $nodeassign) {
                    $args = array(
                        SYSLOG::RES_NODEASSIGN => $nodeassign['id'],
                        SYSLOG::RES_CUST => $args['customerid'],
                        SYSLOG::RES_NODE => $nodeassign['nodeid'],
                        SYSLOG::RES_ASSIGN => $args['assignmentid'],
                    );
                    $this->syslog->AddMessage(SYSLOG::RES_NODEASSIGN, SYSLOG::OPER_ADD, $args);
                }
            }
        }
    }

    private function insertPhoneAssignments($args)
    {
        if (!empty($args['phones'])) {
            // Use multi-value INSERT query
            $values = array();
            foreach ($args['phones'] as $numberid) {
                $values[] = sprintf('(%d, %d)', $numberid, $args['assignmentid']);
            }

            $this->db->Execute('INSERT INTO voip_number_assignments (number_id, assignment_id)
				VALUES ' . implode(', ', $values));
        }
    }

    public function ValidateAssignment($data)
    {
        $error = null;
        $result = array();

        $a = $data;

        foreach ($a as $key => $val) {
            if (!is_array($val)) {
                $a[$key] = trim($val);
            }
        }

        $period = sprintf('%d', $a['period']);

        switch ($period) {
            case DAILY:
                $at = 0;
                break;

            case WEEKLY:
                $at = sprintf('%d', $a['at']);

                if (ConfigHelper::checkConfig('phpui.use_current_payday') && $at == 0) {
                    $at = strftime('%u', time());
                }

                if ($at < 1 || $at > 7) {
                    $error['at'] = trans('Incorrect day of week (1-7)!');
                }
                break;

            case MONTHLY:
                if ($a['at'] == '') {
                    if (ConfigHelper::checkConfig('phpui.use_current_payday')) {
                        $at = date('j', time());
                    } elseif (!ConfigHelper::checkConfig('phpui.use_current_payday')
                        && ConfigHelper::getConfig('phpui.default_monthly_payday') > 0) {
                        $at = ConfigHelper::getConfig('phpui.default_monthly_payday');
                    } else {
                        $at = -1;
                    }
                } else {
                    $at = intval($a['at']);
                }

                if ($at > 28 || $at < 0) {
                    $error['at'] = trans('Incorrect day of month (1-28)!');
                } else {
                    $a['at'] = $at;
                }
                break;

            case QUARTERLY:
                if (ConfigHelper::checkConfig('phpui.use_current_payday') && !$a['at']) {
                    $d = date('j', time());
                    $m = date('n', time());
                    $a['at'] = $d.'/'.$m;
                } elseif (!preg_match('/^[0-9]{2}\/[0-9]{2}$/', $a['at'])) {
                    $error['at'] = trans('Incorrect date format! Enter date in DD/MM format!');
                } else {
                    list($d,$m) = explode('/', $a['at']);
                }

                if (!$error) {
                    if ($d>30 || $d<1 || ($d>28 && $m==2)) {
                        $error['at'] = trans('This month doesn\'t contain specified number of days');
                    }

                    if ($m>3 || $m<1) {
                        $error['at'] = trans('Incorrect month number (max.3)!');
                    }

                    $at = ($m-1) * 100 + $d;
                }
                break;

            case HALFYEARLY:
                if (!preg_match('/^[0-9]{2}\/[0-9]{2}$/', $a['at']) && $a['at']) {
                    $error['at'] = trans('Incorrect date format! Enter date in DD/MM format!');
                } elseif (ConfigHelper::checkConfig('phpui.use_current_payday') && !$a['at']) {
                    $d = date('j', time());
                    $m = date('n', time());
                    $a['at'] = $d.'/'.$m;
                } else {
                    list($d,$m) = explode('/', $a['at']);
                }

                if (!$error) {
                    if ($d>30 || $d<1 || ($d>28 && $m==2)) {
                        $error['at'] = trans('This month doesn\'t contain specified number of days');
                    }

                    if ($m>6 || $m<1) {
                        $error['at'] = trans('Incorrect month number (max.6)!');
                    }

                    $at = ($m-1) * 100 + $d;
                }
                break;

            case YEARLY:
                if (ConfigHelper::checkConfig('phpui.use_current_payday') && !$a['at']) {
                    $d = date('j', time());
                    $m = date('n', time());
                    $a['at'] = $d.'/'.$m;
                } elseif (!preg_match('/^[0-9]{2}\/[0-9]{2}$/', $a['at'])) {
                    $error['at'] = trans('Incorrect date format! Enter date in DD/MM format!');
                } else {
                    list($d,$m) = explode('/', $a['at']);
                }

                if (!$error) {
                    if ($d>30 || $d<1 || ($d>28 && $m==2)) {
                        $error['at'] = trans('This month doesn\'t contain specified number of days');
                    }

                    if ($m>12 || $m<1) {
                        $error['at'] = trans('Incorrect month number');
                    }

                    $ttime = mktime(12, 0, 0, $m, $d, 1990);
                    $at = date('z', $ttime) + 1;
                }
                break;

            default: // DISPOSABLE
                $period = DISPOSABLE;

                $at = date_to_timestamp($a['at']);
                if (empty($at)) {
                    $error['at'] = trans('Incorrect date!');
                } elseif ($at < mktime(0, 0, 0)) {
                    $error['at'] = trans('Date could not be set in past!');
                }
                break;
        }

        if (isset($a['count'])) {
            if ($a['count'] == '') {
                $count = 1;
            } elseif (preg_match('/^[0-9]+(\.[0-9]+)?$/', $a['count'])) {
                $count = floatval($a['count']);
            } else {
                $error['count'] = trans('Incorrect count format! Numeric value required!');
            }
        }

        if (isset($a['datefrom'])) {
            if ($a['datefrom'] == '') {
                $from = 0;
            } elseif (preg_match('/^[0-9]{4}\/[0-9]{2}\/[0-9]{2}$/', $a['datefrom'])) {
                list($y, $m, $d) = explode('/', $a['datefrom']);

                if (checkdate($m, $d, $y)) {
                    $from = mktime(0, 0, 0, $m, $d, $y);
                } else {
                    $error['datefrom'] = trans('Incorrect date format! Enter date in YYYY/MM/DD format!');
                }
            } else {
                $error['datefrom'] = trans('Incorrect date format! Enter date in YYYY/MM/DD format!');
            }
        }

        if (isset($a['dateto'])) {
            if ($a['dateto'] == '') {
                $to = 0;
            } elseif (preg_match('/^[0-9]{4}\/[0-9]{2}\/[0-9]{2}$/', $a['dateto'])) {
                list($y, $m, $d) = explode('/', $a['dateto']);

                if (checkdate($m, $d, $y)) {
                    $to = mktime(23, 59, 59, $m, $d, $y);
                } else {
                    $error['dateto'] = trans('Incorrect date format! Enter date in YYYY/MM/DD format!');
                }
            } else {
                $error['dateto'] = trans('Incorrect date format! Enter date in YYYY/MM/DD format!');
            }
        }

        if (isset($from) && isset($to)) {
            if ($to < $from && $to != 0 && $from != 0) {
                $error['dateto'] = trans('Start date can\'t be greater than end date!');
            }
        }

        $a['discount'] = str_replace(',', '.', $a['discount']);
        $a['pdiscount'] = 0;
        $a['vdiscount'] = 0;
        if (preg_match('/^[0-9]+(\.[0-9]+)*$/', $a['discount'])) {
            $a['pdiscount'] = ($a['discount_type'] == DISCOUNT_PERCENTAGE ? floatval($a['discount']) : 0);
            $a['vdiscount'] = ($a['discount_type'] == DISCOUNT_AMOUNT ? floatval($a['discount']) : 0);
        }
        if ($a['pdiscount'] < 0 || $a['pdiscount'] > 99.99) {
            $error['discount'] = trans('Wrong discount value!');
        }

        if (intval($a['tariffid']) <= 0) {
            switch ($a['tariffid']) {
                // suspending
                case -1:
                    $a['tariffid']  = null;
                    $a['discount']  = 0;
                    $a['pdiscount'] = 0;
                    $a['vdiscount'] = 0;
                    $a['value']     = 0;

                    unset($a['schemaid'], $a['sassignmentid'], $a['invoice'], $a['settlement'], $error['at']);
                    $at = 0;
                    break;

                // promotion schema
                case -2:
                    $schemaid = isset($a['schemaid']) ? intval($a['schemaid']) : 0;
                    $a['promotionassignmentid'] = $a['sassignmentid'][$schemaid];

                    $values = $a['values'][$schemaid];
                    $counts = $a['counts'][$schemaid];
                    foreach ($a['promotionassignmentid'] as $label => $tariffid) {
                        if (empty($tariffid)) {
                            continue;
                        }
                        if (isset($values[$label][$tariffid])) {
                            foreach ($values[$label][$tariffid] as $period_idx => $value) {
                                if (!preg_match('/^[0-9]+(\.[0-9]{1,2})?$/', $value)) {
                                    $error['value-' . $schemaid . '-'
                                    . iconv('UTF-8', 'ASCII//TRANSLIT', preg_replace('/[ _]/', '-', $label))
                                    . '-' . $tariffid . '-' . $period_idx] = trans('Incorrect value!');
                                }
                            }
                        }
                        if (isset($counts[$label]) && !preg_match('/^[0-9]+$/', $counts[$label])) {
                            $error['counts-' . $schemaid . '-'
                            . iconv('UTF-8', 'ASCII//TRANSLIT', preg_replace('/[ _]/', '-', $label))] =
                                trans('Incorrect value!');
                        }
                    }

                    if (empty($from)) {
                        $from = mktime(0, 0, 0);
                    }

                    $a['value']     = 0;
                    $a['discount']  = 0;
                    $a['pdiscount'] = 0;
                    $a['vdiscount'] = 0;
                    // @TODO: handle other period/at values
                    $a['period'] = MONTHLY; // dont know why, remove if you are sure
                    $a['at'] = 1;
                    break;

                // tariffless
                default:
                    if (!$a['name']) {
                        $error['name'] = trans('Liability name is required!');
                    }

                    if (!$a['value']) {
                        $error['value'] = trans('Liability value is required!');
                    } elseif (!preg_match('/^[-]?[0-9.,]+$/', $a['value'])) {
                        $error['value'] = trans('Incorrect value!');
                    } elseif ($a['discount_type'] == 2 && $a['discount'] && $a['value'] - $a['discount'] < 0) {
                        $error['value'] = trans('Value less than discount are not allowed!');
                        $error['discount'] = trans('Value less than discount are not allowed!');
                    }

                    if (ConfigHelper::checkConfig('phpui.tax_category_required')
                        && empty($a['taxcategory'])) {
                        $error['taxcategory'] = trans('Tax category selection is required!');
                    }

                    unset($a['schemaid'], $a['sassignmentid']);
            }
        } else {
            if ($a['discount_type'] == 2 && $a['discount']
                && $this->db->GetOne('SELECT value FROM tariffs WHERE id = ?', array($a['tariffid'])) - $a['discount'] < 0) {
                $error['value'] = trans('Value less than discount are not allowed!');
                $error['discount'] = trans('Value less than discount are not allowed!');
            }

            unset($a['schemaid'], $a['sassignmentid']);
        }

        if (isset($error['dateto'])) {
            $error['todate'] = $error['dateto'];
        }
        if (isset($error['datefrom'])) {
            $error['fromdate'] = $error['datefrom'];
        }

        if (!isset($GLOBALS['CURRENCIES'][$a['currency']])) {
            $error['currency'] = trans('Invalid currency selection!');
        }

        $result['error'] = $error;

        $result['a'] = $a;
        $result = array_merge($result, compact('period', 'at', 'from', 'to', 'schemaid', 'count'));

        return $result;
    }

    public function CheckSchemaModifiedValues($data)
    {
        if (ConfigHelper::checkConfig('phpui.promotion_allow_modify_values_for_privileged_user')
            && ConfigHelper::checkPrivilege('promotion_management')) {
            return true;
        }

        $schemaid = $data['schemaid'];
        $sassignments = $data['sassignmentid'][$schemaid];
        $values = $data['values'][$schemaid];

        if (is_array($values)) {
            foreach ($values as $label => &$assignments) {
                if (!isset($sassignments[$label]) || empty($sassignments[$label])) {
                    unset($values[$label]);
                    continue;
                }
                foreach ($assignments as $assignmentid => &$periods) {
                    if (!in_array($assignmentid, $sassignments)) {
                        unset($values[$label][$assignmentid]);
                        continue;
                    }
                }
                unset($periods);
            }
            unset($assignments);

            $userid = Auth::GetCurrentUser();

            foreach ($values as $assignments) {
                foreach ($assignments as $assignmentid => $periods) {
                    $a_data = $this->db->GetOne(
                        'SELECT data
                        FROM promotionassignments
                        WHERE id = ?',
                        array($assignmentid)
                    );
                    $a_periods = explode(';', $a_data);
                    $allowed_period_indexes = array();
                    foreach ($a_periods as $a_period_idx => $a_period) {
                        $props = explode(':', $a_period);
                        if (count($props) < 3) {
                            continue;
                        }
                        $users = explode(',', $props[2]);
                        if (in_array($userid, $users)) {
                            $allowed_period_indexes[] = $a_period_idx;
                        }
                    }
                    if (array_diff(array_keys($periods), $allowed_period_indexes)) {
                        return false;
                    }
                }
            }
        }

        return true;
    }

    public function UpdateExistingAssignments($data)
    {
        $refid = isset($data['reference']) && isset($data['existing_assignments']['reference_document_limit'])
            ? $data['reference'] : null;
        $assignment_type = isset($data['existing_assignments']['assignment_type_limit'])
            ? $data['existing_assignments']['assignment_type_limit'] : null;

        switch ($data['existing_assignments']['operation']) {
            case EXISTINGASSIGNMENT_DELETE:
            case EXISTINGASSIGNMENT_SUSPEND:
                $args = array(
                    'customerid' => $data['customerid'],
                );
                if ($assignment_type) {
                    $args['tarifftype'] = $assignment_type;
                }
                if ($refid) {
                    $args['refid'] = $refid;
                }
                $aids = $this->db->GetCol(
                    'SELECT a.id FROM assignments a'
                    . (isset($assignment_type) ? ' JOIN tariffs t ON t.id = a.tariffid' : '')
                    . ' WHERE commited = 1 AND customerid = ?'
                    . (isset($refid) ? ' AND docid = ?' : '')
                    . (isset($assignment_type) ? ' AND t.type = ?' : ''),
                    array_values($args)
                );
                if (!empty($aids)) {
                    foreach ($aids as $aid) {
                        if ($data['existing_assignments']['operation'] == EXISTINGASSIGNMENT_DELETE) {
                            $this->DeleteAssignment($aid);
                        } else {
                            $this->SuspendAssignment($aid);
                        }
                    }
                }
                break;
            case EXISTINGASSIGNMENT_CUT:
                $args = array(
                    'customerid' => $data['customerid'],
                );
                if ($assignment_type) {
                    $args['tarifftype'] = $assignment_type;
                }
                if ($refid) {
                    $args['refid'] = $refid;
                }
                if (empty($data['datefrom'])) {
                    list ($year, $month, $day) = explode('/', date('Y/m/d'));
                    $args['datefrom'] = mktime(0, 0, 0, $month, $day, $year);
                } else {
                    $args['datefrom'] = $data['datefrom'];
                }
                $args['at'] = $args['datefrom'];

                // delete assignments which start in future
                $aids = $this->db->GetCol(
                    'SELECT a.id FROM assignments a'
                    . (isset($assignment_type) ? ' JOIN tariffs t ON t.id = a.tariffid' : '')
                    . ' WHERE commited = 1 AND customerid = ?'
                    . (isset($assignment_type) ? ' AND t.type = ?' : '')
                    . (isset($refid) ? ' AND docid = ?' : '') . ' AND (a.datefrom >= ? OR at >= ?)',
                    array_values($args)
                );
                if (!empty($aids)) {
                    foreach ($aids as $aid) {
                        $this->DeleteAssignment($aid);
                    }
                }
                unset($args['at']);
                $args['dateto'] = $args['datefrom'];
                $args['at'] = $args['datefrom'];

                // cut assignment period end date to datefrom
                $aids = $this->db->GetCol(
                    'SELECT a.id FROM assignments a'
                    . (isset($assignment_type) ? ' JOIN tariffs t ON t.id = a.tariffid' : '')
                    . ' WHERE commited = 1 AND customerid = ?'
                    . (isset($assignment_type) ? ' AND t.type = ?' : '')
                    . (isset($refid) ? ' AND docid = ?' : '') . ' AND a.datefrom <= ? AND (a.dateto = 0 OR a.dateto > ?) AND at < ?',
                    array_values($args)
                );
                if (!empty($aids)) {
                    foreach ($aids as $aid) {
                        $this->db->Execute(
                            'UPDATE assignments SET dateto = ? WHERE id = ?',
                            array($args['datefrom'] - 1, $aid)
                        );
                    }
                }

                break;
        }
    }

    public function SuspendAssignment($id, $suspend = true)
    {
        if ($this->syslog) {
            $assign = $this->db->GetRow('SELECT id, tariffid, liabilityid, customerid FROM assignments WHERE id = ?', array($id));
            $args = array(
                SYSLOG::RES_ASSIGN => $assign['id'],
                SYSLOG::RES_TARIFF => $assign['tariffid'],
                SYSLOG::RES_LIAB => $assign['liabilityid'],
                SYSLOG::RES_CUST => $assign['customerid'],
                'suspend' => ($suspend ? 1 : 0)
            );
            $this->syslog->AddMessage(SYSLOG::RES_ASSIGN, SYSLOG::OPER_UPDATE, $args);
        }
        return $this->db->Execute('UPDATE assignments SET suspended=? WHERE id=?', array($suspend ? 1 : 0, $id));
    }

    public function GetTradeDocumentArchiveStats($ids)
    {
        $archive_stats = $this->db->GetRow(
            'SELECT SUM(CASE WHEN d.archived = 1 THEN 1 ELSE 0 END) AS archive,
			SUM(CASE WHEN d.archived = 0 THEN 1 ELSE 0 END) AS current,
			SUM(CASE WHEN a.contenttype = ? THEN 1 ELSE 0 END) AS html,
			SUM(CASE WHEN a.contenttype = ? THEN 1 ELSE 0 END) AS pdf
		FROM documents d
		LEFT JOIN documentattachments a ON a.docid = d.id AND a.type = ?
		WHERE d.id IN (' . implode(',', $ids) . ')',
            array('text/html', 'application/pdf', 1)
        );

        $archive_stats['rtype'] = null;
        if (!$archive_stats['current']) {
            if ($archive_stats['html'] > 0 && !$archive_stats['pdf']) {
                $archive_stats['rtype'] = 'html';
            } elseif ($archive_stats['pdf'] > 0 && !$archive_stats['html']) {
                $archive_stats['rtype'] = 'pdf';
            }
        }

        return $archive_stats;
    }

    public function DeleteArchiveTradeDocument($id)
    {
        $md5sum = $this->db->GetOne('SELECT md5sum FROM documentattachments WHERE docid = ?', array($id));
        if (!empty($md5sum)) {
            $document_manager = new LMSDocumentManager($this->db, $this->auth, $this->cache, $this->syslog);
            $file_manager = new LMSFileManager($this->db, $this->auth, $this->cache, $this->syslog);
            if ($document_manager->DocumentAttachmentExists($md5sum) <= 1
                && !$file_manager->FileExists($md5sum)) {
                @unlink(DOC_DIR . DIRECTORY_SEPARATOR . substr($md5sum, 0, 2)
                    . DIRECTORY_SEPARATOR . $md5sum);
            }
        }

        $this->db->Execute('DELETE FROM documentattachments WHERE docid = ?', array($id));
        $this->db->Execute('UPDATE documents SET archived = 0, adate = 0, auserid = NULL
			WHERE id = ?', array($id));
    }

    public function ArchiveTradeDocument($id)
    {
        $doc = $this->db->GetRow('SELECT d.id, d.number, d.cdate, d.customerid, d.type AS doctype, n.template
			FROM documents d
			LEFT JOIN numberplans n ON n.id = d.numberplanid 
			WHERE d.id = ?', array($id));
        if (empty($doc)) {
            return null;
        }

        $doc['filename'] = ($doc['doctype'] == DOC_DNOTE
            ? ConfigHelper::getConfig('notes.attachment_name', 'dnote_%docid')
            : ConfigHelper::getConfig('invoices.attachment_name', 'invoice_%docid'));

        $file = $this->GetTradeDocument($doc);

        $document_manager = new LMSDocumentManager($this->db, $this->auth, $this->cache, $this->syslog);
        $error = $document_manager->AddArchiveDocument($id, $file);

        if (empty($error)) {
            $this->db->Execute('UPDATE documents SET archived = ?, adate = ?NOW?, auserid = ?
				WHERE id = ?', array(1, Auth::GetCurrentUser(), $id));
        }

        $result = array(
            'ok' => empty($error),
        );
        if (empty($error)) {
            $result['filename'] = $file['filename'];
        } else {
            $result['error'] = $error;
        }

        return $result;
    }

    public function GetTradeDocument($doc)
    {
        global $DOCENTITIES;

        if (isset($doc['archived']) && !empty($doc['archived'])) {
            $document_manager = new LMSDocumentManager($this->db, $this->auth, $this->cache, $this->syslog);
            return $document_manager->GetArchiveDocument($doc['id']);
        }

        $smarty = LMSSmarty::getInstance();

        if ($doc['doctype'] == DOC_DNOTE) {
            $type = ConfigHelper::getConfig('notes.type', '');

            if ($type == 'pdf') {
                $content_type = 'application/pdf';
                $document = new LMSTcpdfDebitNote(trans('Notes'));
            } else {
                $content_type = 'text/html';
                $document = new LMSHtmlDebitNote($smarty);
            }

            $filename = isset($doc['filename']) ? $doc['filename'] : $doc['dnote_filename'];

            $data = $this->GetNoteContent($doc['id']);
        } else {
            $type = ConfigHelper::getConfig('invoices.type', '');
            if ($type == 'pdf') {
                $pdf_type = ConfigHelper::getConfig('invoices.pdf_type', 'tcpdf');
                $pdf_type = ucwords($pdf_type);
                $classname = 'LMS' . $pdf_type . 'Invoice';
                $content_type = 'application/pdf';
                $document = new $classname(trans('Invoices'));
            } else {
                $content_type = 'text/html';
                $document = new LMSHtmlInvoice($smarty);
            }

            $filename = isset($doc['filename']) ? $doc['filename'] : $doc['invoice_filename'];

            $data = $this->GetInvoiceContent($doc['id']);
        }

        if ($type == 'pdf') {
            $fext = 'pdf';
        } else {
            $fext = 'html';
        }

        $document_number = (!empty($doc['template']) ? $doc['template'] : '%N/LMS/%Y');
        $document_number = docnumber(array(
            'number' => $doc['number'],
            'template' => $document_number,
            'cdate' => $doc['cdate'] + date('Z'),
            'customerid' => $doc['customerid'],
        ));

        $filename = preg_replace('/%docid/', $doc['id'], $filename);
        $filename = str_replace('%number', $document_number, $filename);
        $filename = preg_replace('/[^[:alnum:]_\.]/i', '_', $filename);

        if (!isset($doc['which']) || !$doc['which']) {
            $which = DOC_ENTITY_ORIGINAL;
        } else {
            $which = $doc['which'];
        }

        $idx = 0;
        if (isset($data['lang'])) {
            Localisation::setUiLanguage($data['lang']);
        }

        $count = Utils::docEntityCount($which);
        foreach (array_keys($DOCENTITIES) as $type) {
            if ($which & $type) {
                $data['type'] = $type;
                $data['duplicate-date'] = $doc['duplicate-date'];
                $document->Draw($data);
                $idx++;
                if ($idx < $count) {
                    $document->NewPage();
                }
            }
        }
        if (isset($data['lang'])) {
            Localisation::resetUiLanguage();
        }

        return array(
            'filename' => $filename . '.' . $fext,
            'data' => $document->WriteToString(),
            'document' => $data,
            'content-type' => $content_type,
        );
    }

    public function GetInvoiceList(array $params)
    {
        extract($params);
        foreach (array('search', 'cat', 'group', 'numberplan', 'division', 'exclude', 'hideclosed', 'page') as $var) {
            if (!isset($$var)) {
                $$var = null;
            }
        }
        if (!isset($order)) {
            $order = '';
        }
        if (!isset($proforma)) {
            $proforma = 0;
        }
        if (!isset($count)) {
            $count = false;
        }

        if ($order=='') {
            $order='id,asc';
        }

        list($order,$direction) = sscanf($order, '%[^,],%s');
        ($direction=='desc') ? $direction = 'desc' : $direction = 'asc';

        switch ($order) {
            case 'id':
                $sqlord = ' ORDER BY d.id';
                break;
            case 'cdate':
                $sqlord = ' ORDER BY d.cdate';
                break;
            case 'number':
                $sqlord = ' ORDER BY d.number';
                break;
            case 'value':
                $sqlord = ' ORDER BY value';
                break;
            case 'count':
                $sqlord = ' ORDER BY count';
                break;
            case 'name':
                $sqlord = ' ORDER BY d.name';
                break;
        }

        $join_cash = false;

        $where = '';

        if ($search!='' && $cat) {
            switch ($cat) {
                case 'number':
                    $where = ' AND d.number = '.intval($search);
                    break;
                case 'cdate':
                    $where = ' AND d.cdate >= '.intval($search).' AND d.cdate < '.(intval($search)+86400);
                    break;
                case 'month':
                    $last = mktime(23, 59, 59, date('n', $search) + 1, 0, date('Y', $search));
                    $where = ' AND d.cdate >= '.intval($search).' AND d.cdate <= '.$last;
                    break;
                case 'ten':
                    $where = ' AND d.ten = ' . $this->db->Escape($search);
                    break;
                case 'customerid':
                    $where = ' AND d.customerid = '.intval($search);
                    break;
                case 'name':
                    $where = ' AND UPPER(d.name) ?LIKE? UPPER(' . $this->db->Escape('%' . $search . '%') . ')';
                    break;
                case 'address':
                    $where = ' AND UPPER(d.address) ?LIKE? UPPER(' . $this->db->Escape('%' . $search . '%') . ')';
                    break;
                case 'value':
                    $having = ' HAVING -SUM(cash.value) = '.str_replace(',', '.', f_round($search)).' ';
                    $join_cash = true;
                    break;
            }
        }

        if ($hideclosed) {
            $where .= ' AND d.closed = 0';
        }

        if (!empty($group)) {
            $group = Utils::filterIntegers($group);
        }

        if (!empty($numberplan)) {
            $numberplan = Utils::filterIntegers($numberplan);
        }

        if ($count) {
            return $this->db->GetOne('SELECT COUNT(DISTINCT id) FROM (SELECT d.id
                FROM documents d'
                . ($join_cash ?
                    'JOIN invoicecontents a ON (a.docid = d.id)
                    LEFT JOIN cash ON cash.docid = d.id AND cash.itemid = a.itemid'
                    : '') . '
                LEFT JOIN (
                    SELECT DISTINCT a.customerid FROM customerassignments a
                    JOIN excludedgroups e ON (a.customergroupid = e.customergroupid)
                    WHERE e.userid = lms_current_user()
                ) e ON (e.customerid = d.customerid)
                WHERE e.customerid IS NULL AND '
                . ($proforma ? 'd.type = ' . DOC_INVOICE_PRO
                    : '(d.type = '.DOC_CNOTE.(($cat != 'cnotes') ? ' OR d.type = '.DOC_INVOICE : '').')')
                .$where
                .(!empty($group) ?
                    ' AND '.(!empty($exclude) ? 'NOT' : '').' EXISTS (
				SELECT 1 FROM customerassignments WHERE customergroupid IN (' . implode(',', $group) . ')
					AND customerid = d.customerid)' : '')
                . (!empty($numberplan) ? ' AND d.numberplanid IN (' . implode(',', $numberplan) . ')' : '')
                . (!empty($division) ? ' AND d.divisionid = ' . intval($division) : '')
                . (isset($having) ? $having : '') . ') a');
        }

        $invoicelist = $this->db->GetAll('SELECT d.id AS id, d.number, d.cdate, d.type,
			d.customerid, d.name, d.address, d.zip, d.city, countries.name AS country, numberplans.template, d.closed,
			d.cancelled, d.published, d.archived, d.senddate,
			(CASE WHEN d.type = ' . DOC_INVOICE_PRO . '
			    THEN
			        SUM(a.value * a.count)
			    ELSE
			        -SUM(cash.value)
			END) AS value,
			d.currency, d.currencyvalue,
			COUNT(a.docid) AS count,
			i.sendinvoices,
			(CASE WHEN d2.id IS NULL THEN 0 ELSE 1 END) AS referenced
			FROM documents d
			JOIN invoicecontents a ON (a.docid = d.id)
			LEFT JOIN cash ON cash.docid = d.id AND a.itemid = cash.itemid
			LEFT JOIN documents d2 ON d2.reference = d.id
			LEFT JOIN invoicecontents b ON (d.reference = b.docid AND a.itemid = b.itemid)
			LEFT JOIN countries ON (countries.id = d.countryid)
			LEFT JOIN numberplans ON (d.numberplanid = numberplans.id)
			LEFT JOIN (
			SELECT DISTINCT c.id AS customerid, 1 AS sendinvoices FROM customeraddressview c
				JOIN customercontacts cc ON cc.customerid = c.id
				WHERE invoicenotice = 1 AND cc.type & ' . (CONTACT_INVOICES | CONTACT_DISABLED) . ' = ' . CONTACT_INVOICES . '
			) i ON i.customerid = d.customerid
			LEFT JOIN (
			SELECT DISTINCT a.customerid FROM customerassignments a
				JOIN excludedgroups e ON (a.customergroupid = e.customergroupid)
				WHERE e.userid = lms_current_user()
				) e ON (e.customerid = d.customerid)
			WHERE e.customerid IS NULL AND '
            . ($proforma ? 'd.type = ' . DOC_INVOICE_PRO
                : '(d.type = '.DOC_CNOTE.(($cat != 'cnotes') ? ' OR d.type = '.DOC_INVOICE : '').')')
            .$where
            .(!empty($group) ?
                ' AND '.(!empty($exclude) ? 'NOT' : '').' EXISTS (
			SELECT 1 FROM customerassignments WHERE customergroupid IN (' . implode(',', $group) . ')
						AND customerid = d.customerid)' : '')
            . (!empty($numberplan) ? ' AND d.numberplanid IN (' . implode(',', $numberplan) . ')' : '')
            . (!empty($division) ? ' AND d.divisionid = ' . intval($division) : '')
            .' GROUP BY d.id, d2.id, d.number, d.cdate, d.customerid,
			d.name, d.address, d.zip, d.city, numberplans.template, d.closed, d.type, d.reference, countries.name,
			d.cancelled, d.published, sendinvoices, d.archived, d.currency, d.currencyvalue '
            . (isset($having) ? $having : '')
            .$sqlord.' '.$direction
            . (isset($limit) ? ' LIMIT ' . $limit : '')
            . (isset($offset) ? ' OFFSET ' . $offset : ''));

        $invoicelist['order'] = $order;
        $invoicelist['direction'] = $direction;

        return $invoicelist;
    }

    public function AddInvoice($invoice)
    {
        $currtime = time();
        $cdate = $invoice['invoice']['cdate'] ? $invoice['invoice']['cdate'] : $currtime;
        $sdate = $invoice['invoice']['sdate'] ? $invoice['invoice']['sdate'] : $currtime;
        $number = $invoice['invoice']['number'];
        $type = $invoice['invoice']['type'];
        if ($invoice['invoice']['numberplanid']) {
            $fullnumber = docnumber(array(
                'number' => $number,
                'template' => $this->db->GetOne('SELECT template FROM numberplans WHERE id = ?', array($invoice['invoice']['numberplanid'])),
                'cdate' => $cdate,
                'customerid' => $invoice['customer']['id'] ?: null,
            ));
        } else {
            $fullnumber = null;
        }

        $division_manager = new LMSDivisionManager($this->db, $this->auth, $this->cache, $this->syslog);
        $division = $division_manager->GetDivision($invoice['customer']['divisionid']);

        $location_manager = new LMSLocationManager($this->db, $this->auth, $this->cache, $this->syslog);

        if ($invoice['invoice']['recipient_address_id'] > 0) {
            $invoice['invoice']['recipient_address_id'] = $location_manager->CopyAddress($invoice['invoice']['recipient_address_id']);
        } else {
            $invoice['invoice']['recipient_address_id'] = null;
        }

        $post_address_id = $location_manager->GetCustomerAddress($invoice['customer']['id'], POSTAL_ADDRESS);

        if (empty($post_address_id)) {
            $invoice['invoice']['post_address_id'] = null;
        } else {
            $invoice['invoice']['post_address_id'] = $location_manager->CopyAddress($post_address_id);
        }

        $doc_comment = $invoice['invoice']['comment'];
        if (isset($invoice['invoice']['proformanumber'])) {
            $comment = ConfigHelper::getConfig('invoices.proforma_conversion_comment_format', '%comment');
            $comment = str_replace(
                array(
                    '%comment',
                    '%number',
                ),
                array(
                    $doc_comment,
                    $invoice['invoice']['proformanumber'],
                ),
                $comment
            );
        } else {
            $comment = $doc_comment;
        }

        $args = array(
            'number' => $number,
            SYSLOG::RES_NUMPLAN => $invoice['invoice']['numberplanid'] ? $invoice['invoice']['numberplanid'] : null,
            'type' => $type,
            'cdate' => $cdate,
            'sdate' => $sdate,
            'paytime' => $invoice['invoice']['paytime'],
            'paytype' => $invoice['invoice']['paytype'],
            'splitpayment' => empty($invoice['invoice']['splitpayment']) ? 0 : 1,
            SYSLOG::RES_USER => Auth::GetCurrentUser(),
            SYSLOG::RES_CUST => $invoice['customer']['id'],
            'customername' => $invoice['customer']['customername'],
            'address' => ($invoice['customer']['postoffice'] && $invoice['customer']['postoffice'] != $invoice['customer']['city'] && $invoice['customer']['street']
                ? $invoice['customer']['city'] . ', ' : '') . $invoice['customer']['address'],
            'ten' => $invoice['customer']['ten'],
            'ssn' => $invoice['customer']['ssn'],
            'zip' => $invoice['customer']['zip'],
            'city' => $invoice['customer']['postoffice'] ? $invoice['customer']['postoffice'] : $invoice['customer']['city'],
            SYSLOG::RES_COUNTRY => $invoice['customer']['countryid'] ? $invoice['customer']['countryid'] : null,
            SYSLOG::RES_DIV => $invoice['customer']['divisionid'],
            'div_name' => ($division['name'] ? $division['name'] : ''),
            'div_shortname' => ($division['shortname'] ? $division['shortname'] : ''),
            'div_address' => ($division['address'] ? $division['address'] : ''),
            'div_city' => ($division['city'] ? $division['city'] : ''),
            'div_zip' => ($division['zip'] ? $division['zip'] : ''),
            'div_' . SYSLOG::getResourceKey(SYSLOG::RES_COUNTRY) => ($division['countryid'] ? $division['countryid'] : null),
            'div_ten' => ($division['ten'] ? $division['ten'] : ''),
            'div_regon' => ($division['regon'] ? $division['regon'] : ''),
            'div_bank' => $division['bank'] ?: null,
            'div_account' => ($division['account'] ? $division['account'] : ''),
            'div_inv_header' => ($division['inv_header'] ? $division['inv_header'] : ''),
            'div_inv_footer' => ($division['inv_footer'] ? $division['inv_footer'] : ''),
            'div_inv_author' => ($division['inv_author'] ? $division['inv_author'] : ''),
            'div_inv_cplace' => ($division['inv_cplace'] ? $division['inv_cplace'] : ''),
            'fullnumber' => $fullnumber,
            'comment' => $comment,
            'recipient_address_id' => $invoice['invoice']['recipient_address_id'],
            'post_address_id' => $invoice['invoice']['post_address_id'],
            'currency' => isset($invoice['invoice']['currency']) ? $invoice['invoice']['currency'] : Localisation::getCurrentCurrency(),
            'currencyvalue' => isset($invoice['invoice']['currencyvalue']) ? $invoice['invoice']['currencyvalue'] : 1.0,
            'memo' => isset($invoice['customer']['documentmemo']) && !empty($invoice['customer']['documentmemo'])
                ? $invoice['customer']['documentmemo'] : null,
            'reference' => isset($invoice['invoice']['proformaid']) && !empty($invoice['invoice']['proformaid']) ? $invoice['invoice']['proformaid'] : null,
        );

        $this->db->Execute('INSERT INTO documents (number, numberplanid, type,
			cdate, sdate, paytime, paytype, splitpayment, userid, customerid, name, address,
			ten, ssn, zip, city, countryid, divisionid,
			div_name, div_shortname, div_address, div_city, div_zip, div_countryid, div_ten, div_regon,
			div_bank, div_account, div_inv_header, div_inv_footer, div_inv_author, div_inv_cplace, fullnumber,
			comment, recipient_address_id, post_address_id, currency, currencyvalue, memo, reference)
			VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', array_values($args));
        $iid = $this->db->GetLastInsertID('documents');
        if ($this->syslog) {
            unset($args[SYSLOG::RES_USER]);
            $args[SYSLOG::RES_DOC] = $iid;
            $this->syslog->AddMessage(
                SYSLOG::RES_DOC,
                SYSLOG::OPER_ADD,
                $args,
                array('div_' . SYSLOG::getResourceKey(SYSLOG::RES_COUNTRY))
            );
        }

        $itemid = 0;
        foreach ($invoice['contents'] as $idx => $item) {
            $itemid++;
            $item['valuebrutto'] = str_replace(',', '.', $item['valuebrutto']);
            $item['count'] = str_replace(',', '.', $item['count']);
            $item['discount'] = str_replace(',', '.', $item['discount']);
            $item['pdiscount'] = str_replace(',', '.', $item['pdiscount']);
            $item['vdiscount'] = str_replace(',', '.', $item['vdiscount']);
            $item['taxid'] = isset($item['taxid']) ? $item['taxid'] : null;

            $args = array(
                SYSLOG::RES_DOC => $iid,
                'itemid' => $itemid,
                'value' => $item['valuebrutto'],
                SYSLOG::RES_TAX => $item['taxid'],
                'taxcategory' => isset($item['taxcategory']) && !empty($item['taxcategory']) ? $item['taxcategory'] : 0,
                'prodid' => $item['prodid'],
                'content' => $item['jm'],
                'count' => $item['count'],
                'pdiscount' => $item['pdiscount'],
                'vdiscount' => $item['vdiscount'],
                'description' => $item['name'],
                SYSLOG::RES_TARIFF => empty($item['tariffid']) ? null : $item['tariffid'],
            );
            $this->db->Execute('INSERT INTO invoicecontents (docid, itemid,
				value, taxid, taxcategory, prodid, content, count, pdiscount, vdiscount, description, tariffid)
				VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', array_values($args));
            if ($this->syslog) {
                $args[SYSLOG::RES_CUST] = $invoice['customer']['id'];
                $this->syslog->AddMessage(SYSLOG::RES_INVOICECONT, SYSLOG::OPER_ADD, $args);
            }

            if ($type != DOC_INVOICE_PRO || ConfigHelper::checkConfig('phpui.proforma_invoice_generates_commitment')) {
                $this->AddBalance(array(
                    'time' => $cdate,
                    'value' => $item['valuebrutto'] * $item['count'] * -1,
                    'currency' => $invoice['invoice']['currency'],
                    'currencyvalue' => $invoice['invoice']['currencyvalue'],
                    'taxid' => $item['taxid'],
                    'customerid' => $invoice['customer']['id'],
                    'comment' => $item['name'],
                    'docid' => $iid,
                    'itemid' => $itemid
                ));
            }
        }

        return $iid;
    }

    public function InvoiceDelete($invoiceid)
    {
        if ($this->syslog) {
            $customerid = $this->db->GetOne('SELECT customerid FROM documents WHERE id = ?', array($invoiceid));
            $args = array(
                SYSLOG::RES_DOC => $invoiceid,
                SYSLOG::RES_CUST => $customerid,
            );
            $this->syslog->AddMessage(SYSLOG::RES_DOC, SYSLOG::OPER_DELETE, $args);
            $cashids = $this->db->GetCol('SELECT id FROM cash WHERE docid = ?', array($invoiceid));
            if (!empty($cashids)) {
                foreach ($cashids as $cashid) {
                    $args = array(
                        SYSLOG::RES_CASH => $cashid,
                        SYSLOG::RES_DOC => $invoiceid,
                        SYSLOG::RES_CUST => $customerid,
                    );
                    $this->syslog->AddMessage(SYSLOG::RES_CASH, SYSLOG::OPER_DELETE, $args);
                }
            }
            $itemids = $this->db->GetCol('SELECT itemid FROM invoicecontents WHERE docid = ?', array($invoiceid));
            foreach ($itemids as $itemid) {
                $args = array(
                    SYSLOG::RES_DOC => $invoiceid,
                    SYSLOG::RES_CUST => $customerid,
                    'itemid' => $itemid,
                );
                $this->syslog->AddMessage(SYSLOG::RES_INVOICECONT, SYSLOG::OPER_DELETE, $args);
            }
        }

        $document_manager = new LMSDocumentManager($this->db, $this->auth, $this->cache, $this->syslog);
        $document_manager->DeleteDocumentAddresses($invoiceid);

        $this->db->Execute('DELETE FROM documents WHERE id = ?', array($invoiceid));
    }

    public function InvoiceContentDelete($invoiceid, $itemid = 0)
    {
        if ($itemid) {
            if ($this->syslog) {
                $customerid = $this->db->GetOne('SELECT customerid FROM documents
					JOIN invoicecontents ON docid = id WHERE id = ?', array($invoiceid));
                $args = array(
                    SYSLOG::RES_DOC => $invoiceid,
                    SYSLOG::RES_CUST => $customerid,
                    'itemid' => $itemid,
                );
                $this->syslog->AddMessage(SYSLOG::RES_INVOICECONT, SYSLOG::OPER_DELETE, $args);
            }
            $this->db->Execute('DELETE FROM invoicecontents WHERE docid=? AND itemid=?', array($invoiceid, $itemid));

            if (!$this->db->GetOne('SELECT COUNT(*) FROM invoicecontents WHERE docid=?', array($invoiceid))) {
                // if that was the last item of invoice contents
                $this->db->Execute('DELETE FROM documents WHERE id = ?', array($invoiceid));
                if ($this->syslog) {
                    $args = array(
                        SYSLOG::RES_DOC => $invoiceid,
                        SYSLOG::RES_CUST => $customerid,
                    );
                    $this->syslog->AddMessage(SYSLOG::RES_DOC, SYSLOG::OPER_DELETE, $args);
                }
            }
        } else {
            $this->InvoiceDelete($invoiceid);
        }
    }

    public function GetInvoiceContent($invoiceid, $detail_level = LMSFinanceManager::INVOICE_CONTENT_DETAIL_ALL)
    {
        global $PAYTYPES;

        if ($detail_level <= self::INVOICE_CONTENT_DETAIL_MORE) {
            $result = $this->db->GetRow(
                'SELECT d.id, d.type AS doctype, d.number, d.fullnumber, d.name, d.customerid,
				d.userid, d.address, d.zip, d.city, d.countryid,
				d.ten, d.ssn, d.cdate, d.sdate, d.paytime, d.paytype, d.splitpayment, d.numberplanid,
				d.closed, d.cancelled, d.published, d.archived, d.comment AS comment, d.reference, d.reason, d.divisionid,
				n.template,
				d.div_name AS division_name, d.div_shortname AS division_shortname,
				d.div_address AS division_address, d.div_zip AS division_zip,
				d.div_city AS division_city, d.div_countryid AS division_countryid,
				d.div_ten AS division_ten, d.div_regon AS division_regon, d.div_bank AS div_bank, d.div_account AS account,
				d.div_inv_header AS division_header, d.div_inv_footer AS division_footer,
				d.div_inv_author AS division_author, d.div_inv_cplace AS division_cplace,
				d.recipient_address_id, d.post_address_id,
				d.currency, d.currencyvalue, d.memo
				FROM documents d
				LEFT JOIN numberplans n ON (d.numberplanid = n.id)
				WHERE d.id = ? AND (d.type = ? OR d.type = ? OR d.type = ?)',
                array($invoiceid, DOC_INVOICE, DOC_CNOTE, DOC_INVOICE_PRO)
            );
        } else {
            $result = $this->db->GetRow(
                'SELECT d.id, d.type AS doctype, d.number, d.fullnumber, d.name, d.customerid,
				d.userid, d.address, d.zip, d.city, d.countryid, cn.name AS country,
				d.ten, d.ssn, d.cdate, d.sdate, d.paytime, d.paytype, d.splitpayment, d.numberplanid,
				d.closed, d.cancelled, d.published, d.archived, d.comment AS comment, d.reference, d.reason, d.divisionid,
				(SELECT name FROM vusers WHERE id = d.userid) AS user, n.template,
				d.div_name AS division_name, d.div_shortname AS division_shortname,
				d.div_address AS division_address, d.div_zip AS division_zip,
				d.div_city AS division_city, d.div_countryid AS division_countryid,
				d.div_ten AS division_ten, d.div_regon AS division_regon, d.div_bank AS div_bank, d.div_account AS account,
				d.div_inv_header AS division_header, d.div_inv_footer AS division_footer,
				d.div_inv_author AS division_author, d.div_inv_cplace AS division_cplace,
				d.recipient_address_id, d.post_address_id,
				a.state AS rec_state, a.state_id AS rec_state_id,
				a.city as rec_city, a.city_id AS rec_city_id,
				a.street AS rec_street, a.street_id AS rec_street_id,
				a.zip as rec_zip, a.postoffice AS rec_postoffice,
				a.name as rec_name, a.address AS rec_address,
				a.house AS rec_house, a.flat AS rec_flat, a.country_id AS rec_country_id,
				c.pin AS customerpin, c.divisionid AS current_divisionid,
				c.street, c.building, c.apartment,
				(CASE WHEN d.post_address_id IS NULL THEN c.post_street ELSE a2.street END) AS post_street,
				(CASE WHEN d.post_address_id IS NULL THEN c.post_building ELSE a2.house END) AS post_building,
				(CASE WHEN d.post_address_id IS NULL THEN c.post_apartment ELSE a2.flat END) AS post_apartment,
				(CASE WHEN d.post_address_id IS NULL THEN c.post_name ELSE a2.name END) AS post_name,
				(CASE WHEN d.post_address_id IS NULL THEN c.post_address ELSE a2.address END) AS post_address,
				(CASE WHEN d.post_address_id IS NULL THEN c.post_zip ELSE a2.zip END) AS post_zip,
				(CASE WHEN d.post_address_id IS NULL THEN c.post_city ELSE a2.city END) AS post_city,
				(CASE WHEN d.post_address_id IS NULL THEN c.post_postoffice ELSE a2.postoffice END) AS post_postoffice,
				(CASE WHEN d.post_address_id IS NULL THEN c.post_countryid ELSE a2.country_id END) AS post_countryid,
				cp.name AS post_country,
				(CASE WHEN d.div_countryid IS NOT NULL
				    THEN (CASE WHEN d.countryid IS NULL
				        THEN cdv.ccode
				        ELSE cn.ccode
				    END)
				    ELSE NULL
				END) AS lang,
				d.currency, d.currencyvalue, d.memo
				FROM documents d
				JOIN customeraddressview c ON (c.id = d.customerid)
				LEFT JOIN countries cn ON (cn.id = d.countryid)
				LEFT JOIN countries cdv ON cdv.id = d.div_countryid
				LEFT JOIN numberplans n ON (d.numberplanid = n.id)
				LEFT JOIN vaddresses a ON d.recipient_address_id = a.id
				LEFT JOIN vaddresses a2 ON d.post_address_id = a2.id
				LEFT JOIN countries cp ON (d.post_address_id IS NOT NULL AND cp.id = a2.country_id) OR (d.post_address_id IS NULL AND cp.id = c.post_countryid)
				WHERE d.id = ? AND (d.type = ? OR d.type = ? OR d.type = ?)',
                array($invoiceid, DOC_INVOICE, DOC_CNOTE, DOC_INVOICE_PRO)
            );
        }

        if ($result) {
            if ($detail_level == self::INVOICE_CONTENT_DETAIL_ALL && !empty($result['recipient_address_id'])) {
                $result['recipient_address'] = array(
                    'address_id' => $result['recipient_address_id'],
                    'location_name' => $result['rec_name'],
                    'location_state_name' => $result['rec_state'],
                    'location_state' => $result['rec_state_id'],
                    'location_city_name' => $result['rec_city'],
                    'location_city' => $result['rec_city_id'],
                    'location_street_name' => $result['rec_street'],
                    'location_street' => $result['rec_street_id'],
                    'location_house' => $result['rec_house'],
                    'location_zip' => $result['rec_zip'],
                    'location_postoffice' => $result['rec_postoffice'],
                    'location_country_id' => $result['rec_country_id'],
                    'location_flat' => $result['rec_flat'],
                    'location_address_type' => RECIPIENT_ADDRESS,
                );
                // generate address as single string
                $recipient_location = location_str(array(
                    'city_name'      => $result['recipient_address']['location_city_name'],
                    'postoffice'     => $result['recipient_address']['location_postoffice'],
                    'street_name'    => $result['recipient_address']['location_street_name'],
                    'location_house' => $result['recipient_address']['location_house'],
                    'location_flat'  => $result['recipient_address']['location_flat']
                ));

                if (strlen($recipient_location)) {
                    $result['recipient_address']['location'] = (empty($result['recipient_address']['location_name']) ? '' : $result['recipient_address']['location_name'] . ', ')
                        . (empty($result['recipient_address']['location_zip']) ? '' : $result['recipient_address']['location_zip'] . ' ') . $recipient_location;
                } else {
                    $result['recipient_address']['location'] = trans('undefined');
                }
            }

            if ($detail_level >= self::INVOICE_CONTENT_DETAIL_MORE) {
                $result['bankaccounts'] = $this->db->GetCol(
                    'SELECT contact FROM customercontacts
                    WHERE customerid = ? AND (type & ?) = ?',
                    array($result['customerid'], CONTACT_BANKACCOUNT | CONTACT_INVOICES | CONTACT_DISABLED,
                        CONTACT_BANKACCOUNT | CONTACT_INVOICES)
                );
                if (empty($result['bankaccounts'])) {
                    $result['bankaccounts'] = array();
                }
            }

            $result['pdiscount'] = 0;
            $result['vdiscount'] = 0;
            $result['totalbase'] = 0;
            $result['totaltax'] = 0;
            $result['total'] = 0;

            if ($result['reference'] && $result['type'] != DOC_INVOICE_PRO) {
                $result['invoice'] = $this->GetInvoiceContent($result['reference'], $detail_level);
                if (isset($result['invoice']['invoice'])) {
                    // replace pointed correction note number to previous one in invoice chain
                    $result['invoice']['number'] = $result['invoice']['invoice']['number'];
                    $result['invoice']['numberplanid'] = $result['invoice']['invoice']['numberplanid'];
                    $result['invoice']['template'] = $result['invoice']['invoice']['template'];
                    $result['invoice']['cdate'] = $result['invoice']['invoice']['cdate'];
                }
            }

            if (!$result['division_header']) {
                $location_manager = new LMSLocationManager($this->db, $this->auth, $this->cache, $this->syslog);
                $result['division_header'] = $result['division_name'] . "\n"
                        . $result['division_address'] . "\n" . $result['division_zip'] . ' ' . $result['division_city']
                        . ($result['division_countryid'] && $result['countryid'] && $result['division_countryid'] != $result['countryid'] ? "\n" . trans($location_manager->GetCountryName($result['division_countryid'])) : '')
                        . ($result['division_ten'] != '' ? "\n" . trans('TEN') . ' ' . $result['division_ten'] : '');
            }

            if ($result['content'] = $this->db->GetAllByKey('SELECT invoicecontents.value AS value,
						itemid, taxid, (CASE WHEN taxes.reversecharge = 1 THEN -2 ELSE (
								CASE WHEN taxes.taxed = 0 THEN -1 ELSE taxes.value END
							) END) AS taxvalue, taxes.label AS taxlabel, taxcategory,
						prodid, content, count, invoicecontents.description AS description,
						tariffid, itemid, pdiscount, vdiscount
						FROM invoicecontents
						LEFT JOIN taxes ON taxid = taxes.id
						WHERE docid=?
						ORDER BY itemid', 'itemid', array($invoiceid))
            ) {
                foreach ($result['content'] as $idx => $row) {
                    if (isset($result['invoice'])) {
                        $row['value'] += $result['invoice']['content'][$idx]['value'];
                        $row['count'] += $result['invoice']['content'][$idx]['count'];
                    }

                    if ($row['taxvalue'] < 0) {
                        $rounded_taxvalue = round($row['taxvalue']);
                        $taxvalue = 0;
                    } else {
                        $taxvalue = $rounded_taxvalue = $row['taxvalue'];
                    }
                    $result['content'][$idx]['total'] = round($row['value'] * $row['count'], 2);
                    $result['content'][$idx]['totalbase'] = round($result['content'][$idx]['total'] / (100 + $taxvalue) * 100, 2);
                    $result['content'][$idx]['totaltax'] = round($result['content'][$idx]['total'] - $result['content'][$idx]['totalbase'], 2);
                    $result['content'][$idx]['value'] = $row['value'];
                    $result['content'][$idx]['count'] = $row['count'];
                    if (isset($result['invoice']) && empty($row['count'])) {
                        $result['content'][$idx]['basevalue'] = $result['invoice']['content'][$idx]['basevalue'];
                    } else {
                        $result['content'][$idx]['basevalue'] = round($result['content'][$idx]['totalbase'] / $row['count'], 2);
                    }

                    if (isset($result['taxest'][$rounded_taxvalue])) {
                        $result['taxest'][$rounded_taxvalue]['base'] += $result['content'][$idx]['totalbase'];
                        $result['taxest'][$rounded_taxvalue]['total'] += $result['content'][$idx]['total'];
                        $result['taxest'][$rounded_taxvalue]['tax'] += $result['content'][$idx]['totaltax'];
                    } else {
                        $result['taxest'][$rounded_taxvalue]['base'] = $result['content'][$idx]['totalbase'];
                        $result['taxest'][$rounded_taxvalue]['total'] = $result['content'][$idx]['total'];
                        $result['taxest'][$rounded_taxvalue]['tax'] = $result['content'][$idx]['totaltax'];
                        $result['taxest'][$rounded_taxvalue]['taxlabel'] = $row['taxlabel'];
                    }

                    $result['totalbase'] += $result['content'][$idx]['totalbase'];
                    $result['totaltax'] += $result['content'][$idx]['totaltax'];
                    $result['total'] += $result['content'][$idx]['total'];

                    // for backward compatybility
                    $result['taxest'][$rounded_taxvalue]['taxvalue'] = $taxvalue;
                    $result['content'][$idx]['pkwiu'] = $row['prodid'];

                    $result['pdiscount'] += $row['pdiscount'];
                    $result['vdiscount'] += $row['vdiscount'];
                }
            }

            $result['pdate'] = $result['cdate'] + ($result['paytime'] * 86400);
            $result['value'] = $result['total'] - (isset($result['invoice']) ? $result['invoice']['total'] : 0);

            if ($result['value'] < 0) {
                $result['value'] = abs($result['value']);
                $result['rebate'] = true;
            }
            $result['valuep'] = round(($result['value'] - floor($result['value'])) * 100);

            if ($detail_level >= self::INVOICE_CONTENT_DETAIL_MORE) {
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
            }

            $result['paytypename'] = $PAYTYPES[$result['paytype']];

            // for backward compat.
            $result['totalg'] = round(($result['value'] - floor($result['value'])) * 100);
            $result['year'] = date('Y', $result['cdate']);
            $result['month'] = date('m', $result['cdate']);
            $result['pesel'] = $result['ssn'];
            $result['nip'] = $result['ten'];

            if ($detail_level == self::INVOICE_CONTENT_DETAIL_ALL) {
                if ($result['post_name'] || $result['post_address']) {
                    $result['serviceaddr'] = $result['post_name'];
                    if ($result['post_address']) {
                        $result['serviceaddr'] .= "\n" . $result['post_address'];
                    }
                    if ($result['post_zip'] && $result['post_city']) {
                        $result['serviceaddr'] .= "\n" . $result['post_zip'] . ' ' . $result['post_city'];
                    }
                }
            }

            $result['disable_protection'] = ConfigHelper::checkConfig('invoices.disable_protection');
            $result['protection_password'] = ConfigHelper::getConfig('invoices.protection_password');

            return $result;
        } else {
            return false;
        }
    }

    public function GetNoteList(array $params)
    {
        extract($params);
        foreach (array('search', 'cat', 'group', 'exclude', 'hideclosed') as $var) {
            if (!isset($$var)) {
                $$var = null;
            }
        }
        if (!isset($order)) {
            $order = '';
        }
        if (!isset($count)) {
            $count = false;
        }

        if ($order=='') {
            $order='id,asc';
        }

        list($order,$direction) = sscanf($order, '%[^,],%s');
        ($direction=='desc') ? $direction = 'desc' : $direction = 'asc';

        switch ($order) {
            case 'id':
                $sqlord = ' ORDER BY d.id';
                break;
            case 'cdate':
                $sqlord = ' ORDER BY d.cdate';
                break;
            case 'number':
                $sqlord = ' ORDER BY number';
                break;
            case 'value':
                $sqlord = ' ORDER BY value';
                break;
            case 'count':
                $sqlord = ' ORDER BY count';
                break;
            case 'name':
                $sqlord = ' ORDER BY name';
                break;
        }

        $where = '';

        if ($search!='' && $cat) {
            switch ($cat) {
                case 'number':
                    $where = ' AND number = '.intval($search);
                    break;
                case 'cdate':
                    $where = ' AND cdate >= '.intval($search).' AND cdate < '.(intval($search)+86400);
                    break;
                case 'month':
                    $last = mktime(23, 59, 59, date('n', $search) + 1, 0, date('Y', $search));
                    $where = ' AND cdate >= '.intval($search).' AND cdate <= '.$last;
                    break;
                case 'ten':
                    $where = ' AND ten = ' . $this->db->Escape($search);
                    break;
                case 'customerid':
                    $where = ' AND d.customerid = '.intval($search);
                    break;
                case 'name':
                    $where = ' AND UPPER(d.name) ?LIKE? UPPER(' . $this->db->Escape('%' . $search . '%') . ')';
                    break;
                case 'address':
                    $where = ' AND UPPER(address) ?LIKE? UPPER(' . $this->db->Escape('%' . $search . '%') . ')';
                    break;
                case 'value':
                    $having = ' HAVING SUM(n.value) = '.str_replace(',', '.', f_round($search)).' ';
                    break;
            }
        }

        if ($hideclosed) {
            $where .= ' AND closed = 0';
        }

        if ($count) {
            return $this->db->GetOne('SELECT COUNT(*) FROM (SELECT d.id
			FROM documents d
			JOIN debitnotecontents n ON (n.docid = d.id)
			LEFT JOIN countries c ON (c.id = d.countryid)
			LEFT JOIN numberplans ON (d.numberplanid = numberplans.id)
			LEFT JOIN (
				SELECT DISTINCT a.customerid FROM customerassignments a
				JOIN excludedgroups e ON (a.customergroupid = e.customergroupid)
				WHERE e.userid = lms_current_user()
				) e ON (e.customerid = d.customerid)
			WHERE e.customerid IS NULL AND type = '.DOC_DNOTE
                .$where
                .(!empty($group) ?
                    ' AND '.(!empty($exclude) ? 'NOT' : '').' EXISTS (
						SELECT 1 FROM customerassignments WHERE customergroupid = '.intval($group).'
						AND customerid = d.customerid)' : '')
                .' GROUP BY d.id, number, cdate, cancelled, d.customerid,
			d.name, address, zip, city, d.template, closed, published, c.name '
                .(isset($having) ? $having : '') . ') a');
        }

        $result = $this->db->GetAll('SELECT d.id AS id, number, cdate, numberplans.template, closed, published,
			archived, cancelled,
			d.customerid, d.name, address, zip, city, c.name AS country,
			SUM(n.value) AS value, COUNT(n.docid) AS count,
			d.currency, d.currencyvalue
			FROM documents d
			JOIN debitnotecontents n ON (n.docid = d.id)
			LEFT JOIN countries c ON (c.id = d.countryid)
			LEFT JOIN numberplans ON (d.numberplanid = numberplans.id)
			LEFT JOIN (
				SELECT DISTINCT a.customerid FROM customerassignments a
				JOIN excludedgroups e ON (a.customergroupid = e.customergroupid)
				WHERE e.userid = lms_current_user()
				) e ON (e.customerid = d.customerid)
			WHERE e.customerid IS NULL AND type = '.DOC_DNOTE
            .$where
            .(!empty($group) ?
                ' AND '.(!empty($exclude) ? 'NOT' : '').' EXISTS (
			            SELECT 1 FROM customerassignments WHERE customergroupid = '.intval($group).'
			            AND customerid = d.customerid)' : '')
            .' GROUP BY d.id, number, cdate, archived, cancelled, d.customerid,
			d.name, address, zip, city, numberplans.template, closed, published, c.name, d.currency, d.currencyvalue '
            .(isset($having) ? $having : '')
            .$sqlord.' '.$direction
            . (isset($limit) ? ' LIMIT ' . $limit : '')
            . (isset($offset) ? ' OFFSET ' . $offset : ''));

        if (empty($result)) {
            $result = array();
        }

        $result['order'] = $order;
        $result['direction'] = $direction;

        return $result;
    }

    public function GetNoteContent($id)
    {
        global $LMS;

        if ($result = $this->db->GetRow('SELECT d.id, d.number, d.name, d.customerid,
                d.userid, d.address, d.zip, d.city, d.countryid, cn.name AS country,
				d.ten, d.ssn, d.cdate, d.numberplanid, d.closed, d.cancelled, d.published, d.archived, d.divisionid, d.paytime,
				(SELECT name FROM vusers WHERE id = d.userid) AS user, n.template,
				d.div_name AS division_name, d.div_shortname AS division_shortname,
				d.div_address AS division_address, d.div_zip AS division_zip,
				d.div_city AS division_city, d.div_countryid AS division_countryid,
				d.div_ten AS division_ten, d.div_regon AS division_regon, d.div_bank AS div_bank, d.div_account AS account,
				d.div_inv_header AS division_header, d.div_inv_footer AS division_footer,
				d.div_inv_author AS division_author, d.div_inv_cplace AS division_cplace,
				d.post_address_id,
				c.pin AS customerpin, c.divisionid AS current_divisionid,
				c.street, c.building, c.apartment,
				(CASE WHEN d.post_address_id IS NULL THEN c.post_street ELSE a2.street END) AS post_street,
				(CASE WHEN d.post_address_id IS NULL THEN c.post_building ELSE a2.house END) AS post_building,
				(CASE WHEN d.post_address_id IS NULL THEN c.post_apartment ELSE a2.flat END) AS post_apartment,
				(CASE WHEN d.post_address_id IS NULL THEN c.post_name ELSE a2.name END) AS post_name,
				(CASE WHEN d.post_address_id IS NULL THEN c.post_address ELSE a2.address END) AS post_address,
				(CASE WHEN d.post_address_id IS NULL THEN c.post_zip ELSE a2.zip END) AS post_zip,
				(CASE WHEN d.post_address_id IS NULL THEN c.post_city ELSE a2.city END) AS post_city,
				(CASE WHEN d.post_address_id IS NULL THEN c.post_postoffice ELSE a2.postoffice END) AS post_postoffice,
				(CASE WHEN d.post_address_id IS NULL THEN c.post_countryid ELSE a2.country_id END) AS post_countryid,
				cp.name AS post_country,
				(CASE WHEN d.div_countryid IS NOT NULL
				    THEN (CASE WHEN d.countryid IS NULL
				        THEN cdv.ccode
				        ELSE cn.ccode
				    END)
				    ELSE NULL
				END) AS lang,
				d.currency, d.currencyvalue
				FROM documents d
				JOIN customeraddressview c ON (c.id = d.customerid)
				LEFT JOIN countries cn ON (cn.id = d.countryid)
				LEFT JOIN countries cdv ON cdv.id = d.div_countryid
				LEFT JOIN numberplans n ON (d.numberplanid = n.id)
				LEFT JOIN vaddresses a2 ON a2.id = d.post_address_id
				LEFT JOIN countries cp ON (d.post_address_id IS NOT NULL AND cp.id = a2.country_id) OR (d.post_address_id IS NULL AND cp.id = c.post_countryid)
				WHERE d.id = ? AND d.type = ?', array($id, DOC_DNOTE))) {
            $result['bankaccounts'] = $this->db->GetCol(
                'SELECT contact FROM customercontacts
				WHERE customerid = ? AND (type & ?) = ?',
                array($result['customerid'], CONTACT_BANKACCOUNT | CONTACT_INVOICES | CONTACT_DISABLED,
                    CONTACT_BANKACCOUNT | CONTACT_INVOICES)
            );
            if (empty($result['bankaccounts'])) {
                $result['bankaccounts'] = array();
            }

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
            ) {
                foreach ($result['content'] as $idx => $row) {
                    $result['content'][$idx]['value'] = $row['value'];
                    $result['value'] += $row['value'];
                }
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
                if ($result['post_address']) {
                    $result['serviceaddr'] .= "\n" . $result['post_address'];
                }
                if ($result['post_zip'] && $result['post_city']) {
                    $result['serviceaddr'] .= "\n" . $result['post_zip'] . ' ' . $result['post_city'];
                }
            }

            $result['disable_protection'] = ConfigHelper::checkConfig('notes.disable_protection');
            $result['protection_password'] = ConfigHelper::getConfig('notes.protection_password');

            return $result;
        } else {
            return false;
        }
    }

    public function TariffAdd($tariff)
    {
        global $ACCOUNTTYPES;

        $args = array(
            'name' => $tariff['name'],
            'description' => $tariff['description'],
            'value' => $tariff['value'],
            'splitpayment' => isset($tariff['splitpayment']) ? 1 : 0,
            'taxcategory' => $tariff['taxcategory'],
            'currency' => isset($tariff['currency']) ? $tariff['currency'] : Localisation::getCurrentCurrency(),
            'period' => $tariff['period'] ? $tariff['period'] : null,
            SYSLOG::RES_TAX => empty($tariff['taxid']) ? null : $tariff['taxid'],
            SYSLOG::RES_NUMPLAN => $tariff['numberplanid'] ? $tariff['numberplanid'] : null,
            'datefrom' => $tariff['from'] ? $tariff['from'] : 0,
            'dateto' => $tariff['to'] ? $tariff['to'] : 0,
            'prodid' => $tariff['prodid'],
            'uprate' => $tariff['uprate'],
            'downrate' => $tariff['downrate'],
            'upceil' => $tariff['upceil'],
            'up_burst_time' => $tariff['up_burst_time'],
            'up_burst_threshold' => $tariff['up_burst_threshold'],
            'up_burst_limit' => $tariff['up_burst_limit'],
            'downceil' => $tariff['downceil'],
            'down_burst_time' => $tariff['down_burst_time'],
            'down_burst_threshold' => $tariff['down_burst_threshold'],
            'down_burst_limit' => $tariff['down_burst_limit'],
            'climit' => $tariff['climit'],
            'plimit' => $tariff['plimit'],
            'uprate_n' => $tariff['uprate_n'],
            'downrate_n' => $tariff['downrate_n'],
            'upceil_n' => $tariff['upceil_n'],
            'up_burst_time_n' => $tariff['up_burst_time_n'],
            'up_burst_threshold_n' => $tariff['up_burst_threshold_n'],
            'up_burst_limit_n' => $tariff['up_burst_limit_n'],
            'downceil_n' => $tariff['downceil_n'],
            'down_burst_time_n' => $tariff['down_burst_time_n'],
            'down_burst_threshold_n' => $tariff['down_burst_threshold_n'],
            'down_burst_limit_n' => $tariff['down_burst_limit_n'],
            'climit_n' => $tariff['climit_n'],
            'plimit_n' => $tariff['plimit_n'],
            'dlimit' => $tariff['dlimit'],
            'type' => $tariff['type'],
            'domain_limit' => $tariff['domain_limit'],
            'alias_limit' => $tariff['alias_limit'],
            'authtype' => $tariff['authtype'],
        );
        $args2 = array();
        foreach ($ACCOUNTTYPES as $typeidx => $type) {
            $args2[$type['alias'] . '_limit'] = $tariff[$type['alias'] . '_limit'];
            $args2['quota_' . $type['alias'] . '_limit'] = $tariff['quota_' . $type['alias'] . '_limit'];
        }
        $result = $this->db->Execute(
            'INSERT INTO tariffs (name, description, value, splitpayment, taxcategory, currency,
				period, taxid, numberplanid, datefrom, dateto, prodid, uprate, downrate,
				upceil, up_burst_time, up_burst_threshold, up_burst_limit,
				downceil, down_burst_time, down_burst_threshold, down_burst_limit,
				climit, plimit, uprate_n, downrate_n,
				upceil_n, up_burst_time_n, up_burst_threshold_n, up_burst_limit_n,
				downceil_n, down_burst_time_n, down_burst_threshold_n, down_burst_limit_n,
				climit_n, plimit_n, dlimit, type, domain_limit, alias_limit, authtype, '
                . implode(', ', array_keys($args2)) . ')
				VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,
					?,?,?,?,?,?,?,?,?,?,?,?,' . implode(',', array_fill(0, count($args2), '?')) . ')',
            array_values(array_merge($args, $args2))
        );
        if ($result) {
            $id = $this->db->GetLastInsertID('tariffs');

            $tarifftag_manager = new LMSTariffTagManager($this->db, $this->auth, $this->cache, $this->syslog);
            $tarifftag_manager->updateTariffTagsForTariff($id, $tariff['tags']);

            if ($this->syslog) {
                $args[SYSLOG::RES_TARIFF] = $id;
                $this->syslog->AddMessage(SYSLOG::RES_TARIFF, SYSLOG::OPER_ADD, $args);
            }
            return $id;
        } else {
            return false;
        }
    }

    public function TariffUpdate($tariff)
    {
        global $ACCOUNTTYPES;

        $args = array(
            'name' => $tariff['name'],
            'description' => $tariff['description'],
            'value' => $tariff['value'],
            'splitpayment' => isset($tariff['splitpayment']) ? 1 : 0,
            'taxcategory' => $tariff['taxcategory'],
            'currency' => $tariff['currency'],
            'period' => $tariff['period'] ? $tariff['period'] : null,
            SYSLOG::RES_TAX => empty($tariff['taxid']) ? null : $tariff['taxid'],
            SYSLOG::RES_NUMPLAN => $tariff['numberplanid'] ? $tariff['numberplanid'] : null,
            'datefrom' => $tariff['from'],
            'dateto' => $tariff['to'],
            'prodid' => $tariff['prodid'],
            'uprate' => $tariff['uprate'],
            'downrate' => $tariff['downrate'],
            'upceil' => $tariff['upceil'],
            'up_burst_time' => $tariff['up_burst_time'],
            'up_burst_threshold' => $tariff['up_burst_threshold'],
            'up_burst_limit' => $tariff['up_burst_limit'],
            'downceil' => $tariff['downceil'],
            'down_burst_time' => $tariff['down_burst_time'],
            'down_burst_threshold' => $tariff['down_burst_threshold'],
            'down_burst_limit' => $tariff['down_burst_limit'],
            'climit' => $tariff['climit'],
            'plimit' => $tariff['plimit'],
            'uprate_n' => $tariff['uprate_n'],
            'downrate_n' => $tariff['downrate_n'],
            'upceil_n' => $tariff['upceil_n'],
            'up_burst_time_n' => $tariff['up_burst_time_n'],
            'up_burst_threshold_n' => $tariff['up_burst_threshold_n'],
            'up_burst_limit_n' => $tariff['up_burst_limit_n'],
            'downceil_n' => $tariff['downceil_n'],
            'down_burst_time_n' => $tariff['down_burst_time_n'],
            'down_burst_threshold_n' => $tariff['down_burst_threshold_n'],
            'down_burst_limit_n' => $tariff['down_burst_limit_n'],
            'climit_n' => $tariff['climit_n'],
            'plimit_n' => $tariff['plimit_n'],
            'dlimit' => $tariff['dlimit'],
            'domain_limit' => $tariff['domain_limit'],
            'alias_limit' => $tariff['alias_limit'],
            'type' => $tariff['type'],
            'voip_tariff_id' => (!empty($tariff['voip_pricelist'])) ? $tariff['voip_pricelist'] : null,
            'voip_tariff_rule_id' => (!empty($tariff['voip_tariffrule'])) ? $tariff['voip_tariffrule'] : null,
            'authtype' => $tariff['authtype'],
        );
        $args2 = array();
        foreach ($ACCOUNTTYPES as $typeidx => $type) {
            $args2[$type['alias'] . '_limit'] = $tariff[$type['alias'] . '_limit'];
            $args2['quota_' . $type['alias'] . '_limit'] = $tariff['quota_' . $type['alias'] . '_limit'];
        }
        $fields = array_keys($args2);
        $args = array_merge($args, $args2);
        $args[SYSLOG::RES_TARIFF] = $tariff['id'];
        $res = $this->db->Execute('UPDATE tariffs SET name = ?, description = ?, value = ?,
            splitpayment = ?, taxcategory = ?, currency = ?,
            period = ?, taxid = ?, numberplanid = ?, datefrom = ?, dateto = ?, prodid = ?,
            uprate = ?, downrate = ?,
            upceil = ?, up_burst_time = ?, up_burst_threshold = ?, up_burst_limit = ?,
            downceil = ?, down_burst_time = ?, down_burst_threshold = ?, down_burst_limit = ?,
            climit = ?, plimit = ?,
            uprate_n = ?, downrate_n = ?,
            upceil_n = ?, up_burst_time_n = ?, up_burst_threshold_n = ?, up_burst_limit_n = ?,
            downceil_n = ?, down_burst_time_n = ?, down_burst_threshold_n = ?, down_burst_limit_n = ?,
            climit_n = ?, plimit_n = ?,
            dlimit = ?, domain_limit = ?, alias_limit = ?, type = ?, voip_tariff_id = ?, voip_tariff_rule_id = ?, 
            authtype = ?, '
            . implode(' = ?, ', $fields) . ' = ? WHERE id=?', array_values($args));
        if ($res && $this->syslog) {
            $this->syslog->AddMessage(SYSLOG::RES_TARIFF, SYSLOG::OPER_UPDATE, $args);
        }

        $tarifftag_manager = new LMSTariffTagManager($this->db, $this->auth, $this->cache, $this->syslog);
        $tarifftag_manager->updateTariffTagsForTariff($tariff['id'], $tariff['tags']);

        return $res;
    }

    public function TariffDelete($id)
    {
        if ($this->syslog) {
            $assigns = $this->db->GetAll('SELECT promotionid, a.id, promotionschemaid FROM promotionassignments a
				JOIN promotionschemas s ON s.id = a.promotionschemaid
				WHERE a.tariffid = ?', array($id));
        }
        $res = $this->db->Execute('DELETE FROM tariffs WHERE id=?', array($id));
        if ($res && $this->syslog) {
            $this->syslog->AddMessage(SYSLOG::RES_TARIFF, SYSLOG::OPER_DELETE, array(SYSLOG::RES_TARIFF => $id));
            if (!empty($assigns)) {
                foreach ($assigns as $assign) {
                    $args = array(
                    SYSLOG::RES_PROMOASSIGN => $assign['id'],
                    SYSLOG::RES_PROMOSCHEMA => $assign['promotionschemaid'],
                    SYSLOG::RES_PROMO => $assign['promotionid'],
                    SYSLOG::RES_TARIFF => $id
                    );
                    $this->syslog->AddMessage(SYSLOG::RES_PROMOASSIGN, SYSLOG::OPER_DELETE, $args);
                }
            }
        }
        return $res;
    }

    public function GetTariff($id, $network = null)
    {
        if ($network) {
            $network_manager = new LMSNetworkManager($this->db, $this->auth, $this->cache, $this->syslog);
            $net = $network_manager->GetNetworkParams($network);
        }

        $result = $this->db->GetRow('SELECT t.*, taxes.label AS tax, taxes.value AS taxvalue
			FROM tariffs t
			LEFT JOIN taxes ON (t.taxid = taxes.id)
			WHERE t.id=?', array($id));

        $result['customers'] = $this->db->GetAll('SELECT c.id AS id, COUNT(c.id) AS cnt,
                COUNT(CASE WHEN s.customerid IS NULL AND commited = 1 AND suspended = 0 AND datefrom < ?NOW? AND (dateto = 0 OR dateto > ?NOW?) THEN 1 ELSE NULL END) AS active, '
                . $this->db->Concat('c.lastname', "' '", 'c.name') . ' AS customername '
                . ($network ? ', COUNT(vnodes.id) AS nodescount ' : '')
                . 'FROM assignments, customerview c
                LEFT JOIN (
                    SELECT DISTINCT a.customerid
                    FROM assignments a
                    WHERE a.tariffid IS NULL AND a.liabilityid IS NULL
                        AND a.datefrom < ?NOW? AND (a.dateto = 0 OR a.dateto > ?NOW?)
                ) s ON s.customerid = c.id '
                . ($network ? 'LEFT JOIN vnodes ON (c.id = vnodes.ownerid) ' : '')
                . 'WHERE c.id = assignments.customerid AND commited = 1 AND deleted = 0 AND tariffid = ? '
                . ($network ? 'AND ((ipaddr > ' . $net['address'] . ' AND ipaddr < ' . $net['broadcast'] . ') OR (ipaddr_pub > '
                        . $net['address'] . ' AND ipaddr_pub < ' . $net['broadcast'] . ')) ' : '')
                . 'GROUP BY c.id, c.lastname, c.name ORDER BY c.lastname, c.name', array($id));

        $tarifftag_manager = new LMSTariffTagManager($this->db, $this->auth, $this->cache, $this->syslog);
        $result['tags'] = $tarifftag_manager->getTariffTagsForTariff($id);

        $unactive = $this->db->GetAllByKey('SELECT SUM(a.count) AS count,
            SUM(
                (((t.value * (100 - a.pdiscount)) / 100.0) - a.vdiscount)
                * (CASE t.period
                    WHEN ' . MONTHLY . ' THEN 1
                    WHEN ' . QUARTERLY . ' THEN 1 / 3
                    WHEN ' . HALFYEARLY . ' THEN 1 / 6
                    WHEN ' . YEARLY . ' THEN 1 / 12
                    ELSE (CASE a.period
                        WHEN ' . MONTHLY . ' THEN 1
                        WHEN ' . QUARTERLY . ' THEN 1 / 3
                        WHEN ' . HALFYEARLY . ' THEN 1 / 6
                        WHEN ' . YEARLY . ' THEN 1 / 12
                        ELSE 0
                    END)
                END) * a.count) AS value,
                t.currency
            FROM assignments a
            JOIN tariffs t ON (t.id = a.tariffid)
            WHERE t.id = ? AND a.commited = 1 AND (
                a.suspended = 1
                OR a.datefrom > ?NOW?
                OR (a.dateto <= ?NOW? AND a.dateto != 0)
                OR EXISTS (
                    SELECT 1 FROM assignments b
                    WHERE b.customerid = a.customerid
                        AND liabilityid IS NULL AND tariffid IS NULL
                        AND b.datefrom <= ?NOW? AND (b.dateto > ?NOW? OR b.dateto = 0)
                )
			)
			GROUP BY t.currency', 'currency', array($id));

        $all = $this->db->GetAllByKey('SELECT COUNT(*) AS count,
            SUM(
                (((t.value * (100 - a.pdiscount)) / 100.0) - a.vdiscount)
                * (CASE t.period
                    WHEN ' . MONTHLY . ' THEN 1
                    WHEN ' . QUARTERLY . ' THEN 1 / 3
                    WHEN ' . HALFYEARLY . ' THEN 1 / 6
                    WHEN ' . YEARLY . ' THEN 1 / 12
                    ELSE (CASE a.period
                        WHEN ' . MONTHLY . ' THEN 1
                        WHEN ' . QUARTERLY . ' THEN 1 / 3
                        WHEN ' . HALFYEARLY . ' THEN 1 / 6
                        WHEN ' . YEARLY . ' THEN 1 / 12
                        ELSE 0
                    END)
                END) * a.count) AS value,
                t.currency
            FROM assignments a
            JOIN tariffs t ON (t.id = a.tariffid)
            WHERE tariffid = ? AND commited = 1
            GROUP BY t.currency', 'currency', array($id));

        // count of all customers with that tariff
        $result['customerscount'] = empty($result['customers']) ? 0 : count($result['customers']);

        $result['count'] = 0;
        $result['activecount'] = 0;
        $result['totalval'] = array();
        if (!empty($all)) {
            foreach ($all as $currency => $row) {
                // count of all assignments
                $result['count'] += $row['count'];
                // count of 'active' assignments
                $result['activecount'] += $row['count']
                    - (isset($unactive[$currency]) ? $unactive[$currency]['count'] : 0);
                // avg monthly income (without unactive assignments)
                if (!isset($result['totalval'][$currency])) {
                    $result['totalval'][$currency] = 0;
                }
                $result['totalval'][$currency] = $row['value']
                    - (isset($unactive[$currency]) ? $unactive[$currency]['value'] : 0);
            }
        }

        $result['rows'] = ceil($result['customerscount'] / 2);
        return $result;
    }

    public function GetTariffs($forced_id = null)
    {
        return $this->db->GetAllByKey('SELECT t.id, t.name, t.value, t.splitpayment, t.taxcategory, t.currency, uprate, taxid, t.authtype,
				datefrom, dateto, (CASE WHEN datefrom < ?NOW? AND (dateto = 0 OR dateto > ?NOW?) THEN 1 ELSE 0 END) AS valid,
				prodid, downrate, upceil, downceil, climit, plimit, taxes.value AS taxvalue,
				taxes.label AS tax, t.period, t.type AS tarifftype, ' . $this->db->GroupConcat('ta.tarifftagid') . ' AS tags
				FROM tariffs t
				LEFT JOIN tariffassignments ta ON ta.tariffid = t.id
				LEFT JOIN taxes ON t.taxid = taxes.id
				WHERE t.disabled = 0' . (empty($forced_id) ? '' : ' OR t.id = ' . intval($forced_id)) . '
				GROUP BY t.id, t.name, t.value, t.splitpayment, t.taxcategory, t.currency, uprate, taxid, t.authtype, datefrom, dateto, prodid, downrate, upceil, downceil, climit, plimit,
					taxes.value, taxes.label, t.period, t.type
				ORDER BY t.name, t.value DESC', 'id');
    }

    public function TariffSet($id)
    {
        if ($this->db->GetOne('SELECT disabled FROM tariffs WHERE id = ?', array($id)) == 1) {
            return $this->db->Execute('UPDATE tariffs SET disabled = 0 WHERE id = ?', array($id));
        } else {
            return $this->db->Execute('UPDATE tariffs SET disabled = 1 WHERE id = ?', array($id));
        }
    }

    public function TariffExists($id)
    {
        return ($this->db->GetOne('SELECT id FROM tariffs WHERE id=?', array($id)) ? true : false);
    }

    public function ReceiptDelete($docid)
    {
        if ($this->syslog) {
            $customerid = $this->db->GetOne('SELECT customerid FROM documents WHERE id=?', array($docid));
            $itemids = $this->db->GetCol('SELECT itemid FROM receiptcontents WHERE docid=?', array($docid));
            foreach ($itemids as $itemid) {
                $args = array(
                    SYSLOG::RES_DOC => $docid,
                    SYSLOG::RES_CUST => $customerid,
                    'itemid' => $itemid,
                );
                $this->syslog->AddMessage(SYSLOG::RES_RECEIPTCONT, SYSLOG::OPER_DELETE, $args);
            }
            $args = array(
                SYSLOG::RES_DOC => $docid,
                SYSLOG::RES_CUST => $customerid,
            );
            $this->syslog->AddMessage(SYSLOG::RES_DOC, SYSLOG::OPER_DELETE, $args);
            $cashids = $this->db->GetCol('SELECT id FROM cash WHERE docid=?', array($docid));
            foreach ($cashids as $itemid) {
                $args = array(
                    SYSLOG::RES_CASH => $itemid,
                    SYSLOG::RES_DOC => $docid,
                    SYSLOG::RES_CUST => $customerid,
                );
                $this->syslog->AddMessage(SYSLOG::RES_CASH, SYSLOG::OPER_DELETE, $args);
            }
        }
        $this->db->Execute('DELETE FROM documents WHERE id = ?', array($docid));
    }

    public function ReceiptContentDelete($docid, $itemid = 0)
    {
        if ($itemid) {
            if ($this->syslog) {
                $customerid = $this->db->GetOne('SELECT customerid FROM documents WHERE id=?', array($docid));
                $args = array(
                    SYSLOG::RES_DOC => $docid,
                    SYSLOG::RES_CUST => $customerid,
                    'itemid' => $itemid,
                );
                $this->syslog->AddMessage(SYSLOG::RES_RECEIPTCONT, SYSLOG::OPER_DELETE, $args);
            }
            $this->db->Execute('DELETE FROM receiptcontents WHERE docid=? AND itemid=?', array($docid, $itemid));

            if (!$this->db->GetOne('SELECT COUNT(*) FROM receiptcontents WHERE docid=?', array($docid))) {
                // if that was the last item of invoice contents
                if ($this->syslog) {
                    $args = array(
                        SYSLOG::RES_DOC => $docid,
                        SYSLOG::RES_CUST => $customerid,
                    );
                    $this->syslog->AddMessage(SYSLOG::RES_DOC, SYSLOG::OPER_DELETE, $args);
                }
                $this->db->Execute('DELETE FROM documents WHERE id = ?', array($docid));
            }
        } else {
            $this->ReceiptDelete($docid);
        }
    }

    public function DebitNoteDelete($noteid)
    {
        if ($this->syslog) {
            $customerid = $this->db->GetOne('SELECT customerid FROM documents WHERE id = ?', array($noteid));
            $args = array(
                SYSLOG::RES_DOC => $noteid,
                SYSLOG::RES_CUST => $customerid,
            );
            $this->syslog->AddMessage(SYSLOG::RES_DOC, SYSLOG::OPER_DELETE, $args);
            $dnoteitems = $this->db->GetCol(
                'SELECT id FROM debitnotecontents WHERE docid = ?',
                array($noteid)
            );
            foreach ($dnoteitems as $item) {
                $args = array(
                    SYSLOG::RES_DNOTECONT => $item,
                    SYSLOG::RES_DOC => $noteid,
                    SYSLOG::RES_CUST => $customerid,
                );
                $this->syslog->AddMessage(SYSLOG::RES_DNOTECONT, SYSLOG::OPER_DELETE, $args);
            }
            $cashitems = $this->db->GetCol('SELECT id FROM cash WHERE docid = ?', array($noteid));
            foreach ($cashitems as $item) {
                $args = array(
                    SYSLOG::RES_CASH => $item,
                    SYSLOG::RES_DOC => $noteid,
                    SYSLOG::RES_CUST => $customerid,
                );
                $this->syslog->AddMessage(SYSLOG::RES_CASH, SYSLOG::OPER_DELETE, $args);
            }
        }

        $document_manager = new LMSDocumentManager($this->db, $this->auth, $this->cache, $this->syslog);
        $document_manager->DeleteDocumentAddresses($noteid);

        $this->db->Execute('DELETE FROM documents WHERE id = ?', array($noteid));
    }

    public function DebitNoteContentDelete($docid, $itemid = 0)
    {
        if ($itemid) {
            if ($this->syslog) {
                list ($dnotecontid, $customerid) = array_values($this->db->GetRow('SELECT dn.id, customerid FROM debitnotecontents dn
					JOIN documents d ON d.id = dn.docid WHERE docid=? AND itemid=?', array($docid, $itemid)));
                $args = array(
                    SYSLOG::RES_DNOTECONT => $dnotecontid,
                    SYSLOG::RES_DOC => $docid,
                    SYSLOG::RES_CUST => $customerid,
                );
                $this->syslog->AddMessage(SYSLOG::RES_DNOTECONT, SYSLOG::OPER_DELETE, $args);
            }
            $this->db->Execute('DELETE FROM debitnotecontents WHERE docid=? AND itemid=?', array($docid, $itemid));

            if (!$this->db->GetOne('SELECT COUNT(*) FROM debitnotecontents WHERE docid=?', array($docid))) {
                // if that was the last item of debit note contents
                if ($this->syslog) {
                    $args = array(
                        SYSLOG::RES_DOC => $docid,
                        SYSLOG::RES_CUST => $customerid,
                    );
                    $this->syslog->AddMessage(SYSLOG::RES_DOC, SYSLOG::OPER_DELETE, $args);
                }
                $this->db->Execute('DELETE FROM documents WHERE id = ?', array($docid));
            }
        } else {
            $this->DebitNoteDelete($docid);
        }
    }

    public function GetBalanceList(array $params)
    {
        extract($params);
        foreach (array('search', 'cat', 'group', 'exclude') as $var) {
            if (!isset($$var)) {
                $$var = null;
            }
        }
        if (!isset($count)) {
            $count = false;
        }

        $where = '';

        if ($search && $cat) {
            switch ($cat) {
                case 'value':
                    $val = intval($search) > 0 ? intval($search) : intval($search)*-1;
                    $where = ' AND ABS(cash.value) = '.$val;
                    break;
                case 'number':
                    $where = ' AND documents.number = '.intval($search);
                    break;
                case 'cdate':
                    $where = ' AND cash.time >= '.intval($search).' AND cash.time < '.(intval($search)+86400);
                    break;
                case 'month':
                    $where = ' AND cash.time >= ' . intval($search) . ' AND cash.time < ' . strtotime('+1 month', $search);
                    break;
                case 'ten':
                    $where = ' AND c.ten = ' . $this->db->Escape($search);
                    break;
                case 'customerid':
                    $where = ' AND cash.customerid = '.intval($search);
                    break;
                case 'name':
                    $where = ' AND ' . $this->db->Concat('UPPER(c.lastname)', "' '", 'c.name').' ?LIKE? ' . $this->db->Escape("%$search%");
                    break;
                case 'address':
                    $where = ' AND c.address ?LIKE? ' . $this->db->Escape("%$search%");
                    break;
                case 'comment':
                    $where = ' AND cash.comment ?LIKE? ' . $this->db->Escape("%$search%");
                    break;
                case 'cashimport':
                    if (!empty($search)) {
                        $where = ' AND cash.importid IN (SELECT i.id FROM cashimport i WHERE i.sourcefileid '
                            . (is_array($search) ? ' IN (' . implode(',', Utils::filterIntegers($search)) . ')' : ' = ' . intval($search)) . ')';
                    }
                    break;
            }
        } elseif ($cat) {
            switch ($cat) {
                case 'documented':
                    $where = ' AND cash.docid IS NOT NULL';
                    break;
                case 'notdocumented':
                    $where = ' AND cash.docid IS NULL';
                    break;
            }
        }

        if ($from) {
            $where .= ' AND cash.time >= '.intval($from);
        }
        if ($to) {
            $where .= ' AND cash.time <= '.intval($to);
        }

        if ($count) {
            $summary = $this->db->GetRow('SELECT COUNT(cash.id) AS total,
					SUM(CASE WHEN cash.customerid IS NOT NULL AND cash.type = 0 THEN -value * cash.currencyvalue ELSE 0 END) AS liability,
					SUM(CASE WHEN (cash.customerid IS NULL OR cash.type <> 0) AND value > 0 THEN value * cash.currencyvalue ELSE 0 END) AS income, 
					SUM(CASE WHEN (cash.customerid IS NULL OR cash.type <> 0) AND value < 0 THEN -value * cash.currencyvalue ELSE 0 END) AS expense 
				FROM cash
				LEFT JOIN customerview c ON (cash.customerid = c.id)
				LEFT JOIN documents ON (documents.id = docid)
				WHERE 1=1 '
                .$where
                .(!empty($group) ?
                    ' AND '.(!empty($exclude) ? 'NOT' : '').' EXISTS (
					SELECT 1 FROM customerassignments WHERE customergroupid = '.intval($group).'
					AND customerid = cash.customerid)' : ''));
            if (empty($summary)) {
                return array('total' => 0, 'liability' => 0, 'income' => 0, 'expense' => 0, 'after' => 0);
            }

            return $summary;
        }

        if ($balancelist = $this->db->GetAll('SELECT cash.id AS id, time, cash.userid AS userid, cash.value AS value,
                cash.currency, cash.currencyvalue, 
				cash.customerid AS customerid, cash.comment, docid, cash.type AS type,
				documents.type AS doctype, documents.closed AS closed,
				documents.published, documents.archived, '
             . $this->db->Concat('UPPER(c.lastname)', "' '", 'c.name').' AS customername
				FROM cash
				LEFT JOIN customerview c ON (cash.customerid = c.id)
				LEFT JOIN documents ON (documents.id = docid)
				WHERE 1=1 '
            .$where
            .(!empty($group) ?
                ' AND '.(!empty($exclude) ? 'NOT' : '').' EXISTS (
					SELECT 1 FROM customerassignments WHERE customergroupid = '.intval($group).'
					AND customerid = cash.customerid)' : '')
            .' ORDER BY time, cash.id'
            . (isset($limit) ? ' LIMIT ' . $limit : '')
            . (isset($offset) ? ' OFFSET ' . $offset : ''))) {
            $userlist = $this->db->GetAllByKey('SELECT id, name FROM vusers', 'id');

            $after = $this->db->GetOne('SELECT SUM(value) FROM (
				SELECT (CASE WHEN cash.customerid IS NULL OR cash.type <> 0 THEN value * cash.currencyvalue ELSE 0 END) AS value
				FROM cash
				LEFT JOIN customerview c ON (cash.customerid = c.id)
				LEFT JOIN documents ON (documents.id = docid)
				WHERE 1=1 '
                .$where
                .(!empty($group) ?
                    ' AND '.(!empty($exclude) ? 'NOT' : '').' EXISTS (
					SELECT 1 FROM customerassignments WHERE customergroupid = '.intval($group).'
					AND customerid = cash.customerid)' : '')
                .' ORDER BY time, cash.id '
                . (isset($offset) ? ' LIMIT ' . $offset : '')
                . ') a');

            foreach ($balancelist as &$row) {
                $row['user'] = isset($userlist[$row['userid']]['name']) ? $userlist[$row['userid']]['name'] : '';
                $row['before'] = $after;

                if ($row['customerid'] && $row['type'] == 0) {
                    // customer covenant
                    $row['after'] = $row['before'];
                    $row['covenant'] = true;
                } else {
                    $row['after'] = $row['before'] + ($row['value'] * $row['currencyvalue']);
                }

                $after = $row['after'];
            }
            unset($row);

            return $balancelist;
        }
    }

    public function AddBalance($addbalance)
    {
        $args = array(
            'time' => isset($addbalance['time']) ? $addbalance['time'] : time(),
            SYSLOG::RES_USER => isset($addbalance['userid']) && !empty($addbalance['userid']) ? $addbalance['userid'] : Auth::GetCurrentUser(),
            'value' => str_replace(',', '.', round($addbalance['value'], 2)),
            'currency' => $addbalance['currency'],
            'currencyvalue' => isset($addbalance['currencyvalue']) ? $addbalance['currencyvalue'] : 1.0,
            'type' => isset($addbalance['type']) ? $addbalance['type'] : 0,
            SYSLOG::RES_TAX => isset($addbalance['taxid']) && !empty($addbalance['taxid']) ? $addbalance['taxid'] : null,
            SYSLOG::RES_CUST => $addbalance['customerid'],
            'comment' => $addbalance['comment'],
            SYSLOG::RES_DOC => isset($addbalance['docid']) && !empty($addbalance['docid']) ? $addbalance['docid'] : null,
            'itemid' => isset($addbalance['itemid']) ? $addbalance['itemid'] : 0,
            SYSLOG::RES_CASHIMPORT => !empty($addbalance['importid']) ? $addbalance['importid'] : null,
            SYSLOG::RES_CASHSOURCE => !empty($addbalance['sourceid']) ? $addbalance['sourceid'] : null,
        );
        $res = $this->db->Execute('INSERT INTO cash (time, userid, value, currency, currencyvalue, type, taxid,
			customerid, comment, docid, itemid, importid, sourceid)
			VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', array_values($args));

        if ($res) {
            $cashid = $this->db->GetLastInsertID('cash');
            if ($this->syslog) {
                unset($args[SYSLOG::RES_USER]);
                $args[SYSLOG::RES_CASH] = $cashid;
                $this->syslog->AddMessage(SYSLOG::RES_CASH, SYSLOG::OPER_ADD, $args);
            }
            $res = $cashid;
        }
        return $res;
    }

    public function DelBalance($id)
    {
        $row = $this->db->GetRow('SELECT cash.customerid, docid, value, itemid, documents.type AS doctype, importid,
						(CASE WHEN d2.id IS NULL THEN 0 ELSE 1 END) AS referenced
					FROM cash
					LEFT JOIN documents ON (docid = documents.id)
					LEFT JOIN documents d2 ON d2.reference = documents.id
					WHERE cash.id = ?', array($id));


        if ($row['doctype'] == DOC_CNOTE) {
            $previous_record = $this->db->GetRow(
                'SELECT c2.id, c2.docid, ic.count, c.itemid, c2.value
                FROM cash c
                JOIN documents d ON d.id = c.docid
                JOIN documents d2 ON d2.id = d.reference
                JOIN cash c2 ON c2.docid = d2.id AND c2.itemid = c.itemid
                JOIN invoicecontents ic ON ic.docid = d2.id AND ic.itemid = c2.itemid
                WHERE c.docid = ? AND c.itemid = ?',
                array($row['docid'], $row['itemid'])
            );
            if (!empty($previous_record) && -$previous_record['value'] != $row['value']) {
                $this->db->Execute(
                    'UPDATE cash SET value = ? WHERE id = ?',
                    array(str_replace(',', '.', -$previous_record['value']), $id)
                );
                $this->db->Execute(
                    'UPDATE invoicecontents SET value = ?, count = ? WHERE docid = ? AND itemid = ?',
                    array(
                        str_replace(',', '.', $previous_record['value']),
                        str_replace(',', '.', -$previous_record['count']),
                        $row['docid'],
                        $row['itemid']
                    )
                );
                if ($this->syslog) {
                    $args = array(
                        SYSLOG::RES_CASH => $id,
                        SYSLOG::RES_CUST => $row['customerid'],
                        'value' => str_replace(',', '.', -$previous_record['value']),
                    );
                    $this->syslog->AddMessage(SYSLOG::RES_CASH, SYSLOG::OPER_UPDATE, $args);
                    $args = array(
                        SYSLOG::RES_DOC => $row['docid'],
                        SYSLOG::RES_CUST => $row['customerid'],
                        'itemid' => $row['itemid'],
                        'value' => str_replace(',', '.', $previous_record['value']),
                        'count' => str_replace(',', '.', -$previous_record['count']),
                    );
                    $this->syslog->AddMessage(SYSLOG::RES_INVOICECONT, SYSLOG::OPER_UPDATE, $args);
                }
            }
        } else {
            $this->db->Execute('DELETE FROM cash WHERE id = ?', array($id));
            if ($this->syslog) {
                $args = array(
                    SYSLOG::RES_CASH => $id,
                    SYSLOG::RES_CUST => $row['customerid'],
                );
                $this->syslog->AddMessage(SYSLOG::RES_CASH, SYSLOG::OPER_DELETE, $args);
            }
        }
        if ($row['importid']) {
            if ($this->syslog) {
                $cashimport = $this->db->GetRow('SELECT customerid, sourceid, sourcefileid FROM cashimport WHERE id = ?', array($row['importid']));
                $args = array(
                    SYSLOG::RES_CASHIMPORT => $row['importid'],
                    SYSLOG::RES_CUST => $cashimport['customerid'],
                    SYSLOG::RES_CASHSOURCE => $cashimport['sourceid'],
                    SYSLOG::RES_SOURCEFILE => $cashimport['sourcefileid'],
                    'closed' => 0,
                );
                $this->syslog->AddMessage(SYSLOG::RES_CASHIMPORT, SYSLOG::OPER_UPDATE, $args);
            }
            $this->db->Execute('UPDATE cashimport SET closed = 0 WHERE id = ?', array($row['importid']));
        }

        if ($row['doctype'] == DOC_INVOICE || $row['doctype'] == DOC_INVOICE_PRO) {
            if (!$row['referenced']) {
                $this->InvoiceContentDelete($row['docid'], $row['itemid']);
            }
        } elseif ($row['doctype'] == DOC_RECEIPT) {
            $this->ReceiptContentDelete($row['docid'], $row['itemid']);
        } elseif ($row['doctype'] == DOC_DNOTE) {
            $this->DebitNoteContentDelete($row['docid'], $row['itemid']);
        }
    }

    public function PreserveProforma($docid)
    {
        $this->db->Execute('UPDATE documents SET closed = 1 WHERE id = ?', array($docid));

        if ($this->syslog) {
            $customerid = $this->db->GetOne('SELECT customerid FROM documents WHERE id = ?', array($docid));
            $args = array(
                SYSLOG::RES_DOC => $docid,
                SYSLOG::RES_CUST => $customerid,
                'closed' => 1,
            );
            $this->syslog->AddMessage(SYSLOG::RES_DOC, SYSLOG::OPER_UPDATE, $args);
        }

        $rows = $this->db->GetAll('SELECT cash.id, cash.customerid, cash.importid,
						i.sourceid, i.sourcefileid
					FROM cash
					LEFT JOIN cashimport i ON i.id = cash.importid
					WHERE cash.docid = ?', array($docid));

        if (!empty($rows)) {
            foreach ($rows as $row) {
                $this->db->Execute('DELETE FROM cash WHERE id = ?', array($row['id']));
                if ($this->syslog) {
                    $args = array(
                        SYSLOG::RES_CASH => $row['id'],
                        SYSLOG::RES_CUST => $row['customerid'],
                    );
                    $this->syslog->AddMessage(SYSLOG::RES_CASH, SYSLOG::OPER_DELETE, $args);
                }
                if (!empty($row['importid'])) {
                    if ($this->syslog) {
                        $args = array(
                            SYSLOG::RES_CASHIMPORT => $row['importid'],
                            SYSLOG::RES_CUST => $row['customerid'],
                            SYSLOG::RES_CASHSOURCE => $row['sourceid'],
                            SYSLOG::RES_SOURCEFILE => $row['sourcefileid'],
                            'closed' => 0,
                        );
                        $this->syslog->AddMessage(SYSLOG::RES_CASHIMPORT, SYSLOG::OPER_UPDATE, $args);
                    }
                    $this->db->Execute('UPDATE cashimport SET closed = 0 WHERE id = ?', array($row['importid']));
                }
            }
        }
    }

    public function GetPaymentList()
    {
        if ($paymentlist = $this->db->GetAll('SELECT id, name, creditor, value, period, at, description FROM payments ORDER BY name ASC')) {
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
        }

        $paymentlist['total'] = empty($paymentlist) ? 0 : count($paymentlist);

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
        return ($this->db->GetOne('SELECT id FROM payments WHERE id=?', array($id)) ? true : false);
    }

    public function PaymentAdd($paymentdata)
    {
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
                $args[SYSLOG::RES_PAYMENT] = $id;
                $this->syslog->AddMessage(SYSLOG::RES_PAYMENT, SYSLOG::OPER_ADD, $args);
            }
            return $id;
        } else {
            return false;
        }
    }

    public function PaymentDelete($id)
    {
        if ($this->syslog) {
            $args = array(SYSLOG::RES_PAYMENT => $id);
            $this->syslog->AddMessage(SYSLOG::RES_PAYMENT, SYSLOG::OPER_DELETE, $args);
        }
        return $this->db->Execute('DELETE FROM payments WHERE id=?', array($id));
    }

    public function PaymentUpdate($paymentdata)
    {
        $args = array(
            'name' => $paymentdata['name'],
            'creditor' => $paymentdata['creditor'],
            'description' => $paymentdata['description'],
            'value' => $paymentdata['value'],
            'period' => $paymentdata['period'],
            'at' => $paymentdata['at'],
            SYSLOG::RES_PAYMENT => $paymentdata['id'],
        );
        $res = $this->db->Execute('UPDATE payments SET name=?, creditor=?, description=?, value=?, period=?, at=? WHERE id=?', array_values($args));
        if ($res && $this->syslog) {
            $this->syslog->AddMessage(SYSLOG::RES_PAYMENT, SYSLOG::OPER_UPDATE, $args);
        }
        return $res;
    }

    public function GetHostingLimits($customerid)
    {
        global $ACCOUNTTYPES;

        $result = array(
            'alias_limit' => 0,
            'domain_limit' => 0,
            'count' => array(),
            'quota' => array(),
        );
        foreach ($ACCOUNTTYPES as $typeidx => $type) {
            $result['count'][$typeidx] = 0;
            $result['quota'][$typeidx] = 0;
        }

        if ($limits = $this->db->GetAll('SELECT alias_limit, domain_limit,
                sh_limit, www_limit, mail_limit, sql_limit, ftp_limit, quota_sh_limit,
                quota_www_limit, quota_mail_limit, quota_sql_limit, quota_ftp_limit,
                a.count
            FROM assignments a
            JOIN tariffs t ON t.id = a.tariffid
            WHERE customerid = ?
                AND t.type NOT IN (?, ?, ?) 
                AND commited = 1
                AND (a.dateto = 0 OR a.dateto > ?NOW?)
                AND a.datefrom < ?NOW?', array($customerid, SERVICE_INTERNET, SERVICE_PHONE, SERVICE_TV))) {
            foreach ($limits as $row) {
                foreach ($row as $idx => $val) {
                    if ($idx == 'alias_limit' || $idx == 'domain_limit') {
                        if ($val === null || $result[$idx] === null) {
                            $result[$idx] = null; // no limit
                        } else {
                            $result[$idx] += $val;
                        }
                    }
                }
                foreach ($ACCOUNTTYPES as $typeidx => $type) {
                    if ($row[$type['alias'] . '_limit'] === null || $result['count'][$typeidx] === null) {
                        $result['count'][$typeidx] = null;
                    } else {
                        $result['count'][$typeidx] += intval(floor($row[$type['alias'] . '_limit'] * $row['count']));
                    }
                    if ($row['quota_' . $type['alias'] . '_limit'] === null || $result['quota'][$typeidx] === null) {
                        $result['quota'][$typeidx] = null;
                    } else {
                        $result['quota'][$typeidx] += intval(floor($row['quota_' . $type['alias'] . '_limit'] * $row['count']));
                    }
                }
            }
        }

        return $result;
    }

    public function GetTaxes($from = null, $to = null)
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
            if ($m > 6) {
                $m -= 6;
            }
            return ($m - 1) * 100 + 1;
        } else if ($period == QUARTERLY) {
            if ($m > 9) {
                $m -= 9;
            } else if ($m > 6) {
                $m -= 6;
            } else if ($m > 3) {
                $m -= 3;
            }
            return ($m - 1) * 100 + 1;
        } else {
            return 1;
        }
    }

    public function PublishDocuments($ids)
    {
        if (!is_array($ids)) {
            $ids = array($ids);
        }
        $this->db->Execute('UPDATE documents SET published = 1 WHERE id IN (' . implode(',', $ids) . ')');
    }

    public function isDocumentPublished($id)
    {
        return $this->db->GetOne('SELECT published FROM documents WHERE id = ?', array($id)) == 1;
    }

    public function isDocumentReferenced($id)
    {
        return $this->db->GetOne('SELECT id FROM documents WHERE reference = ?', array($id)) > 0;
    }

    public function MarkDocumentsAsSent($ids)
    {
        if (!is_array($ids)) {
            $ids = array($ids);
        }
        $this->db->Execute('UPDATE documents SET senddate = ?NOW? WHERE id IN (' . implode(',', $ids) . ')');
    }

    public function GetReceiptList(array $params)
    {
        extract($params);
        foreach (array('search', 'cat') as $var) {
            if (!isset($$var)) {
                $$var = null;
            }
        }
        if (!isset($order)) {
            $order = '';
        }
        foreach (array('from', 'to', 'advances') as $var) {
            if (!isset($$var)) {
                $$var = 0;
            }
        }
        if (!isset($count)) {
            $count = false;
        }

        list($order,$direction) = sscanf($order, '%[^,],%s');

        ($direction != 'desc') ? $direction = 'asc' : $direction = 'desc';

        switch ($order) {
            case 'number':
                $sqlord = " ORDER BY documents.number $direction";
                break;
            case 'name':
                $sqlord = " ORDER BY documents.name $direction, documents.cdate";
                break;
            case 'user':
                $sqlord = " ORDER BY vusers.rname $direction, documents.cdate";
                break;
            case 'cdate':
            default:
                $sqlord = " ORDER BY documents.cdate $direction, number";
                break;
        }

        $where = '';
        $having = '';

        if ($search && $cat) {
            switch ($cat) {
                case 'value':
                    $having = ' HAVING SUM(value) = ' . $this->db->Escape(str_replace(',', '.', $search));
                    break;
                case 'number':
                    $where = ' AND number = '.intval($search);
                    break;
                case 'ten':
                    $where = ' AND ten = ' . $this->db->Escape($search);
                    break;
                case 'customerid':
                    $where = ' AND customerid = '.intval($search);
                    break;
                case 'name':
                    $where = ' AND documents.name ?LIKE? ' . $this->db->Escape('%' . $search . '%');
                    break;
                case 'address':
                    $where = ' AND address ?LIKE? ' . $this->db->Escape('%' . $search . '%');
                    break;
            }
        }

        if ($from) {
            $where .= ' AND cdate >= '.intval($from);
        }
        if ($to) {
            $where .= ' AND cdate <= '.intval($to);
        }

        if ($advances) {
            $where = ' AND closed = 0';
        }

        if ($count) {
            $summary = $this->db->GetRow(
                'SELECT COUNT(documents.id) AS total,
					SUM(d.income) AS totalincome,
					SUM(d.expense) AS totalexpense
				FROM documents
				LEFT JOIN numberplans ON (numberplanid = numberplans.id)
				LEFT JOIN vusers ON (userid = vusers.id)
				JOIN (
					SELECT documents.id AS id,
						(CASE WHEN SUM(value * documents.currencyvalue) > 0 THEN SUM(value * documents.currencyvalue) ELSE 0 END) AS income,
						(CASE WHEN SUM(value * documents.currencyvalue) < 0 THEN -SUM(value * documents.currencyvalue) ELSE 0 END) AS expense 
					FROM documents
					JOIN receiptcontents ON documents.id = docid AND type = ?
					WHERE regid = ?
					GROUP BY documents.id '
                    . $having . '
				) d ON d.id = documents.id
				WHERE documents.type = ?'
                .$where,
                array(DOC_RECEIPT, $registry, DOC_RECEIPT)
            );
            if (empty($summary)) {
                return array('total' => 0, 'totalincome' => 0, 'totalexpense' => 0);
            }
            return $summary;
        }

        if ($list = $this->db->GetAll(
            'SELECT documents.id AS id, SUM(value) AS value, currency, currencyvalue, number, cdate, customerid,
			documents.name AS customer, address, zip, city, numberplans.template, extnumber, closed,
			COUNT(*) AS posnumber, vusers.rname AS user
			FROM documents
			LEFT JOIN numberplans ON (numberplanid = numberplans.id)
			LEFT JOIN vusers ON (userid = vusers.id)
			LEFT JOIN receiptcontents ON (documents.id = docid AND type = ?)
			WHERE regid = ?'
            .$where
            .' GROUP BY documents.id, currency, currencyvalue, number, cdate, customerid, documents.name, address, zip, city, numberplans.template,
            vusers.rname, extnumber, closed '
            .$having
            .($sqlord != '' ? $sqlord : '')
            . (isset($limit) ? ' LIMIT ' . $limit : '')
            . (isset($offset) ? ' OFFSET ' . $offset : ''),
            array(DOC_RECEIPT, $registry)
        )) {
            foreach ($list as $idx => &$row) {
                $row['number'] = docnumber(array(
                    'number' => $row['number'],
                    'template' => $row['template'],
                    'cdate' => $row['cdate'],
                    'ext_num' => $row['extnumber'],
                    'customerid' => $row['customerid'],
                ));
                $row['customer'] = $row['customer'].' '.$row['address'].' '.$row['zip'].' '.$row['city'];

                $row['positions'] = $this->db->GetAll(
                    'SELECT * FROM receiptcontents WHERE docid = ? ORDER BY itemid',
                    array($row['id'])
                );
            }
            unset($row);

            $list['order'] = $order;
            $list['direction'] = $direction;

            return $list;
        }

        return null;
    }

    public function AddReceipt(array $receipt)
    {
        $this->db->BeginTrans();
        $this->db->LockTables(array('documents', 'numberplans'));

        $SYSLOG = SYSLOG::getInstance();
        $document_manager = new LMSDocumentManager($this->db, $this->auth, $this->cache, $this->syslog);
        $error = array();

        $customer = isset($receipt['customer']) ? $receipt['customer'] : null;
        $contents = $receipt['contents'];

        if (!$receipt['number']) {
            $receipt['number'] = $document_manager->GetNewDocumentNumber(array(
                'doctype' => DOC_RECEIPT,
                'planid' => $receipt['numberplanid'],
                'cdate' => $receipt['cdate'],
                'customerid' => $customer ? $customer['id'] : null,
            ));
        } else {
            if (!preg_match('/^[0-9]+$/', $receipt['number'])) {
                $error['number'] = trans('Receipt number must be integer!');
            } elseif ($document_manager->DocumentExists(array(
                'number' => $receipt['number'],
                'doctype' => DOC_RECEIPT,
                'planid' => $receipt['numberplanid'],
                'cdate' => $receipt['cdate'],
                'customerid' => $customer ? $customer['id'] : null,
            ))) {
                $error['number'] = trans('Receipt number $a already exists!', $receipt['number']);
            }

            if ($error) {
                $receipt['number'] = $document_manager->GetNewDocumentNumber(array(
                    'doctype' => DOC_RECEIPT,
                    'planid' => $receipt['numberplanid'],
                    'cdate' => $receipt['cdate'],
                    'customerid' => $customer ? $customer['id'] : null,
                ));
            }
        }

        $division_manager = new LMSDivisionManager($this->db, $this->auth, $this->cache, $this->syslog);
        $division = $division_manager->GetDivision($receipt['customer']['divisionid']);

        $fullnumber = docnumber(array(
            'number' => $receipt['number'],
            'template' => $this->db->GetOne('SELECT template FROM numberplans WHERE id = ?', array($receipt['numberplanid'])),
            'cdate' => $receipt['cdate'],
            'customerid' => $customer ? $customer['id'] : null,
        ));

        $args = array(
            'type' => DOC_RECEIPT,
            'number' => $receipt['number'],
            'extnumber' => isset($receipt['extnumber']) ? $receipt['extnumber'] : '',
            SYSLOG::RES_NUMPLAN => $receipt['numberplanid'],
            'cdate' => $receipt['cdate'],
            SYSLOG::RES_CUST => $customer ? $customer['id'] : null,
            SYSLOG::RES_USER => Auth::GetCurrentUser(),
            'name' => $customer ? $customer['customername'] :
                ($receipt['o_type'] == 'advance' ? $receipt['adv_name'] : $receipt['other_name']),
            'address' => $customer ? (($customer['postoffice'] && $customer['postoffice'] != $customer['city'] && $customer['street']
                    ? $customer['city'] . ', ' : '') . $customer['address']) : '',
            'zip' => $customer ? $customer['zip'] : '',
            'city' => $customer ? ($customer['postoffice'] ? $customer['postoffice'] : $customer['city']) : '',
            SYSLOG::RES_COUNTRY => $receipt['customer']['countryid'] ? $receipt['customer']['countryid'] : null,
            SYSLOG::RES_DIV => $receipt['customer']['divisionid'],
            'div_name' => ($division['name'] ? $division['name'] : ''),
            'div_shortname' => ($division['shortname'] ? $division['shortname'] : ''),
            'div_address' => ($division['address'] ? $division['address'] : ''),
            'div_city' => ($division['city'] ? $division['city'] : ''),
            'div_zip' => ($division['zip'] ? $division['zip'] : ''),
            'div_' . SYSLOG::getResourceKey(SYSLOG::RES_COUNTRY) => ($division['countryid'] ? $division['countryid'] : null),
            'div_ten' => ($division['ten'] ? $division['ten'] : ''),
            'div_regon' => ($division['regon'] ? $division['regon'] : ''),
            'div_bank' => $division['bank'] ?: null,
            'div_account' => ($division['account'] ? $division['account'] : ''),
            'div_inv_header' => ($division['inv_header'] ? $division['inv_header'] : ''),
            'div_inv_footer' => ($division['inv_footer'] ? $division['inv_footer'] : ''),
            'div_inv_author' => ($division['inv_author'] ? $division['inv_author'] : ''),
            'div_inv_cplace' => ($division['inv_cplace'] ? $division['inv_cplace'] : ''),
            'closed' => $customer || $receipt['o_type'] != 'advance' ? 1 : 0,
            'fullnumber' => $fullnumber,
            'currency' => isset($receipt['currency']) ? $receipt['currency'] : Localisation::getCurrentCurrency(),
            'currencyvalue' => isset($receipt['currencyvalue']) ? $receipt['currencyvalue'] : 1.0,
        );
        $this->db->Execute('INSERT INTO documents (type, number, extnumber, numberplanid, cdate, customerid, userid,
			name, address, zip, city, countryid, 
			divisionid, div_name, div_shortname, div_address, div_city, div_zip, div_countryid, div_ten, div_regon,
			div_bank, div_account, div_inv_header, div_inv_footer, div_inv_author, div_inv_cplace,
			closed, fullnumber, currency, currencyvalue)
			VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', array_values($args));
        $this->db->UnLockTables();

        $rid = $this->db->GetLastInsertId('documents');

        if ($SYSLOG) {
            $args[SYSLOG::RES_DOC] = $rid;
            unset($args[SYSLOG::RES_USER]);
            $SYSLOG->AddMessage(SYSLOG::RES_DOC, SYSLOG::OPER_ADD, $args);
        }

        $iid = 0;
        foreach ($contents as $item) {
            $iid++;

            if ($receipt['type'] == 'in') {
                $value = str_replace(',', '.', $item['value']);
            } else {
                $value = str_replace(',', '.', $item['value'] * -1);
            }

            $args = array(
                SYSLOG::RES_DOC => $rid,
                'itemid' =>  $iid,
                'value' => $value,
                'description' => $item['description'],
                SYSLOG::RES_CASHREG => $receipt['regid'],
            );
            $this->db->Execute('INSERT INTO receiptcontents (docid, itemid, value, description, regid)
					VALUES(?, ?, ?, ?, ?)', array_values($args));
            if ($SYSLOG) {
                $SYSLOG->AddMessage(SYSLOG::RES_RECEIPTCONT, SYSLOG::OPER_ADD, $args);
            }

            $args = array(
                'time' => $receipt['cdate'],
                'type' => 1,
                SYSLOG::RES_DOC => $rid,
                'itemid' => $iid,
                'value' => $value,
                'currency' => isset($receipt['currency']) ? $receipt['currency'] : Localisation::getCurrentCurrency(),
                'currencyvalue' => isset($receipt['currencyvalue']) ? $receipt['currencyvalue'] : 1.0,
                'comment' => $item['description'],
                SYSLOG::RES_USER => Auth::GetCurrentUser(),
                SYSLOG::RES_CUST => $customer ? $customer['id'] : null,
            );
            $this->db->Execute('INSERT INTO cash (time, type, docid, itemid, value, currency, currencyvalue, comment, userid, customerid)
						VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', array_values($args));
            if ($SYSLOG) {
                $args[SYSLOG::RES_CASH] = $this->db->GetLastInsertID('cash');
                unset($args[SYSLOG::RES_USER]);
                $SYSLOG->AddMessage(SYSLOG::RES_CASH, SYSLOG::OPER_ADD, $args);
            }

            if (isset($item['docid'])) {
                $this->db->Execute('UPDATE documents SET closed=1 WHERE id=?', array($item['docid']));
                if ($SYSLOG) {
                    list ($customerid, $numplanid) = array_values($this->db->GetRow('SELECT customerid, numberplanid
							FROM documents WHERE id = ?', array($item['docid'])));
                    $args = array(
                        SYSLOG::RES_DOC => $item['docid'],
                        SYSLOG::RES_NUMPLAN => $numplanid,
                        SYSLOG::RES_CUST => $customerid,
                        'closed' => 1,
                    );
                    $SYSLOG->AddMessage(SYSLOG::RES_DOC, SYSLOG::OPER_UPDATE, $args);
                }
            }
            if (isset($item['references'])) {
                foreach ($item['references'] as $ref) {
                    $this->db->Execute('UPDATE documents SET closed=1 WHERE id=?', array($ref));
                    if ($SYSLOG) {
                        list ($customerid, $numplanid) = array_values($this->db->GetRow('SELECT customerid, numberplanid
								FROM documents WHERE id = ?', array($ref)));
                        $args = array(
                            SYSLOG::RES_DOC => $ref,
                            SYSLOG::RES_NUMPLAN => $numplanid,
                            SYSLOG::RES_CUST => $customerid,
                            'closed' => 1,
                        );
                        $SYSLOG->AddMessage(SYSLOG::RES_DOC, SYSLOG::OPER_UPDATE, $args);
                    }
                }
            }
        }

        $this->db->CommitTrans();

        return empty($error) ? $rid : $error;
    }

    public function GetCashRegistries($cid = null)
    {
        $userid = Auth::GetCurrentUser();

        if (empty($cid)) {
            $select = '';
            $join = '';
            $where = '';
        } else {
            $divisionid = $this->db->GetOne('SELECT divisionid FROM customers WHERE id = ?', array($cid));
            $select = ', np.isdefault';
            $join = ' JOIN numberplanassignments npa ON npa.planid = in_numberplanid
				JOIN numberplans np ON np.id = in_numberplanid ';
            $where = ' AND npa.divisionid = ' . intval($divisionid);
        }

        $result = $this->db->GetAllByKey('SELECT r.id, name,
				in_numberplanid, out_numberplanid' . $select . '
			FROM cashregs r
			JOIN cashrights cr ON regid = r.id
			' . $join . '
			WHERE rights > 1 AND userid = ? ' . $where . '
			ORDER BY name', 'id', array($userid));
        return $result;
    }

    public function GetOpenedLiabilities($customerid)
    {
        static $document_descriptions = array(
            DOC_INVOICE => 'Invoice No. $a',
            DOC_CNOTE => 'Credit Note No. $a',
            DOC_DNOTE => 'Debit Note No. $a',
        );

        $customer_manager = new LMSCustomerManager($this->db, $this->auth, $this->cache, $this->syslog);

        $result = array();

        $liabilities = $this->db->GetAll(
            '(
				SELECT NULL AS docid, comment, time AS cdate, NULL AS doctype, NULL AS number, NULL AS template,
					0 AS reference, value
				FROM cash
				WHERE docid IS NULL AND customerid = ? AND cash.type = 0
			) UNION (
				SELECT cash.docid, NULL AS comment, d.cdate, d.type AS doctype, d.number, np.template,
					(CASE WHEN dr.id IS NOT NULL THEN 1 ELSE 0 END) AS reference,
					SUM(cash.value + (CASE WHEN cashr.value IS NULL THEN 0 ELSE cashr.value END)) AS value
				FROM cash
				JOIN documents d ON d.id = cash.docid
				LEFT JOIN numberplans np ON np.id = d.numberplanid
				LEFT JOIN documents dr ON dr.reference = d.id
				LEFT JOIN cash cashr ON cashr.docid = dr.id
				WHERE cash.customerid = ? AND d.reference IS NULL AND d.type IN (?, ?)
				GROUP BY cash.docid, d.cdate, d.type, d.number, np.template, dr.id
			) ORDER BY cdate DESC',
            array($customerid, $customerid, DOC_INVOICE, DOC_DNOTE)
        );

        if (empty($liabilities)) {
            return $result;
        }

        $balance = $customer_manager->GetCustomerBalance($customerid, time());

        foreach ($liabilities as $liability) {
            if (!empty($liability['docid'])) {
                $liability['comment'] = trans($document_descriptions[$liability['doctype']], docnumber(array(
                    'number' => $liability['number'],
                    'template' => $liability['template'],
                    'cdate' => $liability['cdate'],
                    'customerid' => $customerid,
                )));
            }
            $liability['references'] = array();

            // get cnotes values if those values decreases invoice value
            if ($cnotes = $this->db->GetAll('SELECT d.id, cdate, number, np.template
					FROM documents d
					LEFT JOIN numberplans np ON np.id = d.numberplanid
					WHERE d.reference = ?', array($liability['docid']))) {
                $liability['comment'] .= ' (';
                foreach ($cnotes as $cidx => $cnote) {
                    $liability['comment'] .= docnumber(array(
                        'number' => $cnote['number'],
                        'template' => $cnote['template'],
                        'cdate' => $cnote['cdate'],
                        'customerid' => $customerid,
                    ));
                    $liability['references'][] = $cnote['id'];
                    if ($cidx < count($cnotes)-1) {
                        $liability['comment'] .= ', ';
                    }
                }
                $liability['comment'] .= ')';
            }

            if ($balance - $liability['value'] <= 0) {
                $result[] = $liability;
            } elseif ($balance < 0) {
                $liability['value'] = $balance;
                $result[] = $liability;
                break;
            }
            $balance -= $liability['value'];
            $balance = round($balance, 2);
            if ($balance >= 0) {
                break;
            }
        }

        return array_reverse($result);
    }

    public function GetPromotions()
    {
        $promotions = $this->db->GetAllByKey('SELECT id, name, description,
				(CASE WHEN datefrom < ?NOW? AND (dateto = 0 OR dateto > ?NOW?) THEN 1 ELSE 0 END) AS valid
			FROM promotions WHERE disabled <> 1 ORDER BY name', 'id');

        if (empty($promotions)) {
            return array();
        }

        foreach ($promotions as $promotionid => &$promotion) {
            $promotion['schemas'] = array();
        }
        unset($promotion);

        $promotion_schemas = $this->db->GetAll('SELECT p.id AS promotionid, p.name AS promotion, s.name,
			s.id, s.data AS sdata, s.description,
			(SELECT ' . $this->db->GroupConcat('tariffid', ',') . '
				FROM promotionassignments WHERE promotionschemaid = s.id
			) AS tariffs
			FROM promotions p
			JOIN promotionschemas s ON (p.id = s.promotionid)
			WHERE p.disabled <> 1 AND s.disabled <> 1
				AND EXISTS (SELECT 1 FROM promotionassignments
				WHERE promotionschemaid = s.id LIMIT 1)
			ORDER BY p.name, s.name');

        if (empty($promotion_schemas)) {
            return array();
        } else {
            foreach ($promotion_schemas as $promotion_schema) {
                $period_labels = array(trans('Activation'));
                if (empty($promotion_schema['sdata'])) {
                    $period_labels[] = trans('Months $a-', 1);
                } else {
                    $periods = explode(';', $promotion_schema['sdata']);
                    $month = 1;
                    foreach ($periods as $period) {
                        $period_labels[] = ($period == 1 ? trans('Month $a', $month)
                        : trans('Months $a-$b', $month, $month + $period - 1));
                        $month += $period;
                    }
                    $period_labels[] = trans('Months $a-', $month);
                }

                $promotions[$promotion_schema['promotionid']]['schemas'][$promotion_schema['id']] =
                array(
                    'id' => $promotion_schema['id'],
                    'name' => $promotion_schema['name'],
                    'description' => $promotion_schema['description'],
                    'tariffs' => $promotion_schema['tariffs'],
                    'period_labels' => $period_labels,
                    'items' => array(),
                );
            }
        }

        $promotion_schema_assignments = $this->db->GetAll('SELECT
				p.id AS promotion_id, ps.id AS schema_id, pa.id AS assignment_id,
				t.name as tariff_name, pa.backwardperiod, pa.optional, pa.data AS adata,
				(CASE WHEN label IS NULL THEN ' . $this->db->Concat("'unlabeled_'", 'pa.id') . ' ELSE label END) AS label,
				t.id as tariffid, t.type AS tarifftype, t.value, t.authtype, t.currency
			FROM promotions p
				LEFT JOIN promotionschemas ps ON p.id = ps.promotionid
				LEFT JOIN promotionassignments pa ON ps.id = pa.promotionschemaid
				LEFT JOIN tariffs t ON pa.tariffid = t.id
			ORDER BY pa.orderid');

        $userid = Auth::GetCurrentUser();

        if (empty($promotion_schema_assignments)) {
            return array();
        } else {
            $single_labels = $this->db->GetAll('SELECT promotionschemaid AS schemaid,
					label, COUNT(*) AS cnt
				FROM promotionassignments
				WHERE label IS NOT NULL
				GROUP BY promotionschemaid, label');
            if (empty($single_labels)) {
                $single_labels = array();
            }
            $selection_labels = $this->db->GetAll('SELECT id, promotionschemaid AS schemaid,
					(CASE WHEN label IS NULL THEN ' . $this->db->Concat("'unlabeled_'", 'id') . ' ELSE label END) AS label,
					1 AS cnt
				FROM promotionassignments
				WHERE label IS NULL');
            if (empty($selection_labels)) {
                $selection_labels = array();
            }
            $labels = array_merge($single_labels, $selection_labels);

            $promotion_schema_selections = array();
            if (!empty($labels)) {
                foreach ($labels as &$label) {
                    if (preg_match('/^unlabeled_(?<assignmentid>[0-9]+)$/', $label['label'], $m)) {
                        $label['label'] = trans('<!tariffselection>unlabeled_$a', $m['assignmentid']);
                    }
                    $promotion_schema_selections[$label['schemaid']][$label['label']] = $label['cnt'];
                }
                unset($label);
            }

            foreach ($promotion_schema_assignments as $assign) {
                $pid = $assign['promotion_id'];

                if (empty($promotions[$pid]['valid'])) {
                    continue;
                }

                $sid = $assign['schema_id'];

                if (!isset($promotions[$pid]['schemas'][$sid])) {
                    continue;
                }

                $period_labels = $promotions[$pid]['schemas'][$sid]['period_labels'];
                $periods = array();
                $period_values = array();
                $adata = explode(';', $assign['adata']);
                foreach ($period_labels as $period_label_idx => $period_label) {
                    if (isset($adata[$period_label_idx])) {
                        $props = explode(':', $adata[$period_label_idx]);
                        if ($props[0] == 'NULL') {
                            $period = array(
                                'label' => $period_label,
                                'value' => '',
                            );
                            $period_values[] = '-';
                        } else {
                            $period = array(
                                'label' => $period_label,
                                'value' => $props[0],
                            );
                            $period_values[] = moneyf($props[0], $assign['currency']);
                        }

                        if (count($props) > 2 && !empty($props[2])) {
                            $users = explode(',', $props[2]);
                            $period['modifiable'] = in_array($userid, $users);
                        } else {
                            $period['modifiable'] = false;
                        }
                        if (ConfigHelper::checkConfig('phpui.promotion_allow_modify_values_for_privileged_user')
                            && ConfigHelper::checkPrivilege('promotion_management')) {
                            $period['modifiable'] = $props[0] != 'NULL';
                        }
                        $periods[] = $period;
                    }
                }

                $promotion_schema_item = array(
                    'assignmentid' => $assign['assignment_id'],
                    'tariffid' => $assign['tariffid'],
                    'tariff'   => $assign['tariff_name'],
                    'value'    => $assign['value'],
                    'currency' => $assign['currency'],
                    'backwardperiod' => $assign['backwardperiod'],
                    'optional' => $assign['optional'],
                    'authtype' => $assign['authtype'],
                    'type' => $assign['tarifftype'],
                    'periods' => $periods,
                    'periodvalues' => $period_values,
                );

                if (preg_match('/^unlabeled_(?<assignmentid>[0-9]+)$/', $assign['label'], $m)) {
                    $label = trans('<!tariffselection>unlabeled_$a', $m['assignmentid']);
                } else {
                    $label = $assign['label'];
                }

                if ($promotion_schema_selections[$sid][$label] > 1) {
                    if (!isset($promotions[$pid]['schemas'][$sid]['items'][$label]['selection'])) {
                        $promotions[$pid]['schemas'][$sid]['items'][$label]['selection'] = array(
                            'items' => array(),
                        );
                    }
                    $promotions[$pid]['schemas'][$sid]['items'][$label]['selection']['required'] =
                        empty($assign['optional']);

                    $promotions[$pid]['schemas'][$sid]['items'][$label]['selection']['items'][] =
                        $promotion_schema_item;
                } else {
                    $promotions[$pid]['schemas'][$sid]['items'][$label]['single'] = $promotion_schema_item;
                }
            }
        }

        return $promotions;
    }

    public function AggregateDocuments($list)
    {
        // aggreate trade documents to single row
        $docidx = null;
        $trade_doc_types = array_flip(array(DOC_INVOICE, DOC_CNOTE, DOC_DNOTE, DOC_INVOICE_PRO));
        $comments = array(
            DOC_INVOICE => 'Invoice No. $a',
            DOC_CNOTE => 'Credit Note No. $a',
            DOC_DNOTE => 'Debit Note No. $a',
            DOC_INVOICE_PRO => 'Pro-forma Invoice No. $a',
        );
        $list2 = array();

        foreach ($list['list'] as $idx => &$row) {
            if (!empty($row['docid']) && isset($trade_doc_types[$row['doctype']])) {
                if (!isset($docid) || $row['docid'] != $docid) {
                    $docid = $row['docid'];
                    $list2[] = $list['list'][$idx];
                    $docidx = count($list2) - 1;
                } else {
                    $list2[$docidx]['value'] += $row['value'];
                }
            } else {
                unset($docid);
                $list2[] = $list['list'][$idx];
            }
        }
        unset($row);

        foreach ($list2 as &$row) {
            if (!empty($row['docid']) && isset($trade_doc_types[$row['doctype']])) {
                $row['comment'] = trans($comments[$row['doctype']], docnumber(array(
                'number' => $row['number'],
                'template' => $row['template'],
                'cdate' => $row['cdate'],
                'customerid' => $list['customerid'],
                )));
            }
        }
        unset($row);

        $list['list'] = $list2;

        return $list;
    }

    public function GetDocumentsForBalanceRecords($ids, $doctypes)
    {
        return $this->db->GetCol(
            "SELECT DISTINCT docid FROM cash c
			JOIN documents d ON d.id = c.docid
			WHERE d.type IN ?
				AND c.id IN (" . implode(',', $ids) . ")",
            array($doctypes)
        );
    }

    public function GetDocumentLastReference($docid)
    {
        while ($refdocid = $this->db->GetOne("SELECT id FROM documents WHERE reference = ?", array($docid))) {
            $docid = $refdocid;
        }
        return $docid;
    }

    public function CheckNodeTariffRestrictions($aid, $nodes, $datefrom, $dateto)
    {
        $nodeassigns = $this->db->GetCol(
            'SELECT DISTINCT na.nodeid FROM nodeassignments na
            JOIN nodes n ON n.id = na.nodeid
            JOIN netdevices nd ON nd.id = n.netdev AND n.ownerid IS NULL
            JOIN assignments a ON a.id = na.assignmentid
            WHERE (n.ownerid = a.customerid OR nd.ownerid = a.customerid) AND na.nodeid IN ('
            . implode(', ', $nodes) . ')' . (empty($aid) ? '' : ' AND na.assignmentid <> ' . intval($aid))
            . ' AND ((a.datefrom <= ? AND (a.dateto = 0 OR ? = 0 OR a.dateto >= ?))
                    OR ((a.datefrom <= ? OR ? = 0) AND (a.dateto = 0 OR a.dateto >= ?)))',
            array($datefrom, $dateto, $datefrom, $dateto, $dateto, $dateto)
        );
        $result = array();
        if (!empty($nodeassigns)) {
            foreach ($nodes as $idx => $nodeid) {
                if (in_array($nodeid, $nodeassigns)) {
                    $result[$idx] = $nodeid;
                }
            }
        }
        return $result;
    }

    public function getCurrencyValue($currency, $date = null)
    {
        if ($currency == Localisation::getCurrentCurrency()) {
            return 1.0;
        }
        if (function_exists('get_currency_value')) {
            if (!isset($GLOBALS['CURRENCIES'][$currency])) {
                return null;
            }
            if (empty($date)) {
                $date = mktime(12, 0, 0);
            } elseif (strpos($date, '/') !== false) {
                list ($year, $month, $day) = explode('/', $date);
                $date = mktime(12, 0, 0, $month, $day, $year);
            } elseif ($date > time()) {
                $date = mktime(12, 0, 0);
            } else {
                $date = mktime(
                    12,
                    0,
                    0,
                    date('n', $date),
                    date('j', $date),
                    date('Y', $date)
                );
            }
            if (!isset($this->currency_values[$currency][$date])) {
                $this->currency_values[$currency][$date] = str_replace(',', '.', get_currency_value($currency, $date));
            }
            return $this->currency_values[$currency][$date];
        } else {
            return null;
        }
    }

    public function CopyCashRegistryPermissions($src_userid, $dst_userid)
    {
        $this->db->Execute('DELETE FROM cashrights WHERE userid = ?', array($dst_userid));
        return $this->db->Execute(
            'INSERT INTO cashrights (userid, regid, rights)
            (SELECT ?, regid, rights FROM cashrights WHERE userid = ?)',
            array($dst_userid, $src_userid)
        );
    }

    public function CopyPromotionTariffPermissions($src_userid, $dst_userid)
    {
        $assigns = $this->db->GetAll('SELECT id, data, 0 AS changed FROM promotionassignments');
        if (empty($assigns)) {
            return 0;
        }

        foreach ($assigns as &$assign) {
            $assign['changed'] = intval($assign['changed']);
            $periods = explode(';', $assign['data']);
            foreach ($periods as &$period) {
                $cols = explode(':', $period);
                if (count($cols) == 3 && !empty($cols[2])) {
                    $users = array_flip(explode(',', $cols[2]));
                    if (isset($users[$src_userid])) {
                        if (!isset($users[$dst_userid])) {
                            $users[$dst_userid] = count($users);
                            $assign['changed'] = 1;
                        }
                    } elseif (isset($users[$dst_userid])) {
                        unset($users[$dst_userid]);
                        $assign['changed'] = 1;
                    }
                    if ($assign['changed']) {
                        $cols[2] = implode(',', array_keys($users));
                        $period = implode(':', $cols);
                    }
                }
            }
            unset($period);
            if ($assign['changed']) {
                $assign['data'] = implode(';', $periods);
            }
        }
        unset($assign);

        $change_count = 0;
        foreach ($assigns as $assign) {
            if ($assign['changed']) {
                if ($this->db->Execute(
                    'UPDATE promotionassignments SET data = ? WHERE id = ?',
                    array($assign['data'], $assign['id'])
                )) {
                    $change_count++;
                }
            }
        }

        return $change_count;
    }

    public function transformProformaInvoice($docid)
    {
        static $document_manager = null;
        static $location_manager = null;
        static $currencyvalues = array();
        static $numplans = array();

        if (!isset($document_manager)) {
            $document_manager = new LMSDocumentManager($this->db, $this->auth, $this->cache, $this->syslog);
        }

        if (!isset($location_manager)) {
            $location_manager = new LMSLocationManager($this->db, $this->auth, $this->cache, $this->syslog);
        }

        $proforma = $this->GetInvoiceContent($docid);

        if (!isset($currencyvalues[$proforma['currency']])) {
            $currencyvalues[$proforma['currency']] = $this->getCurrencyValue($proforma['currency']);
            if (!isset($currencyvalues[$proforma['currency']])) {
                return 'Unable to determine currency value for new document and currency ' . $proforma['currency'] . '.';
            }
        }

        if (!isset($numplans[$proforma['divisionid']])) {
            $numplan = $this->db->GetOne(
                'SELECT n.id
                FROM numberplans n
                LEFT JOIN numberplanassignments a ON (a.planid = n.id)
                WHERE isdefault = 1 AND doctype = ? AND a.divisionid = ?',
                array(DOC_INVOICE, $proforma['divisionid'])
            );
            $numplans[$proforma['divisionid']] = $numplan ?: null;
        }
        $numplanid = $numplans[$proforma['divisionid']];

        $this->db->BeginTrans();
        $tables = array('documents', 'cash', 'invoicecontents', 'numberplans', 'divisions', 'vdivisions',
            'customerview', 'customercontacts', 'netdevices', 'nodes',
            'logtransactions', 'logmessages', 'logmessagekeys', 'logmessagedata');
        if (ConfigHelper::getConfig('database.type') == 'postgres') {
            $tables = array_merge($tables, array('customers', 'customer_addresses'));
        } else {
            $tables = array_merge($tables, array('customers cv', 'customer_addresses ca'));
        }
        $this->db->LockTables($tables);

        $currtime = time();

        $comment = ConfigHelper::getConfig('invoices.proforma_conversion_comment_format', '%comment');
        $comment = str_replace(
            array(
                '%comment',
                '%number',
            ),
            array(
                empty($proforma['comment']) ? '' : $proforma['comment'],
                $proforma['fullnumber'],
            ),
            $comment
        );

        $args = array(
            'cdate' => $currtime,
            'sdate' => $currtime,
            'paytime' => $proforma['paytime'],
            'paytype' => $proforma['paytype'],
            'splitpayment' => empty($proforma['splitpayment']) ? 0 : 1,
            SYSLOG::RES_CUST => $proforma['customerid'],
            'name' => $proforma['name'],
            'address' => $proforma['address'],
            'ten' => $proforma ['ten'],
            'ssn' => $proforma['ssn'],
            'zip' => $proforma['zip'],
            'city' => $proforma['city'],
            SYSLOG::RES_COUNTRY => $proforma['countryid'],
            SYSLOG::RES_DIV => $proforma['divisionid'],
            'div_name' => $proforma['div_name'] ?: '',
            'div_shortname' => $proforma['div_shortname'] ?: '',
            'div_address' => $proforma['div_address'] ?: '',
            'div_city' => $proforma['div_city'] ?: '',
            'div_zip' => $proforma['div_zip'] ?: '',
            'div_' . SYSLOG::getResourceKey(SYSLOG::RES_COUNTRY) => $proforma['div_countryid'] ?: null,
            'div_ten'=> $proforma['div_ten'] ?: '',
            'div_regon' => $proforma['div_regon'] ?: '',
            'div_bank' => $proforma['div_bank'] ?: null,
            'div_account' => $proforma['div_account'] ?: '',
            'div_inv_header' => $proforma['div_inv_header'] ?: '',
            'div_inv_footer' => $proforma['div_inv_footer'] ?: '',
            'div_inv_author' => $proforma['div_inv_author'] ?: '',
            'div_inv_cplace' => $proforma['div_inv_cplace'] ?: '',
            'comment' => strlen($comment) ? $comment : null,
            'currency' => $proforma['currency'],
            'currencyvalue' => $currencyvalues[$proforma['currencyvalue']],
            'memo' => $proforma['memo'],
            'type' => DOC_INVOICE,
            'number' => $document_manager->GetNewDocumentNumber(array(
                'doctype' => DOC_INVOICE,
                'planid' => $numplanid,
                'cdate' => $currtime,
                'customerid' => $proforma['customerid'],
            )),
        );
        $args['fullnumber'] = docnumber(array(
            'number' => $args['number'],
            'template' => $this->db->GetOne('SELECT template FROM numberplans WHERE id = ?', array($numplanid)),
            'cdate' => $currtime,
            'customerid' => $proforma['customerid'],
        ));
        $args[SYSLOG::RES_NUMPLAN] = $numplanid;

        $args['recipient_address_id'] = empty($proforma['recipient_address_id']) ? null :
            $location_manager->CopyAddress($proforma['recipient_address_id']);

        $this->db->Execute(
            'INSERT INTO documents (cdate, sdate, paytime, paytype, splitpayment, customerid,
                name, address, ten, ssn, zip, city, countryid, divisionid,
                div_name, div_shortname, div_address, div_city, div_zip, div_countryid,
                div_ten, div_regon, div_bank, div_account, div_inv_header, div_inv_footer,
                div_inv_author, div_inv_cplace, comment, currency, currencyvalue, memo,
                type, number, fullnumber, numberplanid, recipient_address_id)
                VALUES (?, ?, ?, ?, ?, ?,
                    ?, ?, ?, ?, ?, ?, ?, ?,
                    ?, ?, ?, ?, ?, ?,
                    ?, ?, ?, ?, ?, ?,
                    ?, ?, ?, ?, ?, ?,
                    ?, ?, ?, ?, ?)',
            array_values($args)
        );
        $invoiceid = $args[SYSLOG::RES_DOC] = $this->db->GetLastInsertID('documents');
        if ($this->syslog) {
            $this->syslog->AddMessage(
                SYSLOG::RES_DOC,
                SYSLOG::OPER_ADD,
                $args,
                array('div_' . SYSLOG::getResourceKey(SYSLOG::RES_COUNTRY))
            );
        }

        foreach ($proforma['content'] as $idx => $item) {
            $args = array(
                SYSLOG::RES_DOC => $invoiceid,
                'itemid' => $item['itemid'],
                'value' => str_replace(',', '.', $item['value']),
                SYSLOG::RES_TAX => $item['taxid'],
                'taxcategory' => $item['taxcategory'],
                'prodid' => $item['prodid'],
                'content' => $item['content'],
                'count' => str_replace(',', '.', $item['count']),
                'pdiscount' => str_replace(',', '.', $item['pdiscount']),
                'vdiscount' => str_replace(',', '.', $item['vdiscount']),
                'description' => $item['description'],
                SYSLOG::RES_TARIFF => $item['tariffid'] ?: null,
            );
            $this->db->Execute('INSERT INTO invoicecontents (docid, itemid, value,
					taxid, taxcategory, prodid, content, count, pdiscount, vdiscount, description, tariffid)
					VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', array_values($args));
            if ($this->syslog) {
                $args[SYSLOG::RES_CUST] = $proforma['customerid'];
                $this->syslog->AddMessage(SYSLOG::RES_INVOICECONT, SYSLOG::OPER_ADD, $args);
            }

            $this->AddBalance(array(
                'time' => $currtime,
                'value' => $item['value'] * $item['count']*-1,
                'currency' => $proforma['currency'],
                'currencyvalue' => $currencyvalues[$proforma['currencyvalue']],
                'taxid' => $item['taxid'],
                'customerid' => $proforma['customerid'],
                'comment' => $item['description'],
                'docid' => $invoiceid,
                'itemid' => $item['itemid'],
            ));
        }

        if (ConfigHelper::checkConfig('phpui.default_preserve_proforma_invoice')) {
            $this->PreserveProforma($docid);
        } else {
            $this->DeleteArchiveTradeDocument($docid);
            $this->InvoiceDelete($docid);
        }

        $this->db->UnLockTables();
        $this->db->CommitTrans();

        return intval($invoiceid);
    }

    public function isInvoiceEditable($id)
    {
        return ($this->db->GetOne(
            'SELECT d.id FROM documents d
            LEFT JOIN documents d2 ON d2.reference = d.id
            WHERE d.id = ? AND d.type IN ? AND d.cancelled = 0 AND d.closed = 0 AND d.archived = 0 AND d2.id IS NULL
                ' . (ConfigHelper::checkPrivilege('published_document_modification') ? '' : ' AND d.published = 0'),
            array($id, array(DOC_INVOICE, DOC_CNOTE, DOC_INVOICE_PRO))
        ) > 0);
    }

    public function isTariffEditable($id)
    {
        return (ConfigHelper::checkPrivilege('used_tariff_edit') || $this->db->GetOne(
            'SELECT COUNT(CASE WHEN s.customerid IS NULL AND commited = 1 AND suspended = 0 AND datefrom < ?NOW? AND (dateto = 0 OR dateto > ?NOW?) THEN 1 ELSE NULL END) AS active
            FROM assignments
            LEFT JOIN (
                SELECT DISTINCT a.customerid
                FROM assignments a
                WHERE a.tariffid IS NULL AND a.liabilityid IS NULL
                    AND a.datefrom < ?NOW? AND (a.dateto = 0 OR a.dateto > ?NOW?)
            ) s ON s.customerid = assignments.customerid
            WHERE tariffid = ?',
            array($id)
        ) > 0);
    }
}
