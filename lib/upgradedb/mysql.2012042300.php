<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2013 LMS Developers
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
 */

$this->BeginTrans();

$this->Execute("
	CREATE TABLE nodesessions (
		id int(11)		NOT NULL auto_increment,
		customerid int(11)	NOT NULL DEFAULT '0',
		nodeid int(11)		NOT NULL DEFAULT '0',
		ipaddr int(16) unsigned	NOT NULL DEFAULT '0',
		mac varchar(17)		NOT NULL DEFAULT '',
		start int(11)		NOT NULL DEFAULT '0',
		stop int(11)		NOT NULL DEFAULT '0',
		download bigint		DEFAULT '0',
		upload bigint		DEFAULT '0',
		tag varchar(32)		NOT NULL DEFAULT '',
		PRIMARY KEY (id),
		INDEX customerid (customerid),
		INDEX nodeid (nodeid),
		INDEX tag (tag)
	) ENGINE=InnoDB
");
$this->Execute("ALTER TABLE stats ADD nodesessionid int(11) NOT NULL DEFAULT '0'");
$this->Execute("CREATE INDEX nodesessionid ON stats(nodesessionid)");

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2012042300', 'dbversion'));

$this->CommitTrans();
