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

if (!$this->GetOne(
    "SELECT COUNT(*) FROM information_schema.table_constraints
    WHERE table_name = ? AND constraint_type = ? AND constraint_schema = current_schema()",
    array('dbinfo', 'PRIMARY KEY')
)) {
    $this->Execute("DELETE FROM dbinfo a USING dbinfo b WHERE a.ctid < b.ctid AND a.keytype = b.keytype");
    $this->Execute("ALTER TABLE dbinfo ADD PRIMARY KEY (keytype)");
}

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2026111801', 'dbversion'));

$this->CommitTrans();
