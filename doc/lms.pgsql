/* -------------------------------------------------------- 
  phpPgAdmin 2.4.2 DB Dump
  http://sourceforge.net/projects/phppgadmin/
  Host: localhost:5432
  Baza danych  : "lms"
  2002-12-30 09:12:45
  $Id$
-------------------------------------------------------- */ 

/* -------------------------------------------------------- 
  Sekwencje 
-------------------------------------------------------- */ 
DROP SEQUENCE "admins_id_seq";
CREATE SEQUENCE "admins_id_seq" START 1 INCREMENT 1 MAXVALUE 9223372036854775807 MINVALUE 1 CACHE 1; 
DROP SEQUENCE "cash_id_seq";
CREATE SEQUENCE "cash_id_seq" START 1 INCREMENT 1 MAXVALUE 9223372036854775807 MINVALUE 1 CACHE 1; 
DROP SEQUENCE "networks_id_seq";
CREATE SEQUENCE "networks_id_seq" START 1 INCREMENT 1 MAXVALUE 9223372036854775807 MINVALUE 1 CACHE 1; 
DROP SEQUENCE "nodes_id_seq";
CREATE SEQUENCE "nodes_id_seq" START 1 INCREMENT 1 MAXVALUE 9223372036854775807 MINVALUE 1 CACHE 1; 
DROP SEQUENCE "tariffs_id_seq";
CREATE SEQUENCE "tariffs_id_seq" START 1 INCREMENT 1 MAXVALUE 9223372036854775807 MINVALUE 1 CACHE 1; 
DROP SEQUENCE "users_id_seq";
CREATE SEQUENCE "users_id_seq" START 1 INCREMENT 1 MAXVALUE 9223372036854775807 MINVALUE 1 CACHE 1; 

/* -------------------------------------------------------- 
  Struktura tabeli "admins" 
-------------------------------------------------------- */
DROP TABLE admins;
CREATE TABLE admins (
   id integer DEFAULT nextval('admins_id_seq'::text) NOT NULL,
   login varchar(32) DEFAULT '' NOT NULL,
   name varchar(64) DEFAULT '' NOT NULL,
   email varchar(255) DEFAULT '' NOT NULL,
   rights varchar(64) DEFAULT '' NOT NULL,
   passwd varchar(255) DEFAULT '' NOT NULL,
   lastlogindate integer,
   lastloginip varchar(16),
   failedlogindate integer,
   failedloginip varchar(16)
);
CREATE UNIQUE INDEX "admins_id_key" ON admins(id);

/* -------------------------------------------------------- 
  Struktura tabeli "cash" 
-------------------------------------------------------- */
DROP TABLE cash;
CREATE TABLE cash (
   id integer DEFAULT nextval('cash_id_seq'::text) NOT NULL,
   time integer DEFAULT 0 NOT NULL,
   adminid integer DEFAULT 0 NOT NULL,
   type smallint DEFAULT 0 NOT NULL,
   value float4 DEFAULT 0 NOT NULL,
   userid integer DEFAULT 0 NOT NULL,
   comment varchar(255) DEFAULT '' NOT NULL
);
CREATE  UNIQUE INDEX "cash_id_key" ON "cash" ("id");

/* -------------------------------------------------------- 
  Struktura tabeli "networks" 
-------------------------------------------------------- */
DROP TABLE networks;
CREATE TABLE networks (
   id int4 DEFAULT nextval('networks_id_seq'::text) NOT NULL,
   name varchar(255) NOT NULL,
   address varchar(16) NOT NULL,
   mask varchar(16) NOT NULL,
   interface varchar(8),
   gateway varchar(16),
   dns varchar(16),
   dns2 varchar(16),
   domain varchar(64),
   wins varchar(16),
   dhcpstart varchar(16),
   dhcpend varchar(16)
);
CREATE UNIQUE INDEX "networks_id_key" ON networks(id);

/* -------------------------------------------------------- 
  Struktura tabeli "nodes" 
-------------------------------------------------------- */
DROP TABLE nodes;
CREATE TABLE nodes (
   id integer DEFAULT nextval('nodes_id_seq'::text) NOT NULL,
   name varchar(16) NOT NULL,
   mac varchar(20) NOT NULL,
   ipaddr bigint NOT NULL,
   ownerid integer DEFAULT 0 NOT NULL,
   creationdate int4 NOT NULL,
   moddate integer DEFAULT 0 NOT NULL,
   creatorid integer NOT NULL,
   modid integer DEFAULT 0 NOT NULL,
   access smallint DEFAULT 1 NOT NULL
);

CREATE UNIQUE INDEX "nodes_id_key" ON nodes(id);

/* -------------------------------------------------------- 
  Struktura tabeli "tariffs" 
-------------------------------------------------------- */
DROP TABLE tariffs;
CREATE TABLE tariffs (
   id integer DEFAULT nextval('tariffs_id_seq'::text) NOT NULL,
   name varchar(255) NOT NULL,
   value float4 DEFAULT 0 NOT NULL,
   uprate integer DEFAULT 0 NOT NULL,
   downrate integer DEFAULT 0 NOT NULL,
   description text NOT NULL
);
CREATE UNIQUE INDEX "tariffs_id_key" ON tariffs(id);
    
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
   gguin integer DEFAULT NULL,
   address varchar(255) DEFAULT '' NOT NULL,
   zip varchar(6) DEFAULT NULL,
   city varchar(32) DEFAULT NULL,
   nip varchar(16) DEFAULT NULL, 
   tariff integer DEFAULT 0 NOT NULL,
   info text,
   creationdate integer DEFAULT 0 NOT NULL,
   moddate integer DEFAULT 0 NOT NULL,
   creatorid integer DEFAULT 0 NOT NULL,
   modid integer DEFAULT 0 NOT NULL,
   payday smallint DEFAULT 1 NOT NULL,
   deleted smallint DEFAULT 0 NOT NULL
);
CREATE UNIQUE INDEX "users_id_key" ON users(id);

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
