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


$this->Execute("ALTER TABLE vlans ADD COLUMN netnodeid int(11) DEFAULT NULL");
$this->Execute("ALTER TABLE vlans ADD CONSTRAINT vlans_netnodeid_fkey
                    FOREIGN KEY (netnodeid) REFERENCES netnodes(id) ON DELETE SET NULL ON UPDATE CASCADE");

$this->Execute("ALTER TABLE vlans DROP CONSTRAINT vlans_ukey");
$this->Execute("ALTER TABLE vlans ADD CONSTRAINT vlans_customerid_ukey UNIQUE (vlanid, customerid)");
$this->Execute("ALTER TABLE vlans ADD CONSTRAINT vlans_netnodeid_ukey UNIQUE (vlanid, netnodeid)");
