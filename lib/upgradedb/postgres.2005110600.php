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

    ALTER TABLE assignments ADD COLUMN att smallint;
    UPDATE assignments SET att = at;
    ALTER TABLE assignments DROP COLUMN at;
    ALTER TABLE assignments ADD COLUMN at integer;
    UPDATE assignments SET at = att;
    ALTER TABLE assignments ALTER at SET NOT NULL;
    ALTER TABLE assignments ALTER at SET DEFAULT 0;
    ALTER TABLE assignments DROP COLUMN att;

    ALTER TABLE assignments ADD COLUMN liabilityid integer;
    UPDATE assignments SET liabilityid = 0;
    ALTER TABLE assignments ALTER liabilityid SET NOT NULL;
    ALTER TABLE assignments ALTER liabilityid SET DEFAULT 0;

    CREATE SEQUENCE \"liabilities_id_seq\";
    CREATE TABLE liabilities (
	    id integer DEFAULT nextval('liabilities_id_seq'::text) NOT NULL,
	    value numeric(9,2)	DEFAULT 0 NOT NULL,
	    name text 		DEFAULT '' NOT NULL,
	    taxid integer	DEFAULT 0 NOT NULL,
	    prodid varchar(255)	DEFAULT '' NOT NULL,
	    PRIMARY KEY (id)
    );
");

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2005110600', 'dbversion'));

$this->CommitTrans();
