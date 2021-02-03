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

$this->Execute("ALTER TABLE documents ADD div_shortname TEXT NOT NULL DEFAULT ''");

$dl = $this->GetAll("SELECT id, shortname FROM divisions");

if (!empty($dl)) {
    foreach ($dl as $division) {
        $this->Execute("UPDATE documents SET div_shortname = ?
				WHERE divisionid = ?", array(
            ($division['shortname'] ? $division['shortname'] : ''),
            $division['id']));
    }
}

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2014040100', 'dbversion'));
$this->CommitTrans();
