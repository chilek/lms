/* $Id$ */

BEGIN;

/* --------------------------------------------------------
  Structure of table "users"
-------------------------------------------------------- */
DROP SEQUENCE IF EXISTS users_id_seq;
CREATE SEQUENCE users_id_seq;
DROP TABLE IF EXISTS users CASCADE;
CREATE TABLE users (
	id integer DEFAULT nextval('users_id_seq'::text) NOT NULL,
	login varchar(32) 	DEFAULT '' NOT NULL,
	firstname varchar(64)   DEFAULT '' NOT NULL,
	lastname varchar(64) DEFAULT '' NOT NULL,
	email varchar(255) 	DEFAULT '' NOT NULL,
	phone varchar(32)   DEFAULT NULL,
	position varchar(255) 	DEFAULT '' NOT NULL,
	rights text 	DEFAULT '' NOT NULL,
	hosts varchar(255) 	DEFAULT '' NOT NULL,
	passwd varchar(255) 	DEFAULT '' NOT NULL,
	ntype smallint      DEFAULT NULL,
	lastlogindate integer 	DEFAULT 0  NOT NULL,
	lastloginip varchar(16) DEFAULT '' NOT NULL,
	failedlogindate integer DEFAULT 0  NOT NULL,
	failedloginip varchar(16) DEFAULT '' NOT NULL,
	deleted smallint	DEFAULT 0 NOT NULL,
	passwdexpiration integer DEFAULT 0 NOT NULL,
	passwdlastchange integer DEFAULT 0 NOT NULL,
	access smallint DEFAULT 1 NOT NULL,
	accessfrom integer DEFAULT 0 NOT NULL,
	accessto integer DEFAULT 0 NOT NULL,
	settings text NOT NULL DEFAULT '',
	persistentsettings text NOT NULL DEFAULT '',
	PRIMARY KEY (id),
	UNIQUE (login)
);

/* ---------------------------------------------------
 Structure of table "countries"
------------------------------------------------------*/
DROP SEQUENCE IF EXISTS countries_id_seq;
CREATE SEQUENCE countries_id_seq;
DROP TABLE IF EXISTS countries CASCADE;
CREATE TABLE countries (
	id      integer DEFAULT nextval('countries_id_seq'::text) NOT NULL,
	name    varchar(255) NOT NULL DEFAULT '',
	PRIMARY KEY (id),
	UNIQUE (name)
);

/* --------------------------------------------------------
Structure of table "location_states"
-------------------------------------------------------- */
DROP SEQUENCE IF EXISTS location_states_id_seq;
CREATE SEQUENCE location_states_id_seq;
DROP TABLE IF EXISTS location_states CASCADE;
CREATE TABLE location_states (
	id integer          DEFAULT nextval('location_states_id_seq'::text) NOT NULL,
	ident varchar(8)    NOT NULL, -- TERYT: WOJ
	name varchar(64)    NOT NULL, -- TERYT: NAZWA
	PRIMARY KEY (id),
	UNIQUE (name)
);

/* --------------------------------------------------------
  Structure of table "location_districts"
-------------------------------------------------------- */
DROP SEQUENCE IF EXISTS location_districts_id_seq;
CREATE SEQUENCE location_districts_id_seq;
DROP TABLE IF EXISTS location_districts CASCADE;
CREATE TABLE location_districts (
	id integer          DEFAULT nextval('location_districts_id_seq'::text) NOT NULL,
	name varchar(64)    NOT NULL, --TERYT: NAZWA
	ident varchar(8)    NOT NULL, --TERYT: POW
	stateid integer     NOT NULL  --TERYT: WOJ
		REFERENCES location_states (id) ON DELETE CASCADE ON UPDATE CASCADE,
	PRIMARY KEY (id),
	UNIQUE (stateid, name)
);

/* --------------------------------------------------------
  Structure of table "location_boroughs"
-------------------------------------------------------- */
DROP SEQUENCE IF EXISTS location_boroughs_id_seq;
CREATE SEQUENCE location_boroughs_id_seq;
DROP TABLE IF EXISTS location_boroughs CASCADE;
CREATE TABLE location_boroughs (
	id integer          DEFAULT nextval('location_boroughs_id_seq'::text) NOT NULL,
	name varchar(64)    NOT NULL, -- TERYT: NAZWA
	ident varchar(8)    NOT NULL, -- TERYT: GMI
	districtid integer  NOT NULL
		REFERENCES location_districts (id) ON DELETE CASCADE ON UPDATE CASCADE,
	type smallint       NOT NULL, -- TERYT: RODZ
	PRIMARY KEY (id),
	UNIQUE (districtid, name, type)
);

/* --------------------------------------------------------
  Structure of table "location_cities"
-------------------------------------------------------- */
DROP SEQUENCE IF EXISTS location_cities_id_seq;
CREATE SEQUENCE location_cities_id_seq;
DROP TABLE IF EXISTS location_cities CASCADE;
CREATE TABLE location_cities (
	id integer          DEFAULT nextval('location_cities_id_seq'::text) NOT NULL,
	ident varchar(8)    NOT NULL, -- TERYT: SYM / SYMPOD
	name varchar(64)    NOT NULL, -- TERYT: NAZWA
	cityid integer      DEFAULT NULL,
	boroughid integer   DEFAULT NULL
		REFERENCES location_boroughs (id) ON DELETE CASCADE ON UPDATE CASCADE,
	PRIMARY KEY (id)
);
CREATE INDEX location_cities_cityid ON location_cities (cityid);
CREATE INDEX location_cities_boroughid ON location_cities (boroughid, name);

/* --------------------------------------------------------
  Structure of table "location_street_types"
-------------------------------------------------------- */
DROP SEQUENCE IF EXISTS location_street_types_id_seq;
CREATE SEQUENCE location_street_types_id_seq;
DROP TABLE IF EXISTS location_street_types CASCADE;
CREATE TABLE location_street_types (
	id integer          DEFAULT nextval('location_street_types_id_seq'::text) NOT NULL,
	name varchar(8)     NOT NULL, -- TERYT: CECHA
	PRIMARY KEY (id)
);

/* --------------------------------------------------------
  Structure of table "location_streets"
-------------------------------------------------------- */
DROP SEQUENCE IF EXISTS location_streets_id_seq;
CREATE SEQUENCE location_streets_id_seq;
DROP TABLE IF EXISTS location_streets CASCADE;
CREATE TABLE location_streets (
	id integer          DEFAULT nextval('location_streets_id_seq'::text) NOT NULL,
	name varchar(128)   NOT NULL, -- TERYT: NAZWA_1
	name2 varchar(128)  DEFAULT NULL, -- TERYT: NAZWA_2
	ident varchar(8)    NOT NULL, -- TERYT: SYM_UL
	typeid integer      DEFAULT NULL
		REFERENCES location_street_types (id) ON DELETE SET NULL ON UPDATE CASCADE,
	cityid integer      NOT NULL
		REFERENCES location_cities (id) ON DELETE CASCADE ON UPDATE CASCADE,
	PRIMARY KEY (id),
	UNIQUE (cityid, name, ident)
);

/* --------------------------------------------------------
  Structure of table "location_buildings"
-------------------------------------------------------- */
DROP SEQUENCE IF EXISTS location_buildings_id_seq;
CREATE SEQUENCE location_buildings_id_seq;
DROP TABLE IF EXISTS location_buildings CASCADE;
CREATE TABLE location_buildings (
	id           integer DEFAULT nextval('location_buildings_id_seq'::text) NOT NULL,
	city_id      integer NOT NULL
		CONSTRAINT location_cities_city_id_fkey REFERENCES location_cities (id) ON DELETE CASCADE ON UPDATE CASCADE,
	street_id    integer NULL
		CONSTRAINT location_cities_street_id_fkey REFERENCES location_streets (id) ON DELETE CASCADE ON UPDATE CASCADE,
	building_num varchar(20) NULL,
	latitude     numeric(10,6) NULL,
	longitude    numeric(10,6) NULL,
	updated      smallint DEFAULT 0,
	PRIMARY KEY (id)
);
DROP INDEX IF EXISTS location_cityid_index;
CREATE INDEX location_cityid_index ON location_buildings (city_id);

/* ---------------------------------------------------
 Structure of table "addresses"
------------------------------------------------------*/
DROP SEQUENCE IF EXISTS addresses_id_seq;
CREATE SEQUENCE addresses_id_seq;
DROP TABLE IF EXISTS addresses CASCADE;
CREATE TABLE addresses (
	id         integer DEFAULT nextval('addresses_id_seq'::text) NOT NULL,
	name       text NULL,
	state      varchar(64) NULL,
	state_id   integer REFERENCES location_states (id) ON DELETE SET NULL ON UPDATE CASCADE,
	city       varchar(100) NULL,
	city_id    integer REFERENCES location_cities (id) ON DELETE SET NULL ON UPDATE CASCADE,
	postoffice varchar(32) DEFAULT NULL,
	street     varchar(255) NULL,
	street_id  integer REFERENCES location_streets (id) ON DELETE SET NULL ON UPDATE CASCADE,
	zip        varchar(10) NULL,
	country_id integer REFERENCES countries (id) ON DELETE SET NULL ON UPDATE CASCADE,
	house      varchar(20) NULL,
	flat       varchar(20) NULL,
	PRIMARY KEY (id)
);

/* ---------------------------------------------------
 Structure of table "divisions"
------------------------------------------------------*/
DROP SEQUENCE IF EXISTS divisions_id_seq;
CREATE SEQUENCE divisions_id_seq;
DROP TABLE IF EXISTS divisions CASCADE;
CREATE TABLE divisions (
	id 		integer 	NOT NULL DEFAULT nextval('divisions_id_seq'::text),
	shortname 	varchar(255) 	NOT NULL DEFAULT '',
	name 		text 		NOT NULL DEFAULT '',
	ten		varchar(50)	NOT NULL DEFAULT '',
	regon		varchar(255)	NOT NULL DEFAULT '',
	rbe			varchar(255)	NOT NULL DEFAULT '',
	rbename		varchar(255)	NOT NULL DEFAULT '',
	telecomnumber varchar(255)    NOT NULL DEFAULT '',
	account		varchar(48) 	NOT NULL DEFAULT '',
	inv_header 	text		NOT NULL DEFAULT '',
	inv_footer 	text		NOT NULL DEFAULT '',
	inv_author	text		NOT NULL DEFAULT '',
	inv_cplace	text		NOT NULL DEFAULT '',
	inv_paytime	smallint	DEFAULT NULL,
	inv_paytype	smallint	DEFAULT NULL,
	description 	text		NOT NULL DEFAULT '',
	status 		smallint 	NOT NULL DEFAULT 0,
	tax_office_code varchar(8) DEFAULT NULL,
	address_id integer DEFAULT NULL
		REFERENCES addresses (id) ON DELETE SET NULL ON UPDATE CASCADE,
	PRIMARY KEY (id),
	UNIQUE (shortname)
);

/* --------------------------------------------------------
  Structure of table "customers" (customers)
-------------------------------------------------------- */
DROP SEQUENCE IF EXISTS customers_id_seq;
CREATE SEQUENCE customers_id_seq;
DROP TABLE IF EXISTS customers CASCADE;
CREATE TABLE customers (
	id integer DEFAULT nextval('customers_id_seq'::text) NOT NULL,
	extid varchar(32) DEFAULT '' NOT NULL,
	lastname varchar(128)	DEFAULT '' NOT NULL,
	name varchar(128)	DEFAULT '' NOT NULL,
	status smallint 	DEFAULT 0 NOT NULL,
	type smallint		DEFAULT 0 NOT NULL,
	ten varchar(50) 	DEFAULT '' NOT NULL,
	ssn varchar(50) 	DEFAULT '' NOT NULL,
	regon varchar(255) 	DEFAULT '' NOT NULL,
	rbe varchar(255) 	DEFAULT '' NOT NULL, -- EDG/KRS
	rbename varchar(255)	DEFAULT '' NOT NULL,
	icn varchar(255) 	DEFAULT '' NOT NULL, -- dow.os.
	info text		DEFAULT '' NOT NULL,
	notes text		DEFAULT '' NOT NULL,
	creationdate integer 	DEFAULT 0 NOT NULL,
	moddate integer 	DEFAULT 0 NOT NULL,
	creatorid integer 	DEFAULT NULL
		CONSTRAINT customers_creatorid_fkey REFERENCES users (id) ON DELETE SET NULL ON UPDATE CASCADE,
	modid integer 		DEFAULT NULL
		CONSTRAINT customers_modid_fkey REFERENCES users (id) ON DELETE SET NULL ON UPDATE CASCADE,
	deleted smallint 	DEFAULT 0 NOT NULL,
	message text		DEFAULT '' NOT NULL,
	pin varchar(255)		DEFAULT 0 NOT NULL,
	cutoffstop integer	DEFAULT 0 NOT NULL,
	consentdate integer	DEFAULT 0 NOT NULL,
	einvoice smallint 	DEFAULT NULL,
	invoicenotice smallint 	DEFAULT NULL,
	mailingnotice smallint 	DEFAULT NULL,
	divisionid integer	DEFAULT NULL
		CONSTRAINT customers_divisionid_fkey REFERENCES divisions (id) ON DELETE SET NULL ON UPDATE CASCADE,
    paytime smallint 	DEFAULT -1 NOT NULL,
    paytype smallint 	DEFAULT NULL,
	PRIMARY KEY (id)
);

CREATE INDEX customers_lastname_idx ON customers (lastname, name);

/* --------------------------------------------------------
  Structure of table "numberplans"
-------------------------------------------------------- */
DROP SEQUENCE IF EXISTS numberplans_id_seq;
CREATE SEQUENCE numberplans_id_seq;
DROP TABLE IF EXISTS numberplans CASCADE;
CREATE TABLE numberplans (
	id integer DEFAULT nextval('numberplans_id_seq'::text) NOT NULL,
	template varchar(255) DEFAULT '' NOT NULL,
	period smallint DEFAULT 0 NOT NULL,
	doctype integer DEFAULT 0 NOT NULL,
	isdefault smallint DEFAULT 0 NOT NULL,
	PRIMARY KEY (id)
);

/* ---------------------------------------------------
 Structure of table "states"
------------------------------------------------------*/
DROP SEQUENCE IF EXISTS states_id_seq;
CREATE SEQUENCE states_id_seq;
DROP TABLE IF EXISTS states CASCADE;
CREATE TABLE states (
	id 		integer 	DEFAULT nextval('states_id_seq'::text) NOT NULL,
	name 		varchar(255) 	NOT NULL DEFAULT '',
	description 	text 		NOT NULL DEFAULT '',
	PRIMARY KEY (id),
	UNIQUE (name)
);

/* ---------------------------------------------------
 Structure of table "zipcodes"
------------------------------------------------------*/
DROP SEQUENCE IF EXISTS zipcodes_id_seq;
CREATE SEQUENCE zipcodes_id_seq;
DROP TABLE IF EXISTS zipcodes CASCADE;
CREATE TABLE zipcodes (
    	id 		integer 	DEFAULT nextval('zipcodes_id_seq'::text) NOT NULL,
	zip 		varchar(10) 	NOT NULL DEFAULT '',
	stateid 	integer 	DEFAULT NULL
		CONSTRAINT zipcodes_stateid_fkey REFERENCES states (id) ON DELETE SET NULL ON UPDATE CASCADE,
	PRIMARY KEY (id),
	UNIQUE (zip)
);
CREATE INDEX zipcodes_stateid_idx ON zipcodes (stateid);

/* ---------------------------------------------------
 Structure of table "customer_addresses"
------------------------------------------------------*/
DROP SEQUENCE IF EXISTS customer_addresses_id_seq;
CREATE SEQUENCE customer_addresses_id_seq;
DROP TABLE IF EXISTS customer_addresses CASCADE;
CREATE TABLE customer_addresses (
    id          integer DEFAULT nextval('customer_addresses_id_seq'::text) NOT NULL,
    customer_id integer
    	CONSTRAINT customer_addresses_customer_id_fkey REFERENCES customers (id) ON DELETE CASCADE ON UPDATE CASCADE,
    address_id  integer REFERENCES addresses (id) ON DELETE CASCADE ON UPDATE CASCADE,
    type        smallint NULL,
    PRIMARY KEY (id),
    UNIQUE(customer_id, address_id)
);

/* --------------------------------------------------------
  Structure of table "documents"
-------------------------------------------------------- */
DROP SEQUENCE IF EXISTS documents_id_seq;
CREATE SEQUENCE documents_id_seq;
DROP TABLE IF EXISTS documents CASCADE;
CREATE TABLE documents (
	id integer DEFAULT nextval('documents_id_seq'::text) NOT NULL,
	type smallint		DEFAULT 0 NOT NULL,
	number integer		DEFAULT 0 NOT NULL,
	numberplanid integer	DEFAULT NULL
		CONSTRAINT documents_numberplanid_fkey REFERENCES numberplans (id) ON DELETE SET NULL ON UPDATE CASCADE,
	extnumber varchar(255)	DEFAULT '' NOT NULL,
	cdate integer		DEFAULT 0 NOT NULL,
	sdate integer		DEFAULT 0 NOT NULL,
	customerid integer	DEFAULT NULL
		CONSTRAINT documents_customerid_fkey REFERENCES customers (id) ON DELETE SET NULL ON UPDATE CASCADE,
	userid integer		DEFAULT NULL
		CONSTRAINT documents_userid_fkey REFERENCES users (id) ON DELETE SET NULL ON UPDATE CASCADE,
	divisionid integer	DEFAULT NULL
		CONSTRAINT documents_divisionid_fkey REFERENCES divisions (id) ON DELETE SET NULL ON UPDATE CASCADE,
	name varchar(255)	DEFAULT '' NOT NULL,
	address varchar(255)	DEFAULT '' NOT NULL,
	zip varchar(10)		NULL DEFAULT NULL,
	city varchar(32)	NULL DEFAULT NULL,
	countryid integer	DEFAULT NULL
		CONSTRAINT documents_countryid_fkey REFERENCES countries (id) ON DELETE SET NULL ON UPDATE CASCADE,
	ten varchar(50)		DEFAULT '' NOT NULL,
	ssn varchar(50)		DEFAULT '' NOT NULL,
	paytime smallint	DEFAULT 0 NOT NULL,
	paytype smallint	DEFAULT NULL,
	closed smallint		DEFAULT 0 NOT NULL,
	reference integer	DEFAULT NULL
		CONSTRAINT documents_reference_fkey REFERENCES documents (id) ON DELETE SET NULL ON UPDATE CASCADE,
	reason varchar(255)	DEFAULT '' NOT NULL,
	div_name text		DEFAULT '' NOT NULL,
	div_shortname text	DEFAULT '' NOT NULL,
	div_address varchar(255) DEFAULT '' NOT NULL,
	div_city varchar(255)	DEFAULT '' NOT NULL,
	div_zip varchar(255)	DEFAULT '' NOT NULL,
	div_countryid integer	DEFAULT NULL
		CONSTRAINT documents_div_countryid_fkey REFERENCES countries (id) ON DELETE SET NULL ON UPDATE CASCADE,
	div_ten varchar(255)	DEFAULT '' NOT NULL,
	div_regon varchar(255)	DEFAULT '' NOT NULL,
	div_account varchar(48)	DEFAULT '' NOT NULL,
	div_inv_header text	DEFAULT '' NOT NULL,
	div_inv_footer text	DEFAULT '' NOT NULL,
	div_inv_author text	DEFAULT '' NOT NULL,
	div_inv_cplace text	DEFAULT '' NOT NULL,
	fullnumber varchar(50)	DEFAULT NULL,
	cancelled smallint	DEFAULT 0 NOT NULL,
	published smallint	DEFAULT 0 NOT NULL,
	comment text DEFAULT NULL,
	cuserid integer		DEFAULT NULL
		CONSTRAINT documents_cuserid_fkey REFERENCES users (id) ON DELETE SET NULL ON UPDATE CASCADE,
	recipient_address_id integer DEFAULT NULL
		REFERENCES addresses (id) ON DELETE SET NULL ON UPDATE CASCADE,
	post_address_id integer DEFAULT NULL
		REFERENCES addresses (id) ON DELETE SET NULL ON UPDATE CASCADE,
	template varchar(255) DEFAULT NULL,
	commitflags smallint DEFAULT 0 NOT NULL,
	archived smallint DEFAULT 0 NOT NULL,
	auserid integer DEFAULT NULL
		CONSTRAINT documents_auserid_fkey REFERENCES users (id) ON DELETE SET NULL ON UPDATE CASCADE,
	adate integer DEFAULT 0 NOT NULL,
	PRIMARY KEY (id)
);
CREATE INDEX documents_cdate_idx ON documents(cdate);
CREATE INDEX documents_numberplanid_idx ON documents(numberplanid);
CREATE INDEX documents_customerid_idx ON documents(customerid);
CREATE INDEX documents_closed_idx ON documents(closed);
CREATE INDEX documents_reference_idx ON documents(reference);

/* --------------------------------------------------------
  Structure of table "documentcontents"
-------------------------------------------------------- */
DROP TABLE IF EXISTS documentcontents CASCADE;
CREATE TABLE documentcontents (
	docid integer		NOT NULL
		REFERENCES documents (id) ON DELETE CASCADE ON UPDATE CASCADE,
	title text 		DEFAULT '' NOT NULL,
	fromdate integer 	DEFAULT 0 NOT NULL,
	todate integer 		DEFAULT 0 NOT NULL,
	description text 	DEFAULT '' NOT NULL,
	UNIQUE (docid)
);
CREATE INDEX documentcontents_todate_idx ON documentcontents (todate);
CREATE INDEX documentcontents_fromdate_idx ON documentcontents (fromdate);

/* --------------------------------------------------------
  Structure of table "documentattachments"
-------------------------------------------------------- */
DROP SEQUENCE IF EXISTS documentattachments_id_seq;
CREATE SEQUENCE documentattachments_id_seq;
DROP TABLE IF EXISTS documentattachments CASCADE;
CREATE TABLE documentattachments (
	id integer DEFAULT nextval('documentattachments_id_seq'::text) NOT NULL,
	docid integer NOT NULL
		REFERENCES documents (id) ON DELETE CASCADE ON UPDATE CASCADE,
	filename varchar(255) NOT NULL,
	contenttype varchar(255) NOT NULL,
	md5sum varchar(32) NOT NULL,
	main smallint DEFAULT 1 NOT NULL,
	PRIMARY KEY (id),
	UNIQUE (docid, md5sum)
);
CREATE INDEX documentattachments_md5sum_idx ON documentattachments (md5sum);

/* ---------------------------------------------------
 Structure of table "cashregs"
------------------------------------------------------*/
DROP SEQUENCE IF EXISTS cashregs_id_seq;
CREATE SEQUENCE cashregs_id_seq;
DROP TABLE IF EXISTS cashregs CASCADE;
CREATE TABLE cashregs (
	id 			integer 	DEFAULT nextval('cashregs_id_seq'::text) NOT NULL,
	name 		varchar(255) 	DEFAULT '' NOT NULL,
	description 	text 		DEFAULT '' NOT NULL,
	in_numberplanid 	integer 	DEFAULT NULL
		CONSTRAINT cashregs_in_numberplanid_fkey REFERENCES numberplans (id) ON DELETE SET NULL ON UPDATE CASCADE,
	out_numberplanid 	integer 	DEFAULT NULL
		CONSTRAINT cashregs_out_numberplanid_fkey REFERENCES numberplans (id) ON DELETE SET NULL ON UPDATE CASCADE,
	disabled 		smallint	DEFAULT 0 NOT NULL,
	PRIMARY KEY (id),
	UNIQUE (name)
);

/* --------------------------------------------------------
  Structure of table "receiptcontents"
-------------------------------------------------------- */
DROP TABLE IF EXISTS receiptcontents CASCADE;
CREATE TABLE receiptcontents (
	docid integer		NOT NULL
		CONSTRAINT receiptcontents_docid_fk REFERENCES documents (id) ON DELETE CASCADE ON UPDATE CASCADE,
	itemid smallint		DEFAULT 0 NOT NULL,
	value numeric(9,2)	DEFAULT 0 NOT NULL,
	regid integer		DEFAULT NULL
		CONSTRAINT receiptcontents_regid_fkey REFERENCES cashregs (id) ON DELETE SET NULL ON UPDATE CASCADE,
	description text 	DEFAULT '' NOT NULL
);
CREATE INDEX receiptcontents_docid_idx ON receiptcontents(docid);
CREATE INDEX receiptcontents_regid_idx ON receiptcontents(regid);

/* --------------------------------------------------------
  Structure of table "taxes"
-------------------------------------------------------- */
DROP SEQUENCE IF EXISTS taxes_id_seq;
CREATE SEQUENCE taxes_id_seq;
DROP TABLE IF EXISTS taxes CASCADE;
CREATE TABLE taxes (
	id integer DEFAULT nextval('taxes_id_seq'::text) NOT NULL,
	value numeric(4,2) DEFAULT 0 NOT NULL,
	taxed smallint DEFAULT 0 NOT NULL,
	label varchar(16) DEFAULT '' NOT NULL,
	validfrom integer DEFAULT 0 NOT NULL,
	validto integer DEFAULT 0 NOT NULL,
	reversecharge smallint DEFAULT 0 NOT NULL,
	PRIMARY KEY (id)
);

/* ---------------------------------------------------
 Structure of table "voipaccounts"
------------------------------------------------------*/
DROP SEQUENCE IF EXISTS voipaccounts_id_seq;
CREATE SEQUENCE voipaccounts_id_seq;
DROP TABLE IF EXISTS voipaccounts CASCADE;
CREATE TABLE voipaccounts (
	id		integer		NOT NULL DEFAULT nextval('voipaccounts_id_seq'::text),
	ownerid		integer		DEFAULT NULL
		CONSTRAINT voipaccounts_ownerid_fkey REFERENCES customers (id) ON DELETE CASCADE ON UPDATE CASCADE,
	login		varchar(255)	NOT NULL DEFAULT '',
	passwd		varchar(255)	NOT NULL DEFAULT '',
	access      smallint        NOT NULL DEFAULT 1,
	creationdate	integer		NOT NULL DEFAULT 0,
	moddate		integer		NOT NULL DEFAULT 0,
	creatorid	integer		DEFAULT NULL
		CONSTRAINT voipaccounts_creatorid_fkey REFERENCES users (id) ON DELETE SET NULL ON UPDATE CASCADE,
	modid		integer		DEFAULT NULL
		CONSTRAINT voipaccounts_modid_fkey REFERENCES users (id) ON DELETE SET NULL ON UPDATE CASCADE,
	balance		numeric(12,5) NOT NULL DEFAULT 0,
	flags		smallint NOT NULL DEFAULT 0,
	cost_limit	numeric(12,2) NULL DEFAULT NULL,
	address_id integer
		REFERENCES addresses (id) ON DELETE SET NULL ON UPDATE CASCADE,
	PRIMARY KEY (id)
);

/* --------------------------------------------------------
  Structure of table "voip_rule_groups"
-------------------------------------------------------- */
DROP SEQUENCE IF EXISTS voip_rule_groups_id_seq CASCADE;
CREATE SEQUENCE voip_rule_groups_id_seq;
DROP TABLE IF EXISTS voip_rule_groups CASCADE;
CREATE TABLE voip_rule_groups (
	id integer DEFAULT nextval('voip_rule_groups_id_seq'::text) NOT NULL,
	name text NOT NULL,
	description text NULL,
	PRIMARY KEY (id)
);

/* --------------------------------------------------------
  Structure of table "voip_prefix_groups"
-------------------------------------------------------- */
DROP SEQUENCE IF EXISTS voip_prefix_groups_id_seq;
CREATE SEQUENCE voip_prefix_groups_id_seq;
DROP TABLE IF EXISTS voip_prefix_groups CASCADE;
CREATE TABLE voip_prefix_groups (
	id integer DEFAULT nextval('voip_prefix_groups_id_seq'::text) NOT NULL,
	name text NOT NULL,
	description text NULL,
	PRIMARY KEY (id)
);

/* --------------------------------------------------------
  Structure of table "voip_rules"
-------------------------------------------------------- */
DROP SEQUENCE IF EXISTS voip_rules_id_seq CASCADE;
CREATE SEQUENCE voip_rules_id_seq;
DROP TABLE IF EXISTS voip_rules CASCADE;
CREATE TABLE voip_rules (
	id integer DEFAULT nextval('voip_rules_id_seq'::text) NOT NULL,
	rule_group_id integer NOT NULL
		REFERENCES voip_rule_groups (id) ON DELETE CASCADE ON UPDATE CASCADE,
	prefix_group_id integer NOT NULL
		REFERENCES voip_prefix_groups (id) ON DELETE CASCADE ON UPDATE CASCADE,
	settings text NULL,
	PRIMARY KEY (id)
);

/* --------------------------------------------------------
  Structure of table "voip_tariffs"
-------------------------------------------------------- */
DROP SEQUENCE IF EXISTS voip_tariffs_id_seq;
CREATE SEQUENCE voip_tariffs_id_seq;
DROP TABLE IF EXISTS voip_tariffs CASCADE;
CREATE TABLE voip_tariffs (
	id          integer      DEFAULT nextval('voip_tariffs_id_seq'::text) NOT NULL,
	name        varchar(100) NOT NULL,
	description text         NULL DEFAULT NULL,
	PRIMARY KEY (id)
);

/* --------------------------------------------------------
  Structure of table "voip_rule_states"
-------------------------------------------------------- */
DROP SEQUENCE IF EXISTS voip_rule_states_id_seq;
CREATE SEQUENCE voip_rule_states_id_seq;
DROP TABLE IF EXISTS voip_rule_states CASCADE;
CREATE TABLE voip_rule_states (
	id              integer DEFAULT nextval('voip_rule_states_id_seq'::text) NOT NULL,
	voip_account_id integer NOT NULL DEFAULT NULL
		REFERENCES voipaccounts (id) ON DELETE CASCADE ON UPDATE CASCADE,
	rule_id         integer NOT NULL DEFAULT NULL
		REFERENCES voip_rules (id) ON DELETE CASCADE ON UPDATE CASCADE,
	units_left      integer NULL DEFAULT NULL,
	PRIMARY KEY(id),
	UNIQUE(voip_account_id, rule_id)
);

/* --------------------------------------------------------
  Structure of table "voip_prefixes"
-------------------------------------------------------- */
DROP SEQUENCE IF EXISTS voip_prefixes_id_seq;
CREATE SEQUENCE voip_prefixes_id_seq;
DROP TABLE IF EXISTS voip_prefixes CASCADE;
CREATE TABLE voip_prefixes (
	id integer DEFAULT nextval('voip_prefixes_id_seq'::text) NOT NULL,
	prefix varchar(30) NOT NULL,
	groupid integer NOT NULL
		REFERENCES voip_prefix_groups(id) ON DELETE CASCADE ON UPDATE CASCADE,
	PRIMARY KEY (id),
	UNIQUE (prefix, groupid)
);

/* --------------------------------------------------------
  Structure of table "voip_cdr"
-------------------------------------------------------- */
DROP SEQUENCE IF EXISTS voip_cdr_id_seq;
CREATE SEQUENCE voip_cdr_id_seq;
DROP TABLE IF EXISTS voip_cdr CASCADE;
CREATE TABLE voip_cdr (
	id integer DEFAULT nextval('voip_cdr_id_seq'::text) NOT NULL,
	caller varchar(20) NOT NULL,
	callee varchar(20) NOT NULL,
	call_start_time integer NOT NULL,
	totaltime integer NOT NULL,
	billedtime integer NOT NULL,
	price numeric(12,5) NOT NULL,
	status smallint NOT NULL,
	type smallint NOT NULL,
	callervoipaccountid integer NULL
		REFERENCES voipaccounts(id) ON DELETE SET NULL ON UPDATE CASCADE,
	calleevoipaccountid integer NULL
		REFERENCES voipaccounts(id) ON DELETE SET NULL ON UPDATE CASCADE,
	caller_flags smallint NOT NULL DEFAULT 0,
	callee_flags smallint NOT NULL DEFAULT 0,
	caller_prefix_group varchar(100) NULL,
	callee_prefix_group varchar(100) NULL,
	uniqueid varchar(20) NOT NULL,
	PRIMARY KEY (id),
	UNIQUE (uniqueid)
);

/* --------------------------------------------------------
  Structure of table "voip_price_groups"
-------------------------------------------------------- */
DROP SEQUENCE IF EXISTS voip_price_groups_id_seq;
CREATE SEQUENCE voip_price_groups_id_seq;
DROP TABLE IF EXISTS voip_price_groups CASCADE;
CREATE TABLE voip_price_groups (
	id              integer       DEFAULT nextval('voip_price_groups_id_seq'::text) NOT NULL,
	voip_tariff_id  integer       NOT NULL
		REFERENCES voip_tariffs (id) ON DELETE CASCADE ON UPDATE CASCADE,
	prefix_group_id integer       NOT NULL
		REFERENCES voip_prefix_groups (id) ON DELETE CASCADE ON UPDATE CASCADE,
	price           numeric(12,5) DEFAULT 0 NOT NULL,
	unitsize        smallint      DEFAULT 0 NOT NULL,
	PRIMARY KEY (id)
);

/* --------------------------------------------------------
  Structure of table "tariffs"
-------------------------------------------------------- */
DROP SEQUENCE IF EXISTS tariffs_id_seq;
CREATE SEQUENCE tariffs_id_seq;
DROP TABLE IF EXISTS tariffs CASCADE;
CREATE TABLE tariffs (
	id integer DEFAULT nextval('tariffs_id_seq'::text) NOT NULL,
	name varchar(255) 	DEFAULT '' NOT NULL,
	type smallint		DEFAULT 1 NOT NULL,
	value numeric(9,2) 	DEFAULT 0 NOT NULL,
	period smallint 	DEFAULT NULL,
	taxid integer 		NOT NULL
		CONSTRAINT tariffs_taxid_fkey REFERENCES taxes (id) ON DELETE CASCADE ON UPDATE CASCADE,
	numberplanid integer DEFAULT NULL
		REFERENCES numberplans (id) ON DELETE CASCADE ON UPDATE CASCADE,
	prodid varchar(255) 	DEFAULT '' NOT NULL,
	uprate integer		DEFAULT 0 NOT NULL,
	upceil integer		DEFAULT 0 NOT NULL,
	downrate integer	DEFAULT 0 NOT NULL,
	downceil integer	DEFAULT 0 NOT NULL,
	down_burst_time smallint DEFAULT 0 NOT NULL,
	down_burst_limit integer DEFAULT 0 NOT NULL,
	down_burst_threshold integer DEFAULT 0 NOT NULL,
	up_burst_time smallint DEFAULT 0 NOT NULL,
	up_burst_limit integer DEFAULT 0 NOT NULL,
	up_burst_threshold integer DEFAULT 0 NOT NULL,
	climit integer		DEFAULT 0 NOT NULL,
	plimit integer		DEFAULT 0 NOT NULL,
	dlimit integer		DEFAULT 0 NOT NULL,
	uprate_n integer        DEFAULT NULL,
	upceil_n integer        DEFAULT NULL,
	downrate_n integer      DEFAULT NULL,
	downceil_n integer      DEFAULT NULL,
	down_burst_time_n smallint DEFAULT NULL,
	down_burst_limit_n integer DEFAULT NULL,
	down_burst_threshold_n integer DEFAULT NULL,
	up_burst_time_n smallint DEFAULT NULL,
	up_burst_limit_n integer DEFAULT NULL,
	up_burst_threshold_n integer DEFAULT NULL,
	climit_n integer        DEFAULT NULL,
	plimit_n integer        DEFAULT NULL,
	domain_limit integer	DEFAULT NULL,
	alias_limit integer	DEFAULT NULL,
	sh_limit integer	DEFAULT NULL,
	www_limit integer	DEFAULT NULL,
	mail_limit integer	DEFAULT NULL,
	ftp_limit integer	DEFAULT NULL,
	sql_limit integer	DEFAULT NULL,
	cloud_limit integer	DEFAULT NULL,
	quota_sh_limit integer	DEFAULT NULL,
	quota_www_limit integer	DEFAULT NULL,
	quota_mail_limit integer DEFAULT NULL,
	quota_ftp_limit integer	DEFAULT NULL,
	quota_sql_limit integer	DEFAULT NULL,
	quota_cloud_limit integer DEFAULT NULL,
	description text	DEFAULT '' NOT NULL,
	disabled smallint 	DEFAULT 0 NOT NULL,
	voip_tariff_id integer      DEFAULT NULL
		REFERENCES voip_tariffs (id) ON DELETE SET NULL ON UPDATE CASCADE,
	voip_tariff_rule_id integer DEFAULT NULL
		REFERENCES voip_rules (id) ON DELETE SET NULL ON UPDATE CASCADE,
	datefrom integer	NOT NULL DEFAULT 0,
	dateto integer		NOT NULL DEFAULT 0,
	authtype smallint 	DEFAULT 0 NOT NULL,
	PRIMARY KEY (id),
	CONSTRAINT tariffs_name_key UNIQUE (name, value, period)
);
CREATE INDEX tariffs_type_idx ON tariffs (type);

/* --------------------------------------------------------
  Structure of table "voip_numbers"
-------------------------------------------------------- */
DROP SEQUENCE IF EXISTS voip_numbers_id_seq;
CREATE SEQUENCE voip_numbers_id_seq;
DROP TABLE IF EXISTS voip_numbers CASCADE;
CREATE TABLE voip_numbers (
	id integer DEFAULT nextval('voip_numbers_id_seq'::text) NOT NULL,
	voip_account_id integer NOT NULL
		REFERENCES voipaccounts (id) ON DELETE CASCADE ON UPDATE CASCADE,
	phone varchar(20) NOT NULL,
	number_index smallint,
	tariff_id integer NULL
		REFERENCES tariffs (id) ON DELETE SET NULL ON UPDATE CASCADE,
	UNIQUE(phone),
	UNIQUE(voip_account_id, number_index),
	PRIMARY KEY (id)
);

/* --------------------------------------------------------
  Structure of table "voip_pool_numbers"
-------------------------------------------------------- */
DROP SEQUENCE IF EXISTS voip_pool_numbers_id_seq;
CREATE SEQUENCE voip_pool_numbers_id_seq;
DROP TABLE IF EXISTS voip_pool_numbers CASCADE;
CREATE TABLE voip_pool_numbers (
	id integer DEFAULT nextval('voip_pool_numbers_id_seq'::text) NOT NULL,
	disabled smallint DEFAULT 0,
	name varchar(30) NOT NULL,
	poolstart varchar(20) NOT NULL,
	poolend varchar(20) NOT NULL,
	description text,
	type smallint NOT NULL DEFAULT 1,
	PRIMARY KEY (id),
	UNIQUE (name)
);

/* --------------------------------------------------------
  Structure of table "voip_emergency_numbers"
-------------------------------------------------------- */
DROP TABLE IF EXISTS voip_emergency_numbers CASCADE;
CREATE TABLE voip_emergency_numbers (
	location_borough integer NOT NULL
		REFERENCES location_boroughs (id) ON DELETE CASCADE ON UPDATE CASCADE,
	number integer NOT NULL,
	fullnumber varchar(20) NOT NULL,
	UNIQUE (location_borough, number)
);
CREATE INDEX voip_emergency_numbers_number_idx ON voip_emergency_numbers (number);

/* --------------------------------------------------------
  Structure of table "liabilities"
-------------------------------------------------------- */
DROP SEQUENCE IF EXISTS liabilities_id_seq;
CREATE SEQUENCE liabilities_id_seq;
DROP TABLE IF EXISTS liabilities CASCADE;
CREATE TABLE liabilities (
	id integer DEFAULT nextval('liabilities_id_seq'::text) NOT NULL,
	value numeric(9,2)  	DEFAULT 0 NOT NULL,
	name text           	DEFAULT '' NOT NULL,
	taxid integer       	NOT NULL
		CONSTRAINT liabilities_taxid_fkey REFERENCES taxes (id) ON DELETE CASCADE ON UPDATE CASCADE,
	prodid varchar(255) 	DEFAULT '' NOT NULL,
	PRIMARY KEY (id)
);

/* ----------------------------------------------------
 Structure of table "assignments"
---------------------------------------------------*/
DROP SEQUENCE IF EXISTS assignments_id_seq;
CREATE SEQUENCE assignments_id_seq;
DROP TABLE IF EXISTS assignments CASCADE;
CREATE TABLE assignments (
	id integer default nextval('assignments_id_seq'::text) NOT NULL,
	tariffid integer 	DEFAULT NULL
		CONSTRAINT assignments_tariffid_fkey REFERENCES tariffs (id) ON DELETE CASCADE ON UPDATE CASCADE,
	liabilityid integer 	DEFAULT NULL
		CONSTRAINT assignments_liabilityid_fkey REFERENCES liabilities (id) ON DELETE CASCADE ON UPDATE CASCADE,
	customerid integer	NOT NULL
		CONSTRAINT assignments_customerid_fkey REFERENCES customers (id) ON DELETE CASCADE ON UPDATE CASCADE,
	period smallint 	DEFAULT 0 NOT NULL,
	at integer 		DEFAULT 0 NOT NULL,
	datefrom integer	DEFAULT 0 NOT NULL,
	dateto integer		DEFAULT 0 NOT NULL,
	invoice smallint 	DEFAULT 0 NOT NULL,
	suspended smallint	DEFAULT 0 NOT NULL,
	settlement smallint	DEFAULT 0 NOT NULL,
	pdiscount numeric(4,2)	DEFAULT 0 NOT NULL,
	vdiscount numeric(9,2) DEFAULT 0 NOT NULL,
	paytype smallint    DEFAULT NULL,
	numberplanid integer DEFAULT NULL
		REFERENCES numberplans (id) ON DELETE SET NULL ON UPDATE CASCADE,
	attribute varchar(255) DEFAULT NULL,
	recipient_address_id integer DEFAULT NULL
		CONSTRAINT assignments_recipient_address_id_fkey REFERENCES addresses (id) ON DELETE SET NULL ON UPDATE CASCADE,
	docid integer DEFAULT NULL
		CONSTRAINT assignments_docid_fkey REFERENCES documents (id) ON DELETE CASCADE ON UPDATE CASCADE,
	commited smallint DEFAULT 1 NOT NULL,
	separatedocument smallint DEFAULT 0 NOT NULL,
	PRIMARY KEY (id)
);
CREATE INDEX assignments_tariffid_idx ON assignments (tariffid);
CREATE INDEX assignments_customerid_idx ON assignments (customerid);
CREATE INDEX assignments_numberplanid_idx ON assignments (numberplanid);

/* --------------------------------------------------------
  Structure of table "voip_number_assignments"
-------------------------------------------------------- */
DROP SEQUENCE IF EXISTS voip_number_assignments_id_seq;
CREATE SEQUENCE voip_number_assignments_id_seq;
DROP TABLE IF EXISTS voip_number_assignments CASCADE;
CREATE TABLE voip_number_assignments (
	id            integer DEFAULT nextval('voip_number_assignments_id_seq'::text) NOT NULL,
	number_id     integer NOT NULL REFERENCES voip_numbers (id) ON DELETE CASCADE ON UPDATE CASCADE,
	assignment_id integer NOT NULL REFERENCES assignments  (id) ON DELETE CASCADE ON UPDATE CASCADE,
	PRIMARY KEY (id),
	CONSTRAINT voip_number_assignments_assignment_id_key
		UNIQUE (assignment_id, number_id)
);

/* --------------------------------------------------------
  Structure of table "invoicecontents"
-------------------------------------------------------- */
DROP TABLE IF EXISTS invoicecontents CASCADE;
CREATE TABLE invoicecontents (
	docid integer 		NOT NULL
		CONSTRAINT invoicecontents_docid_fkey REFERENCES documents (id) ON DELETE CASCADE ON UPDATE CASCADE,
	itemid smallint		DEFAULT 0 NOT NULL,
	value numeric(12,5) 	DEFAULT 0 NOT NULL,
	taxid integer 		NOT NULL
		CONSTRAINT invoicecontents_taxid_fkey REFERENCES taxes (id) ON DELETE CASCADE ON UPDATE CASCADE,
	prodid varchar(255) 	DEFAULT '' NOT NULL,
	content varchar(16) 	DEFAULT '' NOT NULL,
	count numeric(9,3) 	DEFAULT 0 NOT NULL,
	description text 	DEFAULT '' NOT NULL,
	tariffid integer 	DEFAULT NULL
		CONSTRAINT invoicecontents_tariffid_fkey REFERENCES tariffs (id) ON DELETE SET NULL ON UPDATE CASCADE,
	pdiscount numeric(4,2) DEFAULT 0 NOT NULL,
	vdiscount numeric(9,2) DEFAULT 0 NOT NULL
);
CREATE INDEX invoicecontents_docid_idx ON invoicecontents (docid);

/* --------------------------------------------------------
  Structure of table "debitnotecontents"
-------------------------------------------------------- */
DROP TABLE IF EXISTS debitnotecontents CASCADE;
DROP SEQUENCE IF EXISTS debitnotecontents_id_seq;
CREATE SEQUENCE debitnotecontents_id_seq;
CREATE TABLE debitnotecontents (
	id integer 		DEFAULT nextval('debitnotecontents_id_seq'::text) NOT NULL,
	docid integer           NOT NULL
		CONSTRAINT debitnotecontents_docid_fkey REFERENCES documents (id) ON DELETE CASCADE ON UPDATE CASCADE,
	itemid smallint         DEFAULT 0 NOT NULL,
	value numeric(9,2)      DEFAULT 0 NOT NULL,
	description text 	DEFAULT '' NOT NULL,
	PRIMARY KEY (id),
	CONSTRAINT debitnotecontents_docid_key UNIQUE (docid, itemid)
);

/* ---------------------------------------------------
 Structure of table "cashsources"
------------------------------------------------------*/
DROP SEQUENCE IF EXISTS cashsources_id_seq;
CREATE SEQUENCE cashsources_id_seq;
DROP TABLE IF EXISTS cashsources CASCADE;
CREATE TABLE cashsources (
	id integer      	DEFAULT nextval('cashsources_id_seq'::text) NOT NULL,
	name varchar(32)    DEFAULT '' NOT NULL,
	description text	DEFAULT NULL,
	account varchar(48) NOT NULL DEFAULT '',
	deleted smallint	NOT NULL DEFAULT 0,
	PRIMARY KEY (id),
	UNIQUE (name)
);

/* ---------------------------------------------------
 Structure of table "sourcefiles"
------------------------------------------------------*/
DROP SEQUENCE IF EXISTS sourcefiles_id_seq;
CREATE SEQUENCE sourcefiles_id_seq;
DROP TABLE IF EXISTS sourcefiles CASCADE;
CREATE TABLE sourcefiles (
	id integer      	DEFAULT nextval('sourcefiles_id_seq'::text) NOT NULL,
	userid integer     DEFAULT NULL
		REFERENCES users (id) ON DELETE SET NULL ON UPDATE CASCADE,
	name varchar(255)   NOT NULL,
	idate integer	    NOT NULL,
	PRIMARY KEY (id),
	CONSTRAINT sourcefiles_idate_key UNIQUE (idate, name)
);

CREATE INDEX sourcefiles_userid_idx ON sourcefiles (userid);

/* ---------------------------------------------------
 Structure of table "cashimport"
------------------------------------------------------*/
DROP SEQUENCE IF EXISTS cashimport_id_seq;
CREATE SEQUENCE cashimport_id_seq;
DROP TABLE IF EXISTS cashimport CASCADE;
CREATE TABLE cashimport (
	id integer 			DEFAULT nextval('cashimport_id_seq'::text) NOT NULL,
	date integer 		DEFAULT 0 NOT NULL,
	value numeric(9,2) 		DEFAULT 0 NOT NULL,
	customer text		DEFAULT '' NOT NULL,
	description text	DEFAULT '' NOT NULL,
	customerid integer 		DEFAULT NULL
		CONSTRAINT cashimport_customerid_fkey REFERENCES customers (id) ON DELETE SET NULL ON UPDATE CASCADE,
	hash varchar(50) 		DEFAULT '' NOT NULL,
	closed smallint 		DEFAULT 0 NOT NULL,
	sourceid integer		DEFAULT NULL
		REFERENCES cashsources (id) ON DELETE SET NULL ON UPDATE CASCADE,
	sourcefileid integer    DEFAULT NULL
		REFERENCES sourcefiles (id) ON DELETE SET NULL ON UPDATE CASCADE,
	PRIMARY KEY (id)
);

CREATE INDEX cashimport_hash_idx ON cashimport (hash);
CREATE INDEX cashimport_customerid_idx ON cashimport (customerid);
CREATE INDEX cashimport_sourcefileid_idx ON cashimport (sourcefileid);
CREATE INDEX cashimport_sourceid_idx ON cashimport (sourceid);

/* --------------------------------------------------------
  Structure of table "cash"
-------------------------------------------------------- */
DROP SEQUENCE IF EXISTS cash_id_seq;
CREATE SEQUENCE cash_id_seq;
DROP TABLE IF EXISTS cash CASCADE;
CREATE TABLE cash (
	id integer 		DEFAULT nextval('cash_id_seq'::text) NOT NULL,
	time integer 		DEFAULT 0 NOT NULL,
	type smallint 		DEFAULT 0 NOT NULL,
	userid integer 		DEFAULT NULL
		CONSTRAINT cash_userid_fkey REFERENCES users (id) ON DELETE SET NULL ON UPDATE CASCADE,
	value numeric(9,2) 	DEFAULT 0 NOT NULL,
	taxid integer		DEFAULT NULL
		CONSTRAINT cash_taxid_fkey REFERENCES taxes (id) ON DELETE SET NULL ON UPDATE CASCADE,
	customerid integer 	DEFAULT NULL
		CONSTRAINT cash_customerid_fkey REFERENCES customers (id) ON DELETE SET NULL ON UPDATE CASCADE,
	comment text 		DEFAULT '' NOT NULL,
	docid integer 		DEFAULT NULL
		CONSTRAINT cash_docid_fkey REFERENCES documents (id) ON DELETE CASCADE ON UPDATE CASCADE,
	itemid smallint		DEFAULT 0 NOT NULL,
	importid integer	DEFAULT NULL
		CONSTRAINT cash_importid_fkey REFERENCES cashimport (id) ON DELETE CASCADE ON UPDATE CASCADE,
	sourceid integer	DEFAULT NULL
		CONSTRAINT cash_sourceid_fkey REFERENCES cashsources (id) ON DELETE SET NULL ON UPDATE CASCADE,
	linktechnology integer DEFAULT NULL,
	PRIMARY KEY (id)
);
CREATE INDEX cash_customerid_idx ON cash (customerid);
CREATE INDEX cash_docid_idx ON cash (docid);
CREATE INDEX cash_importid_idx ON cash (importid);
CREATE INDEX cash_sourceid_idx ON cash (sourceid);
CREATE INDEX cash_time_idx ON cash (time);
CREATE INDEX cash_linktechnology_idx ON cash (linktechnology);

/* --------------------------------------------------------
  Structure of table "pna"
-------------------------------------------------------- */
DROP SEQUENCE IF EXISTS pna_id_seq;
CREATE SEQUENCE pna_id_seq;
DROP TABLE IF EXISTS pna CASCADE;
CREATE TABLE pna (
	id integer DEFAULT nextval('pna_id_seq'::text) NOT NULL,
	zip varchar(128) NOT NULL,
	cityid integer DEFAULT NULL
		REFERENCES location_cities (id) ON DELETE CASCADE ON UPDATE CASCADE,
	cityname varchar(100) DEFAULT NULL,
	streetid integer DEFAULT NULL
		REFERENCES location_streets (id) ON DELETE CASCADE ON UPDATE CASCADE,
	streetname varchar(100) DEFAULT NULL,
	fromnumber smallint DEFAULT NULL,
	fromletter varchar(8) DEFAULT NULL,
	tonumber smallint DEFAULT NULL,
	toletter varchar(8) DEFAULT NULL,
	parity smallint DEFAULT 0 NOT NULL,
	PRIMARY KEY (id),
	CONSTRAINT pna_zip_cityid_streetid_fromnumber_tonumber_key
		UNIQUE (zip, cityid, cityname, streetid, streetname, fromnumber, fromletter, tonumber, toletter, parity)
);
CREATE INDEX pna_fromnumber_idx ON pna (fromnumber);
CREATE INDEX pna_tonumber_idx ON pna (tonumber);

/* ---------------------------------------------------
 Structure of table "ewx_channels" (EtherWerX(R))
------------------------------------------------------*/
DROP SEQUENCE IF EXISTS ewx_channels_id_seq;
CREATE SEQUENCE ewx_channels_id_seq;
DROP TABLE IF EXISTS ewx_channels CASCADE;
CREATE TABLE ewx_channels (
    id 		integer 	DEFAULT nextval('ewx_channels_id_seq'::text) NOT NULL,
    name 	varchar(32)     DEFAULT '' NOT NULL,
    upceil 	integer         DEFAULT 0 NOT NULL,
    downceil 	integer        	DEFAULT 0 NOT NULL,
    upceil_n 	integer         DEFAULT NULL,
    downceil_n 	integer        	DEFAULT NULL,
    halfduplex  smallint    DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE (name)
);

/* ---------------------------------------------------
 Structure of table "ewx_stm_channels" (EtherWerX(R))
------------------------------------------------------*/
DROP SEQUENCE IF EXISTS ewx_stm_channels_id_seq;
CREATE SEQUENCE ewx_stm_channels_id_seq;
DROP TABLE IF EXISTS ewx_stm_channels CASCADE;
CREATE TABLE ewx_stm_channels (
    id 		integer 	DEFAULT nextval('ewx_stm_channels_id_seq'::text) NOT NULL,
    cid 	integer      	DEFAULT NULL
		CONSTRAINT ewx_stm_channels_cid_fkey REFERENCES customers (id) ON DELETE SET NULL ON UPDATE CASCADE,
    upceil 	integer         DEFAULT 0 NOT NULL,
    downceil 	integer        	DEFAULT 0 NOT NULL,
    halfduplex  smallint    DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE (cid)
);

/* ---------------------------------------------------
 Structure of table "hosts"
------------------------------------------------------*/
DROP SEQUENCE IF EXISTS hosts_id_seq;
CREATE SEQUENCE hosts_id_seq;
DROP TABLE IF EXISTS hosts CASCADE;
CREATE TABLE hosts (
    id integer DEFAULT nextval('hosts_id_seq'::text) NOT NULL,
    name varchar(255) 		DEFAULT '' NOT NULL,
    description text 		DEFAULT '' NOT NULL,
    lastreload integer 		DEFAULT 0 NOT NULL,
    reload smallint 		DEFAULT 0 NOT NULL,
    PRIMARY KEY (id),
    UNIQUE (name)
);

/* --------------------------------------------------------
  Structure of table "networks"
-------------------------------------------------------- */
DROP SEQUENCE IF EXISTS networks_id_seq;
CREATE SEQUENCE networks_id_seq;
DROP TABLE IF EXISTS networks CASCADE;
CREATE TABLE networks (
	id integer DEFAULT nextval('networks_id_seq'::text) NOT NULL,
	name varchar(255) 	DEFAULT '' NOT NULL,
	address bigint 		DEFAULT 0 NOT NULL,
	snat bigint 		DEFAULT NULL,
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
	vlanid smallint DEFAULT NULL,
	hostid integer NULL
		REFERENCES hosts (id) ON DELETE SET NULL ON UPDATE CASCADE,
	authtype smallint 	DEFAULT 0 NOT NULL,
	pubnetid integer DEFAULT NULL
		CONSTRAINT networks_pubnetid_fkey REFERENCES networks (id) ON DELETE SET NULL ON UPDATE CASCADE,
	PRIMARY KEY (id),
	UNIQUE (name),
	CONSTRAINT networks_address_key UNIQUE (address, hostid)
);
CREATE INDEX networks_hostid_idx ON networks (hostid);

/* ---------------------------------------------------
 Structure of table "invprojects"
------------------------------------------------------*/
DROP SEQUENCE IF EXISTS invprojects_id_seq;
CREATE SEQUENCE invprojects_id_seq;
DROP TABLE IF EXISTS invprojects CASCADE;
CREATE TABLE invprojects (
	id integer DEFAULT nextval('invprojects_id_seq'::text) NOT NULL,
	name varchar(255) NOT NULL,
	type smallint DEFAULT 0,
        divisionid integer DEFAULT NULL
                REFERENCES divisions (id) ON DELETE SET NULL ON UPDATE CASCADE,
	PRIMARY KEY(id)
);

/* ---------------------------------------------------
 Structure of table "netnodes"
------------------------------------------------------*/
DROP SEQUENCE IF EXISTS netnodes_id_seq;
CREATE SEQUENCE netnodes_id_seq;
DROP TABLE IF EXISTS netnodes CASCADE;
CREATE TABLE netnodes (
	id integer DEFAULT nextval('netnodes_id_seq'::text) NOT NULL,
	name varchar(255) NOT NULL,
	type smallint DEFAULT 0,
	invprojectid integer
		REFERENCES invprojects (id) ON DELETE SET NULL ON UPDATE CASCADE,
	status smallint DEFAULT 0,
	longitude numeric(10,6) DEFAULT NULL,
	latitude numeric(10,6) DEFAULT NULL,
	ownership smallint DEFAULT 0,
	coowner varchar(255) DEFAULT '',
	uip smallint DEFAULT 0,
	miar smallint DEFAULT 0,
	createtime integer,
	lastinspectiontime integer DEFAULT NULL,
	admcontact text DEFAULT NULL,
	divisionid integer
		REFERENCES divisions (id) ON DELETE SET NULL ON UPDATE CASCADE,
	address_id integer
		REFERENCES addresses (id) ON DELETE SET NULL ON UPDATE CASCADE,
	info text DEFAULT NULL,
	ownerid integer DEFAULT NULL
		CONSTRAINT netnodes_ownerid_fkey REFERENCES customers (id) ON DELETE SET NULL ON UPDATE CASCADE,
	PRIMARY KEY(id)
);

/* ---------------------------------------------------
 Structure of table "netdeviceproducers"
------------------------------------------------------*/
DROP SEQUENCE IF EXISTS netdeviceproducers_id_seq;
CREATE SEQUENCE netdeviceproducers_id_seq;
DROP TABLE IF EXISTS netdeviceproducers CASCADE;
CREATE TABLE netdeviceproducers (
	id integer DEFAULT nextval('netdeviceproducers_id_seq'::text) NOT NULL,
	name varchar(255) NOT NULL,
	alternative_name VARCHAR(255),
	PRIMARY KEY (id),
	UNIQUE (name)
);

/* ---------------------------------------------------
 Structure of table "netdevicemodels"
------------------------------------------------------*/
DROP SEQUENCE IF EXISTS netdevicemodels_id_seq;
CREATE SEQUENCE netdevicemodels_id_seq;
DROP TABLE IF EXISTS netdevicemodels CASCADE;
CREATE TABLE netdevicemodels (
	id integer DEFAULT nextval('netdevicemodels_id_seq'::text) NOT NULL,
	netdeviceproducerid integer NOT NULL,
	name varchar(255) NOT NULL,
	alternative_name VARCHAR(255),
	PRIMARY KEY (id),
	FOREIGN KEY (netdeviceproducerid)
		REFERENCES netdeviceproducers(id) ON DELETE CASCADE ON UPDATE CASCADE,
	UNIQUE (name, netdeviceproducerid)
);

/* ---------------------------------------------------
 Structure of table "netdevices"
----------------------------------------------------*/
DROP SEQUENCE IF EXISTS netdevices_id_seq;
CREATE SEQUENCE netdevices_id_seq;
DROP TABLE IF EXISTS netdevices CASCADE;
CREATE TABLE netdevices (
	id integer default nextval('netdevices_id_seq'::text) NOT NULL,
	name varchar(32) 	DEFAULT '' NOT NULL,
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
	longitude numeric(10, 6) DEFAULT NULL,
	latitude numeric(10, 6) DEFAULT NULL,
	netnodeid integer	DEFAULT NULL
	    REFERENCES netnodes(id) ON DELETE SET NULL ON UPDATE CASCADE,
	invprojectid integer	DEFAULT NULL
	    REFERENCES invprojects(id) ON DELETE SET NULL ON UPDATE CASCADE,
	status smallint		DEFAULT 0,
	netdevicemodelid integer DEFAULT NULL
		REFERENCES netdevicemodels (id) ON UPDATE CASCADE ON DELETE SET NULL,
	ownerid integer DEFAULT NULL
		CONSTRAINT netdevices_ownerid_fkey REFERENCES customers (id) ON UPDATE CASCADE ON DELETE SET NULL,
	address_id integer DEFAULT NULL
		CONSTRAINT netdevices_address_id_fkey REFERENCES addresses (id) ON UPDATE CASCADE ON DELETE SET NULL,
	PRIMARY KEY (id)
);
CREATE INDEX netdevices_channelid_idx ON netdevices (channelid);

/* ---------------------------------------------------
 Structure of table "netradiosectors"
------------------------------------------------------*/
DROP SEQUENCE IF EXISTS netradiosectors_id_seq;
CREATE SEQUENCE netradiosectors_id_seq;
DROP TABLE IF EXISTS netradiosectors CASCADE;
CREATE TABLE netradiosectors (
	id integer DEFAULT nextval('netradiosectors_id_seq'::text) NOT NULL,
	name varchar(64) NOT NULL,
	type smallint DEFAULT 0 NOT NULL,
	azimuth numeric(9,2) DEFAULT 0 NOT NULL,
	width numeric(9,2) DEFAULT 0 NOT NULL,
	altitude smallint DEFAULT 0 NOT NULL,
	rsrange integer DEFAULT 0 NOT NULL,
	license varchar(64) DEFAULT NULL,
	technology integer DEFAULT 0 NOT NULL,
	frequency numeric(9,5) DEFAULT NULL,
	frequency2 numeric(9,5) DEFAULT NULL,
	bandwidth numeric(9,5) DEFAULT NULL,
	netdev integer NOT NULL
		REFERENCES netdevices (id) ON DELETE CASCADE ON UPDATE CASCADE,
	secret varchar(64) DEFAULT NULL,
	PRIMARY KEY (id),
	UNIQUE (name, netdev)
);

/* --------------------------------------------------------
  Structure of table "nodes"
-------------------------------------------------------- */
DROP SEQUENCE IF EXISTS nodes_id_seq;
CREATE SEQUENCE nodes_id_seq;
DROP TABLE IF EXISTS nodes CASCADE;
CREATE TABLE nodes (
	id integer DEFAULT nextval('nodes_id_seq'::text) NOT NULL,
	name varchar(32) 	DEFAULT '' NOT NULL,
	ipaddr bigint 		DEFAULT 0 NOT NULL,
	ipaddr_pub bigint 	DEFAULT 0 NOT NULL,
	passwd varchar(32)	DEFAULT '' NOT NULL,
	ownerid integer 	DEFAULT NULL
		CONSTRAINT nodes_ownerid_fkey REFERENCES customers (id) ON DELETE CASCADE ON UPDATE CASCADE,
	netdev integer 		DEFAULT NULL
		CONSTRAINT nodes_netdev_fkey REFERENCES netdevices (id) ON DELETE SET NULL ON UPDATE CASCADE,
	linktype smallint	DEFAULT 0 NOT NULL,
	linkradiosector integer DEFAULT NULL
		REFERENCES netradiosectors (id) ON DELETE SET NULL ON UPDATE CASCADE,
	linkspeed integer	DEFAULT 100000 NOT NULL,
	linktechnology integer	DEFAULT 0 NOT NULL,
	port smallint		DEFAULT 0 NOT NULL,
	creationdate integer 	DEFAULT 0 NOT NULL,
	moddate integer 	DEFAULT 0 NOT NULL,
	creatorid integer 	DEFAULT NULL
		CONSTRAINT nodes_creatorid_fkey REFERENCES users (id) ON DELETE SET NULL ON UPDATE CASCADE,
	modid integer 		DEFAULT NULL
		CONSTRAINT nodes_modid_fkey REFERENCES users (id) ON DELETE SET NULL ON UPDATE CASCADE,
	access smallint 	DEFAULT 1 NOT NULL,
	warning smallint 	DEFAULT 0 NOT NULL,
	authtype smallint 	DEFAULT 0 NOT NULL,
	chkmac smallint 	DEFAULT 1 NOT NULL,
	halfduplex smallint	DEFAULT 0 NOT NULL,
	lastonline integer	DEFAULT 0 NOT NULL,
	info text		    DEFAULT '' NOT NULL,
	nas smallint 		DEFAULT 0 NOT NULL,
	longitude numeric(10, 6) DEFAULT NULL,
	latitude numeric(10, 6) DEFAULT NULL,
	netid integer		DEFAULT 0 NOT NULL
		REFERENCES networks (id) ON DELETE CASCADE ON UPDATE CASCADE,
	invprojectid integer DEFAULT NULL
		REFERENCES invprojects(id) ON DELETE SET NULL ON UPDATE CASCADE,
	address_id integer DEFAULT NULL
		REFERENCES addresses(id) ON DELETE SET NULL ON UPDATE CASCADE,
	PRIMARY KEY (id),
	UNIQUE (name),
	UNIQUE (ipaddr, netid)
);
CREATE INDEX nodes_netdev_idx ON nodes (netdev);
CREATE INDEX nodes_ownerid_idx ON nodes (ownerid);
CREATE INDEX nodes_ipaddr_pub_idx ON nodes (ipaddr_pub);
CREATE INDEX nodes_linkradiosector_idx ON nodes (linkradiosector);
CREATE INDEX nodes_authtype_idx ON nodes (authtype);

/* ---------------------------------------------------
 Structure of table "ewx_stm_nodes" (EtherWerX(R))
------------------------------------------------------*/
DROP SEQUENCE IF EXISTS ewx_stm_nodes_id_seq;
CREATE SEQUENCE ewx_stm_nodes_id_seq;
DROP TABLE IF EXISTS ewx_stm_nodes CASCADE;
CREATE TABLE ewx_stm_nodes (
	id 		integer		DEFAULT nextval('ewx_stm_nodes_id_seq'::text) NOT NULL,
	nodeid 		integer         DEFAULT NULL
		CONSTRAINT ewx_stm_nodes_nodeid_fkey REFERENCES nodes (id) ON DELETE SET NULL ON UPDATE CASCADE,
	mac 		varchar(20)     DEFAULT '' NOT NULL,
	ipaddr 		bigint          DEFAULT 0 NOT NULL,
	channelid 	integer       	DEFAULT NULL
		CONSTRAINT ewx_stm_nodes_channelid_fkey REFERENCES ewx_stm_channels (id) ON DELETE SET NULL ON UPDATE CASCADE,
	uprate 		integer         DEFAULT 0 NOT NULL,
	upceil 		integer         DEFAULT 0 NOT NULL,
	downrate 	integer        	DEFAULT 0 NOT NULL,
	downceil 	integer        	DEFAULT 0 NOT NULL,
	halfduplex 	smallint     	DEFAULT 0 NOT NULL,
	PRIMARY KEY (id),
	UNIQUE (nodeid)
);

/* ----------------------------------------------------
 Structure of table "nodelocks"
---------------------------------------------------*/
DROP SEQUENCE IF EXISTS nodelocks_id_seq;
CREATE SEQUENCE nodelocks_id_seq;
DROP TABLE IF EXISTS nodelocks CASCADE;
CREATE TABLE nodelocks (
	id integer		DEFAULT nextval('nodelocks_id_seq'::text) NOT NULL,
	nodeid integer		NOT NULL
		REFERENCES nodes (id) ON DELETE CASCADE ON UPDATE CASCADE,
	days smallint		DEFAULT 0 NOT NULL,
	fromsec integer		DEFAULT 0 NOT NULL,
	tosec integer		DEFAULT 0 NOT NULL,
	PRIMARY KEY (id)
);

/* --------------------------------------------------------
  Structure of table "macs"
-------------------------------------------------------- */
DROP SEQUENCE IF EXISTS macs_id_seq;
CREATE SEQUENCE macs_id_seq;
DROP TABLE IF EXISTS macs CASCADE;
CREATE TABLE macs (
	id integer		DEFAULT nextval('macs_id_seq'::text) NOT NULL,
	mac varchar(17)		DEFAULT '' NOT NULL,
	nodeid integer		NOT NULL
		REFERENCES nodes (id) ON DELETE CASCADE ON UPDATE CASCADE,
	PRIMARY KEY (id),
	CONSTRAINT macs_mac_key UNIQUE (mac, nodeid)
);

/* --------------------------------------------------------
  Structure of table "nodegroups"
-------------------------------------------------------- */
DROP SEQUENCE IF EXISTS nodegroups_id_seq;
CREATE SEQUENCE nodegroups_id_seq;
DROP TABLE IF EXISTS nodegroups CASCADE;
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
DROP SEQUENCE IF EXISTS nodegroupassignments_id_seq;
CREATE SEQUENCE nodegroupassignments_id_seq;
DROP TABLE IF EXISTS nodegroupassignments CASCADE;
CREATE TABLE nodegroupassignments (
        id              integer         NOT NULL DEFAULT nextval('nodegroupassignments_id_seq'::text),
	nodegroupid     integer         NOT NULL
		CONSTRAINT nodegroupassignments_nodegroupid_fkey REFERENCES nodegroups (id) ON DELETE CASCADE ON UPDATE CASCADE,
	nodeid          integer         NOT NULL
		CONSTRAINT nodegroupassignments_nodeid_fkey REFERENCES nodes (id) ON DELETE CASCADE ON UPDATE CASCADE,
	PRIMARY KEY (id),
	CONSTRAINT nodegroupassignments_nodeid_key UNIQUE (nodeid, nodegroupid)
);

/* --------------------------------------------------------
  Structure of table "nodeassignments"
-------------------------------------------------------- */
DROP SEQUENCE IF EXISTS nodeassignments_id_seq;
CREATE SEQUENCE nodeassignments_id_seq;
DROP TABLE IF EXISTS nodeassignments CASCADE;
CREATE TABLE nodeassignments (
        id integer              DEFAULT nextval('nodeassignments_id_seq'::text) NOT NULL,
	nodeid integer          NOT NULL
		REFERENCES nodes (id) ON DELETE CASCADE ON UPDATE CASCADE,
	assignmentid integer    NOT NULL
		REFERENCES assignments (id) ON DELETE CASCADE ON UPDATE CASCADE,
	PRIMARY KEY (id),
	CONSTRAINT nodeassignments_nodeid_key UNIQUE (nodeid, assignmentid)
);

CREATE INDEX nodeassignments_assignmentid_idx ON nodeassignments (assignmentid);

/* --------------------------------------------------------
  Structure of table "tariffstags"
-------------------------------------------------------- */

DROP SEQUENCE IF EXISTS tarifftags_id_seq;
CREATE SEQUENCE tarifftags_id_seq;

DROP TABLE IF EXISTS tarifftags CASCADE;
CREATE TABLE tarifftags (
	id integer DEFAULT nextval('tarifftags_id_seq'::text) NOT NULL,
	name varchar(255) NOT NULL,
	description text NULL,
	PRIMARY KEY (id),
	UNIQUE (name)
);

/* --------------------------------------------------------
  Structure of table "tariffassignments"
-------------------------------------------------------- */

DROP SEQUENCE IF EXISTS tariffassignments_id_seq;
CREATE SEQUENCE tariffassignments_id_seq;
DROP TABLE IF EXISTS tariffassignments CASCADE;
CREATE TABLE tariffassignments (
	id integer DEFAULT nextval('tariffassignments_id_seq'::text) NOT NULL,
        tariffid integer NOT NULL
            REFERENCES tariffs (id) ON DELETE CASCADE ON UPDATE CASCADE,
        tarifftagid integer NOT NULL
            REFERENCES tarifftags (id) ON DELETE CASCADE ON UPDATE CASCADE,
        PRIMARY KEY (id),
        CONSTRAINT tariffassignments_tarifftagid_key UNIQUE (tariffid,tarifftagid)
);

CREATE INDEX tariffassignments_tarifftagid_idx ON tariffassignments (tarifftagid);

/* --------------------------------------------------------
  Structure of table "promotions"
-------------------------------------------------------- */
DROP SEQUENCE IF EXISTS promotions_id_seq;
CREATE SEQUENCE promotions_id_seq;
DROP TABLE IF EXISTS promotions CASCADE;
CREATE TABLE promotions (
    id integer          DEFAULT nextval('promotions_id_seq'::text) NOT NULL,
    name varchar(255)   NOT NULL,
    description text    DEFAULT NULL,
    disabled smallint   DEFAULT 0 NOT NULL,
    datefrom integer	DEFAULT 0 NOT NULL,
    dateto integer		DEFAULT 0 NOT NULL,
    PRIMARY KEY (id),
    UNIQUE (name)
);

/* --------------------------------------------------------
  Structure of table "promotionschemas"
-------------------------------------------------------- */
DROP SEQUENCE IF EXISTS promotionschemas_id_seq;
CREATE SEQUENCE promotionschemas_id_seq;
DROP TABLE IF EXISTS promotionschemas CASCADE;
CREATE TABLE promotionschemas (
    id integer          DEFAULT nextval('promotionschemas_id_seq'::text) NOT NULL,
    name varchar(255)   NOT NULL,
    description text    DEFAULT NULL,
    data text           DEFAULT NULL,
    promotionid integer DEFAULT NULL
        REFERENCES promotions (id) ON DELETE CASCADE ON UPDATE CASCADE,
    disabled smallint   DEFAULT 0 NOT NULL,
    PRIMARY KEY (id),
    CONSTRAINT promotionschemas_promotionid_key UNIQUE (promotionid, name)
);

/* --------------------------------------------------------
  Structure of table "promotionassignments"
-------------------------------------------------------- */
DROP SEQUENCE IF EXISTS promotionassignments_id_seq;
CREATE SEQUENCE promotionassignments_id_seq;
DROP TABLE IF EXISTS promotionassignments CASCADE;
CREATE TABLE promotionassignments (
    id integer          DEFAULT nextval('promotionassignments_id_seq'::text) NOT NULL,
    promotionschemaid integer DEFAULT NULL
        REFERENCES promotionschemas (id) ON DELETE CASCADE ON UPDATE CASCADE,
    tariffid integer    DEFAULT NULL
        REFERENCES tariffs (id) ON DELETE CASCADE ON UPDATE CASCADE,
    data text           DEFAULT NULL,
    optional smallint   DEFAULT 0 NOT NULL,
    label varchar(60) DEFAULT NULL,
    orderid integer     NOT NULL DEFAULT 0,
    PRIMARY KEY (id)
);
CREATE INDEX promotionassignments_tariffid_idx ON promotionassignments (tariffid);

/* ---------------------------------------------------------
  Structure of table "payments"
--------------------------------------------------------- */
DROP SEQUENCE IF EXISTS payments_id_seq;
CREATE SEQUENCE payments_id_seq;
DROP TABLE IF EXISTS payments CASCADE;
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
  Structure of table "numberplanassignments"
-------------------------------------------------------- */
DROP SEQUENCE IF EXISTS numberplanassignments_id_seq;
CREATE SEQUENCE numberplanassignments_id_seq;
DROP TABLE IF EXISTS numberplanassignments CASCADE;
CREATE TABLE numberplanassignments (
	id integer DEFAULT nextval('numberplanassignments_id_seq'::text) NOT NULL,
	planid integer NOT NULL
		CONSTRAINT numberplanassignments_planid_fkey REFERENCES numberplans (id) ON DELETE CASCADE ON UPDATE CASCADE,
	divisionid integer NOT NULL
		CONSTRAINT numberplanassignments_divisionid_fkey REFERENCES divisions (id) ON DELETE CASCADE ON UPDATE CASCADE,
	PRIMARY KEY (id),
	CONSTRAINT numberplanassignments_planid_key UNIQUE (planid, divisionid)
);
CREATE INDEX numberplanassignments_divisionid_idx ON numberplanassignments (divisionid);

/* --------------------------------------------------------
  Structure of table "customergroups"
-------------------------------------------------------- */
DROP SEQUENCE IF EXISTS customergroups_id_seq;
CREATE SEQUENCE customergroups_id_seq;
DROP TABLE IF EXISTS customergroups CASCADE;
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
DROP SEQUENCE IF EXISTS customerassignments_id_seq;
CREATE SEQUENCE customerassignments_id_seq;
DROP TABLE IF EXISTS customerassignments CASCADE;
CREATE TABLE customerassignments (
	id integer DEFAULT nextval('customerassignments_id_seq'::text) NOT NULL,
	customergroupid integer NOT NULL
	    REFERENCES customergroups (id) ON DELETE CASCADE ON UPDATE CASCADE,
	customerid integer NOT NULL
	    CONSTRAINT customerassignments_customerid_fkey REFERENCES customers (id) ON DELETE CASCADE ON UPDATE CASCADE,
	PRIMARY KEY (id),
	CONSTRAINT customerassignments_customergroupid_key UNIQUE (customergroupid, customerid)
);

CREATE INDEX customerassignments_customerid_idx ON customerassignments (customerid);

/* --------------------------------------------------------
  Structure of table "nodesessions"
-------------------------------------------------------- */
DROP SEQUENCE IF EXISTS nodesessions_id_seq;
CREATE SEQUENCE nodesessions_id_seq;
DROP TABLE IF EXISTS nodesessions CASCADE;
CREATE TABLE nodesessions (
	id integer		DEFAULT nextval('nodesessions_id_seq'::text) NOT NULL,
	customerid integer	DEFAULT NULL
		CONSTRAINT nodesessions_customerid_fkey REFERENCES customers (id) ON DELETE SET NULL ON UPDATE CASCADE,
	nodeid integer		DEFAULT NULL
		CONSTRAINT nodesessions_nodeid_fkey REFERENCES nodes (id) ON DELETE SET NULL ON UPDATE CASCADE,
	ipaddr bigint		DEFAULT 0 NOT NULL,
	mac varchar(17)		DEFAULT '' NOT NULL,
	start integer		DEFAULT 0 NOT NULL,
	stop integer		DEFAULT 0 NOT NULL,
	download bigint		DEFAULT 0,
	upload bigint		DEFAULT 0,
	tag varchar(32)		DEFAULT '' NOT NULL,
	terminatecause varchar(32) DEFAULT '' NOT NULL,
	type smallint		DEFAULT 0 NOT NULL,
	PRIMARY KEY (id),
	nasipaddr bigint	DEFAULT NULL,
	nasport text		DEFAULT NULL
);
CREATE INDEX nodesessions_customerid_idx ON nodesessions(customerid);
CREATE INDEX nodesessions_nodeid_idx ON nodesessions(nodeid);
CREATE INDEX nodesessions_tag_idx ON nodesessions(tag);

/* --------------------------------------------------------
  Structure of table "stats"
-------------------------------------------------------- */
DROP TABLE IF EXISTS stats CASCADE;
CREATE TABLE stats (
	nodeid integer 		DEFAULT NULL
		CONSTRAINT stats_nodeid_fkey REFERENCES nodes (id) ON DELETE SET NULL ON UPDATE CASCADE,
	dt integer 		DEFAULT 0 NOT NULL,
	upload bigint 		DEFAULT 0,
	download bigint 	DEFAULT 0,
	nodesessionid integer	DEFAULT NULL
		CONSTRAINT stats_nodesessionid_fkey REFERENCES nodesessions (id) ON DELETE SET NULL ON UPDATE CASCADE,
	CONSTRAINT stats_nodeid_dt_key UNIQUE (nodeid, dt)
);
CREATE INDEX stats_dt_idx ON stats(dt);
CREATE INDEX stats_nodesessionid_idx ON stats(nodesessionid);

/* ---------------------------------------------------
 Structure of table "netlinks"
----------------------------------------------------*/
DROP SEQUENCE IF EXISTS netlinks_id_seq;
CREATE SEQUENCE netlinks_id_seq;
DROP TABLE IF EXISTS netlinks CASCADE;
CREATE TABLE netlinks (
	id integer default nextval('netlinks_id_seq'::text) NOT NULL,
	src integer 		NOT NULL
		CONSTRAINT netlinks_src_fkey REFERENCES netdevices (id) ON DELETE CASCADE ON UPDATE CASCADE,
	dst integer 		NOT NULL
		CONSTRAINT netlinks_dst_fkey REFERENCES netdevices (id) ON DELETE CASCADE ON UPDATE CASCADE,
	type smallint		DEFAULT 0 NOT NULL,
	speed integer		DEFAULT 100000 NOT NULL,
	technology integer	DEFAULT 0 NOT NULL,
	srcport smallint	DEFAULT 0 NOT NULL,
	dstport smallint	DEFAULT 0 NOT NULL,
	srcradiosector integer	DEFAULT NULL
		REFERENCES netradiosectors (id) ON DELETE SET NULL ON UPDATE CASCADE,
	dstradiosector integer	DEFAULT NULL
		REFERENCES netradiosectors (id) ON DELETE SET NULL ON UPDATE CASCADE,
	PRIMARY KEY  (id),
	CONSTRAINT netlinks_src_key UNIQUE (src, dst)
);
CREATE INDEX netlinks_srcradiosector_idx ON netlinks (srcradiosector);
CREATE INDEX netlinks_dstradiosector_idx ON netlinks (dstradiosector);

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

DROP SEQUENCE IF EXISTS rtqueues_id_seq;
CREATE SEQUENCE rtqueues_id_seq;
DROP TABLE IF EXISTS rtqueues CASCADE;
CREATE TABLE rtqueues (
  id integer default nextval('rtqueues_id_seq'::text) NOT NULL,
  name varchar(255) 	DEFAULT '' NOT NULL,
  email varchar(255) 	DEFAULT '' NOT NULL,
  description text	DEFAULT '' NOT NULL,
  newticketsubject varchar(255) NOT NULL DEFAULT '',
  newticketbody text NOT NULL DEFAULT '',
  newmessagesubject varchar(255) NOT NULL DEFAULT '',
  newmessagebody text NOT NULL DEFAULT '',
  resolveticketsubject varchar(255) NOT NULL DEFAULT '',
  resolveticketbody text NOT NULL DEFAULT '',
  verifierticketsubject varchar(255) NOT NULL DEFAULT '',
  verifierticketbody text NOT NULL DEFAULT '',
  deleted smallint	DEFAULT 0 NOT NULL,
  deltime integer	DEFAULT 0 NOT NULL,
  deluserid integer	DEFAULT NULL
	CONSTRAINT rtqueues_deluserid_fkey REFERENCES users (id) ON DELETE SET NULL ON UPDATE CASCADE,
  verifierid integer DEFAULT NULL
    CONSTRAINT rtqueues_verifierid_fkey REFERENCES users (id) ON DELETE SET NULL ON UPDATE CASCADE,
  PRIMARY KEY (id),
  UNIQUE (name)
);

DROP SEQUENCE IF EXISTS rttickets_id_seq;
CREATE SEQUENCE rttickets_id_seq;
DROP TABLE IF EXISTS rttickets CASCADE;
CREATE TABLE rttickets (
  id integer default nextval('rttickets_id_seq'::text) NOT NULL,
  queueid integer 	NOT NULL
    REFERENCES rtqueues (id) ON DELETE CASCADE ON UPDATE CASCADE,
  requestor varchar(255) DEFAULT '' NOT NULL,
  requestor_mail varchar(255) DEFAULT NULL,
  requestor_phone varchar(32) DEFAULT NULL,
  requestor_userid integer DEFAULT NULL
	CONSTRAINT rttickets_requestor_userid_fkey REFERENCES users (id) ON DELETE SET NULL ON UPDATE CASCADE,
  subject varchar(255) 	DEFAULT '' NOT NULL,
  state smallint 	DEFAULT 0 NOT NULL,
  cause smallint	DEFAULT 0 NOT NULL,
  owner integer 	DEFAULT NULL
	CONSTRAINT rttickets_owner_fkey REFERENCES users (id) ON DELETE SET NULL ON UPDATE CASCADE,
  customerid integer 	DEFAULT NULL
    CONSTRAINT rttickets_customerid_fkey REFERENCES customers (id) ON DELETE SET NULL ON UPDATE CASCADE,
  creatorid integer 	DEFAULT NULL
	CONSTRAINT rttickets_creatorid_fkey REFERENCES users (id) ON DELETE SET NULL ON UPDATE CASCADE,
  createtime integer 	DEFAULT 0 NOT NULL,
  resolvetime integer 	DEFAULT 0 NOT NULL,
  source smallint	DEFAULT 0 NOT NULL,
  priority smallint	DEFAULT 0 NOT NULL,
  deleted smallint	DEFAULT 0 NOT NULL,
  deltime integer	DEFAULT 0 NOT NULL,
  deluserid integer	DEFAULT NULL
	CONSTRAINT rttickets_deluserid_fkey REFERENCES users (id) ON DELETE SET NULL ON UPDATE CASCADE,
  address_id integer	DEFAULT NULL
    CONSTRAINT rttickets_address_id_fkey REFERENCES addresses (id) ON UPDATE CASCADE ON DELETE SET NULL,
  nodeid integer	DEFAULT NULL
    CONSTRAINT rttickets_nodeid_fkey REFERENCES nodes (id) ON UPDATE CASCADE ON DELETE SET NULL,
  netnodeid integer	DEFAULT NULL,
  netdevid integer	DEFAULT NULL
    CONSTRAINT rttickets_netdevid_fkey REFERENCES netdevices (id) ON UPDATE CASCADE ON DELETE SET NULL,
  verifierid integer DEFAULT NULL
        CONSTRAINT rttickets_verifierid_fkey REFERENCES users (id) ON DELETE SET NULL ON UPDATE CASCADE,
  verifier_rtime integer DEFAULT NULL,
  deadline integer DEFAULT NULL,
  service integer DEFAULT NULL,
  type integer DEFAULT NULL,
  parentid integer DEFAULT NULL
        CONSTRAINT rttickets_parentid_fkey REFERENCES rttickets (id) ON DELETE SET NULL ON UPDATE CASCADE,
  invprojectid integer DEFAULT NULL
        CONSTRAINT rttickets_invprojectid_fkey REFERENCES invprojects (id) ON DELETE SET NULL ON UPDATE CASCADE,
  PRIMARY KEY (id)
);

CREATE INDEX rttickets_queueid_idx ON rttickets (queueid);
CREATE INDEX rttickets_customerid_idx ON rttickets (customerid);
CREATE INDEX rttickets_creatorid_idx ON rttickets (creatorid);
CREATE INDEX rttickets_createtime_idx ON rttickets (createtime);

DROP TABLE IF EXISTS rtticketlastview CASCADE;
CREATE TABLE rtticketlastview (
	ticketid integer NOT NULL
		CONSTRAINT rtticketlastview_ticketid_fkey REFERENCES rttickets (id) ON UPDATE CASCADE ON DELETE CASCADE,
	userid integer NOT NULL
		CONSTRAINT rtticketlastview_userid_fkey REFERENCES users (id) ON UPDATE CASCADE ON DELETE CASCADE,
	vdate integer NOT NULL,
	CONSTRAINT rtticketlastview_ticketid_key UNIQUE (ticketid, userid)
);
CREATE INDEX rtticketlastview_vdate_idx ON rtticketlastview (vdate);

DROP SEQUENCE IF EXISTS rtmessages_id_seq;
CREATE SEQUENCE rtmessages_id_seq;
DROP TABLE IF EXISTS rtmessages CASCADE;
CREATE TABLE rtmessages (
  id integer default nextval('rtmessages_id_seq'::text) NOT NULL,
  ticketid integer 	NOT NULL
    REFERENCES rttickets (id) ON DELETE CASCADE ON UPDATE CASCADE,
  userid integer 	DEFAULT NULL
	CONSTRAINT rtmessages_userid_fkey REFERENCES users (id) ON DELETE SET NULL ON UPDATE CASCADE,
  customerid integer 	DEFAULT NULL
    CONSTRAINT rtmessages_customerid_fkey REFERENCES customers (id) ON DELETE SET NULL ON UPDATE CASCADE,
  phonefrom varchar(20)	DEFAULT '' NOT NULL,
  mailfrom varchar(255) DEFAULT '' NOT NULL,
  subject varchar(255) 	DEFAULT '' NOT NULL,
  messageid varchar(255) DEFAULT '' NOT NULL,
  inreplyto integer 	DEFAULT NULL
	CONSTRAINT rtmessages_inreplyto_fkey REFERENCES rtmessages (id) ON DELETE SET NULL ON UPDATE CASCADE,
  replyto text 		DEFAULT '' NOT NULL,
  headers text 		DEFAULT '' NOT NULL,
  body text		DEFAULT '' NOT NULL,
  createtime integer	DEFAULT 0 NOT NULL,
  type integer		DEFAULT 0 NOT NULL,
  deleted smallint	DEFAULT 0 NOT NULL,
  deltime integer	DEFAULT 0 NOT NULL,
  deluserid integer	DEFAULT NULL
	CONSTRAINT rtmessages_deluserid_fkey REFERENCES users (id) ON DELETE SET NULL ON UPDATE CASCADE,
  PRIMARY KEY (id)
);

CREATE INDEX rtmessages_ticketid_idx ON rtmessages (ticketid);

DROP SEQUENCE IF EXISTS rtrights_id_seq;
CREATE SEQUENCE rtrights_id_seq;
DROP TABLE IF EXISTS rtrights CASCADE;
CREATE TABLE rtrights (
    id integer DEFAULT nextval('rtrights_id_seq'::text) NOT NULL,
    userid integer NOT NULL
        REFERENCES users (id) ON DELETE CASCADE ON UPDATE CASCADE,
    queueid integer NOT NULL
        REFERENCES rtqueues (id) ON DELETE CASCADE ON UPDATE CASCADE,
    rights integer DEFAULT 0 NOT NULL,
    PRIMARY KEY (id),
    CONSTRAINT rtrights_userid_key UNIQUE (userid, queueid)
);

DROP TABLE IF EXISTS rtattachments CASCADE;
CREATE TABLE rtattachments (
	messageid integer 	    NOT NULL
	    REFERENCES rtmessages (id) ON DELETE CASCADE ON UPDATE CASCADE,
	filename varchar(255) 	DEFAULT '' NOT NULL,
	contenttype varchar(255) DEFAULT '' NOT NULL
);

CREATE INDEX rtattachments_message_idx ON rtattachments (messageid);

DROP SEQUENCE IF EXISTS rtcategories_id_seq;
CREATE SEQUENCE rtcategories_id_seq;
DROP TABLE IF EXISTS rtcategories CASCADE;
CREATE TABLE rtcategories (
	id integer		DEFAULT nextval('rtcategories_id_seq'::text) NOT NULL,
	name varchar(255)	DEFAULT '' NOT NULL,
	description text	DEFAULT '' NOT NULL,
	style text	DEFAULT '',
	PRIMARY KEY (id),
	UNIQUE (name)
);

DROP SEQUENCE IF EXISTS rtcategoryusers_id_seq;
CREATE SEQUENCE rtcategoryusers_id_seq;
DROP TABLE IF EXISTS rtcategoryusers CASCADE;
CREATE TABLE rtcategoryusers (
	id integer		DEFAULT nextval('rtcategoryusers_id_seq'::text) NOT NULL,
	userid integer		NOT NULL
		REFERENCES users (id) ON DELETE CASCADE ON UPDATE CASCADE,
	categoryid integer	NOT NULL
		REFERENCES rtcategories (id) ON DELETE CASCADE ON UPDATE CASCADE,
	PRIMARY KEY (id),
	CONSTRAINT rtcategories_userid_key UNIQUE (userid, categoryid)
);

DROP SEQUENCE IF EXISTS rtticketcategories_id_seq;
CREATE SEQUENCE rtticketcategories_id_seq;
DROP TABLE IF EXISTS rtticketcategories CASCADE;
CREATE TABLE rtticketcategories (
	id integer		DEFAUlT nextval('rtticketcategories_id_seq'::text) NOT NULL,
	ticketid integer	NOT NULL
		REFERENCES rttickets (id) ON DELETE CASCADE ON UPDATE CASCADE,
	categoryid integer	NOT NULL
		REFERENCES rtcategories (id) ON DELETE CASCADE ON UPDATE CASCADE,
	PRIMARY KEY (id),
	CONSTRAINT rtticketcategories_ticketid_key UNIQUE (ticketid, categoryid)
);

DROP SEQUENCE IF EXISTS rtqueuecategories_id_seq;
CREATE SEQUENCE rtqueuecategories_id_seq;
DROP TABLE IF EXISTS rtqueuecategories CASCADE;
CREATE TABLE rtqueuecategories (
	id integer DEFAULT nextval('rtqueuecategories_id_seq'::text) NOT NULL,
	queueid integer NOT NULL REFERENCES rtqueues (id) ON DELETE CASCADE ON UPDATE CASCADE,
	categoryid integer NOT NULL REFERENCES rtcategories (id) ON DELETE CASCADE ON UPDATE CASCADE,
	PRIMARY KEY (id)
);

/* ---------------------------------------------------
 Structure of table "domains"
------------------------------------------------------*/
DROP SEQUENCE IF EXISTS domains_id_seq;
CREATE SEQUENCE domains_id_seq;
DROP TABLE IF EXISTS domains CASCADE;
CREATE TABLE domains (
	id integer DEFAULT nextval('domains_id_seq'::text) NOT NULL,
	ownerid integer 	DEFAULT NULL
		CONSTRAINT domains_ownerid_fkey REFERENCES customers (id) ON DELETE SET NULL ON UPDATE CASCADE,
	name varchar(255) 	DEFAULT '' NOT NULL,
	description text 	DEFAULT '' NOT NULL,
	master varchar(128) 	DEFAULT NULL,
	last_check integer 	DEFAULT NULL,
	type varchar(6) 	DEFAULT '' NOT NULL,
	notified_serial integer DEFAULT NULL,
	account varchar(40) 	DEFAULT NULL,
	mxbackup smallint	DEFAULT 0 NOT NULL,
	PRIMARY KEY (id),
	UNIQUE (name)
);
CREATE INDEX domains_ownerid_idx ON domains (ownerid);

/* ---------------------------------------------------
 Structure of table "passwd" (accounts)
------------------------------------------------------*/
DROP SEQUENCE IF EXISTS passwd_id_seq;
CREATE SEQUENCE passwd_id_seq;
DROP TABLE IF EXISTS passwd CASCADE;
CREATE TABLE passwd (
        id integer DEFAULT nextval('passwd_id_seq'::text) NOT NULL,
	ownerid integer 	DEFAULT NULL
		CONSTRAINT passwd_ownerid_fkey REFERENCES customers (id) ON DELETE SET NULL ON UPDATE CASCADE,
	login varchar(200) 	DEFAULT '' NOT NULL,
	password varchar(200) 	DEFAULT '' NOT NULL,
	lastlogin integer 	DEFAULT 0 NOT NULL,
	uid integer 		DEFAULT 0 NOT NULL,
	home varchar(255) 	DEFAULT '' NOT NULL,
	type smallint 		DEFAULT 0 NOT NULL,
	expdate	integer		DEFAULT 0 NOT NULL,
	domainid integer	NOT NULL
		CONSTRAINT passwd_domainid_fkey REFERENCES domains (id) ON DELETE CASCADE ON UPDATE CASCADE,
	realname varchar(255)	DEFAULT '' NOT NULL,
	createtime integer	DEFAULT 0 NOT NULL,
	quota_sh integer	DEFAULT 0 NOT NULL,
	quota_mail integer	DEFAULT 0 NOT NULL,
	quota_www integer	DEFAULT 0 NOT NULL,
	quota_ftp integer	DEFAULT 0 NOT NULL,
	quota_sql integer	DEFAULT 0 NOT NULL,
	quota_cloud integer	DEFAULT 0 NOT NULL,
	mail_forward varchar(255) DEFAULT '' NOT NULL,
	mail_bcc varchar(255) 	DEFAULT '' NOT NULL,
	description text	DEFAULT '' NOT NULL,
	PRIMARY KEY (id),
	CONSTRAINT passwd_login_key UNIQUE (login, domainid)
);
CREATE INDEX passwd_ownerid_idx ON passwd (ownerid);

/* ---------------------------------------------------
 Structure of table "records" (DNS)
------------------------------------------------------*/
DROP SEQUENCE IF EXISTS records_id_seq;
CREATE SEQUENCE records_id_seq;
DROP TABLE IF EXISTS records CASCADE;
CREATE TABLE records (
	id integer		DEFAULT nextval('records_id_seq'::text) NOT NULL,
	domain_id integer	DEFAULT NULL
		REFERENCES domains (id) ON DELETE CASCADE ON UPDATE CASCADE,
	name varchar(255)	DEFAULT NULL,
	type varchar(10)		DEFAULT NULL,
	content varchar(65535)	DEFAULT NULL,
	ttl integer		DEFAULT NULL,
	prio integer		DEFAULT NULL,
	change_date integer	DEFAULT NULL,
	disabled boolean	DEFAULT '0',
	auth boolean		DEFAULT '1',
	ordername varchar(255) DEFAULT NULL,
	PRIMARY KEY (id)
);
CREATE INDEX records_name_type_idx ON records (name, type, domain_id);
CREATE INDEX records_domain_id_idx ON records (domain_id);

/* ---------------------------------------------------
 Structure of table "domainmetadata" (DNS)
------------------------------------------------------*/
DROP TABLE IF EXISTS domainmetadata CASCADE;
CREATE TABLE domainmetadata (
	id SERIAL PRIMARY KEY,
	domain_id integer
		REFERENCES domains(id) ON DELETE CASCADE ON UPDATE CASCADE,
	kind varchar(32),
	content text
);
CREATE INDEX domainidmetaindex ON domainmetadata (domain_id);

/* ---------------------------------------------------
 Structure of table "supermasters" (DNS)
------------------------------------------------------*/
DROP SEQUENCE IF EXISTS supermasters_id_seq;
CREATE SEQUENCE supermasters_id_seq;
DROP TABLE IF EXISTS supermasters CASCADE;
CREATE TABLE supermasters (
	id integer		DEFAULT nextval('supermasters_id_seq'::text) NOT NULL,
	ip inet			NOT NULL,
	nameserver varchar(255) NOT NULL,
	account varchar(40)	DEFAULT NULL,
	PRIMARY KEY (id)
);

/* ---------------------------------------------------
 Structure of table "comments" (DNS)
------------------------------------------------------*/
DROP SEQUENCE IF EXISTS comments_id_seq;
CREATE SEQUENCE comments_id_seq;
DROP TABLE IF EXISTS comments CASCADE;
CREATE TABLE comments (
	id				integer DEFAULT nextval('comments_id_seq'::text) NOT NULL,
	domain_id		integer NOT NULL
		CONSTRAINT comments_domain_id_fkey REFERENCES domains (id) ON DELETE CASCADE ON UPDATE CASCADE,
	name			varchar(255) NOT NULL,
	type			varchar(10) NOT NULL,
	modified_at		integer NOT NULL,
	account			varchar(40) DEFAULT NULL,
	comment			varchar(65535) NOT NULL,
	PRIMARY KEY (id),
	CONSTRAINT comments_lowercase_name CHECK (((name)::text = LOWER((name)::text)))
);
CREATE INDEX comments_domain_id_idx ON comments (domain_id);
CREATE INDEX comments_name_type_idx ON comments (name, type);
CREATE INDEX comments_domain_id_modified_at_idx ON comments (domain_id, modified_at);

/* ---------------------------------------------------
 Structure of table "cryptokeys" (DNS)
------------------------------------------------------*/
DROP SEQUENCE IF EXISTS cryptokeys_id_seq;
CREATE SEQUENCE cryptokeys_id_seq;
DROP TABLE IF EXISTS cryptokeys CASCADE;
CREATE TABLE cryptokeys (
	id				integer DEFAULT nextval('cryptokeys_id_seq'::text) NOT NULL,
	domain_id		integer
		CONSTRAINT cryptokeys_domain_id_fkey REFERENCES domains (id) ON DELETE CASCADE ON UPDATE CASCADE,
	flags			integer NOT NULL,
	active			boolean,
	content			text,
	PRIMARY KEY (id)
);
CREATE INDEX cryptokeys_domain_id_idx ON cryptokeys (domain_id);

/* ---------------------------------------------------
 Structure of table "tsigkeys" (DNS)
------------------------------------------------------*/
DROP SEQUENCE IF EXISTS tsigkeys_id_seq;
CREATE SEQUENCE tsigkeys_id_seq;
DROP TABLE IF EXISTS tsigkeys CASCADE;
CREATE TABLE tsigkeys (
	id				integer DEFAULT nextval('tsigkeys'::text) NOT NULL,
	name			varchar(255),
	algorithm		varchar(50),
	secret			varchar(255),
	CONSTRAINT tsigkeys_lowercase_name CHECK (((name)::text = LOWER((name)::text))),
	CONSTRAINT tsigkeys_name_key UNIQUE (name, algorithm)
);

/* ---------------------------------------------------
 Structure of table "aliases"
------------------------------------------------------*/
DROP SEQUENCE IF EXISTS aliases_id_seq;
CREATE SEQUENCE aliases_id_seq;
DROP TABLE IF EXISTS aliases CASCADE;
CREATE TABLE aliases (
	id 		integer 	DEFAULT nextval('aliases_id_seq'::text) NOT NULL,
	login 		varchar(255) 	DEFAULT '' NOT NULL,
	domainid 	integer 	NOT NULL
		CONSTRAINT aliases_domainid_fkey REFERENCES domains (id) ON DELETE CASCADE ON UPDATE CASCADE,
	PRIMARY KEY (id),
	CONSTRAINT aliases_login_key UNIQUE (login, domainid)
);

/* ---------------------------------------------------
 Structure of table "aliasassignments"
------------------------------------------------------*/
DROP SEQUENCE IF EXISTS aliasassignments_id_seq;
CREATE SEQUENCE aliasassignments_id_seq;
DROP TABLE IF EXISTS aliasassignments CASCADE;
CREATE TABLE aliasassignments (
	id              integer         DEFAULT nextval('aliasassignments_id_seq'::text) NOT NULL,
	aliasid         integer         NOT NULL
		CONSTRAINT aliasassignments_aliasid_fkey REFERENCES aliases (id) ON DELETE CASCADE ON UPDATE CASCADE,
	accountid       integer         NOT NULL
		CONSTRAINT aliasassignments_accountid_fkey REFERENCES passwd (id) ON DELETE CASCADE ON UPDATE CASCADE,
	mail_forward    varchar(255)    DEFAULT '' NOT NULL,
	PRIMARY KEY (id),
	CONSTRAINT aliasassignments_aliasid_key UNIQUE (aliasid, accountid, mail_forward)
);

/* ---------------------------------------------------
 LMS-UI Configuration table
------------------------------------------------------*/
DROP SEQUENCE IF EXISTS uiconfig_id_seq;
CREATE SEQUENCE uiconfig_id_seq;
DROP TABLE IF EXISTS uiconfig CASCADE;
CREATE TABLE uiconfig (
    id 		integer 	DEFAULT nextval('uiconfig_id_seq'::text) NOT NULL,
    section 	varchar(64) 	NOT NULL DEFAULT '',
    var 	varchar(64) 	NOT NULL DEFAULT '',
    value 	text 		NOT NULL DEFAULT '',
    description text 		NOT NULL DEFAULT '',
    disabled 	smallint 	NOT NULL DEFAULT 0,
    type 	smallint 	NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    CONSTRAINT uiconfig_section_key UNIQUE (section, var)
);

/* ---------------------------------------------------
 Structure of table "events" (Timetable)
------------------------------------------------------*/
DROP SEQUENCE IF EXISTS events_id_seq CASCADE;
CREATE SEQUENCE events_id_seq;
DROP TABLE IF EXISTS events CASCADE;
CREATE TABLE events (
	id 		integer 	DEFAULT nextval('events_id_seq'::text) NOT NULL,
	title 		varchar(255) 	DEFAULT '' NOT NULL,
	description 	text 		DEFAULT '' NOT NULL,
	note 		text 		DEFAULT '' NOT NULL,
	date 		integer 	DEFAULT 0 NOT NULL,
	begintime 	integer 	DEFAULT 0 NOT NULL,
	enddate 	integer 	DEFAULT 0 NOT NULL,
	endtime 	integer 	DEFAULT 0 NOT NULL,
	userid 		integer 	DEFAULT NULL
		CONSTRAINT events_userid_fkey REFERENCES users (id) ON DELETE SET NULL ON UPDATE CASCADE,
	customerid 	integer 	DEFAULT NULL
		CONSTRAINT events_customerid_fkey REFERENCES customers (id) ON DELETE SET NULL ON UPDATE CASCADE,
	private 	smallint 	DEFAULT 0 NOT NULL,
	closed 		smallint 	DEFAULT 0 NOT NULL,
	closeddate	integer		DEFAULT 0 NOT NULL,
	closeduserid	integer		DEFAULT NULL
		CONSTRAINT events_closeduserid_fkey REFERENCES users (id) ON DELETE SET NULL ON UPDATE CASCADE,
	creationdate	integer		DEFAULT 0 NOT NULL,
	moddate		integer		DEFAULT 0 NOT NULL,
	moduserid	integer		DEFAULT NULL
		CONSTRAINT events_moduserid_fkey REFERENCES users (id) ON DELETE SET NULL ON UPDATE CASCADE,
	type		smallint	DEFAULT 1 NOT NULL,
	nodeid		integer		DEFAULT NULL
		REFERENCES nodes (id) ON DELETE SET NULL ON UPDATE CASCADE,
	address_id integer DEFAULT NULL
		CONSTRAINT events_address_id_fkey REFERENCES addresses (id) ON UPDATE CASCADE ON DELETE SET NULL,
	ticketid integer DEFAULT NULL
		CONSTRAINT events_ticketid_fk REFERENCES rttickets (id) ON DELETE SET NULL ON UPDATE CASCADE,
	PRIMARY KEY (id)
);
CREATE INDEX events_date_idx ON events(date);
CREATE INDEX events_nodeid_idx ON events(nodeid);

/* ---------------------------------------------------
 Structure of table "events" (Timetable)
------------------------------------------------------*/
DROP TABLE IF EXISTS eventassignments CASCADE;
CREATE TABLE eventassignments (
	eventid 	integer 	NOT NULL
		CONSTRAINT eventassignments_eventid_fkey REFERENCES events (id) ON DELETE CASCADE ON UPDATE CASCADE,
	userid 		integer 	NOT NULL
		CONSTRAINT eventassignments_userid_fkey REFERENCES users (id) ON DELETE CASCADE ON UPDATE CASCADE,
	CONSTRAINT eventassignments_eventid_key UNIQUE (eventid, userid)
);

/* ---------------------------------------------------
 Structure of table "sessions"
------------------------------------------------------*/
DROP TABLE IF EXISTS sessions CASCADE;
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
 Structure of table "daemoninstances" (lmsd config)
------------------------------------------------------*/
DROP SEQUENCE IF EXISTS daemoninstances_id_seq;
CREATE SEQUENCE daemoninstances_id_seq;
DROP TABLE IF EXISTS daemoninstances CASCADE;
CREATE TABLE daemoninstances (
    id integer DEFAULT nextval('daemoninstances_id_seq'::text) NOT NULL,
    name varchar(255) 		DEFAULT '' NOT NULL,
    hostid integer 		NOT NULL
		CONSTRAINT daemoninstances_hostid_fkey REFERENCES hosts (id) ON DELETE CASCADE ON UPDATE CASCADE,
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
DROP SEQUENCE IF EXISTS daemonconfig_id_seq;
CREATE SEQUENCE daemonconfig_id_seq;
DROP TABLE IF EXISTS daemonconfig CASCADE;
CREATE TABLE daemonconfig (
    id 		integer 	DEFAULT nextval('daemonconfig_id_seq'::text) NOT NULL,
    instanceid 	integer 	NOT NULL
		CONSTRAINT daemonconfig_instanceid_fkey REFERENCES daemoninstances (id) ON DELETE CASCADE ON UPDATE CASCADE,
    var 	varchar(64) 	DEFAULT '' NOT NULL,
    value 	text 		DEFAULT '' NOT NULL,
    description text 		DEFAULT '' NOT NULL,
    disabled 	smallint 	DEFAULT 0 NOT NULL,
    PRIMARY KEY (id),
    CONSTRAINT daemonconfig_instanceid_key UNIQUE(instanceid, var)
);

/* ---------------------------------------------------
 Structure of table "docrights"
------------------------------------------------------*/
DROP SEQUENCE IF EXISTS docrights_id_seq;
CREATE SEQUENCE docrights_id_seq;
DROP TABLE IF EXISTS docrights CASCADE;
CREATE TABLE docrights (
    id          integer         DEFAULT nextval('docrights_id_seq'::text) NOT NULL,
    userid      integer         NOT NULL
		CONSTRAINT docrights_userid_fkey REFERENCES users (id) ON DELETE CASCADE ON UPDATE CASCADE,
    doctype     integer         DEFAULT 0 NOT NULL,
    rights      integer         DEFAULT 0 NOT NULL,
    PRIMARY KEY (id),
    CONSTRAINT docrights_userid_key UNIQUE (userid, doctype)
);

/* ---------------------------------------------------
 Structure of table "cashrights"
------------------------------------------------------*/
DROP SEQUENCE IF EXISTS cashrights_id_seq;
CREATE SEQUENCE cashrights_id_seq;
DROP TABLE IF EXISTS cashrights CASCADE;
CREATE TABLE cashrights (
    id 		integer 	DEFAULT nextval('cashrights_id_seq'::text) NOT NULL,
    userid 	integer 	NOT NULL
		CONSTRAINT cashrights_userid_fkey REFERENCES users (id) ON DELETE CASCADE ON UPDATE CASCADE,
    regid 	integer 	NOT NULL
		CONSTRAINT cashrights_regid_fkey REFERENCES cashregs (id) ON DELETE CASCADE ON UPDATE CASCADE,
    rights 	integer 	DEFAULT 0 NOT NULL,
    PRIMARY KEY (id),
    CONSTRAINT cashrights_userid_key UNIQUE (userid, regid)
);

/* ---------------------------------------------------
 Structure of table "cashreglog"
------------------------------------------------------*/
DROP SEQUENCE IF EXISTS cashreglog_id_seq;
CREATE SEQUENCE cashreglog_id_seq;
DROP TABLE IF EXISTS cashreglog CASCADE;
CREATE TABLE cashreglog (
    id 		integer 	DEFAULT nextval('cashreglog_id_seq'::text) NOT NULL,
    regid 	integer         NOT NULL
		CONSTRAINT cashreglog_regid_fkey REFERENCES cashregs (id) ON DELETE CASCADE ON UPDATE CASCADE,
    userid 	integer		DEFAULT NULL
		CONSTRAINT cashreglog_userid_fkey REFERENCES users (id) ON DELETE SET NULL ON UPDATE CASCADE,
    time 	integer		DEFAULT 0 NOT NULL,
    value 	numeric(9,2)    DEFAULT 0 NOT NULL,
    snapshot 	numeric(9,2)    DEFAULT 0 NOT NULL,
    description text		DEFAULT '' NOT NULL,
    PRIMARY KEY (id),
    CONSTRAINT cashreglog_regid_key UNIQUE (regid, time)
);

/* ---------------------------------------------------
 Structure of table "ewx_pt_config" (EtherWerX(R))
------------------------------------------------------*/
DROP SEQUENCE IF EXISTS ewx_pt_config_id_seq;
CREATE SEQUENCE ewx_pt_config_id_seq;
DROP TABLE IF EXISTS ewx_pt_config CASCADE;
CREATE TABLE ewx_pt_config (
    id 		integer 	DEFAULT nextval('ewx_pt_config_id_seq'::text) NOT NULL,
    nodeid 	integer         DEFAULT NULL
		CONSTRAINT ewx_pt_config_nodeid_fkey REFERENCES nodes (id) ON DELETE SET NULL ON UPDATE CASCADE,
    name 	varchar(32)     DEFAULT '' NOT NULL,
    mac 	varchar(20)     DEFAULT '' NOT NULL,
    ipaddr 	bigint          DEFAULT 0 NOT NULL,
    passwd 	varchar(32)     DEFAULT '' NOT NULL,
    PRIMARY KEY (id),
    UNIQUE (nodeid)
);

/* ---------------------------------------------------
 Structure of table "dbinfo"
------------------------------------------------------*/
DROP TABLE IF EXISTS dbinfo CASCADE;
CREATE TABLE dbinfo (
    keytype 	varchar(255) 	DEFAULT '' NOT NULL,
    keyvalue 	varchar(255) 	DEFAULT '' NOT NULL,
    PRIMARY KEY (keytype)
);

/* ---------------------------------------------------
 Structure of table "customercontacts"
------------------------------------------------------*/
DROP SEQUENCE IF EXISTS customercontacts_id_seq;
CREATE SEQUENCE customercontacts_id_seq;
DROP TABLE IF EXISTS customercontacts CASCADE;
CREATE TABLE customercontacts (
    id 		integer 	DEFAULT nextval('customercontacts_id_seq'::text) NOT NULL,
    customerid 	integer 	NOT NULL
		CONSTRAINT customercontacts_customerid_fkey REFERENCES customers (id) ON DELETE CASCADE ON UPDATE CASCADE,
    name 	varchar(255) 	NOT NULL DEFAULT '',
    contact	varchar(255) 	NOT NULL DEFAULT '',
    type    integer         DEFAULT NULL,
    PRIMARY KEY (id)
);
CREATE INDEX customercontacts_customerid_idx ON customercontacts (customerid);
CREATE INDEX customercontacts_contact_idx ON customercontacts (contact);

/* ---------------------------------------------------
 Structure of table "excludedgroups"
------------------------------------------------------*/
DROP SEQUENCE IF EXISTS excludedgroups_id_seq;
CREATE SEQUENCE excludedgroups_id_seq;
DROP TABLE IF EXISTS excludedgroups CASCADE;
CREATE TABLE excludedgroups (
	id 		integer NOT NULL DEFAULT nextval('excludedgroups_id_seq'::text),
	customergroupid integer NOT NULL
	    REFERENCES customergroups (id) ON DELETE CASCADE ON UPDATE CASCADE,
	userid 		integer NOT NULL
		CONSTRAINT excludedgroups_userid_fkey REFERENCES users (id) ON DELETE CASCADE ON UPDATE CASCADE,
	PRIMARY KEY (id),
	CONSTRAINT excludedgroups_userid_key UNIQUE (userid, customergroupid)
);
CREATE INDEX excludedgroups_customergroupid_idx ON excludedgroups (customergroupid);

/* ---------------------------------------------------
 Structure of table "messages"
------------------------------------------------------*/
DROP SEQUENCE IF EXISTS messages_id_seq;
DROP TABLE IF EXISTS messages CASCADE;
CREATE SEQUENCE messages_id_seq;
CREATE TABLE messages (
        id 	integer 	DEFAULT nextval('messages_id_seq'::text) NOT NULL,
        subject varchar(255)	DEFAULT '' NOT NULL,
	body 	text		DEFAULT '' NOT NULL,
	cdate 	integer		DEFAULT 0 NOT NULL,
	type 	smallint	DEFAULT 0 NOT NULL,
	userid 	integer		DEFAULT NULL
		CONSTRAINT messages_userid_fkey REFERENCES users (id) ON DELETE SET NULL ON UPDATE CASCADE,
	sender 	varchar(255) 	DEFAULT NULL,
        PRIMARY KEY (id)
);

CREATE INDEX messages_cdate_idx ON messages (cdate, type);
CREATE INDEX messages_userid_idx ON messages (userid);

/* ---------------------------------------------------
 Structure of table "messageitems"
------------------------------------------------------*/
DROP SEQUENCE IF EXISTS messageitems_id_seq;
DROP TABLE IF EXISTS messageitems CASCADE;
CREATE SEQUENCE messageitems_id_seq;
CREATE TABLE messageitems (
        id 		integer 	DEFAULT nextval('messageitems_id_seq'::text) NOT NULL,
	messageid 	integer		NOT NULL
		CONSTRAINT messageitems_messageid_fkey REFERENCES messages (id) ON DELETE CASCADE ON UPDATE CASCADE,
	customerid 	integer 	DEFAULT NULL
		CONSTRAINT messageitems_customerid_fkey REFERENCES customers (id) ON DELETE SET NULL ON UPDATE CASCADE,
	destination 	varchar(255) 	DEFAULT '' NOT NULL,
	lastdate 	integer		DEFAULT 0 NOT NULL,
	status 		smallint	DEFAULT 0 NOT NULL,
	error 		text		DEFAULT NULL,
	lastreaddate 	integer		DEFAULT 0 NOT NULL,
	externalmsgid	integer		DEFAULT 0 NOT NULL,
        PRIMARY KEY (id)
);

CREATE INDEX messageitems_messageid_idx ON messageitems (messageid);
CREATE INDEX messageitems_customerid_idx ON messageitems (customerid);

/* ---------------------------------------------------
 Structure of table "nastypes"
------------------------------------------------------*/
DROP SEQUENCE IF EXISTS nastypes_id_seq;
CREATE SEQUENCE nastypes_id_seq;
DROP TABLE IF EXISTS nastypes CASCADE;
CREATE TABLE nastypes (
    	id 	integer 	DEFAULT nextval('nastypes_id_seq'::text) NOT NULL,
	name 	varchar(255) 	NOT NULL,
	PRIMARY KEY (id),
	UNIQUE (name)
);

/* ---------------------------------------------------
 Structure of table "managementurls"
------------------------------------------------------*/
DROP SEQUENCE IF EXISTS managementurls_id_seq;
CREATE SEQUENCE managementurls_id_seq;
DROP TABLE IF EXISTS managementurls;
CREATE TABLE managementurls (
	id integer		DEFAULT nextval('managementurls_id_seq'::text) NOT NULL,
	netdevid integer	DEFAULT NULL
		REFERENCES netdevices (id) ON DELETE CASCADE ON UPDATE CASCADE,
	nodeid integer		DEFAULT NULL
		REFERENCES nodes (id) ON DELETE CASCADE ON UPDATE CASCADE,
	url text		DEFAULT '' NOT NULL,
	comment varchar(100)	DEFAULT NULL,
	PRIMARY KEY (id)
);

/* ---------------------------------------------------
 Structure of table "logtransactions"
------------------------------------------------------*/
DROP SEQUENCE IF EXISTS logtransactions_id_seq;
CREATE SEQUENCE logtransactions_id_seq;
DROP TABLE IF EXISTS logtransactions CASCADE;
CREATE TABLE logtransactions (
	id integer		DEFAULT nextval('logtransactions_id_seq'::text) NOT NULL,
	userid integer		DEFAULT NULL
		CONSTRAINT logtransactions REFERENCES users (id) ON DELETE SET NULL ON UPDATE CASCADE,
	time integer		DEFAULT 0 NOT NULL,
	module varchar(50)	DEFAULT '' NOT NULL,
	PRIMARY KEY (id)
);
CREATE INDEX logtransactions_userid_idx ON logtransactions (userid);
CREATE INDEX logtransactions_time_idx ON logtransactions (time);

/* ---------------------------------------------------
 Structure of table "logmessages"
------------------------------------------------------*/
DROP SEQUENCE IF EXISTS logmessages_id_seq;
CREATE SEQUENCE logmessages_id_seq;
DROP TABLE IF EXISTS logmessages CASCADE;
CREATE TABLE logmessages (
	id integer		DEFAULT nextval('logmessages_id_seq'::text) NOT NULL,
	transactionid integer	NOT NULL
		REFERENCES logtransactions (id) ON DELETE CASCADE ON UPDATE CASCADE,
	resource integer	DEFAULT 0 NOT NULL,
	operation integer	DEFAULT 0 NOT NULL,
	PRIMARY KEY (id)
);
CREATE INDEX logmessages_transactionid_idx ON logmessages (transactionid);
CREATE INDEX logmessages_resource_idx ON logmessages (resource);
CREATE INDEX logmessages_operation_idx ON logmessages (operation);

/* ---------------------------------------------------
 Structure of table "logmessagekeys"
------------------------------------------------------*/
DROP TABLE IF EXISTS logmessagekeys;
CREATE TABLE logmessagekeys (
	logmessageid integer	NOT NULL
		REFERENCES logmessages (id) ON DELETE CASCADE ON UPDATE CASCADE,
	name varchar(32)	NOT NULL,
	value integer		NOT NULL
);
CREATE INDEX logmessagekeys_logmessageid_idx ON logmessagekeys (logmessageid);
CREATE INDEX logmessagekeys_name_idx ON logmessagekeys (name);
CREATE INDEX logmessagekeys_value_idx ON logmessagekeys (value);

/* ---------------------------------------------------
 Structure of table "logmessagedata"
------------------------------------------------------*/
DROP TABLE IF EXISTS logmessagedata;
CREATE TABLE logmessagedata (
	logmessageid integer	NOT NULL
		REFERENCES logmessages (id) ON DELETE CASCADE ON UPDATE CASCADE,
	name varchar(32)	NOT NULL,
	value text		DEFAULT ''
);
CREATE INDEX logmessagedata_logmessageid_idx ON logmessagedata (logmessageid);
CREATE INDEX logmessagedata_name_idx ON logmessagedata (name);

/* ---------------------------------------------------
 Structure of table "templates"
------------------------------------------------------*/
DROP SEQUENCE IF EXISTS templates_id_seq;
CREATE SEQUENCE templates_id_seq;
DROP TABLE IF EXISTS templates CASCADE;
CREATE TABLE templates (
	id      integer		 DEFAULT nextval('templates_id_seq'::text) NOT NULL,
	type    smallint	 NOT NULL,
	name    varchar(50)	 NOT NULL,
	subject varchar(255) DEFAULT '' NOT NULL,
	message	text		 DEFAULT '' NOT NULL,
	PRIMARY KEY (id),
	UNIQUE (type, name)
);

/* ---------------------------------------------------
 Structure of table "rttemplatetypes"
------------------------------------------------------*/
DROP SEQUENCE IF EXISTS rttemplatetypes_id_seq;
CREATE SEQUENCE rttemplatetypes_id_seq;
DROP TABLE IF EXISTS rttemplatetypes;
CREATE TABLE rttemplatetypes (
	id          integer DEFAULT nextval('rttemplatetypes_id_seq'::text) NOT NULL,
	templateid  integer                                               NOT NULL
		CONSTRAINT rttemplatetypes_templateid_fkey REFERENCES templates (id) ON DELETE CASCADE ON UPDATE CASCADE,
	messagetype integer                                               NOT NULL,
	PRIMARY KEY (id),
	CONSTRAINT rttemplatetypes_templateid_key UNIQUE (templateid, messagetype)
);

/* ---------------------------------------------------
 Structure of table "rttemplatequeues"
------------------------------------------------------*/
DROP SEQUENCE IF EXISTS rttemplatequeues_id_seq;
CREATE SEQUENCE rttemplatequeues_id_seq;
DROP TABLE IF EXISTS rttemplatequeues;
CREATE TABLE rttemplatequeues (
	id          integer DEFAULT nextval('rttemplatequeues_id_seq'::text) NOT NULL,
	templateid  integer                                               NOT NULL
		CONSTRAINT rttemplatequeues_templateid_fkey REFERENCES templates (id) ON DELETE CASCADE ON UPDATE CASCADE,
	queueid  integer                                               NOT NULL
		CONSTRAINT rttemplatequeues_queueid_fkey REFERENCES rtqueues (id) ON DELETE CASCADE ON UPDATE CASCADE,
	PRIMARY KEY (id),
	CONSTRAINT rttemplatequeues_templateid_key UNIQUE (templateid, queueid)
);

/* ---------------------------------------------------
 Structure of table usergroups
------------------------------------------------------*/
DROP SEQUENCE IF EXISTS usergroups_id_seq;
CREATE SEQUENCE usergroups_id_seq;
DROP TABLE IF EXISTS usergroups CASCADE;
CREATE TABLE usergroups (
	id integer DEFAULT nextval('usergroups_id_seq'::text) NOT NULL,
	name varchar(255) DEFAULT '' NOT NULL,
	description text DEFAULT '' NOT NULL,
	PRIMARY KEY (id),
	UNIQUE (name)
);

/* ---------------------------------------------------
 Structure of table userassignments
------------------------------------------------------*/
DROP SEQUENCE IF EXISTS userassignments_id_seq;
CREATE SEQUENCE userassignments_id_seq;
DROP TABLE IF EXISTS userassignments CASCADE;
CREATE TABLE userassignments (
	id integer DEFAULT nextval('userassignments_id_seq'::text) NOT NULL,
	usergroupid integer NOT NULL
		REFERENCES usergroups (id) ON DELETE CASCADE ON UPDATE CASCADE,
	userid integer NOT NULL
		REFERENCES users (id) ON DELETE CASCADE ON UPDATE CASCADE,
	PRIMARY KEY (id),
	UNIQUE (usergroupid, userid)
);
CREATE INDEX userassignments_userid_idx ON userassignments (userid);

/* ---------------------------------------------------
 Structure of table passwdhistory
------------------------------------------------------*/
DROP SEQUENCE IF EXISTS passwdhistory_id_seq;
CREATE SEQUENCE passwdhistory_id_seq;
DROP TABLE IF EXISTS passwdhistory CASCADE;
CREATE TABLE passwdhistory (
	id integer DEFAULT nextval('passwdhistory_id_seq'::text) NOT NULL,
	userid integer NOT NULL
		REFERENCES users (id) ON DELETE CASCADE ON UPDATE CASCADE,
	hash varchar(255) DEFAULT '' NOT NULL,
	PRIMARY KEY (id)
);
CREATE INDEX passwdhistory_userid_idx ON passwdhistory (userid);

/* ---------------------------------------------------
 Structure of table filecontainers
------------------------------------------------------*/
DROP SEQUENCE IF EXISTS filecontainers_id_seq;
CREATE SEQUENCE filecontainers_id_seq;
DROP TABLE IF EXISTS filecontainers CASCADE;
CREATE TABLE filecontainers (
	id integer DEFAULT nextval('filecontainers_id_seq'::text) NOT NULL,
	creationdate integer NOT NULL DEFAULT 0,
	creatorid integer DEFAULT NULL
		CONSTRAINT filecontainers_creatorid_fkey REFERENCES users (id) ON DELETE SET NULL ON UPDATE CASCADE,
	description text NOT NULL,
	netdevid integer DEFAULT NULL
		CONSTRAINT filecontainers_netdevid_fkey REFERENCES netdevices (id) ON DELETE CASCADE ON UPDATE CASCADE,
	netnodeid integer DEFAULT NULL
		CONSTRAINT filecontainers_netnodeid_fkey REFERENCES netnodes (id) ON DELETE CASCADE ON UPDATE CASCADE,
	PRIMARY KEY (id)
);

/* ---------------------------------------------------
 Structure of table files
------------------------------------------------------*/
DROP SEQUENCE IF EXISTS files_id_seq;
CREATE SEQUENCE files_id_seq;
DROP TABLE IF EXISTS files CASCADE;
CREATE TABLE files (
	id integer DEFAULT nextval('files_id_seq'::text) NOT NULL,
	containerid integer NOT NULL
		CONSTRAINT files_containerid_fkey REFERENCES filecontainers (id) ON DELETE CASCADE ON UPDATE CASCADE,
	filename varchar(255) NOT NULL,
	contenttype varchar(255) NOT NULL,
	md5sum varchar(32) NOT NULL,
	PRIMARY KEY (id),
	CONSTRAINT files_containerid_key UNIQUE (containerid, md5sum)
);
CREATE INDEX files_md5sum_idx ON files (md5sum);

/* ---------------------------------------------------
 Structure of table "up_rights" (Userpanel)
------------------------------------------------------*/
DROP SEQUENCE IF EXISTS up_rights_id_seq;
CREATE SEQUENCE up_rights_id_seq;
DROP TABLE IF EXISTS up_rights CASCADE;
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
DROP SEQUENCE IF EXISTS up_rights_assignments_id_seq;
CREATE SEQUENCE up_rights_assignments_id_seq;
DROP TABLE IF EXISTS up_rights_assignments CASCADE;
CREATE TABLE up_rights_assignments (
	id integer 		DEFAULT nextval('up_rights_assignments_id_seq'::text) NOT NULL,
	customerid integer DEFAULT NULL
		CONSTRAINT up_rights_assignments_customerid_fkey REFERENCES customers (id) ON DELETE CASCADE ON UPDATE CASCADE,
	rightid integer 	DEFAULT 0 NOT NULL,
	PRIMARY KEY (id),
	CONSTRAINT up_rights_assignments_customerid_key UNIQUE (customerid, rightid)
);

/* ---------------------------------------------------
 Structure of table "up_customers" (Userpanel)
------------------------------------------------------*/
DROP SEQUENCE IF EXISTS up_customers_id_seq;
CREATE SEQUENCE up_customers_id_seq;
DROP TABLE IF EXISTS up_customers CASCADE;
CREATE TABLE up_customers (
	id integer 		        DEFAULT nextval('up_customers_id_seq'::text) NOT NULL,
    customerid integer 	    DEFAULT NULL
		CONSTRAINT up_customers_customerid_fkey REFERENCES customers (id) ON DELETE CASCADE ON UPDATE CASCADE,
	lastlogindate integer 	DEFAULT 0 NOT NULL,
	lastloginip varchar(16) DEFAULT '' NOT NULL,
	failedlogindate integer DEFAULT 0 NOT NULL,
	failedloginip varchar(16) DEFAULT '' NOT NULL,
	enabled smallint 	    DEFAULT 0 NOT NULL,
	PRIMARY KEY (id)
);

/* ---------------------------------------------------
 Structure of table "up_help" (Userpanel)
------------------------------------------------------*/
DROP SEQUENCE IF EXISTS up_help_id_seq;
CREATE SEQUENCE up_help_id_seq;
DROP TABLE IF EXISTS up_help CASCADE;
CREATE TABLE up_help (
    id integer 		    DEFAULT nextval('up_help_id_seq'::text) NOT NULL,
	reference integer 	DEFAULT NULL
		CONSTRAINT up_help_reference_fkey REFERENCES up_help (id) ON DELETE CASCADE ON UPDATE CASCADE,
	title varchar(128) 	DEFAULT 0 NOT NULL,
	body text 		    DEFAULT '' NOT NULL,
	PRIMARY KEY (id)
);

/* ---------------------------------------------------
 Structure of table "up_info_changes" (Userpanel)
------------------------------------------------------*/
DROP SEQUENCE IF EXISTS up_info_changes_id_seq;
CREATE SEQUENCE up_info_changes_id_seq;
DROP TABLE IF EXISTS up_info_changes CASCADE;
CREATE TABLE up_info_changes (
	id integer 		DEFAULT nextval('up_info_changes_id_seq'::text) NOT NULL,
	customerid integer 	DEFAULT NULL
		CONSTRAINT up_info_changes_customerid_fkey REFERENCES customers (id) ON DELETE CASCADE ON UPDATE CASCADE,
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

CREATE VIEW vaddresses AS
    SELECT *, country_id AS countryid, city_id AS location_city, street_id AS location_street,
        house AS location_house, flat AS location_flat,
        (TRIM(both ' ' FROM
             (CASE WHEN street IS NOT NULL THEN street ELSE city END)
             || (CASE WHEN house is NOT NULL
                     THEN (CASE WHEN flat IS NOT NULL THEN ' ' || house || '/' || flat ELSE ' ' || house END)
                     ELSE (CASE WHEN flat IS NOT NULL THEN ' ' || flat ELSE '' END)
                END)
        )) AS address,
        (TRIM(both ' ' FROM
             (CASE WHEN zip IS NOT NULL THEN zip || ' ' ELSE '' END)
             || (CASE WHEN postoffice IS NOT NULL AND postoffice <> city THEN postoffice || ', ' ELSE '' END)
                 || (CASE WHEN postoffice IS NULL OR postoffice = city OR street IS NOT NULL THEN city || ', ' ELSE '' END)
                 || (CASE WHEN street IS NOT NULL THEN street ELSE city END)
                 || (CASE WHEN house is NOT NULL
                        THEN (CASE WHEN flat IS NOT NULL THEN ' ' || house || '/' || flat ELSE ' ' || house END)
                        ELSE (CASE WHEN flat IS NOT NULL THEN ' ' || flat ELSE '' END)
                    END)
        )) AS location
    FROM addresses;

/* ---------------------------------------------------
 Structure of view "vdivisions"
------------------------------------------------------*/
CREATE VIEW vdivisions AS
    SELECT d.*,
        a.country_id as countryid, a.zip as zip, a.city as city, a.address
    FROM divisions d
        JOIN vaddresses a ON a.id = d.address_id;

CREATE VIEW vnetworks AS
    SELECT h.name AS hostname, ne.*, no.ownerid, a.city_id as location_city,
        a.street_id as location_street, a.house as location_house, a.flat as location_flat,
        no.chkmac, inet_ntoa(ne.address) || '/' || mask2prefix(inet_aton(ne.mask)) AS ip,
        no.id AS nodeid, a.location
    FROM nodes no
        LEFT JOIN networks ne ON (ne.id = no.netid)
        LEFT JOIN hosts h ON (h.id = ne.hostid)
        LEFT JOIN vaddresses a ON no.address_id = a.id
    WHERE no.ipaddr = 0 AND no.ipaddr_pub = 0;

CREATE VIEW customerview AS
    SELECT c.*,
        a1.country_id as countryid, a1.zip as zip, a1.city as city,
        a1.street as street,a1.house as building, a1.flat as apartment,
        a2.country_id as post_countryid, a2.zip as post_zip,
        a2.city as post_city, a2.street as post_street, a2.name as post_name,
        a2.house as post_building, a2.flat as post_apartment,
        a1.address as address, a1.location AS full_address,
        a1.postoffice AS postoffice,
        a2.address as post_address, a2.location AS post_full_address,
        a2.postoffice AS post_postoffice
    FROM customers c
        JOIN customer_addresses ca1 ON c.id = ca1.customer_id AND ca1.type = 1
        LEFT JOIN vaddresses a1 ON ca1.address_id = a1.id
        LEFT JOIN customer_addresses ca2 ON c.id = ca2.customer_id AND ca2.type = 0
        LEFT JOIN vaddresses a2 ON ca2.address_id = a2.id
    WHERE NOT EXISTS (
        SELECT 1 FROM customerassignments a
        JOIN excludedgroups e ON (a.customergroupid = e.customergroupid)
        WHERE e.userid = lms_current_user() AND a.customerid = c.id)
        AND c.type < 2;

CREATE VIEW contractorview AS
    SELECT c.*,
        a1.country_id as countryid, a1.zip as zip, a1.city as city, a1.street as street,
        a1.house as building, a1.flat as apartment, a2.country_id as post_countryid,
        a2.zip as post_zip, a2.city as post_city, a2.street as post_street,
        a2.house as post_building, a2.flat as post_apartment, a2.name as post_name,
        a1.address as address, a1.location AS full_address,
        a1.postoffice AS postoffice,
        a2.address as post_address, a2.location AS post_full_address,
        a2.postoffice AS post_postoffice
    FROM customers c
        JOIN customer_addresses ca1 ON c.id = ca1.customer_id AND ca1.type = 1
        LEFT JOIN vaddresses a1 ON ca1.address_id = a1.id
        LEFT JOIN customer_addresses ca2 ON c.id = ca2.customer_id AND ca2.type = 0
        LEFT JOIN vaddresses a2 ON ca2.address_id = a2.id
    WHERE c.type = 2;

CREATE VIEW customeraddressview AS
    SELECT c.*,
        a1.country_id as countryid, a1.zip as zip, a1.city as city, a1.street as street,
        a1.house as building, a1.flat as apartment, a2.country_id as post_countryid,
        a2.zip as post_zip, a2.city as post_city, a2.street as post_street,
        a2.house as post_building, a2.flat as post_apartment, a2.name as post_name,
        a1.address as address, a1.location AS full_address,
        a1.postoffice AS postoffice,
        a2.address as post_address, a2.location AS post_full_address,
        a2.postoffice AS post_postoffice
    FROM customers c
        JOIN customer_addresses ca1 ON c.id = ca1.customer_id AND ca1.type = 1
        LEFT JOIN vaddresses a1 ON ca1.address_id = a1.id
        LEFT JOIN customer_addresses ca2 ON c.id = ca2.customer_id AND ca2.type = 0
        LEFT JOIN vaddresses a2 ON ca2.address_id = a2.id
    WHERE c.type < 2;

CREATE OR REPLACE FUNCTION int2txt(bigint) RETURNS text AS $$
SELECT $1::text;
$$ LANGUAGE SQL IMMUTABLE;

CREATE VIEW nas AS
SELECT n.id, inet_ntoa(n.ipaddr) AS nasname, d.shortname, d.nastype AS type,
	d.clients AS ports, d.secret, d.community, d.description
	FROM nodes n
	JOIN netdevices d ON (n.netdev = d.id)
	WHERE n.nas = 1;

CREATE VIEW vnodes AS
    SELECT n.*, m.mac,
        a.city_id as location_city, a.street_id as location_street,
        a.house as location_house, a.flat as location_flat,
        a.location
    FROM nodes n
        LEFT JOIN (SELECT nodeid, array_to_string(array_agg(mac), ',') AS mac FROM macs GROUP BY nodeid) m ON (n.id = m.nodeid)
        LEFT JOIN vaddresses a ON n.address_id = a.id
    WHERE n.ipaddr <> 0 OR n.ipaddr_pub <> 0;

CREATE VIEW vmacs AS
    SELECT n.*, m.mac, m.id AS macid, a.city_id as location_city,
        a.street_id as location_street, a.location,
        a.house as location_building, a.flat as location_flat
    FROM nodes n
        JOIN macs m ON (n.id = m.nodeid)
        LEFT JOIN vaddresses a ON n.address_id = a.id
    WHERE n.ipaddr <> 0 OR n.ipaddr_pub <> 0;

CREATE VIEW vnodetariffs AS
	SELECT n.*,
		t.downrate, t.downceil,
		t.uprate, t.upceil,
		t.downrate_n, t.downceil_n,
		t.uprate_n, t.upceil_n,
		m.mac,
		a.city_id as location_city, a.street_id as location_street,
		a.house as location_house, a.flat as location_flat,
		a.location
	FROM nodes n
		LEFT JOIN (SELECT nodeid, array_to_string(array_agg(mac), ',') AS mac FROM macs GROUP BY nodeid) m ON (n.id = m.nodeid)
		LEFT JOIN vaddresses a ON n.address_id = a.id
		JOIN (
				 SELECT n.id AS nodeid,
						SUM(t.downrate) AS downrate,
						SUM(t.downceil) AS downceil,
						SUM(t.down_burst_time) AS down_burst_time,
						SUM(t.down_burst_threshold) AS down_burst_threshold,
						SUM(t.down_burst_limit) AS down_burst_limit,
						SUM(t.uprate) AS uprate,
						SUM(t.upceil) AS upceil,
						SUM(t.up_burst_time) AS up_burst_time,
						SUM(t.up_burst_threshold) AS up_burst_threshold,
						SUM(t.up_burst_limit) AS up_burst_limit,
						SUM(COALESCE(t.downrate_n, t.downrate)) AS downrate_n,
						SUM(COALESCE(t.downceil_n, t.downceil)) AS downceil_n,
						SUM(COALESCE(t.down_burst_time_n, t.down_burst_time)) AS down_burst_time_n,
						SUM(COALESCE(t.down_burst_threshold_n, t.down_burst_threshold)) AS down_burst_threshold_n,
						SUM(COALESCE(t.down_burst_limit_n, t.down_burst_limit)) AS down_burst_limit_n,
						SUM(COALESCE(t.uprate_n, t.uprate)) AS uprate_n,
						SUM(COALESCE(t.upceil_n, t.upceil)) AS upceil_n,
						SUM(COALESCE(t.up_burst_time_n, t.up_burst_time)) AS up_burst_time_n,
						SUM(COALESCE(t.up_burst_threshold_n, t.up_burst_threshold)) AS up_burst_threshold_n,
						SUM(COALESCE(t.up_burst_limit_n, t.up_burst_limit)) AS up_burst_limit_n
				 FROM nodes n
					 JOIN nodeassignments na ON na.nodeid = n.id
					 JOIN assignments a ON a.id = na.assignmentid
					 JOIN tariffs t ON t.id = a.tariffid
					 LEFT JOIN (
								   SELECT customerid, COUNT(id) AS allsuspended FROM assignments
								   WHERE tariffid IS NULL AND liabilityid IS NULL
										 AND datefrom <= EXTRACT(EPOCH FROM CURRENT_TIMESTAMP(0))::integer
										 AND (dateto = 0 OR dateto > EXTRACT(EPOCH FROM CURRENT_TIMESTAMP(0))::integer)
								   GROUP BY customerid
							   ) s ON s.customerid = n.ownerid
				 WHERE s.allsuspended IS NULL AND a.suspended = 0 AND a.commited = 1
					   AND a.datefrom <= EXTRACT(EPOCH FROM CURRENT_TIMESTAMP(0))::integer
					   AND (a.dateto = 0 OR a.dateto >= EXTRACT(EPOCH FROM CURRENT_TIMESTAMP(0))::integer)
					   AND (t.downrate > 0 OR t.downceil > 0 OR t.uprate > 0 OR t.upceil > 0)
				 GROUP BY n.id
			 ) t ON t.nodeid = n.id
	WHERE n.ipaddr <> 0 OR n.ipaddr_pub <> 0;

CREATE VIEW vnodealltariffs AS
	SELECT n.*,
		COALESCE(t1.downrate, t2.downrate, 0) AS downrate,
		COALESCE(t1.downceil, t2.downceil, 0) AS downceil,
		COALESCE(t1.down_burst_time, t2.down_burst_time, 0) AS down_burst_time,
		COALESCE(t1.down_burst_threshold, t2.down_burst_threshold, 0) AS down_burst_threshold,
		COALESCE(t1.down_burst_limit, t2.down_burst_limit, 0) AS down_burst_limit,
		COALESCE(t1.uprate, t2.uprate, 0) AS uprate,
		COALESCE(t1.upceil, t2.upceil, 0) AS upceil,
		COALESCE(t1.up_burst_time, t2.up_burst_time, 0) AS up_burst_time,
		COALESCE(t1.up_burst_threshold, t2.up_burst_threshold, 0) AS up_burst_threshold,
		COALESCE(t1.up_burst_limit, t2.up_burst_limit, 0) AS up_burst_limit,
		COALESCE(t1.downrate_n, t2.downrate_n, 0) AS downrate_n,
		COALESCE(t1.downceil_n, t2.downceil_n, 0) AS downceil_n,
		COALESCE(t1.down_burst_time_n, t2.down_burst_time_n, 0) AS down_burst_time_n,
		COALESCE(t1.down_burst_threshold_n, t2.down_burst_threshold_n, 0) AS down_burst_threshold_n,
		COALESCE(t1.down_burst_limit_n, t2.down_burst_limit_n, 0) AS down_burst_limit_n,
		COALESCE(t1.uprate_n, t2.uprate_n, 0) AS uprate_n,
		COALESCE(t1.upceil_n, t2.upceil_n, 0) AS upceil_n,
		COALESCE(t1.up_burst_time_n, t2.up_burst_time_n, 0) AS up_burst_time_n,
		COALESCE(t1.up_burst_threshold_n, t2.up_burst_threshold_n, 0) AS up_burst_threshold_n,
		COALESCE(t1.up_burst_limit_n, t2.up_burst_limit_n, 0) AS up_burst_limit_n,
		m.mac,
		a.city_id as location_city, a.street_id as location_street,
		a.house as location_house, a.flat as location_flat,
		a.location
	FROM nodes n
		LEFT JOIN (
					  SELECT nodeid, array_to_string(array_agg(mac), ',') AS mac
					  FROM macs
					  GROUP BY nodeid
				  ) m ON n.id = m.nodeid
		LEFT JOIN vaddresses a ON a.id = n.address_id
		LEFT JOIN (
					  SELECT n.id AS nodeid, SUM(t.downrate) AS downrate, SUM(t.downceil) AS downceil,
							 SUM(t.down_burst_time) AS down_burst_time,
							 SUM(t.down_burst_threshold) AS down_burst_threshold,
							 SUM(t.down_burst_limit) AS down_burst_limit,
							 SUM(t.uprate) AS uprate, SUM(t.upceil) AS upceil,
							 SUM(t.up_burst_time) AS up_burst_time,
							 SUM(t.up_burst_threshold) AS up_burst_threshold,
							 SUM(t.up_burst_limit) AS up_burst_limit,
							 SUM(COALESCE(t.downrate_n, t.downrate)) AS downrate_n,
							 SUM(COALESCE(t.downceil_n, t.downceil)) AS downceil_n,
							 SUM(COALESCE(t.down_burst_time_n, t.down_burst_time)) AS down_burst_time_n,
							 SUM(COALESCE(t.down_burst_threshold_n, t.down_burst_threshold)) AS down_burst_threshold_n,
							 SUM(COALESCE(t.down_burst_limit_n, t.down_burst_limit)) AS down_burst_limit_n,
							 SUM(COALESCE(t.uprate_n, t.uprate)) AS uprate_n,
							 SUM(COALESCE(t.upceil_n, t.upceil)) AS upceil_n,
							 SUM(COALESCE(t.up_burst_time_n, t.up_burst_time)) AS up_burst_time_n,
							 SUM(COALESCE(t.up_burst_threshold_n, t.up_burst_threshold)) AS up_burst_threshold_n,
							 SUM(COALESCE(t.up_burst_limit_n, t.up_burst_limit)) AS up_burst_limit_n
					  FROM nodes n
						  JOIN nodeassignments na ON na.nodeid = n.id
						  JOIN assignments a ON a.id = na.assignmentid
						  JOIN tariffs t ON t.id = a.tariffid
						  LEFT JOIN (
										SELECT customerid, COUNT(id) AS allsuspended FROM assignments
										WHERE tariffid IS NULL AND liabilityid IS NULL
											  AND datefrom <= EXTRACT(EPOCH FROM CURRENT_TIMESTAMP(0))::integer
											  AND (dateto = 0 OR dateto > EXTRACT(EPOCH FROM CURRENT_TIMESTAMP(0))::integer)
										GROUP BY customerid
									) s ON s.customerid = n.ownerid
					  WHERE s.allsuspended IS NULL AND a.suspended = 0 AND a.commited = 1
							AND a.datefrom <= EXTRACT(EPOCH FROM CURRENT_TIMESTAMP(0))::integer
							AND (a.dateto = 0 OR a.dateto >= EXTRACT(EPOCH FROM CURRENT_TIMESTAMP(0))::integer)
							AND (t.downrate > 0 OR t.downceil > 0 OR t.uprate > 0 OR t.upceil > 0)
					  GROUP BY n.id
				  ) t1 ON t1.nodeid = n.id
		LEFT JOIN (
					  SELECT n.id AS nodeid, SUM(t.downrate) AS downrate, SUM(t.downceil) AS downceil,
							 SUM(t.down_burst_time) AS down_burst_time,
							 SUM(t.down_burst_threshold) AS down_burst_threshold,
							 SUM(t.down_burst_limit) AS down_burst_limit,
							 SUM(t.uprate) AS uprate, SUM(t.upceil) AS upceil,
							 SUM(t.up_burst_time) AS up_burst_time,
							 SUM(t.up_burst_threshold) AS up_burst_threshold,
							 SUM(t.up_burst_limit)AS up_burst_limit,
							 SUM(CASE WHEN t.downrate_n IS NOT NULL THEN t.downrate_n ELSE t.downrate END) AS downrate_n,
							 SUM(CASE WHEN t.downceil_n IS NOT NULL THEN t.downceil_n ELSE t.downceil END) AS downceil_n,
							 SUM(CASE WHEN t.down_burst_time_n IS NOT NULL THEN t.down_burst_time_n ELSE t.down_burst_time END) AS down_burst_time_n,
							 SUM(CASE WHEN t.down_burst_threshold_n IS NOT NULL THEN t.down_burst_threshold_n ELSE t.down_burst_threshold END) AS down_burst_threshold_n,
							 SUM(CASE WHEN t.down_burst_limit_n IS NOT NULL THEN t.down_burst_limit_n ELSE t.down_burst_limit END) AS down_burst_limit_n,
							 SUM(CASE WHEN t.uprate_n IS NOT NULL THEN t.uprate_n ELSE t.uprate END) AS uprate_n,
							 SUM(CASE WHEN t.upceil_n IS NOT NULL THEN t.upceil_n ELSE t.upceil END) AS upceil_n,
							 SUM(CASE WHEN t.up_burst_time_n IS NOT NULL THEN t.up_burst_time_n ELSE t.up_burst_time END) AS up_burst_time_n,
							 SUM(CASE WHEN t.up_burst_threshold_n IS NOT NULL THEN t.up_burst_threshold_n ELSE t.up_burst_threshold END) AS up_burst_threshold_n,
							 SUM(CASE WHEN t.up_burst_limit_n IS NOT NULL THEN t.up_burst_limit_n ELSE t.up_burst_limit END) AS up_burst_limit_n
					  FROM assignments a
						  JOIN tariffs t ON t.id = a.tariffid
						  JOIN (
								   SELECT vn.id,
									   (CASE WHEN nd.id IS NULL THEN vn.ownerid ELSE nd.ownerid END) AS ownerid
								   FROM vnodes vn
									   LEFT JOIN netdevices nd ON nd.id = vn.netdev AND vn.ownerid IS NULL AND nd.ownerid IS NOT NULL
								   WHERE (vn.ownerid IS NOT NULL AND nd.id IS NULL)
										 OR (vn.ownerid IS NULL AND nd.id IS NOT NULL)
							   ) n ON n.ownerid = a.customerid
						  LEFT JOIN (
										SELECT customerid, COUNT(id) AS allsuspended FROM assignments
										WHERE tariffid IS NULL AND liabilityid IS NULL
											  AND datefrom <= EXTRACT(EPOCH FROM CURRENT_TIMESTAMP(0))::integer
											  AND (dateto = 0 OR dateto > EXTRACT(EPOCH FROM CURRENT_TIMESTAMP(0))::integer)
										GROUP BY customerid
									) s ON s.customerid = a.customerid
					  WHERE s.allsuspended IS NULL AND a.suspended = 0 AND a.commited = 1
							AND a.datefrom <= EXTRACT(EPOCH FROM CURRENT_TIMESTAMP(0))::integer
							AND (a.dateto = 0 OR a.dateto >= EXTRACT(EPOCH FROM CURRENT_TIMESTAMP(0))::integer)
							AND (t.downrate > 0 OR t.downceil > 0 OR t.uprate > 0 OR t.upceil > 0)
							AND n.id NOT IN (SELECT nodeid FROM nodeassignments)
							AND a.id NOT IN (SELECT assignmentid FROM nodeassignments)
					  GROUP BY n.id
				  ) t2 ON t2.nodeid = n.id
	WHERE (n.ipaddr <> 0 OR n.ipaddr_pub <> 0)
		  AND ((t1.nodeid IS NOT NULL AND t2.nodeid IS NULL)
			   OR (t1.nodeid IS NULL AND t2.nodeid IS NOT NULL)
			   OR (t1.nodeid IS NULL AND t2.nodeid IS NULL));

CREATE VIEW teryt_terc AS
SELECT ident AS woj, 0::text AS pow, 0::text AS gmi, 0 AS rodz,
        UPPER(name) AS nazwa
    FROM location_states
    UNION
    SELECT s.ident AS woj, d.ident AS pow, 0::text AS gmi, 0 AS rodz,
        d.name AS nazwa
    FROM location_districts d
    JOIN location_states s ON (d.stateid = s.id)
    UNION
    SELECT s.ident AS woj, d.ident AS pow, b.ident AS gmi, b.type AS rodz,
        b.name AS nazwa
    FROM location_boroughs b
    JOIN location_districts d ON (b.districtid = d.id)
    JOIN location_states s ON (d.stateid = s.id);

CREATE VIEW teryt_simc AS
SELECT s.ident AS woj, d.ident AS pow, b.ident AS gmi, b.type AS rodz_gmi,
        c.ident AS sym, c.id AS cityid, c.name AS nazwa,
        COALESCE(cc.ident, c.ident) AS sympod,
        COALESCE(cc.id, c.id) AS subcityid
    FROM location_cities c
    JOIN location_boroughs b ON (c.boroughid = b.id)
    JOIN location_districts d ON (b.districtid = d.id)
    JOIN location_states s ON (d.stateid = s.id)
    LEFT JOIN location_cities cc ON (c.cityid = cc.id);

CREATE VIEW teryt_ulic AS
SELECT st.ident AS woj, d.ident AS pow, b.ident AS gmi, b.type AS rodz_gmi,
        c.ident AS sym, s.ident AS sym_ul, s.name AS nazwa_1, s.name2 AS nazwa_2, t.name AS cecha, s.id
    FROM location_streets s
    JOIN location_street_types t ON (s.typeid = t.id)
    JOIN location_cities c ON (s.cityid = c.id)
    JOIN location_boroughs b ON (c.boroughid = b.id)
    JOIN location_districts d ON (b.districtid = d.id)
    JOIN location_states st ON (d.stateid = st.id);

CREATE VIEW customermailsview AS
		SELECT customerid, array_to_string(array_agg(contact), ',') AS email
			FROM customercontacts
			WHERE (type & 8) > 0 AND contact <> ''
			GROUP BY customerid;

CREATE VIEW vusers AS
	SELECT *, (firstname || ' ' || lastname) AS name, (lastname || ' ' || firstname) AS rname
	FROM users;

/* ---------------------------------------------------
 Data records
------------------------------------------------------*/
INSERT INTO rtcategories (name, description) VALUES ('default', 'default category');
INSERT INTO up_rights(module, name, description)
        VALUES ('info', 'edit_addr_ack', 'Customer can change address information with admin acknowlegment');
INSERT INTO up_rights(module, name, description)
        VALUES ('info', 'edit_addr', 'Customer can change address information');
INSERT INTO up_rights(module, name, description, setdefault)
        VALUES ('info', 'edit_contact_ack', 'Customer can change contact information with admin acknowlegment', 0);
INSERT INTO up_rights(module, name, description)
        VALUES ('info', 'edit_contact', 'Customer can change contact information');

INSERT INTO countries (name) VALUES
('Lithuania'),
('Poland'),
('Romania'),
('Slovakia'),
('USA');

INSERT INTO nastypes (name) VALUES
('mikrotik_snmp'),
('cisco'),
('computone'),
('livingston'),
('max40xx'),
('multitech'),
('netserver'),
('pathras'),
('patton'),
('portslave'),
('tc'),
('usrhiper'),
('other');

INSERT INTO uiconfig (section, var, value, description, disabled) VALUES
('phpui', 'default_autosuggest_placement','bottom','',0),
('phpui', 'autosuggest_max_length','40','',0),
('phpui', 'lang', '', '', 0),
('phpui', 'allow_from', '', '', 0),
('phpui', 'default_module', 'welcome', '', 0),
('phpui', 'timeout', '600', '', 0),
('phpui', 'settings_timeout', '28800', '', 0),
('phpui', 'customerlist_pagelimit', '100', '', 0),
('phpui', 'billinglist_pagelimit', '100', '', 0),
('phpui', 'nodelist_pagelimit', '100', '', 0),
('phpui', 'balancelist_pagelimit', '100', '', 0),
('phpui', 'invoicelist_pagelimit', '100', '', 0),
('phpui', 'debitnotelist_pagelimit', '100', '', 0),
('phpui', 'ticketlist_pagelimit', '100', '', 0),
('phpui', 'accountlist_pagelimit', '100', '', 0),
('phpui', 'domainlist_pagelimit', '100', '', 0),
('phpui', 'aliaslist_pagelimit', '100', '', 0),
('phpui', 'configlist_pagelimit', '100', '', 0),
('phpui', 'receiptlist_pagelimit', '100', '', 0),
('phpui', 'taxratelist_pagelimit', '100', '', 0),
('phpui', 'numberplanlist_pagelimit', '100', '', 0),
('phpui', 'divisionlist_pagelimit', '100', '', 0),
('phpui', 'documentlist_pagelimit', '100', '', 0),
('phpui', 'voipaccountlist_pagelimit', '100', '', 0),
('phpui', 'networkhosts_pagelimit', '256', '', 0),
('phpui', 'messagelist_pagelimit', '100', '', 0),
('phpui', 'recordlist_pagelimit', '100', '', 0),
('phpui', 'cashreglog_pagelimit', '100', '', 0),
('phpui', 'networklist_pagelimit', '100', '', 0),
('phpui', 'reload_type', 'sql', '', 0),
('phpui', 'reload_execcmd', '/bin/true', '', 0),
('phpui', 'reload_sqlquery', '', '', 0),
('phpui', 'lastonline_limit', '600', '', 0),
('phpui', 'timetable_days_forward', '7', '', 0),
('phpui', 'gd_translate_to', 'ISO-8859-2', '', 0),
('phpui', 'check_for_updates_period', '86400', '', 0),
('phpui', 'homedir_prefix', '/home/', '', 0),
('phpui', 'default_taxrate', '23', '', 0),
('phpui', 'default_zip', '', '', 0),
('phpui', 'default_city', '', '', 0),
('phpui', 'default_address', '', '', 0),
('phpui', 'smarty_debug', 'false', '', 0),
('phpui', 'force_ssl', 'false', '', 0),
('phpui', 'allow_mac_sharing', 'false', '', 0),
('phpui', 'big_networks', 'false', '', 0),
('phpui', 'short_pagescroller', 'false', '', 0),
('phpui', 'helpdesk_stats', 'true', '', 0),
('phpui', 'helpdesk_customerinfo', 'true', '', 0),
('phpui', 'helpdesk_customerinfo_mail_body', '--
Klient: %custname ID: %cid
Adres: %address
E-mail: %email
Telefon: %phone', '', 0),
('phpui', 'helpdesk_customerinfo_sms_body', 'Klient: %custname ID: %cid Adres: %address Telefon: %phone', '', 0),
('phpui', 'helpdesk_backend_mode', 'false', '', 0),
('phpui', 'helpdesk_sender_name', '', '', 0),
('phpui', 'helpdesk_reply_body', 'false', '', 0),
('phpui', 'helpdesk_notification_mail_subject', '[RT#%tid] %subject', '', 0),
('phpui', 'helpdesk_notification_mail_body', '%body

URL: %url

%customerinfo', '', 0),
('phpui', 'helpdesk_notification_sms_body', '[RT#%tid] %subject: %body %customerinfo', '', 0),
('phpui', 'use_invoices', 'false', '', 0),
('phpui', 'ticket_template_file', 'rtticketprint.html', '', 0),
('phpui', 'use_current_payday', 'false', '', 0),
('phpui', 'default_monthly_payday', '', '', 0),
('phpui', 'newticket_notify', 'false', '', 0),
('phpui', 'to_words_short_version', 'false', '', 0),
('phpui', 'ticketlist_status', '', '', 0),
('phpui', 'ewx_support', 'false', '', 0),
('phpui', 'invoice_check_payment', 'false', '', 0),
('phpui', 'note_check_payment', 'false', '', 0),
('phpui', 'radius', '1', '', 0),
('phpui', 'public_ip', '1', '', 0),
('phpui', 'default_assignment_period', '3', '', 0),
('phpui', 'default_assignment_invoice', '0', '', 0),
('phpui', 'default_editor', 'html', '', 0),
('phpui', 'logging', 'false', '', 0),
('phpui', 'hide_toolbar', 'false', '', 0),
('phpui', 'add_customer_group_required', 'false', '', 0),
('phpui', 'document_margins', '10,5,15,5', '', 0),
('phpui', 'quicksearch_limit', '15', '', 0),
('phpui', 'ping_type', '1', '', 0),
('phpui', 'default_teryt_city', 'false', '', 0),
('phpui', 'passwordhistory', 6, '', 0),
('phpui', 'event_usergroup_selection_type', 'update', '', 0),
('payments', 'date_format', '%Y/%m/%d', '', 0),
('payments', 'default_unit_name', 'pcs.', '', 0),
('voip', 'default_cost_limit', '200', '', 2),
('invoices', 'template_file', 'invoice.html', '', 0),
('invoices', 'content_type', 'text/html', '', 0),
('invoices', 'cnote_template_file', 'invoice.html', '', 0),
('invoices', 'print_balance_history', 'false', '', 0),
('invoices', 'print_balance_history_limit', '10', '', 0),
('invoices', 'default_printpage', 'original,copy', '', 0),
('invoices', 'type', 'html', '', 0),
('invoices', 'attachment_name', '', '', 0),
('invoices', 'paytime', '14', '', 0),
('invoices', 'paytype', '1', '', 0),
('notes', 'template_file', 'note.html', '', 0),
('notes', 'content_type', 'text/html', '', 0),
('notes', 'type', 'html', '', 0),
('notes', 'attachment_name', '', '', 0),
('notes', 'paytime', '14', '', 0),
('receipts', 'template_file', 'receipt.html', '', 0),
('receipts', 'content_type', 'text/html', '', 0),
('receipts', 'type', 'html', '', 0),
('receipts', 'attachment_name', '', '', 0),
('finances', 'suspension_percentage', '0', '', 0),
('mail', 'debug_email', '', '', 0),
('mail', 'smtp_host', '127.0.0.1', '', 0),
('mail', 'smtp_port', '25', '', 0),
('mail', 'backend', 'pear', '', 0),
('mail', 'smtp_secure', 'tls', '', 0),
('zones', 'hostmaster_mail', 'hostmaster.localhost', '', 0),
('zones', 'master_dns', 'localhost', '', 0),
('zones', 'slave_dns', 'localhost', '', 0),
('zones', 'default_ttl', '3600', '', 0),
('zones', 'ttl_refresh', '28800', '', 0),
('zones', 'ttl_retry', '7200', '', 0),
('zones', 'ttl_expire', '604800', '', 0),
('zones', 'ttl_minimum', '86400', '', 0),
('zones', 'default_webserver_ip', '127.0.0.1', '', 0),
('zones', 'default_mailserver_ip', '127.0.0.1', '', 0),
('zones', 'default_mx', 'localhost', '', 0),
('userpanel', 'data_consent_text', '', '', 0),
('userpanel', 'disable_transferform', '0', '', 0),
('userpanel', 'disable_invoices', '0', '', 0),
('userpanel', 'invoice_duplicate', '0', '', 0),
('userpanel', 'show_tariffname', '1', '', 0),
('userpanel', 'show_speeds', '1', '', 0),
('userpanel', 'show_period', '1', '', 0),
('userpanel', 'queues', '1', '', 0),
('userpanel', 'tickets_from_selected_queues', '0', '', 0),
('userpanel', 'allow_message_add_to_closed_tickets', '1', '', 0),
('userpanel', 'limit_ticket_movements_to_selected_queues', '0', '', 0),
('userpanel', 'default_userid', '0', '', 0),
('userpanel', 'debug_email', '', '', 0),
('userpanel', 'lms_url', '', '', 0),
('userpanel', 'hide_nodesbox', '0', '', 0),
('userpanel', 'hide_documentbox', '0', '', 0),
('userpanel', 'logout_url', '', '', 0),
('userpanel', 'owner_stats', '1', '', 0),
('userpanel', 'default_categories', '1', '', 0),
('userpanel', 'auth_type', '1', '', 0),
('userpanel', 'show_confirmed_documents_only', 'false', '', 0),
('userpanel', 'module_order', '', '', 0),
('userpanel', 'visible_ticket_sources', '0;1;2;3;4;5;6;7', '', 0),
('userpanel', 'change_notification_mail_sender', '', '', 0),
('userpanel', 'change_notification_mail_recipient', '', '', 0),
('userpanel', 'change_notification_mail_subject', '', '', 0),
('userpanel', 'change_notification_mail_body', '', '', 0),
('userpanel', 'change_confirmation_mail_subject', '', '', 0),
('userpanel', 'change_confirmation_mail_body', '', '', 0),
('userpanel', 'change_rejection_mail_subject', '', '', 0),
('userpanel', 'change_rejection_mail_body', '', '', 0),
('userpanel', 'google_recaptcha_sitekey', '', '', 0),
('userpanel', 'google_recaptcha_secret', '', '', 0),
('userpanel', 'allow_reopen_tickets_newer_than', '0', '', 0),
('userpanel', 'pin_changes', 'true', '', 0),
('userpanel', 'show_last_years', '5', '', 0),
('userpanel', 'aggregate_documents', '0', '', 0),
('userpanel', 'speed_unit_type', '1000', '', 0),
('userpanel', 'speed_unit_aggregation_threshold', '5', '', 0),
('userpanel', 'shortcut_icon', '', '', 0),
('directories', 'userpanel_dir', 'userpanel', '', 0);

INSERT INTO invprojects (name, type) VALUES ('inherited', 1);

INSERT INTO netdeviceproducers (id, name) VALUES
(1, 'Mikrotik'),
(2, 'Ubiquiti');

INSERT INTO netdevicemodels (name, alternative_name, netdeviceproducerid) VALUES
('RB750', NULL, 1),
('RB750UP', NULL, 1),
('RB750GL', NULL, 1),
('PowerBox', NULL, 1),
('RB2011iL-IN', NULL, 1),
('RB2011iL-RM', NULL, 1),
('RB2011iLS-IN', NULL, 1),
('RB2011UiAS-IN', NULL, 1),
('RB2011UiAS-RM', NULL, 1),
('RB1100AHx2', NULL, 1),
('CCR1009-8G-1S', NULL, 1),
('CCR1009-8G-1S-1S+', NULL, 1),
('CCR1009-8G-1S-1S+PC', NULL, 1),
('CCR1016-12S-1S+', NULL, 1),
('CCR1016-12G', NULL, 1),
('CCR1036-12G-4S', NULL, 1),
('CCR1036-8G-2S+', NULL, 1),
('CCR1036-12G-4S-EM', NULL, 1),
('CCR1036-8G-2S+EM', NULL, 1),
('RB1100AHx2-LM', NULL, 1),
('RB750G', NULL, 1),
('RB1000', NULL, 1),
('RB1000U', NULL, 1),
('RB1100', NULL, 1),
('RB1100Hx2', NULL, 1),
('RB1100AH', NULL, 1),
('RB1200', NULL, 1),
('RB260GS', NULL, 1),
('RB260GSP', NULL, 1),
('CRS109-8G-1S-2HnD-IN', NULL, 1),
('CRS210-8G-2S+IN', NULL, 1),
('CRS212-1G-10S-1S+IN', NULL, 1),
('CRS125-24G-1S-IN', NULL, 1),
('CRS125-24G-1S-RM', NULL, 1),
('CRS125-24G-1S-2HnD-IN', NULL, 1),
('CRS226-24G-2S+IN', NULL, 1),
('CRS226-24G-2S+RM', NULL, 1),
('RB250GS', NULL, 1),
('NetMetal 5', NULL, 1),
('SXT 5 ac', NULL, 1),
('SXT SA5 ac', NULL, 1),
('NetBox 5', NULL, 1),
('SXT Lite2', NULL, 1),
('SXT Lite5', NULL, 1),
('Groove 52HPn', NULL, 1),
('GrooveA 52HPn', NULL, 1),
('SXT 2', NULL, 1),
('SXT 5', NULL, 1),
('BaseBox 2', NULL, 1),
('BaseBox 5', NULL, 1),
('OmniTIK U-5HnD', NULL, 1),
('SXT HG5', NULL, 1),
('SXT SA5', NULL, 1),
('OmniTIK UPA-5HnD', NULL, 1),
('Metal 9HPn', NULL, 1),
('Metal 2SHPn', NULL, 1),
('Metal 5SHPn', NULL, 1),
('SEXTANT G 5HPnD', NULL, 1),
('QRT 2', NULL, 1),
('QRT 5', NULL, 1),
('SXT Sixpack', NULL, 1),
('SXT 5HnD', NULL, 1),
('SXT G-5HnD', NULL, 1),
('RB951-2n', NULL, 1),
('mAP 2n', NULL, 1),
('cAP 2n', NULL, 1),
('RB951Ui-2HnD', NULL, 1),
('RB951G-2HnD', NULL, 1),
('RB2011UiAS-2HnD-IN', NULL, 1),
('RB751U-2HnD', NULL, 1),
('RB751G-2HnD', NULL, 1),
('911 Lite2', NULL, 1),
('911 Lite5', NULL, 1),
('RB411L', NULL, 1),
('911 Lite5 dual', NULL, 1),
('RB411', NULL, 1),
('RB911G-2HPnD', NULL, 1),
('RB450', NULL, 1),
('RB411GL', NULL, 1),
('RB911G-5HPnD', NULL, 1),
('RB411U', NULL, 1),
('RB911G-5HPacD', NULL, 1),
('RB912UAG-2HPnD', NULL, 1),
('RB912UAG-5HPnD', NULL, 1),
('RB411AR', NULL, 1),
('RB433UL', NULL, 1),
('RB433', NULL, 1),
('RB450G', NULL, 1),
('RB411AH', NULL, 1),
('RB922UAGS-5HPacD', NULL, 1),
('RB850Gx2', NULL, 1),
('RB433GL', NULL, 1),
('RB433AH', NULL, 1),
('RB953GS-5HnT-RP', NULL, 1),
('RB493AH', NULL, 1),
('RB435G', NULL, 1),
('RB493G', NULL, 1),
('RB800', NULL, 1),
('RB433UAH', NULL, 1),
('RB411UAHL', NULL, 1),
('RB493', NULL, 1),
('RB711-5Hn', NULL, 1),
('RB711-2Hn', NULL, 1),
('RB2011L', NULL, 1),
('RB2011LS', NULL, 1),
('RB433L', NULL, 1),
('RB2011UiAS-2HnD', NULL, 1),
('RB711-5Hn-U', NULL, 1),
('RB411UAHR', NULL, 1),
('RB433UAHL', NULL, 1),
('RB711GA-5HnD', NULL, 1),
('RB711A-5Hn-M', NULL, 1),
('CCR1016-12G-BU', NULL, 1),
('RB411R', NULL, 1),
('RB711-5Hn-M', NULL, 1),
('RB600A', NULL, 1),
('Crossroads', NULL, 1),
('RB411A', NULL, 1),
('CA411-711', NULL, 1),
('CA150', NULL, 1),
('CA433U', NULL, 1),
('CA493', NULL, 1),
('CA800', NULL, 1),
('CAOTS', NULL, 1),
('CAOTU', NULL, 1),
('S-RJ01', NULL, 1),
('S-85DLC05D', NULL, 1),
('S-31DLC20D', NULL, 1),
('S-3553LC20D', NULL, 1),
('S+85DLC03D', NULL, 1),
('S+31DLC10D', NULL, 1),
('S+2332LC10D', NULL, 1),
('SFP+ 1m direct attach cable', NULL, 1),
('SFP+ 3m direct attach cable', NULL, 1),
('R11e-2HnD', NULL, 1),
('R11e-2HPnD', NULL, 1),
('R11e-5HnD', NULL, 1),
('R11e-5HacD', NULL, 1),
('R2SHPn', NULL, 1),
('R5SHPn', NULL, 1),
('R52nM', NULL, 1),
('R52H', NULL, 1),
('R52Hn', NULL, 1),
('RB14e', NULL, 1),
('RB14eU', NULL, 1),
('RB44Ge', NULL, 1),
('RB502', NULL, 1),
('RB604', NULL, 1),
('IAMP1E', NULL, 1),
('R5H', NULL, 1),
('R5nH', NULL, 1),
('R2N', NULL, 1),
('R52N', NULL, 1),
('RB44GV', NULL, 1),
('R52', NULL, 1),
('IAMP1', NULL, 1),
('FTC', NULL, 1),
('Flex-guide', NULL, 1),
('MMCX-RPSMA pigtail', NULL, 1),
('2.4Ghz Dipole', NULL, 1),
('RB2011 mount', NULL, 1),
('48 to 24V PoE Converter', NULL, 1),
('18POW', NULL, 1),
('24HPOW', NULL, 1),
('48POW', NULL, 1),
('5VUSB', NULL, 1),
('RBPOE', NULL, 1),
('RBGPOE', NULL, 1),
('ACUFL', NULL, 1),
('ACMMCX', NULL, 1),
('ACSWI', NULL, 1),
('ACSWIM', NULL, 1),
('Radome Cover Kit', NULL, 1),
('mANT30 PA', NULL, 1),
('mANT30', NULL, 1);

INSERT INTO netdevicemodels (name, alternative_name, netdeviceproducerid) VALUES
('3391-A', 'SR71A', 2),
('AF-24', 'AIRFIBER 1.4GBPS+ BACKHAUL 24GHZ (SHIPPED AS SINGLES)', 2),
('AF-5', 'AIRFIBER 1GBPS+ BACKHAUL 5.4-5.8GHZ', 2),
('AG-HP-2G16', '2.4GHZ AIRGRID AIRMAX 16DBI', 2),
('AG-HP-2G20', '5PK 2.4GHZ AIRGRID AIRMAX 20DBI', 2),
('AG-HP-5G23', '5PK 5GHZ AIRGRID AIRMAX 23DBI', 2),
('AG-HP-5G27', '5PK 5GHZ AIRGRID AIRMAX 27DBI', 2),
('AIRCAM', 'AIRCAM', 2),
('AIRCAM-3', 'AIRCAM 3-PACK', 2),
('AIRCAM-DOME', 'AIRCAM DOME', 2),
('AIRCAM-DOME-3', 'AIRCAM-DOME 3PK SOLUTION 3 PACK', 2),
('AIRCAM-MINI', 'AIRCAM-MINI VIDEO SOLUTION', 2),
('AIRGATEWAY', 'AIRGATEWAY INDOOR AP', 2),
('AIRGATEWAY-LR', 'AIRGATEWAY INDOOR AP LONG RANGE ANTENNA', 2),
('AIRROUTER', 'AIRROUTER INDOOR AP', 2),
('AIRROUTER-HP', 'AIRROUTER INDOOR AP HP EXT ANT', 2),
('AIRVISION-C', 'AIRVISION NVR CONTROLLER', 2),
('AM-2G15-120', '2.3-2.7GHZ AIRMAX BASE STATION 15DBI 120 DEG W/ ROCKET KIT', 2),
('AM-2G16-90', '2.3-2.7GHZ AIRMAX BASE STATION 16DBI 90DEG W/ ROCKET KIT', 2),
('AM-3G18-120', '3.3-3.8GHZ AIRMAX BASE STATION 18DBI 120 DEG W/ROCKET KIT', 2),
('AM-5AC21-60', '5G AIRMAX AC 60 DEGREES SECTOR ANTENNA 21DBI', 2),
('AM-5AC22-45', '5G AIRMAX AC 45 DEGREES SECTOR ANTENNA 22DBI', 2),
('AM-5G16-120', '4.9-5.9GHZ AIRMAX BASE STATION 16DBI 120 DEG W/ ROCKET KIT', 2),
('AM-5G17-90', '4.9-5.9GHZ AIRMAX BASE STATION 17DBI 90 DEG W/ROCKET KIT', 2),
('AM-5G19-120', '4.9-5.9GHZ AIRMAX BASE STATION 19DBI 120 DEG W/ ROCKET KIT', 2),
('AM-5G20-90', '4.9-5.9GHZ AIRMAX BASE STATION 20DBI 90 DEG W/ ROCKET KIT', 2),
('AM-9M13-120', '900MHZ AIRMAX BASE STATION 13DBI', 2),
('AM-M-V5G-TI', '5GHZ VARIABLE BEAM BASE STATION MID-GAIN 60-120DEG TI W/ ROCKET KIT', 2),
('AMO-2G10', '2GHZ AIRMAX DUAL OMNI 10DBI W/ ROCKET KIT', 2),
('AMO-2G13', '2GHZ AIRMAX DUAL OMNI 13DBI W/ ROCKET KIT', 2),
('AMO-3G12', '3GHZ AIRMAX DUAL OMNI 12DBI W/ ROCKET KIT', 2),
('AMO-5G10', '5GHZ AIRMAX DUAL OMNI 10DBI W/ ROCKET KIT', 2),
('AMO-5G13', '5GHZ AIRMAX DUAL OMNI 13DBI W/ ROCKET KIT', 2),
('AM-V2G-TI', '2GHZ VARIABLE BEAM BASE STATION 60-120DEG TITANIUM W/ROCKET KIT', 2),
('AM-V5G-TI', '5GHZ VARIABLE BEAM BASE STATION 60-120DEG TITANIUM W/ROCKET KIT', 2),
('AMY-9M16-2', '900MHZ AIRMAX YAGI ANTENNA 16DBI W/ ROCKET KIT 2-PACK', 2),
('BM2-TI', '2.4GHZ BULLET AIRMAX TITANIUM POE/ADAPTER INCL', 2),
('BULLET5', 'BULLET5 CPE', 2),
('BULLETM2-HP', '2.4GHZ BULLET AIRMAX HP', 2),
('BULLETM5-HP', '5GHZ BULLET AIRMAX HP', 2),
('BULLETM5-TI', '5GHZ BULLET AIRMAX TITANIUM POE/ADAPTER INCL', 2),
('ER-8', 'EDGEROUTER 8 5PORT ROUTER', 2),
('ERLITE-3', 'EDGEROUTER LITE 3PORT', 2),
('ERPOE-5', 'EDGEROUTER POE 5PORT ROUTER WITH POE', 2),
('ERPRO-8', 'EDGEROUTER PRO 8 8PORT ROUTER 2 SFP', 2),
('ES-24-250W', 'EDGE SWITCH 24 250W', 2),
('ES-24-500W', 'EDGE SWITCH 24PORT 500W', 2),
('ES-48-500W', 'EDGE SWITCH 48PORT 500W', 2),
('ES-48-750W', 'EDGE SWITCH 48PORT 750W', 2),
('ETH-SP', 'ETHERNET SURGE PROTECTOR', 2),
('INS-8023AF-I', 'INSTANT 8023AF ADAPTER INDOOR', 2),
('INS-8023AF-O', 'INSTANT 8023AF ADAPTER OUTDOOR', 2),
('LOCOM2', '2.4GHZ LOCO MIMO AIRMAX', 2),
('LOCOM5', '5GHZ LOCO MIMO AIRMAX', 2),
('LOCOM900', '900MHZ LOCO MIMO AIRMAX', 2),
('MFI-CS', 'MFI CURRENT SENSOR', 2),
('MFI-DS', 'MFI DOOR SENSOR', 2),
('MFI-LD', 'IN-WALL MANAGEABLE SWITCH/ DIMMER', 2),
('MFI-MPW', 'IN-WALL MANAGEABLE OUTLET', 2),
('MFI-MSC', 'MFI CEILING MOUNT MOTION SENSOR', 2),
('MFI-MSW', 'MFI WALL MOUNT MOTION SENSOR', 2),
('MFI-THS', 'MFI TEMPERATURE SENSOR', 2),
('MPORT', 'MFI MPORT', 2),
('MPORT-S', 'MFI MPORT SERIAL', 2),
('MPOWER', 'MFI 3PORT POWER EU & US WIFI', 2),
('MPOWER-MINI', 'MFI 1PORT POWER EU & US WIFI', 2),
('MPOWER-PRO', 'MFI 8PORT US 6PORT EU POWER WIFI/ETH', 2),
('NANOMOUNT', 'NANOMOUNT', 2),
('NBE-M5-19', '5GHZ NANOBEAM AIRMAX 19DB', 2),
('NBE-M5-400', '5PK 5GHZ NANOBEAM AIRMAX 400MM', 2),
('NBE-RAD-400', 'NANOBEAM RADOME 400MM', 2),
('NBE-WMK', 'NANOBEAM WALL MOUNT KIT', 2),
('NBM3', '3.5GHZ NANOBRIDGE AIRMAX 22DBI 2 PARTS NBM3+NB-OD3', 2),
('NBM365', '3.65GHZ NANOBRIDGE AIRMAX 22DBI 2 PARTS NBM365+NB-OD3', 2),
('NBM9', '900MHZ NANOBRIDGE AIRMAX 13DBI 2 PARTS NBM9+NB-OD9', 2),
('NSM2', '2.4GHZ NANO MIMO AIRMAX', 2),
('NSM3', '3.5GHZ NANO MIMO AIRMAX', 2),
('NSM365', '3.65GHZ NANOBRIDGE AIRMAX 22DBI 2 PARTS NBM365+NB-OD3', 2),
('NSM5', '5GHZ NANO MIMO AIRMAX', 2),
('PBM10', '10GHZ POWERBRIDGE MIMO AIRMAX 2 PARTS - PBM10 $275 + PBM10-DISH 75', 2),
('PBM3', '3.5GHZ POWERBRIDGE MIMO AIRMAX', 2),
('PBM365', '3.65GHZ POWERBRIDGE MIMO AIRMAX', 2),
('PBM5', '5GHZ POWERBRIDGE MIMO AIRMAX', 2),
('PICOM2-HP', '2.4GHZ PICO AIRMAX HP', 2),
('POE-24-12W', 'POE INJECTOR 24VDC 12W', 2),
('POE-24-24W', 'POE INJECTOR 24VDC 24W', 2),
('POE-48-24W', 'POE INJECTOR 48VDC 24W', 2),
('POE-48-24W-G', 'POE INJECTOR 48VDC 24W ROCKETTI UAP-PRO', 2),
('POE-50-60W', 'POE INJECTOR 50V 60W AIRFIBER POE', 2),
('PS2-17D', 'POWERSTATION2-17D CPE', 2),
('PS5-22V', 'POWERSTATION5-22V CPE', 2),
('RAD-2RD', 'RADOME 2 ROCKETDISH EAR99 RADOME FOR ROCKETDISH', 2),
('RAD-3RD', 'RADOME 3 ROCKETDISH EAR99 RADOME FOR ROCKETDISH', 2),
('RD-2G24', '2GHZ ROCKET DISH 24DBI W/ ROCKET KIT', 2),
('RD-3G26', '3GHZ ROCKET DISH 26DBI W/ ROCKET KIT', 2),
('RD-5G30', '5GHZ ROCKET DISH 30DBI W/ ROCKET KIT', 2),
('RD-5G34', '5GHZ ROCKET DISH 34DBI W/ROCKET KIT', 2),
('RM2-TI', '2.4GHZ ROCKET MIMO AIRMAX TITANIUM', 2),
('RM5-TI', '5GHZ ROCKET MIMO AIRMAX TITANIUM', 2),
('ROCKETM2', '2.4GHZ ROCKET MIMO AIRMAX', 2),
('ROCKETM3', '3.5GHZ ROCKET MIMO AIRMAX', 2),
('ROCKETM365', '3.65GHZ ROCKET MIMO AIRMAX', 2),
('ROCKETM5', '5GHZ ROCKET MIMO AIRMAX', 2),
('ROCKETM9', '900MHZ ROCKET MIMO AIRMAX', 2),
('SR2', 'SR2 MINI PCI PCBA', 2),
('SR5', 'SR5 MINI PCI PCBA', 2),
('SR71-15', '11A/N 5GHZ 27DBM 2X2 OUTDOOR', 2),
('SR71E', '11A/B/G/N 200MW 2X2 EXT. ANTENNA', 2),
('SRC', 'SRC CARDBUS', 2),
('TC-CARRIER', 'TOUGH CABLE LEVEL 2 1000 BOX', 2),
('TC-CON', 'TOUGH CABLE CONNEXTRS X 2400 100PCS/BOX', 2),
('TC-GND', 'TOUGH CABLE CONNECTOR GROUND X 1000', 2),
('TC-PRO', 'TOUGH CABLE LEVEL 1 1000 BOX', 2),
('TS-16-CARRIER', 'TOUGHSWITCH 16PORT POE PRO RACKMOUNT', 2),
('TS-5-POE', 'TOUGHSWITCH 5PORT POE', 2),
('TS-8-PRO', 'TOUGHSWITCH 8PORT POE PRO', 2),
('UAP', 'UNIFI AP', 2),
('UAP-3', 'UNIFI AP 3-PACK', 2),
('UAP-AC', 'UNIFI AP 802.11AC', 2),
('UAP-AC-3', 'UNIFI AP AC 3PK', 2),
('UAP-AC-OUTDOOR', 'UNIFI AP AC OUTDOOR', 2),
('UAP-LR', 'UNIFI AP LONG RANGE', 2),
('UAP-LR-3', 'UNIFI AP LONG RANGE 3-PACK', 2),
('UAP-OUTDOOR+', 'UNIFI AP OUTDOOR+ XRF', 2),
('UAP-OUTDOOR-5', 'UNIFI AP OUTDOOR 5GHZ', 2),
('UAP-PRO', 'UNIFI AP PRO GIGE 802.3AF DUAL RADIO', 2),
('UAP-PRO-3', 'UNIFI AP PRO GIGE 802.3AF DUAL RADIO 3PK', 2),
('UB-AM', '10PK UBIQUITI ANTENNA MOUNT', 2),
('USG', 'UNIFI SECURITY GATEWAY', 2),
('UVC', 'UNIFI VIDEO CAMERA IR', 2),
('UVC-3', 'UNIFI VIDEO CAMERA IR 3PK', 2),
('UVC-DOME', 'UNIFI VIDEO CAMERA DOME IR', 2),
('UVC-DOME-3', 'UNIFI VIDEO CAMERA DOME IR 3-PACK', 2),
('UVC-NVR', 'AIRVISION NVR CONTROLLER', 2),
('UVC-PRO', 'UNIFI VIDEO CAMERA PRO', 2),
('UVC-PRO-C', 'UNIFI CAMERA PRO CABLE ACCESSORY', 2),
('UVC-PRO-M', 'UNIFI CAMERA PRO MOUNT ACCESSORY', 2),
('UVP', 'UNIFI VOIP PHONE', 2),
('WS5', 'WS5 SUBSCRIBER', 2),
('WSM5', 'WISPSTATION M SERIES 5GHZ AIRMAX', 2),
('XR2', 'MINI PCI 600MW 2.4GHZ', 2),
('XR5', 'MINI PCI 600MW 5GHZ', 2),
('XR7', 'XR7 MINI PCI PCBA', 2),
('XR9', 'MINI PCI 600MW 900MHZ', 2);

INSERT INTO dbinfo (keytype, keyvalue) VALUES ('dbversion', '2019072500');

COMMIT;
