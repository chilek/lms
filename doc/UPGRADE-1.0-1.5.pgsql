/*
*************************************************************
******* Upgrade bazy danych LMS z wersji 1.0 do 1.5 *********
*************************************************************
Zaleca siê wcze¶niejszy backup bazy:
	$ pg_dump lms > db.out
Sposób u¿ycia:
	$ psql -U lms -d lms -f UPGRADE-1.0-1.5.pgsql
Po tej operacji uruchom LMS i wystartuj modu³ '?m=upgrade01'
Dopiero na koñcu mo¿na usun±æ niepotrzebne rzeczy:
	$ psql -U lms -d lms -c ALTER TABLE users DROP tariff; 
	$ psql -U lms -d lms -c ALTER TABLE users DROP payday;
*************************************************************
*/

BEGIN;

/* Te tabele ju¿ nie s± u¿ywane */
DROP TABLE options;
DROP TABLE tokens;

/* Kosmetyka u adminów */
UPDATE admins SET lastlogindate=0 WHERE lastlogindate IS NULL;
UPDATE admins SET failedlogindate=0 WHERE failedlogindate IS NULL;	 
ALTER TABLE admins ALTER COLUMN lastlogindate SET DEFAULT 0;
ALTER TABLE admins ALTER COLUMN lastlogindate SET NOT NULL;
ALTER TABLE admins ALTER COLUMN failedlogindate SET DEFAULT 0;
ALTER TABLE admins ALTER COLUMN failedlogindate SET NOT NULL;
	
/* Teraz u¿ytkownicy nie s± usuwani z bazy */
ALTER TABLE users ADD deleted int2;
UPDATE users SET deleted=0;
ALTER TABLE users ALTER COLUMN deleted SET DEFAULT 0;
ALTER TABLE users ALTER COLUMN deleted SET NOT NULL;
UPDATE users SET gguin=0 WHERE gguin IS NULL;
ALTER TABLE users ALTER COLUMN gguin SET DEFAULT 0;
ALTER TABLE users ALTER COLUMN gguin SET NOT NULL;

/* Dzieñ zap³aty */
ALTER TABLE users ADD payday integer;
UPDATE users SET payday = 1;
ALTER TABLE users ALTER COLUMN payday SET DEFAULT 1;
ALTER TABLE users ALTER COLUMN payday SET NOT NULL;

/* pesel */
ALTER TABLE users ADD pesel varchar(11);
ALTER TABLE users ALTER COLUMN pesel SET DEFAULT NULL;

/* Nowe kolumny w tabeli */
ALTER TABLE networks ADD dns2 VARCHAR(16);
ALTER TABLE networks ADD interface VARCHAR(8);

/* Konwersja adresów internetowych int <-> text */
CREATE OR REPLACE FUNCTION inet_ntoa(bigint) RETURNS text AS '
SELECT 
     ($1/(256*256*256))::text
     ||''.''||
     ($1/(256*256) - $1/(256*256*256)*256)::text
     ||''.''||
     ($1/256 - $1/(256*256)*256)::text
     ||''.''||
     ($1 - $1/256*256)::text;
' LANGUAGE SQL;

CREATE OR REPLACE FUNCTION inet_aton(text) RETURNS bigint AS '
SELECT
     split_part($1,''.'',1)::int8*(256*256*256)+
     split_part($1,''.'',2)::int8*(256*256)+
     split_part($1,''.'',3)::int8*256+
     split_part($1,''.'',4)::int8;
' LANGUAGE SQL;

ALTER TABLE networks ADD ipaddr bigint;
UPDATE networks SET ipaddr = inet_aton(address); 
ALTER TABLE networks DROP COLUMN address;
ALTER TABLE networks RENAME COLUMN ipaddr to address;
ALTER TABLE networks ALTER COLUMN address set NOT NULL;
ALTER TABLE nodes ADD ipaddr2 bigint;
UPDATE nodes SET ipaddr2 = inet_aton(ipaddr);
ALTER TABLE nodes DROP COLUMN ipaddr;
ALTER TABLE nodes RENAME COLUMN ipaddr2 to ipaddr;
ALTER TABLE nodes ALTER COLUMN ipaddr set NOT NULL;

/* Zmiana formatu zapisu czy komputer jest dostêpny */
UPDATE nodes SET access= CASE access WHEN 'Y' THEN '1' ELSE '0' END;
ALTER TABLE nodes ADD access2 int2;
UPDATE nodes SET access2 = access::text::int2;
ALTER TABLE nodes DROP COLUMN access;
ALTER TABLE nodes RENAME COLUMN access2 TO access;
ALTER TABLE nodes ALTER COLUMN access SET NOT NULL;
ALTER TABLE nodes ALTER COLUMN access SET DEFAULT 0;
/* Dodatkowe poprawki w bazie */
ALTER TABLE nodes ALTER COLUMN ownerid SET DEFAULT '0';
ALTER TABLE nodes ALTER COLUMN mac SET NOT NULL;
/* Linki do urz±dzeñ sieciowych*/
ALTER TABLE nodes ADD netdev integer;
ALTER TABLE nodes ALTER COLUMN netdev SET default 0;
UPDATE nodes SET netdev = 0;
ALTER TABLE nodes ALTER COLUMN netdev SET NOT NULL;  	

/* Zmiana zapisu limitu */
ALTER TABLE tariffs ALTER uprate DROP NOT NULL;
ALTER TABLE tariffs ALTER uprate DROP DEFAULT;
ALTER TABLE tariffs ALTER downrate DROP NOT NULL;
ALTER TABLE tariffs ALTER downrate DROP DEFAULT; 
ALTER TABLE tariffs ALTER description DROP NOT NULL;

/* Zmiana typów pul numerycznych */
ALTER TABLE cash ADD val numeric(9,2);
UPDATE cash SET val=value;
ALTER TABLE cash ALTER val SET NOT NULL;
ALTER TABLE cash ALTER val SET DEFAULT 0;
ALTER TABLE cash DROP COLUMN value;
ALTER TABLE cash RENAME val TO value;
ALTER TABLE tariffs ADD val numeric(9,2);
UPDATE tariffs SET val=value;
ALTER TABLE tariffs ALTER val SET NOT NULL;
ALTER TABLE tariffs ALTER val SET DEFAULT 0;
ALTER TABLE tariffs DROP COLUMN value;
ALTER TABLE tariffs RENAME val TO value;

/* Faktury inaczej */
ALTER TABLE cash ADD COLUMN invoiceid integer;
ALTER TABLE cash ALTER COLUMN invoiceid SET default 0;
UPDATE cash SET invoiceid = 0;
ALTER TABLE cash ALTER COLUMN invoiceid SET NOT NULL; 
ALTER TABLE tariffs ADD taxvalue decimal(9,2);
UPDATE tariffs SET taxvalue = 0;
ALTER TABLE tariffs ALTER COLUMN taxvalue SET DEFAULT 0;
ALTER TABLE tariffs ALTER COLUMN taxvalue SET NOT NULL;
ALTER TABLE tariffs ADD	pkwiu varchar(255);
ALTER TABLE tariffs ALTER COLUMN pkwiu SET DEFAULT NULL;

CREATE SEQUENCE "invoices_id_seq";
CREATE TABLE invoices (
	id integer DEFAULT nextval('invoices_id_seq'::text) NOT NULL,
        number integer NOT NULL,
        cdate integer NOT NULL,
        paytime smallint NOT NULL,
	paytype varchar(255) DEFAULT '' NOT NULL,
        customerid integer NOT NULL,
        name varchar(255) NOT NULL,
        address varchar(255) NOT NULL,
        nip varchar(16) DEFAULT NULL,
	pesel varchar(11) DEFAULT NULL,
        zip varchar(6) NOT NULL,
        city varchar(32) NOT NULL,
        phone varchar(255) NOT NULL,
        finished smallint DEFAULT 0 NOT NULL,
	PRIMARY KEY(id)
);

CREATE TABLE invoicecontents (
	invoiceid integer NOT NULL,
	value numeric(9,2) NOT NULL,
	taxvalue numeric(9,2) NOT NULL,
	pkwiu varchar(255) DEFAULT NULL,
	content varchar(16) NOT NULL,
	count numeric(9,2) NOT NULL,
	description varchar(255) NOT NULL,
	tariffid integer NOT NULL
);	 

/* Nowa tabela dla statystyk */
CREATE TABLE stats (
    nodeid integer DEFAULT 0 NOT NULL,
    dt integer DEFAULT 0 NOT NULL,
    upload integer DEFAULT 0,
    download integer DEFAULT 0,
    PRIMARY KEY (nodeid, dt)
);

/* Nowa tabela - urz±dzenia sieciowe */
CREATE SEQUENCE "netdevices_id_seq";
CREATE TABLE netdevices (
   id integer default nextval('netdevices_id_seq'::text) NOT NULL,
   name varchar(32) default NULL,
   location varchar(255),
   description varchar(255) default NULL,
   producer varchar(64) default NULL,
   model varchar(32) default NULL,
   serialnumber varchar(32) default NULL,
   ports integer default NULL,
   PRIMARY KEY (id)
);

/* Nowa tabela - po³aczenia sieciowe */
CREATE SEQUENCE "netlinks_id_seq";
CREATE TABLE netlinks (
   id integer default nextval('netlinks_id_seq'::text) NOT NULL,
   src integer default 0 NOT NULL,
   dst integer default 0 NOT NULL,
   PRIMARY KEY  (id)
);

/* Nowa tabela - op³aty sta³e */
CREATE SEQUENCE "payments_id_seq";
CREATE TABLE payments (
	id integer DEFAULT nextval('payments_id_seq'::text) NOT NULL,
	name VARCHAR(255) DEFAULT '' NOT NULL,
	value NUMERIC(9,2) DEFAULT 0 NOT NULL,
	creditor VARCHAR(255) DEFAULT '' NOT NULL,
	period integer DEFAULT 0 NOT NULL,
	at integer DEFAULT 0 NOT NULL,
	description text,
	PRIMARY KEY (id)
);

/* Na koniec rewolucja w finansach */
CREATE SEQUENCE "assignments_id_seq";
CREATE TABLE assignments (
   id integer default nextval('assignments_id_seq'::text) NOT NULL,
   tariffid integer default 0 NOT NULL,
   userid integer default 0 NOT NULL,
   period integer default 0 NOT NULL,
   at integer default 0 NOT NULL,
   invoice smallint default 0 NOT NULL,
   PRIMARY KEY (id)
);

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
