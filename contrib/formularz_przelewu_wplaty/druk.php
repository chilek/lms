<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2017 LMS Developers
 *
 *  Please, see the doc/AUTHORS for more information about authors!
 *
 *  $Id$
 */

// REPLACE THIS WITH PATH TO YOU CONFIG FILE

if (is_readable('lms.ini')) {
    $CONFIG_FILE = 'lms.ini';
} elseif (is_readable(DIRECTORY_SEPARATOR . 'etc' . DIRECTORY_SEPARATOR . 'lms' . DIRECTORY_SEPARATOR . 'lms-' . $_SERVER['HTTP_HOST'] . '.ini')) {
    $CONFIG_FILE = DIRECTORY_SEPARATOR . 'etc' . DIRECTORY_SEPARATOR . 'lms' . DIRECTORY_SEPARATOR . 'lms-' . $_SERVER['HTTP_HOST'] . '.ini';
} elseif (is_readable(DIRECTORY_SEPARATOR . 'etc' . DIRECTORY_SEPARATOR . 'lms' . DIRECTORY_SEPARATOR . 'lms.ini')) {
    $CONFIG_FILE = DIRECTORY_SEPARATOR . 'etc' . DIRECTORY_SEPARATOR . 'lms' . DIRECTORY_SEPARATOR . 'lms.ini';
} else {
    die('Unable to read configuration file!');
}

// PLEASE DO NOT MODIFY ANYTHING BELOW THIS LINE UNLESS YOU KNOW
// *EXACTLY* WHAT ARE YOU DOING!!!
// *******************************************************************

header('X-Powered-By: LMS/1.11-git/contrib_formularz_przelewu_wplaty');

define('CONFIG_FILE', $CONFIG_FILE);

// Parse configuration file
$CONFIG = (array) parse_ini_file($CONFIG_FILE, true);

// Check for configuration vars and set default values
$CONFIG['directories']['sys_dir'] = (!isset($CONFIG['directories']['sys_dir']) ? getcwd() : $CONFIG['directories']['sys_dir']);
$CONFIG['directories']['lib_dir'] = (!isset($CONFIG['directories']['lib_dir']) ? $CONFIG['directories']['sys_dir'] . DIRECTORY_SEPARATOR . 'lib' : $CONFIG['directories']['lib_dir']);

define('SYS_DIR', $CONFIG['directories']['sys_dir']);
define('LIB_DIR', $CONFIG['directories']['lib_dir']);

// Load autoloader
$composer_autoload_path = SYS_DIR . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
if (file_exists($composer_autoload_path)) {
    require_once $composer_autoload_path;
} else {
    die("Composer autoload not found. Run 'composer install' command from LMS directory and try again. More informations at https://getcomposer.org/");
}

// Init database

// funkcja to_words()
require_once(LIB_DIR . DIRECTORY_SEPARATOR . 'locale' . DIRECTORY_SEPARATOR . 'pl' . DIRECTORY_SEPARATOR . 'ui.php');

$DB = null;

try {
    $DB = LMSDB::getInstance();
} catch (Exception $ex) {
    trigger_error($ex->getMessage(), E_USER_WARNING);
    // can't working without database
    die("Fatal error: cannot connect to database!" . PHP_EOL);
}

$ISP1_DO = ConfigHelper::getConfig('finances.line_1', 'LINIA1xxxxxxxxxxxxxxxxxxxyz');
$ISP2_DO = ConfigHelper::getConfig('finances.line_2', 'linia2xxxxxxxxxxxxxxxxxxxyz');
$USER_T1 = ConfigHelper::getConfig('finances.pay_title', 'Abonament - ID:%CID% %LongCID%');
$UID = isset($_GET['UID']) ? intval($_GET['UID']) : 0;

$Before = array ("%CID%","%LongCID%");
$After = array ($UID, sprintf('%04d', $UID));

$USER_TY = str_replace($Before, $After, $USER_T1);

//  NRB 26 cyfr, 2 kontrolne, 8 nr banku, 16 nr konta
$KONTO_DO = ConfigHelper::getConfig('finances.account', '98700000000000000000000123');
$CURR = 'PLN';      // oznaczenie waluty
$SHORT_TO_WORDS = 0;    // 1 - krótki format kwoty słownej 'jed dwa trz 15/100'
            // 0 - długi format kwoty słownej 'sto dwadzieścia trzy 15/100 zł'

/************** Koniec konfiguracji ****************/

$KWOTA = trim(isset($_GET['ILE']) ? $_GET['ILE'] : 0);
$USER_OD = trim(strip_tags(isset($_GET['OD']) ? $_GET['OD'] : ''));

$KWOTA_NR = str_replace(',', '.', $KWOTA);  // na wszelki wypadek
$KWOTA_GR = sprintf('%02d', round(($KWOTA_NR - floor($KWOTA_NR))*100));

if ($SHORT_TO_WORDS) {
    $KWOTA_ZL = to_words(floor($KWOTA_NR), 0, '', 1);
    $KWOTA_X = $KWOTA_ZL .' '. $KWOTA_GR. '/100';
} else {
    $KWOTA_ZL = to_words(floor($KWOTA_NR));
    $KWOTA_X = $KWOTA_ZL .' '. $KWOTA_GR. '/100 złotych';
}

?>

<HTML>
<HEAD>

<META http-equiv="Content-Type" content="text/html;charset=utf-8">
</HEAD>
<BODY>

<FORM><P style="font-family: Arial, Helvetica; font-size: 12pt; font-weight: bold;">W zależności od poczty/banku wpisz 1 lub 2 kopie. <input type="button" value="Drukuj" onClick="top.print();"></FORM>

<?php

$SHIFT=394; // drugi druczek przesunięcie o 394

$barcode = new \Com\Tecnick\Barcode\Barcode();
$bobj = $barcode->getBarcodeObj('C128', iconv('UTF-8', 'ASCII//TRANSLIT', $USER_TY), -1, -15, 'black');
$barcode_image = base64_encode($bobj->getPngData());

for ($j=0; $j<2; $j++) { // pętla główna
// teksty na druczku:

     $posx=60+$j*$SHIFT;
     echo('<div style="position: absolute; top: '. $posx .'px; left: 10px"><img src="przelew.png" border=0 alt="wpłata gotówkowa"></div>');
     $posx=63+$j*$SHIFT;
     echo('<span style="position: absolute; top: '. $posx .'px; left: 62px; font-family: Arial, Helvetica;color: #FF0000;font-size: 6pt;">nazwa odbiorcy</span>');
     $posx=96+$j*$SHIFT;
     echo('<span style="position: absolute; top: '. $posx .'px; left: 62px; font-family: Arial, Helvetica; color: #FF0000; font-size: 6pt;">nazwa odbiorcy cd.</span>');
     $posx=131+$j*$SHIFT;
     echo('<span style="position: absolute; top: '. $posx .'px; left: 62px; font-family: Arial, Helvetica;color: #FF0000;font-size: 6pt;">l.k.</span>');
     $posx=131+$j*$SHIFT;
     echo('<span style="position: absolute; top: '. $posx .'px; left: 102px; font-family: Arial, Helvetica; color: #FF0000;font-size: 6pt;">nr rachunku odbiorcy</span>');
     $posx=163+$j*$SHIFT;
     echo('<span style="position: absolute; top: '. $posx .'px; left: 352px; font-family: Arial, Helvetica; color: #FF0000;font-size: 6pt;">kwota</span>');
     $posx=194+$j*$SHIFT;
     echo('<span style="position: absolute; top: '. $posx .'px; left: 72px; font-family: Arial, Helvetica; color: #FF0000;font-size: 6pt;">kwota słownie</span>');
     $posx=222+$j*$SHIFT;
     echo('<span style="position: absolute; top: '. $posx .'px; left: 72px; font-family: Arial, Helvetica; color: #FF0000;font-size: 6pt;">nazwa zleceniodawcy</span>');
     $posx=253+$j*$SHIFT;
     echo('<span style="position: absolute; top: '. $posx .'px; left: 72px; font-family: Arial, Helvetica; color: #FF0000;font-size: 6pt;">nazwa zleceniodawcy cd.</span>');
     $posx=284+$j*$SHIFT;
     echo('<span style="position: absolute; top: '. $posx .'px; left: 72px; font-family: Arial, Helvetica; color: #FF0000;font-size: 6pt;">tytułem</span>');
     $posx=317+$j*$SHIFT;
     echo('<span style="position: absolute; top: '. $posx .'px; left: 72px; font-family: Arial, Helvetica; color: #FF0000;font-size: 6pt;">tytułem cd.</span>');
     $posx=395+$j*$SHIFT;
     echo('<span style="position: absolute; top: '. $posx .'px; left: 337px; font-family: Arial, Helvetica; color: #FF0000;font-size: 6pt;">Opłata</span>');
     $posx=425+$j*$SHIFT;
     echo('<span style="position: absolute; top: '. $posx .'px; left: 337px; font-family: Arial, Helvetica; color: #FF0000;font-size: 6pt;">Podpis</span>');

// waluta:

     $posx=174+$j*$SHIFT;
    for ($i=0; $i<3; $i++) {
         $posy=272+$i*19;
         echo('<span style="position: absolute; top: '. $posx .'px; left: '. $posy. 'px; font-family: Courier, Arial, Helvetica; font-size: 12pt; font-weight: bold;">'.$CURR[$i].'</span>');
    }

// nazwa beneficjenta:
     
     $posx=75+$j*$SHIFT;
    for ($i=0; $i<27; $i++) {
         $posy=62+$i*19;
         echo('<span style="position: absolute; top: '. $posx .'px; left: '. $posy. 'px; font-family: Courier, Arial, Helvetica; font-size: 12pt; font-weight: bold;">'.mb_substr($ISP1_DO, $i, 1).'</span>');
    }
     
     $posx=109+$j*$SHIFT;
    for ($i=0; $i<27; $i++) {
         $posy=62+$i*19;
         echo('<span style="position: absolute; top: '. $posx .'px; left: '. $posy. 'px; font-family: Courier, Arial, Helvetica; font-size: 12pt; font-weight: bold;">'.mb_substr($ISP2_DO, $i, 1).'</SpAn>');
    }

// numer konta beneficjenta:

     $posx=141+$j*$SHIFT;
    for ($i=0; $i<26; $i++) {
         $posy=62+$i*19;
         echo('<span style="position: absolute; top: '. $posx .'px; left: '. $posy. 'px; font-family: Courier, Arial, Helvetica; font-size: 12pt; font-weight: bold;">'.$KONTO_DO[$i].'</SpAn>');
    }

// kwota cyfrowo:

     $posx=174+$j*$SHIFT;
     $KWOTA_SL = sprintf("%0'--12.2f", $KWOTA_NR);
     $KWOTA_SL = str_replace('.', ',', $KWOTA_SL);
    for ($i=0; $i<12; $i++) {
         $posy=347+$i*19;
         echo('<SPAN style="position: absolute; top: '. $posx .'px; left: ' . $posy . 'px; font-family: Courier, Arial, Helvetica; font-size: 12pt; font-weight: bold;">'. $KWOTA_SL[$i] .'</SPAN>');
    }

// kwota słownie:

     $posx=205+$j*$SHIFT;
     echo('<span style="position: absolute; top: '. $posx .'px; left: 62px; font-family: Courier, Arial, Helvetica; font-size: 8pt; font-weight: bold; ";>'.$KWOTA_X.'</span>');

// dane płatnika:


    if (mb_strlen($USER_OD)>54) {  // jeżeli nazwa+adres są dłuższe niz 54 znaki _nie_ wpisujemy w kratki
        $posx=235+$j*$SHIFT;
         echo('<span style="position: absolute; top: '. $posx .'px; left: 62px; font-family: Courier, Arial, Helvetica; font-size: 12pt; font-weight: bold;">'. mb_substr($USER_OD, 0, 50) .'</span>');
         $posx=265+$j*$SHIFT;
         echo('<span style="position: absolute; top: '. $posx .'px; left: 62px; font-family: Courier, Arial, Helvetica; font-size: 12pt; font-weight: bold;">'. mb_substr($USER_OD, 50, 100) .'</span>');
    } else // jeżeli nazwa+adres zmieszczą się w kratkach to wpisujemy w kratkach
     {
         $posx=235+$j*$SHIFT;
        for ($i=0; $i<27; $i++) {
            if ($i < mb_strlen($USER_OD)) {
                $posy=62+$i*19;
                echo('<span style="position: absolute; top: '. $posx .'px; left: '. $posy. 'px; font-family: Courier, Arial, Helvetica; font-size: 12pt; font-weight: bold;">'. mb_substr($USER_OD, $i, 1).'</span>');
            }
        }
         $posx=265+$j*$SHIFT;
        for ($i=27; $i<54; $i++) {
            if ($i < mb_strlen($USER_OD)) {
                $posy=62+($i-27)*19;
                echo('<span style="position: absolute; top: '. $posx .'px; left: '. $posy. 'px; font-family: Courier, Arial, Helvetica; font-size: 12pt; font-weight: bold;">'. mb_substr($USER_OD, $i, 1).'</span>');
            }
        }
    }

// tytułem:

     $posx=298+$j*$SHIFT;
    for ($i=0; $i<27; $i++) {
        if ($i < mb_strlen($USER_TY)) {
            $posy=62+$i*19;
            echo('<span style="position: absolute; top: '. $posx .'px; left: '. $posy. 'px; font-family: Courier, Arial, Helvetica; font-size: 12pt; font-weight: bold;">'. mb_substr($USER_TY, $i, 1).'</span>');
        }
    }


     //$posx=327+$j*$SHIFT;   // wolna linijka
     $posx=329+$j*$SHIFT;   // wolna linijka
     echo('<span style="position: absolute; top: '. $posx .'px; left: 62px; height: 15px; background-color: white; font-family: Courier, Arial, Helvetica; font-size: 12pt; font-weight: bold;">'
        . '<img src="data:image/png;base64,' . $barcode_image . '"></span>');
} //  koniec pętli głównej
?>

<span style="position: absolute; top: 880px; left: 12px; font-family: Arial, Helvetica; font-size: 12pt; font-weight: bold;">Wydrukowano przy użyciu LMS (http://www.lms.org.pl)</span>
<span style="position: absolute; top: 900px; left: 12px; font-family: Arial, Helvetica; font-size: 12pt; font-weight: bold;">LMS kompletny system sieciowo-księgowy dla małych ISPów i ASKów, dostępny na licencji GNU GPL</span>

</HTML>
