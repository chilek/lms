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

if (! $szablon = @file($_CONFIG[finances]['template'])) { echo "Nie umiem odczytac szablonu, ustawiles wszystko w lms.ini?"; die; }; 

if(substr($_CONFIG[finances]['template'],-4)==".rtf"){  
    header('Content-type: text/rtf');
    header('Content-Disposition: attachment; filename=faktura_'.$userinfo['lastname']."_".$_GET[id]."_".date("Y",$cash['time']).'.rtf');
    $trans = array("¡"=>"\'a5","Æ"=>"\'c6","Ê"=>"\'ca","£"=>"\'a3","Ñ"=>"\'d1","Ó"=>"\'d3","¦"=>"\'8c","¯"=>"\'af","¬"=>"\'8f","±"=>"\'b9","æ"=>"\'e6","ê"=>"\'ea","³"=>"\'b3","ñ"=>"\'f1","ó"=>"\'f3","¶"=>"\'9c","¿"=>"\'bf","¼"=>"\'9f");
} else $trans = array();    

$szablon = str_replace('%nabywca',strtr($userinfo['username'],$trans),$szablon);
$szablon = str_replace('%nab_adres_cd',strtr($userinfo['zip']." ".$userinfo['city'],$trans),$szablon);
$szablon = str_replace('%nab_adres',strtr($userinfo['address'],$trans),$szablon);
$szablon = str_replace('%nip',strtr($userinfo['nip'],$trans),$szablon);
$szablon = str_replace('%nr_klienta',strtr($_GET[id],$trans),$szablon);
$szablon = str_replace('%sprzedawca',strtr($_CONFIG[finances]['name'],$trans),$szablon);
$szablon = str_replace('%sprzed_adres_cd',strtr($_CONFIG[finances]['zip']." ".$_CONFIG[finances]['city'],$trans),$szablon);
$szablon = str_replace('%sprzed_adres',strtr($_CONFIG[finances]['address'],$trans),$szablon);
$szablon = str_replace('%numer',strtr($_GET[id]."/".date("Y",$cash['time']),$trans),$szablon);
$szablon = str_replace('%data',strtr(date("d.m.Y"),$trans),$szablon);
$szablon = str_replace('%termin',strtr(date("d.m.Y",mktime(0,0,0,date("m"),date("d")+$_CONFIG[finances]['deadline'],date("Y"))),$trans),$szablon);
$szablon = str_replace('%dni',strtr($_CONFIG[finances]['deadline'],$trans),$szablon);
$szablon = str_replace('%usluga',strtr($cash['comment'],$trans),$szablon);
$szablon = str_replace('%od',strtr(strftime("01-%b-%Y",$cash['time']),$trans),$szablon);
$szablon = str_replace('%do',strtr(strftime("%d-%b-%Y",mktime(0,0,0,date("m",$cash['time'])+1,0,date("Y",$cash['time']))),$trans),$szablon);
$szablon = str_replace('%netto',strtr(sprintf("%1.2f",$cash['value']),$trans),$szablon);
$szablon = str_replace('%brutto',strtr(sprintf("%1.2f",$cash['value']*1.07),$trans),$szablon);
$szablon = str_replace('%vat',strtr(sprintf("%1.2f",$cash['value']*0.07),$trans),$szablon);
$kesz=explode(".",sprintf("%1.2f",$cash['value']*1.07));
if ($kesz[1]>0) {
    $szablon = str_replace('%slownie',strtr(slownie($kesz[0])." z³ ".$kesz[1]." gr",$trans),$szablon);
} else {
    $szablon = str_replace('%slownie',strtr(slownie($kesz[0])." z³",$trans),$szablon);
}			    
$szablon = str_replace('%konto',strtr($_CONFIG[finances]['bank']." ".$_CONFIG[finances]['account'],$trans),$szablon);
$szablon = str_replace('%wystawil',strtr($layout['logname'],$trans),$szablon);
$szablon = str_replace('%stopka',strtr($_CONFIG[finances]['footer'],$trans),$szablon);

while (list ($line_num, $line) = each ($szablon)) {
    echo $line;
}

/*
 * $Log$
 * Revision 1.6  2003/08/18 16:52:19  lukasz
 * - added CVS Log tags
 *
 */
?>