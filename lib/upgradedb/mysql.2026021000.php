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

if (!$this->ResourceExists('ksefinvoicetags', LMSDB::RESOURCE_TYPE_TABLE)) {
    $this->Execute("
        CREATE TABLE ksefinvoicetags (
            id int(11) NOT NULL AUTO_INCREMENT,
            name text NOT NULL,
            PRIMARY KEY (id)
        ) ENGINE=InnoDB
    ");
}

if (!$this->ResourceExists('ksefinvoicetagassignments', LMSDB::RESOURCE_TYPE_TABLE)) {
    $this->Execute("
        CREATE TABLE ksefinvoicetagassignments (
            id int(11) NOT NULL AUTO_INCREMENT,
            ksef_invoice_id int(11) NOT NULL,
            ksef_invoice_tag_id int(11) NOT NULL,
            PRIMARY KEY (id),
            CONSTRAINT ksefinvoicetagassignments_ksef_invoice_id_fkey
                FOREIGN KEY (ksef_invoice_id) REFERENCES ksefinvoices (id) ON DELETE CASCADE ON UPDATE CASCADE,
            CONSTRAINT ksefinvoicetagassignments_ksef_invoice_tag_id_fkey
                FOREIGN KEY (ksef_invoice_tag_id) REFERENCES ksefinvoicetags (id) ON DELETE CASCADE ON UPDATE CASCADE
        ) ENGINE=InnoDB
    ");
}

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2026021000', 'dbversion'));

$this->CommitTrans();
