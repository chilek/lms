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

$this->Execute("
    DROP VIEW vnodealltariffs;
    DROP VIEW vnodetariffs;
    DROP VIEW vmacs;
    DROP VIEW vnodes;
    DROP VIEW customeraddressview;
    DROP VIEW contractorview;
    DROP VIEW customerview;
    DROP VIEW vnetworks;
    DROP VIEW vdivisions;
    DROP VIEW vaddresses
");

$this->Execute("
    CREATE VIEW vaddresses AS
        SELECT a.*, c.ccode AS ccode, country_id AS countryid, city_id AS location_city, street_id AS location_street,
            house AS location_house, flat AS location_flat,
            (TRIM(both ' ' FROM
                 (CASE WHEN street IS NOT NULL THEN street ELSE city END)
                 || (CASE WHEN house is NOT NULL
                         THEN (CASE WHEN flat IS NOT NULL THEN ' ' || house || '/' || flat ELSE ' ' || house END)
                         ELSE (CASE WHEN flat IS NOT NULL THEN ' ' || flat ELSE '' END)
                    END)
            )) AS address,
            (TRIM(both ' ' FROM
                 (CASE WHEN zip IS NOT NULL THEN zip || ' ' ELSE '' END)
                 || (CASE WHEN postoffice IS NOT NULL AND postoffice <> city THEN postoffice || ', ' ELSE '' END)
                     || (CASE WHEN postoffice IS NULL OR postoffice = city OR street IS NOT NULL THEN city || ', ' ELSE '' END)
                     || (CASE WHEN street IS NOT NULL THEN street ELSE city END)
                     || (CASE WHEN house is NOT NULL
                            THEN (CASE WHEN flat IS NOT NULL THEN ' ' || house || '/' || flat ELSE ' ' || house END)
                            ELSE (CASE WHEN flat IS NOT NULL THEN ' ' || flat ELSE '' END)
                        END)
            )) AS location
        FROM addresses a
        LEFT JOIN countries c ON c.id = a.country_id;
    
    CREATE VIEW vdivisions AS
        SELECT d.*,
            a.country_id as countryid, a.ccode, a.zip as zip, a.city as city, a.address
        FROM divisions d
            JOIN vaddresses a ON a.id = d.address_id;
    
    CREATE VIEW vnetworks AS
        SELECT h.name AS hostname, ne.*, no.ownerid, a.ccode, a.city_id as location_city,
            a.street_id as location_street, a.house as location_house, a.flat as location_flat,
            no.chkmac, inet_ntoa(ne.address) || '/' || mask2prefix(inet_aton(ne.mask)) AS ip,
            no.id AS nodeid, a.location
        FROM nodes no
            LEFT JOIN networks ne ON (ne.id = no.netid)
            LEFT JOIN hosts h ON (h.id = ne.hostid)
            LEFT JOIN vaddresses a ON no.address_id = a.id
        WHERE no.ipaddr = 0 AND no.ipaddr_pub = 0;

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
        WHERE c.type = 2;

    CREATE VIEW customerview AS
        SELECT c.*,
            cc.consentdate AS consentdate,
            cc.invoicenotice AS invoicenotice,
            cc.mailingnotice AS mailingnotice,
            cc.einvoice AS einvoice,
            a1.country_id as countryid, a1.ccode,
            a1.zip as zip, a1.city as city,
            a1.street as street,a1.house as building, a1.flat as apartment,
            a2.country_id as post_countryid, a2.ccode AS post_ccode,
            a2.zip as post_zip,
            a2.city as post_city, a2.street as post_street, a2.name as post_name,
            a2.house as post_building, a2.flat as post_apartment,
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
        WHERE NOT EXISTS (
            SELECT 1 FROM customerassignments a
            JOIN excludedgroups e ON (a.customergroupid = e.customergroupid)
            WHERE e.userid = lms_current_user() AND a.customerid = c.id)
            AND c.type < 2;
    
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
        WHERE c.type < 2;

    CREATE VIEW vnodes AS
        SELECT n.*, m.mac,
            a.ccode,
            a.city_id as location_city, a.street_id as location_street,
            a.house as location_house, a.flat as location_flat,
            a.location
        FROM nodes n
            LEFT JOIN (SELECT nodeid, array_to_string(array_agg(mac), ',') AS mac FROM macs GROUP BY nodeid) m ON (n.id = m.nodeid)
            LEFT JOIN vaddresses a ON n.address_id = a.id
        WHERE n.ipaddr <> 0 OR n.ipaddr_pub <> 0;
    
    CREATE VIEW vmacs AS
        SELECT n.*, m.mac, m.id AS macid,
            a.ccode,
            a.city_id as location_city,
            a.street_id as location_street, a.location,
            a.house as location_building, a.flat as location_flat
        FROM nodes n
            JOIN macs m ON (n.id = m.nodeid)
            LEFT JOIN vaddresses a ON n.address_id = a.id
        WHERE n.ipaddr <> 0 OR n.ipaddr_pub <> 0;
    
    CREATE VIEW vnodetariffs AS
        SELECT n.*,
            t.downrate, t.downceil,
            t.uprate, t.upceil,
            t.downrate_n, t.downceil_n,
            t.uprate_n, t.upceil_n,
            m.mac,
            a.ccode,
            a.city_id as location_city, a.street_id as location_street,
            a.house as location_house, a.flat as location_flat,
            a.location
        FROM nodes n
        LEFT JOIN (SELECT nodeid, array_to_string(array_agg(mac), ',') AS mac FROM macs GROUP BY nodeid) m ON (n.id = m.nodeid)
        LEFT JOIN vaddresses a ON n.address_id = a.id
        JOIN (
            SELECT n.id AS nodeid,
                ROUND(SUM(t.downrate * a.count)) AS downrate,
                ROUND(SUM(t.downceil * a.count)) AS downceil,
                SUM(t.down_burst_time) AS down_burst_time,
                SUM(t.down_burst_threshold) AS down_burst_threshold,
                SUM(t.down_burst_limit) AS down_burst_limit,
                ROUND(SUM(t.uprate * a.count)) AS uprate,
                ROUND(SUM(t.upceil * a.count)) AS upceil,
                SUM(t.up_burst_time) AS up_burst_time,
                SUM(t.up_burst_threshold) AS up_burst_threshold,
                SUM(t.up_burst_limit) AS up_burst_limit,
                ROUND(SUM(COALESCE(t.downrate_n, t.downrate) * a.count)) AS downrate_n,
                ROUND(SUM(COALESCE(t.downceil_n, t.downceil) * a.count)) AS downceil_n,
                SUM(COALESCE(t.down_burst_time_n, t.down_burst_time)) AS down_burst_time_n,
                SUM(COALESCE(t.down_burst_threshold_n, t.down_burst_threshold)) AS down_burst_threshold_n,
                SUM(COALESCE(t.down_burst_limit_n, t.down_burst_limit)) AS down_burst_limit_n,
                ROUND(SUM(COALESCE(t.uprate_n, t.uprate) * a.count)) AS uprate_n,
                ROUND(SUM(COALESCE(t.upceil_n, t.upceil) * a.count)) AS upceil_n,
                SUM(COALESCE(t.up_burst_time_n, t.up_burst_time)) AS up_burst_time_n,
                SUM(COALESCE(t.up_burst_threshold_n, t.up_burst_threshold)) AS up_burst_threshold_n,
                SUM(COALESCE(t.up_burst_limit_n, t.up_burst_limit)) AS up_burst_limit_n
            FROM nodes n
            JOIN nodeassignments na ON na.nodeid = n.id
            JOIN assignments a ON a.id = na.assignmentid
            JOIN tariffs t ON t.id = a.tariffid
            LEFT JOIN (
                SELECT customerid, COUNT(id) AS allsuspended FROM assignments
                WHERE tariffid IS NULL AND liabilityid IS NULL
                    AND datefrom <= EXTRACT(EPOCH FROM CURRENT_TIMESTAMP(0))::integer
                    AND (dateto = 0 OR dateto > EXTRACT(EPOCH FROM CURRENT_TIMESTAMP(0))::integer)
                GROUP BY customerid
            ) s ON s.customerid = n.ownerid
            WHERE s.allsuspended IS NULL AND a.suspended = 0 AND a.commited = 1
                AND a.datefrom <= EXTRACT(EPOCH FROM CURRENT_TIMESTAMP(0))::integer
                AND (a.dateto = 0 OR a.dateto >= EXTRACT(EPOCH FROM CURRENT_TIMESTAMP(0))::integer)
                AND (t.downrate > 0 OR t.downceil > 0 OR t.uprate > 0 OR t.upceil > 0)
            GROUP BY n.id
        ) t ON t.nodeid = n.id
        WHERE n.ipaddr <> 0 OR n.ipaddr_pub <> 0;
    
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
        LEFT JOIN (
            SELECT nodeid, array_to_string(array_agg(mac), ',') AS mac
            FROM macs
            GROUP BY nodeid
        ) m ON n.id = m.nodeid
        LEFT JOIN vaddresses a ON a.id = n.address_id
        LEFT JOIN (
            SELECT n.id AS nodeid,
                SUM(a.downrate) AS downrate,
                SUM(a.downceil) AS downceil,
                SUM(a.down_burst_time) AS down_burst_time,
                SUM(a.down_burst_threshold) AS down_burst_threshold,
                SUM(a.down_burst_limit) AS down_burst_limit,
                SUM(a.uprate) AS uprate,
                SUM(a.upceil) AS upceil,
                SUM(a.up_burst_time) AS up_burst_time,
                SUM(a.up_burst_threshold) AS up_burst_threshold,
                SUM(a.up_burst_limit) AS up_burst_limit,
                SUM(a.downrate_n) AS downrate_n,
                SUM(a.downceil_n) AS downceil_n,
                SUM(a.down_burst_time_n) AS down_burst_time_n,
                SUM(a.down_burst_threshold_n) AS down_burst_threshold_n,
                SUM(a.down_burst_limit_n) AS down_burst_limit_n,
                SUM(a.uprate_n) AS uprate_n,
                SUM(a.upceil_n) AS upceil_n,
                SUM(a.up_burst_time_n) AS up_burst_time_n,
                SUM(a.up_burst_threshold_n) AS up_burst_threshold_n,
                SUM(a.up_burst_limit_n) AS up_burst_limit_n
            FROM nodes n
            JOIN (
                SELECT n.id,
                    ROUND(SUM(t.downrate * a.count)) AS downrate,
                    ROUND(SUM(t.downceil * a.count)) AS downceil,
                    SUM(t.down_burst_time) AS down_burst_time,
                    SUM(t.down_burst_threshold) AS down_burst_threshold,
                    SUM(t.down_burst_limit) AS down_burst_limit,
                    ROUND(SUM(t.uprate * a.count)) AS uprate,
                    ROUND(SUM(t.upceil * a.count)) AS upceil,
                    SUM(t.up_burst_time) AS up_burst_time,
                    SUM(t.up_burst_threshold) AS up_burst_threshold,
                    SUM(t.up_burst_limit) AS up_burst_limit,
                    ROUND(SUM(COALESCE(t.downrate_n, t.downrate)) * a.count) AS downrate_n,
                    ROUND(SUM(COALESCE(t.downceil_n, t.downceil)) * a.count) AS downceil_n,
                    SUM(COALESCE(t.down_burst_time_n, t.down_burst_time)) AS down_burst_time_n,
                    SUM(COALESCE(t.down_burst_threshold_n, t.down_burst_threshold)) AS down_burst_threshold_n,
                    SUM(COALESCE(t.down_burst_limit_n, t.down_burst_limit)) AS down_burst_limit_n,
                    ROUND(SUM(COALESCE(t.uprate_n, t.uprate)) * a.count) AS uprate_n,
                    ROUND(SUM(COALESCE(t.upceil_n, t.upceil)) * a.count) AS upceil_n,
                    SUM(COALESCE(t.up_burst_time_n, t.up_burst_time)) AS up_burst_time_n,
                    SUM(COALESCE(t.up_burst_threshold_n, t.up_burst_threshold)) AS up_burst_threshold_n,
                    SUM(COALESCE(t.up_burst_limit_n, t.up_burst_limit)) AS up_burst_limit_n
                FROM assignments a
                JOIN nodeassignments na ON na.assignmentid = a.id
                JOIN nodes n ON n.id = na.nodeid
                JOIN tariffs t ON t.id = a.tariffid
                LEFT JOIN (
                    SELECT customerid, COUNT(id) AS allsuspended FROM assignments
                    WHERE tariffid IS NULL AND liabilityid IS NULL
                        AND datefrom <= EXTRACT(EPOCH FROM CURRENT_TIMESTAMP(0))::integer
                        AND (dateto = 0 OR dateto > EXTRACT(EPOCH FROM CURRENT_TIMESTAMP(0))::integer)
                    GROUP BY customerid
                ) s ON s.customerid = n.ownerid
                WHERE s.allsuspended IS NULL AND a.suspended = 0 AND a.commited = 1
                    AND a.datefrom <= EXTRACT(EPOCH FROM CURRENT_TIMESTAMP(0))::integer
                    AND (a.dateto = 0 OR a.dateto >= EXTRACT(EPOCH FROM CURRENT_TIMESTAMP(0))::integer)
                    AND (t.downrate > 0 OR t.downceil > 0 OR t.uprate > 0 OR t.upceil > 0)
                GROUP BY n.id, a.count
            ) a ON a.id = n.id
            GROUP BY n.id
        ) t1 ON t1.nodeid = n.id
        LEFT JOIN (
            SELECT n.id AS nodeid,
                SUM(a.downrate) AS downrate,
                SUM(a.downceil) AS downceil,
                SUM(a.down_burst_time) AS down_burst_time,
                SUM(a.down_burst_threshold) AS down_burst_threshold,
                SUM(a.down_burst_limit) AS down_burst_limit,
                SUM(a.uprate) AS uprate,
                SUM(a.upceil) AS upceil,
                SUM(a.up_burst_time) AS up_burst_time,
                SUM(a.up_burst_threshold) AS up_burst_threshold,
                SUM(a.up_burst_limit) AS up_burst_limit,
                SUM(a.downrate_n) AS downrate_n,
                SUM(a.downceil_n) AS downceil_n,
                SUM(a.down_burst_time_n) AS down_burst_time_n,
                SUM(a.down_burst_threshold_n) AS down_burst_threshold_n,
                SUM(a.down_burst_limit_n) AS down_burst_limit_n,
                SUM(a.uprate_n) AS uprate_n,
                SUM(a.upceil_n) AS upceil_n,
                SUM(a.up_burst_time_n) AS up_burst_time_n,
                SUM(a.up_burst_threshold_n) AS up_burst_threshold_n,
                SUM(a.up_burst_limit_n) AS up_burst_limit_n
            FROM nodes n
            JOIN (
                SELECT n.id AS nodeid,
                    ROUND(SUM(t.downrate * a.count)) AS downrate,
                    ROUND(SUM(t.downceil * a.count)) AS downceil,
                    SUM(t.down_burst_time) AS down_burst_time,
                    SUM(t.down_burst_threshold) AS down_burst_threshold,
                    SUM(t.down_burst_limit) AS down_burst_limit,
                    ROUND(SUM(t.uprate * a.count)) AS uprate,
                    ROUND(SUM(t.upceil * a.count)) AS upceil,
                    SUM(t.up_burst_time) AS up_burst_time,
                    SUM(t.up_burst_threshold) AS up_burst_threshold,
                    SUM(t.up_burst_limit) AS up_burst_limit,
                    ROUND(SUM((CASE WHEN t.downrate_n IS NOT NULL THEN t.downrate_n ELSE t.downrate END) * a.count)) AS downrate_n,
                    ROUND(SUM((CASE WHEN t.downceil_n IS NOT NULL THEN t.downceil_n ELSE t.downceil END) * a.count)) AS downceil_n,
                    SUM(CASE WHEN t.down_burst_time_n IS NOT NULL THEN t.down_burst_time_n ELSE t.down_burst_time END) AS down_burst_time_n,
                    SUM(CASE WHEN t.down_burst_threshold_n IS NOT NULL THEN t.down_burst_threshold_n ELSE t.down_burst_threshold END) AS down_burst_threshold_n,
                    SUM(CASE WHEN t.down_burst_limit_n IS NOT NULL THEN t.down_burst_limit_n ELSE t.down_burst_limit END) AS down_burst_limit_n,
                    ROUND(SUM((CASE WHEN t.uprate_n IS NOT NULL THEN t.uprate_n ELSE t.uprate END) * a.count)) AS uprate_n,
                    ROUND(SUM((CASE WHEN t.upceil_n IS NOT NULL THEN t.upceil_n ELSE t.upceil END) * a.count)) AS upceil_n,
                    SUM(CASE WHEN t.up_burst_time_n IS NOT NULL THEN t.up_burst_time_n ELSE t.up_burst_time END) AS up_burst_time_n,
                    SUM(CASE WHEN t.up_burst_threshold_n IS NOT NULL THEN t.up_burst_threshold_n ELSE t.up_burst_threshold END) AS up_burst_threshold_n,
                    SUM(CASE WHEN t.up_burst_limit_n IS NOT NULL THEN t.up_burst_limit_n ELSE t.up_burst_limit END) AS up_burst_limit_n
                FROM assignments a
                JOIN tariffs t ON t.id = a.tariffid
                JOIN (
                    SELECT vn.id,
                           (CASE WHEN nd.id IS NULL THEN vn.ownerid ELSE nd.ownerid END) AS ownerid
                    FROM vnodes vn
                    LEFT JOIN netdevices nd ON nd.id = vn.netdev AND vn.ownerid IS NULL AND nd.ownerid IS NOT NULL
                    WHERE (vn.ownerid IS NOT NULL AND nd.id IS NULL)
                        OR (vn.ownerid IS NULL AND nd.id IS NOT NULL)
                ) n ON n.ownerid = a.customerid
                LEFT JOIN (
                    SELECT customerid, COUNT(id) AS allsuspended FROM assignments
                    WHERE tariffid IS NULL AND liabilityid IS NULL
                        AND datefrom <= EXTRACT(EPOCH FROM CURRENT_TIMESTAMP(0))::integer
                        AND (dateto = 0 OR dateto > EXTRACT(EPOCH FROM CURRENT_TIMESTAMP(0))::integer)
                    GROUP BY customerid
                ) s ON s.customerid = a.customerid
                WHERE s.allsuspended IS NULL AND a.suspended = 0 AND a.commited = 1
                    AND a.datefrom <= EXTRACT(EPOCH FROM CURRENT_TIMESTAMP(0))::integer
                    AND (a.dateto = 0 OR a.dateto >= EXTRACT(EPOCH FROM CURRENT_TIMESTAMP(0))::integer)
                    AND (t.downrate > 0 OR t.downceil > 0 OR t.uprate > 0 OR t.upceil > 0)
                    AND n.id NOT IN (SELECT nodeid FROM nodeassignments)
                    AND a.id NOT IN (SELECT assignmentid FROM nodeassignments)
                GROUP BY n.id, a.count
            ) a ON a.nodeid = n.id
            GROUP BY n.id
        ) t2 ON t2.nodeid = n.id
        WHERE (n.ipaddr <> 0 OR n.ipaddr_pub <> 0)
            AND ((t1.nodeid IS NOT NULL AND t2.nodeid IS NULL)
                OR (t1.nodeid IS NULL AND t2.nodeid IS NOT NULL)
                OR (t1.nodeid IS NULL AND t2.nodeid IS NULL))
");

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2020051300', 'dbversion'));

$this->CommitTrans();
