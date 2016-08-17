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

$this->Execute("ALTER TABLE tariffs ADD COLUMN voip_tariff_id int(11) DEFAULT NULL");

$this->Execute("ALTER TABLE tariffs ADD COLUMN voip_tariff_rule_id int(11) DEFAULT NULL");
$this->Execute("ALTER TABLE voip_prefixes DROP INDEX prefix");
$this->Execute("ALTER TABLE voip_prefixes ADD UNIQUE (prefix, groupid)");

$this->Execute("CREATE TABLE voip_price_groups (
   id              int(11)       NOT NULL AUTO_INCREMENT,
   voip_tariff_id  int(11)       NOT NULL,
   prefix_group_id int(11)       NOT NULL,
   price           decimal(12,5) DEFAULT 0 NOT NULL,
   unitsize        smallint      DEFAULT 0 NOT NULL,
   PRIMARY KEY (id)
) ENGINE=InnoDB");

$this->Execute("INSERT INTO voip_price_groups (voip_tariff_id, prefix_group_id, price, unitsize)
	SELECT tariffid, groupid, price, unitsize FROM voip_tariffs");

$this->Execute("DROP TABLE IF EXISTS voip_tariffs");
$this->Execute("CREATE TABLE voip_tariffs (
   id          int(11)      NOT NULL AUTO_INCREMENT,
   name        varchar(100) NOT NULL,
   description text         NULL DEFAULT NULL,
   PRIMARY KEY (id)
) ENGINE=InnoDB");

$this->Execute("INSERT INTO voip_tariffs (id, name)
	SELECT DISTINCT voip_tariff_id, 'default_name' FROM voip_price_groups");

$this->Execute("ALTER TABLE voip_price_groups ADD CONSTRAINT price_tariffid
	FOREIGN KEY (voip_tariff_id) REFERENCES voip_tariffs (id) ON DELETE CASCADE ON UPDATE CASCADE");

$this->Execute("ALTER TABLE voip_price_groups ADD CONSTRAINT group_id_fk
	FOREIGN KEY (prefix_group_id) REFERENCES voip_prefix_groups (id) ON DELETE CASCADE ON UPDATE CASCADE");

$this->Execute("ALTER TABLE tariffs ADD CONSTRAINT tariff_id_fk
	FOREIGN KEY (voip_tariff_id) REFERENCES voip_tariffs (id) ON DELETE SET NULL ON UPDATE CASCADE");

$this->Execute("ALTER TABLE tariffs ADD CONSTRAINT tariff_rule_id_fk
	FOREIGN KEY (voip_tariff_rule_id) REFERENCES voip_rules (id) ON DELETE SET NULL ON UPDATE CASCADE");

$this->Execute("CREATE TABLE voip_rule_states (
  id              int(11) NOT NULL AUTO_INCREMENT,
  voip_account_id int(11) NOT NULL,
  rule_id         int(11) NOT NULL,
  units_left      int(11) NOT NULL,
  PRIMARY KEY(id),
  UNIQUE(voip_account_id, rule_id),
  FOREIGN KEY (voip_account_id) REFERENCES voipaccounts (id) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (rule_id) REFERENCES voip_group_rule_assignments (id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB");

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2016080500', 'dbversion'));

$this->CommitTrans();

?>
