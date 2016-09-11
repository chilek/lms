<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2015 LMS Developers
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
/**
 * @author Maciej_Wawryk
 */

$this->BeginTrans();

$this->Execute("
	CREATE TABLE passwdhistory (
		id int(11) NOT NULL auto_increment,
		userid int(11) NOT NULL,
		hash varchar(255) DEFAULT '' NOT NULL,
		FOREIGN KEY (userid) REFERENCES users (id) ON DELETE CASCADE ON UPDATE CASCADE,
		PRIMARY KEY (id)
	) ENGINE=InnoDB
");

$this->Execute("INSERT INTO uiconfig (section, var, value) VALUES(?, ?, ?)", array('phpui', 'passwordhistory', 6));

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2015122301', 'dbversion'));

$this->CommitTrans();

?>
