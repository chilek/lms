#!/usr/bin/env php
<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2023 LMS Developers
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

ini_set('memory_limit', '-1');
ini_set('default_socket_timeout', '3600');
ini_set('error_reporting', E_ALL & ~E_NOTICE & ~E_DEPRECATED);

$parameters = array(
    'config-file:' => 'C:',
    'quiet' => 'q',
    'help' => 'h',
    'version' => 'v',
    'test' => 't',
    'billings' => null,
    'customers' => null,
    'accounts' => null,
    'start-date:' => 's:',
    'end-date:' => 'e:',
    'customerid:' => null,
    'update' => 'u',
    'mode:' => 'm:',
    'incremental' => 'i',
    'use-last-id' => null,
    'use-call-start-time' => null,
    'chunking' => null,
    'no-chunking' => null,
    'chunk-size:' => null,
    'pricelist-file:' => null,
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
lms-metroportmvno-sync.php
(C) 2001-2023 LMS Developers

EOF;
    exit(0);
}

if (array_key_exists('help', $options)) {
    print <<<EOF
lms-metroportmvno-sync.php
(C) 2001-2022 LMS Developers

-C, --config-file=/etc/lms/lms.ini      alternate config file (default: /etc/lms/lms.ini);
-h, --help                              print this help and exit;
-v, --version                           print version info and exit;
-q, --quiet                             suppress any output, except errors;
-t, --test                              no changes are made to database;
    --billings                          sync billings
    --customers                         sync customers
    --accounts                          sync accounts
-s, --start-date="YYYY-MM-DD HH:mm:ss"  sync billings from date
-e, --end-date="YYYY-MM-DD HH:mm:ss"    sync billings to date
    --customerid=<id>                   limit synchronization to specifed customer
-u, --update                            update existing billing records instead deleting them
-m, --mode=<customer|provider>          billing get method selection
-i, --incremental                       get billing records incrementally after --start-date
    --use-last-id                       get billing records incrementally based on max unique id after --start-date
    --use-call-start-time               get billing records incrementally based on max call start time after --start-date
    --chunking                          enable billings chunking usage
                                        (enabled by default if --incremental parameter was specifed)
    --no-chunking                       disable billings chunking usage
    --chunk-size=<n>                    specify billings chunk size when chunking is enabled (in days)
    --pricelist-file                    specify path to price list csv file

EOF;
    exit(0);
}

$quiet = isset($options['quiet']);
if (!$quiet) {
    print <<<EOF
lms-metroportmvno-sync.php
(C) 2001-2022 LMS Developers

EOF;
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
    die('Unable to read configuration file [' . $CONFIG_FILE . ']!');
}

define('CONFIG_FILE', $CONFIG_FILE);

$CONFIG = (array) parse_ini_file($CONFIG_FILE, true);

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

$SYSLOG = SYSLOG::getInstance();

// Initialize Session, Auth and LMS classes
$AUTH = null;
$LMS = new LMS($DB, $AUTH, $SYSLOG);

$plugin_manager = new LMSPluginManager();
$LMS->setPluginManager($plugin_manager);

$metroportmvno = LMSMetroportMVNOPlugin::getMetroportMVNOInstance();

//<editor-fold desc="Options">
$customerid = isset($options['customerid']) && intval($options['customerid']) ? $options['customerid'] : null;

$year = date('Y');
$month = date('m');

if (isset($options['start-date'])) {
    $startdate = strtotime($options['start-date']);
    if ($startdate === false) {
        die(trans('Fatal error: invalid --start-date format!') . PHP_EOL);
    }
} else {
    $startdate = mktime(0, 0, 0, $month - 1, 1, $year);
}

if (isset($options['end-date'])) {
    $enddate = strtotime($options['end-date']);
    if ($enddate === false) {
        die('Fatal error: invalid --end-date format!' . PHP_EOL);
    }
} else {
    $enddate = strtotime('+ 1 month', $startdate) - 1;
    if ($enddate > time()) {
        $enddate = strtotime('today') - 1;
    }
}

if ($enddate <= $startdate) {
    die(trans('Fatal error: --start-date and --end-date define empty interval!') . PHP_EOL);
}

$startdatestr = date('Y-m-d H:i:s', $startdate);
$enddatestr = date('Y-m-d H:i:s', $enddate);

$test = isset($options['test']);
if ($test) {
    echo PHP_EOL . trans('WARNING! You are using test mode.') . PHP_EOL;
}

$update = isset($options['update']);
$syncBillings = isset($options['billings']);
$syncCustomers = isset($options['customers']);
$syncAccounts = isset($options['accounts']);

$mode = $options['mode'] ?? 'customer';
if ($mode != 'customer' && $mode != 'provider') {
    die(trans('Fatal error: unsupported mode "$a"!', $mode) . PHP_EOL);
}

if (!isset($options['incremental']) && isset($options['use-call-start-time'])) {
    die(trans('Fatal error: Using --use-call-start-time parameter needs --incremental parameter!') . PHP_EOL);
}
if (!isset($options['incremental']) && isset($options['use-last-id'])) {
    die(trans('Fatal error: Using --use-last-id parameter needs --incremental parameter!') . PHP_EOL);
}
if (isset($options['use-call-start-time']) && isset($options['use-last-id'])) {
    die(trans('Fatal error: Using --use-last-id and --use-call-start-time parameters at the same time is not supported!') . PHP_EOL);
}

$useLastId = isset($options['incremental']) && (!isset($options['use-call-start-time']) || isset($options['use-last-id']));
$useCallStartTime = isset($options['incremental']) && isset($options['use-call-start-time']);

$chunking = (isset($options['chunking']) || $useLastId || $useCallStartTime);

if (isset($options['no-chunking'])) {
    $chunking = false;
}

if ($chunking) {
    if (isset($options['chunk-size'])) {
        $chunk_size = intval($options['chunk-size']);
    } else {
        $chunk_size = 1;
    }
    if ($chunk_size < 1) {
        $chunk_size = 1;
    }
}
//</editor-fold>

//<editor-fold desc="Init">
define('API_URL', ConfigHelper::getConfig('metroportmvno.api_url'));
define('API_LOGIN', ConfigHelper::getConfig('metroportmvno.api_login'));
define('API_PASSWORD', ConfigHelper::getConfig('metroportmvno.api_password'));

$commonHeaders = array(
    CURLOPT_CONNECTTIMEOUT => 10,
    CURLOPT_TIMEOUT => 0,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false,
);

if (!function_exists('curl_init')) {
    die(trans('Curl extension not loaded!'));
}

$ch = curl_init();
//</editor-fold>

//<editor-fold desc="Login">
$authArgs = array(
    'login'    => API_LOGIN,
    'password' => API_PASSWORD
);

$authRawRequest = json_encode($authArgs);

curl_setopt_array($ch, $commonHeaders + array(
    CURLOPT_URL => API_URL . '/admins/Auth/login',
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $authRawRequest,
    CURLOPT_HEADER => true,
    CURLOPT_HTTPHEADER => array(
        'Content-Type: application/json; charset=utf-8',
        'Content-Length: '.strlen($authRawRequest)
    ),
));

$authResponse = curl_exec($ch);

if ($errno = curl_errno($ch)) {
    $error_message = curl_error($ch);
    curl_close($ch);
    die(trans('Error connecting to Metroport API server: $a!', $error_message) . PHP_EOL);
}
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
if ($http_code != 200) {
    curl_close($ch);
    die(trans('No access to Metroport API server!') . PHP_EOL);
}

preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $authResponse, $matches);
$cookie = implode("; ", $matches[1]);
//</editor-fold>

//<editor-fold desc="Post MMSC price list">
if (isset($options['pricelist-file'])) {
    if (!$quiet) {
        echo PHP_EOL . '---' . trans('Price list csv file import.') . '---' . PHP_EOL . PHP_EOL;
    }

    $file = $options['pricelist-file'];
    if (!file_exists($file)) {
        die(trans('Price list file ($a) does not exist!') . PHP_EOL);
    }

    $fh = fopen($file, "r");

    curl_setopt_array($ch, $commonHeaders + array(
            CURLOPT_URL => API_URL . '/mvno/retailRates/importRates',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_HEADER => false,
            CURLOPT_POSTFIELDS => array('file'=> new CURLFILE($file)),
            CURLOPT_HTTPHEADER => array(
                "Cookie: " . $cookie
            ),
        ));

    $pricelistResponse = curl_exec($ch);

    if ($errno = curl_errno($ch)) {
        $error_message = curl_error($ch);
        curl_close($ch);
        die(trans('Error: "$a"!', $error_message) . PHP_EOL);
    }

    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if ($http_code != 200 && $http_code != 204) {
        curl_close($ch);
        die(trans('Error - HTTP error code: "$a"!', $http_code) . PHP_EOL);
    }

    echo $pricelistResponse . PHP_EOL;
}

//</editor-fold>

//<editor-fold desc="Get MMSC users">
curl_setopt_array($ch, $commonHeaders + array(
        CURLOPT_URL => API_URL . '/Users/users?usertype=2',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "GET",
        CURLOPT_HEADER => false,
        CURLOPT_HTTPHEADER => array(
            "Content-Type: application/json; charset=utf-8",
            "Cookie: " . $cookie,
        ),
    ));

$usersResponse = curl_exec($ch);

if ($errno = curl_errno($ch)) {
    $error_message = curl_error($ch);
    curl_close($ch);
    die(trans('Error getting users from Metroport API server: "$a"!', $error_message) . PHP_EOL);
}

$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
if ($http_code != 200 && $http_code != 204) {
    curl_close($ch);
    die(trans('Error getting users from Metroport API server - HTTP error code: "$a"!', $http_code) . PHP_EOL);
}

$mmscUsers = array();
if (!empty($usersResponse)) {
    $mmscUsers = json_decode($usersResponse, true);
    if (isset($mmscUsers[0]) && isset($mmscUsers[1])) {
        $mmscUsers = $mmscUsers[1];
    }
}
//</editor-fold>

//<editor-fold desc="Prepare MMSC users data">
if (!empty($mmscUsers)) {
    foreach ($mmscUsers as &$mmscUser) {
        if (!empty($mmscUser['nip'])) {
            $mmscUserClearTen = preg_replace("/[-[:blank:]a-zA-Z]/", '', $mmscUser['nip']);
            if (preg_match('/^[0-9]{10}$/', $mmscUserClearTen)) {
                $mmscUser['nip'] = $mmscUserClearTen;
            }
        }
        if (!empty($mmscUser['pesel'])) {
            $mmscUser['pesel'] = preg_replace("/[[:blank:]]/", '', $mmscUser['pesel']);
        }

        if (!empty($mmscUser['idcardno'])) {
            $mmscUser['idcardno'] = strtoupper(preg_replace("/[[:blank:]]/", '', $mmscUser['idcardno']));
        }
    }
    unset($mmscUser);

    $mmscUserCodes = array_column($mmscUsers, null, 'usercode');
    $mmscUserIds = array_column($mmscUsers, null, 'id');
    $mmscUserTens = array_column($mmscUsers, null, 'nip');
    $mmscUserSsns = array_column($mmscUsers, null, 'pesel');
    $mmscUserIcns = array_column($mmscUsers, null, 'idcardno');
}
//</editor-fold>

if ($syncCustomers) {
    $DB->BeginTrans();
    //<editor-fold desc="Synchronize LMS customers with MMSC users">
    if (!$quiet) {
        echo PHP_EOL . '---' . trans('LMS customers with MMSC users synchronization') . '---' . PHP_EOL . PHP_EOL;
    }

    $lmsAllCustomers = $metroportmvno->getCustomersForBind();
    if (!empty($lmsAllCustomers)) {
        //<editor-fold desc="Sanitize LMS customer ten and icn and build data for ten,ssn,icn duplicates">
        foreach ($lmsAllCustomers as $id => &$lmsCustomer) {
            if (!empty($lmsCustomer['ten'])) {
                $customerClearTen = preg_replace("/[-[:blank:]a-zA-Z]/", '', $lmsCustomer['ten']);
                if (preg_match('/^[0-9]{10}$/', $customerClearTen)) {
                    $lmsCustomer['ten'] = $customerClearTen;
                    $lmsAllCustomersByTen[$customerClearTen][$lmsCustomer['id']] = $lmsCustomer;
                }
            }

            if (!empty($lmsCustomer['ssn'])) {
                $lmsAllCustomersBySsn[$lmsCustomer['ssn']][$lmsCustomer['id']] = $lmsCustomer;
            }

            if (!empty($lmsCustomer['icn'])) {
                $lmsCustomer['icn'] = strtoupper(preg_replace("/[[:blank:]]/", '', $lmsCustomer['icn']));
                $lmsAllCustomersByIcn[$lmsCustomer['icn']][$lmsCustomer['id']] = $lmsCustomer;
            }
        }
        unset($lmsCustomer);
        //</editor-fold>
    }

    if (!empty($mmscUsers) && !empty($lmsAllCustomers)) {
        $insufficientDataCount = 0;
        $userMissmatchingCount = 0;
        foreach ($mmscUsers as $mmcsUser) {
            if (empty($mmcsUser['nip'])
                && empty($mmcsUser['pesel'])
                && empty($mmcsUser['idcardno'])) {
                $insufficientDataCount++;
                $args = array(
                    'mmsc_user_id' => $mmcsUser['id'],
                    'mmsc_user_code_name' => trim($mmcsUser['UserCodeName'])
                );
                $message = $metroportmvno->setUserInsufficientDataMessage($args);
                if (!empty($message) && !$quiet) {
                    echo $message . PHP_EOL;
                }
            } elseif (!isset($lmsAllCustomersByTen[$mmcsUser['nip']])
                && !isset($lmsAllCustomersBySsn[$mmcsUser['pesel']])
                && !isset($lmsAllCustomersByIcn[$mmcsUser['idcardno']])) {
                $userMissmatchingCount++;
                $args = array(
                    'mmsc_user_id' => $mmcsUser['id'],
                    'mmsc_user_code_name' => trim($mmcsUser['UserCodeName']),
                    'mmsc_user_ten' => (!empty($mmcsUser['nip']) ? $mmcsUser['nip'] : null),
                    'mmsc_user_ssn' => (!empty($mmcsUser['pesel']) ? $mmcsUser['pesel'] : null),
                    'mmsc_user_icn' => (!empty($mmcsUser['idcardno']) ? $mmcsUser['idcardno'] : null)
                );
                $message = $metroportmvno->setUserMissmatchingMessage($args);
                if (!empty($message) && !$quiet) {
                    echo $message . PHP_EOL;
                }
            }
        }

        if (!empty($insufficientDataCount) && !$quiet) {
            echo 'Metroport users with insufficient data' . ': ' . $insufficientDataCount . PHP_EOL;
        }

        if (!empty($userMissmatchingCount) && !$quiet) {
            echo 'Metroport users could not be bound with any LMS client' . ': ' . $userMissmatchingCount . PHP_EOL;
        }
    }

    $lmsCustomers = $metroportmvno->getCustomersForBind($customerid);
    if (!empty($lmsCustomers)) {
        //<editor-fold desc="Sanitize LMS customer ten and icn">
        foreach ($lmsCustomers as $id => &$lmsCustomer) {
            if (!empty($lmsCustomer['ten'])) {
                $customerClearTen = preg_replace("/[-[:blank:]a-zA-Z]/", '', $lmsCustomer['ten']);
                if (preg_match('/^[0-9]{10}$/', $customerClearTen)) {
                    $lmsCustomer['ten'] = $customerClearTen;
                }
            }

            if (!empty($lmsCustomer['icn'])) {
                $lmsCustomer['icn'] = strtoupper(preg_replace("/[[:blank:]]/", '', $lmsCustomer['icn']));
            }
        }
        unset($lmsCustomer);
        //</editor-fold>
    }

    if (!empty($lmsCustomers) && !empty($mmscUsers)) {
        $lmsBoundCustomers = $metroportmvno->getBoundCustomers();

        if (!empty($lmsBoundCustomers)) {
            //<editor-fold desc="Sanitize LMS bound customer ten, ssn  and icn">
            foreach ($lmsBoundCustomers as $id => $lmsCustomer) {
                if (!empty($lmsCustomer['ten'])) {
                    $customerClearTen = preg_replace("/[-[:blank:]a-zA-Z]/", '', $lmsCustomer['ten']);
                    if (preg_match('/^[0-9]{10}$/', $customerClearTen)) {
                        $lmsCustomer['ten'] = $customerClearTen;
                        $lmsBoundCustomersByTen[$customerClearTen][$lmsCustomer['id']] = $lmsCustomer;
                    }
                }

                if (!empty($lmsCustomer['ssn'])) {
                    $lmsBoundCustomersBySsn[$lmsCustomer['ssn']][$lmsCustomer['id']] = $lmsCustomer;
                }

                if (!empty($lmsCustomer['icn'])) {
                    $lmsCustomer['icn'] = strtoupper(preg_replace("/[[:blank:]]/", '', $lmsCustomer['icn']));
                    $lmsBoundCustomersByIcn[$lmsCustomer['icn']][$lmsCustomer['id']] = $lmsCustomer;
                }
            }
            unset($lmsCustomer);
            //</editor-fold>
        }

        foreach ($lmsCustomers as $lmsCustomer) {
            $args = array(
                'lms_customer_id' => $lmsCustomer['id'],
                'lms_customer_lastname' => $lmsCustomer['lastname'],
                'lms_customer_name' => !empty($lmsCustomer['name']) ? $lmsCustomer['name'] : null,
                'lms_customer_ten' => !empty($lmsCustomer['ten']) ? $lmsCustomer['ten'] : null,
                'lms_customer_ssn' => !empty($lmsCustomer['ssn']) ? $lmsCustomer['ssn'] : null,
                'lms_customer_icn' => !empty($lmsCustomer['icn']) ? $lmsCustomer['icn'] : null,
            );

            $matchingResult = false;
            if (isset($mmscUserCodes[$lmsCustomer['id']]) && is_int($mmscUserCodes[$lmsCustomer['id']])) {
                $matchingResult = $metroportmvno->setCustomerExtid($lmsCustomer['id'], $mmscUserCodes[$lmsCustomer['id']]['id']);
            } elseif (!empty($lmsCustomer['ten']) && isset($mmscUserTens[$lmsCustomer['ten']]) && !isset($lmsBoundCustomersByTen[$lmsCustomer['ten']])) {
                if (isset($lmsAllCustomersByTen[$lmsCustomer['ten']]) && count($lmsAllCustomersByTen[$lmsCustomer['ten']]) > 1 && !$quiet) {
                    echo trans('LMS customer #$a could not be synchronized. There is another customer with same ten number.', $lmsCustomer['id']) . PHP_EOL;
                    continue;
                }

                $matchingResult = $metroportmvno->setCustomerExtid($lmsCustomer['id'], $mmscUserTens[$lmsCustomer['ten']]['id']);
                $args['mmsc_user_id'] = $mmscUserTens[$lmsCustomer['ten']]['id'];
                $args['mmsc_user_code_name'] = trim($mmscUserTens[$lmsCustomer['ten']]['UserCodeName']);
                $args['mmsc_user_ten'] = $mmscUserTens[$lmsCustomer['ten']]['nip'];
                $args['mmsc_user_ssn'] = $mmscUserTens[$lmsCustomer['ten']]['pesel'];
                $args['mmsc_user_icn'] = $mmscUserTens[$lmsCustomer['ten']]['idcardno'];
            } elseif (!empty($lmsCustomer['ssn']) && isset($mmscUserSsns[$lmsCustomer['ssn']]) && !isset($lmsBoundCustomersBySsn[$lmsCustomer['ssn']])) {
                if (isset($lmsAllCustomersBySsn[$lmsCustomer['ssn']]) && count($lmsAllCustomersBySsn[$lmsCustomer['ssn']]) > 1 && !$quiet) {
                    echo trans('LMS customer #$a could not be synchronized. There is another customer with same ssn number.', $lmsCustomer['id']) . PHP_EOL;
                    continue;
                }

                $matchingResult = $metroportmvno->setCustomerExtid($lmsCustomer['id'], $mmscUserSsns[$lmsCustomer['ssn']]['id']);
                $args['mmsc_user_id'] = $mmscUserSsns[$lmsCustomer['ssn']]['id'];
                $args['mmsc_user_code_name'] = trim($mmscUserSsns[$lmsCustomer['ssn']]['UserCodeName']);
                $args['mmsc_user_ten'] = $mmscUserSsns[$lmsCustomer['ssn']]['nip'];
                $args['mmsc_user_ssn'] = $mmscUserSsns[$lmsCustomer['ssn']]['pesel'];
                $args['mmsc_user_icn'] = $mmscUserSsns[$lmsCustomer['ssn']]['idcardno'];
            } elseif (!empty($lmsCustomer['icn']) && isset($mmscUserIcns[$lmsCustomer['icn']]) && !isset($lmsBoundCustomersByIcn[$lmsCustomer['icn']])) {
                if (isset($lmsAllCustomersByIcn[$lmsCustomer['icn']]) && count($lmsAllCustomersByIcn[$lmsCustomer['icn']]) > 1 && !$quiet) {
                    echo trans('LMS customer #$a could not be synchronized. There is another customer with same icn number.', $lmsCustomer['id']) . PHP_EOL;
                    continue;
                }

                $matchingResult = $metroportmvno->setCustomerExtid($lmsCustomer['id'], $mmscUserIcns[$lmsCustomer['icn']]['id']);
                $args['mmsc_user_id'] = $mmscUserIcns[$lmsCustomer['icn']]['id'];
                $args['mmsc_user_code_name'] = trim($mmscUserIcns[$lmsCustomer['icn']]['UserCodeName']);
                $args['mmsc_user_ten'] = $mmscUserIcns[$lmsCustomer['icn']]['nip'];
                $args['mmsc_user_ssn'] = $mmscUserIcns[$lmsCustomer['icn']]['pesel'];
                $args['mmsc_user_icn'] = $mmscUserIcns[$lmsCustomer['icn']]['idcardno'];
            }

            $args['matching_result'] = $matchingResult;
            $message = $metroportmvno->setCustomerExtidMessage($args);
            if (!empty($message) && !$quiet) {
                echo $message . PHP_EOL;
            }
        }
        unset($lmsCustomer, $matchingResult, $message);
    }
    //</editor-fold>

    if ($test) {
        $DB->RollbackTrans();
    } else {
        $DB->CommitTrans();
    }
}

if ($syncAccounts) {
    $DB->BeginTrans();
    //<editor-fold desc="Get MMSC mvno accounts">
    if (!empty($customerid)) {
        $customerExtIds = $LMS->getCustomerExternalIDs($customerid, $metroportmvno->serviceProviderId);
        $customerExtIds = array_values($customerExtIds);
        $customerExtId = intval($customerExtIds[0]['extid']);
        $userId = '?userid=' . $customerExtId;
    } else {
        $userId = '';
    }

    curl_setopt_array($ch, $commonHeaders + array(
            CURLOPT_URL => API_URL . '/Mvno/Mobiles' . $userId,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HEADER => false,
            CURLOPT_HTTPHEADER => array(
                "Content-Type: application/json; charset=utf-8",
                "Cookie: " . $cookie,
            ),
        ));

    $accountsResponse = curl_exec($ch);

    if ($errno = curl_errno($ch)) {
        $error_message = curl_error($ch);
        curl_close($ch);
        die(trans('Error getting users accounts from Metroport API server: "$a"!', $error_message) . PHP_EOL);
    }

    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if ($http_code != 200 && $http_code != 204) {
        curl_close($ch);
        die('Error getting users accounts from Metroport API server - HTTP error code: ' . $http_code . '!' . PHP_EOL);
    }

    $mmscAccounts = array();
    if (!empty($accountsResponse)) {
        $mmscAccounts = json_decode($accountsResponse, true);
    }
    //</editor-fold>

    //<editor-fold desc="Prepare LMS customers accounts">
    $lmsBoundCustomersByExtId = array();
    $lmsBoundCustomers = $metroportmvno->getBoundCustomers($customerid);
    if (!empty($lmsBoundCustomers)) {
        foreach ($lmsBoundCustomers as $key => $item) {
            $lmsBoundCustomersByExtId[$item['extid']] = $item;
        }
    }
    //</editor-fold>

    //<editor-fold desc="Sync LMS customers accounts with MMSC users mvno accounts">
    if (!empty($mmscAccounts) && !empty($lmsBoundCustomersByExtId)) {
        if (!$quiet) {
            echo PHP_EOL . '---' . trans('LMS customers accounts with MMSC users mvno accounts synchronization') . '---' . PHP_EOL;
        }

        //<editor-fold desc="No synchronized customer accounts">
        $mmscAccountsByAno = array_column($mmscAccounts, null, 'ano');

        foreach ($lmsBoundCustomers as $lmsBoundCustomer) {
            $customerAccounts = $LMS->getCustomerVoipAccounts($lmsBoundCustomer['id']);
            if (!empty($customerAccounts)) {
                foreach ($customerAccounts as $customerAccount) {
                    $customerAccountPhone = $customerAccount['phones'][0]['phone'];
                    $customerAccountAno = substr($customerAccountPhone, 2);
                    if (count($customerAccount['phones']) == 1
                        && isset($mmscAccountsByAno[$customerAccountAno])
                        && empty($customerAccount['extid'])) {
                        $customerNoSynchronizedAccountsByAno[$customerAccountAno] = $customerAccount;
                    }
                }
            }
        }
        //</editor-fold>

        foreach ($mmscAccounts as $mmscAccount) {
            $mmscAccountId = strval($mmscAccount['id']);
            $mmscUserId = $mmscAccount['userid'];

            //get LMS customer account by extid = $mmscUsersAccountId
            if (isset($lmsBoundCustomersByExtId[$mmscUserId])) {
                $login = strval($mmscAccount['ano']);
                $phone = ('48' . $mmscAccount['ano']);

                $numbers = array(
                    array(
                        'phone' => $phone,
                        'info' => ''
                    )
                );

                switch ($mmscAccount['status']) {
                    case 'active':
                    case 'new':
                        $access = 1;
                        break;
                    case 'blocked':
                        $access = 0;
                        break;
                    case 'deleted':
                        $access = -1;
                        break;
                }

                $lmsCustomerId = $lmsBoundCustomersByExtId[$mmscUserId]['id'];

                if (isset($customerNoSynchronizedAccountsByAno[$login])) {
                    $customerAccountId = $customerNoSynchronizedAccountsByAno[$mmscAccount['ano']]['id'];
                    $result = $metroportmvno->setAccountExtid($customerAccountId, $mmscAccountId);
                    if (!empty($result) && !$quiet) {
                        echo trans('Customer #$a - LMS account #$b has been bound with MVNO account #$c.', $lmsCustomerId, $customerAccountId, $mmscAccountId) . PHP_EOL;
                    }
                } else {
                    $customerAccount = $LMS->getCustomerVoipAccounts($lmsCustomerId, $mmscAccountId, $metroportmvno->serviceProviderId);
                    if (!empty($customerAccount)) {
                        if ($customerAccount[0]['access'] != $access && $access == -1 && $mmscAccount['status_confirmed'] == 1) {
                            // delete LMS account
                            $metroportmvno->accountDelete($customerAccount[0]['id']);
                            if (!$quiet) {
                                echo trans('Customer #$a - LMS account #$b has been deleted.', $lmsCustomerId, $customerAccount[0]['id']) . PHP_EOL;
                            }
                        } elseif ($customerAccount[0]['phones'][0]['phone'] != $phone || $customerAccount[0]['access'] != $access) {
                            // update LMS account
                            $args = $customerAccount[0];
                            $args['login'] = $login;
                            $args['address_id'] = -1;
                            $args['access'] = $mmscAccount['status_confirmed'] == 1 ? $access : $customerAccount[0]['access'];
                            $args['numbers'] = $numbers;

                            $result = $metroportmvno->accountUpdate($args);
                            if (!empty($result) && !$quiet) {
                                echo trans('Customer #$a - LMS account #$b has been updated.', $lmsCustomerId, $customerAccount[0]['id']) . PHP_EOL;
                            }
                        }
                    } else {
                        //create LMS account
                        $args = array(
                            'ownerid' => $lmsCustomerId,
                            'login' => $login,
                            'passwd' => '',
                            'address_id' => -1,
                            'access' => $access,
                            'numbers' => $numbers,
                            'extid' => $mmscAccountId,
                            'serviceproviderid' => $metroportmvno->serviceProviderId,
                        );
                        $accountId = $metroportmvno->accountAdd($args);
                        if (!$quiet) {
                            if (empty($accountId)) {
                                echo trans('Customer #$a - LMS account #$b has not been added.', $lmsCustomerId, $phone) . PHP_EOL;
                            } else {
                                echo trans('Customer #$a - LMS account #$b has been added.', $lmsCustomerId, $phone) . PHP_EOL;
                            }
                        }
                    }
                }
            }
        }
    }
    //</editor-fold>
    if ($test) {
        $DB->RollbackTrans();
    } else {
        $DB->CommitTrans();
    }
}

if ($syncBillings) {
    $DB->BeginTrans();
    //<editor-fold desc="Get MMSC billings TrafficTypes">
    curl_setopt_array($ch, $commonHeaders + array(
            CURLOPT_URL => API_URL . '/Mvno/Billings/TrafficTypes',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HEADER => false,
            CURLOPT_HTTPHEADER => array(
                "Content-Type: application/json; charset=utf-8",
                "Cookie: " . $cookie,
            ),
        ));

    $billingsTrafficTypesResponse = curl_exec($ch);

    if ($errno = curl_errno($ch)) {
        $error_message = curl_error($ch);
        curl_close($ch);
        die(trans('Error getting traffic types from Metroport API server: "$a"!', $error_message) . PHP_EOL);
    }

    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if ($http_code != 200 && $http_code != 204) {
        curl_close($ch);
        die(trans('Error getting traffic types from Metroport API server - HTTP error code: "$a"!', $http_code) . PHP_EOL);
    }

    $mmscBillingsTrafficTypes = array();
    if (!empty($billingsTrafficTypesResponse)) {
        $mmscBillingsTrafficTypes = json_decode($billingsTrafficTypesResponse, true);
    }

    if (!empty($mmscBillingsTrafficTypes)) {
        foreach ($mmscBillingsTrafficTypes as &$mmscBillingsTrafficType) {
            switch ($mmscBillingsTrafficType['type']) {
                case 'call':
                    $mmscBillingsTrafficType['lmstype'] = BILLING_RECORD_TYPE_VOICE_CALL;
                    break;
                case 'data':
                    $mmscBillingsTrafficType['lmstype'] = BILLING_RECORD_TYPE_DATA_TRANSFER;
                    break;
                case 'mms':
                    $mmscBillingsTrafficType['lmstype'] = BILLING_RECORD_TYPE_MMS;
                    break;
                case 'sms':
                    $mmscBillingsTrafficType['lmstype'] = BILLING_RECORD_TYPE_SMS;
                    break;
                case 'video':
                    $mmscBillingsTrafficType['lmstype'] = BILLING_RECORD_TYPE_VIDEO_CALL;
                    break;
            }
        }

        $mmscBillingsTrafficTypes = array_column($mmscBillingsTrafficTypes, null, 'id');
    }
    //</editor-fold>

    //<editor-fold desc="Get MMSC billings">
    $voip_account_ids = $DB->GetCol(
        'SELECT id
        FROM voipaccounts
        WHERE serviceproviderid = ?'
        . (!empty($customerid) ? ' AND ownerid = ' . $customerid : ''),
        array(
            $metroportmvno->serviceProviderId
        )
    );
    $voip_numbers = $DB->GetCol(
        'SELECT n.phone
        FROM voip_numbers n
        JOIN voipaccounts a ON a.id = n.voip_account_id
        WHERE a.serviceproviderid = ?'
        . (!empty($customerid) ? ' AND a.ownerid = ' . $customerid : ''),
        array(
            $metroportmvno->serviceProviderId
        )
    );

    if ($useLastId) {
        if (!empty($voip_account_ids) || !empty($voip_numbers)) {
            $idAfter = $DB->GetOne(
                'SELECT MAX(uniqueid)
                FROM voip_cdr
                WHERE incremental = 0
                    AND call_start_time > ?
                    AND (
                        callervoipaccountid IN ?
                        OR calleevoipaccountid IN ?
                        OR caller IN ?
                        OR callee IN ?
                    )',
                array(
                    $startdate,
                    $voip_account_ids,
                    $voip_account_ids,
                    $voip_numbers,
                    $voip_numbers,
                )
            );
        }

        if (empty($idAfter)) {
            $idAfter = null;
        } else {
            $startdate = $DB->GetOne(
                'SELECT call_start_time
                FROM voip_cdr
                WHERE uniqueid = ?',
                array(
                    $idAfter
                )
            );

            $startdate = strtotime('- 3 days', $startdate);
        }

        $enddate = strtotime('today') - 1;
    } elseif ($useCallStartTime) {
        if (!empty($voip_account_ids) || !empty($voip_numbers)) {
            $max_call_start_time = $DB->GetOne(
                'SELECT MAX(call_start_time)
            FROM voip_cdr
            WHERE incremental = 0
                AND call_start_time > ?
                AND (
                    callervoipaccountid IN ?
                    OR calleevoipaccountid IN ?
                    OR caller IN ?
                    OR callee IN ?
                )',
                array(
                    $startdate,
                    $voip_account_ids,
                    $voip_account_ids,
                    $voip_numbers,
                    $voip_numbers,
                )
            );
        }

        if (empty($max_call_start_time)) {
            $max_call_start_time = 0;
        }

        $startdate = max($startdate, $max_call_start_time);
        $enddate = strtotime('today') - 1;
    }

    if ($enddate <= $startdate) {
        die(trans('Fatal error: --start-date and --end-date define empty interval!') . PHP_EOL);
    }

    $startdatestr = date('Y-m-d H:i:s', $startdate);
    $enddatestr = date('Y-m-d H:i:s', $enddate);

    if (!$quiet) {
        echo PHP_EOL . '---' . trans('Getting billing records for period') . ' ' . $startdatestr . ' - ' . $enddatestr . '---' . PHP_EOL;
        if (!empty($idAfter)) {
            echo '---' . trans('Getting billing records after id') . ' ' . $idAfter . '---' . PHP_EOL;
        }
    }

    $customers = $DB->GetAllByKey(
        'SELECT
            c.id, ce.extid,
            (CASE WHEN (t.flags & ?) > 0 THEN 1 ELSE 0 END) AS netflag
        FROM customers c
        JOIN customerextids ce on c.id = ce.customerid
        JOIN assignments a ON a.customerid = c.id
        JOIN tariffs t ON t.id = a.tariffid
        JOIN tariffassignments ta ON ta.tariffid = t.id
        JOIN tarifftags tt ON tt.id = ta.tarifftagid
        WHERE UPPER(tt.name) = UPPER(?)
            AND a.suspended = 0
            AND a.commited = 1
            AND a.datefrom <= ?
            AND (a.dateto = 0 OR a.dateto >= ?)
            AND ce.serviceproviderid = ?
            ' . (!empty($customerid) ? ' AND c.id = ' . $customerid : '') . '
        ORDER BY c.id',
        'id',
        array(
            TARIFF_FLAG_NET_ACCOUNT,
            ConfigHelper::getConfig('metroportmvno.tariff_tag', 'metroport-mvno'),
            $enddate,
            $startdate,
            $metroportmvno->serviceProviderId
        )
    );
    if (empty($customers)) {
        die(trans('Fatal error: no customers with "metroport-mvno"-tagged tariffs assigned!') . PHP_EOL);
    }

    $voipaccounts = $DB->GetAllByKey(
        'SELECT a.id, a.ownerid, n.phone AS number
        FROM voipaccounts a
        JOIN voip_numbers n ON n.voip_account_id = a.id
        WHERE a.serviceproviderid = ?',
        'number',
        array(
            $metroportmvno->serviceProviderId
        )
    );
    if (empty($voipaccounts)) {
        $voipaccounts = array();
    }

    $cdr = array();
    setlocale(LC_NUMERIC, 'C');
    define('RECORD_LIMIT', 500);
    $records = array();

    if ($mode == 'customer') {
        $summaries = array();

        foreach ($customers as $customer) {
            $totalCount = $count = 0;

            $cid = $customer['id'];
            $cextid = $customer['extid'];

            if (!$quiet) {
                echo 'Customer #' . $cid . ' (netflag: ' . (empty($customer['netflag']) ? 0 : 1) . '): ';
            }

            $records[$cid] = array();

            $really_chunking = false;
            $chunk_start_date = $startdate;

            do {
                if ($chunking && $enddate - $chunk_start_date >= ($chunk_size * 86400 * 3) / 2) {
                    if (!$really_chunking && !$quiet) {
                        echo PHP_EOL;
                    }
                    $really_chunking = true;
                    $chunk_end_date = strtotime('+ ' . $chunk_size . ' days', $chunk_start_date) - 1;
                    if ($chunk_end_date > $enddate) {
                        $chunk_end_date = $enddate;
                    }
                } else {
                    $chunk_end_date = $enddate;
                }

                $startdatestr = date('Y-m-d H:i:s', $chunk_start_date);
                $enddatestr = date('Y-m-d H:i:s', $chunk_end_date);

                if ($really_chunking && !$quiet) {
                    echo 'Getting billing records for subperiod' . ' ' . $startdatestr . ' - ' . $enddatestr . '...' . PHP_EOL;
                }

                //<editor-fold desc="Get MMSC billings for user">
                $datestart = '?datestart=' . urlencode($startdatestr);
                $dateend = '&dateend=' . urlencode($enddatestr);
                $userId = !empty($cextid) ? '&userid=' . $cextid : null;
                $id_after = !empty($idAfter) ? '&id_after=' . $idAfter : '';

                $response = array();
                $responseCount = 0;

                if (!empty($userId)) {
                    curl_setopt_array($ch, $commonHeaders + array(
                            CURLOPT_URL => API_URL . '/Mvno/Billings' . $datestart . $dateend . $userId . $id_after,
                            CURLOPT_RETURNTRANSFER => true,
                            CURLOPT_ENCODING => "",
                            CURLOPT_MAXREDIRS => 10,
                            CURLOPT_TIMEOUT => 0,
                            CURLOPT_FOLLOWLOCATION => true,
                            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                            CURLOPT_CUSTOMREQUEST => "GET",
                            CURLOPT_HEADER => false,
                            CURLOPT_HTTPHEADER => array(
                                "Content-Type: application/json; charset=utf-8",
                                "Cookie: " . $cookie,
                            ),
                        ));

                    $billingsResponse = curl_exec($ch);

                    if ($errno = curl_errno($ch)) {
                        $error_message = curl_error($ch);
                        echo trans('Error getting billings from Metroport API server: "$a"!', $error_message) . PHP_EOL;
                    }

                    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    if ($http_code != 200 && $http_code != 204) {
                        echo trans('Error getting billings from Metroport API server - HTTP error code: "$a"!', $http_code) . PHP_EOL;
                    }

                    if (!empty($billingsResponse)) {
                        $response = json_decode($billingsResponse, true);
                        if (isset($response[0]) && isset($response[1])) {
                            $responseCount = $response[0];
                            $response = $response[1];
                        }
                    }
                }
                //</editor-fold>
                if (!empty($response) && $responseCount !== 0) {
                    foreach ($response as $item) {
                        if (!empty($item['src']) && !preg_match('/^[0-9]+$/', $item['src'])) {
                            $item['src'] = preg_replace("/^D[0-9]/", '', $item['src']);
                        }
                        if (!empty($item['dst']) && !preg_match('/^[0-9]+$/', $item['dst'])) {
                            $item['dst'] = preg_replace("/^D[0-9]/", '', $item['dst']);
                        }

                        $id = $item['id'];
                        $src = $item['src'];
                        $dst = $item['dst'];
                        $ano = $item['ano'];
                        $netprice = $item['retail_totalnetto'];
                        $grossprice = ($item['retail_totalnetto'] * $item['retail_vat']);
                        if (empty($customer['netflag'])) {
                            $price = empty($grossprice) ? 0 : $grossprice;
                        } else {
                            $price = empty($netprice) ? 0 : $netprice;
                        }

                        $outgoing = ($src == ('48' . $ano));
                        $direction = !$outgoing ? BILLING_RECORD_DIRECTION_INCOMING : BILLING_RECORD_DIRECTION_OUTGOING;
                        $number = $direction == BILLING_RECORD_DIRECTION_OUTGOING ? $src : $dst;

                        switch ($item['billingfield']) {
                            case 'duration':
                                $duration = $item['duration'];
                                break;
                            case 'datatransfer':
                                $duration = ($item['datatransfer'] * 1024);
                                break;
                            case 'msg':
                                $duration = $item['msg'];
                                break;
                        }

                        $records[$cid][$direction . '_' . $id] = array(
                            'caller' => strval($src),
                            'callee' => strval($dst),
                            'call_start_time' => strtotime($item['calldate']),
                            'totaltime' => intval($duration),
                            'billtedtime' => intval($duration),
                            'price' => str_replace(',', '.', $price),
                            'status' => BILLING_RECORD_STATUS_UNKNOWN,
                            'direction' => $direction,
                            'callervoipaccountid' => isset($voipaccounts[$src]) ? $voipaccounts[$src]['id'] : null,
                            'calleevoipaccountid' => isset($voipaccounts[$dst]) ? $voipaccounts[$dst]['id'] : null,
                            'type' => $mmscBillingsTrafficTypes[$item['traffic_types_id']]['lmstype'],
                            'fraction' => $item['CategoryName'],
                            'uniqueid' => strval($id),
                        );
                    }

                    $totalCount += $responseCount;

                    unset($response);
                }

                $chunk_start_date = $chunk_end_date + 1;
            } while ($chunk_start_date < $enddate);

            $count = count($records[$cid]);

            if (!$quiet) {
                if ($really_chunking) {
                    echo '  ';
                }
                echo $count . ' records (got ' . $totalCount . ' records via API).' . PHP_EOL;
            }
        }
    } elseif ($mode = 'provider') {
        $really_chunking = false;
        $chunk_start_date = $startdate;

        $summaries = array();

        do {
            if ($chunking && $enddate - $chunk_start_date >= ($chunk_size * 86400 * 3) / 2) {
                $really_chunking = true;
                $chunk_end_date = strtotime('+ ' . $chunk_size . ' days', $chunk_start_date) - 1;
                if ($chunk_end_date > $enddate) {
                    $chunk_end_date = $enddate;
                }
            } else {
                $chunk_end_date = $enddate;
            }

            $startdatestr = date('Y-m-d H:i:s', $chunk_start_date);
            $enddatestr = date('Y-m-d H:i:s', $chunk_end_date);

            if ($really_chunking && !$quiet) {
                echo 'Getting billing records for subperiod' . ' ' . $startdatestr . ' - ' . $enddatestr . '...' . PHP_EOL;
            }

            //<editor-fold desc="Get MMSC billings for user">
            $datestart = '?datestart=' . urlencode($startdatestr);
            $dateend = '&dateend=' . urlencode($enddatestr);
            $id_after = !empty($idAfter) ? '&id_after=' . $idAfter : '';

            $response = array();
            $responseCount = 0;

            curl_setopt_array($ch, $commonHeaders + array(
                    CURLOPT_URL => API_URL . '/Mvno/Billings' . $datestart . $dateend . $id_after,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => "",
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 0,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => "GET",
                    CURLOPT_HEADER => false,
                    CURLOPT_HTTPHEADER => array(
                        "Content-Type: application/json; charset=utf-8",
                        "Cookie: " . $cookie,
                    ),
                ));

            $billingsResponse = curl_exec($ch);

            if ($errno = curl_errno($ch)) {
                $error_message = curl_error($ch);
                curl_close($ch);
                die('Error getting billings from Metroport API server: ' . $error_message . '!' . PHP_EOL);
            }

            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if ($http_code != 200 && $http_code != 204) {
                curl_close($ch);
                die('Error getting billings from Metroport API server - HTTP error code: ' . $http_code . '!' . PHP_EOL);
            }

            if (!empty($billingsResponse)) {
                $response = json_decode($billingsResponse, true);
                if (isset($response[0]) && isset($response[1])) {
                    $responseCount = $response[0];
                    $response = $response[1];
                }
            }
            //</editor-fold>

            if (!empty($responseCount)) {
                foreach ($response as $item) {
                    if (!empty($item['src']) && !preg_match('/^[0-9]+$/', $item['src'])) {
                        $item['src'] = preg_replace("/^D[0-9]/", '', $item['src']);
                    }
                    if (!empty($item['dst']) && !preg_match('/^[0-9]+$/', $item['dst'])) {
                        $item['dst'] = preg_replace("/^D[0-9]/", '', $item['dst']);
                    }

                    $id = $item['id'];
                    $src = $item['src'];
                    $dst = $item['dst'];
                    $ano = $item['ano'];

                    /*
                    if (!isset($voipaccounts[$src]) && !isset($voipaccounts[$dst])) {
                        continue;
                    }
                    */

                    if (isset($voipaccounts[$src])) {
                        $cid = $voipaccounts[$src]['ownerid'];
                    } elseif (isset($voipaccounts[$dst])) {
                        $cid = $voipaccounts[$dst]['ownerid'];
                    }
                    if ($customerid && $customerid != $cid || !isset($customers[$cid])) {
                        continue;
                    }

                    $customer = $customers[$cid];
                    $netprice = $item['retail_totalnetto'];
                    $grossprice = ($item['retail_totalnetto'] * $item['retail_vat']);
                    if (empty($customer['netflag'])) {
                        $price = empty($grossprice) ? 0 : $grossprice;
                    } else {
                        $price = empty($netprice) ? 0 : $netprice;
                    }

                    $outgoing = ($src == ('48' . $ano));
                    $direction = !$outgoing ? BILLING_RECORD_DIRECTION_INCOMING : BILLING_RECORD_DIRECTION_OUTGOING;
                    $number = $direction == BILLING_RECORD_DIRECTION_OUTGOING ? $src : $dst;

                    switch ($item['billingfield']) {
                        case 'duration':
                            $duration = $item['duration'];
                            break;
                        case 'datatransfer':
                            $duration = ($item['datatransfer'] * 1024);
                            break;
                        case 'msg':
                            $duration = $item['msg'];
                            break;
                    }

                    if (!isset($records[$cid])) {
                        $records[$cid] = array();
                    }
                    $records[$cid][$direction . '_' . $id] = array(
                        'caller' => strval($src),
                        'callee' => strval($dst),
                        'call_start_time' => strtotime($item['calldate']),
                        'totaltime' => intval($duration),
                        'billtedtime' => intval($duration),
                        'price' => str_replace(',', '.', $price),
                        'status' => BILLING_RECORD_STATUS_UNKNOWN,
                        'direction' => $direction,
                        'callervoipaccountid' => isset($voipaccounts[$src]) ? $voipaccounts[$src]['id'] : null,
                        'calleevoipaccountid' => isset($voipaccounts[$dst]) ? $voipaccounts[$dst]['id'] : null,
                        'type' => $mmscBillingsTrafficTypes[$item['traffic_types_id']]['lmstype'],
                        'fraction' => $item['CategoryName'],
                        'uniqueid' => strval($id)
                    );

                    if (!isset($summaries[$cid])) {
                        $summaries[$cid] = array(
                            'count' => 0,
                        );
                    }
                    $summaries[$cid]['count']++;
                }

                unset($response);
            }

            $chunk_start_date = $chunk_end_date + 1;
        } while ($chunk_start_date < $enddate);

        ksort($summaries);

        if (!$quiet) {
            foreach ($summaries as $cid => $summary) {
                echo 'Customer #' . $cid . ' (netflag: ' . (empty($customers[$cid]['netflag']) ? 0 : 1) . '): '
                    . $summary['count'] . ' records' . PHP_EOL;
            }
        }
    }

    $insert_query = 'INSERT INTO voip_cdr (caller, callee, call_start_time, totaltime, billedtime, price, status, direction, callervoipaccountid, calleevoipaccountid, type, fraction, uniqueid)
        VALUES ';
    $insert_data = '(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';
    $update_query = 'UPDATE voip_cdr SET caller = ?, callee = ?, call_start_time = ?, totaltime = ?,
        billedtime = ?, price = ?, status = ?, callervoipaccountid = ?, calleevoipaccountid = ?,
        type = ?, fraction = ? WHERE id = ?';

    if ($update) {
        $cdr = $DB->GetAllByKey(
            'SELECT id, direction, uniqueid, ' . $DB->Concat('direction', "'_'", 'uniqueid') . ' AS direction_uniqueid
            FROM voip_cdr
            WHERE call_start_time >= ?
                AND call_start_time <= ?
                AND (
                    callervoipaccountid IN ?
                    OR calleevoipaccountid IN ?
                    OR caller IN ?
                    OR callee IN ?
                )',
            'direction_uniqueid',
            array(
                $startdate,
                $enddate,
                $voip_account_ids,
                $voip_account_ids,
                $voip_numbers,
                $voip_numbers,
            )
        );
    } else {
        $DB->Execute(
            'DELETE FROM voip_cdr
            WHERE call_start_time >= ?
                AND call_start_time <= ?
                AND (
                    callervoipaccountid IN ?
                    OR calleevoipaccountid IN ?
                )',
            array(
                $startdate,
                $enddate,
                $voip_account_ids,
                $voip_account_ids,
            )
        );
    }

    if (empty($cdr)) {
        $cdr = array();
    }
    foreach ($records as $cid => $customer_records) {
        while (count($customer_records)) {
            $record_chunk = array_splice($customer_records, 0, RECORD_LIMIT);
            $records_to_insert = array();
            $values = array();
            foreach ($record_chunk as $record) {
                $cdr_idx = $record['direction'] . '_' . $record['uniqueid'];
                if (isset($cdr[$cdr_idx])) {
                    $direction = $record['direction'];
                    $uniqueid = $record['uniqueid'];
                    unset($record['direction'], $record['uniqueid']);
                    $record['id'] = $cdr[$cdr_idx]['id'];
                    unset($cdr[$cdr_idx]);
                    $DB->Execute(
                        $update_query,
                        array_values($record)
                    );
                } else {
                    $records_to_insert[] = $insert_data;
                    $values = array_merge($values, array_values($record));
                }
            }
            if (!empty($records_to_insert)) {
                $DB->Execute(
                    $insert_query . implode(',', $records_to_insert),
                    $values
                );
            }
        }
    }
    if (!empty($cdr)) {
        $DB->Execute(
            'DELETE FROM voip_cdr WHERE id IN ?',
            array(Utils::array_column($cdr, 'id'))
        );
    }
    //</editor-fold>
    if ($test) {
        $DB->RollbackTrans();
    } else {
        $DB->CommitTrans();
    }
}

curl_close($ch);
echo 'done!' . PHP_EOL;
