#!/usr/bin/env php
<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2020 LMS Developers
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

// REPLACE THIS WITH PATH TO YOUR CONFIG FILE

// PLEASE DO NOT MODIFY ANYTHING BELOW THIS LINE UNLESS YOU KNOW
// *EXACTLY* WHAT ARE YOU DOING!!!
// *******************************************************************

ini_set('error_reporting', E_ALL&~E_NOTICE);

$parameters = array(
    'config-file:' => 'C:',
    'quiet' => 'q',
    'help' => 'h',
    'version' => 'v',
    'fakedate:' => 'f:',
    'customerid:' => null,
    'division:' => null,
    'customergroups:' => 'g:',
    'customer-status:' => null,
);

$long_to_shorts = array();
foreach ($parameters as $long => $short) {
    $long = str_replace(':', '', $long);
    if (isset($short)) {
        $short = str_replace(':', '', $short);
    }
    $long_to_shorts[$long] = $short;
}

$options = getopt(
    implode(
        '',
        array_filter(
            array_values($parameters),
            function ($value) {
                return isset($value);
            }
        )
    ),
    array_keys($parameters)
);

foreach (array_flip(array_filter($long_to_shorts, function ($value) {
    return isset($value);
})) as $short => $long) {
    if (array_key_exists($short, $options)) {
        $options[$long] = $options[$short];
        unset($options[$short]);
    }
}

if (array_key_exists('version', $options)) {
    print <<<EOF
lms-payments.php
(C) 2001-2020 LMS Developers

EOF;
    exit(0);
}

if (array_key_exists('help', $options)) {
    print <<<EOF
lms-payments.php
(C) 2001-2020 LMS Developers

-C, --config-file=/etc/lms/lms.ini      alternate config file (default: /etc/lms/lms.ini);
-h, --help                      print this help and exit;
-v, --version                   print version info and exit;
-q, --quiet                     suppress any output, except errors;
-f, --fakedate=YYYY/MM/DD       override system date;
    --customerid=<id>           limit assignments to specifed customer
    --division=<shortname>
                                limit assignments to customers which belong to specified
                                division
-g, --customergroups=<group1,group2,...>
                                allow to specify customer groups to which customers
                                should be assigned
    --customer-status=<status1,status2,...>
                                take assignment of customers with specified status only

EOF;
    exit(0);
}

$quiet = array_key_exists('quiet', $options);
if (!$quiet) {
    print <<<EOF
lms-payments.php
(C) 2001-2020 LMS Developers

EOF;
}

if (array_key_exists('config-file', $options)) {
    $CONFIG_FILE = $options['config-file'];
} else {
    $CONFIG_FILE = DIRECTORY_SEPARATOR . 'etc' . DIRECTORY_SEPARATOR . 'lms' . DIRECTORY_SEPARATOR . 'lms.ini';
}

if (!$quiet) {
    echo "Using file ".$CONFIG_FILE." as config." . PHP_EOL;
}

if (!is_readable($CONFIG_FILE)) {
    die('Unable to read configuration file ['.$CONFIG_FILE.']!');
}

define('CONFIG_FILE', $CONFIG_FILE);

$CONFIG = (array) parse_ini_file($CONFIG_FILE, true);

// Check for configuration vars and set default values
$CONFIG['directories']['sys_dir'] = (!isset($CONFIG['directories']['sys_dir']) ? getcwd() : $CONFIG['directories']['sys_dir']);
$CONFIG['directories']['lib_dir'] = (!isset($CONFIG['directories']['lib_dir']) ? $CONFIG['directories']['sys_dir'] . DIRECTORY_SEPARATOR . 'lib' : $CONFIG['directories']['lib_dir']);
$CONFIG['directories']['plugin_dir'] = (!isset($CONFIG['directories']['plugin_dir']) ? $CONFIG['directories']['sys_dir'] . DIRECTORY_SEPARATOR . 'plugins' : $CONFIG['directories']['plugin_dir']);
$CONFIG['directories']['plugins_dir'] = $CONFIG['directories']['plugin_dir'];

define('SYS_DIR', $CONFIG['directories']['sys_dir']);
define('LIB_DIR', $CONFIG['directories']['lib_dir']);
define('PLUGIN_DIR', $CONFIG['directories']['plugin_dir']);
define('PLUGINS_DIR', $CONFIG['directories']['plugin_dir']);

// Load autoloader
$composer_autoload_path = SYS_DIR . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
if (file_exists($composer_autoload_path)) {
    require_once $composer_autoload_path;
} else {
    die("Composer autoload not found. Run 'composer install' command from LMS directory and try again. More informations at https://getcomposer.org/" . PHP_EOL);
}

// Init database

$DB = null;

try {
    $DB = LMSDB::getInstance();
} catch (Exception $ex) {
    trigger_error($ex->getMessage(), E_USER_WARNING);
    // can't working without database
    die("Fatal error: cannot connect to database!" . PHP_EOL);
}

// Include required files (including sequence is important)

require_once(LIB_DIR . DIRECTORY_SEPARATOR . 'common.php');
require_once(LIB_DIR . DIRECTORY_SEPARATOR . 'language.php');
require_once(LIB_DIR . DIRECTORY_SEPARATOR . 'definitions.php');

$SYSLOG = SYSLOG::getInstance();

// Initialize Session, Auth and LMS classes

$AUTH = null;
$LMS = new LMS($DB, $AUTH, $SYSLOG);

$plugin_manager = new LMSPluginManager();
$LMS->setPluginManager($plugin_manager);

$divisionid = isset($options['division']) ? $LMS->getDivisionIdByShortName($options['division']) : null;
if (!empty($divisionid)) {
    ConfigHelper::setFilter($divisionid);
}

$deadline = ConfigHelper::getConfig('payments.deadline', 14);
$sdate_next = ConfigHelper::checkConfig('payments.saledate_next_month');
$paytype = ConfigHelper::getConfig('payments.paytype', 2); // TRANSFER
$comment = ConfigHelper::getConfig('payments.comment', "Tariff %tariff - %attribute subscription for period %period");
$backward_comment = ConfigHelper::getConfig('payments.backward_comment', $comment);
$s_comment = ConfigHelper::getConfig('payments.settlement_comment', $comment);
$s_backward_comment = ConfigHelper::getConfig('payments.settlement_backward_comment', $s_comment);
$suspension_description = ConfigHelper::getConfig('payments.suspension_description', '');
$suspension_percentage = ConfigHelper::getConfig('finances.suspension_percentage', 0);
$unit_name = trans(ConfigHelper::getConfig('payments.default_unit_name'));
$check_invoices = ConfigHelper::checkConfig('payments.check_invoices');
$proforma_generates_commitment = ConfigHelper::checkConfig('phpui.proforma_invoice_generates_commitment');
$delete_old_assignments_after_days = intval(ConfigHelper::getConfig('payments.delete_old_assignments_after_days', 30));
$prefer_settlement_only = ConfigHelper::checkConfig('payments.prefer_settlement_only');
$prefer_netto = ConfigHelper::checkConfig('payments.prefer_netto');
$customergroups = ConfigHelper::getConfig('payments.customergroups', '', true);

$force_telecom_service_flag = ConfigHelper::checkValue(ConfigHelper::getConfig('invoices.force_telecom_service_flag', 'true'));
$check_customer_vat_payer_flag_for_telecom_service = ConfigHelper::checkConfig('invoices.check_customer_vat_payer_flag_for_telecom_service');

$allowed_customer_status =
Utils::determineAllowedCustomerStatus(
    isset($options['customer-status'])
        ? $options['customer-status']
        : ConfigHelper::getConfig('payments.allowed_customer_status', ''),
    -1
);

if (empty($allowed_customer_status)) {
    $customer_status_condition = '';
} else {
    $customer_status_condition = ' AND c.status IN (' . implode(',', $allowed_customer_status) . ')';
}

function localtime2()
{
    global $fakedate;
    if (!empty($fakedate)) {
        $date = explode("/", $fakedate);
        return mktime(0, 0, 0, $date[1], $date[2], $date[0]);
    } else {
        return time();
    }
}

$fakedate = isset($options['fakedate']) ? $options['fakedate'] : null;
$customerid = isset($options['customerid']) && intval($options['customerid']) ? $options['customerid'] : null;

$currtime = strftime("%s", localtime2());
$month = intval(strftime("%m", localtime2()));
$dom = intval(strftime("%d", localtime2()));
$year = strftime("%Y", localtime2());
$weekday = strftime("%u", localtime2());
$yearday = strftime("%j", localtime2());
$last_dom = date('j', mktime(0, 0, 0, $month + 1, 0, $year)) == date('j', $currtime);

if (is_leap_year($year) && $yearday > 31 + 28) {
    $yearday -= 1;
}

if (!empty($fakedate)) {
    $today = $currtime;
} else {
    $today = mktime(0, 0, 0, $month, $dom, $year);
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

$date_format = ConfigHelper::getConfig('payments.date_format', '%Y/%m/%d');

$forward_periods = array(
    DAILY      => strftime($date_format, mktime(12, 0, 0, $month, $dom, $year)),
    WEEKLY     => strftime($date_format, mktime(12, 0, 0, $month, $dom, $year)).' - '.strftime($date_format, mktime(12, 0, 0, $month, $dom+6, $year)),
    MONTHLY    => strftime($date_format, mktime(12, 0, 0, $month, $dom, $year)).' - '.strftime($date_format, mktime(12, 0, 0, $month+1, $dom-1, $year)),
    QUARTERLY  => strftime($date_format, mktime(12, 0, 0, $month, $dom, $year)).' - '.strftime($date_format, mktime(12, 0, 0, $month+3, $dom-1, $year)),
    HALFYEARLY => strftime($date_format, mktime(12, 0, 0, $month, $dom, $year)).' - '.strftime($date_format, mktime(12, 0, 0, $month+6, $dom-1, $year)),
    YEARLY     => strftime($date_format, mktime(12, 0, 0, $month, $dom, $year)).' - '.strftime($date_format, mktime(12, 0, 0, $month, $dom-1, $year+1)),
    DISPOSABLE => strftime($date_format, mktime(12, 0, 0, $month, $dom, $year)),
);

$forward_aligned_periods = array(
    DAILY      => $forward_periods[DAILY],
    WEEKLY     => $forward_periods[WEEKLY],
    MONTHLY    => strftime($date_format, mktime(12, 0, 0, $month, 1, $year)).' - '.strftime($date_format, mktime(12, 0, 0, $month+1, 0, $year)),
    QUARTERLY  => strftime($date_format, mktime(12, 0, 0, $month, 1, $year)).' - '.strftime($date_format, mktime(12, 0, 0, $month+3, 0, $year)),
    HALFYEARLY => strftime($date_format, mktime(12, 0, 0, $month, 1, $year)).' - '.strftime($date_format, mktime(12, 0, 0, $month+6, 0, $year)),
    YEARLY     => strftime($date_format, mktime(12, 0, 0, $month, 1, $year)).' - '.strftime($date_format, mktime(12, 0, 0, $month, 0, $year+1)),
    DISPOSABLE => $forward_periods[DISPOSABLE],
);

$backward_periods = array(
    DAILY      => strftime($date_format, mktime(12, 0, 0, $month, $dom-1, $year)),
    WEEKLY     => strftime($date_format, mktime(12, 0, 0, $month, $dom-7, $year))  .' - '.strftime($date_format, mktime(12, 0, 0, $month, $dom-1, $year)),
    MONTHLY    => strftime($date_format, mktime(12, 0, 0, $month-1, $dom, $year))  .' - '.strftime($date_format, mktime(12, 0, 0, $month, $dom-1, $year)),
    QUARTERLY  => strftime($date_format, mktime(12, 0, 0, $month-3, $dom, $year))  .' - '.strftime($date_format, mktime(12, 0, 0, $month, $dom-1, $year)),
    HALFYEARLY => strftime($date_format, mktime(12, 0, 0, $month-6, $dom, $year))  .' - '.strftime($date_format, mktime(12, 0, 0, $month, $dom-1, $year)),
    YEARLY     => strftime($date_format, mktime(12, 0, 0, $month, $dom, $year-1)).' - '.strftime($date_format, mktime(12, 0, 0, $month, $dom-1, $year)),
    DISPOSABLE => strftime($date_format, mktime(12, 0, 0, $month, $dom-1, $year))
);

$last_sunday = strtotime('last Sunday '.date("Y-m-d"));

$backward_aligned_periods = array(
    DAILY      => $backward_periods[DAILY],
    WEEKLY     => strftime($date_format, $last_sunday-518400)                        .' - '.strftime($date_format, $last_sunday),
    MONTHLY    => strftime($date_format, mktime(12, 0, 0, $month-1, 1, $year))  .' - '.strftime($date_format, mktime(12, 0, 0, $month, 0, $year)),
    QUARTERLY  => strftime($date_format, mktime(12, 0, 0, $month-3, 1, $year))  .' - '.strftime($date_format, mktime(12, 0, 0, $month, 0, $year)),
    HALFYEARLY => strftime($date_format, mktime(12, 0, 0, $month-6, 1, $year))  .' - '.strftime($date_format, mktime(12, 0, 0, $month, 0, $year)),
    YEARLY     => strftime($date_format, mktime(12, 0, 0, $month, 1, $year-1)).' - '.strftime($date_format, mktime(12, 0, 0, $month, 0, $year)),
    DISPOSABLE => $backward_periods[DISPOSABLE]
);

// Special case, ie. you have 01.01.2005-01.31.2005 on invoice, but invoice/
// assignment is made not January, the 1st:

$current_month = strftime($date_format, mktime(12, 0, 0, $month, 1, $year))." - ".strftime($date_format, mktime(12, 0, 0, $month + 1, 0, $year));
$previous_month = strftime($date_format, mktime(12, 0, 0, $month - 1, 1, $year))." - ".strftime($date_format, mktime(12, 0, 0, $month, 0, $year));
$current_period = strftime("%m/%Y", mktime(12, 0, 0, $month, 1, $year));
$next_period = strftime("%m/%Y", mktime(12, 0, 0, $month + 1, 1, $year));
$prev_period = strftime("%m/%Y", mktime(12, 0, 0, $month - 1, 1, $year));

// sale date setting
$saledate = $currtime;
if ($sdate_next) {
    $saledate = strftime("%s", mktime(12, 0, 0, $month + 1, 1, $year));
}

// calculate start and end of numbering period
function get_period($period)
{
    global $dom, $month, $year;
    if (empty($period)) {
        $period = YEARLY;
    }
    $start = 0;
    $end = 0;

    switch ($period) {
        case DAILY:
            $start = strftime("%s", mktime(0, 0, 0, $month, $dom, $year));
            $end = strftime("%s", mktime(0, 0, 0, $month, $dom + 1, $year));
            break;
        case WEEKLY:
            $startweek = $dom - $weekday + 1;
            $start = strftime("%s", mktime(0, 0, 0, $month, $startweek, $year));
            $end = strftime("%s", mktime(0, 0, 0, $month, $startweek + 7, $year));
            break;
        case MONTHLY:
            $start = strftime("%s", mktime(0, 0, 0, $month, 1, $year));
            $end = strftime("%s", mktime(0, 0, 0, $month + 1, 1, $year));
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
            $start = strftime("%s", mktime(0, 0, 0, $startmonth, 1, $year));
            $end = strftime("%s", mktime(0, 0, 0, $startmonth + 3, 1, $year));
            break;
        case HALFYEARLY:
            if ($month <= 6) {
                $startmonth = 1;
            } else {
                $startmonth = 7;
            }
            $start = strftime("%s", mktime(0, 0, 0, $startmonth, 1, $year));
            $end = strftime("%s", mktime(0, 0, 0, $startmonth + 6, 1, $year));
            break;
        case CONTINUOUS:
            $start = strftime("%s", mktime(0, 0, 0, 1, 1, 1970));
            $end = strftime("%s", mktime(0, 0, 0, $month, $dom + 1, $year));
            break;
        default:
            $start = strftime("%s", mktime(0, 0, 0, 1, 1, $year));
            $end = strftime("%s", mktime(0, 0, 0, 1, 1, $year + 1));
    }
    return array('start' => $start, 'end' => $end);
}

$plans = array();
$periods = array(
    0 => YEARLY,
);
$query = "SELECT n.id, n.period, doctype, COALESCE(a.divisionid, 0) AS divid, isdefault 
		FROM numberplans n 
		LEFT JOIN numberplanassignments a ON (a.planid = n.id) 
		WHERE doctype IN (?, ?, ?)";
$results = $DB->GetAll($query, array(DOC_INVOICE, DOC_INVOICE_PRO, DOC_DNOTE));
if (!empty($results)) {
    foreach ($results as $row) {
        if ($row['isdefault']) {
            $plans[$row['divid']][$row['doctype']] = $row['id'];
        }
        $periods[$row['id']] = ($row['period'] ? $row['period'] : YEARLY);
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
                JOIN customerassignments ON customerassignments.customergroupid = customergroups.id
                WHERE customerassignments.customerid = %customerid_alias%
                AND UPPER(customergroups.name) IN ('" . implode("', '", $customergroup_ANDs_regular) . "')
                HAVING COUNT(*) = " . count($customergroup_ANDs_regular) . ')')
            . (empty($customergroup_ANDs_inversed) ? '' : " AND NOT EXISTS (SELECT COUNT(*) FROM customergroups
                JOIN customerassignments ON customerassignments.customergroupid = customergroups.id
                WHERE customerassignments.customerid = %customerid_alias%
                AND UPPER(customergroups.name) IN ('" . implode("', '", $customergroup_ANDs_inversed) . "')
                HAVING COUNT(*) > 0)")
            . ')';
    }
    $customergroups = ' AND (' . implode(' OR ', $customergroup_ORs) . ')';
}

// invoice auto-closes
if ($check_invoices) {
    $DB->Execute(
        "UPDATE documents SET closed = 1
		WHERE " . ($customerid ? 'customerid = ' . $customerid : '1 = 1') . " AND customerid IN (
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

// solid payments
$assigns = $DB->GetAll(
    "SELECT * FROM payments WHERE value <> 0
			AND (period = ? OR (period = ? AND at = ?)
				OR (period = ? AND at = ?)
				OR (period = ? AND at = ?)
				OR (period = ? AND at = ?)
				OR (period = ? AND at = ?))",
    array(DAILY, WEEKLY, $weekday, MONTHLY, $dom, QUARTERLY, $quarter, HALFYEARLY, $halfyear, YEARLY, $yearday)
);
if (!empty($assigns)) {
    foreach ($assigns as $assign) {
        $DB->Execute(
            "INSERT INTO cash (time, type, value, customerid, comment) 
			VALUES (?, ?, ?, ?, ?)",
            array($currtime, 1, $assign['value'] * -1, null, $assign['name']."/".$assign['creditor'])
        );
        if (!$quiet) {
            print "CID:0\tVAL:".$assign['value']."\tDESC:".$assign['name']."/".$assign['creditor'] . PHP_EOL;
        }
    }
}

// let's go, fetch *ALL* assignments in given day
$query = "SELECT a.id, a.tariffid, a.liabilityid, a.customerid, a.recipient_address_id,
		a.period, a.backwardperiod, a.at, a.suspended, a.settlement, a.datefrom, a.dateto, a.pdiscount, a.vdiscount,
		a.invoice, a.separatedocument, c.type AS customertype,
		(CASE WHEN c.type = ? THEN 0 ELSE (CASE WHEN a.liabilityid IS NULL THEN t.splitpayment ELSE l.splitpayment END) END) AS splitpayment,
		(CASE WHEN a.liabilityid IS NULL THEN t.taxcategory ELSE l.taxcategory END) AS taxcategory,
		t.description AS description, a.id AS assignmentid,
		c.divisionid, c.paytype, c.flags AS customerflags,
		a.paytype AS a_paytype, a.numberplanid, a.attribute,
		p.name AS promotion_name, ps.name AS promotion_schema_name, ps.length AS promotion_schema_length,
		d.inv_paytype AS d_paytype, t.period AS t_period, t.numberplanid AS tariffnumberplanid,
		t.flags,
		(CASE WHEN a.liabilityid IS NULL THEN t.type ELSE l.type END) AS tarifftype,
		(CASE WHEN a.liabilityid IS NULL THEN t.name ELSE l.name END) AS name,
		(CASE WHEN a.liabilityid IS NULL THEN t.taxid ELSE l.taxid END) AS taxid,
		(CASE WHEN a.liabilityid IS NULL THEN t.prodid ELSE l.prodid END) AS prodid,
		ROUND(((((100 - a.pdiscount) * (CASE WHEN a.liabilityid IS NULL THEN t.value ELSE l.value END)) / 100) - a.vdiscount) *
			(CASE a.suspended WHEN 0
				THEN 1.0
				ELSE $suspension_percentage / 100
			END), 2) AS unitary_value,
		ROUND(((((100 - a.pdiscount) * (CASE WHEN a.liabilityid IS NULL THEN t.value ELSE l.value END)) / 100) - a.vdiscount) *
			(CASE a.suspended WHEN 0
				THEN 1.0
				ELSE $suspension_percentage / 100
			END), 2) * a.count AS value,
		(CASE WHEN a.liabilityid IS NULL THEN t.currency ELSE l.currency END) AS currency,
		a.count AS count,
		(SELECT COUNT(id) FROM assignments
			WHERE customerid = c.id AND tariffid IS NULL AND liabilityid IS NULL
			AND datefrom <= $currtime
			AND (dateto > $currtime OR dateto = 0)) AS allsuspended
	FROM assignments a
	JOIN customers c ON (a.customerid = c.id)
	LEFT JOIN promotionschemas ps ON ps.id = a.promotionschemaid
	LEFT JOIN promotions p ON p.id = ps.promotionid
	LEFT JOIN tariffs t ON (a.tariffid = t.id)
	LEFT JOIN liabilities l ON (a.liabilityid = l.id)
	LEFT JOIN divisions d ON (d.id = c.divisionid)
	WHERE " . ($customerid ? 'c.id = ' . $customerid : '1 = 1')
        . $customer_status_condition
        . ($divisionid ? ' AND c.divisionid = ' . $divisionid : '')
        . " AND a.commited = 1
		AND ((a.period = ? AND at = ?)
			OR ((a.period = ?
			OR (a.period = ? AND at = ?)
			OR (a.period = ? AND at = ?)
			OR (a.period = ? AND at = ?)
			OR (a.period = ? AND at = ?)
			OR (a.period = ? AND at = ?))
			AND a.datefrom <= ? AND (a.dateto > ? OR a.dateto = 0)))"
        . ($customergroups ? str_replace('%customerid_alias%', 'c.id', $customergroups) : '')
    ." ORDER BY a.customerid, a.recipient_address_id, a.invoice,  a.paytype, a.numberplanid, a.separatedocument, currency, value DESC, a.id";
$services = $DB->GetAll(
    $query,
    array(
        CTYPES_PRIVATE, DISPOSABLE, $today, DAILY, WEEKLY, $weekday, MONTHLY, $last_dom ? 0 : $dom, QUARTERLY, $quarter, HALFYEARLY, $halfyear, YEARLY, $yearday,
        $currtime, $currtime
    )
);

$billing_invoice_description = ConfigHelper::getConfig('payments.billing_invoice_description', 'Phone calls between %backward_periods (for %phones)');

$query = "SELECT
			a.id, a.tariffid, a.customerid, a.period, a.backwardperiod, a.at, a.suspended, a.settlement, a.datefrom,
			0 AS pdiscount, 0 AS vdiscount, a.invoice, a.separatedocument, c.type AS customertype,
			(CASE WHEN a.liabilityid IS NULL THEN t.type ELSE l.type END) AS tarifftype,
			(CASE WHEN c.type = ? THEN 0 ELSE t.splitpayment END) AS splitpayment,
			t.taxcategory AS taxcategory,
			t.description AS description, a.id AS assignmentid,
			c.divisionid, c.paytype, c.flags AS customerflags,
			a.paytype AS a_paytype, a.numberplanid, a.attribute,
			p.name AS promotion_name, ps.name AS promotion_schema_name, ps.length AS promotion_schema_length,
			d.inv_paytype AS d_paytype, t.period AS t_period, t.numberplanid AS tariffnumberplanid,
			t.taxid AS taxid, '' as prodid, voipcost.value, t.currency, voipphones.phones,
			'set' AS liabilityid, '$billing_invoice_description' AS name,
			? AS count,
			(SELECT COUNT(id)
				FROM assignments
				WHERE
					customerid  = c.id    AND
					tariffid    IS NULL   AND
					liabilityid IS NULL   AND
					datefrom <= $currtime AND
					(dateto > $currtime OR dateto = 0)) AS allsuspended
			FROM assignments a
            LEFT JOIN promotionschemas ps ON ps.id = a.promotionschemaid
            LEFT JOIN promotions p ON p.id = ps.promotionid
			JOIN customers c ON (a.customerid = c.id)
			JOIN (
				SELECT ROUND(sum(price), 2) AS value, va.ownerid AS customerid,
					a2.id AS assignmentid
				FROM voip_cdr vc
				JOIN voipaccounts va ON vc.callervoipaccountid = va.id
				JOIN voip_numbers vn ON vn.voip_account_id = va.id AND vn.phone = vc.caller
				JOIN voip_number_assignments vna ON vna.number_id = vn.id
				JOIN assignments a2 ON a2.id = vna.assignment_id
				WHERE
					vc.call_start_time >= (CASE a2.period
						WHEN " . YEARLY     . ' THEN ' . mktime(0, 0, 0, $month, 1, $year-1) . '
						WHEN ' . HALFYEARLY . ' THEN ' . mktime(0, 0, 0, $month-6, 1, $year)   . '
						WHEN ' . QUARTERLY  . ' THEN ' . mktime(0, 0, 0, $month-3, 1, $year)   . '
						WHEN ' . MONTHLY    . ' THEN ' . mktime(0, 0, 0, $month-1, 1, $year)   . '
						WHEN ' . DISPOSABLE . ' THEN ' . $currtime . "
					END) AND
					vc.call_start_time < (CASE a2.period
						WHEN " . YEARLY     . ' THEN ' . mktime(0, 0, 0, $month, 1, $year) . '
						WHEN ' . HALFYEARLY . ' THEN ' . mktime(0, 0, 0, $month, 1, $year) . '
						WHEN ' . QUARTERLY  . ' THEN ' . mktime(0, 0, 0, $month, 1, $year) . '
						WHEN ' . MONTHLY    . ' THEN ' . mktime(0, 0, 0, $month, 1, $year) . '
						WHEN ' . DISPOSABLE . ' THEN ' . ($currtime + 86400) . "
					END)
				GROUP BY va.ownerid, a2.id
			) voipcost ON voipcost.customerid = a.customerid AND voipcost.assignmentid = a.id
			LEFT JOIN (
				SELECT vna2.assignment_id, " . $DB->GroupConcat('vn2.phone', ', ') . " AS phones
				FROM voip_number_assignments vna2
				LEFT JOIN voip_numbers vn2 ON vn2.id = vna2.number_id
				GROUP BY vna2.assignment_id
			) voipphones ON voipphones.assignment_id = a.id
			LEFT JOIN tariffs t ON (a.tariffid = t.id)
			LEFT JOIN liabilities l ON (a.liabilityid = l.id)
			LEFT JOIN divisions d ON (d.id = c.divisionid)
	    WHERE " . ($customerid ? 'c.id = ' . $customerid : '1 = 1')
           . $customer_status_condition
           . ($divisionid ? ' AND c.divisionid = ' . $divisionid : '')
           . " AND t.type = ? AND
	      a.commited = 1 AND
		  ((a.period = ? AND at = ?) OR
		  ((a.period = ? OR
		  (a.period  = ? AND at = ?) OR
		  (a.period  = ? AND at = ?) OR
		  (a.period  = ? AND at = ?) OR
		  (a.period  = ? AND at = ?) OR
		  (a.period  = ? AND at = ?)) AND
		   a.datefrom <= ? AND
		  (a.dateto > ? OR a.dateto = 0)))"
        . ($customergroups ? str_replace('%customerid_alias%', 'c.id', $customergroups) : '')
    ." ORDER BY a.customerid, a.recipient_address_id, a.invoice, a.paytype, a.numberplanid, a.separatedocument, currency, voipcost.value DESC, a.id";

$billings = $DB->GetAll(
    $query,
    array(
        CTYPES_PRIVATE, 1, SERVICE_PHONE,
        DISPOSABLE, $today, DAILY, WEEKLY, $weekday, MONTHLY, $last_dom ? 0 : $dom, QUARTERLY, $quarter, HALFYEARLY, $halfyear, YEARLY, $yearday,
        $currtime, $currtime
    )
);

$assigns = array();

if ($billings) {
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

        if ($billing_idx == $billing_count || $billings[$billing_idx]['customerid'] != $service_customerid) {
            $billing_idx = $old_billing_idx;
            continue;
        } else {
            $assigns[] = $billings[$billing_idx];
            $billing_idx++;
        }
    }
    unset($service);
} else {
    $assigns = $services;
}
unset($services);

$currencyvalues = array();

// correct currency values for foreign currency documents with today's cdate or sdate
// which have estimated currency value earlier (in the moment of document issue)
$daystart = mktime(0, 0, 0, date('n', $currtime), date('j', $currtime), date('Y', $currtime));
$dayend = $daystart + 86399;
$currencydaystart = strtotime('yesterday', $daystart);
$currencycurrtime = strtotime('yesterday', $currtime);
$currencydayend = strtotime('yesterday', $dayend);

$documents = $DB->GetAll(
    'SELECT d.id, d.currency FROM documents d
    JOIN customers c ON c.id = d.customerid
    WHERE ' . ($customerid ? 'd.customerid = ' . $customerid : '1 = 1')
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

$cashes = $DB->GetAll(
    'SELECT cash.id, cash.currency FROM cash
    LEFT JOIN customers c ON c.id = cash.customerid
    WHERE ' . ($customerid ? 'cash.customerid = ' . $customerid : '1 = 1')
    . ($divisionid ? ' AND c.divisionid = ' . $divisionid : '')
    . ' AND cash.docid IS NULL AND cash.currency <> ? AND cash.time >= ? AND cash.time <= ?',
    array(
        Localisation::getCurrentCurrency(),
        $currencydaystart,
        $currencydayend,
    )
);
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

if (empty($assigns)) {
    die;
}

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
        function find_nodes_for_netdev($customerid, $netdevid, &$customer_nodes, &$customer_netlinks)
        {
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
                    $nodeids = array_merge($nodeids, find_nodes_for_netdev(
                        $customerid,
                        $next_netdevid,
                        $customer_nodes,
                        $customer_netlinks
                    ));
                }
                unset($customer_netlink);
            }

            return $nodeids;
        }

        $customer_netlinks = $DB->GetAllByKey(
            "SELECT " . $DB->Concat('nl.src', "'_'", 'nl.dst') . " AS netlink
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
                $netlink['customerid'],
                $netlink['netdevid'],
                $customer_nodes,
                $customer_netlinks
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

$suspended = 0;
$numbers = array();
$customernumbers = array();
$numbertemplates = array();
$invoices = array();
$telecom_services = array();
$currencies = array();
$doctypes = array();
$paytypes = array();
$addresses = array();
$numberplans = array();
$divisions = array();

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

if ($prefer_netto) {
    $taxeslist = $LMS->GetTaxes();
}

// find assignments with tariff reward/penalty flag
// and check if customer applies to this
$reward_to_check = array();
$reward_period_to_check = array();
foreach ($assigns as $assign) {
    $cid = $assign['customerid'];
    if (isset($reward_to_check[$cid]) || ($assign['flags'] & TARIFF_FLAG_REWARD_PENALTY)) {
        $reward_to_check[$cid] = $cid;
    }
    if ($reward_to_check[$cid]) {
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
    $balance = $LMS->GetCustomerBalance($cid, $period_start);
    if ($balance < 0) {
        $rewards[$cid] = false;
        continue;
    }
    $history = $DB->GetAll(
        'SELECT (CASE WHEN d.id IS NULL THEN c.time ELSE c.time + d.paytime * 86400 END) AS time,
            d.id AS docid,
            (c.value * c.currencyvalue) AS value
        FROM cash c
        LEFT JOIN documents d ON d.id = c.docid AND d.type IN ?
        WHERE c.customerid = ?
            AND c.time > ? AND c.time < ?
        ORDER BY time',
        array(
            array(DOC_INVOICE, DOC_CNOTE, DOC_DNOTE, DOC_INVOICE_PRO),
            $cid,
            $period_start,
            $period_end,
        )
    );
    $rewards[$cid] = true;
    if (!empty($history)) {
        foreach ($history as &$record) {
            if (!empty($record['docid'])) {
                $record['time'] = mktime(
                    23,
                    59,
                    59,
                    date('m', $record['time']),
                    date('d', $record['time']),
                    date('Y', $record['time'])
                ) + 1;
            }
        }
        unset($record);
        usort($history, function ($a, $b) {
            return $a['time'] - $b['time'];
        });
        foreach ($history as $record) {
            $balance += $record['value'];
            if (empty($record['docid'])) {
                continue;
            }
            if ($balance < 0) {
                $rewards[$cid] = false;
            }
        }
    }
}

// determine currency values for assignments with foreign currency
// if payments.prefer_netto = true, use value netto+tax
foreach ($assigns as &$assign) {
    if ($prefer_netto) {
        if (isset($assign['netvalue']) && !empty($assign['netvalue']) != 0) {
            $assign['value'] = $assign['netvalue'] * (100 + $taxeslist[$assign['taxid']]['value']) / 100;
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

if (!empty($currencyvalues) && !$quiet) {
    print "Currency quotes:" . PHP_EOL;
    foreach ($currencyvalues as $currency => $value) {
        print '1 ' . $currency . ' = ' . $value . ' ' . Localisation::getCurrentCurrency(). PHP_EOL;
    }
}
$currencyvalues[Localisation::getCurrentCurrency()] = 1.0;

foreach ($assigns as $assign) {
    $cid = $assign['customerid'];
    $divid = ($assign['divisionid'] ? $assign['divisionid'] : 0);

    $assign['value'] = str_replace(',', '.', floatval($assign['value']));
    if (empty($assign['value'])) {
        continue;
    }

    if (($assign['flags'] & TARIFF_FLAG_REWARD_PENALTY)
        && ($assign['value'] < 0 && !$rewards[$cid]
            || $assign['value'] > 0 && $rewards[$cid])) {
        continue;
    }

    if (!isset($assign['taxcategory'])) {
        $assign['taxcategory'] = 0;
    }

    $linktechnology = isset($assignment_linktechnologies[$assign['id']]) ? $assignment_linktechnologies[$assign['id']]['technology'] : null;

    if (!$assign['suspended'] && $assign['allsuspended']) {
        $assign['value'] = $assign['value'] * $suspension_percentage / 100;
    }

    if ($assign['liabilityid']) {
        $desc = $assign['name'];
    } else {
        if (empty($assign['backwardperiod'])) {
            $desc = $comment;
        } else {
            $desc = $backward_comment;
        }
    }

    $p = $assign['period'];

    $desc = str_replace(
        array(
            '%type',
            '%tariff',
            '%attribute',
            '%desc',
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
        ),
        array(
            $assign['tarifftype'] != SERVICE_OTHER ? $SERVICETYPES[$assign['tarifftype']] : '',
            $assign['name'],
            $assign['attribute'],
            $assign['description'],
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
        ),
        $desc
    );

    if (strpos($comment, '%aligned_partial_period') !== false) {
        if ($assign['datefrom']) {
            $datefrom = explode('/', date('Y/m/d', $assign['datefrom']));
        }
        if ($assign['dateto']) {
            $dateto = explode('/', date('Y/m/d', $assign['dateto']));
            $dateto_nextday = explode('/', date('Y/m/d', $assign['dateto'] + 1));
        }
        if (isset($datefrom) && intval($datefrom[2]) != 1 && intval($datefrom[1]) == intval($month) && intval($datefrom[0]) == intval($year)) {
            $first_aligned_partial_period = array(
                DAILY => $forward_periods[DAILY],
                WEEKLY => $forward_periods[WEEKLY],
                MONTHLY => strftime($date_format, mktime(12, 0, 0, $month, $datefrom[2], $year)) . ' - ' . strftime($date_format, mktime(12, 0, 0, $month + 1, 0, $year)),
                QUARTERLY => strftime($date_format, mktime(12, 0, 0, $month, $datefrom[2], $year)) . ' - ' . strftime($date_format, mktime(12, 0, 0, $month + 3, 0, $year)),
                HALFYEARLY => strftime($date_format, mktime(12, 0, 0, $month, $datefrom[2], $year)) . ' - ' . strftime($date_format, mktime(12, 0, 0, $month + 6, 0, $year)),
                YEARLY => strftime($date_format, mktime(12, 0, 0, $month, $datefrom[2], $year)) . ' - ' . strftime($date_format, mktime(12, 0, 0, $month, 0, $year + 1)),
                DISPOSABLE => $forward_periods[DISPOSABLE],
            );
            $desc = str_replace('%aligned_partial_period', $first_aligned_partial_period[$p], $desc);
            unset($first_aligned_partial_period);
        } else {
            if (isset($dateto) && isset($dateto_nextday) && intval($dateto_nextday[2]) != 1 && intval($dateto[1]) == intval($month) && intval($dateto[0]) == intval($year)) {
                $last_aligned_partial_period = array(
                    DAILY => $forward_periods[DAILY],
                    WEEKLY => $forward_periods[WEEKLY],
                    MONTHLY => strftime($date_format, mktime(12, 0, 0, $month, 1, $year)) . ' - ' . strftime($date_format, mktime(12, 0, 0, $month, intval($dateto[2]), $year)),
                    QUARTERLY => strftime($date_format, mktime(12, 0, 0, $month, 1, $year)) . ' - ' . strftime($date_format, mktime(12, 0, 0, $month + 2, intval($dateto[2]), $year)),
                    HALFYEARLY => strftime($date_format, mktime(12, 0, 0, $month, 1, $year)) . ' - ' . strftime($date_format, mktime(12, 0, 0, $month + 5, intval($dateto[2]), $year)),
                    YEARLY => strftime($date_format, mktime(12, 0, 0, $month, 1, $year)) . ' - ' . strftime($date_format, mktime(12, 0, 0, $month, intval($dateto[2]), $year + 1)),
                    DISPOSABLE => $forward_periods[DISPOSABLE],
                );
                $desc = str_replace('%aligned_partial_period', $last_aligned_partial_period[$p], $desc);
                unset($last_aligned_partial_period);
            } else {
                $desc = str_replace('%aligned_partial_period', $forward_aligned_periods[$p], $desc);
            }
        }
    }

    // for phone calls
    if (isset($assign['phones'])) {
        $desc = str_replace('%phones', $assign['phones'], $desc);
    }

    if ($suspension_percentage && ($assign['suspended'] || $assign['allsuspended'])) {
        $desc .= " ".$suspension_description;
    }

    if (!isset($invoices[$cid]) || $assign['separatedocument']) {
        $invoices[$cid] = 0;
    }
    if (!isset($doctypes[$cid])) {
        $doctypes[$cid] = 0;
    }
    if (!isset($paytypes[$cid])) {
        $paytypes[$cid] = 0;
    }
    if (!isset($numberplans[$cid])) {
        $numberplans[$cid] = 0;
    }

    if ($assign['value'] != 0) {
        $val = $assign['value'];
        $currency = $assign['currency'];
        $splitpayment = $assign['splitpayment'];
        if ($assign['t_period'] && $assign['period'] != DISPOSABLE
            && $assign['t_period'] != $assign['period']) {
            if ($assign['t_period'] == YEARLY) {
                $val = $val / 12.0;
            } elseif ($assign['t_period'] == HALFYEARLY) {
                $val = $val / 6.0;
            } elseif ($assign['t_period'] == QUARTERLY) {
                $val = $val / 3.0;
            }

            if ($assign['period'] == YEARLY) {
                $val = $val * 12.0;
            } elseif ($assign['period'] == HALFYEARLY) {
                $val = $val * 6.0;
            } elseif ($assign['period'] == QUARTERLY) {
                $val = $val * 3.0;
            } elseif ($assign['period'] == WEEKLY) {
                $val = $val / 4.0;
            } elseif ($assign['period'] == DAILY) {
                $val = $val / 30.0;
            }
        }

        $val = str_replace(',', '.', sprintf("%.2f", $val));

        $telecom_service = $force_telecom_service_flag && $assign['tarifftype'] != SERVICE_OTHER
            && ($assign['customertype'] == CTYPES_PRIVATE || ($check_customer_vat_payer_flag_for_telecom_service
                && !($assign['customerflags'] & CUSTOMER_FLAG_VAT_PAYER)));

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

            if ($assign['numberplanid']) {
                $plan = $assign['numberplanid'];
            } elseif ($assign['tariffnumberplanid']) {
                $plan = $assign['tariffnumberplanid'];
            } else {
                $plan = isset($plans[$divid][$assign['invoice']]) ? $plans[$divid][$assign['invoice']] : 0;
            }

            if ($invoices[$cid] == 0 || $doctypes[$cid] != $assign['invoice'] || $paytypes[$cid] != $inv_paytype
                || $numberplans[$cid] != $plan || $assign['recipient_address_id'] != $addresses[$cid]
                || $currencies[$cid] != $currency) {
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

                if (!isset($divisions[$customer['divisionid']])) {
                    $divisions[$customer['divisionid']] = $LMS->GetDivision($customer['divisionid']);
                }
                $division = $divisions[$customer['divisionid']];

                $paytime = $customer['paytime'];
                if ($paytime == -1) {
                    if (isset($division['inv_paytime'])) {
                        $paytime = $division['inv_paytime'];
                    } else {
                        $paytime = $deadline;
                    }
                }

                $fullnumber = docnumber(array(
                    'number' => $newnumber,
                    'template' => $numbertemplates[$plan],
                    'cdate' => $currtime,
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

                $DB->Execute(
                    "INSERT INTO documents (number, numberplanid, type, countryid, divisionid, 
					customerid, name, address, zip, city, ten, ssn, cdate, sdate, paytime, paytype,
					div_name, div_shortname, div_address, div_city, div_zip, div_countryid, div_ten, div_regon,
					div_bank, div_account, div_inv_header, div_inv_footer, div_inv_author, div_inv_cplace, fullnumber,
					recipient_address_id, currency, currencyvalue, memo, flags)
					VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                    array(
                        $newnumber,
                        $plan ? $plan : null,
                        $assign['invoice'],
                        $customer['countryid'] ? $customer['countryid'] : null,
                        $customer['divisionid'], $cid,
                        $customer['lastname']." ".$customer['name'],
                        ($customer['postoffice'] && $customer['postoffice'] != $customer['city'] && $customer['street']
                            ? $customer['city'] . ', ' : '') . $customer['address'],
                        $customer['zip'] ? $customer['zip'] : null,
                        $customer['postoffice'] ? $customer['postoffice'] : ($customer['city'] ? $customer['city'] : null),
                        $customer['ten'], $customer['ssn'], $currtime, $saledate, $paytime, $inv_paytype,
                        ($division['name'] ? $division['name'] : ''),
                        ($division['shortname'] ? $division['shortname'] : ''),
                        ($division['address'] ? $division['address'] : ''),
                        ($division['city'] ? $division['city'] : ''),
                        ($division['zip'] ? $division['zip'] : ''),
                        ($division['countryid'] ? $division['countryid'] : null),
                        ($division['ten'] ? $division['ten'] : ''),
                        ($division['regon'] ? $division['regon'] : ''),
                        $division['bank'] ?: null,
                        ($division['account'] ? $division['account'] : ''),
                        ($division['inv_header'] ? $division['inv_header'] : ''),
                        ($division['inv_footer'] ? $division['inv_footer'] : ''),
                        ($division['inv_author'] ? $division['inv_author'] : ''),
                        ($division['inv_cplace'] ? $division['inv_cplace'] : ''),
                        $fullnumber,
                        $recipient_address_id,
                        $currency,
                        $currencyvalues[$currency],
                        empty($customer['documentmemo']) ? null : $customer['documentmemo'],
                        ($telecom_service ? DOC_FLAG_TELECOM_SERVICE : 0)
                            + ($customer['flags'] & CUSTOMER_FLAG_RELATED_ENTITY ? DOC_FLAG_RELATED_ENTITY : 0),
                    )
                );

                $invoices[$cid] = $DB->GetLastInsertID("documents");
                if (!empty($telecom_service)) {
                    $telecom_services[$invoices[$cid]] = $telecom_service;
                }
                $currencies[$cid] = $currency;
                $doctypes[$cid] = $assign['invoice'];
                $LMS->UpdateDocumentPostAddress($invoices[$cid], $cid);
                $paytypes[$cid] = $inv_paytype;
                $addresses[$cid] = $assign['recipient_address_id'];
                $numberplans[$cid] = $plan;
            }

            if ($splitpayment) {
                $DB->Execute(
                    "UPDATE documents SET splitpayment = ? WHERE id = ?",
                    array(1, $invoices[$cid])
                );
            }

            if (!$prefer_settlement_only || !$assign['settlement'] || !$assign['datefrom']) {
                if ($assign['invoice'] == DOC_DNOTE) {
                    $tmp_itemid = 0;
                } else {
                    if (empty($assign['tariffid'])) {
                        $tmp_itemid = $DB->GetOne(
                            "SELECT itemid FROM invoicecontents
                            WHERE tariffid IS NULL AND value=? AND docid=? AND description=? AND pdiscount=? AND vdiscount=?",
                            array(
                                str_replace(',', '.', $val / $assign['count']),
                                $invoices[$cid],
                                $desc,
                                $assign['pdiscount'],
                                $assign['vdiscount']
                            )
                        );
                    } else {
                        $tmp_itemid = $DB->GetOne(
                            "SELECT itemid FROM invoicecontents
                            WHERE tariffid=? AND value=? AND docid=? AND description=? AND pdiscount=? AND vdiscount=?",
                            array(
                                $assign['tariffid'],
                                str_replace(',', '.', $val / $assign['count']),
                                $invoices[$cid],
                                $desc,
                                $assign['pdiscount'],
                                $assign['vdiscount']
                            )
                        );
                    }
                }

                if ($tmp_itemid != 0) {
                    if ($assign['invoice'] == DOC_DNOTE) {
                        $DB->Execute(
                            "UPDATE debitnotecontents SET value = value + ? 
                            WHERE docid = ? AND itemid = ?",
                            array($val, $invoices[$cid], $tmp_itemid)
                        );
                    } else {
                        $DB->Execute(
                            "UPDATE invoicecontents SET count = count + ?
                            WHERE docid = ? AND itemid = ?",
                            array($assign['count'], $invoices[$cid], $tmp_itemid)
                        );
                    }
                    if ($assign['invoice'] == DOC_INVOICE || $proforma_generates_commitment) {
                        $DB->Execute(
                            "UPDATE cash SET value = value + ? 
                            WHERE docid = ? AND itemid = ?",
                            array(str_replace(',', '.', $val * -1), $invoices[$cid], $tmp_itemid)
                        );
                    }
                } else {
                    $itemid++;

                    if ($assign['invoice'] == DOC_DNOTE) {
                        $DB->Execute(
                            "INSERT INTO debitnotecontents (docid, value, description, itemid) 
                            VALUES (?, ?, ?, ?)",
                            array($invoices[$cid], $val, $desc, $itemid)
                        );
                    } else {
                        $DB->Execute(
                            "INSERT INTO invoicecontents (docid, value, taxid, taxcategory, prodid,
                            content, count, description, tariffid, itemid, pdiscount, vdiscount)
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                            array(
                                $invoices[$cid],
                                str_replace(',', '.', $val / $assign['count']),
                                $assign['taxid'],
                                $assign['taxcategory'],
                                $assign['prodid'],
                                $unit_name,
                                $assign['count'],
                                $desc,
                                empty($assign['tariffid']) ? null : $assign['tariffid'],
                                $itemid,
                                $assign['pdiscount'],
                                $assign['vdiscount']
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
                            "INSERT INTO cash (time, value, currency, currencyvalue, taxid, customerid, comment, docid, itemid, linktechnology) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                            array(
                                $currtime,
                                str_replace(',', '.', $val * -1),
                                $currency,
                                $currencyvalues[$currency],
                                $assign['taxid'],
                                $cid,
                                $desc,
                                $invoices[$cid],
                                $itemid,
                                $linktechnology
                            )
                        );
                    }
                }
            }
        } else {
            if (!$prefer_settlement_only || !$assign['settlement'] || !$assign['datefrom']) {
                $DB->Execute(
                    "INSERT INTO cash (time, value, currency, currencyvalue, taxid, customerid, comment, linktechnology) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
                    array(
                        $currtime,
                        str_replace(',', '.', $val * -1),
                        $currency,
                        $currencyvalues[$currency],
                        $assign['taxid'],
                        $cid,
                        $desc,
                        $linktechnology
                    )
                );
            }
        }

        if (!$quiet && (!$prefer_settlement_only || !$assign['settlement'] || !$assign['datefrom'])) {
            print "CID:$cid\tVAL:$val $currency\tDESC:$desc" . PHP_EOL;
        }

        // settlement accounting
        if ($assign['settlement'] && $assign['datefrom']) {
            $alldays = 1;

            $diffdays = sprintf("%d", round(($today - $assign['datefrom']) / 86400));
            $period_start = mktime(0, 0, 0, $month, $dom - $diffdays, $year);
            $period_end = mktime(0, 0, 0, $month, $dom - 1, $year);
            $period = strftime($date_format, $period_start) . " - " . strftime($date_format, $period_end);

            switch ($assign['period']) {
                case WEEKLY:
                    $alldays = 7;
                    break;
                case MONTHLY:
                    $alldays = 30;
                    $d = $dom;
                    $m = $month;
                    $y = $year;
                    $value = 0;
                    $month_days = strftime("%d", mktime(0, 0, 0, $m + 1, 0, $y));
                    while ($diffdays) {
                        if ($d - $diffdays <= 0) {
                            $value += ($d - 1) * $val / $month_days;
                            $diffdays -= ($d - 1);
                        } else {
                            $value += $diffdays * $val / $month_days;
                            $diffdays = 0;
                        }
                        $date = mktime(0, 0, 0, $m, 0, $y);
                        $month_days = strftime("%d", $date);
                        $d = $month_days + 1;
                        $m = strftime("%m", $date);
                        $y = strftime("%Y", $date);
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

            $value = str_replace(',', '.', sprintf("%.2f", $alldays != 30 ? $diffdays * $val / $alldays : $value));

            if (floatval($value)) {
                //print "value: $val diffdays: $diffdays alldays: $alldays settl_value: $value" . PHP_EOL;

                if (empty($assign['backwardperiod'])) {
                    $sdesc = $s_comment;
                } else {
                    $sdesc = $s_backward_comment;
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
                    ),
                    $sdesc
                );

                if ($assign['invoice']) {
                    if ($assign['invoice'] == DOC_DNOTE) {
                        $tmp_itemid = 0;
                    } else {
                        if (empty($assign['tariffid'])) {
                            $tmp_itemid = $DB->GetOne(
                                "SELECT itemid FROM invoicecontents
                                WHERE tariffid IS NULL AND value = ? AND docid = ? AND description = ?",
                                array(
                                    str_replace(',', '.', $value / $assign['count']),
                                    $invoices[$cid],
                                    $sdesc
                                )
                            );
                        } else {
                            $tmp_itemid = $DB->GetOne(
                                "SELECT itemid FROM invoicecontents
                                WHERE tariffid = ? AND value = ? AND docid = ? AND description = ?",
                                array(
                                    $assign['tariffid'],
                                    str_replace(',', '.', $value / $assign['count']),
                                    $invoices[$cid],
                                    $sdesc
                                )
                            );
                        }
                    }

                    if ($tmp_itemid != 0) {
                        $DB->Execute(
                            "UPDATE invoicecontents SET count = count + ?
							WHERE docid = ? AND itemid = ?",
                            array($assign['count'], $invoices[$cid], $tmp_itemid)
                        );
                        if ($assign['invoice'] == DOC_INVOICE || $proforma_generates_commitment) {
                            $DB->Execute(
                                "UPDATE cash SET value = value + ?
								WHERE docid = ? AND itemid = ?",
                                array(str_replace(',', '.', $value * -1), $invoices[$cid], $tmp_itemid)
                            );
                        }
                    } else {
                        $itemid++;

                        if ($assign['invoice'] == DOC_DNOTE) {
                            $DB->Execute(
                                "INSERT INTO debitnotecontents (docid, value, description, itemid) 
								VALUES (?, ?, ?, ?)",
                                array($invoices[$cid], $value, $desc, $itemid)
                            );
                        } else {
                            $DB->Execute(
                                "INSERT INTO invoicecontents (docid, value, taxid, taxcategory, prodid,
								content, count, description, tariffid, itemid, pdiscount, vdiscount)
								VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                                array(
                                    $invoices[$cid],
                                    str_replace(',', '.', $value / $assign['count']),
                                    $assign['taxid'],
                                    $assign['taxcategory'],
                                    $assign['prodid'],
                                    $unit_name,
                                    $assign['count'],
                                    $sdesc,
                                    empty($assign['tariffid']) ? null : $assign['tariffid'],
                                    $itemid,
                                    $assign['pdiscount'],
                                    $assign['vdiscount']
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
                                "INSERT INTO cash (time, value, currency, currencyvalue, taxid, customerid, comment, docid, itemid, linktechnology)
								VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                                array(
                                    $currtime,
                                    str_replace(',', '.', $value * -1),
                                    $currency,
                                    $currencyvalues[$currency],
                                    $assign['taxid'],
                                    $cid,
                                    $sdesc,
                                    $invoices[$cid],
                                    $itemid,
                                    $linktechnology
                                )
                            );
                        }
                    }
                } else {
                    $DB->Execute(
                        "INSERT INTO cash (time, value, currency, currencyvalue, taxid, customerid, comment, linktechnology)
						VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
                        array(
                            $currtime,
                            str_replace(',', '.', $value * -1),
                            $currency,
                            $currencyvalues[$currency],
                            $assign['taxid'],
                            $cid,
                            $sdesc,
                            $linktechnology
                        )
                    );
                }

                if (!$quiet) {
                    print "CID:$cid\tVAL:$value $currency\tDESC:$sdesc" . PHP_EOL;
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
		WHERE " . ($customerid ? 'customerid = ' . $customerid : '1 = 1') . " AND customerid IN (
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
            WHERE " . ($customerid ? 'a.customerid = ' . $customerid : '1 = 1')
                . ($divisionid ? ' AND c.divisionid = ' . $divisionid : '')
                . " AND ((a.dateto <> 0 AND a.dateto < $today - ? * 86400
                    OR (a.period = ? AND a.at < $today - ? * 86400))
                AND a.liabilityid IS NOT NULL)
		)",
        array($delete_old_assignments_after_days, DISPOSABLE, $delete_old_assignments_after_days)
    );
    $DB->Execute(
        "DELETE FROM assignments
		WHERE " . ($customerid ? 'customerid = ' . $customerid : '1 = 1')
            . ($divisionid ? ' AND EXISTS (SELECT c.id FROM customers c WHERE c.divisionid = ' . $divisionid . ' AND c.id = customerid)' : '')
            . " AND ((dateto <> 0 AND dateto < $today - ? * 86400)
			OR (period = ? AND at < $today - ? * 86400))",
        array($delete_old_assignments_after_days, DISPOSABLE, $delete_old_assignments_after_days)
    );
}

// clear voip tariff rule states
$DB->Execute("DELETE FROM voip_rule_states");

$DB->Destroy();
