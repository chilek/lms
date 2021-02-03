<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2020 LMS Developers
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

$this->Execute("DROP VIEW vnodealltariffs");
$this->Execute("DROP VIEW vnodetariffs");
$this->Execute("DROP VIEW customeraddressview");
$this->Execute("DROP VIEW contractorview");
$this->Execute("DROP VIEW customerview");
$this->Execute("DROP VIEW vmacs");
$this->Execute("DROP VIEW vnodes");
$this->Execute("DROP VIEW vnetworks");
$this->Execute("DROP VIEW vdivisions");
$this->Execute("DROP VIEW vaddresses");

$this->Execute("
    CREATE VIEW vaddresses AS
        SELECT a.*, c.ccode AS ccode, country_id AS countryid, city_id AS location_city, street_id AS location_street,
                  house AS location_house, flat AS location_flat,
                  (TRIM(both ' ' FROM
                        CONCAT((CASE WHEN street IS NOT NULL THEN street ELSE city END),
                               (CASE WHEN house is NOT NULL
                                   THEN (CASE WHEN flat IS NOT NULL THEN CONCAT(' ', house, '/', flat) ELSE CONCAT(' ', house) END)
                                ELSE (CASE WHEN flat IS NOT NULL THEN CONCAT(' ', flat) ELSE '' END)
                                END))
                  )) AS address,
                  (TRIM(both ' ' FROM
                        CONCAT((CASE WHEN zip IS NOT NULL THEN CONCAT(zip, ' ') ELSE '' END),
                               (CASE WHEN postoffice IS NOT NULL AND postoffice <> city THEN CONCAT(postoffice, ', ') ELSE '' END),
                               (CASE WHEN postoffice IS NULL OR postoffice = city OR street IS NOT NULL THEN CONCAT(city, ', ') ELSE '' END),
                               (CASE WHEN street IS NOT NULL THEN street ELSE city END),
                               (CASE WHEN house is NOT NULL
                                   THEN (CASE WHEN flat IS NOT NULL THEN CONCAT(' ', house, '/', flat) ELSE CONCAT(' ', house) END)
                                ELSE (CASE WHEN flat IS NOT NULL THEN CONCAT(' ', flat) ELSE '' END)
                                END)
                        )
                  )) AS location
        FROM addresses a
        LEFT JOIN countries c ON c.id = a.country_id
");

$this->Execute("
    CREATE VIEW vdivisions AS
        SELECT d.*,
            a.country_id as countryid, a.ccode, a.zip as zip, a.city as city, a.address
        FROM divisions d
            JOIN vaddresses a ON a.id = d.address_id
");

$this->Execute("
    CREATE VIEW vnetworks AS
        SELECT h.name AS hostname, ne.*, no.ownerid, a.ccode, a.city_id as location_city,
            a.street_id as location_street, a.house as location_house,
            a.flat as location_flat, no.chkmac, CONCAT(inet_ntoa(ne.address), '/',
        mask2prefix(inet_aton(ne.mask))) AS ip, no.id AS nodeid, a.location
        FROM nodes no
            LEFT JOIN networks ne ON (ne.id = no.netid)
            LEFT JOIN hosts h ON (h.id = ne.hostid)
            LEFT JOIN vaddresses a ON no.address_id = a.id
        WHERE no.ipaddr = 0 AND no.ipaddr_pub = 0
");

$this->Execute("
    CREATE VIEW vnodes AS
        SELECT n.*, m.mac,
            a.ccode, a.city_id as location_city, a.street_id as location_street,
            a.house as location_house, a.flat as location_flat, a.location
        FROM nodes n
            LEFT JOIN vnodes_mac m ON (n.id = m.nodeid)
            LEFT JOIN vaddresses a ON n.address_id = a.id
        WHERE n.ipaddr <> 0 OR n.ipaddr_pub <> 0
");

$this->Execute("
    CREATE VIEW vmacs AS
        SELECT n.*, m.mac, m.id AS macid, a.ccode, a.city_id as location_city, a.street_id as location_street,
            a.house as location_building, a.flat as location_flat, a.location
        FROM nodes n
            JOIN macs m ON (n.id = m.nodeid)
            LEFT JOIN vaddresses a ON n.address_id = a.id
        WHERE n.ipaddr <> 0 OR n.ipaddr_pub <> 0
");

$this->Execute("
    CREATE VIEW customerview AS
        SELECT c.*,
            cc.consentdate AS consentdate,
            cc.invoicenotice AS invoicenotice,
            cc.mailingnotice AS mailingnotice,
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
        SELECT 1 FROM customerassignments a
            JOIN excludedgroups e ON (a.customergroupid = e.customergroupid)
            WHERE e.userid = lms_current_user() AND a.customerid = c.id)
            AND c.type < 2
");

$this->Execute("
    CREATE VIEW contractorview AS
        SELECT c.*,
            cc.consentdate AS consentdate,
            cc.invoicenotice AS invoicenotice,
            cc.mailingnotice AS mailingnotice,
            cc.einvoice AS einvoice,
            a1.country_id as countryid, a1.ccode,
            a1.zip as zip, a1.city as city, a1.street as street,
            a1.house as building, a1.flat as apartment,
            a2.country_id as post_countryid, a2.ccode AS post_ccode,
            a2.zip as post_zip, a2.city as post_city, a2.street as post_street,
            a2.house as post_building, a2.flat as post_apartment, a2.name as post_name,
            a1.address as address, a1.location AS full_address,
            a1.postoffice AS postoffice,
            a2.address as post_address, a2.location AS post_full_address,
            a2.postoffice AS post_postoffice
        FROM customers c
            JOIN customer_addresses ca1 ON c.id = ca1.customer_id AND ca1.type = 1
            LEFT JOIN vaddresses a1 ON ca1.address_id = a1.id
            LEFT JOIN customer_addresses ca2 ON c.id = ca2.customer_id AND ca2.type = 0
            LEFT JOIN vaddresses a2 ON ca2.address_id = a2.id
            LEFT JOIN customerconsentview cc ON cc.customerid = c.id
        WHERE c.type = 2
");

$this->Execute("
    CREATE VIEW customeraddressview AS
        SELECT c.*,
            cc.consentdate AS consentdate,
            cc.invoicenotice AS invoicenotice,
            cc.mailingnotice AS mailingnotice,
            cc.einvoice AS einvoice,
            a1.country_id as countryid, a1.ccode,
            a1.zip as zip, a1.city as city, a1.street as street,
            a1.house as building, a1.flat as apartment,
            a2.country_id as post_countryid, a2.ccode AS post_ccode,
            a2.zip as post_zip, a2.city as post_city, a2.street as post_street,
            a2.house as post_building, a2.flat as post_apartment, a2.name as post_name,
            a1.address as address, a1.location AS full_address,
            a1.postoffice AS postoffice,
            a2.address as post_address, a2.location AS post_full_address,
            a2.postoffice AS post_postoffice
        FROM customers c
            JOIN customer_addresses ca1 ON c.id = ca1.customer_id AND ca1.type = 1
            LEFT JOIN vaddresses a1 ON ca1.address_id = a1.id
            LEFT JOIN customer_addresses ca2 ON c.id = ca2.customer_id AND ca2.type = 0
            LEFT JOIN vaddresses a2 ON ca2.address_id = a2.id
            LEFT JOIN customerconsentview cc ON cc.customerid = c.id
        WHERE c.type < 2
");

$this->Execute("
    CREATE VIEW vnodetariffs AS
        SELECT n.*,
            t.downrate, t.downceil,
            t.down_burst_time, t.down_burst_threshold, t.down_burst_limit,
            t.uprate, t.upceil,
            t.up_burst_time, t.up_burst_threshold, t.up_burst_limit,
            t.downrate_n, t.downceil_n,
            t.down_burst_time_n, t.down_burst_threshold_n, t.down_burst_limit_n,
            t.uprate_n, t.upceil_n,
            t.up_burst_time_n, t.up_burst_threshold_n, t.up_burst_limit_n,
            m.mac,
            a.ccode,
            a.city_id as location_city, a.street_id as location_street,
            a.house as location_house, a.flat as location_flat,
            a.location
        FROM nodes n
            JOIN vnodes_mac m ON m.nodeid = n.id
            LEFT JOIN vaddresses a ON n.address_id = a.id
            JOIN vnodetariffs_tariffs t ON t.nodeid = n.id
        WHERE n.ipaddr <> 0 OR n.ipaddr_pub <> 0
");

$this->Execute("
    CREATE VIEW vnodealltariffs AS
        SELECT n.*,
            COALESCE(t1.downrate, t2.downrate, 0) AS downrate,
            COALESCE(t1.downceil, t2.downceil, 0) AS downceil,
            COALESCE(t1.down_burst_time, t2.down_burst_time, 0) AS down_burst_time,
            COALESCE(t1.down_burst_threshold, t2.down_burst_threshold, 0) AS down_burst_threshold,
            COALESCE(t1.down_burst_limit, t2.down_burst_limit, 0) AS down_burst_limit,
            COALESCE(t1.uprate, t2.uprate, 0) AS uprate,
            COALESCE(t1.upceil, t2.upceil, 0) AS upceil,
            COALESCE(t1.up_burst_time, t2.up_burst_time, 0) AS up_burst_time,
            COALESCE(t1.up_burst_threshold, t2.up_burst_threshold, 0) AS up_burst_threshold,
            COALESCE(t1.up_burst_limit, t2.up_burst_limit, 0) AS up_burst_limit,
            COALESCE(t1.downrate_n, t2.downrate_n, 0) AS downrate_n,
            COALESCE(t1.downceil_n, t2.downceil_n, 0) AS downceil_n,
            COALESCE(t1.down_burst_time_n, t2.down_burst_time_n, 0) AS down_burst_time_n,
            COALESCE(t1.down_burst_threshold_n, t2.down_burst_threshold_n, 0) AS down_burst_threshold_n,
            COALESCE(t1.down_burst_limit_n, t2.down_burst_limit_n, 0) AS down_burst_limit_n,
            COALESCE(t1.uprate_n, t2.uprate_n, 0) AS uprate_n,
            COALESCE(t1.upceil_n, t2.upceil_n, 0) AS upceil_n,
            COALESCE(t1.up_burst_time_n, t2.up_burst_time_n, 0) AS up_burst_time_n,
            COALESCE(t1.up_burst_threshold_n, t2.up_burst_threshold_n, 0) AS up_burst_threshold_n,
            COALESCE(t1.up_burst_limit_n, t2.up_burst_limit_n, 0) AS up_burst_limit_n,
            m.mac,
            a.ccode,
            a.city_id as location_city, a.street_id as location_street,
            a.house as location_house, a.flat as location_flat,
            a.location
        FROM nodes n
        JOIN vnodes_mac m ON m.nodeid = n.id
        LEFT JOIN vaddresses a ON a.id = n.address_id
        LEFT JOIN vnodetariffs_tariffs t1 ON t1.nodeid = n.id
        LEFT JOIN vnodealltariffs_tariffs t2 ON t2.nodeid = n.id
        WHERE (n.ipaddr <> 0 OR n.ipaddr_pub <> 0)
        AND ((t1.nodeid IS NOT NULL AND t2.nodeid IS NULL)
                   OR (t1.nodeid IS NULL AND t2.nodeid IS NOT NULL)
                   OR (t1.nodeid IS NULL AND t2.nodeid IS NULL))
");

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2020051300', 'dbversion'));

$this->CommitTrans();
