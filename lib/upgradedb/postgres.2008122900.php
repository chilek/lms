<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2012 LMS Developers
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

$DB->BeginTrans();

$DB->Execute("
CREATE SEQUENCE numberplanassignments_id_seq;
CREATE TABLE numberplanassignments (
        id integer DEFAULT nextval('numberplanassignments_id_seq'::text) NOT NULL,
        planid integer DEFAULT 0 NOT NULL,
        divisionid integer DEFAULT 0 NOT NULL,
        PRIMARY KEY (id),
        CONSTRAINT numberplanassignments_planid_key UNIQUE (planid, divisionid)
);
CREATE INDEX numberplanassignments_divisionid_idx ON numberplanassignments (divisionid);
");

if($divs = $DB->GetAll('SELECT id FROM divisions'))
	foreach($divs as $div)
		$DB->Execute('INSERT INTO numberplanassignments (planid, divisionid)
			SELECT id, ? FROM numberplans', array($div['id']));

$DB->Execute('UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?', array('2008122900', 'dbversion'));

$DB->CommitTrans();

?>
