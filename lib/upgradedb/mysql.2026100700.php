<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2026 LMS Developers
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
    CREATE TABLE netnodecontacts (
        id int(11) NOT NULL AUTO_INCREMENT,
        netnodeid int(11) NOT NULL,
        contact varchar(255) NOT NULL DEFAULT '',
        name text NOT NULL,
        type int(11) DEFAULT NULL,
        PRIMARY KEY (id),
        INDEX netnodecontacts_netnodeid_idx (netnodeid),
        INDEX netnodecontacts_contact_idx (contact),
        CONSTRAINT netnodecontacts_netnodeid_fkey
            FOREIGN KEY (netnodeid) REFERENCES netnodes (id) ON DELETE CASCADE ON UPDATE CASCADE
    ) ENGINE=InnoDB
");

$this->Execute(
    "INSERT INTO netnodecontacts (netnodeid, contact, name)
    SELECT n.id, n.admcontact, ''
    FROM netnodes n
    WHERE n.admcontact IS NOT NULL
        AND TRIM(n.admcontact) <> ''"
);

$contacts = $this->GetAll('SELECT id, contact FROM netnodecontacts WHERE type IS NULL');
if (!empty($contacts)) {
    foreach ($contacts as $contact) {
        $type = check_email(trim($contact['contact'])) ? CONTACT_EMAIL : CONTACT_LANDLINE;
        $this->Execute(
            'UPDATE netnodecontacts SET type = ? WHERE id = ?',
            [$type, $contact['id']]
        );
    }
}

$this->Execute("ALTER TABLE netnodes DROP COLUMN admcontact");

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", ['2026100700', 'dbversion']);

$this->CommitTrans();
