/* $Id$ */ 

/* -------------------------------------------------------- 
  Struktura tabeli "admins" 
-------------------------------------------------------- */
DROP SEQUENCE "admins_id_seq";
CREATE SEQUENCE "admins_id_seq";
DROP TABLE admins;
CREATE TABLE admins (
	id integer DEFAULT nextval('admins_id_seq'::text) NOT NULL,
	login varchar(32) DEFAULT '' NOT NULL,
	name varchar(64) DEFAULT '' NOT NULL,
	email varchar(255) DEFAULT '' NOT NULL,
	rights varchar(64) DEFAULT '' NOT NULL,
	passwd varchar(255) DEFAULT '' NOT NULL,
	lastlogindate integer DEFAULT 0 NOT NULL,
	lastloginip varchar(16),
	failedlogindate integer DEFAULT 0 NOT NULL,
	failedloginip varchar(16),
	PRIMARY KEY (id)
);

/* -------------------------------------------------------- 
  Struktura tabeli "cash" 
-------------------------------------------------------- */
DROP SEQUENCE "cash_id_seq";
CREATE SEQUENCE "cash_id_seq";
DROP TABLE cash;
CREATE TABLE cash (
	id integer DEFAULT nextval('cash_id_seq'::text) NOT NULL,
	time integer DEFAULT 0 NOT NULL,
	adminid integer DEFAULT 0 NOT NULL,
	type smallint DEFAULT 0 NOT NULL,
	value numeric(9,2) DEFAULT 0 NOT NULL,
	userid integer DEFAULT 0 NOT NULL,
	comment varchar(255) DEFAULT '' NOT NULL,
	invoiceid integer DEFAULT 0 NOT NULL,
	PRIMARY KEY (id)
);

/* -------------------------------------------------------- 
  Struktura tabeli "networks" 
-------------------------------------------------------- */
DROP SEQUENCE "networks_id_seq";
CREATE SEQUENCE "networks_id_seq";
DROP TABLE networks;
CREATE TABLE networks (
	id int4 DEFAULT nextval('networks_id_seq'::text) NOT NULL,
	name varchar(255) NOT NULL,
	address bigint NOT NULL,
	mask varchar(16) NOT NULL,
	interface varchar(8),
	gateway varchar(16),
	dns varchar(16),
	dns2 varchar(16),
	domain varchar(64),
	wins varchar(16),
	dhcpstart varchar(16),
	dhcpend varchar(16),
	PRIMARY KEY (id)
);

/* -------------------------------------------------------- 
  Struktura tabeli "nodes" 
-------------------------------------------------------- */
DROP SEQUENCE "nodes_id_seq";
CREATE SEQUENCE "nodes_id_seq";
DROP TABLE nodes;
CREATE TABLE nodes (
	id integer DEFAULT nextval('nodes_id_seq'::text) NOT NULL,
	name varchar(16) NOT NULL,
	mac varchar(20) NOT NULL,
	ipaddr bigint NOT NULL,
	ownerid integer DEFAULT 0 NOT NULL,
	netdev integer DEFAULT 0 NOT NULL,
	creationdate int4 NOT NULL,
	moddate integer DEFAULT 0 NOT NULL,
	creatorid integer NOT NULL,
	modid integer DEFAULT 0 NOT NULL,
	access smallint DEFAULT 1 NOT NULL,
	PRIMARY KEY (id)
);

/* -------------------------------------------------------- 
  Struktura tabeli "tariffs" 
-------------------------------------------------------- */
DROP SEQUENCE "tariffs_id_seq";
CREATE SEQUENCE "tariffs_id_seq"; 
DROP TABLE tariffs;
CREATE TABLE tariffs (
	id integer DEFAULT nextval('tariffs_id_seq'::text) NOT NULL,
	name varchar(255) NOT NULL,
	value numeric(9,2) DEFAULT 0 NOT NULL,
	taxvalue numeric(9,2) DEFAULT 0 NOT NULL,
	pkwiu varchar(255) DEFAULT NULL,
	uprate integer,
	downrate integer,
	description text,
	PRIMARY KEY (id)
);

/* ---------------------------------------------------------
  Struktura tabeli "payments"
--------------------------------------------------------- */
DROP SEQUENCE "payments_id_seq";
CREATE SEQUENCE "payments_id_seq";
DROP TABLE payments;
CREATE TABLE payments (
	id integer DEFAULT nextval('payments_id_seq'::text) NOT NULL,
	name varchar(255) DEFAULT '' NOT NULL,
	value numeric(9,2) DEFAULT 0 NOT NULL,
	creditor varchar(255) DEFAULT '' NOT NULL,
	period integer DEFAULT 0 NOT NULL,
	at integer DEFAULT 0 NOT NULL,
	description text,
	PRIMARY KEY (id)
);

/* -------------------------------------------------------- 
  Struktura tabeli "invoices" 
-------------------------------------------------------- */
DROP SEQUENCE "invoices_id_seq";
CREATE SEQUENCE "invoices_id_seq";
DROP TABLE invoices;
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
	PRIMARY KEY (id)
);

/* -------------------------------------------------------- 
  Struktura tabeli "invoicecontents" 
-------------------------------------------------------- */
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

/* -------------------------------------------------------- 
  Struktura tabeli "timestamps" 
-------------------------------------------------------- */
DROP TABLE timestamps;
CREATE TABLE timestamps (
	time integer DEFAULT 0 NOT NULL,
	tablename varchar(255) DEFAULT '' NOT NULL
);

/* -------------------------------------------------------- 
  Struktura tabeli "users" 
-------------------------------------------------------- */
DROP SEQUENCE "users_id_seq";
CREATE SEQUENCE "users_id_seq";
DROP TABLE users;
CREATE TABLE users (
	id integer DEFAULT nextval('users_id_seq'::text) NOT NULL,
	lastname varchar(255),
	name varchar(255),
	status smallint DEFAULT NULL,
	email varchar(255) DEFAULT NULL,
	phone1 varchar(255) DEFAULT NULL,
	phone2 varchar(255) DEFAULT NULL,
	phone3 varchar(255) DEFAULT NULL,
	gguin integer DEFAULT 0 NOT NULL,
	address varchar(255) DEFAULT '' NOT NULL,
	zip varchar(6) DEFAULT NULL,
	city varchar(32) DEFAULT NULL,
	nip varchar(16) DEFAULT NULL,
	pesel varchar(11) DEFAULT NULL,
	info text,
	creationdate integer DEFAULT 0 NOT NULL,
	moddate integer DEFAULT 0 NOT NULL,
	creatorid integer DEFAULT 0 NOT NULL,
	modid integer DEFAULT 0 NOT NULL,
	deleted smallint DEFAULT 0 NOT NULL,
	PRIMARY KEY (id)	
);

/* -------------------------------------------------------- 
  Struktura tabeli "stats" 
-------------------------------------------------------- */
DROP TABLE stats;
CREATE TABLE stats (
	nodeid integer DEFAULT 0 NOT NULL,
	dt integer DEFAULT 0 NOT NULL,
	upload integer DEFAULT 0,
	download integer DEFAULT 0,
	PRIMARY KEY (nodeid, dt)
);
/* Ma³y dopalacz niektórych zapytañ */
CREATE INDEX stats_nodeid_idx ON stats(nodeid);

/* ----------------------------------------------------
	 Struktura tabeli assignments
---------------------------------------------------*/
DROP SEQUENCE "assignments_id_seq";
CREATE SEQUENCE "assignments_id_seq";
DROP TABLE assignments;
CREATE TABLE assignments (
	id integer default nextval('assignments_id_seq'::text) NOT NULL,
	tariffid integer default 0 NOT NULL,
	userid integer default 0 NOT NULL,
	period integer default 0 NOT NULL,
	at integer default 0 NOT NULL,
	invoice smallint default 0 NOT NULL,
	PRIMARY KEY (id)
);

/* ---------------------------------------------------
	Struktura tabeli netdevices
----------------------------------------------------*/
DROP SEQUENCE "netdevices_id_seq";
CREATE SEQUENCE "netdevices_id_seq";
DROP TABLE netdevices;
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

/* ---------------------------------------------------
	Struktura tabeli netlinks
----------------------------------------------------*/
DROP SEQUENCE "netlinks_id_seq";
CREATE SEQUENCE "netlinks_id_seq";
DROP TABLE netlinks;
CREATE TABLE netlinks (
   id integer default nextval('netlinks_id_seq'::text) NOT NULL,
   src integer default 0 NOT NULL,
   dst integer default 0 NOT NULL,
   PRIMARY KEY  (id)
);

/* ---------------------------------------------------
    Functions for network address translations
------------------------------------------------------*/
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
