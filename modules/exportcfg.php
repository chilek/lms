<?php

/*
 * LMS version 1.11-cvs
 *
 *  (C) Copyright 2001-2012 LMS Developers
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

/* variables used in *record options below

%CLARION_DATE - data w formacie Clarion tj. ilo�� dni od 28.12.1800
%DATE - data w formacie okre�lonym zmienn� $date_format
%NUMBER - ca�y numer dokumentu
%N - numer dokumentu (liczba)
%UID - ID u�ytkownika
%UID4 - ID u�ytkownika w formacie '%04d', czyli np. 0016
%CUSTOMER - nazwa/nazwisko i imi�
%CID - ID klienta
%CID4 - ID klienta w formacie '%04d', czyli np. 0016
%ADDRESS - adres klienta: ulica
%ZIP - kod pocztowy
%CITY - miasto
%TEN - nip 
%I - kolejny numer wiersza (rekordu) w pliku exportu
%TYPE - typ operacji: 3-KP, 4-KW, 5-przelew-wp�ata, 6-przelew-wyp�ata
%CASHREG - nazwa rejestru kasowego
%DESC - opis pozycji dokumentu/operacji (nie dotyczy faktur)
%VALUE - kwota operacji (brutto)
%ABSVALUE - kwota operacji bez znaku (warto�� bezwzgl�dna)
%DEADLINE - termin p�atno�ci faktury w formacie $date_format

*/

/***** main settings *****/

$encoding = 'UTF-8'; // kodowanie pliku exportu
$date_format = 'Y/m/d'; // format daty patrz: http://php.net/manual/en/function.date.php
$endln = "\n"; // end of line (unix - "\n", windows - "\r\n" )

/**** invoices export settings *****/

$inv_filename = 'export.txt';
$cnote_type = '1'; // typ dokumentu: korekta (zmienna %TYPE)
$invoice_type = '0'; // typ dokumentu: faktura (zmienna %TYPE)

/* Zmienne dotycz�ce tylko exportu faktur 

%NETTO - warto�� netto (suma)
%VAT - warto�� podatku (suma)

%VATPx - stawka podatku 
%NETTOx - warto�� netto
%VATx - warto�� podatku

%TAXEDx - czy dana stawka podatkowa jest zwolniona, w sumie to tylko jedna kombinacja 
	taxed=1 i VATP=0.00 ma sens, no ale skoro juz jestesmy porzadni to niech tak bedzie

UWAGA: x zast�pujemy cyfr� od 1 do 8, kt�ra oznacza kolejn� stawk�, 
       np. %VATP1, %VATP2 itd.

*/

// dla wygody (d�ugi) rekord mo�e by� tablic� z dowoln� liczb� element�w
$inv_record[0] = '%I,"%DATE","%NUMBER",%TYPE,"%DEADLINE",%VALUE,%NETTO,%VAT,';
$inv_record[1] = '"%CID","%CUSTOMER","%ADDRESS","%ZIP","%CITY","%TEN"';

/***** cash documents export settings *****/

$cash_filename = 'export.txt';
$cash_in_type = '3'; // typ dokumentu (zmienna %TYPE)
$cash_out_type = '4'; // typ dokumentu (zmienna %TYPE)
$default_customer = ''; // domy�lna warto�� zmiennej %CUSTOMER gdy pole jest puste (np. przeniesienie �rodk�w)

$cash_record = '%I,%DATE,%ABSVALUE,"%NUMBER","%UID4",%TYPE,"%CASHREG","%CID4","%CUSTOMER","%DESC"';

?>
