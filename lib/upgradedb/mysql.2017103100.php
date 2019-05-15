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

$this->Execute("ALTER TABLE promotionassignments ADD COLUMN orderid int(11) NOT NULL DEFAULT 0");

$assignments = $this->GetAll("SELECT a.id, a.promotionschemaid AS schemaid, tariffid FROM promotionassignments a
	JOIN tariffs t ON t.id = a.tariffid
	ORDER BY a.promotionschemaid, t.name, t.value DESC");
if (!empty($assignments)) {
    $schemaid = 0;
    foreach ($assignments as $a) {
        if ($a['schemaid'] != $schemaid) {
            $schemaid = $a['schemaid'];
            $orderid = 1;
        } else {
            $orderid++;
        }
        $this->Execute(
            "UPDATE promotionassignments SET orderid = ? WHERE id = ?",
            array($orderid, $a['id'])
        );
    }
}

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2017103100', 'dbversion'));

$this->CommitTrans();
