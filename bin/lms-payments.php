#!/usr/bin/env php
<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2024 LMS Developers
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

$script_parameters = array(
    'test' => 't',
    'section:' => 's:',
    'fakedate:' => 'f:',
    'fake-date:' => null,
    'force-date:' => null,
    'issue-date:' => null,
    'customerid:' => null,
    'division:' => null,
    'customergroups:' => 'g:',
    'customer-status:' => null,
    'tariff-tags:' => null,
    'voip-cdr-only' => null,
);

$script_help = <<<EOF
-t, --test                      no changes are made to database;
-s, --section=<section-name>    section name from lms configuration where settings
                                are stored
-f, --fakedate, --fake-date, --force-date=YYYY/MM/DD       override system date;
    --issue-date=YYYY/MM/DD     override system date for generated cash record issue date;
    --customerid=<id>           limit assignments to specifed customer
    --division=<shortname>
                                limit assignments to customers which belong to specified
                                division
-g, --customergroups=<group1,group2,...>
                                allow to specify customer groups to which customers
                                should be assigned
    --customer-status=<status1,status2,...>
                                take assignment of customers with specified status only
    --tariff-tags=<tariff-tag1,tariff-tag-2,...>
                                create financial charges using only tariffs which have
                                assigned specified tariff tags
    --voip-cdr-only
                                issue only voip billing record settlements
EOF;

require_once('script-options.php');

$SYSLOG = SYSLOG::getInstance();

// Initialize Session, Auth and LMS classes

$AUTH = null;
$LMS = new LMS($DB, $AUTH, $SYSLOG);

$plugin_manager = LMSPluginManager::getInstance();
$LMS->setPluginManager($plugin_manager);

$divisionid = isset($options['division']) ? $LMS->getDivisionIdByShortName($options['division']) : null;
if (!empty($divisionid)) {
    ConfigHelper::setFilter($divisionid);
}

$config_section = isset($options['section']) && preg_match('/^[a-z0-9-_]+$/i', $options['section'])
    ? $options['section']
    : 'payments';

$deadline = ConfigHelper::getConfig($config_section . '.deadline', 14);
$sdate_next = ConfigHelper::checkConfig($config_section . '.saledate_next_month');
$paytype = ConfigHelper::getConfig($config_section . '.paytype', PAYTYPE_TRANSFER);
$comment = ConfigHelper::getConfig($config_section . '.comment', "Tariff %tariff - %attribute subscription for period %period");
$comment_by_service_types = array(
    SERVICE_OTHER => ConfigHelper::getConfig($config_section . '.comment_other', $comment),
    SERVICE_INTERNET => ConfigHelper::getConfig($config_section . '.comment_internet', $comment),
    SERVICE_HOSTING => ConfigHelper::getConfig($config_section . '.comment_hosting', $comment),
    SERVICE_SERVICE => ConfigHelper::getConfig($config_section . '.comment_service', $comment),
    SERVICE_PHONE => ConfigHelper::getConfig($config_section . '.comment_phone', $comment),
    SERVICE_TV => ConfigHelper::getConfig($config_section . '.comment_tv', $comment),
    SERVICE_TRANSMISSION => ConfigHelper::getConfig($config_section . '.comment_transmission', $comment),
);
$comment_phone_no_numbers = ConfigHelper::getConfig($config_section . '.comment_phone_no_numbers', $comment);
$backward_comment = ConfigHelper::getConfig($config_section . '.backward_comment', $comment);
$backward_comment_by_service_types = array(
    SERVICE_OTHER => ConfigHelper::getConfig($config_section . '.backward_comment_other', $backward_comment),
    SERVICE_INTERNET => ConfigHelper::getConfig($config_section . '.backward_comment_internet', $backward_comment),
    SERVICE_HOSTING => ConfigHelper::getConfig($config_section . '.backward_comment_hosting', $backward_comment),
    SERVICE_SERVICE => ConfigHelper::getConfig($config_section . '.backward_comment_service', $backward_comment),
    SERVICE_PHONE => ConfigHelper::getConfig($config_section . '.backward_comment_phone', $backward_comment),
    SERVICE_TV => ConfigHelper::getConfig($config_section . '.backward_comment_tv', $backward_comment),
    SERVICE_TRANSMISSION => ConfigHelper::getConfig($config_section . '.backward_comment_transmission', $backward_comment),
);
$backward_comment_phone_no_numbers = ConfigHelper::getConfig($config_section . '.backward_comment_phone_no_numbers', $backward_comment);
$backward_on_the_last_day = ConfigHelper::checkConfig($config_section . '.backward_on_the_last_day');
$s_comment = ConfigHelper::getConfig($config_section . '.settlement_comment', $comment);
$s_comment_by_service_types = array(
    SERVICE_OTHER => ConfigHelper::getConfig($config_section . '.settlement_comment_other', $s_comment),
    SERVICE_INTERNET => ConfigHelper::getConfig($config_section . '.settlement_comment_internet', $s_comment),
    SERVICE_HOSTING => ConfigHelper::getConfig($config_section . '.settlement_comment_hosting', $s_comment),
    SERVICE_SERVICE => ConfigHelper::getConfig($config_section . '.settlement_comment_service', $s_comment),
    SERVICE_PHONE => ConfigHelper::getConfig($config_section . '.settlement_comment_phone', $s_comment),
    SERVICE_TV => ConfigHelper::getConfig($config_section . '.settlement_comment_tv', $s_comment),
    SERVICE_TRANSMISSION => ConfigHelper::getConfig($config_section . '.settlement_comment_transmission', $s_comment),
);
$s_comment_phone_no_numbers = ConfigHelper::getConfig($config_section . '.settlement_comment_phone_no_numbers', $s_comment);
$s_backward_comment = ConfigHelper::getConfig($config_section . '.settlement_backward_comment', $s_comment);
$s_backward_comment_by_service_types = array(
    SERVICE_OTHER => ConfigHelper::getConfig($config_section . '.settlement_backward_comment_other', $s_backward_comment),
    SERVICE_INTERNET => ConfigHelper::getConfig($config_section . '.settlement_backward_comment_internet', $s_backward_comment),
    SERVICE_HOSTING => ConfigHelper::getConfig($config_section . '.settlement_backward_comment_hosting', $s_backward_comment),
    SERVICE_SERVICE => ConfigHelper::getConfig($config_section . '.settlement_backward_comment_service', $s_backward_comment),
    SERVICE_PHONE => ConfigHelper::getConfig($config_section . '.settlement_backward_comment_phone', $s_backward_comment),
    SERVICE_TV => ConfigHelper::getConfig($config_section . '.settlement_backward_comment_tv', $s_backward_comment),
    SERVICE_TRANSMISSION => ConfigHelper::getConfig($config_section . '.settlement_backward_comment_transmission', $s_backward_comment),
);
$s_backward_comment_phone_no_numbers = ConfigHelper::getConfig($config_section . '.settlement_backward_comment_phone_no_numbers', $s_backward_comment);
$suspension_description = ConfigHelper::getConfig($config_section . '.suspension_description', '');
$suspension_percentage = ConfigHelper::getConfig('payments.suspension_percentage', ConfigHelper::getConfig('finances.suspension_percentage', 0));
$unit_name = trans(ConfigHelper::getConfig($config_section . '.default_unit_name'));
$check_invoices = ConfigHelper::checkConfig($config_section . '.check_invoices');
$proforma_generates_commitment = ConfigHelper::checkConfig('phpui.proforma_invoice_generates_commitment');
$delete_old_assignments_after_days = intval(ConfigHelper::getConfig($config_section . '.delete_old_assignments_after_days', 0));
$prefer_settlement_only = ConfigHelper::checkConfig($config_section . '.prefer_settlement_only');
$prefer_netto = ConfigHelper::checkConfig($config_section . '.prefer_netto');
$customergroups = ConfigHelper::getConfig($config_section . '.customergroups', '', true);
$tariff_tags = ConfigHelper::getConfig($config_section . '.tariff_tags', '', true);

$reward_penalty_deadline_grace_days = intval(ConfigHelper::getConfig($config_section . '.reward_penalty_deadline_grace_days'));
$reward_penalty_period_start_check = ConfigHelper::checkConfig($config_section . '.reward_penalty_period_start_check', true);
$reward_penalty_last_deadline_check_only = ConfigHelper::checkConfig($config_section . '.reward_penalty_last_deadline_check_only');

$force_telecom_service_flag = ConfigHelper::checkConfig('invoices.force_telecom_service_flag', true);
$check_customer_vat_payer_flag_for_telecom_service = ConfigHelper::checkConfig('invoices.check_customer_vat_payer_flag_for_telecom_service');

$billing_document_template = ConfigHelper::getConfig($config_section . '.billing_document_template', '');

$auto_payments = ConfigHelper::checkConfig($config_section . '.auto_payments');

$use_comment_for_liabilities = ConfigHelper::checkConfig($config_section . '.use_comment_for_liabilities');

$allowed_customer_status =
Utils::determineAllowedCustomerStatus(
    $options['customer-status'] ?? ConfigHelper::getConfig($config_section . '.allowed_customer_status', '')
);

if (empty($allowed_customer_status)) {
    $customer_status_condition = '';
} else {
    $customer_status_condition = ' AND c.status IN (' . implode(',', $allowed_customer_status) . ')';
}

if (isset($options['force-date'])) {
    $fakedate = $options['force-date'];
} elseif (isset($options['fake-date'])) {
    $fakedate = $options['fake-date'];
} elseif (isset($options['fakedate'])) {
    $fakedate = $options['fakedate'];
} else {
    $fakedate = null;
}
$issuedate = $options['issue-date'] ?? null;
if (isset($options['customerid'])) {
    if (preg_match('/^[0-9]+(,[0-9]+)*$/', $options['customerid'])) {
        $customerid = array_map(
            function ($customerid) {
                return intval($customerid);
            },
            explode(',', $options['customerid'])
        );
    } else {
        $customerid = null;
    }
} else {
    $customerid = null;
}
$voip_cdr_only = isset($options['voip-cdr-only']);

if (empty($fakedate)) {
    $currtime = time();
} else {
    $currtime = strtotime($fakedate);
}
$today = strtotime('today', $currtime);
$issuetime = isset($issuedate) ? strtotime($issuedate) : $currtime;
[$year, $month, $dom] = explode('/', date('Y/n/j', $currtime));
[$backward_year, $backward_month] = explode('/', date('Y/m', strtotime($year . '/' . $month. '/' . $dom . ' - 1 month')));
$weekday = date('N', $currtime);
$yearday = date('z', $currtime) + 1;
$last_dom = date('j', mktime(0, 0, 0, $month + 1, 0, $year)) == date('j', $currtime);

if (is_leap_year($year) && $yearday > 31 + 28) {
    $yearday -= 1;
}

if ($month == 1 || $month == 4 || $month == 7 || $month == 10) {
    $quarter = $dom;
} elseif ($month == 2 || $month == 5 || $month == 8 || $month == 11) {
    $quarter = $dom + 100;
} else {
    $quarter = $dom + 200;
}

if ($month > 6) {
    $halfyear = $dom + ($month - 7) * 100;
} else {
    $halfyear = $dom + ($month - 1) * 100;
}

$date_format = ConfigHelper::getConfig($config_section . '.date_format', '%Y/%m/%d');

$forward_periods = array(
    DAILY      => Utils::strftime($date_format, mktime(12, 0, 0, $month, $dom, $year)),
    WEEKLY     => Utils::strftime($date_format, mktime(12, 0, 0, $month, $dom, $year)).' - '.Utils::strftime($date_format, mktime(12, 0, 0, $month, $dom+6, $year)),
    MONTHLY    => Utils::strftime($date_format, mktime(12, 0, 0, $month, $dom, $year)).' - '.Utils::strftime($date_format, mktime(12, 0, 0, $month+1, $dom-1, $year)),
    QUARTERLY  => Utils::strftime($date_format, mktime(12, 0, 0, $month, $dom, $year)).' - '.Utils::strftime($date_format, mktime(12, 0, 0, $month+3, $dom-1, $year)),
    HALFYEARLY => Utils::strftime($date_format, mktime(12, 0, 0, $month, $dom, $year)).' - '.Utils::strftime($date_format, mktime(12, 0, 0, $month+6, $dom-1, $year)),
    YEARLY     => Utils::strftime($date_format, mktime(12, 0, 0, $month, $dom, $year)).' - '.Utils::strftime($date_format, mktime(12, 0, 0, $month, $dom-1, $year+1)),
    DISPOSABLE => Utils::strftime($date_format, mktime(12, 0, 0, $month, $dom, $year)),
);

$forward_aligned_periods = array(
    DAILY      => $forward_periods[DAILY],
    WEEKLY     => $forward_periods[WEEKLY],
    MONTHLY    => Utils::strftime($date_format, mktime(12, 0, 0, $month, 1, $year)).' - '.Utils::strftime($date_format, mktime(12, 0, 0, $month+1, 0, $year)),
    QUARTERLY  => Utils::strftime($date_format, mktime(12, 0, 0, $month, 1, $year)).' - '.Utils::strftime($date_format, mktime(12, 0, 0, $month+3, 0, $year)),
    HALFYEARLY => Utils::strftime($date_format, mktime(12, 0, 0, $month, 1, $year)).' - '.Utils::strftime($date_format, mktime(12, 0, 0, $month+6, 0, $year)),
    YEARLY     => Utils::strftime($date_format, mktime(12, 0, 0, $month, 1, $year)).' - '.Utils::strftime($date_format, mktime(12, 0, 0, $month, 0, $year+1)),
    DISPOSABLE => $forward_periods[DISPOSABLE],
);

$d = $dom + ($backward_on_the_last_day ? 1 : 0);
$backward_periods = array(
    DAILY      => Utils::strftime($date_format, mktime(12, 0, 0, $month, $d-1, $year)),
    WEEKLY     => Utils::strftime($date_format, mktime(12, 0, 0, $month, $d-7, $year))  .' - '.Utils::strftime($date_format, mktime(12, 0, 0, $month, $d-1, $year)),
    MONTHLY    => Utils::strftime($date_format, mktime(12, 0, 0, $month-1, $d, $year))  .' - '.Utils::strftime($date_format, mktime(12, 0, 0, $month, $d-1, $year)),
    QUARTERLY  => Utils::strftime($date_format, mktime(12, 0, 0, $month-3, $d, $year))  .' - '.Utils::strftime($date_format, mktime(12, 0, 0, $month, $d-1, $year)),
    HALFYEARLY => Utils::strftime($date_format, mktime(12, 0, 0, $month-6, $d, $year))  .' - '.Utils::strftime($date_format, mktime(12, 0, 0, $month, $d-1, $year)),
    YEARLY     => Utils::strftime($date_format, mktime(12, 0, 0, $month, $d, $year-1)).' - '.Utils::strftime($date_format, mktime(12, 0, 0, $month, $d-1, $year)),
    DISPOSABLE => Utils::strftime($date_format, mktime(12, 0, 0, $month, $d-1, $year))
);

$last_sunday = strtotime('last Sunday '.date("Y-m-d"));

$backward_aligned_periods = array(
    DAILY      => $backward_periods[DAILY],
    WEEKLY     => Utils::strftime($date_format, $last_sunday-518400)                        .' - '.Utils::strftime($date_format, $last_sunday),
    MONTHLY    => Utils::strftime($date_format, mktime(12, 0, 0, $month-1, 1, $year))  .' - '.Utils::strftime($date_format, mktime(12, 0, 0, $month, 0, $year)),
    QUARTERLY  => Utils::strftime($date_format, mktime(12, 0, 0, $month-3, 1, $year))  .' - '.Utils::strftime($date_format, mktime(12, 0, 0, $month, 0, $year)),
    HALFYEARLY => Utils::strftime($date_format, mktime(12, 0, 0, $month-6, 1, $year))  .' - '.Utils::strftime($date_format, mktime(12, 0, 0, $month, 0, $year)),
    YEARLY     => Utils::strftime($date_format, mktime(12, 0, 0, $month, 1, $year-1)).' - '.Utils::strftime($date_format, mktime(12, 0, 0, $month, 0, $year)),
    DISPOSABLE => $backward_periods[DISPOSABLE]
);

// Special case, ie. you have 01.01.2005-01.31.2005 on invoice, but invoice/
// assignment is made not January, the 1st:

$current_month = Utils::strftime($date_format, mktime(12, 0, 0, $month, 1, $year))." - ".Utils::strftime($date_format, mktime(12, 0, 0, $month + 1, 0, $year));
$previous_month = Utils::strftime($date_format, mktime(12, 0, 0, $month - 1, 1, $year))." - ".Utils::strftime($date_format, mktime(12, 0, 0, $month, 0, $year));
$current_period = date('m/Y', mktime(12, 0, 0, $month, 1, $year));
$next_period = date('m/Y', mktime(12, 0, 0, $month + 1, 1, $year));
$prev_period = date('m/Y', mktime(12, 0, 0, $month - 1, 1, $year));

// sale date setting
$saledate = $issuetime;
if ($sdate_next) {
    $saledate = mktime(12, 0, 0, $month + 1, 1, $year);
}

// calculate start and end of numbering period
function get_period($period)
{
    global $dom, $month, $year, $weekday;

    if (empty($period)) {
        $period = YEARLY;
    }
    $start = 0;
    $end = 0;

    switch ($period) {
        case DAILY:
            $start = mktime(0, 0, 0, $month, $dom, $year);
            $end = mktime(0, 0, 0, $month, $dom + 1, $year);
            break;
        case WEEKLY:
            $startweek = $dom - $weekday + 1;
            $start = mktime(0, 0, 0, $month, $startweek, $year);
            $end = mktime(0, 0, 0, $month, $startweek + 7, $year);
            break;
        case MONTHLY:
            $start = mktime(0, 0, 0, $month, 1, $year);
            $end = mktime(0, 0, 0, $month + 1, 1, $year);
            break;
        case QUARTERLY:
            if ($month <= 3) {
                $startmonth = 1;
            } elseif ($month <= 6) {
                $startmonth = 4;
            } elseif ($month <= 9) {
                $startmonth = 7;
            } else {
                $startmonth = 10;
            }
            $start = mktime(0, 0, 0, $startmonth, 1, $year);
            $end = mktime(0, 0, 0, $startmonth + 3, 1, $year);
            break;
        case HALFYEARLY:
            if ($month <= 6) {
                $startmonth = 1;
            } else {
                $startmonth = 7;
            }
            $start = mktime(0, 0, 0, $startmonth, 1, $year);
            $end = mktime(0, 0, 0, $startmonth + 6, 1, $year);
            break;
        case CONTINUOUS:
            $start = mktime(0, 0, 0, 1, 1, 1970);
            $end = mktime(0, 0, 0, $month, $dom + 1, $year);
            break;
        default:
            $start = mktime(0, 0, 0, 1, 1, $year);
            $end = mktime(0, 0, 0, 1, 1, $year + 1);
    }
    return array('start' => $start, 'end' => $end);
}

$plans = array();
$periods = array(
    0 => YEARLY,
);
$query = "SELECT n.id, n.period, doctype,
        COALESCE(a.divisionid, 0) AS divid,
        COALESCE(n.customertype, -1) AS customertype,
        n.isdefault
    FROM numberplans n
    LEFT JOIN numberplanassignments a ON a.planid = n.id
    WHERE doctype IN ?
        AND n.datefrom <= ?
        AND (n.dateto = 0 OR n.dateto >= ?)";
$results = $DB->GetAll(
    $query,
    array(
        array(
            DOC_INVOICE, DOC_INVOICE_PRO, DOC_DNOTE,
        ),
        $currtime,
        $currtime,
    )
);
if (!empty($results)) {
    foreach ($results as $row) {
        if ($row['isdefault']) {
            $plans[$row['divid']][$row['doctype']][$row['customertype']] = $row['id'];
        }
        $periods[$row['id']] = ($row['period'] ?: YEARLY);
    }
}

// prepare customergroups in sql query
if (isset($options['customergroups'])) {
    $customergroups = $options['customergroups'];
}
if (!empty($customergroups)) {
    $ORs = preg_split("/([\s]+|[\s]*,[\s]*)/", mb_strtoupper($customergroups), -1, PREG_SPLIT_NO_EMPTY);
    $customergroup_ORs = array();
    foreach ($ORs as $OR) {
        $ANDs = preg_split("/([\s]*\+[\s]*)/", $OR, -1, PREG_SPLIT_NO_EMPTY);
        $customergroup_ANDs_regular = array();
        $customergroup_ANDs_inversed = array();
        foreach ($ANDs as $AND) {
            if (strpos($AND, '!') === false) {
                $customergroup_ANDs_regular[] = $AND;
            } else {
                $customergroup_ANDs_inversed[] = substr($AND, 1);
            }
        }
        $customergroup_ORs[] = '('
            . (empty($customergroup_ANDs_regular) ? '1 = 1' : "EXISTS (SELECT COUNT(*) FROM customergroups
                JOIN vcustomerassignments ON vcustomerassignments.customergroupid = customergroups.id
                WHERE vcustomerassignments.customerid = %customerid_alias%
                AND UPPER(customergroups.name) IN ('" . implode("', '", $customergroup_ANDs_regular) . "')
                HAVING COUNT(*) = " . count($customergroup_ANDs_regular) . ')')
            . (empty($customergroup_ANDs_inversed) ? '' : " AND NOT EXISTS (SELECT COUNT(*) FROM customergroups
                JOIN vcustomerassignments ON vcustomerassignments.customergroupid = customergroups.id
                WHERE vcustomerassignments.customerid = %customerid_alias%
                AND UPPER(customergroups.name) IN ('" . implode("', '", $customergroup_ANDs_inversed) . "')
                HAVING COUNT(*) > 0)")
            . ')';
    }
    $customergroups = ' AND (' . implode(' OR ', $customergroup_ORs) . ')';
}

// prepare tariff tags in sql query
if (isset($options['tariff-tags'])) {
    $tariff_tags = $options['tariff-tags'];
}
if (!empty($tariff_tags)) {
    $ORs = preg_split("/([\s]+|[\s]*,[\s]*)/", mb_strtoupper($tariff_tags), -1, PREG_SPLIT_NO_EMPTY);
    $tariff_tags_ORs = array();
    foreach ($ORs as $OR) {
        $ANDs = preg_split("/([\s]*\+[\s]*)/", $OR, -1, PREG_SPLIT_NO_EMPTY);
        $tariff_tag_ANDs_regular = array();
        $tariff_tag_ANDs_inversed = array();
        foreach ($ANDs as $AND) {
            if (strpos($AND, '!') === false) {
                $tariff_tag_ANDs_regular[] = $AND;
            } else {
                $tariff_tag_ANDs_inversed[] = substr($AND, 1);
            }
        }
        $tariff_tag_ORs[] = '('
            . (empty($tariff_tag_ANDs_regular) ? '1 = 1' : "EXISTS (SELECT COUNT(*) FROM tarifftags
                JOIN tariffassignments ON tariffassignments.tarifftagid = tarifftags.id
                WHERE tariffassignments.tariffid = t.id
                AND UPPER(tarifftags.name) IN ('" . implode("', '", $tariff_tag_ANDs_regular) . "')
                HAVING COUNT(*) = " . count($tariff_tag_ANDs_regular) . ')')
            . (empty($tariff_tag_ANDs_inversed) ? '' : " AND NOT EXISTS (SELECT COUNT(*) FROM tarifftags
                JOIN tariffassignments ON tariffassignments.tarifftagid = tarifftags.id
                WHERE tariffassignments.tariffid = t.id
                AND UPPER(tarifftags.name) IN ('" . implode("', '", $tariff_tag_ANDs_inversed) . "')
                HAVING COUNT(*) > 0)")
            . ')';
    }
    $tariff_tags = ' AND (a.tariffid IS NULL OR (' . implode(' OR ', $tariff_tag_ORs) . '))';
}

$test = isset($options['test']);
if ($test) {
    echo "WARNING! You are using test mode." . PHP_EOL;
}

$doms = array($dom);
if ($last_dom) {
    $doms[] = 0;
}

if ($voip_cdr_only) {
    $services = array();
} else {
    // let's go, fetch *ALL* assignments in given day
    $query = "SELECT
            a.id,
            a.tariffid,
            a.liabilityid,
            a.customerid,
            a.recipient_address_id,
            ca3.ten AS recipient_ten,
            ca3.entity_type AS recipient_type,
            (CASE WHEN ca2.address_id IS NULL THEN ca1.address_id ELSE ca2.address_id END) AS post_address_id,
            a.period,
            a.backwardperiod,
            a.at,
            a.suspended,
            a.settlement,
            a.datefrom,
            a.dateto,
            a.pdiscount,
            a.vdiscount,
            a.invoice,
            a.separatedocument,
            a.separateitem,
            a.note,
            c.type AS customertype,
            (CASE WHEN c.type = ? THEN 0 ELSE (CASE WHEN a.liabilityid IS NULL
                THEN (CASE WHEN t.flags & ? > 0 THEN 1 ELSE 0 END)
                ELSE (CASE WHEN l.flags & ? > 0 THEN 1 ELSE 0 END)
            END) END) AS splitpayment,
            (CASE WHEN a.liabilityid IS NULL
                THEN (CASE WHEN t.flags & ? > 0 THEN 1 ELSE 0 END)
                ELSE (CASE WHEN l.flags & ? > 0 THEN 1 ELSE 0 END)
            END) AS netflag,
            (CASE WHEN a.liabilityid IS NULL THEN t.taxcategory ELSE l.taxcategory END) AS taxcategory,
            t.description AS description,
            a.id AS assignmentid,
            c.divisionid,
            c.paytime,
            c.paytype,
            c.flags AS customerflags,
            a.paytime AS a_paytime,
            a.paytype AS a_paytype,
            a.numberplanid,
            a.attribute,
            p.name AS promotion_name,
            ps.name AS promotion_schema_name,
            ps.length AS promotion_schema_length,
            d.inv_paytime AS d_paytime,
            d.inv_paytype AS d_paytype,
            t.period AS t_period,
            t.numberplanid AS tariffnumberplanid,
            t.flags,
            (CASE WHEN cc1.type IS NULL THEN 0 ELSE 1 END) AS einvoice,
            (CASE WHEN cc2.type IS NULL THEN 0 ELSE 1 END) AS mail_marketing,
            (CASE WHEN cc3.type IS NULL THEN 0 ELSE 1 END) AS sms_marketing,
            (CASE WHEN a.tariffid IS NULL THEN l.type ELSE t.type END) AS tarifftype,
            (CASE WHEN a.liabilityid IS NULL THEN t.name ELSE l.name END) AS name,
            (CASE WHEN a.liabilityid IS NULL THEN t.taxid ELSE l.taxid END) AS taxid,
            (CASE WHEN a.liabilityid IS NULL THEN t.prodid ELSE l.prodid END) AS prodid,
            voipphones.phones,
            ROUND(((((100 - a.pdiscount) * (CASE WHEN a.liabilityid IS NULL THEN tvalue ELSE lvalue END)) / 100) - a.vdiscount), 3) AS price,
            (CASE WHEN a.liabilityid IS NULL THEN t.taxrate ELSE l.taxrate END) AS taxrate,
            (CASE WHEN a.liabilityid IS NULL THEN t.currency ELSE l.currency END) AS currency,
            a.count AS count,
            (SELECT COUNT(id) FROM assignments
                WHERE customerid = c.id AND tariffid IS NULL AND liabilityid IS NULL
                AND datefrom <= $currtime
                AND (dateto > $currtime OR dateto = 0)) AS allsuspended,
            doc.type AS doctype,
            doc.fullnumber AS docnumber,
            doc.cdate AS docdate
        FROM assignments a
        JOIN customers c ON a.customerid = c.id
        LEFT JOIN customerconsents cc1 ON cc1.customerid = c.id AND cc1.type = " . CCONSENT_EINVOICE . "
        LEFT JOIN customerconsents cc2 ON cc2.customerid = c.id AND cc2.type = " . CCONSENT_MAIL_MARKETING . "
        LEFT JOIN customerconsents cc3 ON cc3.customerid = c.id AND cc3.type = " . CCONSENT_SMS_MARKETING . "
        LEFT JOIN customer_addresses ca1 ON ca1.customer_id = c.id AND ca1.type = " . BILLING_ADDRESS . "
        LEFT JOIN customer_addresses ca2 ON ca2.customer_id = c.id AND ca2.type = " . POSTAL_ADDRESS . "
        LEFT JOIN customer_addresses ca3 ON ca3.customer_id = c.id AND ca3.address_id = a.recipient_address_id
        LEFT JOIN promotionschemas ps ON ps.id = a.promotionschemaid
        LEFT JOIN promotions p ON p.id = ps.promotionid
        LEFT JOIN documents doc ON doc.id = a.docid
        LEFT JOIN (
            SELECT
                tariffs.*,
                taxes.value AS taxrate,
                (CASE WHEN tariffs.flags & ? > 0 THEN tariffs.netvalue ELSE tariffs.value END) AS tvalue
            FROM tariffs
            JOIN taxes ON taxes.id = tariffs.taxid
        ) t ON a.tariffid = t.id
        LEFT JOIN (
            SELECT
                liabilities.*,
                taxes.value AS taxrate,
                (CASE WHEN liabilities.flags & ? > 0 THEN liabilities.netvalue ELSE liabilities.value END) AS lvalue
            FROM liabilities
            JOIN taxes ON taxes.id = liabilities.taxid
        ) l ON a.liabilityid = l.id
        LEFT JOIN (
            SELECT
                vna.assignment_id, " . $DB->GroupConcat('vn.phone', ',') . " AS phones
            FROM voip_number_assignments vna
            LEFT JOIN voip_numbers vn ON vn.id = vna.number_id
            GROUP BY vna.assignment_id
        ) voipphones ON voipphones.assignment_id = a.id
        LEFT JOIN divisions d ON d.id = c.divisionid
        WHERE " . (empty($customerid) ? '1 = 1' : 'c.id IN (' . implode(', ', $customerid) . ')')
            . $customer_status_condition
            . ($divisionid ? ' AND c.divisionid = ' . $divisionid : '')
            . " AND a.commited = 1
            AND ((a.period = ? AND at = ?)
                OR ((a.period = ?
                OR (a.period = ? AND at = ?)
                OR (a.period = ? AND at IN ?)
                OR (a.period = ? AND at = ?)
                OR (a.period = ? AND at = ?)
                OR (a.period = ? AND at = ?))
                AND a.datefrom <= ? AND (a.dateto > ? OR a.dateto = 0)))"
            . ($customergroups ? str_replace('%customerid_alias%', 'c.id', $customergroups) : '')
            . ($tariff_tags ?: '')
        . " ORDER BY a.customerid, a.recipient_address_id, a.invoice, a.paytime, c.paytime, d.inv_paytime,
            a.paytype, c.paytype, d.inv_paytype, a.numberplanid, t.numberplanid, a.separatedocument, a.separateitem, currency, netflag, price DESC, a.id";

    $services = $DB->GetAll(
        $query,
        array(
            CTYPES_PRIVATE,
            TARIFF_FLAG_SPLIT_PAYMENT,
            LIABILITY_FLAG_SPLIT_PAYMENT,
            TARIFF_FLAG_NET_ACCOUNT,
            LIABILITY_FLAG_NET_ACCOUT,
            TARIFF_FLAG_NET_ACCOUNT,
            LIABILITY_FLAG_NET_ACCOUT,
            DISPOSABLE, $today, DAILY, WEEKLY, $weekday, MONTHLY, $doms, QUARTERLY, $quarter, HALFYEARLY, $halfyear, YEARLY, $yearday,
            $currtime, $currtime
        )
    );
}

$billing_invoice_description = ConfigHelper::getConfig($config_section . '.billing_invoice_description', 'Phone calls between %backward_periods (for %phones)');
$billing_invoice_separate_fractions = ConfigHelper::checkConfig($config_section . '.billing_invoice_separate_fractions');
$empty_billings = ConfigHelper::checkConfig('voip.empty_billings');

$query = "SELECT
			a.id, a.tariffid, a.customerid,
			a.recipient_address_id,
			ca3.ten AS recipient_ten,
			ca3.entity_type AS recipient_type,
			(CASE WHEN ca2.address_id IS NULL THEN ca1.address_id ELSE ca2.address_id END) AS post_address_id,
			a.period, a.backwardperiod, a.at, a.suspended, a.settlement, a.datefrom,
			0 AS pdiscount, 0 AS vdiscount, a.invoice,
			a.separatedocument,
			a.separateitem,
			a.note,
			c.type AS customertype,
			t.type AS tarifftype,
			t.taxcategory AS taxcategory,
			t.description AS description, a.id AS assignmentid,
			c.divisionid, c.paytype, c.paytime, c.flags AS customerflags,
			a.paytime AS a_paytime, a.paytype AS a_paytype, a.numberplanid, a.attribute,
			p.name AS promotion_name, ps.name AS promotion_schema_name, ps.length AS promotion_schema_length,
			d.inv_paytime AS d_paytime, d.inv_paytype AS d_paytype, t.period AS t_period, t.numberplanid AS tariffnumberplanid,
			0 AS flags,
			t.taxid AS taxid, '' as prodid,
			COALESCE(voipcost.value, 0) AS price,
			COALESCE(voipcost.totaltime, 0) AS call_time,
			" . ($billing_invoice_separate_fractions ? ' COALESCE(voipcost.call_count, 0) AS call_count, COALESCE(voipcost.call_fraction, \'\') AS call_fraction , ' : '') . "
			taxes.value AS taxrate,
            (CASE WHEN c.type = ?
                THEN 0
                ELSE (CASE WHEN t.flags & ? > 0
                    THEN 1
                    ELSE 0
                END)
            END) AS splitpayment,
            (CASE WHEN t.flags & ? > 0
                THEN 1
                ELSE 0
            END) AS netflag,
			t.currency, voipphones.phones,
			'set' AS liabilityid, '$billing_invoice_description' AS name,
			? AS count,
			(SELECT COUNT(id)
				FROM assignments
				WHERE
					customerid  = c.id    AND
					tariffid    IS NULL   AND
					liabilityid IS NULL   AND
					datefrom <= $currtime AND
					(dateto > $currtime OR dateto = 0)) AS allsuspended,
			(CASE WHEN EXISTS (SELECT 1 FROM customerconsents cc WHERE cc.customerid = c.id AND cc.type IN ?) THEN 1 ELSE 0 END) AS billingconsent,
                doc.type AS doctype,
                doc.fullnumber AS docnumber,
                doc.cdate AS docdate
			FROM assignments a
            JOIN tariffs t ON t.id = a.tariffid
            JOIN taxes ON taxes.id = t.taxid
            LEFT JOIN promotionschemas ps ON ps.id = a.promotionschemaid
            LEFT JOIN promotions p ON p.id = ps.promotionid
			JOIN customers c ON (a.customerid = c.id)
            LEFT JOIN customer_addresses ca1 ON ca1.customer_id = c.id AND ca1.type = " . BILLING_ADDRESS . "
            LEFT JOIN customer_addresses ca2 ON ca2.customer_id = c.id AND ca2.type = " . POSTAL_ADDRESS . "
            LEFT JOIN customer_addresses ca3 ON ca3.customer_id = c.id AND ca3.address_id = a.recipient_address_id
            LEFT JOIN documents doc ON doc.id = a.docid
			" . ($empty_billings ? 'LEFT ' : '') . "JOIN (
				SELECT ROUND(sum(price), 2) AS value,
					SUM(vc.billedtime) AS totaltime,
					" . ($billing_invoice_separate_fractions ? ' COUNT(vc.*) AS call_count, vc.fraction AS call_fraction, ' : '')
                    . "va.ownerid AS customerid,
					a2.id AS assignmentid
				FROM voip_cdr vc
				JOIN voipaccounts va ON va.id = vc.callervoipaccountid AND vc.direction = " . BILLING_RECORD_DIRECTION_OUTGOING . " OR va.id = vc.calleevoipaccountid AND vc.direction = " . BILLING_RECORD_DIRECTION_INCOMING . "
				JOIN voip_numbers vn ON vn.voip_account_id = va.id
					AND (
						(
							vn.voip_account_id = vc.callervoipaccountid
							AND
							vn.phone = vc.caller
							AND
							vc.direction = " . BILLING_RECORD_DIRECTION_OUTGOING . "
						) OR (
							vn.voip_account_id = vc.calleevoipaccountid
							AND
							vn.phone = vc.callee
							AND
							vc.direction = " . BILLING_RECORD_DIRECTION_INCOMING . "
						)
					)
				JOIN voip_number_assignments vna ON vna.number_id = vn.id
				JOIN assignments a2 ON a2.id = vna.assignment_id
				JOIN tariffs t ON t.id = a2.tariffid AND t.type = ?
				WHERE (
					(
						vc.call_start_time >= (CASE a2.period
							WHEN " . YEARLY     . ' THEN ' . mktime(0, 0, 0, $month, 1, $year-1) . '
							WHEN ' . HALFYEARLY . ' THEN ' . mktime(0, 0, 0, $month-6, 1, $year)   . '
							WHEN ' . QUARTERLY  . ' THEN ' . mktime(0, 0, 0, $month-3, 1, $year)   . '
							WHEN ' . MONTHLY    . ' THEN ' . mktime(0, 0, 0, $month-1, 1, $year)   . '
							WHEN ' . DISPOSABLE . ' THEN ' . strtotime('today', $currtime) . "
						END)
						AND
						vc.call_start_time < (CASE a2.period
							WHEN " . YEARLY     . ' THEN ' . mktime(0, 0, 0, $month, 1, $year) . '
							WHEN ' . HALFYEARLY . ' THEN ' . mktime(0, 0, 0, $month, 1, $year) . '
							WHEN ' . QUARTERLY  . ' THEN ' . mktime(0, 0, 0, $month, 1, $year) . '
							WHEN ' . MONTHLY    . ' THEN ' . mktime(0, 0, 0, $month, 1, $year) . '
							WHEN ' . DISPOSABLE . ' THEN ' . strtotime('tomorrow', $currtime) . "
						END)
					) OR (
						vc.call_start_time + totaltime >= (CASE a2.period
							WHEN " . YEARLY     . ' THEN ' . mktime(0, 0, 0, $month, 1, $year-1) . '
							WHEN ' . HALFYEARLY . ' THEN ' . mktime(0, 0, 0, $month-6, 1, $year)   . '
							WHEN ' . QUARTERLY  . ' THEN ' . mktime(0, 0, 0, $month-3, 1, $year)   . '
							WHEN ' . MONTHLY    . ' THEN ' . mktime(0, 0, 0, $month-1, 1, $year)   . '
							WHEN ' . DISPOSABLE . ' THEN ' . strtotime('today', $currtime) . "
						END)
						AND
						vc.call_start_time + totaltime < (CASE a2.period
							WHEN " . YEARLY     . ' THEN ' . mktime(0, 0, 0, $month, 1, $year) . '
							WHEN ' . HALFYEARLY . ' THEN ' . mktime(0, 0, 0, $month, 1, $year) . '
							WHEN ' . QUARTERLY  . ' THEN ' . mktime(0, 0, 0, $month, 1, $year) . '
							WHEN ' . MONTHLY    . ' THEN ' . mktime(0, 0, 0, $month, 1, $year) . '
							WHEN ' . DISPOSABLE . ' THEN ' . strtotime('tomorrow', $currtime) . "
						END)
					)
				)
				GROUP BY va.ownerid, a2.id" . ($billing_invoice_separate_fractions ? ', vc.fraction' : '') . "
			) voipcost ON voipcost.customerid = a.customerid AND voipcost.assignmentid = a.id
			LEFT JOIN (
				SELECT vna2.assignment_id, " . $DB->GroupConcat('vn2.phone', ',') . " AS phones
				FROM voip_number_assignments vna2
				LEFT JOIN voip_numbers vn2 ON vn2.id = vna2.number_id
				GROUP BY vna2.assignment_id
			) voipphones ON voipphones.assignment_id = a.id
			LEFT JOIN divisions d ON (d.id = c.divisionid)
	    WHERE " . (empty($customerid) ? '1 = 1' : 'c.id IN (' . implode(', ', $customerid) . ')')
           . $customer_status_condition
           . ($divisionid ? ' AND c.divisionid = ' . $divisionid : '')
           . " AND t.type = ? AND
	      a.commited = 1 AND
		  ((a.period = ? AND at = ?) OR
		  ((a.period = ? OR
		  (a.period  = ? AND at = ?) OR
		  (a.period  = ? AND at IN ?) OR
		  (a.period  = ? AND at = ?) OR
		  (a.period  = ? AND at = ?) OR
		  (a.period  = ? AND at = ?)) AND
		   a.datefrom <= ? AND
		  (a.dateto = 0 OR a.dateto > (CASE a.period
			WHEN " . YEARLY . ' THEN ' . mktime(0, 0, 0, $month, 1, $year - 1) . "
			WHEN " . HALFYEARLY . ' THEN ' . mktime(0, 0, 0, $month - 6, 1, $year)   . "
			WHEN " . QUARTERLY  . ' THEN ' . mktime(0, 0, 0, $month - 3, 1, $year)   . "
			WHEN " . MONTHLY . ' THEN ' . mktime(0, 0, 0, $month - 1, 1, $year)
        . " END))))"
        . ($customergroups ? str_replace('%customerid_alias%', 'c.id', $customergroups) : '')
        . ($tariff_tags ?: '')
    ." ORDER BY a.customerid, a.recipient_address_id, a.invoice, a.paytime, c.paytime, d.inv_paytime,
        a.paytype, c.paytype, d.inv_paytype, a.numberplanid, a.separatedocument, a.separateitem, currency, netflag, voipcost.value DESC, a.id";

$billings = $DB->GetAll(
    $query,
    array(
        CTYPES_PRIVATE,
        TARIFF_FLAG_SPLIT_PAYMENT,
        TARIFF_FLAG_NET_ACCOUNT,
        1,
        array(CCONSENT_FULL_PHONE_BILLING, CCONSENT_SIMPLIFIED_PHONE_BILLING),
        SERVICE_PHONE,
        SERVICE_PHONE,
        DISPOSABLE, $today, DAILY, WEEKLY, $weekday, MONTHLY, $doms, QUARTERLY, $quarter, HALFYEARLY, $halfyear, YEARLY, $yearday,
        $currtime,
    )
);

$assigns = array();

if ($billings) {
    if (empty($services)) {
        $assigns = $billings;
    } else {
        // intelligent merge of service and billing assignment records
        $billing_idx = 0;
        $billing_count = count($billings);
        foreach ($services as $service_idx => &$service) {
            $assigns[] = $service;
            if ($billing_idx == $billing_count || $service['tarifftype'] != SERVICE_PHONE) {
                continue;
            }

            $service_customerid = $service['customerid'];

            while ($billing_idx < $billing_count && $billings[$billing_idx]['customerid'] < $service_customerid) {
                $billing_idx++;
            }
            if ($billing_idx === $billing_count) {
                continue;
            }

            $old_billing_idx = $billing_idx;

            while ($billing_idx < $billing_count && $billings[$billing_idx]['customerid'] == $service_customerid
                && $service['id'] != $billings[$billing_idx]['id']) {
                $billing_idx++;
            }

            while ($billing_idx < $billing_count && $billings[$billing_idx]['customerid'] == $service_customerid
                && $service['id'] == $billings[$billing_idx]['id']) {
                $assigns[] = $billings[$billing_idx];
                //$billing_idx = $old_billing_idx;
                $billing_idx++;
            }
            $billing_idx = $old_billing_idx;
        }
        unset($service);
    }
} else {
    $assigns = $services;
}
unset($services);

if (!empty($assigns)) {
    // get dominating link technology per customer assignments when customer
    // node are directly connected to operator network device
    $assignment_linktechnologies = $DB->GetAllByKey("SELECT a.id, b.technology, MAX(b.technologycount) AS technologycount
		FROM assignments a
		JOIN (
			SELECT a.id, n.linktechnology AS technology, COUNT(n.linktechnology) AS technologycount
			FROM nodeassignments na
				JOIN assignments a ON a.id = na.assignmentid
				JOIN tariffs t ON t.id = a.tariffid
				JOIN nodes n ON n.id = na.nodeid
			WHERE n.linktechnology > 0 AND n.ownerid IS NOT NULL
			GROUP BY a.id, n.linktechnology
		) b ON b.id = a.id
		GROUP BY a.id, b.technology
		ORDER BY a.id", 'id');
    if (empty($assignment_linktechnologies)) {
        $assignment_linktechnologies = array();
    }

    // get dominating link technology per customer assignments when customer
    // node or customer network devices nodes are connected to operator through customer subnetwork
    // ************
    // get assignments which match to nodes or network device nodes in customer subnetworks
    $node_assignments = $DB->GetAllByKey("SELECT " . $DB->GroupConcat('na.assignmentid', ',', true) . " AS assignments,
			na.nodeid
		FROM nodeassignments na
		JOIN nodes n ON n.id = na.nodeid
		JOIN assignments a ON a.id = na.assignmentid
		LEFT JOIN netdevices nd ON nd.id = n.netdev
		WHERE nd.ownerid IS NOT NULL AND ((n.ownerid IS NULL AND n.netdev IS NOT NULL)
			OR n.ownerid IS NOT NULL)
			AND a.suspended = 0
			AND a.period IN (" . implode(',', array(YEARLY, HALFYEARLY, QUARTERLY, MONTHLY, DISPOSABLE)) . ")
			AND a.datefrom < ?NOW? AND (a.dateto = 0 OR a.dateto > ?NOW?)
			AND NOT EXISTS (
				SELECT id FROM assignments aa
				WHERE aa.customerid = (CASE WHEN n.ownerid IS NULL THEN nd.ownerid ELSE n.ownerid END)
					AND aa.tariffid IS NULL AND aa.liabilityid IS NULL
					AND aa.datefrom < ?NOW?
					AND (aa.dateto > ?NOW? OR aa.dateto = 0)
			)
		GROUP BY na.nodeid", 'nodeid');
    if (empty($node_assignments)) {
        $node_assignments = array();
    } else {
        foreach ($node_assignments as $nodeid => $assignments) {
            $node_assignments[$nodeid] = explode(',', $assignments['assignments']);
        }
    }

    if (!empty($node_assignments)) {
        // search for links between operator network devices and customer network devices
        $uni_links = $DB->GetAllByKey(
            "SELECT nl.id AS netlinkid, nl.technology AS technology,
					c.id AS customerid,
					(CASE WHEN ndsrc.ownerid IS NULL THEN nl.src ELSE nl.dst END) AS operator_netdevid,
					(CASE WHEN ndsrc.ownerid IS NULL THEN nl.dst ELSE nl.dst END) AS netdevid
				FROM netlinks nl
				JOIN netdevices ndsrc ON ndsrc.id = nl.src
				JOIN netdevices nddst ON nddst.id = nl.dst
				JOIN customers c ON (ndsrc.ownerid IS NULL AND c.id = nddst.ownerid)
					OR (nddst.ownerid IS NULL AND c.id = ndsrc.ownerid)
				WHERE nl.technology > 0 AND ((ndsrc.ownerid IS NULL AND nddst.ownerid IS NOT NULL)
					OR (nddst.ownerid IS NULL AND ndsrc.ownerid IS NOT NULL))
				ORDER BY nl.id",
            'netlinkid'
        );
        if (!empty($uni_links)) {
            function find_nodes_for_netdev($netlink, &$customer_nodes, &$customer_netlinks, &$processed_netlinks)
            {
                $customerid = $netlink['customerid'];
                $netdevid = $netlink['netdevid'];
                $netlinkid = $netlink['netlinkid'];

                if (isset($processed_netlinks[$netlinkid])) {
                    return array();
                }

                $processed_netlinks[$netlinkid] = $netlinkid;

                if (isset($customer_nodes[$customerid . '_' . $netdevid])) {
                    $nodeids = explode(',', $customer_nodes[$customerid . '_' . $netdevid]['nodeids']);
                } else {
                    $nodeids = array();
                }

                if (!empty($customer_netlinks)) {
                    foreach ($customer_netlinks as &$customer_netlink) {
                        if ($customer_netlink['src'] == $netdevid) {
                            $next_netdevid = $customer_netlink['dst'];
                        } else if ($customer_netlink['dst'] == $netdevid) {
                            $next_netdevid = $customer_netlink['src'];
                        } else {
                            continue;
                        }

                        if (!isset($processed_netlinks[$customer_netlink['netlink']])) {
                            $nodeids = array_merge($nodeids, find_nodes_for_netdev(
                                array(
                                    'netdevid' => $next_netdevid,
                                    'customerid' => $customerid,
                                    'netlinkid' => $customer_netlink['netlink'],
                                ),
                                $customer_nodes,
                                $customer_netlinks,
                                $processed_netlinks
                            ));
                        }
                    }
                    unset($customer_netlink);
                }

                return $nodeids;
            }

            $processed_netlinks = array();

            $customer_netlinks = $DB->GetAllByKey(
                "SELECT " . $DB->Concat('nl.src', "'_'", 'nl.dst') . " AS netlink,
                    nl.src,
                    nl.dst
                FROM netlinks nl
                JOIN netdevices ndsrc ON ndsrc.id = nl.src
                JOIN netdevices nddst ON nddst.id = nl.dst
                WHERE ndsrc.ownerid IS NOT NULL AND nddst.ownerid IS NOT NULL
                    AND ndsrc.ownerid = nddst.ownerid",
                'netlink'
            );

            $customer_nodes = $DB->GetAllByKey(
                "SELECT " . $DB->GroupConcat('n.id') . " AS nodeids,
						" . $DB->Concat('CASE WHEN n.ownerid IS NULL THEN nd.ownerid ELSE n.ownerid END', "'_'", 'n.netdev') . " AS customerid_netdev
					FROM nodes n
					LEFT JOIN netdevices nd ON nd.id = n.netdev AND n.ownerid IS NULL AND nd.ownerid IS NOT NULL
					WHERE n.ownerid IS NOT NULL OR nd.ownerid IS NOT NULL
						AND EXISTS (
							SELECT na.id FROM nodeassignments na
							JOIN assignments a ON a.id = na.assignmentid
							WHERE na.nodeid = n.id AND a.suspended = 0
								AND a.period IN (" . implode(',', array(YEARLY, HALFYEARLY, QUARTERLY, MONTHLY, DISPOSABLE)) . ")
								AND a.datefrom < ?NOW? AND (a.dateto = 0 OR a.dateto > ?NOW?)
						)
						AND NOT EXISTS (
							SELECT id FROM assignments aa
							WHERE aa.customerid = (CASE WHEN n.ownerid IS NULL THEN nd.ownerid ELSE n.ownerid END)
								AND aa.tariffid IS NULL AND aa.liabilityid IS NULL
								AND aa.datefrom < ?NOW?
								AND (aa.dateto > ?NOW? OR aa.dateto = 0)
						)
					GROUP BY customerid_netdev",
                'customerid_netdev'
            );

            // collect customer node/node-netdev identifiers connected to customer subnetwork
            // and then fill assignment linktechnologies relations
            foreach ($uni_links as $netlinkid => &$netlink) {
                $nodes = find_nodes_for_netdev(
                    $netlink,
                    $customer_nodes,
                    $customer_netlinks,
                    $processed_netlinks
                );
                if (!empty($nodes)) {
                    foreach ($nodes as $nodeid) {
                        if (isset($node_assignments[$nodeid])) {
                            foreach ($node_assignments[$nodeid] as $assignmentid) {
                                $assignment_linktechnologies[$assignmentid] = array(
                                    'id' => $assignmentid,
                                    'technology' => $netlink['technology'],
                                    'technologycount' => 1,
                                );
                            }
                        }
                    }
                }
            }
            unset($netlink);
            unset($uni_links);

            unset($customer_netlinks);
            unset($customer_nodes);
        }
    }
}

$suspended = 0;
$numbers = array();
$customernumbers = array();
$numbertemplates = array();
$invoices = array();
$telecom_services = array();
$currencies = array();
$netflags = array();
$doctypes = array();
$paytimes = array();
$paytypes = array();
$addresses = array();
$numberplans = array();
$separatedocuments = array();
$divisions = array();

$old_locale = setlocale(LC_NUMERIC, '0');
setlocale(LC_NUMERIC, 'C');

$result = $LMS->ExecuteHook(
    'payments_before_assignment_loop',
    array(
        'assignments' => $assigns,
        'date' => sprintf('%04d/%02d/%02d', $year, $month, $dom),
    )
);
if ($result['assignments']) {
    $assigns = $result['assignments'];
}

setlocale(LC_NUMERIC, $old_locale);

// find assignments with tariff reward/penalty flag
// and check if customer applies to this
$reward_to_check = array();
$reward_period_to_check = array();
if (!empty($assigns)) {
    foreach ($assigns as $assign) {
        $cid = $assign['customerid'];
        if (isset($reward_to_check[$cid]) || ($assign['flags'] & TARIFF_FLAG_REWARD_PENALTY_ON_TIME_PAYMENTS)) {
            $reward_to_check[$cid] = $cid;
        }
        if (isset($reward_to_check[$cid]) && $reward_to_check[$cid]) {
            if (!isset($reward_period_to_check[$cid])) {
                $reward_period_to_check[$cid] = DAILY;
            }
            if ($assign['period'] >= WEEKLY && $assign['period'] <= YEARLY) {
                $reward_period_to_check[$cid] = max($reward_period_to_check[$cid], $assign['period']);
            } elseif ($assign['period'] == HALFYEARLY && $reward_period_to_check[$cid] < YEARLY) {
                $reward_period_to_check[$cid] = HALFYEARLY;
            }
        }
    }

    $period_end = mktime(0, 0, 0, date('m', $currtime), date('d', $currtime), date('Y', $currtime));
    $period_starts = array(
        DAILY => strtotime('yesterday', $period_end),
        WEEKLY => strtotime('1 week ago', $period_end),
        MONTHLY => strtotime('1 month ago', $period_end),
        QUARTERLY => strtotime('3 months ago', $period_end),
        HALFYEARLY => strtotime('6 months ago', $period_end),
        YEARLY => strtotime('1 year ago', $period_end),
    );

    $rewards = array();
    foreach ($reward_to_check as $cid) {
        $period_start = $period_starts[$reward_period_to_check[$cid]];
        $balance = $LMS->GetCustomerBalance($cid, $period_start, $reward_penalty_deadline_grace_days);
        if (!isset($balance)) {
            $balance = 0;
        } elseif ($reward_penalty_period_start_check && !$reward_penalty_last_deadline_check_only && $balance < 0) {
            $rewards[$cid] = false;
            continue;
        }
        $history = $DB->GetAll(
            'SELECT (CASE WHEN d.id IS NULL OR c.value > 0 THEN c.time ELSE c.time + (d.paytime + ?) * 86400 END) AS deadline,
                d.id AS docid,
                (c.value * c.currencyvalue) AS value
            FROM cash c
            LEFT JOIN documents d ON d.id = c.docid AND d.type IN ?
            WHERE c.customerid = ?
                AND c.value <> 0
                AND c.time >= ? AND c.time < ?
            ORDER BY deadline, value DESC',
            array(
                $reward_penalty_deadline_grace_days,
                array(DOC_INVOICE, DOC_CNOTE, DOC_DNOTE, DOC_INVOICE_PRO),
                $cid,
                $period_start,
                $period_end,
            )
        );
        $rewards[$cid] = true;
        if (empty($history)) {
            $balance = $LMS->GetCustomerBalance($cid, $period_end, $reward_penalty_deadline_grace_days);
            if ($balance < 0) {
                $rewards[$cid] = false;
            }
        } else {
            foreach ($history as &$record) {
                if (!empty($record['docid']) && $record['value'] < 0) {
                    $record['deadline'] = mktime(
                        23,
                        59,
                        59,
                        date('m', $record['deadline']),
                        date('d', $record['deadline']),
                        date('Y', $record['deadline'])
                    ) + 1;
                }
            }
            unset($record);
            usort($history, function ($a, $b) {
                return $a['deadline'] - $b['deadline'];
            });

            $record_index = count($history);
            foreach ($history as $record) {
                if ($record['deadline'] >= $period_end) {
                    break;
                }
                $balance += $record['value'];
                $balance = round($balance, 2);
                $record_index--;
                if (empty($record['docid'])) {
                    continue;
                }
                if ($balance < 0 && ($reward_penalty_last_deadline_check_only && !$record_index || !$reward_penalty_last_deadline_check_only)) {
                    $rewards[$cid] = false;
                    break;
                }
            }
        }
    }
}

// correct currency values for foreign currency documents with today's cdate or sdate
// which have estimated currency value earlier (in the moment of document issue)
$daystart = mktime(0, 0, 0, date('n', $currtime), date('j', $currtime), date('Y', $currtime));
$dayend = $daystart + 86399;
$currencydaystart = strtotime('yesterday', $daystart);
$currencycurrtime = strtotime('yesterday', $currtime);
$currencydayend = strtotime('yesterday', $dayend);

$currencyvalues = array();
if ($prefer_netto) {
    $taxeslist = $LMS->GetTaxes();
}

if (!empty($assigns)) {
    // determine currency values for assignments with foreign currency
    // if payments.prefer_netto = true, use value netto+tax
    // if assignment based on tariff with price variants get price by quantity
    foreach ($assigns as &$assign) {
        if (!empty($assign['tariffid'])) {
            $priceVariant = $LMS->getTariffPriceVariantByQuantityThreshold($assign['tariffid'], $assign['count']);
            if (!empty($priceVariant)) {
                $suspension = empty($assign['suspended']) && empty($assign['allsuspended']) ? 1 : ($suspension_percentage / 100);
                if (!empty($assign['netflag'])) {
                    $assign['price'] = round(((((100 - $assign['pdiscount']) * $priceVariant['net_price']) / 100) - $assign['vdiscount']) * $suspension, 3);
                    $assign['netvalue'] = round($assign['price'] * $assign['count'], 2);
                } else {
                    $assign['price'] = round(((((100 - $assign['pdiscount']) * $priceVariant['gross_price']) / 100) - $assign['vdiscount']) * $suspension, 3);
                    $assign['value'] = round($assign['price'] * $assign['count'], 2);
                }
            }
        }
        if ($prefer_netto) {
            if (!empty($assign['netvalue'])) {
                $assign['value'] = round($assign['netvalue'] * (100 + $taxeslist[$assign['taxid']]['value']) / 100, 2);
            }
        }

        $currency = $assign['currency'];
        if (empty($currency)) {
            $assign['currency'] = Localisation::getCurrentCurrency();
            continue;
        }
        if ($currency != Localisation::getCurrentCurrency()) {
            if (!isset($currencyvalues[$currency])) {
                $currencyvalues[$currency] = $LMS->getCurrencyValue($currency, $currencycurrtime);
                if (!isset($currencyvalues[$currency])) {
                    die('Fatal error: couldn\'t get quote for ' . $currency . ' currency!' . PHP_EOL);
                }
            }
        }
    }
    unset($assign);
}

if (!empty($currencyvalues) && !$quiet) {
    print "Currency quotes:" . PHP_EOL;
    foreach ($currencyvalues as $currency => $value) {
        print '1 ' . $currency . ' = ' . $value . ' ' . Localisation::getCurrentCurrency(). PHP_EOL;
    }
}
$currencyvalues[Localisation::getCurrentCurrency()] = 1.0;

$documents = $DB->GetAll(
    'SELECT d.id, d.currency FROM documents d
    JOIN customers c ON c.id = d.customerid
    WHERE ' . (empty($customerid) ? '1 = 1' : 'd.customerid IN (' . implode(', ', $customerid) . ')')
        . ($divisionid ? ' AND c.divisionid = ' . $divisionid : '')
        . ' AND d.type IN (?, ?, ?, ?, ?) AND ((sdate = 0 AND cdate >= ? AND cdate <= ?)
        OR (sdate > 0 AND ((sdate < cdate  AND sdate >= ? AND sdate <= ?) OR (sdate >= cdate AND cdate >= ? AND cdate <= ?))))
        AND currency <> ?',
    array(
        DOC_INVOICE,
        DOC_CNOTE,
        DOC_INVOICE_PRO,
        DOC_RECEIPT,
        DOC_DNOTE,
        $currencydaystart,
        $currencydayend,
        $currencydaystart,
        $currencydayend,
        $currencydaystart,
        $currencydayend,
        Localisation::getCurrentCurrency(),
    )
);

$cashes = $DB->GetAll(
    'SELECT cash.id, cash.currency FROM cash
    LEFT JOIN customers c ON c.id = cash.customerid
    WHERE ' . (empty($customerid) ? '1 = 1' : 'cash.customerid IN (' . implode(', ', $customerid) . ')')
    . ($divisionid ? ' AND c.divisionid = ' . $divisionid : '')
    . ' AND cash.docid IS NULL AND cash.currency <> ? AND cash.time >= ? AND cash.time <= ?',
    array(
        Localisation::getCurrentCurrency(),
        $currencydaystart,
        $currencydayend,
    )
);

setlocale(LC_NUMERIC, 'C');

// solid payments
$payments = $DB->GetAll(
    "SELECT * FROM payments WHERE value <> 0
			AND (period = ? OR (period = ? AND at = ?)
				OR (period = ? AND at = ?)
				OR (period = ? AND at = ?)
				OR (period = ? AND at = ?)
				OR (period = ? AND at = ?))",
    array(DAILY, WEEKLY, $weekday, MONTHLY, $dom, QUARTERLY, $quarter, HALFYEARLY, $halfyear, YEARLY, $yearday)
);

$DB->BeginTrans();

if (!empty($documents)) {
    foreach ($documents as &$document) {
        $currency = $document['currency'];
        if (empty($currency)) {
            continue;
        }
        if (!isset($currencyvalues[$currency])) {
            $currencyvalues[$currency] = $LMS->getCurrencyValue($currency, $currencydaystart);
            if (!isset($currencyvalues[$currency])) {
                echo 'Unable to determine currency value for document ID ' . $document['id'] . ' and currency ' . $currency . '.' . PHP_EOL;
                continue;
            }
        }
        $DB->Execute(
            'UPDATE documents
            SET currencyvalue = ?
            WHERE id = ?',
            array(
                $currencyvalues[$currency],
                $document['id'],
            )
        );
        $DB->Execute(
            'UPDATE cash
            SET currencyvalue = ?
            WHERE docid = ?',
            array(
                $currencyvalues[$currency],
                $document['id'],
            )
        );
        echo 'Corrected currency value for document ID ' . $document['id'] . ' with currency ' . $currency . '.' . PHP_EOL;
    }
    unset($document);
}

if (!empty($cashes)) {
    foreach ($cashes as &$cash) {
        $currency = $cash['currency'];
        if (empty($currency)) {
            continue;
        }
        if (!isset($currencyvalues[$currency])) {
            $currencyvalues[$currency] = $LMS->getCurrencyValue($currency, $currencydaystart);
            if (!isset($currencyvalues[$currency])) {
                echo 'Unable to determine currency value for cash ID ' . $cash['id'] . ' and currency ' . $currency . '.' . PHP_EOL;
                continue;
            }
        }
        $DB->Execute(
            'UPDATE cash
            SET currencyvalue = ?
            WHERE id = ?',
            array(
                $currencyvalues[$currency],
                $cash['id'],
            )
        );
        echo 'Corrected currency value for cash ID ' . $cash['id'] . ' with currency ' . $currency . '.' . PHP_EOL;
    }
    unset($cash);
}

if (!empty($payments)) {
    foreach ($payments as $payment) {
        $DB->Execute(
            "INSERT INTO cash (time, type, value, customerid, comment)
			VALUES (?, ?, ?, ?, ?)",
            array($issuetime, 1, $payment['value'] * -1, null, $payment['name'] . '/' . $payment['creditor'])
        );
        if (!$quiet) {
            echo 'CID:-' . "\tVAL:" . $payment['value'] . "\tDESC:" . $payment['name'] . '/' . $payment['creditor'] . PHP_EOL;
        }
    }
}

// invoice auto-closes
if ($check_invoices) {
    $DB->Execute(
        "UPDATE documents SET closed = 1
		WHERE " . (empty($customerid) ? '1 = 1' : 'customerid IN (' . implode(', ', $customerid) . ')') . " AND customerid IN (
			SELECT cash.customerid
			FROM cash
			JOIN customers c ON c.id = cash.customerid
			WHERE cash.time <= ?NOW?"
                . ($divisionid ? ' AND c.divisionid = ' . $divisionid : '')
                . ($customergroups ? str_replace('%customerid_alias%', 'cash.customerid', $customergroups) : '') . "
			GROUP BY cash.customerid
			HAVING SUM(cash.value * cash.currencyvalue) >= 0
		) AND type IN (?, ?, ?)
			AND cdate <= ?NOW?
			AND closed = 0",
        array(DOC_INVOICE, DOC_CNOTE, DOC_DNOTE)
    );
}

if (empty($assigns)) {
    die;
}

$document_dirs = array(DOC_DIR);
$document_dirs = $LMS->executeHook('documents_dir_initialized', $document_dirs);

function GetBillingTemplates($document_dirs)
{
    $docengines = array();

    foreach ($document_dirs as $doc_dir) {
        if ($dirs = getdir($doc_dir . DIRECTORY_SEPARATOR . 'templates', '^[a-z0-9_-]+$')) {
            foreach ($dirs as $dir) {
                $infofile = $doc_dir . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR
                    . $dir . DIRECTORY_SEPARATOR . 'info.php';
                if (file_exists($infofile)) {
                    unset($engine);
                    include($infofile);
                    $engine['doc_dir'] = $doc_dir;
                    if (isset($engine['type'])) {
                        if (!is_array($engine['type'])) {
                            $engine['type'] = array($engine['type']);
                        }
                        $intersect = array_intersect($engine['type'], array(DOC_BILLING));
                        if (!empty($intersect)) {
                            $docengines[$dir] = $engine;
                        }
                    } else {
                        $docengines[$dir] = $engine;
                    }
                }
            }
        }
    }

    return $docengines;
}

$billing_document_engines = GetBillingTemplates($document_dirs);

if (!empty($billing_document_engines)) {
    if (empty($billing_document_template)) {
        if (count($billing_document_engines) == 1) {
            $billing_document_template = reset($billing_document_engines);
            $billing_document_template['dir'] = key($billing_document_engines);
            unset($billing_document_engines);
        }
    } else {
        foreach ($billing_document_engines as $dir => $engine) {
            if ($engine['name'] == $billing_document_template) {
                $billing_document_template = $engine;
                $billing_document_template['dir'] = $dir;
                break;
            }
        }
    }
}

if (!empty($billing_document_template)) {
    $billing_plans = array();
    $billing_periods = array(
        0 => YEARLY,
    );
    $results = $DB->GetAll(
        "SELECT n.id, n.period, COALESCE(a.divisionid, 0) AS divid, isdefault
        FROM numberplans n
        LEFT JOIN numberplanassignments a ON (a.planid = n.id)
        WHERE doctype = ?",
        array(
            DOC_BILLING,
        )
    );
    if (!empty($results)) {
        foreach ($results as $row) {
            if ($row['isdefault']) {
                $billing_plans[$row['divid']] = $row['id'];
            }
            $billing_periods[$row['id']] = $row['period'] ?: YEARLY;
        }
    }

    $barcode = new \Com\Tecnick\Barcode\Barcode();

    // Initialize templates engine (must be before locale settings)
    $SMARTY = new LMSSmarty;

    // test for proper version of Smarty

    if (defined('Smarty::SMARTY_VERSION')) {
        $ver_chunks = preg_split('/[- ]/', preg_replace('/^smarty-/i', '', Smarty::SMARTY_VERSION), -1, PREG_SPLIT_NO_EMPTY);
    } else {
        $ver_chunks = null;
    }
    if (count($ver_chunks) < 1 || version_compare('3.1', $ver_chunks[0]) > 0) {
        die('Wrong version of Smarty engine! We support only Smarty-3.x greater than 3.1.' . PHP_EOL);
    }

    define('SMARTY_VERSION', $ver_chunks[0]);

    // Set some template and layout variables

    $SMARTY->setTemplateDir(null);
/*
    $custom_templates_dir = ConfigHelper::getConfig('phpui.custom_templates_dir');
    if (!empty($custom_templates_dir) && file_exists(SMARTY_TEMPLATES_DIR . DIRECTORY_SEPARATOR . $custom_templates_dir)
        && !is_file(SMARTY_TEMPLATES_DIR . DIRECTORY_SEPARATOR . $custom_templates_dir)) {
        $SMARTY->AddTemplateDir(SMARTY_TEMPLATES_DIR . DIRECTORY_SEPARATOR . $custom_templates_dir);
    }
    $SMARTY->AddTemplateDir(
        array(
            SMARTY_TEMPLATES_DIR . DIRECTORY_SEPARATOR . 'default',
            SMARTY_TEMPLATES_DIR,
        )
    );
*/
    $SMARTY->setCompileDir(SMARTY_COMPILE_DIR);

    $layout = array();

    $SMARTY->assignByRef('layout', $layout);
}

$invoices_with_billings = array();

foreach ($assigns as $assign) {
    $cid = $assign['customerid'];
    $divid = ($assign['divisionid'] ?: 0);

    $assign['price'] = round($assign['price'], 3);
    $assign['value'] = round($assign['price'] * $assign['count'], 2);

    if (empty($assign['value']) && ($assign['liabilityid'] != 'set' || !$empty_billings)) {
        continue;
    }

    if (($assign['flags'] & TARIFF_FLAG_REWARD_PENALTY_ON_TIME_PAYMENTS)
        && ($assign['value'] < 0 && !$rewards[$cid]
            || $assign['value'] > 0 && $rewards[$cid])) {
        continue;
    }

    if (($assign['flags'] & TARIFF_FLAG_REWARD_PENALTY_EINVOICE)
        && ($assign['value'] < 0 && empty($assign['einvoice'])
            || $assign['value'] > 0 && !empty($assign['einvoice']))) {
        continue;
    }

    if (($assign['flags'] & TARIFF_FLAG_REWARD_PENALTY_MAIL_MARKETING)
        && ($assign['value'] < 0 && empty($assign['mail_marketing'])
            || $assign['value'] > 0 && !empty($assign['mail_marketing']))) {
        continue;
    }

    if (($assign['flags'] & TARIFF_FLAG_REWARD_PENALTY_SMS_MARKETING)
        && ($assign['value'] < 0 && empty($assign['sms_marketing'])
            || $assign['value'] > 0 && !empty($assign['sms_marketing']))) {
        continue;
    }

    if (!isset($assign['taxcategory'])) {
        $assign['taxcategory'] = 0;
    }

    $linktechnology = isset($assignment_linktechnologies[$assign['id']]) ? $assignment_linktechnologies[$assign['id']]['technology'] : null;

    if (!empty($assign['suspended']) || !empty($assign['allsuspended'])) {
        $assign['price'] = round($assign['price'] * $suspension_percentage / 100, 3);
        $assign['value'] = round($assign['price'] * $assign['count'], 2);
    }
    if (empty($assign['value']) && ($assign['liabilityid'] != 'set' || !$empty_billings || !empty($assign['suspended']))) {
        continue;
    }

    if ($assign['liabilityid'] && !$use_comment_for_liabilities) {
        $desc = $assign['name'];
    } else {
        if (empty($assign['backwardperiod'])) {
            if ($assign['tarifftype'] == SERVICE_PHONE) {
                if (empty($assign['phones'])) {
                    $desc = $comment_phone_no_numbers;
                } else {
                    $desc = isset($comment_by_service_types[$assign['tarifftype']]) ? $comment_by_service_types[$assign['tarifftype']] : $comment;
                }
            } else {
                $desc = isset($comment_by_service_types[$assign['tarifftype']]) ? $comment_by_service_types[$assign['tarifftype']] : $comment;
            }
        } else {
            if ($assign['tarifftype'] == SERVICE_PHONE) {
                if (empty($assign['phones'])) {
                    $desc = $backward_comment_phone_no_numbers;
                } else {
                    $desc = isset($backward_comment_by_service_types[$assign['tarifftype']]) ? $backward_comment_by_service_types[$assign['tarifftype']] : $backward_comment;
                }
            } else {
                $desc = isset($backward_comment_by_service_types[$assign['tarifftype']]) ? $backward_comment_by_service_types[$assign['tarifftype']] : $backward_comment;
            }
        }
    }

    $p = $assign['period'];

    $desc = str_replace(
        array(
            '%type',
            '%billing_period',
            '%tariff',
            '%attribute',
            '%desc',
            '%call_count',
            '%call_fraction',
            '%call_time',
            '%promotion_name',
            '%promotion_schema_name',
            '%promotion_schema_length',
            '%period',
            '%current_month',
            '%current_period',
            '%next_period',
            '%prev_period',
            // better use this
            '%forward_periods',
            '%forward_aligned_periods',
            '%backward_periods',
            '%backward_aligned_periods',
            // for backward references
            '%forward_period',
            '%forward_period_aligned',
            '%aligned_period',
            '%note',
            '%doctype',
            '%docnumber',
            '%docdate',
        ),
        array(
            $assign['tarifftype'] != SERVICE_OTHER ? $SERVICETYPES[$assign['tarifftype']] : '',
            $BILLING_PERIODS[$assign['period']] ?? '',
            $assign['name'],
            $assign['attribute'],
            $assign['description'],
            !empty($assign['call_count']) ? $assign['call_count'] : 0,
            isset($assign['call_fraction']) && mb_strlen($assign['call_fraction']) ? $assign['call_fraction'] : '',
            isset($assign['call_time']) ? ceil($assign['call_time'] / 60) : '',
            $assign['promotion_name'],
            $assign['promotion_schema_name'],
            empty($assign['promotion_schema_length']) ? trans('indefinite period') : trans('$a months', $assign['promotion_schema_length']),
            $forward_periods[$p],
            $current_month,
            $current_period,
            $next_period,
            $prev_period,
            $forward_periods[$p],
            $forward_aligned_periods[$p],
            $backward_periods[$p],
            $backward_aligned_periods[$p],
            $forward_periods[$p],
            $forward_aligned_periods[$p],
            $forward_aligned_periods[$p],
            empty($assign['note']) ? '' : $assign['note'],
            empty($assign['doctype']) ? '' : $DOCTYPES[$assign['doctype']],
            empty($assign['docnumber']) ? '' : $assign['docnumber'],
            empty($assign['docdate']) ? '' : Utils::strftime($date_format, $assign['docdate']),
        ),
        $desc
    );

    if (strpos($desc, '%aligned_partial_period') !== false) {
        if ($assign['datefrom']) {
            $datefrom = explode('/', date('Y/m/d', $assign['datefrom']));
        }
        if ($assign['dateto']) {
            $dateto = explode('/', date('Y/m/d', $assign['dateto']));
            $dateto_nextday = explode('/', date('Y/m/d', $assign['dateto'] + 1));
        }
        if (empty($assign['backwardperiod'])) {
            if (isset($datefrom) && intval($datefrom[2]) != 1 && intval($datefrom[1]) == intval($month) && intval($datefrom[0]) == intval($year)) {
                $first_aligned_partial_period = array(
                    DAILY => $forward_periods[DAILY],
                    WEEKLY => $forward_periods[WEEKLY],
                    MONTHLY => Utils::strftime($date_format, mktime(12, 0, 0, $month, $datefrom[2], $year)) . ' - ' . Utils::strftime($date_format, mktime(12, 0, 0, $month + 1, 0, $year)),
                    QUARTERLY => Utils::strftime($date_format, mktime(12, 0, 0, $month, $datefrom[2], $year)) . ' - ' . Utils::strftime($date_format, mktime(12, 0, 0, $month + 3, 0, $year)),
                    HALFYEARLY => Utils::strftime($date_format, mktime(12, 0, 0, $month, $datefrom[2], $year)) . ' - ' . Utils::strftime($date_format, mktime(12, 0, 0, $month + 6, 0, $year)),
                    YEARLY => Utils::strftime($date_format, mktime(12, 0, 0, $month, $datefrom[2], $year)) . ' - ' . Utils::strftime($date_format, mktime(12, 0, 0, $month, 0, $year + 1)),
                    DISPOSABLE => $forward_periods[DISPOSABLE],
                );
                $desc = str_replace('%aligned_partial_period', $first_aligned_partial_period[$p], $desc);
                unset($first_aligned_partial_period);
            } else {
                if (isset($dateto) && isset($dateto_nextday) && intval($dateto_nextday[2]) != 1 && intval($dateto[1]) == intval($month) && intval($dateto[0]) == intval($year)) {
                    $last_aligned_partial_period = array(
                        DAILY => $forward_periods[DAILY],
                        WEEKLY => $forward_periods[WEEKLY],
                        MONTHLY => Utils::strftime($date_format, mktime(12, 0, 0, $month, 1, $year)) . ' - ' . Utils::strftime($date_format, mktime(12, 0, 0, $month, intval($dateto[2]), $year)),
                        QUARTERLY => Utils::strftime($date_format, mktime(12, 0, 0, $month, 1, $year)) . ' - ' . Utils::strftime($date_format, mktime(12, 0, 0, $month + 2, intval($dateto[2]), $year)),
                        HALFYEARLY => Utils::strftime($date_format, mktime(12, 0, 0, $month, 1, $year)) . ' - ' . Utils::strftime($date_format, mktime(12, 0, 0, $month + 5, intval($dateto[2]), $year)),
                        YEARLY => Utils::strftime($date_format, mktime(12, 0, 0, $month, 1, $year)) . ' - ' . Utils::strftime($date_format, mktime(12, 0, 0, $month, intval($dateto[2]), $year + 1)),
                        DISPOSABLE => $forward_periods[DISPOSABLE],
                    );
                    $desc = str_replace('%aligned_partial_period', $last_aligned_partial_period[$p], $desc);
                    unset($last_aligned_partial_period);
                } else {
                    $desc = str_replace('%aligned_partial_period', $forward_aligned_periods[$p], $desc);
                }
            }
        } else {
            if (isset($datefrom) && intval($datefrom[2]) != 1 && intval($datefrom[1]) == intval($backward_month) && intval($datefrom[0]) == intval($backward_year)) {
                $first_aligned_partial_period = array(
                    DAILY => $forward_periods[DAILY],
                    WEEKLY => $forward_periods[WEEKLY],
                    MONTHLY => Utils::strftime($date_format, mktime(12, 0, 0, $backward_month, $datefrom[2], $backward_year)) . ' - ' . Utils::strftime($date_format, mktime(12, 0, 0, $backward_month + 1, 0, $backward_year)),
                    QUARTERLY => Utils::strftime($date_format, mktime(12, 0, 0, $backward_month, $datefrom[2], $backward_year)) . ' - ' . Utils::strftime($date_format, mktime(12, 0, 0, $backward_month + 3, 0, $backward_year)),
                    HALFYEARLY => Utils::strftime($date_format, mktime(12, 0, 0, $backward_month, $datefrom[2], $backward_year)) . ' - ' . Utils::strftime($date_format, mktime(12, 0, 0, $backward_month + 6, 0, $backward_year)),
                    YEARLY => Utils::strftime($date_format, mktime(12, 0, 0, $backward_month, $datefrom[2], $backward_year)) . ' - ' . Utils::strftime($date_format, mktime(12, 0, 0, $backward_month, 0, $backward_year + 1)),
                    DISPOSABLE => $forward_periods[DISPOSABLE],
                );
                $desc = str_replace('%aligned_partial_period', $first_aligned_partial_period[$p], $desc);
                unset($first_aligned_partial_period);
            } else {
                if (isset($dateto) && isset($dateto_nextday) && intval($dateto_nextday[2]) != 1 && intval($dateto[1]) == intval($backward_month) && intval($dateto[0]) == intval($backward_year)) {
                    $last_aligned_partial_period = array(
                        DAILY => $forward_periods[DAILY],
                        WEEKLY => $forward_periods[WEEKLY],
                        MONTHLY => Utils::strftime($date_format, mktime(12, 0, 0, $backward_month, 1, $backward_year)) . ' - ' . Utils::strftime($date_format, mktime(12, 0, 0, $backward_month, intval($dateto[2]), $backward_year)),
                        QUARTERLY => Utils::strftime($date_format, mktime(12, 0, 0, $backward_month, 1, $backward_year)) . ' - ' . Utils::strftime($date_format, mktime(12, 0, 0, $backward_month + 2, intval($dateto[2]), $backward_year)),
                        HALFYEARLY => Utils::strftime($date_format, mktime(12, 0, 0, $backward_month, 1, $backward_year)) . ' - ' . Utils::strftime($date_format, mktime(12, 0, 0, $backward_month + 5, intval($dateto[2]), $backward_year)),
                        YEARLY => Utils::strftime($date_format, mktime(12, 0, 0, $backward_month, 1, $backward_year)) . ' - ' . Utils::strftime($date_format, mktime(12, 0, 0, $backward_month, intval($dateto[2]), $backward_year + 1)),
                        DISPOSABLE => $forward_periods[DISPOSABLE],
                    );
                    $desc = str_replace('%aligned_partial_period', $last_aligned_partial_period[$p], $desc);
                    unset($last_aligned_partial_period);
                } else {
                    $desc = str_replace('%aligned_partial_period', $backward_aligned_periods[$p], $desc);
                }
            }
        }
        unset($datefrom, $dateto);
    }

    // for phone calls
    if (isset($assign['phones'])) {
        $desc = str_replace('%phones', str_replace(',', ', ', $assign['phones']), $desc);
    }

    if ($suspension_percentage && ($assign['suspended'] || $assign['allsuspended'])) {
        $desc .= ' ' . $suspension_description;
    }

    if (!isset($invoices[$cid])) {
        $invoices[$cid] = 0;
    }
    if (!isset($doctypes[$cid])) {
        $doctypes[$cid] = 0;
    }
    if (!isset($paytimes[$cid])) {
        $paytimes[$cid] = null;
    }
    if (!isset($paytypes[$cid])) {
        $paytypes[$cid] = 0;
    }
    if (!isset($numberplans[$cid])) {
        $numberplans[$cid] = 0;
    }
    if (!isset($separatedocuments[$cid])) {
        $separatedocuments[$cid] = 0;
    }

    if ($assign['price'] != 0 || $empty_billings && $assign['liabilityid'] == 'set') {
        $price = $assign['price'];
        $currency = $assign['currency'];
        $netflag = intval($assign['netflag']);
        $splitpayment = $assign['splitpayment'];
        if ($assign['t_period'] && $assign['period'] != DISPOSABLE
            && $assign['t_period'] != $assign['period']) {
            if ($assign['t_period'] == YEARLY) {
                $price = $price / 12.0;
            } elseif ($assign['t_period'] == HALFYEARLY) {
                $price = $price / 6.0;
            } elseif ($assign['t_period'] == QUARTERLY) {
                $price = $price / 3.0;
            }

            if ($assign['period'] == YEARLY) {
                $price = $price * 12.0;
            } elseif ($assign['period'] == HALFYEARLY) {
                $price = $price * 6.0;
            } elseif ($assign['period'] == QUARTERLY) {
                $price = $price * 3.0;
            } elseif ($assign['period'] == WEEKLY) {
                $price = $price / 4.0;
            } elseif ($assign['period'] == DAILY) {
                $price = $price / 30.0;
            }
        }

        $price = round($price, 3);
        $value = round($price * $assign['count'], 2);

        $telecom_service = $force_telecom_service_flag && $assign['tarifftype'] != SERVICE_OTHER
            && ($assign['customertype'] == CTYPES_PRIVATE || ($check_customer_vat_payer_flag_for_telecom_service
                && !($assign['customerflags'] & CUSTOMER_FLAG_VAT_PAYER))) && $issuetime < mktime(0, 0, 0, 7, 1, 2021);

        if ($netflag) {
            $grossvalue = $value + round($value * ($assign['taxrate'] / 100), 2);
        } else {
            $grossvalue = $value;
        }

        if ($assign['invoice']) {
            if ($assign['a_paytype']) {
                $inv_paytype = $assign['a_paytype'];
            } elseif ($assign['paytype']) {
                $inv_paytype = $assign['paytype'];
            } elseif ($assign['d_paytype']) {
                $inv_paytype = $assign['d_paytype'];
            } else {
                $inv_paytype = $paytype;
            }

            if (strlen($assign['a_paytime'])) {
                $inv_paytime = $assign['a_paytime'];
            } elseif ($assign['paytime'] >= 0) {
                $inv_paytime = $assign['paytime'];
            } elseif (strlen($assign['d_paytime'])) {
                $inv_paytime = $assign['d_paytime'];
            } else {
                $inv_paytime = $deadline;
            }

            if ($assign['numberplanid'] && isset($periods[$assign['numberplanid']])) {
                $plan = $assign['numberplanid'];
            } elseif ($assign['tariffnumberplanid'] && isset($periods[$assign['tariffnumberplanid']])) {
                $plan = $assign['tariffnumberplanid'];
            } else {
                if (isset($plans[$divid][$assign['invoice']][$assign['customertype']])) {
                    $plan = $plans[$divid][$assign['invoice']][$assign['customertype']];
                } elseif (isset($plans[$divid][$assign['invoice']]['-1'])) {
                    $plan = $plans[$divid][$assign['invoice']]['-1'];
                } else {
                    $plan = 0;
                }
            }

            if ($invoices[$cid] == 0 || $doctypes[$cid] != $assign['invoice']
                || !isset($paytimes[$cid]) || $paytimes[$cid] != $inv_paytime
                || $paytypes[$cid] != $inv_paytype
                || $numberplans[$cid] != $plan || $assign['recipient_address_id'] != $addresses[$cid]
                || $separatedocuments[$cid] != $assign['separatedocument']
                || !isset($currencies[$cid]) || $currencies[$cid] != $currency || $netflags[$cid] != $netflag) {
                if (!array_key_exists($plan, $numbertemplates)) {
                    $numbertemplates[$plan] = $DB->GetOne("SELECT template FROM numberplans WHERE id = ?", array($plan));
                }
                $customernumber = !empty($numbertemplates[$plan]) && preg_match('/%[0-9]*C/', $numbertemplates[$plan]);
                if (($customernumber && !isset($customernumbers[$assign['invoice']][$plan][$cid]))
                    || (!$customernumber && !isset($numbers[$assign['invoice']][$plan]))) {
                    $period = get_period($periods[$plan]);
                    $query = "SELECT MAX(number) AS number FROM documents
                        WHERE cdate >= ? AND cdate <= ? AND type = ? AND numberplanid "
                        . ($plan ? '= ' . $plan : 'IS NULL');
                    if ($customernumber) {
                        $query .= ' AND customerid = ' . $cid;
                    }
                    $maxnumber = (($number = $DB->GetOne(
                        $query,
                        array($period['start'], $period['end'], $assign['invoice'])
                    )) != 0 ? $number : 0);
                    if ($customernumber) {
                        $customernumbers[$assign['invoice']][$plan][$cid] = $newnumber = $maxnumber + 1;
                    } else {
                        $numbers[$assign['invoice']][$plan] = $newnumber = $maxnumber + 1;
                    }
                } else {
                    if ($customernumber) {
                        $newnumber = $customernumbers[$assign['invoice']][$plan][$cid] + 1;
                        $customernumbers[$assign['invoice']][$plan][$cid] = $newnumber;
                    } else {
                        $newnumber = $numbers[$assign['invoice']][$plan] + 1;
                        $numbers[$assign['invoice']][$plan] = $newnumber;
                    }
                }

                $itemid = 0;

                $customer = $DB->GetRow("SELECT lastname, name, address, street, city, zip, postoffice, ssn, ten,
                            countryid, divisionid, paytime, documentmemo, flags, type
						FROM customeraddressview WHERE id = ?", array($cid));

                if (!isset($divisions[$assign['divisionid']])) {
                    $divisions[$assign['divisionid']] = $LMS->GetDivision($assign['divisionid']);
                }
                $division = $divisions[$assign['divisionid']];

                $fullnumber = docnumber(array(
                    'number' => $newnumber,
                    'template' => $numbertemplates[$plan],
                    'cdate' => $issuetime,
                    'customerid' => $cid,
                ));

                if ($assign['recipient_address_id']) {
                    $addr = $DB->GetRow('SELECT * FROM addresses WHERE id = ?', array($assign['recipient_address_id']));
                    unset($addr['id']);

                    $copy_address_query = "INSERT INTO addresses (" . implode(",", array_keys($addr)) . ") VALUES (" . implode(",", array_fill(0, count($addr), '?'))  . ")";
                    $DB->Execute($copy_address_query, $addr);

                    $recipient_address_id = $DB->GetLastInsertID('addresses');
                } else {
                    $recipient_address_id = null;
                }

                $exported_telecom_service = !empty($customer['countryid']) && !empty($division['countryid']) && $customer['countryid'] != $division['countryid'];
                $telecom_service = $force_telecom_service_flag && $assign['tarifftype'] != SERVICE_OTHER
                    && $assign['customertype'] == CTYPES_PRIVATE && $issuetime < mktime(0, 0, 0, 7, 1, 2021)
                    && $exported_telecom_service;

                $DB->Execute(
                    "INSERT INTO documents (number, numberplanid, type, countryid, divisionid,
					customerid, name, address, zip, city, ten, ssn, cdate, sdate, paytime, paytype,
					div_name, div_shortname, div_address, div_city, div_zip, div_countryid, div_ten, div_regon,
					div_bank, div_account, div_inv_header, div_inv_footer, div_inv_author, div_inv_cplace, fullnumber,
					recipient_address_id, recipient_ten, recipient_type, post_address_id, currency, currencyvalue, memo, flags)
					VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                    array(
                        $newnumber,
                        $plan ?: null,
                        $assign['invoice'],
                        $customer['countryid'] ?: null,
                        $customer['divisionid'],
                        $cid,
                        $customer['lastname']." ".$customer['name'],
                        ($customer['postoffice'] && $customer['postoffice'] != $customer['city'] && $customer['street']
                            ? $customer['city'] . ', ' : '') . $customer['address'],
                        $customer['zip'] ?: null,
                        $customer['postoffice'] ?: ($customer['city'] ?: null),
                        $customer['ten'],
                        $customer['ssn'],
                        $issuetime,
                        $saledate,
                        $inv_paytime,
                        $inv_paytype,
                        ($division['name'] ?: ''),
                        ($division['shortname'] ?: ''),
                        ($division['address'] ?: ''),
                        ($division['city'] ?: ''),
                        ($division['zip'] ?: ''),
                        ($division['countryid'] ?: null),
                        ($division['ten'] ?: ''),
                        ($division['regon'] ?: ''),
                        $division['bank'] ?: null,
                        ($division['account'] ?: ''),
                        ($division['inv_header'] ?: ''),
                        ($division['inv_footer'] ?: ''),
                        ($division['inv_author'] ?: ''),
                        ($division['inv_cplace'] ?: ''),
                        $fullnumber,
                        $recipient_address_id,
                        $assign['recipient_ten'],
                        $assign['recipient_type'],
                        empty($assign['post_address_id']) ? null : $LMS->CopyAddress($assign['post_address_id']),
                        $currency,
                        $currencyvalues[$currency],
                        empty($customer['documentmemo']) ? null : $customer['documentmemo'],
                        ($telecom_service ? DOC_FLAG_TELECOM_SERVICE : 0)
                            + ($customer['flags'] & CUSTOMER_FLAG_RELATED_ENTITY ? DOC_FLAG_RELATED_ENTITY : 0)
                            + (!$netflag || ($assign['invoice'] != DOC_INVOICE && $assign['invoice'] != DOC_INVOICE_PRO) ? 0 : DOC_FLAG_NET_ACCOUNT),
                    )
                );

                $invoices[$cid] = $DB->GetLastInsertID("documents");
                if (!empty($telecom_service)) {
                    $telecom_services[$invoices[$cid]] = $telecom_service;
                }
                $currencies[$cid] = $currency;
                $netflags[$cid] = $netflag;
                $doctypes[$cid] = $assign['invoice'];
                //$LMS->UpdateDocumentPostAddress($invoices[$cid], $cid);
                $paytimes[$cid] = $inv_paytime;
                $paytypes[$cid] = $inv_paytype;
                $addresses[$cid] = $assign['recipient_address_id'];
                $numberplans[$cid] = $plan;
                $separatedocuments[$cid] = $assign['separatedocument'];
            }

            if ($splitpayment) {
                $DB->Execute(
                    "UPDATE documents SET flags = flags | ? WHERE id = ?",
                    array(
                        DOC_FLAG_SPLIT_PAYMENT,
                        $invoices[$cid],
                    )
                );
            }

            if (!$prefer_settlement_only || !$assign['settlement'] || !$assign['datefrom']) {
                if ($assign['invoice'] == DOC_DNOTE || !empty($assign['separateitem'])) {
                    $tmp_item = null;
                } else {
                    if (empty($assign['tariffid'])) {
                        $tmp_item = $DB->GetRow(
                            "SELECT itemid, count FROM invoicecontents
                            WHERE tariffid IS NULL AND value=? AND docid=? AND description=? AND pdiscount=? AND vdiscount=?",
                            array(
                                $price,
                                $invoices[$cid],
                                $desc,
                                $assign['pdiscount'],
                                $assign['vdiscount']
                            )
                        );
                    } else {
                        $tmp_item = $DB->GetRow(
                            "SELECT itemid, count FROM invoicecontents
                            WHERE tariffid=? AND value=? AND docid=? AND description=? AND pdiscount=? AND vdiscount=?",
                            array(
                                $assign['tariffid'],
                                $price,
                                $invoices[$cid],
                                $desc,
                                $assign['pdiscount'],
                                $assign['vdiscount']
                            )
                        );
                    }
                }

                if (!empty($tmp_item)) {
                    if ($assign['invoice'] == DOC_DNOTE) {
                        $DB->Execute(
                            "UPDATE debitnotecontents SET value = value + ?
                            WHERE docid = ? AND itemid = ?",
                            array($grossvalue, $invoices[$cid], $tmp_item['itemid'])
                        );
                    } else {
                        $DB->Execute(
                            "UPDATE invoicecontents SET count = count + ?
                            WHERE docid = ? AND itemid = ?",
                            array($assign['count'], $invoices[$cid], $tmp_item['itemid'])
                        );
                    }
                    if ($assign['invoice'] == DOC_INVOICE || $proforma_generates_commitment) {
                        $DB->Execute(
                            "UPDATE cash
                            SET value = ?
                            WHERE docid = ? AND itemid = ?",
                            array(
                                $netflag
                                    ? -round(($price * (100 + $assign['taxrate']) / 100) * ($tmp_item['count'] + $assign['count']), 2)
                                    : -$price * ($tmp_item['count'] + $assign['count']),
                                $invoices[$cid],
                                $tmp_item['itemid'],
                            )
                        );
                    }
                } else {
                    $itemid++;

                    if ($assign['invoice'] == DOC_DNOTE) {
                        $DB->Execute(
                            "INSERT INTO debitnotecontents (docid, value, description, itemid)
                            VALUES (?, ?, ?, ?)",
                            array($invoices[$cid], $grossvalue, $desc, $itemid)
                        );
                    } else {
                        $DB->Execute(
                            "INSERT INTO invoicecontents (docid, value, taxid, taxcategory, prodid,
                            content, count, description, tariffid, itemid, pdiscount, vdiscount, period)
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                            array(
                                $invoices[$cid],
                                $price,
                                $assign['taxid'],
                                $assign['taxcategory'],
                                $assign['prodid'],
                                $unit_name,
                                $assign['count'],
                                $desc,
                                empty($assign['tariffid']) ? null : $assign['tariffid'],
                                $itemid,
                                $assign['pdiscount'],
                                $assign['vdiscount'],
                                $assign['period'],
                            )
                        );

                        if ($telecom_service && !isset($telecom_services[$invoices[$cid]])) {
                            $DB->Execute(
                                "UPDATE documents SET flags = ? WHERE id = ?",
                                array(DOC_FLAG_TELECOM_SERVICE, $invoices[$cid])
                            );
                            $telecom_services[$invoices[$cid]] = true;
                        }
                    }
                    if ($assign['invoice'] == DOC_INVOICE || $assign['invoice'] == DOC_DNOTE || $proforma_generates_commitment) {
                        $DB->Execute(
                            "INSERT INTO cash (time, value, currency, currencyvalue, taxid, customerid, comment, docid, itemid, linktechnology, servicetype)
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                            array(
                                $issuetime,
                                -$grossvalue,
                                $currency,
                                $currencyvalues[$currency],
                                $assign['taxid'],
                                $cid,
                                $desc,
                                $invoices[$cid],
                                $itemid,
                                $linktechnology,
                                $assign['tarifftype'],
                            )
                        );

                        if ($auto_payments && ($PAYTYPES[$inv_paytype]['features'] & INVOICE_FEATURE_AUTO_PAYMENT)) {
                            $DB->Execute(
                                "INSERT INTO cash (type, time, value, currency, currencyvalue, taxid, customerid, comment, docid, itemid, linktechnology, servicetype)
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                                array(
                                    1,
                                    $issuetime,
                                    $grossvalue,
                                    $currency,
                                    $currencyvalues[$currency],
                                    null,
                                    $cid,
                                    $desc,
                                    null,
                                    0,
                                    $linktechnology,
                                    $assign['tarifftype'],
                                )
                            );
                        }
                    }

                    if (!empty($billing_document_template) && !empty($assign['billingconsent']) && !isset($invoices_with_billings[$invoices[$cid]])) {
                        $billing_plan = $billing_plans[$divid] ?? 0;
                        if (!isset($numbertemplates[$billing_plan])) {
                            $numbertemplates[$billing_plan] = $DB->GetOne("SELECT template FROM numberplans WHERE id = ?", array($billing_plan));
                        }
                        $customernumber = !empty($numbertemplates[$billing_plan]) && preg_match('/%[0-9]*C/', $numbertemplates[$billing_plan]);
                        if (($customernumber && !isset($customernumbers[DOC_BILLING][$billing_plan][$cid]))
                            || (!$customernumber && !isset($numbers[DOC_BILLING][$billing_plan]))) {
                            $period = get_period($billing_periods[$billing_plan]);
                            $query = "SELECT MAX(number) AS number FROM documents
                                WHERE cdate >= ? AND cdate <= ? AND type = ? AND numberplanid "
                                . ($billing_plan ? '= ' . $billing_plan : 'IS NULL');
                            if ($customernumber) {
                                $query .= ' AND customerid = ' . $cid;
                            }
                            $maxnumber = (($number = $DB->GetOne(
                                $query,
                                array($period['start'], $period['end'], DOC_BILLING)
                            )) != 0 ? $number : 0);
                            if ($customernumber) {
                                $customernumbers[DOC_BILLING][$billing_plan][$cid] = $newnumber = $maxnumber + 1;
                            } else {
                                $numbers[DOC_BILLING][$billing_plan] = $newnumber = $maxnumber + 1;
                            }
                        } else {
                            if ($customernumber) {
                                $newnumber = $customernumbers[DOC_BILLING][$billing_plan][$cid] + 1;
                                $customernumbers[DOC_BILLING][$billing_plan][$cid] = $newnumber;
                            } else {
                                $newnumber = $numbers[DOC_BILLING][$billing_plan] + 1;
                                $numbers[DOC_BILLING][$billing_plan] = $newnumber;
                            }
                        }

                        $doc_fullnumber = docnumber(array(
                            'number' => $newnumber,
                            'template' => $numbertemplates[$billing_plan],
                            'cdate' => $issuetime,
                            'customerid' => $cid,
                        ));

                        switch ($assign['period']) {
                            case YEARLY:
                                $datefrom = mktime(0, 0, 0, $month, 1, $year - 1);
                                $dateto = mktime(0, 0, 0, $month, 1, $year) - 1;
                                break;
                            case HALFYEARLY:
                                $datefrom = mktime(0, 0, 0, $month - 6, 1, $year);
                                $dateto = mktime(0, 0, 0, $month, 1, $year) - 1;
                                break;
                            case QUARTERLY:
                                $datefrom = mktime(0, 0, 0, $month - 3, 1, $year);
                                $dateto = mktime(0, 0, 0, $month, 1, $year) - 1;
                                break;
                            case MONTHLY:
                                $datefrom = mktime(0, 0, 0, $month-1, 1, $year);
                                $dateto = mktime(0, 0, 0, $month, 1, $year) - 1;
                                break;
                            case DISPOSABLE:
                                $datefrom = $currtime;
                                $dateto = strtotime('+ 1 day', $currtime) - 1;
                                break;
                        }

                        if (!$test) {
                            $bobj = $barcode->getBarcodeObj('C128', iconv('UTF-8', 'ASCII//TRANSLIT', $doc_fullnumber), -1, -30, 'black');

                            $document = array(
                                'customerid' => $cid,
                                'type' => DOC_BILLING,
                                'cdate' => $issuetime,
                                'title' => $billing_document_template['title'],
                                'number' => $newnumber,
                                'numberplanid' => $billing_plan,
                                'templ' => $billing_document_template['name'],
                                'fromdate' => $datefrom,
                                'todate' => $dateto,
                                'confirmdate' => 0,
                                'reference' => $invoices[$cid],
                                'barcode' => base64_encode($bobj->getPngData()),
                                'numbers' => preg_split('/\s*,\s*/', $assign['phones']),
                            );

                            $doc_dir = $billing_document_template['doc_dir'];
                            $template_dir = $billing_document_template['doc_dir'] . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . $document['templ'];

                            $engine = $billing_document_template;

                            $customerinfo = $LMS->GetCustomer($cid);

                            $SMARTY->assign(array(
                                'customer' => $customerinfo,
                                'customerinfo' => $customerinfo,
                                'division' => $division,
                                'document' => $document,
                                'engine' => $engine,
                            ));

                            ConfigHelper::setFilter($customerinfo['divisionid']);

                            $default_header = ConfigHelper::getConfig('documents.default_header', '', true);
                            if (strlen($default_header) && file_exists($default_header)) {
                                $header = $SMARTY->fetch($default_header);
                            } else {
                                $header = '';
                            }
                            $SMARTY->assign('header', $header);

                            $default_footer = ConfigHelper::getConfig('documents.default_footer', '', true);
                            if (strlen($default_footer) && file_exists($default_footer)) {
                                $footer = $SMARTY->fetch($default_footer);
                            } else {
                                $footer = '';
                            }
                            $SMARTY->assign('footer', $footer);

                            $output = null;

                            // run template engine
                            if (file_exists($doc_dir . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR
                                . $engine['engine'] . DIRECTORY_SEPARATOR . 'engine.php')) {
                                include($doc_dir . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR
                                    . $engine['engine'] . DIRECTORY_SEPARATOR . 'engine.php');
                            } else {
                                include(DOC_DIR . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . 'default'
                                    . DIRECTORY_SEPARATOR . 'engine.php');
                            }

                            $files = array();

                            if ($output) {
                                $file = DOC_DIR . DIRECTORY_SEPARATOR . 'tmp.file';
                                file_put_contents($file, $output);

                                $md5sum = md5_file($file);
                                $path = DOC_DIR . DIRECTORY_SEPARATOR . substr($md5sum, 0, 2);
                                $docfile = array(
                                    'md5sum' => $md5sum,
                                    'type' => $engine['content_type'],
                                    'filename' => $engine['output'],
                                    'tmpname' => $file,
                                    'attachmenttype' => 1,
                                    'path' => $path,
                                    'newfile' => $path . DIRECTORY_SEPARATOR . $md5sum,
                                );
                                $files[] = $docfile;
                            } elseif ($output !== false) {
                                die('Fatal error: Problem during billing document generation!' . PHP_EOL);
                            }

                            $error = $LMS->AddDocumentFileAttachments($files);

                            if (file_exists($file)) {
                                @unlink($file);
                            }
                        } else {
                            $output = $error = null;
                        }

                        if ($test || isset($output) && $output !== false) {
                            $DB->Execute(
                                "INSERT INTO documents (number, numberplanid, type, countryid, divisionid,
                                    customerid, name, address, zip, city, ten, ssn, cdate,
                                    div_name, div_shortname, div_address, div_city, div_zip, div_countryid, div_ten, div_regon,
                                    div_bank, div_account, div_inv_header, div_inv_footer, div_inv_author, div_inv_cplace, fullnumber,
                                    reference, template, closed)
                                    VALUES (?, ?, ?, ?, ?,
                                        ?, ?, ?, ?, ?, ?, ?, ?,
                                        ?, ?, ?, ?, ?, ?, ?, ?,
                                        ?, ?, ?, ?, ?, ?, ?,
                                        ?, ?, ?)",
                                array(
                                    $newnumber,
                                    $billing_plan ?: null,
                                    DOC_BILLING,
                                    $customer['countryid'] ?: null,
                                    $customer['divisionid'],
                                    $cid,
                                    $customer['lastname'] . ' ' . $customer['name'],
                                    ($customer['postoffice'] && $customer['postoffice'] != $customer['city'] && $customer['street']
                                        ? $customer['city'] . ', ' : '') . $customer['address'],
                                    $customer['zip'] ?: null,
                                    $customer['postoffice'] ?: ($customer['city'] ?: null),
                                    $customer['ten'],
                                    $customer['ssn'],
                                    $issuetime,
                                    $division['name'] ?: '',
                                    $division['shortname'] ?: '',
                                    $division['address'] ?: '',
                                    $division['city'] ?: '',
                                    $division['zip'] ?: '',
                                    $division['countryid'] ?: null,
                                    $division['ten'] ?: '',
                                    $division['regon'] ?: '',
                                    $division['bank'] ?: null,
                                    $division['account'] ?: '',
                                    $division['inv_header'] ?: '',
                                    $division['inv_footer'] ?: '',
                                    $division['inv_author'] ?: '',
                                    $division['inv_cplace'] ?: '',
                                    $doc_fullnumber,
                                    $invoices[$cid],
                                    $billing_document_template['name'],
                                    DOC_CLOSED,
                                )
                            );
                            $billing_docid = $DB->GetLastInsertID('documents');

                            $DB->Execute(
                                "INSERT INTO documentcontents
                                (docid, title, fromdate, todate)
                                VALUES (?, ?, ?, ?)",
                                array(
                                    $billing_docid,
                                    $billing_document_template['title'],
                                    $datefrom,
                                    $dateto,
                                )
                            );

                            if (isset($output) && $output !== false && empty($error)) {
                                $LMS->AddDocumentAttachments($billing_docid, $files);
                            }

                            $invoices_with_billings[$invoices[$cid]] = $billing_docid;

                            if (!$quiet) {
                                echo 'CID:' . $cid . "\tBILLING-NUMBER:" . $doc_fullnumber . "\tREFERENCED-DOCUMENT-NUMBER:" . $fullnumber . "\tREFERENCED-DOCUMENT-ID:" . $invoices[$cid] . PHP_EOL;
                            }
                        }
                    }
                }
            }
        } else {
            if (!$prefer_settlement_only || !$assign['settlement'] || !$assign['datefrom']) {
                $DB->Execute(
                    "INSERT INTO cash (time, value, currency, currencyvalue, taxid, customerid, comment, linktechnology, servicetype)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
                    array(
                        $issuetime,
                        -$grossvalue,
                        $currency,
                        $currencyvalues[$currency],
                        $assign['taxid'],
                        $cid,
                        $desc,
                        $linktechnology,
                        $assign['tarifftype'],
                    )
                );
            }
        }

        if (!$quiet && (!$prefer_settlement_only || !$assign['settlement'] || !$assign['datefrom'])) {
            if ($assign['invoice']) {
                echo 'CID:' . $cid . "\tDOCNUMBER:" . $fullnumber . "\tDOCID:" . $invoices[$cid] . "\tVAL:" . $grossvalue . ' ' . $currency. "\tDESC:" . $desc . PHP_EOL;
            } else {
                echo 'CID:' . $cid . "\tVAL:" . $grossvalue . ' ' . $currency. "\tDESC:" . $desc . PHP_EOL;
            }
        }

        // settlement accounting
        if ($assign['settlement'] && $assign['datefrom']) {
            $alldays = 1;

            $backward_correction = $backward_on_the_last_day ? 1 : 0;
            $backward_correction = empty($assign['backwardperiod']) ? 0 : $backward_correction;
            $diffdays = sprintf("%d", round(($today - $assign['datefrom']) / 86400)) + $backward_correction;
            $period_start = mktime(0, 0, 0, $month, $dom - $diffdays + $backward_correction, $year);
            $period_end = mktime(0, 0, 0, $month, $dom - 1 + $backward_correction, $year);
            $period = Utils::strftime($date_format, $period_start) . " - " . Utils::strftime($date_format, $period_end);

            switch ($assign['period']) {
                case WEEKLY:
                    $alldays = 7;
                    break;
                case MONTHLY:
                    $alldays = 30;
                    $d = $dom;
                    $m = $month;
                    $y = $year;
                    $partial_price = 0;
                    $month_days = date('t', mktime(0, 0, 0, $m, 1, $y));
                    while ($diffdays) {
                        if ($d - $diffdays <= 0) {
                            $partial_price += ($d - 1) * $price / $month_days;
                            $diffdays -= ($d - 1);
                        } else {
                            $partial_price += $diffdays * $price / $month_days;
                            $diffdays = 0;
                        }
                        $date = mktime(0, 0, 0, $m, 0, $y);
                        $month_days = date('d', $date);
                        $d = $month_days + 1;
                        $m = date('m', $date);
                        $y = date('Y', $date);
                    }
                    break;
                case QUARTERLY:
                    $alldays = 90;
                    break;
                case HALFYEARLY:
                    $alldays = 182;
                    break;
                case YEARLY:
                    $alldays = 365;
                    break;
            }

            $partial_price = round($alldays != 30 ? $diffdays * $price / $alldays : $partial_price, 3);

            if (floatval($partial_price)) {
                //print "price: $price diffdays: $diffdays alldays: $alldays settl_price: $partial_price" . PHP_EOL;

                if (empty($assign['backwardperiod'])) {
                    if ($assign['tarifftype'] == SERVICE_PHONE) {
                        if (empty($assign['phones'])) {
                            $sdesc = $s_comment_phone_no_numbers;
                        } else {
                            $sdesc = isset($s_comment_by_service_types[$assign['tarifftype']]) ? $s_comment_by_service_types[$assign['tarifftype']] : $s_comment;
                        }
                    } else {
                        $sdesc = isset($s_comment_by_service_types[$assign['tarifftype']]) ? $s_comment_by_service_types[$assign['tarifftype']] : $s_comment;
                    }
                } else {
                    if ($assign['tarifftype'] == SERVICE_PHONE) {
                        if (empty($assign['phones'])) {
                            $sdesc = $s_backward_comment_phone_no_numbers;
                        } else {
                            $sdesc = isset($s_backward_comment_by_service_types[$assign['tarifftype']]) ? $s_backward_comment_by_service_types[$assign['tarifftype']] : $s_backward_comment;
                        }
                    } else {
                        $sdesc = isset($s_backward_comment_by_service_types[$assign['tarifftype']]) ? $s_backward_comment_by_service_types[$assign['tarifftype']] : $s_backward_comment;
                    }
                }

                $sdesc = str_replace(
                    array(
                        '%type',
                        '%tariff',
                        '%attribute',
                        '%desc',
                        '%period',
                        '%promotion_name',
                        '%promotion_schema_name',
                        '%promotion_schema_length',
                        '%current_month',
                        '%current_period',
                        '%next_period',
                        '%prev_period',
                        '%note',
                        '%doctype',
                        '%docnumber',
                        '%docdate',
                    ),
                    array(
                        $assign['tarifftype'] != SERVICE_OTHER ? $SERVICETYPES[$assign['tarifftype']] : '',
                        $assign['name'],
                        $assign['attribute'],
                        $assign['description'],
                        $period,
                        $assign['promotion_name'],
                        $assign['promotion_schema_name'],
                        empty($assign['promotion_schema_length']) ? trans('indefinite period') : trans('$a months', $assign['promotion_schema_length']),
                        $current_month,
                        $current_period,
                        $next_period,
                        $prev_period,
                        empty($assign['note']) ? '' : $assign['note'],
                        empty($assign['doctype']) ? '' : $DOCTYPES[$assign['doctype']],
                        empty($assign['docnumber']) ? '' : $assign['docnumber'],
                        empty($assign['docdate']) ? '' : Utils::strftime($date_format, $assign['docdate']),
                    ),
                    $sdesc
                );

                if (strpos($sdesc, '%aligned_partial_period') !== false) {
                    $sdesc = str_replace('%aligned_partial_period', $period, $sdesc);
                }

                $partial_value = round($partial_price * $assign['count'], 2);
                if ($netflag) {
                    $partial_grossvalue = $partial_value + round($partial_value * ($assign['taxrate'] / 100), 2);
                } else {
                    $partial_grossvalue = $partial_value;
                }

                if ($assign['invoice']) {
                    if ($assign['invoice'] == DOC_DNOTE || !empty($assign['separateitem'])) {
                        $tmp_item = null;
                    } else {
                        if (empty($assign['tariffid'])) {
                            $tmp_item = $DB->GetRow(
                                "SELECT itemid, count FROM invoicecontents
                                WHERE tariffid IS NULL AND value = ? AND docid = ? AND description = ?",
                                array(
                                    $partial_price,
                                    $invoices[$cid],
                                    $sdesc
                                )
                            );
                        } else {
                            $tmp_item = $DB->GetRow(
                                "SELECT itemid, count FROM invoicecontents
                                WHERE tariffid = ? AND value = ? AND docid = ? AND description = ?",
                                array(
                                    $assign['tariffid'],
                                    $partial_price,
                                    $invoices[$cid],
                                    $sdesc
                                )
                            );
                        }
                    }

                    if (!empty($tmp_item)) {
                        $DB->Execute(
                            "UPDATE invoicecontents SET count = count + ?
							WHERE docid = ? AND itemid = ?",
                            array($assign['count'], $invoices[$cid], $tmp_item['itemid'])
                        );
                        if ($assign['invoice'] == DOC_INVOICE || $proforma_generates_commitment) {
                            $DB->Execute(
                                "UPDATE cash SET value = value + ?
								WHERE docid = ? AND itemid = ?",
                                array(-$partial_grossvalue, $invoices[$cid], $tmp_item['itemid'])
                            );
                            $DB->Execute(
                                "UPDATE cash SET value = ?
                                WHERE docid = ? AND itemid = ?",
                                array(
                                    $netflag
                                        ? -round(($partial_price * (100 + $assign['taxrate']) / 100) * ($tmp_item['count'] + $assign['count']), 2)
                                        : -$partial_grossvalue * ($tmp_item['count'] + $assign['count']),
                                    $invoices[$cid],
                                    $tmp_item['itemid'],
                                )
                            );
                        }
                    } else {
                        $itemid++;

                        if ($assign['invoice'] == DOC_DNOTE) {
                            $DB->Execute(
                                "INSERT INTO debitnotecontents (docid, value, description, itemid)
								VALUES (?, ?, ?, ?)",
                                array($invoices[$cid], $partial_grossvalue, $desc, $itemid)
                            );
                        } else {
                            $DB->Execute(
                                "INSERT INTO invoicecontents (docid, value, taxid, taxcategory, prodid,
								content, count, description, tariffid, itemid, pdiscount, vdiscount, period)
								VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                                array(
                                    $invoices[$cid],
                                    $partial_price,
                                    $assign['taxid'],
                                    $assign['taxcategory'],
                                    $assign['prodid'],
                                    $unit_name,
                                    $assign['count'],
                                    $sdesc,
                                    empty($assign['tariffid']) ? null : $assign['tariffid'],
                                    $itemid,
                                    $assign['pdiscount'],
                                    $assign['vdiscount'],
                                    $assign['period'],
                                )
                            );

                            if ($telecom_service && !isset($telecom_services[$invoices[$cid]])) {
                                $DB->Execute(
                                    "UPDATE documents SET flags = ? WHERE id = ?",
                                    array(DOC_FLAG_TELECOM_SERVICE, $invoices[$cid])
                                );
                                $telecom_services[$invoices[$cid]] = true;
                            }
                        }
                        if ($assign['invoice'] == DOC_INVOICE || $assign['invoice'] == DOC_DNOTE || $proforma_generates_commitment) {
                            $DB->Execute(
                                "INSERT INTO cash (time, value, currency, currencyvalue, taxid, customerid, comment, docid, itemid, linktechnology, servicetype)
								VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                                array(
                                    $issuetime,
                                    -$partial_grossvalue,
                                    $currency,
                                    $currencyvalues[$currency],
                                    $assign['taxid'],
                                    $cid,
                                    $sdesc,
                                    $invoices[$cid],
                                    $itemid,
                                    $linktechnology,
                                    $assign['tarifftype'],
                                )
                            );
                        }
                    }
                } else {
                    $DB->Execute(
                        "INSERT INTO cash (time, value, currency, currencyvalue, taxid, customerid, comment, linktechnology, servicetype)
						VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
                        array(
                            $issuetime,
                            -$partial_grossvalue,
                            $currency,
                            $currencyvalues[$currency],
                            $assign['taxid'],
                            $cid,
                            $sdesc,
                            $linktechnology,
                            $assign['tarifftype'],
                        )
                    );
                }

                if (!$quiet) {
                    if ($assign['invoice']) {
                        echo 'CID:' . $cid . "\tDOCNUMBER:" . $fullnumber . "\tVAL:" . $partial_grossvalue . ' ' . $currency . "\tDESC:" . $sdesc . PHP_EOL;
                    } else {
                        echo 'CID:' . $cid . "\tVAL:" . $partial_grossvalue . ' ' . $currency . "\tDESC:" . $sdesc . PHP_EOL;
                    }
                }
            }

            // remove settlement flag
            $DB->Execute("UPDATE assignments SET settlement = 0 WHERE id = ?", array($assign['assignmentid']));
        }
    }
}

// invoice auto-closes
if ($check_invoices) {
    $DB->Execute(
        "UPDATE documents SET closed = 1
		WHERE " . (empty($customerid) ? '1 = 1' : 'customerid IN (' . implode(', ', $customerid) . ')') . " AND customerid IN (
			SELECT cash.customerid
			FROM cash
			JOIN customers c ON c.id = cash.customerid
			WHERE cash.time <= ?NOW?"
                . ($divisionid ? ' AND c.divisionid = ' . $divisionid : '')
                . ($customergroups ? str_replace('%customerid_alias%', 'cash.customerid', $customergroups) : '') . "
			GROUP BY cash.customerid
			HAVING SUM(cash.value * cash.currencyvalue) >= 0
		) AND type IN (?, ?, ?)
			AND cdate <= ?NOW?
			AND closed = 0",
        array(DOC_INVOICE, DOC_CNOTE, DOC_DNOTE)
    );
}

if ($delete_old_assignments_after_days) {
    // delete old assignments
    $DB->Execute(
        "DELETE FROM liabilities WHERE id IN (
			SELECT a.liabilityid FROM assignments a
			JOIN customers c ON c.id = a.customerid
            WHERE " . (empty($customerid) ? '1 = 1' : 'a.customerid IN (' . implode(', ', $customerid) . ')')
                . ($divisionid ? ' AND c.divisionid = ' . $divisionid : '')
                . " AND ((a.dateto <> 0 AND a.dateto < $today - ? * 86400
                    OR (a.period = ? AND a.at < $today - ? * 86400))
                AND a.liabilityid IS NOT NULL)
		)",
        array($delete_old_assignments_after_days, DISPOSABLE, $delete_old_assignments_after_days)
    );
    $DB->Execute(
        "DELETE FROM assignments
		WHERE " . (empty($customerid) ? '1 = 1' : 'customerid IN (' . implode(', ', $customerid) . ')')
            . ($divisionid ? ' AND EXISTS (SELECT c.id FROM customers c WHERE c.divisionid = ' . $divisionid . ' AND c.id = customerid)' : '')
            . " AND (tariffid IS NOT NULL OR liabilityid IS NOT NULL)
            AND ((dateto <> 0 AND dateto < $today - ? * 86400)
			OR (period = ? AND at < $today - ? * 86400))",
        array($delete_old_assignments_after_days, DISPOSABLE, $delete_old_assignments_after_days)
    );
}

// clear voip tariff rule states
$DB->Execute("DELETE FROM voip_rule_states");

if ($test) {
    $DB->RollbackTrans();
} else {
    $DB->CommitTrans();
}
