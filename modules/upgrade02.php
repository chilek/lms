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
	    $desc = 'Lista sieci i adresów IP które maj± dostêp do LMS. Je¿eli puste, ka¿dy adres IP ma dostêp do LMS\'a, je¿eli wpiszemy tutaj listê adresów b±d¼ klas adresowych, LMS odrzuci b³êdem HTTP 403 ka¿dego niechcianego u¿ytkownika.';
	    break;
	case 'timeout':
	    $desc = 'Timeout sesji www. Po tym czasie (w sekudnach) u¿ytkownik zostanie wylogowany je¿eli nie podejmie ¿adnej akcji. Domy¶lnie: 600 sekund. !!!UWAGA!!! NIE MA MO¯LIWO¦CI USTAWIENIA BRAKU TIMEOUTU. JE¯ELI USTAWISZ T¡ WARTO¦Æ NA ZERO, NIE BÊDZIESZ MÓG£ KORZYSTAÆ Z LMS!!! No i oczywi¶cie, "it\'s not a bug, it\'s a feature"';
	    break;
	case 'userlist_pagelimit':
	    $desc = 'Limit wy¶wietlanych pozycji na stronie w li¶cie u¿ytkowników. Domy¶lnie: brak limitu';
	    break;
	case 'nodelist_pagelimit':
	    $desc = 'Limit wy¶wietlanych pozycji na stronie w li¶cie komputerów. Domy¶lnie: brak limitu';
	    break;
	case 'balancelist_pagelimit':
	    $desc = 'Limit wy¶wietlanych pozycji na stronie w li¶cie operacji finansowych. Domy¶lnie: 100';
	    break;
	case 'configlist_pagelimit':
	    $desc = 'Limit wy¶wietlanych pozycji na stronie w li¶cie opcji konfiguracyjnych. Domy¶lnie: 100';
	    break;
	case 'invoicelist_pagelimit':
	    $desc = 'Limit wy¶wietlanych pozycji na stronie w li¶cie faktury. Domy¶lnie: 100';
	    break;
	case 'ticketlist_pagelimit':
	    $desc = 'Limit wy¶wietlanych pozycji na stronie w li¶cie zg³oszeñ. Domy¶lnie: 100';
	    break;
	case 'accountlist_pagelimit':
	    $desc = 'Limit wy¶wietlanych pozycji na stronie w li¶cie kont. Domy¶lnie: 100';
	    break;
	case 'domainlist_pagelimit':
	    $desc = 'Limit wy¶wietlanych pozycji na stronie w li¶cie domen. Domy¶lnie: 100';
	    break;
	case 'aliaslist_pagelimit':
	    $desc = 'Limit wy¶wietlanych pozycji na stronie w li¶cie aliasów. Domy¶lnie: 100';
	    break;
	case 'networkhosts_pagelimit':
	    $desc = 'Limit wy¶wietlanych komputerów na stronie w informacjach o sieci. Wpisanie zera spowoduje pomijanie tych informacji (i przyspieszenie wy¶wietlenia strony). Domy¶lnie: 256';
	    break;
	case 'force_ssl':
	    $desc = 'Ustawinie tej zmiennej na 1 spowoduje ¿e LMS bêdzie wymusza³ po³±czenie SSL powoduj±c redirect do \'https://\'.$_SERVER[HTTP_HOST].$_SERVER[REQUEST_URI]; przy ka¿dej próbie dostêpu bez SSL. Domy¶lnie: wy³±czone';
	    break;
	case 'reload_type':
	    $desc = 'Typ reloadu. Dozwolone warto¶ci: exec - wywo³ywanie jakiej¶ komendy (najczê¶ciej co¶ przez sudo, jaki¶ skrypt lub co¶, konfigurowalny poni¿ej), sql  - zrobienie wpisów w SQL\'u (te¿ mo¿na ustawiæ konkretne query SQL)';
	    break;
	case 'reload_execcmd':
	    $desc = 'Komenda do wykonania podczas reloadu je¿eli reload_type jest ustawione na "exec". Domy¶lnie /bin/true. String ten puszczany do komendy system() wiêc pronuje rozwagê i pomy¶lenie co siê robi i jak :) Generalnie ¶redniki powinny byæ parsowane przez bash\'a, ale z paru wzglêdów LMS sam dzieli poni¿szy ci±g pod wzglêdem ¶redników i wykonuje komendy pojedyñczo';
	    break;
	case 'reload_sqlquery':
	    $desc = 'Query SQL\'a. Jak kto¶ bardzo chce, to mo¿na u¿yæ '%TIME%' jako podstawki pod aktualny timestamp unixowy. UWAGA! Znak ¶rednika (czyli ;) jest traktowany jako separator kwerend. Tj. oddzielaj±c znakiem ¶rednika mo¿esz wpisaæ kilka komend SQL';
	    break;
	case 'allow_mac_sharing':
	    $desc = 'Przyzwolenie na *dodawanie* rekordów komputerów z adresami MAC ju¿ istniej±cymi (po polsku: nie sprawdza czy jaki¶ inny komputer posiada taki adres MAC). Domy¶lnie: wy³±czone';
	    break;
	case 'default_zip':
	case 'default_city':
	case 'default_address':
	    $desc = 'Domy¶lny adres przy dodawaniu u¿ytkowników';
	    break;
	case 'lastonline_limit':
	    $desc = 'Czas w sekundach, po którym host zostanie uznany za nieaktywny. Najlepiej ustawiæ na warto¶æ odpowiadaj±c± czêstotliwo¶ci uruchamiania skryptu badaj±cego aktywno¶æ komputerów (lms-fping). Domy¶lnie: 600 sekund';
	    break;
	case 'use_current_payday':
	    $desc = 'Okre¶la, czy ma byæ u¿yta aktualna data jako dzieñ zap³aty podczas przypisywania zobowi±zañ u¿ytkownikom. Domy¶lnie: wy³±czone';
	    break;
	case 'smarty_debug':
	    $desc = 'W³±czenie konsoli debugowej Smartyego, przydatne do ¶ledzenia warto¶ci przekazywanych z PHP do Smartyego. Domy¶lnie: wy³±czone';
	    break;
	case 'debug_email':
	    $desc = 'Adres e-mail do debugowania - pod ten email bêd± sz³y maile wysy³ane z sekcji "Mailing" LMSa, zamiast do w³a¶ciwych u¿ytkowników';
	    break;
	case 'arpd_servers':
	    $desc = 'Lista serwerów lms-arpd do zczytywania MAC\'adresów z odleg³ych sieci. Lista ta powinna zawieraæ wpisy w postaci adresip[:port] oddzielone spacjami, na przyk³ad: arpd_servers = 192.168.1.1 192.168.2.1:2029';
	    break;
	case 'helpdesk_backend_mode':
	    $desc = 'W³±czenie tej opcji spowoduje, ¿e wszystkie wiadomo¶ci w systemie helpdesk (oprócz tych skierowanych do zg³aszaj±cego) bêd± wysy³ane do serwera pocztowego na adres odpowiedniej kolejki. Na serwerze tym powinien byæ uruchomiony skrypt lms-rtparser, który zajmie siê zapisem wiadomo¶ci do bazy danych. Domy¶lnie: wy³±czona';
	    break;
	case 'contract_template':
	    $desc = 'Nazwa w³asnego szablonu umowy dla u¿ytkownika, który nale¿y umie¶ciæ w katalogu templates. Mo¿na tak¿e zmodyfikowaæ istniej±cy defaultowy plik contract.html. Domy¶lnie: "contract.html"';
	    break;
	case 'to_words_short_version':
	    $desc = 'Okre¶la sposób reprezentacji s³ownej kwot (na fakturach). Dla warto¶ci "1" kwota 123,15 bêdzie mia³a rozwiniêcie s³owne "jed dwa trz 15/100". Domy¶lnie: 0';
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
	    $desc = 'Katalog systemowy. Jest to miejsce gdzie jest ca³a zawarto¶æ UI LMS\'a, czyli index.php, grafiki, templejty i reszta. Domy¶lnie index.php stara siê sam odnale¼æ w filesystemie u¿ywaj±c getcwd(), ale lepiej by by³o gdyby mu powiedzieæ gdzie jest';
	    break;
	case 'modules_dir':
	    $desc = 'Katalog z "modu³ami" LMS\'a - kawa³kami kodu które szumnie kto¶ (czyli Baseciq) nazwa³ modu³ami. Domy¶lnie jest to podkatalog modules w sys_dir';
	    break;
	case 'lib_dir':
	    $desc = 'Katalog z "bibliotekami" LMS\'a. Czyli zawarto¶æ katalogu lib. Domy¶lnie to podkatalog lib w sys_dir';
	    break;
	case 'backup_dir':
	    $desc = 'Katalog z backupami SQL\'owymi - miejsce gdzie LMS zapisuje dumpy z bazy. Domy¶lnie jest to podkatalog "backups". Naprawdê dobrze by by³o go przenie¶æ poza miejsce osi±galne przez przegl±darkê';
	    break;
	case 'config_templates_dir':
	    $desc = 'Katalog z templejtami plików konfiguracyjnych. Nieu¿ywana';
	    break;
	case 'smarty_dir':
	    $desc = 'Katalog z bibliotek± Smarty - domy¶lnie podkatalog Smarty w lib_dir';
	    break;
	case 'smarty_compile_dir':
	    $desc = 'Katalog kompilacji Smartyego. Miejsce gdzie Smarty psuje nasze templejty. Domy¶lnie to templates_c w katalogu sysdir';
	    break;
	case 'smarty_templates_dir':
	    $desc = 'Katalog z templejtami którymi karmimy Smartiego. Domy¶lnie to podkatalog templates z sys_dir';
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
	    $desc = 'Nag³ówek, a w³a¶ciwie dane sprzedaj±cego. Jako znak nowej linii nale¿y podaæ "\n", np: header = SuperNet ISP\n12-234 W±chock\nWiosenna 52\n0 49 3883838\n\nksiegowosc@supernet.pl\n\nNIP: 123-123-12-23';
	    break;
	case 'footer':
	    $desc = 'Stopka. Ma³ym drukiem na dole strony bêdzie, np: footer = Nasz Bank: Sratytaty, nazwa r-ku: SNETISP, nr r-ku: 828823917293871928371\nBiuro obs³ug klienta 329 29 29. Dzia³ windykacji: 329 28 28\nSprz±taczki: 329 29 28';
	    break;
	case 'default_author':
	    $desc = 'Domy¶lna osoba która wystawi³a fakturê';
	    break;
	case 'number_template':
	    $desc = 'Szablon numeru faktury. Domy¶lnie: numer/LMS/rok, czyli %N/LMS/%Y. Dopuszcza siê zmienne: %N - numer kolejny w roku, %M - miesi±c wystawienia, %Y - rok wystawienia';
	    break;
	case 'cplace':
	    $desc = 'Miejsce wystawienia faktury';
	    break;
	case 'template_file':
	    $desc = 'Plik templejtu faktury. Domy¶lnie: "invoice.html". Powinno to byæ umieszczone w katalogu templates';
	    break;
	case 'content_type':
	    $desc = 'Content-type dla faktury. Je¿eli wpiszecie tutaj "application/octet-stream", to przegl±darka zechce wam wys³aæ plik do zapisania na dysku, zamiast go wy¶wietliæ. Przydatne je¿eli u¿yjecie w³asnego template\'a który wygeneruje np. rtf\'a czy xls\'a. Domy¶lnie: "text/html; charset=iso-8859-2"';
	    break;
	case 'attachment_name':
	    $desc = 'Nazwa pliku, jako który ma zostaæ zapisany gotowy wydruk faktury UWAGA: Ustawienie attachment_name wraz z zostawieniem domy¶lnego content_type spowoduje (w przypadku MSIE) wy¶wietlenie faktur, oraz prompt do zapisania na dysku + w ramach promocji crash misia (6.0SP1 na WXP)';
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
