<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2018 LMS Developers
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

if ($this->ResourceExists('rttickets_address_id_fk', LMSDB::RESOURCE_TYPE_CONSTRAINT)) {
    $this->Execute("ALTER TABLE rttickets DROP FOREIGN KEY rttickets_address_id_fk");
} else {
    $this->Execute("ALTER TABLE rttickets DROP FOREIGN KEY rttickets_address_id_fkey");
}

if ($this->ResourceExists('rttickets_nodeid_fk', LMSDB::RESOURCE_TYPE_CONSTRAINT)) {
    $this->Execute("ALTER TABLE rttickets DROP FOREIGN KEY rttickets_nodeid_fk");
} else {
    $this->Execute("ALTER TABLE rttickets DROP FOREIGN KEY rttickets_nodeid_fkey");
}

$this->Execute("ALTER TABLE rttickets ADD CONSTRAINT rttickets_address_id_fkey
	FOREIGN KEY (address_id) REFERENCES addresses (id) ON DELETE SET NULL ON UPDATE CASCADE");
$this->Execute("ALTER TABLE rttickets ADD CONSTRAINT rttickets_nodeid_fkey
	FOREIGN KEY (nodeid) REFERENCES nodes (id) ON DELETE SET NULL ON UPDATE CASCADE");

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2018062000', 'dbversion'));

$this->CommitTrans();
