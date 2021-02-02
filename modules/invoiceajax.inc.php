<?php

if (isset($_GET['updatedocumentflags'])) {
    $documentflags = array(
        'splitpayment' => $LMS->isSplitPaymentSuggested(
            $_GET['customerid'],
            $_GET['cdate'],
            $_GET['value']
        ),
        'telecomservice' => $LMS->isTelecomServiceSuggested(
            $_GET['customerid']
        ),
    );
    header('Content-Type: application/json');
    die(json_encode($documentflags));
}
