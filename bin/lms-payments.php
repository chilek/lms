#!/usr/bin/env php
<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2016 LMS Developers
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
	'C:' => 'config-file:',
	'q' => 'quiet',
	'h' => 'help',
	'v' => 'version',
	'f:' => 'fakedate:',
);

foreach ($parameters as $key => $val) {
	$val = preg_replace('/:/', '', $val);
	$newkey = preg_replace('/:/', '', $key);
	$short_to_longs[$newkey] = $val;
}
$options = getopt(implode('', array_keys($parameters)), $parameters);
foreach ($short_to_longs as $short => $long)
	if (array_key_exists($short, $options)) {
		$options[$long] = $options[$short];
		unset($options[$short]);
	}

if (array_key_exists('version', $options)) {
	print <<<EOF
lms-payments.php
(C) 2001-2016 LMS Developers

EOF;
	exit(0);
}

if (array_key_exists('help', $options)) {
	print <<<EOF
lms-payments.php
(C) 2001-2016 LMS Developers

-C, --config-file=/etc/lms/lms.ini      alternate config file (default: /etc/lms/lms.ini);
-h, --help                      print this help and exit;
-v, --version                   print version info and exit;
-q, --quiet                     suppress any output, except errors;
-f, --fakedate=YYYY/MM/DD       override system date

EOF;
	exit(0);
}

$quiet = array_key_exists('quiet', $options);
if (!$quiet) {
	print <<<EOF
lms-payments.php
(C) 2001-2016 LMS Developers

EOF;
}

if (array_key_exists('config-file', $options))
	$CONFIG_FILE = $options['config-file'];
else
	$CONFIG_FILE = DIRECTORY_SEPARATOR . 'etc' . DIRECTORY_SEPARATOR . 'lms' . DIRECTORY_SEPARATOR . 'lms.ini';

if (!$quiet)
	echo "Using file ".$CONFIG_FILE." as config." . PHP_EOL;

if (!is_readable($CONFIG_FILE))
	die('Unable to read configuration file ['.$CONFIG_FILE.']!'); 

define('CONFIG_FILE', $CONFIG_FILE);

$CONFIG = (array) parse_ini_file($CONFIG_FILE, true);

// Check for configuration vars and set default values
$CONFIG['directories']['sys_dir'] = (!isset($CONFIG['directories']['sys_dir']) ? getcwd() : $CONFIG['directories']['sys_dir']);
$CONFIG['directories']['lib_dir'] = (!isset($CONFIG['directories']['lib_dir']) ? $CONFIG['directories']['sys_dir'] . DIRECTORY_SEPARATOR . 'lib' : $CONFIG['directories']['lib_dir']);

define('SYS_DIR', $CONFIG['directories']['sys_dir']);
define('LIB_DIR', $CONFIG['directories']['lib_dir']);

// Load autoloader
$composer_autoload_path = SYS_DIR . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
if (file_exists($composer_autoload_path)) {
    require_once $composer_autoload_path;
} else {
    die("Composer autoload not found. Run 'composer install' command from LMS directory and try again. More informations at https://getcomposer.org/");
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

$deadline = ConfigHelper::getConfig('payments.deadline', 14);
$sdate_next = ConfigHelper::getConfig('payments.saledate_next_month', 0);
$paytype = ConfigHelper::getConfig('payments.paytype', 2); // TRANSFER
$comment = ConfigHelper::getConfig('payments.comment', "Tariff %tariff - %attribute subscription for period %period");
$s_comment = ConfigHelper::getConfig('payments.settlement_comment', ConfigHelper::getConfig('payments.comment'));
$suspension_description = ConfigHelper::getConfig('payments.suspension_description', '');
$suspension_percentage = ConfigHelper::getConfig('finances.suspension_percentage', 0);
$unit_name = trans(ConfigHelper::getConfig('payments.default_unit_name'));

function localtime2() {
	global $fakedate;
	if (!empty($fakedate)) {
		$date = explode("/", $fakedate);
		return mktime(0, 0, 0, $date[1], $date[2], $date[0]);
	} else
		return time();
}

$fakedate = (array_key_exists('fakedate', $options) ? $options['fakedate'] : NULL);

$currtime = strftime("%s", localtime2());
$month = intval(strftime("%m", localtime2()));
$dom = intval(strftime("%d", localtime2()));
$year = strftime("%Y", localtime2());
$weekday = strftime("%u", localtime2());
$yearday = strftime("%j", localtime2());

if (is_leap_year($year) && $yearday > 31 + 28)
	$yearday -= 1;

if (!empty($fakedate))
	$today = $currtime;
else
	$today = mktime(0, 0, 0, $month, $dom, $year);

if ($month == 1 || $month == 4 || $month == 7 || $month == 10)
	$quarter = $dom;
elseif ($month == 2 || $month == 5 || $month == 8 || $month == 11)
	$quarter = $dom + 100;
else
	$quarter = $dom + 200;

if ($month > 6)
	$halfyear = $dom + ($month - 7) * 100;
else
	$halfyear = $dom + ($month - 1) * 100;

$date_format = ConfigHelper::getConfig('payments.date_format');
$forward_periods = array(
	DAILY      => strftime($date_format, mktime(12, 0, 0, $month, $dom, $year)),
	WEEKLY     => strftime($date_format, mktime(12, 0, 0, $month, $dom, $year)).' - '.strftime($date_format, mktime(12, 0, 0, $month  , $dom+6, $year)),
	MONTHLY    => strftime($date_format, mktime(12, 0, 0, $month, $dom, $year)).' - '.strftime($date_format, mktime(12, 0, 0, $month+1, $dom-1, $year)),
	QUARTERLY  => strftime($date_format, mktime(12, 0, 0, $month, $dom, $year)).' - '.strftime($date_format, mktime(12, 0, 0, $month+3, $dom-1, $year)),
	HALFYEARLY => strftime($date_format, mktime(12, 0, 0, $month, $dom, $year)).' - '.strftime($date_format, mktime(12, 0, 0, $month+6, $dom-1, $year)),
	YEARLY     => strftime($date_format, mktime(12, 0, 0, $month, $dom, $year)).' - '.strftime($date_format, mktime(12, 0, 0, $month  , $dom-1, $year+1)),
	DISPOSABLE => strftime($date_format, mktime(12, 0, 0, $month, $dom, $year)),
);

$forward_aligned_periods = array(
	DAILY      => $forward_periods[DAILY],
	WEEKLY     => $forward_periods[WEEKLY],
	MONTHLY    => strftime($date_format, mktime(12, 0, 0, $month, 1, $year)).' - '.strftime($date_format, mktime(12, 0, 0, $month+1, 0, $year)),
	QUARTERLY  => strftime($date_format, mktime(12, 0, 0, $month, 1, $year)).' - '.strftime($date_format, mktime(12, 0, 0, $month+3, 0, $year)),
	HALFYEARLY => strftime($date_format, mktime(12, 0, 0, $month, 1, $year)).' - '.strftime($date_format, mktime(12, 0, 0, $month+6, 0, $year)),
	YEARLY     => strftime($date_format, mktime(12, 0, 0, $month, 1, $year)).' - '.strftime($date_format, mktime(12, 0, 0, $month,   0, $year+1)),
	DISPOSABLE => $forward_periods[DISPOSABLE],
);

$backward_periods = array(
	DAILY      => strftime($date_format, mktime(12, 0, 0, $month,   $dom-1, $year)),
	WEEKLY     => strftime($date_format, mktime(12, 0, 0, $month,   $dom-7, $year))  .' - '.strftime($date_format, mktime(12, 0, 0, $month, $dom-1, $year)),
	MONTHLY    => strftime($date_format, mktime(12, 0, 0, $month-1, $dom,   $year))  .' - '.strftime($date_format, mktime(12, 0, 0, $month, $dom-1, $year)),
	QUARTERLY  => strftime($date_format, mktime(12, 0, 0, $month-3, $dom,   $year))  .' - '.strftime($date_format, mktime(12, 0, 0, $month, $dom-1, $year)),
	HALFYEARLY => strftime($date_format, mktime(12, 0, 0, $month-6, $dom,   $year))  .' - '.strftime($date_format, mktime(12, 0, 0, $month, $dom-1, $year)),
	YEARLY     => strftime($date_format, mktime(12, 0, 0, $month,   $dom,   $year-1)).' - '.strftime($date_format, mktime(12, 0, 0, $month, $dom-1, $year)),
	DISPOSABLE => strftime($date_format, mktime(12, 0, 0, $month,   $dom-1, $year))
);

$last_sunday = strtotime('last Sunday '.date("Y-m-d"));

$backward_aligned_periods = array(
	DAILY      => $backward_periods[DAILY],
	WEEKLY     => strftime($date_format, $last_sunday-518400)                        .' - '.strftime($date_format, $last_sunday),
	MONTHLY    => strftime($date_format, mktime(12, 0, 0, $month-1, 1     , $year))  .' - '.strftime($date_format, mktime(12, 0, 0, $month-1, date("t"), $year)),
	QUARTERLY  => strftime($date_format, mktime(12, 0, 0, $month-3, 1     , $year))  .' - '.strftime($date_format, mktime(12, 0, 0, $month-1, date("t"), $year)),
	HALFYEARLY => strftime($date_format, mktime(12, 0, 0, $month-6, 1     , $year))  .' - '.strftime($date_format, mktime(12, 0, 0, $month-1, date("t"), $year)),
	YEARLY     => strftime($date_format, mktime(12, 0, 0, $month  , 1     , $year-1)).' - '.strftime($date_format, mktime(12, 0, 0, $month-1, date("t"), $year)),
	DISPOSABLE => $backward_periods[DISPOSABLE]
);

// Special case, ie. you have 01.01.2005-01.31.2005 on invoice, but invoice/
// assignment is made not January, the 1st:

$current_month = strftime($date_format, mktime(12, 0, 0, $month, 1, $year))." - ".strftime($date_format, mktime(12, 0, 0, $month + 1, 0, $year));
$current_period = strftime("%m/%Y", mktime(12, 0, 0, $month, 1, $year));
$next_period = strftime("%m/%Y", mktime(12, 0, 0, $month + 1, 1, $year));
$prev_period = strftime("%m/%Y", mktime(12, 0, 0, $month - 1, 1, $year));

// sale date setting
$saledate = $currtime;
if ($sdate_next)
	$saledate = strftime("%s", mktime(12, 0, 0, $month + 1, 1, $year));

// calculate start and end of numbering period
function get_period($period) {
	global $dom, $month, $year;
	if (empty($period))
		$period = YEARLY;
	$start = 0;
	$end = 0;

	switch ($period)
	{
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
			if ($month <= 3)
				$startmonth = 1;
			elseif ($month <= 6)
				$startmonth = 4;
			elseif ($month <= 9)
				$startmonth = 7;
			else
				$startmonth = 10;
			$start = strftime("%s", mktime(0, 0, 0, $startmonth, 1, $year));
			$end = strftime("%s", mktime(0, 0, 0, $startmonth + 3, 1, $year));
			break;
		case HALFYEARLY:
			if ($month <= 6)
				$startmonth = 1;
			else
				$startmonth = 7;
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

$query = "SELECT n.id, n.period, COALESCE(a.divisionid, 0) AS divid, isdefault 
		FROM numberplans n 
		LEFT JOIN numberplanassignments a ON (a.planid = n.id) 
		WHERE doctype = ?";
$results = $DB->GetAll($query, array(DOC_INVOICE));
if (!empty($results))
	foreach ($results as $row) {
		if ($row['isdefault'])
			$plans[$row['divid']] = $row['id'];
		$periods[$row['id']] = ($row['period'] ? $row['period'] : YEARLY);
	}

// prepare customergroups in sql query
$customergroups = " AND EXISTS (SELECT 1 FROM customergroups g, customerassignments ca 
	WHERE c.id = ca.customerid 
	AND g.id = ca.customergroupid 
	AND (%groups)) ";
$groupnames = ConfigHelper::getConfig('payments.customergroups');
$groupsql = "";
$groups = preg_split("/[[:blank:]]+/", $groupnames, -1, PREG_SPLIT_NO_EMPTY);
foreach ($groups as $group) {
	if (!empty($groupsql))
		$groupsql .= " OR ";
	$groupsql .= "UPPER(g.name) = UPPER('".$group."')";
}
if (!empty($groupsql))
	$customergroups = preg_replace("/\%groups/", $groupsql, $customergroups);

# let's go, fetch *ALL* assignments in given day
$query = "SELECT a.tariffid, a.liabilityid, a.customerid, 
		a.period, a.at, a.suspended, a.settlement, a.datefrom, a.pdiscount, a.vdiscount, 
		a.invoice, t.description AS description, a.id AS assignmentid, 
		c.divisionid, c.paytype, a.paytype AS a_paytype, a.numberplanid, a.attribute,
		d.inv_paytype AS d_paytype, t.period AS t_period, t.numberplanid AS tariffnumberplanid,
		(CASE a.liabilityid WHEN 0 THEN t.type ELSE -1 END) AS tarifftype, 
		(CASE a.liabilityid WHEN 0 THEN t.name ELSE l.name END) AS name, 
		(CASE a.liabilityid WHEN 0 THEN t.taxid ELSE l.taxid END) AS taxid, 
		(CASE a.liabilityid WHEN 0 THEN t.prodid ELSE l.prodid END) AS prodid, 
		ROUND(((((100 - a.pdiscount) * (CASE a.liabilityid WHEN 0 THEN t.value ELSE l.value END)) / 100) - a.vdiscount) *
			(CASE a.suspended WHEN 0
				THEN 1.0
				ELSE $suspension_percentage / 100
			END), 2) AS value,
		(SELECT COUNT(id) FROM assignments 
			WHERE customerid = c.id AND tariffid = 0 AND liabilityid = 0 
			AND datefrom <= $currtime
			AND (dateto > $currtime OR dateto = 0)) AS allsuspended 
	FROM assignments a 
	JOIN customers c ON (a.customerid = c.id) 
	LEFT JOIN tariffs t ON (a.tariffid = t.id) 
	LEFT JOIN liabilities l ON (a.liabilityid = l.id) 
	LEFT JOIN divisions d ON (d.id = c.divisionid) 
	WHERE (c.status = ? OR c.status = ?)
		AND ((a.period = ? AND at = ?)
			OR ((a.period = ?
			OR (a.period = ? AND at = ?)
			OR (a.period = ? AND at = ?)
			OR (a.period = ? AND at = ?)
			OR (a.period = ? AND at = ?)
			OR (a.period = ? AND at = ?))
			AND a.datefrom <= ? AND (a.dateto > ? OR a.dateto = 0)))"
		.(!empty($groupnames) ? $customergroups : "")
	." ORDER BY a.customerid, a.invoice, a.paytype, a.numberplanid, value DESC";
$assigns = $DB->GetAll($query, array(CSTATUS_CONNECTED, CSTATUS_DEBT_COLLECTION,
	DISPOSABLE, $today, DAILY, WEEKLY, $weekday, MONTHLY, $dom, QUARTERLY, $quarter, HALFYEARLY, $halfyear, YEARLY, $yearday,
	$currtime, $currtime));


$date = new DateTime(date("Y-m-d"));
$time = $date->format("U");
unset($date);

$billing_invoice_description = ConfigHelper::getConfig('payments.billing_invoice_description', 'Phone calls between %backward_period');

$query = 'SELECT
            a.tariffid, a.customerid, a.period, a.at, a.suspended, a.settlement, a.datefrom,
            a.pdiscount, a.vdiscount, a.invoice, t.description AS description, a.id AS assignmentid,
			c.divisionid, c.paytype, a.paytype AS a_paytype, a.numberplanid, a.attribute,
			d.inv_paytype AS d_paytype, t.period AS t_period, t.numberplanid AS tariffnumberplanid,
			t.type AS tarifftype, t.taxid AS taxid, \'\' as prodid, '
			. "'set' as liabilityid,"
			. "'$billing_invoice_description' as name,
		    ROUND((SELECT
			          CASE WHEN sum(price) IS NULL THEN 0 ELSE sum(price) END
			       FROM
			          voip_cdr vc LEFT JOIN voipaccounts va ON vc.callervoipaccountid = va.id
			       WHERE
			          va.ownerid = a.customerid AND
			          vc.call_start_time >= (CASE a.period
				                               WHEN " . YEARLY     . ' THEN ' . strtotime("-1 year"  ,$time) . '
				                               WHEN ' . HALFYEARLY . ' THEN ' . strtotime("-6 month" ,$time) . '
				                               WHEN ' . QUARTERLY  . ' THEN ' . strtotime("-3 month" ,$time) . '
				                               WHEN ' . MONTHLY    . ' THEN ' . strtotime("-1 month" ,$time) . '
				                               WHEN ' . DISPOSABLE . ' THEN ' . strtotime("-1 day"   ,$time) . "
				                             END)
	              ),2) AS value,
		  (SELECT
		     COUNT(id)
		   FROM
		     assignments
		   WHERE
		     customerid  = c.id    AND
		     tariffid    = 0       AND
			 datefrom <= $currtime AND
			 (dateto > $currtime OR dateto = 0)) AS allsuspended
	       FROM assignments a
	            JOIN customers c ON (a.customerid = c.id)
	            LEFT JOIN tariffs t ON (a.tariffid = t.id)
	            LEFT JOIN divisions d ON (d.id = c.divisionid
	      )
	    WHERE
	      (c.status  = ? OR c.status = ?) AND
		  ((a.period = ? AND at = ?) OR
		  ((a.period = ? OR
		  (a.period  = ? AND at = ?) OR
		  (a.period  = ? AND at = ?) OR
		  (a.period  = ? AND at = ?) OR
		  (a.period  = ? AND at = ?) OR
		  (a.period  = ? AND at = ?)) AND
		   a.datefrom <= ? AND
		  (a.dateto > ? OR a.dateto = 0)))"
		.(!empty($groupnames) ? $customergroups : "")
	." ORDER BY a.customerid, a.invoice, a.paytype, a.numberplanid, value DESC";

$billings = $DB->GetAll($query, array(CSTATUS_CONNECTED, CSTATUS_DEBT_COLLECTION,
	DISPOSABLE, $today, DAILY, WEEKLY, $weekday, MONTHLY, $dom, QUARTERLY, $quarter, HALFYEARLY, $halfyear, YEARLY, $yearday,
	$currtime, $currtime));	
	
foreach ($billings as $v)
	array_push($assigns, $v);

if (empty($assigns))
	die;

$suspended = 0;
$invoices = array();
$paytypes = array();
$numberplans = array();

foreach ($assigns as $assign) {
	$cid = $assign['customerid'];
	$divid = ($assign['divisionid'] ? $assign['divisionid'] : 0);

	if ($assign['value'] == 0) continue;

	if (!$assign['suspended'] && $assign['allsuspended'])
		$assign['value'] = $assign['value'] * $suspension_percentage / 100;

	if ($assign['liabilityid'])
		$desc = $assign['name'];
	else
		$desc = $comment;

	$desc = preg_replace("/\%type/", $assign['tarifftype'] != TARIFF_OTHER ? $TARIFFTYPES[$assign['tarifftype']] : '', $desc);
	$desc = preg_replace("/\%tariff/", $assign['name'], $desc);
	$desc = preg_replace("/\%attribute/", $assign['attribute'], $desc);
	$desc = preg_replace("/\%desc/", $assign['description'], $desc);
	$desc = preg_replace("/\%current_month/", $current_month, $desc);
	$desc = preg_replace("/\%current_period/", $current_period, $desc);
	$desc = preg_replace("/\%next_period/", $next_period, $desc);
	$desc = preg_replace("/\%prev_period/", $prev_period, $desc);

	$p = $assign['period'];

	// better use this
	$desc = preg_replace("/\%forward_periods/"         , $forward_periods[$p]         , $desc);
	$desc = preg_replace("/\%forward_aligned_periods/" , $forward_aligned_periods[$p] , $desc);
	$desc = preg_replace("/\%backward_periods/"        , $backward_periods[$p]        , $desc);
	$desc = preg_replace("/\%backward_aligned_periods/", $backward_aligned_periods[$p], $desc);

	// for backward references
	$desc = preg_replace("/\%forward_period/"          , $forward_periods[$p]         , $desc);
	$desc = preg_replace("/\%forward_period_aligned/"  , $forward_aligned_periods[$p] , $desc);
	$desc = preg_replace("/\%period/"                  , $forward_periods[$p]         , $desc);
	$desc = preg_replace("/\%aligned_period/"          , $forward_aligned_periods[$p] , $desc);

	if ($suspension_percentage && ($assign['suspended'] || $assign['allsuspended']))
		$desc .= " ".$suspension_description;

	if (!array_key_exists($cid, $invoices)) $invoices[$cid] = 0;
	if (!array_key_exists($cid, $paytypes)) $paytypes[$cid] = 0;
	if (!array_key_exists($cid, $numberplans)) $numberplans[$cid] = 0;

	if ($assign['value'] != 0)
	{
		$val = $assign['value'];
		if ($assign['t_period'] && $assign['period'] != DISPOSABLE
			&& $assign['t_period'] != $assign['period'])
		{
			if ($assign['t_period'] == YEARLY)
				$val = $val / 12.0;
			elseif ($assign['t_period'] == HALFYEARLY)
				$val = $val / 6.0;
			elseif ($assign['t_period'] == QUARTERLY)
				$val = $val / 3.0;

			if ($assign['period'] == YEARLY)
				$val = $val * 12.0;
			elseif ($assign['period'] == HALFYEARLY)
				$val = $val * 6.0;
			elseif ($assign['period'] == QUARTERLY)
				$val = $val * 3.0;
			elseif ($assign['period'] == WEEKLY)
				$val = $val / 4.0;
			elseif ($assign['period'] == DAILY)
				$val = $val / 30.0;
		}

		$val = str_replace(',', '.', sprintf("%.2f", $val));

		if ($assign['invoice'])
		{
			if ($assign['a_paytype'])
				$inv_paytype = $assign['a_paytype'];
			elseif ($assign['paytype'])
				$inv_paytype = $assign['paytype'];
			elseif ($assign['d_paytype'])
				$inv_paytype = $assign['d_paytype'];
			else
				$inv_paytype = $paytype;

			if ($assign['numberplanid'])
				$plan = $assign['numberplanid'];
			elseif ($assign['tariffnumberplanid'])
				$plan = $assign['tariffnumberplanid'];
			else
				$plan = (array_key_exists($divid, $plans) ? $plans[$divid] : 0);

			if ($invoices[$cid] == 0 || $paytypes[$cid] != $inv_paytype || $numberplans[$cid] != $plan)
			{
				if (!isset($numbers[$plan]))
				{
					$period = get_period($periods[$plan]);
					$numbers[$plan] = (($number = $DB->GetOne("SELECT MAX(number) AS number FROM documents 
							WHERE cdate >= ? AND cdate <= ? AND type = 1 AND numberplanid = ?",
							array($period['start'], $period['end'], $plan))) != 0 ? $number : 0);
					$numbertemplates[$plan] = $DB->GetOne("SELECT template FROM numberplans WHERE id = ?", array($plan));
				}

				$itemid = 0;
				$numbers[$plan]++;

				$customer = $DB->GetRow("SELECT lastname, name, address, city, zip, ssn, ten, countryid, divisionid, paytime 
						FROM customeraddressview WHERE id = $cid");

				$division = $DB->GetRow("SELECT name, shortname, address, city, zip, countryid, ten, regon,
						account, inv_header, inv_footer, inv_author, inv_cplace
						FROM divisions WHERE id = ?", array($customer['divisionid']));

				$paytime = $customer['paytime'];
				if ($paytime == -1) $paytime = $deadline;

				$fullnumber = docnumber($numbers[$plan], $numbertemplates[$plan], $currtime);
				$DB->Execute("INSERT INTO documents (number, numberplanid, type, countryid, divisionid, 
					customerid, name, address, zip, city, ten, ssn, cdate, sdate, paytime, paytype,
					div_name, div_shortname, div_address, div_city, div_zip, div_countryid, div_ten, div_regon,
					div_account, div_inv_header, div_inv_footer, div_inv_author, div_inv_cplace, fullnumber) 
					VALUES(?, ?, 1, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
					array($numbers[$plan], $plan, $customer['countryid'], $customer['divisionid'], $cid,
					$customer['lastname']." ".$customer['name'], $customer['address'], $customer['zip'],
					$customer['city'], $customer['ten'], $customer['ssn'], $currtime, $saledate, $paytime, $inv_paytype,
					($division['name'] ? $division['name'] : ''),
					($division['shortname'] ? $division['shortname'] : ''),
					($division['address'] ? $division['address'] : ''), 
					($division['city'] ? $division['city'] : ''), 
					($division['zip'] ? $division['zip'] : ''),
					($division['countryid'] ? $division['countryid'] : 0),
					($division['ten'] ? $division['ten'] : ''), 
					($division['regon'] ? $division['regon'] : ''), 
					($division['account'] ? $division['account'] : ''),
					($division['inv_header'] ? $division['inv_header'] : ''), 
					($division['inv_footer'] ? $division['inv_footer'] : ''), 
					($division['inv_author'] ? $division['inv_author'] : ''), 
					($division['inv_cplace'] ? $division['inv_cplace'] : ''),
					$fullnumber,
					));

				$invoices[$cid] = $DB->GetLastInsertID("documents");
				$paytypes[$cid] = $inv_paytype;
				$numberplans[$cid] = $plan;
			}
			if (($tmp_itemid = $DB->GetOne("SELECT itemid FROM invoicecontents 
				WHERE tariffid=? AND value=$val AND docid=? AND description=? AND pdiscount=? AND vdiscount=?",
				array($assign['tariffid'], $invoices[$cid], $desc, $assign['pdiscount'], $assign['vdiscount']))) != 0)
			{
				$DB->Execute("UPDATE invoicecontents SET count=count+1 
					WHERE tariffid=? AND docid=? AND value=? AND description=? AND pdiscount=? AND vdiscount=?",
					array($assign['tariffid'], $invoices[$cid], $assign['value'], $desc, $assign['pdiscount'], $assign['vdiscount']));
				$DB->Execute("UPDATE cash SET value=value+($val*-1) 
					WHERE docid = ? AND itemid = $tmp_itemid", array($invoices[$cid]));
			}
			else
			{
				$itemid++;

				$DB->Execute("INSERT INTO invoicecontents (docid, value, taxid, prodid, 
					content, count, description, tariffid, itemid, pdiscount, vdiscount) 
					VALUES (?, $val, ?, ?, ?, 1, ?, ?, $itemid, ?, ?)",
					array($invoices[$cid], $assign['taxid'], $assign['prodid'], $unit_name,
					$desc, $assign['tariffid'], $assign['pdiscount'], $assign['vdiscount']));
				$DB->Execute("INSERT INTO cash (time, value, taxid, customerid, comment, docid, itemid) 
					VALUES ($currtime, $val * -1, ?, $cid, ?, ?, $itemid)",
					array($assign['taxid'], $desc, $invoices[$cid]));
			}
		}
		else
			$DB->Execute("INSERT INTO cash (time, value, taxid, customerid, comment) 
				VALUES ($currtime, $val * -1, ?, $cid, ?)", array($assign['taxid'], $desc));

		if (!$quiet) print "CID:$cid\tVAL:$val\tDESC:$desc" . PHP_EOL;

		// settlement accounting
		if ($assign['settlement'] && $assign['datefrom'])
		{
			$alldays = 1;

			$diffdays = sprintf("%d", ($today - $assign['datefrom']) / 86400);
			$period_start = mktime(0, 0, 0, $month, $dom - $diffdays, $year);
			$period_end = mktime(0, 0, 0, $month, $dom - 1, $year);
			$period = strftime($date_format, $period_start) . " - " . strftime($date_format, $period_end);

			switch ($assign['period']) {
				case WEEKLY:
					$alldays = 7; break;
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
					$alldays = 90; break;
				case HALFYEARLY:
					$alldays = 182; break;
				case YEARLY:
					$alldays = 365; break;
			}

			$value = str_replace(',', '.', sprintf("%.2f", $alldays != 30 ? $diffdays * $val / $alldays : $value));

			//print "value: $val diffdays: $diffdays alldays: $alldays settl_value: $value" . PHP_EOL;

			$sdesc = $s_comment;
			$sdesc = preg_replace("/\%type/", $assign['tarifftype'] != TARIFF_OTHER ? $TARIFFTYPES[$assign['tarifftype']] : '', $sdesc);
			$sdesc = preg_replace("/\%tariff/", $assign['name'], $sdesc);
			$sdesc = preg_replace("/\%attribute/", $assign['attribute'], $sdesc);
			$sdesc = preg_replace("/\%desc/", $assign['description'], $sdesc);
			$sdesc = preg_replace("/\%period/", $period, $sdesc);
			$sdesc = preg_replace("/\%current_month/", $current_month, $sdesc);
			$sdesc = preg_replace("/\%current_period/", $current_period, $sdesc);
			$sdesc = preg_replace("/\%next_period/", $next_period, $sdesc);
			$sdesc = preg_replace("/\%prev_period/", $prev_period, $sdesc);

			if ($assign['invoice'])
			{
				if (($tmp_itemid = $DB->GetOne("SELECT itemid FROM invoicecontents 
					WHERE tariffid=? AND value=$value AND docid=? AND description=?",
					array($assign['tariffid'], $invoices[$cid], $sdesc))) != 0)
				{
					$DB->Execute("UPDATE invoicecontents SET count=count+1 
						WHERE tariffid=? AND docid=? AND description=?",
						array($assign['tariffid'], $invoices[$cid], $sdesc));

					$DB->Execute("UPDATE cash SET value = value + ($value * -1) 
						WHERE docid = ? AND itemid = $tmp_itemid",
						array($invoices[$cid]));
				}
				else
				{
					$itemid++;

					$DB->Execute("INSERT INTO invoicecontents (docid, value, taxid, prodid, 
						content, count, description, tariffid, itemid, pdiscount, vdiscount) 
						VALUES (?, $value, ?, ?, ?, 1, ?, ?, $itemid, ?, ?)",
						array($invoices[$cid], $assign['taxid'], $assign['prodid'], $unit_name,
						$sdesc, $assign['tariffid'], $assign['pdiscount'], $assign['vdiscount']));
					$DB->Execute("INSERT INTO cash (time, value, taxid, customerid, comment, docid, itemid) 
						VALUES($currtime, $value * -1, ?, $cid, ?, ?, $itemid)",
						array($assign['taxid'], $sdesc, $invoices[$cid]));
				}
			}
			else
				$DB->Execute("INSERT INTO cash (time, value, taxid, customerid, comment) 
					VALUES ($currtime, $value * -1, ?, $cid, ?)", array($assign['taxid'], $sdesc));

			if (!$quiet) print "CID:$cid\tVAL:$value\tDESC:$sdesc" . PHP_EOL;

			// remove settlment flag
			$DB->Execute("UPDATE assignments SET settlement = 0 WHERE id = ?", array($assign['assignmentid']));
		}
	}
}

// solid payments
$assigns = $DB->GetAll("SELECT * FROM payments WHERE value <> 0
			AND (period = ? OR (period = ? AND at = ?)
				OR (period = ? AND at = ?)
				OR (period = ? AND at = ?)
				OR (period = ? AND at = ?)
				OR (period = ? AND at = ?))",
	array(DAILY, WEEKLY, $weekday, MONTHLY, $dom, QUARTERLY, $quarter, HALFYEARLY, $halfyear, YEARLY, $yearday));
if (!empty($assigns))
	foreach($assigns as $assign)
	{
		$DB->Execute("INSERT INTO cash (time, type, value, customerid, comment) 
			VALUES (?, 1, ? * -1, 0, ?)",
			array($currtime, $assign['value'], $assign['name']."/".$assign['creditor']));
		if (!$quiet) print "CID:0\tVAL:".$assign['value']."\tDESC:".$assign['name']."/".$assign['creditor'] . PHP_EOL;
	}

// delete old assignments
$DB->Execute("DELETE FROM liabilities WHERE id IN ( 
	SELECT liabilityid FROM assignments 
	WHERE dateto < ?NOW? - 86400 * 30 AND dateto <> 0 AND at < $today - 86400 * 30 
		AND liabilityid != 0)");
$DB->Execute("DELETE FROM assignments 
	WHERE dateto < ?NOW? - 86400 * 30 AND dateto <> 0 AND at < $today - 86400 * 30");

// clear voip tariff rule states
$DB->Execute("DELETE FROM voip_rule_states");

$DB->Destroy();

?>
