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

function splitNameToFirstAndLastName($users_before)
{
    if (!$users_before) {
        return array();
    }

    foreach ($users_before as $id => $user) {
        $parts = explode(' ', $user['name']);
        $lastname = array_pop($parts);
        $firstname = implode(' ', $parts);
        $users[$id]['id'] = $user['id'];
        $users[$id]['firstname'] = $firstname;
        $users[$id]['lastname'] = $lastname;
    }
    return $users;
}

$this->BeginTrans();

$this->Execute("ALTER TABLE users ADD COLUMN firstname varchar(64) NOT NULL DEFAULT ''");
$this->Execute("ALTER TABLE users ADD COLUMN lastname varchar(64) NOT NULL DEFAULT ''");

$users_before = $this->GetAll('SELECT id, name FROM users');
$users = splitNameToFirstAndLastName($users_before);

if ($users) {
    foreach ($users as $user) {
        $this->Execute("UPDATE users SET firstname=?, lastname=? WHERE id = ?", array($user['firstname'], $user['lastname'], $user['id']));
    }
}

$this->Execute("ALTER TABLE users DROP COLUMN name");

$this->Execute("CREATE VIEW vusers AS SELECT *, " . $this->Concat('firstname', "' '", 'lastname') . " AS name, " . $this->Concat('lastname', "' '", 'firstname') . " AS rname FROM users");

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2016102500', 'dbversion'));

$this->CommitTrans();
