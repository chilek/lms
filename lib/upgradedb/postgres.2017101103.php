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

$this->Execute("
	ALTER TABLE nodes ADD CONSTRAINT nodes_netdev_fkey
		FOREIGN KEY (netdev) REFERENCES netdevices (id) ON DELETE SET NULL ON UPDATE CASCADE;
	ALTER TABLE netlinks ALTER COLUMN src DROP DEFAULT;
	ALTER TABLE netlinks ADD CONSTRAINT netlinks_src_fkey
		FOREIGN KEY (src) REFERENCES netdevices (id) ON DELETE CASCADE ON UPDATE CASCADE;
	ALTER TABLE netlinks ALTER COLUMN dst DROP DEFAULT;
	ALTER TABLE netlinks ADD CONSTRAINT netlinks_dst_fkey
		FOREIGN KEY (dst) REFERENCES netdevices (id) ON DELETE CASCADE ON UPDATE CASCADE
");

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2017101103', 'dbversion'));

$this->CommitTrans();

?>
