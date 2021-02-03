<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2018 LMS Developers
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

$this->Execute("DELETE FROM pna WHERE fromhouse ~ '[a-z]' OR tohouse ~ '[a-z]'");

if ($this->ResourceExists('pna_zip_key', LMSDB::RESOURCE_TYPE_CONSTRAINT)) {
    $this->Execute("ALTER TABLE pna DROP CONSTRAINT pna_zip_key");
} else {
    $this->Execute("ALTER TABLE pna DROP CONSTRAINT pna_zip_cityid_streetid_fromhouse_tohouse_parity_key");
}

$this->Execute("
	ALTER TABLE pna ALTER COLUMN fromhouse DROP DEFAULT;
	ALTER TABLE pna ALTER COLUMN fromhouse TYPE smallint USING fromhouse::smallint;
	ALTER TABLE pna ALTER COLUMN fromhouse SET DEFAULT NULL;
	ALTER TABLE pna RENAME COLUMN fromhouse TO fromnumber;
	ALTER TABLE pna ADD COLUMN fromletter varchar(8) DEFAULT NULL;

	ALTER TABLE pna ALTER COLUMN tohouse DROP DEFAULT;
	ALTER TABLE pna ALTER COLUMN tohouse TYPE smallint USING tohouse::smallint;
	ALTER TABLE pna ALTER COLUMN tohouse SET DEFAULT NULL;
	ALTER TABLE pna RENAME COLUMN tohouse TO tonumber;
	ALTER TABLE pna ADD COLUMN toletter varchar(8) DEFAULT NULL;

	ALTER TABLE pna ADD CONSTRAINT pna_zip_cityid_streetid_fromnumber_tonumber_parity_key
		UNIQUE (zip, cityid, streetid, fromnumber, fromletter, tonumber, toletter, parity);

	CREATE INDEX pna_fromnumber_idx ON pna (fromnumber);
	CREATE INDEX pna_tonumber_idx ON pna (tonumber)
");

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2018022200', 'dbversion'));

$this->CommitTrans();
