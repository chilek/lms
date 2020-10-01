<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2020 LMS Developers
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

if ($this->ResourceExists('uiconfig_configid_fkey', LMSDB::RESOURCE_TYPE_CONSTRAINT)) {
    $this->Execute("ALTER TABLE uiconfig DROP CONSTRAINT uiconfig_configid_fkey");
}
$this->Execute("ALTER TABLE uiconfig ADD CONSTRAINT uiconfig_configid_fkey
            FOREIGN KEY (configid) REFERENCES uiconfig (id) ON DELETE CASCADE ON UPDATE CASCADE");

$this->Execute("
	ALTER TABLE uiconfig ADD COLUMN divisionid integer DEFAULT NULL;
    ALTER TABLE uiconfig ADD CONSTRAINT uiconfig_divisionid_fkey FOREIGN KEY (divisionid) REFERENCES divisions (id) ON DELETE CASCADE ON UPDATE CASCADE;
");

if ($this->ResourceExists('uiconfig_section_key', LMSDB::RESOURCE_TYPE_CONSTRAINT)) {
    $this->Execute("ALTER TABLE uiconfig DROP CONSTRAINT uiconfig_section_key");
}
$this->Execute("ALTER TABLE uiconfig ADD CONSTRAINT uiconfig_section_var_userid_divisionid_ukey UNIQUE (section, var, userid, divisionid)");

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2020090200', 'dbversion'));

$this->CommitTrans();
