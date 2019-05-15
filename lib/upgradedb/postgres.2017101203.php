<?php

/**
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2017 LMS Developers
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

$this->Execute("
	ALTER TABLE customers ADD CONSTRAINT customers_creatorid_fkey
		FOREIGN KEY (creatorid) REFERENCES users (id) ON DELETE SET NULL ON UPDATE CASCADE;
	ALTER TABLE customers ADD CONSTRAINT customers_modid_fkey
		FOREIGN KEY (modid) REFERENCES users (id) ON DELETE SET NULL ON UPDATE CASCADE;
	ALTER TABLE nodes ADD CONSTRAINT nodes_creatorid_fkey
		FOREIGN KEY (creatorid) REFERENCES users (id) ON DELETE SET NULL ON UPDATE CASCADE;
	ALTER TABLE nodes ADD CONSTRAINT nodes_modid_fkey
		FOREIGN KEY (modid) REFERENCES users (id) ON DELETE SET NULL ON UPDATE CASCADE;
	ALTER TABLE voipaccounts ADD CONSTRAINT voipaccounts_creatorid_fkey
		FOREIGN KEY (creatorid) REFERENCES users (id) ON DELETE SET NULL ON UPDATE CASCADE;
	ALTER TABLE voipaccounts ADD CONSTRAINT voipaccounts_modid_fkey
		FOREIGN KEY (modid) REFERENCES users (id) ON DELETE SET NULL ON UPDATE CASCADE;
	ALTER TABLE rttickets ADD CONSTRAINT rttickets_creatorid_fkey
		FOREIGN KEY (creatorid) REFERENCES users (id) ON DELETE SET NULL ON UPDATE CASCADE;
");

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2017101203', 'dbversion'));

$this->CommitTrans();
