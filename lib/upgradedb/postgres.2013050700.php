<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2013 LMS Developers
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

$this->Execute("SET CONSTRAINTS ALL IMMEDIATE");

$this->Execute("
	DROP VIEW vnodes;
	DROP VIEW vmacs;
");

$this->Execute("ALTER TABLE nodes ADD COLUMN netid integer NOT NULL DEFAULT 0");

$this->Execute("ALTER TABLE nodes DROP CONSTRAINT nodes_ipaddr_key");
$this->Execute("ALTER TABLE nodes ADD CONSTRAINT nodes_ipaddr_key UNIQUE (ipaddr, netid)");

$nodes = $this->GetAll("SELECT n.id, INET_NTOA(n.ipaddr) AS ipaddr, net.id AS netid
	FROM nodes n JOIN networks net ON net.address = n.ipaddr & INET_ATON(net.mask)
	ORDER BY net.id");
if (!empty($nodes))
	foreach ($nodes as $node)
		$this->Execute("UPDATE nodes SET netid = ? WHERE id = ?",
			array($node['netid'], $node['id']));

$this->Execute("ALTER TABLE nodes ADD CONSTRAINT nodes_netid_fkey FOREIGN KEY (netid) REFERENCES networks (id) ON DELETE CASCADE ON UPDATE CASCADE");

$this->Execute("
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

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2013050700', 'dbversion'));

$this->CommitTrans();

?>
