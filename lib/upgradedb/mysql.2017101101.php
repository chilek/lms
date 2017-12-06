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

$sql_tables = array(
	'documents' => array(
		'column' => 'customerid',
		'ondelete' => 'SET NULL',
		'onupdate' => 'CASCADE',
	),
	'cash' => array(
		'column' => 'customerid',
		'ondelete' => 'SET NULL',
		'onupdate' => 'CASCADE',
	),
	'nodesessions' => array(
		'column' => 'customerid',
		'ondelete' => 'SET NULL',
		'onupdate' => 'CASCADE',
	),
	'rttickets' => array(
		'column' => 'customerid',
		'ondelete' => 'SET NULL',
		'onupdate' => 'CASCADE',
	),
	'rtmessages' => array(
		'column' => 'customerid',
		'ondelete' => 'SET NULL',
		'onupdate' => 'CASCADE',
	),
	'events' => array(
		'column' => 'customerid',
		'ondelete' => 'SET NULL',
		'onupdate' => 'CASCADE',
	),
	'messageitems' => array(
		'column' => 'customerid',
		'ondelete' => 'SET NULL',
		'onupdate' => 'CASCADE',
	),
	'up_rights_assignments' => array(
		'column' => 'customerid',
		'ondelete' => 'CASCADE',
		'onupdate' => 'CASCADE',
	),
	'up_customers' => array(
		'column' => 'customerid',
		'ondelete' => 'CASCADE',
		'onupdate' => 'CASCADE',
	),
	'up_info_changes' => array(
		'column' => 'customerid',
		'ondelete' => 'CASCADE',
		'onupdate' => 'CASCADE',
	),
	'nodes' => array(
		'column' => 'ownerid',
		'ondelete' => 'CASCADE',
		'onupdate' => 'CASCADE',
	),
	'voipaccounts' => array(
		'column' => 'ownerid',
		'ondelete' => 'CASCADE',
		'onupdate' => 'CASCADE',
	),
	'passwd' => array(
		'column' => 'ownerid',
		'ondelete' => 'SET NULL',
		'onupdate' => 'CASCADE',
	),
	'domains' => array(
		'column' => 'ownerid',
		'ondelete' => 'SET NULL',
		'onupdate' => 'CASCADE',
	),
);

$cids = $this->GetCol("SELECT id FROM customers");
if (!empty($cids)) {
	$cid_string = implode(',', $cids);
	foreach ($sql_tables as $sql_table => $props) {
		$this->Execute("ALTER TABLE " . $sql_table . " ADD CONSTRAINT " . $sql_table . "_" . $props['column'] . "_fkey
			FOREIGN KEY (" . $props['column'] . ") REFERENCES customers (id) ON DELETE " . $props['ondelete'] . " ON UPDATE " . $props['onupdate']);
	}
}

$this->Execute("
CREATE VIEW vnodealltariffs_nodes AS
	SELECT vn.id,
		(CASE WHEN nd.id IS NULL THEN vn.ownerid ELSE nd.ownerid END) AS ownerid
	FROM vnodes vn
	LEFT JOIN netdevices nd ON nd.id = vn.netdev AND vn.ownerid IS NULL AND nd.ownerid IS NOT NULL
	WHERE (vn.ownerid IS NOT NULL AND nd.id IS NULL)
		OR (vn.ownerid IS NULL AND nd.id IS NOT NULL)
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

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2017101101', 'dbversion'));

$this->CommitTrans();

?>
