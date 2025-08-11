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


$this->Execute("UPDATE customerassignments SET startdate = 0 WHERE startdate IS NULL");
$this->Execute("ALTER TABLE customerassignments MODIFY COLUMN startdate int(11) NOT NULL DEFAULT 0");

$this->Execute("DROP TRIGGER IF EXISTS customerassignments_insert_trigger");
$this->Execute("
    CREATE TRIGGER customerassignments_insert_trigger BEFORE INSERT ON customerassignments
        FOR EACH ROW
    BEGIN
        IF NEW.startdate = 0 THEN
            SET NEW.startdate = UNIX_TIMESTAMP();
        END IF;
    END
");

$this->Execute("DROP VIEW customerview");
$this->Execute("DROP VIEW vcustomerassignments");

$this->Execute("
    CREATE VIEW vcustomerassignments AS
        SELECT ca.*
        FROM customerassignments ca
        WHERE startdate <= UNIX_TIMESTAMP() AND enddate = 0
");

$this->Execute("
    CREATE VIEW customerview AS
        SELECT c.*,
            cc.consentdate AS consentdate,
            cc.invoicenotice AS invoicenotice,
            cc.mailingnotice AS mailingnotice,
            cc.smsnotice AS smsnotice,
            cc.einvoice AS einvoice,
            a1.country_id as countryid, a1.ccode,
            a1.zip as zip, a1.city as city,
            a1.street as street, a1.house as building, a1.flat as apartment,
            a2.country_id as post_countryid, a2.ccode AS post_ccode,
            a2.zip as post_zip, a2.city as post_city,
            a2.street as post_street, a2.house as post_building, a2.flat as post_apartment,
            a2.name as post_name, a1.address as address, a1.location AS full_address,
            a1.postoffice AS postoffice,
            a2.address as post_address, a2.location AS post_full_address,
            a2.postoffice AS post_postoffice
        FROM customers c
            JOIN customer_addresses ca1 ON c.id = ca1.customer_id AND ca1.type = 1
            LEFT JOIN vaddresses a1 ON ca1.address_id = a1.id
            LEFT JOIN customer_addresses ca2 ON c.id = ca2.customer_id AND ca2.type = 0
            LEFT JOIN vaddresses a2 ON ca2.address_id = a2.id
            LEFT JOIN customerconsentview cc ON cc.customerid = c.id
        WHERE NOT EXISTS (
            SELECT 1 FROM vcustomerassignments a
            JOIN excludedgroups e ON (a.customergroupid = e.customergroupid)
            WHERE e.userid = lms_current_user() AND a.customerid = c.id)
            AND (lms_current_user() = 0 OR c.divisionid IN (
                SELECT ud.divisionid
                FROM userdivisions ud
                WHERE ud.userid = lms_current_user()))
            AND c.type < 2;
");
