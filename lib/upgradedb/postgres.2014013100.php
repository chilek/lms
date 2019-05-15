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

$this->BeginTrans();

if ($id = $this->GetOne("SELECT id FROM uiconfig WHERE section = ? AND var = ?", array('userpanel', 'default_queue'))) {
    $this->Execute("UPDATE uiconfig SET var = ? WHERE id = ?", array('queues', $id));
} else {
    $this->Execute(
        "INSERT INTO uiconfig (section, var, value) VALUES (?, ?, ?)",
        array('userpanel', 'queues', '1')
    );
}

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2014013100', 'dbversion'));

$this->CommitTrans();
