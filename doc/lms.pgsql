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
  Struktura tabeli dla "admins" 
-------------------------------------------------------- */
DROP TABLE "admins";
CREATE TABLE "admins" (
   "id" int4 DEFAULT nextval('"admins_id_seq"'::text) NOT NULL,
   "login" varchar(32) DEFAULT '' NOT NULL,
   "name" varchar(64) DEFAULT '' NOT NULL,
   "email" varchar(255) DEFAULT '' NOT NULL,
   "rights" varchar(64) DEFAULT '' NOT NULL,
   "passwd" varchar(255) DEFAULT '' NOT NULL,
   "lastlogindate" int4,
   "lastloginip" varchar(16),
   "failedlogindate" int4,
   "failedloginip" varchar(16)
);
CREATE UNIQUE INDEX "admins_id_key" ON "admins" ("id");

/* -------------------------------------------------------- 
  Struktura tabeli dla "cash" 
-------------------------------------------------------- */
DROP TABLE "cash";
CREATE TABLE "cash" (
   "id" int4 DEFAULT nextval('"cash_id_seq"'::text) NOT NULL,
   "time" int4 DEFAULT '0' NOT NULL,
   "adminid" int4 DEFAULT '0' NOT NULL,
   "type" int2 DEFAULT '0' NOT NULL,
   "value" float4 DEFAULT '0' NOT NULL,
   "userid" int4 DEFAULT '0' NOT NULL,
   "comment" varchar(255)DEFAULT '' NOT NULL
);
CREATE  UNIQUE INDEX "cash_id_key" ON "cash" ("id");

/* -------------------------------------------------------- 
  Struktura tabeli dla "networks" 
-------------------------------------------------------- */
DROP TABLE "networks";
CREATE TABLE "networks" (
   "id" int4 DEFAULT nextval('"networks_id_seq"'::text) NOT NULL,
   "name" varchar(255) NOT NULL,
   "address" varchar(16) NOT NULL,
   "mask" varchar(16) NOT NULL,
   "gateway" varchar(16),
   "dns" varchar(16),
   "dns2" varchar(16),
   "domain" varchar(64),
   "wins" varchar(16),
   "dhcpstart" varchar(16),
   "dhcpend" varchar(16)
);
CREATE UNIQUE INDEX "networks_id_key" ON "networks" ("id");

/* -------------------------------------------------------- 
  Struktura tabeli dla "nodes" 
-------------------------------------------------------- */
DROP TABLE "nodes";
CREATE TABLE "nodes" (
   "id" int4 DEFAULT nextval('"nodes_id_seq"'::text) NOT NULL,
   "name" varchar(16) NOT NULL,
   "mac" varchar(20) NOT NULL,
   "ipaddr" bigint NOT NULL,
   "ownerid" int4 DEFAULT '0' NOT NULL,
   "creationdate" int4 NOT NULL,
   "moddate" int4 DEFAULT '0' NOT NULL,
   "creatorid" int4 NOT NULL,
   "modid" int4 DEFAULT '0' NOT NULL,
   "access" int2 DEFAULT '1' NOT NULL
);
CREATE UNIQUE INDEX "nodes_id_key" ON "nodes" ("id");

/* -------------------------------------------------------- 
  Struktura tabeli dla "tariffs" 
-------------------------------------------------------- */
DROP TABLE "tariffs";
CREATE TABLE "tariffs" (
   "id" int4 DEFAULT nextval('"tariffs_id_seq"'::text) NOT NULL,
   "name" varchar(255) NOT NULL,
   "value" float4 DEFAULT '0' NOT NULL,
   "uprate" int4 DEFAULT '0' NOT NULL,
   "downrate" int4 DEFAULT '0' NOT NULL,
   "description" text NOT NULL
);
CREATE UNIQUE INDEX "tariffs_id_key" ON "tariffs" ("id");
    
/* -------------------------------------------------------- 
  Struktura tabeli dla "timestamps" 
-------------------------------------------------------- */
DROP TABLE "timestamps";
CREATE TABLE "timestamps" (
   "time" int4 DEFAULT '0' NOT NULL,
   "tablename" varchar(255) DEFAULT '' NOT NULL
);

/* -------------------------------------------------------- 
  Struktura tabeli dla "users" 
-------------------------------------------------------- */
DROP TABLE "users";
CREATE TABLE "users" (
   "id" int4 DEFAULT nextval('"users_id_seq"'::text) NOT NULL,
   "lastname" varchar(255),
   "name" varchar(255),
   "status" int4 DEFAULT NULL,
   "email" varchar(255) DEFAULT NULL,
   "phone1" varchar(255) DEFAULT NULL,
   "phone2" varchar(255) DEFAULT NULL,
   "phone3" varchar(255) DEFAULT NULL,
   "gguin" int4 DEFAULT NULL,
   "address" varchar(255) DEFAULT '' NOT NULL,
   "zip" varchar(6) DEFAULT NULL,
   "city" varchar(32) DEFAULT NULL,
   "nip" varchar(16) DEFAULT NULL, 
   "tariff" int4 DEFAULT '0' NOT NULL,
   "info" text,
   "creationdate" int4 DEFAULT '0' NOT NULL,
   "moddate" int4 DEFAULT '0' NOT NULL,
   "creatorid" int4 DEFAULT '0' NOT NULL,
   "modid" int4 DEFAULT '0' NOT NULL,
   "payday" int4 DEFAULT '1' NOT NULL,
   "deleted" int2 DEFAULT '0' NOT NULL
);
CREATE  UNIQUE INDEX "users_id_key" ON "users" ("id");

