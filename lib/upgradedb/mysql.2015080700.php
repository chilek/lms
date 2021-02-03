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

// Contact types
$CONTACT_MOBILE = 1;
$CONTACT_FAX = 2;
$CONTACT_LANDLINE = 4;
$CONTACT_EMAIL = 8;

$this->BeginTrans();

$this->Execute("ALTER TABLE customercontacts CHANGE phone contact varchar(255) NOT NULL DEFAULT ''");
$this->Execute(
    "UPDATE customercontacts SET type = type | ? WHERE type = 0 OR type = ?",
    array($CONTACT_LANDLINE, $CONTACT_FAX)
);
$this->Execute(
    "UPDATE customercontacts SET type = ? WHERE type IS NULL",
    array($CONTACT_LANDLINE)
);

$customers = $this->GetAll("SELECT id, email FROM customers WHERE email <> ''");
if (!empty($customers)) {
    $records = array();
    foreach ($customers as $customer) {
        $records[] = sprintf('(%d, \'%s\', %d)', $customer['id'], $customer['email'], $CONTACT_EMAIL);
    }
    if (!empty($records)) {
        $this->Execute("INSERT INTO customercontacts (customerid, contact, type) VALUES " . implode(',', $records));
    }
}

$this->Execute("DROP VIEW IF EXISTS customersview");
$this->Execute("DROP VIEW IF EXISTS contractorview");
$this->Execute("ALTER TABLE customers DROP COLUMN email");
$this->Execute("CREATE VIEW customersview AS
	SELECT c.* FROM customers c
	WHERE NOT EXISTS (
		SELECT 1 FROM customerassignments a
		JOIN excludedgroups e ON (a.customergroupid = e.customergroupid)
		WHERE e.userid = lms_current_user() AND a.customerid = c.id)
	AND c.type < ?", array(2));
$this->Execute("CREATE VIEW contractorview AS
	SELECT c.* FROM customers c
	WHERE c.type = ?", array(2));

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2015080700', 'dbversion'));

$this->CommitTrans();
