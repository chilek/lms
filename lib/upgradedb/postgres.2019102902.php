<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2019 LMS Developers
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
    DROP VIEW vdivisions;
    ALTER TABLE divisions ADD COLUMN bank varchar(100) DEFAULT NULL;
    CREATE VIEW vdivisions AS
        SELECT d.*,
            a.country_id as countryid, a.zip as zip, a.city as city, a.address
        FROM divisions d
            JOIN vaddresses a ON a.id = d.address_id;
    ALTER TABLE documents ADD COLUMN div_bank varchar(100) DEFAULT NULL
");

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2019102902', 'dbversion'));

$this->CommitTrans();
