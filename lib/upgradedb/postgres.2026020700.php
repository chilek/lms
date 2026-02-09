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
        CREATE SEQUENCE ksefinvoices_id_seq;
        CREATE TABLE ksefinvoices (
            id integer DEFAULT nextval('ksefinvoices_id_seq'::text) NOT NULL,
            division_id integer NOT NULL
                CONSTRAINT ksefinvoices_division_id_fkey REFERENCES divisions (id) ON DELETE RESTRICT ON UPDATE CASCADE,
            issue_date bigint NOT NULL,
            permanent_storage_date timestamptz NOT NULL,
            ksef_number varchar(40) NOT NULL,
            invoice_number varchar(256) NOT NULL,
            seller_ten varchar(10) NOT NULL,
            seller_name varchar(512) NOT NULL,
            buyer_identifier_type smallint NOT NULL,
            buyer_identifier_value varchar(50) NOT NULL,
            buyer_name varchar(512) NOT NULL,
            net_amount numeric(12,5) NOT NULL,
            gross_amount numeric(12,5) NOT NULL,
            vat_amount numeric(12,5) NOT NULL,
            currency varchar(3) NOT NULL,
            invoicing_mode smallint NOT NULL,
            invoice_type smallint NOT NULL,
            form_system_code varchar(15) NOT NULL,
            form_schema_version varchar(15) NOT NULL,
            form_value varchar(15) NOT NULL,
            invoice_hash varchar(44) NOT NULL,
            corrected_invoice_hash varchar(44) DEFAULT NULL,
            PRIMARY KEY (id),
            CONSTRAINT ksefinvoices_ksef_number_ukey UNIQUE (ksef_number)
        );
        CREATE INDEX ksefinvoices_issue_date_idx ON ksefinvoices (issue_date);
        CREATE INDEX ksefinvoices_permanent_storage_date_idx ON ksefinvoices (permanent_storage_date);
        CREATE INDEX ksefinvoices_ksef_number_idx ON ksefinvoices (ksef_number);
        CREATE INDEX ksefinvoices_seller_ten_idx ON ksefinvoices (seller_ten);
        CREATE INDEX ksefinvoices_buyer_identifier_value_idx ON ksefinvoices (buyer_identifier_value);
        CREATE INDEX ksefinvoices_invoice_type_idx ON ksefinvoices (invoice_type)
    ");

    $this->Execute("
        CREATE SEQUENCE ksefinvoicethirdsubjects_id_seq;
        CREATE TABLE ksefinvoicethirdsubjects (
                id integer DEFAULT nextval('ksefinvoicethirdsubjects_id_seq'::text) NOT NULL,
            ksefinvoiceid integer NOT NULL
                CONSTRAINT ksefinvoicethirdsubjects_ksefinvoiceid_fkey REFERENCES ksefinvoices (id) ON DELETE CASCADE ON UPDATE CASCADE,
            identifier_type smallint NOT NULL,
            identifier_value varchar(50) NOT NULL,
            name varchar(512) NOT NULL,
            role integer NOT NULL,
            PRIMARY KEY (id)
        );
        CREATE INDEX ksefinvoicethirdsubjects_identifier_type_idx ON ksefinvoicethirdsubjects (identifier_type);
        CREATE INDEX ksefinvoicethirdsubjects_identifier_value_idx ON ksefinvoicethirdsubjects (identifier_value);
        CREATE INDEX ksefinvoicethirdsubjects_role_idx ON ksefinvoicethirdsubjects (role)
    ");
}

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2026020700', 'dbversion'));

$this->CommitTrans();
