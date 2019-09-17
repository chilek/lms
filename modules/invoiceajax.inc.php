<?php

if (isset($_GET['splitpaymentcheck'])) {
    $splitpayment = $LMS->isSplitPaymentSuggested(
        $_GET['customerid'],
        $_GET['cdate'],
        $_GET['value']
    );
    header('Content-Type: application/json');
    die(json_encode(compact('splitpayment')));
}
