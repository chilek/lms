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

ini_set('error_reporting', E_ALL&~E_NOTICE);

$parameters = array(
    'C:' => 'config-file:',
    'q' => 'quiet',
    'h' => 'help',
    'v' => 'version',
    's:' => 'section:',
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
lms-asterisk.php
(C) 2001-2016 LMS Developers

EOF;
    exit(0);
}

if (array_key_exists('help', $options)) {
    print <<<EOF
lms-asterisk.php
(C) 2001-2016 LMS Developers

-C, --config-file=/etc/lms/lms.ini      alternate config file (default: /etc/lms/lms.ini);
-h, --help                      print this help and exit;
-v, --version                   print version info and exit;
-q, --quiet                     suppress any output, except errors
-s, --section=<section-name>    section name from lms configuration where settings
                                are stored

EOF;
    exit(0);
}

$quiet = array_key_exists('quiet', $options);
if (!$quiet) {
    print <<<EOF
lms-asterisk.php
(C) 2001-2016 LMS Developers

EOF;
}

if (array_key_exists('config-file', $options)) {
    $CONFIG_FILE = $options['config-file'];
} else {
    $CONFIG_FILE = DIRECTORY_SEPARATOR . 'etc' . DIRECTORY_SEPARATOR . 'lms' . DIRECTORY_SEPARATOR . 'lms.ini';
}

if (!$quiet) {
    echo "Using file ".$CONFIG_FILE." as config." . PHP_EOL;
}

$config_section = (array_key_exists('section', $options) && preg_match('/^[a-z0-9-]+$/i', $options['section']) ? $options['section'] : 'asterisk');

if (!is_readable($CONFIG_FILE)) {
    die("Unable to read configuration file [".$CONFIG_FILE."]!" . PHP_EOL);
}

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

// Init database

$DB = null;

try {
    $DB = LMSDB::getInstance();
} catch (Exception $ex) {
    trigger_error($ex->getMessage(), E_USER_WARNING);
    // can't working without database
    die("Fatal error: cannot connect to database!" . PHP_EOL);
}

$sip_config_file = ConfigHelper::getConfig($config_section . '.sip_config_file', '/etc/asterisk/sip-lms.conf');
$ext_incoming_config_file = ConfigHelper::getConfig($config_section . '.ext_incoming_config_file', '/etc/asterisk/extensions-lms-incoming.conf');
$ext_outgoing_config_file = ConfigHelper::getConfig($config_section . '.ext_outgoing_config_file', '/etc/asterisk/extensions-lms-outgoing.conf');

// Include required files (including sequence is important)

require_once(LIB_DIR . DIRECTORY_SEPARATOR . 'common.php');
require_once(LIB_DIR . DIRECTORY_SEPARATOR . 'language.php');
include_once(LIB_DIR . DIRECTORY_SEPARATOR . 'definitions.php');
require_once(LIB_DIR . DIRECTORY_SEPARATOR . 'unstrip.php');

$records = $DB->GetAll("SELECT * FROM voip_emergency_numbers");
$boroughs = array();
if (!empty($records)) {
    foreach ($records as $record) {
        extract($record);
        if (!isset($boroughs[$location_borough])) {
            $boroughs[$location_borough] = array();
        }
        if (!isset($boroughs[$location_borough][$number])) {
            $boroughs[$location_borough][$number] = array();
        }
        $boroughs[$location_borough][$number] = $record;
    }
}

$accounts = $DB->GetAll("SELECT a.login, a.passwd, " . $DB->GroupConcat("n.phone") . " AS phones, a.flags, lc.boroughid
	FROM voipaccounts a
	LEFT JOIN vaddresses va ON va.id = a.address_id
	LEFT JOIN (
		SELECT voip_account_id, phone FROM voip_numbers ORDER BY voip_account_id, number_index
	) n ON n.voip_account_id = a.id
	LEFT JOIN location_cities lc ON lc.id = location_city
	GROUP BY a.login, a.passwd, a.flags, lc.boroughid");

$fhs = fopen($sip_config_file, "w+");
if (empty($fhs)) {
    die("Couldn't create file " . $sip_config_file . "!" . PHP_EOL);
}
$fhei = fopen($ext_incoming_config_file, "w+");
if (empty($fhei)) {
    die("Couldn't create file " . $ext_incoming_config_file . "!" . PHP_EOL);
}
$fheo = fopen($ext_outgoing_config_file, "w+");
if (empty($fheo)) {
    die("Couldn't create file " . $ext_outgoing_config_file . "!" . PHP_EOL);
}

if (!empty($accounts)) {
    fprintf($fhei, "[incoming-lms]\n");
    fprintf($fheo, "[outgoing-lms]\n");

    foreach ($accounts as $account) {
        extract($account);

        $phones = explode(',', $phones);

        foreach ($phones as $phone) {
            fprintf($fheo, "exten => _%s,1,Goto(incoming,%s,1)\n", $phone, $phone);
        }

        $phone = reset($phones);
        fprintf(
            $fhs,
            "[%s]\nmd5secret=%s\ncontext=outgoing-lms-%s\nqualify=yes\ntype=friend\ninsecure=no\nhost=dynamic\nnat=no\ndirectmedia=no\n\n",
            $login,
            md5($login . ':asterisk:' . $passwd),
            $phone
        );

        foreach ($phones as $phone) {
            $prio = 1;
            fprintf($fhei, "\nexten => _%s,%d,Set(CDR(exten)=\${EXTEN})\n", $phone, $prio++);
            fprintf($fhei, "exten => _%s,%d,Set(CDR(accountcode)=\${CALLERID(num)})\n", $phone, $prio++);
            if ($flags & (CALL_FLAG_ADMIN_RECORDING | CALL_FLAG_CUSTOMER_RECORDING)) {
                fprintf($fhei, "exten => _%s,%d,Monitor(wav,\${CDR(uniqueid)},mb)\n", $phone, $prio++);
            }
            fprintf($fhei, "exten => _%s,%d,Dial(SIP/%s,30)\n", $phone, $prio++, $login);
            fprintf($fhei, "exten => _%s,%d,Hangup()\n", $phone, $prio++);
        }
    }

    fprintf($fheo, "\nexten => _X.,1,Goto(outgoing,\${EXTEN},2)\n");

    foreach ($accounts as $account) {
        extract($account);

        $phones = explode(',', $phones);

        foreach ($phones as $phone) {
            fprintf($fheo, "\n[outgoing-lms-%s]\n", $phone);
            $prio = 1;
            fprintf($fheo, "exten => _+.,%d,Goto(outgoing-lms-%s,\${EXTEN:1},1)\n", $prio++, $phone);
            $prio = 1;
            if ($flags & (CALL_FLAG_ADMIN_RECORDING | CALL_FLAG_CUSTOMER_RECORDING)) {
                fprintf($fheo, "exten => _X.,%d,Monitor(wav,\${CDR(uniqueid)},mb)\n", $prio++);
            }
            fprintf($fheo, "exten => _X.,%d,Set(CDR(accountcode)=%s)\n", $prio++, $phone);
            fprintf($fheo, "exten => _X.,%d,Goto(outgoing,\${EXTEN},1)\n", $prio++);

            if (isset($boroughs[$boroughid])) {
                foreach ($boroughs[$boroughid] as $number => $record) {
                    $prio = 1;
                    if ($flags & (CALL_FLAG_ADMIN_RECORDING | CALL_FLAG_CUSTOMER_RECORDING)) {
                        fprintf(
                            $fheo,
                            "exten => _%s,%d,Monitor(wav,\${CDR(uniqueid)},mb)\n",
                            $record['number'],
                            $prio++,
                            $record['fullnumber']
                        );
                    }
                    fprintf($fheo, "exten => _%s,%d,Goto(outgoing,%s,2)\n", $record['number'], $prio++, $record['fullnumber']);
                }
            }
        }
    }
}

fclose($fhs);
fclose($fhei);
fclose($fheo);

?>
