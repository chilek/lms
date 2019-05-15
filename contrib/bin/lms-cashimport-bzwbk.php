<?php

/*

  * Skrypt importu raportów płatności masowych z banku BZWBK do LMS
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

//załóż dodatkowy katalog o nazwie "files" do którego skrypt zapisywał będzie raporty
//wpisz login i hasło do systemu płatności masowych banku

$ch=curl_init();
$params="username=nazwauzytkownika&password=haslouzytkownika";
$user_agent = "Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0)";

// wpisz login, hasło, nazwe bazy oraz adres serwra baz danych
$db_user='lms';
$db_pass='password';
$db_name='lms';
$db_host='localhost';

mysql_connect($db_host, $db_user, $db_pass);
mysql_select_db($db_name);


function get_save_file($file_url, $filename)
{
    $ch=curl_init();
    curl_setopt($ch, CURLOPT_URL, $file_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_COOKIEFILE, "cookie/ciastko");
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);
    $data_rows=curl_exec($ch);
    $f=fopen("files/".$filename, "w");
    fputs($f, $data_rows);
    fclose($f);
    curl_close($ch);
    return $data_rows;
}

function inc_to_lms($data_rows)
{
 
    $lines=explode("\n", $data_rows);
    $KKSGW=explode("|", $lines[0]);
    for ($i=1; $i<count($lines)-2; $i++) {
        $line=explode("|", $lines[$i]);

 //wpis do lmsa

        $data=mktime(0, 0, 0, substr($line[1], 2, 2), substr($line[1], 0, 2), substr($line[1], -4));
        
        $kto=$line[3];
        $kwota=$line[2];
        $opis=substr($line[6], 0, -2);
        $id=substr($line[5], -12);
        $id=1*$id;
        $hash=md5($line[0].$KKSGW);
        $kto=addslashes(iconv("ISO-8859-2", "UTF-8", $kto));
        $opis=addslashes(iconv("ISO-8859-2", "UTF-8", $opis));
        $rs=mysql_query("Select id from cashimport where Hash='".$hash."'");
        if (mysql_num_rows($rs)==0) {
            mysql_query("Insert into cashimport (Date,Value,Customer,Description,CustomerId,Hash) values ('$data','$kwota','$kto','$opis','$id','$hash')");
      //          echo ("Insert into cashimport (Date,Value,Customer,Description,CustomerId,Hash) values ('$data','$kwota','$kto','$opis','$id','$hash')")."\n";
        } else {
            echo "Pomijam wpis, bo juz taki istnieje.\n";
        }

     
 //koniec wpisu do lmsa;
    }
}


curl_setopt($ch, CURLOPT_URL, "https://www.centrum24.pl/rapkm/loginAction.do");
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_COOKIEJAR, "cookie/ciastko");
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);

$res=curl_exec($ch);
//echo $res;
curl_close($ch);


$url="https://www.centrum24.pl/rapkm/dostepne_pliki.do";

$params="action=lista";

$ch=curl_init();

curl_setopt($ch, CURLOPT_URL, $url."?".$params);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_COOKIEFILE, "cookie/ciastko");
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);

$files=curl_exec($ch);

//echo $files;

curl_close($ch);

$last=file("files/!lastfile.txt");


$ofs=0;
$start=strpos($files, "dostepne_pliki.do?", $ofs);
$end=strpos($files, ".txt\"", $ofs);
$first=true;

while ($start!==false) {
    $file_url=substr($files, $start, $end-$start+4);
    $filename=substr($file_url, strpos($file_url, "nazwa_pliku=")+12);

    if ($last[0]==$filename) {
        break;
    }

    if ($first) {
         $first=false;
         $f=fopen("files/!lastfile.txt", "w");
         fputs($f, $filename);
         fclose($f);
    }

//echo $filename." - URL $file_url \n";

//pobieranie pliku
    inc_to_lms(get_save_file("https://www.centrum24.pl/rapkm/".$file_url, $filename));
//koniec pobierania pliku


    $ofs=$end+5;
    $start=strpos($files, "dostepne_pliki.do?", $ofs);
    $end=strpos($files, ".txt\"", $ofs);
}
