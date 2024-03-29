<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2016 LMS Developers
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

define('CONFIG_TYPE_NONE_2016041900', 7);

$this->BeginTrans();

$this->Execute(
    "INSERT INTO uiconfig (section, var, value, type) VALUES('phpui', 'event_usergroup_selection_type', 'update', ?)",
    array(CONFIG_TYPE_NONE_2016041900)
);
$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2016041900', 'dbversion'));

$this->CommitTrans();
