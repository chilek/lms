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

$lang = $this->GetOne("SELECT value FROM uiconfig WHERE section = ? AND var = ? AND disabled = 0", array('phpui', 'lang', 0));

$this->BeginTrans();

$this->Execute("
    DELETE FROM customercontacts WHERE customerid NOT IN (SELECT id FROM customers);
    ALTER TABLE customercontacts ADD FOREIGN KEY (customerid)
        REFERENCES customers (id) ON DELETE CASCADE ON UPDATE CASCADE;
    ALTER TABLE customercontacts ALTER customerid DROP DEFAULT;

    DELETE FROM imessengers WHERE customerid NOT IN (SELECT id FROM customers);
    ALTER TABLE imessengers ADD FOREIGN KEY (customerid)
        REFERENCES customers (id) ON DELETE CASCADE ON UPDATE CASCADE;
    ALTER TABLE imessengers ALTER customerid DROP DEFAULT;

    ALTER TABLE customercontacts ADD type smallint DEFAULT NULL;
    UPDATE customercontacts SET type = 2 WHERE name ILIKE '%fax%';
");

if ($lang == 'pl') {
    $this->Execute("UPDATE customercontacts SET type = COALESCE(type, 0) + 1
        WHERE regexp_replace(phone, '[^0-9]', '', 'g') ~ '^(\\\\+?[0-9]{2}|0|)(88[0-9]|5[01][0-9]|6[069][0-9]|7[2789][0-9])[0-9]{6}$'");
}

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2010121400', 'dbversion'));

$this->CommitTrans();
