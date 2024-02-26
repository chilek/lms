<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2024 LMS Developers
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
 */

$this->BeginTrans();

//region Suspension configs
if (!$this->GetOne(
    'SELECT 1 FROM uiconfig WHERE section = ? AND var = ?',
    array(
        'suspensions',
        'default_percentage',
    )
)) {
    $this->Execute(
        'INSERT INTO uiconfig (section, var, value, disabled) VALUES (?, ?, ?, ?)',
        array(
            'suspensions',
            'default_percentage',
            '0',
            0,
        )
    );
}

if (!$this->GetOne(
    'SELECT 1 FROM uiconfig WHERE section = ? AND var = ?',
    array(
        'suspensions',
        'default_value',
    )
)) {
    $this->Execute(
        'INSERT INTO uiconfig (section, var, value, disabled) VALUES (?, ?, ?, ?)',
        array(
            'suspensions',
            'default_value',
            '0',
            0,
        )
    );
}

if (!$this->GetOne(
    'SELECT 1 FROM uiconfig WHERE section = ? AND var = ?',
    array(
        'suspensions',
        'default_netflag',
    )
)) {
    $this->Execute(
        'INSERT INTO uiconfig (section, var, value, disabled) VALUES (?, ?, ?, ?)',
        array(
            'suspensions',
            'default_netflag',
            '0',
            0,
        )
    );
}

if (!$this->GetOne(
    'SELECT 1 FROM uiconfig WHERE section = ? AND var = ?',
    array(
        'suspensions',
        'default_charge_method',
    )
)) {
    $this->Execute(
        'INSERT INTO uiconfig (section, var, value, description, disabled) VALUES (?, ?, ?, ?, ?)',
        array(
            'suspensions',
            'default_charge_method',
            '2',
            '[1-none|2-once|3-periodically]',
            0,
        )
    );
}

if (!$this->GetOne(
    'SELECT 1 FROM uiconfig WHERE section = ? AND var = ?',
    array(
        'suspensions',
        'default_calculation_method',
    )
)) {
    $this->Execute(
        'INSERT INTO uiconfig (section, var, value, description, disabled) VALUES (?, ?, ?, ?, ?)',
        array(
            'suspensions',
            'default_calculation_method',
            '1',
            '[1-percentage|2-value]',
            0,
        )
    );
}
//endregion

/* --------------------------------------------------------
  Structure of table "suspensions"
-------------------------------------------------------- */
if (!$this->ResourceExists('suspensions', LMSDB::RESOURCE_TYPE_TABLE)) {
    $this->Execute(
        "CREATE TABLE suspensions(
            id                 int(11)       NOT NULL auto_increment,
            at                 int(11)       DEFAULT NULL,
            datefrom           int(11)       DEFAULT 0 NOT NULL,
            dateto             int(11)       DEFAULT 0 NOT NULL,
            chargemethod       smallint      NOT NULL,
            calculationmethod  smallint      NOT NULL,
            value              numeric(9, 3) DEFAULT NULL,
            percentage         numeric(3, 2) DEFAULT NULL,
            netflag            smallint      DEFAULT NULL,
            currency           varchar(3)    DEFAULT NULL,
            note               text          DEFAULT NULL,
            customerid         int(11)       DEFAULT NULL,
            taxid              int(11)       DEFAULT NULL,
            PRIMARY KEY(id),
            CONSTRAINT suspensions_customerid_fkey
                FOREIGN KEY (customerid) REFERENCES customers(id) ON DELETE CASCADE ON UPDATE CASCADE,
            CONSTRAINT suspensions_taxid_fkey
                FOREIGN KEY (taxid) REFERENCES taxes(id) ON DELETE CASCADE ON UPDATE CASCADE
        )"
    );
}

/* --------------------------------------------------------
  Structure of table "assignmentsuspensions"
-------------------------------------------------------- */
if (!$this->ResourceExists('assignmentsuspensions', LMSDB::RESOURCE_TYPE_TABLE)) {
    $this->Execute(
        "CREATE TABLE assignmentsuspensions(
            id              int(11) NOT NULL auto_increment,
            suspensionid   int(11) NOT NULL,
            assignmentid   int(11) NOT NULL,
            PRIMARY KEY(id),
            CONSTRAINT suspensions_suspensionid_fkey
                FOREIGN KEY (suspensionid) REFERENCES suspensions(id) ON DELETE CASCADE ON UPDATE CASCADE,
            CONSTRAINT assignments_assignmentid_fkey 
                FOREIGN KEY (assignmentid) REFERENCES assignments(id) ON DELETE CASCADE ON UPDATE CASCADE,
            UNIQUE KEY assignmentsuspensions_assignmentid_suspensionid_ukey (assignmentid, suspensionid)
        )"
    );
}

//transform old assignment suspensions to new ones
define('DISPOSABLE', 0);
define('DAILY', 1);
define('WEEKLY', 2);
define('MONTHLY', 3);
define('QUARTERLY', 4);
define('HALFYEARLY', 7);
define('YEARLY', 5);
define('SUSPENSION_CHARGE_METHOD_ONCE', 2);
define('SUSPENSION_CHARGE_METHOD_PERIODICALLY', 3);
define('TARIFF_FLAG_NET_ACCOUNT', 16);
define('LIABILITY_FLAG_NET_ACCOUT', 16);
define('SUSPENSION_CALCULATION_METHOD_PERCENTAGE', 1);

$allSuspendedAssignments = $this->GetAll(
    'SELECT 
        a.id,
        a.at,
        a.datefrom,
        a.dateto,
        a.period,
        a.customerid,
        (CASE WHEN a.period = ? THEN ? ELSE ? END) AS assignment_charge_method
    FROM assignments a
    WHERE
        a.tariffid IS NULL AND a.liabilityid IS NULL
    ORDER BY a.id DESC',
    array(
        DISPOSABLE,
        SUSPENSION_CHARGE_METHOD_ONCE,
        SUSPENSION_CHARGE_METHOD_PERIODICALLY,
        TARIFF_FLAG_NET_ACCOUNT,
        LIABILITY_FLAG_NET_ACCOUT,
        TARIFF_FLAG_NET_ACCOUNT,
        LIABILITY_FLAG_NET_ACCOUT,
    )
);

if (!empty($allSuspendedAssignments)) {
    $today = strtotime('today');
    foreach ($allSuspendedAssignments as $allSuspendedAssignment) {
        $suspensionExist = $this->getOne('SELECT customerid FROM suspensions WHERE customerid = ?', array($allSuspendedAssignment['customerid']));
        if (empty($suspensionExist)) {
            $assignments = $this->getAll(
                'SELECT 
                    a.id,
                    a.at,
                    a.datefrom,
                    a.dateto,
                    a.period,
                    a.customerid,
                    (CASE WHEN a.period = ? THEN ? ELSE ? END) AS assignment_charge_method
                FROM assignments a
                WHERE 
                    a.suspended = 0
                    AND a.tariffid IS NOT NULL OR a.liabilityid IS NOT NULL
                    AND a.customerid = ?',
                array(
                    DISPOSABLE,
                    SUSPENSION_CHARGE_METHOD_ONCE,
                    SUSPENSION_CHARGE_METHOD_PERIODICALLY,
                    $allSuspendedAssignment['customerid']
                )
            );

            if (!empty($assignments)) {
                foreach ($assignments as $row) {
                    switch ($row['assignment_charge_method']) {
                        case SUSPENSION_CHARGE_METHOD_ONCE:
                            $startdate = $row['datefrom'] > $today ? $row['datefrom'] : $today;
                            [$year, $month, $dom] = explode('/', date('Y/n/j', $startdate));
                            $commingPayDate = 0;
                            $commingPayTimestamp = 0;

                            switch ($row['period']) {
                                case DISPOSABLE:
                                    $commingPayTimestamp = $row['at'];
                                    $commingPayDate = date('Y-m-d', $commingPayTimestamp);
                                    break;
                                case MONTHLY:
                                    $commingPayTimestamp = mktime(0, 0, 0, $month, $row['at'], $year);
                                    $commingPayDate = date('Y-m-d', $commingPayTimestamp);
                                    break;
                                case QUARTERLY:
                                    [$d, $m] = explode('/', $row['at']);
                                    $quarterlyDate1 = mktime(0, 0, 0, $m, $d, $year);
                                    $quarterlyDate2 = strtotime('+3 months', $quarterlyDate1);
                                    $quarterlyDate3 = strtotime('+6 months', $quarterlyDate1);
                                    $quarterlyDate4 = strtotime('+9 months', $quarterlyDate1);

                                    if ($quarterlyDate1 <= $row['datefrom']) {
                                        if ($quarterlyDate2 <= $row['datefrom']) {
                                            if ($quarterlyDate3 <= $row['datefrom']) {
                                                if ($quarterlyDate4 <= $row['datefrom']) {
                                                    $commingPayTimestamp = strtotime('+3 months', $quarterlyDate4);
                                                } else {
                                                    $commingPayTimestamp = $quarterlyDate3;
                                                }
                                            } else {
                                                $commingPayTimestamp = $quarterlyDate3;
                                            }
                                        } else {
                                            $commingPayTimestamp = $quarterlyDate2;
                                        }
                                    } else {
                                        $commingPayTimestamp = $quarterlyDate1;
                                    }
                                    $commingPayDate = date('Y-m-d', $commingPayTimestamp);
                                    break;
                                case HALFYEARLY:
                                    [$d, $m] = explode('/', $row['at']);
                                    $halfyearlyDate1 = mktime(0, 0, 0, $m, $d, $year);
                                    $halfyearlyDate2 = strtotime('+6 months', $halfyearlyDate1);

                                    if ($halfyearlyDate1 <= $row['datefrom']) {
                                        if ($halfyearlyDate2 <= $row['datefrom']) {
                                            $commingPayTimestamp = strtotime('+6 months', $halfyearlyDate2);
                                        } else {
                                            $commingPayTimestamp = $halfyearlyDate2;
                                        }
                                    } else {
                                        $commingPayTimestamp = $halfyearlyDate1;
                                    }
                                    $commingPayDate = date('Y-m-d', $commingPayTimestamp);
                                    break;
                                case YEARLY:
                                    [$d, $m] = explode('/', $row['at']);
                                    $yearlyDate = mktime(0, 0, 0, $m, $d, $year);
                                    if ($yearlyDate <= $row['datefrom']) {
                                        $commingPayTimestamp = strtotime('+1 year', $yearlyDate);
                                    } else {
                                        $commingPayTimestamp = $yearlyDate;
                                    }
                                    $commingPayDate = date('Y-m-d', $commingPayTimestamp);
                                    break;
                            }

                            if (!isset($suspension_at) || $commingPayTimestamp < $suspension_at) {
                                $suspension_at = $commingPayTimestamp;
                            }
                            break;
                        case SUSPENSION_CHARGE_METHOD_PERIODICALLY:
                            $startdate = $row['datefrom'] > $today ? $row['datefrom'] : $today;
                            $dom = date('j', $startdate);
                            $commingPayDay = 0;

                            switch ($row['period']) {
                                case DISPOSABLE:
                                    $commingPayDay = date('j', $row['at']);
                                    break;
                                case MONTHLY:
                                    $commingPayDay = $row['at'];
                                    break;
                                case QUARTERLY:
                                case HALFYEARLY:
                                case YEARLY:
                                    [$d, $m] = explode('/', $row['at']);
                                    $commingPayDay = $d;
                                    break;
                            }

                            if (!isset($suspension_at) || $commingPayDay < $suspension_at) {
                                $suspension_at = $commingPayDay;
                            }
                            break;
                    }
                }
            }

            $args = array(
                'at' => $suspension_at,
                'datefrom' => $allSuspendedAssignment['datefrom'],
                'dateto' => $allSuspendedAssignment['dateto'],
                'chargemethod' => SUSPENSION_CHARGE_METHOD_PERIODICALLY,
                'calculationmethod' => SUSPENSION_CALCULATION_METHOD_PERCENTAGE,
                'customerid' => $allSuspendedAssignment['customerid']
            );

            $this->Execute(
                "INSERT INTO suspensions (at, datefrom, dateto, chargemethod, calculationmethod, customerid)
                VALUES (?, ?, ?, ?, ?, ?)",
                array_values($args)
            );

            $this->Execute("DELETE FROM assignments WHERE id = ?", array($allSuspendedAssignment['id']));
        }
    }
}

$suspendedAssignments = $this->GetAll(
    'SELECT
        a.id,
        a.at,
        a.datefrom,
        a.dateto,
        a.period,
        (CASE WHEN a.liabilityid IS NULL THEN t.currency ELSE l.currency END) AS currency,
        (CASE WHEN a.liabilityid IS NULL THEN t.taxid ELSE l.taxid END) AS taxid,
        (CASE WHEN a.liabilityid IS NULL
                THEN (CASE WHEN t.flags & ? > 0 THEN 1 ELSE 0 END)
                ELSE (CASE WHEN l.flags & ? > 0 THEN 1 ELSE 0 END)
        END) AS netflag,
        (CASE WHEN a.tariffid IS NULL AND a.liabilityid IS NULL THEN 1 ELSE 0 END) AS allsuspended
    FROM assignments a
    LEFT JOIN (
        SELECT tariffs.*,
            taxes.value AS taxrate,
            (CASE WHEN tariffs.flags & ? > 0 THEN tariffs.netvalue ELSE tariffs.value END) AS tvalue
        FROM tariffs
        JOIN taxes ON taxes.id = tariffs.taxid
    ) t ON a.tariffid = t.id
    LEFT JOIN (
        SELECT liabilities.*,
            taxes.value AS taxrate,
            (CASE WHEN liabilities.flags & ? > 0 THEN liabilities.netvalue ELSE liabilities.value END) AS lvalue
        FROM liabilities
        JOIN taxes ON taxes.id = liabilities.taxid
    ) l ON a.liabilityid = l.id
    WHERE suspended = 1',
    array(
        TARIFF_FLAG_NET_ACCOUNT,
        LIABILITY_FLAG_NET_ACCOUT,
        TARIFF_FLAG_NET_ACCOUNT,
        LIABILITY_FLAG_NET_ACCOUT,
    )
);

if (!empty($suspendedAssignments)) {
    $today = strtotime('today');
    [$year, $month, $dom] = explode('/', date('Y/n/j', $today));
    foreach ($suspendedAssignments as $suspendedAssignment) {
        $at = null;
        switch ($suspendedAssignment['period']) {
            case DISPOSABLE:
                $charge_method = SUSPENSION_CHARGE_METHOD_ONCE;
                $at = $suspendedAssignment['at'];
                break;
            case DAILY:
                $charge_method = SUSPENSION_CHARGE_METHOD_PERIODICALLY;
                $at = $dom;
                break;
            case WEEKLY:
                $charge_method = SUSPENSION_CHARGE_METHOD_PERIODICALLY;
                $atdate = date('Y/n/j', mktime(0, 0, 0, 0, $suspendedAssignment['at'] + 5, 0));
                [$wyear, $wmonth, $wdom] = explode('/', date('Y/n/j', $atdate));
                $at = $wdom;
                break;
            case MONTHLY:
                $charge_method = SUSPENSION_CHARGE_METHOD_PERIODICALLY;
                $at = $suspendedAssignment['at'];
                break;
            case QUARTERLY:
                $charge_method = SUSPENSION_CHARGE_METHOD_PERIODICALLY;
                $atdate = sprintf('%02d/%02d', $suspendedAssignment['at'] % 100, $suspendedAssignment['at'] / 100 + 1);
                [$qday, $qmonth] = explode('/', $atdate);
                $at = $qday;
                break;
            case HALFYEARLY:
                $charge_method = SUSPENSION_CHARGE_METHOD_PERIODICALLY;
                $atdate = sprintf('%02d/%02d', $suspendedAssignment['at'] % 100, $suspendedAssignment['at'] / 100 + 1);
                [$hday, $hmonth] = explode('/', $atdate);
                $at = $hday;
                break;
            case YEARLY:
                $charge_method = SUSPENSION_CHARGE_METHOD_PERIODICALLY;
                $atdate = date('d/m', ($suspendedAssignment['at'] - 1) * 86400);
                [$yday, $tmonth] = explode('/', $atdate);
                $at = $yday;
                break;
        }

        $args = array(
            'at' => $at,
            'datefrom' => $suspendedAssignment['datefrom'],
            'dateto' => $suspendedAssignment['dateto'],
            'chargemethod' => $charge_method,
            'calculationmethod' => SUSPENSION_CALCULATION_METHOD_PERCENTAGE,
        );

        $this->Execute(
            "INSERT INTO suspensions (at, datefrom, dateto, chargemethod, calculationmethod)
                VALUES (?, ?, ?, ?, ?)",
            array_values($args)
        );

        $suspensionId = $this->GetLastInsertID('suspensions');

        $this->Execute(
            "INSERT INTO assignmentsuspensions (suspensionid, assignmentid)
                VALUES (?, ?)",
            array(
                $suspensionId,
                $suspendedAssignment['id'],
            )
        );

        $this->Execute(
            "UPDATE assignments SET suspended = 0
            WHERE id = ?",
            array(
                $suspendedAssignment['id']
            )
        );
    }
}

$this->Execute("DROP VIEW vnodealltariffs_tariffs");
$this->Execute("DROP VIEW vnodetariffs_tariffs");
$this->Execute("DROP VIEW vnodetariffs_allsuspended");
$this->Execute("DROP VIEW vassignmentsuspensions");
$this->Execute("DROP VIEW vassignmentssuspensionsvalues");
$this->Execute("DROP VIEW vassignmentssuspensionsgroupcounts");

/* --------------------------------------------------------
  Structure of view "vassignmentssuspensionsgroupcounts"
-------------------------------------------------------- */
$this->Execute("
CREATE VIEW vassignmentssuspensionsgroupcounts AS
    SELECT COUNT(vasg.suspension_assignment_id) AS suspensiongroup_assignments_count,
           vasg.suspension_id AS suspensiongroup_suspension_id
    FROM (SELECT
              (CASE WHEN suspensions.assignment_id IS NOT NULL
                    THEN suspensions.assignment_id
                    ELSE a.id
                  END) AS suspension_assignment_id,
              (CASE WHEN suspensions.suspension_id IS NOT NULL
                    THEN suspensions.suspension_id
                    ELSE suspensions_all.suspension_id
                  END) AS suspension_id
          FROM assignments a
          LEFT JOIN (
              SELECT
                  assignmentsuspensions.assignmentid AS assignment_id,
                  assignmentsuspensions.suspensionid AS suspension_id,
                  suspensions1.id AS id
              FROM assignmentsuspensions
                       JOIN suspensions AS suspensions1 ON suspensions1.id = assignmentsuspensions.suspensionid
                       LEFT JOIN taxes ON taxes.id = suspensions1.taxid
          ) suspensions ON suspensions.assignment_id = a.id
          LEFT JOIN (
              SELECT
                  suspensions2.id AS suspension_id,
                  suspensions2.customerid,
                  (CASE WHEN suspensions2.customerid IS NULL THEN 0 ELSE 1 END) AS suspend_all
              FROM suspensions AS suspensions2
          ) AS suspensions_all ON suspensions_all.customerid = a.customerid
          WHERE suspensions.suspension_id IS NOT NULL OR suspensions_all.suspend_all = 1
         ) AS vasg
    GROUP BY vasg.suspension_id
");

/* --------------------------------------------------------
  Structure of view "vassignmentssuspensionsvalues"
-------------------------------------------------------- */
$this->Execute("
CREATE VIEW vassignmentssuspensionsvalues AS
    SELECT
        suspension_assignment_id AS suspensionvalues_assignment_id,
        suspension_id AS suspensionvalues_suspension_id,
        assignment_base_price AS suspensionvalues_assignment_base_price,
        assignment_tpv_price AS suspensionvalues_assignment_tpv_price,
        assignment_price AS suspensionvalues_assignment_price,
        suspensiongroup_assignments_count AS suspensionvalues_assignments_count
    FROM (
        SELECT
            (CASE WHEN suspensions.assignment_id IS NOT NULL
                THEN suspensions.assignment_id
                ELSE a.id
            END) AS suspension_assignment_id,
            (CASE WHEN suspensions.suspension_id IS NOT NULL
                THEN suspensions.suspension_id
                ELSE suspensions_all.suspension_id
            END) AS suspension_id,
            (CASE WHEN suspensions.suspension_id IS NOT NULL
                THEN suspensions.suspensiongroup_assignments_count
                ELSE suspensions_all.suspensiongroup_assignments_count
            END) AS suspensiongroup_assignments_count,
            ROUND(((((100 - a.pdiscount) * (CASE WHEN a.liabilityid IS NULL THEN tvalue ELSE lvalue END)) / 100) - a.vdiscount), 3) AS assignment_base_price,
            assignments_tpvariants.tpvprice AS assignment_tpv_price,
            (CASE WHEN assignments_tpvariants.tpvprice IS NULL
                    THEN ROUND(((((100 - a.pdiscount) * (CASE WHEN a.liabilityid IS NULL THEN tvalue ELSE lvalue END)) / 100) - a.vdiscount), 3)
                ELSE assignments_tpvariants.tpvprice
            END) AS assignment_price
        FROM assignments a
        LEFT JOIN (
            SELECT 
                tariffs.*,
                taxes.value AS taxrate, taxes.label AS taxlabel,
                (CASE WHEN tariffs.flags & 16 > 0 THEN tariffs.netvalue ELSE tariffs.value END) AS tvalue
            FROM tariffs
            JOIN taxes ON taxes.id = tariffs.taxid
        ) t ON a.tariffid = t.id
        LEFT JOIN (
            SELECT 
                liabilities.*,
                taxes.value AS taxrate, taxes.label AS taxlabel,
                (CASE WHEN liabilities.flags & 16 > 0 THEN liabilities.netvalue ELSE liabilities.value END) AS lvalue
            FROM liabilities
            JOIN taxes ON taxes.id = liabilities.taxid
        ) l ON a.liabilityid = l.id
        LEFT JOIN (
            SELECT
                assignmentsuspensions.assignmentid AS assignment_id,
                assignmentsuspensions.suspensionid AS suspension_id,
                suspensions1.id AS id,
                vasg.suspensiongroup_assignments_count AS suspensiongroup_assignments_count
            FROM assignmentsuspensions
            JOIN suspensions AS suspensions1 ON suspensions1.id = assignmentsuspensions.suspensionid
            JOIN vassignmentssuspensionsgroupcounts vasg ON vasg.suspensiongroup_suspension_id = suspensions1.id
            LEFT JOIN taxes ON taxes.id = suspensions1.taxid
        ) suspensions ON suspensions.assignment_id = a.id
        LEFT JOIN (
          SELECT
              suspensions2.id AS suspension_id,
              suspensions2.customerid,
              (CASE WHEN suspensions2.customerid IS NULL THEN 0 ELSE 1 END) AS suspend_all,
              vasg.suspensiongroup_assignments_count AS suspensiongroup_assignments_count
          FROM suspensions AS suspensions2
          JOIN vassignmentssuspensionsgroupcounts vasg ON vasg.suspensiongroup_suspension_id = suspensions2.id
        ) AS suspensions_all ON suspensions_all.customerid = a.customerid
        LEFT JOIN (
            SELECT
                tpv.*
            FROM assignments a
            JOIN (
                SELECT
                    tariffpricevariants.quantity_threshold AS tpv_quantity_threshold, tariffs.id AS tpv_tariffid,
                    (CASE WHEN tariffs.flags & 16 > 0 THEN tariffpricevariants.net_price ELSE tariffpricevariants.gross_price END) AS tpvprice
                FROM tariffs
                JOIN tariffpricevariants ON tariffs.id = tariffpricevariants.tariffid
            ) tpv ON a.tariffid = tpv.tpv_tariffid AND tpv.tpv_quantity_threshold <= a.count AND tpv.tpv_tariffid = a.tariffid
            ORDER BY tpv.tpv_quantity_threshold DESC LIMIT 1
        ) AS assignments_tpvariants ON a.tariffid = assignments_tpvariants.tpv_tariffid
        WHERE suspensions.suspension_id IS NOT NULL OR suspensions_all.suspend_all = 1
    ) AS vasv
");

/* --------------------------------------------------------
  Structure of view "vassignmentsuspensions"
-------------------------------------------------------- */

$this->Execute("
CREATE VIEW vassignmentsuspensions AS
    SELECT
        (CASE WHEN suspensions.assignment_id IS NOT NULL
            THEN suspensions.assignment_id
            ELSE a.id
        END) AS suspension_assignment_id,
        (CASE WHEN suspensions.suspension_id IS NOT NULL OR suspensions_all.suspend_all = 1 THEN 1 ELSE 0 END) AS suspended,
        (CASE WHEN suspensions.suspension_id IS NULL AND suspensions_all.suspend_all = 1 THEN 1 ELSE 0 END) AS suspension_suspend_all,
        (CASE WHEN suspensions.suspension_id IS NOT NULL
            THEN suspensions.suspension_id
            ELSE suspensions_all.suspension_id
        END) AS suspension_id,
        (CASE WHEN suspensions.suspension_id IS NOT NULL
            THEN suspensions.at
            ELSE suspensions_all.at
        END) AS suspension_at,
        (CASE WHEN suspensions.suspension_id IS NOT NULL
            THEN suspensions.datefrom
            ELSE suspensions_all.datefrom
        END) AS suspension_datefrom,
        (CASE WHEN suspensions.suspension_id IS NOT NULL
            THEN suspensions.dateto
            ELSE suspensions_all.dateto
        END) AS suspension_dateto,
        (CASE WHEN suspensions.suspension_id IS NOT NULL
            THEN suspensions.chargemethod
            ELSE suspensions_all.chargemethod
        END) AS suspension_charge_method,
        (CASE WHEN suspensions.suspension_id IS NOT NULL
            THEN suspensions.calculationmethod
            ELSE suspensions_all.calculationmethod
        END) AS suspension_calculation_method,
        (CASE WHEN suspensions.suspension_id IS NOT NULL
            THEN suspensions.value
            ELSE suspensions_all.value
        END) AS suspension_value,
        (CASE WHEN suspensions.suspension_id IS NOT NULL
            THEN suspensions.percentage
            ELSE suspensions_all.percentage
        END) AS suspension_percentage,
        (CASE WHEN suspensions.suspension_id IS NOT NULL
            THEN suspensions.netflag
            ELSE suspensions_all.netflag
        END) AS suspension_netflag,
        (CASE WHEN suspensions.suspension_id IS NOT NULL
            THEN suspensions.currency
            ELSE suspensions_all.currency
        END) AS suspension_currency,
        (CASE WHEN suspensions.suspension_id IS NOT NULL
            THEN suspensions.taxid
            ELSE suspensions_all.taxid
        END) AS suspension_tax_id,
        (CASE WHEN suspensions.suspension_id IS NOT NULL
            THEN suspensions.note
            ELSE suspensions_all.note
        END) AS suspension_note,
        (CASE WHEN suspensions.customerid IS NOT NULL
            THEN suspensions_all.customerid
            ELSE a.customerid
        END) AS suspension_customer_id,
        (CASE WHEN suspensions.taxrate IS NOT NULL
            THEN suspensions.taxrate
            ELSE suspensions_all.taxrate
        END) AS suspension_taxrate,
        (CASE WHEN suspensions.taxlabel IS NOT NULL
            THEN suspensions.taxlabel
            ELSE suspensions_all.taxlabel
        END) AS suspension_taxlabel,
        vasv.suspensionvalues_assignment_base_price,
        vasv.suspensionvalues_assignment_tpv_price,
        vasv.suspensionvalues_assignment_price,
        vasv.suspensionvalues_assignments_count,
        (CASE
            WHEN suspensions.chargemethod = 3 OR suspensions.chargemethod = 2
                    OR suspensions_all.chargemethod = 3 OR suspensions_all.chargemethod = 2
                THEN (CASE
                    WHEN suspensions.calculationmethod = 1
                        THEN vasv.suspensionvalues_assignment_price *
                             (CASE
                                WHEN suspensions.percentage IS NOT NULL
                                    THEN ROUND(suspensions.percentage / 100, 2)
                                ELSE
                                    ROUND((SELECT CAST(uiconfig.value AS numeric) 
                                    FROM uiconfig 
                                    WHERE uiconfig.section = 'suspensions' 
                                    AND uiconfig.var = 'default_percentage'
                                    AND uiconfig.configid IS NULL 
                                    LIMIT 1) / 100, 2)
                             END)
                    WHEN suspensions_all.calculationmethod = 1
                        THEN vasv.suspensionvalues_assignment_price *
                             (CASE
                                 WHEN suspensions_all.percentage IS NOT NULL
                                    THEN ROUND(suspensions_all.percentage / 100, 2)
                                 ELSE
                                    ROUND((SELECT CAST(uiconfig.value AS numeric) 
                                    FROM uiconfig 
                                    WHERE uiconfig.section = 'suspensions' 
                                    AND uiconfig.var = 'default_percentage'
                                    AND uiconfig.configid IS NULL 
                                    LIMIT 1) / 100, 2)
                             END)
                    WHEN suspensions.calculationmethod = 2
                        THEN
                             (CASE
                                  WHEN suspensions.value IS NOT NULL
                                      THEN ROUND(suspensions.value / vasv.suspensionvalues_assignments_count, 2)
                                  ELSE
                                    ROUND((SELECT CAST(uiconfig.value AS numeric) 
                                    FROM uiconfig 
                                    WHERE uiconfig.section = 'suspensions' 
                                    AND uiconfig.var = 'default_percentage'
                                    AND uiconfig.configid IS NULL 
                                    LIMIT 1) / 100, 2)
                             END)
                    WHEN suspensions_all.calculationmethod = 2
                        THEN
                            (CASE
                                WHEN suspensions_all.value IS NOT NULL
                                    THEN ROUND(suspensions_all.value / vasv.suspensionvalues_assignments_count, 2)
                                ELSE
                                    ROUND((SELECT CAST(uiconfig.value AS numeric) 
                                    FROM uiconfig 
                                    WHERE uiconfig.section = 'suspensions' 
                                    AND uiconfig.var = 'default_percentage'
                                    AND uiconfig.configid IS NULL 
                                    LIMIT 1) / 100, 2)
                            END)
                    END)
            WHEN suspensions.chargemethod = 1 OR suspensions_all.chargemethod = 1
            THEN 0
        END) AS suspension_price
    FROM assignments a
    LEFT JOIN (
        SELECT
            tariffs.*,
            taxes.value AS taxrate, taxes.label AS taxlabel,
            (CASE WHEN tariffs.flags & 16 > 0 THEN tariffs.netvalue ELSE tariffs.value END) AS tvalue
        FROM tariffs
        JOIN taxes ON taxes.id = tariffs.taxid
    ) t ON a.tariffid = t.id
    LEFT JOIN (
        SELECT
            tpv.*
        FROM assignments a
        JOIN (
            SELECT
                tariffpricevariants.quantity_threshold AS tpv_quantity_threshold,
                tariffs.id AS tpv_tariffid,
               (CASE WHEN tariffs.flags & 16 > 0 THEN tariffpricevariants.net_price ELSE tariffpricevariants.gross_price END) AS tpvprice
            FROM tariffs
            JOIN tariffpricevariants ON tariffs.id = tariffpricevariants.tariffid
        ) tpv ON a.tariffid = tpv.tpv_tariffid AND tpv.tpv_quantity_threshold <= a.count AND tpv.tpv_tariffid = a.tariffid
        ORDER BY tpv.tpv_quantity_threshold DESC LIMIT 1
    ) AS assignments_tpvariants ON a.tariffid = assignments_tpvariants.tpv_tariffid
    LEFT JOIN (
        SELECT
            liabilities.*,
            taxes.value AS taxrate, taxes.label AS taxlabel,
            (CASE WHEN liabilities.flags & 16 > 0 THEN liabilities.netvalue ELSE liabilities.value END) AS lvalue
        FROM liabilities
        JOIN taxes ON taxes.id = liabilities.taxid
    ) l ON a.liabilityid = l.id
    LEFT JOIN (
        SELECT
            assignmentsuspensions.id AS assignmentsuspension_id,
            assignmentsuspensions.assignmentid AS assignment_id,
            assignmentsuspensions.suspensionid AS suspension_id,
            suspensions1.id AS id,
            suspensions1.at, suspensions1.datefrom, suspensions1.dateto, suspensions1.chargemethod, suspensions1.calculationmethod,
            suspensions1.value, suspensions1.percentage, suspensions1.netflag, suspensions1.currency, suspensions1.note, suspensions1.taxid,
            suspensions1.customerid,
            taxes.value AS taxrate, taxes.label AS taxlabel
        FROM assignmentsuspensions
        JOIN suspensions AS suspensions1 ON suspensions1.id = assignmentsuspensions.suspensionid
        LEFT JOIN taxes ON taxes.id = suspensions1.taxid
    ) suspensions ON suspensions.assignment_id = a.id
    LEFT JOIN (
        SELECT suspensions2.id AS suspension_id, suspensions2.at, suspensions2.datefrom, suspensions2.dateto,
               suspensions2.chargemethod, suspensions2.calculationmethod,
               suspensions2.value, suspensions2.percentage, suspensions2.netflag, suspensions2.currency, suspensions2.note, suspensions2.taxid,
               suspensions2.customerid,
               (CASE WHEN suspensions2.customerid IS NULL THEN 0 ELSE 1 END) AS suspend_all,
               taxes.value AS taxrate, taxes.label AS taxlabel
        FROM suspensions AS suspensions2
        LEFT JOIN taxes ON taxes.id = suspensions2.taxid
    ) AS suspensions_all ON suspensions_all.customerid = a.customerid
    LEFT JOIN vassignmentssuspensionsvalues vasv ON vasv.suspensionvalues_assignment_id = a.id
    WHERE suspensions.suspension_id IS NOT NULL OR suspensions_all.suspend_all = 1
");

$this->Execute("
CREATE VIEW vnodetariffs_tariffs AS
    SELECT n.id AS nodeid,
        ROUND(SUM(t.downrate * a.count)) AS downrate,
        ROUND(SUM(t.downceil * a.count)) AS downceil,
        SUM(t.down_burst_time) AS down_burst_time,
        SUM(t.down_burst_threshold) AS down_burst_threshold,
        SUM(t.down_burst_limit) AS down_burst_limit,
        ROUND(SUM(t.uprate * a.count)) AS uprate,
        ROUND(SUM(t.upceil * a.count)) AS upceil,
        SUM(t.up_burst_time) AS up_burst_time,
        SUM(t.up_burst_threshold) AS up_burst_threshold,
        SUM(t.up_burst_limit) AS up_burst_limit,
        ROUND(SUM(COALESCE(t.downrate_n, t.downrate) * a.count)) AS downrate_n,
        ROUND(SUM(COALESCE(t.downceil_n, t.downceil) * a.count)) AS downceil_n,
        SUM(COALESCE(t.down_burst_time_n, t.down_burst_time)) AS down_burst_time_n,
        SUM(COALESCE(t.down_burst_threshold_n, t.down_burst_threshold)) AS down_burst_threshold_n,
        SUM(COALESCE(t.down_burst_limit_n, t.down_burst_limit)) AS down_burst_limit_n,
        ROUND(SUM(COALESCE(t.uprate_n, t.uprate) * a.count)) AS uprate_n,
        ROUND(SUM(COALESCE(t.upceil_n, t.upceil) * a.count)) AS upceil_n,
        SUM(COALESCE(t.up_burst_time_n, t.up_burst_time)) AS up_burst_time_n,
        SUM(COALESCE(t.up_burst_threshold_n, t.up_burst_threshold)) AS up_burst_threshold_n,
        SUM(COALESCE(t.up_burst_limit_n, t.up_burst_limit)) AS up_burst_limit_n
    FROM nodes n
    JOIN nodeassignments na ON na.nodeid = n.id
    JOIN assignments a ON a.id = na.assignmentid
    JOIN tariffs t ON t.id = a.tariffid
    LEFT JOIN vassignmentsuspensions vas ON vas.suspension_assignment_id = a.id
        AND vas.suspension_datefrom <= UNIX_TIMESTAMP()
        AND (vas.suspension_dateto >= UNIX_TIMESTAMP() OR vas.suspension_dateto = 0)
        AND a.datefrom <= UNIX_TIMESTAMP()
        AND (a.dateto > UNIX_TIMESTAMP() OR a.dateto = 0)
    WHERE vas.suspended IS NULL AND a.commited = 1
        AND a.datefrom <= UNIX_TIMESTAMP()
        AND (a.dateto = 0 OR a.dateto >= UNIX_TIMESTAMP())
        AND (t.downrate > 0 OR t.downceil > 0 OR t.uprate > 0 OR t.upceil > 0)
    GROUP BY n.id
");

$this->Execute("
CREATE VIEW vnodealltariffs_tariffs AS
    SELECT n.id AS nodeid,
        ROUND(SUM(t.downrate * a.count)) AS downrate,
        ROUND(SUM(t.downceil * a.count)) AS downceil,
        SUM(t.down_burst_time) AS down_burst_time,
        SUM(t.down_burst_threshold) AS down_burst_threshold,
        SUM(t.down_burst_limit) AS down_burst_limit,
        ROUND(SUM(t.uprate * a.count)) AS uprate,
        ROUND(SUM(t.upceil * a.count)) AS upceil,
        SUM(t.up_burst_time) AS up_burst_time,
        SUM(t.up_burst_threshold) AS up_burst_threshold,
        SUM(t.up_burst_limit) AS up_burst_limit,
        ROUND(SUM(COALESCE(t.downrate_n, t.downrate) * a.count)) AS downrate_n,
        ROUND(SUM(COALESCE(t.downceil_n, t.downceil) * a.count)) AS downceil_n,
        SUM(COALESCE(t.down_burst_time_n, t.down_burst_time)) AS down_burst_time_n,
        SUM(COALESCE(t.down_burst_threshold_n, t.down_burst_threshold)) AS down_burst_threshold_n,
        SUM(COALESCE(t.down_burst_limit_n, t.down_burst_limit)) AS down_burst_limit_n,
        ROUND(SUM(COALESCE(t.uprate_n, t.uprate) * a.count)) AS uprate_n,
        ROUND(SUM(COALESCE(t.upceil_n, t.upceil) * a.count)) AS upceil_n,
        SUM(COALESCE(t.up_burst_time_n, t.up_burst_time)) AS up_burst_time_n,
        SUM(COALESCE(t.up_burst_threshold_n, t.up_burst_threshold)) AS up_burst_threshold_n,
        SUM(COALESCE(t.up_burst_limit_n, t.up_burst_limit)) AS up_burst_limit_n
    FROM assignments a
    JOIN tariffs t ON t.id = a.tariffid
    JOIN vnodealltariffs_nodes n ON n.ownerid = a.customerid
    LEFT JOIN vassignmentsuspensions vas ON vas.suspension_assignment_id = a.id
        AND vas.suspension_datefrom <= UNIX_TIMESTAMP()
        AND (vas.suspension_dateto >= UNIX_TIMESTAMP() OR vas.suspension_dateto = 0)
        AND a.datefrom <= UNIX_TIMESTAMP()
        AND (a.dateto > UNIX_TIMESTAMP() OR a.dateto = 0)
    WHERE vas.suspended IS NULL AND a.commited = 1
        AND a.datefrom <= UNIX_TIMESTAMP()
        AND (a.dateto = 0 OR a.dateto >= UNIX_TIMESTAMP())
        AND (t.downrate > 0 OR t.downceil > 0 OR t.uprate > 0 OR t.upceil > 0)
        AND n.id NOT IN (SELECT nodeid FROM nodeassignments)
        AND a.id NOT IN (SELECT assignmentid FROM nodeassignments)
    GROUP BY n.id
");

$this->Execute("ALTER TABLE assignments DROP COLUMN suspended");

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2024052400', 'dbversion'));

$this->CommitTrans();
