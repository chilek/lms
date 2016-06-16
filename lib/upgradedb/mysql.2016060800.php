<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2016 LMS Developers
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

$this->Execute("ALTER TABLE `voip_cdr` CHANGE `status` `status` smallint NOT NULL;");

$this->Execute("ALTER TABLE `voip_cdr` CHANGE `type` `type` smallint NOT NULL;");

$this->Execute("ALTER TABLE `voip_cdr` ADD COLUMN calleevoipaccountid int(11) NULL");

$this->Execute("ALTER TABLE `voip_cdr` ADD COLUMN caller_flags smallint NOT NULL DEFAULT 0");

$this->Execute("ALTER TABLE `voip_cdr` ADD COLUMN callee_flags smallint NOT NULL DEFAULT 0");

$this->Execute("ALTER TABLE `voip_cdr` ADD COLUMN caller_prefix_group varchar(30) NULL");

$this->Execute("ALTER TABLE `voip_cdr` ADD COLUMN callee_prefix_group varchar(30) NULL");

$this->Execute("ALTER TABLE `voip_cdr` ADD COLUMN uniqueid varchar(20) NOT NULL");

$this->Execute("ALTER TABLE `voip_cdr` CHANGE `voipaccountid` `callervoipaccountid` INT(11) NULL");

$this->Execute("ALTER TABLE `voipaccounts` ADD COLUMN flags smallint NOT NULL DEFAULT 0");

$this->Execute("ALTER TABLE `voip_tariffs` ADD COLUMN price decimal(12,5) NULL DEFAULT 0");

$this->Execute("ALTER TABLE `voip_tariffs` ADD COLUMN unitsize smallint NULL DEFAULT 0");

$this->Execute("ALTER TABLE `voip_prefixes` ADD groupid int(11) NOT NULL, ADD CONSTRAINT FOREIGN KEY(groupid) REFERENCES voip_prefix_groups(id)");

$this->Execute("DROP TABLE `voip_prefix_group_assignments`");

$this->Execute("ALTER TABLE `voip_prefixes` DROP COLUMN name");

$this->Execute("ALTER TABLE `voip_prefixes` DROP COLUMN description");

$this->Execute("ALTER TABLE `voip_tariffs` DROP COLUMN prefixid");

$this->Execute("ALTER TABLE `voip_tariff_rules` DROP COLUMN prefixid");

$this->Execute("ALTER TABLE `voip_tariffs` CHANGE `groupid` `groupid` int(11) NOT NULL");

$this->Execute("ALTER TABLE `voip_tariff_rules` CHANGE `groupid` `groupid` int(11) NOT NULL");

$this->Execute("ALTER TABLE `voip_tariff_rules` ADD COLUMN rule_settings text NULL");

$this->Execute("ALTER TABLE `voip_tariffs` CHANGE `price` `price` int(11) NOT NULL");

$this->Execute("ALTER TABLE `voip_tariffs` CHANGE `price` `price` smallint NOT NULL");

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2016060800', 'dbversion'));

$this->CommitTrans();

?>
