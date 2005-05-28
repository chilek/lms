<?php

/*
 * LMS version 1.7-cvs
 *
 *  (C) Copyright 2001-2005 LMS Developers
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

$DB->BeginTrans();

$DB->Execute("CREATE TEMP TABLE assignments_t AS SELECT * FROM assignments");
$DB->Execute("DROP TABLE assignments");
$DB->Execute("CREATE TABLE assignments (
	id integer PRIMARY KEY,
	tariffid integer 	DEFAULT 0 NOT NULL,
	customerid integer 	DEFAULT 0 NOT NULL,
	period integer 		DEFAULT 0 NOT NULL,
	at integer 		DEFAULT 0 NOT NULL,
	datefrom integer	DEFAULT 0 NOT NULL,
	dateto integer		DEFAULT 0 NOT NULL,
	invoice smallint 	DEFAULT 0 NOT NULL,
	suspended smallint	DEFAULT 0 NOT NULL,
	discount numeric(4,2)	DEFAULT 0 NOT NULL)
");
$DB->Execute("INSERT INTO assignments (id, tariffid, customerid, period, at, datefrom, dateto, invoice, suspended, discount)
		SELECT id, tariffid, userid, period, at, datefrom, dateto, invoice, suspended, discount
		FROM assignments_t");
$DB->Execute("DROP TABLE assignments_t");

$DB->Execute("CREATE TEMP TABLE events_t AS SELECT * FROM events");
$DB->Execute("DROP TABLE events");
$DB->Execute("CREATE TABLE events (
	id integer PRIMARY KEY,
	title varchar(255) DEFAULT '' NOT NULL,
	description text DEFAULT '' NOT NULL,
	note text DEFAULT '' NOT NULL,
	date integer DEFAULT 0 NOT NULL,
	begintime smallint DEFAULT 0 NOT NULL,
	endtime smallint DEFAULT 0 NOT NULL,
	userid integer DEFAULT 0 NOT NULL,
	customerid integer DEFAULT 0 NOT NULL,
	private smallint DEFAULT 0 NOT NULL,
	closed smallint DEFAULT 0 NOT NULL)
");
$DB->Execute("INSERT INTO events (id, title, description, note, date, begintime, endtime, private, closed, customerid, userid)
		SELECT id, title, description, note, date, begintime, endtime, private, closed, userid, adminid
		FROM events_t");
$DB->Execute("DROP TABLE events_t");

$DB->Execute("CREATE TEMP TABLE rtmessages_t AS SELECT * FROM rtmessages");
$DB->Execute("DROP TABLE rtmessages");
$DB->Execute("CREATE TABLE rtmessages (
  id integer 		PRIMARY KEY,
  ticketid integer 	DEFAULT 0 NOT NULL,
  userid integer 	DEFAULT 0 NOT NULL,
  customerid integer	DEFAULT 0 NOT NULL,
  mailfrom varchar(255) DEFAULT '' NOT NULL,
  subject varchar(255) 	DEFAULT '' NOT NULL,
  messageid varchar(255) DEFAULT '' NOT NULL,
  inreplyto integer 	DEFAULT 0 NOT NULL,
  replyto text 		DEFAULT '' NOT NULL,
  headers text 		DEFAULT '' NOT NULL,
  body text 		DEFAULT '' NOT NULL,
  createtime integer	DEFAULT 0 NOT NULL)
");
$DB->Execute("INSERT INTO rtmessages (id, ticketid, userid, customerid, mailfrom, subject, messageid, inreplyto, replyto, headers, body, createtime)
		SELECT id, ticketid, adminid, userid, mailfrom, subject, messageid, inreplyto, replyto, headers, body, createtime
		FROM rtmessages_t");
$DB->Execute("DROP TABLE rtmessages_t");

$DB->Execute("CREATE TEMP TABLE rttickets_t AS SELECT * FROM rttickets");
$DB->Execute("DROP TABLE rttickets");
$DB->Execute("CREATE TABLE rttickets (
  id integer 		PRIMARY KEY,  
  queueid integer 	DEFAULT 0 NOT NULL,
  requestor varchar(255) DEFAULT '' NOT NULL,
  subject varchar(255) 	DEFAULT '' NOT NULL,
  state smallint 	DEFAULT 0 NOT NULL,
  owner integer 	DEFAULT 0 NOT NULL,
  customerid integer	DEFAULT 0 NOT NULL,
  createtime integer 	DEFAULT 0 NOT NULL,
  resolvetime integer 	DEFAULT 0 NOT NULL)
");
$DB->Execute("INSERT INTO rttickets (id, queueid, requestor, subject, state, owner, customerid, createtime, resolvetime)
		SELECT id, queueid, requestor, subject, state, owner, userid, createtime, resolvetime
		FROM rttickets_t");
$DB->Execute("DROP TABLE rttickets_t");

$DB->Execute("CREATE TEMP TABLE cash_t AS SELECT * FROM cash");
$DB->Execute("DROP INDEX cash_userid_idx");
$DB->Execute("DROP TABLE cash");
$DB->Execute("CREATE TABLE cash (
	id integer 		PRIMARY KEY,
	time integer 		DEFAULT 0 NOT NULL,
	userid integer 	DEFAULT 0 NOT NULL,
	type smallint 		DEFAULT 0 NOT NULL,
	value numeric(9,2) 	DEFAULT 0 NOT NULL,
	taxvalue numeric(9,2)   DEFAULT 0,
	customerid integer 		DEFAULT 0 NOT NULL,
	comment varchar(255) 	DEFAULT '' NOT NULL,
	invoiceid integer 	DEFAULT 0 NOT NULL,
	itemid smallint		DEFAULT 0 NOT NULL)
");
$DB->Execute("INSERT INTO cash (id, time, userid, customerid, type, value, taxvalue, comment, invoiceid, itemid)
		SELECT id, time, adminid, userid, type, value, taxvalue, comment, invoiceid, itemid
		FROM cash_t");
$DB->Execute("DROP TABLE cash_t");
$DB->Execute("CREATE INDEX cash_customerid_idx ON cash(customerid)");

$DB->Execute("CREATE TABLE customerassignments (
	id integer PRIMARY KEY, 
	customergroupid integer DEFAULT 0 NOT NULL, 
	customerid integer DEFAULT 0 NOT NULL, 
	UNIQUE (customergroupid, customerid))
");
$DB->Execute("INSERT INTO customerassignments (id, customergroupid, customerid)
		SELECT id, usergroupid, userid
		FROM userassignments");
$DB->Execute("DROP TABLE userassignments");

$DB->Execute("CREATE TABLE customergroups AS SELECT * FROM usergroups");
$DB->Execute("DROP TABLE usergroups");

$DB->Execute("CREATE TEMP TABLE rtrights_t AS SELECT * FROM rtrights");
$DB->Execute("DROP TABLE rtrights");
$DB->Execute("CREATE TABLE rtrights (
    id integer PRIMARY KEY, 
    userid integer 	DEFAULT 0 NOT NULL,
    queueid integer 	DEFAULT 0 NOT NULL,
    rights integer 	DEFAULT 0 NOT NULL,
    UNIQUE (userid, queueid))
");
$DB->Execute("INSERT INTO rtrights (id, userid, queueid, rights)
		SELECT id, adminid, queueid, rights
		FROM rtrights_t");
$DB->Execute("DROP TABLE rtrights_t");

$DB->Execute("CREATE TEMP TABLE eventassignments_t AS SELECT * FROM eventassignments");
$DB->Execute("DROP TABLE eventassignments");
$DB->Execute("CREATE TABLE eventassignments (
	eventid integer DEFAULT 0 NOT NULL,
	userid integer DEFAULT 0 NOT NULL,
	UNIQUE (eventid, userid))
");
$DB->Execute("INSERT INTO eventassignments (eventid, userid)
		SELECT eventid, adminid
		FROM eventassignments_t");
$DB->Execute("DROP TABLE eventassignments_t");

$DB->Execute("CREATE TABLE customers AS SELECT * FROM users");
$DB->Execute("DROP TABLE users");

$DB->Execute("CREATE TABLE users AS SELECT * FROM admins");
$DB->Execute("DROP TABLE admins");


$DB->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?",array('2005052700', 'dbversion'));

$DB->CommitTrans();

?>
