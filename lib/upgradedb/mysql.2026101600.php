<?php
/*
 * LMS version 1.11-git
 *
 * (C) Copyright 2001-2026 LMS Developers
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License Version 2 as
 * published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307,
 * USA.
 */

$this->BeginTrans();

if (!$this->ResourceExists('nodesessions.nassessionid', LMSDB::RESOURCE_TYPE_COLUMN)) {
    $this->Execute('ALTER TABLE nodesessions ADD COLUMN nassessionid varchar(253) DEFAULT NULL');
}

if (!$this->ResourceExists('nodesessions_nassessionid_idx', LMSDB::RESOURCE_TYPE_INDEX)) {
    $this->Execute('CREATE INDEX nodesessions_nassessionid_idx ON nodesessions (nassessionid)');
}

if (!$this->ResourceExists('nodesessions_start_idx', LMSDB::RESOURCE_TYPE_INDEX)) {
    $this->Execute('CREATE INDEX nodesessions_start_idx ON nodesessions (start)');
}

if (!$this->ResourceExists('nodesessions_stop_idx', LMSDB::RESOURCE_TYPE_INDEX)) {
    $this->Execute('CREATE INDEX nodesessions_stop_idx ON nodesessions (stop)');
}

$this->Execute('UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?', array('2026101600', 'dbversion'));

$this->CommitTrans();
