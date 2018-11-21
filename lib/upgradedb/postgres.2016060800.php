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
	ALTER TABLE voip_cdr ALTER COLUMN status TYPE smallint USING type::smallint;
	ALTER TABLE voip_cdr ALTER COLUMN type TYPE smallint USING type::smallint;
	ALTER TABLE voip_cdr ADD COLUMN calleevoipaccountid integer NULL
		REFERENCES voipaccounts(id) ON DELETE SET NULL ON UPDATE CASCADE;
	ALTER TABLE voip_cdr ADD COLUMN caller_flags smallint NOT NULL DEFAULT 0;
	ALTER TABLE voip_cdr ADD COLUMN callee_flags smallint NOT NULL DEFAULT 0;
	ALTER TABLE voip_cdr ADD COLUMN caller_prefix_group varchar(30) NULL;
	ALTER TABLE voip_cdr ADD COLUMN callee_prefix_group varchar(30) NULL;
	ALTER TABLE voip_cdr ADD COLUMN uniqueid varchar(20) NOT NULL;
	ALTER TABLE voip_cdr RENAME voipaccountid TO callervoipaccountid;
	ALTER TABLE voip_cdr ALTER COLUMN callervoipaccountid DROP NOT NULL;
	ALTER TABLE voip_cdr ADD FOREIGN KEY (callervoipaccountid)
		REFERENCES voipaccounts(id) ON DELETE SET NULL ON UPDATE CASCADE;
	ALTER TABLE voipaccounts ADD COLUMN flags smallint NOT NULL DEFAULT 0;
	DROP TABLE voip_prefix_group_assignments;
	ALTER TABLE voip_prefixes ADD COLUMN groupid smallint NOT NULL
		REFERENCES voip_prefix_groups(id) ON DELETE CASCADE ON UPDATE CASCADE;
	ALTER TABLE voip_prefixes DROP COLUMN name;
	ALTER TABLE voip_prefixes DROP COLUMN description;
	DROP TABLE voip_tariffs;
	DROP TABLE voip_tariff_rules;
	CREATE TABLE voip_tariffs (
		id integer DEFAULT nextval('voip_tariffs_id_seq'::text) NOT NULL,
		groupid integer NOT NULL
			REFERENCES voip_prefix_groups(id) ON DELETE CASCADE ON UPDATE CASCADE,
		tariffid integer NOT NULL
			REFERENCES tariffs(id) ON DELETE CASCADE ON UPDATE CASCADE,
		price numeric(12,5) NOT NULL,
		unitsize smallint NOT NULL,
		PRIMARY KEY (id)
	);
	CREATE TABLE voip_tariff_rules (
		id integer DEFAULT nextval('voip_tariff_rules_id_seq'::text) NOT NULL,
		groupid integer NOT NULL
			REFERENCES voip_prefix_groups(id) ON DELETE CASCADE ON UPDATE CASCADE,
		tarifid integer NOT NULL
			REFERENCES tariffs (id) ON DELETE CASCADE ON UPDATE CASCADE,
		description text NULL,
		rule_settings text NULL,
		PRIMARY KEY (id)
	);
");

define('CONFIG_TYPE_POSITIVE_INTEGER', 2);
$this->Execute("INSERT INTO uiconfig (section, var, value, type) VALUES('phpui', 'billinglist_pagelimit', '100', ?)", array(CONFIG_TYPE_POSITIVE_INTEGER));

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2016060800', 'dbversion'));

$this->CommitTrans();

?>
