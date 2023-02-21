<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2023 LMS Developers
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

define('DOC_FLAG_NET_ACCOUNT_2023022100', 16);

$this->BeginTrans();

$this->Execute(
    "CREATE TABLE tariffpricevariants (
        id int(11) NOT NULL auto_increment,
        tariffid int(11) NOT NULL,
        quantity_threshold int(11) NOT NULL,
        net_price numeric(9,3) DEFAULT 0 NOT NULL,
        gross_price numeric(9,3) DEFAULT 0 NOT NULL,
        PRIMARY KEY (id),
        CONSTRAINT tariffpricevariants_tariffid_fkey
            FOREIGN KEY (tariffid) REFERENCES tariffs (id) ON DELETE CASCADE ON UPDATE CASCADE,
        UNIQUE KEY tariffpricevariants_tariffid_quantity_threshold_ukey (tariffid, quantity_threshold)
        CONSTRAINT tariffpricevariants_quantity_threshold_ckey CHECK (quantity_threshold > 0)
    ) ENGINE=InnoDB"
);

$this->Execute("ALTER TABLE tariffs MODIFY COLUMN value decimal(9,3) NOT NULL DEFAULT 0");
$this->Execute("ALTER TABLE tariffs MODIFY COLUMN netvalue decimal(9,3) DEFAULT NULL");

$this->Execute("ALTER TABLE liabilities MODIFY COLUMN value decimal(9,3) NOT NULL DEFAULT 0");
$this->Execute("ALTER TABLE liabilities MODIFY COLUMN netvalue decimal(9,3) DEFAULT NULL");

$this->Execute("ALTER TABLE assignments MODIFY COLUMN vdiscount decimal(9,3) NOT NULL DEFAULT 0");

$this->Execute("ALTER TABLE invoicecontents MODIFY COLUMN vdiscount decimal(9,3) NOT NULL DEFAULT 0");

$this->Execute("DROP VIEW vinvoicecontents");

$this->Execute(
    "CREATE VIEW vinvoicecontents AS
    SELECT ic.docid AS docid, ic.itemid AS itemid, ic.value AS value, ic.pdiscount AS pdiscount,
        ic.taxid AS taxid, ic.prodid AS prodid, ic.content AS content, ic.count AS count, ic.description AS description,
        ic.tariffid AS tariffid, ic.vdiscount AS vdiscount, ic.taxcategory AS taxcategory, ic.period AS period,
        (CASE WHEN (d.flags & ?) > 0 THEN 1 ELSE 0 END) AS netflag,
        (CASE WHEN (d.flags & ?) > 0 THEN
                ROUND(ic.value, 3)
            ELSE
                ROUND((ic.value / (1 + (t.value / 100))), 3)
        END) AS netprice,
        (CASE WHEN (d.flags & ?) > 0 THEN
                ROUND((ic.value * (1 + (t.value / 100))), 3)
            ELSE
                ROUND(ic.value, 3)
        END) AS grossprice,
        (CASE WHEN (d.flags & ?) > 0 THEN
                ROUND((ic.value * ABS(ic.count)), 2)
            ELSE
                (ROUND((ic.value * ABS(ic.count)), 2) - ROUND(((ROUND((ic.value * ABS(ic.count)), 2) * t.value) / (100 + t.value)), 2))
        END) AS netvalue,
        (CASE WHEN (d.flags & ?) > 0 THEN
                ROUND(((ROUND((ic.value * ABS(ic.count)), 2) * t.value) / 100), 2)
            ELSE
                ROUND(((ROUND((ic.value * ABS(ic.count)), 2) * t.value) / (100 + t.value)), 2)
        END) AS taxvalue,
        (CASE WHEN (d.flags & ?) > 0 THEN
                ROUND((ROUND((ic.value * ABS(ic.count)), 2) * (1 + (t.value / 100))), 2)
            ELSE
                ROUND((ic.value * ABS(ic.count)), 2)
        END) AS grossvalue,
        (ic.count - ic2.count) AS diff_count,
        (ic.pdiscount - ic2.pdiscount) AS diff_pdiscount,
        (ic.vdiscount - ic2.vdiscount) AS diff_vdiscount,
        (CASE WHEN (d.flags & ?) > 0 THEN
                ROUND(ic.value, 3) - ROUND(ic2.value, 3)
            ELSE
                ROUND((ic.value / (1 + (t.value / 100))), 3) - ROUND((ic2.value / (1 + (t.value / 100))), 3)
        END) AS diff_netprice,
        (CASE WHEN (d.flags & ?) > 0 THEN
                ROUND((ic.value * (1 + (t.value / 100))), 3) - ROUND((ic2.value * (1 + (t.value / 100))) , 3)
            ELSE
                ROUND(ic.value, 3) - ROUND(ic2.value, 3)
        END) AS diff_grossprice,
        (CASE WHEN (d.flags & ?) > 0 THEN
                ROUND((ic.value * ABS(ic.count)), 2) - ROUND((ic2.value * ABS(ic2.count)), 2)
            ELSE
                ((ROUND((ic.value * ABS(ic.count)), 2) - ROUND(((ROUND((ic.value * ABS(ic.count)), 2) * t.value) / (100 + t.value)),2)) - ROUND((ic2.value * ABS(ic2.count)), 2)) + ROUND(((ROUND((ic2.value * ABS(ic2.count)), 2) * t.value) / (100 + t.value)) ,2)
        END) AS diff_netvalue,
        (CASE WHEN (d.flags & ?) > 0 THEN
                ROUND(((ROUND((ic.value * ABS(ic.count)), 2) * t.value) / 100),2) - ROUND(((ROUND((ic2.value * ABS(ic2.count)), 2) * t.value) / 100), 2)
            ELSE
                ROUND(((ROUND((ic.value * ABS(ic.count)), 2) * t.value) / (100 + t.value)),2) - ROUND(((ROUND((ic2.value * ABS(ic2.count)), 2) * t.value) / (100 + t.value)), 2)
        END) AS diff_taxvalue,
        (CASE WHEN (d.flags & ?) > 0 THEN
                ROUND((ROUND((ic.value * ABS(ic.count)), 2) * (1 + (t.value / 100))),2) - ROUND((ROUND((ic2.value * ABS(ic2.count)), 2) * (1 + (t.value / 100))), 2)
            ELSE
                ROUND((ic.value * ABS(ic.count)), 2) - ROUND((ic2.value * ABS(ic2.count)), 2)
        END) AS diff_grossvalue,
        (CASE WHEN t.reversecharge = 1
            THEN -2
            ELSE (CASE WHEN t.taxed = 0
                THEN -1
                ELSE t.value
            END)
        END) AS taxrate
    FROM invoicecontents ic
    JOIN taxes t ON t.id = ic.taxid
    JOIN documents d ON d.id = ic.docid
    LEFT JOIN documents d2 ON d2.id = d.reference
    LEFT JOIN invoicecontents ic2 ON ic2.docid = d2.id AND ic2.itemid = ic.itemid",
    array(
        DOC_FLAG_NET_ACCOUNT_2023022100,
        DOC_FLAG_NET_ACCOUNT_2023022100,
        DOC_FLAG_NET_ACCOUNT_2023022100,
        DOC_FLAG_NET_ACCOUNT_2023022100,
        DOC_FLAG_NET_ACCOUNT_2023022100,
        DOC_FLAG_NET_ACCOUNT_2023022100,
        DOC_FLAG_NET_ACCOUNT_2023022100,
        DOC_FLAG_NET_ACCOUNT_2023022100,
        DOC_FLAG_NET_ACCOUNT_2023022100,
        DOC_FLAG_NET_ACCOUNT_2023022100,
        DOC_FLAG_NET_ACCOUNT_2023022100,
    )
);

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2023022100', 'dbversion'));

$this->CommitTrans();
