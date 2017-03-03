#!/usr/bin/env php
<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2017 LMS Developers
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
	'm:' => 'message-file:',
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
lms-sms2rt.php
(C) 2001-2017 LMS Developers

EOF;
	exit(0);
}

if (array_key_exists('help', $options)) {
	print <<<EOF
lms-sms2rt.php
(C) 2001-2017 LMS Developers

-C, --config-file=/etc/lms/lms.ini      alternate config file (default: /etc/lms/lms.ini);
-m, --message-file=<message-file>       name of message file;
-h, --help                      print this help and exit;
-v, --version                   print version info and exit;
-q, --quiet                     suppress any output, except errors;

EOF;
	exit(0);
}

$quiet = array_key_exists('quiet', $options);
if (!$quiet) {
	print <<<EOF
lms-sms2rt.php
(C) 2001-2017 LMS Developers

EOF;
}

if (array_key_exists('config-file', $options))
	$CONFIG_FILE = $options['config-file'];
else
	$CONFIG_FILE = DIRECTORY_SEPARATOR . 'etc' . DIRECTORY_SEPARATOR . 'lms' . DIRECTORY_SEPARATOR . 'lms.ini';

if (!$quiet)
	echo "Using file ".$CONFIG_FILE." as config." . PHP_EOL;

if (!is_readable($CONFIG_FILE))
	die('Unable to read configuration file ['.$CONFIG_FILE.']!' . PHP_EOL);

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
	die("Composer autoload not found. Run 'composer install' command from LMS directory and try again. More informations at https://getcomposer.org/" . PHP_EOL);
}

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

// Include required files (including sequence is important)

require_once(LIB_DIR . DIRECTORY_SEPARATOR . 'common.php');
require_once(LIB_DIR . DIRECTORY_SEPARATOR . 'language.php');
include_once(LIB_DIR . DIRECTORY_SEPARATOR . 'definitions.php');

$SYSLOG = SYSLOG::getInstance();

// Initialize Session, Auth and LMS classes

$AUTH = NULL;
$LMS = new LMS($DB, $AUTH, $SYSLOG);
$LMS->ui_lang = $_ui_language;
$LMS->lang = $_language;

$incoming_queue = ConfigHelper::getConfig('sms.incoming_queue', 'SMS');
$default_mail_from = ConfigHelper::getConfig('sms.default_mail_from', 'root@localhost');
$categories = ConfigHelper::getConfig('sms.categories', 'default');
$categories = preg_split('/\s*,\s*/', trim($categories));
$lms_url = ConfigHelper::getConfig('sms.lms_url', '', true);
$service = ConfigHelper::getConfig('sms.service', '', true);
$prefix = ConfigHelper::getConfig('sms.prefix', '', true);
$newticket_notify = ConfigHelper::checkConfig('phpui.newticket_notify');
$helpdesk_customerinfo = ConfigHelper::checkConfig('phpui.helpdesk_customerinfo');
$helpdesk_sendername = ConfigHelper::getConfig('phpui.helpdesk_sender_name');

if (isset($options['message-file']))
	$message_file = $options['message-file'];
else
	die("Required message file parameter!" . PHP_EOL);

if (($queueid = $DB->GetOne("SELECT id FROM rtqueues WHERE UPPER(name)=UPPER(?)",
	array($incoming_queue))) == NULL)
	die("Undefined queue!" . PHP_EOL);

if (($fh = fopen($message_file, "r")) != NULL) {
	$sms = fread($fh, 4000);
	fclose($fh);

	$lines = explode("\n", $sms);

	$body = FALSE;
	$message = "";
	$phone = NULL;
	$date = NULL;
	$ucs = false;
	reset($lines);
	while (($line = current($lines)) !== FALSE) {
		if (preg_match("/^From: ([[:digit:]]{3,15})$/", $line, $matches) && $phone == NULL)
			$phone = $matches[1];
		if (preg_match("/^Received: (.*)$/", $line, $matches) && $date == NULL)
			$date = strtotime($matches[1]);
		if (preg_match("/^Alphabet:.*UCS2?$/", $line))
			$ucs = true;
		if (empty($line) && !$body)
			$body = TRUE;
		else
			if ($body)
				$message .= $line;
		next($lines);
	}
	if ($ucs)
		$message = iconv("UNICODEBIG", "UTF-8", $message);

	if (!empty($phone)) {
		$phone = preg_replace('/^' . $prefix . '/', '', $phone);
		$customer = $DB->GetRow("SELECT customerid AS cid, ".$DB->Concat('lastname', "' '", 'c.name')." AS name 
				FROM customercontacts cc 
				LEFT JOIN customers c ON c.id = cc.customerid 
				WHERE c.deleted = 0 AND (cc.type & ?) > 0 AND REPLACE(REPLACE(contact, ' ', ''), '-', '') ?LIKE? ?",
					array(CONTACT_MOBILE | CONTACT_LANDLINE, "%" . $phone));
		$formatted_phone = preg_replace('/^([0-9]{3})([0-9]{3})([0-9]{3})$/', '$1 $2 $3', $phone);
	} else
		$customer = NULL;

//	if ($phone[0] != "+")
//		$phone = "+" . $phone;

	$DB->Execute("INSERT INTO rttickets (queueid, requestor, 
		subject, customerid, createtime) VALUES(?, ?, ?, ?, ?)",
		array($queueid, !empty($customer['name']) ? $customer['name'] : (empty($phone) ? '' : $formatted_phone),
			trans('SMS from $a', (empty($phone) ? trans("unknown") : $formatted_phone)), !empty($customer['cid']) ? $customer['cid'] : 0, $date));
	$tid = $DB->GetLastInsertID("rttickets");
	$DB->Execute("INSERT INTO rtmessages (ticketid, customerid, phonefrom, subject, body, createtime)
		VALUES(?, ?, ?, ?, ?, ?NOW?)",
		array($tid, !empty($customer['cid']) ? $customer['cid'] : 0, empty($phone) ? '' : $phone,
			trans('SMS from $a', empty($phone) ? trans("unknown") : $formatted_phone), $message));

	foreach($categories as $category)
		if (($catid = $DB->GetOne("SELECT id FROM rtcategories WHERE name = ?", array($category))) != NULL)
			$DB->Execute("INSERT INTO rtticketcategories (ticketid, categoryid) VALUES(?, ?)", array($tid, $catid));

	if ($newticket_notify) {
		if (!empty($helpdesk_sender_name)) {
			$mailfname = $LMS->GetQueueName($queueid);
			$mailfname = '"'.$mailfname.'"';
		} else
			$mailfname = '';

		if ($qemail = $LMS->GetQueueEmail($queueid))
			$mailfrom = $qemail;
		else
			$mailfrom = $default_mail_from;

		$headers['From'] = $mailfname.' <'.$mailfrom.'>';
		$headers['Subject'] = sprintf("[RT#%06d] %s", $tid, trans('SMS from $a', (empty($phone) ? trans("unknown") : $formatted_phone)));
		$headers['Reply-To'] = $headers['From'];

		$sms_body = $headers['Subject']."\n".$message;
		if (!empty($lms_url))
			$message = $message."\n\n" . $lms_url . '?m=rtticketview&id='.$tid;

		if ($helpdesk_customerinfo && !empty($customer['cid'])) {
			$info = $DB->GetRow("SELECT " . $DB->Concat('UPPER(lastname)',"' '",'c.name') . " AS customername,
					address, zip, city FROM customeraddressview c WHERE c.id = ?", array($customer['cid']));
			$info['contacts'] = $DB->GetAll("SELECT contact, name, type FROM customercontacts
				WHERE customerid = ?", array($customer['cid']));
			$info['locations'] = $DB->GetCol("SELECT DISTINCT location FROM nodes WHERE ownerid = ?",
				array($customer['cid']));

			$emails = array();
			$phones = array();
			if (!empty($info['contacts']))
				foreach ($info['contacts'] as $contact) {
					$target = $contact['contact'] . (strlen($contact['name']) ? ' (' . $contact['name'] . ')' : '');
					if ($contact['type'] == CONTACT_EMAIL)
						$emails[] = $target;
					else
						$phones[] = $target;
				}


			$message .= "\n\n-- \n";
			$message .= trans('Customer:').' '.$info['customername']."\n";
			$message .= trans('ID:').' '.sprintf('%04d', $customer['cid'])."\n";
			$message .= trans('Address:') . ' ' . (empty($info['locations']) ? $info['address'] . ', ' . $info['zip'] . ' ' . $info['city']
				: implode(', ', $info['locations'])) . "\n";
			if (!empty($phones))
				$message .= trans('Phone:').' ' . implode(', ', $phones) . "\n";
			if (!empty($emails))
				$message .= trans('E-mail:').' ' . implode(', ', $emails);

			$sms_body .= "\n";
			$sms_body .= trans('Customer:').' '.$info['customername'];
			$sms_body .= ' '.sprintf('(%04d)', $customer['cid']).'. ';
			$sms_body .= empty($info['locations']) ? $info['address'] . ', ' . $info['zip'] . ' ' . $info['city']
				: implode(', ', $info['locations']);
			if (!empty($phones))
				$sms_body .= '. ' . trans('Phone:') . ' ' . preg_replace('/([0-9])[\s-]+([0-9])/', '\1\2', implode(',', $phones));
		}

		// send email
		if($recipients = $DB->GetCol("SELECT DISTINCT email
			FROM users, rtrights
			WHERE users.id = userid AND queueid = ? AND email <> ''
				AND (rtrights.rights & 8) > 0 AND deleted = 0
				AND (ntype & ?) > 0",
			array($queueid, MSG_MAIL)))
		{
			foreach($recipients as $email)
			{
				$headers['To'] = '<'.$email.'>';
				$LMS->SendMail($email, $headers, $message);
			}
		}

		// send sms
		if (!empty($service) && ($recipients = $DB->GetCol("SELECT DISTINCT phone
			FROM users, rtrights
			WHERE users.id = userid AND queueid = ? AND phone <> ''
				AND (rtrights.rights & 8) > 0 AND deleted = 0
				AND (ntype & ?) > 0",
			array($queueid, MSG_SMS))))
			foreach ($recipients as $phone)
				$LMS->SendSMS($phone, $sms_body);
	}
} else
	die("Message file doesn't exist!" . PHP_EOL);

$DB->Destroy();

?>
