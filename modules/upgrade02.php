<?php

/*
 * LMS version 1.5-cvs
 *
 *  (C) Copyright 2001-2004 LMS Developers
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

$LMS->DB->BeginTrans();

foreach($LMS->CONFIG['phpui'] as $key => $val)
{
    switch($key)
    {
	case 'allow_from':
	    $desc = 'Lista sieci i adres�w IP kt�re maj� dost�p do LMS. Je�eli puste, ka�dy adres IP ma dost�p do LMS\'a, je�eli wpiszemy tutaj list� adres�w b�d� klas adresowych, LMS odrzuci b��dem HTTP 403 ka�dego niechcianego u�ytkownika.';
	    break;
	case 'timeout':
	    $desc = 'Timeout sesji www. Po tym czasie (w sekudnach) u�ytkownik zostanie wylogowany je�eli nie podejmie �adnej akcji. Domy�lnie: 600 sekund. !!!UWAGA!!! NIE MA MO�LIWO�CI USTAWIENIA BRAKU TIMEOUTU. JE�ELI USTAWISZ T� WARTO�� NA ZERO, NIE B�DZIESZ M�G� KORZYSTA� Z LMS!!! No i oczywi�cie, "it\'s not a bug, it\'s a feature"';
	    break;
	case 'userlist_pagelimit':
	    $desc = 'Limit wy�wietlanych pozycji na stronie w li�cie u�ytkownik�w. Domy�lnie: brak limitu';
	    break;
	case 'nodelist_pagelimit':
	    $desc = 'Limit wy�wietlanych pozycji na stronie w li�cie komputer�w. Domy�lnie: brak limitu';
	    break;
	case 'balancelist_pagelimit':
	    $desc = 'Limit wy�wietlanych pozycji na stronie w li�cie operacji finansowych. Domy�lnie: 100';
	    break;
	case 'configlist_pagelimit':
	    $desc = 'Limit wy�wietlanych pozycji na stronie w li�cie opcji konfiguracyjnych. Domy�lnie: 100';
	    break;
	case 'invoicelist_pagelimit':
	    $desc = 'Limit wy�wietlanych pozycji na stronie w li�cie faktury. Domy�lnie: 100';
	    break;
	case 'ticketlist_pagelimit':
	    $desc = 'Limit wy�wietlanych pozycji na stronie w li�cie zg�osze�. Domy�lnie: 100';
	    break;
	case 'accountlist_pagelimit':
	    $desc = 'Limit wy�wietlanych pozycji na stronie w li�cie kont. Domy�lnie: 100';
	    break;
	case 'domainlist_pagelimit':
	    $desc = 'Limit wy�wietlanych pozycji na stronie w li�cie domen. Domy�lnie: 100';
	    break;
	case 'aliaslist_pagelimit':
	    $desc = 'Limit wy�wietlanych pozycji na stronie w li�cie alias�w. Domy�lnie: 100';
	    break;
	case 'networkhosts_pagelimit':
	    $desc = 'Limit wy�wietlanych komputer�w na stronie w informacjach o sieci. Wpisanie zera spowoduje pomijanie tych informacji (i przyspieszenie wy�wietlenia strony). Domy�lnie: 256';
	    break;
	case 'force_ssl':
	    $desc = 'Ustawinie tej zmiennej na 1 spowoduje �e LMS b�dzie wymusza� po��czenie SSL powoduj�c redirect do \'https://\'.$_SERVER[HTTP_HOST].$_SERVER[REQUEST_URI]; przy ka�dej pr�bie dost�pu bez SSL. Domy�lnie: wy��czone';
	    break;
	case 'reload_type':
	    $desc = 'Typ reloadu. Dozwolone warto�ci: exec - wywo�ywanie jakiej� komendy (najcz�ciej co� przez sudo, jaki� skrypt lub co�, konfigurowalny poni�ej), sql  - zrobienie wpis�w w SQL\'u (te� mo�na ustawi� konkretne query SQL)';
	    break;
	case 'reload_execcmd':
	    $desc = 'Komenda do wykonania podczas reloadu je�eli reload_type jest ustawione na "exec". Domy�lnie /bin/true. String ten puszczany do komendy system() wi�c pronuje rozwag� i pomy�lenie co si� robi i jak :) Generalnie �redniki powinny by� parsowane przez bash\'a, ale z paru wzgl�d�w LMS sam dzieli poni�szy ci�g pod wzgl�dem �rednik�w i wykonuje komendy pojedy�czo';
	    break;
	case 'reload_sqlquery':
	    $desc = 'Query SQL\'a. Jak kto� bardzo chce, to mo�na u�y� '%TIME%' jako podstawki pod aktualny timestamp unixowy. UWAGA! Znak �rednika (czyli ;) jest traktowany jako separator kwerend. Tj. oddzielaj�c znakiem �rednika mo�esz wpisa� kilka komend SQL';
	    break;
	case 'allow_mac_sharing':
	    $desc = 'Przyzwolenie na *dodawanie* rekord�w komputer�w z adresami MAC ju� istniej�cymi (po polsku: nie sprawdza czy jaki� inny komputer posiada taki adres MAC). Domy�lnie: wy��czone';
	    break;
	case 'default_zip':
	case 'default_city':
	case 'default_address':
	    $desc = 'Domy�lny adres przy dodawaniu u�ytkownik�w';
	    break;
	case 'lastonline_limit':
	    $desc = 'Czas w sekundach, po kt�rym host zostanie uznany za nieaktywny. Najlepiej ustawi� na warto�� odpowiadaj�c� cz�stotliwo�ci uruchamiania skryptu badaj�cego aktywno�� komputer�w (lms-fping). Domy�lnie: 600 sekund';
	    break;
	case 'use_current_payday':
	    $desc = 'Okre�la, czy ma by� u�yta aktualna data jako dzie� zap�aty podczas przypisywania zobowi�za� u�ytkownikom. Domy�lnie: wy��czone';
	    break;
	case 'smarty_debug':
	    $desc = 'W��czenie konsoli debugowej Smartyego, przydatne do �ledzenia warto�ci przekazywanych z PHP do Smartyego. Domy�lnie: wy��czone';
	    break;
	case 'debug_email':
	    $desc = 'Adres e-mail do debugowania - pod ten email b�d� sz�y maile wysy�ane z sekcji "Mailing" LMSa, zamiast do w�a�ciwych u�ytkownik�w';
	    break;
	case 'arpd_servers':
	    $desc = 'Lista serwer�w lms-arpd do zczytywania MAC\'adres�w z odleg�ych sieci. Lista ta powinna zawiera� wpisy w postaci adresip[:port] oddzielone spacjami, na przyk�ad: arpd_servers = 192.168.1.1 192.168.2.1:2029';
	    break;
	case 'helpdesk_backend_mode':
	    $desc = 'W��czenie tej opcji spowoduje, �e wszystkie wiadomo�ci w systemie helpdesk (opr�cz tych skierowanych do zg�aszaj�cego) b�d� wysy�ane do serwera pocztowego na adres odpowiedniej kolejki. Na serwerze tym powinien by� uruchomiony skrypt lms-rtparser, kt�ry zajmie si� zapisem wiadomo�ci do bazy danych. Domy�lnie: wy��czona';
	    break;
	case 'contract_template':
	    $desc = 'Nazwa w�asnego szablonu umowy dla u�ytkownika, kt�ry nale�y umie�ci� w katalogu templates. Mo�na tak�e zmodyfikowa� istniej�cy defaultowy plik contract.html. Domy�lnie: "contract.html"';
	    break;
	case 'to_words_short_version':
	    $desc = 'Okre�la spos�b reprezentacji s�ownej kwot (na fakturach). Dla warto�ci "1" kwota 123,15 b�dzie mia�a rozwini�cie s�owne "jed dwa trz 15/100". Domy�lnie: 0';
	    break;
	default:
	    $desc = 'Nieznana opcja. Brak opisu';
	    break;
    }

    $DB->Execute('INSERT INTO uiconfig(section, var, value, description) VALUES(?,?,?,?)',
		array('phpui', $key, $val, $desc)
		);
}

/*
foreach($LMS->CONFIG['directories'] as $key => $val)
{
    switch($key)
    {
	case 'sys_dir':
	    $desc = 'Katalog systemowy. Jest to miejsce gdzie jest ca�a zawarto�� UI LMS\'a, czyli index.php, grafiki, templejty i reszta. Domy�lnie index.php stara si� sam odnale�� w filesystemie u�ywaj�c getcwd(), ale lepiej by by�o gdyby mu powiedzie� gdzie jest';
	    break;
	case 'modules_dir':
	    $desc = 'Katalog z "modu�ami" LMS\'a - kawa�kami kodu kt�re szumnie kto� (czyli Baseciq) nazwa� modu�ami. Domy�lnie jest to podkatalog modules w sys_dir';
	    break;
	case 'lib_dir':
	    $desc = 'Katalog z "bibliotekami" LMS\'a. Czyli zawarto�� katalogu lib. Domy�lnie to podkatalog lib w sys_dir';
	    break;
	case 'backup_dir':
	    $desc = 'Katalog z backupami SQL\'owymi - miejsce gdzie LMS zapisuje dumpy z bazy. Domy�lnie jest to podkatalog "backups". Naprawd� dobrze by by�o go przenie�� poza miejsce osi�galne przez przegl�dark�';
	    break;
	case 'config_templates_dir':
	    $desc = 'Katalog z templejtami plik�w konfiguracyjnych. Nieu�ywana';
	    break;
	case 'smarty_dir':
	    $desc = 'Katalog z bibliotek� Smarty - domy�lnie podkatalog Smarty w lib_dir';
	    break;
	case 'smarty_compile_dir':
	    $desc = 'Katalog kompilacji Smartyego. Miejsce gdzie Smarty psuje nasze templejty. Domy�lnie to templates_c w katalogu sysdir';
	    break;
	case 'smarty_templates_dir':
	    $desc = 'Katalog z templejtami kt�rymi karmimy Smartiego. Domy�lnie to podkatalog templates z sys_dir';
	    break;
	default:
	    $desc = 'Nieznana opcja. Brak opisu';
	    break;
    }    
    
    $DB->Execute('INSERT INTO uiconfig(section, var, value, description) VALUES(?,?,?,?)',
		array('directories', $key, $val, $desc)
		);
}
*/

foreach($LMS->CONFIG['invoices'] as $key => $val)
{
    switch($key)
    {
	case 'header':
	    $desc = 'Nag��wek, a w�a�ciwie dane sprzedaj�cego. Jako znak nowej linii nale�y poda� "\n", np: header = SuperNet ISP\n12-234 W�chock\nWiosenna 52\n0 49 3883838\n\nksiegowosc@supernet.pl\n\nNIP: 123-123-12-23';
	    break;
	case 'footer':
	    $desc = 'Stopka. Ma�ym drukiem na dole strony b�dzie, np: footer = Nasz Bank: Sratytaty, nazwa r-ku: SNETISP, nr r-ku: 828823917293871928371\nBiuro obs�ug klienta 329 29 29. Dzia� windykacji: 329 28 28\nSprz�taczki: 329 29 28';
	    break;
	case 'default_author':
	    $desc = 'Domy�lna osoba kt�ra wystawi�a faktur�';
	    break;
	case 'number_template':
	    $desc = 'Szablon numeru faktury. Domy�lnie: numer/LMS/rok, czyli %N/LMS/%Y. Dopuszcza si� zmienne: %N - numer kolejny w roku, %M - miesi�c wystawienia, %Y - rok wystawienia';
	    break;
	case 'cplace':
	    $desc = 'Miejsce wystawienia faktury';
	    break;
	case 'template_file':
	    $desc = 'Plik templejtu faktury. Domy�lnie: "invoice.html". Powinno to by� umieszczone w katalogu templates';
	    break;
	case 'content_type':
	    $desc = 'Content-type dla faktury. Je�eli wpiszecie tutaj "application/octet-stream", to przegl�darka zechce wam wys�a� plik do zapisania na dysku, zamiast go wy�wietli�. Przydatne je�eli u�yjecie w�asnego template\'a kt�ry wygeneruje np. rtf\'a czy xls\'a. Domy�lnie: "text/html; charset=iso-8859-2"';
	    break;
	case 'attachment_name':
	    $desc = 'Nazwa pliku, jako kt�ry ma zosta� zapisany gotowy wydruk faktury UWAGA: Ustawienie attachment_name wraz z zostawieniem domy�lnego content_type spowoduje (w przypadku MSIE) wy�wietlenie faktur, oraz prompt do zapisania na dysku + w ramach promocji crash misia (6.0SP1 na WXP)';
	    break;
	default:
	    $desc = 'Nieznana opcja. Brak opisu';
	    break;
    }

    $DB->Execute('INSERT INTO uiconfig(section, var, value, description) VALUES(?,?,?,?)',
		array('invoices', $key, $val, $desc)
		);
}

$LMS->DB->CommitTrans();

header('Location: ?m=configlist');

?>
