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
    $slow .=$z[bcdiv($liczba,100)*100]." ";
    $liczba =  bcmod($liczba,100);
 }
 if ($liczba>20) {
    $slow .=$z[bcdiv($liczba,10)*10]." ";
    $liczba =  bcmod($liczba,10);
 }
 if (($liczba>0) or (strlen($slow)<1)) {$slow .=$z[$liczba];}
 return $slow;
}

$userinfo=$LMS->GetUser($_GET[id]);

$szablon = file($_CONFIG[finances]['template']); 

$szablon = str_replace('%logo',$_CONFIG[finances]['logo'],$szablon);
$szablon = str_replace('%nabywca',$userinfo['username'],$szablon);
$szablon = str_replace('%nab_adres_cd',$userinfo['zip']." ".$userinfo['city'],$szablon);
$szablon = str_replace('%nab_adres',$userinfo['address'],$szablon);
$szablon = str_replace('%nip',$userinfo['nip'],$szablon);
$szablon = str_replace('%nr_klienta',$_GET[id],$szablon);
$szablon = str_replace('%sprzedawca',$_CONFIG[finances]['name'],$szablon);
$szablon = str_replace('%sprzed_adres_cd',$_CONFIG[finances]['zip']." ".$_CONFIG[finances]['city'],$szablon);
$szablon = str_replace('%sprzed_adres',$_CONFIG[finances]['address'],$szablon);
$szablon = str_replace('%numer',time(),$szablon);
$szablon = str_replace('%data',date("d.m.Y"),$szablon);
$szablon = str_replace('%termin',date("d.m.Y",mktime(0,0,0,date("m"),date("d")+$_CONFIG[finances]['deadline'],date("Y"))),$szablon);
$szablon = str_replace('%dni',$_CONFIG[finances]['deadline'],$szablon);
$szablon = str_replace('%usluga',$_CONFIG[finances]['service'],$szablon);
$szablon = str_replace('%od',strftime("01-%b-%Y"),$szablon);
$szablon = str_replace('%do',strftime("%d-%b-%Y",mktime(0,0,0,date("m")+1,0,date("Y"))),$szablon);
$szablon = str_replace('%netto',sprintf("%1.2f",-$userinfo['balance']),$szablon);
$szablon = str_replace('%brutto',sprintf("%1.2f",-$userinfo['balance']*1.07),$szablon);
$szablon = str_replace('%vat',sprintf("%1.2f",-$userinfo['balance']*0.07),$szablon);
$kesz=explode(".",sprintf("%1.2f",-$userinfo['balance']*1.07));
if ($kesz[1]>0) {
    $szablon = str_replace('%slownie',slownie($kesz[0])." z³ ".$kesz[1]." gr",$szablon);
} else {
    $szablon = str_replace('%slownie',slownie($kesz[0])." z³",$szablon);
}			    
$szablon = str_replace('%konto',$_CONFIG[finances]['bank']." ".$_CONFIG[finances]['account'],$szablon);
$szablon = str_replace('%wystawil',$layout['logname'],$szablon);
$szablon = str_replace('%stopka',$_CONFIG[finances]['footer'],$szablon);

while (list ($line_num, $line) = each ($szablon)) {
    echo $line;
}

?>
