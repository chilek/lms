/* $Id$ */ 

/* -------------------------------------------------------- 
  Structure of table "users" 
-------------------------------------------------------- */
DROP SEQUENCE "users_id_seq";
CREATE SEQUENCE "users_id_seq";
DROP TABLE users;
CREATE TABLE users (
	id integer DEFAULT nextval('users_id_seq'::text) NOT NULL,
	login varchar(32) 	DEFAULT '' NOT NULL,
	name varchar(64) 	DEFAULT '' NOT NULL,
	email varchar(255) 	DEFAULT '' NOT NULL,
	rights varchar(64) 	DEFAULT '' NOT NULL,
	hosts varchar(255) 	DEFAULT '' NOT NULL,
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
 Structure of table "assignments"
---------------------------------------------------*/
DROP SEQUENCE "assignments_id_seq";
CREATE SEQUENCE "assignments_id_seq";
DROP TABLE assignments;
CREATE TABLE assignments (
	id integer default nextval('assignments_id_seq'::text) NOT NULL,
	tariffid integer 	DEFAULT 0 NOT NULL,
	customerid integer 		DEFAULT 0 NOT NULL,
	period integer 		DEFAULT 0 NOT NULL,
	at integer 		DEFAULT 0 NOT NULL,
	datefrom integer	DEFAULT 0 NOT NULL,
	dateto integer		DEFAULT 0 NOT NULL,
	invoice smallint 	DEFAULT 0 NOT NULL,
	suspended smallint	DEFAULT 0 NOT NULL,
	discount numeric(4,2)	DEFAULT 0 NOT NULL,
	PRIMARY KEY (id)
);
CREATE INDEX assignments_tariffid_idx ON assignments (tariffid);

/* -------------------------------------------------------- 
  Structure of table "cash" 
-------------------------------------------------------- */
DROP SEQUENCE "cash_id_seq";
CREATE SEQUENCE "cash_id_seq";
DROP TABLE cash;
CREATE TABLE cash (
	id integer 		DEFAULT nextval('cash_id_seq'::text) NOT NULL,
	time integer 		DEFAULT 0 NOT NULL,
	userid integer 		DEFAULT 0 NOT NULL,
	type smallint 		DEFAULT 0 NOT NULL,
	value numeric(9,2) 	DEFAULT 0 NOT NULL,
	taxid integer		DEFAULT 0 NOT NULL,
	customerid integer 	DEFAULT 0 NOT NULL,
	comment varchar(255) 	DEFAULT '' NOT NULL,
	docid integer 		DEFAULT 0 NOT NULL,
	itemid smallint		DEFAULT 0 NOT NULL,
	reference integer	DEFAULT 0 NOT NULL,
	PRIMARY KEY (id)
);
CREATE INDEX cash_customerid_idx ON cash(customerid);
CREATE INDEX cash_docid_idx ON cash(docid);
CREATE INDEX cash_reference_idx ON cash(reference);
CREATE INDEX cash_time_idx ON cash(time);

/* -------------------------------------------------------- 
  Structure of table "networks" 
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
  Structure of table "nodes" 
-------------------------------------------------------- */
DROP SEQUENCE "nodes_id_seq";
CREATE SEQUENCE "nodes_id_seq";
DROP TABLE nodes;
CREATE TABLE nodes (
	id integer DEFAULT nextval('nodes_id_seq'::text) NOT NULL,
	name varchar(16) 	DEFAULT '' NOT NULL,
	mac varchar(20) 	DEFAULT '' NOT NULL,
	ipaddr bigint 		DEFAULT 0 NOT NULL,
	ipaddr_pub bigint 		DEFAULT 0 NOT NULL,
	passwd varchar(32)	DEFAULT '' NOT NULL,
	ownerid integer 	DEFAULT 0 NOT NULL,
	netdev integer 		DEFAULT 0 NOT NULL,
	linktype smallint	DEFAULT 0 NOT NULL,
	creationdate integer 	DEFAULT 0 NOT NULL,
	moddate integer 	DEFAULT 0 NOT NULL,
	creatorid integer 	DEFAULT 0 NOT NULL,
	modid integer 		DEFAULT 0 NOT NULL,
	access smallint 	DEFAULT 1 NOT NULL,
	warning smallint 	DEFAULT 0 NOT NULL,
	lastonline integer	DEFAULT 0 NOT NULL,
	info text		DEFAULT '' NOT NULL,
	PRIMARY KEY (id),
	UNIQUE (name),
	UNIQUE (ipaddr)
);
CREATE INDEX nodes_netdev_idx ON nodes (netdev);

/* -------------------------------------------------------- 
  Structure of table "tariffs" 
-------------------------------------------------------- */
DROP SEQUENCE "tariffs_id_seq";
CREATE SEQUENCE "tariffs_id_seq"; 
DROP TABLE tariffs;
CREATE TABLE tariffs (
	id integer DEFAULT nextval('tariffs_id_seq'::text) NOT NULL,
	name varchar(255) 	DEFAULT '' NOT NULL,
	value numeric(9,2) 	DEFAULT 0 NOT NULL,
	taxid integer 		DEFAULT 0 NOT NULL,
	prodid varchar(255) 	DEFAULT '' NOT NULL,
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
  Structure of table "payments"
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
  Structure of table "taxes" 
-------------------------------------------------------- */
DROP SEQUENCE "taxes_id_seq";
CREATE SEQUENCE "taxes_id_seq";
DROP TABLE taxes;
CREATE TABLE taxes (
    id	integer DEFAULT nextval('taxes_id_seq'::text) NOT NULL,
    value numeric(4,2) DEFAULT 0 NOT NULL,
    taxed smallint DEFAULT 0 NOT NULL,
    label varchar(16) DEFAULT '' NOT NULL,
    validfrom integer DEFAULT 0 NOT NULL,
    validto integer DEFAULT 0 NOT NULL,
    PRIMARY KEY (id)
);

/* -------------------------------------------------------- 
  Structure of table "documents" 
-------------------------------------------------------- */
DROP SEQUENCE "documents_id_seq";
CREATE SEQUENCE "documents_id_seq";
DROP TABLE documents;
CREATE TABLE documents (
	id integer DEFAULT nextval('documents_id_seq'::text) NOT NULL,
	type smallint		DEFAULT 0 NOT NULL,
	number integer 		DEFAULT 0 NOT NULL,
	cdate integer 		DEFAULT 0 NOT NULL,
	customerid integer 	DEFAULT 0 NOT NULL,
	userid integer		DEFAULT 0 NOT NULL,		
    	name varchar(255) 	DEFAULT '' NOT NULL,
    	address varchar(255) 	DEFAULT '' NOT NULL,
	zip varchar(10)		DEFAULT '' NOT NULL,
    	city varchar(32) 	DEFAULT '' NOT NULL,
	ten varchar(16) 	DEFAULT '' NOT NULL,
	ssn varchar(11) 	DEFAULT '' NOT NULL,
    	paytime smallint 	DEFAULT 0 NOT NULL,
	paytype varchar(255) 	DEFAULT '' NOT NULL,
	PRIMARY KEY (id)
);
CREATE INDEX documents_cdate_idx ON documents(cdate);

/* -------------------------------------------------------- 
  Structure of table "receiptcontents" 
-------------------------------------------------------- */
DROP TABLE receiptcontents;
CREATE TABLE receiptcontents (
	docid integer		DEFAULT 0 NOT NULL,
	itemid smallint		DEFAULT 0 NOT NULL,
	value numeric(9,2)	DEFAULT 0 NOT NULL,
	description varchar(255) DEFAULT '' NOT NULL
);
CREATE INDEX receiptcontents_docid_idx ON receiptcontents(docid);

/* -------------------------------------------------------- 
  Structure of table "invoicecontents" 
-------------------------------------------------------- */
DROP TABLE invoicecontents;
CREATE TABLE invoicecontents (
	docid integer 		DEFAULT 0 NOT NULL,
	itemid smallint		DEFAULT 0 NOT NULL,
	value numeric(9,2) 	DEFAULT 0 NOT NULL,
	taxid integer 		DEFAULT 0 NOT NULL,
	prodid varchar(255) 	DEFAULT '' NOT NULL,
	content varchar(16) 	DEFAULT '' NOT NULL,
	count numeric(9,2) 	DEFAULT 0 NOT NULL,
	description varchar(255) DEFAULT '' NOT NULL,
	tariffid integer 	DEFAULT 0 NOT NULL
);	 
CREATE INDEX invoicecontents_docid_idx ON invoicecontents (docid);

/* -------------------------------------------------------- 
  Structure of table "timestamps" 
-------------------------------------------------------- */
DROP TABLE timestamps;
CREATE TABLE timestamps (
	time integer 		DEFAULT 0  NOT NULL,
	tablename varchar(255) 	DEFAULT '' NOT NULL,
	UNIQUE (tablename)
);

/* -------------------------------------------------------- 
  Structure of table "customers" (customers)
-------------------------------------------------------- */
DROP SEQUENCE "customers_id_seq";
CREATE SEQUENCE "customers_id_seq";
DROP TABLE customers;
CREATE TABLE customers (
	id integer DEFAULT nextval('customers_id_seq'::text) NOT NULL,
	lastname varchar(255)	DEFAULT '' NOT NULL,
	name varchar(255)	DEFAULT '' NOT NULL,
	status smallint 	DEFAULT 0 NOT NULL,
	email varchar(255) 	DEFAULT '' NOT NULL,
	phone1 varchar(255) 	DEFAULT '' NOT NULL,
	phone2 varchar(255) 	DEFAULT '' NOT NULL,
	phone3 varchar(255) 	DEFAULT '' NOT NULL,
	gguin integer 		DEFAULT 0 NOT NULL,
	address varchar(255) 	DEFAULT '' NOT NULL,
	zip varchar(10)		DEFAULT '' NOT NULL,
	city varchar(32) 	DEFAULT '' NOT NULL,
	ten varchar(16) 	DEFAULT '' NOT NULL,
	ssn varchar(11) 	DEFAULT '' NOT NULL,
	info text		DEFAULT '' NOT NULL,
	serviceaddr text	DEFAULT '' NOT NULL,
	creationdate integer 	DEFAULT 0 NOT NULL,
	moddate integer 	DEFAULT 0 NOT NULL,
	creatorid integer 	DEFAULT 0 NOT NULL,
	modid integer 		DEFAULT 0 NOT NULL,
	deleted smallint 	DEFAULT 0 NOT NULL,
	message text		DEFAULT '' NOT NULL,
	pin integer		DEFAULT 0 NOT NULL,
	PRIMARY KEY (id)	
);

/* -------------------------------------------------------- 
  Structure of table "customergroups" 
-------------------------------------------------------- */
DROP SEQUENCE "customergroups_id_seq";
CREATE SEQUENCE "customergroups_id_seq";
DROP TABLE customergroups;
CREATE TABLE customergroups (
	id integer DEFAULT nextval('customergroups_id_seq'::text) NOT NULL, 
	name varchar(255) DEFAULT '' NOT NULL, 
	description text DEFAULT '' NOT NULL, 
	PRIMARY KEY (id), 
	UNIQUE (name)
);

/* -------------------------------------------------------- 
  Structure of table "customerassignments" 
-------------------------------------------------------- */
DROP SEQUENCE "customerassignments_id_seq";
CREATE SEQUENCE "customerassignments_id_seq";
DROP TABLE customerassignments;
CREATE TABLE customerassignments (
	id integer DEFAULT nextval('customerassignments_id_seq'::text) NOT NULL, 
	customergroupid integer DEFAULT 0 NOT NULL, 
	customerid integer DEFAULT 0 NOT NULL, 
	PRIMARY KEY (id),
	UNIQUE (customergroupid, customerid)
);

/* -------------------------------------------------------- 
  Structure of table "stats" 
-------------------------------------------------------- */
DROP TABLE stats;
CREATE TABLE stats (
	nodeid integer 		DEFAULT 0 NOT NULL,
	dt integer 		DEFAULT 0 NOT NULL,
	upload bigint 		DEFAULT 0,
	download bigint 	DEFAULT 0,
	PRIMARY KEY (nodeid, dt)
);
CREATE INDEX stats_nodeid_idx ON stats(nodeid);

/* ---------------------------------------------------
 Structure of table "netdevices"
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
 Structure of table "netlinks"
----------------------------------------------------*/
DROP SEQUENCE "netlinks_id_seq";
CREATE SEQUENCE "netlinks_id_seq";
DROP TABLE netlinks;
CREATE TABLE netlinks (
	id integer default nextval('netlinks_id_seq'::text) NOT NULL,
	src integer 		DEFAULT 0 NOT NULL,
	dst integer 		DEFAULT 0 NOT NULL,
	type smallint		DEFAULT 0 NOT NULL,
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
 Tables for RT (Helpdesk)
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
  UNIQUE (name)
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
  customerid integer 	DEFAULT 0 NOT NULL,
  createtime integer 	DEFAULT 0 NOT NULL,
  resolvetime integer 	DEFAULT 0 NOT NULL,
  PRIMARY KEY (id)
);
CREATE INDEX rttickets_queueid_idx ON rttickets (queueid);

DROP SEQUENCE "rtmessages_id_seq";
CREATE SEQUENCE "rtmessages_id_seq";
DROP TABLE rtmessages;
CREATE TABLE rtmessages (
  id integer default nextval('rtmessages_id_seq'::text) NOT NULL,
  ticketid integer 	DEFAULT 0 NOT NULL,
  userid integer 	DEFAULT 0 NOT NULL,
  customerid integer 	DEFAULT 0 NOT NULL,
  mailfrom varchar(255) DEFAULT '' NOT NULL,
  subject varchar(255) 	DEFAULT '' NOT NULL,
  messageid varchar(255) DEFAULT '' NOT NULL,
  inreplyto integer 	DEFAULT 0 NOT NULL,
  replyto text 		DEFAULT '' NOT NULL,
  headers text 		DEFAULT '' NOT NULL,
  body text		DEFAULT '' NOT NULL,
  createtime integer	DEFAULT 0 NOT NULL,
  PRIMARY KEY (id)
);

DROP SEQUENCE "rtrights_id_seq";
CREATE SEQUENCE "rtrights_id_seq";
DROP TABLE rtrights;
CREATE TABLE rtrights (
    id integer DEFAULT nextval('rtrights_id_seq'::text) NOT NULL, 
    userid integer DEFAULT 0 NOT NULL,
    queueid integer DEFAULT 0 NOT NULL,
    rights integer DEFAULT 0 NOT NULL,
    PRIMARY KEY (id),
    UNIQUE (userid, queueid)
);

/* ---------------------------------------------------
 Structure of table "passwd" (accounts)
------------------------------------------------------*/

DROP SEQUENCE passwd_id_seq;
CREATE SEQUENCE passwd_id_seq;
DROP TABLE passwd;
CREATE TABLE passwd (
        id integer DEFAULT nextval('passwd_id_seq'::text) NOT NULL,
	ownerid integer 	DEFAULT 0 NOT NULL,
	login varchar(200) 	DEFAULT '' NOT NULL,
	password varchar(200) 	DEFAULT '' NOT NULL,
	lastlogin integer 	DEFAULT 0 NOT NULL,
	uid integer 		DEFAULT 0 NOT NULL,
	home varchar(255) 	DEFAULT '' NOT NULL,
	type smallint 		DEFAULT 0 NOT NULL,
	expdate	integer		DEFAULT 0 NOT NULL,
	domainid integer	DEFAULT 0 NOT NULL,
	realname varchar(255)	DEFAULT '' NOT NULL,
	createtime integer	DEFAULT 0 NOT NULL,
	quota_sh integer	DEFAULT 0 NOT NULL,
	quota_mail integer	DEFAULT 0 NOT NULL,
	quota_www integer	DEFAULT 0 NOT NULL,
	quota_ftp integer	DEFAULT 0 NOT NULL,
	PRIMARY KEY (id),
	UNIQUE (login)
);

/* ---------------------------------------------------
 Structure of table "domains"
------------------------------------------------------*/

DROP SEQUENCE domains_id_seq;
CREATE SEQUENCE domains_id_seq;
DROP TABLE domains;
CREATE TABLE domains (
	id integer DEFAULT nextval('domains_id_seq'::text) NOT NULL,
	name varchar(255) 	DEFAULT '' NOT NULL,
	description text 	DEFAULT '' NOT NULL,
	PRIMARY KEY (id),
	UNIQUE (name)
);

/* ---------------------------------------------------
 Structure of table "aliases"
------------------------------------------------------*/

DROP SEQUENCE aliases_id_seq;
CREATE SEQUENCE aliases_id_seq;
DROP TABLE aliases;
CREATE TABLE aliases (
	id integer DEFAULT nextval('aliases_id_seq'::text) NOT NULL,
	login varchar(255) DEFAULT '' NOT NULL,
	accountid integer DEFAULT 0 NOT NULL,
	PRIMARY KEY (id),
	UNIQUE (login, accountid)
);

/* ---------------------------------------------------
 LMS-UI Configuration table
------------------------------------------------------*/

DROP SEQUENCE uiconfig_id_seq;
CREATE SEQUENCE uiconfig_id_seq;
DROP TABLE uiconfig;
CREATE TABLE uiconfig (
    id integer DEFAULT nextval('uiconfig_id_seq'::text) NOT NULL,
    section varchar(64) NOT NULL DEFAULT '',
    var varchar(64) NOT NULL DEFAULT '',
    value text NOT NULL DEFAULT '',
    description text NOT NULL DEFAULT '',
    disabled smallint NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    UNIQUE (section, var)
);

/* ---------------------------------------------------
 Structure of table "events" (Timetable)
------------------------------------------------------*/

DROP SEQUENCE events_id_seq;
CREATE SEQUENCE events_id_seq;
DROP TABLE events;
CREATE TABLE events (
	id integer default nextval('rtqueues_id_seq'::text) NOT NULL,
	title varchar(255) DEFAULT '' NOT NULL,
	description text DEFAULT '' NOT NULL,
	note text DEFAULT '' NOT NULL,
	date integer DEFAULT 0 NOT NULL,
	begintime smallint DEFAULT 0 NOT NULL,
	endtime smallint DEFAULT 0 NOT NULL,
	userid integer DEFAULT 0 NOT NULL,
	customerid integer DEFAULT 0 NOT NULL,
	private smallint DEFAULT 0 NOT NULL,
	closed smallint DEFAULT 0 NOT NULL,
	PRIMARY KEY (id)
);
CREATE INDEX events_date_idx ON events(date);

/* ---------------------------------------------------
 Structure of table "events" (Timetable)
------------------------------------------------------*/

DROP TABLE eventassignments;
CREATE TABLE eventassignments (
	eventid integer DEFAULT 0 NOT NULL,
	userid integer DEFAULT 0 NOT NULL,
	UNIQUE (eventid, userid)
);

/* ---------------------------------------------------
 Structure of table "sessions"
------------------------------------------------------*/

DROP TABLE sessions;
CREATE TABLE sessions (
    id varchar(50) NOT NULL default '', 
    ctime integer NOT NULL default 0, 
    mtime integer NOT NULL default 0, 
    atime integer NOT NULL default 0, 
    vdata text NOT NULL, 
    content text NOT NULL, 
    PRIMARY KEY (id)
);

/* ---------------------------------------------------
 Structure of table "cashimport"
------------------------------------------------------*/

DROP SEQUENCE cashimport_id_seq;
CREATE SEQUENCE cashimport_id_seq;
DROP TABLE cashimport;
CREATE TABLE cashimport (
    id integer DEFAULT nextval('cashimport_id_seq'::text) NOT NULL,
    date integer DEFAULT 0 NOT NULL,
    value numeric(9,2) DEFAULT 0 NOT NULL,
    customer varchar(150) DEFAULT '' NOT NULL,
    description varchar(150) DEFAULT '' NOT NULL,
    customerid integer DEFAULT 0 NOT NULL,
    hash varchar(50) DEFAULT '' NOT NULL,
    closed smallint DEFAULT 0 NOT NULL,
    PRIMARY KEY (id)
);
CREATE INDEX cashimport_hash_idx ON cashimport (hash);

/* ---------------------------------------------------
 Structure of table "daemonhosts" (lmsd config)
------------------------------------------------------*/

DROP SEQUENCE daemonhosts_id_seq;
CREATE SEQUENCE daemonhosts_id_seq;
DROP TABLE daemonhosts;
CREATE TABLE daemonhosts (
    id integer DEFAULT nextval('daemonhosts_id_seq'::text) NOT NULL,
    name varchar(255) 		DEFAULT '' NOT NULL,
    description text 		DEFAULT '' NOT NULL,
    lastreload integer 		DEFAULT 0 NOT NULL,
    reload smallint 		DEFAULT 0 NOT NULL,
    PRIMARY KEY (id),
    UNIQUE (name)
);

/* ---------------------------------------------------
 Structure of table "daemoninstances" (lmsd config)
------------------------------------------------------*/

DROP SEQUENCE daemoninstances_id_seq;
CREATE SEQUENCE daemoninstances_id_seq;
DROP TABLE daemoninstances;
CREATE TABLE daemoninstances (
    id integer DEFAULT nextval('daemoninstances_id_seq'::text) NOT NULL,
    name varchar(255) 		DEFAULT '' NOT NULL,
    hostid integer 		DEFAULT 0 NOT NULL,
    module varchar(255) 	DEFAULT '' NOT NULL,
    crontab varchar(255) 	DEFAULT '' NOT NULL,
    priority integer 		DEFAULT 0 NOT NULL,
    description text 		DEFAULT '' NOT NULL,
    disabled smallint 		DEFAULT 0 NOT NULL,
    PRIMARY KEY (id)
);

/* ---------------------------------------------------
 Structure of table "daemonconfig" (lmsd config)
------------------------------------------------------*/

DROP SEQUENCE daemonconfig_id_seq;
CREATE SEQUENCE daemonconfig_id_seq;
DROP TABLE daemonconfig;
CREATE TABLE daemonconfig (
    id integer DEFAULT nextval('daemonconfig_id_seq'::text) NOT NULL,
    instanceid integer 		DEFAULT 0 NOT NULL,
    var varchar(64) 		DEFAULT '' NOT NULL,
    value text 			DEFAULT '' NOT NULL,
    description text 		DEFAULT '' NOT NULL,
    disabled smallint 		DEFAULT 0 NOT NULL,
    PRIMARY KEY (id),
    UNIQUE(instanceid, var)
);

/* ---------------------------------------------------
 Structure of table "dbinfo"
------------------------------------------------------*/

DROP TABLE dbinfo;
CREATE TABLE dbinfo (
    keytype varchar(255) DEFAULT '' NOT NULL,
    keyvalue varchar(255) DEFAULT '' NOT NULL,
    PRIMARY KEY (keytype)
);

INSERT INTO dbinfo (keytype, keyvalue) VALUES ('dbversion','2005062100');
