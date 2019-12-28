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
lms-makedhcpconf.php
(C) 2001-2017 LMS Developers

EOF;
    exit(0);
}

if (array_key_exists('help', $options)) {
    print <<<EOF
lms-makedhcpconf.php
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
lms-makedhcpconf.php
(C) 2001-2017 LMS Developers

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
    die('Unable to read configuration file [' . $CONFIG_FILE . ']!' . PHP_EOL);
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

// Include required files (including sequence is important)

require_once(LIB_DIR . DIRECTORY_SEPARATOR . 'common.php');
require_once(LIB_DIR . DIRECTORY_SEPARATOR . 'language.php');
require_once(LIB_DIR . DIRECTORY_SEPARATOR . 'definitions.php');

$config_owneruid = ConfigHelper::getConfig('dhcp.config_owneruid', 0, true);
$config_ownergid = ConfigHelper::getConfig('dhcp.config_ownergid', 0, true);
$config_permission = ConfigHelper::getConfig('dhcp.config_permission', "0644");
$config_file = ConfigHelper::getConfig('dhcp.config_file', "/etc/dhcpd.conf");
$default_lease_time = ConfigHelper::getConfig('dhcp.default_lease_time', 86400);
$max_lease_time = ConfigHelper::getConfig('dhcp.max_lease_time', 86400);
$enable_option82 = ConfigHelper::checkConfig('dhcp.enable_option82');
$use_network_authtype = ConfigHelper::checkConfig('dhcp.use_network_authtype');
$config_begin = ConfigHelper::getConfig(
    'dhcp.begin',
    "ddns-update-style none;\nlog-facility local6;\ndefault-lease-time $default_lease_time;\nmax-lease-time $max_lease_time;\n"
);

// we're looking for dhcp-mac config sections
$config_macs = array();
foreach ($CONFIG as $key => $value) {
    if (preg_match('/dhcp-[0-9a-f]{2}:.*/i', $key)) {
        $config_macs[] = preg_replace("/^dhcp-/", "", $key);
    }
}

$maxlease = $max_lease_time;
if (!empty($maxlease)) {
    $header .= "max-lease-time " . $maxlease . ";\n\n";
}


$existing_networks = $DB->GetCol("SELECT name FROM networks");

// get selected networks from ini file
$networks = ConfigHelper::getConfig('dhcp.networks', '', true);
$networks = preg_split('/(\s+|\s*,\s*)/', $networks, -1, PREG_SPLIT_NO_EMPTY);

// exclude networks set in config value
$excluded_networks = ConfigHelper::getConfig('dhcp.excluded_networks', '', true);
$excluded_networks = preg_split('/(\s+|\s*,\s*)/', $excluded_networks, -1, PREG_SPLIT_NO_EMPTY);

if (empty($networks)) {
    $networks = array_diff($existing_networks, $excluded_networks);
} else {
    $networks = array_intersect(array_diff($networks, $excluded_networks), $existing_networks);
}

$networks = $DB->GetAllByKey("SELECT id, name, address, INET_ATON(mask) AS mask, gateway,
		dns, dns2, domain, wins, dhcpstart, dhcpend, interface
	FROM networks WHERE 1=1"
    . ($use_network_authtype ? " AND (networks.authtype & " . SESSIONTYPE_DHCP . ") > 0" : '')
    . (empty($networks) ? '' : " AND UPPER(name) IN ('" . implode("','", array_map('mb_strtoupper', $networks)) . "')") . "
	ORDER BY interface, name", 'id');
if (empty($networks)) {
    die("Fatal error: No networks selected for processing, exiting." . PHP_EOL);
}

// fix interface names
foreach ($networks as &$network) {
    $network['interface'] = preg_replace('/:.+$/', '', $network['interface']);
}
unset($network);

// customer groups
$customergroups = ConfigHelper::getConfig('dhcp.customergroups', '', true);
$customergroups = preg_split('/(\s+|\s*,\s*)/', $customergroups, -1, PREG_SPLIT_NO_EMPTY);
if (empty($customergroups)) {
    $customerids = array();
} else {
    $customerids = $DB->GetRow("SELECT DISTINCT a.customerid FROM customerassignments a
		JOIN customergroups g ON g.id = a.customergroupid
		WHERE UPPER(g.name) IN ('" . implode("','", array_map('mb_strtoupper', $customergroups)) . "')");
}

$fh = fopen($config_file, "w");
if (empty($fh)) {
    die("Fatal error: Unable to write " . $config_file . ", exiting." . PHP_EOL);
}

// prefix
fwrite($fh, preg_replace("/\\\\n/", "\n", $config_begin));
$prefix = "";
if (!empty($CONFIG['dhcp']['options'])) {
    foreach ($CONFIG['dhcp']['options'] as $name => $value) {
        $prefix .= "option " . $name . " " . $value . ";\n";
    }
}
fwrite($fh, $prefix);

foreach ($networks as $networkid => $net) {
    $net_prefix = "";
    // network prefix
    if (!empty($net['interface']) && !empty($lastif) && (strcmp($lastif, $net['interface']) != 0)) {
        $net_prefix .= "}\n";
    }

    if (!empty($net['interface']) && strcmp($lastif, $net['interface']) != 0) {
        $net_prefix .= "\nshared-network LMS-" . $net['interface'] . " {\n";
        $lastif = $net['interface'];
    }

    // TODO: lease time for network set by LMS-UI
    $default_lease = $default_lease_time;
    $max_lease = $max_lease_time;
    $options = array();
    $options['subnet-mask'] = long_ip($net['mask']);
    if (!empty($net['gateway'])) {
        $options['routers'] = $net['gateway'];
    }
    if (!empty($net['dns'])) {
        $options['domain-name-servers'] = $net['dns'] . (!empty($net['dns2']) ? ", " . $net['dns2'] : "");
    }
    if (!empty($net['domain'])) {
        $options['domain-name'] = '"' . $net['domain'] . '"';
    }
    if (!empty($net['wins'])) {
        $options['netbios-name-servers'] = $net['wins'];
    }

    if (!empty($CONFIG['dhcp-' . $net['name']])) {
        if (!empty($CONFIG['dhcp-' . $net['name']]['default_lease_time'])) {
            $default_lease = $CONFIG['dhcp-' . $net['name']]['default_lease_time'];
        }
        if (!empty($CONFIG['dhcp-' . $net['name']]['max_lease_time'])) {
            $max_lease = $CONFIG['dhcp-' . $net['name']]['max_lease_time'];
        }
        if (!empty($CONFIG['dhcp-' . $net['name']]['options'])) {
            $options = array_merge($options, $CONFIG['dhcp-' . $net['name']]['options']);
        }
    }

    $net_prefix .= "\n\tsubnet " . long_ip($net['address']) . " netmask " . long_ip($net['mask'])
        . " { # Network " . $net['name'] . " (ID: " . $net['id'] . ")\n"
        . (!empty($net['dhcpstart']) ? "\t\trange " . $net['dhcpstart'] . " " . $net['dhcpend'] . ";\n" : "");
    if ($default_lease != $default_lease_time) {
        $net_prefix .= "\t\tdefault-lease-time " . $default_lease . ";\n";
    }
    if ($max_lease != $max_lease_time) {
        $net_prefix .= "\t\tmax-lease-time " . $max_lease . ";\n";
    }
    foreach ($options as $name => $value) {
        $net_prefix .= "\t\toption " . $name . " " . $value . ";\n";
    }
    $net_prefix .= "\n";
    fwrite($fh, $net_prefix);

    // get nodes for current network
    $nodes = $DB->GetAll("SELECT n.id, n.name, mac, INET_NTOA(ipaddr) AS ip,
			INET_NTOA(ipaddr_pub) AS ip_pub, ownerid FROM vnodes n
		WHERE netid = ?
		" . (empty($customerids) ? '' : " AND (n.ownerid IS NULL OR n.ownerid IN (" . implode(',', $customerids) . "))") . "
		ORDER BY ipaddr", array($networkid));

    if (empty($nodes)) {
        fwrite($fh, "\t}\n");
        continue;
    }

    $netdevices = array();
    foreach ($nodes as $node) {
        if (empty($node['ownerid'])) {
            foreach (explode(',', $node['mac']) as $mac) {
                $netdevices[$mac] = true;
            }
        }
    }

    foreach ($nodes as $node) {
        // get node configuration from database
        if ($enable_option82) {
            // get data for option 82
            $dhcp_relay = $DB->GetRow(
                "SELECT nd.id, m.mac, n.port
						FROM netdevices d, nodes n, nodes nd, macs m
						WHERE n.id = ? AND n.netdev = d.id
						AND d.id = nd.netdev AND nd.ownerid IS NULL
						AND nd.id = m.nodeid",
                array($node['id'])
            );
        } else {
            $dhcp_relay = array();
        }

        $macs = explode(",", $node['mac']);
        $hosts = array();
        foreach ($macs as $key => $mac) {
            $hosts[$mac]['name'] = $node['name'] . ($key == 0 ? "" : "-" . $node['id'] . "-" . $key);
            $hosts[$mac]['fixed_address'] = $node['ip'];
            $hosts[$mac]['id'] = $node['id'];
            $hosts[$mac]['options'] = array();
            if (!empty($dhcp_relay['mac'])) {
                $hosts[$mac]['options']['agent.remote-id'] = $dhcp_relay['mac'];
            }
            if (!empty($dhcp_relay['port'])) {
                $hosts[$mac]['options']['agent.circuit-id'] = $dhcp_relay['port'];
            }
        }

        // get node configuration from ini
        $fixed_address = (!empty($CONFIG['dhcp-' . $node['ip']]) ? $node['ip'] : null);
        $name = (!empty($CONFIG['dhcp-' . $node['name']]) ? $node['name'] : null);
        $ini_macs = array();
        foreach ($macs as $mac) {
            foreach ($config_macs as $config_mac) {
                if (preg_match('/' . $config_mac . '/i', $mac)) {
                    $ini_macs[$mac] = $CONFIG['dhcp-' . $config_mac];
                }
            }
        }

        if ($fixed_address != null) {
            foreach ($hosts as $key => $host) {
                if (!empty($CONFIG['dhcp-' . $fixed_address]['name'])) {
                    $hosts[$key]['name'] = $CONFIG['dhcp-' . $fixed_address]['name'];
                }
                if (!empty($CONFIG['dhcp-' . $fixed_address]['options'])) {
                    $hosts[$key]['options'] = array_merge($host['options'], $CONFIG['dhcp-' . $fixed_address]['options']);
                }
            }
        }
        if ($name != null) {
            reset($hosts);
            while (($host = current($hosts)) !== false && $host['name'] != $name) {
                next($hosts);
            }
            if ($host !== false) {
                if (!empty($CONFIG['dhcp-' . $name]['fixed_address'])) {
                    $hosts[key($hosts)]['fixed_address'] = $CONFIG['dhcp-' . $name]['fixed_address'];
                }
                if (!empty($CONFIG['dhcp-' . $name]['hardware_ethernet'])) {
                    $hosts[key($hosts)]['hardware_ethernet'] = $CONFIG['dhcp-' . $name]['hardware_ethernet'];
                }
                if (!empty($CONFIG['dhcp-' . $name]['options'])) {
                    $hosts[key($hosts)]['options'] = array_merge($hosts[key($hosts)]['options'], $CONFIG['dhcp-' . $name]['options']);
                }
            }
        }
        foreach ($ini_macs as $ini_mac => $config_mac) {
            if (!empty($config_mac['fixed_address'])) {
                $hosts[$ini_mac]['fixed_address'] = $config_mac['fixed_address'];
            }
            if (!empty($config_mac['name'])) {
                $hosts[$ini_mac]['name'] = $config_mac['name'];
            }
            if (!empty($config_mac['options'])) {
                $hosts[$ini_mac]['options'] = array_merge($hosts[$ini_mac]['options'], $config_mac['options']);
            }
        }

        $node_info = "";
        foreach ($hosts as $mac => $host) {
            if (empty($node['ownerid']) || !isset($netdevices[$mac])) {
                $node_info .= "\t\thost " . $host['name'] . " { # ID: " . $host['id'] . "\n";
                $node_info .= "\t\t\thardware ethernet " . $mac . ";\n";
                $node_info .= "\t\t\tfixed-address " . $host['fixed_address'] . ";\n";
                foreach ($host['options'] as $name => $value) {
                    $node_info .= "\t\t\toption " . $name . " " . $value . ";\n";
                }
                $node_info .= "\t\t}\n";
            }
        }
        fwrite($fh, $node_info);
    }
    // close subnet section
    fwrite($fh, "\t}\n");
}

// close shared-network section
if (!empty($lastif)) {
    fwrite($fh, "}\n");
}

fclose($fh);

chmod($config_file, intval($config_permission, 8));
chown($config_file, $config_owneruid);
chgrp($config_file, $config_ownergid);

?>
