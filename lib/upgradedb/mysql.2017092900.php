<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2017 LMS Developers
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

$this->Execute("ALTER TABLE assignments ADD COLUMN docid int(11) DEFAULT NULL");
$this->Execute("ALTER TABLE assignments ADD CONSTRAINT assignments_docid_fkey
	FOREIGN KEY (docid) REFERENCES documents (id) ON UPDATE CASCADE ON DELETE CASCADE");

$this->Execute("ALTER TABLE receiptcontents ALTER COLUMN docid DROP DEFAULT");
$this->Execute("DELETE FROM receiptcontents rc WHERE NOT EXISTS (SELECT null FROM documents d WHERE rc.docid = d.id)");
$this->Execute("ALTER TABLE receiptcontents ADD CONSTRAINT receiptcontents_docid_fkey
	FOREIGN KEY (docid) REFERENCES documents (id) ON DELETE CASCADE ON UPDATE CASCADE");

$this->Execute("ALTER TABLE invoicecontents ALTER COLUMN docid DROP DEFAULT");
$this->Execute("DELETE FROM invoicecontents ic WHERE NOT EXISTS (SELECT null FROM documents d WHERE ic.docid = d.id)");
$this->Execute("ALTER TABLE invoicecontents ADD CONSTRAINT invoicecontents_docid_fkey
	FOREIGN KEY (docid) REFERENCES documents (id) ON DELETE CASCADE ON UPDATE CASCADE");

$this->Execute("ALTER TABLE debitnotecontents ALTER COLUMN docid DROP DEFAULT");
$this->Execute("DELETE FROM debitnotecontents dc WHERE NOT EXISTS (SELECT null FROM documents d WHERE dc.docid = d.id)");
$this->Execute("ALTER TABLE debitnotecontents ADD CONSTRAINT debitnotecontents_docid_fkey
	FOREIGN KEY (docid) REFERENCES documents (id) ON DELETE CASCADE ON UPDATE CASCADE");

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2017092900', 'dbversion'));

$this->CommitTrans();

?>
