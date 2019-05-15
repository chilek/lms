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
CREATE SEQUENCE states_id_seq;
CREATE TABLE states (
    	id 	integer DEFAULT nextval('states_id_seq'::text) NOT NULL,
	name 	varchar(255) NOT NULL DEFAULT '',
	description text NOT NULL DEFAULT '',
	PRIMARY KEY (id),
	UNIQUE (name)
);

CREATE SEQUENCE zipcodes_id_seq;
CREATE TABLE zipcodes (
    	id 	integer DEFAULT nextval('customerassignments_id_seq'::text) NOT NULL,
	zip 	varchar(10) NOT NULL DEFAULT '',
	stateid integer NOT NULL DEFAULT 0,
	PRIMARY KEY (id),
	UNIQUE (zip)
);

CREATE INDEX zipcodes_stateid_idx ON zipcodes (stateid);

CREATE INDEX customers_zip_idx ON customers (zip);
INSERT INTO zipcodes (zip) SELECT DISTINCT zip FROM customers;

");

if (ConfigHelper::getConfig('phpui.lang') == 'pl'
    || $this->GetOne("SELECT 1 FROM uiconfig WHERE var='lang' AND section='phpui' AND disabled=0 AND value='pl'")) {
    $this->Execute("
	INSERT INTO states (name) VALUES ('dolnośląskie');
	INSERT INTO states (name) VALUES ('kujawsko-pomorskie');
	INSERT INTO states (name) VALUES ('lubelskie');
	INSERT INTO states (name) VALUES ('lubuskie');
	INSERT INTO states (name) VALUES ('łódzkie');
	INSERT INTO states (name) VALUES ('małopolskie');
	INSERT INTO states (name) VALUES ('mazowieckie');
	INSERT INTO states (name) VALUES ('opolskie');
	INSERT INTO states (name) VALUES ('podkarpackie');
	INSERT INTO states (name) VALUES ('podlaskie');
	INSERT INTO states (name) VALUES ('pomorskie');
	INSERT INTO states (name) VALUES ('śląskie');
	INSERT INTO states (name) VALUES ('świętokrzyskie');
	INSERT INTO states (name) VALUES ('warmińsko-mazurskie');
	INSERT INTO states (name) VALUES ('wielkopolskie');
	INSERT INTO states (name) VALUES ('zachodniopomorskie');
	");
}

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2008080800', 'dbversion'));

$this->CommitTrans();
