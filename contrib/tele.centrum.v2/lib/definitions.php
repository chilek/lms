<?php

// callcenter agents
$agents = array(
    'Agent/2001'    => '(Imię i Nazwisko)',
    'Agent/2003'    => '(Imię i Nazwisko)',
    'Agent/2004'    => '(Imię i Nazwisko)',
    'Agent/2005'    => '(Imię i Nazwisko)',
    'Agent/2006'    => '(Imię i Nazwisko)',
    'Agent/2007'    => '(Imię i Nazwisko)',
    'Agent/2008'    => '(Imię i Nazwisko)',
    'Agent/2009'    => '(Imię i Nazwisko)',
    'Agent/2010'    => '(Imię i Nazwisko)',
    'Agent/2011'    => '(Imię i Nazwisko)',
    'Agent/2012'    => '(Imię i Nazwisko)',
    'Agent/2013'    => '(Imię i Nazwisko)',
    'Agent/2014'    => '(Imię i Nazwisko)',
    'Agent/2015'    => '(Imię i Nazwisko)',
    'Agent/2016'    => '(Imię i Nazwisko)'
);

$user_id        = ConfigHelper::getConfig('callcenter.queueuser');
$default_category = ConfigHelper::getConfig('callcenter.default_category');
$default_queue = ConfigHelper::getConfig('callcenter.default_queue', 1);
$warning        = ConfigHelper::getConfig('callcenter.warning');
$information    = ConfigHelper::getConfig('callcenter.information');
$callcenterip   = ConfigHelper::getConfig('callcenter.callcenterip');
$networks       = explode(",", ConfigHelper::getConfig('callcenter.networks'));

$service_internet_category = ConfigHelper::getConfig('callcenter.service_internet_category', $default_category);
$service_phone_category = ConfigHelper::getConfig('callcenter.service_phone_category', $default_category);
$service_tv_category = ConfigHelper::getConfig('callcenter.service_tv_category', $default_category);

$fault_issues_queue = ConfigHelper::getConfig('callcenter.fault_issues_queue', $default_queue);
$offer_issues_queue = ConfigHelper::getConfig('callcenter.offer_issues_queue', $default_queue);
$payment_issues_queue = ConfigHelper::getConfig('callcenter.payment_issues_queue', $default_queue);

$newticket_subject = ConfigHelper::getConfig(
    'callcenter.newticket_subject',
    'Zgłoszenie telefoniczne z E-Południe Call Center nr [#' . $uid . ']'
);

$time = localtime(time(), true)['tm_hour'];

$ag = empty($agents[$agentnr]) ? '...' : $agents[$agentnr];
if ($time >= 19 or $time < 6) {
    $welcomeMsg = "Dobry wieczór. Nazywam się $ag, w czym mogę pomóc?";
} else {
    $welcomeMsg = "Dzień dobry. Nazywam się $ag, w czym mogę pomóc?";
}

$ip = $_SERVER['REMOTE_ADDR'];


// function to check is ip in valid network
function ip_in_range($ip, $range)
{
    if (strpos($range, '/') == false) {
        $range .= '/32';
    }
    
    list( $range, $netmask ) = explode('/', $range, 2);
    $range_decimal = ip2long($range);
    $ip_decimal = ip2long($ip);
    $wildcard_decimal = pow(2, ( 32 - $netmask )) - 1;
    $netmask_decimal = ~ $wildcard_decimal;

    return ( ( $ip_decimal & $netmask_decimal ) == ( $range_decimal & $netmask_decimal ) );
}

define('CUSTOMER_ISSUE_FAULT', 1);
define('CUSTOMER_ISSUE_OFFER', 2);
define('CUSTOMER_ISSUE_PAYMENT', 3);

$CUSTOMER_ISSUES = array(
    CUSTOMER_ISSUE_FAULT => array(
        'label' => 'Zgłoszenie awarii',
        'queueid' => $fault_issues_queue,
        'ticket_type' => RT_TYPE_FAULT,
    ),
    CUSTOMER_ISSUE_OFFER => array(
        'label' => 'Informacja handlowa',
        'queueid' => $offer_issues_queue,
        'ticket_type' => RT_TYPE_OFFER,
    ),
    CUSTOMER_ISSUE_PAYMENT => array(
        'label' => 'Sprawy finansowe',
        'queueid' => $payment_issues_queue,
        'ticket_type' => RT_TYPE_PAYMENT,
    ),
);

$CUSTOMER_VISIBLE_SERVICETYPES = array(
    SERVICE_INTERNET  => array(
        'label' => 'Internet',
        'categoryid' => $service_internet_category,
        'value' => SERVICE_INTERNET,
    ),
    SERVICE_PHONE  => array(
        'label' => 'Telefon',
        'categoryid' => $service_phone_category,
        'value' => SERVICE_PHONE,
    ),
    SERVICE_TV  => array(
        'label' => 'Telewizja',
        'categoryid' => $service_tv_category,
        'value' => SERVICE_TV,
    ),
    SERVICE_OTHER => array(
        'label' => 'Inna',
        'categoryid' => $default_category,
        'value' => SERVICE_OTHER,
    ),
);
