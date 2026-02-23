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

if (!defined('DOC_INVOICE')) {
    define('DOC_INVOICE', 1);
}
if (!defined('DOC_CNOTE')) {
    define('DOC_CNOTE', 3);
}


$invoicecontents = $this->GetAll(
    "SELECT
        ic.docid,
        d.type AS doctype,
        d.reference,
        ic.itemid,
        ic.count,
        ic.value,
        ic.pdiscount,
        ic.vdiscount
    FROM invoicecontents ic
    JOIN documents d ON d.id = ic.docid
    WHERE d.type = ?
        OR (d.type = ?
            AND EXISTS (SELECT 1 FROM documents d2 WHERE d2.reference = d.id))
    ORDER BY d.cdate, ic.docid",
    array(
        DOC_CNOTE,
        DOC_INVOICE,
    )
);

if (!empty($invoicecontents)) {
    foreach ($invoicecontents as $ic) {
        $docid = $ic['docid'];
        $itemid = $ic['itemid'];
        $refdoc = $ic['reference'];
        if ($ic['doctype'] == DOC_INVOICE) {
            $e_values[$docid][$itemid] = array(
                'count' => $ic['count'],
                'value' => $ic['value'],
                'pdiscount' => $ic['pdiscount'],
                'vdiscount' => $ic['vdiscount'],
            );
        } else {
            if (!isset($e_values[$refdoc][$itemid])) {
                die('Fatal error: encountered corrupted correction note chain for document #' . $docid . '!' . PHP_EOL);
            }
            $refic = $e_values[$refdoc][$itemid];
            $newic = array();
            foreach (array('count', 'value', 'pdiscount', 'vdiscount') as $field) {
                $newic[$field] = $refic[$field] + $ic[$field];
            }
            $e_values[$docid][$itemid] = $newic;
            $newic['docid'] = $docid;
            $newic['itemid'] = $itemid;
            $this->Execute(
                'UPDATE invoicecontents
                SET count = ?, value = ?, pdiscount = ?, vdiscount = ?
                WHERE docid = ? AND itemid = ?',
                array_values($newic)
            );
        }
    }
}
