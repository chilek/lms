<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2022 LMS Developers
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

$this->Execute("ALTER TABLE netradiosectors MODIFY COLUMN technology int(11) DEFAULT NULL");

$this->Execute("UPDATE netradiosectors SET technology = NULL WHERE technology = 0");

$this->Execute("ALTER TABLE netlinks MODIFY COLUMN type tinyint(1) DEFAULT NULL");
$this->Execute("ALTER TABLE netlinks MODIFY COLUMN speed int(11) DEFAULT NULL");
$this->Execute("ALTER TABLE netlinks MODIFY COLUMN technology int(11) DEFAULT NULL");

$this->Execute("UPDATE netlinks SET technology = NULL WHERE technology = 0");

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2022120702', 'dbversion'));

$this->CommitTrans();
