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

$this->BeginTrans();

$this->Execute("
    ALTER TABLE liabilities ADD COLUMN netflag smallint DEFAULT 0 NOT NULL;
    ALTER TABLE liabilities ADD COLUMN netvalue numeric(9,2) DEFAULT NULL;
    UPDATE liabilities SET netvalue = ROUND(value / ((SELECT taxes.value FROM taxes WHERE taxes.id = taxid) / 100 + 1), 2) WHERE netvalue IS NULL
");

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2021052400', 'dbversion'));

$this->CommitTrans();
