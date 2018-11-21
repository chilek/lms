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

$this->BeginTrans();

$this->Execute("ALTER TABLE voip_numbers ADD COLUMN `index` smallint");

$numbers_list = $this->GetAll("SELECT voip_account_id, phone FROM voip_numbers ORDER BY id");
$counter = array();

if ($numbers_list) {
	foreach ($numbers_list as $number) {
		$vaccid = $number['voip_account_id'];
		$phone  = $number['phone'];

		if (isset($counter[$vaccid])) {
			++$counter[$vaccid];
			$this->Execute("UPDATE voip_numbers SET `index` = ? WHERE phone ?LIKE? ?", array($counter[$vaccid], $phone));
		} else {
			$counter[$vaccid] = 1;
			$this->Execute("UPDATE voip_numbers SET `index` = 1 WHERE phone ?LIKE? ?", array($phone));
		}
	}
}

$this->Execute("ALTER TABLE voip_numbers ADD CONSTRAINT vn_uniq_index UNIQUE (voip_account_id, `index`)");

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2016100500', 'dbversion'));

$this->CommitTrans();

?>
