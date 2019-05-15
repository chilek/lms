<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2013 LMS Developers
 *
 *  Please, see the doc/AUTHORS for more information about authors!
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
 *  $Id$
 */

$this->BeginTrans();

$this->Execute("
    CREATE SEQUENCE hosts_id_seq;
    CREATE TABLE hosts (
	id integer DEFAULT nextval('hosts_id_seq'::text) NOT NULL,
        name varchar(255)           DEFAULT '' NOT NULL,
	description text            DEFAULT '' NOT NULL,
        lastreload integer          DEFAULT 0 NOT NULL,
	reload smallint             DEFAULT 0 NOT NULL,
        PRIMARY KEY (id),
	UNIQUE (name)
    );
    INSERT INTO hosts (id, name, description, lastreload, reload) SELECT id, name, description, lastreload, reload FROM daemonhosts;
    DROP TABLE daemonhosts;
    SELECT setval('hosts_id_seq', MAX(id)) FROM hosts;
");

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2005082900', 'dbversion'));

$this->CommitTrans();
