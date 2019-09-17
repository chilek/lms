<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2017 LMS Developers
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

if (isset($_GET['splitpaymentcheck'])) {
    $splitpayment = $LMS->isSplitPaymentSuggested(
        $_GET['customerid'],
        $_GET['cdate'],
        $_GET['value']
    );
    header('Content-Type: application/json');
    die(json_encode(compact('splitpayment')));
}

function GetNumberPlans($proforma, $invoice, $customerid = null)
{
    global $LMS, $SMARTY;

    $result = new xajaxResponse();

    $DB = LMSDB::getInstance();

    $args = array(
        'doctype' => $proforma ? DOC_INVOICE_PRO : DOC_INVOICE,
        'cdate' => date('Y/m', $invoice['cdate']),
    );
    if (isset($customerid) && !empty($customerid)) {
        $SMARTY->assign('customerid', $customerid);
        $args['customerid'] = $customerid;
        $args['division'] = $DB->GetOne('SELECT divisionid FROM customers WHERE id = ?', array($customerid));
    }
    $SMARTY->assign('numberplanlist', $LMS->GetNumberPlans($args));

    $SMARTY->assign('invoice', $invoice);

    $contents = $SMARTY->fetch('invoice/invoicenumberplans.html');
    $result->assign('invoicenumberplans', 'innerHTML', $contents);

    return $result;
}

$LMS->InitXajax();
$LMS->RegisterXajaxFunction('GetNumberPlans');
$SMARTY->assign('xajax', $LMS->RunXajax());
