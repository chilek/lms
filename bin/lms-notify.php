#!/usr/bin/php
<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2015 LMS Developers
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

ini_set('error_reporting', E_ALL&~E_NOTICE);

$parameters = array(
	'C:' => 'config-file:',
	'q' => 'quiet',
	'h' => 'help',
	'v' => 'version',
	'd' => 'debug',
	't:' => 'type:',
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
lms-notify.php
(C) 2001-2015 LMS Developers

EOF;
	exit(0);
}

if (array_key_exists('help', $options)) {
	print <<<EOF
lms-notify.php
(C) 2001-2015 LMS Developers

-C, --config-file=/etc/lms/lms.ini      alternate config file (default: /etc/lms/lms.ini);
-h, --help                      print this help and exit;
-v, --version                   print version info and exit;
-q, --quiet                     suppress any output, except errors
-d, --debug                     do debugging, dont send anything.
-t, --type=<notification-types> take only selected notification types into account
                                (separated by colons)

EOF;
	exit(0);
}

$quiet = array_key_exists('quiet', $options);
if (!$quiet) {
	print <<<EOF
lms-notify.php
(C) 2001-2015 LMS Developers

EOF;
}

$debug = array_key_exists('debug', $options);
$types = array();
if (array_key_exists('type', $options))
	$types = explode(',', $options['type']);

if (array_key_exists('config-file', $options))
	$CONFIG_FILE = $options['config-file'];
else
	$CONFIG_FILE = DIRECTORY_SEPARATOR . 'etc' . DIRECTORY_SEPARATOR . 'lms' . DIRECTORY_SEPARATOR . 'lms.ini';

if (!$quiet)
	echo "Using file ".$CONFIG_FILE." as config." . PHP_EOL;

if (!is_readable($CONFIG_FILE))
	die("Unable to read configuration file [".$CONFIG_FILE."]!" . PHP_EOL);

define('CONFIG_FILE', $CONFIG_FILE);

$CONFIG = (array) parse_ini_file($CONFIG_FILE, true);

// Check for configuration vars and set default values
$CONFIG['directories']['sys_dir'] = (!isset($CONFIG['directories']['sys_dir']) ? getcwd() : $CONFIG['directories']['sys_dir']);
$CONFIG['directories']['lib_dir'] = (!isset($CONFIG['directories']['lib_dir']) ? $CONFIG['directories']['sys_dir'] . DIRECTORY_SEPARATOR . 'lib' : $CONFIG['directories']['lib_dir']);

define('SYS_DIR', $CONFIG['directories']['sys_dir']);
define('LIB_DIR', $CONFIG['directories']['lib_dir']);

// Load autoloader
require_once(LIB_DIR . DIRECTORY_SEPARATOR . 'autoloader.php');

// Do some checks and load config defaults
require_once(LIB_DIR . DIRECTORY_SEPARATOR . 'config.php');

// Init database

$DB = null;

try {
	$DB = LMSDB::getInstance();
} catch (Exception $ex) {
	trigger_error($ex->getMessage(), E_USER_WARNING);
	// can't working without database
	die("Fatal error: cannot connect to database!" . PHP_EOL);
}

$host = ConfigHelper::getConfig('notify.smtp_host');
$port = ConfigHelper::getConfig('notify.smtp_port');
$user = ConfigHelper::getConfig('notify.smtp_user');
$pass = ConfigHelper::getConfig('notify.smtp_pass');
$auth = ConfigHelper::getConfig('notify.smtp_auth');

$debug_email = ConfigHelper::getConfig('notify.debug_email', '');
$mail_from = ConfigHelper::getConfig('notify.mailfrom', '');
$mail_fname = ConfigHelper::getConfig('notify.mailfname', '');

// debtors notify
$limit = intval(ConfigHelper::getConfig('notify.limit', 0));
$debtors_message = ConfigHelper::getConfig('notify.debtors_message', '');
$debtors_subject = ConfigHelper::getConfig('notify.debtors_subject', 'Debtors notification');
// new debit note notify
$notes_message = ConfigHelper::getConfig('notify.notes_message', '');
$notes_subject = ConfigHelper::getConfig('notify.notes_subject', 'New debit note notification');
// new invoice notify
$invoices_message = ConfigHelper::getConfig('notify.invoices_message', '');
$invoices_subject = ConfigHelper::getConfig('notify.invoices_subject', 'New invoice notification');
// before deadline notify
$deadline_message = ConfigHelper::getConfig('notify.deadline_message', '');
$deadline_subject = ConfigHelper::getConfig('notify.deadline_subject', 'Invoice deadline notification');
$deadline_days = intval(ConfigHelper::getConfig('notify.deadline_days', 0));

if (empty($mail_from))
	die("Fatal error: mailfrom unset! Can't continue, exiting." . PHP_EOL);

$smtp_auth = ConfigHelper::getConfig('notify.smtp_auth');
if (!empty($smtp_auth) && !preg_match('/^LOGIN|PLAIN|CRAM-MD5|NTLM$/i', ConfigHelper::getConfig('notify.smtp_auth')))
	die("Fatal error: smtp_auth setting not supported! Can't continue, exiting." . PHP_EOL);

// Include required files (including sequence is important)

require_once(LIB_DIR . DIRECTORY_SEPARATOR . 'language.php');
include_once(LIB_DIR . DIRECTORY_SEPARATOR . 'definitions.php');
require_once(LIB_DIR . DIRECTORY_SEPARATOR . 'unstrip.php');
require_once(LIB_DIR . DIRECTORY_SEPARATOR . 'common.php');
require_once(LIB_DIR . DIRECTORY_SEPARATOR . 'SYSLOG.class.php');

if (ConfigHelper::checkConfig('phpui.logging') && class_exists('SYSLOG'))
	$SYSLOG = new SYSLOG($DB);
else
	$SYSLOG = null;

// Initialize Session, Auth and LMS classes

$AUTH = NULL;
$LMS = new LMS($DB, $AUTH, $SYSLOG);
$LMS->ui_lang = $_ui_language;
$LMS->lang = $_language;

if (!empty($mail_fname))
	$mail_from = qp_encode($mail_fname) . ' <' . $mail_from . '>';

function parse_data($data, $row) {
	global $DB;

	$amount = -$row['balance'];
	$data = preg_replace("/\%b/", $amount, $data);
	$data = preg_replace("/\%date-y/", strftime("%Y"), $data);
	$data = preg_replace("/\%date-m/", strftime("%m"), $data);
	$data = preg_replace("/\%date-d/", strftime("%d"), $data);
	$data = preg_replace("/\%date_month_name/", strftime("%B"), $data);
	$deadline = $row['cdate'] + $row['paytime'] * 86400;
	$data = preg_replace("/\%deadline-y/", strftime("%Y", $deadline), $data);
	$data = preg_replace("/\%deadline-m/", strftime("%m", $deadline), $data);
	$data = preg_replace("/\%deadline-d/", strftime("%d", $deadline), $data);
	$data = preg_replace("/\%B/", $row['balance'], $data);
	$data = preg_replace("/\%saldo/", round($row['balance'], 2), $data);
	$data = preg_replace("/\%pin/", $row['pin'], $data);
	$data = preg_replace("/\%cid/", $row['id'], $data);
	if (preg_match("/\%abonament/", $data)) {
		$saldo = $DB->GetOne("SELECT SUM(value)
			FROM assignments, tariffs
			WHERE tariffid = tariffs.id AND customerid = ?
				AND (datefrom <= ?NOW? OR datefrom = 0)
				AND (dateto > ?NOW? OR dateto = 0)
				AND ((datefrom < dateto) OR (datefrom = 0 AND datefrom = 0))",
			array($row['id']));
		$data = preg_replace("/\%abonament/", $saldo, $data);
	}
	// invoices, debit notes
	$data = preg_replace("/\%invoice/", $row['doc_number'], $data);
	$data = preg_replace("/\%number/", $row['doc_number'], $data);
	$data = preg_replace("/\%value/", $row['value'], $data);

	return $data;
}

function send_message($msgid, $cid, $rmail, $rname, $subject, $body, $host = null, $port = null, $user = null, $pass = null, $auth = null) {
	global $LMS, $DB, $mail_from;
	$DB->Execute("INSERT INTO messageitems
		(messageid, customerid, destination, status)
		VALUES (?, ?, ?, ?)",
		array($msgid, $cid, $rmail, 1));

	$headers = array('From' => $mail_from, 'To' => qp_encode($rname) . ' <' . $rmail . '>',
		'Subject' => $subject);
	$result = $LMS->SendMail($rmail, $headers, $body, null, $host, $port, $user, $pass, $auth);

	$query = "UPDATE messageitems
		SET status = ?, lastdate = ?NOW?, error = ?
		WHERE messageid = ? AND customerid = ?";

	if (is_string($result))
		$DB->Execute($query, array(3, $result, $msgid, $cid));
	else // MSG_SENT
		$DB->Execute($query, array($result, null, $msgid, $cid));
}

function create_message($subject, $template) {
	global $DB;

	$DB->Execute("INSERT INTO messages (type, cdate, subject, body)
		VALUES (1, ?NOW?, ?, ?)",
		array($subject, $template));
	return $DB->GetLastInsertID('messages');
}

// ------------------------------------------------------------------------
// ACTIONS
// ------------------------------------------------------------------------
// Debtors
if ($debtors_message && (empty($types) || in_array('debtors', $types))) {
	// @TODO: check 'messages' table and don't send notifies to often
	$customers = $DB->GetAll("SELECT c.id, c.pin, c.lastname, c.name,
			SUM(value) AS balance, c.email
		FROM customers c
		JOIN cash ON (c.id = cash.customerid)
		LEFT JOIN documents d ON d.id = cash.docid
		WHERE c.email <> '' AND (cash.docid = 0 OR (cash.docid <> 0
			AND (d.type = 2 OR (d.type IN (1,3)
				AND d.cdate + d.paytime * 86400 < ?NOW?))))
		GROUP BY c.id, c.pin, c.lastname, c.name, c.email
		HAVING SUM(value) < ?", array($limit));

	if (!empty($customers)) {
		if (!$debug)
			$msgid = create_message($debtors_subject, $debtors_message);

		foreach ($customers as $row) {
			$recipient_name = $row['lastname'] . ' ' . $row['name'];
			$recipient_mail = ($debug_email ? $debug_email : $row['email']);

			if (!$quiet)
				printf("[debt] %s (%04d): %s" . PHP_EOL,
					$recipient_name, $row['id'], $recipient_mail);

			if (!$debug)
				send_message($msgid, $row['id'], $recipient_mail, $recipient_name,
					$debtors_subject, parse_data($debtors_message, $row), $host, $port, $user, $pass, $auth);
		}
	}
}

// Invoices created up to 24 hours ago
if ($invoices_message && (empty($types) || in_array('invoices', $types))) {
	$documents = $DB->GetAll("SELECT d.id AS docid, c.id, c.pin, d.name,
		d.number, n.template, d.cdate, d.paytime, c.email,
		COALESCE(ca.balance, 0) AS balance, v.value
		FROM documents d
		JOIN customers c ON (c.id = d.customerid)
		JOIN (SELECT SUM(value) * -1 AS value, docid
			FROM cash
			GROUP BY docid
		) v ON (v.docid = d.id)
		LEFT JOIN numberplans n ON (d.numberplanid = n.id)
		LEFT JOIN (SELECT SUM(value) AS balance, customerid
			FROM cash
			GROUP BY customerid
		) ca ON (ca.customerid = d.customerid)
		WHERE c.email <> '' AND d.type = 1
			AND d.cdate > ?NOW? - 86400
		");

	if (!empty($documents)) {
		if (!$debug)
			$msgid = create_message($invoices_subject, $invoices_message);

		foreach ($documents as $row) {
			$row['doc_number'] = docnumber($row['number'], ($row['template'] ? $row['template'] : '%N/LMS/%Y'), $row['cdate']);

			$recipient_mail = ($debug_email ? $debug_email : $row['email']);

			if (!$quiet)
				printf("[new invoice] %s (%04d) %s: %s" . PHP_EOL,
					$row['name'], $row['id'], $row['doc_number'], $recipient_mail);

			if (!$debug)
				send_message($msgid, $row['id'], $recipient_mail, $row['name'],
					$invoices_subject, parse_data($invoices_message, $row), $host, $port, $user, $pass, $auth);
		}
	}
}

// Invoices (not payed) up to $deadline_days days after deadline (cdate + paytime)
if ($deadline_message && (empty($types) || in_array('deadline', $types))) {
	$documents = $DB->GetAll("SELECT d.id AS docid, c.id, c.pin, d.name,
		d.number, n.template, d.cdate, d.paytime, c.email,
		COALESCE(ca.balance, 0) AS balance, v.value
		FROM documents d
		JOIN customers c ON (c.id = d.customerid)
		JOIN (
			SELECT SUM(value) * -1 AS value, docid
			FROM cash
			GROUP BY docid
		) v ON (v.docid = d.id)
		LEFT JOIN numberplans n ON (d.numberplanid = n.id)
		LEFT JOIN (
			SELECT SUM(value) AS balance, cash.customerid
			FROM cash
			LEFT JOIN documents ON documents.id = cash.docid
			WHERE cash.docid = 0 OR (cash.docid <> 0 
				AND (documents.type = 2 OR (documents.type IN (1,3)
					AND documents.cdate + documents.paytime * 86400 < ?NOW?)))
			GROUP BY cash.customerid
		) ca ON (ca.customerid = d.customerid)
		WHERE c.email <> '' AND d.type = 1 AND d.closed = 0 AND ca.balance < 0
			AND d.cdate + (d.paytime + 1 + ?) * 86400 > ?NOW?
			AND d.cdate + (d.paytime + ?) * 86400 < ?NOW?",
		array($deadline_days, $deadline_days));

	if (!empty($documents)) {
		if (!$debug)
			$msgid = create_message($deadline_subject, $deadline_message);

		foreach ($documents as $row) {
			$row['doc_number'] = docnumber($row['number'], ($row['template'] ? $row['template'] : '%N/LMS/%Y'), $row['cdate']);

			$recipient_mail = ($debug_email ? $debug_email : $row['email']);

			if (!$quiet)
				printf("[deadline] %s (%04d) %s: %s" . PHP_EOL,
					$row['name'], $row['id'], $row['doc_number'], $recipient_mail);

			if (!$debug)
				send_message($msgid, $row['id'], $recipient_mail, $row['name'],
					$deadline_subject, parse_data($deadline_message, $row), $host, $port, $user, $pass, $auth);
		}
	}
}

// Debit notes created up to 24 hours ago
if ($notes_message && (empty($types) || in_array('notes', $types))) {
	$documents = $DB->GetAll("SELECT d.id AS docid, c.id, c.pin, d.name,
		d.number, n.template, d.cdate, c.email,
		COALESCE(ca.balance, 0) AS balance, v.value
		FROM documents d
		JOIN customers c ON (c.id = d.customerid)
		JOIN (SELECT SUM(value) * -1 AS value, docid
			FROM cash
			GROUP BY docid
		) v ON (v.docid = d.id)
		LEFT JOIN numberplans n ON (d.numberplanid = n.id)
		LEFT JOIN (SELECT SUM(value) AS balance, customerid
			FROM cash
			GROUP BY customerid
		) ca ON (ca.customerid = d.customerid)
		WHERE c.email <> '' AND d.type = 5
			AND d.cdate > ?NOW? - 86400");

	if (!empty($documents)) {
		if (!$debug)
			$msgid = create_message($notes_subject, $notes_message);

		foreach ($documents as $row) {
			$row['doc_number'] = docnumber($row['number'], ($row['template'] ? $row['template'] : '%N/LMS/%Y'), $row['cdate']);

			$recipient_mail = ($debug_email ? $debug_email : $row['email']);

			if (!$quiet)
				printf("[new debit note] %s (%04d) %s: %s" . PHP_EOL,
					$row['name'], $row['id'], $row['doc_number'], $recipient_mail);

			if (!$debug)
				send_message($msgid, $row['id'], $recipient_mail, $row['name'],
					$notes_subject, parse_data($notes_message, $row), $host, $port, $user, $pass, $auth);
		}
	}
}

$DB->Destroy();

?>
