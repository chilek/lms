LMS - LAN Management System 1.11-git

   [logo-small.png]

LMS Developers

   Copyright © 2001-2013 LMS Developers
     __________________________________________________________________

   Spis treści
   1. Wstęp

        1.1. Czym jest LMS
        1.2. Autorzy
        1.3. Licencja
        1.4. Informacje dodatkowe

   2. Instalacja i konfiguracja

        2.1. Wstęp
        2.2. Wymagania
        2.3. Instalacja LMS
        2.4. Lokalizacja
        2.5. Instalacja serwera baz danych
        2.6. Konfiguracja podstawowa
        2.7. Prawa dostępu
        2.8. Upgrade
        2.9. Dokumenty
        2.10. Baza podziału terytorialnego (TERYT)

   3. Interfejs Użytkownika (LMS-UI)

        3.1. Logowanie
        3.2. Administracja
        3.3. Klienci
        3.4. Komputery
        3.5. Osprzęt sieciowy
        3.6. Sieci IP
        3.7. Finanse
        3.8. Dokumenty
        3.9. Hosting
        3.10. Wiadomości
        3.11. Przeładowanie
        3.12. Statystyki
        3.13. Helpdesk
        3.14. Terminarz
        3.15. Konfiguracja

   4. Skrypty

        4.1. Instalacja
        4.2. Lista dostępnych skryptów
        4.3. Opis i konfiguracja

   5. Generator plików konfiguracyjnych (lms-mgc)

        5.1. Instalacja
        5.2. Konfiguracja
        5.3. Przykład zastosowania lms-mgc

   6. LMS Daemon

        6.1. Informacje podstawowe
        6.2. Moduły
        6.3. T-Script

   7. Dla dociekliwych

        7.1. Drzewo katalogów
        7.2. Struktura bazy danych
        7.3. Format pliku konfiguracyjnego
        7.4. Generowanie danych losowych
        7.5. Poziomy dostępu
        7.6. Ograniczenia

   8. Dodatki

        8.1. Moje konto
        8.2. Moje konto 2
        8.3. Panel SQL
        8.4. Ostrzeżenia + squid
        8.5. Antywirus

   9. Userpanel

        9.1. O programie
        9.2. Instalacja
        9.3. Konfiguracja
        9.4. Wygląd (style)
        9.5. Moduły

   10. FAQ

   Spis tabel
   2-1. Lista zmian zrywających zgodność z wcześniejszymi wydaniami
   4-1. Lista skryptów wykonywalnych
   6-1. Lista modułów demona lmsd
   7-1. Drzewo katalogów LMS

   Spis przykładów
   3-1. Domeny. Konfiguracja PowerDNS.
   3-2. Hosting. Konfiguracja proftpd.
   3-3. Konta. Konfiguracja serwera pocztowego (postfix+sasl+courier).
   3-4. Konta. Konfiguracja pure-ftpd.
   4-1. Lms-notify: Przykładowy wyciąg 10 ostatnich operacji kasowych
   4-2. Lms-notify: Przykład szablonu
   5-1. Lms-mgc: Przykład instancji
   6-1. Parser: Tworzenie pliku /etc/hosts
   6-2. Parser: Lista dłużników
   6-3. Parser: Opisy komputerów dla iptrafa.
   6-4. Parser: Plik "ethers" dla programu arp.
   6-5. Parser: Zamiennik modułu notify
   6-6. Parser: Statystyki.
   7-1. Format opcji konfiguracyjnych
     __________________________________________________________________

Rozdział 1. Wstęp

1.1. Czym jest LMS

   "LMS" jest zintegrowanym systemem zarządzania sieciami przeznaczonym
   dla różnej wielkości dostawców internetu (ISP).

   Oprogramowanie to stworzone w PHP, C i Perlu, współpracujące z różnymi
   bazami danych, składa się z przyjaznego interfejsu użytkownika
   (frontend) oraz programów instalowanych na serwerze dostępowym
   (backend) udostępniając następujące funkcjonalności:
     * zarządzanie dostępem do internetu (w tym kontrola przepływności i
       statystyki),
     * moduły finansowo-księgowe z fakturowaniem,
     * korespondencja seryjna i wiadomości administracyjne do klientów,
     * zarządzanie kontami i hostingiem,
     * ewidencja klientów i sprzętu (mapa sieci),
     * system obsługi zgłoszeń (helpdesk),
     * zarządzanie dowolnymi usługami,
     * zarządzanie czasem (terminarz),
     * panel administracyjny dla abonenta.

   Całość została wymyślona w ramach administracji ASK NetX i tam jest
   nieustannie rozwijana i poddawana testom.

   LMS nie zastąpi Ci umiejętności jakie powinien mieć administrator.
   Jeśli nie potrafisz wykonać tak prostych czynności jak instalacja czy
   konfiguracja, prawdopodobnie nie będziesz umiał dostroić LMS do swojego
   systemu. Tak więc bez znajomości systemów U*IX się nie obejdzie.
     __________________________________________________________________

1.2. Autorzy

1.2.1. LMS Developers

     * Kod PHP:

       Łukasz 'Baseciq' Mozer
       Michał 'DziQs' Zapalski
       Radosław 'Warden' Antoniuk
       Krzysztof 'hunter' Drewicz
       Marcin 'Lexx' Król
       Aleksander A.L.E.C Machniak
       Tomasz 'Chilek' Chiliński
       Konrad 'kondi' Rzentarzewski
       Grzegorz 'Ceho' Chwesewicz
     * Kod C:

       Aleksander 'A.L.E.C' Machniak
       Marcin 'Lexx' Król
       Tomasz 'Chilek' Chiliński
     * Kod Perl:

       Łukasz 'Baseciq' Mozer
       Michał 'DziQs' Zapalski
       Maciej 'agaran' Pijanka
       Krzysztof 'hunter' Drewicz
       Tomasz 'Chilek' Chiliński
       Grzegorz 'Ceho' Chwesewicz
     * Design:

       Łukasz 'Baseciq' Mozer
     * HTML, JavaScript, CSS:

       Łukasz 'Baseciq' Mozer
       Paweł 'Bob_R' Czerski
       Paweł 'sickone' Kisiela
       Tomasz 'Chilek' Chiliński
       Konrad 'kondi' Rzentarzewski
       Grzegorz 'Ceho' Chwesewicz
     * Grafika:

       Piotr 'Pierzak' Mierzeński
       Grzegorz 'byko' Cichowski
       Kuba 'kflis' Flis
       Łukasz 'Baseciq' Mozer
       Jakub 'Jimmac' Steiner
     * Dokumentacja i strona WWW:

       Aleksander 'A.L.E.C' Machniak
       Kuba 'shasta' Jankowski
       Grzegorz 'JaBBaS' Dzięgielewski
       Łukasz 'Baseciq' Mozer
       Marcin 'Lexx' Król
       Konrad 'kondi' Rzentarzewski
     * Betatesterzy:

       Grzegorz 'byko' Cichowski
       Radosław 'Warden' Antoniuk
       Tomasz 'dzwonek' Dzwonkowski
       Sebastian 'Victus' Frasunkiewicz
       Kuba 'kflis' Flis
       Krystian 'UFOczek' Kochanowski
       Grzegorz 'JaBBaS' Dzięgielewski
       Andrzej 'chsh' Grądziel
     __________________________________________________________________

1.2.2. Inni

   LMS zawiera fragmenty następującego oprogramowania: phpMyAdmin,
   phpsysinfo, NewsPortal, overLIB, ezpdf, xajax,
   procedury konwersji liczb na postać słowną autorstwa Piotra Klebana
   oraz przykłady kodu zawarte w Podręczniku PHP.
     __________________________________________________________________

1.3. Licencja

   Niniejszy program jest oprogramowaniem wolnodostępnym; możesz go
   rozprowadzać dalej i/lub modyfikować na warunkach Powszechnej Licencji
   Publicznej GNU, wydanej przez Fundację Wolnodostępnego Oprogramowania -
   według wersji drugiej tej Licencji lub którejś z późniejszych wersji.

   Niniejszy program rozpowszechniany jest z nadzieją, iż będzie on
   użyteczny - jednak BEZ JAKIEJKOLWIEK GWARANCJI, nawet domyślnej
   gwarancji PRZYDATNOŚCI HANDLOWEJ albo PRZYDATNOŚCI DO OKREŚLONYCH
   ZASTOSOWAŃ. W celu uzyskania bliższych informacji - Powszechna Licencja
   Publiczna GNU.

   Z pewnością wraz z niniejszym programem otrzymałeś też egzemplarz
   Powszechnej Licencji Publicznej GNU; jeśli nie - napisz do Free
   Software Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA
   02111-1307, USA.

   Angielski tekst tej licencji znajduje się tutaj.
     __________________________________________________________________

1.4. Informacje dodatkowe

1.4.1. Kontakt z autorami

   Najlepiej poprzez listę mailingową, na którą można się zapisać
   wysyłając pustego maila o temacie "subscribe lms" na adres
   ecartis@lists.lms.org.pl, a maile wysyła się na adres
   lms@lists.lms.org.pl.
     __________________________________________________________________

1.4.2. Zgłaszanie błędów i pomysłów

   Aby efektywnie zgłosić błąd lub nowy pomysł, najlepiej jest zapisać się
   na listę mailingową gdzie ktoś z autorów bądź użytkowników będzie miał
   szansę odpowiedzieć na pytania Cię nurtujące. Dostępny jest także dla
   testów BTS, gdzie można zgłaszać błędy po uprzedniej rejestracji.
   Raporty z BTS idą także na listę mailingową, więc najlepiej się
   zapisać, zgłosić błąd poprzez BTS, wysłać linka na listę i czekać na
   rozwój wydarzeń. Adres BTS to http://bts.lms.org.pl.
     __________________________________________________________________

1.4.3. Najnowsza wersja

   Zawsze aktualną wersję LMS można pobrać z repozytorium CVS za pomocą
   interfejsu www tutaj, lub klasycznie (dostęp anonimowy, puste hasło):
cvs -d :pserver:cvs@cvs.lms.org.pl:/cvsroot login
cvs -d :pserver:cvs@cvs.lms.org.pl:/cvsroot co lms
cvs -d :pserver:cvs@cvs.lms.org.pl:/cvsroot logout

   Repozytorium CVS można także przeglądać przy pomocy interfejsu WWW pod
   adresem http://cvs.lms.org.pl.
     __________________________________________________________________

1.4.4. Historia zmian

   Informacje o zmianach jakie zaszły w kolejnych wersjach LMS zawarte są
   w pliku ChangeLog.
     __________________________________________________________________

1.4.5. Wsparcie komercyjne

   Wielokrotnie na liście dyskusyjnej pojawiają się pytania typu "kiedy
   dana funkcjonalność zostanie dodana, czy też jaka kwota przyspieszyłaby
   wykonanie pewnych konkretnych prac." Aby nie zaśmiecać głównej listy
   mailingowej powstała lista lms-support. Aby się na nią zapisać należy
   wysyłać email o treści: "subscribe lms-support" na adres ecartis (at)
   lists.lms.org.pl, a konkretne maile wysyła się na adres lms-support
   (at) lists.lms.org.pl.

   Osoby poszukujące płatnego wsparcia w instalacji/konfiguracji lub
   potrzebujące nowych funkcjonalności powinny właśnie skorzystać z listy
   lms-support. Tam rozmawiamy o pieniądzach. Zlecając prace czy też
   pytając się o cenę należy pamiętać że LMS jest projektem Open Source.
   Nie jest on produktem jakiejś firmy, a więc każdą poprawkę należy
   uzgadniać bezpośrednio z developerem który ma ją wykonać. Osoby
   potrzebujące dokumentu potwierdzającego wykonanie usługi (faktury VAT,
   rachunku uproszczonego, umowy zlecenia bądź o dzieło) powinny to od
   razu zaznaczyć - najprawdopodobniej uzyskanie takiego dokumentu
   podniesie koszty całej usługi.

   Najbardziej doświadczonym w załatwianiu tego typu spraw jest Alec.
   Prowadzi on stronę www gdzie można dokonywać wpłat mających na celu
   rozwój projektu. Szczegóły znajdują się na stronie: http://lms.alec.pl.
     __________________________________________________________________

Rozdział 2. Instalacja i konfiguracja

2.1. Wstęp

   LMS składa się z kilku modułów, podstawowym modułem jest LMS-UI
   (interfejs użytkownika). Jest on w całości napisany w PHP i do pracy
   wymaga bazy danych (właściwie to każdy moduł wymaga bazy danych). To
   właśnie w LMS-UI wykonujemy wszystkie czynności, reszta modułów ma
   tylko za zadanie zautomatyzować pracę LMS.

   LMS to także zestaw skryptów w języku Perl, i to właśnie te skrypty
   wymagają abyś posiadał interpreter tego języka. Jeśli będziesz chciał
   używać tych skryptów, musisz mieć Perl'a. Skrypty pozwalają m.in. na
   comiesięczne naliczanie opłat abonamentowych bądź wysyłanie upomnień.
   Największy z nich - LMS-MGC jest skryptem, ale na tyle uniwersalnym że
   potrafi wygenerować praktycznie dowolny plik konfiguracyjny i
   zrestartować usługę na twoim serwerze.

   Jest jeszcze LMS Daemon, napisany w języku C. Jest on przeznaczony (a
   właściwie jego wtyczki) do generowania plików konfiguracyjnych i
   restartowania usług. Można go stosować jako zamiennik lub uzupełnienie
   skryptów perlowych. Odpowiada on za to, aby to co zostało zmienione w
   LMS-UI zostało zmienione także w rzeczywistości.
     __________________________________________________________________

2.2. Wymagania

2.2.1. Serwer WWW

   Ponieważ LMS-UI jest napisane w PHP, niezbędny jest serwer WWW z
   interpreterem tego języka. Preferowanym serwerem jest Apache
   (www.apache.org).
     __________________________________________________________________

2.2.2. Interpreter PHP

   Interpreter powinien być w wersji 5.2.x lub nowszej. PHP można ściągnąć
   ze strony www.php.net. W szczególności wymagane są następujące moduły
   (sprawdź "extension" w php.ini lub wyjście funkcji phpinfo()):
     * pcre, posix,
     * zlib (dla kompresowanych backupów),
     * gd i/lub ming (tylko dla mapy sieci),
     * mysql, mysqli lub pgsql (dla bazy danych),
     * iconv, mbstring
     * PEAR::Mail (wymaga PEAR::Net_SMTP i PEAR::Net_Socket) do mailingu.
     __________________________________________________________________

2.2.3. Serwer baz danych

   LMS nie będzie działał prawidłowo na wersjach MySQL starszych od 5.0.

   LMS współpracuje także z PostgreSQL w wersji 8.4.x lub nowszych.
     __________________________________________________________________

2.2.4. Biblioteka Smarty

   LMS-UI do pracy wymaga jeszcze biblioteki Smarty
   (http://www.smarty.net) w wersji 3.0 lub wyższej.
     __________________________________________________________________

2.2.5. Perl

   O ile dla LMS-UI wystarczy to co powyżej, to żeby mieć działający
   LMS-MGC i resztę skryptów potrzebujemy także Perla i moduły do niego,
   które można pobrać z www.cpan.org, czyli:
     * perl właściwy i jego podstawowe moduły (POSIX, GetOpt::Long),
     * Config::IniFiles,
     * DBI,
     * DBD-mysql (Jeśli masz zamiar używać mysql'a),
     * DBD-Pg (Jeśli masz zamiar używać postgres'a),
     __________________________________________________________________

2.2.6. Kompilator języka C

   Jeśli chcesz uruchomić LMS Daemon będziesz potrzebował działający
   kompilator języka C, gdyż jest on dostarczany wyłącznie w postaci kodu
   źródłowego.
     __________________________________________________________________

2.2.7. Przeglądarka www

   LMS posiada webowy interfejs, dlatego wymagana jest przeglądarka, która
   obsługuje javascript i ma włączone cookies. Z naszego doświadczenia
   wynika, że najlepszym wyborem będzie Mozilla Firefox 1.x.
     __________________________________________________________________

2.3. Instalacja LMS

   LMS w postaci archiwum tar.gz można pobrać ze strony domowej projektu
   (www.lms.org.pl), a następnie rozpakować i umieścić w wybranym katalogu
   (np. /var/www/lms ) dostępnym dla serwera www:
$ cd /var/www
$ wget http://www.lms.org.pl/download/stable/lms-x.x.x.tar.gz
$ tar zxf lms-x.x.x.tar.gz

   Biblioteka Smarty zawarta jest w paczce z LMSem. Natomiast gdy używasz
   wersji systemu pobranej wprost z CVSu musisz sam zadbać o jej
   instalację. Najprościej skorzystać ze skryptu /devel/smarty_install.sh,
   który pobierze bibliotekę Smarty z Internetu i skopiuje zawartość
   katalogu /lib z pobranej paczki do katalogu /lib/Smarty.

   Notatka

   Położenie wszystkich katalogów możesz zmienić w sekcji [directories]
   pliku lms.ini.

   Pliki z konfiguracją (sample/lms.ini i sample/lms-mgc.ini) umieść w
   katalogu /etc/lms.

   Skrypty wykonywalne z katalogu bin najlepiej przenieść do katalogu
   /usr/sbin.

   Ostrzeżenie

   Serwer www musi mieć prawo odczytu pliku lms.ini oraz prawa odczytu i
   zapisu do katalogu backup. Stanowi to potencjalne obniżenie poziomu
   bezpieczeństwa systemu.
   Ostrzeżenie

               Bezwzględnie LMS wymaga wyłączenia opcji PHP register_globals.
   Notatka

   Począwszy od wersji 1.6 przechowywanie konfiguracji interfejsu
   użytkownika w lms.ini jest przestarzałe. Jedynymi koniecznymi sekcjami
   w tym pliku są [database] i [directories] oraz konfiguracja skryptów
   perlowych. Ustawienia LMS-UI przechowywane są w bazie danych i mogą być
   modyfikowane przez interfejs użytkownika, mają także wyższy priorytet
   od tych zawartych w lms.ini.

   Zalecane zmiany w php.ini (lub httpd.conf dla wirtualki LMSa):
mbstring.func_overload = 7
register_globals = off
max_execution_time = 60 ; co najmniej
memory_limit = 32M ; co najmniej

   Przed pierwszym uruchomieniem LMSa wymagane jest ustawienie opcji
   konfiguracyjnych bazy danych i katalogów w pliku lms.ini. Następnie po
   uruchomieniu LMS-UI zakładamy konto użytkownika uprzywilejowanego
   (zaznaczając wszystkie uprawnienia). Po czym możemy przystąpić do
   konfiguracji podstawowej systemu. W menu Konfiguracja -> Interfejs
   użytkownika ustawiamy podstawowe opcje odnoszące się do LMS-UI.
   Następnie wymagane jest zdefiniowanie przynajmniej jednej firmy
   (oddziału) oraz wskazane jest zdefiniowanie stawek podatkowych, planów
   numeracyjnych, województw oraz hostów.
     __________________________________________________________________

2.4. Lokalizacja

   Domyślnym językiem interfejsu użytkownika jest angielski, a znaki są
   kodowane w UTF-8. Aby znaki narodowe innych języków były poprawnie
   wyświetlane należy mieć w systemie odpowiednie locale. Np. dla języka
   polskiego będzie to komenda:
# localedef -v -c -i pl_PL -f UTF-8 /usr/share/locale/pl_PL.UTF-8

   Jeśli w interfejsie użytkownika znaki narodowe w dalszym ciągu nie będą
   wyświetlane poprawnie możliwe, że trzeba będzie dodać do pliku
   konfiguracyjnego serwera www (httpd.conf) wpis:
AddDefaultCharset Off

   oraz w konfiguracji PHP (php.ini) zakomentować wpis:
;default_charset = "iso-8859-1"

   Informacje na temat konfiguracji kodowania bazy danych w dalszej części
   rozdziału.
     __________________________________________________________________

2.5. Instalacja serwera baz danych

2.5.1. MySQL

2.5.1.1. Wstęp

   Ta bardzo popularna baza jest dostępna z większością dystrybucji
   Linuksa. Jeżeli jednak będziesz musiał ją zainstalować samodzielnie,
   zacznij od ściągnięcia źródeł z www.mysql.com.
     __________________________________________________________________

2.5.1.2. Instalacja serwera MySQL

   Po rozpakowaniu, wejdź do katalogu z naszym MySQL i wydaj kolejno
   polecenia:
$ ./configure --prefix=/usr/local/mysql
$ make
$ make install
$ /usr/local/mysql/bin/mysql_install_db
$ chown mysql -R /usr/local/mysql/var
$ /usr/local/mysql/bin/safe_mysqld &
$ /usr/local/mysql/bin/mysqladmin -u root password nowe_hasło
     __________________________________________________________________

2.5.1.3. Utworzenie bazy danych

   Konieczne to jest jeżeli uruchamiasz LMS po raz PIERWSZY. Tak więc,
   wejdź do katalogu w którym masz LMS'a i uruchom shell mysql'a:
mysql -u[tutaj wpisz użytkownika z pełnym dostępem do bazy] -p
Enter password:[podaj hasło]
mysql> CREATE DATABASE lms CHARACTER SET utf8 COLLATE utf8_polish_ci;
mysql> GRANT USAGE ON lms.* TO lms@localhost;
mysql> GRANT ALL ON lms.* TO lms@localhost IDENTIFIED BY 'twoje_hasło';
mysql> flush privileges;
     __________________________________________________________________

2.5.1.4. Konfiguracja LMS (lms.ini)

   Ponieważ MySQL jest domyślną bazą dla LMS'u, konfiguracja ogranicza się
   do podania w sekcji [database] pliku /etc/lms/lms.ini hasła i
   użytkownika:
user     = lms
password = hasło_z_pkt.3

   W PHP dostępne są dwa rozszerzenia do obsługi bazy MySQL (mysql i
   mysqli). LMS obsługuje oba, możesz wybrać, ktego chcesz użyć ustawiając
   odpowiednio opcję type w sekcji [database].

   Po takim zabiegu, o ile LMS'owi uda się nawiązać połączenie do bazy
   danych, można już bez problemu dostać się do systemu. Jeżeli jednak w
   bazie danych nie ma żadnego konta użytkownika, jedyną rzeczą jaką
   zobaczysz będzie formularz dodania użytkownika. Jeżeli podasz
   prawidłowe dane użytkownika, LMS przeniesie Cię na stronę logowania
   gdzie od razu będziesz mógł użyć nowo utworzonego konta.

   Zatrzymaj się tutaj i dodaj coś do crona, tak dla świętego spokoju:
12 4 3,10,17,21,28 * * /usr/bin/mysqldump -u lms --password=Twoje-super-tajne-hasło \
              --add-drop-table --add-locks lms > backups/lms-auto-"$(date +%s)".sql

   Spowoduje to wykonywanie o 4:12 rano, każdego 3, 10, 17, 21 i 28 dnia
   miesiąca automagicznie zrzutu danych z mysqla.
     __________________________________________________________________

2.5.2. PostgreSQL

2.5.2.1. Wstęp

   LMS jest testowany na PostgreSQL 8.4.x i nowszych, możesz mieć problemy
   korzystając ze starszych wersji. Jeżeli nie masz zainstalowanego
   serwera PostgreSQL, możesz np. własnoręcznie skompilować go ze źródeł
   dostępnych na stronie www.postgresql.org.
     __________________________________________________________________

2.5.2.2. Instalacja

   Jest to wersja skrócona instalacji, więcej informacji znajdziesz w
   dokumentacji postgresa. Po ściągnięciu i rozpakowaniu wejdź do katalogu
   głównego i wpisz kolejno poniższe polecenia.
$ ./configure --enable-locale
$ gmake
$ su
$ gmake install
$ adduser postgres
$ mkdir /usr/local/pgsql/data
$ chown postgres /usr/local/pgsql/data
$ su - postgres
$ /usr/local/pgsql/bin/initdb -D /usr/local/pgsql/data --locale=pl_PL.UTF-8
$ /usr/local/pgsql/bin/postmaster -D /usr/local/pgsql/data >logfile 2>&1 &

   Ostrzeżenie

   Dotyczy wersji <= 9.1.x: Wymagane jest dodanie wpisu w postgresql.conf:
   custom_variable_classes = 'lms'
     __________________________________________________________________

2.5.2.3. Utworzenie bazy danych

   Mając uruchomiony serwer możesz przystąpić do tworzenia bazy o nazwie
   'lms', której właścicielem będzie użytkownik z loginem 'lms'.
$ /usr/local/pgsql/bin/createuser -DPRS lms
$ /usr/local/pgsql/bin/createdb -E UNICODE -O lms lms
     __________________________________________________________________

2.5.2.4. Konfiguracja LMS (lms.ini)

   Dla systemu LMS domyślnym serwerem baz danych jest MySQL, dlatego w
   sekcji [database] pliku /etc/lms/lms.ini należy ustawić następujące
   opcje:
type     = postgres
user     = lms
password = hasło_podane_przy_tworzeniu_użytkownika_lms

   Notatka

           Hasło jest wymagane w zależności od konfiguracji autentykacji
           użytkowników postgresa w /usr/local/pgsql/data/pg_hba.conf. Domyślnie
           hasło nie jest wymagane.

   Po takim zabiegu, o ile LMS'owi uda się nawiązać połączenie do bazy
   danych, można już bez problemu dostać się do systemu. Jeżeli jednak w
   bazie danych nie ma żadnego konta użytkownika, jedyną rzeczą jaką
   zobaczysz będzie formularz dodania użytkownika.

   Zatrzymaj się tutaj i dodaj coś do crona, tak dla świętego spokoju:
12 4 3,10,17,21,28 * * /usr/bin/pg_dump -U lms --clean \
                       --file=backups/lms-auto-"$(date +%s)".sql
     __________________________________________________________________

2.6. Konfiguracja podstawowa

   Głównym plikiem konfiguracyjnym LMS jest lms.ini, który należy umieścić
   w katalogu /etc/lms lub w katalogu głównym LMS'a. Zawiera on zmienne
   konfiguracyjne LMS-UI oraz wszystkich skryptów wykonywalnych z
   wyjątkiem LMS-MGC.

   Notatka

           Pamiętaj o usunięciu średników na początku linii z ustawianym
           parametrem konfiguracyjnym.
     __________________________________________________________________

2.6.1. Sekcja [database] - ustawienia bazy danych

     * type
       Typ drivera bazy danych. Aktualnie w 100% supportowany jest
       'mysql', 'mysqli' oraz 'postgres'. Domyślnie: mysql
       Przykład: type = mysql
     * host
       Host gdzie zainstalowana jest baza danych. Najczęściej, localhost,
       ale można tutaj wstawić cokolwiek (ip, domena, ścieżka do gniazda w
       formacie '/path/to/socket'). Domyślnie: localhost
       Przykład: host = localhost
     * user
       Użytkownik do bazy danych. W wielu wypadkach (jeżeli postępowałeś
       zgodnie ze wskazówkami w dokumentacji) będzie to 'lms'. Jeżeli
       chcesz używać konta uprzywilejowanego, prawdopodobnie wpiszesz
       'root' (MySQL na większości *nixów), 'mysql' (na PLD) bądź
       'postgres' (PostgreSQL). Domyślnie: mysql
       Przykład: user = lms
     * password
       Hasło do bazy danych. Domyślnie puste.
       Przykład: password = password
     * database
       Nazwa bazy danych, domyślnie lms.
       Przykład: database = lms
     __________________________________________________________________

2.6.2. Sekcja [directories] - ustawienia katalogów

     * sys_dir
       Katalog systemowy. Jest to miejsce gdzie jest cała zawartość UI
       LMS'a, czyli index.php, grafiki, szablony i reszta. Domyślnie,
       index.php stara się sam odnaleźć w filesystemie używając getcwd(),
       ale lepiej by było gdyby mu powiedzieć gdzie jest:
       Przykład: sys_dir = /var/www/htdocs/lms/
     * modules_dir
       Katalog z "modułami" LMS'a. Czyli zawartość katalogu modules.
       Domyślnie jest to podkatalog modules w sys_dir.
       Przykład: modules_dir = /usr/share/lms/modules/
     * lib_dir
       Katalog z "bibliotekami" LMS'a. Czyli zawartość katalogu lib.
       Domyślnie to podkatalog lib w sys_dir.
       Przykład: lib_dir = /usr/share/lms/lib/
     * backup_dir
       Katalog z kopiami zapasowymi bazy danych - miejsce gdzie LMS
       zapisuje zrzuty z bazy. Domyślnie jest to podkatalog 'backups'.
       Przykład: backup_dir = /var/backup/lms/

   Ostrzeżenie

   Jeśli katalog z kopiami zapasowymi będzie osiągalny z poziomu WWW, to
   każdy bez autoryzacji będzie miał do nich dostęp.
     * doc_dir
       Katalog na archiwum dokumentów - miejsce gdzie LMS zapisuje pobrane
       pliki. Domyślnie jest to podkatalog 'documents'.
       Przykład: doc_dir = /usr/share/documents/

      Ostrzeżenie

                  Jeśli ten katalog będzie osiągalny z poziomu WWW, to każdy bez
                  autoryzacji będzie miał do nich dostęp.
     * smarty_compile_dir
       Katalog kompilacji Smarty-ego. Miejsce gdzie Smarty kompiluje
       szablony. Domyślnie to templates_c w katalogu sysdir.
       Przykład: smarty_compile_dir = /var/smarty/compile/lms
     * smarty_templates_dir
       Katalog z szablonami którymi Smarty-ego. Domyślnie to podkatalog
       templates z sys_dir'a.
       Przykład: smarty_templates_dir = /usr/share/lms/templates
     __________________________________________________________________

2.6.3. Sekcja [finances] - konfiguracja finansów

   Sekcja ta zawiera opcje dotyczące naliczania opłat, a także dane do
   druków przelewu, których opis znajdziesz w rozdziale o fakturach.
     * suspension_percentage (opcjonalny)
       Wartość procentowa obciążenia generowana dla obciążeń zawieszonych.
       Domyślnie: '0'
       Przykład: suspension_percentage = 50
     __________________________________________________________________

2.7. Prawa dostępu

2.7.1. Idea

   LMS ma możliwość zdefiniowania do 256 reguł dostępu do systemu. Każda z
   nich może zabraniać, bądź pozwalać na dostęp do konkretnych modułów.
   Każdemu użytkownikowi można przydzielić dowolną kombinację reguł
   dostępu.

   Domyślnie zdefiniowana jest następująca lista reguł dostępu:
     * pełen dostęp
     * odczyt wszystkich danych (z wyjątkiem Helpdesku)
     * podłączanie i odłączanie komputerów
     * zarządzanie finansami
     * przeładowywanie konfiguracji
     * zarządzanie klientami
     * zarządzanie komputerami
     * dostęp do statystyk
     * dostęp do korespondencji seryjnej
     * zarządzanie Helpdeskiem (RT)
     * obsługa Helpdesku (RT)
     * zarządzanie hostingiem
     * konfiguracja interfejsu użytkownika
     * zarządzanie sieciami i urządzeniami sieciowymi
     * zarządzanie terminarzem
     * zarządzanie i konfiguracja demona
     * operacje kasowe
     * zarządzanie grupami klientów
     * zarządzanie grupami komputerów
     * przypisywanie klientów do grup
     * przypisywanie komputerów do grup
     * zarządzanie kontami voip
     * zarządzanie Userpanelem
     * brak dostępu do modyfikacji i zakładania kont użytkowników
     * brak dostępu

   Niektóre z nich zezwalają na dostęp do modułów oraz dwie zabraniają.
   Moduły do których użytkownik ma zawsze dostęp to: welcome, copyrights,
   logout, chpasswd (przy czym chpasswd umożliwia tylko zmianę własnego
   hasła), dostęp do reszty jest zdefiniowany regułami.
   Notatka

           Jeśli nie ustawimy użytkownikowi żadnej reguły dostępu, to LMS
           domyślnie przydzieli mu regułkę 0, czyli pełen dostęp.
     __________________________________________________________________

2.7.2. Jak to działa?

   Decyzja czy użytkownik ma prawo dostępu do modułu czy nie przebiega
   następująco:
   - najpierw sprawdzana jest lista modułów, do których zawsze ma się
   dostęp,
   - potem następuje sprawdzenie czy moduł pasuje do reguł w
   poszczególnych poziomach, do których użytkownik ma dostęp,
   - na końcu podejmowana jest decyzja czy użytkownik ma uprawnienia by do
   modułu się dobrać. Jeżeli moduł się załapał na którykolwiek poziom,
   który zabrania dostępu, dostęp zostanie zabroniony nawet jeżeli
   użytkownik ma ustawiony poziom, który pozwala na dostęp do modułu (np.
   ktoś ma pełny dostęp oraz brak dostępu do modułu dodawania komputerów,
   nie będzie on mógł się odwołać do tego modułu). Jeżeli moduł pasuje do
   poziomu, który pozwala na dostęp do danego modułu LMS zezwala na dalszą
   pracę. Jeżeli natomiast moduł się nie "załapał" na żaden poziom również
   zostanie mu wyświetlony komunikat o braku dostępu.
     __________________________________________________________________

2.7.3. Reguły dostępu definiowane przez użytkownika

   Zaawansowani użytkownicy mają możliwość zdefiniowania dodatkowych
   dowolnych reguł dostępu lub przedefiniowania istniejących. W tym celu
   należy utworzyć skrypt PHP na wzór lib/accesstable.php. Lokalizację
   pliku podajemy w opcji custom_accesstable sekcji [phpui].

   W ten sposób można zdefiniować własne reguły zezwalające lub
   zabraniające dostępu do dowolnych modułów. Moduł to nazwa pliku PHP z
   katalogu modules, którą w regułach dostępu podajemy bez rozszerzenia.
   Dla przykładu, można zdefiniować uprawnienie do odczytu faktur (np. na
   potrzeby skryptu lms-sendinvoices) w następujący sposób:
<?php
$access['table'][100]['name']      = 'odczyt faktur';
$access['table'][100]['allow_reg'] = '^invoice$';
?>
     __________________________________________________________________

2.8. Upgrade

   Uaktualnienie LMS'a przebiega w kilku etapach. Zacznij od sprawdzenia
   wymagań systemu, gdyż mogły ulec zmianie. Jeśli korzystasz z bazy MySQL
   powinieneś także zweryfikować uprawnienia użytkownika, one też ulegały
   zmianie w przeszłości.

   Najpierw utwórz katalog z plikami nowej wersji (nie zaleca się
   nadpisywania starych plików nowymi) oraz usuń zawartość katalogu
   templates_c.

   Następnie, jeśli nie masz jeszcze w systemie locali pl_PL.UTF-8, należy
   je utworzyć komendą:
# localedef -v -c -i pl_PL -f UTF-8 /usr/share/locale/pl_PL.UTF-8

   Jeśli w interfejsie użytkownika znaki narodowe nie będą wyświetlane
   poprawnie możliwe, że trzeba będzie w pliku konfiguracyjnym serwera www
   (httpd.conf) ustawić:
AddDefaultCharset Off

   oraz w konfiguracji PHP (php.ini) wyłączyć/zakomentować domyślne
   kodowanie:
;default_charset = "iso-8859-1"

   Kolejnym krokiem jest dokonanie zmian w bazie. Od wersji 1.3.1
   wprowadzono automatyczną procedurę aktualizacji struktury bazy danych.
   Następuje ona za każdym razem podczas uruchomienia LMS-UI (jeszcze
   przed zalogowaniem do systemu).

   Ostatni etap - konwersja danych do unicodu. Począwszy od wersji 1.5.4
   zaleca się przechowywanie danych zakodowanych w utf-8 (UNICODE),
   poniżej zostanie przedstawiony przykład migracji z kodowania ISO-8859-2
   do UNICODE na bazie danych PostgreSQL.

   Zaczynamy od utworzenia backupu danych w LMSie. Następnie przechodzimy
   do katalogu z backupami i wykonujemy konwersję pliku backupu.
# iconv --from-code=ISO-8859-2 --to-code=UTF-8 < plik_backupu > nowy_plik

   Po czym zmieniamy nazwę nowo powstałego pliku na lms-xxxxxxx.sql, aby
   plik ten był widoczny z poziomu LMS-UI. Teraz należy utworzyć bazę
   danych z kodowaniem UNICODE (opis w dziale Instalacja). Po czym
   uruchamiamy LMS-UI i odtwarzamy dane z przekonwertowanego backupu.

   Ostrzeżenie

   Jeżeli używasz wersji pobranej z CVSu musisz dodatkowo zadbać o
   instalację biblioteki Smarty. Do katalogu /lib/Smarty wgraj zawartość
   katalogu /lib z paczki Smarty. Czynność (wraz z pobraniem biblioteki
   Smarty) automatycznie wykonuje skrypt /devel/smarty_install.sh.
     __________________________________________________________________

2.8.1. Zmiany w konfiguracji

   Poniżej znajduje się lista zmian (oraz numer wersji), które zrywają
   zgodność z wcześniejszymi wydaniami. Zmiany najcześćiej dotyczą
   usunięcia jakiejś opcji/funkcji, zmiany nazwy opcji konfiguracyjnej,
   zachowania.

   Tabela 2-1. Lista zmian zrywających zgodność z wcześniejszymi wydaniami
   Wersja                               Opis zmiany
   1.11.8
            * opcje smtp_* i debug_email przeniesiono z sekcji [phpui] do sekcji
              [mail]
            * lms-notify: opcję mailtemplate zastąpiono opcją debtors_template,
              opcję mailsubject zastąpiono opcją debtors_subject, usunięto
              parametr --template-file (-D)
            * lms-notify-sms: opcję smstemplate zastąpiono opcją
              debtors_template, usunięto parametr --template-file (-D)
     __________________________________________________________________

2.9. Dokumenty

   LMS umożliwia generowanie oraz przechowywanie przeróżnych dokumentów
   tj. faktur, dowodów wpłat oraz dokumentów nie-finansowych np. umów,
   protokołów. Dokumenty mogą być numerowane wg wzorców (planów)
   numeracyjnych zdefiniowanych przy pomocy menu Konfiguracja - Plany
   numeracyjne.
     __________________________________________________________________

2.9.1. Sposób wyliczania podatku VAT

   Poniżej przedstawiono sposób w jaki wyliczany jest podatek w LMS.
   Wartości wszystkich działań są zaokrąglane do dwóch miejsc po
   przecinku.

   W bazie LMS cena jednostkowa pozycji fakturowej jest przechowywana jako
   wartość brutto (z podatkiem).
     * wartość podatku = (stawka podatku / 100) + 1
       Przykład: stawka podatku VAT to 22%
       wartość podatku = (22 / 100) + 1 = 1,22
     * cena jednostkowa netto = cena jednostkowa brutto / wartość podatku
       Przykład: cena brutto metra kabla wynosi 2,56 zł, a stawka podatku
       VAT to 22%
       cena jednostkowa netto = 2,56 zł / 1,22 = 2,10 zł
     * sumaryczna cena brutto = cena jednostkowa brutto * ilość sztuk
       Przykład: cena brutto metra kabla wynosi 2,56 zł, ilość metrów
       1366, a stawka podatku VAT to 22%
       cena sumaryczna brutto = 2,56 zł * 1366 m = 3496,96 zł
     * cena sumaryczna netto = cena sumaryczna brutto / wartość podatku
       Przykład: cena brutto metra kabla wynosi 2,56 zł, ilość metrów
       1366, a stawka podatku VAT to 22%
       cena sumaryczna netto = (2,56 zł * 1366 m = 3496,96 zł) / 1,22 =
       2866,36 zł
     __________________________________________________________________

2.9.2. Faktury

   LMS umożliwia wystawianie faktur automatycznie i ręcznie. Ręczne
   wystawienie faktury jest możliwe z menu 'Nowa faktura' w panelu
   'Finanse'. Automatyczne wystawianie faktur wymaga włączenia tej opcji
   podczas przypisywania taryf użytkownikom. W takim wypadku zapisem
   faktur do bazy zajmuje się skrypt lms-payments lub demon lmsd

   Do poprawnego działania i tworzenia wydruków wymagane jest ustawienie
   podstawowych parametrów faktury takich jak nagłówek, stopka, domyślny
   wystawca, miejsce wystawienia oraz konto bankowe w definicji firmy.
   Ponadto mamy do dyspozycji opcje sekcji [invoices] konfiguracji:
     * print_balance_history
       Określa czy na fakturze (html) drukować listę operacji finansowych
       na koncie klienta. Domyślnie: nie ustawiona.
       Przykład: print_balance_history = true
     * print_balance_history_limit
       Liczba rekordów na liście operacji finansowych na fakturze.
       Domyślnie: 10.
       Przykład: print_balance_history_limit = 20000

   Wygenerowane faktury można obejrzeć w dwojaki sposób: albo poprzez
   ikonkę drukarki na wykazie bilansu finansowego (ogólnego bądź
   pojedynczego użytkownika), albo poprzez 'Lista faktur' z menu
   'Finanse'. W przypadku listy faktur, możliwe jest także filtrowanie
   faktur do wydruku.

   Podczas wydruku faktury domyślnie wyświetlany jest oryginał i kopia,
   można to zmienić:
     * default_printpage
       Lista oddzielonych przecinkiem nazw stron wydruku faktur. Można
       użyć zdefiniowane ciągi znaków "original", "copy" i "duplicate".
       Domyślnie: "original,copy".
       Przykład: default_printpage = "original"
     __________________________________________________________________

2.9.2.1. HTML

   Przy domyślnych ustawieniach faktury wyświetlane są w formacie html wg
   domyślnego szablonu. W sekcji [invoices] masz do dyspozycji jeszcze
   następujące opcje:
     * template_file
       Szablon faktury, który powinien znajdować się w katalogu templates.
       Domyślnie: invoice.html.
       Przykład: template_file = invoice-mynet.html
     * content_type
       Content-type dla faktury. Jeżeli wpiszesz tutaj
       'application/octet-stream' to przeglądarka zechce wysłać plik do
       zapisania na dysku, zamiast go wyświetlić. Przydatne jeśli używasz
       własnego szablonu, który wygeneruje np.rtf'a lub xls'a. Domyślnie:
       'text/html'
       Przykład: content_type = application/octet-stream
     * attachment_name
       Można podać nazwę pliku, jako który ma zostać zapisany gotowy
       wydruk. Domyślnie: pusta.
       Przykład: attachment_name = faktura.xls

   Wynikowy dokument HTML zawiera oryginały i kopie, które są oddzielone
   znacznikami podziału strony poprzez CSS. Tak więc każda nowoczesna
   przeglądarka zgodna z CSS powinna bezproblemowo drukować faktury ładnie
   podzielone na strony. Funkcja ta była testowana na przeglądarkach
   Microsoft Internet Explorer 6.0, Opera 7.02 oraz Mozilla 1.3.

   Notatka

   Praktycznie każda przeglądarka internetowa ma możliwość konfiguracji
   wydruku, gdzie można wyłączyć funkcje takie jak drukowanie stopki i
   nagłówka, czy też adresu na wydruku.
     __________________________________________________________________

2.9.2.2. PDF

   Możliwe jest także tworzenie faktur jako pliki pdf. Przypisanie opcji
   type z sekcji [invoices] wartości 'pdf' spowoduje, że faktury zamiast w
   html'u będą tworzone jako pliki "portable data format". Opcja
   template_file spełnia podobną rolę jak dla faktur html'owych, z tym, że
   posiada predefiniowane wartości: 'standard' - faktura podstawowa
   (odpowiednik invoice.html) i 'FT-0100' - faktura przystosowana do
   drukowania na drukach FT-0100 zawierających druk polecenia przelewu. W
   opcji template_file można także wstawić nazwę pliku php, jednak ta
   możliwość jest przeznaczona dla zaawansowanych użytkowników, gdyż
   wymaga utworzenia pliku php, a nie tak jak w przypadku faktur
   html'owych szablonu Smarty.
     __________________________________________________________________

2.9.2.3. Korekty

   Faktury korygujące korzystają z ustawień dotyczących pozostałych faktur
   z sekcji [invoices]. Domyślny szablon faktury uwzględnia faktury
   korygujące. Jednak udostępniono opcję pozwalającą na zdefiniowanie
   osobnego szablonu dla korekt (pozostałe opcje są wspólne dla faktur i
   dla korekt):
     * cnote_template_file
       Szablon faktury korygującej, który powinien znajdować się w
       katalogu templates. Domyślnie: invoice.html.
       Przykład: cnote_template_file = invoice-mynet.html
     __________________________________________________________________

2.9.2.4. Automatyczne generowanie numeru konta bankowego

   LMS umożliwia automatyczne generowanie numeru konta bankowego w
   standardzie IBAN zawierającego ID klienta. Funkcja ta może być używana
   do masowej identyfikacji płatności która jako usługa znajduje się w
   ofercie większości banków. Aby masowa identyfikacja była możliwa,
   należy podpisać umowę z bankiem z której potrzebne nam będą:
     * Numer rozliczeniowy banku
       zawsze stały dla każdego banku, złożony z 8 cyfr
     * Identyfikator rachunku
       identyfikator naszego wirtualnego rachunku, złożony z 4 cyfr

   Gdy mamy potrzebne dane, definiujemy konto bankowe w konfiguracji firmy
   (oddziału). System sam rozpozna (na podstawie długości), czy
   wprowadzono cały numer rachunku firmy czy prefiks do płatności
   masowych. Prefix powinien składać się z 8 do 20 cyfr bez spacji i
   innych znaków.

   Od tej pory jeśli używamy faktur PDF z szablonem FT-0100, lub drukujemy
   bloczki przelewu/wpłaty z menu Finanse -> Wydruki -> Faktury -> Drukuj
   polecenia przelewu/wpłaty, każdy wydruk będzie zawierał unikalny numer
   konta z zawartym ID naszego klienta. ID klienta zostanie dodane na
   końcu, oraz poprzedzone odpowiednia ilością zer. Suma kontrolna będzie
   wyliczana automatycznie. Więcej o IBAN w Wikipedii
     __________________________________________________________________

2.9.3. Polecenia przelewu/wpłaty

   Dane do druków polecenia przelewu brane są z danych firmy do której
   przypisany jest dany klient. Tytuł płatności można ustawić przy użyciu
   opcji 'pay_title' w sekcji [finances]. Dotyczy to zarówno wydruków
   poleceń przelewu dostępnych w Finanse -> Wydruki jak i wydruków faktur
   typu FT-0100.
     __________________________________________________________________

2.9.4. Dokumenty kasowe (KP/KW)

   Dokumenty kasowe, ze względu na swoją specyfikę, posiadają podobne
   opcje konfiguracyjne jak faktury.
     __________________________________________________________________

2.9.4.1. HTML

   Przy domyślnych ustawieniach dokumenty kasowe wyświetlane są w formacie
   html wg domyślnego szablonu. Dla dowodów wpłaty oraz wypłaty
   przewidziano wspólny szablon wydruku. W sekcji [receipts] masz do
   dyspozycji jeszcze następujące opcje:
     * template_file
       Szablon dowodu wpłaty/wypłaty, który powinien znajdować się w
       katalogu templates. Domyślnie: receipt.html.
       Przykład: template_file = mytempl/receipt.html
     * content_type
       Content-type dla druku. Jeżeli wpiszesz tutaj
       'application/octet-stream' to przeglądarka zechce wysłać plik do
       zapisania na dysku, zamiast go wyświetlić. Przydatne jeśli używasz
       własnego szablonu, który wygeneruje np.rtf'a lub xls'a. Domyślnie:
       'text/html'
       Przykład: content_type = application/octet-stream
     * attachment_name
       Można podać nazwę pliku, jako który ma zostać zapisany gotowy
       wydruk. Domyślnie: pusta.
       Przykład: attachment_name = receipt.xls
     __________________________________________________________________

2.9.4.2. PDF

   Możliwe jest także tworzenie dokumentów kasowych jako plików pdf.
   Przypisanie opcji type z sekcji [receipts] wartości 'pdf' spowoduje, że
   dowody zamiast w html'u będą tworzone jako pliki "portable data
   format". Opcja template_file spełnia podobną rolę jak dla wydruków
   html'owych z tym, że posiada predefiniowaną wartość: 'standard' -
   wydruk podstawowy (odpowiednik receipt.html). W opcji template_file
   można także wstawić nazwę pliku php, jednak ta możliwość jest
   przeznaczona dla zaawansowanych użytkowników, gdyż wymaga utworzenia
   pliku php, a nie tak jak w przypadku html'a szablonu Smarty.
     __________________________________________________________________

2.9.5. Noty obciążeniowe

2.9.5.1. HTML

   Przy domyślnych ustawieniach noty wyświetlane są w formacie html wg
   domyślnego szablonu. W sekcji [notes] masz do dyspozycji następujące
   opcje:
     * template_file
       Szablon noty, który powinien znajdować się w katalogu templates.
       Domyślnie: note.html.
       Przykład: template_file = mytempl/note.html
     * content_type
       Content-type dla druku. Jeżeli wpiszesz tutaj
       'application/octet-stream' to przeglądarka zechce wysłać plik do
       zapisania na dysku, zamiast go wyświetlić. Przydatne jeśli używasz
       własnego szablonu, który wygeneruje np.rtf'a lub xls'a. Domyślnie:
       'text/html'
       Przykład: content_type = application/octet-stream
     * attachment_name
       Można podać nazwę pliku, jako który ma zostać zapisany gotowy
       wydruk. Domyślnie: pusta.
       Przykład: attachment_name = receipt.xls
     __________________________________________________________________

2.9.6. Dokumenty pozostałe

   Oprócz dokumentów finansowych w LMSie można przechowywać dokumenty
   takie jak umowy, protokoły, aneksy i inne. Każemu klientowi można
   przypisać dowolną liczbę dokumentów na zakładce 'Dokumenty klienta' w
   panelu 'Informacje o kliencie' lub poprzez menu 'Dokumenty'. Pliki z
   dokumentami przechowywane są poza bazą danych (o czym należy pamiętać
   robiąc backupy) w katalogu określonym zmienną 'doc_dir' w sekcji
   [directories] pliku konfiguracyjnego.

   Dokumenty mogą być importowane do systemu jako gotowe pliki, ale także
   tworzone według szablonów przy użyciu zdefiniowanych kreatorów. Tutaj
   system daje duże możliwości konfiguracji. W katalogu
   documents/templates/default znajduje się domyślny kreator dokumentu
   (szablon i silnik). Użytkownik może utworzyć dowolną liczbę własnych
   kreatorów dokumentów, które należy umieścić w katalogu
   documents/templates/.

   Każdy kreator powinien zawierać plik info.php o określonej strukturze:
<?php
$engine = array(
        'name' => 'default',    // nazwa (katalogu) kreatora, małe litery i cyfry
        'engine' => 'default',  // katalog z silnikiem (engine.php)
                                // można używać silników z innych kreatorów
        'template' => 'template.html',          // plik szablonu (w katalogu 'name')
        'title' => trans('Default document'),   // opis, który będzie wyświetlany w LMS-UI
        'content_type' => 'text/html',          // typ pliku wynikowego
        'output' => 'default.html',             // nazwa pliku wynikowego
        'plugin' => 'plugin',                   // nazwa pliku pluginu (w katalogu 'name')
        'post-action' => 'post-action',         // plik PHP wykonywany po dodaniu dokumentu (w transakcji)
);
?>

   Plik info.php opisuje kreatora i jest jedynym wymaganym plikiem. Do
   utworzenia dokumentu potrzebny jest silnik (plik o nazwie engine.php).
   Można utworzyć własny silnik lub skorzystać z innego, ustawiając
   zmienną 'engine' na nazwę kreatora, którego silnik chcemy wykorzystać.
   Nie ma zatem wymogu tworzenia własnego silnika dla każdego nowego
   kreatora, wystarczy utworzyć szablon 'template' i plik info.php.

   Zmienna plugin określa nazwę pliku php odpowiedzialnego za wyświetlenie
   dodatkowych pól w formularzu tworzenia nowego dokumentu. Plugin może
   ponadto zawierać obsługę błędów dla tych pól. Po dodaniu dokumentu
   wykonywany jest skrypt PHP określony w zmiennej post-action. Prosty
   przykład pluginu i post-akcji przedstawiono w przykładowym domyślnym
   dokumencie.
     __________________________________________________________________

2.10. Baza podziału terytorialnego (TERYT)

   LMS obsługuje bazę podziału terytorialnego TERYT w sposób umożliwiający
   przypisanie adresów zgodnych z zawartością tej bazy do komputerów oraz
   urządzeń sieciowych. Aby mieć możliwość wyboru adresów z listy należy
   zaimportować bazę TERYT. Służy do tego skrypt lms-teryt.
     __________________________________________________________________

Rozdział 3. Interfejs Użytkownika (LMS-UI)

   LMS'owy Interfejs Użytkownika to panel administracyjny do tworzenia i
   zarządzania bazą klientów i komputerów dostępny za pomocą przeglądarki
   internetowej. Umożliwia wprowadzanie danych komputerów, przypisanie ich
   do klientów i sieci. Definiowanie opłat i zarządzanie finansami sieci,
   Szybkie wyszukiwanie danych o klientach i ich sprzęcie. Sporządzanie
   korespondencji seryjnej do klientów, definiowanie praw dostępu dla
   użytkowników i ich haseł. Ponadto daje możliwość przeglądania statystyk
   wykorzystania łącza, a także tworzenia kopii bazy danych oraz
   zarządzania konfiguracją usług na serwerze. LMS-UI posiada także wiele
   innych możliwości, które w tym rozdziale zostaną opisane.
     __________________________________________________________________

3.1. Logowanie

   Po wpisaniu w przeglądarce adresu do strony z LMS'em, powita cię ekran
   logowania. Musisz podać właściwy login oraz hasło. Hasła w bazie danych
   przechowywane są w formie zaszyfrowanej.

   Jeżeli jest to twoje pierwsze logowanie i nie masz jeszcze założonego
   konta, zostaniesz przekierowany do modułu tworzenia kont użytkowników.
   W tym momencie będziesz miał tylko dostęp do tego jednego modułu.
     __________________________________________________________________

3.2. Administracja

   Po zalogowaniu do LMS'a znajdziesz się w module (menu) administracji
   systemem LMS. Tutaj znajdziesz niezbędne informacje o systemie.
   Będziesz mógł zarządzać użytkownikami i tworzyć/odtwarzać kopie bazy
   danych.

   Z menu po lewej stronie wybierasz interesujący cię moduł. Możesz
   również dokonać zmiany hasła, wylogować się lub szybko przejść do
   informacji o kliencie i komputerze. W polach szybkiego wyszukiwania,
   oprócz id klienta lub komputera, możesz podawać nazwisko, nazwę,
   fragment adresu, telefon, email, adres IP lub MAC. W przypadku gdy w
   bazie znajduje się więcej klientów pasujących do szukanych danych
   zostaną wyświetlone informacje tylko o jednym kliencie lub komputerze.
     __________________________________________________________________

3.2.1. Informacje

   To panel, na którym znajdują się podstawowe informacje na temat naszego
   systemu: wersja LMS i jego składników, informacje o prawach autorskich,
   wersja jądra i uptime serwera, dane statystyczne o klientach i
   komputerach, bieżące informacje o aktywności komputerów i stanie
   finansów sieci. Ponadto znajdziesz tu przydatne LMS'owe linki.
     __________________________________________________________________

3.2.2. Użytkownicy

   Panel 'Użytkownicy' służy do zarządzania kontami użytkowników, czyli
   osób korzystających z LMS'a, zakładania i przeglądania ich kont, zmiany
   haseł i definiowania praw dostępu.

   Prawa dostępu zostały szczegółowo opisane w rozdziale Prawa dostępu.

   Po wybraniu menu 'Użytkownicy' zobaczysz listę wszystkich użytkowników
   z informacją o ostatnim logowaniu. Kliknięcie na pozycji z listy
   spowoduje wyświetlenie informacji szczegółowych o danym koncie, w tym o
   zdefiniowanych prawach dostępu. W każdej chwili możliwa jest zmiana
   danych i uprawnień za pomocą przycisku 'Edytuj'. Aby założyć nowe konto
   użytkownika skorzystaj z górnego menu 'Nowy użytkownik'.
     __________________________________________________________________

3.2.3. Nowy użytkownik

   Aby dodać nowe konto użytkownika należy podać login, nazwisko/imię oraz
   hasło, które nie może być puste. Pozostałe dane nie są wymagane.
   "Dozwolone hosty" to lista adresów IP hostów lub sieci oddzielonych
   przecinkiem, z których dany użytkownik może się logować do systemu
   (podobnie do opcji konfiguracyjnej 'allow_from'). Jeśli lista ta jest
   pusta system nie dokonuje sprawdzenia adresu IP. Poniżej można
   zaznaczyć uprawnienia dostępu do systemu. Jeżeli wszystkie pola
   pozostawisz puste, użytkownikowi przypisany zostanie 'Pełen dostęp'.

   Pole "Powiadomienia" określa jakiego typu powiadomienia (np. z
   helpdesku) mają być wysyłane do użytkownika. Aby skorzystać z
   powiadomień należy też podać adres e-mail i/lub numer telefonu.
     __________________________________________________________________

3.2.4. Kopie zapasowe

   Z panelu 'Kopie zapasowe' możesz zarządzać kopiami awaryjnymi danych z
   bazy LMSa. Kopia bazy to plik tekstowy zawierający zapytania SQL i dane
   z wszystkich tabel w bazie, zapisywany w katalogu zdefiniowanym w
   sekcji [directories] lms.ini zmienną backup_dir.

   Notatka

           Domyślnie kopie zapisywane są w katalogu lms/backups dostępnym przez
           przeglądarkę, dlatego dobrze było by przenieść go w inne miejsce.

   Sporządzone kopie można w każdej chwili przeglądać, usuwać lub
   zapisywać na lokalnym dysku. Kliknięcie na ikonkę Odtwórz spowoduje
   wyczyszczenie bieżącej bazy danych i załadowanie do niej danych z
   wybranego pliku kopii. Tuż przed odtworzeniem tworzona jest kopia
   aktualnej bazy danych. Odtworzenie bazy danych z kopii jest możliwe
   tylko wtedy gdy została ona utworzona na tej samej wersji LMSa.
     __________________________________________________________________

3.3. Klienci

   Tutaj zarządzasz danymi klientów twojej sieci i ich finansami, ale
   także ich komputerami. Możesz na przykład jednym kliknięciem odłączyć
   wszystkie komputery klienta.
   Notatka

   Do automatycznego odłączania komputerów klientów, których bilans jest
   poniżej zadanej wartości służy skrypt lms-cutoff.
     __________________________________________________________________

3.3.1. Lista

   Po wejściu do panelu 'Klienci' ukaże się lista klientów, którą możesz
   filtrować według wybranych kryteriów (statusu, grupy lub sieci) lub
   sortować klikając na nazwie kolumny. Na liście ikona żarówki informuje
   o statusie podłączenia komputerów klienta, a ikona znaku drogowego o
   statusie ostrzeżeń. Kliknięcie na nich zmienia status na przeciwny w
   stosunku do wszystkich komputerów danego klienta. Gdy klient nie
   posiada przypisanych żadnych komputerów ikony te zostaną zamienione
   znakiem wykrzyknika.

   U klientów z saldem ujemnym pojawia się link "Rozlicz", pozwalający na
   zaksięgowanie jednym kliknięciem wpłaty równoważącej ujemne saldo.

   Kliknięcie na wybranym kliencie spowoduje przejście do panelu z
   informacjami szczegółowymi o nim i przypisanych mu komputerach,
   taryfach oraz operacjach finansowych. Tam też możesz modyfikować dane
   klienta, definiować opłaty abonamentowe i zaksięgować wpłatę lub
   obciążenie jego rachunku.
     __________________________________________________________________

3.3.2. Nowy klient

   Dodając nowego klienta podajesz jego nazwisko/nazwę i dane teleadresowe
   oraz status (podłączony/oczekujący/zainteresowany).
   Notatka

           Często zgłaszane jest błędne kapitalizowanie narodowych znaków
           diakrytycznych. Odpowiedzialny za to jest serwer bazy danych i
           rozwiązania należy szukać w jego konfiguracji.
     __________________________________________________________________

3.3.3. Szukaj

   Możliwe jest wyszukiwanie klientów (także usuniętych) wg zadanych
   kryteriów. Moduł ten, od pola szybkiego wyszukiwania z menu po lewej
   stronie, oprócz tego, że jest bardziej rozbudowany i pozwala wyszukiwać
   wg wielu kryteriów, różni się także tym, że zwraca listę klientów
   spełniających podane kryteria.
     __________________________________________________________________

3.3.4. Grupy

   Tutaj zarządzasz grupami klientów. Po kliknięciu menu 'Grupy' ukaże ci
   się lista grup z podstawowymi informacjami o grupach. Klikając na
   wybranej grupie przejdziesz do modułu, w którym możesz zmienić dane tej
   sieci, przeglądać oraz przypisywać klientów do grup.
     __________________________________________________________________

3.3.5. Nowa grupa

   Grupa powinna posiadać unikalną nazwę, składającą się z liter, cyfr,
   znaku myślnika lub podkreślenia. Ponieważ grupy wykorzystywane są w
   skryptach w nazwie nie może być znaku spacji.
     __________________________________________________________________

3.3.6. Powiadomienia

   Tutaj można "hurtowo" przypisać treść wiadomości administracyjnej
   (powiadomienia) klientom, których wybiera się z listy w lewym oknie.
   Treść przypisuje się klientom, natomiast aby wiadomość zostałą
   wyświetlona w przeglądarce internetowej na komputrerze klienta należy
   ją włączyć dla wybranych komputerów. Włączenie jak i wyłączenie
   powiadomień wszystkim komputerom należącym do wybranych klientów jest
   możliwe w tym miejscu.
     __________________________________________________________________

3.3.7. Raporty

   Zestaw modułów wyświetlających listy i raporty formie przyjaznej dla
   drukarki zawiera:
     * Lista klientów z wieloma filtrami i dowolnym sortowaniem,
     * Raport wierzytelności klienta(ów) na dany dzień,
     * Bilans klienta za wybrany okres
     __________________________________________________________________

3.4. Komputery

   Panel ten służy do zarządzania komputerami i umożliwia: przeglądanie
   listy komputerów, wyszukiwanie, dodawanie nowych lub usuwanie już
   wpisanych do bazy komputerów, a także podgląd i zmianę informacji o
   nich.
     __________________________________________________________________

3.4.1. Lista

   Lista komputerów, ta która pokazuje się po wejściu do panelu
   'Komputery'. obejmuje wszystkie komputery w bazie. Można ją sortować
   klikając na nazwie dowolnej kolumny. W każdym wierszu, po prawej
   stronie znajdują się ikony służące kolejno do: zmiany statusu komputera
   [Podłącz/Odłącz], zmiany statusu ostrzeżenia [Ostrzegaj/Nie ostrzegaj],
   usunięcia z bazy [Usuń], wywołania panelu edycyjnego [Edytuj] oraz
   wyświetlenia informacji szczegółowych na temat danego komputera
   [Informacje]. Do panelu informacyjnego, który jest zintegrowany z
   panelem informacji o właścicielu komputera można także przejść klikając
   na podświetlonym wierszu.
     __________________________________________________________________

3.4.2. Nowy komputer

   Tutaj dodaje się nowe komputery do bazy danych. W tym celu definiujemy
   nazwę komputera (składającą się z liter, cyfr, znaku podkreślenia lub
   myślnika), jego właściciela, adres IP, adres MAC oraz status. Przy
   polach na adresy IP i MAC znajdują się linki do paneli, w których można
   wybrać dostępny adres IP oraz znaleziony w sieci MAC. Po wpisaniu
   danych naciśnij "Zapisz". Przy wprowadzaniu danych dla wielu komputerów
   wygodnie jest zaznaczyć pole "Wywołaj ponownie..."
   Notatka

           Aby wyszukać komputery w swojej sieci możesz skorzystać z programu
           nbtscan. Jeśli jest on zainstalowany w systemie, po naciśnięciu
           'Skanuj' zobaczysz listę znalezionych komputerów.
   Notatka

   Aby dodać komputer musisz mieć sieć i klienta, którym go przypiszesz.
     __________________________________________________________________

3.4.3. Szukaj

   Wyszukiwanie komputerów według zadanych kryteriów. Możesz podać całą
   lub fragment nazwy komputera oraz jego adresów IP i MAC.
     __________________________________________________________________

3.4.4. Powiadomienia

   Tutaj definiuje się treść wiadomości administracyjnych (powiadomień)
   dla klientów oraz włącza lub wyłącza je wybranym komputerom. Po lewej
   stronie znajduje się lista, na której komputery z włączonym
   powiadomieniem zaznaczone są kolorem czerwonym. Jeśli chcesz tylko
   zmienić/przypisać klientom treść powiadomienia, zaznacz odpowiednie
   komputery na liście i pozostaw puste pola 'Włącz' oraz 'Wyłącz'.
     __________________________________________________________________

3.4.5. Raporty

   Wyświetlenie list komputerów, w formacie przyjaznym dla drukarki z
   możliwością definiowania filtrów i sortowania podobnie jak na Liście.
     __________________________________________________________________

3.5. Osprzęt sieciowy

   W panelu przeznaczonym do ewidencji sprzętu zarządzamy strukturą sieci,
   urządzeniami typu switch, hub, router, serwer i połączeniami między
   nimi, a także przyłączonymi do nich komputerami. Każdemu urządzeniu
   można przypisać co najmniej jeden adres sieciowy.
     __________________________________________________________________

3.5.1. Lista

   Lista urządzeń zawiera ich nazwy i symbole, położenie i opis, a ponadto
   ilość dostępnych portów. Lista może być sortowana wg dowolnego
   parametru/kolumny. Kliknięcie na wybranym urządzeniu spowoduje
   wyświetlenie informacji szczegółowych o nim, gdzie możliwe jest
   definiowanie połączeń urządzeń i komputerów, adresów IP urządzenia oraz
   zamiana dwóch urządzeń.
     __________________________________________________________________

3.5.2. Nowe urządzenie

   Urządzenie sieciowe powinno posiadać unikalną nazwę. Pozostałe
   parametry tj. producent, model, nr seryjny, ilość portów, lokalizacja i
   opis są opcjonalne.
     __________________________________________________________________

3.5.3. Szukaj

   Wyszukiwanie urządzeń według zadanych kryteriów. Możesz podać całą lub
   fragment nazwy, adresów IP i MAC oraz innych danych.
     __________________________________________________________________

3.5.4. Mapa

   Generowanie graficznej mapy całej sieci, na podstawie danych z bazy
   LMS'a następuje po wybraniu opcji menu 'Mapa'. Możesz zdefiniować,
   które urządzenie jest nadrzędne w stosunku do pozostałych.
 Notatka

         Do wygenerowania mapy potrzebna jest wbudowana w PHP obsługa biblioteki
         graficznej GD lub Ming.
   Aby wybrać typ mapy musisz skorzystać z opcji map_type z sekcji
   [phpui]. Ustaw "flash" jeśli używasz biblioteki Ming, "gd" jeśli chcesz
   generować obrazki przy pomocy gdlib lub "openlayers" jeśli chcesz
   używać biblioteki OpenLayers do generowania mapy. Domyślnie (opcja
   nieustawiona) LMS spróbuje wykryć jaką bibliotekę masz dostępną w
   systemie, przy czym w pierwszej kolejności szuka możliwości
   wygenerowania mapy we flashu, a jak się to nie uda, to użyje GD.

   Na mapie hosty wyłączone oznaczone są kolorem czarnym. Ikona z
   pytajnikiem oznacza, że dany komputer nie był jeszcze
   skanowany/włączony. Aby skorzystać z tej funkcjonalności należy,
   korzystając z crona, uruchamiać skrypt skanujący. Na mapie komputer
   traktowany jest za włączony jeśli czas ostatniego pozytywnego
   skanowania nie jest starszy od zadanej wartości (domyślnie 600 sekund).
   Parametr ten określa się przy pomocy opcji lastonline_limit w sekcji
   [phpui] pliku konfiguracyjnego. Powyższe odnosi się również do urządzeń
   z tym, że status urządzenia określa się na podstawie statusu wszystkich
   jego adresów. Status komputerów jest także widoczny na liście
   komputerów.
   Notatka

           Do badania aktywności hostów można wykorzystać skrypt lms-fping lub
           demona lmsd.
     __________________________________________________________________

3.6. Sieci IP

   Tutaj definiujesz dane swojej sieci tj. pulę adresową, domenę, DNS'y,
   bramę, zakres DHCP. Jeżeli LMS służy ci do zarządzania wieloma sieciami
   lub dzielisz jedną sieć na podsieci, albo korzystasz z różnych pul
   adresowych tutaj jest to możliwe.
     __________________________________________________________________

3.6.1. Lista

   Lista sieci oprócz podstawowych danych o sieciach zawiera podsumowanie
   ilości adresów wolnych i przypisanych. Modyfikowanie właściwości sieci
   następuje w module dostępnym po wybraniu sieci z listy lub bezpośrednio
   po kliknięciu ikony [Edytuj].

   Podczas edycji danych sieci możesz przeglądać listę komputerów z tej
   sieci, gdzie w miejscu adresu IP pojawia się nazwa
   komputera/urządzenia, do którego możesz przejść klikając na odpowiednim
   polu. Kliknięcie na polu z adresem IP przeniesie Cię do modułu
   dodawania nowego komputera. Znajdziesz tu także dwa przydatne linki:
   Porządkuj sieć służący do przeadresowania komputerów, tak aby
   wyeliminować luki w adresacji oraz Przeadresuj do sieci służący do
   przenoszenia wszystkich komputerów/urządzeń z tej sieci do innej.
     __________________________________________________________________

3.6.2. Nowa sieć

   Definiując nową sieć musisz określić jej unikalną nazwę i pulę adresową
   podając adres IP sieci i maskę. Pozostałe dane są opcjonalne.

   Interfejsy fizyczne, aliasy i vlany są rozpoznawane przez LMS, w sposób
   podany poniżej:
     * Interfejs fizyczny - przykład: eth0
     * Alias - przykład: eth0:1
     * Interfejs vlan o VID 19 - przykład: eth0.19
     * Pierwszy alias na interfejsie vlan o VID 19 - przykład: eth0.19:1
     __________________________________________________________________

3.7. Finanse

   Jest to właściwie wiele modułów umożliwiających zarządzanie finansami
   sieci. Masz możliwość definiowania taryf abonamentowych, opłat (zleceń)
   stałych, księgowania operacji finansowych, przeglądania bilansu i
   historii rachunku oraz sporządzania faktur i zestawień finansowych.
     __________________________________________________________________

3.7.1. Lista taryf

   Po wejściu do panelu 'Finanse' zobaczysz listę taryf zawierającą
   podstawowe informacje o nich. Klikając wybraną taryfę na liście
   zostaniesz przeniesiony do modułu 'Informacje o taryfie', gdzie możesz
   edytować jej parametry lub zamieniać klientom taryfy. W polu 'ilość
   klientów' podana jest liczba klientów, którym dana taryfa została
   przypisana oraz kolejno od lewej (w nawiasie) całkowita liczba
   przypisań taryfy i liczba aktywnych przypisań taryfy, uwzględniająca
   okresy obowiązywania.
     __________________________________________________________________

3.7.2. Nowa taryfa

   Definiując nową taryfę musisz podać unikalną nazwę, kwotę i stawkę
   podatku. Przy czym "zw." w polu stawka podatku oznacza "zwolnienie z
   podatku".
     __________________________________________________________________

3.7.3. Lista płatności

   Lista opłat stałych świadczonych na rzecz innych podmiotów, oprócz
   elementów standardowych zawiera ikonę [Nalicz], przy pomocy której
   można obciążyć rachunek sieci. Automatycznym naliczaniem opłat zajmuje
   się skrypt 'lms-payments' lub odpowiedni moduł demona lmsd. Klikając
   wybraną opłatę na liście zostaniesz przeniesiony do modułu 'Informacje
   o opłacie', gdzie możesz edytować jej parametry lub zaksięgować daną
   opłatę w bazie operacji finansowych.
     __________________________________________________________________

3.7.4. Nowa płatność

   Nowej opłacie stałej przypisujesz unikalną nazwę, wierzyciela oraz
   kwotę i dzień naliczenia.
     __________________________________________________________________

3.7.5. Bilans finansowy

   Historia operacji finansowych z podsumowaniem przychodu, rozchodu,
   wpłat i zobowiązań klientów. Ikona drukarki umożliwia wydruk faktury
   odpowiadającej danej pozycji z listy.
     __________________________________________________________________

3.7.6. Nowa operacja

   Wprowadzanie nowych operacji finansowych. Możliwe jest zaksięgowanie
   tej samej wpłaty lub obciążenia wielu klientom równocześnie.

   Notatka

           Do naliczania stałych opłat abonamentowych najlepiej wykorzystywać
           skrypt lms-payments lub demona lmsd, które potrafią ponadto wystawiać
           faktury.
     __________________________________________________________________

3.7.7. Lista faktur

   Lista wystawionych faktur z możliwością wydruku wybranych faktur (ikona
   [Drukuj]) oraz oznaczenia faktur jako rozliczone. Faktury oznaczone
   jako rozliczone będą na liście wyszarzone. Faktury można filtrować wg
   zadanych kryteriów przy użyciu dostępnego na liście filtra.
     __________________________________________________________________

3.7.8. Nowa faktura

   Ręczne wystawianie faktury dla wybranego klienta. W pierwszej
   kolejności wybieramy klienta z listy, ustawiamy typ płatności i
   pozostałe dane takie jak termin płatności, data wystawienia, a nawet
   numer faktury i klikamy 'Zapisz'. Następnie możemy dodawać pozycje do
   faktury. 'Zapisz i drukuj' kończy edycję nowej faktury, zapisuje ją w
   systemie i wyświetla wydruk faktury w nowym oknie.

   Konfigurację wydruków faktur opisano w rozdziale p.t. Instalacja i
   Konfiguracja.
     __________________________________________________________________

3.7.9. Lista not obciążeniowych

   Lista wystawionych not obciążeniowych (debetowych) z możliwością
   wydruku wybranych (ikona [Drukuj]) oraz oznaczenia ich jako rozliczone.
   Noty oznaczone jako rozliczone będą na liście wyszarzone. Noty można
   filtrować wg zadanych kryteriów przy użyciu dostępnego na liście
   filtra.
     __________________________________________________________________

3.7.10. Nowa nota obciążeniowa

   Ręczne wystawianie noty dla wybranego klienta. W pierwszej kolejności
   wybieramy klienta z listy, ustawiamy datę wystawienia oraz numer i
   klikamy 'Zapisz'. Następnie w tabelce poniżej możemy dodawać pozycje do
   noty. 'Zapisz i drukuj' kończy edycję nowej noty obciążeniowej,
   zapisuje ją w systemie i wyświetla wydruk faktury w nowym oknie.

   Konfigurację wydruków not opisano w rozdziale p.t. Instalacja i
   Konfiguracja.
     __________________________________________________________________

3.7.11. Rejestr kasowy

   Kasę gotówkową można podzielić na rejestry np. kasa1, kasa2, kasa
   główna, bank itp. Na liście rejestrów znajdują się wszystkie informacje
   o zdefiniowanych rejestrach wraz z aktualnym stanem kasy w każdym z
   nich oraz podsumowaniem, które nie obejmuje rejestrów, którym włączono
   opcję "Wyłączenie z sumowania".

   Każdy rejestr kasowy może posiadać własną numerację dokumentów kasowych
   oraz uprawnienia dla użytkowników. Uprawnienie "Zapis", przeznaczone
   dla zwykłych kasjerów, pozwala na odczyt oraz dodawanie nowych
   dokumentów dla wybranego celu:
     * klient - KP/KW dla klienta
     * przen.śr. - przeniesienie środków między kasami
     * zaliczka - dowód wypłaty zaliczki (dla użytkownika - pracownika
       firmy)
     * inny - KP/KW dla osoby nie będącej klientem
     * mod. - możliwość dodania pozycji w inny sposób niż wybranie z listy
       faktur.

   Uprawnienie "Zaawansowany" daje możliwość edycji i usuwania dokumentów
   oraz zmiany numeru i daty wystawienia.

   Na dokumenty kasowe składają się dowody wpłaty "KP" oraz wypłaty "KW",
   stanowiące poświadczenia przyjęcia/wydania gotówki do/z kasy (rejestru
   kasowego). Lista dokumentów kasowych. do której dostajemy się klikając
   wybrany rejestr na liście rejestrów kasowych, może być dowolnie
   sortowana oraz filtrowana podobnie jak lista faktur. Masz także
   możliwość wydrukowania wybranych dowodów.

   Z listy można przejść do edycji dokumentu. Dokonując zmian w
   wystawionych poświadczeniach należy zachować szczególną ostrożność,
   gdyż zapisanie zmian powoduje usunięcie starego poświadczenia i
   związanych z nim operacji oraz wstawienie nowych.
     __________________________________________________________________

3.7.12. Nowy dokument kasowy

   Podczas wystawiania dokumentu kasowego w pierwszej kolejności wybieramy
   rejestr kasowy i typ operacji. Następnie wybieramy z listy klienta lub
   wyszukujemy go wg zadanych kryteriów, ustawiamy datę i numer (najlepiej
   pozostawić wartości zaproponowane przez system). Istnieje możliwość
   wybrania innego typu operacji nie związanych z klientem lub przekazania
   środków do innego rejestru. Następnie klikamy 'Wybierz' aby zatwierdzić
   wybór. Po tym możemy dodawać dowolną ilość pozycji zawierających opis i
   kwotę lub wybrać je z listy nierozliczonych faktur klienta. 'Zapisz i
   drukuj' kończy edycję, zapisuje poświadczenie oraz wpłaty/wypłaty w
   systemie, przechodzi do listy dokumentów w wybranym rejestrze i
   wyświetla wydruk KP/KW w nowym oknie.

   Konfigurację wydruków dokumentów kasowych opisano w rozdziale p.t.
   Instalacja i Konfiguracja.
     __________________________________________________________________

3.7.13. Import

   Import służy do zapisywania operacji finansowych z zewnętrznych
   systemów np. pobranych z banku bezpośrednio ze stron www lub z e-maila.
   Do tego celu należy napisać skrypt (parser) wrzucający dane do tabeli
   cashimport. Przykładem takiego zastosowania jest skrypt
   lms-cashimport-ingbs. Następnie po wejściu do menu Import zobaczymy
   listę wrzuconych operacji finansowych, które należy zatwierdzić
   (ewentualnie zmodyfikować) przed zapisem do systemu finansowego LMSa.

   Dzięki temu modułowi możliwe jest także wczytanie płatności/przelewów z
   przygotowanego wcześniej pliku tekstowego. Plik taki jest czytany linia
   po linii i korzystając z ustawionych wyrażeń regularnych parsowany w
   celu wyłuskania danych dotyczących każdej płatności, potrzebnych do
   zapisania w bazie danych (ID klienta, kwota, itd.). Po wczytaniu pliku
   zostanie wyświetlona lista płatności do zatwierdzenia i/lub korekty.

   Aby skrypt potrafił odczytać dane z plików w dowolnym formacie należy
   zdefiniować odpowiednio wyrażenia regularne w skrypcie PHP, którego
   lokalizację podajemy w opcji import_config sekcji [phpui]. Przykładowe
   wartości wraz z objaśnieniem dostępnych parametrów znajdują się w pliku
   modules/cashimportcfg.php. Domyślna konfiguracja zakłada, że dane będą
   miały postać następującą:
23.02.2004      Machniak Aleksander     123,45  Opłata za Internet 04/2004 ID:0013
15.02.2004      Ból Józef       123,45  Opłata za faktrę LMS/34/2004

   Podczas zatwierdzania importowanych pozycji możliwe jest włączenie
   automatycznego oznaczania faktur jako rozliczonych, zależnie od
   wielkości wpłaty (i bilansu klienta). Służy do tego opcja
   'cashimport_checkinvoices' z sekcji [finances]. Faktura (oraz jej
   korekty) oznaczana jest jako rozliczona jeśli wpłata (z uwzględnieniem
   bilansu klienta) przewyższa kwotę obciążenia wynikającą z danej
   faktury.
     __________________________________________________________________

3.7.14. Eksport

   Eksport danych finansowych do systemów zewnętrznych polega na
   generowaniu plików tekstowych zawierających dane pobrane wg
   zdefiniowanych filtrów. Dla każdego dokumentu tworzony jest rekord w
   pliku tekstowym. Format rekordu ustala użytkownik korzystając ze
   zmiennych.

   Konfiguracji eksportu dokonujemy w pliku, którego lokalizację podajemy
   w opcji export_config sekcji [phpui]. Przykładowe wartości wraz z
   objaśnieniem dostępnych parametrów znajdują się w pliku
   modules/exportcfg.php. Najlepiej więc jest zapisać jego kopię i w niej
   dokonywać stosownych zmian.

   Każdej pozycji Rejestru Sprzedaży (każdej fakturze) odpowiada jeden
   rekord w pliku wyjściowym. W przypadku Raportu Kasowego rekordem jest
   pozycja dokumentu kasowego.
     __________________________________________________________________

3.7.15. Raporty

   Wydruki zestawień finansowych obejmują:
     * Historia operacji finansowych obejmujący operacje finansowe za dany
       okres z możliwością użycia rozbudowanego filtra.
     * Historia operacji klienta na dany okres.
     * Rejestr sprzedaży, czyli zestawienie faktur na dany okres.
     * Raport kasowy, czyli zestawienie dokumentów kasowych za dany okres,
       całościowe lub z wybranego rejestru albo dla wybranego kasjera.
     * Faktury - wydruk wszystkich lub wybranego klienta za dany okres (z
       wyborem oryginał/kopia) lub wygenerowanie PDFa z danymi potrzebnymi
       do zadruku standardowych dwuodcinkowych formularzy przelewu/wpłaty
       z potwierdzeniem.
     * Formularze przelewu - wydruk PDFa z danymi przeznaczonymi do
       zadruku standardowych czteroodcinkowych formularzy przelewu/wpłaty.
     * Łączny przychód bezrachunkowy sieci w danym okresie.
     * Raport wierzytelności na dany dzień, dla wszystkich lub wybranego
       klienta.
     * Historia importu płatności z danego okres z możliwością wyboru
       źródła importu.
     __________________________________________________________________

3.8. Dokumenty

   Dokumenty niefinansowe można znaleźć bezpośrednio w zakładce klienta
   oraz w menu 'Dokumenty'. LMS umożliwia przechowywanie gotowych
   dokumentów w dowolnym formacie oraz tworzenie ich wg zdefiniowanych
   własnych szablonów. Zwłaszcza funkcjonalność szablonów jest ciekawa,
   gdyż umożliwia tworzenie rozbudowanych wtyczek, których działanie nie
   musi ograniczać się tylko do wygenerowania wydruku.
     __________________________________________________________________

3.8.1. Lista

   Lista zawiera podstawowe informacje o wszystkich dokumentach takie jak
   numer, tytuł, typ, datę utworzenia, daty obowiązywania oraz
   nazwę/nazwisko i imię klienta. Prosty filtr umożliwia przeszukiwanie wg
   typu dokumentu lub klienta.
     __________________________________________________________________

3.8.2. Nowy dokument

   Dokumenty można tworzyć na podstawie szablonów utworzonych według zasad
   opisanych w rozdziale Dokumenty pozostałe. Mogą to być także już gotowe
   pliki, które zostaną zapisane na serwerze. Podczas tworzenia/zapisu
   dokumentu należy nadać mu tytuł oraz typ oraz wybrać klienta. Można
   określić daty obowiązywania oraz dodatkowy opis. Dokumenty mogą być
   numerowane według dowolnie zdefiniowanych w systemie planów
   numeracyjnych.
     __________________________________________________________________

3.8.3. Generator dokumentów

   Generator umożliwia utworzenie dokumentów wg wybranego szablonu dla
   wybranej grupy klientów. Opcja 'Drukuj' umożliwia jednocześnie wydruk
   wygenerowanych dokumentów pod warunkiem, że są to dokumenty HTML.
   Notatka

           Z uwagi na wydajność zaleca się korzystanie ze zoptymalizowanych pod
           kątem generatora szablonów oraz nie drukowania większej ilości
           dokumentów (może to prowadzić do zawieszenia przeglądarki).
     __________________________________________________________________

3.8.4. Prawa dostępu

   Tutaj możesz zdefiniować prawa dostępu (odczyt, tworzenie,
   zatwierdzanie, edycja, usuwanie) użytkowników do wybranych typów
   dokumentów.
     __________________________________________________________________

3.9. Hosting

   Zarządzanie różnymi usługami na serwerze jest teraz możliwe.
   Funkcjonalność ta jest przeznaczona dla zaawansowanych użytkowników.
   Wymaga znajomości tych usług i ich konfiguracji w celu korzystania z
   bazy danych.

   W LMSie można utworzyć pięć rodzajów kont: shell (1), poczta (2), www
   (4), ftp (8) i sql (16). W nawiasach podano numeryczne wewnętrzne
   oznaczenie typu konta w bazie. Konta mogą być wielotypowe. Przykładowo,
   jeśli zdefiniujesz konto shell+poczta+ftp w bazie zostanie zapisana
   cyfra 11. Oznacza to, że do rozpoznawania typu konta w warunkach WHERE
   zapytań SQL należy stosować sumowanie binarne (jak na przykładach w
   dalszej części rozdziału).

   Masz także możliwość definiowania domen i aliasów.
     __________________________________________________________________

3.9.1. Konta

   Na liście przedstawione są podstawowe informacje o kontach. Możliwe
   jest dowolne sortowanie listy poprzez kliknięcie nazwy kolumny oraz
   filtrowanie wg zadanych kryteriów. Przejście do edycji danych konta
   następuje po wybraniu ikony [Edytuj]. Użytkownik ma także prawo do
   zmiany hasła.
     __________________________________________________________________

3.9.2. Nowe konto

   Definiując dane konto musisz podać login, hasło, wybrać domenę, wybrać
   typ konta oraz przypisać klienta (lub utworzyć tzw. konto systemowe).
   Data ważności konta jest opcjonalna. Pozostawienie pustego pola z datą
   oznacza, że konto nigdy nie wygasa.

   Masz możliwość zdefiniować dowolny katalog domowy użytkownika (konta).
   Opcja konfiguracyjna homedir_prefix w sekcji [phpui] zawiera prefix
   katalogu domowego, domyślnie ustawiony na wartość "/home/".
     __________________________________________________________________

3.9.3. Aliasy

   Konta (głównie mailowe) mogą posiadać dowolną ilość aliasów.
   Administrator serwera pocztowego może przekierować (lokalnie) pocztę z
   wszystkich aliasów do jednego konta. Na liście aliasów przedstawione są
   podstawowe informacje o nich i o kontach na które aliasy te wskazują.
   Możliwe jest dowolne sortowanie listy poprzez kliknięcie nazwy kolumny
   oraz filtrowanie wg zadanych kryteriów.
     __________________________________________________________________

3.9.4. Nowy alias

   Tworząc alias definiujesz dla niego login i domenę oraz cel. Celem może
   być jedno lub więcej istniejących kont. Uwaga: aby utworzyć alias do
   konta, które znajduje się na obcym serwerze należy utworzyć konto i
   podać adres przekierowania.
     __________________________________________________________________

3.9.5. Domeny

   Obecnie LMS może bezpośrednio zarządzać serwerem PowerDNS z mysql/pqsl
   backend. LMS teraz posiada pełne wsparcie dla większości funkcji
   serwera PowerDNS. Ma pełnie wsparcie dla domen typu: master, slave i
   native. Na liście domen przedstawione są podstawowe informacje o
   zdefiniowanych domenach jak nazwa, typ, właściciel. Możliwe jest
   dowolne sortowanie listy poprzez kliknięcie nazwy kolumny lub poprzez
   pierwszą literę nazwy domeny. Możliwa jest edycja podstawowych danych
   domeny po wybraniu ikony [Edytuj]. Zaawansowana edycja rekordów domeny
   odbywa się poprzez wybranie ikony [Informacje] a następnie [Rekordy].
   Możliwa wtedy jest pełna konfiguracja wszystkich rekordów opisujących
   daną domenę. Każda zmiana rekordu zapisana w bazie danych LMS np.:
   edycja, dodanie lub usunięcie rekordu automatycznie zwiększa licznik
   domeny o 1. Każda zmiana jest także automatycznie dostrzegana przez
   serwer PowerDNS. Aby taka wspópraca była możliwa należy w bazie danych
   LMS założyć dodatkowego użytkownika z uprawieniami SELECT, INSERT,
   UPDATE, DELETE dla tabel domains, records oraz SELECT dla tabeli
   supermaster.
     __________________________________________________________________

3.9.6. Nowa domena

   Dane domeny zawierają nazwę, opis, typ (master, slave, native), adres
   ip serwera www, adres ip serwera pocztowego, adres ip głównego serwera
   nazw (jeśli wybrano typ slave) i inne parametry, których wartości
   domyślne ustawione są w sekcji [zones]. Domena może zostać przypisana
   do klienta.
     __________________________________________________________________

3.9.7. Szukaj

   Wyszukiwanie kont, aliasów i domen według zadanych kryteriów.
     __________________________________________________________________

3.9.8. Przykłady

   Poniżej opisano sposób intergracji LMS z serwerem PowerDNS.

   Przykład 3-1. Domeny. Konfiguracja PowerDNS.
Przygotowanie bazy danych na której działa LMS do współpracy z serwerem PowerDNS.

Dodajemy użytkownika powerdns o uprawnieniach ograniczonych do tabel domains i records.

GRANT SELECT, INSERT, UPDATE, DELETE ON lms.domains TO 'powerdns'@'adres-serwera-dns' IDENTIFIED BY 'hasło';
GRANT SELECT, INSERT, UPDATE, DELETE ON lms.records TO 'powerdns'@'adres-serwera-dns' IDENTIFIED BY 'hasło';

Instalujemy pakiety: pdns, pdns-backend, pdns-recursor.

W dystrybucjach Centos, Fedora, RedHat:

yum install pdns pdns-backend pdns-recursor

Edytujemy plik /etc/pdns/pdns.conf dodając:

launch=gmysql
gmysql-dbname=lms #wpisz właściwą nazwę bazy danych na której pracuje lms
gmysql-host=adres-serwera-sql #podaj adres ip lub nazwę serwera na którym pracuje bazy danych
gmysql-password=hasło
gmysql-user=powerdns
master=yes
slave=yes
recursor=127.0.0.1:5300
allow-recursion=127.0.0.0/8, 192.168.0.1/24

Gdzie 192.168.0.1 to przykładowa sieć. Po przecinku należy dodać własne sieci zktórych
serwer ma obsługiwać zapytania rekurencyjne.

Edytujemy plik /etc/pdns-recursor/recursor.conf dodając:

allow-from=127.0.0.0/8
local-address=127.0.0.1
local-port=5300

Następnie:

chkconfig --levels 235 pdns-recursor on
chkconfig --levels 235 pdns on

service pdns-recursor start
service pdns start

Na Debianie:

apt-get install pdns-server pdns-backend-mysql pdns-recursor

Edytujemy plik  /etc/powerdns/pdns.conf dodając:

launch=gmysql

Edytujemy /etc/powerdns/pdns.d/pdns.local dodając:

gmysql-dbname=lms #wpisz właściwą nazwę bazy danych na której pracuje lms
gmysql-host=adres-serwera-sql #podaj adres ip lub nazwę serwera na którym pracuje bazy danych
gmysql-password=hasło
gmysql-user=powerdns
master=yes
slave=yes

Edytujemy plik /etc/powerdns/recursor.conf      dodając:

allow-from=127.0.0.0/8
local-address=127.0.0.1
local-port=5300

Następnie

/etc/init.d/pdns start
/etc/init.d/pdns-recursor start

   Poniższy listing zawiera istotne fragmenty pliku konfiguracyjnego
   demona proftpd (w wersji 1.2.10) umożliwiający przechowywanie danych o
   kontach ftp w bazie LMSa. Przykład zawiera konfigurację dla bazy danych
   PostgreSQL, w komentarzach podano rozwiązania dla MySQLa:

   Przykład 3-2. Hosting. Konfiguracja proftpd.
  ServerName    "LMS FTP Server"

  #nazwa_bazy@host:port klient hasło
  SQLConnectInfo lms@localhost:5432 lms mypassword

  SQLAuthTypes Crypt Plaintext
  SQLUserInfo passwd login password uid NULL home NULL
  RequireValidShell off
  SQLAuthenticate users

  # utworzenie katalogu domowego gdy nie istnieje
  SQLHomedirOnDemand on

  # komunikat przy logowaniu
  SQLShowInfo PASS "230" "Last login: %{getlastlogin}"
  SQLLog PASS setlastlogin

  # SQLNamedQuery getlastlogin SELECT "CASE lastlogin WHEN 0 THEN '' ELSE FROM_UNIXTIME(lastlogin) END FROM passwd WHERE login='%u'"
  # SQLNamedQuery setlastlogin UPDATE "lastlogin=UNIX_TIMESTAMP() WHERE login='%u'" passwd
  SQLNamedQuery getlastlogin SELECT "CASE lastlogin WHEN 0 THEN '' ELSE lastlogin::abstime::timestamp::text END FROM passwd WHERE login='%u'"
  SQLNamedQuery setlastlogin UPDATE "lastlogin=EXTRACT(EPOCH FROM CURRENT_TIMESTAMP(0)) WHERE login='%u'" passwd

  # Sprawdzamy datę ważności konta oraz ograniczamy szukanie do kont ftp
  # SQLUserWhereClause "type & 8 = 8 AND (expdate = 0 OR expdate > UNIX_TIMESTAMP())"
  SQLUserWhereClause "type & 8 = 8 AND (expdate = 0 OR expdate > EXTRACT(EPOCH FROM CURRENT_TIMESTAMP(0)))"

   W kolejnym przykładzie przedstawimy jak skonfigurować serwer Postfix
   2.1.1 oraz Cyrus-SASL 2.1.19, Courier-IMAP/POP3 3.0.4, aby korzystały z
   bazy danych LMSa. LMS'owe konta będą kontami wirtualnymi, a poczta
   przechowywana będzie w formacie Maildir.

   Ponieważ hasła w LMS'ie są szyfrowane, wymagane jest zainstalowanie
   SASL'a z łatą pozwalającą na to. W komentarzach podano wartości opcji
   charakterystycznych dla bazy MySQL. Listing zawiera tylko opcje
   bezpośrednio związane z bazą danych:

   Przykład 3-3. Konta. Konfiguracja serwera pocztowego
   (postfix+sasl+courier).
# Plik smtpd.conf (Cyrus-SASL):

pwcheck_method: auxprop
password_format: crypt
mech_list: login plain
sql_user: lms
sql_passwd: hasło
sql_hostnames: localhost
sql_database: lms
# MySQL
#sql_engine: mysql
#sql_select: SELECT p.password FROM passwd p, domains d WHERE p.domainid = d.id
#       AND p.login='%u' AND d.name ='%r' AND p.type & 2 = 2
#       AND (p.expdate = 0 OR p.expdate > UNIX_TIMESTAMP())
# PostgreSQL
sql_engine: pgsql
sql_select: SELECT p.password FROM passwd p, domains d WHERE p.domainid = d.id
        AND p.login='%u' AND d.name ='%r' AND p.type & 2 = 2
        AND (p.expdate = 0 OR p.expdate > EXTRACT(EPOCH FROM CURRENT_TIMESTAMP(0)))

# authpgsqlrc (lub authmysqlrc) (Courier):

# użytkownik postfix (właściciel katalogu z pocztą)
#MYSQL_UID_FIELD '1004'
PGSQL_UID_FIELD '1004'
# grupa postfix (właściciel katalogu z pocztą)
#MYSQL_GID_FIELD '1004'
PGSQL_GID_FIELD '1004'
#MYSQL_PORT             3306
PGSQL_PORT              5432
#MYSQL_USERNAME         lms
PGSQL_USERNAME          lms
#MYSQL_PASSWORD         hasło
PGSQL_PASSWORD          hasło
#MYSQL_DATABASE         lms
PGSQL_DATABASE          lms
#MYSQL_SELECT_CLAUSE SELECT p.login, \
#       p.password, '', 104, 104, '/var/spool/mail/virtual', \
#       CONCAT(d.name,'/', p.login, '/'), '', p.login, '' \
#       FROM passwd p, domains d WHERE p.domainid = d.id \
#       AND p.login = '$(local_part)' AND d.name = '$(domain)' \
#       AND p.type & 2 = 2 AND (p.expdate = 0 OR p.expdate > UNIX_TIMESTAMP())
PGSQL_SELECT_CLAUSE SELECT p.login, \
        p.password, '', 104, 104, '/var/spool/mail/virtual', \
        d.name || '/' || p.login ||'/', '', p.login, '' \
        FROM passwd p, domains d WHERE p.domainid = d.id
        AND p.login = '$(local_part)' AND d.name = '$(domain)' \
        AND p.type & 2 = 2 \
        AND (p.expdate = 0 OR p.expdate > EXTRACT(EPOCH FROM CURRENT_TIMESTAMP(0)))

# main.cf (Postfix):

virtual_mailbox_base = /var/spool/mail/virtual
virtual_mailbox_domains = pgsql:/etc/postfix/virtual_domains_maps.cf
virtual_mailbox_maps = pgsql:/etc/postfix/virtual_mailbox_maps.cf
virtual_alias_maps = pgsql:/etc/postfix/virtual_alias_maps.cf
recipient_bcc_maps = pgsql:/etc/postfix/recipient_bcc_maps.cf

# virtual_domains_maps.cf (Postfix):

user = lms
password = hasło
hosts = localhost
dbname = lms
#pgSQL i MySQL
query = SELECT name FROM domains WHERE name = '%s'

# virtual_mailbox_maps.cf (Postfix):

user = lms
password = hasło
hosts = localhost
dbname = lms

# MySQL
#query = SELECT CONCAT(d.name,'/', p.login, '/')
#       FROM passwd p, domains d WHERE p.domainid = d.id
#       AND p.login = '%u' AND d.name = '%d'
#       AND p.type & 2 = 2 AND (p.expdate = 0 OR p.expdate > UNIX_TIMESTAMP())
# PostgresSQL
query = SELECT d.name || '/' || p.login || '/'
        FROM passwd p, domains d WHERE p.domainid = d.id
        AND p.login = '%u' AND d.name = '%d'
        AND p.type & 2 = 2
        AND (p.expdate = 0 OR p.expdate > EXTRACT(EPOCH FROM CURRENT_TIMESTAMP(0)))

# virtual_alias_maps.cf (Postfix):

user = lms
password = hasło
hosts = localhost
dbname = lms
# MySQL
#query = SELECT p.mail_forward
#       FROM passwd p
#       JOIN domains d ON (p.domainid = d.id)
#       WHERE p.login = '%u' AND d.name = '%d'
#               AND p.type & 2 = 2 AND p.mail_forward != ''
#               AND (p.expdate = 0 OR p.expdate > UNIX_TIMESTAMP())
#       UNION
#       SELECT CASE WHEN aa.mail_forward != '' THEN aa.mail_forward ELSE CONCAT(p.login, '@', pd.name) END
#       FROM aliases a
#       JOIN domains ad ON (a.domainid = ad.id)
#       JOIN aliasassignments aa ON (aa.aliasid = a.id)
#       LEFT JOIN passwd p ON (aa.accountid = p.id AND (p.expdate = 0 OR p.expdate > UNIX_TIMESTAMP()))
#       LEFT JOIN domains pd ON (p.domainid = pd.id)
#       WHERE a.login = '%u' AND ad.name = '%d'
#               AND (aa.mail_forward != '' OR p.id IS NOT NULL)
# PostgreSQL
query = SELECT p.mail_forward
        FROM passwd p
        JOIN domains d ON (p.domainid = d.id)
        WHERE p.login = '%u' AND d.name = '%d'
                AND p.type & 2 = 2 AND p.mail_forward != ''
                AND (p.expdate = 0 OR p.expdate > EXTRACT(EPOCH FROM CURRENT_TIMESTAMP(0)))
        UNION
        SELECT CASE WHEN aa.mail_forward != '' THEN aa.mail_forward ELSE p.login || '@' || pd.name END
        FROM aliases a
        JOIN domains ad ON (a.domainid = ad.id)
        JOIN aliasassignments aa ON (aa.aliasid = a.id)
        LEFT JOIN passwd p ON (aa.accountid = p.id
                AND (p.expdate = 0 OR p.expdate > EXTRACT(EPOCH FROM CURRENT_TIMESTAMP(0))))
        LEFT JOIN domains pd ON (p.domainid = pd.id)
        WHERE a.login = '%u' AND ad.name = '%d'
                AND (aa.mail_forward != '' OR p.id IS NOT NULL)

# recipient_bcc_maps.cf (Postfix):

user = lms
password = hasło
hosts = localhost
dbname = lms
# MySQL
#query = SELECT p.mail_bcc FROM passwd p, domains d
#       WHERE p.domainid = d.id
#           AND p.login = '%u' AND d.name = '%d'
#               AND p.type & 2 = 2
#               AND p.mail_bcc != ''
#               AND (p.expdate = 0 OR p.expdate > UNIX_TIMESTAMP())
# PostgreSQL
query = SELECT p.mail_bcc FROM passwd p, domains d
        WHERE p.domainid = d.id
                AND p.login = '%u' AND d.name = '%d'
                AND p.type & 2 = 2
                AND p.mail_bcc != ''
                AND (p.expdate = 0 OR p.expdate > EXTRACT(EPOCH FROM CURRENT_TIMESTAMP(0)))

   Następny przykład podesłany przez bart'a przedstawia instalację i
   konfigurację serwera pure-ftpd w dystrybucji Gentoo z wykorzystaniem
   bazy danych MySQL.

   Przykład 3-4. Konta. Konfiguracja pure-ftpd.

   No to zaczynamy od instalacji serwera pure-ftpd. Pod Gentoo wygląda to
   tak:
bart # emerge pure-ftpd -av
These are the packages that I would merge, in order:
Calculating dependencies ...done!
[ebuild   R   ] net-ftp/pure-ftpd-1.0.20-r1  -caps -ldap +mysql +pam -postgres +ssl +vchroot 459 kB
Total size of downloads: 459 kB

   Co do innych systemów to każdy chyba wie jak się instaluje pakiety w
   swoim systemie, a jeżeli nie to pozostaje kompilacja ze źródeł. Po
   zainstalowaniu przechodzimy do stworzenia pliku, który będzie
   odpowiadał za łączenie się z bazą LMS'a. Tworzymy plik
   /etc/pureftpd-mysql.conf, który to powinien zawierać minimum:
MYSQLServer     localhost (adres serwera bazy danych - domyślnie 'localhost')
MYSQLPort       3306 (port na którym działa serwer MySql - domyślnie '3306')
MYSQLSocket     /var/run/mysqld/mysqld.sock (
MYSQLUser       lms (nazwa usera z dostępem do bazy)
MYSQLPassword   hasło (tutaj należy podać hasło)
MYSQLDatabase   lms (nazwa bazy danych)
MYSQLCrypt      crypt (sposób przechowywania haseł)
MYSQLGetPW      SELECT password FROM passwd WHERE login="\L" (pobieranie hasła dla usera)
MYSQLGetUID     SELECT uid FROM passwd WHERE login="\L" (pobieranie uid dla usera)
MYSQLGetGID     SELECT gid FROM passwd WHERE login="\L" (pobieranie gid dla usera)
MYSQLGetDir     SELECT home FROM passwd WHERE login="\L" (pobieranie nazwy katalogu domowego dla usera)
MySQLGetQTASZ   SELECT quota_ftp FROM passwd WHERE login="\L" (quota czyli pojemność konta w MB - podając w lms-ui 10 oznacza to pojemność 10MB)

   Teraz pozostaje nam już tylko konfiguracja serwera pure-ftpd. (w gentoo
   plik konfiguracyjny mieści się w /etc/conf.d/pure-ftpd) a więc:
## Najpierw odkomentujmy tę linię, ponieważ inaczej serwer nie będzie chciał wystartować
IS_CONFIGURED="yes"
## Tutaj podajemy adres naszego serwera i port na którym ma nasłuchiwać
SERVER="-S www.nasza.domena.pl,21"
## Określamy ilość jednoczesnych połączeń do serwera oraz ilość połączeń z tegosamego IP
## To już chyba każdy według potrzeb
MAX_CONN="-c 50"
MAX_CONN_IP="-C 2"
## Startujemy daemona w tle
DAEMON="-B"
## Ustalamy procentową zajętość dysku/partycji kiedy serwer powinien przestać zezwalać na przyjmowanie danych
DISK_FULL="-k 90%"
## Jeżeli serwer jest za NATem odkomentuj tę linię
#USE_NAT="-N"
## Autoryzacja ma być pobierana z bazy LMS'a - podajemy ścieżkę do stworzonego przez nas wcześniej pliku
AUTH="-l mysql:/etc/pureftpd-mysql.conf"
## Pozostałe opcje w moim wypadku są takie
MISC_OTHER="-A -x -j"
     __________________________________________________________________

3.10. Wiadomości

   Wiadomości to miejsce z którego możesz rozsyłać do klientów spam w
   postaci wiadomości e-mail lub sms.
     __________________________________________________________________

3.10.1. Lista

   Lista wiadomości zawiera historię wysłanych wiadomości. Możesz ją
   dowolnie przeszukiwać korzystając z filtra. Kliknięcie wiersza
   wiadomości powoduje przejście do strony ze szczegółowymi informacjami o
   wiadomości tj. treści, nadawcy. Strona ta zawiera również listę
   odbiorców wiadomości wraz ze statusem wysyłki.
     __________________________________________________________________

3.10.2. Nowa wiadomość

   Aby wysłać wiadomość należy wybrać grupę docelową przy pomocy
   dostępnych filtrów określających status klienta, sieć, grupę, typ
   łącza, itp. Należy również wybrać typ wiadomości, podać jej temat
   (wymagany również dla smsów) i treść.

   W treści wiadomości można używać zmiennych, w miejsce których zostaną
   podstawione dane właściwe dla każdego z odbiorców:
   %customer - nazwisko/nazwa i imię klienta
   %balance - kwota bilansu (ze znakiem)
   %cid - ID klienta
   %pin - PIN klienta
   %last_10_in_a_table - lista ostatnich 10 operacji na koncie klienta
   %bankaccount - numer konta bankowego
     __________________________________________________________________

3.10.3. Konfiguracja

   Serwer musi być odpowiednio skonfigurowany do użycia PEAR::Mail dla
   mailingu. Jeżeli korzystasz z serwera pocztowego na zdalnym hoście
   będziesz musiał ustawić opcje smtp_host, smtp_port, smtp_username,
   smtp_password.

   Poniżej znajduje się lista opcji konfiguracyjnych związanych z
   wysyłaniem wiadomości pocztowych. Opcje te umieszczone są w sekcji
   [mail]
     * debug_email
       Adres e-mail do debugowania - pod ten adres będą szły wiadomości
       wysyłane z sekcji 'Wiadomości' LMS'a, zamiast do właściwych
       klientów.
       Przykład: debug_email = root@localhost
     * smtp_host, smtp_port, smtp_username, smtp_password
       Parametry połączenia SMTP. LMS umożliwia korzystanie ze zdalnego
       serwera pocztowego z autoryzacją, wykorzystując do tego moduł
       PEAR::Mail. Domyślnie: 127.0.0.1:25.
       Przykład: smtp_host = poczta.domena.pl
     * smtp_auth_type
       Metoda autoryzacji SMTP w mailingu. Przy ustawieniach domyślnych
       zostanie użyta najlepsza z dostępnych metod. Domyślnie: nie
       ustawiona.
       Przykład: smtp_auth_type = DIGEST-MD5

   Konfiguracja wiadomości SMS jest bardziej rozbudowana. Przede wszystkim
   należy zdefiniować z jakiej usługi będziemy korzystać. Czy będzie to
   bramka smsowa, czy jakieś oprogramowanie zainstalowane na serwerze (np.
   gnokii, smstools). Konfiguracji dokonujemy w sekcji [sms]:
     * service
       Usługa używana do wysyłania smsów. Dozwolone wartości to 'smstools'
       i 'smscenter'
       Przykład: service = smstools
     * prefix
       Telefoniczny prefix kraju. Domyślnie: 48 (Polska)
       Przykład: prefix = 49
     * from
       Nadawca wiadomości. Domyślnie: pusta.
       Przykład: from = ISP Sp. z o.o.
     * username
       Nazwa użytkownika bramki smsowej. Domyślnie: pusta.
       Przykład: username = isp
     * password
       Hasło do bramki smsowej. Domyślnie: puste.
       Przykład: password = haslo
     * smscenter_type
       Typ konta w usłudze smscenter. Jeśli wybrano 'static' LMS doda
       nazwę nadawcy na końcu wiadomości. Domyślnie: dynamic.
       Przykład: smscenter_type = static
     * smstools_outdir
       Katalog na pliki wiadomości do wysłania dla demona smsd z pakietu
       smstools. Serwer HTTP musi mieć prawa do zapisu w tym katalogu.
       Domyślnie: /var/spool/sms/outgoing.
       Przykład: smstools_outdir = /home/smsd/outgoing
     __________________________________________________________________

3.11. Przeładowanie

   Menu 'Przeładowanie' służy do zarządzania usługami na
   serwerach/routerach poprzez "włączanie" żądania przeładowania dla
   LMS-MGC lub demona lmsd.

   Jeżeli korzystasz z LMS-MGC kliknięcie na menu 'Przeładowanie'
   spowoduje uruchomienie generatora plików konfiguracyjnych LMS-MGC,
   który wygeneruje zdefiniowaną konfigurację i przeładuje usługi
   (zależnie od zdefiniowanej opcji reload_execcmd).

   Zachowanie menu 'Przeładowanie' jest zależne od ustawienia opcji
   reload_type. Jeśli zdefiniowałeś zapytanie SQL w opcji reload_sqlquery
   zostanie ono wykonane. Jeżeli zdefiniowałeś hosty możesz wybrać z
   listy, na których z nich chcesz dokonać przeładowania. W takim wypadku
   zapytania sql lub komendy zostaną wykonane dla każdego hosta, przy czym
   definiując je możesz skorzystać ze zmiennej '%host', pod którą zostanie
   podstawiona właściwa nazwa hosta.

   Więcej informacji znajdziesz w rozdziałach dotyczących LMS-MGC oraz
   demona.
     __________________________________________________________________

3.12. Statystyki

   Interfejs do przeglądania statystyk wykorzystania łącza w postaci
   prostych wykresów jest dostępny w menu 'Statystyki'. Dane statystyk
   zawierają ilość danych wysyłanych i pobieranych z Internetu (na danym
   interfejsie) dla każdego komputera. Korzystając z górnego menu możesz
   szybko wygenerować statystyki z ostatniej godziny, ostatniego dnia,
   ostatnich 30 dni lub ostatniego roku.

 Notatka

         Zapisem danych do bazy zajmuje się skrypt lms-traffic, albo demon lmsd.
     __________________________________________________________________

3.12.1. Filtr

   Przed wygenerowaniem wykresu możesz zdefiniować parametry określające
   okres jaki ma być brany pod uwagę, ograniczyć do jednej sieci (jeśli
   masz ich więcej), ilości komputerów oraz posortować odpowiednio wyniki
   (na przykład według downloadu).
     __________________________________________________________________

3.12.2. Kompaktowanie

   W zależności od wybranej przez klienta częstotliwości zapisu, może
   nastąpić szybki przyrost danych w bazie, co spowoduje zwiększenie czasu
   oczekiwania na rysowanie wykresów. Z tego powodu w menu 'Kompaktowanie'
   udostępniono możliwość zmniejszenia rozmiarów bazy statystyk bez utraty
   danych. Wybierając poziom dokładności danych, dane zostaną uśrednione w
   następujący sposób:
     * Poziom Niski (low): dane z poprzedniego dnia i starsze, zostaną
       uśrednione do jednego dnia, czyli jeśli do bazy zapisywane były z
       częstotliwością 10 minut, to 6*24 wpisów zostanie zastąpione
       jednym.
     * Poziom Średni (medium): dane starsze niż miesiąc zostaną uśrednione
       do jednego dnia.
     * Poziom Wysoki (high): dane starsze niż miesiąc zostaną uśrednione
       do jednej godziny.

   Ostrzeżenie

               Kompaktowanie bazy danych jest procesem nieodwracalnym.

   Możliwe jest kompaktowanie bazy danych z wykorzystaniem cron'a. Stronę
   kompaktowania można wywołać (uruchomić) bezpośrednio w przeglądarce,
   np. w następujący sposób:
links -dump \
"http://lms/?m=trafficdbcompact&level=low&removeold=1&removedeleted=1&loginform[login]=login&loginform[pwd]=pass&override=1
     __________________________________________________________________

3.12.3. Raporty

   Obecnie masz możliwość wydruku statystyk klienta z wybranego miesiąca.
   Na wydruku będą statystyki podane w formie tabelarycznej z podziałem na
   dni wraz z podsumowaniem pobranych/wysłanych danych i średnimi
   prędkościami.
     __________________________________________________________________

3.13. Helpdesk

   Helpdesk (inaczej Request Tracker) to system obsługi zgłoszeń. W
   systemie można prowadzić bazę wszystkich zgłoszeń i zapytań klientów
   sieci, ale także osób które nie są wpisane do bazy LMS'a. Zgłoszenia
   można pogrupować w kategorie (kolejki) i wyszukiwać wg zadanych
   kryteriów. Do każdej kolejki można zdefiniować uprawnienia dla
   użytkowników.

   W menu po lewej stronie znajduje się pole szybkiego wyszukiwania
   zgłoszenia, w którym można podawać jego ID lub nazwisko zgłaszającego
   je klienta. W drugim przypadku zostanie wyświetlona lista wszystkich
   zgłoszeń danego klienta.

   Każde zgłoszenie posiada historię, na którą składają się wiadomości od
   użytkowników i klientów. Administrator może wysłać swoją wiadomość do
   klienta, klikając 'Wyślij' podczas dodawania wiadomości. (Należy podać
   e-mail odbiorcy. Adresem nadawcy będzie adres kolejki, a jeśli jest
   pusty, adres użytkownika). Wszystkie wiadomości także te wysłane,
   zostają zapisane w historii zgłoszenia. Zgłoszenie może mieć cztery
   stany: nowy, otwarty, rozwiązany i martwy.

   Skrypt lms-rtparser został stworzony aby umożliwić obsługę systemu i
   przesyłanie zgłoszeń pocztą elektroniczną.
     __________________________________________________________________

3.13.1. Lista kolejek

   Na liście kolejek znajdują się podstawowe informacje i statystyki
   zgłoszeń. Kliknięcie na wybranej kolejce powoduje wyświetlenie listy
   zgłoszeń. Stąd można także przejść do informacji szczegółowych (w tym
   także uprawnieniach) o kolejce lub usunąć wybraną kolejkę. Usunięcie
   kolejki spowoduje także usunięcie zgłoszeń z bazy danych przypisanych
   do niej.
     __________________________________________________________________

3.13.2. Nowa kolejka

   Kolejka (kategoria) posiada nazwę, opcjonalny opis oraz opcjonalny
   adres poczty elektronicznej, który używany jest do korespondencji. W
   tabeli 'Uprawnienia' definiuje się prawa do danej kolejki, które mają
   moc większą od ogólnych uprawnień użytkowników tzn. jeśli user nie ma
   praw do danej kolejki, to nawet jeśli ma 'pełny dostęp' zgłoszeń w tej
   kolejce nie zobaczy. Prawo zapisu pozwala na edycję i dodawanie
   zgłoszeń i wiadomości. Prawo 'Usuwanie' pozwala na usuwanie wiadomości
   i zgłoszeń. Prawo 'Powiadomienia' służy do otrzymywania informacji o
   nowych zgłoszeniach. Jeżeli włączono opcję 'newticket_notify' wszyscy
   użytkownicy z tym uprawnieniem będą otrzymywać emailowe powiadomienia o
   nowych zgłoszeniach do przedmiotowej kolejki. Wszystkie prawa "wyższe"
   od prawa 'Odczyt' zawierają uprawnienie do odczytu wiadomości.
     __________________________________________________________________

3.13.3. Wyszukiwanie

   Wyszukiwanie zgłoszeń polega na wybraniu z bazy zgłoszeń spełniających
   wszystkie podane kryteria (warunek AND, nie OR). Możesz podać temat,
   właściciela, kolejkę, status i zgłaszającego. Klientów sieci wybieraj z
   listy, dla pozostałych należy podać Nazwisko/Imię i/lub e-mail.
     __________________________________________________________________

3.13.4. Nowe zgłoszenie

   Dodając nowe zgłoszenie należy określić dla niego temat, treść, wybrać
   kolejkę oraz zgłaszającego klienta. Osoby spoza sieci wpisujemy w
   polach Nazwisko i Imię. Opcjonalnie, jeśli wiadomość została zgłoszona
   pocztą elektroniczną podajemy adres e-mail (także dla klienta
   zapisanego w bazie).
     __________________________________________________________________

3.13.5. Raporty

   Tutaj mamy możliwość wydruku list zgłoszeń lub statystyk helpdesku:
     * Lista zgłoszeń z filtrami kolejki, statusu i klienta oraz
       możliwością określenia granicy dni,
     * Statystyki zgłoszeń z możliwością wyboru kolejki oraz wartości
       granicznych.
     __________________________________________________________________

3.14. Terminarz

   Terminarz, to tzw. organizer czasu, czyli miejsce gdzie każdy
   użytkownik może prowadzić własny kalendarz. Wprowadzone zadania
   (zdarzenia) mogą być również dostępne dla wszystkich i mieć
   przypisanych dowolnych klientów, co pozwala np. na zarządzanie ekipami
   serwisowymi.

   Dodatkiem do Terminarza jest skrypt lms-reminder, służący do
   przypominania użytkownikom o zaplanowanych na dany dzień zadaniach.
     __________________________________________________________________

3.14.1. Terminarz (Lista zadań)

   Terminarz przedstawia listę dni od dnia wybranego z podręcznego
   kalendarzyka. Ilość dni na liście określa opcja timetable_days_forward
   domyślnie ustawiona na 7 dni. Z poziomu listy można wydrukować plan
   dnia lub przejść do edycji zdarzenia.
     __________________________________________________________________

3.14.2. Nowe zdarzenie

   Dodając do Terminarza nowe zadania musisz określić krótki tytuł oraz
   dzień i godzinę (zakres godzin). Pozostałe pola są opcjonalne.
   Zaznaczenie opcji 'prywatne' spowoduje ukrycie zadania przed wszystkimi
   użytkownikami oprócz tego, który je dodał do Terminarza. Właściwość ta
   pozwala na prowadzenie przez każdego użytkownika własnego, prywatnego
   kalendarza.
     __________________________________________________________________

3.14.3. Szukaj

   Wyszukując w Terminarzu zadania możemy określić zakres dat,
   poszukiwanego klienta, klienta lub fragment tekstu, znajdującego się w
   tytule, opisie lub notatce do zadania. Na liście zadań zostaną
   wyświetlone zadania spełniające wszystkie podane kryteria.
     __________________________________________________________________

3.15. Konfiguracja

3.15.1. Interfejs Użytkownika

3.15.1.1. Podstawy

   Począwszy od wersji 1.5.3 możliwa jest konfiguracja interfejsu
   użytkownika także poprzez LMS-UI. Opcje przechowywane są w bazie danych
   i należy je przenieść z pliku lms.ini. W celu automatycznego
   przeniesienia konfiguracji do bazy danych kliknij na linku znajdującym
   się na pustej liście opcji konfiguracyjnych.

  Notatka

          Opcje konfigurowane w LMS-UI mają większy priorytet od tych zapisanych
          w lms.ini, co oznacza, że plik konfiguracyjny jest także odczytywany,
          ale wartości zmiennych z tego pliku nadpisywane są wartościami
          zapisanymi w bazie danych.
  Notatka

          Demon odczytuje niektóre opcje konfiguracyjne UI tylko z bazy danych,
          dlatego zaleca się przechowywanie konfiguracji w bazie zamiast w pliku
          ini.

   Aby dodać nową opcję kliknij link 'Dodaj opcję'. Aby wyedytować
   parametry opcji kliknij na jej rekord. Zostaniesz przeniesiony do
   formularza edycyjnego. Zmiana statusu opcji oznacza
   włączenie/wyłączenie jej działania, co polega na przypisaniu jej
   wartości domyślnej (jeżeli taką posiada).
     __________________________________________________________________

3.15.1.2. Lista opcji konfiguracyjnych

   Poniżej przedstawiamy listę opcji konfiguracyjnych interfejsu
   użytkownika. Opcje te należy umieścić w sekcji [phpui]. Pozostałe opcje
   zostały omówione w odpowiednich rozdziałach ich dotyczących.
     * lang
       Ustawia język interfejsu użytkownika. Jeśli nie podano, język
       zostanie ustawiony na podstawie ustawień przeglądarki. Domyślnie:
       en.
       Przykład: lang = pl
     * allow_from
       Lista sieci i adresów IP które mają dostęp do LMS. Jeżeli puste,
       każdy adres IP ma dostęp do LMS'a, jeżeli wpiszemy tutaj listę
       adresów bądź pul adresowych, LMS odrzuci błędem HTTP 403 każdego
       niechcianego użytkownika.
       Przykład: allow_from = 192.168.0.0/16, 213.25.209.224/27,
       213.241.77.29
     * timeout
       Timeout sesji www. Po tym czasie (w sekundach) użytkownik zostanie
       wylogowany jeżeli nie podejmie żadnej akcji. Domyślnie 600 sekund.
       Przykład: timeout = 900

   Ostrzeżenie

   Nie ma możliwości ustawienia braku timeoutu. Jeżeli ustawisz tą wartość
   na zero, nie będziesz mógł korzystać z LMS!
     * default_module
       Nazwa modułu startowego (nazwa pliku z katalogu /modules bez
       rozszerzenia .php). Domyślnie: welcome.
       Przykład: default_module = copyrights
     * customerlist_pagelimit
       Limit wyświetlanych pozycji na stronie w liście klientów.
       Domyślnie: 100.
       Przykład: customerlist_pagelimit = 10
     * nodelist_pagelimit
       Limit wyświetlanych pozycji na stronie w liście komputerów.
       Domyślnie: 100.
       Przykład: nodelist_pagelimit = 10
     * balancelist_pagelimit
       Limit wyświetlanych pozycji na stronie na rachunku klienta.
       Domyślnie: 100.
       Przykład: balancelist_pagelimit = 50
     * invoicelist_pagelimit
       Limit wyświetlanych pozycji na stronie w liście faktur. Domyślnie:
       100
       Przykład: invoicelist_pagelimit = 50
     * ticketlist_pagelimit
       Limit wyświetlanych pozycji na stronie w liście zgłoszeń.
       Domyślnie: 100
       Przykład: ticketlist_pagelimit = 50
     * networkhosts_pagelimit
       Ilość komputerów wyświetlanych na jednej stronie w informacjach o
       sieci. Domyślnie: 256.
       Przykład: networkhosts_pagelimit = 1024
     * accountlist_pagelimit
       Limit wyświetlanych pozycji na stronie w liście kont. Domyślnie:
       100.
       Przykład: accountlist_pagelimit = 25
     * domainlist_pagelimit
       Limit wyświetlanych pozycji na stronie w liście domen. Domyślnie:
       100.
       Przykład: domainlist_pagelimit = 25
     * aliaslist_pagelimit
       Limit wyświetlanych pozycji na stronie w liście aliasów. Domyślnie:
       100.
       Przykład: aliaslist_pagelimit = 25
     * configlist_pagelimit
       Limit wyświetlanych pozycji na stronie w liście opcji
       konfiguracyjnych. Domyślnie: 100.
       Przykład: configlist_pagelimit = 50
     * taxratelist_pagelimit
       Limit wyświetlanych pozycji na stronie w liście stawek podatkowych.
       Domyślnie: 100.
       Przykład: taxratelist_pagelimit = 10
     * numberplanlist_pagelimit
       Limit wyświetlanych pozycji na stronie w liście planów
       numeracyjnych. Domyślnie: 100.
       Przykład: numberplanlist_pagelimit = 10
     * divisionlist_pagelimit
       Limit wyświetlanych pozycji na stronie w liście firm. Domyślnie:
       100.
       Przykład: divisionlist_pagelimit = 10
     * documentlist_pagelimit
       Limit wyświetlanych pozycji na stronie w liście dokumentów.
       Domyślnie: 100.
       Przykład: documentlist_pagelimit = 10
     * reload_type
       Typ reloadu. Dozwolone wartości:
       exec - wywoływanie jakiejś komendy (najczęściej coś przez sudo,
       jakiś skrypt lub coś, konfigurowalny poniżej)
       sql - zrobienie wpisów w SQL'u (też można ustawić konkretne query
       SQL'a)
       Domyślna wartość to 'sql'.
       Przykład: reload_type = exec
     * reload_execcmd
       Komenda do wykonania podczas reloadu jeżeli reload_type jest
       ustawione na 'exec'. Domyślnie /bin/true. String ten puszczany do
       komendy system() więc proponuję rozwagę i pomyślenie co się robi i
       jak :) Generalnie średniki powinny być parsowane przez bash'a, ale
       z paru względów LMS sam dzieli poniższy ciąg pod względem średników
       i wykonuje komendy pojedynczo. W poleceniach można używać zmiennej
       '%host', która zostanie zamieniona na nazwę zdefiniowanego hosta
       (Konfiguracja -> Hosty).
       Przykład: reload_execcmd = "sudo /usr/bin/reload_lms.sh"
     * reload_sqlquery
       Zapytanie SQL wykonywane podczas reloadu, jeśli reload_type = sql.
       Domyślnie zapytanie ustawia w bazie polecenie przeładowania dla
       demona lmsd. W zapytaniu można użyć zmiennej '%host' oraz '%TIME%'
       jako podstawki pod aktualny timestamp unixowy. UWAGA! Znak średnika
       jest traktowany jako separator kwerend, tzn. oddzielając znakiem
       średnika możesz wpisać kilka zapytań SQL.
       Przykład: reload_sqlquery = "INSERT INTO reload VALUES
       ('1','%TIME%')"
     * force_ssl
       Wymuszanie SSL'a. Ustawienie tej zmiennej na 1 spowoduje że LMS
       będzie wymuszał połączenie SSL powodując redirect do
       'https://'.$_SERVER[HTTP_HOST].$_SERVER[REQUEST_URI] przy każdej
       próbie dostępu bez SSL. Domyślnie wyłączone.
       Przykład: force_ssl = 0
     * allow_mac_sharing
       Przyzwolenie na dodawanie rekordów komputerów z adresami MAC już
       istniejącymi (nie sprawdza czy jakiś inny komputer posiada taki
       adres MAC). Domyślnie wyłączone
       Przykład: allow_mac_sharing = 1
     * smarty_debug
       Włączenie konsoli debugowej Smarty-ego, przydatne do śledzenia
       wartości przekazywanych z PHP do Smarty-ego. Domyślnie wyłączone.
       Przykład: smarty_debug = 1
     * default_zip, default_city, default_address
       Domyślny kod pocztowy, miasto, ulica, stosowane podczas wstawiania
       nowego klienta. Przydatne gdy mamy do wpisania wielu klientów z tej
       samej ulicy.
       Przykład: default_zip = 39-300
     * use_current_payday
       Określa, czy ma być użyta aktualna data jako dzień zapłaty podczas
       przypisywania zobowiązań klientom. Domyślnie wyłączone.
       Przykład: use_current_payday = 1
     * default_monthly_payday
       Określa domyślny dzień miesiąca odpowiadający dniom zapłaty podczas
       przypisywania zobowiązań klientom. Domyślnie niezdefiniowany.
       Przykład: default_monthly_payday = 1
     * use_invoices
       Powoduje zaznaczenie opcji "z fakturą" w formularzu dodawania
       zobowiązania. Domyślnie wyłączona.
       Przykład: use_invoices = tak
     * lastonline_limit
       Określa czas (w sekundach), po którym komputer zostaje uznany za
       nieaktywny. Powinien odpowiadać częstotliwości uruchamiania skryptu
       badającego aktywność komputerów (np.lms-fping). Domyślnie: 600.
       Przykład: lastonline_limit = 300
     * timetable_days_forward
       Określa ilość dni (łącznie z bieżącym) jaka ma być wyświetlana w
       terminarzu. Domyślnie: 7.
       Przykład: timetable_days_forward = 2
     * arpd_servers
       Lista serwerów arpd do sczytywania MAC'adresów z odległych sieci.
       Lista ta powinna zawierać wpisy w postaci adresIP[:port] oddzielone
       spacjami. Domyślnie pusta.
       Przykład: arpd_servers = 192.168.1.1 192.168.2.1
     * helpdesk_backend_mode
       Włączenie tej opcji spowoduje, że wszystkie wiadomości w systemie
       helpdesk (oprócz tych skierowanych do zgłaszającego) będą wysyłane
       do serwera pocztowego na adres odpowiedniej kolejki. Na serwerze
       tym powinien być uruchomiony skrypt lms-rtparser, który zajmie się
       zapisem wiadomości do bazy danych. Domyślnie: wyłączona.
       Przykład: helpdesk_backend_mode = 1
     * helpdesk_sender_name
       Nazwa nadawcy wiadomości albo predefiniowane wartości: 'queue' -
       nazwa kolejki do której należy zgłoszenie, 'user' - nazwa
       zalogowanego użytkownika (nadawcy). Domyślnie: pusta.
       Przykład: helpdesk_sender_name = Helpdesk
     * newticket_notify
       Włączenie tej opcji spowoduje, że wszyscy użytkownicy z prawami do
       kolejki dostaną powiadomienie (mailem i/lub smsem) o dodaniu do
       niej nowego zgłoszenia. Domyślnie: wyłączona.
       Przykład: newticket_notify = 1
     * helpdesk_stats
       Dodaje statystyki przyczyn zgłoszeń na stronie informacji o
       zgłoszeniu oraz na jego wydruku. Domyślnie: włączona.
       Przykład: helpdesk_stats = 0
     * helpdesk_customerinfo
       Dodaje podstawowe informacje o kliencie na stronie informacji o
       zgłoszeniu oraz w treści powiadomienia. Domyślnie: włączona.
       Przykład: helpdesk_customerinfo = 0
     * ticketlist_status
       Domyślne ustawienie filtra statusu na liście zgłoszeń. Dozwolonych
       wartości szukaj w kodzie strony html. Domyślnie: nie ustawiona.
       Przykład: ticketlist_status = -1
     * ticket_template_file
       Szablon wydruku zgłoszenia. Domyślnie: rtticketprint.html.
       Przykład: ticket_template_file = ../mytemplates/ticket.html
     * to_words_short_version
       Określa format reprezentacji słownej kwot (na fakturach). Dla
       wartości "1" rozwinięciem kwoty 123,15 będzie "jed dwa trz 15/100".
       Domyślnie: 0.
       Przykład: to_words_short_version = 1
     * nodepassword_length
       Domyślna długość hasła (generowanego automatycznie) dla komputera.
       Maksymalnie 32. Domyślnie: 16.
       Przykład: nodepassword_length = 8
     * gd_translate_to
       Kodowanie danych dla biblioteki GD (przydatne jeśli GD wymaga
       ISO-8859-2 zamiast UTF-8 dla funkcji imagetext). Domyślnie:
       ISO-8859-2.
       Przykład: gd_translate_to =
     * check_for_updates_period
       Jak często sprawdzać czy są dostępne poprawki LMS-a (w sekundach).
       Domyślnie: 86400.
       Przykład: check_for_updates_period = 604800
     * default_taxrate
       Określa wartość (nie etykietę) stawki podatkowej, która będzie
       domyślnie zaznaczona na listach wyboru. Domyślnie: 22
       Przykład: default_taxrate = 7
     * big_networks
       Wsparcie dla dużych ISPów, np. ukrywanie list wyboru klientów.
       Domyślnie: wyłączona
       Przykład: big_networks = true
     * short_pagescroller
       Zmienia wygląd pól wyboru strony, ułatwiając nawigację na listach z
       bardzo dużą liczbą stron. Domyślnie: wyłączona
       Przykład: short_pagescroller = tak
     * ewx_support
       Wsparcie dla urządzeń EtherWerX. Domyślnie: wyłączona
       Przykład: ewx_support = tak
     * account_type
       Zmienia domyślnego zaznaczenie checkboxów w dodwaniu kont. shell =
       1 (0000000000000001) mail = 2 (0000000000000010) www = 4
       (0000000000000100) ftp = 8 (0000000000001000) sql = 16
       (0000000000010000) Domyślnie: 32767
       Przykład: account_type = 2
     * default_assignment_period
       Domyślnie wybrana z listy rozwijanej wartość okresu obciążenia,
       przy dodawaniu zobowiązania dla klienta. Możliwe wartości:
       jednorazowo - 0 codziennie - 1 co tydzień - 2 co miesiąc - 3 co
       kwartał - 4 co rok - 5 Domyślnie: 0
       Przykład: default_assignment_period = 3
     __________________________________________________________________

3.15.2. Stawki podatkowe

   Przed rozpoczęciem pracy z systemem finansowym należy zdefiniować
   stawki podatkowe jakich będziemy używać. Na liście znajdują się
   wszystkie dane stawek. Możliwa jest edycja stawki podatkowej, przy czym
   należy pamiętać, że system nie pozwoli na zmianę wartości stawki,
   jeżeli została ona użyta w przeszłości. Nie możliwe jest także
   usunięcie takiej stawki.

   Link 'Dodaj stawkę' przenosi do formularza definiowania nowej stawki
   procentowej. Etykieta stawki jest wyświetlana na listach wyboru oraz w
   szablonie faktury. Wartość stawki to liczba od 0 do 100 z dokładnością
   do dwóch miejsc po przecinku. Status opodatkowania jest wykorzystywany
   do wyróżnienia stawki zwolnionej z podatku, czyli wszystkie pozostałe
   stawki powinny mieć włączone 'opodatkowanie'.

   Stawkę, która będzie domyślnie zaznaczona na listach wyboru można
   zdefiniować przy pomocy opcji default_taxrate w sekcji [phpui]
     __________________________________________________________________

3.15.3. Plany numeracyjne

   Wszystkim dokumentom generowanym przez system LMS można nadawać dowolną
   numerację wg zdefiniowanych wzorów (planów). Możliwe jest używanie
   różnych numeracji w obrębie jednego typu dokumentów. Dla każdego typu
   można zdefiniować jeden plan domyślny (ważne w przypadku faktur
   wystawianych automatycznie, skrypty/demon muszą wiedzieć na jakiej
   numeracji mają być oparte nowo tworzone dokumenty).

   Do każdego planu należy przypisać także okres numeracyjny, czyli
   przedział czasowy w jakim zachowywana jest ciągłość numeracji. Przy
   przejściu do nowego okresu numeracja zostanie wyzerowana. Można
   zdefiniować numerację jednodniową, tygodniową (od poniedziałku do
   niedzieli), miesięczną, kwartalną oraz roczną.

   Na liście planów numeracyjnych znajdują się wszystkie niezbędne
   informacje o planach wraz z przykładowym numerem oraz liczbą dokumentów
   utworzonych przy ich użyciu. Poprzez link 'Dodaj plan' przechodzi się
   do interfejsu dodawania planu. Edycji można dokonać klikając na wybrany
   rekord na liście. Usunięcie planu numeracyjnego jest możliwe tylko
   wtedy gdy nie dotyczy on żadnego istniejącego dokumentu.

   Szablon numeru to dowolny ciąg znaków, który zawiera specjalne symbole
   (specyfikatory) znane z funkcji strftime. Szczegóły użycia oraz wykaz
   wszystkich symboli można znaleźć w manualu PHP. Podstawowym i jedynym
   wymaganym symbolem w szablonie jest symbol '%N', za który zostanie
   podstawiony numer wewnętrzny dokumentu. Pozostałe symbole wynikają z
   daty wystawienia dokumentu. A oto najczęściej używane z nich:
     * %N - liczba dziesiętna określająca numer dokumentu
     * %[1-9]N - j.w. ale z zerami wiodącymi, np. '%4N' dla liczby 12
       zwróci '0012'
     * %I - dodatkowy numer (działa tylko z dokumentami kasowymi)
     * %Y - rok jako liczba dziesiętna z wiekiem włącznie
     * %y - rok jako liczba dziesiętna bez uwzględnienia wieku (00 do 99)
     * %m - miesiąc jako liczba dziesiętna (01 do 12)
     * %b - skrótowa nazwa miesiąca zgodnie z lokalizacją

   Notatka

   Jeżeli w systemie nie zdefiniowano planów numeracyjnych dokumenty będą
   numerowane wg wzorca '%N/LMS/%Y' z okresem rocznym.
     __________________________________________________________________

3.15.4. Firmy (Oddziały)

   Firmy (Oddziały) służą do grupowania klientów. Powinieneś zdefiniować
   przynajmniej jedną firmę. Masz możliwość podania nazwy skróconej i
   pełnej firmy, jej adresu, konta bankowego (lub prefiksu konta płatności
   masowych) oraz danych do fakturowania. Zablokowanie firmy uniemożliwia
   jej przypisanie do klienta.
     __________________________________________________________________

3.15.5. Hosty

   Tutaj definiuje się hosty które będą współpracowały z LMSem, czyli
   komputery (routery, serwery) pobierające konfigurację z bazy LMSa, na
   których będą uruchamiane skrypty lub demon lmsd.

   Nazwa każdego hosta musi być unikalna i zaleca się aby odpowiadała
   rzeczywistej nazwie maszyny, którą można uzyskać uruchamiając polecenie
   hostname na każdej z tych maszyn (zakładając, że są to komputery z
   u*ixem).
     __________________________________________________________________

3.15.6. Demon

   Po zdefiniowaniu hostów można rozpocząć konfigurację demona lmsd.
   Konfiguracja jest bardziej szczegółowo opisana w rozdziale dotyczącym
   demona.
     __________________________________________________________________

3.15.7. Źródła importu

   Import płatności może odbywać się z wielu źródeł (banków). W tym
   miejscu definiuje się ich nazwy, co umożliwia późniejsze przeszukiwanie
   wpłat wg źródła. Źródło można ustawić również dla wpłat klientów poza
   importem. Dokonując importu płatności masz możliwość wyboru źródła.
   Możliwe jest automatyczne określenie źródła, w tym celu należy
   przypisać identyfikator źródła do wzorca w konfiguracji importu.
     __________________________________________________________________

3.15.8. Promocje

   W tym miejscu mamy możliwość definiowania schematów promocji. Schemat
   określa kwotę oraz sposób płatności abonamentu przez cały okres trwania
   promocji. Definiujemy tutaj jeden lub wiele okresów o dowolnej długości
   i mamy możliwość określenia kwoty abonamentu oraz sposobu naliczania.
   Po okresie promocyjnym abonament będzie naliczany w normalnej
   (określonej w taryfie) kwocie. Ponadto możemy określić kwotę opłaty
   aktywacyjnej oraz dodatkowej taryfy doliczanej do abonamentu po okresie
   promocyjnym.

   Zdefiniowane schematy/promocje będą widoczne na liście wyboru w
   formularzu nowego zobowiązania. Wybranie schematu i taryfy spowoduje
   utworzenie odpowiedniej liczby zobowiązań wynikającej ze zdefiniowanych
   w schemacie okresów.
     __________________________________________________________________

Rozdział 4. Skrypty

4.1. Instalacja

   Jeśli chcesz ustawić konfigurację któregoś ze skryptów, robi się to w
   odpowiedniej sekcji lms.ini. Same skrypty przenieś z katalogu /lms/bin/
   do katalogu /usr/sbin. Po przeniesieniu musisz je jeszcze dopisać do
   crontaba tak, aby były uruchamiane automatycznie, właśnie wtedy kiedy
   tego chcesz.

   Przykładowo, wpis w crontabie dla skryptu lms-payments (wykonywanego
   codziennie o godzinie 00:01) powinien wyglądać następująco:
1 0 * * *       /usr/sbin/lms-payments 1 > /dev/null

   Po więcej informacji możesz sięgnąć do man crontab

   Większość lms'owych skryptów posiada dodatkowe opcje uruchomieniowe:
-C plik     położenie i nazwa alternatywnego pliku lms.ini, domyślnie /etc/lms/lms.ini
-q          wykonanie skryptu bez wyświetlania komunikatów
-h          pomoc (a w zasadzie to tylko listing opcji)
-v          informacja o wersji skryptu
     __________________________________________________________________

4.2. Lista dostępnych skryptów

   Tabela 4-1. Lista skryptów wykonywalnych
   Nazwa Opis
   lms-notify Powiadamianie klientów pocztą internetową o zaległościach,
   wystawionych fakturach, przekroczeniu terminu płatności
   lms-notify-sms Odpowiednik lms-notify do wysyłania smsów
   lms-notify-messages Odpowiednik lms-notify do ustawiania powiadomień
   http
   lms-cutoff Odłączanie klientów zadłużonych
   lms-etherdesc Generowanie pliku dla iptraf zawierającego pary MAC adres
   - nazwa hosta
   lms-payments Naliczanie opłat okresowych (abonamentowych) z
   fakturowaniem
   lms-traffic Zapis statystyk wykorzystania łącza
   lms-traffic-logiptables Statystyki łącza dla iptables
   lms-makearp Tworzenie tablicy ARP (/etc/ethers)
   lms-makedhcpconf Konfiguracja serwera DHCP (dhcpd.conf)
   lms-makeiptables Konfiguracja firewalla iptables
   lms-makeipchains Konfiguracja firewalla ipchains
   lms-makeopenbsdpf Konfiguracja firewalla dla systemu OpenBSD
   lms-makeoidentconf Konfiguracja oident
   lms-sendinvoices Wysyłanie faktur do klientów
   lms-makemacs Filtrowanie ruchu na bazie adresów źródłowych MAC
   lms-makehosts Generuje plik /etc/hosts
   lms-makewarnings Generuje regułki przekierowujące ruch klientów
   zadłużonych
   lms-makemessages Generuje regułki przekierowujące ruch klientów, którym
   ustawiono wiadomość administracyjną
   lms-fping Badanie aktywności komputerów.
   lms-reminder Przypominanie o zaplanowanych zadaniach z Terminarza
   lms-rtparser Backend do Helpdesk'a.
   lms-teryt Import bazy TERYT
     __________________________________________________________________

4.3. Opis i konfiguracja

4.3.1. lms-notify

   lms-notify jest dobrym sposobem przypominania ludziom o tym że do pracy
   sieci i łącz konieczne są ich pieniążki. Pozwala on na napisanie
   kilku[-nastu] plików tekstowych i traktowania ich jako szablonów do
   mailingu. Skrypt jest wielofunkcyjny, włączenie określonego zadania
   następuje poprzez zdefiniowanie lokalizacji pliku z szablonem
   wiadomości. Do wysyłania poczty został zastosowany moduł Mail::Sender.
     __________________________________________________________________

4.3.1.1. Szablony

   W szablonach można używać następujących zmiennych:
     * %date-m - zostanie zastąpione aktualnym miesiącem, licząc od 1
       poprzedzone 0, np. 02
     * %date-y - zostanie zastąpione aktualnym rokiem, np. 2003
     * %date_month_name - zostanie zastąpione nazwą bieżącego miesiąca,
       np. marzec
     * %saldo - zostanie zastąpione aktualnym saldem klienta, np. 535
     * %abonament - zostanie zastąpione kwotą abonamentu jaka jest do
       danego klienta przypisana, np. 107
     * %b - saldo z zanegowanym znakiem, np. 107
     * %B - saldo z prawdziwym znakiem, np. -107
     * %pin - numer PIN klienta
     * %cid - ID klienta
     * %number - numer dokumentu (tylko w powiadomieniu o fakturze lub
       nocie obciążeniowej)
     * %value - wartość brutto na fakturze (tylko w powiadomieniu o
       fakturze)
     * %last_10_in_a_table - wyciąg ostatnich 10 operacji kasowych na
       koncie klienta (tylko wiadomości e-mail), np.:

   Przykład 4-1. Lms-notify: Przykładowy wyciąg 10 ostatnich operacji
   kasowych
-----------+------------------------------------------------------+---------
2003-02-02 | Abonament za miesiąc 2003/02                         |  107.00
2003-02-01 | Wpłata                                               | -107.00
2003-02-01 | Abonament za miesiąc 2003/02                         |  107.00
2003-02-01 | Wpłata                                               | -321.00
2003-01-31 | Abonament za miesiąc 2003/01                         |  107.00
2003-01-31 | Abonament za miesiąc 2003/01                         |  107.00
2003-01-31 | Abonament za miesiąc 2003/01                         |  107.00
-----------+------------------------------------------------------+---------

   Przykład 4-2. Lms-notify: Przykład szablonu
UWAGA! Ta wiadomość została wygenerowana automatycznie.

Uprzejmie informujemy iż na Pani/Pana koncie figuruje zaległość w opłatach za
internet w wysokości %B zł.

Jeżeli porozumieli się już Państwo z administratorami w kwestii opłaty za
bieżący miesiąc czyli %date-m %date-y roku, prosimy o
zignorowanie tej wiadomości.

W wypadku gdy uważają Państwo iż zaległość ta jest nieporozumieniem prosimy o
jak najszybszy kontakt.

Wszelkie informacje na temat Państwa rozliczeń mogą Państwo znaleźć pod
adresem http://www.naszasiec.pl/mojekonto/

Jeżeli chcieliby Państwo uregulować zaległości prosimy o kontakt:

Dział Rozliczeń ASK NaszaSiec

Gwidon Mniejważny
telefon: 0-606666666
e-mail: gwidonm@naszasiec.pl

ps. załączamy ostatnie 10 operacji jakie zostało zarejestrowane przez nasz
system billingowy na Państwa koncie:

Data       | Opis                                                 | Wartość
%last_10_in_a_table

--
Amatorska Sieć Komputerowa NaszaSiec
http://www.naszasiec.pl/
     __________________________________________________________________

4.3.1.2. Konfiguracja

   Konfigurację dla lms-notify można ustalić w pliku lms.ini w sekcji
   [notify]. Możesz tam ustawić następujące parametry, które mają
   zastosowanie również dla skryptów lms-notify-sms i lms-notify-messages:
     * debtors_template (opcjonalny)
       Lokalizacja pliku z szablonem wiadomości wysyłanej do zadłużonych
       klientów. Pozostawienie tej opcji pustej wyłączy powiadomienia o
       zadłużeniu. Domyślnie: pusta
       Przykład: debtors_template = /etc/lms/debtors.txt
     * debtors_subject (opcjonalny)
       Temat wiadomości o zadłużeniu. Domyślnie: 'Debtors notification'
       Przykład: debtors_subject = 'Powiadomienie o zadłużeniu'
     * invoices_template (opcjonalny)
       Lokalizacja pliku z szablonem wiadomości z informacją o wystawieniu
       faktury. Pod uwagę brane są faktury wystawione w ciągu ostatnich 24
       godzin od uruchomienia skryptu. Pozostawienie tej opcji pustej
       wyłączy powiadomienia o nowych fakturach. Domyślnie: pusta
       Przykład: invoices_template = /etc/lms/new_invoice.txt
     * invoices_subject (opcjonalny)
       Temat wiadomości o nowej fakturze. Domyślnie: 'New invoice
       notification'
       Przykład: invoices_subject = 'Powiadomienie o wystawieniu faktury'
     * notes_template (opcjonalny)
       Lokalizacja pliku z szablonem wiadomości z informacją o wystawieniu
       noty obciążeniowej. Pod uwagę brane są noty wystawione w ciągu
       ostatnich 24 godzin od uruchomienia skryptu. Pozostawienie tej
       opcji pustej wyłączy powiadomienia o nowych notach. Domyślnie:
       pusta
       Przykład: notes_template = /etc/lms/new_note.txt
     * notes_subject (opcjonalny)
       Temat wiadomości o nowej nocie obciążeniowej. Domyślnie: 'New debit
       note notification'
       Przykład: notes_subject = 'Powiadomienie o wystawieniu noty
       obciążeniowej'
     * deadline_template (opcjonalny)
       Lokalizacja pliku z szablonem wiadomości wysyłanej do zadłużonych
       klientów, posiadających przeterminowane (nierozliczone) faktury.
       Pozostawienie tej opcji pustej wyłączy powiadomienia. Domyślnie:
       pusta
       Przykład: deadline_template = /etc/lms/deadline.txt
     * deadline_subject (opcjonalny)
       Temat wiadomości o przeterminowanych fakturach. Domyślnie: 'Invoice
       deadline notification'
       Przykład: deadline_subject = 'Powiadomienie o zaległości'
     * limit (opcjonalny)
       Pozwala na ustalenie limitu bilansu poniżej którego do klienta
       zostanie wysłana wiadomość z informacją o zadłużeniu. Domyślnie: 0
       Przykład: limit = -20

   Poniżej przedstawiono opcje dotyczące wyłącznie wiadomości e-mail.
     * mailfrom (wymagana)
       Adres e-mail z którego zostanie wysłany e-mail. Proszę pamiętać, że
       na niektórych MTA (np. exim) konto to musi istnieć w systemie.
       Domyślnie: pusta.
       Przykład: mailfrom = staff@domain.pl
     * mailfname
       Nazwa nadawcy maila. Domyślnie: pusta.
       Przykład: mailfname = Administratorzy
     * smtp_host
       Serwer SMTP, przez który ma zostać wysłana wiadomość. Domyślnie:
       localhost
       Przykład: smtp_host = smtp.mydomain.pl
     * smtp_auth
       Sposób autoryzacji. Dozwolone wartości: LOGIN, PLAIN, CRAM-MD5,
       NTLM. Domyślnie: pusta (brak autoryzacji)
       Przykład: smtp_auth = LOGIN
     * smtp_user
       Login do autoryzacji SMTP. Domyślnie: pusty
       Przykład: smtp_user = admin
     * smtp_pass
       Hasło do konta zdefiniowanego w opcji smtp_user. Domyślnie: puste
       Przykład: smtp_pass = password
     * debug_email (opcjonalny)
       Adres e-mail do debugowania. Gdy ustawiony, cała poczta zostaje
       wysłana na dany email zamiast do klientów. Przydatne do debugowania
       i sprawdzania czy wszystko działa OK. Domyślnie: nie ustawiony.
       Przykład: debug_email = lexx@domain.pl
     __________________________________________________________________

4.3.2. lms-notify-sms

   lms-notify-sms to odpowiednik lms-notify, służący do wysyłania smsów.
   Obecnie skrypt wspiera dwie usługi, smstools oraz gnokii. Wyboru usługi
   dokonuje się w sekcji [sms]. Skrypt jest wielofunkcyjny, włączenie
   określonego zadania następuje poprzez zdefiniowanie lokalizacji pliku z
   szablonem wiadomości.

   Konfigurację dla lms-notify-sms można ustalić w pliku lms.ini w sekcji
   [notify-sms], oprócz opcji dostępnych w lms-notify masz do dyspozycji
   następujące opcje:
     * service (opcjonalny)
       Pozwala na wybranie usługi SMS niezależnie od tej, którą podano w
       sekcji [sms]. Domyślnie: pusta
       Przykład: service = smstools
     __________________________________________________________________

4.3.3. lms-notify-messages

   lms-notify-messages to odpowiednik lms-notify, służący do ustawiania
   powiadomień, które będą pojawiać się w przeglądarkach internetowych
   klientów. Skrypt jest wielofunkcyjny, włączenie określonego zadania
   następuje poprzez zdefiniowanie lokalizacji pliku z szablonem
   wiadomości.

   Konfigurację dla lms-notify-messages przeprowadza się w pliku lms.ini w
   sekcji [notify-messages].
     __________________________________________________________________

4.3.4. lms-cutoff

   Skrypt pozwala na odłączenie (a raczej zmianę w bazie danych stanu
   komputerów na wyłączony) klientów których bilans jest poniżej zadanej
   wartości. Właściwe odłączanie powinno być realizowane przez generator
   plików konfiguracyjnych.

   Konfigurację dla lms-cutoff możemy ustalić w pliku lms.ini w sekcji
   [cutoff]. Możemy tam ustawić następujące parametry:
     * limit (opcjonalny)
       Pozwala na ustalenie limitu bilansu poniżej którego do bazy danych
       zostanie zapisany stan odłączony. Domyślnie: 0
       Przykład: limit = -20
     * message (optional)
       Jeśli nie jest pusta, wiadomość ta po dołączeniu użytkownika
       zostanie zapisana do jego rekordu w polu wiadomości
       administracyjnej. W treści wiadomości można użyć zmiennej %now,
       która zostanie zamieniona na bieżącą datę oraz zmiennych %b i %B
       tak jak w skrypcie lms-notify. Domyślnie: 'Automatic cutoff caused
       by exceeding of liabilities limit on %now'
       Przykład: message = ''
     __________________________________________________________________

4.3.5. lms-payments

   Skrypt służący do naliczania opłat abonamentowych przypisanych klientom
   oraz opłat stałych. Ponadto zapisuje dane do faktur. Aby działał
   poprawnie powinien być uruchamiany codziennie.

   Skrypt ten udostępnia trzy opcje do fakturowania, które można ustawić w
   sekcji [payments] pliku lms.ini:
     * deadline (opcjonalny)
       Pozwala na ustalenie terminu płatności w dniach. Domyślnie: 14
       Przykład: deadline = 7
     * paytype (opcjonalny)
       Identyfikator rodzaju płatności (1-gotówka, 2-przelew,
       3-przelew/gotówka, 4-karta, 5-kompensata, 6-barter, 7-umowa).
       Domyślnie: 2 (przelew)
       Przykład: paytype = 1
     * comment (opcjonalny)
       Opis pozycji na fakturze za naliczane zobowiązanie
       Domyślnie: 'Tariff %tariff subscription for period %period'
       Niektóre ze słów kluczowych są zastępowane:
       %tariff - nazwa taryfy
       %period - okres (liczony od dziś do ostatniego dnia cyklu
       rozliczeniowego, w formacie RRRR/MM/DD)
       %current_month - okres od pierwszego dnia bieżącego miesiąca do
       jego końca
       %current_period - bieżący miesiąc w formacie MM/RRRR
       %next_period - następny miesiąc w formacie MM/RRRR
       %prev_period - poprzedni miesiąc w formacie MM/RRRR
       %desc - opis taryfy
       Przykład: comment = 'Abonament za %current_month w/g taryfy
       %tariff'
     * settlement_comment (opcjonalny)
       Opis pozycji z tytułu wyrównania niepełnego okresu zobowiązania.
       Domyślnie odpowiada opcji comment
       Przykład: settlement_comment = 'Wyrównanie za okres %period'
     * suspension_description (opcjonalny)
       Tekst dodawany na końcu opisu operacji dla obciążeń zawieszonych.
       Domyślnie: ''
       Przykład: suspension_description = (zawieszenie)
     * saledate_next_month (opcjonalny)
       Włączenie tej opcji spowoduje, że data sprzedaży na fakturze
       zostanie ustawiona na pierwszy dzień następnego miesiąca.
       Domyślnie: 0
       Przykład: saledate_next_month = 1

   Ponadto mamy do dyspozycji jeden przydatny parametr wiersza poleceń
   --fakedate (-f). Przy jego użyciu można sprawić, aby skrypt działał z
   podmienioną datą systemową (w formacie YYYY/MM/DD), na przykład
   --fakedate=2004/10/10.
     __________________________________________________________________

4.3.6. lms-traffic

   Skrypt służy do logowania informacji o ilości danych pobranych i
   wysłanych przez każdy komputer w sieci lokalnej. W bazie danych
   zapisywana jest ilość danych w bajtach, numer komputera z bazy lms i
   znacznik czasu. Od klienta zależy w jakich odstępach dane będą
   odczytywane. Ponieważ dane odczytywane są z pliku utworzonego przez
   klienta, nie ma znaczenia z jakiego źródła pochodzą, może to być
   iptables, ipchains lub program zewnętrzny np. ipfm.

   Przeglądanie wykresów wykorzystania łącza oraz definiowanie filtrów
   dostępne jest z głównego menu 'Statystyki'.
     __________________________________________________________________

4.3.6.1. Instalacja

   Przed uruchomieniem lms-traffic należy zadbać o utworzenie pliku z
   danymi. Zawartość pliku powinna mieć następujący format:
<adres IP> <n_spacji> <upload> <n_spacji> <download>
<adres IP> <n_spacji> <upload> <n_spacji> <download>
...

   Skrypt tworzący statystyki należy uruchamiać z taką samą
   częstotliwością co lms-traffic.

   Notatka

           Przykład takiego skryptu dla iptables znajduje się w pliku
           /sample/traffic_ipt.pl.

   Następnie instalujemy skrypt dopisując do crontaba. Oprócz
   standardowych opcji wiersza poleceń możliwe jest zdefiniowanie
   lokalizacji pliku z logiem
-f=/plik        położenie i nazwa pliku ze statystykami domyślnie /var/log/traffic.log

   Ostrzeżenie

   Częstotliwość zapisywania danych do bazy ustala użytkownik. Ustawienie
   jej poniżej 10 minut, może spowodować szybki przyrost ilości rekordów w
   bazie danych, a co za tym idzie zwiększyć czas oczekiwania na
   wyświetlenie wyników.
     __________________________________________________________________

4.3.7. lms-traffic-logiptables

   Skrypt służy do logowania informacji o ilości danych pobranych i
   wysłanych przez każdy komputer z sieci lokalnej, na podstawie liczników
   iptables. Dane sczytuje z firewalla, tworząc jednocześnie odpowiednie
   reguły. Zatem, nie jest konieczne ręczne tworzenie reguł iptables, ani
   wywoływanie skryptu lms-traffic.

   Konfigurację należy umieścić w sekcji [traffic-logiptables]:
     * outfile
       Lokalizacja skryptu z regułami iptables. Domyślnie:
       /etc/rc.d/rc.stat
       Przykład: outfile = /etc/rc.d/rc.stat
     * iptables_binary
       Lokalizacja programu iptables. Domyślnie: /usr/sbin/iptables
       Przykład: iptables_binary = /usr/local/sbin/iptables
     * wan_interfaces
       Nazwy interfejsów, na których dane mają być zliczane. Domyślnie:
       niezdefiniowane.
       Przykład: wan_interfaces = eth0
     * local_ports
       Lista portów (źródłowych i docelowych) dla zliczanych pakietów.
       Domyślnie: niezdefiniowane.
       Przykład: local_ports = 80
     * script_owneruid
       UID właściciela skryptu określonego w 'outfile'. Domyślnie: 0
       (root).
       Przykład: script_owneruid = 0
     * script_ownergid
       GID właściciela skryptu określonego w 'outfile'. Domyślnie: 0
       (root).
       Przykład: script_ownergid = 0
     * script_permission
       Uprawnienia skryptu określonego w 'outfile'. Domyślnie: 700
       (rwx------).
       Przykład: script_permission = 700
     * networks
       Lista nazw sieci (oddzielonych spacjami), które mają być
       uwzględnione podczas generowania pliku. Jeśli nie ustawiono,
       zostanie stworzony konfig dla wszystkich sieci.
       Przykład: networks = public-custa public-custb
     __________________________________________________________________

4.3.8. lms-makedhcpconf

   Tworzenie pliku konfiguracyjnego serwera DHCP - dhcpd.conf.
   Konfigurację skryptu umieszcza się w sekcji [dhcp]:
     * config_file
       Lokalizacja pliku wynikowego. Domyślnie: /etc/dhcpd.conf
       Przykład: config_file = /tmp/dhcpd.conf
     * networks
       Lista nazw sieci (oddzielonych spacjami), które mają być
       uwzględnione podczas generowania pliku. Jeśli nie ustawiono,
       zostanie stworzony konfig dla wszystkich sieci.
       Przykład: networks = public-custa public-custb
     * customergroups
       Lista nazw grup (oddzielonych spacjami), które mają być
       uwzględnione podczas generowania pliku. Jeśli nie ustawiono,
       zostanie stworzony konfig dla wszystkich grup.
       Przykład: customergroups = grupa1 grupa2
     * default_lease_time
       Domyślny czas dzierżawy. Domyślnie: 86400.
       Przykład: default_lease_time = 43200
     * max_lease_time
       Maksymalny czas dzierżawy. Domyślnie: 86400.
       Przykład: max_lease_time = 43200
     * ignore_ddns
       Nie generuj wpisu 'ddns-update-style none;' na początku pliku.
       Przydatne przy starszych (2.2) wersjach demona dhcpd. Domyślnie:
       wyłączone.
       Przykład: ignore_ddns = 1
     * log_facility
       Ustawienie trybu logowania daemona dhcp. Jeżeli puste to tryb
       domyślny.
       Przykład: log_facility = 7
     * authoritative
       Dodanie wpisu 'authoritative;' na początku pliku. Domyślnie:
       wyłączone.
       Przykład: authoritative = 1
     * script_owneruid
       UID właściciela skryptu określonego w 'config_file'. Domyślnie: 0
       (root).
       Przykład: script_owneruid = 0
     * script_ownergid
       GID właściciela skryptu określonego w 'config_file'. Domyślnie: 0
       (root).
       Przykład: script_ownergid = 0
     * script_permission
       Uprawnienia skryptu określonego w 'config_file'. Domyślnie: 600
       (rwx------).
       Przykład: script_permission = 700

   Możesz podać czasy dzierżawy dla konkretnych sieci poprzez stworzenie
   sekcji [dhcp:nazwasieci] np.:
[dhcp:public-custa] # nazwa sieci małymi literami!
default_lease_time = 3600
max_lease_time     = 3600

   Możesz podać gateway, serwer dns, nazwę domeny i wins dla konkretnego
   hosta poprzez stworzenie sekcji [dhcp:adresip] np.:
[dhcp:213.25.209.216]
domain  = anotherdomain.pl
gateway = 213.25.209.251
dns     = 213.25.209.8
wins    = 213.25.209.10
     __________________________________________________________________

4.3.9. lms-makeiptables, lms-makeipchains

   Para skryptów służących do generowania plików zawierających reguły
   firewalla. Do utworzonego pliku możesz dołączyć inne wcześniej
   utworzone pliki, a w końcu nadać mu odpowiednie uprawnienia. Skrypty
   nie uruchamiają wygenerowanych plików.

   Konfigurację dla tych skryptów możesz ustalić w pliku lms.ini w sekcji
   [iptables] (i odpowiednio [ipchains]). Oba skrypty posiadają te same
   opcje:
     * networks
       Lista nazw sieci (oddzielonych spacjami), które mają być
       uwzględnione podczas generowania pliku firewalla. Jeśli nie
       ustawiono, zostanie stworzony konfig dla wszystkich sieci.
       Przykład: networks = public-custa public-custb
     * iptables_binary (ipchains_binary)
       Lokalizacja programu iptables (ipchains). Domyślnie:
       /usr/sbin/iptables (/usr/sbin/ipchains)
       Przykład: iptables_binary = /usr/local/sbin/iptables
     * script_file
       Plik, do którego zapisujemy reguły firewalla. Domyślnie:
       /etc/rc.d/rc.masq
       Przykład: script_file = /etc/rc.d/rc.firewall
     * pre_script
       Plik wykonywany PO wyczyszczeniu regułek, ale PRZED ustawieniem
       nowych. Domyślnie: niezdefiniowany.
       Przykład: pre_script = /etc/rc.d/rc.masq-pre
     * post_script
       Plik wykonywany PO ustawieniu regułek. Domyślnie: niezdefiniowany.
       Przykład: post_script = /etc/rc.d/rc.masq-post
     * forward_to
       Lista sieci, dla których włączamy forwarding. Możliwe wartości: ""
       - pełny forward, "dowolny ciąg" - wyłącz forward, "siec1 siec2" -
       lista sieci z włączonym forwardingiem. Domyślnie: pełny forward.
       Przykład: forward_to = public-custa public-custb
     * script_owneruid
       UID właściciela pliku. Domyślnie: 0 (root).
       Przykład: script_owneruid = 0
     * script_ownergid
       GID właściciela pliku. Domyślnie: 0 (root).
       Przykład: script_ownergid = 0
     * script_permission
       Uprawnienia pliku skryptu. Domyślnie: 700 (rwx------).
       Przykład: script_permission = 700
     * snat_address
       Adres SNAT. Jeśli nie ustawiono, dla hostów z adresami publicznymi
       będzie użyte "-j MASQUERADE". Jeśli ustawiono zostanie użyte "-j
       SNAT --to xxx.xxx.xxx.xxx". Dotyczy lms-makeiptables. Domyślnie:
       nie ustawiony.
       Przykład: snat_address = 123.123.123.123
     * tcp_redirect_ports (udp_redirect_ports)
       Konfiguracja przekierowań w formie port_źródłowy:port_docelowy dla
       przekierowań na lokalną maszynę dla połączeń wychodzących. Dotyczy
       lms-makeipchains. Domyślnie: nie ustawione.
       Przykład: tcp_redirect_ports = 80:3128 25:25
     __________________________________________________________________

4.3.10. lms-etherdesc

   Skrypt służący do generowania pliku zawierającego MAC adresy oraz nazwy
   hostów pobierane z bazy lms'a. Adresy zapisywane są w formacie
   'stripped mac', czyli bez ":". Tego typu plik wykorzystywany jest przez
   pakiet iptraf.

   Konfigurację tego skryptu zawiera sekcja [ether] w pliku lms.ini:
     * networks
       Lista nazw sieci (oddzielonych spacjami), które mają być
       uwzględnione podczas generowania pliku. Jeśli nie ustawiono,
       zostanie stworzony konfig dla wszystkich sieci.
       Przykład: networks = public-custa public-custb
     * etherdesc_owneruid
       UID właściciela pliku. Domyślnie: 0 (root).
       Przykład: etherdesc_owneruid = 0
     * etherdesc_file
       Lokalizacja pliku. Domyślnie: /var/lib/iptraf/ethernet.desc.
       Przykład: etherdesc_file = /etc/ethernet.desc
     * etherdesc_ownergid
       GID właściciela pliku. Domyślnie: 0 (root).
       Przykład: etherdesc_ownergid = 0
     * etherdesc_permission
       Uprawnienia pliku skryptu. Domyślnie: 600 (rw-------).
       Przykład: etherdesc_permission = 600
     __________________________________________________________________

4.3.11. lms-sendinvoices

   Skrypt służy do wysyłania pocztą elektroniczną faktur, jako załączników
   do wiadomości. Faktury generowane są na podstawie szablonu dostępnego w
   lms-ui, dlatego wymagane jest podanie klienta i hasła do interfejsu www
   lms-ui.

   W odróżnieniu od pozostałych skryptów ten wymaga dodatkowych modułów
   perla: LWP::UserAgent, MIME::QuotedPrint oraz Mail::Sender.

   Konfigurację należy umieścić w sekcji [sendinvoices]:
     * lms_url
       Adres do lms-ui. Domyślnie: http://localhost/lms/
       Przykład: lms_url = http://lms.mynet.pl
     * lms_user
       Login użytkownika. Domyślnie: pusty
       Przykład: lms_user = admin
     * lms_password
       Hasło do lms-ui. Domyślnie: puste
       Przykład: lms_password = moje_hasło
     * debug_email
       Konto pocztowe do testów. Domyślnie: niezdefiniowane.
       Przykład: debug_email = admin@mynet.pl
     * sender_name
       Nadawca listu. Domyślnie: niezdefiniowany.
       Przykład: sender_name = ASK MyNet
     * sender_email
       Adres nadawcy listu. Domyślnie: niezdefiniowany.
       Przykład: sender_email = admin@mynet.pl
     * mail_subject
       Temat wiadomości. Można użyć zmiennej %invoice zastępowanej numerem
       faktury. Domyślnie: 'Invoice No. %invoice'.
       Przykład: mail_subject = 'Nowa faktura'
     * mail_body
       Treść wiadomości. Można użyć zmiennej %invoice, która zostanie
       zastąpiona numerem faktury. Domyślnie: 'Attached file with Invoice
       No. %invoice'.
       Przykład: mail_body = ''
     * customergroups
       Lista nazw grup (oddzielonych spacjami), które mają być
       uwzględnione podczas wysyłki. Domyślnie: nie ustawiona - wszystkie
       grupy.
       Przykład: customergroups = grupa1 grupa2
     * smtp_host
       Serwer SMTP, przez który ma zostać wysłana wiadomość. Domyślnie:
       localhost
       Przykład: smtp_host = smtp.mydomain.pl
     * smtp_auth
       Sposób autoryzacji. Dozwolone wartości: LOGIN, PLAIN, CRAM-MD5,
       NTLM. Domyślnie: pusta (brak autoryzacji)
       Przykład: smtp_auth = LOGIN
     * smtp_user
       Login do autoryzacji SMTP. Domyślnie: pusty
       Przykład: smtp_user = admin
     * smtp_pass
       Hasło do konta zdefiniowanego w opcji smtp_user. Domyślnie: puste
       Przykład: smtp_pass = password

   Ponadto mamy do dyspozycji jeden przydatny parametr wiersza poleceń
   --fakedate (-f). Przy jego użyciu można sprawić, aby skrypt działał z
   podmienioną datą systemową (w formacie YYYY/MM/DD), na przykład
   --fakedate=2004/10/10.
     __________________________________________________________________

4.3.12. lms-makemacs

   Skrypt służący do generowania pliku zawierającego reguły netfiltra
   filtrujące ruch klientów na bazie testu adresu źródłowego MAC. Dla
   każdego komputera generowana jest jedna reguła dla tablicy nat,
   zadanego łańcucha, testująca adres źródłowy IP pakietu oraz adres
   źródłowy MAC ramki. Jeśli testy zakończą się pozytywnie następuje
   powrót do łańcucha nadrzędnego za pomocą decyzji RETURN. Na końcu listy
   reguł dodawane są 2 reguły przekierowujące ruch http oraz webcache na
   podany w konfiguracji adres IP oraz port (z wykorzystaniem decyzji
   DNAT). Przekierowanie może odbywać się na wirtualny host www z
   zawiadomieniem klienta o zaległościach finansowych bez możliwości
   wyłączenia zawiadomienia. Na końcu dodawana jest reguła blokująca
   jakikolwiek inny ruch.

   Konfigurację tego skryptu zawiera sekcja [macs] w pliku lms.ini:
     * networks
       Lista nazw sieci (oddzielonych spacjami), które mają być
       uwzględnione podczas generowania pliku. Jeśli nie ustawiono,
       zostanie stworzony plik konfiguracyjny dla wszystkich sieci.
       Przykład: networks = public-custa public-custb
     * customergroups
       Lista nazw grup klientów (oddzielonych spacjami), które mają być
       uwzględnione podczas generowania pliku. Jeśli nie ustawiono,
       zostanie stworzony plik konfiguracyjny dla wszystkich grup
       klientów.
       Przykład: customergroups = osiedle1 osiedle2
     * iptables_binary
       Lokalizacja programu iptables. Domyślnie: /sbin/iptables
       Przykład: iptables_binary = /usr/local/sbin/iptables
     * config_owneruid
       UID właściciela pliku. Domyślnie: 0 (root).
       Przykład: config_owneruid = 0
     * config_file
       Lokalizacja pliku. Domyślnie: /etc/rc.d/rc.macs.
       Przykład: config_file = /etc/conf.d/rc.macs
     * config_ownergid
       GID właściciela pliku. Domyślnie: 0 (root).
       Przykład: config_ownergid = 0
     * config_permission
       Uprawnienia pliku skryptu. Domyślnie: 700 (rwx------).
       Przykład: config_permission = 700
     * chain
       Łańcuch do którego będą dodawane generowane reguły. Domyślnie:
       MACS.
       Przykład: chain = TESTY_MACOW
     * redirect_address
       Adres IP + port na który będzie przekierowywany niesklasyfikowany
       ruch http i webcache. Domyślnie: 127.0.0.1:80.
       Przykład: redirect_address = 192.168.1.1:3000
     * lock_noaccess
       Czy generować regułki testujące z decyzją RETURN dla komputerów,
       które są odłączone. Domyślnie: 0 (reguły są generowane)
       Przykład: lock_noaccess = 1
     __________________________________________________________________

4.3.13. lms-makehosts

   Skrypt służący do generowania pliku /etc/hosts zawierającego
   odwzorowania nazw komputerów na adresy IP.

   Konfigurację tego skryptu zawiera sekcja [hosts] w pliku lms.ini:
     * networks
       Lista nazw sieci (oddzielonych spacjami), które mają być
       uwzględnione podczas generowania pliku. Jeśli nie ustawiono,
       zostanie stworzony plik konfiguracyjny dla wszystkich sieci.
       Przykład: networks = public-custa public-custb
     * customergroups
       Lista nazw grup klientów (oddzielonych spacjami), które mają być
       uwzględnione podczas generowania pliku. Jeśli nie ustawiono,
       zostanie stworzony plik konfiguracyjny dla wszystkich grup
       klientów.
       Przykład: customergroups = osiedle1 osiedle2
     * config_owneruid
       UID właściciela pliku. Domyślnie: 0 (root).
       Przykład: config_owneruid = 0
     * config_file
       Lokalizacja pliku. Domyślnie: /etc/hosts.
       Przykład: config_file = /etc/hosts
     * config_ownergid
       GID właściciela pliku. Domyślnie: 0 (root).
       Przykład: config_ownergid = 0
     * config_permission
       Uprawnienia pliku skryptu. Domyślnie: 644 (rw-r--r--).
       Przykład: config_permission = 600
     * config_header
       Pierwsza linia w pliku /etc/hosts. Domyślnie: 127.0.0.1 localhost
       localhost.localdomain.
       Przykład: config_header = 192.168.1.1 serwer serwer.nasza-siec
     __________________________________________________________________

4.3.14. lms-makewarnings

   Skrypt służący do generowania pliku zawierającego reguły netfiltra
   przekierowujące ruch http i webcache klientów o saldzie mniejszym lub
   równym od zadanego na zadany adres IP i port (wykorzystywana jest
   tablica nat, testy adresów źródłowych IP oraz decyzja DNAT).

   Konfigurację tego skryptu zawiera sekcja [warnings] w pliku lms.ini:
     * networks
       Lista nazw sieci (oddzielonych spacjami), które mają być
       uwzględnione podczas generowania pliku. Jeśli nie ustawiono,
       zostanie stworzony plik konfiguracyjny dla wszystkich sieci.
       Przykład: networks = public-custa public-custb
     * customergroups
       Lista nazw grup klientów (oddzielonych spacjami), które mają być
       uwzględnione podczas generowania pliku. Jeśli nie ustawiono,
       zostanie stworzony plik konfiguracyjny dla wszystkich grup
       klientów.
       Przykład: customergroups = osiedle1 osiedle2
     * iptables_binary
       Lokalizacja programu iptables. Domyślnie: /sbin/iptables
       Przykład: iptables_binary = /usr/local/sbin/iptables
     * config_owneruid
       UID właściciela pliku. Domyślnie: 0 (root).
       Przykład: config_owneruid = 0
     * config_file
       Lokalizacja pliku. Domyślnie: /etc/rc.d/rc.warnings.
       Przykład: config_file = /etc/conf.d/rc.warnings
     * config_ownergid
       GID właściciela pliku. Domyślnie: 0 (root).
       Przykład: config_ownergid = 0
     * config_permission
       Uprawnienia pliku skryptu. Domyślnie: 700 (rwx------).
       Przykład: config_permission = 700
     * chain
       Łańcuch do którego będą dodawane generowane reguły. Domyślnie:
       WARNINGS.
       Przykład: chain = OSTRZEZENIA
     * redirect_address
       Adres IP + port na który będzie przekierowywany ruch http i
       webcache pochodzący z hostów dla których ma być włączone
       ostrzeżenie o zaległościach finansowych. Domyślnie: 127.0.0.1:80.
       Przykład: redirect_address = 192.168.1.1:3001
     * limit
       Saldo klienta przy którym dla wszystkich komputerów klienta
       generowane są regułki przekierowujące ruch. Domyślnie: 0
       Przykład: limit = -85
     __________________________________________________________________

4.3.15. lms-makemessages

   Skrypt służący do generowania pliku zawierającego reguły netfiltra
   przekierowujące ruch http i webcache klientów dla których zostało
   włączone przekazywanie wiadomości administracyjnej (ostrzeżenia) na
   zadany adres IP i port (wykorzystywana jest tablica nat, testy adresów
   źródłowych IP oraz decyzja DNAT).

   Konfigurację tego skryptu zawiera sekcja [messages] w pliku lms.ini:
     * networks
       Lista nazw sieci (oddzielonych spacjami), które mają być
       uwzględnione podczas generowania pliku. Jeśli nie ustawiono,
       zostanie stworzony plik konfiguracyjny dla wszystkich sieci.
       Przykład: networks = public-custa public-custb
     * customergroups
       Lista nazw grup klientów (oddzielonych spacjami), które mają być
       uwzględnione podczas generowania pliku. Jeśli nie ustawiono,
       zostanie stworzony plik konfiguracyjny dla wszystkich grup
       klientów.
       Przykład: customergroups = osiedle1 osiedle2
     * iptables_binary
       Lokalizacja programu iptables. Domyślnie: /sbin/iptables
       Przykład: iptables_binary = /usr/local/sbin/iptables
     * config_owneruid
       UID właściciela pliku. Domyślnie: 0 (root).
       Przykład: config_owneruid = 0
     * config_file
       Lokalizacja pliku. Domyślnie: /etc/rc.d/rc.messages.
       Przykład: config_file = /etc/conf.d/rc.messages
     * config_ownergid
       GID właściciela pliku. Domyślnie: 0 (root).
       Przykład: config_ownergid = 0
     * config_permission
       Uprawnienia pliku skryptu. Domyślnie: 700 (rwx------).
       Przykład: config_permission = 700
     * chain
       Łańcuch do którego będą dodawane generowane reguły. Domyślnie:
       MESSAGES.
       Przykład: chain = WIADOMOSCI
     * redirect_address
       Adres IP + port na który będzie przekierowywany ruch http i
       webcache pochodzący z hostów dla których ma być włączona wiadomość
       administracyjna. Domyślnie: 127.0.0.1:80.
       Przykład: redirect_address = 192.168.1.1:3002
     __________________________________________________________________

4.3.16. lms-fping

   Skrypt zapisuje do bazy informacje o aktywności komputerów. Do
   skanowania wykorzystywany jest szybki program fping (z opcjami -ar1).
   Najpierw tworzona jest lista hostów, a następnie, po wywołaniu fping'a,
   komputerom włączonym zostaje przypisana data i czas skanowania. Dzięki
   temu mamy w bazie informacje kiedy dany komputer był ostatnio włączony.

   Konfigurację tego skryptu zawiera sekcja [fping] w pliku lms.ini:
     * networks
       Lista nazw sieci (oddzielonych spacjami), które mają być
       uwzględnione podczas skanowania. Jeśli nie ustawiono przeskanowane
       zostaną wszystkie komputery.
       Przykład: networks = public-custa public-custb
     * fping_binary
       Lokalizacja programu fping. Domyślnie: /usr/sbin/fping
       Przykład: fping_binary = /usr/local/sbin/fping
     * temp_file
       Lokalizacja pliku tymczasowego na listę hostów, który po wykonaniu
       skryptu zostaje usunięty. Domyślnie: /tmp/fping_hosts.
       Przykład: temp_file = /tmp/hosts
     __________________________________________________________________

4.3.17. lms-reminder

   Skrypt służy do przypominania klientom o zaplanowanych na dany dzień
   zadaniach. Lista zdarzeń przypisanych w Terminarzu danemu klientowi w
   bieżącym dniu zostaje wysłana na jego adres e-mail.

   Konfigurację dla lms-reminder umieszcza się w pliku lms.ini w sekcji
   [reminder]. Możesz tam ustawić następujące parametry:
     * mailsubject (wymagany)
       Pozwala na ustalenie tematu e-maila wysyłanego do klienta.
       Domyślnie: nie ustawione.
       Przykład: mailsubject = 'Terminarz z LMSa'
     * mailfrom (wymagany)
       Adres e-mail z którego zostanie wysłany e-mail. Proszę pamiętać, że
       na niektórych MTA (np. exim) konto to musi istnieć w systemie.
       Domyślnie nieustawione.
       Przykład: mailfrom = staff@domain.pl
     * mailfname (wymagany)
       Nazwa nadawcy maila.
       Przykład: mailfname = LMS
     * smtp_host
       Serwer SMTP, przez który ma zostać wysłana wiadomość. Domyślnie:
       localhost
       Przykład: smtp_host = smtp.mydomain.pl
     * smtp_auth
       Sposób autoryzacji. Dozwolone wartości: LOGIN, PLAIN, CRAM-MD5,
       NTLM. Domyślnie: pusta (brak autoryzacji)
       Przykład: smtp_auth = LOGIN
     * smtp_user
       Login do autoryzacji SMTP. Domyślnie: pusty
       Przykład: smtp_user = admin
     * smtp_pass
       Hasło do konta zdefiniowanego w opcji smtp_user. Domyślnie: puste
       Przykład: smtp_pass = password
     * debug_email (opcjonalny)
       Adres e-mail do debugowania. Gdy ustawiony, cała poczta zostaje
       wysłana na dany email zamiast do klientów. Przydatne do debugowania
       i sprawdzania czy wszystko działa OK. Domyślnie: nie ustawiony.
       Przykład: debug_email = alec@domain.pl
     __________________________________________________________________

4.3.18. lms-rtparser

   Jest to tzw. backend dla systemu Helpdesk, czyli skrypt który
   współpracując z serwerem pocztowym zapisuje do bazy danych wszystkie
   wiadomości skierowane na adresy Helpdesk'a. Skrypt pobiera z wejścia
   wiadomość pocztową, parsuje zawartość i umieszcza zgłoszenie w kolejce,
   wysyłając do zgłaszających potwierdzenie przyjęcia wiadomości. W
   temacie potwierdzenia znajduje się symbol zgłoszenia. Podczas
   parsowania wiadomości następuje, na podstawie tematu wiadomości, próba
   rozpoznania czy wiadomość nie jest odpowiedzią na inną wiadomość z już
   przypisanym numerem zgłoszenia. Od wiadomości zostają odłączone
   załączniki i umieszczone w katalogu zdefiniowanym w opcji mail_dir.

   Oprócz modułów Perla standardowo wymaganych przez resztę skryptów,
   należy zainstalować także moduły MIME::Parser i MIME::Words z pakietu
   MIME-Tools oraz Mail::Sender i Text::Iconv.

   Skrypt można uruchamiać na wiele sposobów. Jednym z nich jest
   stworzenie skryptu powłoki, który odczytując skrzynkę pocztową wywoła
   lms-rtparser dla każdego maila. Wygodniejszym zastosowaniem jest jednak
   zintegrowanie go z serwerem pocztowym. Poniżej przedstawiono sposób
   podłączenia go do postfixa przy użyciu opcji header_checks.
# plik main.cf:
header_checks = regexp:/etc/postfix/header_checks

# plik header_checks
/^To:.*adres@domena.*/ FILTER filter:dummy

# plik master.cf:
filter unix - n n - 10 pipe
      -flags=Rq user=nobody argv=/path/to/lms-rtparser

   Powyższy sposób działa dla postfixa w wersjach nowszych od 2.0.
   Wcześniejsze wersje nie obsługują FILTER w header_checks. Z tym
   problemem można sobie poradzić używając procmaila:
# plik main.cf
mailbox_command = /usr/bin/procmail

# w katalogu domowym klienta, którego maile mają być obsługiwane przez HelpDesk:
# plik .forward
"|IFS=' ' && exec /usr/bin/procmail -f - || exit 75 #YOUR_USERNAME"

# plik .procmailrc
:0 c
   * ^To.*adres@domena
   | /bin/lms-rtparser

:0 A
$DEFAULT

   Kolejny listing to przykład podłączenia parsera do Exima przy użyciu
   filtrów systemowych:
# plik exim.conf

system_filter_pipe_transport = address_pipe

# plik system_filter.txt

if $recipients is "adres_kolejki@domena.pl"
then
     pipe "/sciezka/do/lms-rtparser -q id_kolejki"
endif

   Notatka

   Jeżeli chcesz aby wiadomości wprowadzane poprzez lms-ui były kierowane
   do parsera, zamiast bezpośrednio zapisywane do bazy, powinieneś włączyć
   opcję konfiguracyjną helpdesk_backend_mode w sekcji [phpui].

   Konfigurację tego skryptu zawiera sekcja [rt] w pliku lms.ini:
     * default_queue
       Numer ID kolejki, do której trafią zgłoszenia. Jeśli nie podano,
       Kolejka zostanie odszukana na podstawie adresu odbiorcy maila.
       Opcja ta może zostać nadpisana przy pomocy parametru -q przy
       uruchomieniu skryptu. Domyślnie: niezdefiniowana.
       Przykład: default_queue =
     * mail_from
       Nadawca potwierdzenia (adres). Jeśli nie zdefiniowano, zostanie
       użyty adres kolejki do której zapisano zgłoszenie. Domyślnie:
       pusty.
       Przykład: mail_from = rt@net.pl
     * mail_from_name
       Nadawca potwierdzenia (nazwa). Domyślnie: niezdefiniowana.
       Przykład: mail_from_name = 'BOK SuperLAN'
     * autoreply_subject
       Temat potwierdzenia. Tu można korzystać ze zmiennych %tid -
       identyfikator zgłoszenia i %subject - temat zgłoszenia. Domyślnie:
       "[RT#%tid] Receipt of request '%subject'".
       Przykład: autoreply_subject = "[RT#%tid] Potwierdzenie odbioru
       zgłoszenia o temacie '%subject'"
     * autoreply_body
       Treść potwierdzenia. Tu można korzystać ze zmiennych: %tid -
       identyfikator zgłoszenia i %subject - temat zgłoszenia. Domyślnie:
       "Your request was registered in our system.\nTo this request was
       assigned ticket identifier RT#%tid.\nPlease, place string [RT#%tid]
       in subject field of any\nmail relating to this request.\n."
       Example: autoreply_body = "Państwa zgłoszenie zostało
       zarejestrowane w naszym systemie.\nZgłoszeniu został nadany numer:
       RT#%tid.\nW korespondencji związanej z tym zgłoszeniem prosimy
       podawać w temacie ciąg znaków [RT#%tid].\n"
     * smtp_host
       Serwer SMTP, przez który ma zostać wysłana wiadomość. Domyślnie:
       localhost
       Przykład: smtp_host = smtp.mydomain.pl
     * smtp_auth
       Sposób autoryzacji. Dozwolone wartości: LOGIN, PLAIN, CRAM-MD5,
       NTLM. Domyślnie: pusta (brak autoryzacji)
       Przykład: smtp_auth = LOGIN
     * smtp_user
       Login do autoryzacji SMTP. Domyślnie: pusty
       Przykład: smtp_user = admin
     * smtp_pass
       Hasło do konta zdefiniowanego w opcji smtp_user. Domyślnie: puste
       Przykład: smtp_pass = password
     * mail_dir
       Katalog w którym zostaną zapisane załączniki. Katalog ten powinien
       być dostępny dla apache'a i klienta uruchamiającego lms-rtparser.
       Gdy nie ustawiono, załączniki zostaną utracone. Domyślnie:
       niezdefiniowany.
       Przykład: mail_dir = /usr/local/lms/mail
     * tmp_dir
       Katalog tymczasowy. Domyślnie zostanie użyty katalog zdefiniowany w
       zmiennej systemowej lub /tmp.
       Przykład: tmp_dir = /home/user/tmp
     * auto_open
       Włączenie tej opcji spowoduje, że w momencie odebrania wiadomości
       dotyczącej zgłoszenia zamkniętego (lub martwego) zgłoszenie to
       zostanie otwarte. Domyślnie: wyłączone.
       Przykład: auto_open = 1
     * newticket_notify
       Włączenie tej opcji spowoduje wysyłanie powiadomień o nowych
       zgłoszeniach do użytkowników którzy mają prawa do konkretnej
       kolejki. Domyślnie: wyłączone.
       Przykład: newticket_notify = 1
     * lms_url
       Do powiadomienia o nowym zgłoszeniu zostaje załączony link do tego
       zgłoszenia w LMS-UI, aby użytkownik mógł szybko przejść do tego
       zgłoszenia. Domyślnie: http://localhost/lms/.
       Przykład: lms_url = https://lms.domena.pl/
     * include_customerinfo
       Do powiadomienia o nowym zgłoszeniu zostają załączone podstawowe
       dane klienta, jeżeli został on rozpoznany po adresie mailowym.
       Domyślnie: włączona.
       Przykład: include_customerinfo = 0
     __________________________________________________________________

4.3.19. lms-teryt

   Skrypt służący do importu i aktualizacji danych bazy TERYT. Zawiera
   również możliwość pobrania plików bazy z Internetu, a także procedurę
   przypisywania identyfikatorów TERYT do istniejących komputerów, które
   mają zdefiniowany adres ale nie mają przypisanego TERYTu.

   Skrypt zawiera następujące opcje uruchomieniowe, które można łączyć:
     * -f, --fetch
       Włącza procedurę pobierania (i rozpakowania) plików bazy TERYT z
       Internetu. Wymagane jest umożliwienie połączenia HTTP z serwerem
       określonych w opcji 'url' oraz zainstalowanie programu unzip.
     * -l, --list=<lista>
       Zawęża działanie opcji importu/aktualizacji do określonych
       województw. Podobnie jak w opcji konfiguracyjnej 'state_list'
       podajemy tutaj numeryczne identyfikatory oddzielone przecinkami. Ze
       względu na duży rozmiar całej bazy, wskazane jest ograniczenie się
       tylko do wybranych województw. Identyfikatory można znaleźć w pliku
       TERC.xml.

       2 - dolnośląskie
       4 - kujawsko-pomorskie
       6 - lubelskie
       8 - lubuskie
       10 - łódzkie
       12 - małopolskie
       14 - mazowieckie
       16 - opolskie
       18 - podkarpackie
       20 - podlaskie
       22 - pomorskie
       24 - śląskie
       26 - świętokrzyskie
       28 - warmiśko-mazurskie
       30 - wielkopolskie
       32 - zachodniopomorskie
     * -u, --update
       Import danych do bazy LMSa. Jeśli baza była już wcześniej
       importowana, nastąpi aktualizacja bazy.
     * -m, --merge
       Przypisanie identyfikatorów TERYT dla komputerów/urządzeń, które
       nie zostały jaszcze przypisane, ale posiadają wpisany adres
       lokalizacji. Algorytm jest dość prosty i nie ma pewności, że
       wszystkie adresy zostaną rozpoznane.

   Konfigurację tego skryptu zawiera sekcja [teryt] w pliku lms.ini:
     * url
       Adres strony pobierania plików bazy TERYT. Domyślnie zawiera
       poniższy link.
       Przykład: url =
       http://www.stat.gov.pl/broker/access/prefile/listPreFiles.jspa
     * dir
       Katalog w którym, są przechowywane rozpakowane pliki (xml) bazy
       TERYT. W tym katalogu zostaną też zapisane pobrane pliki.
       Domyślnie: katalog uruchomienia skryptu.
       Przykład: dir = /var/lib/teryt
     * unzip_binary
       Lokalizacja programu unzip. Domyślnie: /usr/bin/unzip.
       Przykład: unzip_binary = /sbin/unzip
     * state_list
       Lista identyfikatorów województw, oddzielonych przecinkami, które
       będą brane pod uwagę podczas importu. W celu minimalizacji rozmiaru
       bazy danych i czasu działania skryptu najlepiej ograniczyć się do
       wybranych województw.
       Przykład: state_list = 2
     __________________________________________________________________

Rozdział 5. Generator plików konfiguracyjnych (lms-mgc)

   LMS-MGC to "magiczny" generator plików konfiguracyjnych. Przy odrobinie
   wysiłku można stworzyć przy jego pomocy dowolnego rodzaju plik
   konfiguracyjny (np. generujący odpowiednie strefy dla DNS)
     __________________________________________________________________

5.1. Instalacja

   Lms-mgc posiada własny plik konfiguracyjny: lms-mgc.ini. Jego
   instalacja polega na przeniesieniu do katalogu /usr/sbin. Uruchomienie
   generatora można wykonać na dwa sposoby: wpisać do crona (np. co
   godzinę)
0 * * * *       /usr/sbin/lms-mgc 1 > /dev/null

   albo z poziomu LMS skorzystać z menu "Przeładowanie". Druga metoda
   wymaga użycia sudo. Niestety, jedyne wyjście by umożliwić uruchomienie
   lms-mgc, to dopisanie użytkownika do sudo, a następnie ustawienie w
   sekcji konfiguracyjnej [phpui]:

   reload_type = exec

   reload_execcmd = sudo /usr/sbin/lms-mgc

   Lms-mgc posiada następujące opcje uruchomienia:
-C, --config-file=/path/lms-mgc.ini alternatywny plik konfiguracyjny
                                    (default: /etc/lms/lms-mgc.ini);
-i, --instances=name                nazwa (lub numer) instancji do uruchomienia, bez czytania
                                    konfiguracji z lms-mgc.ini, np. -i "name1 name2"
-h, --help                          wyświetla pomoc;
-v, --version                       wyświetla numer wersji;
-q, --quiet                         tylko komunikaty o błędach;
-d, --debug                         informacje szczegółowe dla każdego IP;
     __________________________________________________________________

5.2. Konfiguracja

   Konfigurację dla LMS-MGC przeprowadza się w pliku lms-mgc.ini
     __________________________________________________________________

5.2.1. Sekcja [database] - ustawienia bazy danych

     * type
       Typ bazy danych. Aktualnie w 100% supportowany jest 'mysql', ale
       jak na razie nie widać większych problemów z 'postgres'. Domyślnie:
       mysql
       Przykład: type = mysql
     * host
       Host gdzie zainstalowana jest baza danych. Najczęściej, localhost,
       ale można tutaj wstawić cokolwiek (ipek, domena, path to socketa w
       formacie 'localhost:/path/to/socket'). Domyślnie: localhost
       Przykład: host = localhost
     * user
       Użytkownik do bazy danych. W wielu wypadkach (jeżeli postępowałeś
       zgodnie ze wskazówkami w doc/INSTALL) będzie to 'lms'. Jeżeli
       chcesz używać konta uprzywilejowanego, prawdopodobnie wpiszesz
       'root' (MySQL na większości *nixów), 'mysql' (na PLD) bądź
       'postgres' (PostgreSQL). Domyślnie: root
       Przykład: user = mysql
     * password
       Hasło do bazy danych. Domyślnie puste.
       Przykład: password = tajne_haslo
     * database
       Nazwa bazy danych, domyślnie lms.
       Przykład: database = lms
     __________________________________________________________________

5.2.2. Sekcja [mgc] - lista instancji

   Właściwa konfiguracja dotycząca generatorów poszczególnych plików
   konfiguracyjnych jest umieszczana w sekcji [mgc] i pochodnych. W samej
   sekcji [mgc] możemy użyć następującego parametru:
     * instances
       Lista "instancji" oddzielona spacjami.
       Przykład: instances = dhcp firewall squid

   Notatka

           Zmienną instances można także umieścić w sekcji dowolnej instancji.
           Patrz niżej.
     __________________________________________________________________

5.2.3. Sekcja [mgc:xxx] - konfiguracja instancji

   Każda instancja ma swoją nazwę i jej konfigurację tworzy się
   umieszczając sekcję o nazwie [mgc:nazwa], czyli przykładowo:
   [mgc:mydaemon]

   W samych instancjach możemy używać następujących opcji
   konfiguracyjnych:
     * instances
       Zmienna, w której możesz podać listę innych instancji, aby
       następnie wywoływać mgc poleceniem 'lms-mgc -i sekcja' zamiast
       'lms-mgc -i "sekcja1 sekcja2 sekcja3"'. Jeśli zostanie użyta,
       wszystkie pozostałe zmienne tej sekcji zostaną zignorowane.
       Przykład: instances = dns1 dns2 dns3
     * outfile
       Definiuje plik do którego ma być zapisany wynik działania bieżącej
       instancji (jeżeli ta zmienna będzie nie ustawiona, instancja się
       zakończy)
       Przykład: outfile = /etc/somefile
     * append
       Pozwala ustawić aby wynik działania instancji nie nadpisywał pliku
       wynikowego, lecz został dopisany na jego końcu
       Przykład: append = 1
     * outfile_perm
       Pozwala na ustawienie praw dostępu do pliku wyjściowego (domyślnie
       600)
       Przykład: outfile_perm = 700
     * outfile_owner
       Pozwala na ustawienie właściciela pliku wyjściowego (domyślnie 0)
       Przykład: outfile_owner = 0

       Ostrzeżenie

                   Właściciel musi być podany numerycznie!
     * outfile_group
       Pozwala na ustawienie grupy pliku wyjściowego (domyślnie 0)
       Przykład: outfile_group = 0

       Ostrzeżenie

                   Grupa musi być podana numerycznie!
     * header_file
       Pozwala na umieszczenie w pliku wynikowym zawartości innego pliku
       jako nagłówek (domyślnie nie ustawione)
       Przykład: header_file = /etc/lms/myservice_header
     * header
       Pozwala na umieszczenie w pliku wynikowym zawartości zmiennej jako
       nagłówka (domyślnie puste)
       Przykład: header = option1 = bla\noption2 = blabla

       Notatka

               Znak \n został tu użyty jako separator linii. Końcowe \n nie jest
               wymagane.
     * customergroups
       Pozwala ustalić które z grup klienckich będą uwzględniane w pliku
       konfiguracyjnym (domyślnie wszystkie)
       Przykład: customergroups = grupa1 grupa2
     * excluded_customergroups
       Pozwala ustalić które z grup klientów mają zostać wyłączone z pliku
       konfiguracyjnego (domyślnie żadna)
       Przykład: excluded_customergroups = grupa3 grupa4
     * networks
       Pozwala ustalić które z naszych sieci będą uwzględniane w pliku
       konfiguracyjnym (domyślnie wszystkie)
       Przykład: networks = cust1-publ cust2-publ cust3-priv
     * excluded_networks
       Pozwala ustalić które z naszych sieci będą wyłączone z pliku
       konfiguracyjnego (domyślnie żadna)
       Przykład: excluded_networks = cust4-publ cust5-publ

   Teraz mgc pobiera kolejne sieci i wykonuje w kółko następujące
   czynności:
     * network_header
       Generuje nagłówek dla każdej sieci (domyślnie puste):
       Przykład: network_header = network %ADDR/%MASK { # Config section
       for %NAME
     * dst_networks
       Pozwala ustawić sieci docelowe, czyli takie dla których będzie
       przetwarzany parametr: dst_network_header (domyślnie wszystkie):
       Przykład: dst_networks = main coalloc
     * dst_network_header
       Pozwala ustawić nagłówek dla sieci docelowych
       Przykład: dst_network_header = \tallow to %DADDR/%DMASK;
     * network_body
       Parametr jest przetwarzany po wysłaniu nagłówków dla sieci, a przed
       rozpoczęciem analizy adresów IP
       Przykład: network_body = \tnodes {

   Teraz MGC rozpocznie przetwarzanie regułek dla kolejnych adresów IP.
   Robi to w dosyć specyficzny sposób. Tzn. oblicza kolejny adres IP i
   sprawdza czy zdefiniowano regułę dla hosta i wykonuje pierwszą.
   Sprawdzanie jest wykonywane w następującej kolejności:
     * ignore
       Pozwala na ustawienie listy adresów w postaci adres/prefix lub
       adres/maska oddzielanej spacjami dla której ma być ignorowane
       generowanie
       Przykład: ignore = 192.168.0.100/32
     * node(IP)
       Przy pomocy tej opcji można zdefiniować regułę dla wybranego
       komputera. W nawiasie podaje się jego adres IP. Każda sekcja
       instancji może zawierać dowolną ilość takich opcji.
       Przykład: node(192.168.0.20) = ??
     * allnodes
       Pozwala na ustawienie regułki przetwarzanej dla każdego kolejnego
       adresu IP.
       Przykład: allnodes = ??
     * allexistnodes
       Pozwala na ustawienie regułki przetwarzanej dla każdego kolejnego
       adresu IP który jest używany.
       Przykład: allexistnodes = ??
     * netdevnode
       Pozwala na ustawienie regułki przetwarzanej dla każdego kolejnego
       adresu IP urządzenia sieciowego.
       Przykład: netdevnode = ??
     * grantednode_priv
       Jest przetwarzana gdy dany adres komputer z danym adresem IP
       istnieje, ale w lms-ui ma status "podłączony" (regułka przetwarzana
       dla adresów prywatnych)
       Przykład: grantednode_priv = \t\tnode %NAME (%IP/%MAC) unique %ID;
     * grantednode_publ
       Jest przetwarzana gdy dany adres komputer z danym adresem IP
       istnieje, ale w lms-ui ma status "podłączony" (regułka przetwarzana
       dla adresów publicznych)
       Przykład: grantednode_publ = \t\tnode %NAME (%IP/%MAC) unique %ID;
     * deniednode_priv
       Jest przetwarzana gdy dany adres komputer z danym adresem IP
       istnieje, ale w lms-ui ma status "odłączony" (regułka przetwarzana
       dla adresów prywatnych)
       Przykład: deniednode_priv = node %NAME (%IP/%MAC) unique %ID deny;
     * deniednode_publ
       Jest przetwarzana gdy dany adres komputer z danym adresem IP
       istnieje, ale w lms-ui ma status "odłączony" (regułka przetwarzana
       dla adresów publicznych)
       Przykład: deniednode_publ = node %NAME (%IP/%MAC) unique %ID deny;
     * dhcpnode_priv
       Jest przetwarzana gdy dany adres IP zawiera się w zakresie DHCP
       (regułka przetwarzana dla adresów prywatnych)
       Przykład: dhcpnode_priv = node unknown (%IP) reject;
     * dhcpnode_publ
       Jest przetwarzana gdy dany adres IP zawiera się w zakresie DHCP
       (regułka przetwarzana dla adresów publicznych)
       Przykład: dhcpnode_publ = node unknown (%IP) reject;
     * freeip_priv
       Jest przetwarzana gdy dany adres IP nie jest przypisany do żadnego
       komputera (regułka przetwarzana dla adresów prywatnych)
       Przykład: freeip_priv = node unknown (%IP) lock_as_unused;
     * freeip_publ
       Jest przetwarzana gdy dany adres IP nie jest przypisany do żadnego
       komputera (regułka przetwarzana dla adresów publicznych)
       Przykład: freeip_publ = node unknown (%IP) lock_as_unused;
     * default_priv
       Regułka domyślna. Jest przetwarzana gdy adres nie zostanie
       przetworzony przez żadną regułkę grantednode lub deniednode
       (regułka przetwarzana dla adresów prywatnych)
       Przykład: default_priv = node unknown (%IP) lock_as_intruder;

   Notatka

           lms-mgc sam rozpoznaje który adres należy do puli publicznej, a który
           do prywatnej.
     * default_publ
       Regułka domyślna. Jest przetwarzana gdy adres nie zostanie
       przetworzony przez żadną regułkę grantednode lub deniednode
       (regułka przetwarzana dla adresów publicznych)
       Przykład: default_publ = node unknown (%IP) lock_as_intruder;

   W końcu następuje wygenerowanie końcowej części pliku i wykonanie
   polecenia systemowego.
     * network_footer
       Pozwala na ustawienie stopki dla właśnie przetwarzanej sieci
       Przykład: network_footer = ??
     * footer_file
       Pozwala na umieszczenie w pliku wynikowym zawartości innego pliku
       jako stopka (domyślnie nie ustawione)
       Przykład: footer_file = /etc/lms/myservice_footer
     * footer
       Pozwala na umieszczenie w pliku wynikowym zawartości zmiennej jako
       stopki (domyślnie puste)
       Przykład: footer = # End.
     * post_exec
       Komenda do wywołania po wygenerowaniu pliku konfiguracyjnego
       Przykład: post_exec = killall -HUP mydaemon
     __________________________________________________________________

5.2.4. Zmienne konfiguracyjne

   W opcjach konfiguracyjnych można używać następujących zmiennych, które
   zostaną podmienione na odpowiednie dane z bazy:

   Zmienne dla komputerów:
     * %IP - adres IP komputera
     * %PUBIP - drugi (publiczny) adres IP komputera
     * %PIN - pin klienta posiadającego dany komputer
     * %ID - ID komputera w bazie
     * %MAC - adres MAC karty sieciowej
     * %SMAC - adres MAC pisany małymi literami z usuniętymi dwukropkami
     * %CMAC - adres MAC pisany w formacie CISCO (FFFF.FFFF.FFFF)
     * %OWNER - ID właściciela komputera
     * %CUSTOMER - nazwisko i imię właściciela komputera
     * %NAME - nazwa komputera dużymi znakami
     * %name - nazwa komputera małymi znakami
     * %INFO - opis komputera
     * %PASSWD - hasło komputera
     * %PORT - port urządzenia, do którego podłączony jest komputer
     * %UPRATE - gwarantowany transfer dla danych wychodzących
     * %NUPRATE - gwarantowany transfer dla danych wychodzących (dla
       godzin nocnych)
     * %DOWNRATE - gwarantowany transfer dla danych przychodzących
     * %NDOWNRATE - gwarantowany transfer dla danych przychodzących (dla
       godzin nocnych)
     * %UPCEIL - maksymalny transfer dla danych wychodzących
     * %NUPCEIL - maksymalny transfer dla danych wychodzących (dla godzin
       nocnych)
     * %DOWNCEIL - maksymalny transfer dla danych przychodzących
     * %NDOWNCEIL - maksymalny transfer dla danych przychodzących (dla
       godzin nocnych)
     * %CLIMIT - limit równoczesnych połączeń
     * %NCLIMIT - limit równoczesnych połączeń (dla godzin nocnych)
     * %PLIMIT - limit pakietów
     * %NPLIMIT - limit pakietów (dla godzin nocnych)
     * %1 %2 %3 %4 - kolejne oktety (od lewej) adresu IP
     * %NID - ID sieci, do której należy komputer
     * %NNAME - nazwa sieci dużymi znakami
     * %nname - nazwa sieci małymi znakami
     * %NADDR - adres sieci
     * %NIFACE - interfejs sieci
     * %NMASK - maska sieci
     * %NGATE - adres bramy
     * %NDNS - adres serwera DNS
     * %NDNS2 - adres drugiego serwera DNS
     * %NDOMAIN - domena sieci
     * %NWINS - adres serwera WINS dla tej sieci
     * %NDHCPS - pierwszy adres DHCP sieci
     * %NDHCPE - ostatni adres DHCP sieci

   Zmienne dla sieci (w opcjach dotyczących tylko sieci):
     * %ID - ID sieci w bazie
     * %NAME - nazwa sieci dużymi znakami
     * %name - nazwa sieci małymi znakami
     * %ADDR - adres sieci
     * %IFACE - interfejs
     * %MASK - maska
     * %GATE - brama sieci
     * %DNS - serwer DNS tej sieci
     * %DNS2 - drugi serwer DNS tej sieci
     * %DOMAIN - domena tej sieci
     * %WINS - adres serwera WINS dla tej sieci
     * %DHCPS - pierwszy adres DHCP tej sieci
     * %DHCPE - ostatni adres DHCP tej sieci

   Notatka

           W opcji konfiguracyjnej dst_network_header można ponadto użyć
           powyższych zmiennych ale poprzedzonych literą D (np. %DADDR, %dname)
           jako parametry sieci docelowych.

   Zmienne które można stosować we wszystkich opcjach:
     * %DATE - data w formacie YYYYMMDD;
     * %TIME - czas w formacie HHMM;
     * %TIMES - czas w formacie HHMMSS;
     * %UTIME - czas w formacie unix timestamp;
     __________________________________________________________________

5.3. Przykład zastosowania lms-mgc

   Konfiguracja i zasada działania lms-mgc może się wydawać dość zawiła,
   dlatego posłużymy się przykładem. Poniżej przedstawiono sposób
   generowania i uruchamiania firewalla ipchains (bardzo prostego).

   Przykład 5-1. Lms-mgc: Przykład instancji

   Zacznij od utworzenia nowej sekcji mgc w lms-mgc.ini, nazywając ją
   'ipchains' i stwórz w tej sekcji prostą maskaradę per adres IP z lanu:
[mgc:ipchains]
outfile           = /etc/rc.d/rc.masq
outfile_perm      = 700
header            = #!/bin/sh\n/sbin/ipchains -F\n/sbin/ipchains -X\n/sbin/ipchains -P forward DENY
grantednode_priv  = /sbin/ipchains -A forward -s %IP -j MASQ
post_exec         = /etc/rc.d/rc.masq

   Dopiszmy także do sekcji głównej mgc informację żeby mgc uruchamiał tą
   sekcję:
[mgc]
instances         = ipchains

   Teraz próba odpalenia lms-mgc powinna zaowocować wygenerowaniem
   /etc/rc.d/rc.masq, oraz jego odpaleniem.
     __________________________________________________________________

Rozdział 6. LMS Daemon

6.1. Informacje podstawowe

   Napisany w języku C program ma ułatwiać zarządzanie usługami. Sam demon
   odpowiada za uruchamianie odpowiednich modułów na żądanie użytkownika.
   Moduły natomiast, służą do tworzenia plików konfiguracyjnych na
   podstawie danych z bazy LMS'a oraz restartowania odpowiednich usług na
   serwerze. Spełniają także inne funkcje np. zbieranie statystyk, badanie
   aktywności hostów, naliczanie opłat, powiadamianie o zaległościach.
     __________________________________________________________________

6.1.1. Wymagania

   Oto lista rzeczy, które lmsd potrzebuje już na etapie kompilacji:
     * interfejs użytkownika LMS-UI
     * libmysqlclient (tj. pełnej instalacji MySQL'a lub odpowiedniego
       pakietu) lub libpq w przypadku bazy PostgreSQL
     * libdl (to w każdej dzisiejszej dystrybucji jest)
     * kompilator języka C (testowany na gcc-2.95.x i nowszych)
     * moduł ggnotify wymaga biblioteki libgadu i jej plików nagłówkowych
     * moduł parser wymaga pakietu bison w wersji 1.875 lub nowszej oraz
       pakietu flex
     * moduły ewx-* wymagają biblioteki net-snmp i jej plików
       nagłówkowych.
     __________________________________________________________________

6.1.2. Instalacja

   Przed kompilacją należy przy pomocy skryptu ./configure ustalić opcje
   przedstawione na poniższym listingu (w nawiasach podano wartości
   domyślne opcji):
  --help                pomoc
  --enable-debug0       logowanie zapytań SQL (wyłączone)
  --enable-debug1       logowanie zdarzeń (wyłączone)
  --with-pgsql          gdy korzystasz z bazy PostgreSQL (wyłączone)
  --with-mysql          gdy korzystasz z bazy MySQL (włączone)
  --prefix=PREFIX       docelowy katalog instalacyjny demona i modułów (/usr/local)
  --lmsbindir=DIR       docelowa lokalizacja binarki lmsd (PREFIX/lms/bin)
  --lmslibdir=DIR       docelowa lokalizacja modułów lmsd (PREFIX/lms/lib)
  --libdir=DIR          lokalizacja bibliotek bazy danych (/usr/lib)
  --incdir=DIR          lokalizacja plików nagłówkowych bazy danych (/usr/include)
  --inifile=FILE        plik konfiguracyjny - wyłącza konfigurację przez UI

   Zatem wymagane jest określenie bazy z jakiej będziemy korzystać
   (-with-mysql lub -with-pgsql) oraz położenia bibliotek dostarczanych
   wraz z bazą (--incdir, --libdir). Możliwe jest zmuszenie demona do
   korzystania z plików konfiguracyjnych zamiast bazy danych. Nie jest
   możliwe używanie obu sposobów przechowywania konfiguracji, dlatego
   należy o tym zdecydować przed kompilacją.
# ./configure --with-pgsql --libdir=/usr/local/pgsql/lib --incdir=/usr/local/pgsql/include

   Następnie kompilacja i instalacja (umieszczenie demona w katalogu
   określonym zmienną --prefix):
# make && make install

   Skompilowane moduły (pliki z rozszerzeniem .so), znajdujące się w
   katalogu modules/nazwa_modułu zostają umieszczone w katalogu
   PREFIX/lms/lib, a główny program (lmsd) w katalogu PREFIX/lms/bin.
     __________________________________________________________________

6.1.3. Konfiguracja

   Całą konfigurację demona i modułów przeprowadza się przy pomocy LMS-UI
   w menu Konfiguracja -> Demon. Konfigurację modułów omówiono w osobnych
   rozdziałach ich dotyczących. Podstawowe parametry pracy demona i dane
   do połączenia z bazą danych podaje się jako opcje linii komend, zgodnie
   z poniższym listingiem:
--dbhost -h host[:port]    host na którym zainstalowana jest baza danych (domyślnie: 'localhost')
--dbname -d nazwa_bazy     nazwa bazy danych (domyślnie: 'lms')
--dbuser -u użytkownik     nazwa użytkownika bazy danych (domyślnie: 'lms')
--dbpass -p hasło          hasło do bazy danych (domyślnie: puste)
--hostname -H nazwa_hosta  host, na którym działa demon. Domyślnie przyjmowana jest nazwa
                           zwracana przez komendę hostname, ale można ją nadpisać. Nazwa
                           ta musi zgadzać się z nazwą hosta podaną w konfiguracji hostów
--pidfile -P pid_file      pidfile where daemon write pid (default: none)
--ssl -s                   wymusza bezpieczne połączenie z bazą danych (domyślnie: wyłączone)
--command -c polecenie     polecenie powłoki do wykonania przed każdym połączeniem z bazą
                           tzn. co minutę (domyślnie: puste)
--instance -i "instancja[ ...]" lista instancji (modułów) do przeładowania. Wszystkie pozostałe
                           zostaną pominięte
--reload -q                wykonuje przeładowanie i kończy pracę
--reload-all -r            wykonuje przeładowanie wszystkich instancji (także tych, które mają
                           zdefiniowany crontab) i kończy pracę
--foreground -f            działa na pierwszym planie (nie forkuje się)
--version -v               wyświetla wersję i prawa licencyjne

   Opcje dostępu do bazy są także odczytywane ze zmiennych powłoki:
   LMSDBPASS, LMSDBNAME, LMSDBUSER, LMSDBHOST, LMSDBPORT.
   Notatka

           Lista instancji składa się z nazw instancji oddzielonych spacją. W
           nazwach instancji zawierających spacje należy zamienić je na znaki
           '\s', np. lmsd -i "moja\sinstancja".

   Konfiguracja demona jest podzielona na hosty (umożliwiając osobne
   konfigurowanie i przeładowywanie demonów zainstalowanych na różnych
   komputerach/routerach) oraz sekcje konfiguracyjne nazwane instancjami.

   Instancja oprócz parametrów konfiguracyjnych wybranego modułu zawiera
   opcje podstawowe, takie jak:
     * Nazwa
       Nazwa instancji unikalna w obrębie jednego hosta.
       Przykład: system
     * Priorytet
       Liczba określająca priorytet, czyli kolejność wykonania instancji.
       Instancja o najniższym numerze zostanie wykonana jako pierwsza.
       Przykład: 10
     * Moduł
       Nazwa pliku modułu (z rozszerzeniem lub bez). Jeśli nie podano
       ścieżki demon będzie szukał modułu w katalogu PREFIX/lms/lib, do
       którego trafiają moduły podczas "make install".
       Przykład: /usr/lib/system.so
     * Crontab
       Czas wykonania modułu określany w sposób podobny do używanego w
       programie crontab. Wszystkie dane muszą być numeryczne. Podany
       przykład spowoduje wykonywanie wybranej instancji co 5 minut, w
       godzinach od 8 do 18. Gdy opcja ta jest pusta instancja zostanie
       wykonana wyłącznie podczas przeładowania. Domyślnie: pusta.
       Przykład: */5 8-18 * * *

   Jakakolwiek zmiana w konfiguracji nie wymaga restartu demona.
     __________________________________________________________________

6.1.4. Uruchomienie

   Program domyślnie działa w trybie demona. Wtedy przeładowanie
   konfiguracji i usług jest dokonywane na żądanie, przy użyciu menu
   'Przeładowanie' w LMS-UI. Sprawdzenie żądania przeładowania oraz odczyt
   konfiguracji (w szczególności listy instancji i ich konfiguracji)
   następuje co minutę. Gdy demon wykryje żądanie wykonania reloadu,
   wywoła wszystkie włączone instancje. Instancje z podaną opcją 'crontab'
   zostaną wykonane o określonym tą opcją czasie.

   Innym sposobem uruchomienia jest jednorazowy reload z wykorzystaniem
   opcji -q. Ten sposób najczęściej używany jest w celach testowych, a w
   połączeniu z opcją -i pozwala na wykonanie dowolnych instancji z
   pominięciem pozostałych zapisanych w bazie oraz bez względu na wartość
   opcji 'crontab' tych instancji.
     __________________________________________________________________

6.2. Moduły

   Sam demon potrafi tylko uruchamiać moduły i to one odwalają całą
   robotę. Większość modułów jest przeznaczona do określonego
   zastosowania, jedynie 'hostfile' można używać do różnych konfigów
   (usług), np. różnych typów firewalli. Parametry konfiguracyjne modułów
   umieszcza się w sekcjach instancji je wywołujących.
     __________________________________________________________________

6.2.1. Lista dostępnych modułów

   Tabela 6-1. Lista modułów demona lmsd
        Nazwa                                   Opis
        system                       Wywoływanie poleceń powłoki
        parser                 Parser uniwersalnych skryptów T-Script
         dhcp                        Konfiguracja serwera dhcpd
        cutoff             Odłączanie klientów z zaległościami w opłatach
         dns                          Konfiguracja serwera dns
        ethers                       Tworzenie pliku /etc/ethers
       hostfile           Moduł uniwersalny (np. tworzenie reguł iptables)
        notify      Powiadamianie klientów o zaległościach w opłatach pocztą
                                            elektroniczną
       ggnotify      Powiadamianie klientów o zaległościach w opłatach przez
                                              gadu-gadu
       payments                    Naliczanie opłat abonamentowych
        oident                           Konfiguracja oident
          tc                             Tworzenie reguł TC
        tc-new          Tworzenie reguł TC (powiązania komputerów z taryfami)
       traffic                     Statystyki wykorzystania łącza
        pinger                       Badanie aktywności klientów
        ewx-pt                Konfiguracja EtherWerX PPPoE Terminatora
       ewx-stm           Konfiguracja EtherWerX Standalone Traffic Managera
   ewx-stm-channels    Konfiguracja EtherWerX Standalone Traffic Managera (ze
                                    rozszerzoną obsługą kanałów)
     __________________________________________________________________

6.2.2. System

6.2.2.1. Opis

   Jedyne co robi ten moduł to wykonanie zadanego polecenia (listy
   poleceń) powłoki i/lub komendy SQL. Może się przydać gdy chcesz podczas
   przeładowania konfiguracji wykonać jakąś komendę lub uruchomić
   zewnętrzny skrypt, na przykład jeden z tych, które możesz znaleźć w
   katalogu /bin. W pierwszej kolejności jest wykonywane polecenie SQL.
     __________________________________________________________________

6.2.2.2. Konfiguracja

   W związku z powyższym możesz zdefiniować jedynie treść polecenia SQL
   lub shella. Powłoka powinna sobie także poradzić z listą poleceń
   oddzielonych średnikami:
     * sql
       Polecenie SQL. Domyślnie: puste.
       Przykład: sql = 'DELETE FROM stats WHERE dt < %NOW% - 365*86400'
     * command
       Polecenie powłoki. Domyślnie: puste.
       Przykład: command = 'echo -n "tu moduł "; echo "system"'
     __________________________________________________________________

6.2.3. Payments

6.2.3.1. Opis

   Moduł nalicza opłaty abonamentowe klientów oraz opłaty stałe. Należy go
   uruchamiać codziennie. Opłaty naliczane na podstawie przypisanych
   klientowi taryf zapisywane są do bazy wraz z komentarzem określonym
   zmienną 'comment'. Po naliczeniu opłat tworzone są faktury. Komentarz
   do opłaty stałej to zlepek składający się z jej nazwy oraz wierzyciela.
   Na końcu usuwane są z bazy nieaktualne obciążenia klientów.
     __________________________________________________________________

6.2.3.2. Konfiguracja

   Dla tego modułu są dostępne następujące zmienne konfiguracyjne:
     * comment
       Komentarz do operacji. '%period' zostanie zamienione na daty od-do
       należnego abonamentu, np. '2003/10/10 - 2003/11/09', '%tariff' na
       nazwę odpowiedniej taryfy/zobowiązania, %month na pełną nazwę
       bieżącego miesiąca, %year na bieżący rok, a %next_mon na następny
       miesiąc w formacie RRRR/MM. Domyślnie: 'Subscription: '%tariff' for
       period: %period'.
       Przykład: comment = 'Abonament miesięczny za okres %period'
     * settlement_comment
       Komentarz do operacji wyrównania. '%period' zostanie zamienione na
       daty od-do okresu wyrównania, np. '2003/10/20 - 2003/11/09', a
       '%tariff' na nazwę odpowiedniej taryfy. Domyślnie odpowiada opcji
       comment.
       Przykład: settlement_comment = 'Wyrównanie za okres %period'
     * up_payments
       "Naliczanie z góry", czyli czy okres w komentarzu ma być liczony do
       przodu, czy do tyłu w stosunku do daty naliczenia opłaty.
       Domyślnie: yes.
       Przykład: up_payments = no
     * expiry_days
       Określa liczbę dni od daty wygaśnięcia przypisanych klientowi
       zobowiązań, po której dane tego zobowiązania zostaną usunięte z
       bazy. Przy ustawieniu na '0' dane zostaną usunięte natychmiast po
       dacie, do której obowiązywało zobowiązanie. Domyślnie: 30.
       Przykład: expiry_days = 365
     * deadline
       Termin płatności podany w dniach. Domyślnie: 14.
       Przykład: deadline = 21
     * paytype
       Rodzaj płatności (1-gotówka, 2-przelew, 3-przelew/gotówka, 4-karta,
       5-kompensata, 6-barter, 7-umowa). Domyślnie: 2 (przelew).
       Przykład: paytype = 1
     * numberplan
       ID planu numeracyjnego faktur. Domyślnie: 0 (plan domyślny).
       Przykład: numberplan = 0
     * check_invoices
       Włącza automatyczne oznaczanie faktur jako rozliczonych dla
       klientów z bilansem większym równym zero. Domyślnie: wyłączona.
       Przykład: check_invoices = 1
     * networks
       Lista nazw sieci, które mają być brane pod uwagę. Wielkość liter
       nie ma znaczenia. Domyślnie: pusta (wszystkie sieci).
       Przykład: networks = "lan1 lan2"
     * excluded_networks
       Lista nazw sieci, które mają pominięte. Wielkość liter nie ma
       znaczenia. Domyślnie: pusta (żadna).
       Przykład: excluded_networks = "lan3 lan4"
     * customergroups
       Lista nazw grup klientów, które mają być brane pod uwagę. Wielkość
       liter nie ma znaczenia. Domyślnie: pusta (wszystkie grupy).
       Przykład: customergroups = "grupa1 grupa2"
     * excluded_customergroups
       Lista nazw grup klientów, które mają być pominięte. Wielkość liter
       nie ma znaczenia. Domyślnie: pusta (żadna).
       Przykład: excluded_customergroups = "grupa3 grupa4"
     __________________________________________________________________

6.2.4. Notify

6.2.4.1. Opis

   Moduł 'notify' służy do informowania klientów o zaległościach w
   opłatach za pomocą poczty elektronicznej. Aktualne saldo klienta
   porównywane jest ze zmienną 'limit', jeśli jest niższe - wiadomość
   zostaje wysłana. Treść wiadomości pobierana jest z przygotowanego
   szablonu, w którym można stosować następujące zmienne:
     * %saldo - aktualne saldo klienta (także %B)
     * %b - wartość bezwzględna aktualnego salda klienta
     * %pin - PIN klienta
     * %name - imię klienta
     * %lastname - nazwisko/nazwa klienta
     * %last_10_in_a_table - wyciąg ostatnich 10 operacji na kocie klienta
     __________________________________________________________________

6.2.4.2. Konfiguracja

   Poniżej przedstawiono dostępne opcje konfiguracyjne modułu 'notify':
     * template
       Lokalizacja pliku z szablonem wiadomości. Domyślnie: pusty
       Przykład: template = modules/notify/sample/mailtemplate
     * file
       Lokalizacja pliku tymczasowego. Domyślnie: /tmp/mail
       Przykład: file = /tmp/mail.txt
     * command
       Polecenie powłoki wysyłające e-maila. '%address' zostanie
       zastąpione adresem e-mail klienta. Domyślnie: 'mail -s "Liabilities
       Information" %address < /tmp/mail'.
       Przykład: command = 'mail %address -s "musisz zapłacić, bo jak
       nie..." < /tmp/mail.txt'
     * limit
       Wiadomość o zaległościach zostaje wysłana jeśli saldo klienta
       spadnie poniżej kwoty określonej zmienną limit. Domyślnie: 0
       Przykład: limit = -20
     * debug_mail
       Określa adres na który zostaną wysłane wszystkie wiadomości,
       przydatne podczas testów. Domyślnie: puste.
       Przykład: debug_mail = localhost@moja.net
     __________________________________________________________________

6.2.5. Ggnotify

6.2.5.1. Opis

   Odpowiednik modułu 'notify' służący do wysyłania wiadomości gadu-gadu.
   Aktualne saldo klienta porównywane jest ze zmienną 'limit', jeśli jest
   niższe - wiadomość zostaje wysłana. Treść wiadomości pobierana jest z
   przygotowanego szablonu, w którym można stosować zmienne takie jak dla
   modułu 'notify' (może to być też ten sam szablon).

   Moduł wymaga zainstalowanej biblioteki libgadu oraz źródeł programu
   ekg. Odpowiednie ścieżki do nich należy ustawić w
   modules/ggnotify/Makefile przed kompilacją modułu.
     __________________________________________________________________

6.2.5.2. Konfiguracja

   Podobnie jak w 'notify' masz do dyspozycji następujące zmienne:
     * template
       Lokalizacja pliku z szablonem wiadomości. Domyślnie: pusty.
       Przykład: template = modules/ggnotify/sample/mailtemplate
     * uin
       Identyfikator gadu-gadu użytkownika wysyłającego wiadomości.
       Domyślnie: pusty.
       Przykład: uin = 1234567
     * password
       Hasło dla konta określonego zmienną 'uin'. Domyślnie: puste.
       Przykład: password = "moje_trudne__hasło"
     * limit
       Wiadomość o zaległościach zostaje wysłana jeśli saldo klienta
       spadnie poniżej kwoty określonej zmienną limit. Domyślnie: 0
       Przykład: limit = -20
     * debug_uin
       Jeśli ustawione, na to konto zostaną wysłane wszystkie wiadomości.
       Domyślnie: puste.
       Przykład: debug_uin = 7654321
     __________________________________________________________________

6.2.6. Cutoff

6.2.6.1. Opis

   Cutoff zmienia status komputerów na 'odłączony' i/lub włącza
   ostrzeżenia klientom, którzy mają na koncie zaległości większe niż
   określony limit. Ponadto odłącza komputery klientów, którym wygasły
   wszystkie zobowiązania lub są one zawieszone. Ten moduł nie zajmuje się
   fizycznym blokowaniem dostępu do sieci.
     __________________________________________________________________

6.2.6.2. Konfiguracja

   Dla modułu 'cutoff' mamy następujące opcje:
     * limit
       Odłączenie następuje jeśli saldo klienta spadnie poniżej wartości
       określonej jako kwota lub wartość procentowa sumy miesięcznych
       zobowiązań klienta (ze znakiem '%'). Domyślnie: 0.
       Przykład: limit = -20
     * command
       Określa komendę systemową, która zostanie wywołana jeżeli co
       najmniej jeden klient zostanie odłączony lub zostanie włączone
       ostrzeżenie. Domyślnie: nieustawiona.
       Przykład: command = 'lmsd -qi firewall'
     * warning
       Włącza ostrzeżenie dla odłączanego klienta i przypisuje mu
       określoną w tej opcji treść. Jeżeli pusta, ostrzeżenie nie będzie
       włączane. Data w ostrzeżeniu ukryta jest pod zmienną '%time'.
       Możesz także użyć zmiennych: %B dla rzeczywistego salda klienta
       oraz %b dla salda bez znaku. Saldo liczone jest w chwili wykonania
       modułu cutoff, a nie wyświetlenia komunikatu w przeglądarce
       klienta. Domyślnie: 'Blocked automatically due to payment deadline
       override at %time".
       Przykład: warning = ""
     * expired_warning
       Włącza ostrzeżenie dla odłączanego klienta i przypisuje mu
       określoną w tej opcji treść. Jeżeli pusta, ostrzeżenie nie będzie
       włączane. Dotyczy klientów, którym wygasły zobowiązania. Data w
       ostrzeżeniu ukryta jest pod zmienną '%time'. Domyślnie: 'Blocked
       automatically due to tariff(s) expiration at %time".
       Przykład: expired_warning = ""
     * warnings_only
       Ta opcja pozwala zdecydować, czy chcemy użyć naszego modułu
       wyłącznie do włączania ostrzeżeń. Działa tylko w stosunku do
       klientów, którzy posiadają aktywne zobowiązania. Klienci, którym
       wygasły zobowiązania, zostaną odłączeni mimo włączenia tej opcji.
       Domyślnie: wyłączona.
       Przykład: warnings_only = true
     * setnodegroup_only
       Ta opcja pozwala zdecydować, czy chemy przypisać do wybranej grupy
       komputery klienta, zamiast zmieniać jego status lub włączać
       ostrzeżenie. Do grupa o podanej nazwie zostaną przypisane wszystkie
       komputery klienta, który przekroczył limit zadłużenia, albo posiada
       nierozliczone faktury (jeśli włączono check_invoices). Domyślnie:
       pusta.
       Przykład: setnodegroup_only = ograniczony_dostep
     * use_nodeassignments
       Jeśli stosujesz powiązywanie komputerów z zobowiązaniami powinieneś
       włączyć tą opcję. W przeciwnym wypadku będą sprawdzane powiązania
       taryf z klientami. Domyślnie: wyłączona.
       Przykład: use_nodeassignments = true
     * use_customerassignments
       Jeśli chciałbyś pominąć sprawdzanie zobowiązań klientów powinieneś
       włączyć tą opcję. Domyślnie: włączona.
       Przykład: use_customerassignments = false
     * disable_suspended
       Włączenie tej opcji spowoduje odłaczenie także klientów, których
       wszystkie obowiązujące zobowiązania są zawieszone. Domyślnie:
       wyłączona.
       Przykład: disable_suspended = true
     * check_invoices
       Opcja włącza dodatkowe sprawdzenie czy klient posiada nierozliczone
       faktury z dniem płatności starszym o określoną w opcji 'deadline'
       ilość dni. Domyślnie: wyłączona.
       Przykład: check_invoices = true
     * deadline
       Określa okres w dniach (licząc od terminu płatności faktury), po
       którym nierozliczona faktura jest brana pod uwagę przy zastosowaniu
       opcji 'check_invoices'. Domyślnie, klient zostanie zablokowany od
       razu po terminie płatności. Domyślnie: 0.
       Przykład: deadline = 30
     * customergroups
       Lista nazw grup klientów, które mają być brane pod uwagę. Wielkość
       liter nie ma znaczenia. Domyślnie: pusta (wszystkie grupy).
       Przykład: customergroups = "grupa1 grupa2"
     * excluded_customergroups
       Lista nazw grup klientów, które mają być pominięte. Wielkość liter
       nie ma znaczenia. Domyślnie: pusta (żadna).
       Przykład: excluded_customergroups = "grupa3 grupa4"
     * networks
       Lista nazw sieci, które mają być brane pod uwagę. Wielkość liter
       nie ma znaczenia. Domyślnie: pusta (wszystkie sieci).
       Przykład: networks = "lan1 lan2"
     * excluded_networks
       Lista nazw sieci, które mają pominięte. Wielkość liter nie ma
       znaczenia. Domyślnie: pusta (żadna).
       Przykład: excluded_networks = "lan3 lan4"
     __________________________________________________________________

6.2.7. Dhcp

6.2.7.1. Opis

   Moduł zarządzający serwerem DHCP, tworzy plik konfiguracyjny oraz
   restartuje usługę. Zmienna 'command' umożliwia również wykonywanie
   innych czynności (programów).
     __________________________________________________________________

6.2.7.2. Konfiguracja

   Większość parametrów konfiguracyjnych odpowiada fragmentom pliku
   konfiguracyjnego dhcpd, które w typowych zastosowaniach nie wymagają
   zmiany:
     * file
       Określa lokalizację pliku konfiguracyjnego serwera dhcp. Domyślnie:
       /etc/dhcpd.conf.
       Przykład: file = /etc/dhcpd.conf
     * command
       Polecenie wykonywane po utworzeniu pliku konfiguracyjnego.
       Domyślnie: 'killall dhcpd; /usr/sbin/dhcpd'.
       Przykład: command = 'service dhcp restart'
     * begin
       Nagłówek pliku. Domyślnie: pusty.
       Przykład: begin = "authoritative;"
     * end
       Stopka pliku. Domyślnie: pusty.
       Przykład: end = ""
     * subnet_start
       Nagłówek podsieci. '%a' - nazwa, '%m' - maska, %b - broadcast.
       Domyślnie: "subnet %a netmask %m {\ndefault-lease-time
       86400;\nmax-lease-time 86400;".
       Przykład: subnet_start = "subnet %a netmask %m {default-lease-time
       3600;"
     * subnet_end
       Stopka podsieci. Domyślnie: "}".
       Przykład: subnet_end = '\t}'
     * subnet_gateway
       Brama podsieci. '%i' zostanie zamienione na adres ip. Domyślnie:
       "option routers %i;".
       Przykład: subnet_gateway = "option routers %i"
     * subnet_dns
       DNS'y podsieci. '%i - adresy dns'ów. Domyślnie: "option
       domain-name-servers %i;".
       Przykład: subnet_dns = "option domain-name-servers 192.168.0.1"
     * subnet_domain
       Nazwa domenowa podsieci. '%n' - nazwa. Domyślnie: 'option
       domain-name "%n";'.
       Przykład: subnet_domain = 'option domain-name "test.%n";'
     * subnet_wins
       Serwery wins. '%i' - adres ip serwera. Domyślnie: "option
       netbios-name-servers %i;".
       Przykład: subnet_wins = ""
     * subnet_range
       Zakres adresów podsieci. '%s' - adres początkowy, '%e' - koniec
       zakresu. Domyślnie: "range %s %e;".
       Przykład: subnet_range = "range %s %e;"
     * host
       Parametry hostów, gdzie '%n' - nazwa hosta, '%m' - MAC, '%i' -
       adres ip. Domyślnie: "\thost %n {\n\t\thardware ethernet %m;
       fixed-address %i; \n\t}".
       Przykład: host = "host %n {hardware ethernet %m; fixed-address
       %i;}"
     * networks
       Lista nazw sieci, które mają być brane pod uwagę. Wielkość liter
       nie ma znaczenia. Domyślnie: pusta (wszystkie sieci).
       Przykład: networks = "lan1 lan2"
     * customergroups
       Lista nazw grup klientów, które mają być brane pod uwagę. Wielkość
       liter nie ma znaczenia. Domyślnie: pusta (wszystkie grupy).
       Przykład: customergroups = "grupa1 grupa2"
     __________________________________________________________________

6.2.8. Hostfile

6.2.8.1. Opis

   Moduł 'hostfile' jest dość uniwersalnym narzędziem. Ponieważ wykonuje
   pętlę po wszystkich komputerach (oraz adresach urządzeń sieciowych) w
   bazie rozróżniając ich status podłączenia i ostrzeżenia, adresy
   prywatne i publiczne, a ponadto sieć do której są podłączone oraz grupy
   do których należą ich właściciele. Dzięki temu możliwe jest tworzenie
   np. reguł dowolnego firewalla, czy też pliku /etc/hosts. Dane zapisuje
   do pliku i następnie wykonuje określone polecenie powłoki.
     __________________________________________________________________

6.2.8.2. Konfiguracja

   W opcjach zawierających reguły hosta można stosować specjalne zmienne,
   które podczas zapisu do pliku zostaną zastąpione odpowiednimi dla
   danego komputera danymi:
   %i - adres IP,
   %ipub - publiczny adres IP,
   %id - ID komputera,
   %m - adres MAC,
   %ms - lista adresów MAC hosta (oddzielonych przecinkiem),
   %n - nazwa komputera,
   %p - hasło,
   %port - nr portu w urządzeniu, do którego jest podłączony komputer,
   %info - opis komputera,
   %l - lokalizacja komputera,
   %devl - lokalizacja urządzenia, do którego podłączony jest komputer,
   %domain - domena,
   %net - nazwa sieci, do której należy host,
   %if - interfejs sieci,
   %addr - adres sieci,
   %mask - maska sieci,
   %prefix - maska sieci w postaci prefixu CIDR,
   %gw - adres bramy,
   %dns, %dns2 - adresy serwerów DNS,
   %dhcps, %dhcpe - początek i koniec zakresu DHCP,
   %wins - adres serwera WINS,
   %i16 - ostatni oktet adresu IP w formacie szesnastkowym,
   %i16pub - ostatni oktet publicznego adresu IP w formacie szesnastkowym.
   %domainpub - domena sieci publicznej,
   %netpub - nazwa sieci, do której należy adres publiczny,
   %ifpub - interfejs sieci publicznej,
   %addrpub - adres sieci publicznej,
   %maskpub - maska sieci publicznej,
   %prefixpub - maska sieci publicznej w postaci prefixu CIDR,
   %gwpub - adres bramy w sieci publicznej,
   %dnspub, %dns2pub - adresy serwerów DNS w sieci publicznej,
   %dhcpspub, %dhcpepub - początek i koniec zakresu DHCP w sieci
   publicznej,
   %winspub - adres serwera WINS w sieci publicznej,
   %customer - nazwa klienta,
   %cid - ID klienta

   Poniżej opcje udostępniane przez ten moduł:
     * file
       Lokalizacja pliku tymczasowego. Domyślnie: /tmp/hostfile
       Przykład: file = /etc/rc.d/rc.firewall
     * command
       Polecenie powłoki wyk. po utworzeniu pliku 'file'. Domyślnie: puste
       Przykład: command = '/bin/sh /etc/rc.d/rc.firewall'
     * begin
       Nagłówek pliku tymczasowego. Domyślnie: "/usr/sbin/iptables -F
       FORWARD\n"
       Przykład: begin = "IPT=/usr/sbin/iptables \n$IPT -F FORWARD\n"
     * end
       Stopka pliku tymczasowego. Domyślnie: "/usr/sbin/iptables -A
       FORWARD -J REJECT\n"
       Przykład: end = "$IPT -A FORWARD -J REJECT\n"
     * host_begin
       Nagłówek reguły hosta. Domyślnie: ""
       Przykład: host_begin = "#%n\n"
     * host_end
       Stopka reguły hosta. Domyślnie: ""
       Przykład: host_end = "\n"
     * grantedhost
       Reguła dla hosta podłączonego. Domyślnie: "/usr/sbin/iptables -A
       FORWARD -s %i -m mac --mac-source %m -j ACCEPT\n"
       Przykład: grantedhost = "$IPT -A FORWARD -s %i -m mac --mac-source
       %m -j ACCEPT\n"
     * deniedhost
       Reguła dla hosta odłączonego. Domyślnie: "/usr/sbin/iptables -A
       FORWARD -s %i -m mac --mac-source %m -j REJECT\n"
       Przykład: deniedhost = "$IPT -A FORWARD -s %i -m mac --mac-source
       %m -j REJECT\n"
     * public_grantedhost
       Reguła dla hosta podłączonego, który posiada adres publiczny.
       Domyślnie reguła określona opcją 'grantedhost'.
       Przykład: public_grantedhost = "$IPT -A FORWARD -s %i -m mac
       --mac-source %m -j ACCEPT\n$IPT -t nat -A PREROUTING -p tcp -d
       %ipub -j DNAT --to-destination %i\n$IPT -t nat -A POSTROUTING -s %i
       -j SNAT --to-source %ipub\n"
     * public_deniedhost
       Reguła dla hosta odłączonego, który posiada adres publiczny.
       Domyślnie reguła określona opcją 'deniedhost'.
       Przykład: public_deniedhost = ""
     * warnedhost
       Reguła dla hosta z włączonym ostrzeżeniem. Domyślnie reguła
       określona opcją 'grantedhost'.
       Przykład: warnedhost = "$IPT -A PREROUTING -s %i --dport 80 -p tcp
       -j REDIRECT --to-port 82\n"
     * public_warnedhost
       Reguła dla hosta z włączonym ostrzeżeniem, który posiada adres
       publiczny. Domyślnie reguła określona opcją 'warnedhost'.
       Przykład: public_warnedhost = ""
     * public_replace
       Określa czy reguły zdefiniowane dla adresów publicznych mają
       nadpisać reguły główne, czy zostać dopisane do nich. Domyślnie:
       włączona.
       Przykład: public_replace = false
     * warn_replace
       Określa czy reguły zdefiniowane dla komputerów z ostrzeżeniem mają
       nadpisać reguły główne, czy zostać dopisane do nich. Domyślnie:
       wyłączona.
       Przykład: warn_replace = true
     * networks
       Lista nazw sieci, które mają być brane pod uwagę. Wielkość liter
       nie ma znaczenia. Domyślnie: pusta (wszystkie sieci).
       Przykład: networks = "lan1 lan2"
     * customergroups
       Lista nazw grup klientów, które mają być brane pod uwagę. Wielkość
       liter nie ma znaczenia. Domyślnie: pusta (wszystkie grupy).
       Przykład: customergroups = "grupa1 grupa2"
     * nodegroups
       Lista nazw grup komputerów, które mają być brane pod uwagę.
       Wielkość liter nie ma znaczenia. Domyślnie: pusta (wszystkie
       grupy).
       Przykład: nodegroups = "grupa1 grupa2"
     * excluded_networks
       Lista nazw sieci, które nie mają być brane pod uwagę. Wielkość
       liter nie ma znaczenia. Domyślnie: pusta.
       Przykład: excluded_networks = "lan1 lan2"
     * excluded_customergroups
       Lista nazw grup klientów, które nie mają być brane pod uwagę.
       Wielkość liter nie ma znaczenia. Domyślnie: pusta.
       Przykład: excluded_customergroups = "grupa1 grupa2"
     * excluded_nodegroups
       Lista nazw grup komputerów, które nie mają być brane pod uwagę.
       Wielkość liter nie ma znaczenia. Domyślnie: pusta.
       Przykład: excluded_nodegroups = "grupa1 grupa2"
     * skip_dev_ips
       Jeśli ustawiona na tak (yes, true) pominięte zostaną adresy
       urządzeń sieciowych. Domyślnie: tak
       Przykład: skip_dev_ips = nie
     * skip_host_ips
       Jeśli ustawiona na tak (yes, true) pominięte zostaną adresy
       komputerów. Uwaga: włączenie obu opcji 'skip_*_ips' spowoduje ich
       zignorowanie. Domyślnie: nie
       Przykład: skip_host_ips = tak
     * multi_mac
       Jeśli ustawiona na tak (yes, true) utworzony zostanie osobny rekord
       dla każdego adresu MAC. Tzn. jeśli komputer ma przypisanych kilka
       adresów MAC, będzie utworzone tyle wpisów ile tych adresów.
       Domyślnie: nie
       Przykład: multi_mac = tak
     * share_netdev_pubip
       Włączenie tej opcji (yes, true, 1) spowoduje, że wszystkim adresom
       urządzenia sieciowego, które nie posiadają zdefiniowanego adresu
       publicznego zostanie przypisany jeden (ten sam) ze zdefiniowanych
       dla danego urządzenia adresów publicznych. Domyślnie: nie
       Przykład: share_netdev_pubip = tak
     __________________________________________________________________

6.2.9. Traffic

6.2.9.1. Opis

   'Traffic' to odpowiednik perlowego lms-traffic, zapisujący do bazy
   statystyki wykorzystania łącza z pliku utworzonego przez użytkownika.
   Plik taki powinien mieć format: ip_hosta upload download . Więcej
   informacji (w tym jak utworzyć taki plik) można znaleźć w rozdziale
   dotyczącym lms-traffic.
     __________________________________________________________________

6.2.9.2. Konfiguracja

   Moduł posiada trzy opcje:
     * file
       Lokalizacja pliku ze statystykami firewalla. Domyślnie:
       /var/log/traffic.log
       Przykład: file = /tmp/log
     * begin_command
       Polecenie powłoki wykonywane przed wczytaniem pliku. Domyślnie:
       puste
       Przykład: begin_command = 'perl /usr/local/lms/bin/traffic_ipt.pl'
     * end_command
       Polecenie powłoki wykonywane po wczytaniu pliku. Domyślnie: puste
       Przykład: end_command = 'rm /var/log/traffic.log'
     __________________________________________________________________

6.2.10. Tc (HTB)

6.2.10.1. Opis

   Moduł generujący skrypt zawierający polecenia iptables i tc służące do
   ograniczania przepływności i limitowania połączeń klientom. Regułki dla
   komputerów można dowolnie zdefiniować i wykorzystać nie tylko do
   "traffic control". Zasada działania skryptu przedstawia się
   następująco: Najpierw z bazy pobierane są dane dla wszystkich klientów.
   Obliczane są sumy ograniczeń (uprate, downrate, upceil, downceil,
   limity połączeń) dla każdego klienta Następnie wykonywana jest pętla ze
   sprawdzeniem przynależności do grupy klientów i sieci (jeśli
   określono). Jeśli wartości ograniczeń są różne od zera następuje zapis
   reguł do pliku z podmianą zmiennych. W regułkach można stosować
   następujące zmienne: %name - nazwa hosta, %i - adres IP, %m - MAC, %if
   - interfejs, %uprate, %downrate, %upceil, %downceil, %plimit, %climit,
   %o1, %o2, %o3, %o4 - kolejne oktety adresu IP, %h1, %h2, %h3, %h4 -
   kolejne oktety adresu IP w zapisie szesnastkowym oraz %x - licznik o
   wartości początkowej 100 zwiększany o jeden dla każdego komputera (lub
   klienta, w zależności od ustawienia opcji one_class_per_host).

   Domyślna polityka tworzenia klas htb zakłada utworzenie jednej klasy
   dla wszystkich komputerów klienta. Może to być zmienione za pomocą
   opcji 'one_class_per_host'.

   Konfiguracja domyślna zakłada, że twój system jest przystosowany do
   zastosowania htb oraz iptables z modułami limit, connlimit, mark i
   ipp2p. Możesz sam spatchować jądro lub skorzystać ze źródeł dostępnych
   na stronie www.inet.one.pl.
     __________________________________________________________________

6.2.10.2. Konfiguracja

   Masz do dyspozycji standardowe parametry takie jak customergroups,
   file, command, networks i dodatkowo opcje definiujące treść regułek tc
   i firewalla. Domyślna konfiguracja przeznaczona jest dla łącz 512/128
   kbit i 100mbit.
     * file
       Lokalizacja pliku tymczasowego. Domyślnie: /etc/rc.d/rc.htb.
       Przykład: file = /tmp/rc.htb
     * command
       Polecenie powłoki wykonywane po utworzeniu pliku. Domyślnie: "sh
       /etc/rc.d/rc.htb start".
       Przykład: command = "chmod 700 /tmp/rc.htb; /tmp/rc.htb start"
     * begin
       Nagłówek skryptu. Domyślnie:
"#!/bin/sh
IPT=/usr/sbin/iptables
TC=/sbin/tc
LAN=eth0
WAN=eth1
BURST="burst 30k"

stop ()
{
$IPT -t mangle -D FORWARD -i $WAN -j LIMITS >/dev/null 2>&1
$IPT -t mangle -D FORWARD -o $WAN -j LIMITS >/dev/null 2>&1
$IPT -t mangle -F LIMITS >/dev/null 2>&1
$IPT -t mangle -X LIMITS >/dev/null 2>&1
$IPT -t mangle -F OUTPUT
$IPT -t filter -F FORWARD
$TC qdisc del dev $LAN root 2> /dev/null
$TC qdisc del dev $WAN root 2> /dev/null
}

start ()
{
stop
$IPT -t mangle -N LIMITS
$IPT -t mangle -I FORWARD -i $WAN -j LIMITS
$IPT -t mangle -I FORWARD -o $WAN -j LIMITS
# incomming traffic
$IPT -t mangle -A OUTPUT -j MARK --set-mark 1
$TC qdisc add dev $LAN root handle 1:0 htb default 3 r2q 1
$TC class add dev $LAN parent 1:0 classid 1:1 htb rate 99000kbit ceil 99000kbitquantum 1500
$TC class add dev $LAN parent 1:1 classid 1:2 htb rate   500kbit ceil   500kbit
$TC class add dev $LAN parent 1:1 classid 1:3 htb rate 98500kbit ceil 98500kbitprio 9 quantum 1500
$TC qdisc add dev $LAN parent 1:3 esfq perturb 10 hash dst
# priorities for ICMP, TOS 0x10 and ports 22 and 53
$TC class add dev $LAN parent 1:2 classid 1:20 htb rate 50kbit ceil 500kbit $BURST prio 1 quantum 1500
$TC qdisc add dev $LAN parent 1:20 esfq perturb 10 hash dst
$TC filter add dev $LAN parent 1:0 protocol ip prio 2 u32 match ip sport 22 0xffff flowid 1:20
$TC filter add dev $LAN parent 1:0 protocol ip prio 2 u32 match ip sport 53 0xffff flowid 1:20
$TC filter add dev $LAN parent 1:0 protocol ip prio 1 u32 match ip tos 0x10 0xff flowid 1:20
$TC filter add dev $LAN parent 1:0 protocol ip prio 1 u32 match ip protocol 1 0xff flowid 1:20
# serwer -> LAN
$TC filter add dev $LAN parent 1:0 protocol ip prio 4 handle 1 fw flowid 1:3

# outgoing traffic
$TC qdisc add dev $WAN root handle 2:0 htb default 11 r2q 1
$TC class add dev $WAN parent 2:0 classid 2:1 htb rate 120kbit ceil 120kbit
# priorities for ACK, ICMP, TOS 0x10, ports 22 and 53
$TC class add dev $WAN parent 2:1 classid 2:10 htb rate 60kbit ceil 120kbit prio 1 quantum 1500
$TC qdisc add dev $WAN parent 2:10 esfq perturb 10 hash dst
$TC filter add dev $WAN parent 2:0 protocol ip prio 1 u32 match ip protocol 6 0xff \
match u8 0x05 0x0f at 0 match u16 0x0000 0xffc0 at 1 match u8 0x10 0xff at 33 flowid 2:10
$TC filter add dev $WAN parent 2:0 protocol ip prio 1 u32 match ip dport 22 0xffff flowid 2:10
$TC filter add dev $WAN parent 2:0 protocol ip prio 1 u32 match ip dport 53 0xffff flowid 2:10
$TC filter add dev $WAN parent 2:0 protocol ip prio 1 u32 match ip tos 0x10 0xff flowid 2:10
$TC filter add dev $WAN parent 2:0 protocol ip prio 1 u32 match ip protocol 1 0xff flowid 2:10
# serwer -> Internet
$TC class add dev $WAN parent 2:1 classid 2:11 htb rate 30kbit ceil 120kbit prio 2 quantum 1500
$TC qdisc add dev $WAN parent 2:11 esfq perturb 10 hash dst
$TC filter add dev $WAN parent 2:0 protocol ip prio 3 handle 1 fw flowid 2:11
$TC filter add dev $WAN parent 2:0 protocol ip prio 9 u32 match ip dst 0/0 flowid 2:11
       Przykład: begin = "#!/bin/bash\n$TC=/usr/local/sbin/tc\n"
     * end
       Stopka skryptu. Domyślnie:
}

case "$1" in
    'start')
        start
    ;;
    'stop')
        stop
    ;;
    'status')
        echo "WAN Interface"
        echo "============="
        $TC class show dev $WAN | grep root
        $TC class show dev $WAN | grep -v root | sort | nl
        echo "LAN Interface"
        echo "============="
        $TC class show dev $LAN | grep root
        $TC class show dev $LAN | grep -v root | sort | nl
    ;;
    *)
        echo -e "\nUsage: rc.htb start|stop|status"
    ;;
esac
       Przykład: end = ""
     * one_class_per_host
       Określa politykę tworzenia klas htb. W ustawieniu domyślnym
       wszystkie komputery klienta zostaną wrzucone do jednej klasy.
       Ustawienie tej opcji na 'true' spowoduje, że reguły określone w
       host_htb_up i host_htb_down zostaną wygenerowane dla wszystkich
       komputerów klienta (z inną wartością '%x'). Reguły z
       host_mark_down, host_mark_up, host_plimit i host_climit generowane
       są dla każdego komputera niezależnie od ustawień tej zmiennej.
       Domyślnie: false
       Przykład: one_class_per_host = 1
     * limit_per_host
       Określa politykę tworzenia limitów połączeń/pakietów dla
       komputerów. W ustawieniu domyślnym limit połączeń/pakietów jest
       dzielony przez ilość komputerów klienta. Włączenie tej opcji
       spowoduje, że reguły określone w host_climit i host_plimit zostaną
       wygenerowane dla wszystkich komputerów klienta z wartością $climit
       i %plimit zdefiniowaną w taryfie (nie podzieloną przez ilość
       komputerów). Domyślnie: false
       Przykład: limit_per_host = 1
     * host_mark_up
       Reguła markująca dla każdego komputera. Domyślnie:
# %n
$IPT -t mangle -A LIMITS -s %i -j MARK --set-mark %x
       Przykład: host_mark_up = ""
     * host_mark_down
       Reguła markująca dla każdego komputera. Domyślnie:
$IPT -t mangle -A LIMITS -d %i -j MARK --set-mark %x
       Przykład: host_mark_down = ""
     * host_htb_down
       Zestaw reguł dla każdego komputera, wykonywanych gdy uprate i
       downrate są różne od zera. Domyślnie:
$TC class add dev $LAN parent 1:2 classid 1:%x htb rate %downratekbit ceil %downceilkbit $BURST prio 2 quantum 1500
$TC qdisc add dev $LAN parent 1:%x esfq perturb 10 hash dst
$TC filter add dev $LAN parent 1:0 protocol ip prio 5 handle %x fw flowid 1:%x
       Przykład: host_htb_down = ""
     * host_htb_up
       Zestaw reguł dla każdego komputera, wykonywanych gdy uprate i
       downrate są różne od zera. Domyślnie:
$TC class add dev $WAN parent 2:1 classid 2:%x htb rate %upratekbit ceil %upceilkbit $BURST prio 2 quantum 1500
$TC qdisc add dev $WAN parent 2:%x esfq perturb 10 hash dst
$TC filter add dev $WAN parent 2:0 protocol ip prio 5 handle %x fw flowid 2:%x
       Przykład: host_htb_up = ""
     * host_climit
       Regułka z ograniczeniem ilości równoczesnych połączeń tcp.
       Wykonywana gdy climit w bazie jest różny od zera. Domyślnie:
$IPT -t filter -I FORWARD -p tcp -s %i -m connlimit --connlimit-above %climit -m ipp2p --ipp2p -j REJECT
       Przykład: host_climit = "$IPT -t filter -I FORWARD -p tcp -s %i -m
       connlimit --connlimit-above -j REJECT"
     * host_plimit
       Regułka z ograniczeniem ilości pakietów w jednostce czasu (tutaj
       sekunda). Wykonywana gdy plimit w bazie jest różny od zera.
       Domyślnie:
$IPT -t filter -I FORWARD -p tcp -d %i -m limit --limit %plimit/s -m ipp2p --ipp2p -j ACCEPT
$IPT -t filter -I FORWARD -p tcp -s %i -m limit --limit %plimit/s -m ipp2p --ipp2p -j ACCEPT
       Przykład: host_plimit = ""
     * networks
       Lista nazw sieci, które mają być brane pod uwagę. Wielkość liter
       nie ma znaczenia. Domyślnie: pusta (wszystkie sieci).
       Przykład: networks = "lan1 lan2"
     * customergroups
       Lista nazw grup klientów, które mają być brane pod uwagę. Wielkość
       liter nie ma znaczenia. Domyślnie: pusta (wszystkie grupy).
       Przykład: customergroups = "grupa1 grupa2"
     __________________________________________________________________

6.2.11. Tc-new (HTB)

6.2.11.1. Opis

   Moduł generujący skrypt zawierający polecenia iptables i tc służące do
   ograniczania przepływności i limitowania połączeń klientom. Działa
   podobnie do modułu 'tc', lecz obsługuje funkcjonalność pozwalającą na
   powiązywanie taryf z komputerami. Skrypt tworzy tzw. kanały dla
   komputerów klienta przypisanych do tej samej taryfy. Przykładowo: dla
   klienta z dwoma komputerami przypisanymi do tej samej taryfy zostaną
   utworzone dwie klasy (upload i download) oraz po dwa filtry dla każdego
   komputera. W przypadku gdy klient ma dwie taryfy i do każdej po jednym
   komputerze powstaną po dwie klasy dla każdej taryfy i odpowiednia ilość
   filtrów. Wartość początkowa liczników (%x i %h w regułach) wynosi 100.
     __________________________________________________________________

6.2.11.2. Konfiguracja

   Wartości domyślne niektórych opcją są zgodne z modułem tc.
     * file
       Lokalizacja pliku wynikowego. Domyślnie: /etc/rc.d/rc.htb.
       Przykład: file = /tmp/rc.htb
     * command
       Polecenie powłoki wykonywane po utworzeniu pliku. Domyślnie: "sh
       /etc/rc.d/rc.htb start".
       Przykład: command = "chmod 700 /tmp/rc.htb; /tmp/rc.htb start"
     * begin
       Nagłówek skryptu. Domyślnie: jak w module tc
       Przykład: begin = "#!/bin/bash\n$TC=/usr/local/sbin/tc\n"
     * end
       Stopka skryptu. Domyślnie: jak w module tc
       Przykład: end = ""
     * class_up
       Definicja klasy dla uploadu, w której możemy użyć następujących
       zmiennych: %cid - ID klienta, %cname - nazwa klienta, %h - numer
       uchwytu klasy %uprate, %upceil. Domyślnie:
# %cname (ID:%cid)
$TC class add dev $WAN parent 2:1 classid 2:%h htb rate %upratekbit ceil %upceilkbit $BURST prio 2 quantum 1500
$TC qdisc add dev $WAN parent 2:%h esfq perturb 10 hash dst
       Przykład: class_up = "$TC class add dev $WAN parent 2:1 classid
       2:%h htb rate %upratekbit ceil %upceilkbit $BURST prio 2 quantum
       1500"
     * class_down
       Definicja klasy dla downloadu, w której możemy użyć następujących
       zmiennych: %cid - ID klienta, %cname - nazwa klienta, %h - numer
       uchwytu klasy, %downrate, %downceil. Domyślnie:
$TC class add dev $LAN parent 1:2 classid 1:%h htb rate %downratekbit ceil %downceilkbit $BURST prio 2 quantum 1500
$TC qdisc add dev $LAN parent 1:%h esfq perturb 10 hash dst

       Przykład: class_up = "$TC class add dev $LAN parent 1:2 classid
       1:%h htb rate %downratekbit ceil %ceilceilkbit $BURST prio 2
       quantum 1500"
     * filter_up
       Definicja filtrów dla ruchu w kierunku od hosta. Dozwolone zmienne:
       %n - nazwa hosta, %if - nazwa interfejsu sieci, %i - adres, %m -
       mac, %ms - lista adresów MAC hosta (oddzielonych przecinkiem), %o1,
       %o2, %o3, %o4 - oktety adresu dziesiętnie, %h1, %h2, %h3, %h4 -
       oktety adresu w zapisie szesnastkowym, %h - uchwyt klasy, %x -
       uchwyt filtra (unikalny numer reguły). Domyślnie:
# %n
$IPT -t mangle -A LIMITS -s %i -j MARK --set-mark %x
$TC filter add dev $WAN parent 2:0 protocol ip prio 5 handle %x fw flowid 2:%h
       Przykład: class_up = "%n\n$IPT -A src%o3 -s %i -j CLASSIFY
       --set-class 2:%h\n"
     * filter_down
       Definicja filtrów dla ruchu w kierunku do hosta. Dozwolone zmienne:
       %n - nazwa hosta, %if - nazwa interfejsu sieci, %i - adres, %m -
       mac, %ms - lista adresów MAC hosta (oddzielonych przecinkiem), %o1,
       %o2, %o3, %o4 - oktety adresu dziesiętnie, %h1, %h2, %h3, %h4 -
       oktety adresu w zapisie szesnastkowym, %h - uchwyt klasy, %x -
       uchwyt filtra (unikalny numer reguły). Domyślnie:
$IPT -t mangle -A LIMITS -d %i -j MARK --set-mark %x
$TC filter add dev $LAN parent 1:0 protocol ip prio 5 handle %x fw flowid 1:%h
       Przykład: class_down = "%n\n$IPT -A dst%o3 -d %i -j CLASSIFY
       --set-class 1:%h\n"
     * climit
       Definicja reguły dla limitu połączeń hosta. Dozwolone zmienne: %n -
       nazwa hosta, %if - nazwa interfejsu sieci, %i - adres, %m - mac,
       %ms - lista adresów MAC hosta (oddzielonych przecinkiem), %o1, %o2,
       %o3, %o4 - oktety adresu dziesiętnie, %h1, %h2, %h3, %h4 - oktety
       adresu w zapisie szesnastkowym, %climit - limit połączeń.
       Domyślnie:
$IPT -t filter -I FORWARD -p tcp -s %i -m connlimit --connlimit-above %climit -j REJECT

       Przykład: climit = ""
     * plimit
       Definicja reguły dla limitu pakietów dla hosta. Dozwolone zmienne:
       %n - nazwa hosta, %if - nazwa interfejsu sieci, %i - adres, %m -
       mac, %ms - lista adresów MAC hosta (oddzielonych przecinkiem), %o1,
       %o2, %o3, %o4 - oktety adresu dziesiętnie, %h1, %h2, %h3, %h4 -
       oktety adresu w zapisie szesnastkowym, %plimit - limit pakietów.
       Domyślnie:
$IPT -t filter -I FORWARD -d %i -m limit --limit %plimit/s -j ACCEPT
$IPT -t filter -I FORWARD -s %i -m limit --limit %plimit/s -j ACCEPT

       Przykład: plimit = ""
     * networks
       Lista nazw sieci, które mają być brane pod uwagę. Wielkość liter
       nie ma znaczenia. Domyślnie: pusta (wszystkie sieci).
       Przykład: networks = "lan1 lan2"
     * customergroups
       Lista nazw grup klientów, które mają być brane pod uwagę. Wielkość
       liter nie ma znaczenia. Domyślnie: pusta (wszystkie grupy).
       Przykład: customergroups = "grupa1 grupa2"
     * night_hours
       Zakres godzin nocnych w formacie 24-godzinnym. Np. "22-5" oznacza,
       że podczas uruchomienia modułu w godzinach od 22:00 do 4:59 brane
       będą wartości taryf dla godzin nocnych. Obowiązują następujące
       ograniczenia dla formatu zakresu: nie może zawierać spacji, okres
       nie może zaczynać się przed godziną 18. Domyślnie: pusta.
       Przykład: night_hours = "24-6"
     * night_no_debtors
       Włączenie tej opcji spowoduje sprawdzenie czy klient posiada
       nierozliczone faktury przeterminowane. Jeśli tak, taryfa nocna nie
       zostanie zastosowana. Domyślnie: false.
       Przykład: night_no_debtors = tak
     * night_deadline
       Dodatkowy czas (ilość dni) po terminie płatności, zanim faktura
       zostanie potraktowana jako przeterminowana. Opcja działa w
       połączeniu z 'night_no_debtors'. Domyślnie: 0.
       Przykład: night_deadline = 7
     * multi_mac
       Jeśli ustawiona na tak (yes, true) utworzony zostanie osobny rekord
       dla każdego adresu MAC. Tzn. jeśli komputer ma przypisanych kilka
       adresów MAC, będzie utworzone tyle wpisów ile tych adresów.
       Domyślnie: nie
       Przykład: multi_mac = tak
     __________________________________________________________________

6.2.12. Dns

6.2.12.1. Opis

   Moduł do konfiguracji stref serwera 'named' jest jednym z bardziej
   skomplikowanych. Tworzy dla każdej sieci pliki stref oraz odpowiednie
   wpisy w named.conf w oparciu o szablony tych plików. Przykładowe
   szablony znajdują się w katalogu /modules/dns/sample.
     __________________________________________________________________

6.2.12.2. Konfiguracja

     * forward-patterns
       Katalog z szablonami stref. Domyślnie: forward.
       Przykład: forward-patterns = /dns/patterns/forward
     * reverse-patterns
       Katalog z szablonami stref odwrotnych. Domyślnie: reverse.
       Przykład: reverse-patterns = /dns/patterns/revers
     * generic-forward
       Szablon domyślny. Zostanie wykorzystany jeśli w katalogu określonym
       'forward-patterns' nie będzie pliku odpowiadającego nazwie
       domenowej sieci. Domyślnie: modules/dns/sample/forward/generic.
       Przykład: generic-forward = /dns/patterns/forward
     * generic-reverse
       Szablon domyślny. Zostanie wykorzystany jeśli w katalogu określonym
       'reverse-patterns' nie będzie pliku odpowiadającego numerowi IP
       sieci. Domyślnie: modules/dns/sample/reverse/generic.
       Przykład: generic-reverse = /dns/patterns/forward
     * forward-zones
       Katalog na pliki wynikowe stref. Domyślnie:
       modules/dns/sample/out/forward.
       Przykład: forward-zones = /dns/forward
     * reverse-zones
       Katalog na pliki wynikowe stref odwrotnych. Domyślnie:
       modules/dns/sample/out/reverse.
       Przykład: reverse-zones = /dns/reverse
     * host-reverse
       Linia w pliku strefy odwr. odpowiadająca każdemu komputerowi w dane
       sieci. Domyślnie: "%n IN A %i\n".
       Przykład: host-reverse = "\t %n IN A %i\n"
     * host-forward
       Linia w pliku strefy odpowiadająca każdemu komputerowi w danej
       sieci. Domyślnie: "%c IN PTR %n.%d.\n".
       Przykład: host-forward = "\t %c IN PTR %n.%d.\n"
     * conf-pattern
       Lokalizacja szablonu głównego pliku konfiguracyjnego serwera.
       Domyślnie: modules/dns/sample/named.conf.
       Przykład: conf-pattern = /dns/patterns/named.conf
     * conf-output
       Lokalizacja głównego pliku konfiguracyjnego serwera. Domyślnie:
       /tmp/named.conf.
       Przykład: conf-output = /etc/named.conf
     * conf-forward-entry
       Wpis dla każdej strefy w głównym pliku konfiguracyjnym. Domyślnie:
       'zone "%n" {\ntype master;\n file "forward/%n"; \nnotify yes; \n};
       \n'.
       Przykład: conf-forward-entry = 'zone "%n" { \n\ttype master;
       \n\tfile "forward/%n"; \n\tnotify yes; \n}; \n'
     * conf-reverse-entry
       Wpis dla każdej strefy odwr. w głównym pliku konfiguracyjnym.
       Domyślnie: 'zone "%c.in-addr.arpa" { \ntype master; \nfile
       "reverse/%c"; \nnotify yes; \n}; \n'.
       Przykład: conf-revers-entry = 'zone "%c.in-addr.arpa" { \n\ttype
       master; \n\tfile "reverse/%c"; \n\tnotify yes; \n}; \n'
     * command
       Polecenie wykonywane po utworzeniu plików konf. Domyślnie: puste.
       Przykład: command = ""
     * networks
       Lista nazw sieci, które mają być brane pod uwagę. Wielkość liter
       nie ma znaczenia. Domyślnie: pusta (wszystkie sieci).
       Przykład: networks = "lan1 lan2"
     * custmergroups
       Lista nazw grup klientów, które mają być brane pod uwagę. Wielkość
       liter nie ma znaczenia. Domyślnie: pusta (wszystkie grupy).
       Przykład: customergroups = "grupa1 grupa2"
     __________________________________________________________________

6.2.13. Ethers

6.2.13.1. Opis

   Moduł tworzący konfigurację tablicy ARP systemu. Ustawiając opcję
   'dummy_macs' można sprawić, aby komputerom odłączonym został przypisany
   mac-adres 00:00:00:00:00:00.
     __________________________________________________________________

6.2.13.2. Konfiguracja

   Tutaj są tylko standardowe opcje:
     * file
       Lokalizacja pliku wynikowego. Domyślnie: /etc/ethers.
       Przykład: file = /tmp/ethers
     * command
       Polecenie powłoki wykonywane po wygenerowaniu konfiga. Domyślnie:
       'arp -f /etc/ethers'.
       Przykład: command = ""
     * dummy_macs
       Jeśli ustawimy na 'yes', to komputerom odłączonym zostanie
       przypisany mac-adres '00:00:00:00:00:00'. Domyślnie: "no".
       Przykład: dummy_macs = yes
     * networks
       Lista nazw sieci, które mają być brane pod uwagę. Wielkość liter
       nie ma znaczenia. Domyślnie: pusta (wszystkie sieci).
       Przykład: networks = "lan1 lan2"
     * customergroups
       Lista nazw grup klientów, które mają być brane pod uwagę. Wielkość
       liter nie ma znaczenia. Domyślnie: pusta (wszystkie grupy).
       Przykład: customergroups = "grupa1 grupa2"
     __________________________________________________________________

6.2.14. Oident

6.2.14.1. Opis

   Moduł do konfiguracji oidentd. W zasadzie można to zrobić modułem
   'hostfile', ale tutaj masz już gotowe ustawienia domyślne.
     __________________________________________________________________

6.2.14.2. Konfiguracja

   A oto parametry modułu 'oident'
     * begin
       Tekst wstawiany na początku pliku. Domyślnie: puste.
       Przykład: begin = "#Generowany automatycznie\n"
     * end
       Tekst wstawiany na końcu pliku. Domyślnie: puste.
       Przykład: end = ""
     * host
       Linia tekstu dla każdego komputera. Domyślnie: "%i\t%n\tUNIX".
       Przykład: host = "%i %n WINDOWS"
     * file
       Nazwa pliku konfiguracyjnego. Domyślnie: /etc/oidentd.conf.
       Przykład: file = /etc/oident/identd.conf
     * networks
       Lista nazw sieci do uwzględnienia. Domyślnie: pusta (wszystkie
       sieci).
       Przykład: networks = 'lan1 lan2'
     * command
       Polecenie do wykonania po utworzeniu pliku. Domyślnie: puste.
       Przykład: command = "killall -HUP midentd"
     __________________________________________________________________

6.2.15. Pinger

6.2.15.1. Opis

   Moduł pinger to odpowiednik perlowego skryptu lms-fping. Różnice są
   jednak zasadnicze. Nie potrzebuje zewnętrznego programu i działa przy
   wykorzystaniu protokołu ARP. Powodem tego jest mniej więcej dwukrotnie
   szybsze wykonanie skanowania sieci. Nie ma także problemów z
   komputerami mającymi wyłączone odpowiadanie na pingi. Po skanowaniu,
   wszystkim włączonym komputerom jest ustawiany w bazie danych czas
   skanowania, wykorzystywany do obrazowania aktywności komputerów np. na
   mapie sieci.

   Notatka

   Pinger rozpoznaje interfejsy sieciowe na podstawie nazwy, dlatego (np.
   gdy do zakładania interfejsów wirtualnych/aliasów wykorzystujesz
   program ip) musisz nadawać interfejsom etykiety (ip addr add ... label
   ...). Pamiętaj także, żeby nie używać w nazwach kropek, ani myślników
   (mimo, że ip na to pozwala), gdyż pinger nie rozpozna poprawnie takiego
   interfejsu.
     __________________________________________________________________

6.2.15.2. Konfiguracja

   Pinger udostępnia tylko jedną opcję konfiguracyjną:
     * networks
       Lista nazw sieci, które mają być skanowane. Domyślnie: pusta
       (wszystkie sieci).
       Przykład: networks = 'lan1 lan2'
     __________________________________________________________________

6.2.16. Parser

6.2.16.1. Wstęp

   Moduł parser jest oparty na skryptowym języku T-Script, którego głównym
   zadaniem jest generowanie plików tekstowych. Może być używany do
   przetwarzania szablonów z danymi pobieranymi z różnych źródeł np. baz
   SQL lub plików tekstowych. W naszym przypadku treść skryptu (szablon)
   jest przechowywany w bazie danych, dlatego istnieje możliwość jego
   edycji poprzez LMS-UI. W przyszłości moduł parser może zastąpić
   większość modułów demona.

   Opis języka T-Script znajduje się w rozdziale T-Script.

   Przed kompilacją modułu upewnij się, że posiadasz w systemie pakiety
   bison (co najmniej w wersji 1.875) oraz flex.
     __________________________________________________________________

6.2.16.2. Konfiguracja

   Parser posiada następujące opcje:
     * script
       Zawartość skryptu (szablonu). Domyślnie: pusta.
       Przykład: script = '{var=1}zmienna var={var}'
     * file
       Lokalizacja pliku wynikowego. Domyślnie: pusta
       Przykład: file = /tmp/parser.out
     * command
       Polecenie powłoki do wykonania po kompilacji skryptu. Domyślnie:
       pusta
       Przykład: command = "sh /tmp/parser.out"
     __________________________________________________________________

6.2.17. Ewx-pt

6.2.17.1. Wstęp

   Moduł ewx-pt służy do zarządzania urządzeniem PPPoE Terminator marki
   EtherWerX. Komunikacja odbywa się z wykorzystaniem protokołu SNMP.

   Moduł podczas pracy tworzy własną kopię konfiguracji urządzenia.
   Konfiguracja urządzenia nie jest w żaden sposób weryfikowana, dlatego
   korzystając z modułu ewx-pt nie można dokonywać zmian bezpośrednio na
   urządzeniu. W celu rekonfiguracji urządzenia należy wyczyścić
   konfigurację na urządzeniu oraz usunąć zawartość tabeli ewx_pt_config w
   bazie LMSa.
   Notatka

           Pamiętaj o włączeniu opcji konfiguracyjnej ewx_support w sekcji
           [phpui].
     __________________________________________________________________

6.2.17.2. Konfiguracja

   Moduł ewx-pt udostępnia następujące opcje:
     * snmp_host
       Adres urządzenia EtherWerX. Domyślnie: pusty.
       Przykład: snmp_host = 192.168.0.1
     * snmp_port
       Port urządzenia dla komunikacji SNMP. Domyślnie: 161
       Przykład: snmp_port = 2161
     * community
       Nazwa community SNMP urządzenia. Domyślnie: private
       Przykład: community = public
     * offset
       Liczba całkowita o jaką będą zwiększane identyfikatory komputerów
       podczas zapisu na urządzeniu EtherWerX. Domyślnie: 0.
       Przykład: offset = 1000
     * networks
       Lista nazw sieci, które mają być brane pod uwagę. Uwaga: po zmianie
       wartości tej opcji zalecana jest rekonfiguracja urządzenia.
       Domyślnie: pusta (wszystkie sieci).
       Przykład: networks = 'lan1 lan2'
     * dummy_ip_networks
       Lista nazw sieci, dla których komputerom zostanie przypisany adres
       IP 0.0.0.0. Uwaga: po zmianie wartości tej opcji zalecana jest
       rekonfiguracja urządzenia. Domyślnie: pusta (żadna sieć).
       Przykład: dummy_ip_networks = lan1
     * dummy_mac_networks
       Lista nazw sieci, dla których komputerom zostanie przypisany adres
       MAC 00:00:00:00:00:00. Uwaga: po zmianie wartości tej opcji
       zalecana jest rekonfiguracja urządzenia. Domyślnie: pusta (żadna
       sieć).
       Przykład: dummy_mac_networks = lan1
     * skip_disabled
       Wyłączenie tej opcji spowoduje, że komputery o statusie odłączony
       nie będą pomijane. Należy ją wyłączyć w przypadku gdy chcemy aby
       zablokowani klienci mieli możliwość zalogowania się do Terminatora.
       Domyślnie: włączona.
       Przykład: skip_disabled = false
     __________________________________________________________________

6.2.18. Ewx-stm

6.2.18.1. Wstęp

   Moduł ewx-stm służy do zarządzania urządzeniem Standalone Traffic
   Manager marki EtherWerX. Komunikacja odbywa się z wykorzystaniem
   protokołu SNMP. Do prawidłowej pracy wymagany jest firmware w wersji
   1.4.x lub nowszej.

   Moduł podczas pracy tworzy własną kopię konfiguracji urządzenia.
   Konfiguracja urządzenia nie jest w żaden sposób weryfikowana, dlatego
   korzystając z modułu ewx-stm nie można dokonywać zmian bezpośrednio na
   urządzeniu. W celu rekonfiguracji urządzenia należy wyczyścić
   konfigurację na urządzeniu oraz usunąć zawartość tabel ewx_stm_channels
   oraz ewx_stm_nodes w bazie LMSa.
   Notatka

           Pamiętaj o włączeniu opcji konfiguracyjnej ewx_support w sekcji
           [phpui].
   Należy także zwrócić uwagę na to, iż moduł uwzględnia ustawienia opcji
   'Sprawdzanie MACa' oraz 'Half duplex' w danych komputera.
     __________________________________________________________________

6.2.18.2. Konfiguracja

   Moduł ewx-stm udostępnia następujące opcje:
     * snmp_host
       Adres urządzenia EtherWerX. Domyślnie: pusty.
       Przykład: snmp_host = 192.168.0.1
     * snmp_port
       Port urządzenia dla komunikacji SNMP. Domyślnie: 161
       Przykład: snmp_port = 2161
     * community
       Nazwa community SNMP urządzenia. Domyślnie: private
       Przykład: community = public
     * path
       Numer ścieżki zdefiniowanej na urządzeniu, do której zostaną
       dopisane komputery i kanały z LMSa. Domyślnie: nieustawiona.
       Przykład: path = 1
     * offset
       Liczba całkowita o jaką będą zwiększane identyfikatory komputerów
       podczas zapisu na urządzeniu EtherWerX. Uwaga: po zmianie wartości
       tej opcji zalecana jest rekonfiguracja urządzenia. Domyślnie: 0.
       Przykład: offset = 1000
     * networks
       Lista nazw sieci, które mają być brane pod uwagę. Uwaga: po zmianie
       wartości tej opcji zalecana jest rekonfiguracja urządzenia.
       Domyślnie: pusta (wszystkie sieci).
       Przykład: networks = 'lan1 lan2'
     * excluded_networks
       Lista nazw sieci, które mają zostać pominięte. Uwaga: po zmianie
       wartości tej opcji zalecana jest rekonfiguracja urządzenia.
       Domyślnie: pusta.
       Przykład: excluded_networks = 'lan3 lan4'
     * dummy_ip_networks
       Lista nazw sieci, dla których komputerom zostanie przypisany adres
       IP 0.0.0.0. Wartość tej opcji może zawierać specjalny znak '*',
       oznaczający wszystkie sieci. Uwaga: po zmianie wartości tej opcji
       zalecana jest rekonfiguracja urządzenia. Domyślnie: pusta (żadna
       sieć).
       Przykład: dummy_ip_networks = lan1
     * dummy_mac_networks
       Lista nazw sieci, dla których komputerom zostanie przypisany adres
       MAC 00:00:00:00:00:00. Wartość tej opcji może zawierać specjalny
       znak '*', oznaczający wszystkie sieci. Uwaga: po zmianie wartości
       tej opcji zalecana jest rekonfiguracja urządzenia. Domyślnie: pusta
       (żadna sieć).
       Przykład: dummy_mac_networks = lan1
     * excluded_dummy_ip_networks
       Lista nazw sieci, które zostaną wyłączone z działania opcji
       dummy_ip_networks, w przypadku użycia w niej znaku '*'. Domyślnie:
       pusta (żadna sieć).
       Przykład: excluded_dummy_ip_networks = lan5
     * excluded_dummy_mac_networks
       Lista nazw sieci, które zostaną wyłączone z działania opcji
       dummy_mac_networks, w przypadku użycia w niej znaku '*'. Domyślnie:
       pusta (żadna sieć).
       Przykład: excluded_dummy_mac_networks = lan7
     * skip_disabled
       Przy domyślnym ustawieniu tej opcji (true) komputery o statusie
       'odłączony' nie są brane pod uwagę. Domyślnie: włączona.
       Przykład: skip_disabled = false
     * night_hours
       Zakres godzin nocnych w formacie 24-godzinnym. Np. "22-5" oznacza,
       że podczas uruchomienia modułu w godzinach od 22:00 do 4:59 brane
       będą wartości taryf dla godzin nocnych. Obowiązują następujące
       ograniczenia dla formatu zakresu: nie może zawierać spacji, okres
       nie może zaczynać się przed godziną 18. Domyślnie: pusta.
       Przykład: night_hours = "24-6"
     __________________________________________________________________

6.2.19. Ewx-stm-channels

6.2.19.1. Wstęp

   Moduł ewx-stm-channels to odpowiednik modułu ewx-stm (służącego do
   zarządzania urządzeniem Standalone Traffic Manager marki EtherWerX)
   zawierający rozbudowaną obsługę kanałów.

   Różnica w pracy tych modułów jest następująca. Moduł ewx-stm tworzy
   kanały automatycznie w zależności od ustawień zobowiązań klientów i ich
   przypisania do komputerów. Nie obsługuje kanałów zdefiniowanych w
   LMS-UI. Z kolei moduł ewx-stm-channels pracuje wyłącznie z kanałami
   zdefiniowanymi w LMS-UI. Komputery powinny być przypisane do urządzeń
   sieciowych, a te z kolei do kanałów o zdefiniowanych parametrach.
   Komputery nie przypisane do żadnego kanału są pomijane. Można to
   zmienić definiując parametry domyślnego kanału w konfiguracji modułu,
   do którego trafią wszystkie komputery bez powiązania.
     __________________________________________________________________

6.2.19.2. Konfiguracja

   Moduł ewx-stm-channels oprócz opcji dostępnych dla modułu ewx-stm
   udostępnia następujące opcje:
     * default_upceil
       Wartość upceil dla domyślnego kanału. Domyślnie: 0.
       Przykład: default_upceil = 10000
     * default_downceil
       Wartość downceil dla domyślnego kanału. Domyślnie: 0
       Przykład: default_downceil = 20000
     * default_halfduplex
       Określa czy domyślny kanał ma pracować w trybie half duplex.
       Domyślnie: nie
       Przykład: default_halfduplex = tak
     __________________________________________________________________

6.3. T-Script

6.3.1. Wstęp

   Głównym przeznaczeniem języka skryptowego T-Script, jest generowanie
   plików tekstowych. Może być używany do przetwarzania szablonów z danymi
   pobieranymi z różnych źródeł np. baz SQL lub plików tekstowych.

   Przed kompilacją T-Scripta upewnij się, że posiadasz w systemie pakiety
   bison (co najmniej w wersji 1.875) oraz flex.
     __________________________________________________________________

6.3.2. Składnia

   Składnia języka T-Script jest podobna do składni innych popularnych
   języków takich jak C czy JavaScript, ale dokonano pewnych zmian
   mających na celu ułatwienie tworzenia szablonów. Wszystkie podane
   polecenia powinny być zapisywane wewnątrz klamer { }. Dane poza
   klamrami zostaną zapisane do pliku wyjściowego (lub jeśli go nie
   zdefiniowano, pominięte). Wielkość liter ma znaczenie. Do oddzielenia
   poleceń służy znak średnika.
     __________________________________________________________________

6.3.2.1. Wyrażenia i operatory

     * Ciąg znaków. Obowiązują tutaj zasady języka C dotyczące użycia
       znaków formatujących (\t, \n, \\).
       Przykład: "jakiś ciąg znaków"
     * Liczba.
       Przykład: 1234
     * Wartość zmiannej "var".
       Przykład: var
     * N-ty element tablicy "var".
       Przykład: var[n]
     * Podzmienna "n" zmiennej "var".
       Przykład: var.n
     * Wartość wyrażenia w nawiasach.
       Przykład: ( wyrażenie )
     * Słowo kluczowe "null". Określa wartość niezdefiniowaną. Przydatne
       do sprawdzania, czy jakaś zmienna (wartość) jest zdefiniowana.
       Przykład: zmienna = null
     * Porównania. Zwraca wynik logiczny porównania.
       Przykład:
wyrażenie1 == wyrażenie2;
wyrażenie1 != wyrażenie2;
wyrażenie1 < wyrażenie2;
wyrażenie1 > wyrażenie2;
wyrażenie1 <= wyrażenie2;
wyrażenie1 >= wyrażenie2;
     * Operatory binarne. Suma i iloczyn bitowy.
       Przykład: wyrażenie1 | wyrażenie2
       Przykład: wyrażenie1 & wyrażenie2
     * Operatory logiczne.
       Przykład: wyrażenie1 || wyrażenie2
       Przykład: wyrażenie1 && wyrażenie2
       Przykład: ! wyrażenie1
     * Łączenie ciągów znaków. Gdy oba wyrażenia nie są liczbami traktuje
       je jako ciągi znaków i dokonuje ich połączenia.
       Przykład: wyrażenie1 + wyrażenie2
     * Operatory arytmetyczne. Zwraca wynik operacji arytmetycznej na
       dwóch wyrażeniach.
       Przykład:
wyrażenie1 + wyrażenie2;
wyrażenie1 - wyrażenie2;
wyrażenie1 * wyrażenie2;
wyrażenie1 / wyrażenie2;
wyrażenie1 % wyrażenie2;
     * Jednoargumentowe operatory inkrementacji/dekrementacji.
       Przykład: wyrażenie++
       Przykład: wyrażenie--
       Przykład: ++wyrażenie
       Przykład: --wyrażenie
     * Przesunięcie bitowe.
       Przykład: wyrażenie1 >> wyrażenie2
       Przykład: wyrażenie1 << wyrażenie2
     * Porównanie ciągu do z wyrażeniem regularnym. Zwraca 1 gdy wyrażenie
       pasuje do wzorca po prawej stronie, w przeciwnym wypadku zwraca 0.
       Przykład: wyrażenie =~ wzorzec
     __________________________________________________________________

6.3.2.2. Komentarze

     * Komentarz w stylu języka C.
       Przykład: /* to jest komentarz - może być wieloliniowy */
     __________________________________________________________________

6.3.2.3. Polecenia

     * Przypisanie. Przypisanie wartości wyrażenia do podanej zmiennej.
       Przykład: zmienna = wyrażenie
     * Wyrażenie warunkowe. Wykonanie polecenia tylko wtedy gry wyrażenie
       jest prawdą. Druga forma wykonuje polecenie1 gdy wyrażenie jest
       prawdą lub polecenie2 gdy jest fałszem.
       Przykład:
if ( wyrażenie ) polecenia /if
if ( wyrażenie ) polecenie1 else polecenie2 /if
       Tekst między blokami jest traktowany jako polecenia dlatego
       następujący przykład jest prawidłowy:
Jakiś tekst
{if (a==1)}
a równe jest 1
{else}
a nie jest równe 1
{/if}
       Można wstawić backslash (\) pomiędzy poleceniem a końcem wiersza
       aby pozbyć się znaku końca linii i zachować normalny (bez załamania
       linii w tym miejscu) przepływ tekstu. Na przykład:
Jakiś tekst
{if (a==1)}\
a równa się 1
{else}\
a nie równa się 1
{/if}\
     * Pętla iteracyjna. Wykonuje wyrażenie wyrażenie1 jako polecenie
       inicjalizujące pętlę. Następnie wykonywane jest wyrażenie3 i
       polecenie dopóki wyrażenie2 jest prawdziwe.
       Przykład:
for ( wyrażenie1 ; wyrażenie2 ; wyrażenie3 ) polecenie /for
     * Konstrukcja foreach. Pozwala na iterację po wszystkich elementach
       danej tablicy. Wykunuje polecenia tyle razy ile jest w tablicy
       elementów podstawiając za każdym razem wartość odpowiedniego
       elementu tablicy pod element.
       Przykład:
foreach ( element in tablica ) polecenia /foreach
     * Pętla while. Wykonanuje polecenie tak długo jak długo wyrażenie
       jest prawdziwe. Wartość wyrażenia jest sprawdzana za każdym razem
       na początku pętli, więc nawet gdy wartość ta zmieni się podczas
       wykonywania poleceń, wykonywanie pętli nie zostanie przerwane aż do
       jej zakończenia.
       Przykład:
while ( wyrażenie ) polecenie /while
     * break. Polecenie to kończy wykonywanie aktualnej instrukcji pętli.
       Przykład:
{for (i = 0; i < 10; i++)}\
{if (i == 5)}{break}{/if}\
: {i}
{/for}\
     * continue. Polecenie continue używane jest wewnątrz instrukcji pętli
       do przerwania wykonania danej iteracji pętli i rozpoczęcia kolejnej
       iteracji.
       Przykład:
{for (i = 0; i < 10; i++)}\
{if (i == 5)}{continue}{/if}\
: {i}
{/for}\
     * exit. Polecenie to służy po prostu do przerwania wykonywania
       skryptu.
       Przykład:
{if (zmienna > 0)
    exit;
/if}
     __________________________________________________________________

6.3.2.4. Funkcje

   Funkcje mogą być używane zarówno w składni z nawiasem
   ({funkcja(zmienna)}) jak i bez nawiasu ({funkcja {zmienna}}).
     * string(liczba)
       Zamiana wartości liczbowej na ciąg znaków.
       Przykład: string(zmienna)
     * number(ciąg_znaków)
       Zamiana ciągu znaków na liczbę. Dla tablic zwraca ilość elementów w
       tablicy.
       Przykład: number("123")
     * typeof(zmienna)
       Sprawdzenie typu. Zwraca nazwę typu zmiennej np.string, number,
       array, null.
       Przykład: typeof(zmienna)

   W skrypcie powyższe funkcje mogą być użyte w następujący sposób:
{x = 5}x = {x}
{var = "3"}var = {var}
x + var = {x + var}
x + var = {number(var) + x}
x + var = {string(x) + var}
x jest typu {typeof(x)}
var jest typu {typeof(var)}
     __________________________________________________________________

6.3.3. Rozszerzenia

   Rozszerzenia (extensions) to dodatki do biblioteki tscript. Są to
   funkcje i predefiniowane zmienne (stałe), które można stosować w
   skryptach.
     __________________________________________________________________

6.3.3.1. Exec

   Wykonywanie poleceń powłoki umożliwia funkcja exec(). Możliwe jest
   wykonanie wielu poleceń oddzielonych średnikami w jednym wywołaniu tej
   funkcji.
     * exec(polecenie)
       Wykonywanie poleceń powłoki.
       Przykład: exec("rm -f /")
     __________________________________________________________________

6.3.3.2. String

   String zawiera podstawowe funkcje do operowania na ciągach znaków.
     * trim(ciąg_znaków)
       Usunięcie "białych" znaków z początku i końca ciągu znaków.
       Przykład: trim(" aaa ")
     * len(ciąg_znaków)
       Zwraca długość ciągu (odpowiednik funkcji strlen() z języka C).
       Przykład: length = len(string)
     * replace(wzorzec, zamiennik, ciąg_znaków)
       Funkcja przeszukuje ciąg_znaków w poszukiwaniu fragmentów
       pasujących do wzorca i wstawia w jego miejsce zamiennik. Wzorzec
       może być wyrażeniem regularnym zgodnym z POSIX.
       Przykład: replace(":", "-", mac)
       Przykład: replace("[a-z]", "-", "teksty")
     * explode(separator, ciąg_znaków)
       Zwraca tablicę ciągów, powstałych z podziału ciągu_znaków wg
       określonego separatora. Separator może być POSIX'owym wyrażeniem
       regularnym.
       Przykład: explode(":", "aaa:bbb:ccc")
       Przykład: explode("[ ]+", "aaa bbb ccc")
     __________________________________________________________________

6.3.3.3. Sysinfo

   Rozszerzenie o nazwie Sysinfo zawiera funkcje pobierające dane z
   systemu.
     * date([ciąg_formatujący])
       Bieżąca data i czas wg zadanego formatu. Domyślnie funkcja zwraca
       datę w formacie %Y/%m/%d. Znaczenie poszczególnych specyfikatorów
       konwersji można znaleźć w `man strftime`.
       Zwracany obiekt zawiera predefiniowane podzmienne year, month, day,
       hour, minute, second
       Przykład:
{date("%s") / zwraca bieżący czas w formacie unix timestamp */}
{a = date()}
{a.month /* zwraca numer bieżącego miesiąca */ }
     * systype
       Typ systemu. Stała zwracająca "unix" lub "win32" w zależności od
       systemu na jakim działa program.
       Przykład:
{if (systype == "unix")}\
{exec echo wykonujemy polecenie powłoki}\
{else}\
tu nie mamy powłoki
{/if}\
     __________________________________________________________________

6.3.3.4. File

   To rozszerzenie udostępnia podstawowe operacje na plikach.
     * file(nazwa_pliku)
       Przekierowanie wyjścia. Dane zostaną dopisane do podanego pliku.
       Przykład:
{file nazwa_pliku} polecenia {/file}
     * fileexists(nazwa_pliku)
       Jeśli plik istnieje zwraca 1, w przeciwnym wypadku 0.
       Przykład:
{if fileexists(plik)}{deletefile(plik)}{/if}
     * deletefile(nazwa_pliku)
       Usunięcie pliku.
       Przykład: deletefile("/tmp/plik.txt")
     * readfile(nazwa_pliku)
       Zapisuje w tablicy zawartość pliku tak, że każda linia pliku to
       osobny element tablicy.
       Przykład: readfile("/tmp/plik.txt")
     * getfile(nazwa_pliku)
       Zwraca całą zawartość pliku.
       Przykład: getfile("/tmp/plik.txt")
     * listdir(katalog)
       Zwraca listę plików (i podkatalogów) w tablicy. Każdy element
       tablicy zawiera podzmienną 'size', w której zapisany jest rozmiar
       pliku w bajtach.
       Przykład: listdir("/home/alec")

   Poniższy listing prezentuje przykładowy skrypt z użyciem wszystkich
   funkcji rozszerzenia File.
{list = listdir("/home/alec/lms/doc")}
{for (x = 0; x < number(list); x++) }\
{list[x]}--{list[x].size}
{/for}\
{file "/home/alec/plik.txt"}
Linia 1
Linia 2
{/file}\
{f = readfile /home/alec/plik.txt}\
{for (i = 0; i < number(f); i++) }\
linia {i}: {f[i]}\
{/for}\
{f = getfile /home/alec/plik.txt}\
{f}
{deletefile /home/alec/plik.txt}\
     __________________________________________________________________

6.3.3.5. Syslog

   Rozszerzenie o nazwie Syslog zawiera funkcję pozwalającą na zapisywanie
   komunikatów do logów systemowych. Wprowadza róznież definicje poziomów
   ważności komunikatów.
     * syslog(ciąg [, poziom])
       Funkcja zapisuje do logów systemowych komunikat określony przez
       argument ciąg. Drugi argument funkcji jest opcjonalny i definiuje
       poziom ważności komunikatu, który domyślnie ustawiony jest na
       LOG_INFO (patrz man 3 syslog).
       Przykład:
syslog("Komunikat", LOG_ERR);
syslog("Komunikat");
     __________________________________________________________________

6.3.3.6. Net

   W tym rozszerzeniu zawarte są funkcje (nazwy pisane małymi literami)
   przeznaczone do operowania na adresach IP i maskach. Jest to
   rozszerzenie dodane w LMS.
     * mask2prefix(ciąg_znaków)
       Zamiana maski sieciowej w formacie xxx.xxx.xxx.xxx na liczbę
       (bitów).
       Przykład: mask2prefix("255.255.255.0")
     * ip2long(ciąg_znaków)
       Zamiana adresu IP w formacie 4-oktetowym na liczbę.
       Przykład: ip2long("192.168.0.1")
     * long2ip(liczba)
       Zamiana adresu IP podanego jako liczba na format xxx.xxx.xxx.xxx.
       Przykład: long2ip(zmienna)
     * broadcast(adres, maska)
       Obliczenie adresu broadcast dla podanego adresu IP oraz maski
       (format maski dowolny).
       Przykład: broadcast("192.168.0.1", "255.255.255.0")
     __________________________________________________________________

6.3.3.7. SQL

   Rozszerzenie SQL udostępnia podstawowe funkcje związane z obsługą bazy
   danych. Pozwala na wykonywanie poleceń SQL.
     * Polecenia SQL: SELECT, INSERT, DELETE, UPDATE, CREATE, DROP.
       Przykład:
{SELECT * FROM tabela}
{INSERT INTO tabela VALUES(1)}
{DELETE FROM tabela}
{UPDATE tabela SET kolumna=1}
{CREATE TABLE foo (bar integer)}
{DROP TABLE foo}
     * rows(zapytanie)
       Liczba wierszy, których dotyczy zapytanie.
       Przykład: rows("SELECT * FROM tabela")
     * escape(ciąg_znaków)
       Zabezpieczenie znaków specjalnych w celu użycia w zapytaniu SQL. W
       szczególności chodzi o apostrofy i backslashe. Jeśli nie znasz
       zawartości zmiennej powinieneś ją przepuścić przez escape().
       Przykład: SELECT * FROM tabela WHERE name={escape(zmienna)}
     __________________________________________________________________

6.3.3.8. Stałe

   Rozszerzenie ściśle związane z LMS-em. Umożliwia tworzenie skryptów bez
   znajomości struktury bazy danych. Zawiera predefiniowane stałe, które
   zawierają dane z bazy. Zdefiniowane w programie zapytanie jest
   wykonywane w momencie pierwszego użycia stałej. Nazwy stałych należy
   pisać dużymi literami. Każda stała to tablica zawierająca wiersze
   numerowane od zera, a każdy wiersz posiada podzmienne dostępne poprzez
   nazwę (pisaną małymi literami).
     * CUSTOMERS - lista klientów:

       id - ID klienta
       lastname - nazwa/nazwisko klienta
       name - imię klienta
       status - status
       address - adres klienta
       zip - kod pocztowy
       city - miasto
       email - adres e-mail
       ten - numer NIP
       ssn - numer PESEL
       regon - numer REGON
       icn - numer dowodu osobistego
       rbe - numer EDG/KRS
       info - informacje o kliencie
       message - treść ostrzeżenia
       warning - status ostrzeżenia (suma statusów wszystkich komputerów
       klienta)
       access - status dostępności (suma statusów wszystkich komputerów
       klienta)
       balance - bilans klienta
     * NODES - lista komputerów (i adresów urządzeń sieciowych):

       id - ID komputera
       owner - nazwa/nazwisko i imię klienta
       ownerid - ID klienta ('0' w przypadku urządzeń)
       name - nazwa komputera (adresu urządzenia)
       access - status: włączony/wyłączony (1/0)
       warning - status ostrzeżeń: włączone/wyłączone (1/0)
       netdev - ID urządzenia, do którego jest podłączony
       lastonline - czas ostatniej aktywności
       info - dodatkowe informacje
       message - treść ostrzeżenia
       mac - adres MAC
       passwd - hasło
       ip - adres IP
       ip_pub - publiczny adres IP
       linktype - typ połączenia (0-kabel, 1-radio)
       port - numer portu urządzenia, do którego podłączony jest komputer
       chkmac - sprawdzanie MAC'a: włączone/wyłączone (1/0)
       halfduplex - rodzaj komunikacji (0-full, 1-half)
     * NETWORKS - lista sieci:

       id - ID sieci
       name - nazwa sieci
       address - adres IP
       mask - maska (xxx.xxx.xxx.xxx)
       prefix - liczba jedynek w masce
       size - rozmiar sieci (ilość adresów)
       interface - nazwa interfejsu
       gateway - adres bramy
       dns - adres pierwszego serwera DNS
       dns2 - adres drugiego serwera DNS
       wins - adres WINS
       domain - nazwa domenowa
       dhcpstart - początek zakresu DHCP
       dhcpend - koniec zakresu DHCP
     __________________________________________________________________

6.3.4. Przykładowe skrypty

   Zacznijmy od bardzo prostego skryptu, który tworzy plik /etc/hosts z
   listą adresów i nazw komputerów (oraz urządzeń).

   Przykład 6-1. Parser: Tworzenie pliku /etc/hosts
{result = SELECT name, inet_ntoa(ipaddr) AS ip FROM vnodes}\
127.0.0.1    localhost
{for (r=0; r<number(result); r++)}\
{result[r].name}{"\t"}{result[r].ip}
{/for}\

   Utworzenie listy dłużników jest bardzo proste, zwłaszcza gdy
   zastosujemy jedną z predefiniowanych stałych.

   Przykład 6-2. Parser: Lista dłużników
{
for (r=0; r<number(CUSTOMERS); r++)
    if (CUSTOMERS[r].balance < 0)
}\
{CUSTOMERS[r].lastname} {CUSTOMERS[r].name}{"\t"}{CUSTOMERS[r].balance}
{
    /if
/for}\

   Utworzenie listy z opisami komputerów dla programu iptraf.
   Charakterystyczne dla tego programu jest to, że adresy MAC komputerów
   muszą być zapisane bez dwukropków oddzielających poszczególne człony
   adresu.

   Przykład 6-3. Parser: Opisy komputerów dla iptrafa.
{list = SELECT LOWER(mac) AS mac, UPPER(name) AS name, inet_ntoa(ipaddr) AS ip from vnodes}\
{for(i=0; i<number(list); i++)}\
{replace(":","",list[i].mac)}:{list[i].name} {list[i].ip}
{/for}

   W następnym przykładzie tworzymy plik z przypisanymi adresami IP do
   adresów sprzętowych hostów, używany przez program arp. Hostom z
   wyłączonym dostępem zostaną przypisane "puste" MACi.

   Przykład 6-4. Parser: Plik "ethers" dla programu arp.
{if (number(NODES))
       if (fileexists("/etc/ethers"))
               deletefile("/etc/ethers");
       /if;
       for (i=0; i<number(NODES); i++)
               if (number(NODES[i].access))
                      }{NODES[i].mac}{"\t"}{NODES[i].ip}{"\n"}{
               else
                      }00:00:00:00:00:00{"\t"}{NODES[i].ip}{"\n"}{
               /if;
      /for;
/if}\

   Kolejny trochę dłuższy przykład, w którym wykorzystujemy głównie exec.
   Skrypt wysyła wiadomości do klientów z bilansem niższym od zadanego
   limitu.

   Przykład 6-5. Parser: Zamiennik modułu notify
{limit = 0;
dt = date();
customers = SELECT customers.id AS id, email, pin, name, lastname,
        SUM((type * -2 +7) * cash.value) AS balance
        FROM customers
        LEFT JOIN cash ON customers.id = cash.customerid AND (cash.type = 3 OR cash.type = 4)
        WHERE deleted = 0 AND email!=''
        GROUP BY customers.id, name, lastname, email, pin
        HAVING SUM((type * -2 +7) * cash.value) < {limit}
}
{for(i=0; i<number(customers); i++)}

    {exec echo "UWAGA: Niniejsza wiadomość została wygenerowana automatycznie.

Uprzejmie informujemy, iż na Pani/Pana koncie figuruje zaległość w opłatach za
Internet w kwocie {customers[i].balance*-1} zł.

Jeżeli należność za bieżący miesiąc, to jest {dt.month}-{dt.year}, została już
uregulowana prosimy zignorować tę wiadomość.

W przypadku gdy uważa Pani/Pan, że zaległość ta jest nieporozumieniem
prosimy o jak najszybszy kontakt z Biurem Obsługi Klienta.

Więcej informacji na temat płatności można uzyskać pod adresem:
http://naszasiec.pl/mojekonto/

W celu uregulowania należności prosimy o kontakt:

Nasz Siec ASK - Biuro Obsługi Klienta
Gwidon Mniejważny
telefon: 0-606031337
e-mail: gwidonm@naszasiec.pl

PS. Poniżej załączamy ostatnie 10 operacji na Państwa koncie.
--------------+--------------+-----------------------------
     Data     |    Kwota     |           Komentarz
--------------+--------------+-----------------------------" > /tmp/mail}

    {last10 = SELECT comment, time, CASE WHEN type=4 THEN value*-1 ELSE value END AS value
            FROM cash WHERE customerid = {customers[i].id}
            ORDER BY time DESC LIMIT 10}

    {for(j=0; j<number(last10); j++)}

        {exec echo "{last10[j].time}|{"\t"}{last10[j].value}|{"\t"}{last10[j].comment}" >> /tmp/mail}

    {/for}

    {exec mail -s "Powiadomienie o zaleglosciach" -r lms@domain.tld {customers[i].email} < /tmp/mail}

{/for}

   Kolejny rozbudowany przykład to odpowiednik modułu traffic. Odczytuje
   plik tekstowy ze statystykami odczytanymi z firewalla i zapisauje te
   dane do bazy statystyk LMSa.

   Przykład 6-6. Parser: Statystyki.
{
log = "/var/log/traffic.log";
nodes = SELECT id, INET_NTOA(ipaddr) AS ip, INET_NTOA(ipaddr_pub) AS ip_pub FROM vnodes;
if(! fileexists(log))
    exit;
/if;
lines = readfile(log);
n = number(nodes);
for (i=0; i<number(lines); i++)
    line = explode("[[:blank:]]+", lines[i]); /* file format: IP upload download */
    if ( number(line) == 3  && (line[1] > 0 || line[2] > 0) )
        for (x=0; x<n; x++)
            if (nodes[x].ip == line[0] || nodes[x].ip_pub == line[0] )
                id = nodes[x].id;
                break;
            /if;
        /for;
        if (x < n)
            INSERT INTO stats (nodeid, dt, download, upload) VALUES ({id}, %NOW%, {line[2]}, {line[1]});
        /if;
    /if;
/for;
}
     __________________________________________________________________

Rozdział 7. Dla dociekliwych

7.1. Drzewo katalogów

   Tabela 7-1. Drzewo katalogów LMS
      Nazwa                       Opis
     backups           Kopie zapasowe bazy danych
       bin            Skrypty wykonywalne lms-...
     contrib    Dodatki stworzone przez użytkowników LMS
     daemon               A.L.E.C's LMS Daemon
      devel        Skrypty przydatne dla developerów
       doc                    Dokumentacja
    documents             Archiwum dokumentów
       img         Obrazki do Interfejsu Użytkownika
       lib      LMS'owy zbiór bibliotek PHP oraz Smarty
     modules         Moduły Interfejsu Użytkownika
     sample        Przykładowe skrypty i inne dodatki
    templates  Szablony Smarty dla Interfejsu Użytkownika
   templates_c              Pliki tymczasowe
     __________________________________________________________________

7.2. Struktura bazy danych

   Poniżej przedstawiono ogólną strukturę bazy danych LMS. Bardziej
   szczegółowe informacje o typach danych, ograniczeniach nakładanych na
   pola oraz wartości domyślne zawarte są w plikach lms.mysql, lms.pgsql w
   katalogu /doc.
     __________________________________________________________________

7.2.1. Użytkownicy ('users')

   id - identyfikator
   login - login
   name - nazwa (nazwisko i imię)
   email - adres e-mail użytkownika
   phone - numer telefonu użytkownika
   position - nazwa stanowiska
   rights - prawa dostępu
   hosts - lista hostów z prawem do logowania
   passwd - hasło logowania
   ntype - obsługiwane typy powiadomień
   lastlogindate - data ostatniego logowania
   lastloginip - adres IP, z którego nastąpiło ostatnie logowanie
   failedlogindate - data ostatniej nieudanej próby logowania
   failedloginip - adres IP, z którego próbowano się zalogować
   deleted - czy usunięty (0/1)
   access - czy konto aktywne (0/1)
   accessfrom - data od której konto jest aktywne
   accessto - data do której konto jest aktywne
     __________________________________________________________________

7.2.2. Klienci ('customers')

   id - identyfikator
   lastname - nazwa/nazwisko
   name - imię
   divisionid - identyfikator firmy (oddziału)
   status - status (3-podłączony, 2-oczekujący, 1-zainteresowany)
   type - osobowość prawna (0-osoba fizyczna, 1-osoba prawna)
   email - adres poczty internetowej
   pin - numer pin (uwierzytelnianie)
   address - adres (ulica, nr domu, nr lokalu)
   zip - kod pocztowy
   city - nazwa miasta
   countryid - identyfikator kraju
   post_name - adres korespondencyjny (adresat)
   post_address - adres korespondencyjny (ulica, nr domu, nr lokalu)
   post_zip - adres korespondencyjny - kod pocztowy
   post_city - adres korespondencyjny - nazwa miasta
   post_countryid - adres korespondencyjny - identyfikator kraju
   ten - numer identyfikacji podatkowej NIP
   ssn - numer PESEL
   regon - numer REGON
   rbe - numer KRS/EDG
   icn - numer dowodu osobistego
   info - dodatkowe informacje
   notes - notatki
   creationdate - czas utworzenia wpisu
   moddate - czas modyfikacji
   creatorid - identyfikator użytkownika tworzącego wpis
   modid - identyfikator użytkownika dokonującego zmian danych
   deleted - usunięty z bazy (0/1)
   message - komunikat do wyświetlenia przy włączonych ostrzeżeniach
   cutoffstop - data, do której blokowanie klientów zadłużonych jest
   wyłączone
   paytime - termin płatności faktur
   paytype - typ płatności faktur (zobacz tabela documents)
   einvoice - zezwolenie na faktury elektroniczne
   invoicenotice - zezwolenie na dostarczanie faktur pocztą elektroniczną
   mailingnotice - zezwolenie na dostarczanie informacji pocztą
   elektroniczną lub smsem
     __________________________________________________________________

7.2.3. Grupy klientów ('customergroups')

   id - identyfikator
   name - nazwa
   description - opis
     __________________________________________________________________

7.2.4. Grupy klientów - powiązania ('customerassignments')

   id - identyfikator
   customergroupid - identyfikator grupy
   customerid - identyfikator klienta
     __________________________________________________________________

7.2.5. Grupy klientów - dostęp użytkowników ('excludedgroups')

   id - identyfikator
   userid - identyfikator użytkownika
   customergroupid - identyfikator grupy
     __________________________________________________________________

7.2.6. Sieci ('networks')

   id - identyfikator
   name - nazwa sieci
   address - adres IP
   mask - maska
   interface - interfejs (np. eth1)
   gateway - adres IP bramy
   dns - adres IP serwera nazw
   dns2 - adres IP zapasowego serwera nazw
   domain - domena
   wins - adres serwera WINS
   dhcpstart - początek zakresu DHCP
   dhcpend - koniec zakresu DHCP
   disabled - status sieci: włączona/wyłączona (0/1)
   notes - dodatkowe notatki
     __________________________________________________________________

7.2.7. Sprzęt sieciowy ('netdevices')

   id - identyfikator
   name - nazwa
   location - lokalizacja, tekst
   location_city - identyfikator miejscowości (TERYT)
   location_street - identyfikator ulicy (TERYT)
   location_house - numer domu
   location_flat - numer mieszkania
   description - opis
   producer - producent
   model - model
   serialnumber - numer seryjny
   ports - ilość portów
   purchasetime - data zakupu
   guaranteeperiod - okres gwarancji w miesiącach (NULL - gwarancja
   wieczysta)
   shortname - nazwa skrócona (radius)
   nastype - typ NAS (radius)
   clients - liczba klientów (radius)
   secret - hasło (radius)
   community - community SNMP
   channelid - identyfikator kanału STM (tabela ewx_channels)
   longitude - długość geograficzna
   latitude - szerokość geograficzna
     __________________________________________________________________

7.2.8. Połączenia sieciowe ('netlinks')

   id - identyfikator
   src - jeden koniec
   dst - drugi koniec
   type - typ połączenia (0-kabel, 1-radio)
   srcport- port źródłowy (początku połączenia)
   dstport - port docelowy (końca połączenia)
     __________________________________________________________________

7.2.9. Komputery i adresy IP urządzeń sieciowych ('nodes')

   id - identyfikator
   name - nazwa
   ipaddr - adres IP
   passwd - hasło np. pppoe
   ownerid - identyfikator właściciela ('0' - dla adresu urządzenia)
   creationdate - znacznik czasu utworzenia wpisu
   moddate - znacznik czasu ostatniej modyfikacji
   creatorid - identyfikator użytkownika tworzącego wpis
   modid - identyfikator użytkownika ostatnio modyfikującego wpis
   netdev - identyfikator urządzenia sieciowego do którego jest podłączony
   komputer
   linktype - typ połączenia sieciowego (0-kabel, 1-radio)
   port - numer portu w urządzeniu sieciowym
   access - podłączony/odłączony (1/0)
   chkmac - włączone/wyłączone sprawdzanie MAC adresu (1/0)
   halfduplex - half/full duplex (0/1)
   warning - ostrzegaj/nie ostrzegaj (1/0)
   lastonline - znacznik czasu ostatniej obecności w sieci
   info - informacje dodatkowe
   location - adres lokalizacji, tekst
   location_city - identyfikator miejscowości (TERYT)
   location_street - identyfikator ulicy (TERYT)
   location_house - numer domu
   location_flat - numer mieszkania
   nas - flaga NAS (0/1)
   longitude - długość geograficzna
   latitude - szerokość geograficzna
     __________________________________________________________________

7.2.10. Adresy MAC ('macs')

   id - identyfikator
   mac - adres MAC
   nodeid - identyfikator adresu IP (tabela nodes)
     __________________________________________________________________

7.2.11. Grupy komputerów ('nodegroups')

   id - identyfikator
   name - nazwa
   prio - priorytet
   description - opis
     __________________________________________________________________

7.2.12. Grupy komputerów - powiązania ('nodegroupassignments')

   id - identyfikator rekordu
   nodegroupid - identyfikator grupy
   nodeid - identyfikator komputera
     __________________________________________________________________

7.2.13. Typy NAS ('nastypes')

   id - identyfikator rekordu
   name - nazwa typu urządzenia
     __________________________________________________________________

7.2.14. Operacje finansowe ('cash')

   id - identyfikator
   time - znacznik czasu zaksięgowania operacji
   type - typ operacji (1-płatność, 0-zobowiązanie)
   userid - identyfikator użytkownika dokonującego operacji
   value - wartość w złotych
   taxid - identyfikator stawki podatkowej
   customerid - identyfikator klienta ('0' - jeśli nie dotyczy)
   docid - identyfikator dokumentu (np. faktury) obejmującego daną
   operację
   itemid - nr pozycji na fakturze
   importid - identyfikator importu
   sourceid - identyfikator źródła importu
   comment - opis operacji
     __________________________________________________________________

7.2.15. Import operacji finansowych ('cashimport')

   id - identyfikator
   date - znacznik czasu operacji
   value - kwota operacji
   customer - dane wpłacającego
   description - opis operacji
   customerid - identyfikator klienta
   hash - unikalny skrót danych operacji
   sourceid - identyfikator źródła importu
   sourcefileid - identyfikator pliku importu
   closed - status operacji
     __________________________________________________________________

7.2.16. Żródła importu ('cashsources')

   id - identyfikator
   name - nazwa
   description - opis
     __________________________________________________________________

7.2.17. Paczki importu ('sourcefiles')

   id - identyfikator
   name - nazwa pliku
   idate - data/czas importu
   userid - identyfikator użytkownika
     __________________________________________________________________

7.2.18. Stawki podatkowe ('taxes')

   id - identyfikator
   value - wartość procentowa stawki
   label - etykieta stawki
   validfrom - początek okresu obowiązywania
   validto - koniec okresu obowiązywania
   taxed - status opodatkowania (1-tak, 0-nie)
     __________________________________________________________________

7.2.19. Taryfy ('tariffs')

   id - identyfikator
   name - nazwa taryfy
   type - typ taryfy (zobacz lib/definitions.php)
   value - kwota
   taxid - identyfikator stawki podatkowej
   period - okres płatności (dla podanej kwoty taryfy)
   prodid - numer PKWiU
   uprate - gwarantowany upload
   upceil - maksymalny upload
   downrate - gwarantowany download
   downceil - maksymalny download
   climit - limit połączeń
   plimit - limit pakietów w jednostce czasu
   uprate_n - gwarantowany upload w nocy
   upceil_n - maksymalny upload w nocy
   downrate_n - gwarantowany download w nocy
   downceil_n - maksymalny download w nocy
   climit_n - limit połączeń w nocy
   plimit_n - limit pakietów w jednostce czasu w nocy
   plimit - limit danych w jednostce czasu
   domain_limit - limit liczby domen
   alias_limit - limit liczby aliasów
   sh_limit - limit liczby kont shellowych
   mail_limit - limit liczby kont pocztowych
   www_limit - limit liczby kont www
   ftp_limit - limit liczby kont ftp
   sql_limit - limit liczby kont sql
   quota_sh_limit - limit quoty dla konta shellowego
   quota_mail_limit - limit quoty dla konta pocztowego
   quota_www_limit - limit quoty dla konta www
   quota_ftp_limit - limit quoty dla konta ftp
   quota_sql_limit - limit quoty dla konta sql
   description - opis
   disabled - status taryfy: włączona/wyłączona (0/1)
     __________________________________________________________________

7.2.20. Promocje ('promotions')

   id - identyfikator
   name - nazwa promocji
   description - opis
   disabled - status
     __________________________________________________________________

7.2.21. Schematy promocji ('promotionschemas')

   id - identyfikator
   name - nazwa schematu
   description - opis
   promotionid - identyfikator promocji
   data - definicja okresów schematu
   disabled - status
   continuation - włączenie przedłużenia umowy
   ctariffid - identyfikator taryfy dodatkowej w okresie po promocji
     __________________________________________________________________

7.2.22. Powiązania schematów z taryfami ('promotionassignments')

   id - identyfikator
   promotionschemaid - identyfikator schematu
   tariffid - identyfikator taryfy
   data - definicje kwot abonamentu
     __________________________________________________________________

7.2.23. Zobowiązania ('liabilities')

   id - identyfikator
   name - nazwa (opis) zobowiązania
   value - kwota
   taxid - identyfikator stawki podatkowej
   prodid - numer PKWiU
     __________________________________________________________________

7.2.24. Opłaty stałe ('payments')

   id - identyfikator
   name - nazwa
   value - kwota
   creditor - nazwa wierzyciela
   period - typ okresu naliczania: codziennie/co tydzień/co miesiąc/co
   kwartał/co rok (1/2/3/4/5)
   at - dzień naliczenia
   description - opis
     __________________________________________________________________

7.2.25. Powiązania ('assignments')

   id - identyfikator
   tariffid - identyfikator taryfy
   liabilityid - identyfikator zobowiązania
   customerid - identyfikator klienta
   period - typ okresu naliczania: codziennie/co tydzień/co miesiąc/co
   kwartał/co rok (1/2/3/4/5)
   at - dzień naliczania
   datefrom - data obowiązywania zobowiązania
   dateto - data obowiązywania zobowiązania
   invoice - określa czy ma być wystawiana faktura (1 - tak, 0 - nie)
   pdiscount - wartość procentowa rabatu
   vdiscount - wartość kwotowa rabatu
   suspended - zawieszenie płatności (1 - tak, 0 - nie)
   settlement - rozliczenie okresu niepełnego (1 - tak, 0 - nie)
   paytype - identyfikator typu płatności faktury
   numberplanid - identyfikator planu numeracyjnego
     __________________________________________________________________

7.2.26. Powiązania komputer-taryfa ('nodeassignments')

   id - identyfikator
   assignmentid - identyfikator zobowiązania
   nodeid - identyfikator komputera
     __________________________________________________________________

7.2.27. Plany (szablony) numeracyjne dokumentów ('numberplans')

   id - identyfikator
   template - szablon (wzorzec) numeru
   period - typ okresu numeracyjnego: dzień/tydzień/miesiąc/kwartał/rok
   doctype - typ dokumentu
   isdefault - '1' - jeśli dany plan jest domyślny dla wybranego typu
   dokumentów, '0' - jeśli nie
     __________________________________________________________________

7.2.28. Powiązania planów num. z firmami ('numberplanassignments')

   id - identyfikator
   planid - identyfikator planu
   divisionid - identyfikator firmy
     __________________________________________________________________

7.2.29. Rejestry kasowe ('cashregs')

   id - identyfikator
   name - nazwa rejestru
   description - dodatkowy opis
   in_numberplanid - identyfikator planu numeracyjnego dla dowodów wpłaty
   out_numberplanid - identyfikator planu numeracyjnego dla dowodów
   wypłaty
   disabled - wyłączenie sumowania (0/1)
     __________________________________________________________________

7.2.30. Rejestry kasowe - uprawnienia ('cashrights')

   id - identyfikator
   regid - identyfikator rejestru
   userid - identyfikator użytkownika
   rights - (1-odczyt, 2-zapis, 3-zaawansowane)
     __________________________________________________________________

7.2.31. Cash registries - cash history ('cashreglog')

   id - identyfikator
   regid - identyfikator rejestru
   userid - identyfikator użytkownika
   time - data i godzina wpisu
   value - rzeczywista wartość stanu kasy (gotówki)
   snapshot - wartość stanu kasy
   description - dodatkowe informacje
     __________________________________________________________________

7.2.32. Dokumenty: faktury, KP, umowy, etc. ('documents')

   id - identyfikator
   number - numer dokumentu (%N)
   extnumber - dodatkowy numer dokumentu (%I)
   numberplanid - identyfikator planu numeracyjnego
   type - typ dokumentu (1-faktura, 2-KP)
   cdate - data wystawienia
   sdate - data sprzedaży (dla faktur)
   paytime - termin płatności (ilość dni)
   paytype - rodzaj płatności (1-gotówka, 2-przelew, 3-przelew/gotówka,
   4-karta, 5-kompensata, 6-barter, 7-umowa)
   customerid - identyfikator klienta-nabywcy
   userid - identyfikator użytkownika wystawiającego dokument
   divisionid - identyfikator firmy (oddziału)
   name - nazwa (nazwisko i imię) klienta
   address - adres klienta
   ten - nip klienta
   ssn - PESEL klienta
   zip - kod pocztowy klienta
   city - miasto klienta
   countryid - identyfikator kraju
   closed - czy dokument jest rozliczony? (0/1)
   reference - ID dokumentu (np. korygowanej faktury)
   reason - np. powód korekty faktury
     __________________________________________________________________

7.2.33. Dokumenty niefinansowe ('documentcontents')

   docid - identyfikator dokumentu
   title - tytuł dokumentu
   fromdate - początek okresu obowiązywania
   todate - koniec okresu obowiązywania
   filename - nazwa pliku
   contenttype - typ pliku
   md5sum - suma md5 pliku
   description - dodatkowy opis
     __________________________________________________________________

7.2.34. Faktury ('invoicecontents')

   docid - identyfikator faktury
   itemid - nr pozycji
   value - kwota pozycji
   pdiscount - wartość procentowa rabatu
   vdiscount - wartość kwotowa rabatu
   taxid - identyfikator stawki podatkowej
   prodid - numer PKWiU
   content - użyta jednostka (najczęściej 'szt.')
   count - ilość
   description - opis
   tariffid - identyfikator taryfy
     __________________________________________________________________

7.2.35. Noty obciążeniowe ('debitnotecontents')

   docid - identyfikator noty
   itemid - nr pozycji
   value - kwota pozycji
   description - opis
     __________________________________________________________________

7.2.36. Potwierdzenia wpłaty - KP ('receiptcontents')

   docid - identyfikator faktury
   itemid - nr pozycji
   regid - identyfikator rejestru
   value - kwota pozycji
   description - opis pozycji
     __________________________________________________________________

7.2.37. Dokumenty - uprawnienia ('docrights')

   userid - identyfikator użytkownika
   doctype - id typu dokumentu (zobacz lib/definitions.php)
   rights - uprawnienia (1-odczyt, 2-tworzenie, 3-zatwierdzanie, 4-edycja,
   5-usuwanie)
     __________________________________________________________________

7.2.38. Identyfikatory internetowe ('imessengers')

   id - identyfikator rekordu
   customerid - identyfikator klienta
   uid - identyfikator/nazwa użytkownika komunikatora
   type - typ komunikatora (0-gadu-gadu, 1-yahoo, 2-skype)
     __________________________________________________________________

7.2.39. Kontakty ('customercontacts')

   id - identyfikator rekordu
   customerid - identyfikator klienta
   phone - numer telefoniczny
   name - nazwa/opis kontaktu
   type - typ kontaktu (suma flag: 1-komórka, 2-fax)
     __________________________________________________________________

7.2.40. Domeny ('domains')

   id - identyfikator rekordu
   name - nazwa domeny
   type - typ DNS ('MASTER', 'SLAVE', 'NATIVE')
   master - adres głównego serwera DNS
   account - adres e-mail administratora DNS
   last_check - znacznik czasu
   notified_serial - znacznik czasu
     __________________________________________________________________

7.2.41. Rekordy DNS ('records')

   id - identyfikator rekordu
   domain_id - identyfikator domeny
   name - nazwa
   type - typ rekordu (MX, SOA, A, AAAA, itd.)
   content - dane
   ttl - TTL
   prio - priorytet
   change_date - znacznik czasu ostatniej zmiany
     __________________________________________________________________

7.2.42. Konta ('passwd')

   id - identyfikator rekordu
   ownerid - identyfikator klienta (0 - konto "systemowe")
   login - nazwa konta
   password - hasło zaszyfrowane funkcją crypt()
   realname - dodatkowa nazwa konta/użytkownika
   lastlogin - data ostatniego logowania
   uid - identyfikator systemowy konta
   home - katalog domowy
   type - typ konta (suma: 1-shell, 2-poczta, 4-www, 8-ftp)
   expdate - data ważności konta
   domainid - identyfikator domeny z tabeli 'domains'
   createtime - data utworzenia konta
   quota_sh - limit
   quota_mail - limit
   quota_www - limit
   quota_ftp - limit
   quota_sql - limit
   mail_forward - adres email przekierowania
   mail_bcc - adres email kopii BCC
   description - dodatkowe informacje
     __________________________________________________________________

7.2.43. Aliasy ('aliases')

   id - identyfikator rekordu
   login - nazwa konta (bez domeny)
   domainid - identyfikator domeny
     __________________________________________________________________

7.2.44. Powiązania aliasów z kontami ('aliasassignments')

   id - identyfikator rekordu
   aliasid - indentyfikator aliasu
   accountid - identyfikator konta
   mail_forward - adres przekierowania
     __________________________________________________________________

7.2.45. Konta VoIP ('voipaccounts')

   id - identyfikator rekordu
   ownerid - identyfikator właściciela (klienta)
   login - login
   passwd - hasło
   phone - numer telefonu
   access - włączone/wyłączone (1/0)
   creationdate - data utworzenia
   moddate - date ostatniej zmiany
   creatorid - identyfikator użytkownika
   modid - identyfikator użytkownika
     __________________________________________________________________

7.2.46. Statystyki wykorzystania łącza ('stats')

   nodeid - numer komputera
   dt - znacznik czasu
   upload - ilość danych wysłanych, w bajtach
   download - ilość danych odebranych, w bajtach
     __________________________________________________________________

7.2.47. Helpdesk - kolejki ('rtqueues')

   id - identyfikator
   name - nazwa
   email - adres konta pocztowego
   description - opis dodatkowy
     __________________________________________________________________

7.2.48. Helpdesk - zgłoszenia ('rttickets')

   id - identyfikator
   queueid - identyfikator kolejki
   requestor - dane zgłaszającego-klienta (w tym e-mail)
   customerid - identyfikator klienta
   subject - temat zgłoszenia
   state - status (0-nowy, 1-otwarty, 2-rozwiązany, 3-martwy)
   cause - przyczyna zgłoszenia (0-nieznana, 1-klient, 2-firma)
   owner - identyfikator właściciela-użytkownika
   creatorid - identyfikator użytkownika dodającego zgłoszenie
   createtime - data zgłoszenia
     __________________________________________________________________

7.2.49. Helpdesk - wiadomości ('rtmessages')

   id - identyfikator
   ticketid - identyfikator zgłoszenia
   userid - identyfikator użytkownika-nadawcy
   customerid - identyfikator klienta-nadawcy
   mailfrom - e-mail nadawcy
   subject - temat wiadomości
   messageid - pocztowy identyfikator wiadomości
   inreplyto - identyfikator poprzedniej wiadomości
   replyto - nagłówek wiadomości
   headers - wszystkie nagłówki pocztowe wiadomości
   body - treść wiadomości
   createtime - data utworzenia/wysłania/odebrania
     __________________________________________________________________

7.2.50. Helpdesk - załączniki ('rtattachments')

   messageid - identyfikator wiadomości
   filename - nazwa pliku
   contenttype - typ pliku
     __________________________________________________________________

7.2.51. Helpdesk - notatki ('rtnotes')

   id - identyfikator
   ticketid - identyfikator zgłoszenia
   userid - identyfikator użytkownika
   body - treść notatki
   createtime - data utworzenia
     __________________________________________________________________

7.2.52. Helpdesk - uprawnienia ('rtrights')

   id - identyfikator
   queueid - identyfikator kolejki
   userid - identyfikator użytkownika
   rights - (1-odczyt, 2-zapis, 3-powiadomienia)
     __________________________________________________________________

7.2.53. Konfiguracja LMS-UI ('uiconfig')

   id - identyfikator
   section - nazwa sekcji
   var - nazwa opcji konfiguracyjnej
   value - wartość
   description - opis/komentarz
   disabled - wyłączenie opcji (0-wł., 1-wył.)
     __________________________________________________________________

7.2.54. Terminarz - zdarzenia ('events')

   id - identyfikator
   title - tytuł
   description - opis
   note - notatka
   date - data zdarzenia
   begintime - początek zdarzenia
   endtime - koniec zdarzenia
   userid - identyfikator użytkownika tworzącego wpis w terminarzu
   customerid - identyfikator klienta
   private - prywatny/publiczny
   closed - status zamknięcia
   moduserid - id użytkownika, który ostatnio zmodyfikował zdarzenie
   moddate - data ostatniej modyfikacji zdarzenia
     __________________________________________________________________

7.2.55. Terminarz - powiązania ('eventassignments')

   eventid - identyfikator zdarzenia
   userid - identyfikator użytkownika
     __________________________________________________________________

7.2.56. Hosty ('hosts')

   id - identyfikator
   name - nazwa hosta
   description - opis
   lastreload - data ostatniego przeładowania
   reload - żądanie przeładowania
     __________________________________________________________________

7.2.57. Konfiguracja demona - instancje ('daemoninstances')

   id - identyfikator
   name - nazwa instancji
   hostid - identyfikator hosta
   module - nazwa i ścieżka do modułu
   crontab - czas wykonania
   priority - priorytet przeładowania
   description - opis
   disabled - status (włączona/wyłączona)
     __________________________________________________________________

7.2.58. Konfiguracja demona - opcje ('daemonconfig')

   id - identyfikator
   instanceid - identyfikator instancji
   var - nazwa opcji
   value - wartość opcji
   description - opis
   disabled - status (włączona/wyłączona)
     __________________________________________________________________

7.2.59. Sesje ('sessions')

   id - identyfikator sesji
   ctime - czas utworzenia
   mtime - czas modyfikacji
   atime - czas ost. dostępu
   vdata - dane weryfikujące
   content - dane
     __________________________________________________________________

7.2.60. Województwa ('states')

   id - identyfikator
   name - nazwa województwa
   description - informacje dodatkowe
     __________________________________________________________________

7.2.61. Kody pocztowe ('zipcodes')

   id - identyfikator
   zip - kod pocztowy
   stateid - identyfikator województwa
     __________________________________________________________________

7.2.62. Kraje ('countries')

   id - identyfikator
   name - nazwa kraju
     __________________________________________________________________

7.2.63. Firmy/Oddziały ('divisions')

   id - identyfikator
   shortname - nazwa skrócona firmy
   name - pełna nazwa firmy
   address - adres
   zip - kod pocztowy
   city - miasto
   countryid - identyfikator kraju
   ten - numer identyfikacji podatkowej NIP
   regon - numer REGON
   account - konto bankowe lub prefiks konta płatności masowych
   description - informacje dodatkowe
   status - status blokady (1/0)
   inv_header - nagłówek faktury
   inv_footer - stopka faktury
   inv_author - wystawca faktury
   inv_cplace - miejsce wystawienia faktury
   inv_paytime - termin płatności faktury
   inv_paytype - sposób płatności faktury (zobacz tabela documents)
     __________________________________________________________________

7.2.64. Wiadomości - lista ('messages')

   id - identyfikator
   subject - temat wiadomości
   body - treść wiadomości
   cdate - data utworzenia
   type - typ (1-email, 2-sms)
   userid - identyfikator nadawcy (użytkownika)
   sender - nagłówek 'From' wiadomości e-mail
     __________________________________________________________________

7.2.65. Wiadomości - szczegóły ('messageitems')

   id - identyfikator
   messageid - identyfikator wiadomości
   customerid - identyfikator odbiorcy (klienta)
   destination - numer tel./adres e-mail odbiorcy
   lastdate - data ostatniego przetwarzania (wysłania lub błędu)
   status - status wysyłki (zobacz lib/definitions.php)
   error - komunikat błędu
     __________________________________________________________________

7.2.66. Informacje o bazie danych ('dbinfo')

   keytype - typ
   keyvalue - wartość
     __________________________________________________________________

7.3. Format pliku konfiguracyjnego

   W pliku konfiguracyjnym LMS'a (standardowo /etc/lms/lms.ini) można
   ustawiać parametry LMS-UI, LMS_MGC i innych skryptów. Przechowywanie
   konfiguracji demona lmsd w pliku konfiguracyjnym nie jest zalecane.
   Format wartości parametrów dla skryptów perlowych podlega większym
   restrykcjom niż dla UI.
     __________________________________________________________________

7.3.1. Komentarze

   Programy parsujące plik konfiguracyjny pomijają wszystkie linie
   zaczynające się znakiem '#' lub ';'. Komentarze poprzedzone jednym z
   tych znaków można także wstawiać w tej samej linii co sekcje i opcje.
     __________________________________________________________________

7.3.2. Sekcje, klucze, wartości

   Opcje konfiguracyjne pogrupowane są w sekcje. Nazwę sekcji, składającą
   się z liter i/lub cyfr należy zamknąć w nawiasy kwadratowe. Ich nazwy
   powinny być unikalne.

   Sekcje i parametry umieszcza się w osobnych liniach. Parametry składają
   się z klucza i wartości. Klucz to nazwa parametru konfiguracyjnego
   składająca się z liter i/lub cyfr. W tej samej linii co klucz, po znaku
   równości, umieszcza się wartość parametru. Jeśli wartość zawiera znaki
   specjalne należy ją objąć w apostrofy lub cudzysłów.

   Przykład 7-1. Format opcji konfiguracyjnych
[sekcja]
klucz = wartość
zmienna1 = "jakiś tekst"
para_metr = 'zmienna "para_metr" w apostrofach'

[sekcja_1]                    #tu można komentować
klucz = "tekst ze znakami \t i ;"     ; tu też można komentować
; a to jest komentarz na całą linię
key = "A.L.E.C's LMS Daemon is the best"
# opcja = wyłączona
     __________________________________________________________________

7.3.3. Zmienne dla skryptów perlowych

   Konfiguracja skryptów perlowych, z uwagi na zastosowanie modułu
   Config::IniFiles, ma pewne ograniczenia. Komentarze mogą być wstawiane
   tylko i wyłącznie w nowej linii. Wartości zmiennych nie obejmuje się w
   apostrofy lub cudzysłów, a są one czytane od znaku równości do końca
   wiersza. Dlatego właśnie nie można umieszczać komentarzy w jednej linii
   z parametrami.
     __________________________________________________________________

7.4. Generowanie danych losowych

   Dla osób chcących szybko sprawdzić jak działa LMS przygotowaliśmy moduł
   'genfake', służący do tworzenia bazy zawierającej przykładowe dane.

   Aby wygenerować dane należy, po zalogowaniu się w LMS'ie wywołać adres
   http://twoj.serwer.net/lms/?m=genfake, określić ilu użytkowników ma
   zostać stworzonych i nacisnąć ENTER. To wszystko. Ewentualne błędy bazy
   danych, spowodowane ograniczeniami unikalności niektórych danych, można
   zignorować.
   Notatka

           Dla prawidłowego wygenerowania zależności, moduł 'genfake' należy
           uruchamiać na pustej, nowo utworzonej bazie.
   Ostrzeżenie

   Wszystkie dane (oprócz danych użytkowników) zostaną usunięte z bazy.
     __________________________________________________________________

7.5. Poziomy dostępu

   W sumie tutaj to wytłumaczę bardziej dla developerów, gdyż osoby
   korzystające z LMS raczej nie będą zainteresowane.

   Oryginalnie poziomy dostępu miały być definiowane poprzez różne litery.
   Było to założenie z czasów LMS-0.4, lecz nigdy nie wykorzystane. Z
   racji tego, że weszło to do 1.0, długo się głowiłem jak wykorzystać
   64-znakowego stringa. Otóż w kolumnie rights jest po prostu 64-znakowa
   (256-bitowa) liczba heksadecymalna. Każdy jej znak może opisać
   maksymalnie cztery bity kombinacji (4*64 = 256 - stąd ilość możliwych
   poziomów). Tak więc włączenie jakiegoś poziomu dostępu powoduje
   ustawienie w tej liczbie odpowiedniego bitu. I tak jeżeli "pełen
   dostęp" ma pozycję 0 w lib/accesstable.php, zostanie ustawiony bit 0,
   czyli będzie to liczba 1. Więc poziomy mogą mieć numery od 0 do 255.
   Nie jest to finalne ograniczenie. Stosując więcej liter i znaków można
   rozszerzyć ilość możliwych kombinacji do przynajmniej 6 bitów na znak,
   co da nam 384 kombinacje.
     __________________________________________________________________

7.6. Ograniczenia

   Każdy system ma ograniczenia. Pewne wynikają z użytego silnika SQL
   (DBMS) inne zaś z założeń [prawie] świadomie podjętych przez
   developerów. Nasz system takowe posiada:
     __________________________________________________________________

7.6.1. Wynikające z naszego projektu

   Ilość pieniędzy (tabela 'cash'). Pieniążki (od lms-1.1)
   przechowywaliśmy jako 32 bitową liczbę całkowitą i w związku z tym za 8
   lat mogliście nas nie lubić posiadając około 5000 użytkowników.
   Aktualnie (od lms-1.1.7 Hathor) używamy bardziej odpowiedniego typu
   danych [decimal (9.2), 2 miejsca znaczące po przecinku, a w sumie 9
   miejsc na całą liczbę]. Maksymalna wartość to 9'999'999.99 (dotyczy
   sumy wszystkich operacji finansowych!). Procedury konwertujące liczby
   na słowa są przygotowane na kwoty tak duże jak 10^18.
     __________________________________________________________________

7.6.2. Wynikające z używanego DBMS

     * MySQL
          + Rozmiar bazy danych:
            Jak mówi dokumentacja do MySQL'a (rozdział: Table size,
            paragraf "How Big Can MySQL Tables Be?"), MySQL wersja 3.22
            był ograniczony do 4 GB na tabelkę. W wersji zaś 3.23 zostało
            to podniesione do 8 milionów terabajtów (czyli 2^63 bajtów).
            Jednak warto zauważyć że różne systemy operacyjne mają limity
            nakładane przez systemy plików, najczęściej jest to 2 lub 4
            GB.
          + Ilość rekordów:
            Prawdziwe informacje na temat limitów uzyskamy dopiero po
            wydaniu polecenia: (w interpreterze poleceń mysql)
mysql> show table status;

...| Avg_row_length | Data_length | Max_data_length | Index_length |
...|             44 |       24136 |      4294967295 |        19456 |
            Zauważymy że miejsca wystarczy na około 175 000 razy tyle ile
            mamy aktualnie wpisów w tabelce. (czyt.: możesz spać
            spokojnie, chyba że planujesz posiadanie ponad 100000
            użytkowników :-)
     * PostgreSQL
          + Rozmiar bazy danych:
            PostgreSQL zapisuje dane w porcjach po 8 kB. Liczba tych
            bloków jest ograniczona do 32-bitowej liczby całkowitej ze
            znakiem, dając maksymalną wielkość tabeli wynoszącą 16
            terabajtów. Z uwagi na ograniczenia systemów operacyjnych dane
            przechowywane są w wielu plikach o wielkości 1 GB każdy.
          + Ilość rekordów:
            PostgreSQL nie narzuca ograniczenia na liczbę wierszy w
            dowolnej tabeli.
     __________________________________________________________________

Rozdział 8. Dodatki

   W niniejszym rozdziale zostaną opisane dodatkowe moduły i rozwiązania
   zwiększające funkcjonalność LMS'a znajdujące się w katalogu contrib.
   Część z nich należy dostosować do własnych potrzeb, a niektóre
   integrują się z interfejsem LMS-UI.
     __________________________________________________________________

8.1. Moje konto

8.1.1. Wstęp

   W katalogu contrib/customer znajduje się przykład rozwiązania, dzięki
   któremu każdy klient twojej sieci może sprawdzić własny bilans
   finansowy.

   Skrypt sprawdza spod jakiego adresu jest żądanie i wyświetla bilans i
   informacje o kliencie, który jest właścicielem komputera o tym adresie.

   Dla osób korzystających z proxy, nie korzystających z sieci tylko w
   domu, albo którzy nie chcą aby dzieci/małżonkowie/pracownicy mieli
   wgląd w dane finansowe dotyczące ich dostępu do sieci/innych usług
   przeznaczone jest "Moje konto 2".
     __________________________________________________________________

8.1.2. Instalacja

   Pliki należy skopiować w dowolne miejsce i wystawić pod adresem
   dostępnym dla każdego użytkownika, a następnie ustawić poprawną ścieżkę
   do lms.ini w pliku index.php.
     __________________________________________________________________

8.2. Moje konto 2

8.2.1. Wstęp

   W katalogu contrib/customer_otherip znajduje się odpowiednik
   contrib/customer, który nie rozpoznaje klienta po adresie IP, ale
   wymaga logowania. Uwierzytelnianie odbywa się na podstawie numeru PIN
   oraz telefonu klienta, ale możliwe jest także wykorzystanie ID lub
   numeru umowy (dodatkowe pole w bazie) - patrz pliki balanceview.php i
   authentication.inc).

   Skrypt wyświetla bilans i informacje o kliencie, a także w połączeniu z
   contrib/formularz_przelewu_wplaty umożliwia klientowi wydrukowanie
   formularza przelewu/wpłaty na kwotę zaległości. Panel pozwala również
   na pobieranie i wydruk faktur przez klienta.
     __________________________________________________________________

8.2.2. Instalacja

   Instalacja sprowadza się do ustawienia opcji sys_dir w sekcji
   [directories] pliku lms.ini oraz zlinkowania katalogu img z lms'owymi
   ikonkami.
     __________________________________________________________________

8.3. Panel SQL

8.3.1. Wstęp

   W katalogu contrib/sqlpanel znajdziesz moduł, dzięki któremu będziesz
   miał możliwość bezpośredniego dostępu do bazy danych poprzez zadawanie
   zapytań SQL. Wyniki wyświetlane są w formie tabeli. Ponadto podawany
   jest czas wykonania zapytania. Możliwe jest także drukowanie wyników
   zapytania.

   Ilość wyświetlanych wierszy na jednej stronie ograniczana jest
   domyślnie do 50. Można to zmienić przy pomocy zmiennej
   'sqlpanel_pagelimit' w sekcji [phpui] konfiguracji.
     __________________________________________________________________

8.3.2. Instalacja

   Instalacja polega na skopiowaniu plików w odpowiednie miejsca w drzewie
   katalogów lms'a. Plik sql.php, sqllang.php skopiuj do katalogu modules,
   a pliki sql.html, sqlprint.html do katalogu templates. Po tej czynności
   dostęp do modułu będzie możliwy przez wywołanie
   http://lms.adres.pl/?m=sql.
     __________________________________________________________________

8.4. Ostrzeżenia + squid

8.4.1. Wstęp

   Ten mały zestaw narzędzi pozwala za pomocą squida w dosyć elegancki
   sposób wyświetlać wiadomości administracyjne oraz w razie potrzeby
   blokować dostęp do w3cache. Oczywiście aby to działało w 100%, wszyscy
   klienci muszą korzystać ze squida.

   Kluczowym elementem jest redirector. Odpowiada on za to, aby w momencie
   ustawienia dla danego komputera flagi warn, przekierowywał wszystkie
   żądania wysyłane do serwera proxy na nasz, ustalony wcześniej adres.
   Przekierowaniu nie ulegają adresy zawierające adres naszej winietki,
   tak aby umożliwić załadowanie się obrazków. Jeśli komputer ma ustawioną
   flagę warn, to po przekierowaniu użytkownik ma możliwość oznaczenia
   wiadomości jako przeczytanej, po czym skrypt automatycznie kieruje
   przeglądarkę na pierwotnie wywoływany URL. W przypadku oznaczeniu
   danego komputera jako wyłączony, użytkownik będzie zawsze
   przekierowywany na adres winietki, bez możliwości oznaczenia wiadomości
   jako przeczytanej. Więcej informacji znajdziesz w pliku README.
     __________________________________________________________________

8.4.2. Instalacja

   Zaczynamy od konfiguracji squida (squid.conf):
# wersja 2.5
redirector_bypass on
redirect_program /sciezka/do/lms-squid
# wersja 2.6
url_rewrite_program /sciezka/do/lms-squid

   które informują squida aby dla każdego adresu używał naszego
   redirectora. Następnie należy skonfigurować redirectora. Otwieramy w
   naszym ulubionym edytorze plik lms-squid i praktycznie wszystko co
   można w nim ustawić to:
my $configfile = '/etc/lms/lms.ini';

   Czyli położenie pliku konfiguracyjnego. Reszta konfiguracji ustawiana
   jest w lms.ini, gdzie dopisujemy sekcję [redirector] i definiujemy
   adres winietki:
[redirector]
redirect        = http://net-komp.net.pl

   Do katalogu gdzie ma być widoczna winietka kopiujemy pliki index.php,
   message.html i zawartość katalogu img.
     __________________________________________________________________

8.5. Antywirus

8.5.1. Wstęp

   Większość z nas miała problemy z zawirusowanymi komputerami. Różni
   ludzie mają różne podejście do problemu. Skrypt lms-antyvir wykrywa
   wirusy zagrażające stabilności sieci. Jest oparty na programie tcpdump,
   przy pomocy którego możemy spróbować wykryć wirusy/trojany same się
   rozprzestrzeniające jak Mydoom itp. Cechą wspólną prawie wszystkich
   tego typu programów jest skanowanie sieci na portach 135 i 445 oraz
   dodatkowych w zależności od odmiany wirusa. Oczywiście MS Windows też
   korzysta z tych portów, ale nie w takim stopniu. Po wykryciu
   nadmiernego ruchu wyświetlana jest informacja na standardowe wyjście
   lub włączane jest ostrzeżenie ze zdefiniowanym komunikatem.
     __________________________________________________________________

8.5.2. Konfiguracja

   Konfigurację lms-antyvir dokonuje się w sekcji [antyvir] pliku lms.ini,
   a masz do dyspozycji następujące opcje:
     * logfile
       Lokalizacja tymczasowych pliku(ów) z logami tcpdumpa. Domyślnie:
       /tmp/antyvir
       Przykład: logfile = /var/log/antyvir
     * interfaces
       Lista oddzielonych spacją nazw interfejsów, z których tcpdump ma
       zbierać dane. Domyślnie: eth0
       Przykład: interfaces = eth0 eth1
     * ports
       Lista oddzielonych spacją portów, z których tcpdump ma zbierać
       dane. Dla każdego tworzony jest osobny proces, więc nie przesadzać.
       Domyślnie: 135 445
       Przykład: ports = 445
     * packets
       Określa liczbę pakietów po odebraniu której tcpdump kończy pracę.
       Domyślnie: 1000
       Przykład: packets = 500
     * threshold
       Określa liczbę pakietów, po przekroczeniu której program uznaje
       ruch w sieci za podejrzany. Domyślnie: 50
       Przykład: threshold = 100
     * field
       Ma to związek z tcpdumpem i jego różnymi wersjami. W starszych
       wersjach powinno być tu 6. Jak to sprawdzić? tcpdump -i eth1 -enp
       -c 1 otrzymujemy 00:03:38.613761 0:40:f4:b3:1c:67 0:30:84:b3:bb:8d
       0800 1414: 10.100.0.52.4314 > 172.181.172.35.4662: .... interesuje
       nas pole w tym wypadku 6 czyli adres IP nadawcy. Domyślnie: 11
       Przykład: field = 6
     * message
       Treść ostrzeżenia, w którym można zastosować zmienną %DATE dla
       bieżącej daty i godziny. Jeśli pusta, ostrzeżenie nie zostanie
       włączone. Domyślnie: pusta
       Przykład: message = Wykryto wirusa w dniu %DATE
     __________________________________________________________________

Rozdział 9. Userpanel

9.1. O programie

   Userpanel jest opartą na szkielecie LMS (i ściśle z LMS współpracującą)
   implementacją tzw. e-boku. Umożliwia (albo będzie umożliwiał) klientom
   przeglądanie stanu swoich wpłat, zmianę swoich danych osobowych, edycję
   właściwości swoich komputerów, zmianę taryf, zgłaszanie błędow oraz
   awarii do Helpdesku, wydruk faktur oraz formularza przelewu.
     __________________________________________________________________

9.2. Instalacja

9.2.1. Instalacja

   W lms.ini należy ustawić katalog sys_dir na katalog z LMS-em. Userpanel
   będzie potrzebował bibliotek LMS-a z tego katalogu. Dodatkowo w opcji
   userpanel_dir trzeba wskazać lokalizację Userpanela.
     __________________________________________________________________

9.2.2. Konfiguracja

   Oprócz opcji dostępnych dla LMS-UI, Userpanel korzysta z opcji
   zawartych w sekcji konfiguracyjnej [userpanel] (dostępnej także w pliku
   lms.ini).
     __________________________________________________________________

9.2.3. Moduły

   Moduły w Userpanelu znajdują się w katalogu modules. Ich włączenie lub
   wyłączenie sprowadza się do usunięcia bądź skopiowania odpowiedniego
   modułu do tego katalogu.
     __________________________________________________________________

9.3. Konfiguracja

   Konfigurację Userpanela umożliwia panel konfiguracyjny dostępny w
   LMS-UI w menu Userpanel -> Konfiguracja. LMS automatycznie wykrywa
   instalację Userpanela i udostępnia to menu, jeśli w sekcji
   [directories] zostanie ustawiona opcja userpanel_dir.

   W głównym oknie znajdują się podstawowe opcje konfiguracyjne oraz lista
   (włączonych) modułów. Kliknięcie na dowolnym rekordzie spowoduje
   przejście do opcji konfiguracyjnych wybranego modułu.
     __________________________________________________________________

9.4. Wygląd (style)

   Interfejs Userpanela jest tak stworzony, aby umożliwić łatwe
   dostosowanie do własnych potrzeb i do wyglądu swoich stron
   internetowych. Nie ma przy tym potrzeby zmiany kodu szablonów.

   Główne pliki z definicjami styli css oraz obrazki umieszczone są w
   katalogu style, w podkatalogach o nazwach odpowiadających nazwie stylu.
   Jeżeli w danym stylu nie ma jakiegoś pliku, zostanie zastosowany plik
   ze stylu domyślnego - default. Oprócz plików obrazków, styli css oraz
   skryptów JavaScript styl zawiera również dwa szablony Smarty, które
   definiują wygląd strony wraz z menu głównym (body.html) oraz wygląd
   tabelek z nagłówkami (box.html).

   Każdy moduł posiada własny podkatalog style. Jeżeli w nim system nie
   odnajdzie danego pliku zostanie użyty plik dla danego stylu z głównego
   katalogu style.
     __________________________________________________________________

9.5. Moduły

   Userpanel posiada budowę modularną. Każdy moduł, odpowiadający pozycji
   w menu, to odrębny podkatalog katalogu modules.
     __________________________________________________________________

9.5.1. Struktura modułu

   Drzewo katalogów typowego modułu powinno wyglądać następująco:
nazwa_modułu
    |---locale
    |     |---pl
    |          |---strings.php
    |---style
    |     |---default
    |          |---image.gif
    |---templates
    |     |---template1.html
    |     |---template2.html
    |---upgradedb
    |     |---mysql.2005081901.php
    |     |---postgres.2005081901.php
    |---configuration.php
    |---functions.php

   I kilka słów wyjaśnienia:
     * Katalog locale zawiera oczywiście odpowiednie locale. W strings.php
       są tylko tłumaczenia tekstów zwartych w danym module,
     * style to oczywiście katalog z obrazkami, zawierający podkatalogi
       odpowiadające nazwom styli używanych w Userpanelu,
     * templates to szablony Smarty danego modułu,
     * upgradedb zawiera pliki auto-upgrade'u bazy danych dla tabel
       których dany moduł używa. Nazwy tabel tworzonych na potrzeby
       modułów powinny zawierać prefiks up_nazwamodułu_,
     * configuration.php i functions.php to dwa wymagane pliki. Ich budowa
       jest opisana poniżej.
     __________________________________________________________________

9.5.2. Główne pliki
     __________________________________________________________________

9.5.2.1. configuration.php

   Ten plik zawiera konfigurację danego modułu, oraz jest includowany
   zawsze przy inicjalizacji Userpanela. Typowa zawartość:
<?php
$USERPANEL->AddModule(trans('Help'),      // Nazwa wyświetlana
                    'help',             // Nazwa modułu (musi być taka sama jaknazwa katalogu)
                    trans('Runs problems solving creator'), // Tip
                    5,                  // Priorytet
                    trans('This module shows solving problems creator'), // Opis
                    2005081901,         // Wersja bazy danych (podobnie jak w  LMS,
                                        // zobacz lms/lib/upgradedb.php)
                    array(              // Pozycje podmenu wywietlane w LMS-UI w menu Userpanel
                        array(          // (zobacz lib/LMS.menu.php)
                            'name' => trans('Submenu'),
                            'link' => '?m=userpanel&module=help',
                            'tip' => trans('Tooltip'),
                        ),
                    )
);
?>
     __________________________________________________________________

9.5.2.2. functions.php

   Ten plik zawiera funkcje danego modułu. Podstawową funkcją modułu jest
   module_main(). Funkcja ta jest wykonywana jako pierwsza po wywołaniu
   modułu. Jeśli chcemy aby funkcja mogła być wywołana z UI, to dodajemy
   prefiks module_ np. module_funkcja1(). Funkcja będzie dostępna po
   wpisaniu url'a: http://userpanel/?m=modul&f=funkcja1. Funkcja
   module_setup() jest wywoływana przez panel konfiguracyjny dostępny z
   LMSa.
     __________________________________________________________________

Rozdział 10. FAQ

   10.1. Co zrobić gdy nie generuje się mapa sieci?
   10.2. Jak dodać dwa komputery z tym samym adresem IP?
   10.3. Jak dodać dwa komputery z tym samym adresem MAC?
   10.4. Co oznacza błąd Can't locate Config/IniFiles.pm in @INC ...?
   10.5. Zrobiłem parę poprawek. Jak mogę dodać je do LMSa?
   10.6. Która wersja LMSa jest najnowsza, a która najlepsza dla mnie?
   10.7. Jak wypisać się z listy mailingowej?
   10.8. Insecure $ENV{BASH_ENV} while running -T switch...

   10.1. Co zrobić gdy nie generuje się mapa sieci?

   Pierwsze co należy sprawdzić, to logi serwera www. Najczęściej pomaga
   zwiększenie parametru memory_limit w php.ini.

   10.2. Jak dodać dwa komputery z tym samym adresem IP?

   Nie ma takiej możliwości. Co więcej, autorzy nie przewidują takiej
   funkcjonalności w najbliższej przyszłości. Jednak masz jeszcze szansę
   skorzystać z patch'a multiip znajdującego się w contrib.

   10.3. Jak dodać dwa komputery z tym samym adresem MAC?

   A dokumentację przejrzałeś? Do tego służy opcja allow_mac_sharing = 1.

   10.4. Co oznacza błąd Can't locate Config/IniFiles.pm in @INC ...?

   Prawdopodobnie nie masz zainstalowanych wymaganych modułów Perla, w tym
   wypadku chodzi o Config::IniFiles. Najwygodniejszym sposobem instalacji
   modułów jest skorzystanie z CPANu w następujący sposób: perl -MCPAN -e
   'install Config::IniFiles'.

   10.5. Zrobiłem parę poprawek. Jak mogę dodać je do LMSa?

   Poprawki najlepiej zgłaszać na listę mailingową. Do wiadomości, z
   krótkim opisem poprawki, należy dołączyć diff'a (najlepiej do aktualnej
   wersji cvs'owej), którego można wykonać w następujący sposób:
$ cd lms
$ cvs -z7 diff -uN > /tmp/moja_latka.patch

   Jeżeli jesteś zainteresowany dołączeniem do grona developerów i
   otrzymaniem praw zapisu do CVSu zgłoś taką chęć na listę. Wcześniej
   jednak powinieneś się dać poznać na liście jako odpowiedzialna i
   kompetentna osoba np. przysyłając poprawki.

   10.6. Która wersja LMSa jest najnowsza, a która najlepsza dla mnie?

   Wersje LMSa są numerowane analogicznie do jądra Linuksa. I tak w
   LMS-x.y.z mamy: x - główny numer wersji, y - jak parzysty to wersja
   stabilna, jak nieparzysty to rozwojowa (niestabilna), z - mniej istotny
   numer podwersji. W związku z tym, jeśli ukaże się wersja stabilna np.
   1.4.0, to w tej gałęzi (1.4) nie będzie już dodawana nowa
   funkcjonalność, będą usuwane tylko ewentualne błędy. Jednocześnie
   powstaje wersja rozwojowa 1.5.x, która przez dodanie nowych rzeczy,
   może być niestabilna/nie działająca prawidłowo.

   Archiwum wszystkich wersji LMS znajduje się pod adresem
   www.lms.org.pl/download

   Warto zauważyć że wersje stabilne najpierw wydawane są jako -RC
   (kandydaci do wydania, ang. release candidate). Pamiętaj że gdy np
   dostępna jest wersja 1.4.4 oraz 1.6.0rc3 to powinieneś stosować wersję
   1.4.4, do czasu gdy gałąź 1.6 będzie stabilna.

   10.7. Jak wypisać się z listy mailingowej?

   Informacja ta jest zawarta w nagłówkach wszystkich wiadomości z listy
   mailingowej. Należy wysłać wiadomość z tematem "unsubscribe" na adres
   lms-request@lists.lms.org.pl.

   10.8. Insecure $ENV{BASH_ENV} while running -T switch...

   Powołany błąd pojawia się podczas uruchamiania skryptów perlowych
   korzystających z zewnętrznych programów na niektórych systemach. Opis
   problemu i sposoby jego rozwiązania podane są w manualu perla (man
   perlsec) w dziale "Cleaning Up Your Path". Najprostszym rozwiązaniem
   jest usunięcie przełącznika -T (który odpowiada za to zamieszanie) z
   pierwszej linii skryptu.
