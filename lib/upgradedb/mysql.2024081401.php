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

$this->Execute("DROP VIEW IF EXISTS vallusers");
$this->Execute("DROP VIEW IF EXISTS vusers");

if ($this->ResourceExists('users.lastlogindate.int(11)', LMSDB::RESOURCE_TYPE_COLUMN_TYPE)) {
    $this->Execute("ALTER TABLE users MODIFY COLUMN lastlogindate int(16) NOT NULL DEFAULT 0");
}

if ($this->ResourceExists('users.failedlogindate.int(11)', LMSDB::RESOURCE_TYPE_COLUMN_TYPE)) {
    $this->Execute("ALTER TABLE users MODIFY COLUMN failedlogindate int(16) NOT NULL DEFAULT 0");
}

if ($this->ResourceExists('users.passwdexpiration.int(11)', LMSDB::RESOURCE_TYPE_COLUMN_TYPE)) {
    $this->Execute("ALTER TABLE users MODIFY COLUMN passwdexpiration int(16) NOT NULL DEFAULT 0");
}

if ($this->ResourceExists('users.passwdlastchange.int(11)', LMSDB::RESOURCE_TYPE_COLUMN_TYPE)) {
    $this->Execute("ALTER TABLE users MODIFY COLUMN passwdlastchange int(16) NOT NULL DEFAULT 0");
}

if ($this->ResourceExists('users.accessfrom.int(11)', LMSDB::RESOURCE_TYPE_COLUMN_TYPE)) {
    $this->Execute("ALTER TABLE users MODIFY COLUMN accessfrom int(16) NOT NULL DEFAULT 0");
}

if ($this->ResourceExists('users.accessto.int(11)', LMSDB::RESOURCE_TYPE_COLUMN_TYPE)) {
    $this->Execute("ALTER TABLE users MODIFY COLUMN accessto int(16) NOT NULL DEFAULT 0");
}

$this->Execute(
    "CREATE VIEW vusers AS
        SELECT u.*, CONCAT(u.firstname, ' ', u.lastname) AS name, CONCAT(u.lastname, ' ', u.firstname) AS rname
        FROM users u
        LEFT JOIN userdivisions ud ON u.id = ud.userid
        WHERE lms_current_user() = 0 OR ud.divisionid IN (SELECT ud2.divisionid
            FROM userdivisions ud2
            WHERE ud2.userid = lms_current_user())
        GROUP BY u.id"
);

$this->Execute(
    "CREATE VIEW vallusers AS
        SELECT *, CONCAT(firstname, ' ', lastname) AS name, CONCAT(lastname, ' ', firstname) AS rname
        FROM users"
);

if ($this->ResourceExists('twofactorauthcodehistory.uts.int(11)', LMSDB::RESOURCE_TYPE_COLUMN_TYPE)) {
    $this->Execute("ALTER TABLE twofactorauthcodehistory MODIFY COLUMN uts int(16) NOT NULL");
}

if ($this->ResourceExists('twofactorauthtrusteddevices.expires.int(11)', LMSDB::RESOURCE_TYPE_COLUMN_TYPE)) {
    $this->Execute("ALTER TABLE twofactorauthtrusteddevices MODIFY COLUMN expires int(16) NOT NULL");
}

$this->Execute("DROP VIEW IF EXISTS customeraddressview");
$this->Execute("DROP VIEW IF EXISTS contractorview");
$this->Execute("DROP VIEW IF EXISTS customerview");
$this->Execute("DROP VIEW IF EXISTS customerconsentview");
$this->Execute("DROP VIEW IF EXISTS vcustomerassignments");

if ($this->ResourceExists('customerconsents.cdate.int(11)', LMSDB::RESOURCE_TYPE_COLUMN_TYPE)) {
    $this->Execute("ALTER TABLE customerconsents MODIFY COLUMN cdate int(16) NOT NULL DEFAULT 0");
}

if ($this->ResourceExists('customerassignments.startdate.int(11)', LMSDB::RESOURCE_TYPE_COLUMN_TYPE)) {
    $this->Execute("ALTER TABLE customerassignments MODIFY COLUMN startdate int(16) NOT NULL DEFAULT 0");
}

if ($this->ResourceExists('customerassignments.enddate.int(11)', LMSDB::RESOURCE_TYPE_COLUMN_TYPE)) {
    $this->Execute("ALTER TABLE customerassignments MODIFY COLUMN enddate int(16) NOT NULL DEFAULT 0");
}

$this->Execute("DROP FUNCTION IF EXISTS UNIX_TIMESTAMP64");
$this->Execute(
    "CREATE FUNCTION UNIX_TIMESTAMP64() RETURNS BIGINT DETERMINISTIC
    RETURN TIMESTAMPDIFF(SECOND, DATE '1970-01-01', NOW())"
);

$this->Execute(
    "CREATE VIEW vcustomerassignments AS
        SELECT ca.*
        FROM customerassignments ca
        WHERE startdate <= UNIX_TIMESTAMP64() AND enddate = 0"
);

$this->Execute(
    "CREATE VIEW customerconsentview AS
        SELECT c.id AS customerid,
            SUM(CASE WHEN cc.type = 1 THEN cc.cdate ELSE 0 END) AS consentdate,
            SUM(CASE WHEN cc.type = 2 THEN 1 ELSE 0 END) AS invoicenotice,
            SUM(CASE WHEN cc.type = 3 THEN 1 ELSE 0 END) AS mailingnotice,
            SUM(CASE WHEN cc.type = 8 THEN 1 ELSE 0 END) AS smsnotice,
            SUM(CASE WHEN cc.type = 4 THEN 1 ELSE 0 END) AS einvoice
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
        a1.street as street, a1.house as building, a1.flat as apartment,
        a2.country_id as post_countryid, a2.ccode AS post_ccode,
        a2.zip as post_zip, a2.city as post_city,
        a2.street as post_street, a2.house as post_building, a2.flat as post_apartment,
        a2.name as post_name, a1.address as address, a1.location AS full_address,
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

$this->Execute("DROP VIEW IF EXISTS vnodealltariffs");
$this->Execute("DROP VIEW IF EXISTS vnodealltariffs_tariffs");
$this->Execute("DROP VIEW IF EXISTS vnodealltariffs_nodes");
$this->Execute("DROP VIEW IF EXISTS vnodetariffs");
$this->Execute("DROP VIEW IF EXISTS vnodetariffs_tariffs");
$this->Execute("DROP VIEW IF EXISTS vnodetariffs_allsuspended");
$this->Execute("DROP VIEW IF EXISTS vmacs");
$this->Execute("DROP VIEW IF EXISTS vnodes");
$this->Execute("DROP VIEW IF EXISTS vnodes_mac");

if ($this->ResourceExists('assignments.datefrom.int(11)', LMSDB::RESOURCE_TYPE_COLUMN_TYPE)) {
    $this->Execute("ALTER TABLE assignments MODIFY COLUMN datefrom int(16) NOT NULL DEFAULT 0");
}

if ($this->ResourceExists('assignments.dateto.int(11)', LMSDB::RESOURCE_TYPE_COLUMN_TYPE)) {
    $this->Execute("ALTER TABLE assignments MODIFY COLUMN dateto int(16) NOT NULL DEFAULT 0");
}

if ($this->ResourceExists('nodes.creationdate.int(11)', LMSDB::RESOURCE_TYPE_COLUMN_TYPE)) {
    $this->Execute("ALTER TABLE nodes MODIFY COLUMN creationdate int(16) NOT NULL DEFAULT 0");
}

if ($this->ResourceExists('nodes.moddate.int(11)', LMSDB::RESOURCE_TYPE_COLUMN_TYPE)) {
    $this->Execute("ALTER TABLE nodes MODIFY COLUMN moddate int(16) NOT NULL DEFAULT 0");
}

if ($this->ResourceExists('nodes.lastonline.int(11)', LMSDB::RESOURCE_TYPE_COLUMN_TYPE)) {
    $this->Execute("ALTER TABLE nodes MODIFY COLUMN lastonline int(16) NOT NULL DEFAULT 0");
}

$this->Execute(
    "CREATE VIEW vnodes_mac AS
        SELECT nodeid, GROUP_CONCAT(mac SEPARATOR ',') AS mac
        FROM macs GROUP BY nodeid"
);

$this->Execute(
    "CREATE VIEW vnodes AS
        SELECT n.*, m.mac,
            a.ccode, a.city_id as location_city, a.street_id as location_street,
            a.house as location_house, a.flat as location_flat, a.location
        FROM nodes n
        LEFT JOIN vnodes_mac m ON (n.id = m.nodeid)
        LEFT JOIN vaddresses a ON n.address_id = a.id
        WHERE n.ipaddr <> 0 OR n.ipaddr_pub <> 0"
);

$this->Execute(
    "CREATE VIEW vmacs AS
        SELECT n.*, m.mac, m.id AS macid, a.ccode, a.city_id as location_city, a.street_id as location_street,
            a.house as location_building, a.flat as location_flat, a.location
        FROM nodes n
        JOIN macs m ON (n.id = m.nodeid)
        LEFT JOIN vaddresses a ON n.address_id = a.id
        WHERE n.ipaddr <> 0 OR n.ipaddr_pub <> 0"
);

$this->Execute(
    "CREATE VIEW vnodetariffs_allsuspended AS
        SELECT customerid, COUNT(id) AS allsuspended FROM assignments
        WHERE tariffid IS NULL AND liabilityid IS NULL
            AND datefrom <= UNIX_TIMESTAMP64()
            AND (dateto = 0 OR dateto > UNIX_TIMESTAMP64())
        GROUP BY customerid"
);

$this->Execute(
    "CREATE VIEW vnodetariffs_tariffs AS
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
        LEFT JOIN vnodetariffs_allsuspended s ON s.customerid = n.ownerid
        WHERE s.allsuspended IS NULL AND a.suspended = 0 AND a.commited = 1
            AND a.datefrom <= UNIX_TIMESTAMP64()
            AND (a.dateto = 0 OR a.dateto >= UNIX_TIMESTAMP64())
            AND (t.downrate > 0 OR t.downceil > 0 OR t.uprate > 0 OR t.upceil > 0)
        GROUP BY n.id"
);

$this->Execute(
    "CREATE VIEW vnodetariffs AS
        SELECT n.*,
            t.downrate, t.downceil,
            t.down_burst_time, t.down_burst_threshold, t.down_burst_limit,
            t.uprate, t.upceil,
            t.up_burst_time, t.up_burst_threshold, t.up_burst_limit,
            t.downrate_n, t.downceil_n,
            t.down_burst_time_n, t.down_burst_threshold_n, t.down_burst_limit_n,
            t.uprate_n, t.upceil_n,
            t.up_burst_time_n, t.up_burst_threshold_n, t.up_burst_limit_n,
            net.mask, net.gateway, net.dns, net.dns2,
            m.mac,
            a.ccode,
            a.city_id as location_city, a.street_id as location_street,
            a.house as location_house, a.flat as location_flat,
            a.location
        FROM nodes n
            JOIN networks net ON net.id = n.netid
            JOIN vnodes_mac m ON m.nodeid = n.id
            LEFT JOIN vaddresses a ON n.address_id = a.id
            JOIN vnodetariffs_tariffs t ON t.nodeid = n.id
        WHERE n.ipaddr <> 0 OR n.ipaddr_pub <> 0"
);

$this->Execute(
    "CREATE VIEW vnodealltariffs_nodes AS
        SELECT vn.id,
            (CASE WHEN nd.id IS NULL THEN vn.ownerid ELSE nd.ownerid END) AS ownerid
        FROM vnodes vn
        LEFT JOIN netdevices nd ON nd.id = vn.netdev AND vn.ownerid IS NULL AND nd.ownerid IS NOT NULL
        WHERE (vn.ownerid IS NOT NULL AND nd.id IS NULL)
            OR (vn.ownerid IS NULL AND nd.id IS NOT NULL)"
);

$this->Execute(
    "CREATE VIEW vnodealltariffs_tariffs AS
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
        FROM assignments a
        JOIN tariffs t ON t.id = a.tariffid
        JOIN vnodealltariffs_nodes n ON n.ownerid = a.customerid
        LEFT JOIN vnodetariffs_allsuspended s ON s.customerid = a.customerid
        WHERE s.allsuspended IS NULL AND a.suspended = 0 AND a.commited = 1
            AND a.datefrom <= UNIX_TIMESTAMP64()
            AND (a.dateto = 0 OR a.dateto >= UNIX_TIMESTAMP64())
            AND (t.downrate > 0 OR t.downceil > 0 OR t.uprate > 0 OR t.upceil > 0)
            AND n.id NOT IN (SELECT nodeid FROM nodeassignments)
            AND a.id NOT IN (SELECT assignmentid FROM nodeassignments)
        GROUP BY n.id"
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
        JOIN vnodes_mac m ON m.nodeid = n.id
        LEFT JOIN vaddresses a ON a.id = n.address_id
        LEFT JOIN vnodetariffs_tariffs t1 ON t1.nodeid = n.id
        LEFT JOIN vnodealltariffs_tariffs t2 ON t2.nodeid = n.id
        WHERE (n.ipaddr <> 0 OR n.ipaddr_pub <> 0)
            AND ((t1.nodeid IS NOT NULL AND t2.nodeid IS NULL)
                OR (t1.nodeid IS NULL AND t2.nodeid IS NOT NULL)
                OR (t1.nodeid IS NULL AND t2.nodeid IS NULL))"
);

if ($this->ResourceExists('customernotes.dt.int(11)', LMSDB::RESOURCE_TYPE_COLUMN_TYPE)) {
    $this->Execute("ALTER TABLE customernotes MODIFY COLUMN dt int(16) NOT NULL");
}

if ($this->ResourceExists('customernotes.moddate.int(11)', LMSDB::RESOURCE_TYPE_COLUMN_TYPE)) {
    $this->Execute("ALTER TABLE customernotes MODIFY COLUMN moddate int(16) DEFAULT NULL");
}

if ($this->ResourceExists('customerkarmalastchanges.timestamp.int(11)', LMSDB::RESOURCE_TYPE_COLUMN_TYPE)) {
    $this->Execute("ALTER TABLE customerkarmalastchanges MODIFY COLUMN timestamp int(16) NOT NULL");
}

if ($this->ResourceExists('customercalls.dt.int(11)', LMSDB::RESOURCE_TYPE_COLUMN_TYPE)) {
    $this->Execute("ALTER TABLE customercalls MODIFY COLUMN dt int(16) DEFAULT 0 NOT NULL");
}

if ($this->ResourceExists('numberplans.datefrom.int(11)', LMSDB::RESOURCE_TYPE_COLUMN_TYPE)) {
    $this->Execute("ALTER TABLE numberplans MODIFY COLUMN datefrom int(16) NOT NULL DEFAULT 0");
}

if ($this->ResourceExists('numberplans.dateto.int(11)', LMSDB::RESOURCE_TYPE_COLUMN_TYPE)) {
    $this->Execute("ALTER TABLE numberplans MODIFY COLUMN dateto int(16) NOT NULL DEFAULT 0");
}

if ($this->ResourceExists('documentcontents.fromdate.int(11)', LMSDB::RESOURCE_TYPE_COLUMN_TYPE)) {
    $this->Execute("ALTER TABLE documentcontents MODIFY COLUMN fromdate int(16) NOT NULL DEFAULT 0");
}

if ($this->ResourceExists('documentcontents.todate.int(11)', LMSDB::RESOURCE_TYPE_COLUMN_TYPE)) {
    $this->Execute("ALTER TABLE documentcontents MODIFY COLUMN todate int(16) NOT NULL DEFAULT 0");
}

if ($this->ResourceExists('documentattachments.cdate.int(11)', LMSDB::RESOURCE_TYPE_COLUMN_TYPE)) {
    $this->Execute("ALTER TABLE documentattachments MODIFY COLUMN cdate int(16) NOT NULL DEFAULT 0");
}

if ($this->ResourceExists('taxes.validfrom.int(11)', LMSDB::RESOURCE_TYPE_COLUMN_TYPE)) {
    $this->Execute("ALTER TABLE taxes MODIFY COLUMN validfrom int(16) NOT NULL DEFAULT 0");
}

if ($this->ResourceExists('taxes.validto.int(11)', LMSDB::RESOURCE_TYPE_COLUMN_TYPE)) {
    $this->Execute("ALTER TABLE taxes MODIFY COLUMN validto int(16) NOT NULL DEFAULT 0");
}

if ($this->ResourceExists('voipaccounts.creationdate.int(11)', LMSDB::RESOURCE_TYPE_COLUMN_TYPE)) {
    $this->Execute("ALTER TABLE voipaccounts MODIFY COLUMN creationdate int(16) NOT NULL DEFAULT 0");
}

if ($this->ResourceExists('voipaccounts.moddate.int(11)', LMSDB::RESOURCE_TYPE_COLUMN_TYPE)) {
    $this->Execute("ALTER TABLE voipaccounts MODIFY COLUMN moddate int(16) NOT NULL DEFAULT 0");
}

if ($this->ResourceExists('voip_cdr.call_start_time.int(11)', LMSDB::RESOURCE_TYPE_COLUMN_TYPE)) {
    $this->Execute("ALTER TABLE voip_cdr MODIFY COLUMN call_start_time int(16) NOT NULL");
}

if ($this->ResourceExists('tariffs.datefrom.int(11)', LMSDB::RESOURCE_TYPE_COLUMN_TYPE)) {
    $this->Execute("ALTER TABLE tariffs MODIFY COLUMN datefrom int(16) NOT NULL DEFAULT 0");
}

if ($this->ResourceExists('tariffs.dateto.int(11)', LMSDB::RESOURCE_TYPE_COLUMN_TYPE)) {
    $this->Execute("ALTER TABLE tariffs MODIFY COLUMN dateto int(16) NOT NULL DEFAULT 0");
}

if ($this->ResourceExists('promotions.datefrom.int(11)', LMSDB::RESOURCE_TYPE_COLUMN_TYPE)) {
    $this->Execute("ALTER TABLE promotions MODIFY COLUMN datefrom int(16) DEFAULT 0 NOT NULL");
}

if ($this->ResourceExists('promotions.dateto.int(11)', LMSDB::RESOURCE_TYPE_COLUMN_TYPE)) {
    $this->Execute("ALTER TABLE promotions MODIFY COLUMN dateto int(16) DEFAULT 0 NOT NULL");
}

if ($this->ResourceExists('promotionschemas.datefrom.int(11)', LMSDB::RESOURCE_TYPE_COLUMN_TYPE)) {
    $this->Execute("ALTER TABLE promotionschemas MODIFY COLUMN datefrom int(16) DEFAULT 0 NOT NULL");
}

if ($this->ResourceExists('promotionschemas.dateto.int(11)', LMSDB::RESOURCE_TYPE_COLUMN_TYPE)) {
    $this->Execute("ALTER TABLE promotionschemas MODIFY COLUMN dateto int(16) DEFAULT 0 NOT NULL");
}

if ($this->ResourceExists('sourcefiles.idate.int(11)', LMSDB::RESOURCE_TYPE_COLUMN_TYPE)) {
    $this->Execute("ALTER TABLE sourcefiles MODIFY COLUMN idate int(16) NOT NULL");
}

if ($this->ResourceExists('cashimport.date.int(11)', LMSDB::RESOURCE_TYPE_COLUMN_TYPE)) {
    $this->Execute("ALTER TABLE cashimport MODIFY COLUMN date int(16) NOT NULL DEFAULT 0");
}

if ($this->ResourceExists('cashimport.operdate.int(11)', LMSDB::RESOURCE_TYPE_COLUMN_TYPE)) {
    $this->Execute("ALTER TABLE cashimport MODIFY COLUMN operdate int(16) DEFAULT NULL");
}

if ($this->ResourceExists('cash.time.int(11)', LMSDB::RESOURCE_TYPE_COLUMN_TYPE)) {
    $this->Execute("ALTER TABLE cash MODIFY COLUMN time int(16) NOT NULL DEFAULT 0");
}

if ($this->ResourceExists('hosts.lastreload.int(11)', LMSDB::RESOURCE_TYPE_COLUMN_TYPE)) {
    $this->Execute("ALTER TABLE hosts MODIFY COLUMN lastreload int(16) DEFAULT 0 NOT NULL");
}

if ($this->ResourceExists('invprojects.cdate.int(11)', LMSDB::RESOURCE_TYPE_COLUMN_TYPE)) {
    $this->Execute("ALTER TABLE invprojects MODIFY COLUMN cdate int(16) DEFAULT NULL");
}

if ($this->ResourceExists('netnodes.createtime.int(11)', LMSDB::RESOURCE_TYPE_COLUMN_TYPE)) {
    $this->Execute("ALTER TABLE netnodes MODIFY COLUMN createtime int(16)");
}

if ($this->ResourceExists('netnodes.lastinspectiontime.int(11)', LMSDB::RESOURCE_TYPE_COLUMN_TYPE)) {
    $this->Execute("ALTER TABLE netnodes MODIFY COLUMN lastinspectiontime int(16) DEFAULT NULL");
}

if ($this->ResourceExists('netdevices.purchasetime.int(11)', LMSDB::RESOURCE_TYPE_COLUMN_TYPE)) {
    $this->Execute("ALTER TABLE netdevices MODIFY COLUMN purchasetime int(16) NOT NULL DEFAULT 0");
}

if ($this->ResourceExists('nodesessions.start.int(11)', LMSDB::RESOURCE_TYPE_COLUMN_TYPE)) {
    $this->Execute("ALTER TABLE nodesessions MODIFY COLUMN start int(16) NOT NULL DEFAULT 0");
}

if ($this->ResourceExists('nodesessions.stop.int(11)', LMSDB::RESOURCE_TYPE_COLUMN_TYPE)) {
    $this->Execute("ALTER TABLE nodesessions MODIFY COLUMN stop int(16) NOT NULL DEFAULT 0");
}

if ($this->ResourceExists('stats.dt.int(11)', LMSDB::RESOURCE_TYPE_COLUMN_TYPE)) {
    $this->Execute("ALTER TABLE stats MODIFY COLUMN dt int(16) NOT NULL DEFAULT 0");
}

if ($this->ResourceExists('rtqueues.deltime.int(11)', LMSDB::RESOURCE_TYPE_COLUMN_TYPE)) {
    $this->Execute("ALTER TABLE rtqueues MODIFY COLUMN deltime int(16) NOT NULL DEFAULT 0");
}

if ($this->ResourceExists('rttickets.createtime.int(11)', LMSDB::RESOURCE_TYPE_COLUMN_TYPE)) {
    $this->Execute("ALTER TABLE rttickets MODIFY COLUMN createtime int(16) NOT NULL DEFAULT 0");
}

if ($this->ResourceExists('rttickets.resolvetime.int(11)', LMSDB::RESOURCE_TYPE_COLUMN_TYPE)) {
    $this->Execute("ALTER TABLE rttickets MODIFY COLUMN resolvetime int(16) NOT NULL DEFAULT 0");
}

if ($this->ResourceExists('rttickets.modtime.int(11)', LMSDB::RESOURCE_TYPE_COLUMN_TYPE)) {
    $this->Execute("ALTER TABLE rttickets MODIFY COLUMN modtime int(16) NOT NULL DEFAULT 0");
}

if ($this->ResourceExists('rttickets.deltime.int(11)', LMSDB::RESOURCE_TYPE_COLUMN_TYPE)) {
    $this->Execute("ALTER TABLE rttickets MODIFY COLUMN deltime int(16) NOT NULL DEFAULT 0");
}

if ($this->ResourceExists('rttickets.verifier_rtime.int(11)', LMSDB::RESOURCE_TYPE_COLUMN_TYPE)) {
    $this->Execute("ALTER TABLE rttickets MODIFY COLUMN verifier_rtime int(16) DEFAULT NULL");
}

if ($this->ResourceExists('rttickets.deadline.int(11)', LMSDB::RESOURCE_TYPE_COLUMN_TYPE)) {
    $this->Execute("ALTER TABLE rttickets MODIFY COLUMN deadline int(16) DEFAULT NULL");
}

if ($this->ResourceExists('rtticketlastview.vdate.int(11)', LMSDB::RESOURCE_TYPE_COLUMN_TYPE)) {
    $this->Execute("ALTER TABLE rtticketlastview MODIFY COLUMN vdate int(16) NOT NULL");
}

if ($this->ResourceExists('rtmessages.createtime.int(11)', LMSDB::RESOURCE_TYPE_COLUMN_TYPE)) {
    $this->Execute("ALTER TABLE rtmessages MODIFY COLUMN createtime int(16) NOT NULL DEFAULT 0");
}

if ($this->ResourceExists('rtmessages.deltime.int(11)', LMSDB::RESOURCE_TYPE_COLUMN_TYPE)) {
    $this->Execute("ALTER TABLE rtmessages MODIFY COLUMN deltime int(16) NOT NULL DEFAULT 0");
}

if ($this->ResourceExists('passwd.lastlogin.int(11)', LMSDB::RESOURCE_TYPE_COLUMN_TYPE)) {
    $this->Execute("ALTER TABLE passwd MODIFY COLUMN lastlogin int(16) NOT NULL DEFAULT 0");
}

if ($this->ResourceExists('passwd.expdate.int(11)', LMSDB::RESOURCE_TYPE_COLUMN_TYPE)) {
    $this->Execute("ALTER TABLE passwd MODIFY COLUMN expdate int(16) NOT NULL DEFAULT 0");
}

if ($this->ResourceExists('passwd.createtime.int(11)', LMSDB::RESOURCE_TYPE_COLUMN_TYPE)) {
    $this->Execute("ALTER TABLE passwd MODIFY COLUMN createtime int(16) NOT NULL DEFAULT 0");
}

if ($this->ResourceExists('events.date.int(11)', LMSDB::RESOURCE_TYPE_COLUMN_TYPE)) {
    $this->Execute("ALTER TABLE events MODIFY COLUMN date int(16) NOT NULL DEFAULT 0");
}

if ($this->ResourceExists('events.enddate.int(11)', LMSDB::RESOURCE_TYPE_COLUMN_TYPE)) {
    $this->Execute("ALTER TABLE events MODIFY COLUMN enddate int(16) NOT NULL DEFAULT 0");
}

if ($this->ResourceExists('events.closeddate.int(11)', LMSDB::RESOURCE_TYPE_COLUMN_TYPE)) {
    $this->Execute("ALTER TABLE events MODIFY COLUMN closeddate int(16) NOT NULL DEFAULT 0");
}

if ($this->ResourceExists('events.creationdate.int(11)', LMSDB::RESOURCE_TYPE_COLUMN_TYPE)) {
    $this->Execute("ALTER TABLE events MODIFY COLUMN creationdate int(16) NOT NULL DEFAULT 0");
}

if ($this->ResourceExists('events.moddate.int(11)', LMSDB::RESOURCE_TYPE_COLUMN_TYPE)) {
    $this->Execute("ALTER TABLE events MODIFY COLUMN moddate int(16) NOT NULL DEFAULT 0");
}

if ($this->ResourceExists('sessions.ctime.int(11)', LMSDB::RESOURCE_TYPE_COLUMN_TYPE)) {
    $this->Execute("ALTER TABLE sessions MODIFY COLUMN ctime int(16) NOT NULL DEFAULT 0");
}

if ($this->ResourceExists('sessions.mtime.int(11)', LMSDB::RESOURCE_TYPE_COLUMN_TYPE)) {
    $this->Execute("ALTER TABLE sessions MODIFY COLUMN mtime int(16) NOT NULL DEFAULT 0");
}

if ($this->ResourceExists('sessions.atime.int(11)', LMSDB::RESOURCE_TYPE_COLUMN_TYPE)) {
    $this->Execute("ALTER TABLE sessions MODIFY COLUMN atime int(16) NOT NULL DEFAULT 0");
}

if ($this->ResourceExists('cashreglog.time.int(11)', LMSDB::RESOURCE_TYPE_COLUMN_TYPE)) {
    $this->Execute("ALTER TABLE cashreglog MODIFY COLUMN time int(16) DEFAULT 0 NOT NULL");
}

if ($this->ResourceExists('messages.cdate.int(11)', LMSDB::RESOURCE_TYPE_COLUMN_TYPE)) {
    $this->Execute("ALTER TABLE messages MODIFY COLUMN cdate int(16) DEFAULT 0 NOT NULL");
}

if ($this->ResourceExists('messageitems.lastdate.int(11)', LMSDB::RESOURCE_TYPE_COLUMN_TYPE)) {
    $this->Execute("ALTER TABLE messageitems MODIFY COLUMN lastdate int(16) DEFAULT 0 NOT NULL");
}

if ($this->ResourceExists('messageitems.lastreaddate.int(11)', LMSDB::RESOURCE_TYPE_COLUMN_TYPE)) {
    $this->Execute("ALTER TABLE messageitems MODIFY COLUMN lastreaddate int(16) DEFAULT 0 NOT NULL");
}

if ($this->ResourceExists('logtransactions.time.int(11)', LMSDB::RESOURCE_TYPE_COLUMN_TYPE)) {
    $this->Execute("ALTER TABLE logtransactions MODIFY COLUMN time int(16) DEFAULT 0 NOT NULL");
}

if ($this->ResourceExists('filecontainers.creationdate.int(11)', LMSDB::RESOURCE_TYPE_COLUMN_TYPE)) {
    $this->Execute("ALTER TABLE filecontainers MODIFY COLUMN creationdate int(16) NOT NULL DEFAULT 0");
}

if ($this->ResourceExists('up_customers.lastlogindate.int(11)', LMSDB::RESOURCE_TYPE_COLUMN_TYPE)) {
    $this->Execute("ALTER TABLE up_customers MODIFY COLUMN lastlogindate int(16) NOT NULL DEFAULT 0");
}

if ($this->ResourceExists('up_customers.failedlogindate.int(11)', LMSDB::RESOURCE_TYPE_COLUMN_TYPE)) {
    $this->Execute("ALTER TABLE up_customers MODIFY COLUMN failedlogindate int(16) NOT NULL DEFAULT 0");
}

if ($this->ResourceExists('up_sessions.ctime.int(11)', LMSDB::RESOURCE_TYPE_COLUMN_TYPE)) {
    $this->Execute("ALTER TABLE up_sessions MODIFY COLUMN ctime int(16) NOT NULL DEFAULT 0");
}

if ($this->ResourceExists('up_sessions.mtime.int(11)', LMSDB::RESOURCE_TYPE_COLUMN_TYPE)) {
    $this->Execute("ALTER TABLE up_sessions MODIFY COLUMN mtime int(16) NOT NULL DEFAULT 0");
}

if ($this->ResourceExists('up_sessions.atime.int(11)', LMSDB::RESOURCE_TYPE_COLUMN_TYPE)) {
    $this->Execute("ALTER TABLE up_sessions MODIFY COLUMN atime int(16) NOT NULL DEFAULT 0");
}

$this->Execute("DROP TRIGGER IF EXISTS customerassignments_insert_trigger");
$this->Execute(
    "CREATE TRIGGER customerassignments_insert_trigger BEFORE INSERT ON customerassignments
        FOR EACH ROW
    BEGIN
        IF NEW.startdate = 0 THEN
            SET NEW.startdate = UNIX_TIMESTAMP64();
       END IF;
    END"
);

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2024081401', 'dbversion'));

$this->CommitTrans();
