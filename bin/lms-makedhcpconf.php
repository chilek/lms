#!/usr/bin/env php
<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2026 LMS Developers
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

$script_parameters = array(
    'section:' => 's:',
    'customergroups:' => 'g:',
);

$script_help = <<<EOF
-s, --section=<section-name>    section name from lms configuration where settings
                                are stored
-g, --customergroups=<group1,group2,...>
                                allow to specify customer groups to which customers
                                should be assigned
EOF;

require_once('script-options.php');

$SYSLOG = SYSLOG::getInstance();

// Initialize Session, Auth and LMS classes

$AUTH = null;
$LMS = new LMS($DB, $AUTH, $SYSLOG);

// REPLACE THIS WITH PATH TO YOUR CONFIG FILE

$quiet = isset($options['quiet']);

$config_section = isset($options['section']) && preg_match('/^[a-z0-9-_]+$/i', $options['section'])
    ? $options['section']
    : 'dhcp';

$config_owneruid = ConfigHelper::getConfig($config_section . '.config_owneruid', 0, true);
$config_ownergid = ConfigHelper::getConfig($config_section . '.config_ownergid', 0, true);
$config_permission = ConfigHelper::getConfig($config_section . '.config_permission', "0644");
$config_file = ConfigHelper::getConfig($config_section . '.config_file', "/etc/dhcpd.conf");
$default_lease_time = ConfigHelper::getConfig($config_section . '.default_lease_time', 86400);
$max_lease_time = ConfigHelper::getConfig($config_section . '.max_lease_time', 86400);
$enable_option82 = ConfigHelper::checkConfig($config_section . '.enable_option82');
$use_network_authtype = ConfigHelper::checkConfig($config_section . '.use_network_authtype');
$filename_pattern = ConfigHelper::getConfig($config_section . '.filename_pattern', '', true);
$speed_unit_type = intval(ConfigHelper::getConfig('phpui.speed_unit_type', 1000));
$config_begin = ConfigHelper::getConfig(
    $config_section . '.begin',
    "ddns-update-style none;\nlog-facility local6;\ndefault-lease-time $default_lease_time;\nmax-lease-time $max_lease_time;\n"
);
$global_network_begin = ConfigHelper::getConfig($config_section . '.network_begin', '', true);
$global_range_format = ConfigHelper::getConfig($config_section . '.range_format', 'range %start% %end%;');

// we're looking for dhcp-mac config sections
$config_macs = array();
foreach ($CONFIG as $key => $value) {
    if (preg_match('/dhcp-[0-9a-f]{2}:.*/i', $key)) {
        $config_macs[] = preg_replace("/^dhcp-/", "", $key);
    }
}

$existing_networks = $DB->GetCol("SELECT name FROM networks");

// get selected networks from ini file
$networks = ConfigHelper::getConfig($config_section . '.networks', '', true);
$networks = preg_split('/(\s+|\s*,\s*)/', $networks, -1, PREG_SPLIT_NO_EMPTY);

// exclude networks set in config value
$excluded_networks = ConfigHelper::getConfig($config_section . '.excluded_networks', '', true);
$excluded_networks = preg_split('/(\s+|\s*,\s*)/', $excluded_networks, -1, PREG_SPLIT_NO_EMPTY);

if (empty($networks)) {
    $networks = array_diff($existing_networks, $excluded_networks);
} else {
    $networks = array_intersect(array_diff($networks, $excluded_networks), $existing_networks);
}

$networks = $DB->GetAllByKey(
    "SELECT
        id,
        name,
        address,
        INET_ATON(mask) AS mask,
        gateway,
        dns,
        dns2,
        domain,
        wins,
        dhcpstart,
        dhcpend,
        interface
    FROM networks
    WHERE 1 = 1"
    . ($use_network_authtype ? " AND (networks.authtype & " . SESSIONTYPE_DHCP . ") > 0" : '')
    . (empty($networks) ? '' : " AND UPPER(name) IN ('" . implode("','", array_map('mb_strtoupper', $networks)) . "')") . "
    ORDER BY interface, name",
    'id'
);
if (empty($networks)) {
    die('Fatal error: No networks selected for processing, exiting.' . PHP_EOL);
}

// fix interface names
foreach ($networks as &$network) {
    $network['interface'] = preg_replace('/:.+$/', '', $network['interface']);
}
unset($network);

// customer groups
$customergroups = ConfigHelper::getConfig($config_section . '.customergroups', '', true);

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
                WHERE vcustomerassignments.customerid = %customerid_alias%
                AND UPPER(customergroups.name) IN ('" . implode("', '", $customergroup_ANDs_regular) . "')
                HAVING COUNT(*) = " . count($customergroup_ANDs_regular) . ')')
            . (empty($customergroup_ANDs_inversed) ? '' : " AND NOT EXISTS (SELECT COUNT(*) FROM customergroups
                JOIN vcustomerassignments ON vcustomerassignments.customergroupid = customergroups.id
                WHERE vcustomerassignments.customerid = %customerid_alias%
                AND UPPER(customergroups.name) IN ('" . implode("', '", $customergroup_ANDs_inversed) . "')
                HAVING COUNT(*) > 0)")
            . ')';
    }
    $customergroups = ' AND (' . implode(' OR ', $customergroup_ORs) . ')';
}

$fh = fopen($config_file, "w");
if (empty($fh)) {
    die('Fatal error: Unable to write ' . $config_file . ', exiting.' . PHP_EOL);
}

// prefix
fwrite(
    $fh,
    preg_replace(
        array(
            '/\r/',
            '/\\\\n/',
        ),
        array(
            '',
            "\n",
        ),
        $config_begin
    )
);
$prefix = "";
if (!empty($CONFIG['dhcp']['options'])) {
    foreach ($CONFIG['dhcp']['options'] as $name => $value) {
        $prefix .= 'option ' . $name . ' ' . $value . ";\n";
    }
}
fwrite($fh, $prefix . "\n");

$host_content = '';
$line_prefix = '';
$lastif = '';

foreach ($networks as $networkid => $net) {
    $net_prefix = "";
    // network prefix
    if (!empty($net['interface']) && !empty($lastif) && (strcmp($lastif, $net['interface']) != 0)) {
        $net_prefix .= "}\n";
    }

    if (!empty($net['interface']) && strcmp($lastif, $net['interface']) != 0) {
        $net_prefix .= "\nshared-network LMS-" . $net['interface'] . " {\n";
        $lastif = $net['interface'];
        $line_prefix = "\t";
    } else {
        if (!empty($net['interface']) && !empty($lastif) && strcmp($lastif, $net['interface'])) {
            $line_prefix = '';
        }
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

    $range_format = $global_range_format;
    $network_begin = $global_network_begin;

    if (!empty($CONFIG[$config_section . '-' . $net['name']])) {
        if (!empty($CONFIG[$config_section . '-' . $net['name']]['begin'])) {
            $begin = $CONFIG[$config_section . '-' . $net['name']]['begin'];
        }
        if (!empty($CONFIG[$config_section . '-' . $net['name']]['default_lease_time'])) {
            $default_lease = $CONFIG[$config_section . '-' . $net['name']]['default_lease_time'];
        }
        if (!empty($CONFIG[$config_section . '-' . $net['name']]['max_lease_time'])) {
            $max_lease = $CONFIG[$config_section . '-' . $net['name']]['max_lease_time'];
        }
        if (!empty($CONFIG[$config_section . '-' . $net['name']]['options'])) {
            $options = array_merge($options, $CONFIG[$config_section . '-' . $net['name']]['options']);
        }
        if (!empty($CONFIG[$config_section . '-' . $net['name']]['range_format'])) {
            $range_format = $CONFIG[$config_section . '-' . $net['name']]['range_format'];
        }
        if (!empty($CONFIG[$config_section . '-' . $net['name']]['network_begin'])) {
            $network_begin = $CONFIG[$config_section . '-' . $net['name']]['network_begin'];
        }
    } else {
        $begin = '';
    }

    $net_prefix .= $line_prefix . 'subnet ' . long_ip($net['address']) . ' netmask ' . long_ip($net['mask'])
        . ' { # Network ' . $net['name'] . ' (ID: ' . $net['id'] . ")\n";

    if (!empty($begin)) {
        $begin = str_replace(
            array(
                "\r",
                '\\n',
                '\\t',
            ),
            array(
                '',
                "\n",
                "\t",
            ),
            $begin
        );
    } elseif (!empty($network_begin)) {
        $begin = str_replace(
            array(
                "\r",
                '\\n',
                '\\t',
            ),
            array(
                '',
                "\n",
                "\t",
            ),
            $network_begin
        );
    }

    if (!empty($begin)) {
        foreach (explode("\n", $begin) as $line) {
            if (!empty($line)) {
                $net_prefix .= $line_prefix . "\t" . $line . "\n";
            }
        }
    }

    if (!empty($net['dhcpstart'])) {
        $range = str_replace(
            array(
                "\r",
                '\\n',
                '\\t',
                '%start%',
                '%end%',
            ),
            array(
                '',
                "\n",
                "\t",
                $net['dhcpstart'],
                $net['dhcpend'],
            ),
            $range_format
        );
        foreach (explode("\n", $range) as $line) {
            if (!empty($line)) {
                $net_prefix .= $line_prefix . "\t" . $line . "\n";
            }
        }
    }

    if ($default_lease != $default_lease_time) {
        $net_prefix .= $line_prefix . "\tdefault-lease-time " . $default_lease . ";\n";
    }
    if ($max_lease != $max_lease_time) {
        $net_prefix .= $line_prefix . "\tmax-lease-time " . $max_lease . ";\n";
    }
    foreach ($options as $name => $value) {
        $net_prefix .= $line_prefix . "\toption " . $name . " " . $value . ";\n";
    }
    fwrite($fh, $net_prefix);

    // get nodes for current network
    $nodes = $DB->GetAll(
        "SELECT
            n.id,
            n.name,
            mac,
            INET_NTOA(ipaddr) AS ip,
            INET_NTOA(ipaddr_pub) AS ip_pub,
            ownerid,
            downrate,
            downceil,
            uprate,
            upceil
        FROM vnodealltariffs n
        WHERE netid = ?
            AND (n.ownerid IS NULL OR 1 = 1"
            . ($customergroups ? str_replace('%customerid_alias%', 'n.ownerid', $customergroups) : '')
            . ')'
        . ' ORDER BY ipaddr',
        array(
            $networkid,
        )
    );

    if (empty($nodes)) {
        fwrite($fh, $line_prefix . "}\n");
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
                "SELECT
                    nd.id,
                    m.mac,
                    n.port
                FROM netdevices d, nodes n, nodes nd, macs m
                WHERE n.id = ?
                    AND n.netdev = d.id
                    AND d.id = nd.netdev
                    AND nd.ownerid IS NULL
                    AND nd.id = m.nodeid",
                array($node['id'])
            );
        } else {
            $dhcp_relay = array();
        }

        $macs = explode(",", $node['mac']);
        $hosts = array();
        foreach ($macs as $key => $mac) {
            $hosts[$mac] = $node;
            $hosts[$mac]['name'] = $node['name'] . ($key == 0 ? '' : '-' . $node['id'] . '-' . $key);
            $hosts[$mac]['fixed_address'] = $node['ip'];
            $hosts[$mac]['options'] = array();
            if (!empty($dhcp_relay['mac'])) {
                $hosts[$mac]['options']['agent.remote-id'] = $dhcp_relay['mac'];
            }
            if (!empty($dhcp_relay['port'])) {
                $hosts[$mac]['options']['agent.circuit-id'] = $dhcp_relay['port'];
            }
        }

        // get node configuration from ini
        $fixed_address = (!empty($CONFIG[$config_section . '-' . $node['ip']]) ? $node['ip'] : null);
        $name = (!empty($CONFIG[$config_section . '-' . $node['name']]) ? $node['name'] : null);
        $ini_macs = array();
        foreach ($macs as $mac) {
            foreach ($config_macs as $config_mac) {
                if (preg_match('/' . $config_mac . '/i', $mac)) {
                    $ini_macs[$mac] = $CONFIG[$config_section . '-' . $config_mac];
                }
            }
        }

        if ($fixed_address != null) {
            foreach ($hosts as $key => $host) {
                if (!empty($CONFIG[$config_section . '-' . $fixed_address]['name'])) {
                    $hosts[$key]['name'] = $CONFIG[$config_section . '-' . $fixed_address]['name'];
                }
                if (!empty($CONFIG[$config_section . '-' . $fixed_address]['options'])) {
                    $hosts[$key]['options'] = array_merge($host['options'], $CONFIG[$config_section . '-' . $fixed_address]['options']);
                }
            }
        }
        if ($name != null) {
            reset($hosts);
            while (($host = current($hosts)) !== false && $host['name'] != $name) {
                next($hosts);
            }
            if ($host !== false) {
                if (!empty($CONFIG[$config_section . '-' . $name]['fixed_address'])) {
                    $hosts[key($hosts)]['fixed_address'] = $CONFIG[$config_section . '-' . $name]['fixed_address'];
                }
                if (!empty($CONFIG[$config_section . '-' . $name]['hardware_ethernet'])) {
                    $hosts[key($hosts)]['hardware_ethernet'] = $CONFIG[$config_section . '-' . $name]['hardware_ethernet'];
                }
                if (!empty($CONFIG[$config_section . '-' . $name]['options'])) {
                    $hosts[key($hosts)]['options'] = array_merge($hosts[key($hosts)]['options'], $CONFIG[$config_section . '-' . $name]['options']);
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

        foreach ($hosts as $mac => $host) {
            if (empty($node['ownerid']) || !isset($netdevices[$mac])) {
                $host_content .= 'host ' . $host['name'] . ' { # ID: ' . $host['id'] . "\n";
                $host_content .= "\thardware ethernet " . $mac . ";\n";
                $host_content .= "\tfixed-address " . $host['fixed_address'] . ";\n";

                if (!empty($filename_pattern)) {
                    $host_content .= "\tfilename \""
                        . str_replace(
                            array(
                                '%kilo_downrate%',
                                '%kilo_uprate%',
                                '%kilo_downceil%',
                                '%kilo_upceil%',
                                '%mega_downrate%',
                                '%mega_uprate%',
                                '%mega_downceil%',
                                '%mega_upceil%',
                            ),
                            array(
                                $host['downrate'],
                                $host['uprate'],
                                $host['downceil'],
                                $host['upceil'],
                                round($host['downrate'] / $speed_unit_type),
                                round($host['uprate'] / $speed_unit_type),
                                round($host['downceil'] / $speed_unit_type),
                                round($host['upceil'] / $speed_unit_type),
                            ),
                            $filename_pattern
                        ) . "\";\n";
                }

                $mac = preg_replace('/[^0-9a-fA-F]/', '', $mac);
                foreach ($host['options'] as $name => $value) {
                    $value = str_replace(
                        array('%mac%', '%MAC%'),
                        array(strtolower($mac), strtoupper($mac)),
                        $value
                    );
                    $host_content .= "\toption " . $name . " " . $value . ";\n";
                }
                $host_content .= "}\n";
            }
        }
    }
    // close subnet section
    fwrite($fh, $line_prefix . "}\n");
}

// close shared-network section
if (!empty($lastif)) {
    fwrite($fh, "}\n");
}

fwrite($fh, $host_content);

fclose($fh);

chmod($config_file, intval($config_permission, 8));
chown($config_file, $config_owneruid);
chgrp($config_file, $config_ownergid);
