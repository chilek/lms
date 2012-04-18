<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2012 LMS Developers
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

$DB->Execute("
    CREATE TABLE ewx_stm_nodes (
	id int(11)              NOT NULL auto_increment,
	nodeid int(11)          DEFAULT '0' NOT NULL,
	mac varchar(20)         DEFAULT '' NOT NULL,
	ipaddr int(16) unsigned DEFAULT '0' NOT NULL,
	channelid int(11)       DEFAULT '0' NOT NULL,
	uprate int(11)          DEFAULT '0' NOT NULL,
	upceil int(11)          DEFAULT '0' NOT NULL,
	downrate int(11)        DEFAULT '0' NOT NULL,
	downceil int(11)        DEFAULT '0' NOT NULL,
	halfduplex tinyint(1)   DEFAULT '0' NOT NULL,
	PRIMARY KEY (id),
	UNIQUE KEY nodeid (nodeid)
    ) ENGINE=MyISAM
");

$DB->Execute("
    CREATE TABLE ewx_stm_channels (
	id int(11)              NOT NULL auto_increment,
	customerid int(11)      DEFAULT '0' NOT NULL,
	upceil int(11)          DEFAULT '0' NOT NULL,
	downceil int(11)        DEFAULT '0' NOT NULL,
	PRIMARY KEY (id),
	UNIQUE KEY customerid (customerid)
    ) ENGINE=MyISAM
");

$DB->Execute("
    CREATE TABLE ewx_pt_config (
        id int(11)              NOT NULL auto_increment,
	nodeid int(11)          DEFAULT '0' NOT NULL,
	name varchar(16)        DEFAULT '' NOT NULL,
	mac varchar(20)         DEFAULT '' NOT NULL,
	ipaddr int(16) unsigned DEFAULT '0' NOT NULL,
	passwd varchar(32)      DEFAULT '' NOT NULL,
	PRIMARY KEY (id),
	UNIQUE KEY nodeid (nodeid)
    ) ENGINE=MyISAM
");

$DB->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2007030800', 'dbversion'));

$DB->CommitTrans();

?>
