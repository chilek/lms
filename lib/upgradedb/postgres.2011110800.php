<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2012 LMS Developers
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

$DB->BeginTrans();

$DB->Execute("
	DROP VIEW vnodes;
	DROP VIEW vmacs;
");

$DB->Execute("ALTER TABLE nodes ADD longitude numeric(10, 6) DEFAULT NULL");
$DB->Execute("ALTER TABLE nodes ADD latitude numeric(10, 6) DEFAULT NULL");


$DB->Execute("
	CREATE VIEW vnodes AS
	SELECT n.*, m.mac
		FROM nodes n
		LEFT JOIN (SELECT nodeid, array_to_string(array_agg(mac), ',') AS mac
			FROM macs GROUP BY nodeid) m ON (n.id = m.nodeid);
	CREATE VIEW vmacs AS
	SELECT n.*, m.mac, m.id AS macid
		FROM nodes n
		JOIN macs m ON (n.id = m.nodeid);
");

$DB->Execute("ALTER TABLE netdevices ADD longitude numeric(10, 6) DEFAULT NULL");
$DB->Execute("ALTER TABLE netdevices ADD latitude numeric(10, 6) DEFAULT NULL");

$DB->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?",array('2011110800', 'dbversion'));

$DB->CommitTrans();

?>
