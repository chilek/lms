<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2026 LMS Developers
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

$this->Execute("DROP VIEW vallusers");
$this->Execute("DROP VIEW vusers");

$this->Execute("ALTER TABLE users ALTER COLUMN lastloginip TYPE varchar(40)");
$this->Execute("ALTER TABLE users ALTER COLUMN failedloginip TYPE varchar(40)");

$this->Execute("
    CREATE VIEW vusers AS
        SELECT u.*, (u.firstname || ' ' || u.lastname) AS name, (u.lastname || ' ' || u.firstname) AS rname
        FROM users u
        LEFT JOIN userdivisions ud ON u.id = ud.userid
        WHERE lms_current_user() = 0 OR ud.divisionid IN (SELECT ud2.divisionid
             FROM userdivisions ud2
             WHERE ud2.userid = lms_current_user())
        GROUP BY u.id
");

$this->Execute("
    CREATE VIEW vallusers AS
        SELECT *, (firstname || ' ' || lastname) AS name, (lastname || ' ' || firstname) AS rname
        FROM users
");

$this->Execute("ALTER TABLE up_customers ALTER COLUMN lastloginip TYPE varchar(40)");
$this->Execute("ALTER TABLE up_customers ALTER COLUMN failedloginip TYPE varchar(40)");

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2026020901', 'dbversion'));

$this->CommitTrans();
