#!/usr/bin/php
<?php

/*
 * LMS version 1.11-cvs
 *
 *  (C) Copyright 2001-2012 LMS Developers
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
 *  $Id: index.php,v 1.219 2010/05/24 07:43:16 chilek Exp $
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
foreach($short_to_longs as $short => $long)
	if (array_key_exists($short, $options))
	{
		$options[$long] = $options[$short];
		unset($options[$short]);
	}

if (array_key_exists('version', $options))
{
	print <<<EOF
lms-payments.php
(C) 2001-2012 LMS Developers

EOF;
	exit(0);
}

if (array_key_exists('help', $options))
{
	print <<<EOF
lms-payments.php
(C) 2001-2012 LMS Developers

-C, --config-file=/etc/lms/lms.ini      alternate config file (default: /etc/lms/lms.ini);
-h, --help                      print this help and exit;
-v, --version                   print version info and exit;
-q, --quiet                     suppress any output, except errors;
-f, --fakedate=YYYY/MM/DD       override system date

EOF;
	exit(0);
}

$quiet = array_key_exists('quiet', $options);
if (!$quiet)
{
	print <<<EOF
lms-payments.php
(C) 2001-2012 LMS Developers

EOF;
}

if (array_key_exists('config-file', $options))
	$CONFIG_FILE = $options['config-file'];
else
	$CONFIG_FILE = '/etc/lms/lms.ini';

if (!$quiet)
	echo "Using file ".$CONFIG_FILE." as config.\n";

if (!is_readable($CONFIG_FILE))
	die('Unable to read configuration file ['.$CONFIG_FILE.']!'); 

$CONFIG = (array) parse_ini_file($CONFIG_FILE, true);

// Check for configuration vars and set default values
$CONFIG['directories']['sys_dir'] = (!isset($CONFIG['directories']['sys_dir']) ? getcwd() : $CONFIG['directories']['sys_dir']);
$CONFIG['directories']['lib_dir'] = (!isset($CONFIG['directories']['lib_dir']) ? $CONFIG['directories']['sys_dir'].'/lib' : $CONFIG['directories']['lib_dir']);

define('SYS_DIR', $CONFIG['directories']['sys_dir']);
define('LIB_DIR', $CONFIG['directories']['lib_dir']);
// Do some checks and load config defaults

require_once(LIB_DIR.'/config.php');

// Init database
 
$_DBTYPE = $CONFIG['database']['type'];
$_DBHOST = $CONFIG['database']['host'];
$_DBUSER = $CONFIG['database']['user'];
$_DBPASS = $CONFIG['database']['password'];
$_DBNAME = $CONFIG['database']['database'];

require(LIB_DIR.'/LMSDB.php');

$DB = DBInit($_DBTYPE, $_DBHOST, $_DBUSER, $_DBPASS, $_DBNAME);

if(!$DB)
{
	// can't working without database
	die("Fatal error: cannot connect to database!\n");
}

// Read configuration from database

if($cfg = $DB->GetAll('SELECT section, var, value FROM uiconfig WHERE disabled=0'))
	foreach($cfg as $row)
		$CONFIG[$row['section']][$row['var']] = $row['value'];

// Include required files (including sequence is important)

//require_once(LIB_DIR.'/definitions.php');
require_once(LIB_DIR.'/common.php');

if (empty($CONFIG['payments']['deadline']))
	$CONFIG['payments']['deadline'] = 14;
if (empty($CONFIG['payments']['paytype']))
	$CONFIG['payments']['paytype'] = 2; // TRANSFER
if (empty($CONFIG['payments']['saledate_next_month']))
	$CONFIG['payments']['saledate_next_month'] = 0;
if (empty($CONFIG['payments']['comment']))
	$CONFIG['payments']['comment'] = "Tariff %tariff subscription for period %period";
if (empty($CONFIG['payments']['settlement_comment']))
	$CONFIG['payments']['settlement_comment'] = $CONFIG['payments']['comment'];
if (empty($CONFIG['payments']['suspension_description']))
	$CONFIG['payments']['suspension_description'] = "";
if (empty($CONFIG['finances']['suspension_percentage']))
	$CONFIG['finances']['suspension_percentage'] = 0;

$deadline = $CONFIG['payments']['deadline'];
$sdate_next = $CONFIG['payments']['saledate_next_month'];
$paytype = $CONFIG['payments']['paytype'];
$comment = $CONFIG['payments']['comment'];
$s_comment = $CONFIG['payments']['settlement_comment'];
$suspension_description = $CONFIG['payments']['suspension_description'];
$suspension_percentage = $CONFIG['finances']['suspension_percentage'];

function localtime2()
{
	global $fakedate;
	if (!empty($fakedate))
	{
		$date = explode("/", $fakedate);
		return mktime(0, 0, 0, $date[1], $date[2], $date[0]);
	}
	else
		return time();
}

define('HALFYEAR', 7);
define('CONTINUOUS', 6);
define('YEAR', 5);
define('QUARTER', 4);
define('MONTH', 3);
define('WEEK', 2);
define('DAY', 1);
define('DISPOSABLE', 0);

// Tariff types
define('TARIFF_INTERNET', 1);
define('TARIFF_HOSTING', 2);
define('TARIFF_SERVICE', 3);
define('TARIFF_PHONE', 4);
define('TARIFF_TV', 5);
define('TARIFF_OTHER', -1);

$TARIFFTYPES = array(
	TARIFF_INTERNET	=> isset($CONFIG['tarifftypes']['internet']) ? $CONFIG['tarifftypes']['internet'] : trans('internet'),
	TARIFF_HOSTING	=> isset($CONFIG['tarifftypes']['hosting']) ? $CONFIG['tarifftypes']['config'] : trans('hosting'),
	TARIFF_SERVICE	=> isset($CONFIG['tarifftypes']['service']) ? $CONFIG['tarifftypes']['service'] : trans('service'),
	TARIFF_PHONE	=> isset($CONFIG['tarifftypes']['phone']) ? $CONFIG['tarifftypes']['phone'] : trans('phone'),
	TARIFF_TV	=> isset($CONFIG['tarifftypes']['tv']) ? $CONFIG['tarifftypes']['tv'] : trans('tv'),
	TARIFF_OTHER	=> isset($CONFIG['tarifftypes']['other']) ? $CONFIG['tarifftypes']['other'] : trans('other'),
);

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
	$today = mktime(0, 0, 0, $dom, $month, $year);

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

$q_month = $month + 2;
$q_year = $year;
$y_month  = $month + 5;
$y_year = $year;
if ($q_month > 12)
{
	$q_month -= 12;
	$q_year += 1;
}
if ($y_month > 12)
{
	$y_month -= 12;
	$y_year += 1;
}

$txts[DAY] = strftime("%Y/%m/%d", mktime(12, 0, 0, $month, $dom, $year));
$txts[WEEK] = strftime("%Y/%m/%d", mktime(12, 0, 0, $month, $dom, $year))." - ".strftime("%Y/%m/%d", mktime(12, 0, 0, $month, $dom + 6, $year));
$txts[MONTH] = strftime("%Y/%m/%d", mktime(12, 0, 0, $month, $dom, $year))." - ".strftime("%Y/%m/%d", mktime(12, 0, 0, $month + 1, $dom - 1, $year));
$txts[QUARTER] = strftime("%Y/%m/%d", mktime(12, 0, 0, $month, $dom, $year))." - ".strftime("%Y/%m/%d", mktime(12, 0, 0, $q_month, $dom - 1, $q_year));
$txts[HALFYEAR] = strftime("%Y/%m/%d", mktime(12, 0, 0, $month, $dom, $year))." - ".strftime("%Y/%m/%d", mktime(12, 0, 0, $y_month, $dom - 1, $y_year));
$txts[YEAR] = strftime("%Y/%m/%d", mktime(12, 0, 0, $month, $dom, $year))." - ".strftime("%Y/%m/%d", mktime(12, 0, 0, $month - 1, $dom - 1, $year + 1));
$txts[DISPOSABLE] = strftime("%Y/%m/%d", mktime(12, 0, 0, $month, $dom, $year));

// Special case, ie. you have 01.01.2005-01.31.2005 on invoice, but invoice/
// assignment is made not January, the 1st:

$current_month = strftime("%Y/%m/%d", mktime(12, 0, 0, $month, 1, $year))." - ".strftime("%Y/%m/%d", mktime(12, 0, 0, $month + 1, 0, $year));
$current_period = strftime("%m/%Y", mktime(12, 0, 0, $month, 1, $year));
$next_period = strftime("%m/%Y", mktime(12, 0, 0, $month + 1, 1, $year));

// sale date setting
$saledate = $currtime;
if ($sdate_next)
	$saledate = strftime("%s", mktime(12, 0, 0, $month + 1, 1, $year));

// calculate start and end of numbering period
function get_period($period)
{
	global $dom, $month, $year;
	if (empty($period))
		$period = YEAR;
	$start = 0;
	$end = 0;

	switch ($period)
	{
		case DAY:
			$start = strftime("%s", mktime(0, 0, 0, $month, $dom, $year));
			$end = strftime("%s", mktime(0, 0, 0, $month, $dom + 1, $year));
			break;
		case WEEK:
			$startweek = $dom - $weekday + 1;
			$start = strftime("%s", mktime(0, 0, 0, $month, $startweek, $year));
			$end = strftime("%s", mktime(0, 0, 0, $month, $startweek + 7, $year));
			break;
		case MONTH:
			$start = strftime("%s", mktime(0, 0, 0, $month, 1, $year));
			$end = strftime("%s", mktime(0, 0, 0, $month + 1, 1, $year));
			break;
		case QUARTER:
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
		case HALFYEAR:
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
		WHERE doctype = 1";
$results = $DB->GetAll($query);
foreach($results as $row)
{
	if ($row['isdefault'])
		$plans[$row['divid']] = $row['id'];
	$periods[$row['id']] = ($row['period'] ? $row['period'] : YEAR);
}

// prepare customergroups in sql query
$customergroups = " AND EXISTS (SELECT 1 FROM customergroups g, customerassignments ca 
	WHERE c.id = ca.customerid 
	AND g.id = ca.customergroupid 
	AND (%groups)) ";
$groupnames = $CONFIG['payments']['customergroups'];
$groupsql = "";
$groups = preg_split("/[[:blank:]]+/", $groupnames, -1, PREG_SPLIT_NO_EMPTY);
foreach ($groups as $group)
{
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
		c.divisionid, c.paytype, a.paytype AS a_paytype, a.numberplanid, 
		d.inv_paytype AS d_paytype, t.period AS t_period, 
		(CASE a.liabilityid WHEN 0 THEN -1 ELSE t.type END) AS tarifftype, 
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
			AND (datefrom <= $currtime OR datefrom = 0) 
			AND (dateto > $currtime OR dateto = 0)) AS allsuspended 
	FROM assignments a 
	JOIN customers c ON (a.customerid = c.id) 
	LEFT JOIN tariffs t ON (a.tariffid = t.id) 
	LEFT JOIN liabilities l ON (a.liabilityid = l.id) 
	LEFT JOIN divisions d ON (d.id = c.divisionid) 
	WHERE c.status = 3 
		AND ((a.period = ".DISPOSABLE." AND at = $today) 
			OR ((a.period = ".DAY." 
			OR (a.period = ".WEEK." AND at = $weekday) 
			OR (a.period = ".MONTH." AND at = $dom) 
			OR (a.period = ".QUARTER." AND at = $quarter) 
			OR (a.period = ".HALFYEAR." AND at = $halfyear) 
			OR (a.period = ".YEAR." AND at = $yearday)) 
			AND (a.datefrom <= $currtime OR a.datefrom = 0) 
			AND (a.dateto > $currtime OR a.dateto = 0)))"
		.(!empty($groupnames) ? $customergroups : "")
	." ORDER BY a.customerid, a.invoice, a.paytype, a.numberplanid, value DESC";
$assigns = $DB->GetAll($query);

if (empty($assigns))
	die;

$suspended = 0;
$invoices = array();
$paytypes = array();
$numberplans = array();

foreach($assigns as $assign)
{
	$cid = $assign['customerid'];
	$divid = ($assign['divisionid'] ? $assign['divisionid'] : 0);

	if ($assign['value'] == 0) continue;

	if (!$assign['suspended'] && $assign['allsuspended'])
		$assign['value'] = $assign['value'] * $suspension_percentage / 100;

	if ($assign['liabilityid'])
		$desc = $assign['name'];
	else
		$desc = $CONFIG['payments']['comment'];
	$desc = preg_replace("/\%type/", $assign['tarifftype'] != TARIFF_OTHER ? $TARIFFTYPES[$assign['tarifftype']] : '', $desc);
	$desc = preg_replace("/\%tariff/", $assign['name'], $desc);
	$desc = preg_replace("/\%desc/", $assign['description'], $desc);
	$desc = preg_replace("/\%period/", $txts[$assign['period']], $desc);
	$desc = preg_replace("/\%current_month/", $current_month, $desc);
	$desc = preg_replace("/\%current_period/", $current_period, $desc);
	$desc = preg_replace("/\%next_period/", $next_period, $desc);

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
			if ($assign['t_period'] == YEAR)
				$val = $val / 12.0;
			elseif ($assign['t_period'] == HALFYEAR)
				$val = $val / 6.0;
			elseif ($assign['t_period'] == QUARTER)
				$val = $val / 3.0;

			if ($assign['period'] == YEAR)
				$val = $val * 12.0;
			elseif ($assign['period'] == HALFYEAR)
				$val = $val * 6.0;
			elseif ($assign['period'] == QUARTER)
				$val = $val * 3.0;
			elseif ($assign['period'] == WEEK)
				$val = $val / 4.0;
			elseif ($assign['period'] == DAY)
				$val = $val / 30.0;
		}

		$val = sprintf("%.2f", $val);

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
				}

				$itemid = 0;
				$numbers[$plan]++;

				$customer = $DB->GetRow("SELECT lastname, name, address, city, zip, ssn, ten, countryid, divisionid, paytime 
						FROM customers WHERE id = $cid");
				$paytime = $customer['paytime'];
				if ($paytime == -1) $paytime = $deadline;

				$DB->Execute("INSERT INTO documents (number, numberplanid, type, countryid, divisionid, 
					customerid, name, address, zip, city, ten, ssn, cdate, sdate, paytime, paytype) 
					VALUES(?, ?, 1, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
					array($numbers[$plan], $plan, $customer['countryid'], $customer['divisionid'], $cid,
					$customer['lastname']." ".$customer['name'], $customer['address'], $customer['zip'],
					$customer['city'], $customer['ten'], $customer['ssn'], $currtime, $saledate, $paytime, $inv_paytype));

				$invoices[$cid] = $DB->GetLastInsertID("documents");
				$paytypes[$cid] = $inv_paytype;
				$numberplans[$cid] = $plan;
			}
			if ($tmp_itemid = $DB->GetOne("SELECT itemid FROM invoicecontents 
				WHERE tariffid=? AND value=$val AND docid=? AND description=? AND pdiscount=? AND vdiscount=?",
				array($assign['tariffid'], $invoices[$cid], $desc, $assign['pdiscount'], $assign['vdiscount'])) != 0)
			{
				$DB->Execute("UPDATE invoicecontents SET count=count+1 
					WHERE tariffid=? AND docid=? AND description=? AND pdiscount=? AND vdiscount=?",
					array($assign['tariffid'], $invoices[$cid], $desc, $assign['pdiscount'], $assign['vdiscount']));
				$DB->Execute("UPDATE cash SET value=value+($val*-1) 
					WHERE docid = ? AND itemid = $tmp_itemid", array($invoices[$cid]));
			}
			else
			{
				$itemid++;

				$DB->Execute("INSERT INTO invoicecontents (docid, value, taxid, prodid, 
					content, count, description, tariffid, itemid, pdiscount, vdiscount) 
					VALUES (?, $val, ?, ?, 'szt.', 1, ?, ?, $itemid, ?, ?)",
					array($invoices[$cid], $assign['taxid'], $assign['prodid'],
					$desc, $assign['tariffid'], $assign['pdiscount'], $assign['vdiscount']));
				$DB->Execute("INSERT INTO cash (time, value, taxid, customerid, comment, docid, itemid) 
					VALUES ($currtime, $val * -1, ?, $cid, ?, ?, $itemid)",
					array($assign['taxid'], $desc, $invoices[$cid]));
			}
		}
		else
			$DB->Execute("INSERT INTO cash (time, value, taxid, customerid, comment) 
				VALUES ($currtime, $val * -1, ?, $cid, ?)", array($assign['taxid'], $desc));

		if (!$quiet) print "CID:$cid\tVAL:$val\tDESC:$desc\n";

		// settlement accounting
		if ($assign['settlement'] && $assign['datefrom'])
		{
			$alldays = 1;
			$diffdays = sprintf("%d", ($today - $assign['datefrom']) / 86400);
			$period = strftime("%Y/%m/%d", mktime(0, 0, 0, $month, $dom - $diffdays, $year))." - "
				.strftime("%Y/%m/%d", mktime(0, 0, 0, $month, $dom - 1, $year));

			switch ($assign['period'])
			{
				// there are no disposable Or daily liabilities with settlement
				case WEEK:
					$alldays = 7; break;
				case MONTH:
					$alldays = 30; break;
				case QUARTER:
					$alldays = 90; break;
				case HALFYEAR:
					$alldays = 182; break;
				case YEAR:
					$alldays = 365; break;
			}

			$value = sprintf("%.2f", $diffdays * $val / $alldays);

			//print "value: $val diffdays: $diffdays alldays: $alldays settl_value: $value\n";

			$sdesc = $s_comment;
			$sdesc = preg_replace("/\%type/", $assign['tarifftype'] != TARIFF_OTHER ? $TARIFFTYPES[$assign['tarifftype']] : '', $sdesc);
			$sdesc = preg_replace("/\%tariff/", $assign['name'], $sdesc);
			$sdesc = preg_replace("/\%desc/", $assign['description'], $sdesc);
			$sdesc = preg_replace("/\%period/", $period, $sdesc);
			$sdesc = preg_replace("/\%current_month/", $current_month, $sdesc);

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
						VALUES (?, $value, ?, ?, 'szt.', 1, ?, ?, $itemid, ?, ?)",
						array($invoices[$cid], $assign['taxid'], $assign['prodid'],
						$sdesc, $assign['tariffid'], $assign['pdiscount'], $assign['vdiscount']));
					$DB->Execute("INSERT INTO cash (time, value, taxid, customerid, comment, docid, itemid) 
						VALUES($currtime, $value * -1, ?, $cid, ?, ?, $itemid)",
						array($assign['taxid'], $sdesc, $invoices[$cid]));
				}
			}
			else
				$DB->Execute("INSERT INTO cash (time, value, taxid, customerid, comment) 
					VALUES ($currtime, $value * -1, ?, $cid, ?)", array($assign['taxid'], $sdesc));

			if (!$quiet) print "CID:$cid\tVAL:$value\tDESC:$sdesc\n";

			// remove settlment flag
			$DB->Execute("UPDATE assignments SET settlement = 0 WHERE id = ?", array($assign['assignmentid']));
		}
	}
}

// solid payments
$assigns = $DB->GetAll("SELECT * FROM payments WHERE value <> 0 
			AND (period = ".DAY." 
				OR (period = ".WEEK." AND at=$weekday) 
				OR (period = ".MONTH." AND at=$dom) 
				OR (period = ".QUARTER." AND at = $quarter) 
				OR (period = ".HALFYEAR." AND at = $halfyear) 
				OR (period = ".YEAR." AND at = $yearday))");
if (!empty($assigns))
	foreach($assigns as $assign)
	{
		$DB->Execute("INSERT INTO cash (time, type, value, customerid, comment) 
			VALUES (?, 1, ? * -1, 0, ?)",
			array($currtime, $assign['value'], $assign['name']."/".$assign['creditor']));
		if (!$quiet) print "CID:0\tVAL:".$assign['value']."\tDESC:".$assign['name']."/".$assign['creditor']."\n";
	}

// delete old assignments
$DB->Execute("DELETE FROM Liabilities WHERE id IN ( 
	SELECT liabilityid FROM assignments 
	WHERE dateto < ?NOW? - 86400 * 30 AND dateto <> 0 AND at < $today - 86400 * 30 
		AND liabilityid != 0)");
$DB->Execute("DELETE FROM assignments 
	WHERE dateto < ?NOW? - 86400 * 30 AND dateto <> 0 AND at < $today - 86400 * 30");

$DB->Destroy();

?>
