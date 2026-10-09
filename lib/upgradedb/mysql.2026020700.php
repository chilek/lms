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

if (!$this->ResourceExists('ksefinvoices', LMSDB::RESOURCE_TYPE_TABLE)) {
    $this->Execute("
        CREATE TABLE ksefinvoices (
            id int(11) NOT NULL AUTO_INCREMENT,
            division_id int(11) NOT NULL,
            issue_date int(16) NOT NULL,
            permanent_storage_date datetime(6) NOT NULL,
            ksef_number varchar(40) NOT NULL,
            invoice_number varchar(256) NOT NULL,
            seller_ten varchar(10) NOT NULL,
            seller_name varchar(512) NOT NULL,
            buyer_identifier_type smallint NOT NULL,
            buyer_identifier_value varchar(50) NOT NULL,
            buyer_name varchar(512) NOT NULL,
            net_amount decimal(12,5) NOT NULL,
            gross_amount decimal(12,5) NOT NULL,
            vat_amount decimal(12,5) NOT NULL,
            currency varchar(3) NOT NULL,
            invoicing_mode smallint NOT NULL,
            invoice_type smallint NOT NULL,
            form_system_code varchar(15) NOT NULL,
            form_schema_version varchar(15) NOT NULL,
            form_value varchar(15) NOT NULL,
            invoice_hash varchar(44) NOT NULL,
            corrected_invoice_hash varchar(44) DEFAULT NULL,
            PRIMARY KEY (id),
            CONSTRAINT ksefinvoices_division_id_fkey
                FOREIGN KEY (division_id) REFERENCES divisions (id) ON DELETE RESTRICT ON UPDATE CASCADE,
            UNIQUE KEY ksefinvoices_ksef_number_ukey (ksef_number),
            KEY ksefinvoices_issue_date_idx (issue_date),
            KEY ksefinvoices_permanent_storage_date_idx (permanent_storage_date),
            KEY ksefinvoices_ksef_number_idx (ksef_number),
            KEY ksefinvoices_seller_ten_idx (seller_ten),
            KEY ksefinvoices_buyer_identifier_value_idx (buyer_identifier_value),
            KEY ksefinvoices_invoice_type_idx (invoice_type)
        ) ENGINE=InnoDB
    ");

    $this->Execute("
        CREATE TABLE ksefinvoicethirdsubjects (
            id int(11) NOT NULL AUTO_INCREMENT,
            ksefinvoiceid int(11) NOT NULL,
            identifier_type smallint NOT NULL,
            identifier_value varchar(50) NOT NULL,
            name varchar(512) NOT NULL,
            role int(11) NOT NULL,
            PRIMARY KEY (id),
            CONSTRAINT ksefinvoicethirdsubjects_ksefinvoiceid_fkey
                FOREIGN KEY (ksefinvoiceid) REFERENCES ksefinvoices (id) ON DELETE CASCADE ON UPDATE CASCADE,
            KEY ksefinvoicethirdsubjects_identifier_type_idx (identifier_type),
            KEY ksefinvoicethirdsubjects_identifier_value_idx (identifier_value),
            KEY ksefinvoicethirdsubjects_role_idx (role)
        ) ENGINE=InnoDB
    ");
}

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2026020700', 'dbversion'));

$this->CommitTrans();
