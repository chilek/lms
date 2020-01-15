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

$this->Execute("DROP VIEW vnodealltariffs_tariffs");
$this->Execute("DROP VIEW vnodetariffs_tariffs");
$this->Execute("DROP VIEW vnodetariffs_allsuspended");

$this->Execute("ALTER TABLE customers MODIFY divisionid int(11) NULL");
$this->Execute("ALTER TABLE customers ALTER COLUMN divisionid SET DEFAULT NULL");
$this->Execute("ALTER TABLE zipcodes MODIFY stateid int(11) NULL");
$this->Execute("ALTER TABLE zipcodes ALTER COLUMN stateid SET DEFAULT NULL");
$this->Execute("ALTER TABLE documents MODIFY numberplanid int(11) NULL");
$this->Execute("ALTER TABLE documents ALTER COLUMN numberplanid SET DEFAULT NULL");
$this->Execute("ALTER TABLE documents MODIFY countryid int(11) NULL");
$this->Execute("ALTER TABLE documents ALTER COLUMN countryid SET DEFAULT NULL");
$this->Execute("ALTER TABLE documents MODIFY divisionid int(11) NULL");
$this->Execute("ALTER TABLE documents ALTER COLUMN divisionid SET DEFAULT NULL");
$this->Execute("ALTER TABLE documents MODIFY reference int(11) NULL");
$this->Execute("ALTER TABLE documents ALTER COLUMN reference SET DEFAULT NULL");

$this->Execute("ALTER TABLE documents ADD COLUMN used_reference integer DEFAULT 0 NOT NULL");
$this->Execute("CREATE INDEX documents_used_reference_idx ON documents (used_reference)";

$this->Execute("ALTER TABLE documents MODIFY div_countryid int(11) NULL");
$this->Execute("ALTER TABLE documents ALTER COLUMN div_countryid SET DEFAULT NULL");
$this->Execute("ALTER TABLE cashregs MODIFY in_numberplanid int(11) NULL");
$this->Execute("ALTER TABLE cashregs ALTER COLUMN in_numberplanid SET DEFAULT NULL");
$this->Execute("ALTER TABLE cashregs MODIFY out_numberplanid int(11) NULL");
$this->Execute("ALTER TABLE cashregs ALTER COLUMN out_numberplanid SET DEFAULT NULL");
$this->Execute("ALTER TABLE receiptcontents MODIFY regid int(11) NULL");
$this->Execute("ALTER TABLE receiptcontents ALTER COLUMN regid SET DEFAULT NULL");
$this->Execute("ALTER TABLE tariffs ALTER COLUMN taxid DROP DEFAULT");
$this->Execute("ALTER TABLE liabilities ALTER COLUMN taxid DROP DEFAULT");
$this->Execute("ALTER TABLE assignments MODIFY tariffid int(11) NULL");
$this->Execute("ALTER TABLE assignments ALTER COLUMN tariffid SET DEFAULT NULL");
$this->Execute("ALTER TABLE assignments MODIFY liabilityid int(11) NULL");
$this->Execute("ALTER TABLE assignments ALTER COLUMN liabilityid SET DEFAULT NULL");
$this->Execute("ALTER TABLE invoicecontents ALTER COLUMN taxid DROP DEFAULT");
$this->Execute("ALTER TABLE invoicecontents MODIFY tariffid int(11) NULL");
$this->Execute("ALTER TABLE invoicecontents ALTER COLUMN tariffid SET DEFAULT NULL");
$this->Execute("ALTER TABLE cash MODIFY taxid int(11) NULL");
$this->Execute("ALTER TABLE cash ALTER COLUMN taxid SET DEFAULT NULL");
$this->Execute("ALTER TABLE cash MODIFY docid int(11) NULL");
$this->Execute("ALTER TABLE cash ALTER COLUMN docid SET DEFAULT NULL");

$this->Execute("ALTER TABLE cash ADD COLUMN used_docid integer DEFAULT 0 NOT NULL");
$this->Execute("CREATE INDEX cash_used_docid_idx ON cash (used_docid)");

$this->Execute("ALTER TABLE nodegroupassignments ALTER COLUMN nodegroupid DROP DEFAULT");
$this->Execute("ALTER TABLE nodegroupassignments ALTER COLUMN nodeid DROP DEFAULT");
$this->Execute("ALTER TABLE numberplanassignments ALTER COLUMN planid DROP DEFAULT");
$this->Execute("ALTER TABLE numberplanassignments ALTER COLUMN divisionid DROP DEFAULT");
$this->Execute("ALTER TABLE stats MODIFY nodesessionid int(11) NULL");
$this->Execute("ALTER TABLE stats ALTER COLUMN nodesessionid SET DEFAULT NULL");
$this->Execute("ALTER TABLE stats MODIFY nodeid int(11) NULL");
$this->Execute("ALTER TABLE stats ALTER COLUMN nodeid SET DEFAULT NULL");
$this->Execute("ALTER TABLE nodesessions MODIFY nodeid int(11) NULL");
$this->Execute("ALTER TABLE nodesessions ALTER COLUMN nodeid SET DEFAULT NULL");
$this->Execute("ALTER TABLE rttickets MODIFY owner int(11) NULL");
$this->Execute("ALTER TABLE rttickets ALTER COLUMN owner SET DEFAULT NULL");
$this->Execute("ALTER TABLE rtmessages MODIFY inreplyto int(11) NULL");
$this->Execute("ALTER TABLE rtmessages ALTER COLUMN inreplyto SET DEFAULT NULL");
$this->Execute("ALTER TABLE passwd ALTER COLUMN domainid DROP DEFAULT");
$this->Execute("ALTER TABLE aliases ALTER COLUMN domainid DROP DEFAULT");
$this->Execute("ALTER TABLE aliasassignments ALTER COLUMN aliasid DROP DEFAULT");
$this->Execute("ALTER TABLE aliasassignments MODIFY accountid int(11) NULL");
$this->Execute("ALTER TABLE aliasassignments ALTER COLUMN accountid SET DEFAULT NULL");
$this->Execute("ALTER TABLE eventassignments ALTER COLUMN eventid DROP DEFAULT");
$this->Execute("ALTER TABLE daemoninstances ALTER COLUMN hostid DROP DEFAULT");
$this->Execute("ALTER TABLE daemonconfig ALTER COLUMN instanceid DROP DEFAULT");
$this->Execute("ALTER TABLE cashrights ALTER COLUMN regid DROP DEFAULT");
$this->Execute("ALTER TABLE cashreglog ALTER COLUMN regid DROP DEFAULT");
$this->Execute("ALTER TABLE ewx_pt_config MODIFY nodeid int(11) NULL");
$this->Execute("ALTER TABLE ewx_pt_config ALTER COLUMN nodeid SET DEFAULT NULL");
$this->Execute("ALTER TABLE messageitems ALTER COLUMN messageid DROP DEFAULT");
$this->Execute("ALTER TABLE up_help MODIFY reference int(11) NULL");
$this->Execute("ALTER TABLE up_help ALTER COLUMN reference SET DEFAULT NULL");

$this->Execute("UPDATE customers SET divisionid = NULL WHERE divisionid = 0");
$this->Execute("UPDATE documents SET divisionid = NULL WHERE divisionid = 0");
$this->Execute("DELETE FROM numberplanassignments WHERE divisionid = 0");
$ids = $this->GetCol("SELECT id FROM divisions");
if (empty($ids)) {
    $this->Execute("UPDATE customers SET divisionid = NULL WHERE divisionid IS NOT NULL");
    $this->Execute("UPDATE documents SET divisionid = NULL WHERE divisionid IS NOT NULL");
    $this->Execute("DELETE FROM numberplanassignments");
} else {
    $sql_ids = implode(',', $ids);
    $this->Execute("UPDATE customers SET divisionid = NULL
		WHERE divisionid IS NOT NULL AND divisionid NOT IN (" . $sql_ids . ")");
    $this->Execute("UPDATE documents SET divisionid = NULL
		WHERE divisionid IS NOT NULL AND divisionid NOT IN (" . $sql_ids . ")");
    $this->Execute("DELETE FROM numberplanassignments WHERE divisionid NOT IN (" . $sql_ids . ")");
}

$this->Execute("UPDATE documents SET countryid = NULL WHERE countryid = 0");
$this->Execute("UPDATE documents SET div_countryid = NULL WHERE div_countryid = 0");
$ids = $this->GetCol("SELECT id FROM countries");
if (empty($ids)) {
    $this->Execute("UPDATE documents SET countryid = NULL WHERE countryid IS NOT NULL");
    $this->Execute("UPDATE documents SET div_countryid = NULL WHERE div_countryid IS NOT NULL");
} else {
    $sql_ids = implode(',', $ids);
    $this->Execute("UPDATE documents SET countryid = NULL
		WHERE countryid IS NOT NULL AND countryid NOT IN (" . $sql_ids . ")");
    $this->Execute("UPDATE documents SET div_countryid = NULL
		WHERE div_countryid IS NOT NULL AND div_countryid NOT IN (" . $sql_ids . ")");
}

$this->Execute("UPDATE zipcodes SET stateid = NULL WHERE stateid = 0");
$ids = $this->GetCol("SELECT id FROM states");
if (empty($ids)) {
    $this->Execute("UPDATE zipcodes SET stateid = NULL WHERE stateid IS NOT NULL");
} else {
    $sql_ids = implode(',', $ids);
    $this->Execute("UPDATE zipcodes SET stateid = NULL
		WHERE stateid IS NOT NULL AND stateid NOT IN (" . $sql_ids . ")");
}

$this->Execute("UPDATE documents SET numberplanid = NULL WHERE numberplanid = 0");
$this->Execute("UPDATE cashregs SET in_numberplanid = NULL WHERE in_numberplanid = 0");
$this->Execute("UPDATE cashregs SET out_numberplanid = NULL WHERE out_numberplanid = 0");
$this->Execute("DELETE FROM numberplanassignments WHERE planid = 0");
$ids = $this->GetCol("SELECT id FROM numberplans");
if (empty($ids)) {
    $this->Execute("UPDATE documents SET numberplanid = NULL WHERE numberplanid IS NOT NULL");
    $this->Execute("UPDATE cashregs SET in_numberplanid = NULL WHERE in_numberplanid IS NOT NULL");
    $this->Execute("UPDATE cashregs SET out_numberplanid = NULL WHERE out_numberplanid IS NOT NULL");
    $this->Execute("DELETE FROM numberplanassignments");
} else {
    $sql_ids = implode(',', $ids);
    $this->Execute("UPDATE documents SET numberplanid = NULL
		WHERE numberplanid IS NOT NULL AND numberplanid NOT IN (" . $sql_ids . ")");
    $this->Execute("UPDATE cashregs SET in_numberplanid = NULL
		WHERE in_numberplanid IS NOT NULL AND in_numberplanid NOT IN (" . $sql_ids . ")");
    $this->Execute("UPDATE cashregs SET out_numberplanid = NULL
		WHERE out_numberplanid IS NOT NULL AND out_numberplanid NOT IN (" . $sql_ids . ")");
    $this->Execute("DELETE FROM numberplanassignments WHERE planid NOT IN (" . $sql_ids . ")");
}

$this->Execute("UPDATE documents SET reference = NULL WHERE reference = 0");
$this->Execute("UPDATE cash SET docid = NULL WHERE docid = 0");
$ids = $this->GetCol("SELECT id FROM documents");
if (empty($ids)) {
    $this->Execute("UPDATE documents SET reference = NULL WHERE reference IS NOT NULL");
    $this->Execute("UPDATE cash SET docid = NULL WHERE docid IS NOT NULL");
} else {
    foreach ($ids as $id) {
        $DB->Execute("UPDATE cash SET used_docid = ? WHERE docid = ?", array(1, $id));
        $DB->Execute("UPDATE documents SET used_reference = ? WHERE reference = ?", array(1, $id));
    }
    $this->Execute("UPDATE documents SET reference = NULL
		WHERE reference IS NOT NULL AND used_reference = ?", array(0));
    $this->Execute("UPDATE cash SET docid = NULL
		WHERE docid IS NOT NULL AND used_docid = ?", array(0));
}
$this->Execute("ALTER TABLE cash DROP COLUMN used_docid");
$this->Execute("ALTER TABLE documents DROP COLUMN used_reference");

$this->Execute("UPDATE receiptcontents SET regid = NULL WHERE regid = 0");
$this->Execute("DELETE FROM cashrights WHERE regid = 0");
$this->Execute("DELETE FROM cashreglog WHERE regid = 0");
$ids = $this->GetCol("SELECT id FROM cashregs");
if (empty($ids)) {
    $this->Execute("UPDATE receiptcontents SET regid = NULL WHERE regid IS NOT NULL");
    $this->Execute("DELETE FROM cashrights");
    $this->Execute("DELETE FROM cashreglog");
} else {
    $sql_ids = implode(',', $ids);
    $this->Execute("UPDATE receiptcontents SET regid = NULL
		WHERE regid IS NOT NULL AND regid NOT IN (" . $sql_ids . ")");
    $this->Execute("DELETE FROM cashrights WHERE regid NOT IN (" . $sql_ids . ")");
    $this->Execute("DELETE FROM cashreglog WHERE regid NOT IN (" . $sql_ids . ")");
}

$this->Execute("DELETE FROM tariffs WHERE taxid = 0");
$this->Execute("DELETE FROM liabilities WHERE taxid = 0");
$this->Execute("DELETE FROM invoicecontents WHERE taxid = 0");
$this->Execute("UPDATE cash SET taxid = NULL WHERE taxid = 0");
$ids = $this->GetCol("SELECT id FROM taxes");
if (empty($ids)) {
    $this->Execute("DELETE FROM tariffs");
    $this->Execute("DELETE FROM liabilities");
    $this->Execute("DELETE FROM invoicecontents");
    $this->Execute("UPDATE cash SET taxid = NULL WHERE taxid IS NOT NULL");
} else {
    $sql_ids = implode(',', $ids);
    $this->Execute("DELETE FROM tariffs WHERE taxid NOT IN (" . $sql_ids . ")");
    $this->Execute("DELETE FROM liabilities WHERE taxid NOT IN (" . $sql_ids . ")");
    $this->Execute("DELETE FROM invoicecontents WHERE taxid NOT IN (" . $sql_ids . ")");
    $this->Execute("UPDATE cash SET taxid = NULL
		WHERE taxid IS NOT NULL AND taxid NOT IN (" . $sql_ids . ")");
}

$this->Execute("UPDATE assignments SET tariffid = NULL WHERE tariffid = 0");
$this->Execute("UPDATE invoicecontents SET tariffid = NULL WHERE tariffid = 0");
$ids = $this->GetCol("SELECT id FROM tariffs");
if (empty($ids)) {
    $this->Execute("DELETE FROM assignments WHERE tariffid IS NOT NULL");
    $this->Execute("UPDATE invoicecontents SET tariffid = NULL WHERE tariffid IS NOT NULL");
} else {
    $sql_ids = implode(',', $ids);
    $this->Execute("DELETE FROM assignments
		WHERE tariffid IS NOT NULL AND tariffid NOT IN (" . $sql_ids . ")");
    $this->Execute("UPDATE invoicecontents SET tariffid = NULL
		WHERE tariffid IS NOT NULL AND tariffid NOT IN (" . $sql_ids . ")");
}

$this->Execute("UPDATE assignments SET liabilityid = NULL WHERE liabilityid = 0");
$ids = $this->GetCol("SELECT id FROM liabilities");
if (empty($ids)) {
    $this->Execute("DELETE FROM assignments WHERE liabilityid IS NOT NULL");
} else {
    $sql_ids = implode(',', $ids);
    $this->Execute("DELETE FROM assignments
		WHERE liabilityid IS NOT NULL AND liabilityid NOT IN (" . $sql_ids . ")");
}

$ids = $this->GetCol("UPDATE cash SET importid = NULL WHERE importid = 0");
$ids = $this->GetCol("SELECT id FROM cashimport");
if (empty($ids)) {
    $this->Execute("DELETE FROM cash WHERE importid IS NOT NULL");
} else {
    $sql_ids = implode(',', $ids);
    $this->Execute("DELETE FROM cash WHERE importid IS NOT NULL AND importid NOT IN (" . $sql_ids . ")");
}

$ids = $this->GetCol("SELECT id FROM cashsources");
if (empty($ids)) {
    $this->Execute("UPDATE cash SET sourceid = NULL");
} else {
    $sql_ids = implode(',', $ids);
    $this->Execute("UPDATE cash SET sourceid = NULL WHERE sourceid NOT IN (" . $sql_ids . ")");
}

$this->Execute("DELETE FROM nodegroupassignments WHERE nodegroupid = 0");
$ids = $this->GetCol("SELECT id FROM nodegroups");
if (empty($ids)) {
    $this->Execute("DELETE FROM nodegroupassignments");
} else {
    $sql_ids = implode(',', $ids);
    $this->Execute("DELETE FROM nodegroupassignments WHERE nodegroupid NOT IN (" . $sql_ids . ")");
}

$this->Execute("DELETE FROM nodegroupassignments WHERE nodeid = 0");
$this->Execute("UPDATE stats SET nodeid = NULL WHERE nodeid = 0");
$this->Execute("UPDATE nodesessions SET nodeid = NULL WHERE nodeid = 0");
$this->Execute("UPDATE ewx_pt_config SET nodeid = NULL WHERE nodeid = 0");
$ids = $this->GetCol("SELECT id FROM nodes");
if (empty($ids)) {
    $this->Execute("DELETE FROM nodegroupassignments");
    $this->Execute("UPDATE stats SET nodeid = NULL WHERE nodeid IS NOT NULL");
    $this->Execute("UPDATE nodesessions SET nodeid = NULL WHERE nodeid IS NOT NULL");
    $this->Execute("UPDATE ewx_pt_config SET nodeid = NULL WHERE nodeid IS NOT NULL");
} else {
    $sql_ids = implode(',', $ids);
    $this->Execute("DELETE FROM nodegroupassignments
		WHERE nodeid IS NOT NULL AND nodeid NOT IN (" . $sql_ids . ")");
    $this->Execute("UPDATE stats SET nodeid = NULL
		WHERE nodeid IS NOT NULL AND nodeid NOT IN (" . $sql_ids . ")");
    $this->Execute("UPDATE nodesessions SET nodeid = NULL
		WHERE nodeid IS NOT NULL AND nodeid NOT IN (" . $sql_ids . ")");
    $this->Execute("UPDATE ewx_pt_config SET nodeid = NULL
		WHERE nodeid IS NOT NULL AND nodeid NOT IN (" . $sql_ids . ")");
}

$this->Execute("UPDATE stats SET nodesessionid = NULL WHERE nodesessionid = 0");
$ids = $this->GetCol("SELECT id FROM nodesessions");
if (empty($ids)) {
    $this->Execute("UPDATE stats SET nodesessionid = NULL WHERE nodesessionid IS NOT NULL");
} else {
    $sql_ids = implode(',', $ids);
    $this->Execute("UPDATE stats SET nodesessionid = NULL
		WHERE nodesessionid IS NOT NULL AND nodesessionid NOT IN (" . $sql_ids . ")");
}

$this->Execute("UPDATE rttickets SET owner = NULL WHERE owner = 0");
$ids = $this->GetCol("SELECT id FROM users");
if (empty($ids)) {
    $this->Execute("UPDATE rttickets SET owner = NULL WHERE owner IS NOT NULL");
} else {
    $sql_ids = implode(',', $ids);
    $this->Execute("UPDATE rttickets SET owner = NULL
		WHERE owner IS NOT NULL AND owner NOT IN (" . $sql_ids . ")");
}

$this->Execute("UPDATE rtmessages SET inreplyto = NULL WHERE inreplyto = 0");
$ids = $this->GetCol("SELECT id FROM rtmessages");
if (empty($ids)) {
    $this->Execute("UPDATE rtmessages SET inreplyto = NULL WHERE inreplyto IS NOT NULL");
} else {
    $sql_ids = implode(',', $ids);
    $this->Execute("UPDATE rtmessages SET inreplyto = NULL
		WHERE inreplyto IS NOT NULL AND inreplyto NOT IN (" . $sql_ids . ")");
}

$this->Execute("DELETE FROM passwd WHERE domainid = 0");
$this->Execute("DELETE FROM aliases WHERE domainid = 0");
$ids = $this->GetCol("SELECT id FROM domains");
if (empty($ids)) {
    $this->Execute("DELETE FROM passwd");
    $this->Execute("DELETE FROM aliases");
} else {
    $sql_ids = implode(',', $ids);
    $this->Execute("DELETE FROM passwd WHERE domainid NOT IN (" . $sql_ids . ")");
    $this->Execute("DELETE FROM aliases WHERE domainid NOT IN (" . $sql_ids . ")");
}

$this->Execute("DELETE FROM aliasassignments WHERE accountid = 0 AND mail_forward = ''");
$this->Execute("UPDATE aliasassignments SET accountid = NULL WHERE accountid = 0 AND mail_forward<>''");
$ids = $this->GetCol("SELECT id FROM passwd");
if (empty($ids)) {
    $this->Execute("DELETE FROM aliasassignments WHERE mail_forward = ''");
} else {
    $sql_ids = implode(',', $ids);
    $this->Execute("DELETE FROM aliasassignments WHERE mail_forward = '' AND accountid NOT IN (" . $sql_ids . ")");
}

$this->Execute("DELETE FROM aliasassignments WHERE aliasid = 0");
$ids = $this->GetCol("SELECT id FROM aliases");
if (empty($ids)) {
    $this->Execute("DELETE FROM aliasassignments");
} else {
    $sql_ids = implode(',', $ids);
    $this->Execute("DELETE FROM aliasassignments WHERE aliasid NOT IN (" . $sql_ids . ")");
}

$this->Execute("DELETE FROM eventassignments WHERE eventid = 0");
$ids = $this->GetCol("SELECT id FROM events");
if (empty($ids)) {
    $this->Execute("DELETE FROM eventassignments");
} else {
    $sql_ids = implode(',', $ids);
    $this->Execute("DELETE FROM eventassignments WHERE eventid NOT IN (" . $sql_ids . ")");
}

$this->Execute("DELETE FROM daemoninstances WHERE hostid = 0");
$ids = $this->GetCol("SELECT id FROM hosts");
if (empty($ids)) {
    $this->Execute("DELETE FROM daemoninstances");
} else {
    $sql_ids = implode(',', $ids);
    $this->Execute("DELETE FROM daemoninstances WHERE hostid NOT IN (" . $sql_ids . ")");
}

$this->Execute("DELETE FROM daemonconfig WHERE instanceid = 0");
$ids = $this->GetCol("SELECT id FROM daemoninstances");
if (empty($ids)) {
    $this->Execute("DELETE FROM daemonconfig");
} else {
    $sql_ids = implode(',', $ids);
    $this->Execute("DELETE FROM daemonconfig WHERE instanceid NOT IN (" . $sql_ids . ")");
}

$this->Execute("DELETE FROM messageitems WHERE messageid = 0");
$ids = $this->GetCol("SELECT id FROM messages");
if (empty($ids)) {
    $this->Execute("DELETE FROM messageitems");
} else {
    $sql_ids = implode(',', $ids);
    $this->Execute("DELETE FROM messageitems WHERE messageid NOT IN (" . $sql_ids . ")");
}

$this->Execute("UPDATE up_help SET reference = NULL WHERE reference = 0");
$ids = $this->GetCol("SELECT id FROM up_help");
if (empty($ids)) {
    $this->Execute("UPDATE up_help SET reference = NULL WHERE reference IS NOT NULL");
} else {
    $sql_ids = implode(',', $ids);
    $this->Execute("UPDATE up_help SET reference = NULL
		WHERE reference IS NOT NULL AND reference NOT IN (" . $sql_ids . ")");
}

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2017101204', 'dbversion'));

$this->CommitTrans();
