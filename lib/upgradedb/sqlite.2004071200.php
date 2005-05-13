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

/* includes 2004070100, 2004070800, 2004071200 upgrades */

$DB->Execute("BEGIN");
$DB->Execute("CREATE TABLE rtrights (
    id integer PRIMARY KEY, 
    adminid integer DEFAULT 0 NOT NULL,
    queueid integer DEFAULT 0 NOT NULL,
    rights integer DEFAULT 0 NOT NULL,
    UNIQUE (adminid, queueid)
)");

$DB->Execute("CREATE TEMP TABLE rtq AS SELECT * FROM rtqueues");
$DB->Execute("DROP TABLE rtqueues");
$DB->Execute("CREATE TABLE rtqueues (
  id integer 		PRIMARY KEY,
  name varchar(255) 	DEFAULT '' NOT NULL,
  email varchar(255) 	DEFAULT '' NOT NULL,
  description text	DEFAULT '' NOT NULL,
  UNIQUE (name)
)");
$DB->Execute("INSERT INTO rtqueues (id, name, email) SELECT id, name, email FROM rtq");
$DB->Execute("DROP TABLE rtq");

$DB->Execute("CREATE TEMP TABLE rtt AS SELECT * FROM rttickets");
$DB->Execute("DROP TABLE rttickets");
$DB->Execute("CREATE TABLE rttickets (
  id integer 		PRIMARY KEY,  
  queueid integer 	DEFAULT 0 NOT NULL,
  requestor varchar(255) DEFAULT '' NOT NULL,
  subject varchar(255) 	DEFAULT '' NOT NULL,
  state smallint 	DEFAULT 0 NOT NULL,
  owner integer 	DEFAULT 0 NOT NULL,
  userid integer	DEFAULT 0 NOT NULL,
  createtime integer 	DEFAULT 0 NOT NULL
)");
$DB->Execute("INSERT INTO rttickets(id, queueid, requestor, subject, state, owner, createtime) SELECT id, queueid, requestor, subject, state, owner, createtime FROM rtt");
$DB->Execute("DROP TABLE rtt");

$DB->Execute("CREATE TEMP TABLE rtm AS SELECT * FROM rtmessages");
$DB->Execute("DROP TABLE rtmessages");
$DB->Execute("CREATE TABLE rtmessages (
  id integer 		PRIMARY KEY,
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
  createtime integer	DEFAULT 0 NOT NULL
)");
$DB->Execute("INSERT INTO rtmessages(id, ticketid, adminid, mailfrom, subject, messageid, inreplyto, replyto, headers, body, createtime) SELECT id, ticketid, sender, mailfrom, subject, messageid, inreplyto, replyto, headers, body, createtime FROM rtm");
$DB->Execute("DROP TABLE rtm");

$DB->Execute("UPDATE dbinfo SET keyvalue = '2004071200' WHERE keytype = 'dbversion'");
$DB->Execute("COMMIT");

?>
