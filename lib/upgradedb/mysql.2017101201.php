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

$this->Execute("ALTER TABLE documents ADD CONSTRAINT documents_userid_fkey
		FOREIGN KEY (userid) REFERENCES users (id) ON DELETE SET NULL ON UPDATE CASCADE");
$this->Execute("ALTER TABLE documents ADD CONSTRAINT documents_cuserid_fkey
		FOREIGN KEY (cuserid) REFERENCES users (id) ON DELETE SET NULL ON UPDATE CASCADE");
$this->Execute("ALTER TABLE cash ADD CONSTRAINT cash_userid_fkey
		FOREIGN KEY (userid) REFERENCES users (id) ON DELETE SET NULL ON UPDATE CASCADE");
$this->Execute("ALTER TABLE rtqueues ADD CONSTRAINT rtqueues_deluserid_fkey
		FOREIGN KEY (deluserid) REFERENCES users (id) ON DELETE SET NULL ON UPDATE CASCADE");
$this->Execute("ALTER TABLE rttickets ADD CONSTRAINT rttickets_deluserid_fkey
		FOREIGN KEY (deluserid) REFERENCES users (id) ON DELETE SET NULL ON UPDATE CASCADE");
$this->Execute("ALTER TABLE rtmessages ADD CONSTRAINT rtmessages_userid_fkey
		FOREIGN KEY (userid) REFERENCES users (id) ON DELETE SET NULL ON UPDATE CASCADE");
$this->Execute("ALTER TABLE rtmessages ADD CONSTRAINT rtmessages_deluserid_fkey
		FOREIGN KEY (deluserid) REFERENCES users (id) ON DELETE SET NULL ON UPDATE CASCADE");
$this->Execute("ALTER TABLE events ADD CONSTRAINT events_userid_fkey
		FOREIGN KEY (userid) REFERENCES users (id) ON DELETE SET NULL ON UPDATE CASCADE");
$this->Execute("ALTER TABLE events ADD CONSTRAINT events_closeduserid_fkey
		FOREIGN KEY (closeduserid) REFERENCES users (id) ON DELETE SET NULL ON UPDATE CASCADE");
$this->Execute("ALTER TABLE events ADD CONSTRAINT events_moduserid_fkey
		FOREIGN KEY (moduserid) REFERENCES users (id) ON DELETE SET NULL ON UPDATE CASCADE");
$this->Execute("ALTER TABLE eventassignments ADD CONSTRAINT eventassignments_userid_fkey
		FOREIGN KEY (userid) REFERENCES users (id) ON DELETE CASCADE ON UPDATE CASCADE");
$this->Execute("ALTER TABLE docrights ADD CONSTRAINT docrights_userid_fkey
		FOREIGN KEY (userid) REFERENCES users (id) ON DELETE CASCADE ON UPDATE CASCADE");
$this->Execute("ALTER TABLE cashrights ADD CONSTRAINT cashrights_userid_fkey
		FOREIGN KEY (userid) REFERENCES users (id) ON DELETE CASCADE ON UPDATE CASCADE");
$this->Execute("ALTER TABLE cashreglog ADD CONSTRAINT cashreglog_userid_fkey
		FOREIGN KEY (userid) REFERENCES users (id) ON DELETE SET NULL ON UPDATE CASCADE");
$this->Execute("ALTER TABLE excludedgroups ADD CONSTRAINT excludedgroups_userid_fkey
		FOREIGN KEY (userid) REFERENCES users (id) ON DELETE CASCADE ON UPDATE CASCADE");
$this->Execute("ALTER TABLE messages ADD CONSTRAINT messages_userid_fkey
		FOREIGN KEY (userid) REFERENCES users (id) ON DELETE SET NULL ON UPDATE CASCADE");
$this->Execute("ALTER TABLE logtransactions ADD CONSTRAINT logtransactions_userid_fkey
		FOREIGN KEY (userid) REFERENCES users (id) ON DELETE SET NULL ON UPDATE CASCADE");

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2017101201', 'dbversion'));

$this->CommitTrans();
