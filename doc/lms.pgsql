/* $Id$ */ 

/* -------------------------------------------------------- 
  Struktura tabeli "admins" 
-------------------------------------------------------- */
DROP SEQUENCE "admins_id_seq";
CREATE SEQUENCE "admins_id_seq";
DROP TABLE admins;
CREATE TABLE admins (
	id integer DEFAULT nextval('admins_id_seq'::text) NOT NULL,
	login varchar(32) 	DEFAULT '' NOT NULL,
	name varchar(64) 	DEFAULT '' NOT NULL,
	email varchar(255) 	DEFAULT '' NOT NULL,
	rights varchar(64) 	DEFAULT '' NOT NULL,
	passwd varchar(255) 	DEFAULT '' NOT NULL,
	lastlogindate integer 	DEFAULT 0  NOT NULL,
	lastloginip varchar(16) DEFAULT '' NOT NULL,
	failedlogindate integer DEFAULT 0  NOT NULL,
	failedloginip varchar(16) DEFAULT '' NOT NULL,
	deleted smallint	DEFAULT 0 NOT NULL,
	PRIMARY KEY (id),
	UNIQUE (login)
);

/* ----------------------------------------------------
	 Struktura tabeli assignments
---------------------------------------------------*/
DROP SEQUENCE "assignments_id_seq";
CREATE SEQUENCE "assignments_id_seq";
DROP TABLE assignments;
CREATE TABLE assignments (
	id integer default nextval('assignments_id_seq'::text) NOT NULL,
	tariffid integer 	DEFAULT 0 NOT NULL,
	userid integer 		DEFAULT 0 NOT NULL,
	period integer 		DEFAULT 0 NOT NULL,
	at integer 		DEFAULT 0 NOT NULL,
	datefrom integer	DEFAULT 0 NOT NULL,
	dateto integer		DEFAULT 0 NOT NULL,
	invoice smallint 	DEFAULT 0 NOT NULL,
	PRIMARY KEY (id)
);

/* -------------------------------------------------------- 
  Struktura tabeli "cash" 
-------------------------------------------------------- */
DROP SEQUENCE "cash_id_seq";
CREATE SEQUENCE "cash_id_seq";
DROP TABLE cash;
CREATE TABLE cash (
	id integer 		DEFAULT nextval('cash_id_seq'::text) NOT NULL,
	time integer 		DEFAULT 0 NOT NULL,
	adminid integer 	DEFAULT 0 NOT NULL,
	type smallint 		DEFAULT 0 NOT NULL,
	value numeric(9,2) 	DEFAULT 0 NOT NULL,
	taxvalue numeric(9,2)	DEFAULT 0,
	userid integer 		DEFAULT 0 NOT NULL,
	comment varchar(255) 	DEFAULT '' NOT NULL,
	invoiceid integer 	DEFAULT 0 NOT NULL,
	PRIMARY KEY (id)
);

/* -------------------------------------------------------- 
  Struktura tabeli "networks" 
-------------------------------------------------------- */
DROP SEQUENCE "networks_id_seq";
CREATE SEQUENCE "networks_id_seq";
DROP TABLE networks;
CREATE TABLE networks (
	id integer DEFAULT nextval('networks_id_seq'::text) NOT NULL,
	name varchar(255) 	DEFAULT '' NOT NULL,
	address bigint 		DEFAULT 0 NOT NULL,
	mask varchar(16) 	DEFAULT '' NOT NULL,
	interface varchar(8) 	DEFAULT '' NOT NULL, 
	gateway varchar(16) 	DEFAULT '' NOT NULL,
	dns varchar(16) 	DEFAULT '' NOT NULL,
	dns2 varchar(16) 	DEFAULT '' NOT NULL,
	domain varchar(64) 	DEFAULT '' NOT NULL,
	wins varchar(16) 	DEFAULT '' NOT NULL,
	dhcpstart varchar(16) 	DEFAULT '' NOT NULL,
	dhcpend varchar(16) 	DEFAULT '' NOT NULL,
	PRIMARY KEY (id),
	UNIQUE (name),
	UNIQUE (address)
);

/* -------------------------------------------------------- 
  Struktura tabeli "nodes" 
-------------------------------------------------------- */
DROP SEQUENCE "nodes_id_seq";
CREATE SEQUENCE "nodes_id_seq";
DROP TABLE nodes;
CREATE TABLE nodes (
	id integer DEFAULT nextval('nodes_id_seq'::text) NOT NULL,
	name varchar(16) 	DEFAULT '' NOT NULL,
	mac varchar(20) 	DEFAULT '' NOT NULL,
	ipaddr bigint 		DEFAULT 0 NOT NULL,
	ownerid integer 	DEFAULT 0 NOT NULL,
	netdev integer 		DEFAULT 0 NOT NULL,
	creationdate integer 	DEFAULT 0 NOT NULL,
	moddate integer 	DEFAULT 0 NOT NULL,
	creatorid integer 	DEFAULT 0 NOT NULL,
	modid integer 		DEFAULT 0 NOT NULL,
	access smallint 	DEFAULT 1 NOT NULL,
	warning smallint 	DEFAULT 0 NOT NULL,
	PRIMARY KEY (id),
	UNIQUE (name),
	UNIQUE (ipaddr)
);

/* -------------------------------------------------------- 
  Struktura tabeli "tariffs" 
-------------------------------------------------------- */
DROP SEQUENCE "tariffs_id_seq";
CREATE SEQUENCE "tariffs_id_seq"; 
DROP TABLE tariffs;
CREATE TABLE tariffs (
	id integer DEFAULT nextval('tariffs_id_seq'::text) NOT NULL,
	name varchar(255) 	DEFAULT '' NOT NULL,
	value numeric(9,2) 	DEFAULT 0 NOT NULL,
	taxvalue numeric(9,2) 	DEFAULT 0,
	pkwiu varchar(255) 	DEFAULT '' NOT NULL,
	uprate integer		DEFAULT 0 NOT NULL,
	upceil integer		DEFAULT 0 NOT NULL,
	downrate integer	DEFAULT 0 NOT NULL,
	downceil integer	DEFAULT 0 NOT NULL,
	climit integer		DEFAULT 0 NOT NULL,
	plimit integer		DEFAULT 0 NOT NULL,
	description text	DEFAULT '' NOT NULL,
	PRIMARY KEY (id),
	UNIQUE (name)
);

/* ---------------------------------------------------------
  Struktura tabeli "payments"
--------------------------------------------------------- */
DROP SEQUENCE "payments_id_seq";
CREATE SEQUENCE "payments_id_seq";
DROP TABLE payments;
CREATE TABLE payments (
	id integer DEFAULT nextval('payments_id_seq'::text) NOT NULL,
	name varchar(255) 	DEFAULT '' NOT NULL,
	value numeric(9,2) 	DEFAULT 0 NOT NULL,
	creditor varchar(255) 	DEFAULT '' NOT NULL,
	period integer 		DEFAULT 0 NOT NULL,
	at integer 		DEFAULT 0 NOT NULL,
	description text	DEFAULT '' NOT NULL,
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
        number integer 		DEFAULT 0 NOT NULL,
        cdate integer 		DEFAULT 0 NOT NULL,
        paytime smallint 	DEFAULT 0 NOT NULL,
	paytype varchar(255) 	DEFAULT '' NOT NULL,
        customerid integer 	DEFAULT 0 NOT NULL,
        name varchar(255) 	DEFAULT '' NOT NULL,
        address varchar(255) 	DEFAULT '' NOT NULL,
        nip varchar(16) 	DEFAULT '' NOT NULL,
	pesel varchar(11) 	DEFAULT '' NOT NULL,
        zip varchar(6) 		DEFAULT '' NOT NULL,
        city varchar(32) 	DEFAULT '' NOT NULL,
        phone varchar(255) 	DEFAULT '' NOT NULL,
        finished smallint 	DEFAULT 0 NOT NULL,
	PRIMARY KEY (id)
);

/* -------------------------------------------------------- 
  Struktura tabeli "invoicecontents" 
-------------------------------------------------------- */
CREATE TABLE invoicecontents (
	invoiceid integer 	DEFAULT 0 NOT NULL,
	value numeric(9,2) 	DEFAULT 0 NOT NULL,
	taxvalue numeric(9,2) 	DEFAULT 0,
	pkwiu varchar(255) 	DEFAULT '' NOT NULL,
	content varchar(16) 	DEFAULT '' NOT NULL,
	count numeric(9,2) 	DEFAULT 0 NOT NULL,
	description varchar(255) DEFAULT '' NOT NULL,
	tariffid integer 	DEFAULT 0 NOT NULL
);	 

/* -------------------------------------------------------- 
  Struktura tabeli "timestamps" 
-------------------------------------------------------- */
DROP TABLE timestamps;
CREATE TABLE timestamps (
	time integer 		DEFAULT 0  NOT NULL,
	tablename varchar(255) 	DEFAULT '' NOT NULL,
	UNIQUE (tablename)
);

/* -------------------------------------------------------- 
  Struktura tabeli "users" 
-------------------------------------------------------- */
DROP SEQUENCE "users_id_seq";
CREATE SEQUENCE "users_id_seq";
DROP TABLE users;
CREATE TABLE users (
	id integer DEFAULT nextval('users_id_seq'::text) NOT NULL,
	lastname varchar(255)	DEFAULT '' NOT NULL,
	name varchar(255)	DEFAULT '' NOT NULL,
	status smallint 	DEFAULT 0 NOT NULL,
	email varchar(255) 	DEFAULT '' NOT NULL,
	phone1 varchar(255) 	DEFAULT '' NOT NULL,
	phone2 varchar(255) 	DEFAULT '' NOT NULL,
	phone3 varchar(255) 	DEFAULT '' NOT NULL,
	gguin integer 		DEFAULT 0 NOT NULL,
	address varchar(255) 	DEFAULT '' NOT NULL,
	zip varchar(6) 		DEFAULT '' NOT NULL,
	city varchar(32) 	DEFAULT '' NOT NULL,
	nip varchar(16) 	DEFAULT '' NOT NULL,
	pesel varchar(11) 	DEFAULT '' NOT NULL,
	info text		DEFAULT '' NOT NULL,
	creationdate integer 	DEFAULT 0 NOT NULL,
	moddate integer 	DEFAULT 0 NOT NULL,
	creatorid integer 	DEFAULT 0 NOT NULL,
	modid integer 		DEFAULT 0 NOT NULL,
	deleted smallint 	DEFAULT 0 NOT NULL,
	message text		DEFAULT '' NOT NULL,
	PRIMARY KEY (id)	
);

/* -------------------------------------------------------- 
  Struktura tabeli "usergroups" 
-------------------------------------------------------- */
DROP SEQUENCE "usergroups_id_seq";
CREATE SEQUENCE "usergroups_id_seq";
DROP TABLE usergroups;
CREATE TABLE usergroups (
	id integer DEFAULT nextval('usergroups_id_seq'::text) NOT NULL, 
	name varchar(255) DEFAULT '' NOT NULL, 
	description text DEFAULT '' NOT NULL, 
	PRIMARY KEY (id), 
	UNIQUE (name)
);


/* -------------------------------------------------------- 
  Struktura tabeli "userassignments" 
-------------------------------------------------------- */
DROP SEQUENCE "userassignments_id_seq";
CREATE SEQUENCE "userassignments_id_seq";
DROP TABLE userassignments;
CREATE TABLE userassignments (
	id integer DEFAULT nextval('userassignments_id_seq'::text) NOT NULL, 
	usergroupid integer DEFAULT 0 NOT NULL, 
	userid integer DEFAULT 0 NOT NULL, 
	PRIMARY KEY (id),
	UNIQUE (usergroupid, userid)
);

/* -------------------------------------------------------- 
  Struktura tabeli "stats" 
-------------------------------------------------------- */
DROP TABLE stats;
CREATE TABLE stats (
	nodeid integer 		DEFAULT 0 NOT NULL,
	dt integer 		DEFAULT 0 NOT NULL,
	upload integer 		DEFAULT 0,
	download integer 	DEFAULT 0,
	PRIMARY KEY (nodeid, dt)
);
/* Ma�y dopalacz niekt�rych zapyta� */
CREATE INDEX stats_nodeid_idx ON stats(nodeid);


/* ---------------------------------------------------
	Struktura tabeli netdevices
----------------------------------------------------*/
DROP SEQUENCE "netdevices_id_seq";
CREATE SEQUENCE "netdevices_id_seq";
DROP TABLE netdevices;
CREATE TABLE netdevices (
	id integer default nextval('netdevices_id_seq'::text) NOT NULL,
	name varchar(32) 	DEFAULT '' NOT NULL,
	location varchar(255) 	DEFAULT '' NOT NULL,
	description varchar(255) DEFAULT '' NOT NULL,
	producer varchar(64) 	DEFAULT '' NOT NULL,
	model varchar(32) 	DEFAULT '' NOT NULL,
	serialnumber varchar(32) DEFAULT '' NOT NULL,
	ports integer 		DEFAULT 0 NOT NULL,
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
	src integer 		DEFAULT 0 NOT NULL,
	dst integer 		DEFAULT 0 NOT NULL,
	PRIMARY KEY  (id),
	UNIQUE (src, dst)
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

/* --------------------------------------------------
    Tables for RT
-----------------------------------------------------*/
DROP TABLE rtattachments;  
CREATE TABLE rtattachments (
	messageid integer 	DEFAULT 0 NOT NULL, 
	filename varchar(255) 	DEFAULT '' NOT NULL, 
	contenttype varchar(255) DEFAULT '' NOT NULL
);

DROP SEQUENCE "rtqueues_id_seq";
CREATE SEQUENCE "rtqueues_id_seq";
DROP TABLE rtqueues;
CREATE TABLE rtqueues (
  id integer default nextval('rtqueues_id_seq'::text) NOT NULL,
  name varchar(255) 	DEFAULT '' NOT NULL,
  email varchar(255) 	DEFAULT '' NOT NULL,
  description text	DEFAULT '' NOT NULL,
  PRIMARY KEY (id),
  UNIQUE (name),
);

DROP SEQUENCE "rttickets_id_seq";
CREATE SEQUENCE "rttickets_id_seq";
DROP TABLE rttickets;
CREATE TABLE rttickets (
  id integer default nextval('rttickets_id_seq'::text) NOT NULL,  
  queueid integer 	DEFAULT 0 NOT NULL,
  requestor varchar(255) DEFAULT '' NOT NULL,
  subject varchar(255) 	DEFAULT '' NOT NULL,
  state smallint 	DEFAULT 0 NOT NULL,
  owner integer 	DEFAULT 0 NOT NULL,
  userid integer 	DEFAULT 0 NOT NULL,
  createtime integer 	DEFAULT 0 NOT NULL,
  PRIMARY KEY (id)
);

DROP SEQUENCE "rtmessages_id_seq";
CREATE SEQUENCE "rtmessages_id_seq";
DROP TABLE rtmessages;
CREATE TABLE rtmessages (
  id integer default nextval('rtmessages_id_seq'::text) NOT NULL,
  ticketid integer 	DEFAULT 0 NOT NULL,
  adminid integer 	DEFAULT 0 NOT NULL,
  userid integer 	DEFAULT 0 NOT NULL,
  mailfrom varchar(255) DEFAULT '' NOT NULL,
  subject varchar(255) 	DEFAULT '' NOT NULL,
  messageid varchar(255) DEFAULT '' NOT NULL,
  inreplyto integer 	DEFAULT 0 NOT NULL,
  replyto text 		DEFAULT '' NOT NULL,
  headers text 		DEFAULT '' NOT NULL,
  body text 		DEFAULT '' NOT NULL,
  createtime integer	DEFAULT 0 NOT NULL,
  PRIMARY KEY (id)
);

DROP SEQUENCE "rtrights_id_seq";
CREATE SEQUENCE "rtrights_id_seq";
DROP TABLE rtrights;
CREATE TABLE rtrights (
    id integer DEFAULT nextval('rtrights_id_seq'::text) NOT NULL, 
    adminid integer DEFAULT 0 NOT NULL,
    queueid integer DEFAULT 0 NOT NULL,
    rights integer DEFAULT 0 NOT NULL,
    PRIMARY KEY (id),
    UNIQUE (adminid, queueid)
);

/* ---------------------------------------------------
    Database info table
------------------------------------------------------*/

CREATE TABLE dbinfo (
    keytype varchar(255) DEFAULT '' NOT NULL,
    keyvalue varchar(255) DEFAULT '' NOT NULL,
    PRIMARY KEY (keytype)
);

INSERT INTO dbinfo (keytype, keyvalue) VALUES ('dbversion','2004071200');    
