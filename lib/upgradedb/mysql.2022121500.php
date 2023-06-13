<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2022 LMS Developers
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

if (!$this->ResourceExists('users.trustedhosts', LMSDB::RESOURCE_TYPE_COLUMN)) {
    $this->Execute("DROP VIEW vallusers");
    $this->Execute("DROP VIEW vusers");

    $this->Execute("ALTER TABLE users ADD COLUMN trustedhosts varchar(256) DEFAULT NULL");

    $this->Execute("
        CREATE VIEW vusers AS
            SELECT u.*, CONCAT(u.firstname, ' ', u.lastname) AS name, CONCAT(u.lastname, ' ', u.firstname) AS rname
            FROM users u
            LEFT JOIN userdivisions ud ON u.id = ud.userid
            WHERE lms_current_user() = 0 OR ud.divisionid IN (
                SELECT ud2.divisionid
                FROM userdivisions ud2
                WHERE ud2.userid = lms_current_user()
            )
            GROUP BY u.id
    ");

    $this->Execute("
        CREATE VIEW vallusers AS
            SELECT *, CONCAT(firstname, ' ', lastname) AS name, CONCAT(lastname, ' ', firstname) AS rname
            FROM users
    ");
}

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2022121500', 'dbversion'));

$this->CommitTrans();
