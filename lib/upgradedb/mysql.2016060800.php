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

$this->Execute("ALTER TABLE voip_cdr CHANGE status status smallint NOT NULL");
$this->Execute("ALTER TABLE voip_cdr CHANGE type type smallint NOT NULL");
$this->Execute("ALTER TABLE voip_cdr ADD COLUMN calleevoipaccountid int(11) NULL,
	ADD CONSTRAINT FOREIGN KEY(calleevoipaccountid) REFERENCES voipaccounts(id)");
$this->Execute("ALTER TABLE voip_cdr ADD COLUMN caller_flags smallint NOT NULL DEFAULT 0");
$this->Execute("ALTER TABLE voip_cdr ADD COLUMN callee_flags smallint NOT NULL DEFAULT 0");
$this->Execute("ALTER TABLE voip_cdr ADD COLUMN caller_prefix_group varchar(30) NULL");
$this->Execute("ALTER TABLE voip_cdr ADD COLUMN callee_prefix_group varchar(30) NULL");
$this->Execute("ALTER TABLE voip_cdr ADD COLUMN uniqueid varchar(20) NOT NULL");
$this->Execute("ALTER TABLE voip_cdr CHANGE voipaccountid callervoipaccountid INT(11) NULL,
	ADD CONSTRAINT FOREIGN KEY(callervoipaccountid) REFERENCES voipaccounts(id)");
$this->Execute("ALTER TABLE voipaccounts ADD COLUMN flags smallint NOT NULL DEFAULT 0");
$this->Execute("DROP TABLE voip_prefix_group_assignments");
$this->Execute("ALTER TABLE voip_prefixes ADD groupid int(11) NOT NULL,
	ADD CONSTRAINT FOREIGN KEY(groupid) REFERENCES voip_prefix_groups(id)");
$this->Execute("ALTER TABLE voip_prefixes DROP COLUMN name");
$this->Execute("ALTER TABLE voip_prefixes DROP COLUMN description");
$this->Execute("DROP TABLE voip_tariffs");
$this->Execute("DROP TABLE voip_tariff_rules");
$this->Execute("CREATE TABLE voip_tariffs (
	id int(11) AUTO_INCREMENT,
	groupid int(11) NOT NULL,
	tariffid int(11) NOT NULL,
	price decimal(12,5) NOT NULL,
	unitsize smallint NOT NULL,
	FOREIGN KEY (groupid) REFERENCES voip_prefix_groups(id) ON DELETE CASCADE ON UPDATE CASCADE,
	FOREIGN KEY (tariffid) REFERENCES tariffs(id) ON DELETE CASCADE ON UPDATE CASCADE,
	PRIMARY KEY (id)
) ENGINE=InnoDB;
");
$this->Execute("CREATE TABLE voip_tariff_rules (
	id int(11) AUTO_INCREMENT,
	groupid int(11) NOT NULL,
	tariffid int(11) NOT NULL,
	description text NULL,
	rule_settings text NULL,
	FOREIGN KEY (groupid) REFERENCES voip_prefix_groups(id) ON DELETE CASCADE ON UPDATE CASCADE,
	FOREIGN KEY (tariffid) REFERENCES tariffs(id) ON DELETE CASCADE ON UPDATE CASCADE,
	PRIMARY KEY (id)
) ENGINE=InnoDB");
$this->Execute("ALTER TABLE voip_prefix_groups ENGINE = InnoDB;");
$this->Execute("ALTER TABLE voip_prefixes ENGINE = InnoDB;");
$this->Execute("ALTER TABLE voip_cdr ENGINE = InnoDB;");

define('CONFIG_TYPE_POSITIVE_INTEGER', 2);
$this->Execute("INSERT INTO uiconfig (section, var, value, type) VALUES('phpui', 'billinglist_pagelimit', '100', ?)", array(CONFIG_TYPE_POSITIVE_INTEGER));

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2016060800', 'dbversion'));

$this->CommitTrans();
