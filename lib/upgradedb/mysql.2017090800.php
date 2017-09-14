<?php

/**
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2017 LMS Developers
 *
 *  Please, see the doc/AUTHORS for more information about authors!
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
 *  $Id$
 */

$this->BeginTrans();

$this->Execute("
CREATE VIEW vnodetariffs_allsuspended AS
	SELECT customerid, COUNT(id) AS allsuspended FROM assignments
	WHERE tariffid = 0 AND liabilityid = 0
		AND datefrom <= UNIX_TIMESTAMP()
		AND (dateto = 0 OR dateto > UNIX_TIMESTAMP())
	GROUP BY customerid
");

$this->Execute("
CREATE VIEW vnodetariffs_tariffs AS
	SELECT n.id AS nodeid,
		SUM(t.downrate) AS downrate,
		SUM(t.downceil) AS downceil,
		SUM(t.uprate) AS uprate,
		SUM(t.upceil) AS upceil,
		SUM(COALESCE(t.downrate_n, t.downrate)) AS downrate_n,
		SUM(COALESCE(t.downceil_n, t.downceil)) AS downceil_n,
		SUM(COALESCE(t.uprate_n, t.uprate)) AS uprate_n,
		SUM(COALESCE(t.upceil_n, t.upceil)) AS upceil_n
	FROM nodes n
	JOIN nodeassignments na ON na.nodeid = n.id
	JOIN assignments a ON a.id = na.assignmentid
	JOIN tariffs t ON t.id = a.tariffid
	LEFT JOIN vnodetariffs_allsuspended s ON s.customerid = n.ownerid
	WHERE s.allsuspended IS NULL AND a.suspended = 0
		AND a.datefrom <= UNIX_TIMESTAMP()
		AND (a.dateto = 0 OR a.dateto >= UNIX_TIMESTAMP())
		AND (t.downrate > 0 OR t.downceil > 0 OR t.uprate > 0 OR t.upceil > 0)
	GROUP BY n.id
");

$this->Execute("
CREATE VIEW vnodetariffs AS
	SELECT n.*,
		t.downrate, t.downceil,
		t.uprate, t.upceil,
		t.downrate_n, t.downceil_n,
		t.uprate_n, t.upceil_n,
		m.mac,
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
CREATE VIEW vnodealltariffs_nodes AS
	SELECT vn.id,
		(CASE WHEN nd.id IS NULL THEN vn.ownerid ELSE nd.ownerid END) AS ownerid
	FROM vnodes vn
	LEFT JOIN netdevices nd ON nd.id = vn.netdev AND vn.ownerid = 0 AND nd.ownerid IS NOT NULL
	WHERE (vn.ownerid > 0 AND nd.id IS NULL)
		OR (vn.ownerid = 0 AND nd.id IS NOT NULL)
");

$this->Execute("
CREATE VIEW vnodealltariffs_tariffs AS
	SELECT n.id AS nodeid, SUM(t.downrate) AS downrate, SUM(t.downceil) AS downceil,
		SUM(t.uprate) AS uprate, SUM(t.upceil) AS upceil,
		SUM(COALESCE(t.downrate_n, t.downrate)) AS downrate_n,
		SUM(COALESCE(t.downceil_n, t.downceil)) AS downceil_n,
		SUM(COALESCE(t.uprate_n, t.uprate)) AS uprate_n,
		SUM(COALESCE(t.upceil_n, t.upceil)) AS upceil_n
	FROM assignments a
	JOIN tariffs t ON t.id = a.tariffid
	JOIN vnodealltariffs_nodes n ON n.ownerid = a.customerid
	LEFT JOIN vnodetariffs_allsuspended s ON s.customerid = a.customerid
	WHERE s.allsuspended IS NULL AND a.suspended = 0
		AND a.datefrom <= UNIX_TIMESTAMP()
		AND (a.dateto = 0 OR a.dateto >= UNIX_TIMESTAMP())
		AND (t.downrate > 0 OR t.downceil > 0 OR t.uprate > 0 OR t.upceil > 0)
		AND n.id NOT IN (SELECT nodeid FROM nodeassignments)
		AND a.id NOT IN (SELECT assignmentid FROM nodeassignments)
	GROUP BY n.id
");

$this->Execute("
CREATE VIEW vnodealltariffs AS
	SELECT n.*,
		COALESCE(t1.downrate, t2.downrate, 0) AS downrate,
		COALESCE(t1.downceil, t2.downceil, 0) AS downceil,
		COALESCE(t1.uprate, t2.uprate, 0) AS uprate,
		COALESCE(t1.upceil, t2.upceil, 0) AS upceil,
		COALESCE(t1.downrate_n, t2.downrate_n, 0) AS downrate_n,
		COALESCE(t1.downceil_n, t2.downceil_n, 0) AS downceil_n,
		COALESCE(t1.uprate_n, t2.uprate_n, 0) AS uprate_n,
		COALESCE(t1.upceil_n, t2.upceil_n, 0) AS upceil_n,
		m.mac,
		a.city_id as location_city, a.street_id as location_street,
		a.house as location_house, a.flat as location_flat,
		a.location
	FROM nodes n
	JOIN vnodes_mac m ON m.nodeid = n.id
	LEFT JOIN vaddresses a ON a.id = n.address_id
	JOIN vnodetariffs_tariffs t1 ON t1.nodeid = n.id
	LEFT JOIN vnodealltariffs_tariffs t2 ON t2.nodeid = n.id
	WHERE (n.ipaddr <> 0 OR n.ipaddr_pub <> 0)
		AND ((t1.nodeid IS NOT NULL AND t2.nodeid IS NULL)
			OR (t1.nodeid IS NULL AND t2.nodeid IS NOT NULL)
			OR (t1.nodeid IS NULL AND t2.nodeid IS NULL))
");

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2017090800', 'dbversion'));

$this->CommitTrans();

?>
