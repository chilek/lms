<?php

/**
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2026 LMS Developers
 *
 *  Please, see the doc/AUTHORS for more information about authors!
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
 *  $Id$
 */

use \Lms\KSeF\KSeF;

if (isset($_POST['start-date'])) {
    $startDate = strtotime($_POST['start-date']);
    if ($startDate === false) {
        $startDate = null;
    }
} else {
    $startDate = $SESSION->get('ksef-invoices-start-date');
}
if (!isset($startDate)) {
    $startDate = strtotime(date('Y/m/01'));
}
$SESSION->save('ksef-invoices-start-date', $startDate);

if (isset($_POST['end-date'])) {
    $endDate = strtotime($_POST['end-date']);
    if ($endDate === false || $endDate >= $startDate) {
        $endDate = null;
    }
} else {
    $endDate = $SESSION->get('ksef-invoices-end-date');
}
if (!isset($endDate)) {
    $endDate = strtotime('+1 month', $startDate) - 1;
}
$SESSION->save('ksef-invoices-end-date', $endDate);

$allTags = $DB->GetAllByKey(
    'SELECT
        t.id,
        t.name
    FROM ksefinvoicetags t
    ORDER BY t.name',
    'id'
);
if (empty($allTags)) {
    $allTags = [];
}

$invoices = $DB->GetAll(
    'SELECT
        i.*,
        (CASE WHEN EXISTS (SELECT 1 FROM ksefinvoiceitems ii WHERE ii.ksef_invoice_id = i.id) THEN 1 ELSE 0 END) AS itemcount,
        d.name AS division_name,
        d.shortname AS division_shortname,
        d.label AS division_label,
        t.tags
    FROM ksefinvoices i
    JOIN divisions d ON d.id = i.division_id
    LEFT JOIN (
        SELECT
            ta.ksef_invoice_id,
            ' . $DB->GroupConcat('ta.ksef_invoice_tag_id') . ' AS tags
        FROM ksefinvoicetagassignments ta
        JOIN ksefinvoices i ON i.id = ta.ksef_invoice_id
        WHERE i.permanent_storage_date BETWEEN ? AND ?
        GROUP BY ta.ksef_invoice_id
    ) t ON t.ksef_invoice_id = i.id
    WHERE i.permanent_storage_date BETWEEN ? AND ?',
    [
        date('Y/m/d', $startDate),
        date('Y/m/d 23:59:59', $endDate),
        date('Y/m/d', $startDate),
        date('Y/m/d 23:59:59', $endDate),
    ]
);
if (empty($invoices)) {
    $invoices = [];
}
$now = time();
foreach ($invoices as &$invoice) {
    $invoice['pay_type_name'] = KSeF::payTypeName($invoice['pay_type']);
    $invoice['expired'] = strtotime('+1 day', $invoice['pay_date']) < $now;
    $invoice['about_to_expire'] = !$invoice['expired'] && strtotime('-7 days', $invoice['pay_date']) < $now;
    $invoice['invoice_type_name'] = $DOCTYPES[$invoice['invoice_type']] ?? KSeF::docTypeName($invoice['invoice_type']) ?? trans('<!ksef>unknown document type');

    if (!empty($invoice['about_to_expire'])) {
        $invoice['days_to_expire'] = round(($invoice['pay_date'] - $now) / 86400);
    }

    if (empty($invoice['tags'])) {
        $tags = [];
    } else {
        $tags = explode(',', $invoice['tags']);
    }
    $invoice['tags'] = [];
    foreach ($tags as $tag) {
        $invoice['tags'][$tag] = $allTags[$tag]['name'];
    }
}
unset($invoice);

$divisions = $LMS->GetDivisions();

$SMARTY->assign('divisions', $divisions);
$SMARTY->assign('start_date', $startDate);
$SMARTY->assign('end_date', $endDate);
//$SMARTY->assign('sort_order', 'issue-date asc');
$SMARTY->assign('invoices', $invoices);

$SMARTY->display('ksef/ksefpurchaseinvoices.html');
