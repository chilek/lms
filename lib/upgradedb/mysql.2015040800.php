<?php

/*
 *  LMS version 1.11-git
 *
 *  Copyright (C) 2001-2015 LMS Developers
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

$this->Execute("ALTER TABLE netlinks ADD COLUMN srcradiosector int(11) DEFAULT NULL");
$this->Execute("ALTER TABLE netlinks ADD COLUMN dstradiosector int(11) DEFAULT NULL");
$this->Execute("ALTER TABLE netlinks ADD INDEX srcradiosector (srcradiosector)");
$this->Execute("ALTER TABLE netlinks ADD INDEX dstradiosector (dstradiosector)");
$this->Execute("ALTER TABLE netlinks ADD FOREIGN KEY (srcradiosector) REFERENCES netradiosectors (id) ON DELETE SET NULL ON UPDATE CASCADE");
$this->Execute("ALTER TABLE netlinks ADD FOREIGN KEY (dstradiosector) REFERENCES netradiosectors (id) ON DELETE SET NULL ON UPDATE CASCADE");

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2015040800', 'dbversion'));

$this->CommitTrans();
