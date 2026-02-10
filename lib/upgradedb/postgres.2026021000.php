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
        CREATE SEQUENCE ksefinvoicetags_id_seq;
        CREATE TABLE ksefinvoicetags (
            id integer DEFAULT nextval('ksefinvoicetags_id_seq'::text) NOT NULL,
            name text NOT NULL,
            PRIMARY KEY (id)
        )
    ");
}

if (!$this->ResourceExists('ksefinvoicetagassignments', LMSDB::RESOURCE_TYPE_TABLE)) {
    $this->Execute("
        CREATE SEQUENCE ksefinvoicetagassignments_id_seq;
        CREATE TABLE ksefinvoicetagassignments (
            id integer DEFAULT nextval('ksefinvoicetagassignments_id_seq'::text) NOT NULL,
            ksef_invoice_id integer NOT NULL
                CONSTRAINT ksefinvoicetagassignments_ksef_invoice_id_fkey REFERENCES ksefinvoices (id) ON DELETE CASCADE ON UPDATE CASCADE,
            ksef_invoice_tag_id integer NOT NULL
                CONSTRAINT ksefinvoicetagassignments_ksef_invoice_tag_id_fkey REFERENCES ksefinvoicetags (id) ON DELETE CASCADE ON UPDATE CASCADE,
            PRIMARY KEY (id)
        )
    ");
}

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2026021000', 'dbversion'));

$this->CommitTrans();
