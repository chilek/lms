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
    CREATE SEQUENCE rtticketwatchers_id_seq;
    CREATE TABLE rtticketwatchers (
        id integer  DEFAUlT nextval('rtticketwatchers_id_seq'::text) NOT NULL,
        ticketid integer    NOT NULL
            CONSTRAINT rtticketwatchers_rttickets_fkey REFERENCES rttickets (id) ON DELETE CASCADE ON UPDATE CASCADE,
        userid integer	NOT NULL
            CONSTRAINT rtticketwatchers_users_fkey REFERENCES users (id) ON DELETE CASCADE ON UPDATE CASCADE,
        CONSTRAINT rtticketwatchers_ticketid_ukey UNIQUE (ticketid, userid),
        PRIMARY KEY (id)
    )
");
