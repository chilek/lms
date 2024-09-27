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

$this->Execute("DROP VIEW vmacs");
$this->Execute("DROP VIEW vnodealltariffs");
$this->Execute("DROP VIEW vnodetariffs");
$this->Execute("DROP VIEW vnodes");

$this->Execute("ALTER TABLE nodes MODIFY COLUMN passwd varchar(256) NOT NULL DEFAULT ''");

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

$this->Execute(
    "CREATE VIEW vmacs AS
        SELECT n.*, m.mac, m.id AS macid, a.ccode, a.city_id as location_city, a.street_id as location_street,
            a.house as location_building, a.flat as location_flat, a.location
        FROM nodes n
            JOIN macs m ON (n.id = m.nodeid)
            LEFT JOIN vaddresses a ON n.address_id = a.id
        WHERE n.ipaddr <> 0 OR n.ipaddr_pub <> 0"
);


$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2024092700', 'dbversion'));

$this->CommitTrans();
