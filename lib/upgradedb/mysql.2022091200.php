<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2022 LMS Developers
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

$default_stateid = $this->GetOne("SELECT value FROM uiconfig WHERE section = ? AND var = ?", array('phpui', 'default_stateid'));

if (!empty($default_stateid)) {
    $default_state = $this->GetOne("SELECT name FROM states WHERE id = ?", array($default_stateid));
    if (!empty($default_state)) {
        $this->Execute(
            "UPDATE uiconfig SET var = ?, value = ? WHERE section = ? AND var = ?",
            array(
                'default_billing_address_state',
                mb_strtolower($default_state),
                'phpui',
                'default_stateid',
            )
        );
    }
}

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2022091200', 'dbversion'));

$this->CommitTrans();
