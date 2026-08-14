<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2024 LMS Developers
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

if (!$this->ResourceExists('divisions.servicephone', LMSDB::RESOURCE_TYPE_COLUMN)) {
    $this->Execute("DROP VIEW vdivisions");

    $this->Execute("ALTER TABLE divisions ADD COLUMN servicephone varchar(40) DEFAULT NULL");

    $this->Execute(
        "CREATE VIEW vdivisions AS
            SELECT d.*,
               a.country_id AS countryid,
                a.ccode,
                a.zip AS zip,
                a.city AS city,
                a.address,
                oa.country_id AS office_countryid,
                oa.ccode AS office_ccode,
                oa.zip AS office_zip,
                oa.city AS office_city,
                oa.address AS office_address,
                (CASE WHEN d.firstname IS NOT NULL AND d.lastname IS NOT NULL AND d.birthdate IS NOT NULL THEN 1 ELSE 0 END) AS naturalperson
            FROM divisions d
            JOIN vaddresses a ON a.id = d.address_id
            LEFT JOIN vaddresses oa ON oa.id = d.office_address_id"
    );
}

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2024112100', 'dbversion'));

$this->CommitTrans();
