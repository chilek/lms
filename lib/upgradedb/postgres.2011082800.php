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

$this->Execute("
    -- wojewodztwa
    CREATE SEQUENCE location_states_id_seq;
    CREATE TABLE location_states (
        id integer          DEFAULT nextval('location_states_id_seq'::text) NOT NULL,
        ident varchar(8)    NOT NULL, -- TERYT: WOJ
        name varchar(64)    NOT NULL, -- TERYT: NAZWA
        PRIMARY KEY (id),
        UNIQUE (name)
    );
    -- powiaty
    CREATE SEQUENCE location_districts_id_seq;
    CREATE TABLE location_districts (
        id integer          DEFAULT nextval('location_districts_id_seq'::text) NOT NULL,
        name varchar(64)    NOT NULL, --TERYT: NAZWA
        ident varchar(8)    NOT NULL, --TERYT: POW
        stateid integer     NOT NULL  --TERYT: WOJ
            REFERENCES location_states (id) ON DELETE CASCADE ON UPDATE CASCADE,
        PRIMARY KEY (id),
        UNIQUE (stateid, name)
    );

    -- gminy
    CREATE SEQUENCE location_boroughs_id_seq;
    CREATE TABLE location_boroughs (
        id integer          DEFAULT nextval('location_boroughs_id_seq'::text) NOT NULL,
        name varchar(64)    NOT NULL, -- TERYT: NAZWA
        ident varchar(8)    NOT NULL, -- TERYT: GMI
        districtid integer  NOT NULL
            REFERENCES location_districts (id) ON DELETE CASCADE ON UPDATE CASCADE,
        type smallint       NOT NULL, -- TERYT: RODZ
        PRIMARY KEY (id),
        UNIQUE (districtid, name, type)
    );

    -- miasta
    CREATE SEQUENCE location_cities_id_seq;
    CREATE TABLE location_cities (
        id integer          DEFAULT nextval('location_cities_id_seq'::text) NOT NULL,
        ident varchar(8)    NOT NULL, -- TERYT: SYM / SYMPOD
        name varchar(64)    NOT NULL, -- TERYT: NAZWA
        cityid integer      DEFAULT NULL,
        boroughid integer   DEFAULT NULL
            REFERENCES location_boroughs (id) ON DELETE CASCADE ON UPDATE CASCADE,
        PRIMARY KEY (id)
    );
    CREATE INDEX location_cities_cityid ON location_cities (cityid);
    CREATE INDEX location_cities_boroughid ON location_cities (boroughid, name);

    -- cechy ulic
    CREATE SEQUENCE location_street_types_id_seq;
    CREATE TABLE location_street_types (
        id integer          DEFAULT nextval('location_street_types_id_seq'::text) NOT NULL,
        name varchar(8)     NOT NULL, -- TERYT: CECHA
        PRIMARY KEY (id)
    );
    -- ulice
    CREATE SEQUENCE location_streets_id_seq;
    CREATE TABLE location_streets (
        id integer          DEFAULT nextval('location_streets_id_seq'::text) NOT NULL,
        name varchar(128)   NOT NULL, -- TERYT: NAZWA_1
        ident varchar(8)    NOT NULL, -- TERYT: SYM_UL
        typeid integer      DEFAULT NULL
            REFERENCES location_street_types (id) ON DELETE SET NULL ON UPDATE CASCADE,
        cityid integer      NOT NULL
            REFERENCES location_cities (id) ON DELETE CASCADE ON UPDATE CASCADE,
        PRIMARY KEY (id),
        UNIQUE (cityid, name, ident)
    );

    -- netdevices
    ALTER TABLE netdevices ADD location_street integer DEFAULT NULL
        REFERENCES location_streets (id) ON DELETE SET NULL ON UPDATE CASCADE;
    ALTER TABLE netdevices ADD location_city integer DEFAULT NULL
        REFERENCES location_cities (id) ON DELETE SET NULL ON UPDATE CASCADE;
    ALTER TABLE netdevices ADD location_house varchar(8) DEFAULT NULL;
    ALTER TABLE netdevices ADD location_flat varchar(8) DEFAULT NULL;
    CREATE INDEX netdevices_location_street_idx ON netdevices (location_street);
    CREATE INDEX netdevices_location_city_idx ON netdevices (location_city, location_street, location_house, location_flat);

    -- nodes
    ALTER TABLE nodes ADD location varchar(255) DEFAULT NULL;
");

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

$this->Execute("
    -- do we need zip code for node address? No.
    ALTER TABLE nodes DROP location_zip CASCADE;
    ALTER TABLE nodes DROP location_city CASCADE;
    ALTER TABLE nodes DROP location_address CASCADE;

    -- nodes
    ALTER TABLE nodes ADD location_city integer DEFAULT NULL
        REFERENCES location_cities (id) ON DELETE SET NULL ON UPDATE CASCADE;
    ALTER TABLE nodes ADD location_street integer DEFAULT NULL
        REFERENCES location_streets (id) ON DELETE SET NULL ON UPDATE CASCADE;
    ALTER TABLE nodes ADD location_house varchar(8) DEFAULT NULL;
    ALTER TABLE nodes ADD location_flat varchar(8) DEFAULT NULL;
    CREATE INDEX nodes_location_street_idx ON nodes (location_street);
    CREATE INDEX nodes_location_city_idx ON nodes (location_city, location_street, location_house, location_flat);

    -- TERYT database views (irrelevant fields are skipped)
    CREATE VIEW teryt_terc AS
    SELECT ident AS woj, 0::text AS pow, 0::text AS gmi, 0 AS rodz,
        UPPER(name) AS nazwa
    FROM location_states
    UNION
    SELECT s.ident AS woj, d.ident AS pow, 0::text AS gmi, 0 AS rodz,
        d.name AS nazwa
    FROM location_districts d
    JOIN location_states s ON (d.stateid = s.id)
    UNION
    SELECT s.ident AS woj, d.ident AS pow, b.ident AS gmi, b.type AS rodz,
        b.name AS nazwa
    FROM location_boroughs b
    JOIN location_districts d ON (b.districtid = d.id)
    JOIN location_states s ON (d.stateid = s.id);

    CREATE VIEW teryt_simc AS
    SELECT s.ident AS woj, d.ident AS pow, b.ident AS gmi, b.type AS rodz_gmi,
        c.ident AS sym, c.name AS nazwa,
        (CASE WHEN cc.ident IS NOT NULL THEN cc.ident ELSE c.ident END) AS sympod
    FROM location_cities c
    JOIN location_boroughs b ON (c.boroughid = b.id)
    JOIN location_districts d ON (b.districtid = d.id)
    JOIN location_states s ON (d.stateid = s.id)
    LEFT JOIN location_cities cc ON (c.cityid = cc.id);

    CREATE VIEW teryt_ulic AS
    SELECT st.ident AS woj, d.ident AS pow, b.ident AS gmi, b.type AS rodz_gmi,
        c.ident AS sym, s.ident AS sym_ul, s.name AS nazwa_1, t.name AS cecha, s.id
    FROM location_streets s
    JOIN location_street_types t ON (s.typeid = t.id)
    JOIN location_cities c ON (s.cityid = c.id)
    JOIN location_boroughs b ON (c.boroughid = b.id)
    JOIN location_districts d ON (b.districtid = d.id)
    JOIN location_states st ON (d.stateid = st.id);

    CREATE VIEW vnodes AS
    SELECT n.*, m.mac
    FROM nodes n
    LEFT JOIN (SELECT nodeid, array_to_string(array_agg(mac), ',') AS mac
        FROM macs GROUP BY nodeid) m ON (n.id = m.nodeid);

    CREATE VIEW vmacs AS
    SELECT n.*, m.mac, m.id AS macid
        FROM nodes n
        JOIN macs m ON (n.id = m.nodeid);
");

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2011082800', 'dbversion'));

$this->CommitTrans();
