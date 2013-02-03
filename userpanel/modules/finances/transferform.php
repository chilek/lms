<?php 

/*
 *  LMS version 1.11-git
 *
 *  (C) Copyright 2001-2013 LMS Developers
 *
 *  Please, see the doc/AUTHORS for more information about authors!
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License Version 2 as
 *  published by the Free Software Foundation.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program; if not, write to the Free Software
 *  Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307,
 *  USA.
 *
 *  $Id$
 */

global $LMS,$CONFIG,$SESSION;

$customer = $LMS->GetCustomer($SESSION->id);
$division = $LMS->DB->GetRow('SELECT account, name, address, zip, city
	FROM divisions WHERE id = ?', array($customer['divisionid']));

//  NRB 26 cyfr: 2 kontrolne, 8 nr banku, 16 nr konta 
$KONTO_DO = bankaccount($customer['id'], $division['account']);

if ($division) {
	list($division['name']) = explode("\n", $division['name']);
	$ISP1_DO = $division['name'];
	$ISP2_DO = trim($division['zip'].' '.$division['city'].' '.$division['address']);
} else {
	if (!empty($CONFIG['finances']['line_1']))
		$ISP1_DO = $CONFIG['finances']['line_1'];
	if (!empty($CONFIG['finances']['line_2']))
		$ISP2_DO = $CONFIG['finances']['line_2'];
}

$USER_T1 = (!isset($CONFIG['finances']['pay_title']) ? 'Abonament - ID:%CID% %LongCID%' : iconv('UTF-8','ISO-8859-2',$CONFIG['finances']['pay_title']));
$CURR = 'PLN';
$SHORT_TO_WORDS = check_conf('phpui.to_words_short_version');

$Before = array ("%CID%", "%LongCID%");
$After = array ($customer['id'], sprintf('%04d',$customer['id']));
$USER_TY = str_replace($Before,$After,$USER_T1);
$KWOTA = trim($customer['balance']*-1);
$ISP1_DO = iconv('UTF-8','ISO-8859-2',$ISP1_DO);
$ISP2_DO = iconv('UTF-8','ISO-8859-2',$ISP2_DO);
$USER_OD = trim(iconv('UTF-8','ISO-8859-2', $customer['customername']));
$USER_ADDR = trim(iconv('UTF-8','ISO-8859-2', $customer['zip'].' '.$customer['city'].' '.$customer['address']));
$KWOTA_NR = str_replace(',','.',$KWOTA);  // na wszelki wypadek
$KWOTA_GR = sprintf('%02d',round(($KWOTA_NR - floor($KWOTA_NR))*100));

if($SHORT_TO_WORDS)
{
	$KWOTA_ZL = to_words(floor($KWOTA_NR), 0, '', 1);
	$KWOTA_ZL = iconv('UTF-8','ISO-8859-2',$KWOTA_ZL);
	$KWOTA_X = $KWOTA_ZL .' '. $KWOTA_GR. '/100';
}
else
{
	$KWOTA_ZL = to_words(floor($KWOTA_NR));
	$KWOTA_GR = to_words($KWOTA_GR);
	$KWOTA_X = iconv('UTF-8', 'ISO-8859-2', trans('$a dollars $b cents', $KWOTA_ZL, $KWOTA_GR));
}

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1 //EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="pl">
<head>
  <meta content="text/html; charset=ISO-8859-2" http-equiv="content-type" />
  <title>Wirtualne Biuro Obs³ugi Klienta</title>
</head>
<body>

<?php

$SHIFT=394; // drugi druczek przesuniêcie o 394

for ( $j=0; $j<2; $j++ ) // pêtla g³ówna
{
// teksty na druczku:

     $posx=60+$j*$SHIFT; 
     echo('<div style="position: absolute; top: '. $posx .'px; left: 10px"><img src="modules/finances/style/default/przelew.png" border="0" alt="wp³ata gotówkowa" /></div>');
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
     echo('<span style="position: absolute; top: '. $posx .'px; left: 72px; font-family: Arial, Helvetica; color: #FF0000;font-size: 6pt;">kwota s³ownie</span>');
     $posx=222+$j*$SHIFT;
     echo('<span style="position: absolute; top: '. $posx .'px; left: 72px; font-family: Arial, Helvetica; color: #FF0000;font-size: 6pt;">nazwa zleceniodawcy</span>');
     $posx=253+$j*$SHIFT;
     echo('<span style="position: absolute; top: '. $posx .'px; left: 72px; font-family: Arial, Helvetica; color: #FF0000;font-size: 6pt;">nazwa zleceniodawcy cd.</span>');
     $posx=284+$j*$SHIFT;
     echo('<span style="position: absolute; top: '. $posx .'px; left: 72px; font-family: Arial, Helvetica; color: #FF0000;font-size: 6pt;">tytu³em</span>');
     $posx=317+$j*$SHIFT;
     echo('<span style="position: absolute; top: '. $posx .'px; left: 72px; font-family: Arial, Helvetica; color: #FF0000;font-size: 6pt;">tytu³em cd.</span>');
     $posx=395+$j*$SHIFT;
     echo('<span style="position: absolute; top: '. $posx .'px; left: 337px; font-family: Arial, Helvetica; color: #FF0000;font-size: 6pt;">Op³ata</span>');
     $posx=425+$j*$SHIFT;
     echo('<span style="position: absolute; top: '. $posx .'px; left: 337px; font-family: Arial, Helvetica; color: #FF0000;font-size: 6pt;">Podpis</span>');

// waluta:

     $posx=174+$j*$SHIFT;
     for ( $i=0, $len=min(strlen($CURR), 27); $i<$len; $i++ ) 
     {
          $posy=272+$i*19;
          echo('<span style="position: absolute; top: '. $posx .'px; left: '. $posy. 'px; font-family: Courier, Arial, Helvetica; font-size: 12pt; font-weight: bold;">'.$CURR[$i].'</span>');
     }

// nazwa beneficjenta:
     
     if (strlen($ISP1_DO)>27)  // je¿eli nazwa 27 znaki _nie_ wpisujemy w kratki
     {
          $posx=75+$j*$SHIFT;
          echo('<span style="position: absolute; top: '. $posx .'px; left: 62px; font-family: Courier, Arial, Helvetica; font-size: 12pt; font-weight: bold;">'. substr($ISP1_DO,0,50) .'</span>');
     }
     else
     {
          $posx=75+$j*$SHIFT;
          for ( $i=0; $i<27; $i++ ) 
          {
               $posy=62+$i*19;
               echo('<span style="position: absolute; top: '. $posx .'px; left: '. $posy. 'px; font-family: Courier, Arial, Helvetica; font-size: 12pt; font-weight: bold;">'.$ISP1_DO[$i].'</span>');
          }
     }

     if (strlen($ISP2_DO)>27)  // je¿eli nazwa 27 znaki _nie_ wpisujemy w kratki
     {
          $posx=109+$j*$SHIFT;
          echo('<span style="position: absolute; top: '. $posx .'px; left: 62px; font-family: Courier, Arial, Helvetica; font-size: 12pt; font-weight: bold;">'. substr($ISP2_DO,0,50) .'</span>');
     }
     else
     {
          $posx=109+$j*$SHIFT;
          for ( $i=0; $i<27; $i++ ) 
          {
               $posy=62+$i*19;
               echo('<span style="position: absolute; top: '. $posx .'px; left: '. $posy. 'px; font-family: Courier, Arial, Helvetica; font-size: 12pt; font-weight: bold;">'.$ISP2_DO[$i].'</span>');
          }
     }

// numer konta beneficjenta:

     $posx=141+$j*$SHIFT;
     for ( $i=0, $len=min(strlen($KONTO_DO), 26); $i<$len; $i++ )
     {
          $posy=62+$i*19;
          echo('<span style="position: absolute; top: '. $posx .'px; left: '. $posy. 'px; font-family: Courier, Arial, Helvetica; font-size: 12pt; font-weight: bold;">'.$KONTO_DO[$i].'</span>');
     }

// kwota cyfrowo:

     $posx=174+$j*$SHIFT;
     $KWOTA_SL = sprintf("%0'12.2f", $KWOTA_NR);
     $KWOTA_SL = str_replace('.',',',$KWOTA_SL);
     for ( $i=0, $len=min(strlen($KWOTA_SL), 12); $i<$len; $i++ ) 
     {
          $posy=347+$i*19;
          echo('<span style="position: absolute; top: '. $posx .'px; left: ' . $posy . 'px; font-family: Courier, Arial, Helvetica; font-size: 12pt; font-weight: bold;">'. $KWOTA_SL[$i] .'</span>');
     }

// kwota s³ownie:

     $posx=205+$j*$SHIFT;
     echo('<span style="position: absolute; top: '. $posx .'px; left: 62px; font-family: Courier, Arial, Helvetica; font-size: 8pt; font-weight: bold;">'.$KWOTA_X.'</span>');

// dane p³atnika:


     if (strlen($USER_OD)>27)  // je¿eli nazwa 27 znaki _nie_ wpisujemy w kratki
     {
          $posx=235+$j*$SHIFT;
          echo('<span style="position: absolute; top: '. $posx .'px; left: 62px; font-family: Courier, Arial, Helvetica; font-size: 12pt; font-weight: bold;">'. substr($USER_OD,0,50) .'</span>');
     }
     else                // je¿eli nazwa+adres zmieszcz± siê w kratkach to wpisujemy w kratkach
     {                    
          $posx=235+$j*$SHIFT;
          for ( $i=0, $len=min(strlen($USER_OD), 27); $i<$len; $i++ ) 
          {
               $posy=62+$i*19;
               echo('<span style="position: absolute; top: '. $posx .'px; left: '. $posy. 'px; font-family: Courier, Arial, Helvetica; font-size: 12pt; font-weight: bold;">'. $USER_OD[$i].'</span>');
          }
     }

     if (strlen($USER_ADDR)>27)  // je¿eli adres jest d³u¿szy niz 27 znaków _nie_ wpisujemy w kratki
     {
          $posx=265+$j*$SHIFT;
          echo('<span style="position: absolute; top: '. $posx .'px; left: 62px; font-family: Courier, Arial, Helvetica; font-size: 12pt; font-weight: bold;">'. substr($USER_ADDR,0,50) .'</span>');
     }
     else                // je¿eli nazwa+adres zmieszcz± siê w kratkach to wpisujemy w kratkach
     {                    
          $posx=265+$j*$SHIFT;
          for ( $i=0, $len=min(strlen($USER_ADDR), 27); $i<$len; $i++ ) 
          {
               $posy=62+$i*19;
               echo('<span style="position: absolute; top: '. $posx .'px; left: '. $posy. 'px; font-family: Courier, Arial, Helvetica; font-size: 12pt; font-weight: bold;">'. $USER_ADDR[$i].'</span>');
          }
     }

// tytu³em:

     $posx=298+$j*$SHIFT;
     for ( $i=0, $len=min(strlen($USER_TY), 27); $i<$len; $i++ ) 
     {
          $posy=62+$i*19;
          echo('<span style="position: absolute; top: '. $posx .'px; left: '. $posy. 'px; font-family: Courier, Arial, Helvetica; font-size: 12pt; font-weight: bold;">'. $USER_TY[$i].'</span>');
     }


     $posx=327+$j*$SHIFT;   // wolna linijka
     echo('<span style="position: absolute; top: '. $posx .'px; left: 62px; font-family: Courier, Arial, Helvetica; font-size: 12pt; font-weight: bold;">-</span>');

} //  koniec pêtli g³ównej

?>
</body>
</html>
