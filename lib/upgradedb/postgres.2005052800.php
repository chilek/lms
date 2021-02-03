<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2013 LMS Developers
 *
 *  Please, see the doc/AUTHORS for more information about authors!
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License Version 2 as
 *  published by the Free Software Foundation.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program; if not, write to the Free Software
 *  Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307,
 *  USA.
 *
 *  $Id$
 */

$this->BeginTrans();

$this->Execute("

	CREATE TEMP TABLE users_t AS SELECT * FROM users;
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
	INSERT INTO users (id, login, name, email, rights, hosts, passwd, lastlogindate, lastloginip, failedlogindate, failedloginip, deleted)
	    SELECT id, login, name, email, rights, hosts, passwd, lastlogindate, lastloginip, failedlogindate, failedloginip, deleted 
	    FROM users_t;
	DROP TABLE users_t; 

	CREATE TEMP TABLE customers_t AS SELECT * FROM customers;
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
	nip varchar(16) 	DEFAULT '' NOT NULL,
	pesel varchar(11) 	DEFAULT '' NOT NULL,
	info text		DEFAULT '' NOT NULL,
	serviceaddr text	DEFAULT '' NOT NULL,
	creationdate integer 	DEFAULT 0 NOT NULL,
	moddate integer 	DEFAULT 0 NOT NULL,
	creatorid integer 	DEFAULT 0 NOT NULL,
	modid integer 		DEFAULT 0 NOT NULL,
	deleted smallint 	DEFAULT 0 NOT NULL,
	message text		DEFAULT '' NOT NULL,
	pin integer		DEFAULT 0 NOT NULL,
	PRIMARY KEY (id));
	INSERT INTO customers (id, lastname, name, status, email, phone1, phone2, phone3, gguin, address, zip, city, nip, pesel, info, serviceaddr, creationdate, moddate, creatorid, modid, deleted, message, pin)
	    SELECT id, lastname, name, status, email, phone1, phone2, phone3, gguin, address, zip, city, nip, pesel, info, serviceaddr, creationdate, moddate, creatorid, modid, deleted, message, pin
	    FROM customers_t;
	DROP TABLE customers_t; 

	CREATE TEMP TABLE groups_t AS SELECT * FROM customergroups;
	DROP TABLE customergroups;
	CREATE TABLE customergroups (
	id integer DEFAULT nextval('customergroups_id_seq'::text) NOT NULL, 
	name varchar(255) DEFAULT '' NOT NULL, 
	description text DEFAULT '' NOT NULL, 
	PRIMARY KEY (id), 
	UNIQUE (name));
	INSERT INTO customergroups (id, name, description) 
	    SELECT id, name, description 
	    FROM groups_t;
	DROP TABLE groups_t; 

	CREATE TEMP TABLE a_t AS SELECT * FROM customerassignments;
	DROP TABLE customerassignments;
	CREATE TABLE customerassignments (
	id integer DEFAULT nextval('customerassignments_id_seq'::text) NOT NULL, 
	customergroupid integer DEFAULT 0 NOT NULL, 
	customerid integer DEFAULT 0 NOT NULL, 
	PRIMARY KEY (id),
	UNIQUE (customergroupid, customerid));
	INSERT INTO customerassignments (id, customergroupid, customerid)
	    SELECT id, customergroupid, customerid 
	    FROM a_t;
	DROP TABLE a_t; 
	
");

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2005052800', 'dbversion'));

$this->CommitTrans();
