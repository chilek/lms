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
						id int11 AUTO_INCREMENT PRIMARY KEY,
						prefix varchar(30) NOT NULL,
						name text NULL,
						description text NULL,
						UNIQUE (prefix));");

$this->Execute("CREATE TABLE voip_prefix_groups (
						id int11 AUTO_INCREMENT PRIMARY KEY,
						name text,
						description text);");

$this->Execute("CREATE TABLE voip_prefix_group_assignments (
						id int11 AUTO_INCREMENT PRIMARY KEY,
						prefixid int11 NOT NULL,
						groupid int11 NOT NULL,
						FOREIGN KEY (prefixid) REFERENCES voip_prefixes(id) ON DELETE CASCADE ON UPDATE CASCADE,
						FOREIGN KEY (groupid) REFERENCES voip_prefix_groups(id) ON DELETE CASCADE ON UPDATE CASCADE);");

$this->Execute("CREATE TABLE voip_tariffs (
						id int11 AUTO_INCREMENT PRIMARY KEY,
						prefixid int11 NULL,
						groupid int11 NULL,
						tariffid int11 NOT NULL,
						price text,
						unitsize smallint,
						FOREIGN KEY (prefixid) REFERENCES voip_prefixes(id) ON DELETE CASCADE ON UPDATE CASCADE,
						FOREIGN KEY (groupid) REFERENCES voip_prefix_groups(id) ON DELETE CASCADE ON UPDATE CASCADE,
						FOREIGN KEY (tariffid) REFERENCES tariffs(id) ON DELETE CASCADE ON UPDATE CASCADE);");

$this->Execute("CREATE TABLE voip_tariff_rules (
						id int11 AUTO_INCREMENT PRIMARY KEY,
						prefixid int11,
						groupid int11,
						tariffid int11,
						description text,
						unitsize smallint,
						price text,
						FOREIGN KEY (prefixid) REFERENCES voip_prefixes(id) ON DELETE CASCADE ON UPDATE CASCADE,
						FOREIGN KEY (groupid) REFERENCES voip_prefix_groups(id) ON DELETE CASCADE ON UPDATE CASCADE,
						FOREIGN KEY (tariffid) REFERENCES tariffs(id) ON DELETE CASCADE ON UPDATE CASCADE);");

$this->Execute("CREATE TABLE voip_cdr (
						id int11 AUTO_INCREMENT PRIMARY KEY,
						caller varchar(20) NOT NULL,
						callee varchar(20) NOT NULL,
						call_start_time int11 NOT NULL,
						time_start_to_end int11 NOT NULL,
						time_answer_to_end int11 NOT NULL,
						price float NOT NULL,
						status varchar(15) NOT NULL,
						type VARCHAR(1) NOT NULL,
						voipaccountid int11 NOT NULL);");

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2016053000', 'dbversion'));

$this->CommitTrans();

?>
