#!/usr/bin/php
<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2013 LMS Developers
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
 *  $Id: lms-sendinvoices.php,v 1.1 2012/03/03 15:27:16 chilek Exp $
 */

ini_set('error_reporting', E_ALL&~E_NOTICE);

$parameters = array(
	'C:' => 'config-file:',
	'q' => 'quiet',
	'h' => 'help',
	'v' => 'version',
	't' => 'test',
	'f:' => 'fakedate:',
	'i:' => 'invoiceid:',
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
lms-sendinvoices.php
(C) 2001-2013 LMS Developers

EOF;
	exit(0);
}

if (array_key_exists('help', $options))
{
	print <<<EOF
lms-sendinvoices.php
(C) 2001-2013 LMS Developers

-C, --config-file=/etc/lms/lms.ini      alternate config file (default: /etc/lms/lms.ini);
-h, --help                      print this help and exit;
-t, --test                      print only invoices to send;
-v, --version                   print version info and exit;
-q, --quiet                     suppress any output, except errors;
-f, --fakedate=YYYY/MM/DD       override system date;
-i, --invoiceid=N               send only selected invoice

EOF;
	exit(0);
}

$quiet = array_key_exists('quiet', $options);
if (!$quiet)
{
	print <<<EOF
lms-sendinvoices.php
(C) 2001-2013 LMS Developers

EOF;
}

if (array_key_exists('config-file', $options))
	$CONFIG_FILE = $options['config-file'];
else
	$CONFIG_FILE = '/etc/lms/lms.ini';

if (!$quiet) {
	echo "Using file ".$CONFIG_FILE." as config.\n";
}

if (!is_readable($CONFIG_FILE))
	die("Unable to read configuration file [".$CONFIG_FILE."]!\n");

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

require_once(LIB_DIR.'/language.php');
include_once(LIB_DIR.'/definitions.php');
require_once(LIB_DIR.'/unstrip.php');
require_once(LIB_DIR.'/common.php');
require_once(LIB_DIR.'/LMS.class.php');

$lms_url = (!empty($CONFIG['sendinvoices']['lms_url']) ? $CONFIG['sendinvoices']['lms_url'] : 'http://localhost/lms/');
$lms_user = (!empty($CONFIG['sendinvoices']['lms_user']) ? $CONFIG['sendinvoices']['lms_user'] : '');
$lms_password = (!empty($CONFIG['sendinvoices']['lms_password']) ? $CONFIG['sendinvoices']['lms_password'] : '');

if (!empty($CONFIG['sendinvoices']['smtp_host']))
	$CONFIG['mail']['smtp_host'] = $CONFIG['sendinvoices']['smtp_host'];
if (!empty($CONFIG['sendinvoices']['smtp_port']))
	$CONFIG['mail']['smtp_port'] = $CONFIG['sendinvoices']['smtp_port'];
if (!empty($CONFIG['sendinvoices']['smtp_user']))
	$CONFIG['mail']['smtp_username'] = $CONFIG['sendinvoices']['smtp_user'];
if (!empty($CONFIG['sendinvoices']['smtp_pass']))
	$CONFIG['mail']['smtp_password'] = $CONFIG['sendinvoices']['smtp_pass'];
if (!empty($CONFIG['sendinvoices']['smtp_auth']))
	$CONFIG['mail']['smtp_auth_type'] = $CONFIG['sendinvoices']['smtp_auth'];

$filetype = (!empty($CONFIG['invoices']['type']) ? $CONFIG['invoices']['type'] : '');
$debug_email = (!empty($CONFIG['sendinvoices']['debug_email']) ? $CONFIG['sendinvoices']['debug_email'] : '');
$sender_name = (!empty($CONFIG['sendinvoices']['sender_name']) ? $CONFIG['sendinvoices']['sender_name'] : '');
$sender_email = (!empty($CONFIG['sendinvoices']['sender_email']) ? $CONFIG['sendinvoices']['sender_email'] : '');
$mail_subject = (!empty($CONFIG['sendinvoices']['mail_subject']) ? $CONFIG['sendinvoices']['mail_subject'] : 'Invoice No. %invoice');
$mail_body = (!empty($CONFIG['sendinvoices']['mail_body']) ? $CONFIG['sendinvoices']['mail_body'] : $CONFIG['mail']['sendinvoice_mail_body']);
$invoice_filename = (!empty($CONFIG['sendinvoices']['invoice_filename']) ? $CONFIG['sendinvoices']['invoice_filename'] : 'invoice_%docid');
$notify_email = (!empty($CONFIG['sendinvoices']['notify_email']) ? $CONFIG['sendinvoices']['notify_email'] : '');

if (empty($sender_email))
	die("Fatal error: sender_email unset! Con't continue, exiting.\n");

if (!empty($CONFIG['mail']['smtp_auth_type']) && !preg_match('/^LOGIN|PLAIN|CRAM-MD5|NTLM$/i', $CONFIG['mail']['smtp_auth_type']))
	die("Fatal error: smtp_auth setting not supported! Can't continue, exiting.\n");

$fakedate = (array_key_exists('fakedate', $options) ? $options['fakedate'] : NULL);
$invoiceid = (array_key_exists('invoiceid', $options) ? $options['invoiceid'] : NULL);

function localtime2()
{
	global $fakedate;
	if (!empty($fakedate)) {
		$date = explode("/", $fakedate);
		return mktime(0, 0, 0, intval($date[1]), intval($date[2]), intval($date[0]));
	} else
		return time();
}

$ftype = 'text/html';
$fext = 'html';

if ($filetype == 'pdf') {
	$ftype = 'application/pdf';
	$fext = 'pdf';
}

$timeoffset = date('Z');
$currtime = localtime2() + $timeoffset;
$month = intval(date('m', $currtime));
$day = intval(date('d', $currtime));
$year = intval(date('Y', $currtime));
$daystart = (intval($currtime / 86400) * 86400) - $timeoffset;
$dayend = $daystart + 86399;
$from = $sender_email;

if (!empty($sender_name))
	$from = "$sender_name <$from>";

// prepare customergroups in sql query
$customergroups = " AND EXISTS (SELECT 1 FROM customergroups g, customerassignments ca 
	WHERE c.id = ca.customerid 
	AND g.id = ca.customergroupid 
	AND (%groups)) ";
$groupnames = $CONFIG['sendinvoices']['customergroups'];
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

// Initialize Session, Auth and LMS classes

$AUTH = NULL;
$LMS = new LMS($DB, $AUTH, $CONFIG);
$LMS->ui_lang = $_ui_language;
$LMS->lang = $_language;

$query = "SELECT d.id, d.number, d.cdate, c.email, d.name, d.customerid, n.template 
		FROM documents d 
		LEFT JOIN customers c ON c.id = d.customerid 
		LEFT JOIN numberplans n ON n.id = d.numberplanid 
		WHERE c.deleted = 0 AND d.type = 1 AND c.email <> '' AND c.invoicenotice = 1 "
			. (!empty($invoiceid) ? "AND d.id = " . $invoiceid : "AND d.cdate >= $daystart AND d.cdate <= $dayend")
			. (!empty($groupnames) ? $customergroups : "")
		. " ORDER BY d.number";
$docs = $DB->GetAll($query);

define('USER_AGENT', "Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0)");
define('COOKIE_FILE', tempnam('/tmp', 'lms-sendinvoices-cookies-'));

$ch = curl_init();
if (!$ch)
	die("Fatal error: Can't init curl library!\n");

if (!empty($docs))
{
	if (array_key_exists('test', $options))
	{
		$test = TRUE;
		printf("WARNING! You are using test mode.\n");
	}
	foreach ($docs as $doc) {
		curl_setopt_array($ch, array(
			CURLOPT_URL => $lms_url . '/?m=invoice&override=1&original=1&id=' . $doc['id']
				. '&loginform[login]=' . $lms_user . '&loginform[pwd]=' . $lms_password,
			CURLOPT_HTTPGET => TRUE,
			CURLOPT_POST => FALSE,
			CURLOPT_RETURNTRANSFER => TRUE,
			CURLOPT_COOKIEJAR => COOKIE_FILE,
			CURLOPT_COOKIEFILE => COOKIE_FILE,
			CURLOPT_SSLVERSION => 3,
			CURLOPT_SSL_VERIFYHOST => 2,
			CURLOPT_SSL_VERIFYPEER => FALSE,
			CURLOPT_USERAGENT => USER_AGENT
		));
		$res = curl_exec($ch);
		if (!empty($res)) {
			$custemail = (!empty($debug_email) ? $debug_email : $doc['email']);
			$invoice_number = (!empty($doc['template']) ? $doc['template'] : '%N/LMS/%Y');
			$body = $mail_body;
			$subject = $mail_subject;

			$invoice_number = docnumber($doc['number'], $invoice_number, $doc['cdate'] + date('Z'));
			$body = preg_replace('/%invoice/', $invoice_number, $body);
			$body = preg_replace('/%balance/', $LMS->GetCustomerBalance($doc['customerid']), $body);
			$day = sprintf("%02d",$day);
			$month = sprintf("%02d",$month);
			$year = sprintf("%04d",$year);
			$body = preg_replace('/%today/', $year ."-". $month ."-". $day, $body);
			$body = str_replace('\n', "\n", $body);
			$subject = preg_replace('/%invoice/', $invoice_number, $subject);
			$filename = preg_replace('/%docid/', $doc['id'], $invoice_filename);

			if (!$quiet || $test)
				printf("Invoice No. $invoice_number for " . $doc['name'] . " <$custemail>\n");

			if (!$test)
			{
				$res = $LMS->SendMail($custemail . ',' . $notify_email,
					array('From' => $from, 'To' => $doc['name'] . ' <' . $custemail . '>',
						'Cc' => $notify_email,
						'Subject' => $subject), $body,
					array(0 => array('content_type' => $ftype, 'filename' => $filename . '.' . $fext,
						'data' => $res)));

				if (is_string($res))
					fprintf(STDERR, "Error sending mail: $res\n");
			}
		}
	}
}
curl_close($ch);

unlink(COOKIE_FILE);

?>
