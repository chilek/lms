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

$invoices = $DB->GetAll(
    'SELECT
        i.*,
        d.name AS division_name,
        d.shortname AS division_shortname,
        d.label AS division_label
    FROM ksefinvoices i
    JOIN divisions d ON d.id = i.division_id
    WHERE i.permanent_storage_date BETWEEN ? AND ?',
    [
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
}
unset($invoice);

$divisions = $LMS->GetDivisions();

$SMARTY->assign('divisions', $divisions);
$SMARTY->assign('start_date', $startDate);
$SMARTY->assign('end_date', $endDate);
//$SMARTY->assign('sort_order', 'issue-date asc');
$SMARTY->assign('invoices', $invoices);

$SMARTY->display('ksef/ksefpurchaseinvoices.html');
