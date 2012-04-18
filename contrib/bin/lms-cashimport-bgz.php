#!/usr/bin/php
<?php

/*
 * LMS version 1.11-git
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
 *  $Id: lms-cashimport-bgz.php,v 1.1 2012/03/03 15:27:16 chilek Exp $
 */

ini_set('error_reporting', E_ALL&~E_NOTICE);

$parameters = array(
	'C:' => 'config-file:',
	'q' => 'quiet',
	'h' => 'help',
	'v' => 'version',
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
lms-cashimport-bgz.php
(C) 2001-2012 LMS Developers

EOF;
	exit(0);
}

if (array_key_exists('help', $options))
{
	print <<<EOF
lms-cashimport-bgz.php
(C) 2001-2012 LMS Developers

-C, --config-file=/etc/lms/lms.ini      alternate config file (default: /etc/lms/lms.ini);
-h, --help                      print this help and exit;
-v, --version                   print version info and exit;
-q, --quiet                     suppress any output, except errors;

EOF;
	exit(0);
}

$quiet = array_key_exists('quiet', $options);
if (!$quiet)
{
	print <<<EOF
lms-cashimport-bgz.php
(C) 2001-2012 LMS Developers

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

// Initialize Session, Auth and LMS classes

$AUTH = NULL;
$LMS = new LMS($DB, $AUTH, $CONFIG);
$LMS->ui_lang = $_ui_language;
$LMS->lang = $_language;

if (empty($CONFIG['finances']['bgz_username']) || empty($CONFIG['finances']['bgz_firm']) || empty($CONFIG['finances']['bgz_firm']))
	die("Fatal error: BGZ credentials are not set!\n");

define('USER_AGENT', "Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0)");
define('COOKIE_FILE', tempnam('/tmp', 'lms-bgz-cookies-'));

function log_in_to_bgz($user, $firm, $pass) {
	$ch = curl_init();
	if (!$ch)
		return FALSE;

	$params = array(
		'user' => $user,
		'firm' => $firm,
		'pass' => $pass
	);

	curl_setopt_array($ch, array(
		CURLOPT_URL => "https://www.transferbgz.pl/logon/logon.pa",
		CURLOPT_HTTPGET => FALSE,
		CURLOPT_POST => TRUE,
		CURLOPT_POSTFIELDS => $params,
		CURLOPT_RETURNTRANSFER => TRUE,
		CURLOPT_COOKIEJAR => COOKIE_FILE,
		CURLOPT_COOKIEFILE => COOKIE_FILE,
		CURLOPT_SSLVERSION => 3,
		CURLOPT_SSL_VERIFYHOST => 2,
		CURLOPT_SSL_VERIFYPEER => FALSE,
		CURLOPT_USERAGENT => USER_AGENT
	));

	$res = curl_exec($ch);
	if (!$res || !mb_ereg_match(".*Witamy w serwisie", $res)) {
		curl_close($ch);
		return FALSE;
	}

	curl_setopt_array($ch, array(
		CURLOPT_URL => "https://www.transferbgz.pl/iden/przegladanieKatalogow.pa?systemId=1"
	));

	$dirs = curl_exec($ch);
	if (!$dirs) {
		curl_close($ch);
		return FALSE;
	}

	define('GET_FILES_REQUEST', "<table page=\"1\"><sort-components><sort key=\"file_name\" dir=\"ASC\"/></sort-components>"
		."<filters><filter key=\"name\" value=\"\"/><filter key=\"status\" value=\"4\"/><filter key=\"userName\" value=\"".$user."\"/></filters>"
		."</table>");
	//define('GET_FILES_REQUEST', "<table page=\"1\"><sort-components><sort key=\"file_name\" dir=\"ASC\"/></sort-components>"
	//	."<filters><filter key=\"name\" value=\"78_PZ030404.TXT\"/><filter key=\"status\" value=\"\"/><filter key=\"userName\" value=\"".$user."\"/></filters>"
	//	."</table>");
	return TRUE;
}

function log_out_from_bgz() {
	$ch = curl_init();
	curl_setopt_array($ch, array(
		CURLOPT_URL => "https://www.transferbgz.pl/logon/logout.pa",
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
	curl_close($ch);
	return $res;
}

function change_password($oldpass, $newpass) {
	$ch = curl_init();
	curl_setopt_array($ch, array(
		CURLOPT_URL => "https://www.transferbgz.pl/iden/preZmianaHasla.pa",
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
	if (!$res || !mb_ereg_match(".*<label for=\"oldPassword\" >Stare hasło</label>", $res)
		|| !mb_ereg("<input type=\"hidden\" name=\"pl.com.max.primer.app.AttributeKeys.TOKEN\" value=\"([0-9a-f]+)\" />", $res, $regs))
	{
		curl_close($ch);
		return FALSE;
	}

	$params = array(
		'pl.com.max.primer.app.AttributeKeys.TOKEN' => $regs[1],
		'oldPassword' => $oldpass,
		'newPassword' => $newpass,
		'verifyNewPassword' => $newpass
	);
	curl_setopt_array($ch, array(
		CURLOPT_URL => "https://www.transferbgz.pl/iden/zmianaHasla.pa",
		CURLOPT_HTTPGET => FALSE,
		CURLOPT_POST => TRUE,
		CURLOPT_POSTFIELDS => $params,
	));
	$res = curl_exec($ch);
	curl_close($ch);
	if (!$res || !mb_ereg_match(".*Zmiana hasła zakończona powodzeniem\.", $res))
		return FALSE;

	return TRUE;
}

function get_files() {
	$ch = curl_init();
	curl_setopt_array($ch, array(
		CURLOPT_URL => "https://www.transferbgz.pl/iden/przegladanieKatalogowAjax.pax",
		CURLOPT_HTTPGET => FALSE,
		CURLOPT_POST => TRUE,
		CURLOPT_CUSTOMREQUEST => "POST",
		CURLOPT_POSTFIELDS => GET_FILES_REQUEST,
		CURLOPT_HTTPHEADER => array('Content-type: application/xml', 'Content-length: '. strlen(GET_FILES_REQUEST)),
		CURLOPT_RETURNTRANSFER => TRUE,
		CURLOPT_COOKIEJAR => COOKIE_FILE,
		CURLOPT_COOKIEFILE => COOKIE_FILE,
		CURLOPT_SSLVERSION => 3,
		CURLOPT_SSL_VERIFYHOST => 2,
		CURLOPT_SSL_VERIFYPEER => FALSE,
		CURLOPT_USERAGENT => USER_AGENT
	));

	$files = curl_exec($ch);
	curl_close($ch);
	return $files;
}

function get_file_contents($fileid) {
	$ch = curl_init();
	curl_setopt_array($ch, array(
		CURLOPT_URL => "https://www.transferbgz.pl/iden/pobraniePliku.pa?fileId=".$fileid,
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
	curl_close($ch);
	return $res;
}

function parse_file($filename, $contents) {
	global $CONFIG, $DB, $quiet;

	if (!$quiet)
		printf("Getting cash import file ".$filename." ... ");

	@include(!empty($CONFIG['phpui']['import_config']) ? $CONFIG['phpui']['import_config'] : 'cashimportcfg.php');

	if (!isset($patterns) || !is_array($patterns))
	{
		printf(trans("Configuration error. Patterns array not found!")."\n");
		return;
	}

	$file		= explode("\n", $contents);
	$patterns_cnt	= isset($patterns) ? sizeof($patterns) : 0;
	$ln		= 0;
	$sum		= array();
	$data		= array();

	foreach($file as $line)
	{
		$id = NULL;
		$count = 0;
		$ln++;
		$is_sum = false;

		if($patterns_cnt) foreach($patterns as $idx => $pattern)
		{
			$theline = $line;

			if(strtoupper($pattern['encoding']) != 'UTF-8')
			{
				$theline = @iconv($pattern['encoding'], 'UTF-8//TRANSLIT', $theline);
			}

			if (preg_match($pattern['pattern'], $theline, $matches))
				break;
			if (isset($pattern['pattern_sum']) && preg_match($pattern['pattern_sum'], $theline, $matches)) {
				$is_sum = true;
				break;
			}
			$count++;
		}

		// line isn't matching to any pattern
		if($count == $patterns_cnt)
		{
			if(trim($line) != '') 
				$error['lines'][$ln] = $patterns_cnt == 1 ? $theline : $line;
			continue; // go to next line
		}

		if ($is_sum) {
			$sum = $matches;
			continue;
		}

		$name = isset($matches[$pattern['pname']]) ? trim($matches[$pattern['pname']]) : '';
		$lastname = isset($matches[$pattern['plastname']]) ? trim($matches[$pattern['plastname']]) : '';
		$comment = isset($matches[$pattern['pcomment']]) ? trim($matches[$pattern['pcomment']]) : '';
		$time = isset($matches[$pattern['pdate']]) ? trim($matches[$pattern['pdate']]) : '';
		$value = str_replace(',','.', isset($matches[$pattern['pvalue']]) ? trim($matches[$pattern['pvalue']]) : '');

		if(!$pattern['pid'])
		{
			if(!empty($pattern['pid_regexp'])) 
				$regexp = $pattern['pid_regexp'];
			else
				$regexp = '/.*ID[:\-\/]([0-9]{0,4}).*/i';

			if(preg_match($regexp, $theline, $matches))
				$id = $matches[1];
		}
		else
			$id = isset($matches[$pattern['pid']]) ? intval($matches[$pattern['pid']]) : NULL;

		// seek invoice number
		if(!$id && !empty($pattern['invoice_regexp']))
		{
			if(preg_match($pattern['invoice_regexp'], $theline, $matches)) 
			{
				$invid = $matches[$pattern['pinvoice_number']];
				$invyear = $matches[$pattern['pinvoice_year']];
				$invmonth = !empty($pattern['pinvoice_month']) && $pattern['pinvoice_month'] > 0 ? intval($matches[$pattern['pinvoice_month']]) : 1;

				if($invid && $invyear)
				{
					$from = mktime(0,0,0, $invmonth, 1, $invyear);
					$to = mktime(0,0,0, !empty($pattern['pinvoice_month']) && $pattern['pinvoice_month'] > 0 ? $invmonth + 1 : 13, 1, $invyear);
					$id = $DB->GetOne('SELECT customerid FROM documents 
							WHERE number=? AND cdate>? AND cdate<? AND type IN (?,?)', 
							array($invid, $from, $to, DOC_INVOICE, DOC_CNOTE));
				}
			}
		}

		if(!$id && $name && $lastname)
		{
			$uids = $DB->GetCol('SELECT id FROM customers WHERE UPPER(lastname)=UPPER(?) and UPPER(name)=UPPER(?)', array($lastname, $name));
			if(sizeof($uids)==1)
				$id = $uids[0];
		}
		elseif($id && (!$name || !$lastname))
		{
			if($tmp = $DB->GetRow('SELECT lastname, name FROM customers WHERE id = ?', array($id)))
			{
				$lastname = $tmp['lastname'];
				$name = $tmp['name'];
			}
			else
				$id = NULL;
		}

		if($time)
		{
			if(preg_match($pattern['date_regexp'], $time, $date))
			{
				$time = mktime(0,0,0, 
					$date[$pattern['pmonth']], 
					$date[$pattern['pday']], 
					$date[$pattern['pyear']]);
			}
			elseif(!is_numeric($time))
				$time = time();
			if (isset($pattern['date_hook']))
				$time = $pattern['date_hook']($time, $_FILES['file']['name']);
		}
		else
			$time = time();

		if(!empty($pattern['comment_replace']))
			$comment = preg_replace($pattern['comment_replace']['from'], $pattern['comment_replace']['to'], $comment);

		$customer = trim($lastname.' '.$name);
		$comment = trim($comment);

		if(!empty($pattern['use_line_hash']))
			$hash = md5($theline.(!empty($pattern['line_idx_hash']) ? $ln : ''));
		else
			$hash = md5($time.$value.$customer.$comment.(!empty($pattern['line_idx_hash']) ? $ln : ''));

		if(is_numeric($value))
		{
			if(isset($pattern['modvalue']) && $pattern['modvalue'])
			{
				$value = str_replace(',','.', $value * $pattern['modvalue']);
			}

			if (!$DB->GetOne('SELECT id FROM cashimport WHERE hash = ?', array($hash)))
			{
				// Add file
				if (!$sourcefileid) {
					$DB->Execute('INSERT INTO sourcefiles (name, idate, userid)
						VALUES (?, ?NOW?, ?)',
						array($filename, $AUTH->id));

					$sourcefileid = $DB->GetLastInsertId('sourcefiles');
				}

				if(!empty($_POST['source']))
					$sourceid = intval($_POST['source']);
				elseif(!empty($pattern['id']))
					$sourceid = intval($pattern['id']);
				else
					$sourceid = NULL;

				$values = array($time, $value, $customer, $id, $comment, $hash, $sourceid, $sourcefileid);
				$DB->Execute('INSERT INTO cashimport (date, value, customer,
					customerid, description, hash, sourceid, sourcefileid)
					VALUES (?, ?, ?, ?, ?, ?, ?, ?)', $values);

				$keys = array('time', 'value', 'customer', 'id', 'comment', 'hash', 'sourceid', 'sourcefileid');
				$data[] = array_combine($keys, $values);
			} else {
				$error['lines'][$ln] = array(
					'customer' => $customer,
					'customerid' => $id,
					'date' => $time,
					'value' => $value,
					'comment' => $comment
				);
			}
		}
	}

	if ($patterns_cnt && !empty($sum))
		foreach ($patterns as $idx => $pattern)
			if (isset($pattern['pattern_sum']) && isset($pattern['pattern_sum_check']) && !$pattern['pattern_sum_check']($data, $sum))
				$error['sum'] = true;

	if ($error['sum'] && $sourcefileid) {
		$DB->Execute('DELETE FROM cashimport WHERE sourcefileid = ?', array($sourcefileid));
		$DB->Execute('DELETE FROM sourcefiles WHERE id = ?', array($sourcefileid));
	}

	if (!$quiet)
		printf("Done.\n");
}

function commit_cashimport()
{
	global $DB, $LMS, $CONFIG;

	$imports = $DB->GetAll('SELECT i.*, f.idate
		FROM cashimport i
		LEFT JOIN sourcefiles f ON (f.id = i.sourcefileid)
		WHERE i.closed = 0 AND i.customerid <> 0');

	if (!empty($imports)) {
		$idate  = isset($CONFIG['finances']['cashimport_use_idate'])
			&& chkconfig($CONFIG['finances']['cashimport_use_idate']);
		$icheck = isset($CONFIG['finances']['cashimport_checkinvoices'])
			&& chkconfig($CONFIG['finances']['cashimport_checkinvoices']);

		foreach ($imports as $import) {

			$DB->BeginTrans();

			$balance['time'] = $idate ? $import['idate'] : $import['date'];
			$balance['type'] = 1;
			$balance['value'] = $import['value'];
			$balance['customerid'] = $import['customerid'];
			$balance['comment'] = $import['description'];
			$balance['importid'] = $import['id'];
			$balance['sourceid'] = $import['sourceid'];
			$balance['userid'] = 0;

			if ($import['value'] > 0 && $icheck)
			{
				if($invoices = $DB->GetAll('SELECT d.id,
						(SELECT SUM(value*count) FROM invoicecontents WHERE docid = d.id) +
						COALESCE((SELECT SUM((a.value+b.value)*(a.count+b.count)) - SUM(b.value*b.count)
							FROM documents dd
							JOIN invoicecontents a ON (a.docid = dd.id)
							JOIN invoicecontents b ON (dd.reference = b.docid AND a.itemid = b.itemid)
							WHERE dd.reference = d.id
							GROUP BY dd.reference), 0) AS value
					FROM documents d
					WHERE d.customerid = ? AND d.type = ? AND d.closed = 0
					GROUP BY d.id, d.cdate ORDER BY d.cdate',
					array($balance['customerid'], DOC_INVOICE)))
				{
					foreach($invoices as $inv)
						$sum += $inv['value'];

					$bval = $LMS->GetCustomerBalance($balance['customerid']);
					$value = f_round($bval + $import['value'] + $sum);

					foreach($invoices as $inv) {
						$inv['value'] = f_round($inv['value']);
						if($inv['value'] > $value)
							break;
						else
						{
							// close invoice and assigned credit notes
							$DB->Execute('UPDATE documents SET closed = 1
								WHERE id = ? OR reference = ?',
								array($inv['id'], $inv['id']));

							$value -= $inv['value'];
						}
					}
				}
			}

			$DB->Execute('UPDATE cashimport SET closed = 1 WHERE id = ?', array($import['id']));
			$LMS->AddBalance($balance);

			$DB->CommitTrans();
		}
	}
}

if (!$quiet)
	printf("Logging in to BGZ ... ");
$res = log_in_to_bgz($CONFIG['finances']['bgz_username'], $CONFIG['finances']['bgz_firm'], $CONFIG['finances']['bgz_password']);
if (!$res) {
	unlink(COOKIE_FILE);
	die("Cannot log in to BGZ!\n");
}

if (!$quiet)
	printf("Done.\nGetting cash import file list ... ");
$files = get_files();
if (!$files) {
	unlink(COOKIE_FILE);
	die("Cannot get file list!\n");
}
if (!$quiet)
	printf("Done.\n");

$xml = new SimpleXMLIterator($files);
$xml->rewind();
while ($xml->valid()) {
	if ($xml->key() == "rows") {
		$rows = $xml->getChildren();
		$rows->rewind();
		while ($rows->valid()) {
			if ($rows->key() == "row") {
				$fileid = $filename = NULL;
				$props = $rows->getChildren();
				$props->rewind();
				while ($props->valid()) {
					switch ($props->key()) {
						case "id":
							$fileid = $props->current();
							break;
						case "file_name":
							$filename = strval($props->current());
					}
					$props->next();
				}
				if (!empty($fileid) && !empty($filename)) {
					$contents = get_file_contents($fileid);
					if ($contents)
						parse_file($filename, $contents);
				}
			}
			$rows->next();
		}
	}
	$xml->next();
}

$lastchange = !empty($CONFIG['finances']['bgz_password_lastchange']) ? intval($CONFIG['finances']['bgz_password_lastchange']) : 0;
if (!$lastchange || time() - $lastchange > 30 * 86400)
{
	if (!$quiet)
		printf("Changing BGZ password ... ");
	$oldpass = $CONFIG['finances']['bgz_password'];
	$newpassarray = str_split($oldpass);
	array_unshift($newpassarray, array_pop($newpassarray));
	$newpass = implode('', $newpassarray);
	$res = change_password($oldpass, $newpass);
	if ($res) {
		$DB->Execute("UPDATE uiconfig SET value = ? WHERE section = 'finances' AND var = 'bgz_password'",
			array($newpass));
		$DB->Execute("DELETE FROM uiconfig WHERE section = 'finances' AND var = 'bgz_password_lastchange'");
		$DB->Execute("INSERT INTO uiconfig (section, var, value) VALUES('finances', 'bgz_password_lastchange', ?NOW?)");
		if (!empty($CONFIG['finances']['bgz_newpassword_email']))
			$LMS->SendMail($CONFIG['finances']['bgz_newpassword_email'],
				array('From' => 'lms-cashimport-bgz.php', 'Subject' => 'Aktualne hasło do panelu BGŻ'), $newpass);
	}
	if (!$quiet)
		if ($res)
			printf("Done.\n");
		else
			printf("Error!\n");
}

if (!$quiet)
	printf("Logging out from BGZ ... ");
log_out_from_bgz();
if (!$quiet)
	printf("Done.\n");

unlink(COOKIE_FILE);

commit_cashimport();

?>
