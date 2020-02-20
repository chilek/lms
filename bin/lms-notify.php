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

ini_set('error_reporting', E_ALL & ~E_NOTICE);

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
    'a:' => 'actions:',
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
lms-notify.php
(C) 2001-2017 LMS Developers

EOF;
    exit(0);
}

if (array_key_exists('help', $options)) {
    print <<<EOF
lms-notify.php
(C) 2001-2017 LMS Developers

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
-a, --actions=<node-access,customer-status,assignment-invoice,all-assignment-suspension>
                                action names which should be performed for
                                virtual block/unblock channels

EOF;
    exit(0);
}

$quiet = array_key_exists('quiet', $options);
if (!$quiet) {
    print <<<EOF
lms-notify.php
(C) 2001-2017 LMS Developers

EOF;
}

$debug = array_key_exists('debug', $options);
$fakedate = (array_key_exists('fakedate', $options) ? $options['fakedate'] : null);

$types = array();
if (array_key_exists('type', $options)) {
    $types = explode(',', $options['type']);
}

$channels = array();
if (array_key_exists('channel', $options)) {
    $channels = explode(',', $options['channel']);
}
if (empty($channels)) {
    $channels[] = 'mail';
}

$actions = array();
if (isset($options['actions'])) {
    $actions = explode(',', $options['actions']);
} else {
    $actions = array('node-access', 'customer-status', 'assignment-invoice');
}

$current_month = intval(strftime('%m'));
$current_year = intval(strftime('%Y'));

$config_section = (array_key_exists('section', $options) && preg_match('/^[a-z0-9-_]+$/i', $options['section'])
    ? $options['section'] : 'notify');

$timeoffset = date('Z');

function localtime2()
{
    global $fakedate, $timeoffset;
    if (!empty($fakedate)) {
        $date = explode("/", $fakedate);
        return mktime(0, 0, 0, intval($date[1]), intval($date[2]), intval($date[0])) + $timeoffset;
    } else {
        return time();
    }
}

if (array_key_exists('config-file', $options)) {
    $CONFIG_FILE = $options['config-file'];
} else {
    $CONFIG_FILE = DIRECTORY_SEPARATOR . 'etc' . DIRECTORY_SEPARATOR . 'lms' . DIRECTORY_SEPARATOR . 'lms.ini';
}

if (!$quiet) {
    echo "Using file " . $CONFIG_FILE . " as config." . PHP_EOL;
}

if (!is_readable($CONFIG_FILE)) {
    die("Unable to read configuration file [" . $CONFIG_FILE . "]!" . PHP_EOL);
}

define('CONFIG_FILE', $CONFIG_FILE);

$CONFIG = (array)parse_ini_file($CONFIG_FILE, true);

// Check for configuration vars and set default values
$CONFIG['directories']['sys_dir'] = (!isset($CONFIG['directories']['sys_dir']) ? getcwd() : $CONFIG['directories']['sys_dir']);
$CONFIG['directories']['lib_dir'] = (!isset($CONFIG['directories']['lib_dir']) ? $CONFIG['directories']['sys_dir'] . DIRECTORY_SEPARATOR . 'lib' : $CONFIG['directories']['lib_dir']);
$CONFIG['directories']['plugin_dir'] = (!isset($CONFIG['directories']['plugin_dir']) ? $CONFIG['directories']['sys_dir'] . DIRECTORY_SEPARATOR . 'plugins' : $CONFIG['directories']['plugin_dir']);
$CONFIG['directories']['plugins_dir'] = $CONFIG['directories']['plugin_dir'];

define('SYS_DIR', $CONFIG['directories']['sys_dir']);
define('LIB_DIR', $CONFIG['directories']['lib_dir']);
define('PLUGIN_DIR', $CONFIG['directories']['plugin_dir']);
define('PLUGINS_DIR', $CONFIG['directories']['plugin_dir']);

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

// Include required files (including sequence is important)

require_once(LIB_DIR . DIRECTORY_SEPARATOR . 'common.php');
require_once(LIB_DIR . DIRECTORY_SEPARATOR . 'language.php');
require_once(LIB_DIR . DIRECTORY_SEPARATOR . 'definitions.php');

// now it's time for script settings
$smtp_options = array(
    'host' => ConfigHelper::getConfig($config_section . '.smtp_host'),
    'port' => ConfigHelper::getConfig($config_section . '.smtp_port'),
    'user' => ConfigHelper::getConfig($config_section . '.smtp_username', ConfigHelper::getConfig($config_section . '.smtp_user')),
    'pass' => ConfigHelper::getConfig($config_section . '.smtp_password', ConfigHelper::getConfig($config_section . '.smtp_pass')),
    'auth' => ConfigHelper::getConfig($config_section . '.smtp_auth_type', ConfigHelper::getConfig($config_section . '.smtp_auth')),
    'ssl_verify_peer' => ConfigHelper::checkValue(ConfigHelper::getConfig($config_section . '.smtp_ssl_verify_peer', true)),
    'ssl_verify_peer_name' => ConfigHelper::checkValue(ConfigHelper::getConfig($config_section . '.smtp_ssl_verify_peer_name', true)),
    'ssl_allow_self_signed' => ConfigHelper::checkConfig($config_section . '.smtp_ssl_allow_self_signed'),
);

$suspension_percentage = floatval(ConfigHelper::getConfig('finances.suspension_percentage', 0));
$debug_email = ConfigHelper::getConfig($config_section . '.debug_email', '', true);
$mail_from = ConfigHelper::getConfig($config_section . '.mailfrom', '', true);
$mail_fname = ConfigHelper::getConfig($config_section . '.mailfname', '', true);
$notify_email = ConfigHelper::getConfig($config_section . '.notify_email', '', true);
$reply_email = ConfigHelper::getConfig($config_section . '.reply_email', '', true);
$dsn_email = ConfigHelper::getConfig($config_section . '.dsn_email', '', true);
$mdn_email = ConfigHelper::getConfig($config_section . '.mdn_email', '', true);
$format = ConfigHelper::getConfig($config_section . '.format', 'text');
$mail_format = ConfigHelper::getConfig($config_section . '.mail_format', $format);
$content_type = $format == 'html' ? 'text/html' : 'text/plain';
$mail_content_type = $mail_format == 'html' ? 'text/html' : 'text/plain';

$content_types = array(
    MSG_MAIL => $mail_content_type,
    MSG_SMS => 'text/plain',
    MSG_USERPANEL => $content_type,
    MSG_USERPANEL_URGENT => $content_type,
    MSG_WWW => $content_type,
);

$debug_phone = ConfigHelper::getConfig($config_section . '.debug_phone', '', true);
$script_service = ConfigHelper::getConfig($config_section . '.service', '', true);
if ($script_service) {
    LMSConfig::getConfig()->getSection('sms')->addVariable(new ConfigVariable('service', $script_service));
}

// documents - contracts (or annexes) which expire some day before notify
// contracts - contracts which customer assignment max(dateto) is some day before notify
// debtors - debtors notify
// reminder - reminder notify
// income - income notify
// invoices - new invoice notify
// notes - new debit note notify
// warnings - send message to customers with warning flag set for node
// messages - send message to customers which have awaiting www messages
// timetable - send event notify to users
$notifications = array();
foreach (array(
             'documents',
             'contracts',
             'debtors',
             'reminder',
             'income',
             'invoices',
             'notes',
             'warnings',
             'messages',
             'timetable'
         ) as $type) {
    $notifications[$type] = array();
    $notifications[$type]['limit'] = intval(ConfigHelper::getConfig($config_section . '.' . $type . '_limit', 0));
    $notifications[$type]['message'] = ConfigHelper::getConfig($config_section . '.' . $type . '_message', $type . ' notification');
    $notifications[$type]['subject'] = ConfigHelper::getConfig($config_section . '.' . $type . '_subject', $type . ' notification');
    $notifications[$type]['days'] = intval(ConfigHelper::getConfig($config_section . '.' . $type . '_days', 0));
    $notifications[$type]['file'] = ConfigHelper::getConfig($config_section . '.' . $type . '_file', '/etc/rc.d/' . $type . '.sh');
    $notifications[$type]['header'] = ConfigHelper::getConfig($config_section . '.' . $type . '_header', "#!/bin/bash\n\nipset flush $type\n");
    $notifications[$type]['rule'] = ConfigHelper::getConfig($config_section . '.' . $type . '_rule', "ipset add $type %i\n");
    $notifications[$type]['footer'] = ConfigHelper::getConfig($config_section . '.' . $type . '_footer', '', true);
    $notifications[$type]['deleted_customers'] = ConfigHelper::checkConfig($config_section . '.' . $type . '_deleted_customers', true);
}

if (in_array('mail', $channels) && empty($mail_from)) {
    die("Fatal error: mailfrom unset! Can't continue, exiting." . PHP_EOL);
}

if (!empty($auth) && !preg_match('/^LOGIN|PLAIN|CRAM-MD5|NTLM$/i', $auth)) {
    die("Fatal error: smtp_auth setting not supported! Can't continue, exiting." . PHP_EOL);
}

//$currtime = localtime2() + $timeoffset;
$currtime = localtime2();
$daystart = intval($currtime / 86400) * 86400 - $timeoffset;
//$daystart = intval($currtime / 86400) * 86400;
$dayend = $daystart + 86399;

$deadline = ConfigHelper::getConfig('payments.deadline', ConfigHelper::getConfig('invoices.paytime', 0));

// Include required files (including sequence is important)
require_once(LIB_DIR . DIRECTORY_SEPARATOR . 'common.php');
require_once(LIB_DIR . DIRECTORY_SEPARATOR . 'language.php');
include_once(LIB_DIR . DIRECTORY_SEPARATOR . 'definitions.php');

$SYSLOG = SYSLOG::getInstance();

// Initialize Session, Auth and LMS classes

$AUTH = null;
$LMS = new LMS($DB, $AUTH, $SYSLOG);
$LMS->ui_lang = $_ui_language;
LMS::$currency = $_currency;

$plugin_manager = new LMSPluginManager();
$LMS->setPluginManager($plugin_manager);
$plugin_manager->executeHook('lms_initialized', $LMS);

// Load plugin files and register hook callbacks
$plugins = $plugin_manager->getAllPluginInfo(LMSPluginManager::OLD_STYLE);
if (!empty($plugins)) {
    foreach ($plugins as $plugin_name => $plugin) {
        if ($plugin['enabled']) {
            require(LIB_DIR . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . $plugin_name . '.php');
        }
    }
}

if (!empty($mail_fname)) {
    $mail_from = qp_encode($mail_fname) . ' <' . $mail_from . '>';
}

$sms_options = $LMS->getCustomerSMSOptions();

//include(LIB_DIR . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . 'mtsms.php');

function parse_customer_data($data, $row)
{
    global $LMS;
    $DB = LMSDB::getInstance();

    $amount = -$row['balance'];
    $totalamount = -$row['totalbalance'];
    $hook_data = $LMS->executeHook('notify_parse_customer_data', array('data' => $data, 'customer' => $row));
    $data = $hook_data['data'];
    $data = preg_replace(
        "/\%bankaccount/",
        format_bankaccount(bankaccount($row['id'], $row['account'])),
        $data
    );
    $data = preg_replace("/\%b/", $amount, $data);
    $data = preg_replace("/\%totalb/", $totalamount, $data);
    $data = preg_replace("/\%date-y/", strftime("%Y"), $data);
    $data = preg_replace("/\%date-m/", strftime("%m"), $data);
    $data = preg_replace("/\%date_month_name/", strftime("%B"), $data);
    $deadline = $row['cdate'] + $row['paytime'] * 86400;
    $data = preg_replace("/\%deadline-y/", strftime("%Y", $deadline), $data);
    $data = preg_replace("/\%deadline-m/", strftime("%m", $deadline), $data);
    $data = preg_replace("/\%deadline-d/", strftime("%d", $deadline), $data);
    $data = preg_replace("/\%B/", $row['balance'], $data);
    $data = preg_replace("/\%totalB/", $row['totalbalance'], $data);
    $data = preg_replace("/\%saldo/", moneyf($row['balance']), $data);
    $data = preg_replace("/\%totalsaldo/", moneyf($row['totalbalance']), $data);
    $data = preg_replace("/\%pin/", $row['pin'], $data);
    $data = preg_replace("/\%cid/", $row['id'], $data);
    if (preg_match("/\%abonament/", $data)) {
        $assignments = $DB->GetAll(
            'SELECT ROUND(((((100 - a.pdiscount) * (CASE WHEN a.liabilityid IS NULL THEN t.value ELSE l.value END)) / 100) - a.vdiscount) *
			    (CASE a.suspended WHEN 0
				    THEN 1.0
				    ELSE ? / 100
			    END), 2) AS value, t.currency
            FROM assignments a
            LEFT JOIN tariffs t ON t.id = a.tariffid
            LEFT JOIN liabilities l ON l.id = a.liabilityid
            WHERE customerid = ? AND (t.id IS NOT NULL OR l.id IS NOT NULL)
                AND a.datefrom <= ? AND (a.dateto > ? OR a.dateto = 0)
                AND NOT EXISTS (
                    SELECT COUNT(id) FROM assignments
                    WHERE customerid = c.id AND tariffid IS NULL AND liabilityid IS NULL
                        AND datefrom <= ? AND (dateto > ? OR dateto = 0                
                )
            GROUP BY tariffs.currency',
            array(
                $row['id'],
                $GLOBALS['suspension_percentage'],
                $GLOBALS['currtime'],
                $GLOBALS['currtime'],
                $GLOBALS['currtime'],
                $GLOBALS['currtime'],
            )
        );
        $saldo = array();
        if (!empty($assignments)) {
            foreach ($assignments as $assignment) {
                $saldo[] = moneyf($assignment['value'], $assignment['currency']);
            }
        }
        $data = preg_replace("/\%abonament/", empty($saldo) ? '0' : implode(', ', $saldo), $data);
    }

    if (preg_match('/%last_(?<number>[0-9]+)_in_a_table/', $data, $m)) {
        $lastN = $LMS->GetCustomerShortBalanceList($row['id'], $m['number']);
        if (empty($lastN)) {
            $lN = '';
        } else {
            // ok, now we are going to rise up system's load
            $lN = '-----------+--------------+--------------+--------------+----------------------------------------------------<eol>';
            foreach ($lastN as $row_s) {
                $op_time = strftime("%Y/%m/%d ", $row_s['time']);
                if ($row_s['value'] < 0) {
                    $op_liability = sprintf("%9.2f %s ", $row_s['value'], $row_s['currency']);
                    $op_payment = '              ';
                } else {
                    $op_liability = '              ';
                    $op_payment = sprintf("%9.2f %s ", $row_s['value'], $row_s['currency']);
                }
                $op_after = sprintf("%9.2f %s ", $row_s['after'], LMS::$currency);
                $for_what = sprintf(" %-52s", $row_s['comment']);
                $lN .= $op_time . '|' . $op_liability . '|' . $op_payment . '|' . $op_after . '|' . $for_what . '<eol>';
            }
            $lN .= '-----------+--------------+--------------+--------------+----------------------------------------------------<eol>';
        }
        $data = preg_replace('/%last_[0-9]+_in_a_table/', $lN, $data);
    }

    // invoices, debit notes
    $data = preg_replace("/\%invoice/", $row['doc_number'], $data);
    $data = preg_replace("/\%number/", $row['doc_number'], $data);
    $data = preg_replace("/\%value/", moneyf($row['value'], $row['currency']), $data);
    $data = preg_replace("/\%cdate-y/", strftime("%Y", $row['cdate']), $data);
    $data = preg_replace("/\%cdate-m/", strftime("%m", $row['cdate']), $data);
    $data = preg_replace("/\%cdate-d/", strftime("%d", $row['cdate']), $data);

    list ($now_y, $now_m) = explode('/', strftime("%Y/%m", time()));
    $data = preg_replace("/\%lastday/", strftime("%d", mktime(12, 0, 0, $now_m + 1, 0, $now_y)), $data);

    return $data;
}

function parse_node_data($data, $row)
{
    $data = preg_replace("/\%i/", $row['ip'], $data);
    //$data = preg_replace("/\%nas/", $row['nasip'], $data);

    return $data;
}

function create_message($type, $subject, $template)
{
    global $content_types;

    $DB = LMSDB::getInstance();

    $DB->Execute(
        "INSERT INTO messages (type, cdate, subject, body, contenttype)
        VALUES (?, ?NOW?, ?, ?, ?)",
        array(
            $type,
            str_replace('<eol>', $content_types[$type] == 'text/html' ? '<br>' : "\n", $subject),
            str_replace('<eol>', $content_types[$type] == 'text/html' ? '<br>' : "\n", $template),
            $content_types[$type]
        )
    );
    return $DB->GetLastInsertID('messages');
}

function send_mail($msgid, $cid, $rmail, $rname, $subject, $body)
{
    global $LMS, $mail_from, $notify_email, $reply_email, $dsn_email, $mdn_email, $content_types;
    global $smtp_options;

    $DB = LMSDB::getInstance();

    $DB->Execute(
        "INSERT INTO messageitems
        (messageid, customerid, destination, status)
        VALUES (?, ?, ?, ?)",
        array($msgid, $cid, $rmail, 1)
    );
    $msgitemid = $DB->GetLastInsertID('messageitems');

    $subject = str_replace('<eol>', $content_types[MSG_MAIL] == 'text/html' ? '<br>' : "\n", $subject);
    $body = str_replace('<eol>', $content_types[MSG_MAIL] == 'text/html' ? '<br>' : "\n", $body);

    $headers = array(
        'From' => empty($dsn_email) ? $mail_from : $dsn_email,
        'To' => qp_encode($rname) . " <$rmail>",
        'Subject' => $subject,
        'Reply-To' => empty($reply_email) ? $mail_from : $reply_email,
    );

    if ($content_types[MSG_MAIL] == 'text/html') {
        $headers['X-LMS-Format'] = 'html';
    }

    if (!empty($mdn_email)) {
        $headers['Return-Receipt-To'] = $mdn_email;
        $headers['Disposition-Notification-To'] = $mdn_email;
    }

    if (!empty($notify_email)) {
        $headers['Cc'] = $notify_email;
    }

    if (!empty($dsn_email) || !empty($mdn_email)) {
        if (!empty($dsn_email)) {
            $headers['Delivery-Status-Notification-To'] = true;
        }
        $headers['X-LMS-Message-Item-Id'] = $msgitemid;
        $headers['Message-ID'] = '<messageitem-' . $msgitemid . '@rtsystem.' . gethostname() . '>';
    }

    $result = $LMS->SendMail($rmail, $headers, $body, null, $smtp_options);

    $query = "UPDATE messageitems
        SET status = ?, lastdate = ?NOW?, error = ?
        WHERE messageid = ? AND customerid = ? AND id = ?";

    if (is_string($result)) {
        $DB->Execute($query, array(3, $result, $msgid, $cid, $msgitemid));
    } else { // MSG_SENT
        $DB->Execute($query, array($result, null, $msgid, $cid, $msgitemid));
    }
}

function send_sms($msgid, $cid, $phone, $data)
{
    global $LMS, $sms_options;

    $DB = LMSDB::getInstance();

    $DB->Execute(
        "INSERT INTO messageitems
        (messageid, customerid, destination, status)
        VALUES (?, ?, ?, ?)",
        array($msgid, $cid, $phone, 1)
    );
    $msgitemid = $DB->GetLastInsertID('messageitems');

    $result = $LMS->SendSMS(str_replace(' ', '', $phone), str_replace('<eol>', "\n", $data), $msgitemid, $sms_options);
    $query = "UPDATE messageitems
        SET status = ?, lastdate = ?NOW?, error = ?
        WHERE messageid = ? AND customerid = ? AND id = ?";

    if (preg_match("/[^0-9]/", $result)) {
        $DB->Execute($query, array(3, $result, $msgid, $cid, $msgitemid));
    } elseif ($result == 2) { // MSG_SENT
        $DB->Execute($query, array($result, null, $msgid, $cid, $msgitemid));
    }
}

function send_to_userpanel($msgid, $cid, $destination)
{
    $DB = LMSDB::getInstance();

    $DB->Execute(
        "INSERT INTO messageitems
        (messageid, customerid, destination, status)
        VALUES (?, ?, ?, ?)",
        array($msgid, $cid, $destination, MSG_SENT)
    );
}

function send_mail_to_user($rmail, $rname, $subject, $body)
{
    global $LMS, $mail_from, $notify_email;
    global $smtp_options;

    $headers = array(
        'From' => $mail_from,
        'To' => qp_encode($rname) . " <$rmail>",
        'Subject' => $subject
    );
    if (!empty($notify_email)) {
        $headers['Cc'] = $notify_email;
    }
    $result = $LMS->SendMail($rmail, $headers, $body, null, $smtp_options);
}

function send_sms_to_user($phone, $data)
{
    global $LMS;

    $result = $LMS->SendSMS(str_replace(' ', '', $phone), $data);
}

// ------------------------------------------------------------------------
// ACTIONS
// ------------------------------------------------------------------------

// timetable
if (empty($types) || in_array('timetable', $types)) {
    $days = $notifications['timetable']['days'];
    $users = $DB->GetAll(
        "SELECT id, name, (CASE WHEN ntype & ? > 0 THEN email ELSE '' END) AS email,
            (CASE WHEN ntype & ? > 0 THEN phone ELSE '' END) AS phone FROM vusers
        WHERE deleted = 0 AND access = 1 AND ntype & ? > 0 AND (email <> '' OR phone <> '')",
        array(MSG_MAIL, MSG_SMS, (MSG_MAIL | MSG_SMS))
    );
    $date = mktime(0, 0, 0);
    $subject = $notifications['timetable']['subject'];
    $today = date("Y/m/d");
    foreach ($users as $user) {
        if (empty($user['email']) && empty($user['phone'])) {
            continue;
        }

        $contents = '';
        $events = $DB->GetAll("SELECT DISTINCT title, description, begintime, endtime,
            customerid, UPPER(lastname) AS lastname, c.name AS name, address
            FROM events
            LEFT JOIN customeraddressview c ON (c.id = customerid)
            LEFT JOIN eventassignments ON (events.id = eventassignments.eventid)
            WHERE date=? AND
            ((private=1 AND (events.userid=? OR eventassignments.userid=?)) OR
            (private=0 AND eventassignments.userid=?) OR
            (private=0 AND eventassignments.userid IS NULL))
            ORDER BY begintime", array($date, $user['id'], $user['id'], $user['id']));

        if (!empty($events)) {
            $mail_contents = '';
            $sms_contents = '';
            foreach ($events as $event) {
                $begintime = sprintf("%02d:%02d", floor($event['begintime'] / 100), $event['begintime'] % 100);
                $mail_contents .= trans("Timetable for today") . ': ' . $today . PHP_EOL;
                $sms_contents .= trans("Timetable for today") . ': ' . $today . ', ';
                $mail_contents .= "----------------------------------------------------------------------------" . PHP_EOL;
                $mail_contents .= trans("Time:") . "\t" . $begintime;
                $sms_contents .= trans("Time:") . " " . $begintime;
                if ($event['endtime'] != 0 && $event['begintime'] != $event['endtime']) {
                    $endtime = sprintf("%02d:%02d", floor($event['endtime'] / 100), $event['endtime'] % 100);
                    $mail_contents .= ' - ' . $endtime;
                    $sms_contents .= ' - ' . $endtime;
                }
                $mail_contents .= PHP_EOL;
                $sms_contents .= ': ';
                $mail_contents .= trans('Title:') . "\t" . $event['title'] . PHP_EOL;
                $sms_contents .= $event['title'];
                $mail_contents .= trans('Description:') . "\t" . $event['description'] . PHP_EOL;
                $sms_contents .= ' (' . $event['description'] . ')';
                if ($event['customerid']) {
                    $mail_contents .= trans('Customer:') . "\t" . $event['lastname'] . " " . $event['name']
                        . ", " . $event['address'] . PHP_EOL;
                    $sms_contents .= trans('Customer:') . ' ' . $event['lastname'] . " " . $event['name']
                        . ", " . $event['address'];
                    $contacts = $DB->GetCol(
                        "SELECT contact FROM customercontacts
                        WHERE customerid = ? AND (type & ?) = 0 AND (type & ?) > 0",
                        array($event['customerid'], CONTACT_DISABLED, (CONTACT_MOBILE | CONTACT_FAX | CONTACT_LANDLINE))
                    );
                    if (!empty($contacts)) {
                        $mail_contents .= trans('customer contacts: ') . PHP_EOL . implode(', ', $contacts) . PHP_EOL;
                        $sms_contents .= ' - ' . implode(', ', $contacts);
                    }
                }
                $mail_contents .= "----------------------------------------------------------------------------" . PHP_EOL;
                $sms_contents .= ' ';
            }

            if (!empty($user['email'])) {
                $recipient_name = $row['lastname'] . ' ' . $row['name'];
                $recipient_mails = ($debug_email
                    ? explode(',', $debug_email) : (
                    !empty($user['email']) ? explode(',', trim($user['email'])) : null)
                );
                if (!$quiet) {
                    printf("[timetable/mail] %s (%04d): %s" . PHP_EOL, $user['name'], $user['id'], $user['email']);
                }
                if (!$debug) {
                    send_mail_to_user($user['email'], $user['name'], $subject, $mail_contents);
                }
            }
            if (!empty($user['sms'])) {
                if (!$quiet) {
                    printf("[timetable/sms] %s (%04d): %s" . PHP_EOL, $user['name'], $user['id'], $user['phone']);
                }
                if (!$debug) {
                    send_sms_to_user($user['phone'], $sms_contents);
                }
            }
        }
    }
}

// documents
if (empty($types) || in_array('documents', $types)) {
    $days = $notifications['documents']['days'];
    $customers = $DB->GetAll(
        "SELECT DISTINCT c.id, c.pin, c.lastname, c.name,
            b.balance, m.email, x.phone
        FROM customers c
        LEFT JOIN (
            SELECT customerid, SUM(value * currencyvalue) AS balance FROM cash
            GROUP BY customerid
        ) b ON b.customerid = c.id
        JOIN documents d ON d.customerid = c.id
        JOIN documentcontents dc ON dc.docid = d.id
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
        WHERE d.type IN (?, ?) AND dc.todate >= $daystart + ? * 86400
            AND dc.todate < $daystart + (? + 1) * 86400"
            . ($notifications['documents']['deleted_customers'] ? '' : ' AND c.deleted = 0'),
        array(
            CONTACT_EMAIL | CONTACT_NOTIFICATIONS | CONTACT_DISABLED,
            CONTACT_EMAIL | CONTACT_NOTIFICATIONS,
            CONTACT_MOBILE | CONTACT_NOTIFICATIONS | CONTACT_DISABLED,
            CONTACT_MOBILE | CONTACT_NOTIFICATIONS,
            DOC_CONTRACT,
            DOC_ANNEX,
            $days,
            $days
        )
    );

    if (!empty($customers)) {
        $notifications['documents']['customers'] = array();
        foreach ($customers as $row) {
            $notifications['documents']['customers'][] = $row['id'];
            $message = parse_customer_data($notifications['documents']['message'], $row);
            $subject = parse_customer_data($notifications['documents']['subject'], $row);

            $recipient_name = $row['lastname'] . ' ' . $row['name'];
            $recipient_mails = ($debug_email ? explode(',', $debug_email) :
                (!empty($row['email']) ? explode(',', trim($row['email'])) : null));
            $recipient_phones = ($debug_phone ? explode(',', $debug_phone) :
                (!empty($row['phone']) ? explode(',', trim($row['phone'])) : null));

            if (!$quiet) {
                if (in_array('mail', $channels) && !empty($recipient_mails)) {
                    foreach ($recipient_mails as $recipient_mail) {
                        printf(
                            "[mail/documents] %s (%04d): %s" . PHP_EOL,
                            $recipient_name,
                            $row['id'],
                            $recipient_mail
                        );
                    }
                }
                if (in_array('sms', $channels) && !empty($recipient_phones)) {
                    foreach ($recipient_phones as $recipient_phone) {
                        printf(
                            "[sms/documents] %s (%04d): %s" . PHP_EOL,
                            $recipient_name,
                            $row['id'],
                            $recipient_phone
                        );
                    }
                }
                if (in_array('userpanel', $channels)) {
                    printf(
                        "[userpanel/documents] %s (%04d)" . PHP_EOL,
                        $recipient_name,
                        $row['id']
                    );
                }
                if (in_array('userpanel-urgent', $channels)) {
                    printf(
                        "[userpanel-urgent/documents] %s (%04d)" . PHP_EOL,
                        $recipient_name,
                        $row['id']
                    );
                }
            }

            if (!$debug) {
                if (in_array('mail', $channels) && !empty($recipient_mails)) {
                    $msgid = create_message(MSG_MAIL, $subject, $message);
                    foreach ($recipient_mails as $recipient_mail) {
                        send_mail(
                            $msgid,
                            $row['id'],
                            $recipient_mail,
                            $recipient_name,
                            $subject,
                            $message
                        );
                    }
                }
                if (in_array('sms', $channels) && !empty($recipient_phones)) {
                    $msgid = create_message(MSG_SMS, $subject, $message);
                    foreach ($recipient_phones as $recipient_phone) {
                        send_sms($msgid, $row['id'], $recipient_phone, $message);
                    }
                }
                if (in_array('userpanel', $channels)) {
                    $msgid = create_message(MSG_USERPANEL, $subject, $message);
                    send_to_userpanel($msgid, $row['id'], trans('userpanel'));
                }
                if (in_array('userpanel-urgent', $channels)) {
                    $msgid = create_message(MSG_USERPANEL_URGENT, $subject, $message);
                    send_to_userpanel($msgid, $row['id'], trans('userpanel urgent'));
                }
            }
        }
    }
}

// contracts
if (empty($types) || in_array('contracts', $types)) {
    $expiration_type = ConfigHelper::getConfig($config_section . '.expiration_type', 'assignments');
    $days = $notifications['contracts']['days'];
    $customers = $DB->GetAll(
        "SELECT c.id, c.pin, c.lastname, c.name,
            SUM(value * currencyvalue) AS balance, d.dateto AS cdate,
            m.email, x.phone
        FROM customers c
        JOIN cash ON (c.id = cash.customerid) "
        . ($expiration_type == 'assignments' ?
            "JOIN (
                SELECT MAX(a.dateto) AS dateto, a.customerid
                FROM assignments a
                WHERE a.dateto > 0
                GROUP BY a.customerid
                HAVING MAX(a.dateto) >= $daystart + $days * 86400 AND MAX(a.dateto) < $daystart + ($days + 1) * 86400
            ) d ON d.customerid = c.id" :
            "JOIN (
                SELECT DISTINCT customerid, 0 AS dateto FROM documents
                JOIN documentcontents dc ON dc.docid = documents.id
                WHERE dc.todate >= $daystart + $days * 86400 AND dc.todate < $daystart + ($days + 1) * 86400
                    AND documents.type IN (" . DOC_CONTRACT . ',' . DOC_ANNEX . ")
            ) d ON d.customerid = c.id") . "
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
        WHERE d.dateto >= $daystart + ? * 86400 AND d.dateto < $daystart + (? + 1) * 86400"
            . ($notifications['contracts']['deleted_customers'] ? '' : ' AND c.deleted = 0') . "
        GROUP BY c.id, c.pin, c.lastname, c.name, d.dateto, m.email, x.phone",
        array(
            CONTACT_EMAIL | CONTACT_NOTIFICATIONS | CONTACT_DISABLED,
            CONTACT_EMAIL | CONTACT_NOTIFICATIONS,
            CONTACT_MOBILE | CONTACT_NOTIFICATIONS | CONTACT_DISABLED,
            CONTACT_MOBILE | CONTACT_NOTIFICATIONS,
            $days,
            $days
        )
    );

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
                if (in_array('mail', $channels) && !empty($recipient_mails)) {
                    foreach ($recipient_mails as $recipient_mail) {
                        printf(
                            "[mail/contracts] %s (%04d): %s" . PHP_EOL,
                            $recipient_name,
                            $row['id'],
                            $recipient_mail
                        );
                    }
                }
                if (in_array('sms', $channels) && !empty($recipient_phones)) {
                    foreach ($recipient_phones as $recipient_phone) {
                        printf(
                            "[sms/contracts] %s (%04d): %s" . PHP_EOL,
                            $recipient_name,
                            $row['id'],
                            $recipient_phone
                        );
                    }
                }
                if (in_array('userpanel', $channels)) {
                    printf(
                        "[userpanel/contracts] %s (%04d)" . PHP_EOL,
                        $recipient_name,
                        $row['id']
                    );
                }
                if (in_array('userpanel-urgent', $channels)) {
                    printf(
                        "[userpanel-urgent/contracts] %s (%04d)" . PHP_EOL,
                        $recipient_name,
                        $row['id']
                    );
                }
            }

            if (!$debug) {
                if (in_array('mail', $channels) && !empty($recipient_mails)) {
                    $msgid = create_message(MSG_MAIL, $subject, $message);
                    foreach ($recipient_mails as $recipient_mail) {
                        send_mail(
                            $msgid,
                            $row['id'],
                            $recipient_mail,
                            $recipient_name,
                            $subject,
                            $message
                        );
                    }
                }
                if (in_array('sms', $channels) && !empty($recipient_phones)) {
                    $msgid = create_message(MSG_SMS, $subject, $message);
                    foreach ($recipient_phones as $recipient_phone) {
                        send_sms($msgid, $row['id'], $recipient_phone, $message);
                    }
                }
                if (in_array('userpanel', $channels)) {
                    $msgid = create_message(MSG_USERPANEL, $subject, $message);
                    send_to_userpanel($msgid, $row['id'], trans('userpanel'));
                }
                if (in_array('userpanel-urgent', $channels)) {
                    $msgid = create_message(MSG_USERPANEL_URGENT, $subject, $message);
                    send_to_userpanel($msgid, $row['id'], trans('userpanel urgent'));
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
    $customers = $DB->GetAll(
        "SELECT c.id, c.pin, c.lastname, c.name,
            b2.balance AS balance, b.balance AS totalbalance, m.email, x.phone, divisions.account
        FROM customers c
        LEFT JOIN divisions ON divisions.id = c.divisionid
        LEFT JOIN (
            SELECT customerid, SUM(value * currencyvalue) AS balance FROM cash GROUP BY customerid
        ) b ON b.customerid = c.id
        LEFT JOIN (
            SELECT cash.customerid, SUM(value * cash.currencyvalue) AS balance FROM cash
            LEFT JOIN customers ON customers.id = cash.customerid
            LEFT JOIN divisions ON divisions.id = customers.divisionid
            LEFT JOIN documents d ON d.id = cash.docid
            LEFT JOIN (
                SELECT SUM(value * cash.currencyvalue) AS totalvalue, docid FROM cash
                JOIN documents ON documents.id = cash.docid
                WHERE documents.type = ?
                GROUP BY docid
            ) tv ON tv.docid = cash.docid
            WHERE (cash.docid IS NULL AND ((cash.type <> 0 AND cash.time < $currtime)
                OR (cash.type = 0 AND cash.time + ((CASE customers.paytime WHEN -1 THEN
                    (CASE WHEN divisions.inv_paytime IS NULL THEN $deadline ELSE divisions.inv_paytime END) ELSE customers.paytime END) + ?) * 86400 < $currtime)))
                OR (cash.docid IS NOT NULL AND ((d.type = ? AND cash.time < $currtime)
                    OR (d.type = ? AND cash.time < $currtime AND tv.totalvalue >= 0)
                    OR (((d.type = ? AND tv.totalvalue < 0)
                        OR d.type IN (?, ?, ?)) AND d.cdate + (d.paytime + ?) * 86400 < $currtime)))
            GROUP BY cash.customerid
        ) b2 ON b2.customerid = c.id
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
        WHERE c.status <> ? AND c.cutoffstop < $currtime AND b2.balance " . ($limit > 0 ? '>' : '<') . " ?"
            . ($notifications['debtors']['deleted_customers'] ? '' : ' AND c.deleted = 0'),
        array(
            DOC_CNOTE,
            $days,
            DOC_RECEIPT,
            DOC_CNOTE,
            DOC_CNOTE,
            DOC_INVOICE,
            DOC_INVOICE_PRO,
            DOC_DNOTE,
            $days,
            CONTACT_EMAIL | CONTACT_NOTIFICATIONS | CONTACT_DISABLED,
            CONTACT_EMAIL | CONTACT_NOTIFICATIONS,
            CONTACT_MOBILE | CONTACT_NOTIFICATIONS | CONTACT_DISABLED,
            CONTACT_MOBILE | CONTACT_NOTIFICATIONS,
            CSTATUS_DISCONNECTED,
            $limit
        )
    );

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
                if (in_array('mail', $channels) && !empty($recipient_mails)) {
                    foreach ($recipient_mails as $recipient_mail) {
                        printf(
                            "[mail/debtors] %s (%04d): %s" . PHP_EOL,
                            $recipient_name,
                            $row['id'],
                            $recipient_mail
                        );
                    }
                }
                if (in_array('sms', $channels) && !empty($recipient_phones)) {
                    foreach ($recipient_phones as $recipient_phone) {
                        printf(
                            "[sms/debtors] %s (%04d): %s" . PHP_EOL,
                            $recipient_name,
                            $row['id'],
                            $recipient_phone
                        );
                    }
                }
                if (in_array('backend', $channels)) {
                    printf(
                        "[backend/debtors] %s (%04d)" . PHP_EOL,
                        $recipient_name,
                        $row['id']
                    );
                }
                if (in_array('userpanel', $channels)) {
                    printf(
                        "[userpanel/debtors] %s (%04d)" . PHP_EOL,
                        $recipient_name,
                        $row['id']
                    );
                }
                if (in_array('userpanel-urgent', $channels)) {
                    printf(
                        "[userpanel-urgent/debtors] %s (%04d)" . PHP_EOL,
                        $recipient_name,
                        $row['id']
                    );
                }
            }

            if (!$debug) {
                if (in_array('mail', $channels) && !empty($recipient_mails)) {
                    $msgid = create_message(MSG_MAIL, $subject, $message);
                    foreach ($recipient_mails as $recipient_mail) {
                        send_mail(
                            $msgid,
                            $row['id'],
                            $recipient_mail,
                            $recipient_name,
                            $subject,
                            $message
                        );
                    }
                }
                if (in_array('sms', $channels) && !empty($recipient_phones)) {
                    $msgid = create_message(MSG_SMS, $subject, $message);
                    foreach ($recipient_phones as $recipient_phone) {
                        send_sms($msgid, $row['id'], $recipient_phone, $message);
                    }
                }
                if (in_array('userpanel', $channels)) {
                    $msgid = create_message(MSG_USERPANEL, $subject, $message);
                    send_to_userpanel($msgid, $row['id'], trans('userpanel'));
                }
                if (in_array('userpanel-urgent', $channels)) {
                    $msgid = create_message(MSG_USERPANEL_URGENT, $subject, $message);
                    send_to_userpanel($msgid, $row['id'], trans('userpanel urgent'));
                }
            }
        }
    }
}

// Invoices (not payed) up to $reminder_days days before deadline (cdate + paytime)
if (empty($types) || in_array('reminder', $types)) {
    $days = $notifications['reminder']['days'];
    $limit = $notifications['reminder']['limit'];
    $documents = $DB->GetAll(
        "SELECT d.id AS docid, c.id, c.pin, d.name,
        d.number, n.template, d.cdate, d.paytime, m.email, x.phone, divisions.account,
        b2.balance AS balance, b.balance AS totalbalance, v.value, v.currency
        FROM documents d
        JOIN customers c ON (c.id = d.customerid)
        LEFT JOIN divisions ON divisions.id = c.divisionid
        LEFT JOIN (
            SELECT customerid, SUM(value * currencyvalue) AS balance FROM cash GROUP BY customerid
        ) b ON b.customerid = c.id
        LEFT JOIN (
            SELECT cash.customerid, SUM(value * cash.currencyvalue) AS balance FROM cash
            LEFT JOIN customers ON customers.id = cash.customerid
            LEFT JOIN divisions ON divisions.id = customers.divisionid
            LEFT JOIN documents d ON d.id = cash.docid
            LEFT JOIN (
                SELECT SUM(value * cash.currencyvalue) AS totalvalue, docid FROM cash
                JOIN documents ON documents.id = cash.docid
                WHERE documents.type = ?
                GROUP BY docid
            ) tv ON tv.docid = cash.docid
            WHERE (cash.docid IS NULL AND ((cash.type <> 0 AND cash.time < $currtime)
                OR (cash.type = 0 AND cash.time + (CASE customers.paytime WHEN -1 THEN
                    (CASE WHEN divisions.inv_paytime IS NULL THEN $deadline ELSE divisions.inv_paytime END) ELSE customers.paytime END) * 86400 < $currtime)))
                OR (cash.docid IS NOT NULL AND ((d.type = ? AND cash.time < $currtime)
                    OR (d.type = ? AND cash.time < $currtime AND tv.totalvalue >= 0)
                    OR (((d.type = ? AND tv.totalvalue < 0)
                        OR d.type IN (?, ?, ?)) AND (" . ($days > 0 ? 'cash.docid = d.id OR ' : '') . "d.cdate + (d.paytime - ?) * 86400 < $currtime))))
            GROUP BY cash.customerid
        ) b2 ON b2.customerid = c.id
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
            SELECT SUM(value) * -1 AS value, currency, docid
            FROM cash
            GROUP BY docid, currency
        ) v ON (v.docid = d.id)
        LEFT JOIN numberplans n ON (d.numberplanid = n.id)
        WHERE d.type IN (?, ?, ?) AND d.closed = 0 AND b2.balance < ?
            AND ((d.cdate / 86400) + d.paytime - ? + 1) * 86400 >= $daystart
            AND ((d.cdate / 86400) + d.paytime - ? + 1) * 86400 < $dayend"
            . ($notifications['reminder']['deleted_customers'] ? '' : ' AND c.deleted = 0'),
        array(
            DOC_CNOTE,
            DOC_RECEIPT,
            DOC_CNOTE,
            DOC_CNOTE,
            DOC_INVOICE,
            DOC_INVOICE_PRO,
            DOC_DNOTE,
            $days,
            CONTACT_EMAIL | CONTACT_INVOICES | CONTACT_NOTIFICATIONS | CONTACT_DISABLED,
            CONTACT_EMAIL | CONTACT_INVOICES | CONTACT_NOTIFICATIONS,
            CONTACT_MOBILE | CONTACT_NOTIFICATIONS | CONTACT_DISABLED,
            CONTACT_MOBILE | CONTACT_NOTIFICATIONS,
            DOC_INVOICE,
            DOC_INVOICE_PRO,
            DOC_DNOTE,
            $limit,
            $days,
            $days
        )
    );
    if (!empty($documents)) {
        $notifications['reminder']['customers'] = array();
        foreach ($documents as $row) {
            $notifications['reminder']['customers'][] = $row['id'];
            $row['doc_number'] = docnumber(array(
                'number' => $row['number'],
                'template' => ($row['template'] ? $row['template'] : '%N/LMS/%Y'),
                'cdate' => $row['cdate'],
                'customerid' => $row['id'],
            ));

            $message = parse_customer_data($notifications['reminder']['message'], $row);
            $subject = parse_customer_data($notifications['reminder']['subject'], $row);

            $recipient_mails = ($debug_email ? explode(',', $debug_email) :
                (!empty($row['email']) ? explode(',', trim($row['email'])) : null));
            $recipient_phones = ($debug_phone ? explode(',', $debug_phone) :
                (!empty($row['phone']) ? explode(',', trim($row['phone'])) : null));

            if (!$quiet) {
                if (in_array('mail', $channels) && !empty($recipient_mails)) {
                    foreach ($recipient_mails as $recipient_mail) {
                        printf(
                            "[mail/reminder] %s (%04d) %s: %s" . PHP_EOL,
                            $row['name'],
                            $row['id'],
                            $row['doc_number'],
                            $recipient_mail
                        );
                    }
                }
                if (in_array('sms', $channels) && !empty($recipient_phones)) {
                    foreach ($recipient_phones as $recipient_phone) {
                        printf(
                            "[sms/reminder] %s (%04d) %s: %s" . PHP_EOL,
                            $row['name'],
                            $row['id'],
                            $row['doc_number'],
                            $recipient_phone
                        );
                    }
                }
                if (in_array('backend', $channels)) {
                    printf(
                        "[backend/reminder] %s (%04d)" . PHP_EOL,
                        $row['name'],
                        $row['id'],
                        $row['doc_number']
                    );
                }
                if (in_array('userpanel', $channels)) {
                    printf(
                        "[userpanel/reminder] %s (%04d) %s" . PHP_EOL,
                        $row['name'],
                        $row['id'],
                        $row['doc_number']
                    );
                }
                if (in_array('userpanel-urgent', $channels)) {
                    printf(
                        "[userpanel-urgent/reminder] %s (%04d) %s" . PHP_EOL,
                        $row['name'],
                        $row['id'],
                        $row['doc_number']
                    );
                }
            }

            if (!$debug) {
                if (in_array('mail', $channels) && !empty($recipient_mails)) {
                    $msgid = create_message(MSG_MAIL, $subject, $message);
                    foreach ($recipient_mails as $recipient_mail) {
                        send_mail(
                            $msgid,
                            $row['id'],
                            $recipient_mail,
                            $row['name'],
                            $subject,
                            $message
                        );
                    }
                }
                if (in_array('sms', $channels) && !empty($recipient_phones)) {
                    $msgid = create_message(MSG_SMS, $subject, $message);
                    foreach ($recipient_phones as $recipient_phone) {
                        send_sms($msgid, $row['id'], $recipient_phone, $message);
                    }
                }
                if (in_array('userpanel', $channels)) {
                    $msgid = create_message(MSG_USERPANEL, $subject, $message);
                    send_to_userpanel($msgid, $row['id'], trans('userpanel'));
                }
                if (in_array('userpanel-urgent', $channels)) {
                    $msgid = create_message(MSG_USERPANEL_URGENT, $subject, $message);
                    send_to_userpanel($msgid, $row['id'], trans('userpanel urgent'));
                }
            }
        }
    }
}

// Income as result of customer payment
if (empty($types) || in_array('income', $types)) {
    $days = $notifications['income']['days'];
    $incomes = $DB->GetAll(
        "SELECT c.id, c.pin, cash.value, cash.currency, cash.time AS cdate,
        m.email, x.phone, divisions.account,
        " . $DB->Concat('c.lastname', "' '", 'c.name') . " AS name,
        b2.balance AS balance, b.balance AS totalbalance
        FROM cash
        JOIN customers c ON c.id = cash.customerid
        LEFT JOIN divisions ON divisions.id = c.divisionid
        LEFT JOIN (
            SELECT customerid, SUM(value * currencyvalue) AS balance FROM cash GROUP BY customerid
        ) b ON b.customerid = c.id
        LEFT JOIN (
            SELECT cash.customerid, SUM(value * cash.currencyvalue) AS balance FROM cash
            LEFT JOIN customers ON customers.id = cash.customerid
            LEFT JOIN divisions ON divisions.id = customers.divisionid
            LEFT JOIN documents d ON d.id = cash.docid
            LEFT JOIN (
                SELECT SUM(value * cash.currencyvalue) AS totalvalue, docid FROM cash
                JOIN documents ON documents.id = cash.docid
                WHERE documents.type = ?
                GROUP BY docid
            ) tv ON tv.docid = cash.docid
            WHERE (cash.docid IS NULL AND ((cash.type <> 0 AND cash.time < $currtime)
                OR (cash.type = 0 AND cash.time + ((CASE customers.paytime WHEN -1 THEN
                    (CASE WHEN divisions.inv_paytime IS NULL THEN $deadline ELSE divisions.inv_paytime END) ELSE customers.paytime END)) * 86400 < $currtime)))
                OR (cash.docid IS NOT NULL AND ((d.type = ? AND cash.time < $currtime)
                    OR (d.type = ? AND cash.time < $currtime AND tv.totalvalue >= 0)
                    OR (((d.type = ? AND tv.totalvalue < 0)
                        OR d.type IN (?, ?, ?)) AND d.cdate + d.paytime * 86400 < $currtime)))
            GROUP BY cash.customerid
        ) b2 ON b2.customerid = c.id
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
        WHERE cash.type = 1 AND cash.value > 0 AND cash.time >= $daystart + (? * 86400) AND cash.time < $daystart + (? + 1) * 86400"
            . ($notifications['income']['deleted_customers'] ? '' : ' AND c.deleted = 0'),
        array(
            DOC_CNOTE,
            DOC_RECEIPT,
            DOC_CNOTE,
            DOC_CNOTE,
            DOC_INVOICE,
            DOC_INVOICE_PRO,
            DOC_DNOTE,
            CONTACT_EMAIL | CONTACT_INVOICES | CONTACT_NOTIFICATIONS | CONTACT_DISABLED,
            CONTACT_EMAIL | CONTACT_INVOICES | CONTACT_NOTIFICATIONS,
            CONTACT_MOBILE | CONTACT_NOTIFICATIONS | CONTACT_DISABLED,
            CONTACT_MOBILE | CONTACT_NOTIFICATIONS,
            $days,
            $days,
        )
    );

    if (!empty($incomes)) {
        $notifications['income']['customers'] = array();
        foreach ($incomes as $row) {
            $notifications['income']['customers'][] = $row['id'];

            $message = parse_customer_data($notifications['income']['message'], $row);
            $subject = parse_customer_data($notifications['income']['subject'], $row);

            $recipient_mails = ($debug_email ? explode(',', $debug_email) :
                (!empty($row['email']) ? explode(',', trim($row['email'])) : null));
            $recipient_phones = ($debug_phone ? explode(',', $debug_phone) :
                (!empty($row['phone']) ? explode(',', trim($row['phone'])) : null));

            if (!$quiet) {
                if (in_array('mail', $channels) && !empty($recipient_mails)) {
                    foreach ($recipient_mails as $recipient_mail) {
                        printf(
                            "[mail/income] %s (%04d) - %s: %s" . PHP_EOL,
                            $row['name'],
                            $row['id'],
                            moneyf($row['value']),
                            $recipient_mail
                        );
                    }
                }
                if (in_array('sms', $channels) && !empty($recipient_phones)) {
                    foreach ($recipient_phones as $recipient_phone) {
                        printf(
                            "[sms/income] %s (%04d) - %s: %s" . PHP_EOL,
                            $row['name'],
                            $row['id'],
                            moneyf($row['value']),
                            $recipient_phone
                        );
                    }
                }
                if (in_array('userpanel', $channels)) {
                    printf(
                        "[userpanel/income] %s (%04d) - %s" . PHP_EOL,
                        $row['name'],
                        $row['id'],
                        moneyf($row['value'])
                    );
                }
                if (in_array('userpanel-urgent', $channels)) {
                    printf(
                        "[userpanel-urgent/income] %s (%04d) - %s" . PHP_EOL,
                        $row['name'],
                        $row['id'],
                        moneyf($row['value'])
                    );
                }
            }

            if (!$debug) {
                if (in_array('mail', $channels) && !empty($recipient_mails)) {
                    $msgid = create_message(MSG_MAIL, $subject, $message);
                    foreach ($recipient_mails as $recipient_mail) {
                        send_mail(
                            $msgid,
                            $row['id'],
                            $recipient_mail,
                            $row['name'],
                            $subject,
                            $message
                        );
                    }
                }
                if (in_array('sms', $channels) && !empty($recipient_phones)) {
                    $msgid = create_message(MSG_SMS, $subject, $message);
                    foreach ($recipient_phones as $recipient_phone) {
                        send_sms($msgid, $row['id'], $recipient_phone, $message);
                    }
                }
                if (in_array('userpanel', $channels)) {
                    $msgid = create_message(MSG_USERPANEL, $subject, $message);
                    send_to_userpanel($msgid, $row['id'], trans('userpanel'));
                }
                if (in_array('userpanel-urgent', $channels)) {
                    $msgid = create_message(MSG_USERPANEL_URGENT, $subject, $message);
                    send_to_userpanel($msgid, $row['id'], trans('userpanel urgent'));
                }
            }
        }
    }
}

// Invoices created at current day
if (empty($types) || in_array('invoices', $types)) {
    $documents = $DB->GetAll(
        "SELECT d.id AS docid, c.id, c.pin, d.name,
        d.number, n.template, d.cdate, d.paytime, m.email, x.phone, divisions.account,
        COALESCE(ca.balance, 0) AS balance, v.value, v.currency
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
        JOIN (SELECT SUM(value) * -1 AS value, currency, docid
            FROM cash
            GROUP BY docid, currency
        ) v ON (v.docid = d.id)
        LEFT JOIN numberplans n ON (d.numberplanid = n.id)
        LEFT JOIN (SELECT SUM(value * currencyvalue) AS balance, customerid
            FROM cash
            GROUP BY customerid
        ) ca ON (ca.customerid = d.customerid)
        WHERE (c.invoicenotice IS NULL OR c.invoicenotice = 0) AND d.type IN (?, ?, ?)
            AND d.cdate >= ? AND d.cdate <= ?"
            . ($notifications['invoices']['deleted_customers'] ? '' : ' AND c.deleted = 0'),
        array(
            CONTACT_EMAIL | CONTACT_INVOICES | CONTACT_NOTIFICATIONS | CONTACT_DISABLED,
            CONTACT_EMAIL | CONTACT_INVOICES | CONTACT_NOTIFICATIONS,
            CONTACT_MOBILE | CONTACT_NOTIFICATIONS | CONTACT_DISABLED,
            CONTACT_MOBILE | CONTACT_NOTIFICATIONS,
            DOC_INVOICE,
            DOC_INVOICE_PRO,
            DOC_CNOTE,
            $daystart,
            $dayend
        )
    );

    if (!empty($documents)) {
        $notifications['invoices']['customers'] = array();
        foreach ($documents as $row) {
            $notifications['invoices']['customers'][] = $row['id'];
            $row['doc_number'] = docnumber(array(
                'number' => $row['number'],
                'template' => ($row['template'] ? $row['template'] : '%N/LMS/%Y'),
                'cdate' => $row['cdate'],
                'customerid' => $row['id'],
            ));

            $message = parse_customer_data($notifications['invoices']['message'], $row);
            $subject = parse_customer_data($notifications['invoices']['subject'], $row);

            $recipient_mails = ($debug_email ? explode(',', $debug_email) :
                (!empty($row['email']) ? explode(',', trim($row['email'])) : null));
            $recipient_phones = ($debug_phone ? explode(',', $debug_phone) :
                (!empty($row['phone']) ? explode(',', trim($row['phone'])) : null));

            if (!$quiet) {
                if (in_array('mail', $channels) && !empty($recipient_mails)) {
                    foreach ($recipient_mails as $recipient_mail) {
                        printf(
                            "[mail/invoices] %s (%04d) %s: %s" . PHP_EOL,
                            $row['name'],
                            $row['id'],
                            $row['doc_number'],
                            $recipient_mail
                        );
                    }
                }
                if (in_array('sms', $channels) && !empty($recipient_phones)) {
                    foreach ($recipient_phones as $recipient_phone) {
                        printf(
                            "[sms/invoices] %s (%04d): %s: %s" . PHP_EOL,
                            $row['name'],
                            $row['id'],
                            $row['doc_number'],
                            $recipient_phone
                        );
                    }
                }
                if (in_array('userpanel', $channels)) {
                    printf(
                        "[userpanel/invoices] %s (%04d): %s" . PHP_EOL,
                        $row['name'],
                        $row['id'],
                        $row['doc_number']
                    );
                }
                if (in_array('userpanel-urgent', $channels)) {
                    printf(
                        "[userpanel-urgent/invoices] %s (%04d): %s" . PHP_EOL,
                        $row['name'],
                        $row['id'],
                        $row['doc_number']
                    );
                }
            }

            if (!$debug) {
                if (in_array('mail', $channels) && !empty($recipient_mails)) {
                    $msgid = create_message(MSG_MAIL, $subject, $message);
                    foreach ($recipient_mails as $recipient_mail) {
                        send_mail(
                            $msgid,
                            $row['id'],
                            $recipient_mail,
                            $row['name'],
                            $subject,
                            $message
                        );
                    }
                }
                if (in_array('sms', $channels) && !empty($recipient_phones)) {
                    $msgid = create_message(MSG_SMS, $subject, $message);
                    foreach ($recipient_phones as $recipient_phone) {
                        send_sms($msgid, $row['id'], $recipient_phone, $message);
                    }
                }
                if (in_array('userpanel', $channels)) {
                    $msgid = create_message(MSG_USERPANEL, $subject, $message);
                    send_to_userpanel($msgid, $row['id'], trans('userpanel'));
                }
                if (in_array('userpanel-urgent', $channels)) {
                    $msgid = create_message(MSG_USERPANEL_URGENT, $subject, $message);
                    send_to_userpanel($msgid, $row['id'], trans('userpanel urgent'));
                }
            }
        }
    }
}

// Debit notes created at current day
if (empty($types) || in_array('notes', $types)) {
    $documents = $DB->GetAll(
        "SELECT d.id AS docid, c.id, c.pin, d.name,
        d.number, n.template, d.cdate, m.email, x.phone, divisions.account,
        COALESCE(ca.balance, 0) AS balance, v.value, v.currency
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
        JOIN (SELECT SUM(value) * -1 AS value, currency, docid
            FROM cash
            GROUP BY docid, currency
        ) v ON (v.docid = d.id)
        LEFT JOIN numberplans n ON (d.numberplanid = n.id)
        LEFT JOIN (SELECT SUM(value * currencyvalue) AS balance, customerid
            FROM cash
            GROUP BY customerid
        ) ca ON (ca.customerid = d.customerid)
        WHERE (c.invoicenotice IS NULL OR c.invoicenotice = 0) AND d.type = ?
            AND d.cdate >= ? AND d.cdate <= ?"
            . ($notifications['notes']['deleted_customers'] ? '' : ' AND c.deleted = 0'),
        array(
            CONTACT_EMAIL | CONTACT_NOTIFICATIONS | CONTACT_DISABLED,
            CONTACT_EMAIL | CONTACT_NOTIFICATIONS,
            CONTACT_MOBILE | CONTACT_NOTIFICATIONS | CONTACT_DISABLED,
            CONTACT_MOBILE | CONTACT_NOTIFICATIONS,
            DOC_DNOTE,
            $daystart,
            $dayend
        )
    );
    if (!empty($documents)) {
        $notifications['notes']['customers'] = array();
        foreach ($documents as $row) {
            $notifications['notes']['customers'][] = $row['id'];
            $row['doc_number'] = docnumber(array(
                'number' => $row['number'],
                'template' => ($row['template'] ? $row['template'] : '%N/LMS/%Y'),
                'cdate' => $row['cdate'],
                'customerid' => $row['id'],
            ));

            $message = parse_customer_data($notifications['notes']['message'], $row);
            $subject = parse_customer_data($notifications['notes']['subject'], $row);

            $recipient_mails = ($debug_email ? explode(',', $debug_email) :
                (!empty($row['email']) ? explode(',', trim($row['email'])) : null));
            $recipient_phones = ($debug_phone ? explode(',', $debug_phone) :
                (!empty($row['phone']) ? explode(',', trim($row['phone'])) : null));

            if (!$quiet) {
                if (in_array('mail', $channels) && !empty($recipient_mails)) {
                    foreach ($recipient_mails as $recipient_mail) {
                        printf(
                            "[mail/notes] %s (%04d) %s: %s" . PHP_EOL,
                            $row['name'],
                            $row['id'],
                            $row['doc_number'],
                            $recipient_mail
                        );
                    }
                }
                if (in_array('sms', $channels) && !empty($recipient_phones)) {
                    foreach ($recipient_phones as $recipient_phone) {
                        printf(
                            "[sms/notes] %s (%04d) %s: %s" . PHP_EOL,
                            $row['name'],
                            $row['id'],
                            $row['doc_number'],
                            $recipient_phone
                        );
                    }
                }
                if (in_array('userpanel', $channels)) {
                    printf(
                        "[userpanel/notes] %s (%04d): %s" . PHP_EOL,
                        $row['name'],
                        $row['id'],
                        $row['doc_number']
                    );
                }
                if (in_array('userpanel-urgent', $channels)) {
                    printf(
                        "[userpanel-urgent/notes] %s (%04d): %s" . PHP_EOL,
                        $row['name'],
                        $row['id'],
                        $row['doc_number']
                    );
                }
            }

            if (!$debug) {
                if (in_array('mail', $channels) && !empty($recipient_mails)) {
                    $msgid = create_message(MSG_MAIL, $subject, $message);
                    foreach ($recipient_mails as $recipient_mail) {
                        send_mail(
                            $msgid,
                            $row['id'],
                            $recipient_mail,
                            $row['name'],
                            $subject,
                            $message
                        );
                    }
                }
                if (in_array('sms', $channels) && !empty($recipient_phones)) {
                    $msgid = create_message(MSG_SMS, $subject, $message);
                    foreach ($recipient_phones as $recipient_phone) {
                        send_sms($msgid, $row['id'], $recipient_phone, $message);
                    }
                }
                if (in_array('userpanel', $channels)) {
                    $msgid = create_message(MSG_USERPANEL, $subject, $message);
                    send_to_userpanel($msgid, $row['id'], trans('userpanel'));
                }
                if (in_array('userpanel-urgent', $channels)) {
                    $msgid = create_message(MSG_USERPANEL_URGENT, $subject, $message);
                    send_to_userpanel($msgid, $row['id'], trans('userpanel urgent'));
                }
            }
        }
    }
}

// Node which warning flag has set
if (empty($types) || in_array('warnings', $types)) {
    $customers = $DB->GetAll(
        "SELECT c.id, (" . $DB->Concat('c.lastname', "' '", 'c.name') . ") AS name,
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
        LEFT JOIN (SELECT SUM(value * currencyvalue) AS balance, customerid
            FROM cash
            GROUP BY customerid
        ) ca ON (ca.customerid = c.id)
        WHERE c.id IN (SELECT DISTINCT ownerid FROM vnodes WHERE warning = 1)"
            . ($notifications['warnings']['deleted_customers'] ? '' : ' AND c.deleted = 0'),
        array(
            CONTACT_EMAIL | CONTACT_NOTIFICATIONS | CONTACT_DISABLED,
            CONTACT_EMAIL | CONTACT_NOTIFICATIONS,
            CONTACT_MOBILE | CONTACT_NOTIFICATIONS | CONTACT_DISABLED,
            CONTACT_MOBILE | CONTACT_NOTIFICATIONS
        )
    );

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
                if (in_array('mail', $channels) && !empty($recipient_mails)) {
                    foreach ($recipient_mails as $recipient_mail) {
                        printf(
                            "[mail/warnings] %s (%04d): %s" . PHP_EOL,
                            $row['name'],
                            $row['id'],
                            $recipient_mail
                        );
                    }
                }
                if (in_array('sms', $channels) && !empty($recipient_phones)) {
                    foreach ($recipient_phones as $recipient_phone) {
                        printf(
                            "[sms/warnings] %s (%04d): %s" . PHP_EOL,
                            $row['name'],
                            $row['id'],
                            $recipient_phone
                        );
                    }
                }
                if (in_array('userpanel', $channels)) {
                    printf(
                        "[userpanel/warnings] %s (%04d)" . PHP_EOL,
                        $row['name'],
                        $row['id']
                    );
                }
                if (in_array('userpanel-urgent', $channels)) {
                    printf(
                        "[userpanel-urgent/warnings] %s (%04d)" . PHP_EOL,
                        $row['name'],
                        $row['id']
                    );
                }
            }

            if (!$debug) {
                if (in_array('mail', $channels) && !empty($recipient_mails)) {
                    $msgid = create_message(MSG_MAIL, $subject, $message);
                    foreach ($recipient_mails as $recipient_mail) {
                        send_mail(
                            $msgid,
                            $row['id'],
                            $recipient_mail,
                            $row['name'],
                            $subject,
                            $message
                        );
                    }
                }
                if (in_array('sms', $channels) && !empty($recipient_phones)) {
                    $msgid = create_message(MSG_SMS, $subject, $message);
                    foreach ($recipient_phones as $recipient_phone) {
                        send_sms($msgid, $row['id'], $recipient_phone, $message);
                    }
                }
                if (in_array('userpanel', $channels)) {
                    $msgid = create_message(MSG_USERPANEL, $subject, $message);
                    send_to_userpanel($msgid, $row['id'], trans('userpanel'));
                }
                if (in_array('userpanel-urgent', $channels)) {
                    $msgid = create_message(MSG_USERPANEL_URGENT, $subject, $message);
                    send_to_userpanel($msgid, $row['id'], trans('userpanel urgent'));
                }
            }
        }
    }
}

// Events about customers should be notified if they are still opened
if (empty($types) || in_array('events', $types)) {
    $time = intval(strftime('%H%M'));
    $events = $DB->GetAll(
        "SELECT id, title, description, customerid, userid FROM events
        WHERE (customerid IS NOT NULL OR userid IS NOT NULL) AND closed = 0 AND date <= ? AND enddate >= ?
            AND begintime <= ? AND (endtime = 0 OR endtime >= ?)",
        array($daystart, $dayend, $time, $time)
    );

    if (!empty($events)) {
        $customers = array();
        $users = $DB->GetAllByKey(
            "SELECT id, name, (CASE WHEN (ntype & ?) > 0 THEN email ELSE '' END) AS email,
                (CASE WHEN (ntype & ?) > 0 THEN phone ELSE '' END) AS phone FROM vusers
            WHERE deleted = 0 AND accessfrom <= ?NOW? AND (accessto = 0 OR accessto >= ?NOW?)
            ORDER BY id",
            'id',
            array(MSG_MAIL, MSG_SMS)
        );

        foreach ($events as $event) {
            $contacts = array();

            $message = $event['description'];
            $subject = $event['title'];

            $cid = intval($event['customerid']);
            $uid = intval($event['userid']);

            if ($cid) {
                if (!array_key_exists($cid, $customers)) {
                    $customers[$cid] = $DB->GetRow(
                        "SELECT (" . $DB->Concat('c.lastname', "' '", 'c.name') . ") AS name,
                            m.email, x.phone
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
                        WHERE c.id = ?",
                        array(
                            CONTACT_EMAIL | CONTACT_NOTIFICATIONS | CONTACT_DISABLED,
                            CONTACT_EMAIL | CONTACT_NOTIFICATIONS,
                            CONTACT_MOBILE | CONTACT_NOTIFICATIONS | CONTACT_DISABLED,
                            CONTACT_MOBILE | CONTACT_NOTIFICATIONS,
                            $cid
                        )
                    );
                }
                if (!empty($customers[$cid]['email'])) {
                    $emails = explode(',', $debug_email ? $debug_email : $customers[$cid]['email']);
                    foreach ($emails as $contact) {
                        if (!array_key_exists($contact, $emails)) {
                            $contacts[$contact] = array(
                                'cid' => $cid,
                                'email' => $contact,
                            );
                        }
                    }
                }
                if (!empty($customers[$cid]['phone'])) {
                    $phones = explode(',', $debug_phone ? $debug_phone : $customers[$cid]['phone']);
                    foreach ($phones as $contact) {
                        if (!array_key_exists($contact, $phones)) {
                            $contacts[$contact] = array(
                                'cid' => $cid,
                                'phone' => $contact,
                            );
                        }
                    }
                }
            }

            if ($uid && array_key_exists($uid, $users)) {
                if (!empty($users[$uid]['email'])) {
                    $emails = explode(',', $debug_email ? $debug_email : $users[$uid]['email']);
                    foreach ($emails as $contact) {
                        if (!array_key_exists($contact, $contacts)) {
                            $contacts[$contact] = array(
                                'uid' => $uid,
                                'phone' => $contact,
                            );
                        }
                    }
                }
                if (!empty($users[$uid]['phone'])) {
                    $phones = explode(',', $debug_phone ? $debug_phone : $users[$uid]['phone']);
                    foreach ($phones as $contact) {
                        if (!array_key_exists($contact, $contacts)) {
                            $contacts[$contact] = array(
                                'uid' => $uid,
                                'phone' => $contact,
                            );
                        }
                    }
                }
            }

            if (!$quiet) {
                foreach ($contacts as $contact) {
                    if (array_key_exists('uid', $contact)) {
                        $uid = $contact['uid'];
                        if (in_array('mail', $channels) && array_key_exists('email', $contact)) {
                            printf(
                                "[mail/events] %s (UID: %04d): %s" . PHP_EOL,
                                $users[$uid]['name'],
                                $uid,
                                $contact['email']
                            );
                            if (!$debug) {
                                send_mail_to_user($contact['email'], $users[$uid]['name'], $subject, $message);
                            }
                        }
                        if (in_array('sms', $channels) && array_key_exists('phone', $contact)) {
                            printf(
                                "[sms/events] %s (UID: %04d): %s" . PHP_EOL,
                                $users[$uid]['name'],
                                $uid,
                                $contact['phone']
                            );
                            if (!$debug) {
                                send_sms_to_user($contact['phone'], $message);
                            }
                        }
                    }
                    if (array_key_exists('cid', $contact)) {
                        $cid = $contact['cid'];
                        if (in_array('mail', $channels) && array_key_exists('email', $contact)) {
                            printf(
                                "[mail/events] %s (CID: %04d): %s" . PHP_EOL,
                                $customers[$cid]['name'],
                                $cid,
                                $contact['email']
                            );
                            if (!$debug) {
                                $msgid = create_message(MSG_MAIL, $subject, $message);
                                send_mail(
                                    $msgid,
                                    $cid,
                                    $contact['email'],
                                    $customers[$cid]['name'],
                                    $subject,
                                    $message
                                );
                            }
                        }
                        if (in_array('sms', $channels) && array_key_exists('phone', $contact)) {
                            printf(
                                "[sms/events] %s (CID: %04d): %s" . PHP_EOL,
                                $customers[$cid]['name'],
                                $cid,
                                $contact['phone']
                            );
                            if (!$debug) {
                                $msgid = create_message(MSG_SMS, $subject, $message);
                                send_sms($msgid, $cid, $contact['phone'], $message);
                            }
                        }
                    }
                }
            }
        }
    }
}

// send message to customers which have awaiting www messages
if (in_array('www', $channels) && (empty($types) || in_array('messages', $types))) {
    if (!$debug) {
        $fh = fopen($notifications['messages']['file'], 'w');
    }

    $nodes = $DB->GetAll("SELECT INET_NTOA(ipaddr) AS ip
            FROM vnodes n
        JOIN (SELECT DISTINCT customerid FROM messageitems
            JOIN messages m ON m.id = messageid
            WHERE type = ? AND status = ?
        ) m ON m.customerid = n.ownerid
        ORDER BY ipaddr", array(MSG_WWW, MSG_NEW));

    if (!$debug && $fh) {
        fwrite($fh, str_replace("\\n", PHP_EOL, $notifications['messages']['header']));
    }

    if (!empty($nodes)) {
        foreach ($nodes as $node) {
            if (!$quiet) {
                printf("[www/messages] %s" . PHP_EOL, $node['ip']);
            }
            if (!$debug && $fh) {
                fwrite($fh, str_replace(
                    "\\n",
                    PHP_EOL,
                    parse_node_data($notifications['messages']['rule'], $node)
                ));
            }
        }
        if (!$debug) {
            $DB->Execute(
                "UPDATE messageitems
                SET status = ?
                WHERE messageid IN (
                    SELECT id FROM messages WHERE type = ? AND status = ?
                )",
                array(
                    MSG_SENT,
                    MSG_WWW,
                    MSG_NEW,
                )
            );
        }
    }

    if (!$debug && $fh) {
        fwrite($fh, str_replace("\\n", PHP_EOL, $notifications['messages']['footer']));
        fclose($fh);
    }
}

if (in_array('www', $channels) && !empty($types)) {
    foreach ($types as $type) {
        if ($type == 'messages') {
            continue;
        }
        $notification = $notifications[$type];
        if (!$debug) {
            if (!($fh = fopen($notification['file'], 'w'))) {
                continue;
            }
            fwrite($fh, str_replace("\\n", PHP_EOL, $notification['header']));
        }
        if (!empty($notification['customers'])) {
            if ($type == 'warnings') {
                $nodes = $DB->GetAll("SELECT INET_NTOA(ipaddr) AS ip
                        FROM vnodes
                    WHERE warning = 1 ORDER BY ipaddr");
            } else {
                $nodes = $DB->GetAll("SELECT INET_NTOA(ipaddr) AS ip
                        FROM vnodes
                    WHERE ownerid IN (" . implode(',', $notification['customers']) . ")"
                    . " ORDER BY id");
            }
            if (!empty($nodes)) {
                foreach ($nodes as $node) {
                    if (!$quiet) {
                        printf("[www/%s] %s" . PHP_EOL, $type, $node['ip']);
                    }
                    if (!$debug) {
                        fwrite($fh, str_replace(
                            "\\n",
                            PHP_EOL,
                            parse_node_data($notification['rule'], $node)
                        ));
                    }
                }
            }
        }
        if (!$debug) {
            fwrite($fh, str_replace("\\n", PHP_EOL, $notification['footer']));
            fclose($fh);
        }
    }
}

$intersect = array_intersect(array('block', 'unblock'), $channels);
if (!empty($intersect)) {
    $customers = array();
    foreach ($notifications as $type => $notification) {
        if (array_key_exists('customers', $notification)) {
            $customers = array_merge($customers, $notification['customers']);
        }
    }
    $customers = array_unique($customers);

    foreach (array('block', 'unblock') as $channel) {
        if (in_array($channel, $channels)) {
            switch ($channel) {
                case 'block':
                    if (empty($customers)) {
                        break;
                    }
                    $customers = $DB->GetCol(
                        "SELECT id FROM customers
                        WHERE status IN (?, ?) AND id IN (" . implode(',', $customers) . ")",
                        array(CSTATUS_CONNECTED, CSTATUS_DEBT_COLLECTION)
                    );
                    if (empty($customers)) {
                        break;
                    }
                    if (in_array('node-access', $actions)) {
                        $nodes = $DB->GetAll(
                            "SELECT id, ownerid FROM nodes WHERE access = ?
                            AND ownerid IN (" . implode(',', $customers) . ")",
                            array(1)
                        );
                        if (!empty($nodes)) {
                            foreach ($nodes as $node) {
                                $DB->Execute("UPDATE nodes SET access = ?
                                    WHERE id = ?", array(0, $node['id']));
                                if ($SYSLOG) {
                                    $SYSLOG->NewTransaction('lms-notify.php');
                                    $SYSLOG->AddMessage(
                                        SYSLOG::RES_NODE,
                                        SYSLOG::OPER_UPDATE,
                                        array(
                                            SYSLOG::RES_NODE => $node['id'],
                                            SYSLOG::RES_CUST => $node['ownerid'],
                                            'access' => 0
                                        )
                                    );
                                }
                            }
                        }
                    }
                    if (in_array('assignment-invoice', $actions)) {
                        $assigns = $DB->GetAll(
                            "SELECT id, customerid FROM assignments
                            WHERE invoice = ? AND (tariffid IS NOT NULL OR liabilityid IS NOT NULL)
                                AND datefrom <= ?NOW? AND (dateto = 0 OR dateto >= ?NOW?)
                                AND customerid IN (" . implode(',', $customers) . ")",
                            array(1)
                        );
                        if (!empty($assigns)) {
                            foreach ($assigns as $assign) {
                                $DB->Execute("UPDATE assignments SET invoice = ?
                                    WHERE id = ?", array(0, $assign['id']));
                                if ($SYSLOG) {
                                    $SYSLOG->NewTransaction('lms-notify.php');
                                    $SYSLOG->AddMessage(
                                        SYSLOG::RES_ASSIGN,
                                        SYSLOG::OPER_UPDATE,
                                        array(
                                            SYSLOG::RES_ASSIGN => $assign['id'],
                                            SYSLOG::RES_CUST => $assign['customerid'],
                                            'invoice' => 0
                                        )
                                    );
                                }
                            }
                        }
                    }
                    if (in_array('customer-status', $actions)) {
                        $custids = $DB->GetCol(
                            "SELECT id FROM customers
                            WHERE status <> ? AND id IN (" . implode(',', $customers) . ")",
                            array(CSTATUS_DEBT_COLLECTION)
                        );
                        if (!empty($custids)) {
                            foreach ($custids as $custid) {
                                $DB->Execute(
                                    "UPDATE customers SET status = ? WHERE id = ?",
                                    array(CSTATUS_DEBT_COLLECTION, $custid)
                                );
                                if ($SYSLOG) {
                                    $SYSLOG->NewTransaction('lms-notify.php');
                                    $SYSLOG->AddMessage(
                                        SYSLOG::RES_CUST,
                                        SYSLOG::OPER_UPDATE,
                                        array(SYSLOG::RES_CUST => $custid, 'status' => CSTATUS_DEBT_COLLECTION)
                                    );
                                }
                            }
                        }
                    }
                    if (in_array('all-assignment-suspension', $actions)) {
                        $args = array(
                            SYSLOG::RES_ASSIGN => null,
                            SYSLOG::RES_CUST => null,
                            'datefrom' => time(),
                            SYSLOG::RES_TARIFF => null,
                            SYSLOG::RES_LIAB => null,
                        );
                        foreach ($customers as $cid) {
                            if (!$DB->GetOne(
                                "SELECT id FROM assignments WHERE customerid = ? AND tariffid IS NULL AND liabilityid IS NULL",
                                array($cid)
                            )) {
                                $DB->Execute("INSERT INTO assignments (customerid, datefrom, tariffid, liabilityid)
                                    VALUES (?, ?, NULL, NULL)", array($cid, $args['datefrom']));
                                if ($SYSLOG) {
                                    $SYSLOG->NewTransaction('lms-notify.php');
                                    $args[SYSLOG::RES_ASSIGN] = $DB->GetLastInsertID('assignments');
                                    $args[SYSLOG::RES_CUST] = $cid;
                                    $SYSLOG->AddMessage(SYSLOG::RES_ASSIGN, SYSLOG::OPER_ADD, $args);
                                }
                            }
                        }
                    }
                    $plugin_manager->executeHook('notification_blocks', array(
                        'customers' => $customers,
                        'actions' => $actions,
                    ));
                    break;
                case 'unblock':
                    if (empty($customers)) {
                        break;
                    }
                    $customers = $DB->GetCol(
                        "SELECT id FROM customers WHERE status = ?"
                        . (empty($customers) ? '' : " AND id NOT IN (" . implode(',', $customers) . ")"),
                        array(CSTATUS_DEBT_COLLECTION)
                    );
                    if (empty($customers)) {
                        break;
                    }
                    if (in_array('node-access', $actions)) {
                        $nodes = $DB->GetAll(
                            "SELECT id, ownerid FROM nodes WHERE access = ?
                            AND ownerid IN (" . implode(',', $customers) . ")",
                            array(0)
                        );
                        if (!empty($nodes)) {
                            foreach ($nodes as $node) {
                                $DB->Execute("UPDATE nodes SET access = ?
                                    WHERE id = ?", array(1, $node['id']));
                                if ($SYSLOG) {
                                    $SYSLOG->NewTransaction('lms-notify.php');
                                    $SYSLOG->AddMessage(
                                        SYSLOG::RES_NODE,
                                        SYSLOG::OPER_UPDATE,
                                        array(
                                            SYSLOG::RES_NODE => $node['id'],
                                            SYSLOG::RES_CUST => $node['ownerid'],
                                            'access' => 1
                                        )
                                    );
                                }
                            }
                        }
                    }
                    if (in_array('assignment-invoice', $actions)) {
                        $assigns = $DB->GetAll(
                            "SELECT id, customerid FROM assignments
                            WHERE invoice = ? AND (tariffid IS NOT NULL OR liabilityid IS NOT NULL)
                                AND datefrom <= ?NOW? AND (dateto = 0 OR dateto >= ?NOW?)
                                AND customerid IN (" . implode(',', $customers) . ")",
                            array(0)
                        );
                        if (!empty($assigns)) {
                            foreach ($assigns as $assign) {
                                $DB->Execute("UPDATE assignments SET invoice = ?
                                    WHERE id = ?", array(1, $assign['id']));
                                if ($SYSLOG) {
                                    $SYSLOG->NewTransaction('lms-notify.php');
                                    $SYSLOG->AddMessage(
                                        SYSLOG::RES_ASSIGN,
                                        SYSLOG::OPER_UPDATE,
                                        array(
                                            SYSLOG::RES_ASSIGN => $assign['id'],
                                            SYSLOG::RES_CUST => $assign['customerid'],
                                            'invoice' => 1
                                        )
                                    );
                                }
                            }
                        }
                    }
                    if (in_array('customer-status', $actions)) {
                        $custids = $DB->GetCol(
                            "SELECT id FROM customers
                            WHERE status = ? AND id IN (" . implode(',', $customers) . ")",
                            array(CSTATUS_DEBT_COLLECTION)
                        );
                        if (!empty($custids)) {
                            foreach ($custids as $custid) {
                                $DB->Execute(
                                    "UPDATE customers SET status = ? WHERE id = ?",
                                    array(CSTATUS_CONNECTED, $custid)
                                );
                                if ($SYSLOG) {
                                    $SYSLOG->NewTransaction('lms-notify.php');
                                    $SYSLOG->AddMessage(
                                        SYSLOG::RES_CUST,
                                        SYSLOG::OPER_UPDATE,
                                        array(SYSLOG::RES_CUST => $custid, 'status' => CSTATUS_CONNECTED)
                                    );
                                }
                            }
                        }
                    }
                    if (in_array('all-assignment-suspension', $actions)) {
                        $args = array(
                            SYSLOG::RES_ASSIGN => null,
                            SYSLOG::RES_CUST => null,
                            'settlement' => 1,
                            'datefrom' => time(),
                        );
                        foreach ($customers as $cid) {
                            if ($SYSLOG) {
                                $SYSLOG->NewTransaction('lms-notify.php');
                            }
                            if ($datefrom = $DB->GetOne(
                                "SELECT datefrom FROM assignments WHERE customerid = ? AND tariffid IS NULL AND liabilityid IS NULL",
                                array($cid)
                            )) {
                                $year = intval(strftime('%Y', $datefrom));
                                $month = intval(strftime('%m', $datefrom));
                                if ($year < $current_year || ($year == $current_year && $month < $current_month)) {
                                    $aids = $DB->GetCol(
                                        "SELECT id FROM assignments
                                        WHERE customerid = ? AND (tariffid IS NOT NULL OR liabilityid IS NOT NULL)
                                            AND datefrom < ?NOW? AND (dateto = 0 OR dateto > ?NOW?)",
                                        array($cid)
                                    );
                                    if (!empty($aids)) {
                                        foreach ($aids as $aid) {
                                            $DB->Execute("UPDATE assignments SET settlement = 1, datefrom = ?
                                                WHERE id = ?", array($args['datefrom'], $aid));
                                            if ($SYSLOG) {
                                                $args[SYSLOG::RES_ASSIGN] = $aid;
                                                $args[SYSLOG::RES_CUST] = $cid;
                                                $SYSLOG->AddMessage(SYSLOG::RES_ASSIGN, SYSLOG::OPER_UPDATE, $args);
                                            }
                                        }
                                    }
                                }
                            }
                            $aids = $DB->GetCol("SELECT id FROM assignments
                                WHERE customerid = ? AND tariffid IS NULL AND liabilityid IS NULL", array($cid));
                            if (!empty($aids)) {
                                foreach ($aids as $aid) {
                                    $DB->Execute("DELETE FROM assignments WHERE id = ?", array($aid));
                                    if ($SYSLOG) {
                                        $SYSLOG->AddMessage(
                                            SYSLOG::RES_ASSIGN,
                                            SYSLOG::OPER_DELETE,
                                            array(SYSLOG::RES_ASSIGN => $aid, SYSLOG::RES_CUST => $cid)
                                        );
                                    }
                                }
                            }
                        }
                    }
                    $plugin_manager->executeHook('notification_unblocks', array(
                        'customers' => $customers,
                        'actions' => $actions,
                    ));
                    break;
            }
        }
    }
}

$DB->Destroy();
