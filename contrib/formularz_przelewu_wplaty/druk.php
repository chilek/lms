<HTML>
<HEAD>

<meta http-equiv="Content-Type" content="text/html;charset=ISO-8859-2">
</HEAD>
<BODY>

<FORM><P style="font-family: Arial, Helvetica; font-size: 12pt; font-weight: bold;">W zale�no�ci od poczty/banku wpisz 1 lub 2 kopie. <input type="button" value="Drukuj" onClick="top.print();"></FORM>

<?php 

// ustaw prawid�ow� �cie�k� do pliku common.php z katalogu /lib lmsa
require_once("../../lib/common.php");

//  NRB 26 cyfr, 2 kontrolne, 8 nr banku, 16 nr konta 

$KONTO_DO="0000000000000000000000000";

$ISP1_DO="xxxxxxxxxxxxxxxxxxxxxxx";
$ISP2_DO="xxxxxxxxxxxxxxxxxxxxxxx";

$KWOTA = $_GET['ILE'];
$USER_OD = $_GET['OD'];
$USER_TY = "Abonament - ID:".$_GET['UID'];

$KWOTA_NR = str_replace(',','.',$KWOTA);  // na wszelki wypadek
$KWOTA_ZL = to_words(floor($KWOTA_NR));
$KWOTA_GR = round(($KWOTA_NR - floor($KWOTA_NR))*100); 
$CURR="PLN";


$SHIFT=394; // drugi druczek przesuni�cie o 394


for ( $j=0; $j<2; $j++ ) // p�tla g��wna
{
// teksty na druczku:

     $posx=60+$j*$SHIFT; 
     echo('<div style="position: absolute; top: '. $posx .'px; left: 10px"><img src="przelew.png" border=0 alt="wp�ata got�wkowa"></div>');
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
     echo('<span style="position: absolute; top: '. $posx .'px; left: 72px; font-family: Arial, Helvetica; color: #FF0000;font-size: 6pt;">kwota s�ownie</span>');
     $posx=222+$j*$SHIFT;
     echo('<span style="position: absolute; top: '. $posx .'px; left: 72px; font-family: Arial, Helvetica; color: #FF0000;font-size: 6pt;">nazwa zleceniodawcy</span>');
     $posx=253+$j*$SHIFT;
     echo('<span style="position: absolute; top: '. $posx .'px; left: 72px; font-family: Arial, Helvetica; color: #FF0000;font-size: 6pt;">nazwa zleceniodawcy cd.</span>');
     $posx=284+$j*$SHIFT;
     echo('<span style="position: absolute; top: '. $posx .'px; left: 72px; font-family: Arial, Helvetica; color: #FF0000;font-size: 6pt;">tytu�em</span>');
     $posx=317+$j*$SHIFT;
     echo('<span style="position: absolute; top: '. $posx .'px; left: 72px; font-family: Arial, Helvetica; color: #FF0000;font-size: 6pt;">tytu�em cd.</span>');
     $posx=395+$j*$SHIFT;
     echo('<span style="position: absolute; top: '. $posx .'px; left: 337px; font-family: Arial, Helvetica; color: #FF0000;font-size: 6pt;">Op�ata</span>');
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
     $KWOTA_SL = sprintf("%0'--12.2f",$KWOTA);
     $KWOTA_SL = str_replace('.',',',$KWOTA_SL);
     for ( $i=0; $i<12; $i++ ) 
     {
          $posy=347+$i*19;
          echo('<SPAN style="position: absolute; top: '. $posx .'px; left: ' . $posy . 'px; font-family: Courier, Arial, Helvetica; font-size: 12pt; font-weight: bold;">'. $KWOTA_SL[$i] .'</SPAN>');
     }

// kwota s�ownie:

     $posx=205+$j*$SHIFT;
     echo('<span style="position: absolute; top: '. $posx .'px; left: 62px; font-family: Courier, Arial, Helvetica; font-size: 8pt; font-weight: bold; ";>' . $KWOTA_ZL .' '. $KWOTA_GR.'/100 z�otych</span>');

// dane p�atnika:


     if (strlen($USER_OD)>54)  // je�eli nazwa+adres s� d�u�sze niz 54 znaki _nie_ wpisujemy w kratki
     {
          $posx=235+$j*$SHIFT;
          echo('<span style="position: absolute; top: '. $posx .'px; left: 62px; font-family: Courier, Arial, Helvetica; font-size: 12pt; font-weight: bold;">'. substr($USER_OD,0,50) .'</span>');
          $posx=265+$j*$SHIFT;
          echo('<span style="position: absolute; top: '. $posx .'px; left: 62px; font-family: Courier, Arial, Helvetica; font-size: 12pt; font-weight: bold;">'. substr($USER_OD,50,100) .'</span>');
     }
     else                // je�eli nazwa+adres zmieszcz� si� w kratkach to wpisujemy w kratkach
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

// tytu�em:

     $posx=298+$j*$SHIFT;
     for ( $i=0; $i<27; $i++ ) 
     {
          $posy=62+$i*19;
          echo('<span style="position: absolute; top: '. $posx .'px; left: '. $posy. 'px; font-family: Courier, Arial, Helvetica; font-size: 12pt; font-weight: bold;">'. $USER_TY[$i].'</span>');
     }


     $posx=327+$j*$SHIFT;   // wolna linijka
     echo('<span style="position: absolute; top: '. $posx .'px; left: 62px; font-family: Courier, Arial, Helvetica; font-size: 12pt; font-weight: bold;">-</span>');

} //  koniec p�tli g��wnej
?>

<span style="position: absolute; top: 880px; left: 12px; font-family: Arial, Helvetica; font-size: 12pt; font-weight: bold;">Wydrukowano przy u�yciu LMS (http://lms.rulez.pl)</span>
<span style="position: absolute; top: 900px; left: 12px; font-family: Arial, Helvetica; font-size: 12pt; font-weight: bold;">LMS kompletny system sieciowo-ksi�gowy dla ma�ych ISP�w i ASK�w, dost�pny na licencji GNU GPL</span>

</HTML>
