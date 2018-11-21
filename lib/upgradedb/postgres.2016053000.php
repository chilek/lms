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

$this->Execute("
	CREATE SEQUENCE voip_prefixes_id_seq;
	CREATE TABLE voip_prefixes (
		id integer DEFAULT nextval('voip_prefixes_id_seq'::text) NOT NULL,
		prefix varchar(30) NOT NULL,
		name text NULL,
		description text NULL,
		PRIMARY KEY (id),
		UNIQUE (prefix)
	);

	CREATE SEQUENCE voip_prefix_groups_id_seq;
	CREATE TABLE voip_prefix_groups (
		id integer DEFAULT nextval('voip_prefix_groups_id_seq'::text) NOT NULL,
		name text NOT NULL,
		description text NULL,
		PRIMARY KEY (id)
	);

	CREATE SEQUENCE voip_prefix_group_assignments_id_seq;
	CREATE TABLE voip_prefix_group_assignments (
		id integer DEFAULT nextval('voip_prefix_group_assignments_id_seq'::text) NOT NULL,
		prefixid integer NOT NULL
			REFERENCES voip_prefixes(id) ON DELETE CASCADE ON UPDATE CASCADE,
		groupid integer NOT NULL
			REFERENCES voip_prefix_groups(id) ON DELETE CASCADE ON UPDATE CASCADE,
		PRIMARY KEY (id)
	);

	CREATE SEQUENCE voip_tariffs_id_seq;
	CREATE TABLE voip_tariffs (
		id integer DEFAULT nextval('voip_tariffs_id_seq'::text) NOT NULL,
		prefixid integer NULL
			REFERENCES voip_prefixes(id) ON DELETE CASCADE ON UPDATE CASCADE,
		groupid integer NULL
			REFERENCES voip_prefix_groups(id) ON DELETE CASCADE ON UPDATE CASCADE,
		tariffid integer NOT NULL
			REFERENCES tariffs(id) ON DELETE CASCADE ON UPDATE CASCADE,
		PRIMARY KEY (id)
	);

	CREATE SEQUENCE voip_tariff_rules_id_seq;
	CREATE TABLE voip_tariff_rules (
		id integer DEFAULT nextval('voip_tariff_rules_id_seq'::text) NOT NULL,
		prefixid integer NULL
			REFERENCES voip_prefixes(id) ON DELETE CASCADE ON UPDATE CASCADE,
		groupid integer NULL
			REFERENCES voip_prefix_groups(id) ON DELETE CASCADE ON UPDATE CASCADE,
		tarifid integer NOT NULL
			REFERENCES tariffs (id) ON DELETE CASCADE ON UPDATE CASCADE,
		description text NULL,
		unitsize smallint NULL,
		price numeric(12,5) NOT NULL,
		PRIMARY KEY (id)
	);

	CREATE SEQUENCE voip_cdr_id_seq;
	CREATE TABLE voip_cdr (
		id integer DEFAULT nextval('voip_cdr_id_seq'::text) NOT NULL,
		caller varchar(20) NOT NULL,
		callee varchar(20) NOT NULL,
		call_start_time integer NOT NULL,
		time_start_to_end integer NOT NULL,
		time_answer_to_end integer NOT NULL,
		price numeric(12,5) NOT NULL,
		status varchar(15) NOT NULL,
		type smallint NOT NULL,
		voipaccountid integer NOT NULL,
		PRIMARY KEY (id)
	);
");

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2016053000', 'dbversion'));

$this->CommitTrans();

?>
