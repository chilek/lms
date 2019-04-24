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
	CREATE TABLE rttemplatetypes(
		id          int(11) NOT NULL auto_increment,
		templateid  int(11) NOT NULL,
		messagetype int(11) NOT NULL,
		PRIMARY KEY (id),
		UNIQUE KEY rttemplatetypes_templateid_key (templateid, messagetype),
		CONSTRAINT rttemplatetypes_templateid_fkey
			FOREIGN KEY (templateid) REFERENCES templates (id) ON DELETE CASCADE ON UPDATE CASCADE
	) ENGINE=InnoDB
");

$this->Execute("
	CREATE TABLE rttemplatequeues(
		id          int(11) NOT NULL auto_increment,
		templateid  int(11) NOT NULL,
		queueid  int(11) NOT NULL,
		PRIMARY KEY (id),
		UNIQUE KEY rttemplatequeues_templateid_key (templateid, queueid),
		CONSTRAINT rttemplatequeues_templateid_fkey
			FOREIGN KEY (templateid) REFERENCES templates (id) ON DELETE CASCADE ON UPDATE CASCADE,
		CONSTRAINT rttemplatequeues_queueid_fkey
			FOREIGN KEY (queueid) REFERENCES rtqueues (id) ON DELETE CASCADE ON UPDATE CASCADE
	) ENGINE=InnoDB
");

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2019042400', 'dbversion'));

$this->CommitTrans();

?>
