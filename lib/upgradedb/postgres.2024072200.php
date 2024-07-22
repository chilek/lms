<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2024 LMS Developers
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

if (!$this->ResourceExists('netnodes.flags', LMSDB::RESOURCE_TYPE_COLUMN)) {
    $this->Execute("ALTER TABLE netnodes ADD COLUMN flags smallint NOT NULL DEFAULT 0");
}

if (!$this->ResourceExists('netnodes.services', LMSDB::RESOURCE_TYPE_COLUMN)) {
    $this->Execute("ALTER TABLE netnodes ADD COLUMN services varchar(100) NOT NULL DEFAULT ''");
}

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2024072200', 'dbversion'));

$this->CommitTrans();
