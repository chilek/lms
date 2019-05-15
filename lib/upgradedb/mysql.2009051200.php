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
CREATE TABLE docrights (
    id          int(11)         NOT NULL auto_increment,
    userid      int(11)         DEFAULT '0' NOT NULL,
    doctype     int(11)         DEFAULT '0' NOT NULL,
    rights      int(11)         DEFAULT '0' NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY userid (userid, doctype)
) ENGINE=MyISAM;
");

foreach (array(-1,-2,-3,-4,-5,-6,-7,-8, -9,-10) as $doctype) {
    $this->Execute(
        "INSERT INTO docrights (userid, doctype, rights)
		SELECT id, ?, ? FROM users WHERE deleted = 0",
        array($doctype, 31)
    );
}
/*
1 - view
2 - create
4 - confirm
8 - edit
16 - delete
*/

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2009051200', 'dbversion'));
