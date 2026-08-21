<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2021 LMS Developers
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

define('DEFAULT_NUMBER_TEMPLATE_2021030900', '%N/LMS/%Y');

$numberplans = $this->GetAllByKey("SELECT * FROM numberplans ORDER BY id", 'id');


$this->LockTables("documents");

do {
    $docs = $this->GetAll(
        "SELECT id, customerid, cdate, number, numberplanid
        FROM documents
        WHERE fullnumber IS NULL
        ORDER BY id LIMIT 30000"
    );
    $stop = empty($docs);
    if (!$stop) {
        foreach ($docs as $doc) {
            if ($doc['numberplanid']) {
                $template = $numberplans[$doc['numberplanid']]['template'];
            } else {
                $template = DEFAULT_NUMBER_TEMPLATE_2021030900;
            }
            $fullnumber = docnumber(array(
                'number' => $doc['number'],
                'template' => $template,
                'cdate' => $doc['cdate'],
                'customerid' => $doc['customerid'],
            ));
            $this->Execute(
                "UPDATE documents SET fullnumber = ? WHERE id = ?",
                array($fullnumber, $doc['id'])
            );
        }
        unset($docs);
    }
} while (!$stop);

$this->UnLockTables("documents");
