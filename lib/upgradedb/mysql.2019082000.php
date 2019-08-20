<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2019 LMS Developers
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

$this->Execute("ALTER TABLE countries ADD COLUMN ccode varchar(5)");

$this->Execute("UPDATE countries SET ccode = ? WHERE name = ?", array('pl_PL', 'Poland'));
$this->Execute("UPDATE countries SET ccode = ? WHERE name = ?", array('lt_LT', 'Lithuania'));
$this->Execute("UPDATE countries SET ccode = ? WHERE name = ?", array('ro_RO', 'Romania'));
$this->Execute("UPDATE countries SET ccode = ? WHERE name = ?", array('sk_SK', 'Slovakia'));
$this->Execute("UPDATE countries SET ccode = ? WHERE name = ?", array('en_US', 'USA'));
$this->Execute("INSERT INTO countries (name, ccode) VALUES (?, ?)", array('Czech', 'cs_CZ'));

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2019082000', 'dbversion'));

$this->CommitTrans();
