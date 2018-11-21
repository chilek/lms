<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2017 LMS Developers
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
	DROP VIEW IF EXISTS customerview;
	DROP VIEW IF EXISTS contractorview;
	DROP VIEW IF EXISTS customeraddressview;
	DROP VIEW IF EXISTS vmacs;
	DROP VIEW IF EXISTS vnetworks;
	DROP VIEW IF EXISTS vnodes;
	DROP VIEW IF EXISTS vaddresses;
	DROP VIEW IF EXISTS vdivisions;
");

$this->Execute("ALTER TABLE addresses ALTER COLUMN city TYPE varchar(100)");

$this->Execute("
    CREATE VIEW vaddresses AS
        SELECT *, country_id AS countryid, city_id AS location_city, street_id AS location_street,
            house AS location_house, flat AS location_flat,
            (TRIM(both ' ' FROM
                 (CASE WHEN street IS NOT NULL THEN street ELSE '' END)
                 || (CASE WHEN house is NOT NULL
                         THEN (CASE WHEN flat IS NOT NULL THEN ' ' || house || '/' || flat ELSE ' ' || house END)
                         ELSE (CASE WHEN flat IS NOT NULL THEN ' ' || flat ELSE '' END)
                    END)
            )) AS address,
            (TRIM(both ' ' FROM
                 (CASE WHEN zip IS NOT NULL THEN zip || ' ' ELSE '' END)
                 || (CASE WHEN city IS NOT NULL
                     THEN city || ', ' ||
                         (CASE WHEN street IS NOT NULL THEN street ELSE '' END)
                         || (CASE WHEN house is NOT NULL
                                THEN (CASE WHEN flat IS NOT NULL THEN ' ' || house || '/' || flat ELSE ' ' || house END)
                                ELSE (CASE WHEN flat IS NOT NULL THEN ' ' || flat ELSE '' END)
                            END)
                     ELSE
                         (CASE WHEN street IS NOT NULL THEN street ELSE '' END)
                         || (CASE WHEN house is NOT NULL
                                 THEN (CASE WHEN flat IS NOT NULL THEN ' ' || house || '/' || flat ELSE ' ' || house END)
                                 ELSE (CASE WHEN flat IS NOT NULL THEN ' ' || flat ELSE '' END)
                            END)
                 END)
            )) AS location
        FROM addresses");

$this->Execute("
CREATE VIEW customerview AS
    SELECT c.*,
        a1.country_id as countryid, a1.zip as zip, a1.city as city,
        a1.street as street,a1.house as building, a1.flat as apartment,
        a2.country_id as post_countryid, a2.zip as post_zip,
        a2.city as post_city, a2.street as post_street, a2.name as post_name,
        a2.house as post_building, a2.flat as post_apartment,
        a1.location as address, a2.location as post_address
    FROM customers c
        JOIN customer_addresses ca1 ON c.id = ca1.customer_id AND ca1.type = 1
        LEFT JOIN vaddresses a1 ON ca1.address_id = a1.id
        LEFT JOIN customer_addresses ca2 ON c.id = ca2.customer_id AND ca2.type = 0
        LEFT JOIN vaddresses a2 ON ca2.address_id = a2.id
    WHERE NOT EXISTS (
        SELECT 1 FROM customerassignments a
        JOIN excludedgroups e ON (a.customergroupid = e.customergroupid)
        WHERE e.userid = lms_current_user() AND a.customerid = c.id)
        AND c.type < 2;
CREATE VIEW contractorview AS
    SELECT c.*,
        a1.country_id as countryid, a1.zip as zip, a1.city as city, a1.street as street,
        a1.house as building, a1.flat as apartment, a2.country_id as post_countryid, 
        a2.zip as post_zip, a2.city as post_city, a2.street as post_street, 
        a2.house as post_building, a2.flat as post_apartment, a2.name as post_name,
        a1.location as address, a2.location as post_address
    FROM customers c
        JOIN customer_addresses ca1 ON c.id = ca1.customer_id AND ca1.type = 1
        LEFT JOIN vaddresses a1 ON ca1.address_id = a1.id
        LEFT JOIN customer_addresses ca2 ON c.id = ca2.customer_id AND ca2.type = 0
        LEFT JOIN vaddresses a2 ON ca2.address_id = a2.id
    WHERE c.type = 2;
CREATE VIEW customeraddressview AS
    SELECT c.*,
        a1.country_id as countryid, a1.zip as zip, a1.city as city, a1.street as street,
        a1.house as building, a1.flat as apartment, a2.country_id as post_countryid,
        a2.zip as post_zip, a1.city as post_city, a2.street as post_street,
        a2.house as post_building, a2.flat as post_apartment, a2.name as post_name,
        a1.location as address, a2.location as post_address
    FROM customers c
        JOIN customer_addresses ca1 ON c.id = ca1.customer_id AND ca1.type = 1
        LEFT JOIN vaddresses a1 ON ca1.address_id = a1.id
        LEFT JOIN customer_addresses ca2 ON c.id = ca2.customer_id AND ca2.type = 0
        LEFT JOIN vaddresses a2 ON ca2.address_id = a2.id
    WHERE c.type < 2;
CREATE VIEW vmacs AS
    SELECT n.*, m.mac, m.id AS macid, a.city_id as location_city,
        a.street_id as location_street, a.location,
        a.house as location_building, a.flat as location_flat
    FROM nodes n
        JOIN macs m ON (n.id = m.nodeid)
        LEFT JOIN vaddresses a ON n.address_id = a.id
    WHERE n.ipaddr <> 0 OR n.ipaddr_pub <> 0;
CREATE VIEW vnetworks AS
    SELECT h.name AS hostname, ne.*, no.ownerid, a.city_id as location_city,
        a.street_id as location_street, a.house as location_house, a.flat as location_flat,
        no.chkmac, inet_ntoa(ne.address) || '/' || mask2prefix(inet_aton(ne.mask)) AS ip, 
        no.id AS nodeid, a.location
    FROM nodes no
        LEFT JOIN networks ne ON (ne.id = no.netid)
        LEFT JOIN hosts h ON (h.id = ne.hostid)
        LEFT JOIN vaddresses a ON no.address_id = a.id
    WHERE no.ipaddr = 0 AND no.ipaddr_pub = 0;
CREATE VIEW vnodes AS
    SELECT n.*, m.mac,
        a.city_id as location_city, a.street_id as location_street,
        a.house as location_house, a.flat as location_flat,
        a.location
    FROM nodes n
        LEFT JOIN (SELECT nodeid, array_to_string(array_agg(mac), ',') AS mac FROM macs GROUP BY nodeid) m ON (n.id = m.nodeid)
        LEFT JOIN vaddresses a ON n.address_id = a.id
    WHERE n.ipaddr <> 0 OR n.ipaddr_pub <> 0;
CREATE VIEW vdivisions AS
    SELECT d.*,
        a.country_id as countryid, a.zip as zip, a.city as city,
        (CASE WHEN a.house IS NULL THEN a.street ELSE (CASE WHEN a.flat IS NULL THEN a.street || ' ' || a.house ELSE a.street || ' ' || a.house || '/' || a.flat END) END) as address
    FROM divisions d
        JOIN addresses a ON a.id = d.address_id;
");

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2017032000', 'dbversion'));

$this->CommitTrans();

?>
