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
foreach ($short_to_longs as $short => $long)
	if (array_key_exists($short, $options)) {
		$options[$long] = $options[$short];
		unset($options[$short]);
	}

if (array_key_exists('version', $options)) {
	print <<<EOF
lms-sendinvoices.php
(C) 2001-2015 LMS Developers

EOF;
	exit(0);
}

if (array_key_exists('help', $options)) {
	print <<<EOF
lms-sendinvoices.php
(C) 2001-2015 LMS Developers

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
if (!$quiet) {
	print <<<EOF
lms-sendinvoices.php
(C) 2001-2015 LMS Developers

EOF;
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
$CONFIG['directories']['smarty_compile_dir'] = (!isset($CONFIG['directories']['smarty_compile_dir']) ? $CONFIG['directories']['sys_dir'] . DIRECTORY_SEPARATOR . 'templates_c' : $CONFIG['directories']['smarty_compile_dir']);
$CONFIG['directories']['smarty_templates_dir'] = (!isset($CONFIG['directories']['smarty_templates_dir']) ? $CONFIG['directories']['sys_dir'] . DIRECTORY_SEPARATOR . 'templates' : $CONFIG['directories']['smarty_templates_dir']);

define('SYS_DIR', $CONFIG['directories']['sys_dir']);
define('LIB_DIR', $CONFIG['directories']['lib_dir']);
define('SMARTY_COMPILE_DIR', $CONFIG['directories']['smarty_compile_dir']);
define('SMARTY_TEMPLATES_DIR', $CONFIG['directories']['smarty_templates_dir']);

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

$filetype = ConfigHelper::getConfig('invoices.type', '');

if ($filetype != 'pdf') {
	// Initialize templates engine (must be before locale settings)
	$SMARTY = new LMSSmarty;

	// test for proper version of Smarty

	if (defined('Smarty::SMARTY_VERSION'))
		$ver_chunks = preg_split('/[- ]/', preg_replace('/^smarty-/i', '', Smarty::SMARTY_VERSION), -1, PREG_SPLIT_NO_EMPTY);
	else
		$ver_chunks = NULL;
	if (count($ver_chunks) < 1 || version_compare('3.1', $ver_chunks[0]) > 0)
		die('Wrong version of Smarty engine! We support only Smarty-3.x greater than 3.1.' . PHP_EOL);

	define('SMARTY_VERSION', $ver_chunks[0]);

	// add LMS's custom plugins directory
	$SMARTY->addPluginsDir(LIB_DIR . DIRECTORY_SEPARATOR . 'SmartyPlugins');
}

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

if ($filetype != 'pdf') {
	// Set some template and layout variables

	$SMARTY->setTemplateDir(null);
	$custom_templates_dir = ConfigHelper::getConfig('phpui.custom_templates_dir');
	if (!empty($custom_templates_dir) && file_exists(SMARTY_TEMPLATES_DIR . DIRECTORY_SEPARATOR . $custom_templates_dir)
		&& !is_file(SMARTY_TEMPLATES_DIR . DIRECTORY_SEPARATOR . $custom_templates_dir))
		$SMARTY->AddTemplateDir(SMARTY_TEMPLATES_DIR . DIRECTORY_SEPARATOR . $custom_templates_dir);
	$SMARTY->AddTemplateDir(
		array(
			SMARTY_TEMPLATES_DIR . DIRECTORY_SEPARATOR . 'default',
			SMARTY_TEMPLATES_DIR,
		)
	);
	$SMARTY->setCompileDir(SMARTY_COMPILE_DIR);

	$SMARTY->assignByRef('layout', $layout);
	$SMARTY->assignByRef('LANGDEFS', $LANGDEFS);
	$SMARTY->assignByRef('_ui_language', $LMS->ui_lang);
	$SMARTY->assignByRef('_language', $LMS->lang);
}

// now it's time for script settings
$host = ConfigHelper::getConfig('sendinvoices.smtp_host');
$port = ConfigHelper::getConfig('sendinvoices.smtp_port');
$user = ConfigHelper::getConfig('sendinvoices.smtp_user');
$pass = ConfigHelper::getConfig('sendinvoices.smtp_pass');
$auth = ConfigHelper::getConfig('sendinvoices.smtp_auth');

$debug_email = ConfigHelper::getConfig('sendinvoices.debug_email', '');
$sender_name = ConfigHelper::getConfig('sendinvoices.sender_name', '');
$sender_email = ConfigHelper::getConfig('sendinvoices.sender_email', '');
$mail_subject = ConfigHelper::getConfig('sendinvoices.mail_subject', 'Invoice No. %invoice');
$mail_body = ConfigHelper::getConfig('sendinvoices.mail_body', ConfigHelper::getConfig('mail.sendinvoice_mail_body'));
$invoice_filename = ConfigHelper::getConfig('sendinvoices.invoice_filename', 'invoice_%docid');
$notify_email = ConfigHelper::getConfig('sendinvoices.notify_email', '');

if (empty($sender_email))
	die("Fatal error: sender_email unset! Can't continue, exiting." . PHP_EOL);

$smtp_auth_type = ConfigHelper::getConfig('mail.smtp_auth_type');
if (($auth || !empty($smtp_auth_type)) && !preg_match('/^LOGIN|PLAIN|CRAM-MD5|NTLM$/i', $auth ? $auth : $smtp_auth_type))
	die("Fatal error: smtp_auth setting not supported! Can't continue, exiting." . PHP_EOL);

$fakedate = (array_key_exists('fakedate', $options) ? $options['fakedate'] : NULL);
$invoiceid = (array_key_exists('invoiceid', $options) ? $options['invoiceid'] : NULL);

function localtime2() {
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
$groupnames = ConfigHelper::getConfig('sendinvoices.customergroups');
$groupsql = "";
$groups = preg_split("/[[:blank:]]+/", $groupnames, -1, PREG_SPLIT_NO_EMPTY);
foreach ($groups as $group) {
	if (!empty($groupsql))
		$groupsql .= " OR ";
	$groupsql .= "UPPER(g.name) = UPPER('".$group."')";
}
if (!empty($groupsql))
	$customergroups = preg_replace("/\%groups/", $groupsql, $customergroups);

// Initialize Session, Auth and LMS classes

$AUTH = NULL;
$LMS = new LMS($DB, $AUTH, $SYSLOG);
$LMS->ui_lang = $_ui_language;
$LMS->lang = $_language;

if (array_key_exists('test', $options)) {
	$test = TRUE;
	printf("WARNING! You are using test mode." . PHP_EOL);
}

$query = "SELECT d.id, d.number, d.cdate, d.name, d.customerid, n.template, m.email
		FROM documents d 
		LEFT JOIN customers c ON c.id = d.customerid 
		JOIN (SELECT customerid, " . $DB->GroupConcat('contact') . " AS email
			FROM customercontacts WHERE (type & ?) = ? GROUP BY customerid) m ON m.customerid = c.id
		LEFT JOIN numberplans n ON n.id = d.numberplanid 
		WHERE c.deleted = 0 AND d.type IN (?, ?) AND c.invoicenotice = 1"
			. (!empty($invoiceid) ? " AND d.id = " . $invoiceid : " AND d.cdate >= $daystart AND d.cdate <= $dayend")
			. (!empty($groupnames) ? $customergroups : "")
		. " ORDER BY d.number";
$docs = $DB->GetAll($query, array(CONTACT_INVOICES | CONTACT_DISABLED, CONTACT_INVOICES, DOC_INVOICE, DOC_CNOTE));

if (!empty($docs)) {
	if ($filetype == 'pdf') {
		$pdf_type = ConfigHelper::getConfig('invoices.pdf_type', 'tcpdf');
		$pdf_type = ucwords($pdf_type);
		$classname = 'LMS' . $pdf_type . 'Invoice';
	}
	foreach ($docs as $doc) {
		if ($filetype == 'pdf')
			$document = new $classname(trans('Invoices'));
		else
			$document = new LMSHtmlInvoice($SMARTY);

		$invoice = $LMS->GetInvoiceContent($doc['id']);
		$invoice['type'] = trans('ORIGINAL');
		$document->Draw($invoice);
		$res = $document->WriteToString();

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
		$filename = str_replace('%number', $invoice_number, $filename);
		$filename = preg_replace('/[^[:alnum:]_\.]/i', '_', $filename);
		$doc['name'] = '"' . $doc['name'] . '"';

		$mailto = array();
		$mailto_qp_encoded = array();
		foreach (explode(',', $custemail) as $email) {
			$mailto[] = $doc['name'] . " <$email>";
			$mailto_qp_encoded[] = qp_encode($doc['name']) . " <$email>";
		}
		$mailto = implode(', ', $mailto);
		$mailto_qp_encoded = implode(', ', $mailto_qp_encoded);

		if (!$quiet || $test)
			printf("Invoice No. $invoice_number for $mailto" . PHP_EOL);

		if (!$test) {
			$headers = array('From' => $from, 'To' => $mailto_qp_encoded,
				'Subject' => $subject);
			if (!empty($notify_email))
				$headers['Cc'] = $notify_email;
			$res = $LMS->SendMail($custemail . ',' . $notify_email, $headers, $body,
				array(0 => array('content_type' => $ftype, 'filename' => $filename . '.' . $fext,
					'data' => $res)), $host, $port, $user, $pass, $auth);

			if (is_string($res))
				fprintf(STDERR, "Error sending mail: $res" . PHP_EOL);
		}
	}
}

?>
