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

$this->BeginTrans();

$this->Execute("
CREATE SEQUENCE promotions_id_seq;
CREATE TABLE promotions (
    id integer      DEFAULT nextval('promotions_id_seq'::text) NOT NULL,
    name varchar(255) NOT NULL,
    description text DEFAULT NULL,
    disabled smallint DEFAULT 0 NOT NULL,
    PRIMARY KEY (id),
    UNIQUE (name)
);

CREATE SEQUENCE promotionschemas_id_seq;
CREATE TABLE promotionschemas (
    id integer      DEFAULT nextval('promotionschemas_id_seq'::text) NOT NULL,
    name varchar(255) NOT NULL,
    description text DEFAULT NULL,
    data text DEFAULT NULL,
    promotionid integer DEFAULT NULL
        REFERENCES promotions (id) ON DELETE CASCADE ON UPDATE CASCADE,
    disabled smallint DEFAULT 0 NOT NULL,
    PRIMARY KEY (id),
    UNIQUE (promotionid, name)
);

CREATE SEQUENCE promotionassignments_id_seq;
CREATE TABLE promotionassignments (
    id integer      DEFAULT nextval('promotionassignments_id_seq'::text) NOT NULL,
    promotionschemaid integer DEFAULT NULL
        REFERENCES promotionschemas (id) ON DELETE CASCADE ON UPDATE CASCADE,
    tariffid integer DEFAULT NULL
        REFERENCES tariffs (id) ON DELETE CASCADE ON UPDATE CASCADE,
    data text DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE (promotionschemaid, tariffid)
);
CREATE INDEX promotionassignments_tariffid_idx ON promotionassignments (tariffid);

ALTER TABLE tariffs DROP CONSTRAINT tariffs_name_key;
ALTER TABLE tariffs ADD CONSTRAINT tariffs_name_key UNIQUE(name, value, period);
");


$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2011022000', 'dbversion'));

$this->CommitTrans();
