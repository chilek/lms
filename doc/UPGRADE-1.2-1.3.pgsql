/*
*************************************************************
******* Upgrade bazy danych LMS z wersji 1.0 do 1.3 *********
*************************************************************
Zaleca siê wcze¶niejszy backup bazy:
	$ pg_dump lms > db.out
Sposób u¿ycia:
	$ psql -U lms -d lms -f UPGRADE.pgsql
Po tej operacji uruchom LMS i wystartuj modu³ '?m=upgrade01'
Dopiero na koñcu mo¿na usun±æ niepotrzebne rzeczy:
	$ psql -U lms -d lms -c ALTER TABLE users DROP tariff; 
	$ psql -U lms -d lms -c ALTER TABLE users DROP payday;
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
