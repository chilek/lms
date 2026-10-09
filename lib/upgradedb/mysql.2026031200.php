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

if (!$this->ResourceExists('ksefdocuments.permanent_storage_date', LMSDB::RESOURCE_TYPE_COLUMN)) {
    $this->Execute("ALTER TABLE ksefdocuments ADD COLUMN permanent_storage_date datetime(6) DEFAULT NULL");

    $ksefDocuments = $this->GetAll(
        "SELECT
            kd.id AS ksefdocid,
            kbs.lastupdate
        FROM ksefdocuments kd
        JOIN ksefbatchsessions kbs ON kbs.id = kd.batchsessionid
        WHERE kd.status = ?
            AND kd.permanent_storage_date IS NULL",
        [
            200,
        ]
    );

    if (!empty($ksefDocuments)) {
        foreach ($ksefDocuments as $ksefDocument) {
            $this->Execute(
                "UPDATE ksefdocuments
                SET permanent_storage_date = ?
                WHERE id = ?",
                [
                    date('c', $ksefDocument['lastupdate']),
                    $ksefDocument['ksefdocid']
                ]
            );
        }
    }
}

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2026031200', 'dbversion'));

$this->CommitTrans();
