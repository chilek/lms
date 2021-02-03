<?php

/*

 * skrypt importu raportów płatności masowych z banku BPH do LMS
 *
 *  (C) Copyright Webvisor Sp. z o.o.
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
*/

//skrypt wymaga dodatkowego katalogu o nazwie "files" do którego zapisywać będzie raporty

// Wpisz tutaj login i hasło do systemu bankowego
$ch=curl_init();
$params="uzytkownik=loginzbanku&haslo=haslozbanku";
$user_agent = "Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0)";

// wpisz wpisz tutaj login hasło nazwę bazy danych oraz adres serwera bazy danych
 
$db_user='lms';
$db_pass='password';
$db_name='lms';
$db_host='localhost';

mysql_connect($db_host, $db_user, $db_pass);
mysql_select_db($db_name);

function explodeX($row, $sep, $ign)
{
    $cFieldPos=0;
    $openCite=false;
    $row=str_replace("\r", "", $row);
    $row=str_replace("\n", $sep, $row);
    for ($i=0; $i<strlen($row); $i++) {
        if (substr($row, $i, 1)==$ign) {
            $openCite=!$openCite;
        }
    
        if (substr($row, $i, 1)==$sep && !$openCite) {
            $rows[]=substr($row, $cFieldPos, $i-$cFieldPos);
            $cFieldPos=$i+1;
        }
    }
    return $rows;
}

curl_setopt($ch, CURLOPT_URL, "https://www.trans.bph.pl/");
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_COOKIEJAR, "cookie/ciastko");
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);

curl_exec($ch);
curl_close($ch);

$url="https://www.trans.bph.pl/";
$params="a=browse";

$ch=curl_init();

curl_setopt($ch, CURLOPT_URL, $url."?".$params);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_COOKIEFILE, "cookie/ciastko");
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);

$files=curl_exec($ch);

curl_close($ch);

$ofs=0;

$last=file("files/!lastfile.txt");

$start_import=0;
if (trim($last[0])=="") {
    $start_import=1;
}

$start=strpos($files, "file=wns", $ofs);
while ($start!==false) {
    $end=strpos($files, ".txt", $start);
    $ofs=$end+4;
    $fn=substr($files, $start+5, $end-$start+4-5);

    if (!$start_import && $_GET['forcefile']!=$fn) {
        echo "Pomijam ".$fn."\n";
        if (trim($fn)==trim($last[0])) {
            $start_import=1;
        }
        $start=strpos($files, "file=wns", $ofs);
        continue;
    }
    echo "Odczytuje ".$fn."\n";


    $params="a=browse&changeto=&s=&file=".$fn;

    $ch=curl_init();

    curl_setopt($ch, CURLOPT_URL, $url."?".$params);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_COOKIEFILE, "cookie/ciastko");
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);

    $wyn=curl_exec($ch);

    $f=fopen("files/".$fn, "w");
    fputs($f, $wyn);
    fclose($f);

    if ($_GET['forcefile']!=$fn) {
        $f=fopen("files/!lastfile.txt", "w");
        fputs($f, $fn);
        fclose($f);
    }

    $if=file("files/".$fn);

    for ($i=0; $i<count($if); $i++) {
        $fields=explodeX($if[$i], ",", "");
        if ($fields[0]=="02") {
            $data=mktime(0, 0, 0, substr($fields[5], 4, 2), substr($fields[5], 6, 2), substr($fields[5], 0, 4));

            $kto=$fields[8];
            $kwota=number_format($fields[4]/100, 2, ".", "");
            $opis=$fields[6];
            $tid=$fields[7];
            $id=$fields[3];
            $hash=md5($data.$kwota.$kto.$opis.$id.$tid);
            $kto=addslashes(iconv("ISO-8859-2", "UTF-8", $kto));
            $opis=addslashes(iconv("ISO-8859-2", "UTF-8", $opis));
            $rs=mysql_query("Select id from cashimport where Hash='".$hash."'");
            if (mysql_num_rows($rs)==0) {
                mysql_query("Insert into cashimport (Date,Value,Customer,Description,CustomerId,Hash) values ('$data','$kwota','$kto','$opis','$id','$hash')");
            } else {
                echo "Pomijam wpis, bo juz taki istnieje.\n";
            }
        }
    }

    curl_close($ch);

    $start=strpos($files, "file=wns", $ofs);
}
