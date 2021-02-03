<?php
# MySQL
$db_ip='localhost';
$db_name='lms2';
$db_user='lms2';
$db_pass='1234';

$start_id_netdevices='225';                                         // Id urządzenia z LMS-a, od którego będzię budowana mapa (węzeł startowy) np router brzegowy
$pnp4nagios=true;                                                   // Podaje true jeśli korzystatmy z OMD, a jesli tylko z samego nagiosa FAlSE
$link_to_LMS='https://lms.vps.sl-net.pl';                           // Adress http do LMS-a
$network='';                                                        // Adres sieci - aktualnie pole nie używane
$nagios_path_conf='generated';                                      // Scieżka gdzie będą tworzone pliki konfiguracyjne dla nagios np /etc/nagios3/conf.d/lms
$nagios_hosts_file='hosts_lms_nagios2.cfg';                         // Nazwa pliku z hostami
$mapimages = array(                                                 // Przypisanie obrazka (wyśwetlany na mapie) do urządzenia na podstawie pola model z LMS
                    'RB433AH'=>'routerboard.png',
                    'RB750'=>'routerboard.png',
                    'RB450G'=>'routerboard.png',
                    'RB433'=>'routerboard.png',
                    'RB333'=>'routerboard.png',
                    'RB411U'=>'routerboard.png',
                    'RB411AH'=>'routerboard.png',
                    'RB192'=>'routerboard.png',
                    'Alix 3D2'=>'routerboard.png',
                    'DGS-1210-24'=>'DGS-1210-24.png',
                    'ProLiant ML110'=>'HP_ML110.png',
                    'ProLiant ML150 G6'=>'HP_ML150G6.png',
                    'TL-SG1008P'=>'TL-SG1008P.png',
                    'V5724G'=>'V5724G.png',
                    'H635G'=>'TL-SG1008P.png',
                    'NanoBridge M5'=>'nanobridge_m5.png',
                    'nanostation m5'=>'nanostation_m5.png',
                    'Rocket M5'=>'rocket_m5.png'
);
$logoimages = array(                                                // Przypisanie obrazka (wyśwetlany w podglądzie) do urządzenia na podstawie pola producent z LMS
                    'MIKROTIK'=>'mikrotik_logo.png',
                    'Ubiquiti'=>
                    'ubnt_logo.png'
);
