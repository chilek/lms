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

$this->Execute("
	CREATE TABLE daemonhosts (
	id int(11) NOT NULL auto_increment,
	name varchar(255) DEFAULT '' NOT NULL,
	description text DEFAULT '' NOT NULL,
	lastreload int(11) DEFAULT '0' NOT NULL,
	reload tinyint(1) DEFAULT '0' NOT NULL,
	PRIMARY KEY (id),
	UNIQUE KEY name (name))
");
$this->Execute("
	CREATE TABLE daemoninstances (
	id int(11) NOT NULL auto_increment,
	name varchar(255) DEFAULT '' NOT NULL,
	hostid int(11) DEFAULT '0' NOT NULL,
	module varchar(255) DEFAULT '' NOT NULL,
	crontab varchar(255) DEFAULT '' NOT NULL,
	priority int(11) DEFAULT '0' NOT NULL,
	description text DEFAULT '' NOT NULL,
	disabled tinyint(1) DEFAULT '0' NOT NULL,
	PRIMARY KEY (id))
");
$this->Execute("
	CREATE TABLE daemonconfig (
	id int(11) NOT NULL auto_increment,
	instanceid int(11) DEFAULT '0' NOT NULL,
	var varchar(64) DEFAULT '' NOT NULL,
	value text DEFAULT '' NOT NULL,
	description text DEFAULT '' NOT NULL,
	disabled tinyint(1) DEFAULT '0' NOT NULL,
	PRIMARY KEY (id),
	UNIQUE KEY instanceid (instanceid, var))
");

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2005033103', 'dbversion'));
