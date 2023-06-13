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

if (!$this->GetOne("SELECT id FROM netdevicetypes WHERE name = ?", array('fiber cassette'))) {
    $this->Execute(
        "INSERT INTO netdevicetypes (name, passive) VALUES (?, ?)",
        array(
            'fiber cassette',
            1,
        )
    );
}

if (!$this->GetOne("SELECT id FROM netdevicetypes WHERE name = ?", array('splice tray'))) {
    $this->Execute(
        "INSERT INTO netdevicetypes (name, passive) VALUES (?, ?)",
        array(
            'splice tray',
            1,
        )
    );
}

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2023030300', 'dbversion'));

$this->CommitTrans();
