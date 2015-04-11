<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2014 LMS Developers
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

$numberplans = $DB->GetAllByKey("SELECT * FROM numberplans ORDER BY id", 'id');

$DB->BeginTrans();
$DB->LockTables("documents");

$DB->Execute("ALTER TABLE documents ADD fullnumber varchar(50) DEFAULT NULL");
$DB->Execute("ALTER TABLE documents ADD INDEX (fullnumber)");

include(LIB_DIR . DIRECTORY_SEPARATOR . 'common.php');

$offset = 0;
do {
	$docs = $DB->GetAll("SELECT id, cdate, number, numberplanid FROM documents
		WHERE numberplanid <> 0 ORDER BY id LIMIT 50000 OFFSET $offset");
	if (!empty($docs)) {
		foreach ($docs as $doc) {
			$fullnumber = docnumber($doc['number'], $numberplans[$doc['numberplanid']]['template'], $doc['cdate']);
			$DB->Execute("UPDATE documents SET fullnumber = ? WHERE id = ?",
				array($fullnumber, $doc['id']));
		}
		$offset += count($docs);
	}
	unset($docs);
} while (!empty($docs));


$DB->UnLockTables("documents");
$DB->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2014072500', 'dbversion'));
$DB->CommitTrans();

?>

