<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2025 LMS Developers
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

if (!$this->ResourceExists('divisions.divisions_shortname_label_ukey', LMSDB::RESOURCE_TYPE_CONSTRAINT)) {
    if ($this->ResourceExists('divisions.shortname', LMSDB::RESOURCE_TYPE_CONSTRAINT)) {
        $this->Execute("ALTER TABLE divisions DROP CONSTRAINT shortname");
    }

    $this->Execute("ALTER TABLE divisions ADD CONSTRAINT divisions_shortname_label_ukey UNIQUE (shortname, label)");
}

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2025102200', 'dbversion'));

$this->CommitTrans();
