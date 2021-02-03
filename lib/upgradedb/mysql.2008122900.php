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

$this->Execute("
CREATE TABLE numberplanassignments (
        id int(11) NOT NULL auto_increment,
	planid int(11) NOT NULL DEFAULT 0,
	divisionid int(11) NOT NULL DEFAULT 0,
	PRIMARY KEY (id),
	UNIQUE KEY planid (planid, divisionid),
	INDEX divisionid (divisionid)
) ENGINE=MyISAM;
");

if ($divs = $this->GetAll('SELECT id FROM divisions')) {
    foreach ($divs as $div) {
        $this->Execute('INSERT INTO numberplanassignments (planid, divisionid)
			SELECT id, ? FROM numberplans', array($div['id']));
    }
}

$this->Execute('UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?', array('2008122900', 'dbversion'));
