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

define('CONTACT_EMAIL', 8);

$this->BeginTrans();

$this->Execute("DROP VIEW customermailsview");
$this->Execute("CREATE VIEW customermailsview AS
		SELECT customerid, array_to_string(array_agg(contact), ',') AS email
			FROM customercontacts
			WHERE type = ? AND contact <> ''
			GROUP BY customerid", array(CONTACT_EMAIL));
$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2016040700', 'dbversion'));

$this->CommitTrans();
