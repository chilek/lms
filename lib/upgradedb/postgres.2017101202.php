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
	DROP VIEW vnodealltariffs;
	DROP VIEW vnodetariffs
");

$this->Execute("
	ALTER TABLE customers ALTER COLUMN divisionid DROP NOT NULL;
	ALTER TABLE customers ALTER COLUMN divisionid SET DEFAULT NULL;
	ALTER TABLE zipcodes ALTER COLUMN stateid DROP NOT NULL;
	ALTER TABLE zipcodes ALTER COLUMN stateid SET DEFAULT NULL;
	ALTER TABLE documents ALTER COLUMN numberplanid DROP NOT NULL;
	ALTER TABLE documents ALTER COLUMN numberplanid SET DEFAULT NULL;
	ALTER TABLE documents ALTER COLUMN countryid DROP NOT NULL;
	ALTER TABLE documents ALTER COLUMN countryid SET DEFAULT NULL;
	ALTER TABLE documents ALTER COLUMN divisionid DROP NOT NULL;
	ALTER TABLE documents ALTER COLUMN divisionid SET DEFAULT NULL;
	ALTER TABLE documents ALTER COLUMN reference DROP NOT NULL;
	ALTER TABLE documents ALTER COLUMN reference SET DEFAULT NULL;
	ALTER TABLE documents ALTER COLUMN div_countryid DROP NOT NULL;
	ALTER TABLE documents ALTER COLUMN div_countryid SET DEFAULT NULL;
	ALTER TABLE cashregs ALTER COLUMN in_numberplanid DROP NOT NULL;
	ALTER TABLE cashregs ALTER COLUMN in_numberplanid SET DEFAULT NULL;
	ALTER TABLE cashregs ALTER COLUMN out_numberplanid DROP NOT NULL;
	ALTER TABLE cashregs ALTER COLUMN out_numberplanid SET DEFAULT NULL;
	ALTER TABLE receiptcontents ALTER COLUMN regid DROP NOT NULL;
	ALTER TABLE receiptcontents ALTER COLUMN regid SET DEFAULT NULL;
	ALTER TABLE tariffs ALTER COLUMN taxid DROP DEFAULT;
	ALTER TABLE liabilities ALTER COLUMN taxid DROP DEFAULT;
	ALTER TABLE assignments ALTER COLUMN tariffid DROP NOT NULL;
	ALTER TABLE assignments ALTER COLUMN tariffid SET DEFAULT NULL;
	ALTER TABLE assignments ALTER COLUMN liabilityid DROP NOT NULL;
	ALTER TABLE assignments ALTER COLUMN liabilityid SET DEFAULT NULL;
	ALTER TABLE invoicecontents ALTER COLUMN taxid DROP DEFAULT;
	ALTER TABLE invoicecontents ALTER COLUMN tariffid DROP NOT NULL;
	ALTER TABLE invoicecontents ALTER COLUMN tariffid SET DEFAULT NULL;
	ALTER TABLE cash ALTER COLUMN taxid DROP NOT NULL;
	ALTER TABLE cash ALTER COLUMN taxid SET DEFAULT NULL;
	ALTER TABLE cash ALTER COLUMN docid DROP NOT NULL;
	ALTER TABLE cash ALTER COLUMN docid SET DEFAULT NULL;
	ALTER TABLE nodegroupassignments ALTER COLUMN nodegroupid DROP DEFAULT;
	ALTER TABLE nodegroupassignments ALTER COLUMN nodeid DROP DEFAULT;
	ALTER TABLE numberplanassignments ALTER COLUMN planid DROP DEFAULT;
	ALTER TABLE numberplanassignments ALTER COLUMN divisionid DROP DEFAULT;
	ALTER TABLE stats ALTER COLUMN nodesessionid DROP NOT NULL;
	ALTER TABLE stats ALTER COLUMN nodesessionid SET DEFAULT NULL;
	ALTER TABLE stats ALTER COLUMN nodeid DROP NOT NULL;
	ALTER TABLE stats ALTER COLUMN nodeid SET DEFAULT NULL;
	ALTER TABLE nodesessions ALTER COLUMN nodeid DROP NOT NULL;
	ALTER TABLE nodesessions ALTER COLUMN nodeid SET DEFAULT NULL;
	ALTER TABLE rttickets ALTER COLUMN owner DROP NOT NULL;
	ALTER TABLE rttickets ALTER COLUMN owner SET DEFAULT NULL;
	ALTER TABLE rtmessages ALTER COLUMN inreplyto DROP NOT NULL;
	ALTER TABLE rtmessages ALTER COLUMN inreplyto SET DEFAULT NULL;
	ALTER TABLE passwd ALTER COLUMN domainid DROP DEFAULT;
	ALTER TABLE aliases ALTER COLUMN domainid DROP DEFAULT;
	ALTER TABLE aliasassignments ALTER COLUMN aliasid DROP DEFAULT;
	ALTER TABLE aliasassignments ALTER COLUMN accountid DROP DEFAULT;
	ALTER TABLE eventassignments ALTER COLUMN eventid DROP DEFAULT;
	ALTER TABLE daemoninstances ALTER COLUMN hostid DROP DEFAULT;
	ALTER TABLE daemonconfig ALTER COLUMN instanceid DROP DEFAULT;
	ALTER TABLE cashrights ALTER COLUMN regid DROP DEFAULT;
	ALTER TABLE cashreglog ALTER COLUMN regid DROP DEFAULT;
	ALTER TABLE ewx_pt_config ALTER COLUMN nodeid DROP NOT NULL;
	ALTER TABLE ewx_pt_config ALTER COLUMN nodeid SET DEFAULT NULL;
	ALTER TABLE messageitems ALTER COLUMN messageid DROP DEFAULT;
	ALTER TABLE up_help ALTER COLUMN reference DROP NOT NULL;
	ALTER TABLE up_help ALTER COLUMN reference SET DEFAULT NULL
");

$ids = $this->GetCol("SELECT id FROM divisions");
if (!empty($ids)) {
	$sql_ids = implode(',', $ids);
	$this->Execute("UPDATE customers SET divisionid = NULL WHERE divisionid = 0 OR divisionid NOT IN (" . $sql_ids . ")");
	$this->Execute("UPDATE documents SET divisionid = NULL WHERE divisionid = 0 OR divisionid NOT IN (" . $sql_ids . ")");
	$this->Execute("DELETE FROM numberplanassignments WHERE divisionid = 0 OR divisionid NOT IN (" . $sql_ids . ")");
}

$ids = $this->GetCol("SELECT id FROM countries");
if (!empty($ids)) {
	$sql_ids = implode(',', $ids);
	$this->Execute("UPDATE documents SET countryid = NULL WHERE countryid = 0 OR countryid NOT IN (" . $sql_ids . ")");
	$this->Execute("UPDATE documents SET div_countryid = NULL WHERE div_countryid = 0 OR div_countryid NOT IN (" . $sql_ids . ")");
}

$ids = $this->GetCol("SELECT id FROM states");
if (!empty($ids)) {
	$sql_ids = implode(',', $ids);
	$this->Execute("UPDATE zipcodes SET stateid = NULL WHERE stateid = 0 OR stateid NOT IN (" . $sql_ids . ")");
}

$ids = $this->GetCol("SELECT id FROM numberplans");
if (!empty($ids)) {
	$sql_ids = implode(',', $ids);
	$this->Execute("UPDATE documents SET numberplanid = NULL WHERE numberplanid = 0 OR numberplanid NOT IN (" . $sql_ids . ")");
	$this->Execute("UPDATE cashregs SET in_numberplanid = NULL WHERE in_numberplanid = 0 OR in_numberplanid NOT IN (" . $sql_ids . ")");
	$this->Execute("UPDATE cashregs SET out_numberplanid = NULL WHERE out_numberplanid = 0 OR out_numberplanid NOT IN (" . $sql_ids . ")");
	$this->Execute("DELETE FROM numberplanassignments WHERE planid = 0 OR planid NOT IN (" . $sql_ids . ")");
}

$ids = $this->GetCol("SELECT id FROM documents");
if (!empty($ids)) {
	$sql_ids = implode(',', $ids);
	$this->Execute("UPDATE documents SET reference = NULL WHERE reference = 0 OR reference NOT IN (" . $sql_ids . ")");
	$this->Execute("UPDATE cash SET docid = NULL WHERE docid = 0 OR docid NOT IN (" . $sql_ids . ")");
}

$ids = $this->GetCol("SELECT id FROM cashregs");
if (!empty($ids)) {
	$sql_ids = implode(',', $ids);
	$this->Execute("UPDATE receiptcontents SET regid = NULL WHERE regid = 0 OR regid NOT IN (" . $sql_ids . ")");
	$this->Execute("DELETE FROM cashrights WHERE regid = 0 OR regid NOT IN (" . $sql_ids . ")");
	$this->Execute("DELETE FROM cashreglog WHERE regid = 0 OR regid NOT IN (" . $sql_ids . ")");
}

$ids = $this->GetCol("SELECT id FROM taxes");
if (!empty($ids)) {
	$sql_ids = implode(',', $ids);
	$this->Execute("DELETE FROM tariffs WHERE taxid = 0 OR taxid NOT IN (" . $sql_ids . ")");
	$this->Execute("DELETE FROM liabilities WHERE taxid = 0 OR taxid NOT IN (" . $sql_ids . ")");
	$this->Execute("DELETE FROM invoicecontents WHERE taxid = 0 OR taxid NOT IN (" . $sql_ids . ")");
	$this->Execute("UPDATE cash SET taxid = NULL WHERE taxid = 0 OR taxid NOT IN (" . $sql_ids . ")");
}

$ids = $this->GetCol("SELECT id FROM tariffs");
if (!empty($ids)) {
	$sql_ids = implode(',', $ids);
	$this->Execute("DELETE FROM assignments WHERE tariffid > 0 AND tariffid NOT IN (" . $sql_ids . ")");
	$this->Execute("UPDATE invoicecontents SET tariffid = NULL WHERE tariffid = 0 OR tariffid NOT IN (" . $sql_ids . ")");
}
$this->Execute("UPDATE assignments SET tariffid = NULL WHERE tariffid = 0");

$ids = $this->GetCol("SELECT id FROM liabilities");
if (!empty($ids)) {
	$sql_ids = implode(',', $ids);
	$this->Execute("DELETE FROM assignments WHERE liabilityid > 0 AND liabilityid NOT IN (" . $sql_ids . ")");
}
$this->Execute("UPDATE assignments SET liabilityid = NULL WHERE liabilityid = 0");

$ids = $this->GetCol("SELECT id FROM cashimport");
if (!empty($ids)) {
	$sql_ids = implode(',', $ids);
	$this->Execute("DELETE FROM cash WHERE importid NOT IN (" . $sql_ids . ")");
}

$ids = $this->GetCol("SELECT id FROM cashsources");
if (!empty($ids)) {
	$sql_ids = implode(',', $ids);
	$this->Execute("UPDATE cash SET sourceid = NULL WHERE sourceid NOT IN (" . $sql_ids . ")");
}

$ids = $this->GetCol("SELECT id FROM nodegroups");
if (!empty($ids)) {
	$sql_ids = implode(',', $ids);
	$this->Execute("DELETE FROM nodegroupassignments WHERE nodegroupid = 0 OR nodegroupid NOT IN (" . $sql_ids . ")");
}

$ids = $this->GetCol("SELECT id FROM nodes");
if (!empty($ids)) {
	$sql_ids = implode(',', $ids);
	$this->Execute("DELETE FROM nodegroupassignments WHERE nodeid = 0 OR nodeid NOT IN (" . $sql_ids . ")");
	$this->Execute("UPDATE stats SET nodeid = NULL WHERE nodeid = 0 OR nodeid NOT IN (" . $sql_ids . ")");
	$this->Execute("UPDATE nodesessions SET nodeid = NULL WHERE nodeid = 0 OR nodeid NOT IN (" . $sql_ids . ")");
	$this->Execute("UPDATE ewx_pt_config SET nodeid = NULL WHERE nodeid = 0 OR nodeid NOT IN (" . $sql_ids . ")");
}

$ids = $this->GetCol("SELECT id FROM nodesessions");
if (!empty($ids)) {
	$sql_ids = implode(',', $ids);
	$this->Execute("UPDATE stats SET nodesessionid = NULL WHERE nodesessionid = 0 OR nodesessionid NOT IN (" . $sql_ids . ")");
}

$ids = $this->GetCol("SELECT id FROM users");
if (!empty($ids)) {
	$sql_ids = implode(',', $ids);
	$this->Execute("UPDATE rttickets SET owner = NULL WHERE owner = 0 OR owner NOT IN (" . $sql_ids . ")");
}

$ids = $this->GetCol("SELECT id FROM rtmessages");
if (!empty($ids)) {
	$sql_ids = implode(',', $ids);
	$this->Execute("UPDATE rtmessages SET inreplyto = NULL WHERE inreplyto = 0 OR inreplyto NOT IN (" . $sql_ids . ")");
}

$ids = $this->GetCol("SELECT id FROM domains");
if (!empty($ids)) {
	$sql_ids = implode(',', $ids);
	$this->Execute("DELETE FROM passwd WHERE domainid = 0 OR domainid NOT IN (" . $sql_ids . ")");
	$this->Execute("DELETE FROM aliases WHERE domainid = 0 OR domainid NOT IN (" . $sql_ids . ")");
}

$ids = $this->GetCol("SELECT id FROM passwd");
if (!empty($ids)) {
	$sql_ids = implode(',', $ids);
	$this->Execute("DELETE FROM aliasassignments WHERE accountid = 0 OR accountid NOT IN (" . $sql_ids . ")");
}

$ids = $this->GetCol("SELECT id FROM aliases");
if (!empty($ids)) {
	$sql_ids = implode(',', $ids);
	$this->Execute("DELETE FROM aliasassignments WHERE aliasid = 0 OR aliasid NOT IN (" . $sql_ids . ")");
}

$ids = $this->GetCol("SELECT id FROM events");
if (!empty($ids)) {
	$sql_ids = implode(',', $ids);
	$this->Execute("DELETE FROM eventassignments WHERE eventid = 0 OR eventid NOT IN (" . $sql_ids . ")");
}

$ids = $this->GetCol("SELECT id FROM hosts");
if (!empty($ids)) {
	$sql_ids = implode(',', $ids);
	$this->Execute("DELETE FROM daemoninstances WHERE hostid = 0 OR hostid NOT IN (" . $sql_ids . ")");
}

$ids = $this->GetCol("SELECT id FROM daemoninstances");
if (!empty($ids)) {
	$sql_ids = implode(',', $ids);
	$this->Execute("DELETE FROM daemonconfig WHERE instanceid = 0 OR instanceid NOT IN (" . $sql_ids . ")");
}

$ids = $this->GetCol("SELECT id FROM messages");
if (!empty($ids)) {
	$sql_ids = implode(',', $ids);
	$this->Execute("DELETE FROM messageitems WHERE messageid = 0 OR messageid NOT IN (" . $sql_ids . ")");
}

$this->Execute("
	ALTER TABLE customers ADD CONSTRAINT customers_divisionid_fkey
		FOREIGN KEY (divisionid) REFERENCES divisions (id) ON DELETE SET NULL ON UPDATE CASCADE;
	ALTER TABLE zipcodes ADD CONSTRAINT zipcodes_stateid_fkey
		FOREIGN KEY (stateid) REFERENCES states (id) ON DELETE SET NULL ON UPDATE CASCADE;
	ALTER TABLE documents ADD CONSTRAINT documents_numberplanid_fkey
		FOREIGN KEY (numberplanid) REFERENCES numberplans (id) ON DELETE SET NULL ON UPDATE CASCADE;
	ALTER TABLE documents ADD CONSTRAINT documents_divisionid_fkey
		FOREIGN KEY (divisionid) REFERENCES divisions (id) ON DELETE SET NULL ON UPDATE CASCADE;
	ALTER TABLE documents ADD CONSTRAINT documents_countryid_fkey
		FOREIGN KEY (countryid) REFERENCES countries (id) ON DELETE SET NULL ON UPDATE CASCADE;
	ALTER TABLE documents ADD CONSTRAINT documents_reference_fkey
		FOREIGN KEY (reference) REFERENCES documents (id) ON DELETE SET NULL ON UPDATE CASCADE;
	ALTER TABLE documents ADD CONSTRAINT documents_div_countryid_fkey
		FOREIGN KEY (div_countryid) REFERENCES countries (id) ON DELETE SET NULL ON UPDATE CASCADE;
	ALTER TABLE cashregs ADD CONSTRAINT cashregs_in_numberplanid_fkey
		FOREIGN KEY (in_numberplanid) REFERENCES numberplans (id) ON DELETE SET NULL ON UPDATE CASCADE;
	ALTER TABLE cashregs ADD CONSTRAINT cashregs_out_numberplanid_fkey
		FOREIGN KEY (out_numberplanid) REFERENCES numberplans (id) ON DELETE SET NULL ON UPDATE CASCADE;
	ALTER TABLE receiptcontents ADD CONSTRAINT receiptcontents_regid_fkey
		FOREIGN KEY (regid) REFERENCES cashregs (id) ON DELETE SET NULL ON UPDATE CASCADE;
	ALTER TABLE tariffs ADD CONSTRAINT tariffs_taxid_fkey
		FOREIGN KEY (taxid) REFERENCES taxes (id) ON DELETE CASCADE ON UPDATE CASCADE;
	ALTER TABLE liabilities ADD CONSTRAINT liabilities_taxid_fkey 
		FOREIGN KEY (taxid) REFERENCES taxes (id) ON DELETE CASCADE ON UPDATE CASCADE;
	ALTER TABLE assignments ADD CONSTRAINT assignments_tariffid_fkey
		FOREIGN KEY (tariffid) REFERENCES tariffs (id) ON DELETE CASCADE ON UPDATE CASCADE;
	ALTER TABLE assignments ADD CONSTRAINT assignments_liabilityid_fkey
		FOREIGN KEY (liabilityid) REFERENCES liabilities (id) ON DELETE CASCADE ON UPDATE CASCADE;
	ALTER TABLE invoicecontents ADD CONSTRAINT invoicecontents_taxid_fkey
		FOREIGN KEY (taxid) REFERENCES taxes (id) ON DELETE CASCADE ON UPDATE CASCADE;
	ALTER TABLE invoicecontents ADD CONSTRAINT invoicecontents_tariffid_fkey
		FOREIGN KEY (tariffid) REFERENCES tariffs (id) ON DELETE SET NULL ON UPDATE CASCADE;
	ALTER TABLE cash ADD CONSTRAINT cash_taxid_fkey
		FOREIGN KEY (taxid) REFERENCES taxes (id) ON DELETE SET NULL ON UPDATE CASCADE;
	ALTER TABLE cash ADD CONSTRAINT cash_docid_fkey
		FOREIGN KEY (docid) REFERENCES documents (id) ON DELETE CASCADE ON UPDATE CASCADE;
	ALTER TABLE cash ADD CONSTRAINT cash_importid_fkey
		FOREIGN KEY (importid) REFERENCES cashimport (id) ON DELETE CASCADE ON UPDATE CASCADE;
	ALTER TABLE cash ADD CONSTRAINT cash_sourceid_fkey
		FOREIGN KEY (sourceid) REFERENCES cashsources (id) ON DELETE SET NULL ON UPDATE CASCADE;
	ALTER TABLE nodegroupassignments ADD CONSTRAINT nodegroupassignments_nodegroupid_fkey
		FOREIGN KEY (nodegroupid) REFERENCES nodegroups (id) ON DELETE CASCADE ON UPDATE CASCADE;
	ALTER TABLE nodegroupassignments ADD CONSTRAINT nodegroupassignments_nodeid_fkey
		FOREIGN KEY (nodeid) REFERENCES nodes (id) ON DELETE CASCADE ON UPDATE CASCADE;
	ALTER TABLE numberplanassignments ADD CONSTRAINT numberplanassignments_planid_fkey
		FOREIGN KEY (planid) REFERENCES numberplans (id) ON DELETE CASCADE ON UPDATE CASCADE;
	ALTER TABLE numberplanassignments ADD CONSTRAINT numberplanassignments_divisionid_fkey
		FOREIGN KEY (divisionid) REFERENCES divisions (id) ON DELETE CASCADE ON UPDATE CASCADE;
	ALTER TABLE stats ADD CONSTRAINT stats_nodesessionid_fkey
		FOREIGN KEY (nodesessionid) REFERENCES nodesessions (id) ON DELETE SET NULL ON UPDATE CASCADE;
	ALTER TABLE stats ADD CONSTRAINT stats_nodeid_fkey
		FOREIGN KEY (nodeid) REFERENCES nodes (id) ON DELETE SET NULL ON UPDATE CASCADE;
	ALTER TABLE nodesessions ADD CONSTRAINT nodesessions_nodeid_fkey
		FOREIGN KEY (nodeid) REFERENCES nodes (id) ON DELETE SET NULL ON UPDATE CASCADE;
	ALTER TABLE rttickets ADD CONSTRAINT rttickets_owner_fkey
		FOREIGN KEY (owner) REFERENCES users (id) ON DELETE SET NULL ON UPDATE CASCADE;
	ALTER TABLE rtmessages ADD CONSTRAINT rtmessages_inreplyto_fkey
		FOREIGN KEY (inreplyto) REFERENCES rtmessages (id) ON DELETE SET NULL ON UPDATE CASCADE;
	ALTER TABLE passwd ADD CONSTRAINT passwd_domainid_fkey
		FOREIGN KEY (domainid) REFERENCES domains (id) ON DELETE CASCADE ON UPDATE CASCADE;
	ALTER TABLE aliases ADD CONSTRAINT aliases_domainid_fkey
		FOREIGN KEY (domainid) REFERENCES domains (id) ON DELETE CASCADE ON UPDATE CASCADE;
	ALTER TABLE aliasassignments ADD CONSTRAINT aliasassignments_aliasid_fkey
		FOREIGN KEY (aliasid) REFERENCES aliases (id) ON DELETE CASCADE ON UPDATE CASCADE;
	ALTER TABLE aliasassignments ADD CONSTRAINT aliasassignments_accountid_fkey
		FOREIGN KEY (accountid) REFERENCES passwd (id) ON DELETE CASCADE ON UPDATE CASCADE;
	ALTER TABLE eventassignments ADD CONSTRAINT eventassignments_eventid_fkey
		FOREIGN KEY (eventid) REFERENCES events (id) ON DELETE CASCADE ON UPDATE CASCADE;
	ALTER TABLE daemoninstances ADD CONSTRAINT daemoninstances_hostid_fkey
		FOREIGN KEY (hostid) REFERENCES hosts (id) ON DELETE CASCADE ON UPDATE CASCADE;
	ALTER TABLE daemonconfig ADD CONSTRAINT daemonconfig_instanceid_fkey
		FOREIGN KEY (instanceid) REFERENCES daemonconfig (id) ON DELETE CASCADE ON UPDATE CASCADE;
	ALTER TABLE cashrights ADD CONSTRAINT cashrights_regid_fkey
		FOREIGN KEY (regid) REFERENCES cashregs (id) ON DELETE CASCADE ON UPDATE CASCADE;
	ALTER TABLE cashreglog ADD CONSTRAINT cashreglog_regid_fkey
		FOREIGN KEY (regid) REFERENCES cashregs (id) ON DELETE CASCADE ON UPDATE CASCADE;
	ALTER TABLE ewx_pt_config ADD CONSTRAINT ewx_pt_config_nodeid_fkey
		FOREIGN KEY (nodeid) REFERENCES nodes (id) ON DELETE SET NULL ON UPDATE CASCADE;
	ALTER TABLE messageitems ADD CONSTRAINT messageitems_messageid_fkey
		FOREIGN KEY (messageid) REFERENCES messages (id) ON DELETE CASCADE ON UPDATE CASCADE;
	ALTER TABLE up_help ADD CONSTRAINT up_help_reference_fkey
		FOREIGN KEY (reference) REFERENCES up_help (id) ON DELETE CASCADE ON UPDATE CASCADE;
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
	LEFT JOIN (SELECT nodeid, array_to_string(array_agg(mac), ',') AS mac FROM macs GROUP BY nodeid) m ON (n.id = m.nodeid)
	LEFT JOIN vaddresses a ON n.address_id = a.id
	JOIN (
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
		LEFT JOIN (
			SELECT customerid, COUNT(id) AS allsuspended FROM assignments
			WHERE tariffid IS NULL AND liabilityid IS NULL
				AND datefrom <= EXTRACT(EPOCH FROM CURRENT_TIMESTAMP(0))::integer
				AND (dateto = 0 OR dateto > EXTRACT(EPOCH FROM CURRENT_TIMESTAMP(0))::integer)
			GROUP BY customerid
		) s ON s.customerid = n.ownerid
		WHERE s.allsuspended IS NULL AND a.suspended = 0
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
	LEFT JOIN (
		SELECT nodeid, array_to_string(array_agg(mac), ',') AS mac
		FROM macs
		GROUP BY nodeid
	) m ON n.id = m.nodeid
	LEFT JOIN vaddresses a ON a.id = n.address_id
	LEFT JOIN (
		SELECT n.id AS nodeid, SUM(t.downrate) AS downrate, SUM(t.downceil) AS downceil,
			SUM(t.uprate) AS uprate, SUM(t.upceil) AS upceil,
			SUM(COALESCE(t.downrate_n, t.downrate)) AS downrate_n,
			SUM(COALESCE(t.downceil_n, t.downceil)) AS downceil_n,
			SUM(COALESCE(t.uprate_n, t.uprate)) AS uprate_n,
			SUM(COALESCE(t.upceil_n, t.upceil)) AS upceil_n
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
		WHERE s.allsuspended IS NULL AND a.suspended = 0
			AND a.datefrom <= EXTRACT(EPOCH FROM CURRENT_TIMESTAMP(0))::integer
			AND (a.dateto = 0 OR a.dateto >= EXTRACT(EPOCH FROM CURRENT_TIMESTAMP(0))::integer)
			AND (t.downrate > 0 OR t.downceil > 0 OR t.uprate > 0 OR t.upceil > 0)
		GROUP BY n.id
	) t1 ON t1.nodeid = n.id
	LEFT JOIN (
		SELECT n.id AS nodeid, SUM(t.downrate) AS downrate, SUM(t.downceil) AS downceil,
			SUM(t.uprate) AS uprate, SUM(t.upceil) AS upceil,
			SUM(CASE WHEN t.downrate_n IS NOT NULL THEN t.downrate_n ELSE t.downrate END) AS downrate_n,
			SUM(CASE WHEN t.downceil_n IS NOT NULL THEN t.downceil_n ELSE t.downceil END) AS downceil_n,
			SUM(CASE WHEN t.uprate_n IS NOT NULL THEN t.uprate_n ELSE t.uprate END) AS uprate_n,
			SUM(CASE WHEN t.upceil_n IS NOT NULL THEN t.upceil_n ELSE t.upceil END) AS upceil_n
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
		WHERE s.allsuspended IS NULL AND a.suspended = 0
			AND a.datefrom <= EXTRACT(EPOCH FROM CURRENT_TIMESTAMP(0))::integer
			AND (a.dateto = 0 OR a.dateto >= EXTRACT(EPOCH FROM CURRENT_TIMESTAMP(0))::integer)
			AND (t.downrate > 0 OR t.downceil > 0 OR t.uprate > 0 OR t.upceil > 0)
			AND n.id NOT IN (SELECT nodeid FROM nodeassignments)
			AND a.id NOT IN (SELECT assignmentid FROM nodeassignments)
		GROUP BY n.id
	) t2 ON t2.nodeid = n.id
	WHERE (n.ipaddr <> 0 OR n.ipaddr_pub <> 0)
		AND ((t1.nodeid IS NOT NULL AND t2.nodeid IS NULL)
			OR (t1.nodeid IS NULL AND t2.nodeid IS NOT NULL)
			OR (t1.nodeid IS NULL AND t2.nodeid IS NULL));
");

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2017101202', 'dbversion'));

$this->CommitTrans();

?>
