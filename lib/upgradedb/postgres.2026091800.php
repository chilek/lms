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

define('DOC_FLAG_NET_ACCOUNT_2026091800', 16);

$this->BeginTrans();

$this->Execute(
    "CREATE OR REPLACE FUNCTION get_invoice_contents(integer) RETURNS TABLE (
        docid integer,
        itemid smallint,
        value numeric(12,5),
        pdiscount numeric(5,2),
        taxid integer,
        prodid varchar(255),
        content varchar(16),
        count numeric(9,3),
        description text,
        tariffid integer,
        vdiscount numeric(9,3),
        taxcategory smallint,
        period smallint,
        netflag integer,
        netprice numeric(12,5),
        grossprice numeric(12,5),
        netvalue numeric(12,5),
        taxvalue numeric(4,2),
        grossvalue numeric(12,5),
        diff_count numeric(9,3),
        diff_pdiscount numeric(5,2),
        diff_vdiscount numeric(9,3),
        diff_netprice numeric(12,5),
        diff_grossprice numeric(12,5),
        diff_netvalue numeric(12,5),
        diff_taxvalue numeric(4,2),
        diff_grossvalue numeric(12,5),
        taxrate numeric(4,2)
    ) AS $$
        SELECT
            ic.docid,
            ic.itemid,
            ic.value,
            ic.pdiscount,
            ic.taxid,
            ic.prodid,
            ic.content,
            ic.count,
            ic.description,
            ic.tariffid,
            ic.vdiscount,
            ic.taxcategory,
            ic.period,
            CASE
                WHEN (d.flags & ?) > 0 THEN 1
                ELSE 0
            END AS netflag,
            CASE
                WHEN (d.flags & ?) > 0 THEN ROUND(ic.value, 3)
                ELSE ROUND(ic.value / (1 + t.value / 100), 3)
            END AS netprice,
            CASE
                WHEN (d.flags & ?) > 0 THEN ROUND(ic.value * (1 + t.value / 100), 3)
                ELSE ROUND(ic.value, 3)
            END AS grossprice,
            CASE
                WHEN (d.flags & ?) > 0 THEN ROUND(ic.value * ABS(ic.count), 2)
                ELSE ROUND(ic.value * ABS(ic.count), 2) - ROUND(ROUND(ic.value * ABS(ic.count), 2) * t.value / (100 + t.value), 2)
            END AS netvalue,
            CASE
                WHEN (d.flags & ?) > 0 THEN ROUND(ROUND(ic.value * ABS(ic.count), 2) * t.value / 100, 2)
                ELSE ROUND(ROUND(ic.value * ABS(ic.count), 2) * t.value / (100 + t.value), 2)
            END AS taxvalue,
            CASE
                WHEN (d.flags & ?) > 0 THEN ROUND(ROUND(ic.value * ABS(ic.count), 2) * (1 + t.value / 100), 2)
                ELSE ROUND(ic.value * ABS(ic.count), 2)
            END AS grossvalue,
            ic.count - COALESCE(ic2.count, 0) AS diff_count,
            ic.pdiscount - COALESCE(ic2.pdiscount, 0) AS diff_pdiscount,
            ic.vdiscount - COALESCE(ic2.vdiscount, 0) AS diff_vdiscount,
            CASE
                WHEN (d.flags & ?) > 0 THEN ROUND(ic.value, 3) - ROUND(COALESCE(ic2.value, 0), 3)
                ELSE ROUND(ic.value / (1 + t.value / 100), 3) - ROUND(COALESCE(ic2.value, 0) / (1 + t.value / 100), 3)
                END AS diff_netprice,
            CASE
                WHEN (d.flags & ?) > 0 THEN ROUND(ic.value * (1 + t.value / 100), 3) - ROUND(COALESCE(ic2.value, 0) * (1 + t.value / 100), 3)
                ELSE ROUND(ic.value, 3) - ROUND(COALESCE(ic2.value, 0), 3)
            END AS diff_grossprice,
            CASE
                WHEN (d.flags & ?) > 0 THEN ROUND(ic.value * ABS(ic.count), 2) - ROUND(COALESCE(ic2.value, 0) * ABS(COALESCE(ic2.count, 0)), 2)
                ELSE ROUND(ic.value * ABS(ic.count), 2) - ROUND(ROUND(ic.value * ABS(ic.count), 2) * t.value / (100 + t.value), 2) - ROUND(COALESCE(ic2.value, 0) * ABS(COALESCE(ic2.count, 0)), 2) + ROUND(ROUND(COALESCE(ic2.value, 0) * ABS(COALESCE(ic2.count, 0)), 2) * t.value / (100 + t.value), 2)
            END AS diff_netvalue,
            CASE
                WHEN (d.flags & ?) > 0 THEN ROUND(ROUND(ic.value * ABS(ic.count), 2) * t.value / 100, 2) - ROUND(ROUND(COALESCE(ic2.value, 0) * ABS(COALESCE(ic2.count, 0)), 2) * t.value / 100, 2)
                ELSE ROUND(ROUND(ic.value * ABS(ic.count), 2) * t.value / (100 + t.value), 2) - ROUND(ROUND(COALESCE(ic2.value, 0) * ABS(COALESCE(ic2.count, 0)), 2) * t.value / (100 + t.value), 2)
            END AS diff_taxvalue,
            CASE
                WHEN (d.flags & ?) > 0 THEN ROUND(ROUND(ic.value * ABS(ic.count), 2) * (1 + t.value / 100), 2) - ROUND(ROUND(COALESCE(ic2.value, 0) * ABS(COALESCE(ic2.count, 0)), 2) * (1 + t.value / 100), 2)
                ELSE ROUND(ic.value * ABS(ic.count), 2) - ROUND(COALESCE(ic2.value, 0) * ABS(COALESCE(ic2.count, 0)), 2)
                END AS diff_grossvalue,
            CASE
                WHEN t.reversecharge = 1 THEN -2
                ELSE
                    CASE
                        WHEN t.taxed = 0 THEN -1
                        ELSE t.value
                    END
            END AS taxrate
        FROM invoicecontents ic
        JOIN taxes t ON t.id = ic.taxid
        JOIN documents d ON d.id = ic.docid
        LEFT JOIN documents d2 ON d2.id = d.reference
        LEFT JOIN invoicecontents ic2 ON ic2.docid = d2.id AND ic2.itemid = ic.itemid
        WHERE $1 IS NULL OR d.customerid = $1
    $$ LANGUAGE SQL IMMUTABLE",
    [
        DOC_FLAG_NET_ACCOUNT_2026091800,
        DOC_FLAG_NET_ACCOUNT_2026091800,
        DOC_FLAG_NET_ACCOUNT_2026091800,
        DOC_FLAG_NET_ACCOUNT_2026091800,
        DOC_FLAG_NET_ACCOUNT_2026091800,
        DOC_FLAG_NET_ACCOUNT_2026091800,
        DOC_FLAG_NET_ACCOUNT_2026091800,
        DOC_FLAG_NET_ACCOUNT_2026091800,
        DOC_FLAG_NET_ACCOUNT_2026091800,
        DOC_FLAG_NET_ACCOUNT_2026091800,
        DOC_FLAG_NET_ACCOUNT_2026091800,
    ]
);

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2026091800', 'dbversion'));

$this->CommitTrans();
