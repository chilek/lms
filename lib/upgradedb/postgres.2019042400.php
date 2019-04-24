<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2019 LMS Developers
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
	CREATE SEQUENCE rttemplatetypes_id_seq;
	CREATE TABLE rttemplatetypes(
		id          integer DEFAULT nextval('templatetypes_id_seq'::text) NOT NULL,
		templateid  integer                                               NOT NULL
			CONSTRAINT rttemplatetypes_templateid_fkey REFERENCES templates (id) ON DELETE CASCADE ON UPDATE CASCADE,
		messagetype integer                                               NOT NULL,
		PRIMARY KEY (id),
		CONSTRAINT rttemplatetypes_templateid_key UNIQUE (templateid, messagetype)
	);
	CREATE SEQUENCE rttemplatequeues_id_seq;
	CREATE TABLE rttemplatequeues(
		id          integer DEFAULT nextval('rttemplatequeues_id_seq'::text) NOT NULL,
		templateid  integer                                               NOT NULL
			CONSTRAINT rttemplatequeues_templateid_fkey REFERENCES templates (id) ON DELETE CASCADE ON UPDATE CASCADE,
		queueid  integer                                               NOT NULL
			CONSTRAINT rttemplatequeues_queueid_fkey REFERENCES rtqueues (id) ON DELETE CASCADE ON UPDATE CASCADE,
		PRIMARY KEY (id),
		CONSTRAINT rttemplatequeues_templateid_key UNIQUE (templateid, queueid)
	)
");
$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2019042400', 'dbversion'));

$this->CommitTrans();

?>
