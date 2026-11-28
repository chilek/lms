<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2026 LMS Developers
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

if (!$this->ResourceExists('assignments_promotionschemaid_idx', LMSDB::RESOURCE_TYPE_INDEX)) {
    $this->Execute("CREATE INDEX assignments_promotionschemaid_idx ON assignments (promotionschemaid)");
}

if (!$this->ResourceExists('netdevices_ownerid_idx', LMSDB::RESOURCE_TYPE_INDEX)) {
    $this->Execute("CREATE INDEX netdevices_ownerid_idx ON netdevices (ownerid)");
}

if (!$this->ResourceExists('addresses_city_id_idx', LMSDB::RESOURCE_TYPE_INDEX)) {
    $this->Execute("CREATE INDEX addresses_city_id_idx ON addresses (city_id)");
}

if (!$this->ResourceExists('addresses_street_id_idx', LMSDB::RESOURCE_TYPE_INDEX)) {
    $this->Execute("CREATE INDEX addresses_street_id_idx ON addresses (street_id)");
}

if (!$this->ResourceExists('location_buildings_street_id_idx', LMSDB::RESOURCE_TYPE_INDEX)) {
    $this->Execute("CREATE INDEX location_buildings_street_id_idx ON location_buildings (street_id)");
}

if (!$this->ResourceExists('pna_cityid_idx', LMSDB::RESOURCE_TYPE_INDEX)) {
    $this->Execute("CREATE INDEX pna_cityid_idx ON pna (cityid)");
}

if (!$this->ResourceExists('pna_streetid_idx', LMSDB::RESOURCE_TYPE_INDEX)) {
    $this->Execute("CREATE INDEX pna_streetid_idx ON pna (streetid)");
}

if (!$this->ResourceExists('customers_divisionid_idx', LMSDB::RESOURCE_TYPE_INDEX)) {
    $this->Execute("CREATE INDEX customers_divisionid_idx ON customers (divisionid)");
}

if (!$this->ResourceExists('customer_addresses_address_id_idx', LMSDB::RESOURCE_TYPE_INDEX)) {
    $this->Execute("CREATE INDEX customer_addresses_address_id_idx ON customer_addresses (address_id)");
}

if (!$this->ResourceExists('customercallassignments_customerid_idx', LMSDB::RESOURCE_TYPE_INDEX)) {
    $this->Execute("CREATE INDEX customercallassignments_customerid_idx ON customercallassignments (customerid)");
}

if (!$this->ResourceExists('documents_divisionid_idx', LMSDB::RESOURCE_TYPE_INDEX)) {
    $this->Execute("CREATE INDEX documents_divisionid_idx ON documents (divisionid)");
}

if (!$this->ResourceExists('documents_recipient_address_id_idx', LMSDB::RESOURCE_TYPE_INDEX)) {
    $this->Execute("CREATE INDEX documents_recipient_address_id_idx ON documents (recipient_address_id)");
}

if (!$this->ResourceExists('documents_post_address_id_idx', LMSDB::RESOURCE_TYPE_INDEX)) {
    $this->Execute("CREATE INDEX documents_post_address_id_idx ON documents (post_address_id)");
}

if (!$this->ResourceExists('assignments_docid_idx', LMSDB::RESOURCE_TYPE_INDEX)) {
    $this->Execute("CREATE INDEX assignments_docid_idx ON assignments (docid)");
}

if (!$this->ResourceExists('assignments_liabilityid_idx', LMSDB::RESOURCE_TYPE_INDEX)) {
    $this->Execute("CREATE INDEX assignments_liabilityid_idx ON assignments (liabilityid)");
}

if (!$this->ResourceExists('assignments_recipient_address_id_idx', LMSDB::RESOURCE_TYPE_INDEX)) {
    $this->Execute("CREATE INDEX assignments_recipient_address_id_idx ON assignments (recipient_address_id)");
}

if (!$this->ResourceExists('voipaccounts_ownerid_idx', LMSDB::RESOURCE_TYPE_INDEX)) {
    $this->Execute("CREATE INDEX voipaccounts_ownerid_idx ON voipaccounts (ownerid)");
}

if (!$this->ResourceExists('voipaccounts_address_id_idx', LMSDB::RESOURCE_TYPE_INDEX)) {
    $this->Execute("CREATE INDEX voipaccounts_address_id_idx ON voipaccounts (address_id)");
}

if (!$this->ResourceExists('voip_cdr_callervoipaccountid_idx', LMSDB::RESOURCE_TYPE_INDEX)) {
    $this->Execute("CREATE INDEX voip_cdr_callervoipaccountid_idx ON voip_cdr (callervoipaccountid)");
}

if (!$this->ResourceExists('voip_cdr_calleevoipaccountid_idx', LMSDB::RESOURCE_TYPE_INDEX)) {
    $this->Execute("CREATE INDEX voip_cdr_calleevoipaccountid_idx ON voip_cdr (calleevoipaccountid)");
}

if (!$this->ResourceExists('netranges_buildingid_idx', LMSDB::RESOURCE_TYPE_INDEX)) {
    $this->Execute("CREATE INDEX netranges_buildingid_idx ON netranges (buildingid)");
}

if (!$this->ResourceExists('netnodes_address_id_idx', LMSDB::RESOURCE_TYPE_INDEX)) {
    $this->Execute("CREATE INDEX netnodes_address_id_idx ON netnodes (address_id)");
}

if (!$this->ResourceExists('netdevices_netnodeid_idx', LMSDB::RESOURCE_TYPE_INDEX)) {
    $this->Execute("CREATE INDEX netdevices_netnodeid_idx ON netdevices (netnodeid)");
}

if (!$this->ResourceExists('netdevices_address_id_idx', LMSDB::RESOURCE_TYPE_INDEX)) {
    $this->Execute("CREATE INDEX netdevices_address_id_idx ON netdevices (address_id)");
}

if (!$this->ResourceExists('nodes_address_id_idx', LMSDB::RESOURCE_TYPE_INDEX)) {
    $this->Execute("CREATE INDEX nodes_address_id_idx ON nodes (address_id)");
}

if (!$this->ResourceExists('nodelocks_nodeid_idx', LMSDB::RESOURCE_TYPE_INDEX)) {
    $this->Execute("CREATE INDEX nodelocks_nodeid_idx ON nodelocks (nodeid)");
}

if (!$this->ResourceExists('netlinks_dst_idx', LMSDB::RESOURCE_TYPE_INDEX)) {
    $this->Execute("CREATE INDEX netlinks_dst_idx ON netlinks (dst)");
}

if (!$this->ResourceExists('rttickets_owner_idx', LMSDB::RESOURCE_TYPE_INDEX)) {
    $this->Execute("CREATE INDEX rttickets_owner_idx ON rttickets (owner)");
}

if (!$this->ResourceExists('rttickets_verifierid_idx', LMSDB::RESOURCE_TYPE_INDEX)) {
    $this->Execute("CREATE INDEX rttickets_verifierid_idx ON rttickets (verifierid)");
}

if (!$this->ResourceExists('rttickets_nodeid_idx', LMSDB::RESOURCE_TYPE_INDEX)) {
    $this->Execute("CREATE INDEX rttickets_nodeid_idx ON rttickets (nodeid)");
}

if (!$this->ResourceExists('rttickets_netnodeid_idx', LMSDB::RESOURCE_TYPE_INDEX)) {
    $this->Execute("CREATE INDEX rttickets_netnodeid_idx ON rttickets (netnodeid)");
}

if (!$this->ResourceExists('rttickets_netdevid_idx', LMSDB::RESOURCE_TYPE_INDEX)) {
    $this->Execute("CREATE INDEX rttickets_netdevid_idx ON rttickets (netdevid)");
}

if (!$this->ResourceExists('rttickets_address_id_idx', LMSDB::RESOURCE_TYPE_INDEX)) {
    $this->Execute("CREATE INDEX rttickets_address_id_idx ON rttickets (address_id)");
}

if (!$this->ResourceExists('rttickets_parentid_idx', LMSDB::RESOURCE_TYPE_INDEX)) {
    $this->Execute("CREATE INDEX rttickets_parentid_idx ON rttickets (parentid)");
}

if (!$this->ResourceExists('rtmessages_customerid_idx', LMSDB::RESOURCE_TYPE_INDEX)) {
    $this->Execute("CREATE INDEX rtmessages_customerid_idx ON rtmessages (customerid)");
}

if (!$this->ResourceExists('rtmessages_inreplyto_idx', LMSDB::RESOURCE_TYPE_INDEX)) {
    $this->Execute("CREATE INDEX rtmessages_inreplyto_idx ON rtmessages (inreplyto)");
}

if (!$this->ResourceExists('events_address_id_idx', LMSDB::RESOURCE_TYPE_INDEX)) {
    $this->Execute("CREATE INDEX events_address_id_idx ON events (address_id)");
}

if (!$this->ResourceExists('eventassignments_userid_idx', LMSDB::RESOURCE_TYPE_INDEX)) {
    $this->Execute("CREATE INDEX eventassignments_userid_idx ON eventassignments (userid)");
}

if (!$this->ResourceExists('filecontainers_messageid_idx', LMSDB::RESOURCE_TYPE_INDEX)) {
    $this->Execute("CREATE INDEX filecontainers_messageid_idx ON filecontainers (messageid)");
}

if (!$this->ResourceExists('ksefdocuments_docid_idx', LMSDB::RESOURCE_TYPE_INDEX)) {
    $this->Execute("CREATE INDEX ksefdocuments_docid_idx ON ksefdocuments (docid)");
}

if (!$this->ResourceExists('ksefdocuments_batchsessionid_idx', LMSDB::RESOURCE_TYPE_INDEX)) {
    $this->Execute("CREATE INDEX ksefdocuments_batchsessionid_idx ON ksefdocuments (batchsessionid)");
}

if (!$this->ResourceExists('ksefinvoicethirdsubjects_ksefinvoiceid_idx', LMSDB::RESOURCE_TYPE_INDEX)) {
    $this->Execute("CREATE INDEX ksefinvoicethirdsubjects_ksefinvoiceid_idx ON ksefinvoicethirdsubjects (ksefinvoiceid)");
}

if (!$this->ResourceExists('ksefinvoicetagassignments_ksef_invoice_id_idx', LMSDB::RESOURCE_TYPE_INDEX)) {
    $this->Execute("CREATE INDEX ksefinvoicetagassignments_ksef_invoice_id_idx ON ksefinvoicetagassignments (ksef_invoice_id)");
}

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2026112700', 'dbversion'));

$this->CommitTrans();
