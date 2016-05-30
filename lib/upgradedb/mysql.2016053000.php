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

$this->Execute("CREATE TABLE voip_prefix (
						id SERIAL PRIMARY KEY,
						prefix varchar(30),
						name text,
						description text,
						UNIQUE (prefix))");

$this->Execute("CREATE TABLE voip_prefix_group (
						id SERIAL PRIMARY KEY,
						name text,
						description text)");

$this->Execute("CREATE TABLE voip_prefix_group_assignments (
						id SERIAL PRIMARY KEY,
						prefixid int,
						groupid int,
						FOREIGN KEY (prefixid) REFERENCES voip_prefix(id),
						FOREIGN KEY (groupid) REFERENCES voip_prefix_group(id))");

$this->Execute("CREATE TABLE voip_tariffs (
						id SERIAL PRIMARY KEY,
						prefixid int,
						groupid int,
						tariffid int,
						price text,
						unitsize smallint,
						FOREIGN KEY (prefixid) REFERENCES voip_prefix(id),
						FOREIGN KEY (groupid) REFERENCES voip_prefix_group(id),
						FOREIGN KEY (tarifid) REFERENCES tariffs(id))");

$this->Execute("CREATE TABLE voip_tariff_rules (
						id SERIAL PRIMARY KEY,
						prefixid int,
						groupid int,
						tarifid int,
						description text,
						unitsize smallint,
						price text,
						FOREIGN KEY (prefixid) REFERENCES voip_prefix(id),
						FOREIGN KEY (groupid) REFERENCES voip_prefix_group(id),
						FOREIGN KEY (tarifid) REFERENCES tariffs(id))");						
						
$this->Execute("CREATE TABLE voip_cdr (
						ID SERIAL PRIMARY KEY,
						caller varchar(20) NOT NULL,
						callee varchar(20) NOT NULL,
						call_start_time int NOT NULL,
						time_start_to_end int NOT NULL,
						time_answer_to_end int NOT NULL,
						price float NOT NULL,
						status varchar(15) NOT NULL,
						type VARCHAR(1) NOT NULL,
						voipaccountid int NOT NULL)");

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2016053000', 'dbversion'));

$this->CommitTrans();

?>
