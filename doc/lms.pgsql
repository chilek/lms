/* $Id$ */ 

/* -------------------------------------------------------- 
  Structure of table "users" 
-------------------------------------------------------- */
DROP SEQUENCE users_id_seq;
CREATE SEQUENCE users_id_seq;
DROP TABLE users;
CREATE TABLE users (
	id integer DEFAULT nextval('users_id_seq'::text) NOT NULL,
	login varchar(32) 	DEFAULT '' NOT NULL,
	name varchar(64) 	DEFAULT '' NOT NULL,
	email varchar(255) 	DEFAULT '' NOT NULL,
	position varchar(255) 	DEFAULT '' NOT NULL,
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
DROP SEQUENCE assignments_id_seq;
CREATE SEQUENCE assignments_id_seq;
DROP TABLE assignments;
CREATE TABLE assignments (
	id integer default nextval('assignments_id_seq'::text) NOT NULL,
	tariffid integer 	DEFAULT 0 NOT NULL,
	liabilityid integer 	DEFAULT 0 NOT NULL,
	customerid integer	DEFAULT 0 NOT NULL,
	period smallint 	DEFAULT 0 NOT NULL,
	at integer 		DEFAULT 0 NOT NULL,
	datefrom integer	DEFAULT 0 NOT NULL,
	dateto integer		DEFAULT 0 NOT NULL,
	invoice smallint 	DEFAULT 0 NOT NULL,
	suspended smallint	DEFAULT 0 NOT NULL,
	settlement smallint	DEFAULT 0 NOT NULL,
	discount numeric(4,2)	DEFAULT 0 NOT NULL,
	PRIMARY KEY (id)
);
CREATE INDEX assignments_tariffid_idx ON assignments (tariffid);
CREATE INDEX assignments_customerid_idx ON assignments (customerid);

/* -------------------------------------------------------- 
  Structure of table "cash" 
-------------------------------------------------------- */
DROP SEQUENCE cash_id_seq;
CREATE SEQUENCE cash_id_seq;
DROP TABLE cash;
CREATE TABLE cash (
	id integer 		DEFAULT nextval('cash_id_seq'::text) NOT NULL,
	time integer 		DEFAULT 0 NOT NULL,
	type smallint 		DEFAULT 0 NOT NULL,
	userid integer 		DEFAULT 0 NOT NULL,
	value numeric(9,2) 	DEFAULT 0 NOT NULL,
	taxid integer		DEFAULT 0 NOT NULL,
	customerid integer 	DEFAULT 0 NOT NULL,
	comment text 		DEFAULT '' NOT NULL,
	docid integer 		DEFAULT 0 NOT NULL,
	itemid smallint		DEFAULT 0 NOT NULL,
	importid integer	DEFAULT NULL,
	sourceid integer	DEFAULT NULL,
	PRIMARY KEY (id)
);
CREATE INDEX cash_customerid_idx ON cash (customerid);
CREATE INDEX cash_docid_idx ON cash (docid);
CREATE INDEX cash_importid_idx ON cash (importid);
CREATE INDEX cash_sourceid_idx ON cash (sourceid);
CREATE INDEX cash_time_idx ON cash (time);

/* -------------------------------------------------------- 
  Structure of table "networks" 
-------------------------------------------------------- */
DROP SEQUENCE networks_id_seq;
CREATE SEQUENCE networks_id_seq;
DROP TABLE networks;
CREATE TABLE networks (
	id integer DEFAULT nextval('networks_id_seq'::text) NOT NULL,
	name varchar(255) 	DEFAULT '' NOT NULL,
	address bigint 		DEFAULT 0 NOT NULL,
	mask varchar(16) 	DEFAULT '' NOT NULL,
	interface varchar(16) 	DEFAULT '' NOT NULL, 
	gateway varchar(16) 	DEFAULT '' NOT NULL,
	dns varchar(16) 	DEFAULT '' NOT NULL,
	dns2 varchar(16) 	DEFAULT '' NOT NULL,
	domain varchar(64) 	DEFAULT '' NOT NULL,
	wins varchar(16) 	DEFAULT '' NOT NULL,
	dhcpstart varchar(16) 	DEFAULT '' NOT NULL,
	dhcpend varchar(16) 	DEFAULT '' NOT NULL,
	disabled smallint 	DEFAULT 0 NOT NULL,
	notes text		DEFAULT '' NOT NULL,
	PRIMARY KEY (id),
	UNIQUE (name),
	UNIQUE (address)
);

/* -------------------------------------------------------- 
  Structure of table "nodes" 
-------------------------------------------------------- */
DROP SEQUENCE nodes_id_seq;
CREATE SEQUENCE nodes_id_seq;
DROP TABLE nodes;
CREATE TABLE nodes (
	id integer DEFAULT nextval('nodes_id_seq'::text) NOT NULL,
	name varchar(32) 	DEFAULT '' NOT NULL,
	mac varchar(20) 	DEFAULT '' NOT NULL,
	ipaddr bigint 		DEFAULT 0 NOT NULL,
	ipaddr_pub bigint 	DEFAULT 0 NOT NULL,
	passwd varchar(32)	DEFAULT '' NOT NULL,
	ownerid integer 	DEFAULT 0 NOT NULL,
	netdev integer 		DEFAULT 0 NOT NULL,
	linktype smallint	DEFAULT 0 NOT NULL,
	port smallint		DEFAULT 0 NOT NULL,
	creationdate integer 	DEFAULT 0 NOT NULL,
	moddate integer 	DEFAULT 0 NOT NULL,
	creatorid integer 	DEFAULT 0 NOT NULL,
	modid integer 		DEFAULT 0 NOT NULL,
	access smallint 	DEFAULT 1 NOT NULL,
	warning smallint 	DEFAULT 0 NOT NULL,
	chkmac smallint 	DEFAULT 1 NOT NULL,
	halfduplex smallint	DEFAULT 0 NOT NULL,
	lastonline integer	DEFAULT 0 NOT NULL,
	info text		DEFAULT '' NOT NULL,
	location text		DEFAULT '' NOT NULL,
	nas smallint 		DEFAULT 0 NOT NULL,
	PRIMARY KEY (id),
	UNIQUE (name),
	UNIQUE (ipaddr)
);
CREATE INDEX nodes_netdev_idx ON nodes (netdev);
CREATE INDEX nodes_ownerid_idx ON nodes (ownerid);
CREATE INDEX nodes_ipaddr_pub_idx ON nodes (ipaddr_pub);
CREATE SEQUENCE nodegroups_id_seq;

/* -------------------------------------------------------- 
  Structure of table "nodegroups" 
-------------------------------------------------------- */
DROP SEQUENCE nodegroups_id_seq;
CREATE SEQUENCE nodegroups_id_seq;
DROP TABLE nodegroups;
CREATE TABLE nodegroups (
        id              integer         NOT NULL DEFAULT nextval('nodegroups_id_seq'::text),
	name            varchar(255)    NOT NULL DEFAULT '',
	prio		integer		NOT NULL DEFAULT 0,
	description     text            NOT NULL DEFAULT '',
	PRIMARY KEY (id),
	UNIQUE (name)
);

/* -------------------------------------------------------- 
  Structure of table "nodegroupassignments" 
-------------------------------------------------------- */
DROP SEQUENCE nodegroupassignments_id_seq;
CREATE SEQUENCE nodegroupassignments_id_seq;
DROP TABLE nodegroupassignments;
CREATE TABLE nodegroupassignments (
        id              integer         NOT NULL DEFAULT nextval('nodegroupassignments_id_seq'::text),
	nodegroupid     integer         NOT NULL DEFAULT 0,
	nodeid          integer         NOT NULL DEFAULT 0,
	PRIMARY KEY (id),
	UNIQUE (nodeid, nodegroupid)
);

/* -------------------------------------------------------- 
  Structure of table "nodeassignments" 
-------------------------------------------------------- */
DROP SEQUENCE nodeassignments_id_seq;
CREATE SEQUENCE nodeassignments_id_seq;
DROP TABLE nodeassignments;
CREATE TABLE nodeassignments (
        id integer              DEFAULT nextval('nodeassignments_id_seq'::text) NOT NULL,
	nodeid integer          NOT NULL
		REFERENCES nodes (id) ON DELETE CASCADE ON UPDATE CASCADE,
	assignmentid integer    NOT NULL
		REFERENCES assignments (id) ON DELETE CASCADE ON UPDATE CASCADE,
	PRIMARY KEY (id),
	UNIQUE (nodeid, assignmentid)
);

CREATE INDEX nodeassignments_assignmentid_idx ON nodeassignments (assignmentid);

/* -------------------------------------------------------- 
  Structure of table "tariffs" 
-------------------------------------------------------- */
DROP SEQUENCE tariffs_id_seq;
CREATE SEQUENCE tariffs_id_seq; 
DROP TABLE tariffs;
CREATE TABLE tariffs (
	id integer DEFAULT nextval('tariffs_id_seq'::text) NOT NULL,
	name varchar(255) 	DEFAULT '' NOT NULL,
	type smallint		DEFAULT 1 NOT NULL,
	value numeric(9,2) 	DEFAULT 0 NOT NULL,
	taxid integer 		DEFAULT 0 NOT NULL,
	prodid varchar(255) 	DEFAULT '' NOT NULL,
	uprate integer		DEFAULT 0 NOT NULL,
	upceil integer		DEFAULT 0 NOT NULL,
	downrate integer	DEFAULT 0 NOT NULL,
	downceil integer	DEFAULT 0 NOT NULL,
	climit integer		DEFAULT 0 NOT NULL,
	plimit integer		DEFAULT 0 NOT NULL,
	dlimit integer		DEFAULT 0 NOT NULL,
	uprate_n integer        DEFAULT NULL,
	upceil_n integer        DEFAULT NULL,
	downrate_n integer      DEFAULT NULL,
	downceil_n integer      DEFAULT NULL,
	climit_n integer        DEFAULT NULL,
	plimit_n integer        DEFAULT NULL,			
	domain_limit integer	DEFAULT NULL,
	alias_limit integer	DEFAULT NULL,
	sh_limit integer	DEFAULT NULL,
	www_limit integer	DEFAULT NULL,
	mail_limit integer	DEFAULT NULL,
	ftp_limit integer	DEFAULT NULL,
	sql_limit integer	DEFAULT NULL,
	quota_sh_limit integer	DEFAULT NULL,
	quota_www_limit integer	DEFAULT NULL,
	quota_mail_limit integer DEFAULT NULL,
	quota_ftp_limit integer	DEFAULT NULL,
	quota_sql_limit integer	DEFAULT NULL,
	description text	DEFAULT '' NOT NULL,
	PRIMARY KEY (id),
	UNIQUE (name)
);
CREATE INDEX tariffs_type_idx ON tariffs (type);

/* -------------------------------------------------------- 
  Structure of table "liabilities" 
-------------------------------------------------------- */
DROP SEQUENCE liabilities_id_seq;
CREATE SEQUENCE liabilities_id_seq;
DROP TABLE liabilities;
CREATE TABLE liabilities (
	id integer DEFAULT nextval('liabilities_id_seq'::text) NOT NULL,
	value numeric(9,2)  	DEFAULT 0 NOT NULL,
	name text           	DEFAULT '' NOT NULL,
	taxid integer       	DEFAULT 0 NOT NULL,
	prodid varchar(255) 	DEFAULT '' NOT NULL,
	PRIMARY KEY (id)
);
										   
/* ---------------------------------------------------------
  Structure of table "payments"
--------------------------------------------------------- */
DROP SEQUENCE payments_id_seq;
CREATE SEQUENCE payments_id_seq;
DROP TABLE payments;
CREATE TABLE payments (
	id integer DEFAULT nextval('payments_id_seq'::text) NOT NULL,
	name varchar(255) 	DEFAULT '' NOT NULL,
	value numeric(9,2) 	DEFAULT 0 NOT NULL,
	creditor varchar(255) 	DEFAULT '' NOT NULL,
	period smallint		DEFAULT 0 NOT NULL,
	at smallint 		DEFAULT 0 NOT NULL,
	description text	DEFAULT '' NOT NULL,
	PRIMARY KEY (id)
);

/* -------------------------------------------------------- 
  Structure of table "taxes" 
-------------------------------------------------------- */
DROP SEQUENCE taxes_id_seq;
CREATE SEQUENCE taxes_id_seq;
DROP TABLE taxes;
CREATE TABLE taxes (
    id integer DEFAULT nextval('taxes_id_seq'::text) NOT NULL,
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
DROP SEQUENCE documents_id_seq;
CREATE SEQUENCE documents_id_seq;
DROP TABLE documents;
CREATE TABLE documents (
	id integer DEFAULT nextval('documents_id_seq'::text) NOT NULL,
	type smallint		DEFAULT 0 NOT NULL,
	number integer 		DEFAULT 0 NOT NULL,
	numberplanid integer	DEFAULT 0 NOT NULL,
	extnumber varchar(255) 	DEFAULT '' NOT NULL,
	cdate integer 		DEFAULT 0 NOT NULL,
	customerid integer 	DEFAULT 0 NOT NULL,
	userid integer		DEFAULT 0 NOT NULL,		
	divisionid integer	DEFAULT 0 NOT NULL,		
    	name varchar(255) 	DEFAULT '' NOT NULL,
    	address varchar(255) 	DEFAULT '' NOT NULL,
	zip varchar(10)		DEFAULT '' NOT NULL,
    	city varchar(32) 	DEFAULT '' NOT NULL,
	countryid integer	DEFAULT 0 NOT NULL,
	ten varchar(16) 	DEFAULT '' NOT NULL,
	ssn varchar(11) 	DEFAULT '' NOT NULL,
    	paytime smallint 	DEFAULT 0 NOT NULL,
	paytype varchar(255) 	DEFAULT '' NOT NULL,
	closed smallint		DEFAULT 0 NOT NULL,
	reference integer	DEFAULT 0 NOT NULL,
	reason varchar(255)	DEFAULT '' NOT NULL,
	PRIMARY KEY (id)
);
CREATE INDEX documents_cdate_idx ON documents(cdate);
CREATE INDEX documents_numberplanid_idx ON documents(numberplanid);
CREATE INDEX documents_customerid_idx ON documents(customerid);
CREATE INDEX documents_closed_idx ON documents(closed);

/* -------------------------------------------------------- 
  Structure of table "documentcontents" 
-------------------------------------------------------- */
DROP TABLE documentcontents;
CREATE TABLE documentcontents (
	docid integer 		DEFAULT 0 NOT NULL,
	title text 		DEFAULT '' NOT NULL,
	fromdate integer 	DEFAULT 0 NOT NULL,
	todate integer 		DEFAULT 0 NOT NULL,
	filename varchar(255) 	DEFAULT '' NOT NULL,
	contenttype varchar(255) DEFAULT '' NOT NULL,
	md5sum varchar(32) 	DEFAULT '' NOT NULL,
	description text 	DEFAULT '' NOT NULL,
	UNIQUE (docid)
);
CREATE INDEX documentcontents_md5sum_idx ON documentcontents (md5sum);
CREATE INDEX documentcontents_todate_idx ON documentcontents (todate);
CREATE INDEX documentcontents_fromdate_idx ON documentcontents (fromdate);


/* -------------------------------------------------------- 
  Structure of table "receiptcontents" 
-------------------------------------------------------- */
DROP TABLE receiptcontents;
CREATE TABLE receiptcontents (
	docid integer		DEFAULT 0 NOT NULL,
	itemid smallint		DEFAULT 0 NOT NULL,
	value numeric(9,2)	DEFAULT 0 NOT NULL,
	regid integer		DEFAULT 0 NOT NULL,
	description text 	DEFAULT '' NOT NULL
);
CREATE INDEX receiptcontents_docid_idx ON receiptcontents(docid);
CREATE INDEX receiptcontents_regid_idx ON receiptcontents(regid);

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
	description text 	DEFAULT '' NOT NULL,
	tariffid integer 	DEFAULT 0 NOT NULL,
	discount numeric(4,2)	DEFAULT 0 NOT NULL
);	 
CREATE INDEX invoicecontents_docid_idx ON invoicecontents (docid);

/* -------------------------------------------------------- 
  Structure of table "debitnotecontents" 
-------------------------------------------------------- */
DROP TABLE debitnotecontents;
DROP SEQUENCE debitnotecontents_id_seq;
CREATE SEQUENCE debitnotecontents_id_seq;
CREATE TABLE debitnotecontents (
	id integer 		DEFAULT nextval('debitnotecontents_id_seq'::text) NOT NULL,
        docid integer           DEFAULT 0 NOT NULL,
	itemid smallint         DEFAULT 0 NOT NULL,
	value numeric(9,2)      DEFAULT 0 NOT NULL,
        description text 	DEFAULT '' NOT NULL,
	PRIMARY KEY (id),
	UNIQUE (docid, itemid)
);

/* -------------------------------------------------------- 
  Structure of table "numberplans" 
-------------------------------------------------------- */
DROP SEQUENCE numberplans_id_seq;
CREATE SEQUENCE numberplans_id_seq;
DROP TABLE numberplans;
CREATE TABLE numberplans (
	id integer DEFAULT nextval('numberplans_id_seq'::text) NOT NULL,
	template varchar(255) DEFAULT '' NOT NULL,
	period smallint DEFAULT 0 NOT NULL,
	doctype integer DEFAULT 0 NOT NULL,
	isdefault smallint DEFAULT 0 NOT NULL,
	PRIMARY KEY (id)
);

/* -------------------------------------------------------- 
  Structure of table "numberplanassignments" 
-------------------------------------------------------- */
DROP SEQUENCE numberplanassignments_id_seq;
CREATE SEQUENCE numberplanassignments_id_seq;
DROP TABLE numberplanassignments;
CREATE TABLE numberplanassignments (
	id integer DEFAULT nextval('numberplanassignments_id_seq'::text) NOT NULL,
	planid integer DEFAULT 0 NOT NULL,
	divisionid integer DEFAULT 0 NOT NULL,
	PRIMARY KEY (id),
	UNIQUE (planid, divisionid)
);
CREATE INDEX numberplanassignments_divisionid_idx ON numberplanassignments (divisionid);

/* -------------------------------------------------------- 
  Structure of table "customers" (customers)
-------------------------------------------------------- */
DROP SEQUENCE customers_id_seq;
CREATE SEQUENCE customers_id_seq;
DROP TABLE customers;
CREATE TABLE customers (
	id integer DEFAULT nextval('customers_id_seq'::text) NOT NULL,
	lastname varchar(128)	DEFAULT '' NOT NULL,
	name varchar(128)	DEFAULT '' NOT NULL,
	status smallint 	DEFAULT 0 NOT NULL,
	type smallint		DEFAULT 0 NOT NULL,
	email varchar(255) 	DEFAULT '' NOT NULL,
	address varchar(255) 	DEFAULT '' NOT NULL,
	zip varchar(10)		DEFAULT '' NOT NULL,
	city varchar(32) 	DEFAULT '' NOT NULL,
	countryid integer	DEFAULT 0 NOT NULL,
	ten varchar(16) 	DEFAULT '' NOT NULL,
	ssn varchar(11) 	DEFAULT '' NOT NULL,
	regon varchar(255) 	DEFAULT '' NOT NULL,
	rbe varchar(255) 	DEFAULT '' NOT NULL, -- EDG/KRS
	icn varchar(255) 	DEFAULT '' NOT NULL, -- dow.os.
	info text		DEFAULT '' NOT NULL,
	notes text		DEFAULT '' NOT NULL,
	serviceaddr text	DEFAULT '' NOT NULL,
	creationdate integer 	DEFAULT 0 NOT NULL,
	moddate integer 	DEFAULT 0 NOT NULL,
	creatorid integer 	DEFAULT 0 NOT NULL,
	modid integer 		DEFAULT 0 NOT NULL,
	deleted smallint 	DEFAULT 0 NOT NULL,
	message text		DEFAULT '' NOT NULL,
	pin integer		DEFAULT 0 NOT NULL,
	cutoffstop integer	DEFAULT 0 NOT NULL,
	consentdate integer	DEFAULT 0 NOT NULL,
	divisionid integer	DEFAULT 0 NOT NULL,
    	paytime smallint 	DEFAULT -1 NOT NULL,
    	paytype varchar(255) 	DEFAULT NULL,
	PRIMARY KEY (id)
);

CREATE INDEX customers_zip_idx ON customers (zip);
CREATE INDEX customers_lastname_idx ON customers (lastname, name);

/* -------------------------------------------------------- 
  Structure of table "customergroups" 
-------------------------------------------------------- */
DROP SEQUENCE customergroups_id_seq;
CREATE SEQUENCE customergroups_id_seq;
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
DROP SEQUENCE customerassignments_id_seq;
CREATE SEQUENCE customerassignments_id_seq;
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
CREATE INDEX stats_dt_idx ON stats(dt);

/* ---------------------------------------------------
 Structure of table "netdevices"
----------------------------------------------------*/
DROP SEQUENCE netdevices_id_seq;
CREATE SEQUENCE netdevices_id_seq;
DROP TABLE netdevices;
CREATE TABLE netdevices (
	id integer default nextval('netdevices_id_seq'::text) NOT NULL,
	name varchar(32) 	DEFAULT '' NOT NULL,
	location varchar(255) 	DEFAULT '' NOT NULL,
	description text 	DEFAULT '' NOT NULL,
	producer varchar(64) 	DEFAULT '' NOT NULL,
	model varchar(32) 	DEFAULT '' NOT NULL,
	serialnumber varchar(32) DEFAULT '' NOT NULL,
	ports integer 		DEFAULT 0 NOT NULL,
	purchasetime integer	DEFAULT 0 NOT NULL,
	guaranteeperiod smallint DEFAULT 0,
	shortname varchar(32) 	DEFAULT '' NOT NULL,
	nastype integer 	DEFAULT 0 NOT NULL,
	clients integer 	DEFAULT 0 NOT NULL,
	secret varchar(60) 	DEFAULT '' NOT NULL,
	community varchar(50) 	DEFAULT '' NOT NULL,
	channelid integer 	DEFAULT NULL
	    REFERENCES ewx_channels (id) ON DELETE SET NULL ON UPDATE CASCADE,
	PRIMARY KEY (id)
);

CREATE INDEX netdevices_channelid_idx ON netdevices (channelid);

/* ---------------------------------------------------
 Structure of table "netlinks"
----------------------------------------------------*/
DROP SEQUENCE netlinks_id_seq;
CREATE SEQUENCE netlinks_id_seq;
DROP TABLE netlinks;
CREATE TABLE netlinks (
	id integer default nextval('netlinks_id_seq'::text) NOT NULL,
	src integer 		DEFAULT 0 NOT NULL,
	dst integer 		DEFAULT 0 NOT NULL,
	type smallint		DEFAULT 0 NOT NULL,
	srcport smallint	DEFAULT 0 NOT NULL,
	dstport smallint	DEFAULT 0 NOT NULL,
	PRIMARY KEY  (id),
	UNIQUE (src, dst)
);

/* ---------------------------------------------------
 Functions for network address translations
------------------------------------------------------*/
CREATE OR REPLACE FUNCTION inet_ntoa(bigint) RETURNS text AS $$
SELECT
        ($1/(256*256*256))::text
        ||'.'||
	($1/(256*256) - $1/(256*256*256)*256)::text
	||'.'||
	($1/256 - $1/(256*256)*256)::text
	||'.'||
	($1 - $1/256*256)::text;
$$ LANGUAGE SQL IMMUTABLE;
				   
CREATE OR REPLACE FUNCTION inet_aton(text) RETURNS bigint AS $$
SELECT
	split_part($1,'.',1)::int8*(256*256*256)+
	split_part($1,'.',2)::int8*(256*256)+
	split_part($1,'.',3)::int8*256+
	split_part($1,'.',4)::int8;
$$ LANGUAGE SQL IMMUTABLE;			       

CREATE OR REPLACE FUNCTION mask2prefix(bigint) RETURNS smallint AS $$
SELECT
	length(replace(ltrim(textin(bit_out($1::bit(32))), '0'), '0', ''))::smallint;
$$ LANGUAGE SQL IMMUTABLE;
    
CREATE OR REPLACE FUNCTION broadcast(bigint, bigint) RETURNS bigint AS $$
SELECT
	($1::bit(32) |  ~($2::bit(32)))::bigint;
$$ LANGUAGE SQL IMMUTABLE;

/* --------------------------------------------------
 Tables for RT (Helpdesk)
-----------------------------------------------------*/
DROP TABLE rtattachments;  
CREATE TABLE rtattachments (
	messageid integer 	DEFAULT 0 NOT NULL, 
	filename varchar(255) 	DEFAULT '' NOT NULL, 
	contenttype varchar(255) DEFAULT '' NOT NULL
);

DROP SEQUENCE rtqueues_id_seq;
CREATE SEQUENCE rtqueues_id_seq;
DROP TABLE rtqueues;
CREATE TABLE rtqueues (
  id integer default nextval('rtqueues_id_seq'::text) NOT NULL,
  name varchar(255) 	DEFAULT '' NOT NULL,
  email varchar(255) 	DEFAULT '' NOT NULL,
  description text	DEFAULT '' NOT NULL,
  PRIMARY KEY (id),
  UNIQUE (name)
);

DROP SEQUENCE rttickets_id_seq;
CREATE SEQUENCE rttickets_id_seq;
DROP TABLE rttickets;
CREATE TABLE rttickets (
  id integer default nextval('rttickets_id_seq'::text) NOT NULL,  
  queueid integer 	DEFAULT 0 NOT NULL,
  requestor varchar(255) DEFAULT '' NOT NULL,
  subject varchar(255) 	DEFAULT '' NOT NULL,
  state smallint 	DEFAULT 0 NOT NULL,
  cause smallint	DEFAULT 0 NOT NULL,
  owner integer 	DEFAULT 0 NOT NULL,
  customerid integer 	DEFAULT 0 NOT NULL,
  creatorid integer 	DEFAULT 0 NOT NULL,
  createtime integer 	DEFAULT 0 NOT NULL,
  resolvetime integer 	DEFAULT 0 NOT NULL,
  PRIMARY KEY (id)
);

CREATE INDEX rttickets_queueid_idx ON rttickets (queueid);
CREATE INDEX rttickets_customerid_idx ON rttickets (customerid);
CREATE INDEX rttickets_creatorid_idx ON rttickets (creatorid);
CREATE INDEX rttickets_createtime_idx ON rttickets (createtime);

DROP SEQUENCE rtmessages_id_seq;
CREATE SEQUENCE rtmessages_id_seq;
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

CREATE INDEX rtmessages_ticketid_idx ON rtmessages (ticketid);

DROP SEQUENCE rtnotes_id_seq;
CREATE SEQUENCE rtnotes_id_seq;
DROP TABLE rtnotes;
CREATE TABLE rtnotes (
	id integer default nextval('rtnotes_id_seq'::text) NOT NULL,
	ticketid integer      DEFAULT 0 NOT NULL,
        userid integer        DEFAULT 0 NOT NULL,
	body text             DEFAULT '' NOT NULL,
	createtime integer    DEFAULT 0 NOT NULL,
	PRIMARY KEY (id)
);
			  
CREATE INDEX rtnotes_ticketid_idx ON rtnotes (ticketid);

DROP SEQUENCE rtrights_id_seq;
CREATE SEQUENCE rtrights_id_seq;
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
	quota_sql integer	DEFAULT 0 NOT NULL,
	mail_forward varchar(255) DEFAULT '' NOT NULL,
	mail_bcc varchar(255) 	DEFAULT '' NOT NULL,
	description text	DEFAULT '' NOT NULL,
	PRIMARY KEY (id),
	UNIQUE (login, domainid)
);
CREATE INDEX passwd_ownerid_idx ON passwd (ownerid);

/* ---------------------------------------------------
 Structure of table "domains"
------------------------------------------------------*/
DROP SEQUENCE domains_id_seq;
CREATE SEQUENCE domains_id_seq;
DROP TABLE domains;
CREATE TABLE domains (
	id integer DEFAULT nextval('domains_id_seq'::text) NOT NULL,
	ownerid integer 	DEFAULT 0 NOT NULL,
	name varchar(255) 	DEFAULT '' NOT NULL,
	description text 	DEFAULT '' NOT NULL,
	master varchar(128) 	DEFAULT NULL,
	last_check integer 	DEFAULT NULL,
	type varchar(6) 	DEFAULT '' NOT NULL,
	notified_serial integer DEFAULT NULL,
	account varchar(40) 	DEFAULT NULL,
	PRIMARY KEY (id),
	UNIQUE (name)
);
CREATE INDEX domains_ownerid_idx ON domains (ownerid);

/* ---------------------------------------------------
 Structure of table "records" (DNS)
------------------------------------------------------*/
DROP SEQUENCE records_id_seq;
CREATE SEQUENCE records_id_seq;
DROP TABLE records;
CREATE TABLE records (
	id integer		DEFAULT nextval('records_id_seq'::text) NOT NULL,
	domain_id integer	DEFAULT NULL
		REFERENCES domains (id) ON DELETE CASCADE ON UPDATE CASCADE,
	name varchar(255)	DEFAULT NULL,
	type varchar(6)		DEFAULT NULL,
	content varchar(255)	DEFAULT NULL,
	ttl integer		DEFAULT NULL,
	prio integer		DEFAULT NULL,
	change_date integer	DEFAULT NULL,
	PRIMARY KEY (id)
);

CREATE INDEX records_name_type_idx ON records (name, type, domain_id);
CREATE INDEX records_domain_id_idx ON records (domain_id);

/* ---------------------------------------------------
 Structure of table "supermasters" (DNS)
------------------------------------------------------*/
DROP SEQUENCE supermasters_id_seq;
CREATE SEQUENCE supermasters_id_seq;
DROP TABLE supermasters;
CREATE TABLE supermasters (
	id integer		DEFAULT nextval('supermasters_id_seq'::text) NOT NULL,
	ip varchar(25)		NOT NULL,
	nameserver varchar(255) NOT NULL,
	account varchar(40)	DEFAULT NULL,
	PRIMARY KEY (id)
);

/* ---------------------------------------------------
 Structure of table "aliases"
------------------------------------------------------*/
DROP SEQUENCE aliases_id_seq;
CREATE SEQUENCE aliases_id_seq;
DROP TABLE aliases;
CREATE TABLE aliases (
	id 		integer 	DEFAULT nextval('aliases_id_seq'::text) NOT NULL,
	login 		varchar(255) 	DEFAULT '' NOT NULL,
	domainid 	integer 	DEFAULT 0 NOT NULL,
	PRIMARY KEY (id),
	UNIQUE (login, domainid)
);

/* ---------------------------------------------------
 Structure of table "aliasassignments"
------------------------------------------------------*/
DROP SEQUENCE aliasassignments_id_seq;
CREATE SEQUENCE aliasassignments_id_seq;
DROP TABLE aliasassignments;
CREATE TABLE aliasassignments (
	id              integer         DEFAULT nextval('passwd_id_seq'::text) NOT NULL,
	aliasid         integer         DEFAULT 0 NOT NULL,
	accountid       integer         DEFAULT 0 NOT NULL,
	mail_forward    varchar(255)    DEFAULT '' NOT NULL,
	PRIMARY KEY (id),
	UNIQUE (aliasid, accountid, mail_forward)
);

/* ---------------------------------------------------
 LMS-UI Configuration table
------------------------------------------------------*/
DROP SEQUENCE uiconfig_id_seq;
CREATE SEQUENCE uiconfig_id_seq;
DROP TABLE uiconfig;
CREATE TABLE uiconfig (
    id 		integer 	DEFAULT nextval('uiconfig_id_seq'::text) NOT NULL,
    section 	varchar(64) 	NOT NULL DEFAULT '',
    var 	varchar(64) 	NOT NULL DEFAULT '',
    value 	text 		NOT NULL DEFAULT '',
    description text 		NOT NULL DEFAULT '',
    disabled 	smallint 	NOT NULL DEFAULT 0,
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
	id 		integer 	DEFAULT nextval('events_id_seq'::text) NOT NULL,
	title 		varchar(255) 	DEFAULT '' NOT NULL,
	description 	text 		DEFAULT '' NOT NULL,
	note 		text 		DEFAULT '' NOT NULL,
	date 		integer 	DEFAULT 0 NOT NULL,
	begintime 	smallint 	DEFAULT 0 NOT NULL,
	endtime 	smallint 	DEFAULT 0 NOT NULL,
	userid 		integer 	DEFAULT 0 NOT NULL,
	customerid 	integer 	DEFAULT 0 NOT NULL,
	private 	smallint 	DEFAULT 0 NOT NULL,
	closed 		smallint 	DEFAULT 0 NOT NULL,
	PRIMARY KEY (id)
);
CREATE INDEX events_date_idx ON events(date);

/* ---------------------------------------------------
 Structure of table "events" (Timetable)
------------------------------------------------------*/
DROP TABLE eventassignments;
CREATE TABLE eventassignments (
	eventid 	integer 	DEFAULT 0 NOT NULL,
	userid 		integer 	DEFAULT 0 NOT NULL,
	UNIQUE (eventid, userid)
);

/* ---------------------------------------------------
 Structure of table "sessions"
------------------------------------------------------*/
DROP TABLE sessions;
CREATE TABLE sessions (
    id 		varchar(50) 	NOT NULL DEFAULT '', 
    ctime 	integer 	NOT NULL DEFAULT 0, 
    mtime 	integer 	NOT NULL DEFAULT 0, 
    atime 	integer 	NOT NULL DEFAULT 0, 
    vdata 	text 		NOT NULL, 
    content 	text 		NOT NULL, 
    PRIMARY KEY (id)
);

/* ---------------------------------------------------
 Structure of table "cashimport"
------------------------------------------------------*/
DROP SEQUENCE cashimport_id_seq;
CREATE SEQUENCE cashimport_id_seq;
DROP TABLE cashimport;
CREATE TABLE cashimport (
    id integer 			DEFAULT nextval('cashimport_id_seq'::text) NOT NULL,
    date integer 		DEFAULT 0 NOT NULL,
    value numeric(9,2) 		DEFAULT 0 NOT NULL,
    customer varchar(150) 	DEFAULT '' NOT NULL,
    description varchar(150) 	DEFAULT '' NOT NULL,
    customerid integer 		DEFAULT 0 NOT NULL,
    hash varchar(50) 		DEFAULT '' NOT NULL,
    closed smallint 		DEFAULT 0 NOT NULL,
    sourceid integer		DEFAULT NULL,
    PRIMARY KEY (id)
);
CREATE INDEX cashimport_hash_idx ON cashimport (hash);

/* ---------------------------------------------------
 Structure of table "cashsources"
------------------------------------------------------*/
DROP SEQUENCE cashsources_id_seq;
CREATE SEQUENCE cashsources_id_seq;
DROP TABLE cashsources;
CREATE TABLE cashsources (
    id integer      	DEFAULT nextval('cashsources_id_seq'::text) NOT NULL,
    name varchar(32)    DEFAULT '' NOT NULL,
    description text	DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE (name)
);

/* ---------------------------------------------------
 Structure of table "hosts"
------------------------------------------------------*/
DROP SEQUENCE hosts_id_seq;
CREATE SEQUENCE hosts_id_seq;
DROP TABLE hosts;
CREATE TABLE hosts (
    id integer DEFAULT nextval('hosts_id_seq'::text) NOT NULL,
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
    id 		integer 	DEFAULT nextval('daemonconfig_id_seq'::text) NOT NULL,
    instanceid 	integer 	DEFAULT 0 NOT NULL,
    var 	varchar(64) 	DEFAULT '' NOT NULL,
    value 	text 		DEFAULT '' NOT NULL,
    description text 		DEFAULT '' NOT NULL,
    disabled 	smallint 	DEFAULT 0 NOT NULL,
    PRIMARY KEY (id),
    UNIQUE(instanceid, var)
);

/* ---------------------------------------------------
 Structure of table "docrights"
------------------------------------------------------*/
DROP SEQUENCE docrights_id_seq;
CREATE SEQUENCE docrights_id_seq;
DROP TABLE docrights;
CREATE TABLE docrights (
    id          integer         DEFAULT nextval('docrights_id_seq'::text) NOT NULL,
    userid      integer         DEFAULT 0 NOT NULL,
    doctype     integer         DEFAULT 0 NOT NULL,
    rights      integer         DEFAULT 0 NOT NULL,
    PRIMARY KEY (id),
    UNIQUE (userid, doctype)
);

/* ---------------------------------------------------
 Structure of table "cashrights"
------------------------------------------------------*/
DROP SEQUENCE cashrights_id_seq;
CREATE SEQUENCE cashrights_id_seq;
DROP TABLE cashrights;
CREATE TABLE cashrights (
    id 		integer 	DEFAULT nextval('cashrights_id_seq'::text) NOT NULL,
    userid 	integer 	DEFAULT 0 NOT NULL,
    regid 	integer 	DEFAULT 0 NOT NULL,
    rights 	integer 	DEFAULT 0 NOT NULL,
    PRIMARY KEY (id),
    UNIQUE (userid, regid)
);
							    
/* ---------------------------------------------------
 Structure of table "cashregs"
------------------------------------------------------*/
DROP SEQUENCE cashregs_id_seq;
CREATE SEQUENCE cashregs_id_seq;
DROP TABLE cashregs;
CREATE TABLE cashregs (
    id 			integer 	DEFAULT nextval('cashregs_id_seq'::text) NOT NULL,
    name 		varchar(255) 	DEFAULT '' NOT NULL,
    description 	text 		DEFAULT '' NOT NULL,
    in_numberplanid 	integer 	DEFAULT 0 NOT NULL,
    out_numberplanid 	integer 	DEFAULT 0 NOT NULL,
    disabled 		smallint	DEFAULT 0 NOT NULL,
    PRIMARY KEY (id),
    UNIQUE (name)
);

/* ---------------------------------------------------
 Structure of table "cashreglog"
------------------------------------------------------*/
DROP SEQUENCE cashreglog_id_seq;
CREATE SEQUENCE cashreglog_id_seq;
DROP TABLE cashreglog;
CREATE TABLE cashreglog (
    id 		integer 	DEFAULT nextval('cashreglog_id_seq'::text) NOT NULL,
    regid 	integer         DEFAULT 0 NOT NULL,
    userid 	integer		DEFAULT 0 NOT NULL,
    time 	integer		DEFAULT 0 NOT NULL,
    value 	numeric(9,2)    DEFAULT 0 NOT NULL,
    snapshot 	numeric(9,2)    DEFAULT 0 NOT NULL,
    description text		DEFAULT '' NOT NULL,
    PRIMARY KEY (id),
    UNIQUE (regid, time)
);

/* ---------------------------------------------------
 Structure of table "ewx_pt_config" (EtherWerX(R))
------------------------------------------------------*/
DROP SEQUENCE ewx_pt_config_id_seq;
CREATE SEQUENCE ewx_pt_config_id_seq;
DROP TABLE ewx_pt_config;
CREATE TABLE ewx_pt_config (
    id 		integer 	DEFAULT nextval('ewx_pt_config_id_seq'::text) NOT NULL,
    nodeid 	integer         DEFAULT 0 NOT NULL,
    name 	varchar(32)     DEFAULT '' NOT NULL,
    mac 	varchar(20)     DEFAULT '' NOT NULL,
    ipaddr 	bigint          DEFAULT 0 NOT NULL,
    passwd 	varchar(32)     DEFAULT '' NOT NULL,
    PRIMARY KEY (id),
    UNIQUE (nodeid)
);

/* ---------------------------------------------------
 Structure of table "ewx_stm_nodes" (EtherWerX(R))
------------------------------------------------------*/
DROP SEQUENCE ewx_stm_nodes_id_seq;
CREATE SEQUENCE ewx_stm_nodes_id_seq;
DROP TABLE ewx_stm_nodes;
CREATE TABLE ewx_stm_nodes (
        id 		integer		DEFAULT nextval('ewx_stm_nodes_id_seq'::text) NOT NULL,
	nodeid 		integer         DEFAULT 0 NOT NULL,
	mac 		varchar(20)     DEFAULT '' NOT NULL,
	ipaddr 		bigint          DEFAULT 0 NOT NULL,
	channelid 	integer       	DEFAULT 0 NOT NULL,
	uprate 		integer         DEFAULT 0 NOT NULL,
	upceil 		integer         DEFAULT 0 NOT NULL,
	downrate 	integer        	DEFAULT 0 NOT NULL,
	downceil 	integer        	DEFAULT 0 NOT NULL,
	halfduplex 	smallint     	DEFAULT 0 NOT NULL,
	PRIMARY KEY (id),
	UNIQUE (nodeid)
);

/* ---------------------------------------------------
 Structure of table "ewx_stm_channels" (EtherWerX(R))
------------------------------------------------------*/
DROP SEQUENCE ewx_stm_channels_id_seq;
CREATE SEQUENCE ewx_stm_channels_id_seq;
DROP TABLE ewx_stm_channels;
CREATE TABLE ewx_stm_channels (
    id 		integer 	DEFAULT nextval('ewx_stm_channels_id_seq'::text) NOT NULL,
    customerid 	integer      	DEFAULT 0 NOT NULL,
    upceil 	integer         DEFAULT 0 NOT NULL,
    downceil 	integer        	DEFAULT 0 NOT NULL,
    PRIMARY KEY (id),
    UNIQUE (customerid)
);

/* ---------------------------------------------------
 Structure of table "ewx_channels" (EtherWerX(R))
------------------------------------------------------*/
DROP SEQUENCE ewx_channels_id_seq;
CREATE SEQUENCE ewx_channels_id_seq;
DROP TABLE ewx_channels;
CREATE TABLE ewx_channels (
    id 		integer 	DEFAULT nextval('ewx_channels_id_seq'::text) NOT NULL,
    name 	varchar(32)     DEFAULT '' NOT NULL,
    upceil 	integer         DEFAULT 0 NOT NULL,
    downceil 	integer        	DEFAULT 0 NOT NULL,
    upceil_n 	integer         DEFAULT NULL,
    downceil_n 	integer        	DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE (name)
);

/* ---------------------------------------------------
 Structure of table "dbinfo"
------------------------------------------------------*/
DROP TABLE dbinfo;
CREATE TABLE dbinfo (
    keytype 	varchar(255) 	DEFAULT '' NOT NULL,
    keyvalue 	varchar(255) 	DEFAULT '' NOT NULL,
    PRIMARY KEY (keytype)
);

/* ---------------------------------------------------
 Structure of table "imessengers"
------------------------------------------------------*/
DROP SEQUENCE imessengers_id_seq;
CREATE SEQUENCE imessengers_id_seq;
DROP TABLE imessengers;
CREATE TABLE imessengers (
    id 		integer         DEFAULT nextval('imessengers_id_seq'::text) NOT NULL,
    customerid 	integer    	DEFAULT 0 NOT NULL,
    uid 	varchar(32)     DEFAULT '' NOT NULL,
    type 	smallint        DEFAULT 0 NOT NULL,
    PRIMARY KEY (id)
);
CREATE INDEX imessengers_customerid_idx ON imessengers (customerid);

/* ---------------------------------------------------
 Structure of table "customercontacts"
------------------------------------------------------*/
DROP SEQUENCE customercontacts_id_seq;
CREATE SEQUENCE customercontacts_id_seq;
DROP TABLE customercontacts;
CREATE TABLE customercontacts (
    id 		integer 	DEFAULT nextval('customercontacts_id_seq'::text) NOT NULL,
    customerid 	integer 	NOT NULL DEFAULT 0,
    name 	varchar(255) 	NOT NULL DEFAULT '',
    phone 	varchar(255) 	NOT NULL DEFAULT '',
    PRIMARY KEY (id)
);
CREATE INDEX customercontacts_customerid_idx ON customercontacts (customerid);
CREATE INDEX customercontacts_phone_idx ON customercontacts (phone);

/* ---------------------------------------------------
 Structure of table "excludedgroups"
------------------------------------------------------*/
DROP SEQUENCE excludedgroups_id_seq;
CREATE SEQUENCE excludedgroups_id_seq;
DROP TABLE excludedgroups;
CREATE TABLE excludedgroups (
	id 		integer NOT NULL DEFAULT nextval('excludedgroups_id_seq'::text),
	customergroupid integer NOT NULL DEFAULT 0,
	userid 		integer NOT NULL DEFAULT 0,
	PRIMARY KEY (id),
	UNIQUE (userid, customergroupid)
);

/* ---------------------------------------------------
 Structure of table "states"
------------------------------------------------------*/
DROP SEQUENCE states_id_seq;
CREATE SEQUENCE states_id_seq;
DROP TABLE states;
CREATE TABLE states (
    	id 		integer 	DEFAULT nextval('states_id_seq'::text) NOT NULL,
	name 		varchar(255) 	NOT NULL DEFAULT '',
	description 	text 		NOT NULL DEFAULT '',
	PRIMARY KEY (id),
	UNIQUE (name)
);

/* ---------------------------------------------------
 Structure of table "countries"
------------------------------------------------------*/
DROP SEQUENCE countries_id_seq;
CREATE SEQUENCE countries_id_seq;
DROP TABLE countries;
CREATE TABLE countries (
	id      integer DEFAULT nextval('countries_id_seq'::text) NOT NULL,
	name    varchar(255) NOT NULL DEFAULT '',
	PRIMARY KEY (id),
	UNIQUE (name)
);

/* ---------------------------------------------------
 Structure of table "zipcodes"
------------------------------------------------------*/
DROP SEQUENCE zipcodes_id_seq;
CREATE SEQUENCE zipcodes_id_seq;
DROP TABLE zipcodes;
CREATE TABLE zipcodes (
    	id 		integer 	DEFAULT nextval('customerassignments_id_seq'::text) NOT NULL,
	zip 		varchar(10) 	NOT NULL DEFAULT '',
	stateid 	integer 	NOT NULL DEFAULT 0,
	PRIMARY KEY (id),
	UNIQUE (zip)
);
CREATE INDEX zipcodes_stateid_idx ON zipcodes (stateid);

/* ---------------------------------------------------
 Structure of table "divisions"
------------------------------------------------------*/
DROP SEQUENCE divisions_id_seq;
CREATE SEQUENCE divisions_id_seq;
DROP TABLE divisions;
CREATE TABLE divisions (
    	id 		integer 	NOT NULL DEFAULT nextval('divisions_id_seq'::text),
	shortname 	varchar(255) 	NOT NULL DEFAULT '',
	name 		text 		NOT NULL DEFAULT '',
	address		varchar(255) 	NOT NULL DEFAULT '',
	city		varchar(255) 	NOT NULL DEFAULT '',
	zip		varchar(255) 	NOT NULL DEFAULT '',
	countryid	integer		NOT NULL DEFAULT 0,
	ten		varchar(16)	NOT NULL DEFAULT '',
	regon		varchar(255)	NOT NULL DEFAULT '',
	account		varchar(48) 	NOT NULL DEFAULT '',
	inv_header 	text		NOT NULL DEFAULT '',
	inv_footer 	text		NOT NULL DEFAULT '',
	inv_author	text		NOT NULL DEFAULT '',
	inv_cplace	text		NOT NULL DEFAULT '',
	inv_paytime	smallint	DEFAULT NULL,
	inv_paytype	varchar(255)	DEFAULT NULL, 
	description 	text		NOT NULL DEFAULT '',
	status 		smallint 	NOT NULL DEFAULT 0,
	PRIMARY KEY (id),
	UNIQUE (shortname)
);

/* ---------------------------------------------------
 Structure of table "voipaccounts"
------------------------------------------------------*/
DROP SEQUENCE voipaccounts_id_seq;
CREATE SEQUENCE voipaccounts_id_seq;
DROP TABLE voipaccounts;
CREATE TABLE voipaccounts (
	id		integer		NOT NULL DEFAULT nextval('voipaccounts_id_seq'::text),
	ownerid		integer		NOT NULL DEFAULT 0,
	login		varchar(255)	NOT NULL DEFAULT '',
	passwd		varchar(255)	NOT NULL DEFAULT '',
	phone		varchar(255)	NOT NULL DEFAULT '',
	creationdate	integer		NOT NULL DEFAULT 0,
	moddate		integer		NOT NULL DEFAULT 0,
	creatorid	integer		NOT NULL DEFAULT 0,
	modid		integer		NOT NULL DEFAULT 0,
	PRIMARY KEY (id)
);

/* ---------------------------------------------------
 Structure of table "messages"
------------------------------------------------------*/
DROP SEQUENCE messages_id_seq;
DROP TABLE messages;
CREATE SEQUENCE messages_id_seq;
CREATE TABLE messages (
        id 	integer 	DEFAULT nextval('messages_id_seq'::text) NOT NULL,
        subject varchar(255)	DEFAULT '' NOT NULL,
	body 	text		DEFAULT '' NOT NULL,
	cdate 	integer		DEFAULT 0 NOT NULL,
	type 	smallint	DEFAULT 0 NOT NULL,
	userid 	integer		DEFAULT 0 NOT NULL,
	sender 	varchar(255) 	DEFAULT NULL,
        PRIMARY KEY (id)
);

CREATE INDEX messages_cdate_idx ON messages (cdate, type);
CREATE INDEX messages_userid_idx ON messages (userid);

/* ---------------------------------------------------
 Structure of table "messageitems"
------------------------------------------------------*/
DROP SEQUENCE messageitems_id_seq;
DROP TABLE messageitems;
CREATE SEQUENCE messageitems_id_seq;
CREATE TABLE messageitems (
        id 		integer 	DEFAULT nextval('messageitems_id_seq'::text) NOT NULL,
	messageid 	integer		DEFAULT 0 NOT NULL,
	customerid 	integer 	DEFAULT 0 NOT NULL,
	destination 	varchar(255) 	DEFAULT '' NOT NULL,
	lastdate 	integer		DEFAULT 0 NOT NULL,
	status 		smallint	DEFAULT 0 NOT NULL,
	error 		text		DEFAULT NULL, 
        PRIMARY KEY (id)
); 

CREATE INDEX messageitems_messageid_idx ON messageitems (messageid);
CREATE INDEX messageitems_customerid_idx ON messageitems (customerid);

/* ---------------------------------------------------
 Structure of table "nastypes"
------------------------------------------------------*/
DROP SEQUENCE nastypes_id_seq;
CREATE SEQUENCE nastypes_id_seq;
DROP TABLE nastypes;
CREATE TABLE nastypes (
    	id 	integer 	DEFAULT nextval('nastypes_id_seq'::text) NOT NULL,
	name 	varchar(255) 	NOT NULL,
	PRIMARY KEY (id),
	UNIQUE (name)
);

/* ---------------------------------------------------
 Structure of table "up_rights" (Userpanel)
------------------------------------------------------*/
DROP SEQUENCE up_rights_id_seq;
CREATE SEQUENCE up_rights_id_seq;
DROP TABLE up_rights;
CREATE TABLE up_rights (
	id integer 		DEFAULT nextval('up_rights_id_seq'::text) NOT NULL,
        module varchar(255) 	DEFAULT 0 NOT NULL,
        name varchar(255) 	DEFAULT 0 NOT NULL,
        description varchar(255) DEFAULT 0,
	setdefault smallint 	DEFAULT 0,
	PRIMARY KEY (id)
);

/* ---------------------------------------------------
 Structure of table "up_rights_assignments" (Userpanel)
------------------------------------------------------*/
DROP SEQUENCE up_rights_assignments_id_seq;
CREATE SEQUENCE up_rights_assignments_id_seq;
DROP TABLE up_rights_assignments;
CREATE TABLE up_rights_assignments (
	id integer 		DEFAULT nextval('up_rights_assignments_id_seq'::text) NOT NULL,
	customerid integer 	DEFAULT 0 NOT NULL,
        rightid integer 	DEFAULT 0 NOT NULL,
	PRIMARY KEY (id),
	UNIQUE (customerid, rightid)
);	  

/* ---------------------------------------------------
 Structure of table "up_customers" (Userpanel)
------------------------------------------------------*/
DROP SEQUENCE up_customers_id_seq;
CREATE SEQUENCE up_customers_id_seq;
DROP TABLE up_customers;
CREATE TABLE up_customers (
	id integer 		DEFAULT nextval('up_customers_id_seq'::text) NOT NULL,
        customerid integer 	DEFAULT 0 NOT NULL,
	lastlogindate integer 	DEFAULT 0 NOT NULL,
	lastloginip varchar(16) DEFAULT '' NOT NULL,
	failedlogindate integer DEFAULT 0 NOT NULL,
	failedloginip varchar(16) DEFAULT '' NOT NULL,
	enabled smallint 	DEFAULT 0 NOT NULL,
	PRIMARY KEY (id)
);	  

/* ---------------------------------------------------
 Structure of table "up_help" (Userpanel)
------------------------------------------------------*/
DROP SEQUENCE up_help_id_seq;
CREATE SEQUENCE up_help_id_seq;
DROP TABLE up_help;
CREATE TABLE up_help (
        id integer 		DEFAULT nextval('up_help_id_seq'::text) NOT NULL,
	reference integer 	DEFAULT 0 NOT NULL,
	title varchar(128) 	DEFAULT 0 NOT NULL,
	body text 		DEFAULT '' NOT NULL,
	PRIMARY KEY (id)
);

/* ---------------------------------------------------
 Structure of table "up_info_changes" (Userpanel)
------------------------------------------------------*/
DROP SEQUENCE up_info_changes_id_seq;
CREATE SEQUENCE up_info_changes_id_seq;
DROP TABLE up_info_changes;
CREATE TABLE up_info_changes (
	id integer 		DEFAULT nextval('up_info_changes_id_seq'::text) NOT NULL,
	customerid integer 	DEFAULT 0 NOT NULL,
	fieldname varchar(255) 	DEFAULT 0 NOT NULL,
	fieldvalue varchar(255) DEFAULT 0 NOT NULL,
	PRIMARY KEY (id)
);

/* ---------------------------------------------------
 Functions and Views
------------------------------------------------------*/
CREATE OR REPLACE FUNCTION lms_current_user() RETURNS integer AS '
SELECT 
CASE 
    WHEN current_setting(''lms.current_user'') = '''' 
    THEN 0 
    ELSE current_setting(''lms.current_user'')::integer
END
' LANGUAGE SQL;

CREATE VIEW customersview AS
SELECT c.* FROM customers c
        WHERE NOT EXISTS (
	        SELECT 1 FROM customerassignments a
	        JOIN excludedgroups e ON (a.customergroupid = e.customergroupid)
	        WHERE e.userid = lms_current_user() AND a.customerid = c.id);

CREATE OR REPLACE FUNCTION int2txt(bigint) RETURNS text AS $$
SELECT $1::text;
$$ LANGUAGE SQL IMMUTABLE;

CREATE VIEW nas AS 
SELECT n.id, inet_ntoa(n.ipaddr) AS nasname, d.shortname, d.nastype AS type,
	d.clients AS ports, d.secret, d.community, d.description 
	FROM nodes n 
	JOIN netdevices d ON (n.netdev = d.id) 
	WHERE n.nas = 1;

/* ---------------------------------------------------
 Data records
------------------------------------------------------*/
INSERT INTO uiconfig (section, var)
	VALUES ('userpanel', 'data_consent_text');
INSERT INTO uiconfig (section, var, value, description, disabled) 
	VALUES ('userpanel', 'disable_transferform', '0', '', 0);
INSERT INTO uiconfig (section, var, value, description, disabled)
	VALUES ('userpanel', 'disable_invoices', '0', '', 0);
INSERT INTO uiconfig (section, var, value, description, disabled)
	VALUES ('userpanel', 'invoice_duplicate', '0', '', 0);
INSERT INTO uiconfig (section, var, value)
	VALUES ('userpanel', 'show_tariffname', '1');
INSERT INTO uiconfig (section, var, value)
	VALUES ('userpanel', 'show_speeds', '1');
INSERT INTO uiconfig (section, var, value, description, disabled)
	VALUES ('userpanel', 'default_queue', '1', '', 0);
INSERT INTO uiconfig (section, var, value, description, disabled)
	VALUES ('userpanel', 'default_userid', '0', '', 0);
INSERT INTO uiconfig (section, var, value, description, disabled)
	VALUES ('userpanel', 'debug_email', '', '', 0);
INSERT INTO uiconfig (section, var, value, description, disabled)
	VALUES ('userpanel', 'lms_url', '', '', 0);
INSERT INTO uiconfig (section, var, value, description, disabled)
	VALUES ('userpanel', 'hide_nodesbox', '0', '', 0);
INSERT INTO uiconfig (section, var, value, description, disabled)
	VALUES ('userpanel', 'logout_url', '', '', 0);
INSERT INTO uiconfig (section, var, value, description, disabled)
	VALUES ('userpanel', 'owner_stats', '0', '', 0);
INSERT INTO up_rights(module, name, description)
	VALUES ('info', 'edit_addr_ack', 'Customer can change address information with admin acknowlegment');
INSERT INTO up_rights(module, name, description)
        VALUES ('info', 'edit_addr', 'Customer can change address information');
INSERT INTO up_rights(module, name, description, setdefault)
        VALUES ('info', 'edit_contact_ack', 'Customer can change contact information with admin acknowlegment', 0);
INSERT INTO up_rights(module, name, description)
        VALUES ('info', 'edit_contact', 'Customer can change contact information');

INSERT INTO countries (name) VALUES ('Lithuania');
INSERT INTO countries (name) VALUES ('Poland');
INSERT INTO countries (name) VALUES ('Romania');
INSERT INTO countries (name) VALUES ('Slovakia');
INSERT INTO countries (name) VALUES ('USA');

INSERT INTO nastypes (name) VALUES ('mikrotik_snmp');
INSERT INTO nastypes (name) VALUES ('cisco');
INSERT INTO nastypes (name) VALUES ('computone');
INSERT INTO nastypes (name) VALUES ('livingston');
INSERT INTO nastypes (name) VALUES ('max40xx');
INSERT INTO nastypes (name) VALUES ('multitech');
INSERT INTO nastypes (name) VALUES ('netserver');
INSERT INTO nastypes (name) VALUES ('pathras');
INSERT INTO nastypes (name) VALUES ('patton');
INSERT INTO nastypes (name) VALUES ('portslave');
INSERT INTO nastypes (name) VALUES ('tc');
INSERT INTO nastypes (name) VALUES ('usrhiper');
INSERT INTO nastypes (name) VALUES ('other');

INSERT INTO dbinfo (keytype, keyvalue) VALUES ('dbversion','2010011300');
