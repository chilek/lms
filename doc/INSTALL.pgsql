----------------------------------------------------------------------
                    LAN Management System 1.1-cvs
----------------------------------------------------------------------
         Wspó³praca z PostgreSQL - instalacja i konfiguracja
----------------------------------------------------------------------
Spis treci:
    1. Wstêp
    2. Instalacja serwera PostgreSQL
    3. Utworzenie bazy danych
    4. Konfiguracja LMS (lms.ini)

1. Wstêp

LMS jest testowany na PostgreSQL 7.2.3 i nowszych, ale poniewa¿ ¿adne
skomplikowane w³a¶ciwo¶ci tej bazy nie s± wykorzystywane, nie powinno
byæ problemów z innymi wersjami. Je¿eli nie mamy zainstalowanego serwera
PostgreSQL, najlepszym rozwi±zaniem bêdzie w³asnorêczna kompilacja
ze ¼róde³ dostêpnych na stronie www.postgresql.org.

2. Instalacja

Jest to wersja skrócona instalacji, wiêcej informacji znajdziesz 
w dokumentacji postgresa. Po ¶ci±gniêciu i rozpakowaniu wchodzimy 
do katalogu g³ównego i wydajemy kolejno poni¿sze polecenia. 

    $ ./configure --enable-locale
    $ gmake
    $ su
    $ gmake install
    $ adduser postgres
    $ mkdir /usr/local/pgsql/data
    $ chown postgres /usr/local/pgsql/data
    $ su - postgres
    $ /usr/local/pgsql/bin/initdb -D /usr/local/pgsql/data
    $ /usr/local/pgsql/bin/postmaster -D /usr/local/pgsql/data >logfile 2>&1 &

3. Utworzenie bazy danych

Maj±c uruchomiony serwer mo¿esz przyst±piæ do tworzenia bazy o nazwie 'lms', 
której w³a¶cicielem bêdzie u¿ytkownik z loginem 'lms'. 

    $ /usr/local/pgsql/bin/createuser -d -A -P lms
    $ /usr/local/pgsql/bin/createdb -E LATIN2 -U lms lms
    $ /usr/local/pgsql/bin/psql -d lms -U lms -f /lms/doc/lms.pgsql
    
4. Konfiguracja LMS (lms.ini)

Dla systemu LMS domy¶lnym serwerem baz danych jest MySQL, dlatego w sekcji
[database] pliku /etc/lms/lms.ini nale¿y ustawiæ nastêpuj±ce opcje:
    
    type 	= postgres
    user	= lms
    password	= has³o_podane_przy_tworzeniu_u¿ytkownika_lms
    
Uwaga: Has³o jest wymagane w zale¿no¶ci od konfiguracji autentykacji 
       u¿ytkowników postgresa w /usr/local/pgsql/data/pg_hba.conf. 
       Domy¶lnie has³o nie jest wymagane.

Po takim zabiegu, o ile LMS'owi uda siê nawi±zaæ po³±czenie do bazy danych,
mo¿na ju¿ bez problemu dostaæ siê do systemu. Je¿eli jednak w bazie danych
nie ma ¿adnego konta administratora, jedyn± rzecz± jak± bêdziemy widzieæ
bêdzie formularz dodania administratora. Je¿eli podamy prawid³owe dane
administratora, LMS przeniesie nas na stronê logowania gdzie odrazu bêdziemy
mogli u¿yæ nowo utworzonego konta.

------------------------------------------------------------------------
$Id$
------------------------------------------------------------------------
