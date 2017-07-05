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

$this->BeginTrans();

$this->Execute("ALTER TABLE documents ADD COLUMN recipient_address_id integer");
$this->Execute("ALTER TABLE documents ADD CONSTRAINT recipient_address_id_fk FOREIGN KEY (recipient_address_id) REFERENCES addresses (id) ON DELETE SET NULL ON UPDATE CASCADE");

$this->Execute("ALTER TABLE assignments DROP COLUMN address_id");
$this->Execute("ALTER TABLE assignments ADD COLUMN recipient_address_id integer");
$this->Execute("ALTER TABLE assignments ADD CONSTRAINT recipient_address_id_fk2 FOREIGN KEY (recipient_address_id) REFERENCES addresses (id) ON DELETE SET NULL ON UPDATE CASCADE");

$this->Execute("ALTER TABLE location_buildings DROP COLUMN flats");

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2017030100', 'dbversion'));

$this->CommitTrans();

?>
