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
    CREATE SEQUENCE cashimport_id_seq;
    CREATE TABLE cashimport (
	id integer DEFAULT nextval('cashimport_id_seq'::text) NOT NULL,
	date integer DEFAULT 0 NOT NULL,
        value numeric(9,2) DEFAULT 0 NOT NULL,
        customer varchar(150) DEFAULT '' NOT NULL,
	description varchar(150) DEFAULT '' NOT NULL,
	customerid integer DEFAULT 0 NOT NULL,
	hash varchar(50) DEFAULT '' NOT NULL,
	closed smallint DEFAULT 0 NOT NULL,
	PRIMARY KEY (id))
");

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2005022300', 'dbversion'));
$this->CommitTrans();
