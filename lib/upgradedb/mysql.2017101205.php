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

$this->Execute("ALTER TABLE customers ADD CONSTRAINT customers_divisionid_fkey
	FOREIGN KEY (divisionid) REFERENCES divisions (id) ON DELETE SET NULL ON UPDATE CASCADE");
$this->Execute("ALTER TABLE zipcodes ADD CONSTRAINT zipcodes_stateid_fkey
	FOREIGN KEY (stateid) REFERENCES states (id) ON DELETE SET NULL ON UPDATE CASCADE");
$this->Execute("ALTER TABLE documents ADD CONSTRAINT documents_numberplanid_fkey
	FOREIGN KEY (numberplanid) REFERENCES numberplans (id) ON DELETE SET NULL ON UPDATE CASCADE");
$this->Execute("ALTER TABLE documents ADD CONSTRAINT documents_divisionid_fkey
	FOREIGN KEY (divisionid) REFERENCES divisions (id) ON DELETE SET NULL ON UPDATE CASCADE");
$this->Execute("ALTER TABLE documents ADD CONSTRAINT documents_countryid_fkey
	FOREIGN KEY (countryid) REFERENCES countries (id) ON DELETE SET NULL ON UPDATE CASCADE");
$this->Execute("ALTER TABLE documents ADD CONSTRAINT documents_reference_fkey
	FOREIGN KEY (reference) REFERENCES documents (id) ON DELETE SET NULL ON UPDATE CASCADE");
$this->Execute("ALTER TABLE documents ADD CONSTRAINT documents_div_countryid_fkey
	FOREIGN KEY (div_countryid) REFERENCES countries (id) ON DELETE SET NULL ON UPDATE CASCADE");
$this->Execute("ALTER TABLE cashregs ADD CONSTRAINT cashregs_in_numberplanid_fkey
	FOREIGN KEY (in_numberplanid) REFERENCES numberplans (id) ON DELETE SET NULL ON UPDATE CASCADE");
$this->Execute("ALTER TABLE cashregs ADD CONSTRAINT cashregs_out_numberplanid_fkey
	FOREIGN KEY (out_numberplanid) REFERENCES numberplans (id) ON DELETE SET NULL ON UPDATE CASCADE");
$this->Execute("ALTER TABLE receiptcontents ADD CONSTRAINT receiptcontents_regid_fkey
	FOREIGN KEY (regid) REFERENCES cashregs (id) ON DELETE SET NULL ON UPDATE CASCADE");
$this->Execute("ALTER TABLE tariffs ADD CONSTRAINT tariffs_taxid_fkey
	FOREIGN KEY (taxid) REFERENCES taxes (id) ON DELETE CASCADE ON UPDATE CASCADE");
$this->Execute("ALTER TABLE liabilities ADD CONSTRAINT liabilities_taxid_fkey 
	FOREIGN KEY (taxid) REFERENCES taxes (id) ON DELETE CASCADE ON UPDATE CASCADE");
$this->Execute("ALTER TABLE assignments ADD CONSTRAINT assignments_tariffid_fkey
	FOREIGN KEY (tariffid) REFERENCES tariffs (id) ON DELETE CASCADE ON UPDATE CASCADE");
$this->Execute("ALTER TABLE assignments ADD CONSTRAINT assignments_liabilityid_fkey
	FOREIGN KEY (liabilityid) REFERENCES liabilities (id) ON DELETE CASCADE ON UPDATE CASCADE");
$this->Execute("ALTER TABLE invoicecontents ADD CONSTRAINT invoicecontents_taxid_fkey
	FOREIGN KEY (taxid) REFERENCES taxes (id) ON DELETE CASCADE ON UPDATE CASCADE");
$this->Execute("ALTER TABLE invoicecontents ADD CONSTRAINT invoicecontents_tariffid_fkey
	FOREIGN KEY (tariffid) REFERENCES tariffs (id) ON DELETE SET NULL ON UPDATE CASCADE");
$this->Execute("ALTER TABLE cash ADD CONSTRAINT cash_taxid_fkey
	FOREIGN KEY (taxid) REFERENCES taxes (id) ON DELETE SET NULL ON UPDATE CASCADE");
$this->Execute("ALTER TABLE cash ADD CONSTRAINT cash_docid_fkey
	FOREIGN KEY (docid) REFERENCES documents (id) ON DELETE CASCADE ON UPDATE CASCADE");
$this->Execute("ALTER TABLE cash ADD CONSTRAINT cash_importid_fkey
	FOREIGN KEY (importid) REFERENCES cashimport (id) ON DELETE CASCADE ON UPDATE CASCADE");
$this->Execute("ALTER TABLE cash ADD CONSTRAINT cash_sourceid_fkey
	FOREIGN KEY (sourceid) REFERENCES cashsources (id) ON DELETE SET NULL ON UPDATE CASCADE");
$this->Execute("ALTER TABLE nodegroupassignments ADD CONSTRAINT nodegroupassignments_nodegroupid_fkey
	FOREIGN KEY (nodegroupid) REFERENCES nodegroups (id) ON DELETE CASCADE ON UPDATE CASCADE");
$this->Execute("ALTER TABLE nodegroupassignments ADD CONSTRAINT nodegroupassignments_nodeid_fkey
	FOREIGN KEY (nodeid) REFERENCES nodes (id) ON DELETE CASCADE ON UPDATE CASCADE");
$this->Execute("ALTER TABLE numberplanassignments ADD CONSTRAINT numberplanassignments_planid_fkey
	FOREIGN KEY (planid) REFERENCES numberplans (id) ON DELETE CASCADE ON UPDATE CASCADE");
$this->Execute("ALTER TABLE numberplanassignments ADD CONSTRAINT numberplanassignments_divisionid_fkey
	FOREIGN KEY (divisionid) REFERENCES divisions (id) ON DELETE CASCADE ON UPDATE CASCADE");
$this->Execute("ALTER TABLE stats ADD CONSTRAINT stats_nodesessionid_fkey
	FOREIGN KEY (nodesessionid) REFERENCES nodesessions (id) ON DELETE SET NULL ON UPDATE CASCADE");
$this->Execute("ALTER TABLE stats ADD CONSTRAINT stats_nodeid_fkey
	FOREIGN KEY (nodeid) REFERENCES nodes (id) ON DELETE SET NULL ON UPDATE CASCADE");
$this->Execute("ALTER TABLE nodesessions ADD CONSTRAINT nodesessions_nodeid_fkey
	FOREIGN KEY (nodeid) REFERENCES nodes (id) ON DELETE SET NULL ON UPDATE CASCADE");
$this->Execute("ALTER TABLE rttickets ADD CONSTRAINT rttickets_owner_fkey
	FOREIGN KEY (owner) REFERENCES users (id) ON DELETE SET NULL ON UPDATE CASCADE");
$this->Execute("ALTER TABLE rtmessages ADD CONSTRAINT rtmessages_inreplyto_fkey
	FOREIGN KEY (inreplyto) REFERENCES rtmessages (id) ON DELETE SET NULL ON UPDATE CASCADE");
$this->Execute("ALTER TABLE passwd ADD CONSTRAINT passwd_domainid_fkey
	FOREIGN KEY (domainid) REFERENCES domains (id) ON DELETE CASCADE ON UPDATE CASCADE");
$this->Execute("ALTER TABLE aliases ADD CONSTRAINT aliases_domainid_fkey
	FOREIGN KEY (domainid) REFERENCES domains (id) ON DELETE CASCADE ON UPDATE CASCADE");
$this->Execute("ALTER TABLE aliasassignments ADD CONSTRAINT aliasassignments_aliasid_fkey
	FOREIGN KEY (aliasid) REFERENCES aliases (id) ON DELETE CASCADE ON UPDATE CASCADE");
$this->Execute("ALTER TABLE aliasassignments ADD CONSTRAINT aliasassignments_accountid_fkey
	FOREIGN KEY (accountid) REFERENCES passwd (id) ON DELETE CASCADE ON UPDATE CASCADE");
$this->Execute("ALTER TABLE eventassignments ADD CONSTRAINT eventassignments_eventid_fkey
	FOREIGN KEY (eventid) REFERENCES events (id) ON DELETE CASCADE ON UPDATE CASCADE");
$this->Execute("ALTER TABLE daemoninstances ADD CONSTRAINT daemoninstances_hostid_fkey
	FOREIGN KEY (hostid) REFERENCES hosts (id) ON DELETE CASCADE ON UPDATE CASCADE");
$this->Execute("ALTER TABLE daemonconfig ADD CONSTRAINT daemonconfig_instanceid_fkey
	FOREIGN KEY (instanceid) REFERENCES daemoninstances (id) ON DELETE CASCADE ON UPDATE CASCADE");
$this->Execute("ALTER TABLE cashrights ADD CONSTRAINT cashrights_regid_fkey
	FOREIGN KEY (regid) REFERENCES cashregs (id) ON DELETE CASCADE ON UPDATE CASCADE");
$this->Execute("ALTER TABLE cashreglog ADD CONSTRAINT cashreglog_regid_fkey
	FOREIGN KEY (regid) REFERENCES cashregs (id) ON DELETE CASCADE ON UPDATE CASCADE");
$this->Execute("ALTER TABLE ewx_pt_config ADD CONSTRAINT ewx_pt_config_nodeid_fkey
	FOREIGN KEY (nodeid) REFERENCES nodes (id) ON DELETE SET NULL ON UPDATE CASCADE");
$this->Execute("ALTER TABLE messageitems ADD CONSTRAINT messageitems_messageid_fkey
	FOREIGN KEY (messageid) REFERENCES messages (id) ON DELETE CASCADE ON UPDATE CASCADE");
$this->Execute("ALTER TABLE up_help ADD CONSTRAINT up_help_reference_fkey
	FOREIGN KEY (reference) REFERENCES up_help (id) ON DELETE CASCADE ON UPDATE CASCADE");

$this->Execute("
CREATE VIEW vnodetariffs_allsuspended AS
	SELECT customerid, COUNT(id) AS allsuspended FROM assignments
	WHERE tariffid IS NULL AND liabilityid IS NULL
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

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2017101205', 'dbversion'));

$this->CommitTrans();
