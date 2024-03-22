<?php

error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);

$uid = isset($_GET['id']) ? intval($_GET['id']) : null;
$agentnr = isset($_GET['agentnr']) ? intval($_GET['agentnr']) : null;

require_once('..' . DIRECTORY_SEPARATOR . 'initLMS.php');
require_once('lib' . DIRECTORY_SEPARATOR . 'definitions.php');

if (!empty($_GET['phone'])) {
    $phone = intval($_GET['phone']);
    $ticket['phone'] = $phone;
} else {
    $phone = null;
}

$ticket['phonetype'] = 'on';

str_replace(
    array('%uid', '%customerphone', '%agentnr'),
    array($uid, $phone, $agentnr),
    $newticket_subject
);

$basedir=(__DIR__ . DIRECTORY_SEPARATOR . 'templates_c');
$wwwuser = posix_getuid();
$wwwgroup = posix_getgid();

if (!is_dir($basedir)) {
    die("Please create Smarty compiled templates directory using shell command: mkdir " . $basedir);
} elseif (!is_readable($basedir) || !is_writable($basedir)) {
    die("Please set correct permissions to Smarty compiled templates directory using shell commands: chmod 755 " . $basedir . "; chown " . $wwwuser . ":" . $wwwgroup . " " . $basedir);
}

$SMARTY = new LMSSmarty;
$SMARTY->AddTemplateDir('templates');
$SMARTY->setCompileDir('templates_c');

if ($ip != $callcenterip) {
    $check = false;
    // check if in range
    foreach ($networks as $network) {
        if (ip_in_range($ip, $network)==true) {
            $check = true;
        }
    }

    if ($check === false) {
        die();
    }
}

// get all customers with valid phone number
if (!empty($phone)) {
    $result = $DB->GetAll(
        'SELECT c.id, address, city, deleted,
        ' . $DB->Concat('UPPER(lastname)', "' '", 'c.name') . ' AS username,
        REPLACE(REPLACE(cc.contact, \'-\', \'\'), \' \', \'\') AS phone
        FROM customerview c
            LEFT JOIN customercontacts cc ON cc.customerid = c.id
        WHERE
            REPLACE(REPLACE(cc.contact, \'-\', \'\'), \' \', \'\') ?LIKE? \'?\'
            AND c.deleted = 0
            AND (cc.type & ' . CONTACT_MOBILE . ' > 0 OR cc.type & ' . CONTACT_LANDLINE . ' > 0)
        ORDER BY username, cc.contact, address, deleted',
        array($phone)
    );
    // prepare result to put in js
    if (!empty($result)) {
        $js_result  = json_encode($result, JSON_UNESCAPED_UNICODE);
    } else {
        $js_result  = null;
    }
}

if (!empty($_POST)) {
    $ticket = $_POST;

    if (empty($ticket['phone'])) {
        $ticket['phone'] = empty($ticket['contactphone']) ? null : $ticket['contactphone'];
    }

    // simple form validation
    if (isset($ticket['body']) && $ticket['body'] == '' && $ticket['queue'] == 1) {
        $error['body'] = 'Podaj treść zgłoszenia!';
    }

    if (isset($ticket['name']) && $ticket['name'] == '') {
        $error['name'] = 'Podaj imię i nazwisko/nazwę klienta!';
    }

    if (isset($ticket['address']) && $ticket['address'] == '') {
        $error['address'] = 'Podaj adres instalacji z którą jest problem!';
    }

    if (isset($ticket['phone']) && $ticket['phone'] == '') {
        $error['phone'] = 'Podaj telefon do kontaktu ze zgłaszającym usterkę!';
    }

    // continue if no error found
    if (!isset($error) || empty($error)) {
        if (!$agentnr || !isset($agents[$agentnr])) {
            $agent = 'Brak informacji';
        } else {
            $agent = $agents[$agentnr];
        }

        //prepare body
        $ticket['mailfrom'] = '';
        $ticket['owner'] = null;
        // if valid user found in db
        if (isset($ticket['othercustomer']) || empty($result)) {
            $ticket['customerid'] = null;
            if (isset($ticket['odblokowanie_komunikatu']) && $ticket['odblokowanie_komunikatu'] == 'tak') {
                $ticket['requestor'] = $ticket['name'] . ', ' . $ticket['address'];
                $ticket['body'] = 'Prośba o odblokowanie internetu.' . PHP_EOL;
            } elseif (isset($ticket['kontakt']) && $ticket['kontakt'] == 'tak') {
                $ticket['requestor'] = $ticket['name'].', '.$ticket['address'];
                $ticket['body'] = 'Prośba o kontakt z ofertą handlową.' . PHP_EOL;
            } else {
                $ticket['requestor'] = $ticket['name'].', '.$ticket['address'];
            }
        } else {
            $ticket['customerid'] = $result[$ticket['customer']]['id'];
            $ticket['requestor'] = '';
            if (isset($ticket['odblokowanie_komunikatu']) && $ticket['odblokowanie_komunikatu'] == 'tak') {
                $ticket['requestor'] = $ticket['name'] . ', ' . $ticket['address'];
                $ticket['body'] = 'Prośba o odblokowanie internetu.' . PHP_EOL;
            } elseif (isset($ticket['kontakt']) && $ticket['kontakt'] == 'tak') {
                $ticket['requestor'] = $ticket['name'] . ', ' . $ticket['address'];
                $ticket['body'] = 'Prośba o kontakt z ofertą handlową.' . PHP_EOL;
            } else {
                $ticket['requestor'] = $ticket['name'] . ', ' . $ticket['address'];
            }
        }
        $ticket['body'] .= PHP_EOL . PHP_EOL
            . (empty($ticket['address']) ? null : 'Adres instalacji: ' . $ticket['address'] . PHP_EOL)
            . 'Agent: ' . $agent . PHP_EOL
            . (empty($ticket['contactphone']) ? null : 'Numer kontaktowy: ' . $ticket['contactphone'] . PHP_EOL)
            . (empty($phone) ? null : 'Numer dzwoniącego: ' . $phone . PHP_EOL);
        $ticket['subject'] = $newticket_subject;
        $queue = $CUSTOMER_ISSUES[$ticket['queue']]['queueid'];

        $firstservice = empty($ticket['service']) ?
            null : array_shift(array_slice($ticket['service'], 0, 1));

        //insert ticket
        $DB->Execute(
            'INSERT INTO rttickets (queueid, customerid, requestor, subject,
                state, owner, createtime, cause, source, creatorid, type, service)
                VALUES (?, ?, ?, ?, ?, ?, ?NOW?, ?, ?, ?, ?, ?)',
            array(
                $queue,
                $ticket['customerid'],
                //matched client by phone cleans requestor field - there is no use to duplicate this in requestor field
                (empty($ticket['customerid']) ? $ticket['requestor'] : null),
                $ticket['subject'],
                RT_NEW,
                $ticket['owner'],
                RT_CAUSE_OTHER,
                RT_SOURCE_CALLCENTER,
                $user_id,
                $CUSTOMER_ISSUES[$ticket['queue']]['ticket_type'],
                $firstservice,
            )
        );
        $id = $DB->GetLastInsertID('rttickets');

        $DB->Execute(
            'INSERT INTO rtmessages (ticketid, customerid, createtime,
                subject, body, mailfrom)
                VALUES (?, ?, ?NOW?, ?, ?, ?)',
            array(
                $id,
                $ticket['customerid'],
                $ticket['subject'],
                preg_replace("/\r/", "", $ticket['body']),
                $ticket['mailfrom'],
            )
        );

        $ticket['service'] = empty($ticket['service']) ? array($default_category) : $ticket['service'];

        foreach ($ticket['service'] as $idx => $t) {
            if (isset($CUSTOMER_VISIBLE_SERVICETYPES[$idx]['categoryid'])) {
                $DB->Execute(
                    'INSERT INTO rtticketcategories (ticketid, categoryid) VALUES (?, ?)',
                    array($id, $t)
                );
            }
        }

        if (ConfigHelper::checkConfig(
            'rt.new_ticket_notify',
            ConfigHelper::checkConfig('phpui.newticket_notify')
        )) {
            $headers['Subject'] = sprintf("[RT#%06d] %s", $id, $ticket['subject']);
            $sms_body = $headers['Subject']."\n".$ticket['body'];

            if (ConfigHelper::checkConfig(
                'rt.notification_customerinfo',
                ConfigHelper::checkConfig('phpui.helpdesk_customerinfo')
            )) {
                if ($ticket['customerid']) {
                    $info = $DB->GetRow(
                        'SELECT id, pin, '.$DB->Concat('UPPER(lastname)', "' '", 'name').' AS customername,
                            address, zip, city
                        FROM customeraddressview
                        WHERE id = ?',
                        array($ticket['customerid'])
                    );

                    $info['contacts'] = $DB->GetAll(
                        'SELECT contact, name, type
                        FROM customercontacts
                        WHERE customerid = ?',
                        array($ticket['customerid'])
                    );

                    $phones = array();
                    if (!empty($info['contacts'])) {
                        foreach ($info['contacts'] as $contact) {
                            $target = $contact['contact'] . (strlen($contact['name']) ? ' (' . $contact['name'] . ')' : '');
                            if ($contact['type'] & CONTACT_MOBILE) {
                                $phones[] = $target;
                            }
                        }
                    }

                    $sms_body .= "\n";
                    $sms_body .= trans('Customer:').' '.$info['customername'];
                    $sms_body .= ' '.sprintf('(%04d)', $ticket['customerid']).'. ';
                    $sms_body .= $info['address'].', '.$info['zip'].' '.$info['city'];
                    if (!empty($phones)) {
                        $sms_body .= '. ' . trans('Phone:') . ' ' . preg_replace('/([0-9])[\s-]+([0-9])/', '\1\2', implode(',', $phones));
                    }
                } elseif (!empty($requestor)) {
                    $sms_body .= "\n";
                    $sms_body .= trans('Customer:') . ' ' . $requestor;
                }
            }

            // send sms
            $service = ConfigHelper::getConfig('sms.service');
            if (!empty($service) && ($recipients = $DB->GetCol(
                'SELECT DISTINCT u.phone
                FROM users u
                JOIN rtrights r ON r.userid = u.id
                WHERE r.queueid = ?
                    AND u.phone <> \'\'
                    AND (r.rights & ?) > 0
                    AND u.deleted = 0',
                array(
                    $queue,
                    RT_RIGHT_SMS_NOTICE,
                )
            ))) {
                foreach ($recipients as $phone) {
                    $LMS->SendSMS($phone, $sms_body);
                }
            }
        }

        if ($id) {
            $msg = 'Zgłoszenie o numerze ' . $id . ' zostało dodane.';
            $ticket = array();
        } else {
            $msg = 'Wystąpił błąd. Nie dodano zgłoszenia!';
            $insert_error = true;
        }
        print_r($msg);
        die();
    }
}

$SMARTY->assign(array(
    'welcomeMsg' => $welcomeMsg,
    'warning' => $warning,
    '_CUSTOMER_ISSUES' => $CUSTOMER_ISSUES,
    '_CUSTOMER_VISIBLE_SERVICETYPES' => $CUSTOMER_VISIBLE_SERVICETYPES,
    'result'    => isset($result) ? $result : null,
    'js_result' => isset($js_result) ? $js_result : null,
    'information' => $information,
    'ticket'    => $ticket,
    'phone' => $phone,
    'error' => !empty($error) ? $error : null,
));

$SMARTY->display('index.html');
