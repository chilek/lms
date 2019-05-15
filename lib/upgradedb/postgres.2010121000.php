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

$this->Execute("
    DROP VIEW customersview;

    ALTER TABLE customers ADD einvoice smallint DEFAULT NULL;
    ALTER TABLE customers ADD invoicenotice smallint DEFAULT NULL;
    ALTER TABLE customers ADD mailingnotice smallint DEFAULT NULL;

    CREATE VIEW customersview AS
        SELECT c.* FROM customers c
        WHERE NOT EXISTS (
            SELECT 1 FROM customerassignments a
            JOIN excludedgroups e ON (a.customergroupid = e.customergroupid)
            WHERE e.userid = lms_current_user() AND a.customerid = c.id);

    DELETE FROM assignments WHERE customerid NOT IN (SELECT id FROM customers);
    ALTER TABLE assignments ADD FOREIGN KEY (customerid)
        REFERENCES customers (id) ON DELETE CASCADE ON UPDATE CASCADE;
    ALTER TABLE assignments ALTER customerid DROP DEFAULT;

    DELETE FROM customerassignments WHERE customerid NOT IN (SELECT id FROM customers);
    ALTER TABLE customerassignments ADD FOREIGN KEY (customerid)
        REFERENCES customers (id) ON DELETE CASCADE ON UPDATE CASCADE;
    ALTER TABLE customerassignments ALTER customerid DROP DEFAULT;

    DELETE FROM customerassignments WHERE customergroupid NOT IN (SELECT id FROM customergroups);
    ALTER TABLE customerassignments ADD FOREIGN KEY (customergroupid)
        REFERENCES customergroups (id) ON DELETE CASCADE ON UPDATE CASCADE;
    ALTER TABLE customerassignments ALTER customergroupid DROP DEFAULT;

    CREATE INDEX customerassignments_customerid_idx ON customerassignments (customerid);

    DELETE FROM excludedgroups WHERE customergroupid NOT IN (SELECT id FROM customergroups);
    ALTER TABLE excludedgroups ADD FOREIGN KEY (customergroupid)
        REFERENCES customergroups (id) ON DELETE CASCADE ON UPDATE CASCADE;
    ALTER TABLE excludedgroups ALTER customergroupid DROP DEFAULT;

    CREATE INDEX excludedgroups_customergroupid_idx ON excludedgroups (customergroupid);
");

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2010121000', 'dbversion'));

$this->CommitTrans();
