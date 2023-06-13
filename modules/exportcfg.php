<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2022 LMS Developers
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

%CLARION_DATE - data w formacie Clarion tj. liczba dni od 28.12.1800
%DATE - data w formacie określonym zmienną $date_format
%NUMBER - cały numer dokumentu
%N - numer dokumentu (liczba)
%UID - ID użytkownika
%UID4 - ID użytkownika w formacie '%04d', czyli np. 0016
%CUSTOMER - nazwa/nazwisko i imię
%CID - ID klienta
%CID4 - ID klienta w formacie '%04d', czyli np. 0016
%ADDRESS - adres klienta: ulica
%ZIP - kod pocztowy
%CITY - miasto
%TEN - nip
%I - kolejny numer wiersza (rekordu) w pliku exportu
%TYPE - typ operacji: 3-KP, 4-KW, 5-przelew-wpłata, 6-przelew-wypłata
%CASHREG - nazwa rejestru kasowego
%DESC - opis pozycji dokumentu/operacji (nie dotyczy faktur)
%VALUE - kwota operacji (brutto)
%ABSVALUE - kwota operacji bez znaku (wartość bezwzględna)
%BALANCE - bieżące saldo klienta
%DEADLINE - termin płatności faktury w formacie $date_format

*/

/***** main settings *****/

$encoding = 'UTF-8'; // kodowanie pliku exportu
$date_format = 'Y/m/d'; // format daty patrz: http://php.net/manual/en/function.date.php
$endln = "\n"; // end of line (unix - "\n", windows - "\r\n" )

/**** invoices export settings *****/

$inv_filename = 'export.txt';
$cnote_type = '1'; // typ dokumentu: korekta (zmienna %TYPE)
$invoice_type = '0'; // typ dokumentu: faktura (zmienna %TYPE)

/* Zmienne dotyczące tylko exportu faktur

%NETTO - wartość netto (suma)
%VAT - wartość podatku (suma)

%VATPx - stawka podatku
%NETTOx - wartość netto
%VATx - wartość podatku

%TAXEDx - czy dana stawka podatkowa jest zwolniona, w sumie to tylko jedna kombinacja
    taxed=1 i VATP=0.00 ma sens, ale skoro już jesteśmy porządni to niech tak będzie

UWAGA: x zastępujemy cyfrą od 1 do 8, która oznacza kolejną stawkę,
       np. %VATP1, %VATP2 itd.

*/

// dla wygody (długi) rekord może być tablicą z dowolną liczbą elementów
$inv_record[0] = '%I,"%DATE","%NUMBER",%TYPE,"%DEADLINE",%VALUE,%NETTO,%VAT,';
$inv_record[1] = '"%CID","%CUSTOMER","%ADDRESS","%ZIP","%CITY","%TEN"';

/***** cash documents export settings *****/

$cash_filename = 'export.txt';
$cash_in_type = '3'; // typ dokumentu (zmienna %TYPE)
$cash_out_type = '4'; // typ dokumentu (zmienna %TYPE)
$default_customer = ''; // domyślna wartość zmiennej %CUSTOMER gdy pole jest puste (np. przeniesienie środków)

$cash_record = '%I,%DATE,%ABSVALUE,"%NUMBER","%UID4",%TYPE,"%CASHREG","%CID4","%CUSTOMER","%DESC"';
