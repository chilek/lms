#!/usr/bin/env php
<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2021 LMS Developers
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

ini_set('error_reporting', E_ALL & ~E_NOTICE & ~E_DEPRECATED);

$parameters = array(
    'config-file:' => 'C:',
    'quiet' => 'q',
    'help' => 'h',
    'version' => 'v',
    'debug' => 'd',
    'fakedate:' => 'f:',
    'part-number:' => 'p:',
    'part-size:' => 'l:',
    'interval:' => 'i:',
    'type:' => 't:',
    'section:' => 's:',
    'channel:' => 'c:',
    'actions:' => 'a:',
    'block-prechecks:' => null,
    'unblock-prechecks:' => null,
    'customergroups:' => 'g:',
    'customer-status:' => null,
    'customerid:' => null,
    'division:' => null,
);

$long_to_shorts = array();
foreach ($parameters as $long => $short) {
    $long = str_replace(':', '', $long);
    if (isset($short)) {
        $short = str_replace(':', '', $short);
    }
    $long_to_shorts[$long] = $short;
}

$options = getopt(
    implode(
        '',
        array_filter(
            array_values($parameters),
            function ($value) {
                return isset($value);
            }
        )
    ),
    array_keys($parameters)
);

foreach (array_flip(array_filter($long_to_shorts, function ($value) {
    return isset($value);
})) as $short => $long) {
    if (array_key_exists($short, $options)) {
        $options[$long] = $options[$short];
        unset($options[$short]);
    }
}

if (array_key_exists('version', $options)) {
    print <<<EOF
lms-notify.php
(C) 2001-2021 LMS Developers

EOF;
    exit(0);
}

if (array_key_exists('help', $options)) {
    print <<<EOF
lms-notify.php
(C) 2001-2021 LMS Developers

-C, --config-file=/etc/lms/lms.ini      alternate config file (default: /etc/lms/lms.ini);
-h, --help                      print this help and exit;
-v, --version                   print version info and exit;
-q, --quiet                     suppress any output, except errors
-d, --debug                     do debugging, dont send anything.
-f, --fakedate=YYYY/MM/DD       override system date;
-p, --part-number=NN            defines which part of notifications should be sent;
-l, --part-size=NN              defines part size of notifications which should be sent
                                (can be specified as percentage value);
-i, --interval=ms               force delay interval between subsequent posts
-t, --type=<notification-types> take only selected notification types into account
                                (separated by colons)
-c, --channel=<channel-types>  use selected channels for notifications
                                (separated by colons)
-s, --section=<section-name>    section name from lms configuration where settings
                                are stored
-a, --actions=<node-access,node-warning,customer-status,assignment-invoice,all-assignment-suspension>
                                action names which should be performed for
                                virtual block/unblock channels
    --block-prechecks=none|<node-access,customer-status,assignment-invoice,all-assignment-suspension>
                                block pre-checks which should be performed for
                                virtual block channel
    --unblock-prechecks=none|<node-access,customer-status,assignment-invoice,all-assignment-suspension>
                                block pre-checks which should be performed for
                                virtual unblock channel
-g, --customergroups=<group1,group2,...>
                                allow to specify customer groups to which notified customers
                                should be assigned
    --customer-status=<status1,status2,...>
                                notify only customers with specified status
    --customerid=<id>           limit notifications to selected customer
    --division=<shortname>
                                limit notifications to customers which belong to specified
                                division

EOF;
    exit(0);
}

$quiet = array_key_exists('quiet', $options);
if (!$quiet) {
    print <<<EOF
lms-notify.php
(C) 2001-2021 LMS Developers

EOF;
}

$debug = array_key_exists('debug', $options);
$fakedate = (array_key_exists('fakedate', $options) ? $options['fakedate'] : null);
$customerid = isset($options['customerid']) && intval($options['customerid']) ? $options['customerid'] : null;

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

$current_month = intval(strftime('%m'));
$current_year = intval(strftime('%Y'));

$config_section = (array_key_exists('section', $options) && preg_match('/^[a-z0-9-_]+$/i', $options['section'])
    ? $options['section'] : 'notify');

$timeoffset = date('Z');

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
    die("Composer autoload not found. Run 'composer install' command from LMS directory and try again. More information at https://getcomposer.org/" . PHP_EOL);
}

// Init database

$DB = null;

try {
    $DB = LMSDB::getInstance();
} catch (Exception $ex) {
    trigger_error($ex->getMessage(), E_USER_WARNING);
    // can't work without database
    die("Fatal error: cannot connect to database!" . PHP_EOL);
}

// Include required files (including sequence is important)

require_once(LIB_DIR . DIRECTORY_SEPARATOR . 'common.php');
require_once(LIB_DIR . DIRECTORY_SEPARATOR . 'language.php');
require_once(LIB_DIR . DIRECTORY_SEPARATOR . 'definitions.php');

if (empty($fakedate)) {
    $currtime = time();
} else {
    $currtime = strtotime($fakedate);
}
list ($year, $month, $day) = explode('/', date('Y/n/j', $currtime));
$daystart = mktime(0, 0, 0, $month, $day, $year);
$dayend = mktime(23, 59, 59, $month, $day, $year);

$SYSLOG = SYSLOG::getInstance();

// Initialize Session, Auth and LMS classes

$AUTH = null;
$LMS = new LMS($DB, $AUTH, $SYSLOG);

$plugin_manager = new LMSPluginManager();
$LMS->setPluginManager($plugin_manager);
$LMS->executeHook('lms_initialized', $LMS);

define('ACTION_PARAM_NONE', 0);
define('ACTION_PARAM_REQUIRED', 1);
define('ACTION_PARAM_OPTIONAL', -1);

$supported_actions = array(
    'customer-status' => array(
        'params' => ACTION_PARAM_OPTIONAL,
        'param_validator' => function ($params) {
            global $CSTATUSES;
            if (count($params) > 1) {
                return false;
            }
            static $allowed_params = null;
            if (!isset($allowed_params)) {
                $allowed_params = Utils::array_column($CSTATUSES, 'alias');
            }
            foreach ($params as $param) {
                if (!in_array($param, $allowed_params)) {
                    return false;
                }
            }
            return true;
        },
        'param_map' => function ($params) {
            global $CSTATUSES;

            $result = array();

            static $allowed_params = null;
            if (!isset($allowed_params)) {
                $allowed_params = array_flip(Utils::array_column($CSTATUSES, 'alias'));
            }
            foreach ($params as $param) {
                if (isset($allowed_params[$param])) {
                    $result[$allowed_params[$param]] = $allowed_params[$param];
                }
            }

            return $result;
        },
    ),
    'node-access' => array(
        'params' => ACTION_PARAM_NONE,
    ),
    'node-warning' => array(
        'params' => ACTION_PARAM_NONE,
    ),
    'assignment-invoice' => array(
        'params' => ACTION_PARAM_OPTIONAL,
        'param_validator' => function ($params) {
            if (count($params) > 1) {
                return false;
            }
            static $allowed_params = array('invoice', 'proforma', 'note');
            foreach ($params as $param) {
                if (!in_array($param, $allowed_params)) {
                    return false;
                }
            }
            return true;
        },
    ),
    'all-assignment-suspension' => array(
        'params' => ACTION_PARAM_NONE,
    ),
    'customer-group' => array(
        'params' => ACTION_PARAM_REQUIRED,
    ),
    'node-group' => array(
        'params' => ACTION_PARAM_REQUIRED,
    ),
);

$hook_data = $LMS->executeHook('get_supported_actions', array('supported_actions' => $supported_actions));
$supported_actions = $hook_data['supported_actions'];

$actions = array();
if (isset($options['actions'])) {
    if (preg_match('/^[^,\(]+(\([^\)]+\))?(,[^,\(]+(\([^\)]+\))?)*$/', $options['actions'])
        && preg_match_all('/([^,\(]+)(?:\(([^\)]+)\))?/', $options['actions'], $matches)) {
        foreach ($matches[1] as $idx => $action) {
            if (!isset($supported_actions[$action])
                || ($supported_actions[$action]['params'] == ACTION_PARAM_REQUIRED && empty($matches[2][$idx]))
                || ($supported_actions[$action]['params'] == ACTION_PARAM_NONE && !empty($matches[2][$idx]))) {
                die('Invalid format of actions parameter!' . PHP_EOL);
            }
            $actions[$action] = empty($matches[2][$idx]) ? array() : preg_split('/,/', $matches[2][$idx], PREG_SPLIT_NO_EMPTY);

            if (!empty($actions[$action]) && (($supported_actions[$action]['params'] == ACTION_PARAM_REQUIRED || $supported_actions[$action]['params'] == ACTION_PARAM_OPTIONAL)
                && isset($supported_actions[$action]['param_validator']) && !($supported_actions[$action]['param_validator']($actions[$action])))) {
                die('Invalid format of actions parameter!' . PHP_EOL);
            }

            if (!empty($actions[$action]) && isset($supported_actions[$action]['param_map'])) {
                $action[$action] = $supported_actions[$action]['param_map']($actions[$action]);
            }
        }
    } else {
        die('Invalid format of actions parameter!' . PHP_EOL);
    }
} else {
    $actions = array(
        'node-access' => array(),
        'customer-status' => array(),
        'assignment-invoice' => array(),
    );
}

$block_prechecks = array();
if (isset($options['block-prechecks'])) {
    if ($options['block-prechecks'] != 'none') {
        if (preg_match('/^[^,\(]+(\([^\)]+\))?(,[^,\(]+(\([^\)]+\))?)*$/', $options['block-prechecks'])
            && preg_match_all('/([^,\(]+)(?:\(([^\)]+)\))?/', $options['block-prechecks'], $matches)) {
            foreach ($matches[1] as $idx => $precheck) {
                if (!isset($supported_actions[$precheck])
                    || ($supported_actions[$precheck]['params'] == ACTION_PARAM_REQUIRED && empty($matches[2][$idx]))
                    || ($supported_actions[$precheck]['params'] == ACTION_PARAM_NONE && !empty($matches[2][$idx]))) {
                    die('Invalid format of block_prechecks parameter!' . PHP_EOL);
                }
                $block_prechecks[$precheck] = empty($matches[2][$idx]) ? array() : preg_split('/,/', $matches[2][$idx], PREG_SPLIT_NO_EMPTY);

                if (!empty($block_prechecks[$precheck]) && (($supported_actions[$precheck]['params'] == ACTION_PARAM_REQUIRED || $supported_actions[$precheck]['params'] == ACTION_PARAM_OPTIONAL)
                    && isset($supported_actions[$precheck]['param_validator']) && !($supported_actions[$precheck]['param_validator']($block_prechecks[$precheck])))) {
                    die('Invalid format of block_prechecks parameter!' . PHP_EOL);
                }

                if (!empty($block_prechecks[$precheck]) && isset($supported_actions[$precheck]['param_map'])) {
                    $block_prechecks[$precheck] = $supported_actions[$precheck]['param_map']($block_prechecks[$precheck]);
                }
            }
        } else {
            die('Invalid format of block_prechecks parameter!' . PHP_EOL);
        }
    }
} else {
    $block_prechecks = $actions;
}

$unblock_prechecks = array();
if (isset($options['unblock-prechecks'])) {
    if ($options['unblock-prechecks'] != 'none') {
        if (preg_match('/^[^,\(]+(\([^\)]+\))?(,[^,\(]+(\([^\)]+\))?)*$/', $options['unblock-prechecks'])
            && preg_match_all('/([^,\(]+)(?:\(([^\)]+)\))?/', $options['unblock-prechecks'], $matches)) {
            foreach ($matches[1] as $idx => $precheck) {
                if (!isset($supported_actions[$precheck])
                    || ($supported_actions[$precheck]['params'] == ACTION_PARAM_REQUIRED && empty($matches[2][$idx]))
                    || ($supported_actions[$precheck]['params'] == ACTION_PARAM_NONE && !empty($matches[2][$idx]))) {
                    die('Invalid format of actions parameter!' . PHP_EOL);
                }
                $unblock_prechecks[$precheck] = empty($matches[2][$idx]) ? array() : preg_split('/,/', $matches[2][$idx], PREG_SPLIT_NO_EMPTY);

                if (!empty($unblock_prechecks[$precheck]) && (($supported_actions[$precheck]['params'] == ACTION_PARAM_REQUIRED || $supported_actions[$precheck]['params'] == ACTION_PARAM_OPTIONAL)
                    && isset($supported_actions[$precheck]['param_validator']) && !($supported_actions[$precheck]['param_validator']($unblock_prechecks[$precheck])))) {
                    die('Invalid format of unblock_prechecks parameter!' . PHP_EOL);
                }

                if (!empty($unblock_prechecks[$precheck]) && isset($supported_actions[$precheck]['param_map'])) {
                    $unblock_prechecks[$precheck] = $supported_actions[$precheck]['param_map']($unblock_prechecks[$precheck]);
                }
            }
        } else {
            die('Invalid format of unblock_prechecks parameter!' . PHP_EOL);
        }
    }
} else {
    $unblock_prechecks = $actions;
}

$divisionid = isset($options['division']) ? $LMS->getDivisionIdByShortName($options['division']) : null;
if (!empty($divisionid)) {
    ConfigHelper::setFilter($divisionid);
}

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
$mail_from = ConfigHelper::getConfig($config_section . '.sender_email', ConfigHelper::getConfig($config_section . '.mailfrom', '', true));
$mail_fname = ConfigHelper::getConfig($config_section . '.sender_name', ConfigHelper::getConfig($config_section . '.mailfname', '', true));
$notify_email = ConfigHelper::getConfig($config_section . '.notify_email', '', true);
$reply_email = ConfigHelper::getConfig($config_section . '.reply_email', '', true);
$dsn_email = ConfigHelper::getConfig($config_section . '.dsn_email', '', true);
$mdn_email = ConfigHelper::getConfig($config_section . '.mdn_email', '', true);
$format = ConfigHelper::getConfig($config_section . '.format', 'text');
$mail_format = ConfigHelper::getConfig($config_section . '.mail_format', $format);
$content_type = $format == 'html' ? 'text/html' : 'text/plain';
$mail_content_type = $mail_format == 'html' ? 'text/html' : 'text/plain';
$customergroups = ConfigHelper::getConfig($config_section . '.customergroups', '', true);
$ignore_customer_consents = ConfigHelper::checkConfig($config_section . '.ignore_customer_consents');
$ignore_contact_flags = ConfigHelper::checkConfig($config_section . '.ignore_contact_flags');

$required_phone_contact_flags = CONTACT_MOBILE | ($ignore_contact_flags ? 0 : CONTACT_NOTIFICATIONS);
$checked_phone_contact_flags = $required_phone_contact_flags | CONTACT_DISABLED;
$required_mail_contact_flags = CONTACT_EMAIL | ($ignore_contact_flags ? 0 : CONTACT_NOTIFICATIONS);
$checked_mail_contact_flags = $required_mail_contact_flags | CONTACT_DISABLED;

$allowed_customer_status =
    Utils::determineAllowedCustomerStatus(
        isset($options['customer-status'])
            ? $options['customer-status']
            : ConfigHelper::getConfig($config_section . '.allowed_customer_status', ''),
        -1
    );

if (isset($options['interval'])) {
    $interval = $options['interval'];
} else {
    $interval = ConfigHelper::getConfig($config_section . '.interval', 0);
}
if ($interval == 'random') {
    $interval = -1;
} else {
    $interval = intval($interval);
}

$part_size = isset($options['part-size']) ? $options['part-size'] : ConfigHelper::getConfig($config_section . '.limit', '0');

$part_number = isset($options['part-number']) ? $options['part-number'] : null;
if (isset($part_number)) {
    $part_number = intval($part_number);
} else {
    $part_number = intval(date('H', time()));
}

if (!empty($part_size) && preg_match('/^[0-9]+$/', $part_size)) {
    $part_offset = $part_number * $part_size;
}

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
             'birthday',
             'warnings',
             'messages',
             'timetable',
             'events',
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
    $notifications[$type]['aggregate_documents'] = ConfigHelper::checkConfig($config_section . '.' . $type . '_aggregate_documents');
}

if (in_array('mail', $channels) && empty($mail_from)) {
    die("Fatal error: mailfrom unset! Can't continue, exiting." . PHP_EOL);
}

if (!empty($auth) && !preg_match('/^LOGIN|PLAIN|CRAM-MD5|NTLM$/i', $auth)) {
    die("Fatal error: smtp_auth setting not supported! Can't continue, exiting." . PHP_EOL);
}

$deadline = ConfigHelper::getConfig('payments.deadline', ConfigHelper::getConfig('invoices.paytime', 0));

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

function parse_customer_data($data, $format, $row)
{
    global $LMS;
    $DB = LMSDB::getInstance();

    $amount = -$row['balance'];
    $totalamount = -$row['totalbalance'];
    $hook_data = $LMS->executeHook('notify_parse_customer_data', array('data' => $data, 'customer' => $row));
    $data = $hook_data['data'];

    if (isset($row['deadline'])) {
        $deadline = $row['deadline'];
    } else {
        $deadline = $row['cdate'] + $row['paytime'] * 86400;
    }

    list ($now_y, $now_m) = explode('/', strftime("%Y/%m", time()));

    if ($row['totalbalnce'] < 0) {
        $commented_balance = trans('Billing status: $a (to pay)', moneyf(-$row['totalbalance']));
    } elseif ($row['totalbalance'] > 0) {
        $commented_balance = trans('Billing status: $a (excess payment or to repay)', moneyf($row['totalbalance']));
    } else {
        $commented_balance = trans('Billing status: $a', moneyf($row['totalbalance']));
    }

    $data = str_replace(
        array(
            '%bankaccount',
            '%commented_balance',
            '%name',
            '%age',
            '%b',
            '%totalb',
            '%date-y',
            '%date-m',
            '%date_month_name',
            '%deadline-y',
            '%deadline-m',
            '%deadline-d',
            '%B',
            '%totalB',
            '%saldo',
            '%totalsaldo',
            '%pin',
            '%cid',

        ),
        array(
            format_bankaccount(bankaccount($row['id'], $row['account'])),
            $commented_balance,
            $row['name'],
            $row['age'],
            sprintf('%01.2f', $amount),
            sprintf('%01.2f', $totalamount),
            strftime('%Y'),
            strftime('%m'),
            strftime("%B"),
            strftime("%Y", $deadline),
            strftime("%m", $deadline),
            strftime("%d", $deadline),
            sprintf('%01.2f', $row['balance']),
            sprintf('%01.2f', $row['totalbalance']),
            moneyf($row['balance']),
            moneyf($row['totalbalance']),
            $row['pin'],
            $row['id'],
        ),
        $data
    );
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

    $data = $LMS->getLastNInTable($data, $row['id'], $format, $row['aggregate_documents']);

    // invoices, debit notes, documents
    $data = str_replace(
        array(
            '%invoice',
            '%number',
            '%value',
            '%cdate-y',
            '%cdate-m',
            '%cdate-d',
            '%lastday',
        ),
        array(
            $row['doc_number'],
            $row['doc_number'],
            moneyf($row['value'], $row['currency']),
            strftime('%Y', $row['cdate']),
            strftime('%m', $row['cdate']),
            strftime('%d', $row['cdate']),
            strftime("%d", mktime(12, 0, 0, $now_m + 1, 0, $now_y)),
        ),
        $data
    );

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
            $subject,
            $template,
            $content_types[$type]
        )
    );
    return $DB->GetLastInsertID('messages');
}

function send_mail($msgid, $cid, $rmail, $rname, $subject, $body)
{
    global $LMS, $mail_from, $notify_email, $reply_email, $dsn_email, $mdn_email, $content_types;
    global $smtp_options, $interval;

    $DB = LMSDB::getInstance();

    $DB->Execute(
        "INSERT INTO messageitems
        (messageid, customerid, destination, status)
        VALUES (?, ?, ?, ?)",
        array($msgid, $cid, $rmail, 1)
    );
    $msgitemid = $DB->GetLastInsertID('messageitems');

    $headers = array(
        'From' => empty($dsn_email) ? $mail_from : $dsn_email,
        'To' => qp_encode($rname) . " <$rmail>",
        'Recipient-Name' => $rname,
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

    $result = $LMS->SendMail($rmail, $headers, $body, null, null, $smtp_options);

    $query = "UPDATE messageitems
        SET status = ?, lastdate = ?NOW?, error = ?
        WHERE messageid = ? AND customerid = ? AND id = ?";

    if (is_string($result)) {
        $DB->Execute($query, array(MSG_ERROR, $result, $msgid, $cid, $msgitemid));
        fprintf(STDERR, trans('Error sending mail: $a', $result) . PHP_EOL);
    } else { // MSG_SENT
        $DB->Execute($query, array($result, null, $msgid, $cid, $msgitemid));
    }

    if (isset($interval) && !empty($interval)) {
        if ($interval == -1) {
            $delay = mt_rand(500, 5000);
        } else {
            $delay = intval($interval);
        }
        usleep($delay * 1000);
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

    $result = $LMS->SendSMS(str_replace(' ', '', $phone), $data, $msgitemid, $sms_options);
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
    $result = $LMS->SendMail($rmail, $headers, $body, null, null, $smtp_options);
}

function send_sms_to_user($phone, $data)
{
    global $LMS;

    $result = $LMS->SendSMS(str_replace(' ', '', $phone), $data);

    if (is_string($result)) {
        fprintf(STDERR, trans('Error sending SMS: $a', $result) . PHP_EOL);
    }
}

// prepare customergroups in sql query
if (isset($options['customergroups'])) {
    $customergroups = $options['customergroups'];
}
if (!empty($customergroups)) {
    $ORs = preg_split("/([\s]+|[\s]*,[\s]*)/", mb_strtoupper($customergroups), -1, PREG_SPLIT_NO_EMPTY);
    $customergroup_ORs = array();
    foreach ($ORs as $OR) {
        $ANDs = preg_split("/([\s]*\+[\s]*)/", $OR, -1, PREG_SPLIT_NO_EMPTY);
        $customergroup_ANDs_regular = array();
        $customergroup_ANDs_inversed = array();
        foreach ($ANDs as $AND) {
            if (strpos($AND, '!') === false) {
                $customergroup_ANDs_regular[] = $AND;
            } else {
                $customergroup_ANDs_inversed[] = substr($AND, 1);
            }
        }
        $customergroup_ORs[] = '('
            . (empty($customergroup_ANDs_regular) ? '1 = 1' : "EXISTS (SELECT COUNT(*) FROM customergroups
                JOIN vcustomerassignments ON vcustomerassignments.customergroupid = customergroups.id
                WHERE vcustomerassignments.customerid = c.id
                AND UPPER(customergroups.name) IN ('" . implode("', '", $customergroup_ANDs_regular) . "')
                HAVING COUNT(*) = " . count($customergroup_ANDs_regular) . ')')
            . (empty($customergroup_ANDs_inversed) ? '' : " AND NOT EXISTS (SELECT COUNT(*) FROM customergroups
                JOIN vcustomerassignments ON vcustomerassignments.customergroupid = customergroups.id
                WHERE vcustomerassignments.customerid = c.id
                AND UPPER(customergroups.name) IN ('" . implode("', '", $customergroup_ANDs_inversed) . "')
                HAVING COUNT(*) > 0)")
            . ')';
    }
    $customergroups = ' AND (' . implode(' OR ', $customergroup_ORs) . ')';
}

if (empty($allowed_customer_status)) {
    $customer_status_condition = '';
} else {
    $customer_status_condition = ' AND c.status IN (' . implode(',', $allowed_customer_status) . ')';
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
        WHERE deleted = 0 AND access = 1
            AND accessfrom <= ?NOW? AND (accessto = 0 OR accessto >= ?NOW?)
            AND ntype & ? > 0 AND (email <> '' OR phone <> '')",
        array(MSG_MAIL, MSG_SMS, MSG_MAIL | MSG_SMS)
    );
    $time = intval(strtotime('now') - strtotime('today'));
    $subject = $notifications['timetable']['subject'];
    $today = date('Y/m/d', $daystart);
    foreach ($users as $user) {
        if (empty($user['email']) && empty($user['phone'])) {
            continue;
        }

        $contents = '';
        $events = $DB->GetAll(
            "SELECT DISTINCT title, description, note, date, begintime, enddate, endtime,
            customerid, UPPER(lastname) AS lastname, c.name AS name, address
            FROM events
            LEFT JOIN customeraddressview c ON (c.id = customerid)
            LEFT JOIN eventassignments ON (events.id = eventassignments.eventid)
            WHERE closed = 0
                AND date <= ? AND enddate + 86400 >= ?
                AND begintime <= ? AND (endtime = 0 OR endtime >= ?)
                AND ((private = 1 AND (events.userid = ? OR eventassignments.userid = ?))
                    OR (private = 0 AND eventassignments.userid = ?)
                    OR (private = 0 AND eventassignments.userid IS NULL)
                )
            ORDER BY begintime",
            array(
                $daystart,
                $dayend,
                $time,
                $time,
                $user['id'],
                $user['id'],
                $user['id']
            )
        );

        if (!empty($events)) {
            $mail_contents = trans('Timetable for today') . ': ' . $today . PHP_EOL;
            $sms_contents = trans('Timetable for today') . ': ' . $today . ', ';
            foreach ($events as $event) {
                $mail_contents .= "----------------------------------------------------------------------------" . PHP_EOL;

                if ($event['endtime'] == 86400) {
                    $mail_contents .= trans('whole day');
                    $sms_contents .= trans('whole day');
                } else {
                    $begintime = sprintf("%02d:%02d", floor($event['begintime'] / 3600), floor(($event['begintime'] % 3600) / 60));
                    $mail_contents .= trans('Time:') . "\t" . $begintime;
                    $sms_contents .= trans('Time:') . ' ' . $begintime;
                    if ($event['endtime'] != 0 && $event['begintime'] != $event['endtime']) {
                        $endtime = sprintf("%02d:%02d", floor($event['endtime'] / 3600), floor(($event['endtime'] % 3600) / 60));
                        $mail_contents .= ' - ' . $endtime;
                        $sms_contents .= ' - ' . $endtime;
                    }
                    if ($event['date'] != $event['enddate']) {
                        $mail_contents .= ' ' . trans('(multi day)');
                        $sms_contents .= ' ' . trans('(multi day)');
                    }
                }

                $mail_contents .= PHP_EOL;
                $sms_contents .= ': ';
                $mail_contents .= trans('Title:') . "\t" . $event['title'] . PHP_EOL;
                $sms_contents .= $event['title'];
                $mail_contents .= trans('Description:') . "\t" . $event['description'] . PHP_EOL;
                $sms_contents .= ' (' . $event['description'] . ')';
                $mail_contents .= trans('Note:') . "\t" . $event['note'] . PHP_EOL;
                $sms_contents .= ' (' . $event['note'] . ')';
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
                $sms_contents .= ' ';
            }

            if (!empty($user['email']) && in_array('mail', $channels)) {
                if (!$quiet) {
                    printf("[timetable/mail] %s (%04d): %s" . PHP_EOL, $user['name'], $user['id'], $user['email']);
                }
                if (!$debug) {
                    send_mail_to_user($user['email'], $user['name'], $subject, $mail_contents);
                }
            }
            if (!empty($user['phone']) && in_array('sms', $channels)) {
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
            b.balance, m.email, x.phone, d.number, n.template, d.cdate, d.confirmdate AS deadline
        FROM customeraddressview c
        LEFT JOIN (
            SELECT customerid, SUM(value * currencyvalue) AS balance FROM cash
            GROUP BY customerid
        ) b ON b.customerid = c.id
        JOIN documents d ON d.customerid = c.id
        LEFT JOIN numberplans n ON (d.numberplanid = n.id)
        JOIN documentcontents dc ON dc.docid = d.id
        LEFT JOIN (SELECT " . $DB->GroupConcat('contact') . " AS email, customerid
            FROM customercontacts
            WHERE (type & ?) = ?
            GROUP BY customerid
        ) m ON (m.customerid = c.id) " . ($ignore_customer_consents ? '' : 'AND c.mailingnotice = 1') . "
        LEFT JOIN (SELECT " . $DB->GroupConcat('contact') . " AS phone, customerid
            FROM customercontacts
            WHERE (type & ?) = ?
            GROUP BY customerid
        ) x ON (x.customerid = c.id) " . ($ignore_customer_consents ? '' : 'AND c.smsnotice = 1') . "
        WHERE 1 = 1" . $customer_status_condition . " AND d.type IN (?, ?) AND d.closed = 0
            AND d.confirmdate >= $daystart + ? * 86400
            AND d.confirmdate < $daystart + (? + 1) * 86400"
            . ($customerid ? ' AND c.id = ' . $customerid : '')
            . ($divisionid ? ' AND c.divisionid = ' . $divisionid : '')
            . ($notifications['documents']['deleted_customers'] ? '' : ' AND c.deleted = 0')
            . ($customergroups ?: ''),
        array(
            $checked_mail_contact_flags,
            $required_mail_contact_flags,
            $checked_phone_contact_flags,
            $required_phone_contact_flags,
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
            $row['aggregate_documents'] = $notifications['documents']['aggregate_documents'];

            $row['paytime'] = $days;

            $row['doc_number'] = docnumber(array(
                'number' => $row['number'],
                'template' => $row['template'] ?: '%N/LMS/%Y',
                'cdate' => $row['cdate'],
                'customerid' => $row['id'],
            ));

            unset($message, $message_html, $message_text);
            if ($format == $mail_format) {
                $message = parse_customer_data($notifications['documents']['message'], $format, $row);
            } else {
                $message_html = parse_customer_data($notifications['documents']['message'], 'html', $row);
                $message_text = parse_customer_data($notifications['documents']['message'], 'text', $row);
            }
            $subject = parse_customer_data($notifications['documents']['subject'], 'text', $row);

            $recipient_name = $row['lastname'] . ' ' . $row['name'];

            if (empty($row['email'])) {
                $recipient_mails = null;
            } else {
                $recipient_mails = explode(',', $debug_email ?: trim($row['email']));
            }
            if (empty($row['phone'])) {
                $recipient_phones = null;
            } else {
                $recipient_phones = explode(',', $debug_phone ?: trim($row['phone']));
            }

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
                    $msgid = create_message(
                        MSG_MAIL,
                        $subject,
                        isset($message) ? $message : ($mail_format == 'html' ? $message_html : $message_text)
                    );
                    foreach ($recipient_mails as $recipient_mail) {
                        send_mail(
                            $msgid,
                            $row['id'],
                            $recipient_mail,
                            $recipient_name,
                            $subject,
                            isset($message) ? $message : ($mail_format == 'html' ? $message_html : $message_text)
                        );
                    }
                }
                if (in_array('sms', $channels) && !empty($recipient_phones)) {
                    $msgid = create_message(
                        MSG_SMS,
                        $subject,
                        isset($message) ? $message : $message_text
                    );
                    foreach ($recipient_phones as $recipient_phone) {
                        send_sms(
                            $msgid,
                            $row['id'],
                            $recipient_phone,
                            isset($message) ? $message : $message_text
                        );
                    }
                }
                if (in_array('userpanel', $channels)) {
                    $msgid = create_message(
                        MSG_USERPANEL,
                        $subject,
                        isset($message) ? $message : ($format == 'html' ? $message_html : $message_text)
                    );
                    send_to_userpanel($msgid, $row['id'], trans('userpanel'));
                }
                if (in_array('userpanel-urgent', $channels)) {
                    $msgid = create_message(
                        MSG_USERPANEL_URGENT,
                        $subject,
                        isset($message) ? $message : ($format == 'html' ? $message_html : $message_text)
                    );
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
        FROM customeraddressview c
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
                SELECT DISTINCT customerid, dc.todate AS dateto FROM documents
                JOIN documentcontents dc ON dc.docid = documents.id
                WHERE dc.todate >= $daystart + $days * 86400 AND dc.todate < $daystart + ($days + 1) * 86400
                    AND documents.type IN (" . DOC_CONTRACT . ',' . DOC_ANNEX . ")
            ) d ON d.customerid = c.id") . "
        LEFT JOIN (SELECT " . $DB->GroupConcat('contact') . " AS email, customerid
            FROM customercontacts
            WHERE (type & ?) = ?
            GROUP BY customerid
        ) m ON (m.customerid = c.id) " . ($ignore_customer_consents ? '' : 'AND c.mailingnotice = 1') . "
        LEFT JOIN (SELECT " . $DB->GroupConcat('contact') . " AS phone, customerid
            FROM customercontacts
            WHERE (type & ?) = ?
            GROUP BY customerid
        ) x ON (x.customerid = c.id) " . ($ignore_customer_consents ? '' : 'AND c.smsnotice = 1') . "
        WHERE "
            . ($expiration_type == 'assignments' ? '1 = 1' : 'NOT EXISTS (SELECT 1 FROM documents d2 WHERE d2.reference = d.id AND d2.type < 0)')
            . $customer_status_condition . " AND d.dateto >= $daystart + ? * 86400 AND d.dateto < $daystart + (? + 1) * 86400"
            . ($customerid ? ' AND c.id = ' . $customerid : '')
            . ($divisionid ? ' AND c.divisionid = ' . $divisionid : '')
            . ($notifications['contracts']['deleted_customers'] ? '' : ' AND c.deleted = 0')
            . ($customergroups ?: '')
        . " GROUP BY c.id, c.pin, c.lastname, c.name, d.dateto, m.email, x.phone",
        array(
            $checked_mail_contact_flags,
            $required_mail_contact_flags,
            $checked_phone_contact_flags,
            $required_phone_contact_flags,
            $days,
            $days
        )
    );

    if (!empty($customers)) {
        $notifications['contracts']['customers'] = array();
        foreach ($customers as $row) {
            $notifications['contracts']['customers'][] = $row['id'];
            $row['aggregate_documents'] = $notifications['contracts']['aggregate_documents'];

            unset($message, $message_html, $message_text);
            if ($format == $mail_format) {
                $message = parse_customer_data($notifications['contracts']['message'], $format, $row);
            } else {
                $message_html = parse_customer_data($notifications['contracts']['message'], 'html', $row);
                $message_text = parse_customer_data($notifications['contracts']['message'], 'text', $row);
            }
            $subject = parse_customer_data($notifications['contracts']['subject'], 'text', $row);

            $recipient_name = $row['lastname'] . ' ' . $row['name'];

            if (empty($row['email'])) {
                $recipient_mails = null;
            } else {
                $recipient_mails = explode(',', $debug_email ?: trim($row['email']));
            }
            if (empty($row['phone'])) {
                $recipient_phones = null;
            } else {
                $recipient_phones = explode(',', $debug_phone ?: trim($row['phone']));
            }

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
                    $msgid = create_message(
                        MSG_MAIL,
                        $subject,
                        isset($message) ? $message : ($mail_format == 'html' ? $message_html : $message_text)
                    );
                    foreach ($recipient_mails as $recipient_mail) {
                        send_mail(
                            $msgid,
                            $row['id'],
                            $recipient_mail,
                            $recipient_name,
                            $subject,
                            isset($message) ? $message : ($mail_format == 'html' ? $message_html : $message_text)
                        );
                    }
                }
                if (in_array('sms', $channels) && !empty($recipient_phones)) {
                    $msgid = create_message(
                        MSG_SMS,
                        $subject,
                        isset($message) ? $message : $message_text
                    );
                    foreach ($recipient_phones as $recipient_phone) {
                        send_sms(
                            $msgid,
                            $row['id'],
                            $recipient_phone,
                            isset($message) ? $message : $message_text
                        );
                    }
                }
                if (in_array('userpanel', $channels)) {
                    $msgid = create_message(
                        MSG_USERPANEL,
                        $subject,
                        isset($message) ? $message : ($format == 'html' ? $message_html : $message_text)
                    );
                    send_to_userpanel($msgid, $row['id'], trans('userpanel'));
                }
                if (in_array('userpanel-urgent', $channels)) {
                    $msgid = create_message(
                        MSG_USERPANEL_URGENT,
                        $subject,
                        isset($message) ? $message : ($format == 'html' ? $message_html : $message_text)
                    );
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
        FROM customeraddressview c
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
        ) m ON (m.customerid = c.id) " . ($ignore_customer_consents ? '' : 'AND c.mailingnotice = 1') . "
        LEFT JOIN (SELECT " . $DB->GroupConcat('contact') . " AS phone, customerid
            FROM customercontacts
            WHERE (type & ?) = ?
            GROUP BY customerid
        ) x ON (x.customerid = c.id) " . ($ignore_customer_consents ? '' : 'AND c.smsnotice = 1') . "
        WHERE 1 = 1" . $customer_status_condition
            . " AND c.cutoffstop < $currtime AND b2.balance " . ($limit > 0 ? '>' : '<') . " ?"
            . ($customerid ? ' AND c.id = ' . $customerid : '')
            . ($divisionid ? ' AND c.divisionid = ' . $divisionid : '')
            . ($notifications['debtors']['deleted_customers'] ? '' : ' AND c.deleted = 0')
            . ($customergroups ?: '')
        . ' ORDER BY c.id',
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
            $checked_mail_contact_flags,
            $required_mail_contact_flags,
            $checked_phone_contact_flags,
            $required_phone_contact_flags,
            $limit
        )
    );

    if (!empty($customers)) {
        $count = count($customers);
        if (!empty($part_size)) {
            if (preg_match('/^(?<percent>[0-9]+)%$/', $part_size, $m)) {
                $percent = intval($m['percent']);
                if ($percent < 1 || $percent > 99) {
                    $start_idx = 0;
                    $end_idx = $count;
                } else {
                    $part_size = ceil(($percent * $count) / 100);
                    $part_offset = $part_number * $part_size;
                    if ((!$part_offset && $part_number) || $part_offset >= $count) {
                        $start_idx = $part_offset;
                        $end_idx = $part_offset - 1;
                    } else {
                        $start_idx = $part_offset;
                        $end_idx = $part_offset + ($part_size ?: $count) - 1;
                    }
                }
            } else {
                $start_idx = $part_offset;
                $end_idx = $start_idx + $part_size - 1;
            }
        } else {
            $start_idx = 0;
            $end_idx = $count;
        }

        $notifications['debtors']['customers'] = array();
        $idx = 0;
        foreach ($customers as $row) {
            $notifications['debtors']['customers'][] = $row['id'];
            $row['aggregate_documents'] = $notifications['debtors']['aggregate_documents'];

            unset($message, $message_html, $message_text);
            if ($format == $mail_format) {
                $message = parse_customer_data($notifications['debtors']['message'], $format, $row);
            } else {
                $message_html = parse_customer_data($notifications['debtors']['message'], 'html', $row);
                $message_text = parse_customer_data($notifications['debtors']['message'], 'text', $row);
            }
            $subject = parse_customer_data($notifications['debtors']['subject'], 'text', $row);

            $recipient_name = $row['lastname'] . ' ' . $row['name'];

            if (empty($row['email'])) {
                $recipient_mails = null;
            } else {
                $recipient_mails = explode(',', $debug_email ?: trim($row['email']));
            }
            if (empty($row['phone'])) {
                $recipient_phones = null;
            } else {
                $recipient_phones = explode(',', $debug_phone ?: trim($row['phone']));
            }

            if (!$quiet) {
                if (in_array('mail', $channels) && !empty($recipient_mails)) {
                    if ($idx >= $start_idx && $idx <= $end_idx) {
                        foreach ($recipient_mails as $recipient_mail) {
                            printf(
                                "[mail/debtors] %s (%04d): %s" . PHP_EOL,
                                $recipient_name,
                                $row['id'],
                                $recipient_mail
                            );
                        }
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
                    if ($idx >= $start_idx && $idx <= $end_idx) {
                        $msgid = create_message(
                            MSG_MAIL,
                            $subject,
                            isset($message) ? $message : ($mail_format == 'html' ? $message_html : $message_text)
                        );
                        foreach ($recipient_mails as $recipient_mail) {
                            send_mail(
                                $msgid,
                                $row['id'],
                                $recipient_mail,
                                $recipient_name,
                                $subject,
                                isset($message) ? $message : ($mail_format == 'html' ? $message_html : $message_text)
                            );
                        }
                    }
                }
                if (in_array('sms', $channels) && !empty($recipient_phones)) {
                    $msgid = create_message(
                        MSG_SMS,
                        $subject,
                        isset($message) ? $message : $message_text
                    );
                    foreach ($recipient_phones as $recipient_phone) {
                        send_sms(
                            $msgid,
                            $row['id'],
                            $recipient_phone,
                            isset($message) ? $message : $message_text
                        );
                    }
                }
                if (in_array('userpanel', $channels)) {
                    $msgid = create_message(
                        MSG_USERPANEL,
                        $subject,
                        isset($message) ? $message : ($format == 'html' ? $message_html : $message_text)
                    );
                    send_to_userpanel($msgid, $row['id'], trans('userpanel'));
                }
                if (in_array('userpanel-urgent', $channels)) {
                    $msgid = create_message(
                        MSG_USERPANEL_URGENT,
                        $subject,
                        isset($message) ? $message : ($format == 'html' ? $message_html : $message_text)
                    );
                    send_to_userpanel($msgid, $row['id'], trans('userpanel urgent'));
                }
            }

            $idx++;
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
        JOIN customeraddressview c ON (c.id = d.customerid)
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
        ) m ON (m.customerid = c.id) " . ($ignore_customer_consents ? '' : 'AND c.mailingnotice = 1') . "
        LEFT JOIN (SELECT " . $DB->GroupConcat('contact') . " AS phone, customerid
            FROM customercontacts
            WHERE (type & ?) = ?
            GROUP BY customerid
        ) x ON (x.customerid = c.id) " . ($ignore_customer_consents ? '' : 'AND c.smsnotice = 1') . "
        JOIN (
            SELECT SUM(value) * -1 AS value, currency, docid
            FROM cash
            GROUP BY docid, currency
        ) v ON (v.docid = d.id)
        LEFT JOIN numberplans n ON (d.numberplanid = n.id)
        WHERE 1 = 1" . $customer_status_condition . " AND d.type IN (?, ?, ?) AND d.closed = 0 AND b2.balance < ?
            AND (d.cdate + (d.paytime - ? + 1) * 86400) >= $daystart
            AND (d.cdate + (d.paytime - ? + 1) * 86400) < $dayend"
            . ($customerid ? ' AND c.id = ' . $customerid : '')
            . ($divisionid ? ' AND c.divisionid = ' . $divisionid : '')
            . ($notifications['reminder']['deleted_customers'] ? '' : ' AND c.deleted = 0')
            . ($customergroups ?: '')
            . ' ORDER BY d.id',
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
            $checked_phone_contact_flags,
            $required_phone_contact_flags,
            DOC_INVOICE,
            DOC_INVOICE_PRO,
            DOC_DNOTE,
            $limit,
            $days,
            $days
        )
    );
    if (!empty($documents)) {
        $count = count($documents);
        if (!empty($part_size)) {
            if (preg_match('/^(?<percent>[0-9]+)%$/', $part_size, $m)) {
                $percent = intval($m['percent']);
                if ($percent < 1 || $percent > 99) {
                    $start_idx = 0;
                    $end_idx = $count;
                } else {
                    $part_size = ceil(($percent * $count) / 100);
                    $part_offset = $part_number * $part_size;
                    if ((!$part_offset && $part_number) || $part_offset >= $count) {
                        $start_idx = $part_offset;
                        $end_idx = $part_offset - 1;
                    } else {
                        $start_idx = $part_offset;
                        $end_idx = $part_offset + ($part_size ?: $count) - 1;
                    }
                }
            } else {
                $start_idx = $part_offset;
                $end_idx = $start_idx + $part_size - 1;
            }
        } else {
            $start_idx = 0;
            $end_idx = $count;
        }

        $notifications['reminder']['customers'] = array();
        $idx = 0;
        foreach ($documents as $row) {
            $notifications['reminder']['customers'][] = $row['id'];
            $row['doc_number'] = docnumber(array(
                'number' => $row['number'],
                'template' => ($row['template'] ? $row['template'] : '%N/LMS/%Y'),
                'cdate' => $row['cdate'],
                'customerid' => $row['id'],
            ));

            $row['aggregate_documents'] = $notifications['reminder']['aggregate_documents'];

            unset($message, $message_html, $message_text);
            if ($format == $mail_format) {
                $message = parse_customer_data($notifications['reminder']['message'], $format, $row);
            } else {
                $message_html = parse_customer_data($notifications['reminder']['message'], 'html', $row);
                $message_text = parse_customer_data($notifications['reminder']['message'], 'text', $row);
            }
            $subject = parse_customer_data($notifications['reminder']['subject'], 'text', $row);

            if (empty($row['email'])) {
                $recipient_mails = null;
            } else {
                $recipient_mails = explode(',', $debug_email ?: trim($row['email']));
            }
            if (empty($row['phone'])) {
                $recipient_phones = null;
            } else {
                $recipient_phones = explode(',', $debug_phone ?: trim($row['phone']));
            }

            if (!$quiet) {
                if (in_array('mail', $channels) && !empty($recipient_mails)) {
                    if ($idx >= $start_idx && $idx <= $end_idx) {
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
                    if ($idx >= $start_idx && $idx <= $end_idx) {
                        $msgid = create_message(
                            MSG_MAIL,
                            $subject,
                            isset($message) ? $message : ($mail_format == 'html' ? $message_html : $message_text)
                        );
                        foreach ($recipient_mails as $recipient_mail) {
                            send_mail(
                                $msgid,
                                $row['id'],
                                $recipient_mail,
                                $row['name'],
                                $subject,
                                isset($message) ? $message : ($mail_format == 'html' ? $message_html : $message_text)
                            );
                        }
                    }
                }
                if (in_array('sms', $channels) && !empty($recipient_phones)) {
                    $msgid = create_message(
                        MSG_SMS,
                        $subject,
                        isset($message) ? $message : $message_text
                    );
                    foreach ($recipient_phones as $recipient_phone) {
                        send_sms(
                            $msgid,
                            $row['id'],
                            $recipient_phone,
                            isset($message) ? $message : $message_text
                        );
                    }
                }
                if (in_array('userpanel', $channels)) {
                    $msgid = create_message(
                        MSG_USERPANEL,
                        $subject,
                        isset($message) ? $message : ($format == 'html' ? $message_html : $message_text)
                    );
                    send_to_userpanel($msgid, $row['id'], trans('userpanel'));
                }
                if (in_array('userpanel-urgent', $channels)) {
                    $msgid = create_message(
                        MSG_USERPANEL_URGENT,
                        $subject,
                        isset($message) ? $message : ($format == 'html' ? $message_html : $message_text)
                    );
                    send_to_userpanel($msgid, $row['id'], trans('userpanel urgent'));
                }
            }

            $idx++;
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
        JOIN customeraddressview c ON c.id = cash.customerid
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
        ) m ON (m.customerid = c.id) " . ($ignore_customer_consents ? '' : 'AND c.mailingnotice = 1') . "
        LEFT JOIN (SELECT " . $DB->GroupConcat('contact') . " AS phone, customerid
            FROM customercontacts
            WHERE (type & ?) = ?
            GROUP BY customerid
        ) x ON (x.customerid = c.id) " . ($ignore_customer_consents ? '' : 'AND c.smsnotice = 1') . "
        WHERE 1 = 1" . $customer_status_condition
            . " AND cash.type = 1 AND cash.value > 0 AND cash.time >= $daystart + (? * 86400) AND cash.time < $daystart + (? + 1) * 86400"
            . ($customerid ? ' AND c.id = ' . $customerid : '')
            . ($divisionid ? ' AND c.divisionid = ' . $divisionid : '')
            . ($notifications['income']['deleted_customers'] ? '' : ' AND c.deleted = 0')
            . ($customergroups ?: ''),
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
            $checked_phone_contact_flags,
            $required_phone_contact_flags,
            $days,
            $days,
        )
    );

    if (!empty($incomes)) {
        $notifications['income']['customers'] = array();
        foreach ($incomes as $row) {
            $notifications['income']['customers'][] = $row['id'];

            $row['aggregate_documents'] = $notifications['income']['aggregate_documents'];

            unset($message, $message_html, $message_text);
            if ($format == $mail_format) {
                $message = parse_customer_data($notifications['income']['message'], $format, $row);
            } else {
                $message_html = parse_customer_data($notifications['income']['message'], 'html', $row);
                $message_text = parse_customer_data($notifications['income']['message'], 'text', $row);
            }
            $subject = parse_customer_data($notifications['income']['subject'], 'text', $row);

            if (empty($row['email'])) {
                $recipient_mails = null;
            } else {
                $recipient_mails = explode(',', $debug_email ?: trim($row['email']));
            }
            if (empty($row['phone'])) {
                $recipient_phones = null;
            } else {
                $recipient_phones = explode(',', $debug_phone ?: trim($row['phone']));
            }

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
                    $msgid = create_message(
                        MSG_MAIL,
                        $subject,
                        isset($message) ? $message : ($mail_format == 'html' ? $message_html : $message_text)
                    );
                    foreach ($recipient_mails as $recipient_mail) {
                        send_mail(
                            $msgid,
                            $row['id'],
                            $recipient_mail,
                            $row['name'],
                            $subject,
                            isset($message) ? $message : ($mail_format == 'html' ? $message_html : $message_text)
                        );
                    }
                }
                if (in_array('sms', $channels) && !empty($recipient_phones)) {
                    $msgid = create_message(
                        MSG_SMS,
                        $subject,
                        isset($message) ? $message : $message_text
                    );
                    foreach ($recipient_phones as $recipient_phone) {
                        send_sms(
                            $msgid,
                            $row['id'],
                            $recipient_phone,
                            isset($message) ? $message : $message_text
                        );
                    }
                }
                if (in_array('userpanel', $channels)) {
                    $msgid = create_message(
                        MSG_USERPANEL,
                        $subject,
                        isset($message) ? $message : ($format == 'html' ? $message_html : $message_text)
                    );
                    send_to_userpanel($msgid, $row['id'], trans('userpanel'));
                }
                if (in_array('userpanel-urgent', $channels)) {
                    $msgid = create_message(
                        MSG_USERPANEL_URGENT,
                        $subject,
                        isset($message) ? $message : ($format == 'html' ? $message_html : $message_text)
                    );
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
        JOIN customeraddressview c ON (c.id = d.customerid)
        LEFT JOIN divisions ON divisions.id = c.divisionid
        LEFT JOIN (SELECT " . $DB->GroupConcat('contact') . " AS email, customerid
            FROM customercontacts
            WHERE (type & ?) = ?
            GROUP BY customerid
        ) m ON (m.customerid = c.id) " . ($ignore_customer_consents ? '' : 'AND c.mailingnotice = 1') . "
        LEFT JOIN (SELECT " . $DB->GroupConcat('contact') . " AS phone, customerid
            FROM customercontacts
            WHERE (type & ?) = ?
            GROUP BY customerid
        ) x ON (x.customerid = c.id) " . ($ignore_customer_consents ? '' : 'AND c.smsnotice = 1') . "
        JOIN (SELECT SUM(value) * -1 AS value, currency, docid
            FROM cash
            GROUP BY docid, currency
        ) v ON (v.docid = d.id)
        LEFT JOIN numberplans n ON (d.numberplanid = n.id)
        LEFT JOIN (SELECT SUM(value * currencyvalue) AS balance, customerid
            FROM cash
            GROUP BY customerid
        ) ca ON (ca.customerid = d.customerid)
        WHERE 1 = 1" . $customer_status_condition
            . " AND (c.invoicenotice IS NULL OR c.invoicenotice = 0) AND d.type IN (?, ?, ?)
            AND d.cdate >= ? AND d.cdate <= ?"
            . ($customerid ? ' AND c.id = ' . $customerid : '')
            . ($divisionid ? ' AND c.divisionid = ' . $divisionid : '')
            . ($notifications['invoices']['deleted_customers'] ? '' : ' AND c.deleted = 0')
            . ($customergroups ?: '')
        . ' ORDER BY d.id',
        array(
            CONTACT_EMAIL | CONTACT_INVOICES | CONTACT_NOTIFICATIONS | CONTACT_DISABLED,
            CONTACT_EMAIL | CONTACT_INVOICES | CONTACT_NOTIFICATIONS,
            $checked_phone_contact_flags,
            $required_phone_contact_flags,
            DOC_INVOICE,
            DOC_INVOICE_PRO,
            DOC_CNOTE,
            $daystart,
            $dayend
        )
    );

    if (!empty($documents)) {
        $count = count($documents);
        if (!empty($part_size)) {
            if (preg_match('/^(?<percent>[0-9]+)%$/', $part_size, $m)) {
                $percent = intval($m['percent']);
                if ($percent < 1 || $percent > 99) {
                    $start_idx = 0;
                    $end_idx = $count;
                } else {
                    $part_size = ceil(($percent * $count) / 100);
                    $part_offset = $part_number * $part_size;
                    if ((!$part_offset && $part_number) || $part_offset >= $count) {
                        $start_idx = $part_offset;
                        $end_idx = $part_offset - 1;
                    } else {
                        $start_idx = $part_offset;
                        $end_idx = $part_offset + ($part_size ?: $count) - 1;
                    }
                }
            } else {
                $start_idx = $part_offset;
                $end_idx = $start_idx + $part_size - 1;
            }
        } else {
            $start_idx = 0;
            $end_idx = $count;
        }

        $notifications['invoices']['customers'] = array();
        $idx = 0;
        foreach ($documents as $row) {
            $notifications['invoices']['customers'][] = $row['id'];
            $row['doc_number'] = docnumber(array(
                'number' => $row['number'],
                'template' => ($row['template'] ? $row['template'] : '%N/LMS/%Y'),
                'cdate' => $row['cdate'],
                'customerid' => $row['id'],
            ));

            $row['aggregate_documents'] = $notifications['invoices']['aggregate_documents'];

            unset($message, $message_html, $message_text);
            if ($format == $mail_format) {
                $message = parse_customer_data($notifications['invoices']['message'], $format, $row);
            } else {
                $message_html = parse_customer_data($notifications['invoices']['message'], 'html', $row);
                $message_text = parse_customer_data($notifications['invoices']['message'], 'text', $row);
            }
            $subject = parse_customer_data($notifications['invoices']['subject'], 'text', $row);

            if (empty($row['email'])) {
                $recipient_mails = null;
            } else {
                $recipient_mails = explode(',', $debug_email ?: trim($row['email']));
            }
            if (empty($row['phone'])) {
                $recipient_phones = null;
            } else {
                $recipient_phones = explode(',', $debug_phone ?: trim($row['phone']));
            }

            if (!$quiet) {
                if (in_array('mail', $channels) && !empty($recipient_mails)) {
                    if ($idx >= $start_idx && $idx <= $end_idx) {
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
                    if ($idx >= $start_idx && $idx <= $end_idx) {
                        $msgid = create_message(
                            MSG_MAIL,
                            $subject,
                            isset($message) ? $message : ($mail_format == 'html' ? $message_html : $message_text)
                        );
                        foreach ($recipient_mails as $recipient_mail) {
                            send_mail(
                                $msgid,
                                $row['id'],
                                $recipient_mail,
                                $row['name'],
                                $subject,
                                isset($message) ? $message : ($mail_format == 'html' ? $message_html : $message_text)
                            );
                        }
                    }
                }
                if (in_array('sms', $channels) && !empty($recipient_phones)) {
                    $msgid = create_message(
                        MSG_SMS,
                        $subject,
                        isset($message) ? $message : $message_text
                    );
                    foreach ($recipient_phones as $recipient_phone) {
                        send_sms(
                            $msgid,
                            $row['id'],
                            $recipient_phone,
                            isset($message) ? $message : $message_text
                        );
                    }
                }
                if (in_array('userpanel', $channels)) {
                    $msgid = create_message(
                        MSG_USERPANEL,
                        $subject,
                        isset($message) ? $message : ($format == 'html' ? $message_html : $message_text)
                    );
                    send_to_userpanel($msgid, $row['id'], trans('userpanel'));
                }
                if (in_array('userpanel-urgent', $channels)) {
                    $msgid = create_message(
                        MSG_USERPANEL_URGENT,
                        $subject,
                        isset($message) ? $message : ($format == 'html' ? $message_html : $message_text)
                    );
                    send_to_userpanel($msgid, $row['id'], trans('userpanel urgent'));
                }
            }

            $idx++;
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
        JOIN customeraddressview c ON (c.id = d.customerid)
        LEFT JOIN divisions ON divisions.id = c.divisionid
        LEFT JOIN (SELECT " . $DB->GroupConcat('contact') . " AS email, customerid
            FROM customercontacts
            WHERE (type & ?) = ?
            GROUP BY customerid
        ) m ON (m.customerid = c.id) " . ($ignore_customer_consents ? '' : 'AND c.mailingnotice = 1') . "
        LEFT JOIN (SELECT " . $DB->GroupConcat('contact') . " AS phone, customerid
            FROM customercontacts
            WHERE (type & ?) = ?
            GROUP BY customerid
        ) x ON (x.customerid = c.id) " . ($ignore_customer_consents ? '' : 'AND c.smsnotice = 1') . "
        JOIN (SELECT SUM(value) * -1 AS value, currency, docid
            FROM cash
            GROUP BY docid, currency
        ) v ON (v.docid = d.id)
        LEFT JOIN numberplans n ON (d.numberplanid = n.id)
        LEFT JOIN (SELECT SUM(value * currencyvalue) AS balance, customerid
            FROM cash
            GROUP BY customerid
        ) ca ON (ca.customerid = d.customerid)
        WHERE 1 = 1" . $customer_status_condition
            . " AND (c.invoicenotice IS NULL OR c.invoicenotice = 0) AND d.type = ?
            AND d.cdate >= ? AND d.cdate <= ?"
            . ($customerid ? ' AND c.id = ' . $customerid : '')
            . ($divisionid ? ' AND c.divisionid = ' . $divisionid : '')
            . ($notifications['notes']['deleted_customers'] ? '' : ' AND c.deleted = 0')
            . ($customergroups ?: '')
        . ' ORDER BY d.id',
        array(
            $checked_mail_contact_flags,
            $required_mail_contact_flags,
            $checked_phone_contact_flags,
            $required_phone_contact_flags,
            DOC_DNOTE,
            $daystart,
            $dayend
        )
    );
    if (!empty($documents)) {
        $count = count($documents);
        if (!empty($part_size)) {
            if (preg_match('/^(?<percent>[0-9]+)%$/', $part_size, $m)) {
                $percent = intval($m['percent']);
                if ($percent < 1 || $percent > 99) {
                    $start_idx = 0;
                    $end_idx = $count;
                } else {
                    $part_size = ceil(($percent * $count) / 100);
                    $part_offset = $part_number * $part_size;
                    if ((!$part_offset && $part_number) || $part_offset >= $count) {
                        $start_idx = $part_offset;
                        $end_idx = $part_offset - 1;
                    } else {
                        $start_idx = $part_offset;
                        $end_idx = $part_offset + ($part_size ?: $count) - 1;
                    }
                }
            } else {
                $start_idx = $part_offset;
                $end_idx = $start_idx + $part_size - 1;
            }
        } else {
            $start_idx = 0;
            $end_idx = $count;
        }

        $notifications['notes']['customers'] = array();
        foreach ($documents as $row) {
            $notifications['notes']['customers'][] = $row['id'];
            $row['doc_number'] = docnumber(array(
                'number' => $row['number'],
                'template' => ($row['template'] ? $row['template'] : '%N/LMS/%Y'),
                'cdate' => $row['cdate'],
                'customerid' => $row['id'],
            ));

            $row['aggregate_documents'] = $notifications['notes']['aggregate_documents'];

            unset($message, $message_html, $message_text);
            if ($format == $mail_format) {
                $message = parse_customer_data($notifications['notes']['message'], $format, $row);
            } else {
                $message_html = parse_customer_data($notifications['notes']['message'], 'html', $row);
                $message_text = parse_customer_data($notifications['notes']['message'], 'text', $row);
            }
            $subject = parse_customer_data($notifications['notes']['subject'], 'text', $row);

            if (empty($row['email'])) {
                $recipient_mails = null;
            } else {
                $recipient_mails = explode(',', $debug_email ?: trim($row['email']));
            }
            if (empty($row['phone'])) {
                $recipient_phones = null;
            } else {
                $recipient_phones = explode(',', $debug_phone ?: trim($row['phone']));
            }

            if (!$quiet) {
                if (in_array('mail', $channels) && !empty($recipient_mails)) {
                    if ($idx >= $start_idx && $idx <= $end_idx) {
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
                    if ($idx >= $start_idx && $idx <= $end_idx) {
                        $msgid = create_message(
                            MSG_MAIL,
                            $subject,
                            isset($message) ? $message : ($mail_format == 'html' ? $message_html : $message_text)
                        );
                        foreach ($recipient_mails as $recipient_mail) {
                            send_mail(
                                $msgid,
                                $row['id'],
                                $recipient_mail,
                                $row['name'],
                                $subject,
                                isset($message) ? $message : ($mail_format == 'html' ? $message_html : $message_text)
                            );
                        }
                    }
                }
                if (in_array('sms', $channels) && !empty($recipient_phones)) {
                    $msgid = create_message(
                        MSG_SMS,
                        $subject,
                        isset($message) ? $message : $message_text
                    );
                    foreach ($recipient_phones as $recipient_phone) {
                        send_sms(
                            $msgid,
                            $row['id'],
                            $recipient_phone,
                            isset($message) ? $message : $message_text
                        );
                    }
                }
                if (in_array('userpanel', $channels)) {
                    $msgid = create_message(
                        MSG_USERPANEL,
                        $subject,
                        isset($message) ? $message : ($format == 'html' ? $message_html : $message_text)
                    );
                    send_to_userpanel($msgid, $row['id'], trans('userpanel'));
                }
                if (in_array('userpanel-urgent', $channels)) {
                    $msgid = create_message(
                        MSG_USERPANEL_URGENT,
                        $subject,
                        isset($message) ? $message : ($format == 'html' ? $message_html : $message_text)
                    );
                    send_to_userpanel($msgid, $row['id'], trans('userpanel urgent'));
                }
            }

            $idx++;
        }
    }
}

// Customer birthdays
if (empty($types) || in_array('birthday', $types)) {
    $cmonth = date('m', $daystart);
    $customers = $DB->GetAll(
        "SELECT c.id, c.lastname, c.name, c.ssn, m.email, x.phone
        FROM customeraddressview c
        LEFT JOIN (SELECT " . $DB->GroupConcat('contact') . " AS email, customerid
            FROM customercontacts
            WHERE (type & ?) = ?
            GROUP BY customerid
        ) m ON (m.customerid = c.id) " . ($ignore_customer_consents ? '' : 'AND c.mailingnotice = 1') . "
        LEFT JOIN (SELECT " . $DB->GroupConcat('contact') . " AS phone, customerid
            FROM customercontacts
            WHERE (type & ?) = ?
            GROUP BY customerid
        ) x ON (x.customerid = c.id) " . ($ignore_customer_consents ? '' : 'AND c.smsnotice = 1') . "
        WHERE 1 = 1" . $customer_status_condition
        . ' AND ' . $DB->RegExp('c.ssn', '[0-9]{2}(' . $cmonth . '|' . sprintf('%02d', $cmonth + 20) . ')' . date('d', $daystart) . '[0-9]{5}')
        . ($customerid ? ' AND c.id = ' . $customerid : '')
        . ($notifications['birthday']['deleted_customers'] ? '' : ' AND c.deleted = 0')
        . ($customergroups ?: '')
        . ' ORDER BY c.id',
        array(
            $checked_mail_contact_flags,
            $required_mail_contact_flags,
            $checked_phone_contact_flags,
            $required_phone_contact_flags,
        )
    );
    if (!empty($customers)) {
        $count = count($customers);
        if (!empty($part_size)) {
            if (preg_match('/^(?<percent>[0-9]+)%$/', $part_size, $m)) {
                $percent = intval($m['percent']);
                if ($percent < 1 || $percent > 99) {
                    $start_idx = 0;
                    $end_idx = $count;
                } else {
                    $part_size = ceil(($percent * $count) / 100);
                    $part_offset = $part_number * $part_size;
                    if ((!$part_offset && $part_number) || $part_offset >= $count) {
                        $start_idx = $part_offset;
                        $end_idx = $part_offset - 1;
                    } else {
                        $start_idx = $part_offset;
                        $end_idx = $part_offset + ($part_size ?: $count) - 1;
                    }
                }
            } else {
                $start_idx = $part_offset;
                $end_idx = $start_idx + $part_size - 1;
            }
        } else {
            $start_idx = 0;
            $end_idx = $count;
        }

        $notifications['birthday']['customers'] = array();
        $idx = 0;
        foreach ($customers as $row) {
            $notifications['birthday']['customers'][] = $row['id'];

            $row['name'] = $row['lastname'] . ' ' . $row['name'];
            $year = intval(substr($row['ssn'], 0, 2));
            $month = intval(substr($row['ssn'], 2, 2));
            $row['age'] = round(date('Y') - (1900 + floor($month / 20) * 100) - $year);

            unset($message, $message_html, $message_text);
            if ($format == $mail_format) {
                $message = parse_customer_data($notifications['birthday']['message'], $format, $row);
            } else {
                $message_html = parse_customer_data($notifications['birthday']['message'], 'html', $row);
                $message_text = parse_customer_data($notifications['birthday']['message'], 'text', $row);
            }
            $subject = parse_customer_data($notifications['birthday']['subject'], 'text', $row);

            if (empty($row['email'])) {
                $recipient_mails = null;
            } else {
                $recipient_mails = explode(',', $debug_email ?: trim($row['email']));
            }
            if (empty($row['phone'])) {
                $recipient_phones = null;
            } else {
                $recipient_phones = explode(',', $debug_phone ?: trim($row['phone']));
            }

            if (!$quiet) {
                if (in_array('mail', $channels) && !empty($recipient_mails)) {
                    if ($idx >= $start_idx && $idx <= $end_idx) {
                        foreach ($recipient_mails as $recipient_mail) {
                            printf(
                                "[mail/birthday] %s (%04d) age %s: %s" . PHP_EOL,
                                $row['name'],
                                $row['id'],
                                $row['age'],
                                $recipient_mail
                            );
                        }
                    }
                }
                if (in_array('sms', $channels) && !empty($recipient_phones)) {
                    foreach ($recipient_phones as $recipient_phone) {
                        printf(
                            "[sms/birthday] %s (%04d) age %s: %s" . PHP_EOL,
                            $row['name'],
                            $row['id'],
                            $row['age'],
                            $recipient_phone
                        );
                    }
                }
                if (in_array('userpanel', $channels)) {
                    printf(
                        "[userpanel/birthday] %s (%04d): age %s" . PHP_EOL,
                        $row['name'],
                        $row['id'],
                        $row['age']
                    );
                }
                if (in_array('userpanel-urgent', $channels)) {
                    printf(
                        "[userpanel-urgent/birthday] %s (%04d): age %s" . PHP_EOL,
                        $row['name'],
                        $row['id'],
                        $row['age']
                    );
                }
            }

            if (!$debug) {
                if (in_array('mail', $channels) && !empty($recipient_mails)) {
                    if ($idx >= $start_idx && $idx <= $end_idx) {
                        $msgid = create_message(
                            MSG_MAIL,
                            $subject,
                            isset($message) ? $message : ($mail_format == 'html' ? $message_html : $message_text)
                        );
                        foreach ($recipient_mails as $recipient_mail) {
                            send_mail(
                                $msgid,
                                $row['id'],
                                $recipient_mail,
                                $row['name'],
                                $subject,
                                isset($message) ? $message : ($mail_format == 'html' ? $message_html : $message_text)
                            );
                        }
                    }
                }
                if (in_array('sms', $channels) && !empty($recipient_phones)) {
                    $msgid = create_message(
                        MSG_SMS,
                        $subject,
                        isset($message) ? $message : $message_text
                    );
                    foreach ($recipient_phones as $recipient_phone) {
                        send_sms(
                            $msgid,
                            $row['id'],
                            $recipient_phone,
                            isset($message) ? $message : $message_text
                        );
                    }
                }
                if (in_array('userpanel', $channels)) {
                    $msgid = create_message(
                        MSG_USERPANEL,
                        $subject,
                        isset($message) ? $message : ($format == 'html' ? $message_html : $message_text)
                    );
                    send_to_userpanel($msgid, $row['id'], trans('userpanel'));
                }
                if (in_array('userpanel-urgent', $channels)) {
                    $msgid = create_message(
                        MSG_USERPANEL_URGENT,
                        $subject,
                        isset($message) ? $message : ($format == 'html' ? $message_html : $message_text)
                    );
                    send_to_userpanel($msgid, $row['id'], trans('userpanel urgent'));
                }
            }

            $idx++;
        }
    }
}

// Node which warning flag has set
if (empty($types) || in_array('warnings', $types)) {
    $customers = $DB->GetAll(
        "SELECT c.id, (" . $DB->Concat('c.lastname', "' '", 'c.name') . ") AS name,
        c.pin, c.message, m.email, x.phone, divisions.account, COALESCE(ca.balance, 0) AS balance
        FROM customeraddressview c
        LEFT JOIN divisions ON divisions.id = c.divisionid
        LEFT JOIN (SELECT " . $DB->GroupConcat('contact') . " AS email, customerid
            FROM customercontacts
            WHERE (type & ?) = ?
            GROUP BY customerid
        ) m ON (m.customerid = c.id) " . ($ignore_customer_consents ? '' : 'AND c.mailingnotice = 1') . "
        LEFT JOIN (SELECT " . $DB->GroupConcat('contact') . " AS phone, customerid
            FROM customercontacts
            WHERE (type & ?) = ?
            GROUP BY customerid
        ) x ON (x.customerid = c.id) " . ($ignore_customer_consents ? '' : 'AND c.smsnotice = 1') . "
        LEFT JOIN (SELECT SUM(value * currencyvalue) AS balance, customerid
            FROM cash
            GROUP BY customerid
        ) ca ON (ca.customerid = c.id)
        WHERE 1 = 1" . $customer_status_condition
            . " AND c.id IN (SELECT DISTINCT ownerid FROM vnodes WHERE warning = 1)"
            . ($customerid ? ' AND c.id = ' . $customerid : '')
            . ($divisionid ? ' AND c.divisionid = ' . $divisionid : '')
            . ($notifications['warnings']['deleted_customers'] ? '' : ' AND c.deleted = 0')
            . ($customergroups ?: '')
        . ' ORDER BY c.id',
        array(
            $checked_mail_contact_flags,
            $required_mail_contact_flags,
            $checked_phone_contact_flags,
            $required_phone_contact_flags
        )
    );

    if (!empty($customers)) {
        $count = count($customers);
        if (!empty($part_size)) {
            if (preg_match('/^(?<percent>[0-9]+)%$/', $part_size, $m)) {
                $percent = intval($m['percent']);
                if ($percent < 1 || $percent > 99) {
                    $start_idx = 0;
                    $end_idx = $count;
                } else {
                    $part_size = ceil(($percent * $count) / 100);
                    $part_offset = $part_number * $part_size;
                    if ((!$part_offset && $part_number) || $part_offset >= $count) {
                        $start_idx = $part_offset;
                        $end_idx = $part_offset - 1;
                    } else {
                        $start_idx = $part_offset;
                        $end_idx = $part_offset + ($part_size ?: $count) - 1;
                    }
                }
            } else {
                $start_idx = $part_offset;
                $end_idx = $start_idx + $part_size - 1;
            }
        } else {
            $start_idx = 0;
            $end_idx = $count;
        }

        $notifications['warnings']['customers'] = array();
        $idx = 0;
        foreach ($customers as $row) {
            $notifications['warnings']['customers'][] = $row['id'];
            $row['aggregate_documents'] = $notifications['warnings']['aggregate_documents'];

            unset($message, $message_html, $message_text);
            if ($format == $mail_format) {
                $message = parse_customer_data($notifications['warnings']['message'], $format, $row);
            } else {
                $message_html = parse_customer_data($notifications['warnings']['message'], 'html', $row);
                $message_text = parse_customer_data($notifications['warnings']['message'], 'text', $row);
            }
            $subject = parse_customer_data($notifications['warnings']['subject'], 'text', $row);

            if (empty($row['email'])) {
                $recipient_mails = null;
            } else {
                $recipient_mails = explode(',', $debug_email ?: trim($row['email']));
            }
            if (empty($row['phone'])) {
                $recipient_phones = null;
            } else {
                $recipient_phones = explode(',', $debug_phone ?: trim($row['phone']));
            }

            if (!$quiet) {
                if (in_array('mail', $channels) && !empty($recipient_mails)) {
                    if ($idx >= $start_idx && $idx <= $end_idx) {
                        foreach ($recipient_mails as $recipient_mail) {
                            printf(
                                "[mail/warnings] %s (%04d): %s" . PHP_EOL,
                                $row['name'],
                                $row['id'],
                                $recipient_mail
                            );
                        }
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
                    if ($idx >= $start_idx && $idx <= $end_idx) {
                        $msgid = create_message(
                            MSG_MAIL,
                            $subject,
                            isset($message) ? $message : ($mail_format == 'html' ? $message_html : $message_text)
                        );
                        foreach ($recipient_mails as $recipient_mail) {
                            send_mail(
                                $msgid,
                                $row['id'],
                                $recipient_mail,
                                $row['name'],
                                $subject,
                                isset($message) ? $message : ($mail_format == 'html' ? $message_html : $message_text)
                            );
                        }
                    }
                }
                if (in_array('sms', $channels) && !empty($recipient_phones)) {
                    $msgid = create_message(
                        MSG_SMS,
                        $subject,
                        isset($message) ? $message : $message_text
                    );
                    foreach ($recipient_phones as $recipient_phone) {
                        send_sms(
                            $msgid,
                            $row['id'],
                            $recipient_phone,
                            isset($message) ? $message : $message_text
                        );
                    }
                }
                if (in_array('userpanel', $channels)) {
                    $msgid = create_message(
                        MSG_USERPANEL,
                        $subject,
                        isset($message) ? $message : ($format == 'html' ? $message_html : $message_text)
                    );
                    send_to_userpanel($msgid, $row['id'], trans('userpanel'));
                }
                if (in_array('userpanel-urgent', $channels)) {
                    $msgid = create_message(
                        MSG_USERPANEL_URGENT,
                        $subject,
                        isset($message) ? $message : ($format == 'html' ? $message_html : $message_text)
                    );
                    send_to_userpanel($msgid, $row['id'], trans('userpanel urgent'));
                }
            }

            $idx++;
        }
    }
}

// Events about customers should be notified if they are still opened
if (empty($types) || in_array('events', $types)) {
    $time = intval(strtotime('now') - strtotime('today'));
    $events = $DB->GetAll(
        "SELECT id, title, description, customerid, userid FROM events
        WHERE (customerid IS NOT NULL OR userid IS NOT NULL) AND closed = 0
            AND date <= ? AND enddate + 86400 >= ?
            AND begintime <= ? AND (endtime = 0 OR endtime >= ?)"
        . ($customerid ? ' AND customerid = ' . $customerid : ''),
        array($daystart, $dayend, $time, $time)
    );

    if (!empty($events)) {
        $customers = array();
        $users = $DB->GetAllByKey(
            "SELECT id, name, (CASE WHEN (ntype & ?) > 0 THEN email ELSE '' END) AS email,
                (CASE WHEN (ntype & ?) > 0 THEN phone ELSE '' END) AS phone FROM vusers
            WHERE deleted = 0 AND access = 1
                AND accessfrom <= ?NOW? AND (accessto = 0 OR accessto >= ?NOW?)
                AND ntype & ? > 0 AND (email <> '' OR phone <> '')
            ORDER BY id",
            'id',
            array(MSG_MAIL, MSG_SMS, MSG_MAIL | MSG_SMS)
        );

        $customer_message_pattern = $notifications['events']['message'] == 'events notification'
            ? '%description'
            : $notifications['events']['message'];
        $customer_subject_pattern = $notifications['events']['subject'] == 'events notification'
            ? '%title'
            : $notifications['events']['subject'];

        foreach ($events as $event) {
            $contacts = array();

            $message = $event['description'];
            $subject = $event['title'];

            $customer_message = str_replace(
                array(
                    '%title',
                    '%description',
                ),
                array(
                    $subject,
                    $message,
                ),
                $customer_message_pattern
            );

            $customer_subject = str_replace(
                array(
                    '%title',
                    '%description',
                ),
                array(
                    $subject,
                    $message,
                ),
                $customer_subject_pattern
            );

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
                        ) m ON (m.customerid = c.id) " . ($ignore_customer_consents ? '' : 'AND c.mailingnotice = 1') . "
                        LEFT JOIN (SELECT " . $DB->GroupConcat('contact') . " AS phone, customerid
                            FROM customercontacts
                            WHERE (type & ?) = ?
                            GROUP BY customerid
                        ) x ON (x.customerid = c.id) " . ($ignore_customer_consents ? '' : 'AND c.smsnotice = 1') . "
                        WHERE 1 = 1" . $customer_status_condition . " AND c.id = ?",
                        array(
                            $checked_mail_contact_flags,
                            $required_mail_contact_flags,
                            $checked_phone_contact_flags,
                            $required_phone_contact_flags,
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
                                'email' => $contact,
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
                                "[mail/events] Event #%d, %s (UID: #%d): %s" . PHP_EOL,
                                $event['id'],
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
                                "[sms/events] Event #%d, %s (UID: #%d): %s" . PHP_EOL,
                                $event['id'],
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
                                "[mail/events] Event #%d, %s (CID: #%d): %s" . PHP_EOL,
                                $event['id'],
                                $customers[$cid]['name'],
                                $cid,
                                $contact['email']
                            );
                            if (!$debug) {
                                $msgid = create_message(MSG_MAIL, $customer_subject, $customer_message);
                                send_mail(
                                    $msgid,
                                    $cid,
                                    $contact['email'],
                                    $customers[$cid]['name'],
                                    $customer_subject,
                                    $customer_message
                                );
                            }
                        }
                        if (in_array('sms', $channels) && array_key_exists('phone', $contact)) {
                            printf(
                                "[sms/events] Event #%d, %s (CID: #%d): %s" . PHP_EOL,
                                $event['id'],
                                $customers[$cid]['name'],
                                $cid,
                                $contact['phone']
                            );
                            if (!$debug) {
                                $msgid = create_message(MSG_SMS, $customer_subject, $customer_message);
                                send_sms($msgid, $cid, $contact['phone'], $customer_message);
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

    $nodes = $DB->GetAll(
        "SELECT INET_NTOA(ipaddr) AS ip
            FROM vnodes n
        JOIN customeraddressview c ON c.id = n.ownerid
        JOIN (SELECT DISTINCT customerid FROM messageitems
            JOIN messages m ON m.id = messageid
            WHERE type = ? AND status = ?
        ) m ON m.customerid = n.ownerid
        WHERE 1 = 1"
        . ($customerid ? ' AND n.ownerid = ' . $customerid : '')
        . ($divisionid ? ' AND c.divisionid = ' . $divisionid : '')
        . " ORDER BY ipaddr",
        array(
            MSG_WWW,
            MSG_NEW
        )
    );

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

                    $where = array();
                    foreach ($block_prechecks as $precheck => $precheck_params) {
                        switch ($precheck) {
                            case 'node-access':
                                $where[] = 'EXISTS (SELECT id FROM nodes WHERE nodes.ownerid = c.id AND access = 1)';
                                break;
                            case 'node-warning':
                                $where[] = 'EXISTS (SELECT id FROM nodes WHERE nodes.ownerid = c.id AND warning = 0)';
                                break;
                            case 'assignment-invoice':
                                switch (reset($precheck_params)) {
                                    case 'proforma':
                                        $target_doctype = DOC_INVOICE_PRO;
                                        break;
                                    case 'note':
                                        $target_doctype = DOC_DNOTE;
                                        break;
                                    case 'invoice':
                                    default:
                                        $target_doctype = DOC_INVOICE;
                                        break;
                                }
                                $where[] = 'EXISTS (SELECT id FROM assignments
                                    WHERE invoice <> ' . $target_doctype . ' AND (tariffid IS NOT NULL OR liabilityid IS NOT NULL)
                                        AND datefrom <= ?NOW? AND (dateto = 0 OR dateto >= ?NOW?)
                                        AND customerid = c.id)';
                                break;
                            case 'customer-status':
                                $target_cstatus = reset($precheck_params);
                                if (empty($target_cstatus)) {
                                    $target_cstatus = CSTATUS_DEBT_COLLECTION;
                                }
                                $where[] = 'EXISTS (SELECT id FROM customers
                                    WHERE status <> ' . $target_cstatus . ' AND customers.id = c.id)';
                                break;
                            case 'all-assignment-suspension':
                                $where[] = 'NOT EXISTS (SELECT id FROM assignments
                                    WHERE customerid = c.id AND tariffid IS NULL AND liabilityid IS NULL)';
                                break;
                            case 'customer-group':
                                $where[] = 'NOT EXISTS (
                                    SELECT ca.id FROM vcustomerassignments ca
                                    JOIN customergroups g ON g.id = ca.customergroupid
                                    WHERE ca.customerid = c.id AND LOWER(g.name) = LOWER(\'' . reset($precheck_params) . '\'))';
                                break;
                            case 'node-group':
                                $where[] = 'NOT EXISTS (
                                    SELECT nga.id FROM nodegroupassignments nga
                                    JOIN nodegroups g ON g.id = nga.nodegroupid
                                    JOIN nodes n ON n.id = nga.nodeid
                                    WHERE n.ownerid = c.id AND LOWER(g.name) = LOWER(\'' . reset($precheck_params) . '\'))';
                                break;
                        }
                    }

                    $customers = $DB->GetCol(
                        'SELECT c.id FROM customers c
                        WHERE c.id IN (' . implode(',', $customers) . ')'
                        . ($divisionid ? ' AND c.divisionid = ' . $divisionid : '')
                        . (empty($where) ? '' : ' AND (' . implode(' AND ', $where) . ')')
                    );
                    if (empty($customers)) {
                        break;
                    }

                    foreach ($actions as $action => $action_params) {
                        switch ($action) {
                            case 'node-access':
                                $nodes = $DB->GetAll(
                                    "SELECT id, ownerid FROM nodes WHERE access = ?
                                    AND ownerid IN (" . implode(',', $customers) . ")",
                                    array(1)
                                );
                                if (!empty($nodes)) {
                                    foreach ($nodes as $node) {
                                        if (!$quiet) {
                                            printf("[block/node-access] CustomerID: %04d, NodeID: %04d" . PHP_EOL, $node['ownerid'], $node['id']);
                                        }

                                        if (!$debug) {
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
                                break;
                            case 'node-warning':
                                $nodes = $DB->GetAll(
                                    "SELECT id, ownerid FROM nodes WHERE warning = ?
                                    AND ownerid IN (" . implode(',', $customers) . ")",
                                    array(0)
                                );
                                if (!empty($nodes)) {
                                    foreach ($nodes as $node) {
                                        if (!$quiet) {
                                            printf("[block/node-warning] CustomerID: %04d, NodeID: %04d" . PHP_EOL, $node['ownerid'], $node['id']);
                                        }

                                        if (!$debug) {
                                            $DB->Execute("UPDATE nodes SET warning = ?
                                                WHERE id = ?", array(1, $node['id']));
                                            if ($SYSLOG) {
                                                $SYSLOG->NewTransaction('lms-notify.php');
                                                $SYSLOG->AddMessage(
                                                    SYSLOG::RES_NODE,
                                                    SYSLOG::OPER_UPDATE,
                                                    array(
                                                        SYSLOG::RES_NODE => $node['id'],
                                                        SYSLOG::RES_CUST => $node['ownerid'],
                                                        'warning' => 1,
                                                    )
                                                );
                                            }
                                        }
                                    }
                                }
                                break;
                            case 'assignment-invoice':
                                $assigns = $DB->GetAll(
                                    "SELECT id, customerid FROM assignments
                                    WHERE invoice = ? AND (tariffid IS NOT NULL OR liabilityid IS NOT NULL)
                                        AND datefrom <= ?NOW? AND (dateto = 0 OR dateto >= ?NOW?)
                                        AND customerid IN (" . implode(',', $customers) . ")",
                                    array(DOC_INVOICE)
                                );
                                if (!empty($assigns)) {
                                    foreach ($assigns as $assign) {
                                        if (!$quiet) {
                                            printf("[block/assignment-invoice] CustomerID: %04d, AssignmentID: %d" . PHP_EOL, $assign['customerid'], $assign['id']);
                                        }

                                        if (empty($action_params)) {
                                            $target_doctype = 0;
                                        } else {
                                            switch (reset($action_params)) {
                                                case 'proforma':
                                                    $target_doctype = DOC_INVOICE_PRO;
                                                    break;
                                                case 'invoice':
                                                    $target_doctype = DOC_INVOICE;
                                                    break;
                                                case 'note':
                                                    $target_doctype = DOC_DNOTE;
                                                    break;
                                            }
                                        }

                                        if (!$debug) {
                                            $DB->Execute("UPDATE assignments SET invoice = ?
                                                WHERE id = ?", array($target_doctype, $assign['id']));
                                            if ($SYSLOG) {
                                                $SYSLOG->NewTransaction('lms-notify.php');
                                                $SYSLOG->AddMessage(
                                                    SYSLOG::RES_ASSIGN,
                                                    SYSLOG::OPER_UPDATE,
                                                    array(
                                                        SYSLOG::RES_ASSIGN => $assign['id'],
                                                        SYSLOG::RES_CUST => $assign['customerid'],
                                                        'invoice' => $target_doctype
                                                    )
                                                );
                                            }
                                        }
                                    }
                                }
                                break;
                            case 'customer-status':
                                $custids = $DB->GetCol(
                                    "SELECT id FROM customers
                                    WHERE status <> ? AND id IN (" . implode(',', $customers) . ")",
                                    array(CSTATUS_DEBT_COLLECTION)
                                );
                                if (!empty($custids)) {
                                    $target_cstatus = reset($action_params);
                                    if (empty($target_cstatus)) {
                                        $target_cstatus = CSTATUS_DEBT_COLLECTION;
                                    }

                                    foreach ($custids as $custid) {
                                        if (!$quiet) {
                                            printf("[block/customer-status] CustomerID: %04d" . PHP_EOL, $custid);
                                        }

                                        if (!$debug) {
                                            $DB->Execute(
                                                "UPDATE customers SET status = ? WHERE id = ?",
                                                array($target_cstatus, $custid)
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
                                break;
                            case 'all-assignment-suspension':
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
                                        if (!$quiet) {
                                            printf("[block/all-assignment-suspension] CustomerID: %04d" . PHP_EOL, $cid);
                                        }

                                        if (!$debug) {
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
                                break;
                            case 'customer-group':
                                $customergroupid = $LMS->CustomergroupGetId(reset($action_params));
                                if ($customergroupid) {
                                    foreach ($customers as $cid) {
                                        if (!$quiet) {
                                            printf("[block/customer-group] CustomerID: %04d, CustomerGroupID: %04d" . PHP_EOL, $cid, $customergroupid);
                                        }

                                        if (!$debug) {
                                            $LMS->CustomerassignmentAdd(
                                                array(
                                                    'customergroupid' => $customergroupid,
                                                    'customerid' => $cid,
                                                )
                                            );
                                        }
                                    }
                                }
                                break;
                            case 'node-group':
                                $nodegroupid = $LMS->GetNodeGroupIdByName(reset($action_params));
                                if ($nodegroupid) {
                                    $nodes = $DB->GetAll(
                                        "SELECT n.id, n.ownerid
                                        FROM nodes n
                                        WHERE NOT EXISTS (
                                                SELECT 1 FROM nodegroupassignments nga
                                                WHERE nga.noderid = n.id
                                                    AND nga.nodegroupid = ?
                                            )
                                            AND n.ownerid IN (" . implode(',', $customers) . ")",
                                        array(
                                            $nodegroupid,
                                        )
                                    );
                                    if (!empty($nodes)) {
                                        foreach ($nodes as $node) {
                                            if (!$quiet) {
                                                printf("[block/node-group] CustomerID: %04d, NodeID: %04d" . PHP_EOL, $node['ownerid'], $node['id']);
                                            }

                                            if (!$debug) {
                                                if ($SYSLOG) {
                                                    $SYSLOG->NewTransaction('lms-notify.php');
                                                }
                                                $LMS->addNodeGroupAssignment(array(
                                                    'nodeid' => $node['id'],
                                                    'nodegroupid' => $nodegroupid,
                                                ));
                                            }
                                        }
                                    }
                                }
                                break;
                        }
                    }

                    $plugin_manager->executeHook('notification_blocks', array(
                        'customers' => $customers,
                        'actions' => $actions,
                    ));

                    break;
                case 'unblock':
                    $where = array();
                    foreach ($unblock_prechecks as $precheck => $precheck_params) {
                        switch ($precheck) {
                            case 'node-access':
                                $where[] = 'EXISTS (SELECT id FROM nodes WHERE nodes.ownerid = c.id AND access = 0)';
                                break;
                            case 'node-warning':
                                $where[] = 'EXISTS (SELECT id FROM nodes WHERE nodes.ownerid = c.id AND warning = 1)';
                                break;
                            case 'assignment-invoice':
                                switch (reset($precheck_params)) {
                                    case 'proforma':
                                        $target_doctype = DOC_INVOICE_PRO;
                                        break;
                                    case 'note':
                                        $target_doctype = DOC_DNOTE;
                                        break;
                                    case 'invoice':
                                    default:
                                        $target_doctype = DOC_INVOICE;
                                        break;
                                }
                                $where[] = 'EXISTS (SELECT id FROM assignments
                                    WHERE invoice = ' . $target_doctype . ' AND (tariffid IS NOT NULL OR liabilityid IS NOT NULL)
                                        AND datefrom <= ?NOW? AND (dateto = 0 OR dateto >= ?NOW?)
                                        AND customerid = c.id)';
                                break;
                            case 'customer-status':
                                $target_cstatus = reset($precheck_params);
                                if (empty($target_cstatus)) {
                                    $target_cstatus = CSTATUS_CONNECTED;
                                }
                                $where[] = 'EXISTS (SELECT id FROM customers
                                    WHERE status <> ' . $target_cstatus . ' AND customers.id = c.id)';
                                break;
                            case 'all-assignment-suspension':
                                $where[] = 'EXISTS (SELECT id FROM assignments
                                    WHERE customerid = c.id AND tariffid IS NULL AND liabilityid IS NULL)';
                                break;
                            case 'customer-group':
                                $where[] = 'EXISTS (
                                    SELECT ca.id FROM vcustomerassignments ca
                                    JOIN customergroups g ON g.id = ca.customergroupid
                                    WHERE ca.customerid = c.id AND LOWER(g.name) = LOWER(\'' . reset($precheck_params) . '\'))';
                                break;
                            case 'node-group':
                                $where[] = 'EXISTS (
                                    SELECT nga.id FROM nodegroupassignments nga
                                    JOIN nodegroups g ON g.id = nga.nodegroupid
                                    JOIN nodes n ON n.id = nga.nodeid
                                    WHERE n.ownerid = c.id AND LOWER(g.name) = LOWER(\'' . reset($precheck_params) . '\'))';
                                break;
                        }
                    }

                    $customers = $DB->GetCol(
                        'SELECT c.id FROM customers c
                        WHERE 1 = 1' . $customer_status_condition
                        . (empty($customers) ? '' : ' AND c.id NOT IN (' . implode(',', $customers) . ')')
                        . ($divisionid ? ' AND c.divisionid = ' . $divisionid : '')
                        . (empty($where) ? '' : ' AND (' . implode(' AND ', $where) . ')')
                        . ($customergroups ?: '')
                    );
                    if (empty($customers)) {
                        break;
                    }

                    foreach ($actions as $action => $action_params) {
                        switch ($action) {
                            case 'node-access':
                                $nodes = $DB->GetAll(
                                    "SELECT id, ownerid FROM nodes WHERE access = ?
                                    AND ownerid IN (" . implode(',', $customers) . ")",
                                    array(0)
                                );
                                if (!empty($nodes)) {
                                    foreach ($nodes as $node) {
                                        if (!$quiet) {
                                            printf("[unblock/node-access] CustomerID: %04d, NodeID: %04d" . PHP_EOL, $node['ownerid'], $node['id']);
                                        }

                                        if (!$debug) {
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
                                break;
                            case 'node-warning':
                                $nodes = $DB->GetAll(
                                    "SELECT id, ownerid FROM nodes WHERE warning = ?
                                    AND ownerid IN (" . implode(',', $customers) . ")",
                                    array(1)
                                );
                                if (!empty($nodes)) {
                                    foreach ($nodes as $node) {
                                        if (!$quiet) {
                                            printf("[unblock/node-warning] CustomerID: %04d, NodeID: %04d" . PHP_EOL, $node['ownerid'], $node['id']);
                                        }

                                        if (!$debug) {
                                            $DB->Execute("UPDATE nodes SET warning = ?
                                                WHERE id = ?", array(0, $node['id']));
                                            if ($SYSLOG) {
                                                $SYSLOG->NewTransaction('lms-notify.php');
                                                $SYSLOG->AddMessage(
                                                    SYSLOG::RES_NODE,
                                                    SYSLOG::OPER_UPDATE,
                                                    array(
                                                        SYSLOG::RES_NODE => $node['id'],
                                                        SYSLOG::RES_CUST => $node['ownerid'],
                                                        'warning' => 0
                                                    )
                                                );
                                            }
                                        }
                                    }
                                }
                                break;
                            case 'assignment-invoice':
                                $assigns = $DB->GetAll(
                                    "SELECT id, customerid FROM assignments
                                    WHERE invoice <> ? AND (tariffid IS NOT NULL OR liabilityid IS NOT NULL)
                                        AND datefrom <= ?NOW? AND (dateto = 0 OR dateto >= ?NOW?)
                                        AND customerid IN (" . implode(',', $customers) . ")",
                                    array(DOC_INVOICE)
                                );
                                if (!empty($assigns)) {
                                    foreach ($assigns as $assign) {
                                        if (!$quiet) {
                                            printf("[unblock/assignment-invoice] CustomerID: %04d, AssignmentID: %04d" . PHP_EOL, $assign['customerid'], $assign['id']);
                                        }

                                        if (!$debug) {
                                            $DB->Execute("UPDATE assignments SET invoice = ?
                                                WHERE id = ?", array(DOC_INVOICE, $assign['id']));
                                            if ($SYSLOG) {
                                                $SYSLOG->NewTransaction('lms-notify.php');
                                                $SYSLOG->AddMessage(
                                                    SYSLOG::RES_ASSIGN,
                                                    SYSLOG::OPER_UPDATE,
                                                    array(
                                                        SYSLOG::RES_ASSIGN => $assign['id'],
                                                        SYSLOG::RES_CUST => $assign['customerid'],
                                                        'invoice' => DOC_INVOICE,
                                                    )
                                                );
                                            }
                                        }
                                    }
                                }
                                break;
                            case 'customer-status':
                                $custids = $DB->GetCol(
                                    "SELECT id FROM customers
                                    WHERE status = ? AND id IN (" . implode(',', $customers) . ")",
                                    array(CSTATUS_DEBT_COLLECTION)
                                );
                                if (!empty($custids)) {
                                    $target_cstatus = reset($action_params);
                                    if (empty($target_cstatus)) {
                                        $target_cstatus = CSTATUS_CONNECTED;
                                    }

                                    foreach ($custids as $custid) {
                                        if (!$quiet) {
                                            printf("[unblock/customer-status] CustomerID: %04d" . PHP_EOL, $custid);
                                        }

                                        if (!$debug) {
                                            $DB->Execute(
                                                "UPDATE customers SET status = ? WHERE id = ?",
                                                array($target_cstatus, $custid)
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
                                break;
                            case 'all-assignment-suspension':
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
                                                    if (!$quiet) {
                                                        printf("[unblock/all-assignment-suspension] assignment update: CustomerID: %04d, AssignmentID: %04d" . PHP_EOL, $cid, $aid);
                                                    }

                                                    if (!$debug) {
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
                                    }
                                    $aids = $DB->GetCol("SELECT id FROM assignments
                                        WHERE customerid = ? AND tariffid IS NULL AND liabilityid IS NULL", array($cid));
                                    if (!empty($aids)) {
                                        foreach ($aids as $aid) {
                                            if (!$quiet) {
                                                printf("[unblock/all-assignment-suspension] assignment deletion: CustomerID: %04d, AssignmentID: %04d" . PHP_EOL, $cid, $aid);
                                            }

                                            if (!$debug) {
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
                                break;
                            case 'customer-group':
                                $customergroupid = $LMS->CustomergroupGetId(reset($action_params));
                                if ($customergroupid) {
                                    foreach ($customers as $cid) {
                                        if (!$quiet) {
                                            printf("[unblock/customer-group] CustomerID: %04d, CustomerGroupID: %04d" . PHP_EOL, $cid, $customergroupid);
                                        }

                                        if (!$debug) {
                                            $LMS->CustomerassignmentDelete(
                                                array(
                                                    'customergroupid' => $customergroupid,
                                                    'customerid' => $cid,
                                                )
                                            );
                                        }
                                    }
                                }
                                break;
                            case 'node-group':
                                $nodegroupid = $LMS->GetNodeGroupIdByName(reset($action_params));
                                if ($nodegroupid) {
                                    $nodes = $DB->GetAll(
                                        "SELECT n.id, n.ownerid
                                        FROM nodes n
                                        WHERE EXISTS (
                                                SELECT 1 FROM nodegroupassignments nga
                                                WHERE nga.noderid = n.id
                                                    AND nga.nodegroupid = ?
                                            )
                                            AND n.ownerid IN (" . implode(',', $customers) . ")",
                                        array(
                                            $nodegroupid,
                                        )
                                    );
                                    if (!empty($nodes)) {
                                        foreach ($nodes as $node) {
                                            if (!$quiet) {
                                                printf("[unblock/node-group] CustomerID: %04d, NodeID: %04d" . PHP_EOL, $node['ownerid'], $node['id']);
                                            }

                                            if (!$debug) {
                                                if ($SYSLOG) {
                                                    $SYSLOG->NewTransaction('lms-notify.php');
                                                }
                                                $LMS->deleteNodeGroupAssignment(array(
                                                    'nodeid' => $node['id'],
                                                    'nodegroupid' => $nodegroupid,
                                                ));
                                            }
                                        }
                                    }
                                }
                                break;
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
