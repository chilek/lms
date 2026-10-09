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

if (!$this->ResourceExists('invoicecontents_tariffid_idx', LMSDB::RESOURCE_TYPE_CONSTRAINT)) {
    $this->Execute("CREATE INDEX invoicecontents_tariffid_idx ON invoicecontents (tariffid)");
}

if (!$this->ResourceExists('invoicecontents_docid_itemid_idx', LMSDB::RESOURCE_TYPE_CONSTRAINT)) {
    $this->Execute("CREATE INDEX invoicecontents_docid_itemid_idx ON invoicecontents(docid, itemid)");
}

if (!$this->ResourceExists('invoicecontents_docid_itemid_idx', LMSDB::RESOURCE_TYPE_CONSTRAINT)) {
    $this->Execute("CREATE INDEX cash_docid_itemid_idx ON cash (docid, itemid)");
}

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2026032800', 'dbversion'));

$this->CommitTrans();
