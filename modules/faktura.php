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
    $szablon = str_replace('%slownie',strtr($LMS->NumberSpell($kesz[0])." z³ ".$kesz[1]." gr",$trans),$szablon);
} else {
    $szablon = str_replace('%slownie',strtr($LMS->NumberSpell($kesz[0])." z³",$trans),$szablon);
}			    
$szablon = str_replace('%konto',strtr($_CONFIG[finances]['bank']." ".$_CONFIG[finances]['account'],$trans),$szablon);
$szablon = str_replace('%wystawil',strtr($layout['logname'],$trans),$szablon);
$szablon = str_replace('%stopka',strtr($_CONFIG[finances]['footer'],$trans),$szablon);

while (list ($line_num, $line) = each ($szablon)) {
    echo $line;
}

/*
 * $Log$
 * Revision 1.9  2003/10/10 21:40:21  lexx
 * - NumberSpell
 *
 * Revision 1.8  2003/10/06 18:44:18  lexx
 * - stare i dzialajace
 *
 * Revision 1.6  2003/08/18 16:52:19  lukasz
 * - added CVS Log tags
 *
 */
?>

