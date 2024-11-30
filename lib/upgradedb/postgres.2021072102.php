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

define('DOC_FLAG_NET_ACCOUNT_2021072102', 16);


if ($this->ResourceExists('vinvoicecontents', LMSDB::RESOURCE_TYPE_VIEW)) {
    $this->Execute("DROP VIEW vinvoicecontents");
}

$this->Execute(
    "CREATE VIEW vinvoicecontents AS
        SELECT ic.*,
            (CASE WHEN (d.flags & ?) > 0 THEN 1 ELSE 0 END) AS netflag,
            (CASE WHEN (d.flags & ?) > 0
                THEN
                    ROUND(ic.value, 2)
                ELSE
                    ROUND(ic.value / (1 + (t.value / 100)), 2)
                END) AS netprice,
            (CASE WHEN (d.flags & ?) > 0
                THEN
                    ROUND(ic.value * (1 + (t.value / 100)), 2)
                ELSE
                    ROUND(ic.value, 2)
                END) AS grossprice,
            (CASE WHEN (d.flags & ?) > 0
                THEN
                    ROUND(ic.value * ABS(ic.count), 2)
                ELSE
                    ROUND(ic.value * ABS(ic.count), 2)
                        - ROUND(ROUND(ic.value * ABS(ic.count), 2) * t.value / (100 + t.value), 2)
                END) AS netvalue,
            (CASE WHEN (d.flags & ?) > 0
                THEN
                    ROUND(ic.value * ABS(ic.count), 2)
                ELSE
                    ROUND(ROUND(ic.value * ABS(ic.count), 2) * t.value / (100 + t.value), 2)
                END) AS taxvalue,
            (CASE WHEN (d.flags & ?) > 0
                THEN
                    ROUND(ROUND(ic.value * ABS(ic.count), 2) * (1 + (t.value / 100)), 2)
                ELSE
                    ROUND(ic.value * ABS(ic.count), 2)
                END) AS grossvalue,
            (CASE WHEN t.reversecharge = 1 THEN -2 ELSE (
                CASE WHEN t.taxed = 0 THEN -1 ELSE t.value END
            ) END) AS taxrate
        FROM invoicecontents ic
        JOIN taxes t ON t.id = ic.taxid
        JOIN documents d ON d.id = ic.docid",
    array(
        DOC_FLAG_NET_ACCOUNT_2021072102,
        DOC_FLAG_NET_ACCOUNT_2021072102,
        DOC_FLAG_NET_ACCOUNT_2021072102,
        DOC_FLAG_NET_ACCOUNT_2021072102,
        DOC_FLAG_NET_ACCOUNT_2021072102,
        DOC_FLAG_NET_ACCOUNT_2021072102,
    )
);
