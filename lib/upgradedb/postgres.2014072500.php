<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2015 LMS Developers
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

require_once(LIB_DIR . DIRECTORY_SEPARATOR . 'language.php');
require_once(LIB_DIR . DIRECTORY_SEPARATOR . 'definitions.php');

$numberplans = $this->GetAllByKey("SELECT * FROM numberplans ORDER BY id", 'id');

$this->BeginTrans();

$this->Execute("ALTER TABLE documents ADD fullnumber varchar(50) DEFAULT NULL");
$this->Execute("CREATE INDEX documents_fullnumber_idx ON documents (fullnumber)");

$this->LockTables("documents");

$offset = 0;
do {
    $docs = $this->GetAll("SELECT id, cdate, number, numberplanid FROM documents
		WHERE fullnumber IS NULL
		ORDER BY id LIMIT 30000 OFFSET $offset");
    $stop = empty($docs);
    if (!$stop) {
        foreach ($docs as $doc) {
            if ($doc['numberplanid']) {
                $template = $numberplans[$doc['numberplanid']]['template'];
            } else {
                $template = DEFAULT_NUMBER_TEMPLATE;
            }
            $fullnumber = docnumber(array(
                'number' => $doc['number'],
                'template' => $template,
                'cdate' => $doc['cdate'],
            ));
            $this->Execute(
                "UPDATE documents SET fullnumber = ? WHERE id = ?",
                array($fullnumber, $doc['id'])
            );
        }
        $offset += count($docs);
        unset($docs);
    }
} while (!$stop);

$this->UnLockTables("documents");

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2014072500', 'dbversion'));

$this->CommitTrans();
