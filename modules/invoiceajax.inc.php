<?php

if (!empty($_GET['action'])) {
    switch ($_GET['action']) {
        case 'updateheader':
            if (empty($_GET['flags']) || empty($_GET['customerid'])) {
                header('Content-Type: application/json');
                die('[]');
            }

            $flags = $_GET['flags'];

            if (empty($flags['documentflags'])) {
                $result = [];
            } else {
                $result = [
                    'splitpayment' => $LMS->isSplitPaymentSuggested(
                        $_GET['customerid'],
                        $_GET['cdate'],
                        $_GET['value']
                    ),
                    'telecomservice' => $LMS->isTelecomServiceSuggested(
                        $_GET['customerid']
                    ),
                ];
            }

            if (isset($flags['paytype']) && $flags['paytype'] === 'true') {
                $defaultPayType = $LMS->getDefaultPayType($_GET['customerid']);

                if (empty($defaultPayType) || !isset($PAYTYPES[$defaultPayType])) {
                    $result['paytype'] = 0;
                } else {
                    $result['paytype'] = $defaultPayType;
                    $result['paytypename'] = trans($PAYTYPES[$defaultPayType]['label']);
                }
            }

            header('Content-Type: application/json');
            die(json_encode($result));
            break;
    }
}
