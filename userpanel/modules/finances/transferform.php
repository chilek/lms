<?php

/*
 *  LMS version 1.11-git
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

global $SESSION;

$cid = $SESSION->id;
$Before = array ("%CID%", "%LongCID%");
$After = array ($cid, sprintf('%04d', $cid));
$payment_title = str_replace($Before, $After, ConfigHelper::getConfig('finances.pay_title', 'Abonament - ID:%CID% %LongCID%'));

$transferform = new LMSTcpdfTransferForm('Transfer form', $pagesize = 'A4', $orientation = 'portrait');
$tranferform_common_data = $transferform->GetCommonData(array('customerid' => $cid));
$payment_value = trim($tranferform_common_data['customerinfo']['balance'] * -1);

$tranferform_custom_data = array(
    'title' => $payment_title,
    'value' => $payment_value,
    'paytype' => 8, // only to hide deadline
    'barcode' => $payment_title,
);

$tranferform_custom_data['value'] = $payment_value;
$tranferform_data = $transferform->SetCustomData($tranferform_common_data, $tranferform_custom_data);
$transferform->Draw($tranferform_data, 0, 25);
$transferform->WriteToBrowser();
