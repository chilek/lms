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

if (!$this->ResourceExists('assignments.recipient_address_id2', LMSDB::RESOURCE_TYPE_COLUMN)) {
    $this->Execute("ALTER TABLE assignments ADD COLUMN recipient_address_id2 int(11) DEFAULT NULL");
    $this->Execute("ALTER TABLE assignments ADD CONSTRAINT assignments_recipient_address_id2_fkey
        FOREIGN KEY (recipient_address_id2) REFERENCES addresses (id) ON DELETE SET NULL ON UPDATE CASCADE");
}

if (!$this->ResourceExists('documents.recipient_address_id2', LMSDB::RESOURCE_TYPE_COLUMN)) {
    $this->Execute("ALTER TABLE documents ADD COLUMN recipient_address_id2 int(11) DEFAULT NULL");
    $this->Execute("ALTER TABLE documents ADD CONSTRAINT documents_recipient_address_id2_fkey
        FOREIGN KEY (recipient_address_id2) REFERENCES addresses (id) ON DELETE SET NULL ON UPDATE CASCADE");
}

if (!$this->ResourceExists('documents.recipient_ten2', LMSDB::RESOURCE_TYPE_COLUMN)) {
    $this->Execute("ALTER TABLE documents ADD COLUMN recipient_ten2 varchar(50) DEFAULT NULL");
}

if (!$this->ResourceExists('documents.recipient_type2', LMSDB::RESOURCE_TYPE_COLUMN)) {
    $this->Execute("ALTER TABLE documents ADD COLUMN recipient_type2 smallint DEFAULT NULL");
}

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2026052500', 'dbversion'));

$this->CommitTrans();
