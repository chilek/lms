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

// Add rtqueues - table that contains information about RT (Request Tracker) queues.

$this->Execute("
    CREATE SEQUENCE \"rtqueues_id_seq\";
    CREATE TABLE rtqueues (
	id integer default nextval('rtqueues_id_seq'::text) NOT NULL,
	name varchar(255) DEFAULT '' NOT NULL,
	email varchar(255) DEFAULT '' NOT NULL,
	PRIMARY KEY (id))
");

// rttickets - Tickets in RT

$this->Execute("
    CREATE SEQUENCE \"rttickets_id_seq\";
    CREATE TABLE rttickets (
	id integer default nextval('rttickets_id_seq'::text) NOT NULL,  
	queueid integer DEFAULT 0 NOT NULL,
	requestor varchar(255) DEFAULT '' NOT NULL,
	subject varchar(255) DEFAULT '' NOT NULL,
	state smallint DEFAULT 0 NOT NULL,
	owner integer DEFAULT 0 NOT NULL,
	createtime integer DEFAULT 0 NOT NULL,
	PRIMARY KEY (id))
");

// rtmessages - content of mails in RT

$this->Execute("
    CREATE SEQUENCE \"rtmessages_id_seq\";
    CREATE TABLE rtmessages (
	id integer default nextval('rtmessages_id_seq'::text) NOT NULL,
	ticketid integer DEFAULT 0 NOT NULL,
	sender integer DEFAULT 0 NOT NULL,
	mailfrom varchar(255) DEFAULT '' NOT NULL,
	subject varchar(255) DEFAULT '' NOT NULL,
	messageid varchar(255) DEFAULT '' NOT NULL,
	inreplyto integer DEFAULT 0 NOT NULL,
	replyto text DEFAULT '' NOT NULL,
	headers text DEFAULT '' NOT NULL,
	body text DEFAULT '' NOT NULL,
	PRIMARY KEY (id))
");

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2004030800', 'dbversion'));
