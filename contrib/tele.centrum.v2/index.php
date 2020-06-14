<?php

error_reporting(E_ALL &~ E_NOTICE &~ E_DEPRECATED);

require_once('..' . DIRECTORY_SEPARATOR . 'initLMS.php');
require_once('lib' . DIRECTORY_SEPARATOR . 'definitions.php');

$uid        = $_GET['id'];
$phone      = $_GET['phone'];
$agentnr    = $_GET['agentnr'];
$ticket['phonetype'] = 'on';

$basedir=(__DIR__ . DIRECTORY_SEPARATOR . 'templates_c');
$wwwuser=posix_getuid();
$wwwgroup=posix_getgid();

if (!is_dir($basedir)) {
    die("Please create Smarty compiled templates directory using shell command: mkdir " . $basedir);
} elseif (!is_readable($basedir) || !is_writable($basedir)) {
    die("Please set valid permissions to Smarty compiled templates directory using shell commands: chmod 755 " . $basedir . "; chown " . $wwwuser. ":" . $wwwgroup . " " . $basedir);
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
    $result     = $DB->GetAll("SELECT c.id, cc.contact AS phone, address, city, deleted,
                ".$DB->Concat('UPPER(lastname)', "' '", 'c.name')." AS username
                FROM customerview c
                LEFT JOIN customercontacts cc ON cc.customerid = c.id 
                WHERE REPLACE(REPLACE(cc.contact, '-', ''), ' ', '') ?LIKE? ? AND DELETED != 1
                ORDER by deleted, username, cc.contact, address", array($phone));
    // prepare result to put in js
    $js_result  = json_encode($result, JSON_UNESCAPED_UNICODE);
}

if (!empty($_POST)) {
    $ticket = $_POST;
        empty($ticket['phone']) ? $ticket['phone'] = $ticket['contactphone'] : '';
    // simple form validation
    if ($ticket['body'] == '' and $ticket['queue']==1) {
        $error['body'] = 'Podaj treść zgłoszenia!';
    }

    if ($ticket['name'] == '') {
        $error['name'] = 'Podaj imię i nazwisko/nazwę klienta!';
    }

    if ($ticket['address'] == '') {
        $error['address'] = 'Podaj adres instalacji z którą jest problem!';
    }

    if ($ticket['phone'] == '') {
        $error['phone'] = 'Podaj telefon do kontaktu ze zgłaszającym usterkę!';
    }

    // continue if no error found
    if (!$error) {
        if (!$agentnr or !$agents[$agentnr]) {
            $agent = 'Brak informacji';
        } else {
            $agent = $agents[$agentnr];
        }

        //prepare body
        $ticket['contactphone'] = $ticket['phone'];
        $ticket['mailfrom'] = '';
        $ticket['owner'] = 0;
        // if valid user found in db
        if (isset($ticket['othercustomer']) or !$result) {
            $ticket['customerid'] = 0;
            if ($ticket['odblokowanie_komunikatu'] == 'tak') {
                $ticket['requestor']  = $ticket['name'].', '.$ticket['address'];
                $ticket['body'] = 'Prośba o odblokowanie internetu.'.PHP_EOL.'Agent: '.$agent .PHP_EOL.'Numer kontaktowy: ' . $ticket['contactphone'];
            } elseif ($ticket['kontakt'] == 'tak') {
                $ticket['requestor']  = $ticket['name'].', '.$ticket['address'];
                $ticket['body'] = 'Prośba o kontakt z ofertą handlową.'. PHP_EOL .'Agent: '.$agent .PHP_EOL.'Numer kontaktowy: ' . $ticket['contactphone'];
            } else {
                $ticket['requestor']  = $ticket['name'].', '.$ticket['address'];
                $ticket['body'] .=  PHP_EOL .'Agent: '.$agent .PHP_EOL . 'Numer kontaktowy: ' . $ticket['contactphone'];
            }
        } else {
            $ticket['customerid'] = $result[$ticket['customer']]['id'];
            $ticket['requestor'] = '';
            if ($ticket['odblokowanie_komunikatu'] == 'tak') {
                $ticket['requestor']  = $ticket['name'].', '.$ticket['address'];
                $ticket['body'] = 'Prośba o odblokowanie internetu.'.PHP_EOL.'Agent: '.$agent .PHP_EOL.'Numer kontaktowy: ' . $ticket['contactphone'];
            } elseif ($ticket['kontakt'] == 'tak') {
                $ticket['requestor']  = $ticket['name'].', '.$ticket['address'];
                $ticket['body'] = 'Prośba o kontakt z ofertą handlową.'. PHP_EOL .'Agent: '.$agent .PHP_EOL.'Numer kontaktowy: ' . $ticket['contactphone'];
            } else {
                $ticket['requestor']  = $ticket['name'].', '.$ticket['address'];
                $ticket['body'] .=  PHP_EOL .'Agent: '.$agent .PHP_EOL . 'Numer kontaktowy: ' . $ticket['contactphone'];
            }
        }
        $ticket['subject'] = 'Zgłoszenie telefoniczne z E-Południe Call Center nr ['.$uid.']';
        // set real quque id
        if ($ticket['queue'] == 1) {
            $ticket['queue'] = $queues[0];
        } elseif ($ticket['queue'] == 2) {
            $ticket['queue'] = $queues[1];
        } elseif ($ticket['queue'] == 3) {
            $ticket['queue'] = $queues[2];
        }

        //insert ticket
        $DB->Execute('INSERT INTO rttickets (queueid, customerid, requestor, subject,
		state, owner, createtime, cause, creatorid)
		VALUES (?, ?, ?, ?, 0, ?, ?NOW?, 0, ?)', array($ticket['queue'],
        $ticket['customerid'],
        $ticket['requestor'],
        $ticket['subject'],
        $ticket['owner'],
        $user_id
        ));
        $id = $DB->GetLastInsertID('rttickets');

        $DB->Execute('INSERT INTO rtmessages (ticketid, customerid, createtime,
		    subject, body, mailfrom)
		    VALUES (?, ?, ?NOW?, ?, ?, ?)', array($id,
            $ticket['customerid'],
            $ticket['subject'],
            preg_replace("/\r/", "", $ticket['body']),
            $ticket['mailfrom']));

        if (isset($ticket['internet'])) {
            $DB->Execute('INSERT INTO rtticketcategories (ticketid, categoryid) VALUES (?, ?)', array($id, $categories[0]));
        }
        if (isset($ticket['tv'])) {
            $DB->Execute('INSERT INTO rtticketcategories (ticketid, categoryid) VALUES (?, ?)', array($id, $categories[1]));
        }
        if (isset($ticket['telefon'])) {
            $DB->Execute('INSERT INTO rtticketcategories (ticketid, categoryid) VALUES (?, ?)', array($id, $categories[2]));
        }
        if (is_null($ticket['internet']) && is_null($ticket['tv']) && is_null($ticket['telefon'])) {
            $DB->Execute('INSERT INTO rtticketcategories (ticketid, categoryid) VALUES (?, ?)', array($id, $categories[3]));
        }

        $queue = $ticket['queue'];

        if (ConfigHelper::checkConfig('phpui.newticket_notify')) {
            $headers['Subject'] = sprintf("[RT#%06d] %s", $id, $ticket['subject']);
            $sms_body = $headers['Subject']."\n".$ticket['body'];

            if (ConfigHelper::checkConfig('phpui.helpdesk_customerinfo')) {
                if ($ticket['customerid']) {
                    $info = $DB->GetRow('SELECT id, pin, '.$DB->Concat('UPPER(lastname)', "' '", 'name').' AS customername,
							address, zip, city FROM customeraddressview
							WHERE id = ?', array($ticket['customerid']));

                    $info['contacts'] = $DB->GetAll('SELECT contact, name, type FROM customercontacts
						WHERE customerid = ?', array($ticket['customerid']));

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
                    $sms_body .= trans('Customer:').' '.$requestor;
                }
            }

            // send sms
            $service = ConfigHelper::getConfig('sms.service');
            if (!empty($service) && ($recipients = $DB->GetCol(
                'SELECT DISTINCT phone
				FROM users, rtrights
					WHERE users.id = userid AND queueid = ? AND phone != \'\'
						AND (rtrights.rights & 8) = 8 AND deleted = 0
						',
                array($queue)
            ))) {
                foreach ($recipients as $phone) {
                    $LMS->SendSMS($phone, $sms_body);
                }
            }
        }

        if ($id) {
            $msg    = 'Zgłoszenie zostoło dodane.';
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
    'result'    => $result,
    'js_result' => $js_result,
    'information' => $information,
    'ticket'    => $ticket,
    'phone' => $phone,
    'error' => $error
));

$SMARTY->display('index.html');
