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
foreach ($short_to_longs as $short => $long) {
    if (array_key_exists($short, $options)) {
        $options[$long] = $options[$short];
        unset($options[$short]);
    }
}

if (array_key_exists('version', $options)) {
    print <<<EOF
lms-cashimport-bgz.php
(C) 2001-2017 LMS Developers

EOF;
    exit(0);
}

if (array_key_exists('help', $options)) {
    print <<<EOF
lms-cashimport-bgz.php
(C) 2001-2017 LMS Developers

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
lms-cashimport-bgz.php
(C) 2001-2017 LMS Developers

EOF;
}

if (array_key_exists('config-file', $options)) {
    $CONFIG_FILE = $options['config-file'];
} else {
    $CONFIG_FILE = '/etc/lms/lms.ini';
}

if (!$quiet) {
    echo "Using file ".$CONFIG_FILE." as config." . PHP_EOL;
}

if (!is_readable($CONFIG_FILE)) {
    die("Unable to read configuration file [".$CONFIG_FILE."]!" . PHP_EOL);
}

define('CONFIG_FILE', $CONFIG_FILE);

$CONFIG = (array) parse_ini_file($CONFIG_FILE, true);

// Check for configuration vars and set default values
$CONFIG['directories']['sys_dir'] = (!isset($CONFIG['directories']['sys_dir']) ? getcwd() : $CONFIG['directories']['sys_dir']);
$CONFIG['directories']['lib_dir'] = (!isset($CONFIG['directories']['lib_dir']) ? $CONFIG['directories']['sys_dir'].'/lib' : $CONFIG['directories']['lib_dir']);

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
include_once(LIB_DIR . DIRECTORY_SEPARATOR . 'definitions.php');
require_once(LIB_DIR . DIRECTORY_SEPARATOR . 'unstrip.php');
require_once(LIB_DIR . DIRECTORY_SEPARATOR . 'SYSLOG.class.php');

// Initialize Session, Auth and LMS classes

$AUTH = null;
$SYSLOG = null;
$LMS = new LMS($DB, $AUTH, $SYSLOG);
$LMS->ui_lang = $_ui_language;
$LMS->lang = $_language;

$bgz_username = ConfigHelper::getConfig('finances.bgz_username');
$bgz_password = ConfigHelper::getConfig('finances.bgz_password');
$bgz_firm = ConfigHelper::getConfig('finances.bgz_firm');
if (empty($bgz_username) || empty($bgz_password) || empty($bgz_firm)) {
    die("Fatal error: BGZ credentials are not set!" . PHP_EOL);
}

define('USER_AGENT', "Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0)");
define('COOKIE_FILE', tempnam('/tmp', 'lms-bgz-cookies-'));

function log_in_to_bgz($user, $firm, $pass)
{
    $ch = curl_init();
    if (!$ch) {
        return false;
    }

    $params = array(
        'user' => $user,
        'firm' => $firm,
        'pass' => $pass
    );

    curl_setopt_array($ch, array(
        CURLOPT_URL => "https://www.transferbgz.pl/logon/logon.pa",
        CURLOPT_HTTPGET => false,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $params,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_COOKIEJAR => COOKIE_FILE,
        CURLOPT_COOKIEFILE => COOKIE_FILE,
        CURLOPT_SSLVERSION => 3,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_USERAGENT => USER_AGENT
    ));

    $res = curl_exec($ch);
    if (!$res || !mb_ereg_match(".*Witamy w serwisie", $res)) {
        curl_close($ch);
        return false;
    }

    curl_setopt_array($ch, array(
        CURLOPT_URL => "https://www.transferbgz.pl/iden/przegladanieKatalogow.pa?systemId=1"
    ));

    $dirs = curl_exec($ch);
    if (!$dirs) {
        curl_close($ch);
        return false;
    }

    define('GET_FILES_REQUEST', "<table page=\"1\"><sort-components><sort key=\"file_name\" dir=\"ASC\"/></sort-components>"
        ."<filters><filter key=\"name\" value=\"\"/><filter key=\"status\" value=\"4\"/><filter key=\"userName\" value=\"".$user."\"/></filters>"
        ."</table>");
    //define('GET_FILES_REQUEST', "<table page=\"1\"><sort-components><sort key=\"file_name\" dir=\"ASC\"/></sort-components>"
    //  ."<filters><filter key=\"name\" value=\"78_PZ030404.TXT\"/><filter key=\"status\" value=\"\"/><filter key=\"userName\" value=\"".$user."\"/></filters>"
    //  ."</table>");
    return true;
}

function log_out_from_bgz()
{
    $ch = curl_init();
    curl_setopt_array($ch, array(
        CURLOPT_URL => "https://www.transferbgz.pl/logon/logout.pa",
        CURLOPT_HTTPGET => true,
        CURLOPT_POST => false,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_COOKIEJAR => COOKIE_FILE,
        CURLOPT_COOKIEFILE => COOKIE_FILE,
        CURLOPT_SSLVERSION => 3,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_USERAGENT => USER_AGENT
    ));
    $res = curl_exec($ch);
    curl_close($ch);
    return $res;
}

function change_password($oldpass, $newpass)
{
    $ch = curl_init();
    curl_setopt_array($ch, array(
        CURLOPT_URL => "https://www.transferbgz.pl/iden/preZmianaHasla.pa",
        CURLOPT_HTTPGET => true,
        CURLOPT_POST => false,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_COOKIEJAR => COOKIE_FILE,
        CURLOPT_COOKIEFILE => COOKIE_FILE,
        CURLOPT_SSLVERSION => 3,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_USERAGENT => USER_AGENT
    ));
    $res = curl_exec($ch);
    if (!$res || !mb_ereg_match(".*<label for=\"oldPassword\" >Stare hasło</label>", $res)
        || !mb_ereg("<input type=\"hidden\" name=\"pl.com.max.primer.app.AttributeKeys.TOKEN\" value=\"([0-9a-f]+)\" />", $res, $regs)) {
        curl_close($ch);
        return false;
    }

    $params = array(
        'pl.com.max.primer.app.AttributeKeys.TOKEN' => $regs[1],
        'oldPassword' => $oldpass,
        'newPassword' => $newpass,
        'verifyNewPassword' => $newpass
    );
    curl_setopt_array($ch, array(
        CURLOPT_URL => "https://www.transferbgz.pl/iden/zmianaHasla.pa",
        CURLOPT_HTTPGET => false,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $params,
    ));
    $res = curl_exec($ch);
    curl_close($ch);
    if (!$res || !mb_ereg_match(".*Zmiana hasła zakończona powodzeniem\.", $res)) {
        return false;
    }

    return true;
}

function get_files()
{
    $ch = curl_init();
    curl_setopt_array($ch, array(
        CURLOPT_URL => "https://www.transferbgz.pl/iden/przegladanieKatalogowAjax.pax",
        CURLOPT_HTTPGET => false,
        CURLOPT_POST => true,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => GET_FILES_REQUEST,
        CURLOPT_HTTPHEADER => array('Content-type: application/xml', 'Content-length: '. strlen(GET_FILES_REQUEST)),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_COOKIEJAR => COOKIE_FILE,
        CURLOPT_COOKIEFILE => COOKIE_FILE,
        CURLOPT_SSLVERSION => 3,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_USERAGENT => USER_AGENT
    ));

    $files = curl_exec($ch);
    curl_close($ch);
    return $files;
}

function get_file_contents($fileid)
{
    $ch = curl_init();
    curl_setopt_array($ch, array(
        CURLOPT_URL => "https://www.transferbgz.pl/iden/pobraniePliku.pa?fileId=".$fileid,
        CURLOPT_HTTPGET => true,
        CURLOPT_POST => false,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_COOKIEJAR => COOKIE_FILE,
        CURLOPT_COOKIEFILE => COOKIE_FILE,
        CURLOPT_SSLVERSION => 3,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_USERAGENT => USER_AGENT
    ));
    $res = curl_exec($ch);
    curl_close($ch);
    return $res;
}

@include(ConfigHelper::getConfig('phpui.import_config', 'cashimportcfg.php'));
if (!isset($patterns) || !is_array($patterns)) {
    die(trans("Configuration error. Patterns array not found!") . PHP_EOL);
}

function parse_file($filename, $contents)
{
    global $DB, $quiet, $patterns;

    if (!$quiet) {
        printf("Getting cash import file ".$filename." ... ");
    }

    $file       = explode("\n", $contents);
    $patterns_cnt   = isset($patterns) ? sizeof($patterns) : 0;
    $ln     = 0;
    $sum        = array();
    $data       = array();

    foreach ($file as $line) {
        $id = null;
        $count = 0;
        $ln++;
        $is_sum = false;

        if ($patterns_cnt) {
            foreach ($patterns as $idx => $pattern) {
                $theline = $line;

                if (strtoupper($pattern['encoding']) != 'UTF-8') {
                    $theline = @iconv($pattern['encoding'], 'UTF-8//TRANSLIT', $theline);
                }

                if (preg_match($pattern['pattern'], $theline, $matches)) {
                    break;
                }
                if (isset($pattern['pattern_sum']) && preg_match($pattern['pattern_sum'], $theline, $matches)) {
                    $is_sum = true;
                    break;
                }
                $count++;
            }
        }

        // line isn't matching to any pattern
        if ($count == $patterns_cnt) {
            if (trim($line) != '') {
                $error['lines'][$ln] = $patterns_cnt == 1 ? $theline : $line;
            }
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
        $value = str_replace(',', '.', isset($matches[$pattern['pvalue']]) ? trim($matches[$pattern['pvalue']]) : '');

        if (!$pattern['pid']) {
            if (!empty($pattern['pid_regexp'])) {
                $regexp = $pattern['pid_regexp'];
            } else {
                $regexp = '/.*ID[:\-\/]([0-9]{0,4}).*/i';
            }

            if (preg_match($regexp, $theline, $matches)) {
                $id = $matches[1];
            }
        } else {
            $id = isset($matches[$pattern['pid']]) ? intval($matches[$pattern['pid']]) : null;
        }

        // seek invoice number
        if (!$id && !empty($pattern['invoice_regexp'])) {
            if (preg_match($pattern['invoice_regexp'], $theline, $matches)) {
                $invid = $matches[$pattern['pinvoice_number']];
                $invyear = $matches[$pattern['pinvoice_year']];
                $invmonth = !empty($pattern['pinvoice_month']) && $pattern['pinvoice_month'] > 0 ? intval($matches[$pattern['pinvoice_month']]) : 1;

                if ($invid && $invyear) {
                    $from = mktime(0, 0, 0, $invmonth, 1, $invyear);
                    $to = mktime(0, 0, 0, !empty($pattern['pinvoice_month']) && $pattern['pinvoice_month'] > 0 ? $invmonth + 1 : 13, 1, $invyear);
                    $id = $DB->GetOne(
                        'SELECT customerid FROM documents 
							WHERE number=? AND cdate>? AND cdate<? AND type IN (?,?)',
                        array($invid, $from, $to, DOC_INVOICE, DOC_CNOTE)
                    );
                }
            }
        }

        if (!$id && $name && $lastname) {
            $uids = $DB->GetCol('SELECT id FROM customers WHERE UPPER(lastname)=UPPER(?) and UPPER(name)=UPPER(?)', array($lastname, $name));
            if (sizeof($uids)==1) {
                $id = $uids[0];
            }
        } elseif ($id && (!$name || !$lastname)) {
            if ($tmp = $DB->GetRow('SELECT lastname, name FROM customers WHERE id = ?', array($id))) {
                $lastname = $tmp['lastname'];
                $name = $tmp['name'];
            } else {
                $id = null;
            }
        }

        if ($time) {
            if (preg_match($pattern['date_regexp'], $time, $date)) {
                $time = mktime(
                    0,
                    0,
                    0,
                    $date[$pattern['pmonth']],
                    $date[$pattern['pday']],
                    $date[$pattern['pyear']]
                );
            } elseif (!is_numeric($time)) {
                $time = time();
            }
            if (isset($pattern['date_hook'])) {
                $time = $pattern['date_hook']($time, $_FILES['file']['name']);
            }
        } else {
            $time = time();
        }

        if (!empty($pattern['comment_replace'])) {
            $comment = preg_replace($pattern['comment_replace']['from'], $pattern['comment_replace']['to'], $comment);
        }

        $customer = trim($lastname.' '.$name);
        $comment = trim($comment);

        if (!empty($pattern['use_line_hash'])) {
            $hash = md5($theline.(!empty($pattern['line_idx_hash']) ? $ln : ''));
        } else {
            $hash = md5($time.$value.$customer.$comment.(!empty($pattern['line_idx_hash']) ? $ln : ''));
        }

        if (is_numeric($value)) {
            if (isset($pattern['modvalue']) && $pattern['modvalue']) {
                $value = str_replace(',', '.', $value * $pattern['modvalue']);
            }

            if (!$DB->GetOne('SELECT id FROM cashimport WHERE hash = ?', array($hash))) {
                // Add file
                if (!$sourcefileid) {
                    $DB->Execute(
                        'INSERT INTO sourcefiles (name, idate, userid)
						VALUES (?, ?NOW?, ?)',
                        array($filename, Auth::GetCurrentUser())
                    );

                    $sourcefileid = $DB->GetLastInsertId('sourcefiles');
                }

                if (!empty($_POST['source'])) {
                    $sourceid = intval($_POST['source']);
                } elseif (!empty($pattern['id'])) {
                    $sourceid = intval($pattern['id']);
                } else {
                    $sourceid = null;
                }

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

    if ($patterns_cnt && !empty($sum)) {
        foreach ($patterns as $idx => $pattern) {
            if (isset($pattern['pattern_sum']) && isset($pattern['pattern_sum_check']) && !$pattern['pattern_sum_check']($data, $sum)) {
                $error['sum'] = true;
            }
        }
    }

    if ($error['sum'] && $sourcefileid) {
        $DB->Execute('DELETE FROM cashimport WHERE sourcefileid = ?', array($sourcefileid));
        $DB->Execute('DELETE FROM sourcefiles WHERE id = ?', array($sourcefileid));
    }

    if (!$quiet) {
        printf("Done." . PHP_EOL);
    }
}

function commit_cashimport()
{
    global $DB, $LMS;

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

            if ($import['value'] > 0 && $icheck) {
                if ($invoices = $DB->GetAll(
                    'SELECT x.id, x.value FROM (
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
                    array($balance['customerid'], DOC_INVOICE, $balance['customerid'])
                )) {
                    foreach ($invoices as $inv) {
                        $sum += $inv['value'];
                    }

                    $bval = $LMS->GetCustomerBalance($balance['customerid']);
                    $value = f_round($bval + $import['value'] + $sum);

                    foreach ($invoices as $inv) {
                        $inv['value'] = f_round($inv['value']);
                        if ($inv['value'] > $value) {
                            break;
                        } else {
                            // close invoice and assigned credit notes
                            $DB->Execute(
                                'UPDATE documents SET closed = 1
								WHERE id = ? OR reference = ?',
                                array($inv['id'], $inv['id'])
                            );

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

if (!$quiet) {
    printf("Logging in to BGZ ... ");
}
$res = log_in_to_bgz(ConfigHelper::getConfig('finances.bgz_username'), ConfigHelper::getConfig('finances.bgz_firm'), ConfigHelper::getConfig('finances.bgz_password'));
if (!$res) {
    unlink(COOKIE_FILE);
    die("Cannot log in to BGZ!" . PHP_EOL);
}

if (!$quiet) {
    printf("Done." . PHP_EOL . "Getting cash import file list ... ");
}
$files = get_files();
if (!$files) {
    unlink(COOKIE_FILE);
    die("Cannot get file list!" . PHP_EOL);
}
if (!$quiet) {
    printf("Done." . PHP_EOL);
}

$xml = new SimpleXMLIterator($files);
$xml->rewind();
while ($xml->valid()) {
    if ($xml->key() == "rows") {
        $rows = $xml->getChildren();
        $rows->rewind();
        while ($rows->valid()) {
            if ($rows->key() == "row") {
                $fileid = $filename = null;
                $props = $rows->getChildren();
                $props->rewind();
                while ($props->valid()) {
                    switch ($props->key()) {
                        case "id":
                            $fileid = strval($props->current());
                            break;
                        case "file_name":
                            $filename = strval($props->current());
                    }
                    $props->next();
                }
                if (!empty($fileid) && !empty($filename)) {
                    $contents = get_file_contents($fileid);
                    if ($contents) {
                        parse_file($filename, $contents);
                    }
                }
            }
            $rows->next();
        }
    }
    $xml->next();
}

$lastchange = intval(ConfigHelper::getConfig('finances.bgz_password_lastchange', 0));
if (!$lastchange || time() - $lastchange > 30 * 86400) {
    if (!$quiet) {
        printf("Changing BGZ password ... ");
    }
    $oldpass = ConfigHelper::getConfig('finances.bgz_password');
    $newpassarray = str_split($oldpass);
    array_unshift($newpassarray, array_pop($newpassarray));
    $newpass = implode('', $newpassarray);
    $res = change_password($oldpass, $newpass);
    if ($res) {
        $DB->Execute(
            "UPDATE uiconfig SET value = ? WHERE section = 'finances' AND var = 'bgz_password'",
            array($newpass)
        );
        $DB->Execute("DELETE FROM uiconfig WHERE section = 'finances' AND var = 'bgz_password_lastchange'");
        $DB->Execute("INSERT INTO uiconfig (section, var, value) VALUES('finances', 'bgz_password_lastchange', ?NOW?)");
        $bgz_newpassword_email = ConfigHelper::getConfig('finances.bgz_newpassword_email');
        if (!empty($bgz_newpassword_email)) {
            $LMS->SendMail(
                $bgz_newpassword_email,
                array('From' => 'lms-cashimport-bgz.php', 'Subject' => 'Aktualne hasło do panelu BGŻ'),
                $newpass
            );
        }
    }
    if (!$quiet) {
        if ($res) {
            printf("Done." . PHP_EOL);
        } else {
            printf("Error!" . PHP_EOL);
        }
    }
}

if (!$quiet) {
    printf("Logging out from BGZ ... ");
}
log_out_from_bgz();
if (!$quiet) {
    printf("Done." . PHP_EOL);
}

unlink(COOKIE_FILE);

commit_cashimport();

?>
