<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2026 LMS Developers
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

$indexes = array(
    'cash' => array('importid', 'docid'),
    'ksefinvoices' => array('ksefinvoices_ksef_number_idx'),
    'invoicecontents' => array('docid'),
    'cashimport' => array('sourceid'),
    'customercontacts' => array('contact'),
    'netdevicemacs' => array('netdevicemacs_netdevid_idx'),
    'userdivisions' => array('userdivisions_userid_idx'),
    'voip_cdr' => array('voip_cdr_direction_idx'),
);

foreach ($indexes as $table => $tableIndexes) {
    foreach ($tableIndexes as $index) {
        if ($this->GetOne(
            "SELECT COUNT(*) FROM information_schema.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?",
            array($table, $index)
        )) {
            $this->Execute("ALTER TABLE " . $table . " DROP INDEX " . $index);
        }
    }
}

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2026112702', 'dbversion'));

$this->CommitTrans();
