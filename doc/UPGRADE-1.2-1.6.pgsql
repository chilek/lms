/*
*************************************************************
******* Upgrade bazy danych LMS z wersji 1.2 do 1.6 *********
*************************************************************
Zaleca siê wcze¶niejszy backup bazy:
	$ pg_dump lms > db.out
Sposób u¿ycia:
	$ psql -U lms -d lms -f UPGRADE-1.2-1.6.pgsql
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

/* Chyba o niczym nie zapomnia³em? */
COMMIT;

/*
$Id$
*/
