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

$this->Execute("ALTER TABLE tariffs ADD COLUMN voip_tariff_id int(11) DEFAULT NULL;

                CREATE TABLE voip_price_groups (
                   id              int(11)       NOT NULL AUTO_INCREMENT,
                   voip_tariff_id  int(11)       NOT NULL,
                   prefix_group_id int(11)       NOT NULL,
                   price           decimal(12,5) DEFAULT 0 NOT NULL,
                   unitsize        smallint      DEFAULT 0 NOT NULL,
                   PRIMARY KEY (id)
                ) ENGINE=InnoDB;

                INSERT INTO
                   voip_price_groups (voip_tariff_id, prefix_group_id, price, unitsize)
                SELECT tariffid, groupid, price, unitsize FROM voip_tariffs;

                DROP TABLE IF EXISTS voip_tariffs;

                CREATE TABLE voip_tariffs (
                   id          int(11)      NOT NULL AUTO_INCREMENT,
                   name        varchar(100) NOT NULL,
                   description text         NULL DEFAULT NULL,
                   PRIMARY KEY (id)
                ) ENGINE=InnoDB;

                INSERT INTO voip_tariffs (id, name)
                SELECT DISTINCT voip_tariff_id, 'default_name' FROM voip_price_groups;

                ALTER TABLE voip_price_groups ADD CONSTRAINT price_tariffid
                FOREIGN KEY (voip_tariff_id) REFERENCES voip_tariffs (id);

                ALTER TABLE tariffs ADD CONSTRAINT tariffs_tariffid
                FOREIGN KEY (voip_tariff_id) REFERENCES voip_tariffs (id);");

$this->Execute("UPDATE tariffs SET voip_tariff_id = id WHERE type = ?;", array(TARIFF_PHONE));

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2016071800', 'dbversion'));

$this->CommitTrans();

?>
