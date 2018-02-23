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

$this->Execute("DELETE FROM pna WHERE fromhouse REGEXP '[a-z]' OR tohouse REGEXP '[a-z]'");

$this->Execute("ALTER TABLE pna CHANGE COLUMN fromhouse fromnumber smallint DEFAULT NULL");
$this->Execute("ALTER TABLE pna ADD COLUMN fromletter varchar(8) DEFAULT NULL");
$this->Execute("ALTER TABLE pna CHANGE COLUMN tohouse tonumber smallint DEFAULT NULL");
$this->Execute("ALTER TABLE pna ADD COLUMN toletter varchar(8) DEFAULT NULL");

$this->Execute("ALTER TABLE pna DROP INDEX zip,
	ADD UNIQUE zip (zip, cityid, streetid, fromnumber, fromletter, tonumber, toletter, parity)");

$this->Execute("CREATE INDEX pna_fromnumber_idx ON pna (fromnumber)");
$this->Execute("CREATE INDEX pna_tonumber_idx ON pna (tonumber)");

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2018022200', 'dbversion'));

$this->CommitTrans();

?>
