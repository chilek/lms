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

// Internet Messengers
define('IM_GG', 0);
define('IM_YAHOO', 1);
define('IM_SKYPE', 2);

define('CONTACT_IM_GG', 512);
define('CONTACT_IM_YAHOO', 1024);
define('CONTACT_IM_SKYPE', 2048);

$this->BeginTrans();

$this->Execute("DROP VIEW IF EXISTS customermailsview");

$this->Execute("ALTER TABLE customercontacts CHANGE type type int(11) DEFAULT NULL");

$this->Execute("
	CREATE VIEW customermailsview AS
		SELECT customerid, GROUP_CONCAT(contact SEPARATOR ',') AS email
			FROM customercontacts
			WHERE (type & 8) > 0 AND contact <> ''
			GROUP BY customerid;
");

$ims = $this->GetAll("SELECT customerid, uid, type FROM imessengers");
if (!empty($ims))
	foreach ($ims as $im) {
		switch ($im['type']) {
			case IM_GG:
				$type = CONTACT_IM_GG;
				break;
			case IM_YAHOO:
				$type = CONTACT_IM_YAHOO;
				break;
			case IM_SKYPE:
				$type = CONTACT_IM_SKYPE;
				break;
		}
		$this->Execute("INSERT INTO customercontacts (customerid, name, contact, type)
			VALUES (?, ?, ?, ?)", array($im['customerid'], '', $im['uid'], $type));
	}

$this->Execute("DROP TABLE imessengers");

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2016101000', 'dbversion'));

$this->CommitTrans();

?>
