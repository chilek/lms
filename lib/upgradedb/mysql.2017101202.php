<?php

/**
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2017 LMS Developers
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

$this->Execute("ALTER TABLE customers MODIFY creatorid int(11) NULL");
$this->Execute("ALTER TABLE customers ALTER COLUMN creatorid SET DEFAULT NULL");
$this->Execute("ALTER TABLE customers MODIFY modid int(11) NULL");
$this->Execute("ALTER TABLE customers ALTER COLUMN modid SET DEFAULT NULL");
$this->Execute("ALTER TABLE nodes MODIFY creatorid int(11) NULL");
$this->Execute("ALTER TABLE nodes ALTER COLUMN creatorid SET DEFAULT NULL");
$this->Execute("ALTER TABLE nodes MODIFY modid int(11) NULL");
$this->Execute("ALTER TABLE nodes ALTER COLUMN modid SET DEFAULT NULL");
$this->Execute("ALTER TABLE voipaccounts MODIFY creatorid int(11) NULL");
$this->Execute("ALTER TABLE voipaccounts ALTER COLUMN creatorid SET DEFAULT NULL");
$this->Execute("ALTER TABLE voipaccounts MODIFY modid int(11) NULL");
$this->Execute("ALTER TABLE voipaccounts ALTER COLUMN modid SET DEFAULT NULL");
$this->Execute("ALTER TABLE rttickets MODIFY creatorid int(11) NULL");
$this->Execute("ALTER TABLE rttickets ALTER COLUMN creatorid SET DEFAULT NULL");

$userids = $this->GetCol("SELECT id FROM users");
if (!empty($userids)) {
    $sql_userids = implode(',', $userids);
    $this->Execute("UPDATE customers SET creatorid = NULL WHERE creatorid = 0 OR creatorid NOT IN (" . $sql_userids . ")");
    $this->Execute("UPDATE customers SET modid = NULL WHERE modid = 0 OR modid NOT IN (" . $sql_userids . ")");
    $this->Execute("UPDATE nodes SET creatorid = NULL WHERE creatorid = 0 OR creatorid NOT IN (" . $sql_userids . ")");
    $this->Execute("UPDATE nodes SET modid = NULL WHERE modid = 0 OR modid NOT IN (" . $sql_userids . ")");
    $this->Execute("UPDATE voipaccounts SET creatorid = NULL WHERE creatorid = 0 OR creatorid NOT IN (" . $sql_userids . ")");
    $this->Execute("UPDATE voipaccounts SET modid = NULL WHERE modid = 0 OR modid NOT IN (" . $sql_userids . ")");
    $this->Execute("UPDATE rttickets SET creatorid = NULL WHERE creatorid = 0 OR creatorid NOT IN (" . $sql_userids . ")");
}

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2017101202', 'dbversion'));

$this->CommitTrans();
