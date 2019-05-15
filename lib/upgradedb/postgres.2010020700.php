<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2013 LMS Developers
 *
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
    CREATE SEQUENCE ewx_channels_id_seq;
    CREATE TABLE ewx_channels (
	id          integer         DEFAULT nextval('ewx_channels_id_seq'::text) NOT NULL,
        name        varchar(32)     DEFAULT '' NOT NULL,
	upceil      integer         DEFAULT 0 NOT NULL,
	downceil    integer         DEFAULT 0 NOT NULL,
	upceil_n    integer         DEFAULT NULL,
	downceil_n  integer         DEFAULT NULL,
	PRIMARY KEY (id),
	UNIQUE (name)
    );

    ALTER TABLE netdevices ADD channelid integer DEFAULT NULL
	    REFERENCES ewx_channels (id) ON DELETE SET NULL ON UPDATE CASCADE;
    CREATE INDEX netdevices_channelid_idx ON netdevices (channelid);
");

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2010020700', 'dbversion'));

$this->CommitTrans();
