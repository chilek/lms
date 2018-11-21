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
	ALTER TABLE documents ALTER COLUMN userid DROP NOT NULL;
	ALTER TABLE documents ALTER COLUMN userid SET DEFAULT NULL;
	ALTER TABLE documents ALTER COLUMN cuserid DROP NOT NULL;
	ALTER TABLE documents ALTER COLUMN cuserid SET DEFAULT NULL;
	ALTER TABLE cash ALTER COLUMN userid DROP NOT NULL;
	ALTER TABLE cash ALTER COLUMN userid SET DEFAULT NULL;
	ALTER TABLE rtqueues ALTER COLUMN deluserid DROP NOT NULL;
	ALTER TABLE rtqueues ALTER COLUMN deluserid SET DEFAULT NULL;
	ALTER TABLE rttickets ALTER COLUMN deluserid DROP NOT NULL;
	ALTER TABLE rttickets ALTER COLUMN deluserid SET DEFAULT NULL;
	ALTER TABLE rtmessages ALTER COLUMN userid DROP NOT NULL;
	ALTER TABLE rtmessages ALTER COLUMN userid SET DEFAULT NULL;
	ALTER TABLE rtmessages ALTER COLUMN deluserid DROP NOT NULL;
	ALTER TABLE rtmessages ALTER COLUMN deluserid SET DEFAULT NULL;
	ALTER TABLE events ALTER COLUMN userid DROP NOT NULL;
	ALTER TABLE events ALTER COLUMN userid SET DEFAULT NULL;
	ALTER TABLE events ALTER COLUMN closeduserid DROP NOT NULL;
	ALTER TABLE events ALTER COLUMN closeduserid SET DEFAULT NULL;
	ALTER TABLE events ALTER COLUMN moduserid DROP NOT NULL;
	ALTER TABLE events ALTER COLUMN moduserid SET DEFAULT NULL;
	ALTER TABLE eventassignments ALTER COLUMN userid DROP DEFAULT;
	ALTER TABLE docrights ALTER COLUMN userid DROP DEFAULT;
	ALTER TABLE cashrights ALTER COLUMN userid DROP DEFAULT;
	ALTER TABLE cashreglog ALTER COLUMN userid DROP NOT NULL;
	ALTER TABLE cashreglog ALTER COLUMN userid SET DEFAULT NULL;
	ALTER TABLE excludedgroups ALTER COLUMN userid DROP DEFAULT;
	ALTER TABLE messages ALTER COLUMN userid DROP NOT NULL;
	ALTER TABLE messages ALTER COLUMN userid SET DEFAULT NULL;
	ALTER TABLE logtransactions ALTER COLUMN userid DROP NOT NULL;
	ALTER TABLE logtransactions ALTER COLUMN userid SET DEFAULT NULL;
");

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

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2017101200', 'dbversion'));

$this->CommitTrans();

?>
