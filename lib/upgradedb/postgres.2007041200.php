<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2013 LMS Developers
 *
 *  Please, see the doc/AUTHORS for more information about authors!
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
 *  $Id$
 */

$this->BeginTrans();

$this->Execute("ALTER TABLE cashreglog ADD snapshot numeric(9,2)");

$list = $this->GetAll('SELECT id, regid, time FROM cashreglog');

if ($list) {
    foreach ($list as $row) {
        $val = $this->GetOne(
            'SELECT SUM(value) FROM receiptcontents
	                LEFT JOIN documents ON (docid = documents.id)
			WHERE cdate <= ? AND regid = ?',
            array($row['time'], $row['regid'])
        );

        $this->Execute(
            'UPDATE cashreglog SET snapshot = ? WHERE id = ?',
            array(str_replace(',', '.', floatval($val)), $row['id'])
        );
    }
}

$this->Execute("ALTER TABLE cashreglog ALTER snapshot SET NOT NULL");
$this->Execute("ALTER TABLE cashreglog ALTER snapshot SET DEFAULT 0");

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2007041200', 'dbversion'));

$this->CommitTrans();
