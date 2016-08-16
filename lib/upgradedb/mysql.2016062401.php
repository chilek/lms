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

$this->Execute("CREATE TABLE voip_rules (
	id int(11) NOT NULL AUTO_INCREMENT,
	name text NOT NULL,
	description text NULL,
	PRIMARY KEY (id)
) ENGINE=InnoDB");

$this->Execute("CREATE TABLE voip_group_rule_assignments (
	id int(11) NOT NULL AUTO_INCREMENT,
	ruleid int(11) NOT NULL,
	groupid int(11) NOT NULL,
	rule_settings text NULL,
	FOREIGN KEY (ruleid) REFERENCES voip_rules(id) ON DELETE CASCADE ON UPDATE CASCADE,
	FOREIGN KEY (groupid) REFERENCES voip_prefix_groups(id) ON DELETE CASCADE ON UPDATE CASCADE,
	PRIMARY KEY (id)
) ENGINE=InnoDB");

$this->Execute("DROP TABLE IF EXISTS voip_tariff_rules");
$this->Execute("CREATE TABLE voip_tariff_rules (
	id int(11) NOT NULL AUTO_INCREMENT,
	tarifid int(11) NOT NULL,
	ruleid int(11) NULL,
	FOREIGN KEY (tarifid) REFERENCES tariffs(id) ON DELETE CASCADE ON UPDATE CASCADE,
	FOREIGN KEY (ruleid) REFERENCES voip_rules(id) ON DELETE CASCADE ON UPDATE CASCADE,
	PRIMARY KEY (id)
) ENGINE=InnoDB;");

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2016062401', 'dbversion'));

$this->CommitTrans();

?>
