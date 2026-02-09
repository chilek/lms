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

if (!$this->ResourceExists('ksefinvoices.bank_account', LMSDB::RESOURCE_TYPE_COLUMN)) {
    $this->Execute("ALTER TABLE ksefinvoices ADD COLUMN bank_account varchar(48) DEFAULT NULL");
}
if (!$this->ResourceExists('ksefinvoices.bank_name', LMSDB::RESOURCE_TYPE_COLUMN)) {
    $this->Execute("ALTER TABLE ksefinvoices ADD COLUMN bank_name varchar(48) DEFAULT NULL");
}
if (!$this->ResourceExists('ksefinvoices.pay_date', LMSDB::RESOURCE_TYPE_COLUMN)) {
    $this->Execute("ALTER TABLE ksefinvoices ADD COLUMN pay_date int(16) DEFAULT NULL");
}
if (!$this->ResourceExists('ksefinvoices.pay_type', LMSDB::RESOURCE_TYPE_COLUMN)) {
    $this->Execute("ALTER TABLE ksefinvoices ADD COLUMN pay_type smallint DEFAULT 0");
}
if (!$this->ResourceExists('ksefinvoices.buyer_id', LMSDB::RESOURCE_TYPE_COLUMN)) {
    $this->Execute("ALTER TABLE ksefinvoices ADD COLUMN buyer_id varchar(50) DEFAULT NULL");
}
if ($this->ResourceExists('ksefinvoiceitems', LMSDB::RESOURCE_TYPE_TABLE)) {
    $this->Execute("
        CREATE TABLE ksefinvoiceitems (
            ksef_invoice_id int(11) NOT NULL,
            item_id smallint NOT NULL,
            name varchar(512) DEFAULT NULL,
            prod_id varchar(50) DEFAULT NULL,
            unit varchar(512) DEFAULT NULL,
            count numeric(22,6) DEFAULT NULL,
            net_flag smallint DEFAULT 1,
            price numeric(24,8) NOT NULL,
            value numeric(16,2) NOT NULL,
            tax_rate numeric(4,2) NOT NULL,
            taxed smallint DEFAULT 1,
            reverse_charge smallint DEFAULT 0,
            eu smallint DEFAULT 0,
            export smallint DEFAULT 0,
            product_service_group smallint DEFAULT 0,
            CONSTRAINT ksefinvoiceitems_ksef_invoice_id_fkey
                FOREIGN KEY (ksef_invoice_id) REFERENCES ksefinvoices (id) ON DELETE CASCADE ON UPDATE CASCADE
        )
    ");
}

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2026020800', 'dbversion'));

$this->CommitTrans();
