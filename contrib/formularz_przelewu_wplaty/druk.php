<HTML>
<HEAD>

<META http-equiv="Content-Type" content="text/html;charset=ISO-8859-2">
</HEAD>
<BODY>

<FORM><P style="font-family: Arial, Helvetica; font-size: 12pt; font-weight: bold;">W zależności od poczty/banku wpisz 1 lub 2 kopie. <input type="button" value="Drukuj" onClick="top.print();"></FORM>

<?php 

/*
 * LMS version 1.7-cvs
 *
 *  (C) Copyright 2001-2005 LMS Developers
 *
 *  Please, see the doc/AUTHORS for more information about authors!
 *
 *  $Id$
 */

// REPLACE THIS WITH PATH TO YOU CONFIG FILE

$CONFIG_FILE = (is_readable('lms.ini')) ? 'lms.ini' : '/etc/lms/lms.ini';

// PLEASE DO NOT MODIFY ANYTHING BELOW THIS LINE UNLESS YOU KNOW
// *EXACTLY* WHAT ARE YOU DOING!!!
// *******************************************************************

header('X-Powered-By: LMS/1.7-cvs/contrib_formularz_przelewu_wplaty');

// Parse configuration file

function lms_parse_ini_file($filename, $process_sections = false) 
{
	$ini_array = array();
	$section = '';
	$lines = file($filename);
	foreach($lines as $line) 
	{
		$line = trim($line);
		
		if($line == '' || $line[0] == ';' || $line[0] == '#') 
			continue;
		
		list($sec_name) = sscanf($line, "[%[^]]");
		
		if( $sec_name )
			$section = trim($sec_name);
		else 
		{
			list($property, $value) = sscanf($line, "%[^=] = '%[^']'");
			if ( !$property || !$value ) 
			{
				list($property, $value) = sscanf($line, "%[^=] = \"%[^\"]\"");
				if ( !$property || !$value ) 
				{
					list($property, $value) = sscanf($line, "%[^=] = %[^;#]");
					if( !$property || !$value ) 
						continue;
					else
						$value = trim($value, "\"'");
				}
			}
		
			$property = trim($property);
			$value = trim($value);
			
			if($process_sections) 
				$ini_array[$section][$property] = $value;
			else 
				$ini_array[$property] = $value;
		}
	}
	
	return $ini_array;
}


foreach(lms_parse_ini_file($CONFIG_FILE, true) as $key => $val)
    $CONFIG[$key] = $val;

//  NRB 26 cyfr, 2 kontrolne, 8 nr banku, 16 nr konta 
$KONTO_DO = (! $CONFIG['finances']['account'] ? '98700000000000000000000123' : $CONFIG['finances']['account']);

// ustaw prawidłową ścieżkę do pliku z funkcją to_words()
require_once("../../lib/locale/pl/functions.php");

$ISP1_DO = (! $CONFIG['finances']['line_1'] ? 'LINIA1xxxxxxxxxxxxxxxxxxxyz' : $CONFIG['finances']['line_1']);
$ISP2_DO = (! $CONFIG['finances']['line_2'] ? 'linia2xxxxxxxxxxxxxxxxxxxyz' : $CONFIG['finances']['line_2']);

$USER_T1 = (! $CONFIG['finances']['pay_title'] ? 'Abonament - ID:%CID% %LongCID%' : $CONFIG['finances']['pay_title']);

$Before = array ("%CID%","%LongCID%");
$After = array ($_GET['UID'],sprintf('%04d',$_GET['UID']));

$USER_TY=str_replace($Before,$After,$USER_T1);

$CURR = 'PLN';
$SHORT_TO_WORDS = 0;	// 1 - krótki format kwoty słownej 'jed dwa trz 15/100'
			// 0 - długi format kwoty słownej 'sto dwadzieścia trzy 15/100 zł'

$USE_ICONV = 1;		// włącza przekodowywanie ciągów z UTF-8 do ISO-8859-2

/************** Koniec konfiguracji ****************/

$KWOTA = trim($_GET['ILE']);
$USER_OD = trim($_GET['OD']);
$USER_OD = $USE_ICONV ? iconv('UTF-8','ISO-8859-2',$USER_OD) : $USER_OD;

$KWOTA_NR = str_replace(',','.',$KWOTA);  // na wszelki wypadek
$KWOTA_GR = sprintf('%02d',round(($KWOTA_NR - floor($KWOTA_NR))*100));

if($SHORT_TO_WORDS)
{
	$KWOTA_ZL = to_words(floor($KWOTA_NR), 0, '', 1);
	if($USE_ICONV) $KWOTA_ZL = iconv('UTF-8','ISO-8859-2',$KWOTA_ZL);
	$KWOTA_X = $KWOTA_ZL .' '. $KWOTA_GR. '/100';
}
else
{
	$KWOTA_ZL = to_words(floor($KWOTA_NR));
	if($USE_ICONV) $KWOTA_ZL = iconv('UTF-8','ISO-8859-2',$KWOTA_ZL);
	$KWOTA_X = $KWOTA_ZL .' '. $KWOTA_GR. '/100 złotych';
}

$SHIFT=394; // drugi druczek przesunięcie o 394

for ( $j=0; $j<2; $j++ ) // pętla główna
{
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
     for ( $i=0; $i<27; $i++ ) 
     {
          $posy=272+$i*19;
          echo('<span style="position: absolute; top: '. $posx .'px; left: '. $posy. 'px; font-family: Courier, Arial, Helvetica; font-size: 12pt; font-weight: bold;">'.$CURR[$i].'</SpAn>');
     }

// nazwa beneficjenta:
     
     $posx=75+$j*$SHIFT;
     for ( $i=0; $i<27; $i++ ) 
     {
          $posy=62+$i*19;
          echo('<span style="position: absolute; top: '. $posx .'px; left: '. $posy. 'px; font-family: Courier, Arial, Helvetica; font-size: 12pt; font-weight: bold;">'.$ISP1_DO[$i].'</SpAn>');
     }
     
     $posx=109+$j*$SHIFT;
     for ( $i=0; $i<27; $i++ ) 
     {
          $posy=62+$i*19;
          echo('<span style="position: absolute; top: '. $posx .'px; left: '. $posy. 'px; font-family: Courier, Arial, Helvetica; font-size: 12pt; font-weight: bold;">'.$ISP2_DO[$i].'</SpAn>');
     }

// numer konta beneficjenta:

     $posx=141+$j*$SHIFT;
     for ( $i=0; $i<26; $i++ )
     {
          $posy=62+$i*19;
          echo('<span style="position: absolute; top: '. $posx .'px; left: '. $posy. 'px; font-family: Courier, Arial, Helvetica; font-size: 12pt; font-weight: bold;">'.$KONTO_DO[$i].'</SpAn>');
     }

// kwota cyfrowo:

     $posx=174+$j*$SHIFT;
     $KWOTA_SL = sprintf("%0'--12.2f",$KWOTA_NR);
     $KWOTA_SL = str_replace('.',',',$KWOTA_SL);
     for ( $i=0; $i<12; $i++ ) 
     {
          $posy=347+$i*19;
          echo('<SPAN style="position: absolute; top: '. $posx .'px; left: ' . $posy . 'px; font-family: Courier, Arial, Helvetica; font-size: 12pt; font-weight: bold;">'. $KWOTA_SL[$i] .'</SPAN>');
     }

// kwota słownie:

     $posx=205+$j*$SHIFT;
     echo('<span style="position: absolute; top: '. $posx .'px; left: 62px; font-family: Courier, Arial, Helvetica; font-size: 8pt; font-weight: bold; ";>'.$KWOTA_X.'</span>');

// dane płatnika:


     if (strlen($USER_OD)>54)  // jeżeli nazwa+adres są dłuższe niz 54 znaki _nie_ wpisujemy w kratki
     {
          $posx=235+$j*$SHIFT;
          echo('<span style="position: absolute; top: '. $posx .'px; left: 62px; font-family: Courier, Arial, Helvetica; font-size: 12pt; font-weight: bold;">'. substr($USER_OD,0,50) .'</span>');
          $posx=265+$j*$SHIFT;
          echo('<span style="position: absolute; top: '. $posx .'px; left: 62px; font-family: Courier, Arial, Helvetica; font-size: 12pt; font-weight: bold;">'. substr($USER_OD,50,100) .'</span>');
     }
     else                // jeżeli nazwa+adres zmieszczą się w kratkach to wpisujemy w kratkach
     {                    
          $posx=235+$j*$SHIFT;
          for ( $i=0; $i<27; $i++ ) 
          {
               $posy=62+$i*19;
               echo('<span style="position: absolute; top: '. $posx .'px; left: '. $posy. 'px; font-family: Courier, Arial, Helvetica; font-size: 12pt; font-weight: bold;">'. $USER_OD[$i].'</span>');
          }
          $posx=265+$j*$SHIFT;
          for ( $i=27; $i<54; $i++ ) 
          {
               $posy=62+$i*19-513;
               echo('<span style="position: absolute; top: '. $posx .'px; left: '. $posy. 'px; font-family: Courier, Arial, Helvetica; font-size: 12pt; font-weight: bold;">'. $USER_OD[$i].'</span>');
          }
     }

// tytułem:

     $posx=298+$j*$SHIFT;
     for ( $i=0; $i<27; $i++ ) 
     {
          $posy=62+$i*19;
          echo('<span style="position: absolute; top: '. $posx .'px; left: '. $posy. 'px; font-family: Courier, Arial, Helvetica; font-size: 12pt; font-weight: bold;">'. $USER_TY[$i].'</span>');
     }


     $posx=327+$j*$SHIFT;   // wolna linijka
     echo('<span style="position: absolute; top: '. $posx .'px; left: 62px; font-family: Courier, Arial, Helvetica; font-size: 12pt; font-weight: bold;">-</span>');

} //  koniec pętli głównej
?>

<span style="position: absolute; top: 880px; left: 12px; font-family: Arial, Helvetica; font-size: 12pt; font-weight: bold;">Wydrukowano przy użyciu LMS (http://lms.rulez.pl)</span>
<span style="position: absolute; top: 900px; left: 12px; font-family: Arial, Helvetica; font-size: 12pt; font-weight: bold;">LMS kompletny system sieciowo-księgowy dla małych ISPów i ASKów, dostępny na licencji GNU GPL</span>

</HTML>
