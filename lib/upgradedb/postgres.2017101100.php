<?php

/**
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

$this->BeginTrans();

$sql_tables = array(
    'documents' => array(
        'column' => 'customerid',
        'ondelete' => 'SET NULL',
        'onupdate' => 'CASCADE',
    ),
    'cash' => array(
        'column' => 'customerid',
        'ondelete' => 'SET NULL',
        'onupdate' => 'CASCADE',
    ),
    'nodesessions' => array(
        'column' => 'customerid',
        'ondelete' => 'SET NULL',
        'onupdate' => 'CASCADE',
    ),
    'rttickets' => array(
        'column' => 'customerid',
        'ondelete' => 'SET NULL',
        'onupdate' => 'CASCADE',
    ),
    'rtmessages' => array(
        'column' => 'customerid',
        'ondelete' => 'SET NULL',
        'onupdate' => 'CASCADE',
    ),
    'events' => array(
        'column' => 'customerid',
        'ondelete' => 'SET NULL',
        'onupdate' => 'CASCADE',
    ),
    'messageitems' => array(
        'column' => 'customerid',
        'ondelete' => 'SET NULL',
        'onupdate' => 'CASCADE',
    ),
    'up_rights_assignments' => array(
        'column' => 'customerid',
        'ondelete' => 'CASCADE',
        'onupdate' => 'CASCADE',
    ),
    'up_customers' => array(
        'column' => 'customerid',
        'ondelete' => 'CASCADE',
        'onupdate' => 'CASCADE',
    ),
    'up_info_changes' => array(
        'column' => 'customerid',
        'ondelete' => 'CASCADE',
        'onupdate' => 'CASCADE',
    ),
    'nodes' => array(
        'column' => 'ownerid',
        'ondelete' => 'CASCADE',
        'onupdate' => 'CASCADE',
    ),
    'voipaccounts' => array(
        'column' => 'ownerid',
        'ondelete' => 'CASCADE',
        'onupdate' => 'CASCADE',
    ),
    'passwd' => array(
        'column' => 'ownerid',
        'ondelete' => 'SET NULL',
        'onupdate' => 'CASCADE',
    ),
    'domains' => array(
        'column' => 'ownerid',
        'ondelete' => 'SET NULL',
        'onupdate' => 'CASCADE',
    ),
);

$this->Execute("DROP VIEW vnodealltariffs");

$cids = $this->GetCol("SELECT id FROM customers");
if (!empty($cids)) {
    $cid_string = implode(',', $cids);
    foreach ($sql_tables as $sql_table => $props) {
        $this->Execute("ALTER TABLE " . $sql_table . " ALTER COLUMN " . $props['column'] . " DROP NOT NULL");
        $this->Execute("ALTER TABLE " . $sql_table . " ALTER COLUMN " . $props['column'] . " SET DEFAULT NULL");
        $this->Execute("UPDATE " . $sql_table . " SET " . $props['column'] . " = NULL WHERE " . $props['column'] . " = 0
			OR " . $props['column'] . " NOT IN (" . $cid_string . ")");
    }
}

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2017101100', 'dbversion'));

$this->CommitTrans();
