----------------------------------------------------------------------
                    LAN Management System 1.1-cvs
----------------------------------------------------------------------
         Wsp�praca z PostgreSQL - instalacja i konfiguracja
----------------------------------------------------------------------
Spis treci:
    1. Wst�p
    2. Instalacja serwera PostgreSQL
    3. Utworzenie bazy danych
    4. Konfiguracja LMS (lms.ini)

1. Wst�p

LMS jest testowany na PostgreSQL 7.2.3 i nowszych, ale poniewa� �adne
skomplikowane w�a�ciwo�ci tej bazy nie s� wykorzystywane, nie powinno
by� problem�w z innymi wersjami. Je�eli nie mamy zainstalowanego serwera
PostgreSQL, najlepszym rozwi�zaniem b�dzie w�asnor�czna kompilacja
ze �r�de� dost�pnych na stronie www.postgresql.org.

2. Instalacja

Jest to wersja skr�cona instalacji, wi�cej informacji znajdziesz 
w dokumentacji postgresa. Po �ci�gni�ciu i rozpakowaniu wchodzimy 
do katalogu g��wnego i wydajemy kolejno poni�sze polecenia. 

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

Maj�c uruchomiony serwer mo�esz przyst�pi� do tworzenia bazy o nazwie 'lms', 
kt�rej w�a�cicielem b�dzie u�ytkownik z loginem 'lms'. 

    $ /usr/local/pgsql/bin/createuser -d -A -P lms
    $ /usr/local/pgsql/bin/createdb -E LATIN2 -U lms lms
    $ /usr/local/pgsql/bin/psql -d lms -U lms -f /lms/doc/lms.pgsql
    
4. Konfiguracja LMS (lms.ini)

Dla systemu LMS domy�lnym serwerem baz danych jest MySQL, dlatego w sekcji
[database] pliku /etc/lms/lms.ini nale�y ustawi� nast�puj�ce opcje:
    
    type 	= postgres
    user	= lms
    password	= has�o_podane_przy_tworzeniu_u�ytkownika_lms
    
Uwaga: Has�o jest wymagane w zale�no�ci od konfiguracji autentykacji 
       u�ytkownik�w postgresa w /usr/local/pgsql/data/pg_hba.conf. 
       Domy�lnie has�o nie jest wymagane.

Po takim zabiegu, o ile LMS'owi uda si� nawi�za� po��czenie do bazy danych,
mo�na ju� bez problemu dosta� si� do systemu. Je�eli jednak w bazie danych
nie ma �adnego konta administratora, jedyn� rzecz� jak� b�dziemy widzie�
b�dzie formularz dodania administratora. Je�eli podamy prawid�owe dane
administratora, LMS przeniesie nas na stron� logowania gdzie odrazu b�dziemy
mogli u�y� nowo utworzonego konta.

------------------------------------------------------------------------
$Id$
------------------------------------------------------------------------
