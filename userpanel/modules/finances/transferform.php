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

global $LMS,$SESSION;

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
	$line_1 = ConfigHelper::getConfig('finances.line_1');
	if (!empty($line_1))
		$ISP1_DO = $line_1;
	$line_2 = ConfigHelper::getConfig('finances.line_2');
	if (!empty($line_2))
		$ISP2_DO = $line_2;
}

$USER_T1 = ConfigHelper::getConfig('finances.pay_title', 'Abonament - ID:%CID% %LongCID%');
$CURR = 'PLN';
$SHORT_TO_WORDS = ConfigHelper::checkConfig('phpui.to_words_short_version');

$Before = array ("%CID%", "%LongCID%");
$After = array ($customer['id'], sprintf('%04d',$customer['id']));
$USER_TY = str_replace($Before,$After,$USER_T1);
$KWOTA = trim($customer['balance']*-1);
$USER_OD = trim($customer['customername']);
$USER_ADDR = trim($customer['zip'].' '.$customer['city'].' '.$customer['address']);
$KWOTA_NR = str_replace(',','.',$KWOTA);  // na wszelki wypadek
$KWOTA_GR = sprintf('%02d',round(($KWOTA_NR - floor($KWOTA_NR))*100));

if($SHORT_TO_WORDS)
{
	$KWOTA_ZL = to_words(floor($KWOTA_NR), 0, '', 1);
	$KWOTA_X = $KWOTA_ZL .' '. $KWOTA_GR. '/100';
}
else
{
	$KWOTA_ZL = to_words(floor($KWOTA_NR));
	$KWOTA_GR = to_words($KWOTA_GR);
	$KWOTA_X = trans('$a dollars $b cents', $KWOTA_ZL, $KWOTA_GR);
}

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1 //EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="pl">
<head>
  <meta content="text/html; charset=utf-8" http-equiv="content-type" />
  <title>Wirtualne Biuro Obsługi Klienta</title>
</head>
<body>

<?php

$SHIFT=394; // drugi druczek przesunięcie o 394

for ( $j=0; $j<2; $j++ ) // pętla główna
{
// teksty na druczku:

     $posx=60+$j*$SHIFT; 
     echo('<div style="position: absolute; top: '. $posx .'px; left: 10px"><img src="modules/finances/style/default/przelew.png" border="0" alt="wpłata gotówkowa" /></div>');
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
     for ( $i=0, $len=min(mb_strlen($CURR), 27); $i<$len; $i++ ) 
     {
          $posy=272+$i*19;
          echo('<span style="position: absolute; top: '. $posx .'px; left: '. $posy. 'px; font-family: Courier, Arial, Helvetica; font-size: 12pt; font-weight: bold;">'.$CURR[$i].'</span>');
     }

// nazwa beneficjenta:
     
     if (mb_strlen($ISP1_DO)>27)  // jeżeli nazwa 27 znaki _nie_ wpisujemy w kratki
     {
          $posx=75+$j*$SHIFT;
          echo('<span style="position: absolute; top: '. $posx .'px; left: 62px; font-family: Courier, Arial, Helvetica; font-size: 12pt; font-weight: bold;">'. mb_substr($ISP1_DO,0,50) .'</span>');
     }
     else
     {
          $posx=75+$j*$SHIFT;
          for ( $i=0; $i<27; $i++ ) 
          {
               $posy=62+$i*19;
               echo('<span style="position: absolute; top: '. $posx .'px; left: '. $posy. 'px; font-family: Courier, Arial, Helvetica; font-size: 12pt; font-weight: bold;">' . mb_substr($ISP1_DO, $i, 1) .'</span>');
          }
     }

     if (mb_strlen($ISP2_DO)>27)  // jeżeli nazwa 27 znaki _nie_ wpisujemy w kratki
     {
          $posx=109+$j*$SHIFT;
          echo('<span style="position: absolute; top: '. $posx .'px; left: 62px; font-family: Courier, Arial, Helvetica; font-size: 12pt; font-weight: bold;">'. mb_substr($ISP2_DO,0,50) .'</span>');
     }
     else
     {
          $posx=109+$j*$SHIFT;
          for ( $i=0; $i<27; $i++ ) 
          {
               $posy=62+$i*19;
               echo('<span style="position: absolute; top: '. $posx .'px; left: '. $posy. 'px; font-family: Courier, Arial, Helvetica; font-size: 12pt; font-weight: bold;">' . mb_substr($ISP2_DO, $i, 1) . '</span>');
          }
     }

// numer konta beneficjenta:

     $posx=141+$j*$SHIFT;
     for ( $i=0, $len=min(mb_strlen($KONTO_DO), 26); $i<$len; $i++ )
     {
          $posy=62+$i*19;
          echo('<span style="position: absolute; top: '. $posx .'px; left: '. $posy. 'px; font-family: Courier, Arial, Helvetica; font-size: 12pt; font-weight: bold;">'.$KONTO_DO[$i].'</span>');
     }

// kwota cyfrowo:

     $posx=174+$j*$SHIFT;
     $KWOTA_SL = sprintf("%0'12.2f", $KWOTA_NR);
     $KWOTA_SL = str_replace('.',',',$KWOTA_SL);
     for ( $i=0, $len=min(mb_strlen($KWOTA_SL), 12); $i<$len; $i++ ) 
     {
          $posy=347+$i*19;
          echo('<span style="position: absolute; top: '. $posx .'px; left: ' . $posy . 'px; font-family: Courier, Arial, Helvetica; font-size: 12pt; font-weight: bold;">' .  mb_substr($KWOTA_SL, $i, 1) . '</span>');
     }

// kwota słownie:

     $posx=205+$j*$SHIFT;
     echo('<span style="position: absolute; top: '. $posx .'px; left: 62px; font-family: Courier, Arial, Helvetica; font-size: 8pt; font-weight: bold;">'.$KWOTA_X.'</span>');

// dane płatnika:


     if (mb_strlen($USER_OD)>27)  // jeżeli nazwa 27 znaki _nie_ wpisujemy w kratki
     {
          $posx=235+$j*$SHIFT;
          echo('<span style="position: absolute; top: '. $posx .'px; left: 62px; font-family: Courier, Arial, Helvetica; font-size: 12pt; font-weight: bold;">'. mb_substr($USER_OD,0,50) .'</span>');
     }
     else                // jeżeli nazwa+adres zmieszczą się w kratkach to wpisujemy w kratkach
     {                    
          $posx=235+$j*$SHIFT;
          for ( $i=0, $len=min(mb_strlen($USER_OD), 27); $i<$len; $i++ ) 
          {
               $posy=62+$i*19;
               echo('<span style="position: absolute; top: '. $posx .'px; left: '. $posy. 'px; font-family: Courier, Arial, Helvetica; font-size: 12pt; font-weight: bold;">'. mb_substr($USER_OD, $i, 1) .'</span>');
          }
     }

     if (mb_strlen($USER_ADDR)>27)  // jeżeli adres jest dłuższy niz 27 znaków _nie_ wpisujemy w kratki
     {
          $posx=265+$j*$SHIFT;
          echo('<span style="position: absolute; top: '. $posx .'px; left: 62px; font-family: Courier, Arial, Helvetica; font-size: 12pt; font-weight: bold;">'. mb_substr($USER_ADDR,0,50) .'</span>');
     }
     else                // jeżeli nazwa+adres zmieszczą się w kratkach to wpisujemy w kratkach
     {                    
          $posx=265+$j*$SHIFT;
          for ( $i=0, $len=min(mb_strlen($USER_ADDR), 27); $i<$len; $i++ ) 
          {
               $posy=62+$i*19;
               echo('<span style="position: absolute; top: '. $posx .'px; left: '. $posy. 'px; font-family: Courier, Arial, Helvetica; font-size: 12pt; font-weight: bold;">'. mb_substr($USER_ADDR, $i, 1) .'</span>');
          }
     }

// tytułem:

     $posx=298+$j*$SHIFT;
     for ( $i=0, $len=min(mb_strlen($USER_TY), 27); $i<$len; $i++ ) 
     {
          $posy=62+$i*19;
          echo('<span style="position: absolute; top: '. $posx .'px; left: '. $posy. 'px; font-family: Courier, Arial, Helvetica; font-size: 12pt; font-weight: bold;">' . mb_substr($USER_TY, $i, 1) . '</span>');
     }


     $posx=327+$j*$SHIFT;   // wolna linijka
     echo('<span style="position: absolute; top: '. $posx .'px; left: 62px; font-family: Courier, Arial, Helvetica; font-size: 12pt; font-weight: bold;">-</span>');

} //  koniec pętli głównej

?>
</body>
</html>
