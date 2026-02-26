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

if (!$this->ResourceExists('ksefinvoices.currency_value', LMSDB::RESOURCE_TYPE_COLUMN)) {
    $this->Execute("ALTER TABLE ksefinvoices ADD COLUMN currency_value numeric(22,6) NOT NULL DEFAULT 1.0");
}

if (!$this->ResourceExists('ksefinvoices.from_date', LMSDB::RESOURCE_TYPE_COLUMN)) {
    $this->Execute("ALTER TABLE ksefinvoices ADD COLUMN from_date int(16)");
    $this->Execute("UPDATE ksefinvoices SET from_date = issue_date");
    $this->Execute("ALTER TABLE ksefinvoices MODIFY COLUMN from_date int(16) NOT NULL");
}

if (!$this->ResourceExists('ksefinvoices.to_date', LMSDB::RESOURCE_TYPE_COLUMN)) {
    $this->Execute("ALTER TABLE ksefinvoices ADD COLUMN to_date int(16) DEFAULT NULL");
}

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2026022600', 'dbversion'));

$this->CommitTrans();
