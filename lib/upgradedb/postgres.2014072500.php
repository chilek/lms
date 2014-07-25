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

$DB->BeginTrans();

$DB->Execute("ALTER TABLE documents ADD fullnumber varchar(50) DEFAULT NULL");
$DB->Execute("CREATE INDEX documents_fullnumber_idx ON documents (fullnumber)");

$docs = $DB->GetAll('SELECT d.id, cdate, number, template FROM documents d
	JOIN numberplans n ON n.id = d.numberplanid
	WHERE numberplanid <> 0 ORDER BY id');
if (!empty($docs)) {
	include(LIB_DIR . '/common.php');
	foreach ($docs as $doc) {
		$fullnumber = docnumber($doc['number'], $doc['template'], $doc['cdate']);
		$DB->Execute('UPDATE documents SET fullnumber = ? WHERE id = ?',
			array($fullnumber, $doc['id']));
	}
}

$DB->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2014072500', 'dbversion'));

$DB->CommitTrans();

?>
