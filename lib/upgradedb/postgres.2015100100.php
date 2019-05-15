<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2015 LMS Developers
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
 */

$this->BeginTrans();

$this->Execute("ALTER TABLE users ALTER COLUMN rights TYPE text");

$rightmap = array(
    0 => 'full_access',
    1 => 'read_only',
    2 => 'node_connections',
    3 => 'finances_management',
    4 => 'reload',
    5 => 'customer_management',
    6 => 'node_management',
    7 => 'traffic_stats',
    8 => 'messaging',
    9 => 'helpdesk_administration',
    10 => 'helpdesk_operation',
    11 => 'hosting_management',
    12 => 'configuration',
    13 => 'network_management',
    14 => 'timetable_management',
    15 => 'daemon_management',
    16 => 'cash_operations',
    17 => 'customer_group_management',
    18 => 'node_group_management',
    19 => 'customer_group_assignments',
    20 => 'node_group_assignments',
    21 => 'hide_summaries',
    22 => 'voip_account_management',
    23 => 'userpanel_management',
    24 => 'hide_sysinfo',
    25 => 'hide_links',
    26 => 'hide_finances',
    27 => 'reports',
    28 => 'cash_registry_administration',
    29 => 'transaction_logs',
    30 => 'hide_voip_passwords',
    31 => 'traffic_stats_compacting',
    249 => 'backup_management_forbidden',
    253 => 'user_management_forbidden',
    255 => 'no_access',
);

$users = $this->GetAll("SELECT id, rights FROM users");
foreach ($users as $user) {
    $mask = $user['rights'];
    $len = strlen($mask);
    $bin = '';
    $rights = array();

    for ($cnt = $len; $cnt > 0; $cnt--) {
        $bin = sprintf('%04b', hexdec($mask[$cnt - 1])) . $bin;
    }

    $len = strlen($bin);
    for ($cnt = $len - 1; $cnt >= 0; $cnt--) {
        if ($bin[$cnt] == '1' && array_key_exists($len - $cnt - 1, $rightmap)) {
            $rights[] = $rightmap[$len - $cnt - 1];
        }
    }

    $this->Execute("UPDATE users SET rights = ? WHERE id = ?", array(implode(',', $rights), $user['id']));
}

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2015100100', 'dbversion'));

$this->CommitTrans();
