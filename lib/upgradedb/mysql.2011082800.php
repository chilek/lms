<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2013 LMS Developers
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

// TERYT

$this->BeginTrans();

$this->Execute("DROP VIEW vnodes");

// wojewodztwa
$this->Execute("
    CREATE TABLE location_states (
        id int(11)          NOT NULL auto_increment,
        ident varchar(8)    NOT NULL, -- TERYT: WOJ
        name varchar(64)    NOT NULL, -- TERYT: NAZWA
        PRIMARY KEY (id),
        UNIQUE KEY name (name)
    ) ENGINE=INNODB");
// powiaty
$this->Execute("
    CREATE TABLE location_districts (
        id int(11)          NOT NULL auto_increment,
        name varchar(64)    NOT NULL, -- TERYT: NAZWA
        ident varchar(8)    NOT NULL, -- TERYT: POW
        stateid int(11)     NOT NULL  -- TERYT: WOJ
            REFERENCES location_states (id) ON DELETE CASCADE ON UPDATE CASCADE,
        PRIMARY KEY (id),
        UNIQUE KEY stateid (stateid, name)
    ) ENGINE=INNODB");
// gminy
$this->Execute("
    CREATE TABLE location_boroughs (
        id int(11)          NOT NULL auto_increment,
        name varchar(64)    NOT NULL, -- TERYT: NAZWA
        ident varchar(8)    NOT NULL, -- TERYT: GMI
        districtid int(11)  NOT NULL
            REFERENCES location_districts (id) ON DELETE CASCADE ON UPDATE CASCADE,
        type smallint       NOT NULL, -- TERYT: RODZ
        PRIMARY KEY (id),
        UNIQUE KEY districtid (districtid, name, type)
    ) ENGINE=INNODB");
// miasta
$this->Execute("
    CREATE TABLE location_cities (
        id int(11)          NOT NULL auto_increment,
        ident varchar(8)    NOT NULL, -- TERYT: SYM / SYMPOD
        name varchar(64)    NOT NULL, -- TERYT: NAZWA
        cityid int(11)      DEFAULT NULL,
        boroughid int(11)   DEFAULT NULL
            REFERENCES location_boroughs (id) ON DELETE CASCADE ON UPDATE CASCADE,
        PRIMARY KEY (id),
        INDEX cityid (cityid),
        INDEX boroughid (boroughid, name)
    ) ENGINE=INNODB");
// cechy ulic
$this->Execute("
    CREATE TABLE location_street_types (
        id int(11)          NOT NULL auto_increment,
        name varchar(8)     NOT NULL, -- TERYT: CECHA
        PRIMARY KEY (id)
    ) ENGINE=INNODB");
// ulice
$this->Execute("
    CREATE TABLE location_streets (
        id int(11)          NOT NULL auto_increment,
        name varchar(128)   NOT NULL, -- TERYT: NAZWA_1
        ident varchar(8)    NOT NULL, -- TERYT: SYM_UL
        typeid int(11)      DEFAULT NULL
            REFERENCES location_street_types (id) ON DELETE SET NULL ON UPDATE CASCADE,
        cityid int(11)      NOT NULL
            REFERENCES location_cities (id) ON DELETE CASCADE ON UPDATE CASCADE,
        PRIMARY KEY (id),
        UNIQUE (cityid, name, ident)
    ) ENGINE=INNODB");

// netdevices
$this->Execute("ALTER TABLE netdevices ADD location_city int(11) DEFAULT NULL");
$this->Execute("ALTER TABLE netdevices ADD location_street int(11) DEFAULT NULL");
$this->Execute("ALTER TABLE netdevices ADD INDEX location_street (location_street)");
$this->Execute("ALTER TABLE netdevices ADD FOREIGN KEY (location_street) REFERENCES location_streets (id) ON DELETE SET NULL ON UPDATE CASCADE");
$this->Execute("ALTER TABLE netdevices ADD location_house varchar(8) DEFAULT NULL");
$this->Execute("ALTER TABLE netdevices ADD location_flat varchar(8) DEFAULT NULL");
$this->Execute("ALTER TABLE netdevices ADD INDEX location_city (location_city, location_street, location_house, location_flat)");
$this->Execute("ALTER TABLE netdevices ADD FOREIGN KEY (location_city) REFERENCES location_cities (id) ON DELETE SET NULL ON UPDATE CASCADE");

$this->Execute("ALTER TABLE nodes ADD location varchar(255) DEFAULT NULL");

$nodes = $this->GetAll("SELECT id, location_city AS city, location_address AS addr
    FROM nodes WHERE location_city <> '' OR location_address <> ''");
if ($nodes) {
    foreach ($nodes as $n) {
        $loc = $n['addr'];
        if ($n['city'] && strpos($loc, $n['city']) === false) {
            $loc = $n['city'] . ($loc ? ', ' . $loc : '');
        }

        $this->Execute("UPDATE nodes SET location = ? WHERE id = ?", array($loc, $n['id']));
    }
}

// do we need zip code for node address? No.
$this->Execute("ALTER TABLE nodes DROP location_zip CASCADE");
$this->Execute("ALTER TABLE nodes DROP location_city CASCADE");
$this->Execute("ALTER TABLE nodes DROP location_address CASCADE");

// nodes
$this->Execute("ALTER TABLE nodes ADD location_house varchar(8) DEFAULT NULL");
$this->Execute("ALTER TABLE nodes ADD location_flat varchar(8) DEFAULT NULL");
$this->Execute("ALTER TABLE nodes ADD location_city int(11) DEFAULT NULL");
$this->Execute("ALTER TABLE nodes ADD location_street int(11) DEFAULT NULL");
$this->Execute("ALTER TABLE nodes ADD INDEX location_city (location_city, location_street, location_house, location_flat)");
$this->Execute("ALTER TABLE nodes ADD FOREIGN KEY (location_city) REFERENCES location_cities (id) ON DELETE SET NULL ON UPDATE CASCADE");
$this->Execute("ALTER TABLE nodes ADD INDEX location_street (location_street)");
$this->Execute("ALTER TABLE nodes ADD FOREIGN KEY (location_street) REFERENCES location_streets (id) ON DELETE SET NULL ON UPDATE CASCADE");

// TERYT database views (irrelevant fields are skipped)
$this->Execute("
    CREATE VIEW teryt_terc AS
    SELECT ident AS woj, '0' AS pow, '0' AS gmi, 0 AS rodz,
        UPPER(name) AS nazwa
    FROM location_states
    UNION
    SELECT s.ident AS woj, d.ident AS pow, '0' AS gmi, 0 AS rodz,
        d.name AS nazwa
    FROM location_districts d
    JOIN location_states s ON (d.stateid = s.id)
    UNION
    SELECT s.ident AS woj, d.ident AS pow, b.ident AS gmi, b.type AS rodz,
        b.name AS nazwa
    FROM location_boroughs b
    JOIN location_districts d ON (b.districtid = d.id)
    JOIN location_states s ON (d.stateid = s.id)
");
$this->Execute("
    CREATE VIEW teryt_simc AS
    SELECT s.ident AS woj, d.ident AS pow, b.ident AS gmi, b.type AS rodz_gmi,
        c.ident AS sym, c.name AS nazwa,
        (CASE WHEN cc.ident IS NOT NULL THEN cc.ident ELSE c.ident END) AS sympod
    FROM location_cities c
    JOIN location_boroughs b ON (c.boroughid = b.id)
    JOIN location_districts d ON (b.districtid = d.id)
    JOIN location_states s ON (d.stateid = s.id)
    LEFT JOIN location_cities cc ON (c.cityid = cc.id)
");
$this->Execute("
    CREATE VIEW teryt_ulic AS
    SELECT st.ident AS woj, d.ident AS pow, b.ident AS gmi, b.type AS rodz_gmi,
        c.ident AS sym, s.ident AS sym_ul, s.name AS nazwa_1, t.name AS cecha, s.id
    FROM location_streets s
    JOIN location_street_types t ON (s.typeid = t.id)
    JOIN location_cities c ON (s.cityid = c.id)
    JOIN location_boroughs b ON (c.boroughid = b.id)
    JOIN location_districts d ON (b.districtid = d.id)
    JOIN location_states st ON (d.stateid = st.id)
");
$this->Execute("
    CREATE VIEW vnodes AS
    SELECT n.*, m.mac
    FROM nodes n
    LEFT JOIN vnodes_mac m ON (n.id = m.nodeid);
");

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2011082800', 'dbversion'));

$this->CommitTrans();
