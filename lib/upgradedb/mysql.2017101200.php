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

$this->Execute("ALTER TABLE documents MODIFY userid int(11) NULL");
$this->Execute("ALTER TABLE documents ALTER COLUMN userid SET DEFAULT NULL");
$this->Execute("ALTER TABLE documents MODIFY cuserid int(11) NULL");
$this->Execute("ALTER TABLE documents ALTER COLUMN cuserid SET DEFAULT NULL");
$this->Execute("ALTER TABLE cash MODIFY userid int(11) NULL");
$this->Execute("ALTER TABLE cash ALTER COLUMN userid SET DEFAULT NULL");
$this->Execute("ALTER TABLE rtqueues MODIFY deluserid int(11) NULL");
$this->Execute("ALTER TABLE rtqueues ALTER COLUMN deluserid SET DEFAULT NULL");
$this->Execute("ALTER TABLE rttickets MODIFY deluserid int(11) NULL");
$this->Execute("ALTER TABLE rttickets ALTER COLUMN deluserid SET DEFAULT NULL");
$this->Execute("ALTER TABLE rtmessages MODIFY userid int(11) NULL");
$this->Execute("ALTER TABLE rtmessages ALTER COLUMN userid SET DEFAULT NULL");
$this->Execute("ALTER TABLE rtmessages MODIFY deluserid int(11) NULL");
$this->Execute("ALTER TABLE rtmessages ALTER COLUMN deluserid SET DEFAULT NULL");
$this->Execute("ALTER TABLE events MODIFY userid int(11) NULL");
$this->Execute("ALTER TABLE events ALTER COLUMN userid SET DEFAULT NULL");
$this->Execute("ALTER TABLE events MODIFY closeduserid int(11) NULL");
$this->Execute("ALTER TABLE events ALTER COLUMN closeduserid SET DEFAULT NULL");
$this->Execute("ALTER TABLE events MODIFY moduserid int(11) NULL");
$this->Execute("ALTER TABLE events ALTER COLUMN moduserid SET DEFAULT NULL");
$this->Execute("ALTER TABLE eventassignments ALTER COLUMN userid DROP DEFAULT");
$this->Execute("ALTER TABLE docrights ALTER COLUMN userid DROP DEFAULT");
$this->Execute("ALTER TABLE cashrights ALTER COLUMN userid DROP DEFAULT");
$this->Execute("ALTER TABLE cashreglog MODIFY userid int(11) NULL");
$this->Execute("ALTER TABLE cashreglog ALTER COLUMN userid SET DEFAULT NULL");
$this->Execute("ALTER TABLE excludedgroups ALTER COLUMN userid DROP DEFAULT");
$this->Execute("ALTER TABLE messages MODIFY userid int(11) NULL");
$this->Execute("ALTER TABLE messages ALTER COLUMN userid SET DEFAULT NULL");
$this->Execute("ALTER TABLE logtransactions MODIFY userid int(11) NULL");
$this->Execute("ALTER TABLE logtransactions ALTER COLUMN userid SET DEFAULT NULL");

$userids = $this->GetCol("SELECT id FROM users");
if (!empty($userids)) {
	$sql_userids = implode(',', $userids);
	$this->Execute("UPDATE documents SET userid = NULL WHERE userid = 0 OR userid NOT IN (" . $sql_userids . ")");
	$this->Execute("UPDATE documents SET cuserid = NULL WHERE cuserid = 0 OR cuserid NOT IN (" . $sql_userids . ")");
	$this->Execute("UPDATE cash SET userid = NULL WHERE userid = 0 OR userid NOT IN (" . $sql_userids . ")");
	$this->Execute("UPDATE rtqueues SET deluserid = NULL WHERE deluserid = 0 OR deluserid NOT IN (" . $sql_userids . ")");
	$this->Execute("UPDATE rttickets SET deluserid = NULL WHERE deluserid = 0 OR deluserid NOT IN (" . $sql_userids . ")");
	$this->Execute("UPDATE rtmessages SET userid = NULL WHERE userid = 0 OR userid NOT IN (" . $sql_userids . ")");
	$this->Execute("UPDATE rtmessages SET deluserid = NULL WHERE deluserid = 0 OR deluserid NOT IN (" . $sql_userids . ")");
	$this->Execute("UPDATE events SET userid = NULL WHERE userid = 0 OR userid NOT IN (" . $sql_userids . ")");
	$this->Execute("UPDATE events SET closeduserid = NULL WHERE closeduserid = 0 OR closeduserid NOT IN (" . $sql_userids . ")");
	$this->Execute("UPDATE events SET moduserid = NULL WHERE moduserid = 0 OR moduserid NOT IN (" . $sql_userids . ")");
	$this->Execute("DELETE FROM eventassignments WHERE userid = 0 OR userid NOT IN (" . $sql_userids . ")");
	$this->Execute("DELETE FROM docrights WHERE userid = 0 OR userid NOT IN (" . $sql_userids . ")");
	$this->Execute("DELETE FROM cashrights WHERE userid = 0 OR userid NOT IN (" . $sql_userids . ")");
	$this->Execute("UPDATE cashreglog SET userid = NULL WHERE userid = 0 OR userid NOT IN (" . $sql_userids . ")");
	$this->Execute("DELETE FROM excludedgroups WHERE userid = 0 OR userid NOT IN (" . $sql_userids . ")");
	$this->Execute("UPDATE messages SET userid = NULL WHERE userid = 0 OR userid NOT IN (" . $sql_userids . ")");
	$this->Execute("UPDATE logtransactions SET userid = NULL WHERE userid = 0 OR userid NOT IN (" . $sql_userids . ")");
}

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

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2017101200', 'dbversion'));

$this->CommitTrans();

?>
