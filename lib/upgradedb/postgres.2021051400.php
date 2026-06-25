<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2021 LMS Developers
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


if (!$this->ResourceExists('divisions.birthdate', LMSDB::RESOURCE_TYPE_COLUMN)) {
    $this->Execute("
        DROP VIEW vdivisions;
        ALTER TABLE divisions ADD COLUMN birthdate integer DEFAULT NULL;
        CREATE VIEW vdivisions AS
            SELECT d.*,
                a.country_id as countryid, a.ccode, a.zip as zip, a.city as city, a.address,
                (CASE WHEN d.firstname IS NOT NULL AND d.lastname IS NOT NULL AND d.birthdate IS NOT NULL THEN 1 ELSE 0 END) AS naturalperson
            FROM divisions d
                JOIN vaddresses a ON a.id = d.address_id
    ");
}
