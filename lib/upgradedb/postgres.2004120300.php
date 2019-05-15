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
    CREATE SEQUENCE domains_id_seq;
    CREATE TABLE domains (
	id integer DEFAULT nextval('domains_id_seq'::text) NOT NULL,
	name varchar(255) DEFAULT '' NOT NULL,
	description text DEFAULT '' NOT NULL,
	PRIMARY KEY (id),
	UNIQUE (name)
    );
    INSERT INTO domains (name) SELECT DISTINCT domain FROM passwd WHERE domain != '';
    ALTER TABLE passwd ADD domainid integer;
    UPDATE passwd SET domainid = 0;
    ALTER TABLE passwd ALTER domainid SET NOT NULL;
    ALTER TABLE passwd ALTER domainid SET DEFAULT 0;

");
if ($domains = $this->GetAll('SELECT id, name FROM domains')) {
    foreach ($domains as $row) {
        $this->Execute('UPDATE passwd SET domainid=? WHERE domain=?', array($row['id'], $row['name']));
    }
}
$this->Execute('ALTER TABLE passwd DROP domain');

$this->Execute("UPDATE dbinfo SET keyvalue = '2004120300' WHERE keytype = 'dbversion'");
$this->CommitTrans();
