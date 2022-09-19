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

$this->Execute("ALTER TABLE events ADD COLUMN netnodeid integer DEFAULT NULL");
$this->Execute("ALTER TABLE events ADD CONSTRAINT event_netnodeid_fkey
    FOREIGN KEY (netnodeid) REFERENCES netnodes (id) ON DELETE SET NULL ON UPDATE CASCADE");

$this->Execute("ALTER TABLE events ADD COLUMN netdevid integer DEFAULT NULL");
$this->Execute("ALTER TABLE events ADD CONSTRAINT event_netdevid_fkey
    FOREIGN KEY (netdevid) REFERENCES netdevices (id) ON DELETE SET NULL ON UPDATE CASCADE");

$this->Execute("CREATE INDEX events_netnodeid_idx ON events (netnodeid)");
$this->Execute("CREATE INDEX events_netdevid_idx ON events (netdevid)");

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2022092000', 'dbversion'));

$this->CommitTrans();
