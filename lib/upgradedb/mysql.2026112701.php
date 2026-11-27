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

$getColumn = function ($table, $column) {
    return $this->GetRow(
        "SELECT COLUMN_TYPE AS type, IS_NULLABLE AS nullable FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?",
        array($table, $column)
    );
};

$getIndex = function ($table, $name) {
    $columns = $this->GetCol(
        "SELECT COLUMN_NAME FROM information_schema.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?
        ORDER BY SEQ_IN_INDEX",
        array($table, $name)
    );
    if (empty($columns)) {
        return null;
    }
    $nonUnique = $this->GetOne(
        "SELECT NON_UNIQUE FROM information_schema.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?
        LIMIT 1",
        array($table, $name)
    );
    return array('columns' => $columns, 'unique' => empty($nonUnique));
};

$renameIndex = function ($table, $oldName, $newName) use ($getIndex) {
    $index = $getIndex($table, $oldName);
    if (empty($index) || $getIndex($table, $newName)) {
        return;
    }
    $this->Execute(
        "ALTER TABLE " . $table . " DROP INDEX " . $oldName . ", ADD " . ($index['unique'] ? 'UNIQUE ' : '') . "INDEX "
        . $newName . " (" . implode(', ', $index['columns']) . ")"
    );
};

$getForeignKeys = function ($table, $column) {
    return $this->GetAll(
        "SELECT k.CONSTRAINT_NAME AS name, k.REFERENCED_TABLE_NAME AS reftable,
            r.UPDATE_RULE AS onupdate, r.DELETE_RULE AS ondelete
        FROM information_schema.KEY_COLUMN_USAGE k
        JOIN information_schema.REFERENTIAL_CONSTRAINTS r ON r.CONSTRAINT_SCHEMA = k.CONSTRAINT_SCHEMA
            AND r.TABLE_NAME = k.TABLE_NAME AND r.CONSTRAINT_NAME = k.CONSTRAINT_NAME
        WHERE k.TABLE_SCHEMA = DATABASE() AND k.TABLE_NAME = ? AND k.COLUMN_NAME = ?
            AND k.REFERENCED_TABLE_NAME IS NOT NULL",
        array($table, $column)
    );
};

$dropForeignKeys = function ($table, $column, $refTable = null, $onUpdate = null, $onDelete = null) use ($getForeignKeys) {
    $found = false;
    $foreignKeys = $getForeignKeys($table, $column);
    if (!empty($foreignKeys)) {
        foreach ($foreignKeys as $foreignKey) {
            if (!$found && isset($refTable) && $foreignKey['reftable'] == $refTable
                && $foreignKey['onupdate'] == $onUpdate && $foreignKey['ondelete'] == $onDelete) {
                $found = true;
            } else {
                $this->Execute("ALTER TABLE " . $table . " DROP FOREIGN KEY " . $foreignKey['name']);
            }
        }
    }
    return $found;
};

$syncForeignKey = function ($table, $column, $refTable, $onUpdate, $onDelete, $name = null) use ($dropForeignKeys) {
    if ($dropForeignKeys($table, $column, $refTable, $onUpdate, $onDelete)) {
        return;
    }
    $orphanJoin = $table . " t LEFT JOIN " . $refTable . " r ON r.id = t." . $column;
    $orphanCondition = "t." . $column . " IS NOT NULL AND r.id IS NULL";
    if ($onDelete == 'SET NULL') {
        $this->Execute("UPDATE " . $orphanJoin . " SET t." . $column . " = NULL WHERE " . $orphanCondition);
    } else {
        $orphans = (int) $this->GetOne("SELECT COUNT(*) FROM " . $orphanJoin . " WHERE " . $orphanCondition);
        if ($orphans) {
            writesyslog(
                'LMS upgradedb 2026112701: skipped foreign key ' . $table . '.' . $column . ' -> ' . $refTable
                    . '(id): ' . $orphans . ' orphaned rows',
                LOG_WARNING
            );
            return;
        }
    }
    $this->Execute(
        "ALTER TABLE " . $table . " ADD " . (isset($name) ? "CONSTRAINT " . $name . " " : '')
        . "FOREIGN KEY (" . $column . ") REFERENCES " . $refTable . " (id) ON DELETE " . $onDelete . " ON UPDATE " . $onUpdate
    );
};

$notNullColumns = array(
    array('customers', 'ssn', "varchar(50) NOT NULL DEFAULT ''", "''"),
    array('customers', 'ten', "varchar(50) NOT NULL DEFAULT ''", "''"),
    array('divisions', 'ten', "varchar(50) NOT NULL DEFAULT ''", "''"),
    array('documents', 'ssn', "varchar(50) NOT NULL DEFAULT ''", "''"),
    array('documents', 'ten', "varchar(50) NOT NULL DEFAULT ''", "''"),
    array('events', 'begintime', "int(11) NOT NULL DEFAULT 0", "0"),
    array('events', 'endtime', "int(11) NOT NULL DEFAULT 0", "0"),
    array('invoicecontents', 'count', "decimal(9,3) NOT NULL DEFAULT 0", "0"),
    array('supermasters', 'ip', "varchar(64) NOT NULL", "''"),
    array('voip_prefix_groups', 'name', "text NOT NULL", "''"),
);

foreach ($notNullColumns as $notNullColumn) {
    [$table, $column, $definition, $value] = $notNullColumn;
    $columnInfo = $getColumn($table, $column);
    if (!empty($columnInfo) && $columnInfo['nullable'] == 'YES') {
        $this->Execute("UPDATE " . $table . " SET " . $column . " = " . $value . " WHERE " . $column . " IS NULL");
        $this->Execute("ALTER TABLE " . $table . " MODIFY " . $column . " " . $definition);
    }
}

$columnInfo = $getColumn('customerconsents', 'cdate');
if ($columnInfo['type'] == 'int(16)') {
    $this->Execute("ALTER TABLE customerconsents MODIFY cdate int(11) NOT NULL DEFAULT 0");
}

$columnInfo = $getColumn('ksefinvoices', 'pay_date');
if ($columnInfo['type'] == 'int(16)') {
    $this->Execute("ALTER TABLE ksefinvoices MODIFY pay_date int(11) DEFAULT NULL");
}

$columnInfo = $getColumn('liabilities', 'value');
if ($columnInfo['type'] == 'decimal(9,2)'
    && !$this->GetOne("SELECT 1 FROM liabilities WHERE value >= 1000000 OR value <= -1000000 LIMIT 1")) {
    $this->Execute("ALTER TABLE liabilities MODIFY value decimal(9,3) NOT NULL DEFAULT 0");
}

$voipRuleColumns = array(
    array('rule_group_id', 'voip_rule_groups'),
    array('prefix_group_id', 'voip_prefix_groups'),
);
foreach ($voipRuleColumns as $voipRuleColumn) {
    [$column, $refTable] = $voipRuleColumn;
    $columnInfo = $getColumn('voip_rules', $column);
    if ($columnInfo['nullable'] == 'YES' && !$this->GetOne("SELECT COUNT(*) FROM voip_rules WHERE " . $column . " IS NULL")) {
        $dropForeignKeys('voip_rules', $column);
        $this->Execute("ALTER TABLE voip_rules MODIFY " . $column . " int(11) NOT NULL");
    }
    $syncForeignKey('voip_rules', $column, $refTable, 'CASCADE', 'CASCADE');
}

$foreignKeys = array(
    array('assignments', 'customerid', 'customers', 'CASCADE', 'CASCADE', 'assignments_customerid_fkey'),
    array('assignments', 'numberplanid', 'numberplans', 'CASCADE', 'SET NULL', 'assignments_numberplanid'),
    array('assignments', 'recipient_address_id', 'addresses', 'CASCADE', 'SET NULL', 'assignments_recipient_address_id_fkey'),
    array('cashimport', 'customerid', 'customers', 'CASCADE', 'SET NULL', 'cashimport_customerid_fkey'),
    array('cashimport', 'sourceid', 'cashsources', 'CASCADE', 'SET NULL', 'cashimport_sourceid_fkey'),
    array('cashimport', 'sourcefileid', 'sourcefiles', 'CASCADE', 'SET NULL', 'cashimport_sourcefileid_fkey'),
    array('customer_addresses', 'customer_id', 'customers', 'CASCADE', 'CASCADE', 'customer_addresses_customer_id_fkey'),
    array('customerassignments', 'customergroupid', 'customergroups', 'CASCADE', 'CASCADE', 'customerassignments_customergroupid_fkey'),
    array('customerassignments', 'customerid', 'customers', 'CASCADE', 'CASCADE', 'customerassignments_customerid_fkey'),
    array('customercontacts', 'customerid', 'customers', 'CASCADE', 'CASCADE', 'customercontacts_customerid_fkey'),
    array('documents', 'recipient_address_id', 'addresses', 'CASCADE', 'SET NULL', 'documents_recipient_address_id_fkey'),
    array('events', 'address_id', 'addresses', 'CASCADE', 'SET NULL', 'events_address_id_fkey'),
    array('events', 'netdevid', 'netdevices', 'CASCADE', 'SET NULL', 'events_netdevices_fkey'),
    array('events', 'netnodeid', 'netnodes', 'CASCADE', 'SET NULL', 'events_netnodeid_fkey'),
    array('events', 'nodeid', 'nodes', 'CASCADE', 'SET NULL', 'events_nodeid_fkey'),
    array('events', 'ticketid', 'rttickets', 'CASCADE', 'SET NULL', 'events_ticketid_fkey'),
    array('netdevices', 'channelid', 'ewx_channels', 'CASCADE', 'SET NULL'),
    array('netdevices', 'netnodeid', 'netnodes', 'CASCADE', 'SET NULL'),
    array('netdevices', 'invprojectid', 'invprojects', 'CASCADE', 'SET NULL'),
    array('netdevices', 'ownerid', 'customers', 'CASCADE', 'SET NULL', 'netdevices_ownerid_fkey'),
    array('nodes', 'netid', 'networks', 'CASCADE', 'CASCADE'),
    array('nodes', 'invprojectid', 'invprojects', 'CASCADE', 'SET NULL'),
    array('rtmessages', 'ticketid', 'rttickets', 'CASCADE', 'CASCADE', 'rtmessages_ticketid_fkey'),
    array('rtrights', 'userid', 'users', 'CASCADE', 'CASCADE', 'rtrights_userid_fkey'),
    array('rttickets', 'queueid', 'rtqueues', 'CASCADE', 'CASCADE', 'rttickets_queueid_fkey'),
    array('tariffs', 'voip_tariff_rule_id', 'voip_rule_groups', 'CASCADE', 'SET NULL', 'tariffs_voip_tariff_rule_id_fkey'),
    array('uiconfig', 'configid', 'uiconfig', 'CASCADE', 'CASCADE', 'uiconfig_configid_fkey'),
    array('uiconfig', 'userid', 'users', 'CASCADE', 'CASCADE', 'uiconfig_userid_fkey'),
    array('voip_cdr', 'callervoipaccountid', 'voipaccounts', 'CASCADE', 'SET NULL'),
    array('voip_cdr', 'calleevoipaccountid', 'voipaccounts', 'CASCADE', 'SET NULL'),
    array('voip_prefixes', 'groupid', 'voip_prefix_groups', 'RESTRICT', 'CASCADE'),
    array('voipaccounts', 'address_id', 'addresses', 'CASCADE', 'SET NULL', 'voipaccounts_address_id_fkey'),
);

foreach ($foreignKeys as $foreignKey) {
    $syncForeignKey(...$foreignKey);
}

$indexRenames = array(
    array('assignments', 'recipient_address_id_fk2', 'assignments_recipient_address_id_fkey'),
    array('customercontacts', 'phone', 'contact'),
    array('documents', 'recipient_address_id_fk', 'documents_recipient_address_id_fkey'),
    array('events', 'event_ticketid_rttickets_id_fk', 'events_ticketid_fkey'),
    array('netdevicemodels', 'netdevicemodels_type_fkey', 'type'),
    array('netdevices', 'ownerid_fk', 'netdevices_ownerid_fkey'),
    array('pna', 'pna_fromnumber_idx', 'fromnumber'),
    array('pna', 'pna_tonumber_idx', 'tonumber'),
    array('promotionassignments', 'promotionassignments_ibfk_1', 'promotionschemaid'),
    array('tariffs', 'tariff_id_fk', 'voip_tariff_id'),
    array('uiconfig', 'userid', 'uiconfig_userid_fkey'),
    array('voip_number_assignments', 'voip_number_assignments_assignment_id_key', 'voip_number_assignments_assignment_id'),
    array('voip_numbers', 'vn_uniq_index', 'voip_account_id'),
    array('voip_numbers', 'voip_numbers_tariff_id_fkey', 'tariff_id'),
    array('voip_price_groups', 'group_id_fk', 'prefix_group_id'),
    array('voip_price_groups', 'price_tariffid', 'voip_tariff_id'),
    array('voip_rules', 'voip_rules_ibfk_3', 'rule_group_id'),
    array('voip_rules', 'voip_rules_ibfk_4', 'prefix_group_id'),
    array('voipaccounts', 'voipaccounts_address_id_fk', 'voipaccounts_address_id_fkey'),
);

foreach ($indexRenames as $indexRename) {
    $renameIndex(...$indexRename);
}

$index = $getIndex('pna', 'zip');
if (!empty($index) && !in_array('cityname', $index['columns'])) {
    $this->Execute("ALTER TABLE pna DROP INDEX zip, ADD UNIQUE INDEX zip (zip, cityid, cityname, streetid, streetname, fromnumber, fromletter, tonumber, toletter, parity)");
}

$index = $getIndex('voip_cdr', 'uniqueid');
if (!empty($index) && $index['unique'] && $index['columns'] == array('uniqueid')) {
    $this->Execute("ALTER TABLE voip_cdr DROP INDEX uniqueid");
}

if (!$getIndex('voip_pool_numbers', 'name')
    && !$this->GetOne("SELECT COUNT(*) FROM (SELECT name FROM voip_pool_numbers GROUP BY name HAVING COUNT(*) > 1) d")) {
    $this->Execute("ALTER TABLE voip_pool_numbers ADD UNIQUE INDEX name (name)");
}

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2026112701', 'dbversion'));

$this->CommitTrans();
