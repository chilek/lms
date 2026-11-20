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

if (!$this->ResourceExists('customernotes_customerid_idx', LMSDB::RESOURCE_TYPE_INDEX)) {
    $this->Execute("CREATE INDEX customernotes_customerid_idx ON customernotes (customerid)");
}

if (!$this->ResourceExists('nodes_netid_idx', LMSDB::RESOURCE_TYPE_INDEX)) {
    $this->Execute("CREATE INDEX nodes_netid_idx ON nodes (netid)");
}

if (!$this->ResourceExists('macs_nodeid_idx', LMSDB::RESOURCE_TYPE_INDEX)) {
    $this->Execute("CREATE INDEX macs_nodeid_idx ON macs (nodeid)");
}

if (!$this->ResourceExists('events_customerid_idx', LMSDB::RESOURCE_TYPE_INDEX)) {
    $this->Execute("CREATE INDEX events_customerid_idx ON events (customerid)");
}

if (!$this->ResourceExists('events_ticketid_idx', LMSDB::RESOURCE_TYPE_INDEX)) {
    $this->Execute("CREATE INDEX events_ticketid_idx ON events (ticketid)");
}

if (!$this->ResourceExists('voip_cdr_type_idx', LMSDB::RESOURCE_TYPE_INDEX)) {
    $this->Execute("CREATE INDEX voip_cdr_type_idx ON voip_cdr (type)");
}

if (!$this->ResourceExists('documents_fullnumber_idx', LMSDB::RESOURCE_TYPE_INDEX)) {
    $this->Execute("CREATE INDEX documents_fullnumber_idx ON documents (fullnumber)");
}

if (!$this->ResourceExists('netradiosectors_netdev_idx', LMSDB::RESOURCE_TYPE_INDEX)) {
    $this->Execute("CREATE INDEX netradiosectors_netdev_idx ON netradiosectors (netdev)");
}

if (!$this->ResourceExists('ksefinvoicesummaries_ksef_invoice_id_idx', LMSDB::RESOURCE_TYPE_INDEX)) {
    $this->Execute("CREATE INDEX ksefinvoicesummaries_ksef_invoice_id_idx ON ksefinvoicesummaries (ksef_invoice_id)");
}

if (!$this->ResourceExists('ksefinvoiceitems_ksef_invoice_id_idx', LMSDB::RESOURCE_TYPE_INDEX)) {
    $this->Execute("CREATE INDEX ksefinvoiceitems_ksef_invoice_id_idx ON ksefinvoiceitems (ksef_invoice_id)");
}

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2026111902', 'dbversion'));

$this->CommitTrans();
