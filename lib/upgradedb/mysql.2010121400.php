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

$DB->BeginTrans();

$DB->Execute("DELETE FROM customercontacts WHERE customerid NOT IN (SELECT id FROM customers)");
$DB->Execute("ALTER TABLE customercontacts ADD FOREIGN KEY (customerid)
       REFERENCES customers (id) ON DELETE CASCADE ON UPDATE CASCADE");
$DB->Execute("ALTER TABLE customercontacts ALTER customerid DROP DEFAULT");

$DB->Execute("DELETE FROM imessengers WHERE customerid NOT IN (SELECT id FROM customers)");
$DB->Execute("ALTER TABLE imessengers ADD FOREIGN KEY (customerid)
        REFERENCES customers (id) ON DELETE CASCADE ON UPDATE CASCADE");
$DB->Execute("ALTER TABLE imessengers ALTER customerid DROP DEFAULT");

$DB->Execute("ALTER TABLE customercontacts ADD type smallint DEFAULT NULL");
$DB->Execute("UPDATE customercontacts SET type = 2 WHERE name LIKE '%fax%'");

$lang = ConfigHelper::getConfig('phpui.lang');

if ($lang == 'pl') {
    $DB->Execute("UPDATE customercontacts SET type = COALESCE(type, 0) + 1
        WHERE REPLACE(REPLACE(phone, '-', ''), ' ', '') REGEXP '^(\\\\+?[0-9]{2}|0)?(88[0-9]|5[01][0-9]|6[069][0-9]|7[2789][0-9])[0-9]{6}$'");
}

$DB->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2010121400', 'dbversion'));

$DB->CommitTrans();

?>
