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
 */

// PLEASE DO NOT MODIFY ANYTHING BELOW THIS LINE UNLESS YOU KNOW
// *EXACTLY* WHAT ARE YOU DOING!!!
// *******************************************************************

ini_set('error_reporting', E_ALL & ~E_NOTICE & ~E_DEPRECATED);

$script_parameters = [
    'section:' => 's:',
    'jobs:' => 'j:',
    'test' => 't',
];

$script_help = <<<EOF
-s, --section=<section-name>    section name from lms configuration where settings
                                are stored (default: fping)
-j, --jobs=<number>             number of fping processes running in parallel
                                (default: value of 'jobs' option in configuration
                                or 4)
-t, --test                      only list on-line hosts, do not update database
EOF;

require_once('script-options.php');

$test = isset($options['test']);
$show_timing = $test && !$quiet;

$config_section = isset($options['section']) && preg_match('/^[a-z0-9-_]+$/i', $options['section'])
    ? $options['section']
    : 'fping';

/**
 * Prints time elapsed since previous call (or since process start for the first call).
 * Used only in test mode to find out which phase of the script is slow.
 *
 * @param string $label
 * @param bool $enabled
 */
function report_timing($label, $enabled)
{
    static $last = null;

    $now = microtime(true);
    if ($last === null) {
        $last = isset($_SERVER['REQUEST_TIME_FLOAT']) ? $_SERVER['REQUEST_TIME_FLOAT'] : $now;
    }
    if ($enabled) {
        printf('[time] %s: %.3f s' . PHP_EOL, $label, $now - $last);
    }
    $last = $now;
}

report_timing('startup (script-options, config, autoload, DB)', $show_timing);

/**
 * Splits list of names separated by whitespaces, commas or semicolons.
 *
 * @param string $value
 * @return string[]
 */
function parse_names_list($value)
{
    $names = preg_split('/[\s,;]+/', (string) $value, -1, PREG_SPLIT_NO_EMPTY);
    return $names === false ? [] : $names;
}

/**
 * Runs fping, passes the list of hosts through its stdin (no temporary file)
 * and returns addresses of on-line hosts (option -a).
 *
 * The list of hosts is distributed round-robin among $jobs fping processes
 * which work in parallel. fping sends probes with a fixed interval (-i, 10 ms
 * by default), so total time is roughly proportional to number of hosts
 * divided by number of processes.
 *
 * @param string $command fping command, may contain additional options
 * @param string[] $hosts
 * @param int $jobs number of parallel fping processes
 * @return array|null array('hosts' => string[], 'status' => int) or null on failure
 */
function fping_alive_hosts($command, array $hosts, $jobs = 4)
{
    $hosts = array_values($hosts);
    $jobs = max(1, min((int) $jobs, count($hosts)));

    $chunks = array_fill(0, $jobs, []);
    foreach ($hosts as $i => $host) {
        $chunks[$i % $jobs][] = $host;
    }

    $processes = [];

    foreach ($chunks as $index => $chunk) {
        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['file', '/dev/null', 'w'],
        ];

        $process = proc_open($command . ' -a -r1', $descriptors, $pipes);
        if (!is_resource($process)) {
            foreach ($processes as $started) {
                fclose($started['pipe']);
                proc_terminate($started['process']);
                proc_close($started['process']);
            }
            return null;
        }

        // fping reads the whole list of targets before it starts pinging
        // and prints nothing until then, so it is safe to write the whole
        // list first and read the output afterwards.
        $data = implode(PHP_EOL, $chunk) . PHP_EOL;
        $length = strlen($data);
        $written = 0;
        while ($written < $length) {
            $result = @fwrite($pipes[0], substr($data, $written));
            if ($result === false || $result === 0) {
                break;
            }
            $written += $result;
        }
        fclose($pipes[0]);

        stream_set_blocking($pipes[1], false);

        $processes[$index] = [
            'process' => $process,
            'pipe' => $pipes[1],
            'output' => '',
        ];
    }

    // collect output of all processes
    $streams = [];
    foreach ($processes as $index => $process) {
        $streams[$index] = $process['pipe'];
    }

    while (!empty($streams)) {
        $read = $streams;
        $write = null;
        $except = null;

        if (stream_select($read, $write, $except, 5) === false) {
            break;
        }

        foreach ($read as $stream) {
            $index = array_search($stream, $streams, true);
            if ($index === false) {
                continue;
            }

            $data = fread($stream, 65536);
            if ($data !== false && $data !== '') {
                $processes[$index]['output'] .= $data;
            }
            if (feof($stream)) {
                unset($streams[$index]);
            }
        }
    }

    $status = 0;
    $alive = [];

    foreach ($processes as $process) {
        fclose($process['pipe']);
        $code = proc_close($process['process']);

        if ($code < 0 || $status < 0) {
            $status = -1;
        } elseif ($code > $status) {
            $status = $code;
        }

        foreach (preg_split('/\R/', $process['output'], -1, PREG_SPLIT_NO_EMPTY) as $line) {
            $line = trim($line);
            if (check_ip($line)) {
                $alive[$line] = $line;
            }
        }
    }

    return [
        'hosts' => array_values($alive),
        'status' => $status,
    ];
}

// ---- configuration -------------------------------------------------------

$fping_command = trim(ConfigHelper::getConfig($config_section . '.command', 'fping'));
$included_names = array_map('mb_strtoupper', parse_names_list(ConfigHelper::getConfig($config_section . '.networks', '')));
$excluded_names = array_map('mb_strtoupper', parse_names_list(ConfigHelper::getConfig($config_section . '.excluded_networks', '')));
$included = array_flip($included_names);
$excluded = array_flip($excluded_names);

$jobs = isset($options['jobs']) ? $options['jobs'] : ConfigHelper::getConfig($config_section . '.jobs', 4);
$jobs = filter_var($jobs, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($jobs === false) {
    fwrite(STDERR, 'Fatal error: number of jobs must be a positive integer.' . PHP_EOL);
    exit(1);
}

// ---- collect addresses of nodes from selected networks -------------------

$networks = $DB->GetAll('SELECT name, address, mask FROM networks');
if (empty($networks)) {
    $networks = [];
}

if (!$quiet && !empty($included_names)) {
    $existing = [];
    foreach ($networks as $network) {
        $existing[mb_strtoupper($network['name'])] = true;
    }
    foreach ($included_names as $name) {
        if (!isset($existing[$name])) {
            fwrite(STDERR, "Warning: network '$name' not found." . PHP_EOL);
        }
    }
}

$hosts = [];
$selected_networks = 0;
foreach ($networks as $network) {
    $name = mb_strtoupper($network['name']);

    if (!empty($included) && !isset($included[$name])) {
        continue;
    }
    if (isset($excluded[$name])) {
        continue;
    }

    $mask = ip2long($network['mask']);
    if ($mask === false) {
        continue;
    }

    $selected_networks++;

    $start = (int) $network['address'];
    $end = $start + ((~$mask & 0xFFFFFFFF) + 1);

    $ips = $DB->GetCol(
        'SELECT inet_ntoa(ipaddr) FROM nodes WHERE ipaddr > ? AND ipaddr < ?',
        [$start, $end]
    );

    if (!empty($ips)) {
        foreach ($ips as $ip) {
            $hosts[$ip] = $ip;
        }
    }
}
$hosts = array_values($hosts);

report_timing('collecting hosts from database', $show_timing);
if ($show_timing) {
    echo '[info] networks: ' . $selected_networks . ' of ' . count($networks)
        . ', hosts to check: ' . count($hosts) . PHP_EOL;
    echo "[info] fping command: $fping_command -a -r1 (parallel processes: "
        . max(1, min($jobs, count($hosts))) . ')' . PHP_EOL;
}

if (empty($hosts)) {
    if (!$quiet) {
        echo 'No hosts to check.' . PHP_EOL;
    }
    exit(0);
}

// ---- ping them -----------------------------------------------------------

$result = fping_alive_hosts($fping_command, $hosts, $jobs);
if ($result === null) {
    fwrite(STDERR, "Fatal error: unable to run '$fping_command'." . PHP_EOL);
    exit(1);
}

// fping exit codes: 0 - all hosts reachable, 1 - some hosts unreachable
// (normal here), 2 - some addresses not found, 3 - invalid arguments,
// 4 - system call failure
if ($result['status'] >= 3 || $result['status'] < 0) {
    fwrite(STDERR, "Fatal error: '$fping_command' failed (exit code " . $result['status'] . ')' . PHP_EOL);
    exit(1);
}

$online = $result['hosts'];

report_timing('fping', $show_timing);
if ($show_timing) {
    echo '[info] hosts on-line: ' . count($online) . PHP_EOL;
}

if (!$quiet) {
    foreach ($online as $ip) {
        echo $ip . PHP_EOL;
    }
}

// ---- update lastonline in batches ----------------------------------------

if (!$test) {
    foreach (array_chunk($online, 500) as $chunk) {
        $addresses = array_map('ip2long', $chunk);
        $DB->Execute(
            'UPDATE nodes SET lastonline = ?NOW? WHERE ipaddr IN ?',
            [$addresses]
        );
    }
}

exit(0);
