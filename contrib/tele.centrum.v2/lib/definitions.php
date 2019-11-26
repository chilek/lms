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
$queues         = explode(',', ConfigHelper::getConfig('callcenter.queues'));
$categories     = explode(',', ConfigHelper::getConfig('callcenter.categories'));
$warning        = ConfigHelper::getConfig('callcenter.warning');
$information    = ConfigHelper::getConfig('callcenter.information');
$callcenterip   = ConfigHelper::getConfig('callcenter.callcenterip');
$networks       = explode(",", ConfigHelper::getConfig('callcenter.networks'));

$time = localtime(time(), true)['tm_hour'];
empty($agents[$agentnr]) ? $ag = '...' : $ag = $agents[$agentnr];
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
