<?php

/*
 * LMS version 27.x
 *
 *  (C) Copyright 2001-2021 LMS Developers
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


$this->Execute("
	CREATE TABLE rtticketwatchers (
		id int(11) NOT NULL auto_increment,
		ticketid int(11) NOT NULL,
		userid int(11) NOT NULL,
		PRIMARY KEY (id),
		CONSTRAINT rtticketwatchers_rttickets_fkey
		    FOREIGN KEY (ticketid) REFERENCES rttickets (id) ON DELETE CASCADE ON UPDATE CASCADE,
		CONSTRAINT rtticketwatchers_users_fkey
		    FOREIGN KEY (userid) REFERENCES users (id) ON DELETE CASCADE ON UPDATE CASCADE,
		UNIQUE KEY rtticketwatchers_ticketid_ukey (ticketid, userid)
	);
");
