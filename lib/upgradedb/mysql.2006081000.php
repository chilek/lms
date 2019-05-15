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

// REGON (Business Registration Number)
$this->Execute("ALTER TABLE customers ADD COLUMN regon varchar(255) NOT NULL DEFAULT ''");
// KRS/EDG (Register of Business Entities)
$this->Execute("ALTER TABLE customers ADD COLUMN rbe varchar(255) NOT NULL DEFAULT ''");
// Dowod osobisty (Identity Card Number)
$this->Execute("ALTER TABLE customers ADD COLUMN icn varchar(255) NOT NULL DEFAULT ''");

// Node location
$this->Execute("ALTER TABLE nodes ADD COLUMN location text NOT NULL DEFAULT ''");

// Account names (logins) will be unique only in one domain context
$this->Execute("ALTER TABLE passwd DROP KEY login");
$this->Execute("ALTER TABLE passwd ADD UNIQUE KEY (login, domainid)");

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2006081000', 'dbversion'));

$this->CommitTrans();
