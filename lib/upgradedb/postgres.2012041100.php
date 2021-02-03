<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2013 LMS Developers
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

// Najpierw kasujemy widok bo korzysta on z kolumny PIN, której chcemy zmienić typ
$this->Execute("DROP VIEW customersview");

$this->Execute("ALTER TABLE customers ALTER COLUMN pin TYPE varchar(6)");

$this->Execute("CREATE VIEW customersview AS
		SELECT c.* FROM customers c
    		WHERE NOT EXISTS (
			SELECT 1 FROM customerassignments a
			JOIN excludedgroups e ON (a.customergroupid = e.customergroupid)
			WHERE e.userid = lms_current_user() AND a.customerid = c.id)");

$this->Execute("UPDATE customers SET pin = '0' || pin WHERE LENGTH(pin) < 4");

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2012041100', 'dbversion'));

$this->CommitTrans();
