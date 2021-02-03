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
    CREATE TABLE rtqueues (
	id int(11) NOT NULL auto_increment, 
	name varchar(255) NOT NULL default '', 
	email varchar(255) NOT NULL default '', 
	PRIMARY KEY (id)) ENGINE=MyISAM
");

// rttickets - Tickets in RT

$this->Execute("
    CREATE TABLE rttickets (
	id int(11) NOT NULL auto_increment, 
	queueid int(11) NOT NULL default '0', 
	requestor varchar(255) NOT NULL default '', 
	subject varchar(255) NOT NULL default '', 
	state tinyint(4) NOT NULL default '0', 
	owner int(11) NOT NULL default '0', 
	createtime int(11) NOT NULL default '0', 
	PRIMARY KEY (id)) ENGINE=MyISAM
");

// rtmessages - content of mails in RT

$this->Execute("
    CREATE TABLE rtmessages (
	id int(11) NOT NULL auto_increment,
	ticketid int(11) NOT NULL default '0', 
	sender int(11) NOT NULL default '0', 
	mailfrom varchar(255) NOT NULL default '', 
	subject varchar(255) NOT NULL default '', 
	messageid varchar(255) NOT NULL default '', 
	inreplyto int(11) NOT NULL default '0', 
	replyto text NOT NULL default '', 
	headers text NOT NULL default '', 
	body mediumtext NOT NULL default '', 
	PRIMARY KEY  (id) ) ENGINE=MyISAM
");

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2004030800', 'dbversion'));
