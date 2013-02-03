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

$s_arr = array();
$schemas = $DB->GetAll("SELECT * FROM promotionschemas");
if ($schemas) foreach ($schemas as $schema) {
    $data = explode(';', $schema['data']);
    $cnt  = count($data);

    if ($data[$cnt-1] == 0) {
        $s_arr[] = $schema['id'];
        array_pop($data);
        $data = implode(';', $data);
        $DB->Execute("UPDATE promotionschemas SET data = ? WHERE id = ?",
            array($data, $schema['id']));
    }
}

if (!empty($s_arr)) {
    $schemas = $DB->GetAll("SELECT * FROM promotionassignments
        WHERE promotionschemaid IN (".implode(',', $s_arr).")");
    if ($schemas) foreach ($schemas as $schema) {
        $data = explode(';', $schema['data']);
        $cnt  = count($data);

        array_pop($data);
        $data = implode(';', $data);
        $DB->Execute("UPDATE promotionassignments SET data = ? WHERE id = ?",
            array($data, $schema['id']));
    }
}

$DB->Execute("ALTER TABLE promotionschemas ADD ctariffid integer DEFAULT NULL
    REFERENCES tariffs (id) ON DELETE RESTRICT ON UPDATE CASCADE");
$DB->Execute("CREATE INDEX promotionschemas_ctariffid_idx ON promotionschemas (ctariffid)");
$DB->Execute("ALTER TABLE promotionschemas ADD continuation smallint DEFAULT NULL");
$DB->Execute("UPDATE promotionschemas SET continuation = 1");

$DB->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2011030700', 'dbversion'));

$DB->CommitTrans();

?>
