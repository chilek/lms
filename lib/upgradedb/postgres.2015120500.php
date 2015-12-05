<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2015 LMS Developers
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
	ALTER TABLE records ALTER COLUMN disabled DROP DEFAULT;
	ALTER TABLE records ALTER COLUMN disabled TYPE smallint USING CASE WHEN disabled THEN 1 ELSE 0 END;
	ALTER TABLE records ALTER COLUMN disabled SET DEFAULT 0;
	ALTER TABLE records ALTER COLUMN auth DROP DEFAULT;
	ALTER TABLE records ALTER COLUMN auth TYPE smallint USING CASE WHEN auth THEN 1 ELSE 0 END;
	ALTER TABLE records ALTER COLUMN auth SET DEFAULT 1
");

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2015120500', 'dbversion'));

$this->CommitTrans();

?>
