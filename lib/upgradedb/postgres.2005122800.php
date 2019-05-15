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

$create_reg = $this->GetOne('SELECT docid FROM receiptcontents LIMIT 1');

$this->Execute("

    ALTER TABLE receiptcontents ADD COLUMN regid integer;
    UPDATE receiptcontents SET regid = ?;
    ALTER TABLE receiptcontents ALTER regid SET NOT NULL;
    ALTER TABLE receiptcontents ALTER regid SET DEFAULT 0;
    
    CREATE INDEX receiptcontents_regid_idx ON receiptcontents (regid);

    CREATE SEQUENCE \"cashrights_id_seq\";
    CREATE TABLE cashrights (
	id integer DEFAULT nextval('cashrights_id_seq'::text) NOT NULL,
        userid integer DEFAULT 0 NOT NULL,
	regid integer DEFAULT 0 NOT NULL,
	rights integer DEFAULT 0 NOT NULL,
	PRIMARY KEY (id),
	UNIQUE (userid, regid)
    );

    CREATE SEQUENCE \"cashregs_id_seq\";
    CREATE TABLE cashregs (
	id integer DEFAULT nextval('cashregs_id_seq'::text) NOT NULL,
        name varchar(255) DEFAULT '' NOT NULL,
	description text DEFAULT '' NOT NULL,
	in_numberplanid integer DEFAULT 0 NOT NULL,
	out_numberplanid integer DEFAULT 0 NOT NULL,
	PRIMARY KEY (id),
	UNIQUE (name)
    )
", array($create_reg ? 1 : 0));

if ($create_reg) {
    $this->Execute("INSERT INTO cashregs (name) VALUES ('default')");
}

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2005122800', 'dbversion'));

$this->CommitTrans();
