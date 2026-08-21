<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2021 LMS Developers
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

define('DOC_FLAG_NET_ACCOUNT', 16);


if ($this->ResourceExists('vinvoicecontents', LMSDB::RESOURCE_TYPE_VIEW)) {
    $this->Execute("DROP VIEW vinvoicecontents");
}

$this->Execute(
    "CREATE VIEW vinvoicecontents AS
        (
            SELECT ic.*,
                1 AS netflag,
                ROUND(ic.value, 2) AS netprice,
                ROUND(ic.value * (1 + (t.value / 100)), 2) AS grossprice,
                ROUND(ic.value * ABS(ic.count), 2) AS netvalue,
                ROUND(ROUND(ic.value * ABS(ic.count), 2) * t.value / 100, 2) AS taxvalue,
                ROUND(ROUND(ic.value * ABS(ic.count), 2) * (1 + (t.value / 100)), 2) AS grossvalue,
                (ic.count - ic2.count) AS diff_count,
                (ic.pdiscount - ic2.pdiscount) AS diff_pdiscount,
                (ic.vdiscount - ic2.vdiscount) AS diff_vdiscount,
                (ROUND(ic.value, 2) - ROUND(ic2.value, 2)) AS diff_netprice,
                (ROUND(ic.value * (1 + (t.value / 100)), 2) - ROUND(ic2.value * (1 + (t.value / 100)), 2)) AS diff_grossprice,
                (ROUND(ic.value * ABS(ic.count), 2) - ROUND(ic2.value * ABS(ic2.count), 2)) AS diff_netvalue,
                (ROUND(ROUND(ic.value * ABS(ic.count), 2) * t.value / 100, 2)
                    - ROUND(ROUND(ic2.value * ABS(ic2.count), 2) * t.value / 100, 2)) AS diff_taxvalue,
                (ROUND(ROUND(ic.value * ABS(ic.count), 2) * (1 + (t.value / 100)), 2)
                    - ROUND(ROUND(ic2.value * ABS(ic2.count), 2) * (1 + (t.value / 100)), 2)) AS diff_grossvalue,
                (CASE WHEN t.reversecharge = 1 THEN -2 ELSE (
                    CASE WHEN t.taxed = 0 THEN -1 ELSE t.value END
                ) END) AS taxrate
            FROM invoicecontents ic
            JOIN taxes t ON t.id = ic.taxid
            JOIN documents d ON d.id = ic.docid
            LEFT JOIN documents d2 ON d2.id = d.reference
            LEFT JOIN invoicecontents ic2 ON ic2.docid = d2.id AND ic2.itemid = ic.itemid
            WHERE (d.flags & ?) > 0
        ) UNION (
            SELECT ic.*,
                0 AS netflag,
                ROUND(ic.value / (1 + (t.value / 100)), 2) AS netprice,
                ROUND(ic.value, 2) AS grossprice,
                (ROUND(ic.value * ABS(ic.count), 2)
                    - ROUND(ROUND(ic.value * ABS(ic.count), 2) * t.value / (100 + t.value), 2)) AS netvalue,
                ROUND(ROUND(ic.value * ABS(ic.count), 2) * t.value / (100 + t.value), 2) AS taxvalue,
                ROUND(ic.value * ABS(ic.count), 2) AS grossvalue,
                (ic.count - ic2.count) AS diff_count,
                (ic.pdiscount - ic2.pdiscount) AS diff_pdiscount,
                (ic.vdiscount - ic2.vdiscount) AS diff_vdiscount,
                (ROUND(ic.value / (1 + (t.value / 100)), 2) - ROUND(ic2.value / (1 + (t.value / 100)), 2)) AS diff_netprice,
                (ROUND(ic.value, 2) - ROUND(ic2.value, 2)) AS diff_grossprice,
                (ROUND(ic.value * ABS(ic.count), 2)
                    - ROUND(ROUND(ic.value * ABS(ic.count), 2) * t.value / (100 + t.value), 2)
                    - ROUND(ic2.value * ABS(ic2.count), 2)
                    + ROUND(ROUND(ic2.value * ABS(ic2.count), 2) * t.value / (100 + t.value), 2)) AS diff_netvalue,
                (ROUND(ROUND(ic.value * ABS(ic.count), 2) * t.value / (100 + t.value), 2)
                    - ROUND(ROUND(ic2.value * ABS(ic2.count), 2) * t.value / (100 + t.value), 2)) AS diff_taxvalue,
                (ROUND(ic.value * ABS(ic.count), 2) - ROUND(ic2.value * ABS(ic2.count), 2)) AS diff_grossvalue,
                (CASE WHEN t.reversecharge = 1 THEN -2 ELSE (
                    CASE WHEN t.taxed = 0 THEN -1 ELSE t.value END
                ) END) AS taxrate
            FROM invoicecontents ic
            JOIN taxes t ON t.id = ic.taxid
            JOIN documents d ON d.id = ic.docid
            LEFT JOIN documents d2 ON d2.id = d.reference
            LEFT JOIN invoicecontents ic2 ON ic2.docid = d2.id AND ic2.itemid = ic.itemid
            WHERE (d.flags & ?) = 0
        )",
    array(
        DOC_FLAG_NET_ACCOUNT,
        DOC_FLAG_NET_ACCOUNT,
    )
);
