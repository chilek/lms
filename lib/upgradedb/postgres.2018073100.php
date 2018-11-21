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

$this->Execute("
	CREATE TABLE rtticketlastview (
		ticketid integer NOT NULL
			CONSTRAINT rtticketlastview_ticketid_fkey REFERENCES rttickets (id) ON UPDATE CASCADE ON DELETE CASCADE,
		userid integer NOT NULL
			CONSTRAINT rtticketlastview_userid_fkey REFERENCES users (id) ON UPDATE CASCADE ON DELETE CASCADE,
		vdate integer NOT NULL,
		CONSTRAINT rtticketlastview_ticketid_key UNIQUE (ticketid, userid)
	);
	CREATE INDEX rtticketlastview_vdate_idx ON rtticketlastview (vdate);
");

$this->Execute("
	INSERT INTO rtticketlastview (ticketid, userid, vdate) (
		SELECT t.id, u.id, ?NOW? FROM rttickets t, users u
	);
");

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2018073100', 'dbversion'));

$this->CommitTrans();

?>
