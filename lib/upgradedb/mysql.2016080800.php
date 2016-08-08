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

$this->Execute("RENAME TABLE voip_rules TO voip_rule_groups;
     
                RENAME TABLE voip_group_rule_assignments TO voip_rules;
     
     			ALTER TABLE voip_rules
     			DROP FOREIGN KEY voip_rules_ibfk_1,
				CHANGE COLUMN ruleid rule_group_id INT(11) DEFAULT NULL,
				ADD CONSTRAINT voip_rules_ibfk_3 FOREIGN KEY (rule_group_id) REFERENCES voip_rule_groups(id);
				
				ALTER TABLE voip_rules
				DROP FOREIGN KEY voip_rules_ibfk_2,
				CHANGE COLUMN groupid prefix_group_id INT(11) DEFAULT NULL,
				ADD CONSTRAINT voip_rules_ibfk_4 FOREIGN KEY (prefix_group_id) REFERENCES voip_prefix_groups(id);
     
                ALTER TABLE voip_rules CHANGE rule_settings settings text NULL;
                      
                UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2016080800', 'dbversion'));
                
$this->CommitTrans();

?>
