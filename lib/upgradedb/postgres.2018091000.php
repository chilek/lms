<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2018 LMS Developers
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

$this->Execute('ALTER TABLE events ALTER COLUMN begintime TYPE integer');
$this->Execute('ALTER TABLE events ALTER COLUMN endtime TYPE integer');

$events = $this->GetAll('SELECT id, begintime, endtime FROM events');
if (!empty($events))
	foreach ($events as $event) {
		$begintime = floor($event['begintime'] / 100) * 3600 + ($event['begintime'] % 100) * 60;
		$endtime = floor($event['endtime'] / 100) * 3600 + ($event['endtime'] % 100) * 60;
		$this->Execute('UPDATE events SET begintime = ?, endtime = ? WHERE id = ?',
			array($begintime, $endtime, $event['id']));
	}

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2018091000', 'dbversion'));

$this->CommitTrans();

?>
