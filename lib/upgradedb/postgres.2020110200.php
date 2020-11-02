<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2020 LMS Developers
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
    ALTER TABLE promotionschemas ADD COLUMN deleted smallint DEFAULT 0 NOT NULL;
    ALTER TABLE promotionschemas ADD COLUMN length smallint DEFAULT NULL;
    ALTER TABLE assignments ADD COLUMN promotionschemaid integer DEFAULT NULL;
    ALTER TABLE assignments ADD CONSTRAINT assignments_promotionschemaid_fkey
        FOREIGN KEY (promotionschemaid) REFERENCES promotionschemas (id) ON DELETE RESTRICT ON UPDATE CASCADE
");

$schemas = $this->GetAll("SELECT id, data FROM promotionschemas WHERE data <> ''");
if (!empty($schemas)) {
    foreach ($schemas as $schema) {
        $data = explode(';', $schema['data']);
        $length = 0;
        foreach ($data as $period) {
            $length += intval($period);
        }
        $this->Execute(
            "UPDATE promotionschemas SET length = ? WHERE id = ?",
            array($length, $schema['id'])
        );
    }
}

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2020110200', 'dbversion'));

$this->CommitTrans();
