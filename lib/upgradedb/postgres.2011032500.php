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

$this->BeginTrans();

if (!$this->ResourceExists('documents.sdate', LMSDB::RESOURCE_TYPE_COLUMN)) {
    $this->Execute("ALTER TABLE documents ADD sdate integer DEFAULT 0 NOT NULL");
}

$this->Execute("UPDATE documents SET sdate = cdate WHERE type IN (1, 3)"); // DOC_INVOICE/DOC_CNOTE

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2011032500', 'dbversion'));

$this->CommitTrans();
