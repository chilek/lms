/*
*************************************************************
******* Upgrade bazy danych LMS z wersji 1.2 do 1.5 *********
*************************************************************
Zaleca si� wcze�niejszy backup bazy:
	$ pg_dump lms > db.out
Spos�b u�ycia:
	$ psql -U lms -d lms -f UPGRADE-1.2-1.5.pgsql
*************************************************************
*/

BEGIN;

/* Informacje o bazie */
CREATE TABLE dbinfo (
    keytype VARCHAR(255) DEFAULT '' NOT NULL,
    keyvalue VARCHAR(255) DEFAULT '' NOT NULL,
    PRIMARY KEY (keytype)
);
INSERT INTO dbinfo (keytype, keyvalue) VALUES ('dbversion','2004030400');		  

/* Chyba o niczym nie zapomnia�em? */
COMMIT;

/*
$Id$
*/
