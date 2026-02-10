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

if (!isset($_GET['id'])) {
    die;
}

$id = intval($_GET['id']);
if (empty($id)) {
    die;
}

$invoice = $DB->GetRow(
    'SELECT
        i.*,
        d.name AS division_name,
        d.shortname AS division_shortname,
        d.label AS division_label
    FROM ksefinvoices i
    JOIN divisions d ON d.id = i.division_id
    WHERE i.id = ?',
    [
        $id,
    ]
);
if (empty($invoice)) {
    die;
}

if (!empty($_GET['qr2pay'])) {
    $qr2pay = implode(
        '|',
        [
            $invoice['seller_ten'],
            'PL',
            $invoice['bank_account'],
            str_pad($invoice['gross_amount'] * 100, 6, 0, STR_PAD_LEFT),
            mb_substr($invoice['division_shortname'], 0, 20),
            $invoice['invoice_number'],
            '',
            '',
            '',
        ]
    );

    $SMARTY->assign('qr2pay', $qr2pay);

    $SMARTY->display('ksef/ksefpurchaseinvoiceinfo.html');

    $SESSION->close();

    die;
}

$now = time();
$invoice['pay_type_name'] = KSeF::payTypeName($invoice['pay_type']);
$invoice['expired'] = strtotime('+1 day', $invoice['pay_date']) < $now;

$invoice['items'] = $DB->GetAll(
    'SELECT
        ii.*
    FROM ksefinvoiceitems ii
    WHERE ii.ksef_invoice_id = ?',
    [
        $id,
    ]
);

$summary = [];
foreach ($invoice['items'] as &$item) {
    $taxRateLabel = KSeF::ksefTaxLabel([
        'tax_rate' => $item['tax_rate'],
        'taxed' => $item['taxed'],
        'reverse_charge' => $item['reverse_charge'],
        'eu' => $item['eu'],
        'export' => $item['export'],
    ]);
    $item['tax_rate_label'] = $taxRateLabel;

    if (empty($item['net_flag'])) {
        $item['gross_price'] = $item['price'];
        $item['gross_value'] = $item['value'];
        $item['net_value'] = round((100 * $item['gross_value']) / (100 + $item['tax_rate']), 2);
    } else {
        $item['net_price'] = $item['price'];
        $item['net_value'] = $item['value'];
        $item['gross_value'] = round(($item['net_value'] * (100 + $item['tax_rate'])) / 100, 2);
    }
    $item['tax'] = round($item['gross_value'] - $item['net_value'], 2);

    if (!isset($summary[$taxRateLabel])) {
        $summary[$taxRateLabel] = [
            'tax_rate' => $item['tax_rate'],
            'net' => 0,
            'gross' => 0,
        ];
    }

    if (empty($item['net_flag'])) {
        $summary[$taxRateLabel]['gross'] += $item['gross_value'];
    } else {
        $summary[$taxRateLabel]['net'] += $item['net_value'];
    }
}
unset($item);

foreach ($summary as &$item) {
    if (empty($item['net'])) {
        $item['net'] = round((100 * $item['gross']) / (100 + $item['tax_rate']), 2);
    } else {
        $item['gross'] = round(($item['net'] * (100 + $item['tax_rate'])) / 100, 2);
    }
    $item['tax'] = round($item['gross'] - $item['net'], 2);
}
unset($item);

$invoice['summary'] = $summary;

$SMARTY->assign('invoice', $invoice);

$SMARTY->display('ksef/ksefpurchaseinvoiceinfo.html');
