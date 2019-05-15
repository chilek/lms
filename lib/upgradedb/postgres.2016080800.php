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

$this->Execute("ALTER TABLE voip_rules RENAME TO voip_rule_groups;
                ALTER SEQUENCE voip_rules_id_seq RENAME TO voip_rule_groups_id_seq;
                  
                ALTER TABLE voip_group_rule_assignments RENAME TO voip_rules;
                ALTER SEQUENCE voip_group_rule_assignments_id_seq RENAME TO voip_rules_id_seq;                   
                   
                ALTER TABLE voip_rules RENAME ruleid        TO rule_group_id;
                ALTER TABLE voip_rules RENAME groupid       TO prefix_group_id;
                ALTER TABLE voip_rules RENAME rule_settings TO settings;

                UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2016080800', 'dbversion'));

$this->CommitTrans();
