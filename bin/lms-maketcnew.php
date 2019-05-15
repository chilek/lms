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

define('XVALUE', 100);

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
lms-maketcnew.php
(C) 2001-2017 LMS Developers

EOF;
    exit(0);
}

if (array_key_exists('help', $options)) {
    print <<<EOF
lms-maketcnew.php
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
lms-maketcnew.php
(C) 2001-2017 LMS Developers

EOF;
}

if (array_key_exists('config-file', $options)) {
    $CONFIG_FILE = $options['config-file'];
} else {
    $CONFIG_FILE = '/etc/lms/lms.ini';
}

if (!$quiet) {
    echo "Using file " . $CONFIG_FILE . " as config." . PHP_EOL;
}

define('CONFIG_FILE', $CONFIG_FILE);

if (!is_readable($CONFIG_FILE)) {
    die('Unable to read configuration file [' . $CONFIG_FILE . ']!' . PHP_EOL);
}

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
    // can't work without database
    die("Fatal error: cannot connect to database!" . PHP_EOL);
}

// Include required files (including sequence is important)

require_once(LIB_DIR . DIRECTORY_SEPARATOR . 'common.php');
//require_once(LIB_DIR . DIRECTORY_SEPARATOR . 'definitions.php');

$script_file = ConfigHelper::getConfig('tcnew.script_file', "/etc/rc.d/rc.htb");
$script_file_day = ConfigHelper::getConfig('tcnew.script_file_day', "/etc/rc.d/rc.htb.day");
$script_file_night = ConfigHelper::getConfig('tcnew.script_file_night', "/etc/rc.d/rc.htb.night");
$script_permission = ConfigHelper::getConfig('tcnew.script_permission', "0700");
$script_begin = ConfigHelper::getConfig('tcnew.begin', "#!/bin/bash\n\nPATH=\"/bin:/sbin:/usr/bin:/usr/sbin\"\n\n");
$script_begin_day = ConfigHelper::getConfig('tcnew.begin_day', $script_begin);
$script_begin_night = ConfigHelper::getConfig('tcnew.begin_night', $script_begin);
$script_end = ConfigHelper::getConfig('tcnew.end', '', true);
$script_end_day = ConfigHelper::getConfig('tcnew.end_day', $script_end);
$script_end_night = ConfigHelper::getConfig('tcnew.end_night', $script_end);
$script_class_up = ConfigHelper::getConfig('tcnew.class_up', '', true);
$script_class_up_day = ConfigHelper::getConfig('tcnew.class_up_day', $script_class_up);
$script_class_up_night = ConfigHelper::getConfig('tcnew.class_up_night', $script_class_up);
$script_class_down = ConfigHelper::getConfig('tcnew.class_down', '', true);
$script_class_down_day = ConfigHelper::getConfig('tcnew.class_down_day', $script_class_down);
$script_class_down_night = ConfigHelper::getConfig('tcnew.class_down_night', $script_class_down);
$script_filter_up = ConfigHelper::getConfig('tcnew.filter_up', '', true);
$script_filter_up_day = ConfigHelper::getConfig('tcnew.filter_up_day', '', true);
$script_filter_up_night = ConfigHelper::getConfig('tcnew.filter_up_night', '', true);
$script_filter_down = ConfigHelper::getConfig('tcnew.filter_down', '', true);
$script_filter_down_day = ConfigHelper::getConfig('tcnew.filter_down_day', '', true);
$script_filter_down_night = ConfigHelper::getConfig('tcnew.filter_down_night', '', true);
$script_climit = ConfigHelper::getConfig('tcnew.climit', '', true);
$script_plimit = ConfigHelper::getConfig('tcnew.plimit', '', true);
$script_multi_mac = ConfigHelper::checkConfig('tcnew.multi_mac');
$create_device_channels = ConfigHelper::checkConfig('tcnew.create_device_channels');
$all_assignments = ConfigHelper::checkConfig('tcnew.all_assignments');

$existing_networks = $DB->GetCol("SELECT name FROM networks");

// get selected networks from ini file
$networks = ConfigHelper::getConfig('tcnew.networks', '', true);
$networks = preg_split('/(\s+|\s*,\s*)/', $networks, -1, PREG_SPLIT_NO_EMPTY);

// exclude networks set in config value
$excluded_networks = ConfigHelper::getConfig('tcnew.excluded_networks', '', true);
$excluded_networks = preg_split('/(\s+|\s*,\s*)/', $excluded_networks, -1, PREG_SPLIT_NO_EMPTY);

if (empty($networks)) {
    $networks = array_diff($existing_networks, $excluded_networks);
} else {
    $networks = array_intersect(array_diff($networks, $excluded_networks), $existing_networks);
}

$networks = $DB->GetAllByKey(
    "SELECT id, name, address, mask, interface FROM networks"
    . (empty($networks) ? '' : " WHERE UPPER(name) IN ('" . implode("','", array_map('mb_strtoupper', $networks)) . "')"),
    'id'
);

// customer groups
$customergroups = ConfigHelper::getConfig('tcnew.customergroups', '', true);
$customergroups = preg_split('/(\s+|\s*,\s*)/', $customergroups, -1, PREG_SPLIT_NO_EMPTY);
if (empty($customergroups)) {
    $customerids = array();
} else {
    $customerids = $DB->GetRow("SELECT DISTINCT a.customerid FROM customerassignments a
		JOIN customergroups g ON g.id = a.customergroupid
		WHERE UPPER(g.name) IN ('" . implode("','", array_map('mb_strtoupper', $customergroups)) . "')");
}

// nodes
if ($all_assignments) {
    $query = '(';
} else {
    $query = '';
}

$query .= "SELECT t.downrate AS downrate, t.downceil AS downceil, t.uprate AS uprate, t.upceil AS upceil,
	(CASE WHEN t.downrate_n IS NOT NULL THEN t.downrate_n ELSE t.downrate END) AS downrate_n,
	(CASE WHEN t.downceil_n IS NOT NULL THEN t.downceil_n ELSE t.downceil END) AS downceil_n,
	(CASE WHEN t.uprate_n IS NOT NULL THEN t.uprate_n ELSE t.uprate END) AS uprate_n,
	(CASE WHEN t.upceil_n IS NOT NULL THEN t.upceil_n ELSE t.upceil END) AS upceil_n,
	t.climit AS climit, t.plimit AS plimit,
	n.id, n.ownerid, n.name, n.netid, INET_NTOA(n.ipaddr) AS ip, n.mac,
	na.assignmentid, a.customerid,
	TRIM(" . $DB->Concat('c.lastname', "' '", 'c.name') . ") AS customer
	FROM nodeassignments na
	JOIN assignments a ON (na.assignmentid = a.id)
	LEFT JOIN (
		SELECT customerid, COUNT(id) AS allsuspended FROM assignments
		WHERE tariffid IS NULL AND liabilityid IS NULL
			AND datefrom <= ?NOW? AND (dateto = 0 OR dateto > ?NOW?)
		GROUP BY customerid
	) s ON s.customerid = a.customerid
	JOIN tariffs t ON (a.tariffid = t.id)
	JOIN vnodes n ON (na.nodeid = n.id)
	JOIN customers c ON (a.customerid = c.id)
	WHERE s.allsuspended IS NULL AND a.suspended = 0 AND a.commited = 1
		AND a.datefrom <= ?NOW? AND (a.dateto >= ?NOW? OR a.dateto = 0)
		AND n.access = 1
		AND (t.downrate > 0 OR t.downceil > 0 OR t.uprate > 0 OR t.upceil > 0)
		AND n.netid IN (" . implode(',', array_keys($networks)) . ")"
        . (empty($customerids) ? '' : " AND c.id IN (" . implode(',', $customerids) . ")");

if ($all_assignments) {
    $query .= ") UNION (
	SELECT t.downrate, t.downceil, t.uprate, t.upceil,
		(CASE WHEN t.downrate_n IS NOT NULL THEN t.downrate_n ELSE t.downrate END) AS downrate_n,
		(CASE WHEN t.downceil_n IS NOT NULL THEN t.downceil_n ELSE t.downceil END) AS downceil_n,
		(CASE WHEN t.uprate_n IS NOT NULL THEN t.uprate_n ELSE t.uprate END) AS uprate_n,
		(CASE WHEN t.upceil_n IS NOT NULL THEN t.upceil_n ELSE t.upceil END) AS upceil_n,
		t.climit, t.plimit,
		n.id, n.ownerid, n.name, n.netid, INET_NTOA(n.ipaddr) AS ip, n.mac,
		a.id AS assignmentid, a.customerid,
		TRIM(" . $DB->Concat('lastname', "' '", 'c.name') . ") AS customer
	FROM assignments a
	LEFT JOIN (
		SELECT customerid, COUNT(id) AS allsuspended FROM assignments
		WHERE tariffid IS NULL AND liabilityid IS NULL
			AND datefrom <= ?NOW? AND (dateto = 0 OR dateto > ?NOW?)
		GROUP BY customerid
	) s ON s.customerid = a.customerid
	JOIN tariffs t ON t.id = a.tariffid
	JOIN customers c ON c.id = a.customerid
	JOIN (
		SELECT vn.id, vn.name, vn.netid, vn.ipaddr, vn.mac, vn.access,
			(CASE WHEN nd.id IS NULL THEN vn.ownerid ELSE nd.ownerid END) AS ownerid
		FROM vnodes vn
			LEFT JOIN netdevices nd ON nd.id = vn.netdev AND vn.ownerid IS NULL AND nd.ownerid IS NOT NULL
		WHERE (vn.ownerid > 0 AND nd.id IS NULL)
			OR (vn.ownerid IS NULL AND nd.id IS NOT NULL)
	) n ON n.ownerid = c.id
	WHERE s.allsuspended IS NULL AND a.suspended = 0 AND a.commited = 1
		AND n.id NOT IN (SELECT DISTINCT nodeid FROM nodeassignments)
		AND a.id NOT IN (SELECT DISTINCT assignmentid FROM nodeassignments)
		AND a.datefrom <= ?NOW?
		AND (a.dateto >= ?NOW? OR a.dateto = 0)
		AND n.access = 1
		AND (t.downrate > 0 OR t.downceil > 0 OR t.uprate > 0 OR t.upceil > 0)
		AND n.netid IN (" . implode(',', array_keys($networks)) . ")"
        . (empty($customerids) ? '' : " AND c.id IN (" . implode(',', $customerids) . ")") . "
	) ORDER BY customerid, assignmentid";
} else {
    $query .= " ORDER BY a.customerid, na.assignmentid";
}

$nodes = $DB->GetAll($query);
if (empty($nodes)) {
    die("Unable to read database or assignments table is empty!" . PHP_EOL);
}

// adding nodes to channels array
$channels = array();
foreach ($nodes as $node) {
    $assignmentid = $node['assignmentid'];
    $ip = $node['ip'];
    $inet = ip_long($ip);
    $networkid = $node['netid'];

    // looking for channel
    $j = 0;
    $channelfound = false;
    foreach ($channels as $key => $channel) {
        $j = $key;
        if ($channel['id'] == $assignmentid) {
            $channelfound = true;
            break;
        }
    }

    list ($uprate, $downrate, $upceil, $downceil, $uprate_n, $downrate_n, $upceil_n, $downceil_n,
        $climit, $plimit, $nodeid) =
        array($node['uprate'], $node['downrate'], $node['upceil'], $node['downceil'],
            $node['uprate_n'], $node['downrate_n'], $node['upceil_n'], $node['downceil_n'],
            $node['climit'], $node['plimit'], $node['id']);

    if (!$channelfound) { // channel (assignment) not found
        // mozliwe ze komputer jest juz przypisany do innego
        // zobowiazania, uwzgledniamy to...
        $j = 0;
        $channelfound = false;
        foreach ($channels as $chankey => $channel) {
            $j = $chankey;
            $x = 0;
            $nodefound = false;
            foreach ($channel['nodes'] as $nodekey => $chnode) {
                $x = $nodekey;
                if ($chnode['id'] == $nodeid) {
                    $nodefound = true;
                    break;
                }
            }
            if ($nodefound) {
                $channelfound = true;
                break;
            }
        }

        // ...komputer znaleziony, sprawdzmy czy kanal nie
        // zawiera juz tego zobowiazania
        if ($channelfound) {
            $y = 0;
            $subfound = false;
            foreach ($channels[$j]['subs'] as $subkey => $sub) {
                $y = $subkey;
                if ($sub == $assignmentid) {
                    $subfound = true;
                    break;
                }
            }

            // zobowiazanie nie znalezione, zwiekszamy kanal
            if (!$subfound) {
                $channels[$j]['uprate'] += $uprate;
                $channels[$j]['upceil'] += $upceil;
                $channels[$j]['downrate'] += $downrate;
                $channels[$j]['downceil'] += $downceil;
                $channels[$j]['uprate_n'] += $uprate_n;
                $channels[$j]['upceil_n'] += $upceil_n;
                $channels[$j]['downrate_n'] += $downrate_n;
                $channels[$j]['downceil_n'] += $downceil_n;
                $channels[$j]['climit'] += $climit;
                $channels[$j]['plimit'] += $plimit;

                $channels[$j]['subs'][] = $assignmentid;
            }

            continue;
        }

        // ...nie znaleziono komputera, tworzymy kanal
        $channels[] = array('id' => $assignmentid, 'nodes' => array(), 'subs' => array(),
            'cid' => $node['ownerid'], 'customer' => $node['customer'],
            'uprate' => $uprate, 'upceil' => $upceil,
            'downrate' => $downrate, 'downceil' => $downceil,
            'uprate_n' => $uprate_n, 'upceil_n' => $upceil_n,
            'downrate_n' => $downrate_n, 'downceil_n' => $downceil_n,
            'climit' => $climit, 'plimit' => $plimit);
        $j = count($channels) - 1;
    }

    $channels[$j]['nodes'][] = array('id' => $nodeid, 'network' => $networkid, 'ip' => $ip,
        'name' => $node['name'], 'mac' => $node['mac']);
}

if ($create_device_channels) {
    $devices = $DB->GetAll("SELECT n.id, INET_NTOA(n.ipaddr) AS ip, n.name, n.mac, n.netid
		FROM vnodes n
		JOIN netdevices nd ON nd.id = n.netdev AND n.ownerid IS NULL
		WHERE nd.ownerid IS NULL
			AND n.netid IN (" . implode(',', array_keys($networks)) . ")");

    if (!empty($devices)) {
        $channels[] = array('id' => '0', 'nodes' => array(), 'subs' => array(),
            'cid' => '1', 'customer' => 'Devices', 'uprate' => '128', 'upceil' => '10000',
            'downrate' => '128', 'downceil' => '10000',
            'uprate_n' => '128', 'upceil_n' => '10000',
            'downrate_n' => '128', 'downceil_n' => '10000',
            'climit' => '0', 'plimit' => '0');
        foreach ($devices as $device) {
            $channels[count($channels) - 1]['nodes'][] = array('id' => $device['id'],
                'network' => $device['netid'], 'ip' => $device['ip'],
                'name' => $device['name'], 'mac' => $device['mac']);
        }
    }
}

// open file
$fh = fopen($script_file, "w");
$fh_d = fopen($script_file_day, "w");
$fh_n = fopen($script_file_night, "w");

if (empty($fh) || empty($fh_d) || empty($fh_n)) {
    die;
}

fwrite($fh, preg_replace("/\\\\n/", "\n", $script_begin));
fwrite($fh_d, preg_replace("/\\\\n/", "\n", $script_begin_day));
fwrite($fh_n, preg_replace("/\\\\n/", "\n", $script_begin_night));

$x = XVALUE;
$mark = XVALUE;

// channels loop
foreach ($channels as $channel) {
    $c_up = $script_class_up;
    $c_down = $script_class_down;
    $c_up_day = $script_class_up_day;
    $c_down_day = $script_class_down_day;
    $c_up_night = $script_class_up_night;
    $c_down_night = $script_class_down_night;

    // make rules...
    $uprate = $channel['uprate'];
    $upceil = (!$channel['upceil'] ? $uprate : $channel['upceil']);
    $downrate = $channel['downrate'];
    $downceil = (!$channel['downceil'] ? $downrate : $channel['downceil']);
    $uprate_n = $channel['uprate_n'];
    $upceil_n = (!$channel['upceil_n'] ? $uprate_n : $channel['upceil_n']);
    $downrate_n = $channel['downrate_n'];
    $downceil_n = (!$channel['downceil_n'] ? $downrate_n : $channel['downceil_n']);
    $from = array("/\\\\n/", "/\%cid/", "/\%cname/", "/\%h/", "/\%class/",
        "/\%uprate/", "/\%upceil/", "/\%downrate/", "/\%downceil/");

    $to = array("\n", $channel['cid'], $channel['customer'], sprintf("%x", $x), sprintf("%d", $x),
        $uprate, $upceil, $downrate, $downceil);
    $c_up = preg_replace($from, $to, $c_up);
    $c_up_day = preg_replace($from, $to, $c_up_day);
    $to = array("\n", $channel['cid'], $channel['customer'], sprintf("%x", $x), sprintf("%d", $x),
        $uprate_n, $upceil_n, $downrate_n, $downceil_n);
    $c_up_night = preg_replace($from, $to, $c_up_night);

    $to = array("\n", $channel['cid'], $channel['customer'], sprintf("%x", $x), sprintf("%d", $x),
        $uprate, $upceil, $downrate, $downceil);
    $c_down = preg_replace($from, $to, $c_down);
    $c_down_day = preg_replace($from, $to, $c_down_day);
    $to = array("\n", $channel['cid'], $channel['customer'], sprintf("%x", $x), sprintf("%d", $x),
        $uprate_n, $upceil_n, $downrate_n, $downceil_n);
    $c_down_night = preg_replace($from, $to, $c_down_night);

    // ... and write to file
    fwrite($fh, $c_down);
    fwrite($fh, $c_up);
    fwrite($fh_d, $c_down_day);
    fwrite($fh_d, $c_up_day);
    fwrite($fh_n, $c_down_night);
    fwrite($fh_n, $c_up_night);

    foreach ($channel['nodes'] as $host) {
        // octal parts of IP
        $hostip = ip2long($host['ip']);
        $o1 = ($hostip >> 24) & 0xff; // first octet
        $o2 = ($hostip >> 16) & 0xff; // second octet
        $o3 = ($hostip >> 8) & 0xff; // third octet
        $o4 = $hostip & 0xff; // last octet
        $h1 = sprintf("%02x", $o1); // first octet in hex
        $h2 = sprintf("%02x", $o2); // second octet in hex
        $h3 = sprintf("%02x", $o3); // third octet in hex
        $h4 = sprintf("%02x", $o4); // last octet in hex
        $h = sprintf("%x", $o4); // last octet in hex

        $h_up = $script_filter_up;
        $h_down = $script_filter_down;
        $h_up_day = $script_filter_up_day;
        $h_down_day = $script_filter_down_day;
        $h_up_night = $script_filter_up_night;
        $h_down_night = $script_filter_down_night;

        // make rules...
        // get first mac from the list
        $mac = $host['mac'];
        if (!$script_multi_mac) {
            $mac = explode(',', $mac);
            $mac = array_shift($mac);
        }

        $from = array("/\\\\n/", "/\%n/", "/\%if/", "/\%i16/", "/\%i/", "/\%ms/",
            "/\%m/", "/\%x/", "/\%o1/", "/\%o2/", "/\%o3/", "/\%o4/",
            "/\%h1/", "/\%h2/", "/\%h3/", "/\%h4/", "/\%h/", "/\%class/");

        $to = array("\n", $host['name'], $networks[$host['network']]['interface'], $h,
            $host['ip'], $host['mac'], $mac, sprintf("%x", $mark), $o1, $o2, $o3, $o4,
            $h1, $h2, $h3, $h4, sprintf("%x", $x), sprintf("%d", $x));
        $h_up = preg_replace($from, $to, $h_up);
        $h_up_day = preg_replace($from, $to, $h_up_day);
        $h_up_night = preg_replace($from, $to, $h_up_night);

        $to = array("\n", $host['name'], $networks[$host['network']]['interface'], $h,
            $host['ip'], $host['mac'], $mac, sprintf("%x", $mark), $o1, $o2, $o3, $o4,
            $h1, $h2, $h3, $h4, sprintf("%x", $x), sprintf("%d", $x));
        $h_down = preg_replace($from, $to, $h_down);
        $h_down_day = preg_replace($from, $to, $h_down_day);
        $h_down_night = preg_replace($from, $to, $h_down_night);

        // ...write to file
        fwrite($fh, $h_down);
        fwrite($fh, $h_up);
        fwrite($fh_d, $h_down_day);
        fwrite($fh_d, $h_up_day);
        fwrite($fh_n, $h_down_night);
        fwrite($fh_n, $h_up_night);

        if ($channel['climit']) {
            $cl = $script_climit;

            $from = array("/\\\\n/", "/\%climit/", "/\%n/", "/\%if/", "/\%i16/", "/\%i/",
                "/\%ms/", "/\%m/", "/\%o1/", "/\%o2/", "/\%o3/", "/\%o4/",
                "/\%h1/", "/\%h2/", "/\%h3/", "/\%h4/");
            $to = array("\n", $channel['climit'], $host['name'],
                $networks[$host['network']]['interface'], $h, $host['ip'], $host['mac'],
                $mac, $o1, $o2, $o3, $o4, $h1, $h2, $h3, $h4);
            $cl = preg_replace($from, $to, $cl);

            fwrite($fh, $cl);
        }

        if ($channel['plimit']) {
            $pl = $script_plimit;

            $from = array("/\\\\n/", "/\%plimit/", "/\%n/", "/\%if/", "/\%i16/", "/\%i/",
                "/\%ms/", "/\%m/", "/\%o1/", "/\%o2/", "/\%o3/", "/\%o4/",
                "/\%h1/", "/\%h2/", "/\%h3/", "/\%h4/");
            $to = array("\n", $channel['plimit'], $host['name'],
                $networks[$host['network']]['interface'], $h, $host['ip'], $host['mac'],
                $mac, $o1, $o2, $o3, $o4, $h1, $h2, $h3, $h4);
            $pl = preg_replace($from, $to, $pl);

            fwrite($fh, $pl);
        }
        $mark++;
    }
    $x++;
}

// file footer
fwrite($fh, preg_replace("/\\\\n/", "\n", $script_end));
fwrite($fh_d, preg_replace("/\\\\n/", "\n", $script_end_day));
fwrite($fh_n, preg_replace("/\\\\n/", "\n", $script_end_night));

fclose($fh);
fclose($fh_d);
fclose($fh_n);

chmod($script_file, intval($script_permission, 8));
chmod($script_file_day, intval($script_permission, 8));
chmod($script_file_night, intval($script_permission, 8));

?>
