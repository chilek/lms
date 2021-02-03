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

$this->Execute("DROP SEQUENCE IF EXISTS voip_rules_id_seq;
					CREATE SEQUENCE voip_rules_id_seq;
					DROP TABLE IF EXISTS voip_rules CASCADE;
					CREATE TABLE voip_rules (
						id integer DEFAULT nextval('voip_rules_id_seq'::text) NOT NULL,
						name text NOT NULL,
						description text NULL,
						PRIMARY KEY (id)
					);

					DROP SEQUENCE IF EXISTS voip_group_rule_assignments_id_seq;
					CREATE SEQUENCE voip_group_rule_assignments_id_seq;
					DROP TABLE IF EXISTS voip_group_rule_assignments CASCADE;
					CREATE TABLE voip_group_rule_assignments (
						id integer DEFAULT nextval('voip_group_rule_assignments_id_seq'::text) NOT NULL,
						ruleid integer NOT NULL
							REFERENCES voip_rules (id) ON DELETE CASCADE ON UPDATE CASCADE,
						groupid integer NOT NULL
							REFERENCES voip_prefix_groups (id) ON DELETE CASCADE ON UPDATE CASCADE,
						rule_settings text NULL,
						PRIMARY KEY (id)
					);

					DROP SEQUENCE IF EXISTS voip_tariff_rules_id_seq;
					CREATE SEQUENCE voip_tariff_rules_id_seq;
					DROP TABLE IF EXISTS voip_tariff_rules CASCADE;
					CREATE TABLE voip_tariff_rules (
						id integer DEFAULT nextval('voip_tariff_rules_id_seq'::text) NOT NULL,
						tarifid integer NOT NULL
							REFERENCES tariffs (id) ON DELETE CASCADE ON UPDATE CASCADE,
						ruleid integer NULL
							REFERENCES voip_rules (id) ON DELETE CASCADE ON UPDATE CASCADE,
						PRIMARY KEY (id)
					);");

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2016062401', 'dbversion'));

$this->CommitTrans();
