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

if (!$this->ResourceExists('ksefinvoices.corrected_ksef_number', LMSDB::RESOURCE_TYPE_COLUMN)) {
    $this->Execute("
        ALTER TABLE ksefinvoices ADD COLUMN corrected_ksef_number varchar(44) DEFAULT NULL;
        CREATE INDEX ksefinvoices_corrected_ksef_number_idx ON ksefinvoices (corrected_ksef_number)
    ");
}

if (!$this->ResourceExists('ksefinvoices.corrected_invoice_number', LMSDB::RESOURCE_TYPE_COLUMN)) {
    $this->Execute("
        ALTER TABLE ksefinvoices ADD COLUMN corrected_invoice_number varchar(256) DEFAULT NULL
    ");
}

if (!$this->ResourceExists('ksefinvoices.corrected_invoice_issue_date', LMSDB::RESOURCE_TYPE_COLUMN)) {
    $this->Execute("
        ALTER TABLE ksefinvoices ADD COLUMN corrected_invoice_issue_date bigint DEFAULT NULL
    ");
}

if (!$this->ResourceExists('ksefinvoices.posting', LMSDB::RESOURCE_TYPE_COLUMN)) {
    $this->Execute("
        ALTER TABLE ksefinvoices ADD COLUMN posting smallint DEFAULT 1
    ");
}

if (!$this->ResourceExists('ksefinvoices.notes', LMSDB::RESOURCE_TYPE_COLUMN)) {
    $this->Execute("
        ALTER TABLE ksefinvoices ADD COLUMN notes text DEFAULT NULL
    ");
}

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2026020801', 'dbversion'));

$this->CommitTrans();
