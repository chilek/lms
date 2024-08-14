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

$this->Execute("DROP VIEW vallusers");
$this->Execute("DROP VIEW vusers");

if ($this->ResourceExists('users.lastlogindate.int4', LMSDB::RESOURCE_TYPE_COLUMN_TYPE)) {
    $this->Execute("ALTER TABLE users ALTER COLUMN lastlogindate TYPE bigint");
}

if ($this->ResourceExists('users.failedlogindate.int4', LMSDB::RESOURCE_TYPE_COLUMN_TYPE)) {
    $this->Execute("ALTER TABLE users ALTER COLUMN failedlogindate TYPE bigint");
}

if ($this->ResourceExists('users.passwdexpiration.int4', LMSDB::RESOURCE_TYPE_COLUMN_TYPE)) {
    $this->Execute("ALTER TABLE users ALTER COLUMN passwdexpiration TYPE bigint");
}

if ($this->ResourceExists('users.passwdlastchange.int4', LMSDB::RESOURCE_TYPE_COLUMN_TYPE)) {
    $this->Execute("ALTER TABLE users ALTER COLUMN passwdlastchange TYPE bigint");
}

if ($this->ResourceExists('users.accessfrom.int4', LMSDB::RESOURCE_TYPE_COLUMN_TYPE)) {
    $this->Execute("ALTER TABLE users ALTER COLUMN accessfrom TYPE bigint");
}

if ($this->ResourceExists('users.accessto.int4', LMSDB::RESOURCE_TYPE_COLUMN_TYPE)) {
    $this->Execute("ALTER TABLE users ALTER COLUMN accessto TYPE bigint");
}

$this->Execute(
    "CREATE VIEW vusers AS
        SELECT u.*, (u.firstname || ' ' || u.lastname) AS name, (u.lastname || ' ' || u.firstname) AS rname
        FROM users u
        LEFT JOIN userdivisions ud ON u.id = ud.userid
        WHERE lms_current_user() = 0 OR ud.divisionid IN (SELECT ud2.divisionid
            FROM userdivisions ud2
            WHERE ud2.userid = lms_current_user())
        GROUP BY u.id"
);

$this->Execute(
    "CREATE VIEW vallusers AS
        SELECT *, (firstname || ' ' || lastname) AS name, (lastname || ' ' || firstname) AS rname
        FROM users"
);

if ($this->ResourceExists('twofactorauthcodehistory.uts.int4', LMSDB::RESOURCE_TYPE_COLUMN_TYPE)) {
    $this->Execute("ALTER TABLE twofactorauthcodehistory ALTER COLUMN uts TYPE bigint");
}

if ($this->ResourceExists('twofactorauthtrusteddevices.expires.int4', LMSDB::RESOURCE_TYPE_COLUMN_TYPE)) {
    $this->Execute("ALTER TABLE twofactorauthtrusteddevices ALTER COLUMN expires TYPE bigint");
}

$this->Execute("DROP VIEW customeraddressview");
$this->Execute("DROP VIEW contractorview");
$this->Execute("DROP VIEW customerview");
$this->Execute("DROP VIEW customerconsentview");
$this->Execute("DROP VIEW vcustomerassignments");

if ($this->ResourceExists('customerconsents.cdate.int4', LMSDB::RESOURCE_TYPE_COLUMN_TYPE)) {
    $this->Execute("ALTER TABLE customerconsents ALTER COLUMN cdate TYPE bigint");
}

if ($this->ResourceExists('customerassignments.startdate.int4', LMSDB::RESOURCE_TYPE_COLUMN_TYPE)) {
    $this->Execute("ALTER TABLE customerassignments ALTER COLUMN startdate TYPE bigint");
}

if ($this->ResourceExists('customerassignments.enddate.int4', LMSDB::RESOURCE_TYPE_COLUMN_TYPE)) {
    $this->Execute("ALTER TABLE customerassignments ALTER COLUMN enddate TYPE bigint");
}

$this->Execute(
    "CREATE VIEW vcustomerassignments AS
        SELECT ca.*
        FROM customerassignments ca
        WHERE startdate <= EXTRACT(EPOCH FROM CURRENT_TIMESTAMP(0))::bigint AND enddate = 0"
);

$this->Execute(
    "CREATE VIEW customerconsentview AS
        SELECT c.id AS customerid,
            SUM(CASE WHEN cc.type = 1 THEN cc.cdate ELSE 0 END)::bigint AS consentdate,
            SUM(CASE WHEN cc.type = 2 THEN 1 ELSE 0 END)::smallint AS invoicenotice,
            SUM(CASE WHEN cc.type = 3 THEN 1 ELSE 0 END)::smallint AS mailingnotice,
            SUM(CASE WHEN cc.type = 8 THEN 1 ELSE 0 END)::smallint AS smsnotice,
            SUM(CASE WHEN cc.type = 4 THEN 1 ELSE 0 END)::smallint AS einvoice
        FROM customers c
        LEFT JOIN customerconsents cc ON cc.customerid = c.id
        GROUP BY c.id"
);

$this->Execute(
    "CREATE VIEW customerview AS
        SELECT c.*,
            cc.consentdate AS consentdate,
            cc.invoicenotice AS invoicenotice,
            cc.mailingnotice AS mailingnotice,
            cc.smsnotice AS smsnotice,
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
            a2.postoffice AS post_postoffice,
            ce.extid AS extid
        FROM customers c
            JOIN customer_addresses ca1 ON c.id = ca1.customer_id AND ca1.type = 1
            LEFT JOIN vaddresses a1 ON ca1.address_id = a1.id
            LEFT JOIN customer_addresses ca2 ON c.id = ca2.customer_id AND ca2.type = 0
            LEFT JOIN vaddresses a2 ON ca2.address_id = a2.id
            LEFT JOIN customerconsentview cc ON cc.customerid = c.id
            LEFT JOIN customerextids ce ON ce.customerid = c.id AND ce.serviceproviderid IS NULL
        WHERE NOT EXISTS (
            SELECT 1 FROM vcustomerassignments a
            JOIN excludedgroups e ON (a.customergroupid = e.customergroupid)
            WHERE e.userid = lms_current_user() AND a.customerid = c.id)
            AND (lms_current_user() = 0 OR c.divisionid IN (
            SELECT ud.divisionid
                FROM userdivisions ud
                WHERE ud.userid = lms_current_user()))
            AND c.type < 2"
);

$this->Execute(
    "CREATE VIEW contractorview AS
        SELECT c.*,
            cc.consentdate AS consentdate,
            cc.invoicenotice AS invoicenotice,
            cc.mailingnotice AS mailingnotice,
            cc.smsnotice AS smsnotice,
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
            a2.postoffice AS post_postoffice,
            ce.extid AS extid
        FROM customers c
            JOIN customer_addresses ca1 ON c.id = ca1.customer_id AND ca1.type = 1
            LEFT JOIN vaddresses a1 ON ca1.address_id = a1.id
            LEFT JOIN customer_addresses ca2 ON c.id = ca2.customer_id AND ca2.type = 0
            LEFT JOIN vaddresses a2 ON ca2.address_id = a2.id
            LEFT JOIN customerconsentview cc ON cc.customerid = c.id
            LEFT JOIN customerextids ce ON ce.customerid = c.id AND ce.serviceproviderid IS NULL
        WHERE c.type = 2"
);

$this->Execute(
    "CREATE VIEW customeraddressview AS
        SELECT c.*,
            cc.consentdate AS consentdate,
            cc.invoicenotice AS invoicenotice,
            cc.mailingnotice AS mailingnotice,
            cc.smsnotice AS smsnotice,
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
            a2.postoffice AS post_postoffice,
            ce.extid AS extid
        FROM customers c
            JOIN customer_addresses ca1 ON c.id = ca1.customer_id AND ca1.type = 1
            LEFT JOIN vaddresses a1 ON ca1.address_id = a1.id
            LEFT JOIN customer_addresses ca2 ON c.id = ca2.customer_id AND ca2.type = 0
            LEFT JOIN vaddresses a2 ON ca2.address_id = a2.id
            LEFT JOIN customerconsentview cc ON cc.customerid = c.id
            LEFT JOIN customerextids ce ON ce.customerid = c.id AND ce.serviceproviderid IS NULL
        WHERE c.type < 2"
);

$this->Execute("DROP VIEW vnodealltariffs");
$this->Execute("DROP VIEW vnodetariffs");
$this->Execute("DROP VIEW vmacs");
$this->Execute("DROP VIEW vnodes");

if ($this->ResourceExists('assignments.datefrom.int4', LMSDB::RESOURCE_TYPE_COLUMN_TYPE)) {
    $this->Execute("ALTER TABLE assignments ALTER COLUMN datefrom TYPE bigint");
}

if ($this->ResourceExists('assignments.dateto.int4', LMSDB::RESOURCE_TYPE_COLUMN_TYPE)) {
    $this->Execute("ALTER TABLE assignments ALTER COLUMN dateto TYPE bigint");
}

if ($this->ResourceExists('nodes.creationdate.int4', LMSDB::RESOURCE_TYPE_COLUMN_TYPE)) {
    $this->Execute("ALTER TABLE nodes ALTER COLUMN creationdate TYPE bigint");
}

if ($this->ResourceExists('nodes.moddate.int4', LMSDB::RESOURCE_TYPE_COLUMN_TYPE)) {
    $this->Execute("ALTER TABLE nodes ALTER COLUMN moddate TYPE bigint");
}

if ($this->ResourceExists('nodes.lastonline.int4', LMSDB::RESOURCE_TYPE_COLUMN_TYPE)) {
    $this->Execute("ALTER TABLE nodes ALTER COLUMN lastonline TYPE bigint");
}

$this->Execute(
    "CREATE VIEW vnodes AS
        SELECT n.*, m.mac,
            a.ccode,
            a.city_id as location_city, a.street_id as location_street,
            a.house as location_house, a.flat as location_flat,
            a.location
        FROM nodes n
        LEFT JOIN (SELECT nodeid, array_to_string(array_agg(mac), ',') AS mac FROM macs GROUP BY nodeid) m ON (n.id = m.nodeid)
        LEFT JOIN vaddresses a ON n.address_id = a.id
        WHERE n.ipaddr <> 0 OR n.ipaddr_pub <> 0"
);

$this->Execute(
    "CREATE VIEW vmacs AS
        SELECT n.*, m.mac, m.id AS macid,
            a.ccode,
            a.city_id as location_city,
            a.street_id as location_street, a.location,
            a.house as location_building, a.flat as location_flat
        FROM nodes n
        JOIN macs m ON (n.id = m.nodeid)
        LEFT JOIN vaddresses a ON n.address_id = a.id
        WHERE n.ipaddr <> 0 OR n.ipaddr_pub <> 0"
);

$this->Execute(
    "CREATE VIEW vnodetariffs AS
        SELECT n.*,
            t.downrate, t.downceil,
            t.uprate, t.upceil,
            t.downrate_n, t.downceil_n,
            t.uprate_n, t.upceil_n,
            net.mask, net.gateway, net.dns, net.dns2,
            m.mac,
            a.ccode,
            a.city_id as location_city, a.street_id as location_street,
            a.house as location_house, a.flat as location_flat,
            a.location
        FROM nodes n
        JOIN networks net ON net.id = n.netid
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
                    AND datefrom <= EXTRACT(EPOCH FROM CURRENT_TIMESTAMP(0))::bigint
                    AND (dateto = 0 OR dateto > EXTRACT(EPOCH FROM CURRENT_TIMESTAMP(0))::bigint)
                GROUP BY customerid
            ) s ON s.customerid = n.ownerid
            WHERE s.allsuspended IS NULL AND a.suspended = 0 AND a.commited = 1
                AND a.datefrom <= EXTRACT(EPOCH FROM CURRENT_TIMESTAMP(0))::bigint
                AND (a.dateto = 0 OR a.dateto >= EXTRACT(EPOCH FROM CURRENT_TIMESTAMP(0))::bigint)
                AND (t.downrate > 0 OR t.downceil > 0 OR t.uprate > 0 OR t.upceil > 0)
            GROUP BY n.id
        ) t ON t.nodeid = n.id
        WHERE n.ipaddr <> 0 OR n.ipaddr_pub <> 0"
);

$this->Execute(
    "CREATE VIEW vnodealltariffs AS
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
            net.mask, net.gateway, net.dns, net.dns2,
            m.mac,
            a.ccode,
            a.city_id as location_city, a.street_id as location_street,
            a.house as location_house, a.flat as location_flat,
            a.location
        FROM nodes n
        JOIN networks net ON net.id = n.netid
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
                        AND datefrom <= EXTRACT(EPOCH FROM CURRENT_TIMESTAMP(0))::bigint
                        AND (dateto = 0 OR dateto > EXTRACT(EPOCH FROM CURRENT_TIMESTAMP(0))::bigint)
                    GROUP BY customerid
                ) s ON s.customerid = n.ownerid
                WHERE s.allsuspended IS NULL AND a.suspended = 0 AND a.commited = 1
                    AND a.datefrom <= EXTRACT(EPOCH FROM CURRENT_TIMESTAMP(0))::bigint
                    AND (a.dateto = 0 OR a.dateto >= EXTRACT(EPOCH FROM CURRENT_TIMESTAMP(0))::bigint)
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
                        AND datefrom <= EXTRACT(EPOCH FROM CURRENT_TIMESTAMP(0))::bigint
                        AND (dateto = 0 OR dateto > EXTRACT(EPOCH FROM CURRENT_TIMESTAMP(0))::bigint)
                    GROUP BY customerid
                ) s ON s.customerid = a.customerid
                WHERE s.allsuspended IS NULL AND a.suspended = 0 AND a.commited = 1
                    AND a.datefrom <= EXTRACT(EPOCH FROM CURRENT_TIMESTAMP(0))::bigint
                    AND (a.dateto = 0 OR a.dateto >= EXTRACT(EPOCH FROM CURRENT_TIMESTAMP(0))::bigint)
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
            OR (t1.nodeid IS NULL AND t2.nodeid IS NULL))"
);

if ($this->ResourceExists('customernotes.dt.int4', LMSDB::RESOURCE_TYPE_COLUMN_TYPE)) {
    $this->Execute("ALTER TABLE customernotes ALTER COLUMN dt TYPE bigint");
}

if ($this->ResourceExists('customernotes.moddate.int4', LMSDB::RESOURCE_TYPE_COLUMN_TYPE)) {
    $this->Execute("ALTER TABLE customernotes ALTER COLUMN moddate TYPE bigint");
}

if ($this->ResourceExists('customerkarmalastchanges.timestamp.int4', LMSDB::RESOURCE_TYPE_COLUMN_TYPE)) {
    $this->Execute("ALTER TABLE customerkarmalastchanges ALTER COLUMN timestamp TYPE bigint");
}

if ($this->ResourceExists('customercalls.dt.int4', LMSDB::RESOURCE_TYPE_COLUMN_TYPE)) {
    $this->Execute("ALTER TABLE customercalls ALTER COLUMN dt TYPE bigint");
}

if ($this->ResourceExists('numberplans.datefrom.int4', LMSDB::RESOURCE_TYPE_COLUMN_TYPE)) {
    $this->Execute("ALTER TABLE numberplans ALTER COLUMN datefrom TYPE bigint");
}

if ($this->ResourceExists('numberplans.dateto.int4', LMSDB::RESOURCE_TYPE_COLUMN_TYPE)) {
    $this->Execute("ALTER TABLE numberplans ALTER COLUMN dateto TYPE bigint");
}

if ($this->ResourceExists('documentcontents.fromdate.int4', LMSDB::RESOURCE_TYPE_COLUMN_TYPE)) {
    $this->Execute("ALTER TABLE documentcontents ALTER COLUMN fromdate TYPE bigint");
}

if ($this->ResourceExists('documentcontents.todate.int4', LMSDB::RESOURCE_TYPE_COLUMN_TYPE)) {
    $this->Execute("ALTER TABLE documentcontents ALTER COLUMN todate TYPE bigint");
}

if ($this->ResourceExists('documentattachments.cdate.int4', LMSDB::RESOURCE_TYPE_COLUMN_TYPE)) {
    $this->Execute("ALTER TABLE documentattachments ALTER COLUMN cdate TYPE bigint");
}

if ($this->ResourceExists('taxes.validfrom.int4', LMSDB::RESOURCE_TYPE_COLUMN_TYPE)) {
    $this->Execute("ALTER TABLE taxes ALTER COLUMN validfrom TYPE bigint");
}

if ($this->ResourceExists('taxes.validto.int4', LMSDB::RESOURCE_TYPE_COLUMN_TYPE)) {
    $this->Execute("ALTER TABLE taxes ALTER COLUMN validto TYPE bigint");
}

if ($this->ResourceExists('voipaccounts.creationdate.int4', LMSDB::RESOURCE_TYPE_COLUMN_TYPE)) {
    $this->Execute("ALTER TABLE voipaccounts ALTER COLUMN creationdate TYPE bigint");
}

if ($this->ResourceExists('voipaccounts.moddate.int4', LMSDB::RESOURCE_TYPE_COLUMN_TYPE)) {
    $this->Execute("ALTER TABLE voipaccounts ALTER COLUMN moddate TYPE bigint");
}

if ($this->ResourceExists('voip_cdr.call_start_time.int4', LMSDB::RESOURCE_TYPE_COLUMN_TYPE)) {
    $this->Execute("ALTER TABLE voip_cdr ALTER COLUMN call_start_time TYPE bigint");
}

if ($this->ResourceExists('tariffs.datefrom.int4', LMSDB::RESOURCE_TYPE_COLUMN_TYPE)) {
    $this->Execute("ALTER TABLE tariffs ALTER COLUMN datefrom TYPE bigint");
}

if ($this->ResourceExists('tariffs.dateto.int4', LMSDB::RESOURCE_TYPE_COLUMN_TYPE)) {
    $this->Execute("ALTER TABLE tariffs ALTER COLUMN dateto TYPE bigint");
}

if ($this->ResourceExists('promotions.datefrom.int4', LMSDB::RESOURCE_TYPE_COLUMN_TYPE)) {
    $this->Execute("ALTER TABLE promotions ALTER COLUMN datefrom TYPE bigint");
}

if ($this->ResourceExists('promotions.dateto.int4', LMSDB::RESOURCE_TYPE_COLUMN_TYPE)) {
    $this->Execute("ALTER TABLE promotions ALTER COLUMN dateto TYPE bigint");
}

if ($this->ResourceExists('promotionschemas.datefrom.int4', LMSDB::RESOURCE_TYPE_COLUMN_TYPE)) {
    $this->Execute("ALTER TABLE promotionschemas ALTER COLUMN datefrom TYPE bigint");
}

if ($this->ResourceExists('promotionschemas.dateto.int4', LMSDB::RESOURCE_TYPE_COLUMN_TYPE)) {
    $this->Execute("ALTER TABLE promotionschemas ALTER COLUMN dateto TYPE bigint");
}

if ($this->ResourceExists('sourcefiles.idate.int4', LMSDB::RESOURCE_TYPE_COLUMN_TYPE)) {
    $this->Execute("ALTER TABLE sourcefiles ALTER COLUMN idate TYPE bigint");
}

if ($this->ResourceExists('cashimport.date.int4', LMSDB::RESOURCE_TYPE_COLUMN_TYPE)) {
    $this->Execute("ALTER TABLE cashimport ALTER COLUMN date TYPE bigint");
}

if ($this->ResourceExists('cashimport.operdate.int4', LMSDB::RESOURCE_TYPE_COLUMN_TYPE)) {
    $this->Execute("ALTER TABLE cashimport ALTER COLUMN operdate TYPE bigint");
}

if ($this->ResourceExists('cash.time.int4', LMSDB::RESOURCE_TYPE_COLUMN_TYPE)) {
    $this->Execute("ALTER TABLE cash ALTER COLUMN time TYPE bigint");
}

if ($this->ResourceExists('hosts.lastreload.int4', LMSDB::RESOURCE_TYPE_COLUMN_TYPE)) {
    $this->Execute("ALTER TABLE hosts ALTER COLUMN lastreload TYPE bigint");
}

if ($this->ResourceExists('invprojects.cdate.int4', LMSDB::RESOURCE_TYPE_COLUMN_TYPE)) {
    $this->Execute("ALTER TABLE invprojects ALTER COLUMN cdate TYPE bigint");
}

if ($this->ResourceExists('netnodes.createtime.int4', LMSDB::RESOURCE_TYPE_COLUMN_TYPE)) {
    $this->Execute("ALTER TABLE netnodes ALTER COLUMN createtime TYPE bigint");
}

if ($this->ResourceExists('netnodes.lastinspectiontime.int4', LMSDB::RESOURCE_TYPE_COLUMN_TYPE)) {
    $this->Execute("ALTER TABLE netnodes ALTER COLUMN lastinspectiontime TYPE bigint");
}

if ($this->ResourceExists('netdevices.purchasetime.int4', LMSDB::RESOURCE_TYPE_COLUMN_TYPE)) {
    $this->Execute("ALTER TABLE netdevices ALTER COLUMN purchasetime TYPE bigint");
}

if ($this->ResourceExists('nodesessions.start.int4', LMSDB::RESOURCE_TYPE_COLUMN_TYPE)) {
    $this->Execute("ALTER TABLE nodesessions ALTER COLUMN start TYPE bigint");
}

if ($this->ResourceExists('nodesessions.stop.int4', LMSDB::RESOURCE_TYPE_COLUMN_TYPE)) {
    $this->Execute("ALTER TABLE nodesessions ALTER COLUMN stop TYPE bigint");
}

if ($this->ResourceExists('stats.dt.int4', LMSDB::RESOURCE_TYPE_COLUMN_TYPE)) {
    $this->Execute("ALTER TABLE stats ALTER COLUMN dt TYPE bigint");
}

if ($this->ResourceExists('rtqueues.deltime.int4', LMSDB::RESOURCE_TYPE_COLUMN_TYPE)) {
    $this->Execute("ALTER TABLE rtqueues ALTER COLUMN deltime TYPE bigint");
}

if ($this->ResourceExists('rttickets.createtime.int4', LMSDB::RESOURCE_TYPE_COLUMN_TYPE)) {
    $this->Execute("ALTER TABLE rttickets ALTER COLUMN createtime TYPE bigint");
}

if ($this->ResourceExists('rttickets.resolvetime.int4', LMSDB::RESOURCE_TYPE_COLUMN_TYPE)) {
    $this->Execute("ALTER TABLE rttickets ALTER COLUMN resolvetime TYPE bigint");
}

if ($this->ResourceExists('rttickets.modtime.int4', LMSDB::RESOURCE_TYPE_COLUMN_TYPE)) {
    $this->Execute("ALTER TABLE rttickets ALTER COLUMN modtime TYPE bigint");
}

if ($this->ResourceExists('rttickets.deltime.int4', LMSDB::RESOURCE_TYPE_COLUMN_TYPE)) {
    $this->Execute("ALTER TABLE rttickets ALTER COLUMN deltime TYPE bigint");
}

if ($this->ResourceExists('rttickets.verifier_rtime.int4', LMSDB::RESOURCE_TYPE_COLUMN_TYPE)) {
    $this->Execute("ALTER TABLE rttickets ALTER COLUMN verifier_rtime TYPE bigint");
}

if ($this->ResourceExists('rttickets.deadline.int4', LMSDB::RESOURCE_TYPE_COLUMN_TYPE)) {
    $this->Execute("ALTER TABLE rttickets ALTER COLUMN deadline TYPE bigint");
}

if ($this->ResourceExists('rtticketlastview.vdate.int4', LMSDB::RESOURCE_TYPE_COLUMN_TYPE)) {
    $this->Execute("ALTER TABLE rtticketlastview ALTER COLUMN vdate TYPE bigint");
}

if ($this->ResourceExists('rtmessages.createtime.int4', LMSDB::RESOURCE_TYPE_COLUMN_TYPE)) {
    $this->Execute("ALTER TABLE rtmessages ALTER COLUMN createtime TYPE bigint");
}

if ($this->ResourceExists('rtmessages.deltime.int4', LMSDB::RESOURCE_TYPE_COLUMN_TYPE)) {
    $this->Execute("ALTER TABLE rtmessages ALTER COLUMN deltime TYPE bigint");
}

if ($this->ResourceExists('passwd.lastlogin.int4', LMSDB::RESOURCE_TYPE_COLUMN_TYPE)) {
    $this->Execute("ALTER TABLE passwd ALTER COLUMN lastlogin TYPE bigint");
}

if ($this->ResourceExists('passwd.expdate.int4', LMSDB::RESOURCE_TYPE_COLUMN_TYPE)) {
    $this->Execute("ALTER TABLE passwd ALTER COLUMN expdate TYPE bigint");
}

if ($this->ResourceExists('passwd.createtime.int4', LMSDB::RESOURCE_TYPE_COLUMN_TYPE)) {
    $this->Execute("ALTER TABLE passwd ALTER COLUMN createtime TYPE bigint");
}

if ($this->ResourceExists('events.date.int4', LMSDB::RESOURCE_TYPE_COLUMN_TYPE)) {
    $this->Execute("ALTER TABLE events ALTER COLUMN date TYPE bigint");
}

if ($this->ResourceExists('events.enddate.int4', LMSDB::RESOURCE_TYPE_COLUMN_TYPE)) {
    $this->Execute("ALTER TABLE events ALTER COLUMN enddate TYPE bigint");
}

if ($this->ResourceExists('events.closeddate.int4', LMSDB::RESOURCE_TYPE_COLUMN_TYPE)) {
    $this->Execute("ALTER TABLE events ALTER COLUMN closeddate TYPE bigint");
}

if ($this->ResourceExists('events.creationdate.int4', LMSDB::RESOURCE_TYPE_COLUMN_TYPE)) {
    $this->Execute("ALTER TABLE events ALTER COLUMN creationdate TYPE bigint");
}

if ($this->ResourceExists('events.moddate.int4', LMSDB::RESOURCE_TYPE_COLUMN_TYPE)) {
    $this->Execute("ALTER TABLE events ALTER COLUMN moddate TYPE bigint");
}

if ($this->ResourceExists('sessions.ctime.int4', LMSDB::RESOURCE_TYPE_COLUMN_TYPE)) {
    $this->Execute("ALTER TABLE sessions ALTER COLUMN ctime TYPE bigint");
}

if ($this->ResourceExists('sessions.mtime.int4', LMSDB::RESOURCE_TYPE_COLUMN_TYPE)) {
    $this->Execute("ALTER TABLE sessions ALTER COLUMN mtime TYPE bigint");
}

if ($this->ResourceExists('sessions.atime.int4', LMSDB::RESOURCE_TYPE_COLUMN_TYPE)) {
    $this->Execute("ALTER TABLE sessions ALTER COLUMN atime TYPE bigint");
}

if ($this->ResourceExists('cashreglog.time.int4', LMSDB::RESOURCE_TYPE_COLUMN_TYPE)) {
    $this->Execute("ALTER TABLE cashreglog ALTER COLUMN time TYPE bigint");
}

if ($this->ResourceExists('messages.cdate.int4', LMSDB::RESOURCE_TYPE_COLUMN_TYPE)) {
    $this->Execute("ALTER TABLE messages ALTER COLUMN cdate TYPE bigint");
}

if ($this->ResourceExists('messageitems.lastdate.int4', LMSDB::RESOURCE_TYPE_COLUMN_TYPE)) {
    $this->Execute("ALTER TABLE messageitems ALTER COLUMN lastdate TYPE bigint");
}

if ($this->ResourceExists('messageitems.lastreaddate.int4', LMSDB::RESOURCE_TYPE_COLUMN_TYPE)) {
    $this->Execute("ALTER TABLE messageitems ALTER COLUMN lastreaddate TYPE bigint");
}

if ($this->ResourceExists('logtransactions.time.int4', LMSDB::RESOURCE_TYPE_COLUMN_TYPE)) {
    $this->Execute("ALTER TABLE logtransactions ALTER COLUMN time TYPE bigint");
}

if ($this->ResourceExists('filecontainers.creationdate.int4', LMSDB::RESOURCE_TYPE_COLUMN_TYPE)) {
    $this->Execute("ALTER TABLE filecontainers ALTER COLUMN creationdate TYPE bigint");
}

if ($this->ResourceExists('up_customers.lastlogindate.int4', LMSDB::RESOURCE_TYPE_COLUMN_TYPE)) {
    $this->Execute("ALTER TABLE up_customers ALTER COLUMN lastlogindate TYPE bigint");
}

if ($this->ResourceExists('up_customers.failedlogindate.int4', LMSDB::RESOURCE_TYPE_COLUMN_TYPE)) {
    $this->Execute("ALTER TABLE up_customers ALTER COLUMN failedlogindate TYPE bigint");
}

if ($this->ResourceExists('up_sessions.ctime.int4', LMSDB::RESOURCE_TYPE_COLUMN_TYPE)) {
    $this->Execute("ALTER TABLE up_sessions ALTER COLUMN ctime TYPE bigint");
}

if ($this->ResourceExists('up_sessions.mtime.int4', LMSDB::RESOURCE_TYPE_COLUMN_TYPE)) {
    $this->Execute("ALTER TABLE up_sessions ALTER COLUMN mtime TYPE bigint");
}

if ($this->ResourceExists('up_sessions.atime.int4', LMSDB::RESOURCE_TYPE_COLUMN_TYPE)) {
    $this->Execute("ALTER TABLE up_sessions ALTER COLUMN atime TYPE bigint");
}

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2024081401', 'dbversion'));

$this->CommitTrans();
