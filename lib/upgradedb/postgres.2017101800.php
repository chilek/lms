<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2017 LMS Developers
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

$this->Execute("ALTER TABLE rttickets ADD COLUMN requestor_mail varchar(255) DEFAULT NULL");
$this->Execute("ALTER TABLE rttickets ADD COLUMN requestor_phone varchar(32) DEFAULT NULL");
$this->Execute("ALTER TABLE rttickets ADD COLUMN requestor_userid integer DEFAULT NULL");
$this->Execute("ALTER TABLE rttickets ADD CONSTRAINT rttickets_requestor_userid_fkey FOREIGN KEY (requestor_userid) REFERENCES users (id) ON UPDATE CASCADE ON DELETE SET NULL");

if ($tickets = $this->GetAll('SELECT id, requestor FROM rttickets WHERE requestor ?LIKE? ?', array('%>%')))
  foreach ($tickets as $ticket)
    if (preg_match('/^(?<name>.+)\s+<(?<mail>[^>]+)>$/', $ticket['requestor'], $m))
        $this->Execute('UPDATE rttickets SET requestor = ?, requestor_mail = ? WHERE id = ?',
        array($m['name'], $m['mail'], $ticket['id']));

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2017101800', 'dbversion'));
$this->CommitTrans();

?>

