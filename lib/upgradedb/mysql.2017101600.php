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

$this->Execute("ALTER TABLE ewx_stm_nodes MODIFY channelid int(11) NULL");
$this->Execute("ALTER TABLE ewx_stm_nodes ALTER COLUMN channelid SET DEFAULT NULL");

$this->Execute("UPDATE ewx_stm_nodes SET channelid = NULL WHERE channelid = 0");
$ids = $this->GetCol("SELECT id FROM ewx_stm_channels");
if (empty($ids))
	$this->Execute("UPDATE ewx_stm_nodes SET channelid = NULL WHERE channelid IS NOT NULL");
else {
	$sql_ids = implode(',', $ids);
	$this->Execute("UPDATE ewx_stm_nodes SET channelid = NULL
		WHERE channelid IS NOT NULL AND channelid NOT IN (" . $sql_ids . ")");
}

$this->Execute("ALTER TABLE ewx_stm_nodes ADD CONSTRAINT ewx_stm_nodes_channelid_fkey
	FOREIGN KEY (channelid) REFERENCES ewx_stm_channels (id) ON DELETE SET NULL ON UPDATE CASCADE");

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2017101600', 'dbversion'));

$this->CommitTrans();

?>
