<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2023 LMS Developers
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

if (!$this->ResourceExists('domains.options', LMSDB::RESOURCE_TYPE_COLUMN)) {
    $this->Execute("ALTER TABLE domains ALTER COLUMN type TYPE text");
    $this->Execute("ALTER TABLE domains ADD COLUMN options TEXT DEFAULT NULL");
    $this->Execute("ALTER TABLE domains ADD COLUMN catalog TEXT DEFAULT NULL");
    $this->Execute("CREATE INDEX domains_catalog_idx ON domains (catalog)");
}

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2023011100', 'dbversion'));

$this->CommitTrans();
