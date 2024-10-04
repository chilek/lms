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

if (!$this->ResourceExists('messages.startdate', LMSDB::RESOURCE_TYPE_COLUMN)) {
    $this->Execute("ALTER TABLE messages ADD COLUMN startdate bigint DEFAULT NULL");
    $this->Execute("CREATE INDEX messages_startdate_idx ON messages (startdate)");
}

if (!$this->ResourceExists('messageitems.attributes', LMSDB::RESOURCE_TYPE_COLUMN)) {
    $this->Execute("ALTER TABLE messageitems ADD COLUMN attributes text DEFAULT NULL");
}

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2024100400', 'dbversion'));

$this->CommitTrans();
