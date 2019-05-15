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
	DROP VIEW customerview;
	DROP VIEW contractorview;
	DROP VIEW customeraddressview;
");

$this->Execute("ALTER TABLE customers ADD COLUMN rbename varchar(255) NOT NULL DEFAULT ''");

$this->Execute("
CREATE VIEW customerview AS
	SELECT c.*,
		(CASE WHEN building IS NULL THEN street ELSE (CASE WHEN apartment IS NULL THEN street || ' ' || building
			ELSE street || ' ' || building || '/' || apartment END) END) AS address,
		(CASE WHEN post_street IS NULL THEN '' ELSE
			(CASE WHEN post_building IS NULL THEN post_street ELSE (CASE WHEN post_apartment IS NULL THEN post_street || ' ' || post_building
				ELSE post_street || ' ' || post_building || '/' || post_apartment END)
			END)
		END) AS post_address
	FROM customers c
	WHERE NOT EXISTS (
			SELECT 1 FROM customerassignments a
			JOIN excludedgroups e ON (a.customergroupid = e.customergroupid)
			WHERE e.userid = lms_current_user() AND a.customerid = c.id)
		AND c.type < 2;

CREATE VIEW contractorview AS
	SELECT c.*,
		(CASE WHEN building IS NULL THEN street ELSE (CASE WHEN apartment IS NULL THEN street || ' ' || building
			ELSE street || ' ' || building || '/' || apartment END) END) AS address,
		(CASE WHEN post_street IS NULL THEN '' ELSE
			(CASE WHEN post_building IS NULL THEN post_street ELSE (CASE WHEN post_apartment IS NULL THEN post_street || ' ' || post_building
				ELSE post_street || ' ' || post_building || '/' || post_apartment END)
			END)
		END) AS post_address
	FROM customers c
	WHERE c.type = 2;

CREATE VIEW customeraddressview AS
	SELECT c.*,
		(CASE WHEN building IS NULL THEN street ELSE (CASE WHEN apartment IS NULL THEN street || ' ' || building
			ELSE street || ' ' || building || '/' || apartment END) END) AS address,
		(CASE WHEN post_street IS NULL THEN '' ELSE
			(CASE WHEN post_building IS NULL THEN post_street ELSE (CASE WHEN post_apartment IS NULL THEN post_street || ' ' || post_building
				ELSE post_street || ' ' || post_building || '/' || post_apartment END)
			END)
		END) AS post_address
	FROM customers c
	WHERE c.type < 2;
");

$this->Execute("
	ALTER TABLE divisions ADD COLUMN rbe varchar(255) NOT NULL DEFAULT '';
	ALTER TABLE divisions ADD COLUMN rbename varchar(255) NOT NULL DEFAULT '';
");

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2017012400', 'dbversion'));

$this->CommitTrans();
