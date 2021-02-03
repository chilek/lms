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

$this->Execute("
    CREATE SEQUENCE \"customercontacts_id_seq\";
    CREATE TABLE customercontacts (
	    id integer DEFAULT nextval('customercontacts_id_seq'::text) NOT NULL,
	    customerid integer NOT NULL DEFAULT 0,
	    name varchar(255) NOT NULL DEFAULT '',
	    phone varchar(255) NOT NULL DEFAULT '',
	    PRIMARY KEY (id)
    );
    
    CREATE INDEX customercontacts_customerid_idx ON customercontacts (customerid);
    CREATE INDEX customercontacts_phone_idx ON customercontacts (phone);
");

if ($list = $this->GetAll('SELECT phone1, phone2, phone3, id FROM customers')) {
    foreach ($list as $row) {
        if (trim($row['phone1'])) {
            $this->Execute('INSERT INTO customercontacts (customerid, phone)
					VALUES(?, ?)', array($row['id'], $row['phone1']));
        }
        if (trim($row['phone2'])) {
            $this->Execute('INSERT INTO customercontacts (customerid, phone)
					VALUES(?, ?)', array($row['id'], $row['phone2']));
        }
        if (trim($row['phone3'])) {
            $this->Execute('INSERT INTO customercontacts (customerid, phone)
					VALUES(?, ?)', array($row['id'], $row['phone3']));
        }
    }
}

$this->Execute("
    ALTER TABLE customers DROP phone1;
    ALTER TABLE customers DROP phone2;
    ALTER TABLE customers DROP phone3;
");

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2007071100', 'dbversion'));

$this->CommitTrans();
