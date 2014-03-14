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

$DB->BeginTrans();

$DB->Execute("ALTER TABLE customers CHANGE paytime paytime smallint NOT NULL DEFAULT '-1'");

$DB->Execute("DROP VIEW IF EXISTS customersview");

$DB->Execute("CREATE VIEW customersview AS
		SELECT c.* FROM customers c
		WHERE NOT EXISTS (
			SELECT 1 FROM customerassignments a
			JOIN excludedgroups e ON (a.customergroupid = e.customergroupid)
			WHERE e.userid = lms_current_user() AND a.customerid = c.id)
				AND c.type < ?", array(2));

$DB->Execute("ALTER TABLE documents CHANGE paytime paytime smallint NOT NULL DEFAULT '0'");
$DB->Execute("ALTER TABLE divisions CHANGE inv_paytime inv_paytime smallint DEFAULT NULL");

$DB->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2013111900', 'dbversion'));

$DB->CommitTrans();

?>
