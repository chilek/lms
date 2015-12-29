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
	'f:' => 'fakedate:',
	't:' => 'type:',
	's:' => 'section:',
	'c:' => 'channel:',
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
-f, --fakedate=YYYY/MM/DD       override system date;
-t, --type=<notification-types> take only selected notification types into account
                                (separated by colons)
-c, --channel=<channel-types>  use selected channels for notifications
                                (separated by colons)
-s, --section=<section-name>    section name from lms configuration where settings
                                are stored

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
$fakedate = (array_key_exists('fakedate', $options) ? $options['fakedate'] : null);

$types = array();
if (array_key_exists('type', $options))
	$types = explode(',', $options['type']);

$channels = array();
if (array_key_exists('channel', $options))
	$channels = explode(',', $options['channel']);
if (empty($channels))
	$channels[] = 'mail';

$config_section = (array_key_exists('section', $options) && preg_match('/^[a-z0-9-_]+$/i', $options['section']) ? $options['section'] : 'notify');

$timeoffset = date('Z');

function localtime2() {
	global $fakedate, $timeoffset;
	if (!empty($fakedate)) {
		$date = explode("/", $fakedate);
		return mktime(0, 0, 0, intval($date[1]), intval($date[2]), intval($date[0])) + $timeoffset;
	} else
		return time();
}

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

$host = ConfigHelper::getConfig($config_section . '.smtp_host');
$port = ConfigHelper::getConfig($config_section . '.smtp_port');
$user = ConfigHelper::getConfig($config_section . '.smtp_user');
$pass = ConfigHelper::getConfig($config_section . '.smtp_pass');
$auth = ConfigHelper::getConfig($config_section . '.smtp_auth');

$debug_email = ConfigHelper::getConfig($config_section . '.debug_email', '');
$mail_from = ConfigHelper::getConfig($config_section . '.mailfrom', '');
$mail_fname = ConfigHelper::getConfig($config_section . '.mailfname', '');
$notify_email = ConfigHelper::getConfig($config_section . '.notify_email', '');

$debug_phone = ConfigHelper::getConfig($config_section . '.debug_phone', '');
$script_service = ConfigHelper::getConfig($config_section . '.service', '');
if ($script_service)
	LMSConfig::getConfig()->getSection('sms')->addVariable(new ConfigVariable('service', $script_service));

// contracts - contracts being finished some day before notify
// debtors - debtors notify
// reminder - reminder notify
// invoices - new invoice notify
// notes - new debit note notify
// warnings - send message to customers with warning flag set for node
// messages - send message to customers which have awaiting www messages
$notifications = array();
foreach (array('contracts', 'debtors', 'reminder', 'invoices', 'notes', 'warnings', 'messages') as $type) {
	$notifications[$type] = array();
	$notifications[$type]['limit'] = intval(ConfigHelper::getConfig($config_section . '.' . $type . '_limit', 0));
	$notifications[$type]['message'] = ConfigHelper::getConfig($config_section . '.' . $type . '_message', $type . ' notification');
	$notifications[$type]['subject'] = ConfigHelper::getConfig($config_section . '.' . $type . '_subject', $type . ' notification');
	$notifications[$type]['days'] = intval(ConfigHelper::getConfig($config_section . '.' . $type . '_days', 0));
	$notifications[$type]['file'] = ConfigHelper::getConfig($config_section . '.' . $type . '_file', '/etc/rc.d/' . $type . '.sh');
	$notifications[$type]['header'] = ConfigHelper::getConfig($config_section . '.' . $type . '_header', "#!/bin/bash\n\nipset flush $type\n");
	$notifications[$type]['rule'] = ConfigHelper::getConfig($config_section . '.' . $type . '_rule', "ipset add $type %i\n");
	$notifications[$type]['footer'] = ConfigHelper::getConfig($config_section . '.' . $type . '_footer', '');
}

if (in_array('mail', $channels) && empty($mail_from))
	die("Fatal error: mailfrom unset! Can't continue, exiting." . PHP_EOL);

if (!empty($auth) && !preg_match('/^LOGIN|PLAIN|CRAM-MD5|NTLM$/i', $auth))
	die("Fatal error: smtp_auth setting not supported! Can't continue, exiting." . PHP_EOL);

//$currtime = localtime2() + $timeoffset;
$currtime = localtime2();
//$daystart = intval($currtime / 86400) * 86400 - $timeoffset;
$daystart = intval($currtime / 86400) * 86400;
$dayend = $daystart + 86399;

$deadline = ConfigHelper::getConfig('payments.deadline', ConfigHelper::getConfig('invoices.paytime', 0));

// Include required files (including sequence is important)

require_once(LIB_DIR . DIRECTORY_SEPARATOR . 'language.php');
include_once(LIB_DIR . DIRECTORY_SEPARATOR . 'definitions.php');
require_once(LIB_DIR . DIRECTORY_SEPARATOR . 'unstrip.php');
require_once(LIB_DIR . DIRECTORY_SEPARATOR . 'common.php');

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

//include(LIB_DIR . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . 'mtsms.php');

function parse_customer_data($data, $row) {
	global $DB;

	$amount = -$row['balance'];
	$data = preg_replace("/\%bankaccount/",
		format_bankaccount(bankaccount($row['id'], $row['account'])), $data);
	$data = preg_replace("/\%b/", $amount, $data);
	$data = preg_replace("/\%date-y/", strftime("%Y"), $data);
	$data = preg_replace("/\%date-m/", strftime("%m"), $data);
	$data = preg_replace("/\%date_month_name/", strftime("%B"), $data);
	$deadline = $row['cdate'] + $row['paytime'] * 86400;
	$data = preg_replace("/\%deadline-y/", strftime("%Y", $deadline), $data);
	$data = preg_replace("/\%deadline-m/", strftime("%m", $deadline), $data);
	$data = preg_replace("/\%deadline-d/", strftime("%d", $deadline), $data);
	$data = preg_replace("/\%B/", $row['balance'], $data);
	$data = preg_replace("/\%saldo/", moneyf($row['balance']), $data);
	$data = preg_replace("/\%pin/", $row['pin'], $data);
	$data = preg_replace("/\%cid/", $row['id'], $data);
	if (preg_match("/\%abonament/", $data)) {
		$saldo = $DB->GetOne("SELECT SUM(value)
			FROM assignments, tariffs
			WHERE tariffid = tariffs.id AND customerid = ?
				AND (datefrom <= $currtime OR datefrom = 0)
				AND (dateto > $currtime OR dateto = 0)
				AND ((datefrom < dateto) OR (datefrom = 0 AND datefrom = 0))",
			array($row['id']));
		$data = preg_replace("/\%abonament/", $saldo, $data);
	}
	// invoices, debit notes
	$data = preg_replace("/\%invoice/", $row['doc_number'], $data);
	$data = preg_replace("/\%number/", $row['doc_number'], $data);
	$data = preg_replace("/\%value/", $row['value'], $data);
	$data = preg_replace("/\%cdate-y/", strftime("%Y", $row['cdate']), $data);
	$data = preg_replace("/\%cdate-m/", strftime("%m", $row['cdate']), $data);
	$data = preg_replace("/\%cdate-d/", strftime("%d", $row['cdate']), $data);

	list ($now_y, $now_m) = explode('/', strftime("%Y/%m", time()));
	$data = preg_replace("/\%lastday/", strftime("%d", mktime(12, 0, 0, $now_m + 1, 0, $now_y)), $data);

	return $data;
}

function parse_node_data($data, $row) {
	$data = preg_replace("/\%i/", $row['ip'], $data);
	//$data = preg_replace("/\%nas/", $row['nasip'], $data);

	return $data;
}

function create_message($type, $subject, $template) {
	global $DB;

	$DB->Execute("INSERT INTO messages (type, cdate, subject, body)
		VALUES (?, ?NOW?, ?, ?)",
		array($type, $subject, $template));
	return $DB->GetLastInsertID('messages');
}

function send_mail($msgid, $cid, $rmail, $rname, $subject, $body) {
	global $LMS, $DB, $mail_from, $notify_email;
	$DB->Execute("INSERT INTO messageitems
		(messageid, customerid, destination, status)
		VALUES (?, ?, ?, ?)",
		array($msgid, $cid, $rmail, 1));
	$msgitemid = $DB->GetLastInsertID('messageitems');

	$headers = array('From' => $mail_from, 'To' => qp_encode($rname) . " <$rmail>",
		'Subject' => $subject);
	if (!empty($notify_email))
		$headers['Cc'] = $notify_email;
	$result = $LMS->SendMail($rmail, $headers, $body);

	$query = "UPDATE messageitems
		SET status = ?, lastdate = ?NOW?, error = ?
		WHERE messageid = ? AND customerid = ? AND id = ?";

	if (is_string($result))
		$DB->Execute($query, array(3, $result, $msgid, $cid, $msgitemid));
	else // MSG_SENT
		$DB->Execute($query, array($result, null, $msgid, $cid, $msgitemid));
}

function send_sms($msgid, $cid, $phone, $data) {
	global $LMS, $DB;
	$DB->Execute("INSERT INTO messageitems
		(messageid, customerid, destination, status)
		VALUES (?, ?, ?, ?)",
		array($msgid, $cid, $phone, 1));
	$msgitemid = $DB->GetLastInsertID('messageitems');

	$result = $LMS->SendSMS(str_replace(' ', '', $phone), $data, $msgid);
	$query = "UPDATE messageitems
		SET status = ?, lastdate = ?NOW?, error = ?
		WHERE messageid = ? AND customerid = ? AND id = ?";

	if (preg_match("/[^0-9]/", $result))
		$DB->Execute($query, array(3, $result, $msgid, $cid, $msgitemid));
	elseif ($result == 2) // MSG_SENT
		$DB->Execute($query, array($result, null, $msgid, $cid, $msgitemid));
}

// ------------------------------------------------------------------------
// ACTIONS
// ------------------------------------------------------------------------

// contracts
if (empty($types) || in_array('contracts', $types)) {
	$days = $notifications['contracts']['days'];
	$customers = $DB->GetAll("SELECT c.id, c.pin, c.lastname, c.name,
			SUM(value) AS balance, MAX(a.dateto) AS cdate,
			m.email, x.phone
		FROM customers c
		JOIN cash ON (c.id = cash.customerid)
		JOIN assignments a ON (c.id = a.customerid)
		LEFT JOIN (SELECT " . $DB->GroupConcat('contact') . " AS email, customerid
			FROM customercontacts
			WHERE (type & ?) = ?
			GROUP BY customerid
		) m ON (m.customerid = c.id)
		LEFT JOIN (SELECT " . $DB->GroupConcat('contact') . " AS phone, customerid
			FROM customercontacts
			WHERE (type & ?) = ?
			GROUP BY customerid
		) x ON (x.customerid = c.id)
		GROUP BY c.id, c.pin, c.lastname, c.name, m.email, x.phone
		HAVING MAX(a.dateto) >= $daystart + ? * 86400 AND MAX(a.dateto) < $daystart + (? + 1) * 86400",
		array(CONTACT_EMAIL | CONTACT_NOTIFICATIONS | CONTACT_DISABLED,
			CONTACT_EMAIL | CONTACT_NOTIFICATIONS,
			CONTACT_MOBILE | CONTACT_NOTIFICATIONS | CONTACT_DISABLED,
			CONTACT_MOBILE | CONTACT_NOTIFICATIONS,
			$days, $days));

	if (!empty($customers)) {
		$notifications['contracts']['customers'] = array();
		foreach ($customers as $row) {
			$notifications['contracts']['customers'][] = $row['id'];
			$message = parse_customer_data($notifications['contracts']['message'], $row);
			$subject = parse_customer_data($notifications['contracts']['subject'], $row);

			$recipient_name = $row['lastname'] . ' ' . $row['name'];
			$recipient_mails = ($debug_email ? explode(',', $debug_email) :
				(!empty($row['email']) ? explode(',', trim($row['email'])) : null));
			$recipient_phones = ($debug_phone ? explode(',', $debug_phone) :
				(!empty($row['phone']) ? explode(',', trim($row['phone'])) : null));

			if (!$quiet) {
				if (in_array('mail', $channels) && !empty($recipient_mails))
					foreach ($recipient_mails as $recipient_mail)
						printf("[mail/contracts] %s (%04d): %s" . PHP_EOL,
							$recipient_name, $row['id'], $recipient_mail);
				if (in_array('sms', $channels) && !empty($recipient_phones))
					foreach ($recipient_phones as $phone)
						printf("[sms/contracts] %s (%04d): %s" . PHP_EOL,
							$recipient_name, $row['id'], $phone);
			}

			if (!$debug) {
				if (in_array('mail', $channels) && !empty($recipient_mails)) {
					$msgid = create_message(MSG_MAIL, $subject, $message);
					foreach ($recipient_mails as $recipient_mail)
						send_mail($msgid, $row['id'], $recipient_mail, $recipient_name,
							$subject, $message);
				}
				if (in_array('sms', $channels) && !empty($recipient_phones)) {
					$msgid = create_message(MSG_SMS, $subject, $message);
					foreach ($recipient_phones as $phone)
						send_sms($msgid, $row['id'], $phone, $message);
				}
			}
		}
	}
}

// Debtors
if (empty($types) || in_array('debtors', $types)) {
	$days = $notifications['debtors']['days'];
	$limit = $notifications['debtors']['limit'];
	// @TODO: check 'messages' table and don't send notifies to often
	$customers = $DB->GetAll("SELECT c.id, c.pin, c.lastname, c.name,
			SUM(value) AS balance, m.email, x.phone, divisions.account
		FROM customers c
		LEFT JOIN divisions ON divisions.id = c.divisionid
		JOIN cash ON (c.id = cash.customerid)
		LEFT JOIN (SELECT " . $DB->GroupConcat('contact') . " AS email, customerid
			FROM customercontacts
			WHERE (type & ?) = ?
			GROUP BY customerid
		) m ON (m.customerid = c.id)
		LEFT JOIN (SELECT " . $DB->GroupConcat('contact') . " AS phone, customerid
			FROM customercontacts
			WHERE (type & ?) = ?
			GROUP BY customerid
		) x ON (x.customerid = c.id)
		LEFT JOIN documents d ON d.id = cash.docid
		WHERE c.cutoffstop < $currtime AND ((cash.docid = 0 AND ((cash.type <> 0 AND cash.time < $currtime)
			OR (cash.type = 0 AND cash.time + ((CASE c.paytime WHEN -1 THEN
				(CASE WHEN divisions.inv_paytime IS NULL THEN $deadline ELSE divisions.inv_paytime END) ELSE c.paytime END) + ?) * 86400 < $currtime)))
			OR (cash.docid <> 0 AND ((d.type IN (?, ?) AND cash.time < $currtime
				OR (d.type IN (?, ?) AND d.cdate + (d.paytime + ?) * 86400 < $currtime)))))
		GROUP BY c.id, c.pin, c.lastname, c.name, m.email, x.phone, divisions.account
		HAVING SUM(value) < ?", array(
			CONTACT_EMAIL | CONTACT_NOTIFICATIONS | CONTACT_DISABLED,
			CONTACT_EMAIL | CONTACT_NOTIFICATIONS,
			CONTACT_MOBILE | CONTACT_NOTIFICATIONS | CONTACT_DISABLED,
			CONTACT_MOBILE | CONTACT_NOTIFICATIONS,
			$days, DOC_RECEIPT, DOC_CNOTE, DOC_INVOICE, DOC_DNOTE, $days, $limit));

	if (!empty($customers)) {
		$notifications['debtors']['customers'] = array();
		foreach ($customers as $row) {
			$notifications['debtors']['customers'][] = $row['id'];
			$message = parse_customer_data($notifications['debtors']['message'], $row);
			$subject = parse_customer_data($notifications['debtors']['subject'], $row);

			$recipient_name = $row['lastname'] . ' ' . $row['name'];
			$recipient_mails = ($debug_email ? explode(',', $debug_email) :
				(!empty($row['email']) ? explode(',', trim($row['email'])) : null));
			$recipient_phones = ($debug_phone ? explode(',', $debug_phone) :
				(!empty($row['phone']) ? explode(',', trim($row['phone'])) : null));

			if (!$quiet) {
				if (in_array('mail', $channels) && !empty($recipient_mails))
					foreach ($recipient_mails as $recipient_mail)
						printf("[mail/debtors] %s (%04d): %s" . PHP_EOL,
							$recipient_name, $row['id'], $recipient_mail);
				if (in_array('sms', $channels) && !empty($recipient_phones))
					foreach ($recipient_phones as $phone)
						printf("[sms/debtors] %s (%04d): %s" . PHP_EOL,
							$recipient_name, $row['id'], $phone);
			}

			if (!$debug) {
				if (in_array('mail', $channels) && !empty($recipient_mails)) {
					$msgid = create_message(MSG_MAIL, $subject, $message);
					foreach ($recipient_mails as $recipient_mail)
						send_mail($msgid, $row['id'], $recipient_mail, $recipient_name,
							$subject, $message);
				}
				if (in_array('sms', $channels) && !empty($recipient_phones)) {
					$msgid = create_message(MSG_SMS, $subject, $message);
					foreach ($recipient_phones as $phone)
						send_sms($msgid, $row['id'], $phone, $message);
				}
			}
		}
	}
}

// Invoices (not payed) up to $reminder_days days before deadline (cdate + paytime)
if (empty($types) || in_array('reminder', $types)) {
	$days = $notifications['reminder']['days'];
	$documents = $DB->GetAll("SELECT d.id AS docid, c.id, c.pin, d.name,
		d.number, n.template, d.cdate, d.paytime, m.email, x.phone, divisions.account,
		COALESCE(ca.balance, 0) AS balance, v.value
		FROM documents d
		JOIN customers c ON (c.id = d.customerid)
		LEFT JOIN divisions ON divisions.id = c.divisionid
		LEFT JOIN (SELECT " . $DB->GroupConcat('contact') . " AS email, customerid
			FROM customercontacts
			WHERE (type & ?) = ?
			GROUP BY customerid
		) m ON (m.customerid = c.id)
		LEFT JOIN (SELECT " . $DB->GroupConcat('contact') . " AS phone, customerid
			FROM customercontacts
			WHERE (type & ?) = ?
			GROUP BY customerid
		) x ON (x.customerid = c.id)
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
			JOIN customers c ON c.id = cash.customerid
			LEFT JOIN divisions ON divisions.id = c.divisionid
			WHERE (cash.docid = 0 AND ((cash.type <> 0 AND cash.time < $currtime)
				OR (cash.type = 0 AND cash.time + ((CASE c.paytime WHEN -1 THEN
				(CASE WHEN divisions.inv_paytime IS NULL THEN $deadline ELSE divisions.inv_paytime END) ELSE c.paytime END) + ?) * 86400 < $currtime)))
				OR (cash.docid <> 0 AND ((documents.type IN (?, ?) AND cash.time < $currtime)
					OR (documents.type IN (?, ?) AND ((documents.cdate / 86400) + documents.paytime - ?) * 86400 < $currtime)))
			GROUP BY cash.customerid
		) ca ON (ca.customerid = d.customerid)
		WHERE d.type = 1 AND d.closed = 0 AND ca.balance < 0
			AND ((d.cdate / 86400) + d.paytime + 1 - ?) * 86400 >= $daystart
			AND ((d.cdate / 86400) + d.paytime - ?) * 86400 < $daystart",
		array(
			CONTACT_EMAIL | CONTACT_INVOICES | CONTACT_NOTIFICATIONS | CONTACT_DISABLED,
			CONTACT_EMAIL | CONTACT_INVOICES | CONTACT_NOTIFICATIONS,
			CONTACT_MOBILE | CONTACT_NOTIFICATIONS | CONTACT_DISABLED,
			CONTACT_MOBILE | CONTACT_NOTIFICATIONS,
			$days, DOC_RECEIPT, DOC_CNOTE, DOC_INVOICE, DOC_DNOTE, $days, $days, $days));
	if (!empty($documents)) {
		$notifications['reminder']['customers'] = array();
		foreach ($documents as $row) {
			$notifications['reminder']['customers'][] = $row['id'];
			$row['doc_number'] = docnumber($row['number'], ($row['template'] ? $row['template'] : '%N/LMS/%Y'), $row['cdate']);

			$message = parse_customer_data($notifications['reminder']['message'], $row);
			$subject = parse_customer_data($notifications['reminder']['subject'], $row);

			$recipient_mails = ($debug_email ? explode(',', $debug_email) :
				(!empty($row['email']) ? explode(',', trim($row['email'])) : null));
			$recipient_phones = ($debug_phone ? explode(',', $debug_phone) :
				(!empty($row['phone']) ? explode(',', trim($row['phone'])) : null));

			if (!$quiet) {
				if (in_array('mail', $channels) && !empty($recipient_mails))
					foreach ($recipient_mails as $recipient_mail)
						printf("[mail/reminder] %s (%04d) %s: %s" . PHP_EOL,
							$row['name'], $row['id'], $row['doc_number'], $recipient_mail);
				if (in_array('sms', $channels) && !empty($recipient_phones))
					foreach ($recipient_phones as $phone)
						printf("[sms/reminder] %s (%04d) %s: %s" . PHP_EOL,
							$row['name'], $row['id'], $row['doc_number'], $phone);
			}

			if (!$debug) {
				if (in_array('mail', $channels) && !empty($recipient_mails)) {
					$msgid = create_message(MSG_MAIL, $subject, $message);
					foreach ($recipient_mails as $recipient_mail)
						send_mail($msgid, $row['id'], $recipient_mail, $row['name'],
							$subject, $message);
				}
				if (in_array('sms', $channels) && !empty($recipient_phones)) {
					$msgid = create_message(MSG_SMS, $subject, $message);
					foreach ($recipient_phones as $phone)
						send_sms($msgid, $row['id'], $phone, $message);
				}
			}
		}
	}
}

// Invoices created at current day
if (empty($types) || in_array('invoices', $types)) {
	$documents = $DB->GetAll("SELECT d.id AS docid, c.id, c.pin, d.name,
		d.number, n.template, d.cdate, d.paytime, m.email, x.phone, divisions.account,
		COALESCE(ca.balance, 0) AS balance, v.value
		FROM documents d
		JOIN customers c ON (c.id = d.customerid)
		LEFT JOIN divisions ON divisions.id = c.divisionid
		LEFT JOIN (SELECT " . $DB->GroupConcat('contact') . " AS email, customerid
			FROM customercontacts
			WHERE (type & ?) = ?
			GROUP BY customerid
		) m ON (m.customerid = c.id)
		LEFT JOIN (SELECT " . $DB->GroupConcat('contact') . " AS phone, customerid
			FROM customercontacts
			WHERE (type & ?) = ?
			GROUP BY customerid
		) x ON (x.customerid = c.id)
		JOIN (SELECT SUM(value) * -1 AS value, docid
			FROM cash
			GROUP BY docid
		) v ON (v.docid = d.id)
		LEFT JOIN numberplans n ON (d.numberplanid = n.id)
		LEFT JOIN (SELECT SUM(value) AS balance, customerid
			FROM cash
			GROUP BY customerid
		) ca ON (ca.customerid = d.customerid)
		WHERE (c.invoicenotice IS NULL OR c.invoicenotice = 0) AND d.type IN (?, ?)
			AND d.cdate >= ? AND d.cdate <= ?",
		array(
			CONTACT_EMAIL | CONTACT_INVOICES | CONTACT_NOTIFICATIONS | CONTACT_DISABLED,
			CONTACT_EMAIL | CONTACT_INVOICES | CONTACT_NOTIFICATIONS,
			CONTACT_MOBILE | CONTACT_NOTIFICATIONS | CONTACT_DISABLED,
			CONTACT_MOBILE | CONTACT_NOTIFICATIONS,
			DOC_INVOICE, DOC_CNOTE, $daystart, $dayend));

	if (!empty($documents)) {
		$notifications['invoices']['customers'] = array();
		foreach ($documents as $row) {
			$notifications['invoices']['customers'][] = $row['id'];
			$row['doc_number'] = docnumber($row['number'], ($row['template'] ? $row['template'] : '%N/LMS/%Y'), $row['cdate']);

			$message = parse_customer_data($notifications['invoices']['message'], $row);
			$subject = parse_customer_data($notifications['invoices']['subject'], $row);

			$recipient_mails = ($debug_email ? explode(',', $debug_email) :
				(!empty($row['email']) ? explode(',', trim($row['email'])) : null));
			$recipient_phones = ($debug_phone ? explode(',', $debug_phone) :
				(!empty($row['phone']) ? explode(',', trim($row['phone'])) : null));

			if (!$quiet) {
				if (in_array('mail', $channels) && !empty($recipient_mails))
					foreach ($recipient_mails as $recipient_mail)
						printf("[mail/invoices] %s (%04d) %s: %s" . PHP_EOL,
							$row['name'], $row['id'], $row['doc_number'], $recipient_mail);
				if (in_array('sms', $channels) && !empty($recipient_phones))
					foreach ($recipient_phones as $phone)
						printf("[sms/invoices] %s (%04d): %s: %s" . PHP_EOL,
							$row['name'], $row['id'], $row['doc_number'], $phone);
			}

			if (!$debug) {
				if (in_array('mail', $channels) && !empty($recipient_mails)) {
					$msgid = create_message(MSG_MAIL, $subject, $message);
					foreach ($recipient_mails as $recipient_mail)
						send_mail($msgid, $row['id'], $recipient_mail, $row['name'],
							$subject, $message);
				}
				if (in_array('sms', $channels) && !empty($recipient_phones)) {
					$msgid = create_message(MSG_SMS, $subject, $message);
					foreach ($recipient_phones as $phone)
						send_sms($msgid, $row['id'], $phone, $message);
				}
			}
		}
	}
}

// Debit notes created at current day
if (empty($types) || in_array('notes', $types)) {
	$documents = $DB->GetAll("SELECT d.id AS docid, c.id, c.pin, d.name,
		d.number, n.template, d.cdate, m.email, x.phone, divisions.account,
		COALESCE(ca.balance, 0) AS balance, v.value
		FROM documents d
		JOIN customers c ON (c.id = d.customerid)
		LEFT JOIN divisions ON divisions.id = c.divisionid
		LEFT JOIN (SELECT " . $DB->GroupConcat('contact') . " AS email, customerid
			FROM customercontacts
			WHERE (type & ?) = ?
			GROUP BY customerid
		) m ON (m.customerid = c.id)
		LEFT JOIN (SELECT " . $DB->GroupConcat('contact') . " AS phone, customerid
			FROM customercontacts
			WHERE (type & ?) = ?
			GROUP BY customerid
		) x ON (x.customerid = c.id)
		JOIN (SELECT SUM(value) * -1 AS value, docid
			FROM cash
			GROUP BY docid
		) v ON (v.docid = d.id)
		LEFT JOIN numberplans n ON (d.numberplanid = n.id)
		LEFT JOIN (SELECT SUM(value) AS balance, customerid
			FROM cash
			GROUP BY customerid
		) ca ON (ca.customerid = d.customerid)
		WHERE (c.invoicenotice IS NULL OR c.invoicenotice = 0) AND d.type = ?
			AND d.cdate >= ? AND d.cdate <= ?",
		array(
			CONTACT_EMAIL | CONTACT_NOTIFICATIONS | CONTACT_DISABLED,
			CONTACT_EMAIL | CONTACT_NOTIFICATIONS,
			CONTACT_MOBILE | CONTACT_NOTIFICATIONS | CONTACT_DISABLED,
			CONTACT_MOBILE | CONTACT_NOTIFICATIONS,
			DOC_DNOTE, $daystart, $dayend));
	if (!empty($documents)) {
		$notifications['notes']['customers'] = array();
		foreach ($documents as $row) {
			$notifications['notes']['customers'][] = $row['id'];
			$row['doc_number'] = docnumber($row['number'], ($row['template'] ? $row['template'] : '%N/LMS/%Y'), $row['cdate']);

			$message = parse_customer_data($notifications['notes']['message'], $row);
			$subject = parse_customer_data($notifications['notes']['subject'], $row);

			$recipient_mails = ($debug_email ? explode(',', $debug_email) :
				(!empty($row['email']) ? explode(',', trim($row['email'])) : null));
			$recipient_phones = ($debug_phone ? explode(',', $debug_phone) :
				(!empty($row['phone']) ? explode(',', trim($row['phone'])) : null));

			if (!$quiet) {
				if (in_array('mail', $channels) && !empty($recipient_mails))
					foreach ($recipient_mails as $recipient_mail)
						printf("[mail/notes] %s (%04d) %s: %s" . PHP_EOL,
							$row['name'], $row['id'], $row['doc_number'], $recipient_mail);
				if (in_array('sms', $channels) && !empty($recipient_phones))
					foreach ($recipient_phones as $phone)
						printf("[sms/notes] %s (%04d) %s: %s" . PHP_EOL,
							$row['name'], $row['id'], $row['doc_number'], $recipient_phone);
			}

			if (!$debug) {
				if (in_array('mail', $channels) && !empty($recipient_mails)) {
					$msgid = create_message(MSG_MAIL, $subject, $message);
					foreach ($recipient_mails as $recipient_mail)
						send_mail($msgid, $row['id'], $recipient_mail, $row['name'],
							$subject, $message);
				}
				if (in_array('sms', $channels) && !empty($recipient_phones)) {
					$msgid = create_message(MSG_SMS, $subject, $message);
					foreach ($recipient_phones as $phone)
						send_sms($msgid, $row['id'], $phone, $message);
				}
			}
		}
	}
}

// Node which warning flag has set
if (empty($types) || in_array('warnings', $types)) {
	$customers = $DB->GetAll("SELECT c.id, (" . $DB->Concat('c.lastname', "' '", 'c.name') . ") AS name,
		c.pin, c.message, m.email, x.phone, divisions.account, COALESCE(ca.balance, 0) AS balance
		FROM customers c
		LEFT JOIN divisions ON divisions.id = c.divisionid
		LEFT JOIN (SELECT " . $DB->GroupConcat('contact') . " AS email, customerid
			FROM customercontacts
			WHERE (type & ?) = ?
			GROUP BY customerid
		) m ON (m.customerid = c.id)
		LEFT JOIN (SELECT " . $DB->GroupConcat('contact') . " AS phone, customerid
			FROM customercontacts
			WHERE (type & ?) = ?
			GROUP BY customerid
		) x ON (x.customerid = c.id)
		LEFT JOIN (SELECT SUM(value) AS balance, customerid
			FROM cash
			GROUP BY customerid
		) ca ON (ca.customerid = c.id)
		WHERE c.id IN (SELECT DISTINCT ownerid FROM vnodes WHERE warning = 1)",
		array(
			CONTACT_EMAIL | CONTACT_NOTIFICATIONS | CONTACT_DISABLED,
			CONTACT_EMAIL | CONTACT_NOTIFICATIONS,
			CONTACT_MOBILE | CONTACT_NOTIFICATIONS | CONTACT_DISABLED,
			CONTACT_MOBILE | CONTACT_NOTIFICATIONS));

	if (!empty($customers)) {
		$notifications['warnings']['customers'] = array();
		foreach ($customers as $row) {
			$notifications['warnings']['customers'][] = $row['id'];
			$message = parse_customer_data($row['message'], $row);
			$subject = parse_customer_data($notifications['warnings']['subject'], $row);

			$recipient_mails = ($debug_email ? explode(',', $debug_email) :
				(!empty($row['email']) ? explode(',', trim($row['email'])) : null));
			$recipient_phones = ($debug_phone ? explode(',', $debug_phone) :
				(!empty($row['phone']) ? explode(',', trim($row['phone'])) : null));

			if (!$quiet) {
				if (in_array('mail', $channels) && !empty($recipient_mails))
					foreach ($recipient_mails as $recipient_mail)
						printf("[mail/warnings] %s (%04d): %s" . PHP_EOL,
							$row['name'], $row['id'], $recipient_mail);
				if (in_array('sms', $channels) && !empty($recipient_phones))
					foreach ($recipient_phones as $phone)
						printf("[sms/warnings] %s (%04d): %s" . PHP_EOL,
							$row['name'], $row['id'], $recipient_phone);
			}

			if (!$debug) {
				if (in_array('mail', $channels) && !empty($recipient_mails)) {
					$msgid = create_message(MSG_MAIL, $subject, $message);
					foreach ($recipient_mails as $recipient_mail)
						send_mail($msgid, $row['id'], $recipient_mail, $row['name'],
							$subject, $message);
				}
				if (in_array('sms', $channels) && !empty($recipient_phones)) {
					$msgid = create_message(MSG_SMS, $subject, $message);
					foreach ($recipient_phones as $phone)
						send_sms($msgid, $row['id'], $phone, $message);
				}
			}
		}
	}
}

// send message to customers which have awaiting www messages
if (in_array('www', $channels) && (empty($types) || in_array('messages', $types))) {
	$nodes = $DB->GetAll("SELECT INET_NTOA(ipaddr) AS ip
			FROM vnodes n
		JOIN (SELECT DISTINCT customerid FROM messageitems
			JOIN messages m ON m.id = messageid
			WHERE type = ? AND status = ?
		) m ON m.customerid = n.ownerid
		ORDER BY ipaddr", array(MSG_WWW, MSG_NEW));

	if (!empty($nodes)) {
		if (!$debug) {
			if (!($fh = fopen($notifications['messages']['file'], 'w')))
				continue;
			fwrite($fh, str_replace("\\n", PHP_EOL, $notifications['messages']['header']));
		}

		foreach ($nodes as $node) {
			if (!$quiet)
				printf("[www/messages] %s" . PHP_EOL, $node['ip']);
			if (!$debug)
				fwrite($fh, str_replace("\\n", PHP_EOL,
					parse_node_data($notifications['messages']['rule'], $node)));
		}

		if (!$debug) {
			fwrite($fh, str_replace("\\n", PHP_EOL, $notifications['messages']['footer']));
			fclose($fh);
		}
	}
}

if (in_array('www', $channels))
	foreach ($notifications as $type => $notification) {
		if (!$debug) {
			if (!($fh = fopen($notification['file'], 'w')))
				continue;
			fwrite($fh, str_replace("\\n", PHP_EOL, $notification['header']));
		}
		if (!empty($notification['customers'])) {
			if ($type == 'warnings')
				$nodes = $DB->GetAll("SELECT INET_NTOA(ipaddr) AS ip
						FROM vnodes
					WHERE warning = 1 ORDER BY ipaddr");
			else
				$nodes = $DB->GetAll("SELECT INET_NTOA(ipaddr) AS ip
						FROM vnodes
					WHERE ownerid IN (" . implode(',', $notification['customers']) . ")"
					. " ORDER BY id");
			if (!empty($nodes))
				foreach ($nodes as $node) {
					if (!$quiet)
						printf("[www/%s] %s" . PHP_EOL, $type, $node['ip']);
					if (!$debug)
						fwrite($fh, str_replace("\\n", PHP_EOL,
							parse_node_data($notification['rule'], $node)));
				}
		}
		if (!$debug) {
			fwrite($fh, str_replace("\\n", PHP_EOL, $notification['footer']));
			fclose($fh);
		}
	}

if (in_array('blocking', $channels)) {
	foreach ($notifications as $type => $notification)
		if (!empty($notification['customers'])) {
			$DB->Execute("UPDATE nodes SET access=0 WHERE ownerid IN ("
				. implode(',', $notification['customers']) . ")");
			$DB->Execute("UPDATE assignments SET suspended=1
				WHERE (tariffid <> 0 OR liabilityid <> 0)
				AND (datefrom = 0 OR datefrom <= ?NOW?)
				AND (dateto = 0 OR dateto >= ?NOW?)
				AND customerid IN (" . implode(',', $notification['customers']) . ")");
		}
}

$DB->Destroy();

?>
