<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2020 LMS Developers
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

$cid = $_GET['id'];
$type = $_GET['type'];
$value = $_GET['value'];
$Before = array ("%CID%", "%LongCID%");
$After = array ($cid, sprintf('%04d', $cid));
$payment_title = str_replace($Before, $After, ConfigHelper::getConfig('finances.pay_title', 'Abonament - ID:%CID% %LongCID%'));

$transferform = new LMSTcpdfTransferForm('Transfer form', $pagesize = 'A4', $orientation = 'portrait');
$tranferform_common_data = $transferform->GetCommonData(array('customerid' => $cid));

$tranferform_custom_data = array(
    'title' => $payment_title,
    'paytype' => 8, // only to hide deadline
    'barcode' => $payment_title,
);

if ($type == LMSTcpdfTransferForm::VALUE_ASSIGNMENTS) {
    //get assignments grouped by currency
    $currency_assignments = $LMS->GetCustomerAssignmentValue($cid);

    $form_count = 0;
    $perpage_form_count = 1;
    $form_translateY = 25;
    $currency_assignments_count = count($currency_assignments);
    foreach ($currency_assignments as $cakey => $currency_assignment) {
        $tranferform_custom_data['value'] = trim($currency_assignment['sum']);
        $tranferform_custom_data['currency'] = $cakey;
        $tranferform_data = $transferform->SetCustomData($tranferform_common_data, $tranferform_custom_data);

        if ($perpage_form_count == 1) {
            $form_translateY = 25;
        } elseif ($perpage_form_count == 2) {
            $form_translateY = 160;
        }

        $transferform->Draw($tranferform_data, 0, $form_translateY);

        $perpage_form_count++;
        $form_count++;
        if ($form_count < $currency_assignments_count && $perpage_form_count == 3) {
            $perpage_form_count = 1;
            $transferform->NewPage();
        }
    }
} else {
    switch ($type) {
        case LMSTcpdfTransferForm::VALUE_BALANCE:
            $payment_value = trim($tranferform_common_data['customerinfo']['balance'] * -1);
            break;
        case LMSTcpdfTransferForm::VALUE_CUSTOM:
            $payment_value = isset($value) ? $value : 0;
            break;
        default:
            $payment_value = 0;
    }

    $tranferform_custom_data['value'] = $payment_value;
    $tranferform_data = $transferform->SetCustomData($tranferform_common_data, $tranferform_custom_data);
    $transferform->Draw($tranferform_data, 0, 25);
}

$transferform->WriteToBrowser();
