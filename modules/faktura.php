<?php

/*
 * LMS version 1.0-pre10
 *
 *  (C) Copyright 2001-2003 LMS Developers
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

setlocale (LC_TIME, "pl_PL");


function slownie($liczba) {
// Funkcja nie akceptuje liczb wiêkszych ni¿ 999... No có¿ ;) Jak komu¶ to 
// potrzebne to niech se wklepie
 $z['0'] = "zero";
 $z['1'] = "jeden";
 $z['2'] = "dwa";
 $z['3'] = "trzy";
 $z['4'] = "cztery";
 $z['5'] = "piêæ";
 $z['6'] = "sze¶æ";
 $z['7'] = "siedem";
 $z['8'] = "osiem";
 $z['9'] = "dziewiêæ";
 $z['10'] = "dziesiêæ";
 $z['11'] = "jedena¶cie";
 $z['12'] = "dwana¶cie";
 $z['13'] = "trzyna¶cie";
 $z['14'] = "czterna¶cie";
 $z['15'] = "piêtna¶cie";
 $z['16'] = "szesna¶cie";
 $z['17'] = "siedemna¶cie";
 $z['18'] = "osiemna¶cie";
 $z['19'] = "dziewiêtna¶cie";
 $z['20'] = "dwadzie¶cia";
 $z['30'] = "trzydzie¶ci";
 $z['40'] = "czterdzie¶ci";
 $z['50'] = "pieædziesi±t";
 $z['60'] = "sze¶ædziesi±t";
 $z['70'] = "siedemdziesi±t";
 $z['80'] = "osiemdziesi±t";
 $z['90'] = "dziewieædziesi±t";
 $z['100'] = "sto";
 $z['200'] = "dwie¶cie";
 $z['300'] = "trzysta";
 $z['400'] = "czterysta";
 $z['500'] = "piêæset";
 $z['600'] = "sze¶æset";
 $z['700'] = "siedemset";
 $z['800'] = "osiemset";
 $z['900'] = "dziewiêæset";
 $slow='';
 if ($liczba>100) {
    $slow .=$z[round($liczba / 100)*100]." ";
    $liczba = $liczba % 100;
 }
 if ($liczba>20) {
    $slow .=$z[round($liczba / 10)*10]." ";
    $liczba =  $liczba % 10;
 }
 if (($liczba>0) or (strlen($slow)<1)) {$slow .=$z[$liczba];}
 return $slow;
}
	   
$cash=$LMS->GetCashByID($_GET[id]);
$userinfo=$LMS->GetUser($cash['userid']);

// Zczytujemy plik
$plik = fopen ($_CONFIG[finances]['template'],"r");
while (!feof ($plik)){   
    $szablon .= fgets ($plik, 1500);
}
fclose($plik);

$roboczy = $szablon;

$trans = array();    

// Najpierw rozwi±¿emy sobie rzeczy które s± takie same w ca³ej fakturze

$roboczy = str_replace('%nabywca',strtr($userinfo['username'],$trans),$roboczy);
$roboczy = str_replace('%nab_adres_cd',strtr($userinfo['zip']." ".$userinfo['city'],$trans),$roboczy);
$roboczy = str_replace('%nab_adres',strtr($userinfo['address'],$trans),$roboczy);
$roboczy = str_replace('%nip',strtr($userinfo['nip'],$trans),$roboczy);
$roboczy = str_replace('%nr_klienta',strtr($_GET[id],$trans),$roboczy);
$roboczy = str_replace('%sprzedawca',strtr($_CONFIG[finances]['name'],$trans),$roboczy);
$roboczy = str_replace('%sprzed_adres_cd',strtr($_CONFIG[finances]['zip']." ".$_CONFIG[finances]['city'],$trans),$roboczy);
$roboczy = str_replace('%sprzed_adres',strtr($_CONFIG[finances]['address'],$trans),$roboczy);
$roboczy = str_replace('%numer',strtr($_GET[id]."/".date("Y",$cash['time']),$trans),$roboczy);
$roboczy = str_replace('%data',strtr(date("d.m.Y"),$trans),$roboczy);
$roboczy = str_replace('%termin',strtr(date("d.m.Y",mktime(0,0,0,date("m"),date("d")+$_CONFIG[finances]['deadline'],date("Y"))),$trans),$roboczy);
$roboczy = str_replace('%dni',strtr($_CONFIG[finances]['deadline'],$trans),$roboczy);
$roboczy = str_replace('%konto',strtr($_CONFIG[finances]['bank']." ".$_CONFIG[finances]['account'],$trans),$roboczy);
$roboczy = str_replace('%wystawil',strtr($layout['logname'],$trans),$roboczy);
$roboczy = str_replace('%stopka',strtr($_CONFIG[finances]['footer'],$trans),$roboczy);
$roboczy = str_replace('%vat','7%',$roboczy);

// A teraz potniemy na kawa³ki i zmienimy jedynie to co w tabelce

$kawalki = explode('<!-- body -->',$roboczy);
$head=$kawalki[0];
$kawalki = explode('<!-- table -->',$kawalki[1]);
$body1=$kawalki[0];
$kawalki = explode('<!-- /table -->',$kawalki[1]);
$table=$kawalki[0];
$kawalki = explode('<!-- break -->',$kawalki[1]);
$body2=$kawalki[0];
$kawalki = explode('<!-- tail -->',$kawalki[1]);
$break=$kawalki[0];
$tail=$kawalki[1];

echo $head;
echo $body1;

$line=1;
foreach($LMS->ADB->GetAll("SELECT * FROM `cash` WHERE `userid`=".$cash['userid']) as $row) {
    $roboczy = $table;
    $roboczy = str_replace('%nr',$line,$roboczy);
    $roboczy = str_replace('%usluga',strtr($row['comment'],$trans),$roboczy);
    $roboczy = str_replace('%okres',strtr(strftime("01-%b-%Y",$row['time']).' - '.strftime("%d-%b-%Y",mktime(0,0,0,date("m",$row['time'])+1,0,date("Y",$row['time']))),$trans),$roboczy);
    $roboczy = str_replace('%netto',sprintf("%1.2f",$row['value']),$roboczy);
    $roboczy = str_replace('%wvat',sprintf("%1.2f",$row['value']*0.07),$roboczy);
    $roboczy = str_replace('%brutto',sprintf("%1.2f",$row['value']*1.07),$roboczy);
    $line++;
    $sumnetto=$sumnetto+$row['value'];
    echo $roboczy;
}

$body2 = str_replace('%sumnetto',sprintf("%1.2f",$sumnetto),$body2);
$body2 = str_replace('%sumwvat',sprintf("%1.2f",$sumnetto*0.07),$body2);
$body2 = str_replace('%sumbrutto',sprintf("%1.2f",$sumnetto*1.07),$body2);
$kesz=explode(".",sprintf("%1.2f",$sumnetto*1.07));
if ($kesz[1]>0) {
    $body2 = str_replace('%slownie',strtr(slownie($kesz[0])." z³ ".$kesz[1]." gr",$trans),$body2);
} else {
    $body2 = str_replace('%slownie',strtr(slownie($kesz[0])." z³",$trans),$body2);
}			    


echo $body2;
echo $tail;

/*
 * $Log$
 * Revision 1.7  2003/09/21 18:07:47  lexx
 * - netdev
 *
 * Revision 1.6  2003/08/18 16:52:19  lukasz
 * - added CVS Log tags
 *
 */
?>