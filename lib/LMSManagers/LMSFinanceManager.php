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
 * LMSFinanceManager
 *
 */
class LMSFinanceManager extends LMSManager implements LMSFinanceManagerInterface
{
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
        return $this->db->GetOne('SELECT SUM(tariffs.value)
		    FROM assignments a, tariffs
			WHERE tariffid = tariffs.id AND customerid = ? AND suspended = 0 AND commited = 1
			    AND a.datefrom <= ?NOW? AND (a.dateto > ?NOW? OR a.dateto = 0)', array($id));
    }

    public function GetCustomerAssignments($id, $show_expired = false)
    {
        $now = mktime(0, 0, 0, date('n'), date('d'), date('Y'));

        $assignments = $this->db->GetAll('SELECT
                                            a.id AS id, a.tariffid, a.customerid, a.period,
                                            a.at, a.suspended, a.invoice, a.settlement,
                                            a.datefrom, a.dateto, a.pdiscount, a.vdiscount,
                                            a.attribute, a.liabilityid, t.uprate, t.upceil,
                                            t.downceil, t.downrate, t.type AS tarifftype,
                                            (CASE WHEN t.value IS NULL THEN l.value ELSE t.value END) AS value,
                                            (CASE WHEN t.name IS NULL THEN l.name ELSE t.name END) AS name,
                                            d.number AS docnumber, d.type AS doctype, d.cdate, np.template
                                          FROM
                                            assignments a
                                            LEFT JOIN tariffs t     ON (a.tariffid = t.id)
                                            LEFT JOIN liabilities l ON (a.liabilityid = l.id)
                                            LEFT JOIN documents d ON d.id = a.docid
                                            LEFT JOIN numberplans np ON np.id = d.numberplanid
                                          WHERE a.customerid=? AND a.commited = 1 '
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

				$row['docnumber'] = docnumber(array(
					'number' => $row['docnumber'],
					'template' => $row['numtemplate'],
					'cdate' => $row['cdate'],
					'customerid' => $id,
				));

                $assignments[$idx] = $row;

                // assigned nodes
                $assignments[$idx]['nodes'] = $this->db->GetAll('SELECT vn.name, vn.id, vn.location, nd.name as netdev_name,
                                                                   nd.ownerid as netdev_ownerid
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
        $this->db->BeginTrans();

        if ($this->syslog) {
            $custid = $this->db->GetOne('SELECT customerid FROM assignments WHERE id=?', array($id));

            $nodeassigns = $this->db->GetAll('SELECT id, nodeid FROM nodeassignments WHERE assignmentid = ?', array($id));
            if (!empty($nodeassigns))
                foreach ($nodeassigns as $nodeassign) {
                    $args = array(
                        SYSLOG::RES_NODEASSIGN => $nodeassign['id'],
                        SYSLOG::RES_CUST => $custid,
                        SYSLOG::RES_NODE => $nodeassign['nodeid'],
                        SYSLOG::RES_ASSIGN => $id
                    );
                    $this->syslog->AddMessage(SYSLOG::RES_NODEASSIGN, SYSLOG::OPER_DELETE, $args);
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

        $this->db->CommitTrans();
    }

    public function AddAssignment($data)
    {
        $result = array();

		$commited = (!isset($data['commited']) || $data['commited'] ? 1 : 0);

        // Create assignments according to promotion schema
        if (!empty($data['promotiontariffid']) && !empty($data['schemaid'])) {

            $data['tariffid'] = $data['promotiontariffid'];
            $tariff = $this->db->GetRow('SELECT a.data, s.data AS sdata, t.name, t.value, t.period,
                                         	t.id, t.prodid, t.taxid, s.continuation, s.ctariffid
					                     FROM
					                     	promotionassignments a
						                    JOIN promotionschemas s ON (s.id = a.promotionschemaid)
						                    JOIN tariffs t ON (t.id = a.tariffid)
					                     WHERE a.promotionschemaid = ? AND a.tariffid = ?', array($data['schemaid'], $data['promotiontariffid']));

            $data_schema = explode(';', $tariff['sdata']);
            $data_tariff = explode(';', $tariff['data']);
            $datefrom    = $data['datefrom'];
            $cday        = date('d', $datefrom);

            foreach ($data_tariff as $idx => $dt) {
                list($value, $period) = explode(':', $dt);

                // Activation
                if (!$idx) {

                    // if activation value specified, create disposable liability
                    if (f_round($value)) {
                        $start_day   = date('d', $data['datefrom']);
                        $start_month = date('n', $data['datefrom']);
                        $start_year  = date('Y', $data['datefrom']);

                        // payday is before the start of the period
                        // set activation payday to next month's payday
                        if (ConfigHelper::checkConfig('phpui.promotion_activation_at_next_day')) {
                            $_datefrom = $data['datefrom'];
                            $datefrom = time() + 86400;
                        } elseif ($start_day > $data['at']) {
                            $_datefrom = $data['datefrom'];
                            $datefrom = mktime(0, 0, 0, $start_month + 1, $data['at'], $start_year);
                        }

                        $args = array(
                            'name' => trans('Activation payment'),
                            'value' => str_replace(',', '.', $value),
                            SYSLOG::RES_TAX => intval($tariff['taxid']),
                            'prodid' => $tariff['prodid']
                        );
                        $this->db->Execute('INSERT INTO liabilities (name, value, taxid, prodid) VALUES (?, ?, ?, ?)', array_values($args));

                        $lid = $this->db->GetLastInsertID('liabilities');

                        if ($this->syslog) {
                            $args[SYSLOG::RES_LIAB] = $lid;
                            $args[SYSLOG::RES_CUST] = $data['customerid'];
                            $this->syslog->AddMessage(SYSLOG::RES_LIAB, SYSLOG::OPER_ADD, $args);
                        }

                        $tariffid = 0;
                        $period   = DISPOSABLE;
                        $at       = $datefrom;
                    } else {
                        continue;
                    }
                }

                // promotion period
                else {
                    $lid = 0;

                    if (!$period)
                        $period = $data['period'];

                    $datefrom  = !empty($_datefrom) ? $_datefrom : $datefrom;
                    $_datefrom = 0;
                    $at        = (ConfigHelper::checkConfig('phpui.promotion_preserve_at_day') && !empty($data['at'])
                                               ? $data['at'] : $this->CalcAt($period, $datefrom));
                    $length    = $data_schema[$idx - 1];
                    $month     = date('n', $datefrom);
                    $year      = date('Y', $datefrom);

                    // assume $data['at'] == 1, set last day of the specified month
                    $dateto = mktime(23, 59, 59, $month + $length + ($cday && $cday != 1 ? 1 : 0), 0, $year);
                    $cday   = 0;

                    // Find tariff with specified name+value+period...
                    $tariffid = null;
                    if ($tariff['period'] !== null) {

                        $tariffid = $this->db->GetOne('SELECT id FROM tariffs
                        							   WHERE
                        							   		name   = ? AND
                        							   		value  = ? AND
                        							   		period = ?
                        							   LIMIT 1',
							                           array( $tariff['name'],
                                                              empty($value) || $value == 'NULL' ? 0 : str_replace(',', '.', $value),
                                                              $tariff['period'] ) );

                    } else {
                        $tariffid = $this->db->GetOne('
                            SELECT id FROM tariffs
                            WHERE name = ? AND value = ? AND period IS NULL
                            LIMIT 1',
                            array(
                                $tariff['name'],
                                empty($value) || $value == 'NULL' ? 0 : str_replace(',', '.', $value),
                            )
                        );
                    }

                    // ... if not found clone tariff
                    if (!$tariffid) {
                        $args = $this->db->GetRow('SELECT
                        							  name, value, period, taxid, type, upceil,
                                                  	  downceil, uprate, downrate, prodid, plimit, climit, dlimit, upceil_n,
                                                  	  downceil_n, uprate_n, downrate_n, domain_limit, alias_limit, sh_limit,
                                                  	  www_limit, ftp_limit, mail_limit, sql_limit, quota_sh_limit, quota_www_limit,
                                                  	  quota_ftp_limit, quota_mail_limit, quota_sql_limit. authtype
												   FROM
												   	  tariffs WHERE id = ?', array($tariff['id']));

                        $args = array_merge($args, array(
								                          'name' => $tariff['name'],
								                          'value' => str_replace(',', '.', $value),
								                          'period' => $tariff['period'] ));

						$args[SYSLOG::RES_TAX] = $args['taxid'];
                        unset($args['taxid']);

                        $this->db->Execute('INSERT INTO tariffs
                        					   (name, value, period, type, upceil, downceil, uprate, downrate, prodid,
                        					   plimit, climit, dlimit, upceil_n, downceil_n, uprate_n, downrate_n,
				   							   domain_limit, alias_limit, sh_limit, www_limit, ftp_limit, mail_limit, sql_limit,
											   quota_sh_limit, quota_www_limit, quota_ftp_limit, quota_mail_limit, quota_sql_limit,
											   authtype, taxid)
							                VALUES
							                   (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', array_values($args));

                        $tariffid = $this->db->GetLastInsertId('tariffs');

                        if ($this->syslog) {
                            $args[SYSLOG::RES_TARIFF] = $tariffid;
                            $this->syslog->AddMessage(SYSLOG::RES_TARIFF, SYSLOG::OPER_ADD, $args);
                        }
                    }
                }

                // Create assignment
                $args = array(
                    SYSLOG::RES_TARIFF  => empty($tariffid) ? null : $tariffid,
                    SYSLOG::RES_CUST    => $data['customerid'],
                    'period'            => $period,
                    'at'                => $at,
                    'invoice'           => !empty($data['invoice']) ? (isset($data['separateinvoice']) ? 2 : 1) : 0,
                    'settlement'        => !empty($data['settlement']) ? 1 : 0,
                    SYSLOG::RES_NUMPLAN => !empty($data['numberplanid']) ? $data['numberplanid'] : NULL,
                    'paytype'           => !empty($data['paytype']) ? $data['paytype'] : NULL,
                    'datefrom'          => $idx ? $datefrom : 0,
                    'dateto'            => $idx ? $dateto : 0,
                    'pdiscount'         => 0,
                    'vdiscount'         => 0,
                    'attribute'         => !empty($data['attribute']) ? $data['attribute'] : NULL,
                    SYSLOG::RES_LIAB    => empty($lid) ? null : $lid,
                    'recipient_address_id' => $data['recipient_address_id'] > 0 ? $data['recipient_address_id'] : NULL,
                    'commited'			=> $commited,
                );

                $result[] = $data['assignmentid'] = $this->insertAssignment( $args );

				$this->insertNodeAssignments($data);

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
                        SYSLOG::RES_TARIFF  => $t,
                        SYSLOG::RES_CUST    => $data['customerid'],
                        'period'            => $data['period'],
                        'at'                => (ConfigHelper::checkConfig('phpui.promotion_preserve_at_day') && !empty($data['at'])
                                                       ? $data['at'] : $this->CalcAt($data['period'], $datefrom)),
                        'invoice'           => !empty($data['invoice']) ? (isset($data['separateinvoice']) ? 2 : 1) : 0,
                        'settlement'        => !empty($data['settlement']) ? 1 : 0,
                        SYSLOG::RES_NUMPLAN => !empty($data['numberplanid']) ? $data['numberplanid'] : NULL,
                        'paytype'           => !empty($data['paytype']) ? $data['paytype'] : NULL,
                        'datefrom'          => $datefrom,
                        'dateto'            => 0,
                        'pdiscount'         => 0,
                        'vdiscount'         => 0,
                        'attribute'         => !empty($data['attribute']) ? $data['attribute'] : NULL,
                        SYSLOG::RES_LIAB    => null,
                        'recipient_address_id' => $data['recipient_address_id'] > 0 ? $data['recipient_address_id'] : NULL,
                        'commited'			=> $commited,
                    );

                    $result[] = $data['assignmentid'] = $this->insertAssignment( $args );

					$this->insertNodeAssignments($data);
				}
            }
        } else {
        // Create one assignment record
            if (!empty($data['value'])) {
                $args = array(
                    'name' => $data['name'],
                    'value' => str_replace(',', '.', $data['value']),
                    SYSLOG::RES_TAX => intval($data['taxid']),
                    'prodid' => $data['prodid']
                );
                $this->db->Execute('INSERT INTO liabilities (name, value, taxid, prodid)
					    VALUES (?, ?, ?, ?)', array_values($args));
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
                'at'                => $data['at'],
                'invoice'           => !empty($data['invoice']) ? (isset($data['separateinvoice']) ? 2 : 1) : 0,
                'settlement'        => !empty($data['settlement']) ? 1 : 0,
                SYSLOG::RES_NUMPLAN => !empty($data['numberplanid']) ? $data['numberplanid'] : NULL,
                'paytype'           => !empty($data['paytype']) ? $data['paytype'] : NULL,
                'datefrom'          => $data['datefrom'],
                'dateto'            => $data['dateto'],
                'pdiscount'         => str_replace(',', '.', $data['pdiscount']),
                'vdiscount'         => str_replace(',', '.', $data['vdiscount']),
                'attribute'         => !empty($data['attribute']) ? $data['attribute'] : NULL,
                SYSLOG::RES_LIAB    => !isset($lid) || empty($lid) ? null : $lid,
                'recipient_address_id' => $data['recipient_address_id'] > 0 ? $data['recipient_address_id'] : NULL,
                'commited'			=> $commited,
            );

            $data['assignmentid'] = $this->insertAssignment($args);

            $this->insertNodeAssignments($data);
        }

        return $result;
    }

    /*
     * Helper method who insert assignment.
     *
     * \param  array $args array with parameters for SQL query
     * \return int   last inserted id
     */
    private function insertAssignment($args) {
    	$this->db->Execute('INSERT INTO assignments
    							(tariffid, customerid, period, at, invoice, settlement, numberplanid,
    							paytype, datefrom, dateto, pdiscount, vdiscount, attribute, liabilityid, recipient_address_id,
    							commited)
					        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
					        array_values($args));

        $id = $this->db->GetLastInsertID('assignments');

        if ($this->syslog) {
            $args[SYSLOG::RES_ASSIGN] = $id;
            $this->syslog->AddMessage(SYSLOG::RES_ASSIGN, SYSLOG::OPER_ADD, $args);
        }

        return $id;
    }

	private function insertNodeAssignments($args) {
		if (!empty($args['nodes'])) {
			// Use multi-value INSERT query
			$values = array();
			foreach ($args['nodes'] as $nodeid)
				$values[] = sprintf('(%d, %d)', $nodeid, $args['assignmentid']);

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

	public function SuspendAssignment($id, $suspend = TRUE) {
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

    public function AddInvoice($invoice)
    {
        $currtime = time();
        $cdate = $invoice['invoice']['cdate'] ? $invoice['invoice']['cdate'] : $currtime;
        $sdate = $invoice['invoice']['sdate'] ? $invoice['invoice']['sdate'] : $currtime;
        $number = $invoice['invoice']['number'];
        $type = $invoice['invoice']['type'];
        if ($invoice['invoice']['numberplanid'])
            $fullnumber = docnumber(array(
            	'number' => $number,
            	'template' => $this->db->GetOne('SELECT template FROM numberplans WHERE id = ?', array($invoice['invoice']['numberplanid'])),
            	'cdate' => $cdate,
            ));
        else
            $fullnumber = null;

        $division = $this->db->GetRow('SELECT name, shortname, address, city, zip, countryid, ten, regon,
				account, inv_header, inv_footer, inv_author, inv_cplace
				FROM vdivisions WHERE id = ?', array($invoice['customer']['divisionid']));

		$location_manager = new LMSLocationManager($this->db, $this->auth, $this->cache, $this->syslog);

		if ($invoice['invoice']['recipient_address_id'] > 0) {
			$invoice['invoice']['recipient_address_id'] = $location_manager->CopyAddress( $invoice['invoice']['recipient_address_id'] );
		} else {
			$invoice['invoice']['recipient_address_id'] = null;
		}

		$post_address_id = $location_manager->GetCustomerAddress($invoice['customer']['id'], POSTAL_ADDRESS);

		if (empty($post_address_id)) {
			$invoice['invoice']['post_address_id'] = null;
		} else {
			$invoice['invoice']['post_address_id'] = $location_manager->CopyAddress( $post_address_id );
		}

        $args = array(
            'number' => $number,
            SYSLOG::RES_NUMPLAN => $invoice['invoice']['numberplanid'] ? $invoice['invoice']['numberplanid'] : null,
            'type' => $type,
            'cdate' => $cdate,
            'sdate' => $sdate,
            'paytime' => $invoice['invoice']['paytime'],
            'paytype' => $invoice['invoice']['paytype'],
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
            'div_account' => ($division['account'] ? $division['account'] : ''),
            'div_inv_header' => ($division['inv_header'] ? $division['inv_header'] : ''),
            'div_inv_footer' => ($division['inv_footer'] ? $division['inv_footer'] : ''),
            'div_inv_author' => ($division['inv_author'] ? $division['inv_author'] : ''),
            'div_inv_cplace' => ($division['inv_cplace'] ? $division['inv_cplace'] : ''),
            'fullnumber' => $fullnumber,
            'recipient_address_id' => $invoice['invoice']['recipient_address_id'],
			'post_address_id' => $invoice['invoice']['post_address_id'],
        );

        $this->db->Execute('INSERT INTO documents (number, numberplanid, type,
			cdate, sdate, paytime, paytype, userid, customerid, name, address,
			ten, ssn, zip, city, countryid, divisionid,
			div_name, div_shortname, div_address, div_city, div_zip, div_countryid, div_ten, div_regon,
			div_account, div_inv_header, div_inv_footer, div_inv_author, div_inv_cplace, fullnumber,
			recipient_address_id, post_address_id)
			VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', array_values($args));
        $iid = $this->db->GetLastInsertID('documents');
        if ($this->syslog) {
            unset($args[SYSLOG::RES_USER]);
            $args[SYSLOG::RES_DOC] = $iid;
            $this->syslog->AddMessage(SYSLOG::RES_DOC, SYSLOG::OPER_ADD, $args,
            	array('div_' . SYSLOG::getResourceKey(SYSLOG::RES_COUNTRY)));
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
                'prodid' => $item['prodid'],
                'content' => $item['jm'],
                'count' => $item['count'],
                'pdiscount' => $item['pdiscount'],
                'vdiscount' => $item['vdiscount'],
                'description' => $item['name'],
                SYSLOG::RES_TARIFF => empty($item['tariffid']) ? null : $item['tariffid'],
            );
            $this->db->Execute('INSERT INTO invoicecontents (docid, itemid,
				value, taxid, prodid, content, count, pdiscount, vdiscount, description, tariffid)
				VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', array_values($args));
            if ($this->syslog) {
                $args[SYSLOG::RES_CUST] = $invoice['customer']['id'];
                $this->syslog->AddMessage(SYSLOG::RES_INVOICECONT, SYSLOG::OPER_ADD, $args);
            }

			if ($type != DOC_INVOICE_PRO || ConfigHelper::checkConfig('phpui.proforma_invoice_generates_commitment'))
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
        $this->db->BeginTrans();
        if ($this->syslog) {
            $customerid = $this->db->GetOne('SELECT customerid FROM documents WHERE id = ?', array($invoiceid));
            $args = array(
                SYSLOG::RES_DOC => $invoiceid,
                SYSLOG::RES_CUST => $customerid,
            );
            $this->syslog->AddMessage(SYSLOG::RES_DOC, SYSLOG::OPER_DELETE, $args);
            $cashids = $this->db->GetCol('SELECT id FROM cash WHERE docid = ?', array($invoiceid));
            foreach ($cashids as $cashid) {
                $args = array(
                    SYSLOG::RES_CASH => $cashid,
                    SYSLOG::RES_DOC => $invoiceid,
                    SYSLOG::RES_CUST => $customerid,
                );
                $this->syslog->AddMessage(SYSLOG::RES_CASH, SYSLOG::OPER_DELETE, $args);
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
        $this->db->Execute('DELETE FROM invoicecontents WHERE docid = ?', array($invoiceid));
        $this->db->Execute('DELETE FROM cash WHERE docid = ?', array($invoiceid));
        $this->db->CommitTrans();
    }

    public function InvoiceContentDelete($invoiceid, $itemid = 0)
    {
        if ($itemid) {
            $this->db->BeginTrans();
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

            if ($this->syslog) {
                $cashid = $this->db->GetOne('SELECT id FROM cash WHERE docid = ? AND itemid = ?', array($invoiceid, $itemid));
                $args = array(
                    SYSLOG::RES_CASH => $cashid,
                    SYSLOG::RES_DOC => $invoiceid,
                    SYSLOG::RES_CUST => $customerid,
                );
                $this->syslog->AddMessage(SYSLOG::RES_CASH, SYSLOG::OPER_DELETE, $args);
            }
            $this->db->Execute('DELETE FROM cash WHERE docid = ? AND itemid = ?', array($invoiceid, $itemid));
            $this->db->CommitTrans();
        } else
            $this->InvoiceDelete($invoiceid);
    }

    public function GetInvoiceContent($invoiceid)
    {
        global $PAYTYPES, $LMS;

        if ($result = $this->db->GetRow('SELECT d.id, d.type AS doctype, d.number, d.name, d.customerid,
				d.userid, d.address, d.zip, d.city, d.countryid, cn.name AS country,
				d.ten, d.ssn, d.cdate, d.sdate, d.paytime, d.paytype, d.numberplanid,
				d.closed, d.cancelled, d.published, d.reference, d.reason, d.divisionid,
				(SELECT name FROM vusers WHERE id = d.userid) AS user, n.template,
				d.div_name AS division_name, d.div_shortname AS division_shortname,
				d.div_address AS division_address, d.div_zip AS division_zip,
				d.div_city AS division_city, d.div_countryid AS division_countryid,
				d.div_ten AS division_ten, d.div_regon AS division_regon, d.div_account AS account,
				d.div_inv_header AS division_header, d.div_inv_footer AS division_footer,
				d.div_inv_author AS division_author, d.div_inv_cplace AS division_cplace,
				d.recipient_address_id, d.post_address_id,
				a.city as rec_city, a.zip as rec_zip, a.postoffice AS rec_postoffice,
				a.name as rec_name, a.address AS rec_address,
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
				(CASE WHEN d.post_address_id IS NULL THEN c.post_countryid ELSE a2.country_id END) AS post_countryid
				FROM documents d
				JOIN customeraddressview c ON (c.id = d.customerid)
				LEFT JOIN countries cn ON (cn.id = d.countryid)
				LEFT JOIN numberplans n ON (d.numberplanid = n.id)
				LEFT JOIN vaddresses a ON d.recipient_address_id = a.id
				LEFT JOIN vaddresses a2 ON d.post_address_id = a2.id
				WHERE d.id = ? AND (d.type = ? OR d.type = ? OR d.type = ?)', array($invoiceid, DOC_INVOICE, DOC_CNOTE, DOC_INVOICE_PRO))) {

			if (!empty($result['post_address_id'])) {

			}

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
						itemid, taxid, (CASE WHEN taxes.reversecharge = 1 THEN -2 ELSE (
								CASE WHEN taxes.taxed = 0 THEN -1 ELSE taxes.value END
							) END) AS taxvalue, taxes.label AS taxlabel,
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

					if ($row['taxvalue'] < 0)
						$taxvalue = 0;
					else
						$taxvalue = $row['taxvalue'];
                    $result['content'][$idx]['basevalue'] = round(($row['value'] / (100 + $taxvalue) * 100), 2);
                    $result['content'][$idx]['total'] = round($row['value'] * $row['count'], 2);
                    $result['content'][$idx]['totalbase'] = round($result['content'][$idx]['total'] / (100 + $taxvalue) * 100, 2);
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
                    $result['taxest'][$row['taxvalue']]['taxvalue'] = $taxvalue;
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
		global $LMS;

        if ($result = $this->db->GetRow('SELECT d.id, d.number, d.name, d.customerid,
				d.userid, d.address, d.zip, d.city, d.countryid, cn.name AS country,
				d.ten, d.ssn, d.cdate, d.numberplanid, d.closed, d.published, d.divisionid, d.paytime,
				(SELECT name FROM vusers WHERE id = d.userid) AS user, n.template,
				d.div_name AS division_name, d.div_shortname AS division_shortname,
				d.div_address AS division_address, d.div_zip AS division_zip,
				d.div_city AS division_city, d.div_countryid AS division_countryid,
				d.div_ten AS division_ten, d.div_regon AS division_regon, d.div_account AS account,
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
				(CASE WHEN d.post_address_id IS NULL THEN c.post_countryid ELSE a2.country_id END) AS post_countryid
				FROM documents d
				JOIN customeraddressview c ON (c.id = d.customerid)
				LEFT JOIN countries cn ON (cn.id = d.countryid)
				LEFT JOIN numberplans n ON (d.numberplanid = n.id)
				LEFT JOIN vaddresses a2 ON a2.id = d.post_address_id
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
		global $ACCOUNTTYPES;

        $args = array(
            'name' => $tariff['name'],
            'description' => $tariff['description'],
            'value' => $tariff['value'],
            'period' => $tariff['period'] ? $tariff['period'] : null,
            SYSLOG::RES_TAX => $tariff['taxid'],
            SYSLOG::RES_NUMPLAN => $tariff['numberplanid'] ? $tariff['numberplanid'] : null,
            'datefrom' => $tariff['from'] ? $tariff['from'] : 0,
            'dateto' => $tariff['to'] ? $tariff['to'] : 0,
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
            'domain_limit' => $tariff['domain_limit'],
            'alias_limit' => $tariff['alias_limit'],
			'authtype' => $tariff['authtype'],
        );
        $args2 = array();
        foreach ($ACCOUNTTYPES as $typeidx => $type) {
            $args2[$type['alias'] . '_limit'] = $tariff[$type['alias'] . '_limit'];
            $args2['quota_' . $type['alias'] . '_limit'] = $tariff['quota_' . $type['alias'] . '_limit'];
        }
        $result = $this->db->Execute('INSERT INTO tariffs (name, description, value,
				period, taxid, numberplanid, datefrom, dateto, prodid, uprate, downrate, upceil, downceil, climit,
				plimit, uprate_n, downrate_n, upceil_n, downceil_n, climit_n,
				plimit_n, dlimit, type, domain_limit, alias_limit, authtype, '
				. implode(', ', array_keys($args2)) . ')
				VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,' . implode(',', array_fill(0, count($args2), '?')) . ')',
				array_values(array_merge($args, $args2)));
        if ($result) {
            $id = $this->db->GetLastInsertID('tariffs');
            if ($this->syslog) {
                $args[SYSLOG::RES_TARIFF] = $id;
                $this->syslog->AddMessage(SYSLOG::RES_TARIFF, SYSLOG::OPER_ADD, $args);
            }
            return $id;
        } else
            return FALSE;
    }

    public function TariffUpdate($tariff)
    {
		global $ACCOUNTTYPES;

        $args = array(
            'name' => $tariff['name'],
            'description' => $tariff['description'],
            'value' => $tariff['value'],
            'period' => $tariff['period'] ? $tariff['period'] : null,
            SYSLOG::RES_TAX => $tariff['taxid'],
            SYSLOG::RES_NUMPLAN => $tariff['numberplanid'] ? $tariff['numberplanid'] : null,
            'datefrom' => $tariff['from'],
            'dateto' => $tariff['to'],
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
            'domain_limit' => $tariff['domain_limit'],
            'alias_limit' => $tariff['alias_limit'],
            'type' => $tariff['type'],
            'voip_tariff_id' => (!empty($tariff['voip_pricelist'])) ? $tariff['voip_pricelist'] : NULL,
            'voip_tariff_rule_id' => (!empty($tariff['voip_tariffrule'])) ? $tariff['voip_tariffrule'] : NULL,
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
            period = ?, taxid = ?, numberplanid = ?, datefrom = ?, dateto = ?, prodid = ?,
            uprate = ?, downrate = ?, upceil = ?, downceil = ?, climit = ?, plimit = ?,
            uprate_n = ?, downrate_n = ?, upceil_n = ?, downceil_n = ?, climit_n = ?, plimit_n = ?,
            dlimit = ?, domain_limit = ?, alias_limit = ?, type = ?, voip_tariff_id = ?, voip_tariff_rule_id = ?, 
            authtype = ?, '
            . implode(' = ?, ', $fields) . ' = ? WHERE id=?', array_values($args));
        if ($res && $this->syslog)
            $this->syslog->AddMessage(SYSLOG::RES_TARIFF, SYSLOG::OPER_UPDATE, $args);
        return $res;
    }

    public function TariffDelete($id)
    {
        if ($this->syslog)
            $assigns = $this->db->GetAll('SELECT promotionid, a.id, promotionschemaid FROM promotionassignments a
				JOIN promotionschemas s ON s.id = a.promotionschemaid
				WHERE a.tariffid = ?', array($id));
        $res = $this->db->Execute('DELETE FROM tariffs WHERE id=?', array($id));
        if ($res && $this->syslog) {
            $this->syslog->AddMessage(SYSLOG::RES_TARIFF, SYSLOG::OPER_DELETE, array(SYSLOG::RES_TARIFF => $id));
            if (!empty($assigns))
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
                . 'WHERE c.id = customerid AND commited = 1 AND deleted = 0 AND tariffid = ? '
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
			WHERE tariffid = ? AND commited = 1', array($id));

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

    public function GetTariffs($forced_id = null)
    {
        return $this->db->GetAllByKey('SELECT t.id, t.name, t.value, uprate, taxid, t.authtype,
				datefrom, dateto, (CASE WHEN datefrom < ?NOW? AND (dateto = 0 OR dateto > ?NOW?) THEN 1 ELSE 0 END) AS valid,
				prodid, downrate, upceil, downceil, climit, plimit, taxes.value AS taxvalue,
				taxes.label AS tax, t.period, t.type AS tarifftype, ' . $this->db->GroupConcat('ta.tarifftagid') . ' AS tags
				FROM tariffs t
				LEFT JOIN tariffassignments ta ON ta.tariffid = t.id
				LEFT JOIN taxes ON t.taxid = taxes.id
				WHERE t.disabled = 0' . (empty($forced_id) ? '' : ' OR t.id = ' . intval($forced_id)) . '
				GROUP BY t.id, t.name, t.value, uprate, taxid, datefrom, dateto, prodid, downrate, upceil, downceil, climit, plimit,
					taxes.value, taxes.label, t.period, t.type
				ORDER BY t.name, t.value DESC', 'id');
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

	public function ReceiptDelete($docid) {
		$this->db->BeginTrans();
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
		$this->db->Execute('DELETE FROM receiptcontents WHERE docid=?', array($docid));
		$this->db->Execute('DELETE FROM documents WHERE id = ?', array($docid));
		$this->db->Execute('DELETE FROM cash WHERE docid = ?', array($docid));
		$this->db->CommitTrans();
	}

    public function ReceiptContentDelete($docid, $itemid = 0)
    {
        if ($itemid) {
        	$this->db->BeginTrans();
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
            if ($this->syslog) {
                $cashid = $this->db->GetOne('SELECT id FROM cash WHERE docid = ? AND itemid = ?', array($docid, $itemid));
                $args = array(
                    SYSLOG::RES_CASH => $cashid,
                    SYSLOG::RES_DOC => $docid,
                    SYSLOG::RES_CUST => $customerid,
                );
                $this->syslog->AddMessage(SYSLOG::RES_CASH, SYSLOG::OPER_DELETE, $args);
            }
            $this->db->Execute('DELETE FROM cash WHERE docid = ? AND itemid = ?', array($docid, $itemid));
            $this->db->CommitTrans();
        } else
        	$this->ReceiptDelete($docid);
    }

	public function DebitNoteDelete($noteid) {
		$this->db->BeginTrans();
		if ($this->syslog) {
			$customerid = $this->db->GetOne('SELECT customerid FROM documents WHERE id = ?', array($noteid));
			$args = array(
				SYSLOG::RES_DOC => $noteid,
				SYSLOG::RES_CUST => $customerid,
			);
			$this->syslog->AddMessage(SYSLOG::RES_DOC, SYSLOG::OPER_DELETE, $args);
			$dnoteitems = $this->db->GetCol('SELECT id FROM debitnotecontents WHERE docid = ?',
				array($noteid));
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
		$this->db->Execute('DELETE FROM debitnotecontents WHERE docid = ?', array($noteid));
		$this->db->Execute('DELETE FROM cash WHERE docid = ?', array($noteid));
		$this->db->CommitTrans();

	}

	public function DebitNoteContentDelete($docid, $itemid = 0)
    {
        if ($itemid) {
        	$this->db->BeginTrans();
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
            if ($this->syslog) {
                $cashid = $this->db->GetOne('SELECT id FROM cash WHERE docid = ? AND itemid = ?', array($docid, $itemid));
                $args = array(
                    SYSLOG::RES_CASH => $cashid,
                    SYSLOG::RES_DOC => $docid,
                    SYSLOG::RES_CUST => $customerid,
                );
                $this->syslog->AddMessage(SYSLOG::RES_CASH, SYSLOG::OPER_DELETE, $args);
            }
            $this->db->Execute('DELETE FROM cash WHERE docid = ? AND itemid = ?', array($docid, $itemid));
            $this->db->CommitTrans();
        } else
        	$this->DebitNoteDelete($docid);
    }

    public function AddBalance($addbalance)
    {
        $args = array(
            'time' => isset($addbalance['time']) ? $addbalance['time'] : time(),
            SYSLOG::RES_USER => isset($addbalance['userid']) && !empty($addbalance['userid']) ? $addbalance['userid'] : Auth::GetCurrentUser(),
            'value' => str_replace(',', '.', round($addbalance['value'], 2)),
            'type' => isset($addbalance['type']) ? $addbalance['type'] : 0,
            SYSLOG::RES_TAX => isset($addbalance['taxid']) && !empty($addbalance['taxid']) ? $addbalance['taxid'] : null,
            SYSLOG::RES_CUST => $addbalance['customerid'],
            'comment' => $addbalance['comment'],
            SYSLOG::RES_DOC => isset($addbalance['docid']) && !empty($addbalance['docid']) ? $addbalance['docid'] : null,
            'itemid' => isset($addbalance['itemid']) ? $addbalance['itemid'] : 0,
            SYSLOG::RES_CASHIMPORT => !empty($addbalance['importid']) ? $addbalance['importid'] : NULL,
            SYSLOG::RES_CASHSOURCE => !empty($addbalance['sourceid']) ? $addbalance['sourceid'] : NULL,
        );
        $res = $this->db->Execute('INSERT INTO cash (time, userid, value, type, taxid,
			customerid, comment, docid, itemid, importid, sourceid)
			VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', array_values($args));
        if ($res && $this->syslog) {
            unset($args[SYSLOG::RES_USER]);
            $args[SYSLOG::RES_CASH] = $this->db->GetLastInsertID('cash');
            $this->syslog->AddMessage(SYSLOG::RES_CASH, SYSLOG::OPER_ADD, $args);
        }
        return $res;
    }

    public function DelBalance($id)
    {
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
                    SYSLOG::RES_CASH => $id,
                    SYSLOG::RES_CUST => $row['customerid'],
                );
                $this->syslog->AddMessage(SYSLOG::RES_CASH, SYSLOG::OPER_DELETE, $args);
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
        } else
            return FALSE;
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
        if ($res && $this->syslog)
            $this->syslog->AddMessage(SYSLOG::RES_PAYMENT, SYSLOG::OPER_UPDATE, $args);
        return $res;
    }

	public function GetHostingLimits($customerid) {
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
			quota_www_limit, quota_mail_limit, quota_sql_limit, quota_ftp_limit
			FROM tariffs WHERE type <> ? AND type <> ? AND type <> ? AND id IN (SELECT tariffid FROM assignments
			WHERE customerid = ? AND tariffid IS NOT NULL
				AND commited = 1
				AND (dateto > ?NOW? OR dateto = 0)
				AND (datefrom < ?NOW? OR datefrom = 0))', array(TARIFF_INTERNET, TARIFF_PHONE, TARIFF_TV, $customerid))) {
			foreach ($limits as $row) {
				foreach ($row as $idx => $val)
					if ($idx == 'alias_limit' || $idx == 'domain_limit')
						if ($val === NULL || $result[$idx] === NULL)
							$result[$idx] = NULL; // no limit
						else
							$result[$idx] += $val;
				foreach ($ACCOUNTTYPES as $typeidx => $type) {
					if ($row[$type['alias'] . '_limit'] === null || $result['count'][$typeidx] === null)
						$result['count'][$typeidx] = null;
					else
						$result['count'][$typeidx] += $row[$type['alias'] . '_limit'];
					if ($row['quota_' . $type['alias'] . '_limit'] === null || $result['quota'][$typeidx] === null)
						$result['quota'][$typeidx] = null;
					else
						$result['quota'][$typeidx] += $row['quota_' . $type['alias'] . '_limit'];
				}
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

	public function isDocumentPublished($id) {
		return $this->db->GetOne('SELECT published FROM documents WHERE id = ?', array($id)) == 1;
	}

	public function AddReceipt(array $receipt) {
		$this->db->BeginTrans();
		$this->db->LockTables(array('documents', 'numberplans'));

		$SYSLOG = SYSLOG::getInstance();
		$document_manager = new LMSDocumentManager($this->db, $this->auth, $this->cache, $this->syslog);
		$error = array();

		$customer = isset($receipt['customer']) ? $receipt['customer'] : null;
		$contents = $receipt['contents'];

		if (!$receipt['number'])
			$receipt['number'] = $document_manager->GetNewDocumentNumber(array(
				'doctype' => DOC_RECEIPT,
				'planid' => $receipt['numberplanid'],
				'cdate' => $receipt['cdate'],
				'customerid' => $customer ? $customer['id'] : null,
			));
		else {
			if (!preg_match('/^[0-9]+$/', $receipt['number']))
				$error['number'] = trans('Receipt number must be integer!');
			elseif ($document_manager->DocumentExists(array(
				'number' => $receipt['number'],
				'doctype' => DOC_RECEIPT,
				'planid' => $receipt['numberplanid'],
				'cdate' => $receipt['cdate'],
				'customerid' => $customer ? $customer['id'] : null,
			)))
				$error['number'] = trans('Receipt number $a already exists!', $receipt['number']);

			if($error)
				$receipt['number'] = $document_manager->GetNewDocumentNumber(array(
					'doctype' => DOC_RECEIPT,
					'planid' => $receipt['numberplanid'],
					'cdate' => $receipt['cdate'],
					'customerid' => $customer ? $customer['id'] : null,
				));
		}

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
			'closed' => $customer || $receipt['o_type'] != 'advance' ? 1 : 0,
			'fullnumber' => $fullnumber,
		);
		$this->db->Execute('INSERT INTO documents (type, number, extnumber, numberplanid, cdate, customerid, userid, name, address, zip, city, closed,
					fullnumber)
					VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', array_values($args));
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

			if ($receipt['type'] == 'in')
				$value = str_replace(',', '.', $item['value']);
			else
				$value = str_replace(',', '.', $item['value'] * -1);

			$args = array(
				SYSLOG::RES_DOC => $rid,
				'itemid' =>  $iid,
				'value' => $value,
				'description' => $item['description'],
				SYSLOG::RES_CASHREG => $receipt['regid'],
			);
			$this->db->Execute('INSERT INTO receiptcontents (docid, itemid, value, description, regid)
					VALUES(?, ?, ?, ?, ?)', array_values($args));
			if ($SYSLOG)
				$SYSLOG->AddMessage(SYSLOG::RES_RECEIPTCONT, SYSLOG::OPER_ADD, $args);

			$args = array(
				'time' => $receipt['cdate'],
				'type' => 1,
				SYSLOG::RES_DOC => $rid,
				'itemid' => $iid,
				'value' => $value,
				'comment' => $item['description'],
				SYSLOG::RES_USER => Auth::GetCurrentUser(),
				SYSLOG::RES_CUST => $customer ? $customer['id'] : null,
			);
			$this->db->Execute('INSERT INTO cash (time, type, docid, itemid, value, comment, userid, customerid)
						VALUES(?, ?, ?, ?, ?, ?, ?, ?)', array_values($args));
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
			if (isset($item['references']))
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

		$this->db->CommitTrans();

		return empty($error) ? $rid : $error;
	}

	public function GetCashRegistries($cid = null) {
		$userid = Auth::GetCurrentUser();

		if (empty($cid)) {
			$where = '';
			$join = '';
		} else {
			$divisionid = $this->db->GetOne('SELECT divisionid FROM customers WHERE id = ?', array($cid));
			$join = ' JOIN numberplanassignments npa ON npa.planid = in_numberplanid ';
			$where = ' AND npa.divisionid = ' . intval($divisionid);
		}

		$result = $this->db->GetAllByKey('SELECT r.id, name,
				in_numberplanid, out_numberplanid
			FROM cashregs r
			JOIN cashrights cr ON regid = r.id
			' . $join . '
			WHERE rights > 1 AND userid = ? ' . $where . '
			ORDER BY name', 'id', array($userid));
		return $result;
	}

	public function GetOpenedLiabilities($customerid) {
		static $document_descriptions = array(
			DOC_INVOICE => 'Invoice No. $a',
			DOC_CNOTE => 'Credit Note No. $a',
			DOC_DNOTE => 'Debit Note No. $a',
		);

		$customer_manager = new LMSCustomerManager($this->db, $this->auth, $this->cache, $this->syslog);

		$result = array();

		$liabilities = $this->db->GetAll('(
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
			array($customerid, $customerid, DOC_INVOICE, DOC_DNOTE));

		if (empty($liabilities))
			return $result;

		$balance = $customer_manager->GetCustomerBalance($customerid, time());

		foreach ($liabilities as $liability) {
			if (!empty($liability['docid']))
				$liability['comment'] = trans($document_descriptions[$liability['doctype']], docnumber(array(
					'number' => $liability['number'],
					'template' => $liability['template'],
					'cdate' => $liability['cdate'],
					'customerid' => $customerid,
				)));
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
					if ($cidx < count($cnotes)-1)
						$liability['comment'] .= ', ';
				}
				$liability['comment'] .= ')';
			}

			if ($balance - $liability['value'] <= 0)
				$result[] = $liability;
			elseif ($balance < 0) {
				$liability['value'] = $balance;
				$result[] = $liability;
				break;
			}
			$balance -= $liability['value'];
			$balance = round($balance, 2);
			if ($balance >= 0)
				break;
		}

		return array_reverse($result);
	}

	public function GetPromotions() {
		$promotions = $this->db->GetAllByKey('SELECT id, name,
				(CASE WHEN datefrom < ?NOW? AND (dateto = 0 OR dateto > ?NOW?) THEN 1 ELSE 0 END) AS valid
			FROM promotions WHERE disabled <> 1', 'id');

		if (empty($promotions))
			return array();

		foreach ($promotions as $promotionid => &$promotion)
			$promotion['schemas'] = array();
		unset($promotion);

		$promotion_schemas = $this->db->GetAll('SELECT p.id AS promotionid, p.name AS promotion, s.name, s.id,
			(SELECT ' . $this->db->GroupConcat('tariffid', ',') . '
				FROM promotionassignments WHERE promotionschemaid = s.id
			) AS tariffs
			FROM promotions p
			JOIN promotionschemas s ON (p.id = s.promotionid)
			WHERE p.disabled <> 1 AND s.disabled <> 1
				AND EXISTS (SELECT 1 FROM promotionassignments
				WHERE promotionschemaid = s.id LIMIT 1)
			ORDER BY p.name, s.name');

		if (empty($promotion_schemas))
			return array();
		else
			foreach ($promotion_schemas as $promotion_schema)
				$promotions[$promotion_schema['promotionid']]['schemas'][$promotion_schema['id']] =
					array(
						'id' => $promotion_schema['id'],
						'name' => $promotion_schema['name'],
						'tariffs' => $promotion_schema['tariffs'],
						'items' => array(),
					);

		$promotion_schema_assignments = $this->db->GetAll('SELECT
				p.id AS promotion_id, ps.id AS schema_id,
				t.name as tariff_name, pa.optional,
				(CASE WHEN label IS NULL THEN ' . $this->db->Concat("'unlabeled_'", 't.id') . ' ELSE label END) AS label,
				t.id as tariffid, t.type AS tarifftype, t.value, t.authtype
			FROM promotions p
				LEFT JOIN promotionschemas ps ON p.id = ps.promotionid
				LEFT JOIN promotionassignments pa ON ps.id = pa.promotionschemaid
				LEFT JOIN tariffs t ON pa.tariffid = t.id
			ORDER BY pa.orderid');

		if (empty($promotion_schema_assignments))
			return array();
		else {
			$single_labels = $this->db->GetAll('SELECT promotionschemaid AS schemaid,
					label, COUNT(*) AS cnt
				FROM promotionassignments
				WHERE label IS NOT NULL
				GROUP BY promotionschemaid, label');
			if (empty($single_labels))
				$single_labels = array();
			$selection_labels = $this->db->GetAll('SELECT promotionschemaid AS schemaid,
					(CASE WHEN label IS NULL THEN ' . $this->db->Concat("'unlabeled_'", 'tariffid') . ' ELSE label END) AS label,
					1 AS cnt
				FROM promotionassignments
				WHERE label IS NULL');
			if (empty($selection_labels))
				$selection_labels = array();
			$labels = array_merge($single_labels, $selection_labels);

			$promotion_schema_selections = array();
			if (!empty($labels)) {
				foreach ($labels as &$label) {
					if (preg_match('/^unlabeled_(?<tariffid>[0-9]+)$/', $label['label'], $m))
						$label['label'] = trans('<!tariffselection>unlabeled_$a', $m['tariffid']);
					$promotion_schema_selections[$label['schemaid']][$label['label']] = $label['cnt'];
				}
				unset($label);
			}

			foreach ($promotion_schema_assignments as $assign) {
				$pid = $assign['promotion_id'];

				if (empty($promotions[$pid]['valid']))
					continue;

				$sid = $assign['schema_id'];

				$promotion_schema_item = array(
					'tariffid' => $assign['tariffid'],
					'tariff'   => $assign['tariff_name'],
					'value'    => $assign['value'],
					'optional' => $assign['optional'],
					'authtype' => $assign['authtype'],
					'type' => $assign['tarifftype'],
				);

				if (preg_match('/^unlabeled_(?<tariffid>[0-9]+)$/', $assign['label'], $m))
					$label = trans('<!tariffselection>unlabeled_$a', $m['tariffid']);
				else
					$label = $assign['label'];

				if ($promotion_schema_selections[$sid][$label] > 1) {
					if (!isset($promotions[$pid]['schemas'][$sid]['items'][$label]['selection']))
						$promotions[$pid]['schemas'][$sid]['items'][$label]['selection'] = array(
							'items' => array(),
						);
					$promotions[$pid]['schemas'][$sid]['items'][$label]['selection']['required'] =
						empty($assign['optional']);

					$promotions[$pid]['schemas'][$sid]['items'][$label]['selection']['items'][] =
						$promotion_schema_item;
				} else
					$promotions[$pid]['schemas'][$sid]['items'][$label]['single'] = $promotion_schema_item;
			}
		}

		return $promotions;
	}
}
