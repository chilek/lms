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

$this->Execute("CREATE TABLE messages (
        id 	int(11) 	NOT NULL auto_increment,
        subject varchar(255)	DEFAULT '' NOT NULL,
	body 	text		DEFAULT '' NOT NULL,
	cdate 	int(11)		DEFAULT 0 NOT NULL,
	type 	smallint	DEFAULT 0 NOT NULL,
	userid 	int(11)		DEFAULT 0 NOT NULL,
	sender 	varchar(255) 	DEFAULT NULL,
        PRIMARY KEY (id),
	INDEX cdate (cdate, type),
	INDEX userid (userid)
) ENGINE=MyISAM");

$this->Execute("CREATE TABLE messageitems (
        id 		int(11) 	NOT NULL auto_increment,
	messageid 	int(11)		DEFAULT 0 NOT NULL,
	customerid 	int(11) 	DEFAULT 0 NOT NULL,
	destination 	varchar(255) 	DEFAULT '' NOT NULL,
	lastdate 	int(11)		DEFAULT 0 NOT NULL,
	status 		smallint	DEFAULT 0 NOT NULL,
	error 		text		DEFAULT NULL, 
        PRIMARY KEY (id),
	INDEX messageid (messageid),
	INDEX customerid (customerid)
) ENGINE=MyISAM");

$this->Execute('UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?', array('2009031300', 'dbversion'));

$this->CommitTrans();
