<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2013 LMS Developers
 *
 *  Please, see the doc/AUTHORS for more information about authors!
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
 *  $Id$
 */

$this->BeginTrans();

$lastupgrade = $this->GetOne("SELECT keyvalue FROM dbinfo where keytype='dbversion'");

// we have 2005092900 (1.7.3) database - it was wrong upgrade
// so we need something do in other way
if ($lastupgrade == '2005092900') {
    $this->Execute("ALTER TABLE cash ADD type smallint DEFAULT '0' NOT NULL");
    // set type for network operations
    $this->Execute("UPDATE cash SET type = 1 WHERE customerid = 0");
} else {
    $this->Execute("UPDATE cash SET value = -value WHERE type = 2 OR type = 4");
    $this->Execute("UPDATE cash SET customerid = 0 WHERE type = 1 OR type = 2");
    $this->Execute("UPDATE cash SET type = 1 WHERE type < 4");
    $this->Execute("UPDATE cash SET type = 0 WHERE type != 1"); // "type!=1" <-> "type=4"
}

// "type" values after change:
// 1 - cash operations
// 0 - non-cash operations

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2005101700', 'dbversion'));

$this->CommitTrans();
