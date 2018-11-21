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

$this->Execute("ALTER TABLE tariffs ADD COLUMN voip_tariff_id integer DEFAULT NULL;
                ALTER TABLE tariffs ADD COLUMN voip_tariff_rule_id integer DEFAULT NULL;

                ALTER TABLE voip_prefixes DROP CONSTRAINT voip_prefixes_prefix_key;
                ALTER TABLE voip_prefixes ADD UNIQUE (prefix, groupid);

                DROP SEQUENCE IF EXISTS voip_price_groups_id_seq;
                CREATE SEQUENCE voip_price_groups_id_seq;
                DROP TABLE IF EXISTS voip_price_groups CASCADE;
                CREATE TABLE voip_price_groups (
                   id              integer       DEFAULT nextval('voip_price_groups_id_seq'::text) NOT NULL,
                   voip_tariff_id  integer       NOT NULL,
                   prefix_group_id integer       NOT NULL,
                   price           numeric(12,5) DEFAULT 0 NOT NULL,
                   unitsize        smallint      DEFAULT 0 NOT NULL,
                   PRIMARY KEY (id)
                );

                INSERT INTO
                   voip_price_groups (voip_tariff_id, prefix_group_id, price, unitsize)
                SELECT tariffid, groupid, price, unitsize FROM voip_tariffs;

                DROP SEQUENCE IF EXISTS voip_tariffs_id_seq;
                CREATE SEQUENCE voip_tariffs_id_seq;
                DROP TABLE IF EXISTS voip_tariffs CASCADE;
                CREATE TABLE voip_tariffs (
                   id          integer      DEFAULT nextval('voip_tariffs_id_seq'::text) NOT NULL,
                   name        varchar(100) NOT NULL,
                   description text         NULL DEFAULT NULL,
                   PRIMARY KEY (id)
                );

                INSERT INTO voip_tariffs (id, name)
                SELECT DISTINCT voip_tariff_id, 'default_name' FROM voip_price_groups;

                ALTER TABLE voip_price_groups ADD CONSTRAINT price_tariffid
                FOREIGN KEY (voip_tariff_id) REFERENCES voip_tariffs (id) ON DELETE CASCADE ON UPDATE CASCADE;

                ALTER TABLE voip_price_groups ADD CONSTRAINT group_id_fk
                FOREIGN KEY (prefix_group_id) REFERENCES voip_prefix_groups (id) ON DELETE CASCADE ON UPDATE CASCADE;

                ALTER TABLE tariffs ADD CONSTRAINT tariff_id_fk
                FOREIGN KEY (voip_tariff_id) REFERENCES voip_tariffs (id) ON DELETE SET NULL ON UPDATE CASCADE;

                ALTER TABLE tariffs ADD CONSTRAINT tariff_rule_id_fk
                FOREIGN KEY (voip_tariff_rule_id) REFERENCES voip_rules (id) ON DELETE SET NULL ON UPDATE CASCADE");

$this->Execute("DROP SEQUENCE IF EXISTS voip_rule_states_id_seq;
                CREATE SEQUENCE voip_rule_states_id_seq;
                DROP TABLE IF EXISTS voip_rule_states CASCADE;
                CREATE TABLE voip_rule_states (
                    id              integer DEFAULT nextval('voip_rule_states_id_seq'::text) NOT NULL,
                    voip_account_id integer NOT NULL DEFAULT NULL
                        REFERENCES voipaccounts (id) ON DELETE CASCADE ON UPDATE CASCADE,
                    rule_id         integer NOT NULL DEFAULT NULL
                        REFERENCES voip_group_rule_assignments (id) ON DELETE CASCADE ON UPDATE CASCADE,
                    units_left      integer NULL DEFAULT NULL,
                    PRIMARY KEY(id),
                    UNIQUE(voip_account_id, rule_id)
                );

                UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2016080500', 'dbversion'));

$this->CommitTrans();

?>
