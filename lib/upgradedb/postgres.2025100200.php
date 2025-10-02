<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2025 LMS Developers
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

const CCONSENT_BALANCE_ON_DOCUMENTS_2025100200 = 33;
if (!$this->GetOne('SELECT COUNT(*) FROM customerconsents WHERE type = ?', array(CCONSENT_BALANCE_ON_DOCUMENTS_2025100200))) {
    $this->Execute(
        'INSERT INTO customerconsents
        (customerid, cdate, type)
        (SELECT id, 0, ? FROM customers)',
        array(
            CCONSENT_BALANCE_ON_DOCUMENTS_2025100200,
        )
    );
}

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2025100200', 'dbversion'));

$this->CommitTrans();
