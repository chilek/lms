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
foreach ($short_to_longs as $short => $long)
	if (array_key_exists($short, $options)) {
		$options[$long] = $options[$short];
		unset($options[$short]);
	}

if (array_key_exists('version', $options)) {
	print <<<EOF
lms-cashimport.php
(C) 2001-2016 LMS Developers

EOF;
	exit(0);
}

if (array_key_exists('help', $options)) {
	print <<<EOF
lms-cashimport.php
(C) 2001-2016 LMS Developers

-C, --config-file=/etc/lms/lms.ini      alternate config file (default: /etc/lms/lms.ini);
-h, --help                      print this help and exit;
-v, --version                   print version info and exit;
-q, --quiet                     suppress any output, except errors;

EOF;
	exit(0);
}

$quiet = array_key_exists('quiet', $options);
if (!$quiet) {
	print <<<EOF
lms-cashimport.php
(C) 2001-2016 LMS Developers

EOF;
}

if (array_key_exists('config-file', $options))
	$CONFIG_FILE = $options['config-file'];
else
	$CONFIG_FILE = '/etc/lms/lms.ini';

if (!$quiet)
	echo "Using file " . $CONFIG_FILE . " as config." . PHP_EOL;

if (!is_readable($CONFIG_FILE))
	die("Unable to read configuration file [" . $CONFIG_FILE . "]!" . PHP_EOL);

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

$server = ConfigHelper::getConfig('cashimport.server');
$username = ConfigHelper::getConfig('cashimport.username');
$password = ConfigHelper::getConfig('cashimport.password');
if (empty($server) || empty($username) || empty($password))
	die("Fatal error: mailbox credentials are not set!" . PHP_EOL);

@include(ConfigHelper::getConfig('phpui.import_config', 'cashimportcfg.php'));
if (!isset($patterns) || !is_array($patterns))
	die(trans("Configuration error. Patterns array not found!") . PHP_EOL);

function parse_file($filename, $contents) {
	global $quiet, $patterns;

	$DB = LMSDB::getInstance();

	if (!$quiet)
		printf("Getting cash import file ".$filename." ... ");

	$file		= preg_split('/\r?\n/', $contents);
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
		$srcaccount = isset($matches[$pattern['srcaccount']]) ? trim($matches[$pattern['srcaccount']]) : '';
		$dstaccount = isset($matches[$pattern['dstaccount']]) ? trim($matches[$pattern['dstaccount']]) : '';

		if (!$pattern['pid']) {
			if (!empty($pattern['pid_regexp']))
				$regexp = $pattern['pid_regexp'];
			else
				$regexp = '/.*ID[:\-\/]([0-9]{0,4}).*/i';

			if (preg_match($regexp, $theline, $matches))
				$id = $matches[1];
		} else
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

		// seek by explicitly given source or destination customer account numbers
		if (!$id)
			if (!empty($dstaccount))
				$id = $DB->GetOne('SELECT customerid FROM customercontacts
					WHERE contact = ? AND (type & ?) = ?',
					array($dstaccount, CONTACT_BANKACCOUNT | CONTACT_INVOICES | CONTACT_DISABLED,
						CONTACT_BANKACCOUNT | CONTACT_INVOICES));
			elseif (!empty($srcaccount))
				$id = $DB->GetOne('SELECT customerid FROM customercontacts
					WHERE contact = ? AND (type & ?) = ?',
					array($srcaccount, CONTACT_BANKACCOUNT | CONTACT_INVOICES | CONTACT_DISABLED,
						CONTACT_BANKACCOUNT));

		if (!$id && $name && $lastname) {
			$uids = $DB->GetCol('SELECT id FROM customers WHERE UPPER(lastname)=UPPER(?) and UPPER(name)=UPPER(?)',
				array($lastname, $name));
			if (count($uids) == 1)
				$id = $uids[0];
		} elseif ($id && (!$name || !$lastname))
			if ($tmp = $DB->GetRow('SELECT id, lastname, name FROM customers WHERE '
				. (isset($pattern['extid']) && $pattern['extid'] ? 'ext' : '') . 'id = ?', array($id))) {
				if (isset($pattern['extid']) && $pattern['extid'])
					$id = $tmp['id'];
				$lastname = $tmp['lastname'];
				$name = $tmp['name'];
			} else
				$id = NULL;

		if ($time) {
			if (preg_match($pattern['date_regexp'], $time, $date)) {
				$time = mktime(0,0,0, 
					$date[$pattern['pmonth']], 
					$date[$pattern['pday']], 
					$date[$pattern['pyear']]);
			} elseif(!is_numeric($time))
				$time = time();
			if (isset($pattern['date_hook']))
				$time = $pattern['date_hook']($time, $_FILES['file']['name']);
		} else
			$time = time();

		if (!empty($pattern['comment_replace']))
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
		printf("Done." . PHP_EOL);
}

function commit_cashimport() {
	global $LMS;

	$DB = LMSDB::getInstance();

	$imports = $DB->GetAll('SELECT i.*, f.idate
		FROM cashimport i
		LEFT JOIN sourcefiles f ON (f.id = i.sourcefileid)
		WHERE i.closed = 0 AND i.customerid <> 0');

	if (!empty($imports)) {
            
		$idate  = ConfigHelper::checkValue(ConfigHelper::getConfig('finances.cashimport_use_idate', false));
		$icheck = ConfigHelper::checkValue(ConfigHelper::getConfig('finances.cashimport_checkinvoices', false));

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
				if($invoices = $DB->GetAll('SELECT x.id, x.value FROM (
                                        SELECT d.id,
                                            (SELECT SUM(value*count) FROM invoicecontents WHERE docid = d.id) +
                                            COALESCE((
                                                SELECT SUM((a.value+b.value)*(a.count+b.count)) - SUM(b.value*b.count) 
                                                FROM documents dd
                                                JOIN invoicecontents a ON (a.docid = dd.id)
                                                JOIN invoicecontents b ON (dd.reference = b.docid AND a.itemid = b.itemid)
                                                WHERE dd.reference = d.id
                                                GROUP BY dd.reference), 0) AS value,
                                            d.cdate
                                        FROM documents d
                                        WHERE d.customerid = ? AND d.type = ? AND d.closed = 0
                                        GROUP BY d.id, d.cdate
                                        UNION
                                        SELECT d.id, dn.value, d.cdate
                                        FROM documents d 
                                        JOIN debitnotecontents dn ON dn.docid = d.id 
                                        WHERE d.customerid = ?
                                    ) x ORDER BY x.cdate',
					array($balance['customerid'], DOC_INVOICE, $balance['customerid'])))
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

$ih = @imap_open("{" . ConfigHelper::getConfig('cashimport.server') . "}INBOX", ConfigHelper::getConfig('cashimport.username'), ConfigHelper::getConfig('cashimport.password'));
if (!$ih)
	die("Cannot connect to mail server!" . PHP_EOL);

$posts = imap_search($ih, ConfigHelper::checkValue(ConfigHelper::getConfig('cashimport.use_seen_flag', true)) ? 'UNSEEN' : 'ALL');
if (!empty($posts))
	foreach ($posts as $postid) {
		$post = imap_fetchstructure($ih, $postid);
		if ($post->type == 1) {
			$parts = $post->parts;
			//print_r($parts);
			foreach ($parts as $partid => $part )
				if ($part->ifdisposition && strtoupper($part->disposition) == 'ATTACHMENT' && $part->type == 0) {
					$fname = $part->dparameters[0]->value;
					$msg = imap_fetchbody($ih, $postid, $partid + 1);
					if ($part->encoding == 3)
						$msg = imap_base64($msg);
					if (ConfigHelper::checkValue(ConfigHelper::getConfig('cashimport.use_seen_flag', true)))
						imap_setflag_full($ih, $postid, "\\Seen");
					parse_file($fname, $msg);
					if (ConfigHelper::checkValue(ConfigHelper::getConfig('cashimport.autocommit', false)))
						commit_cashimport();
				}
		}
	}

imap_close($ih);

?>
