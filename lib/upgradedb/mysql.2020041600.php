<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2019 LMS Developers
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

if (!$this->ResourceExists('cash_importid_ukey', LMSDB::RESOURCE_TYPE_CONSTRAINT)) {
    $cash_import_duplicates = $this->GetAll(
        "SELECT id, importid FROM cash
        WHERE importid IN (
            SELECT importid FROM cash WHERE importid IS NOT NULL GROUP BY importid HAVING COUNT(*) > 1
        )"
    );
    if (!empty($cash_import_duplicates)) {
        $prev_importid = null;
        foreach ($cash_import_duplicates as $cash_import) {
            if ($prev_importid != $cash_import['importid']) {
                $prev_importid = $cash_import['importid'];
                continue;
            }
            $this->Execute("DELETE FROM cash WHERE id = ?", array($cash_import['id']));
        }
    }

    $this->Execute("ALTER TABLE cash ADD UNIQUE KEY cash_importid_ukey (importid)");
}

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2020041600', 'dbversion'));

$this->CommitTrans();
