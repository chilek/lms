<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2016 LMS Developers
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

$this->Execute("CREATE TABLE voip_prefixes (
						id int(11) AUTO_INCREMENT,
						prefix varchar(30) NOT NULL,
						name text NULL,
						description text NULL,
						UNIQUE (prefix),
						PRIMARY KEY (id));");

$this->Execute("CREATE TABLE voip_prefix_groups (
						id int(11) AUTO_INCREMENT,
						name text NULL,
						description text NULL,
						PRIMARY KEY (id));");

$this->Execute("CREATE TABLE voip_prefix_group_assignments (
						id int(11) AUTO_INCREMENT,
						prefixid int(11) NOT NULL,
						groupid int(11) NOT NULL,
						FOREIGN KEY (prefixid) REFERENCES voip_prefixes(id) ON DELETE CASCADE ON UPDATE CASCADE,
						FOREIGN KEY (groupid) REFERENCES voip_prefix_groups(id) ON DELETE CASCADE ON UPDATE CASCADE,
						PRIMARY KEY (id));");

$this->Execute("CREATE TABLE voip_tariffs (
						id int(11) AUTO_INCREMENT,
						prefixid int(11) NULL,
						groupid int(11) NULL,
						tariffid int(11) NOT NULL,
						FOREIGN KEY (prefixid) REFERENCES voip_prefixes(id) ON DELETE CASCADE ON UPDATE CASCADE,
						FOREIGN KEY (groupid) REFERENCES voip_prefix_groups(id) ON DELETE CASCADE ON UPDATE CASCADE,
						FOREIGN KEY (tariffid) REFERENCES tariffs(id) ON DELETE CASCADE ON UPDATE CASCADE,
						PRIMARY KEY (id));");

$this->Execute("CREATE TABLE voip_tariff_rules (
						id int(11) AUTO_INCREMENT,
						prefixid int(11) NULL,
						groupid int(11) NULL,
						tariffid int(11) NOT NULL,
						description text NULL,
						unitsize smallint NOT NULL,
						price decimal(12,5) NOT NULL,
						FOREIGN KEY (prefixid) REFERENCES voip_prefixes(id) ON DELETE CASCADE ON UPDATE CASCADE,
						FOREIGN KEY (groupid) REFERENCES voip_prefix_groups(id) ON DELETE CASCADE ON UPDATE CASCADE,
						FOREIGN KEY (tariffid) REFERENCES tariffs(id) ON DELETE CASCADE ON UPDATE CASCADE,
						PRIMARY KEY (id));");

$this->Execute("CREATE TABLE voip_cdr (
						id int(11) AUTO_INCREMENT,
						caller varchar(20) NOT NULL,
						callee varchar(20) NOT NULL,
						call_start_time int(11) NOT NULL,
						time_start_to_end int(11) NOT NULL,
						time_answer_to_end int(11) NOT NULL,
						price decimal(12,5) NOT NULL,
						status varchar(15) NOT NULL,
						type smallint NOT NULL,
						voipaccountid int(11) NOT NULL,
						PRIMARY KEY (id));");

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2016053000', 'dbversion'));

$this->CommitTrans();
