<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2017 LMS Developers
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

$this->Execute("ALTER TABLE nodes MODIFY netdev int(11) NULL");
$this->Execute("ALTER TABLE nodes ALTER COLUMN netdev SET DEFAULT NULL");

$netdevids = $this->GetCol("SELECT id FROM netdevices");
if (empty($netdevids)) {
    $this->Execute("UPDATE nodes SET netdev = NULL");
} else {
    $sql_netdevids = implode(',', $netdevids);
    $this->Execute("UPDATE nodes SET netdev = NULL WHERE netdev = 0 OR netdev NOT IN (" . $sql_netdevids . ")");
    $this->Execute("DELETE FROM netlinks WHERE src NOT IN (" . $sql_netdevids . ") OR dst NOT IN (" . $sql_netdevids . ")");
}

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2017101102', 'dbversion'));

$this->CommitTrans();
