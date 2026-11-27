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

$constraintExists = function ($table, $name) {
    return $this->GetOne(
        "SELECT COUNT(*) FROM pg_constraint WHERE conrelid = ?::regclass AND conname = ?",
        array($table, $name)
    ) > 0;
};

$indexExists = function ($table, $name) {
    return $this->GetOne(
        "SELECT COUNT(*) FROM pg_indexes WHERE schemaname = current_schema() AND tablename = ? AND indexname = ?",
        array($table, $name)
    ) > 0;
};

$constraintRenames = array(
    array('voip_rule_groups', 'voip_rules_pkey', 'voip_rule_groups_pkey'),
    array('voip_rules', 'voip_group_rule_assignments_pkey', 'voip_rules_pkey'),
    array('voip_rules', 'voip_group_rule_assignments_groupid_fkey', 'voip_rules_prefix_group_id_fkey'),
    array('voip_rules', 'voip_group_rule_assignments_ruleid_fkey', 'voip_rules_rule_group_id_fkey'),
    array('assignments', 'recipient_address_id_fk2', 'assignments_recipient_address_id_fkey'),
    array('divisions', 'divisions_address_id_fk', 'divisions_address_id_fkey'),
    array('documents', 'recipient_address_id_fk', 'documents_recipient_address_id_fkey'),
    array('events', 'event_netdevid_fkey', 'events_netdevid_fkey'),
    array('events', 'event_netnodeid_fkey', 'events_netnodeid_fkey'),
    array('events', 'event_ticketid_rttickets_id_fk', 'events_ticketid_fk'),
    array('invprojects', 'invprojects_divisions_fkey', 'invprojects_divisionid_fkey'),
    array('logtransactions', 'logtransactions', 'logtransactions_userid_fkey'),
    array('netdevices', 'netdevices_address_id_fk', 'netdevices_address_id_fkey'),
    array('netdevices', 'ownerid_fk', 'netdevices_ownerid_fkey'),
    array('netdevices', 'netdevices_invproject_fkey', 'netdevices_invprojectid_fkey'),
    array('netdevices', 'netdevices_netdevicemodel_fkey', 'netdevices_netdevicemodelid_fkey'),
    array('netdevices', 'netdevices_netnode_fkey', 'netdevices_netnodeid_fkey'),
    array('netnodes', 'netnodes_divisions_fkey', 'netnodes_divisionid_fkey'),
    array('nodes', 'nodes_invproject_fkey', 'nodes_invprojectid_fkey'),
    array('nodes', 'nodes_ipaddr_key', 'nodes_ipaddr_netid_key'),
    array('pna', 'pna_zip_cityid_streetid_fromnumber_tonumber_parity_key', 'pna_zip_cityid_streetid_fromnumber_tonumber_key'),
    array('receiptcontents', 'receiptcontents_docid_fk', 'receiptcontents_docid_fkey'),
    array('tariffs', 'tariff_id_fk', 'tariffs_voip_tariff_id_fkey'),
    array('voip_numbers', 'vn_uniq_index', 'voip_numbers_voip_account_id_number_index_key'),
    array('voip_price_groups', 'group_id_fk', 'voip_price_groups_prefix_group_id_fkey'),
    array('voip_price_groups', 'price_tariffid', 'voip_price_groups_voip_tariff_id_fkey'),
    array('voipaccounts', 'voipaccounts_address_id_fk', 'voipaccounts_address_id_fkey'),
);

foreach ($constraintRenames as $rename) {
    [$table, $oldName, $newName] = $rename;
    if ($constraintExists($table, $oldName) && !$constraintExists($table, $newName)) {
        $this->Execute("ALTER TABLE " . $table . " RENAME CONSTRAINT " . $oldName . " TO " . $newName);
    }
}

$indexRenames = array(
    array('events', 'nodeid_idx', 'events_nodeid_idx'),
    array('customercontacts', 'customercontacts_phone_idx', 'customercontacts_contact_idx'),
);

foreach ($indexRenames as $rename) {
    [$table, $oldName, $newName] = $rename;
    if ($indexExists($table, $oldName) && !$indexExists($table, $newName)) {
        $this->Execute("ALTER INDEX " . $oldName . " RENAME TO " . $newName);
    }
}

if ($this->GetOne(
    "SELECT COUNT(*) FROM pg_constraint c
    JOIN pg_attribute a ON a.attrelid = c.conrelid AND a.attnum = c.conkey[1]
    WHERE c.conrelid = 'rttickets'::regclass AND c.conname = 'rttickets_parentid_fkey' AND a.attname = 'id'"
)) {
    $this->Execute("ALTER TABLE rttickets DROP CONSTRAINT rttickets_parentid_fkey");
}
if (!$constraintExists('rttickets', 'rttickets_parentid_fkey')) {
    $this->Execute("UPDATE rttickets SET parentid = NULL WHERE parentid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM rttickets t WHERE t.id = rttickets.parentid)");
    $this->Execute("ALTER TABLE rttickets ADD CONSTRAINT rttickets_parentid_fkey FOREIGN KEY (parentid) REFERENCES rttickets (id) ON DELETE SET NULL ON UPDATE CASCADE");
}

if ($this->GetOne(
    "SELECT COUNT(*) FROM pg_constraint c
    JOIN pg_attribute a ON a.attrelid = c.conrelid AND a.attnum = c.conkey[1]
    WHERE c.conrelid = 'voip_cdr'::regclass AND c.conname = 'voip_cdr_direction_uniqueid_ukey' AND a.attname = 'direction'"
)) {
    $this->Execute("ALTER TABLE voip_cdr DROP CONSTRAINT voip_cdr_direction_uniqueid_ukey");
    $this->Execute("ALTER TABLE voip_cdr ADD CONSTRAINT voip_cdr_direction_uniqueid_ukey UNIQUE (uniqueid, direction)");
}

if (!$this->ResourceExists('passwdhistory_userid_idx', LMSDB::RESOURCE_TYPE_INDEX)) {
    $this->Execute("CREATE INDEX passwdhistory_userid_idx ON passwdhistory (userid)");
}

$this->Execute("DROP SEQUENCE IF EXISTS imessengers_id_seq");
$this->Execute("DROP SEQUENCE IF EXISTS voip_prefix_group_assignments_id_seq");

$this->Execute("ALTER TABLE domains ALTER COLUMN type SET DEFAULT ''");
$this->Execute("ALTER TABLE cashimport ALTER COLUMN customer SET DEFAULT ''");
$this->Execute("ALTER TABLE cashimport ALTER COLUMN description SET DEFAULT ''");
$this->Execute("ALTER TABLE users ALTER COLUMN rights SET DEFAULT ''");
$this->Execute("ALTER TABLE promotionassignments ALTER COLUMN label SET DEFAULT NULL");

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2026112701', 'dbversion'));

$this->CommitTrans();
